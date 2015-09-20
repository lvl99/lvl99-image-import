<?php
/*
 * LVL99 Image Import
 * @view Admin Imported
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;
$textdomain = $lvl99_image_import->get_textdomain();
$images_imported = !empty($lvl99_image_import->results['images_imported']) ? $lvl99_image_import->results['images_imported'] : array();
?>

<div class="wrap">
  <h2><?php _e('Image Import', $textdomain); ?></h2>

  <?php $lvl99_image_import->admin_notices(); ?>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=scan" class="nav-tab"><?php _ex('Scan &amp; Import', 'import admin page tab', $textdomain); ?></a>
    <?php /* <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/options-general.php?page=lvl99-image-import-options" class="nav-tab"><?php _ex('Options', 'options admin page tab', $textdomain); ?></a> */ ?>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=help" class="nav-tab"><?php _ex('Help', 'help admin page tab', $textdomain); ?></a>
  </h2>

  <div class="lvl99-plugin-page">

    <?php if ( !empty($images_imported) && count($images_imported) > 0 ) : ?>
    <div class="lvl99-plugin-intro">Finished importing <?php echo count($images_imported); ?> images:</div>

    <div class="lvl99-import-image-imported">
      <?php foreach( $images_imported as $num => $image ) : ?>
      <div class="lvl99-import-image-imported-item">
        Imported <i><?php echo $image['src']; ?></i> as <b><?php echo $image['as']; ?></b>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else : ?>
    <p>Something didn't work correctly...</p>
    <pre>
    <?php var_dump( $lvl99_image_import->results ); ?>
    </pre>
    <?php endif; ?>
  </div>

</div>