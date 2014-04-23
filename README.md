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

### Setting up basic information

Given you have a cart ready for checkout, the first step you have to do to interact with Veritrans is to create a Veritrans instance and populating it with basic information.

```php

// Create a new Veritrans instance. It will default to V1 API and VT-Web payment method.
$veritrans = new Veritrans();

$veritrans->order_id = 'XDF1AA5'; // change the order_id property with your actual order ID.
```

### About Veritrans API version

Veritrans API is always evolving. As a result, there are several versions which are improved in each subsequent version. Veritrans defines the default version, which is defined by the version which is used most by the client.

```php
class Veritrans {
	// ...
	const VERSION_STABLE = 1;
	// ...	
}
```

At initialization, the version will be always initialized to `VERSION_STABLE`, which points to `1` in this case.
You can set the version you want to use by manipulating the `version` property.

```php
$veritrans->version = 1; // Please use v1 at the moment...
// $veritrans->version = 2; // ...because Veritrans v2 is still experimental
$veritrans->version = Veritrans::VERSION_STABLE; // or use VERSION_STABLE constant
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

The handling of customer information is different in each version.

- __V.1:__ It is optional to set-up the customer information, but if you are developing plugins using this library, it is __highly__ recommended to set it up to give the best user experience. Otherwise, your customer have to fill them in the VT-Web page.

- __V.2:__ Some of the customer information fields become obligatory in the API V2, which will be displayed in the example below.

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

- __3-D Secure:__ Enable a more powerful authentication for your customer. You (or your merchant) must sign additional documents with Veritrans though to activate it. 
  
  ```php
  $veritrans->enable_3d_secure = TRUE;
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

- __Setting the available payment methods:__
	```php
	$veritrans->payment_methods	= array("credit_card", "mandiri_clickpay");
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

## Step 2: Using the API

Before you can start using the wrapper, you have to set your API keys in order to let yourself get authenticated by Veritrans API. The methods to set the keys and the response are different for each API version.

#### V1 VT-Web

In the current version (2013), set the `merchant_id` property with your Merchant ID and `merchant_hash_key` with your Merchant Hash Key. Both of them are available [here](https://payments.veritrans.co.id/map).

```php
//TODO: Change with your actual merchant id and merchant hash key
$veritrans->merchant_id = 'T100000000000001000001';
$veritrans->merchant_hash_key = '305e0328a366cbce8e17a385435bb7eb3f0cbcfbfc0f1c3ef56b658';
```

Next, you have to call the `getTokens()` method to obtain `token_merchant` and `token_browser` first, and use them in a `POST` request to enter the VT-Web page. The example below illustrates their usage.

```php
try {
	
	// Call Veritrans VT-Web API Get Token
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

Soon after the PHP code above, add the following form:

```html
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

#### Responding to V1 VT-Web payment notification

After the payment process is completed, Veritrans will __asynchronously__ send HTTP(S) POST notification to merchant's web server.
As a merchant, you need to process this POST paramters to update order status in your database server. Veritrans will send 3 POST parameters: `orderId`, `mStatus`, and `TOKEN_MERCHANT`.

```php
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

#### V1 VT-Direct

1. Create a HTML form first to obtain a `token_id` from Veritrans. The steps are described [here](http://docs.veritrans.co.id/vtdirect/integrating_vtdirect.html).

2. Next, assign your server key to the Veritrans instance AFTER you obtained the `token_id` from Veritrans.
	 ```php
	 // TODO: Change with your actual server key
	 $veritrans->server_key = 'eebadfec-fa3a-496a-8ea0-bb5795179ce6';
	 ```

3. Next, charge using the `charge()` method.
   ```php
   $status = $veritrans->charge();
   if ($status['status'] == 'success')
   {
     // mark the order as success.
   } else
   {
     // mark the order as failed.
   }
   ```

### V2 API

If you set the `version` to `2`, you have to set your keys by setting the `server_key` property with the Server Key from your account. The server key can be obtained [here](https://my.sandbox.veritrans.co.id/settings/config_info).

```php
//TODO: Change with your actual server key
$veritrans->server_key = 'eebadfec-fa3a-496a-8ea0-bb5795179ce6';
```

#### V2 VT-Web

The method to enter the VT-Web page is a little different in V2 API. Instead of sending a POST request, you can simply redirect your request to the page obtained from the `getTokens()` method.

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

#### Responding to V2 VT-Web payment notification

#### V2 VT-Direct

1. Create a HTML form first to obtain a `token_id` from Veritrans.

2. Next, assign your server key to the Veritrans instance AFTER you obtained the `token_id` from Veritrans.
	 ```php
	 // TODO: Change with your actual server key
	 $veritrans->server_key = 'eebadfec-fa3a-496a-8ea0-bb5795179ce6';
	 ```

3. Next, charge using the `charge()` method.
   ```php
   // TODO
   ```

## Contributing

### Developing e-commerce plug-ins

There are several guides that must be taken care of when you develop new plugins.

1. __Handling currency other than IDR.__ Veritrans `v1` and `v2` currently accepts payments in Indonesian Rupiah only. As a corrolary, there is a validation on the server to check whether the item prices are in integer or not. As much as you are tempted to round-off the price, DO NOT do that! Always prepare when your system uses currencies other than IDR, convert them to IDR accordingly, and only round the price AFTER that.

2. Consider using the __auto-sanitization__ feature.

### Developing API for new API versions

## Credits
