<?php
/*
 * LVL99 Image Import
 * @view Admin Extras
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
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=scan" class="nav-tab"><?php _ex('Scan &amp; Import', 'import admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=extras" class="nav-tab nav-tab-active"><?php _ex('Extras', 'extras admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/options-general.php?page=lvl99-image-import-options" class="nav-tab"><?php _ex('Options', 'options admin page tab', $textdomain); ?></a>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=help" class="nav-tab"><?php _ex('Help', 'help admin page tab', $textdomain); ?></a>
  </h2>

  <div class="lvl99-plugin-page">

    <div class="lvl99-plugin-intro">
      <?php echo __('Extra operations to manage files on your WordPress site.', $textdomain); ?>
    </div>

    <ul class="lvl99-image-import-extras-list">
      <li>
        <h3><a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=brokenlinks">Scan for broken or external file links</a></h3>
        <p>Scans all attachments in the database and detects if the file is there or not. When importing WordPress.com data, sometimes file links don't download properly and leave broken unfixable links in their wake.</p>
        <?php /* <p>If any external attachment links are found, they can also be downloaded to the server.</p> */ ?>
      </li>
    </ul>

  </div>

</div>