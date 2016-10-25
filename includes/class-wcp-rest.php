<?php
/**
 * WP API Integration for RESTful access to the database
 */

    class wcp_rest {
        // properties


        // methods

        /**
         * 
         * @access public
         * @since 2.0.8
        **/
		public function __construct() {
			add_action( 'rest_api_init', array($this, 'wcp_register_api_hooks') );

		}

		/**
		 * Register hooks
		 * @access public
		 * @since 2.0.8
		 **/
		public function wcp_register_api_hooks() {
			$namespace = 'shwcp/v1';

			// /wp-json/shwcp/v1/get-contacts/
			register_rest_route( $namespace, '/get-contacts/', array(
				'methods' => 'GET',
				'callback' => array(
					$this,
					'shwcp_get_contacts',
				)
			) );

			// /wp-json/shwcp/v1/get-contact/
			register_rest_route( $namespace, '/get-contact/', array(
				'methods' => 'GET',
				'callback' => array(
					$this,
					'shwcp_get_contact'
				)
			) );
		}


		/**
		 * Route Callbacks all Contacts
		 */
		public function shwcp_get_contacts($data) {
			$return = array(
				'testing' => 'yes',
				'list contacts' => 'yes'
			);

			$response = new WP_REST_Response( $return );
			$response->header( 'Access-Control-Allow-Origin', apply_filters( 'gwcp_access_control_allow_origin','*' ) );
    		return $response;
		}

		/**
         * Route Callbacks Single Contact
         */
        public function shwcp_get_contact($data) {
            $return = array(
                'testing' => 'yes',
                'list single contact' => 'yes'
            );
        
            $response = new WP_REST_Response( $return );
            $response->header( 'Access-Control-Allow-Origin', apply_filters( 'wcp_access_control_allow_origin','*' ) );
            return $response;
        }
	}
