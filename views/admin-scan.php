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

$upload_dir = wp_upload_dir();
$upload_path = $lvl99_image_import->get_relative_upload_path();

// Saved options to re-configure
$posted = NULL;
if ( !empty($lvl99_image_import->route['request']) )
{
  $posted = $lvl99_image_import->route['request']['post'];
}

$importtype = isset($posted['importtype']) ? $posted['importtype'] : 'medialibrary';
$removequerystrings = isset($posted['removequerystrings']) ? $lvl99_image_import->sanitise_option_boolean($posted['removequerystrings']) : FALSE;
$posttypes = isset($posted['posttypes']) ? $posted['posttypes'] : 'all';
$posttypes_selected = isset($posted['posttypes_selected']) ? $posted['posttypes_selected'] : $posttypes_all;
$uploaddir = isset($posted['uploaddir']) ? $posted['uploaddir'] : trailingslashit(preg_replace( '/^[\/\\\\]/', '', $upload_dir['subdir'] ));
$overwritefiles = isset($post['overwritefiles']) ? $lvl99_image_import->sanitise_option_boolean($posted['overwritefiles']) : FALSE;
$forceresize = isset($post['forceresize']) ? $lvl99_image_import->sanitise_option_boolean($posted['forceresize']) : $lvl99_image_import->get_option('forceresize');
$forceresizemax = isset($post['forceresizemax']) ? $posted['forceresizemax'] : $lvl99_image_import->get_option('forceresizemax');
$forceresizekeep = isset($post['forceresizekeep']) ? $lvl99_image_import->sanitise_option_boolean($posted['forceresizekeep']) : FALSE;
$maximgwidth = isset($post['maximgwidth']) ? $posted['maximgwidth'] : $lvl99_image_import->get_option('maximgwidth');
$maximgthumbnail = isset($post['maximgthumbnail']) ? $lvl99_image_import->sanitise_option_maximgthumbnail($posted['maximgthumbnail']) : $lvl99_image_import->get_option('maximgthumbnail');
$filters = isset($posted['filters']) ? $posted['filters'] : array(
  array(
    'method' => 'exclude',
    'input' => home_url('/'),
    'output' => '',
    '_initial' => 1,
  ),
);

// Options to render using plugin render_options function (saves time with pesky HTML!)
$scan_options = array(
  /*
   * (importtype)
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
    // 'help' => _x('Changes the importing behaviour.', 'field help: importtype', $this->textdomain),
  ),
);

$more_options = array(
  /*
   * Remove query strings
   */
  'removequerystrings' => array(
    'label' => _x('Remove query strings from image references', 'field label: removequerystrings', $this->textdomain),
    'field_type' => 'checkbox',
    'default' => TRUE,
    'help_after' => _x( sprintf('WordPress.com often uses query strings to dynamically size images to certain dimensions (e.g. <code>example.jpg?w=1024</code>, <code>example.jpg?w=1600</code>). By removing the query strings you can download the full-sized images. <b>Note:</b> Really large images won\'t be processed by WordPress unless you have at least <code>%s</code> of memory available.', WP_MAX_MEMORY_LIMIT), 'field help: removequerystrings', $this->textdomain),
  ),
);

// Media library options (if importtype=medialibrary then these are shown via JS)
$medialibrary_options = array(
  /*
   * Image import directory (uploaddir)
   */
  'uploaddir' => array(
    'label' => _x('Image import file directory', 'field label: uploaddir', $this->textdomain),
    'field_type' => 'text',
    'default' => $uploaddir,
    'input_before' => '<code>' . $upload_path . '</code>',
    'input_class' => ' ',
    'help_after' => 'Sub-folders will be created if they don\'t already exist. Sub-folders can also be assigned per image reference in the next screen, or via the <b>Search &amp; Replace</b> filter to automate renaming into month-day folders. It\'s recommended that if you are detecting and assigning sub-folders via <b>Search &amp; Replace</b> that you then make this value empty as any sub-folders assigned per image will be relative to this value.',
  ),
  /*
   * Force resize
   */
  'forceresize' => $lvl99_image_import->default_options['forceresize'],
  /*
   * Force resize keep
   */
  'forceresizekeep' => $lvl99_image_import->default_options['forceresizekeep'],
  /*
   * Force resize max image size
   */
  'forceresizemax' => $lvl99_image_import->default_options['forceresizemax'],
  /*
   * Maximum image width
   */
  'maximgwidth' => $lvl99_image_import->default_options['maximgwidth'],
  /*
   * Maximum image thumbnail
   */
  'maximgthumbnail' => $lvl99_image_import->default_options['maximgthumbnail'],
  /*
   * Overwrite image files
   */
  'overwritefiles' => array(
    'label' => _x('Overwrite existing image files', 'field label: overwritefiles', $this->textdomain),
    'field_type' => 'checkbox',
    'default' => FALSE,
    'help_after' => _x( 'If checked, overwrites files found on the server with newly downloaded versions. <b>Note:</b> If you are re-importing many files this can make the operation take longer. It\'s good to use if only re-importing a few.', 'field help: overwritefiles', $this->textdomain),
  ),
);

// Preset option values (if reconfiguring)
$scan_options['importtype']['default'] = $importtype;
$more_options['removequerystrings']['default'] = $removequerystrings;
$medialibrary_options['forceresize']['default'] = $forceresize;
$medialibrary_options['forceresizemax']['default'] = $forceresizemax;
$medialibrary_options['forceresizekeep']['default'] = $forceresizekeep;
$medialibrary_options['maximgwidth']['default'] = $maximgwidth;
$medialibrary_options['maximgthumbnail']['default'] = $maximgthumbnail;
?>

<div class="wrap">
  <h2><?php _e('Image Import', $textdomain); ?></h2>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=scan" class="nav-tab nav-tab-active"><?php _ex('Scan &amp; Import', 'scan admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=extras" class="nav-tab"><?php _ex('Extras', 'extras admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/options-general.php?page=lvl99-image-import-options" class="nav-tab"><?php _ex('Options', 'options admin page tab', $textdomain); ?></a>
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

      <div class="lvl99-plugin-option-group lvl99-image-import-medialibrary-options"<?php if ( $importtype == 'change' ) : ?> style="display: none"<?php endif; ?>>
        <?php $lvl99_image_import->render_options($medialibrary_options); ?>
      </div>

      <?php $lvl99_image_import->render_options($more_options); ?>

      <div class="lvl99-plugin-option">
        <label for="<?php echo $textdomain; ?>_filters" class="lvl99-plugin-option-label"><?php _ex( 'Filters', 'field label: filters', $textdomain ); ?></label>

        <div class="lvl99-plugin-option-help">
          <p>Apply filters to include, exclude or change image references.</p>
          <p class="small">Plain text matches work, and if you're fancy you can also use <a href="http://www.regex101.com" target="_blank">PCRE regular expressions</a>. <b>Note:</b> The order of filters will also affect whether an item is included or excluded (recommended to put excludes first, includes second and search &amp; replace filters last).</p>
        </div>

        <div class="lvl99-image-import-filters lvl99-sortable">
          <?php foreach ( $filters as $num => $filter ) : ?>
          <?php $rand = substr(md5($num), 0, 8); ?>
          <div class="lvl99-image-import-filter-item ui-draggable ui-sortable">
            <div class="lvl99-image-import-filter-method">
              <span class="fa-arrows-v lvl99-sortable-handle"></span>
              <?php if ( !empty($filter['_initial']) ) : ?>
              <?php if ( $filter['method'] == 'exclude' ) : ?>
              Exclude if matches...
              <?php elseif ( $filter['method'] == 'include' ) : ?>
              Include if matches...
              <?php elseif ( $filter['method'] == 'replace' ) : ?>
              Search &amp; Replace
              <?php endif; ?>
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $rand; ?>][method]" value="<?php echo esc_attr($filter['method']); ?>" />
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $rand; ?>][_initial]" value="1" />
              <?php else : ?>
              <select name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $rand; ?>][method]">
                <option value="exclude"<?php if ($filter['method'] == 'exclude') : ?> selected="selected"<?php endif; ?>>Exclude if matches...</option>
                <option value="include"<?php if ($filter['method'] == 'include') : ?> selected="selected"<?php endif; ?>>Include if matches...</option>
                <option value="replace"<?php if ($filter['method'] == 'replace') : ?> selected="selected"<?php endif; ?>>Search &amp; Replace</option>
              </select>
              <?php endif; ?>
            </div>
            <div class="lvl99-image-import-filter-input">
              <?php if ( !empty($filter['_initial']) ) : ?>
              <code><?php echo esc_attr(home_url('/')); ?></code>
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $rand; ?>][input]" value="<?php echo esc_attr(home_url('/')); ?>" />
              <?php else : ?>
              <input type="text" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $rand; ?>][input]" value="<?php echo esc_attr(stripslashes($filter['input'])); ?>" placeholder="Search for..." />
              <?php endif; ?>
            </div>
            <div class="lvl99-image-import-filter-output">
              <?php if ( $filter['method'] == 'replace' ) : ?>
              <input type="text" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $rand; ?>][output]" value="<?php echo esc_attr($filter['output']); ?>" placeholder="Replace with empty string" />
              <?php else : ?>
              <input type="hidden" name="<?php echo esc_attr($textdomain); ?>_filters[<?php echo $rand; ?>][output]" value="" />
              <?php endif; ?>
            </div>
            <div class="lvl99-import-image-filter-controls">
              <?php if ( !empty($filter['_initial']) ) : ?>
              <a href="javascript:void(0)" class="button button-secondary button-small" title="This is to avoid changing any existing local image references. It has a high probability of screwing up your WP database if you disable this filter. That said, you're welcome to remove this filter via developer tools and proceed (just make sure you back up your WP database first)." onclick="alert('This is to avoid changing any existing local image references. It has a high probability of screwing up your WP database if you disable this filter. That said, you\'re welcome to remove this filter via developer tools and proceed (just make sure you back up your WP database first).');">?</a> <a href="#remove-filter" class="button button-secondary button-small">Remove</a>
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
        <div style="text-align: right">
          <input type="submit" name="<?php echo esc_attr($textdomain); ?>_submit" value="<?php _ex('Scan posts for image references and apply filters', 'scan admin page button-submit label', $textdomain); ?>" class="button button-primary" />
        </div>
      </div>

    </form>
  </div>

</div>