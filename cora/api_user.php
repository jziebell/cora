<?php

namespace cora;

/**
 * Stuff related to API users. For now this is very basic, but this could be
 * extended later on to allow creation and management of these users. At the
 * very least, Cora needs to be able to see if the API user is valid based off
 * of the API key.
 *
 * @author Jon Ziebell
 */
class api_user extends crud {

  /**
   * Check to see if an API key is valid.
   *
   * @param string $api_key The API key to look up.
   *
   * @return bool Whether or not the API key is valid.
   */
  public function is_valid_api_key($api_key) {
    $api_users = $this->read(array('api_key' => $api_key));
    return (count($api_users) === 1);
  }

}
