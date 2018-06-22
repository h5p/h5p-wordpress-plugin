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
 * @link https://developer.wordpress.org/plugins/privacy/ Documentation
 */
class H5PPrivacyPolicy {

  /**
   * @since 1.10.2
   */
  private $plugin_slug = NULL;

  /**
   * Default page length for exporters and erasers (to avoid timeouts)
   *
   * @since 1.10.2
   */
  const PAGE_LENGTH = 25;

  /**
   * Initialize.
   *
   * @since 1.10.2
   */
  public function __construct($plugin_slug) {
    $this->plugin_slug = $plugin_slug;
  }

  /**
   * Add privacy policy suggestion text.
   *
   * @since 1.10.2
   */
  public function add_privacy_policy_content() {
    if (!function_exists('wp_add_privacy_policy_content')) {
      return;
    }

    // Links
    $link_xapi = sprintf(
      '<a href="https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md" target="_blank">%s</a>',
    	__( "xAPI", $this->plugin_slug)
    );
    $link_h5p_tracking = sprintf(
      '<a href="https://h5p.org/tracking-the-usage-of-h5p" target="_blank">%s</a>',
    	__( "H5P tracking information", $this->plugin_slug)
    );
    $link_google = sprintf(
      '<a href="https://policies.google.com/privacy">%s</a>',
      __("Google's Privacy policy", $this->plugin_slug)
    );
    $link_twitter = sprintf(
      '<a href="https://twitter.com/en/privacy">%s</a>',
      __("Twitter's Privacy policy", $this->plugin_slug)
    );

    // Intentionally using no identifier here, as this is a WordPress headline.
    $content  = '<h2>' . __("What personal data we collect and why we collect it") . '</h2>';
    $content .= '<h3>' . strtoupper(__($this->plugin_slug, $this->plugin_slug)) . '</h3>';
    $content .= '<p class="privacy-policy-tutorial"><strong>' . __('Suggested text ("We" and "our" mean "you", not "us"!):', $this->plugin_slug) . '</strong></p>';
    $content .= '<p class="privacy-policy-tutorial">' . sprintf(__("We may process and store personal data about your interactions using %s. We use the data to learn about how well the interactions are designed and how it could be adapted to improve the usability and your learning outcomes. The data is processed and stored [on our platform|on an external platform] until further notice.", $this->plugin_slug), $link_xapi) . '</p>';
    $content .= '<p class="privacy-policy-tutorial">' . __("We may store the results of your interactions on our platform until further notice. The results may contain your score, the maximum score possible, when you started, when you finished, and how much time you used. We use the results to learn about how well you performed and to help us give you feedback.", $this->plugin_slug) . '</p>';
    $content .= '<p class="privacy-policy-tutorial">' . sprintf(__("We may store interactive content that you create on our platform. We also may send anonymized reports about content creation without any personal data to the plugin creators. Please consult the %s page for details.", $this->plugin_slug), $link_h5p_tracking) . '</p>';
    $content .= '<p class="privacy-policy-tutorial">' . sprintf(__("If you use interactive content that contains a video that is hosted on YouTube, YouTube will set cookies on your computer. YouTube uses these cookies to help them and their partners to analyze the traffic to their websites. Please consult %s for details. It is our legitimate interest to use YouTube, because we we need their services for our interactive content and would not be able to provide you with their video content features otherwise.", $this->plugin_slug), $link_google) . '</p>';
    $content .= '<p class="privacy-policy-tutorial">' . sprintf(__("If you use interactive content that contains a Twitter feed, Twitter will set a cookie on your computer. Twitter uses these cookies to help them and their partners to make their advertizing more relevant to you. Please consult %s for details. It is our legitimate interest to use Twitter, because we need their services for our interactive content and would not be able to provide you with it otherwise.", $this->plugin_slug), $link_twitter) . '</p>';
    $content .= '<p class="privacy-policy-tutorial">' . sprintf(__("If you use interactive content that contains speech recognition, Google Cloud will process your voice for converting it to text. Please consult %s for details. It is our legitimate interest to use Google Cloud, because we we need their services for our interactive content and would not be able to provide you with speech recognition features otherwise.", $this->plugin_slug), $link_google) . '</p>';

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
      ($page - 1) * self::PAGE_LENGTH, // page start
      self::PAGE_LENGTH // to page end
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
      ($page - 1) * self::PAGE_LENGTH, // page start
      self::PAGE_LENGTH // to page end
    ));
  }

  /**
   * Get events data.
   *
   * @since 1.10.2
   * @param int $wpid WordPress User ID.
   * @param int $page Exporter page.
   * @return array Database results.
   */
  function get_user_events($wpid, $page) {
    global $wpdb;

    return $wpdb->get_results($wpdb->prepare(
      "
      SELECT
        *
      FROM
        {$wpdb->prefix}h5p_events
      WHERE
        user_id = %d
      LIMIT
        %d, %d
      ",
      $wpid,
      ($page - 1) * self::PAGE_LENGTH, // page start
      self::PAGE_LENGTH // to page end
    ));
  }

  /**
   * Get contents data.
   *
   * @since 1.10.2
   * @param int $wpid WordPress User ID.
   * @param int $page Exporter page.
   * @return array Database results.
   */
  function get_user_contents($wpid, $page) {
    global $wpdb;

    return $wpdb->get_results($wpdb->prepare(
      "
      SELECT
        con.id,
        con.created_at,
        con.updated_at,
        con.user_id,
        con.title,
        con.library_id,
        con.parameters,
        con.filtered,
        con.slug,
        con.embed_type,
        con.disable,
        con.content_type,
        lib.title AS library_title
      FROM
        {$wpdb->prefix}h5p_contents AS con,
        {$wpdb->prefix}h5p_libraries AS lib
      WHERE
        con.user_id = %d AND
        con.library_id = lib.id
      LIMIT
        %d, %d
      ",
      $wpid,
      ($page - 1) * self::PAGE_LENGTH, // page start
      self::PAGE_LENGTH // to page end
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

    // Set time related parameters
    $datetimeformat = get_option('date_format') . ' ' . get_option('time_format');
    $offset = get_option('gmt_offset') * 3600;

    foreach ($items as $item) {
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
   * Amend export items with items from events.
   *
   * @since 1.10.2
   * @param int $wpid WordPress User ID.
   * @param array &$export_items Current export items.
   * @param int $page Export page.
   * @return int Number of items amended.
   */
  function add_export_items_events($wpid, &$export_items, $page) {
    $items = $this->get_user_events($wpid, $page);

    // Set time related parameters
    $datetimeformat = get_option('date_format') . ' ' . get_option('time_format');
    $offset = get_option('gmt_offset') * 3600;

    foreach ($items as $item) {
      $data = array(
        array(
          'name' => __('ID', $this->plugin_slug),
          'value' => $item->id
        ),
        array(
          'name' => __('User ID', $this->plugin_slug),
          'value' => $item->user_id
        ),
        array(
          'name' => __('Created at', $this->plugin_slug),
          'value' => date($datetimeformat, $offset + $item->created_at)
        ),
        array(
          'name' => __('Type', $this->plugin_slug),
          'value' => $item->type
        ),
        array(
          'name' => __('Sub type', $this->plugin_slug),
          'value' => $item->sub_type
        ),
        array(
          'name' => __('Content', $this->plugin_slug),
          'value' => $item->content_title . ' (ID: ' . $item->content_id . ')'
        ),
        array(
          'name' => __('Library', $this->plugin_slug),
          'value' => $item->library_name . ' ' . $item->library_version
        ),
      );

      $export_items[] = array(
        'group_id' => 'h5p-events',
        'group_label' => strtoupper($this->plugin_slug) . ' ' . __('Events', $this->plugin_slug),
        'item_id' => 'h5p-events-' . $item->id,
        'data' => $data
      );
    }
    return count($items);
  }

  /**
   * Amend export items with items from contents.
   *
   * @since 1.10.2
   * @param int $wpid WordPress User ID.
   * @param array &$export_items Current export items.
   * @param int $page Export page.
   * @return int Number of items amended.
   */
  function add_export_items_contents($wpid, &$export_items, $page) {
    $items = $this->get_user_contents($wpid, $page);

    foreach ($items as $item) {
      $data = array(
        array(
          'name' => __('ID', $this->plugin_slug),
          'value' => $item->id
        ),
        array(
          'name' => __('Title', $this->plugin_slug),
          'value' => $item->title
        ),
        array(
          'name' => __('Created at', $this->plugin_slug),
          'value' => $item->created_at
        ),
        array(
          'name' => __('Updated at', $this->plugin_slug),
          'value' => $item->updated_at
        ),
        array(
          'name' => __('User ID', $this->plugin_slug),
          'value' => $item->user_id
        ),
        array(
          'name' => __('Library', $this->plugin_slug),
          'value' => $item->library_title . ' (ID: ' . $item->library_id . ')'
        ),
        array(
          'name' => __('Parameters', $this->plugin_slug),
          'value' => $item->parameters
        ),
        array(
          'name' => __('Filtered parameters', $this->plugin_slug),
          'value' => $item->filtered
        ),
        array(
          'name' => __('Slug', $this->plugin_slug),
          'value' => $item->slug
        ),
        array(
          'name' => __('Embed type', $this->plugin_slug),
          'value' => $item->embed_type
        ),
        array(
          'name' => __('Disable', $this->plugin_slug),
          'value' => $item->disable
        ),
        array(
          'name' => __('Content type', $this->plugin_slug),
          'value' => $item->content_type
        )
        // TODO: Will have to be amended when we add the new metadata fields
      );

      $export_items[] = array(
        'group_id' => 'h5p-contents',
        'group_label' => strtoupper($this->plugin_slug) . ' ' . __('Contents', $this->plugin_slug),
        'item_id' => 'h5p-contents-' . $item->id,
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
      $length[] = $this->add_export_items_events($wp_user->ID, $export_items, $page);
      $length[] = $this->add_export_items_contents($wp_user->ID, $export_items, $page);
    }

    return array(
      'data' => $export_items,
      'done' => max($length) < self::PAGE_LENGTH
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

    // Get ID of the "oldest" admin
    $users = get_users(array('role' => 'administrator', 'number' => 1));
    $admin_prime_id = $users[0]->ID;

    $erase_items = array();

    $wp_user = get_user_by('email', $email);
    if ($wp_user) {
      $length = array();
      $length[] = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}h5p_results WHERE user_id = %d LIMIT %d",
        $wp_user->ID,
        self::PAGE_LENGTH
      ));
      $length[] = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}h5p_contents_user_data WHERE user_id = %d LIMIT %d",
        $wp_user->ID,
        self::PAGE_LENGTH
      ));
      $length[] = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}h5p_events WHERE user_id = %d LIMIT %d",
        $wp_user->ID,
        self::PAGE_LENGTH
      ));
      $length[] = $wpdb->query($wpdb->prepare(
        // Only anonymize data by linking them to the admin
        "UPDATE {$wpdb->prefix}h5p_contents SET user_id = %d WHERE user_id = %d LIMIT %d",
        $admin_prime_id,
        $wp_user->ID,
        self::PAGE_LENGTH
      ));
    }

    return array(
      'items_removed' => max($length),
      'items_retained' => false,
      'messages' => array(),
      'done' => max($length) < self::PAGE_LENGTH
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
