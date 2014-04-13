<?php

/**
 * Plugin Name: H5P
 * Plugin URI: http://h5p.org/wordpress
 * Description: Create interactive and rich content. TODO
 * Version: 1.0
 * Author: Joubel
 * Author URI: http://joubel.com
 * License: MIT
 */

/**
 * Media button (ThickBox)
 */
function h5p_add_insert_button($context) {
  $ajax_url = add_query_arg( 
    array( 
        'action' => 'h5p_contents',
    ), 
    'admin-ajax.php'
  ); 
  return '<a href="' . $ajax_url . '" class="button thickbox" title="Select and insert H5P Interactive Content">Add H5P</a>';
}
add_action('media_buttons_context', 'h5p_add_insert_button');

function h5p_select_content() {
  print 'Select the H5P Content you wish to insert.';
  exit;
}
add_action('wp_ajax_h5p_contents', 'h5p_select_content');

// TODO: add_shortcode('h5p', 'h5p_shortcode');
// TODO: add_menu_page

/**
 * H5P Admin
 */
function h5p_admin_menu() {
  add_menu_page('H5P Content', 'H5P Content', 'manage_options', 'h5p_content', 'h5p_content', 'none');
  add_submenu_page('h5p_content', 'All H5P Content', 'All H5P Content', 'manage_options', 'h5p_content', 'h5p_content');
  add_submenu_page('h5p_content', 'Add New', 'Add New', 'manage_options', 'h5p_content_new', 'h5p_content_new');
}
add_action('admin_menu', 'h5p_admin_menu');

function h5p_admin_styles() {
  wp_enqueue_style('h5p', plugins_url('library/styles/h5p.css', __FILE__));
  wp_enqueue_style('h5p-admin', plugins_url('styles/admin.css', __FILE__));
}
add_action('admin_head', 'h5p_admin_styles');

function h5p_content() {
  print 'This is a list!';
}
function h5p_content_new() {
  print 'This is a form!';
}

/**
 * Install
 */
global $h5p_db_version;
$h5p_db_version = "1.0";

function h5p_install() {
  global $h5p_db_version;
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
  
  add_option('h5p_db_version', $h5p_db_version);
}
register_activation_hook(__FILE__, 'h5p_install');

/**
 * Uninstall
 */
function h5p_uninstall() {
  global $wpdb;

	$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_contents");
	$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_contents_libraries");
	$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries");
	$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries_libraries");
	$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries_languages");
	
	delete_option('h5p_db_version');
}
register_uninstall_hook(__FILE__, 'h5p_uninstall');
