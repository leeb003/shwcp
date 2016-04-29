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

				// removed for now, tinymce isn't playing nice with custom styles on front end
				//wp_enqueue_style('tiny_mce', SHWCP_ROOT_URL . '/assets/css/tinymce-light/light/skin.min.css', '');
				//wp_enqueue_style('tiny_mce_content', SHWCP_ROOT_URL . '/assets/css/tinymce-light/light/content.min.css', '');

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

				// dynamic css
				global $post; // send post id for database style selection
				wp_register_style('dynamic-css', 
					admin_url('admin-ajax.php', $this->scheme)
						.'?action=dynamic_css&postid=' . $post->ID, 'wcp-frontend', SHWCP_PLUGIN_VERSION );
				wp_enqueue_style('dynamic-css');
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
                    'ajaxurl' => admin_url('admin-ajax.php', $this->scheme),
                    'nextNonce' => wp_create_nonce( 'myajax-next-nonce' ),
					'contactsColor' => isset($this->first_tab['page_color'])?$this->first_tab['page_color']:'#03a9f4',
					'fixed_edit' => isset($this->first_tab['fixed_edit'])?$this->first_tab['fixed_edit']: 'false',
					'postID' => $post->ID,
					'dateFormat' => $this->date_format_js,
					'timeFormat' => $this->time_format_js,

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
			$home_menu = __('Home', 'shwcp'); // used in drawer menu
			$home = __('Home', 'shwcp');
			if ( !isset($_GET['wcp'])) {  // only for main view
				if( isset($_GET['wcp_search']) 
					|| isset($_GET['st']) 
					|| isset($_GET['ty']) 
					|| isset($_GET['so'])
				) {	
					$home = __('Clear Filters', 'shwcp');
				}
			}
			$main_page = __('All Entries', 'shwcp');
			$page_front = __('Front Page Sorting', 'shwcp');
			$page_front_arg = add_query_arg( array('wcp' => 'frontsort'), get_permalink() );
			$page_fields = __('Manage Fields', 'shwcp');
			$page_fields_arg = add_query_arg( array('wcp' => 'fields'), get_permalink() );
			$page_sst = __('Sources Types & Status', 'shwcp');
			$page_sst_arg = add_query_arg( array('wcp' => 'sst'), get_permalink() );
			$page_entry = __('Individual Entry', 'shwcp');
			$page_entry_arg = add_query_arg( array('wcp' => 'entry'), get_permalink() );
			$page_export = __('Import & Export', 'shwcp');
			$page_export_arg = add_query_arg( array('wcp' => 'ie'), get_permalink() );
			$page_stats = __('Statistics', 'shwcp');
			$page_stats_arg = add_query_arg( array('wcp' => 'stats'), get_permalink() );
			$page_logging = __('Logging', 'shwcp');
			$page_logging_arg = add_query_arg( array('wcp' => 'logging'), get_permalink() );
			$page_events = __('Events', 'shwcp');
			$page_events_arg = add_query_arg( array('wcp' => 'events'), get_permalink() );
			$permalink = get_permalink();

			// Login Form and link variables
			$login_nonce = wp_nonce_field( 'ajax-login-nonce', 'security', true, false);
            $lost_password = wp_lostpassword_url();
            $username_text = __('Username', 'shwcp');
            $password_text = __('Password', 'shwcp');
            $lost_password_text = __('Lost Your Password?', 'shwcp');
            $logout_url = wp_logout_url( get_permalink());
            $logout_url_text = __('Logout', 'shwcp');
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
       	<a class="login_button" href="$logout_url">$logout_url_text</a>
	</div>
EOC;
            } else {
                $login_link .= <<<EOC
	<div class="login-link">
       	<a class="login_button" id="show_login" href="">$login_text</a>
	</div>
EOC;
            }


			if ($this->can_access ) {  // general access to the leads
        		if ('frontsort' == $this->curr_page) {  // Front fields sorting
					$this->main_section = $this->get_front_sorting();

        		} elseif ('fields' == $this->curr_page) { // Field setup
					$this->main_section = $this->get_edit_fields();
				} elseif ('sst' == $this->curr_page) { // Sources Status & Types
					$this->main_section = $this->get_sst_fields();
				} elseif ('entry' == $this->curr_page) { // Individual Lead page
					$this->main_section = $this->get_individual($db);
					$lead_id = intval($_GET['lead']);
					//$bar_tools = '<div class="bar-tools"></div>';
					$bar_tools = $this->top_search($search_select);
						
				} elseif ('ie' == $this->curr_page) { // Import Export page
					require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-ie.php');
					$wcp_ie = new wcp_ie();
					$this->main_section = $wcp_ie->import_export();
				} elseif ('stats' == $this->curr_page) { // Statistics
					$this->main_section = $this->get_stats();
					if ($this->current_access == 'ownleads') {  // get the ownleads stats file
						require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-stats-ownleads.php');
						$wcp_stats = new wcp_stats_ownleads();
					} else {
						require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-stats.php');
						$wcp_stats = new wcp_stats();
					}
					$this->main_section = $wcp_stats->load_statistics();
				} elseif ('logging' == $this->curr_page) { // Logging
					require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-logging.php');
					$wcp_logging = new wcp_logging();
					$this->main_section = $wcp_logging->log_entries();
					$bar_tools = '<div class="bar-tools">'
						. '<i class="remove-all-logs wcp-white wcp-md md-remove-circle-outline" title="'
						. __('Remove all log entries', 'shlcm') . '"> </i>'
						. '<div class="log-search">'
                        . '<input class="log-search-input" placeholder="' . __('Search', 'shwcp') . '" type="search" value="" />'
                        . '</div></div>';
				} elseif ('events' == $this->curr_page) { // Events
					require_once(SHWCP_ROOT_PATH  . '/includes/class-wcp-events.php');
					$wcp_events = new wcp_events();
					$this->main_section = $wcp_events->show_events();
					if ($this->can_edit) {
						$bar_tools = '<div class="bar-tools">'
								. '<i class="add-edit-event wcp-white wcp-md md-add" title="' . __('Add New Event', 'shwcp')
								. '"> </i></div>';
					}
				} else { // get the default view
					$bar_tools = $this->top_search($search_select);
					require_once(SHWCP_ROOT_PATH . '/includes/class-allleads.php');
                	$all_leads = new allleads();
					$this->main_section = $all_leads->get_all_leads();
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
				if ($this->curr_page == 'fields') {
					$bread_single = $page_fields;
				} elseif ($this->curr_page == 'sst') {
					$bread_single = $page_sst;
				} elseif ($this->curr_page == 'frontsort') {
					$bread_single = $page_front;
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

				$breadcrumbs = $breadcrumbs . ' > ' . ucfirst($bread_single);
			}
			if (isset($wcp_settings['logo_attachment_url']) && $wcp_settings['logo_attachment_url'] != '') {
				$logo_text = __('Logo', 'shwcp');
				$logo = '<img class="img-responsive" src="' . $wcp_settings['logo_attachment_url'] . '" alt="' . $logo_text . '"/>';
			} else {
				$logo = "";
			}
			// Head section and drawer	
			// test for demo site and preload demo login values
			$url = $_SERVER['HTTP_HOST'];
			$demo_creds = '';
			if ($url == 'demo.sh-themes.com') {
				$demo_creds = 'value="demo"';
			}
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
	<div class="wcp-page page-container wcp-access-$this->current_access">

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
				$this->head_section .= <<<EOC
						<li><a href="$page_front_arg">$page_front <i class="wcp-md md-subject"></i></a></li>
						<li><a href="$page_fields_arg">$page_fields <i class="wcp-md md-select-all"></i></a></li>
						<li><a href="$page_sst_arg">$page_sst <i class="wcp-md md-toc"></i></a></li>
EOC;
			}
			if ($this->current_access == 'full'
				|| $this->current_access == 'ownleads') {
				$this->head_section .= <<<EOC
						<li class="statslink"><a href="$page_stats_arg">$page_stats <i class="wcp-md md-trending-up"></i></a></li>
EOC;
			}
			if ($this->current_access == 'full') {
                $this->head_section .= <<<EOC
						<li class="logginglink"><a href="$page_logging_arg">$page_logging <i class="wcp-md md-verified-user"></i></a></li>
EOC;
			}

			if ($this->can_edit) {
				$this->head_section .= <<<EOC
                        <li class="ielink"><a href="$page_export_arg">$page_export <i class="wcp-md md-cloud-download"></i></a></li>

EOC;
	       	}
			if (isset($this->first_tab['calendar_events'])
				&& $this->first_tab['calendar_events'] == 'true'
			) {	
				$this->head_section .= <<<EOC
						<li class="eventslink"><a href="$page_events_arg">$page_events <i class="wcp-md md-event-available"></i></a></li>

EOC;
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
						$this->head_section .= <<<EOC
						<li><a href="{$v['url']}" $target >{$v['link']}</a></li>
EOC;
					}
				}
			}

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

			$content = $this->head_section . $this->main_section . $this->bottom_section;
   			return $content;
		} // end  content filter function

		/*
		 * Source Status & Types
		 */
		private function get_sst_fields() {
			global $wpdb;

			// no access to this page for non-admins
            if ($this->current_access != 'full') {
                $content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                return $content;
            }

			$sst_source = $wpdb->get_results (
				"
					SELECT * from $this->table_sst where sst_type_desc='l_source' order by sst_order asc
				"
			);
			$sst_status = $wpdb->get_results (
                "
                    SELECT * from $this->table_sst where sst_type_desc='l_status' order by sst_order asc
                "
            );
			$sst_type = $wpdb->get_results (
                "
                    SELECT * from $this->table_sst where sst_type_desc='l_type' order by sst_order asc
                "
            );

			$sort_names = $wpdb->get_results (
				"
					SELECT * from $this->table_sort
				"
			);
			$source_name = 'l_source';   // default source name
			foreach($sort_names as $k => $v) {
				if ($v->orig_name == 'l_source') {
					$source_name = stripslashes($v->translated_name);
				}
			}
			
			$status_name = 'l_status';   // default status name
            foreach($sort_names as $k => $v) {
                if ($v->orig_name == 'l_status') {
                    $status_name = stripslashes($v->translated_name);
                }
            }
			
			$type_name = 'l_type';   // default type name
            foreach($sort_names as $k => $v) {
                if ($v->orig_name == 'l_type') {
                    $type_name = stripslashes($v->translated_name);
                }
            }
			
			$sst_title = __('Sources, Status & Types', 'shwcp');
			$add_text = __('Add New', 'shwcp');
			$new_field_text = __('New Field', 'shwcp');
			$save_text = __('Save All', 'shwcp');

			$sst_fields = <<<EOC
			<div class="sst-top">
				<div class="wcp-title">$sst_title</div>
				<div class="field-actions">
					<div class="wcp-button save-sst">$save_text</div>
				</div>
			</div>
			<div class="row">
				<div class="wcp-sst wcp-sources col-md-4 col-sm-6"><h4>$source_name <i class="add-sst wcp-md md-add-circle" 
					title="$add_text $source_name"> </i></h4>
					<div class="wcp-sst-holder">
EOC;

			foreach($sst_source as $k => $v) {
				$remove = '';
				if ($v->sst_default != 1) {
					$remove = '<i class="remove-sst wcp-red wcp-md md-remove-circle-outline" title="' 
						    . __('Toggle Field Removal', 'shwcp') . '"> </i>';
				}
				$clean_name  = stripslashes($v->sst_name);
				$sst_fields .= '<div class="l_source">'
					 	    . '<input class="source-' . $v->sst_id . '" type="text" value="' . $clean_name . '" />'
							. $remove . ' <i class="wcp-md md-sort" title="' . __('Sort', 'shwcp') . '"> </i></div>';			
			}

			$sst_fields .= <<<EOC
				</div>
				<div class="sst-clone l_source">
					<input class="source-new" type="text" value="$new_field_text" />
					<i class="remove-sst wcp-red wcp-md md-remove-circle-outline"> </i>
					<i class="wcp-md md-sort"> </i>
				</div>
			</div>
			<div class="wcp-sst wcp-status col-md-4 col-sm-6"><h4>$status_name <i class="add-sst wcp-md md-add-circle" 
				title="$add_text $status_name"> </i></h4>
				<div class="wcp-sst-holder">
EOC;
			
			foreach($sst_status as $k => $v) {
				$remove = '';
                if ($v->sst_default != 1) {
                    $remove = '<i class="remove-sst wcp-red wcp-md md-remove-circle-outline" title="' 
						    . __('Toggle Field Removal', 'shwcp') . '"> </i>';
                }
                $sst_fields .= '<div class="l_status">'
							. '<input class="status-' . $v->sst_id . '" type="text" value="' . $v->sst_name . '"/>'
							. $remove . ' <i class="wcp-md md-sort" title="' . __('Sort', 'shwcp') . '"> </i></div>';     
            }

			$sst_fields .= <<<EOC
				</div>
				<div class="sst-clone l_status">
					<input class="status-new" type="text" value="$new_field_text" />
					<i class="remove-sst wcp-red wcp-md md-remove-circle-outline"> </i>
					<i class="wcp-md md-sort"> </i>
				</div>
			</div>
			<div class="wcp-sst wcp-types col-md-4 col-sm-6"><h4>$type_name <i class="add-sst wcp-md md-add-circle" 
				title="$add_text $type_name"> </i></h4>
				<div class="wcp-sst-holder">
EOC;
			foreach($sst_type as $k => $v) {
				$remove = '';
                if ($v->sst_default != 1) {
                    $remove = '<i class="remove-sst wcp-red wcp-md md-remove-circle-outline" title="' 
							. __('Toggle Field Removal', 'shwcp') . '"> </i>';
                }
                $sst_fields .= '<div class="l_type">'
							. '<input class="type-' . $v->sst_id . '" type="text" value="' . stripslashes($v->sst_name) . '" />'
							. $remove . ' <i class="wcp-md md-sort" title="' . __('Sort', 'shwcp') . '"> </i></div>';     
            }

			$sst_fields .= <<<EOC
				</div>
				<div class="sst-clone l_type">
					<input class="type-new" type="text" value="$new_field_text" />
					<i class="remove-sst wcp-red wcp-md md-remove-circle-outline"> </i>
					<i class="wcp-md md-sort"> </i>
				</div>
			</div>
		</div><!-- End Row -->
EOC;

			return $sst_fields;
		}

		/*
		 * Individual Lead page
		 */
		private function get_individual($db) {
			global $wpdb;
			$lead_id = intval($_GET['lead']);
			$lead_vals_pre = $wpdb->get_row (
				"
					SELECT l.*
                    FROM $this->table_main l
                    WHERE l.id = $lead_id;
				"
			);

			// no access to other leads for users that manage their own
            if ($this->current_access == 'ownleads' && $lead_vals_pre->owned_by != $this->current_user->user_login) {
				$content = '<span class="no-access">' . __('You do not have access to this entry', 'shwcp') . '</span>';
				return $content;
            }

			$sorting = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_ind_number asc");

			// organize by sorting and add others at the end
			foreach ($sorting as $k => $v) {
				foreach ($lead_vals_pre as $k2 => $v2) {
					if ($v->orig_name == $k2) {
						$lead_vals[$k2] = $v2;
					}
				}
			}
			// and add on the other fields that aren't listed in sorting
			$lead_vals['small_image'] = $lead_vals_pre->small_image;
			$lead_vals['lead_files'] = $lead_vals_pre->lead_files;

			//print_r($lead_vals);


            $sst = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");
			// filter editable fields
            $editable = array();
			$non_editable = array();
            foreach ($lead_vals as $k => $v) {
                if (in_array($k, $this->field_noedit)  //non editables or no edit access
					|| !$this->can_edit 
				) {
					$non_editable[$k] = $v;
                } else {
                    $editable[$k] = $v;
                }
            }

            // get the translated field names
            $translated = array();
            foreach ($editable as $k => $v) {
				$v = stripslashes($v);
				if ($k == 'user3') {
					continue;
				}
                $match = false;
                foreach ($sorting as $k2 => $v2) {
					if ($v2->orig_name == $k) {
                      	$translated[$k]['value'] = $v;
                       	$translated[$k]['trans'] = $v2->translated_name;
                        $match = true;
                    }
                }
                if (!$match) {  // this should not happen
                    $translated[$k]['value'] = $v;
                    $translated[$k]['trans'] = $k;
                }
            }

			// get the translated field names for non edits
			$non_edit_trans = array();
			foreach ($non_editable as $k => $v) {
				$v = stripslashes($v);
				$match = false;
				foreach ($sorting as $k2 => $v2) {
					if ($v2->orig_name == $k) {
						$non_edit_trans[$k]['value'] = $v;
						$non_edit_trans[$k]['trans'] = $v2->translated_name;
						$non_edit_trans[$k]['orig_name'] =  $v2->orig_name;
						$non_edit_trans[$k]['field_type'] = $v2->field_type;
					}
				}
            }

			$lead_detail_text = __('Entry Details', 'shwcp');
			$remove_image_text = __('Remove', 'shwcp');
			if ($lead_vals['small_image'] == '') {
				// If Default Entry has been set in settings, load it
				if (isset($this->first_tab['contact_image_url']) && $this->first_tab['contact_image_url'] != '') {
					$small_image = $this->first_tab['contact_image_url'];
				// Initial Site default image 
				} else {
                	$small_image = SHWCP_ROOT_URL . '/assets/img/default_lead.png';
				}
            } else {
                $small_image = $this->shwcp_upload_url . $db . '/' . $lead_vals['small_image'];
            }
			// Upload Small Image
			$lead_content = <<<EOC

							<div class="detail-top"><h4>$lead_detail_text</h4></div>
							<div class="row single-container">
EOC;


			 // Upload Files
            $lead_files = unserialize($lead_vals['lead_files']);
            if (empty($lead_files)) {
                $files_message = __('There are currently no files uploaded.', 'shwcp');
            } else {
                $files_message = __('Existing Files', 'shwcp');
            }
            // for hidden div storage
            $files_msg = __('Existing Files', 'shwcp');
            $no_files_msg = __('There are currently no files uploaded.', 'shwcp');

            $lead_content .= <<<EOC
                                <div class="col-md-4 single-right"><!--right side content -->

EOC;



			// Upload Small Image
			if ($this->first_tab['contact_image'] == 'true') {  // if contact images are set to display
				$lead_image_text = __('Entry Image', 'shwcp');
				$lead_content .= <<<EOC
					
								<div class="lead-image-container col-md-12 leadID-$lead_id">
						  			<div class="image-holder">
										<img class="current-image img-responsive" src="$small_image" alt="$lead_image_text" />
									</div>

EOC;

				if ($this->can_edit) {
					$button_text = __('Drag or click to upload a jpg, png or gif image file', 'shwcp');
					$set_image_text = __('Set Image', 'shwcp');
					$complete_text = __('Complete', 'shwcp');
					$lead_content .= <<<EOC

									<div id="browse_file">
						  				$button_text
									</div>
						  			<a href="#" class="submit-lead-image wcp-button" style="display:none;">$set_image_text</a>
						  			<div class="lead-image-file"></div>
						  				<!-- Progress -->
						  				<div class="progress"><div class="progress-container"> &nbsp;</div>
                     	  				<span class="progress-percent"></span>
									</div>
									<div class="remove-image-text" style="display:none;">$remove_image_text</div>
                     	  			<span class="complete-text">$complete_text</span>
EOC;
				}

					$lead_content .= <<<EOC

								</div>
								<div class="clear-both"></div>

EOC;
			}


			if ($this->first_tab['contact_upload'] == 'true') {  // Lead file uploads enabled
                $lead_content .= <<<EOC
                                    <div class="lead-files-container leadID-$lead_id">
                                        <div class="existing-files"><h6 class="files-message">$files_message</h6>

EOC;

                if (!empty($lead_files)) {
                    foreach ($lead_files as $file => $v) {
                        $lead_file_url = $this->shwcp_upload_url . $db . '/' . $lead_id . '-files/' . $v['name'];
                        $file_link = '<a class="leadfile-link" href="' . $lead_file_url . '">' . $v['name'] . '</a>';
                        $last_modified_text = __('Last Modified', 'shwcp');
                        $lead_content .= <<<EOC
                                            <div class="lead-info">
                                                <span class="leadfile-name" title="{$v['name']} $last_modified_text {$v['date']}">
                                                    $file_link
                                                </span>
                                                <span class="leadfile-size">{$v['size']}

EOC;

                        if ($this->can_edit) {
                            $lead_content .= <<<EOC
                                                    <i class="wcp-red wcp-md md-remove-circle-outline remove-existing-file"> </i>

EOC;
                        }

                        $lead_content .= <<<EOC

                                                </span>
                                            </div>

EOC;
                    }
                }

                $queued_text = __('Queued for upload', 'shwcp');
                $add_files_text = __('Add Files', 'shwcp');
                $lead_content .= <<<EOC

                                        </div>
                                        <div class="files-queued">
                                            <h6>$queued_text</h6>
                                        </div>
                                        <div class="wcp-button submit-lead-files">$add_files_text</div>

EOC;


                if ($this->can_edit) {
                    // Progress
                    $button_text = __('Drag or click to upload a file or multiple files', 'shwcp');
                    $lead_content .= <<<EOC
                                        <div class="progress">
                                            <div class="progress-container2"> &nbsp;</div>
                                            <span class="progress-percent2"></span>
                                        </div>
                                        <div id="upload_files">$button_text</div>

EOC;
                }

				$lead_content .= <<<EOC
									</div>
EOC;
                // file messages
                $lead_content .= <<<EOC

                                    <div class="files-msg">$files_msg</div>
                                    <div class="no-files-msg">$no_files_msg</div>
									<div class="clear-both"></div>

EOC;

            } // end Lead file uploads enabled

			// begin Entry Info

			$lead_content .= <<<EOC

                                    <div class="wcp-no-edit row">

EOC;
            $i = 0;
            $last = count($non_edit_trans);

			if ($this->current_access != 'readonly') {
            	// non edits display in access view (full, ownleads)
            	foreach ( $non_edit_trans as $k => $v) {
                	if ('l_source' == $k
                    	|| 'l_status' == $k
                    	|| 'l_type' == $k
                	) {
                    	foreach($sst as $k2 => $v2) {
                        	if ($k == $v2->sst_type_desc) {   // matching sst's
                            	if ($v['value'] == $v2->sst_id) { // selected
                                	$lead_content .= <<<EOC

                                        <div class="col-md-6">
                                            <span class="non-edit-label">{$v['trans']}</span>
                                            <span class="non-edit-value $k">$v2->sst_name</span>
                                        </div>

EOC;
                            	}
                        	}
                    	}
                	} else {
						if ($v['field_type'] == '7'
                        	|| $v['orig_name'] == 'updated_date'
                            || $v['orig_name'] == 'creation_date'
                         ) { // Date time format
							 $display_date = '';
                             if ($v['value'] != '0000-00-00 00:00:00') {
                                 $display_date = date("$this->date_format $this->time_format", strtotime($v['value']));
                             }
							$value = $display_date;
						} else {
							$value = $v['value'];
						}
	
                    	$lead_content .= <<<EOC
                                        <div class="col-md-6">
                                            <span class="non-edit-label">{$v['trans']}</span>
                                            <span class="non-edit-value $k">$value</span>
                                        </div>

EOC;
                	}

            	}
			} // end non-edits non-readonly view

			$lead_content .= <<<EOC
                                    </div><!-- End No-Edit Div -->

EOC;

			// end Entry Info
			
			$lead_content .= <<<EOC
								</div><!-- End single-right -->
EOC;


			$lead_content .= <<<EOC
				
						   			<div class="col-md-8 single-lead-fields">

EOC;

			// Read only, non edit display
			if ($this->current_access == 'readonly') {
				$lead_content .= <<<EOC
                                    <div class="wcp-edit-lead leadID-$lead_id row">

EOC;
                foreach ( $non_edit_trans as $k => $v) {
                    if ('l_source' == $k
                        || 'l_status' == $k
                        || 'l_type' == $k
                    ) {
                        foreach($sst as $k2 => $v2) {
                            if ($k == $v2->sst_type_desc) {   // matching sst's
                                if ($v['value'] == $v2->sst_id) { // selected
                                    $lead_content .= <<<EOC

                                        <div class="col-md-6">
                                            <span class="non-edit-label">{$v['trans']}</span>
                                            <span class="non-edit-value $k">$v2->sst_name</span>
                                        </div>

EOC;
                                }
                            }
                        }

					
                    } else {
						$clean_trans = stripslashes($v['trans']);
						$field_type = '';
                        foreach ($sorting as $sk => $sv) {
							if ($v['trans'] == $sv->translated_name) {  // match up sorting for each field for field type display
								if ($sv->field_type == '7'
									|| $sv->orig_name == 'updated_date'
									|| $sv->orig_name == 'creation_date'
								) { // Date time format
									$display_date = '';
                             		if ($v['value'] != '0000-00-00 00:00:00') {
                                 		$display_date = date("$this->date_format $this->time_format", strtotime($v['value']));
                             		}
									$lead_content .= <<<EOC
                                        <div class="col-md-6">
                                            <div class="non-edit-holder">
                                                <span class="non-edit-label">$clean_trans</span>
                                                <span class="non-edit-value $k">$display_date</span>
                                            </div>
                                        </div>

EOC;
								} elseif ($sv->field_type == '8') { // Star Rating
									$rating = floatval($v['value']);
									$lead_content .= <<<EOC
										<div class="col-md-6">
											<div class="non-edit-holder">
												<span class="non-edit-label">$clean_trans</span><br />
												<div class="rateit bigstars" data-rateit-ispreset="true" data-rateit-value="$rating"
                                               	data-rateit-starwidth="32" data-rateit-starheight="32" data-rateit-readonly="true">
												</div>
											</div>
										</div>
EOC;
								} elseif ($sv->field_type == '9') { // Checkbox
									$checked = '';
									$disabled = 'disabled="disabled"';
									if ($v['value'] == '1') {
										$checked = 'checked="checked"';
									}
									$lead_content .= <<<EOC
										<div class="col-md-6">
                                        	<div class="input-field">
                                            	<label for="$k">$clean_trans</label>
                                                <input class="checkbox $k" id="$k" type="checkbox" $checked $disabled />
                                                <label for="$k"> </label>
                                            </div>
                                        </div>

EOC;
								} elseif ($sv->field_type == '99') { // Group Title
									$lead_content .= <<<EOC
													<div class="col-md-12">
                                            			<div class="input-field fields-grouptitle">
                                                			<h3 for="$k">$clean_trans</h3>
                                                			<input class="lead_field $k" value="" type="hidden" />
														</div>
                                        			</div>
EOC;

								} else {  // All the others
                        			$lead_content .= <<<EOC
                                        <div class="col-md-6">
											<div class="non-edit-holder">
                                            	<span class="non-edit-label">$clean_trans</span>
                                            	<span class="non-edit-value $k">{$v['value']}</span>
											</div>
                                        </div>

EOC;
								}
                    		}
						}
					}

                }
				$lead_content .= <<<EOC
                                    </div>
EOC;

			
			// End read only display

			} else if ($this->can_edit) {
				$lead_content .= <<<EOC
										<div class="wcp-edit-lead leadID-$lead_id row">

EOC;
				$i = 0;
				$last = count($translated);
				foreach ( $translated as $k => $v) {
					if ('l_source' == $k
                    	|| 'l_status' == $k
                    	|| 'l_type' == $k
                	) {
						$lead_content .= <<<EOC

										<div class="col-md-6">
											<div class="input-field">
												<label for="$k">{$v['trans']}</label>
                              	      			<select id="$k" class="lead_select $k input-select">

EOC;

                    	foreach($sst as $k2 => $v2) {
							$v2->sst_name = stripslashes($v2->sst_name);
                        	$selected = '';
                        	if ($k == $v2->sst_type_desc) {   // matching sst's
                            	if ($v['value'] == $v2->sst_id) { // selected
                                	$selected = 'selected="selected"';
                            	}					
								$lead_content .= <<<EOC

													<option value="$v2->sst_id" $selected>$v2->sst_name</option>

EOC;
							}
						}
						$lead_content .= <<<EOC
												</select>
											</div>
										</div>

EOC;

					} elseif ('owned_by' == $k) {
						$disabled = '';
						// Manage Own can or can't change ownership
                		$can_change_ownership = isset($this->second_tab['own_leads_change_owner'])
                    		? $this->second_tab['own_leads_change_owner'] : 'no';


						if ($this->current_access == 'ownleads'
                        	&& $can_change_ownership == 'no'
                    	){
                        	$disabled = 1;
                    	} else {

							$lead_content .= <<<EOC
							
										<div class="col-md-6">
								   	  		<div class="input-field"><label for="$k">{$v['trans']}</label>
								      			<select class="lead_select $k input-select">

EOC;

							foreach($this->all_users as $k2 => $v2) {
								$selected = '';
								if ($v2->data->user_login == $v['value']) {
									$selected = 'selected="selected"';
								}
								$data_user_login = $v2->data->user_login;
								$lead_content .= <<<EOC
													<option value="$data_user_login" $selected>$v2->user_login</option>

EOC;
							}
					
							$lead_content .= <<<EOC
												</select>
											</div>
										</div>

EOC;
						}

					} else {  // The rest of the fields
						$clean_trans = stripslashes($v['trans']);
						$field_type = '';
						foreach ($sorting as $sk => $sv) {
                            if ($v['trans'] == $sv->translated_name) {  // match up sorting for each field for field type display
                                if ($sv->field_type == '2'
									|| $sv->field_type == '6' 
								) {  // Textarea, or Map Address

									$field_type = <<<EOC
													<div class="col-md-6">
                                            			<div class="input-field">
                                                			<label for="$k">$clean_trans</label>
                                                			<textarea class="lead_field $k materialize-textarea">{$v['value']}</textarea>
                                            			</div>
                                        			</div>
EOC;
								} elseif($sv->field_type == '7') { // Date Picker fields
									$display_date = '';
                             		if ($v['value'] != '0000-00-00 00:00:00') {
                                 		$display_date = date("$this->date_format $this->time_format", strtotime($v['value']));
                             		}
									$field_type = <<<EOC
													<div class="col-md-6">
														<div class="input-field">
															<label for="$k">$clean_trans</label>
															<input class="lead_field $k date-choice" value="$display_date" 
															    type="text" />
														</div>
													</div>
EOC;
								} elseif($sv->field_type == '8') { // Star Rating field
									$rateval = floatval($v['value']);
									$field_type = <<<EOC
													<div class="col-md-6">
                    									<div class="input-field">
                        									<label for="$k">$clean_trans</label>
                        									<div class="shwcp-rating rateit bigstars" data-rateit-starwidth="32" 
																data-rateit-starheight="32" backingfld="shwcp-rate-field"
																data-rateit-backingfld=".$k"
																data-rateit-ispreset="true" data-rateit-value="$rateval">
															</div>
															<input class="lead_field $k" value="$rateval" type="text" />
                    									</div>
                									</div>
EOC;
	                            } elseif ($sv->field_type == '9') { // Checkbox
                                    $checked = '';
                                    if ($v['value'] == '1') {
                                        $checked = 'checked="checked"';
                                    }
                                    $field_type = <<<EOC
                                        			<div class="col-md-6">
                                            			<div class="input-field">
															<label for="$k">$clean_trans</label>
															<input class="checkbox $k" id="$k" type="checkbox" $checked />
                                                			<label for="$k"> </label>
                                            			</div>
                                        			</div>

EOC;


								} elseif ($sv->field_type == '99') { // Group Title
									$field_type = <<<EOC
													<div class="col-md-12">
                                            			<div class="input-field fields-grouptitle">
                                                			<h3 for="$k">$clean_trans</h3>
                                                			<input class="lead_field $k" value="" type="hidden" />
														</div>
                                        			</div>
EOC;
								}

							}
						}

						if (!$field_type) {  // default
							$field_type = <<<EOC
											<div class="col-md-6">
                                           		<div class="input-field">
                                               		<label for="$k">$clean_trans</label>
                                               		<input class="lead_field $k" value="{$v['value']}" type="text" />
                                           		</div>
                                       		</div>
EOC;
						}
								
                   		$lead_content .= $field_type;
                	}

                	$i++;
				}

				$lead_content .= <<<EOC
					
									</div>
EOC;
			} // end can edit display edit field div


			if ($this->can_edit) {
				$save_field_text = __('Save Fields', 'shwcp');
		   		$lead_content .= <<<EOC
									<div class="save-lead-fields leadID-$lead_id wcp-button left-check">$save_field_text</div>

EOC;
			}
			
			// Notes
			$existing_notes = $wpdb->get_results (
				" 
					SELECT notes.*, user.user_login
					from $this->table_notes notes, {$wpdb->prefix}users user
					WHERE lead_id=$lead_id and notes.creator=user.ID
					order by date_added desc
				"
			);
			$notes_text = __('Notes', 'shwcp');
			$lead_content .= <<<EOC
									<div class="note-title"><h4>$notes_text</h4></div>
									<div class="lead-notes-container leadID-$lead_id">

EOC;

			foreach ($existing_notes as $k => $note) {
				$lead_content .= <<<EOC
										<div class="lead-note leadID-$lead_id noteID-$note->id">

EOC;

				if ($this->can_edit) {
					$lead_content .= <<<EOC
											<i class="wcp-red wcp-md md-remove-circle-outline remove-note"> </i>
											<i class="wcp-md md-create edit-note"> </i>

EOC;
				}

				$note_updated = false;
				$updated_entry = '';
				if ($note->updater != 0) { // get updater name
					$note_updated = true;
					$updater = $wpdb->get_var(
							"
							SELECT user_login from {$wpdb->prefix}users user WHERE user.ID=$note->updater
							"
					);
				}
				if (isset($updater) && $updater) {
					$updated_text = '&nbsp;&nbsp; | &nbsp;&nbsp;' . __('Last Updated by', 'shwcp');
					$updated_entry = <<<EOC
											$updated_text $updater
EOC;
				}

				$note_content = stripslashes($note->note_content);
				$date_formatted = date("$this->date_format $this->time_format", strtotime($note->date_added));
				$user_note_info = '👤 ' . $note->user_login;
                $lead_content .= <<<EOC
											<span class="timeline-header"> 🕓 $date_formatted $user_note_info
											$updated_entry</span>
                                           <span class="timeline-body">$note_content</span>

EOC;
				
				$lead_content .= <<<EOC

										</div>

EOC;
			}

			if (empty($existing_notes)) {
				$no_results = __('No Results Found', 'shwcp');
				$lead_content .= <<<EOC

										<div class="lead-note no-note">
										$no_results
										</div>
EOC;
			}	
						   
		 	$lead_content .= <<<EOC

									</div><!-- End lead-notes-container -->

EOC;

			if ($this->can_edit) {
                $add_note_text = __('Add A Note', 'shwcp');
                $lead_content .= <<<EOC

                                        <div class="add-note wcp-button">$add_note_text</div>

EOC;
            }

            $lead_content .= <<<EOC

                                </div><!--End Edits & No Edits-->
EOC;
            //print_r($non_edit_trans);
            //print_r($non_editable);

			$lead_content .= <<<EOC

						   	</div><!-- End lead-container -->

EOC;


			return $lead_content;
		}

		/*
		 * Statistics
		 */
		private function get_stats() {
			return 'statistics';
		}

		/*
		 *	Field edit, add, and removal - Manage Fields
		 */
		private function get_edit_fields() {
			global $wpdb;
		
			// no access to this page for non-admins
            $this->get_the_current_user();
            if ($this->current_access != 'full') {
                $content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                return $content;
            }

			$lead_columns = $wpdb->get_results (
				"
					SELECT * from $this->table_sort order by sort_ind_number asc
				"
			);

			$field_title      = __('Add, Edit, Define, Sort (for individual view and forms) and Remove Fields', 'shwcp');
			$field_desc       = __('Core Fields cannot be removed, but all can be renamed.', 'shwcp');
			$save             = __('Save Changes', 'shwcp');
			$add_new_text     = __('Add New Field', 'shwcp');
			$new_text         = __('New Field', 'shwcp');
			$text_field_text  = __('Text Field', 'shwcp');
			$text_area_text   = __('Text Area', 'shwcp');
			$phone_text       = __('Phone Number', 'shwcp');
			$email_text       = __('Email Address', 'shwcp');
			$website_text     = __('Website Address', 'shwcp');
			$map_text         = __('Google Map Link', 'shwcp');
			$date_text        = __('Date Time', 'shwcp');
			$rate_text        = __('Rating', 'shwcp');
			$check_text       = __('Checkbox', 'shwcp');
			$field_type_text  = __('Field Type', 'shwcp');
			$group_title_text = __('Group Title', 'shwcp');
			$required_text    = __('Required', 'shwcp');

			$remove_text      = __('Toggle Field Removal', 'shwcp');
			$remove_set_text  = __('Set For Removal', 'shwcp');
			$cancel_text      = __('Cancel', 'shwcp');

			$date_warning = __('If changing an existing field type to the Date Time selection all existing data for this field will be removed.  Just make sure that is what you want before saving.', 'shwcp');
			$date_warning_title = __('Warning', 'shwcp');
			$date_warning_close = __('Close', 'shwcp');

			$wcp_fields = <<<EOC
			<div class="fields-top">
				<div class="wcp-title">
					$field_title<br />
					$field_desc
				</div>
				<div class="field-actions">
					<div class="wcp-button save-fields">$save</div> <div class="wcp-button add-field">$add_new_text</div>
					<div class="date-field-warning" style="display:none"> 
						<div class='warning-title'>$date_warning_title</div>
						<div class='warning-message'>$date_warning</div>
						<div class='warning-close'>$date_warning_close</div>
					</div>
				</div>
			</div>
			<div class="clear-both"></div>
			<div class="wcp-fields">
EOC;
			foreach ($lead_columns as $k => $v) {
				if (!in_array($v->orig_name, $this->field_noremove)) {
					$remove = '<i class="remove-field wcp-red wcp-md md-remove-circle-outline" title="' . $remove_text . '"></i>'
						    . '<div class="wcp-button cancel-remove" style="display:none;">' . $cancel_text . '</div>';

					// Field types
					// 1 text field
					// 2 text area
					// 3 telephone number
					// 4 email address
					// 5 website address
					// 6 google map address
					// 7 date picker
					// 8 rating
					// 9 checkbox
					// 99 group title
					if (isset($v->field_type) ) {
						$field_type = $v->field_type;
					} else {
						$field_type = '1';
					}
					$checked = 'checked="checked"';
					$field_options = '<div class="field-options-holder">'
						. '<i class="field-options wcp-md md-data-usage" title="' 
						. $field_type_text . '"></i>'
						. '<div class="popover-material">'

						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="1" '
						. (($field_type == '1') ? $checked : '')
						. ' data-text="' . $text_field_text . '" />' . $text_field_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="2" '
						. (($field_type == '2') ? $checked : '')
						. ' data-text="' . $text_area_text . '" />' . $text_area_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="3" '
						. (($field_type == '3') ? $checked : '')
						. ' data-text="' . $phone_text . '" />' . $phone_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="4" '
                        . (($field_type == '4') ? $checked : '')
                        . ' data-text="' . $email_text . '" />' . $email_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="5" '
                        . (($field_type == '5') ? $checked : '')
                        . ' data-text="' . $website_text . '" />' . $website_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="6" '
						. (($field_type == '6') ? $checked : '')
						. ' data-text="' . $map_text . '" />' . $map_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="7" '
						. (($field_type == '7') ? $checked : '')
						. ' data-text="' . $date_text . '" />' . $date_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="8" '
						. (($field_type == '8') ? $checked : '')
						. ' data-text="' . $rate_text . '" />' . $rate_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="9" '
						. (($field_type == '9') ? $checked : '')
						. ' data-text="' . $check_text . '" />' . $check_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="99" '
						. (($field_type == '99') ? $checked : '')
						. ' data-text="' . $group_title_text . '" />' . $group_title_text . '<br />'
						. '</div></div>';

					$group_title_bg = '';
					$required_check = $v->required_input == 1 ? 'checked="checked"' : '';
					$required_field = '<div class="required-field-holder"><input type="checkbox"'
									. ' id="' . $v->orig_name . '-req" class="required-field" ' . $required_check . '/>'
									. '<label for="' . $v->orig_name . '-req">' . $required_text . '</label></div>';

					if ($field_type == '2') {
                    	$specific_type = '<div class="field-options-title"><span>' . $text_area_text . '</span></div>';
                	} elseif ($field_type == '3') { 
                    	$specific_type = '<div class="field-options-title"><span>' . $phone_text . '</span></div>';
                	} elseif ($field_type == '4') { 
                    	$specific_type = '<div class="field-options-title"><span>' . $email_text . '</span></div>';
                	} elseif ($field_type == '5') {
                    	$specific_type = '<div class="field-options-title"><span>' . $website_text . '</span></div>';
                	} elseif ($field_type == '6') {
                    	$specific_type = '<div class="field-options-title"><span>' . $map_text . '</span></div>';
                	} elseif ($field_type == '7') {
                    	$specific_type = '<div class="field-options-title"><span>' . $date_text . '</span></div>';
               		} elseif ($field_type == '8') {
                    	$specific_type = '<div class="field-options-title"><span>' . $rate_text . '</span></div>';
						$required_field = ''; // not on ratings
					} elseif ($field_type == '9') {
						$specific_type = '<div class="field-options-title"><span>' . $check_text . '</span></div>';
						$required_field = ''; // not on checkboxes
					} elseif ($field_type == '99') {
						$group_title_bg = ' style="background-color: #ededed;"';
						$specific_type = '<div class="field-options-title"><span>' . $group_title_text . '</span></div>';
						$required_field = ''; // just a label and not form input
                	} else {
                    	$specific_type = '<div class="field-options-title"><span>' . $text_field_text . '</span></div>';
                	}

				} else {
					$remove = '';
					$field_options = '';
					$specific_type = '';
					$required_field = '';
					$group_title_bg = '';
				}
				$clean_name  = stripslashes($v->translated_name);
				$wcp_fields .= '<div class="wcp-fielddiv"' . $group_title_bg . '><div class="wcp-group input-field">'
					. '<label for="' . $v->orig_name . '" class="field-label">' . $v->orig_name . '</label>'
					. '<input class="wcp-field ' . $v->orig_name . '" type="text" id="' . $v->orig_name 
					. '" value="' . $clean_name . '" required />'
					. '</div>' . $specific_type . $remove 
					. $field_options
					. '<i class="wcp-md md-sort" title="' . __('Sort', 'shwcp') . '"></i>'
					. $required_field
					. '</div>';
			}

			$wcp_fields .= <<<EOC
			</div>
			<div class="remove-set-text" style="display:none;">$remove_set_text</div>
			<div class="remove-text" style="display:none;">$remove_text</div>
			<div class="new-text" style="display:none;">$new_text</div>
			<div class="field-type-text" style="display:none;">$field_type_text</div>
			<div class="textfield-text" style="display:none;">$text_field_text</div>
			<div class="textarea-text" style="display:none;">$text_area_text</div>
			<div class="phone-text" style="display:none;">$phone_text</div>
			<div class="email-text" style="display:none;">$email_text</div>
			<div class="website-text" style="display:none;">$website_text</div>
			<div class="map-text" style="display:none;">$map_text</div>
			<div class="date-text" style="display:none;">$date_text</div>
			<div class="rate-text" style="display:none;">$rate_text</div>
			<div class="check-text" style="display: none;">$check_text</div>
			<div class="group-title-text" style="display:none;">$group_title_text</div>
			<div class="required-text" style="display:none;">$required_text</div>
EOC;

			return $wcp_fields;

		}

		/*
		 * Front table sorting fields
		 */
		private function get_front_sorting() {
			global $wpdb;

			// no access to this page for non-admins
            $this->get_the_current_user();
            if ($this->current_access != 'full') {
                $content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                return $content;
            }

			$lead_columns = $wpdb->get_results(
				"
					SELECT * from $this->table_sort order by sort_number
				"
			);
			$items1_title = __('Fields To Display On Main View (In Order)', 'shwcp');
			$items2_title = __('Fields Not To Display On Main View', 'shwcp');
			$save = __('Save Changes', 'shwcp');

			$wcp_sorting = <<<EOC
			<div class="wcp-title">$items1_title</div>
			<ul class="keepers front-sorting">
EOC;
			foreach ($lead_columns as $k => $v) {
				if (1 == $v->sort_active) {
					$wcp_sorting .= '<li class="keeper ' . $v->orig_name . '">' . stripslashes($v->translated_name) . '</li>';
				}
			}
			
			$wcp_sorting .= <<<EOC
			</ul>
			<div class="wcp-title">$items2_title</div>
			<ul class="nonkeepers front-sorting">
EOC;
			// Fields that don't display on the front end
			foreach ($lead_columns as $k => $v) {
                if (1 != $v->sort_active) {
                    $wcp_sorting .= '<li class="keeper ' . $v->orig_name . '">' . stripslashes($v->translated_name) . '</li>';
                }
            }
			
			$wcp_sorting .= <<<EOC
			</ul>
			<div class="row">
				<div class="col-md-12">
					<div class="wcp-button save-sorting">$save</div>
				</div>
			</div>
EOC;
			return $wcp_sorting;
			
		}

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
            // no access to adding for entry page and people who can't edit 
            if ($this->can_edit
				&& $this->curr_page != 'entry'
			) {
                $top_search .= '<i class="add-lead wcp-white wcp-md md-add" title="' . __('Add Entry', 'shwcp')
                            . '"> </i>';
            }

            $top_search .= '</div>';
			return $top_search;
		}


	} // end class
