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
  public function generate($merchantID, $settlementmethod, $orderID, $amount) {

    $ctx  = hash_init('sha512');

    $str  = MERCHANT_HASH_KEY .
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
