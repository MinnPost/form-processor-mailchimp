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

	public function generate_interest_options( $list_id, $category_id = '', $keys = array(), $field_value = 'id' ) {
		// need to try to generate a field this way i think
		$interest_options = array();
		if ( '' !== $category_id ) {
			$resource_type = 'lists';
			$subresource_type = 'interest-categories';
			$method = 'interests';

			$params = array(
				'resource_type' => $resource_type,
				'subresource_type' => $subresource_type,
				'method' => $method,
			);

			$interest_categories = $this->mailchimp->load( $resource_type . '/' . $list_id . '/' . $subresource_type );
			foreach ( $interest_categories['categories'] as $key => $category ) {
				$id = $category['id'];
				$title = $category['title'];

				$params['resource'] = $list_id;
				$params['subresource'] = $id;

				$interests = $this->mailchimp->load( $resource_type . '/' . $list_id . '/' . $subresource_type . '/' . $id . '/' . $method, $params );

				$id = isset( $keys[ $key ] ) ? $keys[ $key ] : $category['id'];
				$interest_options[ $id ]['title'] = $title;
				$interest_options[ $id ]['interests'] = array();
				foreach ( $interests['interests'] as $interest ) {
					$interest_id = $interest['id'];
					$interest_name = $interest['name'];
					$interest_options[ $id ]['interests'][ ${'interest_' . $field_value} ] = $interest_name;
				}
			}
			return $interest_options;
		}
	}

}
