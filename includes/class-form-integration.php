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
            	// print_r($wpdatafinal);
            	if (empty($wpdatafinal)) {  // if they haven't set any valid fields, we don't want it
                	return;
            	}

            	$wpdatafinal['creation_date'] = current_time( 'mysql' );
            	$wpdatafinal['created_by']    = __('CF7 Form Submittal', 'shwcp');
            	$wpdatafinal['updated_date']  = current_time( 'mysql' );
            	$wpdatafinal['updated_by']    = __('CF7 Form Submittal', 'shwcp');
            	// remove any fields that shouldn't change
            	unset($wpdatafinal['id']);

            	// get the sst's if the name doesn't match assign to default

            	/* Source */
            	if (!isset($wpdatafinal['l_source'])) {
                	$wpdatafinal['l_source'] = 'Not Set';
            	}
            	$source = $wpdb->get_var("
                	SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                	" where sst_name='{$wpdatafinal['l_source']}' and sst_type=1
                	");
            	if (!$source) {
                	$source = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db .
                	" where sst_type=1 AND sst_default=1"
                	);
            	}
            	$wpdatafinal['l_source'] = $source; // Overwrite with id
            	/* Status */
            	if (!isset($wpdatafinal['l_status'])) {
                	$wpdatafinal['l_status'] = 'Not Set';
            	}

            	$status = $wpdb->get_var("
                	SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                	" where sst_name='{$wpdatafinal['l_status']}' and sst_type=2
                	");
            	if (!$status) {
                	$status = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                	" where sst_type=2 AND sst_default=1"
                	);
            	}
            	$wpdatafinal['l_status'] = $status;

            	/* Type */
            	if (!isset($wpdatafinal['l_type'])) {
                	$wpdatafinal['l_type'] = 'Not Set';
            	}
            	$type = $wpdb->get_var("
                	SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                	" where sst_name='{$wpdatafinal['l_type']}' and sst_type=3
                	");
            	if (!$type) {
                	$type = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                	" where sst_type=3 AND sst_default=1"
                	);
            	}
            	$wpdatafinal['l_type'] = $type;

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

            	$wpdb->insert(
                	$wpdb->prefix . SHWCP_LEADS . $db,
                	$wpdatafinal,
                	$format
            	);

            	//print_r($wpdatafinal);
        	}

        	//return $posted_data;
    	}
		
	    public function wpcf7_add_shortcode_wpcontacts() {
        	if (defined('WPCF7_VERSION')) {  // just to make sure
            	wpcf7_add_shortcode('wpcontacts', 
					array($this, 'wpcf7_wpcontacts_shortcode_handler'), true);
        	}
    	}

    	public function wpcf7_wpcontacts_shortcode_handler($tag) {
        	if (!is_array($tag)) return '';
        	$content = trim($tag['content']);
        	$content = preg_split('/\n/', $content);
        	$wp_mappings = array();
        	$wp_data = array();
			$wp_database = '';
        	foreach ($content as $entry) {  // Get data
            	$entry = trim($entry);
            	if (preg_match("/^wpfieldmap/i", $entry)) { // field map entry 
                	list($fieldmaptext, $mappings) = explode("=", $entry);
                	$mapper = explode(":", $mappings);
                	$wp_mappings[$mapper[0]] = $mapper[1];
				} else if (preg_match("/^wpdatabasemap/i", $entry)) { // Database mapping
					$mapper = explode("=", $entry);
					$wp_database = $mapper[1];
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

        	$html .= '<input type="hidden" name="wpcontacts" value="yes" />';

        	//$html .= print_r($wp_mappings, false);
        	//$html .= print_r($wp_data, false);
        	return $html;
    	}
		/* End Contact Form 7 Methods */

		/* Ninja Form Methods */
		public function ninja_forms_wpcontacts() {
			global $ninja_forms_processing;
			$wpcinsert = false;
			
			// get the submitted data
			$all_fields = $ninja_forms_processing->get_all_fields();
			$field_settings = $ninja_forms_processing->get_field_settings();
			if ( is_array( $all_fields ) ) {
				global $wpdb;

				$wpdatapre = array();
				$wpdatabasemap = '';
				foreach ($all_fields as $field_id => $user_value ) {
					//echo $user_value . "\n";
					// We care about the admin field for mapping
					$field_settings = $ninja_forms_processing->get_field_settings($field_id);
					foreach ($field_settings['data'] as $field_setting => $field_value) {
						if ($field_setting == 'admin_label') {  // check for our naming convention
							$admin_label = explode( '-', $field_value );
							if ($admin_label[0] == 'wpcontacts') {
								// We have a match
								$wpcinsert = true;
								$wpdatapre[$admin_label[1]] = $user_value;
								//echo $admin_label[0] . '  ' . $wp_field . " MATCH FOR FIELD\n\n";
							} elseif ($field_value == trim('wpdatabasemap') ) {  // database to use
								$wpdatabasemap = trim($user_value);
							}	
								
						}
					}
					//print_r($field_settings);
					// do something
				}

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

					// get the sst's if the name doesn't match assign to default

                	/* Source */
                	if (!isset($wpdatafinal['l_source'])) {
                    	$wpdatafinal['l_source'] = 'Not Set';
                	}
                	$source = $wpdb->get_var("
                    	SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                    	" where sst_name='{$wpdatafinal['l_source']}' and sst_type=1
                    	");
                	if (!$source) {
                    	$source = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                    	" where sst_type=1 AND sst_default=1"
                    	);
                	}
                	$wpdatafinal['l_source'] = $source; // Overwrite with id
                	/* Status */
                	if (!isset($wpdatafinal['l_status'])) {
                    	$wpdatafinal['l_status'] = 'Not Set';
                	}

                	$status = $wpdb->get_var("
                    	SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                    	" where sst_name='{$wpdatafinal['l_status']}' and sst_type=2
                    	");
                	if (!$status) {
                    	$status = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                    	" where sst_type=2 AND sst_default=1"
                    	);
                	}
                	$wpdatafinal['l_status'] = $status;

                	/* Type */
                	if (!isset($wpdatafinal['l_type'])) {
                    	$wpdatafinal['l_type'] = 'Not Set';
                	}
                	$type = $wpdb->get_var("
                    	SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                    	" where sst_name='{$wpdatafinal['l_type']}' and sst_type=3
                    	");
                	if (!$type) {
                    	$type = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                    	" where sst_type=3 AND sst_default=1"
                    	);
                	}
                	$wpdatafinal['l_type'] = $type;

					//echo "Fields to insert \n\n";
					//print_r($wpdatafinal);

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

                	$wpdb->insert(
                    	$wpdb->prefix . SHWCP_LEADS . $db,
                    	$wpdatafinal,
                    	$format
                	);
				} // end match and insert
							
			}			
		}

		/* End Ninja Form Methods */

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
									foreach ($form['fields'][$forminc]->inputs as $input => $inputv) {
										$entryid = $inputv['id'];
										$wpdatapre[$label_value[1]] .= $entry["$entryid"] . ' '; 
										/* Have to use the quotes to access the ids (e.g. 11.3) above or php can't find them!! */
									}

								} else { // The other fields
									$entryid = $form['fields'][$forminc]->id;
									$wpdatapre[$label_value[1]] = $entry[$entryid];
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

					// get the sst's if the name doesn't match assign to default

                    /* Source */
                    if (!isset($wpdatafinal['l_source'])) {
                        $wpdatafinal['l_source'] = 'Not Set';
                    }
                    $source = $wpdb->get_var("
                        SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                        " where sst_name='{$wpdatafinal['l_source']}' and sst_type=1
                        ");
                    if (!$source) {
                        $source = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                        " where sst_type=1 AND sst_default=1"
                        );
                    }
                    $wpdatafinal['l_source'] = $source; // Overwrite with id
                    /* Status */
                    if (!isset($wpdatafinal['l_status'])) {
                        $wpdatafinal['l_status'] = 'Not Set';
                    }

                    $status = $wpdb->get_var("
                        SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                        " where sst_name='{$wpdatafinal['l_status']}' and sst_type=2
                        ");
                    if (!$status) {
                        $status = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                        " where sst_type=2 AND sst_default=1"
                        );
                    }
                    $wpdatafinal['l_status'] = $status;

                    /* Type */
                    if (!isset($wpdatafinal['l_type'])) {
                        $wpdatafinal['l_type'] = 'Not Set';
                    }
                    $type = $wpdb->get_var("
                        SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                        " where sst_name='{$wpdatafinal['l_type']}' and sst_type=3
                        ");
                    if (!$type) {
                        $type = $wpdb->get_var("SELECT sst_id FROM " . $wpdb->prefix . SHWCP_SST . $db . 
                        " where sst_type=3 AND sst_default=1"
                        );
                    }
                    $wpdatafinal['l_type'] = $type;

                    //echo "Fields to insert \n\n";
                    //print_r($wpdatafinal);

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
            $databases = array();
            $db_number = '';
            foreach ($dbs as $k => $option) {
                $db_options = get_option($option->option_name);
                $database_name = $db_options['database_name'];
                $db_options = get_option($option->option_name);
                if ($database_name == $name) {
                    $remove_name = '/^' . $option_entry . '_/';  // Just get the database number
                    $db_number = preg_replace($remove_name, '', $option->option_name);
                }
            }
            return $db_number;
        }

	}
