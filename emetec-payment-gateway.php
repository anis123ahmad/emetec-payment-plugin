<?php
/**
 * Plugin Name: Emetec Payment Gateway Plugin for WooCommerce 
 * Description: Custom WooCommerce Payment Gateway Plugin using Emetec API.
 * Author: Anis
 * Version: 1.0.0
 * Author URI: https://github.com/anis123ahmad
 * Text Domain: emetec-payment-plugin
 *  License: GNU General Public License v3.0
 *  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

if(!defined('EMETEC_PAYMENT_GATEWAY_EPG_VERSION')){
    define('EMETEC_PAYMENT_GATEWAY_EPG_VERSION', '0.0.2');
}


$version = explode('.', phpversion());
define( 'WC_EMETEC_PHP', $version[0]);
define( 'WC_EMETEC_MIN_WC_VER', '5.7' );
define( 'WC_EMETEC_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_EMETEC_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WC_EMETEC_SITE_URL', get_site_url().'/' );

add_action('plugins_loaded', 'emetec_payment_gateway_epg_init');
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);

function emetec_payment_gateway_epg_init(){

    if (!requeriments_emetec_payment_gateway_epg())
        return;

    emetec_payment_gateway_epg()->run_emetec();
}//end of function

function emetec_payment_gateway_epg_notices( $notice ) {
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function requeriments_emetec_payment_gateway_epg(){

    if ( !in_array(
        'woocommerce/woocommerce.php',
        apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
        true
    ) ) {
        
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    emetec_payment_gateway_epg_notices('Emetec Payment Gateway for WooCommerce: Woocommerce must be installed and active');
                }
            );
        }
        
		return false;
    
	}

    return true;

}//end of function

function emetec_payment_gateway_epg(){
    
	static $plugin;
    
	if ( !isset( $plugin ) ){
        
		require_once( 'includes/class-emetec-payment-gateway-epg-plugin.php' );
        $plugin = new Emetec_Payment_Gateway_EPG_Plugin( __FILE__, EMETEC_PAYMENT_GATEWAY_EPG_VERSION );

    }
    
	return $plugin;
	
}//end of function
