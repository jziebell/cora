<?php

/**
 * Entry point for the API. This sets up cora, the error/exception handlers,
 * and then sends the request off for processing. All requests should start
 * here.
 *
 * @author Jon Ziebell
 */

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
$cora = cora\cora::get_instance();
set_error_handler(array($cora, 'error_handler'));
set_exception_handler(array($cora, 'exception_handler'));

// The shutdown handler will output the response
register_shutdown_function(array($cora, 'shutdown_handler'));

// Go!
$cora->process_request($_REQUEST);

/**
 * TODO: Things to document
 * > batch API + names/aliases
 * > cora::set_headers() and the difference between custom and not custom responses
 * > how to use unicode (set meta tag, set up mysql tables, use proper encoding in response)
 */
