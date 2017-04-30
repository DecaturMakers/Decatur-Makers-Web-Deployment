<?php
if( isset( $_GET['edit_member'] ) ) {
	$member_id = absint( $_GET['edit_member'] );
} elseif( isset( $_GET['view_member'] ) ) {
	$member_id = absint( $_GET['view_member'] );
}
$member = new RCP_Member( $member_id );
?>
<h2>
	<?php _e( 'Edit Member:', 'rcp' ); echo ' ' . $member->display_name; ?> - 
	<a href="<?php echo admin_url( '/admin.php?page=rcp-members' ); ?>" class="button-secondary">
		<?php _e( 'Cancel', 'rcp' ); ?>
	</a>
</h2>
<?php if( $switch_to_url = rcp_get_switch_to_url( $member->ID ) ) { ?>
	<a href="<?php echo esc_url( $switch_to_url ); ?>" class="rcp_switch"><?php _e('Switch to User', 'rcp'); ?></a>
<?php } ?>
<form id="rcp-edit-member" action="" method="post">
	<table class="form-table">
		<tbody>
			<?php do_action( 'rcp_edit_member_before', $member->ID ); ?>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-status"><?php _e( 'Status', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="status" id="rcp-status">
						<?php
							$statuses = array( 'active', 'expired', 'cancelled', 'pending', 'free' );
							$current_status = rcp_get_status( $member->ID );
							foreach( $statuses as $status ) : 
								echo '<option value="' . esc_attr( $status ) .  '"' . selected( $status, rcp_get_status( $member->ID ), false ) . '>' . ucwords( $status ) . '</option>';
							endforeach;
						?>
					</select>
					<p class="description"><?php _e( 'The status of this user\'s subscription', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-level"><?php _e( 'Subscription Level', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="level" id="rcp-level">
						<?php
							foreach( rcp_get_subscription_levels( 'all' ) as $key => $level) :
								echo '<option value="' . esc_attr( absint( $level->id ) ) . '"' . selected( $level->name, rcp_get_subscription( $member->ID ), false ) . '>' . esc_html( $level->name ) . '</option>';
							endforeach;
						?>
					</select>
					<p class="description"><?php _e( 'Choose the subscription level for this user', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-key"><?php _e( 'Subscription Key', 'rcp' ); ?></label>
				</th>
				<td>
					<input id="rcp-key" type="text" style="width: 200px;" value="<?php echo esc_attr( $member->get_subscription_key() ); ?>" disabled="disabled"/>
					<p class="description"><?php _e( 'The member\'s subscription key. This cannot be changed.', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-expiration"><?php _e( 'Expiration date', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="expiration" id="rcp-expiration" type="text" style="width: 120px;" class="rcp-datepicker" value="<?php echo esc_attr( get_user_meta( $member->ID, 'rcp_expiration', true ) ); ?>"/>
					<label for="rcp-unlimited">
						<input name="unlimited" id="rcp-unlimited" type="checkbox"<?php checked( get_user_meta( $member->ID, 'rcp_expiration', true ), 'none' ); ?>/>
						<span class="description"><?php _e( 'Never expires?', 'rcp' ); ?></span>
					</label>
					<p class="description"><?php _e( 'Enter the expiration date for this user in the format of yyyy-mm-dd', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-payment-profile-id"><?php _e( 'Payment Profile ID', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="payment-profile-id" id="rcp-payment-profile-id" type="text" style="width: 200px;" value="<?php echo esc_attr( $member->get_payment_profile_id() ); ?>"/>
					<p class="description"><?php _e( 'This is the customer\'s payment profile ID in the payment processor', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<?php _e( 'Recurring', 'rcp' ); ?>
				</th>
				<td>
					<label for="rcp-recurring">
						<input name="recurring" id="rcp-recurring" type="checkbox" value="1" <?php checked( 1, rcp_is_recurring( $member->ID ) ); ?>/>
						<?php _e( 'Is this user\'s subscription recurring?', 'rcp' ); ?>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<?php _e( 'Trialing', 'rcp' ); ?>
				</th>
				<td>
					<label for="rcp-trialing">
						<input name="trialing" id="rcp-trialing" type="checkbox" value="1" <?php checked( 1, rcp_is_trialing( $member->ID ) ); ?>/>
						<?php _e( 'Does this user have a trial membership?', 'rcp' ); ?>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<?php _e( 'Sign Up Method', 'rcp' ); ?>
				</th>
				<td>
					<?php $method = get_user_meta( $member->ID, 'rcp_signup_method', true ) ? get_user_meta( $member->ID, 'rcp_signup_method', true ) : 'live';?>
					<select name="signup_method" id="rcp-signup-method">
						<option value="live" <?php selected( $method, 'live' ); ?>><?php _e( 'User Signup', 'rcp' ); ?>
						<option value="manual" <?php selected( $method, 'manual' ); ?>><?php _e( 'Added by admin manually', 'rcp' ); ?>
					</select>
					<p class="description"><?php _e( 'Was this a real signup or a membership given to the user', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-notes"><?php _e( 'User Notes', 'rcp' ); ?></label>
				</th>
				<td>
					<textarea name="notes" id="rcp-notes" class="large-text" rows="10" cols="50"><?php echo esc_textarea( get_user_meta( $member->ID, 'rcp_notes', true ) ); ?></textarea>
					<p class="description"><?php _e( 'Use this area to record notes about this user if needed', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<?php _e( 'Discount codes used', 'rcp' ); ?>
				</th>
				<td>
					<?php
					$discounts = get_user_meta( $member->ID, 'rcp_user_discounts', true );
					if( $discounts ) {
						foreach( $discounts as $discount ) {
							if( is_string( $discount ) ) {
								echo $discount . '<br/>';
							}
						}
					} else {
						_e( 'None', 'rcp' );
					}
					?>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<?php _e( 'Payments', 'rcp' ); ?>
				</th>
				<td>
					<?php echo rcp_print_user_payments( $member->ID ); ?>
				</td>
			</tr>
			<?php do_action( 'rcp_edit_member_after', $member->ID ); ?>
		</tbody>
	</table>
	<p class="submit">
		<input type="hidden" name="rcp-action" value="edit-member"/>
		<input type="hidden" name="user" value="<?php echo absint( urldecode( $_GET['edit_member'] ) ); ?>"/>
		<input type="submit" value="<?php _e( 'Update User Subscription', 'rcp' ); ?>" class="button-primary"/>
	</p>
</form>
