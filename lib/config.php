<?php
	// Change with your merchant hash key
	define('MERCHANT_ID', '10');
	define('MERCHANT_HASH_KEY', 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz0123456789012345678901');
	
	// Configuration 
	define('REQUEST_KEY_URL', 'http://localhost/~vcool/weblink/comodityRegist.txt');
	define('PAYMENT_REDIRECT_URL', 'http://122.208.206.170:80/web1/deviceCheck.action');
	
	// Settlement method:
	define('SETTLEMENT_TYPE_CARD', '01');
	
	// Flag: Sales and Sales Credit, 0: only 1 credit. If not specified, 0
	define('CARD_CAPTURE_FLAG', '1');
	
?>