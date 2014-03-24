<?php 

namespace Veritrans;

require 'veritrans_2013.php';
require 'veritrans_2014.php';

class Factory {

  private $veritrans;

  public function __construct($veritrans)
  {
    $this->veritrans = $veritrans;
  }

  public function get($stack_version)
  {
    if ($stack_version == \Veritrans::STACK_2014)
    {
      return new  Veritrans2014($this->veritrans);
    } else
    {
      return new Veritrans2013($this->veritrans);
    }
  }
}