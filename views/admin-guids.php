<?php
/*
 * LVL99 Image Import
 * @view Admin GUID Filenames
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;

$textdomain = $lvl99_image_import->get_textdomain();
$items_scanned = $lvl99_image_import->results['items_scanned'];
$items_completed = $lvl99_image_import->results['items_completed'];
$items_errored = $lvl99_image_import->results['items_errored'];
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

    <div class="lvl99-plugin-intro">
      <?php echo __( sprintf('<p>Scanned %d GUIDs, corrected %d incorrect file name references (%d errored).</p>', count($items_scanned), count($items_completed), count($items_errored) ), $textdomain ); ?>
    </div>

    <?php if ( count($items_completed) > 0 ) : ?>
    <table class="widefat lvl99-image-import-table">
      <thead>
        <tr>
          <th class="lvl99-image-import-col-src">Incorrect file name</th>
          <th class="lvl99-image-import-col-as">Corrected to...</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $items_completed as $num => $item ) : ?>
        <tr>
          <td class="lvl99-image-import-col-src">
            <a href="<?php echo esc_url($item['old_guid']); ?>" target="_blank">
              <code><?php echo $item['guid_filename']; ?></code>
            </a>
          </td>
          <td class="lvl99-image-import-col-as">
            <a href="<?php echo esc_url($item['new_guid']); ?>" target="_blank">
              <code><?php echo $item['meta_filename']; ?></code>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php else : ?>
    <p>Cool! No incorrect GUID file name references were detected.</p>
    <?php endif; ?>

  </div>

</div>