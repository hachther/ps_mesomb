var successCallback = function (data) {

  var checkout_form = $('form.woocommerce-checkout');

  // add a token to our hidden input field
  // console.log(data) to find the token
  checkout_form.find('#mesomb_token').val(data.token);

  // deactivate the tokenRequest function event
  checkout_form.off('checkout_place_order', tokenRequest);

  // submit the form now
  checkout_form.submit();

};

var errorCallback = function (data) {
  console.log(data);
};

var tokenRequest = function () {

  // here will be a payment gateway function that process all the card data from your form,
  // maybe it will need your Publishable API key which is misha_params.publishableKey
  // and fires successCallback() on success and errorCallback on failure
  return false;

};

jQuery(function ($) {
  // const placeholders = {
  //   ORANGE: 'Orange Money Number (Expl: 690000000)',
  //   MTN: 'Mobile Money Number  (Expl: 670000000)',
  //   AIRTEL: 'Airtel Money Number  (Expl: 67000000)',
  // }
  const placeholders = $('#mesomb-provider-names')?.data()?.json ?? {};
  function toggleCountry(country) {
    if (!(country?.length > 0)) {
      return;
    }
    $('.provider-row input').prop('checked', false);
    $('.provider-row').hide();
    $('.' + country).show();
  }
  // var checkout_form = $( 'form.woocommerce-checkout' );
  // checkout_form.on( 'checkout_place_order', tokenRequest );
  toggleCountry($('select[name=country]').val());
  $('input[name=billing_phone]').on('change', function (evt) {
    $('#ps_mesomb-payer').val(evt.target.value)
  })
  $('input[name=service]').on('change', function (evt) {
    $('input[name=payer]').attr('placeholder', placeholders[evt.target.value]);
  })
  $('body').on('change', 'select[name=country]', function (evt) {
    const country = evt.target.value;
    if (country) {
      toggleCountry(country);
    }
  });
  $('#payment-form').on('submit', function (evt) {
    $('#ps_mesomb-alert').show();
    setTimeout(function () {
      $('#ps_mesomb-alert').hide();
    }, 6000);
  })
});
