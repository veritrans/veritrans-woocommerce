 Veritrans Weblink Type PHP integration library 
==============================================

## How to use

###STEP 1 : Requesting key

Given you already have cart ready for checkout.
We create a veritrans instance

```
$veritrans = new Veritrans;
$veritrans->settlement_type = '01';
$veritrans->merchant_id = 'sample1';
$veritrans->merchant_hash = '12323111';
$veritrans->order_id = 'your_unique_order_id';
$veritrans->session_id = 'your_application_session_id';
$veritrans->gross_amount = '150000';
$veritrans->card_capture_flag = '1';
$veritrans->billing_address_different_with_shipping_address = 1;
$veritrans->required_shipping_address = 0;

```

**dont forget to set your commodity**

```
$commidities =  array (
						array("COMMODITY_ID" => 'sku1', "COMMODITY_PRICE" => '10000', 
							  "COMMODITY_QTY" => '2', 
						      "COMMODITY_NAME1" => 'Kaos', "COMMODITY_NAME2" => 'T-Shirt'),
						array("COMMODITY_ID" => 'sku2', "COMMODITY_PRICE" => '20000', 
							  "COMMODITY_QTY" => '1', 
						      "COMMODITY_NAME1" => 'Celana', "COMMODITY_NAME2" => 'Pants')
						);
						
$veritrans->commodity = $commodities;

```

**request for a key**

```
$key = $veritrans->get_keys();
```


**save order infomation and keys to your database**

```
$data = array(
  'order_id'    => $order_id,
  'session_id'  => $this->session->userdata('session_id'),
  'amount'      => $this->cart->total(),
  'token_browser' => $key['token_browser'],
  'token_merchant'=> $key['token_merchant']
);
```

**In this sample, we're assuming that we use CodeIgniter ActiveRecord**

```
$this->db->insert('orders', $data);
```

###STEP 2 :  Redirect user to Veritrans payment page

**Prepare the FORM to redirect the customer**

```
<form action="<?= Veritrans::PAYMENT_REDIRECT_URL ?>" method="post"  onSubmit="document.getElementById('submitBtn').disabled=true;">
  <input type="hidden" name="MERCHANT_ID" value="sample1" />
  <input type="hidden" name="ORDER_ID" value="<?= $order['order_id'] ?>" />
  <input type="hidden" name="BROWSER_ENCRYPTION_KEY" value="<?= $key['token_browser'] ?>" />
  <input id="submitBtn" type="submit" value="Confirm Checkout" />
</form>
```


###STEP 3 : Responding Veritrans Payment Notification
After the payment is completed
Veritrans will contact Merchant's web server
As Merchant, you need to response this query
@TODO Validate request from veritrans, make sure it comes from veritrans not from hacker
 
**Create Veritrans Notification instance**

```
$veritrans_notification = new VeritransNotification($_POST);
```

**Check if valid**

```
if($order->token_merchant != $veritrans_notification->TOKEN_MERCHANT){
  echo "ERR";
  $this->db->insert('payment_notifications', array("params" => 'no match'));
  exit();
}
```
