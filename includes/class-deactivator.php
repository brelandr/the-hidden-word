<?php
/**
 * Plugin deactivation.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Deactivator
 */
class THW_Deactivator {

	/**
	 * Deactivate plugin.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( THW_Activator::SEED_CRON_HOOK );
		flush_rewrite_rules();
	}
}
