<?php
/*
Plugin Name: Veritrans - WooCommerce Payment Gateway
Plugin URI: http://colorlabsproject.com/plugins/veritrans/
Description: Accept all payment directly on your WooCommerce site in a seamless and secure checkout environment with <a href="http://veritrans.co.id" target="_blank">Veritrans.co.id</a>
Version: 1.3.0
Author: ColorLabs & Company
Author URI: http://colorlabsproject.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
add_action( 'plugins_loaded', 'veritrans_gateway_init', 0 );

function veritrans_gateway_init() {

  if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
  }
  
  DEFINE ('VT_PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );
  
  require_once dirname( __FILE__ ) . '/class/class.veritrans-gateway.php';
  
  add_filter( 'woocommerce_payment_gateways', 'add_veritrans_payment_gateway' );
}

function add_veritrans_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Veritrans';
  return $methods;
}
