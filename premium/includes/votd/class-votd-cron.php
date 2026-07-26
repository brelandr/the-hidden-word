<?php
/**
 * VOTD cron scheduling (extracted from verse-of-the-day).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_VOTD_Cron
 */
class THW_Premium_VOTD_Cron {

	/**
	 * Schedule daily VOTD refresh after midnight.
	 */
	public static function schedule_daily_refresh() {
		THW_Premium_Verse_Of_The_Day::schedule_daily_refresh();
	}

	/**
	 * Run daily refresh cron callback.
	 */
	public static function run_daily_refresh() {
		THW_Premium_Verse_Of_The_Day::run_daily_refresh();
	}
}
