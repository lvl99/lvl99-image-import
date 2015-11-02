<?php
/*
 * LVL99 Image Import
 * @view Admin Duplicate attachments
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;

$textdomain = $lvl99_image_import->get_textdomain();
$items_scanned = $lvl99_image_import->results['items_scanned'];
$upload_dir = wp_upload_dir();
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
    <div class="lvl99-plugin-intro">
      <?php echo __( sprintf('<p>Found %d duplicate attachment files.</p>', count($items_scanned) ), $textdomain ); ?>
    </div>

    <form method="post">
      <input type="hidden" name="<?php echo esc_attr($textdomain); ?>" value="fixduplicates">
      <table class="widefat lvl99-image-import-table">
        <thead>
          <tr>
            <th class="lvl99-image-import-col-do"><input type="checkbox" name="<?php echo esc_attr($textdomain); ?>_selectall" value="true" checked="checked" /></th>
            <th class="lvl99-image-import-col-src">Duplicate file detected</th>
            <th class="lvl99-image-import-col-as">Original file</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $items_scanned as $num => $item ) : ?>
          <?php if ( $item['status'] == 'same_file' ) : ?>
          <tr>
            <td class="lvl99-image-import-col-do"><input type="checkbox" name="lvl99-image-import_items[$num][do]" value="true" checked="checked" /></td>
            <td class="lvl99-image-import-col-src">
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_items[$num][from_ID]" value="<?php echo esc_attr($item['ID']); ?>" />
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_items[$num][from_guid]" value="<?php echo esc_attr($item['guid']); ?>" />
              <a href="<?php echo esc_url($item['guid']); ?>" target="_blank">
                <code><?php echo str_replace( WP_CONTENT_URL, '', $item['guid'] ); ?></code>
              </a>
            </td>
            <td class="lvl99-image-import-col-as">
              <?php if ( isset($item['original']) ) : ?>
              <a href="<?php echo esc_url($item['original']['guid']); ?>" target="_blank">
                Attachment #<?php echo $item['original']['ID']; ?>: <code><?php echo str_replace( WP_CONTENT_URL, '', $item['original']['guid'] ); ?></code>
              </a>
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_items[$num][to_ID]" value="<?php echo esc_attr($item['original']['ID']); ?>" />
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_items[$num][to_guid]" value="<?php echo esc_attr($item['original']['guid']); ?>" />
              <?php else : ?>
              <i>No corresponding attachment found in the database</i>
              <?php endif; ?>
            </td>
          </tr>
          <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="lvl99-plugin-option">
        <button id="lvl99-image-import-submit" class="button button-submit button-primary">Remove <span class="item-count"><?php echo count($items_scanned); ?></span> duplicates and change all references to original files</button>
      </div>

    </form>

    <?php else : ?>
    <p>Cool! No duplicate attachments were detected.</p>
    <?php endif; ?>

  </div>

</div>