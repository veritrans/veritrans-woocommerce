<?php

/**
* HashGenerator class
*
* Generate using SHA-512
*/

class HashGenerator {

  /**
  * Generate hash value from string
  * @param merchantID
  * @param orderID
  * @param merchant_hash_key
  * @return hash value
  */
  public static function generate($merchant_hash_key,$merchantID, $orderID) {

    $ctx  = hash_init('sha512');

    $str  = $merchant_hash_key .
      "," . $merchantID .
      "," . $orderID;
	  
    hash_update($ctx, $str);
    $hash = hash_final($ctx, true);
    return bin2hex($hash);
  }

}

?>
