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
 * TODO: Add settings page
 * TODO: Add library admin
 * TODO: Add development mode
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
   * Instance of this class.
   *
   * @since 1.0.0
   * @var \H5peditor
   */
  protected static $h5peditor = NULL;
  
  /**
   * Keep track of the current content.
   * 
   * @since 1.0.0
   */
  private $content = NULL;

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
    
    // Alter title on some pages.
    add_filter('admin_title', array($this, 'title'), 10, 2);
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
    $h5p_content = __('H5P Content', $this->plugin_slug);
    add_menu_page($h5p_content, $h5p_content, 'manage_options', $this->plugin_slug, array($this, 'display_all_content_page'), 'none');
    
    $all_h5p_content = __('All H5P Content', $this->plugin_slug);
    add_submenu_page($this->plugin_slug, $all_h5p_content, $all_h5p_content, 'manage_options', $this->plugin_slug, array($this, 'display_all_content_page'));
    
    $add_new = __('Add New', $this->plugin_slug);
    add_submenu_page($this->plugin_slug, $add_new, $add_new, 'manage_options', $this->plugin_slug . '_new', array($this, 'display_new_content_page'));
  }

  /**
   * Load content and add to title for certain pages.
   * 
   * @param type $admin_title
   * @param type $title
   * @return type
   */
  public function title($admin_title, $title) {
    // Should we have used get_current_screen() ?   
    
    $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING);
    $task = filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    $show = ($page === 'h5p' && $task === 'show');
    $edit = ($page === 'h5p_new');
    
    if (($show || $edit) && $id !== NULL) {
      $plugin = H5P_Plugin::get_instance();
      $this->content = $plugin->get_content($id);

      if (!is_string($this->content)) {
        if ($edit) {
          $admin_title = str_replace($title, 'Edit', $admin_title);
        }
        $admin_title = esc_html($this->content['title']) . ' &lsaquo; ' . $admin_title;
      }
    }
    
    return $admin_title;
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
        $datetimeformat = get_option('date_format') . ' ' . get_option('time_format');
        $offset = get_option('gmt_offset') * 3600;
        include_once('views/all-content.php');
        return;
      
      case 'show':
        // Admin preview of H5P content.
        if (is_string($this->content)) {
          $this->set_error($this->content);
          $this->print_messages();
        }
        else {
          $plugin = H5P_Plugin::get_instance();
          $embed_code = $plugin->add_assets($this->content, TRUE);
          include_once('views/show-content.php');
          H5P_Plugin::get_instance()->add_settings();
        }
        return;
    }
    
    print '<div class="wrap"><h2>' . esc_html__('Unknown task.', $this->plugin_slug) . '</h2></div>';
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
        "SELECT id, title, created_at, updated_at
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
    $plugin = H5P_Plugin::get_instance();
    
    // Try to load current content if any (editing)
    if ($this->content !== NULL) {
      if (is_string($this->content)) {
        $this->set_error($this->content);
        $this->content = NULL;
      }
    }
    $contentExists = ($this->content !== NULL);
    
    // Check if we're deleting content
    $delete = filter_input(INPUT_GET, 'delete');
    if ($delete && $contentExists) {
      if (wp_verify_nonce($delete, 'deleting_h5p_content')) {
        $core = $plugin->get_h5p_instance('core');
        $core->h5pF->deleteContentData($this->content['id']);
        $this->delete_export($this->content['id']);
        wp_safe_redirect(add_query_arg(array('page' => 'h5p'), wp_get_referer()));
        return;
      }
      $this->set_error(__('Invalid confirmation code, not deleting.'));
    }
    
    // Check if we're uploading or creating content
    $action = filter_input(INPUT_POST, 'action', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(upload|create)$/')));
    if ($action) {
      check_admin_referer('h5p_content', 'yes_sir_will_do'); // Verify form
      
      $result = FALSE;
      if ($action === 'create') {
        // Handle creation of new content.
        $result = $this->handle_content_creation($this->content);
      }
      elseif (isset($_FILES['h5p_file']) && $_FILES['h5p_file']['error'] === 0) {
        // Handle file upload
        $result = $this->handle_upload($this->content);
      }
      
      if ($result) {
        $this->delete_export($result);
        wp_safe_redirect(
          add_query_arg(
            array(
              'page' => 'h5p',
              'task' => 'show',
              'id' => $result
            ),
            wp_get_referer()
          )
        );
        return;
      }
    }
    
    // Prepare form
    $title = $this->get_input('title', $contentExists ? $this->content['title'] : '');
    $library = $this->get_input('library', $contentExists ? H5PCore::libraryToString($this->content['library']) : 0);
    $parameters = $this->get_input('parameters', $contentExists ? $this->content['params'] : '{}');
    
    include_once('views/new-content.php');
    $this->add_editor_assets($contentExists ? $this->content['id'] : NULL);
  }

  /**
   * Remove h5p export file.
   * 
   * @since 1.0.0
   * @param int $contentId
   */
  private function delete_export($contentId) {
    $plugin = H5P_Plugin::get_instance();
    $export = $plugin->get_h5p_instance('export');
    $export->deleteExport($contentId);
  }
  
  /**
   * 
   * 
   * @since 1.0.0
   * @param array $content
   * @return boolean
   */
  private function handle_content_creation($content) {
    // Keep track of the old library and params
    $oldLibrary = NULL;
    $oldParams = NULL;
    if ($content !== NULL) {
      $oldLibrary = $content['library'];
      $oldParams = json_decode($content['params']);
    }
    else {
      $content = array();
    }
    
    $plugin = H5P_Plugin::get_instance();
    $core = $plugin->get_h5p_instance('core');
    
    // Get library
    $content['library'] = $core->libraryFromString($this->get_input('library'));
    if (!$content['library']) {
      $core->h5pF->setErrorMessage(__('Invalid library.', $this->plugin_slug));
      return FALSE;
    }
    
    // Check if library exists.
    $content['library']['libraryId'] = $core->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'], $content['library']['$minorVersion']);
    if (!$content['library']['libraryId']) {
      $core->h5pF->setErrorMessage(__('No such library.', $this->plugin_slug));
      return FALSE;
    }
    
    // Get title
    $content['title'] = $this->get_input_title();
    if ($content['title'] === NULL) {
      return FALSE;
    }
    
    // Check parameters
    $content['params'] = $this->get_input('parameters');
    if ($content['params'] === NULL) {
      return FALSE;
    }
    $params = json_decode($content['params']);
    if ($params === NULL) {
      $core->h5pF->setErrorMessage(__('Invalid parameters.', $this->plugin_slug));
      return FALSE;
    }
    
    // Save new content
    $content['id'] = $core->h5pF->saveContentData($content);
    
    // Create content directory
    $editor = $this->get_h5peditor_instance();
    if (!$editor->createDirectories($content['id'])) {
      $core->h5pF->setErrorMessage(__('Unable to create content directory.', $this->plugin_slug));
      // Remove content.
      $core->h5pF->deleteContentData($content['id']);
      return FALSE;
    }

    // Move images and find all content dependencies
    $editor->processParameters($content['id'], $content['library'], $params, $oldLibrary, $oldParams);
    return $content['id'];
  }
  
  /**
   * Set error message.
   * 
   * @param string $message
   */
  private function set_error($message) {
    $plugin = H5P_Plugin::get_instance();
    $core = $plugin->get_h5p_instance('core');
    $core->h5pF->setErrorMessage($message);
  }
  
  /**
   * Get input post data field.
   * 
   * @param string $field The field to get data for.
   * @param string $default Optional default return.
   * @return string
   */
  private function get_input($field, $default = NULL) {
    // Get field
    $value = filter_input(INPUT_POST, $field);
    if ($value === NULL) {
      if ($default === NULL) {
        // No default, set error message.
        $this->set_error(sprintf(__('Missing %s.', $this->plugin_slug), $field));
      }
      return $default;
    }
    
    return $value;
  }
  
  /**
   * Get input post data field title. Validates.
   * 
   * @return string
   */
  private function get_input_title() {
    $title = $this->get_input('title');
    if ($title === NULL) {
      return NULL;
    }
    
    // Trim title and check length
    $trimmed_title = trim($title);
    if ($trimmed_title === '') {
      $this->set_error(sprintf(__('Missing %s.', $this->plugin_slug), 'title'));
      return NULL;
    }
    
    if (strlen($trimmed_title) > 255) {
      $this->set_error(__('Title is too long. Must be 256 letters or shorter.', $this->plugin_slug));
      return NULL;
    }
    
    return $trimmed_title;
  }
  
  /**
   * Handle upload of new H5P content file.
   * 
   * @since 1.0.0
   * @param array $content
   * @return boolean
   */
  private function handle_upload($content) {
    $plugin = H5P_Plugin::get_instance();
    $validator = $plugin->get_h5p_instance('validator');
    $interface = $plugin->get_h5p_instance('interface');

    // Move so core can validate the file extension.
    rename($_FILES['h5p_file']['tmp_name'], $interface->getUploadedH5pPath());

    if ($content === NULL) {
      $content = array();
    }
    $content['title'] = $this->get_input_title();
    
    if ($validator->isValidPackage() && $content['title'] !== NULL) {
      $storage = $plugin->get_h5p_instance('storage');
      $storage->savePackage($content);
      return $storage->contentId;
    }
    else {
      // The uploaded file was not a valid H5P package
      unlink($interface->getUploadedH5pPath());
    }
    
    return FALSE;
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
          print '<li>' . esc_html($message) . '</li>';
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
