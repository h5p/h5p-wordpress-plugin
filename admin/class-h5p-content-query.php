<?php
/**
 * H5P Plugin.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2015 Joubel
 */

/**
 * H5P Content Query class
 *
 * @package H5P_Plugin_Admin
 * @author Joubel <contact@joubel.com>
 */
class H5PContentQuery {

  private $base_table;
  private $valid_joins;

  // Valid filter operators
  private $valid_operators = array(
    '=' => " = '%s'",
    'LIKE' => " LIKE '%%%s%%'"
  );

  // Valid fields and their true database names
  private $valid_fields = array(
    'id' => array('hc', 'id'),
    'title' => array('hc', 'title', TRUE),
    'content_type' => array('hl', 'title', TRUE),
    'created_at' => array('hc', 'created_at'),
    'updated_at' => array('hc', 'updated_at'),
    'user_id' => array('u', 'ID'),
    'user_name' => array('u', 'display_name', TRUE)
  );

  private $fields, $join, $where, $where_args, $order_by, $limit, $limit_args;

  /**
   * Confluctor
   */
  public function __construct($fields, $offset = NULL, $limit = NULL, $order_by = NULL, $reverse_order = NULL, $filters = NULL) {
    global $wpdb;

    $this->base_table = "{$wpdb->prefix}h5p_contents hc";
    $this->valid_joins = array(
      'hl' => " LEFT JOIN {$wpdb->prefix}h5p_libraries hl ON hl.id = hc.library_id",
      'u' => " LEFT JOIN {$wpdb->base_prefix}users u ON hc.user_id = u.ID"
    );


    $join = array();

    // Start adding fields
    $this->fields = '';
    foreach ($fields as $field) {
      if (!isset($this->valid_fields[$field])) {
        throw new Exception('Invalid field: ' . $field);
      }

      $valid_field = $this->valid_fields[$field];
      $table = $valid_field[0];

      if ($table !== 'hc' && !isset($this->valid_joins[$table])) {
        throw new Exception('Invalid table: ' . $table);
      }

      // Add valid fields
      if ($this->fields) {
        $this->fields .= ', ';
      }
      $this->fields .= $table . '.' . $valid_field[1] . ' AS ' . $field;

      if ($table !== 'hc' && !isset($join[$table])) {
        // Add join
        $join[$table] = $this->valid_joins[$table];
      }
    }
    if (!$this->fields) {
      throw new Exception('No fields specified.');
    }

    // Add joins
    $this->join = join('', $join);

    // Add filters to data query
    $this->where = '';
    $this->where_args = array();

    if ($filters !== NULL) {
      foreach ($filters as $filter) {
        if (!isset($this->valid_fields[$filter[0]]) || !isset($filter[1])) {
          continue; // Skip invalid fields
        }
        $field = $this->valid_fields[$filter[0]];
        $this->where .= ($this->where ? ' AND ' : ' WHERE ') . $field[0] . '.' . $field[1];
        $this->where_args[] = $filter[1];

        // Check if operator is valid, if not use the first valid one.
        if (!isset($filter[2])) {
          $operator = '=';
        }
        $operator = (isset($filter[2]) ? isset($filter[2]) : '=');
        if (!isset($this->valid_operators[$filter[2]])) {
          throw new Exception('Invalid operator: '. $operator);
        }
        $this->where .= $this->valid_operators[$filter[2]];
      }
    }

    // Sort by
    $this->order_by = '';
    if ($order_by !== NULL && isset($this->valid_fields[$order_by])) {
      $field = $this->valid_fields[$order_by];
      $dir = ($reverse_order ? TRUE : FALSE);
      if (isset($field[2])) {
        $dir = !$dir; // Reverse ordering of text fields
      }
      $this->order_by .= " ORDER BY {$field[0]}.{$field[1]} " . ($dir ? 'ASC' : 'DESC');
    }

    // Limit
    $this->limit = '';
    $this->limit_args = array();
    if ($limit !== NULL) {
      $this->limit .= ' LIMIT';

      if ($offset !== NULL) {
        $this->limit .= ' %d,';
        $this->limit_args[] = $offset;
      }

      $this->limit .= ' %d';
      $this->limit_args[] = $limit;
    }
  }

  /**
   * Get the result of the query.
   *
   * @since 1.5.3
   * @return array
   */
  public function get_rows() {
    global $wpdb;

    $query = "SELECT {$this->fields}
      FROM {$this->base_table}
      {$this->join}
      {$this->where}
      {$this->order_by}
      {$this->limit}";
    $args = array_merge($this->where_args, $this->limit_args);

    if (!empty($args)) {
      // We need to prep if we have args
      $query = $wpdb->prepare($query, $args);
    }
    return $wpdb->get_results($query);
  }

  /**
   * Total number of matches. Useful for pagination.
   *
   * @since 1.5.3
   * @return int
   */
  public function get_total() {
    global $wpdb;

    $query = "SELECT COUNT(hc.id)
      FROM {$this->base_table}
      {$this->where}";

    if (!empty($this->where_args)) {
      // We need to prep if we have args
      $query = $wpdb->prepare($query, $this->where_args);
    }
    return (int) $wpdb->get_var($query);
  }
}
