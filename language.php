<?php
$json_language=<<<_END
{
"admin_change_settings_successfull":{
        "en":"Global wallet settings changed",
        "ru":"Глобальные настройки кошелька изменены"
        },
"register_failed_password_mismatch":{
        "en":"Register failed. Password mismatch.",
        "ru":"Ошибка при регистрациии: пароли не совпадают."
        },
"register_failed_invalid_password":{
        "en":"Register failed. Invalid password.",
        "ru":"Ошбика при регистрации: неправильный пароль."
        },
"register_failed_invalid_login":{
        "en":"Register failed. Invalid login.",
        "ru":"Ошибка при регистрации: неправильный логин."
        },
"register_failed_invalid_captcha":{
        "en":"Register failed. Invalid captcha.",
        "ru":"Ошибка при регистрации: неправильная каптча."
        },
"register_failed_disabled":{
        "en":"Register failed. Registration/login disabled.",
        "ru":"Ошибка при регистрации: регистрация и вход на ресурсе отключены."
        },
"register_successfull":{
        "en":"Register successfull.",
        "ru":"Регистрация прошла успешно."
        },
"login_successfull":{
        "en":"",
        "ru":""
        },
"login_failed_invalid_login":{
        "en":"Login failed. Invalid login/password",
        "ru":"Ошибка при входе: неправильный логин или пароль."
        },
"login_failed_invalid_password":{
        "en":"Login failed. Invalig login/password",
        "ru":"Ошибка при входе: неправильный логин или пароль."
        },
"login_failed_invalid_captcha":{
        "en":"Login failed. Invalid captcha.",
        "ru":"Ошибка при входе: неправильная каптча."
        },
"login_failed_disabled":{
        "en":"Login failed. Registration/login disabled.",
        "ru":"Ошибка при входе: регистрация и вход на ресурсе отключены."
        },
"user_change_settings_failed_new_password_mismatch":{
        "en":"Change settings failed. New password mismatch.",
        "ru":"Ошибка при изменении настроек: пароли не совпадают."
        },
"user_change_settings_failed_password_incorrect":{
        "en":"Change settings failed. Password incorrect.",
        "ru":"Ошибка при изменении настроек: неправильный пароль."
        },
"user_change_settings_successfull":{
        "en":"Settings successfully changed",
        "ru":"Настройки успешно изменены"
        },
"header_greeting":{
        "en":"Welcome,",
        "ru":"Вы вошли как"
        },
"logout_successfull":{
        "en":"",
        "ru":""
        },
"alias_deleted":{
        "en":"Address book entry deleted",
        "ru":"Удалена запись из адресной книги"
        },
"alias_changed":{
        "en":"Address book entry changed",
        "ru":"Запись в адресной книге изменена"
        },
"send_failed":{
        "en":"Sending failed",
        "ru":"Ошибка при отправке"
        },
"send_successfull":{
        "en":"Sending successfull",
        "ru":"Успешно отправлено"
        },
"create_new_address_failed":{
        "en":"Creating new address failed",
        "ru":"Ошибка при создании нового адреса"
        },
"create_new_address_successfull":{
        "en":"New address requested",
        "ru":"Новый адрес запрошен"
        },
"footer_about":{
        "en":"Opensource gridcoin online wallet (<a href='https://github.com/sau412/gridcoin_online_wallet'>github link</a>) by Vladimir Tsarev, my nickname is sau412 on telegram, twitter, facebook, gmail, github, vk.",
        "ru":""
        },
"login_login":{
        "en":"Login:",
        "ru":"Логин:"
        },
"login_password":{
        "en":"Password:",
        "ru":"Пароль:"
        },
"login_submit":{
        "en":"Login",
        "ru":"Вход"
        },
"new_address_submit":{
        "en":"Request new receiving address",
        "ru":"Запросить новый адрес"
        },
"register_login":{
        "en":"Login:",
        "ru":"Логин:"
        },
"register_mail":{
        "en":"E-mail:",
        "ru":"E-mail:"
        },
"register_password1":{
        "en":"Password 1:",
        "ru":"Пароль 1:"
        },
"register_password2":{
        "en":"Password 2:",
        "ru":"Пароль 2:"
        },
"register_withdraw":{
        "en":"Withdraw address:",
        "ru":"Адрес для вывода:"
        },
"register_submit":{
        "en":"Register",
        "ru":"Регистрация"
        },
"tab_info":{
        "en":"Info",
        "ru":"Информация"
        },
"tab_dashboard":{
        "en":"Dashboard",
        "ru":"Основное"
        },
"tab_send":{
        "en":"Send",
        "ru":"Отправка"
        },
"tab_receive":{
        "en":"Receive",
        "ru":"Получение"
        },
"tab_transactions":{
        "en":"Transactions",
        "ru":"Транзкции"
        },
"tab_address_book":{
        "en":"Address book",
        "ru":"Адресная книга"
        },
"tab_settings":{
        "en":"Settings",
        "ru":"Настройки"
        },
"tab_control":{
        "en":"Control",
        "ru":"Управление"
        },
"tab_log":{
        "en":"Log",
        "ru":"Лог"
        },
"tab_login":{
        "en":"Login",
        "ru":"Вход"
        },
"tab_register":{
        "en":"Register",
        "ru":"Регистрация"
        },
"dashboard_balance":{
        "en":"Balance:",
        "ru":"Баланс:"
        },
"send_address":{
        "en":"Address:",
        "ru":"Адрес:"
        },
"send_amount":{
        "en":"Amount:",
        "ru":"Количество:"
        },
"send_submit":{
        "en":"Send",
        "ru":"Отправить"
        },
"address_book_header":{
        "en":"Address book",
        "ru":"Адресная книга"
        },
"address_book_label":{
        "en":"Label:",
        "ru":"Название:"
        },
"address_book_address":{
        "en":"address:",
        "ru":"адрес:"
        },
"address_book_submit":{
        "en":"Add",
        "ru":"Добавить"
        },
"address_book_table_header_label":{
        "en":"Label",
        "ru":"Название"
        },
"address_book_table_header_address":{
        "en":"Address",
        "ru":"Адрес"
        },
"receive_header":{
        "en":"Receiving addresses",
        "ru":"Адреса для получения"
        },
"receive_generating":{
        "en":"generating...",
        "ru":"генерация..."
        },
"receive_table_header_address":{
        "en":"Address",
        "ru":"Адрес"
        },
"receive_table_header_received":{
        "en":"Received,",
        "ru":"Получено,"
        },
"transactions_header":{
        "en":"Transactions",
        "ru":"Транзакции"
        },
"transactions_table_header_address":{
        "en":"Address",
        "ru":"Адрес"
        },
"transactions_table_header_amount":{
        "en":"Amount,",
        "ru":"Количество,"
        },
"transactions_table_header_status":{
        "en":"Status",
        "ru":"Состояние"
        },
"transactions_table_header_tx_id":{
        "en":"TX ID",
        "ru":"TX ID"
        },
"transactions_table_header_timestamp":{
        "en":"Timestamp",
        "ru":"Время"
        },
"transactions_table_status_sent":{
        "en":"sent",
        "ru":"отправлено"
        },
"transactions_table_status_received":{
        "en":"received",
        "ru":"получено"
        },
"transactions_table_status_processing":{
        "en":"processing",
        "ru":"обработка"
        },
"transactions_table_status_pending":{
        "en":"pending",
        "ru":"подтверждается"
        },
"transactions_table_status_sending_error":{
        "en":"sending error",
        "ru":"ошибка при отправке"
        },
"transactions_table_status_address_error":{
        "en":"address error",
        "ru":"ошибка в адресе"
        },
"client_state_header":{
        "en":"Client state",
        "ru":"Состояние клиента:"
        },
"client_state_current_block":{
        "en":"Current block:",
        "ru":"Текущий блок:"
        },
"client_state_current_block_hash":{
        "en":"Hash:",
        "ru":"Хэш:"
        },
"client_state_payouts":{
        "en":"Payouts:",
        "ru":"Выплаты:"
        },
"client_state_api":{
        "en":"API:",
        "ru":"API:"
        },
"client_state_client_state":{
        "en":"Client state:",
        "ru":"Состояние клиента:"
        },
"client_state_enabled":{
        "en":"enabled",
        "ru":"разрешено"
        },
"client_state_disabled":{
        "en":"disabled",
        "ru":"запрещено"
        },
"client_state_on":{
        "en":"on",
        "ru":"включено"
        },
"client_state_off":{
        "en":"off",
        "ru":"выключено"
        },
"client_state_hour":{
        "en":"h",
        "ru":"ч"
        },
"client_state_minute":{
        "en":"min",
        "ru":"мин"
        },
"settings_header":{
        "en":"Settings",
        "ru":"Настройки"
        },
"settings_mail":{
        "en":"E-mail:",
        "ru":"E-mail:"
        },
"settings_notifications":{
        "en":"notifications state",
        "ru":"состояние оповещений"
        },
"settings_enabled":{
        "en":"enabled",
        "ru":"включено"
        },
"settings_disabled":{
        "en":"disabled",
        "ru":"выключено"
        },
"settings_api_state":{
        "en":"API state:",
        "ru":"состояние API"
        },
"settings_yes":{
        "en":"yes",
        "ru":"да"
        },
"settings_no":{
        "en":"no",
        "ru":"нет"
        },
"settings_regenerate_api_key":{
        "en":"regenerate API key",
        "ru":"пересоздать ключ API"
        },
"settings_api_key":{
        "en":"API key:",
        "ru":"ключ API:"
        },
"settings_withdraw_address":{
        "en":"Withdraw address:",
        "ru":"Адрес для вывода:"
        },
"settings_password":{
        "en":"Password (required to change settings):",
        "ru":"Пароль (необходим для смены настроек):"
        },
"settings_new_password1":{
        "en":"New password (if you want to change password):",
        "ru":"Новы пароль (если вы хотите сменить ваш пароль):"
        },
"settings_new_password2":{
        "en":"New password one more time:",
        "ru":"Новый пароль ещё раз:"
        },
"settings_submit":{
        "en":"Apply",
        "ru":"Применить"
        },
"log_header":{
        "en":"Log",
        "ru":"Лог"
        },
"log_table_header_timestamp":{
        "en":"Timestamp",
        "ru":"Время"
        },
"log_table_header_message":{
        "en":"Message",
        "ru":"Сообщение"
        },
"info_header":{
        "en":"Info",
        "ru":"Информация"
        },
"loading_block":{
        "en":"Loading block...",
        "ru":"Загрузка блока..."
        },
"link_block_explorer":{
        "en":"block explorer",
        "ru":"обозреватель блоков"
        },
"link_send_to":{
        "en":"send to",
        "ru":"отправить"
        },
"link_address_book":{
        "en":"address book",
        "ru":"Адресная книга"
        },
"wallet_settings_header":{
        "en":"Wallet settings",
        "ru":"Настройки кошелька"
        },
"wallet_settings_enabled":{
        "en":"enabled",
        "ru":"включено"
        },
"wallet_settings_disabled":{
        "en":"disabled",
        "ru":"выключено"
        },
"wallet_settings_login_state":{
        "en":"Login/register state:",
        "ru":"Состояние входа и регистрации:"
        },
"wallet_settings_payouts_state":{
        "en":"payouts state:",
        "ru":"состояние выплат:"
        },
"wallet_settings_api_state":{
        "en":"API state:",
        "ru":"состояние API:"
        },
"wallet_settings_global_message":{
        "en":"Global message:",
        "ru":"Глобальное сообщение:"
        },
"wallet_settings_info":{
        "en":"Info block:",
        "ru":"Информационный блок:"
        },
"wallet_settings_submit":{
        "en":"Apply",
        "ru":"Применить"
        }
}
_END;

function lang_load($lang_code) {
        global $json_language;
        global $default_language;
        if($lang_code=='') $lang_code=$default_language;
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

function lang_select_form($token) {
        return <<<_END
<form name=change_language method=post class='lang_selector'>
<input type=hidden name=action value='change_lang'>
<input type=hidden name=token value='$token'>
<select name=lang onChange='form.submit();'><option>language</option><option value='en'>English</option><option value='ru'>Русский</option></select>
</form>

_END;
}

function lang_parser($text) {
        while(preg_match('/%([A-Za-z0-9_]+)%/',$text,$matches)) {
                $replace_from=$matches[0];
                $replace_to=lang_message($matches[1]);
//echo "Replacing from '$replace_from' to '$replace_to'<br>";
                $text=str_replace($replace_from,$replace_to,$text);
        }
        return $text;
}

?>
