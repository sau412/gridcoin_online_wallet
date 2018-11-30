<?php
$json_language=<<<_END
{
"admin_change_settings_successfull":{
        "en":"Wallet settings changed"
        },
"register_failed_password_mismatch":{
        "en":"Register failed. Password mismatch."
        },
"register_failed_invalid_password":{
        "en":"Register failed. Invalid password."
        },
"register_failed_invalid_login":{
        "en":"Register failed. Invalid login."
        },
"register_failed_invalid_captcha":{
        "en":"Register failed. Invalid captcha."
        },
"register_failed_disabled":{
        "en":"Register failed. Registration/login disabled."
        },
"register_successfull":{
        "en":"Register successfull."
        },
"login_successfull":{
        "en":""
        },
"login_failed_invalid_login":{
        "en":"Login failed. Invalid login/password"
        },
"login_failed_invalid_password":{
        "en":"Login failed. Invalig login/password"
        },
"login_failed_invalid_captcha":{
        "en":"Login failed. Invalid captcha."
        },
"login_failed_disabled":{
        "en":"Login failed. Registration/login disabled."
        },
"user_change_settings_failed_new_password_mismatch":{
        "en":"Change settings failed. New password mismatch."
        },
"user_change_settings_failed_password_incorrect":{
        "en":"Change settings failed. Password incorrect."
        },
"user_change_settings_successfull":{
        "en":"Settings successfully changed"
        },
"logout_successfull":{
        "en":""
        },
"alias_deleted":{
        "en":"Address book entry deleted"
        },
"alias_changed":{
        "en":"Address book entry changed"
        },
"send_failed":{
        "en":"Sending failed"
        },
"send_successfull":{
        "en":"Sending successfull"
        },
"create_new_address_failed":{
        "en":"Creating new address failed"
        },
"create_new_address_successfull":{
        "en":"New address requested"
        }
}
_END;

function lang_load($lang_code) {
        global $json_language;
        $json_language_parsed=json_decode($json_language);
        $current_language=array();
        foreach($json_language_parsed as $key=>$lang_variable) {
                if(property_exists($lang_variable,$lang_code)) $current_language[$key]=$lang_variable->$lang_code;
        }
        return $current_language;
}

function lang_message($code) {
        global $current_language;
        if(isset($current_language[$code])) {
                return $current_language[$code];
        }
        return $code;
}
?>
