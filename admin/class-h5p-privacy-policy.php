<?php
/**
 * H5P Plugin.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2018 Joubel
 */

/**
 * Plugin admin class.
 *
 * @package H5P_Plugin_Admin
 * @author Joubel <contact@joubel.com>
 */
class H5PPrivacyPolicy {

  /**
   * @since 1.10.2
   */
  private $plugin_slug = NULL;

  /**
   * Initialize.
   *
   * @since 1.10.2
   */
   public function __construct($plugin_slug) {
     $this->plugin_slug = $plugin_slug;
   }

   /**
    * Add the privacy policy suggestion text.
    *
    * @since 1.10.2
    */
   public function add_privacy_policy_content() {
     if (!function_exists('wp_add_privacy_policy_content')) {
       return;
     }
     $content = sprintf(
      __( 'FOOBAR', $this->plugin_slug )
     );

     wp_add_privacy_policy_content(
       $this->plugin_slug,
       wp_kses_post(wpautop($content, false))
     );
   }
}
