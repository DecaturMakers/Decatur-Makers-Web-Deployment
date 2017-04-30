<?php
/**
 * Payment Gateway Base Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_Stripe extends RCP_Payment_Gateway {

	private $secret_key;
	private $publishable_key;

	/**
	 * Get things going
	 *
	 * @since 2.1
	 */
	public function init() {

		global $rcp_options;

		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';

		$this->test_mode   = isset( $rcp_options['sandbox'] );

		if( $this->test_mode ) {

			$this->secret_key      = isset( $rcp_options['stripe_test_secret'] )      ? trim( $rcp_options['stripe_test_secret'] )      : '';
			$this->publishable_key = isset( $rcp_options['stripe_test_publishable'] ) ? trim( $rcp_options['stripe_test_publishable'] ) : '';

		} else {

			$this->secret_key      = isset( $rcp_options['stripe_live_secret'] )      ? trim( $rcp_options['stripe_live_secret'] )      : '';
			$this->publishable_key = isset( $rcp_options['stripe_live_publishable'] ) ? trim( $rcp_options['stripe_live_publishable'] ) : '';

		}

		if( ! class_exists( 'Stripe\Stripe' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/init.php';
		}

	}

	/**
	 * Process registration
	 *
	 * @since 2.1
	 */
	public function process_signup() {

		\Stripe\Stripe::setApiKey( $this->secret_key );

		$paid   = false;
		$member = new RCP_Member( $this->user_id );
		$customer_exists = false;

		if( empty( $_POST['stripeToken'] ) ) {
			wp_die( __( 'Missing Stripe token, please try again or contact support if the issue persists.', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
		}

		if ( $this->auto_renew ) {

			// process a subscription sign up

			$plan_id = strtolower( str_replace( ' ', '', $this->subscription_name ) );

			if ( ! $this->plan_exists( $plan_id ) ) {
				// create the plan if it doesn't exist
				$this->create_plan( $this->subscription_name );
			}

			try {

				$customer_id = $member->get_payment_profile_id();

				if ( $customer_id ) {

					$customer_exists = true;

					try {

						// Update the customer to ensure their card data is up to date
						$customer = \Stripe\Customer::retrieve( $customer_id );

						if( isset( $customer->deleted ) && $customer->deleted ) {

							// This customer was deleted
							$customer_exists = false;

						}

					// No customer found
					} catch ( Exception $e ) {


						$customer_exists = false;

					}

				}

				// Customer with a discount
				if ( ! empty( $this->discount_code ) ) {

					if( $customer_exists ) {

						$customer->card   = $_POST['stripeToken'];
						$customer->coupon = $this->discount_code;
						$customer->save();

						// Update the customer's subscription in Stripe
						$customer_response = $customer->updateSubscription( array( 'plan' => $plan_id ) );

					} else {

						$customer = \Stripe\Customer::create( apply_filters( 'rcp_stripe_customer_create_args', array(
							'card' 			=> $_POST['stripeToken'],
							'plan' 			=> $plan_id,
							'email' 		=> $this->email,
							'description' 	=> 'User ID: ' . $this->user_id . ' - User Email: ' . $this->email . ' Subscription: ' . $this->subscription_name,
							'coupon' 		=> $_POST['rcp_discount']
						), $this ) );

					}

				// Customer without a discount
				} else {

					if( $customer_exists ) {

						$customer->card   = $_POST['stripeToken'];
						$customer->save();

						// Update the customer's subscription in Stripe
						$customer_response = $customer->updateSubscription( array( 'plan' => $plan_id ) );

					} else {

						$customer = \Stripe\Customer::create( apply_filters( 'rcp_stripe_customer_create_args', array(
							'card' 			=> $_POST['stripeToken'],
							'plan' 			=> $plan_id,
							'email' 		=> $this->email,
							'description' 	=> 'User ID: ' . $this->user_id . ' - User Email: ' . $this->email . ' Subscription: ' . $this->subscription_name
						), $this ) );

					}

				}

				if ( ! empty( $this->signup_fee ) ) {

					if( $this->signup_fee > 0 ) {
						$description = sprintf( __( 'Signup Fee for %s', 'rcp_stripe' ), $this->subscription_name );
					} else {
						$description = sprintf( __( 'Signup Discount for %s', 'rcp_stripe' ), $this->subscription_name );
					}

					\Stripe\InvoiceItem::create( apply_filters( 'rcp_stripe_invoice_item_create_args', array(
						'customer'    => $customer->id,
						'amount'      => $this->signup_fee * 100,
						'currency'    => strtolower( $this->currency ),
						'description' => $description
					), $this, $customer ) );

					// Create the invoice containing taxes / discounts / fees
					$invoice = \Stripe\Invoice::create( apply_filters( 'rcp_stripe_invoice_create_args', array(
						'customer' => $customer->id, // the customer to apply the fee to
					), $this, $customer ) );

					$invoice->pay();

				}

				$member->set_payment_profile_id( $customer->id );

				// subscription payments are recorded via webhook

				$paid = true;

			} catch ( \Stripe\Error\Card $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
				if( isset( $err['code'] ) ) {
					$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

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

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

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

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

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

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

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

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

			} catch (Exception $e) {

				// Something else happened, completely unrelated to Stripe

				$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
				$error .= print_r( $e, true );

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

			}

		} else {

			// process a one time payment signup

			try {

				$charge = \Stripe\Charge::create( apply_filters( 'rcp_stripe_charge_create_args', array(
					'amount' 		 => $this->amount * 100, // amount in cents
					'currency' 		 => strtolower( $this->currency ),
					'card' 			 => $_POST['stripeToken'],
					'description' 	 => 'User ID: ' . $this->user_id . ' - User Email: ' . $this->email . ' Subscription: ' . $this->subscription_name,
					'receipt_email'  => $this->email,
					'metadata'       => array(
						'email'      => $this->email,
						'user_id'    => $this->user_id,
						'level_id'   => $this->subscription_id,
						'level'      => $this->subscription_name,
						'key'        => $this->subscription_key
					)
				), $this ) );

				$payment_data = array(
					'date'              => date( 'Y-m-d g:i:s', time() ),
					'subscription'      => $this->subscription_name,
					'payment_type' 		=> 'Credit Card One Time',
					'subscription_key' 	=> $this->subscription_key,
					'amount' 			=> $this->amount,
					'user_id' 			=> $this->user_id,
					'transaction_id'    => $charge->id
				);

				$rcp_payments = new RCP_Payments();
				$rcp_payments->insert( $payment_data );

				$paid = true;

			} catch ( \Stripe\Error\Card $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
				if( isset( $err['code'] ) ) {
					$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

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

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

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

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

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

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

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

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

			} catch (Exception $e) {

				// Something else happened, completely unrelated to Stripe

				$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
				$error .= print_r( $e, true );

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

			}
		}

		if ( $paid ) {

			// set this user to active
			$member->renew( $this->auto_renew );

			if ( ! is_user_logged_in() ) {

				// log the new user in
				rcp_login_user_in( $this->user_id, $this->user_name, $_POST['rcp_user_pass'] );

			}

			do_action( 'rcp_stripe_signup', $this->user_id, $this );

		} else {

			wp_die( __( 'An error occurred, please contact the site administrator: ', 'rcp_stripe' ) . get_bloginfo( 'admin_email' ), __( 'Error', 'rcp' ), array( 'response' => '401' ) );

		}

		// redirect to the success page, or error page if something went wrong
		wp_redirect( $this->return_url ); exit;

	}

	public function process_webhooks() {

		if( ! isset( $_GET['listener'] ) || strtolower( $_GET['listener'] ) != 'stripe' ) {
			return;
		}

		// Ensure listener URL is not cached by W3TC
		define( 'DONOTCACHEPAGE', true );

		\Stripe\Stripe::setApiKey( $this->secret_key );

		// retrieve the request's body and parse it as JSON
		$body          = @file_get_contents( 'php://input' );
		$event_json_id = json_decode( $body );

		// for extra security, retrieve from the Stripe API
		if ( isset( $event_json_id->id ) ) {

			$rcp_payments = new RCP_Payments();

			$event_id = $event_json_id->id;

			try {

				$event   = \Stripe\Event::retrieve( $event_id );
				$invoice = $event->data->object;

				if( empty( $invoice->customer ) ) {
					die( 'no customer attached' );
				}

				// retrieve the customer who made this payment (only for subscriptions)
				$user    = rcp_get_member_id_from_profile_id( $invoice->customer );

				if( empty( $user ) ) {

					// Grab the customer ID from the old meta keys
					global $wpdb;
					$user = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_rcp_stripe_user_id' AND meta_value = %s LIMIT 1", $invoice->customer ) );

				}

				if( empty( $user ) ) {
					die( 'no user ID found' );
				}

				$member = new RCP_Member( $user );
				
				// check to confirm this is a stripe subscriber
				if ( $member ) {

					// successful payment
					if ( $event->type == 'charge.succeeded' ) {

						if( ! $member->get_subscription_id() ) {
							die( 'no subscription ID for member' );
						}

						$payment_data = array(
							'date'              => date( 'Y-m-d g:i:s', $event->created ),
							'subscription'      => $member->get_subscription_name(),
							'payment_type' 		=> 'Credit Card',
							'subscription_key' 	=> $member->get_subscription_key(),
							'amount' 			=> $invoice->amount / 100,
							'user_id' 			=> $member->ID,
							'transaction_id'    => $invoice->id
						);

						if( ! rcp_check_for_existing_payment( $payment_data['payment_type'], $payment_data['date'], $payment_data['subscription_key'] ) ) {

							// record this payment if it hasn't been recorded yet
							$rcp_payments->insert( $payment_data );

							$member->renew( $member->is_recurring() );

							do_action( 'rcp_stripe_charge_succeeded', $user, $payment_data );

							die( 'rcp_stripe_charge_succeeded action fired successfully' );

						} else {

							die( 'duplicate payment found' );

						}

					}

					// failed payment
					if ( $event->type == 'charge.failed' ) {

						do_action( 'rcp_stripe_charge_failed', $invoice );

						die( 'rcp_stripe_charge_failed action fired successfully' );

					}

					// Cancelled / failed subscription
					if( $event->type == 'customer.subscription.deleted' ) {

						$member->set_status( 'cancelled' );

						die( 'member cancelled successfully' );

					}

					do_action( 'rcp_stripe_' . $event->type, $invoice );

				}


			} catch ( Exception $e ) {
				// something failed
				die( 'PHP exception: ' . $e->getMessage() );
			}

			die( '1' );

		}
		
		die( 'no event ID found' );

	}

	/**
	 * Add credit card fields
	 *
	 * @since 2.1
	 * @return string
	 */
	public function fields() {

		ob_start();
?>
		<script type="text/javascript">

			// this identifies your website in the createToken call below
			Stripe.setPublishableKey('<?php echo $this->publishable_key; ?>');

			function stripeResponseHandler(status, response) {
				if (response.error) {
					// re-enable the submit button
					jQuery('#rcp_registration_form #rcp_submit').attr("disabled", false);

					jQuery('#rcp_ajax_loading').hide();

					// show the errors on the form
					jQuery(".payment-errors").html(response.error.message);

				} else {
					var form$ = jQuery("#rcp_registration_form");
					// token contains id, last4, and card type
					var token = response['id'];
					// insert the token into the form so it gets submitted to the server
					form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");

					// and submit
					form$.get(0).submit();

				}
			}

			jQuery(document).ready(function($) {

				$("#rcp_registration_form").on('submit', function(event) {
					// get the subscription price

					if( $('.rcp_level:checked').length ) {
						var price = $('.rcp_level:checked').closest('li').find('span.rcp_price').attr('rel') * 100;
					} else {
						var price = $('.rcp_level').attr('rel') * 100;
					}

					if( ( $('select#rcp_gateway option:selected').val() == 'stripe' || $('input[name=rcp_gateway]').val() == 'stripe') && price > 0 && ! $('.rcp_gateway_fields').hasClass('rcp_discounted_100')) {

						event.preventDefault();

						// disable the submit button to prevent repeated clicks
						$('#rcp_registration_form #rcp_submit').attr("disabled", "disabled");
						$('#rcp_ajax_loading').show();

						// createToken returns immediately - the supplied callback submits the form if there are no errors
						Stripe.createToken({
							number: $('.card-number').val(),
							name: $('.card-name').val(),
							cvc: $('.card-cvc').val(),
							exp_month: $('.card-expiry-month').val(),
							exp_year: $('.card-expiry-year').val(),
							address_zip: $('.card-zip').val()
						}, stripeResponseHandler);

						return false;
					}
				});
			});
		</script>
<?php
		rcp_get_template_part( 'card-form' );
		return ob_get_clean();
	}

	/**
	 * Validate additional fields during registration submission
	 *
	 * @since 2.1
	 */
	public function validate_fields() {

		if( empty( $_POST['rcp_card_number'] ) ) {
			rcp_errors()->add( 'missing_card_number', __( 'The card number you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_cvc'] ) ) {
			rcp_errors()->add( 'missing_card_code', __( 'The security code you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_zip'] ) ) {
			rcp_errors()->add( 'missing_card_zip', __( 'The zip / postal code you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_name'] ) ) {
			rcp_errors()->add( 'missing_card_name', __( 'The card holder name you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_exp_month'] ) ) {
			rcp_errors()->add( 'missing_card_exp_month', __( 'The card expiration month you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_exp_year'] ) ) {
			rcp_errors()->add( 'missing_card_exp_year', __( 'The card expiration year you have entered is invalid', 'rcp' ), 'register' );
		}

	}

	/**
	 * Load Stripe JS
	 *
	 * @since 2.1
	 */
	public function scripts() {
		wp_enqueue_script( 'stripe', 'https://js.stripe.com/v2/', array( 'jquery' ) );
	}

	/**
	 * Create plan in Stripe
	 *
	 * @since 2.1
	 * @return bool
	 */
	private function create_plan( $plan_name = '' ) {
		global $rcp_options;

		// get all subscription level info for this plan
		$plan           = rcp_get_subscription_details_by_name( $plan_name );
		$price          = $plan->price * 100;
		$interval       = $plan->duration_unit;
		$interval_count = $plan->duration;
		$name           = $plan->name;
		$plan_id        = strtolower( str_replace( ' ', '', $plan_name ) );
		$currency       = strtolower( $rcp_options['currency'] );

		\Stripe\Stripe::setApiKey( $this->secret_key );

		try {

			\Stripe\Plan::create( array(
				"amount"         => $price,
				"interval"       => $interval,
				"interval_count" => $interval_count,
				"name"           => $name,
				"currency"       => $currency,
				"id"             => $plan_id
			) );

			// plann successfully created
			return true;

		} catch ( Exception $e ) {
			// there was a problem
			return false;
		}

	}

	/**
	 * Determine if a plan exists
	 *
	 * @since 2.1
	 * @return bool
	 */
	private function plan_exists( $plan_id = '' ) {

		$plan_id = strtolower( str_replace( ' ', '', $plan_id ) );

		\Stripe\Stripe::setApiKey( $this->secret_key );

		try {
			$plan = \Stripe\Plan::retrieve( $plan_id );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

}
