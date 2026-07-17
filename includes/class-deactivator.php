<?php
/**
 * Plugin deactivation.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Deactivator
 */
class HWBL_Deactivator {

	/**
	 * Deactivate plugin.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( HWBL_Activator::SEED_CRON_HOOK );
		flush_rewrite_rules();
	}
}
