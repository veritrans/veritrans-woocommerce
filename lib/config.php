<?php
  // Change with your merchant hash key
  define('MERCHANT_ID', 'sample1');
  define('MERCHANT_HASH_KEY', 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz0123456789012345678901');
  define('FINISH_PAYMENT_RETURN_URL', 'http://192.168.10.228');
  define('UNFINISH_PAYMENT_RETURN_URL', 'http://192.168.10.228');
  define('ERROR_PAYMENT_RETURN_URL', 'http://192.168.10.228');
  
  // Configuration http://192.168.10.250:80/web1/deviceCheck.action
  // define('REQUEST_KEY_URL', 'http://192.168.10.250:80/web1/confirm.action');
  define('REQUEST_KEY_URL', 'http://192.168.10.250:80/web1/commodityRegist.action');
  define('PAYMENT_REDIRECT_URL', 'http://192.168.10.250:80/web1/deviceCheck.action');
  
  // Settlement method:
  define('SETTLEMENT_TYPE_CARD', '01');
  
  // Flag: Sales and Sales Credit, 0: only 1 credit. If not specified, 0
  define('CARD_CAPTURE_FLAG', '1');
  
?>