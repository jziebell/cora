<?php

namespace cora;

/**
 * Workhorse for processing an API request. This has all of the core
 * functionality and settings. Here is a list of all the settings and their
 * default values. See documentation and set these values below.
 *
 * $database_host = 'localhost';
 * $database_username = 'cora';
 * $database_password = 'JbbSv5eMFaBhRZs8';
 * $database_name = 'cora';
 * $session_length = 28800;
 * $force_ssl = false;
 * $requests_per_minute = 30;
 * $allow_api_user_ip_filtering = false;
 * $api_map = array(...);
 *
 * @author Jon Ziebell
 */
final class cora {

  /* BEGIN SETTINGS */

  /**
   * Database host. Can be IP or hostname.
   * @var string
   */
  private static $database_host = 'localhost';

  /**
   * Username to connect to the database. As a general rule, this user should
   * only have access to the databases necessary for your application and should
   * only have select,insert, and update permissions. Cora uses 'deleted'
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
   * Session length in seconds. Cora does not create any cookies (you have to do
   * that), but will block all API requests for a session after it has been
   * inactive for this length of time. When setting the cookie from your
   * application, use time()+cora\cora::get_session_length() to get the cookie
   * expiration time. Example:
   *   86400 = 24 hours
   *   28800 = 8 hours
   *   14400 = 4 hours
   *   0 = On browser close. Note that the client cookie will expire on browser
   *     close but if someone took that cookie and altered the expiration time
   *     it would still be allowed on the server indefinitely.
   * @var int
   */
  private static $session_length = 28800;

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
  private static $allow_api_user_ip_filtering = false;

  /**
   * API functions are not exposed to the public by default. They must be added
   * to this array first. Also, I chose to explicitly list out the arguments for
   * each of the calls instead of using a generic $arguments parameter. This is
   * mostly preference but I believe it makes the code more readable.
   * @var array
   */
  private static $api_map = array(
    // Private functions require a valid session.
    'private' => array(
      'sample_crud_resource' => array(
        'select' => array('where_clause', 'columns'),
        'select_id' => array('where_clause', 'columns'),
        'get' => array('id', 'columns'),
        'insert' => array('attributes'),
        'update' => array('id', 'attributes'),
        'delete' => array('id'),
        'my_custom_function' => array('whatever', 'i', 'want')
      ),
      'sample_dictionary_resource' => array(
        'select' => array('where_clause', 'columns')
      )
    ),
    // Public functions do not require a valid session.
    'public' => array(
      'user' => array(
        'insert' => array('attributes'),
        'log_in' => array('username', 'password')
      ),
      'cora\api_user' => array(
        'insert' => array('attributes')
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
   * The API provided in the constructor.
   * @var string
   */
  private $api_key;

  /**
   * The resource provided in the constructor.
   * @var string
   */
  private $resource;

  /**
   * The method provided in the constructor.
   * @var string
   */
  private $method;

  /**
   * The arguments provided in the constructor. This is an associative array
   * that should basically match the signature of the method I'm trying to call.
   * @var array
   */
  private $arguments;

  /**
   * Whether or not this API request is public (does not require a valid
   * session) or private (does require a valid session).
   * @var string
   */
  private $request_type;

  /**
   * The full JSON-encoded response sent back to the requester.
   * @var string
   */
  private $response_body;

  /**
   * The response from the requested resource. If there was an exception this
   * value will remain null.
   * @var mixed
   */
  private $api_response;

  /**
   * Whether or not the API request was successful.
   * @var bool
   */
  private $success = false;

  /**
   * Database object.
   * @var database
   */
  private $database;

  /**
   * Session object.
   * @var session
   */
  private $session;

  /**
   * Save the request variables for use later on. If unset, they are defaulted
   * to null. Any of these values except for session_key being null will throw
   * an exception as soon as you try to process the request. The reason that
   * doesn't happen here is so that I can store exactly what was sent to me for
   * logging purposes.
   *
   * @param array $data Really just the $_POST array in a normal situation.
   *     Required keys are: api_key, resource, method, arguments. The
   *     session_key value is not required.
   */
  public function __construct($data) {
    $this->start_timestamp = microtime(true);
    $this->database = database::get_instance();
    $this->session = session::get_instance();

    if(isset($data['session_key'])) {
      $this->session->set_session_key($data['session_key']);
    }

    $this->api_key   = isset($data['api_key'])   ? $data['api_key']   : null;
    $this->resource  = isset($data['resource'])  ? $data['resource']  : null;
    $this->method    = isset($data['method'])    ? $data['method']    : null;
    $this->arguments = isset($data['arguments']) ? $data['arguments'] : null;
  }

  /**
   * Check to see if there were any obvious errors in the API request.
   *
   * @throws \Exception If the API key was not specified.
   * @throws \Exception If the resource was not specified.
   * @throws \Exception If the method was not specified.
   * @throws \Exception If a private method was called without a valid session.
   * @return null
   */
  public function check_request_for_errors() {
    if($this->api_key === null) {
      throw new \Exception('API Key is required.');
    }
    if($this->resource === null) {
      throw new \Exception('Resource is required.');
    }
    if($this->method === null) {
      throw new \Exception('Method is required.');
    }

    if($this->request_type === 'private' && $this->session->is_valid() === false) {
      throw new \Exception('A valid session is required to call ' .
        $this->resource . '->' . $this->method . '().');
    }

    // TODO: Make sure I sent a valid API key
  }

  /**
   * Loop over all of the public functions and see if this request is in there.
   * If so, set $this->request_type to 'public' otherwise default to 'private'.
   *
   * @return null
   */
  private function set_request_type() {
    $this->request_type = 'private';
    if(isset(self::$api_map['public'][$this->resource])) {
      if(isset(self::$api_map['public'][$this->resource][$this->method])) {
        $this->request_type = 'public';
      }
    }
  }

  /**
   * Execute the request. It is run through the rate limiter, checked for
   * errors, processed, then logged.
   *
   * If the rate limit is violated, return immediately; there's no need to do
   * anything else. Requests sent after the rate limit is reached are not logged
   * for performance reasons. Only a single select query will have been
   * performed. Same deal for SSL if it was required and not used and same deal
   * if the requested resource/method is unavailable.
   *
   * @return string The response JSON.
   */
  public function process_api_request() {
    // Rate limit before doing anything else.
    if($this->over_rate_limit()) {
      return json_encode(array(
        'success'=>false,
        'data'=>null,
        'error'=>'Rate limit threshold reached. Maximum number of ' .
          'requests is ' . $this->requests_per_minute . ' per minute.'
      ));
    }

    // Force SSL.
    if(self::$force_ssl === true) {
      if(empty($_SERVER['HTTPS'])) {
        return json_encode(array(
          'success'=>false,
          'data'=>null,
          'error'=>'You must send all requests to this API using SSL.'
        ));
      }
    }

    // Sets $this->request_type to 'public' or 'private'
    $this->set_request_type();

    // Throw exceptions if data was missing or incorrect.
    $this->check_request_for_errors();

    // Make sure requested resource/method is mapped.
    $resource_mapped = isset(
      self::$api_map[$this->request_type][$this->resource]
    );
    $method_mapped = isset(
      self::$api_map[$this->request_type][$this->resource][$this->method]
    );
    if($resource_mapped === false || $method_mapped === false) {
      return json_encode(array(
        'success'=>false,
        'data'=>null,
        'error'=>'Requested resource/method (' . $this->resource . '/' .
          $this->method . ') does not exist.'
      ));
    }

    $resource_instance = new $this->resource();
    $arguments = $this->get_arguments(
      self::$api_map[$this->request_type][$this->resource][$this->method]
    );
    $this->api_response = call_user_func_array(
      array($resource_instance, $this->method), $arguments
    );

    $this->success = true;
    $this->response_body = json_encode(array(
      'success'=>$this->success,
      'data'=>$this->api_response
    ));

    $this->log_request();

    return $this->response_body;
  }

  /**
   * Check to see if the request from the current IP address needs to be rate
   * limited. If $this->requests_per_minute is null then there is no rate
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
   * truncated to 128kb for sanity. Any field in the request with the key
   * 'password', regardless of the resource and method, is replaced with the
   * text '[REMOVED]' for security.
   *
   * @return null
   */
  private function log_request() {
    $response_time = microtime(true) - $this->start_timestamp;
    $response_body = substr(json_encode($this->response_body), 0, 131072);
    $response_has_error = !$this->success;

    $request_arguments = $this->arguments;
    if(isset($request_arguments['password'])) {
      $request_arguments['password'] = '[REMOVED]';
    }

    $api_log_resource = new api_log();
    $api_log_resource->insert(array(
      'request_api_key'       =>  $this->api_key,
      'request_resource'      =>  $this->resource,
      'request_method'        =>  $this->method,
      'request_arguments'     =>  json_encode($request_arguments),
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
  private function get_arguments($argument_keys) {
    $arguments = array();
    foreach($argument_keys as $argument_key) {
      if($this->arguments !== null && isset($this->arguments[$argument_key])) {
        $arguments[$argument_key] = $this->arguments[$argument_key];
      }
      else {
        $arguments[$argument_key] = null;
      }
    }
    return $arguments;
  }

  /**
   * Gets the session_length setting.
   *
   * @return int
   */
  public static function get_session_length() {
    return self::$session_length;
  }

  /**
   * Gets the force_ssl setting.
   *
   * @return bool
   */
  public static function get_force_ssl() {
    return self::$force_ssl;
  }

  /**
   * Gets the database_host setting.
   *
   * @return string
   */
  public static function get_database_host() {
    return self::$database_host;
  }

  /**
   * Gets the database_name setting.
   *
   * @return string
   */
  public static function get_database_name() {
    return self::$database_name;
  }

  /**
   * Gets the database_username setting.
   *
   * @return string
   */
  public static function get_database_username() {
    return self::$database_username;
  }

  /**
   * Gets the database_password setting.
   *
   * @return string
   */
  public static function get_database_password() {
    return self::$database_password;
  }

  /**
   * Override of the default PHP error handler. This also doubles as the
   * shutdown function. If there was no error on shutdown then it just won't do
   * anything. If there was an error that somehow didn't get caught, then this
   * will find it with error_get_last and return appropriately.
   *
   * @param int $error_number The error number from PHP.
   * @param string $error The error message.
   * @param string $error_file The file the error happend in.
   * @param int $error_line The line of the file the error happened on.
   * @return string The JSON response with the error details.
   */
  public function error_handler(
    $error_number, $error, $error_file, $error_line
  ) {
    if($error_number === null) {
      $error = error_get_last();
      if($error !== null) {
        die($this->handle_exception(
          $error['message'],
          $error['type'],
          $error['file'],
          $error['line'],
          debug_backtrace()
        ));
      }
      else {
        // Will land here if this function is executed on shutdown and there was
        // no error.
      }
    } else {
      die($this->handle_exception(
        $error,
        $error_number,
        $error_file,
        $error_line,
        debug_backtrace()
      ));
    }
  }

  /**
   * Handle all exceptions by generating a JSON response with the error details.
   *
   * @param string $error The error message.
   * @param mixed $error_number The error number from PHP.
   * @param string $error_file The file the error happend in.
   * @param int $error_line The line of the file the error happened on.
   * @param array $error_trace The stack trace for the error.
   * @return string The JSON response with the error details.
   */
  public function handle_exception(
    $error, $error_number, $error_file, $error_line, $error_trace
  ) {
    $this->response_body = json_encode(array(
      'success'=>false,
      'data'=>null,
      'error'=>$error,
      'error_number'=>$error_number,
      'error_file'=>$error_file,
      'error_line'=>$error_line,
      'error_trace'=>$error_trace
    ));

    $this->success = false;
    $this->log_request();

    return $this->response_body;
  }

}

?>