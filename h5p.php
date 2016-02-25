<?php
/**
 * H5P Plugin.
 *
 * Eases the creation and insertion of rich interactive content
 * into you blog. Find content libraries at http://h5p.org
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2014 Joubel
 *
 * @wordpress-plugin
 * Plugin Name:       H5P
 * Plugin URI:        http://h5p.org/wordpress
 * Description:       Allows you to upload, create, share and use rich interactive content on your WordPress site.
 * Version:           1.5.7
 * Author:            Joubel
 * Author URI:        http://joubel.com
 * Text Domain:       h5p
 * License:           MIT
 * License URI:       http://opensource.org/licenses/MIT
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/h5p/h5p-wordpress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

/**
 * Makes it easy to load classes when you need them
 *
 * @param string $class name
 */
function h5p_autoloader($class) {
  static $classmap;
  if (!isset($classmap)) {
    $classmap = array(
      // Core
      'H5PCore' => 'h5p-php-library/h5p.classes.php',
      'H5PFrameworkInterface' => 'h5p-php-library/h5p.classes.php',
      'H5PContentValidator' => 'h5p-php-library/h5p.classes.php',
      'H5PValidator' => 'h5p-php-library/h5p.classes.php',
      'H5PStorage' => 'h5p-php-library/h5p.classes.php',
      'H5PExport' => 'h5p-php-library/h5p.classes.php',
      'H5PDevelopment' => 'h5p-php-library/h5p-development.class.php',

      // Editor
      'H5peditor' => 'h5p-editor-php-library/h5peditor.class.php',
      'H5peditorFile' => 'h5p-editor-php-library/h5peditor-file.class.php',
      'H5peditorStorage' => 'h5p-editor-php-library/h5peditor-storage.interface.php',

      // Public
      'H5P_Plugin' => 'public/class-h5p-plugin.php',
      'H5PWordPress' => 'public/class-h5p-wordpress.php',

      // Admin
      'H5P_Plugin_Admin' => 'admin/class-h5p-plugin-admin.php',
      'H5PContentAdmin' => 'admin/class-h5p-content-admin.php',
      'H5PContentQuery' => 'admin/class-h5p-content-query.php',
      'H5PLibraryAdmin' => 'admin/class-h5p-library-admin.php',
      'H5PEditorWordPressStorage' => 'admin/class-h5p-editor-wordpress-storage.php',
    );
  }

  if (isset($classmap[$class])) {
    require_once plugin_dir_path(__FILE__) . $classmap[$class];
  }
}
spl_autoload_register('h5p_autoloader');

// Public-Facing Functionality
register_activation_hook(__FILE__, array('H5P_Plugin', 'activate'));
register_deactivation_hook( __FILE__, array('H5P_Plugin', 'deactivate'));
add_action('plugins_loaded', array('H5P_Plugin', 'get_instance'));

// Dashboard and Administrative Functionality
if (is_admin()) {
  add_action('plugins_loaded', array('H5P_Plugin_Admin', 'get_instance'));
}
