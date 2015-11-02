<?php
/*
 * LVL99 Image Import
 * @view Admin Import
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import, $_wp_additional_image_sizes;
$textdomain = $lvl99_image_import->get_textdomain();

$posttypes = $lvl99_image_import->route['request']['post']['posttypes'];
$posttypes_selected = NULL;
if ( !empty($lvl99_image_import->route['request']['post']['posttypes_selected']) )
{
  $posttypes_selected = $lvl99_image_import->route['request']['post']['posttypes_selected'];
}

$importtype = $lvl99_image_import->results['importtype'];
$removequerystrings = $lvl99_image_import->sanitise_option_boolean($lvl99_image_import->results['removequerystrings']);
$overwritefiles = $lvl99_image_import->sanitise_option_boolean($lvl99_image_import->results['overwritefiles']);
$uploaddir = $lvl99_image_import->results['uploaddir'];
$upload_path = $lvl99_image_import->get_relative_upload_path($uploaddir);
$forceresize = $lvl99_image_import->sanitise_option_boolean($lvl99_image_import->results['forceresize']);
$forceresizemax = $lvl99_image_import->sanitise_option_number($lvl99_image_import->results['forceresizemax']);
$forceresizekeep = $lvl99_image_import->sanitise_option_boolean($lvl99_image_import->results['forceresizekeep']);
$maximgwidth = $lvl99_image_import->sanitise_option_number($lvl99_image_import->results['maximgwidth']);
$maximgthumbnail = $lvl99_image_import->sanitise_option_maximgthumbnail($lvl99_image_import->results['maximgthumbnail']);
$filters = $lvl99_image_import->results['filters'];
$images = $lvl99_image_import->results['images_import'];
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

    <?php if ( !empty($images) && count($images) > 0 ) : ?>
    <div class="lvl99-plugin-intro">
      <?php echo sprintf( __('Scanning found <b>%d image references</b> to %s within <b>%d posts</b>', $textdomain), count($images), ($importtype == 'medialibrary' ? __('import into the Media Library', $textdomain) : __('change', $textdomain)), count($posts_affected) ); ?>
    </div>

      <?php if ( count($filters) > 0 ) : ?>
      <form method="post">
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>" value="scan" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_posttypes" value="<?php echo esc_attr($posttypes); ?>" />
        <?php if ( !empty($posttypes_selected) && is_array($posttypes_selected) ) : ?>
        <?php foreach ( $posttypes_selected as $posttype ) : ?>
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_posttypes_selected[]" value="<?php echo esc_attr($posttype); ?>" />
        <?php endforeach; ?>
        <?php endif; ?>
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_importtype" value="<?php echo esc_attr($importtype); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_removequerystrings" value="<?php echo esc_attr($removequerystrings); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_uploaddir" value="<?php echo esc_attr($uploaddir); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_overwritefiles" value="<?php echo esc_attr($overwritefiles); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_forceresize" value="<?php echo esc_attr($forceresize); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_forceresizemax" value="<?php echo esc_attr($forceresizemax); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_forceresizekeep" value="<?php echo esc_attr($forceresizekeep); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_maximgwidth" value="<?php echo esc_attr($maximgwidth); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_maximgthumbnail" value="<?php echo esc_attr($maximgthumbnail); ?>" />
        <div class="lvl99-plugin-notice lvl99-image-import-config">
          <?php if ( $posttypes == 'all' ) : ?>
          <h4>Post types to scan: <code><?php echo $posttypes; ?></code>
          <?php elseif ( $posttypes == 'selected' ) : ?>
          <h4>Post types to scan: <code><?php echo implode( ', ', $posttypes_selected ); ?></code>
          <?php endif; ?>
          <?php if ( $importtype == 'medialibrary' ) : ?>
          <h4>Import image files to: <code><?php echo $upload_path; ?></code></h4>
          <?php if ( $maximgwidth > 0 || $forceresize ) : ?>
          <blockquote>
            <?php if ( $forceresize ) : ?>
            <p><b>Force resizing</b> is <code>enabled</code> for any images whose dimensions exceed <code><?php echo $forceresizemax; ?></code> pixels</p>
            <?php if ( $forceresizekeep ) : ?>
            <p>Original image files will be kept and resized image file names will be renamed to <code><i>filename</i><b>_resized</b><i>.ext</i></code></p>
            <?php endif; ?>
            <?php endif; ?>
            <?php if ( $maximgwidth > 0 ) : ?>
            <p><b>Maximum image thumbnail</b> to reference if imported image exceeds <code><?php echo $maximgwidth; ?></code> pixels width is <code><?php echo $maximgthumbnail; ?> (<?php echo $_wp_additional_image_sizes[$maximgthumbnail]['width']; ?> &times; <?php echo $_wp_additional_image_sizes[$maximgthumbnail]['height']; ?><?php if ( $_wp_additional_image_sizes[$maximgthumbnail]['crop'] ) : ?>, cropped<?php endif; ?>)</code></p>
            <?php endif; ?>
            <?php if ( $overwritefiles ) : ?>
            <p><b>Overwrite files</b> is <code>enabled</code>. Any files on the server that match found image references will be overwritten during the import process. <b>Use at your own risk! Don't forget to backup your <code>uploads</code> folder!</b></p>
            <?php else : ?>
            <p><b>Overwrite files</b> is <code>disabled</code>. This means that if the file already exists on your server within the upload path above, it will reference the existing file and not download the image.</p>
            <?php endif; ?>
          </blockquote>
          <?php endif; ?>
          <?php elseif ( $importtype == 'change' ) : ?>
          <p>Import type is set to <code>change</code>. <b>No images will be imported</b> as this mode will only change the image references as specified below.</p>
          <?php endif; ?>
          <h4>Filters applied:</h4>
          <ul class="lvl99-image-import-filters-list">
            <?php foreach ( $filters as $num => $filter ) : ?>
            <li class="lvl99-image-import-filter-applied lvl99-image-import-filter-applied-<?php echo esc_attr($filter['method']); ?>">
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $num; ?>][method]" value="<?php echo esc_attr($filter['method']); ?>" />
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $num; ?>][input]" value="<?php echo esc_attr($filter['input']); ?>" />
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $num; ?>][output]" value="<?php echo esc_attr(!empty($filter['output']) ? $filter['output'] : ''); ?>" />
              <?php if ( $filter['method'] == 'include' ) : ?>
              <b>Include</b> images matching <code><?php echo $filter['input']; ?></code>
              <?php elseif ( $filter['method'] == 'exclude' ) : ?>
              <b>Exclude</b> images matching <code><?php echo $filter['input']; ?></code>
              <?php elseif ( $filter['method'] == 'replace' ) : ?>
              <b>Search</b> image URL for <code><?php echo $filter['input']; ?></code> and <b>replace</b> with <code><?php echo (empty($filter['output']) ? '<i>empty string</i>' : $filter['output']); ?></code>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <button class="button button-secondary">Reconfigure scan and filter options</button>
        </div>
      </form>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>" value="imported" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_importtype" value="<?php echo esc_attr($importtype); ?>" />
        <?php if ( $importtype == 'medialibrary' ) : ?>
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_uploaddir" value="<?php echo esc_attr($uploaddir); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_overwritefiles" value="<?php echo esc_attr($overwritefiles); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_forceresize" value="<?php echo esc_attr($forceresize); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_forceresizemax" value="<?php echo esc_attr($forceresizemax); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_forceresizekeep" value="<?php echo esc_attr($forceresizekeep); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_maximgwidth" value="<?php echo esc_attr($maximgwidth); ?>" />
        <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_maximgthumbnail" value="<?php echo esc_attr($maximgthumbnail); ?>" />
        <?php endif; ?>
        <table class="lvl99-image-import-table widefat">
          <thead>
            <tr>
              <th class="lvl99-image-import-col-do"><input type="checkbox" name="<?php echo esc_attr($textdomain); ?>_selectall" value="true" checked="checked" /></th>
              <th class="lvl99-image-import-col-src">Image reference</th>
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
                <?php /* <a href="<?php echo esc_url($image['src']); ?>" target="_blank"><?php echo $image['src']; ?></a> */ ?>
                <input type="text" name="<?php echo esc_attr($textdomain); ?>_images[<?php echo esc_attr($hash); ?>][src]" value="<?php echo esc_attr($image['src']); ?>" />
              </td>
              <td class="lvl99-image-import-col-as">
                <?php if ( $importtype == 'medialibrary' ) : ?>
                <?php /* <code><?php echo $upload_path; ?></code> */ ?>
                <?php endif; ?>
                <input type="text" name="<?php echo esc_attr($textdomain); ?>_images[<?php echo esc_attr($hash); ?>][as]" value="<?php echo esc_attr($image['as']); ?>" />
                <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_images[<?php echo esc_attr($hash); ?>][posts]" value="<?php echo esc_attr(implode(',', $image['posts'])); ?>" />
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="lvl99-plugin-option">

          <div class="lvl99-plugin-notice lvl99-plugin-notice-warning">
            <p class="large"><b>Please note that this next operation is irreversable</b>.</p>
            <p>Make sure you have backed up your uploads folder and WordPress database before performing the <?php if ( $importtype == 'medialibrary' ) : ?>importing of files and changing the references<?php elseif ( $importtype == 'change' ) : ?>changing the references<?php endif; ?>.</p>
            <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=scan" class="button button-primary">Ok, I'll do that right now!</a>
          </div>

          <div style="text-align: right">
            <?php if ( $importtype == 'medialibrary' ) : ?>
            <button id="lvl99-image-import-submit" class="button button-secondary">Import <span><?php echo count($images); ?></span> images to the Media Library and change image references within related posts</button>
            <?php elseif ( $importtype == 'change' ) : ?>
            <button id="lvl99-image-import-submit" class="button button-secondary">Change <span><?php echo count($images); ?></span> image references within related posts</button>
            <?php endif; ?>
          </div>
        </div>

        <div class="lvl99-image-import-submitted" style="display:none">
          <div class="lvl99-plugin-notices">
            <div class="lvl99-plugin-notice">
              <p>Processing selected images now. Depending on the amount of images you are importing/changing, it may take a while to complete (estimated ~<b>30s</b> per image, ~<b class="time-total"></b> mins total).</p>
              <p><b><i>Don't close this window!</i></b></p>
              <div class="lvl99-image-import-progress">
                <div class="lvl99-image-import-progress-bar"></div>
              </div>
            </div>
            <?php $progress_log = $lvl99_image_import->progress_log_url(); ?>
            <?php if ( $progress_log ) : ?>
            <pre class="lvl99-image-import-progress-log" data-src="<?php echo esc_url($progress_log); ?>"></pre>
            <?php endif; ?>
          </div>
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