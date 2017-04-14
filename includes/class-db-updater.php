<?php
/*
 * The database updater class
 * updates tables with new columns and settings for a new version
 * only runs on check db version will be updated afterwards
 */
class db_updater {
	// properties


	// methods
	public function __construct() {
	
	}

	/*
     * Check and update tables that need updating
     */
	public function run_checks() {
		global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

		/* Sort Table Changes */
        $sort_table_check = $wpdb->prefix . SHWCP_SORT;
		// get all tables to check
		$sort_tables = $wpdb->get_results("show tables like '" . $sort_table_check . "%'", ARRAY_N);
		if (!empty($sort_tables)) {
			foreach ($sort_tables as $k => $sort_table_array) {
				$sort_table = $sort_table_array[0];

            	$field_type_exists = $wpdb->get_var(
                	"
                	SELECT `COLUMN_NAME`
                	FROM `INFORMATION_SCHEMA`.`COLUMNS`
                	WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                	AND `TABLE_NAME`='$sort_table'
                	AND `COLUMN_NAME`='field_type'
                	"
            	);
            	if (!$field_type_exists) {   // If field_type is not present add it - since 1.2.1
                	$wpdb->query(
                    	"
                    	ALTER TABLE $sort_table add column field_type int(11) NOT NULL DEFAULT '1'
                    	"
                	);
            	}
            	$required_exists = $wpdb->get_var(
                	"
                	SELECT `COLUMN_NAME`
                	FROM `INFORMATION_SCHEMA`.`COLUMNS`
                	WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                	AND `TABLE_NAME`='$sort_table'
                	AND `COLUMN_NAME`='required_input'
                	"
            	);
            	if (!$required_exists) {   // If required is not present add it - since 2.0.1
                	$wpdb->query(
                    	"
                    	ALTER TABLE $sort_table add column required_input int(11) NOT NULL DEFAULT '0'
                    	"
                	);
            	}
				/******************************** 3.0.5 *******************************/
				$front_filter_exists = $wpdb->get_var(
					"
                    SELECT `COLUMN_NAME`
                    FROM `INFORMATION_SCHEMA`.`COLUMNS`
                    WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                    AND `TABLE_NAME`='$sort_table'
                    AND `COLUMN_NAME`='front_filter_active'
                    "
                );
				if (!$front_filter_exists) { // front_filter_active is not present add it - since 3.0.5
					$wpdb->query("ALTER TABLE $sort_table add column front_filter_active int(11) NOT NULL DEFAULT '0'");
				}
				$front_filter_sort_exists = $wpdb->get_var(
                    "
                    SELECT `COLUMN_NAME`
                    FROM `INFORMATION_SCHEMA`.`COLUMNS`
                    WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                    AND `TABLE_NAME`='$sort_table'
                    AND `COLUMN_NAME`='front_filter_sort'
                    "
                );
                if (!$front_filter_sort_exists) { // front_filter_sort is not present add it - since 3.0.5
                    $wpdb->query("ALTER TABLE $sort_table add column front_filter_sort int(11) NOT NULL DEFAULT '0'");
					// and set the defaults shown for source, status and type (if they weren't set up previously)
				
					$wpdb->update(
                    	$sort_table,
                    	array( 'front_filter_active' => 1, 'front_filter_sort' => 1 ),
                    	array( 'orig_name' => 'l_source' ),
                    	array( '%d', '%d' ),
                    	array( '%s' )
                	);
					$wpdb->update(
                    	$sort_table,
                    	array( 'front_filter_active' => 1, 'front_filter_sort' => 2 ),
                    	array( 'orig_name' => 'l_status' ),
                    	array( '%d', '%d' ),
                    	array( '%s' )
                	);
					$wpdb->update(
                    	$sort_table,
                    	array( 'front_filter_active' => 1, 'front_filter_sort' => 3 ),
                    	array( 'orig_name' => 'l_type' ),
                    	array( '%d', '%d' ),
                    	array( '%s' )
               		);
				}

				/**************** 3.0.6 ***************/
                // Modifying source, status and type to dropdowns
                $wpdb->update(
                    $sort_table,
                    array( 'field_type' => 10 ),
                    array( 'orig_name' => 'l_source' ),
                    array( '%d' ),
                    array( '%s' )
                );
                $wpdb->update(
                    $sort_table,
                    array( 'field_type' => 10 ),
                    array( 'orig_name' => 'l_status' ),
                    array( '%d' ),
                    array( '%s' )
                );
                $wpdb->update(
                    $sort_table,
                    array( 'field_type' => 10 ),
                    array( 'orig_name' => 'l_type' ),
                    array( '%d' ),
                    array( '%s' )
                );

        	}	
		}	/* End Sort Table Changes */

		
		/* Notes Table Changes */

		/* Add date_updated and updater to notes - since 2.0.0 we added note editing */
        $notes_table_check = $wpdb->prefix . SHWCP_NOTES;
		$notes_tables = $wpdb->get_results("show tables like '" . $notes_table_check . "%'", ARRAY_N);
        if (!empty($notes_tables)) {
            foreach ($notes_tables as $k => $notes_table_array) {
                $notes_table = $notes_table_array[0];
            	// check if date_updated  exists
            	$date_updated_exists = $wpdb->get_var(
                	"
                	SELECT `COLUMN_NAME`
                	FROM `INFORMATION_SCHEMA`.`COLUMNS`
                	WHERE `TABLE_SCHEMA`='$wpdb->dbname'
                	AND `TABLE_NAME`='$notes_table'
                	AND `COLUMN_NAME`='date_updated'
                	"
            	);
            	if (!$date_updated_exists) { // if date_updated does not exist, add it and updater - since 2.0.0
                	$wpdb->query(
                    	"
                    	ALTER TABLE $notes_table add column updater bigint(20) NOT NULL
                    	"
                	);
                	$wpdb->query(
                    	"
                    	ALTER TABLE $notes_table add column date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL
                    	"
                	);
            	}
			}
        }    /* End Notes Table Changes */

		/* Add events table since 2.0.0 for events, already installed versions won't have it 
		 * We don't need to check multiple tables for this one since 2.0.0 introduced multiple databases
		 * and previous versions will only have the default database
		 */
        $events_table = $wpdb->prefix . SHWCP_EVENTS;
        // check if table exists
        $events_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$events_table'");
        if (!$events_table_exists) {
            $events_sql = "CREATE TABLE IF NOT EXISTS $events_table ("
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
            dbDelta($events_sql);

        }		

	}

} // end class
