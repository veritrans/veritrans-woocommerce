<?php

// Wraper for veritrans weblink type payment

require_once 'lib/config.php';
require_once 'lib/Pest.php';
require_once 'lib/hash_generator.php';
require_once 'veritrans_notification.php';

class Veritrans
{
  // Required Params
  private $settlement_type = '01'; // 00:payment type not set, 01:credit card settlement 
  private $merchant_id;
  private $order_id;
  private $session_id;
  private $gross_amount;
  private $merchanthash;
  private $card_capture_flag;

  // Optional Params
  private $email;
  private $first_name;
  private $last_name;
  private $postal_code;
  private $address1;
  private $address2;
  private $city;
  private $country_code;
  private $phone;
  private $birthday;
  private $sex; // 1:male, 2:female, 3:other
  private $card_no;
  private $card_exp_date; // mm/yy/format
  private $card_holder_name;
  private $card_number_of_installment;
  private $settlement_sub_type; 
  private $shop_name;
  private $screen_title;
  private $contents;
  private $timelimit_of_payment;
  private $timelimit_of_cancel;
  private $lang_enable_flag;
  private $lang;
  private $commodity_id;
  private $commodity_unit;
  private $commodity_num;
  private $commodity_name;
  private $jan_conde;

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
      $this->$property = $value;
    }

    return $this;
  }

  function __construct($params = null) 
  {

  }

  public function get_keys()
  {
    $this->gross_amount = '2';
    
    // Generate merchant hash code
    $hash = HashGenerator::generate(MERCHANT_ID, $this->settlement_type, $this->order_id, $this->gross_amount);
    
    // echo $hash;
    // exit();
    // $hash = HashGenerator::generate(MERCHANT_ID, '01', $this->order_id, '20');


    // populate parameters for the post request
    $data = array(
      'SETTLEMENT_TYPE'             => '01',
      'MERCHANT_ID'                 => MERCHANT_ID,
      'ORDER_ID'                    => $this->order_id,
      'SESSION_ID'                  => $this->session_id,
      'GROSS_AMOUNT'                => $this->gross_amount,
      'PREVIOUS_CUSTOMER_FLAG'      => '1',
      'CUSTOMER_STATUS'             => '',
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
      'SHIPPING_INPUT_FLAG'         => '1',
      'SHIPPING_SPECIFICATION_FLAG' => '1',
      'SHIPPING_FIRST_NAME'         => $this->first_name,
      'SHIPPING_LAST_NAME'          => $this->last_name,
      'SHIPPING_ADDRESS1'           => 'oke',
      'SHIPPING_ADDRESS2'           => 'Minatoku',
      'SHIPPING_CITY'               => 'Tokyo',
      'SHIPPING_COUNTRY_CODE'       => 'JPN',
      'SHIPPING_POSTAL_CODE'        => '1606028',
      'SHIPPING_PHONE'              => '03111122229',
      'CARD_NO'                     => '4111111111111111',
      'CARD_EXP_DATE'               => '11/14', // mm/yy/form
      'FINISH_PAYMENT_RETURN_URL'   => FINISH_PAYMENT_RETURN_URL,
      'UNFINISH_PAYMENT_RETURN_URL' => UNFINISH_PAYMENT_RETURN_URL,
      'ERROR_PAYMENT_RETURN_URL'    => ERROR_PAYMENT_RETURN_URL,
      'LANG_ENABLE_FLAG'            => '',
      'LANG'                        => '',
      // 'REPEAT_LINE'                  => '1',
      // 'COMMODITY_ID'                => 'IDxx1',
      // 'COMMODITY_UNIT'              => '10',
      // 'COMMODITY_NUM'               => '1',
      // 'COMMODITY_NAME1'             => 'Waterbostlea',
      // 'COMMODITY_NAME2'             => 'Waterbotstleaaa in Indonesian',
      // 'COMMODITY_ID'                => 'IDxx12',
      // 'COMMODITY_UNIT'              => '10',
      // 'COMMODITY_NUM'               => '1',
      // 'COMMODITY_NAME1'             => 'Waterbostle',
      // 'COMMODITY_NAME2'             => 'Waterbotstle in Indonen1'
	  'COMMODITY'                   => 
	    array(
	      array("COMMODITY_ID" => "123", "COMMODITY_UNIT" => "1", "COMMODITY_NUM" => "1", "COMMODITY_NAME1" => "BUKU", "COMMODITY_NAME2" => "BOOK"),
	      array("COMMODITY_ID" => "123", "COMMODITY_UNIT" => "1", "COMMODITY_NUM" => "1", "COMMODITY_NAME1" => "BUKU", "COMMODITY_NAME2" => "BOOK")
	    )
      );

	$line = 0;
	$query_string = "";
	foreach ($data["COMMODITY"] as $row) {
      $q = http_build_query($row);
      if(!($query_string=="")) 
        $query_string = $query_string . "&";
      $query_string = $query_string . $q;
      $line = $line + 1;
	};
	$query_string = $query_string . "&REPEAT_LINE=" . $line;

	$clone = array($data);
	$clone = $clone[0];
	unset($clone["COMMODITY"]);

	$query_string = http_build_query($clone) . "&" . $query_string;
		
    $client = new Pest(REQUEST_KEY_URL);
    $result = $client->post('', $query_string);

    $key = $this->extract_keys_from($result);

    return $key;
  }

  // Private methods
  // return array of keys or error
  private function extract_keys_from($body)
  {
    
    $key = array();
    $body_lines = explode("\n", $body);
    foreach($body_lines as $line) {
      if(preg_match('/^MERCHANT_ENCRYPTION_KEY=(.+)/', $line, $match)) {
        $key['merchant_key'] = str_replace("\r", "", $match[1]);
        } elseif(preg_match('/^BROWSER_ENCRYPTION_KEY=(.+)/', $line, $match)) {
          $key['browser_key'] = str_replace("\r", "", $match[1]);
          } elseif(preg_match('/^ERROR_MESSAGE=(.+)/', $line, $match)) {
            $key['error_message'] = str_replace("\r", "", $match[1]);
          }
        }

        return $key;

      }

  }

?>
