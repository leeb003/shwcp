<?php
/**
 * WCP Class for ajax requests, extends main class
 */

    class wcp_ajax extends main_wcp {
        // properties

        // methods

        /**
         * Frontend handle request
         **/
        public function myajax_wcpfrontend_callback() {
			global $wpdb;
			// nonce and logged in check
			$nonce = isset($_POST['nextNonce']) ? $_POST['nextNonce'] : '';

			// $postID and $current_db are necessary for many of the actions below
			$postID = isset($_POST['postID']) ? intval($_POST['postID']) : '';
			$current_db = '';
			$saved_db = get_post_meta($postID, 'wcp_db_select', true);
			if ($saved_db
				&& $saved_db != 'default'
			) {
				$current_db = '_' . $saved_db;
			}

			$shwcp_upload     = $this->shwcp_upload     . $current_db;
			$shwcp_upload_url = $this->shwcp_upload_url . $current_db;
			
			$this->load_db_options($postID);

			$logged_in = is_user_logged_in();
			$this->get_the_current_user();
			if (!wp_verify_nonce( $nonce, 'myajax-next-nonce' ) 
				|| !$logged_in ) {

				$response['logged_in'] = 'false';
				header( "Content-Type: application/json" );
            	echo json_encode($response);
				wp_die();
			}

			// only allow users with access to ajax posting
			if (!$this->can_edit
				&& !$this->can_access 
			) {
				$response['logged_in'] = 'No Access';
				header( "Content-Type: application/json" );
                echo json_encode($response);
                wp_die();
			}

			// Logging class
            require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-logging.php');
            $wcp_logging = new wcp_logging();

			// delete leads verify
			if (isset($_POST['delete_all_checked']) && $_POST['delete_all_checked'] == 'true') {
				$response['title'] = __('Deleting Multiple Entries', 'shwcp');
				$response['msg'] = __('You are about to delete multiple entries, are you sure?', 'shwcp');
				$response['confirm'] = __('Confirm Delete', 'shwcp');
				$response['cancel'] = __('Cancel', 'shwcp');

			// delete leads confirm
			} elseif (isset($_POST['delete_all_confirm']) && $_POST['delete_all_confirm'] == 'true') {
				// Get the sorting info for returning fields to frontend and updating the table and for checking required
                $sorting = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_number asc");
				$sst = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");

				$remove_entries = $_POST['remove_entries'];
				$remove = array();
				$i = 1;

				$environment = array(
                    'user_login' => $this->current_user->user_login,
                    'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                    'db_name'    => $this->first_tab['database_name'],
                    'settings'   => $this->first_tab,
                );

				foreach( $remove_entries as $k => $v) {
					// get entry info before deletion
					$output_fields = $wpdb->get_row( $wpdb->prepare(
                    	"
                    	SELECT l.*
                    	FROM $this->table_main l
                    	WHERE l.id = %d;
                    	",
                    $v
                	));
					$translated_fields = $this->shwcp_return_entry($output_fields, $v, $sorting, $sst);
					// action hook delete entry
                	do_action('wcp_del_entry_action', $translated_fields,$environment);
									
					$removed[$i] = $v;
					$wpdb->delete(
                    	$this->table_main,
                    	array(
                        	'id' => $v
                    	),
                    	array(
                        	'%d'
                    	)
                	);
					$i++;
				}

				$event = __('Deleted Multiple Entries', 'shwcp');
				$detail = __('Entry IDs', 'shwcp') . ' ' . implode(', ', $removed);
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);

				$response['removed'] = $removed;

			// show lead edit form - edit existing and add new leads
			} elseif (isset($_POST['edit_lead']) && $_POST['edit_lead'] == 'true' 
				|| isset($_POST['new_lead']) && $_POST['new_lead'] == 'true'
			) {
				$new = false;
				$lead_id = $_POST['lead_id'];

				// Manage Own can or can't change ownership
				$response['access'] = $this->current_access;
				$response['can_change_ownership'] = isset($this->second_tab['own_leads_change_owner']) 
					? $this->second_tab['own_leads_change_owner'] : 'no';


				if ('new' == $lead_id) {  // new lead check
					$new = true;
					// get a list of the existing columns to fill in for lead
                	$lead_pre = array();
                	foreach ($wpdb->get_col( "DESC " . $this->table_main, 0 ) as $column_name ) {
                   		$lead_pre[$column_name] = "";
                	}
				} else { // existing lead
					$lead_pre = $wpdb->get_row(
                    	"
                        	SELECT l.* FROM $this->table_main l WHERE l.id = $lead_id;
                    	"
                	);
				}
				
				$sorting = $wpdb->get_results (
                    "
                        SELECT * from $this->table_sort order by sort_ind_number asc
                    "
                );

				$sst = $wpdb->get_results (
					"
						SELECT * from $this->table_sst order by sst_order
					"
				);
				foreach ($sst as $k => $v) {
					$v->sst_name = stripslashes($v->sst_name);
				}

				// organize by sorting and add others at the end
            	foreach ($sorting as $k => $v) {
                	foreach ($lead_pre as $k2 => $v2) {
                    	if ($v->orig_name == $k2) {
                        	$lead[$k2] = $v2;
                    	}
                	}
            	}

				// filter editable fields
				$editable = array();
				foreach ($lead as $k => $v) {
					if (in_array($k, $this->field_noedit)) {
						// skip for now
					} else {
						$editable[$k] = $v;
					}
				}

				// get the translated field names
				$translated = array();
				foreach ($editable as $k => $v) {
					$v = stripslashes($v);

					$match = false;
					foreach ($sorting as $k2 => $v2) {
						if ($v2->orig_name == $k) {
							if ($v2->field_type == '7') {
								if ($v) {
									// WP Display format
                                    $display_date = '';
                                    if ($v != '0000-00-00 00:00:00') {
                                        $display_date = date("$this->date_format $this->time_format", strtotime($v));
                                    }
									$v = $display_date;
								}
							}
							$translated[$k]['value'] = $v;
							$translated[$k]['trans'] = stripslashes($v2->translated_name);
							$translated[$k]['type']  = stripslashes($v2->field_type);
							$match = true;
						} 
					}
					if (!$match) {  // this should not happen
						$translated[$k]['value'] = $v;
						$translated[$k]['trans'] = $k;
					}
				} 
				
				//$response['sorting'] = $sorting;
				//$response['data'] = $lead;
				$response['sst'] = $sst;
				$response['sorting'] = $sorting;
				$response['current_user'] = $this->current_user->data->user_login;
				$response['all_users'] = $this->get_all_wcp_users();
				$response['translated'] = $translated;
				if ($new) {
					$response['title'] = __('Add A New Entry', 'shwcp');
				} else {
					$response['title'] = __('Edit Entry Information', 'shwcp');
				}
				$response['save_button'] = __('Save', 'shwcp');
				$response['cancel_button'] = __('Cancel', 'shwcp');


			// Save Lead
			} elseif (isset($_POST['save_lead']) && $_POST['save_lead'] == 'true') {  // save the lead updates
				$new = false;
				$lead_id = $_POST['lead_id'];

				// Get the sorting info for returning fields to frontend and updating the table and for checking required
                $sorting = $wpdb->get_results (
                    "
                        SELECT * from $this->table_sort order by sort_number asc
                    "
                );

				$field_vals = $_POST['field_vals'];
				$dropdown_fields = isset($_POST['dropdown_fields']) ? $_POST['dropdown_fields'] : array();
				/* Check required fields */
				$field_checks = $this->field_checks($sorting, $field_vals);

				if ($field_checks['required_not_set']) {
					$response['required'] = $field_checks['required'];
					$response['required_msg'] = $field_checks['required_msg'];

				} else {  // continue
					if ('new' == $lead_id) {
						$new = true;
						$response['new'] = 'true';
						// setup image url to return for default or Settings default if set
						if ($this->first_tab['contact_image'] == 'true') {  // if display images set
							$response['contact_image_used'] = 'true';
						} else {
							$response['contact_image_used'] = 'false';
						}

						if (isset($this->first_tab['contact_image_url']) && $this->first_tab['contact_image_id'] !='' ) { 
                        	// Settings Default
                        	$image_id = intval($this->first_tab['contact_image_id']);
                        	$image_meta = wp_get_attachment_metadata($image_id);
                        	$full_file = $image_meta['file'];
                        	$file_fullname = basename( $full_file );

                        	$info = pathinfo($file_fullname);
                        	$file_name = basename($file_fullname,'.'. $info['extension']);
                        	$file_ext = $info['extension'];
                        	$small_image = $file_name . '_25x25' . '.' . $file_ext;
                        	$thumb = $shwcp_upload_url . '/' . $small_image;
                    	} else {   // preset default
                        	$thumb = SHWCP_ROOT_URL . '/assets/img/default_lead_th.png';
                    	}
						$response['default_thumb'] = $thumb;


					} else {
						$lead_id = intval($lead_id);
						$response['new'] = 'false';
					}

					/* date and time conversion for saving */
					foreach ($sorting as $k => $v) {
                        foreach ($field_vals as $k2 => $v2) {
                            $v2 = stripslashes($v2);
                            if ($k2 == $v->orig_name && $v->field_type == '7') {
								$orig_datetime = DateTime::createFromFormat("$this->date_format $this->time_format", $v2);
								if ($orig_datetime) {
									$new_datetime = $orig_datetime->format('Y-m-d H:i:s');
									$field_vals[$k2] = $new_datetime;
								}
							}
						}
					}

					// select value names - for sending back
					$l_source = sanitize_text_field($_POST['l_source']);
					$l_status = sanitize_text_field($_POST['l_status']);
					$l_type = sanitize_text_field($_POST['l_type']);

					$format = array();
					$where_format = array("%d");

					// add updated info
					if ($new) {
						$field_vals['creation_date'] = current_time( 'mysql' );
						$field_vals['created_by'] = $this->current_user->data->user_login;
					}
					$field_vals['updated_date'] = current_time( 'mysql' );
					$field_vals['updated_by'] = $this->current_user->data->user_login;

					// set the formats
					foreach ($field_vals as $f => $v) {
						if ($f == 'l_source'
							|| $f == 'l_status'
							|| $f == 'l_type'
					   	) {
							$format[] = '%d';
						} else {
							$format[] = '%s';
						}
					}
				
					if ($new) {   	// New Lead
						$wpdb->insert(
							$this->table_main,
							$field_vals,
							$format
						);
						$lead_id = $wpdb->insert_id;
					} else {		// Existing Lead
						$wpdb->update(
							$this->table_main,
							$field_vals,
							array( 'id' => $lead_id ),
							$format,
							$where_format
						);
					}

					$created_by = $wpdb->get_var("SELECT created_by FROM $this->table_main where id=$lead_id");
					$creation_date = $wpdb->get_var("SELECT creation_date FROM $this->table_main where id=$lead_id");
					$updated_info = get_userdata($this->current_user->ID);
					$updated_by = $updated_info->user_login;

					// add in the extra fields to send back
					$field_vals['id'] = $lead_id;
					$field_vals['created_by'] = $created_by;
					$field_vals['creation_date'] = $creation_date;

					foreach ($sorting as $k => $v) {	
						foreach ($field_vals as $k2 => $v2) {
							$v2 = stripslashes($v2);
							if ($k2 == $v->orig_name && $v->sort_active == '1') {
								if ($v->orig_name == 'l_source') {
									$output_fields[$k2] = $l_source;
								} elseif ( $v->orig_name == 'l_status') {
									$output_fields[$k2] = $l_status;
								} elseif ( $v->orig_name == 'l_type') {
									$output_fields[$k2] = $l_type;
								} elseif ( $v->orig_name == 'updated_by') {
									$output_fields[$k2] = $updated_by;  // translated value

								} elseif ( $v->orig_name == 'updated_date'
									|| $v->orig_name == 'creation_date'
									|| $v->field_type == '7'
								) {
									$display_date = date("$this->date_format $this->time_format", strtotime($v2));
									$output_fields[$k2] = $display_date;
							 	} elseif ( $v->field_type == '10') { // dropdown fields , send back translated value
                                    foreach ($dropdown_fields as $k3 => $v3) {
                                        if ($k3 == $v2) { 
                                            $output_fields[$k2] = $v3;
                                        }
                                    }

								} else {
									$output_fields[$k2] = $v2;
								}
							}
						}
					}
		
					$sst = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");	
					$environment = array(
                        'user_login' => $this->current_user->user_login,
						'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
						'db_name'    => $this->first_tab['database_name'],
                        'settings'   => $this->first_tab,
                    );
					// get all entry fields for sending ($output_fields above is filtered)
                    $all_fields = $wpdb->get_row( $wpdb->prepare(
                        "
                        SELECT l.*
                        FROM $this->table_main l
                        WHERE l.id = %d;
                        ",
                        $lead_id
                    ));
					if ($new) {
						$event = __('Added Entry', 'shwcp');
						// new entry translate fields and action hook
						$translated_fields = $this->shwcp_return_entry($all_fields, $lead_id, $sorting, $sst);
						do_action('wcp_add_entry_action', $translated_fields, $environment);
					} else {
						$event = __('Updated Entry', 'shwcp');
						// updated entry action hook
						$translated_fields = $this->shwcp_return_entry($all_fields, $lead_id, $sorting, $sst);
                        do_action('wcp_update_entry_action', $translated_fields,$environment); 
					}
					$output_string = implode(', ', $output_fields);
					$detail = __('Entry ID', 'shwcp') . ' ' . $lead_id . __(' Fields-> ', 'shwcp') . $output_string;
					$wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
					$response['lead_id'] = $lead_id;
					$response['output_fields'] = $output_fields;
					$response['shwcp_root_url'] = SHWCP_ROOT_URL;
					$response['sorting'] = $sorting;
				} // end continue check

			// Delete lead Confirm Prompt
			} elseif (isset($_POST['delete_lead']) && $_POST['delete_lead'] == 'true') {
				$lead_id = intval($_POST['lead_id']);
				$response['title'] = __('Confirm Removal Of The Following Entry', 'shwcp');
				$response['confirm_button'] = __('Remove Entry', 'shwcp');
			    $response['cancel_button'] = __('Cancel', 'shwcp');
				$response['lead_id'] = $lead_id;

			// Actual deletion of entry
			} elseif (isset($_POST['confirm_delete_lead']) && $_POST['confirm_delete_lead'] == 'true') {
				$lead_id = intval($_POST['lead_id']);
				$response['lead_id'] = $lead_id;
				// Get entry info before deletion
                $output_fields = $wpdb->get_row( $wpdb->prepare(
                    "
                    SELECT l.*
                    FROM $this->table_main l
                    WHERE l.id = %d;
                    ",
                    $lead_id
                ));

				$wpdb->delete(
					$this->table_main,
					array(
						'id' => $lead_id
					),
					array(
						'%d'
					)
				);
				$event = __('Deleted Entry', 'shwcp');
				$detail = __('Entry ID ', 'shwcp') . $lead_id;
				$wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);

				// Delete entry action hook
				$sorting = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_number asc");
				$sst = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");
				$environment = array(
                    'user_login' => $this->current_user->user_login,
                    'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                    'db_name'    => $this->first_tab['database_name'],
                    'settings'   => $this->first_tab,
                );
                $translated_fields = $this->shwcp_return_entry($output_fields, $lead_id, $sorting, $sst);
                do_action('wcp_del_entry_action', $translated_fields,$environment);

				$response['lead_id'] = $lead_id;

			// Frontend Sorting
            } elseif (isset($_POST['frontend_sort']) && $_POST['frontend_sort'] == 'true') { 

				$previous = $wpdb->get_results ("SELECT * from $this->table_sort");
				$delete = $wpdb->query("TRUNCATE TABLE $this->table_sort"); // empty table first
				$keepers = isset($_POST['keepers']) ? $_POST['keepers'] : array();
				$nonkeepers = isset($_POST['nonkeepers']) ? $_POST['nonkeepers'] : array();

				$sort_ind = 1;

				foreach ($keepers as $k => $v) {
					foreach ($previous as $k2 => $v2) { // previous sort individual number tracking
						if ($v2->orig_name == $v['orig_name']) {
							$sort_ind = $v2->sort_ind_number;
							$field_type = $v2->field_type;
							$required_input = $v2->required_input;
						}
					}
					$wpdb->insert(
						$this->table_sort,
						array(
							'orig_name' => $v['orig_name'],
							'translated_name' => $v['translated_name'],
							'sort_number' => $k,
							'sort_active' => 1,
							'sort_ind_number' => $sort_ind,
							'field_type' => $field_type,
							'required_input' => $required_input,
						),
						array(
							'%s',
							'%s',
							'%d',
							'%d',
							'%d',
							'%d'
						)
					);
				}

                foreach ($nonkeepers as $k => $v) {
					foreach ($previous as $k2 => $v2) { // previous sort individual number tracking
                        if ($v2->orig_name == $v['orig_name']) {
                            $sort_ind = $v2->sort_ind_number;
							$field_type = $v2->field_type;
                        }
                    }
                    $wpdb->insert(
                        $this->table_sort,
                        array(
                            'orig_name' => $v['orig_name'],
                            'translated_name' => $v['translated_name'],
                            'sort_number' => $k,
                            'sort_active' => 0,
							'sort_ind_number' => $sort_ind,
							'field_type' => $field_type
                        ),
                        array(
                            '%s',
                            '%s',
                            '%d',
                            '%d',
							'%d'
                        )
                    );
                }
				$event = __('Changed Frontend Sorting', 'shwcp');
                $detail = __('Modified Sort Results', 'shwcp');
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
				$response['message'] = 'success';

			// Manage Dropdowns
			} elseif (isset($_POST['manage_dropdowns']) && $_POST['manage_dropdowns'] == 'true') {
				$dropdowns = array();
				$lead_columns = $wpdb->get_results (
                	"
                    SELECT * from $this->table_sort order by sort_ind_number asc
                	"
            	);
				foreach ($lead_columns as $k => $v) {  // lead_columns from sort generated above
                	if ($v->field_type == '10') {
                    	$dropdowns[$v->orig_name] = $v->translated_name;
                	}
            	}
				$response['dropdowns'] = $dropdowns;

			// Select Specific Dropdown options
			} elseif (isset($_POST['dropdown_select']) && $_POST['dropdown_select'] == 'true') {
				$list = $_POST['dropdown_list'];
				$options = $wpdb->get_results (
					"
					SELECT * from $this->table_sst WHERE sst_type_desc='$list' order by sst_order asc
					"
				);
				$response['options'] = $options;

			// Save Dropdown options
			} elseif (isset($_POST['save_dropdown_options']) && $_POST['save_dropdown_options'] == 'true') {
				$sst_type_desc = $_POST['sst_type_desc'];
				// get the current sst_type for the dropdown
				$sst_type = $wpdb->get_var("SELECT sst_type FROM $this->table_sst where sst_type_desc='$sst_type_desc' limit 1");
				$new_options = array();
				$i = 1;
				foreach ($_POST['optlist'] as $k => $v) {
					// insert
					if ($v['action'] == 'add' ) {
						$wpdb->insert(
                            $this->table_sst,
                            array(
                                'sst_name' => $v['sst_name'],
                                'sst_type_desc' => $sst_type_desc,
                                'sst_type' => $sst_type,
                                'sst_default' => 0,
                                'sst_order' => $i,
                            ),
                            array(
                                '%s',
                                '%s',
                                '%d',
                                '%d',
                                '%d',
                            )
                        );
						$i++;
						$new_options[$v['unique']] = $wpdb->insert_id;
					// update
					} elseif ($v['action'] == 'update') {
						$wpdb->update(
                        	$this->table_sst,
                            array(
                            	'sst_order' => $i,
								'sst_name' => $v['sst_name']
                            ),
                            array(
                                'sst_id' => $v['sst_id']
                            ),
                            array( '%d', '%s' ),
                            array( '%d' )
                        );
						$i++;
					// delete
					} elseif ($v['action'] == 'delete') {
						$wpdb->delete(
							$this->table_sst,
							array( 'sst_id' => $v['sst_id']),
							array( '%d')
						);
						// don't add it
					}
				}
				$response['options'] = 'saved';
				$response['new_options'] = $new_options;
				$response['sst_type'] = $sst_type;

			// Manage Fields
			} elseif (isset($_POST['manage_fields']) && $_POST['manage_fields'] == 'true') { 
				$new_fields = array();
				// get a list of the previous sorting
				$last_sorting = array();
				$last_sorting = $wpdb->get_results (
                	"
                    	SELECT * from $this->table_sort
                	"
            	);
				$delete = $wpdb->query("TRUNCATE TABLE $this->table_sort"); // empty sorting table
				$response['last_sorting'] = $last_sorting;
				$i = 1; // sort_ind_number
				$c_inc = 1;
				foreach ($_POST['fieldlist'] as $k => $v) {
					if ($v['action'] == 'add') { 

						if ($v['field_type'] == '7') {  // Date picker, date time type
							$field_type = "datetime NOT NULL DEFAULT '000-00-00 00:00:00'";

						} elseif ($v['field_type'] == '10') { // Dropdown, integer for mapping to ssts
							$field_type = "bigint(20) NOT NULL";

						} else {
							// $field_type = "varchar(255) NOT NULL";
							// larger data accepted for custom fields, might slow down searches significantly but oh well
							$field_type = "text NOT NULL";
						}

						// get a list of the existing columns
						$column_names = $wpdb->get_col( "DESC " . $this->table_main, 0);
						//add new column, but check if it exists first and increment
						$extra_column = 'extra_column_' . $c_inc;
						while(in_array($extra_column, $column_names)) {
							$c_inc++;
							$extra_column = 'extra_column_' . $c_inc;
						}
						$new_fields[$i]['column'] = $extra_column;
						$new_fields[$i]['unique'] = $v['unique'];

						//$response['columns'] = $column_names;
						//$wpdb->query("ALTER TABLE $this->table_main add column $extra_column varchar(255) NOT NULL");
						$wpdb->query("ALTER TABLE $this->table_main add column $extra_column $field_type");
						$wpdb->insert(
							$this->table_sort,
							array(
								'orig_name' => $extra_column,
                            	'translated_name' => $v['trans_name'],
                            	'sort_number' => $k,
                            	'sort_active' => 0,
								'sort_ind_number' => $i,
								'field_type' => intval($v['field_type']),
							    'required_input' => intval($v['required'])
                        	),
                        	array(
                            	'%s',
                            	'%s',
								'%d',
                            	'%d',
                            	'%d',
								'%d',
								'%d'
                        	)
                    	);
						/* Dropdowns add to sst table with unique sst_type above 3 */
						if ($v['field_type'] == '10') {
							$query = "SELECT DISTINCT(sst_type) as sst_type FROM $this->table_sst ORDER BY sst_type DESC";
							$existing_sst = $wpdb->get_results ( $query );
							$sst_array = array();
							foreach ($existing_sst as $k2 => $v2) {
								$sst_array[] = $v2->sst_type;
							}
							$sst_inc = 4; // start with 4 (above the defaults)
							$response['sst_array'] = $sst_array;
							while(in_array($sst_inc, $sst_array)) {
								$sst_inc++;
							}
							$wpdb->insert(
                            	$this->table_sst,
                            	array(
                                	'sst_name' => 'Default',
                                	'sst_type_desc' => $extra_column,
                                	'sst_type' => $sst_inc,
									'sst_default' => 1,
                                	'sst_order' => 1
                            	),
                            	array( '%s','%s','%d','%d','%d' )
                        	);
                        	$insert_id = $wpdb->insert_id;
						}
							
						$i++;
						$c_inc++;
					} elseif ( $v['action'] == 'delete' ) {
						// delete the column and don't add it to sorting
						$existing_cols = $wpdb->get_col( "DESC " . $this->table_main, 0 );
						if (in_array($v['orig_name'], $existing_cols)) {
							$wpdb->query( "ALTER TABLE $this->table_main drop column {$v['orig_name']}" );
						}
						if ($v['field_type'] == '10') {  // delete from sst as well
							$wpdb->delete($this->table_sst, array( 'sst_type_desc' => $v['orig_name'] ), array('%s') );
						}

					} else { // update
						// Modify field types in main table if changed, just to avoid touching the builtin fields
						// we just won't even consider those fields even though we check for changed field_type from last sort
						$dont_change = array(
							'id', 
							'small_image', 
							'lead_files', 
							'creation_date', 
							'updated_date', 
							'created_by', 
							'updated_by', 
							'owned_by', 
							'l_source', 
							'l_status', 
							'l_type'
						); 

						if (!in_array($v['orig_name'], $dont_change) ) {
							// Check for a change from last sorting
							$last_field_type = 'na';
							foreach($last_sorting as $k2 => $v2) {
								if ( $v['orig_name'] == $v2->orig_name ) {
									$last_field_type = $v2->field_type;
								}
							}

							if ( ($last_field_type != $v['field_type']) 
								&& $last_field_type != 'na') { 
								$response['last_field_type'] = $last_field_type;
                            	$response['orig_name'] = $v['orig_name'];
						
								if ($v['field_type'] == '7') {  // changed to date time type
                            		$field_type = "datetime NOT NULL DEFAULT '000-00-00 00:00:00'";
									$wpdb->query("ALTER TABLE $this->table_main modify column {$v['orig_name']} $field_type");
									if ($last_field_type == '10') { // need to remove from sst
										 $wpdb->delete($this->table_sst, array( 'sst_type_desc' => $v['orig_name'] ), array('%s') );
									}
                        		} elseif ( $last_field_type == '7' 
									&& $v['field_type'] != '10' 
								) {  // changed from date time to varchar
                            		$field_type = "text NOT NULL";
									$wpdb->query("ALTER TABLE $this->table_main modify column {$v['orig_name']} $field_type");

                        		} elseif ( $last_field_type == '10' ) { // changed from dropdown to something else, delete sst
									$field_type = "text NOT NULL";
                                    $wpdb->query("ALTER TABLE $this->table_main modify column {$v['orig_name']} $field_type");
									$wpdb->delete($this->table_sst, array( 'sst_type_desc' => $v['orig_name'] ), array('%s') );

								} elseif ( $v['field_type'] == '10' ) { // to dropdown, need to create it and alter
									$field_type = "bigint(20) NOT NULL";
									$wpdb->query("ALTER TABLE $this->table_main modify column {$v['orig_name']} $field_type");
									$query = "SELECT DISTINCT(sst_type) as sst_type FROM $this->table_sst ORDER BY sst_type DESC";
                            		$existing_sst = $wpdb->get_results ( $query );
                            		$sst_array = array();
                            		foreach ($existing_sst as $k2 => $v2) {
                                		$sst_array[] = $v2->sst_type;
                           	 		}
                            		$sst_inc = 4; // start with 4 (above the defaults)
                            		while(in_array($sst_inc, $sst_array)) {
                                		$sst_inc++;
                            		}
                            		$wpdb->insert(
                                		$this->table_sst,
                                		array(
                                    		'sst_name' => 'Default',
                                    		'sst_type_desc' => $v['orig_name'],
                                    		'sst_type' => $sst_inc,
                                    		'sst_default' => 1,
                                    		'sst_order' => 1
                                		),
                                		array( '%s','%s','%d','%d', '%d' )
                            		);
								}
							}
						}


						// update sorting
						$sort_active = 0;
						$sort_front = 0;
						// get the current active status
						foreach($last_sorting as $k2 => $v2) {
							if ( $v['orig_name'] == $v2->orig_name ) {
								$sort_active = $v2->sort_active;
								$sort_front = $v2->sort_number;
							}
						}
						$wpdb->insert(
                            $this->table_sort,
                            array(
                                'orig_name' => $v['orig_name'],
                                'translated_name' => $v['trans_name'],
                                'sort_number' => $sort_front,
                                'sort_active' => $sort_active,
								'sort_ind_number' => $i,
								'field_type' => intval($v['field_type']),
								'required_input' => intval($v['required'])

                            ),
                            array(
                                '%s',
                                '%s',
								'%d',
                                '%d',
                                '%d',
								'%d',
								'%d'
                            )
                        );
						$i++;
					}
				}
				$event = __('Changed Fields', 'shwcp');
                $detail = __('Modified Entry Fields ', 'shwcp');
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
				$response['message'] = 'managing fields';
				$response['new_fields'] = $new_fields;
			// Save SST Page	
			} elseif (isset($_POST['sst_fields']) && $_POST['sst_fields'] == 'true') {  
				global $wpdb;
				$source = $_POST['source'];
				$status = $_POST['status'];
				$type = $_POST['type'];
				$new_entries = array();
				// get the defaults to assign removed sst leads associated to
				$default_source = $wpdb->get_row("SELECT * FROM $this->table_sst where sst_type=1 AND sst_default=1");
				$default_status = $wpdb->get_row("SELECT * FROM $this->table_sst where sst_type=2 AND sst_default=1"); 
				$default_type   = $wpdb->get_row("SELECT * FROM $this->table_sst where sst_type=3 AND sst_default=1");
				$i = 0;
				foreach ($source as $k => $v) {
					if ($v['remove'] == 'true') {
						if (is_numeric($v['id'])) {
							$wpdb->delete($this->table_sst, array( 'sst_id' => $v['id'] ), array('%d') );
							// update leads to default
							$wpdb->update(
								$this->table_main,
								array(
									'l_source' => $default_source->sst_id
								),
								array(
									'l_source' => $v['id']
								),
								array( '%d' ),
								array( '%d' )
							);

						}

					} else if ($v['id'] == 'new') { 
						// new entry
						$wpdb->insert(
							$this->table_sst,
							array(
								'sst_name' => $v['name'],
								'sst_type_desc' => 'l_source',
								'sst_type' => '1',
								'sst_order' => $k
							), 
							array( '%s','%s','%d','%d' )
						);
						$insert_id = $wpdb->insert_id;
						$new_entries[$i]['unique'] = $v['unique'];
						$new_entries[$i]['id'] = $insert_id;
						$i++;
					} else {
						// update source
						$wpdb->update(
							$this->table_sst,
							array(
								'sst_name' => $v['name'],
								'sst_order' => $k
							),
							array( 'sst_id' => $v['id'] ),
							array( '%s', '%d' ),
							array( '%d' )
						);
					}
				}
				foreach ($status as $k => $v) {
					if ($v['remove'] == 'true') {
						if (is_numeric($v['id'])) {
                        	$wpdb->delete($this->table_sst, array( 'sst_id' => $v['id'] ), array('%d') );
							// update leads to default
                            $wpdb->update(
                                $this->table_main,
                                array(
                                    'l_status' => $default_status->sst_id
                                ),
                                array(
                                    'l_status' => $v['id']
                                ),
                                array( '%d' ),
                                array( '%d' )
                            );
						}

                    } else if ($v['id'] == 'new') {
                        // new entry
                        $wpdb->insert(
                            $this->table_sst,
                            array(
                                'sst_name' => $v['name'],
                                'sst_type_desc' => 'l_status',
                                'sst_type' => '2',
                                'sst_order' => $k
                            ),
                            array( '%s','%s','%d','%d' )
                        );
						$insert_id = $wpdb->insert_id;
                        $new_entries[$i]['unique'] = $v['unique'];
                        $new_entries[$i]['id'] = $insert_id;
                        $i++;

                    } else {
                        // update status
                        $wpdb->update(
                            $this->table_sst,
                            array(
                                'sst_name' => $v['name'],
                                'sst_order' => $k
                            ),
                            array( 'sst_id' => $v['id'] ),
                            array( '%s', '%d' ),
                            array( '%d' )
                        );
                    }
                }
				foreach ($type as $k => $v) {
					if ($v['remove'] == 'true') {
						if (is_numeric($v['id'])) {
                        	$wpdb->delete($this->table_sst, array( 'sst_id' => $v['id'] ), array('%d') );
							// update leads to default
                            $wpdb->update(
                                $this->table_main,
                                array(
                                    'l_type' => $default_type->sst_id
                                ),
                                array(
                                    'l_type' => $v['id']
                                ),
                                array( '%d' ),
                                array( '%d' )
                            );
						}

                    } else if ($v['id'] == 'new') {
                        // new entry
                        $wpdb->insert(
                            $this->table_sst,
                            array(
                                'sst_name' => $v['name'],
                                'sst_type_desc' => 'l_type',
                                'sst_type' => '3',
                                'sst_order' => $k
                            ),
                            array( '%s','%s','%d','%d' )
                        );
						$insert_id = $wpdb->insert_id;
                        $new_entries[$i]['unique'] = $v['unique'];
                        $new_entries[$i]['id'] = $insert_id;
                        $i++;

                    } else {
                        // update type
                        $wpdb->update(
                            $this->table_sst,
                            array(
                                'sst_name' => $v['name'],
                                'sst_order' => $k
                            ),
                            array( 'sst_id' => $v['id'] ),
                            array( '%s', '%d' ),
                            array( '%d' )
                        );
                    }
                }

				$event = __('Modified SST', 'shwcp');
                $detail = __('Modified Sources, Status, and Types', 'shwcp');
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);

				//$response['source'] = $source;
				$response['new_entries'] = $new_entries;
				$response['message'] = 'hello';

			// handle small image upload	
			} elseif (isset($_POST['upload_small_image']) && $_POST['upload_small_image'] == 'true' ) {
				if (empty($_FILES) || $_FILES["file"]["error"]) {
					$response['message'] = __('Error uploading', 'shwcp');
				} else {
					$lead_id = intval($_POST['lead_id']);
					//$file_name = $_FILES["file"]["name"];
					$imagetypes = array(
    					'image/png' => '.png',
    					'image/gif' => '.gif',
    					'image/jpeg' => '.jpg',
    					'image/bmp' => '.bmp'
					);
					$file_ext = $imagetypes[$_FILES["file"]["type"]];
					$file_name = $lead_id . '-' . 'small_image' . $file_ext;
					// clean up any other lead small image files in the directory first, not needed since we replace but 
					// reference for other places
					//array_map('unlink', glob($shwcp_upload . '/' . $lead_id . '-*.*'));

					$new_file = $shwcp_upload . '/' . $file_name;
					$new_file_url = $shwcp_upload_url . '/' . $file_name;
					move_uploaded_file($_FILES["file"]["tmp_name"], $new_file);
					$thumb = wp_get_image_editor( $new_file );
					if ( !is_wp_error($thumb) ) {
						$thumb->resize(25, 25, true);
						$thumb->save( $shwcp_upload . '/' . $lead_id . '-' . 'small_image_th' . $file_ext);
					}
					$wpdb->update(
                        $this->table_main,
                        array( 'small_image' => $file_name ),
                        array( 'id' => $lead_id ),
                        array( '%s' ),
						array( '%d' )
                    );

					//$file_name
					$event = __('Uploaded Image', 'shwcp');
                	$detail = __('New Image set for Entry ID ', 'shwcp') . $lead_id;
                	$wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
					// new photo fields and action hook
					$environment = array(
                        'user_login' => $this->current_user->user_login,
                        'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                        'db_name'    => $this->first_tab['database_name'],
                        'settings'   => $this->first_tab,
                    );
                    do_action('wcp_photo_entry_action', $lead_id, $new_file_url, $environment);
					$response['new_file_url'] = $new_file_url;
					$response['post'] = $_POST;
					$response['file'] = $file_name;
					$response['message'] = 'Word...press';
				}

			// Upload CSV or Excel file for import, Step 1
			} elseif (isset($_POST['upload_import']) && $_POST['upload_import'] == 'true') {
				if (empty($_FILES) || $_FILES["file"]["error"]) {
                    $response['message'] = __('Error uploading', 'shwcp');
                } else {
					$fileType = $_FILES['file']['type']; //Obtain file type, returns "image/png", image/jpeg, text/plain etc
					switch($fileType) {
                    	case 'text/csv' :                   // csv's 
                    	case 'application/vnd.ms-excel':
						case 'text/comma-separated-values':
                    	case 'text/plain':
                    	case 'text/tsv':
                        	$extension = '.csv';
                        	break;

                    	case 'application/vnd.ms-excel' :   // Excel
                    	case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' :
                        	$extension = '.xlsx';
                        	break;

                    	default :
                        	$extension = 'unsupported';
                        	$error = 'Unsupported file type.  Use file type csv, xls, or xlsx';
	                }
					$file_name = 'import' . $extension;

					/* Examine first row for column names */
                    $new_file = $shwcp_upload . '/' . $file_name;
                    $new_file_url = $shwcp_upload_url . '/' . $file_name;
					$orig_name = $_POST['name'];
                    move_uploaded_file($_FILES["file"]["tmp_name"], $new_file);

					require_once SHWCP_ROOT_PATH . '/includes/PHPExcel/Classes/PHPExcel.php';
                    $inputFileName = $new_file;
                    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                    $objPHPExcel = $objReader->load($inputFileName);
                    $worksheet = $objPHPExcel->getActiveSheet();

					$topRow = 1; // Get the first row only for the columns
					$topColumns = $worksheet->rangeToArray('A' . $topRow . ':' . $worksheet->getHighestColumn() . $topRow);

					$lastRow = $objPHPExcel->getActiveSheet()->getHighestRow();  // another way to get total
		
					// Get the actual db columns to return for selection
                    global $wpdb;
                    $sorting = $wpdb->get_results ("SELECT * from $this->table_sort");
				
					$response['new_file'] = $new_file;	
					$response['none'] = __('No Assignment', 'shwcp');
					$response['continue'] = __('Import Now', 'shwcp');
					$response['step'] = __('Step 2, Assign your spreadsheet columns to <b>WP Contacts</b> columns', 'shwcp');
					$response['fields'] = $sorting;
					$response['topColumns'] = $topColumns;
					$response['totalRows'] = $lastRow;
					$response['totalRowText'] = __('Total Rows to import', 'shwcp');
					$response['completeText'] = __('Complete', 'shwcp'); // Sent in this step since next step sends increments
																		 // stored in hidden div
					/* End examine first row */

					// logging
					$event = __('Imported Entry File', 'shwcp');
                    $detail = __('File Name: ', 'shwcp') .  $orig_name;
                    $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
                    $response['new_file_url'] = $new_file_url;
                    $response['post'] = $_POST;
                    $response['file'] = $orig_name;
                    $response['message'] = 'ok';
                }

			// Upload CSV or Excel file for import, Step 3 process file
			} elseif (isset($_POST['import_step3']) && $_POST['import_step3'] == 'true') {
				$fieldMap = isset($_POST['fieldMap']) ? $_POST['fieldMap'] : array();
				$new_file = $_POST['new_file_loc'];

				// check to make sure some are selected
        		$selected = false;
        		foreach($fieldMap as $k => $v) {
            		if ($v['dbCol'] != 'not-assigned') {
                		$selected = true;
					}
				}

				if (!$selected) {
					header( "Content-Type: application/json" );
					header('Cache-Control: no-cache');
					$response['error'] = true;
					$response['errormsg'] = __('You have not assigned any of your columns, you need to set at least one.', 'shwcp');
					$response['title'] = __('Error', 'shwcp');
					$response['dismiss_button'] = __('Close', 'shwcp');
					echo json_encode($response);
					flush();
                    ob_flush();
					exit;

				} else {  // Read the file and import the leads
					$column_map = array();
					$total_rows = intval($_POST['totalRows']);
					$chunks = $total_rows / 10;
					$chunks = ceil($chunks);
					if ($total_rows <= 100) {
						$chunks = 100;

					} elseif ( $total_rows >= 10000) {
						$chunks = 1000;

					} elseif ($total_rows >= 5000 && $total_rows < 10000) {
						$chunks = 400;

					} elseif ($total_rows >= 2000 && $total_rows < 5000) {
						$chunks = 200;
					}

					// start importing
					require_once SHWCP_ROOT_PATH . '/includes/PHPExcel/Classes/PHPExcel.php';
					// use the chunk read filter class
					require_once SHWCP_ROOT_PATH . '/includes/class-chunk-read-filter.php';
                    $inputFileName = $new_file;
                    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
					$chunkSize = $chunks;
					$chunkFilter = new chunkReadFilter();
					$objReader->setReadFilter($chunkFilter);
					/**  Advise the Reader that we only want to load cell data  **/
					//$objReader->setReadDataOnly(true);

					/**  Loop to read our worksheet in "chunk size" blocks  **/ 
					$i = 1;
					header( 'Content-type: text/html; charset=utf-8' );
		            header('Cache-Control: no-cache');
					for ($startRow = 2; $startRow <= $total_rows; $startRow += $chunkSize) { 
						echo ',' . $startRow;
                        flush();
                        ob_flush();
    					/**  Tell the Read Filter which rows we want this iteration  **/ 
    					$chunkFilter->setRows($startRow,$chunkSize); 
    					/**  Load only the rows that match our filter  **/ 
    					$objPHPExcel = $objReader->load($inputFileName); 
    					//    Do some processing here 
						$worksheet = $objPHPExcel->getActiveSheet();
						foreach ($worksheet->getRowIterator() as $row) {
							// only process rows in this batch for incrementing
							// even though the others aren't loaded into memory, the spreadsheet still reads to the start number
							// each time it loads up for the new batch
							if ( $chunkFilter->readCell(0, $row->getRowIndex()) && $row->getRowIndex() != 1 ) {
                        		$i++;
                        		$col = 0;
                        		//echo 'Row number: ' . $row->getRowIndex() . "\r\n";
                        		$cellIterator = $row->getCellIterator();
                        		$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                        		foreach ($cellIterator as $cell) {
                            		if (!is_null($cell)) {
										$cell_val = $cell->getValue();
										if(PHPExcel_Shared_Date::isDateTime($cell)) {  // Check if it's an excel formatted date
     										$cell_val = date('Y-m-d H:i:s', PHPExcel_Shared_Date::ExcelToPHP($cell_val)); 
										}
                                   		foreach($fieldMap as $k => $v) {
                                       		if ($k == $col) { // Importing this column, add to import array
                                           		$column_map[$i][$v['dbCol']] = trim($cell_val);
                                       		}
                                   		}
                                   		$col++;
                            		}
                        		}		
							}
						}
						// This is where we import the chunk to the database and clear the array
						//print_r($column_map);
						// get the default source status and type in case they aren't uploading these
						$default_source = $wpdb->get_row("SELECT * FROM $this->table_sst where sst_type=1 AND sst_default=1");
                		$default_status = $wpdb->get_row("SELECT * FROM $this->table_sst where sst_type=2 AND sst_default=1");
                		$default_type   = $wpdb->get_row("SELECT * FROM $this->table_sst where sst_type=3 AND sst_default=1");
						$sorting        = $wpdb->get_results ("SELECT * from $this->table_sort");

						// build our insert statement
						foreach ($column_map as $row => $data) {
							$insert_array = array();
							// need to update sst each time in case new ones are added
							$sst = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");
							// defaults for sst if not set, will override if they are in the loops below
							$insert_array['l_source'] = $default_source->sst_id;
							$insert_array['l_status'] = $default_status->sst_id;
							$insert_array['l_type']   = $default_type->sst_id;

							foreach ($data as $k => $v) {
								$v = trim($v);
								if ($k == 'l_source') {        // Source
									if ($v != '') {
										$insert_array['l_source'] = $this->sst_update_db($v, $sst,'l_source', 1);
									}
								
								} elseif ($k == 'l_status') {  // Status
									if ($v != '') {
										$insert_array['l_status'] = $this->sst_update_db($v, $sst,'l_status', 2);
									}
				
								} elseif ($k == 'l_type') {    // Type
									if ($v != '') {
										$insert_array['l_type'] = $this->sst_update_db($v, $sst,'l_type', 3);
									}
								} else {                        // All other custom and string entries
									//check for datetime column
									$field_type = 'na';
                            		foreach($sorting as $k2 => $v2) {
                                		if ( $k == $v2->orig_name ) {
                                    		$field_type = $v2->field_type;
										}
                                	}
									if ($field_type == '7') { // datetime field and we need to try and convert
										$timestamp = strtotime($v);
										$datetime_format = date("Y-m-d H:i:s", $timestamp);
										$insert_array[$k] = $datetime_format;

									} elseif ($field_type == '10') { // dropdown type field need id or create
										$sst_type = $wpdb->get_var(
											"SELECT sst_type FROM $this->table_sst where sst_type_desc='$k' limit 1"
										);
										$real_value = $this->sst_update_db($v, $sst, $k, $sst_type);
										$insert_array[$k] = $real_value;
	
									} else {  // all other varchar fields
										$insert_array[$k] = $v;
									}
								}
							}
							$insert_array['created_by']    = $this->current_user->data->user_login;
							$insert_array['updated_by']    = $this->current_user->data->user_login;
							$insert_array['owned_by']      = $this->current_user->data->user_login;
							$insert_array['creation_date'] = current_time( 'mysql' );
							$insert_array['updated_date']  = current_time( 'mysql' );

							// Actual insert and clear array
							// set the formats
							//print_r($insert_array);
                			foreach ($insert_array as $f => $v) {
                    			if ($f == 'l_source'
                        			|| $f == 'l_status'
                        			|| $f == 'l_type'
                       			) {
                        			$format[] = '%d';
                    			} else {
                        			$format[] = '%s';
                    			}
                			}

                    		$wpdb->insert(
                        		$this->table_main,
                        		$insert_array,
                        		$format
                    		);
                    		//$lead_id = $wpdb->insert_id;
							$insert_array = array();
							$format = array();
						} // end foreach column_map loop
						$column_map = array();
					} 
					echo ',' . $i;
					flush();
                    ob_flush();

					//print_r($column_map);	
					// This is where we delete the file after processing
					unlink($new_file);
					exit;
				}

			// Export no fields selected response
			} elseif (isset($_POST['export_nofields']) && $_POST['export_nofields'] == 'true') {
				$response['body'] =  __('Please select at least 1 field for exporting.', 'shwcp');
				$response['title'] = __('Select Fields', 'shwcp');
				$response['cancel_button'] = __('OK', 'shwcp');

			/* MailChimp api functionality */
			} elseif (isset($_POST['mail_chimp']) && $_POST['mail_chimp'] == 'true') {
				$api_key         = trim($_POST['api_key']);
				$list            = trim($_POST['list']);
				$email_field     = isset($_POST['email_field']) ? $_POST['email_field'] : '';
				$firstname_field = isset($_POST['firstname_field']) ? $_POST['firstname_field'] : '';
				$lastname_field  = isset($_POST['lastname_field']) ? $_POST['lastname_field'] : '';
				$response['select_label'] = __("Select Your MailChimp list to import into", 'shwcp');
				$response['email_label'] = __("Email Address Field", 'shwcp');
				$response['firstname_label'] = __("First Name Field", 'shwcp');
				$response['lastname_label'] = __("Last Name Field", 'shwcp');
				$response['select_choose'] = __('Choose A Field', 'shwcp');
				$response['confirm'] = __('Ready to send to MailChimp, click confirm to subscribe entries to your list.', 'shwcp');
				$response['confirm_submit'] = __('Confirm Submission', 'shwcp');
				require_once SHWCP_ROOT_PATH . '/includes/mail-chimp/MailChimp.php';
                $MailChimp = new MailChimp($api_key);
				if ($api_key) {
					if ($email_field != '') {  // confirmation step
						$response['confirm_ready'] = 'true';
						$response['email_field'] = $email_field;
						$response['firstname_field'] = $firstname_field;
						$response['lastname_field'] = $lastname_field;
					}

					$response['selected_list'] = $list;
					$response['lists'] = $MailChimp->get('lists');
					$fields = array();
					$sorting = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_ind_number asc");
					foreach ($sorting as $k => $v) {
						if ($v->orig_name != 'id') {
							$fields[$k] = $v;
						}
					}
					$response['fields'] = $fields;
					$response['lists'] = $MailChimp->get('lists');
				} else {
					$response['nothing'] = 'true';
				}

			// submit it
			} elseif (isset($_POST['mail_chimp_conf']) && $_POST['mail_chimp_conf'] == 'true') {
				$api_key         = trim($_POST['api_key']);
                $list            = trim($_POST['list']);
                $email_field     = isset($_POST['email_field']) ? $_POST['email_field'] : '';
                $firstname_field = isset($_POST['firstname_field']) ? $_POST['firstname_field'] : '';
                $lastname_field  = isset($_POST['lastname_field']) ? $_POST['lastname_field'] : '';
				require_once SHWCP_ROOT_PATH . '/includes/mail-chimp/MailChimp.php';
                $MailChimp = new MailChimp($api_key);
				$owner_only = '';
				if ($this->current_access == 'ownleads') {
                    $user_login = $this->current_user->user_login;
                    $owner_only = "and owned_by='$user_login'";
                }
				// get all fields where email is not blank (<>)
				$firstname = '';
				$lastname = '';
				if ($firstname_field) {
					$firstname = ", $firstname_field";
				}
				if ($lastname_field) {
					$lastname = ", $lastname_field";
				}
				$fields = $wpdb->get_results (
					"
					 SELECT " . $email_field . $firstname . $lastname . " FROM $this->table_main 
					 WHERE $email_field <> '' $owner_only
					", ARRAY_A
				);
				foreach ($fields as $k => $v) {
					$firstname_m = isset($v[$firstname_field]) ? $v[$firstname_field] : ''; 
					$lastname_m = isset($v[$lastname_field]) ? $v[$lastname_field] : '';;
					$result[] = $MailChimp->post('lists/' . $list . '/members', array(
						'email_address' => $v[$email_field],
						'status'        => 'subscribed',
						'merge_fields'  => array('FNAME'=> $firstname_m, 'LNAME' => $lastname_m),
					));
				}
				$response['status'] = __('Your entries have been submitted to MailChimp, below are the details', 'shwcp');
				$response['result'] = $result;


			/* End MailChimp api functionality */

			// Confirm Remove File
			} elseif (isset($_POST['remove_file_check']) && $_POST['remove_file_check'] == 'true') {
				$lead_id = intval($_POST['lead_id']);
				$lead_file = $_POST['lead_file'];
				$response['message'] = '<p class="remove-file-confirm leadID-' . $lead_id . '">'
					 			     . __('Are you sure you wish to remove the following file?', 'shwcp')
									 . ' <br /><br /><b><span class="file-remove-name">' . $lead_file 
									 . '</span></b></p>';
				$response['title'] = __('Confirm Remove File', 'shwcp');
				$response['cancel_button'] = __('Cancel', 'shwcp');
				$response['confirm_button'] = __('Confirm', 'shwcp');

			// Actually Remove File and update db
			} elseif (isset($_POST['remove_file_confirm']) && $_POST['remove_file_confirm'] == 'true') {
                $lead_id = intval($_POST['lead_id']);
				$lead_file = $_POST['lead_file'];
				$lead_dir = $shwcp_upload . '/' . $lead_id . '-files';
				if (file_exists($lead_dir . '/' . $lead_file)) {
					unlink($lead_dir . '/' . $lead_file);
					$response['removed'] = 'yes';
					$response['shwcp_upload_url'] = $shwcp_upload_url;
				    $response['lastMod'] = __('Last Modified', 'shwcp');
				} else {
					$response['removed'] = 'no';
				}
		
				$files = preg_grep('/^([^.])/', scandir($lead_dir));
                // get sizes and dates
                $files_info = array();
                $i = 0;
                foreach ($files as $k => $v) {
                    $files_info[$i]['name'] = $v;
                    $files_info[$i]['size'] = size_format(filesize($lead_dir . '/' . $v));
                    $files_info[$i]['date'] = date("m-d-Y H:i:s", filemtime($lead_dir . '/' . $v));
                    $i++;
                }
                
                $files_info_ser = serialize($files_info);
                $wpdb->update(
                    $this->table_main,
                    array( 'lead_files' => $files_info_ser ),
                    array( 'id' => $lead_id ),
                    array( '%s' ),
                    array( '%d' )
                );
                $response['files'] = $files_info;
				$event = __('Removed Entry File', 'shwcp');
                $detail = __('Entry ID ', 'shwcp') . $lead_id . __(' File:', 'shwcp') . $lead_file;
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
				// entry delete files action hook
                $environment = array(
                    'user_login' => $this->current_user->user_login,
                    'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                    'db_name'    => $this->first_tab['database_name'],
                    'settings'   => $this->first_tab,
                );
                $file_data = array(
                    'file_name' => $lead_file,
                    'file_url'  => $shwcp_upload_url . '/' . $lead_id . '-files' . '/' . $lead_file,
                );
                do_action('wcp_delfiles_entry_action', $lead_id, $file_data, $environment);


			// handle lead file uploads
			} elseif (isset($_POST['upload_lead_files']) && $_POST['upload_lead_files'] == 'true' ) {
				if (empty($_FILES) || $_FILES["file"]["error"]) {
					$response['message'] = __('Error uploading', 'shwcp');
				} else {
					$lead_id = intval($_POST['lead_id']);
					$file_name = preg_replace('/\s+/', '', $_FILES["file"]["name"]);
					//Make the lead directory if needed
					$lead_dir = $shwcp_upload . '/' . $lead_id . '-files';
					if ( !file_exists($lead_dir) ) {
                		wp_mkdir_p($lead_dir);
            		}
					move_uploaded_file($_FILES["file"]["tmp_name"], $lead_dir . '/' . $file_name);
					// get file info in directory and update db (ignore dot files and dir)
					$files = preg_grep('/^([^.])/', scandir($lead_dir));
					// get sizes and dates
					$files_info = array();
					$i = 0;
					$files_added = '';
					foreach ($files as $k => $v) {
						if ($v == $file_name) { // we need to send this info back too
							$files_info[$i]['name'] = $v;
							$file['size'] =  $files_info[$i]['size'] = size_format(filesize($lead_dir . '/' . $v));
							$file['date'] = $files_info[$i]['date'] = date("m-d-Y H:i:s", filemtime($lead_dir . '/' . $v));
						} else {
							$files_info[$i]['name'] = $v;
							$files_info[$i]['size'] = size_format(filesize($lead_dir . '/' . $v));
							$files_info[$i]['date'] = date("m-d-Y H:i:s", filemtime($lead_dir . '/' . $v));
						}
						$i++;
					}
				
					$files_info_ser = serialize($files_info);
					$wpdb->update(
						$this->table_main,
						array( 'lead_files' => $files_info_ser ),
						array( 'id' => $lead_id ),
						array( '%s' ),
						array( '%d' )
					);
					$event = __('Added Files', 'shwcp');
               		$detail = __('Entry ID ', 'shwcp') . $lead_id . __(' New File:', 'shwcp') . $file_name;
                	$wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
					// entry files action hook
                    $environment = array(
                        'user_login' => $this->current_user->user_login,
                        'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                        'db_name'    => $this->first_tab['database_name'],
                        'settings'   => $this->first_tab,
                    );
					$file_data = array(
						'file_name' => $file_name,
						'file_url'  => $shwcp_upload_url . '/' . $lead_id . '-files' . '/' . $file_name,
						'file_size' => $file['size']
					);
                    do_action('wcp_addfiles_entry_action', $lead_id, $file_data, $environment);
					//$response['files'] = $files_info;
					$response['lead_id'] = $lead_id;
					$response['file_name'] = $file_name;
					$response['file_size'] = $file['size'];
					$response['file_date'] = $file['date'];
					$response['lastMod'] = __('Last Modified', 'shwcp');
					$response['file_url'] = $shwcp_upload_url;
				}

			// Add Note for lead
			} elseif (isset($_POST['add_note']) && $_POST['add_note'] == 'true' ) {
				$lead_id = intval($_POST['lead_id']);
				$response['lead_id'] = $lead_id;
				$response['title'] = __('Add A Note', 'shwcp');
				$response['cancel_button'] = __('Cancel', 'shwcp');
				$response['confirm_button'] = __('Save Note', 'shwcp');

			// Save the note for lead
			} elseif (isset($_POST['save_note']) && $_POST['save_note'] == 'true' ) {
				$lead_id    = intval($_POST['lead_id']);
				$note       = wp_kses_post($_POST['note']);
				$date_added = current_time( 'mysql' );
                $creator    = $this->current_user->ID;

				$wpdb->insert(
					$this->table_notes,
					array(
                        'lead_id' => $lead_id,
                        'note_content' => $note,
                        'date_added' => $date_added,
                        'creator' => $creator
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s',
                        '%d'
                    )
				);
				$note_id = $wpdb->insert_id;
				$event = __('Added a note', 'shwcp');
                $detail = __('Entry ID ', 'shwcp') . $lead_id;
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
				// entry add note action hook
                $environment = array(
                    'user_login' => $this->current_user->user_login,
                    'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                    'db_name'    => $this->first_tab['database_name'],
                    'settings'   => $this->first_tab,
                );
                do_action('wcp_addnote_entry_action', $lead_id, $note, $note_id, $environment);

				$response['lead_id'] = $lead_id;
				$response['note_id'] = $note_id;
				$response['note'] = stripslashes($note);
				$response['date_added'] =  date("$this->date_format $this->time_format", strtotime($date_added));
				$response['creator'] = $this->current_user->user_login;

			// Edit note modal
			} elseif (isset($_POST['edit_note']) && $_POST['edit_note'] == 'true' ) {
				$lead_id = intval($_POST['lead_id']);
				$note_id = intval($_POST['note_id']);
				$existing_note = $wpdb->get_results (
                " 
                    SELECT notes.*, user.user_login
                    from $this->table_notes notes, {$wpdb->base_prefix}users user
                    WHERE notes.id=$note_id and notes.creator=user.ID
                "
            	);
				$response['title'] = __('Edit Note', 'shwcp');
				$response['cancel_button'] = __('Cancel', 'shwcp');
				$response['confirm_button'] = __('Save Note', 'shwcp');
				$response['lead_id'] = $lead_id;
				$response['note_id'] = $note_id;
				$response['note'] = stripslashes($existing_note[0]->note_content);

			// Save the edited note
			} elseif (isset($_POST['save_edit_note']) && $_POST['save_edit_note'] == 'true') {
				$note_id = intval($_POST['note_id']);
				$lead_id = intval($_POST['lead_id']);
				$note    = wp_kses_post($_POST['note']);
				$date_updated = current_time( 'mysql' );
				$updater      = $this->current_user->ID;
				$wpdb->update(
					$this->table_notes,
					array(
						'note_content' => $note,
						'updater'      => $updater,
						'date_updated' => $date_updated
					),
					array( 'id' => $note_id ),
					array(
						'%s',
						'%d',
						'%s'
					),
					array('%d')
				);
                $event = __('Edited existing note', 'shwcp');
                $detail = __('Entry ID ', 'shwcp') . $lead_id;
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
				// entry update note action hook
                $environment = array(
                    'user_login' => $this->current_user->user_login,
                    'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                    'db_name'    => $this->first_tab['database_name'],
                    'settings'   => $this->first_tab,
                );
                do_action('wcp_updatenote_entry_action', $lead_id, $note, $note_id, $environment);
				
				$response['note_id'] = $note_id;
				$response['note'] = stripslashes($note);

			// Remove Note Confirm
			} elseif (isset($_POST['remove_note']) && $_POST['remove_note'] == 'true' ) {
				$lead_id = intval($_POST['lead_id']);
				$note_id = intval($_POST['note_id']);
				$response['note_id'] = $note_id;
				$response['title'] = __('Remove Note', 'shwcp');
				$response['body'] = __('Are you sure you wish to delete this note?', 'shwcp');
            	$response['cancel_button'] = __('Cancel', 'shwcp');
            	$response['confirm_button'] = __('Delete Note', 'shwcp');

			// Remove Note
			} elseif (isset($_POST['remove_this_note']) && $_POST['remove_this_note'] == 'true') {
				$note_id = intval($_POST['note_id']);
				$lead_id = intval($_POST['lead_id']);
				// get note content first
				$note = $wpdb->get_var("SELECT note_content FROM $this->table_notes WHERE id=$note_id");

				$wpdb->delete(
                    $this->table_notes,
                    array(
                        'id' => $note_id
                    ),
                    array(
                        '%d'
                    )
                );
				$event = __('Removed a note', 'shwcp');
                $detail = __('Entry ID ', 'shwcp') . $lead_id;
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
				// entry deleted note action hook
                $environment = array(
                    'user_login' => $this->current_user->user_login,
                    'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                    'db_name'    => $this->first_tab['database_name'],
                    'settings'   => $this->first_tab,
                );
                do_action('wcp_delnote_entry_action', $lead_id, $note, $note_id, $environment);

				$response['note_id'] = $note_id;

			// Save Individual Lead Fields
			} elseif (isset($_POST['save_lead_fields']) && $_POST['save_lead_fields'] == 'true') {
				$lead_id = intval($_POST['lead_id']);

                $field_vals = $_POST['field_vals'];

				 // Get the sorting info for checking required
                $sorting = $wpdb->get_results (
                    "
                        SELECT * from $this->table_sort order by sort_number asc
                    "
                );

                /* Check required fields */
                $field_checks = $this->field_checks($sorting, $field_vals);

                if ($field_checks['required_not_set']) {
                    $response['required'] = $field_checks['required'];
                    $response['required_msg'] = $field_checks['required_msg'];

                } else {  // continue

                	// select value names - for sending back
                	$l_source = sanitize_text_field($_POST['l_source']);
                	$l_status = sanitize_text_field($_POST['l_status']);
                	$l_type = sanitize_text_field($_POST['l_type']);

                	$format = array();
                	$where_format = array("%d");

                	$field_vals['updated_date'] = current_time( 'mysql' );
                	$field_vals['updated_by'] = $this->current_user->data->user_login;

					/* date and time conversion for saving */
                    foreach ($sorting as $k => $v) {
                        foreach ($field_vals as $k2 => $v2) {
                            $v2 = stripslashes($v2);
                            if ($k2 == $v->orig_name && $v->field_type == '7') {
                                $orig_datetime = DateTime::createFromFormat("$this->date_format $this->time_format", $v2);
								if ($orig_datetime) {
                                	$new_datetime = $orig_datetime->format('Y-m-d H:i:s');
                                	$field_vals[$k2] = $new_datetime;
								}
                            }
                        }
                    }

                	// set the formats
                	foreach ($field_vals as $f => $v) {
                    	if ($f == 'l_source'
                        	|| $f == 'l_status'
                        	|| $f == 'l_type'
                       	) {
                        	$format[] = '%d';
                    	} else {
                        	$format[] = '%s';
                    	}
                	}

                	$wpdb->update(
                    	$this->table_main,
                    	$field_vals,
                    	array( 'id' => $lead_id ),
                    	$format,
                    	$where_format
                	);

                	$created_by = $wpdb->get_var("SELECT created_by FROM $this->table_main where id=$lead_id");
                	$creation_date = $wpdb->get_var("SELECT creation_date FROM $this->table_main where id=$lead_id");
                	$updated_info = get_userdata($this->current_user->ID);
                	$updated_by = $updated_info->user_login;

                	// add in the extra fields to send back
                	$field_vals['id'] = $lead_id;
                	$field_vals['created_by'] = $created_by;
                	$field_vals['creation_date'] = $creation_date;

                	foreach ($sorting as $k => $v) {
                    	foreach ($field_vals as $k2 => $v2) {
                        	if ($k2 == $v->orig_name && $v->sort_active == '1') {
                            	if ($v->orig_name == 'l_source') {
                                	$output_fields[$k2] = $l_source;
                            	} elseif ( $v->orig_name == 'l_status') {
                                	$output_fields[$k2] = $l_status;
                            	} elseif ( $v->orig_name == 'l_type') {
                                	$output_fields[$k2] = $l_type;
                            	} elseif ( $v->orig_name == 'updated_by') {
                                	$output_fields[$k2] = $updated_by;  // translated value
								} elseif ( $v->orig_name == 'updated_date') {
									$output_fields['updated_date_formatted'] = 
										date("$this->date_format $this->time_format", strtotime('now'));
                            	} else {
                                	$output_fields[$k2] = $v2;
                            	}
                        	}
                    	}
                	}
					$event = __('Modified Entry Details', 'shwcp');
                	$detail = __('Entry ID ', 'shwcp') . $lead_id;
                	$wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
					$sst = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");
					$environment = array(
                        'user_login' => $this->current_user->user_login,
                        'db_number'  => get_post_meta($postID, 'wcp_db_select', true),
                        'db_name'    => $this->first_tab['database_name'],
                        'settings'   => $this->first_tab,
                    );
                    // update entry translate fields and action hook
                    $translated_fields = $this->shwcp_return_entry($output_fields, $lead_id, $sorting, $sst);
                    do_action('wcp_update_entry_action', $translated_fields, $environment);

					$response['lead_id'] = $lead_id;
					$response['field_vals'] = array_merge($output_fields, $field_vals);
				} // end continue

			} elseif (isset($_POST['lead_stats']) && $_POST['lead_stats'] == 'true') {
				$ownleads = '';
				if ($_POST['ownleads'] != 'shwcp_admin_view') {
					$ownleads_user = $_POST['ownleads'];
					$ownleads = "owned_by='$ownleads_user' AND ";
				}

				if ($_POST['graph'] == 'monthly') {
					$current_month = date('n');
					// monthly total leads - last 12 months
            		$months = array(
                		1  => __('January', 'shwcp'),
                		2  => __('February', 'shwcp'),
                		3  => __('March', 'shwcp'),
                		4  => __('April', 'shwcp'),
                		5  => __('May', 'shwcp'),
                		6  => __('June', 'shwcp'),
                		7  => __('July', 'shwcp'),
                		8  => __('August', 'shwcp'),
                		9  => __('September', 'shwcp'),
                		10 => __('October', 'shwcp'),
                		11 => __('November', 'shwcp'),
                		12 => __('December', 'shwcp')
            		);
            		$lead_totals = $wpdb->get_results (
                		"
                		SELECT DATE_FORMAT(creation_date, '%Y') as 'year',
                		DATE_FORMAT(creation_date, '%m') as 'month',
                		COUNT(*) as 'total'
                		FROM $this->table_main WHERE $ownleads(creation_date) >= CURDATE() - INTERVAL 1 YEAR
                		GROUP BY DATE_FORMAT(creation_date, '%Y%m')
                		"
            		);
            		// line up months with zero data to past months...ugggh
            		$decr = $current_month;
            		for ($i = 1; $i <= 12; $i++) {
                		if ($decr == 0) { $decr = 12; }
                		$labels1[] = $months[$decr];
                		$set = false;
                		foreach ($lead_totals as $k => $v) {
                    		if ($v->month == $decr) {
                        		$values1[] = $v->total;
                        		$set = true;
                    		}
                		}
                		if (!$set) { $values1[] = 0;}

                		$decr--;
            		}
            		$response['labels1'] = array_reverse($labels1);  // flip them for the graph
            		$response['values1'] = array_reverse($values1);

				// daily for the last month
				} elseif ($_POST['graph'] == 'daily') {
					$lead_totals = $wpdb->get_results (
						"
						SELECT count( * ) cnt, DATE_FORMAT(creation_date, '%Y-%m-%d') as 'day'
						FROM $this->table_main WHERE $ownleads(creation_date) >= CURDATE() - INTERVAL 1 MONTH
						GROUP BY day
						ORDER BY day
						"
					);
					$today = date('Y-m-d');
					$month_ago = date('Y-m-d', strtotime('-1 month'));
					$total_days = (strtotime(date('Y-m-d')) - strtotime($month_ago) ) / 86400;
					$values1 = array();
					$labels1 = array();
					$date_check = $month_ago;
					for ($i = 0; $i <= $total_days; $i++) {
						$match = false;
						foreach($lead_totals as $k => $v) {
							if ($date_check == $v->day) {
								$values1[] = $v->cnt;
								$labels1[] = date("$this->date_format", strtotime($v->day));
								$match = true;
								$date_mod = DateTime::createFromFormat('Y-m-d', $date_check);
								$date_mod->modify('+1 day');
								$date_check = $date_mod->format('Y-m-d');
							}
						}
						if (!$match) {
							$values1[] = 0;
							$labels1[] = date("$this->date_format", strtotime($date_check));
							$date_mod = DateTime::createFromFormat('Y-m-d', $date_check);
                            $date_mod->modify('+1 day');
                            $date_check = $date_mod->format('Y-m-d');
						}
					}

					//$response['today'] = $today;
					//$response['month_ago'] = $month_ago;
					//$response['total_days'] = $total_days;
					$response['labels1'] = $labels1;
					$response['values1'] = $values1;


				// weekly for the past year
				} elseif ($_POST['graph'] == 'weekly') {
					$lead_totals = $wpdb->get_results (
						"
						SELECT count( * ) cnt, year( creation_date ) AS year, week( creation_date, 3 ) week
						FROM $this->table_main WHERE $ownleads(creation_date) >= CURDATE( ) - INTERVAL 1 YEAR 
						GROUP BY week 
						ORDER BY year, week
						"
					);
					//get the current week number
					$curr_week = date("W");
					$curr_year = date("Y");
					$max_weeks = 27; // our cutoff on how far to go back (6 months)
					$week = $curr_week;
					$year = $curr_year;
					$values1 = array();
					$labels1 = array();
					for ($i = $max_weeks; $i > 0; $i--) {
						$match = false;
						foreach ($lead_totals as $k => $v) {
							if ($v->year == $year) {
								if ($v->week == $week) {
									$values1[] = $v->cnt;
									$labels1[] = $v->year . ' ' . $v->week;
									$match = true;
									$week--;
								}
							}
						}
						if (!$match) {
							$values1[] = 0;
							$labels1[] = $year . ' ' . $week;
							$week--;
						}
						if ($week == 0) {
							$year = $year - 1;
							$week = new DateTime("December 28th, $year"); // last week of year
							$week = $week->format('W');
						}
					}
												

					//$response['curr_year'] = $curr_year;
					//$response['curr_week'] = $curr_week;
					$response['labels1'] = array_reverse($labels1);
					$response['values1'] = array_reverse($values1);


				}

			// Remove all logs
			} elseif (isset($_POST['remove_logs']) && $_POST['remove_logs'] == 'true') {
				$response['title'] = __('Remove All Logs', 'shwcp');
                $response['body'] = __('Do you really want to remove all logging activity?', 'shwcp');
                $response['confirm_button'] = __('Remove Logs', 'shwcp');
                $response['cancel_button'] = __('Close', 'shwcp');

			// Actually remove all logs
			} elseif (isset($_POST['confirm_remove_logs']) && $_POST['confirm_remove_logs'] == 'true') {
				$delete = $wpdb->query("TRUNCATE TABLE $this->table_log"); 				

				$event = __('Removed all logs', 'shwcp');
                $detail = __('Cleared all logging entries ', 'shwcp');
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
				$response['removed'] = 'true';

			/* Calendar page events */

			// Calendar loading
			} elseif (isset($_POST['load_calendar']) && $_POST['load_calendar'] == 'true') {
				$start           = $_POST['start'];
				$end             = $_POST['end'];
				$front_url       = $_POST['frontUrl'];
				$contacts_color  = $_POST['contactsColor'];
				$current         = $_POST['current'];  // current year / month view for calendar
				$inc = 1;  // $matches incrementor

				preg_match('/^(\d{4})-(\d{2})/', $current, $curr_matches);
				$current_y = $curr_matches[1];
				$current_m = $curr_matches[2];

				$matches = array();
				$page_ind_arg = add_query_arg( array('wcp' => 'entry'), $front_url );
				$owner_only = '';
                if ($this->current_access == 'ownleads') {
                    $user_login = $this->current_user->user_login;
                    $owner_only = "and owned_by='$user_login'";
                }

				// new entries query
				if (isset($this->first_tab['calendar_entries_new']) && $this->first_tab['calendar_entries_new'] == 'true') {
					$entries = $wpdb->get_results (
                        "
						SELECT * from $this->table_main WHERE `creation_date` between '$start' and '$end' $owner_only
                        "
                    );
					foreach ($entries as $k => $entry) {
						$matches[$inc]['title'] = __('New Entry - ', 'shwcp') 
							. stripslashes($entry->first_name . ' ' . $entry->last_name);
						$matches[$inc]['creation_date'] = $entry->creation_date;
						$matches[$inc]['stop'] = $entry->creation_date;
						$matches[$inc]['color'] = $contacts_color;
						$matches[$inc]['textcolor'] = '#ffffff';
						$matches[$inc]['class'] = 'lead-link';
						$matches[$inc]['url'] = $page_ind_arg . '&lead=' . $entry->id;
						$matches[$inc]['description'] = __('New Entry Created') 
							. ' ' . stripslashes($entry->first_name . ' ' . $entry->last_name);
						$matches[$inc]['edit_event'] = 'na';
						$inc++;
					}
				}

				// Datetime columns in leads table and loop through to get other events
				if (isset($this->first_tab['calendar_entries_date']) && $this->first_tab['calendar_entries_date'] == 'true') {
					$datetimecols = $wpdb->get_results ( "SHOW COLUMNS FROM $this->table_main" );
					$date_cols = array();
					foreach ($datetimecols as $col => $v) {
						if ($v->Type == 'datetime'
							&& $v->Field != 'creation_date'
							&& $v->Field != 'updated_date'
						) {
							$date_cols[] = $v->Field;
						}
					}
					$color = 1;
					foreach ($date_cols as $v) {
						$trans_name = $wpdb->get_var("SELECT translated_name FROM $this->table_sort where orig_name='$v'");
						$entries = $wpdb->get_results (
							"
							SELECT * from $this->table_main WHERE `" . $v . "` between '$start' and '$end' $owner_only
							"
						);
						foreach ($entries as $k => $entry) {
                    		$matches[$inc]['title'] = stripslashes($trans_name . ' - ' 
								. $entry->first_name . ' ' . $entry->last_name);
                    		$matches[$inc]['creation_date'] = $entry->$v;
							$matches[$inc]['stop'] = $entry->$v;
                    		$matches[$inc]['color'] = $this->colors[$color];
                    		$matches[$inc]['textcolor'] = '#ffffff';
							$matches[$inc]['class'] = 'lead-link';
                    		$matches[$inc]['url'] = $page_ind_arg . '&lead=' . $entry->id;
							$matches[$inc]['description'] = stripslashes($trans_name) . ' ' . __('For', 'shwcp') . ' ' 
								. stripslashes($entry->first_name . ' ' . $entry->last_name);
							$matches[$inc]['edit_event'] = 'na';
							$inc++;
                		}
						$color++;
					}
				}  // end Datetime columns

				// Events created
				$events = $wpdb->get_results ( "SELECT * FROM $this->table_events WHERE `start` between '$start' and '$end'" );
				foreach($events as $k => $v) {
					$edit_event = 'yes';  // Affects ownleads users ability to edit events
					if ($this->current_access == 'ownleads') {
						if ($this->current_user->ID != $v->event_creator) {
							$edit_event = 'no';
						}
					}
					$matches[$inc]['title'] = stripslashes($v->title);
					$matches[$inc]['creation_date'] = $v->start;
					$matches[$inc]['stop'] = $v->end;
					$matches[$inc]['color'] = $v->event_color;
					$matches[$inc]['textcolor'] = '#ffffff';
					$matches[$inc]['url'] = $v->id;
					$matches[$inc]['class'] = 'modal-link';
					$matches[$inc]['description'] = stripslashes($v->details);
					$matches[$inc]['edit_event'] = $edit_event;
					$inc++;
				}

				// Reoccuring events check and add
				$reoccur = $wpdb->get_results ( "SELECT * FROM $this->table_events WHERE `repeat`=1");
				$start_time = strtotime($start);
                $end_time = strtotime($end);
				foreach($reoccur as $k => $v) {
					$edit_event = 'yes';  // Affects ownleads users ability to edit events
                    if ($this->current_access == 'ownleads') {
                        if ($this->current_user->ID != $v->event_creator) {
                            $edit_event = 'no';
                        }
                    }
					$repeat     = $v->repeat_every;
					$orig_start = $v->start;
					$orig_end   = $v->end;
					/* Yearly Match */
					if ($repeat == 'year') {   // Yearly match check
						$time_check = preg_replace('/^\d{4}/', $current_y, $orig_start);
						if ($time_check != $orig_start
							&& $time_check > $orig_start
						) { // don't duplicate it and don't show before set date
							$time_end_check = preg_replace('/^\d{4}/', $current_y, $orig_end);
							$time_check_time = strtotime($time_check);
							if ($time_check_time >$start_time
								&& $time_check_time < $end_time
							) {  // Match for calendar
								$matches[$inc]['title'] = stripslashes($v->title);
								$matches[$inc]['creation_date'] = $time_check;
								$matches[$inc]['stop'] = $time_end_check;
								$matches[$inc]['color'] = $v->event_color;
								$matches[$inc]['textcolor'] = '#ffffff';
								$matches[$inc]['url'] = $v->id;
								$matches[$inc]['class'] = 'modal-link';
								$matches[$inc]['description'] = stripslashes($v->details);
								$matches[$inc]['edit_event'] = $edit_event;
								$inc++;
							}
						}
					/* Monthly Match */
					} elseif ($repeat == 'month') {  
						preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $orig_start, $t_matches);
						$reoccur_start = $current_y . '-' . $current_m . '-' . $t_matches[3] . ' ' 
							. $t_matches[4] .':' . $t_matches[5] . ':' . $t_matches[6];
						preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $orig_end, $e_matches);
						$reoccur_stop = $current_y . '-' . $current_m . '-' . $e_matches[3] . ' '
                            . $e_matches[4] .':' . $e_matches[5] . ':' . $e_matches[6];

						$valid_date = $this->checkIsAValidDate($reoccur_start);

						if (!$valid_date) {  // End of month, shorter current month (e.g. 31st)
							// need to consider how to handle this, right now it just won't show up if an event is
							// set for the 31st and the month is shorter (logically), maybe we'll add an end of month
							// or maybe this is ok
						}
							
					    if ($reoccur_start != $orig_start
							&& $reoccur_start > $orig_start ) {
							$matches[$inc]['title'] = stripslashes($v->title);
                        	$matches[$inc]['creation_date'] = $reoccur_start;
                        	$matches[$inc]['stop'] = $reoccur_stop;
                        	$matches[$inc]['color'] = $v->event_color;
                        	$matches[$inc]['textcolor'] = '#ffffff';
                        	$matches[$inc]['url'] = $v->id;
							$matches[$inc]['class'] = 'modal-link';
							$matches[$inc]['description'] = stripslashes($v->details);
							$matches[$inc]['edit_event'] = $edit_event;
							$inc++;
						}
					/* Weekly Match */
					} elseif ($repeat == 'week') {
						$startDateTime = $orig_start;
						$endDateTime = $orig_end;
						$repeatEndDate = $end;
						#$timestamp = strtotime($startDateTime);
						#$day_of_week = date('l', $timestamp);
						$step  = 1;
						$unit  = 'W';
						$repeatStart = new DateTime($startDateTime);
						$repeat_end = new DateTime($endDateTime);
						$repeatEnd   = new DateTime($repeatEndDate);
						#$repeatStart->modify($day_of_week);  
						$interval = new DateInterval("P{$step}{$unit}");
						$period   = new DatePeriod($repeatStart, $interval, $repeatEnd);
						$period2  = new DatePeriod($repeat_end, $interval, $repeatEnd);
						// calculate end times
						$i = 1;
						$end_times = array();
						foreach( $period2 as $key => $date2 ) {
							if ($date2->format('Y-m-d H:i:s') != $orig_end) { // no duplicating
								$end_times[$i] = $date2->format('Y-m-d H:i:s');
								$i++;
							}
						}
						$i = 1;
						foreach ($period as $key => $date ) {
							if ($date->format('Y-m-d H:i:s') != $orig_start) { // no duplicating
								$new_start = $date->format('Y-m-d H:i:s');
								
								$matches[$inc]['title'] = stripslashes($v->title);
                            	$matches[$inc]['creation_date'] = $new_start;
                            	$matches[$inc]['stop'] = isset($end_times[$i]) ? $end_times[$i] : $new_start; // protect overlap
                            	$matches[$inc]['color'] = $v->event_color;
                            	$matches[$inc]['textcolor'] = '#ffffff';
                            	$matches[$inc]['url'] = $v->id;
								$matches[$inc]['class'] = 'modal-link';
								$matches[$inc]['description'] = stripslashes($v->details);
								$matches[$inc]['edit_event'] = $edit_event;
								$i++;
								$inc++;
							}
						}
					/* Daily Match */
					} elseif ($repeat == 'day') {
						$startDateTime = $orig_start;
						$endDateTime = $orig_end;
						$repeatEndDate = $end;
						$step = 1;
						$unit = 'D';
						$repeatStart = new DateTime($startDateTime);
                        $repeat_end = new DateTime($endDateTime);
                        $repeatEnd   = new DateTime($repeatEndDate);
                        #$repeatStart->modify($day_of_week);  
                        $interval = new DateInterval("P{$step}{$unit}");
                        $period   = new DatePeriod($repeatStart, $interval, $repeatEnd);
                        $period2  = new DatePeriod($repeat_end, $interval, $repeatEnd);
                        // calculate end times
                        $i = 1;
                        $end_times = array();
                        foreach( $period2 as $key => $date2 ) {
                            if ($date2->format('Y-m-d H:i:s') != $orig_end) { // no duplicating
                                $end_times[$i] = $date2->format('Y-m-d H:i:s');
                                $i++;
                            }
                        }
                        $i = 1;
                        foreach ($period as $key => $date ) {
                            if ($date->format('Y-m-d H:i:s') != $orig_start) { // no duplicating
                                $new_start = $date->format('Y-m-d H:i:s');
                                
                                $matches[$inc]['title'] = stripslashes($v->title);
                                $matches[$inc]['creation_date'] = $new_start;
                                $matches[$inc]['stop'] = isset($end_times[$i]) ? $end_times[$i] : $new_start;  //protect overlap
                                $matches[$inc]['color'] = $v->event_color;
                                $matches[$inc]['textcolor'] = '#ffffff';
                                $matches[$inc]['url'] = $v->id;
								$matches[$inc]['class'] = 'modal-link';
								$matches[$inc]['description'] = stripslashes($v->details);
								$matches[$inc]['edit_event'] = $edit_event;
                                $i++;
								$inc++;
                            }
                        }
					}
				}


				$response['matches'] = $matches;
				$response['calendar'] = 'true';

			// Delete existing event
			} elseif (isset($_POST['delete_event']) && $_POST['delete_event'] == 'true') {
				$event_id = intval($_POST['event_id']);
				$query = $wpdb->delete( $this->table_events, array( 'id' => $event_id ), array( '%d' ) );
				$event = __('Event Deleted', 'shwcp');
                $detail = __('User has deleted event with id', 'shwcp') . ' ' . $event_id;
                $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
                $response['deleted'] = $query;

			// Add New or edit existing Event Dialog
			} elseif (isset($_POST['add_event']) && $_POST['add_event'] == 'true') {
				$notifications = $this->first_tab['calendar_notify'];
				$users = $this->get_all_wcp_users();
				$event_id = $_POST['event_id'];
				if ($event_id != 'new') {           // Existing Entry
					$event_id = intval($event_id);
					$event_entry = $wpdb->get_row(
                        "
                            SELECT * FROM $this->table_events WHERE id=$event_id;
                        "
                    );	
					$event_entry->title = stripslashes($event_entry->title);
					$event_entry->details = stripslashes($event_entry->details);
					$event_entry->start = date("$this->date_format $this->time_format", strtotime($event_entry->start));
					$event_entry->end = date("$this->date_format $this->time_format", strtotime($event_entry->end));
					$response['event_entry'] = $event_entry;
					$response['notify_who'] = unserialize($event_entry->notify_who);
				}
				$response['event_id'] = $event_id;
				$response['notifications'] = $notifications;

				$lead_id = $_POST['lead_id']; // right now it's just general for general events
				$response['title'] = __('Add/Edit An Event', 'shwcp');
				$response['cancel_button'] = __('Cancel', 'shwcp');
				$response['confirm_button'] = __('Confirm', 'shwcp');
				$response['title_label'] = __('Title', 'shwcp');
				$response['start_label'] = __('Start Time', 'shwcp');
				$response['stop_label'] = __('End Time', 'shwcp');
				$response['color_label'] = __('Event Color', 'shwcp');
				$response['repeat_label'] = __('Repeat', 'shwcp');
				$response['repeat_enable'] = __('Repeating Event?', 'shwcp');
				$response['repeat_select'] = array(
					/* 'hour' => __('Every Hour', 'shwcp'), */
					'day' => __('Every Day', 'shwcp'),
					'week' => __('Every Week', 'shwcp'),
					'month' => __('Every Month', 'shwcp'),
					'year' => __('Every Year', 'shwcp')
				);

				$response['alert_label'] = __('Notify', 'shwcp');
				$response['alert_enable'] = __('Enable Notification?', 'shwcp');
				$response['alert_notes'] = __('Event Description', 'shwcp');
				$response['alert_select'] = array(
					'minutes' => __('Minute(s)', 'shwcp'),
					'hours' => __('Hour(s)', 'shwcp'),
					'days' => __('Day(s)', 'shwcp'),
					'weeks' => __('Week(s)', 'shwcp'),
					'months' => __('Month(s)', 'shwcp')
				);

				$response['notify_recip'] = __('Who receives notifications?', 'shwcp');
				$response['notify_users'] = $users;
				$response['alert_options'] = array(
					'beforestart' => __('Before', 'shwcp'),
					'afterstart'  => __('After', 'shwcp'),
				);
				$response['lead_id'] = $lead_id;
				$response['users'] = $users;

			} elseif (isset($_POST['save_event']) && $_POST['save_event'] == 'true') {
				$event_id         = $_POST['event_id'];
				$title            = sanitize_text_field($_POST['title']);
				$orig_start       = DateTime::createFromFormat("$this->date_format $this->time_format", $_POST['start']);
                $new_start        = $orig_start ? $orig_start->format('Y-m-d H:i:s') : $_POST['start'];
				$orig_stop        = DateTime::createFromFormat("$this->date_format $this->time_format", $_POST['stop']);
                $new_stop         = $orig_stop ? $orig_stop->format('Y-m-d H:i:s') : $_POST['stop'];
				$start            = $new_start;
				$stop             = $new_stop;
				$repeat_enable    = intval($_POST['repeat_enable']);
				$repeat_sel       = sanitize_text_field($_POST['repeat_sel']);
				$alert_enable     = intval($_POST['alert_enable']);
				$alert_notify_inc = intval($_POST['alert_notify_inc']);
				$alert_notify_sel = sanitize_text_field($_POST['alert_notify_sel']);
				$alert_time       = sanitize_text_field($_POST['alert_time']);
				$event_color      = $_POST['event_color'];
				$notify_user      = isset($_POST['notify_user']) ? $_POST['notify_user'] : array();
				$description      = wp_kses_post($_POST['description']);

				$error = false;

				$start_time = strtotime($start);
				$stop_time = strtotime($stop);
				if ($start_time > $stop_time
					|| $start == ''
					|| $stop == ''
				) {
					$error = true;
					$response['error'] = '1';
					$response['error_msg'] = __('Event start time is not before end time or time is blank.', 'shwcp');
				} elseif ($title == '') {
					$error = true;
					$response['error'] = 1;
					$response['error_msg'] = __('Please add a title.', 'shwcp');
				}

				if (!$error) {   // continue
					// notification time setting
					$notify_at = '';
					if ($alert_enable == 1) {
						$time_math = '-';
						if ($alert_time == 'afterstart') {
							$time_math = '+';
						}
				  		$notify_at = date('Y-m-d H:i:s', strtotime("$time_math $alert_notify_inc $alert_notify_sel", $start_time));
					
					}
					$response['notify_at'] = $notify_at;
					if ($event_id != 'new') {             // Existing event
						$event_id = intval($event_id);
						$wpdb->update(
                        	$this->table_events,
                            array(
                                'start'             => date('Y-m-d H:i:s', $start_time),
                                'end'               => date('Y-m-d H:i:s', $stop_time),
                                'title'             => $title,
                                'details'           => $description,
                                'repeat'            => $repeat_enable,
                                'repeat_every'      => $repeat_sel,
                                'alert_enable'      => $alert_enable,
                                'notify_at'         => $notify_at,
                                'alert_notify_inc'  => $alert_notify_inc,
                                'alert_notify_sel'  => $alert_notify_sel,
                                'alert_time'        => $alert_time,
                                'notify_who'        => serialize($notify_user),
                                'notify_email_sent' => 0,
                                'entry_id'          => 0,
                                'event_color'       => $event_color,
                                'event_creator'     => $this->current_user->ID
                            ),
							array( 'id' => $event_id),
                            array('%s','%s','%s','%s','%d','%s','%d', '%s','%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s'),
							array('%d')
                        );
                        $insertID = $event_id;
                        $event = __('Edited Event', 'shwcp');
                        $detail = __('User has edited the event with id', 'shwcp') . ' ' . $event_id;

					} else {               // New Event
						$wpdb->insert(
                    		$this->table_events,
                    		array(
                        		'start'             => date('Y-m-d H:i:s', $start_time),
                        		'end'               => date('Y-m-d H:i:s', $stop_time),
                        		'title'             => $title,
                        		'details'           => $description,
                        		'repeat'            => $repeat_enable,
								'repeat_every'      => $repeat_sel,
								'alert_enable'      => $alert_enable,
								'notify_at'         => $notify_at,
								'alert_notify_inc'  => $alert_notify_inc,
								'alert_notify_sel'  => $alert_notify_sel,
								'alert_time'        => $alert_time,
								'notify_who'        => serialize($notify_user),
								'notify_email_sent' => 0,
								'entry_id'          => 0,
								'event_color'       => $event_color,
								'event_creator'     => $this->current_user->ID
                    		),
                    		array('%s','%s','%s','%s','%d','%s','%d', '%s','%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
                		);
						$insertID = $wpdb->insert_id;
						$event = __('New Event', 'shwcp');
                		$detail = __('User has added a new event with id', 'shwcp') . ' ' . $insertID;
					}

                	$wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);
                	$response['insert'] = $insertID;
				}

			}

			/* End Calendar Page Events */


			header( "Content-Type: application/json" );
			echo json_encode($response);
            wp_die();
        }

		/*
		 * Update the database if needed with new Source, Status and Types on imports
		 * otherwise, return the existing id
		 */
		public function sst_update_db($value, $sst, $choice, $sst_type) {
			global $wpdb;
			$exists = false;
            foreach ($sst as $sst_row => $sst_data) {
				if ($value == $sst_data->sst_name && $sst_type == $sst_data->sst_type) {
                	$exists = true;
					return $sst_data->sst_id;
                }
            }
            if (!$exists) {
            	$wpdb->insert(
                	$this->table_sst,
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

		/*
		 * Not logged in response to notify logging in (since all ajax requests are for modifications
		 */
		public function nopriv_wcpfrontend_callback() {
			// nonce and logged in check
            $nonce = isset($_POST['nextNonce']) ? $_POST['nextNonce'] : '';
            $logged_in = is_user_logged_in();
            if (!wp_verify_nonce( $nonce, 'myajax-next-nonce' )
                || !$logged_in ) {

                $response['logged_in'] = 'false';
				$response['title'] = __('Please Log In', 'shwcp');
				$response['body'] = __('Please log in to continue', 'shwcp');
				$response['login_button'] = __('Login', 'shwcp');
				$response['close'] = __('Close', 'shwcp');
                header( "Content-Type: application/json" );
                echo json_encode($response);
                wp_die();
            }
		}

		/**
         * Login Ajax Verify Function
         * @since 1.0.0
         */
        public function ajax_login() {
            if (!is_user_logged_in()) {
                // First check the nonce, if it fails the function will break
                check_ajax_referer( 'ajax-login-nonce', 'security' );

                // Nonce is checked, get the POST data and sign user on
                $info = array();
                $info['user_login'] = $_POST['username'];
                $info['user_password'] = $_POST['password'];
                $info['remember'] = true;

                $user_signon = wp_signon( $info, false );
                if ( is_wp_error($user_signon) ){
                    echo json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.', 'shwcp')));
                } else {
                    echo json_encode(array('loggedin'=>true, 'message'=>__('Login successful, redirecting...', 'shwcp')));
                }
                wp_die();
            }
        }

		/**
		 * Backend Ajax 
		 * @since 1.1.4
		 */
		public function myajax_wcpbackend_callback() {
            global $wpdb;

			$creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, array());
			if ( ! WP_Filesystem($creds) ) {
				request_filesystem_credentials($url, '', true, false, null);
				$response['logged_in'] = 'false';
				echo json_encode($response);
				wp_die();
			}
			global $wp_filesystem;
			// nonce and logged in check
            $nonce = isset($_POST['nextNonce']) ? $_POST['nextNonce'] : '';
            $logged_in = is_user_logged_in();
            if (!wp_verify_nonce( $nonce, 'myajax-next-nonce' )
                || !$logged_in ) {

                $response['logged_in'] = 'false';
                header( "Content-Type: application/json" );
                echo json_encode($response);
                wp_die();
            }

			// Reset Database to initial state
            if (isset($_POST['reset_db']) && $_POST['reset_db'] == 'true') {
				$db = $_POST['database'];
				require_once SHWCP_ROOT_PATH . '/includes/class-setup-wcp.php';
        		$setup_wcp = new setup_wcp;

				$setup_wcp->drop_tables($db);
            	$setup_wcp->install_shwcp($db);
            	$setup_wcp->data_shwcp($db);

				// Remove all files and directories in our custom upload directory as well
				$wp_filesystem->rmdir($this->shwcp_upload . $db, true);

				// Re-create the folder
                wp_mkdir_p( $this->shwcp_upload . $db);

				/* Remove db version so we can check the database */
	            update_option('shwcp_db_ver', '0');
				$response['reset'] = 'true';

			// Backup Database and files
			} elseif ( isset($_POST['backup_db']) && $_POST['backup_db'] == 'true') {
				$db = $_POST['database'];
				require_once SHWCP_ROOT_PATH . '/includes/class-setup-wcp.php';
				$setup_wcp = new setup_wcp;

				$table_main     = $wpdb->prefix . SHWCP_LEADS  . $db;
            	$table_sst      = $wpdb->prefix . SHWCP_SST    . $db;
            	$table_log      = $wpdb->prefix . SHWCP_LOG    . $db;
            	$table_sort     = $wpdb->prefix . SHWCP_SORT   . $db;
            	$table_notes    = $wpdb->prefix . SHWCP_NOTES  . $db;
				$table_events   = $wpdb->prefix . SHWCP_EVENTS . $db;

				$tables = array($table_main, $table_sst, $table_sort, $table_log, $table_notes, $table_events);
				$tables_sql = $setup_wcp->backup_tables($tables);
				//$this->shwcp_backup;  // backup directory 
			    //$this->shwcp_backup_url;
				// create the main backup directory if it doesn't exist
				if ( !file_exists($this->shwcp_backup) ) {
	                wp_mkdir_p( $this->shwcp_backup );
	            }
				// create timestamp folder
				if ($db == '') {           // default db
					$folder = 'default-' . date('m-d-Y_his');
				} else {                   // aditional dbs
					$folder = 'database' . $db . '-' . date('m-d-y_his');
				}

				$files = $this->shwcp_backup . '/' . $folder . '/' . 'files';
				if ( !file_exists($this->shwcp_backup . '/' . $folder) ) {
                    wp_mkdir_p( $this->shwcp_backup . '/' . $folder);
					wp_mkdir_p( $files );
                }   				

				$wp_filesystem->put_contents( 
					$this->shwcp_backup . '/' . $folder . '/create.sql',
					$tables_sql['create'],
					FS_CHMOD_FILE  
				);
				$wp_filesystem->put_contents(
                    $this->shwcp_backup . '/' . $folder . '/insert.sql',
                    $tables_sql['insert'],
                    FS_CHMOD_FILE  
                );

				// copy current files over
				copy_dir(
					$this->shwcp_upload . $db,
					$files
				);

				$response['backup'] = 'true';
				//$response['tables_sql'] = $tables_sql;


			// Delete existing backups
			} elseif (isset($_POST['remove_backup']) && $_POST['remove_backup'] == 'true') {
				$backup = trim($_POST['backup']);
				$filters = array( '../', '/');
				$backup = str_replace($filters, "", $backup); // just in case someones doing something funny
				// Remove all files and directories in our custom upload directory as well
			    $wp_filesystem->rmdir($this->shwcp_backup . '/' . $backup, true);
				$response['removed'] = 'true';

			// Restore a backup
			} elseif (isset($_POST['restore_backup']) && $_POST['restore_backup'] == 'true') {
				$backup = trim($_POST['backup']);
				$db = $_POST['database'];
				if ($backup == '') {
					$response['restored'] = 'false';
					$response['error'] = 'true';
					$response['errormsg'] = __('You must select a backup to restore.', 'shwcp');
				} else {  
					$create_sql = $wp_filesystem->get_contents($this->shwcp_backup . '/' . $backup . '/create.sql');
					$insert_sql = $wp_filesystem->get_contents_array($this->shwcp_backup . '/' . $backup . '/insert.sql');
					if (!$create_sql
						|| !$insert_sql) {
						$response['error'] = 'true';
						$response['errormsg'] = __('The backup selected is missing required data, please select another', 'shwcp');
					} else { // perform the database work and restore files
						require_once SHWCP_ROOT_PATH . '/includes/class-setup-wcp.php';
                		$setup_wcp = new setup_wcp;
                		$setup_wcp->drop_tables($db);  // drop existing tables beforehand

						// WordPress will only do 1 operation per query so we have to split them all up
						// Split on starting comment and then add it back to the statement (whatever) 
						$create_sql = explode('/*-----', $create_sql);
						foreach($create_sql as $k => $v) {
							$v = '/*-----' . $v;
							if (preg_match("/CREATE TABLE/", $v)) {  // Make sure it has a create statement, otherwise errors
								$wpdb->query($v);
							}
						}
						foreach($insert_sql as $k => $v) {
							$wpdb->query($v);
						}
						$wp_filesystem->rmdir($this->shwcp_upload . $db, true); // remove existing upload
						wp_mkdir_p($this->shwcp_upload . $db);  // recreate upload directory

						// copy backup files over to the upload directory
                		copy_dir(
							$this->shwcp_backup . '/' . $backup . '/files',
                    		$this->shwcp_upload . $db
                		);
						/* Remove db version so we can check the database */
						update_option('shwcp_db_ver', '0');
						$response['restored'] = 'true';
						//$response['sql'] = print_r($sql, true);
					}
				}
			// Add New Database
			} elseif (isset($_POST['new_db']) && $_POST['new_db'] == 'true') {
				$db_trans_name = sanitize_text_field($_POST['db_trans_name']);
				// loop through existing leads table to find the next available number to assign the db to 
				$table_main = $wpdb->prefix . SHWCP_LEADS;
            	$databases = array();
            	$dbs = $wpdb->get_results("SHOW tables LIKE '$table_main%'");
            	foreach ($dbs as $k => $v) {
                	foreach ($v as $v2 => $table) {
                    	if ($table == $table_main) {  // default database, ignore...
							// ignore
                    	} else {        // All others created, find numeric values after name
							$databases[] = $table;
                    	}
                	}
            	}
				$db_inc = 1;
				$dbname = $table_main . '_' . $db_inc;
                while(in_array($dbname, $databases)) {
                	$db_inc++;
                    $dbname = $table_main . '_' . $db_inc;
                }
				$dbnumber = '_' . $db_inc;
				// create the database
				require_once SHWCP_ROOT_PATH . '/includes/class-setup-wcp.php';
                $setup_wcp = new setup_wcp;
				$setup_wcp->install_shwcp($dbnumber);
				$setup_wcp->data_shwcp($dbnumber);

				// add the option for the database name to our new options for the database
				$options = array(
					'database_name' => $db_trans_name
				);
				$option_name = 'shwcp_main_settings' . $dbnumber; // base name set in api_tabs class
				add_option($option_name, $options);

				// Create File Directory - uses $this upload
				$file_loc = $this->shwcp_upload . $dbnumber;
				if ( !file_exists($file_loc) ) {
                	wp_mkdir_p( $file_loc );
            	}
				

				// responses
				$response['created'] = 'true';
				$response['dbnumber'] = $dbnumber;
				$response['dbname'] = $dbname;

			// Delete existing database
			} elseif (isset($_POST['delete_db']) && $_POST['delete_db'] == 'true') {
				$dbnumber = intval($_POST['db_number']);
				$dbnumber = '_' . $dbnumber;
				// delete tables
				require_once SHWCP_ROOT_PATH . '/includes/class-setup-wcp.php';
                $setup_wcp = new setup_wcp;
				$setup_wcp->drop_tables($dbnumber);
				// delete options - match settings api names and database number
				$first_tab_key           = 'shwcp_main_settings' . $dbnumber;
    			$permission_settings_key = 'shwcp_permissions'   . $dbnumber;
    			$info_settings_key       = 'shwcp_info'          . $dbnumber;
    			$db_actions_key          = 'shwcp_db'            . $dbnumber;
				delete_option($first_tab_key);
				delete_option($permission_settings_key);
				delete_option($info_settings_key);
				delete_option($db_actions_key);

				// Delete File Directory
				$file_loc = $this->shwcp_upload . $dbnumber;
				if (file_exists($file_loc) ) {
					$wp_filesystem->rmdir($file_loc, true);  // true for recursive
				}

				$response['deleted'] = $dbnumber;

			}


			header( "Content-Type: application/json" );
            echo json_encode($response);
            wp_die();


		} // End Backend Ajax

		/*
		 * Check for valid date
		 */
		public function checkIsAValidDate($date_string){
    		return (bool)strtotime($date_string);
		}

		/*
		 * Check required fields function
		 */
		public function field_checks($sorting, $field_vals) {
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
			$field_checks = array();
            $field_checks['required_not_set'] = false;
            foreach ($sorting as $k => $v) {    
                foreach ($field_vals as $k2 => $v2) {
                    if ($v->field_type == '8'
                        || $v->field_type == '9'
                        || $v->field_type == '99'
                    ) {    
                        // ignore check for these

                    } elseif ($k2 == $v->orig_name && $v->required_input == '1') {
                        if ($v->field_type == '4'
                            && !filter_var($v2, FILTER_VALIDATE_EMAIL)
                        ) { // check for valid email
                            $field_checks['required'] = 'true';
                            $field_checks['required_msg'] = $v->translated_name . ' ' . __('is invalid.', 'shwcp');
							$field_checks['required_not_set'] = true;
                        } elseif ($v->field_type == '7') {
                        // check date
							if ($v2 == ''|| $v2 == '0000-00-00 00:00:00' ) {
                            	$field_checks['required'] = 'true';
                            	$field_checks['required_msg'] = $v->translated_name . ' ' . __('needs a date set.', 'shwcp');
								$field_checks['required_not_set'] = true;
							}
                        } elseif ($v2 == '') { // generic empty check
                            $field_checks['required'] = 'true';
                            $field_checks['required_msg'] = $v->translated_name . ' ' . __('is required.', 'shwcp');
                            $field_checks['required_not_set'] = true;
                        }
                    }
                }
            }
			return $field_checks;
		}

        /**
         * Organize entry data for REST return data - a little different from the same function in REST class
         * since we have some things available due to this class extending the main
         * @param array $entry The Entry
         * @param int $id Entry ID
         * @param array $sorting The Sorting array
         * @param array $sst Source, Status, Type
         *
         * @return array
         */
        public function shwcp_return_entry($entry, $id, $sorting, $sst) {
            global $wpdb;
            // organize by sorting and add others at the end
            foreach ($sorting as $k => $v) {
                foreach ($entry as $k2 => $v2) {
                    if ($v->orig_name == $k2) {
                        $lead_vals[$k2] = $v2;
                    }
                }
            }
            // and add on the other fields that aren't listed in sorting
			// $postID and $current_db are necessary for many of the actions below
            $postID = isset($_POST['postID']) ? intval($_POST['postID']) : '';
            $current_db = '';
            $saved_db = get_post_meta($postID, 'wcp_db_select', true);
            if ($saved_db
                && $saved_db != 'default'
            ) {
                $current_db = '_' . $saved_db;
            }

            $shwcp_upload     = $this->shwcp_upload     . $current_db;
            $shwcp_upload_url = $this->shwcp_upload_url . $current_db;

            $lead_vals['small_image'] = isset($entry->small_image) ? $entry->small_image : '';
            if ($lead_vals['small_image']) { // set url
                $lead_vals['small_image'] = $shwcp_upload_url . '/' . $lead_vals['small_image'];
            }
            $lead_vals['lead_files'] = isset($entry->lead_files) ? unserialize($entry->lead_files) : '';
            if (!empty($lead_vals['lead_files'])) {
                foreach ($lead_vals['lead_files'] as $k => $v) { // set url
                    $lead_vals['lead_files'][$k]['url'] = $shwcp_upload_url . '/' . $id . '-files/' . $v['name'];
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

	} // end class
