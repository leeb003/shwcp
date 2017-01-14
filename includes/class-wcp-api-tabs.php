<?php
/*
 * The main plugin class, holds everything our plugin does,
 * initialized right after declaration
 */
class SHWCP_API_Tabs {
	
	/*
	 * For easier overriding we declared the keys
	 * here as well as our tabs array which is populated
	 * when registering settings
	 */
	private $first_tab_key           = 'shwcp_main_settings';
	private $permission_settings_key = 'shwcp_permissions';
	private $info_settings_key       = 'shwcp_info';
	private $db_actions_key          = 'shwcp_db';

	private $first_tab_key_db;
	private $permission_settings_key_db;
	private $info_settings_key_db;
	private $db_actions_key_db;
	private $db;

	protected $table_sst;
    protected $table_log;
    protected $table_sort;
    protected $table_notes;

	private $plugin_options_key = 'shwcp_options';
	private $plugin_settings_tabs = array();

	private $shwcp_backup;
	private $shwcp_backup_url;
	private $backup_files;

	private $show_contact_upload = false;

	/*
	 * Fired during plugins_loaded (very very early),
	 * so don't miss-use this, only actions and filters,
	 * current ones speak for themselves.
	 */
	function __construct() {
		// options are dynamic and naming matches table names when using multiple databases
		$db = '';
		if (isset($_GET['db'])) {  // Initial link
    		$db_var = isset($_GET['db']) ? intval($_GET['db']) : 0;
    		if ($db_var > 0) {
        		$db = '_' . $db_var;
    		} else {
        		$db = ''; // for default
    		}
		} elseif (isset($_GET['tab'])) {  // Tab Navigation
			if (preg_match('/^' . $this->first_tab_key . '(\S+)/', $_GET['tab'], $matches)) {
				$db = $matches[1];
			} elseif (preg_match('/^' . $this->permission_settings_key . '(\S+)/', $_GET['tab'], $matches)) {
				$db = $matches[1];
			} elseif (preg_match('/^' . $this->info_settings_key . '(\S+)/', $_GET['tab'], $matches)) {
				$db = $matches[1];
			} elseif (preg_match('/^' . $this->db_actions_key . '(\S+)/', $_GET['tab'], $matches)) {
				$db = $matches[1];
			}
		} elseif (isset($_POST['option_page'])) {  // On Save post 
            if (preg_match('/^' . $this->first_tab_key . '(\S+)/', $_POST['option_page'], $matches)) {
                $db = $matches[1];
            } elseif (preg_match('/^' . $this->permission_settings_key . '(\S+)/', $_POST['option_page'], $matches)) {
                $db = $matches[1];
            } elseif (preg_match('/^' . $this->info_settings_key . '(\S+)/', $_POST['option_page'], $matches)) {
                $db = $matches[1];
            } elseif (preg_match('/^' . $this->db_actions_key . '(\S+)/', $_POST['option_page'], $matches)) {
                $db = $matches[1];
            }
		}
		//echo '<p>DB is ' . $db . '</p>';
		$this->db = $db;  // set private variable for other methods to access

		// override options with specific database if set
		$this->first_tab_key_db            = $this->first_tab_key . $db;
		$this->permission_settings_key_db  = $this->permission_settings_key . $db;
		$this->info_settings_key_db        = $this->info_settings_key . $db;
		$this->db_actions_key_db           = $this->db_actions_key . $db;

		global $wpdb;
        $this->table_main     = $wpdb->prefix . SHWCP_LEADS;
        $this->table_sst      = $wpdb->prefix . SHWCP_SST;
        $this->table_log      = $wpdb->prefix . SHWCP_LOG;
        $this->table_sort     = $wpdb->prefix . SHWCP_SORT;
        $this->table_notes    = $wpdb->prefix . SHWCP_NOTES;

		add_action( 'init', array( &$this, 'load_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_first_tab' ) );
		add_action( 'admin_init', array( &$this, 'register_second_tab' ) );
		add_action( 'admin_init', array( &$this, 'register_third_tab' ) );
		add_action( 'admin_init', array( &$this, 'register_forth_tab' ) );
		add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
		// submenus
		add_action('admin_menu', array(&$this, 'add_admin_submenus' ) );

        // Callback Ajax Backend ajax
        require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-ajax.php');
        $wcp_ajax = new wcp_ajax();
        add_action( 'wp_ajax_ajax-wcpbackend', array($wcp_ajax, 'myajax_wcpbackend_callback'));

		// get folder info in backup directory - repeat of main class for now since this class doesn't extend
        $upload_dir = wp_upload_dir();
        // backup directory & url
        $this->shwcp_backup = $upload_dir['basedir'] . '/shwcp_backups';
        $this->shwcp_backup_url = $upload_dir['baseurl'] . '/shwcp_backups';
		if ( !file_exists($this->shwcp_backup) ) {
            wp_mkdir_p( $this->shwcp_backup );
        }
		// list of backups
		require_once ABSPATH . '/wp-includes/ms-functions.php'; // needed for get_dirsize function
		if (!$db) { // default dabatabase (new name is 'default', old was contacts so grab either)
			$this->backup_files = preg_grep('/^(contacts\S+)|(default\S+)/', scandir($this->shwcp_backup));
		} else {
        	$this->backup_files = preg_grep('/^database' . $db . '([^.])/', scandir($this->shwcp_backup));
		}
	}
	
	/*
	 * Loads both the general and advanced settings from
	 * the database into their respective arrays. Uses
	 * array_merge to merge with default values if they're
	 * missing.
	 */
	function load_settings() {
		$this->first_tab = (array) get_option( $this->first_tab_key_db );
		$this->permission_settings = (array) get_option( $this->permission_settings_key_db );
		$this->info_settings = (array) get_option( $this->info_settings_key_db );
		$this->db_actions = (array) get_option( $this->db_actions_key_db );
		
		// Merge with defaults
		$this->first_tab = array_merge( array(
			'page_option' => 'Some Page',
			'database_name' => 'Default',
			'page_public' => 'false',
			'license_key' => '',
			'troubleshoot' => 'false',   // enable scripts in header vs footer
			'fixed_edit' => 'false',
			'all_fields' => 'false',
			'calendar_events' => 'false',
			'calendar_notify' => 'false',
			'calendar_entries_new' => 'true',
			'calendar_entries_date' => 'false',
			'mailchimp_api' => 'false',
			'custom_time' => 'false',
			'custom_links' => '',
			'custom_css' => '',
			'custom_js' => '',
			'page_page' => 'true',
			'page_page_count' => '40',
			'default_sort' => 'id',
			'default_sort_dir' => 'desc',
			'show_admin' => 'true',
			'page_color' => '#607d8b',
			'logo_attachment_url' => SHWCP_ROOT_URL . '/assets/img/wpcontacts.png',
			'logo_attachment_id' => '',
			'page_footer' => 'WP Contacts &copy;2017 SH-Themes',
			'page_greeting' => 'Welcome To <span class="wcp-primary">WP</span> Contacts',
			'contact_image' => 'true', 
			'contact_image_url' => '',
			'contact_image_id' => '',
			'contact_upload' => 'true'
		), $this->first_tab );

		// set current user default to full permissions
		$current_user = get_current_user_id();
		$this->permission_settings = array_merge( array(
			'advanced_option' => 'Advanced value',
			'own_leads_change_owner' => 'yes',
			'permission_settings' => array (
				"$current_user"=> 'full'
			)
		), $this->permission_settings );

		$this->info_settings = array_merge( array(
			'nothing_yet' => 'nothing'
		), $this->info_settings );

		$this->db_actions = array_merge( array(
			'reset_db' => 'false',
			'backup_db' => 'false',
			'restore_db' => 'false',
			'backups_list' => 'false'

		), $this->db_actions );

		// Generate thumbnail if contact_image_url and id set but no thumbnail exists yet
		if ($this->first_tab['contact_image_url'] != ''
			&& $this->first_tab['contact_image_id'] !='' ) {
			$image_id = intval($this->first_tab['contact_image_id']);

			$image_meta = wp_get_attachment_metadata($image_id);
			if (!empty($image_meta)) {  // make sure it's in the db
				$full_file = $image_meta['file'];
				$file_fullname = basename( $full_file );

				$info = pathinfo($file_fullname);
				$file_name = basename($file_fullname,'.'. $info['extension']);
				$file_ext = $info['extension'];
				$small_image = $file_name . '_25x25' . '.' . $file_ext;
				
				// check for existing thumb
				$upload_dir = wp_upload_dir();
				$shwcp_upload = $upload_dir['basedir'] . '/shwcp' . $this->db;
				if (!file_exists($shwcp_upload . '/' . $small_image)) {
					$new_thumb = $shwcp_upload . '/' . $small_image;
					$full_file_path = $upload_dir['basedir'] . '/' . $full_file;
					$thumb = wp_get_image_editor( $full_file_path );
                    if ( !is_wp_error($thumb) ) {
                        $thumb->resize(25, 25, true);
                        $thumb->save( $new_thumb );
                    }
				}
			}
		}
	}
	
	/*
	 * Registers the main settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_first_tab() {
		$this->plugin_settings_tabs[$this->first_tab_key_db] = __('Main Settings', 'shwcp');
		
		register_setting( $this->first_tab_key_db, $this->first_tab_key_db );
		add_settings_section( 'section_general', __('Main Contacts Settings', 'shwcp'), array( &$this, 'section_general_desc' ), 
				$this->first_tab_key_db );
		add_settings_field( 'page_option', __('Page for WP Contacts', 'shwcp'), array( &$this, 'field_page_option' ), 
				$this->first_tab_key_db, 'section_general' );
		add_settings_field( 'database_name', __('Database Name', 'shwcp'), array( &$this, 'field_database_name' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'page_public', __('Public Accessibility', 'shwcp'), array( &$this, 'field_page_public' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'page_page', __('Use Pagination', 'shwcp'), array( &$this, 'field_page_page' ),
                $this->first_tab_key_db, 'section_general' );
		add_settings_field( 'page_page_count', __('Pagination Results', 'shwcp'), array( &$this, 'field_page_page_count' ),
                $this->first_tab_key_db, 'section_general' );
		add_settings_field( 'default_sort', __('Default Front Sort', 'shwcp'), array( &$this, 'field_default_sort' ),
				$this->first_tab_key_db, 'section_general' );
		add_settings_field( 'show_admin', __('Show WP Admin Bar', 'shwcp'), array( &$this, 'field_show_admin' ),
				$this->first_tab_key_db, 'section_general' );
		add_settings_field( 'page_color', __('Primary Color', 'shwcp'), array( &$this, 'field_color_option' ), 
				$this->first_tab_key_db, 'section_general' );
		add_settings_field( 'logo_attachment_url', __('Logo Image', 'shwcp'), array( &$this, 'field_logo_image' ),
                $this->first_tab_key_db, 'section_general' );
		add_settings_field( 'page_footer', __('Footer Text', 'shwcp'), array( &$this, 'field_footer_option' ), 
				$this->first_tab_key_db, 'section_general' );
		add_settings_field( 'page_greeting', __('Greeting Text', 'shwcp'), array( &$this, 'field_greeting_option' ),
		        $this->first_tab_key_db, 'section_general' );
		add_settings_field( 'contact_image', __('Entry Image', 'shwcp'), array( &$this, 'field_contact_image' ),
				$this->first_tab_key_db, 'section_general' );
		add_settings_field( 'contact_image_url', __('Default Entry Image', 'shwcp'), 
				array( &$this, 'field_default_image_url' ), $this->first_tab_key_db, 'section_general' );
		add_settings_field( 'contact_upload', __('Contact Uploads', 'shwcp'), array( &$this, 'field_contact_upload' ),
				$this->first_tab_key_db, 'section_general' );

		if ($this->first_tab_key == $this->first_tab_key_db) { // only needed on initial settings
			add_settings_field( 'license_key', __('Envato Purchase Code', 'shwcp'), array( &$this, 'field_license_key' ),
                $this->first_tab_key_db, 'section_general' );
		}

		add_settings_field( 'fixed_edit', __('Fixed Edit Column', 'shwcp'), array( &$this, 'field_fixed_edit' ),
                $this->first_tab_key_db, 'section_general' );

		add_settings_field( 'all_fields', __('Search All Fields', 'shwcp'), array( &$this, 'field_all_fields' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'calendar_events', __('Enable Events', 'shwcp'), array( &$this, 'field_calendar_events' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'calendar_notify', __('Enable Event Notifications', 'shwcp'), array( &$this, 'field_calendar_notify' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'calendar_entries_new', __('Calendar New Entries', 'shwcp'), 
				array( &$this, 'field_calendar_entries_new' ), $this->first_tab_key_db, 'section_general' );

		add_settings_field( 'calendar_entries_date', __('Calendar Date Entries', 'shwcp'), 
				array( &$this, 'field_calendar_entries_date' ),$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'mailchimp_api', __('Enable MailChimp Export', 'shwcp'), array( &$this, 'field_mailchimp_api' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'custom_time', __('Use WordPress Date & Time Settings', 'shwcp'), array( &$this, 'field_custom_time' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'custom_links', __('Custom Menu Links', 'shwcp'), array( &$this, 'field_custom_links' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'custom_css', __('Custom CSS', 'shwcp'), array( &$this, 'field_custom_css' ),
				$this->first_tab_key_db, 'section_general' );

		add_settings_field( 'custom_js', __('Custom Javascript', 'shwcp'), array( &$this, 'field_custom_js' ),
                $this->first_tab_key_db, 'section_general' );

		if ($this->first_tab_key == $this->first_tab_key_db) { // only needed on initial settings
            add_settings_field( 'troubleshoot', __('Troubleshooting', 'shwcp'), array( &$this, 'field_troubleshoot' ),
                $this->first_tab_key_db, 'section_general' );
        }
	}
	
	/*
	 * Registers the permission settings and appends the
	 * key to the plugin settings tabs array.
	 */
	function register_second_tab() {
		$this->plugin_settings_tabs[$this->permission_settings_key_db] = __('Permissions', 'shwcp');
		
		register_setting( $this->permission_settings_key_db, $this->permission_settings_key_db );
		add_settings_section( 'section_permission', __('Set Users Access to WP Contacts', 'shwcp'), array( &$this, 'section_permission_desc' ), $this->permission_settings_key_db );
		add_settings_field( 'manage_own_owner', __('Manage Own Leads Ownership Change', 'shwcp'), array( &$this, 'field_manage_own_owner' ), $this->permission_settings_key_db, 'section_permission' );

		add_settings_field( 'user_permission', __('Users', 'shwcp'), array( &$this, 'field_user_permission' ), $this->permission_settings_key_db, 'section_permission' );
	}

	/*
	 * Register third tab info
	 *
	 */
	function register_third_tab() {
		$this->plugin_settings_tabs[$this->info_settings_key_db] = __('Site Information', 'shwcp');

		register_setting( $this->info_settings_key_db, $this->info_settings_key_db );
		add_settings_section( 'section_info', __('Information about your server', 'shwcp'), array( &$this, 'section_site_desc' ), 
				$this->info_settings_key_db);
		add_settings_field( 'site_info', __('Info', 'shwcp'), array( &$this, 'field_section_info'), 
				$this->info_settings_key_db, 'section_info' );
	}

	/*
	 * Register Forth tab info
	 *
	 */
	function register_forth_tab() {
		$this->plugin_settings_tabs[$this->db_actions_key_db] = __('Database Operations', 'shwcp');

        register_setting( $this->db_actions_key_db, $this->db_actions_key_db );
        add_settings_section( 'db_actions', __('Database Actions Available', 'shwcp'), array( &$this, 'section_db_desc' ),
                $this->db_actions_key_db);
		add_settings_field( 'backup_db', __('Backup Database', 'shwcp'), array( &$this, 'field_backup_db'),
                $this->db_actions_key_db, 'db_actions' );
		add_settings_field( 'restore_db', __('Restore Database Backup', 'shwcp'), array( &$this, 'field_restore_db'),
                $this->db_actions_key_db, 'db_actions' );
		add_settings_field( 'backups_list', __('Current Backups', 'shwcp'), array( &$this, 'field_backups_list'),
                $this->db_actions_key_db, 'db_actions' );
        add_settings_field( 'reset_db', __('Reset Database', 'shwcp'), array( &$this, 'field_reset_db'),
                $this->db_actions_key_db, 'db_actions' );
    }
	
	/*
	 * The following methods provide descriptions
	 * for their respective sections, used as callbacks
	 * with add_settings_section
	 */
	function section_general_desc() { echo __('Set up WP Contacts general settings on this tab.', 'shwcp'); }
	function section_permission_desc() { echo __('Set up WP Contacts Users and access to the frontend.  You will have Full Access by default, but you must save this screen initially. Keep in mind if you have public accessible set to true in the Main Settings, all logged in users will also be able to view leads.', 'shwcp'); }
	function section_site_desc() { echo __('Note that these server settings will affect the size, amount, and time taken allowed for uploads and scripts.  Be aware of this as it will affect the size of uploads and time allowed for processing imports etc.  <br />These PHP settings may need to be adjusted on your server according to your requirements.', 'shwcp');
	echo '<br /><br /><u>' . __('You are running version', 'shwcp') . ' <b>' . SHWCP_PLUGIN_VERSION . '</b> ' . __('of WP Contacts', 'shwcp') . '</u><br /><p>' . __('Have a question? Take a look at our', 'shwcp') . ' <a href="http://docs.sh-themes.com/category/plugins/wp-contacts/" target="_blank">' . __('Online Documentation', 'shwcp') . '</p>';
	}
	function section_db_desc() { echo __('This section allows you to perform various actions on your database.  Pay attention to what you are doing as changes here will affect what you have in your database!', 'shwcp'); }
	
	/*
	 * Main Settings field callback, WP Contacts Page
	 */
	function field_page_option() {
		?>
		<p>
		<?php echo __("If you haven't done so, create a blank page for WP Contacts and assign the WP Contacts Template to it first.", 'shwcp'); ?>
		</p>
		<?php
	}

	/*
	 * Field Database Name - used to associate a name to the database to use when the page template is selected
	 * @since 2.0.0
	 */
	function field_database_name() {
		?>
		<input class="database-name" name="<?php echo $this->first_tab_key_db; ?>[database_name]" 
            value="<?php echo esc_attr( $this->first_tab['database_name'] ); ?>" />
        <p><?php echo __("The name used to associate the WP Contacts page template with a specific database.  If you have multiple databases, you'll be able to select this on a page that is using the WP Contacts template.", 'shwcp'); ?></p>
        <?php
    }


	/*
	 * Main Settings field callback, front page accessibility
	 */
	function field_page_public() {
		?>
		<select class="wcp-public" name="<?php echo $this->first_tab_key_db; ?>[page_public]">
			<option value="false" <?php selected( $this->first_tab['page_public'], 'false'); ?>
			><?php echo __('False', 'shwcp'); ?></option>
			<option value="true" <?php selected( $this->first_tab['page_public'], 'true'); ?>
			><?php echo __('True', 'shwcp'); ?></option>
		</select>
		<p>
			<?php echo __("Choose if you want to allow people who are not logged in read access to WP Contacts", "shwcp");?>
		</p>
		<?php
	}

	/*
	 * Envato Purchase License Key
	 */
	function field_license_key() {
		?>
		<input class="wcp-license" name="<?php echo $this->first_tab_key_db; ?>[license_key]" 
			value="<?php echo esc_attr( $this->first_tab['license_key'] ); ?>" />
		<p><?php echo __("Enter your Envato purchase code for updates.", 'shwcp'); ?></p>
		<?php
	}

	/*
	 * Troubleshooting - enable scripts in header to avoid conflicts
	 */
	function field_troubleshoot() {
		 ?>
        <select class="wcp-troubleshoot" name="<?php echo $this->first_tab_key_db; ?>[troubleshoot]">
            <option value="false" <?php selected( $this->first_tab['troubleshoot'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['troubleshoot'], 'true'); ?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("Move scripts to header - only necessary if you are having javascript conflicts with a theme or other plugins. Symptoms would be frontend functionality is not working correctly.  Use as a last resort as the cause of the problem should be located and corrected.", "shwcp");?>
        </p>
        <?php
    }

	/* 
	 * Fixed Edit Columns
	 */
	function field_fixed_edit() {
		?>
		<select class="wcp-fixed-edit" name="<?php echo $this->first_tab_key_db; ?>[fixed_edit]">
            <option value="false" <?php selected( $this->first_tab['fixed_edit'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['fixed_edit'], 'true'); ?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("Choose to enable a sticky edit column on the main view (Edit column stays visable).  Good for large frontend table views.", "shwcp");?>
        </p>
        <?php
    }

	/*
	 * Search All Fields Option
	 */
	function field_all_fields() {
		?>
        <select class="wcp-fixed-edit" name="<?php echo $this->first_tab_key_db; ?>[all_fields]">
            <option value="false" <?php selected( $this->first_tab['all_fields'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['all_fields'], 'true'); ?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("Choose to enable option to search all fields at once in search.  Warning, depending on your server capabilities and how many columns you have these have the potential to be slow.  It's always better to search specific fields.", "shwcp");?>
        </p>
        <?php
    }

	/*
	 * Calendar and events functionality
	 */
	function field_calendar_events() {
		?>
	 	<select class="wcp-events" name="<?php echo $this->first_tab_key_db; ?>[calendar_events]">
            <option value="false" <?php selected( $this->first_tab['calendar_events'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['calendar_events'], 'true'); ?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("Select if you would like to enable Calendar and Events functionality.  This will enable a new link in your frontend menu to the calendar.", "shwcp");
?>
        </p>
        <?php
    }

	/*
	 * Calender Event Notifications
	 */
	function field_calendar_notify() {
        ?>
        <select class="wcp-event-notify" name="<?php echo $this->first_tab_key_db; ?>[calendar_notify]">
            <option value="false" <?php selected( $this->first_tab['calendar_notify'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="email" <?php selected( $this->first_tab['calendar_notify'], 'email'); ?>
            ><?php echo __('Email', 'shwcp'); ?></option>
			<!-- On screen notifications, not implemented yet
			<option value="on-screen" <?php selected( $this->first_tab['calendar_notify'], 'on-screen'); ?>
            ><?php echo __('On-Screen', 'shwcp'); ?></option>
			<option value="both" <?php selected( $this->first_tab['calendar_notify'], 'both'); ?>
            ><?php echo __('Both', 'shwcp'); ?></option>
			-->
        </select>
        <p>
            <?php echo __("Select if you would like to enable event notifications for custom events created.", "shwcp");
?>
        </p>
        <?php
    }

	/* 
	 * Display New Entries on calendar
	 */
	function field_calendar_entries_new() {
		?>
        <select class="calendar-entries-new" name="<?php echo $this->first_tab_key_db; ?>[calendar_entries_new]">
            <option value="false" <?php selected( $this->first_tab['calendar_entries_new'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['calendar_entries_new'], 'true'); ?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("New Entries can show up from your database on your calendar, choose to display these or not.", "shwcp");
?>
        </p>
        <?php
    }   

	/*
     * Display Date/Time entry fields on calendar
	 */
	function field_calendar_entries_date() {
		?>
        <select class="calendar-entries-date" name="<?php echo $this->first_tab_key_db; ?>[calendar_entries_date]">
            <option value="false" <?php selected( $this->first_tab['calendar_entries_date'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['calendar_entries_date'], 'true'); ?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("Choose if you want to enable Entries Date Time fields to appear on your calendar.", "shwcp");
?>
        </p>
        <?php
    }

	/*
	 * MailChimp API Enable
	 */
	function field_mailchimp_api() {
		?>
		 <select class="wcp-mailchimp-api" name="<?php echo $this->first_tab_key_db; ?>[mailchimp_api]">
            <option value="false" <?php selected( $this->first_tab['mailchimp_api'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['mailchimp_api'], 'true'); ?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("Select if you would like to enable exporting direct to MailChimp.  Once enabled, exporting will show up on the frontend on the Import & Export link.", "shwcp");
?>
        </p>
        <?php
    }

	/*
	 * Use WordPress Custom Time format for date and time fields
	 */
	function field_custom_time() {
		?>
         <select class="wcp-custom-time" name="<?php echo $this->first_tab_key_db; ?>[custom_time]">
            <option value="false" <?php selected( $this->first_tab['custom_time'], 'false'); ?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['custom_time'], 'true'); ?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("You can set WP Contacts to use the WordPress custom time settings under Settings -> General.  Keep in mind searching for partial dates will be the most flexible with this disabled.  ", "shwcp");
?>
        </p>
        <?php
    }

	/*
	 * Custom Menu Links
	 */
	function field_custom_links() {
		$link_text     = __('Link Text', 'shwcp');
		$link_url_text = __('Link URL (e.g. http://www.example.com)', 'shwcp');
		$delete_text   = __('Delete', 'shwcp');
		$open_text = __('New window?', 'shwcp');
		?>
		<p><button class="button-primary add-custom-menu-link"><?php echo __('Add New', 'shwcp'); ?> </button></p>
		<table class="wcp-custom-links">
		<tbody>
		<?php
		$inc = 0;
		if (isset($this->first_tab['custom_links'])
			&& is_array($this->first_tab['custom_links'])
		) {
			foreach ($this->first_tab['custom_links'] as $k => $v) {
				if (isset($v['open']) 
					&& $v['open'] == 'on'
				) {
					$checked = 'checked="checked"';
				} else {
					$checked = '';
				}
				echo '<tr><td><input class="wcp-cust-link" name="' . $this->first_tab_key_db . '[custom_links][' . $inc . '][link]"' 
				   . ' placeholder="' . $link_text . '" value="' . $v['link'] . '" /></td>'

				   . '<td><input class="wcp-cust-url" name="' . $this->first_tab_key_db . '[custom_links][' . $inc . '][url]"'
				   . ' placeholder="' . $link_url_text . '" value="' . $v['url'] . '" /> '

				   . $open_text . ' <input type="checkbox" class="wcp-cust-url-open" name="' . $this->first_tab_key_db 
				   . '[custom_links][' . $inc . '][open]" ' . $checked . ' /></td>'

				   . '<td><a class="wcp-cust-delete">' . $delete_text . '</a> <i class="wcp-md md-sort"></i></td>'
				   . '</tr>';
				$inc++;
			}
		}

		?>
		</tbody>
		</table>
		<p><?php echo __('Add your own custom links to the slide out main menu.', 'shwcp'); ?></p>
		<div class="wcp-cust-link-text hide-me"><?php echo $link_text; ?></div>
		<div class="wcp-cust-link-url hide-me"><?php echo $link_url_text; ?></div>
		<div class="wcp-cust-del-text hide-me"><?php echo $delete_text; ?></div>
		<div class="wcp-cust-open-text hide-me"><?php echo $open_text; ?></div>

		<?php
	}

	/*
	 * Custom CSS Overrides
	 */
	function field_custom_css() {
		?>
		<textarea class="custom-css" name="<?php echo $this->first_tab_key_db; ?>[custom_css]"><?php echo esc_attr( $this->first_tab['custom_css'] ); ?></textarea>
		<?php
	}

	/*
	 * Custom Javascript
	 */
	function field_custom_js() {
		?>
		<textarea class="custom-js" name="<?php echo $this->first_tab_key_db; ?>[custom_js]"><?php echo esc_attr( $this->first_tab['custom_js'] ); ?></textarea>
		<?php
	}

	/*
	 * Use pagination for results
	 */
	function field_page_page() {
		?>
		<select class="wcp-page-page" name="<?php echo $this->first_tab_key_db; ?>[page_page]">
			<option value="true" <?php selected( $this->first_tab['page_page'], 'true'); ?>
				><?php echo __('True', 'shwcp'); ?></option>
			<option value="false" <?php selected( $this->first_tab['page_page'], 'false'); ?>
				><?php echo __('False', 'shwcp'); ?></option>
		</select>
		<p>
			<?php echo __("Use pagination for results (highly recommended), or show all results in view.", "shwcp"); ?>
		</p>
		<?php
	}
	/*
	 * Choose to show WP admin bar on the page
	 */
	function field_show_admin() {
		?>
		<select class="wcp-show-admin" name="<?php echo $this->first_tab_key_db; ?>[show_admin]">
			<option value="true" <?php selected( $this->first_tab['show_admin'], 'true'); ?>
                ><?php echo __('True', 'shwcp'); ?></option>
            <option value="false" <?php selected( $this->first_tab['show_admin'], 'false'); ?>
                ><?php echo __('False', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("Select if you want the Wordpress admin bar to show on Contacts page or not.", "shwcp"); ?>
        </p>
        <?php
    }
	/*
	 * If pagination set, result count per page
	 */
	function field_page_page_count() {
		?>
		<select class="wcp-page-page-count" name="<?php echo $this->first_tab_key_db; ?>[page_page_count]">
		<?php 
			for ($i = 1;$i < 201;$i++) {
		?>
			<option value="<?php echo $i; ?>" <?php selected ( $this->first_tab['page_page_count'], $i); ?>
				><?php echo $i; ?></option>
			<?php } ?>
		</select>	
		<p>
			<?php echo __("Set the pagination results per page","shwcp");?>
		</p>
		<?php
	
	}

	/*
	 * Default sorting field and direction for main page results
	 */
	function field_default_sort() {
		global $wpdb;
		$sort_table = $wpdb->get_results(
                "SELECT * from $this->table_sort order by sort_number desc");
		?>
		<select class="wcp-default-sort" name="<?php echo $this->first_tab_key_db; ?>[default_sort]">
		<?php
			foreach ($sort_table as $k => $v) {
		?>
			<option value="<?php echo $v->orig_name; ?>" <?php selected ( $this->first_tab['default_sort'], $v->orig_name); ?>
			 ><?php echo $v->translated_name; ?></option>
			 <?php } ?>
		</select>

		<select class="wcp-default-sort-dir" name="<?php echo $this->first_tab_key_db; ?>[default_sort_dir]">
		  <option value="desc" <?php selected ( $this->first_tab['default_sort_dir'], 'desc'); ?> >
		  <?php echo __('Descending', 'shwcp');?></option>
		  <option value="asc" <?php selected ( $this->first_tab['default_sort_dir'], 'asc'); ?> >
          <?php echo __('Ascending', 'shwcp');?></option>
		</select>
		<p>
			<?php echo __("Choose the Front Page default view sorting field and direction (default is ID Descending)", "shwcp");?>
		</p>
		<?php
	}

	/* 
	 * Color picker field callback, renders a 
	 * color picker
	 */
	function field_color_option() {
		?>
		<input type="text" name="<?php echo $this->first_tab_key_db; ?>[page_color]" class="color-field" value="<?php echo esc_attr( $this->first_tab['page_color'] ); ?>" />
		<p>
			<?php echo __('This sets the primary color for WP Contacts', 'shwcp');?>
		</p>

		<?php
	}

	/*
	 * Logo image upload
	 */
	function field_logo_image() {
		?>
		<button id="upload_now" class="button button-primary custom_media_upload"><?php echo __("Upload", "shwcp");?></button>
		<button class="button button-primary logo_clear"><?php echo __("Clear Image", "shwcp");?></button>
		<img class="wcp_logo_image" style="display:none;" src="<?php echo esc_attr( $this->first_tab['logo_attachment_url']);?>" />
		<input class="custom_media_url" type="text" style="display:none;" name="<?php echo $this->first_tab_key_db;?>[logo_attachment_url]" value="<?php echo esc_attr( $this->first_tab['logo_attachment_url']);?>">
		<input class="custom_media_id" type="text" style="display:none;" name="<?php echo $this->first_tab_key_db;?>[logo_attachment_id]" value="<?php echo esc_attr( $this->first_tab['logo_attachment_id']);?>">
		<?php
	}

	/*
	 * Footer Text field callback, textarea
	 */
	function field_footer_option() {
		?>
		<textarea name="<?php echo $this->first_tab_key_db; ?>[page_footer]"><?php echo esc_attr( $this->first_tab['page_footer'] ); ?> </textarea>
		<?php
	}

	/*
	 * Greeting Text For Login
	 */
	function field_greeting_option() {
        ?>
        <textarea name="<?php echo $this->first_tab_key_db; ?>[page_greeting]"><?php echo esc_attr( $this->first_tab['page_greeting'] ); ?>
 		</textarea>
		<p><?php echo __("Greeting Text on login form, keep it relatively short", "shwcp"); ?></p>
        <?php
    }

	/*
	 * Contact allow images
	 */
	function field_contact_image() {
			// set up display of upload form variable
			if (isset($this->first_tab['contact_image']) ) {
				if ($this->first_tab['contact_image'] == 'true') {
					$this->show_contact_upload = true;
				}
			}
		?>
		 <select class="wcp-contact-image" name="<?php echo $this->first_tab_key_db; ?>[contact_image]">
            <option value="false" <?php selected( $this->first_tab['contact_image'], 'false');?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['contact_image'], 'true');?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p><?php echo __("Allow featured images for each entry.", 'shwcp'); ?></p>
        <?php
	}

	/*
	 * Default Contact Image for entries
	 */
	function field_default_image_url() {
			if ($this->show_contact_upload) {
				$display = 'display-contact';
			} else {
				$display = 'hide-contact';
			}
		?>
		<div class="contact-upload <?php echo $display;?>">
			<button id="upload_now2" class="button button-primary custom_contact_upload"><?php echo __("Upload", "shwcp");?>
			</button>
        	<button class="button button-primary contact_image_clear"><?php echo __("Clear Image", "shwcp");?></button>
        	<img class="wcp_contact_image" style="display:none;" 
				src="<?php echo esc_attr( $this->first_tab['contact_image_url']);?>" />
        	<input class="custom_contact_url" type="text" style="display:none;" 
				name="<?php echo $this->first_tab_key_db;?>[contact_image_url]" 
				value="<?php echo esc_attr( $this->first_tab['contact_image_url']);?>" />
        	<input class="custom_contact_id" type="text" style="display:none;" 
				name="<?php echo $this->first_tab_key_db;?>[contact_image_id]" 
				value="<?php echo esc_attr( $this->first_tab['contact_image_id']);?>" />
			<p><?php echo __("Set an Entry image to use as default for new entries.  Keep in mind the size of the image can affect page loading.", "shwcp"); ?></p>
		</div>
		<?php
	}
	
	/*
	 * Contacts allow file uploads
	 */
	function field_contact_upload() {
		?>
		 <select class="wcp-contact-upload" name="<?php echo $this->first_tab_key_db; ?>[contact_upload]">
            <option value="false" <?php selected( $this->first_tab['contact_upload'], 'false');?>
            ><?php echo __('False', 'shwcp'); ?></option>
            <option value="true" <?php selected( $this->first_tab['contact_upload'], 'true');?>
            ><?php echo __('True', 'shwcp'); ?></option>
        </select>
        <p><?php echo __("Allow file uploads for Contact entries.", 'shwcp'); ?></p>
        <?php
	}
	

	/* Permissions Tab Fields */

	/*
	 * Users with Manage Own Leads Only can or can't change ownership
	 */
	function field_manage_own_owner() {
		?>
		 <select class="own-leads-change-owner" name="<?php echo $this->permission_settings_key_db; ?>[own_leads_change_owner]">
		 	<option value="no" <?php selected ( $this->permission_settings['own_leads_change_owner'], 'no'); ?>>
            <?php echo __('No', 'shwcp'); ?></option>
            <option value="yes" <?php selected ( $this->permission_settings['own_leads_change_owner'], 'yes'); ?>>
			<?php echo __('Yes', 'shwcp'); ?></option>
        </select>
        <p>
            <?php echo __("Allow <b>Manage Own Leads</b> users to change lead ownership (hand leads off to other users) ?","shwcp");?>
        </p>
        <?php
	}

	/*
	 * User Permissions field callback
	 */
	 function field_user_permission() {
			$users = get_users();
			//print_r($users);
			?>
			<table class="wcp_users">
				<tr>
					<th><?php echo __('WP Username', 'shwcp');?></th><th><?php echo __('Access', 'shwcp');?></th>
				</tr>
			<?php
			$i = 0;
			foreach ($users as $user) { 
				$user_perm = isset($this->permission_settings['permission_settings'][$user->ID]) ? $this->permission_settings['permission_settings'][$user->ID] : 'none';
				$i++;
				?>
				<tr class="row<?php echo $i&1;?>">
					<td><?php echo $user->user_login; ?> </td>
					<td> 
						<input type="radio" name="<?php echo $this->permission_settings_key_db;?>[permission_settings][<?php echo $user->ID; ?>]" value="none" <?php checked( $user_perm, 'none');?> >
                        <?php echo __('No Access', 'shwcp');?><br />
						<input type="radio" name="<?php echo $this->permission_settings_key_db;?>[permission_settings][<?php echo $user->ID; ?>]" value="readonly" <?php checked( $user_perm, 'readonly');?> >
						<?php echo __('Read Only', 'shwcp');?><br />
						<input type="radio" name="<?php echo $this->permission_settings_key_db;?>[permission_settings][<?php echo $user->ID; ?>]" value="ownleads" <?php checked( $user_perm, 'ownleads');?>>
						<?php echo __('Manage Own Leads', 'shwcp');?><br />
						<input type="radio" name="<?php echo $this->permission_settings_key_db;?>[permission_settings][<?php echo $user->ID; ?>]" value="full" <?php checked( $user_perm, 'full');?>>
						<?php echo __('Full Access', 'shwcp');?><br />
					</td>
				</tr>
			<?php } ?>
			</table>

	 <?php }

	/*
	 * Site information field callback
	 */
	function field_section_info() { 
		?>
		<table class="wcp_site_info">
			<tr>
				<th<?php __('Variable', 'shwcp'); ?></th>
				<th><?php __('Value', 'shwcp'); ?></th>
			</tr>
			<tr><td><?php echo __('PHP version running', 'shwcp');?></td><td><?php echo phpversion(); ?></td></tr>
			<tr><td>post_max_size</td><td><?php echo ini_get('post_max_size'); ?></td></tr>
		 	<tr><td>memory_limit</td><td><?php echo ini_get('memory_limit'); ?></td> </tr>
			<tr><td>upload_max_filesize</td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
			<tr><td>max_execution_time</td><td><?php echo ini_get('max_execution_time'); ?></td></tr>
			<tr><td>max_file_uploads</td><td><?php echo ini_get('max_file_uploads'); ?></td></tr>
			<tr><td>max_input_vars</td><td><?php echo ini_get('max_input_vars'); ?></td></tr>
		</table>
	<?php }

	/*
	 * Backup DB (tab 4) ajax only
     */
	function field_backup_db() {
		$wait = SHWCP_ROOT_URL . '/assets/img/wait.gif';
	?>
		<!-- Backup Database -->
        <hr>
        <table class="wcp_backup_db">
            <tr>
              <td>
                <div class="backup-db-div left-content">
                  <input type="checkbox" class="backup-db-check" value="none" />
                        <?php echo __('Backup Database', 'shwcp');?>
                        <br />
                </div>
                <div class="confirm-button right-content">
                     <button class="backup-db-confirm button button-primary">
                     <?php echo __('Backup Database Now', 'shwcp');?></button>
                </div>
                <div class="clear-both"></div>
                <br />
                <div class="backup-message">
                <b>
                    <?php echo __('This will create a new backup of the current state of your database.', 'shwcp'); ?>
                </b>
                </div>
                <div class="backup-success">
                    <span class="success-msg">
                    <?php echo __('Database has been backed up!', 'shwcp'); ?>
                    </span>
                </div>
				<div class="backup-wait">
                    <span class="wait-msg">
                    <?php echo __('Please Wait, while the database is backed up.', 'shwcp'); ?>  <img src="<?php echo $wait; ?>" />
                    </span>
                </div>
				<div class="current-working-database" style="display:none;"><?php echo $this->db;?></div>

              </td>
            </tr>
        </table>
        <hr>

	<?php }


	/*
     * Restore DB (tab 4) ajax only
     */
    function field_restore_db() {
		$wait = SHWCP_ROOT_URL . '/assets/img/wait.gif';
    ?>
        <!-- Restore Database -->
        <hr>
        <table class="wcp_restore_db">
            <tr>
              <td>
                <div class="restore-db-div left-content">
                  <input type="checkbox" class="restore-db-check" value="none" />
                        <?php echo __('Restore Database', 'shwcp');?>
                        <br />
					<select class="restore-db-file">
						<option value="" selected="selected"><?php echo __('--SELECT--', 'shwcp'); ?></option>
					<?php
					$i = 1;
                	foreach ($this->backup_files as $k => $v) {
                    	$files_info[$i]['name'] = $v;
                    	$size = size_format(get_dirsize($this->shwcp_backup . '/' . $v));
                    	echo "<option value='$v'>$i). <span class='backup-entry'>$v</span>"
							. " $size</option>\n";
                    	$i++;
                	}
					?>
					</select>
                </div>
                <div class="confirm-button right-content">
                     <button class="restore-db-confirm button button-primary button-warning">
                     <?php echo __('Confirm Database Restore', 'shwcp');?></button>
                </div>
                <div class="clear-both"></div>
                <br />
                <div class="restore-message">
                <b>
                    <?php echo __('This option will restore a backup of your choice back into the database (and overwrite existing entry data).', 'shwcp'); ?>
                </b>
                </div>
                <div class="restore-success">
                    <span class="success-msg">
                    <?php echo __('Database has been Restored!', 'shwcp'); ?>
                    </span>
                </div>
				<div class="restore-wait">
					<span class="wait-msg">
					<?php echo __('Please Wait, while the database is restored.', 'shwcp'); ?> <img src="<?php echo $wait; ?>" />
					</span>
				</div>
              </td>
            </tr>
        </table>
        <hr>

    <?php }

	/*
	 * List current backups (tab 4) 
	 *
     */
	public function field_backups_list() {
	?>
		<div class="current-backups">
        	<ul class="backup-ul">
           		<?php
                // get sizes and dates
                $i = 1;
                foreach ($this->backup_files as $k => $v) {
                    $files_info[$i]['name'] = $v;
                    $size = size_format(get_dirsize($this->shwcp_backup . '/' . $v));
                    $file_date = date("m-d-Y H:i:s", filemtime($this->shwcp_backup . '/' . $v));
                    echo "<li>$i). <b><span class='backup-entry'>$v</span> </b> <i>$file_date</i> $size "
						 . " <a href='#' class='remove-backup'>" . __('Delete Backup', 'shwcp') . "</a></li>\n";
                    $i++;
                }
                ?>
			</ul>
        </div>	

	<?php }

	/*
	 * Reset DB to defaults (tab 4) ajax only
	 */
	function field_reset_db() {
	?>
		<!-- Restore Contacts Database -->

		<!-- Reset Contacts Database -->
		<hr>
		<table class="wcp_reset_db">
			<tr>
			  <td>
				<div class="reset-db-div left-content">
				  <input type="checkbox" class="reset-db-check" value="none" />
                       	<?php echo __('Reset Database to Defaults', 'shwcp');?>
						<br />
				</div>
				<div class="confirm-button right-content">
					 <button class="reset-db-confirm button button-primary button-warning">
				     <?php echo __('Confirm Database Reset', 'shwcp');?></button>
				</div>
				<div class="clear-both"></div>
				<br />
				<div class="reset-message">
				<b>
					<?php echo __('Warning, this action will clear all entries (and data) and restore the database to default (like a new installation).', 'shwcp'); ?>
				</b>
					<?php echo __('  Use this only if you want to start over fresh again.  Your Main Settings and Permissions will remain intact.', 'shwcp'); ?>
				</div>
				<div class="reset-success">
					<span class="success-msg">
					<?php echo __('Database has been reset to the initial state, you can now go to the frontend and start working with it again.', 'shwcp'); ?>
					</span>
				</div>
			  </td>
			</tr>
		</table>
		<hr>
	<?php }

			



	/*
	 * Called during admin_menu, adds an options
	 * page under Settings called My Settings, rendered
	 * using the plugin_options_page method.
	 */
	function add_admin_menus() {
		$plugin_options = add_menu_page( __('WP Contacts', 'shwcp'), __('WP Contacts', 'shwcp'), 'manage_options', 
				$this->plugin_options_key, array($this, 'plugin_options_page'), 
				SHWCP_ROOT_URL . '/assets/img/wcp-16.png', '27.337');

		// loaded only in our Contacts menu
		add_action( 'load-' . $plugin_options, array($this, 'load_admin_scripts') );
		// loaded in WP admin for metabox functionality
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_meta_enqueue_script' ), 10,1);
	}

	/*
	 * Submenu functionality added to our admin menu for add, delete and selecting databases
	 */
	function add_admin_submenus() {
		// get the default database name
		$default_db = get_option( $this->first_tab_key );	
		if (!isset($default_db['database_name']) || $default_db['database_name'] == '' ) {
			$default_name = __('Default Database', 'shwcp');
		} else {
			$default_name = $default_db['database_name'];
		}

		// Default
		add_submenu_page( $this->plugin_options_key, $default_name, $default_name, 
            'manage_options', $this->plugin_options_key);

		// All other databases created based on options name
		global $wpdb;
		$options_table = $wpdb->prefix . 'options';
		$option_entry = $this->first_tab_key;
		$dbs = $wpdb->get_results("SELECT * FROM $options_table WHERE `option_name` LIKE '%$option_entry%'");
		//print_r($dbs);
		foreach ($dbs as $k => $option) {
			if ($option->option_name == $option_entry) {
				// ignore since it's set above (Default)
			} else {
				$db_options = get_option($option->option_name);
				$remove_name = '/^' . $option_entry . '_/';  // Just get the database number
				$db_number = preg_replace($remove_name, '', $option->option_name);
				if (is_numeric($db_number)) {
					add_submenu_page( $this->plugin_options_key, $db_options['database_name'], $db_options['database_name'],
            			'manage_options', $this->plugin_options_key . '&db=' . $db_number, '__return_null');
				}
			}
		}

		// Add & Delete DBs
		$add_db = add_submenu_page( $this->plugin_options_key, __('Add New Database', 'shwcp'), __('Add New Database', 'shwcp'), 
			'manage_options', 'shwcp_add_db', array(&$this, 'shwcp_add_db'));
		$delete_db = add_submenu_page( $this->plugin_options_key, __('Delete Database', 'shwcp'), __('Delete Database', 'shwcp'),
			'manage_options', 'shwcp_delete_db', array(&$this, 'shwcp_delete_db'));

		// loaded only in our Contacts menu
        add_action( 'load-' . $add_db, array($this, 'load_admindb_scripts') );
		add_action( 'load-' . $delete_db, array($this, 'load_admindb_scripts') );
	}
	

	/*
	 * Submenu add database page
	 */
	function shwcp_add_db() {
		$title = __('Add New Database', 'shwcp');
		$placeholder = __('New Database', 'shwcp');
		$label = __('Unique Database Name', 'shwcp');
		$add = __('Add Database', 'shwcp');
		$submitted = isset($_GET['submitted']) ? intval($_GET['submitted']) : '';
		$description = __('Create an entirely new and separate database.  Afterwards, you can set a page to use this database instead of the default.  You can also customize all settings and configurations separately for each database.  Be sure to provide a concise and unique name for this so you can locate it correctly when assigning to a page.', 'shwcp');
		$steps = __('Steps to get this database up and running', 'shwcp');
		$step1 = __('Create the database with a unique name here', 'shwcp');
		$step2 = __('Configure initial settings and access for the database and save them!', 'shwcp');
		$step3 = __('Assign the database to a new or existing page using the WP Contacts page template', 'shwcp');
		$check_menu = __('Database has been created!  Go to the menu for WP Contacts and select your new database to start editing it.', 'shwcp');
		$added = ' style="display:none;" ';
		$disabled = '';
		if ($submitted == '1') {
			$disabled = ' style="display:none;" ';
			$added = '';
		}
		echo <<<EOC
			<div class="wrap"><div id="icon-tools" class="icon32"></div>
				<h2>$title</h2>
				<p>$description</p>
			</div>
			<hr />
			<h3>$steps</h3>
			<div class="shwcp-create-steps">
			  <ol>
				<li>$step1</li>
				<li>$step2</li>
				<li>$step3</li>
			  </ol>
			</div>
			<hr />
			<div class="shwcp-create-div" $disabled>
				<label for="shwcp-newdb-name">$label : </label>
				<input class="shwcp-newdb-name" type="text" placeholder="$placeholder" />&nbsp;&nbsp;
				<button class="button-primary add-shwcp-newdb">$add</button>
			</div>
			<div class="shwcp-db-added" $added><h3>$check_menu</h3></div>
			
EOC;
	}

	/*
	 * Submenu delete database page
	 */
	function shwcp_delete_db() {
		$steps = __('Choose the database you would like to remove and click submit to confirm', 'shwcp');
		$label = __('Delete', 'shwcp');
		$title = __('Delete Existing Database', 'shwcp');
		$select_text = __('--Select--', 'shwcp');
		$hidden = ' style="display:none"';
		$submitted = isset($_GET['submitted']) ? intval($_GET['submitted']) : '';
		$dbname = isset($_GET['dbname']) ? $_GET['dbname'] : '';
		$description = __("Delete unneeded databases.  You cannot delete the default database, but you can reset it to it's default state under the Database tab for managing it.", 'shwcp');
		$removed_text = __('Database has been removed.', 'shwcp');
		if ($submitted == '1') {
			$hidden = '';
		}
        echo <<<EOC
            <div class="wrap"><div id="icon-tools" class="icon32"></div>
                <h2>$title</h2>
				<p>$description</p>
            </div>
			<hr />
            <h3>$steps</h3>
            <hr />
            <div class="shwcp-delete-div">
                <label for="shwcp-deldb-name">$label : </label>
                <select class="shwcp-deldb-name" />
				<option value="">$select_text</option>
EOC;
		global $wpdb;
		$options_table = $wpdb->prefix . 'options';
        $option_entry = $this->first_tab_key;
        $dbs = $wpdb->get_results("SELECT * FROM $options_table WHERE `option_name` LIKE '%$option_entry%'");
		foreach($dbs as $k => $db) {
			if ($db->option_name == $this->first_tab_key) { // default database, no deleting
				// no delete
			} else {             // All user created databases
				$remove_name = '/^' . $this->first_tab_key . '_/';  // Just get the database number
	            $db_number = preg_replace($remove_name, '', $db->option_name);
				$options = get_option($db->option_name);
				echo '<option value="' . $db_number . '">' . $options['database_name'] . '</option>';
			}
		}
	
		echo <<<EOC
				</select>&nbsp;&nbsp;
                <button class="button-primary shwcp-deldb-submit">$label</button>
            </div>
            <div class="shwcp-deldb-confirm" $hidden><h3>$dbname $removed_text</h3></div>
EOC;
	}

	/* Only called on our plugin tabs page */
	function load_admin_scripts() {
		/* Can't enqueue scripts here, it's too early, so register against proper action hook first */
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
	}

	/* Only called on our add & remove database page */
 	function load_admindb_scripts() {
		/* Can't enqueue scripts here, it's too early, so register against proper action hook first */
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_db_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_db_styles' ), 10, 1 );
	}

	/**
	 * Load admin meta Javascript outside of plugin admin (for pages)
	 * @since 2.0.0
	 * @return void
	 */
	public function admin_meta_enqueue_script() {
		wp_register_script( 'wcp-admin-meta', SHWCP_ROOT_URL . '/assets/js/admin-meta.js', array( 'jquery' ), 
				SHWCP_PLUGIN_VERSION, true);
		wp_enqueue_script('wcp-admin-meta');
	}

	/**
     * Load admin Javascript.
     * @access public
     * @since 1.0.0
     * @return void
     */
    public function admin_enqueue_scripts ( $hook = '' ) {
    	wp_enqueue_media();
		wp_enqueue_script('jquery-ui-sortable');

		wp_register_script( 'wcp-admin',
		SHWCP_ROOT_URL . '/assets/js/admin.js', array( 'wp-color-picker' ), SHWCP_PLUGIN_VERSION, true );
		wp_enqueue_script( 'wcp-admin' );
		wp_localize_script(  'wcp-admin', 'WCP_Ajax_Admin', array(
        	'ajaxurl' => admin_url('admin-ajax.php'),
            'nextNonce' => wp_create_nonce( 'myajax-next-nonce' )
        ));
	} // End admin_enqueue_scripts

	/**
     * Load admin Javascript for db pages
     * @access public
     * @since 2.0.0
	 * @return void
     */
	public function admin_enqueue_db_scripts ($hook = '' ) {
		wp_register_script( 'wcp-admin-db',
		SHWCP_ROOT_URL . '/assets/js/admin-addremove-db.js', array( 'jquery' ), SHWCP_PLUGIN_VERSION, true );
		wp_enqueue_script( 'wcp-admin-db' );
		wp_localize_script( 'wcp-admin-db', 'WCP_Ajax_DB_Admin', array( 
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nextNonce' => wp_create_nonce( 'myajax-next-nonce' )
		));
	} // End admin_enqueue_db_scripts
	
	/**
	 * Load admin CSS.
     * @access public
     * @since 1.0.0
     * @return void
     */
	public function admin_enqueue_styles ( $hook = '' ) {
		// Add the color picker css file      
		wp_enqueue_style( 'wp-color-picker' );

		wp_register_style( 'wcp-admin', SHWCP_ROOT_URL . '/assets/css/admin.css', array(), SHWCP_PLUGIN_VERSION );
		wp_enqueue_style( 'wcp-admin' );
		
		// material design fonts
		wp_register_style( 'md-iconic-font', SHWCP_ROOT_URL
	         . '/assets/css/material-design-iconic-font/css/material-design-iconic-font.min.css', '1.1.1' );
	    wp_enqueue_style( 'md-iconic-font' );

	} // End admin_enqueue_styles

	/**
     * Load admin db CSS.
     * @access public
     * @since 2.0.0
     * @return void
     */
    public function admin_enqueue_db_styles ( $hook = '' ) {
		// no styles needed yet

    } // End admin_enqueue_styles


	
	/*
	 * Plugin Options page rendering goes here, checks
	 * for active tab and replaces key with the related
	 * settings key. Uses the plugin_options_tabs method
	 * to render the tabs.
	 */
	function plugin_options_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->first_tab_key_db;
		//$db = isset($_GET['db'] ) ? '_' . $_GET['db'] : '';
		//$tab_db = $tab . $db;
		?>
		<div class="wrap">
			<?php $this->plugin_options_tabs(); ?>
			<form method="post" action="options.php">
				<?php wp_nonce_field( 'update-options' ); ?>
				<?php settings_fields( $tab ); ?>
				<?php do_settings_sections( $tab ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	/*
	 * Renders our tabs in the plugin options page,
	 * walks through the object's tabs array and prints
	 * them one by one. Provides the heading for the
	 * plugin_options_page method.
	 */
	function plugin_options_tabs() {
		$db_text = __('Database', 'shwcp');
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->first_tab_key_db;
		echo '<h2 class="shwcp-database">' . $this->first_tab['database_name'] 
			. '<span class="shwcp-small-htext">' . $db_text . '</span></h2>';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key 
				. '&tab=' . $tab_key . '">' . $tab_caption . '</a>';	
		}
		echo '</h2>';
	}
};

// Initialize the plugin
add_action( 'plugins_loaded', create_function( '', '$settings_api_tabs_shwcp = new SHWCP_API_Tabs;' ) );
