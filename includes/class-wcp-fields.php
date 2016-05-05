<?php
/**
 * WCP Class for Managing the custom fields
 */

    class wcp_fields extends main_wcp {
        // properties

        // methods

        //public function __construct() {
        //    parent::__construct();
        //}

		public function get_edit_fields() {
            global $wpdb;
			$this->load_db_options(); // load the current tables and options

            // no access to this page for non-admins
            $this->get_the_current_user();
            if ($this->current_access != 'full') {
                $content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                return $content;
            }

			$content = '<div class="wcp-tabs">'
                     . '<ul class="tab-select">'
                     . '<li><a href="#fields-container"><i class="md-select-all"></i><span class="tab-label">'
                     . __('Manage Fields', 'shwcp') . '</span></a></li>'
                     . '<li class="dropdown-options-tab"><a href="#options-container"><i class="md-input"></i>'
					 . '<span class="tab-label">'
                     . __('Manage Dropdown Options', 'shwcp') . '</span></a></li>';


            $content .= '</ul>';

            $lead_columns = $wpdb->get_results (
                "
                    SELECT * from $this->table_sort order by sort_ind_number asc
                "
            );

            $field_title      = __('Add, Edit, Define, Sort (for individual view and forms) and Remove Fields', 'shwcp');
            $field_desc       = __('Core Fields cannot be removed, but all can be renamed.', 'shwcp');
            $save             = __('Save Changes', 'shwcp');
            $add_new_text     = __('Add New Field', 'shwcp');
            $new_text         = __('New Field', 'shwcp');
            $text_field_text  = __('Text Field', 'shwcp');
            $text_area_text   = __('Text Area', 'shwcp');
            $phone_text       = __('Phone Number', 'shwcp');
            $email_text       = __('Email Address', 'shwcp');
            $website_text     = __('Website Address', 'shwcp');
            $map_text         = __('Google Map Link', 'shwcp');
            $date_text        = __('Date Time', 'shwcp');
            $rate_text        = __('Rating', 'shwcp');
            $check_text       = __('Checkbox', 'shwcp');
            $field_type_text  = __('Field Type', 'shwcp');
			$dropdown_text    = __('Dropdown', 'shwcp');
            $group_title_text = __('Group Title', 'shwcp');
            $required_text    = __('Required', 'shwcp');

            $remove_text      = __('Toggle Field Removal', 'shwcp');
            $remove_set_text  = __('Set For Removal', 'shwcp');
            $cancel_text      = __('Cancel', 'shwcp');

            $date_warning = __('If changing an existing field type to the Date Time selection all existing data for this field will be removed.  Just make sure that is what you want before saving.', 'shwcp');
            $date_warning_title = __('Warning', 'shwcp');
            $date_warning_close = __('Close', 'shwcp');
	
			$content .= <<<EOC
			<div class="fields-container" id="fields-container">
            	<div class="fields-top">
                	<div class="wcp-title">
                    	$field_title<br />
                	</div>
					<p>$field_desc</p>
					<hr>
                	<div class="field-actions">
                    	<div class="wcp-button save-fields">$save</div> <div class="wcp-button add-field">$add_new_text</div>
                    	<div class="date-field-warning" style="display:none">
                        	<div class='warning-title'>$date_warning_title</div>
                        	<div class='warning-message'>$date_warning</div>
                        	<div class='warning-close'>$date_warning_close</div>
                    	</div>
                	</div>
            	</div>
            	<div class="clear-both"></div>
            	<div class="wcp-fields">
EOC;
            foreach ($lead_columns as $k => $v) {
                if (!in_array($v->orig_name, $this->field_noremove)) {
                    $remove = '<i class="remove-field wcp-red wcp-md md-remove-circle-outline" title="' . $remove_text . '"></i>'
                            . '<div class="wcp-button cancel-remove" style="display:none;">' . $cancel_text . '</div>';

                    // Field types
                    // 1 text field
                    // 2 text area
                    // 3 telephone number
                    // 4 email address
                    // 5 website address
                    // 6 google map address
                    // 7 date picker
                    // 8 rating
                    // 9 checkbox
					// 10 dropdown select
                    // 99 group title
                    if (isset($v->field_type) ) {
                        $field_type = $v->field_type;
                    } else {
                        $field_type = '1';
                    }

			$checked = 'checked="checked"';
                    $field_options = '<div class="field-options-holder">'
                        . '<i class="field-options wcp-md md-data-usage" title="'
                        . $field_type_text . '"></i>'
                        . '<div class="popover-material">'

                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="1" '
                        . (($field_type == '1') ? $checked : '')
                        . ' data-text="' . $text_field_text . '" />' . $text_field_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="2" '
                        . (($field_type == '2') ? $checked : '')
                        . ' data-text="' . $text_area_text . '" />' . $text_area_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="3" '
                        . (($field_type == '3') ? $checked : '')
                        . ' data-text="' . $phone_text . '" />' . $phone_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="4" '
                        . (($field_type == '4') ? $checked : '')
                        . ' data-text="' . $email_text . '" />' . $email_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="5" '
                        . (($field_type == '5') ? $checked : '')
                        . ' data-text="' . $website_text . '" />' . $website_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="6" '
                        . (($field_type == '6') ? $checked : '')
                        . ' data-text="' . $map_text . '" />' . $map_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="7" '
                        . (($field_type == '7') ? $checked : '')
                        . ' data-text="' . $date_text . '" />' . $date_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="8" '
                        . (($field_type == '8') ? $checked : '')
                        . ' data-text="' . $rate_text . '" />' . $rate_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="9" '
                        . (($field_type == '9') ? $checked : '')
                        . ' data-text="' . $check_text . '" />' . $check_text . '<br />'
						. '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="10" '
                        . (($field_type == '10') ? $checked : '')
                        . ' data-text="' . $dropdown_text . '" />' . $dropdown_text . '<br />'
                        . '<input type="radio" class="field-type" name="' . $v->orig_name . '-type" value="99" '
                        . (($field_type == '99') ? $checked : '')
                        . ' data-text="' . $group_title_text . '" />' . $group_title_text . '<br />'
                        . '</div></div>';

                    $group_title_bg = '';
                    $required_check = $v->required_input == 1 ? 'checked="checked"' : '';
                    $required_field = '<div class="required-field-holder"><input type="checkbox"'
                                    . ' id="' . $v->orig_name . '-req" class="required-field" ' . $required_check . '/>'
                                    . '<label for="' . $v->orig_name . '-req">' . $required_text . '</label></div>';

					if ($field_type == '2') {
                        $specific_type = '<div class="field-options-title"><span>' . $text_area_text . '</span></div>';
                    } elseif ($field_type == '3') {
                        $specific_type = '<div class="field-options-title"><span>' . $phone_text . '</span></div>';
                    } elseif ($field_type == '4') {
                        $specific_type = '<div class="field-options-title"><span>' . $email_text . '</span></div>';
                    } elseif ($field_type == '5') {
                        $specific_type = '<div class="field-options-title"><span>' . $website_text . '</span></div>';
                    } elseif ($field_type == '6') {
                        $specific_type = '<div class="field-options-title"><span>' . $map_text . '</span></div>';
                    } elseif ($field_type == '7') {
                        $specific_type = '<div class="field-options-title"><span>' . $date_text . '</span></div>';
                    } elseif ($field_type == '8') {
                        $specific_type = '<div class="field-options-title"><span>' . $rate_text . '</span></div>';
                        $required_field = ''; // not on ratings
                    } elseif ($field_type == '9') {
                        $specific_type = '<div class="field-options-title"><span>' . $check_text . '</span></div>';
                        $required_field = ''; // not on checkboxes
					} elseif ($field_type == '10') {
						$specific_type = '<div class="field-options-title"><span>' . $dropdown_text .'</span></div>';
						$required_field = ''; // not on dropdowns
                    } elseif ($field_type == '99') {
                        $group_title_bg = ' style="background-color: #ededed;"';
                        $specific_type = '<div class="field-options-title"><span>' . $group_title_text . '</span></div>';
                        $required_field = ''; // just a label and not form input
                    } else {
                        $specific_type = '<div class="field-options-title"><span>' . $text_field_text . '</span></div>';
                    }

                } else {
                    $remove = '';
                    $field_options = '';
                    $specific_type = '';
                    $required_field = '';
                    $group_title_bg = '';
                }
                $clean_name  = stripslashes($v->translated_name);
                $content .= '<div class="wcp-fielddiv"' . $group_title_bg . '><div class="wcp-group input-field">'
                    . '<label for="' . $v->orig_name . '" class="field-label">' . $v->orig_name . '</label>'
                    . '<input class="wcp-field ' . $v->orig_name . '" type="text" id="' . $v->orig_name
                    . '" value="' . $clean_name . '" required />'
                    . '</div>' . $specific_type . $remove
                    . $field_options
                    . '<i class="wcp-md md-sort" title="' . __('Sort', 'shwcp') . '"></i>'
                    . $required_field
                    . '</div>';
            }

			$content .= <<<EOC
            	</div>
            	<div class="remove-set-text" style="display:none;">$remove_set_text</div>
            	<div class="remove-text" style="display:none;">$remove_text</div>
            	<div class="new-text" style="display:none;">$new_text</div>
            	<div class="field-type-text" style="display:none;">$field_type_text</div>
            	<div class="textfield-text" style="display:none;">$text_field_text</div>
            	<div class="textarea-text" style="display:none;">$text_area_text</div>
            	<div class="phone-text" style="display:none;">$phone_text</div>
            	<div class="email-text" style="display:none;">$email_text</div>
            	<div class="website-text" style="display:none;">$website_text</div>
            	<div class="map-text" style="display:none;">$map_text</div>
            	<div class="date-text" style="display:none;">$date_text</div>
            	<div class="rate-text" style="display:none;">$rate_text</div>
				<div class="dropdown-text" style="display:none;">$dropdown_text</div>
            	<div class="check-text" style="display: none;">$check_text</div>
            	<div class="group-title-text" style="display:none;">$group_title_text</div>
            	<div class="required-text" style="display:none;">$required_text</div>
			</div> <!-- End fields-container -->
EOC;

			/* Start Manage Dropdown fields */
			$options_title = __('Create your option lists for dropdown custom field types', 'shwcp');
			$options_desc = __('You must have created and saved at least one dropdown field in Manage Fields first', 'shwcp');
			$select_desc = __('Select the List to manage', 'shwcp');
			$dropdown_label = __('Dropdown Field To Manage', 'shwcp');
			$default_option = __('Select', 'shwcp');
			$toggle_text = __('Toggle Option Removal', 'shwcp');
			$sort_text = __('Sort', 'shwcp');
			$options_text = __('Options', 'shwcp');
			$add_option_text = __('Add New Option', 'shwcp');
			$save_options_text = __('Save Dropdown Options', 'shwcp');
			$new_option_text = __('New Option', 'shwcp');
			
			$dropdowns = array();

			$content .= <<<EOC
			<div class="options-container" id="options-container">
				<div class="wcp-title">
                        $options_title<br />
                </div>
                <p>$options_desc</p>
				<hr>
				<div class="dropdown-label" style="display:none;">$dropdown_label</div>
				<div class="default-option" style="display:none;">$default_option</div>
				<div class="toggle-text" style="display:none;">$toggle_text</div>
				<div class="sort-text" style="display:none;">$sort_text</div>
				<div class="options-text" style="display:none;">$options_text</div>
				<div class="add-option-text" style="display:none;">$add_option_text</div>
				<div class="save-options-text" style="display:none;">$save_options_text</div>

				<div class="option-clone option-new">
                    <input class="wcp-selname" type="text" value="$new_option_text" />
                    <i class="remove-option wcp-red wcp-md md-remove-circle-outline"> </i>
                    <i class="option-sort wcp-md md-sort"> </i>
                </div>
				<div class="row">
					<div class="dropdown-container col-md-6">
					</div>
				</div>
				<div class="row">
					<div class="options-div col-md-4 col-sm-12"> </div>
				</div>
			</div>
EOC;

		
			$content .= <<<EOC
		</div><!-- End Tabs Container -->
EOC;

            return $content;

        }

	}
