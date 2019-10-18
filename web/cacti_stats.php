<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");

db_connect();

$total_users=db_query_to_variable("SELECT count(*) FROM `users`");
$active_users=db_query_to_variable("SELECT count(DISTINCT `user_uid`) FROM `transactions` WHERE DATE_SUB(NOW(),INTERVAL 1 DAY)<`timestamp`");
$users_balance=db_query_to_variable("SELECT SUM(`balance`) FROM `users`");
$wallet_balance=db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='wallet_balance'");
$total_addr=db_query_to_variable("SELECT count(*) FROM `wallets`");
$total_tx=db_query_to_variable("SELECT count(*) FROM `transactions`");

echo "total_users:$total_users";
echo " active_users:$active_users";
echo " users_balance:$users_balance";
echo " wallet_balance:$wallet_balance";
echo " total_addr:$total_addr";
echo " total_tx:$total_tx";
?>
