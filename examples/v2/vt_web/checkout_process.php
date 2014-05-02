<?php

if(empty($_POST)) 
{
  echo "Empty data.";
  exit;
}

require '../../../veritrans.php';

$veritrans = new Veritrans();
$veritrans->server_key = 'eebadfec-fa3a-496a-8ea0-bb5795179ce6'; // change with your development/production server key
$veritrans->version = 2; // since 2014's stack is currently not the default one, we need to set it explicitly

//TODO: Change with your actual order_id.
$veritrans->order_id = 'order'.rand();

$veritrans->billing_different_with_shipping = 1;
$veritrans->required_shipping_address = 1;

// Billing info. [Optional]
$veritrans->first_name = $_POST['billing_first_name'];
$veritrans->last_name = $_POST['billing_last_name'];
$veritrans->address1 = $_POST['billing_address1'];
$veritrans->address2 = $_POST['billing_address2'];
$veritrans->city = $_POST['billing_city'];
$veritrans->country_code = "IDN";
$veritrans->postal_code = $_POST['billing_postal_code'];
$veritrans->phone = $_POST['billing_phone'];

// Shipping info. [Required if required_shipping_address = '1']
$veritrans->shipping_first_name = $_POST['shipping_first_name'];
$veritrans->shipping_last_name = $_POST['shipping_last_name'];
$veritrans->shipping_address1 = $_POST['shipping_address1'];
$veritrans->shipping_address2 = $_POST['shipping_address2'];
$veritrans->shipping_city = $_POST['shipping_city'];
$veritrans->shipping_country_code = "IDN";
$veritrans->shipping_postal_code = $_POST['shipping_postal_code'];
$veritrans->shipping_phone = $_POST['shipping_phone'];
$veritrans->email = $_POST['email'];

// Payment options
// $veritrans->enable_3d_secure = 1;
$veritrans->bank         = "mandiri";
// $veritrans->installment_banks  = array("bni", "cimb");
$veritrans->promo_bins     = array("4");
// $veritrans->point_banks      = array("bni", "cimb");
// $veritrans->payment_methods    = array("credit_card", "mandiri_clickpay");
// $veritrans->installment_terms   = array(
//  'bni' => array(3,12),
//  'cimb' => array(3, 6, 12)
//  );

// Set commodity items. This is just sample items.
// TODO: Change with your actual items.
$items = array(
      array(
        "item_id" => 'itemsatu',
        "price" => 250000,
        "quantity"   => 1,
        "item_name1" => 'sepatu',
        "item_name2" => 'Shoes'
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

//Call Veritrans VT-Web API Get Token

try {

  $keys = $veritrans->getTokens();
  
  if(!in_array($keys['status_code'], array(201, 202, 203))) 
  {

    // print the error
    var_dump($veritrans->errors);
    
    exit();

  } else {

    header('Location: ' . $keys['redirect_url']);
  }
} catch (Exception $e) {
  var_dump($e);
}


//Redirect customer to Veritrans payment page.

?>