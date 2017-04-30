<?php
/**
 * Export Members Class
 *
 * Export members to a CSV
 *
 * @package     Restrict Content Pro
 * @subpackage  Export Class
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class RCP_Members_Export extends RCP_Export {

	/**
	 * Our export type. Used for export-type specific filters / actions
	 *
	 * @access      public
	 * @var         string
	 * @since       1.5
	 */
	public $export_type = 'members';

	/**
	 * Set the CSV columns
	 *
	 * @access      public
	 * @since       1.5
	 * @return      array
	 */
	public function csv_cols() {
		$cols = array(
			'user_id'          => __( 'User ID', 'rcp' ),
			'user_login'       => __( 'User Login', 'rcp' ),
			'user_email'       => __( 'User Email', 'rcp' ),
			'first_name'       => __( 'First Name', 'rcp' ),
			'last_name'        => __( 'Last Name', 'rcp' ),
			'subscription'     => __( 'Subscription', 'rcp' ),
			'subscription_key' => __( 'Subscription Key', 'rcp' ),
			'expiration'       => __( 'Expiration', 'rcp' ),
			'discount_codes'   => __( 'Discount Codes', 'rcp' ),
			'profile_id'       => __( 'Payment Profile ID', 'rcp' )
		);
		return $cols;
	}

	/**
	 * Get the data being exported
	 *
	 * @access      public
	 * @since       1.5
	 * @return      array
	 */
	public function get_data() {
		global $wpdb;

		$data = array();

		$subscription = isset( $_POST['rcp-subscription'] ) ? absint( $_POST['rcp-subscription'] )        : null;
		$status       = isset( $_POST['rcp-status'] )       ? sanitize_text_field( $_POST['rcp-status'] ) : 'active';
		$offset       = isset( $_POST['rcp-offset'] )       ? absint( $_POST['rcp-offset'] )              : null;
		$number       = isset( $_POST['rcp-number'] )       ? absint( $_POST['rcp-number'] )              : null;

		$members      = rcp_get_members( $status, $subscription, $offset, $number );

		if( $members ) :
			foreach ( $members as $member ) {

				$member = new RCP_Member( $member->ID );

				$discounts = (array) get_user_meta( $member->ID, 'rcp_user_discounts', true );

				$data[] = array(
					'user_id'          => $member->ID,
					'user_login'       => $member->user_login,
					'user_email'       => $member->user_email,
					'first_name'       => $member->first_name,
					'last_name'        => $member->last_name,
					'subscription'     => $member->get_subscription_id(),
					'subscription_key' => $member->get_subscription_key(),
					'expiration'       => $member->get_expiration_date(),
					'discount_codes'   => ! empty( $discounts ) && is_array( $discounts ) ? implode( ' ', $discounts ) : '',
					'profile_id'       => $member->get_payment_profile_id()
				);

			}
		endif;

		$data = apply_filters( 'rcp_export_get_data', $data );
		$data = apply_filters( 'rcp_export_get_data_' . $this->export_type, $data );

		return $data;
	}
}