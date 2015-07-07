<?php

/**
 * Handles all communication with the database.
 */
class H5PEditorWordPressStorage implements H5peditorStorage {

  /**
   * Empty contructor.
   */
  function __construct() { }

  public function getLanguage($name, $majorVersion, $minorVersion, $language) {
    global $wpdb;

    return $wpdb->get_var($wpdb->prepare(
        "SELECT hlt.translation
          FROM {$wpdb->prefix}h5p_libraries_languages hlt
          JOIN {$wpdb->prefix}h5p_libraries hl ON hl.id = hlt.library_id
          WHERE hl.name = %s
          AND hl.major_version = %d
          AND hl.minor_version = %d
          AND hlt.language_code = %s",
        $name, $majorVersion, $minorVersion, $language)
      );
  }

  public function addTmpFile($file) {
    // TODO: Keep track of tmp files.
  }

  public function keepFile($oldPath, $newPath) {
    // TODO: No longer a tmp file.
  }

  public function removeFile($path) {
    // TODO: Removed from file tracking.
  }

  public function getLibraries($libraries = NULL) {
    global $wpdb;
    $super_user = current_user_can('manage_h5p_libraries');

    if ($libraries !== NULL) {
      // Get details for the specified libraries only.
      $librariesWithDetails = array();
      foreach ($libraries as $library) {
        $details = $wpdb->get_row($wpdb->prepare(
            "SELECT title, runnable, restricted, tutorial_url
              FROM {$wpdb->prefix}h5p_libraries
              WHERE name = %s
              AND major_version = %d
              AND minor_version = %d
              AND semantics IS NOT NULL",
            $library->name, $library->majorVersion, $library->minorVersion
          ));
        if ($details) {
          $library->tutorialUrl = $details->tutorial_url;
          $library->title = $details->title;
          $library->runnable = $details->runnable;
          $library->restricted = $super_user ? FALSE : ($details->restricted === '1' ? TRUE : FALSE);
          $librariesWithDetails[] = $library;
        }
      }

      return $librariesWithDetails;
    }

    $libraries = array();

    $libraries_result = $wpdb->get_results(
        "SELECT name,
                title,
                major_version AS majorVersion,
                minor_version AS minorVersion,
                tutorial_url AS tutorialUrl,
                restricted
          FROM {$wpdb->prefix}h5p_libraries
          WHERE runnable = 1
          AND semantics IS NOT NULL
          ORDER BY title"
      );
    foreach ($libraries_result as $library) {
      // Make sure we only display the newest version of a library.
      foreach ($libraries as $key => $existingLibrary) {
        if ($library->name === $existingLibrary->name) {
          // Mark old ones
          // This is the newest
          if (($library->majorVersion === $existingLibrary->majorVersion && $library->minorVersion > $existingLibrary->minorVersion) ||
              ($library->majorVersion > $existingLibrary->majorVersion)) {
            $existingLibrary->isOld = TRUE;
          }
          else {
            $library->isOld = TRUE;
          }
        }
      }

      $library->restricted = $super_user ? FALSE : ($library->restricted === '1' ? TRUE : FALSE);

      // Add new library
      $libraries[] = $library;
    }
    return $libraries;
  }

  /**
   * Implements alterLibrarySemantics
   *
   * Gives you a chance to alter all the library files.
   */
  public function alterLibraryFiles(&$files, $libraries) {
    $plugin = H5P_Plugin::get_instance();
    $plugin->alter_assets($files, $libraries, 'editor');
  }
}
