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
   * @throws \Exception If, after 3 attempts, a unique API key could not be
   *     found. This will realisticly never happen but is protected aginst
   *     regardless.
   * @return int
   */
  public function insert($attributes) {
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
        // TODO: Figure out a good way to send error info back to the client
        // that would need to be displayed to the end user. This is not an
        // exception...it is an error, though.
        if(stripos($e->getMessage(), 'for key \'email\'') !== false) {
          die('same email');
        }
      }
    } while (--$tries_remaining > 0);

    throw new \Exception('Failed to generate unique API key in ' .
      $tries . ' attempts. Please retry your action.');
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