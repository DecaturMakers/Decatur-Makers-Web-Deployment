<?php


/**
 * Checks whether the post is Paid Only
 *
 * @access      private
 * @return      bool
*/

function rcp_is_paid_content( $post_id ) {
	if ( $post_id == '' || !is_int( $post_id ) )
		$post_id = get_the_ID();

	$return = false;

	$is_paid = get_post_meta( $post_id, '_is_paid', true );
	if ( $is_paid ) {
		// this post is for paid users only
		$return = true;
	}

	return (bool) apply_filters( 'rcp_is_paid_content', $return, $post_id );
}


/**
 * Retrieve a list of all Paid Only posts
 *
 * @access      public
 * @return      array
*/

function rcp_get_paid_posts() {
	$args = array(
		'meta_key' => '_is_paid',
		'meta_value' => 1,
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'post_type' => 'any',
		'fields' => 'ids'
	);
	$paid_ids = get_posts( $args );
	if ( $paid_ids ) {
		return $paid_ids;
	}

	return array();
}


/**
 * Apply the currency sign to a price
 *
 * @access      public
 * @return      string
*/

function rcp_currency_filter( $price ) {
	global $rcp_options;

	$currency = isset( $rcp_options['currency'] ) ? $rcp_options['currency'] : 'USD';
	$position = isset( $rcp_options['currency_position'] ) ? $rcp_options['currency_position'] : 'before';
	if ( $position == 'before' ) :
		switch ( $currency ) :
		case "GBP" : return '&pound;' . $price; break;
		case "USD" :
		case "AUD" :
		case "BRL" :
		case "CAD" :
		case "HKD" :
		case "MXN" :
		case "SGD" :
			return '&#36;' . $price;
			break;
		case "JPY" : return '&yen;' . $price; break;
		default :
			$formatted = $currency . ' ' . $price;
			return apply_filters( 'rcp_' . strtolower( $currency ) . '_currency_filter_before', $formatted, $currency, $price );
			break;
			endswitch;
			else :
				switch ( $currency ) :
				case "GBP" : return $price . '&pound;'; break;
		case "USD" :
		case "AUD" :
		case "BRL" :
		case "CAD" :
		case "HKD" :
		case "MXN" :
		case "SGD" :
			return $price . '&#36;';
			break;
		case "JPY" : return $price . '&yen;'; break;
		default :
	$formatted = $price . ' ' . $currency;
	return apply_filters( 'rcp_' . strtolower( $currency ) . '_currency_filter_after', $formatted, $currency, $price );
	break;
	endswitch;
	endif;
}


/**
 * Get the currency list
 *
 * @access      private
 * @return      array
*/

function rcp_get_currencies() {
	$currencies = array(
		'USD' => __( 'US Dollars (&#36;)', 'rcp' ),
		'EUR' => __( 'Euros (&euro;)', 'rcp' ),
		'GBP' => __( 'Pounds Sterling (&pound;)', 'rcp' ),
		'AUD' => __( 'Australian Dollars (&#36;)', 'rcp' ),
		'BRL' => __( 'Brazilian Real (&#36;)', 'rcp' ),
		'CAD' => __( 'Canadian Dollars (&#36;)', 'rcp' ),
		'CZK' => __( 'Czech Koruna', 'rcp' ),
		'DKK' => __( 'Danish Krone', 'rcp' ),
		'HKD' => __( 'Hong Kong Dollar (&#36;)', 'rcp' ),
		'HUF' => __( 'Hungarian Forint', 'rcp' ),
		'ILS' => __( 'Israeli Shekel', 'rcp' ),
		'JPY' => __( 'Japanese Yen (&yen;)', 'rcp' ),
		'MYR' => __( 'Malaysian Ringgits', 'rcp' ),
		'MXN' => __( 'Mexican Peso (&#36;)', 'rcp' ),
		'NZD' => __( 'New Zealand Dollar (&#36;)', 'rcp' ),
		'NOK' => __( 'Norwegian Krone', 'rcp' ),
		'PHP' => __( 'Philippine Pesos', 'rcp' ),
		'PLN' => __( 'Polish Zloty', 'rcp' ),
		'SGD' => __( 'Singapore Dollar (&#36;)', 'rcp' ),
		'SEK' => __( 'Swedish Krona', 'rcp' ),
		'CHF' => __( 'Swiss Franc', 'rcp' ),
		'TWD' => __( 'Taiwan New Dollars', 'rcp' ),
		'THB' => __( 'Thai Baht', 'rcp' ),
		'TRY' => __( 'Turkish Lira', 'rcp' ),
		'RIAL'=> __( 'Iranian Rial (&#65020;)', 'rcp' ),
		'RUB' => __( 'Russian Rubles', 'rcp' )
	);
	return apply_filters( 'rcp_currencies', $currencies );
}


/**
 * reverse of strstr()
 *
 * @access      private
 * @return      string
*/

function rcp_rstrstr( $haystack, $needle ) {
	return substr( $haystack, 0, strpos( $haystack, $needle ) );
}


/**
 * Is odd?
 *
 * Checks if a number is odd
 *
 * @access      private
 * @return      bool
*/

function rcp_is_odd( $int ) {
	return $int & 1;
}


/*
* Gets the excerpt of a specific post ID or object
* @param - $post - object/int - the ID or object of the post to get the excerpt of
* @param - $length - int - the length of the excerpt in words
* @param - $tags - string - the allowed HTML tags. These will not be stripped out
* @param - $extra - string - text to append to the end of the excerpt
*/

function rcp_excerpt_by_id( $post, $length = 50, $tags = '<a><em><strong><blockquote><ul><ol><li><p>', $extra = ' . . .' ) {

	if ( is_int( $post ) ) {
		// get the post object of the passed ID
		$post = get_post( $post );
	} elseif ( !is_object( $post ) ) {
		return false;
	}
	$more = false;
	if ( has_excerpt( $post->ID ) ) {
		$the_excerpt = $post->post_excerpt;
	} elseif ( strstr( $post->post_content, '<!--more-->' ) ) {
		$more = true;
		$length = strpos( $post->post_content, '<!--more-->' );
		$the_excerpt = $post->post_content;
	} else {
		$the_excerpt = $post->post_content;
	}

	$tags = apply_filters( 'rcp_excerpt_tags', $tags );

	if ( $more ) {
		$the_excerpt = strip_shortcodes( strip_tags( stripslashes( substr( $the_excerpt, 0, $length ) ), $tags ) );
	} else {
		$the_excerpt = strip_shortcodes( strip_tags( stripslashes( $the_excerpt ), $tags ) );
		$the_excerpt = preg_split( '/\b/', $the_excerpt, $length * 2+1 );
		$excerpt_waste = array_pop( $the_excerpt );
		$the_excerpt = implode( $the_excerpt );
		$the_excerpt .= $extra;
	}

	return wpautop( $the_excerpt );
}


/**
 * The default length for excerpts
 *
 * @access      private
 * @return      string
*/

function rcp_excerpt_length( $excerpt_length ) {
	// the number of words to show in the excerpt
	return 100;
}
add_filter( 'rcp_filter_excerpt_length', 'rcp_excerpt_length' );


/**
 * Get current URL
 *
 * Returns the URL to the current page, including detection for https
 *
 * @access      private
 * @return      string
*/

function rcp_get_current_url() {
	global $post;

	if ( is_singular() ) :

		$current_url = get_permalink( $post->ID );

	else :

		$current_url = 'http';
		if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) $current_url .= "s";

		$current_url .= "://";

		if ( $_SERVER["SERVER_PORT"] != "80" ) {
			$current_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$current_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}

	endif;

	return apply_filters( 'rcp_current_url', $current_url );
}


/**
 * Log Types
 *
 * Sets up the valid log types for WP_Logging
 *
 * @access      private
 * @since       1.3.4
 * @return      array
*/

function rcp_log_types( $types ) {

    $types = array(
    	'gateway_error'
    );
    return $types;

}
add_filter( 'wp_log_types', 'rcp_log_types' );


/**
 * Check if "Prevent Account Sharing" is enabled
 *
 * @access      private
 * @since       1.4
 * @return      bool
*/
function rcp_no_account_sharing() {
	global $rcp_options;
	return (bool) apply_filters( 'rcp_no_account_sharing', isset( $rcp_options['no_login_sharing'] ) );
}


/**
 * Stores cookie value in a transient when a user logs in
 *
 * Transient IDs are based on the user ID so that we can track the number of
 * users logged into the same account
 *
 * @access      private
 * @since       1.5
 * @return      void
*/

function rcp_set_user_logged_in_status( $logged_in_cookie, $expire, $expiration, $user_id, $status = 'logged_in' ) {

	if( ! rcp_no_account_sharing() )
		return;

	if ( ! empty( $user_id ) ) :

		$data = get_transient( 'rcp_user_logged_in_' . $user_id );

		if( false === $data )
			$data = array();

		$data[] = $logged_in_cookie;

		set_transient( 'rcp_user_logged_in_' . $user_id, $data );

	endif;
}
add_action( 'set_logged_in_cookie', 'rcp_set_user_logged_in_status', 10, 5 );


/**
 * Removes the current user's auth cookie from the rcp_user_logged_in_# transient when logging out
 *
 * @access      private
 * @since       1.5
 * @return      void
*/

function rcp_clear_auth_cookie() {

	if( ! rcp_no_account_sharing() )
		return;

	$user_id = get_current_user_id();

	$already_logged_in = get_transient( 'rcp_user_logged_in_' . $user_id );

	if( $already_logged_in !== false ) :

		$data = maybe_unserialize( $already_logged_in );

		$key = array_search( $_COOKIE[LOGGED_IN_COOKIE], $data );
		if( false !== $key ) {
			unset( $data[$key] );
			$data = array_values( $data );
			set_transient( 'rcp_user_logged_in_' . $user_id, $data );
		}

	endif;

}
add_action( 'clear_auth_cookie', 'rcp_clear_auth_cookie' );


/**
 * Checks if a user is allowed to be logged-in
 *
 * The transient related to the user is retrieved and the first cookie in the transient
 * is compared to the LOGGED_IN_COOKIE of the current user.
 *
 * The first cookie in the transient is the oldest, so it is the one that gets logged out
 *
 * We only log a user out if there are more than 2 users logged into the same account
 *
 * @access      private
 * @since       1.5
 * @return      void
*/

function rcp_can_user_be_logged_in() {
	if ( is_user_logged_in() && rcp_no_account_sharing() ) :

		$user_id = get_current_user_id();

		$already_logged_in = get_transient( 'rcp_user_logged_in_' . $user_id );

		if( $already_logged_in !== false ) :

			$data = maybe_unserialize( $already_logged_in );

			if( count( $data ) < 2 )
				return; // do nothing

			// remove the first key
			unset( $data[0] );
			$data = array_values( $data );

			if( ! in_array( $_COOKIE[LOGGED_IN_COOKIE], $data ) ) :

				set_transient( 'rcp_user_logged_in_' . $user_id, $data );

				// Log the user out - this is the oldest user logged into this account
				wp_logout();
				wp_safe_redirect( trailingslashit( get_bloginfo( 'wpurl' ) ) . 'wp-login.php?loggedout=true' );

			endif;

		endif;

	endif;
}
add_action( 'init', 'rcp_can_user_be_logged_in' );


/**
 * Retrieve a list of the allowed HTML tags
 *
 * This is used for filtering HTML in subscription level descriptions and other places
 *
 * @access  public
 * @since   1.5
*/
function rcp_allowed_html_tags() {
	$tags = array(
		'p' => array(
			'class' => array()
		),
		'span' => array(
			'class' => array()
		),
		'a' => array(
       		'href' => array(),
        	'title' => array(),
        	'class' => array(),
        	'title' => array()
        ),
		'strong' => array(),
		'em' => array(),
		'br' => array(),
		'img' => array(
       		'src' => array(),
        	'title' => array(),
        	'alt' => array()
        ),
		'div' => array(
			'class' => array()
		),
		'ul' => array(
			'class' => array()
		),
		'li' => array(
			'class' => array()
		)
	);

	return apply_filters( 'rcp_allowed_html_tags', $tags );
}


/**
 * Checks whether function is disabled.
 *
 * @access public
 * @since  1.5
 *
 * @param  string $function Name of the function.
 * @return bool Whether or not function is disabled.
 */
function rcp_is_func_disabled( $function ) {
	$disabled = explode( ',',  ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}


/**
 * Converts the month number to the month name
 *
 * @access public
 * @since  1.8
 *
 * @param  int $n Month number.
 * @return string The name of the month.
 */
if( ! function_exists( 'rcp_get_month_name' ) ) {
	function rcp_get_month_name($n) {
		$timestamp = mktime(0, 0, 0, $n, 1, 2005);

		return date( "F", $timestamp );
	}
}

/**
 * Retrieve timezone
 *
 * @since 1.8
 * @return string $timezone The timezone ID
 */
function rcp_get_timezone_id() {

    // if site timezone string exists, return it
    if ( $timezone = get_option( 'timezone_string' ) )
        return $timezone;

    // get UTC offset, if it isn't set return UTC
    if ( ! ( $utc_offset = 3600 * get_option( 'gmt_offset', 0 ) ) )
        return 'UTC';

    // attempt to guess the timezone string from the UTC offset
    $timezone = timezone_name_from_abbr( '', $utc_offset );

    // last try, guess timezone string manually
    if ( $timezone === false ) {

        $is_dst = date('I');

        foreach ( timezone_abbreviations_list() as $abbr ) {
            foreach ( $abbr as $city ) {
                if ( $city['dst'] == $is_dst &&  $city['offset'] == $utc_offset )
                    return $city['timezone_id'];
            }
        }
    }

    // fallback
    return 'UTC';
}

/**
 * Get the number of days in a particular month
 *
 * @since 2.0.9
 * @return string $timezone The timezone ID
 */
if ( ! function_exists( 'cal_days_in_month' ) ) {
	// Fallback in case the calendar extension is not loaded in PHP
	// Only supports Gregorian calendar
	function cal_days_in_month( $calendar, $month, $year ) {
		return date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
	}
}

/**
 * Retrieves the payment status label for a payment
 *
 * @since 2.1
 * @return string
 */
function rcp_get_payment_status_label( $payment ) {

	if( is_numeric( $payment ) ) {
		$payments = new RCP_Payments();
		$payment  = $payments->get_payment( $payment );
	}

	if( ! $payment ) {
		return '';
	}

	$label  = '';
	$status = ! empty( $payment->status ) ? $payment->status : 'complete';

	switch( $status ) {

		case 'pending' :

			$label = __( 'Pending', 'rcp' );

			break;

		case 'refunded' :

			$label = __( 'Refunded', 'rcp' );

			break;

		case 'complete' :
		default :

			$label = __( 'Complete', 'rcp' );

			break;
	}

	return apply_filters( 'rcp_payment_status_label', $label, $status, $payment );

}