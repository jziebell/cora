<?php

namespace cora;

/**
 * Offers session-related functions. Like database, this is a singleton. The
 * functions provided here do not create a cookie; that's up to the user. This
 * just offers the generate a sesion key which is stored in the database and
 * checked by Cora when a request is received. The value returned by request()
 * must be stored in the cookie with the name 'session_key'.
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
   * to store the returned session key in a cookie.
   *
   * @param int $timeout How long, in seconds, until the session expires due to
   *     inactivity. Set to null for no timeout.
   * @param int $life How long, in seconds, until the session expires. Set to
   *     null for no expiration.
   * @param int $external_id An (optional) external integer pointer to another
   *     table. This will most often be user.user_id, but could be something
   *     like person.person_id or player.player_id.
   * @throws \Exception If The cookie fails to set.
   * @return string The generated session key.
   */
  public function request($timeout, $life, $external_id = null) {
    $database = database::get_instance();
    $session_key = self::generate_session_key();

    $session_key_escaped = $database->escape($session_key);
    $timeout_escaped = $database->escape($timeout);
    $life_escaped = $database->escape($life);
    $external_id_escaped = $database->escape($external_id);
    $created_by_escaped = $database->escape($_SERVER['REMOTE_ADDR']);
    $last_used_by_escaped = $created_by_escaped;

    $query = '
      insert into
        `api_session`(
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

    // Set the local cookie expiration. If the life is ever set for the session,
    // then the cookie should obviously expire at the same time.
    //
    // If the life is not set, then check the timeout. If timeout is also null,
    // the local cookie should last "forever". If timeout is not null, then the
    // cookie should expire on browser close.
    //
    //    timeout | life | expire
    //    ------------------------
    //    null    | null | 2038
    //    null    | set  | time() + life
    //    set     | null | 0
    //    set     | set  | time() + life
    //
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

    $path = ''; // The current directory that the cookie is being set in.
    $secure = cora::get_setting('force_ssl');
    $httponly = true; // Only allow access to the cookie from the server.
    $domain = cora::get_setting('cookie_domain');
    if($domain === null) { // See setting documentation for more info.
      $domain = '';
    }

    $result = setcookie(
      'session_key',
      $session_key,
      $expire,
      $path,
      $domain,
      $secure,
      $httponly
    );
    if($result === false) {
      throw new \Exception('Failed to set cookie.', 1400);
    }

    return $session_key;
  }

  /**
   * Similar to the Linux touch command, this method "touches" the session and
   * updates last_used_at and last_used_by. This is executed every time a
   * request is sent to the API with a session to keep it active.
   * @param string $session_key The session key of the session to touch.
   * @return bool True if it was successfully updated, false if the session does
   *     not exist or is expired. Basically, return bool whether or not the
   *     sesion is valid.
   */
  public function touch($session_key) {
    $database = database::get_instance();
    $session_key_escaped = $database->escape($session_key);
    $last_used_by_escaped = $database->escape($_SERVER['REMOTE_ADDR']);

    $query = '
      update
        `api_session`
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
    // info string to see if a row matched but just didn't need updating. All
    // this is to avoid executing a count query.
    if($database->affected_rows === 1) {
      return true;
    }
    else {
      // I need a way of finding out whether or not this update affected any
      // rows. Using $database->affected_rows is unfortunately not accurate in
      // this situation because two API requests could have come through in the
      // same second. If that happens, the second will cause affected_rows to be
      // 0. To remedy that, use the info string and look at the number of
      // matched rows instead.
      preg_match_all('/Rows matched: (\d+)/', $database->info, $matches);
      return $matches[1][0] === '1';
    }
  }

  /**
   * Delete the api_session with the provided session_key.
   *
   * @param string $session_key The session key of the session to delete.
   * @return bool True if it was successfully deleted. Could return false
   *     for a non-existent api_session key or if it was already deleted.
   */
  public function delete($session_key) {
    $database = database::get_instance();
    $session_key_escaped = $database->escape($session_key);
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
   * Generate a random (enough) session key.
   *
   * @return string The generated session key.
   */
  private static function generate_session_key() {
    return strtolower(sha1(uniqid(mt_rand(), true)));
  }

}
