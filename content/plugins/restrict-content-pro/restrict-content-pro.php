<?php
/*
Plugin Name: Restrict Content Pro
Plugin URL: http://pippinsplugins.com/restrict-content-pro-premium-content-plugin
Description: Setup a complete subscription system for your WordPress site and deliver premium content to your subscribers. Unlimited subscription packages, membership management, discount codes, registration / login forms, and more.
Version: 2.2
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk
*/

if ( !defined( 'RCP_PLUGIN_DIR' ) ) {
	define( 'RCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( !defined( 'RCP_PLUGIN_URL' ) ) {
	define( 'RCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( !defined( 'RCP_PLUGIN_FILE' ) ) {
	define( 'RCP_PLUGIN_FILE', __FILE__ );
}
if ( !defined( 'RCP_PLUGIN_VERSION' ) ) {
	define( 'RCP_PLUGIN_VERSION', '2.2' );
}


/*******************************************
* setup DB names
*******************************************/

if ( ! function_exists( 'is_plugin_active_for_network' ) )
	require_once ABSPATH . '/wp-admin/includes/plugin.php';

function rcp_get_levels_db_name() {
	global $wpdb;

	$prefix = is_plugin_active_for_network( 'restrict-content-pro/restrict-content-pro.php' ) ? '' : $wpdb->prefix;

	return apply_filters( 'rcp_levels_db_name', $prefix . 'restrict_content_pro' );
}

function rcp_get_discounts_db_name() {
	global $wpdb;

	$prefix = is_plugin_active_for_network( 'restrict-content-pro/restrict-content-pro.php' ) ? '' : $wpdb->prefix;

	return apply_filters( 'rcp_discounts_db_name', $prefix . 'rcp_discounts' );
}

function rcp_get_payments_db_name() {
	global $wpdb;

	$prefix = is_plugin_active_for_network( 'restrict-content-pro/restrict-content-pro.php' ) ? '' : $wpdb->prefix;

	return apply_filters( 'rcp_payments_db_name', $prefix . 'rcp_payments' );
}


/*******************************************
* global variables
*******************************************/
global $wpdb;

// the plugin base directory
global $rcp_base_dir; // not used any more, but just in case someone else is
$rcp_base_dir = dirname( __FILE__ );

// load the plugin options
$rcp_options = get_option( 'rcp_settings' );

global $rcp_db_name;
$rcp_db_name = rcp_get_levels_db_name();

global $rcp_db_version;
$rcp_db_version = '1.5';

global $rcp_discounts_db_name;
$rcp_discounts_db_name = rcp_get_discounts_db_name();

global $rcp_discounts_db_version;
$rcp_discounts_db_version = '1.2';

global $rcp_payments_db_name;
$rcp_payments_db_name = rcp_get_payments_db_name();

global $rcp_payments_db_version;
$rcp_payments_db_version = '1.4';

/* settings page globals */
global $rcp_members_page;
global $rcp_subscriptions_page;
global $rcp_discounts_page;
global $rcp_payments_page;
global $rcp_settings_page;
global $rcp_reports_page;
global $rcp_export_page;
global $rcp_help_page;

/*******************************************
* plugin text domain for translations
*******************************************/

function rcp_load_textdomain() {

	// Set filter for plugin's languages directory
	$rcp_lang_dir = dirname( plugin_basename( RCP_PLUGIN_FILE ) ) . '/languages/';
	$rcp_lang_dir = apply_filters( 'rcp_languages_directory', $rcp_lang_dir );


	// Traditional WordPress plugin locale filter
	$locale        = apply_filters( 'plugin_locale',  get_locale(), 'rcp' );
	$mofile        = sprintf( '%1$s-%2$s.mo', 'rcp', $locale );

	// Setup paths to current locale file
	$mofile_local  = $rcp_lang_dir . $mofile;
	$mofile_global = WP_LANG_DIR . '/rcp/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/rcp folder
		load_textdomain( 'rcp', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) {
		// Look in local /wp-content/plugins/easy-digital-downloads/languages/ folder
		load_textdomain( 'rcp', $mofile_local );
	} else {
		// Load the default language files
		load_plugin_textdomain( 'rcp', false, $rcp_lang_dir );
	}

}
add_action( 'init', 'rcp_load_textdomain' );

/*******************************************
* requirement checks
*******************************************/

if( version_compare( PHP_VERSION, '5.3', '<' ) ) {

	add_action( 'admin_notices', 'rcp_below_php_version_notice' );
	function rcp_below_php_version_notice() {
		echo '<div class="error"><p>' . __( 'Your version of PHP is below the minimum version of PHP required by Restrict Content Pro. Please contact your host and request that your version be upgraded to 5.3 or later.', 'rcp' ) . '</p></div>';
	}

} else {


	/*******************************************
	* file includes
	*******************************************/

	// global includes
	require( RCP_PLUGIN_DIR . 'includes/install.php' );
	include( RCP_PLUGIN_DIR . 'includes/class-rcp-capabilities.php' );
	include( RCP_PLUGIN_DIR . 'includes/class-rcp-integrations.php' );
	include( RCP_PLUGIN_DIR . 'includes/class-rcp-levels.php' );
	include( RCP_PLUGIN_DIR . 'includes/class-rcp-member.php' );
	include( RCP_PLUGIN_DIR . 'includes/class-rcp-payments.php' );
	include( RCP_PLUGIN_DIR . 'includes/class-rcp-discounts.php' );
	include( RCP_PLUGIN_DIR . 'includes/scripts.php' );
	include( RCP_PLUGIN_DIR . 'includes/ajax-actions.php' );
	include( RCP_PLUGIN_DIR . 'includes/cron-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/deprecated/functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/discount-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/email-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway.php' );
	include( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-manual.php' );
	include( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-paypal.php' );
	include( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-paypal-pro.php' );
	include( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-paypal-express.php' );
	include( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateway-stripe.php' );
	include( RCP_PLUGIN_DIR . 'includes/gateways/class-rcp-payment-gateways.php' );
	include( RCP_PLUGIN_DIR . 'includes/gateways/gateway-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/invoice-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/login-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/member-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/misc-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/registration-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/subscription-functions.php' );
	include( RCP_PLUGIN_DIR . 'includes/error-tracking.php' );
	include( RCP_PLUGIN_DIR . 'includes/shortcodes.php' );
	include( RCP_PLUGIN_DIR . 'includes/template-functions.php' );

	if( !class_exists( 'WP_Logging' ) ) {
		include( RCP_PLUGIN_DIR . 'includes/libraries/class-wp-logging.php' );
	}

	// admin only includes
	if( is_admin() ) {

		include( RCP_PLUGIN_DIR . 'includes/upgrades.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/admin-pages.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/admin-notices.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/admin-ajax-actions.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/screen-options.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/members/members-page.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/settings/settings.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/subscriptions/subscription-levels.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/discounts/discount-codes.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/payments/payments-page.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/reports/reports-page.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/export.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/logs.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/help/help-menus.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/metabox.php' );
		include( RCP_PLUGIN_DIR . 'includes/admin/categories.php' );
		include( RCP_PLUGIN_DIR . 'includes/user-page-columns.php' );
		include( RCP_PLUGIN_DIR . 'includes/process-data.php' );
		include( RCP_PLUGIN_DIR . 'includes/export-functions.php' );
		include( RCP_PLUGIN_DIR . 'RCP_Plugin_Updater.php' );

		// retrieve our license key from the DB
		$license_key = ! empty( $rcp_options['license_key'] ) ? trim( $rcp_options['license_key'] ) : false;

		if( $license_key ) {
			// setup the updater
			$rcp_updater = new RCP_Plugin_Updater( 'https://pippinsplugins.com', RCP_PLUGIN_FILE, array(
					'version' 	=> RCP_PLUGIN_VERSION, // current version number
					'license' 	=> $license_key, // license key (used get_option above to retrieve from DB)
					'item_id'   => 7460, // Download ID
					'author' 	=> 'Pippin Williamson' // author of this plugin
				)
			);
		}

	} else {

		include( RCP_PLUGIN_DIR . 'includes/member-forms.php' );
		include( RCP_PLUGIN_DIR . 'includes/content-filters.php' );
		include( RCP_PLUGIN_DIR . 'includes/feed-functions.php' );
		if( isset( $rcp_options['enable_recaptcha'] ) ) {
			require_once( RCP_PLUGIN_DIR . 'includes/captcha-functions.php' );
		}
		include( RCP_PLUGIN_DIR . 'includes/query-filters.php' );
		include( RCP_PLUGIN_DIR . 'includes/redirects.php' );
	}

}
