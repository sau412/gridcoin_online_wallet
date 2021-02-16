<?php
// Core wallet client related functions

// Send query to corecoin client
function coin_rpc_send_query($query) {
	global $coin_rpc_host,$coin_rpc_port,$coin_rpc_login,$coin_rpc_password;
	$ch=curl_init("http://$coin_rpc_host:$coin_rpc_port");
//echo "http://$coin_rpc_host:$coin_rpc_port\n";
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
	curl_setopt($ch,CURLOPT_POST,TRUE);
	curl_setopt($ch,CURLOPT_USERPWD,"$coin_rpc_login:$coin_rpc_password");
//echo "$coin_rpc_login:$coin_rpc_password\n";
	curl_setopt($ch, CURLOPT_POSTFIELDS,$query);
	$result=curl_exec($ch);
	log_write([
		"function" => "coin_rpc_send_query",
		"query" => $query,
		"wallet_reply_raw" => $result,
	], 7);
//var_dump("curl error",curl_error($ch));
	curl_close($ch);
	if($result == '') {
		throw new Exception("WalletReplyEmpty");
	}
	$data = json_decode($result, true, 512, JSON_INVALID_UTF8_IGNORE);
	return $data;
}

// Get block count
function coin_rpc_get_block_count() {
	$query = json_encode([
		"id" => 1,
		"method" => "getblockcount",
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Get block hash
function coin_rpc_get_block_hash($number) {
	$query = json_encode([
		"id" => 1,
		"method" => "getblockhash",
		"params" => [(int)$number]
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Get block info
function coin_rpc_get_block_info($hash) {
	$query = json_encode([
		"id" => 1,
		"method" => "getblock",
		"params" => [$hash],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Get current superblock
function coin_rpc_get_current_superblock_number() {
	$query = json_encode([
		"id" => 1,
		"method" => "superblockage",
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']['Superblock Block Number']) {
		return $data['result']['Superblock Block Number'];
	}
	throw new Exception("WalletReplyError");
}

// Get transaction
function coin_rpc_get_single_transaction($txid) {
	$query=json_encode([
		"id" => 1,
		"method" => "gettransaction",
		"params" => [$txid, false, true]
	]);
	$data = coin_rpc_send_query($query);
	if($data['error']) {
		$query=json_encode([
			"id" => 1,
			"method" => "gettransaction",
			"params" => [$txid]
		]);
		$data = coin_rpc_send_query($query);
	}
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Get balance
function coin_rpc_get_balance() {
	$query = json_encode([
		"id" => 1,
		"method" => "getbalance",
	]);

	$data = coin_rpc_send_query($query);
	if($data['result'] || is_numeric($data['result'])) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Unlock wallet
function coin_rpc_unlock_wallet() {
	global $coin_rpc_wallet_passphrase;
	$query = json_encode([
		"id" => 1,
		"method" => "walletpassphrase",
		"params" => [$coin_rpc_wallet_passphrase, 60],
	]);
	$data=coin_rpc_send_query($query);

	//var_dump($data);
	if($data['error'] == NULL) return TRUE;
	// Wallet already unlocked
	else if($data['error']['message'] == 'Error: Wallet is already unlocked, use walletlock first if need to change unlock settings.') return TRUE;
	// Wallet without passphrase
	else if($data['error']['message'] == 'Error: running with an unencrypted wallet, but walletpassphrase was called.') return TRUE;
	else {
		throw new Exception("WalletUnlockError");
	}
}

// Lock wallet
function coin_rpc_lock_wallet() {
	$query = json_encode([
		"id" => 1,
		"method" => "walletlock",
	]);
	$data=coin_rpc_send_query($query);
	if($data['error'] == NULL) return TRUE;
	// Wallet without passphrase
	else if($data['error']['message'] == 'Error: running with an unencrypted wallet, but walletlock was called.') return TRUE;

	throw new Exception("WalletReplyError");
}

// Validate address
function coin_rpc_validate_address($address) {
	$query = json_encode([
		"id" => 1,
		"method" => "validateaddress",
		"params" => [$address],
	]);
	$data = coin_rpc_send_query($query);

	if($data['error'] == NULL) {
		if($data['result']['isvalid'] == TRUE) return TRUE;
		else return FALSE;
	}
	throw new Exception("WalletReplyError");
}

// Set TX fee
function coin_rpc_set_tx_fee($fee_amount) {
	$query = json_encode([
		"id" => 1,
		"method" => "settxfee",
		"params" => [$fee_amount],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Send coins
function coin_rpc_send($coin_address,$amount) {
	$amount = sprintf("%0.8F",$amount);
	$query = json_encode([
		"id" => 1,
		"method" => "sendtoaddress",
		"params" => [$coin_address, $amount],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Send coins to multiple addresses in one transaction
function coin_rpc_sendmany($sending_data) {
	$sending_data_checked = [];
	foreach($sending_data as $address => $amount) {
		$sending_data_checked[$address] = (double)sprintf("%0.8F",$amount);
	}
	$query = json_encode([
			"id" => 1,
			"method" => "sendmany",
			"params" => [
				"",
				$sending_data_checked,
			],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Get whitelisted project list
function coin_rpc_get_projects() {
	$query = json_encode([
		"id" => 1,
		"method" => "listprojects",
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Get magnitude unit
function coin_rpc_get_magnitude_unit() {
	$query = json_encode([
		"id" => 1,
		"method" => "magnitude",
		"params" => ["326bb50c0dd0ba9d46e15fae3484af35"], // Required any cpid
	]);
    $data = coin_rpc_send_query($query);
//var_dump($data->result[0][1]->{"Magnitude Unit (GRC payment per Magnitude per day)"});
//      foreach($data->result as $key => $val) if($key=="") echo "$key => $val\n";
    if($data['error'] == NULL) {
		return $data['result']["Current Magnitude Unit"];
	}
	throw new Exception("WalletReplyError");
}

// Get new address
function coin_rpc_get_new_address() {
	$query = json_encode([
		"id" => 1,
		"method" => "getnewaddress",
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Get received by address
function coin_rpc_get_received_by_address($address) {
	$query = json_encode([
		"id" => 1,
		"method" => "getreceivedbyaddress",
		"params" => [$address],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Get transactions
function coin_rpc_get_transactions($count=1000) {
	$query = json_encode([
		"id" => 1,
		"method" => "listtransactions",
		"params" => ["", $count],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}

	// Bitcoin wallet requires * instead of empty string
	$query = json_encode([
		"id" => 1,
		"method" => "listtransactions",
		"params" => ["*", $count],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// List listreceived by address
function coin_rpc_list_received_by_address() {
	global $wallet_receive_confirmations;
	$query = json_encode([
		"id" => 1,
		"method" => "listreceivedbyaddress",
		"params" => [$wallet_receive_confirmations],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result'] || is_array($data['result'])) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}

// Decode raw transaction
function coin_decode_raw_transaction($tx_data) {
	$sending_data_checked = [];
	foreach($sending_data as $address => $amount) {
		$sending_data_checked[$address] = (double)sprintf("%0.8F",$amount);
	}
	$query = json_encode([
			"id" => 1,
			"method" => "decoderawtransaction",
			"params" => [
				$tx_data,
			],
	]);
	$data = coin_rpc_send_query($query);
	if($data['result']) {
		return $data['result'];
	}
	throw new Exception("WalletReplyError");
}
