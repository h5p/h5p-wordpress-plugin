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
    'LIKE' => " LIKE '%%%s%%'",
    'IN' => " IN (%s)"
  );

  // Valid fields and their true database names
  private $valid_fields = array(
    'id' => array('hc', 'id'),
    'title' => array('hc', 'title', TRUE),
    'content_type_id' => array('hl', 'name'),
    'content_type' => array('hl', 'title', TRUE),
    'slug' => array('hc', 'slug', TRUE),
    'created_at' => array('hc', 'created_at'),
    'updated_at' => array('hc', 'updated_at'),
    'user_id' => array('u', 'ID'),
    'user_name' => array('u', 'display_name', TRUE),
    'tags' =>  array('t', 'GROUP_CONCAT(DISTINCT CONCAT(t.id,\',\',t.name) ORDER BY t.id SEPARATOR \';\')')
  );

  private $fields, $join, $where, $where_args, $order_by, $limit, $limit_args;

  /**
   * Constructor
   *
   * @since 1.5.3
   * @param array $fields List of fields to return.
   *   Valid values are: id, title, content_type, created_at, updated_at, user_id, user_name
   * @param int $offset Skip this many rows.
   * @param int $limit Max number of rows to return.
   * @param string $order_by Field to order content by.
   * @param bool $reverse_order Reverses the ordering.
   * @param array $filters
   *   Must be defined like so: array(array('field', 'Cool Content', 'LIKE'))
   */
  public function __construct($fields, $offset = NULL, $limit = NULL, $order_by = NULL, $reverse_order = NULL, $filters = NULL) {
    global $wpdb;

    $this->base_table = "{$wpdb->prefix}h5p_contents hc";
    $this->valid_joins = array(
      'hl' => " LEFT JOIN {$wpdb->prefix}h5p_libraries hl ON hl.id = hc.library_id",
      'u' => " LEFT JOIN {$wpdb->users} u ON hc.user_id = u.ID",
      't' => " LEFT JOIN {$wpdb->prefix}h5p_contents_tags ct ON ct.content_id = hc.id
               LEFT JOIN {$wpdb->prefix}h5p_tags t ON ct.tag_id = t.id
               LEFT JOIN {$wpdb->prefix}h5p_contents_tags ct2 ON ct2.content_id = hc.id"
    );

    $this->join = array();

    // Start adding fields
    $this->fields = '';
    foreach ($fields as $field) {
      $valid_field = $this->get_valid_field($field);
      $table = $valid_field[0];

      // Add join
      $this->add_join($table);

      // Add valid fields
      if ($this->fields) {
        $this->fields .= ', ';
      }
      if ($table !== 't') {
        $this->fields .= $table . '.';
      }
      $this->fields .= $valid_field[1] . ' AS ' . $field;
    }
    if (!$this->fields) {
      throw new Exception('No fields specified.');
    }

    // Add filters to data query
    $this->where = '';
    $this->where_args = array();

    if ($filters !== NULL) {
      foreach ($filters as $filter) {
        if (!isset($filter[0]) || !isset($filter[1])) {
          throw new Exception('Missing filter options.');
        }

        $field = $this->get_valid_field($filter[0]);

        // Add join
        $this->add_join($field[0]);

        // Add where
        $this->where .= ($this->where ? ' AND ' : ' WHERE ') . ($field[0] === 't' ? 'ct2.tag_id' : $field[0] . '.' . $field[1]);
        $this->where_args[] = $filter[1];

        // Check if operator is valid, if not use the first valid one.
        $operator = (isset($filter[2]) ? $filter[2] : '=');
        if (!isset($this->valid_operators[$operator])) {
          throw new Exception('Invalid operator: '. $operator);
        }
        $this->where .= $this->valid_operators[$operator];
      }
    }

    // Sort by
    $this->order_by = '';
    if ($order_by !== NULL) {
      $field = $this->get_valid_field($order_by);

      // Add join
      $this->add_join($field[0]);

      $dir = ($reverse_order ? TRUE : FALSE);
      if (isset($field[2])) {
        $dir = !$dir; // Reverse ordering of text fields
      }
      $this->order_by .= " ORDER BY {$field[0]}.{$field[1]} " . ($dir ? 'ASC' : 'DESC');
    }

    // Add joins
    $this->join = join('', $this->join);

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
   * Makes it easier to validate a field while processing fields.
   *
   * @since 1.5.3
   * @param string $field
   * @return array
   */
  private function get_valid_field($field) {
    if (!isset($this->valid_fields[$field])) {
      throw new Exception('Invalid field: ' . $field);
    }

    return $this->valid_fields[$field];
  }

  /**
   * Makes it easier to add valid joins while processing fields.
   *
   * @since 1.5.3
   * @param string $table
   */
  private function add_join($table) {
    if ($table === 'hc' || !is_array($this->join)) {
      return; // Do not join base table.
    }

    if (isset($this->join[$table])) {
      return; // Only add if missing
    }

    // Check if table is valid
    if (!isset($this->valid_joins[$table])) {
      throw new Exception('Invalid table: ' . $table);
    }

    // Add join
    $this->join[$table] = $this->valid_joins[$table];
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
      GROUP BY hc.id
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

    $query = "SELECT COUNT(DISTINCT hc.id)
      FROM {$this->base_table}
      {$this->join}
      {$this->where}";

    if (!empty($this->where_args)) {
      // We need to prep if we have args
      $query = $wpdb->prepare($query, $this->where_args);
    }
    return (int) $wpdb->get_var($query);
  }
}
