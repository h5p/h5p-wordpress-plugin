<?php
/**
 * Select from all H5P content.
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
  <?php if ($save !== NULL): ?>
    <div id="setting-error-settings_updated" class="updated settings-error">
      <p><strong><?php esc_html_e('Settings saved.', $this->plugin_slug) ?></strong></p>
    </div>
  <?php endif; ?>
  <form method="post">
    <h3><?php esc_html_e('Action bar', $this->plugin_slug); ?></h3>
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">Export</th>
          <td>
            <input name="h5p_export" id="h5p-export" type="checkbox" value="true"<?php if ($export): ?> checked="checked"<?php endif; ?>/>
            <label for="h5p-export">Show a link to download the H5P below each content</label>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">H5P Icon</th>
          <td>
            <input name="h5p_icon" id="h5p-icon" type="checkbox" value="true"<?php if ($icon): ?> checked="checked"<?php endif; ?>/>
            <label for="h5p-icon">Show a H5P icon below each content</label>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">H5P User Tracking</th>
          <td>
            <input name="h5p_track_user" id="h5p-track-user" type="checkbox" value="true"<?php if ($track_user): ?> checked="checked"<?php endif; ?>/>
            <label for="h5p-track-user">Log results for signed in users</label>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Library updates", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="library_updates" type="checkbox" value="true"<?php if ($library_updates): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Fetch information about updates for your H5P content types", $this->plugin_slug); ?>
            </label>
          </td>
        </tr>
      </tbody>
    </table>
    <?php wp_nonce_field('h5p_settings', 'save_these_settings'); ?>
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
  </form>
</div>
