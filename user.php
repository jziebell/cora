<?php

/**
 * This resource is not required to run Cora. It is provided as an example of
 * how to use cookies with Cora to log a user in. It also provides a recommended
 * implementation for a basic user resource. Use and modify at will.
 *
 * @author Jon Ziebell
 */
class user extends cora\crud {

  /**
   * Selects a user.
   *
   * @param array $where_clause
   * @param array $columns
   * @return array
   */
  public function select($where_clause, $columns = array()) {
    return parent::_select($where_clause, $columns);
  }

  /**
   * Creates a user. Username and password are both required. The password is
   * hashed with bcrypt.
   *
   * @param array $attributes
   * @return int
   */
  public function insert($attributes) {
    if(!isset($attributes['password'])) {
      die('TODO error message');
    }
    if(!isset($attributes['username'])) {
      die('TODO error message');
    }
    $attributes['password'] = cora\bcrypt::get_hash($attributes['password']);
    return parent::_insert($attributes);
  }

  /**
   * Updates a user. If the password is changed then it is re-hashed with bcrypt
   * and a new salt is generated.
   *
   * @param int $id
   * @param array $attributes
   * @return int
   */
  public function update($id, $attributes) {
    // TODO: Use the currently logged in user from cora
    if(isset($attributes['password'])) {
      $attributes['password'] = cora\bcrypt::get_hash($attributes['password']);
    }
    return parent::_update($id, $attributes);
  }

  /**
   * Deletes a user.
   *
   * @param int $id
   * @return int
   */
  public function delete($id) {
    // TODO: Use the currently logged in user from cora
    return parent::_delete($id);
  }

  /**
   * Log in by checking the provided password against the stored password for
   * the provided username. If it's a match, get a session key from Cora and set
   * the cookie.
   *
   * @param string $username
   * @param string $password
   * @throws Exception If the cookie could not be set.
   * @return bool True if success, false if failure.
   */
  public function log_in($username, $password) {
    $user = $this->select(array('username'=>$username), array('password'));
    if(count($user) !== 1) {
      return false;
    }
    else {
      $user = $user[0];
    }

    if(cora\bcrypt::compare($password, $user['password']) === true) {
      $session_key = $this->session->request();

      // Set the cookie
      $expire = time() + cora\cora::get_session_length();
      $path = ''; // Empty string uses default
      $domain = ''; // Empty string uses default
      $secure = cora\cora::get_force_ssl();
      // TODO: I think the expiration time is bugged. My sessions expire x
      // seconds after inactivity. Won't this force expire the browser cookie
      // after x seconds?
      $result = setcookie('session_key', $session_key,
        $expire,
        $path,
        $domain,
        $secure
      );
      if($result === false) {
        throw new Exception('Failed to set cookie.');
      }
      else {
        return true;
      }

    }
    else {
      return false;
    }
  }

  /**
   * Logs out the currently logged in user.
   *
   * @return bool True if it was successfully invalidated. Could return false
   *     for a non-existant session key or if it was already logged out.
   */
  public function log_out() {
    return $this->session->invalidate();
  }
}
