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
  <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
  <?php $this->print_messages(); ?>
  <form method="post" enctype="multipart/form-data">
    <dl>
      <dt><label for="h5p-file">H5P File</label></dt>
      <dd><input type="file" name="h5p_file" id="h5p-file"></dd>
    </dl>
    <?php wp_nonce_field('h5p_upload_content', 'yes_sir_will_do'); ?>
    <input type="submit" name="submit" value="Upload">
  </form>
</div>
