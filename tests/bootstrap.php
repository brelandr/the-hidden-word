<?php
/**
 * PHPUnit bootstrap.
 *
 * @package Hidden_Word_Bible_Lessons
 */

define( 'ABSPATH', true );
define( 'HWBL_TESTS', true );
define( 'HWBL_VERSION', '1.1.4' );
define( 'HWBL_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'HWBL_PLUGIN_URL', 'http://example.org/wp-content/plugins/hidden-word-bible-lessons/' );
define( 'HWBL_PLUGIN_BASENAME', 'hidden-word-bible-lessons/hidden-word-bible-lessons.php' );
define( 'HWBL_MAX_NIV_VERSES', 500 );
define( 'HWBL_MAX_BUNDLED_VERSES', 500 );

if ( ! function_exists( '__' ) ) {
	/**
	 * Test double for WordPress i18n.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * @param string $key Key.
	 * @return string
	 */
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param string $hook     Hook.
	 * @param mixed  $value    Value.
	 * @param mixed  ...$extra Extra args.
	 * @return mixed
	 */
	function apply_filters( $hook, $value, ...$extra ) {
		unset( $hook, $extra );
		return $value;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * @param string   $hook     Hook.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted args.
	 * @return true
	 */
	function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
		unset( $hook, $callback, $priority, $args );
		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * @param string   $hook     Hook.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted args.
	 * @return true
	 */
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
		unset( $hook, $callback, $priority, $args );
		return true;
	}
}

if ( ! class_exists( 'HWBL_Test_Options' ) ) {
	/**
	 * In-memory option store for unit tests.
	 */
	class HWBL_Test_Options {
		/**
		 * @var array<string, mixed>
		 */
		public static $options = array();
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $key     Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( $key, $default = false ) {
		if ( array_key_exists( $key, HWBL_Test_Options::$options ) ) {
			return HWBL_Test_Options::$options[ $key ];
		}
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * @param string $key   Option key.
	 * @param mixed  $value Value.
	 */
	function update_option( $key, $value ) {
		HWBL_Test_Options::$options[ $key ] = $value;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * @param string $key Option key.
	 */
	function delete_option( $key ) {
		unset( HWBL_Test_Options::$options[ $key ] );
	}
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * @param string $key Key.
	 * @return mixed
	 */
	function get_transient( $key ) {
		return HWBL_Test_Options::$options[ 'transient_' . $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * @param string $key        Key.
	 * @param mixed  $value      Value.
	 * @param int    $expiration Expiration.
	 * @return bool
	 */
	function set_transient( $key, $value, $expiration = 0 ) {
		unset( $expiration );
		HWBL_Test_Options::$options[ 'transient_' . $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * @param string $key Key.
	 * @return bool
	 */
	function delete_transient( $key ) {
		unset( HWBL_Test_Options::$options[ 'transient_' . $key ] );
		return true;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = '' ) {
		unset( $domain );
		return $text;
	}
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( $text );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return esc_url( $url );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Test double for WordPress JSON helper.
	 *
	 * @param mixed $data    Data to encode.
	 * @param int   $options Optional json_encode flags.
	 * @param int   $depth   Maximum depth.
	 * @return string|false
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Test bootstrap stub.
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		/**
		 * @var mixed
		 */
		public $data;

		/**
		 * @var int
		 */
		public $status;

		/**
		 * @param mixed $data   Data.
		 * @param int   $status Status code.
		 */
		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Minimal WordPress add_query_arg double.
	 *
	 * @param mixed ...$args Key/value, array+url, or key/value/url.
	 * @return string
	 */
	function add_query_arg( ...$args ) {
		$url  = '';
		$args_list = $args;
		if ( count( $args_list ) >= 3 && is_string( $args_list[0] ) ) {
			$url = (string) array_pop( $args_list );
			$params = array( (string) $args_list[0] => $args_list[1] );
		} elseif ( count( $args_list ) === 2 && is_array( $args_list[0] ) ) {
			$params = $args_list[0];
			$url    = (string) $args_list[1];
		} elseif ( count( $args_list ) === 2 ) {
			$params = array( (string) $args_list[0] => $args_list[1] );
		} else {
			$params = is_array( $args_list[0] ?? null ) ? $args_list[0] : array();
		}

		$query = array();
		foreach ( $params as $key => $value ) {
			$query[] = rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
		}
		$qs = implode( '&', $query );
		if ( '' === $url ) {
			return '?' . $qs;
		}
		$sep = false === strpos( $url, '?' ) ? '?' : '&';
		return $url . $sep . $qs;
	}
}

if ( ! function_exists( 'delete_user_meta' ) ) {
	/**
	 * @param int    $user_id User ID.
	 * @param string $key     Meta key.
	 * @return bool
	 */
	function delete_user_meta( $user_id, $key ) {
		$user_id = (int) $user_id;
		unset( HWBL_Test_User_Meta::$meta[ $user_id ][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_user_by' ) ) {
	/**
	 * @param string     $field Field.
	 * @param string|int $value Value.
	 * @return WP_User|false
	 */
	function get_user_by( $field, $value ) {
		$users = isset( $GLOBALS['hwbl_test_users'] ) && is_array( $GLOBALS['hwbl_test_users'] )
			? $GLOBALS['hwbl_test_users']
			: array();
		foreach ( $users as $user ) {
			if ( ! $user instanceof WP_User ) {
				continue;
			}
			if ( 'id' === $field && (int) $user->ID === (int) $value ) {
				return $user;
			}
			if ( 'login' === $field && $user->user_login === (string) $value ) {
				return $user;
			}
			if ( 'email' === $field && $user->user_email === (string) $value ) {
				return $user;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wp_login_url' ) ) {
	/**
	 * @param string $redirect Redirect.
	 * @return string
	 */
	function wp_login_url( $redirect = '' ) {
		unset( $redirect );
		return home_url( '/wp-login.php' );
	}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
	/**
	 * @param string $location Location.
	 * @return void
	 */
	function wp_safe_redirect( $location ) {
		$GLOBALS['hwbl_test_redirect'] = (string) $location;
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * @param mixed $value Value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	/**
	 * @return int
	 */
	function get_current_user_id() {
		return isset( $GLOBALS['hwbl_test_user_id'] ) ? (int) $GLOBALS['hwbl_test_user_id'] : 0;
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	/**
	 * @return bool
	 */
	function is_user_logged_in() {
		return get_current_user_id() > 0;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * @param string $cap Capability.
	 * @return bool
	 */
	function current_user_can( $cap ) {
		unset( $cap );
		return get_current_user_id() > 0;
	}
}

if ( ! class_exists( 'HWBL_Test_User_Meta' ) ) {
	/**
	 * In-memory user meta for tests.
	 */
	class HWBL_Test_User_Meta {
		/**
		 * @var array<int, array<string, mixed>>
		 */
		public static $meta = array();
	}
}

if ( ! function_exists( 'get_user_meta' ) ) {
	/**
	 * @param int    $user_id User ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Single.
	 * @return mixed
	 */
	function get_user_meta( $user_id, $key = '', $single = false ) {
		$user_id = (int) $user_id;
		$value   = HWBL_Test_User_Meta::$meta[ $user_id ][ $key ] ?? ( $single ? '' : array() );
		return $single ? $value : (array) $value;
	}
}

if ( ! function_exists( 'update_user_meta' ) ) {
	/**
	 * @param int    $user_id User ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Value.
	 * @return bool
	 */
	function update_user_meta( $user_id, $key, $value ) {
		HWBL_Test_User_Meta::$meta[ (int) $user_id ][ $key ] = $value;
		return true;
	}
}

if ( ! class_exists( 'WP_User' ) ) {
	/**
	 * Minimal user double.
	 */
	class WP_User {
		/**
		 * @var int
		 */
		public $ID;

		/**
		 * @var string
		 */
		public $user_login;

		/**
		 * @var string
		 */
		public $user_email;

		/**
		 * @var string
		 */
		public $display_name;

		/**
		 * @param int    $id    ID.
		 * @param string $login Login.
		 * @param string $email Email.
		 */
		public function __construct( $id = 1, $login = 'member', $email = 'member@example.org' ) {
			$this->ID           = (int) $id;
			$this->user_login   = $login;
			$this->user_email   = $email;
			$this->display_name = $login;
		}
	}
}

if ( ! function_exists( 'get_userdata' ) ) {
	/**
	 * @param int $user_id User ID.
	 * @return WP_User|false
	 */
	function get_userdata( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return false;
		}
		if ( isset( $GLOBALS['hwbl_test_users'][ $user_id ] ) ) {
			return $GLOBALS['hwbl_test_users'][ $user_id ];
		}
		return new WP_User( $user_id, 'user' . $user_id, 'user' . $user_id . '@example.org' );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	/**
	 * @param string $email Email.
	 * @return string|false
	 */
	function is_email( $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
	}
}

if ( ! function_exists( 'wp_mail' ) ) {
	/**
	 * @param string|array $to      To.
	 * @param string       $subject Subject.
	 * @param string       $message Message.
	 * @return bool
	 */
	function wp_mail( $to, $subject, $message ) {
		$GLOBALS['hwbl_test_mail'][] = array( $to, $subject, $message );
		return true;
	}
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	/**
	 * @param array $postarr Post array.
	 * @param bool  $wp_error Error object.
	 * @return int|WP_Error
	 */
	function wp_insert_post( $postarr, $wp_error = false ) {
		unset( $wp_error );
		$id = isset( $GLOBALS['hwbl_test_next_post_id'] ) ? (int) $GLOBALS['hwbl_test_next_post_id'] : 100;
		$GLOBALS['hwbl_test_next_post_id'] = $id + 1;
		$GLOBALS['hwbl_test_posts'][ $id ] = $postarr;
		return $id;
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	/**
	 * @param int    $post_id Post ID.
	 * @param string $key     Key.
	 * @param mixed  $value   Value.
	 * @return bool
	 */
	function update_post_meta( $post_id, $key, $value ) {
		$GLOBALS['hwbl_test_post_meta'][ (int) $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * @param int    $post_id Post ID.
	 * @param string $key     Key.
	 * @param bool   $single  Single.
	 * @return mixed
	 */
	function get_post_meta( $post_id, $key = '', $single = false ) {
		$value = $GLOBALS['hwbl_test_post_meta'][ (int) $post_id ][ $key ] ?? ( $single ? '' : array() );
		return $single ? $value : (array) $value;
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * @param string $str Input.
	 * @return string
	 */
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * @param string $path Path.
	 * @return string
	 */
	function admin_url( $path = '' ) {
		return 'https://example.org/wp-admin/' . ltrim( (string) $path, '/' );
	}
}

if ( ! function_exists( 'wp_specialchars_decode' ) ) {
	/**
	 * @param string $text Text.
	 * @param int    $quote Quote style.
	 * @return string
	 */
	function wp_specialchars_decode( $text, $quote = ENT_QUOTES ) {
		return html_entity_decode( (string) $text, $quote, 'UTF-8' );
	}
}

if ( ! function_exists( 'register_post_type' ) ) {
	/**
	 * @param string $post_type Post type.
	 * @param array  $args      Args.
	 * @return true
	 */
	function register_post_type( $post_type, $args = array() ) {
		unset( $post_type, $args );
		return true;
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	/**
	 * @param string $namespace Namespace.
	 * @param string $route     Route.
	 * @param array  $args      Args.
	 * @return true
	 */
	function register_rest_route( $namespace, $route, $args = array() ) {
		unset( $namespace, $route, $args );
		return true;
	}
}

if ( ! class_exists( 'WP_Application_Passwords' ) ) {
	/**
	 * Stub Application Passwords API.
	 */
	class WP_Application_Passwords {
		/**
		 * @var array<int, bool>
		 */
		public static $revoked = array();

		/**
		 * @var array<int, array<int, array{name:string,uuid:string,password:string}>>
		 */
		public static $passwords = array();

		/**
		 * @param int $user_id User ID.
		 * @return bool
		 */
		public static function delete_all_application_passwords( $user_id ) {
			self::$revoked[ (int) $user_id ] = true;
			self::$passwords[ (int) $user_id ] = array();
			return true;
		}

		/**
		 * @param int                  $user_id User ID.
		 * @param array<string, mixed> $args    Args.
		 * @return array{0:string,1:array<string,string>}|WP_Error
		 */
		public static function create_new_application_password( $user_id, $args = array() ) {
			$user_id  = (int) $user_id;
			$password = 'abcd efgh ijkl mnop';
			$uuid     = 'test-uuid-' . $user_id . '-' . count( self::$passwords[ $user_id ] ?? array() );
			$item     = array(
				'name'     => isset( $args['name'] ) ? (string) $args['name'] : 'App',
				'uuid'     => $uuid,
				'password' => $password,
			);
			self::$passwords[ $user_id ][] = $item;
			return array( $password, $item );
		}

		/**
		 * @param int $user_id User ID.
		 * @return array<int, array<string, string>>
		 */
		public static function get_user_application_passwords( $user_id ) {
			return isset( self::$passwords[ (int) $user_id ] ) ? self::$passwords[ (int) $user_id ] : array();
		}

		/**
		 * @param int    $user_id User ID.
		 * @param string $uuid    UUID.
		 * @return bool
		 */
		public static function delete_application_password( $user_id, $uuid ) {
			$user_id = (int) $user_id;
			if ( empty( self::$passwords[ $user_id ] ) ) {
				return false;
			}
			self::$passwords[ $user_id ] = array_values(
				array_filter(
					self::$passwords[ $user_id ],
					static function ( $item ) use ( $uuid ) {
						return ( $item['uuid'] ?? '' ) !== $uuid;
					}
				)
			);
			return true;
		}
	}
}

if ( ! function_exists( 'sanitize_user' ) ) {
	/**
	 * @param string $username Username.
	 * @param bool   $strict   Strict.
	 * @return string
	 */
	function sanitize_user( $username, $strict = false ) {
		$username = (string) $username;
		if ( $strict ) {
			$username = preg_replace( '/[^a-z0-9 _.\-@]/i', '', $username );
		}
		return trim( (string) $username );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	/**
	 * @param string $email Email.
	 * @return string
	 */
	function sanitize_email( $email ) {
		return filter_var( (string) $email, FILTER_SANITIZE_EMAIL ) ?: '';
	}
}

if ( ! function_exists( 'username_exists' ) ) {
	/**
	 * @param string $username Username.
	 * @return int|false
	 */
	function username_exists( $username ) {
		$username = (string) $username;
		if ( isset( $GLOBALS['hwbl_test_usernames'][ $username ] ) ) {
			return (int) $GLOBALS['hwbl_test_usernames'][ $username ];
		}
		return false;
	}
}

if ( ! function_exists( 'email_exists' ) ) {
	/**
	 * @param string $email Email.
	 * @return int|false
	 */
	function email_exists( $email ) {
		$email = (string) $email;
		if ( isset( $GLOBALS['hwbl_test_emails'][ $email ] ) ) {
			return (int) $GLOBALS['hwbl_test_emails'][ $email ];
		}
		return false;
	}
}

if ( ! function_exists( 'wp_insert_user' ) ) {
	/**
	 * @param array<string, mixed> $userdata User data.
	 * @return int|WP_Error
	 */
	function wp_insert_user( $userdata ) {
		$id = isset( $GLOBALS['hwbl_test_next_user_id'] ) ? (int) $GLOBALS['hwbl_test_next_user_id'] : 50;
		$GLOBALS['hwbl_test_next_user_id'] = $id + 1;
		$login = isset( $userdata['user_login'] ) ? (string) $userdata['user_login'] : 'user' . $id;
		$email = isset( $userdata['user_email'] ) ? (string) $userdata['user_email'] : $login . '@example.org';
		$user  = new WP_User( $id, $login, $email );
		$GLOBALS['hwbl_test_users'][ $id ] = $user;
		$GLOBALS['hwbl_test_usernames'][ $login ] = $id;
		$GLOBALS['hwbl_test_emails'][ $email ] = $id;
		$GLOBALS['hwbl_test_inserted_users'][] = $userdata;
		return $id;
	}
}



if ( ! function_exists( 'wp_remote_get' ) ) {
	/**
	 * @param string $url  URL.
	 * @param array  $args Args.
	 * @return array|WP_Error
	 */
	function wp_remote_get( $url, $args = array() ) {
		unset( $args );
		return array(
			'response' => array( 'code' => 404 ),
			'body'     => '',
			'url'      => (string) $url,
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * @param array|WP_Error $response Response.
	 * @return int
	 */
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_wp_error( $response ) ) {
			return 0;
		}
		return (int) ( $response['response']['code'] ?? 0 );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	/**
	 * @param array|WP_Error $response Response.
	 * @return string
	 */
	function wp_remote_retrieve_body( $response ) {
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return (string) ( $response['body'] ?? '' );
	}
}


if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * @param string $data Data.
	 * @return string
	 */
	function wp_kses_post( $data ) {
		return (string) $data;
	}
}

if ( ! function_exists( 'wpautop' ) ) {
	/**
	 * Minimal wpautop stub for unit tests.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function wpautop( $text ) {
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return '';
		}
		$parts = preg_split( '/\n\s*\n/', $text );
		if ( ! is_array( $parts ) ) {
			$parts = array( $text );
		}
		$html = '';
		foreach ( $parts as $part ) {
			$part = trim( (string) $part );
			if ( '' !== $part ) {
				$html .= '<p>' . $part . '</p>' . "\n";
			}
		}
		return $html;
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	/**
	 * @param string $scheme Scheme.
	 * @return string
	 */
	function wp_salt( $scheme = 'auth' ) {
		return 'hwbl-test-salt-' . (string) $scheme;
	}
}

if ( ! function_exists( 'home_url' ) ) {
	/**
	 * @param string $path Optional path.
	 * @return string
	 */
	function home_url( $path = '' ) {
		$base = isset( $GLOBALS['hwbl_test_home'] ) ? (string) $GLOBALS['hwbl_test_home'] : 'https://example.org';
		$path = (string) $path;
		if ( '' === $path ) {
			return $base;
		}
		return rtrim( $base, '/' ) . '/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * @param string $url       URL.
	 * @param int    $component Component.
	 * @return mixed
	 */
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	/**
	 * @param string $value Value.
	 * @return string
	 */
	function untrailingslashit( $value ) {
		return rtrim( (string) $value, '/\\' );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * @param string $value Value.
	 * @return string
	 */
	function trailingslashit( $value ) {
		return untrailingslashit( $value ) . '/';
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	/**
	 * @param string $show Info key.
	 * @return string
	 */
	function get_bloginfo( $show = '' ) {
		if ( 'name' === $show ) {
			return isset( $GLOBALS['hwbl_test_blog'] ) ? (string) $GLOBALS['hwbl_test_blog'] : 'Test Site';
		}
		return '';
	}
}

if ( ! function_exists( 'get_theme_mod' ) ) {
	/**
	 * @param string $name    Mod name.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_theme_mod( $name, $default = false ) {
		unset( $name );
		return $default;
	}
}

if ( ! function_exists( 'get_site_icon_url' ) ) {
	/**
	 * @param int $size Size.
	 * @return string
	 */
	function get_site_icon_url( $size = 512 ) {
		unset( $size );
		return '';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param string $str Input.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * @param mixed $value Value.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	/**
	 * @param int  $length              Length.
	 * @param bool $special_chars       Special.
	 * @param bool $extra_special_chars Extra.
	 * @return string
	 */
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		unset( $special_chars, $extra_special_chars );
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$out   = '';
		for ( $i = 0; $i < (int) $length; $i++ ) {
			$out .= $chars[ $i % strlen( $chars ) ];
		}
		return $out;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * @param mixed $thing Value.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}


if ( ! function_exists( 'rest_sanitize_boolean' ) ) {
	/**
	 * @param mixed $value Value.
	 * @return bool
	 */
	function rest_sanitize_boolean( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) ) {
			$value = strtolower( $value );
			if ( in_array( $value, array( 'false', '0', 'no', 'off', '' ), true ) ) {
				return false;
			}
			if ( in_array( $value, array( 'true', '1', 'yes', 'on' ), true ) ) {
				return true;
			}
		}
		return (bool) $value;
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	/**
	 * @param string $hook Hook.
	 * @return false
	 */
	function wp_next_scheduled( $hook ) {
		unset( $hook );
		return false;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	/**
	 * @param int    $timestamp Timestamp.
	 * @param string $hook      Hook.
	 * @return true
	 */
	function wp_schedule_single_event( $timestamp, $hook ) {
		unset( $timestamp, $hook );
		return true;
	}
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	/**
	 * @param string $hook Hook.
	 * @return void
	 */
	function wp_clear_scheduled_hook( $hook ) {
		unset( $hook );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error double.
	 */
	class WP_Error {
		/**
		 * @var string
		 */
		public $code;

		/**
		 * @var string
		 */
		public $message;

		/**
		 * @var mixed
		 */
		public $data;

		/**
		 * @param string|int $code    Code.
		 * @param string     $message Message.
		 * @param mixed      $data    Data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = (string) $code;
			$this->message = (string) $message;
			$this->data    = $data;
		}

		/**
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Minimal REST request double.
	 */
	class WP_REST_Request {
		/**
		 * @var array<string, mixed>
		 */
		private $params;

		/**
		 * @var array<string, mixed>|null
		 */
		private $json;

		/**
		 * @param array<string, mixed>      $params Params.
		 * @param array<string, mixed>|null $json   JSON body.
		 */
		public function __construct( $params = array(), $json = null ) {
			$this->params = is_array( $params ) ? $params : array();
			$this->json   = is_array( $json ) ? $json : null;
		}

		/**
		 * @param string $key Key.
		 * @return mixed
		 */
		public function get_param( $key ) {
			return array_key_exists( $key, $this->params ) ? $this->params[ $key ] : null;
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_params() {
			return $this->params;
		}

		/**
		 * @return array<string, mixed>|null
		 */
		public function get_json_params() {
			return $this->json;
		}
	}
}

require_once HWBL_PLUGIN_DIR . 'includes/class-books.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-curriculum.php';
require_once HWBL_PLUGIN_DIR . 'includes/interface-translation-provider.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-bundled-provider.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-helloao-provider.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-bible-reader.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-bible-places.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-bible-concordance.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-user-preferences.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-verse-memorize.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-memorization-audio.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-translation-service.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-scheduler.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-app-config.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-church-network.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-app-connect.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-community-safety.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-account.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-email-verification.php';
