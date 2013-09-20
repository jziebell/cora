-- Adminer 3.7.1 MySQL dump

SET NAMES utf8;

CREATE TABLE `api_log` (
  `api_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_ip` int(10) unsigned NOT NULL,
  `request_api_key` char(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `request_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_resource` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `request_method` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `request_arguments` text COLLATE utf8_unicode_ci,
  `response_error_code` int(10) unsigned DEFAULT NULL,
  `response_data` text COLLATE utf8_unicode_ci,
  `response_time` decimal(5,4) unsigned DEFAULT NULL,
  `response_query_count` smallint(5) unsigned DEFAULT NULL,
  `response_query_time` decimal(5,4) unsigned DEFAULT NULL,
  PRIMARY KEY (`api_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `api_session` (
  `session_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `external_id` int(10) unsigned DEFAULT NULL,
  `timeout` int(10) unsigned DEFAULT NULL,
  `life` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(10) unsigned NOT NULL,
  `last_used_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_used_by` int(10) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `key` (`session_key`),
  KEY `user_id` (`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `api_user` (
  `api_user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_key` char(40) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`api_user_id`),
  UNIQUE KEY `api_key` (`api_key`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `api_user_ip` (
  `api_user_ip_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `api_user_id` int(10) unsigned NOT NULL,
  `ip` int(10) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`api_user_ip_id`),
  UNIQUE KEY `api_user_id` (`api_user_id`,`ip`),
  CONSTRAINT `api_user_ip_ibfk_1` FOREIGN KEY (`api_user_id`) REFERENCES `api_user` (`api_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `api_user_session` (
  `session_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `external_id` int(10) unsigned DEFAULT NULL,
  `timeout` int(10) unsigned DEFAULT NULL,
  `life` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(10) unsigned NOT NULL,
  `last_used_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_used_by` int(10) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `key` (`session_key`),
  KEY `user_id` (`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 2013-09-18 10:21:54
