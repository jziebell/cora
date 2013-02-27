<?php

/**
 * API entry point. This sets the header, time limit, error handlers,
 * autoloader, etc and then tells Cora to process the incoming API request. Cora
 * will return JSON which is echoed out in the die().
 *
 * @author Jon Ziebell
 */

// This API returns JSON data
header('Content-type: application/json');

// Set a reasonable time limit
set_time_limit(2);

// Turn on all error reporting but disable displaying them
error_reporting(-1);
ini_set('display_errors', '0');

// Autoload classes as necessary so there are no includes/requires
spl_autoload_register();

// TODO: Lock api_key to IP address(es)

try {
  // Processes the request and calls the requested resource
  $cora = new cora\cora(
    array(
      'api_key'   => isset($_POST['api_key'])   ? $_POST['api_key']   : null,
      'resource'  => isset($_POST['resource'])  ? $_POST['resource']  : null,
      'method'    => isset($_POST['method'])    ? $_POST['method']    : null,
      'arguments' => (isset($_POST['arguments']) ?
        json_decode($_POST['arguments'], true) : null)
    )
  );

  // Error handling
  set_error_handler(array($cora, 'error_handler'));
  register_shutdown_function(
    array($cora, 'error_handler'),
    null,
    null,
    null,
    null
  );

  // Do it
  die($cora -> process_api_request());
}
catch(Exception $e) {
  die($cora->handle_exception(
    $e->getMessage(),
    $e->getCode(),
    $e->getFile(),
    $e->getLine(),
    $e->getTrace()
  ));
}