<?php

/*
* Hides all premium posts from non active subscribers
*/
function rcp_hide_premium_posts( $query ) {
	global $rcp_options, $user_ID;

	$suppress_filters = isset( $query->query_vars['suppress_filters'] );

	if( isset( $rcp_options['hide_premium'] ) && ! is_singular() && false == $suppress_filters ) {
		if( ! rcp_is_active( $user_ID ) ) {
			$premium_ids = rcp_get_paid_posts();
			if( $premium_ids ) {
				$query->set( 'post__not_in', $premium_ids );
			}
		}
	}
}
add_action( 'pre_get_posts', 'rcp_hide_premium_posts', 99999 );