<?php


if( ! function_exists( 'recaptcha_get_html' ) ) {
	require_once( RCP_PLUGIN_DIR . 'includes/libraries/recaptchalib.php' );
}

function rcp_show_captcha() {
	global $rcp_options;
	// reCaptcha
	if( isset( $rcp_options['enable_recaptcha'] ) && ! empty( $rcp_options['recaptcha_public_key'] ) ) {
		$publickey = trim( $rcp_options['recaptcha_public_key'] );
		echo '<script type="text/javascript"> var RecaptchaOptions = { theme : "' . esc_attr( $rcp_options['recaptcha_style'] ) . '" };</script>';
		echo '<p id="rcp_recaptcha">' . recaptcha_get_html( $publickey, null, is_ssl() ) . '</p>';
	}
}
add_action( 'rcp_before_registration_submit_field', 'rcp_show_captcha', 100 );


function rcp_validate_captcha() {
	global $rcp_options;
	if( isset( $rcp_options['enable_recaptcha'] ) && ! empty( $rcp_options['recaptcha_public_key'] ) ) {
		/* validate recaptcha, if enabled */
		$privatekey = trim( $rcp_options['recaptcha_private_key'] );
		$resp = recaptcha_check_answer(
			$privatekey,
			$_SERVER["REMOTE_ADDR"],
			$_POST["recaptcha_challenge_field"],
			$_POST["recaptcha_response_field"]
		);
		if ( !$resp->is_valid ) {
			// recaptcha is incorrect
			rcp_errors()->add( 'invalid_recaptcha', __( 'The words/numbers you entered did not match the reCaptcha', 'rcp' ) );
		}
	}
}
add_action( 'rcp_form_errors', 'rcp_validate_captcha' );