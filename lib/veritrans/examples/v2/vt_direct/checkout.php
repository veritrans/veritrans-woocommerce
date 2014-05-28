<html>
<head>
	<title>Checkout</title>
	<!-- Include PaymentAPI (MANDATORY)-->
	<script type="text/javascript" src="https://api.veritrans.co.id/v2/assets/js/all.js"></script>
	
	<!-- 
	Include JQuery & JQuery Fancy Box.
	In this sample, we use JQuery & JQuery Fancy to simplify the process when showing 3D Secure dialog. Feel free to use your own implementation or your prefered JS framework. 
	-->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery.fancybox.pack.js"></script>
	<link rel="stylesheet" href="css/jquery.fancybox.css" type="text/css" media="screen" />

	<!-- JS for handling vt-direct form interactions -->
	<script type="text/javascript" src="js/application.js"></script>	

</head>

<body>
	<h1>Checkout</h1>
	
	<span id="message"></span>

	<form action="checkout_process.php" method="POST" id="payment-form">
		<p>
			<label>Card Number</label>
			<input class="card-number" value="4111111111111111" size="20" type="text" autocomplete="off"/>
		</p>
		<p>
			<label>Expiration (MM/YYYY)</label>
			<input class="card-expiry-month" value="12" placeholder="MM" size="2" type="text" />
	    	<span> / </span>
	    	<input class="card-expiry-year" value="2018" placeholder="YYYY" size="4" type="text" />
		</p>
		<p>
	    	<label>CVV</label>
	    	<input class="card-cvv" value="123" size="4" type="password" autocomplete="off"/>
		</p>
		<input id="token_id" name="token_id" type="hidden" />
		<button class="submit-button" type="submit">Pay</button>
	</form>
	<script type="text/javascript">
		$(function(){
			// Sandbox URL
			Veritrans.url = "https://api.sandbox.veritrans.co.id/v2/token";
			
			// TODO: Change with your client key.
			Veritrans.client_key = "1587765e-defa-4064-8b95-807dba85d551";
			var card = function(){
				return { 	'card_number'		: $(".card-number").val(),
							'card_exp_month'	: $(".card-expiry-month").val(),
							'card_exp_year'		: $(".card-expiry-year").val(),
							'card_cvv'			: $(".card-cvv").val(),
							// 'secure'			: true,
							'bank'				: 'bni',
							'gross_amount'		: 200000
							 }
			};

			function callback(response) {
				if (response.redirect_url) {
					// 3dsecure transaction, please open this popup
					openDialog(response.redirect_url);

				} else if (response.status_code == '200') {
					// success 3d secure or success normal
					closeDialog();
					// submit form
					$("#token_id").val(response.token_id);
					$("#payment-form").submit();
				} else {
					// failed request token
					console.log('Close Dialog - failed');
					//closeDialog();
					//$('#purchase').removeAttr('disabled');
					// $('#message').show(FADE_DELAY);
					// $('#message').text(response.status_message);
					alert(response.status_message);
				}
			}

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

			function closeDialog() {
				$.fancybox.close();
			}

			$('.submit-button').click(function(event){
				event.preventDefault();
				$(this).attr("disabled", "disabled"); 
				Veritrans.token(card, callback);
				return false;
			});
		});
	</script>
	
</body>
</html>
