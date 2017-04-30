<?php

class RCP_WooCommerce {
	
	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.2
	*/
	public function __construct() {

		if( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_filter( 'woocommerce_product_data_tabs', array( $this, 'data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'data_display' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );

		add_filter( 'woocommerce_is_purchasable', array( $this, 'is_purchasable' ), 999999, 2 );
		add_filter( 'woocommerce_product_is_visible', array( $this, 'is_visible' ), 999999, 2 );
	}

	/**
	 * Register the product settings tab
	 *
	 * @access  public
	 * @since   2.2
	*/
	public function data_tab( $tabs ) {

		$tabs['access'] = array(
			'label'  => __( 'Access Control', 'rcp' ),
			'target' => 'rcp_access_control',
			'class'  => array(),
		);
	
		return $tabs;

	}

	/**
	 * Display product settings
	 *
	 * @access  public
	 * @since   2.2
	*/
	public function data_display() {
?>
		<div id="rcp_access_control" class="panel woocommerce_options_panel">
			
			<div class="options_group">
				<p><?php _e( 'Restrict purchasing of this product to:', 'rcp' ); ?></p>
				<?php

				woocommerce_wp_checkbox( array( 
					'id'      => '_rcp_woo_active_to_purchase',
					'label'   => __( 'Active subscribers only?', 'rcp' ),
					'cbvalue' => 1
				) );

				$levels = (array) get_post_meta( get_the_ID(), '_rcp_woo_subscription_levels_to_purchase', true );
				foreach ( rcp_get_subscription_levels( 'all' ) as $level ) {
					woocommerce_wp_checkbox( array( 
						'name'    => '_rcp_woo_subscription_levels_to_purchase[]',
						'id'      => '_rcp_woo_subscription_level_' . $level->id,
						'label'   => $level->name,
						'value'   => in_array( $level->id, $levels ) ? $level->id : 0,
						'cbvalue' => $level->id
					) );
				}

				woocommerce_wp_select( array( 
					'id'      => '_rcp_woo_access_level_to_purchase',
					'label'   => __( 'Access level required?', 'rcp' ),
					'options' => rcp_get_access_levels()
				) );
				?>
			</div>

			<div class="options_group">
				<p><?php _e( 'Restrict viewing of this product to:', 'rcp' ); ?></p>
				<?php

				woocommerce_wp_checkbox( array( 
					'id'      => '_rcp_woo_active_to_view',
					'label'   => __( 'Active subscribers only?', 'rcp' ),
					'cbvalue' => 1
				) );

				$levels = (array) get_post_meta( get_the_ID(), '_rcp_woo_subscription_levels_to_view', true );
				foreach ( rcp_get_subscription_levels( 'all' ) as $level ) {
					woocommerce_wp_checkbox( array( 
						'name'    => '_rcp_woo_subscription_levels_to_view[]',
						'id'      => '_rcp_woo_subscription_level_to_view_' . $level->id,
						'label'   => $level->name,
						'value'   => in_array( $level->id, $levels ) ? $level->id : 0,
						'cbvalue' => $level->id
					) );
				}

				woocommerce_wp_select( array( 
					'id'      => '_rcp_woo_access_level_to_view',
					'label'   => __( 'Access level required?', 'rcp' ),
					'options' => rcp_get_access_levels()
				) );
				?>
			</div>

		</div>
<?php
	}

	/**
	 * Saves product access settings
	 *
	 * @access  public
	 * @since   2.2
	*/
	public function save_meta( $post_id = 0 ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		$post = get_post( $post_id );

		if( ! $post ) {
			return $post_id;
		}

		// Check post type is product
		if ( 'product' != $post->post_type ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if( isset( $_POST['_rcp_woo_active_to_purchase'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_active_to_purchase', 1 );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_active_to_purchase' );

		}

		if( isset( $_POST['_rcp_woo_access_level_to_purchase'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_access_level_to_purchase', sanitize_text_field( $_POST['_rcp_woo_access_level_to_purchase'] ) );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_access_level_to_purchase' );

		}

		if( isset( $_POST['_rcp_woo_subscription_levels_to_purchase'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_subscription_levels_to_purchase', array_map( 'absint', $_POST['_rcp_woo_subscription_levels_to_purchase'] ) );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_subscription_levels_to_purchase' );

		}

		if( isset( $_POST['_rcp_woo_active_to_view'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_active_to_view', 1 );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_active_to_view' );

		}

		if( isset( $_POST['_rcp_woo_access_level_to_view'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_access_level_to_view', sanitize_text_field( $_POST['_rcp_woo_access_level_to_view'] ) );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_access_level_to_view' );

		}

		if( isset( $_POST['_rcp_woo_subscription_levels_to_view'] ) ) {

			update_post_meta( $post_id, '_rcp_woo_subscription_levels_to_view', array_map( 'absint', $_POST['_rcp_woo_subscription_levels_to_view'] ) );

		} else {

			delete_post_meta( $post_id, '_rcp_woo_subscription_levels_to_view' );

		}

	}

	/**
	 * Restrict the abbility to purchase a product
	 *
	 * @access  public
	 * @since   2.2
	*/
	public function is_purchasable( $ret, $product ) {

		if( $ret ) {

			$has_access   = true;
			$active_only  = get_post_meta( $product->id, '_rcp_woo_active_to_purchase', true );
			$levels       = (array) get_post_meta( $product->id, '_rcp_woo_subscription_levels_to_purchase', true );
			$access_level = get_post_meta( $product->id, '_rcp_woo_access_level_to_purchase', true );

			if( $active_only ) {

				if( ! rcp_is_active() ) {
					$has_access = false;
				}

			}

			if( $levels ) {

				if( ! in_array( rcp_get_subscription_id(), $levels ) ) {
					$has_access = false;
				}

			}

			if( $access_level ) {

				if( ! rcp_user_has_access( get_current_user_id(), $access_level ) ) {
					$has_access = false;
				}

			}

			$ret = $has_access;

		}

		return $ret;
	}

	/**
	 * Restrict the visibility of a product
	 *
	 * @access  public
	 * @since   2.2
	*/
	public function is_visible( $ret, $product_id ) {

		if( $ret ) {

			$has_access   = true;
			$active_only  = get_post_meta( $product_id, '_rcp_woo_active_to_view', true );
			$levels       = (array) get_post_meta( $product_id, '_rcp_woo_subscription_levels_to_view', true );
			$access_level = get_post_meta( $product_id, '_rcp_woo_access_level_to_view', true );

			if( $active_only ) {

				if( ! rcp_is_active() ) {
					$has_access = false;
				}

			}

			if( $levels ) {

				if( ! in_array( rcp_get_subscription_id(), $levels ) ) {
					$has_access = false;
				}

			}

			if( $access_level ) {

				if( ! rcp_user_has_access( get_current_user_id(), $access_level ) ) {
					$has_access = false;
				}

			}

			$ret = $has_access;

		}

		return $ret;
	}

}
new RCP_WooCommerce;