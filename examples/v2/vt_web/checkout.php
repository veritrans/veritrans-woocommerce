<html>
<head>
  <link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css">
</head>
<body>
  <h2>Checkout</h2>
  <table>
    <thead>
      <td>Product</td>
      <td>Qty</td>
      <td>Price</td>
      <td>Total</td>
    </thead>
    <tr>
      <td>Sepatu Adidas F30</td>
      <td>1</td>
      <td>Rp 850.000</td>
      <td>Rp 850.000</td>
    </tr>
    <tr>
      <td>Sepatu Nike Lunarmoon</td>
      <td>2</td>
      <td>Rp 900.000</td>
      <td>Rp 1.800.000</td>
    </tr>
    <tr>
      <td colspan="4"></td>
    </tr>
    <tr>
      <td colspan="3">Total</td>
      <td>Rp 2.650.000</td>
    </tr>
  </table>
  <form action="checkout_process.php" method="post" id="payment-form">    
    <label>Email</label><br />
    <input name="email" size="30" type="text" value="customer@email.com"><br /><br />
    
    <h3>Billing Info</h3>
    <label>First name</label><br />
    <input name="billing_first_name" size="30" type="text" value="Andri"><br /><br />
    <label>Last name</label><br />
    <input name="billing_last_name" size="30" type="text" value="Setiawan"><br /><br />
    <label>Address 1</label><br />
    <input name="billing_address1" size="30" type="text" value="Bakerstreet 221B"><br /><br />
    <label>Address 2</label><br />
    <input name="billing_address2" size="30" type="text" value="Setiabudi"><br /><br />
    <label>City</label><br />
    <input name="billing_city" size="30" type="text" value="Jakarta"><br /><br />
    <label>Postal code</label><br />
    <input name="billing_postal_code" size="30" type="text" value="12345"><br /><br />
    <label>Phone</label><br />
    <input name="billing_phone" size="30" type="text" value="08112312312312"><br /><br />
  
    <h3>Shipping Info</h3>
    <label>First name</label><br />
    <input name="shipping_first_name" size="30" type="text" value="Ismail"><br /><br />
    <label>Last name</label><br />
    <input name="shipping_last_name" size="30" type="text" value="Faruqi"><br /><br />
    <label>Address 1</label><br />
    <input name="shipping_address1" size="30" type="text" value="Upper Street 21"><br /><br />
    <label>Address 2</label><br />
    <input name="shipping_address2" size="30" type="text" value="Pejaten"><br /><br />
    <label>City</label><br />
    <input name="shipping_city" size="30" type="text" value="Jakarta"><br /><br />
    <label>Postal code</label><br />
    <input name="shipping_postal_code" size="30" type="text" value="54321"><br /><br />
    <label>Phone</label><br />
    <input name="shipping_phone" size="30" type="text" value="08112312312312"><br /><br />
    
    <fieldset>
      <legend>Pay with VT-Web</legend>
      <button id="submit_btn" type="submit" name="payment_type" value="vtweb">Pay with VT-Web</button>
    </fieldset>

    <fieldset>
      <legend>Pay with VT-Direct</legend>
      <div>
        <label>Credit Card Number</label>
        <input type="text" value="4111111111111111" size="20" autocomplete="off" placeholder="" class="card-number">
      </div>  
      <div>
        <label>Expiration Date</label>
        <select class="card-expiry-month">
          <?php for ($month=1; $month < 13; $month++): ?>
            <option value="<?php echo $month ?>"><?php echo $month ?></option>
          <?php endfor ?>
        </select>
        <select class="card-expiry-year">
          <?php for ($year=2013; $year < 2025; $year++): ?>
            <option value="<?php echo $year ?>"><?php echo $year ?></option>
          <?php endfor ?>
        </select>        
      </div>
      <div>
        <label>CVV</label>
        <input type="password" size="3" class="card-cvv" value="" placeholder="">
      </div>
      <input type="hidden" name="token_id" id="token_id" value="">
      <button type="submit" name="payment_type" value="vtdirect" class="submit-button">Pay with VT-Direct</button>
    </fieldset>    

  </form>
  
  <!-- Javascript for VT-Direct token generation -->
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
  <script type="text/javascript" src="https://api.sandbox.veritrans.co.id/v2/assets/js/veritrans.js"></script>
  <script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.pack.js"></script>
  <script type="text/javascript">
    $(function(){
      // Sandbox URL
      Veritrans.url = "https://api.sandbox.veritrans.co.id/v2/token";
      // TODO: Change with your client key.
      Veritrans.client_key = "1587765e-defa-4064-8b95-807dba85d551";
      var card = function() {
        return {   
          'card_number' : $(".card-number").val(),
          'card_exp_month'  : $(".card-expiry-month").val(),
          'card_exp_year'    : $(".card-expiry-year").val(),
          'card_cvv'      : $(".card-cvv").val(),
          'secure'      : true,
          'bank'        : 'bni',
          'gross_amount'    : 1250000
        }
      };

      function callback(response) {
        if (response.redirect_url) {
          // 3dsecure transaction, please open this popup
          openDialog(response.redirect_url);

        } else if (response.status_code == '200') {
          // success 3d secure or success normal
          closeDialog();
          // submit form
          $("#token_id").val(response.token_id);
          $("#payment-form").submit();
        } else {
          // failed request token
          console.log('Close Dialog - failed');
          //closeDialog();
          //$('#purchase').removeAttr('disabled');
          // $('#message').show(FADE_DELAY);
          // $('#message').text(response.status_message);
          alert(response.status_message);
        }
      }

      function openDialog(url) {
        $.fancybox.open({
          href: url,
          type: 'iframe',
          autoSize: false,
          width: 700,
          height: 500,
          closeBtn: false,
          modal: true
        });
      }

      function closeDialog() {
        $.fancybox.close();
      }

      $('.submit-button').click(function(event){
        debugger;
        event.preventDefault();
        $(this).attr("disabled", "disabled"); 
        Veritrans.token(card, callback);
        return false;
      });
    });
  </script>
</body>
</html>