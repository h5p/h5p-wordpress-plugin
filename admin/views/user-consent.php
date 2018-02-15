<?php
/**
 * Get the user's consent to enable the hub and usage data tracking
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2018 Joubel
 */
?>

<div class="wrap">
  <h2>
    <?php esc_html_e('Before you start', $this->plugin_slug); ?>
  </h2>
  <div class="notice">
    <form method="post">
      <p><?php esc_html_e('To be able to start creating interactive content you must first install at least one content type.', $this->plugin_slug); ?></p>
      <p><?php esc_html_e('The H5P Hub is here to simplify this process by automatically installing the content types you choose and providing updates for those already installed.', $this->plugin_slug); ?></p>
      <p>
        <?php printf(wp_kses(__('In order for the H5P Hub to be able to do this, communication with H5P.org is required.<br/>
        As this will provide H5P.org with anonymous data on how H5P is used we kindly ask for your consent before proceeding.<br/>
        You can read more on <a href="%s" target="_blank">the plugin communication page</a>.', $this->plugin_slug), array('br' => array(), 'a' => array('href' => array()))), esc_url('https://h5p.org/tracking-the-usage-of-h5p')) ?>
      </p>
      <p><button class="button-primary" name="consent" type="submit" value="1"><?php esc_html_e('I consent, give me the Hub!', $this->plugin_slug); ?></button></p>
      <p><button class="button" name="consent" type="submit" value="0"><?php esc_html_e('I disapprove', $this->plugin_slug); ?></button></p>
      <?php wp_nonce_field('h5p_consent', 'can_has'); ?>
    </form>
  </div>
</div>
