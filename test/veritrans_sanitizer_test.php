<?php 

class VeritransSanitizerTest extends PHPUnit_Framework_TestCase
{
  public function testLengthSanitizer()
  {
    $string = 'aiueo';
    $sanitized_string = \Veritrans\Sanitizer::create($string)->length(4)->run();
    $this->assertEquals(4, strlen($sanitized_string));
  }

  public function testWhitelistSanitizerExclusion()
  {
    $string = '~';
    $sanitized_string = \Veritrans\Sanitizer::create($string)->whitelist('a-zA-Z0-9-_\',.@()\/ \\\\')->run();
    $this->assertEquals(0, strlen($sanitized_string)); 
  }

  public function testWhitelistSanitizerInclusion()
  {
    $string = '1234567890abczABCZ-_\',.@()/ \\';
    $original_string_length = strlen($string);
    $sanitized_string = \Veritrans\Sanitizer::create($string)->whitelist('a-zA-Z0-9-_\',.@()\/ \\\\')->run();
    $this->assertEquals($original_string_length, strlen($sanitized_string)); 
  }

  public function testCountrySanitizer()
  {
    $string = 'ID';
    $sanitized_string = \Veritrans\Sanitizer::create($string)->to_iso_3166_1_alpha_3()->run();
    $this->assertEquals('IDN', $sanitized_string); 

    $string = 'US';
    $sanitized_string = \Veritrans\Sanitizer::create($string)->to_iso_3166_1_alpha_3()->run();
    $this->assertEquals('USA', $sanitized_string); 

    $string = 'some_random_string';
    $sanitized_string = \Veritrans\Sanitizer::create($string)->to_iso_3166_1_alpha_3()->run();
    $this->assertEquals('IDN', $sanitized_string); 
  }

  public function testNullFallbackSanitizer()
  {
    $string = '';
    $sanitized_string = \Veritrans\Sanitizer::create($string)->null_fallback('blah')->run();
    $this->assertEquals('blah', $sanitized_string);
  }

  public function testEnsureIntegerSanitizer()
  {
    $string = '2234324.23423';
    $sanitized_string = \Veritrans\Sanitizer::create($string)->ensure_integer()->run();
    $this->assertEquals(2234324, $sanitized_string);
  }
}