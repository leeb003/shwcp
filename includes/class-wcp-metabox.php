<?php
/**
 * WCP Class for adding metaboxes to our custom template for database selection
 * Note: This is the pre-gutenberg method for database selection
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
				"core",
				array('__back_compat_meta_box' => true)
			);
    	}

		public function wcp_custom_box() {
			global $post;
			$databases = $this->wcp_getdbs();  // get databases from main public function wcp_getdbs
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
