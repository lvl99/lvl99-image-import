<?php
/*
Plugin Name: LVL99 Image Import
Plugin URI: http://www.github.com/lvl99/lvl99-image-import
Description: A means to import into the Media Library or change all images references within posts.
Author: Matt Scheurich
Author URI: http://www.lvl99.com/
Version: 0.1.0-alpha
Text Domain: lvl99-image-import
*/

/*
Copyright (C) 2015 Matt Scheurich (matt@lvl99.com)
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
MA 02110-1301, USA.
*/

if ( !defined('ABSPATH') ) exit( 'No direct access allowed' );

if ( !class_exists( 'LVL99_Image_Import' ) )
{
  /*
  @class LVL99_Image_Import
  */
  class LVL99_Image_Import
  {
    /*
    The version number of the plugin. Used to manage any API changes between versions.

    @property $version
    @since 0.1.0
    @public
    @type {String}
    */
    public $version = '0.1.0-alpha';

    /*
    The path to the plugin's directory.

    @property $plugin_dir
    @since 0.1.0
    @private
    @type {String}
    */
    private $plugin_dir;

    /*
    The default options. This is set in `load_options`.

    @property $default_options
    @since 0.1.0
    @protected
    @type {Array}
    */
    protected $default_options = array();

    /*
    Holds the options for the plugin

    @property $options
    @since 0.1.0
    @protected
    @type {Array}
    */
    protected $options = array();

    /*
    The text domain for i18n

    @property $textdomain
    @since 0.1.0
    @private
    @type {String}
    */
    private $textdomain = 'lvl99-image-import';

    /*
    The object with the route's information

    @property $route
    @since 0.1.0
    @protected
    @type {Array}
    */
    protected $route = array();

    /*
    The array holding all the notifications to output.

    @property $notices
    @since 0.1.0
    @type {Array}
    */
    public $notices = array();

    /*
    The results array holding info about what has been actioned upon.

    @property $results
    @since 0.1.0
    @type {Array}
    */
    public $results = array(
      'query' => '', // The SQL query used to fetch posts to scan
      'importtype' => NULL, // The type of importation method: `medialibrary` or `change`
      'uploaddir' => NULL, // The extra directory within the WP Upload path to download images to
      'upload_path' => NULL,
      'removequerystrings' => FALSE,
      'overwritefiles' => FALSE,
      'forceresize' => FALSE,
      'forceresizemax' => 0,
      'forceresizekeep' => FALSE,
      'max_memory' => 0,
      'maximgwidth' => 0,
      'maximgthumbnail' => 0,
      'filters' => array(), // Filters used in the results
      'posts_affected' => array(), // Array of posts that have image references
      'posts_excluded' => array(), // Array of posts that were excluded from updating
      'images_import' => array(), // Array of images to import
      'images_excluded' => array(), // Array of images excluded from import
      'images_errored' => array(), // Array of images errored during import
      'images_imported' => array(), // Array of images that were successfully imported
      'images_search' => array(), // The terms to search posts with
      'images_replace' => array(), // The equivalent items to replace found terms within posts
      'brokenlinks' => array(), // Broken links extra
      'items_errored' => array(), // Extra items errored
      'items_excluded' => array(), // Extra items excluded
      'items_completed' => array(), // Extra items completed
      'debug' => array(),
    );

    /*
    PHP magic method which runs when plugin's class is created (initiates a lot of initial filters and all)

    @method __construct
    @since 0.1.0
    @returns {Void}
    */
    public function __construct()
    {
      $this->plugin_dir = dirname(__FILE__);

      // Actions/filters
      register_activation_hook( __FILE__, array( $this, 'activate' ) );
      register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

      add_action( 'init', array( $this, 'i18n' ) );
      add_action( 'admin_init', array( $this, 'initialise' ) );
      add_action( 'admin_menu', array( $this, 'admin_menu' ) );
      add_action( 'wp_loaded', array( $this, 'detect_route' ), 99999 );
      add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );

      add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( &$this, 'admin_plugin_links' ) );
    }

    /*
    @method get_textdomain
    @since 0.1.0
    @description Gets the text domain string
    @returns {String}
    */
    public function get_textdomain()
    {
      return $this->textdomain;
    }

    /*
    Checks if the user is an admin and can perform the operation.

    @method check_admin
    @since 0.1.0
    @private
    @returns {Boolean}
    */
    private function check_admin()
    {
      if ( !is_admin() )
      {
        $callee = debug_backtrace();
        error_log( _x( sprintf('Error: Non-admin attempted operation %s', $callee[1]['function']), $this->textdomain), 'wp error_log' );
        wp_die( __( 'Error: You must have administrator privileges to operate this functionality', $this->textdomain) );
      }

      return TRUE;
    }

    /*
    Loads the plugin's text domain for translation purposes.

    @method i18n
    @since 0.1.0
    @returns {Void}
    */
    public function i18n()
    {
      load_plugin_textdomain( $this->textdomain, FALSE, basename( dirname(__FILE__) ) . '/languages' );
    }

    /*
    Runs when the plugin is activated.

    @method activate
    @since 0.1.0
    @returns {Void}
    */
    public function activate()
    {
      // Install the options
      $_plugin_installed = get_option( '_lvl99-image-import/installed', FALSE );
      $_plugin_version = get_option( '_lvl99-image-import/version', $this->version );
      if ( !$_plugin_installed )
      {
        // Set the initial options
        foreach ( $this->default_options as $name => $value )
        {
          add_option( 'lvl99-image-import/' . $name, $value );
        }
      }

      // Mark that the plugin is now installed
      update_option( '_lvl99-image-import/installed', TRUE );
      update_option( '_lvl99-image-import/version', $this->version );
    }

    /*
    Runs when the plugin is deactivated.

    @method deactivate
    @since 0.1.0
    @returns {Void}
    */
    public function deactivate()
    {
      $_plugin_installed = get_option( '_lvl99-image-import/installed', TRUE );
      $_plugin_version = get_option( '_lvl99-image-import/version', $this->version );

      if ( $_plugin_installed )
      {
        // Do anything after deactivation (depending on version)
        switch ($_plugin_version)
        {
          default:
            // Do nothing!
            break;

          case FALSE:
            break;

          // case '0.1.0':
          //  break;
        }
      }
    }

    /*
    Runs when the plugin is uninstalled/deleted.

    @method uninstall
    @since 0.1.0
    @param {String} $_plugin_version The version of the currently installed plugin
    @returns {Void}
    */
    public function uninstall( $_plugin_version = FALSE )
    {
      if ( !$_plugin_version ) $_plugin_version = get_option( '_lvl99-image-import/version', $this->version );

      // Do any particular operations based on which version is being uninstalled
      switch ($_plugin_version)
      {
        default:
          // Remove anything necessary during uninstall
          // break;

        case FALSE:
          break;

        // case '0.1.0':
        //  break;
      }
    }

    /*
    Runs when the plugin is initialised via WP.

    @method initialise
    @since 0.1.0
    @returns {Void}
    */
    public function initialise ()
    {
      $this->check_admin();

      // Load in the options (via DB or use defined defaults above)
      $this->load_options();
    }

    /*
    Enqueue the admin scripts for the plugin (only if viewing page related to plugin)

    @method admin_enqueue_scripts
    @returns {Void}
    */
    public function admin_enqueue_scripts ( $hook_suffix )
    {
      if ( stristr( $hook_suffix, $this->textdomain ) !== FALSE )
      {
        // Styles
        wp_enqueue_style('thickbox');
        wp_enqueue_style( $this->textdomain, plugins_url( 'css/lvl99-image-import.css', __FILE__ ), array(), $this->version, 'all' );

        // Scripts
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        // wp_enqueue_script( 'lvl99-plugin', plugins_url( 'js/lvl99-plugin.js', __FILE__ ), array('jquery', 'jquery-ui-sortable'), $this->version, TRUE );
        wp_enqueue_script( $this->textdomain, plugins_url( 'js/lvl99-image-import.js', __FILE__ ), array('jquery', 'jquery-ui-sortable'), $this->version, TRUE );

        // Custom page-specific styles and scripts
        add_action( 'admin_head', array($this, 'admin_head'), 99999999 );
        add_action( 'admin_footer', array($this, 'admin_footer'), 99999999 );
      }
    }

    /*
    Any CSS or styles for the admin header

    @method admin_head
    @returns {Void}
    */
    public function admin_head ()
    {
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<script type="text/javascript">
  var wp_homeurl = '<?php echo home_url("/"); ?>',
      wp_siteurl = '<?php echo get_site_url(); ?>';
</script>
<?php
    }

    /*
    Any javascript for the admin footer

    @method admin_footer
    @returns {Void}
    */
    public function admin_footer ()
    {
      $needs_js = FALSE;
      $file_uploaders = array();
      $sort_fields = array();

      // Test if any of the options need JavaScript
      foreach( $this->default_options as $name => $option )
      {
        // File uploader
        if ( $option['field_type'] == 'image' )
        {
          $needs_js = TRUE;
          array_push( $file_uploaders, $this->get_field_id($name) );
        }

        // Sortable
        if ( array_key_exists('sortable', $option) )
        {
          if ( $option['sortable'] )
          {
            $needs_js = TRUE;
            array_push( $sort_fields, $this->get_field_id($name) );
          }
        }
      }

      if ( $needs_js )
      { /*
?>
  <script type="text/javascript">
    var lvl99 = lvl99 || {};

    lvl99.enableDebug = <?php echo $this->enable_debug; ?>;
    lvl99.activeWidgets = 0;
    lvl99.widgetCount = 0;
    lvl99.widgets = {};

    // Toggle
    lvl99.widgetCount++;
    lvl99.widgets.toggle = {
      active: false,
      requires: [ 'jQuery' ],
      init: function () {
        jQuery(document).on('click', '.lvl99-toggle', function (event) {
          var $elem = jQuery(this);
          if ( $elem.is('.lvl99-toggle-hidden') ) {
            $elem.css({
              overflow: 'visible',
              height: 'auto'
            }).removeClass('lvl99-toggle-hidden');
          } else {
            $elem.css({
              overflow: 'hidden',
              height: '1em'
            }).addClass('lvl99-toggle-hidden');
          }
        });
      }
    };

    <?php if ( count($sort_fields) > 0 ) : ?>
    // Sortable fields
    lvl99.widgetCount++;
    lvl99.widgets.sortable = {
      active: false,
      requires: [ 'jQuery', 'jQuery.fn.sortable' ],
      init: function () {
        // Save sorted options event
        function sortUpdate (event) {
          // Get the main element
          var $elem = jQuery(event.target);
          if ( !$elem.is('.lvl99-sortable') ) $elem = $elem.parents('.lvl99-sortable');

          // Find the other elements
          var $input = $elem.find('input[type=hidden]');
          var $order = $elem.find('li input[type=checkbox]');

          // Generate the $input's new value
          var newInputVal = [];
          $order.each( function (i, elem) {
            var $elem = jQuery(elem);
            var elemName = $elem.attr('name').replace(/\]\[/g, '.').replace(/\[/, '.').replace(/\]$/, '').split('.');
            if ( $elem.is(':checked') ) {
              newInputVal.push( elemName.pop() );
            }
          });
          $input.val( newInputVal.join(',') );
        }

        // Events
        jQuery(document).on('sortupdate', '.lvl99-sortable', sortUpdate);
        jQuery(document).on('change', '.lvl99-sortable .ui-sortable input[type=checkbox]', sortUpdate);
        jQuery(document).trigger('lvl99-widget-sortable');
      }
    }
    <?php endif; ?>

    <?php if ( count($file_uploaders) > 0 ) : ?>
    // File uploads
    lvl99.widgetCount++;
    lvl99.widgets.fileUploaders = {
      active: false,
      requires: [ 'jQuery' ],
      init: function () {
        jQuery(document).ready(function() {
          jQuery(document).on( 'click', '.upload_file_button', function (event) {
            event.preventDefault();

            jQuery.data( document.body, 'upload_file_element', jQuery(this).parents('.lvl99-widget-option') );

            window.send_to_editor = function (html) {
              var imgurl = jQuery(html).find('img').eq(0).attr('src').replace(wp_homeurl, '/');
              var $option = jQuery.data( document.body, 'upload_file_element' );

              if ( $option != undefined && $option != '' ) {
                $option.find('input').val(imgurl);
                $option.find('img').attr('src', imgurl).show();
                $option.find('.remove_file_button').show();
              }

              tb_remove();
            };

            tb_show('', 'media-upload.php?type=image&TB_iframe=true');
            return false;
          });

          jQuery(document).on( 'click', '.remove_file_button', function (event) {
            event.preventDefault();

            var $option = jQuery(this).parents('.lvl99-widget-option');
            $option.find('input').val('');
            $option.find('img').attr('src', '').hide();
            jQuery(this).hide();
          });

          jQuery(document).trigger('lvl99-widget-file-uploaders');
        });
      }
    }
    <?php endif; ?>

    // See :http://stackoverflow.com/a/6491621/1421162
    Object.byString = function(o, s) {
      s = s.replace(/\[(\w+)\]/g, '.$1'); // convert indexes to properties
      s = s.replace(/^\./, '');           // strip a leading dot
      var a = s.split('.');
      for (var i = 0, n = a.length; i < n; ++i) {
        var k = a[i];
        if (k in o) {
          o = o[k];
        } else {
          return;
        }
      }
      return o;
  }

    // Initialise the widgets
    function __initLvl99Widgets( counter ) {
      if ( counter < 0 ) {
        if ( lvl99.enableDebug && window.console ) if ( console.log ) console.log( 'Maximum widget checks performed. Aborting...' );
        return;
      }

      // Check if widgets have all their required dependencies before initialising
      for( i in lvl99.widgets ) {
        var widget = lvl99.widgets[i];
        widget.checked = widget.checked + 1;
        var requiresPassed = 0;

        // If exceeded 5 checks, abort this widget
        if ( widget.checked > 10 || widget.active ) continue;

        if ( lvl99.enableDebug && window.console ) if ( console.log ) console.log( 'Checking '+widget.requires.length+' dependencies for ' + i );

        // Check requires are present
        for ( x in widget.requires ) {
          if ( lvl99.enableDebug && window.console ) if ( console.log ) console.log( ' --> Requires ' + widget.requires[x] );

          if ( typeof Object.byString( window, widget.requires[x]) != 'undefined' ) {
            if ( lvl99.enableDebug && window.console ) if ( console.log ) console.log( '   ✓ Detected '+(parseInt(x, 10)+1)+'/'+widget.requires.length+': ' + widget.requires[x] );
            requiresPassed++;
          } else {
            if ( lvl99.enableDebug && window.console ) if ( console.log ) console.log( '   x Not found...' );
          }
        }

        // If all requires passed, initialise widget JS
        if ( requiresPassed === widget.requires.length ) {
          if ( lvl99.enableDebug && window.console ) if ( console.log ) console.log( '✓ Detected all required for widget ' + i );
          if ( typeof widget.init == 'function' ) {
            widget.init();
            widget.active = true;
            lvl99.activeWidgets++;
          }
        } else {
          if ( lvl99.enableDebug && window.console ) if ( console.log ) console.log( 'x Missing dependencies for widget ' + i );
        }
      }

      // All widgets have initialised
      if ( lvl99.activeWidgets === lvl99.widgetCount ) {
        if ( lvl99.enableDebug && window.console ) if ( console.log ) console.log( 'All widgets successfully initialised!' );

      // Retry initialising
      } else {
        setTimeout( function () {
          __initLvl99Widgets(counter - 1);
        }, 1000 );
      }
    }

    if ( lvl99.widgetCount > 0 ) __initLvl99Widgets( 10 );
  </script>
<?php */
      } //endif;
    }

    /*
    Loads all options into the class.

    @method load_options
    @since 0.1.0
    @param {Boolean} $init Whether to run within the initialising `register_setting` WP method or just load the options (see `detect_route` for implementation)
    @returns {Void}
    */
    public function load_options ( $init = TRUE )
    {
      global $_wp_additional_image_sizes;

      // Get the thumbnail sizes to build out visible options for maximgthumbnail
      $maximgthumbnail_sizes = array();
      foreach( $_wp_additional_image_sizes as $name => $size )
      {
        $maximgthumbnail_sizes[] = array(
          'label' => $name . ' &ndash; ' . $size['width'] . ' &times; ' . $size['height'] . ($size['crop'] ? ' (cropped)' : ''),
          'value' => $name,
        );
      }
      $maximgthumbnail_sizes = apply_filters( 'lvl99-image-import/maximgthumbnail_sizes', $maximgthumbnail_sizes );

      // Default options
      $this->default_options = array(
        /*
         * Debugging
         */
        '_debugging' => array(
          'field_type' => 'heading',
          'label' => 'Debugging',
        ),

        /*
         * Show debug output
         */
        'show_debug' => array(
          'label' => 'Show debug output',
          'field_type' => 'checkbox',
          'default' => TRUE,
          'help_after' => 'If you\'re having some issues or want to debug your server\'s activity, enable this to see what happens during the image import operation.',
          'sanitise_callback' => array( $this, 'sanitise_option_boolean' ),
        ),

        /*
         * Import to Media Library default
         */
        '_medialibrary' => array(
          'field_type' => 'heading',
          'label' => 'Import to Media Library defaults',
          'help' => 'Configure the default options for the <b>Import to the Media Library</b> method.',
        ),

        /*
         * Force resize images
         */
        'forceresize' => array(
          'label' => _x('Force resizing of large images', 'field label: forceresize', $this->textdomain),
          'field_type' => 'radio',
          'default' => TRUE,
          'values' => array(
            array(
              'label' => 'Enable resizing',
              'value' => TRUE,
              'description' => 'If any downloaded image exceeds the <i>force resize max</i> value, then it will be resampled to that size.',
            ),
            array(
              'label' => 'Disable resizing',
              'value' => FALSE,
              'description' => 'Do not resize any downloaded images.',
            ),
          ),
          // 'input_before' => '',
          // 'input_class' => '',
          // 'help' => '',
          'help_after' => sprintf( '<b>Note:</b> disabling this option may mean you use more server space, bandwidth and users have a slower web experience when viewing your site. Really large images won\'t be processed by WordPress unless you have at least <code>%s</code> of memory available.', WP_MAX_MEMORY_LIMIT ),
          'sanitise_callback' => array( $this, 'sanitise_option_boolean' ),
        ),

        /*
         * Keep original image
         */
        'forceresizekeep' => array(
          'label' => _x('Keep the original image file when <b>Force resize</b> is enabled. Resized image file names will be formated as <code><i>filename</i><b>_resized</b><i>.ext</i></code>', 'field label: forceresizekeep', $this->textdomain),
          'field_type' => 'checkbox',
          'default' => TRUE,
          // 'input_before' => '',
          // 'input_class' => '',
          // 'help' => '',
          'help_after' => '<b>Note:</b> enabling this option may mean you use more server space than necessary but allows for more control later on if you wish to regenerate image thumbnails using different values.',
          'sanitise_callback' => array( $this, 'sanitise_option_boolean' ),
        ),

        /*
         * Force resize max image size
         */
        'forceresizemax' => array(
          'label' => _x('Force resize maximum image size', 'field label: forceresizemax', $this->textdomain),
          'field_type' => 'number',
          'default' => 3000,
          // 'input_before' => '',
          // 'input_class' => '',
          'help' => 'The maximum image size (width or height) to force resizing to.',
          // 'help_after' => '',
          'sanitise_callback' => array( $this, 'sanitise_option_number' ),
        ),

        /*
         * Maximum image width (maximgwidth)
         */
        'maximgwidth' => array(
          'label' => _x('Maximum image width', 'field label: maximgwidth', $this->textdomain),
          'field_type' => 'number',
          'default' => 1200,
          // 'input_before' => '',
          // 'input_class' => '',
          'help' => 'If an image is imported to the Media Library and its width exceeds this value, then the used image reference will refer to a thumbnail version. Set to <code>0</code> to disable this feature and always reference the full sized image.',
          'help_after' => '<b>Note:</b> This option does not resize image files, it merely changes the reference to which version of the image to use.',
          'sanitise_callback' => array( $this, 'sanitise_option_number' ),
        ),

        /*
         * Max Image Thumbnail (maximgthumbnail)
         */
        'maximgthumbnail' => array(
          'label' => _x('Maximum image thumbnail size', 'field label: maximgthumbnail', $this->textdomain),
          'field_type' => 'select',
          'default' => 'large',
          'values' => $maximgthumbnail_sizes,
          // 'input_before' => '',
          // 'input_class' => '',
          'help' => 'The thumbnail size to change imported image references to if the maximum image width has been exceeded.',
          'help_after' => '<b>Note:</b> This option does not resize image files, it merely changes the reference to which version of the image to use.',
          'sanitise_callback' => array( $this, 'sanitise_option_maximgthumbnail' ),
        ),
      );

      // Get the saved options
      if ( count($this->default_options) > 0 )
      {
        foreach ( $this->default_options as $name => $option  )
        {
          // Ignore static option types: `heading`
          if ( $option['field_type'] == 'heading' ) continue;

          // Ensure `sanitise_callback` is NULL
          if ( !array_key_exists('sanitise_callback', $option) ) $option['sanitise_callback'] = NULL;

          // Get the database's value
          $this->options[$name] = get_option( 'lvl99-image-import/' . $name, $option['default'] );

          // Register the setting to be available to all other plugins (I think?)
          if ( $init && !is_null($option['sanitise_callback']) ) register_setting( $this->textdomain, 'lvl99-image-import/' . $name, $option['sanitise_callback'] );
        }
      }
    }

    /*
    Sets an option for the plugin

    @method set_option
    @since 0.1.0
    @param {String} $name The name of the option
    @param {Mixed} $default The default value to return if it is not set
    @returns {Mixed}
    */
    public function set_option ( $name = FALSE, $value = NULL )
    {
      if ( !$name || !array_key_exists($name, $this->options) ) return;
      update_option( 'lvl99-image-import/' . $name, $value );
      $this->options[$name] = $value;
    }

    /*
    Gets value of plugin's option.

    @method get_option
    @since 0.1.0
    @param {String} $name The name of the option
    @param {Mixed} $default The default value to return if it is not set
    @returns {Mixed}
    */
    public function get_option ( $name = FALSE, $default = NULL )
    {
      if ( !$name || !array_key_exists($name, $this->options) ) return $default;
      return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /*
    Replaced the tags (e.g. `{ABSPATH}`, `{WP_CONTENT_DIR}` ) in the `path` option.

    @method get_option_path
    @since 0.1.0
    @returns {String}
    */
    public function get_option_path ()
    {
      $path = $this->replace_tags( $this->get_option('path'), array(
        'ABSPATH' => ABSPATH,
        'WP_CONTENT_DIR' => WP_CONTENT_DIR,
      ) );
      return $path;
    }

    /*
    Get an array of the option names

    @method get_option_names
    @since 0.1.0
    @returns {Array}
    */
    protected function get_option_names()
    {
      $option_names = array();

      foreach( $this->options as $name => $option )
      {
        array_push( $option_names, $name );
      }

      return $option_names;
    }

    /*
    Get an array of the default option values

    @method get_default_option_values
    @since 0.1.0
    @returns {Array}
    */
    protected function get_default_option_values()
    {
      $default_option_values = array();

      foreach( $this->options as $name => $option )
      {
        if ( !empty($option['default']) ) {
          $default_option_values[$name] = $option['default'];
        } else {
          $default_option_values[$name] = '';
        }
      }

      return $default_option_values;
    }

    /*
    sanitise the option's value

    @method sanitise_option
    @since 0.1.0
    @param {String} $input
    @returns {Mixed}
    */
    protected function sanitise_option ( $option, $input )
    {
      // If the sanitise_option has been set...
      if ( array_key_exists('sanitise_callback', $option) && !empty($option['sanitise_callback']) && !is_null($option['sanitise_callback']) )
      {
        return call_user_func( $option['sanitise_callback'], $input );
      }

      return $input;
    }

    /*
    sanitise the option's text value (strips HTML)

    @method sanitise_option_text
    @since 0.1.0
    @param {String} $input
    @returns {String}
    */
    public static function sanitise_option_text ( $input )
    {
      // ChromePhp::log( 'sanitise_option_text' );
      // ChromePhp::log( $input );

      return strip_tags(trim($input));
    }

    /*
    sanitise the option's HTML value (strips only some HTML)

    @method sanitise_option_html
    @since 0.1.0
    @param {String} $input
    @returns {String}
    */
    public static function sanitise_option_html ( $input )
    {
      // ChromePhp::log( 'sanitise_option_html' );
      // ChromePhp::log( $input );

      return strip_tags( trim($input), '<b><strong><i><em><u><del><strikethru><a><br><span><div><p><h1><h2><h3><h4><h5><h6><ul><ol><li><dl><dd><dt>' );
    }

    /*
    sanitise the option's number value

    @method sanitise_option_number
    @since 0.1.0
    @param {String} $input
    @returns {Integer}
    */
    public static function sanitise_option_number ( $input )
    {
      // ChromePhp::log( 'sanitise_option_number' );
      // ChromePhp::log( $input );

      return intval( preg_replace( '/\D+/i', '', $input ) );
    }

    /*
    sanitise the option's URL value. Namely, remove any absolute domain reference (make it relative to the current domain)

    @method sanitise_option_url
    @since 0.1.0
    @param {String} $input
    @returns {Integer}
    */
    public static function sanitise_option_url ( $input )
    {
      // ChromePhp::log( 'sanitise_option_url' );
      // ChromePhp::log( $input );

      if ( stristr($input, WP_HOME) !== FALSE )
      {
        $input = str_replace(WP_HOME, '', $input);
      }

      return strip_tags(trim($input));
    }

    /*
    sanitise the option's boolean value

    @method sanitise_option_boolean
    @since 0.1.0
    @param {String} $input
    @returns {Integer}
    */
    public static function sanitise_option_boolean ( $input )
    {
      if ( $input === 1 || strtolower($input) === 'true' || $input === TRUE || $input === '1' ) return TRUE;
      if ( $input === 0 || strtolower($input) === 'false' || $input === FALSE || $input === '0' || empty($input) ) return FALSE;
      return (bool) $input;
    }

    /*
    Sanitise the maximgthumbnail option's value

    @method sanitise_option_maximgthumbnail
    @since 0.1.0
    @param {String} $input
    @returns {Integer}
    */
    public static function sanitise_option_maximgthumbnail ( $input )
    {
      global $_wp_additional_image_sizes;

      // Input is valid
      if ( array_key_exists($input, $_wp_additional_image_sizes) )
      {
        return $input;
      } else {
        return 'large';
      }
    }

    /*
    Sanitises SQL, primarily by looking for specific SQL commands

    @method sanitise_sql
    @since 0.1.0
    @param {String} $input The string to sanitise
    @returns {String}
    */
    protected function sanitise_sql ( $input )
    {
      $search = array(
        '/(CREATE|DROP|UPDATE|ALTER|RENAME|TRUNCATE)\s+(TABLE|TABLESPACE|DATABASE|VIEW|LOGFILE|EVENT|FUNCTION|PROCEDURE|TRIGGER)[^;]+/i',
        '/\d\s*=\s*\d/',
        '/;.*/',
      );

      $replace = array(
        '',
        '',
        '',
      );

      $output = preg_replace( $search, $replace, $input );
      return $output;
    }

    /*
    Get option field ID

    @method get_field_id
    @param {String} $field_name The name of the option
    @returns {String}
    */
    protected function get_field_id( $option_name )
    {
      // if ( array_key_exists($option_name, $this->default_options) )
      // {
        return $this->textdomain . '_' . $option_name;
      // }
      // return '';
    }

    /*
    Get option field name

    @method get_field_name
    @param {String} $field_name The name of the option
    @returns {String}
    */
    protected function get_field_name( $option_name )
    {
      if ( array_key_exists($option_name, $this->default_options) )
      {
        return $this->textdomain . '/' . $option_name;
      }
      else
      {
        return $this->textdomain . '_' . $option_name;
      }
    }

    /*
    Render options' input fields.

    @method render_options
    @since 0.1.0
    @param {Array} $options The options to render out
    @returns {Void}
    */
    protected function render_options ( $options )
    {
      $this->check_admin();

      // Check if its the plugin's settings screen
      $screen = get_current_screen();
      $is_settings_options = $screen->id == 'settings_page_lvl99-image-import-options';

      if ( count($options > 0) )
      {
        foreach( $options as $name => $option )
        {
          // ID and name (changes if not settings page)
          $field_id = $is_settings_options ? $this->get_field_id($name) : $this->get_field_id($name);
          $field_name = $is_settings_options ? $this->get_field_name($name) : $this->get_field_id($name);

          // Visible field
          $is_visible = array_key_exists('visible', $option) ? $option['visible'] : TRUE;
          if ( $option['field_type'] == 'hidden' ) $is_visible = FALSE;

          // Headings and other static option types
          if ( $option['field_type'] == 'heading' )
          {
?>
          <div class="lvl99-plugin-option-heading" id="<?php echo esc_attr($field_id); ?>">
            <h3><?php echo $option['label']; ?></h3>
            <hr/>

            <?php if ( isset($option['help']) ) : ?>
            <div class="lvl99-plugin-option-help lvl99-plugin-option-help-before">
              <?php echo $option['help']; ?>
            </div>
            <?php endif; ?>

            <?php if ( isset($option['help_after']) ) : ?>
            <div class="lvl99-plugin-option-help lvl99-plugin-option-help-after">
              <?php echo $option['help_after']; ?>
            </div>
            <?php endif; ?>
          </div>
<?php
            continue;
          }

          // Singular field (e.g. single checkbox or radio)
          $is_singular = $option['field_type'] == 'checkbox' && !array_key_exists('values', $option);

          // Sortable fields
          $is_sortable = ( $option['field_type'] == 'checkbox' && array_key_exists('sortable', $option) && !$is_singular ? $option['sortable'] : FALSE );

          // Input class
          $input_class = !empty($option['input_class']) ? $option['input_class'] : 'widefat';

          // Default values for the option
          $option_value = !empty($this->options[$name]) ? $this->options[$name] : $option['default'];

          if ( $is_visible )
          {
?>
          <div class="lvl99-plugin-option <?php if ($is_sortable && $option['field_type'] != 'checkbox' && $option['field_type'] != 'radio') : ?>lvl99-draggable lvl99-sortable lvl99-sortable-handle<?php endif; ?>">

            <?php do_action( 'lvl99_plugin_option_field_footer_' . $name, '' ); ?>

            <?php if ( !$is_singular ) : ?>
            <label for="<?php echo $field_id; ?>" class="lvl99-plugin-option-label"><?php echo $option['label']; ?></label>
            <?php endif; ?>

            <?php if ( isset($option['help']) ) : ?>
            <div class="lvl99-plugin-option-help lvl99-plugin-option-help-before">
              <?php echo $option['help']; ?>
            </div>
            <?php endif; ?>

            <?php if ( !empty($option['input_before']) ) : ?>
            <span class="lvl99-plugin-option-input-before">
              <?php echo $option['input_before']; ?>
            </span>
            <?php endif; ?>

            <?php if ( $option['field_type'] == 'text' ) : ?>
              <input id="<?php echo $field_id; ?>" type="text" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($option_value); ?>" class="<?php echo esc_attr($input_class); ?>" />

            <?php elseif ( $option['field_type'] == 'number' ) : ?>
              <input id="<?php echo $field_id; ?>" type="number" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($option_value); ?>" class="<?php echo esc_attr($input_class); ?>" />

            <?php elseif ( $option['field_type'] == 'email' ) : ?>
              <input id="<?php echo $field_id; ?>" type="email" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($option_value); ?>" class="<?php echo esc_attr($input_class); ?>" />

            <?php elseif ( $option['field_type'] == 'select' ) : ?>
              <select id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" class="<?php echo esc_attr($input_class); ?>">
              <?php foreach( $option['values'] as $value ) : ?>
                <?php if ( is_array($value) ) : ?>
                <option value="<?php echo $value['value']; ?>" <?php if ( $option_value == $value['value'] ) : ?>selected="selected"<?php endif; ?>>
                <?php if ( isset($value['label']) ) : ?>
                  <?php echo $value['label']; ?>
                <?php else : ?>
                  <?php echo $value['value']; ?>
                <?php endif; ?>
                </option>
                <?php else : ?>
                <option <?php if ( $option_value == $value ) : ?>selected="selected"<?php endif; ?>><?php echo $value; ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
              </select>

            <?php elseif ( $option['field_type'] == 'radio' ) : ?>
              <ul id="<?php echo $field_id; ?>-list">
                <?php foreach( $option['values'] as $value ) : ?>
                <?php if ( is_array($value) ) : ?>
                  <li>
                    <label class="lvl99-plugin-option-value">
                      <input type="radio" name="<?php echo $field_name; ?>" value="<?php echo $value['value']; ?>" <?php if ( $option_value == $value['value'] ) : ?>checked="checked"<?php endif; ?> />
                      <div class="lvl99-plugin-option-value-label">
                        <?php if ( isset($value['label']) ) : ?>
                          <?php echo $value['label']; ?>
                        <?php else : ?>
                          <?php echo $value['value']; ?>
                        <?php endif; ?>
                        <?php if ( !empty($value['description']) ) : ?>
                        <p class="lvl99-plugin-option-value-description"><?php echo $value['description']; ?></p>
                        <?php endif; ?>
                      </div>
                    </label>
                  </li>
                <?php else : ?>
                  <li>
                    <label class="lvl99-plugin-option-value">
                      <input type="radio" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($value); ?>" <?php if ( $option_value == $value ) : ?>checked="checked"<?php endif; ?> />
                      <div class="lvl99-plugin-option-value-label">
                        <?php if ( $is_singular ) : ?>
                        <?php echo $option['label']; ?>
                        <?php else : ?>
                        <?php echo $value; ?>
                        <?php endif; ?>
                        <?php if ( !empty($value['description']) ) : ?>
                        <p class="lvl99-plugin-option-value-description"><?php echo $value['description']; ?></p>
                        <?php endif; ?>
                      </div>
                    </label>
                  </li>
                <?php endif; ?>
                <?php endforeach; ?>
              </ul>

            <?php elseif ( $option['field_type'] == 'checkbox' ) : ?>
              <ul id="<?php echo $field_id; ?>-list" class="<?php if ($is_sortable) : ?>lvl99-sortable<?php endif; ?>">
                <?php $option_values = isset($option['values']) ? $option['values'] : array($option_value); ?>

                <?php if ( $is_sortable ) :
                  // If the field is sortable, we'll need to render the options in the sorted order
                  if ( stristr($option_value, ',') !== FALSE )
                  {
                    $option_values = explode( ',', $option_value );

                    // Add the other values that the $option_values is missing (because they haven't been checked)
                    foreach( $option['values'] as $key => $value )
                    {
                      if ( !in_array($key, $option_values) )
                      {
                        array_push( $option_values, $key );
                      }
                    }

                    // Re-order the options' rendering order
                    $reordered_values = array();
                    foreach ( $option_values as $key => $value )
                    {
                      $reordered_values[$key] = $option['values'][$value];
                    }
                    $option_values = $reordered_values;

                  } ?>
                  <input type="hidden" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($option_value); ?>" />
                <?php endif; ?>

                <?php foreach ( $option_values as $value ) : ?>
                  <?php if ( is_array($value) ) : ?>
                  <li <?php if ( $is_sortable ) : ?>class="ui-draggable ui-sortable"<?php endif; ?>>
                    <?php if ($is_sortable) : ?><span class="fa-arrows-v lvl99-sortable-handle"></span><?php endif; ?>
                    <label class="lvl99-plugin-option-value">
                      <input type="checkbox" name="<?php if ( $is_sortable ) : echo esc_attr($name).'['.esc_attr($value['value']).']'; else : echo $field_name; endif; ?>" value="true" <?php if ( stristr($option_value, $value['value'])) : ?>checked="checked"<?php endif; ?> />
                      <div class="lvl99-plugin-option-value-label">
                        <?php if ( isset($value['label']) ) : ?>
                          <?php echo $value['label']; ?>
                        <?php else : ?>
                          <?php echo $value['value']; ?>
                        <?php endif; ?>
                        <?php if ( !empty($value['description']) ) : ?>
                        <p class="lvl99-plugin-option-value-description"><?php echo $value['description']; ?></p>
                        <?php endif; ?>
                      </div>
                    </label>
                  </li>
                  <?php else : ?>
                  <li <?php if ( $is_sortable ) : ?>class="ui-draggable ui-sortable"<?php endif; ?>>
                    <?php if ($is_sortable) : ?><span class="fa-arrows-v lvl99-sortable-handle"></span><?php endif; ?>
                    <label class="lvl99-plugin-option-value">
                      <input type="checkbox" name="<?php if ( $is_sortable ) : echo esc_attr($name).'['.esc_attr($value['value']).']'; else : echo $field_name; endif; ?>" value="<?php echo ( $is_singular ? 'true' : esc_attr($value) ); ?>" <?php if ( !empty($option_value) && $option_value == $value ) : ?>checked="checked"<?php endif; ?> />
                      <div class="lvl99-plugin-option-value-label">
                        <?php if ( $is_singular ) : ?>
                        <?php echo $option['label']; ?>
                        <?php else : ?>
                        <?php echo $value; ?>
                        <?php endif; ?>
                        <?php if ( !empty($value['description']) ) : ?>
                        <p class="lvl99-plugin-option-value-description"><?php echo $value['description']; ?></p>
                        <?php endif; ?>
                      </div>
                    </label>
                  </li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>

            <?php elseif ( $option['field_type'] == 'image' ) : ?>
              <a href="javascript:void(0);" class="upload_file_button">
                <div class="button-primary"><?php _e( 'Upload or select image', 'lvl99' ); ?></div>
                <input type="hidden" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($option_value); ?>" />
                <p><img src="<?php echo esc_url($option_value); ?>" style="max-width: 100%; <?php if ( $option_value == "" ) : ?>display: none<?php endif; ?>" /></p>
              </a>
              <a href="javascript:void(0);" class="remove_file_button button" <?php if ( $option_value == "" ) : ?>style="display:none"<?php endif; ?>>Remove image</a>

            <?php elseif ( $option['field_type'] == 'textarea' ) : ?>
              <textarea id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" class="<?php echo esc_attr($input_class); ?>"><?php echo $option_value; ?></textarea>

            <?php endif; ?>

            <?php if ( !empty($option['input_after']) ) : ?>
            <span class="lvl99-plugin-option-input-after">
              <?php echo $option['input_after']; ?>
            </span>
            <?php endif; ?>

            <?php if ( isset($option['help_after']) ) : ?>
            <div class="lvl99-plugin-option-help lvl99-plugin-option-help-after">
              <?php echo $option['help_after']; ?>
            </div>
            <?php endif; ?>

            <?php do_action( 'lvl99_plugin_option_field_footer_' . $name, '' ); ?>

            <?php if ( $is_sortable ) : ?>
            <script type="text/javascript">
              jQuery(document).ready( function () {
                jQuery('#<?php echo $field_id; ?>-list.lvl99-sortable').sortable({
                  items: '> li',
                  handle: '.lvl99-sortable-handle'
                });
              });
            </script>
            <?php endif; ?>
          </div>
<?php
          // Hidden fields
          } else {
            if ( $option['field_type'] == 'hidden' )
            {
?>
          <input type="hidden" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($option_value); ?>" />
<?php
            }
          }
        } // endforeach;
      }
    }

    /*
    Replace {tags} within a string using an array's properties (and other custom functions)

    @method replace_tags
    @since 0.1.0
    @param {String} $input
    @param {Array} $tags The array with tags to replace
    @returns {String}
    */
    protected function replace_tags( $input, $tags = array() )
    {
      $output = $input;
      preg_match_all( '/\{[a-z0-9\:\_\-\/\\\]+\}/i', $input, $matches );

      if ( count($matches[0]) )
      {
        foreach( $matches[0] as $tag )
        {
          $tag_search = $tag;
          $tag_name = preg_replace( '/[\{\}]/', '', $tag );
          $tag_replace = '';

          // Get string to replace tag with
          if ( array_key_exists( $tag_name, $tags ) != FALSE )
          {
            $tag_replace = $tags[$tag_name];
          }

          // Tag has arguments
          if ( strstr($tag_name, ':') != FALSE )
          {
            $tag_split = explode( ':', $tag_name );
            $tag_name = $tag_split[0];
            $tag_replace = $tag_split[1];

            // Supported special functions (defined by {function:argument})
            switch ($tag_name)
            {
              case 'date':
                $tag_replace = date( $tag_replace );
                break;
            }
          }

          // Replace
          $output = str_replace( $tag_search, $tag_replace, $output );
        }
      }

      return $output;
    }

    /*
    Detects if a route was fired and then builds `$this->route` object and fires its corresponding method after the plugins have loaded.

    Routes are actions which happen before anything is rendered.

    @method detect_route
    @since 0.1.0
    @returns {Void}
    */
    public function detect_route ()
    {
      // Ignore if doesn't match this plugin's textdomain
      if ( !isset($_GET['page']) && !isset($_REQUEST[$this->textdomain]) ) return;

      // Do the detection schtuff
      if ( (isset($_REQUEST[$this->textdomain]) && !empty($_REQUEST[$this->textdomain])) || ($_GET['page'] == $this->textdomain && isset($_GET['action'])) )
      {
        $this->check_admin();
        $this->load_options(FALSE);

        // Process request params
        $_request = array(
          'get' => $_GET,
          'post' => $_POST,
        );

        $request = array();
        foreach ( $_request as $_method => $_array )
        {
          $request[$_method] = array();
          foreach( $_array as $name => $value )
          {
            if ( stristr($name, $this->textdomain.'_') != FALSE )
            {
              $request[$_method][str_replace( $this->textdomain.'_', '', strtolower($name) )] = is_string($value) ? urldecode($value) : $value;
            }
          }
        }

        // Get the method name depending on the type
        if ( isset($_REQUEST[$this->textdomain]) && !empty($_REQUEST[$this->textdomain]) )
        {
          $method_name = $_REQUEST[$this->textdomain];
        }
        else if ( $_GET['page'] == $this->textdomain && isset($_GET['action']) )
        {
          $method_name = $_GET['action'];
        }

        // Build and set the route to the class for later referral when running the route's method
        $this->route = array(
          'method' => 'route_' . preg_replace( '/[^a-z0-9_]+/i', '', $method_name ),
          'referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL,
          'request' => $request,
        );

        $this->perform_route();
      }
    }

    /*
    Performs the route's method (only if one exists)

    @method perform_route
    @since 0.1.0
    @returns {Void}
    */
    public function perform_route ()
    {
      $this->check_admin();

      if ( isset($this->route['method']) && !empty($this->route['method']) && method_exists( $this, $this->route['method'] ) )
      {
        call_user_func( array( $this, $this->route['method'] ) );
      }
      else
      {
        error_log( 'LVL99 Image Import: invalid route method called: ' . $this->route['method'] );
        // $this->admin_error( sprintf( __('Invalid route method was called: <strong><code>%s</code></strong>', $this->textdomain), $this->route['method'] ) );
      }
    }

    /*
    Initial scan form to configure scan

    @method route_scan
    @since 0.1.0
    @returns {Void}
    */
    public function route_scan ()
    {
      $_REQUEST['action'] = 'scan';
    }

    /*
    Scan posts for external image references.

    @method route_scanned
    @since 0.1.0
    @returns {Void}
    */
    public function route_scanned ()
    {
      $this->check_admin();

      // Do whatever here
      $this->scan_posts();

      // If found images, process them depending on filters
      if ( !empty($this->results['images_import']) && count($this->results['images_import']) > 0 )
      {
        $_REQUEST['action'] = 'scanned';

      }
      else
      {
        $this->admin_error( 'Found no image references to import (excluded ' . count($this->results['images_excluded']) . ' images)' );
        $_REQUEST['action'] = 'scan';
      }
    }

    /*
    Imported selected references.

    @method route_imported
    @since 0.1.0
    @returns {Boolean}
    */
    public function route_imported ()
    {
      $this->check_admin();

      // Import the images into the media library
      $this->results['importtype'] = $this->route['request']['post']['importtype'];
      if ( $this->results['importtype'] == 'medialibrary' )
      {
        $this->import_images();

      // If `change` is only necessary, set the collection to reference the images posted
      }
      else if ( $this->results['importtype'] == 'change' )
      {
        $this->results['images_imported'] = $this->route['request']['post']['images'];
      }

      // If found images, process them depending on filters
      if ( isset($this->results['images_imported']) && count($this->results['images_imported']) > 0 )
      {
        // Update the image references
        $this->update_image_references();
        $_REQUEST['action'] = 'imported';

      }
      else
      {
        $this->admin_error( 'Affected no images!' );
        $_REQUEST['action'] = 'scan';
      }
    }

    /*
    Scan attachments for broken links.

    @method route_brokenlinks
    @since 0.1.0
    @returns {Void}
    */
    public function route_brokenlinks ()
    {
      $this->check_admin();

      $this->scan_brokenlinks();

      if ( count($this->results['items_scanned']) > 0 )
      {
        // $this->admin_notice( sprintf('Found %d broken or external attachment links', count($this->results['items_scanned']) ) );
        $_REQUEST['action'] = 'brokenlinks';
      }
      else
      {
        $this->admin_notice( 'No broken or external attachment links were detected. Good job!' );
        $_REQUEST['action'] = 'extras';
      }
    }

    /*
    Fix broken links

    @method route_brokenlinks
    @since 0.1.0
    @returns {Void}
    */
    public function route_fixbrokenlinks ()
    {
      $this->check_admin();

      if ( isset($this->route['request']['post']['itemsscanned']) && count($this->route['request']['post']['itemsscanned']) > 0 )
      {
        $this->fix_brokenlinks();

        $this->admin_notice( sprintf('Processed %d attachment links', count($this->results['items_scanned']) ) );
        $_REQUEST['action'] = 'extras';
      }
      else
      {
        $this->admin_error( 'No broken links or external attachments were processed!' );
        $_REQUEST['action'] = 'brokenlinks';
      }
    }

    /*
    Shows the admin page.

    @method view_admin_index
    @since 0.1.0
    @returns {Void}
    */
    public function view_admin_index ()
    {
      $this->check_admin();

      $route = $this->route;
      include( trailingslashit($this->plugin_dir) . 'views/admin-index.php' );
    }

    /*
    Shows the admin scan page.

    @method view_admin_scan
    @since 0.1.0
    @returns {Void}
    */
    public function view_admin_scan ()
    {
      $this->check_admin();

      $route = $this->route;
      include( trailingslashit($this->plugin_dir) . 'views/admin-scan.php' );
    }

    /*
    Shows the admin import page.

    @method view_admin_import
    @since 0.1.0
    @param {Array} $images The images that were found during the scan
    @returns {Void}
    */
    public function view_admin_import ()
    {
      $this->check_admin();

      $route = $this->route;
      if ( count($this->results['images_import']) > 0 )
      {
        include( trailingslashit($this->plugin_dir) . 'views/admin-import.php' );
      }
      else
      {
        $this->admin_error( 'Found no image references to import (excluded ' . count($this->result['images_excluded']) . ' images)' );
        include( trailingslashit($this->plugin_dir) . 'views/admin-index.php' );
      }
    }

    /*
    Shows the admin imported page.

    @method view_admin_imported
    @since 0.1.0
    @param {Array} $images The images that were found during the scan
    @returns {Void}
    */
    public function view_admin_imported ()
    {
      $this->check_admin();

      $route = $this->route;
      if ( count($this->results['images_imported']) > 0 )
      {
        include( trailingslashit($this->plugin_dir) . 'views/admin-imported.php' );
      }
      else
      {
        $this->admin_error( 'Imported no images!' );
        include( trailingslashit($this->plugin_dir) . 'views/admin-index.php' );
      }
    }

    /*
    Shows the admin extras page.

    @method view_admin_extras
    @since 0.1.0
    @returns {Void}
    */
    public function view_admin_extras ()
    {
      $this->check_admin();

      $route = $this->route;
      include( trailingslashit($this->plugin_dir) . 'views/admin-extras.php' );
    }

    /*
    Shows the admin brokenlinks page.

    @method view_admin_brokenlinks
    @since 0.1.0
    @returns {Void}
    */
    public function view_admin_brokenlinks ()
    {
      $this->check_admin();

      $route = $this->route;
      include( trailingslashit($this->plugin_dir) . 'views/admin-brokenlinks.php' );
    }

    /*
    Shows the admin options page.

    @method view_admin_options
    @since 0.1.0
    @returns {Void}
    */
    public function view_admin_options ()
    {
      $this->check_admin();

      $route = $this->route;
      include( trailingslashit($this->plugin_dir) . 'views/admin-options.php' );
    }

    /*
    Displays the notices in the admin section (used in admin view code).

    @method admin_notices
    @since 0.1.0
    @returns {Void}
    */
    public function admin_notices ()
    {
      $this->check_admin();

      if ( count($this->notices) > 0 )
      {
        foreach( $this->notices as $notice )
        {
?>
<div class="<?php echo esc_attr($notice['type']); ?>">
  <p><?php echo $notice['content']; ?></p>
</div>
<?php
        }
      }
    }

    /*
    Adds a notice to the admin section

    Example: `$this->admin_notice( sprintf( __('%s: <strong><code>%s</code></strong> was successfully deleted', 'lvl99-image-import'), $this->textdomain, $file_name ) );`

    @method admin_notice
    @since 0.1.0
    @param {String} $type The type of notice: `updated | error`
    @param {String} $message The notice's message to output to the admin messages
    @returns {Void}
    */
    public function admin_notice ( $msg, $type = 'updated' )
    {
      array_push( $this->notices, array(
        'type' => $type,
        'content' => $msg,
      ) );
    }

    /*
    Adds an error notice to the admin section

    Example: `$this->admin_error( sprintf( __('%s: Could not remove <strong><code>%s</code></strong> from the server. Please check file and folder permissions', 'lvl99-image-import'), $this->textdomain, $file_name ) );`

    @method admin_error
    @since 0.1.0
    @param {String} $message The error's message to output to the admin messages
    @returns {Void}
    */
    public function admin_error ( $msg )
    {
      array_push( $this->notices, array(
        'type' => 'error',
        'content' => $msg,
      ) );
      error_log( sprintf( __('%s Error: %s', $this->textdomain, $msg ), $this->textdomain, $msg ) );
    }

    /*
    Adds extra links under the plugin's name within the plugins list.

    @method admin_plugin_links
    @since 0.1.0
    @param {Array} $links An array containing the HTML link code for the plugin's page links
    @returns {Void}
    */
    public function admin_plugin_links ( $links = array() )
    {
      $plugin_links = array(
        '<a href="tools.php?page=lvl99-image-import&action=scan">Scan &amp; Import</a>',
        '<a href="tools.php?page=lvl99-image-import&action=extras">Extras</a>',
        '<a href="options-general.php?page=lvl99-image-import-options">Options</a>',
      );
      return array_merge( $plugin_links, $links );
    }

    /*
    Runs when initialising admin menu. Sets up the links for the plugin's related pages.

    @method admin_menu
    @since 0.1.0
    @returns {Void}
    */
    public function admin_menu ()
    {
      $this->check_admin();

      // General pages
      add_management_page(
        __('Image Import', $this->textdomain),
        __('Image Import', $this->textdomain),
        'activate_plugins',
        'lvl99-image-import',
        array( $this, 'view_admin_index' )
      );

      // Options page
      add_options_page(
        __('Image Import', $this->textdomain),
        __('Image Import', $this->textdomain),
        'activate_plugins',
        'lvl99-image-import-options',
        array( $this, 'view_admin_options' )
      );
    }

    /*
    Formats a file size (given in byte value) with KB/MB signifier.

    @method format_file_size
    @since 0.1.0
    @param {Integer} $input The file size in bytes
    @param {Integer} $decimals The number of decimal points to round to
    @returns {String}
    */
    public function format_file_size ( $input, $decimals = 2 )
    {
      $input = intval( $input );
      if ( $input < 1000000 ) return round( $input/1000 ) . 'KB';
      if ( $input < 1000000000 ) return round( ($input/1000)/1000, $decimals ) . 'MB';
      return $input;
    }

    /*
    Scan all posts to retrieve all the external media images referenced to import

    @method scan_posts
    @since 0.1.0
    @returns {Void}
    */
    public function scan_posts ()
    {
      global $wpdb;

      $this->check_admin();

      // General post query
      $query = "SELECT ID, post_type, post_content, guid, post_mime_type FROM `$wpdb->posts`";

      // Affect which post types to search through
      if ( $this->route['request']['post']['posttypes'] === 'selected' )
      {
        $posttypes = array();
        foreach ( $this->route['request']['post']['posttypes_selected'] as $posttype )
        {
          array_push( $posttypes, $this->sanitise_sql($posttype) );
        }
        $query .= " WHERE post_type IN ('" . implode("', '", $posttypes) . "')";
      }

      // Set up some containing vars
      $images = array();
      $image_names = array();
      $images_import = array();
      $images_excluded = array();
      $posts_affected = array();
      $importtype = $this->route['request']['post']['importtype'];
      $removequerystrings = isset($this->route['request']['post']['removequerystrings']) ? $this->sanitise_option_boolean($this->route['request']['post']['removequerystrings']) : FALSE; // singular checkbox
      $overwritefiles = isset($this->route['request']['post']['overwritefiles']) ? $this->sanitise_option_boolean($this->route['request']['post']['overwritefiles']) : FALSE; // singular checkbox
      $uploaddir = $this->route['request']['post']['uploaddir'];
      $forceresize = isset($this->route['request']['post']['forceresize']) ? $this->sanitise_option_boolean($this->route['request']['post']['forceresize']) : $this->get_option('forceresize');
      $forceresizemax = isset($this->route['request']['post']['forceresizemax']) ? $this->sanitise_option_number($this->route['request']['post']['forceresizemax']) : $this->default_options['forceresizemax']['default'];
      $forceresizekeep = isset($this->route['request']['post']['forceresizekeep']) ? $this->sanitise_option_boolean($this->route['request']['post']['forceresizekeep']) : FALSE; // singular checkbox
      $maximgwidth = isset($this->route['request']['post']['maximgwidth']) ? $this->sanitise_option_number($this->route['request']['post']['maximgwidth']) : $this->default_options['maximgwidth']['default'];
      $maximgthumbnail = isset($this->route['request']['post']['maximgthumbnail']) ? $this->sanitise_option_maximgthumbnail($this->route['request']['post']['maximgthumbnail']) : $this->default_options['maximgthumbnail']['default'];
      $filters = !empty($this->route['request']['post']['filters']) ? $this->route['request']['post']['filters'] : array();

      // Process filters with regex input
      if ( count($filters) > 0 )
      {
        foreach ( $filters as $num => $filter )
        {
          // Empty input? Error
          if ( empty($filter['input']) )
          {
            $this->admin_error( 'Empty &quot;'.$filter['method'].'&quot; filter input detected' );
            return $this->results;
          }

          // Format regex inputs
          if ( preg_match( '/^\//', $filter['input'] ) )
          {
            $filters[$num]['input'] = stripslashes($filter['input']);
          }
        }
      }

      // Change was selected but no replace filter given
      // @NOTE may not be needed as person can change per image item on the next screen
      // if ( $importtype == 'change' )
      // {
      //   $count_replace_filters = 0;
      //   foreach ( $filters as $filter )
      //   {
      //     if ( $filter['method'] == 'replace' ) $count_replace_filters++;
      //   }

      //   if ( $count_replace_filters == 0 )
      //   {
      //     $this->admin_error( __('&quot;Change Image Links&quot; was selected but no &quot;Search &amp; Replace&quot; filter was declared', $this->textdomain) );
      //     $this->results['images'] = array();
      //     return $this->results;
      //   }
      // }

      // Get all post entries to scan for image references
      $count_image_references = 0;
      $results = $wpdb->get_results( $query, ARRAY_A );
      if ( count($results) > 0 )
      {
        foreach ( $results as $result )
        {
          // Check external attachments' guid field
          if ( $result['post_type'] == 'attachment' )
          {
            // Only include attachment if matches image mime type
            if ( strstr($result['post_mime_type'], 'image') == FALSE ) continue;

            // Match all image URLS (png, jpg, gif)
            $found = preg_match_all( '/^(https?:\/\/.*\.(jpg|jpeg|gif|png))$/i', $result['guid'], $matches );

          // Assume searching post_content for all other posts
          }
          else
          {
            $found = preg_match_all( '/<img[^>]+src="([^"]+)"[^>]*>/i', $result['post_content'], $matches );
          }

          if ( $found > 0 && count($matches[1]) > 0 )
          {
            foreach( $matches[1] as $num => $image_url )
            {
              // Remove query strings
              if ( $removequerystrings ) $image_url = preg_replace( '/\?.*$/', '', trim($image_url) );

              // Count references
              $count_image_references++;

              // Generate image info
              $hash = md5($image_url);
              if ( !isset($images[$hash]) )
              {
                $images[$hash] = array(
                  'src' => $image_url,
                  'hash' => $hash,
                  'as' => $image_url,
                  'posts' => array(),
                );
              }

              // Save post ID references
              if ( !in_array( $result['ID'], $images[$hash]['posts'] ) )
              {
                $images[$hash]['posts'][] = $result['ID'];
              }
            }
          }
        }

        // echo '<pre>';
        // echo $query . "\n\n";
        // echo 'request:'."\n\n";
        // print_r($this->route);
        // echo 'image references: ' . $count_image_references . "\n\n";
        // echo 'images found: ' . count($images) . "\n\n";
        // print_r($images);
        // print_r($posts);
        // echo '</pre>';
        // exit();

        // Apply the filters to the images
        foreach ( $images as $hash => $image )
        {
          $image_include = TRUE;
          $image_info = array(
            'src' => $image['src'],
            'as' => $image['as'],
            'posts' => $image['posts'],
          );

          // Run filters
          if ( count($filters) > 0 )
          {
            foreach ( $filters as $filter )
            {
              switch ($filter['method'])
              {
                // Add to the collection of images
                case 'include':
                  // Regexp
                  if ( preg_match( '/^\//', $filter['input'] ) )
                  {
                    // $this->debug( array(
                    //   'filter_include_input' => $filter['input'],
                    // ) );

                    if ( preg_match( $filter['input'], $image['src'] ) )
                    {
                      $image_include = TRUE;
                    }

                  // Normal text match
                  } else {
                    if ( stristr( $image['src'], $filter['input'] ) != FALSE )
                    {
                      $image_include = TRUE;
                    }
                  }

                  break;

                // Remove from the collection of images
                case 'exclude':
                  // Regexp
                  if ( preg_match( '/^\//', $filter['input'] ) )
                  {
                    // $this->debug( array(
                    //   'filter_exclude_input' => $filter['input'],
                    // ) );

                    if ( preg_match( $filter['input'], $image['src'] ) )
                    {
                      $image_include = FALSE;
                    }

                  // Normal text match
                  } else {
                    if ( stristr( $image['src'], $filter['input']) != FALSE )
                    {
                      $image_include = FALSE;
                    }
                  }
                  break;

                // Search and replace in `as` name (for changing image references)
                case 'replace':
                  // $this->debug( array(
                  //   'filter_replace_input' => $filter['input'],
                  // ) );

                  // Regexp search/replace
                  if ( preg_match( '/^\//', $filter['input'] ) )
                  {
                    $image_info['as'] = preg_replace( $filter['input'], $filter['output'], $image['as'] );

                  // Normal text search/replace
                  } else {
                    $image_info['as'] = str_replace( $filter['input'], $filter['output'], $image['as'] );
                  }
                  break;
              }
            }
          }

          // If importtype=medialibrary, strip all the info from the as field, as we only want the file name
          if ( $importtype == 'medialibrary' && $image_info['as'] == $image['src'] )
          {
            $image_info['as'] = $this->get_file_name( $image['src'], $image_names );
          }

          if ( $image_include )
          {
            // Include image to import/change
            $images_import[$hash] = $image_info;

            // Add posts affected
            $image_info['posts'] = $image['posts'];
            foreach( $image_info['posts'] as $post_id )
            {
              if ( !in_array($post_id, $posts_affected) )
              {
                array_push( $posts_affected, $post_id );
              }
            }
          }
          else
          {
            $images_excluded[$hash] = $image_info;
          }
        }
      }

      $this->results['query'] = $query;
      $this->results['importtype'] = $importtype;
      $this->results['removequerystrings'] = $removequerystrings;
      $this->results['overwritefiles'] = $overwritefiles;
      $this->results['uploaddir'] = $uploaddir;
      $this->results['forceresize'] = $forceresize;
      $this->results['forceresizemax'] = $forceresizemax;
      $this->results['forceresizekeep'] = $forceresizekeep;
      $this->results['maximgwidth'] = $maximgwidth;
      $this->results['maximgthumbnail'] = $maximgthumbnail;
      $this->results['filters'] = $filters;
      $this->results['posts_affected'] = $posts_affected;
      $this->results['images_import'] = $images_import;
      $this->results['images_excluded'] = $images_excluded;

      // Debug
      $this->debug( $this->results['query'] );
      $this->debug( $this->results['images_import'] );
      return $this->results;
    }

    /*
    Get file name from URL

    @method get_file_names
    @since 0.1.0
    @param {String} $file_name The image's name
    @param {Array} $file_names The list of names which have been used
    @returns {Void}
    */
    public function get_file_name ( $file_name, &$file_names, $orig_file_name = '' )
    {
      if ( empty($orig_file_name) ) $orig_file_name = $file_name;

      // Strip domain/folder info to get the straight image name
      $new_file_name = $file_name = preg_replace('/^.*\/(.*\.\w+)$/', '$1', $file_name );

      // Use name which hasn't already been used
      if ( !in_array($new_file_name, $file_names) )
      {
        array_push( $file_names, $new_file_name );
        return $new_file_name;

      // Name has already been used, make an alternative version by recursively actioning method again
      } else {
        return $this->get_file_name( preg_replace( '/(.*)\.(\w+)$/', '$1-'.substr(md5($orig_file_name), 0, 8).'.$2', $new_file_name ), $file_names, $orig_file_name );
      }
    }

    /*
    Import all images

    @method import_images
    @private
    @since 0.1.0
    @returns {Void}
    */
    private function import_images ()
    {
      global $wpdb;

      require_once( ABSPATH . 'wp-admin/includes/image.php' );

      $this->check_admin();

      // Import options
      $upload_dir = wp_upload_dir();
      // $removequerystrings = isset($this->route['request']['post']['removequerystrings']) ? $this->sanitise_option_boolean($this->route['request']['post']['removequerystrings']) : FALSE;
      $overwritefiles = isset($this->route['request']['post']['overwritefiles']) ? $this->sanitise_option_boolean($this->route['request']['post']['overwritefiles']) : FALSE;
      $forceresize = $this->sanitise_option_boolean($this->route['request']['post']['forceresize']);
      $forceresizemax = $this->sanitise_option_number($this->route['request']['post']['forceresizemax']);
      $forceresizekeep = isset($this->route['request']['post']['forceresizekeep']) ? $this->sanitise_option_boolean($this->route['request']['post']['forceresizekeep']) : FALSE;
      $maximgwidth = $this->sanitise_option_number($this->route['request']['post']['maximgwidth']);
      $maximgthumbnail = $this->sanitise_option_maximgthumbnail($this->route['request']['post']['maximgthumbnail']);
      $uploaddir = isset($this->route['request']['post']['uploaddir']) ? $this->route['request']['post']['uploaddir'] : $upload_dir['subdir'];
      $upload_path = $this->get_upload_path($uploaddir);
      $max_memory = $this->to_bytes(WP_MAX_MEMORY_LIMIT);

      // Images to process
      $images_import = $this->route['request']['post']['images'];
      $images_imported = array();
      $images_excluded = array();
      $images_errored = array();

      // Import each image
      if ( count($images_import) > 0 )
      {
        foreach ( $images_import as $hash => $image )
        {
          // Gotta be right, yo
          if ( is_array($image) && array_key_exists('src', $image) && array_key_exists('as', $image) && array_key_exists('posts', $image) )
          {
            $image_processtime = time();

            // Convert posts string to array
            $image['posts'] = $this->to_array($image['posts']);

            // If `do` is disabled, then skip the image
            if ( !array_key_exists('do', $image) || !$this->sanitise_option_boolean($image['do']) )
            {
              $image['status'] = 'user_excluded';
              $images_excluded[$hash] = $image;
              continue;
            }

            $this->debug('Image to import');
            $this->debug($image);

            // Download the image to the uploads directory
            $pathinfo = pathinfo($image['as']);
            $filedir = trailingslashit('') . $pathinfo['dirname'];
            $filename = $pathinfo['basename'];
            $image_url = $this->get_upload_url( trailingslashit($filedir) . $filename, FALSE, FALSE );
            $image_path = $this->get_upload_path( trailingslashit($filedir) . $filename, FALSE, FALSE );

            // echo '<pre>';
            // var_dump(array(
            //   'filedir' => $filedir,
            //   'filename' => $filename,
            //   'image_url' => $image_url,
            //   'image_path' => $image_path,
            // ));
            // echo '</pre>';

            // Download (or reference the existing image on the server if already has downloaded)
            $image_dl = $this->download_file( $image['src'], $image_path, $overwritefiles );

            // Error downloading image
            if ( !$image_dl )
            {
              $this->debug('-- Error downloading image file. Skipping...');// echo '<pre>-- Couldn\'t download. Skipping...</pre>';
              $image['status'] = 'error_dl';
              $image['process_time'] = time() - $image_processtime;
              $images_errored[$hash] = $image;
              continue;
            }

            // Save reference to the original file
            $image['dl_as'] = $image_dl;

            // Get the image's file type
            $filetype = wp_check_filetype( $filename, NULL );

            // Check if resized version already exists
            if ( $forceresize && $forceresizemax )
            {
              if ( $forceresizekeep && file_exists( $this->get_upload_path( trailingslashit($filedir) . $pathinfo['filename'] . '_resized.' . $pathinfo['extension'], FALSE, FALSE ) ) )
              {
                $filename = $pathinfo['filename'] . '_resized.' . $pathinfo['extension'];
                $image_url = $this->get_upload_url( trailingslashit($filedir) . $filename, FALSE, FALSE );
                $image_path = $this->get_upload_path( trailingslashit($filedir) . $filename, FALSE, FALSE );
                $this->debug('-- Resized image already exists, using '.$image_path);// echo '<pre>-- Resized image already exists, using '.$image_path.'</pre>';
              }
              else
              {
                // Scale down images which are too large
                $image_size = getimagesize($image_path);

                // Waaaay too huge
                if ( ($image_size[0] * $image_size[1] * 8) > $max_memory )
                {
                  $this->debug('-- Image too large for server to process. Skipping...');// echo '<pre>-- Image too large to process. Skipping...</pre>';
                  $image['status'] = 'error_image_too_large_to_process';
                  $image['process_time'] = time() - $image_processtime;
                  $images_errored[$hash] = $image;
                  continue;
                }

                if ( $image_size && ($image_size[0] > $forceresizemax || $image_size[1] > $forceresizemax) )
                {
                  $this->debug('-- Original image too large, resizing...');// echo '<pre>-- Original image is too large, scaling down...</pre>';

                  // Figure out maximum resize size (for landscape/square)
                  $image_max_width = $forceresizemax;
                  $image_max_height = floor(($image_size[1] / $image_size[0]) * $forceresizemax);

                  // Figure out maximum resize size (for portrait)
                  if ( $image_size[1] > $image_size[0] )
                  {
                    $image_max_height = $forceresizemax;
                    $image_max_width = floor(($image_size[0] / $image_size[1]) * $forceresizemax);
                  }

                  // Resize the image using WordPress
                  $resize_image = wp_get_image_editor($image_path);
                  if ( !is_wp_error($resize_image) )
                  {
                    $resize_image->resize( $image_max_width, $image_max_height, FALSE );

                    // Rename file for saving (keeps the original)
                    if ( $forceresizekeep )
                    {
                      $filename = $pathinfo['filename'] . '_resized.' . $pathinfo['extension'];
                      $image_url = $this->get_upload_url( trailingslashit($filedir) . $filename, FALSE, FALSE );
                      $image_path = $this->get_upload_path( trailingslashit($filedir) . $filename, FALSE, FALSE );
                    }
                    $resize_image->save($image_path);
                  }
                  else
                  {
                    $this->debug('-- WordPress Image Editor can\'t load the file');// echo '<pre>-- WordPress Error: couldn\'t load image to scale!</pre>';
                    $image['status'] = 'wp_image_editor_cant_load_file';
                    $image['process_time'] = time() - $image_processtime;
                    $images_errored[$hash] = $image;
                    continue;
                  }
                }
              }
            }

            // Check if attachment with `guid` already in WP, if not add new attachment
            $sanitise_sql_url = $this->sanitise_sql($image_url);
            $check_attachment_sql = "SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment' AND guid = '$sanitise_sql_url'";
            $check_attachment = $wpdb->get_row( $check_attachment_sql, ARRAY_A );

            // Attachment already exists in the database
            if ( !is_null($check_attachment) )
            {
              $this->debug('-- Reference to image file ('.$sanitise_sql_url.') already found within attachment #'.$check_attachment['ID']);// echo '<pre>-- Attachment exists, checking attachment data '.$image_path.'</pre>';

              $attach_data = wp_get_attachment_metadata( $check_attachment['ID'], TRUE );
              $image['as'] = $image_url;

              // Generate meta data if it doesn't already exist
              if ( !$attach_data )
              {
                $this->debug('-- Generating attachment data (thumbnails)');// echo '<pre>-- Generating attachment data for '.$image_path.'</pre>';

                // Don't time out!
                set_time_limit(0);

                // Meta data
                $attach_data = wp_generate_attachment_metadata( $check_attachment['ID'], $image_path );

                // Control the attachment metadata via apply_filters: 'lvl99_image_import/attachment_metadata'
                $attach_data = apply_filters( $this->textdomain.'/attachment_metadata', $attach_data );
                wp_update_attachment_metadata( $check_attachment['ID'], $attach_data );

                $this->debug('-- Finished generating attachment data (thumbnails)');// echo '<pre>-- Generated attachment data.</pre>';
              }

              // If the image is larger than the maximum threshold, then use the maximgthumbnail file
              if ( !empty($maximgthumbnail) &&
                    $attach_data &&
                    array_key_exists('sizes', $attach_data) &&
                    array_key_exists($maximgthumbnail, $attach_data['sizes']) &&
                    array_key_exists('file', $attach_data['sizes'][$maximgthumbnail]) &&
                    $maximgwidth > 0 &&
                    $attach_data['width'] > $maximgwidth )
              {
                $this->debug('-- Referenced image exceeds `maximgwidth`. Using thumbnail `'.$maximgthumbnail.'` file '.$attach_data['sizes'][$maximgthumbnail]['file']);// echo '<pre>-- Image exceeds '.$maximgwidth.', changing reference to use '.$maximgthumbnail.': '.$attach_data['sizes'][$maximgthumbnail]['file'].'</pre>';
                $image['full_as'] = $image_url;
                $image['as'] = $this->get_upload_url( trailingslashit($filedir) . $attach_data['sizes'][$maximgthumbnail]['file'], FALSE, FALSE );
              }

              $this->debug('-- Finished importing image');// echo '<pre>-- Finished '.$image_path.'</pre>';

              // Add to the imported collection
              $image['process_time'] = time() - $image_processtime;
              $images_imported[$hash] = $image;

              // echo '<pre>Final image info: ';
              // var_dump($image);
              // echo '</pre>';

            // Attachment doesn't exist, download new file and add to the media library
            }
            else
            {
              $this->debug('-- Reference to image file ('.$sanitise_sql_url.') not found as an attachment.');// echo '<pre>-- Attachment doesn\'t exist, adding '.$image_url.' to database</pre>';

              // Build attachment info
              $image['as'] = $image_url;
              $attachment = array(
                'guid' => $image_url,
                'post_title' => sanitize_title($pathinfo['filename']),
                'post_content' => '',
                'post_mime_type' => $filetype['type'],
                'post_status' => 'inherit',
              );

              // Control the attachment info via apply_filters: 'lvl99_image_import/attachment'
              $attachment = apply_filters( $this->textdomain.'/attachment', $attachment );

              // Check if src exists as attachment guid
              $sanitise_sql_url = $this->sanitise_sql($image['src']);
              $check_attachment_sql = "SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment' AND guid = '$sanitise_sql_url'";
              $check_attachment = $wpdb->get_row( $check_attachment_sql, ARRAY_A );

              // Found attachment linking to the remote one, update that (specify ID, or else it will insert a new attachment)
              if ( !is_null($check_attachment) )
              {
                $this->debug( '-- Attachment found with guid that matches src, gonna update attachment #' . $check_attachment['ID']. ' to update instead of creating a new attachment' );
                $attachment['ID'] = $check_attachment['ID'];

                // Since we've updated it here already, remove the attachment post ID from the $images['posts'] array so it doesn't get updated later during the `update_image_references`
                $in_posts = array_search( $attachment['ID'], $image['posts'] );
                if ( $in_posts !== FALSE )
                {
                  $this->debug( '-- Removed attachment #'.$check_attachment['ID'].' from image\'s posts to avoid updating later with wrong file' );
                  array_splice( $image['posts'], $in_posts, 1 );
                }
              }

              // Insert/update image into WP Media Library
              $parent_post_id = 0;
              if ( count($image['posts']) > 0 && $image['posts'][0] != $check_attachment['ID'] ) $parent_post_id = $image['posts'][0]; // Attach to first related post
              $attach_id = wp_insert_attachment( $attachment, $image_path, $parent_post_id );

              // Success
              if ( $attach_id > 0 )
              {
                $this->debug('-- Generating attachment data (thumbnails)');// echo '<pre>-- Generating attachment data for '.$image_path.'</pre>';

                // Don't time out!
                set_time_limit(0);

                // Meta data
                $attach_data = wp_generate_attachment_metadata( $attach_id, $image_path );

                // Control the attachment metadata via apply_filters: 'lvl99_image_import/attachment_metadata'
                $attach_data = apply_filters( $this->textdomain.'/attachment_metadata', $attach_data );
                wp_update_attachment_metadata( $attach_id, $attach_data );

                $this->debug('-- Finished generating attachment data (thumbnails)');// echo '<pre>-- Generated attachment data.</pre>';

                // If the image is larger than the maximum threshold, then use the maximgthumbnail file
                if ( !empty($maximgthumbnail) &&
                      $attach_data &&
                      array_key_exists('sizes', $attach_data) &&
                      array_key_exists($maximgthumbnail, $attach_data['sizes']) &&
                      array_key_exists('file', $attach_data['sizes'][$maximgthumbnail]) &&
                      $maximgwidth > 0 &&
                      $attach_data['width'] > $maximgwidth )
                {
                  $this->debug('-- Referenced image exceeds `maximgwidth`. Using thumbnail `'.$maximgthumbnail.'` file '.$attach_data['sizes'][$maximgthumbnail]['file']);// echo '<pre>-- Image exceeds '.$maximgwidth.', changing reference to use '.$maximgthumbnail.': '.$attach_data['sizes'][$maximgthumbnail]['file'].'</pre>';
                  $image['full_as'] = $image_url;
                  $image['as'] = $this->get_upload_url( trailingslashit($filedir) . $attach_data['sizes'][$maximgthumbnail]['file'], FALSE, FALSE );
                }

                $this->debug('-- Finished importing image');// echo '<pre>-- Finished '.$image_path.'</pre>';

                // Add to the imported collection
                $image['process_time'] = time() - $image_processtime;
                $images_imported[$hash] = $image;

                // echo '<pre>Final image info: ';
                // var_dump($image);
                // echo '</pre>';

              // Error
              } else {
                $this->debug('-- WordPress errored when creating the new attachment');// echo '<pre>-- WordPress couldn\'t create the new attachment.</pre>';
                $image['status'] = 'error_wp_insert_attachment';
                $image['process_time'] = time() - $image_processtime;
                $images_errored[$hash] = $image;
              }
            }
          } else {
            $this->debug('-- Incorrectly formatted `$image` array passed');// echo '<pre>-- Submitted image information was formatted incorrectly.</pre>';
            if ( !is_array($image) ) $image = array('image' => $image);

            $image['status'] = 'error_incorrect_image_array_format';
            $image['process_time'] = time() - $image_processtime;
            $images_errored[$hash] = $image;
          }

          $this->debug('-- Finished processing image data');
          $this->debug($image);
        }
      }

      // Update results
      // $this->results['removequerystrings'] = $removequerystrings;
      $this->results['overwritefiles'] = $overwritefiles;
      $this->results['forceresize'] = $forceresize;
      $this->results['forceresizemax'] = $forceresizemax;
      $this->results['forceresizekeep'] = $forceresizekeep;
      $this->results['max_memory'] = $max_memory;
      $this->results['maximgwidth'] = $maximgwidth;
      $this->results['maximgthumbnail'] = $maximgthumbnail;
      $this->results['uploaddir'] = $uploaddir;
      $this->results['upload_path'] = $upload_path;
      $this->results['images_imported'] = $images_imported;
      $this->results['images_excluded'] = $images_excluded;
      $this->results['images_errored'] = $images_errored;
    }

    /*
    Update all image references

    @method update_image_references
    @private
    @since 0.1.0
    @returns {Void}
    */
    private function update_image_references ()
    {
      global $wpdb;

      $this->check_admin();

      // Images to change within posts
      $images = $this->results['images_imported'];
      $images_imported = array();
      $images_excluded = $this->results['images_excluded'];
      $images_errored = $this->results['images_errored'];
      $images_search = array();
      $images_replace = array();
      $posts_search = array();
      $posts_affected = array();
      $posts_excluded = array();

      // Iterate over images to get search/replace terms and posts to update
      if ( count($images > 0) )
      {
        foreach ( $images as $hash => $image )
        {
          // Incorrectly formatted array object
          if ( !is_array($image) || !array_key_exists('src', $image) || !array_key_exists('as', $image) || !array_key_exists('posts', $image) )
          {
            if ( !is_array($image) ) $image = array('image' => $image);
            $image['status'] = 'error_incorrect_image_array_format';
            $images_errored[$hash] = $image;
            continue;
          }

          // Strip query vars
          $image_search = preg_replace( '/\?.*$/', '', $image['src'] );

          // Make an array of all images src and as to search/replace with
          $images_search[$hash] = '/' . preg_quote($image_search, '/') . '\??[^"]*/'; // Add query var match to strip
          $images_replace[$hash] = $image['as'];

          // Make an array of affected posts and then iterate over that, instead of per image
          $image['posts'] = $this->to_array($image['posts']);
          foreach ( $image['posts'] as $post_id )
          {
            if ( !in_array($post_id, $posts_search) )
            {
              $posts_search[] = $post_id;
            }
          }

          $images_imported[$hash] = $image;
        }

        // echo '<pre>';
        // var_dump( array(
        //   'images_search' => $images_search,
        //   'images_replace' => $images_replace,
        // ) );
        // echo '</pre>';
        // $this->debug( 'posts_search' );
        // $this->debug( $posts_search );

        // Iterate over all posts affected to update
        if ( count($posts_search) > 0 )
        {
          $query = "SELECT ID, post_type, post_content, guid FROM $wpdb->posts WHERE $wpdb->posts.ID IN (".implode( ",", $posts_search).")";
          $posts_fetched = $wpdb->get_results( $query, ARRAY_A );
          $this->results['query'] = $query;

          // Update the posts' content/guid values
          if ( count($posts_fetched) > 0 )
          {
            foreach ( $posts_fetched as $post )
            {
              $this->debug( 'Updating ' . $post['post_type'] . '...' );
              $this->debug( array(
                'ID' => $post['ID'],
                'post_type' => $post['post_type'],
                'post_content' => str_replace( array('<', '>'), array('&lt;', '&gt'), $post['post_content'] ),
                'guid' => $post['guid'],
              ) );

              // Update post info
              $update_post = array(
                'ID' => $post['ID'],
              );

              // Attachment updates guid
              if ( $post['post_type'] == 'attachment' )
              {
                foreach( $images_search as $hash => $image_search )
                {
                  // Do the following only if it matches
                  if ( preg_match( $image_search, $post['guid'] ) !== FALSE )
                  {
                    $this->debug( '-- Found a match to '.$image_search."\n".'   against '.$post['guid']."\n".'   in attachment #' . $post['ID'] );

                    // Do special stuff for attachments: get the image itself and if there's a `full_as`, use that instead of the regular `as`
                    if ( isset($images_imported[$hash]) )
                    {
                      $image = $images_imported[$hash];

                      // Use original, not thumbnail
                      if ( isset($image['full_as']) )
                      {
                        $this->debug( '-- Using `full_as` instead of `as`: ' . $image['full_as'] );
                        $guid = $image['full_as'];

                      // Use given `as` (hopefully not a thumbnail!)
                      } else {
                        $this->debug( '-- Using `as`: ' . $image['as'] );
                        $guid = $image['as'];
                      }

                      break;
                    }
                  }
                }

                $this->debug( '-- guid set to: ' . $guid );

                // Update the guid
                if ( isset($guid) && !is_null($guid) )
                {
                  $update_post['guid'] = $guid;
                  $this->debug( '-- Updated attachment #'.$post['ID'].' guid from '.$post['guid']."\n".'   to ' . $guid );
                }

              // All other posts
              } else {
                $post_content = preg_replace( $images_search, $images_replace, $post['post_content'] );
                if ( !is_null($post_content) ) $update_post['post_content'] = $post_content;
              }

              // Ensure guid or post_content has been updated
              if ( ($post['post_type'] == 'attachment' && isset($update_post['guid'])) ||
                   ($post['post_type'] != 'attachment' && isset($update_post['post_content'])) )
              {
                // @note If an attachment updating guid, have to do some sneaky stuff
                // @note This is pretty dodgy as external files shouldn't be the guid for purposes of generating metadata,
                //       however it's a vital step if wanting to automate downloading external files to the server/database.
                //       Ya dig?
                if ( $post['post_type'] == 'attachment' && isset($update_post['guid']) )
                {
                  $sanitise_sql_id = intval($update_post['ID']);
                  $sanitise_sql_url = $this->sanitise_sql($update_post['guid']);
                  $update_attachment = $wpdb->update(
                    $wpdb->posts,
                    array( // Update
                      'guid' => $sanitise_sql_url,
                    ),
                    array( // Where
                      'ID' => $sanitise_sql_id,
                    ),
                    array( // Update data format
                      '%s',
                    ),
                    array( // Where data format
                      '%d',
                    )
                  );

                  // Success
                  if ( $update_attachment )
                  {
                    $this->debug( '-- Successfully updated guid to ' . $sanitise_sql_url . ' in attachment #' . $sanitise_sql_id );
                    $posts_affected[] = $sanitise_sql_id;

                  // Error
                  } else {
                    $this->debug( '-- Couldn\'t update attachment #'.$sanitise_sql_id.': $wpdb->update failed' );
                    $this->admin_error( 'Couldn\'t update image reference for attachment #'.$update_post['ID'] );
                    $posts_excluded[] = $post_id;
                  }

                } else {
                  // Success
                  if ( wp_update_post($update_post) )
                  {
                    $this->debug( '-- Successfully updated image references within post content' );
                    $posts_affected[] = $post_id;

                  // Error
                  } else {
                    $this->debug( '-- Couldn\'t update post #'.$post_id.': wp_update_post failed' );
                    $this->admin_error( 'Couldn\'t update image reference for post '.$post_id );
                    $posts_excluded[] = $post_id;
                  }
                }

              // Error
              } else {
                $this->debug( '-- Couldn\'t update post #'.$post_id.': $update_post missing `guid` and `post_content`' );
                $this->admin_error( 'Couldn\'t update image reference for post '.$post_id );
                $posts_excluded[] = $post_id;
              }
            }
          }
          else
          {
            $this->admin_error( 'Couldn\'t find any image references within posts to change' );
            error_log('LVL99 Image Import Error: no posts fetched to change image references within. Could have given IDs of posts which were deleted.');
          }
        }
      }

      // Update results
      $this->results['images_search'] = $images_search;
      $this->results['images_replace'] = $images_replace;
      $this->results['images_imported'] = $images_imported;
      $this->results['images_excluded'] = $images_excluded;
      $this->results['images_errored'] = $images_errored;
      $this->results['posts_search'] = $posts_search;
      $this->results['posts_affected'] = $posts_affected;
      $this->results['posts_excluded'] = $posts_excluded;
    }

    /*
    Download the file. If successful, returns the string of where the image is located, else returns `FALSE`.

    @method download_file
    @private
    @since 0.1.0
    @param {String} $file_url The URL to download the image
    @param {String} $file_dest The path to put the image
    @param {Boolean} $overwrite_file Whether to redownload the image or not
    @returns {Mixed} {String} the files's uploaded location, or {Boolean} FALSE on error
    */
    private function download_file ( $file_url, $file_dest, $overwrite_file = FALSE )
    {
      // Only download if it doesn't already exist
      if ( $overwrite_file || (!$overwrite_file && !file_exists($file_dest)) )
      {
        // echo '<pre>-- Downloading '.$file_url.' to '.$file_dest.'</pre>';

        // Delete the original file to "overwrite"
        if ( $overwrite_file && file_exists($file_dest) )
        {
          unlink($file_dest);
          // echo '<pre>-- Deleted original file to re-download</pre>';
        }

        // Make necessary directories if you gotta
        $pathinfo = pathinfo($file_dest);
        if ( !file_exists($pathinfo['dirname']) ) mkdir($pathinfo['dirname'], 0755, TRUE );

        // Open the file for the destination to write to
        $fp = fopen($file_dest, 'wb');

        // Download the file!
        $ch = curl_init($file_url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_exec($ch);

        // Error
        if ( curl_error($ch) )
        {
          // echo '<pre>-- Error downloading '.$file_url.' to '.$file_dest.'</pre>';
          $this->admin_error('Couldn\'t download file: <code>'.$file_url.'</code>');
          error_log( 'cURL error downloading file: ' . $file_url );
          curl_close($ch);
          fclose($fp);
          unlink($fp); // Delete temp file
          return FALSE;

        // Success
        } else {
          // echo '<pre>-- File downloaded to '.$file_dest.'</pre>';
          curl_close($ch);
          fclose($fp);

          return $file_dest;
        }

      // File already exists at the destination, so use that
      } else {
        // echo '<pre>-- File already exists at '.$file_dest.'</pre>';
        return $file_dest;
      }
    }

    /*
    Convert string to array

    @method to_array
    @since 0.1.0
    @param {String} $input The string to convert to an array
    @param {String} $delimiter The string to use as a delimiter (defaults to `,`)
    @returns {Array}
    */
    public function to_array ( $input, $delimiter = ',' )
    {
      $output = array();

      // Convert string to array
      if ( is_string($input) )
      {
        // Check for delimiter and explode if it exists
        if ( strstr($input, $delimiter) !== FALSE )
        {
          $output = explode($delimiter, $input);

        // No delimiter, assume single-item array
        } else {
          $output = array($input);
        }

      // Already array
      } else if ( is_array($input) ) {
        $output = $input;
      }

      return $output;
    }

    /*
    Output something to the debug output

    @method debug
    @private
    @since 0.1.0
    @param {Mixed} $input The input to output to the debug
    @returns {Void}
    */
    private function debug ( $input )
    {
      $trace = debug_backtrace();

      $this->results['debug'][] = array(
        '_time' => time(),
        '_output' => $input,
        '_callee_method' => $trace[1]['function'],
      );
    }

    /*
    Process path/url string

    @method process_path_url
    @since 0.1.0
    @param {String} $input The path/URL string to process
    @param {Boolean} $strip_leading_slash Whether to strip the leading slash or not
    @param {Boolean} $strip_filename Whether to strip the filename or not
    @returns {String}
    */
    public function process_path_url ( $input, $strip_leading_slash = TRUE, $strip_filename = FALSE )
    {
      // Get filename
      if ( !empty($input) )
      {
        $filename = '';
        $pathinfo = pathinfo($input);

        // Strip filename
        if ( array_key_exists('extension', $pathinfo) )
        {
          $input = str_replace( $pathinfo['basename'], '', $input );
        }

        // Sanitise relative folder stuff
        $input_sanitise = array(
          trailingslashit('..'),
          trailingslashit('.'),
          trailingslashit('').'..',
          trailingslashit('').'.',
        );
        $input = str_replace( $input_sanitise, '', $input );

        // Remove first slash (because it'll be appended to other paths/URLs with trailing slash)
        if ( $strip_leading_slash )
        {
          $input = preg_replace( '/^[\/\\\\]+/', '', $input );
        }

        // Add a trailing slash
        if ( !empty($input) ) $input = trailingslashit($input);

        // Put the filename back
        if ( array_key_exists('extension', $pathinfo) && !$strip_filename )
        {
          $input .= $pathinfo['basename'];
        }
      }

      return $input;
    }

    /*
    Get the full upload path

    @method get_upload_path
    @since 0.1.0
    @returns {String}
    */
    private function get_upload_path ( $input = '', $strip_leading_slash = FALSE, $strip_filename = FALSE )
    {
      $upload_dir = wp_upload_dir();
      $upload_path = $this->process_path_url( $upload_dir['basedir'] . $input, $strip_leading_slash, $strip_filename );
      return $upload_path;
    }

    /*
    Get the relative upload path

    @method get_relative_upload_path
    @since 0.1.0
    @returns {String}
    */
    protected function get_relative_upload_path ( $input = '' )
    {
      $upload_path = str_replace( WP_CONTENT_DIR, '', $this->get_upload_path( $input, FALSE, TRUE ) );
      return $upload_path;
    }

    /*
    Get the full upload URL

    @method get_upload_url
    @since 0.1.0
    @returns {String}
    */
    private function get_upload_url ( $input = '', $strip_leading_slash = FALSE, $strip_filename = FALSE )
    {
      $upload_dir = wp_upload_dir();
      $upload_url = $this->process_path_url( $upload_dir['baseurl'] . $input, $strip_leading_slash, $strip_filename );
      return $upload_url;
    }

    /*
    Get the relative upload URL

    @method get_relative_upload_url
    @since 0.1.0
    @returns {String}
    */
    protected function get_relative_upload_url ( $input = '' )
    {
      $upload_url = str_replace( WP_CONTENT_URL, '', $this->get_upload_url( $input, FALSE, TRUE ) );
      return $upload_url;
    }

    /*
    Returns the filesize string (e.g. 256M) to integer in bytes form
    See: http://php.net/manual/en/function.ini-get.php

    @method to_bytes
    @since 0.1.0
    @returns {Number}
    */
    public function to_bytes ( $input )
    {
      $input = trim($input);
      $last = strtolower($input[strlen($input)-1]);

      switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $input *= 1024;
        case 'm':
            $input *= 1024;
        case 'k':
            $input *= 1024;
      }

      return $input;
    }

    /*
    Finds any broken links within attachments

    @method scan_brokenlinks
    @returns {Void}
    */
    private function scan_brokenlinks ()
    {
      global $wpdb;

      $this->check_admin();

      $items_scanned = array();

      // Query
      $query = "SELECT ID, guid, post_type, post_mime_type FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment'";
      $attachments = $wpdb->get_results( $query, ARRAY_A );
      $upload_dir = wp_upload_dir();

      // echo '<pre>';
      // var_dump($upload_dir);
      // echo '</pre>';
      // exit();

      // Attachments
      if ( count($attachments) > 0 )
      {
        foreach ( $attachments as $attachment )
        {
          // Check if file is local and available
          if ( strstr( $attachment['guid'], $upload_dir['baseurl'] ) == FALSE )
          {
            // External file to download
            $this->debug('Found external attachment link to download: ID = '.$attachment['ID'].', guid = '.$attachment['guid']);
            $attachment['status'] = 'external';
            $items_scanned[] = $attachment;
          }
          else
          {
            $check_file = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $attachment['guid'] );

            if ( !file_exists($check_file) )
            {
              // Download file
              // $file_dl = $this->download_file( $attachment['guid'], $check_file);
              $this->debug('Found broken attachment link: ID = '.$attachment['ID'].', guid = '.$attachment['guid']);
              $attachment['status'] = 'broken';
              $items_scanned[] = $attachment;
            }
          }
        }
      }

      $this->results['query'] = $query;
      $this->results['items_scanned'] = $items_scanned;
    }

    /*
    Fixes any broken links or downloads external links within attachments

    @method fix_brokenlinks
    @returns {Void}
    */
    private function fix_brokenlinks ()
    {
      $this->check_admin();

      // Get the scanned and changed items to process
      $items_scanned = $this->route['request']['post']['itemsscanned'];
      $items_completed = array();
      $items_errored = array();
      $items_excluded = array();
      $items_search = array();
      $items_replace = array();

      $upload_dir = wp_upload_dir();

      // Process scanned items
      if ( count($items_scanned) > 0 )
      {
        foreach ( $items_scanned as $item )
        {
          $process_time = time();
          $this->debug( 'Submitted link to fix/download:' );
          $this->debug( $item );

          // Incorrectly formatted item
          if ( !is_array($item) || !array_key_exists('src', $item) || !array_key_exists('as', $item) )
          {
            $this->debug( '-- Incorrect item array format. Skipping...' );
            if ( is_array($item) )
            {
              $item = array(
                'item' => $item,
              );
            }
            $item['status'] = 'error_incorrect_item_array_format';
            $item['process_time'] = time() - $process_time;
            $items_errored[] = $item;
            $this->debug( $item );
            continue;
          }

          // User excluded item (unchecked in list)
          if ( !array_key_exists('do', $item) )
          {
            $this->debug( '-- User excluded. Skipping...' );
            $item['status'] = 'user_excluded';
            $item['process_time'] = time() - $process_time;
            $items_excluded[] = $item;
            $this->debug( $item );
            continue;
          }

          // Check if file exists, if hosted on local server
          $pathinfo = pathinfo($item['as']);
          $filedir = trailingslashit('') . $pathinfo['dirname'];
          $filename = $pathinfo['basename'];
          $file_path = $this->get_upload_path( trailingslashit($filedir) . $filename );
          $file_url = $this->get_upload_url( trailingslashit($filedir) . $filename );

          // -- File doesn't exist, attempt to download
          if ( !file_exists($file_path) )
          {
            // Download file
            $file_dl = $this->download_file( $item['as'], $file_path, FALSE );

            // Error downloading
            if ( !$file_dl )
            {
              $this->debug( '-- Error downloading file. Skipping...' );
              $item['status'] = 'error_dl';
              $item['process_time'] = time() - $process_time;
              $items_errored[] = $item;
              $this->debug( $item );
              continue;
            }

            $file['dl_as'] = $file_url;
          }

          // Does file already exist as another attachment?
          $sanitise_sql_url = $this->sanitise_sql_url($file_url);
          $query = "SELECT ID, guid, post_mime_type FROM $wpdb->posts WHERE $wpdb->posts.guid = '$sanitise_sql_url'";
          $check_attachment = $wpdb->get_row( $query, ARRAY_A );

          // Attachment exists with that URL, refer to that one
          if ( !is_null($check_attachment) )
          {
            $this->debug( '-- Found attachment already using URL given. Using attachment #'.$check_attachment['ID'] );
            continue;

          // URL doesn't exist in database already, update attachment
          }
          else
          {
            // Build new attachment data to update
            $file_type = wp_check_filetype( $filename, NULL );
            $attachment = array(
              'ID' => $item['ID'],
              'guid' => $file_url,
              'post_mime_type' => $filetype['type'],
            );

            // Update the post's `guid`
            $attach_id = wp_insert_attachment( $attachment, $file_path );
            if ( $attach_id > 0 )
            {
              // Update attachment metadata
              $attach_data = wp_get_attachment_metadata( $item['ID'], TRUE );
              if ( !$attach_data )
              {
                $this->debug( '-- Generating metadata (thumbnails)' );

                // Don't time out!
                set_time_limit(0);

                // Meta data
                $attach_data = wp_generate_attachment_metadata( $item['ID'], $file_path );

                // Control the attachment metadata via apply_filters: 'lvl99_image_import/attachment_metadata'
                $attach_data = apply_filters( $this->textdomain.'/attachment_metadata', $attach_data );
                wp_update_attachment_metadata( $item['ID'], $attach_data );

                $this->debug( '-- Finished generating metadata (thumbnails)' );
              }

              $this->debug( '-- Successfully updated attachment #' . $attach_id );
              $item['process_time'] = time() - $process_time;
              $items_completed[] = $item;
            }
            else
            {
              $this->debug( '-- WordPress errored updating attachment #' . $attach_id );
              $item['process_time'] = time() - $process_time;
              $items_errored[] = $item;
            }
          }

          $this->debug( $item );
        }
      }

      // Find references within posts to previous attachments and replace
      // @TODO

      $this->results['items_excluded'] = $items_excluded;
      $this->results['items_errored'] = $items_errored;
      $this->results['items_completed'] = $items_completed;
    }

  }
}

// The instance of the plugin
$lvl99_image_import = new LVL99_Image_Import();

