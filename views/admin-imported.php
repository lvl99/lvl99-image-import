<?php
/*
 * LVL99 Image Import
 * @view Admin Imported
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;
$textdomain = $lvl99_image_import->get_textdomain();

$importtype = $lvl99_image_import->results['importtype'];
$images_imported = $lvl99_image_import->results['images_imported'];
$posts_affected = $lvl99_image_import->results['posts_affected'];
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
    <div class="lvl99-plugin-intro">
      <?php if ( $importtype == 'medialibrary' ) : ?>
      <?php echo sprintf( __('Importing %d images into the Media Library...', $textdomain), count($images_imported) ); ?>
      <?php elseif ( $importtype == 'change' ) : ?>
      <?php echo sprintf( __('Changed %d image references in %d posts', $textdomain), count($images_imported), count($posts_affected) ); ?>
      <?php endif; ?>
    </div>

    <ul class="lvl99-import-image-imported-list">
      <?php if ( $importtype == 'change' ) : ?>
      <?php foreach( $images_imported as $num => $image ) : ?>
      <li class="lvl99-import-image-imported-item">
        Changed <i><?php echo $image['src']; ?></i> to <b><?php echo $image['as']; ?></b> (found in <?php echo count($image['posts']); ?> posts)
      </li>
      <?php endforeach; ?>
      <?php endif; ?>
    </ul>
    <?php else : ?>
    <p>Something didn't work correctly...</p>
    <pre>
    <?php var_dump( $lvl99_image_import->results ); ?>
    </pre>
    <?php endif; ?>
  </div>

</div>