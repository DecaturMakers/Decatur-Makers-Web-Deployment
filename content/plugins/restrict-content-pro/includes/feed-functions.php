<?php

/*******************************************
* Restrict Content Feed Functions
*******************************************/

function rcp_filter_feed_posts( $content ) {
	global $rcp_options;

	if( ! is_feed() )
		return $content;

	$hide_from_feed = get_post_meta( get_the_ID(), 'rcp_hide_from_feed', true );
	if ( $hide_from_feed == 'on' ) {
		if( rcp_is_paid_content( $post_id ) ) {
			return rcp_format_teaser( $rcp_options['paid_message'] );
		} else {
			return rcp_format_teaser( $rcp_options['free_message'] );
		}
	}
	return do_shortcode( $content );

}
add_action( 'the_excerpt', 'rcp_filter_feed_posts' );
add_action( 'the_content', 'rcp_filter_feed_posts' );
