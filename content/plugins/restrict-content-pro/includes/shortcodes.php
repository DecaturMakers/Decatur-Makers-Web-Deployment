<?php

/*******************************************
* Restrict Content Short Codes
*******************************************/

// shortcode or restricting content to registered users and or user roles
function rcp_restrict_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'userlevel' 	=> 'none',
		'message' 		=> '',
		'paid' 			=> false,
		'level' 		=> 0,
		'subscription' 	=> ''
	), $atts ) );

	global $rcp_options, $user_ID;

	if( strlen( trim( $message ) ) > 0 ) {
		$teaser = $message;
	} elseif( $paid ) {
		$teaser = $rcp_options['paid_message'];
	} else {
		$teaser = $rcp_options['free_message'];
	}

	$subscription = explode( ',', $subscription );

	if( $paid ) {

		$has_access = false;
		if( rcp_is_active( $user_ID ) && rcp_user_has_access( $user_ID, $level ) ) {
			$has_access = true;

			if( ! empty( $subscription ) && ! empty( $subscription[0] ) ) {
				if( ! in_array( rcp_get_subscription_id( $user_ID ), $subscription ) ) {
					$has_access = false;
				}
			}
		}

		if ( $userlevel == 'admin' && current_user_can( 'switch_themes' ) && $has_access ) {
			return do_shortcode( wpautop( $content ) );
		}
		if ( $userlevel == 'editor' && current_user_can( 'moderate_comments' ) && $has_access ) {
			return do_shortcode( wpautop( $content ) );
		}
		if ( $userlevel == 'author' && current_user_can( 'upload_files' ) && $has_access ) {
			return do_shortcode(  wpautop( $content ) );
		}
		if ( $userlevel == 'contributor' && current_user_can( 'edit_posts' ) && $has_access ) {
		 	return do_shortcode( wpautop( $content ) );
		}
		if ( $userlevel == 'subscriber' && current_user_can( 'read' ) && $has_access ) {
		 	return do_shortcode( wpautop( $content ) );
		}
		if ( $userlevel == 'none' && is_user_logged_in() && $has_access ) {
		 	return do_shortcode( wpautop( $content ) );
		} else {
			return '<div class="rcp_restricted rcp_paid_only">' . rcp_format_teaser($teaser) . '</div>';
		}

	} else {

		$has_access = false;
		if(rcp_user_has_access($user_ID, $level)) {
			$has_access = true;
			if( ! empty( $subscription ) && ! empty( $subscription[0] ) ) {
				if( in_array( rcp_get_subscription_id( $user_ID ), $subscription ) ) {
					$has_access = false;
				}
			}
		}

		if ( $userlevel == 'admin' && current_user_can( 'switch_themes' ) && $has_access ) {
			return do_shortcode( wpautop( $content ) );
		} elseif ( $userlevel == 'editor' && current_user_can( 'moderate_comments' ) && $has_access ) {
			return do_shortcode( wpautop( $content ) );
		} elseif ( $userlevel == 'author' && current_user_can( 'upload_files' ) && $has_access ) {
			return do_shortcode( wpautop( $content ) );
		} elseif ( $userlevel == 'contributor' && current_user_can( 'edit_posts' ) && $has_access ) {
		 	return do_shortcode( wpautop( $content ) );
		} elseif ( $userlevel == 'subscriber' && current_user_can( 'read' ) && $has_access ) {
		 	return do_shortcode( wpautop( $content ) );
		} elseif ( $userlevel == 'none' && is_user_logged_in() && $has_access ) {
		 	return do_shortcode( wpautop( $content ) );
		} else {
			return '<div class="rcp_restricted">' . do_shortcode( wpautop( $teaser ) ) . '</div>';
		}
	}
}
add_shortcode( 'restrict', 'rcp_restrict_shortcode' );

// shows content only to active, paid users
function rcp_is_paid_user_shortcode( $atts, $content = null ) {

	global $user_ID;

	if( rcp_is_active( $user_ID ) ) {
		return do_shortcode( $content );
	}
}
add_shortcode( 'is_paid', 'rcp_is_paid_user_shortcode' );

// shows content only to logged-in free users, and can hide from paid
function rcp_is_free_user_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'hide_from_paid' => true
	), $atts ) );

	global $user_ID;

	if( $hide_from_paid ) {
		if( !rcp_is_active( $user_ID ) && is_user_logged_in() ) {
			return do_shortcode( $content );
		}
	} elseif( is_user_logged_in() ) {
		return do_shortcode( $content );
	}
}
add_shortcode( 'is_free', 'rcp_is_free_user_shortcode' );

function rcp_not_logged_in( $atts, $content = null ) {
	if( !is_user_logged_in() ) {
		return do_shortcode( $content );
	}
}
add_shortcode( 'not_logged_in', 'rcp_not_logged_in' );

// allows content to be shown to only users that don't have an active subscription
function rcp_is_not_paid( $atts, $content = null ) {
	global $user_ID;
	if( rcp_is_active( $user_ID ) )
		return;
	else
		return do_shortcode( $content );

}
add_shortcode( 'is_not_paid', 'rcp_is_not_paid' );

// shows the display name of the currently logged-in user
function rcp_user_name( $atts, $content = null ) {
	global $user_ID;
	if(is_user_logged_in()) {
		return get_userdata( $user_ID )->display_name;
	}

}
add_shortcode( 'user_name', 'rcp_user_name' );

// user registration login form
function rcp_registration_form( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'id' => null,
		'registered_message' => __( 'You are already registered and have an active subscription.', 'rcp' )
	), $atts ) );

	global $user_ID;

	// only show the registration form to non-logged-in members
	if( ! rcp_is_active( $user_ID ) || rcp_is_trialing( $user_ID ) || rcp_subscription_upgrade_possible( $user_ID ) ) {

		global $rcp_options, $rcp_load_css, $rcp_load_scripts;

		// set this to true so the CSS and JS scripts are loaded
		$rcp_load_css = true;
		$rcp_load_scripts = true;

		$output = rcp_registration_form_fields( $id );

	} else {
		$output = $registered_message;
	}
	return $output;
}
add_shortcode( 'register_form', 'rcp_registration_form' );

// user login form
function rcp_login_form( $atts, $content = null ) {

	global $post;

	$current_page = rcp_get_current_url();

	extract( shortcode_atts( array(
		'redirect' 	=> $current_page,
		'class' 	=> 'rcp_form'
	), $atts ) );

	$output = '';

	global $rcp_load_css;

	// set this to true so the CSS is loaded
	$rcp_load_css = true;

	$output = rcp_login_form_fields( array( 'redirect' => $redirect, 'class' => $class ) );

	return $output;
}
add_shortcode( 'login_form', 'rcp_login_form' );

// password reset form
function rcp_reset_password_form() {
	if( is_user_logged_in() ) {

		global $rcp_options, $rcp_load_css, $rcp_load_scripts;
		// set this to true so the CSS is loaded
		$rcp_load_css = true;
		if( isset( $rcp_options['front_end_validate'] ) ) {
			$rcp_load_scripts = true;
		}

		// get the password reset form fields
		$output = rcp_change_password_form();

		return $output;
	}
}
add_shortcode( 'password_form', 'rcp_reset_password_form' );

// displays a list of premium posts
function rcp_list_paid_posts() {
	$paid_posts = rcp_get_paid_posts();
	$list = '';
	if( $paid_posts ) {
		$list .= '<ul class="rcp_paid_posts">';
		foreach( $paid_posts as $post_id ) {
			$list .= '<li><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . get_the_title( $post_id ) . '</a></li>';
		}
		$list .= '</ul>';
	}
	return $list;
}
add_shortcode( 'paid_posts', 'rcp_list_paid_posts' );

// displays the current user's subscription details
function rcp_user_subscription_details( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'option' => ''
	), $atts ) );

	global $user_ID, $rcp_options;

	ob_start();

	if( is_user_logged_in() ) {

		rcp_get_template_part( 'subscription' );
		
	} else {

		echo rcp_login_form_fields();

	}

	return ob_get_clean();
}
add_shortcode( 'subscription_details', 'rcp_user_subscription_details' );

add_filter( 'widget_text', 'do_shortcode' );


/**
 * Profile Editor Shortcode
 *
 * Outputs the EDD Profile Editor to allow users to amend their details from the
 * front-end
 *
 * @access      public
 * @since       1.5
 */
function rcp_profile_editor_shortcode( $atts, $content = null ) {

	global $rcp_load_css;

	$rcp_load_css = true;

	ob_start();

	rcp_get_template_part( 'profile', 'editor' );

	return ob_get_clean();
}
add_shortcode( 'rcp_profile_editor', 'rcp_profile_editor_shortcode' );


/**
 * Update card form short code
 *
 * Displays a form to update the billing credit / debit card
 *
 * @access      public
 * @since       2.1
 */
function rcp_update_billing_card_shortcode( $atts, $content = null ) {
	global $rcp_load_css, $rcp_load_scripts;

	$rcp_load_css = true;
	$rcp_load_scripts = true;

	ob_start();

	if( rcp_member_can_update_billing_card() ) {

		do_action( 'rcp_before_update_billing_card_form' );
		rcp_get_template_part( 'card-update', 'form' );
		do_action( 'rcp_after_update_billing_card_form' );

	}

	return ob_get_clean();
}
add_shortcode( 'card_details', 'rcp_update_billing_card_shortcode' ); // Old version
add_shortcode( 'rcp_update_card', 'rcp_update_billing_card_shortcode' );