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
  <h2><?php print esc_html($this->content['title']); ?><a href="<?php print add_query_arg(
      array(
        'page' => 'h5p_new',
        'id' => $this->content['id']
      ),
      wp_get_referer()) ?>" class="add-new-h2"><?php esc_html_e('Edit', $this->plugin_slug); ?></a></h2>
  <?php print $this->print_messages(); ?>
  <?php print $embed_code; ?>
</div>