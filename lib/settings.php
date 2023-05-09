<?php
// Settings file

$wallet_name="Gridcoin wallet";
$wallet_domain="";
$wallet_receive_confirmations=1;
$currency_short="GRC";
$currency="Gridcoin";
$sending_fee_core = 0.001;
$sending_fee_user = 0.001;
$min_send_amount = 0.00000001;
$receiving_addresses_cache = 20;

$project_internal_name = "set_project_name_here";
// For counter
$project_counter_name = $project_internal_name;
// For logging
$project_log_name = $project_internal_name;
$logger_url = "set_ypur_logger_here";

// DB variables
$db_host=""; // Fozzy VPN
$db_login="";
$db_password="";
$db_base="";

// Salt for password
$salt="";

// Email service
$email_sender="";
$email_reply_to="";
$email_notify="";

// Gridcoin RPC variables
$coin_rpc_host="";
$coin_rpc_port="";
$coin_rpc_login="";
$coin_rpc_password="";
$coin_rpc_wallet_passphrase="";

// Site settings
$settings_api_enabled=TRUE;
$settings_payouts_enabled=TRUE;
$settings_login_enabled=TRUE;
$default_language="en";
$lockfile="/tmp/gridcoin_wallet_lockfile";

// URLs for links
$address_url="https://www.gridcoinstats.eu/address/";
$tx_url="https://www.gridcoinstats.eu/tx/";
$block_url="https://www.gridcoinstats.eu/block/";
