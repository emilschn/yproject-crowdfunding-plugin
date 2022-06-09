<?php
/**
 * Export
 *
 * Support exporting data for a specific campaign/download.
 *
 * @since Appthemer CrowdFunding 0.1-alpha
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Export capability shim for Easy Digital Downloads
 *
 * @since Appthemer CrowdFunding 1.3
 *
 * @return boolean If a user can export campaign data.
 */
function atcf_export_capability() {
	return current_user_can( 'submit_campaigns' ) || current_user_can( 'manage_options' );
}
add_filter( 'edd_export_capability', 'atcf_export_capability' );