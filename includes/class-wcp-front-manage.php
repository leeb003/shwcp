<?php
/**
 * WCP Class for the Front Page Management
 */

class wcp_front_manage extends main_wcp {
	// properties

	// methods

	//public function __construct() {
	//    parent::__construct();
	//}

	/*
	 * Front table sorting fields
	 */
	public function get_front_sorting() {
		global $wpdb;
		$this->load_db_options(); // load the current tables and options

		// no access to this page for non-admins or custom role with no manage front permissions
		$this->get_the_current_user();
		$custom_role = $this->get_custom_role();
		if ($this->current_access != 'full'
			&& !$custom_role['access'] 
		) {
			$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
			return $content;
		} elseif ($custom_role['access'] 
			&& $custom_role['perms']['manage_front'] != 'yes'
		) {
			$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
			return $content;
		}


		$lead_columns = $wpdb->get_results(
			"
				SELECT * from $this->table_sort order by sort_number
			"
		);
		$files_enabled = $this->first_tab['contact_upload'] == 'true';  // used to determine if we are including files or not
		$items1_title = __('Fields to display on main view (in order)', 'shwcp');
		$items2_title = __('Fields not to display on main view', 'shwcp');
		$lead_files_exist = false;

		$wcp_sorting = <<<EOC
		<div class="wcp-title">$items1_title</div>
		<ul class="keepers front-sorting">
EOC;
		foreach ($lead_columns as $k => $v) {
			if ( $files_enabled != 'true' && $v->orig_name == 'lead_files') {
				// skip it
			} elseif (99 == $v->field_type) {
				// skip Group Titles
			} elseif (1 == $v->sort_active) {
				if ($v->orig_name == 'lead_files') { 
					$lead_files_exist = true;
				}
				$wcp_sorting .= '<li class="keeper ' . $v->orig_name . '">' . stripslashes($v->translated_name) . '</li>';
			}
		}

		$wcp_sorting .= <<<EOC
		</ul>
		<div class="wcp-title">$items2_title</div>
		<ul class="nonkeepers front-sorting">
EOC;
		// Fields that don't display on the front end
		foreach ($lead_columns as $k => $v) {
			if ($v->orig_name == 'lead_files' && 1 != $v->sort_active) { // Contact files column
				$lead_files_exist = true;
				if ( $files_enabled == 'true' && $v->orig_name=='lead_files') {
					$wcp_sorting .= '<li class="keeper ' . 'lead_files' . '">' . __('Files', 'shwcp') . '</li>';
				}
			} elseif (99 == $v->field_type) {
				// skip Group titles
			} elseif (1 != $v->sort_active) {
				$wcp_sorting .= '<li class="keeper ' . $v->orig_name . '">' . stripslashes($v->translated_name) . '</li>';
			}
		}

		// Contact files column - add option if file upload is enabled and not already added
		if ($this->first_tab['contact_upload'] == 'true' && !$lead_files_exist) {  // Lead file uploads enabled
			$wcp_sorting .= '<li class="keeper ' . 'lead_files' . '">' . __('Files', 'shwcp') . '</li>';
		}
		$wcp_sorting .= <<<EOC
		</ul>
		<hr />
EOC;

		// Front Page Filtering choices
		$filters_title = __('Top Filters shown on front page (in order)', 'shwcp');
		$filters_title2 = __('Top Filters disabled on front page', 'shwcp');
		$filters_note = __('Note: You can add more filters by creating dropdown field types.  They will then be available here for selection.', 'shwcp');
		$lead_columns = $wpdb->get_results(
            "
                SELECT * from $this->table_sort 
				WHERE field_type='10'
				order by front_filter_sort
            "
        );
		$wcp_sorting .= <<<EOC
		<div class="wcp-title">$filters_title</div>
		<p>$filters_note</p>
        <ul class="filter-keepers front-filters">
EOC;
		foreach ($lead_columns as $k => $v) {
            if (1 == $v->front_filter_active) {
                $wcp_sorting .= '<li class="keeper ' . $v->orig_name . '">' . stripslashes($v->translated_name) . '</li>';
            }
        }

        $wcp_sorting .= <<<EOC
        </ul>
        <div class="wcp-title">$filters_title2</div>
        <ul class="filter-nonkeepers front-filters">
EOC;
        // Fields that don't display on the front end
        foreach ($lead_columns as $k => $v) {
            if (1 != $v->front_filter_active) {
                $wcp_sorting .= '<li class="keeper ' . $v->orig_name . '">' . stripslashes($v->translated_name) . '</li>';
            }
        }

		$wcp_sorting .= <<<EOC
        </ul>
EOC;

		if ($this->first_tab['contact_image'] =='true') {

			$regen_title = __('Regenerate Thumbnails', 'shwcp');
			$regen_desc = __('If you have changed the size of thumbnails in the backend settings, you can regenerate all previous ones here.', 'shwcp');
			$wait_text = __('Please wait...', 'shwpc');
			$regen_button = __('Regenerate Now', 'shwcp');
			$all_done = __('All Done!', 'shwcp');
			$waiting = SHWCP_ROOT_URL . '/assets/img/wait.gif';
			$wcp_sorting .= <<<EOC
		<hr />
		<div class="wcp-thumb-regen">
			<div class="wcp-title">$regen_title</div>
			<div class="wcp-button wcp-changetext regen-thumbnails">$regen_button</div>
			<div class="wcp-wait hidden">$wait_text</div>
			<div class="wcp-done hidden">$all_done</div>
			<p>$regen_desc</p>
		</div>
EOC;
		}	


		

		return $wcp_sorting;
	}

}	
