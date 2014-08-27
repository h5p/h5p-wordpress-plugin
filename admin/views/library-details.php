<?php
/**
 * List library details.
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
  <?php print H5P_Plugin_Admin::print_messages(); ?>
  <div id="h5p-admin-container"></div>
</div>

