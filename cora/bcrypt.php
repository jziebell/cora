<?php

namespace cora;

/**
 * Offers password hashing functions that utilize bcrypt.
 *
 * @author  Jon Ziebell
 */
abstract class bcrypt {

  /**
   * From php.net/crypt: The two digit cost parameter is the base-2 logarithm of
   * the iteration count for the underlying Blowfish-based hashing algorithmeter
   * and must be in range 04-31. Start going much higher and it will start
   * taking a really long time to hash passwords.
   *
   * You *can* change this value in a production environment, but it will only
   * affect newly generated hashes. Old hashes will use the original cost value
   * as it's stored in the salt. Values less than 12 are not recommended.
   *
   * Decrease this value if your logins are taking too long.
   *
   * @var string
   */
  private static $cost = '14';

  /**
   * Calls the PHP crypt function to generate a hash of a given password. If no
   * salt is provided (creating a new password), one is generated automatically.
   *
   * @param string $password The plaintext password to hash.
   * @param mixed $salt The salt to hash with. If none is provided, one is
   *     generated automatically.
   * @return string The hashed password.
   */
  public static function get_hash($password, $salt = null) {
    if($salt === null) {
      $salt = self::generate_salt();
    }
    return crypt($password, $salt);
  }

  /**
   * Compares a plaintext password to an existing hashed password. The salt is
   * pulled from the already hashed password and used to hash the password
   * attempt.
   *
   * @param string $password The plaintext password.
   * @param string $hashed_password The hashed password you are comparing to.
   * @return bool True if they are the same, false if not.
   */
  public static function compare($password, $hashed_password) {
    $salt = substr($hashed_password, 0, 29) . '$';
    return self::get_hash($password, $salt) === $hashed_password;
  }

  /**
   * Generates a salt that will indicate to PHP to use CRYPT_BLOWFISH. This
   * might not be truly random by any means, but the salts just need to be
   * different for each password so this is good enough.
   *
   * @return string The salt
   */
  private static function generate_salt() {
    $salt = '$2y$' . self::$cost . '$';
    $set = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789./';
    $length = 22;
    $set_length = strlen($set);
    for($i = 0; $i < $length; $i++) {
      $salt .= $set[mt_rand(1, $set_length)-1];
    }
    $salt .= '$';
    return $salt;
  }

}

?>