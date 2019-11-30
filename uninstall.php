<?php
/*
 * uninstall.php 
 * Remove our stuff on plugin uninstallation
 */

// Constants - same as main file
    define('SHWCP_ROOT_FILE', __FILE__);
    define('SHWCP_ROOT_PATH', dirname(__FILE__));
    define('SHWCP_ROOT_URL', plugins_url('', __FILE__));
    define('SHWCP_PLUGIN_VERSION', '3.2.2');
    define('SHWCP_PLUGIN_SLUG', basename(dirname(__FILE__)));
    define('SHWCP_PLUGIN_BASE', plugin_basename(__FILE__));
    define('SHWCP_TEMPLATE', 'wcp-fullpage-template.php');

    // table names
    define('SHWCP_LEADS', 'shwcp_leads');
    define('SHWCP_SST', 'shwcp_sst');
    define('SHWCP_LOG', 'shwcp_log');
    define('SHWCP_SORT', 'shwcp_sort');
    define('SHWCP_NOTES', 'shwcp_notes');
    define('SHWCP_EVENTS', 'shwcp_events');


if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

function shwcp_delete_plugin() {
    global $wpdb;
	// Load classes
    require_once SHWCP_ROOT_PATH . '/includes/class-main-wcp.php';
    $main_wcp = new main_wcp;
	$db_array = $main_wcp->wcp_getdbs();	
	foreach ($db_array as $k => $v) {
		$db = $k;
		$deleted_db = $main_wcp->wcp_deldb($db);
	} 
	delete_option('shwcp_db_ver');
}

shwcp_delete_plugin();
