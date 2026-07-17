<?php
/**
 * Plugin Name: Hidden Word Bible Lessons
 * Plugin URI: https://wordpress.org/plugins/hidden-word-bible-lessons/
 * Description: A Bible discipleship plugin with up to 500 NIV verses, deep-dive lessons, historical context, memorization tools, and discussion prompts.
 * Version: 1.7.0
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
define( 'HWBL_VERSION', '1.7.0' );
define( 'HWBL_PLUGIN_FILE', __FILE__ );
define( 'HWBL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HWBL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HWBL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'HWBL_MAX_NIV_VERSES', 500 );
define( 'HWBL_MAX_BUNDLED_VERSES', 500 );
define( 'HWBL_CURRICULUM_DB_VERSION', '1.3.0' );

// Legacy constant aliases for older Premium builds during transition. Names
// are intentionally unprefixed with HWBL_ — older Premium builds read these
// exact constant names, so renaming them would break upgrades.
if ( ! defined( 'THW_VERSION' ) ) {
	define( 'THW_VERSION', HWBL_VERSION ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}
if ( ! defined( 'THW_PLUGIN_FILE' ) ) {
	define( 'THW_PLUGIN_FILE', HWBL_PLUGIN_FILE ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}
if ( ! defined( 'THW_PLUGIN_DIR' ) ) {
	define( 'THW_PLUGIN_DIR', HWBL_PLUGIN_DIR ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}
if ( ! defined( 'THW_PLUGIN_URL' ) ) {
	define( 'THW_PLUGIN_URL', HWBL_PLUGIN_URL ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}
if ( ! defined( 'THW_PLUGIN_BASENAME' ) ) {
	define( 'THW_PLUGIN_BASENAME', HWBL_PLUGIN_BASENAME ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}
if ( ! defined( 'THW_MAX_NIV_VERSES' ) ) {
	define( 'THW_MAX_NIV_VERSES', HWBL_MAX_NIV_VERSES ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}
if ( ! defined( 'THW_MAX_BUNDLED_VERSES' ) ) {
	define( 'THW_MAX_BUNDLED_VERSES', HWBL_MAX_BUNDLED_VERSES ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}
if ( ! defined( 'THW_CURRICULUM_DB_VERSION' ) ) {
	define( 'THW_CURRICULUM_DB_VERSION', HWBL_CURRICULUM_DB_VERSION ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}

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

/**
 * Legacy alias for Premium and older code. Name is intentionally unprefixed
 * with hwbl_/HWBL_ — older Premium builds call this exact global function
 * name, so renaming it would break upgrades.
 *
 * @return bool
 */
function thw_is_ai_enabled() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	return hwbl_is_ai_enabled();
}

/**
 * Legacy alias for Premium and older code. Name is intentionally unprefixed
 * with hwbl_/HWBL_ — older Premium builds call this exact global function
 * name, so renaming it would break upgrades.
 *
 * @return bool
 */
function thw_is_premium_active() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	return hwbl_is_premium_active();
}
