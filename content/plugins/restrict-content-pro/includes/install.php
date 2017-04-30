<?php

// function to create the DB / Options / Defaults
function rcp_options_install() {
   	global $wpdb,$rcp_db_name, $rcp_db_version, $rcp_discounts_db_name, $rcp_discounts_db_version, 
   	$rcp_payments_db_name, $rcp_payments_db_version;

   	$rcp_options = get_option( 'rcp_settings', array() );

	// create the RCP subscription level database table
	if ($wpdb->get_var( "show tables like '$rcp_db_name'" ) != $rcp_db_name ) {
		$sql = "CREATE TABLE " . $rcp_db_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name tinytext NOT NULL,
		description longtext NOT NULL,
		duration smallint NOT NULL,
		duration_unit tinytext NOT NULL,
		price tinytext NOT NULL,
		fee tinytext NOT NULL,
		list_order mediumint NOT NULL,
		level mediumint NOT NULL,
		status tinytext NOT NULL,
		role tinytext NOT NULL,
		UNIQUE KEY id (id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( "rcp_db_version", $rcp_db_version );
	}

	// create the RCP discounts database table
	if( $wpdb->get_var( "show tables like '$rcp_discounts_db_name'" ) != $rcp_discounts_db_name ) {
		$sql = "CREATE TABLE " . $rcp_discounts_db_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name tinytext NOT NULL,
		description longtext NOT NULL,
		amount tinytext NOT NULL,
		unit tinytext NOT NULL,
		code tinytext NOT NULL,
		use_count mediumint NOT NULL,
		max_uses mediumint NOT NULL,
		status tinytext NOT NULL,
		expiration mediumtext NOT NULL,
		subscription_id mediumint NOT NULL,
		UNIQUE KEY id (id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( "rcp_discounts_db_version", $rcp_discounts_db_version );
	}

	// create the RCP payments database table
	if( $wpdb->get_var( "show tables like '$rcp_payments_db_name'" ) != $rcp_payments_db_name ) {
		$sql = "CREATE TABLE " . $rcp_payments_db_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		subscription mediumtext NOT NULL,
		date datetime NOT NULL,
		amount mediumtext NOT NULL,
		user_id mediumint NOT NULL,
		payment_type tinytext NOT NULL,
		subscription_key mediumtext NOT NULL,
		transaction_id tinytext NOT NULL,
		status varchar(200) NOT NULL,
		UNIQUE KEY id (id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( "rcp_payments_db_version", $rcp_payments_db_version );
	}

	// Create RCP caps
	$caps = new RCP_Capabilities;
	$caps->add_caps();

	// Setup some default options
	$options = array();

	// Checks if the purchase page option exists
	if ( ! isset( $rcp_options['registration_page'] ) ) {

		// Register Page
		$register = wp_insert_post(
			array(
				'post_title'     => __( 'Register', 'edd' ),
				'post_content'   => '[register_form]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);

		// Welcome (Success) Page
		$success = wp_insert_post(
			array(
				'post_title'     => __( 'Welcome', 'edd' ),
				'post_content'   => __( 'Welcome! This is your success page where members are redirected after completing their registration.', 'edd' ),
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_parent'    => $register,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);

		// Store our page IDs
		$options['registration_page'] = $register;
		$options['redirect']  = $success;

	}

	update_option( 'rcp_settings', array_merge( $rcp_options, $options ) );

	// and option that allows us to make sure RCP is installed
	add_option( 'rcp_is_installed', '1' );

}
// run the install scripts upon plugin activation
register_activation_hook( RCP_PLUGIN_FILE, 'rcp_options_install' );

function rcp_check_if_installed() {
	// this is mainly for network activated installs
	if( ! get_option( 'rcp_is_installed' ) ) {
		rcp_options_install();
	}
}
add_action( 'admin_init', 'rcp_check_if_installed' );