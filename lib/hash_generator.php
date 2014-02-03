<?php

/**
* HashGenerator class
*
* Generate using SHA-512
*/

class HashGenerator {

  /**
  * Generate hash value from string
  * @param merchant_id
  * @param merchant_hash_key
  * @param order_id
  * @return hash value
  */
  public static function generate($merchant_id, $merchant_hash_key, $order_id) {

    $ctx  = hash_init('sha512');

    $str  = $merchant_hash_key .
      "," . $merchant_id .
      "," . $order_id;
    hash_update($ctx, $str);
    $hash = hash_final($ctx, true);
    return bin2hex($hash);
  }

}

?>
