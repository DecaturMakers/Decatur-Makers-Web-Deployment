<?php

/**
 * Deprecated functions
 *
 * These are kept here for backwards compatibility with extensions that might be using them
 *
 * @since 1.5
*/



/**
 * Retrieve all payments from database
 *
 * @deprecated  1.5
 * @access      private
 * @param       $offset INT The number to skip
 * @param       $number INT The number to retrieve
 * @return      array
*/

function rcp_get_payments( $offset = 0, $number = 20 ) {
	global $wpdb, $rcp_payments_db_name;
	if( $number > 0 ) {
		$payments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->escape( $rcp_payments_db_name ) . " ORDER BY id DESC LIMIT %d,%d;", absint( $offset ), absint( $number ) ) );
	} else {
		// when retrieving all payments, the query is cached
		$payments = get_transient( 'rcp_payments' );
		if( $payments === false ) {
			$payments = $wpdb->get_results( "SELECT * FROM " . $wpdb->escape( $rcp_payments_db_name ) . " ORDER BY id DESC;" ); // this is to get all payments
			set_transient( 'rcp_payments', $payments, 10800 );
		}
	}
	return $payments;
}


/**
 * Retrieve the total number of payments in the database
 *
 * @deprecated  1.5
 * @access      private
 * @return      int
*/
function rcp_count_payments() {
	global $wpdb, $rcp_payments_db_name;
	$count = get_transient( 'rcp_payments_count' );
	if( $count === false ) {
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM " . $rcp_payments_db_name . ";" );
		set_transient( 'rcp_payments_count', $count, 10800 );
	}
	return $count;
}


/**
 * Retrieve total site earnings
 *
 * @deprecated  1.5
 * @access      private
 * @return      float
*/

function rcp_get_earnings() {
	global $wpdb, $rcp_payments_db_name;
	$payments = get_transient( 'rcp_earnings' );
	if( $payments === false ) {
		$payments = $wpdb->get_results( "SELECT amount FROM " . $rcp_payments_db_name . ";" );
		// cache the payments query
		set_transient( 'rcp_earnings', $payments, 10800 );
	}
	$total = (float) 0.00;
	if( $payments ) :
		foreach( $payments as $payment ) :
			$total = $total + $payment->amount;
		endforeach;
	endif;
	return $total;
}


/**
 * Insert a payment into the database
 *
 * @deprecated  1.5
 * @access      private
 * @param       $payment_data ARRAY The data to store
 * @return      INT the ID of the new payment, or false if insertion fails
*/

function rcp_insert_payment( $payment_data = array() ) {
	global $wpdb, $rcp_payments_db_name;

	$amount = $payment_data['amount'];
	if( $payment_data['amount'] == '' )
		$amount = $payment_data['amount2'];

	if( rcp_check_for_existing_payment( $payment_data['payment_type'], $payment_data['date'], $payment_data['subscription_key'] ) )
		return;

	$wpdb->insert(
		$rcp_payments_db_name,
		array(
			'subscription' 		=> $payment_data['subscription'],
			'date' 				=> $payment_data['date'],
			'amount' 			=> $amount,
			'user_id' 			=> $payment_data['user_id'],
			'payment_type' 		=> $payment_data['payment_type'],
			'subscription_key' 	=> $payment_data['subscription_key']
		),
		array(
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s'
		)
	);

	// if insert was succesful, return the payment ID
	if( $wpdb->insert_id ) {
		// clear the payment caches
		delete_transient( 'rcp_payments' );
		delete_transient( 'rcp_earnings' );
		delete_transient( 'rcp_payments_count' );
		do_action( 'rcp_insert_payment', $wpdb->insert_id, $payment_data, $amount );
		return $wpdb->insert_id;
	}
	// return false if payment wasn't recorded
	return false;
}


/**
 * Check if a payment already exists
 *
 * @deprecated  1.5
 * @access      private
 * @param       $type string The type of payment (web_accept, subscr_payment, Credit Card, etc)
 * @param       $date string/date The date of tpaen
 * @param       $subscriptionkey string The subscription key the payment is connected to
 * @return      bool
*/

function rcp_check_for_existing_payment( $type, $date, $subscription_key ) {

	global $wpdb, $rcp_payments_db_name;

	if( $wpdb->get_results( $wpdb->prepare("SELECT id FROM " . $rcp_payments_db_name . " WHERE `date`='%s' AND `subscription_key`='%s' AND `payment_type`='%s';", $date, $subscription_key, $type ) ) )
		return true; // this payment already exists

	return false; // this payment doesn't exist
}


/**
 * Retrieves the amount for the lat payment made by a user
 *
 * @access      private
 * @param       $user_id INT The ID of the user to retrieve a payment amount for
 * @return      float
*/

function rcp_get_users_last_payment_amount( $user_id = 0 ) {
	global $wpdb, $rcp_payments_db_name;
	$query = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $rcp_payments_db_name . " WHERE `user_id`='%d' ORDER BY id DESC LIMIT 1;", $user_id ) );
	return $query[0]->amount;
}