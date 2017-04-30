<?php

class RCP_Google_Authenticator {
	
	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.2
	 */
	public function __construct() {

		if( ! class_exists( 'GoogleAuthenticator' ) ) {
			return;
		}

		add_action( 'rcp_login_form_fields_before_submit', array( $this, 'auth_code_input' ) );
		add_action( 'rcp_after_login_form_fields', array( 'GoogleAuthenticator', 'loginfooter' ) );
		add_action( 'rcp_before_form_errors', array( $this, 'check_code' ) );
	}

	/**
	 * Add the authentication code input field
	 *
	 * @access  public
	 * @since   2.2
	 */
	public function auth_code_input() {
?>
		<p>
			<label for="googleotp"><?php _e( 'Google Authenticator Code', 'rcp' ); ?></label>
			<input type="text" name="googleotp" id="googleotp" class="rcp-input" value="" size="20" style="ime-mode: inactive;" />
		</p>
<?php		
	}

	/**
	 * Verify the entered code
	 *
	 * @access  public
	 * @since   2.2
	 */
	public function check_code( $post_data ) {

		$auth = new GoogleAuthenticator;

		$user = get_user_by( 'login', trim( $_POST['rcp_user_login'] ) );

		$success = $auth->check_otp( $user, trim( $_POST['rcp_user_login'] ), trim( $_POST['rcp_user_pass'] ) );

		if( is_wp_error( $success ) ) {
			rcp_errors()->add( 'auth_failed', $success->get_error_message(), 'login' );
		}

	}

}
new RCP_Google_Authenticator;