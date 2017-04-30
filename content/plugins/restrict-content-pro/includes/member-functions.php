<?php

/****************************************
* Functions for getting member info
*****************************************/

/*
* Returns an array of all members, based on subscription status
* @param string $status - the subscription status of users to retrieve
* @param int $subscription - the subscription ID to retrieve users from
* @param int $offset - the number of users to skip, used for pagination
* @param int $number - the total users to retrieve, used for pagination
* @param string $order - the order in which to display users: ASC / DESC
* @param string $recurring - retrieve recurring (or non recurring) only
* @param string $search - seach parameter
* Return array
*/
function rcp_get_members( $status = 'active', $subscription = null, $offset = 0, $number = 999999, $order = 'DESC', $recurring = null, $search = '' ) {

	global $wpdb;

	$args = array(
		'offset' => $offset,
		'number' => $number,
		'count_total' => false,
		'orderby' => 'ID',
		'order' => $order,
		'meta_query' => array(
			array(
				'key' => 'rcp_status',
				'value' => $status
			)
		)
	);

	if( ! empty( $subscription ) ) {
		$args['meta_query'][] = array(
			'key'   => 'rcp_subscription_level',
			'value' => $subscription
		);
	}

	if( ! empty( $recurring ) ) {
		if( $recurring == 1 ) {
			// find non recurring users

			$args['meta_query'][] = array(
				'key'     => 'rcp_recurring',
				'compare' => 'NOT EXISTS'
			);
		} else {
			// find recurring users
			$args['meta_query'][] = array(
				'key'     => 'rcp_recurring',
				'value'   => 'yes'
			);
		}
	}

	if( ! empty( $search ) ) {
		$args['search'] = sanitize_text_field( $search );
	}

	$members = get_users( $args );

	if( !empty( $members ) )
		return $members;

	return false;
}


/*
* Counts the number of members by subscription level and status
* @param string/int $level - the ID of the subscription level to count members of
* @param string - the status to count
* return int - the number of members for the specified subscription level and status
*/
function rcp_count_members( $level = '', $status = 'active', $recurring = null, $search = '' ) {
	global $wpdb;


	if( $status == 'free' ) {

		if ( ! empty( $level ) ) :

			$args = array(
				'meta_query' => array(
					array(
						'key' => 'rcp_subscription_level',
						'value' => $level,
					),
					array(
						'key'   => 'rcp_status',
						'value' => 'free'
					)
				)
			);

		else :

			$args = array(
				'meta_query' => array(
					array(
						'key'   => 'rcp_status',
						'value' => 'free'
					)
				)
			);

		endif;

	} else {

		if ( ! empty( $level ) ) :

			$args = array(
				'meta_query' => array(
					array(
						'key'   => 'rcp_subscription_level',
						'value' =>  $level
					),
					array(
						'key'   => 'rcp_status',
						'value' => $status
					)
				)
			);

		else :

			$args = array(
				'meta_query' => array(
					array(
						'key'   => 'rcp_status',
						'value' => $status
					)
				)
			);

		endif;

	}

	if( ! empty( $recurring ) ) {
		if( $recurring == 1 ) {
			// find non recurring users

			$args['meta_query'][] = array(
				'key'     => 'rcp_recurring',
				'compare' => 'NOT EXISTS'
			);
		} else {
			// find recurring users
			$args['meta_query'][] = array(
				'key'     => 'rcp_recurring',
				'value'   => 'yes'
			);
		}
	}

	if( ! empty( $search ) ) {
		$args['search'] = sanitize_text_field( $search );
	}

	$args['fields'] = 'ID';
	$users = new WP_User_Query( $args );
	return $users->get_total();
}

/*
* Retrieves the total number of members by subscription status
* return array - an array of counts
*/
function rcp_count_all_members() {
	$counts = array(
		'active' 	=> rcp_count_members('', 'active'),
		'pending' 	=> rcp_count_members('', 'pending'),
		'expired' 	=> rcp_count_members('', 'expired'),
		'cancelled' => rcp_count_members('', 'cancelled'),
		'free' 		=> rcp_count_members('', 'free')
	);
	return $counts;
}

/*
* Gets all members of a particular subscription level
* @param int $id - the ID of the subscription level to retrieve users for
* @param mixed $fields - the user fields to restrieve. String or array
* return array - an array of user objects
*/
function rcp_get_members_of_subscription( $id = 1, $fields = 'ID') {
	$members = get_users(array(
			'meta_key' 		=> 'rcp_subscription_level',
			'meta_value' 	=> $id,
			'number' 		=> 0,
			'fields' 		=> $fields,
			'count_total' 	=> false
		)
	);
	return $members;
}

/*
* Gets a user's subscription level ID
* @param int $user_id - the ID of the user to return the subscription level of
* return int - the ID of the user's subscription level
*/
function rcp_get_subscription_id( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->get_subscription_id();

}

/*
* Gets a user's subscription level name
* @param int $user_id - the ID of the user to return the subscription level of
* return string - the name of the user's subscription level
*/
function rcp_get_subscription( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->get_subscription_name();

}


/*
* Checks whether a user has a recurring subscription
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user is recurring, false otherwise
*/
function rcp_is_recurring( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->is_recurring();

}


/*
* Checks whether a user is expired
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user is expired, false otherwise
*/
function rcp_is_expired( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->is_expired();

}

/*
* Checks whether a user has an active subscription
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user has an active, paid subscription (or is trialing), false otherwise
*/
function rcp_is_active( $user_id = 0 ) {

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->is_active();

}

/*
* Just a wrapper function for rcp_is_active()
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user has an active, paid subscription (or is trialing), false otherwise
*/
function rcp_is_paid_user( $user_id = 0) {

	$ret = false;

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	if( rcp_is_active( $user_id ) ) {
		$ret = true;
	}
	return apply_filters( 'rcp_is_paid_user', $ret, $user_id );
}

/*
* returns true if the user's subscription gives access to the provided access level
*/
function rcp_user_has_access( $user_id = 0, $access_level_needed ) {

	$subscription_level = rcp_get_subscription_id( $user_id );
	$user_access_level = rcp_get_subscription_access_level( $subscription_level );

	if( ( $user_access_level >= $access_level_needed ) || $access_level_needed == 0 || current_user_can( 'manage_options' ) ) {
		// the user has access
		return true;
	}

	// the user does not have access
	return false;
}

/**
 * Wrapper function for RCP_Member->can_access()
 *
 * Returns true if user can access the current content
 *
 * @access      public
 * @since       2.1
 */
function rcp_user_can_access( $user_id = 0, $post_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if( empty( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}

	$member = new RCP_Member( $user_id );
	return $member->can_access( $post_id );
}

function rcp_calc_member_expiration( $expiration_object ) {

	$current_time       = current_time( 'timestamp' );
	$last_day           = cal_days_in_month( CAL_GREGORIAN, date( 'n', $current_time ), date( 'Y', $current_time ) );

	$expiration_unit 	= $expiration_object->duration_unit;
	$expiration_length 	= $expiration_object->duration;
	$member_expires 	= date( 'Y-m-d H:i:s', strtotime( '+' . $expiration_length . ' ' . $expiration_unit . ' 23:59:59' ) );

	if( date( 'j', $current_time ) == $last_day && 'day' != $expiration_unit ) {
		$member_expires = date( 'Y-m-d H:i:s', strtotime( $member_expires . ' +2 days' ) );
	}

	return apply_filters( 'rcp_calc_member_expiration', $member_expires, $expiration_object );
}


/*
* Gets the date of a user's expiration in a nice format
* @param int $user_id - the ID of the user to return the subscription level of
* return string - The date of the user's expiration, in the format specified in settings
*/
function rcp_get_expiration_date( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->get_expiration_date();
}

/**
 * Sets the users expiration date
 * @param int $user_id - the ID of the user to return the subscription level of
 * @param string $date - the expiration date in YYYY-MM-DD H:i:s
 * @since 2.0
 * @return string - The date of the user's expiration, in the format specified in settings
 */
function rcp_set_expiration_date( $user_id = 0, $new_date = '' ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->set_expiration_date( $new_date );
}

/*
* Gets the date of a user's expiration in a unix time stamp
* @param int $user_id - the ID of the user to return the subscription level of
* return mixed - Timestamp of expiration of false if no expiration
*/
function rcp_get_expiration_timestamp( $user_id ) {
	$expiration = get_user_meta( $user_id, 'rcp_expiration', true );
	return $expiration && $expiration !== 'none' ? strtotime( $expiration ) : false;
}

/*
* Gets the status of a user's subscription. If a user is expired, this will update their status to "expired"
* @param int $user_id - the ID of the user to return the subscription level of
* return string - The status of the user's subscription
*/
function rcp_get_status( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->get_status();
}

/*
* Gets a user's subscription status in a nice format that is localized
* @param int $user_id - the ID of the user to return the subscription level of
* return string - The user's subscription status
*/
function rcp_print_status( $user_id = 0, $echo = true  ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$status = rcp_get_status( $user_id );
	switch ( $status ) :

		case 'active';
			$print_status = __( 'Active', 'rcp' );
		break;
		case 'expired';
			$print_status = __( 'Expired', 'rcp' );
		break;
		case 'pending';
			$print_status = __( 'Pending', 'rcp' );
		break;
		case 'cancelled';
			$print_status = __( 'Cancelled', 'rcp' );
		break;
		default:
			$print_status = __( 'Free', 'rcp' );
		break;

	endswitch;

	if( $echo ) {
		echo $print_status;
	}

	return $print_status;
}

/*
* Sets a user's status to the specified status
* @param int $user_id - the ID of the user to return the subscription level of
* @param string $new_status - the status to set the user to
* return bool - TRUE on a successful status change, false otherwise
*/
function rcp_set_status( $user_id = 0, $new_status = '' ) {

	if( empty( $user_id ) || empty( $new_status ) ) {
		return false;
	}

	$member = new RCP_Member( $user_id );
	return $member->set_status( $new_status );

}

/*
* Gets the user's unique subscription key
* @param int $user_id - the ID of the user to return the subscription level of
* return string/bool - string if the the key is retrieved successfully, false on failure
*/
function rcp_get_subscription_key( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->get_subscription_key();
}

/*
* Checks whether a user has trialed
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user has trialed, false otherwise
*/
function rcp_has_used_trial( $user_id = 0) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->has_trialed();

}


/**
 * Checks if a user is currently trialing
 *
 * @access      public
 * @since       1.5
 * @return      bool
 */
function rcp_is_trialing( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$member = new RCP_Member( $user_id );
	return $member->is_trialing();

}


// prints payment history for the specified user
function rcp_print_user_payments( $user_id ) {
	$payments = new RCP_Payments;
	$user_payments = $payments->get_payments( array( 'user_id' => $user_id ) );
	$payments_list = '';
	if( $user_payments ) :
		foreach( $user_payments as $payment ) :
			$transaction_id = ! empty( $payment->transaction_id ) ? $payment->transaction_id : '';
			$payments_list .= '<ul class="rcp_payment_details">';
				$payments_list .= '<li>' . __( 'Date', 'rcp' ) . ': ' . $payment->date . '</li>';
				$payments_list .= '<li>' . __( 'Subscription', 'rcp' ) . ': ' . $payment->subscription . '</li>';
				$payments_list .= '<li>' . __( 'Payment Type', 'rcp' ) . ': ' . $payment->payment_type . '</li>';
				$payments_list .= '<li>' . __( 'Subscription Key', 'rcp' ) . ': ' . $payment->subscription_key . '</li>';
				$payments_list .= '<li>' . __( 'Transaction ID', 'rcp' ) . ': ' . $transaction_id . '</li>';
				if( $payment->amount != '' ) {
					$payments_list .= '<li>' . __( 'Amount', 'rcp' ) . ': ' . rcp_currency_filter( $payment->amount ) . '</li>';
				} else {
					$payments_list .= '<li>' . __( 'Amount', 'rcp' ) . ': ' . rcp_currency_filter( $payment->amount2 ) . '</li>';
				}
			$payments_list .= '</ul>';
		endforeach;
	else :
		$payments_list = '<p class="rcp-no-payments">' . __( 'No payments recorded', 'rcp' ) . '</p>';
	endif;
	return $payments_list;
}

/**
 * Retrieve the payments for a specific user
 *
 * @since       v1.5
 * @access      public
 * @param       $user_id INT the ID of the user to get payments for
 * @return      array
*/
function rcp_get_user_payments( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$payments = new RCP_Payments;
	return $payments->get_payments( array( 'user_id' => $user_id ) );
}


// returns the role of the specified user
function rcp_get_user_role( $user_id ) {

	global $wpdb;

	$user = new WP_User( $user_id );
	$capabilities = $user->{$wpdb->prefix . 'capabilities'};

	if ( !isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	$user_role = '';

	if( ! empty( $capabilities ) ) {
		foreach ( $wp_roles->role_names as $role => $name ) {

			if ( array_key_exists( $role, $capabilities ) ) {
				$user_role = $role;
			}
		}
	}

	return $user_role;
}

/**
 * Inserts a new note for a user
 *
 * @access      public
 * @since       2.0
 * @return      void
 */
function rcp_add_member_note( $user_id = 0, $note = '' ) {
	$notes = get_user_meta( $user_id, 'rcp_notes', true );
	if( ! $notes ) {
		$notes = '';
	}
	$notes .= "\n\n" . date_i18n( 'F j, Y H:i:s', current_time( 'timestamp' ) ) . ' - ' . $note;

	update_user_meta( $user_id, 'rcp_notes', wp_kses( $notes, array() ) );
}


/**
 * Determine if it's possible to upgrade a user's subscription
 *
 * @since       v1.5
 * @access      public
 * @param       $user_id INT the ID of the user to check
 * @return      bool
*/

function rcp_subscription_upgrade_possible( $user_id = 0 ) {

	if( empty( $user_id ) )
		$user_id = get_current_user_id();

	$ret = false;

	if( ( ! rcp_is_active( $user_id ) || ! rcp_is_recurring( $user_id ) ) && rcp_has_paid_levels() )
		$ret = true;

	return (bool) apply_filters( 'rcp_can_upgrade_subscription', $ret, $user_id );
}


/**
 * Determine if a member is a PayPal subscriber
 *
 * @since       v2.0
 * @access      public
 * @param       $user_id INT the ID of the user to check
 * @return      bool
*/
function rcp_is_paypal_subscriber( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret        = false;
	$member     = new RCP_Member( $user_id );
	$profile_id = $member->get_payment_profile_id();

	// Check if the member is a PayPal customer
	if( false !== strpos( $profile_id, 'I-' ) ) {

		$ret = true;

	} else {

		// The old way of identifying PayPal subscribers
		$ret = (bool) get_user_meta( $user_id, 'rcp_paypal_subscriber', true );

	}

	return (bool) apply_filters( 'rcp_is_paypal_subscriber', $ret, $user_id );
}

/**
 * Determine if a member is a Stripe subscriber
 *
 * @since       v2.1
 * @access      public
 * @param       $user_id INT the ID of the user to check
 * @return      bool
*/
function rcp_is_stripe_subscriber( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret = false;

	$member = new RCP_Member( $user_id );

	$profile_id = $member->get_payment_profile_id();

	// Check if the member is a Stripe customer
	if( false !== strpos( $profile_id, 'cus_' ) ) {

		$ret = true;

	} 

	return (bool) apply_filters( 'rcp_is_stripe_subscriber', $ret, $user_id );
}


/**
 * Process Profile Updater Form
 *
 * Processes the profile updater form by updating the necessary fields
 *
 * @access      private
 * @since       1.5
*/
function rcp_process_profile_editor_updates() {

	// Profile field change request
	if ( empty( $_POST['rcp_action'] ) || $_POST['rcp_action'] !== 'edit_user_profile' || !is_user_logged_in() )
		return false;


	// Nonce security
	if ( ! wp_verify_nonce( $_POST['rcp_profile_editor_nonce'], 'rcp-profile-editor-nonce' ) )
		return false;

	$user_id      = get_current_user_id();
	$old_data     = get_userdata( $user_id );

	$display_name = ! empty( $_POST['rcp_display_name'] ) ? sanitize_text_field( $_POST['rcp_display_name'] ) : '';
	$first_name   = ! empty( $_POST['rcp_first_name'] )   ? sanitize_text_field( $_POST['rcp_first_name'] )   : '';
	$last_name    = ! empty( $_POST['rcp_last_name'] )    ? sanitize_text_field( $_POST['rcp_last_name'] )    : '';
	$email        = ! empty( $_POST['rcp_email'] )        ? sanitize_text_field( $_POST['rcp_email'] )        : '';

	$userdata = array(
		'ID'           => $user_id,
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'display_name' => $display_name,
		'user_email'   => $email
	);

	// Empty email
	if ( empty( $email ) || ! is_email( $email ) ) {
		rcp_errors()->add( 'empty_email', __( 'Please enter a valid email address', 'rcp' ) );
	}

	// Make sure the new email doesn't belong to another user
	if( $email != $old_data->user_email && email_exists( $email ) ) {
		rcp_errors()->add( 'email_exists', __( 'The email you entered belongs to another user. Please use another.', 'rcp' ) );
	}

	// New password
	if ( ! empty( $_POST['rcp_new_user_pass1'] ) ) {
		if ( $_POST['rcp_new_user_pass1'] !== $_POST['rcp_new_user_pass2'] ) {
			rcp_errors()->add( 'password_mismatch', __( 'The passwords you entered do not match. Please try again.', 'rcp' ) );
		} else {
			$userdata['user_pass'] = $_POST['rcp_new_user_pass1'];
		}
	}

	// retrieve all error messages, if any
	$errors = rcp_errors()->get_error_messages();

	// only create the user if there are no errors
	if( empty( $errors ) ) {

		// Update the user
		$updated = wp_update_user( $userdata );

		if( $updated ) {
			do_action( 'rcp_user_profile_updated', $user_id, $userdata );

			wp_safe_redirect( add_query_arg( 'updated', 'true', sanitize_text_field( $_POST['rcp_redirect'] ) ) );

			exit;
		} else {
			rcp_errors()->add( 'not_updated', __( 'There was an error updating your profile. Please try again.', 'rcp' ) );
		}
	}
}
add_action( 'init', 'rcp_process_profile_editor_updates' );

/**
 * Change a user password
 *
 * @access      public
 * @since       1.0
 */
function rcp_change_password() {
	// reset a users password
	if( isset( $_POST['rcp_action'] ) && $_POST['rcp_action'] == 'reset-password' ) {

		global $user_ID;

		if( !is_user_logged_in() )
			return;

		if( wp_verify_nonce( $_POST['rcp_password_nonce'], 'rcp-password-nonce' ) ) {

			do_action( 'rcp_before_password_form_errors', $_POST );

			if( $_POST['rcp_user_pass'] == '' || $_POST['rcp_user_pass_confirm'] == '' ) {
				// password(s) field empty
				rcp_errors()->add( 'password_empty', __( 'Please enter a password, and confirm it', 'rcp' ), 'password' );
			}
			if( $_POST['rcp_user_pass'] != $_POST['rcp_user_pass_confirm'] ) {
				// passwords do not match
				rcp_errors()->add( 'password_mismatch', __( 'Passwords do not match', 'rcp' ), 'password' );
			}

			do_action( 'rcp_password_form_errors', $_POST );

			// retrieve all error messages, if any
			$errors = rcp_errors()->get_error_messages();

			if( empty( $errors ) ) {
				// change the password here
				$user_data = array(
					'ID' 		=> $user_ID,
					'user_pass' => $_POST['rcp_user_pass']
				);
				wp_update_user( $user_data );
				// send password change email here (if WP doesn't)
				wp_safe_redirect( add_query_arg( 'password-reset', 'true', $_POST['rcp_redirect'] ) );
				exit;
			}
		}
	}
}
add_action( 'init', 'rcp_change_password' );

/**
 * Process a member cancellation request
 *
 * @access      public
 * @since       2.1
 */
function rcp_process_member_cancellation() {

	if( ! isset( $_GET['rcp-action'] ) || $_GET['rcp-action'] !== 'cancel' ) {
		return;
	}

	if( ! is_user_logged_in() ) {
		return;
	}

	if( wp_verify_nonce( $_GET['_wpnonce'], 'rcp-cancel-nonce' ) ) {

		global $rcp_options;

		$success  = rcp_cancel_member_payment_profile( get_current_user_id() );
		$redirect = remove_query_arg( array( 'rcp-action', '_wpnonce', 'member-id' ), rcp_get_current_url() );

		if( ! $success && rcp_is_paypal_subscriber() ) {
			// No profile ID stored, so redirect to PayPal to cancel manually
			$redirect = 'https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_manage-paylist';
		}

		if( $success ) {

			do_action( 'rcp_process_member_cancellation', get_current_user_id() );

			$redirect = add_query_arg( 'profile', 'cancelled', $redirect );

		}
	
		wp_redirect( $redirect ); exit;

	}
}
add_action( 'init', 'rcp_process_member_cancellation' );

/**
 * Cancel a member's payment profile
 *
 * @access      public
 * @since       2.1
 */
function rcp_cancel_member_payment_profile( $member_id = 0 ) {

	global $rcp_options;

	$success  = false;
	$member   = new RCP_Member( $member_id );

	if( ! rcp_can_member_cancel( $member_id ) ) {
		return $success;
	}

	if( rcp_is_stripe_subscriber( $member_id ) ) {

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

			$cu = \Stripe\Customer::retrieve( $member->get_payment_profile_id() );
			$cu->cancelSubscription( array( 'at_period_end' => false ) );

			$success = true;

		} catch (\Stripe\Error\InvalidRequest $e) {

			// Invalid parameters were supplied to Stripe's API
			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = "<h4>" . __( 'An error occurred', 'rcp' ) . "</h4>";
			if( isset( $err['code'] ) ) {
				$error .= "<p>" . __( 'Error code:', 'rcp' ) . " " . $err['code'] ."</p>";
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Error\Authentication $e) {

			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = "<h4>" . __( 'An error occurred', 'rcp' ) . "</h4>";
			if( isset( $err['code'] ) ) {
				$error .= "<p>" . __( 'Error code:', 'rcp' ) . " " . $err['code'] ."</p>";
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Error\ApiConnection $e) {

			// Network communication with Stripe failed

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = "<h4>" . __( 'An error occurred', 'rcp' ) . "</h4>";
			if( isset( $err['code'] ) ) {
				$error .= "<p>" . __( 'Error code:', 'rcp' ) . " " . $err['code'] ."</p>";
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (\Stripe\Error\Base $e) {

			// Display a very generic error to the user

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = "<h4>" . __( 'An error occurred', 'rcp' ) . "</h4>";
			if( isset( $err['code'] ) ) {
				$error .= "<p>" . __( 'Error code:', 'rcp' ) . " " . $err['code'] ."</p>";
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		} catch (Exception $e) {

			// Something else happened, completely unrelated to Stripe

			$error = "<h4>" . __( 'An error occurred', 'rcp' ) . "</h4>";
			$error .= print_r( $e, true );

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => 401 ) );

		}

	} elseif( rcp_is_paypal_subscriber( $member_id ) ) {

		if( rcp_has_paypal_api_access() && $member->get_payment_profile_id() ) {

			// Set PayPal API key credentials.
			$api_username  = isset( $rcp_options['sandbox'] ) ? 'test_paypal_api_username' : 'live_paypal_api_username';
			$api_password  = isset( $rcp_options['sandbox'] ) ? 'test_paypal_api_password' : 'live_paypal_api_password';
			$api_signature = isset( $rcp_options['sandbox'] ) ? 'test_paypal_api_signature' : 'live_paypal_api_signature';
			$api_endpoint  = isset( $rcp_options['sandbox'] ) ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';

			$args = array(
				'USER'      => $rcp_options[ $api_username ],
				'PWD'       => $rcp_options[ $api_password ],
				'SIGNATURE' => $rcp_options[ $api_signature ],
				'VERSION'   => '76.0',
				'METHOD'    => 'ManageRecurringPaymentsProfileStatus',
				'PROFILEID' => $member->get_payment_profile_id(),
				'ACTION'    => 'Cancel'
			);

			$error_msg = '';
			$request   = wp_remote_post( $api_endpoint, array( 'body' => $args, 'timeout' => 30 ) );

			if ( is_wp_error( $request ) ) {

				$success   = false;
				$error_msg = $request->get_error_message();

			} else {

				$body = wp_remote_retrieve_body( $request );
				if( is_string( $body ) ) {
					wp_parse_str( $body, $body );
				}

				if( empty( $request['response'] ) ) {
					$success = false;
				}

				if( empty( $request['response']['code'] ) || 200 !== (int) $request['response']['code'] ) {
					$success = false;
				}

				if( empty( $request['response']['message'] ) || 'OK' !== $request['response']['message'] ) {
					$success = false;
				}

				if( isset( $body['ACK'] ) && 'success' === strtolower( $body['ACK'] ) ) {
					$success = true;
				} else {
					$success = false;
					if( isset( $body['L_LONGMESSAGE0'] ) ) {
						$error_msg = $body['L_LONGMESSAGE0'];
					}
				}

			}

			if( ! $success ) {
				wp_die( sprintf( __( 'There was a problem cancelling the subscription, please contact customer support. Error: %s', 'rcp' ), $error_msg ), array( 'response' => 400 ) );
			}

		}

	}

	if( $success ) {
		$member->cancel();
	}

	return $success;
}

/**
 * Updates member payment profile ID meta keys with old versions from pre 2.1 gateways
 *
 * @access      public
 * @since       2.1
 */
function rcp_backfill_payment_profile_ids( $profile_id, $user_id, $member_object ) {

	if( empty( $profile_id ) ) {

		// Check for Stripe
		$profile_id = get_user_meta( $user_id, '_rcp_stripe_user_id', true );

		if( ! empty( $profile_id ) ) {

			$member_object->set_payment_profile_id( $profile_id );

		} else {

			// Check for PayPal
			$profile_id = get_user_meta( $user_id, 'rcp_recurring_payment_id', true );

			if( ! empty( $profile_id ) ) {

				$member_object->set_payment_profile_id( $profile_id );

			}

		}

	}

	return $profile_id;
}
add_filter( 'rcp_member_get_payment_profile_id', 'rcp_backfill_payment_profile_ids', 10, 3 );

/**
 * Retrieves the member's ID from their payment profile ID
 *
 * @access      public
 * @since       2.1
 * @return      int
 */
function rcp_get_member_id_from_profile_id( $profile_id = '' ) {

	global $wpdb;

	$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'rcp_payment_profile_id' AND meta_value = %s LIMIT 1", $profile_id ) );

	if ( $user_id != NULL ) {
		return $user_id;
	}

	return false;
}

/**
 * Determines if a member can cancel their subscription on site
 *
 * @access      public
 * @since       2.1
 */
function rcp_can_member_cancel( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret    = false;
	$member = new RCP_Member( $user_id );

	if( $member->is_recurring() && $member->is_active() && 'cancelled' !== $member->get_status() ) {

		$profile_id = $member->get_payment_profile_id();

		// Check if the member is a Stripe customer
		if( false !== strpos( $profile_id, 'cus_' ) ) {

			$ret = true;

		} elseif ( rcp_is_paypal_subscriber( $user_id ) && rcp_has_paypal_api_access() ) {

			$ret = true;

		}

	}

	return apply_filters( 'rcp_member_can_cancel', $ret, $user_id );
}

/**
 * Gets the cancellation URL for a member
 *
 * @access      public
 * @since       2.1
 */
function rcp_get_member_cancel_url( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$url    = '';
	$member = new RCP_Member( $user_id );

	if( $member->is_recurring() ) {

		$url = wp_nonce_url( add_query_arg( array( 'rcp-action' => 'cancel', 'member-id' => $user_id ) ), 'rcp-cancel-nonce' );

	}

	return apply_filters( 'rcp_member_cancel_url', $url, $user_id );
}

/**
 * Determines if a member can update the credit / debit card attached to their account
 *
 * @access      public
 * @since       2.1
 */
function rcp_member_can_update_billing_card( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret    = false;
	$member = new RCP_Member( $user_id );

	if( $member->is_recurring() ) {

		$profile_id = $member->get_payment_profile_id();

		// Check if the member is a Stripe customer
		if( false !== strpos( $profile_id, 'cus_' ) ) {

			$ret = true;

		}

	}

	return apply_filters( 'rcp_member_can_update_billing_card', $ret, $user_id );
}

/**
 * Wrapper for RCP_Member->get_switch_to_url()
 *
 * @access public
 * @since 2.1
 */
function rcp_get_switch_to_url( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		return;
	}

	$member = new RCP_Member( $user_id );
	return $member->get_switch_to_url();

}

/**
 * Validate a potential username
 *
 * @access      public
 * @since       2.2
 * @param       string $username The username to validate
 * @return      bool
 */
function rcp_validate_username( $username = '' ) {
	$sanitized = sanitize_user( $username, false );
	$valid = ( $sanitized == $username );
	return (bool) apply_filters( 'rcp_validate_username', $valid, $username );
}
