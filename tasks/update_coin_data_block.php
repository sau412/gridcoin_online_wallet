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

function update_transaction($txid) {
    global $wallet_receive_confirmations;

	try {
    	$transaction = coin_rpc_get_single_transaction($txid);
	}
	catch(Exception $e) {
		echo "Transaction $txid is not belonging to wallet\n";
		return;
	}

	// Skip PoS transactions
	if(isset($transaction['generated']) && $transaction['generated'] == true) {
		return;
	}
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
    $total_amount = [];
	$nonstandard = false;
	$nonstandard_input = 0;

    foreach($vout_array as $vout_index => $vout) {
        $vout_value = $vout['value'];
		$vout_address = '';
		if(isset($vout["scriptPubKey"]["addresses"])) {
			$vout_address = array_pop($vout["scriptPubKey"]["addresses"]);
		}
		else if(isset($vout["scriptPubKey"]["address"])) {
			$vout_address = $vout["scriptPubKey"]["address"];
		}
		if($vout_address && !isset($total_amount[$vout_address])) {
			$total_amount[$vout_address] = 0;
		}

		if(isset($vout["scriptPubKey"]["type"]) && $vout["scriptPubKey"]["type"] == "nonstandard") {
			echo "Transaction $txid has nonstandard out\n";

			// Need to find previous transaction
			if(isset($transaction['vin'][0]['txid'])) {
				$prev_txid = $transaction['vin'][0]['txid'];
				echo "Previous transaction is $prev_txid\n";
				$prev_transaction_info = coin_rpc_get_single_transaction($prev_txid);

				// Find address and amount info in previous transaction out's
				if($prev_transaction_info['vout'][1]['value']) {
					$prev_transaction_amount = $prev_transaction_info['vout'][1]['value'];
					echo "Previous transaction amount is $prev_transaction_amount\n";

					// Add prev transaction out amount as negative to next transaction
					$nonstandard = true;
					$nonstandard_input = $prev_transaction_amount;
				}
			}
			continue;
		}

		if($nonstandard && $vout_index == 1) {
			$total_amount[$vout_address] += $vout_value - $nonstandard_input;
			$nonstandard = false;
			$nonstandard_input = 0;
		}
		else if($vout_address && $vout_value) {
			$total_amount[$vout_address] += $vout_value;
		}
    }

	foreach($total_amount as $address => $total_amount) {
		$address_escaped = db_escape($address);
		$txid_escaped = db_escape($txid);
		$total_amount_escaped = db_escape($total_amount);
		// Check if address belongs to wallet
		$user_uid = db_query_to_variable("SELECT `user_uid` FROM `wallets` WHERE `address` = '$address_escaped'");
		if(!$user_uid) {
			continue;
		}
		$user_uid_escaped = db_escape($user_uid);
		echo "Address $address belongs to wallet, received $total_amount\n";

		// Check if transaction exists
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

		update_received_by_address($address);
		update_user_balance($user_uid);
	}
}

db_connect();

// Get current block
$network_block = coin_rpc_get_block_count();

if(!isset($network_block) || $network_block == FALSE) {
	die("Client down\n");
}

$wallet_block = get_variable("current_block");

echo "Wallet block '$wallet_block' network block '$network_block'\n";

if(isset($wallet_block) && $wallet_block != 0 && isset($network_block) && $network_block != 0) {
	if($network_block < $wallet_block) {
		set_variable("payouts_enabled", 0);
		log_write("Wallet block '$wallet_block' greater than network block '$network_block', possible fork, sending disabled", 3);
	}
}

if($network_block > $wallet_block) {
	// Need to substract confirmations amount
	$wallet_block -= $wallet_receive_confirmations;

	echo "Start updating\n";
    while($wallet_block < $network_block) {
        $wallet_block ++;
	    $current_block_hash = coin_rpc_get_block_hash($wallet_block);
		if(!$current_block_hash) {
			echo "Block hash error for block $wallet_block\n";
		}
        $block_info = coin_rpc_get_block_info($current_block_hash);
        if($block_info['tx']) {
            foreach($block_info['tx'] as $txid) {
                echo "Block $wallet_block transaction $txid\n";
				update_transaction($txid);
            }
        }
		set_variable("current_block", $wallet_block);
        set_variable("current_block_hash", $current_block_hash);
	}
}
else {
	echo "Nothing to update\n";
}

// Generating new addresses
echo "Generating new addresses\n";
generate_wallet_addresses();

// Update state variable
set_variable("client_last_update", date("U"));

// Update balance
$balance = coin_rpc_get_balance();
set_variable("wallet_balance", $balance);


echo "DB queries count $db_queries_count\n";
echo "Done\n";
