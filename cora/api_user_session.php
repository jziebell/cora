<?php

namespace cora;

/**
 * General sessions for things like users. See the sesion class for
 * implementation details; this only provides the singleton.
 *
 * @author Jon Ziebell
 */
final class api_user_session extends session {

  /**
   * The singleton.
   *
   * @var api_session
   */
  private static $instance;

  /**
   * This function is private because this class is a singleton and should be
   * instantiated using the get_instance() function. It does not otherwise do
   * anything.
   */
  private function __construct() {
    $this->cora = cora::get_instance();
  }

  /**
   * Use this function to instantiate this class instead of calling new
   * api_session() (which isn't allowed anyways). This avoids confusion from
   * trying to use dependency injection by passing an instance of this class
   * around everywhere.
   *
   * @return api_session A new api_session object or the already created one.
   */
  public static function get_instance() {
    if(isset(self::$instance) === false) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Return whether or not this class has been instantiated.
   *
   * @return bool
   */
  public static function has_instance() {
    return isset(self::$instance);
  }

}
