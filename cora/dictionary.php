<?php

namespace cora;

/**
 * The dictionary class is identical to crud except that only the select-type
 * methods are available. Dictionaries do not support being updated or deleted
 * except manually through another process.
 *
 * The functions defined here cannot be overridden from child classes in order
 * to prevent confusion. They provide very powerful standard functionality. By
 * overriding one of these in order to do something like set a certain value (ex
 * created_by) on create, insert, etc, you limit yourself in the future for more
 * admin-like functions where I might want to override some of those now-default
 * values.
 *
 * For this reason, all child classes should define separate functions as
 * necessary for inserting, updating, etc. All functions here are prefixed with
 * a single "_" in order that the standard name can still be used in the child
 * class.
 *
 * @author Jon Ziebell
 */
abstract class dictionary {

  /**
   * Select items from the current resource according to the specified
   * $where_clause. Only undeleted items are selected by default. This can be
   * altered by manually specifying deleted=1 or deleted=array(0,1) in
   * $where_clause.
   *
   * @param array $where_clause An array of key value pairs to search by and can
   *     include arrays if you want to search in() something.
   * @param array $columns The columns from the resource to return. If not
   *     specified, all columns are returned.
   * @return array The requested items with the requested columns in a 0-indexed
   *     array.
   */
  final protected function _select($where_clause, $columns = array()) {
    $where_clause = $where_clause + array('deleted' => 0);
    return $this->database->select($this->resource, $where_clause, $columns);
  }

  /**
   * See comment on crud->select() for more detail. The return array is indexed
   * by the primary key of the resource items.
   *
   * @param $where_clause An array of key value pairs to search by and can
   *     include arrays if you want to search in() something.
   * @param $columns The columns from the resource to return. If not specified,
   *     all columns are returned.
   * @return array The requested items with the requested colums in a primary-
   *     key-indexed array.
   */
  final protected function _select_id($where_clause, $columns = array()) {
    $rows = $this->select($where_clause, $columns);
    $rows_id = array();
    foreach($rows as $row) {
      $rows_id[$row[$this->resource . '_id']] = $row;
    }
    return $rows_id;
  }

  /**
   * Selects an item by the primary key from the current resource.
   *
   * @param int $id The id of the item to get.
   * @param $columns The columns from the resource to return. If not specified,
   *     all columns are returned.
   * @return array The requested item with the requested columns.
   * @throws \Exception If the item does not exist or is deleted.
   */
  final protected function _get($id, $columns = array()) {
    $item = $this->select(array($this->resource . '_id' => $id));
    if(count($item) === 1) return $item[0];
    else throw new \Exception(
      'Resource "' . $this->resource . '" with id "' . $id . '" is not found.';
    );
  }

}

?>