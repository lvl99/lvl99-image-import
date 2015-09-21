<?php
/*
 * LVL99 Image Import
 * @view Scan
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;
$textdomain = $lvl99_image_import->get_textdomain();

$posttypes_all = get_post_types( array(
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

// Saved options to re-configure
$posted = NULL;
if ( !empty($lvl99_image_import->route['request']) )
{
  $posted = $lvl99_image_import->route['request']['post'];
}

$posttypes = isset($posted['posttypes']) ? $posted['posttypes'] : 'all';
$posttypes_selected = isset($posted['posttypes_selected']) ? $posted['posttypes_selected'] : $posttypes_all;
$filters = isset($posted['filters']) ? $posted['filters'] : array(
  array(
    'method' => 'exclude',
    'input' => home_url('/'),
    'output' => '',
    '_initial' => 1,
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
          <li class="lvl99-image-import-posttypes-all"><label><input type="radio" name="<?php echo $textdomain; ?>_posttypes" value="all"<?php if ( $posttypes == 'all' ) : ?> checked="checked"<?php endif; ?>/> <?php _ex('Scan all post types', 'field value: posttypes=all', $textdomain); ?></label></li>
          <?php if ( count($posttypes_all) > 0 ) : ?>
          <li  class="lvl99-image-import-posttypes-selected">
            <label><input type="radio" name="<?php echo $textdomain; ?>_posttypes" value="selected"<?php if ( $posttypes == 'selected' ) : ?>checked="checked"<?php endif; ?>/> <?php _ex( 'Scan selected post types', 'field value: posttypes=some', $textdomain ); ?></label>
            <ul class="lvl99-image-import-posttypes-list"
            <?php foreach( $posttypes_all as $posttype ) : ?>
              <li class="lvl99-image-import-posttypes-list-item"><label><input type="checkbox" name="<?php echo $textdomain; ?>_posttypes_selected[]" value="<?php echo esc_attr($posttype); ?>"<?php if ( in_array($posttype, $posttypes_selected) ) : ?> checked="checked"<?php endif; ?><?php if ( $posttypes == 'all' ) : ?> disabled="disabled"<?php endif; ?>/> <?php echo $posttype; ?></label></li>
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
          <?php foreach ( $filters as $num => $filter ) : ?>
          <div class="lvl99-image-import-filter-item">
            <div class="lvl99-image-import-filter-method">
              <?php if ( !empty($filter['_initial']) ) : ?>
              <?php if ( $filter['method'] == 'exclude' ) : ?>
              Exclude if matches...
              <?php elseif ( $filter['method'] == 'include' ) : ?>
              Include if matches...
              <?php elseif ( $filter['method'] == 'replace' ) : ?>
              Search &amp; Replace
              <?php endif; ?>
              <input type="hidden" name="lvl99-image-import_filters[<?php echo $num; ?>][method]" value="<?php echo esc_attr($filter['method']); ?>" />
              <input type="hidden" name="lvl99-image-import_filters[<?php echo $num; ?>][_initial]" value="1" />
              <?php else : ?>
              <select name="lvl99-image-import_filters[<?php echo $num; ?>][method]">
                <option value="exclude"<?php if ($filter['method'] == 'exclude') : ?> selected="selected"<?php endif; ?>>Exclude if matches...</option>
                <option value="include"<?php if ($filter['method'] == 'include') : ?> selected="selected"<?php endif; ?>>Include if matches...</option>
                <option value="replace"<?php if ($filter['method'] == 'replace') : ?> selected="selected"<?php endif; ?>>Search &amp; Replace</option>
              </select>
              <?php endif; ?>
            </div>
            <div class="lvl99-image-import-filter-input">
              <?php if ( !empty($filter['_initial']) ) : ?>
              <code><?php echo esc_attr(home_url('/')); ?></code>
              <input type="hidden" name="lvl99-image-import_filters[<?php echo $num; ?>][input]" value="<?php echo esc_attr(home_url('/')); ?>" />
              <?php else : ?>
              <input type="text" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $num; ?>][input]" value="<?php echo esc_attr($filter['input']); ?>" placeholder="Search for..." />
              <?php endif; ?>
            </div>
            <div class="lvl99-image-import-filter-output">
              <?php if ( $filter['method'] == 'replace' ) : ?>
              <input type="text" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $num; ?>][output]" value="<?php echo esc_attr($filter['output']); ?>" placeholder="Replace with empty string" />
              <?php endif; ?>
            </div>
            <div class="lvl99-import-image-filter-controls">
              <?php if ( !empty($filter['_initial']) ) : ?>
              <a href="javascript:void(0)" class="button button-secondary button-small" title="This is to avoid changing any existing local image references. It has a high probability of screwing up your WP database if you disable this filter. That said, you're welcome to remove this filter via developer tools and proceed (just make sure you back up your WP database first)." onclick="alert('This is to avoid changing any existing local image references. It has a high probability of screwing up your WP database if you disable this filter. That said, you\'re welcome to remove this filter via developer tools and proceed (just make sure you back up your WP database first).');">?</a>
              <?php else : ?>
              <a href="#remove-filter" class="button button-secondary button-small">Remove</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <p><a href="#add-filter" class="button button-secondary"><?php echo __( 'Add Filter', $textdomain ); ?></a></p>

        <div class="lvl99-plugin-option-help lvl99-plugin-option-help-after">
          <ul>
            <li><b>Include if matches...</b>: If value matches any URLs, includes the image into the collection of images to import</li>
            <li><b>Exclude if matches...</b>: If value matches any URLs, excludes the image from the collection of images to import.</li>
            <li><b>Search &amp; Replace</b>: Search for strings to then replace with another string. This filter can aid renaming files before importing to the Media Library and/or changing image references to reference other domains.</li>
          </ul>
        </div>
      </div>

      <div class="lvl99-plugin-option">
        <input type="submit" name="<?php echo esc_attr($textdomain); ?>_submit" value="<?php _ex('Scan posts for image references', 'scan admin page button-submit label', $textdomain); ?>" class="button button-primary" />
      </div>

    </form>
  </div>

</div>