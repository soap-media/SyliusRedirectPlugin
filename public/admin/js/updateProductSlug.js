$(function () {
  let $body = $('body');

  hideAutomaticRedirectionInput();
  $body.on('click', '.toggle-product-slug-modification', function () {
    let $input = $(this).parent().find('input');

    handleAutomaticRedirectionInput($input);
  });
  $('.toggle-product-slug-modification').each(function () {
    let $input = $(this).parent().find('input');
    $input.on('keyup', function () {
      handleAutomaticRedirectionInput($(this));
    });
  });

  $(document).ajaxComplete(function (event, request, settings) {
    if (settings.url.indexOf('ajax/products/generate-slug') !== -1) {
      $('.toggle-product-slug-modification').each(function () {
        let $input = $(this).parent().find('input');

        $input.trigger('keyup');
      });
    }
  });
});
