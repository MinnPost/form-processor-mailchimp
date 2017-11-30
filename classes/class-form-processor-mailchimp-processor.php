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
	protected $wordpress;
	protected $mailchimp;

	/**
	* Constructor which sets up admin pages
	*
	* @param string $option_prefix
	* @param string $version
	* @param object $wordpress
	* @param object $mailchimp
	* @throws \Exception
	*/
	public function __construct( $option_prefix, $version, $wordpress, $mailchimp ) {
		$this->option_prefix = $option_prefix;
		$this->version = $version;
		$this->wordpress = $wordpress;
		$this->mailchimp = $mailchimp;

		$this->init();
	}

	private function init() {
		if ( ! is_admin() ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}


	public function register_routes() {
		$namespace = $this->namespace . $this->api_version;
		$base = 'submit';
		register_rest_route( $namespace, '/' . $base, array(
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'submit_form' ),
				'permission_callback' => array( $this, 'submit_form_permission' ),
			),
		) );
	}

	public function submit_form_permission() {
		/*if ( ! current_user_can( 'submit_mc_form' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to view this data.', 'my-text-domain' ), array( 'status' => 401 ) );
		}*/
		return true;
	}

	public function submit_form( WP_REST_Request $request ) {
		$args = array(
			'post_title' => $request->get_param( 'title' ),
			'post_category' => array( $request->get_param( 'category' ) ),
		);

		if ( false !== ( $id = wp_insert_post( $args ) ) ) {
			return get_post( $id );
		}

		return false;
	}



}
