<?php

// retrieves the expiration date of a subscription level
function rcp_ajax_get_subscription_expiration() {
	if( isset( $_POST['subscription_level'] ) ) {
		$level_id = absint( $_POST['subscription_level'] );
		$expiration = rcp_calculate_subscription_expiration( $level_id );
		echo $expiration;
	}
	die();
}
add_action( 'wp_ajax_rcp_get_subscription_expiration', 'rcp_ajax_get_subscription_expiration' );

// processes the ajax re-ordering request
function rcp_update_subscription_order() {
	if( isset( $_POST['recordsArray'] ) ) {
		global $wpdb, $rcp_db_name;
		$subscription_levels = $_POST['recordsArray'];
		$counter = 1;
		foreach ( $subscription_levels as $level ) {
			$new_order = $wpdb->update(
				$rcp_db_name,
				array('list_order' 	=> $counter ),
				array('id' 			=> $level),
				array('%d')
			);
			$counter++;
		}
		// clear the cache
		delete_transient('rcp_subscription_levels');
	}
	die();
}
add_action( 'wp_ajax_update-subscription-order', 'rcp_update_subscription_order' );

// retrieves a list of users via live search
function rcp_search_users() {

	if( wp_verify_nonce( $_POST['rcp_nonce'], 'rcp_member_nonce' ) ) {

		$search_query = trim( $_POST['user_name'] );

		$found_users = get_users( array(
				'number' => 9999,
				'search' => $search_query . '*'
			)
		);

		if( $found_users ) {
			$user_list = '<ul>';
				foreach( $found_users as $user ) {
					$user_list .= '<li><a href="#" data-login="' . esc_attr( $user->user_login ) . '">' . esc_html( $user->user_login ) . '</a></li>';
				}
			$user_list .= '</ul>';

			echo json_encode( array( 'results' => $user_list, 'id' => 'found' ) );

		} else {
			echo json_encode( array( 'msg' => __( 'No users found', 'rcp' ), 'results' => 'none', 'id' => 'fail' ) );
		}

	}
	die();
}
add_action( 'wp_ajax_rcp_search_users', 'rcp_search_users' );