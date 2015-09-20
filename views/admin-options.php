<?php
/*
 * LVL99 Image Import
 * @view Admin Options
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;
$textdomain = $lvl99_image_import->get_textdomain();
?>

<div class="wrap">
  <h2><?php _e('Image Import', $textdomain); ?></h2>

  <?php $lvl99_image_import->admin_notices(); ?>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=scan" class="nav-tab"><?php _ex('Scan &amp; Import', 'scan admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/options-general.php?page=lvl99-image-import-options" class="nav-tab nav-tab-active"><?php _ex('Options', 'options admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=help" class="nav-tab"><?php _ex('Help', 'help admin page tab', $textdomain); ?></a>
  </h2>

  <div class="lvl99-plugin-page">
    <form method="post" action="options.php">
      <?php settings_fields( $textdomain ); ?>
      <?php do_settings_sections( $textdomain ); ?>
      <?php $lvl99_image_import->render_options( $this->default_options ); ?>
      <?php submit_button(); ?>
    </form>
  </div>

</div>