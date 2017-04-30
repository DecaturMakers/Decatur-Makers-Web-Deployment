<?php
/**
 * Template Functions
 *
 * Functions related to loading and displaying template files
 *
 * @package     Restrict Content Pro
 * @subpackage  Template Functions
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */


/**
 * Returns the path to the EDD templates directory
 *
 * @access      private
 * @since       1.5
 * @return      string
 */
function rcp_get_templates_dir() {
	return RCP_PLUGIN_DIR . 'templates';
}

/**
 * Returns the URL to the EDD templates directory
 *
 * @access      private
 * @since       1.3.2.1
 * @return      string
 */
function rcp_get_templates_url() {
	return RCP_PLUGIN_URL . 'templates';
}

/**
 * Retrieves a template part
 *
 * @since v1.5
 *
 * Taken from bbPress
 *
 * @param string $slug
 * @param string $name Optional. Default null
 *
 * @uses  rcp_locate_template()
 * @uses  load_template()
 * @uses  get_template_part()
 */
function rcp_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parst to be filtered
	$templates = apply_filters( 'rcp_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return rcp_locate_template( $templates, $load, false );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * Taken from bbPress
 *
 * @since v1.5
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true.
 *                            Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function rcp_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// Check child theme first
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'rcp/' . $template_name ) ) {
			$located = trailingslashit( get_stylesheet_directory() ) . 'rcp/' . $template_name;
			break;

		// Check parent theme next
		} elseif ( file_exists( trailingslashit( get_template_directory() ) . 'rcp/' . $template_name ) ) {
			$located = trailingslashit( get_template_directory() ) . 'rcp/' . $template_name;
			break;

		// Check theme compatibility last
		} elseif ( file_exists( trailingslashit( rcp_get_templates_dir() ) . $template_name ) ) {
			$located = trailingslashit( rcp_get_templates_dir() ) . $template_name;
			break;
		}
	}

	if ( ( true == $load ) && ! empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}