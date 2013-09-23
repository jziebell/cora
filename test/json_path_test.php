<?php

class json_path_test extends test_case {

  protected function setUp() {
    $this->json_path_resource = new cora\json_path();
    $this->data = array(
      'store' => array(
        'book' => array(
          array(
            'category' => 'reference',
            'author' => 'Nigel Rees',
            'title' => 'Sayings of the Century',
            'price' => 8.95
          ),
          array(
            'category' => 'fiction',
            'author' => 'Evelyn Waugh',
            'title' => 'Sword of Honour',
            'price' => 12.99
          ),
          array(
            'category' => 'fiction',
            'author' => 'Herman Melville',
            'title' => 'Moby Dick',
            'isbn' => '0-553-21311-3',
            'price' => 8.99
          ),
          array(
            'category' => 'fiction',
            'author' => 'J. R. R. Tolkien',
            'title' => 'The Lord of the Rings',
            'isbn' => '0-395-19395-8',
            'price' => 22.99
          )
        ),
        'bicycle' => array(
          'color' => 'red',
          'price' => 19.95
        )
      )
    );
  }

  public function test_evaluate_full_array() {
    $result = $this->json_path_resource->evaluate($this->data, 'store');
    $this->assertEquals($result, $this->data['store']);
  }

  public function test_evaluate_partial_array() {
    $result = $this->json_path_resource->evaluate($this->data, 'store.book');
    $this->assertEquals($result, $this->data['store']['book']);
  }

  public function test_evaluate_numerical_index() {
    $result = $this->json_path_resource->evaluate($this->data, 'store.book[2]');
    $this->assertEquals($result, $this->data['store']['book'][2]);
  }

  public function test_evaluate_dot_notation() {
    $result = $this->json_path_resource->evaluate($this->data, 'store.book.2');
    $this->assertEquals($result, $this->data['store']['book'][2]);
  }

  public function test_evaluate_array_notation() {
    $result = $this->json_path_resource->evaluate($this->data, 'store["book"][2]');
    $this->assertEquals($result, $this->data['store']['book'][2]);

    $result = $this->json_path_resource->evaluate($this->data, 'store[\'book\'][2]');
    $this->assertEquals($result, $this->data['store']['book'][2]);

    $result = $this->json_path_resource->evaluate($this->data, 'store[book][2]');
    $this->assertEquals($result, $this->data['store']['book'][2]);
  }

  public function test_invalid_path_string() {
    $function = function($data, $json_path) {
      $this->json_path_resource->evaluate($data, $json_path);
    };
    $this->assertException($function, array($this->data, ''), 'Invalid path string.', 1800);
    $this->assertException($function, array($this->data, 'does_not_exist'), 'Invalid path string.', 1800);
    $this->assertException($function, array($this->data, 'store.does_not_exist'), 'Invalid path string.', 1800);
    $this->assertException($function, array($this->data, 'store.book[\'does_not_exist\']'), 'Invalid path string.', 1800);
  }

}
