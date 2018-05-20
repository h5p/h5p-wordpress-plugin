<?php
/**
 * H5P Plugin.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2018 Joubel
 */

/**
 * Privacy policy class.
 *
 * @package H5P_Plugin_Admin
 * @author Joubel <contact@joubel.com>
 */
class H5PPrivacyPolicy {

  /**
   * @since 1.10.2
   */
  private $plugin_slug = NULL;

  /**
   * @since 1.10.2
   */
  private $PAGE_LENGTH = 25;

  /**
   * Initialize.
   *
   * @since 1.10.2
   */
  public function __construct($plugin_slug) {
    $this->plugin_slug = $plugin_slug;
  }

  /**
   * Add the privacy policy suggestion text.
   *
   * @since 1.10.2
   */
  public function add_privacy_policy_content() {
    if (!function_exists('wp_add_privacy_policy_content')) {
      return;
    }
    $content = sprintf(__('H5P TODO', $this->plugin_slug));

    wp_add_privacy_policy_content($this->plugin_slug, wp_kses_post(wpautop($content, false)));
  }

  /**
   * Get results data.
   *
   * @since 1.10.2
   * @param int $wpid WordPress User ID.
   * @param int $page Exporter page.
   * @return array Database results.
   */
  function get_user_results($wpid, $page) {
    global $wpdb;

    return $wpdb->get_results($wpdb->prepare(
      "
      SELECT
        res.content_id,
        res.user_id,
        res.score,
        res.max_score,
        res.opened,
        res.finished,
        res.time,
        con.title
      FROM
        {$wpdb->prefix}h5p_results AS res,
        {$wpdb->prefix}h5p_contents AS con
      WHERE
        res.user_id = %d AND
        res.content_id = con.id
      LIMIT
        %d, %d
      ",
      $wpid,
      ($page - 1) * $this->PAGE_LENGTH, // page start
      $this->PAGE_LENGTH // to page end
    ));
  }

  /**
   * Get saved content state data.
   *
   * @since 1.10.2
   * @param int $wpid WordPress User ID.
   * @param int $page Exporter page.
   * @return array Database results.
   */
  function get_user_saved_content_state($wpid, $page) {
    global $wpdb;

    return $wpdb->get_results($wpdb->prepare(
      "
      SELECT
        scs.content_id,
        scs.sub_content_id,
        scs.user_id,
        scs.data_id,
        scs.data,
        scs.preload,
        scs.invalidate,
        scs.updated_at,
        con.title
      FROM
        {$wpdb->prefix}h5p_contents_user_data AS scs,
        {$wpdb->prefix}h5p_contents AS con
      WHERE
        scs.user_id = %d AND
        scs.content_id = con.id
      LIMIT
        %d, %d
      ",
      $wpid,
      ($page - 1) * $this->PAGE_LENGTH, // page start
      $this->PAGE_LENGTH // to page end
    ));
  }

  /**
   * Amend export items with personal data.
   *
   * @since 1.10.2
   * @param int $wpid WordPress User ID.
   * @param array &$export_items Current export items.
   * @param int $page Export page.
   * @return int Number of items amended.
   */
  function add_export_items_results($wpid, &$export_items, $page) {
    $items = $this->get_user_results($wpid, $page);

    foreach ($items as $item) {
      // Set time related parameters
      $datetimeformat = get_option('date_format') . ' ' . get_option('time_format');
      $offset = get_option('gmt_offset') * 3600;

      // Compute time
      if ($item->time === '0') {
        $item->time = $item->finished - $item->opened;
      }
      $seconds = ($item->time % 60);
      $item->time = floor($item->time / 60) . ':' . ($seconds < 10 ? '0' : '') . $seconds;

      // Build data
      $data = array(
        array(
          'name' => __('Content', $this->plugin_slug),
          'value' => $item->title . ' (ID: ' . $item->content_id . ')'
        ),
        array(
          'name' => __('User ID', $this->plugin_slug),
          'value' => $item->user_id
        ),
        array(
          'name' => __('Score', $this->plugin_slug),
          'value' => $item->user_id
        ),
        array(
          'name' => __('Maximum Score', $this->plugin_slug),
          'value' => $item->user_id
        ),
        array(
          'name' => __('Opened', $this->plugin_slug),
          'value' => date($datetimeformat, $offset + $item->opened)
        ),
        array(
          'name' => __('Finished', $this->plugin_slug),
          'value' => date($datetimeformat, $offset + $item->finished)
        ),
        array(
          'name' => __('Time spent', $this->plugin_slug),
          'value' => $item->time
        )
      );

      // Amend export items
      $export_items[] = array(
        'group_id' => 'h5p-results',
        'group_label' => strtoupper($this->plugin_slug) . ' ' . __('Results', $this->plugin_slug),
        'item_id' => 'h5p-results-' . $item->content_id,
        'data' => $data
      );
    }
    return count($items);
  }

  /**
   * Amend export items with items from saved content state.
   *
   * @since 1.10.2
   * @param int $wpid WordPress User ID.
   * @param array &$export_items Current export items.
   * @param int $page Export page.
   * @return int Number of items amended.
   */
  function add_export_items_saved_content_state($wpid, &$export_items, $page) {
    $items = $this->get_user_saved_content_state($wpid, $page);

    foreach ($items as $item) {
      $data = array(
        array(
          'name' => __('Content', $this->plugin_slug),
          'value' => $item->title . ' (ID: ' . $item->content_id . ')'
        ),
        array(
          'name' => __('User ID', $this->plugin_slug),
          'value' => $item->user_id
        ),
        array(
          'name' => __('Subcontent ID', $this->plugin_slug),
          'value' => $item->sub_content_id
        ),
        array(
          'name' => __('Data ID', $this->plugin_slug),
          'value' => $item->data_id
        ),
        array(
          'name' => __('Data', $this->plugin_slug),
          'value' => $item->data
        ),
        array(
          'name' => __('Preload', $this->plugin_slug),
          'value' => $item->preload
        ),
        array(
          'name' => __('Invalidate', $this->plugin_slug),
          'value' => $item->invalidate
        ),
        array(
          'name' => __('Updated at', $this->plugin_slug),
          'value' => $item->updated_at
        )
      );

      $export_items[] = array(
        'group_id' => 'h5p-saved-content-state',
        'group_label' => strtoupper($this->plugin_slug) . ' ' . __('Saved content state', $this->plugin_slug),
        'item_id' => 'h5p-saved-content-state-' . $item->content_id,
        'data' => $data
      );
    }
    return count($items);
  }

  /**
   * Add exporter for personal data.
   *
   * @since 1.10.2
   * @param string $email Email address.
   * @param int $page Exporter page.
   * @return array Export results.
   */
  function h5p_exporter($email, $page = 1) {
    $page = (int) $page;

    $export_items = array();
    $wp_user = get_user_by('email', $email);

    if ($wp_user) {
      $length = array();
      $length[] = $this->add_export_items_results($wp_user->ID, $export_items, $page);
      $length[] = $this->add_export_items_saved_content_state($wp_user->ID, $export_items, $page);
    }

    return array(
      'data' => $export_items,
      'done' => max($length) < $this->PAGE_LENGTH
    );
  }

  /**
   * Register exporter for results.
   *
   * @since 1.10.2
   * @param array $exporters Exporters.
   * @return array Exporters.
   */
  public function register_h5p_exporter($exporters) {
    $exporters[$this->plugin_slug . '-exporter'] = array(
      'exporter_friendly_name' => $this->plugin_slug . '-exporter',
      'callback' => array(
        $this,
        'h5p_exporter'
      )
    );
    return $exporters;
  }

  /**
   * Add eraser for personal data.
   *
   * @since 1.10.2
   * @param string $email Email address.
   * @param int $page Eraser page.
   * @return array Eraser results.
   */
  function h5p_eraser($email, $page = 1) {
    global $wpdb;

    $erase_items = array();

    $wp_user = get_user_by('email', $email);
    if ($wp_user) {
      $length = array();
      $length[] = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}h5p_results WHERE user_id = %d LIMIT %d",
        $wp_user->ID,
        $this->PAGE_LENGTH
      ));
      $length[] = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}h5p_contents_user_data WHERE user_id = %d LIMIT %d",
        $wp_user->ID,
        $this->PAGE_LENGTH
      ));
    }

    return array(
      'items_removed' => max($length),
      'items_retained' => false,
      'messages' => array(),
      'done' => max($length) < $this->PAGE_LENGTH
    );
  }

  /**
   * Register eraser for results.
   *
   * @since 1.10.2
   * @param array $erasers Erasers.
   * @return array Erasers.
   */
  public function register_h5p_eraser($erasers) {
    $erasers[$this->plugin_slug . '-eraser'] = array(
      'eraser_friendly_name' => $this->plugin_slug . '-eraser',
      'callback' => array(
        $this,
        'h5p_eraser'
      )
    );
    return $erasers;
  }
}
