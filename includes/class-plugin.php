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

		add_action( 'init', array( 'THW_Activator', 'maybe_upgrade_curriculum' ), 1 );
		add_action( 'admin_notices', array( $this, 'curriculum_upgrade_notice' ) );

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
	 * Notify admins when a curriculum upgrade seeded new lessons.
	 */
	public function curriculum_upgrade_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$created = get_transient( 'thw_curriculum_upgraded' );
		if ( false === $created ) {
			return;
		}

		delete_transient( 'thw_curriculum_upgraded' );

		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html(
			sprintf(
				/* translators: %d: number of new lessons seeded */
				__( 'The Hidden Word: added %d new bundled Bible lessons from the curriculum update.', 'the-hidden-word' ),
				(int) $created
			)
		);
		echo '</p></div>';
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
