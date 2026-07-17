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
	 * @param string $key   Query key.
	 * @param mixed  $value Query value.
	 * @return string
	 */
	function add_query_arg( $key, $value ) {
		return '?' . $key . '=' . $value;
	}
}

require_once HWBL_PLUGIN_DIR . 'includes/class-books.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-curriculum.php';
require_once HWBL_PLUGIN_DIR . 'includes/interface-translation-provider.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-bundled-provider.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-helloao-provider.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-bible-reader.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-verse-memorize.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-translation-service.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-scheduler.php';
