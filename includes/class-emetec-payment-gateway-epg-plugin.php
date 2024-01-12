<?php

class Emetec_Payment_Gateway_EPG_Plugin
{
    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public $plugin_path;
    /**
     * Absolute path to plugin lib dir
     *
     * @var string
     */
    public $lib_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public $plugin_url;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;
    /**
     * @var WC_Logger
     */
    public $logger;
    /**
     * @var bool
     */
    private $_bootstrapped = false;

    public function __construct($file, $version)
    {
        
		$this->file = $file;
        $this->version = $version;
        // Path.
        $this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
        $this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );
        $this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
        $this->lib_path = $this->plugin_path . trailingslashit( 'lib' );
        $this->logger = new WC_Logger();
    
	}

    
	public function run_emetec()
    {
    
	    try{
            if ($this->_bootstrapped){
                throw new Exception( __( 'Emetec Payment Gateway for WooCommerce can only be called once'));
            }
            $this->_run();
            $this->_bootstrapped = true;
        }catch (Exception $e){
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                emetec_payment_gateway_epg_notices('Emetec Payment Gateway for WooCommerce: ' . $e->getMessage());
            }
        }
    
	}

    
	protected function _run()
    {
        
		if ( !class_exists( '\Emetect\Client' ) )
            require_once ( $this->lib_path . 'Client.php' );
        	require_once ( $this->includes_path . 'class-emetec-payment-gateway.php' );

        add_filter( 'woocommerce_payment_gateways', array($this, 'emetec_payment_gateway_epg_add_gateway'));
        //add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    
	}


    public function emetec_payment_gateway_epg_add_gateway($methods)
    {
        $methods[] = 'Emetec_Payment_Gateway_EPG';
        return $methods;
    }

    public function enqueue_scripts()
    {
        
		$gateways = WC()->payment_gateways->get_available_payment_gateways();
        
		if( isset( $gateways['emetec'] ) && $gateways['emetec']->enabled === 'yes' && is_checkout() ){
            
			$emetec = new \Emetect\Client( $gateways['emetec']->entityId, $gateways['emetec']->token );
            $emetec->sandboxMode( $gateways['emetec']->isTest );

            $res = $emetec->createRegistration();
           // print_r ( $res );
			$checkoutId = $res ? $res['id'] : '';

            //wp_enqueue_script( 'emetec-payment', $this->plugin_url . 'assets/js/emetec-payment.js', array( 'jquery' ), $this->version, ['in_footer' => true] );
            
			/*
			wp_localize_script( 'emetec-payment', 'emetec_checkout', array(
                'srcPaymentWidget' => "{$this->get_url_enviroment()}paymentWidgets.js?checkoutId=$checkoutId/registration",
                'checkoutId' => $checkoutId,
                'idPayment' => $gateways['emetec']->id
            ));
           */
		}
    }

    protected  function get_url_enviroment()
    {
        $wc_main_settings = get_option('woocommerce_emetec_settings');

       return $wc_main_settings['environment'] === '1' ? 'https://test.emetec.pro/v1/' : 'https://emetec.pro/v1/';
    }

    public function log($message)
    {
        if (is_array($message) || is_object($message))
            $message = print_r($message, true);
        $logger = new WC_Logger();
        $logger->add('emetec-payment-gateway-epg', $message);
    }

}//end of class
