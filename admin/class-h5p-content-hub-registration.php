<?php
/**
 * H5P Content Hub Registration.
 *
 * @package   H5P
 * @author    Oliver Tacke <oliver@snordian.de>, Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2022 Joubel
 */

/**
 * H5P Content Hub Registration class.
 *
 * @package H5PContentHubRegistration
 * @author Oliver Tacke <oliver@snordian.de>, Joubel <contact@joubel.com>
 */
class H5PContentHubRegistration {

  /**
   * Display form to register account on the H5P OER Hub.
   *
   * @since 1.15.5
   */
  public static function display_register_accout_form() {
    $plugin = H5P_Plugin::get_instance();

    // Check capability to register
    if (!current_user_can('manage_h5p_content_hub_registration')) {
      H5P_Plugin_Admin::set_error(__('You do not have permission to manage the registration with the content hub.', $plugin->get_plugin_slug()));
      H5P_Plugin_Admin::print_messages();
      wp_die();
    }

    // Check token
    if (!check_ajax_referer('h5p_content_hub_registration_form', FALSE, FALSE)) {
      H5P_Plugin_Admin::set_error(__('Invalid security token.', $plugin->get_plugin_slug()));
      H5P_Plugin_Admin::print_messages();
      wp_die();
    }

    // Let H5P core fetch account information from Hub
    $core = $plugin->get_h5p_instance('core');
    try {
      $accountInfo = $core->hubAccountInfo();
    }
    catch (Exception $e) {
      // Go back to H5P configuration, secret has to be removed manually
      wp_safe_redirect(admin_url('options-general.php?page=h5p_settings'));
    }

    /*
     * Settings for H5P Hub client
     * @link https://github.com/h5p/h5p-hub-client
     */
    $settings = array(
      'H5PContentHubRegistration' => array(
        'registrationURL' => admin_url('admin-ajax.php?action=h5p_register_account&_wpnonce=' . wp_create_nonce( 'content_hub_registration' )),
        'accountSettingsUrl' => '',
        'token' => H5PCore::createToken('content_hub_registration'),
        'l10n' => $core->getLocalization(),
        'licenseAgreementTitle' => __('End User License Agreement (EULA)', $plugin->get_plugin_slug()),
        'licenseAgreementDescription' => __('Please read the following agreement before proceeding with the '),
        'licenseAgreementMainText' => 'TODO', // This is a TODO of the original implementation missing in other plugins, too
        'accountInfo' => $accountInfo,
      ),
    );

    // Render to page
    $plugin->print_settings($settings, 'H5POERHubRegistration');
    include_once('views/hub-registration.php');
    H5P_Plugin_Admin::add_style('h5p-css', 'h5p-php-library/styles/h5p.css');
    H5P_Plugin_Admin::add_script('h5p-hub-registration', 'h5p-php-library/js/h5p-hub-registration.js');
    H5P_Plugin_Admin::add_style('h5p-hub-registration-css', 'h5p-php-library/styles/h5p-hub-registration.css');
    H5P_Plugin_Admin::add_script('h5p-hub-registration-wp', 'admin/scripts/h5p-hub-registration.js');
  }

  /**
   * Register with the H5P Content Hub.
   *
   * @since 1.15.5
   */
  public static function ajax_register_account() {
    $plugin = H5P_Plugin::get_instance();

    // Check capability to register
    if (!current_user_can('manage_h5p_content_hub_registration')) {
      H5PCore::ajaxError(
        __('You do not have permission to register the site with the content hub.',
        $plugin->get_plugin_slug()), 'NO_PERMISSION', 403
      );
      wp_die();
    }

    // Check token
    if (!check_ajax_referer( 'content_hub_registration', FALSE, FALSE )) {
      H5PCore::ajaxError(
        __('Invalid security token.', $plugin->get_plugin_slug())
      );
      wp_die();
    }    

    // Retrieve input from post message
    $logo = isset($_FILES['logo']) ? $_FILES['logo'] : NULL;
    $formdata = [
      'name'           => filter_input(INPUT_POST, 'name'),
      'email'          => filter_input(INPUT_POST, 'email'),
      'description'    => filter_input(INPUT_POST, 'description'),
      'contact_person' => filter_input(INPUT_POST, 'contact_person'),
      'phone'          => filter_input(INPUT_POST, 'phone'),
      'address'        => filter_input(INPUT_POST, 'address'),
      'city'           => filter_input(INPUT_POST, 'city'),
      'zip'            => filter_input(INPUT_POST, 'zip'),
      'country'        => filter_input(INPUT_POST, 'country'),
      'remove_logo'    => filter_input(INPUT_POST, 'remove_logo'),
    ];

    // Try to register via H5P core
    $core = $plugin->get_h5p_instance('core');
    $result = $core->hubRegisterAccount($formdata, $logo);

    if ($result['success'] === FALSE) {
      $core->h5pF->setErrorMessage($result['message']);
      http_response_code($result['status_code']);
      H5PCore::ajaxError(
        $result['message'], $result['error_code'], $result['status_code']
      );
      wp_die();
    }

    $core->h5pF->setInfoMessage($result['message']);
    http_response_code(200);
    H5PCore::ajaxSuccess($result['message']);
    wp_die();
  }
}
