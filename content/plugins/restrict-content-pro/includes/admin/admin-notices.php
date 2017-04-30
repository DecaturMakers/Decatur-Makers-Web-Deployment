<?php

function rcp_admin_notices() {
	global $rcp_options;

	$message = ! empty( $_GET['rcp_message'] ) ? urldecode( $_GET['rcp_message'] ) : false;
	$class   = 'updated';
	$text    = '';

	// only show notice if settings have never been saved
	if( ! is_array( $rcp_options ) || empty( $rcp_options ) ) {
		echo '<div class="notice notice-info"><p><a href="' . admin_url( "admin.php?page=rcp-settings" ) . '">' . __( 'You should now configure your Restrict Content Pro settings', 'rcp' ) . '</a></p></div>';
	}

	if( rcp_check_if_upgrade_needed() ) {
		echo '<div class="error"><p>' . __( 'The Restrict Content Pro database needs to be updated: ', 'rcp' ) . ' ' . '<a href="' . esc_url( add_query_arg( 'rcp-action', 'upgrade', admin_url() ) ) . '">' . __( 'upgrade now', 'rcp' ) . '</a></p></div>';
	}

	if( isset( $_GET['rcp-db'] ) && $_GET['rcp-db'] == 'updated' ) {
		echo '<div class="updated fade"><p>' . __( 'The Restrict Content Pro database has been updated', 'rcp' ) . '</p></div>';
	}

	if ( 'expired' === rcp_check_license() ) {
		echo '<div class="error info"><p>' . __( 'Your license key for Restrict Content Pro has expired. Please renew your license to re-enable automatic updates.', 'rcp' ) . '</p></div>';
	} elseif ( 'valid' !== rcp_check_license() ) {
		echo '<div class="notice notice-info"><p>' . sprintf( __( 'Please <a href="%s">enter and activate</a> your license key for Restrict Content Pro to enable automatic updates.', 'rcp' ), admin_url( 'admin.php?page=rcp-settings' ) ) . '</p></div>';
	}

	if( function_exists( 'rcp_register_stripe_gateway' ) ) {
		$deactivate_url = add_query_arg( array( 's' => 'restrict+content+pro+-+stripe' ), admin_url( 'plugins.php' ) );
		echo '<div class="error"><p>' . sprintf( __( 'You are using an outdated version of the Stripe integration for Restrict Content Pro. Please <a href="%s">deactivate</a> the add-on version to configure the new version.', 'rcp' ), $deactivate_url ) . '</p></div>';
	}

	if( function_exists( 'rcp_register_paypal_pro_express_gateway' ) ) {
		$deactivate_url = add_query_arg( array( 's' => 'restrict+content+pro+-+paypal+pro' ), admin_url( 'plugins.php' ) );
		echo '<div class="error"><p>' . sprintf( __( 'You are using an outdated version of the PayPal Pro / Express integration for Restrict Content Pro. Please <a href="%s">deactivate</a> the add-on version to configure the new version.', 'rcp' ), $deactivate_url ) . '</p></div>';
	}

	switch( $message ) :

		case 'payment_deleted' :

			$text = __( 'Payment deleted', 'rcp' );
			break;

		case 'payment_added' :

			$text = __( 'Payment added', 'rcp' );
			break;

		case 'payment_not_added' :

			$text = __( 'Payment creation failed', 'rcp' );
			$class = 'error';
			break;

		case 'payment_updated' :

			$text = __( 'Payment updated', 'rcp' );
			break;

		case 'payment_not_updated' :

			$text = __( 'Payment update failed', 'rcp' );
			break;

		case 'upgrade-complete' :

			$text =  __( 'Database upgrade complete', 'rcp' );
			break;

		case 'user_added' :

			$text = __( 'The user\'s subscription has been added', 'rcp' );
			break;

		case 'user_not_added' :

			$text = __( 'The user\'s subscription could not be added', 'rcp' );
			$class = 'error';
			break;

		case 'user_updated' :

			$text = __( 'Member updated' );
			break;

		case 'member_cancelled' :

			$text = __( 'Member\'s payment profile cancelled successfully', 'rcp' );
			break;

		case 'level_added' :

			$text = __( 'Subscription level added', 'rcp' );
			break;

		case 'level_updated' :

			$text = __( 'Subscription level updated', 'rcp' );
			break;

		case 'level_not_added' :

			$text = __( 'Subscription level could not be added', 'rcp' );
			$class = 'error';
			break;

		case 'level_not_updated' :

			$text = __( 'Subscription level could not be updated', 'rcp' );
			$class = 'error';
			break;

		case 'discount_added' :

			$text = __( 'Discount code created', 'rcp' );
			break;

		case 'discount_not_added' :

			$text = __( 'The discount code could not be created due to an error', 'rcp' );
			$class = 'error';
			break;

	endswitch;

	if( $message )
		echo '<div class="' . $class . '"><p>' . $text . '</p></div>';

}
add_action( 'admin_notices', 'rcp_admin_notices' );
