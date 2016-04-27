<?php
/**
  * WCP Class Set up plugin tables and data extends main class
  */

    class setup_wcp extends main_wcp {
        // properties

		// methods
		/* 
		 * Check if our leads table exists upon activation
		 */
		public function table_exists() {
			global $wpdb;
			$table_main     = $wpdb->prefix . SHWCP_LEADS;
			if ($wpdb->get_var("SHOW TABLES LIKE '" . $table_main . "'") === $table_main) {
 				// The database table exist
				return true;
			} else {
 				// Table does not exist
				return false;
			}
		}	

		/*
		 * Drop all tables so they can be recreated again (on database reset)
		 */
		public function drop_tables($dbnumber='') {
			global $wpdb;
			// re-assign table names to include $dbnumber if this is a non-default install / addition
			$table_main     = $wpdb->prefix . SHWCP_LEADS  . $dbnumber;
            $table_sst      = $wpdb->prefix . SHWCP_SST    . $dbnumber;
            $table_log      = $wpdb->prefix . SHWCP_LOG    . $dbnumber;
            $table_sort     = $wpdb->prefix . SHWCP_SORT   . $dbnumber;
            $table_notes    = $wpdb->prefix . SHWCP_NOTES  . $dbnumber;
			$table_events   = $wpdb->prefix . SHWCP_EVENTS . $dbnumber;


			global $wpdb;
			$wpdb->query("DROP TABLE IF EXISTS $table_main");
			$wpdb->query("DROP TABLE IF EXISTS $table_sst");
			$wpdb->query("DROP TABLE IF EXISTS $table_sort");
			$wpdb->query("DROP TABLE IF EXISTS $table_log");
			$wpdb->query("DROP TABLE IF EXISTS $table_notes");
			$wpdb->query("DROP TABLE IF EXISTS $table_events");
			
		}

		/*
		 * Gather table data to return for backups
		 * takes array of tables and returns output
		 */
		public function backup_tables($tables) {
			global $wpdb;
			$table_list = implode(', ', $tables);
			$charset_collate = $wpdb->get_charset_collate();
			$data['create'] = "\n/*---------------------------------------------------------------".
          			"\n  SQL DB BACKUP ".date("d.m.Y H:i")." ".
          			"\n  TABLES: $table_list".
          			"\n  ---------------------------------------------------------------*/\n";
  			//mysql_query( "SET NAMES `utf8` COLLATE `utf8_general_ci`" , $link ); // Unicode
			$data['insert'] = '';
   			$tables = is_array($tables) ? $tables : explode(',',$tables);

  			foreach($tables as $table){
    			$data['create'] .= "\n/*---------------------------------------------------------------".
            			"\n  TABLE: `{$table}`".
            			"\n  ---------------------------------------------------------------*/\n";           
    			// $data .= "DROP TABLE IF EXISTS `{$table}`;\n";
				$res = $wpdb->get_results("SHOW CREATE TABLE `{$table}`", ARRAY_N);
    			$data['create'] .= $res[0][1] . ";\n";

				$result = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_N);
				$num_rows = $wpdb->num_rows;

    			if ($num_rows>0) { 
      				$vals = Array(); $z=0;
					foreach($result as $k => $items) {
        				$vals[$z]="(";
        				for($j=0; $j<count($items); $j++){
          					if (isset($items[$j])) { 
								$vals[$z] .= "'" . esc_sql( $items[$j]) . "'";
							} else { 
								$vals[$z].= "NULL"; 
							}
          					if ($j<(count($items)-1)){ $vals[$z].= ","; }
        				}
        				$vals[$z].= ")"; $z++;
      				}
      				$data['insert'] .= "INSERT INTO `{$table}` VALUES ";      
      				$data['insert'] .= "  ".implode(";\nINSERT INTO `{$table}` VALUES ", $vals).";\n";
    			}
  			}
  			return $data;
		}

		/*
		 * Create the tables with optional table number increment sent for extra databases
		 */
		public function install_shwcp($dbnumber='') {
			global $wpdb;

			// re-assign table names to include $dbnumber if this is a non-default install / addition
			$table_main     = $wpdb->prefix . SHWCP_LEADS  . $dbnumber;
            $table_sst      = $wpdb->prefix . SHWCP_SST    . $dbnumber;
            $table_log      = $wpdb->prefix . SHWCP_LOG    . $dbnumber;
            $table_sort     = $wpdb->prefix . SHWCP_SORT   . $dbnumber;
            $table_notes    = $wpdb->prefix . SHWCP_NOTES  . $dbnumber;
			$table_events   = $wpdb->prefix . SHWCP_EVENTS . $dbnumber;


			$charset_collate = $wpdb->get_charset_collate();

			$main_sql = "CREATE TABLE $table_main ("
  				. "id bigint(20) NOT NULL AUTO_INCREMENT,"
				. "small_image varchar(512) NOT NULL,"
				. "lead_files longtext NOT NULL,"
  				. "creation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,"
				. "updated_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,"
				. "created_by varchar(256) NOT NULL,"
				. "updated_by varchar(256) NOT NULL,"
				. "owned_by varchar(256) NOT NULL,"
				. "l_source bigint(20) NOT NULL,"
				. "l_status bigint(20) NOT NULL,"
				. "l_type bigint(20) NOT NULL,"
				. "first_name varchar(255) NOT NULL,"
				. "last_name varchar(255) NOT NULL,"
  				. " UNIQUE KEY id (id)"
				. " ) $charset_collate;";

			// Source, Status, Type
			$sst_sql = "CREATE TABLE $table_sst ("
				. " sst_id bigint(20) NOT NULL AUTO_INCREMENT,"
				. " sst_name varchar(255) DEFAULT '' NOT NULL,"
				. " sst_type_desc varchar(255) DEFAULT '' NOT NULL,"
				. " sst_type bigint(20) NOT NULL,"
				. " sst_default bigint(20) NOT NULL,"
				. " sst_order bigint(20) NOT NULL,"
				. " UNIQUE KEY id (sst_id)"
				. " ) $charset_collate;";

			// Front end sorting
			$sort_sql = "CREATE TABLE $table_sort ("
				. " orig_name varchar(255) DEFAULT '' NOT NULL,"
				. " translated_name varchar(255) DEFAULT '' NOT NULL,"
				. " sort_number bigint(20) NOT NULL,"
				. " sort_active int(11) NOT NULL,"
				. " sort_ind_number bigint(20) NOT NULL,"
				. " field_type int(11) DEFAULT '1' NOT NULL,"
				. " required_input int(11) DEFAULT '0' NOT NULL"
				. " ) $charset_collate;";


			// Logging
			$log_sql = "CREATE TABLE $table_log ("
				. " log_id bigint(20) NOT NULL AUTO_INCREMENT,"
				. " user_id bigint(20) NOT NULL,"
				. " user_login varchar(255) DEFAULT '' NOT NULL,"
				. " event varchar(255) DEFAULT '' NOT NULL,"
				. " detail varchar(255) DEFAULT '' NOT NULL,"
				. " event_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,"
				. " ip_addr varchar(255) DEFAULT '' NOT NULL,"
				. " UNIQUE KEY id (log_id)"
				. " ) $charset_collate;";

			// Notes
			$notes_sql = "CREATE TABLE $table_notes ("
				. " id bigint(20) NOT NULL AUTO_INCREMENT,"
				. " lead_id bigint(20) NOT NULL,"
				. " note_content longtext NOT NULL,"
				. " creator bigint(20) NOT NULL,"
				. " date_added datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,"
				. " updater bigint(20) NOT NULL,"
				. " date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,"
				. " UNIQUE KEY id (id)"
				. " ) $charset_collate;";

			//Events
			$events_sql = "CREATE TABLE IF NOT EXISTS $table_events ("
  				. " `id` bigint(20) NOT NULL AUTO_INCREMENT,"
  				. " `start` datetime NOT NULL,"
  				. " `end` datetime NOT NULL,"
  				. " `title` varchar(255) CHARACTER SET utf8 NOT NULL,"
  				. " `details` text CHARACTER SET utf8 NOT NULL,"
  				. " `repeat` int(11) NOT NULL,"
  				. " `repeat_every` varchar(255) NOT NULL,"
  				. " `alert_enable` int(11) NOT NULL,"
  				. " `notify_at` datetime NOT NULL,"
  				. " `alert_notify_inc` bigint(20) NOT NULL,"
  				. " `alert_notify_sel` varchar(255) NOT NULL,"
  				. " `alert_time` varchar(255) NOT NULL,"
  				. " `notify_who` varchar(1024) NOT NULL,"
  				. " `notify_email_sent` int(11) NOT NULL,"
  				. " `entry_id` int(11) NOT NULL,"
  				. " `event_color` varchar(255) NOT NULL,"
  				. " `event_creator` bigint(20) NOT NULL,"
				. " UNIQUE KEY id (id)"
				. " ) $charset_collate;";

	
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $main_sql );
			// add version
			add_option( 'table_v', 'SHWCP_TABLE_V');

			dbDelta($sst_sql);
			dbDelta($sort_sql);
			dbDelta($log_sql);
			dbDelta($notes_sql);
			dbDelta($events_sql);
		}

		public function data_shwcp($dbnumber='') {
			global $wpdb;
			// re-assign table names to include $dbnumber if this is a non-default install / addition
			$table_main     = $wpdb->prefix . SHWCP_LEADS  . $dbnumber;
            $table_sst      = $wpdb->prefix . SHWCP_SST    . $dbnumber;
            $table_log      = $wpdb->prefix . SHWCP_LOG    . $dbnumber;
            $table_sort     = $wpdb->prefix . SHWCP_SORT   . $dbnumber;
            $table_notes    = $wpdb->prefix . SHWCP_NOTES  . $dbnumber;
			$table_events   = $wpdb->prefix . SHWCP_EVENTS . $dbnumber;


			$this->get_the_current_user(); // define current_user
			// db insert

			$wpdb->insert( 
			$table_main, 
			array( 
				'creation_date' => current_time( 'mysql' ), 
				'updated_date' => current_time( 'mysql' ),
				'created_by' => $this->current_user->user_login,
				'updated_by' => $this->current_user->user_login,
				'l_source' => 1,
				'l_status' => 2,
				'l_type' => 3,
				'first_name' => 'Bob',
				'last_name' => 'Smithe',
				) 
			);
			$lead_id = $wpdb->insert_id;

			$wpdb->insert(
            $table_main,
            array(
                'creation_date' => current_time( 'mysql' ),
                'updated_date' => current_time( 'mysql' ),
                'created_by' => $this->current_user->user_login,
                'updated_by' => $this->current_user->user_login,
                'l_source' => 1,
                'l_status' => 2,
                'l_type' => 3,
                'first_name' => 'Jim',
                'last_name' => 'Johnson',
                )
            );
            $lead_id = $wpdb->insert_id;

			// source status and type defaults
			$wpdb->insert(
			$table_sst,
			array(
				'sst_name' => 'Default',
				'sst_type_desc' => 'l_source',
				'sst_type' => '1',
				'sst_default' => '1',
				'sst_order' => '1',
				)
			);
			$wpdb->insert(
			$table_sst,
			array(
				'sst_name' => 'Default',
				'sst_type_desc' => 'l_status',
				'sst_type' => '2',
				'sst_default' => '1',
				'sst_order' => '1',

				)
			);
			$wpdb->insert(
			$table_sst,
			array(
				'sst_name' => 'Default',
				'sst_type_desc' => 'l_type',
				'sst_type' => '3',
				'sst_default' => '1',
				'sst_order' => '1',
				)
			);

			/* Sorting and translation table original values */
			$wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'first_name',
                   'translated_name' => 'First Name',
                   'sort_number' => '1',
                   'sort_active' => '1',
                   'sort_ind_number' => '1'
                )
            );
            $wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'last_name',
                   'translated_name' => 'Last Name',
                   'sort_number' => '2',
                   'sort_active' => '1',
                   'sort_ind_number' => '2'
                )
            );
			$wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'created_by',
                   'translated_name' => 'Created By',
                   'sort_number' => '3',
                   'sort_active' => '1',
				   'sort_ind_number' => '3'
                )
            );
            $wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'creation_date',
                   'translated_name' => 'Created',
                   'sort_number' => '4',
                   'sort_active' => '1',
                   'sort_ind_number' => '4'
                )
            );
			$wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'updated_by',
                   'translated_name' => 'Updated By',
                   'sort_number' => '5',
                   'sort_active' => '1',
				   'sort_ind_number' => '5'
                )
            );
			$wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'updated_date',
                   'translated_name' => 'Updated',
                   'sort_number' => '6',
                   'sort_active' => '1',
                   'sort_ind_number' => '6'
                )
            );
			$wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'l_source',
                   'translated_name' => 'Source',
                   'sort_number' => '7',
                   'sort_active' => '1',
				   'sort_ind_number' => '7'
                )
            );
			$wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'l_status',
                   'translated_name' => 'Status',
                   'sort_number' => '8',
                   'sort_active' => '1',
				   'sort_ind_number' => '8'
                )
            );
			$wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'l_type',
                   'translated_name' => 'Type',
                   'sort_number' => '9',
                   'sort_active' => '1',
				   'sort_ind_number' => '9'
                )
            );
			$wpdb->insert(
				$table_sort,
				array(
					'orig_name' => 'owned_by',
					'translated_name' => 'Owned By',
					'sort_number' => '10',
					'sort_active' => '1',
					'sort_ind_number' => '10'
				)
			);
			$wpdb->insert(
                $table_sort,
                array(
                   'orig_name' => 'id',
                   'translated_name' => 'ID',
                   'sort_number' => '11',
                   'sort_active' => '0',
                   'sort_ind_number' => '11'
                )
            );


		}

	} // end class
