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
  <form method="post" enctype="multipart/form-data" id="h5p-content-form">
    <dl>
      <dt><label for="title">Title</label></dt>
      <dd><input type="text" name="title" id="title"/></dd>
      <dt></dt>
      <dd>
        <input type="radio" name="action" value="upload"/><?php print __('Upload', $this->plugin_slug); ?><br>
        <input type="radio" name="action" value="create"/><?php print __('Create', $this->plugin_slug); ?>
      </dd>
      <dt class="h5p-upload"><label for="h5p-file">H5P File</label></dt>
      <dd class="h5p-upload"><input type="file" name="h5p_file" id="h5p-file"/></dd>
      <dt class="h5p-create"><?php print __('Content type', $this->plugin_slug); ?></dt>
      <dd class="h5p-create"><div class="h5p-editor"><?php print __('Waiting for javascript...', $this->plugin_slug); ?></div></dd>
    </dl>
    <input type="hidden" name="library" value="<?php print $library; ?>"/>
    <input type="hidden" name="parameters" value="<?php print $parameters; ?>"/>
    <?php wp_nonce_field('h5p_content', 'yes_sir_will_do'); ?>
    <input type="submit" name="submit" value="Go"/>
  </form>
</div>
