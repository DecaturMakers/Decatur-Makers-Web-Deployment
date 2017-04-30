<?php 

function rcp_add_user_columns( $columns ) {
	$columns['rcp_subscription'] 	= __('Subscription', 'rcp' );
    $columns['rcp_status'] 			= __( 'Status', 'rcp' );
	$columns['rcp_links'] 			= __( 'Actions', 'rcp' );
    return $columns;
}
add_filter( 'manage_users_columns', 'rcp_add_user_columns' );
 
function rcp_show_user_columns( $value, $column_name, $user_id ) {
	if ( 'rcp_status' == $column_name )
		return rcp_get_status( $user_id );
	if ( 'rcp_subscription' == $column_name ) {
		return rcp_get_subscription( $user_id );
	}
	if ( 'rcp_links' == $column_name ) {
		$page = admin_url( '/admin.php?page=rcp-members' );
		if( rcp_is_active( $user_id ) ) {
			$links = '<a href="' . esc_url( $page ) . '&edit_member=' . esc_attr( absint( $user_id ) ) . '">' . __( 'Edit Subscription', 'rcp' ) . '</a>';
		} else {
			$links = '<a href="' . esc_url( $page ) . '&edit_member=' . esc_attr( absint( $user_id ) ) . '">' . __( 'Add Subscription', 'rcp' ) . '</a>';
		}
		
		return $links;
	}
	return $value;
}
add_filter( 'manage_users_custom_column',  'rcp_show_user_columns', 100, 3 );
