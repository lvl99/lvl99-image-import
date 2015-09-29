(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
// LVL99 Plugin logic
(function ($, window) {
  $(document).ready( function () {
    var $doc = $(document),
        $win = $(window),
        $html = $('html'),
        $body = $('body'),
        progressStart = 0,
        progressEnd = 0,
        progressLength = 0,
        progressTimer = 0;

    // Show/hide medialibrary options if option is selected
    $doc.on( 'change', 'input[name="lvl99-image-import_importtype"]', function (event) {
      var $elem = $('input[name="lvl99-image-import_importtype"][value=medialibrary]');

      if ( $elem.is(':checked') ) {
        $elem.parents('form').find('.lvl99-image-import-medialibrary-options').slideDown();
      } else {
        $elem.parents('form').find('.lvl99-image-import-medialibrary-options').slideUp();
      }
    });

    // Disable button on form submission (to prevent double-submissions)
    $doc.on( 'submit', 'form', function (event) {
      var $form = $(this);

      $form.find('button').attr('disabled', 'disabled');

      // Display message to encourage user to not close window.
      if ( $form.find('input[name="lvl99-image-import"][value=imported]').length === 1 ) {
        $images = $form.find('tbody .lvl99-image-import-col-do input[type=checkbox]:checked');
        $form.append('<div class="lvl99-image-import-submitted"><div class="lvl99-plugin-notices"><div class="lvl99-plugin-notice"><p>Processing selected images now. Depending on the amount of images you are importing/changing, it may take a while to complete (estimated ~30s per image, ~'+(Math.ceil(($images.length * 30) / 60))+' mins total). <b><i>Don\'t close this window!</i></b></p><div class="lvl99-image-import-progress"><div class="lvl99-image-import-progress-bar"></div></div></div></div></div>');
        startProgress($images.length);
      }
    });

    // Enable/disable selected posttypes
    $doc.on( 'change', '.lvl99-image-import-posttypes input', function (event) {
      var $elem = $(this),
          $options = $('.lvl99-image-import-posttypes-list input[name^=lvl99-image-import_posttypes_selected]');

      if ( $elem.val() === 'all' ) {
        if ( $elem.is(':checked') ) {
          $options.attr('disabled', 'disabled');
        } else {
          $options.removeAttr('disabled');
        }
      } else if ( $elem.val() === 'selected' ) {
        if ( $elem.is(':checked') ) {
          $options.removeAttr('disabled');
        } else {
          $options.attr('disabled', 'disabled');
        }
      }
    });

    // Enable/disable all scanned image references
    $doc.on( 'change', '.lvl99-image-import-col-do input[name=lvl99-image-import_selectall]', function (event) {
      var $elem = $(this),
          $checkboxes = $('.lvl99-image-import-col-do input[type=checkbox]');

      if ( $elem.is(':checked') ) {
        $checkboxes.attr('checked', 'checked');
      } else {
        $checkboxes.removeAttr('checked');
      }
    });

    // Add filter
    $doc.on( 'click', 'a[href=#add-filter]', function (event) {
      var rand = 'a'+(new Date().getTime()+'').slice(-8, -1),
          $newFilter = $('<div class="lvl99-image-import-filter-item ui-draggable ui-sortable"><div class="lvl99-image-import-filter-method"><span class="fa-arrows-v lvl99-sortable-handle"></span><select name="lvl99-image-import_filters['+rand+'][method]"><option value="include">Include if matches...</option><option value="exclude">Exclude if matches...</option><option value="replace">Search &amp; Replace</option></select></div><div class="lvl99-image-import-filter-input"><input type="text" name="lvl99-image-import_filters['+rand+'][input]" value="" placeholder="Search for..." /></div><div class="lvl99-image-import-filter-output"><input type="text" name="lvl99-image-import_filters['+rand+'][output]" value="" placeholder="Replace with empty string" style="display: none" /></div><div class="lvl99-import-image-filter-controls"><a href="#remove-filter" class="button button-secondary button-small">Remove</a></div></div>');

      event.preventDefault();
      $newFilter.appendTo('.lvl99-image-import-filters');
    });

    // Change filter type
    $doc.on( 'change', '.lvl99-image-import-filter-method select', function (event) {
      var $select = $(this),
          $item = $select.parents('.lvl99-image-import-filter-item');

      switch ( $select.val() ) {
        case 'include':
        case 'exclude':
          $item.find('.lvl99-image-import-filter-output input').hide();
          break;

        case 'replace':
          $item.find('.lvl99-image-import-filter-output input').show();
          break;
      }
    })

    // Remove filter
    $doc.on( 'click', 'a[href=#remove-filter]', function (event) {
      var $filter = $(this).parents('.lvl99-image-import-filter-item');
      $filter.remove();
    });

    function startProgress( totalImagesToProcess ) {
      var $progress = $('.lvl99-image-import-progress-bar'),
          $notices = $('.lvl99-image-import-submitted .lvl99-plugin-notices');

      progressStart = new Date();
      progressEnd = new Date( progressStart.getTime() + (totalImagesToProcess * 30000) ); // Estimate 30s per image
      progressLength = 0;

      // Fire every second until ya don't need to
      progressTimer = setInterval( function () {
        var now = new Date();
        progressLength = Math.floor(((now.getTime() - progressStart.getTime()) / (progressEnd.getTime() - progressStart.getTime())) * 100);

        // console.log( (now.getTime() - progressStart.getTime()) );
        // console.log( (progressEnd.getTime() - progressStart.getTime()) );
        // console.log( ((now.getTime() - progressStart.getTime()) / (progressEnd.getTime() - progressStart.getTime())) );
        // console.log( progressLength );

        // Show message if it's taking longer than expected
        if ( progressLength > 100 ) {
          if ( $notices.find('.lvl99-image-import-overtime').length === 0 ) $notices.append('<div class="lvl99-plugin-notice lvl99-plugin-notice-warning lvl99-image-import-overtime">Sorry, this is taking longer than estimated! Shouldn\'t be too long now...</div>');
          progressLength = 100;
        }

        // Change the length of the bar to communicate the overall progress
        $progress.css({
          width: progressLength+'%'
        });

        // No need to do much more
        if ( progressLength === 100 ) clearInterval(progressTimer);
      }, 1000 );
    }

    // Initialise sortables
    $('.lvl99-sortable').sortable({
      items: '.lvl99-image-import-filter-item',
      handle: '.lvl99-sortable-handle'
    });

  });
})(jQuery, window);

},{}]},{},[1]);
