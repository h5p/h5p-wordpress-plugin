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
 * @author  Joubel <contact@joubel.com>
 */
class H5P_Plugin {

  /**
   * Plugin version, used for cache-busting of style and script file references.
   *
   * @since   1.0.0
   * @var     string
   */
  const VERSION = '1.0.0';

  /**
   * The Unique identifier for this plugin.
   *
   * @since    1.0.0
   * @var      string
   */
  protected $plugin_slug = 'h5p';

  /**
   * Instance of this class.
   *
   * @since    1.0.0
   * @var      object
   */
  protected static $instance = null;

  /**
   * Initialize the plugin by setting localization and loading public scripts
   * and styles.
   *
   * @since     1.0.0
   */
  private function __construct() {
    // Load plugin text domain
    add_action('init', array($this, 'load_plugin_textdomain'));

    // Load public-facing style sheet and JavaScript.
    add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

    // TODO: add_shortcode('h5p', 'h5p_shortcode'); ?
    // http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
  }

  /**
   * Return the plugin slug.
   *
   * @since    1.0.0
   * @return    Plugin slug variable.
   */
  public function get_plugin_slug() {
    return $this->plugin_slug;
  }

  /**
   * Return an instance of this class.
   *
   * @since     1.0.0
   * @return    object    A single instance of this class.
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
   * @since    1.0.0
   * @param    boolean    $network_wide    True if WPMU superadmin uses
   *                                       "Network Activate" action, false if
   *                                       WPMU is disabled or plugin is
   *                                       activated on an individual blog.
   */
  public static function activate($network_wide) {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Keep track of h5p content entities
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_contents (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      created_at TIMESTAMP NOT NULL,
      updated_at TIMESTAMP NOT NULL,
      title VARCHAR(255) NOT NULL,
      library_id INT UNSIGNED NOT NULL,
      parameters LONGTEXT NOT NULL,
      embed_type VARCHAR(127) NOT NULL,
      content_type VARCHAR(127) NULL,
      author VARCHAR(127) NULL,
      license VARCHAR(7) NULL,
      keywords TEXT NULL,
      description TEXT NULL,
      UNIQUE KEY  (id)
    );");

    // Keep track of content dependencies
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_contents_libraries (
      content_id INT UNSIGNED NOT NULL,
      library_id INT UNSIGNED NOT NULL,
      dependency_type VARCHAR(255) NOT NULL,
      drop_css TINYINT UNSIGNED NOT NULL,
      UNIQUE KEY  (content_id, library_id)
    );");
    
    // Keep track of h5p libraries
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_libraries (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      created_at TIMESTAMP NOT NULL,
      updated_at TIMESTAMP NOT NULL,
      name VARCHAR(255) NOT NULL,
      title VARCHAR(255) NOT NULL,
      major_version INT UNSIGNED NOT NULL,
      minor_version INT UNSIGNED NOT NULL,
      patch_version INT UNSIGNED NOT NULL,
      runnable INT UNSIGNED NOT NULL,
      fullscreen INT UNSIGNED NOT NULL,
      embed_types VARCHAR(255) NOT NULL,
      preloaded_js TEXT NULL,
      preloaded_css TEXT NULL,
      drop_library_css TEXT NULL,
      semantics TEXT NOT NULL,
      UNIQUE KEY  (id)
    );");
    
    // Keep track of h5p library dependencies
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_libraries_libraries (
      library_id INT UNSIGNED NOT NULL,
      required_library_id INT UNSIGNED NOT NULL,
      dependency_type VARCHAR(255) NOT NULL,
      UNIQUE KEY  (library_id, required_library_id)
    );");
    
    // Keep track of h5p library translations
    dbDelta("CREATE TABLE {$wpdb->prefix}h5p_libraries_languages (
      library_id INT UNSIGNED NOT NULL,
      language_code VARCHAR(255) NOT NULL,
      translation TEXT NOT NULL,
      UNIQUE KEY  (library_id, language_code)
    );");
    
    // Keep track of which DB we have.
    add_option('h5p_version', self::VERSION);
  }

  /**
   * Load the plugin text domain for translation.
   *
   * @since    1.0.0
   */
  public function load_plugin_textdomain() {
    $domain = $this->plugin_slug;
    $locale = apply_filters('plugin_locale', get_locale(), $domain);

    load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
    load_plugin_textdomain($domain, FALSE, basename(plugin_dir_path(dirname( __FILE__ ))) . '/languages/');
  }

  /**
   * Register and enqueue public-facing style sheets and JavaScript files.
   *
   * @since    1.0.0
   */
  public function enqueue_styles_and_scripts() {
    wp_enqueue_style($this->plugin_slug . '-plugin-styles', plugins_url('h5p/h5p-php-library/styles/h5p.css'), array(), self::VERSION);
    //wp_enqueue_script($this->plugin_slug . '-plugin-script', plugins_url('assets/js/public.js', __FILE__), array('jquery'), self::VERSION);
  }
 
  /**
   * @since    1.0.0
   */
  public function get_h5p_path() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['path'] . '/h5p';
  }
  
  /**
   * 
   *
   * @since    1.0.0
   */ 
  public function get_h5p_instance($type) {
    static $interface, $core;

    if (!isset($interface)) {
      $path = plugin_dir_path(__FILE__);
      include_once($path . '../h5p-php-library/h5p.classes.php');
      include_once($path . '../h5p-php-library/h5p-development.class.php');
      include_once($path . 'class-h5p-wordpress.php');
      
      $interface = new H5PWordPress();

      // TODO: Add support for development mode
      // TODO: Add support for language
      $core = new H5PCore($interface, $this->get_h5p_path(), 'und', H5PDevelopment::MODE_NONE);
    }

    switch ($type) {
      case 'validator':
        return new H5PValidator($interface, $core);
      case 'storage':
        return new H5PStorage($interface, $core);
      case 'contentvalidator':
        return new H5PContentValidator($interface, $core);
      case 'export':
        return new H5PExport($interface, $core);
      case 'interface':
        return $interface;
      case 'core':
        return $core;
    }
  }
}
