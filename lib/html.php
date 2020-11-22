<?php

// Standard page begin
function html_page_begin($title,$token) {
        global $wallet_name;
        $lang_select_form=lang_select_form($token);

        return <<<_END
<!DOCTYPE html>
<html>
<head>
<title>$title</title>
<meta charset="utf-8" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="format-detection" content="telephone=no">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="icon" href="favicon.png" type="image/png">
<script src='jquery-3.5.1.min.js'></script>
<script src='bootstrap.bundle.min.js'></script>
<link rel="stylesheet" type="text/css" href="normalize.css">
<link rel="stylesheet" type="text/css" href="bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="style.css">
<script src='script.js'></script>
</head>
<body>

_END;
}

// Page end, scripts and footer
function html_page_end() {
        global $project_counter_name;

        $result=<<<_END
<center>
<hr width=10%>
<p>%footer_about%</p>
<p><img src='https://arikado.xyz/counter/?site=$project_counter_name'></p>
</center>
<script>

var hash = window.location.hash.substr(1);

if(hash != null && hash != '') {
        show_block(hash);
} else {
        show_block('dashboard');
}

$(function () {
        $('[data-toggle="tooltip"]').tooltip()
});

</script>
</body>
</html>

_END;
        return lang_parser($result);
}

function html_login_form($token) {
        global $recaptcha_public_key;
        $login_submit=lang_message("login_submit");
        $captcha=html_captcha();
        $result=<<<_END
<h2>Login</h2>
<form name=login method=post>
<input type=hidden name=action value='login'>
<input type=hidden name=token value='$token'>
<p>%login_login% <input type=text name=login></p>
<p>%login_password% <input type=password name=password></p>
$captcha
<p><input type=submit class='btn btn-primary' value='%login_submit%'></p>
</form>

_END;
        return lang_parser($result);
}

function html_logout_form($user_uid,$token) {
        $username=get_username_by_uid($user_uid);
        $result=<<<_END
<p>%header_greeting% $username (<a href='?action=logout&token=$token'>logout</a>)</p>

_END;
        return lang_parser($result);
}

function html_new_address_form($token) {
        $result=<<<_END
<form name=login method=post>
<input type=hidden name=action value='new_address'>
<input type=hidden name=token value='$token'>
<p><input type=submit class='btn btn-primary' value='%new_address_submit%'></p>
</form>

_END;
        return lang_parser($result);
}

function html_register_form($token) {
        global $recaptcha_public_key;
        $captcha=html_captcha();
        $result=<<<_END
<h2>Register</h2>
<form name=register method=post>
<input type=hidden name=action value='register'>
<input type=hidden name=token value='$token'>
<p>%register_login% <input type=text name=login></p>
<p>%register_mail% <input type=text name=mail></p>
<p>%register_password1% <input type=password name=password1></p>
<p>%register_password2% <input type=password name=password2></p>
<p>%register_withdraw% <input type=text name=withdraw_address></p>
$captcha
<p><input type=submit class='btn btn-primary' value='%register_submit%'></p>
</form>

_END;
        return lang_parser($result);
}

function html_tabs($user_uid) {
        global $wallet_name;

        $lang_select_form=lang_select_form($token);

        $result="";
        $result.=<<<_END
<nav class="navbar navbar-expand-lg navbar-light bg-light">
<a class="navbar-brand" href="#">$wallet_name</a>
<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse justify-content-center" id="navbarNav">
<ul class='navbar-nav'>

_END;
        if($user_uid) {
                $result.=html_menu_element("info","%tab_info%");
                $result.=html_menu_element("dashboard","%tab_dashboard%");
                $result.=html_menu_element("send","%tab_send%");
                $result.=html_menu_element("receive","%tab_receive%");
                $result.=html_menu_element("transactions","%tab_transactions%");
                $result.=html_menu_element("address_book","%tab_address_book%");
                $result.=html_menu_element("settings","%tab_settings%");
                if(is_admin($user_uid)) {
                        $result.=html_menu_element("control","%tab_control%");
                        //$result.=html_menu_element("log","%tab_log%");
                }
        } else {
                $result.=html_menu_element("info","%tab_info%");
                $result.=html_menu_element("login","%tab_login%");
                $result.=html_menu_element("register","%tab_register%");
                $result.=html_menu_element("client_state","%tab_client_state%");
        }
        $result.=<<<_END
</ul>
$lang_select_form
</div>
</nav>

_END;

        return lang_parser($result);
}

function html_menu_element($block,$text) {
        return "<li class='nav-item active'><a class='nav-link' href='#$block' onClick=\"show_block('$block')\">$text</a>\n";
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
	global $sending_fee_user;

        $result="";
        $balance=get_user_balance($user_uid);
        $balance=round($balance,8);

        $result.="<h2>%dashboard_balance% $balance $currency_short</h2>";

	$sending_fee_formatted=sprintf("%0.8f",$sending_fee_user);

        $result.=<<<_END
<form name=send method=post>
<input type=hidden name=action value='send'>
<input type=hidden name=token value='$token'>
<p>%send_address% <input type=text size=40 name=address id=send_address></p>
<p>%send_amount% <input type=text name=amount id=send_amount value=0> $currency_short</p>
<p><small>%send_fee_label% $sending_fee_formatted $currency_short</small></p>
<p><input type=submit class='btn btn-primary' value='%send_submit%'></p>
</form>

_END;
        return lang_parser($result);
}

// Address book
function html_address_book($user_uid,$token,$form=TRUE,$limit=10) {
        $result="";
        $result.=lang_parser("<h2>%address_book_header%</h2>\n");

        if($form) {
                $add_alias_form=<<<_END
<form name=add_alias method=post>
<input type=hidden name=action value='add_alias'>
<input type=hidden name=token value='$token'>
<div class='form-group row'>
<label for='alias_name' class='col-sm-2'>%address_book_label%</label>
<input type=text class="form-control col-sm-10" name=name id=alias_name>
</div>
<div class='form-group row'>
<label for='alias_address' class='col-sm-2'>%address_book_address%</label>
<input type=text class="form-control col-sm-10" size=40 name=address id='alias_address'>
</div>
<input type=submit class='btn btn-primary' value='%address_book_submit%'></p>
</form>

_END;
                $result=lang_parser($add_alias_form);
        }

        $user_uid_escaped=db_escape($user_uid);
        $alias_data_array=db_query_to_array("SELECT `uid`,`address`,`label` FROM `aliases` WHERE `user_uid`='$user_uid_escaped'");
        $result.="<table class='table table-hover'>\n";
        $result.=lang_parser("<tr><th>%address_book_table_header_label%</th><th>%address_book_table_header_address%</th></tr>");
        foreach($alias_data_array as $alias_data) {
                $address=$alias_data['address'];
                $label=$alias_data['label'];

                $address_url=html_address_url($address);
                $label_link=html_send_to_link($address,$label);

                $result.="<tr><td>$label_link</td><td>$address_url</td></tr>\n";
        }
        $result.="</table>\n";

        return $result;
}

// Receiving addresses
function html_receiving_addresses($user_uid,$token,$form=TRUE,$limit=10) {
        global $currency_short;
        $result="";
        $result.=lang_parser("<h2>%receive_header%</h2>\n");
        $user_uid_escaped=db_escape($user_uid);
        $receiving_addresses_data_array=db_query_to_array("SELECT `wallets`.`address`, `wallets`.`received`, `aliases`.`label` FROM `wallets`
                                                                LEFT OUTER JOIN `aliases` ON `aliases`.`user_uid` = '$user_uid_escaped' AND `aliases`.`address` = `wallets`.`address`
                                                                WHERE `wallets`.`user_uid`='$user_uid_escaped' LIMIT $limit");
        $result.="<table class='table_horizontal'>\n";
        $result.=lang_parser("<tr><th>%receive_table_header_address%</th><th>%receive_table_header_received% $currency_short</th><th>%receive_table_header_label%</th></tr>");
        foreach($receiving_addresses_data_array as $receiving_addresses_data) {
                $address = $receiving_addresses_data['address'];
                $received = $receiving_addresses_data['received'];
                $label = $receiving_addresses_data['label'];
                if($address == '') $address_url = lang_parser("<i>%receive_generating%</i>");
                else $address_url = html_address_url($address);
                $result .= "<tr><td>$address_url</td><td>$received</td><td>$label</td></tr>\n";
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
        global $wallet_receive_confirmations;

        $result="";

        // Transactions
        $result.=lang_parser("<h2>%transactions_header%</h2>\n");
        $user_uid_escaped=db_escape($user_uid);
        $transactions_data_array=db_query_to_array("SELECT `address`,`amount`,`fee`,`status`,`tx_id`,`confirmations`,`timestamp` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT $limit");
        $result.="<table class='table_horizontal'>\n";
        $result.=lang_parser("<tr><th>%transactions_table_header_address%</th><th>%transactions_table_header_amount% $currency_short</th>");
        $result.=lang_parser("<th>%transactions_table_header_fee%</th><th>%transactions_table_header_status%</th>");
        $result.=lang_parser("<th>%transactions_table_header_tx_id%</th><th>%transactions_table_header_timestamp%</th></tr>");
        foreach($transactions_data_array as $transactions_data) {
                $address=$transactions_data['address'];
                $amount=$transactions_data['amount'];
		$fee=$transactions_data['fee'];
                $status=$transactions_data['status'];
                $confirmations=$transactions_data['confirmations'];
                $tx_id=$transactions_data['tx_id'];
                $timestamp=$transactions_data['timestamp'];
                $amount=round($amount,8);

                switch($status) {
                        case 'sent': $status=lang_message("transactions_table_status_sent"); break;
                        case 'received': $status=lang_message("transactions_table_status_received"); break;
                        case 'processing': $status=lang_message("transactions_table_status_processing"); break;
                        case 'pending': $status=lang_message("transactions_table_status_pending")." ($confirmations/$wallet_receive_confirmations)"; break;
                        case 'sending error': $status=lang_message("transactions_table_status_sending_error"); break;
                        case 'address error': $status=lang_message("transactions_table_status_address_error"); break;
                }

		$fee_formatted=sprintf("%0.8f",$fee);

                $address_url=html_address_url($address);
                $tx_url=html_tx_url($tx_id);
                $result.="<tr><td>$address_url</td><td align=right>$amount</td><td>$fee_formatted</td><td>$status</td><td>$tx_url</td><td>$timestamp</td></tr>\n";
        }
        $result.="</table>\n";

        // Return result
        return $result;
}

// Client state
function html_client_state() {
        $result="";

        $result.="<h2>%client_state_header%</h2>\n";

        // Block count
        $current_block=get_variable("current_block");
        $result.="<p>%client_state_current_block% $current_block</p>\n";

        // Block hash
        $block_hash=get_variable("current_block_hash");
        $block_hash=html_block_hash($block_hash);
        $result.="<p>%client_state_current_block_hash% $block_hash</p>\n";

        // Payouts enabled
        $payouts_enabled=get_variable("payouts_enabled");
        $payouts_enabled_value=$payouts_enabled?"<span class='enabled'>%client_state_enabled%</span>":"<span class='disabled'>%client_state_disabled%</span>";
        $result.="<p>%client_state_payouts% $payouts_enabled_value</p>\n";

        // API enabled
        $api_enabled=get_variable("api_enabled");
        $api_enabled_value=$api_enabled?"<span class='enabled'>%client_state_enabled%</span>":"<span class='disabled'>%client_state_disabled%</span>";
        $result.="<p>%client_state_api% $api_enabled_value</p>\n";

        // Client state
        $client_last_update=get_variable("client_last_update");
        $last_update_interval=date("U")-$client_last_update;
        if($last_update_interval>0 && $last_update_interval<300) {
                $result.="<p>%client_state_client_state% <span class='enabled'>%client_state_on%</span></p>\n";
        } else {
                $minutes=floor($last_update_interval/60);
                if($minutes>120) {
                        $hours=floor($last_update_interval/3600);
                        $off_time="$hours %client_state_hour%";
                } else {
                        $off_time="$minutes %client_state_minute%";
                }
                $result.="<p>%client_state_client_state% <span class='disabled'>%client_state_off% ($off_time)</span></p>\n";
        }
        return lang_parser($result);
}

// Transactions
function html_transactions_big($user_uid,$token,$limit=10) {
        global $currency_short;

        $result="";

        // Transactions
        $result.=lang_parser("<h2>%transactions_header%</h2>\n");
        $user_uid_escaped=db_escape($user_uid);
        $transactions_data_array=db_query_to_array("SELECT `address`,`amount`,`status`,`tx_id`,`timestamp` FROM `transactions` WHERE `user_uid`='$user_uid_escaped' ORDER BY `timestamp` DESC LIMIT $limit");
        $result.="<table class='table_borderless'>\n";
        foreach($transactions_data_array as $transactions_data) {
                $address=$transactions_data['address'];
                $amount=$transactions_data['amount'];
                $status=$transactions_data['status'];
                $tx_id=$transactions_data['tx_id'];
                $timestamp=$transactions_data['timestamp'];

                $amount=round($amount,8);

                switch($status) {
                        case 'sent':
                        case 'processing':
                                $amount="<span style='color:red;'>-$amount $currency_short</span>";
                                $status_symbol="<span style='color:red;font-size:250%'>&minus;</span>";
                                break;
                        case 'pending':
                                $amount="$amount $currency_short";
                                $status_symbol="<span style='color:green;font-size:250%'>&hellip;</span>";
                                break;
                        case 'received':
                                $amount="$amount $currency_short";
                                $status_symbol="<span style='color:green;font-size:250%'>&plus;</span>";
                                break;
                        default:
                                $amount="$amount $currency_short";
                                $status_symbol="<span style='color:red;font-size:250%'>!</span>";
                                break;
                }

                $address_url=html_address_url($address);
                $tx_url=html_tx_url($tx_id);

                $result.="<tr><td rowspan=2 title='$status'>$status_symbol</td><td align=left>$timestamp</td><td align=right valign=bottom>$amount</td></tr>\n";
                $result.="<tr><td align=left valign=top colspan=2>$address_url</td></tr>\n";
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

        $result.=lang_parser("<h2>%settings_header%</h2>\n");
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
        $result.=lang_parser("<p>%settings_mail%")." <input type=text size=40 name=mail value='$mail_html'>";
        $result.=lang_parser(", %settings_notifications% <select name=mail_notify_enabled><option value='disabled'>%settings_disabled%</option><option value='enabled' $mail_notify_enabled_selected>%settings_enabled%</option></select>");
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
        $result.=lang_parser("<p>%settings_api_state% <select name=api_enabled><option value='disabled'>%settings_disabled%</option><option value='enabled' $api_enabled_selected>%settings_enabled%</option></select>");
        $result.=lang_parser(", %settings_regenerate_api_key% <select name=renew_api_key><option value='no'>%settings_no%</option><option value='yes'>%settings_yes%</option></select>");
        $result.=lang_parser(", %settings_api_key% <tt>$api_key_html</tt></p>");

        // Withdraw addresss
        //$result.="<h3>Withdraw address</h3>\n";
        $withdraw_address_html=html_escape($withdraw_address);
        $result.=lang_parser("<p>%settings_withdraw_address%")." <input type=text size=40 name=withdraw_address value='$withdraw_address_html'></p>";

        // Password options
        //$result.="<h3>Password</h3>\n";
        $result.=lang_parser("<p>%settings_password% <input type=password name=password></p>");
        $result.=lang_parser("<p>%settings_new_password1% <input type=password name=new_password1></p>");
        $result.=lang_parser("<p>%settings_new_password2% <input type=password name=new_password2></p>");

        // Submit button
        $result.=lang_parser("<p><input type=submit class='btn btn-primary' value='%settings_submit%'></p>\n");
        $result.="</form>\n";

        return $result;
}

// Admin settings
function html_admin_settings($user_uid,$token) {
        $result="";

        $result.=lang_parser("<h2>%wallet_settings_header%</h2>\n");
        $result.="<form name=admin_settings method=post>\n";
        $result.="<input type=hidden name=action value='admin_change_settings'>\n";
        $result.="<input type=hidden name=token value='$token'>\n";

        $login_enabled=get_variable("login_enabled");
        $login_enabled_selected=$login_enabled?"selected":"";

        $payouts_enabled=get_variable("payouts_enabled");
        $payouts_enabled_selected=$payouts_enabled?"selected":"";

        $api_enabled=get_variable("api_enabled");
        $api_enabled_selected=$api_enabled?"selected":"";

        $result.=lang_parser("<p>%wallet_settings_login_state% <select name=login_enabled><option value='disabled'>%wallet_settings_disabled%</option><option value='enabled' $login_enabled_selected>%wallet_settings_enabled%</option></select>\n");
        $result.=lang_parser(", %wallet_settings_payouts_state% <select name=payouts_enabled><option value='disabled'>%wallet_settings_disabled%</option><option value='enabled' $payouts_enabled_selected>%wallet_settings_enabled%</option></select>\n");
        $result.=lang_parser(", %wallet_settings_api_state% <select name=api_enabled><option value='disabled'>%wallet_settings_disabled%</option><option value='enabled' $api_enabled_selected>%wallet_settings_enabled%</option></select></p>\n");

        $info=get_variable("info");
        $info_html=html_escape($info);
        $result.=lang_parser("<p>%wallet_settings_info%</p>")."<p><textarea name=info rows=10 cols=50>$info_html</textarea></p>";

        $global_message=get_variable("global_message");
        $global_message_html=html_escape($global_message);
        $result.=lang_parser("<p>%wallet_settings_global_message% ")."<input type=text size=60 name=global_message value='$global_message_html'></p>\n";

        // Submit button
        $result.=lang_parser("<p><input type=submit class='btn btn-primary' value='%wallet_settings_submit%'></p>\n");
        $result.="</form>\n";

        return $result;
}

// Global message
function html_message_global() {
        $result="";

        $global_message=get_variable("global_message");
        if($global_message!='') {
                $result.="<div class='alert alert-warning' role='alert'>$global_message</div>";
        }

        return $result;
}

// Log
function html_log_section_admin() {
        $result="";
        $result.=lang_parser("<h2>%log_header%</h2>\n");
        $data_array=db_query_to_array("SELECT u.`login`,l.`message`,l.`timestamp` FROM `log` AS l
JOIN `users` u ON u.`uid`=l.`user_uid`
ORDER BY `timestamp` DESC LIMIT 100");

        $result.="<table class='table_horizontal'>\n";
        $result.=lang_parser("<tr><th>%log_table_header_timestamp%</th><th>%log_table_header_login%</th><th>%log_table_header_message%</th></tr>\n");
        foreach($data_array as $row) {
                $login=$row['login'];
                $timestamp=$row['timestamp'];
                $message=$row['message'];
                $login_html=html_escape($login);
                $message_html=html_escape($message);
                $result.="<tr><td>$timestamp</td><td>$login_html</td><td>$message_html</td></tr>\n";
        }
        $result.="</table>\n";
        return $result;
}

function html_message($message) {
        return "<div class='alert alert-primary' role='alert'>".html_escape($message)."</div>";
}

function html_address_url($address) {
        global $address_url;
        $address_begin=substr($address,0,10);
        $address_end=substr($address,-10,10);
        $send_to_link=lang_parser(html_send_to_link($address,"%link_send_to%"));
        $address_book_link=lang_parser(html_address_book_link($address,"%link_address_book%"));
        //$result="<div class='url_with_qr_container'>$address_begin......$address_end<div class='qr'>$address<br><a href='$address_url$address'>explorer</a>, <a href='#'>copy</a>, $send_to_link, $address_book_link<br><img src='qr.php?str=$address'></div></div>";
        $result=lang_parser("<div class='url_with_qr_container'>$address<div class='qr'>$address<br><a href='$address_url$address'>%link_block_explorer%</a>, $send_to_link, $address_book_link<br><img src='qr.php?str=$address'></div></div>");
        return $result;
}

function html_tx_url($tx) {
        global $tx_url;
        if($tx=='') return '';
        $tx_begin=substr($tx,0,10);
        $tx_end=substr($tx,-10,10);
        $result=lang_parser("<div class='url_with_qr_container'>$tx_begin......$tx_end<div class='qr'>$tx<br><a href='$tx_url$tx'>%link_block_explorer%</a><br><img src='qr.php?str=$tx'></div></div>");
        return $result;
}

function html_block_hash($hash) {
        global $block_url;
        if($hash=='') return '';
        $hash_begin=substr($hash,0,10);
        $hash_end=substr($hash,-10,10);
        //$result=lang_parser("<span class='url_with_qr_container'>$hash_begin......$hash_end<span class='qr'>$hash<br><a href='$block_url$hash'>%link_block_explorer%</a><br><img src='qr.php?str=$hash'></span></span>");
        $result=lang_parser("<span data-toggle='popover' title='$hash' data-content='<b>test</b>'>$hash_begin......$hash_end</span>");
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
        return lang_parser("<div class='container' id='main_block'>%loading_block%</div>\n");
}

// Show info
function html_info() {
        $result='';
        $result.=lang_parser("<h2>%info_header%</h2>\n");
        $result.=get_variable("info");
        return $result;
}

// Show captcha
function html_captcha() {
        $result=<<<_END
<p><img src='?captcha'><br>Code from image above: <input type=text name=captcha_code></p>
_END;
        return $result;
}
