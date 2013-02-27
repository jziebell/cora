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
);

CREATE TABLE IF NOT EXISTS `api_user` (
  `api_user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name_first` varchar(255) NOT NULL,
  `name_last` varchar(255) NOT NULL,
  `api_key` char(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`api_user_id`)
);

CREATE TABLE IF NOT EXISTS `sample_crud_resource` (
  `sample_crud_resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `column_one` varchar(255) NOT NULL,
  `column_two` varchar(255) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sample_crud_resource_id`)
);

CREATE TABLE IF NOT EXISTS `sample_dictionary_resource` (
  `sample_dictionary_resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `column_one` varchar(255) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sample_dictionary_resource_id`)
);

#TODO: Create table session