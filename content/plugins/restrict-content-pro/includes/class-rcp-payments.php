<?php

/**
 * RCP Payments class
 *
 * This class handles querying, inserting, updating, and removing payments
 * Also handles calculating earnings
 *
 * @since 1.5
*/

class RCP_Payments {


	/**
	 * Holds the name of our payments database table
	 *
	 * @access  private
	 * @since   1.5
	*/

	private $db_name;


	/**
	 * Holds the version number of our discounts database table
	 *
	 * @access  private
	 * @since   1.5
	*/

	private $db_version;


	function __construct() {

		$this->db_name    = rcp_get_payments_db_name();
		$this->db_version = '1.4';

	}


	/**
	 * Add a payment to the database
	 *
	 * @access  public
	 * @param   $payment_data Array All of the payment data, such as amount, date, user ID, etc
	 * @since   1.5
	*/

	public function insert( $payment_data = array() ) {

		global $wpdb;

		$defaults = array(
			'subscription'      => '',
			'date'              => date( 'Y-m-d H:i:s' ),
			'amount'            => 0.00,
			'user_id'           => 0,
			'payment_type'      => '',
			'subscription_key'  => '',
			'transaction_id'    => '',
			'status'            => 'complete'
		);

		$args = wp_parse_args( $payment_data, $defaults );

		if( $this->payment_exists( $args ) )
			return;

		$wpdb->insert( $this->db_name, $args, array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' ) );

		// if insert was succesful, return the payment ID
		if( $wpdb->insert_id ) {
			// clear the payment caches

			delete_transient( 'rcp_earnings' );
			delete_transient( 'rcp_payments_count' );

			// Remove trialing status, if it exists
			delete_user_meta( $args['user_id'], 'rcp_is_trialing' );

			do_action( 'rcp_insert_payment', $wpdb->insert_id, $args, $args['amount'] );

			return $wpdb->insert_id;

		}

		return false;

	}


	/**
	 * Checks if a payment exists in the DB
	 *
	 * @access  public
	 * @param   $args Array An array of the payment details we need to look for
	 * @since   1.5
	*/

	public function payment_exists( $args = array() ) {

		global $wpdb;

		$found = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM " . $this->db_name . " WHERE `date`='%s' AND `subscription_key`='%s' AND `payment_type`='%s';",
				$args['date'],
				$args['subscription_key'],
				$args['payment_type']
			)
		);

		if( $found )
			return true; // this payment already exists

		return false;

	}


	/**
	 * Update a payment in the datbase.
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function update( $payment_id = 0, $payment_data = array() ) {

		global $wpdb;
		do_action( 'rcp_update_payment', $payment_id, $payment_data );
		return $wpdb->update( $this->db_name, $payment_data, array( 'id' => $payment_id ) );
	}


	/**
	 * Delete a payment from the datbase.
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function delete( $payment_id = 0 ) {
		global $wpdb;
		do_action( 'rcp_delete_payment', $payment_id );
		$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db_name} WHERE `id` = '%d';", absint( $payment_id ) ) );

	}


	/**
	 * Retrieve a specific payment
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_payment( $payment_id = 0 ) {

		global $wpdb;

		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE id = %d", absint( $payment_id ) ) );

		if( empty( $payment->status ) ) {
			$payment->status = 'complete';
		}

		return $payment;

	}


	/**
	 * Retrieve a specific payment by a field
	 *
	 * @access  public
	 * @since   1.8.2
	*/

	public function get_payment_by( $field = 'id', $value = '' ) {

		global $wpdb;

		$payment = $wpdb->get_row( "SELECT * FROM {$this->db_name} WHERE {$field} = {$value}" );

		if( empty( $payment->status ) ) {
			$payment->status = 'complete';
		}

		return $payment;

	}


	/**
	 * Retrieve payments from the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_payments( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'       => 20,
			'offset'       => 0,
			'subscription' => 0,
			'user_id'      => 0,
			'date'         => array(),
			'fields'       => false,
			'status'       => '',
			's'            => ''
		);

		$args  = wp_parse_args( $args, $defaults );

		$where = '';

		// payments for a specific subscription level
		if( ! empty( $args['subscription'] ) ) {
			$where .= "WHERE `subscription`= '{$args['subscription']}' ";
		}

		// payments for specific users
		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) )
				$user_ids = implode( ',', $args['user_id'] );
			else
				$user_ids = intval( $args['user_id'] );

			if( ! empty( $args['subscription'] ) ) {
				$where .= "`user_id` IN( {$user_ids} ) ";
			} else {
				$where .= "WHERE `user_id` IN( {$user_ids} ) ";
			}

		}

		// payments for specific statuses
		if( ! empty( $args['status'] ) ) {

			if( is_array( $args['status'] ) )
				$statuss = implode( ',', $args['status'] );
			else
				$statuss = intval( $args['status'] );

			if( ! empty( $args['subscription'] ) || ! empty( $args['user_id'] ) ) {
				$where .= "`status` IN( {$statuss} ) ";
			} else {
				$where .= "WHERE `status` IN( {$statuss} ) ";
			}

		}

		// Setup the date query
		if( ! empty( $args['date'] ) && is_array( $args['date'] ) ) {

			$day   = ! empty( $args['date']['day'] )   ? absint( $args['date']['day'] )   : null;
			$month = ! empty( $args['date']['month'] ) ? absint( $args['date']['month'] ) : null;
			$year  = ! empty( $args['date']['year'] )  ? absint( $args['date']['year'] )  : null;
			$date_where = '';

			$date_where .= ! is_null( $year )  ? $year . " = YEAR ( date ) " : '';

			if( ! is_null( $month ) ) {
				$date_where = $month  . " = MONTH ( date ) AND " . $date_where;
			}

			if( ! is_null( $day ) ) {
				$date_where = $day . " = DAY ( date ) AND " . $date_where;
			}

			if( ! empty( $args['user_id'] ) || ! empty( $args['subscription'] ) ) {
				$where .= "AND (" . $date_where . ")";
			} else {
				$where .= "WHERE ( " . $date_where . " ) ";
			}
		}

		// Fields to return
		if( $args['fields'] ) {
			$fields = $args['fields'];
		} else {
			$fields = '*';
		}

		if( ! empty( $args['s'] ) ) {

			if( empty( $where ) )
				$where = "WHERE ";
			else
				$where = " AND ";

			// Search by email
			if( is_email( $args['s'] ) ) {

				$user = get_user_by( 'email', $args['s'] );

				$where .= "`user_id`=$user->ID ";

			} else {

				$levels_db = new RCP_Levels;

				// Search by subscription key
				if( strlen( $args['s'] ) == 32 ) {

					$where .= "`subscription_key`= '{$args['s']}' ";

				} elseif( $levels_db->get_level_by( 'name', $args['s'] ) ) {

					// Matching subscription level found so search for payments with this level
					$where .= "`subscription`= '{$args['s']}' ";
				} else {
					$where .= "`transaction_id`='{$args['s']}' ";
				}
			}

		}

		$payments = $wpdb->get_results( $wpdb->prepare( "SELECT {$fields} FROM " . $this->db_name . " {$where}ORDER BY id DESC LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) ) );

		return $payments;

	}


	/**
	 * Count the total number of payments in the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'user_id' => 0,
			'status'  => ''
		);

		$args  = wp_parse_args( $args, $defaults );

		$where = '';

		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) )
				$user_ids = implode( ',', $args['user_id'] );
			else
				$user_ids = intval( $args['user_id'] );

			$where .= " WHERE `user_id` IN( {$user_ids} ) ";

		}

		if( ! empty( $args['status'] ) ) {

			if( is_array( $args['status'] ) ) {
				$statuss = implode( ',', $args['status'] );
			} else {
				$statuss = intval( $args['status'] );
			}

			if( ! empty( $args['user_id'] ) ) {
				$where .= " AND `status` IN( {$statuss} ) ";
			} else {
				$where .= " WHERE `status` IN( {$statuss} ) ";
			}

		}

		$key   = md5( 'rcp_payments_' . serialize( $args ) );
		$count = get_transient( $key );

		if( $count === false ) {
			$count = $wpdb->get_var( "SELECT COUNT(ID) FROM " . $this->db_name . "{$where};" );
			set_transient( $key, $count, 10800 );
		}

		return $count;

	}


	/**
	 * Calculate the total earnings of all payments in the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_earnings( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'earnings'     => 1, // Just for the cache key
			'subscription' => 0,
			'user_id'      => 0,
			'date'         => array()
		);

		$args = wp_parse_args( $args, $defaults );

		$cache_args = $args;
		$cache_args['date'] = implode( ',', $args['date'] );
		$cache_key = md5( implode( ',', $cache_args ) );

		$where = '';

		// payments for a specific subscription level
		if( ! empty( $args['subscription'] ) ) {
			$where .= "WHERE `subscription`= '{$args['subscription']}' ";
		}

		// payments for specific users
		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) )
				$user_ids = implode( ',', $args['user_id'] );
			else
				$user_ids = intval( $args['user_id'] );

			if( ! empty( $args['subscription'] ) ) {
				$where .= "`user_id` IN( {$user_ids} ) ";
			} else {
				$where .= "WHERE `user_id` IN( {$user_ids} ) ";
			}

		}

		// Setup the date query
		if( ! empty( $args['date'] ) && is_array( $args['date'] ) ) {

			$day   = ! empty( $args['date']['day'] )   ? absint( $args['date']['day'] )   : null;
			$month = ! empty( $args['date']['month'] ) ? absint( $args['date']['month'] ) : null;
			$year  = ! empty( $args['date']['year'] )  ? absint( $args['date']['year'] )  : null;
			$date_where = '';

			$date_where .= ! is_null( $year )  ? $year . " = YEAR ( date ) " : '';

			if( ! is_null( $month ) ) {
				$date_where = $month  . " = MONTH ( date ) AND " . $date_where;
			}

			if( ! is_null( $day ) ) {
				$date_where = $day . " = DAY ( date ) AND " . $date_where;
			}

			if( ! empty( $args['user_id'] ) || ! empty( $args['subscription'] ) ) {
				$where .= "AND (" . $date_where . ") ";
			} else {
				$where .= "WHERE ( " . $date_where . " ) ";
			}
		}

		// Exclude refunded payments
		if( false !== strpos( $where, 'WHERE' ) ) {

			$where .= "AND ( `status` = 'complete' OR `status` IS NULL )";

		} else {

			$where .= "WHERE ( `status` = 'complete' OR `status` IS NULL )";
	
		}

		$earnings = get_transient( $cache_key );

		if( $earnings === false ) {
			$earnings = $wpdb->get_var( "SELECT SUM(amount) FROM " . $this->db_name . " {$where};" );
			set_transient( $cache_key, $earnings, 3600 );
		}

		return round( $earnings, 2 );

	}

	/**
	 * Retrieves the last payment made by a user
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function last_payment_of_user( $user_id = 0 ) {
		global $wpdb;
		$query = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $this->db_name . " WHERE `user_id`='%d' ORDER BY id DESC LIMIT 1;", $user_id ) );
		if( $query )
			return $query[0]->amount;
		return false;
	}

}