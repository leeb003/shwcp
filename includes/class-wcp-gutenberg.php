<?php
/**
 * Gutenberg functionality
 */

function myprefix_enqueue_assets() {
	$main_wcp = new main_wcp;
	$location = 'gutenberg'; // where we are using so we can format correctly
	$wcp_databases = $main_wcp->wcp_getdbs($location);
    wp_enqueue_script(
        'shwcp-pagedb-script', SHWCP_ROOT_URL . '/assets/js/guten-build/index.js',
        //plugins_url( 'build/index.js', __FILE__ ),
        array( 'wp-blocks','wp-plugins', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-components', 'wp-data', 'wp-compose', 'wp-api-fetch' )
    );
	// get an array of current databases to pass to database selection on page from main_wcp
	wp_localize_script('shwcp-pagedb-script', 'wcp_databases', $wcp_databases);
	//print_r($wcp_databases);
}
add_action( 'enqueue_block_editor_assets', 'myprefix_enqueue_assets' );

/**
 * Add Gutenberg js translations to WP
 */
function shwcp_pagedb_translations() {
	    wp_set_script_translations( 'shwcp-pagedb-script', 'shwcp' );
}
add_action( 'init', 'shwcp_pagedb_translations' );

/**
 * Register DB Select (wcp_db_select) Meta Field to Rest API
 */
function shwcp_pagedb_register_meta() {
	register_meta(
		'post', 'wcp_db_select', array(
			'type'		=> 'string',
			'single'	=> true,
			'show_in_rest'	=> true,
		)
	);
}
add_action( 'init', 'shwcp_pagedb_register_meta' );

/**
 * Register DB Select Metabox to Rest API
 */
function shwcp_pagedb_api_posts_meta_field() {
	register_rest_route(
		'shwcpdb/v1', '/update-meta', array(
			'methods'  => 'POST',
			'callback' => 'shwcp_pagedb_update_callback',
			'args'	 => array(
				'id' => array(
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'shwcp_pagedb_api_posts_meta_field' );

/**
 * SHWCP Page DB REST API Callback for Gutenberg
 */
function shwcp_pagedb_update_callback( $data ) {
	return update_post_meta( $data['id'], $data['key'], $data['value'] );
}
