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
 * $batch_limit
 * $enable_api_user_creation
 * $enable_api_user_ip_filtering
 * $custom_map
 *
 * @author Jon Ziebell
 */
final class cora {

  private static $settings = array(
     // Whether or not debugging is enabled. Debugging will produce additional
     // output in the API response, including data->error_file,
     // data->error_line, data->error_trace, data->error_extra_info, and the
     // original request.
    'debug' => true,

    // Database host. Can be IP or hostname.
    'database_host' => 'localhost',

    // Username to connect to the database. As a general rule, this user should
    // only have access to the databases necessary for your application and
    // should only have select, insert, and update permissions. Cora uses
    // 'deleted' columns on all resources to indicate when that row has been
    // removed. This enables a more secure application that prevents an attacker
    // from deleting any data from your system should they successfully launch
    // an attack of some sort (like SQL injection).
    'database_username' => 'cora',

    // Password to connect to the database.
    'database_password' => 'JbbSv5eMFaBhRZs8',

    // Default database name. If you have more than one database in your
    // application, you can set this to null and call $database->select_db() as
    // necessary.
    'database_name' => 'cora',

    // In general, set this to the domain your sessions should be active on and
    // leave out the www prefix. For example, if your application is at
    // "www.myapp.com", this value should be "myapp.com". If your application is
    // at "myapp.myhomepage.com", then this value should be
    // "myapp.myhomepage.com". You can set this value to null and it will work,
    // but sessions will not persist if a user switches from "www.myapp.com" to
    // "myapp.com". TODO: Figure out why setting this to null works and if it's
    // recommended.
   
    // From http://php.net/manual/en/function.setcookie.php The domain that the
    // cookie is available to. Setting the domain to "www.example.com" will make
    // the cookie available in the www subdomain and higher subdomains. Cookies
    // available to a lower domain, such as "example.com" will be available to
    // higher subdomains, such as "www.example.com".
    'cookie_domain' => null,

    // Whether or not to force all requests to use SSL. If set to true, an error
    // will be returned if SSL is not used. This should generally be set to true
    // unless you're in a development environment.
    //
    // Cora does not offer any sort of protection against someone altering (man
    // in the middle) or resending (replay) the request except via SSL. This
    // would require doing cumbersome things like sending a timestamp or sending
    // a hash calculated from some secret. So, even if you don't care if someone
    // sees your data, the API is not secure except through SSL.
    'force_ssl' => false,

    // The number of requests allowed from a given IP address per minute. Past
    // this point all requests will return an error (without being logged). Set
    // to null to disable rate limiting entirely. Rate limit enforcement is
    // recommended and takes one additional database query per request when
    // enabled.
    //
    // Rate limiting is done with a "bucket". Only requests made in the past 60
    // seconds count towards the total. Therefore there is no set "lockout"
    // period; the limit will just be lifted once one the number of requests in
    // the past minute is less than the value here.
    'requests_per_minute' => 30,

    // The limit to the number of requests that can be batched together. For no
    // limit, set to null...although that's not recommended as someone could
    // malaciously send a lot of requests. While rate limiting is technically
    // done per call, it's possible to break that limit in a single http request
    // by batching calls together since a single batched API call is guaranteed
    // not to fail in the middle due to rate limits. Making this unlimited isn't
    // a great idea because someone could do a one-time gigantic batch request
    // and bog down the API.
    'batch_limit' => 10,

    // Whether or not API user creation is enabled. If set to true, the required
    // API methods to create new API users will be opened up. There are also a
    // handful of API methods that are always available to provide things like
    // statistics for API users. These can only be called when that API user is
    // logged in.
    'enable_api_user_creation' => true,

    // If set to true, allow API users to specify one or more IP addresses that
    // their API key can be used from. This setting doesn't actually matter that
    // much since there are no limitations (rate limiting or quotas) on API
    // usage by API key; it's all done by IP address. Regardless, this feature
    // is available if the user wants to ensure that nobody else is using their
    // key.
    //
    // Enabling this feature requires an additional database query per request.
    'enable_api_user_ip_filtering' => false,
   
    // API methods are all private by default. Add them here to expose them. To
    // require a valid session when making an API call (user logged in), put
    // your call in the 'session' key. Methods added to the 'non_session' key
    // can be called without being logged in.
    'custom_map' => array(
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
    )
  );

  //
  //
  //-----------------------------------------
  //             End Settings
  // Do not modify anything past this point
  // ----------------------------------------
  // 
  // 

  /**
   * The timestamp when processing of the API request started.
   * @var int
   */
  private $start_timestamp;

  /**
   * The original request passed to this object, usually $_REQUEST. Stored
   * right away so logging and error functions have access to it.
   * @var array
   */
  private $request;

  /**
   * A list of all of the API calls extracted from the request. This is stored
   * so that logging and error functions have access to it.
   * @var array
   */
  private $api_calls;

  /**
   * An array of the API responses. For single API calls, count() == 1, for
   * batch calls there will be one row per call.
   * @var array
   */
  private $response_data = array();

  /**
   * The actual response in array form. It is stored here so the shutdown
   * handler has access to it.
   * @var array
   */
  private $response;

  /**
   * This is necessary because of the shutdown handler. According to the PHP
   * documentation and various bug reports, when the shutdown function
   * executes the current working directory changes back to root.
   * https://bugs.php.net/bug.php?id=36529. This is cool and all but it breaks
   * the autoloader. My solution for this is to just change the working
   * directory back to what it was when the script originally ran.
   *
   * Obviuosly I could hardcode this but then users would have to configure
   * the cwd when installing Cora. This handles it automatically and seems to
   * work just fine. Note that if the class that the autoloader needs is
   * already loaded, the shutdown handler won't break. So it's usually not a
   * problem but this is a good thing to fix.
   * @var string
   */
  private $current_working_directory;

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
   * A list of the response times for each API call. This does not reflect the
   * response time for the entire request, nor does it include the time it
   * took for overhead like rate limit checking.
   * @var array
   */
  private $response_times = array();

  /**
   * A list of the query counts for each API call. This does not reflect the
   * query count for the entire request, nor does it include the queries for
   * overhead like rate limit checking.
   * @var array
   */
  private $response_query_counts = array();

  /**
   * A list of the query times for each API call. This does not reflect the
   * query time for the entire request, nor does it include the times for
   * overhead like rate limit checking.
   * @var array
   */  
  private $response_query_times = array();

  /**
   * This stores the currently executing API call. If that API call were to
   * fail, I need to know which one I was running in order to propery log the
   * error.
   * @var array
   */
  private $current_api_call = null;

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

    // See class variable documentation for reasoning.
    $this->current_working_directory = getcwd();
  }

  /**
   * Execute the request. It is run through the rate limiter, checked for
   * errors, then processed. Requests sent after the rate limit is reached are
   * not logged.
   *
   * @param array $request Basically just $_REQUEST or a slight mashup of it
   * for batch requests.
   * @throws \Exception If the rate limit threshhold is reached.
   * @throws \Exception If SSL is required but not used.
   * @throws \Exception If the requested method does not exist.
   * @throws \Exception If this is a batch request and the batch data is not valid JSON
   * @throws \Exception If this is a batch request and it exceeds the maximum number of api calls allowed in one batch.
   * @return null
   */
  public function process_request($request) {
    // This is necessary in order for the shutdown handler/log function to have
    // access to this data, but it's not used anywhere else.
    $this->request = $request;

    // A couple quick error checks
    if($this->is_over_rate_limit() === true) {
      throw new \Exception('Rate limit reached.', 1005);
    }
    if(self::get_setting('force_ssl') === true && empty($_SERVER['HTTPS'])) {
      throw new \Exception('Request must be sent over HTTPS.', 1006);
    }

    // Build a list of API calls. For a single request, it's just the request.
    // For batch requests, add each item in the batch parameter to this array.
    $this->api_calls = array();
    if(isset($request['batch']) === true) {
      $batch = json_decode($request['batch'], true);
      if($batch === false) {
        throw new \Exception('Batch is not valid JSON.', 1012);
      }
      $batch_limit = self::get_setting('batch_limit');
      if(count($batch) > $batch_limit) {
        throw new \Exception('Batch limit exceeded.', 1013);
      }
      foreach($batch as $api_call) {
        // Need to attach the API key onto each api_call
        if(isset($request['api_key'])) {
          $api_call['api_key'] = $request['api_key'];
        }
        $this->api_calls[] = $api_call;
      }
    }
    else {
      $this->api_calls[] = $request;
    }

    // Process each request.
    foreach($this->api_calls as $api_call) {
      // Store the currently running API call for tracking if an error occurs.
      $this->current_api_call = $api_call;

      // Sets $request_type to 'public' or 'private'
      $request_map = $this->get_request_map($api_call);
      $request_type = $this->get_request_type($api_call, $request_map);

      // Throw exceptions if data was missing or incorrect.
      $this->check_api_call_for_errors($api_call, $request_map, $request_type);

      // If the resource doesn't exist, spl_autoload_register() will throw a fatal
      // error. The shutdown handler will "catch" it. It is not possible to catch
      // exceptions directly from the autoloader using try/catch.
      $resource_instance = new $api_call['resource']();

      // If the method doesn't exist
      if(method_exists($resource_instance, $api_call['method']) === false) {
        throw new \Exception('Method does not exist.', 1009);
      }

      if($request_map === 'custom') {
        $custom_map = self::get_setting('custom_map');
        $arguments = $this->get_arguments(
          $api_call,
          $custom_map[$request_type][$api_call['resource']][$api_call['method']]
        );
      }
      else if($request_map === 'cora') {
        $arguments = $this->get_arguments(
          $api_call,
          $this->cora_map[$request_type][$api_call['resource']][$api_call['method']]
        );
      }

      // Process the request and save some statistics.
      $start_time = microtime(true);
      $start_query_count = $this->database->get_query_count();
      $start_query_time = $this->database->get_query_time();
      $this->response_data[] = call_user_func_array(
        array($resource_instance, $api_call['method']), $arguments
      );
      $this->response_times[] = (microtime(true) - $start_time);
      $this->response_query_counts[] = $this->database->get_query_count() - $start_query_count;
      $this->response_query_times[] = $this->database->get_query_time() - $start_query_time;
    }
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
  private function check_api_call_for_errors($request, $request_map, $request_type) {
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
    $custom_map = self::get_setting('custom_map');
    if(isset($custom_map['session'][$request['resource']])
    || isset($custom_map['non_session'][$request['resource']])) {
      return 'custom';
    }
    else if(isset($this->cora_map['session'][$request['resource']])
    || isset($this->cora_map['non_session'][$request['resource']])) {
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
      $map = self::get_setting('custom_map');
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
   * Check to see if the request from the current IP address needs to be rate
   * limited. If $requests_per_minute is null then there is no rate
   * limiting.
   *
   * @return bool If this request puts us over the rate threshold.
   */
  private function is_over_rate_limit() {
    $requests_per_minute = self::get_setting('requests_per_minute');
    if($requests_per_minute === null) {
      return false;
    }
    $api_log_resource = new api_log();
    $requests_this_minute = $api_log_resource->get_number_requests_since(
      $_SERVER['REMOTE_ADDR'],
      time() - 60
    );
    return ($requests_this_minute >= $requests_per_minute);
  }

  /**
   * Fetches a list of arguments when passed an array of keys. Since the
   * arguments are passed from JS to PHP in JSON, I don't need to cast any of
   * the values as the data types are preserved. Since the argument order from
   * the client doesn't matter, this makes sure that the arguments are placed
   * in the correct order for calling the function.
   *
   * @param array $argument_keys The keys to get.
   * @throws \Exception If the arguments in the request were not valid JSON.
   * @return array The requested arguments.
   */
  private function get_arguments($request, $argument_keys) {
    $arguments = array();

    // All arguments are sent in the "arguments" key as JSON
    $request_arguments = json_decode($request['arguments'], true);

    if($request_arguments === false) {
      throw new \Exception('Arguments are not valid JSON.', 1011);
    }

    foreach($argument_keys as $argument_key) {
      if(isset($request_arguments[$argument_key])) {
        $arguments[] = $request_arguments[$argument_key];
      }
      else {
        // This is a bit confusing, but this is nice in that it allows for
        // default arguments. For example: let's say we have the following:
        //
        // api_call($one, $two = 'foo')
        //
        // If I don't specify $two in my arguments, I want the function to use
        // my default value. If I were to pass (1, null) to that function, I
        // would lose the default. Instead, as soon as I come across an argument
        // that wasn't provided, just return the current results.
        return $arguments;
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
   *
   * @param string $setting The setting name
   * @return mixed The setting
   */
  public static function get_setting($setting) {
    return self::$settings[$setting];
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
    $this->set_error_response(
      $error_message,
      $error_code,
      $error_file,
      $error_line,
      debug_backtrace(false)
    );
    die(); // Do not continue execution; shutdown handler will now run.
  }

  /**
   * Override of the default PHP exception handler. All unhandled exceptions go
   * here.
   *
   * @param Exception $e The exception.
   * @return null
   */
  public function exception_handler($e) {
    $this->set_error_response(
      $e->getMessage(),
      $e->getCode(),
      $e->getFile(),
      $e->getLine(),
      $e->getTrace()
    );
    die(); // Do not continue execution; shutdown handler will now run.
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
   * @return null
   */
  public function set_error_response($error_message, $error_code, $error_file, $error_line, $error_trace) {
    // $this->response_error_code = $error_code;

    if(self::get_setting('debug') === true) {
      $this->response = array(
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
      );
    }
    else {
      $this->response = array(
        'success' => false,
        'data' => array(
          'error_message' => $error_message,
          'error_code' => $error_code
        )
      );
    }
  }

  /**
   * Executes when the script finishes. If there was an error that somehow
   * didn't get caught, then this will find it with error_get_last and return
   * appropriately. Note that error_get_last() will only get something when an
   * error wasn't caught by my error/exception handlers. The default PHP error
   * handler fills this in. Doesn't do anything if an exception was thrown due
   * to the rate limit.
   *
   * @return null
   */
  public function shutdown_handler() {
    // Fix the current working directory. See documentation on this class
    // variable for details.
    chdir($this->current_working_directory);
    // If I didn't catch an error/exception with my handlers, look here...this
    // will catch fatal errors that I can't.
    $error = error_get_last();
    if($error !== null) {
      $this->set_error_response(
        $error['message'],
        $error['type'],
        $error['file'],
        $error['line'],
        debug_backtrace(false)
      );
    }

    // If the response has already been set by one of the error handlers, end
    // here.
    if(isset($this->response) === true) {
      // Don't log anything for rate limit breaches.
      if($this->response['data']['error_code'] !== 1005) {
        $this->log();
      }
      die(json_encode($this->response));
    }
    else {
      $this->response = array('success' => true);

      if(isset($this->request['batch']) === true) {
        $this->response['data'] = $this->response_data;
      }
      else {
        $this->response['data'] = $this->response_data[0];
      }

      // If debugging, add the original request to the repsonse
      if(self::get_setting('debug') === true) {
        $this->response['request'] = $this->request;
      }

      // Log all of the API calls that were made.
      $this->log();

      // Output the response
      die(json_encode($this->response));
    }
  }

  /**
   * Log the request and response to the database. The logged response is
   * truncated to 128kb for sanity.
   *
   * @return null
   */
  private function log() {
    $api_log_resource = new api_log();
    // If exception. This is lenghty because I have to check to make sure
    // everything was set or else use null.
    if(isset($this->response['data']['error_code'])) {
      if(isset($this->request['api_key']) === true) {
        $request_api_key = $this->request['api_key'];
      }
      else {
        $request_api_key = null;
      }

      $request_resource = null;
      $request_method = null;
      $request_arguments = null;
      if($this->current_api_call !== null) {
        if(isset($this->current_api_call['resource']) === true) {
          $request_resource = $this->current_api_call['resource'];
        }
        if(isset($this->current_api_call['method']) === true) {
          $request_method = $this->current_api_call['method'];
        }
        if(isset($this->current_api_call['arguments']) === true) {
          $request_arguments = $this->current_api_call['arguments'];
        }
      }
      $response_error_code = $this->response['data']['error_code'];
      $response_time = null;
      $response_query_count = null;
      $response_query_time = null;
      $response_data = substr(json_encode($this->response['data']), 0, 131072);

      $api_log_resource->create(array(
        'request_api_key'       =>  $request_api_key,
        'request_resource'      =>  $request_resource,
        'request_method'        =>  $request_method,
        'request_arguments'     =>  $request_arguments,
        'response_error_code'   =>  $response_error_code,
        'response_data'         =>  $response_data,
        'response_time'         =>  $response_time,
        'response_query_count'  =>  $response_query_count,
        'response_query_time'   =>  $response_query_time
      )); 
    }
    else {
      $response_error_code = null;
      for($i = 0; $i < count($this->api_calls); $i++) {
        $api_call = $this->api_calls[$i];
        $response = $this->response['data'][$i];

        $response_time = $this->response_times[$i];
        $response_query_count = $this->response_query_counts[$i];
        $response_query_time = $this->response_query_times[$i];

        $request_api_key = $api_call['api_key'];
        $request_resource = $api_call['resource'];
        $request_method = $api_call['method'];
        $request_arguments = isset($api_call['arguments']) ? $api_call['arguments'] : null;

        $response_data = substr(json_encode($response), 0, 131072);
        $api_log_resource->create(array(
          'request_api_key'       =>  $request_api_key,
          'request_resource'      =>  $request_resource,
          'request_method'        =>  $request_method,
          'request_arguments'     =>  $request_arguments,
          'response_error_code'   =>  $response_error_code,
          'response_data'         =>  $response_data,
          'response_time'         =>  $response_time,
          'response_query_count'  =>  $response_query_count,
          'response_query_time'   =>  $response_query_time
        ));    
      }
    }

  }

}
