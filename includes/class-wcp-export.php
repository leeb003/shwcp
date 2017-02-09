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

			// Users that manage their own leads can export only their leads
			$ownleads = '';
            $this->get_the_current_user();
            if ($this->current_access == 'ownleads') {
				$ownleads = 'AND l.owned_by=\'' . $this->current_user->user_login . '\'';
            }

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
					if ('l_source'    == $v['orig_name']          // ssts & dropdowns
                    	|| 'l_status' == $v['orig_name']
                    	|| 'l_type'   == $v['orig_name']
						|| 10         == $v['field_type']
                	) {
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

			$leads = $wpdb->get_results(
                "
                    SELECT l.*, sst1.sst_name as source, sst2.sst_name as status, sst3.sst_name as type
                    FROM $this->table_main l, $this->table_sst sst1, $this->table_sst sst2, $this->table_sst sst3
                    WHERE l.l_source = sst1.sst_id
					$range
					$filter
                    AND l.l_status = sst2.sst_id
                    AND l.l_type = sst3.sst_id
					$ownleads
                "
            );

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
            foreach ($leads as $k => $v) {
				$col = 0;
                foreach ($headers as $hv) {  // use header to get them in order
					if ($hv == 'small_image') {   // small image links
						if ($leads[$k]->small_image !='') {
							$small_image = $this->shwcp_upload_url . '/' . $leads[$k]->small_image;
						} else {
							$small_image = '';
						}
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $small_image);
						$col++;
					} elseif ($hv == 'lead_files') { // lead file links
						$lead_links = '';
						if ($leads[$k]->lead_files !='') {
                        	$lead_files = unserialize($leads[$k]->lead_files);
                        	foreach ($lead_files as $file => $value) {
                            	$lead_file_url = $this->shwcp_upload_url . '/' . $leads[$k]->id . '-files/' . $value['name'];
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
                            	if ($k2 == 'l_source') { $trans = 'source'; }
                            	elseif ($k2 == 'l_status') { $trans = 'status'; }
                            	elseif ($k2 == 'l_type') { $trans = 'type'; }
                            	else { $trans = $k2; }
								
								$real_value = $leads[$k]->$trans;
								/* 
								 * check for dropdown type to lookup real value instead of integer
								 */
								foreach ($export_fields as $e => $ev) {
                        			if ($k2 == $ev->orig_name) {
										if ($ev->field_type == '10') { // 10 is dropdown field type
											$real_value = $wpdb->get_var(
											"SELECT sst_name FROM $this->table_sst where sst_id=" . $real_value . " limit 1"
											);
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

			// Choose output format 
			if ($type == 'csv') {    // Save csv FILE

    			$objWriter = new PHPExcel_Writer_CSV($objPHPExcel);
    			header('Content-Encoding: UTF-8');
   	 			header('Content-type: text/csv; charset=UTF-8');
    			header('Content-Disposition: attachment; filename=WP-Contacts-Export.csv');
    			echo "\xEF\xBB\xBF"; // UTF-8 BOM
                $objWriter->save('php://output');

			} elseif ($type == 'excel') {  // Save Excel 2007 file
    			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
    			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    			header('Cache-Control: no-store, no-cache, must-revalidate');
    			header('Cache-Control: post-check=0, pre-check=0', false);
    			header('Pragma: no-cache');
    			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    			header('Content-Disposition: attachment;filename=WP-Contacts-Export.xlsx');
                $objWriter->save('php://output');
			}
			exit;
        }
	} // end class
