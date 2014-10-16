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
  <h2>
    <?php printf(__('Results for "%s"', $this->plugin_slug), esc_html($this->content['title'])); ?>
    <a href="<?php print admin_url('admin.php?page=h5p&task=show&id=' . $this->content['id']); ?>" class="add-new-h2"><?php _e('View', $this->plugin_slug); ?></a>
    <?php if ($this->current_user_can_edit($this->content)): ?>
      <a href="<?php print admin_url('admin.php?page=h5p_new&id=' . $this->content['id']); ?>" class="add-new-h2"><?php _e('Edit', $this->plugin_slug); ?></a>
    <?php endif; ?>
  </h2>
  <div id="h5p-content-results">
    <?php _e('Waiting for JavaScript.', $this->plugin_slug); ?>
  </div>
</div>
