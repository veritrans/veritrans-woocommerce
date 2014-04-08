<html>
<head></head>
<body>
	<h2>Checkout</h2>
	<form action="checkout_process.php" method="post" id="payment-form">
		<h3>Order Data</h3>
		<fieldset>
			<legend>Order Data</legend>
			<table>
				<thead>
					<td>Product</td>
					<td>Qty</td>
					<td>Price</td>
					<td>Total</td>
				</thead>
				<tr>
					<td>Sepatu Adidas F30 Super Sangat Sehat Sekali $^%</td>
					<td>1</td>
					<td>Rp 850.000</td>
					<td>Rp 850.000</td>
				</tr>
				<tr>
					<td>Sepatu Nike Lunarmoon</td>
					<td>2</td>
					<td>Rp 900.000</td>
					<td>Rp 1.800.000</td>
				</tr>
				<tr>
					<td colspan="4"></td>
				</tr>
				<tr>
					<td colspan="3">Total</td>
					<td>Rp 2.650.000</td>
				</tr>
			</table>
		</fieldset>
		<h3>Billing Data</h3>
		<fieldset>
			<label>Email</label><br />
			<input name="email" size="30" type="text" value="vt-testing@veritrans.co.id"><br /><br />
			<label>First name</label><br />
			<input name="billing_first_name" size="30" type="text" value="Andri jksdnfjksdnf ejkndjwknjkewdjk@#$%^ewndjknewjkdnejkwndjkewndjkwed @#$%^"><br /><br />
			<label>Last name</label><br />
			<input name="billing_last_name" size="30" type="text" value="Setiawan"><br /><br />
			<label>Address 1</label><br />
			<input name="billing_address1" size="30" type="text" value="Bakerstreet 221B"><br /><br />
			<label>Address 2</label><br />
			<input name="billing_address2" size="30" type="text" value="Setiabudi"><br /><br />
			<label>City</label><br />
			<input name="billing_city" size="30" type="text" value="Jakarta"><br /><br />
			<label>Postal code</label><br />
			<input name="billing_postal_code" size="30" type="text" value="12345"><br /><br />
			<label>Phone</label><br />
			<input name="billing_phone" size="30" type="text" value="08112312312312"><br /><br />
		</fieldset>
		
		<h3>Shipping Info</h3>
		<fieldset>
			<label>First name</label><br /><br />
			<input name="shipping_first_name" size="30" type="text" value="Andri"><br /><br />
			<label>Last name</label><br />
			<input name="shipping_last_name" size="30" type="text" value="Setiawan"><br /><br />
			<label>Address 1</label><br />
			<input name="shipping_address1" size="30" type="text" value="Bakerstreet 221B"><br /><br />
			<label>Address 2</label><br />
			<input name="shipping_address2" size="30" type="text" value="Setiabudi"><br /><br />
			<label>City</label><br />
			<input name="shipping_city" size="30" type="text" value="Jakarta"><br /><br />
			<label>Postal code</label><br />
			<input name="shipping_postal_code" size="30" type="text" value="12345"><br /><br />
			<label>Phone</label><br />
			<input name="shipping_phone" size="30" type="text" value="08112312312312"><br /><br />

			<input type="text" name="installment_banks[]" value="bni">
			<input type="text" name="installment_terms[bni][]" value="3">
		</fieldset>
		<div>
			<h2>Pay with VT-Web</h2>
			<fieldset>
				<legend>Checkout</legend>
				<button type="submit" name="payment_type" value="vtweb">Pay with Veritrans VT-Web</button>
			</fieldset>
		</div>
		<div>
			<h2>Pay with VT-Direct</h2>
			<fieldset>
		    <legend>Checkout</legend>
		    <p>
		      <label>Card Number</label>
		      <input class="card-number" size="20" type="text" autocomplete="off" value="4111111111111111"/>
		    </p>
		    <p>
		      <label>Expiration (MM/YYYY)</label>
		      <input class="card-expiry-month" placeholder="MM" size="2" type="text" value="03"/>
		      <span> / </span>
		      <input class="card-expiry-year" placeholder="YYYY" size="4" type="text" value="2019" />
		    </p>
		    <p>
		      <label>CVV</label>
		      <input class="card-cvv" size="4" type="password" autocomplete="off" value="333"/>
		    </p>
		    <input id="token_id" name="token_id" type="hidden" />
		    <input type="hidden" name="payment_type" value="vtweb">
		    <button type="submit" class="submit-button" name="payment_type" value="vtdirect">Pay with Veritrans VT-Direct</button>
		  </fieldset>
		</div>
	</form>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script type="text/javascript" src="https://payments.veritrans.co.id/vtdirect/veritrans.min.js"></script>
	<script> $(
	  function(){

	    Veritrans.client_key = "2da5920f-2b47-4079-918f-bcc0d14cfe11"; // please add client-key from veritrans

	    function _cardSet(){
	      return {
	        "card_number" : $('input.card-number').val(),
	        "card_exp_month": $('input.card-expiry-month').val(),
	        "card_exp_year" : $('input.card-expiry-year').val(),
	        "card_cvv" : $('input.card-cvv').val()
	      }
	    };

	    function _success(d){
	    	$('#token_id').val(d.data.token_id); // store token data in input #token_id
	    	$('.input[name=payment_type]').val('vtdirect');
	    	$("#payment-form")[0].submit(); //submits Token to merchant server
	    };

	    function _error(d){
	      alert(d.message); // please customize the error
	      $('.submit-button').removeAttr("disabled");
	    };

	    $("#payment-form").submit(function(e) {
	    	if ($('.submit-button').val() == 'vtdirect') {
	    		$('.submit-button').attr("disabled", "disabled"); // disable the submit button
	      	Veritrans.tokenGet(_cardSet, _success, _error);
	      	return false;
	    	};
	    });
	  });
	</script>
</body>
</html>