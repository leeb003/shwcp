<?php
/**
 * WCP Class for managing the Permissions and s cess
 * We will be moving the backend permissions to this area
 * Managing user cannot change their own role here
 * We'll keep using the permissions option, but need to consider options not present on backend
 */

class wcp_manage_perms extends main_wcp {
	// properties

	// methods

	//public function __construct() {
	//    parent::__construct();
	//}

	/*
	 * Permissions page
	 */
	public function permissions() {
		global $wpdb;
		$this->load_db_options(); // load the current tables and options

		// no access to this page for non-admins
		$this->get_the_current_user();
		if ($this->current_access != 'full') {
			$content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
			return $content;
		}
	
		$existing_perms = $this->second_tab;
		$user_id = $this->current_user->ID;

		$perms_desc = __("Set up permissions and access for your users.", 'shwcp');

		$own_entries = isset($existing_perms['own_leads_change_owner']) ? $existing_perms['own_leads_change_owner'] : 'no';
		$mo_view = isset($existing_perms['own_entries_view_all']) ? $existing_perms['own_entries_view_all'] : 'no';

		$own_entries_no = selected($own_entries, 'no', false);
		$own_entries_no_text = __('No', 'shwcp');
		$own_entries_yes = selected($own_entries, 'yes', false);
		$own_entries_yes_text = __('Yes', 'shwcp');
		$own_entries_label =  __('Manage Own Entries Ownership Change', 'shwcp');
		$own_entries_desc = __("Allows <u>Manage Own Entries</u> users to change ownership (hand entries off to other users)","shwcp");

		$mo_view_label = __('Manage Own Entries View All', 'shwcp');
		$mo_view_desc = __('Allow <u>Manage Own Entries</u> users to view all other entries', 'shwcp');
		$mo_view_no_text = __('No', 'shwcp');
		$mo_view_no = selected($mo_view, 'no', false);
		$mo_view_yes_text = __('Yes', 'shwcp');
		$mo_view_yes = selected($mo_view, 'yes', false);
	
		$users_title  = __('Users', 'shwcp');
		$wp_user_text = __('WP Username', 'shwcp');
		$access_text  = __('Access', 'shwcp');

		$users = get_users();
        $access_levels = array(
            'none'     => __('No Access', 'shwcp'),
            'readonly' => __('Read Only', 'shwcp'),
            'ownleads' => __('Manage Own Leads', 'shwcp'),
            'full'     => __('Full Access', 'shwcp')
        );

		// custom access roles temp
        $custom_access = array(
            'ca1'      => __('Description', 'shwcp'),
            'ca2'      => __('Description 2', 'shwcp')
        );

		$all_access_levels = $access_levels + $custom_access;

		$perms_tab_title = __('Permissions', 'shwcp');
		$ca_tab_title    = __('Custom Access Roles', 'shwcp');

		$perms_output = <<<EOC
		<div class="wcp-tabs">
			<ul class="tab-select">
            	<li>
					<a href="#wcp-perms-tab"><i class="md-group"></i><span class="tab-label">$perms_tab_title</span></a>
				</li>
                <li>
					<a href="#wcp-ca-tab"><i class="md-group-add"></i><span class="tab-label">$ca_tab_title</span></a>
				</li>
			</ul>

			<div class="wcp-perms-tab" id="wcp-perms-tab" id="fields-container">

				<div class="wcp-title">$perms_desc</div>
				<hr />
				<div class="row">
					<div class="col-md-6">
						<div class="input-field">
							<label for="perms-change-owner">$own_entries_label</label>
							<select class="perms-change-owner input-select">
            					<option value="no" $own_entries_no >$own_entries_no_text</option>
            					<option value="yes" $own_entries_yes>$own_entries_yes_text</option>
        					</select>
						</div>
        				<p>$own_entries_desc</p>
					</div>
					<div class="col-md-6">
						<div class="input-field">
                    		<label for="mo-view">$mo_view_label</label>
                    		<select class="mo-view input-select">
                        		<option value="no" $mo_view_no >$mo_view_no_text</option>
                        		<option value="yes" $mo_view_yes>$mo_view_yes_text</option>
                    		</select>
                		</div>
                		<p>$mo_view_desc</p>
					</div>
				</div>
				<hr />
				<div class="row"><!-- Users -->
					<div class="col-md-12">
						<p>$users_title</p>
					</div>
					<div class="col-md-12">
						<table class="user-perms table">
							<thead>
								<tr>
									<th>$wp_user_text</th>
									<th>$access_text</th>
								</tr>
							</thead>
EOC;
		$i = 0;
        foreach ($users as $user) {
			$disabled = '';
			if ($user_id == $user->ID) { // Can't change your own access
				$disabled = 'disabled="disabled"';
			}
            $user_perm = isset($existing_perms['permission_settings'][$user->ID]) 
					   ? $existing_perms['permission_settings'][$user->ID] : 'none';
            $i++;
			$alt = $i&1;
			$perms_output .= <<<EOC
							<tbody>
                				<tr class="wcp-row$alt">
                    				<td>$user->user_login</td>
                    				<td>
										<div class="input-field">
											<select class="permission-level input-select permission-level-$user->ID" 
        										name="permissions-$user->ID" $disabled>
EOC;
                foreach ($all_access_levels as $av => $an) {
					if ($user_perm == $av) {
						$selected = 'selected="selected"';
					} else {
						$selected = '';
					}
                	$perms_output .= <<<EOC
											<option value="$av" $selected >$an</option>
EOC;
                }
			$perms_output .= <<<EOC
                        					</select>
										</div>
                    				</td>
                				</tr>	
EOC;
		}


		$perms_output .= <<<EOC
							</tbody>
						</table>
					</div>
				</div>
			</div><!-- Permissions Tab -->
			<div class="wcp-ca-tab" id="wcp-ca-tab">
				Custom Access
			</div>
		</div>

EOC;
		return $perms_output;
	}
}
