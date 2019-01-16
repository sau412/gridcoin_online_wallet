<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");
require_once("gridcoin.php");
require_once("email.php");

db_connect();

// Get current block
$network_block=grc_rpc_get_block_count();
$wallet_block=get_variable("current_block");
if(isset($wallet_block) && $wallet_block!=0 && isset($network_block) && $network_block!=0) {
        if($network_block<$wallet_block) {
                set_variable("payouts_enabled",0);
                log_write("Wallet block '$wallet_block' greater than network block '$network_block', possible fork, sending disabled");
        }
}
if(isset($network_block) && $network_block!=0) {
        set_variable("current_block",$network_block);
        $current_block_hash=grc_rpc_get_block_hash($network_block);
        set_variable("current_block_hash",$current_block_hash);
}

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
        $confirmations_escaped=db_escape($confirmations);

        if($category=="receive") {
                if($confirmations>=$wallet_receive_confirmations) $status="received";
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
                $user_uid_escaped=db_escape($user_uid);
                $tx_uid=db_query_to_variable("SELECT `uid` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `tx_id`='$tx_id_escaped'");
                if($tx_uid) {
                        $tx_uid_escaped=db_escape($tx_uid);
                        $base_status=db_query_to_variable("SELECT `status` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `tx_id`='$tx_id_escaped'");
                        if($base_status=='pending' && $status=='received') {
                                write_log("Received $amount $currency_short TX ID $tx_id",$user_uid);
                                notify_user($user_uid,"Received $amount $currency_short","TX ID: $tx_id\n");
                        }
                        db_query("UPDATE `transactions` SET `status`='$status',`confirmations`='$confirmations_escaped' WHERE `uid`='$tx_uid_escaped'");
                } else {
                        db_query("INSERT INTO `transactions` (`user_uid`,`amount`,`address`,`status`,`tx_id`,`confirmations`) VALUES ('$user_uid','$amount_escaped','$address_escaped','$status','$tx_id_escaped','$confirmations_escaped')");
                }
                update_user_balance($user_uid);
        }
}

// Send pending transactions
echo "Sending transactions\n";
$transactions_to_send=db_query_to_array("SELECT `uid`,`user_uid`,`amount`,`address` FROM `transactions` WHERE `status`='processing'");

if(count($transactions_to_send)!=0) {
        // Unlock wallet
        if(grc_rpc_unlock_wallet() == FALSE) {
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

                if(grc_rpc_validate_address($address)===TRUE) {
                        $tx_id=grc_rpc_send($address,$amount);

                        if($tx_id==NULL || $tx_id==FALSE) {
                                echo "Sending error to address $address amount $amount\n";
                                //db_query("UPDATE `transactions` SET `tx_id`='',`status`='sending error' WHERE `uid`='$uid_escaped'");
                        } else {
                                echo "Sent to address $address amount $amount\n";
                                db_query("UPDATE `transactions` SET `status`='sent',`tx_id`='$tx_id' WHERE `uid`='$uid_escaped'");
                        }
                } else if(grc_rpc_validate_address($address)===FALSE) {
                        echo "Address error to address $address amount $amount\n";
                        db_query("UPDATE `transactions` SET `tx_id`='',`status`='address error' WHERE `uid`='$uid_escaped'");
                } else {
                        echo "Address validation '$address' failed";
                }
                update_user_balance($user_uid);
        }

        // Lock wallet
        if(grc_rpc_lock_wallet() == FALSE) {
                echo "Lock wallet error\n";
                write_log("Lock wallet error");
                die();
        }
        echo "No unsent payouts\n";
}

// Update state variable
set_variable("client_last_update",date("U"));

echo "Done\n";

?>
