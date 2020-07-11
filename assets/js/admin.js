jQuery( function( $ ) {
  'use strict';

  $(document.body).on('click', '.wcdg-toggle', function(){
    var $t = $(this),
        gateway_id = $t.closest('tr').data('gateway_id');

    $t.find('span').addClass(wcdg.loadingClass);

    $.post(ajaxurl, {action: 'wcdg-toggle', gateway_id: gateway_id})
    .done(function(r){
      if (r.success) {
        $('.wcdg-toggle span')
          .removeClass(wcdg.enabledClass)
          .addClass(wcdg.disabledClass)
          .text(wcdg.disabledText);

        if (r.data.gateway_id){
          $t.find('span')
            .removeClass(wcdg.disabledClass)
            .addClass(wcdg.enabledClass)
            .text(wcdg.enabledText);
        }
      } else {
        if (r.data.error) {
          alert(r.data.error);
        }
      }
    })
    .complete(function(){
      $t.find('span').removeClass(wcdg.loadingClass);
    });

    return false;
  });
});
