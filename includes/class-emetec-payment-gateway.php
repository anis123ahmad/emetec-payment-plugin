<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Emetec Payment Gateway Class
 */
class Emetec_Payment_Gateway_EPG extends WC_Payment_Gateway
{
    
	public bool $isTest;
    public string $debug;
    public string $entityId;
    public string $token;
    
    /**
     * Class constructor for the gateway
     */
    public function __construct()
    {
        
		$this->id = 'emetec'; 			// Payment gateway ID
        $this->icon = ''; 				// URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; 		// True if you need a custom credit card form
        $this->method_title = 'Emetec Payment Gateway'; // Title of the payment method shown on the admin page
        $this->method_description = 'Allows Payments with Emetec Payment Gateway.'; // Description for the payment method shown on the admin page

        $this->supports = array(
            'products'
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option( 'enabled' );
        $this->debug = $this->get_option( 'debug' );
        $this->isTest = (bool)$this->get_option( 'environment' );

        if ( $this->isTest ){
            $this->entityId = $this->get_option('sandbox_entityId');
            $this->token = $this->get_option('sandbox_token');
        }else{
            $this->entityId = $this->get_option('entityId');
            $this->token = $this->get_option('token');
        }

        $this->instructions	= "";
		
		// Actions
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_emetec', array( $this, 'confirmation_ipn' ) );
		add_action( 'woocommerce_receipt_'. $this->id, array(&$this, 'receipt_page') );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		
    
	}//end of constructor
	
	
	
	public function receipt_page($order)
    {
         
		 global $woocommerce;
	     $order = new WC_Order($order);
		 
		 $current_version = get_option( 'woocommerce_version', null );
	
		 
		 if (version_compare( $current_version, '3.0.0', '<' )) {
             $order->reduce_order_stock();
          } else {
             wc_reduce_stock_levels( $order->get_id() );
          }
		
		//Remove Products from cart
		 //WC()->cart->empty_cart();
		 
		 $order->update_status('pending');
		 
		 $order->add_order_note('Emetec Cridit/Debit Card Payment Tried<br/>By : '.$order->get_billing_first_name() );
		 
		 echo '<p>'.__('Thank you for your order, please click the button below to pay with Emetec Payment using Card Method. Do not close this window (or click the back button). You will be redirected back to the website once your payment has been received.', 'emetec-payment-plugin').'</p>';
         
		 echo $this->generate_emetec_form($order);
    
	} //end of function
      
	
	public function generate_emetec_form($order_id)
    {
         
		 global $woocommerce;
	     //$order         = new WC_Order($order_id);
         
		$responseData = $this->request( $order_id );
		
		$arr = json_decode( $responseData, 1 );
		
		
		$buildNumber = $arr['buildNumber'];
		$timestamp = $arr['timestamp'];
		$ndc = $arr['ndc'];
		$id = $arr['id'];
        		
		//$return_url = $this->return_url."/wc-api/convergehpp/";
		$processURI = site_url() . '/wc-api/emetec';
		
		$html_form = "
			<form action='" . $processURI . "' class='paymentWidgets'>
            	AMEX MASTER VISA
        	</form>
			<script src='https://test.emetec.pro/v1/paymentWidgets.js?checkoutId=$id'></script>

		"; 
		
		return $html_form;
		
    }//end of function


	/**
	* Output for the order received page.
	*/
		
	public function thankyou_page() {
		if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
	}//end of function
	
    
	public function emetec_enqueue_scripts() {
    
    /*
	wp_enqueue_script( 'custom-gallery', plugins_url( '/js/gallery.js' , __FILE__ ), array( 'jquery' ), '1.0', true );
    wp_enqueue_script( 'custom-gallery-lightbox', plugins_url( '/js/gallery-lightbox.js' , __FILE__ ), array( 'custom-gallery', 'jquery' ), '1.0', true )
	*/
	// Example 4: External script.
  // Localise the data, specifying our registered script and a global variable name to be used in the script tag
  
  //print_r ( $GLOBALS );
  
wp_enqueue_script( 'emetec-script-external', "https://test.emetec.pro/v1/paymentWidgets.js?checkoutId=" . $GLOBALS['id'], array(''), '' );
	
	
	}
	
	
	/**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = require( dirname( __FILE__ ) . '/admin/settings.php' );
    }

    public function admin_options()
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    public function is_available()
    {
        return parent::is_available() &&
            !empty($this->entityId) &&
            !empty($this->token);
    }

    
	public function request($order_id) {
		
        global $current_user;
		global $woocommerce;
		global $wpdb;
		global $wp;

		$order = wc_get_order($order_id);

		$currency = get_option('woocommerce_currency');
		$merchantTransactionId = $order->get_id();	
		$customerGivenName = $order->get_billing_first_name();			//'Anis';	
		$customerSurname = $order->get_billing_first_name();			//'Ahmad';	
		$customerMobile = $order->get_billing_phone();					//'9758456789';	
		$customerEmail = $order->get_billing_email();					//'tester.byte@gmail.com';	
		$customerIP = WC_Geolocation::get_ip_address(); // CORRECT		//'127.0.0.3';	
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

		$customerBrowserLanguage = strtoupper($lang);					//'EN';	
		$customerBrowserTimezone = 'EN';	
		$customerBrowserUserAgent = $_SERVER['HTTP_USER_AGENT'];		//'EN';	
	
		
		if ( $this->isTest ){
            
			$url = "https://test.emetec.pro/v1/checkouts";
            $token = $this->token;
        }else{
            
			$url = "https://emetec.pro/v1/checkouts";
			$token = $this->token;
        }

		
		
		$data = "entityId=" . $this->entityId .
                "&amount=" . (float) $order->order_total .
                "&currency=" . $currency .
                
				"&merchantTransactionId=$merchantTransactionId" .
				"&transactionCategory=EC" .			/* EC - eCommerce, MO - Mail order, TO - Telephone order, PO - pos, PM - mpos
    													MOTO - Mail order Telephone order */
				
				"&customer.givenName=$customerGivenName" .
				"&customer.surname=$customerSurname" .
				"&customer.mobile=$customerMobile" .
				"&customer.email=$customerEmail" .
				"&customer.ip=$customerIP" .
				"&customer.status=NEW" .								//NEW, EXISTING.
				"&customer.language=" . strtoupper($lang) .				// EN
				"&customer.category=INDIVIDUAL" .						//Valid options are: INDIVIDUAL, COMPANY
				//"&customer.browser.language=$customerBrowserLanguage" .
				//"&customer.browser.timezone=$customerBrowserTimezone" .
				//"&customer.browser.userAgent=$customerBrowserUserAgent" .
                				
				"&paymentType=DB";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                   'Authorization:Bearer ' . $token ) );
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$responseData = curl_exec($ch);
		
		if(curl_errno($ch)) {
		return curl_error($ch);
		}
		curl_close($ch);

		
		return $responseData;
	
	}//end of function

	
	/**
     * Process the payment and return the result.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        
		
		$order = wc_get_order($order_id);
         
		 return array(
         				'result' 	=> 'success',
         				'redirect'	=> $order->get_checkout_payment_url( true )
         	);
			
        return parent::process_payment($order_id);
    
	
	}

    
	/**
     * Output for the order received page.
     * no es parte del request... 
     */
    public function payment_fields()
    {
        ?><b>Credit Card details must be provided in the next screen.</b><br />
        <form action="<?php echo site_url(); ?>/wc-api/emetec" class="paymentWidgets">
            AMEX MASTER VISA
        </form>
        <?php

    }

    
	public function confirmation_ipn()
    {
        
		global $woocommerce;
		
		$id = $_REQUEST['id'];
		$resourcePath = $_REQUEST['resourcePath'];
		
		if ( $this->isTest ){
			$url = "https://test.emetec.pro/v1/checkouts/$id/payment";
			$url .= "?entityId=" . $this->entityId;
			$token = $this->token;
        }else{
			$url = "https://emetec.pro/v1/checkouts/$id/payment";
			$url .= "?entityId=" . $this->entityId;
			$token = $this->token;
        }
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					   'Authorization:Bearer ' . $token ));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$responseData = curl_exec($ch);
		if(curl_errno($ch)) {
			return curl_error($ch);
		}
		curl_close($ch);
		
		$arr = ( json_decode( $responseData, 1 ) );
		$id = $arr['id'];
		$paymentType = $arr['paymentType'];
		$paymentBrand = $arr['paymentBrand'];
		
		if( array_key_exists( 'descriptor', $arr ) )
			$descriptor = $arr['descriptor'];
		
		if( array_key_exists( 'amount', $arr ) )
			$amount = $arr['amount'];

		//$amount = $arr['amount'];
		$order_id = $arr['merchantTransactionId'];
			
		$order = wc_get_order($order_id);

		//Result
		$paymentDescription = $arr['result']['description'];

		//Result Details
		if( array_key_exists( 'resultDetails', $arr ) ) {
		
			$paymentResultDetailsStatus = $arr['resultDetails']['Status'];
			$paymentResultDetailsResponseCode = $arr['resultDetails']['ResponseCode'];
			$paymentResultDetailsApprovalCode = $arr['resultDetails']['ApprovalCode'];
			$paymentResultDetailsTransactionId = $arr['resultDetails']['TransactionId'];
			
			$paymentResultDetailsExtendedDescription = $arr['resultDetails']['ExtendedDescription'];
			$paymentResultDetailsReconciliationId = $arr['resultDetails']['reconciliationId'];
			$paymentResultDetailsNetworkTransactionId = $arr['resultDetails']['NetworkTransactionId'];
			$paymentResultDetailsClearingInstituteName = $arr['resultDetails']['clearingInstituteName'];
			
		}//end of If block

		//Card Dettals
		$prdLast4Digits = $arr['card']['last4Digits'];
		$prdHolder = $arr['card']['holder'];
		$prdType = $arr['card']['type'];
		$prdCountry = $arr['card']['country'];
		$prdMaxPanLength = $arr['card']['maxPanLength'];

		if( array_key_exists( 'issuer', $arr ) )
			$cardIssuerBank = $arr['card']['issuer']['bank'];

		if( $paymentResultDetailsStatus == 'AUTHORIZED' && $paymentResultDetailsResponseCode == 100 ) {
	
			$order->add_order_note('Emetec Card Payment is successful.<br/>
			Txn ID: '.$paymentResultDetailsTransactionId . '<br />Card Last 4 Digit: '.$prdLast4Digits . 
			'<br />Card Type: ' . $prdType );
                
			//$order->update_status('completed');
				 
			update_post_meta( $order->get_id(), '_emetec_txn_id',  $paymentResultDetailsTransactionId );
			update_post_meta( $order->get_id(), '_emetec_reconciliation_id',  $paymentResultDetailsReconciliationId );
				
			$order->update_status('processing');
			$transaction_id = $paymentResultDetailsTransactionId;
			$order->set_transaction_id( $transaction_id );
			$order->save();
	
			$redirect_url = $order->get_checkout_order_received_url();
            
			wp_safe_redirect( $redirect_url );
				
				
			exit;
			
		} else {
			
			$order->update_status('failed');
			$order->add_order_note('Emetech Card Payment failed.<br/>' );
                
			$msg = $_POST['ssl_result_message'];
			$redirect_url = $order->get_checkout_order_received_url();
            //$this->web_redirect( $redirect_url . "?msg=$msg" );
            wc_add_notice( __( 'Emetec payment failed.', 'emtec-payment-gateway' ), 'error' );
			wp_safe_redirect( $redirect_url );
			
			exit;
			
			}
		
		
    }//end of function


}//end of class
