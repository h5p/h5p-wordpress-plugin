<?php

class H5PWordPress implements H5PFrameworkInterface {

  /**
   * Kesps track of messages for the user.
   *
   * @since 1.0.0
   * @var array
   */
  private $messages = array('error' => array(), 'info' => array());

  /**
   * Implements setErrorMessage
   */
  public function setErrorMessage($message, $code = NULL) {
    if (current_user_can('edit_h5p_contents')) {
      $this->messages['error'][] = (object)array(
        'code' => $code,
        'message' => $message
      );
    }
  }

  /**
   * Implements setInfoMessage
   */
  public function setInfoMessage($message) {
    if (current_user_can('edit_h5p_contents')) {
      $this->messages['info'][] = $message;
    }
  }

  /**
   * Return the selected messages.
   *
   * @since 1.0.0
   * @param string $type
   * @return array
   */
  public function getMessages($type) {
    if (empty($this->messages[$type])) {
      return NULL;
    }
    $messages = $this->messages[$type];
    $this->messages[$type] = array();
    return $messages;
  }

  /**
   * Implements t
   */
  public function t($message, $replacements = array()) {
    // Insert !var as is, escape @var and emphasis %var.
    foreach ($replacements as $key => $replacement) {
      if ($key[0] === '@') {
        $replacements[$key] = esc_html($replacement);
      }
      elseif ($key[0] === '%') {
        $replacements[$key] = '<em>' . esc_html($replacement) . '</em>';
      }
    }
    $message = preg_replace('/(!|@|%)[a-z0-9-]+/i', '%s', $message);

    $plugin = H5P_Plugin::get_instance();
    $this->plugin_slug = $plugin->get_plugin_slug();

    // Assumes that replacement vars are in the correct order.
    return vsprintf(__($message, $this->plugin_slug), $replacements);
  }

  /**
   * Helper
   */
  private function getH5pPath() {
    $plugin = H5P_Plugin::get_instance();
    return $plugin->get_h5p_path();
  }

  /**
   * Get the URL to a library file
   */
  public function getLibraryFileUrl($libraryFolderName, $fileName) {
    $upload_dir = wp_upload_dir();
    return $upload_dir['baseurl'] . '/h5p/libraries/' . $libraryFolderName . '/' . $fileName;
  }

  /**
   * Implements getUploadedH5PFolderPath
   */
  public function getUploadedH5pFolderPath() {
    static $dir;

    if (is_null($dir)) {
      $plugin = H5P_Plugin::get_instance();
      $core = $plugin->get_h5p_instance('core');
      $dir = $core->fs->getTmpPath();
    }

    return $dir;
  }

  /**
   * Implements getUploadedH5PPath
   */
  public function getUploadedH5pPath() {
    static $path;

    if (is_null($path)) {
      $plugin = H5P_Plugin::get_instance();
      $core = $plugin->get_h5p_instance('core');
      $path = $core->fs->getTmpPath() . '.h5p';
    }

    return $path;
  }

  /**
   * Implements getLibraryId
   */
  public function getLibraryId($name, $majorVersion = NULL, $minorVersion = NULL) {
    global $wpdb;

    // Look for specific library
    $sql_where = 'WHERE name = %s';
    $sql_args = array($name);

    if ($majorVersion !== NULL) {
      // Look for major version
      $sql_where .= ' AND major_version = %d';
      $sql_args[] = $majorVersion;
      if ($minorVersion !== NULL) {
        // Look for minor version
        $sql_where .= ' AND minor_version = %d';
        $sql_args[] = $minorVersion;
      }
    }

    // Get the lastest version which matches the input parameters
    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT id
        FROM {$wpdb->prefix}h5p_libraries
        {$sql_where}
        ORDER BY major_version DESC,
                 minor_version DESC,
                 patch_version DESC
        LIMIT 1",
        $sql_args)
    );

    return $id === NULL ? FALSE : $id;
  }

  /**
   * Implements isPatchedLibrary
   */
  public function isPatchedLibrary($library) {
    global $wpdb;

    if (defined('H5P_DEV') && H5P_DEV) {
      // Makes sure libraries are updated, patch version does not matter.
      return TRUE;
    }

    $operator = $this->isInDevMode() ? '<=' : '<';
    return $wpdb->get_var($wpdb->prepare(
        "SELECT id
          FROM {$wpdb->prefix}h5p_libraries
          WHERE name = '%s'
          AND major_version = %d
          AND minor_version = %d
          AND patch_version {$operator} %d",
        $library['machineName'],
        $library['majorVersion'],
        $library['minorVersion'],
        $library['patchVersion']
    )) !== NULL;
  }

  /**
   * Implements isInDevMode
   */
  public function isInDevMode() {
    return false;
  }

  /**
   * Implements mayUpdateLibraries
   */
  public function mayUpdateLibraries() {
    return current_user_can('manage_h5p_libraries');
  }

  /**
   * Implements getLibraryUsage
   */
  public function getLibraryUsage($id, $skipContent = FALSE) {
    global $wpdb;

    return array(
      'content' => $skipContent ? -1 : intval($wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(distinct c.id)
          FROM {$wpdb->prefix}h5p_libraries l
          JOIN {$wpdb->prefix}h5p_contents_libraries cl ON l.id = cl.library_id
          JOIN {$wpdb->prefix}h5p_contents c ON cl.content_id = c.id
          WHERE l.id = %d",
          $id)
        )),
      'libraries' => intval($wpdb->get_var($wpdb->prepare(
          "SELECT COUNT(*)
          FROM {$wpdb->prefix}h5p_libraries_libraries
          WHERE required_library_id = %d",
          $id)
        ))
    );
  }

  /**
   * Implements saveLibraryData
   */
  public function saveLibraryData(&$library, $new = TRUE) {
    global $wpdb;

    $preloadedJs = $this->pathsToCsv($library, 'preloadedJs');
    $preloadedCss =  $this->pathsToCsv($library, 'preloadedCss');
    $dropLibraryCss = '';

    if (isset($library['dropLibraryCss'])) {
      $libs = array();
      foreach ($library['dropLibraryCss'] as $lib) {
        $libs[] = $lib['machineName'];
      }
      $dropLibraryCss = implode(', ', $libs);
    }

    $embedTypes = '';
    if (isset($library['embedTypes'])) {
      $embedTypes = implode(', ', $library['embedTypes']);
    }
    if (!isset($library['semantics'])) {
      $library['semantics'] = '';
    }
    if (!isset($library['fullscreen'])) {
      $library['fullscreen'] = 0;
    }
    if (!isset($library['hasIcon'])) {
      $library['hasIcon'] = 0;
    }

    if ($new) {
      $wpdb->insert(
          $wpdb->prefix . 'h5p_libraries',
          array(
            'name' => $library['machineName'],
            'title' => $library['title'],
            'major_version' => $library['majorVersion'],
            'minor_version' => $library['minorVersion'],
            'patch_version' => $library['patchVersion'],
            'runnable' => $library['runnable'],
            'fullscreen' => $library['fullscreen'],
            'embed_types' => $embedTypes,
            'preloaded_js' => $preloadedJs,
            'preloaded_css' => $preloadedCss,
            'drop_library_css' => $dropLibraryCss,
            'tutorial_url' => '', // NOT NULL, has to be there
            'semantics' => $library['semantics'],
            'has_icon' => $library['hasIcon'] ? 1 : 0,
            'metadata_settings'=> $library['metadataSettings'],
            'add_to' => isset($library['addTo']) ? json_encode($library['addTo']) : NULL
            // Missing? created_at, updated_at
          ),
          array(
            '%s',
            '%s',
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
          )
        );
      $library['libraryId'] = $wpdb->insert_id;
    }
    else {
      $wpdb->update(
          $wpdb->prefix . 'h5p_libraries',
          array(
            'title' => $library['title'],
            'patch_version' => $library['patchVersion'],
            'runnable' => $library['runnable'],
            'fullscreen' => $library['fullscreen'],
            'embed_types' => $embedTypes,
            'preloaded_js' => $preloadedJs,
            'preloaded_css' => $preloadedCss,
            'drop_library_css' => $dropLibraryCss,
            'semantics' => $library['semantics'],
            'has_icon' => $library['hasIcon'] ? 1 : 0,
            'metadata_settings'=> $library['metadataSettings'],
            'add_to' => isset($library['addTo']) ? json_encode($library['addTo']) : NULL
          ),
          array('id' => $library['libraryId']),
          array(
            '%s',
            '%d',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s'
          ),
          array('%d')
        );
      $this->deleteLibraryDependencies($library['libraryId']);
    }

    // Log library successfully installed/upgraded
    new H5P_Event('library', ($new ? 'create' : 'update'),
        NULL, NULL,
        $library['machineName'], $library['majorVersion'] . '.' . $library['minorVersion']);

    // Update languages
    $wpdb->delete(
        $wpdb->prefix . 'h5p_libraries_languages',
        array('library_id' => $library['libraryId']),
        array('%d')
      );

    if (isset($library['language'])) {
      foreach ($library['language'] as $languageCode => $translation) {
        $wpdb->insert(
          $wpdb->prefix . 'h5p_libraries_languages',
          array(
            'library_id' => $library['libraryId'],
            'language_code' => $languageCode,
            'translation' => $translation
          ),
          array(
            '%d',
            '%s',
            '%s'
          )
        );
      }
    }
  }

  /**
   * Convert list of file paths to csv
   *
   * @param array $library
   *  Library data as found in library.json files
   * @param string $key
   *  Key that should be found in $libraryData
   * @return string
   *  file paths separated by ', '
   */
  private function pathsToCsv($library, $key) {
    if (isset($library[$key])) {
      $paths = array();
      foreach ($library[$key] as $file) {
        $paths[] = $file['path'];
      }
      return implode(', ', $paths);
    }
    return '';
  }

  /**
   * Implements deleteLibraryDependencies
   */
  public function deleteLibraryDependencies($id) {
    global $wpdb;

    $wpdb->delete(
        $wpdb->prefix . 'h5p_libraries_libraries',
        array('library_id' => $id),
        array('%d')
      );
  }

  /**
   * Implements deleteLibrary
   */
  public function deleteLibrary($library) {
    global $wpdb;

    // Delete library files
    H5PCore::deleteFileTree($this->getH5pPath() . '/libraries/' . $library->name . '-' . $library->major_version . '.' . $library->minor_version);

    // Remove library data from database
    $wpdb->delete($wpdb->prefix . 'h5p_libraries_libraries', array('library_id' => $library->id), array('%d'));
    $wpdb->delete($wpdb->prefix . 'h5p_libraries_languages', array('library_id' => $library->id), array('%d'));
    $wpdb->delete($wpdb->prefix . 'h5p_libraries', array('id' => $library->id), array('%d'));
  }

  /**
   * Implements saveLibraryDependencies
   */
  public function saveLibraryDependencies($id, $dependencies, $dependencyType) {
    global $wpdb;

    foreach ($dependencies as $dependency) {
      $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}h5p_libraries_libraries (library_id, required_library_id, dependency_type)
        SELECT %d, hl.id, %s
        FROM {$wpdb->prefix}h5p_libraries hl
        WHERE name = %s
        AND major_version = %d
        AND minor_version = %d
        ON DUPLICATE KEY UPDATE dependency_type = %s",
        $id, $dependencyType, $dependency['machineName'], $dependency['majorVersion'], $dependency['minorVersion'], $dependencyType)
      );
    }
  }

  /**
   * Implements updateContent
   */
  public function updateContent($content, $contentMainId = NULL) {
    global $wpdb;

    $metadata = (array)$content['metadata'];
    $table = $wpdb->prefix . 'h5p_contents';

    $format = array();
    $data = array_merge(\H5PMetadata::toDBArray($metadata, true, true, $format), array(
      'updated_at' => current_time('mysql', 1),
      'parameters' => $content['params'],
      'embed_type' => 'div', // TODO: Determine from library?
      'library_id' => $content['library']['libraryId'],
      'filtered' => '',
      'disable' => $content['disable']
    ));

    $format[] = '%s'; // updated_at
    $format[] = '%s'; // parameters
    $format[] = '%s'; // embed_type
    $format[] = '%d'; // library_id
    $format[] = '%s'; // filtered
    $format[] = '%d'; // disable

    if (!isset($content['id'])) {
      // Insert new content
      $data['created_at'] = $data['updated_at'];
      $format[] = '%s';
      $data['user_id'] = get_current_user_id();
      $format[] = '%d';
      $wpdb->insert($table, $data, $format);
      $content['id'] = $wpdb->insert_id;
      $event_type = 'create';
    }
    else {
      // Update existing content
      $wpdb->update($table, $data, array('id' => $content['id']), $format, array('%d'));
      $event_type = 'update';
    }

    // Log content create/update/upload
    if (!empty($content['uploaded'])) {
      $event_type .= ' upload';
    }
    new H5P_Event('content', $event_type,
        $content['id'],
        $metadata['title'],
        $content['library']['machineName'],
        $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']);

    return $content['id'];
  }

  /**
   * Implements insertContent
   */
  public function insertContent($content, $contentMainId = NULL) {
    return $this->updateContent($content);
  }

  /**
   * Implement getWhitelist
   */
  public function getWhitelist($isLibrary, $defaultContentWhitelist, $defaultLibraryWhitelist) {
    // TODO: Get this value from a settings page.
    $whitelist = $defaultContentWhitelist;
    if ($isLibrary) {
      $whitelist .= ' ' . $defaultLibraryWhitelist;
    }
    return $whitelist;
  }

  /**
   * Implements copyLibraryUsage
   */
  public function copyLibraryUsage($contentId, $copyFromId, $contentMainId = NULL) {
    global $wpdb;

    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}h5p_contents_libraries (content_id, library_id, dependency_type, weight, drop_css)
        SELECT %d, hcl.library_id, hcl.dependency_type, hcl.weight, hcl.drop_css
          FROM {$wpdb->prefix}h5p_contents_libraries hcl
          WHERE hcl.content_id = %d",
        $contentId, $copyFromId)
      );
  }

  /**
   * Implements deleteContentData
   */
  public function deleteContentData($id) {
    global $wpdb;

    // Remove content data and library usage
    $wpdb->delete($wpdb->prefix . 'h5p_contents', array('id' => $id), array('%d'));
    $this->deleteLibraryUsage($id);

    // Remove user scores/results
    $wpdb->delete($wpdb->prefix . 'h5p_results', array('content_id' => $id), array('%d'));

    // Remove contents user/usage data
    $wpdb->delete($wpdb->prefix . 'h5p_contents_user_data', array('content_id' => $id), array('%d'));
  }

  /**
   * Implements deleteLibraryUsage
   */
  public function deleteLibraryUsage($contentId) {
    global $wpdb;

    $wpdb->delete($wpdb->prefix . 'h5p_contents_libraries', array('content_id' => $contentId), array('%d'));
  }

  /**
   * Implements saveLibraryUsage
   */
  public function saveLibraryUsage($contentId, $librariesInUse) {
    global $wpdb;

    $dropLibraryCssList = array();
    foreach ($librariesInUse as $dependency) {
      if (!empty($dependency['library']['dropLibraryCss'])) {
        $dropLibraryCssList = array_merge($dropLibraryCssList, explode(', ', $dependency['library']['dropLibraryCss']));
      }
    }

    foreach ($librariesInUse as $dependency) {
      $dropCss = in_array($dependency['library']['machineName'], $dropLibraryCssList) ? 1 : 0;
      $wpdb->insert(
          $wpdb->prefix . 'h5p_contents_libraries',
          array(
            'content_id' => $contentId,
            'library_id' => $dependency['library']['libraryId'],
            'dependency_type' => $dependency['type'],
            'drop_css' => $dropCss,
            'weight' => $dependency['weight']
          ),
          array(
            '%d',
            '%d',
            '%s',
            '%d',
            '%d'
          )
        );
    }
  }

  /**
   * Implements loadLibrary
   */
  public function loadLibrary($name, $majorVersion, $minorVersion) {
    global $wpdb;

    $library = $wpdb->get_row($wpdb->prepare(
        "SELECT id as libraryId, name as machineName, title, major_version as majorVersion, minor_version as minorVersion, patch_version as patchVersion,
          embed_types as embedTypes, preloaded_js as preloadedJs, preloaded_css as preloadedCss, drop_library_css as dropLibraryCss, fullscreen, runnable,
          semantics, has_icon as hasIcon
        FROM {$wpdb->prefix}h5p_libraries
        WHERE name = %s
        AND major_version = %d
        AND minor_version = %d",
        $name,
        $majorVersion,
        $minorVersion),
        ARRAY_A
      );

    $dependencies = $wpdb->get_results($wpdb->prepare(
        "SELECT hl.name as machineName, hl.major_version as majorVersion, hl.minor_version as minorVersion, hll.dependency_type as dependencyType
        FROM {$wpdb->prefix}h5p_libraries_libraries hll
        JOIN {$wpdb->prefix}h5p_libraries hl ON hll.required_library_id = hl.id
        WHERE hll.library_id = %d",
        $library['libraryId'])
      );
    foreach ($dependencies as $dependency) {
      $library[$dependency->dependencyType . 'Dependencies'][] = array(
        'machineName' => $dependency->machineName,
        'majorVersion' => $dependency->majorVersion,
        'minorVersion' => $dependency->minorVersion,
      );
    }
    if ($this->isInDevMode()) {
      $semantics = $this->getSemanticsFromFile($library['machineName'], $library['majorVersion'], $library['minorVersion']);
      if ($semantics) {
        $library['semantics'] = $semantics;
      }
    }
    return $library;
  }

  private function getSemanticsFromFile($name, $majorVersion, $minorVersion) {
    $semanticsPath = $this->getH5pPath() . '/libraries/' . $name . '-' . $majorVersion . '.' . $minorVersion . '/semantics.json';
    if (file_exists($semanticsPath)) {
      $semantics = file_get_contents($semanticsPath);
      if (!json_decode($semantics, TRUE)) {
        $this->setErrorMessage($this->t('Invalid json in semantics for %library', array('%library' => $name)));
      }
      return $semantics;
    }
    return FALSE;
  }

  /**
   * Implements loadLibrarySemantics
   */
  public function loadLibrarySemantics($name, $majorVersion, $minorVersion) {
    global $wpdb;

    if ($this->isInDevMode()) {
      $semantics = $this->getSemanticsFromFile($name, $majorVersion, $minorVersion);
    }
    else {
      $semantics = $wpdb->get_var($wpdb->prepare(
          "SELECT semantics
          FROM {$wpdb->prefix}h5p_libraries
          WHERE name = %s
          AND major_version = %d
          AND minor_version = %d",
          $name, $majorVersion, $minorVersion)
        );
    }
    return ($semantics === FALSE ? NULL : $semantics);
  }

  /**
   * Implements alterLibrarySemantics
   */
  public function alterLibrarySemantics(&$semantics, $name, $majorVersion, $minorVersion) {
    /**
     * Allows you to alter the H5P library semantics, i.e. changing how the
     * editor looks and how content parameters are filtered.
     *
     * @since 1.5.3
     *
     * @param object &$semantics
     * @param string $libraryName
     * @param int $libraryMajorVersion
     * @param int $libraryMinorVersion
     */
    do_action_ref_array('h5p_alter_library_semantics', array(&$semantics, $name, $majorVersion, $minorVersion));
  }

  /**
   * Implements loadContent
   */
  public function loadContent($id) {
    global $wpdb;

    $content = $wpdb->get_row($wpdb->prepare(
        "SELECT hc.id
              , hc.title
              , hc.parameters AS params
              , hc.filtered
              , hc.slug AS slug
              , hc.user_id
              , hc.embed_type AS embedType
              , hc.disable
              , hl.id AS libraryId
              , hl.name AS libraryName
              , hl.major_version AS libraryMajorVersion
              , hl.minor_version AS libraryMinorVersion
              , hl.embed_types AS libraryEmbedTypes
              , hl.fullscreen AS libraryFullscreen
              , hc.authors AS authors
              , hc.source AS source
              , hc.year_from AS yearFrom
              , hc.year_to AS yearTo
              , hc.license AS license
              , hc.license_version AS licenseVersion
              , hc.license_extras AS licenseExtras
              , hc.author_comments AS authorComments
              , hc.changes AS changes
              , hc.default_language AS defaultLanguage
              , hc.a11y_title AS a11yTitle
        FROM {$wpdb->prefix}h5p_contents hc
        JOIN {$wpdb->prefix}h5p_libraries hl ON hl.id = hc.library_id
        WHERE hc.id = %d",
        $id),
        ARRAY_A
      );

    if ($content !== NULL) {
      $content['metadata'] = array();
      $metadata_structure = array('title', 'authors', 'source', 'yearFrom', 'yearTo', 'license', 'licenseVersion', 'licenseExtras', 'authorComments', 'changes', 'defaultLanguage', 'a11yTitle');
      foreach ($metadata_structure as $property) {
        if (!empty($content[$property])) {
          if ($property === 'authors' || $property === 'changes') {
            $content['metadata'][$property] = json_decode($content[$property]);
          }
          else {
            $content['metadata'][$property] = $content[$property];
          }
          if ($property !== 'title') {
            unset($content[$property]); // Unset all except title
          }
        }
      }
    }

    return $content;
  }

  /**
   * Implements loadContentDependencies
   */
  public function loadContentDependencies($id, $type = NULL) {
    global $wpdb;

    $query =
        "SELECT hl.id
              , hl.name AS machineName
              , hl.major_version AS majorVersion
              , hl.minor_version AS minorVersion
              , hl.patch_version AS patchVersion
              , hl.preloaded_css AS preloadedCss
              , hl.preloaded_js AS preloadedJs
              , hcl.drop_css AS dropCss
              , hcl.dependency_type AS dependencyType
        FROM {$wpdb->prefix}h5p_contents_libraries hcl
        JOIN {$wpdb->prefix}h5p_libraries hl ON hcl.library_id = hl.id
        WHERE hcl.content_id = %d";
    $queryArgs = array($id);

    if ($type !== NULL) {
      $query .= " AND hcl.dependency_type = %s";
      $queryArgs[] = $type;
    }

    $query .= " ORDER BY hcl.weight";
    return $wpdb->get_results($wpdb->prepare($query, $queryArgs), ARRAY_A);
  }

  /**
   * Implements getOption().
   */
  public function getOption($name, $default = FALSE) {
    if ($name === 'site_uuid') {
      $name = 'h5p_site_uuid'; // Make up for old core bug
    }
    return get_option('h5p_' . $name, $default);
  }


  /**
   * Implements setOption().
   */
  public function setOption($name, $value) {
    if ($name === 'site_uuid') {
      $name = 'h5p_site_uuid'; // Make up for old core bug
    }
    $var = $this->getOption($name);
    $name = 'h5p_' . $name; // Always prefix to avoid conflicts
    if ($var === FALSE) {
      add_option($name, $value);
    }
    else {
      update_option($name, $value);
    }
  }

  /**
   * Convert variables to fit our DB.
   */
  private static function camelToString($input) {
    $input = preg_replace('/[a-z0-9]([A-Z])[a-z0-9]/', '_$1', $input);
    return strtolower($input);
  }

  /**
   * Implements setFilteredParameters().
   */
  public function updateContentFields($id, $fields) {
    global $wpdb;

    $processedFields = array();
    $format = array();
    foreach ($fields as $name => $value) {
      if (is_int($value)) {
        $format[] = '%d'; // Int
      }
      else if (is_float($value)) {
        $format[] = '%f'; // Float
      }
      else {
        $format[] = '%s'; // String
      }

      $processedFields[self::camelToString($name)] = $value;
    }

    $wpdb->update(
      $wpdb->prefix . 'h5p_contents',
      $processedFields,
      array('id' => $id),
      $format,
      array('%d'));
  }

  /**
   * Implements clearFilteredParameters().
   */
  public function clearFilteredParameters($library_ids) {
    global $wpdb;

    $wpdb->query($wpdb->prepare(
      "UPDATE {$wpdb->prefix}h5p_contents
          SET filtered = NULL
        WHERE library_id IN (%s)",
      implode(',', $library_ids))
    );
  }

  /**
   * Implements getNumNotFiltered().
   */
  public function getNumNotFiltered() {
    global $wpdb;

    return (int) $wpdb->get_var(
      "SELECT COUNT(id)
        FROM {$wpdb->prefix}h5p_contents
        WHERE filtered = ''"
    );
  }

  /**
   * Implements getNumContent().
   */
  public function getNumContent($library_id, $skip = NULL) {
    global $wpdb;
    $skip_query = empty($skip) ? '' : " AND id NOT IN ($skip)";

    return (int) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(id)
         FROM {$wpdb->prefix}h5p_contents
        WHERE library_id = %d
              {$skip_query}",
      $library_id
    ));
  }


  /**
   * Implements loadLibraries.
   */
  public function loadLibraries() {
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT id, name, title, major_version, minor_version, patch_version, runnable, restricted
          FROM {$wpdb->prefix}h5p_libraries
          ORDER BY title ASC, major_version ASC, minor_version ASC"
    );

    $libraries = array();
    foreach ($results as $library) {
      $libraries[$library->name][] = $library;
    }

    return $libraries;
  }

  /**
   * Implements getAdminUrl.
   */
  public function getAdminUrl() {

  }

  /**
   * Implements getPlatformInfo
   */
  public function getPlatformInfo() {
    global $wp_version;

    return array(
      'name' => 'WordPress',
      'version' => $wp_version,
      'h5pVersion' => H5P_Plugin::VERSION
    );
  }

  /**
   * Implements fetchExternalData
   */
  public function fetchExternalData($url, $data = NULL, $blocking = TRUE, $stream = NULL) {
    @set_time_limit(0);
    $options = array(
      'timeout' => !empty($blocking) ? 30 : 0.01,
      'stream' => !empty($stream),
      'filename' => !empty($stream) ? $stream : FALSE
    );

    if ($data !== NULL) {
      // Post
      $options['body'] = $data;
      $response = wp_remote_post($url, $options);
    }
    else {
      // Get

      if (empty($options['filename'])) {
        // Support redirects
        $response = wp_remote_get($url);
      }
      else {
        // Use safe when downloading files
        $response = wp_safe_remote_get($url, $options);
      }
    }

    if (is_wp_error($response)) {
      $this->setErrorMessage($response->get_error_message(), 'failed-fetching-external-data');
      return FALSE;
    }
    elseif ($response['response']['code'] === 200) {
      return empty($response['body']) ? TRUE : $response['body'];
    }

    return NULL;
  }

  /**
   * Implements setLibraryTutorialUrl
   */
  public function setLibraryTutorialUrl($library_name, $url) {
    global $wpdb;

    $wpdb->update(
      $wpdb->prefix . 'h5p_libraries',
      array('tutorial_url' => $url),
      array('name' => $library_name),
      array('%s'),
      array('%s')
    );
  }

  /**
   * Implements resetContentUserData
   */
  public function resetContentUserData($contentId) {
    global $wpdb;

    // Reset user datas for this content
    $wpdb->update(
      $wpdb->prefix . 'h5p_contents_user_data',
      array(
        'updated_at' => current_time('mysql', 1),
        'data' => 'RESET'
      ),
      array(
        'content_id' => $contentId,
        'invalidate' => 1
      ),
      array('%s', '%s'),
      array('%d', '%d')
    );
  }

  /**
   * Implements isContentSlugAvailable
   */
  public function isContentSlugAvailable($slug) {
    global $wpdb;
    return !$wpdb->get_var($wpdb->prepare("SELECT slug FROM {$wpdb->prefix}h5p_contents WHERE slug = '%s'", $slug));
  }

  /**
   * Implements getLibraryContentCount
   */
  public function getLibraryContentCount() {
    global $wpdb;
    $count = array();

    // Find number of content per library
    $results = $wpdb->get_results("
        SELECT l.name, l.major_version, l.minor_version, COUNT(*) AS count
          FROM {$wpdb->prefix}h5p_contents c, {$wpdb->prefix}h5p_libraries l
         WHERE c.library_id = l.id
      GROUP BY l.name, l.major_version, l.minor_version
        ");

    // Extract results
    foreach($results as $library) {
      $count[$library->name . ' ' . $library->major_version . '.' . $library->minor_version] = $library->count;
    }
    return $count;
  }

  /**
   * Implements getLibraryStats
   */
  public function getLibraryStats($type) {
    global $wpdb;
    $count = array();

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT library_name AS name,
               library_version AS version,
               num
          FROM {$wpdb->prefix}h5p_counters
         WHERE type = %s
        ", $type));

    // Extract results
    foreach($results as $library) {
      $count[$library->name . ' ' . $library->version] = $library->num;
    }

    return $count;
  }

  /**
   * Implements getNumAuthors
   */
  public function getNumAuthors() {
    global $wpdb;
    return $wpdb->get_var("
        SELECT COUNT(DISTINCT user_id)
          FROM {$wpdb->prefix}h5p_contents
    ");
  }

  // Magic stuff not used, we do not support library development mode.
  public function lockDependencyStorage() {}
  public function unlockDependencyStorage() {}

  /**
   * Implements saveCachedAssets
   */
  public function saveCachedAssets($key, $libraries) {
    global $wpdb;

    foreach ($libraries as $library) {
      // TODO: Avoid errors if they already exists...
      $wpdb->insert(
          "{$wpdb->prefix}h5p_libraries_cachedassets",
          array(
            'library_id' => isset($library['id']) ? $library['id'] : $library['libraryId'],
            'hash' => $key
          ),
          array(
            '%d',
            '%s'
          ));
    }
  }

  /**
   * Implements deleteCachedAssets
   */
  public function deleteCachedAssets($library_id) {
    global $wpdb;

    // Get all the keys so we can remove the files
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT hash
          FROM {$wpdb->prefix}h5p_libraries_cachedassets
         WHERE library_id = %d
        ", $library_id));

    // Remove all invalid keys
    $hashes = array();
    foreach ($results as $key) {
      $hashes[] = $key->hash;

      $wpdb->delete(
          "{$wpdb->prefix}h5p_libraries_cachedassets",
          array('hash' => $key->hash),
          array('%s'));
    }

    return $hashes;
  }

  /**
   * Implements afterExportCreated
   */
  public function afterExportCreated($content, $filename) {
    // Clear cached value for dirsize.
    delete_transient('dirsize_cache');
  }

  /**
   * Check if current user can edit H5P
   *
   * @method currentUserCanEdit
   * @param  int             $contentUserId
   * @return boolean
   */
  private static function currentUserCanEdit ($contentUserId) {
    if (current_user_can('edit_others_h5p_contents')) {
      return TRUE;
    }
    return get_current_user_id() == $contentUserId;
  }

  /**
   * Implements hasPermission
   *
   * @method hasPermission
   * @param  H5PPermission    $permission
   * @param  int              $contentUserId
   * @return boolean
   */
  public function hasPermission($permission, $contentUserId = NULL) {
    switch ($permission) {
      case H5PPermission::DOWNLOAD_H5P:
      case H5PPermission::EMBED_H5P:
      case H5PPermission::COPY_H5P:
        return self::currentUserCanEdit($contentUserId);

      case H5PPermission::CREATE_RESTRICTED:
      case H5PPermission::UPDATE_LIBRARIES:
        return current_user_can('manage_h5p_libraries');

      case H5PPermission::INSTALL_RECOMMENDED:
        current_user_can('install_recommended_h5p_libraries');

    }
    return FALSE;
  }

  /**
   * Replaces existing content type cache with the one passed in
   *
   * @param object $contentTypeCache Json with an array called 'libraries'
   *  containing the new content type cache that should replace the old one.
   */
  public function replaceContentTypeCache($contentTypeCache) {
    global $wpdb;

    // Replace existing content type cache
    $wpdb->query("TRUNCATE TABLE {$wpdb->base_prefix}h5p_libraries_hub_cache");
    foreach ($contentTypeCache->contentTypes as $ct) {
      // Insert into db
      $wpdb->insert("{$wpdb->base_prefix}h5p_libraries_hub_cache", array(
        'machine_name'      => $ct->id,
        'major_version'     => $ct->version->major,
        'minor_version'     => $ct->version->minor,
        'patch_version'     => $ct->version->patch,
        'h5p_major_version' => $ct->coreApiVersionNeeded->major,
        'h5p_minor_version' => $ct->coreApiVersionNeeded->minor,
        'title'             => $ct->title,
        'summary'           => $ct->summary,
        'description'       => $ct->description,
        'icon'              => $ct->icon,
        'created_at'        => self::dateTimeToTime($ct->createdAt),
        'updated_at'        => self::dateTimeToTime($ct->updatedAt),
        'is_recommended'    => $ct->isRecommended === TRUE ? 1 : 0,
        'popularity'        => $ct->popularity,
        'screenshots'       => json_encode($ct->screenshots),
        'license'           => json_encode(isset($ct->license) ? $ct->license : array()),
        'example'           => $ct->example,
        'tutorial'          => isset($ct->tutorial) ? $ct->tutorial : '',
        'keywords'          => json_encode(isset($ct->keywords) ? $ct->keywords : array()),
        'categories'        => json_encode(isset($ct->categories) ? $ct->categories : array()),
        'owner'             => $ct->owner
      ), array(
        '%s',
        '%d',
        '%d',
        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%d',
        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s'
      ));
    }
  }

  /**
   * Convert datetime string to unix timestamp
   *
   * @param string $datetime
   * @return int unix timestamp
   */
  public static function dateTimeToTime($datetime) {
    $dt = new DateTime($datetime);
    return $dt->getTimestamp();
  }

  /**
   * Load addon libraries
   *
   * @return array
   */
  public function loadAddons() {
    global $wpdb;
    // Load addons
    // If there are several versions of the same addon, pick the newest one
    return $wpdb->get_results(
       "SELECT l1.id as libraryId, l1.name as machineName,
              l1.major_version as majorVersion, l1.minor_version as minorVersion,
              l1.patch_version as patchVersion, l1.add_to as addTo,
              l1.preloaded_js as preloadedJs, l1.preloaded_css as preloadedCss
        FROM {$wpdb->prefix}h5p_libraries AS l1
        LEFT JOIN {$wpdb->prefix}h5p_libraries AS l2
          ON l1.name = l2.name AND
            (l1.major_version < l2.major_version OR
              (l1.major_version = l2.major_version AND
               l1.minor_version < l2.minor_version))
        WHERE l1.add_to IS NOT NULL AND l2.name IS NULL", ARRAY_A
    );

    // NOTE: These are treated as library objects but are missing the following properties:
    // title, embed_types, drop_library_css, fullscreen, runnable, semantics, has_icon
  }

  /**
   * Implements getLibraryConfig
   *
   * @param array $libraries
   * @return array
   */
  public function getLibraryConfig($libraries = NULL) {
     return defined('H5P_LIBRARY_CONFIG') ? H5P_LIBRARY_CONFIG : NULL;
  }

  /**
   * Implements libraryHasUpgrade
   */
  public function libraryHasUpgrade($library) {
    global $wpdb;

    return $wpdb->get_var($wpdb->prepare(
        "SELECT id
          FROM {$wpdb->prefix}h5p_libraries
          WHERE name = '%s'
          AND (major_version > %d
           OR (major_version = %d AND minor_version > %d))
        LIMIT 1",
        $library['machineName'],
        $library['majorVersion'],
        $library['majorVersion'],
        $library['minorVersion']
    )) !== NULL;
  }
}
