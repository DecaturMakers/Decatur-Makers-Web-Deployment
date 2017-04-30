<?php

/**
 * Renders the Restrict > Payments page
 *
 * @since  1.0
 * @return void
*/

function rcp_payments_page() {
	global $rcp_options;
	$current_page = admin_url( '/admin.php?page=rcp-payments' ); ?>
	<div class="wrap">

		<?php
		if( isset( $_GET['view'] ) && 'new-payment' == $_GET['view'] ) :
			include( 'new-payment.php' );
		elseif( isset( $_GET['view'] ) && 'edit-payment' == $_GET['view'] ) :
			include( 'edit-payment.php' );
		else : ?>
		<h2>
			<?php _e( 'Payments', 'rcp' ); ?>
			<a href="<?php echo admin_url( '/admin.php?page=rcp-payments&view=new-payment' ); ?>" class="add-new-h2">
				<?php _e( 'Create Payment', 'rcp' ); ?>
			</a>
		</h2>

		<?php do_action('rcp_payments_page_top');

		$rcp_payments  = new RCP_Payments();
		$page          = isset( $_GET['p'] ) ? $_GET['p'] : 1;
		$per_page      = 20;
		$search        = ! empty( $_GET['s'] )       ? urldecode( $_GET['s'] )      : '';

		$user          = get_current_user_id();
		$screen        = get_current_screen();
		$screen_option = $screen->get_option( 'per_page', 'option' );
		$per_page      = get_user_meta( $user, $screen_option, true );
		if ( empty ( $per_page) || $per_page < 1 ) {
			$per_page  = $screen->get_option( 'per_page', 'default' );
		}
		$total_pages   = 1;
		$offset        = $per_page * ( $page-1 );

		$user_id       = isset( $_GET['user_id'] ) ? $_GET['user_id'] : 0;

		$payments      = $rcp_payments->get_payments( array( 'offset' => $offset, 'number' => $per_page, 'user_id' => $user_id, 's' => $search ) );
		$payment_count = $rcp_payments->count( array( 'user_id' => $user_id ) );
		$total_pages   = ceil( $payment_count / $per_page );
		?>
		<form id="rcp-member-search" method="get" action="<?php menu_page_url( 'rcp-payments' ); ?>">
			<label class="screen-reader-text" for="rcp-member-search-input"><?php _e( 'Search Payments', 'rcp' ); ?></label>
			<input type="search" id="rcp-member-search-input" name="s" value="<?php echo esc_attr( $search ); ?>"/>
			<input type="hidden" name="page" value="rcp-payments"/>
			<input type="submit" name="" id="rcp-member-search-submit" class="button" value="<?php _e( 'Search Payments', 'rcp' ); ?>"/>
		</form>
		<p class="total"><strong><?php _e( 'Total Earnings', 'rcp' ); ?>: <?php echo rcp_currency_filter( number_format_i18n( $rcp_payments->get_earnings(), 2 ) ); ?></strong></p>
		<?php if( ! empty( $user_id ) ) : ?>
		<p><a href="<?php echo admin_url( 'admin.php?page=rcp-payments' ); ?>" class="button-secondary" title="<?php _e( 'View all payments', 'rcp' ); ?>"><?php _e( 'Reset User Filter', 'rcp' ); ?></a></p>
		<?php endif; ?>
		<table class="wp-list-table widefat fixed posts rcp-payments">
			<thead>
				<tr>
					<th style="width: 40px;"><?php _e( 'ID', 'rcp' ); ?></th>
					<th style="width: 90px;"><?php _e( 'User', 'rcp' ); ?></th>
					<th style="width: 150px;"><?php _e( 'Subscription', 'rcp' ); ?></th>
					<th><?php _e( 'Date', 'rcp' ); ?></th>
					<th style="width: 90px;"><?php _e( 'Amount', 'rcp' ); ?></th>
					<th><?php _e( 'Type', 'rcp' ); ?></th>
					<th><?php _e( 'Transaction ID', 'rcp' ); ?></th>
					<th><?php _e( 'Status', 'rcp' ); ?></th>
					<?php do_action('rcp_payments_page_table_header'); ?>
					<?php if( current_user_can( 'rcp_manage_payments' ) ) : ?>
						<th><?php _e( 'Actions', 'rcp' ); ?></th>
					<?php endif; ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th style="width: 40px;"><?php _e( 'ID', 'rcp' ); ?></th>
					<th><?php _e( 'User', 'rcp' ); ?></th>
					<th><?php _e( 'Subscription', 'rcp' ); ?></th>
					<th><?php _e( 'Date', 'rcp' ); ?></th>
					<th><?php _e( 'Amount', 'rcp' ); ?></th>
					<th><?php _e( 'Type', 'rcp' ); ?></th>
					<th><?php _e( 'Transaction ID', 'rcp' ); ?></th>
					<th><?php _e( 'Status', 'rcp' ); ?></th>
					<?php do_action( 'rcp_payments_page_table_footer' ); ?>
					<?php if( current_user_can( 'rcp_manage_payments' ) ) : ?>
						<th><?php _e( 'Actions', 'rcp' ); ?></th>
					<?php endif; ?>
				</tr>
			</tfoot>
			<tbody>
				<?php
					if( $payments ) :
						$i = 0; $total_earnings = 0;
						foreach( $payments as $payment ) :
							$user = get_userdata( $payment->user_id );
							?>
							<tr class="rcp_payment <?php if( rcp_is_odd( $i ) ) echo 'alternate'; ?>">
								<td><?php echo absint( $payment->id ); ?></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( 'user_id', $payment->user_id, menu_page_url( 'rcp-payments', false ) ) ); ?>" title="<?php _e( 'View payments by this user', 'rcp' ); ?>">
										<?php echo isset( $user->display_name ) ? esc_html( $user->display_name ) : ''; ?>
									</a>
								</td>
								<td><?php echo esc_html( $payment->subscription ); ?></td>
								<td><?php echo esc_html( $payment->date ); ?></td>
								<td><?php echo rcp_currency_filter( $payment->amount ); ?></td>
								<td><?php echo esc_html( $payment->payment_type ); ?></td>
								<td><?php echo $payment->transaction_id; ?></td>
								<td><?php echo rcp_get_payment_status_label( $payment ); ?></td>
								<?php do_action( 'rcp_payments_page_table_column', $payment->id ); ?>
								<?php if( current_user_can( 'rcp_manage_payments' ) ) : ?>
									<td>
										<a href="<?php echo rcp_get_pdf_download_url( $payment->id ); ?>" class="rcp-payment-invoice"><?php _e( 'Download Invoice', 'rcp' ); ?></a>
										<span>&nbsp;|&nbsp;</span>
										<a href="<?php echo esc_url( add_query_arg( array( 'payment_id' => $payment->id, 'view' => 'edit-payment' ) ) ); ?>" class="rcp-edit-payment"><?php _e( 'Edit', 'rcp' ); ?></a>
										<span>&nbsp;|&nbsp;</span>
										<a href="<?php echo wp_nonce_url( add_query_arg( array( 'payment_id' => $payment->id, 'rcp-action' => 'delete_payment' ) ), 'rcp_delete_payment_nonce' ); ?>" class="rcp-delete-payment"><?php _e( 'Delete', 'rcp' ); ?></a>
									</td>
								<?php endif; ?>
							</tr>
						<?php
						$i++;
						$total_earnings = $total_earnings + $payment->amount;
						endforeach;
					else : ?>
					<tr><td colspan="8"><?php _e( 'No payments recorded yet', 'rcp' ); ?></td></tr>
				<?php endif;?>
			</table>
			<?php if ($total_pages > 1) : ?>
				<div class="tablenav">
					<div class="tablenav-pages alignright">
						<?php

							$base = 'admin.php?' . remove_query_arg( 'p', $_SERVER['QUERY_STRING'] ) . '%_%';

							echo paginate_links( array(
								'base' 		=> $base,
								'format' 	=> '&p=%#%',
								'prev_text' => __( '&laquo; Previous' ),
								'next_text' => __( 'Next &raquo;' ),
								'total' 	=> $total_pages,
								'current' 	=> $page,
								'end_size' 	=> 1,
								'mid_size' 	=> 5,
							));
						?>
				    </div>
				</div><!--end .tablenav-->
			<?php endif; ?>
			<?php do_action( 'rcp_payments_page_bottom' ); ?>
		<?php endif; ?>
	</div><!--end wrap-->
	<?php
}