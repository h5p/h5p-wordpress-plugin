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
  <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
  <?php if (count($contents)): ?>
    <table class="wp-list-table widefat fixed h5ps" cellspacing="0">
      <thead>
        <tr>
          <th><?php print __('Title', $this->plugin_slug); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contents as $i => $content): ?>
          <tr class="<?php print ($i % 2 === 0 ? 'alternate' : '') ?>">
            <td>
              <a href="<?php print add_query_arg(
                  array(
                    'page' => 'h5p', 
                    'task' => 'show', 
                    'id' => $content->id
                  )); ?>"><?php print ($content->title === '' ? 'H5P ' . $content->id : $content->title); ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p><?php print __('No H5P content available. You must upload or create new content.', $this->plugin_slug); ?></p>
  <?php endif; ?>
</div>
