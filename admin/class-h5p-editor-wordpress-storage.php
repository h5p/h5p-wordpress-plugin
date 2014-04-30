<?php

/**
 * Handles all communication with the database.
 */
class H5PEditorWordPressStorage implements H5peditorStorage {

  /**
   * Empty contructor.
   */
  function __construct() { }

  public function getLanguage($name, $majorVersion, $minorVersion) {
    global $wpdb;
    $plugin = H5P_Plugin::get_instance();
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT language_json
          FROM {$wpdb->prefix}h5p_libraries_languages hlt
          JOIN {$wpdb->prefix}h5p_libraries hl ON hl.id = hlt.library_id
          WHERE hl.name = %s
          AND hl.major_version = %d
          AND hl.minor_version = %d
          AND hlt.language_code = %s",
        $name, $majorVersion, $minorVersion, $plugin->get_language())
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
    
    if ($libraries !== NULL) {
      // Get details for the specified libraries only.
      foreach ($libraries as $library) {
        $details = $wpdb->get_row($wpdb->prepare(
            "SELECT title, runnable 
              FROM {$wpdb->prefix}h5p_libraries
              WHERE name = %s
              AND major_version = %d
              AND minor_version = %d
              AND semantics IS NOT NULL",
            $library->name, $library->majorVersion, $library->minorVersion
          ));
        if ($details) {
          $library->title = $details->title;
          $library->runnable = $details->runnable;
        }
      }
      
      return $libraries;
    }
    
    $libraries = array();

    $libraries_result = $wpdb->get_results(
        "SELECT machine_name AS machineName, title, major_version as majorVersion, minor_version as minorVersion 
          FROM {$wpdb->prefix}h5p_libraries
          WHERE runnable = 1 
          AND semantics IS NOT NULL 
          ORDER BY title"  
      );
    foreach ($libraries_result as $library) {
      // Make sure we only display the newest version of a library.
      foreach ($libraries as $key => $existingLibrary) {
        if ($library->machineName === $existingLibrary->machineName) {
          
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
      
      // Add new library
      $libraries[] = $library;
    }
    
    return $libraries;
  }
}