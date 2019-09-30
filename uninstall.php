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

require_once plugin_dir_path(__FILE__) . 'autoloader.php';
$plugin = H5P_Plugin::get_instance();
$plugin->get_library_updates();

global $wp_roles;
if (!isset($wp_roles)) {
  $wp_roles = new WP_Roles();
}

// Remove capabilities
$all_roles = $wp_roles->roles;
foreach ($all_roles as $role_name => $role_info) {
  $role = get_role($role_name);

  if (isset($role_info['capabilities']['manage_h5p_libraries'])) {
    $role->remove_cap('manage_h5p_libraries');
  }
  if (isset($role_info['capabilities']['edit_others_h5p_contents'])) {
    $role->remove_cap('edit_others_h5p_contents');
  }
  if (isset($role_info['capabilities']['edit_h5p_contents'])) {
    $role->remove_cap('edit_h5p_contents');
  }
  if (isset($role_info['capabilities']['view_h5p_contents'])) {
    $role->remove_cap('view_h5p_contents');
  }
  if (isset($role_info['capabilities']['view_others_h5p_contents'])) {
    $role->remove_cap('view_others_h5p_contents');
  }
  if (isset($role_info['capabilities']['view_h5p_results'])) {
    $role->remove_cap('view_h5p_results');
  }
}

global $wpdb;
$wpdb->query("DROP TABLE {$wpdb->base_prefix}h5p_libraries_hub_cache");

if (!is_multisite()) {
  // Simple uninstall for single site
  H5P_Plugin::uninstall();
}
else {
  // Run uninstall on each site in the network.
  $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
  $original_blog_id = get_current_blog_id();

  foreach ($blog_ids as $blog_id) {
    switch_to_blog($blog_id);
    H5P_Plugin::uninstall();
  }

  switch_to_blog($original_blog_id);
}
