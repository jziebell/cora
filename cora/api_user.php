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
   * @throws \Exception If, after 3 tries, a unique API key could not be
   *     found. This will realisticly never happen but is protected aginst
   *     regardless.
   * @return int
   */
  public function insert($attributes) {
    $attributes['confirmed'] = false;
    $tries = $tries_remaining = 3;
    do {
      try {
        $attributes['api_key'] = self::generate_api_key();
        return parent::_insert($attributes);
      } catch (DuplicateEntryException $e) {
        /*
        * Catch all duplicate entry exceptions. If the duplicate violation was
        * on the email field, send back an appropriate message. Otherwise go on
        * and try the insert again with a new API key.
        */
        if(stripos($e->getMessage(), 'for key \'email\'') !== false) {
          die('TODO error message');
        }
      }
    } while (--$tries_remaining > 0);

    throw new \Exception('Failed to generate unique API key in ' .
      $tries . ' tries. Please retry your action.');
  }

  /**
   * [update description]
   * @return [type] [description]
   */
  public function update() {
    // TODO
  }

  /**
   * [delete description]
   * @return [type] [description]
   */
  public function delete() {
    // TODO
  }

  /**
   *
   */
  public function replace_api_key($id) {
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
  }

  /**
   * Generates a random (enough) 32 character long hex string.
   *
   * @return string
   */
  private static function generate_api_key() {
    return strtolower(md5(rand()));
  }

}

?>