<?php

defined( 'ABSPATH' ) or die();

/**
 * Gravity Forms Mailgun API Library.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GF_Mailgun_API {

    /**
     * Mailgun API Key.
     *
     * @since  1.0
     * @access protected
     * @var    string $api_key Mailgun API KEY.
     */
    protected $api_key;

	/**
	 * Mailgun API URL.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $api_url Mailgun API URL.
	 */
	protected $api_url = 'https://api.mailgun.net/v3/';

	/**
	 * Mailgun European API URL.
	 *
	 * @since  1.1
	 * @access protected
	 * @var    string $api_url_eu Mailgun European API URL.
	 */
	protected $api_url_eu = 'https://api.eu.mailgun.net/v3/';

	/**
	 * Mailgun API region.
	 *
	 * @since  1.1
	 * @access protected
	 * @var    string $region Mailgun API region.
	 */
	protected $region;

	/**
	 * Initialize Mailgun API library.
	 *
	 * @since  1.0
	 * @since  1.1 API region.
	 *
	 * @param string $api_key Mailgun API key.
	 * @param string $region  Mailgun API region.
	 */
	public function __construct( $api_key, $region = 'us' ) {

		$this->api_key = $api_key;
		$this->region  = $region;

	}

	/**
	 * Get a single domain, including credentials and DNS records.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $domain Domain name.
	 *
	 * @return array|WP_Error
	 */
	public function get_domain( $domain ) {

		return $this->make_request( 'domains/' . $domain );

	}

	/**
	 * Get a list of domains under a Mailgun account.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array|WP_Error
	 */
	public function get_domains() {

		return $this->make_request( 'domains' );

	}

	/**
	 * Send an email.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $domain  Domain to send email from.
	 * @param array  $message Email contents.
	 *
	 * @uses   GF_Mailgun_API::get_api_url()
	 *
	 * @return array|WP_Error
	 */
	public function send_email( $domain, $message ) {

		// If this message does not have any attachments, run a standard API request.
		if ( ! isset( $message['attachment'] ) ) {

			// Log that we are using default API request method.
			gf_mailgun()->log_debug( __METHOD__ . '(): This message does not have any attachments. Using default API request method.' );

			// Send email.
			return $this->make_request( $domain . '/messages', $message, 'POST' );

		}

		// Extract attachments from message.
		$attachments = $message['attachment'];

		// Remove attachments from email contents.
		unset( $message['attachment'] );

		// Generate form data boundary key.
		$boundary = wp_generate_password( 24 );

		// Initialize post data string.
		$post_data = '';

		// Loop through email contents.
		foreach ( $message as $key => $value ) {

			// Add email data to post data string.
			$post_data .= '--' . $boundary . "\r\n";
			$post_data .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
			$post_data .= $value . "\r\n";

		}

		// Loop through attachments.
		foreach ( $attachments as $attachment ) {

			// Add attachment to post data string.
			$post_data .= '--' . $boundary . "\r\n";
			$post_data .= 'Content-Disposition: form-data; name="attachment"; filename="' . $attachment['name'] . '"' . "\r\n\r\n";
			$post_data .= file_get_contents( $attachment['path'] ) . "\r\n";

		}

		// Finalize post data string.
		$post_data .= '--' . $boundary . '--';

		// Build request URL.
		$request_url = $this->get_api_url() . $domain . '/messages';

		// Build request arguments.
		$request_args = array(
			'body'    => $post_data,
			'method'  => 'POST',
			'headers' => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( 'api:' . $this->api_key ),
				'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
			),
		);

		// Execute API request.
		$response = wp_remote_request( $request_url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== $response['response']['code'] ) {
			return new WP_Error( $response['response']['code'], $response['response']['message'] );
		}

		// Convert JSON response to array.
		return json_decode( $response['body'], true );

	}

	/**
	 * Make API request.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @param string $action        Request action.
	 * @param array  $options       Request options.
	 * @param string $method        HTTP method. Defaults to GET.
	 * @param string $return_key    Array key from response to return. Defaults to null (return full response).
	 * @param int    $response_code Expected HTTP response code. Defaults to 200.
	 *
	 * @uses   GF_Mailgun_API::get_api_url()
	 *
	 * @return array|string|WP_Error
	 */
	private function make_request( $action, $options = array(), $method = 'GET', $return_key = null, $response_code = 200 ) {

		// Build request options string.
		$request_options = 'GET' === $method ? '?' . http_build_query( $options ) : null;

		// Build request URL.
		$request_url = $this->get_api_url() . $action . $request_options;

		// Build request arguments.
		$request_args = array(
			'body'      => 'GET' != $method ? $options : '',
			'method'    => $method,
			'sslverify' => false,
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( 'api:' . $this->api_key ),
			),
		);

		// Execute API request.
		$response = wp_remote_request( $request_url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response['response']['code'] !== $response_code ) {
			return new WP_Error( $response['response']['code'], $response['response']['message'] );
		}

		// Convert JSON response to array.
		$response = json_decode( $response['body'], true );

		// If a return key is defined and array item exists, return it.
		if ( ! empty( $return_key ) && isset( $response[ $return_key ] ) ) {
			return $response[ $return_key ];
		}

		return $response;

	}

	/**
	 * Get base Mailgun API URL.
	 *
	 * @since  1.1
	 * @access private
	 *
	 * @return string
	 */
	private function get_api_url() {

		return 'eu' === $this->region ? $this->api_url_eu : $this->api_url;

	}

}
