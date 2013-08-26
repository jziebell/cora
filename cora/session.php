<?php

namespace cora;

/**
 * Offers session-related functions. Simply extend this class, then call the
 * request() method to have a cookie automatically set.
 *
 * @author Jon Ziebell
 */
abstract class session {

  /**
   * The session_key for this session.
   */
  private $session_key = null;

  /**
   * The external_id for this session.
   */
  private $external_id = null;

  /**
   * Request a session. This method sets a cookie and returns the session key.
   * Use the following table to determine when the local cookie will be set to
   * expire.
   *
   *  timeout | life | expire
   * -------------------------------
   *  null    | null | 2038
   *  null    | set  | time() + life
   *  set     | null | 0
   *  set     | set  | time() + life
   *
   * @param int $timeout How long, in seconds, until the session expires due to
   *     inactivity. Set to null for no timeout.
   * @param int $life How long, in seconds, until the session expires. Set to
   *     null for no expiration.
   * @param int $external_id An optional external integer pointer to another
   *     table. This will most often be user.user_id, but could be something
   *     like person.person_id or player.player_id.
   * @return string The generated session key.
   */
  public function request($timeout, $life, $external_id = null) {
    $database = database::get_instance();
    $session_key = $this->generate_session_key();

    $session_key_escaped = $database->escape($session_key);
    $timeout_escaped = $database->escape($timeout);
    $life_escaped = $database->escape($life);
    $external_id_escaped = $database->escape($external_id);
    $created_by_escaped = $database->escape($_SERVER['REMOTE_ADDR']);
    $last_used_by_escaped = $created_by_escaped;
    
    $table = $this->get_table();
    $table_escaped = $database->escape_identifier($table);

    $query = '
      insert into
         ' . $table_escaped . '(
          `session_key`,
          `timeout`,
          `life`,
          `external_id`,
          `created_by`,
          `last_used_by`,
          `last_used_at`
        )
      values(
        ' . $session_key_escaped . ',
        ' . $timeout_escaped . ',
        ' . $life_escaped . ',
        ' . $external_id_escaped . ',
        inet_aton(' . $created_by_escaped . '),
        inet_aton(' . $last_used_by_escaped . '),
        now()
      )
    ';
    $database->query($query);

    // Set the local cookie expiration.
    if($life !== null) {
      $expire = time() + $life;
    }
    else {
      if($timeout === null) {
        $expire = 4294967295; // 2038
      }
      else {
        $expire = 0; // Browser close
      }
    }

    self::set_cookie($table . '_session_key', $session_key, $expire);
    self::set_cookie($table . '_external_id', $external_id, $expire);

    $this->session_key = $session_key;
    $this->external_id = $external_id;

    return $session_key;
  }

  /**
   * Sets a cookie. This method is not public because cookies should generally
   * be used sparingly to avoid adding state to your application. Cora sets two
   * cookie values to identify who you are and that's it.
   * 
   * @param string $name The name of the cookie.
   * @param mixed $value The value of the cookie.
   * @throws \Exception If The cookie fails to set.
   * @return null
   */
  private static function set_cookie($name, $value, $expire) {
    $path = ''; // The current directory that the cookie is being set in.
    $secure = $this->cora->get_setting('force_ssl');
    $httponly = true; // Only allow access to the cookie from the server.
    $domain = $this->cora->get_setting('cookie_domain');
    if($domain === null) { // See setting documentation for more info.
      $domain = '';
    }

    $cookie_success = setcookie(
      $name,
      $value,
      $expire,
      $path,
      $domain,
      $secure,
      $httponly
    );

    if($cookie_success === false) {
      throw new \Exception('Failed to set cookie.', 1400);
    }
  }

  /**
   * Similar to the Linux touch command, this method "touches" the session and
   * updates last_used_at and last_used_by. This is executed every time a
   * request that requires a session is sent to the API. Note that this uses the
   * cookie sent by the client directly so there is no default way to touch a
   * session unless you are the one logged in to it.
   *
   * @return bool True if it was successfully updated, false if the session does
   *     not exist or is expired. Basically, return bool whether or not the
   *     sesion is valid.
   */
  public function touch() {
    // Grab the cookie values. Note that if no session_key is available, this
    // method will search for a session with a null session key and end up
    // returning false. Class cora\cora will throw an exception for an expired
    // session in that case.
    $table = $this->get_table();
    if(isset($_COOKIE[$table . '_session_key'])) {
      $session_key = $_COOKIE[$table . '_session_key'];
    }
    if(isset($_COOKIE[$table . '_session_key'])) {
      $session_key = $_COOKIE[$table . '_session_key'];
    }
    else {
      $session_key = null;
    }

    if(isset($_COOKIE[$table . '_external_id'])) {
      $external_id = $_COOKIE[$table . '_external_id'];
    }
    else {
      $external_id = null;
    }

    $database = database::get_instance();
    $session_key_escaped = $database->escape($session_key);
    $last_used_by_escaped = $database->escape($_SERVER['REMOTE_ADDR']);
    $table_escaped = $database->escape_identifier($table);

    $query = '
      update
         ' . $table_escaped . '
      set
        `last_used_at` = now(),
        `last_used_by` = inet_aton(' . $last_used_by_escaped . ')
      where
        `deleted` = 0 and
        `session_key` = ' . $session_key_escaped . ' and
        (
          `timeout` is null or 
          `last_used_at` > date_sub(now(), interval `timeout` second)
        ) and
        (
          `life` is null or 
          `created_at` > date_sub(now(), interval `life` second)
        )
    ';

    $database->query($query);

    // If there was one row updated, we're good. Otherwise we need to check the
    // info string to see if a row matched but just didn't need updating (if two
    // requests in the same second come through, the second won't update the
    // row). All this is to avoid executing an extra count query.
    if($database->affected_rows === 1) {
      $this->session_key = $session_key;
      $this->external_id = $external_id;
      return true;
    }
    else {
      preg_match_all('/Rows matched: (\d+)/', $database->info, $matches);
      if(isset($matches[1][0]) && $matches[1][0] === '1') {
        $this->session_key = $session_key;
        $this->external_id = $external_id;        
        return true;
      }
      else {
        return false;
      }
    }
  }

  /**
   * Delete the session with the provided session_key. If no session_key is
   * provided, delete the current session. This function is provided to aid
   * session management. Call it with no parameters for something like
   * user->log_out(), or set $session_key to end a specific session. You would
   * typically want to have your own permission layer on top of that to enable
   * only admins to do that.
   *
   * @param string $session_key The session key of the session to delete.
   * @return bool True if it was successfully deleted. Could return false for a
   *     non-existent session key or if it was already deleted.
   */
  public function delete($session_key = null) {
    $database = database::get_instance();
    if($session_key === null) {
      $session_key = $this->session_key;
    }
    $session_key_escaped = $database->escape($session_key);
    $table_escaped = $database->escape_identifier($this->get_table());
    $query = '
      update
        ' . $table_escaped . '
      set
        `deleted` = 1
      where
        `session_key` = ' . $session_key_escaped . '
    ';
    $database->query($query);
    return $database->affected_rows === 1;
  }

  /**
   * Look at the class that extended this class and use that as the table name.
   * 
   * @return string The table name.
   */
  private function get_table() {
    $class_parts = explode('\\', get_class($this));
    $table = end($class_parts);
    return $table;
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
