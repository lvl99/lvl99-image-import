<?php
/*
 * LVL99 Image Import
 * @view Admin Broken Links
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;

$textdomain = $lvl99_image_import->get_textdomain();
$items_scanned = $lvl99_image_import->results['items_scanned'];
?>

<div class="wrap">
  <h2><?php _e('Image Import', $textdomain); ?></h2>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=scan" class="nav-tab"><?php _ex('Scan &amp; Import', 'import admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=extras" class="nav-tab nav-tab-active"><?php _ex('Extras', 'extras admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/options-general.php?page=lvl99-image-import-options" class="nav-tab"><?php _ex('Options', 'options admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=help" class="nav-tab"><?php _ex('Help', 'help admin page tab', $textdomain); ?></a>
  </h2>

  <div class="lvl99-plugin-page">

    <?php if ( count($items_scanned) > 0 ) : ?>
    <form method="post">
      <input type="hidden" name="lvl99-image-import" value="fixbrokenlinks" />

      <div class="lvl99-plugin-intro">
        <?php echo __( sprintf('<p>Found %d broken or external attachment links.</p><p class="small">Repair broken links by changing to a known link (local or external). External links will be downloaded to the local server.</p>', count($items_scanned) ), $textdomain ); ?>
      </div>

      <table class="widefat lvl99-image-import-table">
        <thead>
          <tr>
            <th class="lvl99-image-import-col-do"><input type="checkbox" name="<?php echo esc_attr($textdomain); ?>_selectall" value="true" checked="checked" /></th>
            <th class="lvl99-image-import-col-src">Broken link</th>
            <th class="lvl99-image-import-col-as">Update Link</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $items_scanned as $num => $item ) : ?>
          <tr>
            <td class="lvl99-image-import-col-do"><input type="checkbox" name="lvl99-image-import_items[<?php echo $num; ?>][do]" value="true" checked="checked" /></td>
            <td class="lvl99-image-import-col-src">
              <input type="hidden" name="lvl99-image-import_items[<?php echo $num; ?>][ID]" value="<?php echo esc_attr($item['ID']); ?>" />
              <input type="hidden" name="lvl99-image-import_items[<?php echo $num; ?>][src]" value="<?php echo esc_url($item['guid']); ?>" />
              <?php if ( $item['status'] == 'external' ) : ?>
              <a href="<?php echo esc_url($item['guid']); ?>" target="_blank">
                <code><?php echo $item['guid']; ?></code>
              </a>
              <?php else : ?>
              <code><?php echo $item['guid']; ?></code>
              <?php endif; ?>
            </td>
            <td class="lvl99-image-import-col-as">
              <?php if ( $item['status'] == 'broken' ) : ?>
              <input type="text" name="lvl99-image-import_items[<?php echo $num; ?>][as]" value="<?php echo esc_url($item['guid']); ?>" />
              <?php elseif ( $item['status'] == 'external' ) : ?>
              <i>External file to download</i>
              <input type="hidden" name="lvl99-image-import_items[<?php echo $num; ?>][as]" value="<?php echo esc_url($item['guid']); ?>" />
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <button class="button button-primary">Update attachment file links</button>

    </form>
    <?php else : ?>
    <p>Cool! No broken or external attachment links were detected.</p>
    <?php endif; ?>

  </div>

</div>