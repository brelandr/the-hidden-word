<?php
/**
 * Main plugin orchestrator.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Plugin
 */
class THW_Plugin {

	/**
	 * Run the plugin.
	 */
	public function run() {
		$this->load_textdomain();

		new THW_CPT_Lesson();
		new THW_Lesson_Meta();
		new THW_Settings();
		new THW_Scheduler();
		new THW_Translation_Service();
		new THW_Shortcodes();
		new THW_Blocks();
		new THW_Public();

		if ( is_admin() ) {
			new THW_Admin();
		}

		do_action( 'thw_register_premium_features' );
	}

	/**
	 * Load plugin text domain.
	 */
	private function load_textdomain() {
		load_plugin_textdomain(
			'the-hidden-word',
			false,
			dirname( THW_PLUGIN_BASENAME ) . '/languages'
		);
	}
}
