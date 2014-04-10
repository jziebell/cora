<?php

namespace cora;

/**
 * All of the settings used in Cora. You can also add your own settings to
 * this file if you like. When using a service like GitHub, it is recommended
 * that you do not commit this file as it contains the database connection
 * information.
 *
 * Here is a list of all the settings. See
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
 * $api_call_map
 *
 * @author Jon Ziebell
 */
final class setting {

  /**
   * The singleton.
   */
  private static $instance;

  /**
   * Constructor.
   */
  private function __construct() {}

  /**
   * Use this function to instantiate this class instead of calling new
   * setting() (which isn't allowed anyways). This is necessary so that the
   * API class can have access to Setting.
   *
   * @return setting A new setting object or the already created one.
   */
  public static function get_instance() {
    if(isset(self::$instance) === false) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private $settings = array(
    // Custom stuff here
    // 'custom' => 'foo',

    // Whether or not debugging is enabled. Debugging will produce additional
    // output in the API response, including data->error_file,
    // data->error_line, data->error_trace, and data->error_extra_info.
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
    // "myapp.com". You can set this value to null and it will work, but
    // sessions will not persist if a user switches from www.myapp.com to
    // myapp.com.
    //
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

    // API methods are all private by default. Add them here to expose them. To
    // require a valid session when making an API call (user logged in), put
    // your call in the 'session' key. Methods added to the 'non_session' key
    // can be called without being logged in.
    'api_call_map' => array(
      'session' => array(
      ),
      'non_session' => array(
        'test_crud' => array(
          'read' => array('attributes', 'columns'),
          'get' => array('id', 'columns')
        ),
        'test_user' => array(
          'log_in' => array('username', 'password', 'remember_me')
        )
      )
    )
  );

  /**
   * Get a setting.
   *
   * @param string $setting The setting name
   *
   * @return mixed The setting
   */
  public function get($setting) {
    return $this->settings[$setting];
  }

}

