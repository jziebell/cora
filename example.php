<?php

// Insert into sample_crud_resource
$post = array(
  'resource' => 'sample_crud_resource',
  'api_key' => 'API_KEY',
  'method' => 'insert',
  'arguments' => json_encode(array(
    'attributes' => array('column_one'=>rand(), 'column_two'=>'will be overridden by sample_crud_resource class')
  ))
);

// Update sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => 'API_KEY',
//   'method' => 'update',
//   'arguments' => json_encode(array(
//     'id' => 1,
//     'attributes' => array('column_one'=>rand())
//   ))
// );

// Delete sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => 'API_KEY',
//   'method' => 'delete',
//   'arguments' => json_encode(array(
//     'id' => 3
//   ))
// );

// Get sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => 'API_KEY',
//   'method' => 'get',
//   'arguments' => json_encode(array(
//     'id' => 1,
//     'columns' => array('column_one')
//   ))
// );

// Select sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => 'API_KEY',
//   'method' => 'select',
//   'arguments' => json_encode(array(
//     'where_clause' => array('deleted'=>array(0,1))
//   ))
// );

// Select by id sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => 'API_KEY',
//   'method' => 'select_id',
//   'arguments' => json_encode(array(
//     'where_clause' => array('deleted'=>array(0,1))
//   ))
// );

// Call a custom function
$post = array(
  'resource' => 'sample_crud_resource',
  'api_key' => 'API_KEY',
  'method' => 'my_custom_function',
  'arguments' => json_encode(array())
);

$post_fields_string = '';
foreach($post as $key => $value) {
  $post_fields_string .= $key . '=' . $value . '&';
}

$curl_handle = curl_init('localhost/cora/index.php');
curl_setopt($curl_handle, CURLOPT_POST, count($post));
curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_fields_string);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($curl_handle);

echo '<pre>';
print_r($result);
echo '<hr/>';
print_r(json_decode($result, true));
echo '</pre>'

?>