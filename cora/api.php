<?php

namespace cora;

/**
 * This is the base class crud, which is a base class for almost everything
 * else. It provides access to a few useful things.
 *
 * @author Jon Ziebell
 */
abstract class api {

  /**
   * The current resource.
   *
   * @var string
   */
  protected $resource;

  /**
   * The database object.
   *
   * @var database
   */
  protected $database;

  /**
   * Session object.
   *
   * @var api_session
   */
  protected $api_session;

  /**
   * Setting object.
   *
   * @var setting
   */
  protected $setting;

  /**
   * Cora object.
   *
   * @var cora
   */
  protected $cora;

  /**
   * Construct and set the variables. The namespace is stripped from the
   * resource variable. Anything that extends crud or API will use this
   * constructor. This means that there should be no arguments or every time
   * you want to use one of those resources you will have to find a way to
   * pass in the arguments. Using a couple singletons here makes that a lot
   * simpler.
   */
  final function __construct() {
    $class_parts = explode('\\', get_class($this));
    $this->resource = end($class_parts);
    $this->database = database::get_instance();
    $this->cora = cora::get_instance();
    $this->setting = setting::get_instance();
    $this->api_session = api_session::get_instance();
  }

  /**
   * Shortcut method for doing API calls within the API. This will create an
   * instance of the resource you want and call the method you want with the
   * arguments you want.
   *
   * @param string $resource The resource to use.
   * @param string $method The method to call.
   * @param array $arguments The arguments to send.
   *
   * @return mixed
   */
  public function api($resource, $method, $arguments = array()) {
    $resource_instance = new $resource();
    return call_user_func_array(array($resource_instance, $method), $arguments);
  }

}
