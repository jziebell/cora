<?php

class bcrypt_test extends test_case {

  protected function setUp() {
    $this->regular_expression = '/^\$2y\$\d{2}\$[A-z0-9\/\.]{53}$/';
    $this->bcrypt_resource = new cora\bcrypt();
  }

  public function test_get_hash_with_valid_salt_returns_valid_format() {
    $password = 'password';
    $cost = '04'; // Decreased cost to increase speed of test.

    $salts = array(
      '$2y$' . $cost . '$M2bXxJn8B9a4bS7q6A5y2b$',
      '$2y$' . $cost . '$vIbXx7O829M4USXS.25t29$',
      '$2y$' . $cost . '$M2bXxJn8B9a4/S7q6A5y2b$'
    );
    foreach($salts as $salt) {
      $hash = $this->bcrypt_resource->get_hash($password, $salt);
      $this->assertEquals(preg_match($this->regular_expression, $hash), 1);
    }
  }

  public function test_get_hash_with_invalid_salt_throws_exception() {
    $password = 'password';

    $salts = array(
      '',                               // empty string
      '$2y$04$nope$',                   // invalid length
      '$2y$04$M2bXxJn8B9a4\S7q6A5y2b$', // \ is invalid
      '$2y$04$M2bXxJn8B9a4*S7q6A5y2b$', // * is invalid
      '$2y$03$M2bXxJn8B9a4nS7q6A5y2b$', // 03 is invalid (04-31)
      '$2y$32$M2bXxJn8B9a4nS7q6A5y2b$'  // 32 is invalid (04-31)
    );

    foreach($salts as $salt) {
      $function = function($password, $salt) {
        $this->bcrypt_resource->get_hash($password, $salt);
      };
      $this->assertException($function, array($password, $salt), 'Invalid salt.', 1700);
    }
  }

  public function test_compare() {
    $this->assertTrue($this->bcrypt_resource->compare('password', '$2y$04$M2bXxJn8B9a4bS7q6A5y2OrVzya1y3c1/QFafuefKoKCw5yknLN9C'));
    $this->assertTrue($this->bcrypt_resource->compare('password', '$2y$04$vIbXx7O829M4USXS.25t2uraOmFZw8UkXAXlz4TePQwHelI/zJVqi'));
    $this->assertTrue($this->bcrypt_resource->compare('password', '$2y$04$M2bXxJn8B9a4/S7q6A5y2OZEPdidGiQXG6OOeuFVjz66sRFnuqjNu'));
  }

  public function test_get_hash_with_no_salt_provided() {
    $password = 'password';

    $hash = $this->bcrypt_resource->get_hash($password);
    $this->assertEquals(preg_match($this->regular_expression, $hash), 1);
  }

}
