<?php
/**
 * H5P Content Sharing.
 *
 * @package   H5P
 * @author    Oliver Tacke <oliver@snordian.de>, Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2022 Joubel
 */

/**
 * H5P Content Sharing class.
 *
 * @package H5PContentSharing
 * @author  Oliver Tacke <oliver@snordian.de>, Joubel <contact@joubel.com>
 */
class H5PContentSharing {

  /**
   * Display form to register account on the H5P OER Hub.
   *
   * @since 1.15.5
   * @param int $content_id Content id of content to share.
   */
  public static function display_share_content_form($content_id) {
    $plugin = H5P_Plugin::get_instance();

    // Check capability to share content.
    if (self::current_user_can_share($content_id) == FALSE) {
      H5P_Plugin_Admin::set_error(__('You are not allowed to share this content.', $plugin->get_plugin_slug()));
      H5P_Plugin_Admin::print_messages();
      wp_die();
    }

    $plugin = H5P_Plugin::get_instance();
    $core = $plugin->get_h5p_instance('core');
    $content = $plugin->get_content($content_id);

    // Try loading existing info from the HUB
    try {
      $hubcontent = !empty($content['contentHubId']) ?
        $core->hubRetrieveContent($content['contentHubId']) :
        NULL;
    }
    catch (Exception $e) {
      H5P_Plugin_Admin::set_error(__(!empty($e->errors) ?
        $e->errors :
        $e->getMessage(), $plugin->get_plugin_slug()));
      H5P_Plugin_Admin::print_messages();
      wp_die();
    }

    // Try to populate with license from content or set defaults
    if (empty($content['contentHubId'])) {
      $license = isset($content['metadata']['license']) ?
        $content['metadata']['license'] :
        NULL;

      $licenseVersion = (isset($license) && isset($content['metadata']['licenseVersion'])) ?
        $content['metadata']['licenseVersion'] :
        NULL;

      $showCopyrightWarning = FALSE;

      // "Undisclosed" and "Copyright" licenses are not allowed on the content hub
      if ($license === 'U') {
        $license = NULL;
      }

      if ($license === 'C') {
        $license = NULL;
        $showCopyrightWarning = true;
      }

      $hubcontent = [
        'license' => $license,
        'licenseVersion' => $licenseVersion,
        'showCopyrightWarning' => $showCopyrightWarning,
      ];
    }

    $language = isset($content['language']) ?
      $content['language'] :
      $plugin->get_language();

    /*
     * Set settings for H5P Hub sharing client.
     * https://github.com/h5p/h5p-hub-sharing-ui
     */
    $nonce = wp_create_nonce( 'h5p_content_sharing' );
    $settings = array(
      'h5pContentHubPublish' => array(
        'token' => H5PCore::createToken('content_hub_sharing'),
        'publishURL' => admin_url('admin-ajax.php?action=h5p_hub_sharing&id=' . $content_id . '&_wpnonce=' . $nonce),
        'returnURL' => admin_url('admin.php?page=h5p&task=show&id=' . $content_id),
        'l10n' => $core->getLocalization(),
        'metadata' => json_decode($core->getUpdatedContentHubMetadataCache($language)),
        'title' => $content['title'],
        'contentType' => H5PCore::libraryToString($content['library']),
        'language' => $language,
        'hubContent' => $hubcontent,
        'context' => isset($content['shared']) ? 'edit' : 'publish'
      ),
    );

    // Render page
    $plugin->print_settings($settings, 'H5POERHubSharing');
    include_once('views/hub-sharing.php');
    H5P_Plugin_Admin::add_style('h5p-css', 'h5p-php-library/styles/h5p.css');
    H5P_Plugin_Admin::add_script('h5p-hub-registration', 'h5p-php-library/js/h5p-hub-sharing.js');
    H5P_Plugin_Admin::add_style('h5p-hub-registration-css', 'h5p-php-library/styles/h5p-hub-sharing.css');
    H5P_Plugin_Admin::add_script('h5p-hub-registration-wp', 'admin/scripts/h5p-hub-sharing.js');
  }

  /**
   * Share content on the H5P Hub.
   *
   * @since 1.15.5
   */
  public static function ajax_hub_sharing() {
    $plugin = H5P_Plugin::get_instance();

    // Load content.
    $content = $plugin->get_content(filter_input(INPUT_GET, 'id'));

    // Check capability to share content.
    if (self::current_user_can_share($content['id']) == FALSE) {
      H5PCore::ajaxError(
        __('You are not allowed to share this content.',
        $plugin->get_plugin_slug())
      );
      wp_die();
    }

    // Check token
    if (!check_ajax_referer( 'h5p_content_sharing', FALSE, FALSE )) {
      H5PCore::ajaxError(
        __('Invalid security token.', $plugin->get_plugin_slug())
      );
      wp_die();
    }

    // Update Hub status for content before proceeding.
    $newstate = self::update_hub_status($content);
    $synced = $newstate !== FALSE ? $newstate : intval($content['synced']);

    if (
      isset($content['synced']) &&
      $content['synced'] === H5PContentHubSyncStatus::WAITING
    ) {
      H5PCore::ajaxError(
        __('Content is being synced.', $plugin->get_plugin_slug())
      );
      wp_die();
    }

    // Add POST fields to redirect to Hub
    $data = filter_input_array(INPUT_POST, array(
      'title' => FILTER_UNSAFE_RAW,
      'language' => FILTER_UNSAFE_RAW,
      'level' => FILTER_UNSAFE_RAW,
      'license' => FILTER_UNSAFE_RAW,
      'license_version' => FILTER_UNSAFE_RAW,
      'disciplines' => array(
        'filter'    => FILTER_UNSAFE_RAW,
        'flags'     => FILTER_REQUIRE_ARRAY,
      ),
      'keywords' => array(
        'filter'    => FILTER_UNSAFE_RAW,
        'flags'     => FILTER_REQUIRE_ARRAY,
      ),
      'summary' => FILTER_UNSAFE_RAW,
      'description' => FILTER_UNSAFE_RAW,
      'screenshot_alt_texts' => array(
        'filter' => FILTER_UNSAFE_RAW,
        'flags' => FILTER_REQUIRE_ARRAY,
      ),
      'remove_screenshots' => array(
        'filter' => FILTER_UNSAFE_RAW,
        'flags' => FILTER_REQUIRE_ARRAY,
      ),
      'remove_icon' => FILTER_UNSAFE_RAW,
      'age' => FILTER_UNSAFE_RAW,
    ));

    // Determine export path and file size
    $export = $plugin->get_h5p_path() . '/exports/' . ($content['slug'] ? $content['slug'] . '-' : '') . $content['id'] . '.h5p';
    $size = filesize($export);

    // Prepare additional data to POST to Hub
    $data['download_url'] = wp_upload_dir()['baseurl'] . '/h5p/exports/' . ($content['slug'] ? $content['slug'] . '-' : '') . $content['id'] . '.h5p';
    $data['size'] = empty($size) ? -1 : $size;

    // Add the icon and any screenshots
    $files = array(
      'icon' => !empty($_FILES['icon']) ? $_FILES['icon'] : NULL,
      'screenshots' => !empty($_FILES['screenshots']) ?
        $_FILES['screenshots'] :
        NULL,
    );

    // Let H5P core share content
    $core = $plugin->get_h5p_instance('core');
    try {
      $isEdit = !empty($content['contentHubId']);
      $updateContent = isset($content['h5p_synced']) &&
        (int)($content['h5p_synced']) === H5PContentHubSyncStatus::NOT_SYNCED &&
        $isEdit;

      if ($updateContent) {
        // node has been edited since the last time it was published
        $data['resync'] = 1;
      }
      $result = $core->hubPublishContent(
        $data, $files, $isEdit ? $content['contentHubId'] : NULL
      );

      $fields = array(
        'shared' => 1, // Content is always shared after sharing or editing
      );
      if (!$isEdit) {
        $fields['content_hub_id'] = $result->content->id;
        // Sync will not happen on 'edit info', only for 'publish' or 'sync'.
        $fields['synced'] = H5PContentHubSyncStatus::WAITING;
      }
      else if ($updateContent) {
        $fields['synced'] = H5PContentHubSyncStatus::WAITING;
      }

      // Update database fields
      $core = $plugin->get_h5p_instance('core');
      $core->h5pF->updateContentFields($content['id'], $fields);

      H5PCore::ajaxSuccess();
      wp_die();
    }
    catch (Exception $e) {
      H5PCore::ajaxError(!empty($e->errors) ? $e->errors : $e->getMessage());
      wp_die();
    }
  }

  /**
   * Unshare content.
   *
   * @since 1.15.5
   * @param int $content_id Content id of content to be unshared.
   */
  public static function unshare($content_id) {
    $plugin = H5P_Plugin::get_instance();

    // Check capability to share content.
    if (self::current_user_can_share($content_id) == FALSE) {
      H5P_Plugin_Admin::set_error(__('You are not allowed to unshare this content.', $plugin->get_plugin_slug()));
      H5P_Plugin_Admin::print_messages();
      wp_die();
    }

    $core = $plugin->get_h5p_instance('core');
    $content = $plugin->get_content($content_id);
    $success = $core->hubUnpublishContent($content['contentHubId']);

    if ($success) {
      $core->h5pF->updateContentFields(
        $content['id'],
        array('shared' => H5PContentStatus::STATUS_UNPUBLISHED)
      );
    }

    H5P_Plugin_Admin::print_messages();
  }

  /**
   * Sync content.
   *
   * @since 1.15.5
   * @param int $content_id Content id of content to be synced.
   */
  public static function sync($content_id) {
    $plugin = H5P_Plugin::get_instance();

    // Check capability to share content.
    if (self::current_user_can_share($content_id) == FALSE) {
      H5P_Plugin_Admin::set_error(__('You are not allowed to sync this content with the Hub.', $plugin->get_plugin_slug()));
      H5P_Plugin_Admin::print_messages();
      wp_die();
    }

    $core = $plugin->get_h5p_instance('core');
    $content = $plugin->get_content($content_id);
    $exportUrl = wp_upload_dir()['baseurl'] . '/h5p/exports/' . ($content['slug'] ? $content['slug'] . '-' : '') . $content['id'] . '.h5p';
    $hubId = $content['contentHubId'];
    $success = $core->hubSyncContent($hubId, $exportUrl);

    if ($success) {
      $core->h5pF->updateContentFields(
        $content['id'],
        array('synced' => H5PContentHubSyncStatus::WAITING)
      );
    }

    H5P_Plugin_Admin::print_messages();
  }

  /**
   * Update content hub status for shared content.
   *
   * @since 1.15.5
   * @param array $content Content information.
   * @return bool|int False if no change, status id otherwise.
   */
  public static function update_hub_status($content = []) {
    // Check capability to share content.
    if (self::current_user_can_share($content['id']) == FALSE) {
      H5P_Plugin_Admin::set_error(__('You are not allowed to share this content.', $plugin->get_plugin_slug()));
      H5P_Plugin_Admin::print_messages();
      wp_die();
    }

    $synced = intval($content['synced']);

    // Only check sync status when waiting.
    if (
      empty($content['contentHubId']) ||
      $synced !== H5PContentHubSyncStatus::WAITING
    ) {
      return FALSE;
    }

    $plugin = H5P_Plugin::get_instance();
    $core = $plugin->get_h5p_instance('core');

    $new_state = $core->getHubContentStatus($content['contentHubId'], $synced);
    if ($new_state !== FALSE) {
      $core->h5pF->updateContentFields(
        $content['id'],
        array('synced' => $new_state)
      );
      return $new_state;
    }

    return FALSE;
  }

  /**
   * Permission check. Can the current user share the given content.
   *
   * @since 1.15.5
   * @param int $content_id Content id of content to check capabilities for.
   * @return bool True, if current user can share content.
   */
  private static function current_user_can_share($content_id) {
    if (
      empty(get_option('h5p_hub_is_enabled')) ||
      empty(get_option('h5p_h5p_site_uuid')) ||
      empty(get_option('h5p_hub_secret'))
    ) {
      return FALSE;
    }

    // If you can't share content, neither can you share others contents
    if (!current_user_can('share_h5p_contents')) {
      return FALSE;
    }
    if (current_user_can('share_others_h5p_contents')) {
      return TRUE;
    }

    $plugin = H5P_Plugin::get_instance();
    $content = $plugin->get_content($content_id);
    $author_id = (int)(is_array($content) ?
      $content['user_id'] :
      $content->user_id);

    return get_current_user_id() === $author_id;
  }
}
