<?php
require_once("../lib/settings.php");
require_once("../lib/language.php");
require_once("../lib/db.php");
require_once("../lib/core.php");
require_once("../lib/html.php");
require_once("../lib/email.php");
require_once("../lib/captcha.php");
require_once("../lib/broker.php");
require_once("../lib/logger.php");

db_connect();

if(isset($_COOKIE['lang'])) $lang=$_COOKIE['lang'];
else $lang=$default_language;
$current_language=lang_load($lang);

$session=get_session();
$user_uid=get_user_uid_by_session($session);
$token=get_user_token_by_session($session);

// Captcha
if(isset($_GET['captcha'])) {
        captcha_show($session);
        die();
}

if(isset($_POST['action'])) $action=stripslashes($_POST['action']);
else if(isset($_GET['action'])) $action=stripslashes($_GET['action']);

if(isset($action)) {
        if(isset($_POST['token'])) $received_token=stripslashes($_POST['token']);
        else if(isset($_GET['token'])) $received_token=stripslashes($_GET['token']);
        if($received_token!=$token) die("Wrong token");

        if($action=='login') {
                $captcha_code=stripslashes($_POST['captcha_code']);
                if(captcha_check($session,$captcha_code)) {
                        $login=stripslashes($_POST['login']);
                        $password=stripslashes($_POST['password']);
                        $message=user_login($session,$login,$password);
                } else {
                        $message="login_failed_invalid_captcha";
                }
                captcha_regenerate($session);
        } else if($action=='register') {
                $captcha_code=stripslashes($_POST['captcha_code']);
                if(captcha_check($session,$captcha_code)) {
                        $login=stripslashes($_POST['login']);
                        $mail=stripslashes($_POST['mail']);
                        $password1=stripslashes($_POST['password1']);
                        $password2=stripslashes($_POST['password2']);
                        $withdraw_address=stripslashes($_POST['withdraw_address']);
                        $message=user_register($session,$mail,$login,$password1,$password2,$withdraw_address);
                } else {
                        $message="register_failed_invalid_captcha";
                }
                captcha_regenerate($session);
        } else if($action=='logout') {
                user_logout($session);
                $message="logout_successfull";
        } else if($action=='send') {
                $amount=stripslashes($_POST['amount']);
                $address=stripslashes($_POST['address']);
                $result=user_send($user_uid,$amount,$address);
                if($result) $message="send_successfull";
                else $message="send_failed";
        } else if($action=='new_address') {
                $result=user_create_new_address($user_uid);
                if($result) $message="create_new_address_successfull";
                else $message="create_new_address_failed";
        } else if($action=='add_alias') {
                $address=stripslashes($_POST['address']);
                $name=stripslashes($_POST['name']);
                $message=set_alias($user_uid,$name,$address);
        } else if($action=='user_change_settings') {
                $mail=stripslashes($_POST['mail']);
                $mail_notify_enabled=stripslashes($_POST['mail_notify_enabled']);
                $api_enabled=stripslashes($_POST['api_enabled']);
                $renew_api_key=stripslashes($_POST['renew_api_key']);
                $withdraw_address=stripslashes($_POST['withdraw_address']);
                $password=stripslashes($_POST['password']);
                $new_password1=stripslashes($_POST['new_password1']);
                $new_password2=stripslashes($_POST['new_password2']);

                $message=user_change_settings($user_uid,$mail,$mail_notify_enabled,$api_enabled,$renew_api_key,$withdraw_address,$password,$new_password1,$new_password2);
        } else if($action=='admin_change_settings' && is_admin($user_uid)) {
                $login_enabled=stripslashes($_POST['login_enabled']);
                $payouts_enabled=stripslashes($_POST['payouts_enabled']);
                $api_enabled=stripslashes($_POST['api_enabled']);
                $info=stripslashes($_POST['info']);
                $global_message=stripslashes($_POST['global_message']);
                $message=admin_change_settings($login_enabled,$payouts_enabled,$api_enabled,$info,$global_message);
        } else if($action=='change_lang') {
                $lang=stripslashes($_POST['lang']);
                setcookie('lang',$lang,time()+86400*30);
                $message='';
        }
        if(isset($message) && $message!='') setcookie("message",$message);
        header("Location: ./");
        die();
}

if(isset($_GET['ajax']) && isset($_GET['block'])) {
        if($user_uid) {
                switch($_GET['block']) {
                        case 'address_book':
                                $limit=10000;
                                $form=TRUE;
                                echo html_address_book($user_uid,$token,$form,$limit);
                                break;
                        case 'control':
                                if(is_admin($user_uid)) {
                                        echo html_admin_settings($user_uid,$token);
                                }
                                break;
                        default:
                        case 'dashboard':
                                echo html_wallet_form($user_uid,$token);
                                break;
                        case 'info':
                                echo html_info();
                                break;
                        case 'log':
                                if(is_admin($user_uid)) {
                                        echo html_log_section_admin();
                                }
                                break;
                        case 'receive':
                                $limit=10000;
                                $form=TRUE;
                                echo html_receiving_addresses($user_uid,$token,$form,$limit);
                                break;
                        case 'send':
                                $limit=10;
                                $form=FALSE;
                                echo html_balance_and_send($user_uid,$token);
                                echo html_address_book($user_uid,$token,$form,$limit);
                                break;
                        case 'settings':
                                echo html_user_settings($user_uid,$token);
                                break;
                        case 'transactions':
                                $limit = 100;
                                echo html_transactions($user_uid, $token, $limit);
                                break;
                        case 'transactions_csv':
                                $limit = 10000;
                                echo html_transactions_csv($user_uid, $token, $limit);
                                break;
                                
                }
        } else {
                switch($_GET['block']) {
                        case 'client_state':
                                echo html_client_state();
                                break;
                        default:
                        case 'info':
                                echo html_info();
                                break;
                        case 'login':
                                echo html_login_form($token);
                                break;
                        case 'register':
                                echo html_register_form($token);
                                break;
                }
        }
        die();
}

if(isset($_COOKIE['message'])) {
        $message=$_COOKIE['message'];
        setcookie("message","");
} else {
        $message="";
}
echo html_page_begin($wallet_name,$token);
echo html_message_global();

if($message) {
        $lang_message=lang_message($message);
        if($lang_message!='') {
                echo "<div class='alert alert-primary' role='alert'>$lang_message</div>";
        }
}
echo html_tabs($user_uid);
if($user_uid) {
        echo html_logout_form($user_uid,$token);
}
echo html_loadable_block();

echo html_page_end();
