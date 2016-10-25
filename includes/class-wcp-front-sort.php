<?php
/**
 * WCP Class for the Front Page Sorting
 */

class wcp_front_sort extends main_wcp {
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

		// no access to this page for non-admins
		$this->get_the_current_user();
		if ($this->current_access != 'full') {
			$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
			return $content;
		}

		$lead_columns = $wpdb->get_results(
			"
				SELECT * from $this->table_sort order by sort_number
			"
		);
		$items1_title = __('Fields To Display On Main View (In Order)', 'shwcp');
		$items2_title = __('Fields Not To Display On Main View', 'shwcp');
		$save = __('Save Changes', 'shwcp');

		$wcp_sorting = <<<EOC
		<div class="wcp-title">$items1_title</div>
		<ul class="keepers front-sorting">
EOC;
		foreach ($lead_columns as $k => $v) {
			if (1 == $v->sort_active) {
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
			if (1 != $v->sort_active) {
				$wcp_sorting .= '<li class="keeper ' . $v->orig_name . '">' . stripslashes($v->translated_name) . '</li>';
			}
		}

		$wcp_sorting .= <<<EOC
		</ul>
		<div class="row">
			<div class="col-md-12">
				<div class="wcp-button save-sorting">$save</div>
			</div>
		</div>
EOC;
		return $wcp_sorting;
	}

}	
