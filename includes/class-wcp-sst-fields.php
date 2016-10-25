<?php
/**
 * WCP Class for the Source Status and Types page display
 */

class wcp_sst_fields extends main_wcp {
	// properties

	// methods

	//public function __construct() {
	//    parent::__construct();
	//}

	/*
	 * Source Status & Types
	 */
	public function get_sst_fields() {
		global $wpdb;
		$this->load_db_options(); // load the current tables and options

		// no access to this page for non-admins
        $this->get_the_current_user();
		if ($this->current_access != 'full') {
			$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
			return $content;
		}

		$sst_source = $wpdb->get_results (
			"
				SELECT * from $this->table_sst where sst_type_desc='l_source' order by sst_order asc
			"
		);
		$sst_status = $wpdb->get_results (
			"
				SELECT * from $this->table_sst where sst_type_desc='l_status' order by sst_order asc
			"
		);
		$sst_type = $wpdb->get_results (
			"
				SELECT * from $this->table_sst where sst_type_desc='l_type' order by sst_order asc
			"
		);

		$sort_names = $wpdb->get_results (
			"
				SELECT * from $this->table_sort
			"
		);
		$source_name = 'l_source';   // default source name
		foreach($sort_names as $k => $v) {
			if ($v->orig_name == 'l_source') {
				$source_name = stripslashes($v->translated_name);
			}
		}

		$status_name = 'l_status';   // default status name
		foreach($sort_names as $k => $v) {
			if ($v->orig_name == 'l_status') {
				$status_name = stripslashes($v->translated_name);
			}
		}

		$type_name = 'l_type';   // default type name
		foreach($sort_names as $k => $v) {
			if ($v->orig_name == 'l_type') {
				$type_name = stripslashes($v->translated_name);
			}
		}

		$sst_title = __('Sources, Status & Types', 'shwcp');
		$sst_desc = __('These can be renamed under Manage Fields', 'shwcp');
		$add_text = __('Add New', 'shwcp');
		$new_field_text = __('New Field', 'shwcp');
		$save_text = __('Save All', 'shwcp');

		$sst_fields = <<<EOC
			<div class="sst-top">
				<div class="wcp-title">$sst_title</div>
				<p>$sst_desc</p>
				<div class="field-actions">
					<div class="wcp-button save-sst">$save_text</div>
				</div>
			</div>
			<div class="row">
				<div class="wcp-sst wcp-sources col-md-4 col-sm-6"><h4>$source_name <i class="add-sst wcp-md md-add-circle"
					title="$add_text $source_name"> </i></h4>
					<div class="wcp-sst-holder">
EOC;

		foreach($sst_source as $k => $v) {
			$remove = '';
			if ($v->sst_default != 1) {
				$remove = '<i class="remove-sst wcp-red wcp-md md-remove-circle-outline" title="'
						. __('Toggle Field Removal', 'shwcp') . '"> </i>';
			}
			$clean_name  = stripslashes($v->sst_name);
			$sst_fields .= '<div class="l_source">'
						. '<input class="source-' . $v->sst_id . '" type="text" value="' . $clean_name . '" />'
						. $remove . ' <i class="wcp-md md-sort" title="' . __('Sort', 'shwcp') . '"> </i></div>';
			}

			$sst_fields .= <<<EOC
				</div>
				<div class="sst-clone l_source">
					<input class="source-new" type="text" value="$new_field_text" />
					<i class="remove-sst wcp-red wcp-md md-remove-circle-outline"> </i>
					<i class="wcp-md md-sort"> </i>
				</div>
			</div>
			<div class="wcp-sst wcp-status col-md-4 col-sm-6"><h4>$status_name <i class="add-sst wcp-md md-add-circle"
				title="$add_text $status_name"> </i></h4>
				<div class="wcp-sst-holder">
EOC;

		foreach($sst_status as $k => $v) {
			$remove = '';
			if ($v->sst_default != 1) {
				$remove = '<i class="remove-sst wcp-red wcp-md md-remove-circle-outline" title="'
						. __('Toggle Field Removal', 'shwcp') . '"> </i>';
			}
			$sst_fields .= '<div class="l_status">'
						. '<input class="status-' . $v->sst_id . '" type="text" value="' . $v->sst_name . '"/>'
						. $remove . ' <i class="wcp-md md-sort" title="' . __('Sort', 'shwcp') . '"> </i></div>';
		}

		$sst_fields .= <<<EOC
				</div>
				<div class="sst-clone l_status">
					<input class="status-new" type="text" value="$new_field_text" />
					<i class="remove-sst wcp-red wcp-md md-remove-circle-outline"> </i>
					<i class="wcp-md md-sort"> </i>
				</div>
			</div>
			<div class="wcp-sst wcp-types col-md-4 col-sm-6"><h4>$type_name <i class="add-sst wcp-md md-add-circle"
				title="$add_text $type_name"> </i></h4>
				<div class="wcp-sst-holder">
EOC;
		foreach($sst_type as $k => $v) {
			$remove = '';
			if ($v->sst_default != 1) {
				$remove = '<i class="remove-sst wcp-red wcp-md md-remove-circle-outline" title="'
						. __('Toggle Field Removal', 'shwcp') . '"> </i>';
			}
			$sst_fields .= '<div class="l_type">'
						. '<input class="type-' . $v->sst_id . '" type="text" value="' . stripslashes($v->sst_name) . '" />'
						. $remove . ' <i class="wcp-md md-sort" title="' . __('Sort', 'shwcp') . '"> </i></div>';
			}

			$sst_fields .= <<<EOC
				</div>
				<div class="sst-clone l_type">
				<input class="type-new" type="text" value="$new_field_text" />
					<i class="remove-sst wcp-red wcp-md md-remove-circle-outline"> </i>
					<i class="wcp-md md-sort"> </i>
				</div>
			</div>
		</div><!-- End Row -->
EOC;

		return $sst_fields;
	}
}	
