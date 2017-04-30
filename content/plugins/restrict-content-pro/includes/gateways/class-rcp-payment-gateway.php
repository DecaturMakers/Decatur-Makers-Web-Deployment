<?php
/**
 * Payment Gateway Base Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway {
	
	public $supports = array();
	public $email;
	public $user_id;
	public $user_name;
	public $currency;
	public $amount;
	public $discount;
	public $length;
	public $length_unit;
	public $signup_fee;
	public $subscription_key;
	public $subscription_id;
	public $subscription_name;
	public $auto_renew;
	public $return_url;
	public $test_mode;

	public function __construct( $subscription_data = array() ) {

		$this->init();

		if( ! empty( $subscription_data ) ) {

			$this->email               = $subscription_data['user_email'];
			$this->user_id             = $subscription_data['user_id'];
			$this->user_name           = $subscription_data['user_name'];
			$this->currency            = $subscription_data['currency'];
			$this->amount              = $subscription_data['price'];
			$this->discount            = $subscription_data['discount'];
			$this->discount_code       = $subscription_data['discount_code'];
			$this->length              = $subscription_data['length'];
			$this->length_unit         = $subscription_data['length_unit'];
			$this->signup_fee          = $this->supports( 'fees' ) ? $subscription_data['fee'] : 0;
			$this->subscription_key    = $subscription_data['key'];
			$this->subscription_id     = $subscription_data['subscription_id'];
			$this->subscription_name   = $subscription_data['subscription_name'];
			$this->auto_renew          = $this->supports( 'recurring' ) ? $subscription_data['auto_renew'] : false;;
			$this->return_url          = $subscription_data['return_url'];

		}

	}

	public function init() {}

	public function process_signup() {}

	public function process_webhooks() {}

	public function scripts() {}

	public function fields() {}

	public function validate_fields() {}

	public function supports( $item = '' ) {
		return in_array( $item, $this->supports );
	}

	public function generate_transaction_id() {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		return strtolower( md5( $this->subscription_key . date( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'rcp', true ) ) );
	}

	public function renew_member( $recurring = false, $status = 'active' ) {
		$member = new RCP_Member( $this->user_id );
		$member->renew( $recurring, $status );
	}

	public function add_error( $code = '', $message = '' ) {
		rcp_errors()->add( $code, $message, 'register' );
	}

}