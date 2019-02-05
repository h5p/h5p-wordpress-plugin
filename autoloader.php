<?php

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
      'H5PFileStorage' => 'h5p-php-library/h5p-file-storage.interface.php',
      'H5PDefaultStorage' => 'h5p-php-library/h5p-default-storage.class.php',
      'H5PEventBase' => 'h5p-php-library/h5p-event-base.class.php',
      'H5PMetadata' => 'h5p-php-library/h5p-metadata.class.php',

      // Editor
      'H5peditor' => 'h5p-editor-php-library/h5peditor.class.php',
      'H5peditorFile' => 'h5p-editor-php-library/h5peditor-file.class.php',
      'H5peditorStorage' => 'h5p-editor-php-library/h5peditor-storage.interface.php',
      'H5PEditorAjaxInterface' => 'h5p-editor-php-library/h5peditor-ajax.interface.php',
      'H5PEditorAjax' => 'h5p-editor-php-library/h5peditor-ajax.class.php',

      // Public
      'H5P_Event' => 'public/class-h5p-event.php',
      'H5P_Plugin' => 'public/class-h5p-plugin.php',
      'H5PWordPress' => 'public/class-h5p-wordpress.php',

      // Admin
      'H5P_Plugin_Admin' => 'admin/class-h5p-plugin-admin.php',
      'H5PContentAdmin' => 'admin/class-h5p-content-admin.php',
      'H5PContentQuery' => 'admin/class-h5p-content-query.php',
      'H5PLibraryAdmin' => 'admin/class-h5p-library-admin.php',
      'H5PEditorWordPressStorage' => 'admin/class-h5p-editor-wordpress-storage.php',
      'H5PEditorWordPressAjax' => 'admin/class-h5p-editor-wordpress-ajax.php',
      'H5PPrivacyPolicy' => 'admin/class-h5p-privacy-policy.php'
    );
  }

  if (isset($classmap[$class])) {
    require_once plugin_dir_path(__FILE__) . $classmap[$class];
  }
}
spl_autoload_register('h5p_autoloader');
