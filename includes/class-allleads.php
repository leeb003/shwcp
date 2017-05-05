<?php
/**
 * Displays all leads extends front class
 */

    class allleads extends wcp_front {
        // properties


        // methods

        /**
         * Default view for frontend showing all leads.
         * @access protected
         * @since 1.0.0
         * @return wcp_main
		**/
		protected function get_all_leads() {
			global $wpdb;
			global $wp;
			$current_db = '';
			$saved_db = $this->load_db_options();  // load database and options
            if ($saved_db
                && $saved_db != 'default'
            ) {
                $current_db = $saved_db;
            } 

            $shwcp_upload     = $this->shwcp_upload     . $current_db;
            $shwcp_upload_url = $this->shwcp_upload_url . $current_db;

			$this->get_the_current_user();  // load method for permissions from parent here
			$custom_role = $this->get_custom_role();
			//print_r($custom_role);
			$ownleads = '';
			if ( ($this->current_access == 'ownleads') 
				|| ($custom_role['access'] && $custom_role['perms']['entries_view'] == 'own') 
			) {

				$ownleads = 'AND l.owned_by=\'' . $this->current_user->user_login . '\'';
			}

			$wcp_main = '';

			$sorting = $wpdb->get_results(
                "
                    SELECT * from $this->table_sort where sort_active=1 order by sort_number
                "
            );
			$all_sort = $wpdb->get_results(
				" 
					SELECT * from $this->table_sort
				"
			);
			$filtering = $wpdb->get_results(
                "
                    SELECT * from $this->table_sort where field_type=10 and front_filter_active=1 order by front_filter_sort
                "
            );

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

			$paging_div = '';

			// get sst info for filtering
			$sst_pre = __('All', 'shwcp');
			$sst_all = $wpdb->get_results("select * from $this->table_sst order by sst_order asc");

			// Searching query
            if (isset($_GET['wcp_search']) && $_GET['wcp_search'] == 'true') {
				$searchset = true;
                if (isset($_GET['s_field'])) {
					if ($_GET['s_field'] == 'wcp_all_fields') {
						$field = 'wcp_all_fields';
					} else {
                    	foreach ($originals as $k => $v) { // verify query sent against our fields
                    		if ($_GET['s_field'] == $v) {
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


                if (isset($_GET['q']) && $_GET['q'] != '') {
				 	// field conditionals
					if ($dropdown_search) {
						$q = $wpdb->esc_like($_GET['q']);
						$check = $wpdb->get_row(
							"select * from $this->table_sst where sst_type_desc='$field' and sst_name LIKE '%$q%'"
						);
						$real_val = isset($check->sst_id) ? $check->sst_id : 'NULL'; // To avoid db errors
						$search = 'AND l.' . $field . '=' . $real_val;
					} elseif ( $field == 'wcp_all_fields' ) {  // all fields search
						$search = '';
						$q = $wpdb->esc_like($_GET['q']);
						foreach($originals as $k => $v) {
							$q_names[] = 'l.' . $v;
						}
						$all_search = implode(", '',", $q_names);
						$search = ' and Concat(' . $all_search . ') like "%' . $q . '%"';
						//foreach ($originals as $k => $v) { // build query
						//	$search .= ' AND l.' . $v . ' LIKE "%' . $q . '%"';
						//}


					} else { // all other fields with like check
						$q = $wpdb->esc_like($_GET['q']);
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

					if (isset($_GET['sort'])) {  // Sorting query

                		if ('asc' == $_GET['sort']) {
                    		$sort = 'ASC';
                		} else {
                    		$sort = 'DESC';
                		}
                		$field = "id"; // default
                		if (isset($_GET['field'])) {
                    		foreach ($originals as $k => $v) { // verify query sent against our fields
                        		if ($_GET['field'] == $v) {
                            		$field = $v;
                        		}
                    		}
                		}
                		$field = 'l.' . $field;
                		$order_by = 'order by ' . $field . ' ' . $sort;

					}  // end sort search results


				}

			} else if (isset($_GET['sort'])) {  // Sorting query

				if ('asc' == $_GET['sort']) {
					$sort = 'ASC';
				} else {
					$sort = 'DESC';
				}
				$field = "id"; // default
				if (isset($_GET['field'])) {
					foreach ($originals as $k => $v) { // verify query sent against our fields
						if ($_GET['field'] == $v) {
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
                if (array_key_exists($v->orig_name, $_GET)) {
                    $num_value = intval($_GET[$v->orig_name]);
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
			// Total lead count for pagination & display
			$lead_count = $wpdb->get_var(
				"
					SELECT count(*) as count 
                    FROM $this->table_main l
                    WHERE 1=1
					$dropdown_q
                    $ownleads
                    $search
                    $order_by
				");


            // Pagination & sorting vars
            $paginate = $this->first_tab['page_page']; // pagination set?
            $rpp = $this->first_tab['page_page_count']; // pagination count - results per page

          	if ('true' == $paginate) { // we are paginating
				if (isset($_GET["pages"])) {
                	$pages = intval($_GET["pages"]);
            	} else {
                	$pages = 1;
            	}
            	$tpages = ($lead_count) ? ceil($lead_count/$rpp) : 1;
            	$adjacents = '2';
            	$start_limit = ($pages -1) * $rpp;


               	$order_by .= ' LIMIT ' . $start_limit . ', ' . $rpp; // add limit
				//$reload = get_permalink();
				$reload = remove_query_arg( array('pages'));
                require_once(SHWCP_ROOT_PATH . '/includes/class-paginate.php');
                $wcp_paging = new paginate($reload, $pages, $tpages, $adjacents);
                $paging_div = $wcp_paging->getDiv();
           	}

            $leads = $wpdb->get_results(
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

			//print_r($leads);


            // sort the fields by building a new array
			$leads_total_text = __('Total Entries', 'shwcp');
			$select_all_text = __('Check All', 'shwcp');
			$unselect_all_text = __('Uncheck All', 'shwcp');
			$delete_selected_text = __('Delete Checked', 'shwcp');
			$search_text = __('Search', 'shwcp');

			if ($leads) {
            	foreach ($leads as $k => $v) {
                	foreach ($sorting as $sk => $sv) {  // use sorting array to get them in order
                    	foreach ($v as $k2 => $v2) {
                        	if ($k2 == $sv->orig_name) {
								$trans = $k2;

                            	$leads_sorted[$k][$sv->translated_name] = $leads[$k]->$trans;
                        	}
                    	}
                	}
                	$leads_sorted[$k]['wcp_lead_id'] = $v->id;    // keep track of the id
					$leads_sorted[$k]['owned_by_cust'] = $v->owned_by; // keep track of original owned by for custom access
            	}
            	$lead_columns = array_keys($leads_sorted[0]);
            	$page_ind_arg = add_query_arg( array('wcp' => 'entry'), get_permalink() );
			}

			// Filtering 
			$wcp_main = <<<EOC

                            <div class="row">
                                <div class="sst-bar col-sm-6 col-xs-12">
                                    <ul class="sst-select">
EOC;

			foreach ($filtering as $fk => $fv) {
				foreach ($dropdowns as $dk => $dv) {
					$orig_name = $current = $trans = $default_url = $value = ''; // clear out first just in case
					if ($fv->orig_name == $dk) {
						$orig_name = $dk;
						$current = $dv['current'];
						$trans = $dv['trans'];
						$default_url = $dv['default_url'];
						$value = $dv['value'];
						$wcp_main .= <<<EOC
                            
                                        <li><a href="#">$current</a>
											<ul>
												<li><a href="{$dv['default_url']}">$sst_pre $trans</a></li>
EOC;
						foreach ($sst_all as $sst_k => $sst_v) {
                       		if ($orig_name == $sst_v->sst_type_desc) {
                           		$sst_v->sst_name = stripslashes($sst_v->sst_name);
                           		$dd_link = esc_url(add_query_arg( array($orig_name => $sst_v->sst_id)));
                           		if ($value && $value==$sst_v->sst_id) {
                               		$wcp_main .= '<li><a class="wcp-primary" href="' . $dd_link . '">' . $sst_v->sst_name 
										   . '</a></li>';
                           		} else {
                               		$wcp_main .= '<li><a href="' . $dd_link . '">' . $sst_v->sst_name . '</a></li>';
                           		}
                       		}
                   		}
						$wcp_main .= '</ul></li>';
					}
				}
			}

			// Sorting for small menu
			$sort_text = __('Sort Entries', 'shwcp');
			$small_sort = '';
			$sort_activated="";
			$i = 0;
		    if (!empty($lead_columns)) {	
		    	foreach ($lead_columns as $k => $v) {
                	if ($v == 'wcp_lead_id' 
						|| $v == 'owned_by_cust' 
					) {
                    	continue; 
                	}
                	$orig_name = $sorting[$i]->orig_name;

                	//print_r($lead_columns);
                	// default arrow down
                	$arrow = '<i class="wcp-sm desc md-arrow-drop-down" title="' . __("Sort Descending", "shwcp") . '"> </i>';
                	$current_url = get_permalink(get_the_ID());
                	$current_url = remove_query_arg( array('pages'));
                	$sort_link = esc_url(add_query_arg( array('sort' => 'desc', 'field' => $orig_name), $current_url));

                	if (isset($_GET['field']) && $_GET['field'] == $orig_name) {
                    	if ( isset($_GET['sort']) && $_GET['sort'] == 'asc') {
                        	$sort_link = esc_url(add_query_arg( array('sort' => 'desc', 'field' => $orig_name), $current_url));
                        	$arrow = '<i class="wcp-sm wcp-primary asc md-arrow-drop-up" title="'
                            	. __("Sort Ascending", "shwcp") . '"> </i>';
							$sort_activated="wcp-primary";
                    	} else {
                        	$sort_link = esc_url(add_query_arg( array('sort' => 'asc', 'field' => $orig_name), $current_url));
                        	$arrow = '<i class="wcp-sm wcp-primary desc md-arrow-drop-down" title="'
                            	. __("Sort Descending", "shwcp") . '"> </i>';
							$sort_activated="wcp-primary";
                    	}
                	}

                	$link_beg = '<a href="' . $sort_link . '">';
                	$link_end = '</a>';

                	$small_sort .= '<li class="small-sort ' . $orig_name . ' ">' 
							    . $link_beg . $v . ' ' . $arrow . $link_end . '</li>';
                	$i++;
            	}
			}
		

			$wcp_main .= <<<EOC
										<li>	
											<a href="#" class="wcp-sort-menu">
												<i class="wcp-md md-sort $sort_activated" title="$sort_text"></i>
											</a>
											<ul>
												$small_sort
											</ul>
										</li>
										<li><a href="#" class="wcp-search-menu"><i class="wcp-md md-search"> </i></a></li>
EOC;
			// no access to adding for entry page and people who can't edit 
            if ($this->can_edit
            ) {
                $wcp_main .= '<li><i class="add-lead wcp-md md-add" title="' . __('Add Entry', 'shwcp') . '"> </i></li>';
            }
		
			$wcp_main .= <<<EOC
									</ul>
								</div>
								<div class="col-sm-6 col-xs-12 lead-count">
EOC;
		            if ($this->can_edit) { // user can edit leads
						$wcp_main .= <<<EOC
									<span class="wcp-button2 select-all-checked">$select_all_text</span>
									<span class="wcp-button2 select-all-checked" style="display:none;">$unselect_all_text</span>
									<span class="wcp-button2 delete-all-checked">$delete_selected_text</span>
EOC;
					/* Custom Role Select & Delete access */
					} elseif (isset($custom_role['perms'])) {
						if ( $custom_role['perms']['entries_delete'] == 'all'
                    		|| $custom_role['perms']['entries_delete'] == 'own'
                    	) {
							$wcp_main .= <<<EOC
                                    <span class="wcp-button2 select-all-checked">$select_all_text</span>
                                    <span class="wcp-button2 select-all-checked" style="display:none;">$unselect_all_text</span>
                                    <span class="wcp-button2 delete-all-checked">$delete_selected_text</span>
EOC;
						}
					}

					$wcp_main .= <<<EOC
                					<span class="wcp-primary">$lead_count</span> $leads_total_text
            					</div>
								<div class="second-menu col-md-12">
									<div class="wcp-search hidden-sm hidden-md hidden-lg">
                						<select class="wcp-select" style="display:none">
EOC;
					if (isset($this->first_tab['all_fields']) && $this->first_tab['all_fields'] == 'true') {  
						// if all fields search is enabled
						$all_fields_text = __('All Fields', 'shwcp');
						$wcp_main .= <<<EOC
											<option value="wcp_all_fields">$all_fields_text</option>
EOC;
					}


                    foreach ($search_select as $k => $v) {
						if ($v->orig_name != 'l_source'
                            && $v->orig_name != 'l_status'
                            && $v->orig_name != 'l_type'
                            && $v->field_type != '99'
                        ) {
                        	$wcp_main .= <<<EOC
											<option value="$v->orig_name">$v->translated_name</option>
EOC;
						}
                    }

			$wcp_main .= <<<EOC
                    					</select>
                    					<input class="wcp-search-input" placeholder="$search_text" type="search" value="" />
                					</div>
								</div>
							</div>
EOC;


		// No Results
        if (empty($leads)) {
            $no_results = __('No Results Found', 'shwcp');
            $wcp_main .= <<<EOC
        					<div class="no-results"><h3>$no_results</h3></div>
						</div>
EOC;
            return $wcp_main;
        }
            //Header row
            $i = 0;
			$wcp_main .= <<<EOC

							<table class="wcp-table">
            					<tr id='header-row' class='header-row'>

EOC;

			if ($this->first_tab['contact_image'] == 'true') {  // if display images set
            	$wcp_main .= <<<EOC
									<th class='contact-image'></th>
EOC;
			}

            //print_r($leads_sorted);
            foreach ($lead_columns as $k => $v) {
				if ($v == 'wcp_lead_id' 
                    || $v == 'owned_by_cust'
                ) {
                    continue;
                }
				$v = stripslashes($v);
				$orig_name = $sorting[$i]->orig_name;

                //print_r($lead_columns);
                // default arrow down
                $arrow = '<i class="wcp-sm desc md-arrow-drop-down" title="' . __("Sort Descending", "shwcp") . '"> </i>';
				$current_url = get_permalink(get_the_ID());
				$current_url = remove_query_arg( array('pages', 'field', 'sort'));
                $sort_link = esc_url(add_query_arg( array('sort' => 'desc', 'field' => $orig_name), $current_url));
				
                if (isset($_GET['field']) && $_GET['field'] == $orig_name) {
                    if ( isset($_GET['sort']) && $_GET['sort'] == 'asc') {
						$sort_link = esc_url(add_query_arg( array('sort' => 'desc', 'field' => $orig_name), $current_url));
                        $arrow = '<i class="wcp-sm wcp-primary asc md-arrow-drop-up" title="'
                            . __("Sort Ascending", "shwcp") . '"> </i>';
                    } else {
						$sort_link = esc_url(add_query_arg( array('sort' => 'asc', 'field' => $orig_name), $current_url));
                        $arrow = '<i class="wcp-sm wcp-primary desc md-arrow-drop-down" title="'
                            . __("Sort Descending", "shwcp") . '"> </i>';
                    }
                }

				$link_beg = '<a href="' . $sort_link . '">';
                $link_end = '</a>';

                $wcp_main .= <<<EOC

									<th class="table-head $orig_name">$link_beg$v $arrow$link_end</th>

EOC;
                $i++;
            }
			$edit_text = __('Quick Edit', 'shwcp');
			if ($this->can_edit) { // user can edit leads
            	$wcp_main .= <<<EOC
									<th class='edit-header'>$edit_text</th>

EOC;
			/* Custom Access can edit all or own - add quick edit header */
			} elseif ($custom_role['access'] && $custom_role['perms']['entries_edit'] == 'all'
				|| $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'own') {
				$wcp_main .= <<<EOC
                                    <th class='edit-header'>$edit_text</th>

EOC;
			}
				


			$wcp_main .= <<<EOC
								</tr>
EOC;
			
			$i = 1;
			$leads_sorted = apply_filters('wcp_leads_filter', $leads_sorted); // Add filter for just the lead data
            foreach ($leads_sorted as $r => $lead) {
                $i++;
                $alt = $i&1;
                $wcp_main .= <<<EOC

								<tr class='wcp-row{$alt} wcp-lead-{$lead['wcp_lead_id']}'>
EOC;
                $i2 = 1;
                // get the image
                $lead_row = $wpdb->get_row ("SELECT * FROM $this->table_main WHERE id = {$lead['wcp_lead_id']}");
                if ($lead_row->small_image == '') {
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
                    	$thumb = SHWCP_ROOT_URL . '/assets/img/default_entry_th.png';
					}
                } else {
                    $parts = explode(".", $lead_row->small_image);
                    $ext = array_pop($parts);
                    $thumb = $shwcp_upload_url . '/' . $parts[0] . '_th.' . $ext;
                }

				if ($this->first_tab['contact_image'] == 'true') { // if display images set
					$wcp_main .= <<<EOC

									<td class="image-td" style="background: transparent url($thumb) no-repeat 20px center;">
									<a class="individual-link" href="{$page_ind_arg}&amp;entry={$lead['wcp_lead_id']}"> </a>
									</td>

EOC;
				}

                foreach ($lead as $k => $v) {
					$v = stripslashes($v);
					if ($k == 'wcp_lead_id' 
                        || $k == 'owned_by_cust'
                    ) {
						// skip displaying the id
                    } else {
						// Convert keys and values to valid css classes for the td for tagetting styles
						$col_num = 'colnum-' . $i2;
						$key_name = substr($k, 0, 12);
						$val_name = substr($v, 0, 12);
						$trans_key = 'col-' . preg_replace('/\W+/','',strtolower(strip_tags($key_name)));
						$trans_val = 'val-' . preg_replace('/\W+/','',strtolower(strip_tags($val_name)));

						/* Field types selection - matches on translated name
						 *
						 * 1 = default text field
						 * 2 = text area 
						 * 3 = phone number
						 * 4 = email address
						 * 5 = website address
						 * 6 = Google map link
						 * 7 = date time picker - treated as text on front
						 * 11 = date picker - treated as text on front
						 * 8 = star rating
						 * 9 = checkbox
						 * 10 = dropdown field
						 */
						$td_content = '';
						$now = date('Y-m-d H:i:s');
						$now_time = date_create($now);

						foreach ($sorting as $sk => $sv) {
							if ($k == $sv->translated_name) {  // match up the sorting for each field to get the field type display

								if ($sv->orig_name == 'lead_files') { // Contact files display
									$max_display = 4;
									$file_data = unserialize($v);
									$td_content = '<div class="files-preview">';
									$count = count($file_data);
									$inc = 1;
									if (isset($file_data[0])) { // check that we have at least 1 file
										foreach ($file_data as $fk => $fv) {
											$file = $fv['name'];
											$ext  = pathinfo($file, PATHINFO_EXTENSION);
											$ext  = strtolower($ext);
											$file_url = $shwcp_upload_url . '/' . $lead['wcp_lead_id'] . '-files' . '/' . $file;
											// check for filetype image existing and link to default if it doesn't
											$preview_image_loc = SHWCP_ROOT_PATH . '/assets/img/filetypes/' . $ext . '.png';
											if ( !file_exists($preview_image_loc) ) {
												$preview_image_url = SHWCP_ROOT_URL . '/assets/img/filetypes/raw.png';
											} else {
												$preview_image_url = SHWCP_ROOT_URL . '/assets/img/filetypes/' . $ext . '.png';
                    						}
											$td_content .= '<div><a href="' . $file_url . '" title="' . $file 
														 . '" target="_blank"><img src="' . $preview_image_url . '" /></a></div>';
											$inc++;
											if ($inc > $max_display) {
												$td_content .= '<div>...</div>';
												break;
											}
										}
									}
									$td_content .= '</div>';

								} elseif ($sv->field_type == '10') { // dropdown fields
									$selected = '';
									foreach($sst_all as $k2 => $v2) {
                                        if ($sv->orig_name == $v2->sst_type_desc) {   // matching sst's
                                            if ($v == $v2->sst_id) { // selected
                                                $selected = $v2->sst_name;
                                            }
                                        }
                                    }
									$td_content = '<a class="individual-link" href="' . $page_ind_arg . '&amp;entry=' 
											    . $lead['wcp_lead_id'] . '">' . $selected . '</a>';

								} elseif ($sv->field_type == '9') { // checkbox
									$checked = '';
									$disabled = 'disabled="disabled"';
									$checkbox = ($v == '1') ? $v : '';
									if ($checkbox) {
										$checked = 'checked="checked"';
									}
									$td_content = '<a class="individual-link" href="' . $page_ind_arg . '&amp;entry='
												. $lead['wcp_lead_id'] . '">'
												. '<input type="checkbox" id="' . $k . $lead['wcp_lead_id'] 
												. '" class="checkbox" ' . $checked . ' ' . $disabled . '/>'
												. '<label for="' . $k . $lead['wcp_lead_id'] . '">  </label></a>';


								} elseif ($sv->field_type == '8') { // Star rating
									$floatv = floatval($v);
									$td_content = '<a class="individual-link" href="' . $page_ind_arg . '&amp;entry='
                                                . $lead['wcp_lead_id'] . '">'
												. '<div class="rateit" data-rateit-ispreset="true" data-rateit-value="' 
												. $floatv . '"' . ' data-rateit-readonly="true"></div></a>';

								} elseif ($sv->field_type == '7' || $sv->field_type == '11') { // date time add some extra classes
									$set_time = date_create($v);
									$date_diff = date_diff($now_time,$set_time);
									//echo $now . ' ' . $v . ' ';  // testing
									$hour = intval($date_diff->format('%R%H'));
									$hour_print = ($date_diff->format('%H'));
									$day = intval($date_diff->format('%R%d'));
									$day_print = $date_diff->format('%d');
									$month = intval($date_diff->format('%R%m'));
									$month_print = $date_diff->format('%m');
									$year = intval($date_diff->format('%R%y'));
									$year_print = $date_diff->format('%y');
									$date_class = 'date-range-unset';
									if ( $year > 0 ) {
										$date_class = 'date-in-' . $year_print . 'y';
									} elseif ( $year < 0 ) {
										$date_class = 'date-past-' . $year_print . 'y';
									} elseif ( $month > 0 ) {
										$date_class = 'date-in-' . $month_print . 'm';
									} elseif ( $month < 0 ) {
										$date_class = 'date-past-' . $month_print . 'm';
									} elseif ( $day > 0 ) {
										$date_class = 'date-in-' . $day_print . 'd';
									} elseif ( $day < 0 ) {
										$date_class = 'date-past-' . $day_print . 'd';
									} elseif ( $hour > 0) {
										$date_class = 'date-in-' . $hour_print . 'h';
									} elseif ($hour < 0) {
										$date_class = 'date-past-' . $hour_print . 'h';
									}

									// WP Display format
									$display_date = '';
									if ($sv->field_type == '7') {
										if ($v != '0000-00-00 00:00:00') {
											$display_date = date("$this->date_format $this->time_format", strtotime($v));
										}
									} elseif ($sv->field_type == '11') {
										if ($v != '0000-00-00 00:00:00') {
                                            $display_date = date("$this->date_format", strtotime($v));
                                        }
									}

									
                                    $td_content = '<a class="individual-link ' . $date_class . '" href="' 
												. $page_ind_arg . '&amp;entry='
                                                . $lead['wcp_lead_id'] . '">' . $display_date . '</a>';

								} elseif ($sv->field_type == '6') {  // Google map
									if ($v) {
										$v = trim($v);
										$address_link = preg_replace('/ /', '+', $v);
										$address_link = preg_replace('/\n/', '+', $address_link);
										$display_address = preg_replace('/\n/', '<br />', $v);
										$google_link = 'https://www.google.com/maps/place/';
										if (strlen($v) > 30) {
                							$display_address = substr($display_address, 0, 27) . '...';
            							} else {
											$display_address = $v;
										}

										$td_content = '<a class="type-map" title="' . $v . '" target="_blank" href="' 
													. $google_link . $address_link . '">'  . '<i class="md-location-on"></i> ' 
													. $display_address . '</a>';
									}

								} elseif ($sv->field_type == '5') {  // Website address
									// check for schema
									if ($v) {
										if (preg_match("#https?://#", $v) === 0) {
    										$url = 'http://'.$v;
										} else {
											$url = $v;
										}
										$td_content = '<a class="type-url" target="_blank" href="' . $url . '">' 
											. '<i class="md-gps-fixed"></i> ' . $v . '</a>';
									}
								} elseif ($sv->field_type == '4') { // Email address
									if ($v) {
										$td_content = '<a class="type-email" target="_top" href="mailto:' . $v . '">' 
											. '<i class="md-mail"></i> ' . $v . '</a>';
									}
								} elseif ($sv->field_type == '3') { // Phone number
									if ($v) {
										$td_content = '<a class="tel" tabIndex="-1" href="tel:' . $v . '">' 
											. '<i class="md-call"></i> ' . $v . '</a>';
									}
								} elseif ($sv->field_type == '2') { // Text area
									$td_content = '<a class="individual-link" href="' . $page_ind_arg . '&amp;entry=' 
										        . $lead['wcp_lead_id'] . '">' . $v . '</a>';
								} else { // all other fields
									if ($sv->orig_name == 'creation_date'
										|| $sv->orig_name == 'updated_date'
									) { // Display format for creation and updated fields
                                    	$v = date("$this->date_format $this->time_format", strtotime($v));
									}

									$td_content = '<a class="individual-link" href="' . $page_ind_arg . '&amp;entry=' 
											    . $lead['wcp_lead_id'] . '">' . $v . '</a>';
								}
							}
						}
						if ($td_content == '') {  // if for some reason no match...
							$td_content = '<a class="individual-link" href="' . $page_ind_arg . '&amp;entry=' 
								        . $lead['wcp_lead_id'] . '">' . $v . '</a>';
						}

						$wcp_main .= <<<EOC

									<td class="$col_num $trans_key $trans_val">$td_content</td>
EOC;
                    }
                    $i2++;
                }
				if ($this->can_edit) { // user can edit leads
                	$wcp_main .= <<<EOC

									<td class='edit-td'>
										<span class='wcp-lead lead-id-{$lead['wcp_lead_id']}'>
											<i class='wcp-sm md-create'> </i>
										</span>
                          				<span class='delete-lead'>
											<i class='wcp-red wcp-sm md-remove-circle-outline'></i>
										</span>
										<span class="delete-all-selected">
											<input id="wcp-delete-all-{$lead['wcp_lead_id']}" 
												class="delete-all delete-{$lead['wcp_lead_id']}" type="checkbox" />
											<label for="wcp-delete-all-{$lead['wcp_lead_id']}"> </label>
										</span>
									</td>
EOC;
				/* Custom Role */
				} elseif ($custom_role['access']) {
					// print_r($lead);
					if ( $custom_role['perms']['entries_edit'] == 'all'
						|| $custom_role['perms']['entries_edit'] == 'own'
						|| $custom_role['perms']['entries_delete'] == 'all'
						|| $custom_role['perms']['entries_delete'] == 'own'
					) {
						$wcp_main .= <<<EOC
							        <td class='edit-td'>
EOC;

					
						if ( ($custom_role['perms']['entries_edit'] == 'all')
							|| ($custom_role['perms']['entries_edit'] == 'own' &&
								$this->current_user->user_login == $lead['owned_by_cust'])
						) {
							$wcp_main .= <<<EOC
                                        <span class='wcp-lead lead-id-{$lead['wcp_lead_id']}'>
                                            <i class='wcp-sm md-create'> </i>
                                        </span>
EOC;
						}
						if ( ($custom_role['perms']['entries_delete'] == 'all')
                            || ($custom_role['perms']['entries_delete'] == 'own' &&
                                $this->current_user->user_login == $lead['owned_by_cust'])
                        ) {
							$wcp_main .= <<<EOC
                                        <span class='delete-lead'>
                                            <i class='wcp-red wcp-sm md-remove-circle-outline'></i>
                                        </span>
                                        <span class="delete-all-selected">
                                            <input id="wcp-delete-all-{$lead['wcp_lead_id']}"
                                                class="delete-all delete-{$lead['wcp_lead_id']}" type="checkbox" />
                                            <label for="wcp-delete-all-{$lead['wcp_lead_id']}"> </label>
                                        </span>
EOC;
						}
							$wcp_main .= <<<EOC
                                    </td>
EOC;
					}
				}

				$wcp_main .= <<<EOC

								</tr>

EOC;
            }
            $wcp_main .= <<<EOC
        </table>
EOC;
            $wcp_main .= $paging_div;
            return $wcp_main;
        }

		/*
         * Put together a search of the date pieces from the specified format and convert to datetime format
         */
		public function get_date_pieces($date) {
			$datetime = '';
			if (is_numeric($date)) {  // Only a numeric string, don't try to format
				$datetime =$date;
				return $datetime;
			}
			$parsed_date = date_parse_from_format("$this->date_format $this->time_format", $date);
			if ( $parsed_date['year'] && $parsed_date['year'] != 0 ) {
				$datetime = $parsed_date['year'] . '-';
				if ( $parsed_date['month'] && $parsed_date['month'] != 0) {
					$datetime .= sprintf("%02d", $parsed_date['month']) . '-';
					if ($parsed_date['day'] && $parsed_date['day'] != 0) {
						$datetime .= sprintf("%02d", $parsed_date['day']) . ' ';
						if ($parsed_date['hour'] != '') {
							$datetime .= sprintf("%02d", $parsed_date['hour']) . ':';
							if ($parsed_date['minute'] != '') {
								$datetime .= sprintf("%02d", $parsed_date['minute']) . ':';
								if ($parsed_date['second'] !='') {
									$datetime .= sprintf("%02d", $parsed_date['second']);
								}
							}
						}
					}
				}
			}
			//print_r($datetime);
			return $datetime;
		}
			

    } // end class

