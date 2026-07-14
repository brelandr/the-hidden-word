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
		add_action( 'init', array( 'THW_Activator', 'maybe_upgrade_curriculum' ), 1 );
		add_action( THW_Activator::SEED_CRON_HOOK, array( 'THW_Activator', 'process_seed_batch' ) );
		add_action( THW_Activator::SYNC_CRON_HOOK, array( 'THW_Activator', 'process_sync_batch' ) );
		add_action( 'admin_notices', array( $this, 'curriculum_upgrade_notice' ) );
		add_action( 'admin_notices', array( $this, 'curriculum_content_sync_notice' ) );
		add_action( 'admin_notices', array( $this, 'curriculum_seeding_notice' ) );

		new THW_CPT_Lesson();
		new THW_Lesson_Meta();
		new THW_Settings();
		new THW_Scheduler();
		new THW_Translation_Service();
		new THW_Shortcodes();
		new THW_Blocks();
		THW_Lesson_List::init();
		new THW_Public();

		if ( is_admin() ) {
			new THW_Admin();
		}

		do_action( 'thw_register_premium_features' );
	}

	/**
	 * Notify admins when bundled lesson content was backfilled.
	 */
	public function curriculum_content_sync_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$updated = get_transient( 'thw_curriculum_content_synced' );
		if ( false === $updated ) {
			return;
		}

		delete_transient( 'thw_curriculum_content_synced' );

		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html(
			sprintf(
				/* translators: %d: number of lessons updated */
				__( 'The Hidden Word: added historical context, narrative background, and discussion questions to %d bundled lessons.', 'the-hidden-word' ),
				(int) $updated
			)
		);
		echo '</p></div>';
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

		$demo_page = THW_Activator::get_demo_page();
		if ( $demo_page instanceof WP_Post ) {
			echo ' ';
			printf(
				/* translators: %s: front-end demo page URL */
				esc_html__( 'View the starter page: %s', 'the-hidden-word' ),
				'<a href="' . esc_url( get_permalink( $demo_page ) ) . '">' . esc_html__( "Today's Lesson", 'the-hidden-word' ) . '</a>'
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
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$progress = THW_Activator::get_seed_progress();
		if ( null === $progress ) {
			return;
		}

		$total = $progress['created'] + $progress['remaining'];

		echo '<div class="notice notice-info"><p>';
		echo esc_html(
			sprintf(
				/* translators: 1: lessons added so far, 2: total lessons being seeded */
				__( 'The Hidden Word: adding the bundled Bible lesson curriculum in the background (%1$d of %2$d added so far). This happens gradually to avoid slowing down your site — no action needed.', 'the-hidden-word' ),
				$progress['created'],
				$total
			)
		);
		echo '</p></div>';
	}
}
