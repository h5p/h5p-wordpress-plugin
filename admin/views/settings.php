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
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row"><?php _e("Action bar", $this->plugin_slug); ?></th>
          <td class="h5p-action-bar-settings">
            <div>
              <label>
                <input name="h5p_frame" type="checkbox" value="true"<?php if ($frame): ?> checked="checked"<?php endif; ?>/>
                <?php _e("Display action bar and frame", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input name="h5p_download" type="checkbox" value="true"<?php if ($download): ?> checked="checked"<?php endif; ?>/>
                <?php _e("Download button", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input name="h5p_embed" type="checkbox" value="true"<?php if ($embed): ?> checked="checked"<?php endif; ?>/>
                <?php _e("Embed button", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input name="h5p_copyright" type="checkbox" value="true"<?php if ($copyright): ?> checked="checked"<?php endif; ?>/>
                <?php _e("Copyright button", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input name="h5p_about" type="checkbox" value="true"<?php if ($about): ?> checked="checked"<?php endif; ?>/>
                <?php _e("About H5P button", $this->plugin_slug); ?>
              </label>
            </div>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("User Tracking", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="h5p_track_user" type="checkbox" value="true"<?php if ($track_user): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Log results for signed in users", $this->plugin_slug); ?>
            </label>
          </td>
        </tr>
      </tbody>
    </table>
    <?php wp_nonce_field('h5p_settings', 'save_these_settings'); ?>
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
  </form>
  <script>
    (function ($) {
      $(document).ready(function () {
        var $inputs = $('.h5p-action-bar-settings input');
        var $frame = $inputs.filter('input[name="h5p_frame"]');
        var $others = $inputs.filter(':not(input[name="h5p_frame"])');

        var toggle = function () {
          if ($frame.is(':checked')) {
            $others.attr('disabled', false);
          }
          else {
            $others.attr('disabled', true);
          }
        };

        $frame.change(toggle);
        toggle();
      });
    })(jQuery);
  </script>
</div>
