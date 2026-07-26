<?php
/**
 * VOTD settings tab (extracted from premium settings).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_Settings_VOTD
 */
class THW_Premium_Settings_VOTD {

	/**
	 * Render VOTD-related settings fields.
	 */
	public static function render_tab() {
		if ( ! class_exists( 'THW_Premium_Settings' ) || ! method_exists( 'THW_Premium_Settings', 'render_votd_tab' ) ) {
			return;
		}

		THW_Premium_Settings::render_votd_tab();
	}
}
