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
	 * and disallow overriding the timestamp. This is essentially the same thing
	 * as create, but I run the query myself so I can exclude it from being
	 * included in the query statistics. For single requests it doesn't actually
	 * matter, but for batch requests I have to prevent that since doing one log
	 * would affect the statistics for the next log.
	 *
	 * @param array $attributes The attributes to insert.
	 * @return int The ID of the inserted row.
	 */
	public function log($attributes) {
		$attributes['request_ip'] = ip2long($_SERVER['REMOTE_ADDR']);
		unset($attributes['request_timestamp']);

		$query = '
			insert into api_log(
	      `request_api_key`,
	      `request_ip`,
	      `request_resource`,
	      `request_method`,
	      `request_arguments`,
	      `response_has_error`,
	      `response_body`,
	      `response_time`,
	      `response_query_count`,
	      `response_query_time`
			)
			values(
	      ' . $this->database->escape($attributes['request_api_key']) . ',
	      ' . $this->database->escape($attributes['request_ip']) . ',
	      ' . $this->database->escape($attributes['request_resource']) . ',
	      ' . $this->database->escape($attributes['request_method']) . ',
	      ' . $this->database->escape($attributes['request_arguments']) . ',
	      ' . $this->database->escape($attributes['response_has_error']) . ',
	      ' . $this->database->escape($attributes['response_body']) . ',
	      ' . $this->database->escape($attributes['response_time']) . ',
	      ' . $this->database->escape($attributes['response_query_count']) . ',
	      ' . $this->database->escape($attributes['response_query_time']) . '
			)
		';

		// See function documentation. Exclude this from the query statistics.
		return $this->database->query($query, false);
	}

	/**
	 * Get the number of requests since a given timestamp for a given IP
	 * address. Handy for rate limiting.
	 *
	 * Important: Do not expose this function publicly and use it there. It is
	 * excluded from log statistics because it is used only for rate limiting.
	 * Exposing this would cause any API calls to it to have inaccurate query
	 * log data.
	 *
	 * @param string $request_ip The IP to look at.
	 * @param int $timestamp The timestamp to check from.
	 * @return int The number of requests on or after $timestamp.
	 */
	public function get_number_requests_since($request_ip, $timestamp) {
		$request_ip_escaped = $this->database->escape(ip2long($request_ip));
		$timestamp_escaped = $this->database->escape($timestamp);
		$query = '
			select
				count(*) as number_requests_since
			from
				api_log
			where
				    request_ip = ' . $request_ip_escaped . '
				and request_timestamp >= from_unixtime(' . $timestamp_escaped . ')
		';

		// Getting the number of requests since a certain date is considered
		// overhead since it's only used for rate limiting. See "Important" note in
		// documentation.
		$result = $this->database->query($query, false);
		$row = $result->fetch_assoc();
		return $row['number_requests_since'];
	}

}
