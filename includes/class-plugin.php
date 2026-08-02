<?php
/**
 * Main plugin orchestrator.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Plugin
 */
class HWBL_Plugin {

	/**
	 * Run the plugin.
	 */
	public function run() {
		add_action( 'init', array( 'HWBL_Activator', 'maybe_upgrade_curriculum' ), 1 );
		add_action( HWBL_Activator::SEED_CRON_HOOK, array( 'HWBL_Activator', 'process_seed_batch' ) );
		add_action( HWBL_Activator::SYNC_CRON_HOOK, array( 'HWBL_Activator', 'process_sync_batch' ) );
		add_action( 'admin_notices', array( $this, 'curriculum_upgrade_notice' ) );
		add_action( 'admin_notices', array( $this, 'curriculum_content_sync_notice' ) );
		add_action( 'admin_notices', array( $this, 'curriculum_seeding_notice' ) );
		add_action( 'admin_notices', array( $this, 'duplicate_premium_plugin_notice' ) );

		new HWBL_CPT_Lesson();
		new HWBL_Lesson_Meta();
		new HWBL_Settings();
		new HWBL_Scheduler();
		HWBL_Local_Bible_Importer::init();
		HWBL_Local_Bible_Provider::init();
		HWBL_Bible_Reader::init();
		HWBL_Bible_Places::init();
		HWBL_Bible_Concordance::init();
		HWBL_User_Preferences::init();
		HWBL_Verse_Memorize::init();
		HWBL_Memorization_SRS::init();
		HWBL_REST_Namespace_Bridge::init();
		HWBL_App_Config::init();
		HWBL_Church_Network::init();
		HWBL_App_Connect::init();
		HWBL_Community_Safety::init();
		HWBL_Account::init();
		HWBL_Push_Notifications::init();
		HWBL_Abilities::init();
		HWBL_Command_Palette::init();
		HWBL_Email_Verification::init();
		new HWBL_Translation_Service();
		new HWBL_Shortcodes();
		new HWBL_Blocks();
		HWBL_Lesson_List::init();
		new HWBL_Public();

		if ( is_admin() ) {
			new HWBL_Admin();
			new HWBL_Local_Bibles_Admin();
		}

		$this->init_engagement_modules();

		do_action( 'hwbl_register_premium_features' );
	}

	/**
	 * Phase 5 engagement modules (available when Premium or integrated mode is active).
	 */
	private function init_engagement_modules() {
		$modules = array(
			'HWBL_Cohort_Leaderboard',
			'HWBL_AI_Assistant_Unified',
			'HWBL_Personalized_Digest',
			'HWBL_Translation_Comparison',
			'HWBL_Memorization_Audio',
			'HWBL_PWA',
		);

		foreach ( $modules as $class ) {
			if ( class_exists( $class ) && method_exists( $class, 'init' ) ) {
				call_user_func( array( $class, 'init' ) );
			}
		}
	}

	/**
	 * Whether the current admin screen should show curriculum notices.
	 *
	 * @return bool
	 */
	private function should_show_curriculum_notice() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		$allowed = array( 'dashboard', 'plugins', 'hwbl_lesson', 'edit-hwbl_lesson', 'hwbl_lesson_page_hwbl-settings' );
		if ( in_array( $screen->id, $allowed, true ) ) {
			return true;
		}

		if ( 'hwbl_lesson' === $screen->post_type ) {
			return true;
		}

		return false !== strpos( (string) $screen->id, 'hwbl' );
	}

	/**
	 * Warn if the separate Premium plugin is still active alongside the merge.
	 */
	public function duplicate_premium_plugin_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! defined( 'HWBL_INTEGRATED_PREMIUM' ) || ! HWBL_INTEGRATED_PREMIUM ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$legacy = 'the-hidden-word-premium/the-hidden-word-premium.php';
		$just_deactivated = (bool) get_transient( 'hwbl_legacy_premium_deactivated' );
		if ( $just_deactivated ) {
			delete_transient( 'hwbl_legacy_premium_deactivated' );
			echo '<div class="notice notice-warning is-dismissible"><p>';
			echo esc_html__(
				'Hidden Word Bible Lessons already includes former Premium features, so the separate “The Hidden Word Premium” plugin was deactivated. You can delete it from the Plugins screen.',
				'hidden-word-bible-lessons'
			);
			echo '</p></div>';
			return;
		}

		if ( ! is_plugin_active( $legacy ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__(
			'Hidden Word Bible Lessons now includes all former Premium features. Please deactivate and delete the separate “The Hidden Word Premium” plugin to avoid conflicts.',
			'hidden-word-bible-lessons'
		);
		echo '</p></div>';
	}

	/**
	 * Notify admins when bundled lesson content was backfilled.
	 */
	public function curriculum_content_sync_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! $this->should_show_curriculum_notice() ) {
			return;
		}

		$updated = get_transient( 'hwbl_curriculum_content_synced' );
		if ( false === $updated ) {
			return;
		}

		delete_transient( 'hwbl_curriculum_content_synced' );

		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html(
			sprintf(
				/* translators: %d: number of lessons updated */
				__( 'Hidden Word Bible Lessons: added historical context, narrative background, and discussion questions to %d bundled lessons.', 'hidden-word-bible-lessons' ),
				(int) $updated
			)
		);
		echo '</p></div>';
	}

	/**
	 * Notify admins when a curriculum upgrade seeded new lessons.
	 */
	public function curriculum_upgrade_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! $this->should_show_curriculum_notice() ) {
			return;
		}

		$created = get_transient( 'hwbl_curriculum_upgraded' );
		if ( false === $created ) {
			return;
		}

		delete_transient( 'hwbl_curriculum_upgraded' );

		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html(
			sprintf(
				/* translators: %d: number of new lessons seeded */
				__( 'Hidden Word Bible Lessons: added %d new bundled Bible lessons from the curriculum update.', 'hidden-word-bible-lessons' ),
				(int) $created
			)
		);

		$demo_page = HWBL_Activator::get_demo_page();
		if ( $demo_page instanceof WP_Post ) {
			$link_label = class_exists( 'HWBL_Scheduler' )
				? HWBL_Scheduler::get_schedule_phrase( 'memorize' )
				: __( "Today's Verse to Memorize", 'hidden-word-bible-lessons' );
			echo ' ';
			echo wp_kses(
				sprintf(
					/* translators: 1: starter page URL, 2: link label */
					__( 'View the starter page: <a href="%1$s">%2$s</a>', 'hidden-word-bible-lessons' ),
					esc_url( get_permalink( $demo_page ) ),
					esc_html( $link_label )
				),
				array(
					'a' => array(
						'href' => true,
					),
				)
			);
		}

		echo '</p></div>';
	}

	/**
	 * Notify admins that the bundled curriculum is still seeding in the
	 * background, so a mostly-empty lesson list right after activation isn't
	 * mistaken for a bug.
	 */
	public function curriculum_seeding_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! $this->should_show_curriculum_notice() ) {
			return;
		}

		$progress = HWBL_Activator::get_seed_progress();
		if ( null === $progress ) {
			return;
		}

		$total = $progress['created'] + $progress['remaining'];

		echo '<div class="notice notice-info is-dismissible"><p>';
		echo esc_html(
			sprintf(
				/* translators: 1: lessons added so far, 2: total lessons being seeded */
				__( 'Hidden Word Bible Lessons: adding the bundled Bible lesson curriculum in the background (%1$d of %2$d added so far). This happens gradually to avoid slowing down your site — no action needed.', 'hidden-word-bible-lessons' ),
				$progress['created'],
				$total
			)
		);
		echo '</p></div>';
	}
}
