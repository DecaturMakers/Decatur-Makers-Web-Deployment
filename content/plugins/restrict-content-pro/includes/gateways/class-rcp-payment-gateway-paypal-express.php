<?php
/**
 * PayPal Express Gateway class
 *
 * @package     Restrict Content Pro
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_PayPal_Express extends RCP_Payment_Gateway {

	private $api_endpoint;
	private $checkout_url;
	protected $username;
	protected $password;
	protected $signature;

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

			$this->api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
			$this->checkout_url = 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=';

		} else {

			$this->api_endpoint = 'https://api-3t.paypal.com/nvp';
			$this->checkout_url = 'https://www.paypal.com/webscr&cmd=_express-checkout&token=';

		}

		if( rcp_has_paypal_api_access() ) {

			$creds = rcp_get_paypal_api_credentials();

			$this->username  = $creds['username'];
			$this->password  = $creds['password'];
			$this->signature = $creds['signature'];

		}

	}

	/**
	 * Process registration
	 *
	 * @since 2.1
	 */
	public function process_signup() {

		global $rcp_options;

		if( $this->auto_renew ) {
			$amount = $this->amount;
		} else {
			$amount = round( $this->amount + $this->signup_fee, 2 );
		}

		$args = array(
			'USER'                           => $this->username,
			'PWD'                            => $this->password,
			'SIGNATURE'                      => $this->signature,
			'VERSION'                        => '121',
			'METHOD'                         => 'SetExpressCheckout',
			'PAYMENTREQUEST_0_AMT'           => $amount,
			'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
			'PAYMENTREQUEST_0_CURRENCYCODE'  => strtoupper( $this->currency ),
			'PAYMENTREQUEST_0_ITEMAMT'       => $amount,
			'PAYMENTREQUEST_0_SHIPPINGAMT'   => 0,
			'PAYMENTREQUEST_0_TAXAMT'        => 0,
			'PAYMENTREQUEST_0_DESC'          => $this->subscription_name,
			'PAYMENTREQUEST_0_CUSTOM'        => $this->user_id,
			'PAYMENTREQUEST_0_NOTIFYURL'     => add_query_arg( 'listener', 'EIPN', home_url( 'index.php' ) ),
			'EMAIL'                          => $this->email,
			'RETURNURL'                      => add_query_arg( array( 'rcp-confirm' => 'paypal_express', 'user_id' => $this->user_id ), get_permalink( $rcp_options['registration_page'] ) ),
			'CANCELURL'                      => get_permalink( $rcp_options['registration_page'] ),
			'REQCONFIRMSHIPPING'             => 0,
			'NOSHIPPING'                     => 1,
			'ALLOWNOTE'                      => 0,
			'ADDROVERRIDE'                   => 0,
			'PAGESTYLE'                      => ! empty( $rcp_options['paypal_page_style'] ) ? trim( $rcp_options['paypal_page_style'] ) : '',
			'SOLUTIONTYPE'                   => 'Sole',
			'LANDINGPAGE'                    => 'Billing',
		);

		if( $this->auto_renew && ! empty( $this->length ) ) {
			$args['L_BILLINGAGREEMENTDESCRIPTION0'] = $this->subscription_name;
			$args['L_BILLINGTYPE0']                 = 'RecurringPayments';
			$args['RETURNURL']                      = add_query_arg( array( 'rcp-recurring' => '1' ), $args['RETURNURL'] );
		}

		$request = wp_remote_post( $this->api_endpoint, array( 'timeout' => 45, 'sslverify' => false, 'body' => $args ) );

		if( is_wp_error( $request ) ) {

			$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
			$error .= '<p>' . $request->get_error_message() . '</p>';

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

		} elseif ( 200 == $request['response']['code'] && 'OK' == $request['response']['message'] ) {

			parse_str( $request['body'], $data );

			if( 'failure' === strtolower( $data['ACK'] ) ) {

				$error = '<p>' . __( 'PayPal token creation failed.', 'rcp' ) . '</p>';
				$error .= '<p>' . __( 'Error message:', 'rcp' ) . ' ' . $data['L_LONGMESSAGE0'] . '</p>';
				$error .= '<p>' . __( 'Error code:', 'rcp' ) . ' ' . $data['L_ERRORCODE0'] . '</p>';

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

			} else {

				// Successful token
				wp_redirect( $this->checkout_url . $data['TOKEN'] );
				exit;

			}

		} else {

			wp_die( __( 'Something has gone wrong, please try again', 'rcp' ), __( 'Error', 'rcp' ), array( 'back_link' => true, 'response' => '401' ) );

		}

	}

	/**
	 * Validate additional fields during registration submission
	 *
	 * @since 2.1
	 */
	public function validate_fields() {

		if( ! rcp_has_paypal_api_access() ) {
			rcp_errors()->add( 'no_paypal_api', __( 'You have not configured PayPal API access. Please configure it in Restrict &rarr; Settings', 'rcp' ), 'register' );
		}

	}

	/**
	 * Process payment confirmation after returning from PayPal
	 *
	 * @since 2.1
	 */
	public function process_confirmation() {

		if ( isset( $_POST['rcp_ppe_confirm_nonce'] ) && wp_verify_nonce( $_POST['rcp_ppe_confirm_nonce'], 'rcp-ppe-confirm-nonce' ) ) {

			$details = $this->get_checkout_details( $_POST['token'] );

			if( ! empty( $_GET['rcp-recurring'] ) ) {

				// Successful payment, now create the recurring profile

				$args = array(
					'USER'                => $this->username,
					'PWD'                 => $this->password,
					'SIGNATURE'           => $this->signature,
					'VERSION'             => '121',
					'TOKEN'               => $_POST['token'],
					'METHOD'              => 'CreateRecurringPaymentsProfile',
					'PROFILESTARTDATE'    => date( 'Y-m-d\Tg:i:s', strtotime( '+' . $details['subscription']['duration'] . ' ' . $details['subscription']['duration_unit'], time() ) ),
					'BILLINGPERIOD'       => ucwords( $details['subscription']['duration_unit'] ),
					'BILLINGFREQUENCY'    => $details['subscription']['duration'],
					'AMT'                 => $details['AMT'],
					'INITAMT'             => round( $details['AMT'] + $details['subscription']['fee'], 2 ),
					'CURRENCYCODE'        => $details['CURRENCYCODE'],
					'FAILEDINITAMTACTION' => 'CancelOnFailure',
					'L_BILLINGTYPE0'      => 'RecurringPayments',
					'DESC'                => $details['subscription']['name'],
					'BUTTONSOURCE'        => 'EasyDigitalDownloads_SP'
				);

				$request = wp_remote_post( $this->api_endpoint, array( 'timeout' => 45, 'sslverify' => false, 'body' => $args ) );

				if( is_wp_error( $request ) ) {

					$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
					$error .= '<p>' . $request->get_error_message() . '</p>';

					wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

				} elseif ( 200 == $request['response']['code'] && 'OK' == $request['response']['message'] ) {

					parse_str( $request['body'], $data );

					if( 'failure' === strtolower( $data['ACK'] ) ) {

						$error = '<p>' . __( 'PayPal payment processing failed.', 'rcp' ) . '</p>';
						$error .= '<p>' . __( 'Error message:', 'rcp' ) . ' ' . $data['L_LONGMESSAGE0'] . '</p>';
						$error .= '<p>' . __( 'Error code:', 'rcp' ) . ' ' . $data['L_ERRORCODE0'] . '</p>';

						wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

					} else {

						$member = new RCP_Member( $details['PAYMENTREQUEST_0_CUSTOM'] );

						$member->renew( true );
						$member->set_payment_profile_id( $data['PROFILEID'] );

						$payment_data = array(
							'date'             => date( 'Y-m-d g:i:s', current_time( 'timestamp' ) ),
							'subscription'     => $member->get_subscription_name(),
							'payment_type'     => 'PayPal Express',
							'subscription_key' => $member->get_subscription_key(),
							'amount'           => round( $details['AMT'] + $details['subscription']['fee'], 2 ),
							'user_id'          => $member->ID,
							'transaction_id'   => $data['PROFILEID']
						);

						$rcp_payments = new RCP_Payments;
						$rcp_payments->insert( $payment_data );

						wp_redirect( esc_url_raw( rcp_get_return_url() ) ); exit;

					}

				} else {

					wp_die( __( 'Something has gone wrong, please try again', 'rcp' ), __( 'Error', 'rcp' ), array( 'back_link' => true, 'response' => '401' ) );

				}

			} else {

				// One time payment

				$args = array(
					'USER'                           => $this->username,
					'PWD'                            => $this->password,
					'SIGNATURE'                      => $this->signature,
					'VERSION'                        => '121',
					'METHOD'                         => 'DoExpressCheckoutPayment',
					'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
					'TOKEN'                          => $_POST['token'],
					'PAYERID'                        => $_POST['payer_id'],
					'PAYMENTREQUEST_0_AMT'           => $details['AMT'],
					'PAYMENTREQUEST_0_ITEMAMT'       => $details['AMT'],
					'PAYMENTREQUEST_0_SHIPPINGAMT'   => 0,
					'PAYMENTREQUEST_0_TAXAMT'        => 0,
					'PAYMENTREQUEST_0_CURRENCYCODE'  => $details['CURRENCYCODE'],
					'BUTTONSOURCE'                   => 'EasyDigitalDownloads_SP'
				);

				$request = wp_remote_post( $this->api_endpoint, array( 'timeout' => 45, 'sslverify' => false, 'body' => $args ) );

				if( is_wp_error( $request ) ) {

					$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
					$error .= '<p>' . $request->get_error_message() . '</p>';

					wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

				} elseif ( 200 == $request['response']['code'] && 'OK' == $request['response']['message'] ) {

					parse_str( $request['body'], $data );

					if( 'failure' === strtolower( $data['ACK'] ) ) {

						$error = '<p>' . __( 'PayPal payment processing failed.', 'rcp' ) . '</p>';
						$error .= '<p>' . __( 'Error message:', 'rcp' ) . ' ' . $data['L_LONGMESSAGE0'] . '</p>';
						$error .= '<p>' . __( 'Error code:', 'rcp' ) . ' ' . $data['L_ERRORCODE0'] . '</p>';

						wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

					} else {

						// Confirm a one-time payment
						$member = new RCP_Member( $details['CUSTOM'] );

						$member->renew( false );

						$payment_data = array(
							'date'             => date( 'Y-m-d g:i:s', strtotime( $data['PAYMENTINFO_0_ORDERTIME'] ) ),
							'subscription'     => $member->get_subscription_name(),
							'payment_type'     => 'PayPal Express One Time',
							'subscription_key' => $member->get_subscription_key(),
							'amount'           => $data['PAYMENTINFO_0_AMT'],
							'user_id'          => $member->ID,
							'transaction_id'   => $data['PAYMENTINFO_0_TRANSACTIONID']
						);

						$rcp_payments = new RCP_Payments;
						$rcp_payments->insert( $payment_data );


						wp_redirect( esc_url_raw( rcp_get_return_url() ) ); exit;

					}

				} else {

					wp_die( __( 'Something has gone wrong, please try again', 'rcp' ), __( 'Error', 'rcp' ), array( 'back_link' => true, 'response' => '401' ) );

				}

			}


		} elseif ( ! empty( $_GET['token'] ) && ! empty( $_GET['PayerID'] ) ) {

			add_filter( 'the_content', array( $this, 'confirmation_form' ), 9999999 );
	
		}

	}

	/**
	 * Display the confirmation form
	 *
	 * @since 2.1
	 * @return string
	 */
	public function confirmation_form() {

		global $rcp_checkout_details;

		$token                = sanitize_text_field( $_GET['token'] );
		$rcp_checkout_details = $this->get_checkout_details( $token );

		ob_start();
		rcp_get_template_part( 'paypal-express-confirm' );
		return ob_get_clean();
	}

	/**
	 * Process PayPal IPN
	 *
	 * @since 2.1
	 */
	public function process_webhooks() {

		if( ! isset( $_GET['listener'] ) || strtoupper( $_GET['listener'] ) != 'EIPN' ) {
			return;
		}

		$posted  = apply_filters('rcp_ipn_post', $_POST ); // allow $_POST to be modified
		$user_id = absint( $posted['custom'] );
		$member  = new RCP_Member( $user_id );

		if( ! $member || ! $member->get_subscription_id() ) {
			die( 'no member found' );
		}

		if( ! rcp_get_subscription_details( $member->get_subscription_id() ) ) {
			die( 'no subscription level found' );
		}

		$amount = number_format( (float) $posted['mc_gross'], 2 );

		// setup the payment info in an array for storage
		$payment_data = array(
			'date'             => date( 'Y-m-d g:i:s', strtotime( $posted['payment_date'] ) ),
			'subscription'     => $member->get_subscription_name(),
			'payment_type'     => $posted['txn_type'],
			'subscription_key' => $member->get_subscription_key(),
			'amount'           => $amount,
			'user_id'          => $user_id,
			'transaction_id'   => $posted['txn_id']
		);

		do_action( 'rcp_valid_ipn', $payment_data, $user_id, $posted );

		if( isset( $rcp_options['email_ipn_reports'] ) ) {
			wp_mail( get_bloginfo('admin_email'), __( 'IPN report', 'rcp' ), $listener->getTextReport() );
		}

		/* now process the kind of subscription/payment */

		$rcp_payments = new RCP_Payments();

		// Subscriptions
		switch ( $posted['txn_type'] ) :

			case "recurring_payment" :

				// when a user makes a recurring payment

				// record this payment in the database
				$rcp_payments->insert( $payment_data );

				update_user_meta( $user_id, 'rcp_paypal_subscriber', $posted['payer_id'] );

				$member->set_payment_profile_id( $posted['recurring_payment_id'] );

				$this->renew_member( true );

				do_action( 'rcp_ipn_subscr_payment', $user_id );

				die( 'successful recurring_payment' );

				break;

			case "recurring_payment_profile_cancel" :

				// user is marked as cancelled but retains access until end of term
				$member->set_status( 'cancelled' );

				// set the use to no longer be recurring
				delete_user_meta( $user_id, 'rcp_paypal_subscriber' );

				do_action( 'rcp_ipn_subscr_cancel', $user_id );

				die( 'successful recurring_payment_profile_cancel' );

				break;

			case "recurring_payment_failed" :
			case "recurring_payment_suspended_due_to_max_failed_payment" :

				if( 'cancelled' !== $member->get_status( $user_id ) ) {

					$member->set_status( 'expired' );

				}

				do_action( 'rcp_ipn_subscr_failed' );
				die( 'successful recurring_payment_failed or recurring_payment_suspended_due_to_max_failed_payment' );

				break;


		endswitch;

	}

	public function get_checkout_details( $token = '' ) {

		$args = array(
			'USER'      => $this->username,
			'PWD'       => $this->password,
			'SIGNATURE' => $this->signature,
			'VERSION'   => '121',
			'METHOD'    => 'GetExpressCheckoutDetails',
			'TOKEN'     => $token
		);

		$request = wp_remote_get( add_query_arg( $args, $this->api_endpoint ), array( 'timeout' => 45, 'sslverify' => false ) );

		if( is_wp_error( $request ) ) {

			return $request;

		} elseif ( 200 == $request['response']['code'] && 'OK' == $request['response']['message'] ) {

			parse_str( $request['body'], $data );

			$data['subscription'] = (array) rcp_get_subscription_details( rcp_get_subscription_id( $_GET['user_id'] ) );

			return $data;

		}

		return false;

	}

}