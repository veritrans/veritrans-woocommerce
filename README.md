Veritrans PHP Wrapper
==============================================

Veritrans :heart: PHP!

This is the official PHP wrapper for Veritrans Payment API. Visit [https://www.veritrans.co.id](https://www.veritrans.co.id) for more information about the product and see documentation at [http://docs.veritrans.co.id](http://docs.veritrans.co.id/vtweb/index.html) for more technical details.

## Installation

### Composer Installation

If you are using [Composer](https://getcomposer.org), add this require line to your `composer.json` file:

```json
"require": {
	"veritrans/veritrans-php": "veritrans-2"
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

If you don't want to sanitize the parameters above yourself based on rules [here](http://docs.veritrans.co.id/vtweb/api.html) and [here](http://docs.veritrans.co.id/vtdirect/integrating_vtdirect.html), it is HIGHLY recommended to turn on the auto-sanitization feature.

```php
$veritrans->force_sanitization = TRUE; // defaults to FALSE
```

## Step 2: Using the API

Before you can start using the wrapper, you have to set your API keys in order to let yourself get authenticated by Veritrans API. The methods to set the keys and the response are different for each API version.

You have to set your keys by setting the `server_key` property with the Server Key from your account. The server key can be obtained [here](https://my.sandbox.veritrans.co.id/settings/config_info).

```php
//TODO: Change with your actual server key
$veritrans->server_key = 'eebadfec-fa3a-496a-8ea0-bb5795179ce6';
```

Next, obtain a token by calling `getTokens()` method.

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

### Responding to the payment notification

1. Create a HTML form first to obtain a `token_id` from Veritrans.

2. Next, assign your server key to the Veritrans instance AFTER you obtained the `token_id` from Veritrans.
	 ```php
	 // TODO: Change with your actual server key
	 $veritrans->server_key = 'eebadfec-fa3a-496a-8ea0-bb5795179ce6';
	 ```

3. Next, charge using the `charge()` method.
   ```php
   if ($veritrans->charge())
   {

   } else
   {

   }
   ```

## Contributing

### Developing e-commerce plug-ins

There are several guides that must be taken care of when you develop new plugins.

1. __Handling currency other than IDR.__ Veritrans `v1` and `v2` currently accepts payments in Indonesian Rupiah only. As a corrolary, there is a validation on the server to check whether the item prices are in integer or not. As much as you are tempted to round-off the price, DO NOT do that! Always prepare when your system uses currencies other than IDR, convert them to IDR accordingly, and only round the price AFTER that.

2. Consider using the __auto-sanitization__ feature.

### Developing API for new API versions

## Credits
