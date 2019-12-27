<?php
/**
 * WCP Class for frontend display, extends main class
 */

    class wcp_front extends main_wcp {
        // properties

		protected $head_section;  // top content
		protected $main_section;  // main content
		protected $bottom_section; // bottom content

        // methods

		public function front_init () {
			global $wpdb;
			// Frontend logged in ajax
			require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-ajax.php');
			$wcp_ajax = new wcp_ajax();
			add_action( 'wp_ajax_ajax-wcpfrontend', array($wcp_ajax, 'myajax_wcpfrontend_callback'));
			add_action( 'wp_ajax_nopriv_ajax-wcpfrontend', array($wcp_ajax, 'nopriv_wcpfrontend_callback'));
			// Ajax Login
			add_action('wp_ajax_nopriv_ajaxlogin', array($wcp_ajax, 'ajax_login') );

			require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-export.php');
            $wcp_export = new wcp_export();
			add_action( 'admin_post_wcpexport', array($wcp_export, 'wcpexport_callback'));
			add_action( 'admin_post_nopriv_wcpexport', array($wcp_export, 'wcpexport_callback'));

			// Load frontend JS & CSS
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 9999 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 9999 );
			// Dynamic CSS
			add_action('wp_ajax_dynamic_css', array($this, 'dynamic_css'));
			add_action('wp_ajax_nopriv_dynamic_css', array($this, 'dynamic_css'));
			add_filter( 'the_content', array($this, 'wcp_content_filter') );

			// WordPress Admin Bar
			add_action( 'init', array($this, 'wp_bar_show'));
		}

		/**
		 * Load Admin Bar or Not
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function wp_bar_show() {
			$this->load_db_options();
			if (!isset($this->first_tab['show_admin'])) { // Options not saved yet
				// do nothing
				return;
			}
			if ($this->first_tab['show_admin'] == 'false') {
				$early_check = $this->template_early_check();
				$template_used = $early_check['template_used'];
				if ($template_used) {
                	add_filter('show_admin_bar', '__return_false');
				}
            }
		}

        /**
         * Load frontend CSS.
         * @access public
         * @since 1.0.0
         * @return void
         */
        public function enqueue_styles () {
			if (is_page_template(SHWCP_TEMPLATE) ) { // enqueue only for our template
				// deregister all styles first
				global $wp_styles;
    			$wp_styles->queue = array();

				wp_enqueue_style('dashicons');
				wp_enqueue_style('admin-bar');

				// material design fonts
				wp_register_style( 'md-iconic-font', SHWCP_ROOT_URL 
					. '/assets/css/material-design-iconic-font/css/material-design-iconic-font.min.css', '1.1.1' );
				wp_enqueue_style( 'md-iconic-font' );

                // Bootstrap style
                wp_register_style( 'bs-modals', SHWCP_ROOT_URL . '/assets/js/bs-modals-only/css/bootstrap.css', '3.3.0' );
                wp_enqueue_style( 'bs-modals' );
				wp_register_style( 'bs-grid', SHWCP_ROOT_URL . '/assets/css/grid12.css', '3.2.4');
				wp_enqueue_style( 'bs-grid' );

				// google font
				wp_register_style( 'googleFont-Roboto', '//fonts.googleapis.com/css?family=Roboto' );
				wp_enqueue_style( 'googleFont-Roboto' );

				// main css
                wp_register_style( 'wcp-frontend', SHWCP_ROOT_URL . '/assets/css/frontend.css',
                array(), SHWCP_PLUGIN_VERSION );
                wp_enqueue_style( 'wcp-frontend' );

				// drawer css
				wp_register_style( 'pure-drawer', SHWCP_ROOT_URL . '/assets/css/pure-drawer.css', '1.0.1' );
				wp_enqueue_style( 'pure-drawer' );

				// mprogress
				wp_register_style( 'shwcp-mprogress', SHWCP_ROOT_URL . '/assets/css/mprogress.css', '1' );
				wp_enqueue_style( 'shwcp-mprogress' );

				// datepicker
				wp_register_style( 'datepicker', SHWCP_ROOT_URL . '/assets/css/datepicker.css', '1' );
				wp_enqueue_style( 'datepicker' );

				// ie9
				wp_register_style('shwcp-ie9', SHWCP_ROOT_URL . '/assets/css/ie9.css');
				wp_enqueue_style('shwcp-ie9');
				$wp_styles->add_data('shwcp-ie9', 'conditional', 'IE 9');

				// fullcalendar.css
                if ('events' == $this->curr_page) { // Event calendar view
					wp_enqueue_style( 'wp-color-picker' );
					wp_register_style('fullcalendar', SHWCP_ROOT_URL . '/assets/css/fullcalendar.css');
					wp_enqueue_style('fullcalendar');
				}
				/* not ready yet, need to purchase license as well
				// introjs for demo site
				$url = $_SERVER['HTTP_HOST'];
				if ( ($url == 'demo.sh-themes.com' || $url == 'php56host.com')
					&& is_user_logged_in() 
				) {

					wp_register_style('introjs', SHWCP_ROOT_URL . '/assets/css/introjs/introjs.css');
					wp_enqueue_style( 'introjs');
					wp_register_style('introjs-theme', SHWCP_ROOT_URL . '/assets/css/introjs/introjs-nassim.css');
					wp_enqueue_style( 'introjs-theme');
				}
				*/
				// dynamic css
				global $post; // send post id for database style selection
				wp_register_style('dynamic-css', 
					admin_url('admin-ajax.php', $this->scheme)
						.'?action=dynamic_css&postid=' . $post->ID, 'wcp-frontend', SHWCP_PLUGIN_VERSION );
				wp_enqueue_style('dynamic-css');

				// rtl support
				if (is_rtl()) {
					wp_register_style('shwcp-rtl', SHWCP_ROOT_URL . '/assets/css/shwcp-rtl.css');
					wp_enqueue_style('shwcp-rtl');
				}
            }
        } // End enqueue_styles ()

		// Load our dynamic php stylesheet
		public function dynamic_css() {
			require_once(SHWCP_ROOT_PATH . '/assets/css/frontdynam.css.php');
			exit();
		}

        /**
         * Load frontend Javascript.
         * @access public
         * @since 1.0.0
         * @return void
         */
        public function enqueue_scripts () {
			if (is_page_template(SHWCP_TEMPLATE) ) { // enqueue only for our template
				
				// deregister all other scripts
                global $wp_scripts;
                $wp_scripts->queue = array();

				global $post; // send post id for database style selection

				// load db
				$this->load_db_options($post->ID);

				// troubleshooting header loading scripts
				$load_footer = true;  // default load in footer is true
				if (isset($this->first_tab['troubleshoot']) && $this->first_tab['troubleshoot'] == 'true') {
					$load_footer = false;  // load scripts in head
				}

				// Add necessary jQuery ui libs
                wp_enqueue_script( 'jquery-ui-tabs' );
                wp_enqueue_script('jquery-ui-sortable');
                wp_enqueue_script('jquery-effects-highlight');
				wp_enqueue_script('jquery-ui-slider');
				wp_enqueue_script('jquery-ui-datepicker');
                wp_enqueue_script('plupload-all');

				// timepicker datepicker addon
				wp_register_script('timepicker', SHWCP_ROOT_URL . '/assets/js/jquery-ui-timepicker-addon.min.js', 
					array( 'jquery-ui-datepicker' ), '1.6.1', $load_footer);
				wp_enqueue_script('timepicker');

				// rateit rating plugin
				wp_register_script('rateit', SHWCP_ROOT_URL . '/assets/js/jquery.rateit.min.js',
					array( 'jquery' ), '1.0.22', $load_footer);
				wp_enqueue_script('rateit');

				// touch punch
				wp_register_script('touch-punch', SHWCP_ROOT_URL . '/assets/js/jquery.ui.touch-punch.js', 
					array( 'jquery' ), '0.2.3', $load_footer);
				wp_enqueue_script( 'touch-punch' );

				// tiny mce
				wp_enqueue_script('tiny_mce', includes_url() . 'js/tinymce/tinymce.min.js', '');

				// chart.js
                if ('stats' == $this->curr_page) { // Statistics page load charts
                    wp_register_script( 'chartjs', SHWCP_ROOT_URL . '/assets/js/Chart.min.js', array( 'jquery' ), '1.0.2', $load_footer);
                    wp_enqueue_script( 'chartjs' );
                }

				// fullcalendar.js
				if ('events' == $this->curr_page) { // Event calendar view
					// Color picker loading
					wp_enqueue_script('iris', admin_url( 'js/iris.min.js', $this->scheme ),
        				array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),false,1);
    				wp_enqueue_script('wp-color-picker', admin_url( 'js/color-picker.min.js', $this->scheme ),
        				array( 'iris' ),false,1);
					$colorpicker_l10n = array(
        				'clear' => __( 'Clear', 'shwcp' ),
        				'defaultString' => __( 'Default', 'shwcp' ),
        				'pick' => __( 'Select Color', 'shwcp' )
    				);
    				wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n ); 

					wp_register_script( 'moment', SHWCP_ROOT_URL . '/assets/js/moment.min.js', 
							array( 'jquery'), '2.9.0', $load_footer);
					wp_enqueue_script('moment');
					wp_register_script( 'fullcalendar', SHWCP_ROOT_URL . '/assets/js/fullcalendar.min.js', 
						array( 'moment'), '2.5.0', $load_footer);
					wp_enqueue_script( 'fullcalendar' );
					wp_register_script( 'calendarpage', SHWCP_ROOT_URL . '/assets/js/calendarpage.js',
						array( 'fullcalendar'), '', $load_footer);
					wp_enqueue_script( 'calendarpage' );
					wp_localize_script( 'calendarpage', 'WCP_Cal_Ajax', array(
						'ajaxurl' => admin_url('admin-ajax.php', $this->scheme),
                    	'nextNonce' => wp_create_nonce( 'myajax-next-nonce' ),
                    	'contactsColor' => isset($this->first_tab['page_color'])?$this->first_tab['page_color']:'#03a9f4',
						'frontUrl' => get_permalink(),
                    	'postID' => $post->ID,
						'dateFormat' => $this->date_format_js,
                    	'timeFormat' => $this->time_format_js,
						'monthNames'  => array(
                            __('January', 'shwcp'),
                            __('February', 'shwcp'),
                            __('March', 'shwcp'),
                            __('April', 'shwcp'),
                            __('May', 'shwcp'),
                            __('June', 'shwcp'),
                            __('July', 'shwcp'),
                            __('August', 'shwcp'),
                            __('September', 'shwcp'),
                            __('October', 'shwcp'),
                            __('November', 'shwcp'),
                            __('December', 'shwcp')
                        ),
                        'monthNamesShort' => array(
                            __("Jan", 'shwcp'),
                            __("Feb", 'shwcp'),
                            __("Mar", 'shwcp'),
                            __("Apr", 'shwcp'),
                            __("May", 'shwcp'),
                            __("Jun", 'shwcp'),
                            __("Jul", 'shwcp'),
                            __("Aug", 'shwcp'),
                            __("Sep", 'shwcp'),
                            __("Oct", 'shwcp'),
                            __("Nov", 'shwcp'),
                            __("Dec", 'shwcp')
                        ),
                        'dayNames' => array(
                            __("Sunday", 'shwcp'),
                            __("Monday", 'shwcp'),
                            __("Tuesday", 'shwcp'),
                            __("Wednesday", 'shwcp'),
                            __("Thursday", 'shwcp'),
                            __("Friday", 'shwcp'),
                            __("Saturday", 'shwcp')
                        ),
                        'dayNamesShort' => array(
                            __("Sun", 'shwcp'),
                            __("Mon", 'shwcp'),
                            __("Tue", 'shwcp'),
                            __("Wed", 'shwcp'),
                            __("Thu", 'shwcp'),
                            __("Fri", 'shwcp'),
                            __("Sat", 'shwcp')
                        ),
						'today' => __("Today", 'shwcp'),
						'month' => __("Month", 'shwcp'),
						'week'  => __('Week', 'shwcp'),
						'day'   => __('Day', 'shwcp'),
					));
				}

				wp_enqueue_script('select2', SHWCP_ROOT_URL . '/assets/js/select2.min.js', array('touch-punch'), '4.0.5' );

                wp_register_script( 'wcp-frontend',
                    SHWCP_ROOT_URL . '/assets/js/frontend.js', array( 'touch-punch' ), SHWCP_PLUGIN_VERSION, $load_footer );
                wp_enqueue_script( 'wcp-frontend' );
				wp_localize_script( 'wcp-frontend', 'WCP_Settings', array(
					'fixed_edit' => isset($this->first_tab['fixed_edit'])?$this->first_tab['fixed_edit']: 'false',
					'all_fields' => isset($this->first_tab['all_fields'])?$this->first_tab['all_fields']: 'false',
					'postID' => $post->ID,
				));

				wp_register_script( 'wcp-frontend-ajax',
					SHWCP_ROOT_URL . '/assets/js/frontend-ajax.js', array( 'wcp-frontend', 'plupload-all', 'touch-punch' ), 
					SHWCP_PLUGIN_VERSION, $load_footer );
				wp_enqueue_script( 'wcp-frontend-ajax' );
                wp_localize_script(  'wcp-frontend-ajax', 'WCP_Ajax', array(
                    'ajaxurl'       => admin_url('admin-ajax.php', $this->scheme),
                    'nextNonce'     => wp_create_nonce( 'myajax-next-nonce' ),
					'contactsColor' => isset($this->first_tab['page_color'])?$this->first_tab['page_color']:'#03a9f4',
					'fixed_edit'    => isset($this->first_tab['fixed_edit'])?$this->first_tab['fixed_edit']: 'false',
					'indColumns'    => isset($this->frontend_settings['ind_columns'])?$this->frontend_settings['ind_columns']: '4',
					'postID'        => $post->ID,
					'dateFormat'    => $this->date_format_js,
					'timeFormat'    => $this->time_format_js,

					'datepickerVars'  => array(
						'closeText'   => __('Close', 'shwcp'),
						'prevText'    => __('Prev', 'shwcp'),
						'nextText'    => __('Next', 'shwcp'),
						'currentText' => __('Current', 'shwcp'),
						'monthNames'  => array(
							__('January', 'shwcp'), 
							__('February', 'shwcp'), 
							__('March', 'shwcp'), 
							__('April', 'shwcp'), 
							__('May', 'shwcp'), 
							__('June', 'shwcp'), 
							__('July', 'shwcp'), 
							__('August', 'shwcp'), 
							__('September', 'shwcp'), 
							__('October', 'shwcp'), 
							__('November', 'shwcp'), 
							__('December', 'shwcp')
						),
						'monthNamesShort' => array(
							__("Jan", 'shwcp'),
							__("Feb", 'shwcp'),
							__("Mar", 'shwcp'),
							__("Apr", 'shwcp'),
							__("May", 'shwcp'),
							__("Jun", 'shwcp'),
							__("Jul", 'shwcp'),
							__("Aug", 'shwcp'),
							__("Sep", 'shwcp'),
							__("Oct", 'shwcp'),
							__("Nov", 'shwcp'),
							__("Dec", 'shwcp')
						),
						'dayNames' => array(
							__("Sunday", 'shwcp'),
							__("Monday", 'shwcp'),
							__("Tuesday", 'shwcp'),
							__("Wednesday", 'shwcp'),
							__("Thursday", 'shwcp'),
							__("Friday", 'shwcp'),
							__("Saturday", 'shwcp')
						),
						'dayNamesShort' => array(
							__("Sun", 'shwcp'),
							__("Mon", 'shwcp'),
							__("Tue", 'shwcp'),
							__("Wed", 'shwcp'),
							__("Thu", 'shwcp'),
							__("Fri", 'shwcp'),
							__("Sat", 'shwcp')
						),
						'dayNamesMin' => array(
							__("Su", 'shwcp'),
							__("Mo", 'shwcp'),
							__("Tu", 'shwcp'),
							__("We", 'shwcp'),
							__("Th", 'shwcp'),
							__("Fr", 'shwcp'),
							__("Sa", 'shwcp')
						),
						'weekHeader' => __('He', 'shwcp'),
					),
					'timepickerVars' => array (
						'timeOnlyTitle' => __('Time', 'shwcp'),
						'timeText'      => __('Time', 'shwcp'),
						'hourText'      => __('Hour', 'shwcp'),
						'minuteText'    => __('Minute', 'shwcp'),
						'secondText'    => __('Second', 'shwcp'),
						'millisecText'  => __('Millisecond', 'shwcp'),
						'timezoneText'  => __('Time Zone', 'shwcp'),
						'currentText'   => __('Now', 'shwcp'),
						'closeText'     => __('Close', 'shwcp'),
						'amNames'       => array(
							__('AM', 'shwcp'), __('A', 'shwcp')
						),
						'pmNames' => array(
							__('PM', 'shwcp'), __('P', 'shwcp')
						)
					)
						
                ));

				// login script
                if (!is_user_logged_in()) {
					$launch_login = 'false';
                	if ($this->first_tab['page_public'] == 'false' && !is_user_logged_in() ) {
                    	$launch_login = 'launch';
               	 	}
                    wp_register_script('ajax-login-script', SHWCP_ROOT_URL . '/assets/js/login-script.js', 
							array('jquery'), SHWCP_PLUGIN_VERSION, $load_footer );
                    wp_enqueue_script('ajax-login-script');
					wp_localize_script( 'wcp-frontend-ajax', 'ajax_login_object', array(
						'ajaxurl' => admin_url( 'admin-ajax.php', $this->scheme ),
						'launchLogin' => $launch_login,
						//'redirecturl' => add_query_arg( array('t' => time()), get_permalink() ),
						'redirecturl' => get_permalink(),
        				'loadingmessage' => __('Sending user info, please wait...', 'shwcp')
    				));
                }

                // Bootstrap 3 Modals
                wp_register_script( 'bootstrap-modals', SHWCP_ROOT_URL . '/assets/js/bs-modals-only/js/bootstrap.js',
                    array( 'jquery' ), '3.3.0', $load_footer );
                wp_enqueue_script( 'bootstrap-modals' );

				// mprogress
				wp_register_script( 'shwcp-mprogress', SHWCP_ROOT_URL . '/assets/js/mprogress.js', array( 'jquery' ), '1', $load_footer);
				wp_enqueue_script( 'shwcp-mprogress' );

				/* Not ready yet
				// introjs for guided tour
				$url = $_SERVER['HTTP_HOST'];
				if (($url == 'demo.sh-themes.com' || $url == 'php56host.com') 
					&& is_user_logged_in()
				) {
					wp_register_script( 'introjs', SHWCP_ROOT_URL . '/assets/js/introjs/intro.js', array('shwcp-mprogress' ), '2.4.0', $load_footer);
					wp_enqueue_script( 'introjs' );
					wp_register_script( 'introjs-config', SHWCP_ROOT_URL . '/assets/js/introjs/intro-config.js', array('introjs' ), '2.4.0', $load_footer);
                    wp_enqueue_script( 'introjs-config' );
				}
				*/
            }
        } // End enqueue_scripts 

		/**
		 * Load the frontend page content
		 */
		public function wcp_content_filter($content) {
			$db = $this->load_db_options(); // load db and get _db
			// if it's not our page template, just return the original content
			if (!is_page_template(SHWCP_TEMPLATE) ) { 
				return $content; 
			}
			
			global $wpdb;
			// custom access role
			$custom_role = $this->get_custom_role();

            // Total lead count
			$ownleads = '';
            if ($this->current_access == 'ownleads') {
                $ownleads = 'WHERE owned_by=\'' . $this->current_user->user_login . '\'';
            }
			$lead_count = $this->get_lead_count();
            $search_select = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_ind_number asc");  // Searching

			$wcp_settings = $this->first_tab; //set in main
			$wcp_permissions = $this->second_tab; //set in main

			// Menu translation
			$home_text = $this->first_tab['database_name'];
			$home_menu = $home_text;
			$home = $home_text;

			$dd_filter = false; // check for Dropdown filters as well
			foreach($_GET as $key=>$value){
  				if("extra_column_" == substr($key,0,13)
					|| "l_source" == substr($key,0,8)
					|| "l_status" == substr($key,0,8)
					|| "l_type" == substr($key,0,6) 
				) {
					$dd_filter = true;
  				}
			}

			if ( !isset($_GET['wcp']) || $_GET['wcp'] == 'logging') {  // only for main view and logs
				if( isset($_GET['wcp_search']) 
					|| isset($_GET['sort'])
					|| isset($_GET['field'])
					|| isset($_GET['wcp_search'])
					|| $dd_filter
				) {	
					$home = __('Clear Filters', 'shwcp');
				}
			}
			$main_page        = __('All Entries', 'shwcp');
			$settings_link    = __('Settings', 'shwcp');
			$page_front       = __('Manage Front Page', 'shwcp');
			$page_front_arg   = add_query_arg( array('wcp' => 'frontsort'), get_permalink() );
			$man_ind          = __('Manage Individual Page', 'shwcp');
			$man_ind_arg      = add_query_arg( array('wcp' => 'man-ind'), get_permalink() );
			$page_fields      = __('Manage Fields', 'shwcp');
			$page_fields_arg  = add_query_arg( array('wcp' => 'fields'), get_permalink() );
			$page_entry       = __('Individual Entry', 'shwcp');
			$page_entry_arg   = add_query_arg( array('wcp' => 'entry'), get_permalink() );
			$page_export      = __('Import & Export', 'shwcp');
			$page_export_arg  = add_query_arg( array('wcp' => 'ie'), get_permalink() );
			$page_stats       = __('Statistics', 'shwcp');
			$page_stats_arg   = add_query_arg( array('wcp' => 'stats'), get_permalink() );
			$page_logging     = __('Logging', 'shwcp');
			$page_logging_arg = add_query_arg( array('wcp' => 'logging'), get_permalink() );
			$page_events      = __('Events', 'shwcp');
			$page_events_arg  = add_query_arg( array('wcp' => 'events'), get_permalink() );
			$permalink        = get_permalink();

			// Login Form and link variables
			$login_nonce = wp_nonce_field( 'ajax-login-nonce', 'security', true, false);
            $lost_password = wp_lostpassword_url();
            $username_text = __('Username', 'shwcp');
            $password_text = __('Password', 'shwcp');
            $lost_password_text = __('Lost Your Password?', 'shwcp');
            $logout_url = wp_logout_url( get_permalink());
            $logout_url_text = __('Logout', 'shwcp');
			$current_user_login = wp_get_current_user();
			$current_user_login = $current_user_login->user_login;
            $login_text = __('Login', 'shwcp');
            $default_text = isset($this->first_tab['page_greeting']) ? $this->first_tab['page_greeting'] : '';

			$bar_tools = '';
			$total_leads = '';
			$total_leads_text = '';

        	$wcp_main = '';

			$login_link = '';
			if (is_user_logged_in()) {
                $login_link .= <<<EOC
	<div class="login-link">
		<i class="login-icon md-person-outline wcp-white"></i>
       	<p class="logged-in-as">$current_user_login</p><a class="login_button" href="$logout_url">$logout_url_text</a>
	</div>
EOC;
            } else {
                $login_link .= <<<EOC
	<div class="login-link">
		<i class="login-icon md-person-outline wcp-white"></i>
       	<a class="login_button" id="show_login" href="">$login_text</a>
	</div>
EOC;
            }


			if ($this->can_access ) {  // general access to the leads
        		if ('frontsort' == $this->curr_page) {  // Front fields sorting
					require_once(SHWCP_ROOT_PATH  . '/includes/class-wcp-front-manage.php');
					$wcp_front_manage = new wcp_front_manage;
					$this->main_section = apply_filters('wcp_front_sort_filter', $wcp_front_manage->get_front_sorting());
        		} elseif ('fields' == $this->curr_page) { // Field setup
					require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-fields.php');
					$wcp_fields = new wcp_fields();
					$this->main_section = apply_filters('wcp_edit_fields_filter', $wcp_fields->get_edit_fields());
				} elseif ('man-ind' == $this->curr_page) { // Individual Page Management
					require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-manage-ind.php');
                    $wcp_ind_manage = new wcp_ind_manage();
                    $this->main_section = apply_filters('wcp_ind_manage_filter', $wcp_ind_manage->manage_individual());
				} elseif ('entry' == $this->curr_page) { // Individual Lead page
					require_once(SHWCP_ROOT_PATH  . '/includes/class-wcp-individual.php');
					$wcp_individual = new wcp_individual();
					$this->main_section = apply_filters('wcp_individual_filter', $wcp_individual->get_individual($db));
					$lead_id = intval($_GET['entry']);
					$bar_tools = $this->top_search($search_select);
				} elseif ('ie' == $this->curr_page) { // Import Export page
					require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-ie.php');
					$wcp_ie = new wcp_ie();
					$this->main_section = apply_filters('wcp_ie_filter', $wcp_ie->import_export());
				} elseif ('stats' == $this->curr_page) { // Statistics
					require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-stats.php');
					$wcp_stats = new wcp_stats();
					$this->main_section = apply_filters('wcp_stats_filter', $wcp_stats->load_statistics());
				} elseif ('logging' == $this->curr_page) { // Logging
					require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-logging.php');
					$wcp_logging = new wcp_logging();
					$this->main_section = apply_filters('wcp_logging_filter', $wcp_logging->log_entries());
					$bar_tools = '<div class="bar-tools">'
						. '<div class="log-search">'
                        . '<input class="log-search-input" placeholder="' . __('Search', 'shwcp') . '" type="search" value="" />'
                        . '</div>'
						. '<div class="remove-holder"><i class="remove-all-logs wcp-white wcp-sm md-remove-circle-outline" title="'
                        . __('Remove all log entries', 'shlcm') . '"> </i></div></div>';
				} elseif ('events' == $this->curr_page) { // Events
					require_once(SHWCP_ROOT_PATH  . '/includes/class-wcp-events.php');
					$wcp_events = new wcp_events();
					$this->main_section = apply_filters('wcp_events_filter', $wcp_events->show_events());
					if ( ($this->can_edit) 
						|| ($custom_role['access'] && $custom_role['perms']['access_events'] == 'addedit')		
					) {
						$bar_tools = '<div class="bar-tools">'
								. '<i class="add-edit-event wcp-white wcp-sm md-add" title="' . __('Add New Event', 'shwcp')
								. '"> </i></div>';
					}
				} else { // get the default view
					$bar_tools = $this->top_search($search_select);
					require_once(SHWCP_ROOT_PATH . '/includes/class-allleads.php');
                	$all_leads = new allleads();
					$this->main_section = apply_filters('wcp_all_leads_filter', $all_leads->get_all_leads());
				}

			} else {
                // not public, no access
				$no_access_text =  __('You do not have permission to access this page.', 'shwcp');
                $no_access = <<<EOC
					<div class="row">
						<div class="col-md-2"></div>
							<div class="col-md-8">
								<span class="no-access">$no_access_text</span>
							</div>
							<div class="col-md-2"></div>
						</div>
EOC;
                $this->main_section = $no_access;

            }


			$breadcrumbs = '<a href="' . $permalink . '">' . $home . '</a>';
			if (isset($this->curr_page) && $this->curr_page != '') {
				if ($this->curr_page == 'logging'  
					&& isset($_GET['wcp_search'])  // Logging page reset link for searches
				) {
					$reset_link = remove_query_arg( array('pages', 'wcp_search', 'q'));
					$breadcrumbs = '<a href="' . $reset_link . '">' . $home . '</a>';
				} else {
					if ($this->curr_page == 'fields') {
						$bread_single = $page_fields;
					} elseif ($this->curr_page == 'frontsort') {
						$bread_single = $page_front;
					} elseif ($this->curr_page == 'man-ind') {
						$bread_single = $man_ind;
					} elseif ($this->curr_page == 'entry') {
						$bread_single = $page_entry;
					} elseif ($this->curr_page == 'ie') {
						$bread_single = $page_export;
					} elseif ($this->curr_page == 'stats') {
						$bread_single = $page_stats;
					} elseif ($this->curr_page == 'logging') {
						$bread_single = $page_logging;
					} elseif ($this->curr_page == 'events') {
						$bread_single = $page_events;
					} else {
						$bread_single = __('Unknown', 'shwcp');
					}
					if (is_rtl()) {
						$breadcrumbs = ucfirst($bread_single) . ' < ' . $breadcrumbs;
					} else {
						$breadcrumbs = $breadcrumbs . ' > ' . ucfirst($bread_single);
					}
				}
			}
			if (isset($wcp_settings['logo_attachment_url']) && $wcp_settings['logo_attachment_url'] != '') {
				$logo_text = __('Logo', 'shwcp');
				$logo = '<img class="img-responsive" src="' . $wcp_settings['logo_attachment_url'] . '" alt="' . $logo_text . '"/>';
			} else {
				$logo = "";
			}
			// Head section and drawer	
			// test for demo site and preload demo login values
			$wcp_links = '';
			$url = $_SERVER['HTTP_HOST'];
			$demo_creds = '';
			if ($url == 'demo.sh-themes.com' || $url == 'demo.wpcontacts.co') {
				$demo_creds = 'value="demo"';
			}
			$user_ID = $this->current_user->ID;
			$this->head_section = <<<EOC
	<form id="login" action="login" method="post">
		<div class="login-form">
    		<div class="input-field">
				<i class="wcp-md md-perm-identity"> </i>
				<label for="username">$username_text</label>
    			<input id="username" class="login-username" $demo_creds type="text" name="username">
			</div><br />
			<div class="input-field">
				<i class="wcp-md md-lock-outline"> </i>
    			<label for="password">$password_text</label>
    			<input id="password" class="login-password" $demo_creds type="password" name="password">
			</div>
			<div class="row">
				<div class="col-md-8">
					<p class="status">$default_text</p>
				</div>
				<div class="col-md-4 text-right">
    				<p><a class="lost" href="$lost_password">$lost_password_text</a></p>
				</div>
			</div>
    		$login_nonce
		</div>
	</form>
	<div class="wcp-page page-container wcp-access-$this->current_access wcp-user-$user_ID">

		<div class="pure-container" data-effect="pure-effect-slide">
            	<input type="checkbox" id="pure-toggle-left" class="pure-toggle" data-toggle="left"/>
            	<label class="pure-toggle-label" for="pure-toggle-left" data-toggle-label="left">
					<span class="pure-toggle-icon"></span>
				</label>
            	<nav class="pure-drawer" data-position="left">
					<div class="wcp-logo">$logo</div>
					<ul class="drawer-menu">
						<li><a href="$permalink">$home_menu <i class="wcp-md md-home"></i></a></li>
EOC;

			if ($this->current_access == 'full') {
				$wcp_links .= <<<EOC
						<li><a href="#" class="wcp-submenu">$settings_link<i class="wcp-md md-dashboard"></i></a>
						  <ul class="wcp-dropdown">
						    <li><a href="$page_front_arg">$page_front</a></li>
						    <li><a href="$page_fields_arg">$page_fields</a></li>
							<li><a href="$man_ind_arg">$man_ind</a></li>
						  </ul>
						</li>
EOC;
			/* Custom Access Settings */
			} elseif ($custom_role['access']) { 
				if ($custom_role['perms']['manage_front'] == 'yes'
					|| $custom_role['perms']['manage_fields'] == 'yes'
					|| $custom_role['perms']['manage_individual'] == 'yes'
				) {
					$wcp_links .= <<<EOC
						<li><a href="#" class="wcp-submenu">$settings_link<i class="wcp-md md-dashboard"></i></a>
						  <ul class="wcp-dropdown">
EOC;
					if ($custom_role['perms']['manage_front'] == 'yes') {
						$wcp_links .=  '<li><a href="' . $page_front_arg . '">' . $page_front . '</a></li>';
					}
					if ($custom_role['perms']['manage_fields'] == 'yes') {
						$wcp_links .= '<li><a href="' . $page_fields_arg . '">' . $page_fields . '</a></li>';
					}
					if ($custom_role['perms']['manage_individual'] == 'yes') {
						$wcp_links .= '<li><a href="' . $man_ind_arg . '">' . $man_ind . '</a></li>';
					}
					$wcp_links .= <<<EOC
						  </ul>
						</li>
EOC;
				}
			}
				
			if ($this->current_access == 'full'
				|| $this->current_access == 'ownleads') {
				$wcp_links .= <<<EOC
						<li class="statslink"><a href="$page_stats_arg">$page_stats <i class="wcp-md md-trending-up"></i></a></li>
EOC;
			/* Custom Access stats */
			} elseif ($custom_role['access']) {
				if ($custom_role['perms']['access_statistics'] == 'all'
					|| $custom_role['perms']['access_statistics'] == 'own'
				) {
					$wcp_links .= <<<EOC
                        <li class="statslink"><a href="$page_stats_arg">$page_stats <i class="wcp-md md-trending-up"></i></a></li>
EOC;
				}
			}

			if ($this->current_access == 'full') {
                $wcp_links .= <<<EOC
						<li class="logginglink"><a href="$page_logging_arg">$page_logging <i class="wcp-md md-verified-user"></i></a></li>
EOC;
			/* Custom Access Logging */
			} elseif ($custom_role['access'] && $custom_role['perms']['access_logging'] == 'yes') {
				$wcp_links .= <<<EOC
                        <li class="logginglink"><a href="$page_logging_arg">$page_logging <i class="wcp-md md-verified-user"></i></a></li>
EOC;

			}

			if ($this->can_edit) {
				$wcp_links .= <<<EOC
                        <li class="ielink"><a href="$page_export_arg">$page_export <i class="wcp-md md-cloud-download"></i></a></li>

EOC;
			/* Custom Access Export Import */
	       	} elseif ($custom_role['access']) {
				if ( $custom_role['perms']['access_import'] == 'yes' 
					|| $custom_role['perms']['access_export'] == 'all'
					|| $custom_role['perms']['access_export'] == 'own'
				) {
					$wcp_links .= <<<EOC
                        <li class="ielink"><a href="$page_export_arg">$page_export <i class="wcp-md md-cloud-download"></i></a></li>

EOC;
				}
			}

			if (isset($this->first_tab['calendar_events'])) {
				/* Custom Role Access */
				if ( ($custom_role['access'] && $custom_role['perms']['access_events'] == 'yes') 
					|| ($custom_role['access'] && $custom_role['perms']['access_events'] == 'addedit')
				) {
					$wcp_links .= <<<EOC
                        <li class="eventslink"><a href="$page_events_arg">$page_events <i class="wcp-md md-event-available"></i></a>
</li>

EOC;

				} elseif ( !$custom_role['access'] && $this->first_tab['calendar_events'] == 'true') {
					$wcp_links .= <<<EOC
						<li class="eventslink"><a href="$page_events_arg">$page_events <i class="wcp-md md-event-available"></i></a></li>

EOC;
				}
			}

			/* Custom Links Inclusion */

			if (isset($this->first_tab['custom_links'])
            	&& is_array($this->first_tab['custom_links'])
        	) { 
            	foreach ($this->first_tab['custom_links'] as $k => $v) {
					$target = '';
                	if (isset($v['open']) 
                    	&& $v['open'] == 'on'
                	) { 
						$target='target="_blank"';
					}
					if ($v['link'] && $v['url']) {  // Check that the url and link are set to show
						$wcp_links .= <<<EOC
						<li><a href="{$v['url']}" $target >{$v['link']}</a></li>
EOC;
					}
				}
			}

			$wcp_links = apply_filters('wcp_menu_links_filter', $wcp_links); // Add filter for just the links

			$this->head_section .= $wcp_links;
			$this->head_section .= <<<EOC
					</ul>
            	</nav>   
            
            	<div class="pure-pusher-container">
                	<div class="pure-pusher"><!-- Start main content area -->
		
						<div class="wcp-toolbar">
        					<div class="wcp-menu"></div>
        					<div class="wcp-breadcrumb">$breadcrumbs</div>
							$login_link
							$bar_tools
        					<div class="clear-both"></div>
    					</div>			
						<div class="wcp-container">
EOC;
	
	$this->head_section = apply_filters('wcp_head_section_filter', $this->head_section);  // filter for head

	$page_footer = isset($this->first_tab['page_footer']) ? $this->first_tab['page_footer'] : '';
	$this->bottom_section = <<<EOC
				</div><!-- End wcp-container -->
			</div>
		</div>
		<label class="pure-overlay" for="pure-toggle-left" data-overlay="left"></label>
	</div> <!-- End drawer -->
	<div class="wcp-footer">$page_footer</div>

</div><!-- End wcp-page -->

<div class="modal fade wcp-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            	<h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <div class="modal-body"></div>
        	<div class="modal-footer"></div>
    	</div>
	</div>
</div>
EOC;
	$this->bottom_section = apply_filters('wcp_bottom_section_filter', $this->bottom_section); // filter for bottom

			$content = $this->head_section . $this->main_section . $this->bottom_section;
   			return $content;
		} // end  content filter function

		/*
		 * Top search functionality
		 */
		public function top_search($search_select) {
			$top_search = '<div class="bar-tools">'
                        . '<div class="wcp-search hidden-xs">'
                        . '<select class="wcp-select" style="display:none">';

            if (isset($this->first_tab['all_fields']) && $this->first_tab['all_fields'] == 'true') {
            // if all fields search is enabled
                $all_fields_text = __('All Fields', 'shwcp');
                $top_search .= '<option value="wcp_all_fields">' . $all_fields_text . '</option>';
            }

            foreach ($search_select as $k => $v) {
                $selected = '';
                if (isset($_GET['s_field']) && $_GET['s_field'] == $v->orig_name) {
                    $selected = 'selected="selected"';
                }
                if ($v->orig_name != 'l_source'
                    && $v->orig_name != 'l_status'
                    && $v->orig_name != 'l_type'
                    && $v->field_type != '99'
                ) {
                    $top_search .= '<option value="' . $v->orig_name . '" ' . $selected . '/>'
                                . stripslashes($v->translated_name) . '</option>';
                }
            }

            $top_search .= '</select>'
                		 . '<input class="wcp-search-input" placeholder="' . __('Search', 'shwcp') . '" type="search" value="" />'
                		 . '</div>';

			$custom_role = $this->get_custom_role();
            // no access to adding for entry page and people who can't edit 
            if ($this->can_edit
				&& $this->curr_page != 'entry'
			) {
				$get_params = '';
				foreach ($_GET as $k => $v) {
					$get_params .= '<input type="hidden" name="get_params[' . $k . ']" value="' . $v . '" />';
				}
				$get_params .= wp_nonce_field( 'wcpexport', 'export-nonce', false, false );
                $top_search .= '<div class="add-holder"><i class="add-lead wcp-white wcp-sm md-add-circle-outline hidden-xs" '
							 . 'title="' . __('Add Entry', 'shwcp') . '"> </i></div>'
							 . '<div class="export-holder"><i class="export-view wcp-white wcp-sm md-file-download hidden-xs" '
							 . 'title="' . __('Export View', 'shwcp') . '"></i><div class="export-query">'
							 . $get_params . '</div></div>';
			/* Custom Roles Top Access */
            } elseif ($custom_role['access']) {                        
				if ($custom_role['perms']['entries_add'] == 'yes') {   // Can add entries
					$top_search .= '<div class="add-holder"><i class="add-lead wcp-white wcp-sm md-add-circle-outline hidden-xs" '
                                 . 'title="' . __('Add Entry', 'shwcp') . '"> </i></div>';
				} 
		  	    // Can export all or own, need to filter export for own
				if ($custom_role['perms']['access_export'] == 'all'
						|| $custom_role['perms']['access_export'] == 'own'
						&& $this->curr_page != 'entry'
					) {
					$get_params = '';
                	foreach ($_GET as $k => $v) {
                    	$get_params .= '<input type="hidden" name="get_params[' . $k . ']" value="' . $v . '" />';
                	}
					$get_params .= wp_nonce_field( 'wcpexport', 'export-nonce', false, false );
					$top_search .= '<div class="export-holder"><i class="export-view wcp-white wcp-sm md-file-download hidden-xs" '
                                 . 'title="' . __('Export View', 'shwcp') . '"></i><div class="export-query">'
                                 . $get_params . '</div></div>';
				}
			}

            $top_search .= '</div>';
			return $top_search;
		}


	} // end class
