<?php

/**
 * Handles all communication with the database.
 */
class H5PEditorWordPressStorage implements H5peditorStorage {

  /**
   * Load language file(JSON) from database.
   * This is used to translate the editor fields(title, description etc.)
   *
   * @param string $name The machine readable name of the library(content type)
   * @param int $major Major part of version number
   * @param int $minor Minor part of version number
   * @param string $lang Language code
   * @return string Translation in JSON format
   */
  public function getLanguage($name, $majorVersion, $minorVersion, $language) {
    global $wpdb;

    // Load translation field from DB
    return $wpdb->get_var($wpdb->prepare(
        "SELECT hlt.translation
           FROM {$wpdb->prefix}h5p_libraries_languages hlt
           JOIN {$wpdb->prefix}h5p_libraries hl ON hl.id = hlt.library_id
          WHERE hl.name = %s
            AND hl.major_version = %d
            AND hl.minor_version = %d
            AND hlt.language_code = %s",
        $name, $majorVersion, $minorVersion, $language
    ));
  }

  /**
   * Load a list of available language codes from the database.
   *
   * @param string $machineName The machine readable name of the library(content type)
   * @param int $majorVersion Major part of version number
   * @param int $minorVersion Minor part of version number
   * @return array List of possible language codes
   */
  public function getAvailableLanguages($machineName, $majorVersion, $minorVersion) {
    global $wpdb;

    $results = $wpdb->get_results($wpdb->prepare(
      "SELECT hll.language_code
         FROM {$wpdb->prefix}h5p_libraries_languages hll
         JOIN {$wpdb->prefix}h5p_libraries hl
           ON hll.library_id = hl.id
        WHERE hl.name = %s
          AND hl.major_version = %d
          AND hl.minor_version = %d",
      $machineName, $majorVersion, $minorVersion
    ));

    $codes = array('en'); // Semantics is 'en' by default.
    foreach ($results as $result) {
      $codes[] = $result->language_code;
    }

    return $codes;
  }

  /**
   * "Callback" for mark the given file as a permanent file.
   * Used when saving content that has new uploaded files.
   *
   * @param int $fileid
   */
  public function keepFile($fileId) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'h5p_tmpfiles', array('path' => $fileId), array('%s'));
  }

  /**
   * Decides which content types the editor should have.
   *
   * Two usecases:
   * 1. No input, will list all the available content types.
   * 2. Libraries supported are specified, load additional data and verify
   * that the content types are available. Used by e.g. the Presentation Tool
   * Editor that already knows which content types are supported in its
   * slides.
   *
   * @param array $libraries List of library names + version to load info for
   * @return array List of all libraries loaded
   */
  public function getLibraries($libraries = NULL) {
    global $wpdb;

    $super_user = current_user_can('manage_h5p_libraries');

    if ($libraries !== NULL) {
      // Get details for the specified libraries only.
      $librariesWithDetails = array();
      foreach ($libraries as $library) {
        // Look for library
        $details = $wpdb->get_row($wpdb->prepare(
            "SELECT title, runnable, restricted, tutorial_url, metadata_settings
              FROM {$wpdb->prefix}h5p_libraries
              WHERE name = %s
              AND major_version = %d
              AND minor_version = %d
              AND semantics IS NOT NULL",
            $library->name, $library->majorVersion, $library->minorVersion
          ));
        if ($details) {
          // Library found, add details to list
          $library->tutorialUrl = $details->tutorial_url;
          $library->title = $details->title;
          $library->runnable = $details->runnable;
          $library->restricted = $super_user ? FALSE : ($details->restricted === '1' ? TRUE : FALSE);
          $library->metadataSettings = json_decode($details->metadata_settings);
          $librariesWithDetails[] = $library;
        }
      }

      // Done, return list with library details
      return $librariesWithDetails;
    }

    // Load all libraries
    $libraries = array();
    $libraries_result = $wpdb->get_results(
        "SELECT name,
                title,
                major_version AS majorVersion,
                minor_version AS minorVersion,
                tutorial_url AS tutorialUrl,
                restricted,
                metadata_settings AS metadataSettings
          FROM {$wpdb->prefix}h5p_libraries
          WHERE runnable = 1
          AND semantics IS NOT NULL
          ORDER BY title"
      );
    foreach ($libraries_result as $library) {
      // Make sure we only display the newest version of a library.
      foreach ($libraries as $key => $existingLibrary) {
        if ($library->name === $existingLibrary->name) {

          // Found library with same name, check versions
          if ( ( $library->majorVersion === $existingLibrary->majorVersion &&
                 $library->minorVersion > $existingLibrary->minorVersion ) ||
               ( $library->majorVersion > $existingLibrary->majorVersion ) ) {
            // This is a newer version
            $existingLibrary->isOld = TRUE;
          }
          else {
            // This is an older version
            $library->isOld = TRUE;
          }
        }
      }

      // Convert from string to object
      $library->metadataSettings = json_decode($library->metadataSettings);

      // Check to see if content type should be restricted
      $library->restricted = $super_user ? FALSE : ($library->restricted === '1' ? TRUE : FALSE);

      // Add new library
      $libraries[] = $library;
    }
    return $libraries;
  }

  /**
   * Allow for other plugins to decide which styles and scripts are attached.
   * This is useful for adding and/or modifing the functionality and look of
   * the content types.
   *
   * @param array $files
   *  List of files as objects with path and version as properties
   * @param array $libraries
   *  List of libraries indexed by machineName with objects as values. The objects
   *  have majorVersion and minorVersion as properties.
   */
  public function alterLibraryFiles(&$files, $libraries) {
    $plugin = H5P_Plugin::get_instance();
    $plugin->alter_assets($files, $libraries, 'editor');
  }

  /**
   * Saves a file or moves it temporarily. This is often necessary in order to
   * validate and store uploaded or fetched H5Ps.
   *
   * @param string $data Uri of data or actual data that should be saved as a temporary file
   * @param boolean $move_file Can be set to TRUE to move the data instead of saving it
   *
   * @return bool|object Returns false if saving failed or an object with the dir
   * and the fileName of the saved file
   */
  public static function saveFileTemporarily($data, $move_file) {
    // Get temporary path
    $plugin = H5P_Plugin::get_instance();
    $interface = $plugin->get_h5p_instance('interface');

    $path = $interface->getUploadedH5pPath();

    if ($move_file) {
      // Move so core can validate the file extension.
      rename($data, $path);
    }
    else {
      // Create file from data
      file_put_contents($path, $data);
    }

    return (object) array (
      'dir' => dirname($path),
      'fileName' => basename($path)
    );
  }

  /**
   * Marks a file for later cleanup, useful when files are not instantly cleaned
   * up. E.g. for files that are uploaded through the editor.
   *
   * @param H5peditorFile
   * @param $content_id
   */
  public static function markFileForCleanup($file, $content_id = null) {
    global $wpdb;

    $plugin = H5P_Plugin::get_instance();
    $path   = $plugin->get_h5p_path();

    if (empty($content_id)) {
      // Should be in editor tmp folder
      $path .= '/editor';
    }
    else {
      // Should be in content folder
      $path .= '/content/' . $content_id;
    }

    // Add file type to path
    $path .= '/' . $file->getType() . 's';

    // Add filename to path
    $path .= '/' . $file->getName();

    // Keep track of temporary files so they can be cleaned up later.
    $wpdb->insert($wpdb->prefix . 'h5p_tmpfiles',
      array('path' => $path, 'created_at' => time()),
      array('%s', '%d'));

    // Clear cached value for dirsize.
    delete_transient('dirsize_cache');
  }

  /**
   * Clean up temporary files
   *
   * @param string $filePath Path to file or directory
   */
  public static function removeTemporarilySavedFiles($filePath) {
    if (is_dir($filePath)) {
      H5PCore::deleteFileTree($filePath);
    }
    else {
      unlink($filePath);
    }
  }
}
