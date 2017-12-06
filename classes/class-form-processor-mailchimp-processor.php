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

	private function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}


	public function register_routes() {
		$namespace = $this->namespace . $this->api_version;
		$resources = get_option( $this->option_prefix . 'resources', '' );
		$subresources = get_option( $this->option_prefix . 'subresources', '' );
		$resource_methods = get_option( $this->option_prefix . 'resource_methods', '' );
		$subresource_methods = get_option( $this->option_prefix . 'subresource_methods', '' );

		if ( '' !== $resources && is_array( $resources ) ) {
			foreach ( $resources as $key => $resource ) {
				$subresource_list = $subresources[ $resource ];
				$method_list = $resource_methods[ $resource ];
				register_rest_route( $namespace, '/' . $resource, array(
					array(
						'methods' => $method_list,
						'callback' => array( $this, 'process' ),
						'permission_callback' => array( $this, 'can_process' ),
					),
				) );
				register_rest_route( $namespace, '/' . $resource . '/(?P<resource_id>\w+)/', array(
					array(
						'methods' => $method_list,
						'callback' => array( $this, 'process' ),
						'args' => array(
							'resource_id' => array(
								'validate_callback' => 'sanitize_key',
							),
						),
						'permission_callback' => array( $this, 'can_process' ),
					),
				) );
				if ( '' !== $subresource_list && is_array( $subresource_list ) ) {
					foreach ( $subresource_list as $key => $subresource ) {
						$method_list = $subresource_methods[ $resource ][ $subresource ];
						register_rest_route( $namespace, '/' . $resource . '/(?P<resource_id>\w+)/' . $subresource, array(
							array(
								'methods' => $method_list,
								'callback' => array( $this, 'process' ),
								'args' => array(
									'resource_id' => array(
										'validate_callback' => 'sanitize_key',
									),
								),
								'permission_callback' => array( $this, 'can_process' ),
							),
						) );
						register_rest_route( $namespace, '/' . $resource . '/(?P<resource_id>\w+)/' . $subresource . '/(?P<subresource_id>\w+)/', array(
							array(
								'methods' => $method_list,
								'callback' => array( $this, 'process' ),
								'args' => array(
									'resource_id' => array(
										'validate_callback' => 'sanitize_key',
									),
									'subresource_id' => array(
										'validate_callback' => 'sanitize_key',
									),
								),
								'permission_callback' => array( $this, 'can_process' ),
							),
						) );
					}
				}
			}
		}
	}

	public function can_process( WP_REST_Request $request ) {
		// Note: any route/method combo is only created if the plugin options warrant it. We don't have to validate that here.
		/*if ( ! current_user_can( 'submit_mc_form' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to view this data.', 'form-processor-mailchimp' ), array( 'status' => 401 ) );
		}*/
		return true;
	}

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
				return 'create new';
				break;
			case 'PATCH':
				return 'edit existing';
				break;
			case 'DELETE':
				return 'delete existing';
				break;
			default:
				return;
				break;
		}
	}



}
