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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=117 ;

CREATE TABLE IF NOT EXISTS `api_user` (
  `api_user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` char(60) DEFAULT NULL,
  `salt` char(32) DEFAULT NULL,
  `api_key` char(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`api_user_id`),
  UNIQUE KEY `api_key` (`api_key`),
  UNIQUE KEY `email` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=221 ;

CREATE TABLE IF NOT EXISTS `api_user_ip` (
  `api_user_ip_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `api_user_id` int(10) unsigned NOT NULL,
  `ip` int(10) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`api_user_ip_id`),
  UNIQUE KEY `api_user_id` (`api_user_id`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `sample_crud_resource` (
  `sample_crud_resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `column_one` varchar(255) NOT NULL,
  `column_two` varchar(255) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sample_crud_resource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

CREATE TABLE IF NOT EXISTS `sample_dictionary_resource` (
  `sample_dictionary_resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `column_one` varchar(255) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sample_dictionary_resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `session` (
  `session_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` char(128) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(10) unsigned NOT NULL,
  `last_used_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_used_by` int(10) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `key` (`session_key`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` char(60) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

ALTER TABLE `api_user_ip`
  ADD CONSTRAINT `api_user_ip_ibfk_1` FOREIGN KEY (`api_user_id`) REFERENCES `api_user` (`api_user_id`);
