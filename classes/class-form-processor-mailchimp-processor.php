<?php
/**
 * Class file for the Form_Processor_MailChimp_Processor class.
 *
 * @file
 */

if ( ! class_exists( 'Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Form processor
 */
class Form_Processor_MailChimp_Processor {

	protected $option_prefix;
	protected $version;
	protected $slug;
	protected $namespace;
	protected $api_version;
	protected $wordpress;
	protected $mailchimp;

	/**
	* Constructor which sets up admin pages
	*
	* @param string $option_prefix
	* @param string $version
	* @param string $slug
	* @param string $namespace
	* @param string $api_version
	* @param object $wordpress
	* @param object $mailchimp
	* @throws \Exception
	*/
	public function __construct( $option_prefix, $version, $slug, $namespace, $api_version, $wordpress, $mailchimp ) {
		$this->option_prefix = $option_prefix;
		$this->version = $version;
		$this->slug = $slug;
		$this->namespace = $namespace;
		$this->api_version = $api_version;
		$this->wordpress = $wordpress;
		$this->mailchimp = $mailchimp;

		$this->init();
	}

	/**
	* Initialize REST API routes
	*
	* @throws \Exception
	*/
	private function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}


	/**
	* Register REST API routes for the configured MailChimp objects
	*
	* @throws \Exception
	*/
	public function register_routes() {
		$namespace = $this->namespace . $this->api_version;
		$method_list = get_option( $this->option_prefix . 'http_methods', '' );

		register_rest_route( $namespace, '/(?P<resource_type>([\w-])+)/', array(
			array(
				'methods' => $method_list,
				'callback' => array( $this, 'process' ),
				'args' => array(
					'resource_type' => array(
						'validate_callback' => array( $this, 'check_resource_type' ),
					),
				),
				'permission_callback' => array( $this, 'can_process' ),
			),
		) );

		register_rest_route( $namespace, '/(?P<resource_type>([\w-])+)/' . '(?P<resource>([\w-])+)/', array(
			array(
				'methods' => $method_list,
				'callback' => array( $this, 'process' ),
				'args' => array(
					'resource_type' => array(
						'validate_callback' => array( $this, 'check_resource_type' ),
					),
					'resource' => array(
						'validate_callback' => array( $this, 'check_resource' ),
					),
				),
				'permission_callback' => array( $this, 'can_process' ),
			),
		) );

		register_rest_route( $namespace, '/(?P<resource_type>([\w-])+)/' . '(?P<resource>([\w-])+)/' . '(?P<subresource_type>([\w-])+)/', array(
			array(
				'methods' => $method_list,
				'callback' => array( $this, 'process' ),
				'args' => array(
					'resource_type' => array(
						'validate_callback' => array( $this, 'check_resource_type' ),
					),
					'resource' => array(
						'validate_callback' => array( $this, 'check_resource' ),
					),
					'subresource_type' => array(
						'validate_callback' => array( $this, 'check_subresource_type' ),
					),
				),
				'permission_callback' => array( $this, 'can_process' ),
			),
		) );

		register_rest_route( $namespace, '/(?P<resource_type>([\w-])+)/' . '(?P<resource>([\w-])+)/' . '(?P<subresource_type>([\w-])+)/' . '(?P<subresource>([\w-])+)/', array(
			array(
				'methods' => $method_list,
				'callback' => array( $this, 'process' ),
				'args' => array(
					'resource_type' => array(
						'validate_callback' => array( $this, 'check_resource_type' ),
					),
					'resource' => array(
						'validate_callback' => array( $this, 'check_resource' ),
					),
					'subresource_type' => array(
						'validate_callback' => array( $this, 'check_subresource_type' ),
					),
					'subresource' => array(
						'validate_callback' => array( $this, 'check_subresource' ),
					),
				),
				'permission_callback' => array( $this, 'can_process' ),
			),
		) );

		register_rest_route( $namespace, '/(?P<resource_type>([\w-])+)/' . '(?P<resource>([\w-])+)/' . '(?P<subresource_type>([\w-])+)/' . '(?P<subresource>([\w-])+)/' . '(?P<method>([\w-])+)/', array(
			array(
				'methods' => $method_list,
				'callback' => array( $this, 'process' ),
				'args' => array(
					'resource_type' => array(
						'validate_callback' => array( $this, 'check_resource_type' ),
					),
					'resource' => array(
						'validate_callback' => array( $this, 'check_resource' ),
					),
					'subresource_type' => array(
						'validate_callback' => array( $this, 'check_subresource_type' ),
					),
					'subresource' => array(
						'validate_callback' => array( $this, 'check_subresource' ),
					),
					'method' => array(
						'validate_callback' => array( $this, 'check_method' ),
					),
				),
				'permission_callback' => array( $this, 'can_process' ),
			),
		) );
	}

	/**
	* Check for a valid resource type
	*
	* @param string $resource_type
	* @param object $request
	* @return $result
	*/
	public function check_resource_type( $resource_type, $request ) {
		if ( isset( $resource_type ) ) {
			$allowed_resource_types = get_option( $this->option_prefix . 'resource_types', array() );
			if ( in_array( $resource_type, $allowed_resource_types ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	* Check for a valid resource
	*
	* @param string $resource
	* @param object $request
	* @return $result
	*/
	public function check_resource( $resource, $request ) {
		$url_params = $request->get_url_params();
		$resource_type = $url_params['resource_type'];
		$resources = get_option( $this->option_prefix . 'resources_' . $resource_type, array() );
		if ( isset( $resources[ $resource_type ] ) ) {
			if ( in_array( $resource, $resources[ $resource_type ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	* Check for a valid subresource type
	*
	* @param string $subresource_type
	* @param object $request
	* @return $result
	*/
	public function check_subresource_type( $subresource_type, $request ) {
		$url_params = $request->get_url_params();
		$resource_type = $url_params['resource_type'];
		$subresource_types = get_option( $this->option_prefix . 'subresource_types_' . $resource_type, array() );
		if ( isset( $subresource_types[ $resource_type ] ) ) {
			if ( in_array( $subresource_type, $subresource_types[ $resource_type ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	* Check for a valid subresource
	*
	* @param string $subresource
	* @param object $request
	* @return $result
	*/
	public function check_subresource( $subresource, $request ) {
		$url_params = $request->get_url_params();
		$resource_type = $url_params['resource_type'];
		$resource = $url_params['resource'];
		$subresource_type = $url_params['subresource_type'];
		$subresources = get_option( $this->option_prefix . 'subresources_' . $resource . '_' . $subresource_type, array() );
		if ( isset( $subresources[ $resource_type ][ $resource ][ $subresource_type ] ) ) {
			if ( in_array( $subresource, $subresources[ $resource_type ][ $resource ][ $subresource_type ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	* Check for a valid method
	*
	* @param string $method
	* @param object $request
	* @return $result
	*/
	public function check_method( $method, $request ) {
		$url_params = $request->get_url_params();
		$resource_type = $url_params['resource_type'];
		$resource = $url_params['resource'];
		$subresource_type = $url_params['subresource_type'];
		$methods = get_option( $this->option_prefix . 'subresource_methods', array() );
		if ( isset( $methods[ $resource_type ][ $resource ][ $subresource_type ] ) ) {
			if ( in_array( $method, $methods[ $resource_type ][ $resource ][ $subresource_type ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	* Check to see if the user has permission to do this
	*
	* @throws \Exception
	*/
	public function can_process( WP_REST_Request $request ) {
		// Note: any route/method combo is only created if the plugin options warrant it. We don't have to validate that here.
		/*if ( ! current_user_can( 'submit_mc_form' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to view this data.', 'form-processor-mailchimp' ), array( 'status' => 401 ) );
		}*/
		return true;
	}

	/**
	* Process the REST API request
	*
	* @return $result
	*/
	public function process( WP_REST_Request $request ) {
		// see methods: https://developer.wordpress.org/reference/classes/wp_rest_request/
		//error_log( 'request is ' . print_r( $request, true ) );
		$http_method = $request->get_method();
		$route = $request->get_route();
		$url_params = $request->get_url_params();
		$body_params = $request->get_body_params();
		$api_call = str_replace( '/' . $this->namespace . $this->api_version . '/', '', $route );
		//error_log( 'api call is ' . $api_call . ' and params are ' . print_r( $params, true ) );

		switch ( $http_method ) {
			case 'GET':
				$result = $this->mailchimp->load( $api_call, $url_params );
				return $result;
				break;
			case 'POST':
				$result = $this->mailchimp->send( $api_call, $http_method, $body_params );
				return $result;
				break;
			case 'PATCH':
				return 'edit existing';
				$result = $this->mailchimp->send( $api_call, $http_method, $body_params );
				return $result;
				break;
			case 'PUT':
				$result = $this->mailchimp->send( $api_call, $http_method, $body_params );
				return $result;
				break;
			case 'DELETE':
				return 'delete existing';
				$result = $this->mailchimp->remove();
				return $result;
				break;
			default:
				return;
				break;
		}
	}



}
