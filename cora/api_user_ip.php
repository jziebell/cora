<?php

   // I think I am going to get the API user id regardless and save it in the
   // api_log along with the API key, so I won't have to get that separately.

namespace cora;

/**
 * TODO
 *
 * @author Jon Ziebell
 */
class api_user_ip extends crud {

  /**
   * [insert description]
   *
   * @param [type] $attributes [description]
   *
   * @return [type] [description]
   */
  public function insert($attributes) {
    // TODO: Can two different API keys use the same IP address? Sure I think
    // TODO: Needs on duplicate key undelete
    // TODO: Permissions
    // TODO: Require email verification
    // TODO: Is this even useful?
    // TODO: Restrict by referrer? server key vs browser key? https://developers.google.com/console/help/
    // referrer is not secure. Maybe just strip all this out in favor of simplicity?
    // Most people would have a JS app that calls the API directly, then
    // offers (maybe) other people access to the api. A lot of those other
    // things would be server-side apis that people just play with and very
    // rarely a JS version.
    // Note that allowing IP filtering is extra work. At the moment I have to
    // do a query to convert API key to api_user, then I also have to query to
    // check and see if this host is allowed.
    $attributes['ip'] = ip2long($attributes['ip']);
    if($attributes['ip'] === false) {
      die('not an exception but bad data');
    }
    return parent::_insert($attributes);
  }

  /**
   * Deletes work a little bit differently for this resource. Instead of
   * providing the primary key, you provide the api_user_id and the ip
   * address.
   *
   * @param [type] $api_user_id [description]
   * @param [type] $ip [description]
   *
   * @return [type] [description]
   */
  public function delete($api_user_id, $ip) {
    // TODO: permissions? Only the current API user or a "master" api user can
    // do this for the current user. Should throw an exception if you try
    // someone else.
    $api_user_id_escaped = $this->database->escape($api_user_id);
    $ip_escaped = $this->database->escape(ip2long($ip));
    $query = "
      update
        api_user_ip
      set
        deleted = 1
      where
        api_user_id = $api_user_id_escaped and
        ip = $ip_escaped
    ";
    return $this->database->query($query);
  }

  /**
   * Delete all of the assigned IP addresses for a given API user. This will
   * efficively allow all IP addresses.
   *
   * @param int $api_user_id [description]
   */
  public function delete_all_for_api_user($api_user_id) {
    // TODO: Permissions
    $api_user_id_escaped = $this->database->escape($api_user_id);
    $query = "
      update
        api_user_ip
      set
        deleted = 1
      where
        api_user_id = $api_user_id_escaped
    ";
    return $this->database->query($query);
  }

}
