<?php
/*
 * LVL99 Image Import
 * @view Admin Index
 * @since 0.1.0
 */

if ( !defined('ABSPATH') ) exit('No direct access allowed');

global $lvl99_image_import;

// Router
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'scan';

// Options
if ( $action == 'scan' ) include( 'admin-scan.php' );
if ( $action == 'scanned' ) include( 'admin-import.php' );
if ( $action == 'imported' ) include( 'admin-imported.php' );
// if ( $action == 'options' ) include( 'admin-options.php' );
if ( $action == 'help' ) include( 'admin-help.php' );

?>