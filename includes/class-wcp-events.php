<?php
/**
 * WCP Class for displaying events page view
 */

    class wcp_events extends main_wcp {
        // properties

        // methods

		/*
         * Retrieve events view
         */
        public function show_events() {
            global $wpdb;
			// no access to this page for non-admins
			$this->load_db_options(); // load the current tables and options
			$this->get_the_current_user();
			//echo $this->current_access;
            //if ($this->current_access != 'full') {
			if (!$this->can_access ) {  // general access to the leads
                $content = '<span class="no-access">' . __('You do not have access to this page', 'shwcp') . '</span>';
                return $content;
            }


			$cancel_text = __('Close', 'shwcp');
			$delete_text = __('Delete Event', 'shwcp');
			$edit_text = __('Edit Event', 'shwcp');
			$entry_text = __('Go To Entry', 'shwcp');
			$delete_alert = __('Click delete again if you are sure you want to remove this event.', 'shwcp');
			$can_edit = ($this->can_edit) ? 'canedit' : 'none';
			$current_access = $this->current_access;
			$events = <<<EOT
				<div class="row">
					<div class="col-md-12">
						<div class="shwcp-calendar"></div>
					</div>
				</div>
				<div class="cancel-text" style="display:none;">$cancel_text</div>
				<div class="delete-text" style="display:none;">$delete_text</div>
			    <div class="edit-text" style="display:none;">$edit_text</div>
			    <div class="entry-text" style="display:none;">$entry_text</div>
				<div class="delete-alert" style="display:none;">$delete_alert</div>
				<div class="can-edit" style="display:none;">$can_edit</div>
				<div class="current-access" style="display:none;">$current_access</div>
EOT;

            return $events;
        }

	}
