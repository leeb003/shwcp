<?php
/**
  * WCP Main Class
  */

    class main_wcp {
        // properties
		protected $table_main;
		protected $table_sst;
		protected $table_log;
		protected $table_sort;
		protected $table_notes;
		protected $table_events;

		protected $field_noedit;   // non editable column
        protected $field_noremove; // non removable column list
		protected $colors;         // list of colors to rotate through for stats & calendar

		protected $allowedtags;    // allowed tags for notes with html (and anywhere else we need it)
		protected $current_user;
		protected $all_users;      // all wordpress users

		protected $curr_page;      // Current page
		protected $scheme;		   // http or https scheme

		protected $settings;
		protected $lead_count;     // total lead count
		protected $log_count;	   // total log count
		protected $first_tab;
		protected $second_tab;
		protected $frontend_settings;
		protected $can_access;
		protected $can_edit;
		protected $current_access;

		protected $shwcp_upload;
		protected $shwcp_upload_url; // upload folder url

		protected $shwcp_backup;     // backups directory
		protected $shwcp_backup_dir;

		protected $date_format = 'Y-m-d';  //Default Date and time formats
        protected $time_format = 'H:i:s';
		protected $date_format_js; // Formats for datepicker
		protected $time_format_js;

		protected $early_check = array();

        // methods
        public function __construct() {
			global $wpdb;

			// non removable fields
            $this->field_noremove = array(
                'id',
                'created_by',
                'updated_by',
                'creation_date',
                'updated_date',
				'owned_by',
				'lead_files'
            );
            // non editable fields
            $this->field_noedit = array(
                'id',
                'created_by',
                'updated_by',
                'creation_date',
                'updated_date',
				'small_image',
				'lead_files'
            );

			// Colors and highlights to rotate through
            $this->colors = array(
                1 => 'rgba(65,105,225,0.8)',
                2 => 'rgba(100,149,237,0.8)',
                3 => 'rgba(173, 216,230,0.8)',
                4 => 'rgba(240,230,140,0.8)',
                5 => 'rgba(189,183,107,0.8)',
                6 => 'rgba(143,188,143,0.8)',
                7 => 'rgba(60,179,113,0.8)',
                8 => 'rgba(255,165,0,0.8)',
                9 => 'rgba(205,92,92,0.8)',
                10 => 'rgba(160,82,45,0.8)',
                11 => 'rgba(244,67,54,0.8)',
                12 => 'rgba(255,205,210,0.8)',
                13 => 'rgba(231,67,99,0.8)',
                14 => 'rgba(207,59,96,0.8)',
                15 => 'rgba(149,117,205,0.8)',
                16 => 'rgba(126,87,194,0.8)',
                17 => 'rgba(102,187,106,0.8)',
                18 => 'rgba(67,160,71,0.8)',
                19 => 'rgba(97,97,97,0.8)',
                20 => 'rgba(66,66,66,0.8)'
            );

			$this->curr_page = isset($_GET['wcp']) ? $_GET['wcp'] : '';
			// scheme
			$this->scheme = is_ssl() ? 'https' : 'http'; // set proper protocol for admin-ajax.php calls

			// upload directory & url
			$upload_dir = wp_upload_dir();
            $this->shwcp_upload = $upload_dir['basedir'] . '/shwcp';
			$this->shwcp_upload_url = $upload_dir['baseurl'] . '/shwcp';

			// backup directory & url
			$this->shwcp_backup = $upload_dir['basedir'] . '/shwcp_backups';
			$this->shwcp_backup_url = $upload_dir['baseurl'] . '/shwcp_backups';
		}

		/**
		 * Title tag support wp4.4
		 */
		public function wcp_slug_setup() {
			add_theme_support( 'title-tag' );
		}

		/**
		 * Date & Time Format
		 * Called from load_db_options to get proper db
		 * @since 2.0.1
		 */
		public function dt_opt() {
			if (isset($this->first_tab['custom_time']) && $this->first_tab['custom_time'] == 'true') {
            	$date_format = get_option('date_format');
            	$time_format = get_option('time_format');
            	if ($date_format) {
                	$this->date_format = $date_format;
            	}
            	if ($time_format) {
                	$this->time_format = $time_format;
            	}
			}
            // Translate for datepicker
            $php_date_format = array('y', 'Y', 'F', 'm', 'd', 'j');
            $js_date_format = array('y', 'yy', 'MM', 'mm', 'dd', 'd'); // and so on
            $php_time_format = array('H', 'g', 'i', 'A', 'a');
            $js_time_format = array('HH', 'h', 'mm', 'TT', 'tt');
            $this->time_format_js = str_replace($php_time_format, $js_time_format, $this->time_format);
            $this->date_format_js = str_replace($php_date_format, $js_date_format, $this->date_format);

		}


		/**
		 * Dynamic DB and options loading
         */
		public function load_db_options($postID = '') {
			global $wpdb;
			if (!$postID) {
				$postID = $this->postid_early();
			}
			$db = '';
			$database = get_post_meta($postID, 'wcp_db_select', true);
			if ($database && $database != 'default') {
    			$db = '_' . $database;
			}

			$this->table_main     = $wpdb->prefix . SHWCP_LEADS  . $db;
            $this->table_sst      = $wpdb->prefix . SHWCP_SST    . $db;
            $this->table_log      = $wpdb->prefix . SHWCP_LOG    . $db;
            $this->table_sort     = $wpdb->prefix . SHWCP_SORT   . $db;
            $this->table_notes    = $wpdb->prefix . SHWCP_NOTES  . $db;
			$this->table_events   = $wpdb->prefix . SHWCP_EVENTS . $db;

			$this->first_tab         = get_option('shwcp_main_settings' . $db);
			$this->second_tab        = get_option('shwcp_permissions'  . $db);
			$this->frontend_settings = get_option('shwcp_frontend_settings' . $db);
			$this->dt_opt(); // Set the date formats
			// return $db for calls that need it
			return $db;
		}

		/**
		 * Early Check post id return
		 */
		public function postid_early() {
			if (is_ssl()) {
                $proto = 'https';
            } else {
                $proto = 'http';
            }
            $url = $proto . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $postid = url_to_postid($url);

            if (!$postid) { // check if this is the front page and get the id
                $url_without_query = strtok($url, '?'); // remove query string portion
                $url_without_query = rtrim($url_without_query, "/"); // remove trailing slash if present
                $site_url = get_site_url();
                if ($url_without_query == $site_url) {  // this is the front page
                    $postid = get_option( 'page_on_front' );
                }
            }
			return $postid;
		}

		/**
		 * Early Check if our template is used on this page
		 */
		public function template_early_check() {
			$template_used = false;
        	if (is_ssl()) {
            	$proto = 'https';
        	} else {
            	$proto = 'http';
        	}
        	$url = $proto . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        	$postid = url_to_postid($url);

			if (!$postid) { // check if this is the front page and get the id
				$url_without_query = strtok($url, '?'); // remove query string portion
				$url_without_query = rtrim($url_without_query, "/"); // remove trailing slash if present
				$site_url = get_site_url();
				if ($url_without_query == $site_url) {  // this is the front page
					$postid = get_option( 'page_on_front' );
				}
			}

        	// look up if template is used
        	global $wpdb;
        	$template = $wpdb->get_var($wpdb->prepare(
            	"select meta_value from $wpdb->postmeta WHERE post_id='%d' and meta_key='_wp_page_template';", $postid
        	));
			if ($template == SHWCP_TEMPLATE) {
				$template_used = true;
			}
			$database = get_post_meta($postid, 'wcp_db_select', true);
			$this->early_check['postID'] = $postid;
			$this->early_check['template_used'] = $template_used;
			$this->early_check['database'] = $database;
			return $this->early_check;
    	}

		/**
		 * Get the current user info
		 *
		 */
		public function get_the_current_user() {
			$this->current_user = wp_get_current_user();

			// access and permissions
            // permissions are none, readonly, ownleads, full, notset
			// We now have custom roles as well
            $this->current_access = isset($this->second_tab['permission_settings'][$this->current_user->ID]) 
				? $this->second_tab['permission_settings'][$this->current_user->ID] : 'notset';
            $wcp_public = isset($this->first_tab['page_public']) ? $this->first_tab['page_public'] : 'false';
            $this->can_access = false;
			$this->can_edit = false;
            if ($wcp_public == 'true') {
                $this->can_access = true;
            } elseif ( is_user_logged_in() ) {
                if ( $this->current_access != 'none'
					&& $this->current_access != 'notset'
				) {
                    $this->can_access = true;
                }
            }

            if ($this->current_access == 'full' || $this->current_access == 'ownleads') {
                $this->can_edit = true;
            }
		}

		/**
		 * Get Custom roles info if it's set
		 * returns array access name or false and perms 
		 */
		public function get_custom_role() {
			$custom = array();
			$current_user = wp_get_current_user();
			$custom['access'] = isset($this->second_tab['permission_settings'][$this->current_user->ID])
			? $this->second_tab['permission_settings'][$this->current_user->ID] : false;
			if ($custom['access'] == 'none'
				|| $custom['access'] == 'readonly' 
				|| $custom['access'] == 'ownleads'
				|| $custom['access'] == 'full'
				|| $custom['access'] == 'notset'
			) {
				$custom['access'] = false;
			} else {
				$custom_roles = isset($this->second_tab['custom_roles']) ? $this->second_tab['custom_roles'] : array();
				foreach ($custom_roles as $k => $v) {
					if ($v['unique'] == $custom['access']) {
						$custom['perms']  = $custom_roles[$k];
					}
				}
			}
			return $custom;
		}

		/**
		 * Get the lead count total
		 * called after current_user established to retain count for users
		 */
		public function get_lead_count() {
			// Total lead count
			global $wpdb;
            $ownleads = '';
            if ($this->current_access == 'ownleads') {
                $ownleads = 'WHERE owned_by=\'' . $this->current_user->user_login . '\'';
            }
            $this->lead_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_main $ownleads" );
			return $this->lead_count;
		}

		/**
		 * Get all WordPress users
		 */
		public function get_all_users() {
			$this->all_users =  get_users();
		}

		/**
		 * Get wcp users only
		 * compare with WP user list and get users with access
		 * @since 2.0.3
		 * @return array
		 */
		public function get_all_wcp_users() {
			$users = get_users();
			$wcp_users = $this->second_tab['permission_settings'];
			$all_wcp_users = array();
			foreach ($users as $user) {
				if (array_key_exists($user->ID, $wcp_users) && $wcp_users[$user->ID] != 'none') {
					$all_wcp_users[$user->ID] = $user;
				}
			}
			return $all_wcp_users;
		}

		/**
		 * Create image and file subdirectory
		 * @access public
		 * @since 1.0.0
		 * @return void
		*/
		public function shwcp_upload_directory() {
			if ( !file_exists($this->shwcp_upload) ) {
				wp_mkdir_p( $this->shwcp_upload );
			}
		}

	} // end class
