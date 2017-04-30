<?php
/**
 * Export Payments Class
 *
 * Export payment hsitory to a CSV
 *
 * @package     Restrict Content Pro
 * @subpackage  Export Class
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class RCP_Payments_Export extends RCP_Export {

	/**
	 * Our export type. Used for export-type specific filters / actions
	 *
	 * @access      public
	 * @var         string
	 * @since       1.5
	 */
	public $export_type = 'payments';

	/**
	 * Set the CSV columns
	 *
	 * @access      public
	 * @since       1.5
	 * @return      array
	 */
	public function csv_cols() {
		$cols = array(
			'id'               => __( 'ID',   'rcp' ),
			'subscription'     => __( 'Subscription', 'rcp' ),
			'amount'           => __( 'Amount', 'rcp' ),
			'user_id'          => __( 'User ID', 'rcp' ),
			'user_login'       => __( 'User Login', 'rcp' ),
			'payment_type'     => __( 'Payment Type', 'rcp' ),
			'subscription_key' => __( 'Subscription Key', 'rcp' ),
			'date'             => __( 'Date', 'rcp' )
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
		$args = array();

		if( ! empty( $_POST['rcp-year'] ) ) {

			$args['date'] = array();
			$args['date']['year'] = absint( $_POST['rcp-year'] );

			if( ! empty( $_POST['rcp-month'] ) ) {

				$args['date']['month'] = absint( $_POST['rcp-month'] );

			}

		}

		$args['number'] = 999999;

		$rcp_db   = new RCP_Payments;
		$payments = $rcp_db->get_payments( $args );

		foreach ( $payments as $payment ) {

			$user   = get_userdata( $payment->user_id );

			$data[] = array(
				'id'               => $payment->id,
				'subscription'     => $payment->subscription,
				'amount'           => $payment->amount,
				'user_id'          => $payment->user_id,
				'user_login'       => $user->user_login,
				'payment_type'     => $payment->payment_type,
				'subscription_key' => $payment->subscription_key,
				'date'             => $payment->date
			);

		}

		$data = apply_filters( 'rcp_export_get_data', $data );
		$data = apply_filters( 'rcp_export_get_data_' . $this->export_type, $data );

		return $data;
	}
}