<?php
/*
Plugin Name: Form Processor for MailChimp
Description: This plugin processes a form that has been submitted to it, and integrates with the MailChimp API.
Version: 0.0.3
Author: Jonathan Stegall
Author URI: https://code.minnpost.com
Text Domain: form-processor-mailchimp
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

class Form_Processor_MailChimp {

	/**
	* @var string
	* The plugin version
	*/
	private $version;

	/**
	* @var string
	* The REST API namespace
	*/
	private $namespace;

	/**
	* @var int
	* The REST API version
	*/
	private $api_version;

	/**
	* @var string
	* The plugin's prefix for saving options
	*/
	protected $option_prefix;

	/**
	* @var object
	* Load and initialize the Form_Processor_MailChimp_WPWrapper class
	*/
	protected $wordpress;

	/**
	* @var object
	* Load and initialize the Form_Processor_MailChimp_MCWrapper class
	*/
	protected $mailchimp;

	/**
	* @var object
	* Load and initialize the Form_Processor_MailChimp_Processor class
	*/
	protected $processor;

	/**
	* @var object
	* Load and initialize the Form_Processor_MailChimp_Admin class
	*/
	public $admin;

	/**
	 * @var object
	 * Static property to hold an instance of the class; this seems to make it reusable
	 *
	 */
	static $instance = null;

	/**
	* Load the static $instance property that holds the instance of the class.
	* This instance makes the class reusable by other plugins
	*
	* @return object
	*   The sfapi object if it is authenticated (empty, otherwise)
	*
	*/
	static public function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Form_Processor_MailChimp();
		}
		return self::$instance;
	}

	/**
	 * This is our constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$this->version = '0.0.3';
		$this->slug    = 'form-processor-mailchimp';

		//The namespace and version for the REST SERVER
		$this->namespace   = 'form-processor-mc/v';
		$this->api_version = '1';

		$this->option_prefix = 'form_process_mc_';

		// WordPress wrapper
		$this->wordpress = $this->wordpress();

		// MailChimp wrapper
		$this->mailchimp = $this->mailchimp();

		// form processor
		$this->processor = $this->processor();

		// admin settings
		$this->admin = $this->load_admin();

	}

	/**
	 * WordPress wrapper. Includes caching.
	 *
	 * @return object $wordpress
	 */
	private function wordpress() {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/class-form-processor-mailchimp-wpwrapper.php' );
		$wordpress = new Form_Processor_MailChimp_WPWrapper( $this->option_prefix, $this->version, $this->slug );
		return $wordpress;
	}

	/**
	 * MailChimp API wrapper
	 *
	 * @return object $mailchimp
	 */
	private function mailchimp() {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/class-form-processor-mailchimp-mcwrapper.php' );
		$mailchimp = new Form_Processor_MailChimp_MCWrapper( $this->option_prefix, $this->version, $this->slug, $this->wordpress );
		return $mailchimp;
	}

	/**
	 * REST API processor. Handles API requests.
	 *
	 * @return object $processor
	 */
	private function processor() {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/class-form-processor-mailchimp-processor.php' );
		$processor = new Form_Processor_MailChimp_Processor( $this->option_prefix, $this->version, $this->slug, $this->namespace, $this->api_version, $this->wordpress, $this->mailchimp );
		return $processor;
	}

	/**
	 * Plugin admin
	 *
	 * @return object $admin
	 */
	public function load_admin() {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/class-form-processor-mailchimp-admin.php' );
		$admin = new Form_Processor_MailChimp_Admin( $this->option_prefix, $this->version, $this->slug, $this->wordpress, $this->mailchimp );
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		return $admin;
	}

	/**
	* Display a Settings link on the main Plugins page
	*
	* @param array $links
	* @param string $file
	* @return array $links
	* These are the links that go with this plugin's entry
	*/
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$settings = '<a href="' . get_admin_url() . 'options-general.php?page=form-processor-mailchimp">' . __( 'Settings', 'form-processor-mailchimp' ) . '</a>';
			array_unshift( $links, $settings );
		}
		return $links;
	}

}

// Instantiate our class
$form_processor_mailchimp = Form_Processor_MailChimp::get_instance();
