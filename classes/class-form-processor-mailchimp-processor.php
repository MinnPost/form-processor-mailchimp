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
	protected $namespace;
	protected $api_version;
	protected $wordpress;
	protected $mailchimp;

	/**
	* Constructor which sets up admin pages
	*
	* @param string $option_prefix
	* @param string $version
	* @param string $namespace
	* @param string $api_version
	* @param object $wordpress
	* @param object $mailchimp
	* @throws \Exception
	*/
	public function __construct( $option_prefix, $version, $namespace, $api_version, $wordpress, $mailchimp ) {
		$this->option_prefix = $option_prefix;
		$this->version = $version;
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
		$resource_types = get_option( $this->option_prefix . 'resource_types', '' );
		$subresource_methods = get_option( $this->option_prefix . 'subresource_methods', '' );

		if ( '' !== $resource_types && is_array( $resource_types ) ) {
			foreach ( $resource_types as $key => $resource_type ) {
				register_rest_route( $namespace, '/' . $resource_type, array(
					array(
						'methods' => $method_list,
						'callback' => array( $this, 'process' ),
						'permission_callback' => array( $this, 'can_process' ),
					),
				) );
				$subresource_types = get_option( $this->option_prefix . 'subresource_types_' . $resource_type, '' );
				$resources = get_option( $this->option_prefix . 'resources_' . $resource_type, '' );
				if ( '' !== $resources && is_array( $resources ) ) {
					foreach ( $resources[ $resource_type ] as $resource ) {
						register_rest_route( $namespace, '/' . $resource_type . '/' . $resource . '/', array(
							array(
								'methods' => $method_list,
								'callback' => array( $this, 'process' ),
								'permission_callback' => array( $this, 'can_process' ),
							),
						) );
						if ( '' !== $subresource_types && is_array( $subresource_types ) ) {
							foreach ( $subresource_types[ $resource_type ] as $subresource_type ) {
								register_rest_route( $namespace, '/' . $resource_type . '/' . $resource . '/' . $subresource_type, array(
									array(
										'methods' => $method_list,
										'callback' => array( $this, 'process' ),
										'permission_callback' => array( $this, 'can_process' ),
									),
								) );

								$subresources = get_option( $this->option_prefix . 'subresources_' . $resource . '_' . $subresource_type, '' );

								if ( isset( $subresources ) && is_array( $subresources ) ) {
									foreach ( $subresources[ $resource_type ][ $resource ][ $subresource_type ] as $subresource ) {
										register_rest_route( $namespace, '/' . $resource_type . '/' . $resource . '/' . $subresource_type . '/' . $subresource, array(
											array(
												'methods' => $method_list,
												'callback' => array( $this, 'process' ),
												'permission_callback' => array( $this, 'can_process' ),
											),
										) );
									}
								}
							}
						}
					}
				}
			}
		}
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
		$method = $request->get_method();
		$route = $request->get_route();
		$url_params = $request->get_url_params();
		$body_params = $request->get_body_params();
		$api_call = str_replace( '/' . $this->namespace . $this->api_version . '/', '', $route );
		//error_log( 'api call is ' . $api_call . ' and params are ' . print_r( $params, true ) );
		switch ( $method ) {
			case 'GET':
				$result = $this->mailchimp->load( $api_call );
				return $result;
				break;
			case 'POST':
				$result = $this->mailchimp->send( $api_call, $method, $body_params );
				return $result;
				break;
			case 'PATCH':
				return 'edit existing';
				$result = $this->mailchimp->send( $api_call, $method, $body_params );
				return $result;
				break;
			case 'PUT':
				$result = $this->mailchimp->send( $api_call, $method, $body_params );
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
