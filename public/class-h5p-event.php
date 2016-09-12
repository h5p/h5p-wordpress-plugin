<?php

/**
 * Makes it easy to track events throughout the H5P system.
 *
 * @package    H5P
 * @copyright  2016 Joubel AS
 * @license    MIT
 */
class H5P_Event extends H5PEventBase {
  private $user;

  /**
   * Adds event type, h5p library and timestamp to event before saving it.
   *
   * @param string $type
   *  Name of event to log
   * @param string $library
   *  Name of H5P library affacted
   */
  function __construct($type, $sub_type = NULL, $content_id = NULL, $content_title = NULL, $library_name = NULL, $library_version = NULL) {

    // Track the user who initiated the event as well
    $current_user = wp_get_current_user();
    $this->user = $current_user->ID;

    parent::__construct($type, $sub_type, $content_id, $content_title, $library_name, $library_version);
  }

  /**
   * Store the event.
   */
  protected function save() {
    global $wpdb;

    // Get data in array format without NULL values
    $data = $this->getDataArray();
    $format = $this->getFormatArray();

    // Add user
    $data['user_id'] = $this->user;
    $format[] = '%d';

    // Insert into DB
    $wpdb->insert("{$wpdb->prefix}h5p_events", $data, $format);
    $this->id = $wpdb->insert_id;
    return $this->id;
  }

  /**
   * Count number of events.
   */
  protected function saveStats() {
    global $wpdb;

    $type = $this->type . ' ' . $this->sub_type;
    $current_num = $wpdb->get_var($wpdb->prepare(
        "SELECT num
           FROM {$wpdb->prefix}h5p_counters
          WHERE type = '%s'
            AND library_name = '%s'
            AND library_version = '%s'
        ", $type, $this->library_name, $this->library_version));

    if ($current_num === NULL) {
      // Insert
      $wpdb->insert("{$wpdb->prefix}h5p_counters", array(
        'type' => $type,
        'library_name' => $this->library_name,
        'library_version' => $this->library_version,
        'num' => 1
      ), array('%s','%s','%s','%d'));
    }
    else {
      // Update num+1
      $wpdb->query($wpdb->prepare(
          "UPDATE {$wpdb->prefix}h5p_counters
              SET num = num + 1
            WHERE type = '%s'
              AND library_name = '%s'
              AND library_version = '%s'
      		", $type, $this->library_name, $this->library_version));
    }
  }
}
