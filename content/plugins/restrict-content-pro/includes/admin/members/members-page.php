<?php

function rcp_members_page() {
	global $rcp_options, $rcp_db_name, $wpdb;
	$current_page = admin_url( '/admin.php?page=rcp-members' ); ?>
	<div class="wrap" id="rcp-members-page">

		<?php if( isset( $_GET['edit_member'] ) || isset( $_GET['view_member'] ) ) :
			include( 'edit-member.php' );
		else : ?>
			<h2><?php _e(' Paid Subscribers', 'rcp' ); ?></h2>
			<?php

			$subscription_id = isset( $_GET['subscription'] ) && $_GET['subscription'] != 'all' ? urldecode( $_GET['subscription'] ) : null;
			$status          = ! empty( $_GET['status'] )  ? urldecode( $_GET['status'] ) : 'active';
			$order           = ! empty( $_GET['order']  )  ? urldecode( $_GET['order']  ) : 'DESC';
			$recurring       = isset( $_GET['recurring'] ) ? absint( $_GET['recurring'] ) : null;
			$search          = ! empty( $_GET['s'] )       ? urldecode( $_GET['s'] )      : '';

			$base_url        = admin_url( 'admin.php?page=rcp-members' );
			if( $search ) {
				$base_url = add_query_arg( 's', $search, $base_url );
			}

			// get subscriber count
			$active_count    = rcp_count_members( $subscription_id, 'active', $recurring, $search );
			$pending_count   = rcp_count_members( $subscription_id, 'pending', $recurring, $search );
			$expired_count   = rcp_count_members( $subscription_id, 'expired', $recurring, $search );
			$cancelled_count = rcp_count_members( $subscription_id, 'cancelled', $recurring, $search );
			$free_count      = rcp_count_members( $subscription_id, 'free', $recurring, $search );
			$current_count   = rcp_count_members( $subscription_id, $status, $recurring, $search );

			// pagination variables
			$page            = isset( $_GET['p'] ) ? absint( $_GET['p'] ) : 1;
			$user            = get_current_user_id();
			$screen          = get_current_screen();
			$screen_option   = $screen->get_option( 'per_page', 'option' );
			$per_page        = get_user_meta( $user, $screen_option, true );
			if ( empty ( $per_page) || $per_page < 1 ) {
				$per_page    = $screen->get_option( 'per_page', 'default' );
			}
			$total_pages     = 1;
			$offset          = $per_page * ( $page - 1 );
			$total_pages     = ceil( $current_count / $per_page );

			?>
			<ul class="subsubsub">
				<li><?php _e('Status: ', 'rcp'); ?></li>
				<li>
					<a href="<?php echo esc_url( add_query_arg('status', 'active', $base_url ) ); ?>" title="<?php _e('View all active subscribers', 'rcp'); ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') || !isset($_GET['status']) ? 'class="current"' : ''; ?>>
					<?php _e('Active', 'rcp'); ?>
					</a>(<?php echo $active_count; ?>)
				</li>
				<li>
					<a href="<?php echo esc_url( add_query_arg('status', 'pending', $base_url ) ); ?>" title="<?php _e('View all pending subscribers', 'rcp'); ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'class="current"' : ''; ?>>
						<?php _e('Pending', 'rcp'); ?>
					</a>(<?php echo $pending_count; ?>)
				</li>
				<li>
					<a href="<?php echo esc_url( add_query_arg('status', 'expired', $base_url ) ); ?>" title="<?php _e('View all expired subscribers', 'rcp'); ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == 'expired') ? 'class="current"' : ''; ?>>
						<?php _e('Expired', 'rcp'); ?>
					</a>(<?php echo $expired_count; ?>)
				</li>
				<li>
					<a href="<?php echo esc_url( add_query_arg('status', 'cancelled', $base_url ) ); ?>" title="<?php _e('View all cancelled subscribers', 'rcp'); ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'class="current"' : ''; ?>>
						<?php _e('Cancelled', 'rcp'); ?>
					</a>(<?php echo $cancelled_count; ?>)
				</li>
				<li>
					<a href="<?php echo esc_url( add_query_arg('status', 'free', $base_url ) ); ?>" title="<?php _e('View all free members', 'rcp'); ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == 'free') ? 'class="current"' : ''; ?>>
						<?php _e('Free', 'rcp'); ?>
					</a>(<?php echo $free_count; ?>)
				</li>
				<?php do_action( 'rcp_members_page_statuses' ); ?>
			</ul>
			<form id="rcp-member-search" method="get" action="<?php menu_page_url( 'rcp-members' ); ?>">
				<label class="screen-reader-text" for="rcp-member-search-input"><?php _e( 'Search Members', 'rcp' ); ?></label>
				<input type="search" id="rcp-member-search-input" name="s" value="<?php echo esc_attr( $search ); ?>"/>
				<input type="hidden" name="page" value="rcp-members"/>
				<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>"/>
				<input type="submit" name="" id="rcp-member-search-submit" class="button" value="<?php _e( 'Search members', 'rcp' ); ?>"/>
			</form>
			<form id="members-filter" action="" method="get">
				<?php
				$levels = rcp_get_subscription_levels( 'all' );
				if($levels) : ?>
					<select name="subscription" id="rcp-subscription">
						<option value="all"><?php _e('All Subscriptions', 'rcp'); ?></option>
						<?php
							foreach($levels as $level) :
								echo '<option value="' . $level->id . '" ' . selected($subscription_id, $level->id, false) . '>' . $level->name . '</option>';
							endforeach;
						?>
					</select>
				<?php endif; ?>
				<select name="recurring" id="rcp-recurring">
					<option value="0"><?php _e('Either', 'rcp'); ?></option>
					<option value="1"<?php selected( 1, $recurring ); ?>><?php _e('Not Recurring', 'rcp'); ?></option>
					<option value="2"<?php selected( 2, $recurring ); ?>><?php _e('Recurring', 'rcp'); ?></option>
				</select>
				<select name="order" id="rcp-order">
					<option value="DESC" <?php selected($order, 'DESC'); ?>><?php _e('Newest First', 'rcp'); ?></option>
					<option value="ASC" <?php selected($order, 'ASC'); ?>><?php _e('Oldest First', 'rcp'); ?></option>
				</select>
				<input type="hidden" name="page" value="rcp-members"/>
				<input type="hidden" name="status" value="<?php echo isset($_GET['status']) ? $_GET['status'] : 'active'; ?>"/>
				<input type="submit" class="button-secondary" value="<?php _e('Filter', 'rcp'); ?>"/>
			</form>
			<?php do_action('rcp_members_above_table'); ?>
			<table class="wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th class="rcp-user-col"><?php _e('User', 'rcp'); ?></th>
						<th class="rcp-id-col"><?php _e('ID', 'rcp'); ?></th>
						<th class="rcp-email-col"><?php _e('Email', 'rcp'); ?></th>
						<th class="rcp-sub-col"><?php _e('Subscription', 'rcp'); ?></th>
						<th class="rcp-status-col"><?php _e('Status', 'rcp'); ?></th>
						<th class="rcp-recurring-col"><?php _e('Recurring', 'rcp'); ?></th>
						<th class="rcp-expiration-col"><?php _e('Expiration', 'rcp'); ?></th>
						<th class="rcp-role-col"><?php _e('User Role', 'rcp'); ?></th>
						<?php do_action('rcp_members_page_table_header'); ?>
						<th class="rcp-actions-role"><?php _e('Actions', 'rcp'); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th><?php _e('User', 'rcp'); ?></th>
						<th><?php _e('ID', 'rcp'); ?></th>
						<th><?php _e('Email', 'rcp'); ?></th>
						<th><?php _e('Subscription', 'rcp'); ?></th>
						<th><?php _e('Status', 'rcp'); ?></th>
						<th><?php _e('Recurring', 'rcp'); ?></th>
						<th><?php _e('Expiration', 'rcp'); ?></th>
						<th><?php _e('User Role', 'rcp'); ?></th>
						<?php do_action('rcp_members_page_table_footer'); ?>
						<th><?php _e('Actions', 'rcp'); ?></th>
					</tr>
				</tfoot>
				<tbody>
				<?php

				if( isset( $_GET['signup_method'] ) ) {
					$method = $_GET['signup_method'] == 'live' ? 'live' : 'manual';
					$members = get_users( array(
							'meta_key' => 'rcp_signup_method',
							'meta_value' => $method,
							'number' => 999999
						)
					);
					$per_page = 999999;
				} else {
					$members = rcp_get_members( $status, $subscription_id, $offset, $per_page, $order, $recurring, $search );
				}
				if($members) :
					$i = 1;
					foreach( $members as $key => $member) : ?>
						<tr class="rcp_row <?php do_action( 'rcp_member_row_class', $member ); if( rcp_is_odd( $i ) ) { echo ' alternate'; } ?>">
							<td><a href="<?php echo add_query_arg( 'user_id', $member->ID, admin_url( 'user-edit.php' ) ); ?>" title="<?php _e( 'View User\'s Profile', 'rcp' ); ?>"><?php echo $member->user_login; ?></a></td>
							<td><?php echo $member->ID; ?></td>
							<td><?php echo $member->user_email; ?></td>
							<td><?php echo rcp_get_subscription($member->ID); ?></td>
							<td><?php echo rcp_print_status($member->ID, false); ?></td>
							<td><?php echo rcp_is_recurring($member->ID) ? __('yes', 'rcp') : __('no', 'rcp'); ?>
							<td><?php echo rcp_get_expiration_date($member->ID); ?></td>
							<td><?php echo rcp_get_user_role($member->ID); ?></td>
							<?php do_action('rcp_members_page_table_column', $member->ID); ?>
							<td>								
								<?php if( current_user_can( 'rcp_manage_members' ) ) : ?>
									<a href="<?php echo esc_url( add_query_arg('edit_member', $member->ID, $current_page) ); ?>"><?php _e('Edit', 'rcp'); ?></a>
									<?php if(isset($_GET['status']) && $_GET['status'] == 'cancelled') { ?>
										| <a href="<?php echo esc_url( add_query_arg('activate_member', $member->ID, $current_page) ); ?>" class="rcp_activate"><?php _e('Enable Access', 'rcp'); ?></a>
									<?php } elseif( (isset($_GET['status']) && $_GET['status'] == 'active') || !isset($_GET['status'])) {  ?>
										| <a href="<?php echo esc_url( add_query_arg('revoke_access', $member->ID, $current_page) ); ?>" class="rcp_revoke"><?php _e('Revoke Access', 'rcp'); ?></a>
									<?php } ?>
									<?php if( rcp_can_member_cancel( $member->ID ) ) { ?>
										| <a href="<?php echo wp_nonce_url( add_query_arg('cancel_member', $member->ID, $current_page ), 'rcp-cancel-nonce' ); ?>" class="rcp_cancel"><?php _e('Cancel', 'rcp'); ?></a>
									<?php } ?>
									<?php if( $switch_to_url = rcp_get_switch_to_url( $member->ID ) ) { ?>
										| <a href="<?php echo esc_url( $switch_to_url ); ?>" class="rcp_switch"><?php _e('Switch to User', 'rcp'); ?></a>
									<?php } ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php $i++;
					endforeach;
				else : ?>
					<tr><td colspan="9"><?php _e('No subscribers found', 'rcp'); ?></td></tr>
				<?php endif; ?>
			</table>
			<?php if ($total_pages > 1 && !isset($_GET['signup_method']) ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages alignright">
						<?php
							$query_string = $_SERVER['QUERY_STRING'];
							$base = 'admin.php?' . remove_query_arg( 'p', $query_string ) . '%_%';
							echo paginate_links( array(
								'base' => $base,
								'format' => '&p=%#%',
								'prev_text' => __('&laquo; Previous'),
								'next_text' => __('Next &raquo;'),
								'total' => $total_pages,
								'current' => $page,
								'end_size' => 1,
								'mid_size' => 5,
							));
						?>
				    </div>
				</div><!--end .tablenav-->
			<?php endif; ?>
			<?php do_action('rcp_members_below_table'); ?>
			<h3><?php _e('Add New Subscription (for existing user)', 'rcp'); ?></h3>
			<form id="rcp-add-new-member" action="" method="post">
				<table class="form-table">
					<tbody>
						<tr class="form-field">
							<th scope="row" valign="top">
								<label for="rcp-username"><?php _e('Username', 'rcp'); ?></label>
							</th>
							<td>
								<input type="text" name="user" id="rcp-user" autocomplete="off" class="regular-text rcp-user-search" style="width: 120px;"/>
								<img class="rcp-ajax waiting" src="<?php echo admin_url('images/wpspin_light.gif'); ?>" style="display: none;"/>
								<div id="rcp_user_search_results"></div>
								<p class="description"><?php _e('Begin typing the user name to add a subscription to.', 'rcp'); ?></p>
							</td>
						</tr>
						<tr class="form-field">
							<th scope="row" valign="top">
								<label for="rcp-level"><?php _e('Subscription Level', 'rcp'); ?></label>
							</th>
							<td>
								<select name="level" id="rcp-level">
									<option value="choose"><?php _e('--choose--', 'rcp'); ?></option>
									<?php
										foreach( rcp_get_subscription_levels() as $level) :
											echo '<option value="' . $level->id . '">' . $level->name . '</option>';
										endforeach;
									?>
								</select>
								<p class="description"><?php _e('Choose the subscription level for this user', 'rcp'); ?></p>
							</td>
						</tr>
						<tr class="form-field">
							<th scope="row" valign="top">
								<label for="rcp-expiration"><?php _e('Expiration date', 'rcp'); ?></label>
							</th>
							<td>
								<input name="expiration" id="rcp-expiration" type="text" style="width: 120px;" class="rcp-datepicker"/>
								<label for="rcp-unlimited">
									<input name="unlimited" id="rcp-unlimited" type="checkbox"/>
									<span class="description"><?php _e( 'Never expires?', 'rcp' ); ?></span>
								</label>
								<p class="description"><?php _e('Enter the expiration date for this user in the format of yyyy-mm-dd', 'rcp'); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="hidden" name="rcp-action" value="add-subscription"/>
					<input type="submit" value="<?php _e('Add User Subscription', 'rcp'); ?>" class="button-primary"/>
				</p>
			</form>

		<?php endif; ?>

	</div><!--end wrap-->

	<?php
}