<?php
/**
 * Show specific H5P Content.
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
    <?php print esc_html($this->content['title']); ?>
    <?php if ($this->current_user_can_view_content_results($this->content)): ?>
      <a href="<?php print admin_url('admin.php?page=h5p&task=results&id=' . $this->content['id']); ?>" class="add-new-h2"><?php _e('Results', $this->plugin_slug); ?></a>
    <?php endif; ?>
    <?php if ($this->current_user_can_edit($this->content)): ?>
      <a href="<?php print admin_url('admin.php?page=h5p_new&id=' . $this->content['id']); ?>" class="add-new-h2"><?php _e('Edit', $this->plugin_slug); ?></a>
    <?php endif; ?>
  </h2>
  <?php print H5P_Plugin_Admin::print_messages(); ?>
  <div class="h5p-wp-admin-wrapper">
    <?php print $embed_code; ?>
  </div>
</div>
