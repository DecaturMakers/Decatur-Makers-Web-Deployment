<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * Extend the WP_List_Table class
 *
 * @access      public
 * @since       1.0
 * @return      void
*/
class RCP_Logs_List_Table extends WP_List_Table {


    function __construct(){
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'log',     //singular name of the listed records
            'plural'    => 'logs',    //plural name of the listed records
            'ajax'      => false             //does this table support ajax?
        ) );

    }


    /**
	 * Render the column contents
	 *
	 * @access      public
	 * @since       1.0
	 * @return      string The contents of each column
	*/
    function column_default( $item, $column_name ){

        switch( $column_name ){

        	case 'log_error' :
        		return get_the_title( $item->ID );

        	case 'message' :

        		return  get_post_field( 'post_content', $item->ID );

            case 'user_id' :

                $user_id = get_post_meta( $item->ID, '_wp_log_user_id', true );
                $user    = get_userdata( $user_id );

                return  '<a href="' . esc_url( add_query_arg( 'user', $user_id, 'admin.php?page=rcp-logs' ) ) . '" title="' . esc_attr( __( 'View logs for this user', 'rcp' ) ) . '">' . $user->display_name . '</a>';

        	case 'date' :
        		$date = strtotime( get_post_field( 'post_date', $item->ID ) );
        		return date_i18n( get_option( 'date_format' ), $date ) . ' ' . __( 'at', 'rcp' ) . ' ' . date_i18n( get_option( 'time_format' ), $date );

        }
    }


   	/**
	 * Render the checbox column
	 *
	 * @access      public
	 * @since       1.0
	 * @return      string HTML Checkbox
	*/
    function column_cb( $item ){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ 'log',
            /*$2%s*/ $item->ID
        );
    }


    /**
	 * Setup our table columns
	 *
	 * @access      public
	 * @since       1.0
	 * @return      array
	*/
    function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'log_error' => __( 'Log Error', 'rcp' ),
            'message'   => __( 'Log Message', 'rcp' ),
            'user_id'   => __( 'User', 'rcp' ),
            'date'      => __( 'Date', 'rcp' )
        );
        return $columns;
    }


    /**
	 * Register our bulk actions
	 *
	 * @access      public
	 * @since       1.0
	 * @return      array
	*/
    function get_bulk_actions() {
        $actions = array(
            'delete' => __( 'Delete', 'rcp' ),
        );
        return $actions;
    }


    /**
	 * Process bulk action requests
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	*/
    function process_bulk_action() {

        $ids = isset( $_GET['log'] ) ? $_GET['log'] : false;

        if( !is_array( $ids ) )
            $ids = array( $ids );

        foreach( $ids as $id ) {
            // Detect when a bulk action is being triggered...
            if( 'delete' === $this->current_action() ) {
                wp_delete_post( $id );
            }

        }
    }


    /**
	 * Load all of our data
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	*/
    function prepare_items() {

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 20;

        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

        $columns = $this->get_columns();

        $hidden = array(); // no hidden columns

        $this->_column_headers = array( $columns, $hidden, array() ) ;

        $this->process_bulk_action();

        $meta_query = array();
        if( isset( $_GET['user'] ) ) {
            $meta_query[] = array(
                'key'   => '_wp_log_user_id',
                'value' => absint( $_GET['user'] )
            );
        }

        $this->items = WP_Logging::get_connected_logs( array(
        	'log_type'       => 'gateway_error',
        	'paged'          => $paged,
        	'posts_per_page' => $per_page,
            'meta_query'     => $meta_query
        ) );


        $current_page = $this->get_pagenum();

        $total_items = WP_Logging::get_log_count( 0, 'gateway_error', $meta_query );

        $this->set_pagination_args( array(
            'total_items' => $total_items,                    //WE have to calculate the total number of items
            'per_page'    => $per_page,                       //WE have to determine how many items to show on a page
            'total_pages' => ceil( $total_items / $per_page ) //WE have to calculate the total number of pages
        ) );
    }

}