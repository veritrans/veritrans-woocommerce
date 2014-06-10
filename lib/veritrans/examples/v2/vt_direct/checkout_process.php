<?php

require '../../../veritrans.php';

if(empty($_POST['token_id'])) 
{
	echo "Empty token_id.";
	exit;
}

$veritrans = new Veritrans();

// TODO: change with your actual server_key that can be found on Merchant Administration Portal (MAP)
$veritrans->server_key = "eebadfec-fa3a-496a-8ea0-bb5795179ce6";

// TODO : change to production URL for your production Environment
// $endpoint = "https://api.veritrans.co.id/v2/charge";

// token_id merepresentasikan credit card yang akan di-carge.
// token_id didaptkan dari request yang dilakukan melalui veritrans.js
$veritrans->payment_type = Veritrans::VT_DIRECT;
$veritrans->token_id = $_POST['token_id'];
$veritrans->bank = 'bni';
if (isset($_POST['save_cc']))
{
	$veritrans->save_token_id = true;
}

// $transaction_details = array(
// 	'order_id' 		=> time(),
// 	'gross_amount' 	=> 200000
// );
$veritrans->order_id = time();
$veritrans->gross_amount = 20000;

// Populate items
$items = [
	array(
		'item_id' 		=> 'item1',
		'price' 	=> 100000,
		'quantity' 	=> 1,
		'item_name1' 		=> 'Adidas f50'
	),
	array(
		'item_id'		=> 'item2',
		'price' 	=> 50000,
		'quantity' 	=> 2,
		'item_name1' 		=> 'Nike N90'
	)
];
$veritrans->items = $items;

// Populate customer's billing address
// $billing_address = array(
// 	'first_name' 	=> "Andri",
// 	'last_name' 	=> "Setiawan",
// 	'address' 		=> "Karet Belakang 15A, Setiabudi.",
// 	'city' 			=> "Jakarta",
// 	'postal_code' 	=> "51161",
// 	'phone' 		=> "081322311801",
// 	'country_code' 	=> 'IDN'
// 	);
$veritrans->first_name = 'Andri';
$veritrans->last_name = 'Setiawan';
$veritrans->address = 'Karet Belakang 15A, Setiabudi';
$veritrans->city = 'Jakarta';
$veritrans->postal_code = '51161';
$veritrans->phone = '081322311801';
$veritrans->country_code = 'IDN';
$veritrans->email = "vt-testing@veritrans.co.id";

// Populate customer's shipping address
// $shipping_address = array(
// 	'first_name' 	=> "John",
// 	'last_name' 	=> "Watson",
// 	'address' 		=> "Bakerstreet 221B.",
// 	'city' 			=> "Jakarta",
// 	'postal_code' 	=> "51162",
// 	'phone' 		=> "081322311801",
// 	'country_code' 	=> 'IDN'
// 	);

$veritrans->required_shipping_address = 1;
$veritrans->billing_different_with_shipping = 1;

$veritrans->shipping_first_name = 'John';
$veritrans->shipping_last_name = 'Watson';
$veritrans->shipping_address = 'Bakerstreet 221B';
$veritrans->shipping_city = 'Jakarta';
$veritrans->shipping_postal_code = '51162';
$veritrans->shipping_phone = '081322311801';
$veritrans->shipping_country_code = 'IDN';

// Populate customer's Info
// $customer_details = array(
// 	'first_name' 	=> "Andri",
// 	'last_name' 	=> "Setiawan",
// 	'email' 		=> "vt-testing@veritrans.co.id",
// 	'phone' 		=> "081322311801",
// 	'billing_address'  => $billing_address,
// 	'shipping_address' => $shipping_address
// 	);

// Data yang akan dikirim untuk request charge transaction dengan credit card.
// $transaction_data = array(
// 	'payment_type' 			=> 'credit_card', 
// 	'credit_card' 			=> array(
// 		'token_id' 	=> $token_id,
// 		'bank' 			=> 'cimb',
// 		'save_token_id'			=> isset($_POST['save_cc'])
// 		),
// 	'transaction_details' 	=> $transaction_details,
// 	'item_details' 					=> $items,
// 	'customer_details' 			=> $customer_details,
// );

// $json_transaction_data = json_encode($transaction_data);

// // Mengirimkan request dengan menggunakan CURL
// // HTTP METHOD : POST
// // Header:
// //	Content-Type : application/json
// //	Accept: application/json
// // 	Basic Auth using server_key
// $request = curl_init($endpoint);
// curl_setopt($request, CURLOPT_CUSTOMREQUEST, "POST");
// curl_setopt($request, CURLOPT_POSTFIELDS, $json_transaction_data);
// curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
// $auth = sprintf('Authorization: Basic %s', base64_encode($server_key.':'));
// curl_setopt($request, CURLOPT_HTTPHEADER, array(
//     'Content-Type: application/json',
// 	'Accept: application/json',
// 	$auth 
// 	)
// );

// // Excute request and parse the response
// $response = json_decode(curl_exec($request));
$response = $veritrans->charge();

if (in_array($response['status_code'], array(200, 201, 202))) {
	if($response['transaction_status'] == "capture")
	{
		//success
		echo "Transaksi berhasil. <br />";
		echo "Status transaksi untuk order id ".$response['order_id'].": ".$response['transaction_status'];

		echo "<h3>Detail transaksi:</h3>";
		var_dump($response);
	}
	else if($response['transaction_status'] == "deny")
	{
		//deny
		echo "Transaksi ditolak. <br />";
		echo "Status transaksi untuk order id ".$response['order_id'].": ".$response['transaction_status'];

		echo "<h3>Detail transaksi:</h3>";
		var_dump($response);
	}
	else if($response['transaction_status'] == "challenge")
	{
		//challenge
		echo "Transaksi challenge. <br />";
		echo "Status transaksi untuk order id ".$response['order_id'].": ".$response['transaction_status'];

		echo "<h3>Detail transaksi:</h3>";
		var_dump($response);
	}
} 
else
{
	//error
	echo "Terjadi kesalahan pada data transaksi yang dikirim.<br />";
	echo "Status message: [".$response['status_code']."] ".$response['status_message'];

	echo "<h3>Response:</h3>";
	var_dump($response);
}

echo "<hr />";
echo "<h3>Request</h3>";
var_dump($veritrans);

?>
