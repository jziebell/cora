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
   * @var session
   */
  protected $session;

  /**
   * Setting object.
   *
   * @var session
   */
  protected $setting;

  /**
   * Cora object.
   *
   * @var session
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

    // Set the proper session variable. This is weird but necessary since Cora
    // supports the ability to log in to manage your API key as well as the
    // ability to log in generically. Cora will instantiate one of these
    // depending on what the request was.
    if(api_session::has_instance() === true) {
      $this->session = api_session::get_instance();
    }
    else if(api_user_session::has_instance() === true) {
      $this->session = api_user_session::get_instance();
    }


    // TODO: can't check this because api_log extends crud which extends api and
    // it is created BEFORE the session is. I could move stuff around but then
    // rate limiting happens way after it should.


    // else {
    //   throw new \Exception('Session object not created.' . $this->resource, 6000);
    // }
  }

}
