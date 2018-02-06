<?php
/**
 * Class file for the Form_Processor_MailChimp_WPWrapper class.
 *
 * @file
 */

if ( ! class_exists( 'Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Create default WordPress functionality to configure the plugin.
 */
class Form_Processor_MailChimp_WPWrapper {

	protected $option_prefix;
	protected $version;

	/**
	* Constructor which sets up WordPress methods
	*
	* @param string $option_prefix
	* @param string $version
	* @throws \Exception
	*/
	public function __construct( $option_prefix, $version ) {

		$this->option_prefix = $option_prefix;
		$this->version = $version;

		$this->cache = true;

		$this->mc_form_transients = new Mailchimp_Form_Processor_WordPress_Transient( 'mc_form_transients' );

	}

	/**
	 * Check to see if this API call exists in the cache
	 * if it does, return the transient for that key
	 *
	 * @param string $call The API call we'd like to make.
	 * @return $this->mc_form_transients->get $cachekey
	 */
	public function cache_get( $call ) {
		$cachekey = md5( wp_json_encode( $call ) );
		return $this->mc_form_transients->get( $cachekey );
	}

	/**
	 * Create a cache entry for the current result, with the url and args as the key
	 *
	 * @param string $call The API query name.
	 * @return Bool whether or not the value was set
	 * @link https://wordpress.stackexchange.com/questions/174330/transient-storage-location-database-xcache-w3total-cache
	 */
	public function cache_set( $call, $data ) {
		$cachekey = md5( wp_json_encode( $call ) );
		return $this->mc_form_transients->set( $cachekey, $data );
	}

}

/**
 * Class to store all theme/plugin transients as an array in one WordPress transient
 **/
class Mailchimp_Form_Processor_WordPress_Transient {

	protected $name;

	public $cache_expiration;

	/**
	 * Constructor which sets cache options and the name of the field that lists this plugin's cache keys.
	 *
	 * @param string $name The name of the field that lists all cache keys.
	 */
	public function __construct( $name ) {
		$this->name = $name;
		$this->cache_expiration = 86400;
		$this->cache_prefix = esc_sql( 'mcform_' );
	}

	/**
	 * Get the transient that lists all the other transients for this plugin.
	 *
	 * @return mixed value of transient. False of empty, otherwise array.
	 */
	public function all_keys() {
		return get_transient( $this->name );
	}

	/**
	 * Set individual transient, and add its key to the list of this plugin's transients.
	 *
	 * @param string $cachekey the key for this cache item
	 * @param mixed $value the value of the cache item
	 * @param int $cache_expiration. How long the plugin key cache, and this individual item cache, should last before expiring.
	 * @return mixed value of transient. False of empty, otherwise array.
	 */
	public function set( $cachekey, $value ) {

		$prefix = $this->cache_prefix;
		$cachekey = $prefix . $cachekey;

		$keys = $this->all_keys();
		$keys[] = $cachekey;
		set_transient( $this->name, $keys, $this->cache_expiration );

		return set_transient( $cachekey, $value, $this->cache_expiration );
	}

	/**
	 * Get the individual cache value
	 *
	 * @param string $cachekey the key for this cache item
	 * @return mixed value of transient. False of empty, otherwise array.
	 */
	public function get( $cachekey ) {
		$prefix = $this->cache_prefix;
		$cachekey = $prefix . $cachekey;
		return get_transient( $cachekey );
	}

	/**
	 * Delete the individual cache value
	 *
	 * @param string $cachekey the key for this cache item
	 * @return bool True if successful, false otherwise.
	 */
	public function delete( $cachekey ) {
		$prefix = $this->cache_prefix;
		$cachekey = $prefix . $cachekey;
		return delete_transient( $cachekey );
	}

	/**
	 * Delete the entire cache for this plugin
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function flush() {
		$keys = $this->all_keys();
		$result = true;
		foreach ( $keys as $key ) {
			$result = delete_transient( $key );
		}
		$result = delete_transient( $this->name );
		return $result;
	}

}
