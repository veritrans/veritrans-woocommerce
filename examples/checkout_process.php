<?php
if(empty($_POST)) 
{
	echo "Empty data.";
	exit;
}

require '../veritrans.php';

//TODO: Change with your actual merchant id and merchant hash key
$MERCHANT_ID = 'T100000000000001001862';
$MERCHANT_HASH_KEY = '21565170a5a85543b98b0e34071656c104f31266ff436be07edb2d5a33983f97';

$veritrans = new Veritrans();
$veritrans->merchant_id       = $MERCHANT_ID;
$veritrans->merchant_hash_key = $MERCHANT_HASH_KEY;

//TODO: Change with your actual order_id.
$veritrans->order_id          = 'order'.rand();

$veritrans->billing_address_different_with_shipping_address = 1;
$veritrans->required_shipping_address = 1;

// Set commodities. This is just sample commodities.
// TODO: Change with your actual commodities.
$commodities = array(
	array(
		"COMMODITY_ID" => "A002",
		"COMMODITY_PRICE" => "850000",
		"COMMODITY_QTY"   => '1',
		"COMMODITY_NAME1" => "Adidas F30",
		"COMMODITY_NAME2" => "Adidas F30"
	),
	array(
		"COMMODITY_ID" => "N003",
		"COMMODITY_PRICE" => "900000",
		"COMMODITY_QTY"   => '2',
		"COMMODITY_NAME1" => "Nike Lunarmoon",
		"COMMODITY_NAME2" => "Nike Lunarmoon"
	)
);
$veritrans->commodity = $commodities;
$veritrans->gross_amount = 2650000;

//set billing info
$veritrans->first_name 	= $_POST['billing_first_name'];
$veritrans->last_name 	= $_POST['billing_last_name'];
$veritrans->email 		= $_POST['billing_email'];
$veritrans->address1 	= $_POST['billing_address1'];
$veritrans->address2 	= $_POST['billing_address2'];
$veritrans->city 		= $_POST['billing_city'];
$veritrans->country_code= "IDN";
$veritrans->postal_code = $_POST['billing_postal_code'];
$veritrans->phone 		= $_POST['billing_phone'];

//set shipping info
$veritrans->shipping_first_name 	= $_POST['shipping_first_name'];
$veritrans->shipping_last_name 		= $_POST['shipping_last_name'];
$veritrans->shipping_address1 		= $_POST['shipping_address1'];
$veritrans->shipping_address2 		= $_POST['shipping_address2'];
$veritrans->shipping_city 			= $_POST['shipping_city'];
$veritrans->shipping_country_code 	= "IDN";
$veritrans->shipping_postal_code 	= $_POST['shipping_postal_code'];
$veritrans->shipping_phone 			= $_POST['shipping_phone'];


//Call Veritrans VT-Web API Get Token
$keys = $veritrans->get_keys();
	
if(!empty($keys['error_message']))
{
	echo "Error while getting token from Veritrans.";
	var_dump($keys);
	exit;
}
else
{
	//Save this token_merchant on your database to be used for checking veritrans notification response.
	$token_merchant = $keys['token_merchant'];
	
	//Use this token_browser for redirecting customer to Veritrans payment page.
	$token_browser = $keys['token_browser'];
}

//Redirect customer to Veritrans payment page.

?>

<!DOCTYPE html>
<html>
<head>
	<script language="javascript" type="text/javascript">
	<!--
	function onloadEvent() {
	  document.form_auto_post.submit();
	}
	//-->
	</script>
</head>

<body onload="onloadEvent();">
<form action="<?php echo Veritrans::PAYMENT_REDIRECT_URL ?>" method="post" name='form_auto_post'>
<input type="hidden" name="MERCHANT_ID" value="<?php echo $veritrans->merchant_id ?>" />
<input type="hidden" name="ORDER_ID" value="<?php echo $veritrans->order_id ?>" />
<input type="hidden" name="TOKEN_BROWSER" value="<?php echo $token_browser ?>" />
<span>Please wait. You are being redirected to Veritrans payment page...</span>
</form>

</body>