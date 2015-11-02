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
        <p>When importing WordPress.com data, sometimes file links don't download properly and leave broken unfixable links in their wake (namely if your server doesn't support connecting to SSL, like when developing on a MAMP localhost server). Using this tool you can edit the broken links to link to external files to then be downloaded to the server and updated in the database.</p>
      </li>
      <li>
        <h3><a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=duplicates">Scan for duplicate file links</a></h3>
        <p>If WordPress Importer finds an attachment that already exists on your server it will append a <code>1</code> on the end of the new file name and import the file again. This means you may have multiple copies of the same file under different names on your server and in your database.</p>
        <div class="lvl99-plugin-notice lvl99-plugin-notice-warning">
          <p>Note:</b> This operation goes through all your attachments which have a <code><i>filename</i><b>1</b><i>.ext</i></code> and detects if an equivalent <code><i>filename.ext</i></code> exists on the server and in the database as well. If you have a lot of attachments, then expect it to take a while before you see anything!</p>
        </div>
      </li>
    </ul>

  </div>

</div>