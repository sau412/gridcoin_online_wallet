<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/email.php");

db_connect();
email_send_all();

?>
