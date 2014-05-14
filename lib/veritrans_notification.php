<?php

// PHP Wraper for Veritrans VT-Web Payment HTTP(S) POST notification response

class VeritransNotification
{

  private $data = array();

  // private $mStatus; 
  // private $orderId;
  // private $TOKEN_MERCHANT;
  

  public function __get($property) 
  {
    if (array_key_exists($property, $this->data)) {
      return $this->data[$property];
    } else
    {
      return NULL;
    }
  }

  public function __set($property, $value) 
  {
    $this->data[$property] = $value;
    return $value;
  }

  function __construct($params = null) 
  {
    if(is_null($params)) {
      $params = json_decode(file_get_contents('php://input'));
    }

    foreach($params as $key => $value){
      $this->$key = $value;
    }
  }

}

?>
