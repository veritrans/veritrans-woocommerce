<?php
require '../../../veritrans_notification.php';

$json_result = file_get_contents('php://input');

$notification = new VeritransNotification();

if($notification->transaction_status == "capture")
{
  error_log('payment success!');
}
else if ($notification->transaction_status == 'deny')
{
 error_log('payment denied!'); 
} else if ($notification->transaction_status == 'challenge')
{
  error_log('payment challenged!');
} else
{
  error_log('system error!');
}