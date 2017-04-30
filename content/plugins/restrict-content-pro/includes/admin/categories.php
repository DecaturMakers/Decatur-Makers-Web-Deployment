<?php
/**
 * Adds custom fields to category edit screens
 *
 * These options are for restricting content within categories
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Categories
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Add restriction options to the edit category page
 *
 * @access      public
 * @since       2.0
 * @return      void
 */
function rcp_category_edit_meta_fields( $term ) { 
	// retrieve the existing value(s) for this meta field. This returns an array
	$term_meta = get_option( "rcp_category_meta_$term->term_id" );
	$access_level = isset( $term_meta['access_level'] ) ? absint( $term_meta['access_level'] ) : 0;
	$subscription_levels = isset( $term_meta['subscriptions'] ) ? array_map( 'absint', $term_meta['subscriptions'] ) : array();
	?>
	<tr>
		<th scope="row"><?php _e( 'Paid Only?', 'rcp' ); ?></th>
		<td>
			<label for="rcp_category_meta[paid_only]">
				<input type="checkbox" name="rcp_category_meta[paid_only]" id="rcp_category_meta[paid_only]" value="1"<?php checked( true, isset( $term_meta['paid_only'] ) ); ?>>
				<span class="description"><?php _e( 'Restrict items in thie category to paid subscribers only?', 'rcp' ); ?></span>
			</label>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php _e( 'Access Level', 'rcp' ); ?></th>
		<td>
			<label for="rcp_category_meta[access_level]">
				<select name="rcp_category_meta[access_level]" id="rcp_category_meta[access_level]">
					<?php foreach( rcp_get_access_levels() as $level ) : ?>
						<option value="<?php echo esc_attr( $level ); ?>"<?php selected( $level, $access_level ); ?>><?php echo $level; ?></option>
					<?php endforeach; ?>
				</select>
				<span class="description"><?php _e( 'Access level required to view content in this category.', 'rcp' ); ?></span>
			</label>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php _e( 'Subscription Levels', 'rcp' ); ?></th>
		<td>
			<?php foreach( rcp_get_subscription_levels() as $level ) : ?>
				<label for="rcp_category_meta[subscriptions][<?php echo $level->id; ?>]">
					<input type="checkbox" name="rcp_category_meta[subscriptions][<?php echo $level->id; ?>]" id="rcp_category_meta[subscriptions][<?php echo $level->id; ?>]" value="1"<?php checked( true, in_array( $level->id, $subscription_levels ) ); ?>>
					<?php echo $level->name; ?>
				</label><br/>
			<?php endforeach; ?>
			<span class="description"><?php _e( 'Subscription levels allowed to view content in this category. Leave unchecked for all.', 'rcp' ); ?></span>
		</td>
	</tr>
<?php
}
add_action( 'category_edit_form_fields', 'rcp_category_edit_meta_fields', 10, 2 );

/**
 * Save our custom category meta
 *
 * @access      public
 * @since       2.0
 * @return      void
 */
function rcp_save_category_meta( $term_id ) {

	$fields = ! empty( $_POST['rcp_category_meta'] ) ? $_POST['rcp_category_meta'] : array();

	if( ! empty( $_POST['rcp_category_meta']['subscriptions'] ) ) {
		$fields['subscriptions'] = array_map( 'absint', array_keys( $_POST['rcp_category_meta']['subscriptions'] ) );
	}

	update_option( "rcp_category_meta_$term_id", $fields );
}  
add_action( 'edited_category', 'rcp_save_category_meta', 10, 2 );  