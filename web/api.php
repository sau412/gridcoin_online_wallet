<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/email.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

//$_POST=$_GET;

//vaR_dump($_POST);
db_connect();

// Check if API disabled globally
if($settings_api_enabled==FALSE) die("API disabled");
if(get_variable("api_enabled")==0) die("API disabled");

// Check is API key present in query
if(!isset($_POST['api_key'])) die("API key is not set");

// Check is API enabled for that user, get that user uid
$api_key=$_POST['api_key'];
$api_key_escaped=db_escape($api_key);
$user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `api_key`='$api_key_escaped' AND `api_enabled`=1");
if(!$user_uid) die("Unknown API key");

// Check method
if(!isset($_POST['method'])) die("Method is not set");
$method=$_POST['method'];

// Do action
switch($method) {
	// Ping
	case 'ping':
		echo "Pong";
		break;

        // Get balance
        case 'get_balance':
                $balance=get_user_balance($user_uid);
                //write_log("API: balance, result: '$balance'",$user_uid);
                echo json_encode(array("balance"=>$balance));
                break;

        // == Receiving addresses methods ==
        // Get all receiving addresses
        case 'get_all_receiving_addresses':
                $user_uid_escaped=db_escape($user_uid);
                $payout_addresses=db_query_to_array("SELECT `uid`,`address`,`received` FROM `wallets` WHERE `user_uid`='$user_uid_escaped'");
                //write_log("API: get_all_receiving_addresses",$user_uid);
                echo json_encode($payout_addresses);
                break;
        // Get specific receiving address
        case 'get_receiving_address_by_uid':
                $address_uid=stripslashes($_POST['address_uid']);
                if(!validate_number($address_uid)) die("Wrong address uid");

                $address_uid_escaped=db_escape($address_uid);
                $user_uid_escaped=db_escape($user_uid);
                $payout_addresses=db_query_to_array("SELECT `uid`,`address`,`received` FROM `wallets` WHERE `user_uid`='$user_uid_escaped' AND `uid`='$address_uid_escaped'");
                $payout_address_single=array_pop($payout_addresses);
                //write_log("API: get_receiving_address_by_uid '$address_uid'",$user_uid);
                echo json_encode($payout_address_single);
                break;
        // Query new receiving address
        case 'new_receiving_address':
                $address_uid = user_create_new_address($user_uid);
                $address_uid_escaped=db_escape($address_uid);
                $user_uid_escaped=db_escape($user_uid);
                write_log("API: new_receiving_address",$user_uid);
                $payout_addresses = db_query_to_array("SELECT `uid`,`address`,`received`
                                                        FROM `wallets`
                                                        WHERE `user_uid`='$user_uid_escaped' AND `uid`='$address_uid_escaped'");
                $payout_address_single = array_pop($payout_addresses);
                
                echo json_encode($payout_address_single);
                break;

        // == Transaction methods ==
        // Get all transactions
        case 'get_all_transactions':
                $user_uid_escaped=db_escape($user_uid);
                $transactions_array=db_query_to_array("SELECT `uid`,`amount`,`address`,`status`,`tx_id`,`confirmations`,`timestamp`
                										FROM `transactions`
                										WHERE `user_uid`='$user_uid_escaped'");
                //write_log("API: get_all_transactions",$user_uid);
                echo json_encode($transactions_array);
                break;
        // Get all transactions by address
        case 'get_all_transactions_by_address':
                $user_uid_escaped=db_escape($user_uid);
                $address_escaped=db_escape($_POST['address']);
                $transactions_array=db_query_to_array("SELECT `uid`,`amount`,`address`,`status`,`tx_id`,`confirmations`,`timestamp`
                										FROM `transactions`
                										WHERE `user_uid`='$user_uid_escaped' AND `address`='$address_escaped'");
                //write_log("API: get_all_transactions",$user_uid);
                echo json_encode($transactions_array);
                break;
        // Get specific transaction
        case 'get_transaction_by_uid':
                $transaction_uid=stripslashes($_POST['transaction_uid']);
                if(!validate_number($transaction_uid)) die("Wrong transaction uid");
                $user_uid_escaped=db_escape($user_uid);
                $transaction_uid_escaped=db_escape($transaction_uid);
                $transactions_array=db_query_to_array("SELECT `uid`,`amount`,`address`,`status`,`tx_id`,`confirmations`,`timestamp`
                										FROM `transactions`
                										WHERE `user_uid`='$user_uid_escaped' AND `uid`='$transaction_uid_escaped'");
                $transaction_single=array_pop($transactions_array);
                //write_log("API: get_transaction_by_uid '$transaction_uid'",$user_uid);
                echo json_encode($transaction_single);
                break;
        // Send specific amount to address
        case 'send':
                if(get_variable("payouts_enabled")==0) die("Sending disabled");

                $amount=stripslashes($_POST['amount']);
                $address=stripslashes($_POST['address']);
                if(!validate_number($amount) || $amount < $min_send_amount) die("Wrong amount");
                if(!validate_ascii($address)) die("Wrong address");

                $transaction_uid=user_send($user_uid,$amount,$address);
                if($transaction_uid==FALSE) die("Sending error");
                write_log("API: send '$amount' to '$address'",$user_uid);
                echo json_encode(array("uid"=>$transaction_uid));
                break;

        // Unknown method
        default:
                write_log("API: Unknown method",$user_uid);
                echo json_encode(array("error"=>"Unknown method"));
                break;
}
