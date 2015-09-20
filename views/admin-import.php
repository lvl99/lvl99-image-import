<?php
/*
 * LVL99 Image Import
 * @view Admin Import
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;
$textdomain = $lvl99_image_import->get_textdomain();

$importtype = $lvl99_image_import->results['importtype'];
$filters = $lvl99_image_import->results['filters'];
$images = $lvl99_image_import->results['images_import'];
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

    <?php if ( !empty($images) && count($images) > 0 ) : ?>
    <form method="post">
      <input type="hidden" name="<?php echo esc_attr($textdomain); ?>" value="imported" />
      <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_importtype" value="<?php echo esc_attr($importtype); ?>" />

      <div class="lvl99-plugin-intro">
        <?php echo sprintf( __('Scanning found %d images references to %s within %d posts', $textdomain), count($images), ($importtype == 'medialibrary' ? __('import', $textdomain) : __('change', $textdomain)), count($posts_affected) ); ?>
      </div>

      <?php if ( count($filters) > 0 ) : ?>
      <div class="lvl99-image-import-filters-applied">
        <h4>Filters applied:</h4>
        <ul class="lvl99-image-import-filters-list">
          <?php foreach ( $filters as $filter ) : ?>
          <li class="lvl99-image-import-filter-applied lvl99-image-import-filter-applied-<?php echo esc_attr($filter['method']); ?>">
            <?php if ( $filter['method'] == 'include' ) : ?>
            <p><b>Include</b> images matching <code><?php echo $filter['input']; ?></code></p>
            <?php elseif ( $filter['method'] == 'exclude' ) : ?>
            <p><b>Exclude</b> images matching <code><?php echo $filter['input']; ?></code></p>
            <?php elseif ( $filter['method'] == 'replace' ) : ?>
            <p><b>Search</b> image URL for <code><?php echo $filter['input']; ?></code> and <b>replace</b> with <code><?php echo $filter['output']; ?></code></p>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <table class="widefat">
        <thead>
          <tr>
            <th class="lvl99-image-import-col-do"><input type="checkbox" name="<?php echo esc_attr($textdomain); ?>_selectall" value="true" checked="checked" /></th>
            <th class="lvl99-image-import-col-src">External image reference</th>
            <th class="lvl99-image-import-col-as">
              <?php if ( $importtype == 'medialibrary' ) : ?>
              Import as...
              <?php elseif ( $importtype == 'change' ) : ?>
              Change to...
              <?php endif; ?>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach( $images as $hash => $image ) : ?>
          <tr>
            <td class="lvl99-image-import-col-do"><input type="checkbox" name="<?php echo esc_attr($textdomain); ?>_images[<?php echo esc_attr($hash); ?>][do]" value="true" checked="checked" /></td>
            <td class="lvl99-image-import-col-src">
              <a href="<?php echo esc_url($image['src']); ?>" target="_blank"><?php echo $image['src']; ?></a>
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_images[<?php echo esc_attr($hash); ?>][src]" value="<?php echo esc_attr($image['src']); ?>" />
            </td>
            <td class="lvl99-image-import-col-as">
              <input type="text" name="<?php echo esc_attr($textdomain); ?>_images[<?php echo esc_attr($hash); ?>][as]" value="<?php echo esc_attr($image['as']); ?>" />
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_images[posts]" value="<?php echo esc_attr(implode(',', $image['posts'])); ?>" />
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="lvl99-plugin-option">
        <?php if ( $importtype == 'medialibrary' ) : ?>
        <button class="button button-primary">Import <?php echo count($images); ?> images to the Media Library and change image references within <?php echo count($posts_affected); ?> posts</button>
        <?php elseif ( $importtype == 'change' ) : ?>
        <button class="button button-primary">Change <?php echo count($images); ?> image references within <?php echo count($posts_affected); ?> posts</button>
        <?php endif; ?>
      </div>

    </form>
    <?php else : ?>
    <p>Something didn't work correctly...</p>
    <pre>
    <?php var_dump( $lvl99_image_import->results ); ?>
    </pre>
    <?php endif; ?>
  </div>

</div>