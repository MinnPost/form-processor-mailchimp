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

		$this->tabs = $this->get_admin_tabs();

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

	private function get_admin_tabs() {
		$tabs = array(
			'mc_settings' => 'MailChimp Settings',
			'allowed_resources' => 'Allowed Resources',
			'resource_settings' => 'Resource Settings',
			//'subresource_settings' => 'Subresource Settings',
			//'schedule' => 'Scheduling',
		); // this creates the tabs for the admin
		return $tabs;
	}

	/**
	* Display the admin settings page
	*
	* @return void
	*/
	public function show_admin_page() {
		$get_data = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );
		?>
		<div class="wrap">
			<h1><?php _e( get_admin_page_title() , 'form-processor-mailchimp' ); ?></h1>

			<?php
			$tabs = $this->tabs;
			$tab = isset( $get_data['tab'] ) ? sanitize_key( $get_data['tab'] ) : 'mc_settings';
			$this->render_tabs( $tabs, $tab );

			switch ( $tab ) {
				case 'mc_settings':
					require_once( plugin_dir_path( __FILE__ ) . '/../templates/admin/settings.php' );
					break;
				case 'allowed_resources':
					require_once( plugin_dir_path( __FILE__ ) . '/../templates/admin/settings.php' );
					break;
				case 'resource_settings':
					require_once( plugin_dir_path( __FILE__ ) . '/../templates/admin/settings.php' );
					break;
				//case 'subresource_settings':
				//	require_once( plugin_dir_path( __FILE__ ) . '/../templates/admin/settings.php' );
				//	break;
				default:
					require_once( plugin_dir_path( __FILE__ ) . '/../templates/admin/settings.php' );
					break;
			} // End switch().
			?>
		</div>
		<?php
	}

	/**
	* Render tabs for settings pages in admin
	* @param array $tabs
	* @param string $tab
	*/
	private function render_tabs( $tabs, $tab = '' ) {

		$get_data = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );
		$mailchimp_api = $this->mailchimp->mailchimp_api;

		$current_tab = $tab;
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab_key => $tab_caption ) {
			$active = $current_tab === $tab_key ? ' nav-tab-active' : '';
			if ( 'mc_settings' === $tab_key || ( isset( $mailchimp_api ) && ! empty( $mailchimp_api ) ) ) {
				echo sprintf( '<a class="nav-tab%1$s" href="%2$s">%3$s</a>',
					esc_attr( $active ),
					esc_url( '?page=form-processor-mailchimp&tab=' . $tab_key ),
					esc_html( $tab_caption )
				);
			}
		}
		echo '</h2>';

		if ( isset( $get_data['tab'] ) ) {
			$tab = sanitize_key( $get_data['tab'] );
		} else {
			$tab = '';
		}
	}

	/**
	* Register items for the settings api
	* @return void
	*
	*/
	public function admin_settings_form() {

		$get_data = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );
		$page = isset( $get_data['tab'] ) ? sanitize_key( $get_data['tab'] ) : 'mc_settings';
		$section = isset( $get_data['tab'] ) ? sanitize_key( $get_data['tab'] ) : 'mc_settings';

		$input_callback_default = array( $this, 'display_input_field' );
		$input_checkboxes_default = array( $this, 'display_checkboxes' );
		$input_select_default = array( $this, 'display_select' );
		$link_default = array( $this, 'display_link' );

		$all_field_callbacks = array(
			'text' => $input_callback_default,
			'checkboxes' => $input_checkboxes_default,
			'select' => $input_select_default,
			'link' => $link_default,
		);

		$mailchimp_api = $this->mailchimp->mailchimp_api;

		$this->mc_settings( 'mc_settings', 'mc_settings', $all_field_callbacks );

		if ( '' !== $mailchimp_api ) {
			$this->allowed_resources( 'allowed_resources', 'allowed_resources', $all_field_callbacks );
			$this->resource_settings( 'resource_settings', 'resource_settings', $all_field_callbacks );
			//$this->subresource_settings( 'subresource_settings', 'subresource_settings', $all_field_callbacks );
		}

	}

	/**
	* Fields for the MailChimp Settings tab
	* This runs add_settings_section once, as well as add_settings_field and register_setting methods for each option
	*
	* @param string $page
	* @param string $section
	* @param string $input_callback
	*/
	private function mc_settings( $page, $section, $callbacks ) {
		$tabs = $this->tabs;
		foreach ( $tabs as $key => $value ) {
			if ( $key === $page ) {
				$title = $value;
			}
		}
		add_settings_section( $page, $title, null, $page );

		$settings = array(
			'mailchimp_api_key' => array(
				'title' => __( 'MailChimp API Key', 'form-processor-mailchimp' ),
				'callback' => $callbacks['text'],
				'page' => $page,
				'section' => $section,
				'args' => array(
					'type' => 'text',
					'desc' => '',
				),
			),
		);

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

	/**
	* Fields for the Allowed Resources tab
	* This runs add_settings_section once, as well as add_settings_field and register_setting methods for each option
	*
	* @param string $page
	* @param string $section
	* @param string $input_callback
	*/
	private function allowed_resources( $page, $section, $callbacks ) {
		$tabs = $this->tabs;
		foreach ( $tabs as $key => $value ) {
			if ( $key === $page ) {
				$title = $value;
			}
		}

		add_settings_section( $page, $title, null, $page );

		$settings = array(
			'resources' => array(
				'title' => __( 'Allowed Resources', 'form-processor-mailchimp' ),
				'callback' => $callbacks['checkboxes'],
				'page' => $page,
				'section' => $section,
				'args' => array(
					'type' => 'select',
					'desc' => '',
					'items' => $this->get_mailchimp_resources_options(),
				),
			),
		);

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

	/**
	* Fields for the Resource Settings tab
	* This runs add_settings_section once, as well as add_settings_field and register_setting methods for each option
	*
	* @param string $page
	* @param string $section
	* @param string $input_callback
	*/
	private function resource_settings( $page, $section, $callbacks ) {

		$resources = get_option( $this->option_prefix . 'resources', '' );

		if ( '' !== $resources ) {
			$settings = array();
			foreach ( $resources as $resource ) {
				$section = $section . '_' . $resource;
				add_settings_section( $section, ucwords( $resource ), null, $page );
				$resource_settings = array(
					'methods' => array(
						'title' => __( 'Allowed Methods', 'form-processor-mailchimp' ),
						'callback' => $callbacks['checkboxes'],
						'page' => $page,
						'section' => $section,
						'args' => array(
							'resource' => $resource,
							'subresource' => '',
							'type' => 'select',
							'desc' => '',
							'items' => $this->get_mailchimp_method_options( $resource ),
						),
					),
					'subresources' => array(
						'title' => __( 'Allowed Subresources', 'form-processor-mailchimp' ),
						'callback' => $callbacks['checkboxes'],
						'page' => $page,
						'section' => $section,
						'args' => array(
							'resource' => $resource,
							'type' => 'select',
							'desc' => '',
							'items' => $this->get_mailchimp_subresources_options( $resource ),
						),
					),
				);
				foreach ( $resource_settings as $key => $attributes ) {
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
					register_setting( $page, $id );
				}
			}
			$settings[ $resource ] = $resource_settings;
		}

	}

	/**
	* Fields for the MailChimp Subresources tab
	* This runs add_settings_section once, as well as add_settings_field and register_setting methods for each option
	*
	* @param string $page
	* @param string $section
	* @param string $input_callback
	*/
	private function subresource_settings( $page, $section, $callbacks ) {

		$subresources = get_option( $this->option_prefix . 'subresources', '' );

		if ( '' !== $subresources ) {

			foreach ( $subresources as $subresource ) {
				$section = $section . '_' . $subresource;
				add_settings_section( $section, ucwords( $subresource ), null, $page );
				$settings = array(
					'methods' => array(
						'title' => __( 'Allowed Methods', 'form-processor-mailchimp' ),
						'callback' => $callbacks['checkboxes'],
						'page' => $page,
						'section' => $section,
						'args' => array(
							'resource' => $subresource,
							'type' => 'select',
							'desc' => '',
							'items' => $this->get_mailchimp_method_options( $list, $subresource ),
						),
					),
				);
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
					register_setting( $page, $id );
				}
			}
		}

	}

	private function get_mailchimp_resources_options() {
		$resources = $this->mailchimp->load( '' );
		$options = array();
		foreach ( $resources['_links'] as $link ) {
			if ( 'self' !== $link['rel'] ) {
				$options[ $link['rel'] ] = array(
					'text' => ucwords( $link['rel'] ),
					'id' => $link['rel'],
					'desc' => '',
					'default' => '',
				);
			}
		}
		return $options;
	}

	private function get_mailchimp_subresources_options( $resource = '' ) {
		$subresources = $this->mailchimp->load( $resource );
		$options = array();
		foreach ( $subresources[ $resource ][0]['_links'] as $link ) {
			if ( ! in_array( $link['rel'], array( 'self', 'parent', 'update', 'delete' ) ) ) {
				$options[ $link['rel'] ] = array(
					'resource' => $resource,
					'text' => ucwords( $link['rel'] ),
					'id' => $link['rel'],
					'desc' => '',
					'default' => '',
				);
			}
		}
		return $options;
	}

	private function get_mailchimp_method_options( $resource = '', $subresource = '' ) {
		$methods = array( 'GET', 'POST', 'PATCH', 'DELETE' );
		$options = array();
		foreach ( $methods as $method ) {
			$options[ strtolower( $method ) ] = array(
				'resource' => $resource,
				'subresource' => $subresource,
				'text' => $method,
				'id' => strtolower( $method ),
				'desc' => '',
				'default' => '',
			);
		}
		return $options;
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
		$checked = '';

		$class = 'regular-text';

		if ( 'checkbox' === $type ) {
			$class = 'checkbox';
		}

		if ( ! isset( $args['constant'] ) || ! defined( $args['constant'] ) ) {
			$value  = esc_attr( get_option( $id, '' ) );
			if ( 'checkbox' === $type ) {
				if ( '1' === $value ) {
					$checked = 'checked ';
				}
				$value = 1;
			}
			if ( '' === $value && isset( $args['default'] ) && '' !== $args['default'] ) {
				$value = $args['default'];
			}

			echo sprintf( '<input type="%1$s" value="%2$s" name="%3$s" id="%4$s" class="%5$s"%6$s>',
				esc_attr( $type ),
				esc_attr( $value ),
				esc_attr( $name ),
				esc_attr( $id ),
				sanitize_html_class( $class . esc_html( ' code' ) ),
				esc_html( $checked )
			);
			if ( '' !== $desc ) {
				echo sprintf( '<p class="description">%1$s</p>',
					esc_html( $desc )
				);
			}
		} else {
			echo sprintf( '<p><code>%1$s</code></p>',
				esc_html__( 'Defined in wp-config.php', 'form-processor-mailchimp' )
			);
		}
	}

	/**
	* Display for multiple checkboxes
	* Above method can handle a single checkbox as it is
	*
	* @param array $args
	*/
	public function display_checkboxes( $args ) {
		$resource = isset( $args['resource'] ) ? $args['resource'] : '';
		$subresource = isset( $args['subresource'] ) ? $args['subresource'] : '';
		$type = 'checkbox';

		$name = $args['name'];
		$options = get_option( $name, array() );

		if ( isset( $options[ $resource ] ) && is_array( $options[ $resource ] ) ) {
			$options = $options[ $resource ];
			if ( isset( $options[ $resource ][ $subresource ] ) && is_array( $options[ $resource ][ $subresource ] ) ) {
				$options = $options[ $resource ][ $subresource ];
			}
		}

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

			if ( '' !== $resource ) {
				// this generates, for example, form_process_mc_methods[lists][]
				$input_name = $name . '[' . $resource . ']';
				if ( '' !== $subresource ) {
					// this generates, for example, form_process_mc_methods[lists][members][]
					$input_name = $name . '[' . $resource . ']' . '[' . $subresource . ']';
				}
			} else {
				$input_name = $name;
			}

			echo sprintf( '<div class="checkbox"><label><input type="%1$s" value="%2$s" name="%3$s[]" id="%4$s"%5$s>%6$s</label></div>',
				esc_attr( $type ),
				esc_attr( $key ),
				esc_attr( $input_name ),
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
	* Display for a dropdown
	*
	* @param array $args
	*/
	public function display_select( $args ) {
		$type   = $args['type'];
		$id     = $args['label_for'];
		$name   = $args['name'];
		$desc   = $args['desc'];
		if ( ! isset( $args['constant'] ) || ! defined( $args['constant'] ) ) {
			$current_value = get_option( $name );

			echo sprintf( '<div class="select"><select id="%1$s" name="%2$s"><option value="">- Select one -</option>',
				esc_attr( $id ),
				esc_attr( $name )
			);

			foreach ( $args['items'] as $key => $value ) {
				$text = $value['text'];
				$value = $value['value'];
				$selected = '';
				if ( $key === $current_value || $value === $current_value ) {
					$selected = ' selected';
				}

				echo sprintf( '<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $value ),
					esc_attr( $selected ),
					esc_html( $text )
				);

			}
			echo '</select>';
			if ( '' !== $desc ) {
				echo sprintf( '<p class="description">%1$s</p>',
					esc_html( $desc )
				);
			}
			echo '</div>';
		} else {
			echo sprintf( '<p><code>%1$s</code></p>',
				esc_html__( 'Defined in wp-config.php', 'form-processor-mailchimp' )
			);
		}
	}

	/**
	* Default display for <a href> links
	*
	* @param array $args
	*/
	public function display_link( $args ) {
		$label   = $args['label'];
		$desc   = $args['desc'];
		$url = $args['url'];
		if ( isset( $args['link_class'] ) ) {
			echo sprintf( '<p><a class="%1$s" href="%2$s">%3$s</a></p>',
				esc_attr( $args['link_class'] ),
				esc_url( $url ),
				esc_html( $label )
			);
		} else {
			echo sprintf( '<p><a href="%1$s">%2$s</a></p>',
				esc_url( $url ),
				esc_html( $label )
			);
		}

		if ( '' !== $desc ) {
			echo sprintf( '<p class="description">%1$s</p>',
				esc_html( $desc )
			);
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
