/*
* LVL99 Plugin JS
*/

var lvl99 = lvl99 || {};

lvl99.enableDebug = true;
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

// Sortable fields
lvl99.widgetCount++;
lvl99.widgets.sortable = {
  active: false,
  requires: [ 'jQuery', 'jQuery.fn.sortable' ],
  init: function () {
    // Events
    jQuery(document).trigger('lvl99-plugin-sortable');
  }
}

// File uploads
lvl99.widgetCount++;
lvl99.widgets.fileUploaders = {
  active: false,
  requires: [ 'jQuery' ],
  init: function () {
    jQuery(document).ready(function() {
      jQuery(document).on( 'click', '.upload_file_button', function (event) {
        event.preventDefault();

        jQuery.data( document.body, 'upload_file_element', jQuery(this).parents('.lvl99-plugin-option') );

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

        var $option = jQuery(this).parents('.lvl99-plugin-option');
        $option.find('input').val('');
        $option.find('img').attr('src', '').hide();
        jQuery(this).hide();
      });

      jQuery(document).trigger('lvl99-plugin-file-uploader');
    });
  }
}

// See: http://stackoverflow.com/a/6491621/1421162
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

    // If exceeded 10 checks, abort this widget
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
