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

db_connect();

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
