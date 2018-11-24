<?php
require_once("settings.php");
require_once("db.php");
require_once("core.php");
require_once("html.php");
//require_once("gridcoin.php");

db_connect();

$session=get_session();
$user_uid=get_user_uid_by_session($session);
$token=get_user_token_by_session($session);

if(isset($_POST) && isset($_POST['action'])) {
        $post_token=stripslashes($_POST['token']);
        if($post_token!=$token) die("Wrong token");

        $action=stripslashes($_POST['action']);
        if($action=='login') {
                $recaptcha_response=stripslashes($_POST['g-recaptcha-response']);
                if(recaptcha_check($recaptcha_response)) {
                        $login=stripslashes($_POST['login']);
                        $password=stripslashes($_POST['password']);
                        $result=user_login($session,$login,$password);
                        if($result!=TRUE) $message="Login error";
                }
        } else if($action=='register') {
                $recaptcha_response=stripslashes($_POST['g-recaptcha-response']);
                if(recaptcha_check($recaptcha_response)) {
                        $login=stripslashes($_POST['login']);
                        $mail=stripslashes($_POST['mail']);
                        $password1=stripslashes($_POST['password1']);
                        $password2=stripslashes($_POST['password2']);
                        $result=user_register($session,$mail,$login,$password1,$password2);
                        if($result!=TRUE) $message="Register error";
                }
        } else if($action=='logout') {
                user_logout($session);
        } else if($action=='send') {
                $amount=stripslashes($_POST['amount']);
                $address=stripslashes($_POST['address']);
                $result=user_send($user_uid,$amount,$address);
                if($result!=TRUE) $message="Sending error";
        } else if($action=='new_address') {
                user_create_new_address($user_uid);
        }
        if(isset($message) && $message!='') setcookie("message",$message);
        header("Location: ./");
        die();
}

echo html_page_begin($wallet_name);

if(!$user_uid) {
        echo html_login_form($token);
        echo html_register_form($token);
} else {
        echo html_logout_form($token);
        echo html_wallet_form($user_uid,$token);
}

//echo "Session $session\n";
//echo "User uid '$user_uid'\n";
//echo "Token $token\n";

echo html_page_end();

?>
