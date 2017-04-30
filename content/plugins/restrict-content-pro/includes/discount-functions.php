<?php

/****************************************
* Functions for getting non-member
* specific info about discount codes
*****************************************/


/*
* Retrieves all discount codes
*
* return object/bool
*/

function rcp_get_discounts() {
	$discounts_db = new RCP_Discounts();
	$discounts    = $discounts_db->get_discounts();
	if( $discounts ) {
		return $discounts;
	}
	return false;
}


/*
* Check if we have any discounts
*
* return bool
*/

function rcp_has_discounts() {
	$discounts = new RCP_Discounts();
	if( $discounts->get_discounts( array( 'status' => 'active' )) )
		return true;
	return false;
}


/*
* returns the DB object for a discount code
* @param int $id - the ID number of the discount to retrieve data for
* return object
*/
function rcp_get_discount_details( $id ) {
	global $wpdb, $rcp_discounts_db_name;
	$code = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $rcp_discounts_db_name . " WHERE id='%d';", $id ) );
	return $code[0];
}

/*
* returns the DB object for a discount code, based on the code provided
* @param string $code - the discount code to retrieve all information for
* return object
*/
function rcp_get_discount_details_by_code( $code ) {
	global $wpdb, $rcp_discounts_db_name;
	$code = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $rcp_discounts_db_name . " WHERE code='%s';", $code ) );
	return $code[0];
}

/*
* Check whether a discount code is valid
* @param - string $code - the discount code to validate
* return boolean
*/
function rcp_validate_discount( $code, $subscription_id = 0 ) {

	$ret       = false;
	$discounts = new RCP_Discounts();
	$discount  = $discounts->get_by( 'code', $code );

	if( ! empty( $discount ) && $discount->status == 'active' ) {

		// Make sure discount is not expired and not maxed out
		if( ! $discounts->is_expired( $discount->id ) && ! $discounts->is_maxed_out( $discount->id ) ) {
			$ret = true;
		}

		// If the discount is restricted to a level, ensure that's the level being signed up for
		if( $discounts->has_subscription_id( $discount->id ) ) {
			if( $subscription_id != $discounts->get_subscription_id( $discount->id ) ) {
				$ret = false;
			}
		}

		// Ensure codes are identical, including case
		if( strcmp( $code, $discount->code ) != 0 ) {
			$ret = false;
		}

	}

	return apply_filters( 'rcp_is_discount_valid', $ret, $discount, $subscription_id );
}


/*
* Get the status of a discount code
* @param - string $code_id - the discount code ID to validate
* return string on success, false on failure
*/
function rcp_get_discount_status( $code_id ) {
	global $wpdb, $rcp_discounts_db_name;

	$code = rcp_get_discount_details( $code_id );
	if( $code ) {
		return $code->status;
	}
	return false;
}

/*
* Checks whether a discount code has uses left
* @param - string $code_id - the discount code ID to check
* return true if uses left, false otherwise
*/
function rcp_discount_has_uses_left( $code_id ) {
	global $wpdb, $rcp_discounts_db_name;

	$usage = $wpdb->get_results( $wpdb->prepare( "SELECT `use_count`, `max_uses` FROM " . $rcp_discounts_db_name . " WHERE id='%d';", $code_id ) );

	if( $usage ) {
		$use_count = $usage[0]->use_count;
		$max_uses = $usage[0]->max_uses;
		if( $max_uses > 0 ) {
			if( $use_count < $max_uses ) {
				return true;
			}
		} else {
			// this code has unlimited uses
			return true;
		}
	}
	return false;
}

/*
* Checks whether a discount code is expired
* @param - int $code_id - the discount code ID to validate
* return true if not expired, false if expired
*/
function rcp_is_discount_not_expired( $code_id ) {
	global $wpdb, $rcp_discounts_db_name;
	$expiration = $wpdb->get_results( $wpdb->prepare( "SELECT expiration FROM " . $rcp_discounts_db_name . " WHERE id='%d';", $code_id ) );

	// if no expiration is set, return true
	if( $expiration[0]->expiration == '' )
		return true;

	if( $expiration ) {
		if ( strtotime( 'NOW' ) < strtotime( $expiration[0]->expiration ) ) {
			return true;
		}
	}
	return false;
}


/*
* Calculates a subscription price after discount
* @param - float $base_price - the original subscription price
* @param - float $amount - the discount amount
* @param - string $type - the kind of discount, either % or flat
* return float
*/
function rcp_get_discounted_price( $base_price, $amount, $type ) {

	if( $type == '%' ) {
		$discounted_price = $base_price - ( $base_price * ( $amount / 100 ) );
	} elseif($type == 'flat') {
		$discounted_price = $base_price - $amount;
	}

	return number_format( (float) $discounted_price, 2 );
}


/*
* Stores a discount code in a user's history
*
* @param string $code - the discount code to store
* @param int $user_id - the ID of the user to store the discount for
* @param object $discount_object - the object containing all info about the discount
* return void
*/
function rcp_store_discount_use_for_user( $code, $user_id, $discount_object ) {

	$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true) ;

	if( !is_array( $user_discounts ) )
		$user_discounts = array();

	$user_discounts[] = $code;

	do_action( 'rcp_pre_store_discount_for_user', $code, $user_id, $discount_object );

	update_user_meta( $user_id, 'rcp_user_discounts', $user_discounts );

	do_action( 'rcp_store_discount_for_user', $code, $user_id, $discount_object );

}


/*
* Checks whether a user has used a particular discount code
* This is used to preventing users from spamming discount codes
* @param int $user_id - the ID of the user to checl
* @param string $code - the discount code to check against the user ID
* return boolean
*/
function rcp_user_has_used_discount( $user_id, $code ) {
	
	$ret = false;

	if( ! empty( $code ) ) {

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );
		
		if( ! empty( $user_discounts ) ) {
			if( in_array( $code, $user_discounts ) ) {
				$ret = true;
			}
		}
	}

	return apply_filters( 'rcp_user_has_used_discount', $ret, $user_id, $code );
}

/*
* Increase the usage count of a discount code
* @param int $code - the ID of the discount
*/
function rcp_increase_code_use( $code_id ) {
	global $wpdb, $rcp_discounts_db_name;
	// add the post ID to the count database if it doesn't already exist
	if( ! $wpdb->query( $wpdb->prepare( "SELECT `use_count` FROM `" . $rcp_discounts_db_name . "` WHERE id='%d';", $code_id ) ) ) {
		$increase_count = $wpdb->insert( $rcp_discounts_db_name,
			array(
				'id' => $code_id,
				'use_count' => 1
			)
		);
	} else {
		$count = $wpdb->query( $wpdb->prepare( "UPDATE " . $rcp_discounts_db_name . " SET use_count = use_count + 1 WHERE id='%d';", $code_id ) );
	}
}

/*
* Returns the number of times a discount code has been used
* @param int/string $code - the ID or code of the discount
* return The number of times the discount code has been used
*/
function rcp_count_discount_code_uses( $code ) {
	global $wpdb, $rcp_discounts_db_name;
	if( is_int( $code ) ) {
		// discount ID has been given
		$count = $wpdb->get_results( $wpdb->prepare( "SELECT use_count FROM " . $rcp_discounts_db_name . " WHERE id='%d';", $code ) );
	} else {
		// discount code has been given
		$count = $wpdb->get_results( $wpdb->prepare( "SELECT use_count FROM " . $rcp_discounts_db_name . " WHERE code='%s';", $code ) );
	}
	if($count)
		return $count[0]->use_count;
	else
		return __( 'None', 'rcp' );
}

function rcp_discount_sign_filter( $amount, $type ) {
	$discount = '';
	if( $type == '%' ) {
		$discount = $amount . '%';
	} elseif( $type == 'flat' ) {
		$discount = rcp_currency_filter( $amount );
	}
	return $discount;
}

function rcp_check_paypal_return_price_after_discount( $price, $amount, $amount2, $user_id ) {
	// get an array of all discount codes this user has used
	$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );
	if( !is_array( $user_discounts ) || $user_discounts == '' ) {
		// this user has never used a discount code
		return false;
	}
	foreach( $user_discounts as $discount_code ) {
		if( !rcp_validate_discount( $discount_code ) ) {
			// discount code is inactive
			return false;
		}
		$code_details = rcp_get_discount_details_by_code( $discount_code );
		$discounted_price = rcp_get_discounted_price( $price, $code_details->amount, $code_details->unit );
		if( $discounted_price == $amount || $discounted_price == $amount2 ) {
			return true;
		}
	}
	return false;
}