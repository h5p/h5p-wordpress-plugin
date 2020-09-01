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
 * TODO: Move results stuff to seperate class
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
    $this->privacy = new H5PPrivacyPolicy($this->plugin_slug);

    // Initialize admin area.
    $this->add_privacy_features();

    // Load admin style sheet and JavaScript.
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles_and_scripts'));

    // Add the options page and menu item.
    add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

    // Allow altering of page titles for different page actions.
    add_filter('admin_title', array($this, 'alter_title'), 10, 2);

    // Add settings link to plugin page
    add_filter('plugin_action_links_h5p/h5p.php', array($this, 'add_settings_link'));

    // Custom media button for inserting H5Ps.
    add_action('media_buttons', array($this->content, 'add_insert_button'));
    add_action('admin_footer', array($this->content, 'print_insert_content_scripts'));
    add_action('wp_ajax_h5p_insert_content', array($this->content, 'ajax_insert_content'));
    add_action('wp_ajax_h5p_inserted', array($this->content, 'ajax_inserted'));

    // Editor ajax
    add_action('wp_ajax_h5p_library-install', array($this->content, 'ajax_library_install'));
    add_action('wp_ajax_h5p_library-upload', array($this->content, 'ajax_library_upload'));
    add_action('wp_ajax_h5p_libraries', array($this->content, 'ajax_libraries'));
    add_action('wp_ajax_h5p_files', array($this->content, 'ajax_files'));
    add_action('wp_ajax_h5p_content-type-cache', array($this->content, 'ajax_content_type_cache'));
    add_action('wp_ajax_h5p_translations', array($this->content, 'ajax_translations'));
    add_action('wp_ajax_h5p_filter', array($this->content, 'ajax_filter'));

    // AJAX for rebuilding all content caches
    add_action('wp_ajax_h5p_rebuild_cache', array($this->library, 'ajax_rebuild_cache'));

    // AJAX for content upgrade
    add_action('wp_ajax_h5p_content_upgrade_library', array($this->library, 'ajax_upgrade_library'));
    add_action('wp_ajax_h5p_content_upgrade_progress', array($this->library, 'ajax_upgrade_progress'));

    // AJAX for handling content usage datas
    add_action('wp_ajax_h5p_contents_user_data', array($this, 'ajax_contents_user_data'));

    // AJAX for logging results
    add_action('wp_ajax_h5p_setFinished', array($this, 'ajax_results'));

    // AJAX for display content results
    add_action('wp_ajax_h5p_content_results', array($this->content, 'ajax_content_results'));

    // AJAX for display user results
    add_action('wp_ajax_h5p_my_results', array($this, 'ajax_my_results'));

    // AJAX for getting contents list
    add_action('wp_ajax_h5p_contents', array($this->content, 'ajax_contents'));

    // AJAX for restricting library access
    add_action('wp_ajax_h5p_restrict_library', array($this->library, 'ajax_restrict_access'));

    // Display admin notices
    add_action('admin_notices', array($this, 'admin_notices'));

    // Embed
    add_action('wp_ajax_h5p_embed', array($this, 'embed'));
    add_action('wp_ajax_nopriv_h5p_embed', array($this, 'embed'));

    // Remove user data and results
    add_action('deleted_user', array($this, 'deleted_user'));
  }

  /**
   * Display a form for adding and editing h5p content.
   *
   * @since 1.14.0
   */
  public function display_new_content_page($custom_view = NULL) {
    $this->content->display_new_content_page($custom_view);
  }

  /**
   * Display a form for adding and editing h5p content.
   *
   * @since 1.14.0
   */
  public function process_new_content($echo_on_success = NULL) {
    $this->content->process_new_content($echo_on_success);
  }

  /**
   * Add settings link to plugin overview page
   *
   * @since 1.6.0
   */
  function add_settings_link($links) {
    $links[] = '<a href="' . admin_url('options-general.php?page=h5p_settings') . '">Settings</a>';
    return $links;
  }

  /**
   * Print page for embed iframe
   *
   * @since 1.3.0
   */
  public function embed() {
    // Allow other sites to embed
    header_remove('X-Frame-Options');

    // Find content
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    if ($id !== NULL) {
      $plugin = H5P_Plugin::get_instance();
      $content = $plugin->get_content($id);
      if (!is_string($content)) {

        // Everyone is allowed to embed, set through settings
        $embed_allowed = (get_option('h5p_embed', TRUE) && !($content['disable'] & H5PCore::DISABLE_EMBED));

        /**
         * Allows other plugins to change the access permission for the
         * embedded iframe's content.
         *
         * @since 1.5.3
         *
         * @param bool $access
         * @param int $content_id
         * @return bool New access permission
         */
        $embed_allowed = apply_filters('h5p_embed_access', $embed_allowed, $id);

        if (!$embed_allowed) {
          // Check to see if embed URL always should be available
          $embed_allowed = (defined('H5P_EMBED_URL_ALWAYS_AVAILABLE') && H5P_EMBED_URL_ALWAYS_AVAILABLE);
        }

        if ($embed_allowed) {
          $lang = isset($content['metadata']['defaultLanguage'])
            ? $content['metadata']['defaultLanguage']
            : $plugin->get_language();
          $cache_buster = '?ver=' . H5P_Plugin::VERSION;

          // Get core settings
          $integration = $plugin->get_core_settings();
          // TODO: The non-content specific settings could be apart of a combined h5p-core.js file.

          // Get core scripts
          $scripts = array();
          foreach (H5PCore::$scripts as $script) {
            $scripts[] = plugins_url('h5p/h5p-php-library/' . $script) . $cache_buster;
          }

          // Get core styles
          $styles = array();
          foreach (H5PCore::$styles as $style) {
            $styles[] = plugins_url('h5p/h5p-php-library/' . $style) . $cache_buster;
          }

          // Get content settings
          $integration['contents']['cid-' . $content['id']] = $plugin->get_content_settings($content);
          $core = $plugin->get_h5p_instance('core');

          // Get content assets
          $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
          $files = $core->getDependenciesFiles($preloaded_dependencies);
          $plugin->alter_assets($files, $preloaded_dependencies, 'external');

          $scripts = array_merge($scripts, $core->getAssetsUrls($files['scripts']));
          $styles = array_merge($styles, $core->getAssetsUrls($files['styles']));

          $additional_embed_head_tags = array();

          /**
           * Add support for additional head tags for embedded content.
           * Very useful when adding xAPI events tracking code.
           *
           * @since 1.9.5
           *
           * @param array &$additional_embed_head_tags
           */
          do_action_ref_array('h5p_additional_embed_head_tags', array(&$additional_embed_head_tags));

          include_once(plugin_dir_path(__FILE__) . '../h5p-php-library/embed.php');

          // Log embed view
          new H5P_Event('content', 'embed',
              $content['id'],
              $content['title'],
              $content['library']['name'],
              $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']);
          exit;
        }
      }
    }

    // Simple unavailble page
    print '<body style="margin:0"><div style="background: #fafafa url(' . plugins_url('h5p/h5p-php-library/images/h5p.svg') . ') no-repeat center;background-size: 50% 50%;width: 100%;height: 100%;"></div><div style="width:100%;position:absolute;top:75%;text-align:center;color:#434343;font-family: Consolas,monaco,monospace">' . __('Content unavailable.', $this->plugin_slug) . '</div></body>';
    exit;
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
   * Print messages to admin UI.
   *
   * @since 1.3.0
   */
  public function admin_notices() {
    global $wpdb;

    // Check to make sure that the correct capabilities are assigned
    if (is_multisite()) {
      if (get_option('h5p_multisite_capabilities', 0) !== '1') {
        // Changed from single site to multsite, re-assign capabilities
        H5P_Plugin::assign_capabilities();
      }
    }
    else {
      if (get_option('h5p_multisite_capabilities', 0) === '1') {
        // Changed from multisite to single site, re-assign capabilities
        H5P_Plugin::assign_capabilities();
      }
    }

    // Gather all messages before printing
    $messages = array();

    // Always print a message after installing or upgrading
    $last_print = get_option('h5p_last_info_print', 0);
    if ($last_print !== H5P_Plugin::VERSION) {

      if ($last_print == 0) {
        // A new installation of the plugin
        $messages[] = __('Thank you for choosing H5P.', $this->plugin_slug);

        $messages[] = sprintf(wp_kses(__('You are ready to <a href="%s">start creating</a> interactive content. Check out our <a href="%s" target="_blank">Examples and Downloads</a> page for inspiration.', $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), admin_url('admin.php?page=h5p_new'), esc_url('https://h5p.org/content-types-and-applications'));
      }
      else {
        // Looks like we've just updated, always thank the user for updating.
        $messages[] = __('Thank you for staying up to date with H5P.', $this->plugin_slug);

        // Check for any version specific messages to show
        $v = H5P_Plugin::split_version($last_print);
        if ($v) {

          if ($v->major < 1 || ($v->major === 1 && $v->minor < 7) || ($v->major === 1 && $v->minor === 7 && $v->patch < 8)) { // < 1.7.8
            // Extra warning that content types should be updated in order to look good with the new editor design changes
            $messages[] = sprintf(wp_kses(__('You should <strong>upgrade your H5P content types!</strong> The old content types still work, but the authoring tool\'s look and feel is greatly improved with the new content types. Here\'s some more info about <a href="%s" target="_blank">upgrading the content types</a>.', $this->plugin_slug), array('strong' => array(), 'a' => array('href' => array(), 'target' => array()))), 'https://h5p.org/update-all-content-types');
          }

          // Notify about H5P Hub communication changes
          if ($v->major < 1 || ($v->major === 1 && $v->minor < 8)) {
            if (!get_option('h5p_ext_communication', TRUE)) {
              $messages[] = sprintf(__('H5P now fetches content types directly from the H5P Hub. In order to do this, the H5P plugin will communicate with H5P.org once per day to fetch information about new and updated content types. It will send in anonymous data to the hub about H5P usage. You may disable the data contribution and/or the H5P Hub in the H5P settings.', $this->plugin_slug));

              // Delete old variable
              delete_option('h5p_ext_communication');
              delete_option('h5p_update_available');
              delete_option('h5p_current_update');
              delete_option('h5p_update_available_path');
            }
          }
        }
      }

      // Always offer help
      $messages[] = sprintf(wp_kses(__('If you need any help you can always file a <a href="%s" target="_blank">Support Request</a>, check out our <a href="%s" target="_blank">Forum</a> or join the conversation in the <a href="%s" target="_blank">H5P Community Chat</a>.', $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), esc_url('https://wordpress.org/support/plugin/h5p'), esc_url('https://h5p.org/forum'), esc_url('https://gitter.im/h5p/CommunityChat'));
      update_option('h5p_last_info_print', H5P_Plugin::VERSION);
    }

    $plugin = H5P_Plugin::get_instance();
    $core = $plugin->get_h5p_instance('core');
    if ($core->h5pF->getOption('hub_is_enabled') && $core->h5pF->getOption('check_h5p_requirements')) {
      $core->checkSetupForRequirements();
      $core->h5pF->setOption('check_h5p_requirements', FALSE);
    }

    if (!empty($messages)) {
      // Print all messages
      ?><div class="updated"><?php
      foreach ($messages as $message) {
        ?><p><?php print $message; ?></p><?php
      }
      ?></div><?php
    }

    // Print any other messages
    self::print_messages();
  }

  /**
   * Initialize admin area.
   *
   * @since 1.10.2
   */
  function add_privacy_features() {
    // Privacy Policy
    add_action('admin_init', array($this->privacy, 'add_privacy_policy_content'), 20);

    // Exporter
    add_filter('wp_privacy_personal_data_exporters', array($this->privacy, 'register_h5p_exporter'), 10);

    // Eraser
    add_filter('wp_privacy_personal_data_erasers', array($this->privacy, 'register_h5p_eraser'), 10);
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
    add_menu_page($h5p_content, $h5p_content, 'view_h5p_contents', $this->plugin_slug, array($this->content, 'display_contents_page'), 'none');

    $all_h5p_content = (current_user_can('view_others_h5p_contents') == true) ? __('All H5P Content', $this->plugin_slug) : __('My H5P Content', $this->plugin_slug);
    add_submenu_page($this->plugin_slug, $all_h5p_content, $all_h5p_content, 'view_h5p_contents', $this->plugin_slug, array($this->content, 'display_contents_page'));

    $add_new = __('Add New', $this->plugin_slug);
    $contents_page = add_submenu_page($this->plugin_slug, $add_new, $add_new, 'edit_h5p_contents', $this->plugin_slug . '_new', array($this->content, 'display_new_content_page'));

    // Process form data when saving H5Ps.
    add_action('load-' . $contents_page, array($this->content, 'process_new_content'));

    $libraries = __('Libraries', $this->plugin_slug);
    $libraries_page = add_submenu_page($this->plugin_slug, $libraries, $libraries, 'manage_h5p_libraries', $this->plugin_slug . '_libraries', array($this->library, 'display_libraries_page'));

    // Process form data when upload H5Ps without content.
    add_action('load-' . $libraries_page, array($this->library, 'process_libraries'));

    if (get_option('h5p_track_user', TRUE)) {
      $my_results = __('My Results', $this->plugin_slug);
      add_submenu_page($this->plugin_slug, $my_results, $my_results, 'view_h5p_results', $this->plugin_slug . '_results', array($this, 'display_results_page'));
    }

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
      // Get input and store settings
      check_admin_referer('h5p_settings', 'save_these_settings'); // Verify form

      // Action bar
      $frame = filter_input(INPUT_POST, 'frame', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_frame', $frame);

      $download = filter_input(INPUT_POST, 'download', FILTER_VALIDATE_INT);
      update_option('h5p_export', $download);

      $embed = filter_input(INPUT_POST, 'embed', FILTER_VALIDATE_INT);
      update_option('h5p_embed', $embed);

      $copyright = filter_input(INPUT_POST, 'copyright', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_copyright', $copyright);

      $about = filter_input(INPUT_POST, 'about', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_icon', $about);

      $track_user = filter_input(INPUT_POST, 'track_user', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_track_user', $track_user);

      $save_content_state = filter_input(INPUT_POST, 'save_content_state', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_save_content_state', $save_content_state);

      $save_content_frequency = filter_input(INPUT_POST, 'save_content_frequency', FILTER_VALIDATE_INT);
      update_option('h5p_save_content_frequency', $save_content_frequency);

      $show_toggle_view_others_h5p_contents = filter_input(INPUT_POST, 'show_toggle_view_others_h5p_contents', FILTER_VALIDATE_INT);
      update_option('h5p_show_toggle_view_others_h5p_contents', $show_toggle_view_others_h5p_contents);

      $insert_method = filter_input(INPUT_POST, 'insert_method', FILTER_SANITIZE_SPECIAL_CHARS);
      update_option('h5p_insert_method', $insert_method);

      $enable_lrs_content_types = filter_input(INPUT_POST, 'enable_lrs_content_types', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_enable_lrs_content_types', $enable_lrs_content_types);

      // TODO: Make it possible to change site key
//      $site_key = filter_input(INPUT_POST, 'site_key', FILTER_SANITIZE_SPECIAL_CHARS);
//      if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $site_key)) {
//        // This appears to be a valid UUID, lets use it!
//        update_option('h5p_site_key', $site_key);
//      }
//      else {
//        // Invalid key, use the old one
//        $site_key = get_option('h5p_site_key', get_option('h5p_h5p_site_uuid', FALSE));
//      }

      $enable_hub = filter_input(INPUT_POST, 'enable_hub', FILTER_VALIDATE_BOOLEAN);
      $is_hub_enabled = get_option('h5p_hub_is_enabled', TRUE) ? TRUE : NULL;
      if ($enable_hub !== $is_hub_enabled) {
        // Changed, update core
        $plugin = H5P_Plugin::get_instance();
        $core   = $plugin->get_h5p_instance('core');
        $core->fetchLibrariesMetadata($enable_hub === NULL);
      }
      update_option('h5p_hub_is_enabled', $enable_hub);

      $send_usage_statistics = filter_input(INPUT_POST, 'send_usage_statistics', FILTER_VALIDATE_BOOLEAN);
      update_option('h5p_send_usage_statistics', $send_usage_statistics);
    }
    else {
      $frame = get_option('h5p_frame', TRUE);
      $download = get_option('h5p_export', TRUE);
      $embed = get_option('h5p_embed', TRUE);
      $copyright = get_option('h5p_copyright', TRUE);
      $about = get_option('h5p_icon', TRUE);
      $track_user = get_option('h5p_track_user', TRUE);
      $save_content_state = get_option('h5p_save_content_state', FALSE);
      $save_content_frequency = get_option('h5p_save_content_frequency', 30);
      $show_toggle_view_others_h5p_contents = get_option('h5p_show_toggle_view_others_h5p_contents', 0);
      $insert_method = get_option('h5p_insert_method', 'id');
      $enable_lrs_content_types = get_option('h5p_enable_lrs_content_types', FALSE);
      $enable_hub = get_option('h5p_hub_is_enabled', TRUE);
//      $site_key = get_option('h5p_site_key', get_option('h5p_h5p_site_uuid', FALSE));
      $send_usage_statistics = get_option('h5p_send_usage_statistics', TRUE);
    }

    // Attach disable hub configuration
    $plugin = H5P_Plugin::get_instance();
    $core = $plugin->get_h5p_instance('core');

    // Get error messages
    $errors = $core->checkSetupErrorMessage()->errors;
    $disableHubData = array(
      'errors' => $errors,
      'header' => $core->h5pF->t('Confirmation action'),
      'confirmationDialogMsg' => $core->h5pF->t('Do you still want to enable the hub ?'),
      'cancelLabel' => $core->h5pF->t('Cancel'),
      'confirmLabel' => $core->h5pF->t('Confirm')
    );
    $plugin->print_settings($disableHubData, 'H5PDisableHubData');

    include_once('views/settings.php');
    H5P_Plugin_Admin::add_script('h5p-jquery', 'h5p-php-library/js/jquery.js');
    H5P_Plugin_Admin::add_script('h5p-event-dispatcher', 'h5p-php-library/js/h5p-event-dispatcher.js');
    H5P_Plugin_Admin::add_script('h5p-confirmation-dialog', 'h5p-php-library/js/h5p-confirmation-dialog.js');
    H5P_Plugin_Admin::add_script('h5p-disable-hub', 'h5p-php-library/js/settings/h5p-disable-hub.js');
    H5P_Plugin_Admin::add_script('h5p-display-options', 'h5p-php-library/js/h5p-display-options.js');
    H5P_Plugin_Admin::add_style('h5p-confirmation-dialog-css', 'h5p-php-library/styles/h5p-confirmation-dialog.css');
    H5P_Plugin_Admin::add_style('h5p-css', 'h5p-php-library/styles/h5p.css');
    H5P_Plugin_Admin::add_style('h5p-core-button-css', 'h5p-php-library/styles/h5p-core-button.css');

    new H5P_Event('settings');
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

    if (current_user_can('disable_h5p_security')) {
      $core = $plugin->get_h5p_instance('core');

      // Make it possible to disable file extension check
      $core->disableFileCheck = (filter_input(INPUT_POST, 'h5p_disable_file_check', FILTER_VALIDATE_BOOLEAN) ? TRUE : FALSE);
    }

    // Move so core can validate the file extension.
    rename($_FILES['h5p_file']['tmp_name'], $interface->getUploadedH5pPath());

    $skipContent = ($content === NULL);
    if ($validator->isValidPackage($skipContent, $only_upgrade)) {
      $tmpDir = $interface->getUploadedH5pFolderPath();

      if (!$skipContent) {
        foreach ($validator->h5pC->mainJsonData['preloadedDependencies'] as $dep) {
          if ($dep['machineName'] === $validator->h5pC->mainJsonData['mainLibrary']) {
            if ($validator->h5pF->libraryHasUpgrade($dep)) {
              // We do not allow storing old content due to security concerns
              $interface->setErrorMessage(__("You're trying to upload content of an older version of H5P. Please upgrade the content on the server it originated from and try to upload again or turn on the H5P Hub to have this server upgrade it for your automaticall.", $this->plugin_slug));
              H5PCore::deleteFileTree($tmpDir);
              return FALSE;
            }
          }
        }

        if (empty($content['metadata']) || empty($content['metadata']['title'])) {
          // Fix for legacy content upload to work.
          // Fetch title from h5p.json or use a default string if not available
          $content['metadata']['title'] = empty($validator->h5pC->mainJsonData['title']) ? 'Uploaded Content' : $validator->h5pC->mainJsonData['title'];
        }
      }

      if (function_exists('check_upload_size')) {
        // Check file sizes before continuing!
        $error = self::check_upload_sizes($tmpDir);
        if ($error !== NULL) {
          // Didn't meet space requirements, cleanup tmp dir.
          $interface->setErrorMessage($error);
          H5PCore::deleteFileTree($tmpDir);
          return FALSE;
        }
      }

      // No file size check errors

      if (isset($content['id'])) {
        $interface->deleteLibraryUsage($content['id']);
      }
      $storage = $plugin->get_h5p_instance('storage');
      $storage->savePackage($content, NULL, $skipContent);

      // Clear cached value for dirsize.
      delete_transient('dirsize_cache');

      return $storage->contentId;
    }

    // The uploaded file was not a valid H5P package
    @unlink($interface->getUploadedH5pPath());
    return FALSE;
  }

  /**
   * Avoid breaking the upload limit with any of the files
   *
   * @since 1.7.3
   * @param string $dir
   * @return string on error, null if all is OK
   */
  public static function check_upload_sizes($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
      $firstChar = substr($file, 0, 1);
      if ($firstChar === '.' || $firstChar === '_') {
        continue; // Skip hidden files (will not be save by core)
      }

      $path = ($dir . '/' . $file);
      if (is_dir($path)) {
        // Scan dir
        $error = self::check_upload_sizes($path);
        if ($error !== NULL) {
          return $error;
        }
      }
      elseif (is_file($path)) {
        // Check file size
        $_POST['html-upload'] = TRUE; // Small hack to get output instead of wp_die().
        $upload = check_upload_size(array('tmp_name' => $path, 'error' => '0'));
        $_POST['html-upload'] = FALSE;
        if ($upload['error'] != '0') {
          return $upload['error'];
        }
      }
    }

    return NULL; // All OK
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

    foreach (array('info', 'error') as $type) {
      $messages = $interface->getMessages($type);
      if (!empty($messages)) {
        print '<div class="' . ($type === 'info' ? 'updated' : $type) . '"><ul>';
        foreach ($messages as $message) {
          print '<li>' . ($type === 'error' ? $message->message : $message) . '</li>';
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
      H5PCore::ajaxError(__('Invalid content', $this->plugin_slug));
      exit;
    }
    if (!wp_verify_nonce(filter_input(INPUT_GET, 'token'), 'h5p_result')) {
      H5PCore::ajaxError(__('Invalid security token', $this->plugin_slug));
      exit;
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
    if ($data['time'] === NULL) {
      $data['time'] = 0;
    }
    $format = array(
      '%d',
      '%d',
      '%d',
      '%d',
      '%d'
    );

    /**
     * Allows you to alter a user's submitted result for a given H5P content
     * This action is fired before the result is saved.
     *
     * @since 1.8.0
     *
     * @param object &$data Has the following properties score,max_score,opened,finished,time
     * @param int $result_id Only set if updating result
     * @param int $content_id Identifier of the H5P Content
     * @param int $user_id Identfieri of the User
     */
    do_action_ref_array('h5p_alter_user_result', array(&$data, $result_id, $content_id, $user_id));

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

    // Get content info for log
    $content = $wpdb->get_row($wpdb->prepare("
        SELECT c.title, l.name, l.major_version, l.minor_version
          FROM {$wpdb->prefix}h5p_contents c
          JOIN {$wpdb->prefix}h5p_libraries l ON l.id = c.library_id
         WHERE c.id = %d
        ", $content_id));

    // Log view
    new H5P_Event('results', 'set',
        $content_id, $content->title,
        $content->name, $content->major_version . '.' . $content->minor_version);

    // Success
    H5PCore::ajaxSuccess();
    exit;
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
  private function get_results_query_where(&$query_args, $content_id = NULL, $user_id = NULL, $filters = array()) {
    if ($content_id !== NULL) {
      $where = ' WHERE hr.content_id = %d';
      $query_args[] = $content_id;
    }
    if ($user_id !== NULL) {
      $where = (isset($where) ? $where . ' AND' : ' WHERE') . ' hr.user_id = %d';
      $query_args[] = $user_id;
    }
    if (isset($where) && isset($filters[0])) {
      $where .= ' AND ' . ($content_id === NULL ? 'hc.title' : 'u.user_login') . " LIKE '%%%s%%'";
      $query_args[] = $filters[0];
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
  public function get_results_num($content_id = NULL, $user_id = NULL, $filters = array()) {
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
  public function get_results($content_id = NULL, $user_id = NULL, $offset = 0, $limit = 20, $sort_by = 0, $sort_dir = 0, $filters = array()) {
    global $wpdb;

    $extra_fields = '';
    $joins = '';
    $query_args = array();

    // Add extra fields and joins for the different result lists
    if ($content_id === NULL) {
      $extra_fields .= " hr.content_id, hc.title AS content_title,";
      $joins .= " LEFT JOIN {$wpdb->prefix}h5p_contents hc ON hr.content_id = hc.id";
    }
    if ($user_id === NULL) {
      $extra_fields .= " hr.user_id, u.display_name AS user_name,";
      $joins .= " LEFT JOIN {$wpdb->users} u ON hr.user_id = u.ID";
    }

    // Add filters
    $where = $this->get_results_query_where($query_args, $content_id, $user_id, $filters);

    // Order results by the select column and direction
    $order_by = $this->get_order_by($sort_by, $sort_dir, array(
      (object) array(
        'name' => ($content_id === NULL ? 'hc.title' : 'u.user_login'),
        'reverse' => TRUE
      ),
      'hr.score',
      'hr.max_score',
      'hr.opened',
      'hr.finished'
    ));

    $query_args[] = $offset;
    $query_args[] = $limit;

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
        {$order_by}
        LIMIT %d, %d",
      $query_args
    ));
  }

  /**
   * Generate order by part of SQLs.
   *
   * @since 1.2.0
   * @param int $field Index of field to order by
   * @param int $direction Direction to order in. 0=DESC,1=ASC
   * @param array $field Objects containing name and reverse sort option.
   * @return string Order by part of SQL
   */
  public function get_order_by($field, $direction, $fields) {
    // Make sure selected sortable field is valid
    if (!isset($fields[$field])) {
      $field = 0; // Fall back to default
    }

    // Find selected sortable field
    $field = $fields[$field];

    if (is_object($field)) {
      // Some fields are reverse sorted by default, e.g. text fields.
      if (!empty($field->reverse)) {
        $direction = !$direction;
      }

      $field = $field->name;
    }

    return 'ORDER BY ' . $field . ' ' . ($direction ? 'ASC' : 'DESC');
  }

  /**
   * Print settings, adds JavaScripts and stylesheets necessary for providing
   * a data view.
   *
   * @since 1.2.0
   * @param string $name of the data view
   * @param string $source URL for data
   * @param array $headers for the table
   */
  public function print_data_view_settings($name, $source, $headers, $filters, $empty, $order) {
    // Add JS settings
    $data_views = array();
    $data_views[$name] = array(
      'source' => $source,
      'headers' => $headers,
      'filters' => $filters,
      'order' => $order,
      'l10n' => array(
        'loading' => __('Loading data.', $this->plugin_slug),
        'ajaxFailed' => __('Failed to load data.', $this->plugin_slug),
        'noData' => __("There's no data available that matches your criteria.", $this->plugin_slug),
        'currentPage' => __('Page $current of $total', $this->plugin_slug),
        'nextPage' => __('Next page', $this->plugin_slug),
        'previousPage' => __('Previous page', $this->plugin_slug),
        'search' => __('Search', $this->plugin_slug),
        'remove' => __('Remove', $this->plugin_slug),
        'empty' => $empty,
        'showOwnContentOnly' => __('Show my content only', $this->plugin_slug)
      )
    );
    $plugin = H5P_Plugin::get_instance();
    $settings = array('dataViews' => $data_views);

    // Add toggler for hiding others' content only if user can view others content types
    $canToggleViewOthersH5PContents = current_user_can('view_others_h5p_contents') ?
      get_option('h5p_show_toggle_view_others_h5p_contents') :
    0;

    // Add user object to H5PIntegration
    $user = wp_get_current_user();
    if ($user->ID !== 0) {
      $user = array(
        'user' => array(
          'id' => $user->ID,
          'name' => $user->display_name,
          'canToggleViewOthersH5PContents' => $canToggleViewOthersH5PContents
        )
      );
      $settings = array_merge( $settings, $user );
    }

    $plugin->print_settings($settings);

    // Add JS
    H5P_Plugin_Admin::add_script('jquery', 'h5p-php-library/js/jquery.js');
    H5P_Plugin_Admin::add_script('event-dispatcher', 'h5p-php-library/js/h5p-event-dispatcher.js');
    H5P_Plugin_Admin::add_script('utils', 'h5p-php-library/js/h5p-utils.js');
    H5P_Plugin_Admin::add_script('data-view', 'h5p-php-library/js/h5p-data-view.js');
    H5P_Plugin_Admin::add_script('data-views', 'admin/scripts/h5p-data-views.js');
    H5P_Plugin_Admin::add_style('admin', 'h5p-php-library/styles/h5p-admin.css');
  }

  /**
   * Displays the "My Results" page.
   *
   * @since 1.2.0
   */
  public function display_results_page() {
    include_once('views/my-results.php');
    $this->print_data_view_settings(
      'h5p-my-results',
      admin_url('admin-ajax.php?action=h5p_my_results'),
      array(
        (object) array(
          'text' => __('Content', $this->plugin_slug),
          'sortable' => TRUE
        ),
        (object) array(
          'text' => __('Score', $this->plugin_slug),
          'sortable' => TRUE
        ),
        (object) array(
          'text' => __('Maximum Score', $this->plugin_slug),
          'sortable' => TRUE
        ),
        (object) array(
          'text' => __('Opened', $this->plugin_slug),
          'sortable' => TRUE
        ),
        (object) array(
          'text' => __('Finished', $this->plugin_slug),
          'sortable' => TRUE
        ),
        __('Time spent', $this->plugin_slug)
      ),
      array(true),
      __("There are no logged results for your user.", $this->plugin_slug),
      (object) array(
        'by' => 4,
        'dir' => 0
      )
    );

    // Log visit to this page
    new H5P_Event('results');
  }

  /**
   * Print results ajax data for either content or user, not both.
   *
   * @since 1.2.0
   * @param int $content_id
   * @param int $user_id
   */
  public function print_results($content_id = NULL, $user_id = NULL) {
    // Load input vars.
    list($offset, $limit, $sortBy, $sortDir, $filters) = $this->get_data_view_input();

    // Get results
    $results = $this->get_results($content_id, $user_id, $offset, $limit, $sortBy, $sortDir, $filters);

    $datetimeformat = get_option('date_format') . ' ' . get_option('time_format');
    $offset = get_option('gmt_offset') * 3600;

    // Make data more readable for humans
    $rows = array();
    foreach ($results as $result)  {
      if ($result->time === '0') {
        $result->time = $result->finished - $result->opened;
      }
      $seconds = ($result->time % 60);
      $time = floor($result->time / 60) . ':' . ($seconds < 10 ? '0' : '') . $seconds;

      $rows[] = array(
        esc_html($content_id === NULL ? $result->content_title : $result->user_name),
        (int) $result->score,
        (int) $result->max_score,
        date($datetimeformat, $offset + $result->opened),
        date($datetimeformat, $offset + $result->finished),
        $time,
      );
    }

    // Print results
    header('Cache-Control: no-cache');
    header('Content-type: application/json');
    print json_encode(array(
      'num' => $this->get_results_num($content_id, $user_id, $filters),
      'rows' => $rows
    ));
    exit;
  }

  /**
   * Provide data for content results view.
   *
   * @since 1.2.0
   */
  public function ajax_my_results() {
    $this->print_results(NULL, get_current_user_id());
  }

  /**
   * Load input vars for data views.
   *
   * @since 1.2.0
   * @return array offset, limit, sort by, sort direction, filters
   */
  public function get_data_view_input() {
    $offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
    $sortBy = filter_input(INPUT_GET, 'sortBy', FILTER_SANITIZE_NUMBER_INT);
    $sortDir = filter_input(INPUT_GET, 'sortDir', FILTER_SANITIZE_NUMBER_INT);
    $filters = filter_input(INPUT_GET, 'filters', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $facets = filter_input(INPUT_GET, 'facets', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

    $limit = (!$limit ? 20 : (int) $limit);
    if ($limit > 100) {
      $limit = 100; // Prevent wrong usage.
    }

    // Use default if not set or invalid
    return array(
      (!$offset ? 0 : (int) $offset),
      $limit,
      (!$sortBy ? 0 : (int) $sortBy),
      (!$sortDir ? 0 : (int) $sortDir),
      $filters,
      $facets
    );

  }

  /**
   * Handle user results reported by the H5P content.
   *
   * @since 1.5.0
   */
  public function ajax_contents_user_data() {
    global $wpdb;

    $content_id = filter_input(INPUT_GET, 'content_id');
    $data_id = filter_input(INPUT_GET, 'data_type');
    $sub_content_id = filter_input(INPUT_GET, 'sub_content_id');
    $current_user = wp_get_current_user();

    if ($content_id === NULL ||
        $data_id === NULL ||
        $sub_content_id === NULL ||
        !$current_user->ID) {
      return; // Missing parameters
    }

    $response = (object) array(
      'success' => TRUE
    );

    $data = filter_input(INPUT_POST, 'data');
    $preload = filter_input(INPUT_POST, 'preload');
    $invalidate = filter_input(INPUT_POST, 'invalidate');
    if ($data !== NULL && $preload !== NULL && $invalidate !== NULL) {
      if (!wp_verify_nonce(filter_input(INPUT_GET, 'token'), 'h5p_contentuserdata')) {
        H5PCore::ajaxError(__('Invalid security token', $this->plugin_slug));
        exit;
      }

      if ($data === '0') {
        // Remove data
        $wpdb->delete($wpdb->prefix . 'h5p_contents_user_data',
          array(
            'content_id' => $content_id,
            'data_id' => $data_id,
            'user_id' => $current_user->ID,
            'sub_content_id' => $sub_content_id
          ),
          array('%d', '%s', '%d', '%d'));
      }
      else {
        // Wash values to ensure 0 or 1.
        $preload = ($preload === '0' ? 0 : 1);
        $invalidate = ($invalidate === '0' ? 0 : 1);

        // Determine if we should update or insert
        $update = $wpdb->get_var($wpdb->prepare(
          "SELECT content_id
           FROM {$wpdb->prefix}h5p_contents_user_data
           WHERE content_id = %d
             AND user_id = %d
             AND data_id = %s
             AND sub_content_id = %d",
            $content_id, $current_user->ID, $data_id, $sub_content_id
        ));

        if ($update === NULL) {
          // Insert new data
          $wpdb->insert($wpdb->prefix . 'h5p_contents_user_data',
            array(
              'user_id' => $current_user->ID,
              'content_id' => $content_id,
              'sub_content_id' => $sub_content_id,
              'data_id' => $data_id,
              'data' => $data,
              'preload' => $preload,
              'invalidate' => $invalidate,
              'updated_at' => current_time('mysql', 1)
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s')
          );
        }
        else {
          // Update old data
          $wpdb->update($wpdb->prefix . 'h5p_contents_user_data',
            array(
              'data' => $data,
              'preload' => $preload,
              'invalidate' => $invalidate,
              'updated_at' => current_time('mysql', 1)
            ),
            array(
              'user_id' => $current_user->ID,
              'content_id' => $content_id,
              'data_id' => $data_id,
              'sub_content_id' => $sub_content_id
            ),
            array('%s', '%d', '%d', '%s'),
            array('%d', '%d', '%s', '%d')
          );
        }
      }

      // Inserted, updated or deleted
      H5PCore::ajaxSuccess();
      exit;
    }
    else {
      // Fetch data
      $response->data = $wpdb->get_var($wpdb->prepare(
        "SELECT hcud.data
         FROM {$wpdb->prefix}h5p_contents_user_data hcud
         WHERE user_id = %d
           AND content_id = %d
           AND data_id = %s
           AND sub_content_id = %d",
        $current_user->ID, $content_id, $data_id, $sub_content_id
      ));

      if ($response->data === NULL) {
        unset($response->data);
      }
    }

    header('Cache-Control: no-cache');
    header('Content-type: application/json; charset=utf-8');
    print json_encode($response);
    exit;
  }

  /**
   * Remove user data and results when user is removed.
   *
   * @since 1.5.0
   */
  public function deleted_user($id) {
    global $wpdb;

    // Remove user scores/results
    $wpdb->delete($wpdb->prefix . 'h5p_results', array('user_id' => $id), array('%d'));

    // Remove contents user/usage data
    $wpdb->delete($wpdb->prefix . 'h5p_contents_user_data', array('user_id' => $id), array('%d'));
  }
}
