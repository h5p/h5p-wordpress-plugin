<?php
/**
 * Fired when the h5p plugin is uninstalled.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2014 Joubel
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

global $wpdb;

// Drop tables
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_contents");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_contents_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries_languages");

// Remove settings
delete_option('h5p_db_version');
delete_option('h5p_export');
delete_option('h5p_icon');

/**
 * Recursively remove file or directory.
 *
 * @since 1.0.0
 * @param string $file
 */
function _h5p_recursive_unlink($file) {
  if (is_dir($file)) {
    // Remove all files in dir.
    $subfiles = array_diff(scandir($file), array('.','..'));
    foreach ($subfiles as $subfile)  {
      _h5p_recursive_unlink($file . '/' . $subfile);
    }
    rmdir($file);
  }
  elseif (file_exists($file)) {
    unlink($file); // TODO: Remove from file_managed!!
  }
}

// Clean out file dirs.
$upload_dir = wp_upload_dir();
$path = $upload_dir['basedir'] . '/h5p';
   
// Remove these regardless of their content.
foreach (array('tmp', 'temp', 'libraries', 'content', 'exports', 'editor') as $directory) {
  _h5p_recursive_unlink($path . '/' . $directory);
}
  
// Only remove development dir if it's empty.
$dir = $path . '/development';
if (is_dir($dir) && count(scandir($dir)) === 2) {
  rmdir($dir);

  // Remove parent if empty.
  if (count(scandir($path)) === 2) {
    rmdir($path);
  }
}