<?php 

namespace Veritrans;

require 'veritrans_2013.php';
require 'veritrans_2014.php';

class Factory {

  private $veritrans;
  private $engines = array();

  public function __construct($veritrans)
  {
    $this->veritrans = $veritrans;
    $this->engines[2] = new Veritrans2014($this->veritrans);
    $this->engines[1] = new Veritrans2013($this->veritrans);
  }

  public function get()
  {
    if ($this->veritrans->version == 2)
    {
      return $this->engines[2];
    } else
    {
      return $this->engines[1];
    }
  }
}