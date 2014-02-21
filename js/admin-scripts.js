(function($){
function show_hide_fields( value ) {
	var $this = $('#woocommerce_veritrans_select_veritrans_payment');
	
	$this.closest('tr').nextAll('tr').hide();
	$('.'+value).closest('tr').show();
}

$(document).ready(function(){
	
	show_hide_fields( $('#woocommerce_veritrans_select_veritrans_payment').val() );
	
	$('#woocommerce_veritrans_select_veritrans_payment').on('change', function(){
		var $this = $(this),
				value = $this.val();
		show_hide_fields( value );
	});
	
});
})(jQuery);