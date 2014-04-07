<?php 

// usage: $fname = new Sanitizer($string).length(20).whitelist("0-9").run();
namespace Veritrans;

class Sanitizer {

  private $string;
  private $order;

  public function __construct($string)
  {
    $this->string = $string;
    $this->order = array();
  }

  public function length($max_length)
  {
    array_push($this->order, function($max_length) { 
      if (strlen($this->string) > $max_length)
      {
        return substr($this->string, 0, $max_length);
      } else
      {
        return $this->string;
      }
    });
    return $this;
  }

  public function whitelist($whitelist_regex)
  {
    array_push($this->order, function($whitelist_regex) {
      return preg_replace('/whitelist_regex/', '', $this->string);
    });
  }

  public function country()
  {
    array_push($this->order, function() {
      return $this->string;
    });
  }

  public function null_fallback($string)
  {
    array_push($this->order, function() {
      if ($this->string)
        return $this->string;
    }); 
  }

  public function run() {
    foreach ($this->order as $func) {
      call_user_func($func);
    }
    return $this->string;
  }
}