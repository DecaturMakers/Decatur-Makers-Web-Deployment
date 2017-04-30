<?php

/*******************************************
* Restrict Content Admin Pages
*******************************************/

function rcp_settings_menu() {

	global $rcp_members_page, $rcp_subscriptions_page, $rcp_discounts_page, $rcp_payments_page, $rcp_reports_page, $rcp_settings_page, $rcp_export_page, $rcp_logs_page, $rcp_help_page;

	// add settings page
	add_menu_page( __( 'Restrict Content Pro Settings', 'rcp' ), __( 'Restrict', 'rcp' ), 'rcp_view_members', 'rcp-members', 'rcp_members_page', 'dashicons-lock' );
	$rcp_members_page 		= add_submenu_page( 'rcp-members', __( 'Members', 'rcp' ), __( 'Members', 'rcp' ), 'rcp_view_members', 'rcp-members', 'rcp_members_page' );
	$rcp_subscriptions_page = add_submenu_page( 'rcp-members', __( 'Subscription Levels', 'rcp' ), __( 'Subscription Levels', 'rcp' ), 'rcp_view_levels', 'rcp-member-levels', 'rcp_member_levels_page' );
	$rcp_discounts_page 	= add_submenu_page( 'rcp-members', __( 'Discounts', 'rcp' ), __( 'Discount Codes', 'rcp' ), 'rcp_view_discounts', 'rcp-discounts', 'rcp_discounts_page' );
	$rcp_payments_page 		= add_submenu_page( 'rcp-members', __( 'Payments', 'rcp' ), __( 'Payments', 'rcp' ), 'rcp_view_payments', 'rcp-payments', 'rcp_payments_page' );
	$rcp_reports_page       = add_submenu_page( 'rcp-members', __( 'Reports', 'rcp'), __( 'Reports', 'rcp' ),'rcp_view_payments', 'rcp-reports', 'rcp_reports_page' );
	$rcp_settings_page 		= add_submenu_page( 'rcp-members', __( 'Restrict Content Pro Settings', 'rcp' ), __( 'Settings', 'rcp' ),'rcp_manage_settings', 'rcp-settings', 'rcp_settings_page' );
	$rcp_export_page 		= add_submenu_page( 'rcp-members', __( 'Export Member Data', 'rcp' ), __( 'Export', 'rcp' ),'rcp_export_data', 'rcp-export', 'rcp_export_page' );
	$rcp_logs_page 		    = add_submenu_page( 'rcp-members', __( 'Logs', 'rcp' ), __( 'Logs', 'rcp' ),'rcp_view_payments', 'rcp-logs', 'rcp_logs_page' );
	$rcp_help_page 			= add_submenu_page( 'rcp-members', __( 'Help', 'rcp' ), __( 'Help', 'rcp' ), 'rcp_view_help', 'rcp-help', '__return_null' );

	if ( get_bloginfo('version') >= 3.3 ) {
		// load each of the help tabs
		add_action( "load-$rcp_members_page", "rcp_help_tabs" );
		add_action( "load-$rcp_subscriptions_page", "rcp_help_tabs" );
		add_action( "load-$rcp_discounts_page", "rcp_help_tabs" );
		add_action( "load-$rcp_reports_page", "rcp_help_tabs" );
		add_action( "load-$rcp_settings_page", "rcp_help_tabs" );
		add_action( "load-$rcp_export_page", "rcp_help_tabs" );
	}
	add_action( "load-$rcp_members_page", "rcp_screen_options" );
	add_action( "load-$rcp_subscriptions_page", "rcp_screen_options" );
	add_action( "load-$rcp_discounts_page", "rcp_screen_options" );
	add_action( "load-$rcp_payments_page", "rcp_screen_options" );
	add_action( "load-$rcp_reports_page", "rcp_screen_options" );
	add_action( "load-$rcp_settings_page", "rcp_screen_options" );
	add_action( "load-$rcp_export_page", "rcp_screen_options" );
	add_action( "load-$rcp_logs_page", "rcp_screen_options" );
}
add_action( 'admin_menu', 'rcp_settings_menu' );