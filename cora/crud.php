<?php

namespace cora;

/**
 * CRUD base class for most resources. Provides the ability to insert (create),
 * read (select), update (update), and delete (update set deleted=1).
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
abstract class crud extends api {

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
    $item = $this->select(array($this->resource . '_id' => $id), $columns);
    if(count($item) === 1) {
      return $item[0];
    }
    else throw new \Exception(
      'Resource "' . $this->resource . '" with id "' . $id . '" is not found.'
    );
  }

  /**
   * Insert an item into the current resource with the provided attributes.
   * Setting of the primary key column is not allowed and will be overwritten if
   * you try.
   *
   *
   * @param array $attributes An array of attributes to set for this item
   * @return int The id of the inserted row.
   */
  final protected function _insert($attributes) {
    unset($attributes[$this->resource . '_id']);
    return $this->database->insert($this->resource, $attributes);
  }

  /**
   * Updates the current resource item with the provided id and sets the
   * provided attributes.
   *
   * @param int $id The id of the item to update.
   * @param array $attributes An array of attributes to set for this item
   * @return int The number of affected rows.
   */
  final protected function _update($id, $attributes) {
    unset($attributes[$this->resource . '_id']);
    return $this->database->update($this->resource, $id, $attributes);
  }

  /**
   * Deletes an item with the provided id from the current resource. Deletes
   * always update the row to set deleted=1 instead of removing it from the
   * database.
   *
   * @param int $id The id of the item to delete.
   * @return int The number of rows affected by the delete. If the item is
   *     already deleted or not found, this value will be 0. Otherwise it will
   *     be 1.
   */
  final protected function _delete($id) {
    return $this->update($id, array('deleted' => 1));
  }

}
