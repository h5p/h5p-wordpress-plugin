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
  <h2><?php print esc_html(get_admin_page_title()); ?><a href="<?php print admin_url('admin.php?page=h5p_new'); ?>" class="add-new-h2">Add new</a></h2>
  <?php if (count($contents)): ?>
    <table class="wp-list-table widefat fixed h5ps" cellspacing="0">
      <thead>
        <tr>
          <th><?php esc_html_e('Title', $this->plugin_slug); ?></th>
          <th class="h5p-created-at"><?php esc_html_e('Created', $this->plugin_slug); ?></th>
          <th class="h5p-updated-at"><?php esc_html_e('Last modified', $this->plugin_slug); ?></th>
          <th class="h5p-edit-link"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contents as $i => $content): ?>
          <tr class="<?php print ($i % 2 === 0 ? 'alternate' : '') ?>">
            <td>
              <a href="<?php print admin_url('admin.php?page=h5p&task=show&id=' . $content->id); ?>"><?php print ($content->title === '' ? 'H5P ' . $content->id : $content->title); ?></a>
            </td>
            <td class="h5p-created-at"><?php print date($datetimeformat, strtotime($content->created_at) + $offset); ?></td>
            <td class="h5p-updated-at"><?php print date($datetimeformat, strtotime($content->updated_at) + $offset); ?></td>
            <td class="h5p-edit-link"><a href="<?php print admin_url('admin.php?page=h5p_new&id=' . $content->id); ?>"><?php esc_html_e('Edit', $this->plugin_slug); ?></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p><?php esc_html_e('No H5P content available. You must upload or create new content.', $this->plugin_slug); ?></p>
  <?php endif; ?>
</div>
