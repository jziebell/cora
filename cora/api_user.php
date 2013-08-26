<?php

namespace cora;

/**
 * TODO
 *
 * @author Jon Ziebell
 */
class api_user extends crud {

  /**
   * Creates a new API user. The API key is automatically generated. If that API
   * key already exists for another user, try a new API key up to 3 times.
   *
   * @param array $attributes
   * @throws \Exception If creating API users is disabled.
   * @throws \Exception If a username was not provided.
   * @throws \Exception If a password was not provided.
   * @return int
   */
  public function create($attributes) {
    if($this->cora->get_setting('enable_api_user_creation') === false) {
      throw new \Exception('API user creation is disabled.', 1500);
    }
    if(!isset($attributes['username'])) {
      throw new \Exception('A username is required.', 1501);
    }
    if(!isset($attributes['password'])) {
      throw new \Exception('A password is required.', 1502);
    }
    $attributes['password'] = bcrypt::get_hash($attributes['password']);
    $attributes['api_key'] = self::generate_api_key();

    // Note that there is an infinitesimally‎ small chance that a duplicate API
    // key is generated here. Probably not worth catching. Could get the same
    // error for a duplicate username as well.
    return parent::create($attributes);
  }

  /**
   * Search for API users.
   *
   * @param array $where_clause
   * @param array $columns
   * @return array
   */
  public function read($where_clause = array(), $columns = array()) {
    return parent::read($where_clause, $columns);
  }


  /**
   * Log in by checking the provided password against the stored password for
   * the provided username. If it's a match, get a session.
   *
   * @param string $username
   * @param string $password
   * @param bool $remember_me If set to true the session never expires.
   * @return bool True if success, false if failure.
   */
  public function log_in($username, $password, $remember_me) {

    if($remember_me === true) {
      $timeout = null;
      $life = null;
    }
    else {
      // Session expires on browser close or after 1 hour of inactivity.
      $timeout = 3600;
      $life = null;
    }

    $api_user = $this->read(array('username'=>$username), array('password', 'api_user_id'));
    if(count($api_user) !== 1) {
      return false;
    }
    else {
      $api_user = $api_user[0];
    }

    if(bcrypt::compare($password, $api_user['password']) === false) {
      return false;
    }
    else {
      $this->session->request($timeout, $life, $api_user['api_user_id']);
      return true;
    }

  }  

  /**
   * [update description]
   * @return [type] [description]
   */
  public function update($id, $attributes) {
    // TODO
  }

  /**
   * [delete description]
   * @return [type] [description]
   */
  public function delete($id) {
    // TODO
  }

  /**
   * Check to see if an API key is valid.
   * 
   * @param string $api_key The API key to look up.
   * @return bool Whether or not the API key is valid.
   */
  public function is_valid_api_key($api_key) {
    // $api_key_escaped = $this->database->escape($api_key);
    // $query = 'select * from `api_user` where `api_key`=' . $api_key_escaped . ' and `deleted`=0';
    $api_users = $this->read(array('api_key' => $api_key));
    return (count($api_users) === 1);

    // $result = $this->database->query($query);
    // return $result->num_rows === 1;
  }

  /**
   * permission note: only an admin and the owner of the api key should be able
   * to do this. this is why I had sessions for api_users as well. perhaps this
   * is just not a feature of cora? maybe I need api_user_session to properly
   * handle them? I could also add api_user_permission or set an admin column or
   * something that defines who is allowed to edit api_users
   */
  // TODO: 8/13/2013 - Is the tries remaining useful?
/*  public function replace_api_key($id) {
    // TODO: Permissions or just use the current api_user_id?
    // by using the current user id, I prevent myself, as an admin, from
    // making this change. I would have to use their API key, which I really
    // shouldn't because it will show some random user up in their logs.
    $tries = $tries_remaining = 3;
    do {
      try {
        $attributes = array('api_key' => self::generate_api_key());
        return parent::_update($id, $attribues);
      } catch (DuplicateEntryException $e) {
        // Catch exception for duplicate API key and try again.
      }
    } while (--$tries_remaining > 0);

    throw new \Exception('Failed to generate unique API key in ' .
      $tries . ' tries. Please retry your action.');
  }*/

  /**
   * Generates a random (enough) 40 character long hex string.
   *
   * @return string
   */
  private static function generate_api_key() {
    return strtolower(sha1(uniqid(mt_rand(), true)));
  }

}
