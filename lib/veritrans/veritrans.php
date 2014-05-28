<?php

// PHP Wrapper for Veritrans VT-Web Payment API.

require_once 'lib/veritrans_factory.php';

class Veritrans
{

  const REQUEST_KEY_URL       = 'https://vtweb.veritrans.co.id/v1/tokens.json';
  const PAYMENT_REDIRECT_URL  = 'https://vtweb.veritrans.co.id/v1/payments.json';

  const VERSION_STABLE = 1;

  const ENVIRONMENT_DEVELOPMENT = 0;
  const ENVIRONMENT_PRODUCTION = 1;

  const VT_WEB = 0;
  const VT_DIRECT = 1;

  private $data = array();

  private $items;

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

  public function __construct($params = null) 
  {
    // maintain compatibility with vt-web2 branch by setting default variables.
    // $this->version = self::VERSION_STABLE;
    $this->version = 2;
    $this->environment = self::ENVIRONMENT_DEVELOPMENT;
    $this->veritrans_method = self::VT_WEB;
    $this->veritrans_engine = new Veritrans\Factory($this);
    $this->billing_different_with_shipping = 0;
    $this->enable_3d_secure = FALSE;
  }

  public function getTokens($options = array())
  {
    return $this->veritrans_engine->get()->getTokens($options);
  }

  public function charge($options = array())
  {
    return $this->veritrans_engine->get()->charge($options);
  }

  public function confirm($transaction_id)
  {
    return $this->veritrans_engine->get()->confirm($transaction_id);
  }

  public function getData()
  {
    return $this->data;
  }

}

?>
