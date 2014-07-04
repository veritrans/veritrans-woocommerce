  <?php

  require_once(dirname(__FILE__) . '/../lib/veritrans/Veritrans.php');

  /**
  	 * Veritrans Payment Gateway Class
  	 */
  class WC_Gateway_Veritrans extends WC_Payment_Gateway {

    // const VT_REQUEST_KEY_URL = 'https://payments.veritrans.co.id/web1/commodityRegist.action';
    // const VT_PAYMENT_REDIRECT_URL = 'https://payments.veritrans.co.id/web1/paymentStart.action';
    const VT_REQUEST_KEY_URL = 'https://vtweb.veritrans.co.id/v1/tokens.json';
    const VT_PAYMENT_REDIRECT_URL = 'https://vtweb.veritrans.co.id/v1/payments.json';

    private $version = 1;

    // Redirect url configuration [optional. Can also be set at Merchant Administration Portal(MAP)]
    private $finish_payment_return_url;
    private $unfinish_payment_return_url;
    private $error_payment_return_url;

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
      $this->client_key_v2_sandbox         = $this->get_option( 'client_key_v2_sandbox' );
      $this->server_key_v2_sandbox         = $this->get_option( 'server_key_v2_sandbox' );
      $this->client_key_v2_production         = $this->get_option( 'client_key_v2_production' );
      $this->server_key_v2_production         = $this->get_option( 'server_key_v2_production' );

  		$this->merchant_id     		= $this->get_option( 'merchant_id' );
      $this->merchant_hash_key 	= $this->get_option( 'merchant_hash_key' );
      
      $this->api_version        = 2;
      $this->environment        = $this->get_option( 'select_veritrans_environment' );
      
      $this->to_idr_rate        = $this->get_option( 'to_idr_rate' );

      $this->enable_3d_secure   = $this->get_option( 'enable_3d_secure' );

      $this->log = new WC_Logger(); 

  		// Payment listener/API hook
  		add_action( 'woocommerce_api_wc_gateway_veritrans', array( &$this, 'veritrans_vtweb_response' ) );
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); 
      add_action( 'wp_enqueue_scripts', array( &$this, 'veritrans_scripts' ) );
      add_action( 'admin_print_scripts-woocommerce_page_woocommerce_settings', array( &$this, 'veritrans_admin_scripts' ));
  		add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'veritrans_admin_scripts' ));
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

  		if($this->description) echo '<p>'.$this->description.'</p>';

  		if('veritrans_direct'==$this->select_veritrans_payment) : ?>	
        <p class="form-row validate-required" id="veritrans_credit_card_field">
          <label for="veritrans_credit_card_field">
            <?php _e('Credit Card Number'); ?>
            <abbr class="required" title="required">*</abbr>
          </label>
          <input type="text" class="input-text veritrans_credit_card" maxlength="16">
        </p>

        <p class="form-row" id="veritrans_card_exp_month_field">
          <label for="veritrans_card_exp_month_field">
            <?php _e('Expiration Date - Month', 'woocommerce'); ?>
            <abbr class="required" title="required">*</abbr>
          </label>
          <select class="veritrans_card_exp_month">
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
          <select class="veritrans_card_exp_year">
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
          <input type="text" class="input-text veritrans_security">
        </p>

        <input type="text" name="veritrans_token_id" class="hide" style="display:none">
      <?php endif; ?>
    <?php }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
  		
      $key_url = 'https://payments.veritrans.co.id/map/settings/config_info';
      $v2_sandbox_key_url = 'https://my.sandbox.veritrans.co.id';
      $v2_production_key_url = 'https://my.veritrans.co.id';
      
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
          'default' => __( 'Credit Card', 'woocommerce' ),
          'desc_tip'      => true,
        ),
        'description' => array(
          'title' => __( 'Customer Message', 'woocommerce' ),
          'type' => 'textarea',
  				'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
          'default' => ''
        ),
        'select_veritrans_environment' => array(
          'title' => __( 'Environment', 'woocommerce' ),
          'type' => 'select',
          'default' => 'sandbox',
          'description' => __( 'Select the Veritrans Environment', 'woocommerce' ),
          'options'   => array(
            'sandbox'    => __( 'Sandbox', 'woocommerce' ),
            'production'   => __( 'Production', 'woocommerce' ),
          ),
        ),
        'client_key_v2_sandbox' => array(
          'title' => __("Client Key", 'woocommerce'),
          'type' => 'text',
          'description' => sprintf(__('Input your <b>Sandbox</b> Veritrans Client Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_sandbox_key_url),
          'default' => '',
          'class' => 'sandbox_settings sensitive',
        ),
        'server_key_v2_sandbox' => array(
          'title' => __("Server Key", 'woocommerce'),
          'type' => 'text',
          'description' => sprintf(__('Input your <b>Sandbox</b> Veritrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_sandbox_key_url),
          'default' => '',
          'class' => 'sandbox_settings sensitive'
        ),
        'client_key_v2_production' => array(
          'title' => __("Client Key", 'woocommerce'),
          'type' => 'text',
          'description' => sprintf(__('Input your <b>Production</b> Veritrans Client Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_production_key_url),
          'default' => '',
          'class' => 'production_settings sensitive',
        ),
        'server_key_v2_production' => array(
          'title' => __("Server Key", 'woocommerce'),
          'type' => 'text',
          'description' => sprintf(__('Input your <b>Production</b> Veritrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_production_key_url),
          'default' => '',
          'class' => 'production_settings sensitive'
        ),
        'enable_3d_secure' => array(
          'title' => __( 'Enable 3D Secure', 'woocommerce' ),
          'type' => 'checkbox',
          'label' => __( 'Enable 3D Secure?', 'woocommerce' ),
          'default' => 'no'
        ),
      );

      if (get_woocommerce_currency() != 'IDR')
      {
        $this->form_fields['to_idr_rate'] = array(
          'title' => __("Current Currency to IDR Rate", 'woocommerce'),
          'type' => 'text',
          'description' => 'The current currency to IDR rate',
          'default' => '10000',
        );
      }
    }

    /**
     * Process the payment and return the result
     */
    function process_payment( $order_id ) {
      global $woocommerce;

      $order = new WC_Order( $order_id );
  		
  		try {
  			$this->charge_payment( $order_id );
  			
        if('veritrans_direct'==$this->select_veritrans_payment) {
  				return array(
  					'result' => 'success',
  					'redirect'  => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))))
  				);
  			}

        else {
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
      $this->charge_v2_vtweb_payment( $order_id );
    }

    /**
     * Routine for charging v2 VT-WEB
     */
    function charge_v2_vtweb_payment( $order_id )
    {
      global $woocommerce;
      $order_items = array();

      $order = new WC_Order( $order_id );     
  	
    	Veritrans::$isProduction = ($this->environment == 'production' ? true : false);
        
    	if (Veritrans::$isProduction) {
        Veritrans::$serverKey = $this->server_key_v2_production;
      }
      else {
        Veritrans::$serverKey = $this->server_key_v2_sandbox;
      }

      if ($this->enable_3d_secure == 'yes') {
        Veritrans::$is3ds = TRUE;
      }
      
      $params = array(
  		  'transaction_details' => array(
    			'order_id' => $order_id,
    			'gross_amount' => 0,
  		  ),
  		  'vtweb' => array()
      );
  	
    	$customer_details = array();
    	$customer_details['first_name'] = $_POST['billing_first_name'];
    	$customer_details['last_name'] = $_POST['billing_last_name'];
    	$customer_details['email'] = $_POST['billing_email'];
    	$customer_details['phone'] = $_POST['billing_phone'];
    	
    	$billing_address = array();
    	$billing_address['first_name'] = $_POST['billing_first_name'];
    	$billing_address['last_name'] = $_POST['billing_last_name'];
    	$billing_address['address'] = $_POST['billing_address_1'];
    	$billing_address['city'] = $_POST['billing_city'];
    	$billing_address['postal_code'] = $_POST['billing_postcode'];
      $billing_address['phone'] = $_POST['billing_phone'];
    	$billing_address['country_code'] = $_POST['billing_country'];
    	
    	$customer_details['billing_address'] = $billing_address;
  	
    	if ($_POST['ship_to_different_address']) {
    	  $shipping_address = array();
        $shipping_address['first_name'] = $_POST['shipping_first_name'];
        $shipping_address['last_name'] = $_POST['shipping_last_name'];
    	  $shipping_address['address'] = $_POST['shipping_address_1'];
        $shipping_address['city'] = $_POST['shipping_city'];
    	  $shipping_address['postal_code'] = $_POST['shipping_postcode'];
        $shipping_address['phone'] = $_POST['billing_phone'];
    	  $shipping_address['country_code'] = $_POST['shipping_country'];
    	  
    	  $customer_details['shipping_address'] = $shipping_address;
    	}
    	
    	$params['customer_details'] = $customer_details;
    	
      // Populate Items
      $items = array();

      if( sizeof( $order->get_items() ) > 0 ) {
        foreach( $order->get_items() as $item ) {
          if ( $item['qty'] ) {
            $product = $order->get_product_from_item( $item );

            $veritrans_item = array();

            $veritrans_item['id']    = $item['product_id'];
            $veritrans_item['price']      = $order->get_item_subtotal( $item, false );
            $veritrans_item['quantity']   = $item['qty'];
    	      $veritrans_item['name'] = $item['name'];
            
            $items[] = $veritrans_item;
          }
        }
      }

      // Shipping fee
      if( $order->get_total_shipping() > 0 ) {
        $items[] = array(
          'id' => 'shippingfee',
          'price' => $order->get_total_shipping(),
          'quantity' => 1,
          'name' => 'Shipping Fee',
        );
      }

      // Tax
      if( $order->get_total_tax() > 0 ) {
        $items[] = array(
          'id' => 'taxfee',
          'price' => $order->get_total_tax(),
          'quantity' => 1,
          'name' => 'Tax',
        );
      }

      // Fees
      if ( sizeof( $order->get_fees() ) > 0 ) {
        $fees = $order->get_fees();
        for ( $i = 0; $i < count($fees); $i++ ) {
          $items[] = array(
            'id' => 'itemfee' . $i,
            'price' => $item['line_total'],
            'quantity' => 1,
            'name' => 'Fee ' . $i,
          ); 
        }
      }

      // sift through the entire item to ensure that currency conversion is applied
      if (get_woocommerce_currency() != 'IDR')
      {
        foreach ($items as &$item) {
          $item['price'] = $item['price'] * $this->to_idr_rate;
        }

        unset($item);
      }
    	
    	$params['item_details'] = $items;
    	
    	foreach ($items as $item) {
    		$params['transaction_details']['gross_amount'] += $item['price'] * intval($item['quantity']);
    	}
        
        // $charge_result = $veritrans->getTokens();
        
    	try {
    		// Redirect to Veritrans VTWeb page
    		header('Location: ' . Veritrans_VtWeb::getRedirectionUrl($params));
    		exit;
    	}
    	catch (Exception $e) {
    		echo $e->getMessage();
    	}
  	
      // If wp_remote_post failed
      // if( $veritrans->error ) {
        // throw new Exception( $veritrans->error );
      // } else {
        // header('Location:' . $charge_result['redirect_url']);
        // exit;
        // $result = json_decode( wp_remote_retrieve_body( $vtweb ), true );

        // // check result
        // if( !empty($result['token_merchant']) ) {
        //   // No error

        //   if ( ! empty( $result['token_browser'] ) )
        //     update_post_meta( $order->id, '_token_browser', $result['token_browser'] );
        //   if ( ! empty( $result['token_merchant'] ) )
        //     update_post_meta( $order->id, '_token_merchant', $result['token_merchant'] );  
        // }

        // else {
        //   // Veritrans doesn't return tokens
        //   $error_str = '';
        //   foreach( $result['errors'] as $error_name => $error_message ) {
        //     $error_str .= "<br><strong>{$error_name}</strong>: {$error_message}\n";
        //   }
        //   throw new Exception( $error_str );
        // }
      // }
    }

    /**
     * Hook into receipt page, the destination page after checkout redirect
     */
  	function receipt_page( $order ) {
  		echo '<p>'.__( 'Thank you for your order, please click the button below to pay with Veritrans.', 'woocommerce' ).'</p>';
  		echo $this->generate_veritrans_form( $order );
  	}
  	
    /**
     * Generate redirect form
     * @param  Int $order_id Order ID
     * @return void
     */
  	public function generate_veritrans_form($order_id) {
  		global $woocommerce;
  		
  		$order = new WC_Order( $order_id );
      
  		$woocommerce->add_inline_js( '
  			$.blockUI({
  				message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to Veritrans to make payment.', 'woocommerce' ) ) . '",
  				baseZ: 99999,
  				overlayCSS: {
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

  		return '
        <form action="'.self::VT_PAYMENT_REDIRECT_URL.'" method="post" id="sent_form_token" target="_top">
    			<input type="hidden" name="merchant_id" value="'.$this->merchant_id.'" />
    			<input type="hidden" name="order_id" value="'.$order_id.'" />
    			<input type="hidden" name="token_browser" value="'.get_post_meta( $order_id, '_token_browser', true ).'" />
    			<input id="submit_veritrans_payment_form" type="submit" class="button alt" value="Confirm Checkout" />
    			<a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancel order &amp; restore cart', 'woocommerce' ).'</a>
    		</form>';
  	}	

    /**
     * Generate Merchant Hashs
     * @param  String $merchantID       Merchant ID
     * @param  String $merchant_hash    Merchant Hash Key
     * @param  String $orderID          Order ID
     * @return String                   Generated Hash Value
     */
  	private function generate_merchant_hash($merchantID, $merchant_hash, $orderID) {
      $ctx  = hash_init('sha512');
      $str  = $merchant_hash .
        "," . $merchantID .
        "," . $orderID;
      hash_update($ctx, $str);
      $hash = hash_final($ctx, true);
      return bin2hex($hash);
    }
  	
  	/**
  	 * Check for Veritrans Web Response
  	 *
  	 * @access public
  	 * @return void
  	 */
  	function veritrans_vtweb_response() {
      global $woocommerce;
  		@ob_clean();

      $params = json_decode( file_get_contents('php://input'), true );

      if ($this->environment == 'production') {
        Veritrans::$serverKey = $this->server_key_v2_production;
      } else {
        Veritrans::$serverKey = $this->server_key_v2_sandbox;
      }
      
      $veritrans_notification = new Veritrans_Notification();
      
      if ($veritrans_notification->verified()) {
        if (in_array($veritrans_notification->status_code, array(200, 201, 202))) {

          $veritrans_confirmation = Veritrans_Transaction::status($veritrans_notification->order_id);

          if ($veritrans_confirmation) {
            $order = new WC_Order( $veritrans_notification->order_id );
            // error_log('transaction_status: ' . $veritrans_notification->transaction_status);
            // error_log('fraud_status :' . $veritrans_notification->fraud_status);

            if ($veritrans_notification->transaction_status == 'capture') {
              if ($veritrans_notification->fraud_status == 'accept') {
                $order->payment_complete();
              }
              else if ($veritrans_notification->fraud_status == 'challenge') {
                $order->update_status('on-hold');
              }
            }
            else if ($veritrans_notification->transaction_status == 'cancel') {
              $order->update_status('cancelled');
            }
            else if ($veritrans_notification->transaction_status == 'deny') {
              $order->update_status('failed');
            }

            $woocommerce->cart->empty_cart();
          }
        }
      }
  	}
  	

  	function successful_request( $posted ) {
  		global $woocommerce;

  		$posted = stripslashes_deep( $posted );

  		$order = new WC_Order( $posted['orderId'] );
  		// Set order as complete
      $order->payment_complete();

      // Reduce stock levels
      $order->reduce_order_stock();

      // Remove cart
      $woocommerce->cart->empty_cart();
  		
  		wp_redirect( add_query_arg('key', $order->order_key, add_query_arg('order', $posted['orderId'], get_permalink(woocommerce_get_page_id('thanks')))) ); exit;
  	}

    /**
     * Convert 2 digits coundry code to 3 digit country code
     *
     * @param String $country_code Country code which will be converted
     */
    public function convert_country_code( $country_code ) {

      // 3 digits country codes
      $cc_three = array(
        'AF' => 'AFG',
        'AX' => 'ALA',
        'AL' => 'ALB',
        'DZ' => 'DZA',
        'AD' => 'AND',
        'AO' => 'AGO',
        'AI' => 'AIA',
        'AQ' => 'ATA',
        'AG' => 'ATG',
        'AR' => 'ARG',
        'AM' => 'ARM',
        'AW' => 'ABW',
        'AU' => 'AUS',
        'AT' => 'AUT',
        'AZ' => 'AZE',
        'BS' => 'BHS',
        'BH' => 'BHR',
        'BD' => 'BGD',
        'BB' => 'BRB',
        'BY' => 'BLR',
        'BE' => 'BEL',
        'PW' => 'PLW',
        'BZ' => 'BLZ',
        'BJ' => 'BEN',
        'BM' => 'BMU',
        'BT' => 'BTN',
        'BO' => 'BOL',
        'BQ' => 'BES',
        'BA' => 'BIH',
        'BW' => 'BWA',
        'BV' => 'BVT',
        'BR' => 'BRA',
        'IO' => 'IOT',
        'VG' => 'VGB',
        'BN' => 'BRN',
        'BG' => 'BGR',
        'BF' => 'BFA',
        'BI' => 'BDI',
        'KH' => 'KHM',
        'CM' => 'CMR',
        'CA' => 'CAN',
        'CV' => 'CPV',
        'KY' => 'CYM',
        'CF' => 'CAF',
        'TD' => 'TCD',
        'CL' => 'CHL',
        'CN' => 'CHN',
        'CX' => 'CXR',
        'CC' => 'CCK',
        'CO' => 'COL',
        'KM' => 'COM',
        'CG' => 'COG',
        'CD' => 'COD',
        'CK' => 'COK',
        'CR' => 'CRI',
        'HR' => 'HRV',
        'CU' => 'CUB',
        'CW' => 'CUW',
        'CY' => 'CYP',
        'CZ' => 'CZE',
        'DK' => 'DNK',
        'DJ' => 'DJI',
        'DM' => 'DMA',
        'DO' => 'DOM',
        'EC' => 'ECU',
        'EG' => 'EGY',
        'SV' => 'SLV',
        'GQ' => 'GNQ',
        'ER' => 'ERI',
        'EE' => 'EST',
        'ET' => 'ETH',
        'FK' => 'FLK',
        'FO' => 'FRO',
        'FJ' => 'FJI',
        'FI' => 'FIN',
        'FR' => 'FRA',
        'GF' => 'GUF',
        'PF' => 'PYF',
        'TF' => 'ATF',
        'GA' => 'GAB',
        'GM' => 'GMB',
        'GE' => 'GEO',
        'DE' => 'DEU',
        'GH' => 'GHA',
        'GI' => 'GIB',
        'GR' => 'GRC',
        'GL' => 'GRL',
        'GD' => 'GRD',
        'GP' => 'GLP',
        'GT' => 'GTM',
        'GG' => 'GGY',
        'GN' => 'GIN',
        'GW' => 'GNB',
        'GY' => 'GUY',
        'HT' => 'HTI',
        'HM' => 'HMD',
        'HN' => 'HND',
        'HK' => 'HKG',
        'HU' => 'HUN',
        'IS' => 'ISL',
        'IN' => 'IND',
        'ID' => 'IDN',
        'IR' => 'RIN',
        'IQ' => 'IRQ',
        'IE' => 'IRL',
        'IM' => 'IMN',
        'IL' => 'ISR',
        'IT' => 'ITA',
        'CI' => '',
        'JM' => 'JAM',
        'JP' => 'JPN',
        'JE' => 'JEY',
        'JO' => 'JOR',
        'KZ' => 'KAZ',
        'KE' => 'KEN',
        'KI' => 'KIR',
        'KW' => 'KWT',
        'KG' => 'KGZ',
        'LA' => 'LAO',
        'LV' => 'LVA',
        'LB' => 'LBN',
        'LS' => 'LSO',
        'LR' => 'LBR',
        'LY' => 'LBY',
        'LI' => 'LIE',
        'LT' => 'LTU',
        'LU' => 'LUX',
        'MO' => 'MAC',
        'MK' => 'MKD',
        'MG' => 'MDG',
        'MW' => 'MWI',
        'MY' => 'MYS',
        'MV' => 'MDV',
        'ML' => 'MLI',
        'MT' => 'MLT',
        'MH' => 'MHL',
        'MQ' => 'MTQ',
        'MR' => 'MRT',
        'MU' => 'MUS',
        'YT' => 'MYT',
        'MX' => 'MEX',
        'FM' => 'FSM',
        'MD' => 'MDA',
        'MC' => 'MCO',
        'MN' => 'MNG',
        'ME' => 'MNE',
        'MS' => 'MSR',
        'MA' => 'MAR',
        'MZ' => 'MOZ',
        'MM' => 'MMR',
        'NA' => 'NAM',
        'NR' => 'NRU',
        'NP' => 'NPL',
        'NL' => 'NLD',
        'AN' => 'ANT',
        'NC' => 'NCL',
        'NZ' => 'NZL',
        'NI' => 'NIC',
        'NE' => 'NER',
        'NG' => 'NGA',
        'NU' => 'NIU',
        'NF' => 'NFK',
        'KP' => 'MNP',
        'NO' => 'NOR',
        'OM' => 'OMN',
        'PK' => 'PAK',
        'PS' => 'PSE',
        'PA' => 'PAN',
        'PG' => 'PNG',
        'PY' => 'PRY',
        'PE' => 'PER',
        'PH' => 'PHL',
        'PN' => 'PCN',
        'PL' => 'POL',
        'PT' => 'PRT',
        'QA' => 'QAT',
        'RE' => 'REU',
        'RO' => 'SHN',
        'RU' => 'RUS',
        'RW' => 'EWA',
        'BL' => 'BLM',
        'SH' => 'SHN',
        'KN' => 'KNA',
        'LC' => 'LCA',
        'MF' => 'MAF',
        'SX' => 'SXM',
        'PM' => 'SPM',
        'VC' => 'VCT',
        'SM' => 'SMR',
        'ST' => 'STP',
        'SA' => 'SAU',
        'SN' => 'SEN',
        'RS' => 'SRB',
        'SC' => 'SYC',
        'SL' => 'SLE',
        'SG' => 'SGP',
        'SK' => 'SVK',
        'SI' => 'SVN',
        'SB' => 'SLB',
        'SO' => 'SOM',
        'ZA' => 'ZAF',
        'GS' => 'SGS',
        'KR' => '',
        'SS' => 'SSD',
        'ES' => 'ESP',
        'LK' => 'LKA',
        'SD' => 'SDN',
        'SR' => 'SUR',
        'SJ' => 'SJM',
        'SZ' => 'SWZ',
        'SE' => 'SWE',
        'CH' => 'CHE',
        'SY' => 'SYR',
        'TW' => 'TWN',
        'TJ' => 'TJK',
        'TZ' => 'TZA',
        'TH' => 'THA',
        'TL' => 'TLS',
        'TG' => 'TGO',
        'TK' => 'TKL',
        'TO' => 'TON',
        'TT' => 'TTO',
        'TN' => 'TUN',
        'TR' => 'TUR',
        'TM' => 'TKM',
        'TC' => 'TCA',
        'TV' => 'TUV',
        'UG' => 'UGA',
        'UA' => 'UKR',
        'AE' => 'ARE',
        'GB' => 'GBR',
        'US' => 'USA',
        'UY' => 'URY',
        'UZ' => 'UZB',
        'VU' => 'VUT',
        'VA' => 'VAT',
        'VE' => 'VEN',
        'VN' => 'VNM',
        'WF' => 'WLF',
        'EH' => 'ESH',
        'WS' => 'WSM',
        'YE' => 'YEM',
        'ZM' => 'ZMB',
        'ZW' => 'ZWE'
      );

      // Check if country code exists
      if( isset( $cc_three[ $country_code ] ) && $cc_three[ $country_code ] != '' ) {
        $country_code = $cc_three[ $country_code ];
      }

      return $country_code;
    }
  }

