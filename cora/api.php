<?php

namespace cora;

/**
 * This is the base class of both crud and dictionary, which are base classes
 * for almost everything else. It provides access to a few useful things.
 *
 * @author Jon Ziebell
 */
abstract class api {

  /**
   * The current resource.
   * @var string
   */
  protected $resource;

  /**
   * The database object.
   * @var database
   */
  protected $database;

  /**
   * Session object.
   * @var api_session
   */
  protected $api_session;

  /**
   * Construct and set the variables. Strip the namespace prefix 'cora\' from
   * the resource name.
   */
  final function __construct() {
    $this->resource = str_replace('cora\\', '', get_class($this));
    $this->database = database::get_instance();
    $this->api_session = api_session::get_instance();
  }

}
