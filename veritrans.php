<?php

// Wraper for veritrans weblink type payment
require_once 'lib/Pest.php';
require_once 'lib/hash_generator.php';
require_once 'veritrans_notification.php';

class Veritrans
{
  const REQUEST_KEY_URL = 'https://vtweb.veritrans.co.id/v1/tokens';
  const PAYMENT_REDIRECT_URL = 'https://vtweb.veritrans.co.id/v1/payments';
  
  // Required parameters
  private $merchant_id;
  private $order_id;
  private $merchant_hash_key;
  private $billing_different_with_shipping;
  private $required_shipping_address;
  private $version;
  
  // Required field if required_shipping_address = 1
  private $shipping_first_name;
  private $shipping_last_name;
  private $shipping_address1;
  private $shipping_address2;
  private $shipping_city;
  private $shipping_country_code;
  private $shipping_postal_code;
  private $shipping_phone;
  private $email;

  // Optional parameters
  private $payment_methods;
  private $finish_payment_return_url;
  private $unfinish_payment_return_url;
  private $error_payment_return_url;
  
  private $first_name;
  private $last_name;
  private $address1;
  private $address2;
  private $city;
  private $country_code;
  private $postal_code;
  private $phone; 
  
  private $promo_bins;
  private $enable_3d_secure;
  private $point_banks;
  private $installment_banks; 
  private $installment_terms;   
  private $bank;  
  
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
    if (property_exists($this, $property)) 
    {
      $alias_attributes = $this->alias_attributes();
      
      if(array_key_exists($property, $alias_attributes))
      {
        // set the corresponding attribute
        $this->$property = $value;
        // set the aliassed attribute
        $this->$alias_attributes[$property] = $value;
      }
      else
      {
        $deprecated_attributes = $this->deprecated_attributes();
        if(array_key_exists($property, $deprecated_attributes)){
          trigger_error("$property is deprecated use $deprecated_attributes[$property] instead");
          $this->$deprecated_attributes[$property] = $value;
        }
        $this->$property = $value;
       
      }
        
    }

    return $this;
  }

  function __construct($params = null) 
  {

  }

  public function get_keys()
  {    
    // Generate merchant hash code
    $hash = HashGenerator::generate($this->merchant_hash_key, $this->merchant_id, $this->order_id);


    // populate parameters for the post request
    $data = array(
      'merchant_id'                 => $this->merchant_id,
      'order_id'                    => $this->order_id,             
      'merchanthash'                => $hash,  
	  'version'						=> $this->version,
      'email'                       => $this->email, 
      'first_name'                  => $this->first_name,
      'last_name'                   => $this->last_name,
      'postal_code'                 => $this->postal_code,
      'address1'                    => $this->address1,
      'address2'                    => $this->address2,
      'city'                        => $this->city,
      'country_code'                => $this->country_code,
      'phone'                       => $this->phone,
      'required_shipping_address'   => $this->required_shipping_address,                 
      'shipping_first_name'         => $this->shipping_first_name,
      'shipping_last_name'          => $this->shipping_last_name,
      'shipping_address1'           => $this->shipping_address1,
      'shipping_address2'           => $this->shipping_address2,
      'shipping_city'               => $this->shipping_city,
      'shipping_country_code'       => $this->shipping_country_code,
      'shipping_postal_code'        => $this->shipping_postal_code,
      'shipping_phone'              => $this->shipping_phone,
      'finish_payment_return_url'   => $this->finish_payment_return_url,
      'unfinish_payment_return_url' => $this->unfinish_payment_return_url,
      'error_payment_return_url'    => $this->error_payment_return_url,
	  'payment_methods'				=> $this->payment_methods,
      'enable_3d_secure'            => $this->enable_3d_secure, 
	  'promo_bins'                  => $this->promo_bins,
	  'point_banks'					=> $this->point_banks,
      'installment_banks'			=> $this->installment_banks, 
      'installment_terms'			=> $this->installment_terms,   
	  'bank'						=> $this->bank	  
      );

    // data query string only without commodity
    $query_string = http_build_query($data);
    
	
    // Build Commodity items
    if(isset($this->items)){
      $items_query_string = $this->build_items_query_string($this->items);
      $query_string = "$query_string&$items_query_string";
    }
	
    
    // Build Installment Banks
    if(isset($this->installment_banks)){
      foreach ($this->installment_banks as $bank){
        $query_string = "$query_string&installment_banks[]=$bank";
      }
    }
	
	
	// Build Payment Methods
    if(isset($this->payment_methods)){
      foreach ($this->payment_methods as $methods){
        $query_string = "$query_string&payment_methods[]=$methods";
      }
    }
	
    
    // Build Installment Terms
    if(isset($this->installment_terms)){
      $query_string = "$query_string&installment_terms=$this->installment_terms";
    }

    // Build Promo Bins
    if(isset($this->promo_bins)){
      foreach ($this->promo_bins as $bin){
        $query_string = "$query_string&promo_bins[]=$bin";
      }
    }
    
    // Build Point Banks
    if(isset($this->point_banks)){
      foreach ($this->point_banks as $bank){
        $query_string = "$query_string&point_banks[]=$bank";
      }
    }
    		
    $client = new Pest(self::REQUEST_KEY_URL);
    $result = $client->post('', $query_string);

    $key = $this->extract_keys_from($result);

    return $key;
  }
  
  // Private methods
  // return array of commodity items
  private function build_items_query_string($items)
  {
    $line = 0;
  	$query_string = "";
  	foreach ($items as $row) {
        $row = $this->replace_commodity_params_with_legacy_params($row);
  	    
        $q = http_build_query($row);
        if(!($query_string=="")) 
          $query_string = $query_string . "&";
        $query_string = $query_string . $q;
        $line = $line + 1;
  	};
  	$query_string = $query_string . "&REPEAT_LINE=" . $line;
  	
  	return $query_string;
  }

  // Private methods
  // return array of keys or error
  private function extract_keys_from($body)  {
    
    $key = array();
    $body_lines = explode("\n", $body);
    foreach($body_lines as $line) {
      if(preg_match('/^TOKEN_MERCHANT=(.+)/', $line, $match)) {
        $key['token_merchant'] = str_replace("\r", "", $match[1]);
        } elseif(preg_match('/^TOKEN_BROWSER=(.+)/', $line, $match)) {
          $key['token_browser'] = str_replace("\r", "", $match[1]);
          } elseif(preg_match('/^ERROR_MESSAGE=(.+)/', $line, $match)) {
            $key['error_message'] = str_replace("\r", "", $match[1]);
          }
      }
    return $key;

   }
   
   private function alias_attributes()
   {
    $alias = array( 'billing_address_different_with_shipping_address' => 'customer_specification_flag',
                    'required_shipping_address' => 'shipping_flag');
    return $alias;
   }
   
   private function deprecated_attributes()
   {
     $alias_attributes = $this->alias_attributes();
     $deprecated_attributes = array_flip($alias_attributes);
     return $deprecated_attributes;
   }
   
   private function replace_commodity_params_with_legacy_params($commodity)
   {
     if(array_key_exists("COMMODITY_QTY", $commodity) && $commodity["COMMODITY_QTY"] != '' )
     {
       $commodity["quantity"] = $commodity["COMMODITY_QTY"];
       unset($commodity["COMMODITY_QTY"]);
     }
     if(array_key_exists("COMMODITY_PRICE", $commodity) && $commodity["COMMODITY_PRICE"] != '')
     {
       $commodity["price"] = $commodity["COMMODITY_PRICE"];
       unset($commodity["COMMODITY_PRICE"]);
     }
	
     if(array_key_exists("COMMODITY_QUANTITY", $commodity) && $commodity["COMMODITY_QUANTITY"] != '' )
     {
       $commodity["quantity"] = $commodity["COMMODITY_QUANTITY"];
       unset($commodity["COMMODITY_QUANTITY"]);
     }
	 if(array_key_exists("COMMODITY_UNIT", $commodity) && $commodity["COMMODITY_UNIT"] != '')
     {
       $commodity["price"] = $commodity["COMMODITY_UNIT"];
       unset($commodity["COMMODITY_UNIT"]);
     }
	 
	 if(array_key_exists("COMMODITY_ID", $commodity) && $commodity["COMMODITY_ID"] != '' )
     {
       $commodity["item_id"] = $commodity["COMMODITY_ID"];
       unset($commodity["COMMODITY_ID"]);
     }
	 
	 if(array_key_exists("COMMODITY_NAME1", $commodity) && $commodity["COMMODITY_NAME1"] != '')
     {
       $commodity["item_name1"] = $commodity["COMMODITY_NAME1"];
       unset($commodity["COMMODITY_NAME1"]);
     }
	 
	 if(array_key_exists("COMMODITY_NAME2", $commodity) && $commodity["COMMODITY_NAME2"] != '')
     {
       $commodity["item_name2"] = $commodity["COMMODITY_NAME2"];
       unset($commodity["COMMODITY_NAME2"]);
     }
     return $commodity;
   }

  }

?>
