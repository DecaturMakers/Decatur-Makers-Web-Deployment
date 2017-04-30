<?php

/**
 * RCP Subscription Levels class
 *
 * This class handles querying, inserting, updating, and removing subscription levels
 * Also includes other discount helper functions
 *
 * @since 1.5
*/

class RCP_Levels {

	/**
	 * Holds the name of our levels database table
	 *
	 * @access  private
	 * @since   1.5
	*/

	private $db_name;


	/**
	 * Holds the version number of our levels database table
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

		$this->db_name    = rcp_get_levels_db_name();
		$this->db_version = '1.5';

	}


	/**
	 * Retrieve a specific subscription level from the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_level( $level_id = 0 ) {
		global $wpdb;

		$level = wp_cache_get( 'level_' . $level_id, 'rcp' );

		if( false === $level ) {

			$level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE id='%d';", $level_id ) );

			wp_cache_set( 'level_' . $level_id, $level, 'rcp' );

		}

		return $level;

	}

	/**
	 * Retrieve a specific subscription level from the database
	 *
	 * @access  public
	 * @since   1.8.2
	*/

	public function get_level_by( $field = 'name', $value = '' ) {
		global $wpdb;


		$level = wp_cache_get( 'level_' . $field . '_' . $value, 'rcp' );

		if( false === $level ) {

			$level = $wpdb->get_row( "SELECT * FROM {$this->db_name} WHERE {$field}='{$value}';" );
	
			wp_cache_set( 'level_' . $field . '_' . $value, $level, 'rcp' );

		}

		return $level;

	}


	/**
	 * Retrieve all subscription levels from the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_levels( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'  => 'all',
			'limit'   => null,
			'orderby' => 'list_order'
		);

		$args = wp_parse_args( $args, $defaults );

		if( $args['status'] == 'active' ) {
			$where = "WHERE `status` !='inactive'";
		} elseif( $args['status'] == 'inactive' ) {
			$where = "WHERE `status` ='{$status}'";
		} else {
			$where = "";
		}

		if( ! empty( $args['limit'] ) )
			$limit = " LIMIT={$args['limit']}";
		else
			$limit = '';

		$cache_key = md5( implode( '|', $args ) . $where );

		$levels = wp_cache_get( $cache_key, 'rcp' );

		if( false === $levels ) {

			$levels = $wpdb->get_results( "SELECT * FROM {$this->db_name} {$where} ORDER BY {$args['orderby']}{$limit};" );

			wp_cache_set( $cache_key, $levels, 'rcp' );

		}

		if( ! empty( $levels ) )
			return $levels;
		return false;
	}


	/**
	 * Retrieve a field for a subscription level
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_level_field( $level_id = 0, $field = '' ) {

		global $wpdb;


		$value = wp_cache_get( 'level_' . $level_id . '_' . $field, 'rcp' );

		if( false === $value ) {

			$value = $wpdb->get_col( $wpdb->prepare( "SELECT {$field} FROM {$this->db_name} WHERE id='%d';", $level_id ) );

			wp_cache_set( 'level_' . $level_id . '_' . $field, $value, 'rcp', 3600 );

		}

		if( $value )
			return $value[0];
		return false;
	}


	/**
	 * Insert a subscription level into the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function insert( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'name'          => '',
			'description'   => '',
			'duration'      => 'unlimited',
			'duration_unit' => 'month',
			'price'         => '0',
			'fee'           => '0',
			'list_order'    => '0',
			'level' 	    => '0',
			'status'        => 'inactive',
			'role'          => 'subscriber'
		);

		$args = wp_parse_args( $args, $defaults );

		do_action( 'rcp_pre_add_subscription', $args );

		$args = apply_filters( 'rcp_add_subscription_args', $args );

		$add = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->db_name} SET
					`name`          = '%s',
					`description`   = '%s',
					`duration`      = '%d',
					`duration_unit` = '%s',
					`price`         = '%s',
					`fee`           = '%s',
					`list_order`    = '0',
					`level`         = '%d',
					`status`        = '%s',
					`role`          = '%s'
				;",
				sanitize_text_field( $args['name'] ),
				sanitize_text_field( $args['description'] ),
				sanitize_text_field( $args['duration'] ),
				sanitize_text_field( $args['duration_unit'] ),
				sanitize_text_field( $args['price'] ),
				sanitize_text_field( $args['fee'] ),
				absint( $args['level'] ),
				sanitize_text_field( $args['status'] ),
				sanitize_text_field( $args['role'] )
			 )
		);

		if( $add ) {

			$args = array(
				'status'  => 'all',
				'limit'   => null,
				'orderby' => 'list_order'
			);

			$cache_key = md5( implode( '|', $args ) );

			wp_cache_delete( $cache_key, 'rcp' );

			do_action( 'rcp_add_subscription', $wpdb->insert_id, $args );
	
			return $wpdb->insert_id;
		}

		return false;

	}


	/**
	 * Update an existing subscription level
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function update( $level_id = 0, $args = array() ) {

		global $wpdb;

		$level = $this->get_level( $level_id );
		$level = get_object_vars( $level );

		$args     = array_merge( $level, $args );

		do_action( 'rcp_pre_edit_subscription_level', absint( $args['id'] ), $args );

		$update = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->db_name} SET
					`name`          = '%s',
					`description`   = '%s',
					`duration`      = '%d',
					`duration_unit` = '%s',
					`price`         = '%s',
					`fee`           = '%s',
					`level`         = '%d',
					`status`        = '%s',
					`role`          = '%s'
					WHERE `id`      = '%d'
				;",
				sanitize_text_field( $args['name'] ),
				wp_kses( $args['description'], rcp_allowed_html_tags() ),
				sanitize_text_field( $args['duration'] ),
				sanitize_text_field( $args['duration_unit'] ),
				sanitize_text_field( $args['price'] ),
				sanitize_text_field( $args['fee'] ),
				absint( $args['level'] ),
				sanitize_text_field( $args['status'] ),
				sanitize_text_field( $args['role'] ),
				absint( $args['id'] )
			)
		);

		$cache_args = array(
			'status'  => 'all',
			'limit'   => null,
			'orderby' => 'list_order'
		);

		$cache_key = md5( implode( '|', $cache_args ) );

		wp_cache_delete( $cache_key, 'rcp' );

		do_action( 'rcp_edit_subscription_level', absint( $args['id'] ), $args );

		if( $update !== false )
			return true;
		return false;

	}


	/**
	 * Delete a subscription level
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function remove( $level_id = 0 ) {

		global $wpdb;

		$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $this->db_name . " WHERE `id`='%d';", absint( $level_id ) ) );
	
		$args = array(
			'status'  => 'all',
			'limit'   => null,
			'orderby' => 'list_order'
		);

		$cache_key = md5( implode( '|', $args ) );

		wp_cache_delete( $cache_key, 'rcp' );

		do_action( 'rcp_remove_subscription_level', absint( $level_id ) );

	}

}