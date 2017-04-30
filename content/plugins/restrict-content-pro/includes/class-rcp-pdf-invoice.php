<?php
/**
 * PDF Invoice Class
 *
 * Extends the TCPDF class to add the extra functionality for the PDF Invoices
 *
 * @since 2.0
 * @package Restrict Content Pro - PDF Invoices
*/

/**
 * RCP_PDF_Invoice Class
 */
class RCP_PDF_Invoice extends TCPDF {

	/**
	 * Header
	 *
	 * Outputs the header message configured in the Settings on all the invoices
	 * as well as display the background images on certain templates
	 *
	 * @since 2.0
	 */
	public function Header() {

		global $rcp_options;

		$font = isset( $rcp_options['invoice_enable_char_support'] ) ? 'freesans' : 'helvetica';
		$this->SetFont( $font, 'I', 8 );

		if ( ! empty( $rcp_options['invoice_header'] ) ) {

			$header = isset( $rcp_options['invoice_header'] ) ? $rcp_options['invoice_header']: '';

			$this->Cell( 0, 10, stripslashes_deep( html_entity_decode( $header, ENT_COMPAT, 'UTF-8' ) ), 0, 0, 'C');
		} // end if

	} // end Header

	/**
	 * Footer
	 *
	 * Outputs the footer message configured in the Settings on all the invoices
	 *
	 * @since 2.0
	 */
	public function Footer() {

		global $rcp_options;

		$this->SetY( -15 );

		$font = isset( $rcp_options['invoice_enable_char_support'] ) ? 'freesans' : 'helvetica';
		$this->SetFont( $font, 'I', 8 );

		if ( ! empty( $rcp_options['invoice_footer'] ) ) {

			$footer = isset( $rcp_options['invoice_footer'] ) ? $rcp_options['invoice_footer']: '';

			$this->Cell( 0, 10, stripslashes_deep( html_entity_decode( $footer, ENT_COMPAT, 'UTF-8' ) ), 0, 0, 'C');
		} // end if

	} // end Footer

	/**
	 * Calculate Line Heights
	 *
	 * Calculates the line heights for the 'To' block
	 *
	 * @since 1.0
	 *
	 * @param string $setting Setting name.
	 *
	 * @return string Returns line height.
	 */
	public function calculate_line_height( $setting ) {

		if ( empty( $setting ) ) {
			return 0;
		} else {
			return 6;
		}
	}

} // end class