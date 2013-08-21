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

// Compress output
ob_start('ob_gzhandler');

// Set a reasonable time limit
set_time_limit(2);

// Turn on all error reporting but disable displaying errors
error_reporting(-1);
ini_set('display_errors', '0');

// Autoload classes as necessary so there are no includes/requires
spl_autoload_register();
    
// Construct cora and set up error handlers
$cora = new cora\cora();
set_error_handler(array($cora, 'error_handler'));
set_exception_handler(array($cora, 'exception_handler'));

// The shutdown handler will output the response
register_shutdown_function(array($cora, 'shutdown_handler'));

// Go!
$cora->process_request($_REQUEST);
