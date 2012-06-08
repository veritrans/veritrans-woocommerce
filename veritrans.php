<?php

// Wraper for veritrans weblink type payment

require_once 'lib/config.php';
require_once 'lib/Pest.php';
require_once 'lib/hash_generator.php';

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
  private $finish_payment_return_url;
  private $unfinish_payment_return_url;
  private $error_payment_return_url;
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
    $hash = HashGenerator::generate(MERCHANT_ID, $this->settlement_type, $this->order_id, $this->amount);


    // populate parameters for the post request
    $data = array(
      'SETTLEMENT_TYPE'							=> $this->settlement_type,
      'MERCHANT_ID'									=> $this->merchant_id,
      'ORDER_ID'										=> $this->order_id,
      'SESSION_ID'									=> $this->session_id,
      'AMOUNT'											=> $this->amount,
      'MERCHANTHASH'								=> $hash,
      'CARD_CAPTURE_FLAG'						=> $this->card_capture_flag,

      'MAIL_ADDRESS'								=> $this->mailaddress,
      'NAME1' 											=> $this->name1,
      'NAME2'												=> $this->name2,
      'ZIP_CODE'										=> $this->zip_code,
      'ADDRESS1'										=> $this->address1,
      'ADDRESS2'										=> $this->address2,
      'ADDRESS3'										=> $this->address3,
      'TELEPHONE_NO'								=> $this->telephone_no,
      'BIRTHDAY'										=> $this->birthday,
      'SEX'													=> $this->sex, // 1:male, 2:female, 3:
      'CARD_NO'											=> $this->card_no,
      'CARD_EXP_DATE'								=> $this->card_exp_date, // mm/yy/form
      'CARD_HOLDER_NAME'						=> $this->card_holder_name,
      'CARD_NUMBER_OF_INSTALLMENT'	=> $this->card_number_of_installment,
      'SETTLEMENT_SUB_TYPE'					=> $this->settlement_sub_type,        							
      'CARD_CAPTURE_FLAG'           => $this->card_capture_flag,
      'SHOP_NAME'                   => $this->shop_name,
      'SCREEN_TITLE'                => $this->screen_title,
      'CONTENTS'                    => $this->contents,
      'TIMELIMIT_OF_PAYMENT'        => $this->timelimit_of_payment,
      'TIMELIMIT_OF_CANCEL'         => $this->timelimit_of_cancel,
      'FINISH_PAYMENT_RETURN_URL'   => $this->finish_payment_return_url,
      'UNFINISH_PAYMENT_RETURN_URL' => $this->unfinish_payment_return_url,
      'ERROR_PAYMENT_RETURN_URL'    => $this->error_payment_return_url,
      'LANG_ENABLE_FLAG'            => $this->lang_enable_flag,
      'LANG'                        => $this->lang,
      'COMMODITY_ID'                => $this->commodity_id,
      'COMMODITY_UNIT'              => $this->commodity_unit,
      'COMMODITY_NUM'               => $this->commodity_num,
      'COMMODITY_NAME'              => $this->commodity_name,
      'JAN_CONDE'                   => $this->jan_conde );

    $client = new Pest(REQUEST_KEY_URL);
    $result = $client->post('', $data);

    $key = $this->extract_keys_from($result);

    return $key;
  }

  // Private methods

  private function extract_keys_from($body)
  {
    $key = array();
    $body_lines = explode("\n", $body);
    foreach($body_lines as $line) {
      if(preg_match('/^MERCHANT_ENCRYPTION_KEY=(.+)/', $line, $match)) {
        $key['merchant_key'] = $match[1];
        } elseif(preg_match('/^BROWSER_ENCRYPTION_KEY=(.+)/', $line, $match)) {
          $key['browser_key'] = $match[1];
          } elseif(preg_match('/^ERROR_MESSAGE=(.+)/', $line, $match)) {
            $key['error_message'] = $match[1];
          }
        }

        return $key;

      }

  }

?>
