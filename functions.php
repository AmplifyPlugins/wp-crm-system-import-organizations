<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'plugins_loaded', 'wp_crm_system_export_organizations' );
function wp_crm_system_export_organizations(){
	if ( isset( $_POST[ 'wpcrm_system_export_organizations_nonce' ] ) ) {
		if( wp_verify_nonce( $_POST[ 'wpcrm_system_export_organizations_nonce' ], 'wpcrm-system-export-organizations-nonce' ) ) {
			require_once WP_CRM_SYSTEM_PLUGIN_DIR_PATH . '/includes/class-export.php';
			require_once WPCRM_IMPORT_ORGANIZATIONS_DIR . '/class-export.php';
			
			$export = new WPCRM_System_Export_Organizations();

			$export->export();
		}
	}
}