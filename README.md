Veritrans PHP Wrapper
==============================================

Veritrans :heart: PHP!

This is the official PHP wrapper for Veritrans Payment API. Visit [https://www.veritrans.co.id](https://www.veritrans.co.id) for more information about the product and see documentation at [http://docs.veritrans.co.id](http://docs.veritrans.co.id/vtweb/index.html) for more technical details.

## Installation

### Composer Installation

If you are using [Composer](https://getcomposer.org), add this require line to your `composer.json` file:

```json
"require": {
	"veritrans/veritrans-php": "dev-vtweb-2"
}
```

and run `composer install` on your terminal.

### Manual Instalation

If you are not using Composer, just copy all files in this repository into your project's library.

## How to use

### VT-Web

1. Edit the file which process your order by setting up basic information as is explained [here](#user-content-setting-up-basic-information).

2. Set the payment type to `VT_WEB`.
   ```php
   $veritrans->payment_type = Veritrans::VT_WEB;
   ```

2. Call `getTokens()` to obtain the URL to redirect your customer to the Veritrans VT-Web page.

	 ```php
	 try {

		// Call Veritrans VT-Web API Get Token
		$keys = $veritrans->getTokens();
	  
		if(!in_array($keys['status_code'], array(201, 202, 203))) 
		{
			// print the error
			print_r($veritrans->errors);
			exit();

		} else {

			// redirect the request if getTokens() is successful
			header('Location: ' . $keys['redirect_url']);

		}
	 } catch (Exception $e) {
	  var_dump($e);
	 }
	 ```

3. After your customer finish the payment, Veritrans will send a payment notification **asynchronously** to the URL
   defined in the `Payment Notification URL` field in your Merchant Administration Portal. You have to handle the notification in order to finish the transaction. The code example is explained below.

  ```php
	require 'lib/veritrans_notification.php';

	$notification = new VeritransNotification();

	if($notification->transaction_status == "capture")
	{
	  error_log('payment success!');
	}
	else if ($notification->transaction_status == 'deny')
	{
	 error_log('payment denied!'); 
	} else if ($notification->transaction_status == 'challenge')
	{
	  error_log('payment challenged!');
	} else
	{
	  error_log('system error!');
	}
  ```

### VT-Direct

1. Create a HTML page containing a link to the `veritrans.js` script. An example of the script is described below.
   ```html
   <html>
		<head>
			<title>Checkout</title>
			<!-- Include PaymentAPI  -->
			<link rel="stylesheet" href="css/jquery.fancybox.css">
		</head>
		<body>
			<script type="text/javascript" src="https://api.sandbox.veritrans.co.id/v2/assets/js/veritrans.js"></script>
			<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
			<script type="text/javascript" src="js/jquery.fancybox.pack.js"></script>

			<h1>Checkout</h1>
			<form action="checkout_process.php" method="POST" id="payment-form">
				<fieldset>
					<legend>Checkout</legend>
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

					<p>
				    	<label>Save credit card</label>
				    	<input type="checkbox" name="save_cc" value="true">
					</p>

					<input id="token_id" name="token_id" type="hidden" />
					<button class="submit-button" type="submit">Submit Payment</button>
				</fieldset>
			</form>

			<!-- Javascript for token generation -->
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
								'secure'			: true,
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
   ```

2. Edit the file which become the target of the form (the `checkout_process.php` in the case above) by setting up basic information as is explained [here](#user-content-setting-up-basic-information).

3. Set the payment type to `VT_DIRECT`, and include additional payment informations.
 	 
 	```php
 	$veritrans->payment_type = Veritrans::VT_WEB;
 	$veritrans->token_id = $_POST['token_id']; // the token_id is obtained by using the veritrans.js script.
 	$veritrans->bank = 'bni';
 	```

4. If you wish to enable the [Veritrans One-Click](#user-content-the-veritrans-one-click-feature) feature to let your customers place orders without entering the credit card number in the next time, set the `save_token_id` property to `true`.
   
   ```php
   $veritrans->save_token_id = true;
   ```

5. Call the `charge()` method and handle the `$response` array appropriately.

	```php
 	$response = $veritrans->charge();

 	if (in_array($response['status_code'], array(200, 201, 202))) {
		if($response['transaction_status'] == "capture")
		{
			// the transaction is successfully processed. Insert additional logic here.
		}
		else if ($response['transaction_status'] == "deny")
		{
			// the transaction is denied by the bank. Insert additional logic here.
		}
		else if($response['transaction_status'] == "challenge")
		{
			// the transaction is challenged by the Veritrans Fraud Detection System. Insert additional logic here.
		}
		echo "The transaction status for order ID " . $response['order_id'] . ": is " . $response['transaction_status'];
	} 
	else
	{
		// there is some error happened. Insert additional logic here.
		echo "Status message: [" . $response['status_code'] . "] is " . $response['status_message'];
	}
	```
 
## Setting up basic information

Given you have a cart ready for checkout, the first step you have to do to interact with Veritrans is to create a Veritrans instance and populating it with basic information.

```php

// Create a new Veritrans instance. It will default to V1 API and VT-Web payment method.
$veritrans = new Veritrans();

$veritrans->order_id = 'XDF1AA5'; // change the order_id property with your actual order ID.
```

Before you can start using the wrapper, you have to set your API keys in order to let yourself get authenticated by Veritrans API. You have to set your keys by setting the `server_key` property with the Server Key from your account [here](https://my.sandbox.veritrans.co.id/settings/config_info).

```php
//TODO: Change with your actual server key
$veritrans->server_key = 'eebadfec-fa3a-496a-8ea0-bb5795179ce6';
```

### Environment

There are two environments in Veritrans:

1. __Development__ environment, which is defined as `Veritrans::ENVIRONMENT_DEVELOPMENT` and
2. __Production__ environment, which is defined as `Veritrans::ENVIRONMENT_PRODUCTION`.

Veritrans PHP will default to the __Development__ environment. You can set the environment by accessing the `environment` property.

```php
// Set the environment to production
$veritrans->environment = Veritrans::ENVIRONMENT_DEVELOPMENT; // change to ENVIRONMENT_PRODUCTION in your production server
```

### Payment types

Independently from the versions, you may define the payment type which will be used by the library. The available payment types are also defined in the `veritrans.php` file.

```php
class Veritrans {
	// ...
	/***
	  *
	  * VT-Web payment type
	  *
	  */
	const VT_WEB = 0;

	/***
	  *
	  * VT-Direct payment type
	  *
	  */
	const VT_DIRECT = 1;
	// ...	
}
```

At initialization, Veritrans defaults to `VT_WEB` type. You can change the payment method by accessing the `payment_type` method.

```php
$veritrans->payment_type = Veritrans::VT_WEB; // change to VT_DIRECT if you wish to use VT-Direct payment type
```

### Setting up customer's billing information

To set up your customer information, you can manipulate the following properties:

```php
$veritrans->first_name = "Andri"; // obligatory in V2 API
$veritrans->last_name = "Setiawan";
$veritrans->email = "customer@email.com"; // obligatory in V2 API
$veritrans->city = "Jakarta"; // obligatory in V2 API
$veritrans->country_code = "IDN";
$veritrans->postal_code = "12345"; // obligatory in V2 API
$veritrans->phone = "08123123123123";

// To define the customer's billing address, you can either set the address1 and address2 properties...
$veritrans->address1 = "Karet Belakang"; // obligatory in V2 API
$veritrans->address2 = "Setiabudi";

// ...or by setting the address property
$veritrans->address = "Karet Belakang Setiabudi";
```

### Do you need a shipping address?

Whether you need a shipping address or not depends on the type of the merchandise your customer orders. 
For example, if your customer orders an electornic airline ticket that will be sent online, you do not
need to define a shipping address. In other case, if you want to ship the merchandise, you need to tell Veritrans the
the shipping address of the order.

State that you want to send shipping address information by setting the `required_shipping_address` property.

```php
$veritrans->required_shipping_address = 1; // Set '0' if shipping address is not required
```

Now where would you ship your order?

- If it is the same as the billing address, set the `billing_different_with_shipping` flag to `FALSE`.

	```php
	$veritrans->billing_different_with_shipping = FALSE; // Set FALSE if shipping address == billing address
	```

- Otherwise, set it to `FALSE` and complete your shipping information.
	
	```php
	$veritrans->billing_different_with_shipping = TRUE; // Set TRUE if shipping address != billing address

	$veritrans->shipping_first_name = "John";
	$veritrans->shipping_last_name = "Watson";
	$veritrans->shipping_address1 = "Bakerstreet 221B";
	$veritrans->shipping_address2 = "Tebet";
	$veritrans->shipping_city = "Jakarta";
	$veritrans->shipping_country_code = "IDN";
	$veritrans->shipping_postal_code = "12346";
	$veritrans->shipping_phone 	= "082313123131";
	```

### Setting your order detail information

Next, you need to tell Veritrans the detail of the order. The following code illustrates the method to do it.

```php
// Set commodity items
$items = array(
			array(
				"item_id" => 'SHIPPING_COST', // please also include the shipping cost here, if available.
				"price" => 250000, // price must be in IDR. If you have discounts, please do it before you assign it here.
				"quantity"   => 1,
				"item_name1" => 'sepatu',
				"item_name2" => 'Shoes' // item_name2 is only obligatory in V1 API's VT-Web method
			),
			array(
				"item_id" => 'itemdua',
				"price" => 500000,
				"quantity"   => 2,
				"item_name1" => 'Tas',
				"item_name2" => 'Bag'
			),
		);
$veritrans->items = $items;
```

### Setting up your payment options

There are myriads of options to be set with Veritrans. Please consult [this page](http://docs.veritrans.co.id/vtweb/other_features.html) to see the optional features that can be set with Veritrans.

- __Setting the available payment methods:__
	```php
	$veritrans->payment_methods	= array("credit_card", "mandiri_clickpay");
	```

- __3-D Secure:__ Enable a more powerful authentication for your customer. You (or your merchant) must sign additional documents with Veritrans though to activate it. 
  
  ```php
  $veritrans->enable_3d_secure = TRUE; // enable 3d secure for ALL payment methods
  $veritrans->enable_3d_secure = array("credit_card", "mandiri_clickpay"); // enable 3d secure for only "credit_card" and "mandiri_clickpay" methods
	```

- __Promo:__ Set promotion.
  
  ```php
  $veritrans->promo_bins = array("411111", "444444");
  ```

- __Acquiring bank:__ Set the acquiring bank if you have multiple accounts registered with Veritrans.
  
  ```php
  $veritrans->bank = "bni";
  ```  

- __Installment transactions:__ 

	```php

	$veritrans->installment_banks = array("bni", "cimb");
	$veritrans->installment_terms = array(
		'bni' => array(3, 12),
		'cimb' => array(3, 6, 12)
		);
	```

- __Transaction with shopping points:__
	```php
	$veritrans->point_banks	= array("bni", "cimb");
	```

### Forcing sanitization

If you don't want to sanitize the parameters above yourself based on rules [here](http://docs.veritrans.co.id/vtweb/api.html) and [here](http://docs.veritrans.co.id/vtdirect/integrating_vtdirect.html), it is HIGHLY recommended to turn on the auto-sanitization feature to avoid headache and keep the Veritrans server happy :smile:

```php
$veritrans->force_sanitization = TRUE; // defaults to FALSE
```

It will:

1. Trim the strings whose length exceed the maximum length.

2. Take out all blacklisted characters from the parameters.

3. Convert all prices to integer format.

4. Convert country code to ISO 3166-1 alpha-3 format.

## The Veritrans One-Click feature

With this feature, you can let your customer order from your website without entering the credit card information after they placed the first order. To enable this feature:

1. You have to use the VT-Direct payment method.

2. You have to enable the 3D Secure option.

3. You have to enable the `save_token_id` property when your customer order at the first time.

Then, after you invoke the `charge()` method, you can save the `saved_token_id` and `saved_token_id_expired_at` properties in your database.

```php
$response = $veritrans->charge();
$customer = new Customer($_GET['customer_id']); // let the Customer be the class which holds the customer information.
$customer->saved_veritrans_token_id = $response['saved_token_id'];
$customer->saved_veritrans_token_id_expired_at = $response['saved_token_id_expired_at'];
$customer->save();
```

## Contributing

### Developing e-commerce plug-ins

There are several guides that must be taken care of when you develop new plugins.

1. __Handling currency other than IDR.__ Veritrans `v1` and `v2` currently accepts payments in Indonesian Rupiah only. As a corrolary, there is a validation on the server to check whether the item prices are in integer or not. As much as you are tempted to round-off the price, DO NOT do that! Always prepare when your system uses currencies other than IDR, convert them to IDR accordingly, and only round the price AFTER that.

2. Consider using the __auto-sanitization__ feature.

### Developing API for new API versions

## Credits
