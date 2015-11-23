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
 * H5P Content Admin class
 *
 * @package H5P_Plugin_Admin
 * @author Joubel <contact@joubel.com>
 */
class H5PContentAdmin {

  /**
   * @since 1.1.0
   */
  private $plugin_slug = NULL;

  /**
   * Editor instance
   *
   * @since 1.1.0
   * @var \H5peditor
   */
  protected static $h5peditor = NULL;

  /**
   * Keep track of the current content.
   *
   * @since 1.1.0
   */
  private $content = NULL;

  /**
   * Are we inserting H5P content on this page?
   *
   * @since 1.2.0
   */
  private $insertButton = FALSE;

  /**
   * Initialize content admin and editor
   *
   * @since 1.1.0
   * @param string $plugin_slug
   */
  public function __construct($plugin_slug) {
    $this->plugin_slug = $plugin_slug;
  }

  /**
   * Load content and alter page title for certain pages.
   *
   * @since 1.1.0
   * @param string $page
   * @param string $admin_title
   * @param string $title
   * @return string
   */
  public function alter_title($page, $admin_title, $title) {
    $task = filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    // Find content title
    $show = ($page === 'h5p' && ($task === 'show' || $task === 'results'));
    $edit = ($page === 'h5p_new');
    if (($show || $edit) && $id !== NULL) {
      if ($this->content === NULL) {
        $plugin = H5P_Plugin::get_instance();
        $this->content = $plugin->get_content($id);
      }

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
   * Permission check. Can the current user edit the given content?
   *
   * @since 1.1.0
   * @param array $content
   * @return boolean
   */
  private function current_user_can_edit($content) {
    if (current_user_can('edit_others_h5p_contents')) {
      return TRUE;
    }

    $user_id = get_current_user_id();
    if (is_array($content)) {
      return ($user_id === (int)$content['user_id']);
    }

    return ($user_id === (int)$content->user_id);
  }

  /**
   * Permission check. Can the current user view results for the given content?
   *
   * @since 1.2.0
   * @param array $content
   * @return boolean
   */
  private function current_user_can_view_content_results($content) {
    if (!get_option('h5p_track_user', TRUE)) {
      return FALSE;
    }

    return $this->current_user_can_edit($content);
  }

  /**
   * Display a list of all h5p content.
   *
   * @since 1.1.0
   */
  public function display_contents_page() {
    switch (filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING)) {
      case NULL:
        include_once('views/contents.php');

        $headers = array(
          (object) array(
            'text' => __('Title', $this->plugin_slug),
            'sortable' => TRUE
          ),
          (object) array(
            'text' => __('Content type', $this->plugin_slug),
            'sortable' => TRUE
          ),
          (object) array(
            'text' => __('Created', $this->plugin_slug),
            'sortable' => TRUE
          ),
          (object) array(
            'text' => __('Last modified', $this->plugin_slug),
            'sortable' => TRUE
          ),
          (object) array(
            'text' => __('Author', $this->plugin_slug),
            'sortable' => TRUE
          )
        );
        if (get_option('h5p_track_user', TRUE)) {
          $headers[] = (object) array(
            'class' => 'h5p-results-link'
          );
        }
        $headers[] = (object) array(
          'class' => 'h5p-edit-link'
        );

        $plugin_admin = H5P_Plugin_Admin::get_instance();
        $plugin_admin->print_data_view_settings(
          'h5p-contents',
          admin_url('admin-ajax.php?action=h5p_contents'),
          $headers,
          array(true),
          __("No H5P content available. You must upload or create new content.", $this->plugin_slug),
          (object) array(
            'by' => 3,
            'dir' => 0
          )
        );
        return;

      case 'show':
        // Admin preview of H5P content.
        if (is_string($this->content)) {
          H5P_Plugin_Admin::set_error($this->content);
          H5P_Plugin_Admin::print_messages();
        }
        else {
          $plugin = H5P_Plugin::get_instance();
          $embed_code = $plugin->add_assets($this->content);
          include_once('views/show-content.php');
          H5P_Plugin::get_instance()->add_settings();
        }
        return;

      case 'results':
        // View content results
        if (is_string($this->content)) {
          H5P_Plugin_Admin::set_error($this->content);
          H5P_Plugin_Admin::print_messages();
        }
        else {
          // Print HTML
          include_once('views/content-results.php');
          $plugin_admin = H5P_Plugin_Admin::get_instance();
          $plugin_admin->print_data_view_settings(
            'h5p-content-results',
            admin_url('admin-ajax.php?action=h5p_content_results&id=' . $this->content['id']),
            array(
              (object) array(
                'text' => __('User', $this->plugin_slug),
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
            __("There are no logged results for this content.", $this->plugin_slug),
            (object) array(
              'by' => 4,
              'dir' => 0
            )
          );
        }
        return;
    }

    print '<div class="wrap"><h2>' . esc_html__('Unknown task.', $this->plugin_slug) . '</h2></div>';
  }

  /**
   * Handle form submit when uploading, deleteing or editing H5Ps.
   * TODO: Rename to process_content_form ?
   *
   * @since 1.1.0
   */
  public function process_new_content() {
    $plugin = H5P_Plugin::get_instance();

    // Check if we have any content or errors loading content
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    if ($id) {
      $this->content = $plugin->get_content($id);
      if (is_string($this->content)) {
        H5P_Plugin_Admin::set_error($this->content);
        $this->content = NULL;
      }
    }

    if ($this->content !== NULL) {
      // We have existing content

      if (!$this->current_user_can_edit($this->content)) {
        // The user isn't allowed to edit this content
        H5P_Plugin_Admin::set_error(__('You are not allowed to edit this content.', $this->plugin_slug));
        return;
      }

      // Check if we're deleting content
      $delete = filter_input(INPUT_GET, 'delete');
      if ($delete) {
        if (wp_verify_nonce($delete, 'deleting_h5p_content')) {
          $core = $plugin->get_h5p_instance('core');
          $core->h5pF->deleteContentData($this->content['id']);
          $this->delete_export($this->content);
          wp_safe_redirect(admin_url('admin.php?page=h5p'));
          return;
        }
        H5P_Plugin_Admin::set_error(__('Invalid confirmation code, not deleting.', $this->plugin_slug));
      }
    }

    // Check if we're uploading or creating content
    $action = filter_input(INPUT_POST, 'action', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^(upload|create)$/')));
    if ($action) {
      check_admin_referer('h5p_content', 'yes_sir_will_do'); // Verify form
      $core = $plugin->get_h5p_instance('core'); // Make sure core is loaded

      $result = FALSE;
      if ($action === 'create') {
        // Handle creation of new content.
        $result = $this->handle_content_creation($this->content);
      }
      elseif (isset($_FILES['h5p_file']) && $_FILES['h5p_file']['error'] === 0) {
        // Create new content if none exists
        $content = ($this->content === NULL ? array('disable' => H5PCore::DISABLE_NONE) : $this->content);
        $content['title'] = $this->get_input_title();
        $this->get_disabled_content_features($core, $content);

        // Handle file upload
        $plugin_admin = H5P_Plugin_Admin::get_instance();
        $result = $plugin_admin->handle_upload($content);
      }

      if ($result) {
        $content['id'] = $result;
        $this->delete_export($content);
        wp_safe_redirect(admin_url('admin.php?page=h5p&task=show&id=' . $result));
      }
    }
  }

  /**
   * Display a form for adding and editing h5p content.
   *
   * @since 1.1.0
   */
  public function display_new_content_page() {
    $contentExists = ($this->content !== NULL);

    $plugin = H5P_Plugin::get_instance();
    $core = $plugin->get_h5p_instance('core');

    // Prepare form
    $title = $this->get_input('title', $contentExists ? $this->content['title'] : '');
    $library = $this->get_input('library', $contentExists ? H5PCore::libraryToString($this->content['library']) : 0);
    $parameters = $this->get_input('parameters', $contentExists ? $core->filterParameters($this->content) : '{}');

    // Determine upload or create
    if (!$contentExists && !$this->has_libraries()) {
      $upload = TRUE;
    }
    else {
      $upload = (filter_input(INPUT_POST, 'action') === 'upload');
    }


    // Filter/escape parameters, double escape that is...
    $safe_text = wp_check_invalid_utf8($parameters);
    $safe_text = _wp_specialchars($safe_text, ENT_QUOTES, false, true);
    $parameters = apply_filters('attribute_escape', $safe_text, $parameters);

    include_once('views/new-content.php');
    $this->add_editor_assets($contentExists ? $this->content['id'] : NULL);
    H5P_Plugin_Admin::add_script('disable', 'h5p-php-library/js/disable.js');
  }

  /**
   * Check to see if the installation has any libraries.
   *
   * @since 1.5.2
   * @global \wpdb $wpdb
   * @return bool
   */
  private function has_libraries() {
    global $wpdb;

    return $wpdb->get_var("SELECT id FROM {$wpdb->prefix}h5p_libraries WHERE runnable = 1 LIMIT 1") !== NULL;
  }

  /**
   * Remove h5p export file.
   *
   * @since 1.1.0
   * @param array $content
   */
  private function delete_export($content) {
    $plugin = H5P_Plugin::get_instance();
    $export = $plugin->get_h5p_instance('export');
    if (!isset($content['slug'])) {
      $content['slug'] = '';
    }
    $export->deleteExport($content);
  }

  /**
   * Create new content.
   *
   * @since 1.1.0
   * @param array $content
   * @return mixed
   */
  private function handle_content_creation($content) {
    $plugin = H5P_Plugin::get_instance();
    $core = $plugin->get_h5p_instance('core');

    // Keep track of the old library and params
    $oldLibrary = NULL;
    $oldParams = NULL;
    if ($content !== NULL) {
      $oldLibrary = $content['library'];
      $oldParams = json_decode($content['params']);
    }
    else {
      $content = array(
        'disable' => H5PCore::DISABLE_NONE
      );
    }

    // Get library
    $content['library'] = $core->libraryFromString($this->get_input('library'));
    if (!$content['library']) {
      $core->h5pF->setErrorMessage(__('Invalid library.', $this->plugin_slug));
      return FALSE;
    }

    // Check if library exists.
    $content['library']['libraryId'] = $core->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'], $content['library']['minorVersion']);
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

    // Set disabled features
    $this->get_disabled_content_features($core, $content);

    // Save new content
    $content['id'] = $core->saveContent($content);

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
   * Extract disabled content features from input post.
   *
   * @since 1.2.0
   * @param H5PCore $core
   * @param int $current
   * @return int
   */
  private function get_disabled_content_features($core, &$content) {
    $set = array(
      'frame' => filter_input(INPUT_POST, 'frame', FILTER_VALIDATE_BOOLEAN),
      'download' => filter_input(INPUT_POST, 'download', FILTER_VALIDATE_BOOLEAN),
      'embed' => filter_input(INPUT_POST, 'embed', FILTER_VALIDATE_BOOLEAN),
      'copyright' => filter_input(INPUT_POST, 'copyright', FILTER_VALIDATE_BOOLEAN),
    );
    $content['disable'] = $core->getDisable($set, $content['disable']);
  }

  /**
   * Get input post data field.
   *
   * @since 1.1.0
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
        H5P_Plugin_Admin::set_error(sprintf(__('Missing %s.', $this->plugin_slug), $field));
      }
      return $default;
    }

    return $value;
  }

  /**
   * Get input post data field title. Validates.
   *
   * @since 1.1.0
   * @return string
   */
  public function get_input_title() {
    $title = $this->get_input('title');
    if ($title === NULL) {
      return NULL;
    }

    // Trim title and check length
    $trimmed_title = trim($title);
    if ($trimmed_title === '') {
      H5P_Plugin_Admin::set_error(sprintf(__('Missing %s.', $this->plugin_slug), 'title'));
      return NULL;
    }

    if (strlen($trimmed_title) > 255) {
      H5P_Plugin_Admin::set_error(__('Title is too long. Must be 256 letters or shorter.', $this->plugin_slug));
      return NULL;
    }

    return $trimmed_title;
  }

  /**
   * Add custom media button for selecting H5P content.
   *
   * @since 1.1.0
   * @return string
   */
  public function add_insert_button() {
    $this->insertButton = TRUE;
    return '<a href="#" id="add-h5p" class="button" title="' . __('Insert H5P Content', $this->plugin_slug) . '">' . __('Add H5P', $this->plugin_slug) . '</a>';
  }

  /**
   * Adds scripts and settings for allowing selection of H5P contents when
   * inserting into pages, posts etc.
   *
   * @since 1.2.0
   */
  public function print_insert_content_scripts() {
    if (!$this->insertButton) {
      return;
    }

    $plugin_admin = H5P_Plugin_Admin::get_instance();
    $plugin_admin->print_data_view_settings(
      'h5p-insert-content',
      admin_url('admin-ajax.php?action=h5p_insert_content'),
      array(
        (object) array(
          'text' => __('Title', $this->plugin_slug),
          'sortable' => TRUE
        ),
        (object) array(
          'text' => __('Content type', $this->plugin_slug),
          'sortable' => TRUE
        ),
        (object) array(
          'text' => __('Last modified', $this->plugin_slug),
          'sortable' => TRUE
        ),
        (object) array(
          'class' => 'h5p-insert-link'
        )
      ),
      array(true),
      __("No H5P content available. You must upload or create new content.", $this->plugin_slug),
      (object) array(
        'by' => 2,
        'dir' => 0
      )
    );
  }

  /**
   * List content to choose from when inserting H5Ps.
   *
   * @since 1.2.0
   */
  public function ajax_insert_content() {
    $this->ajax_contents(TRUE);
  }

  /**
   * Generic function for listing all H5P contents.
   *
   * @global \wpdb $wpdb
   * @since 1.2.0
   * @param boolean $insert Place insert buttons instead of edit links.
   */
  public function ajax_contents($insert = FALSE) {
    global $wpdb;

    // Load input vars.
    $admin = H5P_Plugin_Admin::get_instance();
    list($offset, $limit, $sort_by, $sort_dir, $filters) = $admin->get_data_view_input();

    // Add filters to data query
    $conditions = array();
    if (isset($filters[0])) {
      $conditions[] = array('title', $filters[0], 'LIKE');
    }

    // Different fields for insert
    if ($insert) {
      $fields = array('id', 'title', 'content_type', 'updated_at');
    }
    else {
      $fields = array('id', 'title', 'content_type', 'created_at', 'updated_at', 'user_name', 'user_id');
    }

    // Create new content query
    $content_query = new H5PContentQuery($fields, $offset, $limit, $fields[$sort_by + 1], $sort_dir, $conditions);
    $results = $content_query->get_rows();

    // Make data more readable for humans
    $rows = array();
    foreach ($results as $result)  {
      $rows[] = ($insert ? $this->get_contents_insert_row($result) : $this->get_contents_row($result));
    }

    // Print results
    header('Cache-Control: no-cache');
    header('Content-type: application/json');
    print json_encode(array(
      'num' => $content_query->get_total(),
      'rows' => $rows
    ));
    exit;
  }

  /**
   * Get row for insert table with all values escaped and ready for view.
   *
   * @since 1.2.0
   * @param stdClass $result Database result for row
   * @return array
   */
  private function get_contents_insert_row($result) {
    $datetimeformat = get_option('date_format') . ' ' . get_option('time_format');
    $offset = get_option('gmt_offset') * 3600;

    return array(
      esc_html($result->title),
      esc_html($result->content_type),
      date($datetimeformat, strtotime($result->updated_at) + $offset),
      '<button class="button h5p-insert" data-id="' . $result->id . '">' . __('Insert', $this->plugin_slug) . '</button>'
    );
  }

  /**
   * Get row for contents table with all values escaped and ready for view.
   *
   * @since 1.2.0
   * @param stdClass $result Database result for row
   * @return array
   */
  private function get_contents_row($result) {
    $datetimeformat = get_option('date_format') . ' ' . get_option('time_format');
    $offset = get_option('gmt_offset') * 3600;

    $row = array(
      '<a href="' . admin_url('admin.php?page=h5p&task=show&id=' . $result->id) . '">' . esc_html($result->title) . '</a>',
      esc_html($result->content_type),
      date($datetimeformat, strtotime($result->created_at) + $offset),
      date($datetimeformat, strtotime($result->updated_at) + $offset),
      esc_html($result->user_name)
    );

    $content = array('user_id' => $result->user_id);

    // Add user results link
    if (get_option('h5p_track_user', TRUE)) {
      if ($this->current_user_can_view_content_results($content)) {
        $row[] = '<a href="' . admin_url('admin.php?page=h5p&task=results&id=' . $result->id) . '">' . __('Results', $this->plugin_slug) . '</a>';
      }
      else {
        $row[] = '';
      }
    }

    // Add edit link
    if ($this->current_user_can_edit($content)) {
      $row[] = '<a href="' . admin_url('admin.php?page=h5p_new&id=' . $result->id) . '">' . __('Edit', $this->plugin_slug) . '</a>';
    }
    else {
      $row[] = '';
    }

    return $row;
  }

  /**
   * Returns the instance of the h5p editor library.
   *
   * @since 1.1.0
   * @return \H5peditor
   */
  private function get_h5peditor_instance() {
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
        '',
        $plugin->get_h5p_path()
      );
    }

    return self::$h5peditor;
  }

  /**
   * Add assets and JavaScript settings for the editor.
   *
   * @since 1.1.0
   * @param int $id optional content identifier
   */
  public function add_editor_assets($id = NULL) {
    $plugin = H5P_Plugin::get_instance();
    $plugin->add_core_assets();

    // Make sure the h5p classes are loaded
    $plugin->get_h5p_instance('core');
    $this->get_h5peditor_instance();

    // Add JavaScript settings
    $settings = $plugin->get_settings();
    $cache_buster = '?ver=' . H5P_Plugin::VERSION;

    // Use jQuery and styles from core.
    $assets = array(
      'css' => $settings['core']['styles'],
      'js' => $settings['core']['scripts']
    );

    // Use relative URL to support both http and https.
    $upload_dir = plugins_url('h5p/h5p-editor-php-library');
    $url = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $upload_dir) . '/';

    // Add editor styles
    foreach (H5peditor::$styles as $style) {
      $assets['css'][] = $url . $style . $cache_buster;
    }

    // Add editor JavaScript
    foreach (H5peditor::$scripts as $script) {
      // We do not want the creator of the iframe inside the iframe
      if ($script !== 'scripts/h5peditor-editor.js') {
        $assets['js'][] = $url . $script . $cache_buster;
      }
    }

    // Add JavaScript with library framework integration (editor part)
    H5P_Plugin_Admin::add_script('editor-editor', 'h5p-editor-php-library/scripts/h5peditor-editor.js');
    H5P_Plugin_Admin::add_script('editor', 'admin/scripts/h5p-editor.js');

    // Add translation
    $language = $plugin->get_language();
    $language_script = 'h5p-editor-php-library/language/' . $language . '.js';
    if (!file_exists(plugin_dir_path(__FILE__) . '../' . $language_script)) {
      $language_script = 'h5p-editor-php-library/language/en.js';
    }
    H5P_Plugin_Admin::add_script('language', $language_script);

    // Add JavaScript settings
    $content_validator = $plugin->get_h5p_instance('contentvalidator');
    $settings['editor'] = array(
      'filesPath' => $plugin->get_h5p_url() . '/editor',
      'fileIcon' => array(
        'path' => plugins_url('h5p/h5p-editor-php-library/images/binary-file.png'),
        'width' => 50,
        'height' => 50,
      ),
      'ajaxPath' => admin_url('admin-ajax.php?action=h5p_'),
      'libraryUrl' => plugin_dir_url('h5p/h5p-editor-php-library/h5peditor.class.php'),
      'copyrightSemantics' => $content_validator->getCopyrightSemantics(),
      'assets' => $assets,
      'deleteMessage' => __('Are you sure you wish to delete this content?', $this->plugin_slug)
    );

    if ($id !== NULL) {
      $settings['editor']['nodeVersionId'] = $id;
    }

    $plugin->print_settings($settings);
  }

  /**
   * Get library details through AJAX.
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
      $plugin = H5P_Plugin::get_instance();
      print $editor->getLibraryData($name, $major_version, $minor_version, $plugin->get_language(), $plugin->get_h5p_path());
    }
    else {
      print $editor->getLibraries();
    }

    exit;
  }

  /**
   * Handle file uploads through AJAX.
   *
   * @since 1.1.0
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
    header('Content-type: application/json; charset=utf-8');

    print $file->getResult();
    exit;
  }

  /**
   * Provide data for content results view.
   *
   * @since 1.2.0
   */
  public function ajax_content_results() {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    if (!$id) {
      return; // Missing id
    }

    $plugin = H5P_Plugin::get_instance();
    $content = $plugin->get_content($id);
    if (is_string($content) || !$this->current_user_can_edit($content)) {
      return; // Error loading content or no access
    }

    $plugin_admin = H5P_Plugin_Admin::get_instance();
    $plugin_admin->print_results($id);
  }
}
