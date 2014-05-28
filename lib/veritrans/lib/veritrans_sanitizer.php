<?php 

// usage: $fname = new Sanitizer($string)->length(20)->whitelist("0-9")->run();
namespace Veritrans;

require 'isocountry.php';

class Sanitizer {

  public $string;
  private $order;

  public function __construct($string)
  {
    $this->string = $string;
    $this->order = array();
  }

  public static function create($string)
  {
    return new self($string);
  }

  public function length($max_length)
  {
    array_push($this->order, function($string) use ($max_length) { 
      if (strlen($string) > $max_length)
      {
        return substr($string, 0, $max_length);
      } else
      {
        return $string;
      }
    });
    return $this;
  }

  public function whitelist($whitelist_regex)
  {
    array_push($this->order, function($string) use ($whitelist_regex) {
      return preg_replace('/[^' . $whitelist_regex . ']/', '', $string);
    });
    return $this;
  }

  public function to_iso_3166_1_alpha_3()
  {
    array_push($this->order, function($string) {
      $isoCountry = new \ISOCountry();
      if (array_key_exists(strtoupper($string), $isoCountry->isoA3))
      {
        return $isoCountry->isoA3[strtoupper($string)];
      } else
      {
        return 'IDN';
      }
    });
    return $this;
  }

  public function null_fallback($fallback_string)
  {
    array_push($this->order, function($string) use ($fallback_string) {
      if (strlen($string) > 0)
        return $string;
      else
        return $fallback_string;
    });
    return $this;
  }

  public function ensure_integer()
  {
    array_push($this->order, function($string) {
      return (int)round($string);
    });
    return $this;
  }

  public function run() {
    $result = $this->string;
    foreach ($this->order as $func) {
      $result = call_user_func($func, $result);
    }
    return $result;
  }
}