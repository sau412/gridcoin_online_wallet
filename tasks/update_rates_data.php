<?php
require_once("../lib/settings.php");
require_once("../lib/db.php");
require_once("../lib/core.php");

db_connect();

// Setup cURL
$ch=curl_init();
curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
curl_setopt($ch,CURLOPT_POST,FALSE);

// Get XMR price
curl_setopt($ch,CURLOPT_URL,"https://api.coingecko.com/api/v3/coins/gridcoin-research");
$result=curl_exec($ch);
if($result=="") {
	echo "No GRC price data\n";
	log_write("No GRC price data");
	die();
}
$parsed_data=json_decode($result);
$btc_per_grc_price=(string)$parsed_data->market_data->current_price->btc;
$usd_per_grc_price=(string)$parsed_data->market_data->current_price->usd;
$rub_per_grc_price=(string)$parsed_data->market_data->current_price->rub;
$ltc_per_grc_price=(string)$parsed_data->market_data->current_price->ltc;
$xrp_per_grc_price=(string)$parsed_data->market_data->current_price->xrp;
$eth_per_grc_price=(string)$parsed_data->market_data->current_price->eth;
$xlm_per_grc_price=(string)$parsed_data->market_data->current_price->xlm;

set_variable("btc_per_grc",$btc_per_grc_price);
set_variable("usd_per_grc",$usd_per_grc_price);
set_variable("rub_per_grc",$rub_per_grc_price);
set_variable("ltc_per_grc",$ltc_per_grc_price);
set_variable("xrp_per_grc",$xrp_per_grc_price);
set_variable("eth_per_grc",$eth_per_grc_price);
set_variable("xlm_per_grc",$xlm_per_grc_price);

?>
