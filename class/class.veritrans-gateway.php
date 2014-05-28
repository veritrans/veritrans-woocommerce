<?php

require_once(dirname(__FILE__) . '/../lib/veritrans/veritrans.php');
require_once(dirname(__FILE__) . '/../lib/veritrans/lib/veritrans_notification.php');

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
		$this->merchant_id     		= $this->get_option( 'merchant_id' );
    $this->merchant_hash_key 	= $this->get_option( 'merchant_hash_key' );
    $this->api_version        = $this->get_option( 'select_veritrans_api_version' );
    $this->environment        = $this->get_option( 'select_veritrans_environment' );
    $this->to_idr_rate        = $this->get_option( 'to_idr_rate' );

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
   * Validate Payment Fields
   */
  function validate_fields() {
    global $woocommerce;
		/*if('veritrans_direct'==$this->select_veritrans_payment){
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
		}*/
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
      'select_veritrans_api_version' => array(
        'title' => __( 'API Version', 'woocommerce' ),
        'type' => 'select',
        'default' => 2,
        'description' => __( 'Select the Veritrans API version', 'woocommerce' ),
        'options'   => array(
          1    => __( 'v1', 'woocommerce' ),
          2   => __( 'v2', 'woocommerce' ),
        ),
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
        'class' => 'v2_settings sensitive',
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
        'class' => 'v1_settings sensitive',
      ),
			'merchant_id' => array(
        'title' => __( 'Merchant ID', 'woocommerce' ),
        'type' => 'text',
				'description' => sprintf(__( 'Enter your Veritrans Merchant ID. Get the ID <a href="%s" target="_blank">here</a>', 'woocommerce' ),$key_url),
        'class' => 'v1_vtweb_settings sensitive',
      ),
			'merchant_hash_key' => array(
        'title' => __( 'Merchant Hash Key', 'woocommerce' ),
        'type' => 'text',
				'description' => sprintf(__( 'Enter your Veritrans Merchant hash key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$key_url),
        'class' => 'v1_vtweb_settings sensitive',
      ),
      'client_key' => array(
        'title' => __("Client Key", 'woocommerce'),
        'type' => 'text',
				'description' => sprintf(__('Input your Veritrans Client Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$key_url),
        'default' => '',
        'class' => 'v1_vtdirect_settings',
      ),
      'server_key' => array(
        'title' => __("Server Key", 'woocommerce'),
        'type' => 'text',
				'description' => sprintf(__('Input your Veritrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$key_url),
        'default' => '',
        'class' => 'v1_vtdirect_settings'
      ),
    );

    if (get_woocommerce_currency() != 'IDR')
    {
      $this->form_fields['to_idr_rate'] = array(
        'title' => __("Current Currency to IDR Rate", 'woocommerce'),
        'type' => 'text',
        'class'     => 'veritrans_direct',
        'description' => 'The current currency to IDR rate',
        'default' => '10000',
        'class' => 'v1_vtdirect_settings'
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

    if (2 == $this->api_version)
    {
      $this->charge_v2_vtweb_payment( $order_id );
    } else
    {
      if ('veritrans_direct' == $this->select_veritrans_payment)
      {
        $this->charge_v1_vtdirect_payment( $order_id );
      } else
      {
        $this->charge_v1_vtweb_payment( $order_id );
      }
    }

  }

  /**
   * Routine for charging v2 VT-WEB
   */
  function charge_v2_vtweb_payment( $order_id )
  {
    global $woocommerce;
    $order_items = array();

    $order = new WC_Order( $order_id );     
    $veritrans = new Veritrans();

    $veritrans->api_version = 2;
    $veritrans->environment = ($this->environment == 'production' ? Veritrans::ENVIRONMENT_PRODUCTION : Veritrans::ENVIRONMENT_DEVELOPMENT);
    $veritrans->payment_type = ($this->select_veritrans_payment == 'veritrans_direct' ? Veritrans::VT_DIRECT : Veritrans::VT_WEB);
    $veritrans->server_key = $this->server_key;
    $veritrans->order_id = $order_id;

    if ($_POST['ship_to_different_address'])
    {
      $veritrans->required_shipping_address = 1;
      $veritrans->billing_different_with_shipping = 1;
      $veritrans->shipping_first_name = $_POST['shipping_first_name'];
      $veritrans->shipping_last_name = $_POST['shipping_last_name'];
      $veritrans->shipping_address1 = $_POST['shipping_address_1'];
      $veritrans->shipping_address2 = $_POST['shipping_address_2'];
      $veritrans->shipping_city = $_POST['shipping_city'];
      $veritrans->shipping_country_code = $_POST['shipping_country'];
      $veritrans->shipping_phone = $_POST['billing_phone'];
      $veritrans->shipping_postal_code = $_POST['shipping_postcode'];
    } else
    {
      $veritrans->required_shipping_address = 1;
      $veritrans->billing_different_with_shipping = 0;
    }

    $veritrans->first_name = $_POST['billing_first_name'];
    $veritrans->last_name = $_POST['billing_last_name'];
    $veritrans->address1 = $_POST['billing_address_1'];
    $veritrans->address2 = $_POST['billing_address_2'];
    $veritrans->city = $_POST['billing_city'];
    $veritrans->country_code = $_POST['billing_country'];
    $veritrans->postal_code = $_POST['billing_postcode'];
    $veritrans->phone = $_POST['billing_phone'];
    $veritrans->email = $_POST['billing_email'];

    // Populate Items
    $items = array();

    if( sizeof( $order->get_items() ) > 0 ) {
      foreach( $order->get_items() as $item ) {
        if ( $item['qty'] ) {
          $product = $order->get_product_from_item( $item );

          $veritrans_item = array();

          $veritrans_item['item_id']    = $item['product_id'];
          $veritrans_item['item_name1'] = $item['name'];
          $veritrans_item['item_name2'] = $item['name'];
          $veritrans_item['price']      = $order->get_item_subtotal( $item, false );
          $veritrans_item['quantity']   = $item['qty'];

          $items[] = $veritrans_item;
        }
      }
    }

    // Shipping fee
    if( $order->get_total_shipping() > 0 ) {
      $items[] = array(
        'item_id' => 'shippingfee',
        'item_name1' => 'Shipping Fee',
        'item_name2' => 'Shipping Fee',
        'price' => $order->get_total_shipping(),
        'quantity' => 1,
        );
    }

    // Tax
    if( $order->get_total_tax() > 0 ) {
      $items[] = array(
        'item_id' => 'taxfee',
        'item_name1' => 'Tax',
        'item_name2' => 'Tax',
        'price' => $order->get_total_tax(),
        'quantity' => 1,
        );
    }

    // Fees
    if ( sizeof( $order->get_fees() ) > 0 ) {
      $fees = $order->get_fees();
      for ( $i = 0; $i < count($fees); $i++ ) {
        $items[] = array(
          'item_id' => 'itemfee' . $i,
          'item_name1' => 'Fee ' . $i,
          'item_name2' => 'Fee ' . $i,
          'price' => $item['line_total'],
          'quantity' => 1,
          ); 
      }
    }

    // sift through the entire item to ensure that currency conversion is applied
    if (get_woocommerce_currency() != 'IDR')
    {
      foreach ($items as &$item) {
        $item['price'] = $item['price'] * $this->to_idr_rate;
      }
    }

    $veritrans->items = $items;
    $veritrans->force_sanitization = TRUE;

    $charge_result = $veritrans->getTokens();
    
    // If wp_remote_post failed
    if( $veritrans->error ) {
      throw new Exception( $veritrans->error );
    } else {
      header('Location:' . $charge_result['redirect_url']);
      exit;
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
    }
  }

  /**
   * Routine for charging v1 VT-WEB
   */
  function charge_v1_vtweb_payment( $order_id )
  {
    global $woocommerce;
    $order_items = array();

    $order = new WC_Order( $order_id );     
    $merchant_hash = $this->generate_merchant_hash($this->merchant_id, $this->merchant_hash_key, $order_id);

    $data = array(
      'version'                         => $this->version,
      'merchant_id'                     => $this->merchant_id,
      'merchanthash'                    => $merchant_hash,

      'order_id'                        => $order_id,
      'billing_different_with_shipping' => isset($_POST['ship_to_different_address']) ? $_POST['ship_to_different_address'] : 0,
      'required_shipping_address'       => 1,

      'shipping_first_name'             => $_POST['shipping_first_name'],
      'shipping_last_name'              => $_POST['shipping_last_name'],
      'shipping_address1'               => $_POST['shipping_address_1'],
      'shipping_address2'               => $_POST['shipping_address_2'],
      'shipping_city'                   => $_POST['shipping_city'],
      'shipping_country_code'           => $this->convert_country_code( $_POST['shipping_country'] ), //ISO 3166-1 alpha-3
      'shipping_postal_code'            => $_POST['shipping_postcode'],
      'shipping_phone'                  => $_POST['billing_phone'],

      'email'                           => $_POST['billing_email'], 

      'first_name'                      => $_POST['billing_first_name'],
      'last_name'                       => $_POST['billing_last_name'],
      'postal_code'                     => $_POST['billing_postcode'],
      'address1'                        => $_POST['billing_address_1'],
      'address2'                        => $_POST['billing_address_2'],
      'city'                            => $_POST['billing_city'],
      'country_code'                    => $this->convert_country_code( $_POST['billing_country'] ), //ISO 3166-1 alpha-3
      'phone'                           => $_POST['billing_phone'], 

      /* Optional 
      'finish_payment_return_url'       => $this->finish_payment_return_url,
      'unfinish_payment_return_url'     => $this->unfinish_payment_return_url,
      'error_payment_return_url'        => $this->error_payment_return_url,

      'enable_3d_secure'                => 1,

      'bank'                            => 'bni',
      'installment_banks'               => ["bni", "cimb"]
      'promo_bins'                      => '',
      'point_banks'                     => ["bni", "cimb"],
      'payment_methods'                 => ["credit_card", "mandiri_clickpay"]
      'installment_terms'               => ''
      */
    );

    // Populate Items
    $data['repeat_line'] = 0;
    if( sizeof( $order->get_items() ) > 0 ) {
      foreach( $order->get_items() as $item ) {
        if ( $item['qty'] ) {
          $product = $order->get_product_from_item( $item );

          $item_id[]    = $item['product_id'];
          $item_name1[] = substr($item['name'], 0, 20);
          $item_name2[] = substr($item['name'], 0, 20);
          $price[]      = ceil( $order->get_item_subtotal( $item, false ) );
          $quantity[]   = $item['qty'];

          $data['repeat_line']++;
        }
      }
    }

    // Shipping fee
    if( $order->get_total_shipping() > 0 ) {
      $item_id[] = 'shippingfee';
      $item_name1[] = 'Shipping Fee';
      $item_name2[] = 'Shipping Fee';
      $price[] = ceil($order->get_total_shipping());
      $quantity[] = 1;

      $data['repeat_line']++;
    }

    // Tax
    if( $order->get_total_tax() > 0 ) {
      $item_id[] = 'taxfee';
      $item_name1[] = 'Tax';
      $item_name2[] = 'Tax';
      $price[] = ceil($order->get_total_tax());
      $quantity[] = 1;

      $data['repeat_line']++;
    }

    // Fees
    if ( sizeof( $order->get_fees() ) > 0 ) {
      foreach ( $order->get_fees() as $item ) {
        $data['repeat_line']++;

        $item_id[] = 'itemfee' . $data['repeat_line'];
        $item_name1[] = 'Fee ' . $data['repeat_line'];
        $item_name2[] = 'Fee ' . $data['repeat_line'];
        $price[] = ceil($item['line_total']);
        $quantity[] = 1;
      }
    }

    $data['item_id']    = $item_id;
    $data['item_name1'] = $item_name1;
    $data['item_name2'] = $item_name2;
    $data['price']      = $price;
    $data['quantity']   = $quantity;

    $headers = array( 
      'accept' => 'application/json',
      'content-type' => 'application/json'
    );

    $vtweb = wp_remote_post( self::VT_REQUEST_KEY_URL, array(
      'body' => json_encode($data),
      'timeout' => 30,
      'sslverify' => false,
      'headers' => $headers
    ) );

    // If wp_remote_post failed
    if( is_wp_error( $vtweb ) ) {
      throw new Exception( $vtweb->get_error_message() );
    }else{
      $result = json_decode( wp_remote_retrieve_body( $vtweb ), true );

      // check result
      if( !empty($result['token_merchant']) ) {
        // No error

        if ( ! empty( $result['token_browser'] ) )
          update_post_meta( $order->id, '_token_browser', $result['token_browser'] );
        if ( ! empty( $result['token_merchant'] ) )
          update_post_meta( $order->id, '_token_merchant', $result['token_merchant'] );  
      }

      else {
        // Veritrans doesn't return tokens
        $error_str = '';
        foreach( $result['errors'] as $error_name => $error_message ) {
          $error_str .= "<br><strong>{$error_name}</strong>: {$error_message}\n";
        }
        throw new Exception( $error_str );
      }
    }
  }

  /**
   * Routine for charging v1 VT-DIRECT
   */
  function charge_v1_vtdirect_payment( $order_id )
  {
    global $woocommerce;
    $order_items = array();

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
          'price' => ceil( $order->get_item_subtotal( $item, false ) )
        );
      }
    }

    // Shipping Fee
    if( $order->get_total_shipping() > 0 ) {
      $order_items[] = array(
        'id' => 'shippingfee',
        'name' => 'Shipping Fee',
        'qty' => 1,
        'price' => ceil( $order->get_total_shipping() )
      );
    }

    // Tax
    if( $order->get_total_tax() > 0 ) {
      $order_items[] = array(
        'id' => 'taxfee',
        'name' => 'Tax',
        'qty' => 1,
        'price' => ceil($order->get_total_tax())
      );
    }

    // Fees
    if ( sizeof( $order->get_fees() ) > 0 ) {
      $fee_counter = 0;
      foreach ( $order->get_fees() as $item ) {
        $fee_counter++;
        $order_items[] = array(
          'id' => 'feeitem' . $fee_counter,
          'name' => 'Fee Item ' . $fee_counter,
          'qty' => 1,
          'price' => ceil( $item['line_total'] )
        );
      }
    }

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

    if (2 == $this->api_version)
    {

      $veritrans_notification = new VeritransNotification();

      if (in_array($veritrans_notification->status_code, array(200, 201, 202)))
      {

        $veritrans = new Veritrans();
        $veritrans->server_key = $this->server_key;
        $veritrans_confirmation = $veritrans->confirm($veritrans_notification->order_id);

        if ($veritrans_confirmation)
        {
          $order = new WC_Order( $veritrans_confirmation['order_id'] );
          if ($veritrans_confirmation['transaction_status'] == 'capture')
          {
            $order->payment_complete();
            $order->reduce_order_stock();
          } else if ($veritrans_confirmation['transaction_status'] == 'challenge') 
          {
            $order->update_status('on-hold');
          } else if ($veritrans_confirmation['transaction_status'] == 'deny')
          {
            $order->update_status('failed');
          }
          $woocommerce->cart->empty_cart();
        }
        
      }

    } else
    {
      if( $params ) {

        if( ('' != $params['orderId']) && ('success' == $params['mStatus']) ){
          $token_merchant = get_post_meta( $params['orderId'], '_token_merchant', true );
          
          $this->log->add('veritrans', 'Receiving notif for order with ID: ' . $params['orderId']);
          $this->log->add('veritrans', 'Matching token merchant: ' . $token_merchant . ' = ' . $params['TOKEN_MERCHANT'] );

          if( $params['TOKEN_MERCHANT'] == $token_merchant ) {
            header( 'HTTP/1.1 200 OK' );
            $this->log->add( 'veritrans', 'Token Merchant match' );
            do_action( "valid-veritrans-web-request", $params );
          }
        }

        elseif( 'failure' == $params['mStatus'] ) {
          global $woocommerce;
          // Remove cart
          $woocommerce->cart->empty_cart();
        }

        else {
          wp_die( "Veritrans Request Failure" );
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

