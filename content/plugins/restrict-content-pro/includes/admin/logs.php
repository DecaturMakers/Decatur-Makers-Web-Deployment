<?php

require_once( dirname( __FILE__ ) . '/logs-list-table.php' );

function rcp_logs_page() {
	?>
	<div class="wrap">

        <div id="icon-tools" class="icon32"><br/></div>
        <h2><?php _e( 'RCP Error Logs', 'rcp' ); ?></h2>

       	<form method="get" id="rcp-error-logs">
       		<input type="hidden" name="page" value="rcp-logs"/>
	        <?php

	        $logs_table = new RCP_Logs_List_Table();

	        //Fetch, prepare, sort, and filter our data...
	        $logs_table->prepare_items();

	        $logs_table->views();

	        $logs_table->display();

        ?>
    	</form>
    </div>
    <?php
}