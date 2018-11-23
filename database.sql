SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `aliases` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) NOT NULL,
  `address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `log` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) DEFAULT NULL,
  `level` int(11) NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `mail` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) NOT NULL,
  `to` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `subject` text COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `is_sent` int(11) NOT NULL DEFAULT '0',
  `is_success` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `sessions` (
  `uid` bigint(20) NOT NULL,
  `user_uid` int(11) DEFAULT NULL,
  `session_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `transactions` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) DEFAULT NULL,
  `amount` double NOT NULL,
  `address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `tx_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `users` (
  `uid` bigint(20) NOT NULL,
  `mail` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `balance` double NOT NULL,
  `register_time` datetime NOT NULL,
  `login_time` datetime NOT NULL,
  `api_enabled` tinyint(4) NOT NULL,
  `api_key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `mail_notify_enabled` tinyint(4) NOT NULL,
  `withdraw_address` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `variables` (
  `uid` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `variables` (`uid`, `name`, `value`, `timestamp`) VALUES
(1, 'login_enabled', '1', '2018-11-23 08:08:47'),
(2, 'payouts_enabled', '1', '2018-11-23 08:08:57'),
(3, 'api_enabled', '1', '2018-11-23 08:09:06'),
(4, 'global_message', '', '2018-11-23 08:09:16'),
(5, 'news', 'News here!', '2018-11-23 08:09:28');

CREATE TABLE `wallets` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `received` double NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `aliases`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `user_uid` (`user_uid`),
  ADD KEY `address` (`address`);

ALTER TABLE `log`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `mail`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `user_uid` (`user_uid`),
  ADD KEY `is_sent` (`is_sent`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid_session` (`user_uid`,`session_id`) USING BTREE;

ALTER TABLE `transactions`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `user_uid` (`user_uid`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `mail` (`mail`);

ALTER TABLE `variables`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `wallets`
  ADD PRIMARY KEY (`uid`);


ALTER TABLE `aliases`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `log`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `mail`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sessions`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `transactions`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `variables`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `wallets`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
