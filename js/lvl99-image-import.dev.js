// LVL99 Plugin logic
(function ($, window) {
  $(document).ready( function () {
    var $doc = $(document),
        $win = $(window),
        $html = $('html'),
        $body = $('body');

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

    // Add filter
    $doc.on( 'click', 'a[href=#add-filter]', function (event) {
      var count = $('.lvl99-image-import-filter-item').length,
          $newFilter = $('<div class="lvl99-image-import-filter-item"><div class="lvl99-image-import-filter-method"><select name="lvl99-image-import_filters['+count+'][method]"><option value="include">Include if matches...</option><option value="exclude">Exclude if matches...</option><option value="replace">Search &amp; Replace</option></select></div><div class="lvl99-image-import-filter-input"><input type="text" name="lvl99-image-import_filters['+count+'][input]" value="" placeholder="Search for..." /></div><div class="lvl99-image-import-filter-output"><input type="text" name="lvl99-image-import_filters['+count+'][output]" value="" placeholder="Replace with empty string" style="display: none" /></div><div class="lvl99-import-image-filter-controls"><a href="#delete-filter" class="button button-secondary button-small">Delete</a></div></div>');

      event.preventDefault();
      $newFilter.appendTo('#lvl99-image-import-filters');
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

    // Delete filter
    $doc.on( 'click', 'a[href=#delete-filter]', function (event) {
      var $filter = $(this).parents('.lvl99-image-import-filter-item');
      $filter.remove();
    });

  });
})(jQuery, window);
