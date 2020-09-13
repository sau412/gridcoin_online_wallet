<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/corecoin.php");
require_once("../lib/email.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

$f=fopen($lockfile,"w");
if($f) {
        echo "Checking locks\n";
        if(!flock($f,LOCK_EX|LOCK_NB)) {
		die("Lockfile locked\n");
	}
}

function update_received_by_address($address) {
	$address_received = coin_rpc_get_received_by_address($address);
	echo "Address $address received $address_received\n";

	// Continue if no coins received
	if($address_received == 0) return;

	$address_escaped=db_escape($address);
	$address_received_escaped=db_escape($address_received);
	db_query("UPDATE `wallets` SET `received`='$address_received_escaped'
				WHERE `address`='$address_escaped' AND `received`<'$address_received_escaped'");
}

function update_transaction($user_uid, $address, $txid) {
    global $wallet_receive_confirmations;

    $transaction = coin_rpc_get_single_transaction($txid);
	$vout_array = $transaction['vout'];
	if(!$vout_array) {
		$vout_array = $transaction['decoded']['vout'];
	}
    $confirmations = $transaction["confirmations"];
    $total_amount = 0;
    foreach($vout_array as $vout) {
        $vout_value = $vout['value'];
        $vout_address = array_pop($vout["scriptPubKey"]["addresses"]);
        if($address == $vout_address) {
            $total_amount += $vout_value;
        }
    }

    $address_escaped = db_escape($address);
    $user_uid_escaped = db_escape($user_uid);
    $txid_escaped = db_escape($txid);
    $total_amount_escaped = db_escape($total_amount);
    $exists_txid_uid = db_query_to_variable("SELECT `uid` FROM `transactions`
                                                WHERE `tx_id` = '$txid_escaped' AND
														`address` = '$address_escaped' AND
                                                        `status` IN ('received', 'pending') AND
                                                        `user_uid` = '$user_uid_escaped'");
    $status = "pending";
    if($confirmations >= $wallet_receive_confirmations) {
        $status = "received";
    }
    $status_escaped = db_escape($status);
    $confirmations_escaped = db_escape($confirmations);
    
    if($exists_txid_uid) {
        // Update exists transaction record
        $exists_txid_uid_escaped = db_escape($exists_txid_uid);

        db_query("UPDATE `transactions` SET `status` = '$status_escaped', `confirmations` = '$confirmations_escaped'
                    WHERE `uid` = '$exists_txid_uid_escaped'");
    }
    else {
        // Insert new transaction record
        db_query("INSERT INTO `transactions` (`user_uid`, `amount`, `address`, `status`, `tx_id`, `confirmations`)
                    VALUES ('$user_uid_escaped', '$total_amount_escaped', '$address_escaped',
                            '$status_escaped', '$txid_escaped', '$confirmations_escaped')");
    }
}

db_connect();

// Get current block
$network_block=coin_rpc_get_block_count();

if(!isset($network_block) || $network_block==FALSE) {
	die("Client down\n");
}

$wallet_block=get_variable("current_block");
if(isset($wallet_block) && $wallet_block!=0 && isset($network_block) && $network_block!=0) {
	if($network_block<$wallet_block) {
		set_variable("payouts_enabled",0);
		log_write("Wallet block '$wallet_block' greater than network block '$network_block', possible fork, sending disabled");
	}
}

if(isset($network_block) && $network_block!=0) {
	set_variable("current_block",$network_block);
	$current_block_hash=coin_rpc_get_block_hash($network_block);
	set_variable("current_block_hash",$current_block_hash);
}

$received_hash = [];
$received_transactions_array = db_Query_to_array("SELECT DISTINCT `tx_id`, `address` FROM `transactions` WHERE `status` IN ('received')");
foreach($received_transactions_array as $received_transaction_data) {
	$tx_id = $received_transaction_data['tx_id'];
	$address = $received_transaction_data['address'];

	$hash = hash("sha256", $tx_id.$address);
	if(!in_array($hash, $received_hash)) {
		$received_hash[] = $hash;
	}
}

$received_by_address_array = coin_rpc_list_received_by_address();

foreach($received_by_address_array as $received_by_address) {
    $address = $received_by_address['address'];
    $amount = $received_by_address['amount'];
    $txids_array = $received_by_address['txids'];

    if(!$address) continue;
    if(!$amount) continue;

    $address_escaped = db_escape($address);
    $received = db_query_to_variable("SELECT `received` FROM `wallets` WHERE `address` = '$address_escaped'");
	$update_all = true;
	if($amount > $received || $update_all) {
        $user_uid=db_query_to_variable("SELECT `user_uid` FROM `wallets` WHERE `address`='$address_escaped'");

        if(!$user_uid) continue;

        echo "Something received user $user_uid for $address, syncing transactions\n";

        foreach($txids_array as $txid) {
			$hash = hash("sha256", $txid.$address);
			if(!in_array($hash, $received_hash)) {
				echo "Syncing transaction $txid\n";
				update_transaction($user_uid, $address, $txid);
			} else {
				echo "Transaction $txid already exists\n";
			}
			/*$txid_escaped = db_escape($txid);
			$address_escaped = db_escape($address);
			$exists = db_query_to_variable("SELECT 1 FROM `transactions`
											WHERE `tx_id` = '$txid_escaped' AND
												`address` = '$address_escaped' AND
												`status` IN ('received')");
            if(!$exists) {
				update_transaction($user_uid, $address, $txid);
			}*/
        }
		update_received_by_address($address);
		update_user_balance($user_uid);
    }
}

// Generating new addresses
echo "Generating new addresses\n";
$addresses_array=db_query_to_array("SELECT `uid`,`user_uid` FROM `wallets` WHERE `address`='' OR `address` IS NULL");

foreach($addresses_array as $address_data) {
	$uid=$address_data['uid'];
	$address=coin_rpc_get_new_address();
	$uid_escaped=db_escape($uid);
	$address_escaped=db_escape($address);
	echo "New address $address\n";
	db_query("UPDATE `wallets` SET `address`='$address_escaped' WHERE `uid`='$uid_escaped' AND (`address`='' OR `address` IS NULL)");
}

// Send pending transactions
echo "Sending transactions\n";
$transactions_to_send=db_query_to_array("SELECT `uid`,`user_uid`,`amount`,`address` FROM `transactions` WHERE `status`='processing'");

if(count($transactions_to_send)!=0) {
	// Unlock wallet
	if(coin_rpc_unlock_wallet() == FALSE) {
		echo "Unlock wallet error\n";
		write_log("Unlock wallet error");
		die();
	}

	// Commit transactions
	foreach($transactions_to_send as $tx_data) {
		$uid=$tx_data['uid'];
		$user_uid=$tx_data['user_uid'];
		$amount=$tx_data['amount'];
		$address=$tx_data['address'];

		$uid_escaped=db_escape($uid);

		if(coin_rpc_validate_address($address)===TRUE) {
			coin_rpc_set_tx_fee($sending_fee);
			$tx_id=coin_rpc_send($address,$amount);

			if($tx_id==NULL || $tx_id==FALSE) {
				echo "Sending error to address $address amount $amount\n";
				//db_query("UPDATE `transactions` SET `tx_id`='',`status`='sending error' WHERE `uid`='$uid_escaped'");
			} else {
				echo "Sent to address $address amount $amount\n";
				db_query("UPDATE `transactions` SET `status`='sent',`tx_id`='$tx_id' WHERE `uid`='$uid_escaped'");
			}
		} else if(coin_rpc_validate_address($address)===FALSE) {
			echo "Address error to address $address amount $amount\n";
			db_query("UPDATE `transactions` SET `tx_id`='',`status`='address error' WHERE `uid`='$uid_escaped'");
		} else {
			echo "Address validation '$address' failed";
		}
		update_user_balance($user_uid);
	}

	// Lock wallet
	if(coin_rpc_lock_wallet() == FALSE) {
		echo "Lock wallet error\n";
		write_log("Lock wallet error");
		die();
	}
	echo "No unsent payouts\n";
}

// Update state variable
set_variable("client_last_update",date("U"));

// Update balance
$balance=coin_rpc_get_balance();
set_variable("wallet_balance",$balance);


echo "DB queries count $db_queries_count\n";
echo "Done\n";

?>
