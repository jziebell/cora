<?php

final class sample_dictionary_resource extends cora\dictionary {

  public function select($where_clause, $columns = array()) {
    return parent::_select($where_clause, $columns);
  }
  public function select_id($where_clause, $columns = array()) {
    return parent::_select_id($where_clause, $columns);
  }
  public function get($id, $columns = array()) {
    return parent::_get($id, $columns);
  }

}
