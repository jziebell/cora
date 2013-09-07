<?php

/**
 * I'm not 100% sure, but I'm guessing PHPUnit has it's own autoload function.
 * Using this will add to the autoload stack, but ALL autoload requests either
 * get piped through both functions or go through mine first. I just set mine
 * up to only bother with files in the Cora namespace and it seems to work
 * pretty well.
 */
function cora_autoloader($class) {
  $class_parts = explode('\\', $class);
  if($class_parts[0] === 'cora') {
    require '../' . str_replace('\\', '/', $class) . '.php';
  }
}
spl_autoload_register('cora_autoloader');

/**
 * Extending this class to add some of my own functionality. I don't like the
 * fact that you have to use annotations to assert data about an exception.
 */
class test_case extends PHPUnit_Framework_TestCase {

  public function assertException($function, $arguments, $message = null, $code = null) {
    try {
      call_user_func_array($function, $arguments);
    }
    catch(Exception $e) {
      if($message !== null) {
        $this->assertEquals($message, $e->getMessage());
      }
      if($code !== null) {
        $this->assertEquals($code, $e->getCode());
      }
      return;
    }
    $this->fail('Failed asserting that an exception was thrown.');
  }

}

/**
 * Mock database class
 */
// class database_mock {
//   public function insert($table, $attributes) {
//     return 1;
//   }
// }

/**
 * A sample crud object
 */
class crud_object extends cora\crud {
  // public function __construct() {
  //   $this->database = new database_mock();
  // }
  public function create($attributes) {
    return true;
  }
}
