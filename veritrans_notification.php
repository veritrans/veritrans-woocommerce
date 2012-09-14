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
  private $TOKEN_MERCHANT;
  

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


}

?>
