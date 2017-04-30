<?php

/*******************************************
* Restrict Content Meta Box
*******************************************/

function rcp_get_metabox_fields() {

    //custom meta boxes
    $rcp_prefix = 'rcp_';

    $rcp_meta_box  = array(
        'id'       => 'rcp_meta_box',
        'title'    => __( 'Restrict this content', 'rcp' ),
        'context'  => 'normal',
        'priority' => apply_filters( 'rcp_metabox_priority', 'high' ),
        'fields'   => array(
            array(
            	'name' => __( 'Paid Only?', 'rcp' ),
            	'id'   => '_is_paid',
            	'type' => 'checkbox',
            	'desc' => __( 'Restrict this entry to active paid users only.', 'rcp' )
         	),
    		array(
            	'name' => __( 'Show Excerpt?', 'rcp' ),
            	'id'   => $rcp_prefix . 'show_excerpt',
            	'type' => 'checkbox',
            	'desc' => __( 'Allow non active users to view the excerpt? If left unchecked, the message defined in settings will be used.', 'rcp' )
         	),
    		array(
            	'name' => __( 'Hide from Feed?', 'rcp' ),
            	'id'   => $rcp_prefix . 'hide_from_feed',
            	'type' => 'checkbox',
            	'desc' => __( 'Hide the excerpt of this post / page from the Feed?', 'rcp' )
         	),
    		array(
                'name' => __( 'Access Level', 'rcp' ),
                'id'   => $rcp_prefix . 'access_level',
                'type' => 'select',
                'desc' => __( 'Choose the access level required see this content. The access level is determined by the subscription the member is subscribed to.', 'rcp' ),
                'options' => rcp_get_access_levels(),
                'std'  => 'All'
            ),
    		array(
                'name' => __( 'Subscription Level', 'rcp' ),
                'id'   => $rcp_prefix . 'subscription_level',
                'type' => 'levels',
                'desc' => __( 'Choose the subscription levels allowed to view this content.', 'rcp' ),
                'std'  => 'All'
            ),
    		array(
    			'name' => __( 'User Level', 'rcp' ),
    			'id'   => $rcp_prefix . 'user_level',
    			'type' => 'select',
    			'desc' => __( 'Choose the user level that can see this post / page\'s content. Users of this level and higher will be the only ones able to view the content.', 'rcp' ),
    			'options' => array('All', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber'),
    			'std'  => 'All'
    		)
        )
    );

    return apply_filters( 'rcp_metabox_fields', $rcp_meta_box );
}

// Add meta box


function rcp_add_meta_boxes() {
    $rcp_meta_box = rcp_get_metabox_fields();
	$post_types   = get_post_types( array( 'public' => true, 'show_ui' => true ), 'objects' );
	$exclude      = apply_filters( 'rcp_metabox_excluded_post_types', array( 'forum', 'topic', 'reply', 'product' ) );

	foreach ( $post_types as $page )	{
		if( !in_array( $page->name, $exclude ) )
			add_meta_box( $rcp_meta_box['id'], $rcp_meta_box['title'], 'rcp_render_meta_box', $page->name, $rcp_meta_box['context'], $rcp_meta_box['priority'] );
	}
}
add_action( 'admin_menu', 'rcp_add_meta_boxes' );


// Callback function to show fields in meta box
function rcp_render_meta_box() {
    global $post;

    $rcp_meta_box = rcp_get_metabox_fields();

    // Use nonce for verification
    echo '<input type="hidden" name="rcp_meta_box" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

    echo '<table class="form-table">';

	echo '<tr><td colspan="3">' . sprintf(
            __( 'Use these options to restrict this entire entry, or the [restrict] ... [/restrict] short code to restrict partial content. %sView documentation%s.', 'rcp' ),
            '<a href="' . admin_url( 'admin.php?page=rcp-help#restricting-content' ) . '">',
            '</a>'
        ) . '</td></tr>';

    foreach ( $rcp_meta_box['fields'] as $field ) {
        // get current post meta data
        $meta = get_post_meta( $post->ID, $field['id'], true );

        echo '<tr>';
			echo '<th style="width:20%" class="rcp_meta_box_label"><label for="', $field['id'], '">', $field['name'], '</label></th>';
                echo '<td class="rcp_meta_box_field">';
				switch ($field['type']) {
					case 'select':

						echo '<select name="', $field['id'], '" id="', $field['id'], '">';
						foreach ( $field['options'] as $option ) {
							echo '<option', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
						}
						echo '</select>';
						break;
					case 'levels':

                        $selected = is_array( $meta ) ? $meta : array( $meta );


                        $levels = rcp_get_subscription_levels( 'all' );
                        foreach ( $levels as $level ) {
    						echo '<input type="checkbox" value="' . $level->id . '"' . checked( true, in_array( $level->id, $selected ), false ) . ' name="' . $field['id'] . '[]" id="' . $field['id'] . '_' . $level->id . '" />&nbsp;';
					        echo '<label for="' . $field['id'] . '_' . $level->id . '">' . $level->name . '</label><br/>';
                        }
						break;
					case 'checkbox':
						echo '<input type="checkbox" value="1" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
						break;
				}
			echo '</td>';
			echo '<td class="rcp_meta_box_desc">', $field['desc'], '</td>';
        echo '</tr>';
    }
    echo '<tr><td colspan="3"><strong>' . __( 'Note 1', 'rcp' ) . '</strong>: ' . __( 'To hide this content from logged-out users, but allow free and paid, set the User Level to "Subscriber".', 'rcp' ) . '</td></tr>';
	echo '<tr><td colspan="3"><strong>' . __( 'Note 2', 'rcp' ) . '</strong>: ' . __( 'Access level, subscription level, and user level can all be combined to require the user meet all three specifications.', 'rcp' ) . '</td></tr>';

    echo '</table>';
}

// Save data from meta box
function rcp_save_meta_data( $post_id ) {

    $rcp_meta_box = rcp_get_metabox_fields();

    // verify nonce
    if ( !isset( $_POST['rcp_meta_box'] ) || !wp_verify_nonce( $_POST['rcp_meta_box'], basename( __FILE__ ) ) ) {
        return $post_id;
    }

    // check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }

    // check permissions
    if ( 'page' == $_POST['post_type'] ) {
        if ( !current_user_can( 'edit_page', $post_id ) ) {
            return $post_id;
        }
    } elseif ( !current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    foreach ( $rcp_meta_box['fields'] as $field ) {
		if( isset( $_POST[$field['id']] ) ) {

			$old = get_post_meta ($post_id, $field['id'], true );
			$data = $_POST[$field['id']];

			if ( ( $data || $data == 0 ) && $data != $old ) {
				update_post_meta( $post_id, $field['id'], $data) ;
			} elseif ( '' == $data && $old ) {
				delete_post_meta( $post_id, $field['id'], $old );
			}
		} else {
			delete_post_meta( $post_id, $field['id'] );
		}
    }
}
add_action( 'save_post', 'rcp_save_meta_data' );