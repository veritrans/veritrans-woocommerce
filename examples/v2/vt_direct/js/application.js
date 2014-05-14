<!-- Javascript to generate token and show 3DSecure Dialog -->
  $(function(){
    // Sandbox API URL. TODO: Change with Production API URL when you're ready to go live.
    Veritrans.url = "https://api.veritrans.co.id/v2/token";
    // TODO: Change with your actual client key that can be found at Merchant Administration Portal >> Setting >> Access Key
    Veritrans.client_key = "d4b273bc-201c-42ae-8a35-c9bf48c1152b";
    var card = function(){
      return {
        'card_number'   : $(".card-number").val(),
        'card_exp_month': $(".card-expiry-month").val(),
        'card_exp_year' : $(".card-expiry-year").val(),
        'card_cvv'      : $(".card-cvv").val(),
        'vtkey'         : "v3r1tr4n5-15-n0-1",
        'ip_address'    : '192.168.1.1'

        // Set 'secure', 'bank', and 'gross_amount', if the merchant wants transaction to be processed with 3D Secure
        // 'secure'       : true,
        // 'bank'       : 'bni',
        // 'gross_amount'   : 200000
      }
    };

    // handler when user click the 'Pay' button.
    $('.submit-button').click(function(event){
      event.preventDefault();
      $(this).attr("disabled", "disabled"); 
      Veritrans.token(card, callback);
      return false;
    });

    function callback(response) {
      if (response.redirect_url) {
        // 3Dsecure transaction. Open 3Dsecure dialog
        console.log('Open Dialog 3Dsecure');
        openDialog(response.redirect_url);

      } else if (response.status_code == '200') {
        // success 3d secure or success normal
        closeDialog();

        // store token data in input #token_id and then submit form to merchant server
        $("#token_id").val(response.token_id);
        $("#payment-form").submit();
      } else {
        // failed request token
        closeDialog();
        $('.submit-button').removeAttr('disabled');
        // Show status message.
        $('#message').text(response.status_message);
        console.log(JSON.stringify(response));
      }
    }

    // Open 3DSecure dialog box
    function openDialog(url) {
      $.fancybox.open({
            href: url,
            type: 'iframe',
            autoSize: false,
            width: 700,
            height: 500,
            closeBtn: false,
            modal: true
        });
    }

    // Close 3DSecure dialog box
    function closeDialog() {
      $.fancybox.close();
    }
  });

