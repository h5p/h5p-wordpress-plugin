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
 * TODO: Add embed
 * TODO: Test with PostgreSQL
 * TODO: Check i18n
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
  const VERSION = '1.1.0';

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
    
    // Check for updates
    add_action('plugins_loaded', array($this, 'check_for_updates'), 1);
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
    self::update_database();
    
    // Keep track of which DB we have.
    add_option('h5p_version', self::VERSION);
    
    // Cleaning rutine
    wp_schedule_event(time(), 'daily', 'h5p_daily_cleanup');
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
      UNIQUE KEY  (content_id, library_id, dependency_type)
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
    
    // Add new capabilities 
    $administrator = get_role('administrator');
    $administrator->add_cap('manage_h5p_libraries');
    $administrator->add_cap('manage_h5p_settings');
    $administrator->add_cap('manage_h5p_contents');
    
    $editor = get_role('editor');
    $editor->add_cap('manage_h5p_contents');
  }
  
  /**
   * @since 1.0.0
   */
  public static function deactivate() {
    // Remove cleaning rutine
    wp_clear_scheduled_hook('h5p_daily_cleanup');
  }
  
  /**
   * @since 1.1.0
   */
  public function check_for_updates() {
    if (get_option('h5p_version') !== self::VERSION) {
      self::update_database();
      update_option('h5p_version', self::VERSION);
    }
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
    load_plugin_textdomain($domain, FALSE, basename(plugin_dir_path(dirname( __FILE__ ))) . '/languages/');
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
   * @return string
   */
  public function get_h5p_url() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['baseurl'] . '/h5p';
  }
  
  /**
   * Get H5P language code from WordPress.
   * 
   * @since 1.0.0
   * @return string
   */
  public function get_language() {
    if (WPLANG !== '') {
      $languageParts = explode('_', WPLANG);
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
      $path = plugin_dir_path(__FILE__);
      include_once($path . '../h5p-php-library/h5p.classes.php');
      include_once($path . '../h5p-php-library/h5p-development.class.php');
      include_once($path . 'class-h5p-wordpress.php');
      
      self::$interface = new H5PWordPress();
      
      $language = $this->get_language();
      
      self::$core = new H5PCore(self::$interface, $this->get_h5p_url(), $language, get_option('h5p_export', TRUE)); 
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
    $id = isset($atts['id']) ? intval($atts['id']) : NULL;
    $content = $this->get_content($id);
    if (is_string($content)) {
      return current_user_can('manage_h5p_contents') ? $content : NULL;
    }
    
    return $this->add_assets($content);
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
    if (!isset(self::$settings['content'][$cid])) {
      $core = $this->get_h5p_instance('core');

      // Add JavaScript settings for this content
      self::$settings['content'][$cid] = array(
        'library' => H5PCore::libraryToString($content['library']),
        'jsonContent' => $core->filterParameters($content),
        'fullScreen' => $content['library']['fullscreen'],
        'exportUrl' => get_option('h5p_export', TRUE) ? $this->get_h5p_url() . '/exports/' . $content['id'] . '.h5p' : ''
      );
      
      // Get assets for this content
      $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
      $files = $core->getDependenciesFiles($preloaded_dependencies);
        
      if ($embed === 'div') {
        $this->enqueue_assets($files);
      }
      elseif ($embed === 'iframe') {
        self::$settings[$cid]['scripts'] = $core->getAssetsUrls($files['scripts']);
        self::$settings[$cid]['styles'] = $core->getAssetsUrls($files['styles']);
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
   * Enqueue assets for content embedded by div.
   * 
   * @param array $assets
   */
  public function enqueue_assets(&$assets) {
    $cut = $this->get_h5p_url() . '/libraries/';
    foreach ($assets['scripts'] as $script) {
      $url = $script->path . $script->version;
      if (!in_array($url, self::$settings['loadedJs'])) {
        self::$settings['loadedJs'][] = $url;
        wp_enqueue_script($this->asset_handle(str_replace($cut, '', $script->path)), $script->path, array(), str_replace('?ver', '', $script->version));
      }
    }
    foreach ($assets['styles'] as $style) {
      $url = $style->path . $style->version;
      if (!in_array($url, self::$settings['loadedCss'])) {
        self::$settings['loadedCss'][] = $url;
        wp_enqueue_style($this->asset_handle(str_replace($cut, '', $style->path)), $style->path, array(), str_replace('?ver', '', $style->version));
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
    return $this->plugin_slug . '-' . preg_replace(array('/\.[^.]*$/', '/[^a-z0-9]/i'), array('', '-'), $path);
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

    self::$settings = array(
      'core' => array(
        'styles' => array(),
        'scripts' => array()
      ),
      'url' => $this->get_h5p_url(),
      'exportEnabled' => get_option('h5p_export', TRUE),
      'h5pIconInActionBar' => get_option('h5p_icon', TRUE),
      'loadedJs' => array(),
      'loadedCss' => array(),
      'i18n' => array(
        'fullscreen' => __('Fullscreen', $this->plugin_slug),
        'disableFullscreen' => __('Disable fullscreen', $this->plugin_slug),
        'download' => __('Download', $this->plugin_slug),
        'copyrights' => __('Rights of use', $this->plugin_slug),
        'embed' => __('Embed', $this->plugin_slug),
        'copyrightInformation' => __('Rights of use', $this->plugin_slug),
        'close' => __('Close', $this->plugin_slug),
        'title' => __('Title', $this->plugin_slug),
        'author' => __('Author', $this->plugin_slug),
        'year' => __('Year', $this->plugin_slug),
        'source' => __('Source', $this->plugin_slug),
        'license' => __('License', $this->plugin_slug),
        'thumbnail' => __('Thumbnail', $this->plugin_slug),
        'noCopyrights' => __('No copyright information available for this content.', $this->plugin_slug),
        'downloadDescription' => __('Download this content as a H5P file.', $this->plugin_slug),
        'copyrightsDescription' => __('View copyright information for this content.', $this->plugin_slug),
        'embedDescription' => __('View the embed code for this content.', $this->plugin_slug),
        'h5pDescription' => __('Visit H5P.org to check out more cool content.', $this->plugin_slug),
        'upgradeLibrary' => __('Upgrade library content', $this->plugin_slug),
        'viewLibrary' => __('View library details', $this->plugin_slug),
        'deleteLibrary' => __('Delete library', $this->plugin_slug),
        'NA' => __('N/A', $this->plugin_slug)
      )
    );
    
    $cache_buster = '?ver=' . self::VERSION;
    
    // Add core stylesheets
    foreach (H5PCore::$styles as $style) {
      $style_url = plugins_url('h5p/h5p-php-library/' . $style);
      self::$settings['core']['styles'][] = $style_url . $cache_buster;
      wp_enqueue_style($this->asset_handle('core-' . $style), $style_url, array(), self::VERSION);
    }
    
    // Add custom WP style
    $style_url = plugins_url('h5p/public/styles/h5p-integration.css');
    self::$settings['core']['styles'][] = $style_url . $cache_buster;
    wp_enqueue_style($this->asset_handle('integration'), $style_url, array(), self::VERSION);
    
    // Add JavaScript with library framework integration
    $script_url = plugins_url('h5p/public/scripts/h5p-integration.js');
    self::$settings['core']['scripts'][] = $script_url . $cache_buster;
    wp_enqueue_script($this->asset_handle('integration'), $script_url, array(), self::VERSION);
    
    // Add core JavaScript
    foreach (H5PCore::$scripts as $script) {
      $script_url = plugins_url('h5p/h5p-php-library/' . $script);
      self::$settings['core']['scripts'][] = $script_url . $cache_buster;
      wp_enqueue_script($this->asset_handle('core-' . $script), $script_url, array(), self::VERSION);
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
  public function print_settings(&$settings) {
    $json_settings = json_encode($settings);
    if ($json_settings !== FALSE) {
      print '<script>H5P={settings:' . $json_settings . '}</script>';
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
    $plugin = H5P_Plugin::get_instance();
    foreach (glob($plugin->get_h5p_path() . DIRECTORY_SEPARATOR . 'editor' . DIRECTORY_SEPARATOR . '*') as $dir) {
      if (is_dir($dir)) {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $file) {
          if (time() - filemtime($file) > 86400) {
            // Not modified in over a day
            unlink($file);
          }
        }
      }
    }
  }
}