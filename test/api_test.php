<?php

class api_test extends test_case {

  protected function setUp() {

  }

  public function test_something() {


    // my class > crud > api
    // need a fake class that extends crud
    // need a mock crud class
    // $api_resource = new cora\api();
    //

    $crud_object_resource = new crud_object():



    // $database_stub = $this->getMockBuilder('cora\database')
                      // ->disableOriginalConstructor()
                      // ->getMock();

    // $stub = $this->getMockForAbstractClass('cora\api');
    // $stub->expects($this->any())
    //      ->method('abstractMethod')
    //      ->will($this->returnValue(true));

    // $this->assertTrue($stub->concreteMethod());
  }

}


/**
 * A sample crud object
 */
class crud_object extends cora\crud {
  public function create($attributes) {
    return true;
  }
}
