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
    <?php if ($content === NULL): ?>
      <?php print esc_html(get_admin_page_title()); ?>
    <?php else: ?>
      <?php print sprintf(__('Edit <em>%s</em>'), esc_html($title)); ?>
      <a href="<?php print add_query_arg(array('page' => 'h5p', 'task' => 'show', 'id' => $id), wp_get_referer()) ?>" class="add-new-h2">View</a></h2>
    <?php endif; ?>
  </h2>
  <?php $this->print_messages(); ?>
  <form method="post" enctype="multipart/form-data" id="h5p-content-form">
    <div id="post-body-content">
      <div id="titlediv">
        <label class="" id="title-prompt-text" for="title"><?php print __('Enter title here', $this->plugin_slug); ?></label>
        <input id="title" type="text" name="title" id="title" value="<?php print $title; ?>"/>
      </div>
      <div class="h5p-upload"><input type="file" name="h5p_file" id="h5p-file"/></div>
      <div class="h5p-create"><div class="h5p-editor"><?php print __('Waiting for javascript...', $this->plugin_slug); ?></div></div>
    </div>
    <div class="postbox">
      <div id="minor-publishing">
        <label><input type="radio" name="action" value="upload"/><?php print __('Upload', $this->plugin_slug); ?></label>
        <label><input type="radio" name="action" value="create"/><?php print __('Create', $this->plugin_slug); ?></label>
        <input type="hidden" name="library" value="<?php print $library; ?>"/>
        <input type="hidden" name="parameters" value="<?php print $parameters; ?>"/>
        <?php wp_nonce_field('h5p_content', 'yes_sir_will_do'); ?>
      </div>
      <div id="major-publishing-actions" class="submitbox">
        <a class="submitdelete deletion" href="#">Delete</a>
        <input type="submit" name="submit" value="<?php print __($content === NULL ? 'Create' : 'Update') ?>" class="button button-primary button-large"/>
      </div>
    </div>
  </form>
</div>
