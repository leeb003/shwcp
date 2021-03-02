<?php
/*
 * Plugin Name: WP Contacts
 * Plugin URI: https://www.wpcontacts.co
 * Description: Powerful and feature rich contact management: manage contacts, leads, inventory or just about anything else you need to keep track of.  Create multiple databases and assign different users to each.
 * Version: 3.3.3
 * Author: ScriptHat
 * Author URI: http://www.wpcontacts.co
 */
if(defined('SHWCP_PLUGIN_VERSION') ) {
    die('ERROR: It looks like you already have one instance of WP Contacts installed. WordPress cannot activate and handle two instances at the same time, you need to remove the old one first.');
}


// Constants
    define('SHWCP_ROOT_FILE', __FILE__);
    define('SHWCP_ROOT_PATH', dirname(__FILE__));
    define('SHWCP_ROOT_URL', plugins_url('', __FILE__));
    define('SHWCP_PLUGIN_VERSION', '3.3.3');
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

	// Load classes
   	require_once SHWCP_ROOT_PATH . '/includes/class-main-wcp.php';
	$main_wcp = new main_wcp;
	add_action( 'plugins_loaded', array( $main_wcp, 'shwcp_upload_directory') );

	// Load the frontend page content
    require_once SHWCP_ROOT_PATH. '/includes/class-wcp-front.php';
    $wcp_front = new wcp_front;
	$wcp_front->front_init();
	add_action( 'init', array( $wcp_front, 'get_the_current_user' ) );
	add_action( 'after_setup_theme', array( $wcp_front, 'wcp_slug_setup') );

	/* Testing view of function load order */
	//add_action( 'shutdown', function(){
	//    foreach( $GLOBALS['wp_actions'] as $action => $count )
    //    printf( '%s (%d) <br/>' . PHP_EOL, $action, $count );
	//});

   	// set up the default tables on plugin activation
	register_activation_hook( __FILE__, 'shwcp_setup');

	function shwcp_setup($network_wide) {
        require_once SHWCP_ROOT_PATH . '/includes/class-setup-wcp.php';
        if (is_multisite() && $network_wide ) {  // multisite setup
            global $wpdb;
            foreach ($wpdb->get_col("SELECT blog_id FROM $wpdb->blogs") as $blog_id) {
                switch_to_blog($blog_id);
                $setup_wcp = new setup_wcp;
                $exists = $setup_wcp->table_exists();
                if (!$exists) {
                    $setup_wcp->install_shwcp();
                    $setup_wcp->data_shwcp();
					$setup_wcp->options_shwcp();
                }
            }
            restore_current_blog();
        } else { // standard and not multisite installation
            $setup_wcp = new setup_wcp;
            $exists = $setup_wcp->table_exists();
            if (!$exists) {
                $setup_wcp->install_shwcp();
                $setup_wcp->data_shwcp();
				$setup_wcp->options_shwcp();
            }
        }
    }

	// TGM Plugin Activation
	require_once( SHWCP_ROOT_PATH . '/includes/tgmpa/class-tgm-plugin-activation.php');
	require_once( SHWCP_ROOT_PATH . '/includes/wcp-tgmpa.php');
	add_action( 'tgmpa_register', 'shwcp_register_required_plugins');

	// the admin menu
	if ( is_admin() ) {
		require_once( SHWCP_ROOT_PATH . '/includes/class-wcp-api-tabs.php' );
	}

	// Translations
	add_action('init', 'shwcp_load_textdomain');
	function shwcp_load_textdomain() {
		load_plugin_textdomain('shwcp', false, basename( dirname( __FILE__ ) ) . '/lang');
	}

	/*
	 * Update db tables check - runs on new installs and WP Contacts update 
	 */
	add_action('init', 'shwcp_db_check');
	function shwcp_db_check() {
		$db_version = get_option('shwcp_db_ver');
		$current_version = SHWCP_PLUGIN_VERSION;
		if (version_compare($db_version, $current_version, '<') ) {
			//echo '<p>Version is less or not set, updating db and version...</p>';
			require_once SHWCP_ROOT_PATH . '/includes/class-db-updater.php';
			$db_updater = new db_updater;
			$db_updater->run_checks();
			update_option('shwcp_db_ver', SHWCP_PLUGIN_VERSION);
		}
	}

	// add WP Cron job for email notifications if enabled
	require_once SHWCP_ROOT_PATH . '/includes/class-wcp-cron.php';
	$wcp_cron = new wcp_cron;
	add_action(	'wcp_cron_schedule_hook', array($wcp_cron, 'wcp_cron_check'), 10, 4);
	wp_schedule_single_event( time() + 600, 'wcp_cron_schedule_hook', array());  //every 10 min check 600 sec
	

	// Full page template class
    require_once SHWCP_ROOT_PATH . '/includes/class-page-templater.php';

	// Gutenberg or Classic editor - load files and scripts for whats used
	add_action( 'plugins_loaded', array( $main_wcp, 'shwcp_editor_check') );

	// Automatic user addition on registration
	add_action( 'user_register', array( $main_wcp, 'shwcp_auto_role') );

	/* Form Plugin integrations (Contact Form 7, Ninja, Gravity */
	require_once SHWCP_ROOT_PATH . '/includes/class-form-integration.php';
	$form_integration = new form_integration;

	// Contact Form 7 integration
	add_action( 'wpcf7_init', array($form_integration, 'wpcf7_add_shortcode_wpcontacts') );
	add_action( 'wpcf7_mail_sent', array($form_integration, 'wpcontacts_add_lead'), 2, 1);

	// Ninja Forms integration
	// pre 3.0 action hook
	add_action( 'ninja_forms_post_process', array($form_integration, 'ninja_forms_wpcontacts') );
	// 3.0 + action hook
	add_action( 'ninja_forms_after_submission', array($form_integration, 'ninja_forms3_wpcontacts') );

	// Gravity Forms integration
	add_action( 'gform_after_submission', array($form_integration, 'gravity_forms_wpcontacts'), 10, 2 );

	/* Rest API Endpoints */
	require_once SHWCP_ROOT_PATH . '/includes/class-wcp-rest.php';
	$wcp_rest = new wcp_rest;

