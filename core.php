<?php
// Core functions

// Escape text to show in html page as text
function html_escape($data) {
        $data=htmlspecialchars($data);
        $data=str_replace("'","&apos;",$data);
        return $data;
}

// Add message to log
function write_log($message) {
        $message_escaped=db_escape($message);
        db_query("INSERT INTO `log` (`message`) VALUES ('$message_escaped')");
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
function user_register($session,$mail,$login,$password1,$password2) {
        global $global_salt;

        if($password1!=$password2) return FALSE;

        $session_escaped=db_escape($session);
        $salt=bin2hex(random_bytes(16));
        $salt_escaped=db_escape($salt);

        $password_hash=hash("sha256",$password1.strtolower($login).$salt.$global_salt);

        $message="";

        if(validate_ascii($login)) {
                $login_escaped=db_escape($login);
                $mail_escaped=db_escape($mail);
                $exists_hash=db_query_to_variable("SELECT `password_hash` FROM `users` WHERE `login`='$login_escaped'");
                if($exists_hash=="") {
                        write_log("New user '$login' mail '$mail'");
                        db_query("INSERT INTO `users` (`mail`,`login`,`password_hash`,`salt`,`register_time`,`login_time`) VALUES ('$mail_escaped','$login_escaped','$password_hash','$salt_escaped',NOW(),NOW())");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                        $user_uid_escaped=db_escape($user_uid);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid_escaped' WHERE `session`='$session_escaped'");
                        user_create_new_address($user_uid);
                        return TRUE;
                } else if($password_hash==$exists_hash) {
                        write_log("Logged in '$login'");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                        $user_uid_escaped=db_escape($user_uid);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid_escaped' WHERE `session`='$session_escaped'");
                        return TRUE;
                } else {
                        write_log("Invalid password for '$login'");
                        $message="Invalid password";
                        return TRUE;
                }
        } else {
                write_log("Invalid login for '$login'");
                $message="Invalid login";
        }
        return $message;
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
                $password_hash=hash("sha256",$password.strtolower($login).$salt.$global_salt);

                if($password_hash==$exists_hash) {
                        write_log("Logged in user '$login'");
                        $user_uid=db_query_to_variable("SELECT `uid` FROM `users` WHERE `login`='$login_escaped'");
                        $user_uid_escaped=db_escape($user_uid);
                        db_query("UPDATE `sessions` SET `user_uid`='$user_uid' WHERE `session`='$session_escaped'");
                        db_query("UPDATE `users` SET `login_time`=NOW() WHERE `uid`='$user_uid_escaped'");
                        return TRUE;
                } else {
                        write_log("Invalid password for '$login'");
                        $message="Invalid password";
                        return FALSE;
                }
        } else {
                write_log("Invalid login for '$login'");
                $message="Invalid login";
                return FALSE;
        }
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

        $session_escaped=db_escape($session);
        db_query("UPDATE `sessions` SET `user_uid`=NULL WHERE `session`='$session_escaped'");
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
        $balance=$amount_received-$amount_sent;
        db_query("UPDATE `users` SET `balance`='$balance' WHERE `uid`='$user_uid_escaped'");
}

// Send
function user_send($user_uid,$amount,$address) {
        // Validate data
        if(!validate_number($amount)) return FALSE;
        if(!validate_ascii($address)) return FALSE;
        if($amount<=0) return FALSE;
        if($address=="") return FALSE;
        // Check user balance
        $balance=get_user_balance($user_uid);
        if($balance<$amount) return FALSE;

        // Add transaction to schedule
        $user_uid_escaped=db_escape($user_uid);
        $amount_escaped=db_escape($amount);
        $address_escaped=db_escape($address);
        db_query("INSERT INTO `transactions` (`user_uid`,`amount`,`address`,`status`) VALUES ('$user_uid_escaped','$amount_escaped','$address_escaped','processing')");
        $transaction_uid=mysql_insert_id();

        // Adjust user balance
        update_user_balance($user_uid);
        return $transaction_uid;
}

// Create request for new address
// Returns requiest uid
function user_create_new_address($user_uid) {
        $user_uid_escaped=db_escape($user_uid);
        db_query("INSERT INTO `wallets` (`user_uid`) VALUES ('$user_uid_escaped')");
        $address_uid=mysql_insert_id();
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
                return TRUE;
        } else {
                db_query("INSERT INTO `aliases` (`user_uid`,`address`,`label`) VALUES ('$user_uid_escaped','$address_escaped','$label_escaped') ON DUPLICATE KEY UPDATE `label`=VALUES(`label`)");
                return TRUE;
        }
}

// For php 5 only variant for random_bytes is openssl_random_pseudo_bytes from openssl lib
if(!function_exists("random_bytes")) {
        function random_bytes($n) {
                return openssl_random_pseudo_bytes($n);
        }
}
?>
