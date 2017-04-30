<?php

/*************************************************************************
* this file processes all new subscription creations and updates
* also manages adding/editings subscriptions to users
* User registration and login is handled in handle-registration-login.php
**************************************************************************/
function rcp_process_data() {

	if( ! is_admin() )
		return;

	if( ! empty( $_POST ) ) {

		/****************************************
		* subscription levels
		****************************************/

		// add a new subscription level
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-level' ) {

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$levels = new RCP_Levels();

			$add = $levels->insert( $_POST );

			if( $add ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_added';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_not_added';
			}
			wp_safe_redirect( $url ); exit;
		}

		// edit a subscription level
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-subscription') {

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$levels = new RCP_Levels();

			$update = $levels->update( $_POST['subscription_id'], $_POST );

			if($update) {
				// clear the cache
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_updated';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_not_updated';
			}

			wp_safe_redirect( $url ); exit;
		}

		// add a subscription for an existing member
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-subscription' ) {

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			if ( isset( $_POST['expiration'] ) &&  strtotime( 'NOW' ) > strtotime( $_POST['expiration'] ) && 'none' !== $_POST['expiration'] ) :

				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&rcp_message=user_not_added';
				header( "Location:" . $url );

			else:

				$levels     = new RCP_Levels();

				$user       = get_user_by( 'login', $_POST['user'] );

				$expiration = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';
				$level_id   = absint( $_POST['level'] );

				rcp_set_status( $user->ID, 'active' );
				rcp_set_expiration_date( $user->ID, $expiration );
				
				update_user_meta( $user->ID, 'rcp_signup_method', 'manual' );

				// Add a role, if needed, to the user
				$subscription = $levels->get_level( $level_id );

				update_user_meta( $user->ID, 'rcp_subscription_level', $level_id );

				// Add the new user role
				$role = ! empty( $subscription->role ) ? $subscription->role : 'subscriber';
				$user->add_role( $role );


				if( isset( $_POST['recurring'] ) ) {
					update_user_meta( $user->ID, 'rcp_recurring', 'yes' );
				} else {
					delete_user_meta( $user->ID, 'rcp_recurring' );
				}
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&rcp_message=user_added';
				header( "Location:" .  $url);

			endif;

		}

		// edit a member's subscription
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-member' ) {

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$levels       = new RCP_Levels();
			$user_id      = absint( $_POST['user'] );
			$member       = new RCP_Member( $user_id );
			$status       = sanitize_text_field( $_POST['status'] );
			$level_id     = absint( $_POST['level'] );
			$expiration   = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';


			if( isset( $_POST['level'] ) ) {

				$current_id = rcp_get_subscription_id( $user_id );
				$new_level  = $levels->get_level( $level_id );
				$old_level  = $levels->get_level( $current_id );

				if( $current_id != $level_id ) {

					update_user_meta( $user_id, 'rcp_subscription_level', $level_id );

					// Remove the old user role
					$role = ! empty( $old_level->role ) ? $old_level->role : 'subscriber';
					$member->remove_role( $role );

					// Add the new user role
					$role = ! empty( $new_level->role ) ? $new_level->role : 'subscriber';
					$member->add_role( $role );

				}
			}

			if( isset( $_POST['recurring'] ) ) {
				$member->set_recurring( true );
			} else {
				$member->set_recurring( false );
			}

			if( isset( $_POST['trialing'] ) ) {
				update_user_meta( $user_id, 'rcp_is_trialing', 'yes' );
			} else {
				delete_user_meta( $user_id, 'rcp_is_trialing' );
			}

			if( isset( $_POST['signup_method'] ) ) {
				update_user_meta( $user_id, 'rcp_signup_method', $_POST['signup_method'] );
			}

			if( isset( $_POST['notes'] ) ) {
				update_user_meta( $user_id, 'rcp_notes', wp_kses( $_POST['notes'], array() ) );
			}

			if( isset( $_POST['status'] ) ) {
				rcp_set_status( $user_id, $status );
			}

			if( isset( $_POST['payment-profile-id'] ) ) {
				$member->set_payment_profile_id( $_POST['payment-profile-id'] );
			}

			rcp_set_expiration_date( $user_id, $expiration );

			do_action( 'rcp_edit_member', $user_id );

			wp_redirect( admin_url( 'admin.php?page=rcp-members&edit_member=' . $user_id . '&rcp_message=user_updated' ) ); exit;

		}

		/****************************************
		* discount codes
		****************************************/

		// add a new discount code
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-discount' ) {

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();

			// Setup unsanitized data
			$data = array(
				'name'            => $_POST['name'],
				'description'     => $_POST['description'],
				'amount'          => $_POST['amount'],
				'unit'            => isset( $_POST['unit'] ) && $_POST['unit'] == '%' ? '%' : 'flat',
				'code'            => $_POST['code'],
				'status'          => 'active',
				'expiration'      => $_POST['expiration'],
				'max_uses'        => $_POST['max'],
				'subscription_id' => $_POST['subscription']
			);

			$add = $discounts->insert( $data );

			if( $add ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&rcp_message=discount_added';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&rcp_message=discount_not_added';
			}

			wp_safe_redirect( $url ); exit;
		}

		// edit a discount code
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-discount' ) {

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();

			// Setup unsanitized data
			$data = array(
				'name'            => $_POST['name'],
				'description'     => $_POST['description'],
				'amount'          => $_POST['amount'],
				'unit'            => isset( $_POST['unit'] ) && $_POST['unit'] == '%' ? '%' : 'flat',
				'code'            => $_POST['code'],
				'status'          => $_POST['status'],
				'expiration'      => $_POST['expiration'],
				'max_uses'        => $_POST['max'],
				'subscription_id' => $_POST['subscription']
			);

			$update = $discounts->update( $_POST['discount_id'], $data );

			if( $update ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-updated=1';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-updated=0';
			}

			wp_safe_redirect( $url ); exit;
		}

		// add a new manual payment
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-payment' ) {

			if( ! current_user_can( 'rcp_manage_payments' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$payments = new RCP_Payments();

			$user = get_user_by( 'login', $_POST['user'] );

			if( $user ) {

				$data = array(
					'amount'           => $_POST['amount'],
					'user_id'          => $user->ID,
					'date'             => date( 'Y-m-d', strtotime( $_POST['date'] ) ) . ' ' . date( 'H:i:s', current_time( 'timestamp' ) ),
					'payment_type'     => 'manual',
					'subscription'     => rcp_get_subscription( $user->ID ),
					'subscription_key' => rcp_get_subscription_key( $user->ID ),
					'transaction_id'   => sanitize_text_field( $_POST['transaction-id'] ),
					'status'           => sanitize_text_field( $_POST['status'] ),
				);

				$add = $payments->insert( $data );

			}

			if( ! empty( $add ) ) {
				$url = admin_url( 'admin.php?page=rcp-payments&rcp_message=payment_added' );
			} else {
				$url = admin_url( 'admin.php?page=rcp-payments&rcp_message=payment_not_added' );
			}
			wp_safe_redirect( $url ); exit;
		}

		// edit a payment
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-payment' ) {

			if( ! current_user_can( 'rcp_manage_payments' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$payments = new RCP_Payments();

			$payment_id = absint( $_POST['payment-id'] );
			$user      = get_user_by( 'login', $_POST['user'] );

			if( $user && $payment_id ) {

				$data = array(
					'amount'           => sanitize_text_field( $_POST['amount'] ),
					'user_id'          => $user->ID,
					'date'             => date( 'Y-m-d H:i:s', strtotime( $_POST['date'] ) ),
					'subscription'     => rcp_get_subscription( $user->ID ),
					'subscription_key' => rcp_get_subscription_key( $user->ID ),
					'transaction_id'   => sanitize_text_field( $_POST['transaction-id'] ),
					'status'           => sanitize_text_field( $_POST['status'] ),
				);

				$update = $payments->update( $payment_id, $data );

			}

			if( ! empty( $update ) ) {
				$url = admin_url( 'admin.php?page=rcp-payments&rcp_message=payment_updated' );
			} else {
				$url = admin_url( 'admin.php?page=rcp-payments&rcp_message=payment_not_updated' );
			}
			wp_safe_redirect( $url ); exit;
		}

	}

	/*************************************
	* delete data
	*************************************/
	if( ! empty( $_GET ) ) {

		/* member processing */
		if( isset( $_GET['revoke_access'] ) ) {

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			rcp_set_status( urldecode( absint( $_GET['revoke_access'] ) ), 'cancelled' );
		}
		if( isset( $_GET['activate_member'] ) ) {

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			rcp_set_status( urldecode( absint( $_GET['activate_member'] ) ), 'active' );
		}
		if( isset( $_GET['cancel_member'] ) ) {

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			rcp_cancel_member_payment_profile( urldecode( absint( $_GET['cancel_member'] ) ) );
			wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'member_cancelled', 'admin.php?page=rcp-members' ) ) ); exit;
		}

		/* subscription processing */
		if( isset( $_GET['delete_subscription'] ) && $_GET['delete_subscription'] > 0) {

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$members_of_subscription = rcp_get_members_of_subscription( absint( $_GET['delete_subscription'] ) );

			// cancel all active members of this subscription
			if( $members_of_subscription ) {
				foreach( $members_of_subscription as $member ) {
					rcp_set_status( $member, 'cancelled' );
				}
			}
			$levels = new RCP_Levels();
			$levels->remove( $_GET['delete_subscription'] );

		}
		if( isset( $_GET['activate_subscription'] ) && $_GET['activate_subscription'] > 0) {

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$levels = new RCP_Levels();
			$update = $levels->update( absint( $_GET['activate_subscription'] ), array( 'status' => 'active' ) );
			delete_transient( 'rcp_subscription_levels' );
		}
		if( isset( $_GET['deactivate_subscription'] ) && $_GET['deactivate_subscription'] > 0) {

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$levels = new RCP_Levels();
			$update = $levels->update( absint( $_GET['deactivate_subscription'] ), array( 'status' => 'inactive' ) );
			delete_transient( 'rcp_subscription_levels' );
		}

		/* discount processing */
		if( ! empty( $_GET['delete_discount'] ) ) {

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();
			$discounts->delete( $_GET['delete_discount'] );
		}
		if( ! empty( $_GET['activate_discount'] ) ) {

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();
			$discounts->update( $_GET['activate_discount'], array( 'status' => 'active' ) );
		}
		if( ! empty( $_GET['deactivate_discount'] ) ) {

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();
			$discounts->update( $_GET['deactivate_discount'], array( 'status' => 'disabled' ) );
		}
		if( ! empty( $_GET['rcp-action'] ) && $_GET['rcp-action'] == 'delete_payment' && wp_verify_nonce( $_GET['_wpnonce'], 'rcp_delete_payment_nonce' ) ) {

			if( ! current_user_can( 'rcp_manage_payments' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$payments = new RCP_Payments();
			$payments->delete( absint( $_GET['payment_id'] ) );
			wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'payment_deleted', 'admin.php?page=rcp-payments' ) ) ); exit;
		}

	}
}
add_action( 'admin_init', 'rcp_process_data' );