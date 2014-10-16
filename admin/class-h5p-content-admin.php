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
    if (get_option('h5p_track_user', TRUE) !== '1') {
      return FALSE;
    }

    return current_user_can_edit($content);
  }

  /**
   * Display a list of all h5p content.
   *
   * @since 1.1.0
   */
  public function display_contents_page() {
    switch (filter_input(INPUT_GET, 'task', FILTER_SANITIZE_STRING)) {
      case NULL:
        $contents = $this->get_contents();
        $datetimeformat = get_option('date_format') . ' ' . get_option('time_format');
        $offset = get_option('gmt_offset') * 3600;
        $user_tracking = (get_option('h5p_track_user', TRUE) === '1');
        include_once('views/all-content.php');
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
          include_once('views/results.php');

          // Add JS settings
          $settings = array(
            'contentResults' => array(
              'source' => admin_url('admin-ajax.php?action=h5p_content_results&id=' . $this->content['id']),
              'headers' => array(
                __('User', $this->plugin_slug),
                __('Score', $this->plugin_slug),
                __('Maximum Score', $this->plugin_slug),
                __('Opened', $this->plugin_slug),
                __('Finished', $this->plugin_slug),
                __('Time spent', $this->plugin_slug)
              ),
              'l10n' => array(
                'loading' => __('Loading data.', $this->plugin_slug),
                'ajaxFailed' => __('Failed to load data.', $this->plugin_slug),
                'noData' => __("There's no data available that matches your criteria.", $this->plugin_slug),
                'currentPage' => __('Page $current of $total', $this->plugin_slug),
                'nextPage' => __('Next page', $this->plugin_slug),
                'previousPage' =>__('Previous page', $this->plugin_slug),
              )
            )
          );
          $plugin = H5P_Plugin::get_instance();
          $plugin->print_settings($settings);

          // Add JS
          H5P_Plugin_Admin::add_script('jquery', 'h5p-php-library/js/jquery.js');
          H5P_Plugin_Admin::add_script('utils', 'h5p-php-library/js/h5p-utils.js');
          H5P_Plugin_Admin::add_script('data-view', 'h5p-php-library/js/h5p-data-view.js');
          H5P_Plugin_Admin::add_script('content-results', 'admin/scripts/h5p-content-results.js');
          H5P_Plugin_Admin::add_style('admin', 'h5p-php-library/styles/h5p-admin.css');
        }
        return;
    }

    print '<div class="wrap"><h2>' . esc_html__('Unknown task.', $this->plugin_slug) . '</h2></div>';
  }


  /**
   * Get list of H5P contents.
   *
   * @since 1.1.0
   * @global \wpdb $wpdb
   * @return array
   */
  private function get_contents() {
    global $wpdb;
    return $wpdb->get_results(
        "SELECT id, title, created_at, updated_at, user_id
          FROM {$wpdb->prefix}h5p_contents
          ORDER BY title, id"
      );
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
          $this->delete_export($this->content['id']);
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

      $result = FALSE;
      if ($action === 'create') {
        // Handle creation of new content.
        $result = $this->handle_content_creation($this->content);
      }
      elseif (isset($_FILES['h5p_file']) && $_FILES['h5p_file']['error'] === 0) {
        // Create new content if none exists
        $content = ($this->content === NULL ? array() : $this->content);
        $content['title'] = $this->get_input_title();

        // Handle file upload
        $plugin_admin = H5P_Plugin_Admin::get_instance();
        $result = $plugin_admin->handle_upload($content);
      }

      if ($result) {
        $this->delete_export($result);
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
    $upload = (filter_input(INPUT_POST, 'action') === 'upload');

    // Filter/escape parameters, double escape that is...
    $safe_text = wp_check_invalid_utf8($parameters);
    $safe_text = _wp_specialchars($safe_text, ENT_QUOTES, false, true);
    $parameters = apply_filters('attribute_escape', $safe_text, $parameters);

    include_once('views/new-content.php');
    $this->add_editor_assets($contentExists ? $this->content['id'] : NULL);
  }

  /**
   * Remove h5p export file.
   *
   * @since 1.1.0
   * @param int $content_id
   */
  private function delete_export($content_id) {
    $plugin = H5P_Plugin::get_instance();
    $export = $plugin->get_h5p_instance('export');
    $export->deleteExport($content_id);
  }

  /**
   * Create new content.
   *
   * @since 1.1.0
   * @param array $content
   * @return mixed
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
    $ajax_url = admin_url('admin-ajax.php?action=h5p_contents');
    return '<a href="' . $ajax_url . '" class="button thickbox" title="' . __('Select and insert H5P Interactive Content', $this->plugin_slug) . '">' . __('Add H5P', $this->plugin_slug) . '</a>';
  }

  /**
   * List to select H5P content from.
   *
   * @since 1.1.0
   */
  public function ajax_select_content() {
    $contents = $this->get_contents();
    include_once('views/select-content.php');
    exit;
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

    // Remove integration from the equation.
    for ($i = 0, $s = count($assets['js']); $i < $s; $i++) {
      if (preg_match('/\/h5pintegration\.js/', $assets['js'][$i])) {
        array_splice($assets['js'], $i, 1);
        break;
      }
    }

    // Add editor styles
    foreach (H5peditor::$styles as $style) {
      $assets['css'][] = plugins_url('h5p/h5p-editor-php-library/' . $style . $cache_buster);
    }

    // Add editor JavaScript
    foreach (H5peditor::$scripts as $script) {
      // We do not want the creator of the iframe inside the iframe
      if ($script !== 'scripts/h5peditor-editor.js') {
        $assets['js'][] = plugins_url('h5p/h5p-editor-php-library/' . $script . $cache_buster);
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
    $settings['editor'] = array(
      'filesPath' => $plugin->get_h5p_url() . '/editor',
      'fileIcon' => array(
        'path' => plugins_url('h5p/h5p-editor-php-library/images/binary-file.png'),
        'width' => 50,
        'height' => 50,
      ),
      'ajaxPath' => admin_url('admin-ajax.php?action=h5p_'),
      'libraryUrl' => plugin_dir_url('h5p/h5p-editor-php-library/h5peditor.class.php'),
      'copyrightSemantics' => H5PContentValidator::getCopyrightSemantics(),
      'assets' => $assets
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
      print $editor->getLibraryData($name, $major_version, $minor_version);
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
    header('Content-type: application/json');

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

    // Load offset and limit.
    $offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
    if (!$offset) {
      $offset = 0; // Not set, use default
    }
    $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
    if (!$limit) {
      $limit = 20; // Not set, use default
    }

    $plugin_admin = H5P_Plugin_Admin::get_instance();
    $results = $plugin_admin->get_results($id, NULL, $offset, $limit);

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
        $result->user_name,
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
      'num' => $plugin_admin->get_results_num($id),
      'rows' => $rows
    ));
    exit;
  }
}
