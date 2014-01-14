<?php

namespace cora;

/**
 * CRUD base class for most resources. Provides the ability to create
 * (insert), read (select), update (update), and delete (update set
 * deleted=1). There are also a few extra methods: read_id, get, undelete, and
 * hard_delete.
 *
 * These methods can (and should) be overridden by child classes. The most
 * basic override would simply call the parent function. More advanced
 * overrides might set a value like created_by before creating.
 *
 * Child classes can, at any time, call the parent methods directly from any
 * of their methods.
 *
 * @author Jon Ziebell
 */
abstract class crud extends api {

  /**
   * Insert an item into the current resource with the provided attributes.
   * Setting of the primary key column is not allowed and will be overwritten
   * if you try.
   *
   * @param array $attributes An array of attributes to set for this item
   *
   * @return mixed The id of the inserted row.
   */
  protected function create($attributes) {
    unset($attributes[$this->resource . '_id']);
    return $this->database->insert($this->resource, $attributes);
  }

  /**
   * Read items from the current resource according to the specified
   * $attributes. Only undeleted items are selected by default. This can be
   * altered by manually specifying deleted=1 or deleted=array(0, 1) in
   * $attributes.
   *
   * @param array $attributes An array of key value pairs to search by and
   * can include arrays if you want to search in() something.
   * @param array $columns The columns from the resource to return. If not
   * specified, all columns are returned.
   *
   * @return array The requested items with the requested columns in a
   * 0-indexed array.
   */
  protected function read($attributes = array(), $columns = array()) {
    $attributes = $attributes + array('deleted' => 0);
    return $this->database->select($this->resource, $attributes, $columns);
  }

  /**
   * See comment on crud->select() for more detail. The return array is
   * indexed by the primary key of the resource items.
   *
   * @param array $attributes An array of key value pairs to search by and
   * can include arrays if you want to search in() something.
   * @param array $columns The columns from the resource to return. If not
   * specified, all columns are returned.
   *
   * @return array The requested items with the requested colums in a primary-
   * key-indexed array.
   */
  protected function read_id($attributes = array(), $columns = array()) {
    // If no columns are specified to read, force the primary key column to be
    // included. This will ensure that no error is thrown when the result of the
    // query is converted into the ID array.
    if(count($columns) > 0) {
      $columns[] = $this->resource . '_id';
    }

    $rows = $this->read($attributes, $columns);
    $rows_id = array();
    foreach($rows as $row) {
      $rows_id[$row[$this->resource . '_id']] = $row;
    }
    return $rows_id;
  }

  /**
   * Selects an item by the primary key from the current resource. This will
   * select both deleted and not deleted items since the specification of the
   * id indicates you know what you want.
   *
   * @param int $id The id of the item to get.
   * @param array $columns The columns from the resource to return. If not
   * specified, all columns are returned.
   *
   * @return array The requested item with the requested columns.
   *
   * @throws \Exception If the item does not exist.
   */
  protected function get($id, $columns = array()) {
    $item = $this->read(
      array(
        $this->resource . '_id' => $id,
        'deleted' => array(0, 1)
      ),
      $columns
    );
    if(count($item) === 1) {
      return $item[0];
    }
    else {
      throw new \Exception('Resource item not found.', 1100);
    }
  }

  /**
   * Updates the current resource item with the provided id and sets the
   * provided attributes.
   *
   * @param int $id The id of the item to update.
   * @param array $attributes An array of attributes to set for this item
   *
   * @return int The number of affected rows.
   */
  protected function update($id, $attributes) {
    unset($attributes[$this->resource . '_id']);
    return $this->database->update($this->resource, $id, $attributes);
  }

  /**
   * Deletes an item with the provided id from the current resource. Deletes
   * always update the row to set deleted=1 instead of removing it from the
   * database.
   *
   * @param int $id The id of the item to delete.
   *
   * @return int The number of rows affected by the delete. If the item is
   * already deleted or not found, this value will be 0. Otherwise it will be
   * 1.
   */
  protected function delete($id) {
    return $this->update($id, array('deleted' => 1));
  }

  /**
   * Undeletes an item with the provided id from the current resource. This
   * will update the row and set deleted=0.
   *
   * @param int $id The id of the item to delete.
   *
   * @return int The number of rows affected by the undelete. If the item is
   * not deleted or not found, this value will be 0. Otherwise it will be 1.
   */
  protected function undelete($id) {
    return $this->update($id, array('deleted' => 0));
  }

  /**
   * Actually deletes an item with the provided id from the current resource.
   * This does not set deleted=1. It actually removes the row. Using this
   * function is not recommended unless it is necessary to delete personal
   * information due to some privacy laws or else for performance.
   *
   * @param int $id The id of the item to delete.
   *
   * @return int The number of rows affected by the delete. If the item is
   * already deleted or not found, this value will be 0. Otherwise it will be
   * 1.
   */
  protected function hard_delete($id) {
    return $this->database->hard_delete($this->resource, $id);
  }

}
