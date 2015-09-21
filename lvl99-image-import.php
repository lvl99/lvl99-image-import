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
      'importtype' => FALSE, // The type of importation method: `medialibrary` or `change`
      'filters' => array(), // Filters used in the results
      'posts_affected' => array(), // Array of posts that have image references
      'posts_excluded' => array(), // Array of posts that were excluded from updating
      'images_import' => array(), // Array of images to import
      'images_excluded' => array(), // Array of images excluded from import
      'images_imported' => array(), // Array of images that were successfully imported
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
        wp_enqueue_style('thickbox');
        wp_enqueue_style( $this->textdomain, plugins_url( 'css/lvl99-image-import.css', __FILE__ ), FALSE, $this->version, 'all' );

        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_script( $this->textdomain, plugins_url( 'js/lvl99-image-import.js', __FILE__ ), TRUE, $this->version, array('jquery') );

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
      {
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
        // Initialise each .lvl99-sortable
        <?php /* foreach( $sort_fields as $field ) : ?>
        jQuery('#<?php echo $field; ?>-list.lvl99-sortable').sortable({
          items: '> li',
          handle: '.lvl99-sortable-handle'
        });
        <?php endforeach; */ ?>

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
<?php
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
      // Default options
      $this->default_options = array();

      // Get the saved options
      if ( count($this->default_options) > 0 )
      {
        foreach ( $this->default_options as $name => $option  )
        {
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
    protected function sanitise_option_number ( $input )
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
    protected function sanitise_option_url ( $input )
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
    protected function sanitise_option_boolean ( $input )
    {
      // ChromePhp::log( 'sanitise_option_boolean' );
      // ChromePhp::log( $input );

      if ( $input == 1 || $input == 'true' || $input == TRUE || $input == 'TRUE' || $input == '1' ) return TRUE;
      if ( $input == 0 || $input == 'false' || $input == FALSE || $input == 'FALSE' || $input == '0' || empty($input) ) return FALSE;
      return (bool) $input;
    }

    /*
    Sanitises SQL, primarily by looking for specific SQL commands

    @method sanitise_sql
    @since 0.1.0
    @param {String} $input The string to sanitise
    @returns {String}
    */
    public function sanitise_sql ( $input )
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
      // if ( array_key_exists($option_name, $this->default_options) )
      // {
        return $this->textdomain . '_' . $option_name;
      // }
      // return '';
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

      if ( count($options > 0) )
      {
        foreach( $options as $name => $option )
        {
          // Visible field
          $is_visible = array_key_exists('visible', $option) ? $option['visible'] : TRUE;
          if ( $option['field_type'] == 'hidden' ) $is_visible = FALSE;

          // Singular field (e.g. single checkbox or radio)
          $is_singular = ($option['field_type'] == 'checkbox' || $option['field_type'] == 'radio') && !array_key_exists('values', $option);

          // Sortable fields
          $is_sortable = ( $option['field_type'] == 'checkbox' && array_key_exists('sortable', $option) && !$is_singular ? $option['sortable'] : FALSE );

          // Default values for the option
          $option_value = !empty($this->options[$name]) ? $this->options[$name] : $option['default'];

          if ( $is_visible )
          {
  ?>
          <div class="lvl99-plugin-option <?php if ($is_sortable && $option['field_type'] != 'checkbox' && $option['field_type'] != 'radio') : ?>lvl99-draggable lvl99-sortable lvl99-sortable-handle<?php endif; ?>">

            <?php do_action( 'lvl99_plugin_option_field_footer_' . $name, '' ); ?>

            <?php if ( !$is_singular ) : ?>
            <label for="<?php echo $this->get_field_id($name); ?>" class="lvl99-plugin-option-label"><?php echo $option['label']; ?></label>
            <?php endif; ?>

            <?php if ( isset($option['help']) ) : ?>
            <div class="lvl99-plugin-option-help">
              <?php echo $option['help']; ?>
            </div>
            <?php endif; ?>

            <?php if ( $option['field_type'] == 'text' ) : ?>
              <input id="<?php echo $this->get_field_id($name); ?>" type="text" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo esc_attr($option_value); ?>" class="widefat" />

            <?php elseif ( $option['field_type'] == 'number' ) : ?>
              <input id="<?php echo $this->get_field_id($name); ?>" type="number" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo esc_attr($option_value); ?>" class="widefat" />

            <?php elseif ( $option['field_type'] == 'email' ) : ?>
              <input id="<?php echo $this->get_field_id($name); ?>" type="email" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo esc_attr($option_value); ?>" class="widefat" />

            <?php elseif ( $option['field_type'] == 'select' ) : ?>
              <select id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>">
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
              <ul id="<?php echo $this->get_field_id($name); ?>-list">
                <?php foreach( $option['values'] as $value ) : ?>
                <?php if ( is_array($value) ) : ?>
                  <li>
                    <label class="lvl99-plugin-option-value">
                      <input type="radio" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo $value['value']; ?>" <?php if ( $option_value == $value['value'] ) : ?>checked="checked"<?php endif; ?> />
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
                      <input type="radio" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo ( $is_singular ? 'true' : esc_attr($value) ); ?>" <?php if ( $option_value == $value ) : ?>checked="checked"<?php endif; ?> />
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
              <ul id="<?php echo $this->get_field_id($name); ?>-list" class="<?php if ($is_sortable) : ?>lvl99-sortable<?php endif; ?>">
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
                  <input type="hidden" id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo esc_attr($option_value); ?>" />
                <?php endif; ?>

                <?php foreach ( $option_values as $value ) : ?>
                  <?php if ( is_array($value) ) : ?>
                  <li <?php if ( $is_sortable ) : ?>class="ui-draggable ui-sortable"<?php endif; ?>>
                    <?php if ($is_sortable) : ?><span class="fa-arrows-v lvl99-sortable-handle"></span><?php endif; ?>
                    <label class="lvl99-plugin-option-value">
                      <input type="checkbox" name="<?php if ( $is_sortable ) : echo esc_attr($name).'['.esc_attr($value['value']).']'; else : echo $this->get_field_name($name); endif; ?>" value="true" <?php if ( stristr($option_value, $value['value'])) : ?>checked="checked"<?php endif; ?> />
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
                      <input type="checkbox" name="<?php if ( $is_sortable ) : echo esc_attr($name).'['.esc_attr($value['value']).']'; else : echo $this->get_field_name($name); endif; ?>" value="<?php echo ( $is_singular ? 'true' : esc_attr($value) ); ?>" <?php if ( !empty($option_value) && $option_value == $value ) : ?>checked="checked"<?php endif; ?> />
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
                <input type="hidden" id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo esc_attr($option_value); ?>" />
                <p><img src="<?php echo esc_url($option_value); ?>" style="max-width: 100%; <?php if ( $option_value == "" ) : ?>display: none<?php endif; ?>" /></p>
              </a>
              <a href="javascript:void(0);" class="remove_file_button button" <?php if ( $option_value == "" ) : ?>style="display:none"<?php endif; ?>>Remove image</a>

            <?php elseif ( $option['field_type'] == 'textarea' ) : ?>
              <textarea id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>" class="widefat"><?php echo $option_value; ?></textarea>

            <?php endif; ?>

            <?php if ( isset($option['help_after']) ) : ?>
            <div class="lvl99-plugin-option-help">
              <?php echo $option['help_after']; ?>
            </div>
            <?php endif; ?>

            <?php do_action( 'lvl99_plugin_option_field_footer_' . $name, '' ); ?>

            <?php if ( $is_sortable ) : ?>
            <script type="text/javascript">
              jQuery(document).ready( function () {
                jQuery('#<?php echo $this->get_field_id($name); ?>-list.lvl99-sortable').sortable({
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
          <input type="hidden" id="<?php echo $this->get_field_id($name); ?>" name="<?php echo $this->get_field_name($name); ?>" value="<?php echo esc_attr($option_value); ?>" />
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
      if ( isset($_REQUEST[$this->textdomain]) && !empty($_REQUEST[$this->textdomain]) )
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

        // Build and set the route to the class for later referral when running the route's method
        $this->route = array(
          'method' => 'route_' . preg_replace( '/[^a-z0-9_]+/i', '', $_REQUEST[$this->textdomain] ),
          'referrer' => $_SERVER['HTTP_REFERER'],
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
        $this->admin_error( sprintf( __('Invalid route method was called: <strong><code>%s</code></strong>', $this->textdomain), $this->route['method'] ) );
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
      } else if ( $this->results['importtype'] == 'change' ) {
        $this->results['images_imported'] = $this->route['request']['post']['images'];
      }

      // If found images, process them depending on filters
      if ( isset($this->results['images_imported']) && count($this->results['images_imported']) > 0 )
      {
        // Update the image references
        $this->update_image_references();
        $_REQUEST['action'] = 'imported';

      } else {
        $this->admin_error( 'Affected no images!' );
        $_REQUEST['action'] = 'scan';
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
    Shows the admin options page.

    @method view_admin_options
    @since 0.1.0
    @returns {Void}
    */
    public function view_admin_options ()
    {
      $this->check_admin();

      $route = $this->route;
      // include( trailingslashit($this->plugin_dir) . 'views/admin-options.php' );
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
        // '<a href="options-general.php?page=lvl99-image-import-options">Options</a>',
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
      // add_options_page(
      //   __('Image Import', $this->textdomain),
      //   __('Image Import', $this->textdomain),
      //   'activate_plugins',
      //   'lvl99-image-import-options',
      //   array( $this, 'view_admin_options' )
      // );
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
        $query .= " WHERE $wpdb->posts.post_type IN ('" . implode("', '", $posttypes) . "')";
      }

      // Get all post entries to then scan through
      $results = $wpdb->get_results( $query, ARRAY_A );
      $images = array();
      $image_names = array();
      $images_import = array();
      $images_excluded = array();
      $posts_affected = array();
      $importtype = $this->route['request']['post']['importtype'];
      $filters = !empty($this->route['request']['post']['filters']) ? $this->route['request']['post']['filters'] : array();
      $count_image_references = 0;

      // Process filters with regex input
      if ( count($filters) > 0 )
      {
        foreach ( $filters as $num => $filter )
        {
          // Empty input? Error
          if ( empty($filter['input']) )
          {
            $this->admin_error( 'Empty filter input detected.' );
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
      if ( $importtype == 'change' )
      {
        $count_replace_filters = 0;
        foreach ( $filters as $filter )
        {
          if ( $filter['method'] == 'replace' ) $count_replace_filters++;
        }

        if ( $count_replace_filters == 0 )
        {
          $this->admin_error( __('&quot;Change Image Links&quot; was selected but no &quot;Search &amp; Replace&quot; filter was declared', $this->textdomain) );
          $this->results['images'] = array();
          return $this->results;
        }
      }

      // Get all the images first
      if ( count($results) )
      {
        foreach ( $results as $result )
        {
          // Check external attachments' guid field
          if ( $result['post_type'] == 'attachment' )
          {
            // Only include
            if ( stristr($result['post_mime_type'], 'image') == FALSE ) continue;

            // Match all image URLS
            $found = preg_match_all( '/(https?\:\/\/.*)/i', $result['guid'], $matches );

          // Assume searching post_content for all other posts
          }
          else
          {
            $found = preg_match_all( '/<img.*src="([^"]+)".*\/>/i', $result['post_content'], $matches );
          }

          if ( $found > 0 && count($matches[1]) > 0 )
          {
            // Strip WordPress.com query vars
            foreach( $matches[1] as $num => $image_url )
            {
              $count_image_references++;
              $image_url = preg_replace( '/\?.*$/', '', trim($image_url) );
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
              if ( !in_array($result['ID'], $images[$hash]['posts']) )
              {
                array_push( $images[$hash]['posts'], $result['ID'] );
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
                    $this->debug( array(
                      'filter_include_input' => $filter['input'],
                    ) );

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
                    $this->debug( array(
                      'filter_exclude_input' => $filter['input'],
                    ) );

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
                  $this->debug( array(
                    'filter_replace_input' => $filter['input'],
                  ) );

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

          // If importtype is media library, strip all the info from the as field, as we only want the file name
          if ( $importtype == 'medialibrary' && $image_info['as'] == $image['src'] )
          {
            $image_info['as'] = $this->get_image_name( $image['src'], $image_names );
          }

          if ( $image_include )
          {
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
      $this->results['filters'] = $filters;
      $this->results['posts_affected'] = $posts_affected;
      $this->results['images_import'] = $images_import;
      $this->results['images_excluded'] = $images_excluded;
      return $this->results;
    }

    /*
    Get image names

    @TODO basename() does similar things, so may need to refactor to make simpler

    @method get_image_names
    @since 0.1.0
    @param {String} $image_name The image's name
    @param {Array} $image_names The list of names which have been used
    @returns {Void}
    */
    public function get_image_name ( $image_name, &$image_names, $orig_image_name = '' )
    {
      if ( empty($orig_image_name) ) $orig_image_name = $image_name;

      // Strip domain/folder info to get the straight image name
      $new_image_name = $image_name = preg_replace('/^.*\/(.*\.\w+)$/', '$1', $image_name );

      // Use name which hasn't already been used
      if ( !in_array($new_image_name, $image_names) )
      {
        array_push( $image_names, $new_image_name );
        return $new_image_name;

      // Name has already been used, make an alternative version by recursively actioning method again
      } else {
        return $this->get_image_name( preg_replace( '/(.*)\.(\w+)$/', '$1-'.substr(md5($orig_image_name), 0, 8).'.$2', $new_image_name ), $image_names, $orig_image_name );
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

      $images_import = $this->route['request']['post']['images'];
      $images_imported = array();
      $images_excluded = array();
      $upload_dir = wp_upload_dir();

      // Import each image
      if ( count($images_import) > 0 )
      {
        foreach ( $images_import as $hash => $image )
        {
          // Gotta be right, yo
          if ( is_array($image) && array_key_exists('src', $image) && array_key_exists('as', $image) && array_key_exists('posts', $image) )
          {
            // Convert posts string to array
            $image['posts'] = $this->to_array($image['posts']);

            // If `do` is disabled, then skip the image
            if ( !array_key_exists('do', $image) || !$this->sanitise_option_boolean($image['do']) )
            {
              $image['status'] = 'user_excluded';
              array_push( $images_excluded, $image );
              continue;
            }

            // Download the image to the uploads directory
            $filename = basename($image['as']);
            $image_url = trailingslashit($upload_dir['url']) . $filename;
            $image_path = trailingslashit($upload_dir['path']) . $filename;
            $image_dl = $this->download_image( $image['src'], $image_path );
            $filetype = wp_check_filetype( $filename, null );

            // Check if attachment with `guid` already in WP, if not add new attachment
            $sanitise_sql_url = $this->sanitise_sql($image_url);
            $check_attachment = $wpdb->get_row( "SELECT ID FROM $wpdb->posts WHERE $wpdb->posts.post_type = 'attachment' AND guid = '$sanitise_sql_url'", ARRAY_A );

            // Attachment already exists
            if ( !is_null($check_attachment) )
            {
              $image['as'] = $image_url;
              $images_imported[$hash] = $image;

            // Attachment doesn't exist, download new file and add to the media library
            } else {
              // Error downloading image
              if ( !$image_dl )
              {
                $image['status'] = 'error_dl';
                array_push( $images_excluded, $image );
                continue;
              }

              // Build attachment info
              $image['as'] = $image_url;
              $attachment = array(
                'guid' => $image_url,
                'post_title' => $filename,
                'post_content' => '',
                'post_mime_type' => $filetype['type'],
                'post_status' => 'inherit',
              );

              // Control the attachment info via apply_filters: 'lvl99_image_import/attachment'
              $attachment = apply_filters( $this->textdomain.'/attachment', $attachment );

              // Insert image into WP Media Library
              $attach_id = wp_insert_attachment( $attachment, $image_path );

              // Success
              if ( $attach_id > 0 )
              {
                // Meta data
                $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );

                // Control the attachment metadata via apply_filters: 'lvl99_image_import/attachment_metadata'
                $attach_data = apply_filters( $this->textdomain.'/attachment_metadata', $attach_data );

                wp_update_attachment_metadata( $attach_id, $attach_data );
                $images_imported[$hash] = $image;

              // Error
              } else {
                $image['status'] = 'error_wp';
                $images_excluded[$hash] = $image;
              }
            }
          } else {
            $image = array(
              'status' => 'error_incorrect_image_array_format',
              'image' => $image,
            );
            $images_excluded[$hash] = $image;
          }
        }
      }

      // Update results
      $this->results['images_imported'] = $images_imported;
      $this->results['images_excluded'] = $images_excluded;
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
      $images_search = array();
      $images_replace = array();
      $images_imported = array();
      $images_excluded = array();
      $posts_search = array();
      $posts_affected = array();
      $posts_excluded = array();

      // Iterate over images to get search/replace terms and posts to update
      if ( count($images > 0) )
      {
        foreach ( $images as $hash => $image )
        {
          if ( !is_array($image) || !array_key_exists('src', $image) || !array_key_exists('as', $image) || !array_key_exists('posts', $image) )
          {
            $image = array(
              'status' => 'error_incorrect_image_array_format',
              'image' => $image,
            );
            $images_excluded[$hash] = $image;
            continue;
          }

          // Make an array of all images src and as to search/replace with
          $images_search[] = '/' . preg_quote($image['src'], '/') . '\??[^"]*/'; // Add query var match to strip
          $images_replace[] = $image['as'];

          // Make an array of affected posts and then iterate over that, instead of per image
          $this->debug( array(
            'update_image_reference:pre' => $image,
          ) );
          $image['posts'] = $this->to_array($image['posts']);
          $this->debug( array(
            'update_image_reference:post' => $image,
          ) );
          foreach ( $image['posts'] as $post_id )
          {
            if ( !in_array($post_id, $posts_search) )
            {
              array_push( $posts_search, $post_id );
            }
          }

          $images_imported[$hash] = $image;
        }

        // Iterate over all posts affected to update
        if ( count($posts_search) > 0 )
        {
          foreach ( $posts_search as $post_id )
          {
            $post_id = $this->sanitise_option_number($post_id);
            $post_type = get_post_type($post_id);
            $update_post = array(
              'ID' => $post_id,
            );

            // Attachment updates guid
            if ( $post_type == 'attachment' )
            {
              $guid = get_the_guid( $post_id );
              $guid = preg_replace( $images_search, $images_replace, $guid );

              if ( !is_null($guid) ) $update_post['guid'] = $guid;

            // All other posts
            } else {
              $post_content = get_the_content( $post_id );
              $post_content = preg_replace( $images_search, $images_replace, $post_content );

              if ( !is_null($post_content) ) $update_post['post_content'] = $post_content;
            }

            $this->debug( array(
              'post_type' => $post_type,
              'update_post' => $update_post,
            ) );

            // Ensure guid or post_content has been updated
            if ( array_key_exists('guid', $update_post) || array_key_exists('post_content', $update_post) )
            {
              // Success
              if ( wp_update_post($update_post) )
              {
                array_push( $posts_affected, $post_id );

              // Error
              } else {
                $this->admin_error( 'Couldn\'t update image references for post '.$post_id );
                array_push( $posts_excluded, $post_id );
              }
            } else {
              $this->admin_error( 'Couldn\'t update image references for post '.$post_id );
              array_push( $posts_excluded, $post_id );
            }
          }
        }
      }

      // Update results
      $this->results['images_search'] = $images_search;
      $this->results['images_replace'] = $images_replace;
      $this->results['images_imported'] = $images_imported;
      $this->results['images_excluded'] = $images_excluded;
      $this->results['posts_search'] = $posts_search;
      $this->results['posts_affected'] = $posts_affected;
      $this->results['posts_excluded'] = $posts_excluded;
    }

    /*
    Download the image. If successful, returns the string of where the image is located, else returns `FALSE`.

    @method download_image
    @private
    @since 0.1.0
    @param {String} $image_url The URL to download the image
    @param {String} $image_dest The path to put the image
    @returns {Mixed} {String} the image's uploaded location, or {Boolean} FALSE on error
    */
    private function download_image ( $image_url, $image_dest )
    {
      // Only download if it doesn't already exist
      if ( !file_exists($image_dest) )
      {
        $ch = curl_init($image_url);
        $fp = fopen($image_dest, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_exec($ch);

        // Error
        if ( curl_error($ch) )
        {
          $this->admin_error('Couldn\'t download image: <code>'.$image_url.'</code>');
          error_log( 'cURL error downloading image: ' . $image_url );
          curl_close($ch);
          fclose($fp);
          return FALSE;

        // Success
        } else {
          curl_close($ch);
          fclose($fp);
          return $image_dest;
        }

      // Image already exists at the destination, so use that
      } else {
        return $image_dest;
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

      $this->results['debug'] = array(
        '_time' => time(),
        '_output' => $input,
        '_callee_method' => $trace[1]['function'],
      );
    }
  }
}

// The instance of the plugin
$lvl99_image_import = new LVL99_Image_Import();

