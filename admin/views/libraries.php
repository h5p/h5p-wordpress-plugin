<?php
/**
 * List all H5P Content.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2014 Joubel
 */
?>

<div class="wrap">
  <h2><?php print esc_html(get_admin_page_title()); ?></h2>
  <?php if ($updates_available): ?>
    <form method="post" enctype="multipart/form-data">
      <h3 class="h5p-admin-header"><?php esc_html_e('Update All Libraries', $this->plugin_slug); ?></h3>
      <div class="h5p postbox">
        <div class="h5p-text-holder" id="h5p-download-update">
          <p><?php print esc_html_e('There are updates available for your H5P content types.', $this->plugin_slug) ?></p>
          <p><?php printf(wp_kses(__('You can read about why it\'s important to update and the benefits from doing so on the <a href="%s" target="_blank">Why Update H5P</a> page.', $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), esc_url('https://h5p.org/why-update')); ?>
          <br/><?php print esc_html_e('The page also list the different changelogs, where you can read about the new features introduced and the issues that have been fixed.', $this->plugin_slug) ?></p>
          <p>
            <?php if ($current_update > 1): ?>
              <?php printf(wp_kses(__('The version you\'re running is from <strong>%s</strong>.', $this->plugin_slug), array('strong' => array(), 'em' => array())), date('Y-m-d', $current_update)); ?><br/>
            <?php endif; ?>
            <?php printf(wp_kses(__('The most recent version was released on <strong>%s</strong>.', $this->plugin_slug), array('strong' => array(), 'em' => array())), date('Y-m-d', $update_available)); ?>
          </p>
          <p><?php print esc_html_e('You can use the button below to automatically download and update all of your content types.', $this->plugin_slug) ?></p>
          <?php wp_nonce_field('h5p_update', 'download_update'); ?>
        </div>
        <div class="h5p-button-holder">
          <input type="submit" name="submit" value="<?php print esc_html_e('Download & Update', $this->plugin_slug) ?>" class="button button-primary button-large"/>
        </div>
      </div>
    </form>
  <?php endif; ?>
  <h3 class="h5p-admin-header"><?php esc_html_e('Upload Libraries', $this->plugin_slug); ?></h3>
  <form method="post" enctype="multipart/form-data" id="h5p-library-form">
    <div class="h5p postbox">
      <div class="h5p-text-holder">
        <p><?php print esc_html_e('Here you can upload new libraries or upload updates to existing libraries. Files uploaded here must be in the .h5p file format.', $this->plugin_slug) ?></p>
        <input type="file" name="h5p_file" id="h5p-file"/>
        <input type="checkbox" name="h5p_upgrade_only" id="h5p-upgrade-only"/>
        <label for="h5p-upgrade-only"><?php print __('Only update existing libraries', $this->plugin_slug); ?></label>
        <?php if (current_user_can('disable_h5p_security')): ?>
          <div class="h5p-disable-file-check">
            <label><input type="checkbox" name="h5p_disable_file_check" id="h5p-disable-file-check"/> <?php _e('Disable file extension check', $this->plugin_slug); ?></label>
            <div class="h5p-warning"><?php _e("Warning! This may have security implications as it allows for uploading php files. That in turn could make it possible for attackers to execute malicious code on your site. Please make sure you know exactly what you're uploading.", $this->plugin_slug); ?></div>
          </div>
        <?php endif; ?>
        <?php wp_nonce_field('h5p_library', 'lets_upgrade_that'); ?>
      </div>
      <div class="h5p-button-holder">
        <input type="submit" name="submit" value="<?php esc_html_e('Upload', $this->plugin_slug) ?>" class="button button-primary button-large"/>
      </div>
    </div>
  </form>
  <h3 class="h5p-admin-header"><?php esc_html_e('Installed Libraries', $this->plugin_slug); ?></h3>
  <div id="h5p-admin-container"><?php esc_html_e('Waiting for JavaScript.', $this->plugin_slug); ?></div>
</div>
