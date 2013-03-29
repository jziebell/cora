SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `api_log` (
  `api_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_ip` int(10) unsigned NOT NULL,
  `request_api_key` char(32) DEFAULT NULL,
  `request_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_resource` varchar(255) DEFAULT NULL,
  `request_method` varchar(255) DEFAULT NULL,
  `request_arguments` text,
  `response_has_error` tinyint(1) DEFAULT NULL,
  `response_body` mediumtext,
  `response_time` decimal(5,4) unsigned NOT NULL,
  `response_query_count` smallint(5) unsigned DEFAULT NULL,
  `response_query_time` decimal(5,4) unsigned DEFAULT NULL,
  PRIMARY KEY (`api_log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `api_session` (
  `session_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` char(128) NOT NULL,
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `api_user` (
  `api_user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` char(60) DEFAULT NULL,
  `api_key` char(40) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`api_user_id`),
  UNIQUE KEY `api_key` (`api_key`),
  UNIQUE KEY `email` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `api_user_ip` (
  `api_user_ip_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `api_user_id` int(10) unsigned NOT NULL,
  `ip` int(10) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`api_user_ip_id`),
  UNIQUE KEY `api_user_id` (`api_user_id`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `api_user_ip`
  ADD CONSTRAINT `api_user_ip_ibfk_1` FOREIGN KEY (`api_user_id`) REFERENCES `api_user` (`api_user_id`);
COMMIT;
