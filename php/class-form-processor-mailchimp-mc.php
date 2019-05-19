<?php

/**
 * MailChimp feature/API wrapper
 *
 * @package Form_Processor_Mailchimp
 */

use \DrewM\MailChimp\MailChimp;

class Form_Processor_Mailchimp_MC {

	public $option_prefix;
	public $version;
	public $slug;
	public $wordpress;

	public $api_key;

	public function __construct() {

		$this->option_prefix = form_processor_mailchimp()->option_prefix;
		$this->version       = form_processor_mailchimp()->version;
		$this->slug          = form_processor_mailchimp()->slug;
		$this->wordpress     = form_processor_mailchimp()->wordpress;
		$this->logging       = form_processor_mailchimp()->logging;

		$this->api_key       = defined( 'FORM_PROCESSOR_MC_MAILCHIMP_API_KEY' ) ? FORM_PROCESSOR_MC_MAILCHIMP_API_KEY : get_option( $this->option_prefix . 'mailchimp_api_key', '' );
		$this->mailchimp_api = $this->mailchimp_api();
	}

	/**
	* Load the MailChimp API from composer
	*
	* @return object $mailchimp_api
	*/
	public function mailchimp_api() {
		if ( ! class_exists( 'DrewM/Mailchimp/MailChimp' ) ) {
			require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';
		}
		$mailchimp_key = $this->api_key;
		if ( '' !== $mailchimp_key ) {
			$mailchimp_api = new MailChimp( $mailchimp_key );
			return $mailchimp_api;
		} else {
			return '';
		}
	}

	/**
	* Run a GET request on API
	*
	* @param string $call
	* @param array $params
	* @param bool $reset
	* @return array $data
	*/
	public function load( $call = '', $params = array(), $reset = false ) {
		$resource_type    = isset( $params['resource_type'] ) ? $params['resource_type'] : '';
		$resource         = isset( $params['resource'] ) ? $params['resource'] : '';
		$subresource_type = isset( $params['subresource_type'] ) ? $params['subresource_type'] : '';
		$subresource      = isset( $params['subresource'] ) ? $params['subresource'] : '';
		$method           = isset( $params['method'] ) ? $params['method'] : '';
		$cached           = $this->wordpress->cache_get( $call, $reset );
		if ( is_array( $cached ) ) {
			$data = $cached;
		} else {
			$data   = $this->mailchimp_api->get( $call );
			$cached = $this->wordpress->cache_set( $call, $data );
			// create log entry if there is an error
			if ( isset( $data['status'] ) ) {
				$log_status = $data['status'];
				$log_title  = sprintf(
					// translators: placeholders are: 1) the server error code
					esc_html__( 'MailChimp: %1$s on Load request', 'form-processor-mailchimp' ),
					absint( $log_status )
				);

				$log_message = sprintf(
					// translators: placeholders are: 1) api call, 2) params array, 3) reset flag, 4) api result
					esc_html__( 'Load call was %1$s, params array is %2$s, reset is %3$s, result is %4$s.', 'form-processor-mailchimp' ),
					esc_html( $call ),
					print_r( $params, true ),
					esc_attr( $reset ),
					print_r( $data, true ),
					is_array( $cached )
				);

				$log_entry = array(
					'title'   => $log_title,
					'message' => $log_message,
					'trigger' => 0,
					'parent'  => '',
					'status'  => $log_status,
				);

				$this->logging->setup( $log_entry );
			}
		}

		if ( '' !== $method ) {
			$allowed_items = get_option( $this->option_prefix . 'items_' . $resource . '_' . $subresource_type . '_' . $subresource . '_' . $method, false )[ $resource_type ][ $resource ][ $subresource_type ];
			if ( is_array( $data[ $method ] ) ) {
				foreach ( $data[ $method ] as $key => $item ) {
					if ( is_array( $allowed_items ) && ! in_array( $item['id'], $allowed_items ) ) {
						unset( $data[ $method ][ $key ] );
					}
				}
			}
		}

		return $data;
	}

	/**
	* Run a POST or PUT request on API
	*
	* @param string $call
	* @param string $method
	* @param array $params
	* @return array $result
	*/
	public function send( $call = '', $method = 'POST', $params = array() ) {
		$result = '';
		// this is not flexible enough for broad use, but it does work for the member update
		// email needs to be lowercase before being hashed
		// see: https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
		/*
		In previous versions of the API, we exposed internal database IDs eid and leid for emails and list/email combinations. In API 3.0, we no longer use or expose either of these IDs. Instead, we identify your subscribers by the MD5 hash of the lowercase version of their email address so you can easily predict the API URL of a subscriber’s data.
		*/
		if ( 'PUT' === $method && isset( $params['email_address'] ) ) {
			$call = $call . '/' . md5( strtolower( $params['email_address'] ) );
		}

		if ( 'POST' === $method ) {
			$check_call = $call . '/' . md5( strtolower( $params['email_address'] ) );
			$check_user = $this->load( $check_call, $params, true ); // if we are checking for real, it should skip the cache
			if ( isset( $check_user['id'] ) ) {
				$call             = $check_call;
				$params['status'] = $check_user['status'];
				$method           = 'PUT';
			}
		}

		foreach ( $params as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				foreach ( $value as $subkey => $subvalue ) {
					if ( 'true' === $subvalue || 'false' === $subvalue ) {
						$subvalue = filter_var( $subvalue, FILTER_VALIDATE_BOOLEAN ); // try to force a boolean in case it is a string
						/*if ( false === $subvalue ) {
							$subvalue = '';
						}*/
					} else {
						$subvalue = strip_tags( stripslashes( $subvalue ) );
					}
					$value[ $subkey ] = $subvalue;
				}
				$params[ $key ] = (object) $value;
			} else {
				if ( 'true' === $value || 'false' === $value ) {
					$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN ); // try to force a boolean in case it is a string
				} else {
					$value = strip_tags( stripslashes( $value ) );
				}
				$params[ $key ] = $value;
			}
		}

		$result           = $this->mailchimp_api->{ $method }( $call, $params );
		$result['method'] = $method;

		// create log entry if there is an error
		if ( isset( $result['status'] ) ) {
			$log_status = $result['status'];
			$log_title  = sprintf(
				// translators: placeholders are: 1) the server error code, 2) what method was called
				esc_html__( 'MailChimp: %1$s on %2$s send request', 'form-processor-mailchimp' ),
				absint( $log_status )
			);

			$log_message = sprintf(
				// translators: placeholders are: 1) api call, 2) method, 3) params array, 4) reset flag, 5) api result
				esc_html__( 'Send call was %1$s, method was %2$s, params array is %3$s, reset is %4$s, result is %5$s.', 'form-processor-mailchimp' ),
				esc_html( $call ),
				esc_attr( $method ),
				print_r( $params, true ),
				esc_attr( $reset ),
				print_r( $result, true )
			);

			$log_entry = array(
				'title'   => $log_title,
				'message' => $log_message,
				'trigger' => 0,
				'parent'  => '',
				'status'  => $log_status,
			);

			$this->logging->setup( $log_entry );
		}

		return $result;
	}

	/**
	* Run a DELETE request on API
	*
	* @param string $call
	* @param string $id
	* @return array $result
	*/
	public function remove( $call, $id ) {
		$result = '';
		return $result;
	}

	/**
	* If we only have an object ID, get its name
	*
	* @param string $type
	* @param string $id
	* @return string $result
	*/
	public function get_name( $type, $id, $subtype = '', $subid = '' ) {
		if ( '' !== $subtype ) {
			$subtype = '/' . $subtype;
		}
		if ( '' !== $subid ) {
			$subid = '/' . $subid;
		}
		$result = $this->load( $type . '/' . $id . $subtype . $subid );
		if ( isset( $result['name'] ) ) {
			return $result['name'];
		} elseif ( isset( $result['title'] ) ) {
			return $result['title'];
		}
	}
}
