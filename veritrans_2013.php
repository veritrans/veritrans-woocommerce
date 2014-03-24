<?php 

namespace Veritrans;

require_once 'lib/hash_generator.php';
require_once 'lib/Pest.php';
require_once 'lib/PestJSON.php';

class Veritrans2013 {

  private $veritrans;

  public function __construct($veritrans)
  {
    $this->veritrans = $veritrans;
  }

  public function getTokens()
  {    
    // Generate merchant hash code

    $hash = \HashGenerator::generate($this->veritrans->merchant_id, $this->veritrans->merchant_hash_key, $this->veritrans->order_id);

    // populate parameters for the post request
    $data = array(
      'version'                     => $this->veritrans->version,
      'merchant_id'                 => $this->veritrans->merchant_id,
      'merchanthash'                => $hash,
    
      'order_id'                        => $this->veritrans->order_id,
      'billing_different_with_shipping' => $this->veritrans->billing_different_with_shipping,
      'required_shipping_address'       => $this->veritrans->required_shipping_address,
    
      'shipping_first_name'         => $this->veritrans->shipping_first_name,
      'shipping_last_name'          => $this->veritrans->shipping_last_name,
      'shipping_address1'           => $this->veritrans->shipping_address1,
      'shipping_address2'           => $this->veritrans->shipping_address2,
      'shipping_city'               => $this->veritrans->shipping_city,
      'shipping_country_code'       => $this->veritrans->shipping_country_code,
      'shipping_postal_code'        => $this->veritrans->shipping_postal_code,
      'shipping_phone'              => $this->veritrans->shipping_phone,

      'email'                       => $this->veritrans->email, 
      
      'first_name'                  => $this->veritrans->first_name,
      'last_name'                   => $this->veritrans->last_name,
      'postal_code'                 => $this->veritrans->postal_code,
      'address1'                    => $this->veritrans->address1,
      'address2'                    => $this->veritrans->address2,
      'city'                        => $this->veritrans->city,
      'country_code'                => $this->veritrans->country_code,
      'phone'                       => $this->veritrans->phone,      
      
      'finish_payment_return_url'   => $this->veritrans->finish_payment_return_url,
      'unfinish_payment_return_url' => $this->veritrans->unfinish_payment_return_url,
      'error_payment_return_url'    => $this->veritrans->error_payment_return_url,

      'enable_3d_secure'            => $this->veritrans->enable_3d_secure, 
      'bank'                        => $this->veritrans->bank,
      'installment_banks'           => $this->veritrans->installment_banks, //array ["bni", "cimb"]
      'promo_bins'                  => $this->veritrans->promo_bins,
      'point_banks'                 => $this->veritrans->point_banks,
      'payment_methods'             => $this->veritrans->payment_methods, //array ["credit_card", "mandiri_clickpay"]
      'installment_terms'           => $this->veritrans->installment_terms
      );

    // Populate items
    $data['repeat_line'] = 0;
    foreach ($this->veritrans->items as $item) {
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
      $pest = new \PestJSON('');
      $result = $pest->post(\Veritrans::REQUEST_KEY_URL, $data);
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