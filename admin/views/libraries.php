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
  <h3 class="h5p-admin-header"><?php esc_html_e('Upload', $this->plugin_slug); ?></h3>
  <form method="post" enctype="multipart/form-data" id="h5p-library-form">
    <div class="postbox">
      <div id="minor-publishing">
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
      <div id="major-publishing-actions" class="submitbox">
        <input type="submit" name="submit" value="<?php esc_html_e('Upload', $this->plugin_slug) ?>" class="button button-primary button-large"/>
      </div>
    </div>
  </form>
  <h3 class="h5p-admin-header"><?php esc_html_e('Installed', $this->plugin_slug); ?></h3>
  <div id="h5p-admin-container"><?php esc_html_e('Waiting for JavaScript.', $this->plugin_slug); ?></div>
</div>
