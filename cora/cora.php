<?php

namespace cora;

/**
 * Workhorse for processing an API request. This has all of the core
 * functionality and settings. Here is a list of all the settings. See
 * documentation and set these values below.
 *
 * $debug
 * $database_host
 * $database_username
 * $database_password
 * $database_name
 * $cookie_domain
 * $force_ssl
 * $requests_per_minute
 * $enable_api_user_creation
 * $enable_api_user_ip_filtering
 * $custom_map
 *
 * @author Jon Ziebell
 */
final class cora {

  /* BEGIN SETTINGS */

  /**
   * Whether or not debugging is enabled. Debugging will produce additional
   * output in the API response, including data->error_file, data->error_line,
   * data->error_trace, data->error_extra_info, and the original request.
   * @var bool
   */
  private static $debug = true;

  /**
   * Database host. Can be IP or hostname.
   * @var string
   */
  private static $database_host = 'localhost';

  /**
   * Username to connect to the database. As a general rule, this user should
   * only have access to the databases necessary for your application and should
   * only have select, insert, and update permissions. Cora uses 'deleted'
   * columns on all resources to indicate when that row has been removed. This
   * enables a more secure application that prevents an attacker from deleting
   * any data from your system should they successfully launch an attack of some
   * sort (like SQL injection).
   *
   * Here's an example user creation query:
   *   create user 'username'@'localhost' identified by 'password';
   *   grant select,insert,update on database_name.* to 'username'@'localhost';
   * @var string
   */
  private static $database_username = 'cora';

  /**
   * Password to connect to the database.
   * @var string
   */
  private static $database_password = 'JbbSv5eMFaBhRZs8';

  /**
   * Default database name. If you have more than one database in your
   * application, you can set this to null and call $database->select_db() as
   * necessary.
   * @var string
   */
  private static $database_name = 'cora';

  /**
   * In general, set this to the domain your sessions should be active on and
   * leave out the www prefix. For example, if your application is at
   * "www.myapp.com", this value should be "myapp.com". If your application is
   * at "myapp.myhomepage.com", then this value should be
   * "myapp.myhomepage.com". You can set this value to null and it will work,
   * but sessions will not persist if a user switches from "www.myapp.com" to
   * "myapp.com".
   *
   * From http://php.net/manual/en/function.setcookie.php
   * The domain that the cookie is available to. Setting the domain to
   * "www.example.com" will make the cookie available in the www subdomain and
   * higher subdomains. Cookies available to a lower domain, such as
   * "example.com" will be available to higher subdomains, such as
   * "www.example.com".
   * @var string
   */
  private static $cookie_domain = null;

  /**
   * Whether or not to force all requests to use SSL. If set to true, an error
   * will be returned if SSL is not used. This should generally be set to true
   * unless you're in a development environment.
   *
   * Cora does not offer any sort of protection against someone altering (man in
   * the middle) or resending (replay) the request except via SSL. This would
   * require doing cumbersome things like sending a timestamp or sending a hash
   * calculated from some secret. So, even if you don't care if someone sees
   * your data, the API is not secure except through SSL.
   * @var bool
   */
  private static $force_ssl = false;

  /**
   * The number of requests allowed from a given IP address per minute. Past
   * this point all requests will return an error (without being logged). Set
   * to null to disable rate limiting entirely. Rate limit enforcement is
   * recommended and takes one additional database query per request when
   * enabled.
   *
   * Rate limiting is done with a "bucket". Only requests made in the past 60
   * seconds count towards the total. Therefore there is no set "lockout"
   * period; the limit will just be lifted once one the number of requests in
   * the past minute is less than the value here.
   * @var int
   */
  private static $requests_per_minute = 30;

  /**
   * Whether or not API user creation is enabled. If set to true, the required
   * API methods to create new API users will be opened up. There are also a
   * handful of API methods that are always available to provide things like
   * statistics for API users. These can only be called when that API user is
   * logged in.
   * @var bool
   */
  private static $enable_api_user_creation = true;

  /**
   * If set to true, allow API users to specify one or more IP addresses that
   * their API key can be used from. This setting doesn't actually matter that
   * much since there are no limitations (rate limiting or quotas) on API
   * usage by API key; it's all done by IP address. Regardless, this feature
   * is available if the user wants to ensure that nobody else is using their
   * key.
   *
   * Enabling this feature requires an additional database query per request.
   * @var bool
   */
  private static $enable_api_user_ip_filtering = false;

  /**
   * API methods are all private by default. Add them here to expose them. To
   * require a valid session (user logged in), use the 'session' key. Methods
   * added to the 'non_session' key can be called without being logged in.
   * @var array
   */
  private static $custom_map = array(
    'session' => array(
      'test_crud' => array(
        'read' => array('where_clause', 'columns')
      )
    ),
    'non_session' => array(
      'test_user' => array(
        'log_in' => array('username', 'password', 'remember_me')
      )
    )
  );

  /* END SETTINGS */

  /**
   * The timestamp when processing of the API request started.
   * @var int
   */
  private $start_timestamp;

  /**
   * The original request parameters.
   * @var array
   */
  private $request;

  /**
   * The API provided in the constructor.
   * @var string
   */
  // private $api_key;

  /**
   * The resource provided in the constructor.
   * @var string
   */
  // private $resource;

  /**
   * The method provided in the constructor.
   * @var string
   */
  // private $method;

  /**
   * The arguments provided in the constructor. This is an associative array
   * that should basically match the signature of the method I'm trying to call.
   * @var array
   */
  // private $arguments;

  /**
   * The batch provided in the constructor.
   * @var string
   */
  // private $batch;

  /**
   * Whether or not this API request is public (does not require a valid
   * session) or private (does require a valid session).
   * @var string
   */
  // private $request_type;

  /**
   * The map ('custom' or 'cora') that the request is part of. Requests from the
   * custom map are ones the user has defined. Requests from the cora map are
   * specific to Cora.
   * @var string
   */
  // private $request_map;

  /**
   * The full JSON-encoded response sent back to the requester.
   * @var string
   */
  private $response_body;

  /**
   * The error code, if any. This is set in the exception handler and used in
   * the shutdown handler to determine if the response should be logged.
   */
  private $response_error_code = null;

  /**
   * The response from the requested resource. If there was an exception this
   * value will remain null.
   * @var mixed
   */
  private $api_response;

  /**
   * Extra information for errors. For example, the database class puts
   * additional information into this variable if the query fails. The
   * error_message remains the same but has this additional data to help the
   * developer (if debug is enabled).
   *
   * I'm not 100% sure static is the most intuitive way to use this. I'd prefer
   * it not be static but then any class that wants to set this extra info has
   * to have the cora object passed to it or else cora needs to be a singleton.
   * @var mixed
   */
  private static $error_extra_info = null;

  /**
   * Database object.
   * @var database
   */
  private $database;

  /**
   * This is a hardcoded list of API methods specific to Cora (mostly for the
   * creation/management of API users).
   * @var array
   */
  private $cora_map = array(
    'session' => array(
      'cora\api_user' => array(
        'log_out' => array(),
        'regenerate_api_key' => array(),
        'get_statistics' => array(),
        'delete' => array()
      )
    ),
    'non_session' => array(
      'cora\api_user' => array(
        'log_in' => array('username', 'password', 'remember_me'),
        'create' => array('attributes')
      )
    )
  );

  /**
   * Save the request variables for use later on. If unset, they are defaulted
   * to null. Any of these values being null will throw an exception as soon as
   * you try to process the request. The reason that doesn't happen here is so
   * that I can store exactly what was sent to me for logging purposes.
   *
   * @param array $request Really just the $_REQUEST array in a normal
   *     situation. Required keys are: api_key, resource, method, arguments.
   */
  public function __construct() {
    $this->start_timestamp = microtime(true);
    $this->database = database::get_instance();
    $this->database->reset_statistics();
  }

  /**
   * Check to see if there were any obvious errors in the API request.
   *
   * @throws \Exception If the API key was not specified.
   * @throws \Exception If the resource was not specified.
   * @throws \Exception If the method was not specified.
   * @throws \Exception If the specified API key was invalid.
   * @throws \Exception If a private method was called without a valid session.
   * @return null
   */
  private function check_request_for_errors($request, $request_map, $request_type) {
    if($request['api_key'] === null) {
      throw new \Exception('API Key is required.', 1000);
    }
    if($request['resource'] === null) {
      throw new \Exception('Resource is required.', 1001);
    }
    if($request['method'] === null) {
      throw new \Exception('Method is required.', 1002);
    }

    // Make sure the API key that was sent is valid.
    $api_user_resource = new api_user();
    // $api_user = $api_user_resource->read(array('api_key' => $request['api_key']));
    // if(count($api_user) !== 1) {
    if($api_user_resource->is_valid_api_key($request['api_key']) === false) {
      throw new \Exception('Invalid API key.', 1003);
    }

    // Get the appropriate session object. This has to be done always because
    // the session must be available even for non-session requests in the case
    // of something like logging in.
    switch($request_map) {
      case 'custom':
        $session = api_session::get_instance();
      break;
      case 'cora':
        $session = api_user_session::get_instance();
      break;
    }

    // If the request requires a session, make sure it's valid.
    if($request_type === 'session') {
      if($request_map === 'custom') {
        $session_is_valid = $session->touch();
        if($session_is_valid === false) {
          throw new \Exception('API session is expired.', 1004);
        }
      }
      else if($request_map === 'cora') {
        $session_is_valid = $session->touch();
        if($session_is_valid === false) {
          throw new \Exception('API user session is expired.', 1010);
        }
      }
    }
  }

  /**
   * Returns 'custom' or 'cora' depending on where the API method is located
   * at. Custom methods will override Cora methods, although there should
   * never be any overlap in these anyways.
   *
   * @throws \Exception If the resource was not found in either map.
   * @return string The map.
   */
  private function get_request_map($request) {
    if(isset(self::$custom_map['session'][$request['resource']]) || isset(self::$custom_map['non_session'][$request['resource']])) {
      return 'custom';
    }
    else if(isset($this->cora_map['session'][$request['resource']]) || isset($this->cora_map['non_session'][$request['resource']])) {
      return 'cora';
    }
    else {
      throw new \Exception('Requested resource is not mapped.', 1007);
    }
  }

  /**
   * Returns 'session' or 'non_session' depending on where the API method is
   * located at. Session methods require a valid session in order to execute.
   *
   * @throws \Exception If the method was not found in the map.
   * @return string The type.
   */
  private function get_request_type($request, $request_map) {
    if($request_map === 'cora') {
      $map = $this->cora_map;
    }
    else if($request_map === 'custom') {
      $map = self::$custom_map;
    }

    if(isset($map['session'][$request['resource']][$request['method']])) {
      return 'session';
    }
    else if(isset($map['non_session'][$request['resource']][$request['method']])) {
      return 'non_session';
    }
    else {
      throw new \Exception('Requested method is not mapped.', 1008);
    }
  }

  /**
   * Execute the request. It is run through the rate limiter, checked for
   * errors, then processed. Requests sent after the rate limit is reached are
   * not logged.
   *
   * @throws \Exception If the rate limit threshhold is reached.
   * @throws \Exception If SSL is required but not used.
   * @throws \Exception If the requested method does not exist.
   * @return string The response JSON.
   */
  public function process_request($request) {
    // This is necessary in order for the shutdown handler/log function to have
    // access to this data, but it's not used anywhere else.
    $this->request = $request;

    if($this->over_rate_limit() === true) {
      throw new \Exception('Rate limit reached.', 1005);
    }

    // Force SSL.
    if(self::$force_ssl === true && empty($_SERVER['HTTPS'])) {
      throw new \Exception('Request must be sent over HTTPS.', 1006);
    }

    $request['api_key'] = $request['api_key'];

    // Sets $request_type to 'public' or 'private'
    $request_map = $this->get_request_map($request);
    $request_type = $this->get_request_type($request, $request_map);

    // Throw exceptions if data was missing or incorrect. TODO: API key is not
    // set in each individual request...that's on the parent. Need to adjust
    // for this and make sure it works in all cases.
    $this->check_request_for_errors($request, $request_map, $request_type);

    // If the resource doesn't exist, spl_autoload_register() will throw a fatal
    // error. The shutdown handler will "catch" it. It is not possible to catch
    // exceptions directly from the autoloader using try/catch.
    $resource_instance = new $request['resource']();

    // If the method doesn't exist
    if(method_exists($resource_instance, $request['method']) === false) {
      throw new \Exception('Method does not exist.', 1009);
    }

    if($request_map === 'custom') {
      $arguments = $this->get_arguments(
        $request,
        self::$custom_map[$request_type][$request['resource']][$request['method']]
      );
    }
    else if($request_map === 'cora') {
      $arguments = $this->get_arguments(
        $request,
        $this->cora_map[$request_type][$request['resource']][$request['method']]
      );
    }

    $this->api_response = call_user_func_array(
      array($resource_instance, $request['method']), $arguments
    );

    if(self::$debug === true) {
      $this->response_body = json_encode(array(
        'success' => true,
        'data' => $this->api_response,
        'request' => $request
      ));
    }
    else {
      $this->response_body = json_encode(array(
        'success' => true,
        'data' => $this->api_response
      ));
    }

    return $this->response_body;
  }

  /**
   * Check to see if the request from the current IP address needs to be rate
   * limited. If $requests_per_minute is null then there is no rate
   * limiting.
   *
   * @return bool If this request puts us over the rate threshold.
   */
  private function over_rate_limit() {
    if(self::$requests_per_minute === null) {
      return false;
    }

    $api_log_resource = new api_log();
    $requests_this_minute = $api_log_resource->get_number_requests_since(
      $_SERVER['REMOTE_ADDR'],
      time()-60
    );
    return ($requests_this_minute >= self::$requests_per_minute);
  }

  /**
   * Log the request and response to the database. The logged response is
   * truncated to 128kb for sanity.
   *
   * @param array $request The request to log. If not provided, uses the original request.
   * @return null
   */
  private function log_request($request) {
    $response_time = microtime(true) - $this->start_timestamp;
    $response_body = substr($this->response_body, 0, 131072);
    $response_has_error = $this->response_error_code !== null;

    if($request['arguments'] === null) {
      $request_arguments = null;
    }
    else {
      $request_arguments = json_encode($request['arguments']);
    }

    // Eh, so here's the deal. I need this log to happen in the shutdown handler
    // so that it is guaranteed to run even in the case of an exception or
    // something. Also, I'm registering multiple shutdown functions which
    // probably isn't good and causes the API calls to log in reverse order.

    $api_log_resource = new api_log();
    $api_log_resource->log(array(
      'request_api_key'       =>  $request['api_key'],
      'request_resource'      =>  $request['resource'],
      'request_method'        =>  $request['method'],
      'request_arguments'     =>  $request_arguments,
      'response_has_error'    =>  $response_has_error,
      'response_body'         =>  $response_body,
      'response_time'         =>  $response_time,
      'response_query_count'  =>  $this->database->get_query_count(),
      'response_query_time'   =>  $this->database->get_query_time()
    ));
  }

  /**
   * Fetches a list of arguments when passed an array of keys. Since the
   * arguments are passed from JS to PHP in JSON, I don't need to cast any of
   * the values as the data types are preserved.
   *
   * @param array $argument_keys The keys to get.
   * @return array The requested arguments. If one is not set, null is returned.
   */
  private function get_arguments($request, $argument_keys) {
    $arguments = array();
    foreach($argument_keys as $argument_key) {
      if($request['arguments'] !== null && isset($request['arguments'][$argument_key])) {
        // $arguments[$argument_key] = $request['arguments'][$argument_key];
        $arguments[] = $request['arguments'][$argument_key];
      }
      else {
        return $arguments;
        // $arguments[$argument_key] = null;
      }
    }
    return $arguments;
  }

  /**
   * Sets error_extra_info.
   *
   * @return null
   */
  public static function set_error_extra_info($error_extra_info) {
    self::$error_extra_info = $error_extra_info;
  }

  /**
   * Get error_extra_info.
   *
   * @return mixed
   */
  public static function get_error_extra_info() {
    return $this->error_extra_info;
  }

  /**
   * Get a setting. All of the settings are private static.
   * @param string $setting The setting name
   * @return mixed The setting
   */
  public static function get_setting($setting) {
    return self::$$setting;
  }

  /**
   * Override of the default PHP error handler. Grabs the error info and sends
   * it to the exception handler which returns a JSON response.
   *
   * @param int $error_code The error number from PHP.
   * @param string $error_message The error message.
   * @param string $error_file The file the error happend in.
   * @param int $error_line The line of the file the error happened on.
   * @return string The JSON response with the error details.
   */
  public function error_handler($error_code, $error_message, $error_file, $error_line) {
    die($this->generate_error_response(
      $error_message,
      $error_code,
      $error_file,
      $error_line,
      debug_backtrace()
    ));
  }

  /**
   * Override of the default PHP exception handler. All unhandled exceptions go
   * here.
   *
   * @param Exception $e The exception.
   * @return null
   */
  public function exception_handler($e) {
    die($this->generate_error_response(
      $e->getMessage(),
      $e->getCode(),
      $e->getFile(),
      $e->getLine(),
      $e->getTrace()
    ));
  }

  /**
   * Executes when the script finishes. If there was an error that somehow
   * didn't get caught, then this will find it with error_get_last and return
   * appropriately. Doesn't do anything if an exception was thrown due to the
   * rate limit.
   *
   * @return null
   */
  public function shutdown_handler() {
    if($this->response_error_code !== 1005) { // 1005 = Rate limit reached.
      $this->log_request($this->request);
      $error = error_get_last();
      if($error !== null) {
        die($this->generate_error_response(
          $error['message'],
          $error['type'],
          $error['file'],
          $error['line'],
          debug_backtrace()
        ));
      }
    }
  }

  /**
   * Handle all exceptions by generating a JSON response with the error details.
   * If debugging is enabled, a bunch of other information is sent back to help
   * out.
   *
   * @param string $error_message The error message.
   * @param mixed $error_code The supplied error code.
   * @param string $error_file The file the error happened in.
   * @param int $error_line The line of the file the error happened on.
   * @param array $error_trace The stack trace for the error.
   * @return string The JSON response with the error details.
   */
  public function generate_error_response($error_message, $error_code, $error_file, $error_line, $error_trace) {
    $this->response_error_code = $error_code;

    if(self::$debug === true) {
      $this->response_body = json_encode(array(
        'success' => false,
        'data' => array(
          'error_message' => $error_message,
          'error_code' => $error_code,
          'error_file' => $error_file,
          'error_line' => $error_line,
          'error_trace' => $error_trace,
          'error_extra_info' => self::$error_extra_info
        ),
        'request' => $this->request
      ));
    }
    else {
      $this->response_body = json_encode(array(
        'success' => false,
        'data' => array(
          'error_message' => $error_message,
          'error_code' => $error_code
        )
      ));
    }
    return $this->response_body;
  }

}
