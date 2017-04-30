<?php
/*
* Check whether a discount code is valid. Used during registration to validate a discount code on the fly
* @param - string $code - the discount code to validate
* return none
*/
function rcp_validate_discount_with_ajax() {
	if( isset( $_POST['code'] ) ) {

		$return          = array();
		$return['valid'] = false;
		$return['full']  = false;
		$subscription_id = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;


		if( rcp_validate_discount( $_POST['code'], $subscription_id ) ) {
		
			$code_details = rcp_get_discount_details_by_code( sanitize_text_field( $_POST['code'] ) );
		
			if( $code_details && $code_details->amount == 100 && $code_details->unit == '%' ) {
				// this is a 100% discount
				
				$return['full']   = true;
	
			}

			$return['valid']  = true;
			$return['amount'] = rcp_discount_sign_filter( $code_details->amount, $code_details->unit );

		}

		wp_send_json( $return );
	}
	die();
}
add_action( 'wp_ajax_validate_discount', 'rcp_validate_discount_with_ajax' );
add_action( 'wp_ajax_nopriv_validate_discount', 'rcp_validate_discount_with_ajax' );

/**
 * Calls the load_fields() method for gateways when a gateway selection is made
 *
 * @access      public
 * @since       2.1
 */
function rcp_load_gateway_fields() {

	$gateways = new RCP_Payment_Gateways;
	$gateways->load_fields();
	die();
}
add_action( 'wp_ajax_rcp_load_gateway_fields', 'rcp_load_gateway_fields' );
add_action( 'wp_ajax_nopriv_rcp_load_gateway_fields', 'rcp_load_gateway_fields' );