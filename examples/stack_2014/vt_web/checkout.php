<html>
<head></head>
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
  <form action="checkout_process.php" method="post">    
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
    <input name="shipping_first_name" size="30" type="text" value="Andri"><br /><br />
    <label>Last name</label><br />
    <input name="shipping_last_name" size="30" type="text" value="Setiawan"><br /><br />
    <label>Address 1</label><br />
    <input name="shipping_address1" size="30" type="text" value="Bakerstreet 221B"><br /><br />
    <label>Address 2</label><br />
    <input name="shipping_address2" size="30" type="text" value="Setiabudi"><br /><br />
    <label>City</label><br />
    <input name="shipping_city" size="30" type="text" value="Jakarta"><br /><br />
    <label>Postal code</label><br />
    <input name="shipping_postal_code" size="30" type="text" value="12345"><br /><br />
    <label>Phone</label><br />
    <input name="shipping_phone" size="30" type="text" value="08112312312312"><br /><br />
    
    <input id="submit_btn" type="submit" value="Pay with Veritrans VT-Web" />
  </form>
</body>
</html>