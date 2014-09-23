<?php
/**
 * Upgrade library content.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2014 Joubel
 */
?>

<div class="wrap">
  <?php if ($library): ?>
    <h2><?php printf(__('Upgrade %s %d.%d.%d content', $this->plugin_slug), esc_html($library->title), $library->major_version, $library->minor_version, $library->patch_version); ?></h2>
  <?php endif; ?>
  <?php H5P_Plugin_Admin::print_messages(); ?>
  <?php if ($settings): ?>
    <div id="h5p-admin-container"><?php esc_html_e('Please enable JavaScript.', $this->plugin_slug); ?></div>
  <?php endif; ?>
</div>