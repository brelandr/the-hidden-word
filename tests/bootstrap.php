<?php
/**
 * PHPUnit bootstrap.
 *
 * @package The_Hidden_Word
 */

define( 'ABSPATH', true );
define( 'THW_TESTS', true );
define( 'THW_VERSION', '1.1.4' );
define( 'THW_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'THW_PLUGIN_URL', 'http://example.org/wp-content/plugins/the-hidden-word/' );
define( 'THW_PLUGIN_BASENAME', 'the-hidden-word/the-hidden-word.php' );
define( 'THW_MAX_NIV_VERSES', 500 );
define( 'THW_MAX_BUNDLED_VERSES', 500 );

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

require_once THW_PLUGIN_DIR . 'includes/class-books.php';
require_once THW_PLUGIN_DIR . 'includes/class-curriculum.php';
require_once THW_PLUGIN_DIR . 'includes/interface-translation-provider.php';
require_once THW_PLUGIN_DIR . 'includes/class-bundled-provider.php';
require_once THW_PLUGIN_DIR . 'includes/class-translation-service.php';
require_once THW_PLUGIN_DIR . 'includes/class-scheduler.php';
