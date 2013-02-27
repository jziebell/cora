<?php

namespace cora;

/**
 * Stores a log of API requests and responses. Intended usage is to process the
 * request to the end (exception or not) and then log it.
 *
 * @author Jon Ziebell
 */
class api_log extends crud {

	/**
	 * Insert an item into the api_log resource. Force the IP to the request IP
	 * and disallow overriding the timestamp.
	 *
	 * @param array $attributes
	 * @return int
	 */
	public function insert($attributes) {
		$attributes['request_ip'] = ip2long($_SERVER['REMOTE_ADDR']);
		unset($attributes['request_timestamp']);
		return parent::_insert($attributes);
	}

	/**
	 * Get the number of requests since a given timestamp for a given IP address.
	 * Handy for rate limiting.
	 *
	 * @param string $request_ip The IP to look at.
	 * @param int $timestamp The timestamp to check from.
	 * @return int The number of requests.
	 */
	public function get_number_requests_since($request_ip, $timestamp) {
		$request_ip_escaped = $this->database->escape(ip2long($request_ip));
		$timestamp_escaped = $this->database->escape($timestamp);
		$query = "
			select
				count(*) as number_requests_since
			from
				api_log
			where
				request_ip = $request_ip_escaped and
				request_timestamp >= from_unixtime($timestamp_escaped)
		";
		$result = $this->database->query($query);
		$row = $result->fetch_assoc();
		return $row['number_requests_since'];
	}

}

?>