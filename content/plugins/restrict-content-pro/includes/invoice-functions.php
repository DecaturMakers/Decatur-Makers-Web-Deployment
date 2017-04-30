<?php

/**
 * Generate URL to download a PDF invoice
 *
 * @since 2.0
 * @return string
*/
function rcp_get_pdf_download_url( $payment_id = 0 ) {

	if ( empty( $payment_id ) )
		return false;

	$base = is_admin() ? admin_url( 'index.php' ) : home_url();

	return wp_nonce_url( add_query_arg( array( 'payment_id' => $payment_id, 'rcp-action' => 'download_invoice' ), $base ), 'rcp_download_invoice_nonce' );
}

/**
 * Generate PDF Invoice
 *
 * Loads and stores all of the data for the payment.  The HTML2PDF class is
 * instantiated and do_action() is used to call the invoice template which goes
 * ahead and renders the invoice.
 *
 * @since 2.0
 * @uses HTML2PDF
 * @uses wp_is_mobile()
*/
function rcp_generate_pdf_invoice( $payment_id = 0 ) {
	global $rcp_options;

	include_once( RCP_PLUGIN_DIR . '/includes/libraries/tcpdf/tcpdf.php' );
	include_once( RCP_PLUGIN_DIR . '/includes/class-rcp-pdf-invoice.php' );

	if ( empty( $payment_id ) )
		return;

	$payments_db  = new RCP_Payments;
	$payment      = $payments_db->get_payment( $payment_id );

	if( ! $payment ) {
		wp_die( __( 'This payment record does not exist', 'rcp' ) );
	}

	if( $payment->user_id != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have permission to download this invoice', 'rcp' ) );
	}

	$userdata     = get_userdata( $payment->user_id );
	$company_name = isset( $rcp_options['invoice_company'] ) ? $rcp_options['invoice_company'] : '';
	$payment_date = date_i18n( get_option( 'date_format' ), strtotime( $payment->date ) );

	$pdf = new RCP_PDF_Invoice( 'P', 'mm', 'A4', true, 'UTF-8', false );
	$pdf->SetDisplayMode( 'real' );
	$pdf->setJPEGQuality( 100 );

	$pdf->SetTitle( sprintf( __( 'Invoice #%d', 'rcp' ), $payment->id ) );
	$pdf->SetCreator( __( 'Restrict Content Pro', 'rcp' ) );
	$pdf->SetAuthor( get_option( 'blogname' ) );

	$pdf->SetMargins( 8, 8, 8 );
	$pdf->SetX( 8 );

	$pdf->AddPage();

	$font = isset( $rcp_options['invoice_enable_char_support'] ) ? 'freesans' : 'Helvetica';

	$pdf->Ln( 5 );

	if ( isset( $rcp_options['invoice_logo'] ) && ! empty( $rcp_options['invoice_logo'] ) ) {
		$pdf->Image( $rcp_options['invoice_logo'], 8, 20, '', '11', '', false, 'LTR', false, 96 );
	} else {
		$pdf->SetFont( $font, '', 22 );
		$pdf->SetTextColor( 50, 50, 50 );
		$pdf->Cell( 0, 0, $company_name, 0, 2, 'L', false );
	}

	$pdf->SetFont( $font, '', 18 );
	$pdf->SetY(45);
	$pdf->Cell( 0, 0, __( 'Invoice', 'rcp' ), 0, 2, 'L', false );

	$pdf->SetXY( 8, 60 );
	$pdf->SetFont( $font, 'B', 10 );
	$pdf->Cell( 0, 6, __( 'From', 'rcp' ), 0, 2, 'L', false );
	$pdf->SetFont( $font, '', 10 );
	if ( ! empty( $rcp_options['invoice_name'] ) ) {
		$pdf->Cell( 0, $pdf->calculate_line_height( $rcp_options['invoice_name'] ), $rcp_options['invoice_name'], 0, 2, 'L', false );
	}
	if ( ! empty( $rcp_options['invoice_address'] ) ) {
		$pdf->Cell( 0, $pdf->calculate_line_height( $rcp_options['invoice_address'] ), $rcp_options['invoice_address'], 0, 2, 'L', false );
	}
	if ( ! empty( $rcp_options['invoice_address_2'] ) ) {
		$pdf->Cell( 0, $pdf->calculate_line_height( $rcp_options['invoice_address_2'] ), $rcp_options['invoice_address_2'], 0, 2, 'L', false );
	}
	if ( ! empty( $rcp_options['invoice_city_state_zip'] ) ) {
		$pdf->Cell( 0, $pdf->calculate_line_height( $rcp_options['invoice_city_state_zip'] ), $rcp_options['invoice_city_state_zip'], 0, 2, 'L', false );
	}
	if ( ! empty( $rcp_options['invoice_email'] ) ) {
		$pdf->SetTextColor( 41, 102, 152 );
		$pdf->Cell( 0, $pdf->calculate_line_height( $rcp_options['invoice_email'] ), $rcp_options['invoice_email'], 0, 2, 'L', false );
	}

	$pdf->SetTextColor( 50, 50, 50 );

	$pdf->Ln(12);

	$pdf->Ln();
	$pdf->SetXY( 60, 60 );
	$pdf->SetFont( $font, 'B', 10 );
	$pdf->Cell( 0, 6, __( 'To', 'rcp' ), 0, 2, 'L', false );
	$pdf->SetFont( $font, '', 10 );
	$pdf->Cell( 0, $pdf->calculate_line_height( $userdata->display_name ), $userdata->display_name, 0, 2, 'L', false );
	$pdf->SetTextColor( 41, 102, 152 );
	$pdf->Cell( 0, 6, $userdata->user_email, 0, 2, 'L', false );
	$pdf->SetTextColor( 50, 50, 50 );

	$pdf->Ln( 5 );

	$pdf->SetX( 60 );
	$pdf->SetTextColor( 110, 110, 110 );
	$pdf->Cell( 30, 6, __( 'Invoice Date', 'rcp' ), 0, 0, 'L', false );
	$pdf->SetTextColor( 50, 50, 50 );
	$pdf->Cell( 0, 6, $payment_date, 0, 2, 'L', false );

	$pdf->SetX( 60 );
	$pdf->SetTextColor( 110, 110, 110 );
	$pdf->Cell( 30, 6, __( 'Invoice ID', 'rcp' ), 0, 0, 'L', false );
	$pdf->SetTextColor( 50, 50, 50 );
	$pdf->Cell( 0, 6, '#' . $payment->id, 0, 2, 'L', false );

	$pdf->SetX( 60 );
	$pdf->SetTextColor( 110, 110, 110 );
	$pdf->Cell( 30, 6, __( 'Subscription Key', 'rcp' ), 0, 0, 'L', false );
	$pdf->SetTextColor( 50, 50, 50 );
	$pdf->Cell( 0, 6, $payment->subscription_key, 0, 2, 'L', false );
	$pdf->SetX( 60 );

	$pdf->SetX( 60 );
	$pdf->SetTextColor( 110, 110, 110 );
	$pdf->Cell( 30, 6, __( 'Payment Method', 'rcp' ), 0, 0, 'L', false );
	$pdf->SetTextColor( 50, 50, 50 );
	$pdf->Cell( 0, 6, $payment->payment_type, 0, 2, 'L', false );

	$pdf->SetX( 60 );
	$pdf->SetTextColor( 110, 110, 110 );
	$pdf->Cell( 30, 6, __( 'Transaction ID', 'rcp' ), 0, 0, 'L', false );
	$pdf->SetTextColor( 50, 50, 50 );
	$pdf->Cell( 0, 6, $payment->transaction_id, 0, 2, 'L', false );

	$pdf->Ln( 5 );

	$pdf->SetX( 61 );
	$pdf->SetFillColor( 224, 224, 224 );
	$pdf->SetDrawColor( 209, 209, 209 );
	$pdf->SetFont( $font, 'B', 10 );
	$pdf->Cell( 140, 8, __( 'Invoice Items', 'rcp' ), 1, 2, 'C', true );

	$pdf->Ln( 0.2 );

	$pdf->SetX( 61 );
	$pdf->SetDrawColor( 194, 221, 231 );
	$pdf->SetFillColor( 238, 238, 238 );
	$pdf->SetFont( $font, '', 9 );

	$pdf->Cell( 102, 7, __( 'Subscription', 'rcp' ), 'BL', 0, 'C', false );
	$pdf->Cell( 38, 7, __( 'Amount', 'rcp' ), 'BR', 0, 'C', false );

	$pdf->Ln( 0.2 );

	$pdf->Ln();
	$pdf->SetX( 61 );
	$pdf->SetDrawColor( 238, 238, 238 );
	$pdf->SetX( 61 );
	$pdf->SetFont( $font, '', 10 );
	
	$amount = utf8_encode( html_entity_decode( rcp_currency_filter( $payment->amount ), ENT_COMPAT, 'UTF-8' ) );

	if ( function_exists( 'iconv' ) ) {
		// Ensure characters like euro; are properly converted. See GithuB issue #472 and #1570
		$amount = iconv('UTF-8', 'windows-1252', $amount );
	}

	$pdf->Cell( 102, 8, html_entity_decode( $payment->subscription ), 'B', 0, 'L', false );
	$pdf->SetFillColor( 250, 250, 250 );
	$pdf->Cell( 38, 8, $amount, 'B', 2, 'R', true );

	$pdf->Ln( 5 );
	$pdf->SetX( 61 );

	$pdf->SetFillColor( 224, 224, 224 );
	$pdf->SetDrawColor( 209, 209, 209 );
	$pdf->SetFont( $font, 'B', 10 );
	$pdf->Cell( 140, 8, __( 'Invoice Totals', 'rcp' ), 1, 2, 'C', true );

	$pdf->Ln( 0.2 );

	$pdf->SetDrawColor( 238, 238, 238 );
	$pdf->SetFillColor( 250, 250, 250 );

	$pdf->SetX( 61 );
	$pdf->SetFont( $font, 'B', 11 );
	$pdf->Cell( 102, 10, __( 'Total', 'rcp' ), 'B', 0, 'L', false );
	$pdf->Cell( 38, 10, $amount, 'B', 2, 'R', true );

	$pdf->Ln( 10 );

	if ( ! empty( $rcp_options['invoice_notes'] ) ) {

		$pdf->SetX( 60 );
		$pdf->SetFont( $font, '', 13 );
		$pdf->Cell( 0, 6, __( 'Additional Notes', 'rcp' ), 0, 2, 'L', false );
		$pdf->Ln(2);

		$pdf->SetX( 60 );
		$pdf->SetFont( $font, '', 10 );
		$pdf->MultiCell( 0, 6, $rcp_options['invoice_notes'], 0, 'L', false );

	}

	if ( wp_is_mobile() ) {
		$pdf->Output( apply_filters( 'rcp_invoice_filename_prefix', 'Invoice-' ) . $payment->id . '.pdf', 'I' );
	} else {
		$pdf->Output( apply_filters( 'rcp_invoice_filename_prefix', 'Invoice-' ) . $payment->id . '.pdf', 'D' );
	}


	die(); // Stop the rest of the page from processsing and being sent to the browser
}

function rcp_trigger_invoice_download() {

	if( ! isset( $_GET['rcp-action'] ) || 'download_invoice' != $_GET['rcp-action'] ) {
		return;
	}

	if( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp_download_invoice_nonce' ) ) {
		return;
	}

	$payment_id = absint( $_GET['payment_id'] );

	rcp_generate_pdf_invoice( $payment_id );

}
add_action( 'init', 'rcp_trigger_invoice_download' );