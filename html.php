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
<script src='script.js'></script>
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
<script>

var hash = window.location.hash.substr(1);

if(hash != null && hash != '') {
        show_block(hash);
} else {
        show_block('dashboard');
}
</script>
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
<script src='https://www.google.com/recaptcha/api.js'></script>
<div class="g-recaptcha" data-sitekey="$recaptcha_public_key"></div>
<p><input type=submit value='Login'></p>
</form>

_END;
        return $result;
}

function html_logout_form($user_uid,$token) {
        $username=get_username_by_uid($user_uid);
        $result=<<<_END
<p>Welcome, $username (<a href='?action=logout&token=$token'>logout</a>)</p>

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
<p>Withdraw address: <input type=text name=withdraw_address></p>
<script src='https://www.google.com/recaptcha/api.js'></script>
<div class="g-recaptcha" data-sitekey="$recaptcha_public_key"></div>
<p><input type=submit value='Register'></p>
</form>

_END;
        return $result;
}

function html_tabs($user_uid) {
        $result="";
        $result.="<div style='display: inline-block;'>\n";
        $result.="<ul class=horizontal_menu>\n";
        if($user_uid) {
                $result.=html_menu_element("info","Info");
                $result.=html_menu_element("dashboard","Dashboard");
                $result.=html_menu_element("send","Send");
                $result.=html_menu_element("receive","Receive");
                $result.=html_menu_element("transactions","Transactions");
                $result.=html_menu_element("address_book","Address book");
                $result.=html_menu_element("settings","Settings");
                if(is_admin($user_uid)) {
                        $result.=html_menu_element("control","Control");
                        $result.=html_menu_element("log","Log");
                }
        } else {
                $result.=html_menu_element("info","Info");
                $result.=html_menu_element("login","Login");
                $result.=html_menu_element("register","Register");
        }
        $result.="</ul>\n";
        $result.="</div>\n";

        return $result;
}

function html_menu_element($block,$text) {
        return "<li><a href='#$block' onClick=\"show_block('$block')\">$text</a>\n";
}

function html_wallet_form($user_uid,$token) {
        global $currency_short;

        $result="";
        $result.="<table><tr><td valign=top style='padding: 0 1em;'>";

        // Balance
        $result.=html_balance_and_send($user_uid,$token);
        $result.=html_client_state();

        // Transactions
        $limit=8;
        $result.="</td><td valign=top style='padding: 0 1em;'>";
        $result.=html_transactions_big($user_uid,$token,$limit);

        $result.="</td></tr></table>";

        // Return result
        return $result;
}

// Balance
function html_balance_and_send($user_uid,$token) {
        global $currency_short;

        $result="";
        $balance=get_user_balance($user_uid);
        $result.="<h2>Balance: $balance $currency_short</h2>";

        $result.=<<<_END
<form name=send method=post>
<input type=hidden name=action value='send'>
<input type=hidden name=token value='$token'>
<p>Address: <input type=text size=40 name=address id=send_address></p>
<p>Amount: <input type=text name=amount id=send_amount value=0> $currency_short</p>
<p><input type=submit value='Send'></p>
</form>

_END;
        return $result;
}

// Address book
function html_address_book($user_uid,$token,$form=TRUE,$limit=10) {
        $result="";
        $result.="<h2>Address book</h2>\n";

        if($form) {
                $result.=<<<_END
<form name=add_alias method=post>
<input type=hidden name=action value='add_alias'>
<input type=hidden name=token value='$token'>
<p>Label: <input type=text name=name id=alias_name> address: <input type=text size=40 name=address id=alias_address> <input type=submit value='Add'></p>
</form>

_END;
        }

        $user_uid_escaped=db_escape($user_uid);
        $alias_data_array=db_query_to_array("SELECT `uid`,`address`,`label` FROM `aliases` WHERE `user_uid`='$user_uid_escaped'");
        $result.="<table class='table_horizontal'>\n";
        $result.="<tr><th>Label</th><th>Address</th></tr>";
        foreach($alias_data_array as $alias_data) {
                $address=$alias_data['address'];
                $label=$alias_data['label'];

                if($address=='') $address_url="<i>generating...</i>";
                else $address_url=html_address_url($address);
                $label_link=html_send_to_link($address,$label);

                $result.="<tr><td>$label_link</td><td>$address_url</td></tr>\n";
        }
        $result.="</table>\n";

        return $result;
}

// Receiving addresses
function html_receiving_addresses($user_uid,$token,$form=TRUE,$limit=10) {
        $result="";
        $result.="<h2>Receiving addresses</h2>\n";
        $user_uid_escaped=db_escape($user_uid);
        $receiving_addresses_data_array=db_query_to_array("SELECT `address`,`received` FROM `wallets` WHERE `user_uid`='$user_uid_escaped' LIMIT $limit");
        $result.="<table class='table_horizontal'>\n";
        $result.="<tr><th>Address</th><th>Received</th></tr>";
        foreach($receiving_addresses_data_array as $receiving_addresses_data) {
                $address=$receiving_addresses_data['address'];
                $received=$receiving_addresses_data['received'];
                if($address=='') $address_url="<i>generating...</i>";
                else $address_url=html_address_url($address);
                $result.="<tr><td>$address_url</td><td>$received</td></tr>\n";
        }
        $result.="</table>\n";

        if($form) {
                $result.=html_new_address_form($token);
        }

        return $result;
}

// Transactions
function html_transactions($user_uid,$token,$limit=10) {
        global $currency_short;

        $result="";

        // Transactions
        $result.="<h2>Transactions</h2>\n";
        $user_uid_escaped=db_escape($user_uid);
        $transactions_data_array=db_query_to_array("SELECT `address`,`amount`,`status`,`tx_id` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT $limit");
        $result.="<table class='table_horizontal'>\n";
        $result.="<tr><th>Address</th><th>Amount, $currency_short</th><th>Status</th><th>TX ID</th></tr>";
        foreach($transactions_data_array as $transactions_data) {
                $address=$transactions_data['address'];
                $amount=$transactions_data['amount'];
                $status=$transactions_data['status'];
                $tx_id=$transactions_data['tx_id'];

                $address_url=html_address_url($address);
                $tx_url=html_tx_url($tx_id);
                $result.="<tr><td>$address_url</td><td align=right>$amount</td><td>$status</td><td>$tx_url</td></tr>\n";
        }
        $result.="</table>\n";

        // Return result
        return $result;
}

// Client state
function html_client_state() {
        $result="";

        $result.="<h2>Client state</h2>\n";

        // Block count
        $current_block=get_variable("current_block");
        $result.="<p>Current block: $current_block</p>\n";

        // Block hash
        $block_hash=get_variable("current_block_hash");
        $block_hash=html_block_hash($block_hash);
        $result.="<p>Current block hash: $block_hash</p>\n";

        // Payouts enabled
        $payouts_enabled=get_variable("payouts_enabled");
        $payouts_enabled_value=$payouts_enabled?"<span class='enabled'>enabled</span>":"<span class='disabled'>disabled</span>";
        $result.="<p>Payouts: $payouts_enabled_value</p>\n";

        // API enabled
        $api_enabled=get_variable("api_enabled");
        $api_enabled_value=$api_enabled?"<span class='enabled'>enabled</span>":"<span class='disabled'>disabled</span>";
        $result.="<p>API: $api_enabled_value</p>\n";

        // Client state
        $client_last_update=get_variable("client_last_update");
        $last_update_interval=date("U")-$client_last_update;
        if($last_update_interval>0 && $last_update_interval<300) {
                $result.="<p>Client state: <span class='enabled'>on</span></p>\n";
        } else {
                $minutes=floor($last_update_interval/60);
                if($minutes>120) {
                        $hours=floor($last_update_interval/3600);
                        $off_time="$hours hours";
                } else {
                        $off_time="$minutes minutes";
                }
                $result.="<p>Client state: <span class='disabled'>off ($off_time)</span></p>\n";
        }
        return $result;
}

// Transactions
function html_transactions_big($user_uid,$token,$limit=10) {
        global $currency_short;

        $result="";

        // Transactions
        $result.="<h2>Transactions</h2>\n";
        $user_uid_escaped=db_escape($user_uid);
        $transactions_data_array=db_query_to_array("SELECT `address`,`amount`,`status`,`tx_id` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT $limit");
        $result.="<table class='table_borderless'>\n";
        //$result.="<tr><th>Address</th><th>Amount, $currency_short</th><th>Status</th><th>TX ID</th></tr>";
        foreach($transactions_data_array as $transactions_data) {
                $address=$transactions_data['address'];
                $amount=$transactions_data['amount'];
                $status=$transactions_data['status'];
                $tx_id=$transactions_data['tx_id'];

                switch($status) {
                        case 'sent':
                        case 'processing':
                                $amount="<span style='color:red;'>-$amount $currency_short</span>";
                                $status_symbol="<span style='color:red;font-size:250%'>&minus;</span>";
                                break;
                        case 'pending':
                        case 'received':
                                $amount="$amount $currency_short";
                                $status_symbol="<span style='color:green;font-size:250%'>&plus;</span>";
                                break;
                        default:
                                $amount="$amount $currency_short";
                                $status_symbol="<span style='color:red;font-size:250%'>&#215;</span>";
                                break;
                }

                $address_url=html_address_url($address);
                $tx_url=html_tx_url($tx_id);
                $result.="<tr><td rowspan=2 title='$status'>$status_symbol</td><td align=right valign=bottom>$amount</td></tr>\n";
                $result.="<tr><td align=right valign=top>$address_url</td></tr>\n";
        }
        $result.="</table>\n";

        // Return result
        return $result;
}

// User settings
function html_user_settings($user_uid,$token) {
        $result="";

        $user_uid_escaped=db_escape($user_uid);
        $user_settings_data=db_query_to_array("SELECT `mail`,`api_enabled`,`api_key`,`mail_notify_enabled`,`mail_2fa_enabled`,`withdraw_address` FROM `users` WHERE `uid`='$user_uid_escaped'");
        $user_settings=array_pop($user_settings_data);
        $mail=$user_settings['mail'];
        $mail_notify_enabled=$user_settings['mail_notify_enabled'];
        $mail_2fa_enabled=$user_settings['mail_2fa_enabled'];
        $api_enabled=$user_settings['api_enabled'];
        $api_key=$user_settings['api_key'];
        $withdraw_address=$user_settings['withdraw_address'];

        $result.="<h2>Settings</h2>\n";
        $result.="<form name=user_settings method=post>\n";
        $result.="<input type=hidden name=action value='user_change_settings'>\n";
        $result.="<input type=hidden name=token value='$token'>\n";

        // Notifications
        //$result.="<h3>Mail settings</h3>\n";
        $mail_html=html_escape($mail);
        if($mail_notify_enabled) {
                $mail_notify_enabled_selected='selected';
        } else {
                $mail_notify_enabled_selected='';
        }
        $result.="<p>E-mail <input type=text size=40 name=mail value='$mail_html'>";
        $result.=", notifications state <select name=mail_notify_enabled><option>disabled</option><option $mail_notify_enabled_selected>enabled</option></select>";
        //$result.=", 2FA <select name=mail_2fa_enabled><option>disabled</option><option>enabled</option></select>";
        $result.="</p>";

        // User options
        //$result.="<h3>API settings</h3>\n";
        $api_key_html=html_escape($api_key);
        if($api_enabled) {
                $api_enabled_selected='selected';
        } else {
                $api_enabled_selected='';
        }
        $result.="<p>API state <select name=api_enabled><option>disabled</option><option $api_enabled_selected>enabled</option></select>";
        $result.=", regenerate API key <select name=renew_api_key><option>no</option><option>yes</option></select>";
        $result.=", API key: <tt>$api_key_html</tt></p>";

        // Withdraw addresss
        //$result.="<h3>Withdraw address</h3>\n";
        $withdraw_address_html=html_escape($withdraw_address);
        $result.="<p>Withdraw address <input type=text size=40 name=withdraw_address value='$withdraw_address_html'></p>";

        // Password options
        //$result.="<h3>Password</h3>\n";
        $result.="<p>Password (required to change settings) <input type=password name=password></p>";
        $result.="<p>New password (if you want to change password) <input type=password name=new_password1></p>";
        $result.="<p>New password one more time <input type=password name=new_password2></p>";

        // Submit button
        $result.="<p><input type=submit value='Apply'></p>\n";
        $result.="</form>\n";

        return $result;
}

// Admin settings
function html_admin_settings($user_uid,$token) {
        $result="";

        $result.="<h2>Wallet settings</h2>\n";
        $result.="<form name=admin_settings method=post>\n";
        $result.="<input type=hidden name=action value='admin_change_settings'>\n";
        $result.="<input type=hidden name=token value='$token'>\n";

        $login_enabled=get_variable("login_enabled");
        $login_enabled_selected=$login_enabled?"selected":"";

        $payouts_enabled=get_variable("payouts_enabled");
        $payouts_enabled_selected=$payouts_enabled?"selected":"";

        $api_enabled=get_variable("api_enabled");
        $api_enabled_selected=$api_enabled?"selected":"";

        $result.="<p>Login/register state: <select name=login_enabled><option>disabled</option><option $login_enabled_selected>enabled</option></select>\n";
        $result.=", payouts state: <select name=payouts_enabled><option>disabled</option><option $payouts_enabled_selected>enabled</option></select>\n";
        $result.=", API state: <select name=api_enabled><option>disabled</option><option $api_enabled_selected>enabled</option></select></p>\n";

        $info=get_variable("info");
        $info_html=html_escape($info);
        $result.="<textarea name=info rows=10 cols=50>$info_html</textarea>";

        $global_message=get_variable("global_message");
        $global_message_html=html_escape($global_message);
        $result.="<p>Global message: <input type=text size=60 name=global_message value='$global_message_html'></p>\n";

        // Submit button
        $result.="<p><input type=submit value='Apply'></p>\n";
        $result.="</form>\n";

        return $result;
}

// Global message
function html_message_global() {
        $result="";

        $global_message=get_variable("global_message");
        if($global_message!='') {
                $result.="<div class='message_global'>$global_message</div>";
        }

        return $result;
}

// Log
function html_log_section_admin() {
        $result="";
        $result.="<h2>Log</h2>\n";
        $data_array=db_query_to_array("SELECT `message`,`timestamp` FROM `log` ORDER BY `timestamp` DESC LIMIT 100");

        $result.="<table class='table_horizontal'>\n";
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

function html_address_url($address) {
        global $address_url;
        $address_begin=substr($address,0,10);
        $address_end=substr($address,-10,10);
        $send_to_link=html_send_to_link($address,"send to");
        $address_book_link=html_address_book_link($address,"address book");
        //$result="<div class='url_with_qr_container'>$address_begin......$address_end<div class='qr'>$address<br><a href='$address_url$address'>explorer</a>, <a href='#'>copy</a>, $send_to_link, $address_book_link<br><img src='qr.php?str=$address'></div></div>";
        $result="<div class='url_with_qr_container'>$address<div class='qr'>$address<br><a href='$address_url$address'>explorer</a>, <a href='#'>copy</a>, $send_to_link, $address_book_link<br><img src='qr.php?str=$address'></div></div>";
        return $result;
}

function html_tx_url($tx) {
        global $tx_url;
        if($tx=='') return '';
        $tx_begin=substr($tx,0,10);
        $tx_end=substr($tx,-10,10);
        $result="<div class='url_with_qr_container'>$tx_begin......$tx_end<div class='qr'>$tx<br><a href='$tx_url$tx'>explorer</a>, <a href='#'>copy</a><br><img src='qr.php?str=$tx'></div></div>";
        return $result;
}

function html_block_hash($hash) {
        global $block_url;
        if($hash=='') return '';
        $hash_begin=substr($hash,0,10);
        $hash_end=substr($hash,-10,10);
        $result="<span class='url_with_qr_container'>$hash_begin......$hash_end<span class='qr'>$hash<br><a href='$block_url$hash'>explorer</a>, <a href='#'>copy</a><br><img src='qr.php?str=$hash'></span></span>";
        return $result;
}

function html_send_to_link($address,$text) {
        return "<a href='#' onClick=\"document.getElementById('send_address').value='$address'; return false;\">$text</a>";
}

function html_address_book_link($address,$text) {
        return "<a href='#' onClick=\"document.getElementById('alias_address').value='$address'; return false;\">$text</a>";
}

// Loadable block for ajax
function html_loadable_block() {
        return "<div id='main_block'>Loading block...</div>\n";
}

// Show info
function html_info() {
        $result='';
        $result.="<h2>Info</h2>\n";
        $result.=get_variable("info");
        return $result;
}

?>
