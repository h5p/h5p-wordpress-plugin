<?php
/**
 * Add new H5P Content.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2014 Joubel
 */
?>

<div class="wrap">
  <h2>
    <?php if ($this->content === NULL || is_string($this->content)): ?>
      <?php print esc_html(get_admin_page_title()); ?>
    <?php else: ?>
      <?php esc_html_e('Edit', $this->plugin_slug); ?> <em><?php print esc_html($this->content['title']); ?></em>
      <a href="<?php print admin_url('admin.php?page=h5p&task=show&id=' . $this->content['id']); ?>" class="add-new-h2"><?php _e('View', $this->plugin_slug); ?></a>
      <?php if ($this->current_user_can_view_content_results($this->content)): ?>
        <a href="<?php print admin_url('admin.php?page=h5p&task=results&id=' . $this->content['id']); ?>" class="add-new-h2"><?php _e('Results', $this->plugin_slug); ?></a>
      <?php endif;?>
    <?php endif; ?>
  </h2>
  <?php H5P_Plugin_Admin::print_messages(); ?>
  <?php if (!$contentExists || $this->current_user_can_edit($this->content)): ?>
    <form method="post" enctype="multipart/form-data" id="h5p-content-form">
      <div id="post-body-content">
        <div class="h5p-upload">
          <input type="file" name="h5p_file" id="h5p-file"/>
          <?php if (current_user_can('disable_h5p_security')): ?>
            <div class="h5p-disable-file-check">
              <label><input type="checkbox" name="h5p_disable_file_check" id="h5p-disable-file-check"/> <?php _e('Disable file extension check', $this->plugin_slug); ?></label>
              <div class="h5p-warning"><?php _e("Warning! This may have security implications as it allows for uploading php files. That in turn could make it possible for attackers to execute malicious code on your site. Please make sure you know exactly what you're uploading.", $this->plugin_slug); ?></div>
            </div>
          <?php endif; ?>
        </div>
        <div class="h5p-create"><div class="h5p-editor"><?php esc_html_e('Waiting for javascript...', $this->plugin_slug); ?></div></div>
        <?php  if ($examplesHint): ?>
          <div class="no-content-types-hint">
            <p><?php printf(wp_kses(__('It looks like there are no content types installed. You can get the ones you want by using the small \'Download\' button in the lower left corner on any example from the <a href="%s" target="_blank">Examples and Downloads</a> page and then you upload the file here.', $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), esc_url('https://h5p.org/content-types-and-applications')); ?></p>
            <p><?php printf(wp_kses(__('If you need any help you can always file a <a href="%s" target="_blank">Support Request</a>, check out our <a href="%s" target="_blank">Forum</a> or join the conversation in the <a href="%s" target="_blank">H5P Community Chat</a>.', $this->plugin_slug), array('a' => array('href' => array(), 'target' => array()))), esc_url('https://wordpress.org/support/plugin/h5p'), esc_url('https://h5p.org/forum'), esc_url('https://gitter.im/h5p/CommunityChat')); ?></p>
          </div>
        <?php endif ?>
      </div>
      <div class="postbox h5p-sidebar">
        <h2><?php esc_html_e('Actions', $this->plugin_slug); ?></h2>
        <div id="minor-publishing" <?php if (get_option('h5p_hub_is_enabled', TRUE)) : print 'style="display:none"'; endif; ?>>
          <label><input type="radio" name="action" value="upload"<?php if ($upload): print ' checked="checked"'; endif; ?>/><?php esc_html_e('Upload', $this->plugin_slug); ?></label>
          <label><input type="radio" name="action" value="create"/><?php esc_html_e('Create', $this->plugin_slug); ?></label>
          <input type="hidden" name="library" value="<?php print esc_attr($library); ?>"/>
          <input type="hidden" name="parameters" value="<?php print $parameters; ?>"/>
          <?php wp_nonce_field('h5p_content', 'yes_sir_will_do'); ?>
        </div>
        <div id="major-publishing-actions" class="submitbox">
          <?php if ($this->content !== NULL && !is_string($this->content)): ?>
            <a class="submitdelete deletion" href="<?php print wp_nonce_url(admin_url('admin.php?page=h5p_new&id=' . $this->content['id']), 'deleting_h5p_content', 'delete'); ?>"><?php esc_html_e('Delete') ?></a>
          <?php endif; ?>
          <input type="submit" name="submit-button" value="<?php $this->content === NULL ? esc_html_e('Create', $this->plugin_slug) : esc_html_e('Update')?>" class="button button-primary button-large"/>
        </div>
      </div>
      <?php if (isset($display_options['frame'])): ?>
        <div class="postbox h5p-sidebar">
          <div role="button" class="h5p-toggle" tabindex="0" aria-expanded="true" aria-label="<?php esc_html_e('Toggle panel', $this->plugin_slug); ?>"></div>
          <h2><?php esc_html_e('Display Options', $this->plugin_slug); ?></h2>
          <div class="h5p-action-bar-settings h5p-panel">
            <label>
              <input name="frame" type="checkbox" class="h5p-visibility-toggler" data-h5p-visibility-subject-selector=".h5p-action-bar-buttons-settings" value="true"<?php if ($display_options[H5PCore::DISPLAY_OPTION_FRAME]): ?> checked="checked"<?php endif; ?>/>
              <?php _e("Display toolbar below content", $this->plugin_slug); ?>
            </label>
            <?php if (isset($display_options[H5PCore::DISPLAY_OPTION_DOWNLOAD]) || isset($display_options[H5PCore::DISPLAY_OPTION_EMBED]) || isset($display_options[H5PCore::DISPLAY_OPTION_COPYRIGHT])) : ?>
              <div class="h5p-action-bar-buttons-settings">
                <?php if (isset($display_options[H5PCore::DISPLAY_OPTION_DOWNLOAD])): ?>
                  <label title="<?php _e("If checked a reuse button will always be displayed for this content and allow users to download the content as an .h5p file", $this->plugin_slug); ?>">
                    <input name="download" type="checkbox" value="true"<?php if ($display_options[H5PCore::DISPLAY_OPTION_DOWNLOAD]): ?> checked="checked"<?php endif; ?>/>
                    <?php _e("Allow users to download the content", $this->plugin_slug); ?>
                  </label>
                <?php endif; ?>
                <?php if (isset($display_options[H5PCore::DISPLAY_OPTION_EMBED])): ?>
                  <label>
                    <input name="embed" type="checkbox" value="true"<?php if ($display_options[H5PCore::DISPLAY_OPTION_EMBED]): ?> checked="checked"<?php endif; ?>/>
                    <?php _e("Display Embed button", $this->plugin_slug); ?>
                  </label>
                <?php endif; ?>
                <?php if (isset($display_options[H5PCore::DISPLAY_OPTION_COPYRIGHT])): ?>
                  <label>
                    <input name="copyright" type="checkbox" value="true"<?php if ($display_options[H5PCore::DISPLAY_OPTION_COPYRIGHT]): ?> checked="checked"<?php endif; ?>/>
                    <?php _e("Display Copyright button", $this->plugin_slug); ?>
                  </label>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
      <div class="postbox h5p-sidebar">
        <div role="button" class="h5p-toggle" tabindex="0" aria-expanded="true" aria-label="<?php esc_html_e('Toggle panel', $this->plugin_slug); ?>"></div>
        <h2><?php esc_html_e('Tags', $this->plugin_slug); ?></h2>
        <div class="h5p-panel">
          <textarea rows="2" name="tags" class="h5p-tags"><?php if ($contentExists): print esc_html($this->content['tags']); endif; ?></textarea>
          <p class="howto"><?php esc_html_e('Separate tags with commas', $this->plugin_slug); ?></p>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>
