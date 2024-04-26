<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/corecoin.php");
require_once("../lib/email.php");
require_once("../lib/logger.php");

db_connect();

echo "Getting transactions...\n";
$tx_data_array=db_query_to_array("SELECT * FROM `transactions` ORDER BY `uid` DESC LIMIT 10000");

foreach($tx_data_array as $row) {
    $tx_uid = $row['uid'];
    $tx_id = $row['tx_id'];
    $status = $row['status'];
    if($tx_id == '') continue;

    echo "Checking transaction $tx_id...\n";
    $tx_data = coin_rpc_get_single_transaction($tx_id);

    if(isset($tx_data['generated']) && $tx_data['generated']) {
        echo "Transaction $tx_id is generated transaction\n";
    }

    $confirmations = $tx_data['confirmations'];
    if($confirmations >= 0) continue;
    
    echo "Unconfirmed TX uid $tx_uid txid $tx_id confirmations $confirmations\n";
    $tx_uid_escaped = db_escape($tx_uid);
    if($status == 'sent') {
        db_query("UPDATE `transactions` SET `status` = 'error' WHERE `uid` = '$tx_uid_escaped'");
    }
    else if($status == 'received' || $status == 'pending') {
        db_query("UPDATE `transactions` SET `status` = 'error' WHERE `uid` = '$tx_uid_escaped'");
    }
        //    var_dump($tx_data);
}
