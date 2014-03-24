<?php

// PHP Wrapper for Veritrans VT-Web Payment API.

require_once 'veritrans_factory.php';

class Veritrans
{

  const REQUEST_KEY_URL       = 'https://vtweb.veritrans.co.id/v1/tokens.json';
  const PAYMENT_REDIRECT_URL  = 'https://vtweb.veritrans.co.id/v1/payments.json';

  const STACK_2013 = 1;
  const STACK_2014 = 2;

  const ENVIRONMENT_DEVELOPMENT = 0;
  const ENVIRONMENT_PRODUCTION = 1;

  const VT_WEB = 0;
  const VT_DIRECT = 1;

  private $data = array();

  //// Required parameters
  // private $version = 1;
  // private $merchant_id;
  // private $merchant_hash_key;

  //// private $order_id;
  // private $billing_different_with_shipping;
  // private $required_shipping_address;
  
  //// Shipping info [Required field if required_shipping_address = 1]
  // private $shipping_first_name;
  // private $shipping_last_name;
  // private $shipping_address1;
  // private $shipping_address2;
  // private $shipping_city;
  // private $shipping_country_code;
  // private $shipping_postal_code;
  // private $shipping_phone;
  // private $email;
  
  //// Billing info [optional]
  // private $first_name;
  // private $last_name;
  // private $address1;
  // private $address2;
  // private $city;
  // private $country_code;
  // private $postal_code;
  // private $phone; 

  //// Payment options [optional]
  // private $payment_methods;
  // private $promo_bins;
  // private $enable_3d_secure;
  // private $point_banks;
  // private $installment_banks; 
  // private $installment_terms;   

  // private $bank;

  //// Redirect url configuration [optional. Can also be set at Merchant Administration Portal(MAP)]
  // private $finish_payment_return_url;
  // private $unfinish_payment_return_url;
  // private $error_payment_return_url;
  
  /*
    Sample of array of commodity items
    array (
      array("item_id" => 'sku1', "price" => 10000, "quantity" => 2, "item_name1" => 'Kaos', "item_name2" => 'T-Shirt'),
      array("item_id" => 'sku2', "price" => 20000, "quantity" => 1, "item_name1" => 'Celana', "item_name2" => 'Pants')
      )
  */
  private $items;

  public function __get($property) 
  {
    return $this->data[$property];
  }

  public function __set($property, $value) 
  {
    $this->data[$property] = $value;
    return $value;
  }

  public function __construct($params = null) 
  {
    // maintain compatibility with vt-web2 branch by setting default variables.
    $this->version = self::STACK_2013;
    $this->environment = self::ENVIRONMENT_DEVELOPMENT;
    $this->veritrans_method = self::VT_WEB;
    $this->veritrans_factory = new Veritrans\Factory($this);
  }

  public function getTokens()
  {
    return $this->veritrans_factory->get()->getTokens();
  }

  public function getData()
  {
    return $this->data;
  }

}

?>
