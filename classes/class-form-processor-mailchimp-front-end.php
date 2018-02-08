<?php
/**
 * Class file for the Form_Processor_MailChimp_Front_End class.
 *
 * @file
 */

if ( ! class_exists( 'Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Form processor
 */
class Form_Processor_MailChimp_Front_End {

	protected $option_prefix;
	protected $version;
	protected $namespace;
	protected $api_version;
	protected $mailchimp;
	protected $wordpress;

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
	public function __construct( $option_prefix, $version, $wordpress, $mailchimp ) {
		$this->option_prefix = $option_prefix;
		$this->version = $version;
		$this->mailchimp = $mailchimp;
		$this->wordpress = $wordpress;

		$this->load_actions();
	}

	/**
	* I expect we will need to do some hooks here?
	*
	* @throws \Exception
	*/
	private function load_actions() {
		// do some wordpress hook stuff
	}

	public function generate_interest_options( $list_id, $category_id = '' ) {
		// need to try to generate a field this way i think
		if ( '' !== $category_id ) {
			$list = $this->mailchimp->load( 'lists/' . $list_id . 'interest-categories/' . $category_id );
			return $list;
		}
	}

}
