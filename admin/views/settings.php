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
<div class="wrap h5p-settings-container">
  <?php \H5P_Plugin_Admin::print_messages(); ?>
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
          <th scope="row"><?php _e("Toolbar Below Content", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="frame" class="h5p-visibility-toggler" data-h5p-visibility-subject-selector=".h5p-toolbar-option" type="checkbox" value="true"<?php if ($frame): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Controlled by author - on by default", $this->plugin_slug); ?>
            </label>
            <p class="h5p-setting-desc">
              <?php _e("By default, a toolbar with 4 buttons is displayed below each interactive content.", $this->plugin_slug); ?>
            </p>
          </td>
        </tr>
        <tr valign="top" class="h5p-toolbar-option">
          <th scope="row"><?php _e("Allow download", $this->plugin_slug); ?></th>
          <td>
            <select id="export-button" name="download">
              <option value="<?php echo H5PDisplayOptionBehaviour::NEVER_SHOW; ?>" <?php if ($download == H5PDisplayOptionBehaviour::NEVER_SHOW): ?>selected="selected"<?php endif; ?>>
                <?php _e("Never", $this->plugin_slug); ?>
              </option>
              <option value="<?php echo H5PDisplayOptionBehaviour::ALWAYS_SHOW; ?>" <?php if ($download == H5PDisplayOptionBehaviour::ALWAYS_SHOW): ?>selected="selected"<?php endif; ?>>
                <?php _e("Always", $this->plugin_slug); ?>
              </option>
              <option value="<?php echo H5PDisplayOptionBehaviour::CONTROLLED_BY_PERMISSIONS; ?>" <?php if ($download == H5PDisplayOptionBehaviour::CONTROLLED_BY_PERMISSIONS): ?>selected="selected"<?php endif; ?>>
                <?php _e("Only for editors", $this->plugin_slug); ?>
              </option>
              <option value="<?php echo H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON; ?>" <?php if ($download == H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON): ?>selected="selected"<?php endif; ?>>
                <?php _e("Controlled by author - on by default", $this->plugin_slug); ?>
              </option>
              <option value="<?php echo H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF; ?>" <?php if ($download == H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF): ?>selected="selected"<?php endif; ?>>
                <?php _e("Controlled by author - off by default", $this->plugin_slug); ?>
              </option>
            </select>
            <p class="h5p-setting-desc">
              <?php _e("Setting this to 'Never' will reduce the amount of disk space required for interactive content.", $this->plugin_slug); ?>
            </p>
          </td>
        </tr>
        <tr valign="top" class="h5p-toolbar-option">
          <th scope="row"><?php _e("Display Embed button", $this->plugin_slug); ?></th>
          <td>
            <select id="embed-button" name="embed">
              <option value="<?php echo H5PDisplayOptionBehaviour::NEVER_SHOW; ?>" <?php if ($embed == H5PDisplayOptionBehaviour::NEVER_SHOW): ?>selected="selected"<?php endif; ?>>
                <?php _e("Never", $this->plugin_slug); ?>
              </option>
              <option value="<?php echo H5PDisplayOptionBehaviour::ALWAYS_SHOW; ?>" <?php if ($embed == H5PDisplayOptionBehaviour::ALWAYS_SHOW): ?>selected="selected"<?php endif; ?>>
                <?php _e("Always", $this->plugin_slug); ?>
              </option>
              <option value="<?php echo H5PDisplayOptionBehaviour::CONTROLLED_BY_PERMISSIONS; ?>" <?php if ($embed == H5PDisplayOptionBehaviour::CONTROLLED_BY_PERMISSIONS): ?>selected="selected"<?php endif; ?>>
                <?php _e("Only for editors", $this->plugin_slug); ?>
              </option>
              <option value="<?php echo H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON; ?>" <?php if ($embed == H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON): ?>selected="selected"<?php endif; ?>>
                <?php _e("Controlled by author - on by default", $this->plugin_slug); ?>
              </option>
              <option value="<?php echo H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF; ?>" <?php if ($embed == H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF): ?>selected="selected"<?php endif; ?>>
                <?php _e("Controlled by author - off by default", $this->plugin_slug); ?>
              </option>
            </select>
            <p class="h5p-setting-desc">
              <?php _e("Setting this to 'Never' will disable already existing embed codes.", $this->plugin_slug); ?>
            </p>
          </td>
        </tr>
        <tr valign="top" class="h5p-toolbar-option">
          <th scope="row"><?php _e("Display Copyright button", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="copyright" type="checkbox" value="true"<?php if ($copyright): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Controlled by author - on by default", $this->plugin_slug); ?>
            </label>
          </td>
        </tr>
        <tr valign="top" class="h5p-toolbar-option">
          <th scope="row"><?php _e("Display About H5P button", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="about" type="checkbox" value="true"<?php if ($about): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Always", $this->plugin_slug); ?>
            </label>
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
          <th scope="row"><?php _e("Save Content State", $this->plugin_slug); ?></th>
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
        </tr>
        <tr valign="top">
        <th scope="row"><?php _e("Show toggle switch for others' H5P contents", $this->plugin_slug); ?></th>
        <td>
        <select id="show_toggle_view_others_h5p_contents" name="show_toggle_view_others_h5p_contents">
          <option value="<?php echo H5PDisplayOptionBehaviour::NEVER_SHOW; ?>" <?php if ($show_toggle_view_others_h5p_contents == H5PDisplayOptionBehaviour::NEVER_SHOW): ?>selected="selected"<?php endif; ?>>
            <?php _e("No", $this->plugin_slug); ?>
          </option>
          <option value="<?php echo H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF; ?>" <?php if ($show_toggle_view_others_h5p_contents == H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_OFF): ?>selected="selected"<?php endif; ?>>
            <?php _e("Yes, show all contents by default", $this->plugin_slug); ?>
          </option>
          <option value="<?php echo H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON; ?>" <?php if ($show_toggle_view_others_h5p_contents == H5PDisplayOptionBehaviour::CONTROLLED_BY_AUTHOR_DEFAULT_ON): ?>selected="selected"<?php endif; ?>>
            <?php _e("Yes, show only current user's contents by default", $this->plugin_slug); ?>
          </option>
        </select>
        <p class="h5p-setting-desc">
          <?php _e("Allow to restrict the view of H5P contents to the current user's content. The setting has no effect if the user is not allowed to see other users' content.", $this->plugin_slug); ?>
        </p>
        </td>
        <tr valign="top">
          <th scope="row"><?php _e("Add Content Method", $this->plugin_slug); ?></th>
          <td class="h5p-action-bar-settings">
            <div>
              <?php _e('When adding H5P content to posts and pages using the "Add H5P" button:', $this->plugin_slug); ?>
            </div>
            <div>
              <label>
                <input type="radio" name="insert_method" value="id"
                  <?php if ($insert_method == "id"): ?>checked="checked"<?php endif; ?>
                />
                <?php _e("Reference content by id", $this->plugin_slug); ?>
              </label>
            </div>
            <div>
              <label>
                <input type="radio" name="insert_method" value="slug"
                  <?php if ($insert_method == "slug"): ?>checked="checked"<?php endif; ?>
                />
                <?php printf(wp_kses(__('Reference content by <a href="%s" target="_blank">slug</a>', $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), 'https://en.wikipedia.org/wiki/Semantic_URL#Slug'); ?>
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
            <label class="h5p-hub-setting">
              <input
                class="h5p-settings-disable-hub-checkbox"
                name="enable_hub"
                type="checkbox"
                value="true"
                <?php if ($enable_hub): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Use H5P Hub", $this->plugin_slug); ?>
            </label>
            <p class="h5p-setting-desc">
              <?php _e("It's strongly encouraged to keep this option <strong>enabled</strong>. The H5P Hub provides an easy interface for getting new content types and keeping existing content types up to date. In the future, it will also make it easier to share and reuse content. If this option is disabled you'll have to install and update content types through file upload forms.", $this->plugin_slug); ?>
            </p>
          </td>
        </tr>
<!--        <tr valign="top">-->
<!--          <th scope="row">--><?php //_e("Site Key", $this->plugin_slug); ?><!--</th>-->
<!--          <td>-->
<!--            <input id="h5p-site-key" name="site_key" type="text" maxlength="36" data-value="--><?php //print $site_key ?><!--" placeholder="--><?php //print ($site_key ? '********-****-****-****-************' : __('Empty', $this->plugin_slug)) ?><!--"/>-->
<!--            <button type="button" class="h5p-reveal-value" data-control="h5p-site-key" data-hide="--><?php //_e("Hide", $this->plugin_slug); ?><!--">--><?php //_e("Reveal", $this->plugin_slug); ?><!--</button>-->
<!--            <p class="h5p-setting-desc">-->
<!--              --><?php //_e("The site key is a secret used to uniquely identifies the site with the Hub.", $this->plugin_slug); ?>
<!--            </p>-->
<!--          </td>-->
<!--        </tr>-->
        <tr valign="top">
          <th scope="row"><?php _e("Usage Statistics", $this->plugin_slug); ?></th>
          <td>
            <label>
              <input name="send_usage_statistics" type="checkbox" value="true"<?php if ($send_usage_statistics): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Automatically contribute usage statistics", $this->plugin_slug); ?>
            </label>
            <p class="h5p-setting-desc">
              <?php printf(wp_kses(__("Usage statistics numbers will automatically be reported to help the developers better understand how H5P is used and to determine potential areas of improvement. Read more about which <a href=\"%s\" target=\"_blank\">data is collected on h5p.org</a>.", $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), 'https://h5p.org/tracking-the-usage-of-h5p'); ?>
            </p>
          </td>
        </tr>
      </tbody>
    </table>
    <?php wp_nonce_field('h5p_settings', 'save_these_settings'); ?>
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
  </form>
</div>
