<?php
   /*
   Plugin Name: WP-CRM System Import Organizations
   Plugin URI: https://www.wp-crm.com
   Description: Upload organizations to WP-CRM System in a CSV file.
   Version: 2.2.3
   Author: Scott DeLuzio
   Author URI: https://www.wp-crm.com
   Text Domain: wp-crm-system-import-organizations
   */

	/*  Copyright 2015-2016  Scott DeLuzio (email : support (at) wp-crm.com)	*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define( 'WPCRM_IMPORT_ORGANIZATIONS', __FILE__ );
define( 'WPCRM_IMPORT_ORGANIZATIONS_VERSION', '2.2.3' );
if ( ! defined( 'WPCRM_IMPORT_ORGANIZATIONS_DIR' ) ){
	define( 'WPCRM_IMPORT_ORGANIZATIONS_DIR', plugin_dir_path( __FILE__ ) );
}
include( WPCRM_IMPORT_ORGANIZATIONS_DIR . 'functions.php' );

/* Start Updater */
if (!defined('WPCRM_BASE_STORE_URL')){
	define( 'WPCRM_BASE_STORE_URL', 'http://wp-crm.com' );
}
// the name of your product. This should match the download name in EDD exactly
define( 'WPCRM_IMPORT_ORGANIZATIONS_NAME', 'Import Organizations' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

if( !class_exists( 'WPCRM_SYSTEM_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}

function wpcrm_import_organizations_updater() {

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'wpcrm_import_organizations_license_key' ) );

	// setup the updater
	$edd_updater = new WPCRM_SYSTEM_SL_Plugin_Updater( WPCRM_BASE_STORE_URL, __FILE__, array(
			'version' 	=> WPCRM_IMPORT_ORGANIZATIONS_VERSION,	// current version number
			'license' 	=> $license_key,						// license key (used get_option above to retrieve from DB)
			'item_name' => WPCRM_IMPORT_ORGANIZATIONS_NAME, 	// name of this plugin
			'author' 	=> 'Scott DeLuzio'						// author of this plugin
		)
	);

}
add_action( 'admin_init', 'wpcrm_import_organizations_updater', 0 );

function wpcrm_import_organizations_register_option() {
	// creates our settings in the options table
	register_setting('wpcrm_license_group', 'wpcrm_import_organizations_license_key', 'wpcrm_import_organizations_sanitize_license' );
}
add_action('admin_init', 'wpcrm_import_organizations_register_option');

function wpcrm_import_organizations_sanitize_license( $new ) {
	$old = get_option( 'wpcrm_import_organizations_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'wpcrm_import_organizations_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}
function wpcrm_import_organizations_activate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['wpcrm_import_organizations_activate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'wpcrm_import_organizations_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode( WPCRM_IMPORT_ORGANIZATIONS_NAME ), // the name of our product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPCRM_BASE_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "valid" or "invalid"

		update_option( 'wpcrm_import_organizations_license_status', $license_data->license );

	}
}
add_action('admin_init', 'wpcrm_import_organizations_activate_license');

function wpcrm_import_organizations_deactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['wpcrm_import_organizations_deactivate'] ) ) {

		// run a quick security check
	 	if( ! check_admin_referer( 'wpcrm_plugin_license_nonce', 'wpcrm_plugin_license_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'wpcrm_import_organizations_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode( WPCRM_IMPORT_ORGANIZATIONS_NAME ), // the name of our product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPCRM_BASE_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' )
			delete_option( 'wpcrm_import_organizations_license_status' );

	}
}
add_action('admin_init', 'wpcrm_import_organizations_deactivate_license');
/* End Updater */

/* Load Text Domain */
add_action('plugins_loaded', 'wp_crm_import_organizations_plugin_init');
function wp_crm_import_organizations_plugin_init() {
	load_plugin_textdomain( 'wp-crm-system-import-organizations', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}
// Add license key settings field
function wpcrm_organizations_license_field() {
	include( plugin_dir_path( __FILE__ ) . 'license.php' );
}
add_action( 'wpcrm_system_license_key_field', 'wpcrm_organizations_license_field' );

// Add import field
function wpcrm_organizations_import_field() {
	global $wpcrm_active_tab;
	if ($wpcrm_active_tab == 'import') {
		include( plugin_dir_path( __FILE__ ) . 'import.php' );
	}
}
add_action( 'wpcrm_system_import_field', 'wpcrm_organizations_import_field' );

// Add license key status to Dashboard
function wpcrm_system_import_organizations_dashboard_license($plugins) {
	// the $plugins parameter is an array of all plugins

	$extra_plugins = array(
		'import-organizations'	=> 'wpcrm_import_organizations_license_status'
	);

	// combine the two arrays
	$plugins = array_merge($extra_plugins, $plugins);

	return $plugins;
}
add_filter('wpcrm_system_dashboard_extensions', 'wpcrm_system_import_organizations_dashboard_license');