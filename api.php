<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");

//$_POST=$_GET;

if(!isset($_POST['api_key'])) die("API key is not set");

db_connect();
$api_key=$_POST['api_key'];
$api_key_escaped=db_escape($api_key);
$user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `api_key`='$api_key_escaped'");

if(!$user_uid) die("Unknown API key");

if(!isset($_POST['method'])) die("Method is not set");
$method=$_POST['method'];

switch($method) {
        case 'balance':
                $balance=get_user_balance($user_uid);
                echo $balance;
                break;

        default:
                echo "Method unknown";
                break;
}
?>
