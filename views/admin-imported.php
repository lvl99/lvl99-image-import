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
$images_errored = $lvl99_image_import->results['images_errored'];
$posts_affected = $lvl99_image_import->results['posts_affected'];
?>

<div class="wrap">
  <h2><?php _e('Image Import', $textdomain); ?></h2>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=scan" class="nav-tab"><?php _ex('Scan &amp; Import', 'import admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=extras" class="nav-tab"><?php _ex('Extras', 'extras admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/options-general.php?page=lvl99-image-import-options" class="nav-tab"><?php _ex('Options', 'options admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=help" class="nav-tab"><?php _ex('Help', 'help admin page tab', $textdomain); ?></a>
  </h2>

  <div class="lvl99-plugin-page">

    <?php if ( !empty($images_imported) && count($images_imported) > 0 ) : ?>
    <div class="lvl99-plugin-intro">
      <?php if ( $importtype == 'medialibrary' ) : ?>
      <?php echo sprintf( __('Imported %d images into the Media Library and changed image references in %d posts', $textdomain), count($images_imported), count($posts_affected) ); ?>
      <?php elseif ( $importtype == 'change' ) : ?>
      <?php echo sprintf( __('Changed %d image references in %d posts', $textdomain), count($images_imported), count($posts_affected) ); ?>
      <?php endif; ?>
    </div>

    <ul class="lvl99-image-import-actions-list lvl99-image-import-imported-list">
      <?php foreach( $images_imported as $num => $image ) : ?>
      <li class="lvl99-image-import-action-item">
        <dl>
          <?php if ( $importtype == 'medialibrary' ) : ?>
          <dt>Imported</dt>
          <dd><code><?php echo $image['src']; ?></code></dd>
          <dt>to</dt>
          <dd><code><?php echo (isset($image['dl_as']) ? $image['dl_as'] : $image['as']); ?></code></dd>
          <?php endif; ?>
          <dt>Changed image reference</dt>
          <dd><code><?php echo $image['src']; ?></code></dd>
          <dt>to</dt>
          <dd><code><?php echo $image['as']; ?></code></dd>
          <dt>Within <?php echo count($image['posts']); ?> posts</dt>
          <dd><code><?php echo implode(',', $image['posts']); ?></code></dd>
          <?php if ( isset($image['process_time']) ) : ?>
          <dt>Time taken</dt>
          <dd><code><?php echo $image['process_time']; ?> seconds</code></dd>
        <?php endif; ?>
        </dl>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php else : ?>
    <p>Something didn't work out correctly...</p>
    <pre>
    <?php var_dump( $lvl99_image_import->results ); ?>
    </pre>
    <?php endif; ?>

    <?php if ( count($images_errored) > 0 ) : ?>
    <div class="lvl99-plugin-intro">
      ... however <?php echo count($images_errored); ?> failed to <?php echo ( $importtype == 'medialibrary' ? 'import' : 'change' ); ?>.
    </div>
    <ul class="lvl99-image-import-actions-list lvl99-image-import-errored-list">
      <?php foreach( $images_errored as $num => $image ) : ?>
      <li class="lvl99-image-import-action-item">
        <dl>
          <dt>Error</dt>
          <dd><code><?php echo $image['status']; ?></code></dd>
          <?php if ( array_key_exists('image', $image) ) : ?>
          <dt>Image</dt>
          <dd><code style="display: block"><?php var_dump($image); ?></code></dd>
          <?php else : ?>
          <?php if ( $importtype == 'medialibrary' ) : ?>
          <dt>Importing to</dt>
          <dd><code><?php echo (isset($image['dl_as']) ? $image['dl_as'] : $image['as']); ?></code></dd>
          <?php endif; ?>
          <dt>Changing image reference</dt>
          <dd><code><?php echo $image['src']; ?></code></dd>
          <dt>to</dt>
          <dd><code><?php echo $image['as']; ?></code></dd>
          <?php endif; ?>
          <?php if ( isset($image['process_time']) ) : ?>
          <dt>Time taken</dt>
          <dd><code><?php echo $image['process_time']; ?> seconds</code></dd>
          <?php endif; ?>
        </dl>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

  </div>

</div>