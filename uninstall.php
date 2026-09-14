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
	wp_clear_scheduled_hook( 'hwbl_local_bible_import_batch' );
	wp_clear_scheduled_hook( 'thw_explain_preload_batch' );
	wp_clear_scheduled_hook( 'thw_explain_pack_export_batch' );
	wp_clear_scheduled_hook( 'thw_explain_pack_import_batch' );
	wp_clear_scheduled_hook( 'thw_seed_curriculum_batch' );
	wp_clear_scheduled_hook( 'thw_sync_curriculum_content' );

	delete_option( 'hwbl_pastor_note_views' );
	delete_option( 'hwbl_pastor_note_chapter_views' );
	delete_option( 'hwbl_pastor_notes_db_version' );
	delete_option( 'thw_explain_preload_job' );
	delete_option( 'thw_explain_pack_export_job' );
	delete_option( 'thw_explain_pack_import_job' );
	delete_option( 'thw_explain_packs_installed' );
	delete_option( 'thw_explain_pack_catalog_url' );
	delete_option( 'hwbl_local_bible_db_version' );
	delete_option( 'hwbl_bible_explain_db_version' );
	delete_option( 'hwbl_bible_explain_cpt_migrate_offset' );
	foreach ( array(
		'kjv', 'asv', 'web', 'bsb', 'bbe', 'ylt', 'dby', 'gnv', 'lsv', 'dra', 'cpdv',
		'jps', 'brenton', 'webo', 'erv',
		'rv1909', 'se1865', 'torresamat', 'spablm', 'vulgate', 'crampon', 'segond', 'luther1912', 'luther1545',
		'cuvs', 'cuvt', 'almeida', 'synodal', 'diodati',
		'wlc', 'lxx', 'tr', 'byz',
	) as $hwbl_local_slug ) {
		delete_option( 'hwbl_local_bible_job_' . $hwbl_local_slug );
	}

	if ( is_readable( plugin_dir_path( __FILE__ ) . 'includes/class-local-bible-store.php' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-local-bible-store.php';
		if ( class_exists( 'HWBL_Local_Bible_Store' ) ) {
			HWBL_Local_Bible_Store::drop_tables();
		}
	}

	$explain_store = plugin_dir_path( __FILE__ ) . 'premium/includes/class-bible-reader-explain-store.php';
	if ( is_readable( $explain_store ) ) {
		require_once $explain_store;
		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			THW_Premium_Bible_Reader_Explain_Store::drop_table();
		}
	}

	if ( class_exists( 'HWBL_Bible_Notes' ) ) {
		HWBL_Bible_Notes::drop_table();
	} else {
		$notes_store = plugin_dir_path( __FILE__ ) . 'includes/class-bible-notes.php';
		if ( is_readable( $notes_store ) ) {
			require_once $notes_store;
			if ( class_exists( 'HWBL_Bible_Notes' ) ) {
				HWBL_Bible_Notes::drop_table();
			}
		}
	}

	foreach ( array( 'HWBL_Pastor_Notes', 'HWBL_Verse_Tags' ) as $hwbl_table_class ) {
		if ( class_exists( $hwbl_table_class ) && method_exists( $hwbl_table_class, 'drop_table' ) ) {
			call_user_func( array( $hwbl_table_class, 'drop_table' ) );
		}
	}

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
