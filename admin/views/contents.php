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
  <h2><?php print esc_html(get_admin_page_title()); ?><a href="<?php print admin_url('admin.php?page=h5p_new'); ?>" class="add-new-h2"><?php _e('Add new', $this->plugin_slug); ?></a></h2>
  <div id="h5p-contents">
    <?php _e('Waiting for JavaScript.', $this->plugin_slug); ?>
  </div>
</div>
