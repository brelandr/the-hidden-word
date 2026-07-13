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
