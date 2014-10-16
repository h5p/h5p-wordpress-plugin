<?php
/**
 * H5P Plugin.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2014 Joubel
 */

/**
 * Plugin admin class.
 *
 * TODO: Add development mode
 * TODO: Fix custom permission for library admin
 *
 * @package H5P_Plugin_Admin
 * @author Joubel <contact@joubel.com>
 */
class H5P_Plugin_Admin {

  /**
   * Instance of this class.
   *
   * @since 1.0.0
   * @var \H5P_Plugin_Admin
   */
  protected static $instance = NULL;

  /**
   * @since 1.1.0
   */
  private $plugin_slug = NULL;

  /**
   * Keep track of the current content.
   *
   * @since 1.0.0
   */
  private $content = NULL;

  /**
   * Keep track of the current library.
   *
   * @since 1.1.0
   */
  private $library = NULL;

  /**
   * Initialize the plugin by loading admin scripts & styles and adding a
   * settings page and menu.
   *
   * @since 1.0.0
   */
  private function __construct() {
    $plugin = H5P_Plugin::get_instance();
    $this->plugin_slug = $plugin->get_plugin_slug();

    // Prepare admin pages / sections
    $this->content = new H5PContentAdmin($this->plugin_slug);
    $this->library = new H5PLibraryAdmin($this->plugin_slug);

    // Load admin style sheet and JavaScript.
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles_and_scripts'));

    // Add the options page and menu item.
    add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

    // Allow altering of page titles for different page actions.
    add_filter('admin_title', array($this, 'alter_title'), 10, 2);

    // Custom media button for inserting H5Ps.
    add_action('media_buttons_context', array($this->content, 'add_insert_button'));
    add_action('wp_ajax_h5p_contents', array($this->content, 'ajax_select_content'));

    // Editor ajax
    add_action('wp_ajax_h5p_libraries', array($this->content, 'ajax_libraries'));
    add_action('wp_ajax_h5p_files', array($this->content, 'ajax_files'));

    // AJAX for rebuilding all content caches
    add_action('wp_ajax_h5p_rebuild_cache', array($this->library, 'ajax_rebuild_cache'));

    // AJAX for content upgrade
    add_action('wp_ajax_h5p_content_upgrade_library', array($this->library, 'ajax_upgrade_library'));
    add_action('wp_ajax_h5p_content_upgrade_progress', array($this->library, 'ajax_upgrade_progress'));

    // AJAX for logging results
    add_action('wp_ajax_h5p_setFinished', array($this, 'ajax_results'));

    // AJAX for display content results
    add_action('wp_ajax_h5p_content_results', array($this->content, 'ajax_content_results'));
  }

  /**
   * Return an instance of this class.
   *
   * @since 1.0.0
   * @return \H5P_Plugin_Admin A single instance of this class.
   */
  public static function get_instance() {
    // If the single instance hasn't been set, set it now.
    if (null == self::$instance) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  /**
   * Register and enqueue admin-specific style sheet.
   *
   * @since 1.0.0
   */
  public function enqueue_admin_styles_and_scripts() {
    $plugin = H5P_Plugin::get_instance();
    $plugin->enqueue_styles_and_scripts();
    wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('styles/admin.css', __FILE__), array(), H5P_Plugin::VERSION);
  }

  /**
   * Register the administration menu for this plugin into the WordPress Dashboard menu.
   *
   * @since 1.0.0
   */
  public function add_plugin_admin_menu() {
    // H5P Content pages
    $h5p_content = __('H5P Content', $this->plugin_slug);
    add_menu_page($h5p_content, $h5p_content, 'edit_h5p_contents', $this->plugin_slug, array($this->content, 'display_contents_page'), 'none');

    $all_h5p_content = __('All H5P Content', $this->plugin_slug);
    add_submenu_page($this->plugin_slug, $all_h5p_content, $all_h5p_content, 'edit_h5p_contents', $this->plugin_slug, array($this->content, 'display_contents_page'));

    $add_new = __('Add New', $this->plugin_slug);
    $contents_page = add_submenu_page($this->plugin_slug, $add_new, $add_new, 'edit_h5p_contents', $this->plugin_slug . '_new', array($this->content, 'display_new_content_page'));

    // Process form data when saving H5Ps.
    add_action('load-' . $contents_page, array($this->content, 'process_new_content'));

    $libraries = __('Libraries', $this->plugin_slug);
    $libraries_page = add_submenu_page($this->plugin_slug, $libraries, $libraries, 'manage_h5p_libraries', $this->plugin_slug . '_libraries', array($this->library, 'display_libraries_page'));

    // Process form data when upload H5Ps without content.
    add_action('load-' . $libraries_page, array($this->library, 'process_libraries'));

//    $results = __('Results', $this->plugin_slug);
//    add_submenu_page($this->plugin_slug, $results, $results, 'view_h5p_results', $this->plugin_slug . '_results', array($this, 'display_user_results_page'));

    // Settings page
    add_options_page('H5P Settings', 'H5P', 'manage_options', $this->plugin_slug . '_settings', array($this, 'display_settings_page'));
  }

  /**
   * Display a settings page for H5P.
   *
   * @since 1.0.0
   */
  public function display_settings_page() {
    $save = filter_input(INPUT_POST, 'save_these_settings');
    if ($save !== NULL) {
      check_admin_referer('h5p_settings', 'save_these_settings'); // Verify form

      $export = filter_input(INPUT_POST, 'h5p_export', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_export', $export ? TRUE : FALSE);

      $icon = filter_input(INPUT_POST, 'h5p_icon', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_icon', $icon ? TRUE : FALSE);

      $track_user = filter_input(INPUT_POST, 'h5p_track_user', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_track_user', $track_user ? TRUE : FALSE);
    }
    else {
      $export = get_option('h5p_export', TRUE);
      $icon = get_option('h5p_icon', TRUE);
      $track_user = get_option('h5p_track_user', TRUE);
    }

    include_once('views/settings.php');
  }

  /**
   * Load content and add to title for certain pages.
   * Should we have used get_current_screen() ?
   *
   * @since 1.1.0
   * @param string $admin_title
   * @param string $title
   * @return string
   */
  public function alter_title($admin_title, $title) {
    $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);

    switch ($page) {
      case 'h5p':
      case 'h5p_new':
        return $this->content->alter_title($page, $admin_title, $title);

      case 'h5p_libraries':
        return $this->library->alter_title($page, $admin_title, $title);
    }

    return $admin_title;
  }

  /**
   * Handle upload of new H5P content file.
   *
   * @since 1.1.0
   * @param array $content
   * @return boolean
   */
  public function handle_upload($content = NULL, $only_upgrade = NULL) {
    $plugin = H5P_Plugin::get_instance();
    $validator = $plugin->get_h5p_instance('validator');
    $interface = $plugin->get_h5p_instance('interface');

    // Move so core can validate the file extension.
    rename($_FILES['h5p_file']['tmp_name'], $interface->getUploadedH5pPath());

    $skipContent = ($content === NULL);
    if ($validator->isValidPackage($skipContent, $only_upgrade) && ($skipContent || $content['title'] !== NULL)) {
      if (isset($content['id'])) {
        $interface->deleteLibraryUsage($content['id']);
      }
      $storage = $plugin->get_h5p_instance('storage');
      $storage->savePackage($content, NULL, $skipContent, $only_upgrade);
      return $storage->contentId;
    }

    // The uploaded file was not a valid H5P package
    @unlink($interface->getUploadedH5pPath());
    return FALSE;
  }

  /**
   * Set error message.
   *
   * @param string $message
   */
  public static function set_error($message) {
    $plugin = H5P_Plugin::get_instance();
    $interface = $plugin->get_h5p_instance('interface');
    $interface->setErrorMessage($message);
  }

  /**
   * Print messages.
   *
   * @since 1.0.0
   */
  public static function print_messages() {
    $plugin = H5P_Plugin::get_instance();
    $interface = $plugin->get_h5p_instance('interface');

    foreach (array('updated', 'error') as $type) {
      $messages = $interface->getMessages($type);
      if (!empty($messages)) {
        print '<div class="' . $type . '"><ul>';
        foreach ($messages as $message) {
          print '<li>' . $message . '</li>';
        }
        print '</ul></div>';
      }
    }
  }

  /**
   * Get proper handle for the given asset
   *
   * @since 1.1.0
   * @param string $path
   * @return string
   */
  private static function asset_handle($path) {
    $plugin = H5P_Plugin::get_instance();
    return $plugin->asset_handle($path);
  }

  /**
   * Small helper for simplifying script enqueuing.
   *
   * @since 1.1.0
   * @param string $handle
   * @param string $path
   */
  public static function add_script($handle, $path) {
    wp_enqueue_script(self::asset_handle($handle), plugins_url('h5p/' . $path), array(), H5P_Plugin::VERSION);
  }

  /**
   * Small helper for simplifying style enqueuing.
   *
   * @since 1.1.0
   * @param string $handle
   * @param string $path
   */
  public static function add_style($handle, $path) {
    wp_enqueue_style(self::asset_handle($handle), plugins_url('h5p/' . $path), array(), H5P_Plugin::VERSION);
  }

  /**
   * Handle user results reported by the H5P content.
   *
   * @since 1.2.0
   */
  public function ajax_results() {
    global $wpdb;

    $content_id = filter_input(INPUT_POST, 'contentId', FILTER_VALIDATE_INT);
    if (!$content_id) {
      return;
    }

    $user_id = get_current_user_id();
    $result_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id
        FROM {$wpdb->prefix}h5p_results
        WHERE user_id = %d
        AND content_id = %d",
        $user_id,
        $content_id
    ));

    $table = $wpdb->prefix . 'h5p_results';
    $data = array(
      'score' => filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT),
      'max_score' => filter_input(INPUT_POST, 'maxScore', FILTER_VALIDATE_INT),
      'opened' => filter_input(INPUT_POST, 'opened', FILTER_VALIDATE_INT),
      'finished' => filter_input(INPUT_POST, 'finished', FILTER_VALIDATE_INT),
      'time' => filter_input(INPUT_POST, 'time', FILTER_VALIDATE_INT)
    );
    $format = array(
      '%d',
      '%d',
      '%d',
      '%d',
      '%d'
    );

    if (!$result_id) {
      // Insert new results
      $data['user_id'] = $user_id;
      $format[] = '%d';
      $data['content_id'] = $content_id;
      $format[] = '%d';
      $wpdb->insert($table, $data, $format);
    }
    else {
      // Update existing results
      $wpdb->update($table, $data, array('id' => $result_id), $format, array('%d'));
    }
  }

  /**
   * Create the where part of the results queries.
   *
   * @since 1.2.0
   * @param array $query_args
   * @param int $content_id
   * @param int $user_id
   * @return array
   */
  private function get_results_query_where(&$query_args, $content_id = NULL, $user_id = NULL) {
    if ($content_id !== NULL) {
      $where = ' WHERE hr.content_id = %d';
      $query_args[] = $content_id;
    }
    if ($user_id !== NULL) {
      $where = (isset($where) ? $where . ' AND' : ' WHERE') . ' hr.user_id = %d';
      $query_args[] = $user_id;
    }
    return (isset($where) ? $where : '');
  }

  /**
   * Find number of results.
   *
   * @since 1.2.0
   * @param int $content_id
   * @param int $user_id
   * @return int
   */
  public function get_results_num($content_id = NULL, $user_id = NULL) {
    global $wpdb;

    $query_args = array();
    return (int) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(id) FROM {$wpdb->prefix}h5p_results hr" .
        $this->get_results_query_where($query_args, $content_id, $user_id),
      $query_args
    ));
  }

  /**
   * Handle user results reported by the H5P content.
   *
   * @since 1.2.0
   * @param int $content_id
   * @param int $user_id
   * @return array
   */
  public function get_results($content_id = NULL, $user_id = NULL, $offset = 0, $limit = 20) {
    global $wpdb;

    if ($limit > 100) {
      $limit = 100; // Prevent wrong use
    }

    $extra_fields = '';
    $joins = '';
    if ($content_id === NULL) {
      $extra_fields .= " hr.content_id, hc.title AS content_title,";
      $joins .= " LEFT JOIN {$wpdb->prefix}h5p_contents hc ON hr.content_id = hc.id";
    }
    if ($user_id === NULL) {
      $extra_fields .= " hr.user_id, u.user_login AS user_name,";
      $joins .= " LEFT JOIN {$wpdb->prefix}users u ON hr.user_id = u.ID";
    }

    $query_args = array();
    $where = $this->get_results_query_where($query_args, $content_id, $user_id);

    return $wpdb->get_results($wpdb->prepare(
      "SELECT hr.id,
              {$extra_fields}
              hr.score,
              hr.max_score,
              hr.opened,
              hr.finished,
              hr.time
        FROM {$wpdb->prefix}h5p_results hr
        {$joins}
        {$where}
        LIMIT {$offset}, {$limit}",
      $query_args
    ));
  }
}
