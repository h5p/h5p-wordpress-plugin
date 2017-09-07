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
 * Plugin class.
 *
 * @package H5P_Plugin
 * @author Joubel <contact@joubel.com>
 */
class H5P_Plugin {

  /**
   * Plugin version, used for cache-busting of style and script file references.
   * Keeping track of the DB version.
   *
   * @since 1.0.0
   * @var string
   */
  const VERSION = '1.9.0';

  /**
   * The Unique identifier for this plugin.
   *
   * @since 1.0.0
   * @var string
   */
  protected $plugin_slug = 'h5p';

  /**
   * Instance of this class.
   *
   * @since 1.0.0
   * @var \H5P_Plugin
   */
  protected static $instance = null;

  /**
   * Instance of H5P WordPress Framework Interface.
   *
   * @since 1.0.0
   * @var \H5PWordPress
   */
  protected static $interface = null;

  /**
   * Instance of H5P Core.
   *
   * @since 1.0.0
   * @var \H5PCore
   */
  protected static $core = null;

  /**
   * JavaScript settings to add for H5Ps.
   *
   * @since 1.0.0
   * @var array
   */
  protected static $settings = null;

  /**
   * Initialize the plugin by setting localization and loading public scripts
   * and styles.
   *
   * @since 1.0.0
   */
  private function __construct() {
    // Load plugin text domain
    add_action('init', array($this, 'load_plugin_textdomain'));

    // Load public-facing style sheet and JavaScript.
    add_action('wp_enqueue_scripts', array($this, 'enqueue_styles_and_scripts'));

    // Add support for h5p shortcodes.
    add_shortcode('h5p', array($this, 'shortcode'));

    // Adds JavaScript settings to the bottom of the page.
    add_action('wp_footer', array($this, 'add_settings'));

    // Clean up tmp editor files
    add_action('h5p_daily_cleanup', array($this, 'remove_old_tmp_files'));

    // Check for library updates
    add_action('h5p_daily_cleanup', array($this, 'get_library_updates'));

    // Remove old log messages
    add_action('h5p_daily_cleanup', array($this, 'remove_old_log_events'));

    // Always check if the plugin has been updated to a newer version
    add_action('init', array('H5P_Plugin', 'check_for_updates'), 1);

    // Add menu options to admin bar.
    add_action('admin_bar_menu', array($this, 'admin_bar'), 999);
  }

  /**
   * Return the plugin slug.
   *
   * @since 1.0.0
   * @return string Plugin slug variable.
   */
  public function get_plugin_slug() {
    return $this->plugin_slug;
  }

  /**
   * Return an instance of this class.
   *
   * @since 1.0.0
   * @return \H5P_Plugin A single instance of this class.
   */
  public static function get_instance() {
    // If the single instance hasn't been set, set it now.
    if (null == self::$instance) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  /**
   * Fired when the plugin is activated.
   *
   * @since 1.0.0
   * @global \wpdb $wpdb
   * @param boolean $network_wide
   */
  public static function activate($network_wide) {
    // Check to see if the plugin has been updated to a newer version
    self::check_for_updates();

    // Check for library updates
    $plugin = self::get_instance();
    $plugin->get_library_updates();

    // Always check setup requirements when activating
    update_option('h5p_check_h5p_requirements', TRUE);

    // Cleaning rutine
    wp_schedule_event(time() + (3600 * 24), 'daily', 'h5p_daily_cleanup');
  }

  /**
   * Makes sure the database is up to date.
   *
   * @since 1.1.0
   * @global \wpdb $wpdb
   */
  public static function update_database() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Get charset to use
    $charset = self::determine_charset();

    // Keep track of h5p content entities
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_contents (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      created_at TIMESTAMP NOT NULL DEFAULT 0,
      updated_at TIMESTAMP NOT NULL DEFAULT 0,
      user_id INT UNSIGNED NOT NULL,
      title VARCHAR(255) NOT NULL,
      library_id INT UNSIGNED NOT NULL,
      parameters LONGTEXT NOT NULL,
      filtered LONGTEXT NOT NULL,
      slug VARCHAR(127) NOT NULL,
      embed_type VARCHAR(127) NOT NULL,
      disable INT UNSIGNED NOT NULL DEFAULT 0,
      content_type VARCHAR(127) NULL,
      author VARCHAR(127) NULL,
      license VARCHAR(7) NULL,
      keywords TEXT NULL,
      description TEXT NULL,
      PRIMARY KEY  (id)
    ) {$charset};");

    // Keep track of content dependencies
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_contents_libraries (
      content_id INT UNSIGNED NOT NULL,
      library_id INT UNSIGNED NOT NULL,
      dependency_type VARCHAR(31) NOT NULL,
      weight SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      drop_css TINYINT UNSIGNED NOT NULL,
      PRIMARY KEY  (content_id,library_id,dependency_type)
    ) {$charset};");

    // Keep track of data/state when users use content (contents >-< users)
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_contents_user_data (
      content_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      sub_content_id INT UNSIGNED NOT NULL,
      data_id VARCHAR(127) NOT NULL,
      data LONGTEXT NOT NULL,
      preload TINYINT UNSIGNED NOT NULL DEFAULT 0,
      invalidate TINYINT UNSIGNED NOT NULL DEFAULT 0,
      updated_at TIMESTAMP NOT NULL DEFAULT 0,
      PRIMARY KEY  (content_id,user_id,sub_content_id,data_id)
    ) {$charset};");

    // Create a relation between tags and content
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_contents_tags (
      content_id INT UNSIGNED NOT NULL,
      tag_id INT UNSIGNED NOT NULL,
      PRIMARY KEY  (content_id,tag_id)
    ) {$charset};");

    // Keep track of tags
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_tags (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      name VARCHAR(31) NOT NULL,
      PRIMARY KEY  (id)
    ) {$charset};");

    // Keep track of results (contents >-< users)
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_results (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      content_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      score INT UNSIGNED NOT NULL,
      max_score INT UNSIGNED NOT NULL,
      opened INT UNSIGNED NOT NULL,
      finished INT UNSIGNED NOT NULL,
      time INT UNSIGNED NOT NULL,
      PRIMARY KEY  (id),
      KEY content_user (content_id,user_id)
    ) {$charset};");

    // Keep track of h5p libraries
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_libraries (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      created_at TIMESTAMP NOT NULL,
      updated_at TIMESTAMP NOT NULL,
      name VARCHAR(127) NOT NULL,
      title VARCHAR(255) NOT NULL,
      major_version INT UNSIGNED NOT NULL,
      minor_version INT UNSIGNED NOT NULL,
      patch_version INT UNSIGNED NOT NULL,
      runnable INT UNSIGNED NOT NULL,
      restricted INT UNSIGNED NOT NULL DEFAULT 0,
      fullscreen INT UNSIGNED NOT NULL,
      embed_types VARCHAR(255) NOT NULL,
      preloaded_js TEXT NULL,
      preloaded_css TEXT NULL,
      drop_library_css TEXT NULL,
      semantics TEXT NOT NULL,
      tutorial_url VARCHAR(1023) NOT NULL,
      has_icon INT UNSIGNED NOT NULL DEFAULT 0,
      PRIMARY KEY  (id),
      KEY name_version (name,major_version,minor_version,patch_version),
      KEY runnable (runnable)
    ) {$charset};");

    // Keep track of h5p libraries content type cache
    dbDelta("CREATE TABLE {$wpdb->base_prefix}h5p_libraries_hub_cache (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      machine_name VARCHAR(127) NOT NULL,
      major_version INT UNSIGNED NOT NULL,
      minor_version INT UNSIGNED NOT NULL,
      patch_version INT UNSIGNED NOT NULL,
      h5p_major_version INT UNSIGNED,
      h5p_minor_version INT UNSIGNED,
      title VARCHAR(255) NOT NULL,
      summary TEXT NOT NULL,
      description TEXT NOT NULL,
      icon VARCHAR(511) NOT NULL,
      created_at INT UNSIGNED NOT NULL,
      updated_at INT UNSIGNED NOT NULL,
      is_recommended INT UNSIGNED NOT NULL,
      popularity INT UNSIGNED NOT NULL,
      screenshots TEXT,
      license TEXT,
      example VARCHAR(511) NOT NULL,
      tutorial VARCHAR(511),
      keywords TEXT,
      categories TEXT,
      owner VARCHAR(511),
      PRIMARY KEY  (id),
      KEY name_version (machine_name,major_version,minor_version,patch_version)
    ) {$charset};");

    // Keep track of h5p library dependencies
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_libraries_libraries (
      library_id INT UNSIGNED NOT NULL,
      required_library_id INT UNSIGNED NOT NULL,
      dependency_type VARCHAR(31) NOT NULL,
      PRIMARY KEY  (library_id,required_library_id)
    ) {$charset};");

    // Keep track of h5p library translations
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_libraries_languages (
      library_id INT UNSIGNED NOT NULL,
      language_code VARCHAR(31) NOT NULL,
      translation TEXT NOT NULL,
      PRIMARY KEY  (library_id,language_code)
    ) {$charset};");

    // Keep track of logged h5p events
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_events (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id INT UNSIGNED NOT NULL,
      created_at INT UNSIGNED NOT NULL,
      type VARCHAR(63) NOT NULL,
      sub_type VARCHAR(63) NOT NULL,
      content_id INT UNSIGNED NOT NULL,
      content_title VARCHAR(255) NOT NULL,
      library_name VARCHAR(127) NOT NULL,
      library_version VARCHAR(31) NOT NULL,
      PRIMARY KEY  (id)
    ) {$charset};");

    // A set of global counters to keep track of H5P usage
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_counters (
      type VARCHAR(63) NOT NULL,
      library_name VARCHAR(127) NOT NULL,
      library_version VARCHAR(31) NOT NULL,
      num INT UNSIGNED NOT NULL,
      PRIMARY KEY  (type,library_name,library_version)
    ) {$charset};");

    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_libraries_cachedassets (
      library_id INT UNSIGNED NOT NULL,
      hash VARCHAR(64) NOT NULL,
      PRIMARY KEY  (library_id,hash)
    ) {$charset};");

    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_tmpfiles (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      path VARCHAR(255) NOT NULL,
      created_at INT UNSIGNED NOT NULL,
      PRIMARY KEY  (id),
      KEY created_at (created_at)
    ) {$charset};");

    // Add default setting options
    add_option('h5p_frame', TRUE);
    add_option('h5p_export', TRUE);
    add_option('h5p_embed', TRUE);
    add_option('h5p_copyright', TRUE);
    add_option('h5p_icon', TRUE);
    add_option('h5p_track_user', TRUE);
    add_option('h5p_save_content_state', FALSE);
    add_option('h5p_save_content_frequency', 30);
    add_option('h5p_site_key', get_option('h5p_h5p_site_uuid', FALSE));
    add_option('h5p_content_type_cache_updated_at', 0);
    add_option('h5p_check_h5p_requirements', FALSE);
    add_option('h5p_hub_is_enabled', TRUE);
    add_option('h5p_send_usage_statistics', TRUE);
  }

  /**
   * Determine charset to use for database tables
   *
   * @since 1.2.0
   * @global \wpdb $wpdb
   */
  public static function determine_charset() {
    global $wpdb;
    $charset = '';

    if (!empty($wpdb->charset)) {
      $charset = "DEFAULT CHARACTER SET {$wpdb->charset}";

      if (!empty($wpdb->collate)) {
        $charset .= " COLLATE {$wpdb->collate}";
      }
    }
    return $charset;
  }

  /**
   * @since 1.0.0
   */
  public static function deactivate() {
    // Remove cleaning rutine
    wp_clear_scheduled_hook('h5p_daily_cleanup');
  }

  /**
   * Check if the plugin has been updated and if we need to run some upgrade
   * scripts, change the database or something else.
   *
   * @since 1.2.0
   */
  public static function check_for_updates() {
    global $wpdb;

    $current_version = get_option('h5p_version');
    if ($current_version === self::VERSION) {
      return; // Same version as before
    }

    // We have a new version!
    if (!$current_version) {
      // Never installed before
      $current_version = '0.0.0';
    }

    // Split version number
    $v = self::split_version($current_version);

    $between_1710_1713 = ($v->major === 1 && $v->minor === 7 && $v->patch >= 10 && $v->patch <= 13); // Target 1.7.10, 1.7.11, 1.7.12, 1.7.13
    if ($between_1710_1713) {
      // Fix tmpfiles table manually :-)
      $wpdb->query("ALTER TABLE {$wpdb->prefix}h5p_tmpfiles ADD COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST, DROP PRIMARY KEY, ADD PRIMARY KEY(id)");
    }

    // Check and update database
    self::update_database();

    $pre_120 = ($v->major < 1 || ($v->major === 1 && $v->minor < 2)); // < 1.2.0
    $pre_180 = ($v->major < 1 || ($v->major === 1 && $v->minor < 8)); // < 1.8.0

    // Run version specific updates
    if ($pre_120) {
      // Re-assign all permissions
      self::upgrade_120();
    }
    elseif ($pre_180) {
      // Do not run if upgrade_120 runs
      // Does only add the new permissions
      self::upgrade_180();
    }

    if ($pre_180) {
      // Force requirements check when hub is introduced.
      update_option('h5p_check_h5p_requirements', TRUE);
    }

    // Keep track of which version of the plugin we have.
    if ($current_version === '0.0.0') {
      add_option('h5p_version', self::VERSION);
    }
    else {
      update_option('h5p_version', self::VERSION);
    }
  }

  /**
   * Parse version string into smaller components.
   *
   * @since 1.7.9
   * @param string $version
   * @return stdClass|boolean False on failure to parse
   */
  public static function split_version($version) {
    $version_parts = explode('.', $version);
    if (count($version_parts) !== 3) {
      return FALSE;
    }

    return (object) array(
      'major' => (int) $version_parts[0],
      'minor' => (int) $version_parts[1],
      'patch' => (int) $version_parts[2]
    );
  }

  /**
   * Migration procedures when upgrading to >= 1.2.0.
   *
   * @since 1.2.0
   * @global \wpdb $wpdb
   */
  public static function upgrade_120() {
    global $wpdb;

    // Add caps again, has not worked for everyone in 1.1.0
    self::assign_capabilities();

    // Clean up duplicate indexes (due to bug in dbDelta)
    self::remove_duplicate_indexes('h5p_contents', 'id');
    self::remove_duplicate_indexes('h5p_contents_libraries', 'content_id');
    self::remove_duplicate_indexes('h5p_results', 'id');
    self::remove_duplicate_indexes('h5p_libraries', 'id');
    self::remove_duplicate_indexes('h5p_libraries_libraries', 'library_id');
    self::remove_duplicate_indexes('h5p_libraries_languages', 'library_id');

    // Make sure we use the charset defined in wp-config, and not DB default.
    $charset = self::determine_charset();
    if (!empty($charset)) {
      $wpdb->query("ALTER TABLE `{$wpdb->prefix}h5p_contents` {$charset}");
      $wpdb->query("ALTER TABLE `{$wpdb->prefix}h5p_contents_libraries` {$charset}");
      $wpdb->query("ALTER TABLE `{$wpdb->prefix}h5p_results` {$charset}");
      $wpdb->query("ALTER TABLE `{$wpdb->prefix}h5p_libraries` {$charset}");
      $wpdb->query("ALTER TABLE `{$wpdb->prefix}h5p_libraries_libraries` {$charset}");
      $wpdb->query("ALTER TABLE `{$wpdb->prefix}h5p_libraries_languages` {$charset}");
    }
  }

  /**
   * Add new permissions introduced with hub in 1.8.0.
   *
   * @since 1.8.0
   * @global \WP_Roles $wp_roles
   */
  public static function upgrade_180() {
    global $wp_roles;
    if (!isset($wp_roles)) {
      $wp_roles = new WP_Roles();
    }

    $all_roles = $wp_roles->roles;
    foreach ($all_roles as $role_name => $role_info) {
      $role = get_role($role_name);

      self::map_capability($role, $role_info, 'edit_others_pages', 'install_recommended_h5p_libraries');
    }
  }

  /**
   * Remove duplicate keys that might have been created by a bug in dbDelta.
   *
   * @since 1.2.0
   * @global \wpdb $wpdb
   * @param string $table Table name without wp prefix
   * @param string $index Key name
   */
  public static function remove_duplicate_indexes($table, $index) {
    global $wpdb;
    $wpdb->hide_errors();

    if ($wpdb->query("SHOW INDEX FROM `{$wpdb->prefix}{$table}` WHERE Key_name = '{$index}'")) {
      $wpdb->query("ALTER TABLE `{$wpdb->prefix}{$table}` DROP INDEX `{$index}`");
    }

    for ($i = 0; $i < 5; $i++) {
      if ($wpdb->query("SHOW INDEX FROM `{$wpdb->prefix}{$table}` WHERE Key_name = '{$index}_$i'")) {
        $wpdb->query("ALTER TABLE `{$wpdb->prefix}{$table}` DROP INDEX `{$index}_$i`");
      }
    }

    $wpdb->show_errors();
  }

  /**
   * Assign H5P capabilities to roles. "Copy" default WP caps on roles.
   *
   * @since 1.2.0
   */
  public static function assign_capabilities() {
    global $wp_roles;
    if (!isset($wp_roles)) {
      $wp_roles = new WP_Roles();
    }

    $all_roles = $wp_roles->roles;
    foreach ($all_roles as $role_name => $role_info) {
      $role = get_role($role_name);

      if (is_multisite()) {
        // Multisite, only super admin should be able to disable security checks
        self::map_capability($role, $role_info, array('install_plugins', 'manage_network_plugins'), 'disable_h5p_security');
      }
      else {
        // Not multisite, regular admin can disable security checks
        self::map_capability($role, $role_info, 'install_plugins', 'disable_h5p_security');
      }
      self::map_capability($role, $role_info, 'manage_options', 'manage_h5p_libraries');
      self::map_capability($role, $role_info, 'edit_others_pages', 'install_recommended_h5p_libraries');
      self::map_capability($role, $role_info, 'edit_others_pages', 'edit_others_h5p_contents');
      self::map_capability($role, $role_info, 'edit_posts', 'edit_h5p_contents');
      self::map_capability($role, $role_info, 'read', 'view_h5p_results');
    }

    // Keep track on how the capabilities are assigned (multisite caps or not)
    update_option('h5p_multisite_capabilities', is_multisite() ? 1 : 0);
  }

  /**
   * Make sure that the givn role has or hasn't the provided capability
   * depending on existing roles.
   *
   * @since 1.7.2
   * @param stdClass $role
   * @param array $role_info
   * @param string|array $existing_cap
   * @param string $new_cap
   */
  private static function map_capability($role, $role_info, $existing_cap, $new_cap) {
    if (isset($role_info['capabilities'][$new_cap])) {
      // Already has new cap…
      if (!self::has_capability($role_info, $existing_cap)) {
        // But shouldn't have it!
        $role->remove_cap($new_cap);
      }
    }
    else {
      // Doesn't have new cap…
      if (self::has_capability($role_info, $existing_cap)) {
        // But should have it!
        $role->add_cap($new_cap);
      }
    }
  }

  /**
   * Check that the given role has the needed capability/-ies.
   *
   * @since 1.7.2
   * @param array $role_info
   * @param string|array $capability
   * @return bool
   */
  private static function has_capability($role_info, $capability) {
    if (is_array($capability)) {
      foreach ($capability as $cap) {
        if (!isset($role_info['capabilities'][$cap])) {
          return FALSE;
        }
      }
    }
    else if (!isset($role_info['capabilities'][$capability])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Load the plugin text domain for translation.
   *
   * @since 1.0.0
   */
  public function load_plugin_textdomain() {
    $domain = $this->plugin_slug;
    $locale = apply_filters('plugin_locale', get_locale(), $domain);

    load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
    load_plugin_textdomain($domain, FALSE, basename(plugin_dir_path(dirname( __FILE__ ))) . '/languages');
  }

  /**
   * Register and enqueue public-facing style sheets and JavaScript files.
   *
   * @since 1.0.0
   */
  public function enqueue_styles_and_scripts() {
    wp_enqueue_style($this->plugin_slug . '-plugin-styles', plugins_url('h5p/h5p-php-library/styles/h5p.css'), array(), self::VERSION);
  }

  /**
  * Add menu options to the WordPress admin bar
  *
  * @since 1.2.2
  */
  public function admin_bar($wp_admin_bar) {
    $wp_admin_bar->add_menu(array(
      'parent' => 'new-content',
      'id' => 'new-h5p-content',
      'title' => __('H5P Content', $this->plugin_slug),
      'href' => admin_url('admin.php?page=h5p_new')
    ));
  }

  /**
   * Get the path to the H5P files folder.
   *
   * @since 1.0.0
   * @return string
   */
  public function get_h5p_path() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['basedir'] . '/h5p';
  }

  /**
   * Get the URL for the H5P files folder.
   *
   * @since 1.0.0
   * @param $absolute Optional.
   * @return string
   */
  public function get_h5p_url($absolute = FALSE) {
    static $url;

    if (!$url) {
      $upload_dir = wp_upload_dir();

      // Absolute urls are used to enqueue assets.
      $url = array('abs' => $upload_dir['baseurl'] . '/h5p');

      // Relative URLs are used to support both http and https in iframes.
      $url['rel'] = str_replace(get_home_url(), '', $url['abs']);

      // Check for HTTPS
      if (is_ssl() && substr($url['abs'], 0, 5) !== 'https') {
        // Update protocol
        $url['abs'] = 'https' . substr($url['abs'], 4);
      }
    }
    
    return $absolute ? $url['abs'] : $url['rel'];
  }

  /**
   * Get H5P language code from WordPress.
   *
   * @since 1.0.0
   * @return string
   */
  public function get_language() {
    if (defined('WPLANG')) {
      $language = WPLANG;
    }

    if (empty($language)) {
      $language = get_option('WPLANG');
    }

    if (!empty($language)) {
      $languageParts = explode('_', $language);
      return $languageParts[0];
    }

    return 'en';
  }

  /**
   * Get the different instances of the core.
   *
   * @since 1.0.0
   * @param string $type
   * @return \H5PWordPress|\H5PCore|\H5PContentValidator|\H5PExport|\H5PStorage|\H5PValidator
   */
  public function get_h5p_instance($type) {
    if (self::$interface === null) {
      self::$interface = new H5PWordPress();
      $language = $this->get_language();
      self::$core = new H5PCore(self::$interface, $this->get_h5p_path(), $this->get_h5p_url(), $language, get_option('h5p_export', TRUE));
      self::$core->aggregateAssets = !(defined('H5P_DISABLE_AGGREGATION') && H5P_DISABLE_AGGREGATION === true);
    }

    switch ($type) {
      case 'validator':
        return new H5PValidator(self::$interface, self::$core);
      case 'storage':
        return new H5PStorage(self::$interface, self::$core);
      case 'contentvalidator':
        return new H5PContentValidator(self::$interface, self::$core);
      case 'export':
        return new H5PExport(self::$interface, self::$core);
      case 'interface':
        return self::$interface;
      case 'core':
        return self::$core;
    }
  }

  /**
   * Get content with given id.
   *
   * @since 1.0.0
   * @param int $id
   * @return array
   * @throws Exception
   */
  public function get_content($id) {
    if ($id === FALSE || $id === NULL) {
      return __('Missing H5P identifier.', $this->plugin_slug);
    }

    // Try to find content with $id.
    $core = $this->get_h5p_instance('core');
    $content = $core->loadContent($id);

    if (!$content) {
      return sprintf(__('Cannot find H5P content with id: %d.', $this->plugin_slug), $id);
    }

    $content['language'] = $this->get_language();
    return $content;
  }

  /**
   * Translate h5p shortcode to html.
   *
   * @since 1.0.0
   * @param array $atts
   * @return string
   */
  public function shortcode($atts) {
    global $wpdb;
    if (isset($atts['slug'])) {
      $q=$wpdb->prepare(
        "SELECT  id ".
        "FROM    {$wpdb->prefix}h5p_contents ".
        "WHERE   slug=%s",
        $atts['slug']
      );
      $row=$wpdb->get_row($q,ARRAY_A);

      if ($wpdb->last_error) {
        return sprintf(__('Database error: %s.', $this->plugin_slug), $wpdb->last_error);
      }

      if (!isset($row['id'])) {
        return sprintf(__('Cannot find H5P content with slug: %s.', $this->plugin_slug), $atts['slug']);
      }

      $atts['id']=$row['id'];
    }

    $id = isset($atts['id']) ? intval($atts['id']) : NULL;
    $content = $this->get_content($id);
    if (is_string($content)) {
      // Return error message if the user has the correct cap
      return current_user_can('edit_h5p_contents') ? $content : NULL;
    }

    // Log view
    new H5P_Event('content', 'shortcode',
        $content['id'],
        $content['title'],
        $content['library']['name'],
        $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']);

    return $this->add_assets($content);
  }

  /**
   * Get settings for given content
   *
   * @since 1.5.0
   * @param array $content
   * @return array
   */
  public function get_content_settings($content) {
    global $wpdb;
    $core = $this->get_h5p_instance('core');

    $safe_parameters = $core->filterParameters($content);
    if (has_action('h5p_alter_filtered_parameters')) {
      // Parse the JSON parameters
      $decoded_parameters = json_decode($safe_parameters);

      /**
       * Allows you to alter the H5P content parameters after they have been
       * filtered. This hook only fires before view.
       *
       * @since 1.5.3
       *
       * @param object &$parameters
       * @param string $libraryName
       * @param int $libraryMajorVersion
       * @param int $libraryMinorVersion
       */
      do_action_ref_array('h5p_alter_filtered_parameters', array(&$decoded_parameters, $content['library']['name'], $content['library']['majorVersion'], $content['library']['minorVersion']));

      // Stringify the JSON parameters
      $safe_parameters = json_encode($decoded_parameters);
    }

    // Getting author's user id
    $author_id = (int)(is_array($content) ? $content['user_id'] : $content->user_id);

    // Add JavaScript settings for this content
    $settings = array(
      'library' => H5PCore::libraryToString($content['library']),
      'jsonContent' => $safe_parameters,
      'fullScreen' => $content['library']['fullscreen'],
      'exportUrl' => get_option('h5p_export', TRUE) ? $this->get_h5p_url() . '/exports/' . ($content['slug'] ? $content['slug'] . '-' : '') . $content['id'] . '.h5p' : '',
      'embedCode' => '<iframe src="' . admin_url('admin-ajax.php?action=h5p_embed&id=' . $content['id']) . '" width=":w" height=":h" frameborder="0" allowfullscreen="allowfullscreen"></iframe>',
      'resizeCode' => '<script src="' . plugins_url('h5p/h5p-php-library/js/h5p-resizer.js') . '" charset="UTF-8"></script>',
      'url' => admin_url('admin-ajax.php?action=h5p_embed&id=' . $content['id']),
      'title' => $content['title'],
      'displayOptions' => $core->getDisplayOptionsForView($content['disable'], $author_id),
      'contentUserData' => array(
        0 => array(
          'state' => '{}'
        )
      )
    );

    // Get preloaded user data for the current user
    $current_user = wp_get_current_user();
    if (get_option('h5p_save_content_state', FALSE) && $current_user->ID) {
      $results = $wpdb->get_results($wpdb->prepare(
        "SELECT hcud.sub_content_id,
                hcud.data_id,
                hcud.data
          FROM {$wpdb->prefix}h5p_contents_user_data hcud
          WHERE user_id = %d
          AND content_id = %d
          AND preload = 1",
        $current_user->ID, $content['id']
      ));

      if ($results) {
        foreach ($results as $result) {
          $settings['contentUserData'][$result->sub_content_id][$result->data_id] = $result->data;
        }
      }
    }

    return $settings;
  }

  /**
   * Include settings and assets for the given content.
   *
   * @since 1.0.0
   * @param array $content
   * @param boolean $no_cache
   * @return string Embed code
   */
  public function add_assets($content, $no_cache = FALSE) {
    // Add core assets
    $this->add_core_assets();

    // Detemine embed type
    $embed = H5PCore::determineEmbedType($content['embedType'], $content['library']['embedTypes']);

    // Make sure content isn't added twice
    $cid = 'cid-' . $content['id'];
    if (!isset(self::$settings['contents'][$cid])) {
      self::$settings['contents'][$cid] = $this->get_content_settings($content);
      $core = $this->get_h5p_instance('core');

      // Get assets for this content
      $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
      $files = $core->getDependenciesFiles($preloaded_dependencies);
      $this->alter_assets($files, $preloaded_dependencies, $embed);

      if ($embed === 'div') {
        $this->enqueue_assets($files);
      }
      elseif ($embed === 'iframe') {
        self::$settings['contents'][$cid]['scripts'] = $core->getAssetsUrls($files['scripts']);
        self::$settings['contents'][$cid]['styles'] = $core->getAssetsUrls($files['styles']);
      }
    }

    if ($embed === 'div') {
      return '<div class="h5p-content" data-content-id="' . $content['id'] . '"></div>';
    }
    else {
      return '<div class="h5p-iframe-wrapper"><iframe id="h5p-iframe-' . $content['id'] . '" class="h5p-iframe" data-content-id="' . $content['id'] . '" style="height:1px" src="about:blank" frameBorder="0" scrolling="no"></iframe></div>';
    }
  }

  /**
   * Finds the assets for the dependencies and allows other plugins to change
   * them and add their own.
   *
   * @since 1.5.3
   * @param array $dependencies
   * @param array $files scripts & styles
   * @param string $embed type
   */
  public function alter_assets(&$files, &$dependencies, $embed) {
    if (!has_action('h5p_alter_library_scripts') && !has_action('h5p_alter_library_styles')) {
      return;
    }

    // Refactor dependency list
    $libraries = array();
    foreach ($dependencies as $dependency) {
      $libraries[$dependency['machineName']] = array(
        'majorVersion' => $dependency['majorVersion'],
        'minorVersion' => $dependency['minorVersion']
      );
    }

    /**
      * Allows you to alter which JavaScripts are loaded for H5P. This is
      * useful for adding your own custom scripts or replacing existing once.
     *
     * @since 1.5.3
     *
     * @param array &$scripts List of JavaScripts to be included.
     * @param array $libraries The list of libraries that has the scripts.
     * @param string $embed_type Possible values are: div, iframe, external, editor.
     */
    do_action_ref_array('h5p_alter_library_scripts', array(&$files['scripts'], $libraries, $embed));

    /**
     * Allows you to alter which stylesheets are loaded for H5P. This is
     * useful for adding your own custom stylesheets or replacing existing once.
     *
     * @since 1.5.3
     *
     * @param array &$styles List of stylesheets to be included.
     * @param array $libraries The list of libraries that has the styles.
     * @param string $embed_type Possible values are: div, iframe, external, editor.
     */
    do_action_ref_array('h5p_alter_library_styles', array(&$files['styles'], $libraries, $embed));
  }

  /**
   * Enqueue assets for content embedded by div.
   *
   * @param array $assets
   */
  public function enqueue_assets(&$assets) {
    $rel_url = $this->get_h5p_url();

    // Enqueue JavaScripts
    foreach ($assets['scripts'] as $script) {
      if (preg_match('/^https?:\/\//i', $script->path)) {
        $url = $script->path; // Absolute path
      }
      else {
        $url = $rel_url . $script->path; // Relative path
      }

      // Make sure each file is only loaded once
      if (!in_array($url, self::$settings['loadedJs'])) {
        self::$settings['loadedJs'][] = $url;
        wp_enqueue_script($this->asset_handle(trim($script->path, '/')), $url, array(), urlencode(str_replace('?ver=', '', $script->version)));
      }
    }

    // Enqueue stylesheets
    foreach ($assets['styles'] as $style) {
      if (preg_match('/^https?:\/\//i', $style->path)) {
        $url = $style->path; // Absolute path
      }
      else {
        $url = $rel_url . $style->path; // Relative path
      }

      // Make sure each file is only loaded once
      if (!in_array($url, self::$settings['loadedCss'])) {
        self::$settings['loadedCss'][] = $url;
        wp_enqueue_style($this->asset_handle(trim($style->path, '/')), $url, array(), urlencode(str_replace('?ver=', '', $style->version)));
      }
    }
  }

  /**
   * Removes the file extension and replaces all specialchars with -
   *
   * @since 1.0.0
   * @param string $path
   * @return string
   */
  public function asset_handle($path) {
    return $this->plugin_slug . '-' . preg_replace(array('/\.[^.]*$/', '/[^a-z0-9]/i'), array('', '-'), strtolower($path));
  }

  /**
   * Get generic h5p settings
   *
   * @since 1.3.0
   */
  public function get_core_settings() {
    $current_user = wp_get_current_user();

    $core = $this->get_h5p_instance('core');
    $settings = array(
      'baseUrl' => get_site_url(),
      'url' => $this->get_h5p_url(),
      'postUserStatistics' => (get_option('h5p_track_user', TRUE) === '1') && $current_user->ID,
      'ajax' => array(
        'setFinished' => admin_url('admin-ajax.php?token=' . wp_create_nonce('h5p_result') . '&action=h5p_setFinished'),
        'contentUserData' => admin_url('admin-ajax.php?token=' . wp_create_nonce('h5p_contentuserdata') . '&action=h5p_contents_user_data&content_id=:contentId&data_type=:dataType&sub_content_id=:subContentId')
      ),
      'saveFreq' => get_option('h5p_save_content_state', FALSE) ? get_option('h5p_save_content_frequency', 30) : FALSE,
      'siteUrl' => get_site_url(),
      'l10n' => array(
        'H5P' => $core->getLocalization(),
      ),
      'hubIsEnabled' => get_option('h5p_hub_is_enabled', TRUE) == TRUE
    );

    if ($current_user->ID) {
      $settings['user'] = array(
        'name' => $current_user->display_name,
        'mail' => $current_user->user_email
      );
    }

    return $settings;
  }

  /**
   * Set core JavaScript settings and add core assets.
   *
   * @since 1.0.0
   */
  public function add_core_assets() {
    if (self::$settings !== null) {
      return; // Already added
    }

    self::$settings = $this->get_core_settings();
    self::$settings['core'] = array(
      'styles' => array(),
      'scripts' => array()
    );
    self::$settings['loadedJs'] = array();
    self::$settings['loadedCss'] = array();
    $cache_buster = '?ver=' . self::VERSION;

    // Use relative URL to support both http and https.
    $lib_url = plugins_url('h5p/h5p-php-library') . '/';
    $rel_path = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $lib_url);

    // Add core stylesheets
    foreach (H5PCore::$styles as $style) {
      self::$settings['core']['styles'][] = $rel_path . $style . $cache_buster;
      wp_enqueue_style($this->asset_handle('core-' . $style), $lib_url . $style, array(), self::VERSION);
    }

    // Add core JavaScript
    foreach (H5PCore::$scripts as $script) {
      self::$settings['core']['scripts'][] = $rel_path . $script . $cache_buster;
      wp_enqueue_script($this->asset_handle('core-' . $script), $lib_url . $script, array(), self::VERSION);
    }
  }

  /**
   * Add H5P JavaScript settings to the bottom of the page.
   *
   * @since 1.0.0
   */
  public function add_settings() {
    if (self::$settings !== null) {
      $this->print_settings(self::$settings);
    }
  }

  /**
   * JSON encode and print the given H5P JavaScript settings.
   *
   * @since 1.0.0
   * @param array $settings
   */
  public function print_settings(&$settings, $obj_name = 'H5PIntegration') {
    static $printed;
    if (!empty($printed[$obj_name])) {
      return; // Avoid re-printing settings
    }

    $json_settings = json_encode($settings);
    if ($json_settings !== FALSE) {
      $printed[$obj_name] = TRUE;
      print '<script>' . $obj_name . ' = ' . $json_settings . ';</script>';
    }
  }

  /**
   * Get added JavaScript settings.
   *
   * @since 1.0.0
   * @return array
   */
  public function get_settings() {
    return self::$settings;
  }

  /**
   * This function will unlink tmp editor files for content
   * that has never been saved.
   *
   * @since 1.0.0
   */
  public function remove_old_tmp_files() {
    global $wpdb;

    $older_than = time() - 86400;
    $num = 0; // Number of files deleted

    // Locate files not saved in over a day
    $files = $wpdb->get_results($wpdb->prepare(
        "SELECT path
           FROM {$wpdb->prefix}h5p_tmpfiles
          WHERE created_at < %d",
        $older_than)
      );

    // Delete files from file system
    foreach ($files as $file) {
      if (@unlink($file->path)) {
        $num++;
      }
    }

    // Remove from tmpfiles table
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}h5p_tmpfiles
          WHERE created_at < %d",
        $older_than));

    // Old way of cleaning up tmp files. Needed as a transitional fase and it doesn't really harm to have it here any way.
    $h5p_path = $this->get_h5p_path();
    $editor_path = $h5p_path . DIRECTORY_SEPARATOR . 'editor';
    if (is_dir($h5p_path) && is_dir($editor_path)) {
      $dirs = glob($editor_path . DIRECTORY_SEPARATOR . '*');
      if (!empty($dirs)) {
        foreach ($dirs as $dir) {
          if (!is_dir($dir)) {
            continue;
          }

          $files = glob($dir . DIRECTORY_SEPARATOR . '*');
          if (empty($files)) {
            continue;
          }

          foreach ($files as $file) {
            if (filemtime($file) < $older_than) {
              // Not modified in over a day
              if (unlink($file)) {
                $num++;
              }
            }
          }
        }
      }
    }

    if ($num) {
      // Clear cached value for dirsize.
      delete_transient('dirsize_cache');
    }
  }

  /**
   * Try to connect with H5P.org and look for updates to our libraries.
   * Can be disabled through settings
   *
   * @since 1.2.0
   */
  public function get_library_updates() {
    if (get_option('h5p_hub_is_enabled', TRUE) || get_option('h5p_send_usage_statistics', TRUE)) {
      $core = $this->get_h5p_instance('core');
      $core->fetchLibrariesMetadata();
    }
  }

  /**
   * Remove any log messages older than the set limit.
   *
   * @since 1.6
   */
  public function remove_old_log_events() {
    global $wpdb;

    $older_than = (time() - H5PEventBase::$log_time);

    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->prefix}h5p_events
		          WHERE created_at < %d
        ", $older_than));
  }
}
