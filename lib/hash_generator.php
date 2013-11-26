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
  * @param settlementmethod - how to settlement （Null or Blank → '00'）
  * @param orderID
  * @param amount - Total Amount
  * @return hash value
  */
  public static function generate($merchantID, $merchant_hash, $settlementmethod, $orderID, $amount) {

    $ctx  = hash_init('sha512');

    $str  = $merchant_hash .
      "," . $merchantID .
      "," . ((is_null($settlementmethod) || strlen($settlementmethod) == 0) ? '00' : $settlementmethod) .
      "," . $orderID .
      "," . $amount;
    hash_update($ctx, $str);
    $hash = hash_final($ctx, true);
    return bin2hex($hash);
  }

}

?>
