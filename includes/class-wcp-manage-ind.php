<?php
/**
 * WCP Class for managing the individual page 
 * $this->frontend_settings - new area introduced to save frontend specific configured settings, so as we grow
 * we won't need to manage these settings as hidden fields in the backend settings and we can shrink backend and 
 * move to front as needed.
 */

class wcp_ind_manage extends main_wcp {
	// properties

	// methods

	//public function __construct() {
	//    parent::__construct();
	//}

	/*
	 * Individual Page Tiles
	 */
	public function manage_individual() {
		global $wpdb;
		$this->load_db_options(); // load the current tables and options

		// no access to this page for non-admins or custom roles without access
		$this->get_the_current_user();
		$custom_role = $this->get_custom_role();
		if ($this->current_access != 'full'
			&& !$custom_role['access']
		) {
			$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
			return $content;
		} elseif ($custom_role['access'] 
            && $custom_role['perms']['manage_individual'] != 'yes'
        ) { 
            $content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
            return $content;
        }

		$width_title = __('Left Side Columns', 'shwcp');
		$width_desc = __('The columns used on the left side if there are active tiles in it.  If there are no active tiles on one side, the layout will be full width.', 'shwcp');
		$layout_title = __('Individual Page Layout', 'shwcp');
		$layout_desc = __("Set the Left Column width.  Drag and drop the tiles where you like, disable the ones you don't want to use.", 'shwcp');
		$photo_title   = __('Entry Photo Tile', 'shwcp');
		$files_title   = __('Entry Files Tile', 'shwcp');
		$fields_title  = __('Entry Fields Tile', 'shwcp');
		$notes_title   = __('Entry Notes Tile', 'shwcp');
		$details_title = __('Entry Details Tile', 'shwcp');
		$enabled       = __('Enabled', 'shwcp');
		$disabled      = __('Disabled', 'shwcp');
		$left_text     = __('Left Side', 'shwcp');
		$right_text    = __('Right Side', 'shwcp');
		$bottom_text   = __('Bottom', 'shwcp');

		// Settings array with same names so we can loop in sections
		$enabled_tiles = array(
        	'files_tile'   => isset($this->first_tab['contact_upload'])  ? $this->first_tab['contact_upload'] : 'true',
        	'photo_tile'   => isset($this->first_tab['contact_image'])   ? $this->first_tab['contact_image'] : 'true',
        	'fields_tile'  => isset($this->frontend_settings['fields_enabled']) 
							? $this->frontend_settings['fields_enabled'] : 'true',
        	'details_tile' => isset($this->frontend_settings['details_enabled']) 
							? $this->frontend_settings['details_enabled'] : 'true',
        	'notes_tile'   => isset($this->frontend_settings['notes_enabled'])   
							? $this->frontend_settings['notes_enabled'] : 'true'
		);
        $current_columns      = isset($this->frontend_settings['ind_columns'])       
							? $this->frontend_settings['ind_columns'] : 4;
		$right_columns        = 12 - $current_columns;
		$individual_layout    = isset($this->frontend_settings['individual_layout']) 
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

		$characters = glob(SHWCP_ROOT_PATH . '/assets/img/characters/*');
		$rand_img   =  $characters[rand(0, count($characters) - 1)];
		$photo_img  = SHWCP_ROOT_URL . '/assets/img/characters/' . basename($rand_img);

		/* Tiles with dummy data
		 * the leading <li> and enabled/disabled divs are built in the section loops for each tile
         */ 
		$photo_tile = <<<EOC
						<div class="tile-title text-center">$photo_title</div>
                  		<div class="ind-photo"><img class="img-fluid" src="$photo_img" /></div>
					</li>
EOC;
		$files_tile = <<<EOC
						<div class="tile-title text-center">$files_title</div>
						<div class="existing-files">
                        	<div class="lead-info">
                            	<span class="leadfile-name">
                                	<a class="leadfile-link" href="#">Capture.JPG</a>
                            	</span>
                            	<span class="leadfile-size">22 KB
                                	<i class="wcp-red wcp-md md-remove-circle-outline"> </i>
                            	</span>
                        	</div>
                        	<div class="lead-info">
                            	<span class="leadfile-name">
                                	<span class="leadfile-link">Capture2.JPG</span>
                            	</span>
                            	<span class="leadfile-size">24 KB
                                	<i class="wcp-red wcp-md md-remove-circle-outline"> </i>
                            	</span>
                        	</div>
                        	<div class="lead-info">
                            	<span class="leadfile-name">
                                	<span class="leadfile-link">blog1.jpg</span>
                            	</span>
                            	<span class="leadfile-size">322 KB
                                	<i class="wcp-red wcp-md md-remove-circle-outline"> </i>
                            	</span>
                        	</div>
                        	<div class="lead-info">
                            	<span class="leadfile-name">
                                	<span class="leadfile-link">itemdesc.txt</span>
                            	</span>
                            	<span class="leadfile-size">1 KB
                                	<i class="wcp-red wcp-md md-remove-circle-outline"> </i>
                            	</span>
                        	</div>
                        	<div class="lead-info">
                            	<span class="leadfile-name">
                                	<span class="leadfile-link">settings.zip</span>
                            	</span>
                            	<span class="leadfile-size">3 KB
                                	<i class="wcp-red wcp-md md-remove-circle-outline"> </i>
                            	</span>
                        	</div>
						</div>
                    </li>
EOC;

		$fields_tile = <<<EOC
						<div class="tile-title text-center">$fields_title</div>
                        <div class="wcp-edit-lead-dummy leadID-na row">
                            <div class="col-md-6 first_name-col">
                                <div class="input-field">
                                    <label for="first_name">First Name</label>
                                    <input class="lead_field first_name" value="Bob" type="text" disabled="disabled" />
                                </div>
                            </div>                                          
                            <div class="col-md-6 last_name-col">
                                <div class="input-field">
                                    <label for="last_name">Last Name</label>
                                    <input class="lead_field last_name" value="Smithe" type="text" disabled="disabled" />
                                </div>
                            </div>                                                  
                            <div class="col-md-12 extra_column_4-col">
                                <div class="input-field fields-grouptitle">
                                    <h3 for="extra_column_4">Secondary Information</h3>
                                    <input class="lead_field extra_column_4" value="" type="hidden" />
                                </div>
                            </div>                                                  
                            <div class="col-md-6 extra_column_1-col">
                                <div class="input-field">
                                    <label for="extra_column_1">Dropdown</label>
                                    <select id="extra_column_1" class="lead_select extra_column_1 input-select" disabled="disabled">
                                
                                        <option value="14" selected="selected">Choice 1</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 extra_column_5-col">
                                <div class="input-field">
                                    <label for="extra_column_5">date</label>
                                    <input class="lead_field extra_column_5 date-choice" value="13/03/2017 15:31:40" disabled="disabled" type="text" />
                                </div>
                            </div>
                            <div class="col-md-6 extra_column_6-col">
                                <div class="input-field">
                                    <label for="extra_column_6">Dropdown 2</label>
                                    <select id="extra_column_6" class="lead_select extra_column_6 input-select" disabled="disabled">
                                                                
                                        <option value="34" selected="selected">DD2 Option 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 l_source-col">
                                <div class="input-field">
                                    <label for="l_source">Source</label>
                                    <select id="l_source" class="lead_select l_source input-select" disabled="disabled">
                                        <option value="29" selected="selected">Online</option>
                                        <option value="1" >Default</option>
                                    </select>
                                </div>
                            </div>                                                  
                            <div class="col-md-6 l_status-col">
                                <div class="input-field">
                                    <label for="l_status">Status</label>
                                    <select id="l_status" class="lead_select l_status input-select" disabled="disabled">
                                        <option value="26" selected="selected">Active</option>
                                    </select>
                                </div>
                            </div>                                                  
                            <div class="col-md-6 l_type-col">
                                <div class="input-field">
                                    <label for="l_type">Type</label>
                                    <select id="l_type" class="lead_select l_type input-select" disabled="disabled">
                                        <option value="12" selected="selected">Type 2</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 owned_by-col">
                                <div class="input-field"><label for="owned_by">Owned By</label>
                                    <select class="lead_select owned_by input-select" disabled="disabled">
                                        <option value="jimmy">jimmy</option>
                                    </select>
                                </div>
                            </div>
						</div>
                    </li>
EOC;

		$notes_tile = <<<EOC
						<div class="tile-title text-center">$notes_title</div>
                        <div class="lead-notes-container leadID-1">
                            <div class="lead-note leadID-na noteID-23">
                                <i class="wcp-red wcp-md md-remove-circle-outline remove-note-dummy"> </i>
                                <i class="wcp-md md-create edit-note-dummy"> </i>
                                <span class="timeline-header"> 
									<i class="wcp-dark md-history"></i> 13/03/2017 15:31:40 &nbsp;&nbsp;
									<i class="wcp-dark md-person-outline"></i> johndoe &nbsp;&nbsp; 
									<span class="wcp-dark">|</span> &nbsp;&nbsp;Last Updated by johndoe</span>
                                <span class="timeline-body">
									<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
								</span>
                            </div>
                            <div class="lead-note leadID-na noteID-22">
                                <i class="wcp-red wcp-md md-remove-circle-outline remove-note-dummy"> </i>
                                <i class="wcp-md md-create edit-note-dummy"> </i>
                                <span class="timeline-header"> 
									<i class="wcp-dark md-history"></i> 13/03/2017 15:31:16 &nbsp;&nbsp;
									<i class="wcp-dark md-person-outline"></i> johndoe&nbsp;&nbsp; 
									<span class="wcp-dark">|</span> &nbsp;&nbsp;Last Updated by johndoe</span>
                                <span class="timeline-body"><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
								</span>
                            </div>
                        </div>
                    </li>
EOC;
		$details_tile = <<<EOC
                        <div class="tile-title text-center">$details_title</div>
                        <div class="detail-bottom row">
                            <div class="col-md-4 text-center id">
                                <span class="non-edits">ID</span> 1
                            </div>
                            <div class="col-md-4 text-center created_by">
                                <span class="non-edits">Created By</span> sh-themes 
                                <span class="detail-date">09/12/2016 17:53:10</span>
                            </div>
                            <div class="col-md-4 text-center updated_by">
                                <span class="non-edits">Updated By</span> sh-themes 
                                <span class="detail-date">13/03/2017 18:06:30</span>
                            </div>                            
                        </div>
                    </li>
EOC;

		// Put them in an array for easy reference
		$tiles = array(
			'photo_tile'   => $photo_tile,
			'files_tile'   => $files_tile,
			'fields_tile'  => $fields_tile,
			'notes_tile'   => $notes_tile,
			'details_tile' => $details_tile
		);

		$wcp_ind = <<<EOC
		<div class="wcp-title">$layout_title</div>
		<p>$layout_desc</p>
		<div class="row slider-row">
			<div class="col-md-6">
				<div class="slider-holder">
					<label for="left-col-width">$width_title</label>
					<div class="slider left-col-width">
						<div id="slide-handle" class="ui-slider-handle"></div>
					</div><br />
					<div>
					<p>$width_desc</p>
					</div>
				</div>
			</div>
			<div class="col-md-6"></div>
		</div>
		<div class="row manage-individual">
			<div class="col-md-$current_columns column left-column">
				<ul class="man-left-side man-ind">
EOC;
		// Left Column
		foreach ($individual_layout as $k => $v) {
			if ($v['pos'] == 'left_side') {
				$tile = $v['tile'];
				$is_enabled = $enabled_tiles[$tile];
				if ($is_enabled == 'true') {
					$enabled_view = <<<EOC
					<li class="ind-tile $tile">
						<div class="ind-status enabled"><i class="md-done" title="$enabled"></i></div>
                        <div class="disabled-overlay notshown"><p>$disabled</p></div>
EOC;
				} else { // disabled
					$enabled_view = <<<EOC
					<li class="ind-tile $tile">
						<div class="ind-status disabled"><i class="md-clear" title="$disabled"></i></div>
                        <div class="disabled-overlay"><p>$disabled</p></div>
EOC;
				}
				$wcp_ind .= $enabled_view . $tiles[$tile];
			}
		}
					
		$wcp_ind .= <<<EOC
				</ul>
				<div class="ind-area-text left-text">$left_text</div>
			</div>
			<div class="col-md-$right_columns column right-column">
				<ul class="man-right-side man-ind">

EOC;
		// Right Column
        foreach ($individual_layout as $k => $v) {
            if ($v['pos'] == 'right_side') {
                $tile = $v['tile'];
                $is_enabled = $enabled_tiles[$tile];
                if ($is_enabled == 'true') {
                    $enabled_view = <<<EOC
                    <li class="ind-tile $tile">
                        <div class="ind-status enabled"><i class="md-done" title="$enabled"></i></div>
                        <div class="disabled-overlay notshown"><p>$disabled</p></div>
EOC;
                } else { // disabled
                    $enabled_view = <<<EOC
                    <li class="ind-tile $tile">
                        <div class="ind-status disabled"><i class="md-clear" title="$disabled"></i></div>
                        <div class="disabled-overlay"><p>$disabled</p></div>
EOC;
                }
                $wcp_ind .= $enabled_view . $tiles[$tile];
            }
        }
			
		$wcp_ind .= <<<EOC
				</ul>
				<div class="ind-area-text right-text">$right_text</div>
			</div>
			<div class="col-md-12 column bottom-row">
                <ul class="man-bottom man-ind">
EOC;
		// Bottom Row
        foreach ($individual_layout as $k => $v) {
            if ($v['pos'] == 'bottom_row') {
                $tile = $v['tile'];
                $is_enabled = $enabled_tiles[$tile];
                if ($is_enabled == 'true') {
                    $enabled_view = <<<EOC
                    <li class="ind-tile $tile">
                        <div class="ind-status enabled"><i class="md-done" title="$enabled"></i></div>
                        <div class="disabled-overlay notshown"><p>$disabled</p></div>
EOC;
                } else { // disabled
                    $enabled_view = <<<EOC
                    <li class="ind-tile $tile">
                        <div class="ind-status disabled"><i class="md-clear" title="$disabled"></i></div>
                        <div class="disabled-overlay"><p>$disabled</p></div>
EOC;
                }
                $wcp_ind .= $enabled_view . $tiles[$tile];
            }
        }

		$wcp_ind .= <<<EOC
				</ul>
				<div class="ind-area-text bottom-text">$bottom_text</div>
			</div>
		</div>
		<div class="enabled-text" style="display:none;">$enabled</div>
		<div class="disabled-text" style="display:none;">$disabled</div>
EOC;

		return $wcp_ind;
	}
}
