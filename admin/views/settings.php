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
          <th scope="row"><?php _e("External communication", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="ext_communication" type="checkbox" value="true"<?php if ($ext_communication): ?> checked="checked"<?php endif; ?>/>
              <?php _e("I wish to aid in the development of H5P by contributing anonymous usage data", $this->plugin_slug); ?>
            </label>
            <p class="h5p-setting-desc">
              <?php _e("Disabling this option will prevent your site from fetching the newest H5P updates.", $this->plugin_slug); ?><br/>
              <?php _e("You will have to manually download the Content Type updates from H5P.org and then upload them to your site.", $this->plugin_slug); ?><br/>
              <?php printf(wp_kses(__('You can read more about <a href="%s" target="_blank">which data is collected</a> on h5p.org.', $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), 'https://h5p.org/tracking-the-usage-of-h5p'); ?>
            </p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Action bar", $this->plugin_slug); ?></th>
          <td class="h5p-action-bar-settings">
            <div>
              <label>
                <input name="frame" type="checkbox" value="true"<?php if ($frame): ?> checked="checked"<?php endif; ?>/>
                <?php _e("Display action bar and frame", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input name="download" type="checkbox" value="true"<?php if ($download): ?> checked="checked"<?php endif; ?>/>
                <?php _e("Download button", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input name="embed" type="checkbox" value="true"<?php if ($embed): ?> checked="checked"<?php endif; ?>/>
                <?php _e("Embed button", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input name="copyright" type="checkbox" value="true"<?php if ($copyright): ?> checked="checked"<?php endif; ?>/>
                <?php _e("Copyright button", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input name="about" type="checkbox" value="true"<?php if ($about): ?> checked="checked"<?php endif; ?>/>
                <?php _e("About H5P button", $this->plugin_slug); ?>
              </label>
            </div>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("User Results", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="track_user" type="checkbox" value="true"<?php if ($track_user): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Log results for signed in users", $this->plugin_slug); ?>
            </label>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Save content state", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="save_content_state" type="checkbox" value="true"<?php if ($save_content_state): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Allow logged-in users to resume tasks", $this->plugin_slug); ?>
            </label>
            <p class="h5p-auto-save-freq">
              <label for="h5p-freq"><?php _e("Auto-save frequency (in seconds)", $this->plugin_slug); ?></label>
              <input id="h5p-freq" name="save_content_frequency" type="text" value="<?php print $save_content_frequency ?>"/>
            </p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Add content method", $this->plugin_slug); ?></th>
          <td class="h5p-action-bar-settings">
            <div>
              <?php _e('When adding H5P content to posts and pages using the "Add H5P" button:', $this->plugin_slug); ?>
            </div>
            <div>
              <label>
                <input type="radio" name="insert_method" value="id"
                  <?php if ($insert_method == "id"): ?>checked="checked"<?php endif; ?>
                />
                <?php _e("Reference content by id", $this->plugin_slug); ?></th>
              </label>
            </div>
            <div>
              <label>
                <input type="radio" name="insert_method" value="slug"
                  <?php if ($insert_method == "slug"): ?>checked="checked"<?php endif; ?>
                />
                <?php printf(wp_kses(__('Reference content by <a href="%s" target="_blank">slug</a>', $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), 'https://en.wikipedia.org/wiki/Semantic_URL#Slug'); ?></th>
              </label>
            </div>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Content Types", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="enable_lrs_content_types" type="checkbox" value="true"<?php if ($enable_lrs_content_types): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Enable LRS dependent content types", $this->plugin_slug); ?>
            </label>
            <p class="h5p-setting-desc">
              <?php _e("Makes it possible to use content types that rely upon a Learning Record Store to function properly, like the Questionnaire content type.", $this->plugin_slug); ?>
            </p>
          </td>
        </tr>
      </tbody>
    </table>
    <?php wp_nonce_field('h5p_settings', 'save_these_settings'); ?>
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
  </form>
</div>
