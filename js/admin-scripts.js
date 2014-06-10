(function($){
	function sensitiveOptions() {
    var api_version = $('#woocommerce_veritrans_select_veritrans_api_version').val();
    var payment_type = ($('#woocommerce_veritrans_select_veritrans_payment').val() == 'veritrans_web' ? 'vtweb' : 'vtdirect');
    var environment_type = $('#woocommerce_veritrans_select_veritrans_environment').val();
    
    var api_string = 'v' + api_version + '_settings';
    var payment_type_string = payment_type + '_settings';
    var api_payment_type_string = 'v' + api_version + '_' + payment_type + '_settings';
    var environment_string = environment_type + '_settings';
    var api_environment_string = 'v' + api_version + '_' + environment_type + '_settings';

    $('.sensitive').closest('tr').hide();
    $('.' + api_string).closest('tr').show();
    $('.' + payment_type_string).closest('tr').show();
    $('.' + api_payment_type_string).closest('tr').show();
    $('.' + api_environment_string).closest('tr').show();

    // temporarily hide vt-direct if the API version is 2
    if (api_version == 2)
    {
      $('#woocommerce_veritrans_select_veritrans_payment').closest('tr').hide();
    } else{
      $('#woocommerce_veritrans_select_veritrans_payment').closest('tr').show();
    }
    
  }

	$(document).ready(function(){
		
		$("#woocommerce_veritrans_select_veritrans_api_version").on('change', function(e, data) {
      sensitiveOptions();
    });
    $("#woocommerce_veritrans_select_veritrans_payment").on('change', function(e, data) {
      sensitiveOptions();
    });
    $("#woocommerce_veritrans_select_veritrans_environment").on('change', function(e, data) {
      sensitiveOptions();
    });

    sensitiveOptions();
		
	});
})(jQuery);