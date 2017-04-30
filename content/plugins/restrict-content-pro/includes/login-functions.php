<?php
/**
 * Login Functions
 *
 * Processes the login forms and also the login process during registration
 *
 * @package     Restrict Content Pro
 * @subpackage  Login Functions
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieves the login URl with an optional redirect
 *
 * @access      public
 * @since       2.1
 */
function rcp_get_login_url( $redirect = '' ) {

	global $rcp_options;

	if( isset( $rcp_options['hijack_login_url'] ) && ! empty( $rcp_options['login_redirect'] ) ) {

		$url = add_query_arg( 'redirect', $redirect, get_permalink( absint( $rcp_options['login_redirect'] ) ) );

	} else {

		$url = wp_login_url( $redirect );

	}

	return apply_filters( 'rcp_login_url', $url, $redirect );

}

/**
 * Log a user in
 *
 * @access      public
 * @since       1.0
 */
function rcp_login_user_in( $user_id, $user_login, $remember = false ) {
	$user = get_userdata( $user_id );
	if( ! $user )
		return;
	wp_set_auth_cookie( $user_id, $remember );
	wp_set_current_user( $user_id, $user_login );
	do_action( 'wp_login', $user_login, $user );
}


/**
 *Process the login form
 *
 * @access      public
 * @since       1.0
 */
function rcp_process_login_form() {

	if( ! isset( $_POST['rcp_action'] ) || 'login' != $_POST['rcp_action'] ) {
		return;
	}

	if( ! isset( $_POST['rcp_login_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_login_nonce'], 'rcp-login-nonce' ) ) {
		return;
	}

	// this returns the user ID and other info from the user name
	$user = get_user_by( 'login', $_POST['rcp_user_login'] );

	do_action( 'rcp_before_form_errors', $_POST );

	if( !$user ) {
		// if the user name doesn't exist
		rcp_errors()->add( 'empty_username', __( 'Invalid username', 'rcp' ), 'login' );
	}

	if( !isset( $_POST['rcp_user_pass'] ) || $_POST['rcp_user_pass'] == '') {
		// if no password was entered
		rcp_errors()->add( 'empty_password', __( 'Please enter a password', 'rcp' ), 'login' );
	}

	if( $user ) {
		// check the user's login with their password
		if( !wp_check_password( $_POST['rcp_user_pass'], $user->user_pass, $user->ID ) ) {
			// if the password is incorrect for the specified user
			rcp_errors()->add( 'empty_password', __( 'Incorrect password', 'rcp' ), 'login' );
		}
	}

	if( function_exists( 'is_limit_login_ok' ) && ! is_limit_login_ok() ) {

		rcp_errors()->add( 'limit_login_failed', limit_login_error_msg(), 'login' );

	}

	do_action( 'rcp_login_form_errors', $_POST );

	// retrieve all error messages
	$errors = rcp_errors()->get_error_messages();

	// only log the user in if there are no errors
	if( empty( $errors ) ) {

		$remember = isset( $_POST['rcp_user_remember'] );

		$redirect = ! empty( $_POST['rcp_redirect'] ) ? $_POST['rcp_redirect'] : home_url();

		rcp_login_user_in( $user->ID, $_POST['rcp_user_login'], $remember );

		// redirect the user back to the page they were previously on
		wp_redirect( $redirect ); exit;

	} else {

		if( function_exists( 'limit_login_failed' ) ) {
			limit_login_failed( $_POST['rcp_user_login'] );
		}

	}
}
add_action('init', 'rcp_process_login_form');