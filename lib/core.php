<?php
// Core functions

// Escape text to show in html page as text
function html_escape($data) {
        $data=htmlspecialchars($data);
        $data=str_replace("'","&apos;",$data);
        return $data;
}

// Add message to log
function write_log($message,$user_uid='') {
        global $project_log_name;
        log_write($message);
}

// Checks is string contains only ASCII symbols
function validate_ascii($string) {
        if(strlen($string)>100) return FALSE;
        if(is_string($string)==FALSE) return FALSE;
        for($i=0;$i!=strlen($string);$i++) {
                if(ord($string[$i])<32 || ord($string[$i])>127) return FALSE;
        }
        return TRUE;
}

// Checks is string contains number
function validate_number($string) {
        if(strlen($string)>20) return FALSE;
        if(is_string($string)==FALSE) return FALSE;
        return is_numeric($string);
}

// Get variable
function get_variable($name) {
        $name_escaped=db_escape($name);
        return db_query_to_variable("SELECT `value` FROM `variables` WHERE `name`='$name_escaped'");
}

// Set variable
function set_variable($name,$value) {
        $name_escaped=db_escape($name);
        $value_escaped=db_escape($value);
        db_query("INSERT INTO `variables` (`name`,`value`) VALUES ('$name_escaped','$value_escaped') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
}

// Create or get session
function get_session() {
        if(isset($_COOKIE['session_id']) && validate_ascii($_COOKIE['session_id'])) {
                $session=$_COOKIE['session_id'];
                $session_escaped=db_escape($session);
                $session_exists=db_query_to_variable("SELECT 1 FROM `sessions` WHERE `session`='$session_escaped'");
                if(!$session_exists) {
                        unset($session);
                }
        }

        if(!isset($session)) {
                $session=bin2hex(random_bytes(32));
                $token=bin2hex(random_bytes(32));
                setcookie('session_id',$session,time()+86400*30);
                $session_escaped=db_escape($session);
                $token_escaped=db_escape($token);
                db_query("INSERT INTO `sessions` (`session`,`token`) VALUES ('$session_escaped','$token_escaped')");
        }
        return $session;
}

// Get user uid
function get_user_uid_by_session($session) {
        $session_escaped=db_escape($session);
        $user_uid=db_query_to_variable("SELECT `user_uid` FROM `sessions` WHERE `session`='$session_escaped'");
        return $user_uid;
}

// Get user token
function get_user_token_by_session($session) {
        $session_escaped=db_escape($session);
        $token=db_query_to_variable("SELECT `token` FROM `sessions` WHERE `session`='$session_escaped'");
        return $token;
}

// Create new user
function user_register($session,$mail,$login,$password1,$password2,$withdraw_address) {
        global $global_salt;

        if(get_variable("login_enabled")==0) return "register_failed_disabled";

        if($password1!=$password2) return "register_failed_password_mismatch";

        $session_escaped=db_escape($session);
        $salt=bin2hex(random_bytes(16));
        $salt_escaped=db_escape($salt);

        $password_hash=hash("sha256",$password1.strtolower($login).$salt.$global_salt);

        $message="";

        if(validate_ascii($login)) {
                $login_escaped=db_escape($login);
                $mail_escaped=db_escape($mail);
                $withdraw_address_escaped=db_escape($withdraw_address);
                $exists_hash=db_query_to_variable("SELECT `password_hash` FROM `users` WHERE `login`='$login_escaped'");
                $api_key=bin2hex(random_bytes(16));
                $api_key_escaped=db_escape($api_key);
                if($exists_hash=="") {
                        write_log("New user '$login' mail '$mail'");
                        db_query("INSERT INTO `users` (`mail`,`login`,`password_hash`,`salt`,`register_time`,`login_time`,`withdraw_address`,`api_key`)
VALUES ('$mail_escaped','$login_escaped','$password_hash','$salt_escaped',NOW(),NOW(),'$withdraw_address_escaped','$api_key_escaped')");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                        $user_uid_escaped=db_escape($user_uid);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid_escaped' WHERE `session`='$session_escaped'");
                        user_create_new_address($user_uid);
                        return "register_successfull";
                        return TRUE;
                } else if($password_hash==$exists_hash) {
                        write_log("Logged in '$login'");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                        $user_uid_escaped=db_escape($user_uid);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid_escaped' WHERE `session`='$session_escaped'");
                        return "login_successfull";
                } else {
                        write_log("Invalid password for '$login'");
                        return "register_failed_invalid_password";
                }
        } else {
                write_log("Invalid login for '$login'");
                return "register_failed_invalid_login";
        }
}

// Check user login and password
function user_login($session,$login,$password) {
        global $global_salt;

        $session_escaped=db_escape($session);

        $message="";

        if(validate_ascii($login)) {
                $login_escaped=db_escape($login);
                $exists_hash=db_query_to_variable("SELECT `password_hash` FROM `users` WHERE `login`='$login_escaped'");
                $salt=db_query_to_variable("SELECT `salt` FROM `users` WHERE `login`='$login_escaped'");
                $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                $user_uid_escaped=db_escape($user_uid);

                if(get_variable("login_enabled")==0 && !is_admin($user_uid)) return "login_failed_disabled";

                $password_hash=hash("sha256",$password.strtolower($login).$salt.$global_salt);

                if($password_hash==$exists_hash) {
                        write_log("Logged in user '$login'");
                        notify_user($user_uid,"Logged in $login","IP: ".$_SERVER['REMOTE_ADDR']);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid' WHERE `session`='$session_escaped'");
                        db_query("UPDATE `users` SET `login_time`=NOW() WHERE `uid`='$user_uid_escaped'");
                        return "login_successfull";
                } else {
                        write_log("Invalid password for '$login'");
                        notify_user($user_uid,"Log in failed","IP: ".$_SERVER['REMOTE_ADDR']);
                        return "login_failed_invalid_password";
                }
        } else {
                write_log("Invalid login for '$login'");
                return "login_failed_invalid_login";
        }
}

// Change settings
function user_change_settings($user_uid,$mail,$mail_notify_enabled,$api_enabled,$renew_api_key,$withdraw_address,$password,$new_password1,$new_password2) {
        global $global_salt;

        if($new_password1!=$new_password2) {
                notify_user($user_uid,"Change settings fail","Change settings failed, new password mismatch");
                return "user_change_settings_failed_new_password_mismatch";
        }

        $user_uid_escaped=db_escape($user_uid);
        $user_data_array=db_query_to_array("SELECT `mail`,`login`,`salt`,`password_hash`,`api_enabled`,`mail_notify_enabled`,`mail_2fa_enabled`,`withdraw_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $user_data=array_pop($user_data_array);
        $login=$user_data['login'];
        $salt=$user_data['salt'];
        $password_hash=$user_data['password_hash'];
        $entered_password_hash=hash("sha256",$password.strtolower($login).$salt.$global_salt);

        if($password_hash==$entered_password_hash) {
                if($mail!=$user_data['mail']) {
                        notify_user($user_uid,"Settings changed","E-mail changed to: $mail");
                        $mail_escaped=db_escape($mail);
                        db_query("UPDATE `users` SET `mail`='$mail_escaped' WHERE `uid`='$user_uid_escaped'");
                        $change_log="New e-mail: $mail\n";
                }
                $mail_notify_enabled_value=$mail_notify_enabled=="enabled"?1:0;
                if($mail_notify_enabled_value!=$user_data['mail_notify_enabled']) {
                        db_query("UPDATE `users` SET `mail_notify_enabled`=$mail_notify_enabled_value WHERE `uid`='$user_uid_escaped'");
                        $change_log="E-mail notify state: $mail_notify_enabled_value\n";
                }

                $api_enabled_value=$api_enabled=="enabled"?1:0;
                if($api_enabled_value!=$user_data['api_enabled']) {
                        db_query("UPDATE `users` SET `api_enabled`=$api_enabled_value WHERE `uid`='$user_uid_escaped'");
                        $change_log="API state: $api_enabled_value\n";
                }

                if($renew_api_key=='yes') {
                        $new_api_key=bin2hex(random_bytes(16));
                        $new_api_key_escaped=db_escape($new_api_key);
                        db_query("UPDATE `users` SET `api_key`='$new_api_key_escaped' WHERE `uid`='$user_uid_escaped'");
                        $change_log="API key updated\n";
                }

                if($new_password1!='') {
                        $new_password_hash=hash("sha256",$new_password1.strtolower($login).$salt.$global_salt);
                        $new_password_hash_escaped=db_escape($new_password_hash);
                        db_query("UPDATE `users` SET `password_hash`='$new_password_hash_escaped' WHERE `uid`='$user_uid_escaped'");
                        $change_log="New password applied\n";
                }

                if($withdraw_address!=$user_data['withdraw_address']) {
                        $withdraw_address_escaped=db_escape($withdraw_address);
                        db_query("UPDATE `users` SET `withdraw_address`='$withdraw_address_escaped' WHERE `uid`='$user_uid_escaped'");
                        $change_log="New withdraw address: $withdraw_address\n";
                }

                notify_user($user_uid,"Settings changed",$change_log);
                return "user_change_settings_successfull";
        } else {
                notify_user($user_uid,"Change settings fail","Change settings failed, password incorrect");
                return "user_change_settings_failed_password_incorrect";
        }
}

// Admin change settings
function admin_change_settings($login_enabled,$payouts_enabled,$api_enabled,$info,$global_message) {
        // Login enabled
        $login_enabled_value=$login_enabled=="enabled"?"1":"0";
        set_variable("login_enabled",$login_enabled_value);

        // Payouts enabled
        $payouts_enabled_value=$payouts_enabled=="enabled"?"1":"0";
        set_variable("payouts_enabled",$payouts_enabled_value);

        // API enabled
        $api_enabled_value=$api_enabled=="enabled"?"1":"0";
        set_variable("api_enabled",$api_enabled_value);

        // News
        set_variable("info",$info);

        // Global message
        set_variable("global_message",$global_message);

        return "admin_change_settings_successfull";
}

// Get username by uid
function get_username_by_uid($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $login=db_query_to_variable("SELECT `login` FROM `users` WHERE `uid`='$user_uid_escaped'");
        return $login;
}

// Logout user
function user_logout($session) {
        $user_uid=get_user_uid_by_session($session);
        $username=get_username_by_uid($user_uid);
        write_log("Logged out user '$username'");
        notify_user($user_uid,"Log out $username","IP: ".$_SERVER['REMOTE_ADDR']);

        $session_escaped=db_escape($session);
        db_query("UPDATE `sessions` SET `user_uid`=NULL WHERE `session`='$session_escaped'");
        return "logout_successfull";
}

// Get user balance
function get_user_balance($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $balance=db_query_to_variable("SELECT `balance` FROM `users` WHERE `uid`='$user_uid_escaped'");
        return $balance;
}

// Update balance
function update_user_balance($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $amount_received=db_query_to_variable("SELECT SUM(`amount`) FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `status` IN('received')");
        $amount_sent=db_query_to_variable("SELECT SUM(`amount`) FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `status` IN ('processing','sent')");
        $amount_fee=db_query_to_variable("SELECT SUM(`fee`) FROM `transactions` WHERE `user_uid`='$user_uid_escaped' AND `status` IN ('processing','sent')");
        $balance=$amount_received-$amount_sent-$amount_fee;
        db_query("UPDATE `users` SET `balance`='$balance' WHERE `uid`='$user_uid_escaped'");
}

// Notify user
function notify_user($user_uid,$subject,$body) {
        $user_uid_escaped=db_escape($user_uid);
        $mail=db_query_to_variable("SELECT `mail` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $mail_notify_enabled=db_query_to_variable("SELECT `mail_notify_enabled` FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($mail && $mail_notify_enabled) {
                email_add($user_uid,$mail,$subject,$body);
        }
}

// Send
function user_send($user_uid,$amount,$address) {
        global $currency_short;
	global $sending_fee_core;
	global $sending_fee_user;
        global $min_send_amount;

        // Check payouts enabled
        if(get_variable("payouts_enabled")==0) return FALSE;

        // Validate data
        if(!validate_number($amount)) return FALSE;
        if(!validate_ascii($address)) return FALSE;
        if($amount < $min_send_amount) return FALSE;
        if($address=="") return FALSE;

        // Check user balance
	db_query("LOCK TABLES `transactions` WRITE,`users` WRITE");
        $balance=get_user_balance($user_uid);
        if($balance < ($amount + $sending_fee_user)) return FALSE;

        // Add transaction to schedule
        $user_uid_escaped=db_escape($user_uid);
        $amount_escaped=db_escape($amount);
        $address_escaped=db_escape($address);
	$fee_escaped=db_escape($sending_fee_user);
        db_query("INSERT INTO `transactions` (`user_uid`,`amount`,`fee`,`address`,`status`)
                        VALUES ('$user_uid_escaped','$amount_escaped','$fee_escaped','$address_escaped','processing')");
        $transaction_uid=mysql_insert_id();

        // Adjust user balance
        update_user_balance($user_uid);
	db_query("UNLOCK TABLES");

        // Send notifications
        $username=get_username_by_uid($user_uid);
        write_log("'$username' sent '$amount' $currency_short to address '$address'",$user_uid);
        notify_user($user_uid,"$username sent $amount $currency_short",
                        "Amount: $amount $currency_short\nAddress: $address\nIP: ".$_SERVER['REMOTE_ADDR']);

        return $transaction_uid;
}

/**
 * Create new receiving address for user
 * 
 * @param $user_uid User uid who requests address
 */
function user_create_new_address($user_uid) {
        $user_uid_escaped = db_escape($user_uid);
        db_query("LOCK TABLES `wallets` WRITE");
        $address_uid = db_query_to_variable('SELECT `uid` FROM `wallets` WHERE `user_uid` IS NULL LIMIT 1');
        if($address_uid) {
                $address_uid_escaped = db_escape($address_uid);
                db_query("UPDATE `wallets` SET `user_uid` = '$user_uid_escaped'
                                WHERE `uid` = '$address_uid_escaped' AND `user_uid` IS NULL");
        }
        else {
                db_query("INSERT INTO `wallets` (`user_uid`) VALUES ('$user_uid_escaped')");
                $address_uid=mysql_insert_id();
        }
        db_query("UNLOCK TABLES");
        return $address_uid;
}

function recaptcha_check($response) {
        global $recaptcha_private_key;
        $recaptcha_url="https://www.google.com/recaptcha/api/siteverify";
        $query="secret=$recaptcha_private_key&response=$response&remoteip=".$_SERVER['REMOTE_ADDR'];
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
        curl_setopt($ch,CURLOPT_POST,TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$query);
        curl_setopt($ch,CURLOPT_URL,$recaptcha_url);
        $result = curl_exec ($ch);
        $data = json_decode($result);
        if($data->success) return TRUE;
        else return FALSE;
}

// Add/modify/del alias
function set_alias($user_uid,$label,$address) {
        $label_escaped=db_escape($label);
        $address_escaped=db_escape($address);
        $user_uid_escaped=db_escape($user_uid);
        if($address=='') return FALSE;
        if($label=='') {
                db_query("DELETE FROM `aliases` WHERE `address`='$address_escaped' AND `user_uid`='$user_uid_escaped'");
                return "alias_deleted";
        } else {
                db_query("INSERT INTO `aliases` (`user_uid`,`address`,`label`) VALUES ('$user_uid_escaped','$address_escaped','$label_escaped') ON DUPLICATE KEY UPDATE `label`=VALUES(`label`)");
                return "alias_changed";
        }
}

// Checks is user admin
function is_admin($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        $result=db_query_to_variable("SELECT `is_admin` FROM `users` WHERE `uid`='$user_uid_escaped'");
        if($result==1) return TRUE;
        else return FALSE;
}

/**
 * Generates new addresses for wallet
 * Called from tasks
 */
function generate_wallet_addresses() {
        global $receiving_addresses_cache;

        // Generate requested addresses
        $addresses_array = db_query_to_array("SELECT `uid`, `user_uid` FROM `wallets` WHERE `address` = '' OR `address` IS NULL");

        foreach($addresses_array as $address_data) {
                $uid = $address_data['uid'];
                $address = coin_rpc_get_new_address();
                $uid_escaped = db_escape($uid);
                $address_escaped = db_escape($address);
                echo "New address $address\n";
                db_query("UPDATE `wallets` SET `address` = '$address_escaped' WHERE `uid` = '$uid_escaped' AND (`address` = '' OR `address` IS NULL)");
        }
        
        // Generate cache addresses
        $cache_addresses_count = db_query_to_variable("SELECT count(*) FROM `wallets` WHERE `user_uid` IS NULL");
        
        for(; $cache_addresses_count < $receiving_addresses_cache; $cache_addresses_count ++) {
                $address = coin_rpc_get_new_address();
                $address_escaped = db_escape($address);
                echo "New cache address $address\n";
                db_query("INSERT INTO `wallets` (`user_uid`, `address`) VALUES (NULL, '$address_escaped')");
        }
}

// For php 5 only variant for random_bytes is openssl_random_pseudo_bytes from openssl lib
if(!function_exists("random_bytes")) {
        function random_bytes($n) {
                return openssl_random_pseudo_bytes($n);
        }
}
