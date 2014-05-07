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

<?php if (count($contents)): ?>
  <ul>
    <?php foreach ($contents as $i => $content): ?>
      <li>
        <a href="#" class="h5p-insert" data-id="<?php print $content->id ?>"><?php print esc_html($content->title === '' ? 'H5P ' . $content->id : $content->title); ?></a>
      </li>
    <?php endforeach; ?>
  </ul>
  <script type="text/javascript">
    jQuery(document).ready(function() {
      jQuery('.h5p-insert').click(function () {
        send_to_editor('[h5p id="' + jQuery(this).data('id') + '"]');
        tb_remove();
        return false;
      });
    });
  </script>
<?php else: ?>
  <p><?php esc_html_e('No H5P content available. You must upload or create new content.', $this->plugin_slug); ?></p>
<?php endif; ?>