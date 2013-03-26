<?php

namespace cora;

/**
 * Offers session-related functions. Like database, this is a singleton. The
 * functions provided here do not create a cookie; that's up to the user. This
 * just offers the generate a sesion key which is stored in the database and
 * checked by Cora when a request is received. Normally the value returned by
 * request() would be stored in the cookie with the name 'session_key'.
 *
 * @author Jon Ziebell
 */
final class api_session {

  /**
   * The singleton.
   * @var api_session
   */
  private static $instance;

  /**
   * The current session key. If null, none was provided.
   * @var string
   */
  private $session_key = null;

  /**
   * This function is private because this class is a singleton and should be
   * instantiated using the get_instance() function. It does not otherwise do
   * anything.
   */
  private function __construct() {}

  /**
   * Use this function to instantiate this class instead of calling new
   * api_session() (which isn't allowed anyways). This avoids confusion from
   * trying to use dependency injection by passing an instance of this class
   * around everywhere.
   *
   * @return api_session A new api_session object or the already created one.
   */
  public static function get_instance() {
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Request an api_session from Cora. It is up to the caller of this function
   * to store the value appropriately (like in a cookie) so that it is sent to
   * Cora for all subsequent requests.
   *
   * @param int $external_id An external integer pointer to another table. This
   *     will most often be user.user_id, but could be something like
   *     person.person_id or api_user.api_user_id.
   * @return string The generated session key.
   */
  public function request($external_id = null) {
    $database = database::get_instance();
    $session_key = self::generate_session_key();

    $session_key_escaped = $database->escape($session_key);
    $external_id_escaped = $database->escape($external_id);
    $type_escaped = $database->escape($type);

    $query = '
      insert into
        `api_session`(
          `session_key`,
          `external_id`,
          `last_used_at`,
        )
      values(
        ' . $session_key_escaped . ',
        ' . $external_id_escaped . ',
        now()
      )
    ';
    $database->query($query);

    $this->session_key = $session_key;
    return $session_key;
  }

  /**
   * Invalidate the current api_session.
   *
   * @return bool True if it was successfully invalidated. Could return false
   *     for a non-existent api_session key or if it was already deleted.
   */
  public function invalidate() {
    $database = database::get_instance();
    $session_key_escaped = $database->escape($this->session_key);
    $query = '
      update
        `api_session`
      set
        `deleted` = 1
      where
        `session_key` = ' . $session_key_escaped . '
    ';
    $database->query($query);
    return $database->affected_rows === 1;
  }

  /**
   * Check to see if the current api_session is valid.
   *
   * @param string $session_key
   * @return bool True if it was successfully invalidated. Could return false
   *     for a non-existant api_session key or if it was already deleted.
   */
  public function is_valid() {
    if($this->session_key === null) {
      return false;
    }
    else {
      $database = database::get_instance();
      $session_key_escaped = $database->escape($this->session_key);
      $query = '
        select
          *
        from
          `api_session`
        where
          `session_key` = ' . $session_key_escaped . ' and
          `deleted` = 0 and
          1
          #last used_at
      ';
      // TODO: if session_length=0, set it to some date far in the future
      $result = $database->query($query);
      return $result->num_rows === 1;
    }
  }

  /**
   * Set the session key. This is called from cora->__construct() when the
   * request is received.
   *
   * @param string $session_key The session key.
   */
  public function set_session_key($session_key) {
    $this->session_key = $session_key;
  }

  /**
   * Get the session_key.
   *
   * @return string
   */
  public function get_session_key() {
    return $this->session_key;
  }

  /**
   * Generate a random (enough) session key.
   *
   * @return string The generated session key.
   */
  private static function generate_session_key() {
    return strtolower(sha1(uniqid(mt_rand(), true)));
  }

}
