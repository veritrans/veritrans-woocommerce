Veritrans VT-Web PHP Wrapper
==============================================

PHP wrapper for Veritrans VT-Web payment API. Visit [https://www.veritrans.co.id](https://www.veritrans.co.id) for more information about the product and see documentation at [http://docs.veritrans.co.id](http://docs.veritrans.co.id/vtweb/index.html) for more technical details.

# Installation

#### Composer Installation

If you are using [Composer](https://getcomposer.org), add this require line to your `composer.json` file:

```
"require": {
	"andrisetiawan/veritrans-php": "dev-master"
}
```

and run `composer install` on your terminal.

#### Manual Instalation

If you are not using Composer, just copy all files in this repository into your project's library.


## How to use

### STEP 1 : Requesting key

Given you already have cart ready for checkout.
We create a veritrans instance.

```
$veritrans = new Veritrans();

//TODO: Change with your actual merchant id and merchant hash key
$veritrans->merchant_id 		= 'T100000000000001000001';
$veritrans->merchant_hash_key 	= '305e0328a366cbce8e17a385435bb7eb3f0cbcfbfc0f1c3ef56b658';

//TODO: Change with your actual order_id.
$veritrans->order_id 			= 'your_unique_order_id';

// Set commodity items
$items = array(
			array(
				"item_id" => 'itemsatu',
				"price" => '250000',
				"quantity"   => '1',
				"item_name1" => 'sepatu',
				"item_name2" => 'Shoes'
			),
			array(
				"item_id" => 'itemdua',
				"price" => '500000',
				"quantity"   => '2',
				"item_name1" => 'Tas',
				"item_name2" => 'Bag'
			),
		);

$veritrans->items = $items;

$veritrans->required_shipping_address 						= 1; // Set '0' if shipping address is not required
$veritrans->billing_address_different_with_shipping_address = 1; // Set '0' if shipping address = billing address

// Set billing info. If you don't set this info, customer will need to fill it at the Veritrans payment page.
$veritrans->first_name 	= "Andri";
$veritrans->last_name 	= "Setiawan";
$veritrans->email 		= "customer@email.com";
$veritrans->address1 	= "Karet Belakang";
$veritrans->address2 	= "Setiabudi";
$veritrans->city 		= "Jakarta";
$veritrans->country_code= "IDN";
$veritrans->postal_code = "12345";
$veritrans->phone 		= "08123123123123";

// Set shipping info. If you don't set this info, customer will need to fill it at the Veritrans payment page.
$veritrans->shipping_first_name 	= "John";
$veritrans->shipping_last_name 		= "Watson";
$veritrans->shipping_address1 		= "Bakerstreet 221B";
$veritrans->shipping_address2 		= "Tebet";
$veritrans->shipping_city 			= "Jakarta";
$veritrans->shipping_country_code 	= "IDN";
$veritrans->shipping_postal_code 	= "12346";
$veritrans->shipping_phone 			= "082313123131";

//Call Veritrans VT-Web API Get Token
try {
	$keys = $veritrans->getTokens();

	if(!$keys) {
		print_r($veritrans->errors);
		exit();
	} else {
		//Save this token_merchant on your database to be used for checking veritrans notification response.
		$token_merchant = $keys['token_merchant'];

		//Use this token_browser for redirecting customer to Veritrans payment page.
		$token_browser = $keys['token_browser'];
	}
} catch (Exception $e) {
	var_dump($e);
}
```

### STEP 2:  Redirecting user to Veritrans payment page

**Prepare the FORM to redirect the customer**
	
```
<!DOCTYPE html>
<html>
<head>
	<script language="javascript" type="text/javascript">
	<!--
	function onloadEvent() {
	  document.form_auto_post.submit();
	}
	//-->
	</script>
</head>

<body onload="onloadEvent();">
<form action="<?php echo Veritrans::PAYMENT_REDIRECT_URL ?>" method="post" name='form_auto_post'>
<input type="hidden" name="MERCHANT_ID" value="<?php echo $veritrans->merchant_id ?>" />
<input type="hidden" name="ORDER_ID" value="<?php echo $veritrans->order_id ?>" />
<input type="hidden" name="TOKEN_BROWSER" value="<?php echo $token_browser ?>" />
<span>Please wait. You are being redirected to Veritrans payment page...</span>
</form>

</body>
```


### STEP 3 : Responding Veritrans payment notification
After the payment process is completed, Veritrans will send HTTP(S) POST notification to merchant's web server.
As a merchant, you need to process this POST paramters to update order status in your database server. Veritrans will send 3 POST parameters: `orderId`, `mStatus`, and `TOKEN_MERCHANT`.

```
$notification = new VeritransNotification();

if($notification->mStatus == "fatal")
{
	// Veritrans internal system error. Please contact Veritrans Support if this occurs.
}
else
{
	// TODO: Retrieve order info from your database to check the token_merchant for security purpose.
	// The token_merchant that you get from this http(s) POST notification must be the same as token_merchant that you get previously when requesting token (before redirecting customer to Veritrans payment page.)

	//$order = Order::find('order_id' => $notification->orderId);

	if($order['TOKEN_MERCHANT'] == $notification->TOKEN_MERCHANT )
	{
		// TODO: update order payment status on your database. 3 possibilities $notification->mStatus responses from Veritrans: 'success', 'failure', and 'challenge'
		// $order['payment_status'] = $notification->mStatus; 
		// $order->save();
	}
	else
	{
		// If token_merchant that you get from this http(s) POST request is different with token_merchant that you get previously when requesting token (before redirecting customer to Veritrans payment page.), there is a possibility that the http(s) POST request is not coming from Veritrans. 
		// Don't update your payment status. 
		// To make sure, check your Merchant Administration Portal (MAP) at https://payments.veritrans.co.id/map/
	}
}
```
