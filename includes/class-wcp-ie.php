<?php
/**
 * WCP Class for Importing and Exporting Leads
 */

    class wcp_ie extends main_wcp {
        // properties

        // methods

        //public function __construct() {
        //    parent::__construct();
        //}

        /**
         * Importing and Exporting tabs
         **/
        public function import_export() {
            global $wpdb;
			$this->load_db_options(); // load the current tables and options

			// no access to this page for non-admins, or custom access without export / import
            $this->get_the_current_user();
			$custom_role = $this->get_custom_role();
            if (!$custom_role['access'] && !$this->can_edit) {
                $content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                return $content;
            } elseif ( $custom_role['access'] ) {
				if ($custom_role['perms']['access_export'] != 'all'
					&& $custom_role['perms']['access_export'] != 'own'
					&& $custom_role['perms']['access_import'] != 'yes'
				) {
					$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
				    return $content;
				}
			}

			// Import
			$content = '<div class="wcp-tabs">'
					 . '<ul class="tab-select">';
			if ($custom_role['access'] && $custom_role['perms']['access_import'] == 'yes') {
					 $content .= '<li><a href="#import-container"><i class="md-call-made"></i><span class="tab-label">' 
					 		   . __('Import Entries', 'shwcp') . '</span></a></li>';
			} elseif (!$custom_role['access']) {
					$content .= '<li><a href="#import-container"><i class="md-call-made"></i><span class="tab-label">'
						      . __('Import Entries', 'shwcp') . '</span></a></li>';
			}
			
			if ($custom_role['access']) {
				if ($custom_role['perms']['access_export'] == 'own'
					|| $custom_role['perms']['access_export'] == 'all'
				) {
					 $content .= '<li><a href="#export-container"><i class="md-call-received"></i><span class="tab-label">' 
					 		   . __('Export Entries', 'shwcp') . '</span></a></li>';
				}
			} elseif (!$custom_role['access']) {
				$content .= '<li><a href="#export-container"><i class="md-call-received"></i><span class="tab-label">'
                          . __('Export Entries', 'shwcp') . '</span></a></li>';
			}

			if (isset($this->first_tab['mailchimp_api']) && $this->first_tab['mailchimp_api'] == 'true') {
					 $content .= '<li><a href="#sync-mc-container"><i class="md-autorenew"></i><span class="tab-label">'
					    . __('Sync To MailChimp', 'shwcp') . '</span></a></li>';
			}

			$content .= '</ul>';


			if ( ($custom_role['access'] && $custom_role['perms']['access_import'] == 'yes') 
				|| !$custom_role['access']
			) {

				$content .= '<div class="import-container" id="import-container">'
					 . '<h3>' . __('Import Entries', 'shwcp') .  '</h3>'
					 . '<ol>'
					 . '<li>' . __('Accepted file types are csv, xls, and xlsx.', 'shwcp') . '</li>'
					 . '<li>' . __('Import 1 file at a time.', 'shwcp') . '</li>'
					 . '<li>' . __('Your Import file will need a header row so you can line up your data with the columns in the next step.', 'shwcp') . '</li>'
					 . '<li>' . __('Importing is going to be directly impacted by your server php upload file limits and memory limits.  Ask your webhost how to increase these if needed.', 'shwcp') . '</li>'
					 . '</ol>'
					 . '<div id="browse_import" href="#">'
					 . __('Drag or click to upload a csv, xls, or xlsx file', 'shwcp')
					 . '</div>'
					 . '<div class="import-file"></div>'
					
					 . '<div class="progress"><div class="progress-container"> &nbsp;</div>'
                     . ' <span class="progress-percent"></span></div>'
                     . ' <span class="complete-text">' . __('Complete', 'shwcp') . '</span>'
					 . '<div class="wcp-button submit-upload">' . __('Upload', 'shwcp') . '</div>'
					 . '<div id="results"></div>'
					 . '</div>'
					 . '<div class="success-message">' 
					 . __('Entries have been imported, you can view and further edit them on the main page', 'shwcp') 
					 . '</div><!-- import-container -->';
			}


			// Export
			if ( ($custom_role['access'] && $custom_role['perms']['access_export'] == 'own')
              	|| ($custom_role['access'] && $custom_role['perms']['access_export'] == 'all')
				|| (!$custom_role['access'])
            ) { 
				$filter_fields = $wpdb->get_results ("SELECT * from $this->table_sort order by sort_ind_number asc"); 
				$content .= '<div class="export-container" id="export-container">'
					  . '<h3>' . __('Export Entries', 'shwcp') . '</h3>'
					  . '<p>' . __('Export All Rows, ID Range, or Filter Export', 'shwcp') . '</p>'
					  . '<form action="' . admin_url() . 'admin-post.php" class="export-form" method="post">'
					  . '<p><input type="checkbox" id="allrows" class="allrows" name="allrows" /><label for="allrows">' 
					  . __('All Rows', 'shwcp') . '</label> &nbsp;&nbsp;&nbsp; <input type="checkbox" id="rowrange"'
					  . ' class="rowrange" name="rowrange" /><label for="rowrange">' . __('ID Range', 'shwcp') . '</label>'
					  . ' &nbsp;&nbsp;&nbsp; <input type="checkbox" id="rowfilter" class="rowfilter" name="rowfilter" />'
					  . ' <label for="rowfilter">' . __('Filter Export', 'shwcp') 
					  . '</label></p>'
					  . '<div class="row range-select"><div class="col-md-4">'
					  . '<div class="input-field"><label for="fromrow">' . __('From ID', 'shwcp') . '</label>'
					  . '<input class="fromrow" id="fromrow" name="fromrow" value="1" type="text" />'
					  . '</div></div>'
						
					  . '<div class="col-md-4">'
                      . '<div class="input-field"><label for="torow">' . __('To ID', 'shwcp') . '</label>'
                      . '<input class="torow" id="torow" name="torow" value="1" type="text" />'
                      . '</div></div></div>'

					  . '<div class="row add-filter-row">'
					  . '<div class="col-md-12"><i class="add-filter wcp-md md-add" title="' 
                      . __('Add Filter', 'shwcp') . '"></i></div>'
					  . '</div>'
					   . '<div class="row filter-select filter-entry"><div class="col-md-4">'
					  . '<div class="input-field"><label for="filter-sel">' . __('Select Field', 'shwcp') . '</label>'
					  . '&nbsp;&nbsp;&nbsp;<select class="filter-sel input-select" name="filter-sel[]">';
				foreach ($filter_fields as $k => $v) {
					$content .= '<option value="' . $v->orig_name . '">' . $v->translated_name . '</option>';
				}
				$content .= '</select>'
					  . '</div></div>'
					  . '<div class="col-md-4">'
					  . '<div class="input-field"><label for="filter-val">' . __('Filter', 'shwcp') . '</label>'
					  . '<input class="filter-val" name="filter-val[]" type="text" />'
					  . '</div></div>'
					  .'</div><!-- End Row -->'
					  . '<div class="remove-filter-text" style="display:none;">' . __('Remove', 'shwcp') . '</div>'

					  . '<hr>'
					  . '<p>' . __('Select the Columns to export', 'shwcp') . '</p>'
					  . '<p><input type="checkbox" id="checkall" class="checkall" />' 
					  . '<label for="checkall">' . __('Check / Uncheck All', 'shwcp') . '</label></p><hr>';


				$export_fields = $wpdb->get_results("SELECT * from $this->table_sort order by sort_ind_number asc");

				$content .= '<div class="row"><div class="col-md-4 col-sm-6">';
				$fields_total = count($export_fields) + 2;
				$cut = ceil($fields_total / 3);
				$i = 1;
				foreach ($export_fields as $k => $v) {
					if ($v->field_type != 99 
						&& $v->orig_name != 'lead_files'
					) {
						$content .= '<p><input type="checkbox" id="' . $v->orig_name 
						 . '" class="export-field" name="fields[' . $v->orig_name . ']" />' 
						 . '<label for="' . $v->orig_name . '">' . stripslashes($v->translated_name) . '</label></p>';
						$i++;
					}

					if ($i == $cut) {
						$content .= '</div><div class="col-md-4 col-sm-6">' . "\n";
						$i = 1;
					}
				}
				global $post;
				$postID = $post->ID;

				$content .= '<p><input type="checkbox" id="photo-links" class="export-field" name="fields[photo-links]" />' 
					 . '<label for="photo-links">' . __('Links to Photos', 'shwcp') . '</label></p>'
					 . '<p><input type="checkbox" id="file-links" class="export-field" name="fields[file-links]" />' 
					 . '<label for="file-links">' . __('Links to Files', 'shwcp') . '</label></p>'
					 . '</div></div>'
					 . '<div class="row"><hr /><div class="col-md-12">'
					 . '<div class="input-field"><label for="export-type">' . __('Choose a format:', 'shwcp') 
					 . '</label><select id="export-type" class="input-select" name="output-type">'
					 . '<option value="csv">' . __('CSV', 'shwcp') . '</option>'
					 . '<option value="excel">' . __('Excel', 'shwcp') . '</option>'
					 . '</select></div>'
				     . '<input type="hidden" name="action" value="wcpexport">'
					 . '<input type="hidden" name="postID" value="' . $postID . '">';

				$content .= wp_nonce_field( 'wcpexport', 'export-nonce', false, false );

				$content .= '<p><span class="wcp-button submit-export">' . __('Submit', 'shwcp') . '</span></p>'
					 . '</div></div></form>'
					 . '</div><!-- export-container -->';
			}


			// MailChimp syncronize
			if (isset($this->first_tab['mailchimp_api']) && $this->first_tab['mailchimp_api'] == 'true') {
				$mc_title = __("Export your entries to a MailChimp List", 'shwcp');
				$mc_description = __("As long as your server allows external connections to Mail Chimp, you can add your API Key and choose the list to export entries to, otherwise you can export to csv and upload to MailChimp.  You can retrieve your MailChimp API Key by logging into MailChimp.com and going to Your Account -> Extras -> API Keys.", 'shwcp');
				$mc_api_text = __('MailChimp API Key', 'shwcp');
				$mc_submit = __('Submit', 'shwcp');
            	$content .= <<<EOC
                <div class="sync-mc-container" id="sync-mc-container">
                    <h3>$mc_title</h3>
                    <p>$mc_description</p>
					<hr />
					<div class="sync-mc-div">
						<div class="input-field">
							<label for="mc-api-key">$mc_api_text</label>
							<input class="mc-api-key" type="text" />
						</div>
					</div>
					<span class="wcp-button submit-mc-data">$mc_submit</span>
					
				</div><!--sync-mc-container-->
EOC;
			} // end mailchimp section

	

			$content .= '</div>';


			return $content;
        }

	}
