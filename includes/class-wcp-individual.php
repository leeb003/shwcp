<?php
/**
 * WCP Class for displaying the individual contact view
 */

class wcp_individual extends main_wcp {
	// properties

	// methods

	//public function __construct() {
	//    parent::__construct();
	//}

	/*
	 * Individual Lead page
	 */
	public function get_individual($db) {
		global $wpdb;
		$this->load_db_options(); // load the current tables and options
		$lead_id = intval($_GET['entry']);
		$lead_vals_pre = $wpdb->get_row (
			"
				SELECT l.*
				FROM $this->table_main l
				WHERE l.id = $lead_id;
			"
		);

		// no access to other leads for users that manage their own
		$this->get_the_current_user();
		$custom_role = $this->get_custom_role();
		// standard role access
		if ( !$custom_role['access'] 
			&& $this->current_access == 'ownleads' 
			&& $lead_vals_pre->owned_by != $this->current_user->user_login
		) {
				$content = '<span class="no-access">' . __('You do not have access to this entry', 'shwcp') . '</span>';
				return $content;

		// custom role access
		} elseif ($custom_role['access'] 
			&& $custom_role['perms']['entries_view'] == 'own' 
			&& $lead_vals_pre->owned_by != $this->current_user->user_login
		) {
				$content = '<span class="no-access">' . __('You do not have access to this entry', 'shwcp') . '</span>';
                return $content;
		}

		/* layout */
		// columns before check on active content
        $current_columns = isset($this->frontend_settings['ind_columns']) ? $this->frontend_settings['ind_columns'] : 4;
        $right_columns   = 12 - $current_columns;
		$left_side       = "col-md-" . $current_columns;
		$right_side      = "col-md-" . $right_columns;

		// Enabled sections array
		$sections_enabled = array(
			'files_tile'   => $this->first_tab['contact_upload'],
			'photo_tile'   => $this->first_tab['contact_image'],
			// have to check these since they may not exist
			'fields_tile'  => isset($this->frontend_settings['fields_enabled']) 
							? $this->frontend_settings['fields_enabled'] : 'true',
			'details_tile' => isset($this->frontend_settings['details_enabled']) 
							? $this->frontend_settings['details_enabled'] : 'true',
			'notes_tile'   => isset($this->frontend_settings['notes_enabled'])   
							? $this->frontend_settings['notes_enabled'] : 'true'
		);
		

		// Section Layout
		$individual_layout = isset($this->frontend_settings['individual_layout']) 
						   ? $this->frontend_settings['individual_layout'] : array();
		// Default layout
        if (empty($individual_layout)) {
            $individual_layout = array(
                1 => array(
                    'tile' => 'photo_tile',
                    'pos'  => 'left_side',
                ),
                2 => array(
                    'tile' => 'files_tile',
                    'pos'  => 'left_side',
                ),
                3 => array(
                    'tile' => 'fields_tile',
                    'pos'  => 'right_side',
                ),
                4 => array(
                    'tile' => 'notes_tile',
                    'pos'  => 'right_side',
                ),
                5 => array(
                    'tile' => 'details_tile',
                    'pos'  => 'bottom_row'
                )
            );
        }

		// check for any empty sections
		$left_side_enabled  = false;
        $right_side_enabled = false;
        $bottom_row_enabled = false;

		foreach ($individual_layout as $k => $v) {
			$section = $v['tile'];
			if ($v['pos'] == 'left_side') {
				if ($sections_enabled[$section] == 'true') {
					$left_side_enabled = true;
				}
			}
			if ($v['pos'] == 'right_side') {
				if ($sections_enabled[$section] == 'true') {
					$right_side_enabled = true;
				}
			}
			if ($v['pos'] == 'bottom_row') {
				if ($sections_enabled[$section] == 'true') {
					$bottom_row_enabled = true;
				}
			}
		}

		if (!$left_side_enabled
			&& $right_side_enabled
		) {	
			$right_side = "col-md-12";
		}
		if (!$right_side_enabled
			&& $left_side_enabled
		) {
			$left_side = "col-md-12";
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
			if (in_array($k, $this->field_noedit) ) {  //non editables or no edit access
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
				if ($v2->orig_name == 'lead_files') {
					// skip since we already have them on individual

				} elseif ($v2->orig_name == $k) {
					$non_edit_trans[$k]['value'] = $v;
					$non_edit_trans[$k]['trans'] = $v2->translated_name;
					$non_edit_trans[$k]['orig_name'] =  $v2->orig_name;
					$non_edit_trans[$k]['field_type'] = $v2->field_type;
				}
			}
		}

		$remove_image_text = __('Remove', 'shwcp');
		if ($lead_vals['small_image'] == '') {
			// If Default Entry has been set in settings, load it
			if (isset($this->first_tab['contact_image_url']) && $this->first_tab['contact_image_url'] != '') {
				$small_image = $this->first_tab['contact_image_url'];
			// Initial Site default image 
			} else {
				$small_image = SHWCP_ROOT_URL . '/assets/img/default_entry.png';
			}
		} else {
			$small_image = $this->shwcp_upload_url . $db . '/' . $lead_vals['small_image'];
		}
		// Upload Small Image

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


		/* Create sections for areas below */

		
		// Upload Small Image
		if ($this->first_tab['contact_image'] == 'true') {  // if contact images are set to display
			$lead_image_text = __('Entry Image', 'shwcp');
			$photo_section = <<<EOC

								<div class="lead-image-container col-md-12 leadID-$lead_id">
									<div class="image-holder">
										<img class="current-image img-responsive" src="$small_image" alt="$lead_image_text" />
									</div>

EOC;

			if ( ( !$custom_role['access'] && $this->can_edit )
                || ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'all' 
					&& $custom_role['perms']['manage_entry_photo'] == 'yes'
				)
                || ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'own'
                    && $lead_vals_pre->owned_by == $this->current_user->user_login 
					&& $custom_role['perms']['manage_entry_photo'] == 'yes'
				)
            ) {
				$button_text = __('Drag or click to upload a jpg, png or gif image file', 'shwcp');
				$set_image_text = __('Set Image', 'shwcp');
				$complete_text = __('Complete', 'shwcp');
				$photo_section .= <<<EOC

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
			$photo_section .= <<<EOC

								</div>

EOC;
		}

		if ($this->first_tab['contact_upload'] == 'true') {  // Lead file uploads enabled
			$files_section = <<<EOC
									<div class="row"> 
										<div class="col-md-12">
											<div class="lead-files-container leadID-$lead_id">
												<div class="existing-files"><h6 class="files-message">$files_message</h6>

EOC;

			if (!empty($lead_files)) {
				foreach ($lead_files as $file => $v) {
					$lead_file_url = $this->shwcp_upload_url . $db . '/' . $lead_id . '-files/' . $v['name'];
					$file_link = '<a class="leadfile-link" target="_blank" href="' . $lead_file_url . '">' . $v['name'] . '</a>';
					$last_modified_text = __('Last Modified', 'shwcp');
					$files_section .= <<<EOC
													<div class="lead-info">
														<span class="leadfile-name" title="{$v['name']} $last_modified_text {$v['date']}">
															$file_link
														</span>
														<span class="leadfile-size">{$v['size']}

EOC;

					if ( ( !$custom_role['access'] && $this->can_edit )
                		|| ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'all'
                    		&& $custom_role['perms']['manage_entry_files'] == 'yes'
              			)
                		|| ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'own'
                    		&& $lead_vals_pre->owned_by == $this->current_user->user_login
                    		&& $custom_role['perms']['manage_entry_files'] == 'yes'
                		)
            		) {
						$files_section .= <<<EOC
															<i class="wcp-red wcp-md md-remove-circle-outline remove-existing-file"> </i>

EOC;
					}
					$files_section .= <<<EOC

														</span>
													</div>

EOC;
				}
			}
			$queued_text = __('Queued for upload', 'shwcp');
			$add_files_text = __('Add Files', 'shwcp');
			$files_section .= <<<EOC

												</div>
												<div class="files-queued">
													<h6>$queued_text</h6>
												</div>
												<div class="wcp-button submit-lead-files">$add_files_text</div>

EOC;

			if ( ( !$custom_role['access'] && $this->can_edit )
                || ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'all' 
                    && $custom_role['perms']['manage_entry_files'] == 'yes'
                )
                || ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'own'
                    && $lead_vals_pre->owned_by == $this->current_user->user_login 
                    && $custom_role['perms']['manage_entry_files'] == 'yes'
                )
            ) {
				// Progress
				$button_text = __('Drag or click to upload a file or multiple files', 'shwcp');
				$files_section .= <<<EOC
												<div class="progress">
													<div class="progress-container2"> &nbsp;</div>
													<span class="progress-percent2"></span>
												</div>
												<div id="upload_files">$button_text</div>

EOC;
			}
			$files_section .= <<<EOC
											</div>
										</div>
									</div>
EOC;
			// file messages
			$files_section .= <<<EOC

									<div class="files-msg">$files_msg</div>
									<div class="no-files-msg">$no_files_msg</div>
									<div class="clear-both"></div>

EOC;

		} // end Lead file uploads enabled

		
		// Read only, non edit display
		if ( ( !$custom_role['access'] && $this->current_access == 'readonly')
			|| (!$custom_role['access'] && $this->can_access == 'true' && !is_user_logged_in() )
			|| ($custom_role['access'] && $custom_role['perms']['entries_edit'] == 'none')
			|| ($custom_role['access'] 
				&& $custom_role['perms']['entries_edit'] == 'own' 
				&& $lead_vals_pre->owned_by != $this->current_user->user_login )
        ) {

		//if ( $this->current_access == 'readonly' || ( $this->can_access == 'true' && !is_user_logged_in() ) ) {
			$fields_section = <<<EOC
									<div class="wcp-edit-lead leadID-$lead_id">
										<div class="row">
											<div class="col-md-12">

EOC;
			foreach ( $translated as $k => $v) {
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
							$fields_section .= <<<EOC
												<div class="col-md-6 $k-col">
													<div class="non-edit-holder">
														<span class="non-edit-label">$clean_trans</span>
														<span class="non-edit-value $k">$display_date</span>
													</div>
												</div>

EOC;
						} elseif ($sv->field_type == '11') { // Date Only
							$display_date = '';
                            if ($v['value'] != '0000-00-00 00:00:00') {
                                $display_date = date("$this->date_format", strtotime($v['value']));
                            }
                            $fields_section .= <<<EOC
                                                <div class="col-md-6 $k-col">
                                                    <div class="non-edit-holder">
                                                        <span class="non-edit-label">$clean_trans</span>
                                                        <span class="non-edit-value $k">$display_date</span>
                                                    </div>
                                                </div>
EOC;
						} elseif ($sv->field_type == '8') { // Star Rating
							$rating = floatval($v['value']);
							$fields_section .= <<<EOC
												<div class="col-md-6 $k-col">
													<div class="non-edit-holder">
														<span class="non-edit-label">$clean_trans</span><br />
														<div class="rateit bigstars" data-rateit-ispreset="true" data-rateit-value="$rating" data-rateit-starwidth="32" data-rateit-starheight="32" data-rateit-readonly="true">
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
							$fields_section .= <<<EOC
												<div class="col-md-6 $k-col">
													<div class="input-field">
														<label for="$k">$clean_trans</label>
														<input class="checkbox $k" id="$k" type="checkbox" $checked $disabled />
														<label for="$k"> </label>
													</div>
												</div>

EOC;
						} elseif ($sv->field_type == '10') { // Dropdown
							$entry = '';
							foreach($sst as $k2 => $v2) {
								if ($k == $v2->sst_type_desc) {   // matching sst's
									if ($v['value'] == $v2->sst_id) { // selected
										$entry = $v2->sst_name;
									}
								}
							}
							$fields_section .= <<<EOC
												<div class="col-md-6 $k-col">
													<span class="non-edit-label">$sv->translated_name</span>
													<span class="non-edit-value $sv->orig_name">$entry</span>
												</div>
EOC;
						} elseif ($sv->field_type == '99') { // Group Title
							$fields_section .= <<<EOC
												<div class="col-md-12">
													<div class="input-field fields-grouptitle">
														<h3 for="$k">$clean_trans</h3>
														<input class="lead_field $k" value="" type="hidden" />
													</div>
												</div>
EOC;

						} else {  // All the others
							$fields_section .= <<<EOC
												<div class="col-md-6 $k-col">
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
			$fields_section .= <<<EOC
									</div>
								</div>
							</div><!-- End Entry Fields Section -->
EOC;


		// End read only display
		} elseif ( ( !$custom_role['access'] && $this->can_edit ) 
        	|| ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'all' )
			|| ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'own' 
				&& $lead_vals_pre->owned_by == $this->current_user->user_login )
        ) {
			$fields_section = <<<EOC
									<div class="wcp-edit-lead leadID-$lead_id">
										<div class="row">
											<div class="col-md-12">

EOC;
			$i = 0;
			$last = count($translated);
			foreach ( $translated as $k => $v) {
				if ('owned_by' == $k) {
					// Manage Own can or can't change ownership
					$can_change_ownership = isset($this->second_tab['own_leads_change_owner'])
						? $this->second_tab['own_leads_change_owner'] : 'no';

					// disabled for changing ownership or custom role that cannot change
					if ( ( !$custom_role['access'] && $this->current_access == 'ownleads' && $can_change_ownership == 'no')
                		|| ( $custom_role['access'] && $custom_role['perms']['entries_ownership'] == 'no' )
            		) {
						$fields_section .= <<<EOC

                                                <div class="col-md-6 $k-col">
                                                    <div class="input-field"><label for="$k">{$v['trans']}</label>
                                                        <select class="lead_select $k input-select" disabled="disabled">

EOC;
						$wcp_users = $this->get_all_wcp_users();
                        foreach($wcp_users as $k2 => $v2) {
                            $selected = '';
                            if ($v2->data->user_login == $v['value']) {
                                $selected = 'selected="selected"';
                            }
                            $data_user_login = $v2->data->user_login;
                            $fields_section .= <<<EOC
                                                            <option value="$data_user_login" $selected>$v2->user_login</option>

EOC;
						}
						$fields_section .= <<<EOC
                                                        </select>
                                                    </div>
                                                </div>

EOC;
					} else {
						$fields_section .= <<<EOC

												<div class="col-md-6 $k-col">
													<div class="input-field"><label for="$k">{$v['trans']}</label>
														<select class="lead_select $k input-select">

EOC;
						$wcp_users = $this->get_all_wcp_users();
						foreach($wcp_users as $k2 => $v2) {
							$selected = '';
							if ($v2->data->user_login == $v['value']) {
								$selected = 'selected="selected"';
							}
							$data_user_login = $v2->data->user_login;
							$fields_section .= <<<EOC
															<option value="$data_user_login" $selected>$v2->user_login</option>

EOC;
						}
						$fields_section .= <<<EOC
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
												<div class="col-md-6 $k-col">
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
												<div class="col-md-6 $k-col">
													<div class="input-field">
														<label for="$k">$clean_trans</label>
														<input class="lead_field $k date-choice" value="$display_date"
															type="text" />
													</div>
												</div>
EOC;
							} elseif($sv->field_type == '11') { // Date Only field
								$display_date = '';
                                if ($v['value'] != '0000-00-00 00:00:00') {
                                    $display_date = date("$this->date_format", strtotime($v['value']));
                                }
                                $field_type = <<<EOC
                                                <div class="col-md-6 $k-col">
                                                    <div class="input-field">
                                                        <label for="$k">$clean_trans</label>
                                                        <input class="lead_field $k date-only-choice" value="$display_date"
                                                            type="text" />
                                                    </div>
                                                </div>
EOC;
							
							} elseif($sv->field_type == '8') { // Star Rating field
								$rateval = floatval($v['value']);
								$field_type = <<<EOC
												<div class="col-md-6 $k-col">
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
												<div class="col-md-6 $k-col">
													<div class="input-field">
														<label for="$k">$clean_trans</label>
														<input class="checkbox $k" id="$k" type="checkbox" $checked />
														<label for="$k"> </label>
													</div>
												</div>

EOC;
							} elseif ($sv->field_type == '10') { // Dropdown
								$field_type = <<<EOC
												<div class="col-md-6 $k-col">
													<div class="input-field">
														<label for="$k">$clean_trans</label>
														<select id="$k" class="lead_select $k input-select">
EOC;
								foreach($sst as $k2 => $v2) {
									$v2->sst_name = stripslashes($v2->sst_name);
									$selected = '';
									if ($k == $v2->sst_type_desc) {   // matching sst's
										if ($v['value'] == $v2->sst_id) { // selected
											$selected = 'selected="selected"';
										}
										$field_type .= <<<EOC
															<option value="$v2->sst_id" $selected>$v2->sst_name</option>
EOC;
									}
								}
								$field_type .= <<<EOC
														</select>
													</div>
												</div>
EOC;
							
                            } elseif ($sv->field_type == '777') { // Multiselect
                                $field_type = <<<EOC
                                                <div class="col-md-6 $k-col">
                                                    <div class="input-field">
                                                        <label for="$k">$clean_trans</label>
                                                        <select multiple id="$k" class="lead_select $k multi-select-field">
EOC;
                            	foreach($sst as $k2 => $v2) {
                                    $v2->sst_name = stripslashes($v2->sst_name);
                                    $selected = '';
                                    if ($k == $v2->sst_type_desc) {   // matching sst's
										if ($v['value'] == NULL) {$v['value'] = '[""]';}  // empty sets
                                        if ( in_array($v2->sst_id, json_decode($v['value']))) { // selected
                                            $selected = 'selected="selected"';
                                        }
                                        $field_type .= <<<EOC
                                                            <option value="$v2->sst_id" $selected>$v2->sst_name</option>
EOC;
                                    }
                                }
                                $field_type .= <<<EOC
                                                        </select>
                                                    </div>
                                                </div>
EOC;

							} elseif ($sv->field_type == '99') { // Group Title
								$field_type = <<<EOC
												<div class="col-md-12 $k-col">
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
												<div class="col-md-6 $k-col">
													<div class="input-field">
														<label for="$k">$clean_trans</label>
														<input class="lead_field $k" value="{$v['value']}" type="text" />
													</div>
												</div>
EOC;
					}
					$fields_section .= $field_type;
				}
				$i++;
			}

			$save_field_text = __('Save Fields', 'shwcp');
			$save_entry_fields = <<<EOC
												<div class="col-md-12">
                                                    <div class="save-lead-fields leadID-$lead_id wcp-button left-check">$save_field_text</div>
                                                </div>
EOC;

			$fields_section .= <<<EOC
												$save_entry_fields
											</div>
										</div>
                                    </div><!-- End Fields Row -->
EOC;
		} 


		// Notes
		$existing_notes = $wpdb->get_results (
			" 
				SELECT notes.*, user.user_login
				from $this->table_notes notes, {$wpdb->base_prefix}users user
				WHERE lead_id=$lead_id and notes.creator=user.ID
				order by date_added desc
			"
		);
		$notes_text = __('Notes', 'shwcp');
		$notes_section = <<<EOC
									<div class="row">
										<div class="col-md-12">
											<div class="note-title"><h4>$notes_text</h4></div>
											<div class="lead-notes-container leadID-$lead_id">

EOC;

		foreach ($existing_notes as $k => $note) {
			$notes_section .= <<<EOC
												<div class="lead-note leadID-$lead_id noteID-$note->id">

EOC;

			if ( ( !$custom_role['access'] && $this->can_edit )
            	|| ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'all' )
            	|| ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'own'
                	&& $lead_vals_pre->owned_by == $this->current_user->user_login )
        	) {


				$notes_section .= <<<EOC
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
						SELECT user_login from {$wpdb->base_prefix}users user WHERE user.ID=$note->updater
					"
				);
			}
			if ($note_updated) {
				$updated_text = '&nbsp;&nbsp; <span class="wcp-dark">|</span> &nbsp;&nbsp;' . __('Last Updated by', 'shwcp');
				$updated_entry = <<<EOC
									$updated_text $updater
EOC;
			}
			$note_content = stripslashes($note->note_content);
			$date_formatted = date("$this->date_format $this->time_format", strtotime($note->date_added));
			$user_note_info = '&nbsp;&nbsp;<i class="wcp-dark md-person-outline"></i> ' . $note->user_login;
			$notes_section .= <<<EOC
													<span class="timeline-header"> <i class="wcp-dark md-history"></i> $date_formatted $user_note_info $updated_entry</span>
													<span class="timeline-body">$note_content</span>

EOC;

			$notes_section .= <<<EOC

												</div>

EOC;
		}

		if (empty($existing_notes)) {
			$no_results = __('No Results Found', 'shwcp');
			$notes_section .= <<<EOC

												<div class="lead-note no-note">
													$no_results
												</div>
EOC;
		}

		$notes_section .= <<<EOC

											</div><!-- End lead-notes-container -->

EOC;

		if ( ( !$custom_role['access'] && $this->can_edit )
            || ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'all' )
            || ( $custom_role['access'] && $custom_role['perms']['entries_edit'] == 'own'
                && $lead_vals_pre->owned_by == $this->current_user->user_login )
        ) {
			$add_note_text = __('Add A Note', 'shwcp');
			$notes_section .= <<<EOC

											<div class="add-note wcp-button">$add_note_text</div>
EOC;
		}
		$notes_section .= <<<EOC
										</div>
									</div><!-- End Notes -->
EOC;

		//print_r($non_edit_trans);
		//print_r($non_editable);

        /* non edits display */
		$details_section = '<div class="row"><div class="col-md-12"> <div class="entry-details">';
        // id 
        $details_section .= '<div class="col-md-4 text-center ' . $non_edit_trans['id']['orig_name'] . '"><span class="non-edits">'
                          . $non_edit_trans['id']['trans'] . '</span> ' . $non_edit_trans['id']['value'] . '</div>';
        // created_by
        // creation_date
        $creation_date = '';
        if ($non_edit_trans['creation_date']['value'] != '0000-00-00 00:00:00') {
            $creation_date = date("$this->date_format $this->time_format", strtotime($non_edit_trans['creation_date']['value']));
        }

        $details_section .= '<div class="col-md-4 text-center ' . $non_edit_trans['created_by']['orig_name'] 
					      . '"><span class="non-edits">'
                          . $non_edit_trans['created_by']['trans'] . '</span> ' . $non_edit_trans['created_by']['value'] 
                          . ' <span class="detail-date">' . $creation_date . '</span></div>';

        // updated_by
        // updated_date
        $updated_date = '';
        if ($non_edit_trans['updated_date']['value'] != '0000-00-00 00:00:00') {
            $updated_date = date("$this->date_format $this->time_format", strtotime($non_edit_trans['updated_date']['value']));
        }
        $details_section .= '<div class="col-md-4 text-center">'
						  . '<span class="non-edits">' . $non_edit_trans['updated_by']['trans'] . '</span>'
						  . ' <span class="updated_by">' 
						  . $non_edit_trans['updated_by']['value'] . '</span>'
                          . ' <span class="updated_date">' . $updated_date . '</span></div>'
						  . '</div></div></div><!--End Entry Details-->';


		/* Layout areas */
		$entry_content = '';

		// left side
		if ($left_side_enabled) {
        	$entry_content .= <<<EOC
                            <div class="row single-container">
                                <div class="$left_side left-side">
EOC;
			foreach ($individual_layout as $k => $v) {
				if ($v['pos'] == 'left_side') {
					if ($sections_enabled[$v['tile']] != 'false' ) {
						if ($v['tile'] == 'photo_tile') { $entry_content .= $photo_section; 
						} elseif ($v['tile'] == 'files_tile') { $entry_content .= $files_section;
						} elseif ($v['tile'] == 'fields_tile') { $entry_content .= $fields_section;
						} elseif ($v['tile'] == 'notes_tile') { $entry_content .= $notes_section;
						} elseif ($v['tile'] == 'details_tile') { $entry_content .= $details_section;}
					}
				}
			}

        // end left side
        $entry_content .= <<<EOC
                                </div><!-- End column -->
EOC;
		}
		if ($right_side_enabled) {
        	// right side
        	$entry_content .= <<<EOC

                                <div class="$right_side right-side">

EOC;


            foreach ($individual_layout as $k => $v) {
                if ($v['pos'] == 'right_side') {
                    if ($sections_enabled[$v['tile']] != 'false' ) {
                        if ($v['tile'] == 'photo_tile') { $entry_content .= $photo_section; 
                        } elseif ($v['tile'] == 'files_tile') { $entry_content .= $files_section;
                        } elseif ($v['tile'] == 'fields_tile') { $entry_content .= $fields_section;
                        } elseif ($v['tile'] == 'notes_tile') { $entry_content .= $notes_section;
                        } elseif ($v['tile'] == 'details_tile') { $entry_content .= $details_section;}
                    }
                }
            }

        	// end right side
        	$entry_content .= <<<EOC

                            </div><!-- End lead-container -->
EOC;
		}
		if ($bottom_row_enabled) {
        	// bottom row
       	 	$entry_content .= <<<EOC

							<div class="col-md-12">
EOC;
	

            foreach ($individual_layout as $k => $v) {
                if ($v['pos'] == 'bottom_row') {
                    if ($sections_enabled[$v['tile']] != 'false' ) {
                        if ($v['tile'] == 'photo_tile') { $entry_content .= $photo_section; 
                        } elseif ($v['tile'] == 'files_tile') { $entry_content .= $files_section;
                        } elseif ($v['tile'] == 'fields_tile') { $entry_content .= $fields_section;
                        } elseif ($v['tile'] == 'notes_tile') { $entry_content .= $notes_section;
                        } elseif ($v['tile'] == 'details_tile') { $entry_content .= $details_section;}
                    }
                }
            }       
 
	        $entry_content .= <<<EOC
                            </div><!-- End Bottom Section -->
EOC;
		}

		$entry_content .= <<<EOC
						</div><!-- End Single Container -->
EOC;

		return $entry_content;
	}

} // end class
