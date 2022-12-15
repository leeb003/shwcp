<?php
/**
 * WCP Class for frontend display entry export csv or xlsx, extends main class
 */

    class wcp_export extends main_wcp {
        // properties

        // methods

		//public function __construct() {
		//	parent::__construct();
		//}

        /**
         * Frontend handle request
         **/
        public function wcpexport_callback() {
			global $wpdb;
			$postID = intval($_POST['postID']);

			$this->load_db_options($postID); // load the current tables and options

			// Users that manage their own leads can export only their leads, custom role with own setting the same.
			$ownleads = '';
            $this->get_the_current_user();
			$custom_role = $this->get_custom_role();

            if ( !$custom_role['access'] && $this->current_access == 'ownleads') {
				$ownleads = 'AND l.owned_by=\'' . $this->current_user->user_login . '\'';
            } elseif ($custom_role['access'] && $custom_role['perms']['access_export'] == 'own') {
				$ownleads = 'AND l.owned_by=\'' . $this->current_user->user_login . '\'';
			}


			/* Front page export current view */
			if (isset($_POST['front_page'])) { // front end export
				$params = isset($_POST['get_params']) ? $_POST['get_params'] : array();
	
				$sorting   = $wpdb->get_results("SELECT * from $this->table_sort where sort_active=1 order by sort_number");
            	$all_sort  = $wpdb->get_results("SELECT * from $this->table_sort");
            	$filtering = $wpdb->get_results(
                	"SELECT * from $this->table_sort where front_filter_active=1 order by front_filter_sort");

            	$originals = array();  // array for comparing the field request against to make sure its legit
            	foreach($all_sort as $k => $v) {
                	$originals[] = $v->orig_name;
            	}

            	$sort = 'DESC'; // default
            	$searchset = false;
            	$search_select = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_number asc");  // Searching
            	$search = '';

            	/* 
             	 * order by default from settings if set and field is still present
             	 */
            	$order_field = isset($this->first_tab['default_sort']) ? $this->first_tab['default_sort'] : 'id';
            	$order_dir   = isset($this->first_tab['default_sort_dir']) ? $this->first_tab['default_sort_dir'] : 'desc';
            	// check if field exists
            	$default_field_exists = $wpdb->get_var(
                	"
                	SELECT `COLUMN_NAME`
                	FROM `INFORMATION_SCHEMA`.`COLUMNS`
                	WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                	AND `TABLE_NAME`='$this->table_main'
                	AND `COLUMN_NAME`='$order_field'
                	"
            	);
            	if (!$default_field_exists) {   // If the default field is set but been deleted default back to id...
                	$order_field = 'id';
            	}

            	$order_by = 'order by ' . $order_field . ' ' . $order_dir;  // default sort view
            	// End default sorting logic

            	// get sst info for filtering
            	$sst_pre = __('All', 'shwcp');
            	$sst_all = $wpdb->get_results("select * from $this->table_sst order by sst_order asc");
			
            	// Searching query
            	if (isset($params['wcp_search']) && $params['wcp_search'] == 'true') {
                	$searchset = true;
                	if (isset($params['s_field'])) {
                    	if ($params['s_field'] == 'wcp_all_fields') {
                        	$field = 'wcp_all_fields';
                    	} else {
                        	foreach ($originals as $k => $v) { // verify query sent against our fields
                            	if ($params['s_field'] == $v) {
                                	$field = $v;
                            	}
                        	}
                    	}
                	}
                	// check for dropdown type field (similar to source, status, type)
                	$dropdown_search = false;
                	foreach($all_sort as $k => $v) {
                    	if ($field == $v->orig_name) {
                        	if ($v->field_type == '10') {
                            	$dropdown_search = true;
                        	}
                    	}
                	}


                	if (isset($params['q']) && $params['q'] != '') {
                    	// field conditionals
                    	if ($dropdown_search) {
                        	$q = $wpdb->esc_like($params['q']);
                        	$check = $wpdb->get_row(
								"select * from $this->table_sst where sst_type_desc='$field' and sst_name LIKE '%$q%'"
    						);
                        	$real_val = isset($check->sst_id) ? $check->sst_id : 'NULL'; // To avoid db errors
                        	$search = 'AND l.' . $field . '=' . $real_val;
                    	} elseif ( $field == 'wcp_all_fields' ) {  // all fields search
                        	$search = '';
                        	$q = $wpdb->esc_like($params['q']);
                        	foreach($originals as $k => $v) {
                            	$q_names[] = 'l.' . $v;
                        	}
                        	$all_search = implode(", '',", $q_names);
                        	$search = ' and Concat(' . $all_search . ') like "%' . $q . '%"';

                    	} else { // all other fields with like check
                        	$q = $wpdb->esc_like($params['q']);
                        	$q = esc_sql($q);
                        	// check for date field searches that are in other formats
                        	$db_format = "Y-m-d H:i:s";
                        	$set_format = "$this->date_format $this->time_format";
                        	if ($db_format != $set_format) {
                            	foreach ($all_sort as $k2 => $v2) {
                                	if ($v2->orig_name == $field  && $v2->field_type == '7') {
                                    	$q = $this->get_date_pieces($q);
                                	}
                            	}
                            	if ($field == 'updated_date' || $field == 'creation_date') {  // checks for built in date fields
                                	$q = $this->get_date_pieces($q);
                            	}
                        	}

                        	$search = 'AND l.' . $field . ' LIKE "%' . $q . '%"';
                    	}

                    	// sort search results
					
                    	if (isset($params['sort'])) {  // Sorting query

                        	if ('asc' == $params['sort']) {
                            	$sort = 'ASC';
                        	} else {
                            	$sort = 'DESC';
                        	}
                        	$field = "id"; // default
                        	if (isset($params['field'])) {
                            	foreach ($originals as $k => $v) { // verify query sent against our fields
                                	if ($params['field'] == $v) {
                                    	$field = $v;
                                	}
                            	}
                        	}
                        	$field = 'l.' . $field;
                        	$order_by = 'order by ' . $field . ' ' . $sort;

                    	}  // end sort search results
                	}

            	} else if (isset($params['sort'])) {  // Sorting query

                	if ('asc' == $params['sort']) {
                    	$sort = 'ASC';
                	} else {
                    	$sort = 'DESC';
                	}
                	$field = "id"; // default
                	if (isset($params['field'])) {
                    	foreach ($originals as $k => $v) { // verify query sent against our fields
                        	if ($params['field'] == $v) {
                            	$field = $v;
                        	}
                    	}
                	}
                	$field = 'l.' . $field;
                	$order_by = 'order by ' . $field . ' ' . $sort;
            	} // end sorting section

            	// dropdown filters type 10 fields
            	$dropdowns = array();
            	$dropdown_filter = array();
            	foreach ($filtering as $k => $v) {
                	$current = $sst_pre . ' ' . $v->translated_name;
                	$num_value = false;
                	if (array_key_exists($v->orig_name, $params)) {
                    	$num_value = intval($params[$v->orig_name]);
                    	$dropdown_filter[] = 'AND l.' . $v->orig_name . '=' . $num_value;
                	}
                	foreach($sst_all as $sst_k => $sst_v) {
                    	if ($sst_v->sst_id == $num_value) {
                        	$current = '<span class="wcp-primary">' . $sst_v->sst_name . '</span>';
                    	}
                    	$trans_name = stripslashes($v->translated_name);
                    	$default_url = esc_url(remove_query_arg( array($v->orig_name)));
                    	$dropdowns[$v->orig_name] = array(
                        	'current'       => $current,
                        	'trans'         => $trans_name,
                        	'sst_name'      => $sst_v->sst_name,
                        	'default_url'   => $default_url,
                        	'value'         => $num_value
                    	);
                	}
            	}


            	$dropdown_q = implode(' ', $dropdown_filter);

            	$entries = $wpdb->get_results(
                	"
                    	SELECT l.*
                    	FROM $this->table_main l
                    	WHERE 1=1
                    	$dropdown_q
                    	$ownleads
                    	$search
                    	$order_by
                	"
            	);

			} else {  /* Originates from Export page  */
				// Row Range export
				$range = '';
				$filter = '';
				if (isset($_POST['rowrange'])) {
					$fromrow = abs(intval($_POST['fromrow']));
					$torow = abs(intval($_POST['torow']));
					if ($torow < $fromrow) {  // if they are goofing off, set it to the same number
						$torow = $fromrow;
					}
					$range = "AND l.id BETWEEN $fromrow AND $torow";
				} elseif (isset($_POST['rowfilter'])) { // Filtering
					$filter_sel = $_POST['filter-sel'];
					$filter_val = $_POST['filter-val'];
					$sorting = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_ind_number asc");
					//$sst     = $wpdb->get_results ("SELECT * from $this->table_sst order by sst_order");
					$i = 0;
					$field_array = array();
					foreach ($filter_sel as $k => $v) {
						// build arrays to check for type 10 (dropdowns) as they need to be treated like ssts
						foreach ($sorting as $k2 => $v2) {
							if ($v == $v2->orig_name) {
								$field_array[] = array(
									'translated_name' => $v2->translated_name,
									'orig_name'       => $v2->orig_name,
									'field_type'      => $v2->field_type,
									'query_val'       => $filter_val[$i]
								);
								$i++;
							}
						}
					}
					foreach ($field_array as $k => $v) {
						if ( 10 == $v['field_type'] ) {    // dropdown field types
							$matches = $wpdb->get_results("SELECT * from $this->table_sst where sst_name like '%" 
								 . $v['query_val'] . "%'");
							// build query
							$len = count($matches);
							$i = 0;
							$filter .= 'AND l.' . $v['orig_name'] . ' IN (';
							foreach($matches as $mk => $mv) {
								$filter .= "'" . $mv->sst_id . "'";
								if ($i != $len -1 ) {  // not the last one add a comma
									$filter .= ', ';
								}
								$i++;
							}
							$filter .= ')';
						} else { // other fields
							$filter .= "AND l." . $v['orig_name'] . " like '%" . $v['query_val'] . "%'";
						}
						$filter .= "\n";
					}
					//print_r($filter);
					//echo "\n";
					//print_r($field_array);
					//die();
				}
				$entries = $wpdb->get_results(
                	"
                    	SELECT l.*
                    	FROM $this->table_main l WHERE 1=1
                    	$range
						$filter
                    	$ownleads
                	"
            	);

			} // end page origin check


			// nonce check
			$nonce = isset($_POST['export-nonce']) ? $_POST['export-nonce'] : '';
			if (!wp_verify_nonce( $nonce, 'wcpexport' )) {
				wp_die("Nonce unverified.");
			}
			// Logging class
            require_once(SHWCP_ROOT_PATH . '/includes/class-wcp-logging.php');
            $wcp_logging = new wcp_logging();
			$event = __('Exported Entries', 'shwcp');
			$detail = '';
            $wcp_logging->log($event, $detail, $this->current_user->ID, $this->current_user->user_login, $postID);


			$fields = isset($_POST['fields']) ? $_POST['fields'] : array();
			$type = isset($_POST['output-type']) ? $_POST['output-type'] : 'csv';
			$columns = array();

			// Get Headers
			$export_fields = $wpdb->get_results("SELECT * from $this->table_sort order by sort_number asc");
            foreach ($fields as $k => $v) {
				if ('photo-links' == $k) {
					$headers[] = 'small_image';
				} elseif ('file-links' == $k) {
					$headers[] = 'lead_files';
				} else {
					foreach ($export_fields as $k2 => $v2) {
                		if ($k == $v2->orig_name) {
                    		$headers[] = $v2;
                		}
					}
				}
            }

			require_once(SHWCP_ROOT_PATH . '/includes/PHPExcel/Classes/PHPExcel.php');

			// Create new PHPExcel object
			$objPHPExcel = new PHPExcel();

			// Set document properties
			$objPHPExcel->getProperties()->setCreator("WP Contacts")
							 ->setLastModifiedBy("WP Contacts")
							 ->setTitle("Export")
							 ->setSubject("Export")
							 ->setDescription("Exported entries from WP Contacts.");


			// Add some data
			// Sheet 0 //
			$objPHPExcel->setActiveSheetIndex(0);
			// Header Row
			$row = 1;
			$col = 0;
			foreach ($headers as $val) {
				if ('small_image' == $val) {
					$small_image_header = __('Image Link', 'shwcp');
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $small_image_header);
				} elseif ('lead_files' == $val) {
					$lead_files_header = __('File Links', 'shwcp');
					$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $lead_files_header);
				} else {
    				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $val->translated_name);
				}
    			$col++;
			}

			// Lead Data
			$row++;
            foreach ($entries as $k => $v) {
				$col = 0;
                foreach ($headers as $hv) {  // use header to get them in order
					if ($hv == 'small_image') {   // small image links
						if ($entries[$k]->small_image !='') {
							$small_image = $this->shwcp_upload_url . '/' . $entries[$k]->small_image;
						} else {
							$small_image = '';
						}
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $small_image);
						$col++;
					} elseif ($hv == 'lead_files') { // lead file links
						$lead_links = '';
						if ($entries[$k]->lead_files !='') {
                        	$lead_files = unserialize($entries[$k]->lead_files);
                        	foreach ($lead_files as $file => $value) {
                            	$lead_file_url = $this->shwcp_upload_url . '/' . $entries[$k]->id . '-files/' . $value['name'];
                            	$lead_links .= $lead_file_url . "    ";
							}
						} else {
							$lead_links = '';
						}
						$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $lead_links);
						$colStr = PHPExcel_Cell::stringFromColumnIndex($col);
						$objPHPExcel->getActiveSheet()->getStyle($colStr . $row)->getAlignment()->setWrapText(true);
                        $col++;
					} else {  // all other fields
                    	foreach ($v as $k2 => $v2) {
                        	if ($k2 == $hv->orig_name) {
                            	$trans = $k2; 
								
								$real_value = $entries[$k]->$trans;
								/* 
								 * check for dropdown type to lookup real value instead of integer
								 */
								foreach ($export_fields as $e => $ev) {
                        			if ($k2 == $ev->orig_name) {
										if ($ev->field_type == '10') { // 10 is dropdown field type
											$real_value = $wpdb->get_var(
											"SELECT sst_name FROM $this->table_sst where sst_id=" . $real_value . " limit 1"
											);
										} elseif ($ev->field_type == '777') { // 777 is multiselect field type
                                            $real_values = json_decode($real_value);
                                            $values_array = array();
											if (!empty($real_values)) {
                                            	foreach ($real_values as $realk => $realv) {
                                                	$values_array[] = $wpdb->get_var(
                                                    	"SELECT sst_name FROM $this->table_sst where sst_id=" . $realv. " limit 1"
                                                	);
                                            	}
                                            	$real_value = implode('; ', $values_array);
											} else {
												$real_value = '';
											}
                                        }	
									}
								}

								$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $real_value);
								$col++;
							}
                        }
                    }
                }
				$row++;
            }
			// Rename worksheet
			$objPHPExcel->getActiveSheet()->setTitle('Export');

			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$objPHPExcel->setActiveSheetIndex(0);
			$export_file = $this->first_tab['export_file'];
			// Choose output format 
			if ($type == 'csv') {    // Save csv FILE

    			$objWriter = new PHPExcel_Writer_CSV($objPHPExcel);
    			header('Content-Encoding: UTF-8');
   	 			header('Content-type: text/csv; charset=UTF-8');
    			header("Content-Disposition: attachment; filename=$export_file.csv");
    			echo "\xEF\xBB\xBF"; // UTF-8 BOM
                $objWriter->save('php://output');

			} elseif ($type == 'excel') {  // Save Excel 2007 file
    			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
    			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    			header('Cache-Control: no-store, no-cache, must-revalidate');
    			header('Cache-Control: post-check=0, pre-check=0', false);
    			header('Pragma: no-cache');
    			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    			header("Content-Disposition: attachment;filename=$export_file.xlsx");
                $objWriter->save('php://output');
			}
			exit;
        }
	} // end class
