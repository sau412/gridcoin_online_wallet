<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");
require_once("gridcoin.php");

db_connect();

// Get addresses received
echo "Updating received amounts\n";
$addresses_array=db_query_to_array("SELECT `uid`,`address` FROM `wallets` WHERE `address`<>'' AND `address` IS NOT NULL");

foreach($addresses_array as $address_data) {
        $uid=$address_data['uid'];
        $address=$address_data['address'];
        $address_received=grc_rpc_get_received_by_address($address);
        $uid_escaped=db_escape($uid);
        $address_received_escaped=db_escape($address_received);
        echo "Address $address received $address_received\n";
        db_query("UPDATE `wallets` SET `received`='$address_received_escaped' WHERE `uid`='$uid_escaped' AND `received`<'$address_received_escaped'");
}

// Generating new addresses
echo "Generating new addresses\n";
$addresses_array=db_query_to_array("SELECT `uid`,`user_uid` FROM `wallets` WHERE `address`='' OR `address` IS NULL");

foreach($addresses_array as $address_data) {
        $uid=$address_data['uid'];
        $address=grc_rpc_get_new_address();
        $uid_escaped=db_escape($uid);
        $address_escaped=db_escape($address);
        echo "New address $address\n";
        db_query("UPDATE `wallets` SET `address`='$address_escaped' WHERE `uid`='$uid_escaped' AND (`address`='' OR `address` IS NULL)");
}

// Syncronizing transactions
echo "Synchronizing transactions\n";
$transactions_array=grc_rpc_get_transactions();

foreach($transactions_array as $transaction_data) {
        $amount=$transaction_data->amount;
        $address=$transaction_data->address;
        $category=$transaction_data->category;
        $tx_id=$transaction_data->txid;
        $confirmations=$transaction_data->confirmations;

        $amount_escaped=db_escape($amount);
        $address_escaped=db_escape($address);
        $tx_id_escaped=db_escape($tx_id);
        if($category=="receive") {
                if($confirmations>=10) $status="received";
                else $status="pending";
        } else {
                // Interest transactions
                //var_dump($transaction_data);
                continue;
        }

        $user_uid=db_query_to_variable("SELECT `user_uid` FROM `wallets` WHERE `address`='$address_escaped'");

        if($user_uid) {
                //$exists=db_query_to_variable("SELECT 1 FROM `transactions` WHERE `tx_id`='$tx_id_escaped'");
                echo "Transaction $tx_id address $address amount $amount status $status user_uid $user_uid\n";
                db_query("INSERT INTO `transactions` (`user_uid`,`amount`,`address`,`status`,`tx_id`) VALUES ('$user_uid','$amount_escaped','$address_escaped','$status','$tx_id_escaped')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`)");
                update_user_balance($user_uid);
        }
}

// Send pending transactions
echo "Sending transactions\n";
$transactions_to_send=db_query_to_array("SELECT `uid`,`user_uid`,`amount`,`address` FROM `transactions` WHERE `status`='processing'");

if(count($transactions_to_send)==0) {
        echo "No unsend payouts\n";
        die();
}

// Unlock wallet
if(grc_rpc_unlock_wallet() == FALSE) {
        echo "Unlock wallet error\n";
        write_log("Unlock wallet error");
        die();
}

foreach($transactions_to_send as $tx_data) {
        $uid=$tx_data['uid'];
        $user_uid=$tx_data['user_uid'];
        $amount=$tx_data['amount'];
        $address=$tx_data['address'];

        $uid_escaped=db_escape($uid);

        if(grc_rpc_validate_address($address)==TRUE) {
                $tx_id=grc_rpc_send($address,$amount);

                if($tx_id==NULL || $tx_id==FALSE) {
                        echo "Sending error to address $address amount $amount\n";
                        db_query("UPDATE `transactions` SET `tx_id`='',`status`='sending error' WHERE `uid`='$uid_escaped'");
                } else {
                        echo "Sent to address $address amount $amount\n";
                        db_query("UPDATE `transactions` SET `status`='sent',`tx_id`='$tx_id' WHERE `uid`='$uid_escaped'");
                }
        } else {
                echo "Address error to address $address amount $amount\n";
                db_query("UPDATE `transactions` SET `tx_id`='',`status`='address error' WHERE `uid`='$uid_escaped'");
        }
        update_user_balance($user_uid);
}

// Lock wallet
if(grc_rpc_lock_wallet() == FALSE) {
        echo "Lock wallet error\n";
        write_log("Lock wallet error");
        die();
}
?>