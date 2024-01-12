<?php

wc_enqueue_js( "
    jQuery( function( $ ) {
	
	let emetec_payment_gateway_fields = '#woocommerce_emetec_entityId, #woocommerce_emetec_token';
	
	let emetec_payment_gateway_sandbox_fields = '#woocommerce_emetec_sandbox_entityId, #woocommerce_emetec_sandbox_token';

	$( '#woocommerce_emetec_environment' ).change(function(){

		$( emetec_payment_gateway_sandbox_fields + ',' + emetec_payment_gateway_fields ).closest( 'tr' ).hide();

		if ( '0' === $( this ).val() ) {
			$( emetec_payment_gateway_fields ).closest( 'tr' ).show();
			
		}else{
		   $( emetec_payment_gateway_sandbox_fields ).closest( 'tr' ).show();
		}
	}).change();
});	
");


return apply_filters(
    
	'emetec_payment_gateway_epg_settings',
    
	array(
        
		'enabled' => array(
            'title' => 'Enable/Disable',
            'type' => 'checkbox',
            'label' => 'Enable Emetec Payment Gateway',
            'default' => 'no'
        ),
        
		'title' => array(
            'title' => 'Title',
            'type' => 'text',
            'description' => 'This controls the title which the user sees during checkout.',
            'default' => 'Emetec Payment',
            'desc_tip' => true,
        ),
        
		'description' => array(
            'title' => 'Description',
            'type' => 'textarea',
            'description' => 'This controls the description which the user sees during checkout.',
            'default' => 'Pay with your credit card via Emetec Payment Gateway.',
        ),
        
		'debug' => array(
            'title' => __('Debug'),
            'type' => 'checkbox',
            'label' => __('Debug records, it is saved in payment log'),
            'default' => 'no'
        ),
        
		'environment' => array(
            'title' => __('Environment'),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'description' => __('Emetec environment by default Test'),
            'desc_tip' => true,
            'default' => true,
            'options'     => array(
                false    => __( 'Live' ),
                true => __( 'Test' ),
            )
        ),
        
		'entityId' => array(
            'title' => __( 'Live EntityId' ),
            'type'  => 'text',
            'description' => __( 'EntityId environment Live/Production' ),
            'desc_tip' => true
        ),
        
		'sandbox_entityId' => array(
            'title' => __( 'Sandbox EntityId' ),
            'type'  => 'text',
            'description' => __( 'EntityId environment Test/Sandbox' ),
            'desc_tip' => true
        ),
        
		'token' => array(
            'title' => __( 'Live/Production Token' ),
            'type'  => 'password',
            'style'  => 'width: 350px;',
			'description' => __( 'Token environment Live/Production' ),
            'desc_tip' => true
        ),
        
		'sandbox_token' => array(
            'title' => __( 'Test/Sandbox Token' ),
            'type'  => 'password',
			'class' => 'wc-enhanced-text',
			'description' => __( 'Token environment Test/Sandbox' ),
            'desc_tip' => true
        ),

    )

);
