<?php
/**
 * WCP Class for scheduling notifications
 * The wp cron schedule runs at set intervals and will run outside of WP Contacts.  We need to check all DB's and event tables 
 */

    class wcp_cron {
        // properties

        // methods
		public function wcp_cron_check() {
			global $wpdb;

			// check our databases
			$options_table = $wpdb->prefix . 'options';
            $option_entry = 'shwcp_main_settings';
            $dbs = $wpdb->get_results("SELECT * FROM $options_table WHERE `option_name` LIKE '%$option_entry%'");
            $databases = array();
            foreach ($dbs as $k => $option) {
				$db_options = get_option($option->option_name);
				if (isset($db_options['calendar_events'])       // Calendar is enabled
					&& $db_options['calendar_events'] == 'true'
				) {
					if (isset($db_options['calendar_notify'])       // Notifications enabled
						&& ($db_options['calendar_notify'] == 'both'
						|| $db_options['calendar_notify'] == 'email')
					) { 
						if ($option->option_name == $option_entry) {  // default
							$db_number = 'default';
						} else {
							$remove_name = '/^' . $option_entry . '_/';  // Just get the database number
                    		$db_number = preg_replace($remove_name, '', $option->option_name);				
						}
						$this->check_events_table( $db_number );
					}	
                }
            }
		}

		/*
         * check_events_table
	     * checks for existing events to notify on
         */
		public function check_events_table($db_number) {
			global $wpdb;
			if ($db_number) {
				if ($db_number == 'default') {
					$events_table = $wpdb->prefix . SHWCP_EVENTS;
				} else {
					$events_table = $wpdb->prefix . SHWCP_EVENTS . '_' . $db_number;
				}
				$events = $wpdb->get_results ( "SELECT * FROM $events_table WHERE `alert_enable`=1 and `notify_email_sent`=0" ); 
				// get the enabled alerts without the sent flag
                foreach($events as $k => $v) {
					$notify_at = $v->notify_at;
					$event_id = $v->id;
					$event_repeat = $v->repeat;
					$notify_at_ts = strtotime($notify_at);
					$current_time = strtotime('now');

					if ($notify_at_ts <= $current_time) {
						$event = $v->title;
						$details = $v->details;
						$notify_who = unserialize($v->notify_who);
						$wp_users_table = $wpdb->prefix . 'users';
						$recipients = array();
						foreach($notify_who as $userid => $username) {
							$user_email = $wpdb->get_var("SELECT user_email FROM $wp_users_table  where ID='$userid'");
							$recipients[] = $user_email;
						}
						if (!empty($recipients)) {			
							$this->send_mail_notify($event, $details, $recipients);
							$this->set_new_notification($events_table, $v);
						}
					}
				}
			}
		}

		/* 
         * Send the notification
         */
		public function send_mail_notify($event, $details, $recipients) {
	        $eol = PHP_EOL;

            $to = $recipients;
            $subject = __('Event Notification', 'shwcp') . '-' . stripslashes($event);
            $msg = stripslashes($details);
            add_filter( 'wp_mail_from_name', array(&$this, 'custom_wp_mail_from_name') ); // set name to email
            add_filter( 'wp_mail_content_type', array(&$this, 'set_html_content_type' ) );
            wp_mail($to, $subject, $msg);
            // Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
            remove_filter( 'wp_mail_content_type', array(&$this, 'set_html_content_type') );
		}


		/**
         * Custom from name reset to email address instead of default 'WordPress' -- not currently using
         *
         */
        public function custom_wp_mail_from_name() {
            return __('WPC Event', 'shwcp');
        }

        /**
         * Set message to html
         */
        public function set_html_content_type() {
            return 'text/html';
        }

		/**
		 * Set New Notification time for repeat events
		 * repeat events never have the sent flag set, but single occurance do
		 **/
		public function set_new_notification($events_table, $event) {
			global $wpdb;
			/* Single occurance just update and set to sent for no further notifications */
			if ($event->repeat != '1') {  
				$wpdb->update(
                	$events_table,
                    array(
                    	'notify_email_sent' => 1,
                    ),
                    array( 'id' => $event->id),
                    array('%d'),
                    array('%d')
               );
			/* Repeat occurance, update notify_at time to new alert */
			} else {  
				$now = strtotime('now');
				$time_math = '-';
                if ($event->alert_time == 'afterstart') {
                    $time_math = '+';
                }
				$new_start = strtotime("+ 1 $event->repeat_every", strtotime($event->start));
				$new_notify = strtotime("$time_math $event->alert_notify_inc $event->alert_notify_sel", $new_start);
				while ($new_notify < $now) {   // increment the date by the repeat value until it's now or greater
					$new_notify = strtotime(" + 1 $event->repeat_every", $new_notify);
				}
				$new_notify = date('Y-m-d H:i:s', $new_notify);
				$wpdb->update(
                    $events_table,
                    array(
                        'notify_at' => $new_notify,
                    ),
                    array( 'id' => $event->id),
                    array('%s'),
                    array('%d')
               );
			}			
		}	

	} // end class
