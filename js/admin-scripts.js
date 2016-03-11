(function($){
	function sensitiveOptions() {
        var environment_type = $("select[name*='veritrans_environment']").val();
        
        var api_environment_string = environment_type + '_settings';

        $('.sensitive').closest('tr').hide();
        $('.' + api_environment_string).closest('tr').show();
    }

	$(document).ready(function(){
		
        $("select[name*='veritrans_environment']").on('change', function(e, data) {
            sensitiveOptions();
        });

        sensitiveOptions();
		
	});
})(jQuery);