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
if ( $action == 'scan' )        include( 'admin-scan.php' );
if ( $action == 'scanned' )     include( 'admin-import.php' );
if ( $action == 'imported' )    include( 'admin-imported.php' );
if ( $action == 'extras' )      include( 'admin-extras.php' );
if ( $action == 'brokenlinks' ) include( 'admin-brokenlinks.php' );
if ( $action == 'duplicates' )  include( 'admin-duplicates.php' );
if ( $action == 'options' )     include( 'admin-options.php' );
if ( $action == 'help' )        include( 'admin-help.php' );

// Show debug
if ( $lvl99_image_import->get_option('show_debug') )
{
  echo '<pre>';
  if ( count($lvl99_image_import->results['debug']) > 0 )
  {
    foreach( $lvl99_image_import->results['debug'] as $debug )
    {
      echo "[".date('H:i:s', $debug['_time'])."] ";
      if ( !is_array($debug['_output']) && !is_object($debug['_output']) )
      {
        echo $debug['_output'];
      }
      else
      {
        echo '{ callee method: '.$debug['_callee_method'].' }' . "\n";
        echo "---\n";
        var_dump($debug['_output']);
        echo "---\n";
      }
      echo "\n";
    }
  }
  echo '</pre>';
}

?>