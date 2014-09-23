<?php
/**
 * Display library delete form
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2014 Joubel
 */
?>

<div class="wrap">
  <h2><?php print esc_html($library->title); ?></h2>
  <form method="post" enctype="multipart/form-data" id="h5p-library-form">
    <p><?php esc_html_e('Are you sure you wish to delete this H5P library?', $this->plugin_slug); ?></p>
    <?php wp_nonce_field('h5p_library', 'lets_delete_this'); ?>
    <input type="submit" name="submit" value="<?php esc_html_e('Do it!', $this->plugin_slug) ?>" class="button button-primary button-large"/>
  </form>
</div>

