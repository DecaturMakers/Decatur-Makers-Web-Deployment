<?php
/*
Plugin Name: Restrict Content Pro - CSV User Import
Plugin URL: http://pippinsplugins.com/rcp-csv-user-import
Description: Allows you to import a CSV of users into Restrict Content Pro
Version: 1.1.3
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk, chriscoyier
*/

if(!defined('RCP_CSVUI_PLUGIN_DIR')) {
	define('RCP_CSVUI_PLUGIN_DIR', dirname(__FILE__));
}

ini_set('max_execution_time', 90);

function rcp_csvui_menu_page() {
	global $rcp_csvui_import_page;
	$rcp_csvui_import_page = add_submenu_page('rcp-members', __('CSV Import', 'rcp_csvui'), __('CSV Import', 'rcp_csvui'), 'manage_options', 'rcp-csv-import', 'rcp_csvui_purchase_import');
}
add_action('admin_menu', 'rcp_csvui_menu_page', 100);

function rcp_csvui_purchase_import() {
	?>
	<div class="wrap">
		<h2><?php _e('CSV User Import', 'rcp_csvui'); ?></h2>
		<?php settings_errors( 'rcp-csv-ui' ); ?>
		<P><?php _e('Use this tool to import user memberships into Restrict Content Pro', 'rcp_csvui'); ?></p>
		<p><?php _e('<strong>Note</strong>: your CSV should contain the following fields: <em>user_email, first_name, last_name, user_login</em>. If you wish to update existing users, you can include a <em>ID</em> field as well.', 'rcp_csvui' ); ?></p>
		<script type="text/javascript">jQuery(document).ready(function($) { var dateFormat = 'yy-mm-dd'; $('.rcp_datepicker').datepicker({dateFormat: dateFormat}); });</script>
		<form id="rcp_csvui_import" enctype="multipart/form-data" method="post">
			<table class="form-table">
				<tr>
					<th><?php _e('CSV File', 'rcp_csvui'); ?></th>
					<td>
						<input type="file" name="rcp_csvui_file"/>
						<div class="description"><?php _e('Select the CSV file to import. Must follow guidelines above.', 'rcp_csvui'); ?></div>
					</td>
				</tr>
				<tr>
					<th><?php _e('Subscription Level', 'rcp_csv_ui'); ?></th>
					<td>
						<select name="rcp_level" id="rcp_level">
						<?php
						$subscription_levels = rcp_get_subscription_levels();
						foreach( $subscription_levels as $level ) {
							echo '<option value="' . esc_attr( absint( $level->id ) ) . '">' . esc_html( $level->name ) . '</option>';
						}
						?>
						</select>
						<div class="description"><?php _e('Select the subscription level to add users to.', 'rcp_csvui'); ?></div>
					</td>
				</tr>
				<tr>
					<th><?php _e('Status', 'rcp_csv_ui'); ?></th>
					<td>
						<select name="rcp_status" id="rcp_status">
							<option value="active"><?php _e('Active', 'rcp_csvui'); ?></option>
							<option value="pending"><?php _e('Pending', 'rcp_csvui'); ?></option>
							<option value="cancelled"><?php _e('Cancelled', 'rcp_csvui'); ?></option>
							<option value="expired"><?php _e('Expired', 'rcp_csvui'); ?></option>
							<option value="free"><?php _e('Free', 'rcp_csvui'); ?></option>
						</select>
						<div class="description"><?php _e('Select the subscription status to import users with.', 'rcp_csvui'); ?></div>
					</td>
				</tr>
				<tr>
					<th><?php _e('Expiration', 'rcp_csv_ui'); ?></th>
					<td>
						<input type="text" name="rcp_expiration" id="rcp_expiration" value="" class="rcp_datepicker"/>
						<div class="description"><?php _e('Select the expiration date for all users. Leave this blank and the expiration date will be automatically calculated based on the selected subscription.', 'rcp_csvui'); ?></div>
					</td>
				</tr>

			</table>
			<input type="hidden" name="rcp_action" value="process_csv_import"/>
			<?php wp_nonce_field('rcp_csvui_nonce', 'rcp_csvui_nonce'); ?>
			<?php submit_button( __('Upload and Import', 'rcp_csvui') ); ?>
		</form>
	</div>
	<?php
}

function rcp_csvui_process_csv() {

	if( isset( $_POST['rcp_action'] ) && $_POST['rcp_action'] == 'process_csv_import' ) {

		if( ! wp_verify_nonce( $_POST['rcp_csvui_nonce'], 'rcp_csvui_nonce' ) ) {
			return;
		}

		if( ! class_exists( 'parseCSV' ) ) {

			require_once dirname( __FILE__ ) . '/parsecsv.lib.php';
		}

		$import_file = ! empty( $_FILES['rcp_csvui_file'] ) ? $_FILES['rcp_csvui_file']['tmp_name'] : false;

		if( ! $import_file ) {
			wp_die( __('Please upload a CSV file.', 'rcp_csvui' ), __('Error') );
		}

		$csv = new parseCSV( $import_file );
		
		$subscription_id = isset( $_POST['rcp_level'] ) ? absint( $_POST['rcp_level'] ) : false;
		
		if( ! $subscription_id ) {
			wp_die( __('Please select a subscription level.', 'rcp_csvui' ), __('Error') );
		}

		$subscription_details = rcp_get_subscription_details( $subscription_id );

		if( ! $subscription_details ) {
			wp_die( sprintf( __('That subscription level does not exist: #%d.', 'rcp_csvui' ), $subscription_id ), __('Error') );
		}

		$status     = isset( $_POST['rcp_status'] ) ? sanitize_text_field( $_POST['rcp_status'] ) : 'free';
		$expiration = isset( $_POST['rcp_expiration'] ) ? sanitize_text_field( $_POST['rcp_expiration'] ) : false;

		if( ! $expiration || strlen( trim( $expiration ) ) <= 0 ) {
			// calculate expiration here
			$expiration = rcp_calculate_subscription_expiration( $subscription_id );
		}

		foreach ( $csv->data as $user ) {

			if ( ! empty( $user['id'] ) ) {

				$user_data = get_userdata( $user['id'] );
			
			} elseif ( ! empty( $user['ID'] ) ) {

				$user_data = get_userdata( $user['ID'] );
			
			} else {

				$user_data = get_user_by( 'email', $user['user_email'] );
	
			}

			if( ! $user_data ) {

				$email      = $user['user_email'];
				$password   = wp_generate_password();
				$user_login = ! empty( $user['user_login'] ) ? $user['user_login'] : $user['user_email'];

				$user_data  = array(
					'user_login' => $user_login,
					'user_email' => $email,
					'first_name' => $user['first_name'],
					'last_name'  => $user['last_name'],
					'user_pass'  => $password,
					'role'       => ! empty( $subscription_details->role ) ? $subscription_details->role : 'subscriber'
				);

				$user_id = wp_insert_user( $user_data );

			} else {
				$user_id = $user_data->ID;
				$email   = $user_data->user_email;
			}

            update_user_meta( $user_id, 'rcp_subscription_level', $subscription_id );
            rcp_set_expiration_date( $user_id, $expiration );
            rcp_set_status( $user_id, $status );

            do_action( 'rcp_user_import_user_added', $user_id, $user_data, $subscription_id, $status, $expiration );
		}
		wp_redirect( admin_url( '/admin.php?page=rcp-csv-import&rcp-message=users-imported' ) ); exit;
	}
}
add_action('admin_init', 'rcp_csvui_process_csv');

function rcp_csvui_notices() {
	if( isset( $_GET['rcp-message'] ) && $_GET['rcp-message'] == 'users-imported' ) {
		add_settings_error( 'rcp-csv-ui', 'imported', __('All users have been imported.', 'rcp_csvui'), 'updated' );
	}
}
add_action('admin_notices', 'rcp_csvui_notices' );

function rcp_csvui_scripts( $hook ) {
	global $rcp_csvui_import_page;
	if( $hook != $rcp_csvui_import_page )
		return;
	wp_enqueue_style('datepicker', RCP_PLUGIN_DIR . 'includes/css/datepicker.css');
	wp_enqueue_script('jquery-ui-datepicker');
}
add_action( 'admin_enqueue_scripts', 'rcp_csvui_scripts' );

function rcp_csvui_csv_to_array( $filename = '', $delimiter = ',') {
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;

	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE) {
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
			if(!$header)
				$header = $row;
			else
				$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}