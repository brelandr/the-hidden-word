<?php
/**
 * Plugin Name: Hidden Word Bible Lessons
 * Plugin URI: https://wordpress.org/plugins/hidden-word-bible-lessons/
 * Description: Bible discipleship with 500 NIV lessons, memorization (SM-2), Bible reader, verse of the day, digests, AI study tools (BYOK), multi-translation APIs, PDF guides, and more — all free.
 * Version: 2.3.32
 * Author: Land Tech Web Designs, Corp
 * Author URI: https://landtechwebdesigns.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hidden-word-bible-lessons
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'HWBL_BOOTSTRAP_DONE' ) ) {
	return;
}

define( 'HWBL_BOOTSTRAP_DONE', true );
define( 'HWBL_VERSION', '2.3.32' );
define( 'HWBL_PLUGIN_FILE', __FILE__ );
define( 'HWBL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HWBL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HWBL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'HWBL_MAX_NIV_VERSES', 500 );
define( 'HWBL_MAX_BUNDLED_VERSES', 500 );
define( 'HWBL_CURRICULUM_DB_VERSION', '1.3.0' );
// Former Premium add-on is bundled under premium/ and always enabled.
define( 'HWBL_INTEGRATED_PREMIUM', true );

require_once HWBL_PLUGIN_DIR . 'includes/class-activator.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-deactivator.php';

register_activation_hook( __FILE__, array( 'HWBL_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HWBL_Deactivator', 'deactivate' ) );

/**
 * Autoload plugin classes.
 *
 * @param string $class Class name.
 */
function hwbl_autoload( $class ) {
	if ( 0 !== strpos( $class, 'HWBL_' ) ) {
		return;
	}

	$slug = strtolower( str_replace( '_', '-', $class ) );
	$slug = str_replace( 'hwbl-', '', $slug );

	$paths = array(
		HWBL_PLUGIN_DIR . 'includes/class-' . $slug . '.php',
		HWBL_PLUGIN_DIR . 'includes/interface-' . $slug . '.php',
		HWBL_PLUGIN_DIR . 'admin/class-' . $slug . '.php',
		HWBL_PLUGIN_DIR . 'public/class-' . $slug . '.php',
	);

	foreach ( $paths as $path ) {
		if ( is_readable( $path ) ) {
			require_once $path;
			return;
		}
	}
}

spl_autoload_register( 'hwbl_autoload' );

require_once HWBL_PLUGIN_DIR . 'includes/class-plan-study-styles.php';
require_once HWBL_PLUGIN_DIR . 'includes/class-integrated-premium-loader.php';
add_action( 'plugins_loaded', array( 'HWBL_Integrated_Premium_Loader', 'maybe_load' ), 5 );

/**
 * Initialize the plugin.
 */
function hwbl_init() {
	$plugin = new HWBL_Plugin();
	$plugin->run();
}

add_action( 'plugins_loaded', 'hwbl_init' );

/**
 * Check if premium add-on is active and licensed.
 *
 * @return bool
 */
function hwbl_is_premium_active() {
	return defined( 'THW_PREMIUM_VERSION' ) && class_exists( 'THW_Premium' );
}

/**
 * Whether Premium features should run (licensed add-on or integrated merge mode).
 *
 * @return bool
 */
function hwbl_premium_features_enabled() {
	if ( defined( 'HWBL_INTEGRATED_PREMIUM' ) && HWBL_INTEGRATED_PREMIUM ) {
		return true;
	}

	if ( apply_filters( 'hwbl_integrated_premium', false ) ) {
		return true;
	}

	if ( ! class_exists( 'THW_Premium_License' ) ) {
		return false;
	}

	return THW_Premium_License::is_licensed();
}

/**
 * Whether front-end AI features are enabled in site settings.
 *
 * Premium handles provider configuration and execution when licensed.
 *
 * @return bool
 */
function hwbl_is_ai_enabled() {
	$enabled = get_option( 'hwbl_ai_enabled', null );
	if ( null === $enabled ) {
		$enabled = get_option( 'thw_ai_enabled', false );
	}
	return (bool) apply_filters( 'hwbl_ai_enabled', (bool) $enabled );
}
