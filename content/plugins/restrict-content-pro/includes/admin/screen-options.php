<?php

function rcp_screen_options() {

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
			$args = array(
				'label' => __('Members per page', 'rcp'),
				'default' => 10,
				'option' => 'rcp_members_per_page'
			);
			add_screen_option( 'per_page', $args );
			break;
			
		case $rcp_payments_page :
			$args = array(
				'label' => __('Payments per page', 'rcp'),
				'default' => 10,
				'option' => 'rcp_payments_per_page'
			);
			add_screen_option( 'per_page', $args );
			break;	
		
	endswitch; 
}
 
function rcp_set_screen_option($status, $option, $value) {
	if ( 'rcp_members_per_page' == $option ) return $value;
	if ( 'rcp_payments_per_page' == $option ) return $value;
}
add_filter('set-screen-option', 'rcp_set_screen_option', 10, 3);
