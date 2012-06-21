<?php

// Wraper for veritrans weblink type payment response

class VeritransNotification
{
  
  private $postalcode;
  private $mStatus; 
  private $phone; 
  private $shippingPhone; 
  private $mErrMsg; 
  private $email; 
  private $address; 
  private $name;
  private $vResultCode; 
  private $shippingAddress;
  private $orderId;
  private $shippingPostalcode;
  private $shippingName;
  
  const VERITRANS_IP_ADDRESS = '192.168.10.250';


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
    foreach($params as $key => $value){
      $this->$key = $value;
    }
  }

  public function is_valid()
  {
    if($this->mStatus == 'success' && $_SERVER['REMOTE_ADDR'] == self::VERITRANS_IP_ADDRESS){
      return true;
    }else{
      return false;
    }
  }

}

?>
