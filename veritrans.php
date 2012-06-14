<?php

// Wraper for veritrans weblink type payment

require_once 'lib/config.php';
require_once 'lib/Pest.php';
require_once 'lib/hash_generator.php';
require_once 'veritrans_notification.php';

class Veritrans
{
  // Required Params
  private $settlement_type; // 00:payment type not set, 01:credit card settlement 
  private $merchant_id;
  private $order_id;
  private $session_id;
  private $amount;
  private $merchanthash;
  private $card_capture_flag;

  // Optional Params
  private $mailaddress;
  private $name1;
  private $name2;
  private $zip_code;
  private $address1;
  private $address2;
  private $address3;
  private $telephone_no;
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
    // Generate merchant hash code
    // $hash = HashGenerator::generate(MERCHANT_ID, $this->settlement_type, $this->order_id, $this->amount);
    $hash = HashGenerator::generate(MERCHANT_ID, '01', $this->order_id, '10');


    // populate parameters for the post request
    $data = array(
      'SETTLEMENT_TYPE'             => '01',
      'MERCHANT_ID'                 => MERCHANT_ID,
      'ORDER_ID'                    => $this->order_id,
      'SESSION_ID'                  => $this->session_id,
      'GROSS_AMOUNT'                => '10',
      'PREVIOUS_CUSTOMER_FLAG'      => '',
      'CUSTOMER_STATUS'             => '1',
      // 'AMOUNT'                      => $this->amount,
      'MERCHANTHASH'                => $hash,
      // 'CARD_CAPTURE_FLAG'           => $this->card_capture_flag,
      'PREVIOUS_CUSTOMER_FLAG'      => '',

      'EMAIL'                       => 'test@veritrans.co.jp',
      // // 'NAME1'                       => $this->name1,
      // // 'NAME2'                       => $this->name2,
      'FIRST_NAME'                       => 'SMITH',
      'LAST_NAME'                       => 'JOHN',
      // // 'ZIP_CODE'                    => $this->zip_code,
      'POSTAL_CODE'                    => 'GU24OBLs',
      // // 'ADDRESS1'                    => $this->address1,
      // // 'ADDRESS2'                    => $this->address2,
      // // 'ADDRESS3'                    => $this->address3,
      'ADDRESS1'                    => '52 The Street',
      'ADDRESS2'                    => 'Village Town',
      'CITY'                    => 'London',
      'COUNTRY_CODE'                => 'GBR',
      'PHONE'                       => '0123456789123',
      // 'BIRTHDAY'                    => $this->birthday,
      // 'SEX'                         => $this->sex, // 1:male, 2:female, 3:
      // ship
      'SHIPPING_INPUT_FLAG'           => '1',
      'SHIPPING_SPECIFICATION_FLAG'   => '1',
      'SHIPPING_FIRST_NAME'          => 'TARO',
      'SHIPPING_LAST_NAME'            => 'YAMADA',
      'SHIPPING_ADDRESS1'             => 'Roppongi1-6-1',
      'SHIPPING_ADDRESS2'             => 'Minatoku',
      'SHIPPING_CITY'                 => 'Tokyo',
      'SHIPPING_COUNTRY_CODE'         => 'JPN',
      'SHIPPING_POSTAL_CODE'          => '1606028',
      'SHIPPING_PHONE'                => '03111122229',
      // 'SHIPPING_METHOD'               => 'N',
      // // ship
      'CARD_NO'                     => '4111111111111111',
      'CARD_EXP_DATE'               => '11/14', // mm/yy/form
      // 'CARD_HOLDER_NAME'            => '',
      // 'CARD_NUMBER_OF_INSTALLMENT'  => '',
      // 'SETTLEMENT_SUB_TYPE'         => $this->settlement_sub_type,                      
      // 'CARD_CAPTURE_FLAG'           => $this->card_capture_flag,
      // 'SHOP_NAME'                   => $this->shop_name,
      //     'SCREEN_TITLE'                => $this->screen_title,
      //     'CONTENTS'                    => $this->contents,
      //     'TIMELIMIT_OF_PAYMENT'        => $this->timelimit_of_payment,
      //     'TIMELIMIT_OF_CANCEL'         => $this->timelimit_of_cancel,
      'FINISH_PAYMENT_RETURN_URL'   => FINISH_PAYMENT_RETURN_URL,
      'UNFINISH_PAYMENT_RETURN_URL' => UNFINISH_PAYMENT_RETURN_URL,
      'ERROR_PAYMENT_RETURN_URL'    => ERROR_PAYMENT_RETURN_URL,
      'LANG_ENABLE_FLAG'            => '',
      'LANG'                        => '',
      'REPEAT_LINE'                 => '1',
      'COMMODITY_ID'               => 'IDxx1',
      'COMMODITY_UNIT'             => '10',
      'COMMODITY_NUM'              => '1',
      'COMMODITY_NAME1'            => 'Waterbotle',
      'COMMODITY_NAME2'            => 'Waterbottle in Indonesian');
      // 'JAN_CONDE'                   => $this->jan_conde );

    $client = new Pest(REQUEST_KEY_URL);
    $result = $client->post('', $data);

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
