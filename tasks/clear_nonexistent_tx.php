<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/corecoin.php");
require_once("../lib/email.php");
require_once("../lib/logger.php");

db_connect();

$tx_data_array=db_query_to_array("SELECT * FROM `transactions`");

foreach($tx_data_array as $row) {
    $tx_uid = $row['uid'];
    $tx_id = $row['tx_id'];
    $tx_data = coin_rpc_get_single_transaction($tx_id);
    var_dump($tx_data);
}
