<?php
/*
Plugin Name: Form Processor for MailChimp
Description: This plugin processes a form that has been submitted to it, and integrates with the MailChimp API.
Version: 0.0.6
Author: Jonathan Stegall
Author URI: https://code.minnpost.com
Text Domain: form-processor-mailchimp
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * The full path to the main file of this plugin
 *
 * This can later be passed to functions such as
 * plugin_dir_path(), plugins_url() and plugin_basename()
 * to retrieve information about plugin paths
 *
 * @since 0.0.6
 * @var string
 */
define( 'FORM_PROCESSOR_MAILCHIMP_FILE', __FILE__ );

/**
 * The plugin's current version
 *
 * @since 0.0.6
 * @var string
 */
define( 'FORM_PROCESSOR_MAILCHIMP_VERSION', '0.0.6' );

/**
 * Enable autoloading of plugin classes
 * @param $class_name
 */
function form_processor_mailchimp_autoload( $class_name ) {

	// Only autoload classes from this plugin
	if ( 'Form_Processor_MailChimp' !== $class_name && 0 !== strpos( $class_name, 'Form_Processor_Mailchimp_' ) ) {
		return;
	}

	// wpcs style filename for each class
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) );

	// create file path
	$file = dirname( FORM_PROCESSOR_MAILCHIMP_FILE ) . '/php/' . $file_name . '.php';

	// If a file is found, load it
	if ( file_exists( $file ) ) {
		require_once( $file );
	}

}

try {
	spl_autoload_register( 'form_processor_mailchimp_autoload' );
} catch ( Exception $e ) {
	new WP_Error( $e->getCode(), $e->getMessage() );
}

/**
 * Retrieve the instance of the main plugin class
 *
 * @since 2.6.0
 * @return Form_Processor_Mailchimp
 */
function form_processor_mailchimp() {
	static $plugin;

	if ( is_null( $plugin ) ) {
		$plugin = new Form_Processor_MailChimp( FORM_PROCESSOR_MAILCHIMP_VERSION, __FILE__ );
	}

	return $plugin;
}

form_processor_mailchimp()->init();
