<h2>
	<?php _e( 'Create Payment', 'rcp' ); ?> - 
	<a href="<?php echo admin_url( '/admin.php?page=rcp-payments' ); ?>" class="button-secondary">
		<?php _e( 'Cancel', 'rcp' ); ?>
	</a>
</h2>
<form id="rcp-edit-member" action="" method="post">
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-user-id"><?php _e( 'User', 'rcp' ); ?></label>
				</th>
				<td>
					<input type="text" name="user" autocomplete="off" id="rcp-user" class="regular-text rcp-user-search"/>
					<img class="rcp-ajax waiting" src="<?php echo admin_url('images/wpspin_light.gif'); ?>" style="display: none;"/>
					<div id="rcp_user_search_results"></div>
					<p class="description"><?php _e('Begin typing the user name to add a payment record for.', 'rcp'); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-amount"><?php _e( 'Amount', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="amount" id="rcp-amount" type="text" value=""/>
					<p class="description"><?php _e( 'The amount of this payment', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-date"><?php _e( 'Payment Date', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="date" id="rcp-date" type="text" class="rcp-datepicker" value=""/>
					<p class="description"><?php _e( 'Enter the date for this payment in the format of yyyy-mm-dd', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-date"><?php _e( 'Transaction ID', 'rcp' ); ?></label>
				</th>
				<td>
					<input name="transaction-id" id="rcp-transaction-id" type="text" class="regular-text" value=""/>
					<p class="description"><?php _e( 'Enter the transaction ID for this payment, if any', 'rcp' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" valign="top">
					<label for="rcp-status"><?php _e( 'Status', 'rcp' ); ?></label>
				</th>
				<td>
					<select name="status" id="rcp-status">
						<option value="complete"><?php _e( 'Complete', 'rcp' ); ?></option>
						<option value="refunded"><?php _e( 'Refunded', 'rcp' ); ?></option>
					</select>
					<p class="description"><?php _e( 'The status of this payment.', 'rcp' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input type="hidden" name="rcp-action" value="add-payment"/>
		<input type="submit" value="<?php _e( 'Create Payment', 'rcp' ); ?>" class="button-primary"/>
	</p>
</form>