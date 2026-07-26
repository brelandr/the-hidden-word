<?php
/**
 * License stub for the WordPress.org free plugin.
 *
 * Features are always available. No remote license checks or phone-home.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_License
 */
class THW_Premium_License {

	const OPTION_KEY     = 'thw_premium_license_key';
	const STATUS_KEY     = 'thw_premium_license_status';
	const OPTION_EMAIL   = 'thw_premium_license_email';
	const MESSAGE_KEY    = 'thw_premium_license_message';
	const LAST_CHECK_KEY = 'thw_premium_license_last_check';

	/**
	 * No-op: free plugin does not register license hooks.
	 */
	public static function init() {
	}

	/**
	 * Features are always available in the free WordPress.org build.
	 *
	 * @return bool
	 */
	public static function is_licensed() {
		return true;
	}

	/**
	 * No license form in the free WordPress.org build.
	 */
	public static function render_license_form() {
	}
}
