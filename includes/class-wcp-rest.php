<?php
/**
 * Class wcp_rest
 * WP API Integration for RESTful access to the database
 * does not extend main like other classes, so some things have to be defined here as well
 */

    class wcp_rest {
        // properties
        protected $table_main;
        protected $table_sst;
        protected $table_log;
        protected $table_sort;
        protected $table_notes;
        protected $table_events;
		protected $first_tab;
		protected $second_tab;
		protected $shwcp_upload;
		protected $shwcp_upload_url;
		protected $wcp_logging;
		protected $current_user;

        // methods

        /**
         * 
         * @access public
         * @since 2.0.8
        **/
		public function __construct() {
			// upload directory & url - currently we add db_number to it in the functions using this
            $upload_dir = wp_upload_dir();
            $this->shwcp_upload = $upload_dir['basedir'] . '/shwcp';
            $this->shwcp_upload_url = $upload_dir['baseurl'] . '/shwcp';

			add_action( 'rest_api_init', array($this, 'wcp_register_api_hooks') );

		}

		/**
         * Dynamic DB tables settings based on Database chosen
         */
        public function get_tables($db_name) {
            global $wpdb;
			$db_set = false;
            if ($db_name) {
                $db_set = true;
            }
			if ($db_set) {  // lookup otherwise default
				// look up all main_settings database_name fields and build an array to search names
            	$options_table = $wpdb->prefix . 'options';
            	$option_entry = 'shwcp_main_settings';
            	$dbs = $wpdb->get_results("SELECT * FROM $options_table WHERE `option_name` LIKE '%$option_entry%'");
            	$databases = array();
            	foreach ($dbs as $k => $option) {
                	if ($option->option_name == $option_entry) {
                    	$db_options = get_option($option->option_name);
                    	if (!isset($db_options['database_name'])) {
                        	$database_name = __('Default', 'shwcp');
                    	} else {
                        	$database_name = $db_options['database_name'];
                    	}
                    	$databases['default'] = $database_name;
                	} else {
                    	$db_options = get_option($option->option_name);
                    	$remove_name = '/^' . $option_entry . '_/';  // Just get the database number
                    	$db_number = preg_replace($remove_name, '', $option->option_name);
                    	$database_name = $db_options['database_name'];
                    	$databases[$db_number] = $database_name;
                	}
            	}
			
				foreach ($databases as $k => $v) {
					if ($db_name == $v) {
						$database = $k;
					}
				}
				if (!isset($database)) {
					$database = 'default';
				}
			} else {
				$database = 'default';
			}

            //print_r($databases);
            //print_r($dbs);


            if ($database && $database != 'default') {
                $db = '_' . $database;
            } else {
				$db = '';
			}

            $this->table_main     = $wpdb->prefix . SHWCP_LEADS  . $db;
            $this->table_sst      = $wpdb->prefix . SHWCP_SST    . $db;
            $this->table_log      = $wpdb->prefix . SHWCP_LOG    . $db;
            $this->table_sort     = $wpdb->prefix . SHWCP_SORT   . $db;
            $this->table_notes    = $wpdb->prefix . SHWCP_NOTES  . $db;
            $this->table_events   = $wpdb->prefix . SHWCP_EVENTS . $db;

            $this->first_tab = get_option('shwcp_main_settings' . $db);
            $this->second_tab = get_option('shwcp_permissions'  . $db);
            return $db;
        }

		/**
		 * Register hooks
		 * @access public
		 * @route wp-json/shwcp/v1/
		 * @since 2.0.8
		 **/
		public function wcp_register_api_hooks() {
			$namespace = 'shwcp/v1';
			$this->current_user = wp_get_current_user();

			// /wp-json/shwcp/v1/get-contact-count/
			register_rest_route( $namespace, '/get-contact-count/', array(
				'methods' => 'GET',
				'permission_callback' => array(
					$this,
					'shwcp_permission_callback',
				),
				'callback' => array(
					$this,
					'shwcp_get_contact_count',
				)
			) );

			// /wp-json/shwcp/v1/get-contacts/
			register_rest_route( $namespace, '/get-contacts/', array(
				'methods' => 'GET',
				'permission_callback' => array(
                    $this,
                    'shwcp_permission_callback',
                ),
				'callback' => array(
					$this,
					'shwcp_get_contacts',
				)
			) );

			// /wp-json/shwcp/v1/get-contact/
			register_rest_route( $namespace, '/get-contact/', array(
				'methods' => 'GET',
				'permission_callback' => array(
                    $this,
                    'shwcp_permission_callback',
                ),
				'callback' => array(
					$this,
					'shwcp_get_contact'
				)
			) );
			// /wp-json/shwcp/v1/delete-contact/
			register_rest_route( $namespace, '/delete-contact/', array(
				'methods' => 'POST',
				'permission_callback' => array(
					$this,
					'shwcp_permission_callback',
				),
				'callback' => array(
					$this,
					'shwcp_delete_contact'
				)
			) );
			// /wp-json/shwcp/v1/create-contact/
			register_rest_route( $namespace, '/create-contact/', array(
				'methods' => 'POST',
				'permission_callback' => array(
					$this,
					'shwcp_permission_callback',
				),
				'callback' => array(
					$this,
					'shwcp_create_contact'
				)
			) );
			// /wp-json/shwcp/v1/update-contact/
			register_rest_route( $namespace, '/update-contact/', array(
				'methods' => 'POST',
				'permission_callback' => array(
					$this,
					'shwcp_permission_callback',
				),
				'callback' => array(
					$this,
					'shwcp_update_contact'
				)
			) );

			// /wp-json/shwcp/v1/user-perms/
			register_rest_route( $namespace, '/user-perms/', array(
				'methods' => 'POST',
				'permission_callback' => array(
					$this,
					'shwcp_permission_callback',
				),
				'callback' => array(
					$this,
					'shwcp_user_perms'
				)
			) );

			/* Considered future routes */
			// /wp-json/shwcp/v1/add-note/
			// /wp-json/shwcp/v1/delete-note/
			// /wp-json/shwcp/v1/update-note/

		}

		/**
		 * Permission Callback check used for authorizing request
		 * user capabilities (admin) install_plugins
		 */
		public function shwcp_permission_callback() {
			return current_user_can('install_plugins'); 
		}


		/**
		 * Route Callback Total Contact Count
		 */
		public function shwcp_get_contact_count($request) {
			global $wpdb;
			$params = $request->get_params();
			$db = isset($params['db']) ? $request['db'] : '';
			$db_number= $this->get_tables($db);
			
			// Total lead count for pagination & display
            $lead_count = $wpdb->get_var("SELECT count(*) as count FROM $this->table_main");
			$return = array(
				'count'           => $lead_count,
				'database'        => $db,
				'database_number' => $db_number
			);
			$response = new WP_REST_Response( $return, 200 );
            return $response;
		}

		/**
		 * Route Callbacks all Contacts
		 */

		public function shwcp_get_contacts($request) {
			global $wpdb;
			$params = $request->get_params();
			$db     = isset($params['db']) ? $params['db'] : '';
			$first  = isset($params['first']) ? intval($params['first']) : null;
			$limit  = isset($params['limit']) ? intval($params['limit']) : null;
			// $sortby = isset($params['sortby']) ? $params['sortby'] : '';
			$dir    = isset($params['dir']) ? $params['dir'] : 'asc';
			$db_number= $this->get_tables($db);
			$vars = array();

			$order_by = "order by id $dir"; // default
			/*
			if ($sortby) {
				$order_by = 'order by ' . $sortby . ' ' . $dir;
			}
			*/
			if (isset($first) && isset($limit)) {
				 $order_by .= ' LIMIT %d, %d'; // add limit
				 $vars[] = $first;
				 $vars[] = $limit;
			}

			$contacts = $wpdb->get_results( $wpdb->prepare (
                "
                    SELECT l.*, sst1.sst_name as source, sst2.sst_name as status, sst3.sst_name as type
                    FROM $this->table_main l, $this->table_sst sst1, $this->table_sst sst2, $this->table_sst sst3
                    WHERE l.l_source = sst1.sst_id
                    AND l.l_status = sst2.sst_id
                    AND l.l_type = sst3.sst_id
            		$order_by
				",
				$vars
            ) );
			$sorting = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_ind_number asc");
            $sst = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");

			$translated = array();
			foreach ($contacts as $k => $contact) {
				$id = $contact->id;
				$translated[] = $this->shwcp_return_contact($contact, $id, $sorting, $sst, $db_number);
			}
							
			$return = array(
				'contacts'        => $translated,
				'database'        => $db,
				'database_number' => $db_number,
				'request'         => $params,
			);
			$response = new WP_REST_Response( $return, 200 );
            return $response;
		}

		/**
         * Route Callbacks Single Contact by id
         */
        public function shwcp_get_contact($request) {
			global $wpdb;
            $params = $request->get_params();
            $db     = isset($params['db']) ? $params['db'] : '';
            $id     = isset($params['id']) ? intval($params['id']) : 0;
            $db_number= $this->get_tables($db);
			
			$lead_vals_pre = $wpdb->get_row( $wpdb->prepare(
            	"
                	SELECT l.*
                	FROM $this->table_main l
                	WHERE l.id = %d;
            	",
				$id
        	));
			$sorting = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_ind_number asc");
			$sst = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");

			$translated = $this->shwcp_return_contact($lead_vals_pre, $id, $sorting, $sst, $db_number); // process contact

            $return = array(
                'contact' => $translated,
                'database' => $db
            );
        
            $response = new WP_REST_Response( $return, 200 );
            return $response;
        }

		/** 
		 * Route Callback Delete Contact by id
		 */
		public function shwcp_delete_contact($request) {
			global $wpdb;
			$params    = $request->get_params();
			$db		   = isset($params['db']) ? $params['db'] : '';
			$id 	   = isset($params['id']) && is_numeric($params['id']) ? $params['id'] : 'not set';
			$db_number = $this->get_tables($db);

			// Get contact details beforehand to remove files
			$contact = $wpdb->get_row( $wpdb->prepare(
                "
                    SELECT l.*
                    FROM $this->table_main l
                    WHERE l.id = %d;
                ",
                $id
            ));

			if (empty($contact)) {
				$return = array(
					'error' => __('No match or no id given', 'shwcp')
				);
				$response = new WP_REST_Response( $return, 400 );
				return $response;
			}

			// Delete contact - status is 0 no deletion, 1 success
			$deleted = $wpdb->delete(
                $this->table_main,
                array(
                    'id' => $id
                ),
                array(
                    '%d'
                )
            );
			// Delete contact notes
			$delete_notes = $wpdb->delete(
				$this->table_notes,
				array(
					'lead_id' => $id
				),
				array(
					'%d'
				)
			);
			// Delete contact files and log
			if ($deleted) {
				$files = unserialize($contact->lead_files);
				foreach ($files as $k => $file) {
                	$lead_dir = $this->shwcp_upload . $db_number . '/' . $id . '-files';
                	if (file_exists($lead_dir . '/' . $file['name'])) {
                    	unlink($lead_dir . '/' . $file['name']);
					}
				}
				if ($contact->small_image) {
					$image = $this->shwcp_upload . $db_number . '/' . $contact->small_image;				
					$parts = explode(".", $contact->small_image);
                	$ext = array_pop($parts);
                	$thumb = $this->shwcp_upload . $db_number . '/' . $parts[0] . '_th.' . $ext;
					if (file_exists($image)) {
						unlink($image);
					}
					if (file_exists($thumb)) {
						unlink($thumb);
					}
				}

				// log
            	$event = __('Deleted Entry', 'shwcp');
            	$detail = __('Entry ID ', 'shwcp') . $id;
            	$this->rest_log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $this->table_log);
			}

			$return = array(
				'id'      => $id,
				'action'  => 'delete',
				'status'  => $deleted,
				'files'   => $files,
				'image'   => $image
			);
			$response = new WP_REST_Response( $return, 200 );
			return $response;
		}

		/**
		 * Route Callback Insert Contact
		 */
		public function shwcp_create_contact($request) {
			global $wpdb;
            $params    = $request->get_params();
            $db        = isset($params['db']) ? $params['db'] : '';
            $db_number = $this->get_tables($db);
			$fields    = isset($params['fields']) ? $params['fields'] : array();
			
			$results = $this->add_update_contact('add', $db_number, $fields);

			$response = new WP_REST_Response( $results['return'], $results['status'] );
            return $response;

		}

		/**
		 * Route Callback Update Contact
		 */
		public function shwcp_update_contact($request) {
            global $wpdb;
            $params    = $request->get_params();
            $db        = isset($params['db']) ? $params['db'] : '';
            $db_number = $this->get_tables($db);
            $fields    = isset($params['fields']) ? $params['fields'] : array();
			$id        = isset($params['id']) ? intval($params['id']) : '';

			if (trim($id) == '') {
				$return = array(
					'error' => __('You must give an existing id', 'shwcp')
				);
				$response = new WP_REST_Response( $return, 400 );
				return $response;
			}

			$results = $this->add_update_contact('update', $db_number, $fields, $id);
	
            $response = new WP_REST_Response( $results['return'], $results['status'] );
            return $response;
		}

		/**
		 * Route Callback User Permissions
 		 */
		public function shwcp_user_perms($request) {
			global $wpdb;
			$params    = $request->get_params();
			$db		   = isset($params['db']) ? $params['db'] : '';
			$db_number = $this->get_tables($db);
			$username  = isset($params['username']) ? $params['username'] : '';
			$access    = isset($params['access']) ? $params['access'] : '';

			$level = false;
			$accesslevels = array('none','readonly','ownleads','full');
            if (in_array($access, $accesslevels)) {
				$level = true;
            }
			if (!$level) {
				$return = array(
					'error' => __('Incorrect access level', 'shwcp')
				);
				$response = new WP_REST_Response( $return, 400 );
				return $response;
			}
			$wp_users = get_users();
			$userset = false;
			if ($username != '') {
				foreach ($wp_users as $k => $v) {
					if ($v->user_login == $username) {
						$userID = $v->ID;
						$userset = true;
					}
				}
			}
			if (!$userset) {
				$return = array(
                    'error' => __('WordPress user does not exist', 'shwcp')
                );
                $response = new WP_REST_Response( $return, 400 );
                return $response;
            }
			$owner_change = $this->second_tab['own_leads_change_owner'];
			$existing = false;
			foreach ($this->second_tab['permission_settings'] as $k => $v) {
				if ($k == $userID) { // update existing user
					$new_permissions[$k] = $access;
					$existing = true;
				} else {
					$new_permissions[$k] = $v;
				}
			}
			// add new user
			if (!$existing) {
				$new_permissions[$userID] = $access;
			}

			$final_permissions = array(
				'own_leads_change_owner' => $owner_change,
				'permission_settings' => $new_permissions
			);
			// update permissions
			update_option('shwcp_permissions'  . $db_number, $final_permissions);
			

			$return = array(
				'status' => __('Permission Set', 'shwcp'),
				//'perms' => $this->second_tab,
				//'users' => $wp_users,
				'userID' => $userID
			);
			$response = new WP_REST_Response( $return, 200 );
			return $response;
		}

		/**
         * Organize contact data for REST return data
		 * @param array $contact The Contact entry
		 * @param int $id Contact ID
		 * @param array $sorting The Sorting array
		 * @param array $sst Source, Status, Type
		 *
		 * @return array
         */
        public function shwcp_return_contact($contact, $id, $sorting, $sst, $db) {
			global $wpdb;
			// organize by sorting and add others at the end
            foreach ($sorting as $k => $v) {
                foreach ($contact as $k2 => $v2) {
                    if ($v->orig_name == $k2) {
                        $lead_vals[$k2] = $v2;
                    }
                }
            }
            // and add on the other fields that aren't listed in sorting

            $lead_vals['small_image'] = $contact->small_image;
            if ($lead_vals['small_image']) { // set url
                $lead_vals['small_image'] = $this->shwcp_upload_url . $db . '/' . $lead_vals['small_image'];
            }
            $lead_vals['lead_files'] = unserialize($contact->lead_files);
			if (!empty($lead_vals['lead_files'])) {
            	foreach ($lead_vals['lead_files'] as $k => $v) { // set url
                	$lead_vals['lead_files'][$k]['url'] = $this->shwcp_upload_url . $db . '/' . $id . '-files/' . $v['name'];
            	}
			}

            // get the translated field names
            $translated = array();
            foreach ($lead_vals as $k => $v) {
                $v = (!is_array($v)) ? stripslashes($v) : $v;
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

			foreach ($translated as $k => $v) {
                if ('l_source' == $k          // set sst to names
                    || 'l_status' == $k
                    || 'l_type' == $k
                ) {
                    foreach($sst as $k2 => $v2) {
                        $v2->sst_name = stripslashes($v2->sst_name);
                        $selected = '';
                        if ($k == $v2->sst_type_desc) {   // matching sst's
                            if ($v['value'] == $v2->sst_id) { // selected
                                $translated[$k]['value'] = $v2->sst_name;
                            }
                        }
                    }
                } elseif ( 'id' == $k ) { // insert the notes on id match
                    $notes = $wpdb->get_results (
                        " 
                        SELECT notes.*, user.user_login
                        from $this->table_notes notes, {$wpdb->base_prefix}users user
                        WHERE `lead_id`={$v['value']}
                        order by date_added asc
                        "
                    );
                    $note_entries = array();
                    foreach ($notes as $k => $note) {
                        $note_entries[] = array(
                            'id' => $note->id,
                            'note_content' => $note->note_content,
                            'date_added'   => $note->date_added,
                            'creator'      => $note->user_login,
                            'date_updated' => $note->date_updated
                        );
                    }
                    $translated['notes'] = $note_entries;
                } else { // look for dropdown fields to translate selection (like sst's)
                    $clean_trans = stripslashes($v['trans']);
                    foreach ($sorting as $sk => $sv) {
                        if ($v['trans'] == $sv->translated_name) {  // match up sorting for each field
                            if ($sv->field_type == '10') { // Dropdown
                                $entry = '';
                                foreach($sst as $k2 => $v2) {
                                    if ($k == $v2->sst_type_desc) {   // matching sst's
                                        if ($v['value'] == $v2->sst_id) { // selected
                                            $translated[$k]['value'] = $v2->sst_name;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

			return ($translated);

		}

		/**
         * Logging activity
         **/
        public function rest_log($event,$detail, $user_id, $user_login, $table_log) {
            global $wpdb;
            $ip_addr = $_SERVER['REMOTE_ADDR'];
            $current_time = current_time( 'mysql' );

            $wpdb->insert($table_log,
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

		/**
         * Update the database if needed with new sst otherwise return existing id
		 * 
		 * @param string $value the value
		 * @param array $sst source status type
		 * @param string $choice description
		 * @param int $sst_type type
		 * @param string $table_sst the table to work with
		 *
		 * return int
         */
        public function sst_update_checkdb($value, $sst, $choice, $sst_type, $table_sst) {
            global $wpdb;
            $exists = false;
            foreach ($sst as $sst_row => $sst_data) {
                if ($value == $sst_data->sst_name) {
                    $exists = true;
                    return $sst_data->sst_id;
                }
            }
            if (!$exists) {
                $wpdb->insert(
                    $table_sst,
                    array(
                        'sst_name' => $value,
                        'sst_type_desc' => $choice,
                        'sst_type' => $sst_type,
                        'sst_default' => 0,
                        'sst_order' => 0
                    ),
                    array('%s','%s','%d','%d','%d')
                );
                return $wpdb->insert_id;
            }
        }

		/**
		 * Add or update a contact
		 * 
		 * @param string $method add or update
		 * @param string $db_number the database
		 * @param array $fields fields to insert or update
		 * @param int id the id of the entry to update
		 *
		 */
		public function add_update_contact($method, $db_number, $fields, $id='') {
			global $wpdb;
			if (!isset($fields) || empty($fields)) {
                $return = array(
                    'error' => __('No fields set to insert', 'shwcp')
                );
				$results = array(
					'status' => 400,
					'return' => $return
				);
                return $results;
            }   

            /* Get the existing columns to compare submitted to */
            $main_columns = $wpdb->get_results(
                "
                SELECT `COLUMN_NAME`
                FROM `INFORMATION_SCHEMA`.`COLUMNS`
                WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                AND `TABLE_NAME`='$this->table_main'
                "
            );
            $sorting = $wpdb->get_results("SELECT * FROM " . $this->table_sort);
            $sst = $wpdb->get_results("SELECT * FROM " . $this->table_sst);

            // print_r($main_columns);
            $existing_fields = array();
            foreach($main_columns as $k => $v) {
                $existing_fields[] = $v->COLUMN_NAME;
            }
            // print_r($existing_fields);
            foreach ($fields as $k => $v) {
                if (in_array($k, $existing_fields)) {
                    $wpdatafinal[$k] = $v;
                }
            }

			if ($method == 'add') {
            	$wpdatafinal['creation_date'] = current_time( 'mysql' );
            	$wpdatafinal['created_by']    = $this->current_user->user_login;
			}
            $wpdatafinal['updated_date']  = current_time( 'mysql' );
            $wpdatafinal['updated_by']    = $this->current_user->user_login;
            // remove any fields that shouldn't change
            unset($wpdatafinal['id']);
            unset($wpdatafinal['small_image']);
            unset($wpdatafinal['lead_files']);


            // get the sst's if the name doesn't match assign to default
            // get sst defaults
            foreach ($sst as $k => $v) {
                if ($v->sst_type == 1 && $v->sst_default == 1) { $default_source = $v->sst_id;
                } elseif ($v->sst_type == 2 && $v->sst_default == 1) { $default_status = $v->sst_id;
                } elseif ($v->sst_type == 3 && $v->sst_default == 1) { $default_type = $v->sst_id; }
            }
			
			
            /* Source */
            if (!isset($wpdatafinal['l_source']) || empty(trim($wpdatafinal['l_source'])) ) {
                $wpdatafinal['l_source'] = $default_source;
            } else {
                $value = $this->sst_update_checkdb($wpdatafinal['l_source'], $sst, 'l_source', 1, $this->table_sst);
                $wpdatafinal['l_source'] = $value;
            }

            /* Status */
            if (!isset($wpdatafinal['l_status']) || empty(trim($wpdatafinal['l_status'])) ) {
                $wpdatafinal['l_status'] = $default_status;
            } else {
                $value = $this->sst_update_checkdb($wpdatafinal['l_status'], $sst, 'l_status', 2, $this->table_sst);
                $wpdatafinal['l_status'] = $value;
            }

            /* Type */
            if (!isset($wpdatafinal['l_type']) || empty(trim($wpdatafinal['l_type'])) ) {
                $wpdatafinal['l_type'] = $default_type;
            } else {
                $value = $this->sst_update_checkdb($wpdatafinal['l_type'], $sst, 'l_type', 3, $this->table_sst);
                $wpdatafinal['l_type'] = $value;
            }
   
            /* Dropdown Check */
            foreach ($wpdatafinal as $f => $v) {
                foreach ($sorting as $k2 => $v2) {
                    if ($v2->orig_name == $f) {
                        if ($v2->field_type == '10') {
                            $sst_type = $wpdb->get_var(
                                        "SELECT sst_type FROM " . $this->table_sst
                                        . " where sst_type_desc='$f' limit 1"
                                    );
                            $value = $this->sst_update_checkdb($v, $sst, $f, $sst_type, $this->table_sst);
                            $wpdatafinal[$f] = $value;
                        }
                    }
                }
            }

			// Prepare insert
            foreach ($wpdatafinal as $f => $v) {
                if ($f == 'l_source'
                || $f == 'l_status'
                || $f == 'l_type'
                ) {
                    $format[] = '%d';
                } else {
                    $format[] = '%s';
                }
            }

			if ($method == 'update') {  // update
                $event = __('Updated Entry', 'shwcp');
				$wpdb->update(
					$this->table_main,
					$wpdatafinal,
					array( 'id' => $id),
					$format,
					array ( '%d' )
				);
	
			} else {				  // add
				$event = __('Added Entry', 'shwcp');

            	$wpdb->insert(
                	$this->table_main,
                	$wpdatafinal,
                	$format
            	);
            	$id = $wpdb->insert_id;
			}

            // log
            $output_string = implode(', ', $fields);
            $detail = __('Entry ID', 'shwcp') . ' ' . $id . __(' Fields-> ', 'shwcp') . $output_string;
            $this->rest_log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $this->table_log);

            $return = array(
                'id' => $id,
                'action' => $method,
                'fields' => $fields,
                //'sst' => $sst,
                //'final' => $wpdatafinal
            );
			$results = array(
                    'status' => 200,
                    'return' => $return
                );
            return $results;

		}
	}
