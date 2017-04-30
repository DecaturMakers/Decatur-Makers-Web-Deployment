<?php

/**
 * RCP Discounts class
 *
 * This class handles querying, inserting, updating, and removing discounts
 * Also includes other discount helper functions
 *
 * @since 1.5
*/


class RCP_Discounts {

	/**
	 * Holds the name of our discounts database table
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


	/**
	 * Get things started
	 *
	 * @since   1.5
	*/

	function __construct() {

		$this->db_name    = rcp_get_discounts_db_name();
		$this->db_version = '1.2';

	}


	/**
	 * Retrieve discounts from the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_discounts( $args = array() ) {
		global $wpdb;

		$where = '';

		// TODO: Add optional args for limit, order, etc
		if( ! empty( $args['status'] ) )
			$where = " WHERE status='{$args['status']}'";

		$discounts = $wpdb->get_results( "SELECT * FROM {$this->db_name}{$where};" );

		if( $discounts )
			return $discounts;
		return false;

	}


	/**
	 * Retrieve a specific discount from the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_discount( $discount_id = 0 ) {
		global $wpdb;

		$discount = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE id='%d';", $discount_id ) );

		return $discount;

	}


	/**
	 * Retrieve a specific discount from the database by field
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_by( $field = 'code', $value = '' ) {
		global $wpdb;

		$discount = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE {$field}='%s';", $value ) );

		return $discount;

	}


	/**
	 * Get the status of a discount
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_status( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->status;
		return false;

	}


	/**
	 * Get the amount of a discount
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_amount( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->amount;
		return 0;

	}


	/**
	 * Get the number of times a discount has been used
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_uses( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->use_count;
		return 0;

	}


	/**
	 * Get the maximum number of times a discount can be used
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_max_uses( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->max_uses;
		return 0;

	}


	/**
	 * Get the associated subscription level for a discount
	 *
	 * @access  public
	 * @since   1.6
	 */

	public function get_subscription_id( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->subscription_id;
		return 0;

	}


	/**
	 * Checks wether a discount code has a subscription associated
	 *
	 * @access  public
	 * @since   1.6
	 * @return  bool
	 */

	public function has_subscription_id( $discount_id = 0 ) {

		return $this->get_subscription_id( $discount_id ) > 0;

	}


	/**
	 * Increase the use count of a discount by 1
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function increase_uses( $discount_id = 0 ) {

		$uses = absint( $this->get_uses( $discount_id ) );
		$uses += 1;
		$this->update( $discount_id, array( 'use_count' => $uses ) );
	}


	/**
	 * Get the expiration date of a discount
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_expiration( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->expiration;
		return false;

	}


	/**
	 * Get the discount type
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_type( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->unit;
		return false;

	}


	/**
	 * Store a discount in the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function insert( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'name'           => '',
			'description'    => '',
			'amount'         => '0.00',
			'status'         => 'inactive',
			'unit'           => '%',
			'code'           => '',
			'expiration'     => '',
			'max_uses' 	     => 0,
			'use_count'      => '0',
			'subscription_id'=> 0
		);

		$args = wp_parse_args( $args, $defaults );

		if( $this->get_by( 'code', $args['code'] ) ) {
			return false; // this code already exists
		}

		do_action( 'rcp_pre_add_discount', $args );

		$add = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->db_name} SET
					`name`           = '%s',
					`description`    = '%s',
					`amount`         = '%s',
					`status`         = 'active',
					`unit`           = '%s',
					`code`           = '%s',
					`expiration`     = '%s',
					`max_uses`       = '%d',
					`use_count`      = '0',
					`subscription_id`= '%d'
				;",
				sanitize_text_field( $args['name'] ),
				strip_tags( addslashes( $args['description'] ) ),
				sanitize_text_field( $args['amount'] ),
				$args['unit'],
				sanitize_text_field( $args['code'] ),
				sanitize_text_field( $args['expiration'] ),
				absint( $args['max_uses'] ),
				absint( $args['subscription_id'] )
			)
		);

		do_action( 'rcp_add_discount', $args, $wpdb->insert_id );

		if( $add )
			return $wpdb->insert_id;
		return false;
	}


	/**
	 * Update an existing discount
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function update( $discount_id = 0, $args = array() ) {

		global $wpdb;

		$discount = $this->get_discount( $discount_id );
		$discount = get_object_vars( $discount );

		$args     = array_merge( $discount, $args );

		do_action( 'rcp_pre_edit_discount', absint( $discount_id ), $args );

		$update = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->db_name} SET
					`name`            = '%s',
					`description`     = '%s',
					`amount`          = '%s',
					`unit`            = '%s',
					`code`            = '%s',
					`status`          = '%s',
					`expiration`      = '%s',
					`max_uses`        = '%d',
					`use_count`       = '%d',
					`subscription_id` = '%d'
					WHERE `id`        = '%d'
				;",
				sanitize_text_field( $args['name'] ),
				strip_tags( addslashes( $args['description'] ) ),
				sanitize_text_field( $args['amount'] ),
				$args['unit'],
				sanitize_text_field( $args['code'] ),
				sanitize_text_field( $args['status'] ),
				sanitize_text_field( $args['expiration'] ),
				absint( $args['max_uses'] ),
				absint( $args['use_count'] ),
				absint( $args['subscription_id'] ),
				absint( $discount_id )
			)
		);

		do_action( 'rcp_edit_discount', absint( $discount_id ), $args );

		if( $update )
			return true;
		return false;

	}


	/**
	 * Delete a discount code
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function delete( $discount_id = 0 ) {
		global $wpdb;
		do_action( 'rcp_delete_discount', $discount_id );
		$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db_name} WHERE `id` = '%d';", absint( $discount_id ) ) );
	}


	/**
	 * Check if a discount is maxed out
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function is_maxed_out( $discount_id = 0 ) {

		$uses = $this->get_uses( $discount_id );
		$max  = $this->get_max_uses( $discount_id );
		$ret  = false;

		if( ! empty( $max ) && $max > 0 ) {
			if( $uses >= $max ) {
				$ret = true;
			}
		}

		return (bool) apply_filters( 'rcp_is_discount_maxed_out', $ret, $discount_id, $uses, $max );

	}


	/**
	 * Check if a discount is expired
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function is_expired( $discount_id = 0 ) {

		$ret        = false;
		$expiration = $this->get_expiration( $discount_id );

		// if no expiration is set, return true
		if( ! empty( $expiration ) ) {

			if ( strtotime( 'NOW' ) > strtotime( $expiration ) ) {
				$ret = true;
			}
		}

		return (bool) apply_filters( 'rcp_is_discount_expired', $ret, $discount_id, $expiration );

	}


	/**
	 * Add a discount to a user's history
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function add_to_user( $user_id = 0, $discount_code = '' ) {

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

		if( ! is_array( $user_discounts ) )
			$user_discounts = array();

		$user_discounts[] = $discount_code;

		do_action( 'rcp_pre_store_discount_for_user', $discount_code, $user_id );

		update_user_meta( $user_id, 'rcp_user_discounts', $user_discounts );

		do_action( 'rcp_store_discount_for_user', $discount_code, $user_id );

	}


	/**
	 * Check if a user has used a discount
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function user_has_used( $user_id = 0, $discount_code = '' ) {

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

		if( is_array( $user_discounts ) && in_array( $discount_code, $user_discounts ) )
			return true;

		return false;

	}


	/**
	 * Format the discount code
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function format_discount( $amount = '', $type = '' ) {

		if( $type == '%' ) {
			$discount = $amount . '%';
		} elseif( $type == 'flat' ) {
			$discount = rcp_currency_filter( $amount );
		}

		return $discount;

	}


	/**
	 * Calculate the discounted price
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function calc_discounted_price( $base_price = '', $discount_amount = '', $type = '%' ) {

		$discounted_price = $base_price;
		if( $type == '%' ) {
			$discounted_price = $base_price - ( $base_price * ( $discount_amount / 100 ) );
		} elseif($type == 'flat') {
			$discounted_price = $base_price - $discount_amount;
		}

		return number_format( (float) $discounted_price, 2 );

	}

}