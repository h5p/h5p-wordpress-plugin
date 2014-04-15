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
 * @package H5P_Plugin_Admin
 * @author  Joubel <contact@joubel.com>
 */
class H5P_Plugin_Admin {

  /**
   * Instance of this class.
   *
   * @since    1.0.0
   * @var      object
   */
  protected static $instance = null;

  /**
   * Initialize the plugin by loading admin scripts & styles and adding a
   * settings page and menu.
   *
   * @since     1.0.0
   */
  private function __construct() {
    $plugin = H5P_Plugin::get_instance();
    $this->plugin_slug = $plugin->get_plugin_slug();

    // Load admin style sheet and JavaScript.
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

    // Add the options page and menu item.
    add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
    
    // Custom media button for inserting H5Ps.
    add_action('media_buttons_context', array($this, 'add_insert_button'));
    add_action('wp_ajax_h5p_contents', array($this, 'ajax_select_content'));
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
   * Register and enqueue admin-specific style sheet.
   *
   * @since     1.0.0
   * @return    null    Return early if no settings page is registered.
   */
  public function enqueue_admin_styles() {
    wp_enqueue_style($this->plugin_slug . '-admin-styles', plugins_url('styles/admin.css', __FILE__), array(), H5P_Plugin::VERSION);
  }

  /**
   * Register and enqueue admin-specific JavaScript.
   *
   * @since     1.0.0
   * @return    null    Return early if no settings page is registered.
   */
  public function enqueue_admin_scripts() {
    // Remember to check get_current_screen()->id if including page specific stuff
  }

  /**
   * Register the administration menu for this plugin into the WordPress Dashboard menu.
   *
   * @since    1.0.0
   */
  public function add_plugin_admin_menu() {
    $h5p_content = __('H5P Content', $this->plugin_slug);
    add_menu_page($h5p_content, $h5p_content, 'manage_options', $this->plugin_slug, array($this, 'display_all_content_page'), 'none');

    $all_h5p_content = __('All H5P Content', $this->plugin_slug);
    add_submenu_page($this->plugin_slug, $all_h5p_content, $all_h5p_content, 'manage_options', $this->plugin_slug, array($this, 'display_all_content_page'));
    
    $add_new = __('Add New', $this->plugin_slug);
    add_submenu_page($this->plugin_slug, $add_new, $add_new, 'manage_options', $this->plugin_slug . '_new', array($this, 'display_new_content_page'));
    
    // add_menu_page returns the slug. Keep it if we should add page specific styles or scripts.
  }

  /**
   * Display a list of all h5p content.
   *
   * @since    1.0.0
   */
  public function display_all_content_page() {
    include_once('views/all-content.php');
  }
  
  /**
   * Display a form for adding h5p content.
   *
   * @since    1.0.0
   */
  public function display_new_content_page() {
    if (isset($_FILES['h5p_file']) && $_FILES['h5p_file']['error'] === 0) {
      // TODO: Create H5PWordPress and make sure getUploadedH5pPath returns $_FILES['h5p_file']['tmp_name'].

      $plugin = H5P_Plugin::get_instance();
      $validator = $plugin->get_h5p_instance('validator');
      
      if ($validator->isValidPackage()) {
        $storage = $plugin->get_h5p_instance('storage');
        $storage->savePackage(); // TODO: Should we make h5p-php-library use auto increment id? 
      }
      else {
        // The uploaded file was not a valid H5P package
      }
    }
    
    include_once('views/new-content.php');
  }
  
  /**
   * Add custom media button for selecting H5P content.
   *
   * @since    1.0.0
   */
  public function add_insert_button() {
    $ajax_url = add_query_arg( 
      array( 
          'action' => 'h5p_contents',
      ), 
      'admin-ajax.php'
    ); 
    return '<a href="' . $ajax_url . '" class="button thickbox" title="Select and insert H5P Interactive Content">Add H5P</a>';
  }
 
  /**
   * List to select H5P content from.
   *
   * @since    1.0.0
   */ 
  public function ajax_select_content() {
    print '<p>Select the H5P Content you wish to insert.</p>';
    exit;
  }
}
