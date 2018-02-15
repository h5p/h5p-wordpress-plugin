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
  <?php if ($hubOn): ?>
    <h3><?php esc_html_e('Content Type Cache', $this->plugin_slug); ?></h3>
    <form method="post" id="h5p-update-content-type-cache">
      <div class="h5p postbox">
        <div class="h5p-text-holder">
          <p><?php print esc_html_e('Making sure the content type cache is up to date will ensure that you can view, download and use the latest libraries. This is different from updating the libraries themselves.', $this->plugin_slug) ?></p>
          <table class="form-table">
            <tbody>
            <tr valign="top">
              <th scope="row"><?php _e("Last update", $this->plugin_slug); ?></th>
              <td>
                <?php
                if ($last_update !== '') {
                  echo date_i18n('l, F j, Y H:i:s', $last_update);
                }
                else {
                  echo 'never';
                }
                ?>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
        <div class="h5p-button-holder">
          <?php wp_nonce_field('h5p_sync', 'sync_hub'); ?>
          <input type="submit"
                 name="updatecache"
                 id="updatecache"
                 class="button button-primary button-large"
                 value=<?php esc_html_e('Update', $this->plugin_slug) ?>
          />
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
