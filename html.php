<?php

// Standard page begin
function html_page_begin($title) {
        global $wallet_name;

        return <<<_END
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="icon" href="favicon.png" type="image/png">
<script src='jquery-3.3.1.min.js'></script>
<link rel="stylesheet" type="text/css" href="style.css">
<script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
<center>
<h1>$wallet_name</h1>

_END;
}

// Page end, scripts and footer
function html_page_end() {
        return <<<_END
<hr width=10%>
<p>Opensource gridcoin online wallet (<a href='https://github.com/sau412/gridcoin_online_wallet'>github link</a>) by Vladimir Tsarev, my nickname is sau412 on telegram, twitter, facebook, gmail, github, vk.</p>
</center>
</body>
</html>

_END;
}

function html_login_form($token) {
        global $recaptcha_public_key;
        $result=<<<_END
<h2>Login</h2>
<form name=login method=post>
<input type=hidden name=action value='login'>
<input type=hidden name=token value='$token'>
<p>Login: <input type=text name=login></p>
<p>Password: <input type=password name=password></p>
<div class="g-recaptcha" data-sitekey="$recaptcha_public_key"></div>
<p><input type=submit value='Login'></p>
</form>

_END;
        return $result;
}

function html_logout_form($token) {
        $result=<<<_END
<form name=login method=post>
<input type=hidden name=action value='logout'>
<input type=hidden name=token value='$token'>
<p><input type=submit value='Logout'></p>
</form>

_END;
        return $result;
}

function html_new_address_form($token) {
        $result=<<<_END
<form name=login method=post>
<input type=hidden name=action value='new_address'>
<input type=hidden name=token value='$token'>
<p><input type=submit value='Create'></p>
</form>

_END;
        return $result;
}

function html_register_form($token) {
        global $recaptcha_public_key;
        $result=<<<_END
<h2>Register</h2>
<form name=register method=post>
<input type=hidden name=action value='register'>
<input type=hidden name=token value='$token'>
<p>Login: <input type=text name=login></p>
<p>E-mail: <input type=text name=mail></p>
<p>Password 1: <input type=password name=password1></p>
<p>Password 2: <input type=password name=password2></p>
<div class="g-recaptcha" data-sitekey="$recaptcha_public_key"></div>
<p><input type=submit value='Register'></p>
</form>

_END;
        return $result;
}

function html_wallet_form($user_uid,$token) {
        global $currency_short;

        $result="";

        // Balance
        $balance=get_user_balance($user_uid);
        $result.="<h2>Balance: $balance $currency_short</h2>";

        // Send form
        $result.=<<<_END
<form name=send method=post>
<input type=hidden name=action value='send'>
<input type=hidden name=token value='$token'>
<p>Address: <input type=text size=40 name=address></p>
<p>Amount: <input type=text name=amount></p>
<p><input type=submit value='Send'></p>
</form>

_END;
        // Receiving addresses
        $result.="<h2>Receiving addresses</h2>\n";
        $user_uid_escaped=db_escape($user_uid);
        $receiving_addresses_data_array=db_query_to_array("SELECT `address` FROM `wallets` WHERE `user_uid`='$user_uid_escaped'");
        $result.="<table>\n";
        $result.="<tr><th>Address</th></tr>";
        foreach($receiving_addresses_data_array as $receiving_addresses_data) {
                $address=$receiving_addresses_data['address'];
                if($address=='') $address="<i>generating...</i>";
                $result.="<tr><td>$address</td></tr>\n";
        }
        $result.="</table>\n";
        $result.=html_new_address_form($token);

        // Transactions
        $result.="<h2>Transactions</h2>\n";
        $transactions_data_array=db_query_to_array("SELECT `address`,`amount`,`status`,`tx_id` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT 10");
        $result.="<table>\n";
        $result.="<tr><th>Address</th><th>Amount</th><th>Status</th><th>TX ID</th></tr>";
        foreach($transactions_data_array as $transactions_data) {
                $address=$transactions_data['address'];
                $amount=$transactions_data['amount'];
                $status=$transactions_data['status'];
                $tx_id=$transactions_data['tx_id'];
                $result.="<tr><td>$address</td><td align=right>$amount</td><td>$status</td><td>$tx_id</td></tr>\n";
        }
        $result.="</table>\n";

        // Return result
        return $result;
}


function html_log_section_admin() {
        $result="";
        $result.="<h2>Log</h2>\n";
        $data_array=db_query_to_array("SELECT `message`,`timestamp` FROM `log` ORDER BY `timestamp` DESC LIMIT 100");

        $result.="<table class='data_table'>\n";
        $result.="<tr><th>Timestamp</th><th>Message</th></tr>\n";
        foreach($data_array as $row) {
                $timestamp=$row['timestamp'];
                $message=$row['message'];
                $message_html=htmlspecialchars($message);
                $result.="<tr><td>$timestamp</td><td>$message_html</td></tr>\n";
        }
        $result.="</table>\n";
        return $result;
}

function html_message($message) {
        return "<div style='background:yellow;'>".html_escape($message)."</div>";
}

?>
