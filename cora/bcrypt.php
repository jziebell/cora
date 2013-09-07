<?php

namespace cora;

/**
 * Offers password hashing functions that utilize bcrypt.
 *
 * @author  Jon Ziebell
 */
final class bcrypt {

  /**
   * Calls the PHP crypt function to generate a hash of a given password. If
   * no salt is provided one is generated automatically. If you're creating a
   * hash for a new password you shouldn't provide a salt unless you have some
   * really compelling reason to generate your own (you probably don't).
   *
   * @param string $password The plaintext password to hash.
   * @param string $salt The salt to hash with. If none is provided, one is
   * generated automatically. In general, don't provide a salt unless you know
   * what you're doing or the hash will fail.
   *
   * @throws \Exception If the provided or generated salt is invalid.
   *
   * @return string The hashed password.
   */
  public function get_hash($password, $salt = null) {
    if($salt === null) {
      $salt = $this->generate_salt();
    }
    if(preg_match('/^\$2y\$0*([4-9]|[12][0-9]|3[01])\$[A-Za-z0-9\.\/]{22}\$$/', $salt) !== 1) {
      throw new \Exception('Invalid salt.', 1700);
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
   *
   * @return boolean True if the passwords match, false if not.
   */
  public function compare($password, $hashed_password) {
    $salt = substr($hashed_password, 0, 29) . '$';
    return $this->get_hash($password, $salt) === $hashed_password;
  }

  /**
   * Generates a salt that will tell PHP to use CRYPT_BLOWFISH. This might not
   * be truly random by any means, but the salts just need to be different for
   * each password so this is good enough.
   *
   * @param string $cost Basically, the higher the number, the longer the hash
   * will take to calculate. Values less than 12 are not recommended. You
   * *can* change this value in production even if hashes have already been
   * generated, but it will only affect newly generated hashes. Old hashes
   * will use the original cost value as it's stored in the salt. You will
   * need to manually convert those if you want.
   *
   * @return string The salt.
   */
  private function generate_salt($cost = '14') {
    $salt = '$2y$' . $cost . '$';
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
