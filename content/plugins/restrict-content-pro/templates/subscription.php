<?php

global $user_ID, $rcp_options, $rcp_load_css;

$rcp_load_css = true;

do_action( 'rcp_subscription_details_top' );

if( isset( $_GET['profile'] ) && 'cancelled' == $_GET['profile'] ) : ?>
<p class="rcp_success"><span><?php _e( 'Your profile has been successfully cancelled.', 'rcp' ); ?></span></p>
<?php endif; ?>
<table class="rcp-table" id="rcp-account-overview">
	<thead>
		<tr>
			<th><?php _e( 'Status', 'rcp' ); ?></th>
			<th><?php _e( 'Subscription', 'rcp' ); ?></th>
			<?php if( rcp_is_recurring() && ! rcp_is_expired() ) : ?>
			<th><?php _e( 'Renewal Date', 'rcp' ); ?></th>
			<?php else : ?>
			<th><?php _e( 'Expiration', 'rcp' ); ?></th>
			<?php endif; ?>
			<th><?php _e( 'Actions', 'rcp' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php rcp_print_status(); ?></td>
			<td><?php echo rcp_get_subscription(); ?></td>
			<td><?php echo rcp_get_expiration_date(); ?></td>
			<td>
				<?php
				if( ( ( rcp_is_expired( $user_ID ) || ! rcp_is_recurring( $user_ID ) ) || rcp_get_status( $user_ID ) == 'cancelled' ) && rcp_subscription_upgrade_possible( $user_ID ) ) {
					echo '<a href="' . esc_url( get_permalink( $rcp_options['registration_page'] ) ) . '" title="' . __( 'Renew your subscription', 'rcp' ) . '" class="rcp_sub_details_renew">' . __( 'Renew your subscription', 'rcp' ) . '</a>';
				} elseif( ! rcp_is_active( $user_ID ) && rcp_subscription_upgrade_possible( $user_ID ) ) {
					echo '<a href="' . esc_url( get_permalink( $rcp_options['registration_page'] ) ) . '" title="' . __( 'Upgrade your subscription', 'rcp' ) . '" class="rcp_sub_details_renew">' . __( 'Upgrade your subscription', 'rcp' ) . '</a>';
				} elseif( rcp_is_active( $user_ID ) && rcp_can_member_cancel( $user_ID ) ) {
					echo '<a href="' . rcp_get_member_cancel_url( $user_ID ) . '" title="' . __( 'Cancel your subscription', 'rcp' ) . '">' . __( 'Cancel your subscription', 'rcp' ) . '</a>';
				}
				do_action( 'rcp_subscription_details_action_links' );
				?>
			</td>
		</tr>
	</tbody>
</table>
<table class="rcp-table" id="rcp-payment-history">
	<thead>
		<tr>
			<th><?php _e( 'Invoice #', 'rcp' ); ?></th>
			<th><?php _e( 'Subscription', 'rcp' ); ?></th>
			<th><?php _e( 'Payment Method', 'rcp' ); ?></th>
			<th><?php _e( 'Amount', 'rcp' ); ?></th>
			<th><?php _e( 'Date', 'rcp' ); ?></th>
			<th><?php _e( 'Actions', 'rcp' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php if( rcp_get_user_payments() ) : ?>
		<?php foreach( rcp_get_user_payments() as $payment ) : ?>
			<tr>
				<td><?php echo $payment->id; ?></td>
				<td><?php echo $payment->subscription; ?></td>
				<td><?php echo $payment->payment_type; ?></td>
				<td><?php echo rcp_currency_filter( $payment->amount ); ?></td>
				<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->date ) ); ?></td>
				<td><a href="<?php echo rcp_get_pdf_download_url( $payment->id ); ?>"><?php _e( 'PDF Receipt', 'rcp' ); ?></td>
			</tr>
		<?php endforeach; ?>
	<?php else : ?>
		<tr><td colspan="6"><?php _e( 'You have not made any payments.', 'rcp' ); ?></td></tr>
	<?php endif; ?>
	</tbody>
</table>
<?php do_action( 'rcp_subscription_details_bottom' );