<?php

/**
 * The main plugin class
 *
 * @package Form_Processor_Mailchimp
 */
class Form_Processor_Mailchimp {

	/**
	 * The version number for this release of the plugin.
	 * This will later be used for upgrades and enqueuing files
	 *
	 * This should be set to the 'Plugin Version' value defined
	 * in the plugin header.
	 *
	 * @var string A PHP-standardized version number string
	 */
	public $version;

	/**
	 * Filesystem path to the main plugin file
	 * @var string
	 */
	public $file;

	/**
	 * @var Code_Snippets_DB
	 */
	public $db;

	/**
	 * @var Code_Snippets_Admin
	 */
	public $admin;

	/**
	 * @var Code_Snippets_Shortcode
	 */
	public $shortcode;

	/**
	 * Class constructor
	 *
	 * @param string $version The current plugin version
	 * @param string $file The main plugin file
	 */
	function __construct( $version, $file ) {
		$this->version       = $version;
		$this->file          = $file;
		$this->option_prefix = 'form_process_mc_';
		$this->slug          = 'form-processor-mailchimp';

		// The namespace and version for the REST SERVER
		$this->namespace   = 'form-processor-mc/v';
		$this->api_version = '1';

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		//add_filter( 'code_snippets/execute_snippets', array( $this, 'disable_snippet_execution' ), 5 );
	}

	public function init() {
		//$includes_path = dirname( __FILE__ );

		// WordPress features
		$this->wordpress = new Form_Processor_Mailchimp_WP();

		// MailChimp features
		$this->mailchimp = new Form_Processor_Mailchimp_MC();

		// Form Processor
		//$this->processor = new Form_Processor_Mailchimp_Processor();

		// Admin features
		$this->admin = new Form_Processor_Mailchimp_Admin();

	}

	/**
	 * Get the URL to the plugin admin menu
	 *
	 * @return string          The menu's URL
	 */
	public function get_menu_url() {
		$url = 'options-general.php?page=' . $this->slug;
		return admin_url( $url );
	}

	/**
	 * Load up the localization file if we're using WordPress in a different language.
	 *
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'form-processor-mailchimp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

}
