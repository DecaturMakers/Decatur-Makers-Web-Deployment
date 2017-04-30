<?php

/**
 * Stores error messages
 *
 * @access      public
 * @since       1.0
 */
function rcp_errors() {
    static $wp_error; // Will hold global variable safely
    return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error( null, null, null ) );
}

/**
 * Retrieves the HTML for error messages
 *
 * @access      public
 * @since       2.1
 */
function rcp_get_error_messages_html( $error_id = '' ) {

	$html   = '';
	$errors = rcp_errors()->get_error_codes();

	if( $errors ) {
		
		$html .= '<div class="rcp_message error">';
		// Loop error codes and display errors
		foreach( $errors as $code ) {

			if( rcp_errors()->get_error_data( $code ) == $error_id ) {

				$message = rcp_errors()->get_error_message( $code );

				$html .= '<p class="rcp_error ' . esc_attr( $code ) . '"><span>' . $message . '</span></p>';

			}

		}

		$html .= '</div>';

	}

	return apply_filters( 'rcp_error_messages_html', $html, $errors );

}

/**
 * Displays the HTML for error messages
 *
 * @access      public
 * @since       1.0
 */
function rcp_show_error_messages( $error_id = '' ) {
	if( rcp_errors()->get_error_codes() ) {
		do_action( 'rcp_errors_before' );
		echo rcp_get_error_messages_html( $error_id );
		do_action( 'rcp_errors_after' );
	}
}