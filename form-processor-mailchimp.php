<?php
/*
Plugin Name: Form Processor for MailChimp
Description: This plugin processes a form that has been submitted to it, and integrates with the MailChimp API.
Version: 0.0.7
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
define( 'FORM_PROCESSOR_MAILCHIMP_VERSION', '0.0.7' );

// Load the autoloader.
require_once( 'lib/autoloader.php' );

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
