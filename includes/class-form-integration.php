<?php
/**
 * Integration with forms - Contact Form 7 and Gravity forms etc.
 */

    class form_integration {
        // properties


        // methods

        /**
         * Integration with forms for lead capture.
         * @access public
         * @since 1.2.6
        **/

		/* Contact Form 7 Methods */
	    public function wpcontacts_add_lead($cf7) {
        	$submission = WPCF7_Submission::get_instance();  // new method for retrieving the post vars
        	if ( $submission ) {
            	$posted_data = $submission->get_posted_data();
				$uploaded_files = $submission->uploaded_files();
        	}
        	//print_r($posted_data);
			global $wpdb;

        	if ($posted_data['wpcontacts'] == 'yes') { // handle submission
				// Added for 2.0.0, map to specific database
				$db = '';
				if (isset($posted_data['wpdatabasemap'])) {
					$name = trim($posted_data['wpdatabasemap']);
					$db = $this->search_option_dbname($name);
					if ($db) {
						$db = '_' . $db;
					}
				}
            	$main_table = $wpdb->prefix . SHWCP_LEADS . $db;
				//echo 'Main Table = ' . $main_table;

            	/* Get the existing columns to compare submitted to */
            	$main_columns = $wpdb->get_results(
                	"
                	SELECT `COLUMN_NAME`
                	FROM `INFORMATION_SCHEMA`.`COLUMNS`
                	WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                	AND `TABLE_NAME`='$main_table'
                	"
            	);
            	// print_r($main_columns);
            	$existing_fields = array();
            	foreach($main_columns as $k => $v) {
                	$existing_fields[] = $v->COLUMN_NAME;
            	}
            	// print_r($existing_fields);
            	$wpdatafinal = array();  // get them all in a single array mapped correctly
            	if (isset($posted_data['wpfieldmap']) ) {
                	foreach ($posted_data['wpfieldmap'] as $k => $v) {
                    	if (in_array($v, $existing_fields)) {
                        	if (is_array($posted_data[$k]) ) { // for radio and checkboxes
                            	$entries = implode(', ', $posted_data[$k]);
                        	} else {
                            	$entries = $posted_data[$k];
                        	}
                        	$wpdatafinal[$v] = $entries;
                        	// $wpdatafinal[$v] = $posted_data[$k];
                    	}
                	}
            	}
            	if (isset($posted_data['wpdata']) ) {
                	foreach ($posted_data['wpdata'] as $k => $v) {
                    	if (in_array($k, $existing_fields)) {
                        	$wpdatafinal[$k] = $v;
                    	}
                	}
            	}
            	//print_r($wpdatafinal);
            	if (empty($wpdatafinal)) {  // if they haven't set any valid fields, we don't want it
                	return;
            	}

            	$wpdatafinal['creation_date'] = current_time( 'mysql' );
            	$wpdatafinal['created_by']    = __('CF7 Form Submittal', 'shwcp');
            	$wpdatafinal['updated_date']  = current_time( 'mysql' );
            	$wpdatafinal['updated_by']    = __('CF7 Form Submittal', 'shwcp');
            	// remove any fields that shouldn't change
            	unset($wpdatafinal['id']);

				/* Dropdown Check */
				$sorting = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . SHWCP_SORT . $db);
				$sst = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . SHWCP_SST . $db);
				foreach ($wpdatafinal as $f => $v) {
					foreach ($sorting as $k2 => $v2) { 
						if ($v2->orig_name == $f) {
							if ($v2->field_type == '10') {
								$sst_type = $wpdb->get_var(
                                            "SELECT sst_type FROM " . $wpdb->prefix . SHWCP_SST . $db  
											. " where sst_type_desc='$f' limit 1"
                                        );
								$value = $this->sst_update_checkdb($v, $sst, $f, $sst_type, $db);
								$wpdatafinal[$f] = $value;

							} elseif ($v2->field_type == '777') { // multi-select options id or create for each one
                            	if ($v != '') {
                                   	$value_array = explode(',', $v);
									$field_array = array();
									foreach ($value_array as $v_k => $v_v) {
                                   		$sst_type = $wpdb->get_var(
                                       		"SELECT sst_type FROM " . $wpdb->prefix . SHWCP_SST . $db 
                                       		. " where sst_type_desc='$f' limit 1"
                                   		);
                                   		$value = $this->sst_update_checkdb($v_v, $sst, $f, $sst_type, $db);
										$field_array[] = $value;
									}
                                   	$wpdatafinal[$f] = json_encode($field_array);
                            	}
							}
						}
					}
				}

            	// Prepare insert
            	foreach ($wpdatafinal as $f => $v) {
                   	$format[] = '%s';
            	}

            	$wpdb->insert(
                	$wpdb->prefix . SHWCP_LEADS . $db,
                	$wpdatafinal,
                	$format
            	);

            	//print_r($wpdatafinal);

				// Get any uploaded files if enabled
				if (isset($posted_data['wpcontact_uploads']) && $posted_data['wpcontact_uploads'] == 'yes') {
					$insert_id = $wpdb->insert_id;
					if (isset($uploaded_files) && !empty($uploaded_files)) {
						// upload directory & url
            			$upload_dir = wp_upload_dir();
            			$shwcp_upload = $upload_dir['basedir'] . '/shwcp' . $db;
            			$shwcp_upload_url = $upload_dir['baseurl'] . '/shwcp' . $db;
						$lead_dir = $shwcp_upload . '/' . $insert_id . '-files';
						if ( !file_exists($lead_dir) ) {
                        	wp_mkdir_p($lead_dir);
                    	}    
						foreach ($uploaded_files as $k => $file) {
							$file_parts = pathinfo($file);
							$file_name = $file_parts['filename'] . '.' . $file_parts['extension'];
							copy($file, $lead_dir . '/' . $file_name);
						}

						// get file info in directory and update db (ignore dot files and dir)
                    	$files = preg_grep('/^([^.])/', scandir($lead_dir));
                    	// get sizes and dates
                    	$files_info = array();
                    	$i = 0;
                    	$files_added = '';
                    	foreach ($files as $k => $v) {
                        	$files_info[$i]['name'] = $v;
                        	$files_info[$i]['size'] = size_format(filesize($lead_dir . '/' . $v));
                        	$files_info[$i]['date'] = date("m-d-Y H:i:s", filemtime($lead_dir . '/' . $v));
							$i++;
						}

						$files_info_ser = serialize($files_info);
                   		$wpdb->update(
                       		$wpdb->prefix . SHWCP_LEADS . $db,
                       		array( 'lead_files' => $files_info_ser ),
                       		array( 'id' => $insert_id ),
                       		array( '%s' ),
                       		array( '%d' )
                   		);

						//print_r($uploaded_files);
					}
				}

        	}

        	//return $posted_data;
    	}
		
	    public function wpcf7_add_shortcode_wpcontacts() {
        	if (defined('WPCF7_VERSION')) {  // just to make sure
				if (version_compare(WPCF7_VERSION, '4.6', '>=')) {
					wpcf7_add_form_tag('wpcontacts',
						array($this, 'wpcf7_wpcontacts_shortcode_handler'), true);
				} else {
					/* Deprecated cf7 v4.6 */
            		wpcf7_add_shortcode('wpcontacts', 
						array($this, 'wpcf7_wpcontacts_shortcode_handler'), true);
				}
        	}
    	}

    	public function wpcf7_wpcontacts_shortcode_handler($tag) {
			if (version_compare(WPCF7_VERSION, '4.8', '>=')) {    // CF7 switched from array to object form tag in 4.8
				if (!is_object($tag)) return '';
			} else {
        		if (!is_array($tag)) return '';
			}
        	$content = trim($tag['content']);
        	$content = preg_split('/\n/', $content);
        	$wp_mappings = array();
        	$wp_data = array();
			$wp_database = '';
			$wpcontact_uploads = '';
        	foreach ($content as $entry) {  // Get data
            	$entry = trim($entry);
            	if (preg_match("/^wpfieldmap/i", $entry)) { // field map entry 
                	list($fieldmaptext, $mappings) = explode("=", $entry);
                	$mapper = explode(":", $mappings);
                	$wp_mappings[$mapper[0]] = $mapper[1];
				} else if (preg_match("/^wpdatabasemap/i", $entry)) { // Database mapping
					$mapper = explode("=", $entry);
					$wp_database = $mapper[1];
				} else if (preg_match("/^wpcontact_uploads/i", $entry)) { // Enable uploads
					$wpcontact_uploads = "yes";
            	} else if (!empty($entry)) {
                	$mapper = explode("=", $entry);
                	$wp_data[$mapper[0]] = $mapper[1];
            	} 
        	}

        	$html = '';
        	foreach ($wp_mappings as $k => $v) { // hidden mapping entries
            	$v = strtolower($v);
            	$html .= '<input type="hidden" name="wpfieldmap[' . $k . ']" value="' . $v . '" />';
        	}
        	foreach ($wp_data as $k => $v) { // hidden set entries
            	$k = strtolower($k);
            	$html .= '<input type="hidden" name="wpdata[' . $k . ']" value="' . $v . '" />';
        	}
			
			if ($wp_database) {
                $html .= '<input type="hidden" name="wpdatabasemap" value="' . $wp_database . '" />';
            }

			if ($wpcontact_uploads == "yes") {
				$html .= '<input type="hidden" name="wpcontact_uploads", value="yes" />';
			}

        	$html .= '<input type="hidden" name="wpcontacts" value="yes" />';

        	//$html .= print_r($wp_mappings, false);
        	//$html .= print_r($wp_data, false);
        	return $html;
    	}
		/* End Contact Form 7 Methods */

		/* Ninja Form Methods 3.0 and above */
        public function ninja_forms3_wpcontacts($form_data) {
            $wpcinsert = false;

            // get the submitted data
			$all_fields = $form_data['fields'];
			$field_settings = $form_data['settings'];
            if ( is_array( $all_fields ) ) {
                global $wpdb;

                $wpdatapre = array();
                $wpdatabasemap = '';
                foreach ($all_fields as $k => $entry ) {
					$entry_value = $entry['value'];
					$admin_label = isset($entry['admin_label']) ? $entry['admin_label'] : '';
					$admin_label_split = explode( '-', $admin_label);
					if ($admin_label_split[0] == 'wpcontacts') {
						$wpcinsert = true;
						$wpdatapre[$admin_label_split[1]] = $entry_value;
					} elseif ($admin_label == 'wpdatabasemap') { // database to use
						$wpdatabasemap = trim($entry['value']);
					}
                }
				// print_r($wpdatapre);
				// return;

                $db = '';
                if ($wpdatabasemap) {
                    $name = $wpdatabasemap;
                    $db = $this->search_option_dbname($name);
                    if ($db) {
                        $db = '_' . $db;
                    }
                }
                $main_table = $wpdb->prefix . SHWCP_LEADS . $db;

                /* Get the existing columns to compare submitted to */
                $main_columns = $wpdb->get_results(
                    "
                    SELECT `COLUMN_NAME`
                    FROM `INFORMATION_SCHEMA`.`COLUMNS`
                    WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                    AND `TABLE_NAME`='$main_table'
                    "
                );
                $existing_fields = array();
                foreach ($main_columns as $k => $v) {
                    $existing_fields[] = $v->COLUMN_NAME;
                }

                // If we have any matches...insert
                if ($wpcinsert) {
                    // process and insert
                    foreach ($wpdatapre as $k => $v) {
                        if (in_array($k, $existing_fields)) {
                            $wpdatafinal[$k] = $v;
                            //echo "$k set to $v \n\n";
                        }
                    }
                    $wpdatafinal['creation_date'] = current_time( 'mysql' );
                    $wpdatafinal['created_by']    = __('Ninja Form Submittal', 'shwcp');
                    $wpdatafinal['updated_date']  = current_time( 'mysql' );
                    $wpdatafinal['updated_by']    = __('Ninja Form Submittal', 'shwcp');
                    // remove any fields that shouldn't be changed
                    unset($wpdatafinal['id']);

                    //echo "Fields to insert \n\n";
                    //print_r($wpdatafinal);

                    /* Dropdown Check */
                    $sorting = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . SHWCP_SORT . $db);
                    $sst = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . SHWCP_SST . $db);
                    foreach ($wpdatafinal as $f => $v) {
                        foreach ($sorting as $k2 => $v2) {
                            if ($v2->orig_name == $f) {
                                if ($v2->field_type == '10') {
                                    $sst_type = $wpdb->get_var(
                                            "SELECT sst_type FROM " . $wpdb->prefix . SHWCP_SST . $db
                                            . " where sst_type_desc='$f' limit 1"
                                        );
                                    $value = $this->sst_update_checkdb($v, $sst, $f, $sst_type, $db);
                                    $wpdatafinal[$f] = $value;

								} elseif ($v2->field_type == '777') { // multi-select options id or create for each one
                            		$select_array = array();
                            		if (!empty($v)) {
                                		if (is_array($v)) {
                                    		foreach ($v as $sel_k => $sel_v) {
                                        		if ($sel_v != '') {  // we don't want empty options
                                            		$sst_type = $wpdb->get_var(
														"SELECT sst_type FROM " . $wpdb->prefix . SHWCP_SST . $db . " where sst_type_desc='$f' limit 1"
                                            		);
                                            		$select_str = $this->sst_update_checkdb($sel_v, $sst, $f, $sst_type, $db);
                                            		$select_array[] = $select_str;
                                        		}
                                    		}
                                    		$value = json_encode($select_array);
                                    		//echo "$k = "; print_r($real_value);
                                    		$wpdatafinal[$k] = $value;
										}
									}
                                }
                            }
                        }
                    }

                    // Prepare insert
                    foreach ($wpdatafinal as $f => $v) {
                        $format[] = '%s';
                    }

                    $wpdb->insert(
                        $wpdb->prefix . SHWCP_LEADS . $db,
                        $wpdatafinal,
                        $format
                    );
                } // end match and insert
            }
        }

		/* End Ninja Form Methods post version 3.0 */

		/* Gravity Form Methods */
		public function gravity_forms_wpcontacts($entry, $form) {
			//print_r($entry);
			//print_r($form);
			$wpdatapre = array();
			if (is_array($form['fields'])) {
				global $wpdb;
				
				// keep track of form array position
				$forminc = 0;
				$wpcinsert = false;
				$wpdatabasemap = '';
				foreach ( $form['fields'] as $k => $v) {
					foreach ( $v as $key => $value) {
						if ($key == 'adminLabel'
							|| $key == 'label'
						) {
							$label_value = explode( '-', $value );
							if ($label_value[0] == 'wpcontacts') {
                        		// We have a match get entry value and put in our array
                            	$wpcinsert = true;
								if (is_array($form['fields'][$forminc]->inputs) ) { // This is a field with subfields
									$wpdatapre[$label_value[1]] = '';
									if ($label_value[1] == 'wcpsplit') { // We split data up in different fields
										$label_inc = 2;
										foreach ($form['fields'][$forminc]->inputs as $input => $inputv) {
											if (!isset($inputv['isHidden'])) {  // isHidden is marked for fields not used
												$entryid = $inputv['id'];
												$wpdatapre[$label_value[$label_inc]] = $entry["$entryid"];
												$label_inc++;
											}
										}
									} else { // no splitting, combining to 1 field
										foreach ($form['fields'][$forminc]->inputs as $input => $inputv) {
											$entryid = $inputv['id'];
											$wpdatapre[$label_value[1]] .= $entry["$entryid"] . ' '; 
											/* Have to use the quotes to access the ids (e.g. 11.3) above or php can't find them!! */
										}
									}

								} else { // The other fields
									$entryid = $form['fields'][$forminc]->id;

									if ($label_value[1] == 'wcpsplit') {   // This is our split field used for separating name fields
										$name = $this->split_name($entry[$entryid]);
										$wpdatapre[$label_value[2]] = $name[0];
										$wpdatapre[$label_value[3]] = $name[1];
										//echo "Field 1 is "  . $label_value[2] . " Value is " . $pieces[0] . "\n";

									} else {                                           // non split fields
										$wpdatapre[$label_value[1]] = $entry[$entryid];
									}
								}
							} elseif ( $value == 'wpdatabasemap' ) {
								$entryid = $form['fields'][$forminc]->id;
								$wpdatabasemap = $entry[$entryid];
							}
						}
					}
					$forminc++;
				}
				// print_r($wpdatapre);


				$db = '';
                if ($wpdatabasemap) {
                    $name = $wpdatabasemap;
                    $db = $this->search_option_dbname($name);
                    if ($db) {
                        $db = '_' . $db;
                    }
                }
                $main_table = $wpdb->prefix . SHWCP_LEADS . $db;

                /* Get the existing columns to compare submitted to */
                $main_columns = $wpdb->get_results(
                    "
                    SELECT `COLUMN_NAME`
                    FROM `INFORMATION_SCHEMA`.`COLUMNS`
                    WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                    AND `TABLE_NAME`='$main_table'
                    "
                );
                $existing_fields = array();
                foreach ($main_columns as $k => $v) {
                    $existing_fields[] = $v->COLUMN_NAME;
                }

				// If we have any matches...insert
                if ($wpcinsert) {
                    // process and insert
                    foreach ($wpdatapre as $k => $v) {
                        if (in_array($k, $existing_fields)) {
                            $wpdatafinal[$k] = $v;
                            //echo "$k set to $v \n\n";
                        }
                    }
                    $wpdatafinal['creation_date'] = current_time( 'mysql' );
                    $wpdatafinal['created_by']    = __('Gravity Form Submittal', 'shwcp');
                    $wpdatafinal['updated_date']  = current_time( 'mysql' );
                    $wpdatafinal['updated_by']    = __('Gravity Form Submittal', 'shwcp');
                    // remove any fields that shouldn't be changed
                    unset($wpdatafinal['id']);

                    //echo "Fields to insert \n\n";
                    //print_r($wpdatafinal);

					/* Field Checks */
                	$sorting = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . SHWCP_SORT . $db);
                	$sst = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . SHWCP_SST . $db);
                	foreach ($wpdatafinal as $f => $v) {
                    	foreach ($sorting as $k2 => $v2) {
                        	if ($v2->orig_name == $f) {
								if ($v2->field_type == '9') {  // Checkbox handling, these are currently 1 for 1
									if (!empty(trim($v))) {
										$wpdatafinal[$f] = '1';
									} else {
										$wpdatafinal[$f] = '0';
									}

                            	} elseif ($v2->field_type == '10') {
                                	$sst_type = $wpdb->get_var(
                                            "SELECT sst_type FROM " . $wpdb->prefix . SHWCP_SST . $db
                                            . " where sst_type_desc='$f' limit 1"
                                        );
                                	$value = $this->sst_update_checkdb($v, $sst, $f, $sst_type, $db);
                                	$wpdatafinal[$f] = $value;

								} elseif ($v2->field_type == '777') { // multi-select options id or create for each one
                            		$value = array();
                                	if ($v != '') {
                                   		$value_array = json_decode($v);
										foreach ($value_array as $v_k => $v_v) {	
                                   			$sst_type = $wpdb->get_var(
                                       			"SELECT sst_type FROM " . $wpdb->prefix . SHWCP_SST . $db
                                       			. " where sst_type_desc='$f' limit 1"
                                   			);
                                   			$value[] = $this->sst_update_checkdb($v_v, $sst, $f, $sst_type, $db);
										}
										$wpdatafinal[$f] = json_encode($value);
                               		}
                        		}
                        	}
                    	}
                	}

					// Prepare insert
                    foreach ($wpdatafinal as $f => $v) {
                        $format[] = '%s';
                    }

                    $wpdb->insert(
                        $wpdb->prefix . SHWCP_LEADS . $db,
                        $wpdatafinal,
                        $format
                    );
				}
			}
		}			

		/* End Gravity Form Methods */


		/*
		 * Lookup database to use if non-default for associating entry
		 */
		private function search_option_dbname($name='') {
            global $wpdb;
			$options_table = $wpdb->prefix . 'options';
            $option_entry = 'shwcp_main_settings';
            $dbs = $wpdb->get_results("SELECT * FROM $options_table WHERE `option_name` LIKE '%$option_entry%'");
            $db_number = '';
			//print_r($dbs);
            foreach ($dbs as $k => $option) {
				$db_values = unserialize($option->option_value);
				//print_r($db_values);
				$database_name = $db_values['database_name'];
                if ($database_name == $name) {
					if ($option_entry != $option->option_name) { //Only non-default dbs need remove underscore, have db number 
                    	$remove_name = '/^' . $option_entry . '_/';  // Just get the database number
                    	$db_number = preg_replace($remove_name, '', $option->option_name);
					}
                }
            }
			//echo "DB = " . $database_name . " db # = " . $db_number;
            return $db_number;
        }

		/*
         * Update the database if needed with new sst otherwise return existing id
         */
        public function sst_update_checkdb($value, $sst, $choice, $sst_type, $db) {
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
                    $wpdb->prefix . SHWCP_SST . $db,
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
		 * Split up a name into two parts First and last taking into account that there might be a middle
		 * uses regex that accepts any word character or hyphen in last name
		 * Currently used in GF only
		 */
		public function split_name($name) {
    		$name = trim($name);
    		$last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    		$first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
    		return array($first_name, $last_name);
		} 

	}
