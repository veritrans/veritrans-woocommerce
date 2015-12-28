<?php
## TODO
# empty cart should be after notification or redirection
    require_once(dirname(__FILE__) . '/../lib/veritrans/Veritrans.php');

    /**
       * Veritrans Payment Gateway Class
       */
    class WC_Gateway_Veritrans_Installment extends WC_Payment_Gateway {

      /**
       * Constructor
       */
      function __construct() {
        $this->id           = 'veritrans_installment';
        $this->icon         = apply_filters( 'woocommerce_veritrans_icon', '' );
        $this->method_title = __( 'Veritrans Installment', 'colabsthemes' );
        $this->has_fields   = true;
        $this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Veritrans_Installment', home_url( '/' ) ) );

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Get Settings
        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        
        $this->server_key_v2_sandbox         = $this->get_option( 'server_key_v2_sandbox' );
        $this->server_key_v2_production         = $this->get_option( 'server_key_v2_production' );
        $this->api_version        = 2;
        $this->environment        = $this->get_option( 'select_veritrans_environment' );
        $this->to_idr_rate        = $this->get_option( 'to_idr_rate' );
        $this->enable_sanitization = $this->get_option( 'enable_sanitization' );
        $this->enable_bni         = $this->get_option( 'enable_bni_installment' );
        $this->bni_terms          = $this->get_option( 'enable_bni_installment_terms' );
        $this->enable_mandiri     = $this->get_option( 'enable_mandiri_installment' );
        $this->mandiri_terms      = $this->get_option( 'enable_mandiri_installment_terms' );
        $this->bin_filter         = $this->get_option( 'enable_bin_filter' );


        $this->log = new WC_Logger();

        // Payment listener/API hook
        //add_action( 'woocommerce_api_wc_gateway_veritrans', array( &$this, 'veritrans_vtweb_response' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); 
        add_action( 'wp_enqueue_scripts', array( &$this, 'veritrans_scripts' ) );
        add_action( 'admin_print_scripts-woocommerce_page_woocommerce_settings', array( &$this, 'veritrans_admin_scripts' ));
        add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'veritrans_admin_scripts' ));
        add_action( 'valid-veritrans-web-request', array( $this, 'successful_request' ) );
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
        <h3><?php _e( 'Veritrans Installment', 'woocommerce' ); ?></h3>
        <p><?php _e('Allows installment payments using Veritrans.', 'woocommerce' ); ?></p>
        <table class="form-table">
          <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
          ?>
        </table><!--/.form-table-->
        <?php
      }

      /**
       * Initialise Gateway Settings Form Fields
       * Method ini digunakan untuk mengatur halaman konfigurasi admin
       */
      function init_form_fields() {
        
        $v2_sandbox_key_url = 'https://my.sandbox.veritrans.co.id/settings/config_info';
        $v2_production_key_url = 'https://my.veritrans.co.id/settings/config_info';

        $this->form_fields = array(
          'enabled' => array(
            'title' => __( 'Enable/Disable', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable Veritrans installment Payment', 'woocommerce' ),
            'default' => 'yes'
          ),
          'title' => array(
            'title' => __( 'Title', 'woocommerce' ),
            'type' => 'text',
            'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
            'default' => __( 'Credit Card installment', 'woocommerce' ),
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
          'server_key_v2_sandbox' => array(
            'title' => __("Server Key", 'woocommerce'),
            'type' => 'text',
            'description' => sprintf(__('Input your <b>Sandbox</b> Veritrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_sandbox_key_url),
            'default' => '',
            'class' => 'sandbox_settings sensitive'
          ),
          'server_key_v2_production' => array(
            'title' => __("Server Key", 'woocommerce'),
            'type' => 'text',
            'description' => sprintf(__('Input your <b>Production</b> Veritrans Server Key. Get the key <a href="%s" target="_blank">here</a>', 'woocommerce' ),$v2_production_key_url),
            'default' => '',
            'class' => 'production_settings sensitive'
          ),
          'enable_bni_installment' => array(
            'title' => __( 'Enable BNI installment ', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable BNI installment?', 'woocommerce' ),
            'description' => __( 'Please contact us if you wish to enable this feature in the Production environment.', 'woocommerce' ),
            'default' => 'no'
          ),
          'enable_bni_installment_terms' => array(
            'title' => __( 'BNI Installment terms', 'woocommerce' ),
            'type' => 'text',
            'label' => __( 'BNI Installment terms', 'woocommerce' ),
            'description' => __( 'use comma to separate value. Only 6 and 12 months term available on sandbox environment.', 'woocommerce' ),
            'default' => '6,12'
          ),
          'enable_mandiri_installment' => array(
            'title' => __( 'Enable Mandiri installment ', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable Mandiri installment?', 'woocommerce' ),
            'description' => __( 'Please contact us if you wish to enable this feature in the Production environment.', 'woocommerce' ),
            'default' => 'no'
          ),
          'enable_mandiri_installment_terms' => array(
            'title' => __( 'Mandiri Installment terms', 'woocommerce'),
            'type' => 'text',
            'label' => __( 'Mandiri Installment terms', 'woocommerce' ),
            'description' => __( 'use comma to separate value. Only 6 and 12 months term available on sandbox environment.', 'woocommerce' ),
            'default' => '6,12'
          ),
          'enable_bin_filter' => array(
            'title' => __( 'Bin Number', 'woocommerce'),
            'type' => 'text',
            'label' => __( 'Bin Number', 'woocommerce' ),
            'description' => __( 'Please contact us if you wish to enable this feature in the Production environment.', 'woocommerce' ),
            'default' => '4,5'
          ),
          'enable_sanitization' => array(
            'title' => __( 'Enable Sanitization', 'woocommerce' ),
            'type' => 'checkbox',
            'label' => __( 'Enable Sanitization?', 'woocommerce' ),
            'default' => 'yes'
          )
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
       * Method ini akan dipanggil ketika customer akan melakukan pembayaran
       * Return value dari method ini adalah link yang akan digunakan untuk
       * me-redirect customer ke halaman pembayaran Veritrans
       */
      function process_payment( $order_id ) {
        global $woocommerce;

        return array(
          'result'  => 'success',
          'redirect' => $this->charge_payment( $order_id )
        );
      }

      /**
       * Charge Payment
       * Method ini digunakan untuk mendapatkan link halaman pembayaran Veritrans
       * dengan mengirimkan JSON yang berisi data transaksi
       */
      function charge_payment( $order_id ) {
        global $woocommerce;
        $order_items = array();
        $cart = $woocommerce->cart;

        $order = new WC_Order( $order_id );     
      
        Veritrans_Config::$isProduction = ($this->environment == 'production') ? true : false;
        Veritrans_Config::$serverKey = (Veritrans_Config::$isProduction) ? $this->server_key_v2_production : $this->server_key_v2_sandbox;     
        Veritrans_Config::$is3ds = true;
        Veritrans_Config::$isSanitized = ($this->enable_sanitization == 'yes') ? true : false;
        
        $params = array(
          'transaction_details' => array(
            'order_id' => $order_id,
            'gross_amount' => 0,
          ),
          'vtweb' => array()
        );

        $enabled_payments = array();
        if ($this->enable_credit_card == 'yes'){
          $enabled_payments[] = 'credit_card';
        }

        $params['vtweb']['enabled_payments'] = 'credit_card';
        $bins = $this->bin_filter;
        
        $bins = explode(',', $bins);
        $params['vtweb']['credit_card_bins'] = $bins;


        $customer_details = array();
        $customer_details['first_name'] = $order->billing_first_name;
        $customer_details['last_name'] = $order->billing_last_name;
        $customer_details['email'] = $order->billing_email;
        $customer_details['phone'] = $order->billing_phone;

        $billing_address = array();
        $billing_address['first_name'] = $order->billing_first_name;
        $billing_address['last_name'] = $order->billing_last_name;
        $billing_address['address'] = $order->billing_address_1;
        $billing_address['city'] = $order->billing_city;
        $billing_address['postal_code'] = $order->billing_postcode;
        $billing_address['phone'] = $order->billing_phone;
        $billing_address['country_code'] = (strlen($this->convert_country_code($order->billing_country) != 3 ) ? 'IDN' : $this->convert_country_code($order->billing_country) );

        $customer_details['billing_address'] = $billing_address;
        $customer_details['shipping_address'] = $billing_address;
        
        if ( isset ( $_POST['ship_to_different_address'] ) ) {
          $shipping_address = array();
          $shipping_address['first_name'] = $order->shipping_first_name;
          $shipping_address['last_name'] = $order->shipping_last_name;
          $shipping_address['address'] = $order->shipping_address_1;
          $shipping_address['city'] = $order->shipping_city;
          $shipping_address['postal_code'] = $order->shipping_postcode;
          $shipping_address['phone'] = $order->billing_phone;
          $shipping_address['country_code'] = (strlen($this->convert_country_code($order->shipping_country) != 3 ) ? 'IDN' : $this->convert_country_code($order->billing_country) );
          
          $customer_details['shipping_address'] = $shipping_address;
        }
        
        $params['customer_details'] = $customer_details;
        //error_log(print_r($params,true));

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

        // Discount
        if ( $cart->get_cart_discount_total() > 0) {
          $items[] = array(
            'id' => 'totaldiscount',
            'price' => $cart->get_cart_discount_total() * -1,
            'quantity' => 1,
            'name' => 'Total Discount'
          );
        }

        // Fees
        if ( sizeof( $order->get_fees() ) > 0 ) {
          $fees = $order->get_fees();
          $i = 0;
          foreach( $fees as $item ) {
            $items[] = array(
              'id' => 'itemfee' . $i,
              'price' => $item['line_total'],
              'quantity' => 1,
              'name' => $item['name'],
            );
            $i++;
          }
        }

        $params['transaction_details']['gross_amount'] = $order->get_total();
        error_log('bni'.$this->enable_bni);
        error_log('mandiri'.$this->enable_mandiri);
        if($this->enable_bni == 'yes' || $this->enable_mandiri == 'yes')
        {  
            $payment_options = array(
            'installment' => array(
              'required' => true
              )
            );

            $installment_terms = array();

            $term_bni = $this->bni_terms;
            error_log('term bni '.$term_bni);
            $term_bni_array = explode(',' , $term_bni);
            
            if($term_bni == "yes" || $term_bni_array != null)
            {
              $installment_terms['bni'] = $term_bni_array;
            }
            
            $term_mandiri =  $this->mandiri_terms;
            error_log('term mandiri '.$term_mandiri);
            $term_mandiri_array = explode(',' , $term_mandiri);

            if($term_mandiri == "yes" || $term_mandiri_array != null)
            {
              $installment_terms['mandiri'] = $term_mandiri_array;
            }
 
           
        }        

        // sift through the entire item to ensure that currency conversion is applied
        if (get_woocommerce_currency() != 'IDR')
        {
          foreach ($items as &$item) {
            $item['price'] = $item['price'] * $this->to_idr_rate;
          }

          unset($item);

          $params['transaction_details']['gross_amount'] *= $this->to_idr_rate;
        }

        $params['item_details'] = $items;
        
        if($params['transaction_details']['gross_amount'] >= 500000)
        {
          $payment_options['installment']['installment_terms'] = $installment_terms;
          $params['vtweb']['payment_options'] = $payment_options;
        }

        $woocommerce->cart->empty_cart();
        error_log(print_r($params,TRUE));
        error_log(json_encode($params));
        return Veritrans_VtWeb::getRedirectionUrl($params);
      }

      /**
       * Check for Veritrans Web Response
       * Method ini akan dipanggil untuk merespon notifikasi yang
       * diberikan oleh server Veritrans serta melakukan verifikasi
       * apakah notifikasi tersebut berasal dari Veritrans dan melakukan
       * konfirmasi transaksi pembayaran yang dilakukan customer
       *
       * update: sekaligus untuk menjadi finish/failed URL handler.
       * @access public
       * @return void
       */


      function veritrans_vtweb_response() {

        global $woocommerce;
        @ob_clean();

        global $woocommerce;
        $order = new WC_Order( $order_id );
        
        Veritrans_Config::$isProduction = ($this->environment == 'production') ? true : false;
        
        if ($this->environment == 'production') {
          Veritrans_Config::$serverKey = $this->server_key_v2_production;
        } else {
          Veritrans_Config::$serverKey = $this->server_key_v2_sandbox;
        }
        
        // check whether the request is GET or POST, 
        // if request == GET, request is for finish OR failed URL, then redirect to WooCommerce's order complete/failed
        // else if request == POST, request is for payment notification, then update the payment status
        if(!isset($_GET['order_id'])){    // Check if POST, then create new notification
          $veritrans_notification = new Veritrans_Notification();

          if (in_array($veritrans_notification->status_code, array(200, 201, 202))) {
              header( 'HTTP/1.1 200 OK' );
            if ($order->get_order($veritrans_notification->order_id) == true) {
              $veritrans_confirmation = Veritrans_Transaction::status($veritrans_notification->order_id);             
              do_action( "valid-veritrans-web-request", $veritrans_notification );
            }
          }
        } else {    // else if GET, redirect to order complete/failed
          // error_log('status_code '. $_GET['status_code']); //debug
          // error_log('status_code '. $_GET['transaction_status']); //debug
          if( isset($_GET['order_id']) && isset($_GET['transaction_status']) && ($_GET['transaction_status'] == 'capture' || $_GET['transaction_status'] == 'pending' || $_GET['transaction_status'] == 'settlement'))  //if capture or pending or challenge or settlement, redirect to order received page
          {
            $order_id = $_GET['order_id'];
            // error_log($this->get_return_url( $order )); //debug
            $order = new WC_Order( $order_id );
            wp_redirect($order->get_checkout_order_received_url());
          }else if( isset($_GET['order_id']) && isset($_GET['transaction_status']) && $_GET['transaction_status'] == 'deny')  //if deny, redirect to order checkout page again
          {
            $order_id = $_GET['order_id'];
            $order = new WC_Order( $order_id );
            wp_redirect($order->get_checkout_payment_url(false));
          } else if( isset($_GET['order_id']) && !isset($_GET['transaction_status'])){ // if customer click "back" button, redirect to checkout page again
            $order_id = $_GET['order_id'];
            $order = new WC_Order( $order_id );
            wp_redirect($order->get_checkout_payment_url(false));
          }
        }

        }
 
      /**
       * Method ini akan dipanggil jika customer telah sukses melakukan
       * pembayaran. Method ini akan mengubah status order yang tersimpan
       * di back-end berdasarkan status pembayaran yang dilakukan customer.
       * 
       */

      function successful_request( $veritrans_notification ) {

        global $woocommerce;

        $order = new WC_Order( $veritrans_notification->order_id );
       // error_log(var_dump($order));
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
        else if ($veritrans_notification->transaction_status == 'settlement') {
          if($veritrans_notification->payment_type != 'credit_card'){
            $order->payment_complete();
          }
        }
        else if ($veritrans_notification->transaction_status == 'pending') {
          $order->update_status('on-hold');
        }

        exit;
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
          'CI' => 'CIV',
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
          'KR' => 'KOR',
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
        else{
         $country_code = ''; 
        }

        return $country_code;
      }
    }