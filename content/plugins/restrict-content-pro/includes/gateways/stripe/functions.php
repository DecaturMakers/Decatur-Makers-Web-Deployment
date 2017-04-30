<?php

/**
 * Add JS to the update card form
 *
 * @access      private
 * @since       2.1
 */
function rcp_stripe_update_card_form_js() {
	global $rcp_options;

	if( ! rcp_is_gateway_enabled( 'stripe' ) ) {
		return;
	}

	if( isset( $rcp_options['sandbox'] ) ) {
		$key = $rcp_options['stripe_test_publishable'];
	} else {
		$key = $rcp_options['stripe_live_publishable'];
	}

	if( empty( $key ) ) {
		return;
	}

	wp_enqueue_script( 'stripe', 'https://js.stripe.com/v2/', array( 'jquery' ) );
?>
	<script type="text/javascript">

		function rcp_stripe_response_handler(status, response) {
			if (response.error) {

				// re-enable the submit button
				jQuery('#rcp_update_card_form #rcp_submit').attr("disabled", false);

				jQuery('#rcp_ajax_loading').hide();

				// show the errors on the form
				jQuery(".rcp_message.error").html( '<p class="rcp_error"><span>' + response.error.message + '</span></p>');

			} else {

				var form$ = jQuery("#rcp_update_card_form");
				// token contains id, last4, and card type
				var token = response['id'];
				// insert the token into the form so it gets submitted to the server
				form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");

				// and submit
				form$.get(0).submit();

			}
		}

		jQuery(document).ready(function($) {

			Stripe.setPublishableKey('<?php echo trim( $key ); ?>');

			$("#rcp_update_card_form").on('submit', function(event) {

				event.preventDefault();

				// disable the submit button to prevent repeated clicks
				$('#rcp_update_card_form #rcp_submit').attr("disabled", "disabled");

				// createToken returns immediately - the supplied callback submits the form if there are no errors
				Stripe.createToken({
					number: $('.card-number').val(),
					name: $('.card-name').val(),
					cvc: $('.card-cvc').val(),
					exp_month: $('.card-expiry-month').val(),
					exp_year: $('.card-expiry-year').val(),
					address_zip: $('.card-zip').val()
				}, rcp_stripe_response_handler);

				return false;
			});
		});
	</script>
<?php
}
add_action( 'rcp_before_update_billing_card_form', 'rcp_stripe_update_card_form_js' );

/**
 * Process an update card form request
 *
 * @access      private
 * @since       2.1
 */
function rcp_stripe_update_billing_card( $member_id = 0, $member_obj ) {

	if( empty( $member_id ) ) {
		return;
	}

	if( ! is_a( $member_obj, 'RCP_Member' ) ) {
		return;
	}

	if( ! rcp_is_stripe_subscriber( $member_id ) ) {
		return;
	}

	if( empty( $_POST['stripeToken'] ) ) {
		wp_die( __( 'Missing Stripe token', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$customer_id = $member_obj->get_payment_profile_id();

	global $rcp_options;

	if ( isset( $rcp_options['sandbox'] ) ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/init.php';
	}

	\Stripe\Stripe::setApiKey( $secret_key );
	$customer = \Stripe\Customer::retrieve( $customer_id );

	$customer->card = $_POST['stripeToken']; // obtained with stripe.js
	$customer->save();

	wp_redirect( add_query_arg( 'card', 'updated' ) ); exit;

}
add_action( 'rcp_update_billing_card', 'rcp_stripe_update_billing_card', 10, 2 );

/**
 * Create discount code in Stripe when one is created in RCP
 *
 * @access      private
 * @since       2.1
 */
function rcp_stripe_create_discount() {
	
	if( ! is_admin() ) {
		return;
	}

	if( function_exists( 'rcp_stripe_add_discount' ) ) {
		return; // Old Stripe gateway is active
	}

	if( ! rcp_is_gateway_enabled( 'stripe' ) ) {
		return;
	}

	global $rcp_options;

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/init.php';
	}

	if ( isset( $rcp_options['sandbox'] ) ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	try {

		if ( $_POST['unit'] == '%' ) {
			\Stripe\Coupon::create( array(
					"percent_off" => sanitize_text_field( $_POST['amount'] ),
					"duration"    => "forever",
					"id"          => sanitize_text_field( $_POST['code'] ),
					"currency"   => strtolower( $rcp_options['currency'] )
				)
			);
		} else {
			\Stripe\Coupon::create( array(
					"amount_off" => sanitize_text_field( $_POST['amount'] ) * 100,
					"duration"   => "forever",
					"id"         => sanitize_text_field( $_POST['code'] ),
					"currency"   => strtolower( $rcp_options['currency'] )
				)
			);
		}

	} catch ( \Stripe\Error\Card $e ) {

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

			exit;

	} catch (\Stripe\Error\InvalidRequest $e) {

		// Invalid parameters were supplied to Stripe's API
		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	} catch (\Stripe\Error\Authentication $e) {

		// Authentication with Stripe's API failed
		// (maybe you changed API keys recently)

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	} catch (\Stripe\Error\ApiConnection $e) {

		// Network communication with Stripe failed

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	} catch (\Stripe\Error\Base $e) {

		// Display a very generic error to the user

		$body = $e->getJsonBody();
		$err  = $body['error'];

		$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
		if( isset( $err['code'] ) ) {
			$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
		}
		$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
		$error .= "<p>Message: " . $err['message'] . "</p>";

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	} catch (Exception $e) {

		// Something else happened, completely unrelated to Stripe

		$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
		$error .= print_r( $e, true );

		wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	}

}
add_action( 'rcp_pre_add_discount', 'rcp_stripe_create_discount' );

/**
 * Update a discount in Stripe when a local code is updated
 *
 * @access      private
 * @since       2.1
 */
function rcp_stripe_update_discount() {

	if( ! is_admin() ) {
		return;
	}

	if( function_exists( 'rcp_stripe_add_discount' ) ) {
		return; // Old Stripe gateway is active
	}

	if( ! rcp_is_gateway_enabled( 'stripe' ) ) {
		return;
	}

	global $rcp_options;

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/init.php';
	}

	if ( isset( $rcp_options['sandbox'] ) ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	\Stripe\Stripe::setApiKey( $secret_key );

	if ( ! rcp_stripe_does_coupon_exists( $_POST['code'] ) ) {

		try {

			if ( $_POST['unit'] == '%' ) {
				\Stripe\Coupon::create( array(
						"percent_off" => sanitize_text_field( $_POST['amount'] ),
						"duration"    => "forever",
						"id"          => sanitize_text_field( $_POST['code'] ),
						"currency"    => strtolower( $rcp_options['currency'] )
					)
				);
			} else {
				\Stripe\Coupon::create( array(
						"amount_off" => sanitize_text_field( $_POST['amount'] ) * 100,
						"duration"   => "forever",
						"id"         => sanitize_text_field( $_POST['code'] ),
						"currency"   => strtolower( $rcp_options['currency'] )
					)
				);
			}

		} catch ( Exception $e ) {
			wp_die( '<pre>' . $e . '</pre>', __( 'Error', 'rcp_stripe' ) );
		}

	} else {

		// first delete the discount in Stripe
		try {
			$cpn = \Stripe\Coupon::retrieve( $_POST['code'] );
			$cpn->delete();
		} catch ( Exception $e ) {
			wp_die( '<pre>' . $e . '</pre>', __( 'Error', 'rcp_stripe' ) );
		}

		// now add a new one. This is a fake "update"
		try {

			if ( $_POST['unit'] == '%' ) {
				\Stripe\Coupon::create( array(
						"percent_off" => sanitize_text_field( $_POST['amount'] ),
						"duration"    => "forever",
						"id"          => sanitize_text_field( $_POST['code'] ),
						"currency"    => strtolower( $rcp_options['currency'] )
					)
				);
			} else {
				\Stripe\Coupon::create( array(
						"amount_off" => sanitize_text_field( $_POST['amount'] ) * 100,
						"duration"   => "forever",
						"id"         => sanitize_text_field( $_POST['code'] ),
						"currency"   => strtolower( $rcp_options['currency'] )
					)
				);
			}

		} catch (\Stripe\Error\InvalidRequest $e) {

			// Invalid parameters were supplied to Stripe's API
			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Error\Authentication $e) {

			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Error\ApiConnection $e) {

			// Network communication with Stripe failed

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Error\Base $e) {

			// Display a very generic error to the user

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (Exception $e) {

			// Something else happened, completely unrelated to Stripe

			$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
			$error .= print_r( $e, true );

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		}
	}
}
add_action( 'rcp_edit_discount', 'rcp_stripe_update_discount' );

/**
 * Check if a coupone exists in Stripe
 *
 * @access      private
 * @since       2.1
 */
function rcp_stripe_does_coupon_exists( $code ) {
	global $rcp_options;

	if( ! class_exists( 'Stripe\Stripe' ) ) {
		require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/init.php';
	}

	if ( isset( $rcp_options['sandbox'] ) ) {
		$secret_key = trim( $rcp_options['stripe_test_secret'] );
	} else {
		$secret_key = trim( $rcp_options['stripe_live_secret'] );
	}

	\Stripe\Stripe::setApiKey( $secret_key );
	try {
		\Stripe\Coupon::retrieve( $code );
		$exists = true;
	} catch ( Exception $e ) {
		$exists = false;
	}

	return $exists;
}