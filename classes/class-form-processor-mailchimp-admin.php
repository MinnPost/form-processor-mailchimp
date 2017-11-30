<?php
/**
 * Class file for the Form_Processor_MailChimp_Admin class.
 *
 * @file
 */

if ( ! class_exists( 'Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Create default WordPress admin functionality to configure the plugin.
 */
class Form_Processor_MailChimp_Admin {

	protected $option_prefix;
	protected $version;
	protected $mailchimp;
	protected $wordpress;

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

		$this->mc_form_transients = $this->wordpress->mc_form_transients;

		$this->add_actions();

	}

	/**
	* Create the action hooks to create the admin page(s)
	*
	*/
	public function add_actions() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'admin_settings_form' ) );
			add_action( 'plugins_loaded', array( $this, 'textdomain' ) );
		}

	}

	/**
	* Create WordPress admin options page
	*
	*/
	public function create_admin_menu() {
		$title = __( 'MailChimp Forms', 'form-processor-mailchimp' );
		add_options_page( $title, $title, 'manage_options', 'form-processor-mailchimp', array( $this, 'show_admin_page' ) );
	}

	/**
	* Display the admin settings page
	*
	* @return void
	*/
	public function show_admin_page() {
		?>
		<div class="wrap">
			<h1><?php _e( get_admin_page_title() , 'form-processor-mailchimp' ); ?></h1>
			<div id="main">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'form-processor-mailchimp' ) . do_settings_sections( 'form-processor-mailchimp' );
					?>
					<?php submit_button( __( 'Save settings', 'form-processor-mailchimp' ) ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	* Register items for the settings api
	* @return void
	*
	*/
	public function admin_settings_form() {
		$page = 'form-processor-mailchimp';
		$section = 'form-processor-mailchimp';
		$input_callback = array( $this, 'display_input_field' );
		$select_callback = array( $this, 'display_select' );
		$checkboxes_callback = array( $this, 'display_checkboxes' );
		add_settings_section( $page, null, null, $page );

		$mailchimp_api = $this->mailchimp->mailchimp_api;

		if ( '' !== $mailchimp_api ) {
			$settings = array(
				'list_ids' => array(
					'title' => __( 'MailChimp Lists', 'form-processor-mailchimp' ),
					'callback' => $checkboxes_callback,
					'page' => $page,
					'section' => $section,
					'args' => array(
						'type' => 'select',
						'desc' => '',
						'items' => $this->get_mailchimp_lists_options(),
					),
				),
			);
		} else {
			$settings = array(
				'mailchimp_api_key' => array(
					'title' => __( 'MailChimp API Key', 'form-processor-mailchimp' ),
					'callback' => $input_callback,
					'page' => $page,
					'section' => $section,
					'args' => array(
						'type' => 'text',
						'desc' => '',
					),
				),
			);
		}

		foreach ( $settings as $key => $attributes ) {
			$id = $this->option_prefix . $key;
			$name = $this->option_prefix . $key;
			$title = $attributes['title'];
			$callback = $attributes['callback'];
			$page = $attributes['page'];
			$section = $attributes['section'];
			$args = array_merge(
				$attributes['args'],
				array(
					'title' => $title,
					'id' => $id,
					'label_for' => $id,
					'name' => $name,
				)
			);
			add_settings_field( $id, $title, $callback, $page, $section, $args );
			register_setting( $section, $id );
		}

	}

	private function get_mailchimp_lists_options() {
		$mailchimp_api = $this->mailchimp->mailchimp_api;
		$lists = $this->mailchimp->load( 'lists' );
		$options = array();
		foreach ( $lists['lists'] as $list ) {
			$options[ $list['id'] ] = array(
				'text' => $list['name'],
				'id' => $list['id'],
				'desc' => '',
				'default' => '',
			);
		}
		return $options;
	}


	/**
	* Default display for <input> fields
	*
	* @param array $args
	*/
	public function display_input_field( $args ) {
		$type   = $args['type'];
		$id     = $args['label_for'];
		$name   = $args['name'];
		$desc   = $args['desc'];
		if ( ! isset( $args['constant'] ) || ! defined( $args['constant'] ) ) {
			$value  = esc_attr( get_option( $id, '' ) );
			echo '<input type="' . $type . '" value="' . $value . '" name="' . $name . '" id="' . $id . '"
			class="regular-text code" />';
			if ( '' !== $desc ) {
				echo '<p class="description">' . $desc . '</p>';
			}
		} else {
			echo '<p><code>' . __( 'Defined in wp-config.php', 'form-processor-mailchimp' ) . '</code></p>';
		}
	}

	/**
	* Display for <select>
	*
	* @param array $args
	*/
	public function display_select( $args ) {
		$name = $args['name'];
		$id = $args['label_for'];
		$desc = $args['desc'];
		$current_value = get_option( $name );
		echo '<select name="' . $name . '" id="' . $id . '"><option value="">' . __( 'Choose an option', 'form-processor-mailchimp' ) . '</option>';
		foreach ( $args['items'] as $key => $value ) {
			$selected = '';
			if ( $current_value === $key ) {
				$selected = 'selected';
			}
			echo '<option value="' . $key . '"  ' . $selected . '>' . $value . '</option>';
		}
		echo '</select>';
		if ( '' !== $desc ) {
			echo '<p class="description">' . $desc . '</p>';
		}
	}

	/**
	* Display for multiple checkboxes
	* Input method can handle a single checkbox as it is
	*
	* @param array $args
	*/
	public function display_checkboxes( $args ) {
		$type = 'checkbox';
		$name = $args['name'];
		$options = get_option( $name, array() );
		foreach ( $args['items'] as $key => $value ) {
			$text = $value['text'];
			$id = $value['id'];
			$desc = $value['desc'];
			$checked = '';
			if ( is_array( $options ) && in_array( (string) $key, $options, true ) ) {
				$checked = 'checked';
			} elseif ( is_array( $options ) && empty( $options ) ) {
				if ( isset( $value['default'] ) && true === $value['default'] ) {
					$checked = 'checked';
				}
			}
			echo sprintf( '<div class="checkbox"><label><input type="%1$s" value="%2$s" name="%3$s[]" id="%4$s"%5$s>%6$s</label></div>',
				esc_attr( $type ),
				esc_attr( $key ),
				esc_attr( $name ),
				esc_attr( $id ),
				esc_html( $checked ),
				esc_html( $text )
			);
			if ( '' !== $desc ) {
				echo sprintf( '<p class="description">%1$s</p>',
					esc_html( $desc )
				);
			}
		}
	}

	/**
	 * Load textdomain
	 *
	 * @return void
	 */
	public function textdomain() {
		load_plugin_textdomain( 'form-processor-mailchimp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

}
