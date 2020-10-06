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
				WHERE `address`='$address_escaped'");
}

function update_transaction($user_uid, $address, $txid) {
    global $wallet_receive_confirmations;

    $transaction = coin_rpc_get_single_transaction($txid);
	$vout_array = $transaction['vout'];
	if(!$vout_array) {
		$vout_array = $transaction['decoded']['vout'];
		if(!$vout_array) {
			$raw_transaction = $transaction['hex'];
			$tx_decoded = coin_decode_raw_transaction($raw_transaction);
			$vout_array = $tx_decoded['vout'];
		}
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
		$amount_exists = db_query_to_variable("SELECT `amount` FROM `transactions` WHERE `uid` = '$exists_txid_uid_escaped'");
		if($total_amount_escaped != $amount_exists) {
			echo "Amount in DB $amount_exists, amount in tx $total_amount_escaped\n";
		}
        db_query("UPDATE `transactions` SET `status` = '$status_escaped',
						`confirmations` = '$confirmations_escaped',
						`amount` = '$total_amount_escaped'
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
$received_transactions_array = db_query_to_array("SELECT DISTINCT `tx_id`, `address` FROM `transactions` WHERE `status` IN ('received')");
foreach($received_transactions_array as $received_transaction_data) {
	$tx_id = $received_transaction_data['tx_id'];
	$address = $received_transaction_data['address'];

	$hash = hash("sha256", $tx_id.$address);
	if(!in_array($hash, $received_hash)) {
		$received_hash[] = $hash;
	}
}

$received_in_db = [];
$received_in_db_array = db_query_to_array("SELECT `address`, `received` FROM `wallets`");
foreach($received_in_db_array as $received_row) {
	$address = $received_row['address'];
	$received = $received_row['received'];
	$received_in_db[$address] = $received;
}

$received_by_address_array = coin_rpc_list_received_by_address();

foreach($received_by_address_array as $received_by_address) {
    $address = $received_by_address['address'];
    $amount = $received_by_address['amount'];
    $txids_array = $received_by_address['txids'];

    if(!$address) continue;
    if(!$amount) continue;

	$address_escaped = db_escape($address);
	$received = $received_in_db[$address];
    //$received = db_query_to_variable("SELECT `received` FROM `wallets` WHERE `address` = '$address_escaped'");
	$received_in_transactions = $received;
	// Required only for thorough checking
	/*$received_in_transactions = db_query_to_variable("SELECT SUM(`amount`) FROM `transactions`
													WHERE `status` IN ('received') AND
															`address` = '$address_escaped'");
	*/
	$update_all = false;

	if($amount > $received || $received != $received_in_transactions || $update_all) {
        $user_uid=db_query_to_variable("SELECT `user_uid` FROM `wallets` WHERE `address`='$address_escaped'");

        if(!$user_uid) continue;

		echo "Something received user $user_uid for $address\n";
		echo "Received by wallet $received, received by transactions $received_in_transactions, syncing transactions\n";

        foreach($txids_array as $txid) {
			$hash = hash("sha256", $txid.$address);
			if(!in_array($hash, $received_hash) || $received != $received_in_transactions) {
				echo "Syncing transaction $txid\n";
				update_transaction($user_uid, $address, $txid);
			} else {
				echo "Transaction $txid already exists\n";
			}
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

// Update state variable
set_variable("client_last_update",date("U"));

// Update balance
$balance=coin_rpc_get_balance();
set_variable("wallet_balance",$balance);


echo "DB queries count $db_queries_count\n";
echo "Done\n";

?>
