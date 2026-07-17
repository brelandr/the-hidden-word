<?php
/**
 * Uninstall handler.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-uninstall-keys.php';

/**
 * Remove all plugin data. Wrapped in a function (rather than run at the top
 * level of this file) so working variables stay in local scope instead of
 * the real PHP global scope.
 */
function hwbl_run_uninstall() {
	$option_keys = array(
		'hwbl_seeded',
		'hwbl_schedule_mode',
		'hwbl_active_translation',
		'hwbl_ai_enabled',
		'hwbl_copyright_displayed',
		'hwbl_curriculum_version',
		'hwbl_seed_queue',
		'hwbl_seed_created_count',
		'hwbl_sync_queue',
		'hwbl_sync_updated_count',
		'hwbl_demo_page_created',
		'hwbl_lesson_lookup_map',
		'hwbl_migrated_from_thw',
		// Legacy keys.
		'thw_seeded',
		'thw_schedule_mode',
		'thw_active_translation',
		'thw_ai_enabled',
		'thw_copyright_displayed',
		'thw_curriculum_version',
		'thw_seed_queue',
		'thw_seed_created_count',
		'thw_sync_queue',
		'thw_sync_updated_count',
		'thw_demo_page_created',
		'thw_lesson_lookup_map',
	);

	foreach ( $option_keys as $key ) {
		delete_option( $key );
	}

	if ( class_exists( 'HWBL_Uninstall_Keys' ) ) {
		HWBL_Uninstall_Keys::delete_premium_options();
	}

	delete_transient( 'hwbl_curriculum_upgraded' );
	delete_transient( 'hwbl_curriculum_content_synced' );
	delete_transient( 'hwbl_lesson_index' );
	delete_transient( 'thw_curriculum_upgraded' );
	delete_transient( 'thw_curriculum_content_synced' );
	delete_transient( 'thw_lesson_index' );

	wp_clear_scheduled_hook( 'hwbl_seed_curriculum_batch' );
	wp_clear_scheduled_hook( 'hwbl_sync_curriculum_content' );
	wp_clear_scheduled_hook( 'thw_seed_curriculum_batch' );
	wp_clear_scheduled_hook( 'thw_sync_curriculum_content' );

	$lessons = get_posts(
		array(
			'post_type'      => array( 'hwbl_lesson', 'thw_lesson' ),
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);

	foreach ( $lessons as $lesson_id ) {
		wp_delete_post( $lesson_id, true );
	}
}

hwbl_run_uninstall();
