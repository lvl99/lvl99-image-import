<?php
/*
 * LVL99 Image Import
 * @view Admin Help
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
    <?php /* <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/options-general.php?page=lvl99-image-import-options" class="nav-tab"><?php _ex('Options', 'options admin page tab', $textdomain); ?></a> */ ?>
    <a href="<?php echo trailingslashit(WP_SITEURL); ?>wp-admin/tools.php?page=lvl99-image-import&action=help" class="nav-tab nav-tab-active"><?php _ex('Help', 'help admin page tab', $textdomain); ?></a>
  </h2>

  <div class="lvl99-plugin-page">

    <h2>LVL99 Image Import <small>v<?php echo $lvl99_image_import->version; ?></small></h2>

    <div class="lvl99-plugin-intro">
      <p><b>LVL99 Image Import</b> is a WordPress plugin which allows you to easily import into the Media Library (or change) any images referenced within post content. This was developed to aid importing <a href="http://www.wordpress.com/" target="_blank">WordPress.com</a> hosted images into self-hosted WP sites for easy transition.</p>
      <p>Created and maintained by <a href="mailto:matt@lvl99.com?subject=LVL99+Image+Import">Matt Scheurich</a></p>
    </div>

    <div class="lvl99-plugin-section">
      <ul class="list-text">
        <li>Visit <a href="http://www.github.com/lvl99/lvl99-image-import" target="_blank">github.com/lvl99/lvl99-image-import</a> for news and updates</li>
        <li>Fork development of this plugin at <a href="http://github.com/lvl99/lvl99-image-import" target="_blank">github.com/lvl99/lvl99-image-import</a></li>
        <li>Consider supporting this free plugin's creation and development by donating via the methods below:<br/>
          <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_donations">
            <input type="hidden" name="business" value="matt.scheurich@gmail.com">
            <input type="hidden" name="lc" value="AU">
            <input type="hidden" name="item_name" value="Matt Scheurich">
            <input type="hidden" name="no_note" value="0">
            <input type="hidden" name="currency_code" value="USD">
            <input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest">
            <input type="image" src="https://www.paypalobjects.com/en_AU/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal — The safer, easier way to pay online.">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
          </form>
          <a href="https://flattr.com/submit/auto?user_id=lvl99&url=http%3A%2F%2Fwww.lvl99.com%2Fcode%2Fimage-import" target="_blank"><img src="//api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0"></a></li>
        </li>
      </ul>
    </div>

    <h3>Development and usage licence</h3>
    <pre>Copyright (C) 2015 Matt Scheurich (matt@lvl99.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
MA 02110-1301, USA.</pre>

  </div>

</div>