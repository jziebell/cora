<?php

$api_key = 'e0f38a5fd6c61ce6d7fc7ee7b9272811';

// Insert into sample_crud_resource
$post = array(
  'resource' => 'sample_crud_resource',
  'api_key' => $api_key,
  'method' => 'insert',
  'arguments' => json_encode(array(
    'attributes' => array('column_one'=>rand(), 'column_two'=>'will be overridden by sample_crud_resource class'),
    'return_item' => true
  ))
);

// Update sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => $api_key,
//   'method' => 'update',
//   'arguments' => json_encode(array(
//     'id' => 1,
//     'attributes' => array('column_one'=>rand())
//   ))
// );

// Delete sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => $api_key,
//   'method' => 'delete',
//   'arguments' => json_encode(array(
//     'id' => 3
//   ))
// );

// Get sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => $api_key,
//   'method' => 'get',
//   'arguments' => json_encode(array(
//     'id' => 1,
//     'columns' => array('column_one')
//   ))
// );

// Select sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => $api_key,
//   'method' => 'select',
//   'arguments' => json_encode(array(
//     'where_clause' => array('deleted'=>array(0,1))
//   ))
// );

// Select by id sample_crud_resource
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => $api_key,
//   'method' => 'select_id',
//   'arguments' => json_encode(array(
//     'where_clause' => array('deleted'=>array(0,1))
//   ))
// );

// Call a custom function
// $post = array(
//   'resource' => 'sample_crud_resource',
//   'api_key' => $api_key,
//   'method' => 'my_custom_function',
//   'arguments' => json_encode(array())
// );

// Create an API user
// $post = array(
//   'resource' => 'cora\api_user',
//   'api_key' => $api_key,
//   'method' => 'insert',
//   'arguments' => json_encode(array(
//     'attributes' => array(
//       'username'=>'foo1@bar.com',
//       'password'=>'foo1@bar.com'
//     )
//   ))
// );

// Create a user
// $post = array(
//   'resource' => 'user',
//   'api_key' => $api_key,
//   'method' => 'insert',
//   'arguments' => json_encode(array(
//     'attributes' => array(
//       'username'=>rand() . '@rand.com',
//       'password'=>'monkeybars'
//     )
//   ))
// );

// Log in
// $post = array(
//   'resource' => 'user',
//   'api_key' => $api_key,
//   'method' => 'log_in',
//   'arguments' => json_encode(array(
//     'username'=>'925833790@rand.com',
//     'password'=>'monkeybars'
//   ))
// );

$post_fields_string = '';
foreach($post as $key => $value) {
  $post_fields_string .= $key . '=' . $value . '&';
}

$curl_handle = curl_init('localhost/cora/index.php');
curl_setopt($curl_handle, CURLOPT_POST, count($post));
curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_fields_string);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);

// Debugging only, not a great place for the cookie file.
curl_setopt($curl_handle, CURLOPT_COOKIEFILE, '/var/www/cookie.txt');
curl_setopt($curl_handle, CURLOPT_COOKIEJAR, '/var/www/cookie.txt');
$result = curl_exec($curl_handle);

echo '<pre>';
print_r($result);
echo '<hr/>';
print_r(json_decode($result, true));
echo '</pre>';
