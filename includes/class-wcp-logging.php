<?php
/**
 * WCP Class for logging changes made from users
 */

    class wcp_logging extends main_wcp {
        // properties

        // methods

        //public function __construct() {
        //    parent::__construct();
        //}

        /**
         * Logging activity
         **/
        public function log($event,$detail, $user_id, $user_login, $postID) {
            global $wpdb;
			$this->load_db_options($postID); // load the current tables and options
			$ip_addr = $_SERVER['REMOTE_ADDR'];
			$current_time = current_time( 'mysql' );

			$wpdb->insert($this->table_log,
				array(
					'user_id' => $user_id,
					'user_login' => $user_login,
					'event' => $event,
					'detail' => $detail,
					'event_time' => $current_time,
					'ip_addr' => $ip_addr
				),
				array( '%d','%s','%s','%s','%s','%s' )
			);
			return true;
		}

		/*
         * Retrieve Logging Entries
         */
        public function log_entries() {
            global $wpdb;
			// no access to this page for non-admins or custom role with no logging access
			$this->load_db_options(); // load the current tables and options
			$this->get_the_current_user();
			$custom_role = $this->get_custom_role();
			//echo $this->current_access;
            if ($this->current_access != 'full'
				&& !$custom_role['access']
			) {
                $content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                return $content;
            } elseif ($custom_role['access'] 
            	&& $custom_role['perms']['access_logging'] != 'yes'
        	) { 
            	$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
            	return $content;
        	}

			$paging_div = '';
			$field = '';
			// Pagination & sorting vars
            $paginate = $this->first_tab['page_page']; // pagination set?
            $rpp = $this->first_tab['page_page_count']; // pagination count - results per page
			// compare get against our array
            $log_fields = array('log_id','user_id','user_login','event','detail','event_time','ip_addr');

			// Searching query
			$searchset = false;
            if (isset($_GET['wcp_search']) // This is a search
				&& $_GET['wcp_search'] == 'true'
				&& isset($_GET['q'])
				&& trim($_GET['q']) != ''
			) {
				$searchset = true;
               	$q = esc_sql($_GET['q']);
			} 


			if ($searchset) { // search count
				$this->log_count = $wpdb->get_var( 
					"
					SELECT COUNT(*) FROM $this->table_log
					WHERE log_id LIKE '%$q%'
                    OR user_id LIKE '%$q%'
                    OR user_login LIKE '%$q%'
                    OR event LIKE '%$q%'
                    OR detail LIKE '%$q%'
                    OR event_time LIKE '%$q%'
                    OR ip_addr LIKE '%$q%'
					"
				);
			} else { // standard count
				$this->log_count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->table_log" );
			}

			$order_by = 'order by log_id desc'; // default sort
			$direction = 'desc';

           	if (isset($_GET["pages"])) {
               	$pages = intval($_GET["pages"]);
           	} else {
               	$pages = 1;
           	}
           	$tpages = ($this->log_count) ? ceil($this->log_count/$rpp) : 1;
           	$adjacents = '2';
           	$start_limit = ($pages -1) * $rpp;

			if ( isset($_GET['sort']) && isset($_GET['field']) ) {
				foreach ($log_fields as $v) {
					if ($_GET['field'] == $v) {
						$field = $v;
					}
					if ('asc' == $_GET['sort']) {
						$order_by = 'order by ' . $field . ' asc';
						$direction = 'asc';
					} else {
						$order_by = 'order by ' . $field . ' desc';
					}
				}
			}

			if ('true' == $paginate) { // we are paginating
                $order_by .= ' LIMIT ' . $start_limit . ', ' . $rpp; // add limit
				$reload = remove_query_arg( array('pages') );
                require_once(SHWCP_ROOT_PATH . '/includes/class-paginate.php');
                $wcp_paging = new paginate($reload, $pages, $tpages, $adjacents);
                $paging_div = $wcp_paging->getDiv();
			}

			// the query
			if ($searchset) { // search query
				$log_entries = $wpdb->get_results(
                	"
                	SELECT * from $this->table_log 
                    WHERE log_id LIKE '%$q%'
                    OR user_id LIKE '%$q%'
                    OR user_login LIKE '%$q%'
                    OR event LIKE '%$q%'
                    OR detail LIKE '%$q%'
                    OR event_time LIKE '%$q%'
                    OR ip_addr LIKE '%$q%'
                    $order_by
                    "
                );
			} else { // standard query
           		$log_entries = $wpdb->get_results("SELECT * from $this->table_log $order_by");
			}


			$headers = array();
			if (!empty($log_entries)) {
				foreach ($log_entries[0] as $k => $v) {
					switch ($k) {
						case 'log_id':
							$headers[$k] = __('Log ID', 'shwcp');
							break;
						case 'user_id':
						//	$headers[$k] = __('User ID', 'shwcp');
							break;
						case 'user_login':
							$headers[$k] = __('User Login', 'shwcp');
							break;
						case 'event':
							$headers[$k] = __('Event', 'shwcp');
							break;
						case 'detail':
							$headers[$k] = __('Details', 'shwcp');
							break;
						case 'event_time':
							$headers[$k] = __('Event Time', 'shwcp');
							break;
						case 'ip_addr':
							$headers[$k] = __('IP Address', 'shwcp');
							break;
						default:
							$headers[$k] = __('Unknown', 'shwcp');
					}
				}
			}
			
			// Small screen sort
			$sort_text = __('Sort Entries', 'shwcp');
			$sort_activated = '';
			$sort_small_links = '';
			foreach ($headers as $k => $v) {
                // default arrow down
                $arrow = '<i class="wcp-sm desc md-arrow-drop-down" title="' . __("Sort Descending", "shwcp") . '"> </i>';
                $sort_link = add_query_arg( array('sort' => 'desc', 'field' => $k) );

                if ( $k == $field) {
                    if ($direction == 'asc') {
                        $arrow = '<i class="wcp-sm wcp-primary md-arrow-drop-up" title="'
                               . __("Sort Ascending", "shwcp") . '"> </i>';
                        $sort_link = add_query_arg( array('sort' => 'desc', 'field' => $k));
						$sort_activated='wcp-primary';
                    } else {
                        $arrow = '<i class="wcp-sm wcp-primary md-arrow-drop-down" title="'
                               . __("Sort Descending", "shwcp") . '"> </i>';
                        $sort_link = add_query_arg( array('sort' => 'asc', 'field' => $k));
						$sort_activated='wcp-primary';
                    }
                }

                $sort_small_links .= '<li class="small-sort"><a href="' . $sort_link . '">' . $v . '</a>' . $arrow . '</li>';
            }

			$entries = <<<EOT
				<div class="row">
                	<div class="sst-bar col-sm-6 col-xs-12">
                    	<ul class="sst-select">
                        	<li>
                            	<a class="wcp-sort-menu" href="#">
									<i class="wcp-md md-sort $sort_activated" title="$sort_text"></i>
								</a>
                            	<ul>
									$sort_small_links
								</ul>
							</li>
						</ul>
					</div>
				</div>
EOT;

			// Table
			$entries .= '<table class="log-entries">';
            // Head row
			$entries .= '<tr>';
			foreach ($headers as $k => $v) {
				// default arrow down
				$arrow = '<i class="wcp-sm desc md-arrow-drop-down" title="' . __("Sort Descending", "shwcp") . '"> </i>';
				$sort_link = add_query_arg( array('sort' => 'desc', 'field' => $k) );

				if ( $k == $field) {
					if ($direction == 'asc') {
						$arrow = '<i class="wcp-sm wcp-primary md-arrow-drop-up" title="' 
							   . __("Sort Ascending", "shwcp") . '"> </i>';
						$sort_link = add_query_arg( array('sort' => 'desc', 'field' => $k) );
					} else {
						$arrow = '<i class="wcp-sm wcp-primary md-arrow-drop-down" title="' 
							   . __("Sort Descending", "shwcp") . '"> </i>';
						$sort_link = add_query_arg( array('sort' => 'asc', 'field' => $k) );
					}
				} 

				$entries .= '<th class="table-head"><a href="' . $sort_link . '">' . $v . '</a>' . $arrow . '</th>';
			}
			$entries .= '</tr>';
			
            foreach ($log_entries as $k => $v) {
                $entries .= '<tr>';
                foreach ($v as $k2 => $v2) {
					if ($k2 == 'user_id') {
						continue;
					} 
					if ($k2 == 'event_time') {
						// WP Display format
                        $v2 = date("$this->date_format $this->time_format", strtotime($v2));
					}
                    $entries .= '<td>' . $v2 . '</td>';
                }
                $entries .= '</tr>';
            }
            $entries .= '</table>';
			$entries .= $paging_div;

			if (empty($log_entries)) {
				$entries .= '<h3>' . __('No Log Entries found.', 'shwcp') . '</h3>';
			}

            return $entries;
        }

	}
