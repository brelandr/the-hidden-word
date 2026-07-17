<?php
/**
 * Front-end cache compatibility for schedule-driven content.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Cache
 */
class HWBL_Cache {

	/**
	 * Tell common page-cache plugins not to serve a cached copy of this page.
	 *
	 * @param string $reason Short label for cache plugins that log bypass reasons.
	 */
	public static function mark_page_uncacheable( $reason = 'hwbl_schedule' ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( 'DONOTCACHEOBJECT', true );
		}

		/**
		 * LiteSpeed Cache and compatible plugins.
		 *
		 * @param string $reason Bypass reason.
		 */
		do_action( 'litespeed_control_set_nocache', $reason );

		if ( function_exists( 'w3tc_pgcache_flush' ) && ! defined( 'W3TC_PGCACHE' ) ) {
			// W3 Total Cache honors DONOTCACHEPAGE when defined before output.
		}
	}
}
