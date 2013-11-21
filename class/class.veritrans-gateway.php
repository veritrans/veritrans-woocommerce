<?php
/**
	 * Veritrans Payment Gateway Class
	 */
class WC_Gateway_Veritrans extends WC_Payment_Gateway {

	const VT_REQUEST_KEY_URL = 'https://payments.veritrans.co.id/web1/commodityRegist.action';
  const VT_PAYMENT_REDIRECT_URL = 'https://payments.veritrans.co.id/web1/paymentStart.action';

  /**
   * Constructor
   */
  function __construct() {
    $this->id           = 'veritrans';
    $this->icon         = apply_filters( 'woocommerce_veritrans_icon', '' );
    $this->method_title = __( 'Veritrans', 'colabsthemes' );
    $this->has_fields   = true;
		$this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Veritrans', home_url( '/' ) ) );

    // Load the settings
    $this->init_form_fields();
    $this->init_settings();

    // Get Settings
    $this->title          		= $this->get_option( 'title' );
    $this->description    		= $this->get_option( 'description' );
		$this->select_veritrans_payment = $this->get_option( 'select_veritrans_payment' );
    $this->client_key     		= $this->get_option( 'client_key' );
    $this->server_key     		= $this->get_option( 'server_key' );
		$this->merchant_id     		= $this->get_option( 'merchant_id' );
    $this->merchant_hash_key 	= $this->get_option( 'merchant_hash_key' );
		
		// Payment listener/API hook
		add_action( 'woocommerce_api_wc_gateway_veritrans', array( &$this, 'veritrans_vtweb_response' ) );
		
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); 
    add_action( 'wp_enqueue_scripts', array( &$this, 'veritrans_scripts' ) );
		add_action( 'admin_print_scripts-woocommerce_page_woocommerce_settings', array( &$this, 'veritrans_admin_scripts' ));
		add_action( 'valid-veritrans-web-request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_receipt_veritrans', array( $this, 'receipt_page' ) );
  }

  /**
   * Enqueue Javascripts
   */
	function veritrans_admin_scripts() {
		wp_enqueue_script( 'admin-veritrans', VT_PLUGIN_DIR . 'js/admin-scripts.js', array('jquery') );
	}
  function veritrans_scripts() {
    if( is_checkout() ) {
      wp_enqueue_script( 'veritrans', 'https://payments.veritrans.co.id/vtdirect/veritrans.min.js', array('jquery') );
      wp_enqueue_script( 'veritrans-integration', VT_PLUGIN_DIR . 'js/script.js', array('veritrans') );
      wp_localize_script( 'veritrans-integration', 'wc_veritrans_client_key', $this->client_key );
    }
  }

  /**
   * Admin Panel Options
   * - Options for bits like 'title' and availability on a country-by-country basis
   *
   * @access public
   * @return void
   */
  public function admin_options() { ?>
    <h3><?php _e( 'Veritrans', 'woocommerce' ); ?></h3>
    <p><?php _e('Allows payments using Veritrans.', 'woocommerce' ); ?></p>
    <table class="form-table">
      <?php
        // Generate the HTML For the settings form.
        $this->generate_settings_html();
      ?>
    </table><!--/.form-table-->
    <?php
  }

  /**
   * Payment Fields
   *
   * Show form containing Credit Cards details
   */
  function payment_fields() { 
		if($this->description)echo '<p>'.$this->description.'</p>';
		if('veritrans_direct'==$this->select_veritrans_payment){
		?>	
    <p class="form-row validate-required" id="veritrans_credit_card_field">
      <label for="veritrans_credit_card_field">
        <?php _e('Credit Card Number'); ?>
        <abbr class="required" title="required">*</abbr>
      </label>
      <input type="text" class="input-text" name="veritrans_credit_card" maxlength="16">
    </p>

    <p class="form-row" id="veritrans_card_exp_month_field">
      <label for="veritrans_card_exp_month_field">
        <?php _e('Expiration Date - Month', 'woocommerce'); ?>
        <abbr class="required" title="required">*</abbr>
      </label>
      <select name="veritrans_card_exp_month">
        <?php $month_list = array(
          '01' => '01 - January',
          '02' => '02 - February',
          '03' => '03 - March',
          '04' => '04 - April',
          '05' => '05 - May',
          '06' => '06 - June',
          '07' => '07 - July',
          '08' => '08 - August',
          '09' => '09 - September',
          '10' => '10 - October',
          '11' => '11 - November',
          '12' => '12 - December'
        ); ?>
        <option value="">--</option>
        <?php foreach( $month_list as $month => $name ) : ?>
          <option value="<?php echo $month; ?>"><?php echo $name; ?></option>
        <?php endforeach; ?>
      </select>
    </p>

    <p class="form-row" id="veritrans_card_exp_year_field">
      <label for="veritrans_card_exp_year_field">
        <?php _e('Expiration Date - Year', 'woocommerce'); ?>
        <abbr class="required" title="required">*</abbr>
      </label>
      <select name="veritrans_card_exp_year">
        <option value="">--</option>
        <?php $years = range( date("Y"), date("Y") + 14 );
        foreach( $years as $year ) : ?>
          <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
        <?php endforeach; ?>
      </select>
    </p>

    <p class="form-row validate-required" id="veritrans_security_field" maxlength="3">
      <label for="veritrans_security_field">
        <?php _e('Security Code', 'woocommerce'); ?>
        <abbr class="required" title="required"><a target="_blank" href="https://www.veritrans.co.id/payment-help.html">[?]</a></abbr>
      </label>
      <input type="text" class="input-text" name="veritrans_security">
    </p>

    <input type="text" name="veritrans_token_id" class="hide" style="display:none">
  <?php }}

  /**
   * Validate Payment Fields
   */
  function validate_fields() {
    global $woocommerce;
		if('veritrans_direct'==$this->select_veritrans_payment){
			if( empty($_POST['veritrans_credit_card']) || $_POST['veritrans_credit_card'] == '' ) {
				$woocommerce->add_error( __('Please input your Credit Card Number', 'woocommerce') );
			}

			if( empty($_POST['veritrans_card_exp_month']) || $_POST['veritrans_card_exp_month'] == '' ||
					empty($_POST['veritrans_card_exp_year']) || $_POST['veritrans_card_exp_year'] == '' ) {
				$woocommerce->add_error( __('Please choose your Credit Card Expiration Date', 'woocommerce') );
			}

			if( empty($_POST['veritrans_security']) || $_POST['veritrans_security'] == '' ) {
				$woocommerce->add_error( __('Please input your Security Code', 'woocommerce') );
			}
		}
    return true;
  }

  /**
   * Initialise Gateway Settings Form Fields
   */
  function init_form_fields() {
		$key_url = 'https://payments.veritrans.co.id/map/settings/config_info';
    $this->form_fields = array(
      'enabled' => array(
        'title' => __( 'Enable/Disable', 'woocommerce' ),
        'type' => 'checkbox',
        'label' => __( 'Enable Veritrans Payment', 'woocommerce' ),
        'default' => 'yes'
      ),
      'title' => array(
        'title' => __( 'Title', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
        'default' => __( 'Veritrans Payment', 'woocommerce' ),
        'desc_tip'      => true,
      ),
      'description' => array(
        'title' => __( 'Customer Message', 'woocommerce' ),
        'type' => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
        'default' => ''
      ),
			'select_veritrans_payment' => array(
        'title' => __( 'Payment Method', 'woocommerce' ),
        'type' => 'select',
        'default' => 'veritrans_web',
				'description' => __( 'Select the Veritrans payment system to process payments', 'woocommerce' ),
				'options'		=> array(
								'veritrans_direct' 		=> __( 'Direct', 'woocommerce' ),
								'veritrans_web' 	=> __( 'Web', 'woocommerce' ),
							),
      ),
			'merchant_id' => array(
        'title' => __( 'Merchant ID', 'woocommerce' ),
        'type' => 'text',
				'class'			=> 'veritrans_web',
        'description' => sprintf(__( 'Enter your Veritrans Merchant ID. Get the ID <a href="%s" target="_blank">here</a>', 'woocommerce' ),$key_url),
      ),
			'merchant_hash_key' => array(
        'title' => __( 'Merchant Hash Key', 'woocommerce' ),
        'type' => 'text',
				'class'			=> 'veritrans_web',
        'description' => sprintf(__( 'Enter your Veritrans Merchant hash key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$key_url),
      ),
      'client_key' => array(
        'title' => __("Client Key", 'woocommerce'),
        'type' => 'text',
				'class'			=> 'veritrans_direct',
        'description' => sprintf(__('Input your Veritrans Client Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$key_url),
        'default' => ''
      ),
      'server_key' => array(
        'title' => __("Server Key", 'woocommerce'),
        'type' => 'text',
				'class'			=> 'veritrans_direct',
        'description' => sprintf(__('Input your Veritrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$key_url),
        'default' => ''
      ),
    );
  }

  /**
   * Process the payment and return the result
   */
  function process_payment( $order_id ) {
    global $woocommerce;

    $order = new WC_Order( $order_id );
		
		try {
			$this->charge_payment( $order_id );
			if('veritrans_direct'==$this->select_veritrans_payment){
				return array(
					'result' => 'success',
					'redirect'  => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))))
				);
			}else{
				return array(
					'result' 	=> 'success',
					'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
				);
			}
		} catch (Exception $e) {
			$woocommerce->add_error( '<strong>' . __('Veritrans error: ', 'woocommerce') . '</strong>' . $e->getMessage() );
			return;
		}

  }

  /**
   * Charge Payment 
   */
  function charge_payment( $order_id ) {
    global $woocommerce;
		$order_items = array();
		if('veritrans_direct'==$this->select_veritrans_payment){
			// Check token id
			if( $_POST['veritrans_token_id'] == '' ) {
				throw new Exception( __('Invalid Token ID', 'woocommerce') );
			}

			$endpoint_url = 'https://payments.veritrans.co.id/vtdirect/v1/charges';
			$server_key = $this->server_key;
			$server_key = base64_encode($server_key . ':');
			$token_id = $_POST['veritrans_token_id'];

			$order = new WC_Order( $order_id );
			
			$shipping_address = array();
			$billing_address = array();

			// Order Items
			if( sizeof( $order->get_items() ) > 0 ) {
				foreach( $order->get_items() as $item ) {
					$order_items[] = array(
						'id' => $item['product_id'],
						'name' => substr($item['name'], 0, 20),
						'qty' => $item['qty'] / 1,
						'price' => ceil( $item['line_total'] / $item['qty'] )
					);
				}
			}

			// Shipping Fee
			$order_items[] = array(
				'id' => '1',
				'name' => 'Shipping Fee',
				'qty' => 1,
				'price' => ceil( $order->order_shipping / 1 )
			);
			
			// Tax Fee
			$order_items[] = array(
				'id' => '2',
				'name' => 'Tax',
				'qty' => 1,
				'price' => ceil( ($order->order_tax / 1) + ($order->order_shipping_tax / 1) )
			);

			// Shipping Address
			$shipping_address['first_name'] = $order->shipping_first_name;
			$shipping_address['last_name'] = $order->shipping_last_name;
			$shipping_address['address1'] = $order->shipping_address_1;
			$shipping_address['address2'] = $order->shipping_address_2;
			$shipping_address['city'] = $order->shipping_city;
			$shipping_address['postal_code'] = $order->shipping_postcode;
			$shipping_address['phone'] = $order->billing_phone;

			// Billing Address
			$billing_address['first_name'] = $order->billing_first_name;
			$billing_address['last_name'] = $order->billing_last_name;
			$billing_address['address1'] = $order->billing_address_1;
			$billing_address['address2'] = $order->billing_address_2;
			$billing_address['city'] = $order->billing_city;
			$billing_address['postal_code'] = $order->billing_postcode;
			$billing_address['phone'] = $order->billing_phone;

			// Body that will be send to Veritrans
			$body = array(
				'token_id' => $token_id,
				'order_id' => $order_id,
				'order_items' => $order_items,
				'gross_amount' => ceil( $order->order_total ),
				'email' => $order->billing_email,
				'shipping_address' => $shipping_address,
				'billing_address' => $billing_address
			);

			$headers = array( 
				'Authorization' => 'Basic ' . $server_key,
				'content-type' => 'application/json'
			);    

			$response = wp_remote_post( $endpoint_url, array(
				'body' => json_encode($body),
				'headers' => $headers,
				'timeout' => 20,
				'sslverify' => false
			) );

			// If wp_remote_post failed
			if( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$response_body = $response['body'];
			$response_body = json_decode( $response_body );

			// If response from Veritrans is failure
			if( $response_body->code != 'VD00' ) {
				throw new Exception( $response_body->message );
			}
			
			// Set order as complete
			$order->payment_complete();
	
			// Remove cart
			$woocommerce->cart->empty_cart();
		
    }else{
			$order = new WC_Order( $order_id );
			$order_items = array();
			// Order Items
			if( sizeof( $order->get_items() ) > 0 ) {
				foreach( $order->get_items() as $item ) {
					$order_items[] = array(
														"COMMODITY_ID" => $item['product_id'], 
														"COMMODITY_UNIT" => ceil( $item['line_total'] / $item['qty'] ), 
                            "COMMODITY_NUM" => $item['qty'] / 1, 
                            "COMMODITY_NAME1" => substr($item['name'], 0, 20), 
														"COMMODITY_NAME2" => substr($item['name'], 0, 20)
													);							
				}
			}
			
			$merchant_hash = $this->generate_merchant_hash($this->merchant_id, $this->merchant_hash_key, '01', $order_id, ceil($order->order_total));
			
			$datas = array(
				'MERCHANT_ID' => $this->merchant_id,
				'SETTLEMENT_TYPE' => '01',
				'MERCHANTHASH' => $merchant_hash,
				'ORDER_ID' => $order_id,
				'SESSION_ID' => $_COOKIE['PHPSESSID'],
				'GROSS_AMOUNT' => ceil($order->order_total),
				'EMAIL' => $order->billing_email,
				'SHIPPING_FLAG' => 0,
				'CUSTOMER_SPECIFICATION_FLAG' => 1,
				'LANG_ENABLE_FLAG' => 0,
				'FINISH_PAYMENT_RETURN_URL' => $this->notify_url,
				'ERROR_PAYMENT_RETURN_URL' => $this->notify_url
			);
			$billings = array();
			$shippings = array();
			$billings = array(
				'FIRST_NAME' 		=> $_POST['billing_first_name'],
				'LAST_NAME' 		=> $_POST['billing_last_name'],
				'ADDRESS1' 			=> $_POST['billing_address_1'],
				'ADDRESS2' 			=> $_POST['billing_address_2'],
				'CITY' 					=> $_POST['billing_city'],
				'COUNTRY_CODE' 	=> $_POST['billing_country'],
				'POSTAL_CODE' 	=> $_POST['billing_postcode'],
				'PHONE'					=> $_POST['billing_phone'],
			);
			if($_POST['shiptobilling']!=1){
				$shippings = array(
					'SHIPPING_FIRST_NAME' 		=> $_POST['shipping_first_name'],
					'SHIPPING_LAST_NAME' 			=> $_POST['shipping_last_name'],
					'SHIPPING_ADDRESS1' 			=> $_POST['shipping_address_1'],
					'SHIPPING_ADDRESS2' 			=> $_POST['shipping_address_2'],
					'SHIPPING_CITY' 					=> $_POST['shipping_city'],
					'SHIPPING_COUNTRY_CODE' 	=> $_POST['shipping_country'],
					'SHIPPING_POSTAL_CODE' 		=> $_POST['shipping_postcode'],
					'SHIPPING_PHONE'					=> $_POST['shipping_phone'],
				);
			}

			$query_string = http_build_query($datas);
			$commodity_query_string = $this->build_commodity_query_string( $order_items );
			$query_string = $query_string.'&'.$billings.'&'.$shippings.'&'.$commodity_query_string;
			$vtweb = wp_remote_post( self::VT_REQUEST_KEY_URL, array(
				'body' => $query_string,
				'timeout' => 20,
				'sslverify' => false
			) );
			// If wp_remote_post failed
			if( is_wp_error( $vtweb ) ) {
				throw new Exception( $vtweb->get_error_message() );
			}else{
				$token = array();
				$token = $this->extract_keys_from($vtweb['body']);
				//echo $this->generate_veritrans_form( $order_id, $token );
				//die();
				if ( ! empty( $token['token_browser'] ) )
	        update_post_meta( $order->id, '_token_browser', $token['token_browser'] );
				if ( ! empty( $token['token_merchant'] ) )
	        update_post_meta( $order->id, '_token_merchant', $token['token_merchant'] );	
			}

		}
	
  }
	function receipt_page( $order ) {

		echo '<p>'.__( 'Thank you for your order, please click the button below to pay with Veritrans.', 'woocommerce' ).'</p>';

		echo $this->generate_veritrans_form( $order );

	}
	
	private function generate_veritrans_form($order_id) {
		global $woocommerce;
		
		$order = new WC_Order( $order_id );
		
		$woocommerce->add_inline_js( '
			jQuery("body").block({
					message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to Veritrans to make payment.', 'woocommerce' ) ) . '",
					baseZ: 99999,
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},
					css: {
				        padding:        "20px",
				        zindex:         "9999999",
				        textAlign:      "center",
				        color:          "#555",
				        border:         "3px solid #aaa",
				        backgroundColor:"#fff",
				        cursor:         "wait",
				        lineHeight:		"24px",
				    }
				});
			jQuery("#submit_veritrans_payment_form").click();
		' );
		return '<form action="'.self::VT_PAYMENT_REDIRECT_URL.'" method="post" id="sent_form_token" target="_top">
							<input type="hidden" name="MERCHANT_ID" value="'.$this->merchant_id.'" />
							<input type="hidden" name="ORDER_ID" value="'.$order_id.'" />
							<input type="hidden" name="TOKEN_BROWSER" value="'.$order->order_custom_fields['_token_browser'][0].'" />
							<input type="hidden" name="TOKEN_MERCHANT" value="'.$order->order_custom_fields['_token_merchant'][0].'" />
							<input id="submit_veritrans_payment_form" type="submit" class="button alt" value="Confirm Checkout" />
							<a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancel order &amp; restore cart', 'woocommerce' ).'</a>
						</form>';
	}	
	private function extract_keys_from($body)
  {
    
    $key = array();
    $body_lines = explode("\n", $body);
    foreach($body_lines as $line) {
      if(preg_match('/^TOKEN_MERCHANT=(.+)/', $line, $match)) {
        $key['token_merchant'] = str_replace("\r", "", $match[1]);
        } elseif(preg_match('/^TOKEN_BROWSER=(.+)/', $line, $match)) {
          $key['token_browser'] = str_replace("\r", "", $match[1]);
          } elseif(preg_match('/^ERROR_MESSAGE=(.+)/', $line, $match)) {
            $key['error_message'] = str_replace("\r", "", $match[1]);
          }
      }
    return $key;

  }
	private function generate_merchant_hash($merchantID, $merchant_hash, $settlementmethod, $orderID, $amount) {
    $ctx  = hash_init('sha512');
    $str  = $merchant_hash .
      "," . $merchantID .
      "," . ((is_null($settlementmethod) || strlen($settlementmethod) == 0) ? '00' : $settlementmethod) .
      "," . $orderID .
      "," . $amount;
    hash_update($ctx, $str);
    $hash = hash_final($ctx, true);
    return bin2hex($hash);
  }
	
	private function build_commodity_query_string($commodity)
  {
    $line = 0;
    $query_string = "";
 
    foreach ($commodity as $row) {
      $q = http_build_query($row);
     
      if(!($query_string==""))
        $query_string = $query_string . "&";
 
      $query_string = $query_string . $q;
      $line = $line + 1;
    };
   
    $query_string = $query_string . "&REPEAT_LINE=" . $line;
             
    return $query_string;
  }
	
	/**
	 * Check for Veritrans Web Response
	 *
	 * @access public
	 * @return void
	 */
	function veritrans_vtweb_response() {
		@ob_clean();
		if( ('' != $_POST['orderId']) && ('success' == $_POST['mStatus']) ){
			header( 'HTTP/1.1 200 OK' );
			
      do_action( "valid-veritrans-web-request", $_POST );
		}else {

		wp_die( "Veritrans Request Failure" );

   	}

	}
	
	function successful_request( $posted ) {
		global $woocommerce;

		$posted = stripslashes_deep( $posted );

		$order = new WC_Order( $posted['orderId'] );
		// Set order as complete
    $order->payment_complete();

    // Remove cart
    $woocommerce->cart->empty_cart();
		
		wp_redirect( add_query_arg('key', $order->order_key, add_query_arg('order', $posted['orderId'], get_permalink(woocommerce_get_page_id('thanks')))) ); exit;
	}	
}