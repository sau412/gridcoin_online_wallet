<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/corecoin.php");
require_once("../lib/email.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

$f=fopen($lockfile."send","w");
if($f) {
        echo "Checking locks\n";
        if(!flock($f,LOCK_EX|LOCK_NB)) {
		die("Lockfile locked\n");
	}
}

db_connect();

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
			if($sending_fee_method == "settxfee") {
				coin_rpc_set_tx_fee($sending_fee_core);
			}
			else {
				$smart_fee_info = coin_rpc_estimate_smart_fee($sending_fee_blocks);
				coin_rpc_set_tx_fee($smart_fee_info['feerate']);
			}
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
