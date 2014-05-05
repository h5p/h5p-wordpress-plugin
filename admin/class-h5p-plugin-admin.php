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
 * @author Joubel <contact@joubel.com>
 */
class H5P_Plugin_Admin {

  /**
   * Instance of this class.
   *
   * @since 1.0.0
   * @var \H5P_Plugin_Admin
   */
  protected static $instance = null;
  
  /**
   * Instance of this class.
   *
   * @since 1.0.0
   * @var \H5peditor
   */
  protected static $h5peditor = null;

  /**
   * Initialize the plugin by loading admin scripts & styles and adding a
   * settings page and menu.
   *
   * @since 1.0.0
   */
  private function __construct() {
    $plugin = H5P_Plugin::get_instance();
    $this->plugin_slug = $plugin->get_plugin_slug();

    // Load admin style sheet and JavaScript.
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles_and_scripts'));

    // Add the options page and menu item.
    add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
    
    // Custom media button for inserting H5Ps.
    add_action('media_buttons_context', array($this, 'add_insert_button'));
    add_action('wp_ajax_h5p_contents', array($this, 'ajax_select_content'));
    
    // Editor ajax
    add_action('wp_ajax_h5p_libraries', array($this, 'ajax_libraries'));
    add_action('wp_ajax_h5p_files', array($this, 'ajax_files'));
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
    // Remember to check get_current_screen()->id if including page specific stuff 
  }

  /**
   * Register the administration menu for this plugin into the WordPress Dashboard menu.
   *
   * @since 1.0.0
   */
  public function add_plugin_admin_menu() {
    $h5p_content = __('H5P Content', $this->plugin_slug);
    add_menu_page($h5p_content, $h5p_content, 'manage_options', $this->plugin_slug, array($this, 'display_all_content_page'), 'none');

    $all_h5p_content = __('All H5P Content', $this->plugin_slug);
    add_submenu_page($this->plugin_slug, $all_h5p_content, $all_h5p_content, 'manage_options', $this->plugin_slug, array($this, 'display_all_content_page'));
    
    $add_new = __('Add New', $this->plugin_slug);
    add_submenu_page($this->plugin_slug, $add_new, $add_new, 'manage_options', $this->plugin_slug . '_new', array($this, 'display_new_content_page'));

    // add_menu_page returns the id? Keep it if we should add page specific styles or scripts.
  }
  
  /**
   * Display a list of all h5p content.
   *
   * @since 1.0.0
   */
  public function display_all_content_page() {
    switch (filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING)) {
      case NULL:
        $contents = $this->get_contents();
        include_once('views/all-content.php');
        return;
      
      case 'show':
        // Admin preview of H5P content.
        $plugin = H5P_Plugin::get_instance();
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $content = $plugin->get_content($id);
        if (is_string($content)) {
          print '<div class="error">' . $content . '</div>';
        }
        else {
          // TODO: Change page title? (wp_title)
          $title = ($content['title'] === '' ? 'H5P ' . $id : $content['title']);
          $embed_code = $plugin->add_assets($content, TRUE);
          include_once('views/show-content.php');
          H5P_Plugin::get_instance()->add_settings();
        }
        return;
    }
    
    print '<div class="wrap"><h2>Unknown task.</h2></div>';
  }
  
  /**
   * Get list of H5P contents.
   * 
   * @global \wpdb $wpdb
   * @return array
   */
  public function get_contents() {
    global $wpdb;
    return $wpdb->get_results(
        "SELECT id, title
          FROM {$wpdb->prefix}h5p_contents
          ORDER BY title, id"
      );
  }
  
  /**
   * Display a form for adding h5p content.
   *
   * @since 1.0.0
   */
  public function display_new_content_page() {
    if (isset($_FILES['h5p_file']) && $_FILES['h5p_file']['error'] === 0) {
      // Handle file upload
      check_admin_referer('h5p_upload_content', 'yes_sir_will_do');

      $plugin = H5P_Plugin::get_instance();
      $validator = $plugin->get_h5p_instance('validator');
      $interface = $plugin->get_h5p_instance('interface');
      
      // Move so core can validate the file extension.
      rename($_FILES['h5p_file']['tmp_name'], $interface->getUploadedH5pPath());
      
      if ($validator->isValidPackage()) {
        $storage = $plugin->get_h5p_instance('storage');
        $storage->savePackage();
        
        wp_safe_redirect(
          add_query_arg(
            array(
              'page' => 'h5p',
              'task' => 'show',
              'id' => $storage->contentId
            ),
            wp_get_referer()
          )
        );
        return;
      }
      else {
        // The uploaded file was not a valid H5P package
        unlink($interface->getUploadedH5pPath());
      }
    }
    
    // TODO: Validate if editor is used?
    // TODO: Editor assets.
    
    $library = 0;
    $parameters = '{}';
    include_once('views/new-content.php');
    $this->add_editor_assets();
  }
  
  /**
   * Add custom media button for selecting H5P content.
   *
   * @since 1.0.0
   * @return string
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
   * @since 1.0.0
   */ 
  public function ajax_select_content() {
    $contents = $this->get_contents();
    include_once('views/select-content.php');
    exit;
  }
  
  /**
   * Print messages.
   * 
   * @since 1.0.0
   */
  public function print_messages() {
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
   * Returns the instance of the h5p editor library.
   * 
   * @return \H5peditor
   */
  public function get_h5peditor_instance() {
    if (self::$h5peditor === null) {
      $path = plugin_dir_path(__FILE__);
      include_once($path . '../h5p-editor-php-library/h5peditor.class.php');
      include_once($path . '../h5p-editor-php-library/h5peditor-file.class.php');
      include_once($path . '../h5p-editor-php-library/h5peditor-storage.interface.php');
      include_once($path . 'class-h5p-editor-wordpress-storage.php');
      
      $upload_dir = wp_upload_dir();
      $plugin = H5P_Plugin::get_instance();
      self::$h5peditor = new H5peditor(
        $plugin->get_h5p_instance('core'),
        new H5PEditorWordPressStorage(),
        $upload_dir['basedir'],
        ''
      );
    }
    
    return self::$h5peditor;
  }
  
  /**
   * Get proper handle for the given asset
   * 
   * @since 1.0.0
   * @param string $path
   * @return string
   */
  private function asset_handle($path) {
    $plugin = H5P_Plugin::get_instance();
    return $plugin->asset_handle($path);
  }
  
  /**
   * Small helper for simplifying script enqueuing.
   * 
   * @since 1.0.0
   * @param string $handle
   * @param string $path
   */
  private function add_script($handle, $path) {
    wp_enqueue_script($this->asset_handle($handle), plugins_url('h5p/' . $path), array(), H5P_Plugin::VERSION);
  }
  
  /**
   * Small helper for simplifying style enqueuing.
   * 
   * @since 1.0.0
   * @param string $handle
   * @param string $path
   */
  private function add_style($handle, $path) {
    wp_enqueue_style($this->asset_handle($handle), plugins_url('h5p/' . $path), array(), H5P_Plugin::VERSION);
  }
  
  /**
   * Add assets and JavaScript settings for the editor.
   * 
   * @since 1.0.0
   * @param int $id optional content identifier
   */
  public function add_editor_assets($id = NULL) {
    $plugin = H5P_Plugin::get_instance();
    $plugin->add_core_assets();
    
    // Make sure the h5p classes are loaded
    $plugin->get_h5p_instance('core');
    $this->get_h5peditor_instance();

    // Add editor styles
    foreach (H5peditor::$styles as $style) {
      $this->add_style('editor-' . $style, 'h5p-editor-php-library/' . $style);
    }
    
    // Add editor JavaScript
    foreach (H5peditor::$scripts as $script) {
      $this->add_script('editor-' . $script, 'h5p-editor-php-library/' . $script);
    }
    
    // Add JavaScript with library framework integration (editor part)
    $this->add_script('editor', 'admin/scripts/h5p-editor.js');
    
    // Add translation
    $language = $plugin->get_language();
    $language_script = 'h5p-editor-php-library/language/' . $language . '.js';
    if (!file_exists(plugin_dir_path(__FILE__) . '../' . $language_script)) {
      $language_script = 'h5p-editor-php-library/language/en.js';
    }
    $this->add_script('language', $language_script);
    
    // Add JavaScript settings
    $settings = $plugin->get_settings();
    $settings['editor'] = array(
      'fileIcon' => array(
        'path' => plugins_url('h5p/h5p-editor-php-library/images/binary-file.png'),
        'width' => 50,
        'height' => 50,
      ),
      'ajaxPath' => add_query_arg(
        array(
          'action' => 'h5p_',
        ), 
        'admin-ajax.php'
      ),
      'libraryUrl' => plugin_dir_url('h5p/h5p-editor-php-library/h5peditor.class.php'),
      'copyrightSemantics' => H5PContentValidator::getCopyrightSemantics()
    );

    if ($id !== NULL) {
      $settings['editor']['nodeVersionId'] = $id;
    }
    
    $plugin->print_settings($settings);
  }
  
  /**
   * 
   * 
   * @since 1.0.0
   */
  public function ajax_libraries() {
    $editor = $this->get_h5peditor_instance();
    
    $name = filter_input(INPUT_GET, 'machineName', FILTER_SANITIZE_STRING);
    $major_version = filter_input(INPUT_GET, 'majorVersion', FILTER_SANITIZE_NUMBER_INT);
    $minor_version = filter_input(INPUT_GET, 'minorVersion', FILTER_SANITIZE_NUMBER_INT);

    header('Cache-Control: no-cache');
    header('Content-type: application/json');
    
    if ($name) {
      print $editor->getLibraryData($name, $major_version, $minor_version);
    }
    else {
      print $editor->getLibraries();
    }
    
    exit;
  }
  
  /**
   * 
   * 
   * @since 1.0.0
   */
  public function ajax_files() {
    $plugin = H5P_Plugin::get_instance();
    $files_directory = $plugin->get_h5p_path();
    
    $contentId = filter_input(INPUT_POST, 'contentId', FILTER_SANITIZE_NUMBER_INT);
    if ($contentId) {
      $files_directory .=  '/content/' . $contentId;
    }
    else {
      $files_directory .= '/editor';
    }

    $editor = $this->get_h5peditor_instance();
    $interface = $plugin->get_h5p_instance('interface');
    $file = new H5peditorFile($interface, $files_directory);

    if (!$file->isLoaded()) {
      exit;
    }

    if ($file->validate() && $file->copy()) {
      // Keep track of temporary files so they can be cleaned up later.
      $editor->addTmpFile($file);
    }
    
    header('Cache-Control: no-cache');
    header('Content-type: application/json');

    print $file->getResult();
    exit;
  }
}
