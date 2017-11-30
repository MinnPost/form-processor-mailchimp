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
	protected $wordpress;

	/**
	* Constructor which sets up admin pages
	*
	* @param string $option_prefix
	* @param string $version
	* @param object $wordpress
	* @throws \Exception
	*/
	public function __construct( $option_prefix, $version, $wordpress ) {
		$this->option_prefix = $option_prefix;
		$this->version = $version;
		$this->wordpress = $wordpress;

		$this->mailchimp_api = $this->mailchimp_api();
	}

	public function mailchimp_api() {
		if ( ! class_exists( 'DrewM/Mailchimp/MailChimp' ) ) {
			require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';
		}
		$mailchimp_key = get_option( $this->option_prefix . 'mailchimp_api_key', '' );
		if ( '' !== $mailchimp_key ) {
			$mailchimp_api = new MailChimp( $mailchimp_key );
			return $mailchimp_api;
		} else {
			return '';
		}
	}

	public function load( $call ) {
		$cached = $this->wordpress->cache_get( $call );
		if ( is_array( $cached ) ) {
			return $cached;
		} else {
			$data = $this->mailchimp_api->get( $call );
			$cached = $this->wordpress->cache_set( $call, $data );
			return $data;
		}
	}

}
