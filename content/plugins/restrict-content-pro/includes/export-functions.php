<?php

function rcp_export_members() {
	if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'export-members' ) {

		include RCP_PLUGIN_DIR . 'includes/class-rcp-export.php';
		include RCP_PLUGIN_DIR . 'includes/class-rcp-export-members.php';

		$export = new RCP_Members_Export;
		$export->export();
	}
}
add_action( 'admin_init', 'rcp_export_members' );

function rcp_export_payments() {
	if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'export-payments' ) {

		include RCP_PLUGIN_DIR . 'includes/class-rcp-export.php';
		include RCP_PLUGIN_DIR . 'includes/class-rcp-export-payments.php';

		$export = new RCP_Payments_Export;
		$export->export();
	}
}
add_action( 'admin_init', 'rcp_export_payments' );