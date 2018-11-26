<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");

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
        // Get balance
        case 'get_balance':
                $balance=get_user_balance($user_uid);
                echo $balance;
                break;
        // Get current_price in BTC
        case 'get_price_in_btc':
                $price=get_variable("btc_per_grc");
                echo "$price";
                break;
        // Get current_price in USD
        case 'get_price_in_usd':
                $price=get_variable("btc_per_usd");
                echo "$price";
                break;
        // Get current_price in RUB
        case 'get_price_in_rub':
                $price=get_variable("rub_per_grc");
                echo "$price";
                break;
        // Get current_price in LTC
        case 'get_price_in_ltc':
                $price=get_variable("ltc_per_grc");
                echo "$price";
                break;
        // Get current_price in XRP
        case 'get_price_in_XRP':
                $price=get_variable("xrp_per_grc");
                echo "$price";
                break;
        // Get current_price in ETH
        case 'get_price_in_eth':
                $price=get_variable("eth_per_grc");
                echo "$price";
                break;
        // Get current_price in XLM
        case 'get_price_in_xlm':
                $price=get_variable("xlm_per_grc");
                echo "$price";
                break;
        // Get all prices
        case 'get_prices_all':
                $price=get_variable("btc_per_grc");
                echo "btc_per_grc;$price;\n";
                $price=get_variable("usd_per_grc");
                echo "usd_per_grc;$price;\n";
                $price=get_variable("rub_per_grc");
                echo "rub_per_grc;$price;\n";
                $price=get_variable("ltc_per_grc");
                echo "ltc_per_grc;$price;\n";
                $price=get_variable("xrp_per_grc");
                echo "xrp_per_grc;$price;\n";
                $price=get_variable("eth_per_grc");
                echo "eth_per_grc;$price;\n";
                $price=get_variable("xlm_per_grc");
                echo "xlm_per_grc;$price;\n";
                break;

        // == Receiving addresses methods ==
        // Get all receiving addresses
        case 'get_all_receiving_addresses':
                $user_uid_escaped=db_escape($user_uid);
                $payout_addresses=db_query_to_array("SELECT `uid`,`address`,`received` FROM `wallets` WHERE `user_uid`='$user_uid_escaped'");
                foreach($payout_addresses as $address_data) {
                        $uid=$address_data['uid'];
                        $address=$address_data['address'];
                        $received=$address_data['received'];
                        echo "$uid;$address;$received;\n";
                }
                break;
        // Get specific receiving address
        case 'get_receiving_address_by_uid':
                $address_uid=stripslashes($_POST['address_uid']);
                if(!validate_number($address_uid)) die("Wrong address uid");

                $address_uid_escaped=db_escape($address_uid);
                $user_uid_escaped=db_escape($user_uid);
                $payout_addresses=db_query_to_array("SELECT `address`,`received` FROM `wallets` WHERE `user_uid`='$user_uid_escaped' AND `uid`='$address_uid'");

                foreach($payout_addresses as $address_data) {
                        $address=$address_data['address'];
                        $received=$address_data['received'];
                        echo "$address;$received;\n";
                }
                break;
        // Query new receiving address
        case 'new_receiving_address':
                $requiest_uid=user_create_new_address($user_uid);
                echo $requiest_uid;
                break;

        // == Transaction methods ==
        // Get all transactions
        case 'get_all_transactions':
                $user_uid_escaped=db_escape($user_uid);
                $transactions_array=db_query_to_array("SELECT `uid`,`amount`,`address`,`status`,`tx_id`,`timestamp` FROM `transactions` WHERE `user_uid`='$user_uid_escaped'");

                foreach($transactions_array as $transaction_data) {
                        $uid=$transaction_data['uid'];
                        $amount=$transaction_data['amount'];
                        $address=$transaction_data['address'];
                        $status=$transaction_data['status'];
                        $tx_id=$transaction_data['tx_id'];
                        $timestamp=$transaction_data['timestamp'];
                        echo "$uid;$amount;$address;$status;$tx_id;$timestamp;\n";
                }
                break;
        // Get specific transaction
        case 'get_transaction_by_uid':
                $transaction_uid=stripslashes($_POST['transaction_uid']);
                if(!validate_number($transaction_uid)) die("Wrong transaction uid");
                $user_uid_escaped=db_escape($user_uid);
                $transaction_uid_escaped=db_escape($transaction_uid);
                $transactions_array=db_query_to_array("SELECT `uid`,`amount`,`address`,`status`,`tx_id`,`timestamp` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `uid`='$transaction_uid_escaped'");
                foreach($transactions_array as $transaction_data) {
                        $uid=$transaction_data['uid'];
                        $amount=$transaction_data['amount'];
                        $address=$transaction_data['address'];
                        $status=$transaction_data['status'];
                        $tx_id=$transaction_data['tx_id'];
                        $timestamp=$transaction_data['timestamp'];
                        echo "$uid;$amount;$address;$status;$tx_id;$timestamp;\n";
                }
                break;
        // Send specific amount to address
        case 'send':
                $amount=stripslashes($_POST['amount']);
                $address=stripslashes($_POST['address']);
                if(!validate_number($amount)) die("Wrong amount");
                if(!validate_ascii($address)) die("Wrong address");

                $transaction_uid=user_send($user_uid,$amount,$address);
                if($transaction_uid==FALSE) die("Sending error");
                echo $transaction_uid;
                break;

        // Unknown method
        default:
                echo "Unknown method";
                break;
}
?>
