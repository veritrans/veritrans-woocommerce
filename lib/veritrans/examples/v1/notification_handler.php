<?php
require '../../veritrans_notification.php';

$notification = new VeritransNotification();

if($notification->mStatus == "fatal")
{
	// Veritrans internal system error. Please contact Veritrans Support if this occurs.
	echo 'order failed!';
}
else
{
	
	var_dump($notification);

	// TODO: Retrieve order info from your database to check the token_merchant for security purpose.
	// The token_merchant that you get from this http(s) POST notification must be the same as token_merchant that you get previously when requesting token (before redirecting customer to Veritrans payment page.)

	//$order = Order::find('order_id' => $notification->orderId);

	if($order['TOKEN_MERCHANT'] == $notification->TOKEN_MERCHANT )
	{
		// TODO: update order payment status on your database. 3 possibilities $notification->mStatus responses from Veritrans: 'success', 'failure', and 'challenge'
		// $order['payment_status'] = $notification->mStatus; 
		// $order->save();
	}
	else
	{
		// If token_merchant that you get from this http(s) POST request is different with token_merchant that you get previously when requesting token (before redirecting customer to Veritrans payment page.), there is a possibility that the http(s) POST request is not coming from Veritrans. 
		// Don't update your payment status. 
		// To make sure, check your Merchant Administration Portal (MAP) at https://payments.veritrans.co.id/map/
	}
}