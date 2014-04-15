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

$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_contents");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_contents_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries_libraries");
$wpdb->query("DROP TABLE {$wpdb->prefix}h5p_libraries_languages");
	
delete_option('h5p_db_version');

// TODO: Remove files?
