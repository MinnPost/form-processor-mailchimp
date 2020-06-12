<?php

/**
 * Integrate with Akismet library
 *
 * @package Form_Processor_Mailchimp
 */
class Form_Processor_Mailchimp_Akismet {

	public $option_prefix;
	public $version;
	public $slug;
	public $logging;

	/**
	* Constructor which sets up akismet integration
	*
	*/
	public function __construct() {
		$this->option_prefix = form_processor_mailchimp()->option_prefix;
		$this->version       = form_processor_mailchimp()->version;
		$this->slug          = form_processor_mailchimp()->slug;
		$this->logging       = form_processor_mailchimp()->logging;

		$this->add_actions();
	}

	public function add_actions() {
		add_filter( $this->option_prefix . 'check_spam', array( $this, 'akismet' ), 10, 2 );
	}

	/**
	** Akismet Filter
	** Akismet API: http://akismet.com/development/api/
	**/
	public function akismet( $spam, $params ) {

		$check = filter_var( get_option( $this->option_prefix . 'check_akismet', false ), FILTER_VALIDATE_BOOLEAN );

		if ( false === $check ) {
			return false;
		}

		if ( true === $spam ) {
			return $spam;
		}

		if ( ! $this->akismet_is_available() ) {
			return false;
		}

		$params = $this->akismet_submitted_params( $params );

		if ( ! $params ) {
			return false;
		}

		$c = array();

		$name = '';
		if ( isset( $params['first_name'] ) ) {
			$name .= $params['first_name'];
		}
		if ( isset( $params['first_name'] ) && isset( $params['last_name'] ) ) {
			$name .= ' ';
		}
		if ( isset( $params['last_name'] ) ) {
			$name .= $params['last_name'];
		}

		$c['comment_author']       = $name;
		$c['comment_author_email'] = $params['email_address'];
		$c['comment_author_url']   = $params['author_url'];
		$c['comment_content']      = $params['content'];

		$c['blog']         = get_option( 'home' );
		$c['blog_lang']    = get_locale();
		$c['blog_charset'] = get_option( 'blog_charset' );
		$c['user_ip']      = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$c['user_agent']   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$c['referrer']     = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';

		// https://akismet.com/development/api/#comment-check
		$c['comment_type'] = 'signup';

		if ( false !== get_permalink() ) {
			$c['permalink'] = $permalink;
		}

		$ignore = array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW' );

		foreach ( $_SERVER as $key => $value ) {
			if ( ! in_array( $key, (array) $ignore, true ) ) {
				$c[ "$key" ] = $value;
			}
		}

		return $this->akismet_comment_check( $c );
	}

	public function akismet_is_available() {
		if ( is_callable( array( 'Akismet', 'get_api_key' ) ) ) { // Akismet v3.0+
			return (bool) Akismet::get_api_key();
		}

		if ( function_exists( 'akismet_get_key' ) ) {
			return (bool) akismet_get_key();
		}

		return false;
	}

	private function akismet_submitted_params( $posted_data ) {

		$params = array(
			'email_address' => '',
			'email'         => '',
			'first_name'    => '',
			'last_name'     => '',
			'author_url'    => '',
			'content'       => '',
		);

		$has_akismet_option = false;

		foreach ( (array) $posted_data as $key => $value ) {
			if ( 'submit' === $key || 'password' === $key || '_wpnonce' === $key ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			$value = trim( $value );

			if ( 0 === strlen( $value ) ) {
				continue;
			}

			if ( in_array( $key, array_keys( $params ), true ) ) {
				$params[ $key ] = $value;
			}

			$params['content'] .= "\n\n" . $value;
		}

		if ( '' === $params['email_address'] && '' !== $params['email'] ) {
			$params['email_address'] = $params['email'];
		}

		$params['content'] = trim( $params['content'] );

		return $params;
	}

	public function akismet_comment_check( $comment ) {
		global $akismet_api_host, $akismet_api_port;

		$spam         = false;
		$query_string = $this->build_akismet_query( $comment );

		if ( is_callable( array( 'Akismet', 'http_post' ) ) ) { // Akismet v3.0+
			$response = Akismet::http_post( $query_string, 'comment-check' );
		} else {
			$response = akismet_http_post(
				$query_string,
				$akismet_api_host,
				'/1.1/comment-check',
				$akismet_api_port
			);
		}

		if ( 'true' === $response[1] ) {
			$spam = true;
		}

		$submission->akismet = array(
			'comment' => $comment,
			'spam'    => $spam,
		);

		return $spam;
	}

	private function build_akismet_query( $args, $key = '' ) {
		$sep = '&';
		$ret = array();

		foreach ( (array) $args as $k => $v ) {
			$k = urlencode( $k );

			if ( ! empty( $key ) ) {
				$k = $key . '%5B' . $k . '%5D';
			}

			if ( null === $v ) {
				continue;
			} elseif ( false === $v ) {
				$v = '0';
			}

			if ( is_array( $v ) || is_object( $v ) ) {
				array_push( $ret, wpcf7_build_query( $v, $k ) );
			} else {
				array_push( $ret, $k . '=' . urlencode( $v ) );
			}
		}
		return implode( $sep, $ret );
	}
}
