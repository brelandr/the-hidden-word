<?php
/**
 * Uninstall handler.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'thw_seeded' );
delete_option( 'thw_schedule_mode' );
delete_option( 'thw_active_translation' );
delete_option( 'thw_copyright_displayed' );
delete_option( 'thw_curriculum_version' );
delete_option( 'thw_seed_queue' );
delete_option( 'thw_seed_created_count' );
delete_option( 'thw_sync_queue' );
delete_option( 'thw_sync_updated_count' );
delete_option( 'thw_demo_page_created' );
delete_option( 'thw_lesson_lookup_map' );
delete_transient( 'thw_curriculum_upgraded' );
delete_transient( 'thw_curriculum_content_synced' );

wp_clear_scheduled_hook( 'thw_seed_curriculum_batch' );
wp_clear_scheduled_hook( 'thw_sync_curriculum_content' );

$lessons = get_posts(
	array(
		'post_type'      => 'thw_lesson',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $lessons as $lesson_id ) {
	wp_delete_post( $lesson_id, true );
}
