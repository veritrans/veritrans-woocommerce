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
    $this->engines['2014'] = new Veritrans2014($this->veritrans);
    $this->engines['2013'] = new Veritrans2013($this->veritrans);
  }

  public function get()
  {
    if ($this->veritrans->version == \Veritrans::STACK_2014)
    {
      return $this->engines['2014'];
    } else
    {
      return $this->engines['2013'];
    }
  }
}