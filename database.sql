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
  `level` int(11) DEFAULT NULL,
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
  `session` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `transactions` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) DEFAULT NULL,
  `amount` double NOT NULL,
  `address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `tx_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `users` (
  `uid` bigint(20) NOT NULL,
  `mail` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `balance` double NOT NULL DEFAULT '0',
  `register_time` datetime NOT NULL,
  `login_time` datetime NOT NULL,
  `api_enabled` tinyint(4) NOT NULL DEFAULT '0',
  `api_key` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `mail_notify_enabled` tinyint(4) NOT NULL DEFAULT '0',
  `mail_2fa_enabled` tinyint(4) NOT NULL,
  `withdraw_address` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_admin` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `variables` (
  `uid` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `variables` (`uid`, `name`, `value`, `timestamp`) VALUES
(1, 'login_enabled', '1', '2018-11-29 12:54:06'),
(2, 'payouts_enabled', '1', '2018-11-23 08:08:57'),
(3, 'api_enabled', '1', '2018-11-23 08:09:06'),
(4, 'global_message', '', '2018-11-23 08:09:16'),
(5, 'info', '<p>29th of November: settings implemended!</p>', '2018-11-29 12:53:56'),
(20, 'current_block', '1443000', '2018-11-29 14:14:01'),
(21, 'current_block_hash', '5a65da31946bbd9bcbc66bfb17ac5bf5f92a6ddba6145942ea1f7eccde308c60', '2018-11-29 14:14:01'),
(13, 'btc_per_grc', '1.5179951550687E-6', '2018-11-29 14:00:03'),
(14, 'usd_per_grc', '0.0065174428977991', '2018-11-29 14:00:03'),
(15, 'rub_per_grc', '0.43189138850845', '2018-11-29 14:00:03'),
(16, 'ltc_per_grc', '0.00019040997513233', '2018-11-29 14:00:03'),
(17, 'xrp_per_grc', '0.017199645255718', '2018-11-29 14:00:03'),
(18, 'eth_per_grc', '5.5050026538781E-5', '2018-11-29 14:00:03'),
(19, 'xlm_per_grc', '0.039331815249096', '2018-11-29 14:00:03');

CREATE TABLE `wallets` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `address` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `received` double NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `aliases`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid` (`user_uid`,`address`) USING BTREE;

ALTER TABLE `log`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `mail`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `user_uid` (`user_uid`),
  ADD KEY `is_sent` (`is_sent`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid_session` (`user_uid`,`session`) USING BTREE;

ALTER TABLE `transactions`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `tx_id` (`tx_id`,`address`,`user_uid`) USING BTREE,
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
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `log`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;
ALTER TABLE `mail`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `sessions`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;
ALTER TABLE `transactions`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
ALTER TABLE `users`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `variables`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
ALTER TABLE `wallets`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
