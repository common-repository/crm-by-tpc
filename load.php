<?php
/**
 * Plugin Name: WPPC CRM (Free)
 * Plugin URI: https://www.wppluginco.com/product/crm-plugin-for-wordpress/
 * Description: The CRM Plugin for WordPress is an unobtrusive application that extends the native WordPress Users section to provide better sorting, filtering and search utilities for a variety of purposes such as business professionals who want to track and organize their sales leads with ease.
 * Author: WPPluginCo.com, Spencer Hill (s3w47m88)
 * Author URI: https://www.wppluginco.com/
 * Version: 1.1.8
 */
defined( 'TPC_CRM_ROOT'	 	 ) or define( 'TPC_CRM_ROOT'  	 , dirname( __FILE__ ) );
defined( 'TPC_CRM_URL' 	 	 ) or define( 'TPC_CRM_URL'   	 , plugins_url( basename( TPC_CRM_ROOT ) ) );
defined( 'TPC_CRM_SLUG'   	 ) or define( 'TPC_CRM_SLUG'	 , 'tpc-crm' );
defined( 'TPC_CRM_INSTALLED' ) or define( 'TPC_CRM_INSTALLED', true );
defined( 'TPC_CRM_FREE_VERSION') or define( 'TPC_CRM_FREE_VERSION', '1.1.7');

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'start.php' );

$adminNotices = new TPC_CRM_Admin_Notices( );
$isUserNotified = ( bool ) get_option( '_tpc_is_notified' );

if( !$isUserNotified ) {
	if( !function_exists( 'is_plugin_inactive' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	$tpcp_version = get_option( '_tpcp_crm_version' );
	if( !$tpcp_version ) {
		$adminNotices->addError( sprintf( __( 'Upgrade to <a href="%s">Customer Relationship Manager (Premium)</a> to get more features.', TPC_CRM_SLUG ), 'http://www.theportlandcompany.com/product/customer-relationship-manager-plugin-for-wordpress' ) );
	}

	if( !file_exists( dirname( dirname( __FILE__ ) ) . '/advanced-custom-fields/acf.php' ) ) {
		$adminNotices->addError( sprintf( __( 'Install <a href="%s">Advanced Custom Fields</a> to get more features.', TPC_CRM_SLUG ), admin_url( 'plugin-install.php?tab=search&s=Advanced+Custom+Fields&plugin-search-input=Search+Plugins' ) ) );
	} else if( is_plugin_inactive( 'advanced-custom-fields/acf.php' ) ) {
		$adminNotices->addError( sprintf( __( 'Activate <a href="%s">Advanced Custom Fields</a> to get more features.', TPC_CRM_SLUG ), admin_url( 'plugins.php' ) ) );
	}

	update_option( '_tpc_is_notified', 1 );
}
$adminNotices->run();

/**
 * Add a hook to plugins_loaded
 */
function tpc_load( ) {
	do_action( 'tpc_crm_before_load' );

	$adminUsers = new TPC_CRM_Admin_Users();
	$adminUsers->run();

	$featurePointer = new TPC_CRM_Admin_FeaturePointers( );
	$featurePointer->run( );

	$settingsPage = new TPC_CRM_Admin_Settings();
	$settingsPage->run();

	do_action( 'tpc_crm_loaded' );
}
add_action( 'plugins_loaded', 'tpc_load' );

// Show Intro notice (activated, updated)
function crm_free_show_intro_notice() {
        $dismiss_first_version_activation_notice_crm_free = get_user_option( 'dismiss_first_version_activation_notice_crm_free' );

        if ( $dismiss_first_version_activation_notice_crm_free != 1 ) { ?>
            <div class="updated">
                <p>
                    Thank you for using CRM (Free) Plugin for Wordpress. For support please post in our <a href="https://www.wppluginco.com/forums/">forums</a>. You may also be interested in our other <a href="https://www.wppluginco.com/">Plugins</a> or services including <a href="https://www.wppluginco.com/">Website Development</a>, <a href="https://www.wppluginco.com/">Custom Wordpress Plugin Development</a>, <a href="https://www.wppluginco.com/">Search Marketing and Brand Management</a>.
                </p>
            </div>

            <?php update_user_meta( get_current_user_id(), 'dismiss_first_version_activation_notice_crm_free', true );
        }
        
}

// Enable intro notice
function crm_free_add_intro_notice() {
	// Enable notice
    update_user_meta( get_current_user_id(), 'dismiss_first_version_activation_notice_crm_free', false );
}


// Installation
function tpc_crm_install( ) {
	update_option( '_tpc_crm_free_version', TPC_CRM_FREE_VERSION );

	crm_free_add_intro_notice();
}
register_activation_hook( __FILE__, 'tpc_crm_install' );

// Deactivation
function tpc_crm_uninstall( ) {
	update_option( '_tpc_crm_free_version', 0 );
}
register_deactivation_hook( __FILE__, 'tpc_crm_uninstall' );

add_action('admin_notices', 'crm_free_show_intro_notice');

add_action('update_option__tpc_crm_free_version', 'crm_free_add_intro_notice' );

