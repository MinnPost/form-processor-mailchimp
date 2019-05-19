<?php

/**
 * WP_Logging implementation
 *
 * @package Form_Processor_Mailchimp
 */
class Form_Processor_Mailchimp_Logging extends WP_Logging {

	public $option_prefix;
	public $version;
	public $slug;

	public $enabled;
	public $statuses_to_log;

	private $schedule_name;

	public function __construct() {

		$this->option_prefix = form_processor_mailchimp()->option_prefix;
		$this->version       = form_processor_mailchimp()->version;
		$this->slug          = form_processor_mailchimp()->slug;

		$this->enabled         = get_option( $this->option_prefix . 'enable_logging', false );
		$this->statuses_to_log = get_option( $this->option_prefix . 'statuses_to_log', array() );
		$this->log_type        = 'mailchimp';

		$this->schedule_name = 'wp_logging_prune_routine';

		$this->add_actions();

	}

	private function add_actions() {
		if ( true === filter_var( $this->enabled, FILTER_VALIDATE_BOOLEAN ) ) {
			add_filter( 'cron_schedules', array( $this, 'add_prune_interval' ) );
			add_filter( 'wp_log_types', array( $this, 'set_log_types' ), 10, 1 );
			add_filter( 'wp_logging_should_we_prune', array( $this, 'set_prune_option' ), 10, 1 );
			add_filter( 'wp_logging_prune_when', array( $this, 'set_prune_age' ), 10, 1 );
			add_filter( 'wp_logging_prune_query_args', array( $this, 'set_prune_args' ), 10, 1 );
			add_filter( 'wp_logging_post_type_args', array( $this, 'set_log_visibility' ), 10, 1 );

			$schedule_unit   = get_option( $this->option_prefix . 'logs_how_often_unit', '' );
			$schedule_number = get_option( $this->option_prefix . 'logs_how_often_number', '' );
			$frequency       = $this->get_schedule_frequency( $schedule_unit, $schedule_number );
			$key             = $frequency['key'];

			if ( ! wp_next_scheduled( $this->schedule_name ) ) {
				wp_schedule_event( time(), $key, $this->schedule_name );
			}
		}
	}

	/**
	 * Set visibility for the post type
	 *
	 * @param array $log_args The post arguments
	 * @return array $log_args
	 */
	public function set_log_visibility( $log_args ) {
		// set public to true overrides the WP_DEBUG setting that is the default on the class
		// capabilities makes it so (currently) only admin users can see the log posts in their admin view
		// note: a public value of true is required to show Logs as a nav menu item on the admin.
		// however, if we don't set exclude_from_search to true and publicly_queryable to false, logs *can* appear in search results
		$log_args['public']              = true;
		$log_args['publicly_queryable']  = false;
		$log_args['exclude_from_search'] = true;
		$log_args['capabilities']        = array(
			'edit_post'          => 'manage_options',
			'read_post'          => 'manage_options',
			'delete_post'        => 'manage_options',
			'edit_posts'         => 'manage_options',
			'edit_others_posts'  => 'manage_options',
			'delete_posts'       => 'manage_options',
			'publish_posts'      => 'manage_options',
			'read_private_posts' => 'manage_options',
		);

		$log_args = apply_filters( $this->option_prefix . 'logging_post_type_args', $log_args );

		return $log_args;
	}

	/**
	 * Add interval to wp schedules based on admin settings
	 *
	 * @param array $schedules An array of scheduled cron items.
	 * @return array $frequency
	 */
	public function add_prune_interval( $schedules ) {

		$schedule_unit   = get_option( $this->option_prefix . 'logs_how_often_unit', '' );
		$schedule_number = get_option( $this->option_prefix . 'logs_how_often_number', '' );
		$frequency       = $this->get_schedule_frequency( $schedule_unit, $schedule_number );
		$key             = $frequency['key'];
		$seconds         = $frequency['seconds'];

		$schedules[ $key ] = array(
			'interval' => $seconds * $schedule_number,
			'display'  => 'Every ' . $schedule_number . ' ' . $schedule_unit,
		);

		return $schedules;

	}

	/**
	 * Convert the schedule frequency from the admin settings into an array
	 * interval must be in seconds for the class to use it
	 *
	 * @param string $unit A unit of time.
	 * @param number $number The number of those units.
	 * @return array
	 */
	public function get_schedule_frequency( $unit, $number ) {

		switch ( $unit ) {
			case 'minutes':
				$seconds = 60;
				break;
			case 'hours':
				$seconds = 3600;
				break;
			case 'days':
				$seconds = 86400;
				break;
			default:
				$seconds = 0;
		}

		$key = $unit . '_' . $number;

		return array(
			'key'     => $key,
			'seconds' => $seconds,
		);

	}

	/**
	 * Set terms for MailChimp logs
	 *
	 * @param array $terms An array of string log types in the WP_Logging class.
	 * @return array $terms
	 */
	public function set_log_types( $terms ) {
		$terms[] = $this->log_type;
		return $terms;
	}

	/**
	 * Should logs be pruned at all?
	 *
	 * @param string $should_we_prune Whether to prune old log items.
	 * @return string $should_we_prune Whether to prune old log items.
	 */
	public function set_prune_option( $should_we_prune ) {
		$should_we_prune = get_option( $this->option_prefix . 'prune_logs', $should_we_prune );
		if ( '1' === $should_we_prune ) {
			$should_we_prune = true;
		}
		return $should_we_prune;
	}

	/**
	 * Set how often to prune the MailChimp logs
	 *
	 * @param string $how_old How old the oldest non-pruned log items should be allowed to be.
	 * @return string $how_old
	 */
	public function set_prune_age( $how_old ) {
		$value = get_option( $this->option_prefix . 'logs_how_old', '' ) . ' ago';
		if ( '' !== $value ) {
			return $value;
		} else {
			return $how_old;
		}
	}

	/**
	 * Set arguments for only getting the MailChimp logs
	 *
	 * @param array $args Argument array for get_posts determining what posts are eligible for pruning.
	 * @return array $args
	 */
	public function set_prune_args( $args ) {
		$args['wp_log_type'] = $this->log_type;
		return $args;
	}

	/**
	 * Setup new log entry
	 *
	 * Check and see if we should log anything, and if so, send it to add()
	 *
	 * @access      public
	 * @since       1.0
	 *
	 * @param       string|array $title_or_params A log post title, or the full array of parameters
	 * @param       string $message The log message.
	 * @param       string|0 $trigger The type of log triggered. Usually one of: debug, notice, warning, error.
	 * @param       int $parent The parent WordPress object.
	 * @param       string $status The log status.
	 *
	 * @uses        self::add()
	 * @see         Form_Processor_Mailchimp_Logging::__construct()    the location of the bitmasks that define the logging triggers.
	 *
	 * @return      void
	 */
	public function setup( $title_or_params, $message = '', $trigger = 0, $parent = 0, $status = '' ) {

		if ( is_array( $title_or_params ) ) {
			$title   = $title_or_params['title'];
			$message = $title_or_params['message'];
			$trigger = $title_or_params['trigger'];
			$parent  = $title_or_params['parent'];
			$status  = $title_or_params['status'];
		} else {
			$title = $title_or_params;
		}

		//if ( '1' === $this->enabled && in_array( $status, maybe_unserialize( $this->statuses_to_log ), true ) ) {
		if ( '1' === $this->enabled ) {
			//$triggers_to_log = get_option( $this->option_prefix . 'triggers_to_log', array() );
			// if we force strict on this in_array, it fails because the mapping triggers are bit operators, as indicated in Object_Sync_Sf_Mapping class's method __construct()

			/*if ( in_array( $trigger, maybe_unserialize( $triggers_to_log ) ) || 0 === $trigger ) {
				$this->add( $title, $message, $parent );
			} elseif ( is_array( $trigger ) && array_intersect( $trigger, maybe_unserialize( $triggers_to_log ) ) ) {*/
			$this->add( $title, $message, $parent );
			//}
		}
	}

	/**
	 * Create new log entry
	 *
	 * This is just a simple and fast way to log something. Use self::insert_log()
	 * if you need to store custom meta data
	 *
	 * @access      public
	 * @since       1.0
	 *
	 * @param       string $title A log post title.
	 *
	 * @uses        self::insert_log()
	 * @param       string $message The log message.
	 * @param       int $parent The parent WordPress object.
	 * @param       string $type The type of log message; defaults to $this->log_type.
	 *
	 * @return      int The ID of the new log entry
	 */
	public static function add( $title = '', $message = '', $parent = 0, $type = '' ) {

		if ( '' === $type ) {
			$type = $this->log_type;
		}

		$log_data = array(
			'post_title'   => esc_html( $title ),
			'post_content' => wp_kses_post( $message ),
			'post_parent'  => absint( $parent ),
			'log_type'     => esc_attr( $type ),
		);

		return self::insert_log( $log_data );

	}

	/**
	 * Easily retrieves log items for a particular object ID
	 *
	 * @access      private
	 * @since       1.0
	 *
	 * @param       int $object_id A WordPress object ID.
	 * @param       string $type The type of log item; defaults to $this->log_type because that's the type of logs we create.
	 * @param       int $paged Which page of results do we want?
	 *
	 * @uses        self::get_connected_logs()
	 *
	 * @return      array
	 */
	public static function get_logs( $object_id = 0, $type = '', $paged = null ) {
		if ( '' === $type ) {
			$type = $this->log_type;
		}
		return self::get_connected_logs(
			array(
				'post_parent' => (int) $object_id,
				'paged'       => (int) $paged,
				'log_type'    => (string) $type,
			)
		);
	}

	/**
	 * Retrieve all connected logs
	 *
	 * Used for retrieving logs related to particular items, such as a specific purchase.
	 *
	 * @access  private
	 * @since   1.0
	 *
	 * @param   Array $args An array of arguments for get_posts().
	 *
	 * @uses    wp_parse_args()
	 * @uses    get_posts()
	 * @uses    get_query_var()
	 * @uses    self::valid_type()
	 *
	 * @return  array / false
	 */
	public static function get_connected_logs( $args = array() ) {

		$defaults = array(
			'post_parent'    => 0,
			'post_type'      => 'wp_log',
			'posts_per_page' => 10,
			'post_status'    => 'publish',
			'paged'          => get_query_var( 'paged' ),
			'log_type'       => $this->log_type,
		);

		$query_args = wp_parse_args( $args, $defaults );

		if ( $query_args['log_type'] && self::valid_type( $query_args['log_type'] ) ) {

			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'wp_log_type',
					'field'    => 'slug',
					'terms'    => $query_args['log_type'],
				),
			);

		}

		$logs = get_posts( $query_args );

		if ( $logs ) {
			return $logs;
		}

		// no logs found.
		return false;

	}


	/**
	 * Retrieves number of log entries connected to particular object ID
	 *
	 * @access  private
	 * @since   1.0
	 *
	 * @param       int $object_id A WordPress object ID.
	 * @param       string $type The type of log item; defaults to $this->log_type because that's the type of logs we create.
	 * @param       Array $meta_query A WordPress meta query, parseable by WP_Meta_Query.
	 *
	 * @uses    WP_Query()
	 * @uses    self::valid_type()
	 *
	 * @return  int
	 */
	public static function get_log_count( $object_id = 0, $type = '', $meta_query = null ) {

		if ( '' === $type ) {
			$type = $this->log_type;
		}

		$query_args = array(
			'post_parent'    => (int) $object_id,
			'post_type'      => 'wp_log',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
		);

		if ( ! empty( $type ) && self::valid_type( $type ) ) {

			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'wp_log_type',
					'field'    => 'slug',
					'terms'    => sanitize_key( $type ),
				),
			);

		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$logs = new WP_Query( $query_args );

		return (int) $logs->post_count;

	}

}