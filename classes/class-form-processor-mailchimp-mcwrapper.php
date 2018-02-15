<?php
/**
 * Class file for the Form_Processor_MailChimp_MCWrapper class.
 *
 * @file
 */

if ( ! class_exists( 'Form_Processor_MailChimp' ) ) {
	die();
}

use \DrewM\MailChimp\MailChimp;
/**
 * Mailchimp wrapper
 */
class Form_Processor_MailChimp_MCWrapper {

	protected $option_prefix;
	protected $version;
	protected $slug;
	protected $wordpress;

	public $api_key;

	/**
	* Constructor which sets up admin pages
	*
	* @param string $option_prefix
	* @param string $version
	* @param string $slug
	* @param object $wordpress
	* @throws \Exception
	*/
	public function __construct( $option_prefix, $version, $slug, $wordpress ) {
		$this->option_prefix = $option_prefix;
		$this->version = $version;
		$this->slug = $slug;
		$this->wordpress = $wordpress;

		$this->api_key = get_option( $this->option_prefix . 'mailchimp_api_key', '' );

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
		$resource_type = isset( $params['resource_type'] ) ? $params['resource_type'] : '';
		$resource = isset( $params['resource'] ) ? $params['resource'] : '';
		$subresource_type = isset( $params['subresource_type'] ) ? $params['subresource_type'] : '';
		$subresource = isset( $params['subresource'] ) ? $params['subresource'] : '';
		$method = isset( $params['method'] ) ? $params['method'] : '';
		$cached = $this->wordpress->cache_get( $call, $reset );
		if ( is_array( $cached ) ) {
			$data = $cached;
		} else {
			$data = $this->mailchimp_api->get( $call );
			$cached = $this->wordpress->cache_set( $call, $data );
		}

		if ( '' !== $method ) {
			$allowed_items = get_option( $this->option_prefix . 'items_' . $resource . '_' . $subresource_type . '_' . $subresource . '_' . $method, false )[ $resource_type ][ $resource ][ $subresource_type ];
			foreach ( $data[ $method ] as $key => $item ) {
				if ( ! in_array( $item['id'], $allowed_items ) ) {
					unset( $data[ $method ][ $key ] );
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
		if ( 'PUT' === $method && isset( $params['email_address'] ) ) {
			$call = $call . '/' . md5( $params['email_address'] );
		}

		if ( 'POST' === $method ) {
			$check_call = $call . '/' . md5( $params['email_address'] );
			$check_user = $this->load( $check_call, $params );
			if ( isset( $check_user['id'] ) ) {
				$call = $check_call;
				$params['status'] = $check_user['status'];
				$method = 'PUT';
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

		$result = $this->mailchimp_api->{ $method }( $call, $params );

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
