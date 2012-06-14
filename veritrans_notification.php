<?php

// Wraper for veritrans weblink type payment response

class VeritransNotification
{
  // Required Params
  private $mailaddress;


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

  public function is_valid()
  {
    return false;
  }

}

?>
