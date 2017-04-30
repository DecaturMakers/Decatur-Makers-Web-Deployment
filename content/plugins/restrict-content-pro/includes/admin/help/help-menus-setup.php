<?php

function rcp_help_tabs() {
	global $rcp_members_page;
	global $rcp_subscriptions_page;
	global $rcp_discounts_page;
	global $rcp_payments_page;
	global $rcp_settings_page;
	global $rcp_export_page;
	
	$screen = get_current_screen();
	
	if(!is_object($screen))
		return;
		
	switch($screen->id) :

		case $rcp_members_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_members_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'adding_subs',
					'title' => __( 'Adding Subscriptions', 'rcp' ),
					'content' => rcp_render_members_tab_content( 'adding_subs' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'member_details',
					'title' => __( 'Member Details', 'rcp' ),
					'content' => rcp_render_members_tab_content( 'member_details' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'editing_member',
					'title' => __( 'Editing Members', 'rcp' ),
					'content' => rcp_render_members_tab_content( 'editing_member' )
				)
			);
		break;
		
		case $rcp_subscriptions_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_subscriptions_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'adding_subscriptions',
					'title' => __( 'Adding Subscriptions', 'rcp' ),
					'content' => rcp_render_subscriptions_tab_content( 'adding_subscriptions' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'editing_subscriptions',
					'title' => __( 'Editing Subscriptions', 'rcp' ),
					'content' => rcp_render_subscriptions_tab_content( 'editing_subscriptions' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'deleting_subscriptions',
					'title' => __( 'Deleting Subscriptions', 'rcp' ),
					'content' => rcp_render_subscriptions_tab_content( 'deleting_subscriptions' )
				)
			);
		break;
		
		case $rcp_discounts_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_discounts_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'adding_discounts',
					'title' => __( 'Adding Discounts', 'rcp' ),
					'content' => rcp_render_discounts_tab_content( 'adding_discounts' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'editing_discounts',
					'title' => __( 'Editing Discounts', 'rcp' ),
					'content' => rcp_render_discounts_tab_content( 'editing_discounts' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'using_discounts',
					'title' => __( 'Using Discounts', 'rcp' ),
					'content' => rcp_render_discounts_tab_content( 'using_discounts' )
				)
			);
		break;
		
		case $rcp_payments_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_payments_tab_content( 'general' )
				)
			);
		break;

		case $rcp_settings_page :
			$screen->add_help_tab(
				array(
					'id' => 'general',
					'title' => __( 'General', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'general' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'messages',
					'title' => __( 'Messages', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'messages' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'paypal',
					'title' => __( 'PayPal', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'paypal' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'signup_forms',
					'title' => __( 'Signup Forms', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'signup_forms' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'emails',
					'title' => __( 'Emails', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'emails' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'misc',
					'title' => __( 'Misc', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'misc' )
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'logging',
					'title' => __( 'Logging', 'rcp' ),
					'content' => rcp_render_settings_tab_content( 'logging' )
				)
			);
			break;
			
	default:
		break;
	
	endswitch;
}
add_action('admin_menu', 'rcp_help_tabs', 100);