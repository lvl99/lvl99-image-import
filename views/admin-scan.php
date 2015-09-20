<?php
/*
 * LVL99 Image Import
 * @view Scan
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;
$textdomain = $lvl99_image_import->get_textdomain();

$posttypes = get_post_types( array(
  'public' => true,
  '_builtin' => true,
), 'names', 'or' );

$scan_options = array(
  /*
   * importtype
   */
  'importtype' => array(
    'label' => _x('Scan &amp; Import behaviour', 'field label: importtype', $this->textdomain),
    'field_type' => 'radio',
    'values' => array(
      array(
        'label' => _x('Import images into the Media Library', 'field value label: importtype=medialibrary', $this->textdomain),
        'value' => 'medialibrary',
        'description' => 'Import image files into the Media Library and change the image references within posts to refer to the new images.'
      ),
      array(
        'label' => _x('Change image references', 'field value label: importtype=change', $this->textdomain),
        'value' => 'change',
        'description' => 'Will not import images, but it will change the image references to those you specify in the next screen.'
      ),
      // array(
      //   'label' => _x('Do nothing (test run before import)', 'field value label: importtype=test', $this->textdomain),
      //   'value' => 'test',
      // ),
    ),
    'default' => 'medialibrary',
    'sanitise_callback' => NULL,
    // 'help' => _x('<p>Changes the importing behaviour.</p>', 'field help: importtype', $this->textdomain),
  ),
);
?>

<div class="wrap">
  <h2><?php _e('Image Import', $textdomain); ?></h2>

  <?php $lvl99_image_import->admin_notices(); ?>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=scan" class="nav-tab nav-tab-active"><?php _ex('Scan &amp; Import', 'scan admin page tab', $textdomain); ?></a>
    <?php /* <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/options-general.php?page=lvl99-image-import-options" class="nav-tab"><?php _ex('Options', 'options admin page tab', $textdomain); ?></a> */ ?>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=help" class="nav-tab"><?php _ex('Help', 'help admin page tab', $textdomain); ?></a>
  </h2>

  <div class="lvl99-plugin-page">
    <div class="lvl99-plugin-intro"><?php _ex('Scan for all images referenced within your posts.', 'scan admin page description', $textdomain); ?></div>

    <form method="post">
      <input type="hidden" name="<?php echo $textdomain; ?>" value="scanned" />

      <div class="lvl99-plugin-option">
        <label for="<?php echo $textdomain; ?>_posttypes" class="lvl99-plugin-option-label"><?php _ex( 'Post types to scan', 'field label: posttypes', $textdomain ); ?></label>

        <ul class="lvl99-image-import-posttypes">
          <li class="lvl99-image-import-posttypes-all"><label><input type="radio" name="<?php echo $textdomain; ?>_posttypes" value="all" checked="checked"/> <?php _ex('Scan all post types', 'field value: posttypes=all', $textdomain); ?></label></li>
          <?php if ( count($posttypes) > 0 ) : ?>
          <li  class="lvl99-image-import-posttypes-selected">
            <label><input type="radio" name="<?php echo $textdomain; ?>_posttypes" value="selected" /> <?php _ex( 'Scan selected post types', 'field value: posttypes=some', $textdomain ); ?></label>
            <ul class="lvl99-image-import-posttypes-list"
            <?php foreach( $posttypes as $posttype ) : ?>
              <li class="lvl99-image-import-posttypes-list-item"><label><input type="checkbox" name="<?php echo $textdomain; ?>_posttypes_selected[]" value="<?php echo esc_attr($posttype); ?>" checked="checked" disabled="disabled" /> <?php echo $posttype; ?></label></li>
            <?php endforeach; ?>
            </ul>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <?php $lvl99_image_import->render_options($scan_options); ?>

      <div class="lvl99-plugin-option">
        <label for="<?php echo $textdomain; ?>_filters" class="lvl99-plugin-option-label"><?php _ex( 'Filters', 'field label: filters', $textdomain ); ?></label>

        <div class="lvl99-plugin-option-help">
          <p>Apply filters to include, exclude or change image references.</p>
          <p class="small">Plain text matches work, and if you're fancy you can also use <a href="http://www.regex101.com" target="_blank">PCRE regular expressions</a>.</p>
        </div>

        <div id="lvl99-image-import-filters">
          <?php // Default filter excludes locally hosted images ?>
          <div class="lvl99-image-import-filter-item">
            <div class="lvl99-image-import-filter-method">
              Exclude image if matches...
              <input type="hidden" name="lvl99-image-import_filters[0][method]" value="exclude" />
            </div>
            <div class="lvl99-image-import-filter-input">
              <code><?php echo esc_attr(home_url('/')); ?></code>
              <input type="hidden" name="lvl99-image-import_filters[0][input]" value="<?php echo esc_attr(home_url('/')); ?>" />
            </div>
            <div class="lvl99-image-import-filter-output"></div>
            <div class="lvl99-import-image-filter-controls"><a href="javascript:void(0)" class="button button-secondary button-small" title="This is to avoid changing any existing local image references. It has a high probability of screwing up your WP database if you disable this filter. That said, you're welcome to remove this filter via developer tools and proceed (just make sure you back up your WP database first)." onclick="alert('This is to avoid changing any existing local image references. It has a high probability of screwing up your WP database if you disable this filter. That said, you\'re welcome to remove this filter via developer tools and proceed (just make sure you back up your WP database first).');">?</a></div>
          </div>
        </div>
        <p><a href="#add-filter" class="button button-secondary"><?php echo __( 'Add Filter', $textdomain ); ?></a></p>

        <div class="lvl99-plugin-option-help lvl99-plugin-option-help-after">
          <ul>
            <li><b>Include image if matches...</b>: If value matches any URLs, includes the image into the collection of images to import</li>
            <li><b>Exclude image if matches...</b>: If value matches any URLs, excludes the image from the collection of images to import.</li>
            <li><b>Search &amp; Replace</b>: Search for strings to then replace with another string. This filter can aid renaming files before importing to the Media Library and/or changing image references to reference other domains.</li>
          </ul>
        </div>
      </div>

      <div class="lvl99-plugin-option">
        <input type="submit" name="lvl99_image_import_submit" value="<?php _ex('Scan posts for image references', 'scan admin page button-submit label', $textdomain); ?>" class="button button-primary" />
      </div>

    </form>
  </div>

</div>