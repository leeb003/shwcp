<?php

//namespace DrewM\MailChimp; not using for older php support

/**
 * Original from https://github.com/drewm/mailchimp-api
 * Modified to support wp_remote_request instead of curl lb 1-12-2016
 *
 * Super-simple, minimum abstraction MailChimp API v3 wrapper
 *
 * Uses curl if available, falls back to file_get_contents and HTTP stream.
 * This probably has more comments than code.
 *
 * @author Drew McLellan <drew.mclellan@gmail.com>
 * @version 2.0.5
 */
class MailChimp
{
    private $api_key;
    private $api_endpoint = 'https://<dc>.api.mailchimp.com/3.0';
    
    /*  SSL Verification
        Read before disabling: 
        http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/
    */
    public  $verify_ssl   = true; 

    /**
     * Create a new instance
     * @param string $api_key Your MailChimp API key
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        list(, $datacentre) = explode('-', $this->api_key);
        $this->api_endpoint = str_replace('<dc>', $datacentre, $this->api_endpoint);
    }
    
    public function delete($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('delete', $method, $args, $timeout);
    }

    public function get($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('get', $method, $args, $timeout);
    }

    public function patch($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('patch', $method, $args, $timeout);
    }

    public function post($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    public function put($method, $args=array(), $timeout=10)
    {
        return $this->makeRequest('put', $method, $args, $timeout);
    }

    /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $$http_verb   The HTTP verb to use: get, post, put, patch, delete
     * @param  string $method       The API method to be called
     * @param  array  $args         Assoc array of parameters to be passed
     * @return array                Assoc array of decoded result
     */
    private function makeRequest($http_verb, $method, $args=array(), $timeout=10)
    {
        $url = $this->api_endpoint.'/'.$method;

        $json_data = json_encode($args, JSON_FORCE_OBJECT);

		/* WP Remote Request Method */
		$args = array(
			'timeout' => $timeout,
			'headers' => array(
				'accept'        => 'application/vnd.api+json',
				'content-type'  => 'application/vnd.api+json',
				'authorization' => 'apikey ' . $this->api_key,
				'sslverify'     => $this->verify_ssl,
			),
		);

		switch($http_verb) {
            case 'post':
				$args['method'] = 'POST';
				$args['body'] = $json_data;
                break;

            case 'get':
				$args['method'] = 'GET';
                $query = http_build_query($args);
				$url = $url.'?'.$query;
                break;

            case 'delete':
				$args['method'] = 'DELETE';
                break;

            case 'patch':
				$args['method'] = 'PATCH';
				$args['body'] = $json_data;
                break;

            case 'put':
				$args['method'] = 'PUT';
				$args['body'] = $json_data;
                break;
        }
		
        $response = wp_remote_request($url, $args);


		/* End WP Remote Request Method */

		$result = wp_remote_retrieve_body($response);
		return $result ? json_decode($result, true) : false;
    }
}
