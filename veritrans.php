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
  
  

  // Sample of array of commodity
  // array(
  //           array("COMMODITY_ID" => "123", "COMMODITY_UNIT" => "1", "COMMODITY_NUM" => "1", "COMMODITY_NAME1" => "BUKU", "COMMODITY_NAME2" => "BOOK"),
  //           array("COMMODITY_ID" => "1243", "COMMODITY_UNIT" => "9", "COMMODITY_NUM" => "1", "COMMODITY_NAME1" => "BUKU Sembilan", "COMMODITY_NAME2" => "BOOK NINE")
  //       )
  
  private $commodity;

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
    $hash = HashGenerator::generate($this->merchant_id, $this->merchant_hash_key, $this->order_id);


    // populate parameters for the post request
    $data = array(
      'MERCHANT_ID'                 => $this->merchant_id,
      'ORDER_ID'                    => $this->order_id,
      'GROSS_AMOUNT'                => $this->gross_amount,               
      'MERCHANTHASH'                => $hash,  
      'EMAIL'                       => $this->email, 
      'FIRST_NAME'                  => $this->first_name,
      'LAST_NAME'                   => $this->last_name,
      'POSTAL_CODE'                 => $this->postal_code,
      'ADDRESS1'                    => $this->address1,
      'ADDRESS2'                    => $this->address2,
      'CITY'                        => $this->city,
      'COUNTRY_CODE'                => $this->country_code,
      'PHONE'                       => $this->phone,
      'SHIPPING_FLAG'               => $this->required_shipping_address,                 
      'SHIPPING_FIRST_NAME'         => $this->shipping_first_name,
      'SHIPPING_LAST_NAME'          => $this->shipping_last_name,
      'SHIPPING_ADDRESS1'           => $this->shipping_address1,
      'SHIPPING_ADDRESS2'           => $this->shipping_address2,
      'SHIPPING_CITY'               => $this->shipping_city,
      'SHIPPING_COUNTRY_CODE'       => $this->shipping_country_code,
      'SHIPPING_POSTAL_CODE'        => $this->shipping_postal_code,
      'SHIPPING_PHONE'              => $this->shipping_phone,
      'SHIPPING_METHOD'             => $this->shipping_method,
      'FINISH_PAYMENT_RETURN_URL'   => $this->finish_payment_return_url,
      'UNFINISH_PAYMENT_RETURN_URL' => $this->unfinish_payment_return_url,
      'ERROR_PAYMENT_RETURN_URL'    => $this->error_payment_return_url,
      'enable_3d_secure'            => $this->enable_3d_secure           
      );

    // data query string only without commodity
    $query_string = http_build_query($data);
    
    // Build Commodity
    if(isset($this->commodity)){
      $commodity_query_string = $this->build_commodity_query_string($this->commodity);
      $query_string = "$query_string&$commodity_query_string";
    }
    
    // Build Installment Banks
    if(isset($this->installment_banks)){
      foreach ($this->installment_banks as $bank){
        $query_string = "$query_string&installment_banks[]=$bank";
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
  // return array of commodities
  private function build_commodity_query_string($commodity)
  {
    $line = 0;
  	$query_string = "";
  	foreach ($commodity as $row) {
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
       $commodity["COMMODITY_NUM"] = $commodity["COMMODITY_QTY"];
       unset($commodity["COMMODITY_QTY"]);
     }
     if(array_key_exists("COMMODITY_PRICE", $commodity) && $commodity["COMMODITY_PRICE"] != '')
     {
       $commodity["COMMODITY_UNIT"] = $commodity["COMMODITY_PRICE"];
       unset($commodity["COMMODITY_PRICE"]);
     }
     
     return $commodity;
   }

  }

?>
