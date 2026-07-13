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
		flush_rewrite_rules();
	}
}
