<?php
/**
 * Plugin Name: The Hidden Word
 * Plugin URI: https://wordpress.org/plugins/the-hidden-word/
 * Description: A Bible discipleship plugin with up to 500 NIV verses, deep-dive lessons, historical context, memorization tools, and discussion prompts.
 * Version: 1.1.0
 * Author: Land Tech Web Designs, Corp
 * Author URI: https://landtechwebdesigns.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: the-hidden-word
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'THW_BOOTSTRAP_DONE' ) ) {
	return;
}

define( 'THW_BOOTSTRAP_DONE', true );
define( 'THW_VERSION', '1.1.0' );
define( 'THW_PLUGIN_FILE', __FILE__ );
define( 'THW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'THW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'THW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'THW_MAX_NIV_VERSES', 500 );
define( 'THW_MAX_BUNDLED_VERSES', 500 );
define( 'THW_CURRICULUM_DB_VERSION', '1.1.0' );

require_once THW_PLUGIN_DIR . 'includes/class-activator.php';
require_once THW_PLUGIN_DIR . 'includes/class-deactivator.php';

register_activation_hook( __FILE__, array( 'THW_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'THW_Deactivator', 'deactivate' ) );

/**
 * Autoload plugin classes.
 *
 * @param string $class Class name.
 */
function thw_autoload( $class ) {
	if ( 0 !== strpos( $class, 'THW_' ) ) {
		return;
	}

	$relative = strtolower( str_replace( '_', '-', $class ) );
	$relative = str_replace( 'thw-', 'class-', $relative );

	$paths = array(
		THW_PLUGIN_DIR . 'includes/' . $relative . '.php',
		THW_PLUGIN_DIR . 'admin/' . $relative . '.php',
		THW_PLUGIN_DIR . 'public/' . $relative . '.php',
	);

	foreach ( $paths as $path ) {
		if ( is_readable( $path ) ) {
			require_once $path;
			return;
		}
	}
}

spl_autoload_register( 'thw_autoload' );

/**
 * Initialize the plugin.
 */
function thw_init() {
	$plugin = new THW_Plugin();
	$plugin->run();
}

add_action( 'plugins_loaded', 'thw_init' );

/**
 * Check if premium add-on is active and licensed.
 *
 * @return bool
 */
function thw_is_premium_active() {
	return defined( 'THW_PREMIUM_VERSION' ) && class_exists( 'THW_Premium' );
}
