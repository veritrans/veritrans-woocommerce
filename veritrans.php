<?php

// PHP Wraper for Veritrans VT-Web Payment API.

require_once 'lib/PestJSON.php';
require_once 'lib/hash_generator.php';
require_once 'veritrans_notification.php';

class Veritrans
{

  const REQUEST_KEY_URL       = 'https://vtweb.veritrans.co.id/v1/tokens';
  const PAYMENT_REDIRECT_URL  = 'https://vtweb.veritrans.co.id/v1/payments';

  // Required parameters
  private $version = '1';
  private $merchant_id;
  private $merchant_hash_key;

  private $order_id;
  private $billing_different_with_shipping;
  private $required_shipping_address;
  
  // Shipping info [Required field if required_shipping_address = 1]
  private $shipping_first_name;
  private $shipping_last_name;
  private $shipping_address1;
  private $shipping_address2;
  private $shipping_city;
  private $shipping_country_code;
  private $shipping_postal_code;
  private $shipping_phone;
  private $email;
  
  // Billing info [optional]
  private $first_name;
  private $last_name;
  private $address1;
  private $address2;
  private $city;
  private $country_code;
  private $postal_code;
  private $phone; 

  // Payment options [optional]
  private $payment_methods;
  private $promo_bins;
  private $enable_3d_secure;
  private $point_banks;
  private $installment_banks; 
  private $installment_terms;   

  private $bank;

  // Redirect url configuration [optional. Can also be set at Merchant Administration Portal(MAP)]
  private $finish_payment_return_url;
  private $unfinish_payment_return_url;
  private $error_payment_return_url;
  
  /*
    Sample of array of commodity items
    array (
      array("item_id" => 'sku1', "price" => '10000', "quantity" => '2', "item_name1" => 'Kaos', "item_name2" => 'T-Shirt'),
      array("item_id" => 'sku2', "price" => '20000', "quantity" => '1', "item_name1" => 'Celana', "item_name2" => 'Pants')
      )
  */
  private $items;

  public function __get($property) 
  {
    if (property_exists($this, $property))
    {
      return $this->$property;
    }
  }

  public function __set($property, $value) 
  {
    $this->$property = $value;
    return $this;
  }

  function __construct($params = null) 
  {

  }

  public function getTokens()
  {    
    // Generate merchant hash code

    $hash = HashGenerator::generate($this->merchant_id, $this->merchant_hash_key, $this->order_id);

    // populate parameters for the post request
    $data = array(
      'version'                     => $this->version,
      'merchant_id'                 => $this->merchant_id,
      'merchanthash'                => $hash,
	  
      'order_id'                        => $this->order_id,
      'billing_different_with_shipping' => $this->billing_different_with_shipping,
      'required_shipping_address'       => $this->required_shipping_address,
	  
      'shipping_first_name'         => $this->shipping_first_name,
      'shipping_last_name'          => $this->shipping_last_name,
      'shipping_address1'           => $this->shipping_address1,
      'shipping_address2'           => $this->shipping_address2,
      'shipping_city'               => $this->shipping_city,
      'shipping_country_code'       => $this->shipping_country_code,
      'shipping_postal_code'        => $this->shipping_postal_code,
      'shipping_phone'              => $this->shipping_phone,

      'email'                       => $this->email, 
      
      'first_name'                  => $this->first_name,
      'last_name'                   => $this->last_name,
      'postal_code'                 => $this->postal_code,
      'address1'                    => $this->address1,
      'address2'                    => $this->address2,
      'city'                        => $this->city,
      'country_code'                => $this->country_code,
      'phone'                       => $this->phone,      
      
      'finish_payment_return_url'   => $this->finish_payment_return_url,
      'unfinish_payment_return_url' => $this->unfinish_payment_return_url,
      'error_payment_return_url'    => $this->error_payment_return_url,

      'enable_3d_secure'            => $this->enable_3d_secure, 
      'bank'                        => $this->bank,
      'installment_banks'           => $this->installment_banks, //array ["bni", "cimb"]
      'promo_bins'                  => $this->promo_bins,
      'point_banks'                 => $this->point_banks,
      'payment_methods'             => $this->payment_methods, //array ["credit_card", "mandiri_clickpay"]
      'installment_terms'           => $this->installment_terms
      );

    // Populate items
    $data['repeat_line'] = 0;
    foreach ($this->items as $item) {
      $item_id[]    = $item['item_id'];
      $item_name1[] = $item['item_name1'];
      $item_name2[] = $item['item_name2'];
      $price[]      = $item['price'];
      $quantity[]   = $item['quantity'];
      
      $data['repeat_line'] ++;
    }

    $data['item_id']    = $item_id;
    $data['item_name1'] = $item_name1;
    $data['item_name2'] = $item_name2;
    $data['price']      = $price;
    $data['quantity']   = $quantity;

    // Call Veritrans API
    try {
      $pest = new PestJSON();
      $result = $pest->post(self::REQUEST_KEY_URL, $data);
    } catch (Exception $e) {
      throw $e;
    }

    // Check result
    if(!empty($result['token_merchant'])) {
      // OK
      return $result;
    }
    else {
      // Veritrans doesn't return tokens
      $this->errors = $result['errors'];
      return false;
    }
  }
}

?>
