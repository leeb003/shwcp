<?php
/**
 * WCP Class for adding metaboxes to our custom template for database selection
 */

    class wcp_metabox extends main_wcp {
        // properties

        // methods
		public function gen_metaboxes() {
			// Metabox for database selection
        	add_action("add_meta_boxes_page", array(&$this, "db_selection"));
			// Save the post data
        	add_action( 'save_post', array(&$this, 'save_postdata') );
		}

		public function db_selection() {
			add_meta_box(
				"wcp_db_options", 
				__( 'WP Contacts Database Selection', 'shwcp' ),
            	array(&$this, "wcp_custom_box"), 
				"page", 
				"side", 
				"core" 
			);
    	}
			

		public function wcp_custom_box() {
			global $wpdb;
			$options_table = $wpdb->prefix . 'options';
			$option_entry = 'shwcp_main_settings';
			$dbs = $wpdb->get_results("SELECT * FROM $options_table WHERE `option_name` LIKE '%$option_entry%'");
			$databases = array();
        	foreach ($dbs as $k => $option) {
            	if ($option->option_name == $option_entry) {
					$db_options = get_option($option->option_name);
					if (!isset($db_options['database_name'])) {
						$database_name = __('Default', 'shwcp');
					} else {
						$database_name = $db_options['database_name'];
					}
					$databases['default'] = $database_name;
            	} else {
                	$db_options = get_option($option->option_name);
                	$remove_name = '/^' . $option_entry . '_/';  // Just get the database number
                	$db_number = preg_replace($remove_name, '', $option->option_name);
					$database_name = $db_options['database_name'];
					$databases[$db_number] = $database_name;
            	}
        	}
			
			//print_r($databases);
			//print_r($dbs);

        	global $post;
        	$database = get_post_meta( $post->ID, 'wcp_db_select', true);
			if (!$database) {
				$database = 'default';
			}

			$selected = 'selected="selected"';
			$option_text = __('Select Database To Associate', 'shwcp');
			$option_display = <<<EOT
				<div id="wcp-database-select">
				  <table class="meta-table">
					<tr>
						<td>$option_text</td>
						<td><select class="wcp_db_select" name="wcp_db_select">
EOT;
			foreach ($databases as $k => $v) {
				$selected = '';
				if ($k == $database) {
					$selected = 'selected="selected"';
				}
				$option_display .= <<<EOT
						<option value="$k" $selected>$v</option>
EOT;
			}
			$option_display .= <<<EOT
					  </select></td>
				    </tr>
				  </table>
				</div>
EOT;
			echo $option_display;
		}


		/** 
     	 * Save custom post data 
     	 */
    	public function save_postdata( $post_id ) {
        	global $post;
        	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            	return $post_id;
        	}
        	if ( isset($_POST['post_type']) ) {
				if ($_POST['post_type'] == 'page') {
					$wcp_db_select = isset($_POST['wcp_db_select']) ? sanitize_text_field($_POST['wcp_db_select']) : 'default';
                	update_post_meta( $post_id, 'wcp_db_select', $wcp_db_select);
				}
			}
		}



	} // end class
