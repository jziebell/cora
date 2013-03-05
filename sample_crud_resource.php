<?php

final class sample_crud_resource extends cora\crud {

  public function select($where_clause, $columns = array()) {
    return parent::_select($where_clause, $columns);
  }
  public function select_id($where_clause, $columns = array()) {
    return parent::_select_id($where_clause, $columns);
  }
  public function get($id, $columns = array()) {
    return parent::_get($id, $columns);
  }
  public function insert($attributes) {
    $attributes['column_two'] = "overridden to this value";
    return parent::_insert($attributes);
  }
  public function update($id, $attributes) {
    return parent::_update($id, $attributes);
  }
	public function delete($id) {
    //if(!has_permission) throw new Exception('could do permission checks')
    return parent::_delete($id);
	}

  public function my_custom_function($whatever, $i, $want) {
    // $id = $this->insert(array('column_one'=>'test'));
    // $this->delete($id);
    return array('foo'=>1, 'bar'=>2);
  }

}
