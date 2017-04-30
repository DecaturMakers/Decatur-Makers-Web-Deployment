<?php

function rcp_admin_scripts( $hook ) {

	global $rcp_options, $rcp_members_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_export_page, $rcp_help_page, $rcp_logs_page;
	$pages = array( $rcp_members_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_logs_page, $rcp_export_page, $rcp_help_page );

	if( in_array( $hook, $pages ) ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'bbq',  RCP_PLUGIN_URL . 'includes/js/jquery.ba-bbq.min.js' );
		wp_enqueue_script( 'rcp-admin-scripts',  RCP_PLUGIN_URL . 'includes/js/admin-scripts.js', array( 'jquery' ), RCP_PLUGIN_VERSION );
	}

	if ( $rcp_reports_page == $hook ) {
		wp_enqueue_script( 'jquery-flot', RCP_PLUGIN_URL . 'includes/js/jquery.flot.min.js' );
	}

	if( $hook == $rcp_help_page ) {
		wp_enqueue_style( 'jquery-snippet',  RCP_PLUGIN_URL . 'includes/css/jquery.snippet.min.css' );
		wp_enqueue_script( 'jquery-snippet',  RCP_PLUGIN_URL . 'includes/js/jquery.snippet.min.js' );
	}
	if( in_array( $hook, $pages ) ) {
		wp_localize_script( 'rcp-admin-scripts', 'rcp_vars', array(
				'rcp_member_nonce'    => wp_create_nonce( 'rcp_member_nonce' ),
				'revoke_access'       => __( 'Are you sure you wish to revoke this member\'s access? This will not cancel their payment plan.', 'rcp' ),
				'cancel_user'         => __( 'Are you sure you wish to cancel this member\'s subscription?', 'rcp' ),
				'delete_subscription' => __( 'If you delete this subscription, all members registered with this level will be canceled. Proceed?', 'rcp' ),
				'delete_payment'      => __( 'Are you sure you want to delete this payment? This action is irreversible. Proceed?', 'rcp' ),
				'missing_username'    => __( 'You must choose a username', 'rcp' ),
				'currency_sign'       => rcp_currency_filter(''),
				'currency_pos'        => isset( $rcp_options['currency_position'] ) ? $rcp_options['currency_position'] : 'before',
				'use_as_logo'         => __( 'Use as Logo', 'rcp' ),
				'choose_logo'         => __( 'Choose a Logo', 'rcp' )
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'rcp_admin_scripts' );

function rcp_admin_styles( $hook ) {
	global $rcp_members_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_export_page, $rcp_logs_page, $rcp_help_page;
	$pages = array(
		$rcp_members_page,
		$rcp_subscriptions_page,
		$rcp_discounts_page,
		$rcp_payments_page,
		$rcp_reports_page,
		$rcp_settings_page,
		$rcp_export_page,
		$rcp_logs_page,
		$rcp_help_page,
		'post.php',
		'edit.php',
		'post-new.php'
	);

	if( in_array( $hook, $pages ) ) {
		wp_enqueue_style( 'datepicker',  RCP_PLUGIN_URL . 'includes/css/datepicker.css' );
		wp_enqueue_style( 'rcp-admin',  RCP_PLUGIN_URL . 'includes/css/admin-styles.css', array(), RCP_PLUGIN_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'rcp_admin_styles' );


// register our form css
function rcp_register_css() {
	wp_register_style('rcp-form-css',  RCP_PLUGIN_URL . 'includes/css/forms.css', array(), RCP_PLUGIN_VERSION );
}
add_action('init', 'rcp_register_css');

// register our front end scripts
function rcp_register_scripts() {
	
	global $rcp_options;
	
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	wp_register_script( 'rcp-register',  RCP_PLUGIN_URL . 'includes/js/register' . $suffix . '.js', array('jquery'), RCP_PLUGIN_VERSION );
	wp_register_script( 'jquery-blockui',  RCP_PLUGIN_URL . 'includes/js/jquery.blockUI.js', array('jquery'), RCP_PLUGIN_VERSION );

}
add_action( 'init', 'rcp_register_scripts' );

// load our form css
function rcp_print_css() {
	global $rcp_load_css, $rcp_options;

	// this variable is set to TRUE if the short code is used on a page/post
	if ( ! $rcp_load_css || ( isset( $rcp_options['disable_css'] ) && $rcp_options['disable_css'] ) )
		return; // this means that neither short code is present, so we get out of here

	wp_print_styles( 'rcp-form-css' );
}
add_action( 'wp_footer', 'rcp_print_css' );

// load our form scripts
function rcp_print_scripts() {
	global $rcp_load_scripts, $rcp_options;

	// this variable is set to TRUE if the short code is used on a page/post
	if ( ! $rcp_load_scripts )
		return; // this means that neither short code is present, so we get out of here

	wp_localize_script('rcp-register', 'rcp_script_options',
		array(
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'pleasewait' => __( 'Please Wait . . . ', 'rcp' )
		)
	);

	wp_print_scripts( 'rcp-register' );
	wp_print_scripts( 'jquery-blockui' );

}
add_action( 'wp_footer', 'rcp_print_scripts' );