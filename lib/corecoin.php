<?php
// Core wallet client related functions

// Send query to core wallet
function coin_rpc_send_query($query) {
	global $coin_rpc_host,$coin_rpc_port,$coin_rpc_login,$coin_rpc_password;
	$ch=curl_init("http://$coin_rpc_host:$coin_rpc_port");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
	curl_setopt($ch,CURLOPT_POST,TRUE);
	curl_setopt($ch,CURLOPT_USERPWD,"$coin_rpc_login:$coin_rpc_password");
	curl_setopt($ch, CURLOPT_POSTFIELDS,$query);
	$result=curl_exec($ch);
	curl_close($ch);

	return $result;
}

// Get block count
function coin_rpc_get_block_count() {
	$query='{"id":1,"method":"getblockcount","params":[]}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	return $data->result;
}

// Get block hash
function coin_rpc_get_block_hash($number) {
	$query='{"id":1,"method":"getblockhash","params":['.$number.']}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	return $data->result;
}

// Get block info
function coin_rpc_get_block_info($hash) {
	$query='{"id":1,"method":"getblock","params":["'.$hash.'"]}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	return $data->result;
}

// Get current superblock
function coin_rpc_get_current_superblock_number() {
	$query='{"id":1,"method":"superblockage"}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	return $data->result->{"Superblock Block Number"};
}

// Get transaction
function coin_rpc_get_single_transaction($txid) {
	$query='{"id":1,"method":"gettransaction","params":["'.$txid.'"]}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result, true);
	return $data['result'];
}

// Get balance
function coin_rpc_get_balance() {
	$query='{"id":1,"method":"getbalance","params":[]}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	return $data->result;
}

// Unlock wallet
function coin_rpc_unlock_wallet() {
	global $coin_rpc_wallet_passphrase;
	$query='{"id":1,"method":"walletpassphrase","params":["'.$coin_rpc_wallet_passphrase.'",60]}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
//var_dump($data);
	if($data->error == NULL) return TRUE;
	// Wallet already unlocked
	else if($data->error->message=='Error: Wallet is already unlocked, use walletlock first if need to change unlock settings.') return TRUE;
	// Wallet without passphrase
	else if($data->error->message=='Error: running with an unencrypted wallet, but walletpassphrase was called.') return TRUE;
	else return FALSE;
}

// Lock wallet
function coin_rpc_lock_wallet() {
	$query='{"id":1,"method":"walletlock","params":[]}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	if($data->error == NULL) return TRUE;
	// Wallet without passphrase
	else if($data->error->message=='Error: running with an unencrypted wallet, but walletlock was called.') return TRUE;
	else return FALSE;
}

// Validate address
function coin_rpc_validate_address($coin_address) {
//	if(auth_validate_payout_address($coin_address) == FALSE) return FALSE;
	$query='{"id":1,"method":"validateaddress","params":["'.$coin_address.'"]}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);

	if($data->error == NULL) {
		if($data->result->isvalid == TRUE) return TRUE;
		else if($data->result->isvalid == FALSE) return FALSE;
		else return NULL;
	} else return NULL;
}

// Set TX fee
function coin_rpc_set_tx_fee($fee_amount) {
	$query='{"id":1,"method":"settxfee","params":['.$fee_amount.']}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	if($data->error == NULL) return $data->result;
	else return FALSE;
}

// Send coins
function coin_rpc_send($coin_address,$amount) {
	$amount = sprintf("%0.8F",$amount);
	$query='{"id":1,"method":"sendtoaddress","params":["'.$coin_address.'",'.$amount.']}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	if($data->error == NULL) return $data->result;
	else return FALSE;
}

// Get whitelisted project list
function coin_rpc_get_projects() {
	$query='{"id":1,"method":"projects","params":[]}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result);
	if($data->error == NULL) return $data->result;
	else return FALSE;
}

// Get magnitude unit
function coin_rpc_get_magnitude_unit() {
        $query='{"id":1,"method":"magnitude","params":[]}';
        $result=coin_rpc_send_query($query);
        $data=json_decode($result);
//var_dump($data->result[0][1]->{"Magnitude Unit (coin payment per Magnitude per day)"});
//      foreach($data->result as $key => $val) if($key=="") echo "$key => $val\n";
        if($data->error == NULL) return $data->result[0][1]->{"Magnitude Unit (coin payment per Magnitude per day)"};
        else return FALSE;
}

// Get new address
function coin_rpc_get_new_address() {
        $query='{"id":1,"method":"getnewaddress","params":[]}';
        $result=coin_rpc_send_query($query);
        $data=json_decode($result);
//      foreach($data->result as $key => $val) if($key=="") echo "$key => $val\n";
        if($data->error == NULL) return $data->result;
        else return FALSE;
}

// Get received by address
function coin_rpc_get_received_by_address($address) {
	$address_escaped=urlencode($address);
        $query='{"id":1,"method":"getreceivedbyaddress","params":["'.$address_escaped.'"]}';
        $result=coin_rpc_send_query($query);
        $data=json_decode($result);
//var_dump($data);
//      foreach($data->result as $key => $val) if($key=="") echo "$key => $val\n";
        if($data->error == NULL) return $data->result;
        else return FALSE;
}

// Get transactions
function coin_rpc_get_transactions($count=1000) {
        $query='{"id":1,"method":"listtransactions","params":["",'.$count.']}';
        $result=coin_rpc_send_query($query);
        $data=json_decode($result);
        if($data->error == NULL) return $data->result;
        else {
		// Bitcoin wallet requires * instead of empty string
	        $query='{"id":1,"method":"listtransactions","params":["*",'.$count.']}';
	        $result=coin_rpc_send_query($query);
	        $data=json_decode($result);
	        if($data->error == NULL) return $data->result;
	        else return FALSE;
	}
}

// List listreceived by address
function coin_rpc_list_received_by_address() {
	global $wallet_receive_confirmations;
	$query='{"id":1,"method":"listreceivedbyaddress","params":['.$wallet_receive_confirmations.']}';
	$result=coin_rpc_send_query($query);
	$data=json_decode($result, true);
	if($data['error'] == NULL) return $data['result'];
	else return FALSE;
}

?>
