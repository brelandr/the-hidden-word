<?php
/**
 * Plugin activation.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Activator
 *
 * Seeds the bundled curriculum in small background batches instead of
 * inserting all 500 lessons synchronously during activation, which risks a
 * PHP/webserver timeout on slower hosting. Activation runs one batch inline
 * (so the site has content immediately) and schedules the rest via wp-cron.
 */
class HWBL_Activator {

	/**
	 * Lessons to insert per batch.
	 *
	 * @var int
	 */
	const SEED_BATCH_SIZE = 25;

	/**
	 * Lesson numbers that already exist, cached for the current seed request.
	 *
	 * @var array<int, true>|null
	 */
	private static $seeded_lesson_numbers = null;

	/**
	 * wp-cron hook used to process background seed batches.
	 *
	 * @var string
	 */
	const SEED_CRON_HOOK = 'hwbl_seed_curriculum_batch';

	/**
	 * wp-cron hook used to backfill lesson content on curriculum updates.
	 *
	 * @var string
	 */
	const SYNC_CRON_HOOK = 'hwbl_sync_curriculum_content';

	/**
	 * Activate plugin.
	 */
	public static function activate() {
		self::validate_bundled_verse_count();
		self::migrate_legacy_identifiers();

		$cpt = new HWBL_CPT_Lesson();
		$cpt->register_post_type();
		$cpt->register_meta();

		flush_rewrite_rules();

		if ( ! get_option( 'hwbl_schedule_mode' ) ) {
			update_option( 'hwbl_schedule_mode', 'week' );
		}

		if ( ! get_option( 'hwbl_active_translation' ) ) {
			update_option( 'hwbl_active_translation', 'niv' );
		}

		self::maybe_upgrade_curriculum();
		self::maybe_create_demo_page();
	}

	/**
	 * Migrate legacy thw_* CPT / options / meta to hwbl_* once.
	 */
	public static function migrate_legacy_identifiers() {
		if ( get_option( 'hwbl_migrated_from_thw', false ) ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'hwbl_lesson',
				'thw_lesson'
			)
		);

		$meta_map = array(
			'_thw_book_id'               => '_hwbl_book_id',
			'_thw_chapter'               => '_hwbl_chapter',
			'_thw_verse_start'           => '_hwbl_verse_start',
			'_thw_verse_end'             => '_hwbl_verse_end',
			'_thw_lesson_number'         => '_hwbl_lesson_number',
			'_thw_week_number'           => '_hwbl_week_number',
			'_thw_day_number'            => '_hwbl_day_number',
			'_thw_historical_context'    => '_hwbl_historical_context',
			'_thw_preceding_narrative'   => '_hwbl_preceding_narrative',
			'_thw_follow_on_verses'      => '_hwbl_follow_on_verses',
			'_thw_discussion_questions'  => '_hwbl_discussion_questions',
			'_thw_audio_url'             => '_hwbl_audio_url',
		);

		foreach ( $meta_map as $old => $new ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
					$new,
					$old
				)
			);
		}

		$option_keys = array(
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

		foreach ( $option_keys as $old_key ) {
			$new_key = preg_replace( '/^thw_/', 'hwbl_', $old_key );
			$old_val = get_option( $old_key, null );
			if ( null === $old_val ) {
				continue;
			}
			if ( false === get_option( $new_key, false ) && null === get_option( $new_key, null ) ) {
				add_option( $new_key, $old_val );
			} elseif ( '' === (string) get_option( $new_key, '' ) && '' !== (string) $old_val ) {
				update_option( $new_key, $old_val );
			}
		}

		$transients = array(
			'thw_curriculum_upgraded'       => 'hwbl_curriculum_upgraded',
			'thw_curriculum_content_synced' => 'hwbl_curriculum_content_synced',
			'thw_lesson_index'              => 'hwbl_lesson_index',
		);
		foreach ( $transients as $old_t => $new_t ) {
			$val = get_transient( $old_t );
			if ( false !== $val ) {
				set_transient( $new_t, $val, 5 * MINUTE_IN_SECONDS );
				delete_transient( $old_t );
			}
		}

		if ( wp_next_scheduled( 'thw_seed_curriculum_batch' ) ) {
			wp_clear_scheduled_hook( 'thw_seed_curriculum_batch' );
			if ( ! wp_next_scheduled( self::SEED_CRON_HOOK ) ) {
				wp_schedule_single_event( time() + 15, self::SEED_CRON_HOOK );
			}
		}
		if ( wp_next_scheduled( 'thw_sync_curriculum_content' ) ) {
			wp_clear_scheduled_hook( 'thw_sync_curriculum_content' );
			if ( ! wp_next_scheduled( self::SYNC_CRON_HOOK ) ) {
				wp_schedule_single_event( time() + 15, self::SYNC_CRON_HOOK );
			}
		}

		update_option( 'hwbl_migrated_from_thw', 1 );
	}

	/**
	 * Check whether the curriculum needs (re)seeding and, if so, kick things
	 * off. Safe to call on every 'init' — it does almost nothing once seeding
	 * is either finished or already queued and scheduled.
	 */
	public static function maybe_upgrade_curriculum() {
		self::migrate_legacy_identifiers();
		self::migrate_day_numbers();

		$installed  = get_option( 'hwbl_curriculum_version', '' );
		$target     = HWBL_CURRICULUM_DB_VERSION;
		$seed_queue = get_option( 'hwbl_seed_queue', false );
		$sync_queue = get_option( 'hwbl_sync_queue', false );

		if ( is_array( $sync_queue ) ) {
			if ( empty( $sync_queue ) ) {
				self::finish_content_sync();
			} else {
				self::ensure_sync_batch_scheduled();
			}
			return;
		}

		$fully_seeded = get_option( 'hwbl_seeded' ) && version_compare( $installed, $target, '>=' );

		if ( $fully_seeded && false === $seed_queue ) {
			return;
		}

		if ( get_option( 'hwbl_seeded' ) && $installed && version_compare( $installed, $target, '<' ) && false === $seed_queue ) {
			self::queue_content_sync();
			self::process_sync_batch();
			return;
		}

		if ( false === $seed_queue ) {
			// First time we've seen this version bump: build the work queue and
			// run one batch immediately so the site has content right away.
			self::migrate_legacy_lesson_numbers();
			self::migrate_day_numbers();
			self::queue_pending_lessons();
			self::process_seed_batch();
			return;
		}

		// A queue already exists (seeding in progress from a previous request).
		// Never seed inline from 'init' — just make sure a background batch is
		// scheduled, so ordinary front-end page views stay fast while seeding
		// finishes in the background.
		self::ensure_batch_scheduled();
	}

	/**
	 * Queue all bundled lesson numbers for content backfill.
	 */
	private static function queue_content_sync() {
		require_once HWBL_PLUGIN_DIR . 'includes/class-curriculum.php';

		$queue = array();
		foreach ( HWBL_Curriculum::load_niv() as $entry ) {
			$lesson = HWBL_Curriculum::get_entry_lesson_number( $entry );
			if ( $lesson >= 1 ) {
				$queue[] = $lesson;
			}
		}

		update_option( 'hwbl_sync_queue', $queue, false );
		update_option( 'hwbl_sync_updated_count', 0, false );
	}

	/**
	 * Backfill bundled lesson content for one batch.
	 */
	public static function process_sync_batch() {
		$queue = get_option( 'hwbl_sync_queue', false );

		if ( ! is_array( $queue ) ) {
			return;
		}

		if ( empty( $queue ) ) {
			self::finish_content_sync();
			return;
		}

		$batch_size = (int) apply_filters( 'hwbl_sync_batch_size', self::SEED_BATCH_SIZE );
		$batch      = array_splice( $queue, 0, max( 1, $batch_size ) );

		update_option( 'hwbl_sync_queue', $queue, false );

		$updated = self::sync_lesson_numbers( $batch );

		$total_updated = (int) get_option( 'hwbl_sync_updated_count', 0 ) + $updated;
		update_option( 'hwbl_sync_updated_count', $total_updated, false );

		if ( empty( $queue ) ) {
			self::finish_content_sync();
			return;
		}

		self::ensure_sync_batch_scheduled();
	}

	/**
	 * Copy bundled curriculum content into lesson post meta when fields are empty.
	 *
	 * @param int[] $lesson_numbers Lesson numbers to sync in this batch.
	 * @return int Number of lessons updated.
	 */
	private static function sync_lesson_numbers( $lesson_numbers ) {
		require_once HWBL_PLUGIN_DIR . 'includes/class-curriculum.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-scheduler.php';

		$updated = 0;

		foreach ( $lesson_numbers as $lesson_number ) {
			$lesson_number = (int) $lesson_number;
			$entry         = HWBL_Curriculum::get_entry_by_lesson_number( $lesson_number );
			$post_id       = HWBL_Scheduler::get_lesson_id_by_number( $lesson_number );

			if ( ! $entry || ! $post_id ) {
				continue;
			}

			$changed = false;

			if ( ! get_post_meta( $post_id, '_hwbl_historical_context', true ) && ! empty( $entry['historical_context'] ) ) {
				update_post_meta( $post_id, '_hwbl_historical_context', wp_kses_post( $entry['historical_context'] ) );
				$changed = true;
			}

			if ( ! get_post_meta( $post_id, '_hwbl_preceding_narrative', true ) && ! empty( $entry['preceding_narrative'] ) ) {
				update_post_meta( $post_id, '_hwbl_preceding_narrative', wp_kses_post( $entry['preceding_narrative'] ) );
				$changed = true;
			}

			if ( ! get_post_meta( $post_id, '_hwbl_discussion_questions', true ) && ! empty( $entry['discussion_questions'] ) ) {
				update_post_meta( $post_id, '_hwbl_discussion_questions', wp_json_encode( $entry['discussion_questions'] ) );
				$changed = true;
			}

			if ( ! get_post_meta( $post_id, '_hwbl_follow_on_verses', true ) && ! empty( $entry['follow_on_verses'] ) ) {
				update_post_meta( $post_id, '_hwbl_follow_on_verses', wp_json_encode( $entry['follow_on_verses'] ) );
				$changed = true;
			}

			if ( $changed ) {
				++$updated;
			}
		}

		return $updated;
	}

	/**
	 * Schedule the next content sync batch if one isn't already pending.
	 */
	private static function ensure_sync_batch_scheduled() {
		$queue = get_option( 'hwbl_sync_queue', false );

		if ( is_array( $queue ) && ! empty( $queue ) && ! wp_next_scheduled( self::SYNC_CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 15, self::SYNC_CRON_HOOK );
		}
	}

	/**
	 * Finalize content sync once the queue is empty.
	 */
	private static function finish_content_sync() {
		delete_option( 'hwbl_sync_queue' );

		update_option( 'hwbl_curriculum_version', HWBL_CURRICULUM_DB_VERSION );

		$updated = (int) get_option( 'hwbl_sync_updated_count', 0 );
		delete_option( 'hwbl_sync_updated_count' );

		if ( $updated > 0 ) {
			set_transient( 'hwbl_curriculum_content_synced', $updated, MINUTE_IN_SECONDS * 5 );
		}
	}

	/**
	 * Build the queue of lesson numbers that still need to be created.
	 */
	private static function queue_pending_lessons() {
		require_once HWBL_PLUGIN_DIR . 'includes/class-curriculum.php';

		$queue = array();
		foreach ( HWBL_Curriculum::load_niv() as $entry ) {
			$lesson = HWBL_Curriculum::get_entry_lesson_number( $entry );
			if ( $lesson >= 1 ) {
				$queue[] = $lesson;
			}
		}

		update_option( 'hwbl_seed_queue', $queue, false );
		update_option( 'hwbl_seed_created_count', 0, false );
	}

	/**
	 * Process one batch off the seed queue, and either schedule the next
	 * batch (work remains) or finalize (queue empty).
	 */
	public static function process_seed_batch() {
		$queue = get_option( 'hwbl_seed_queue', false );

		if ( ! is_array( $queue ) ) {
			return; // Nothing queued — already finished, or never started.
		}

		if ( empty( $queue ) ) {
			self::finish_seeding();
			return;
		}

		$batch_size = (int) apply_filters( 'hwbl_seed_batch_size', self::SEED_BATCH_SIZE );
		$batch      = array_splice( $queue, 0, max( 1, $batch_size ) );

		update_option( 'hwbl_seed_queue', $queue, false );

		$created = self::seed_lesson_numbers( $batch );

		$total_created = (int) get_option( 'hwbl_seed_created_count', 0 ) + $created;
		update_option( 'hwbl_seed_created_count', $total_created, false );

		if ( class_exists( 'HWBL_Scheduler' ) ) {
			HWBL_Scheduler::rebuild_lookup_map();
		}

		if ( empty( $queue ) ) {
			self::finish_seeding();
			return;
		}

		self::ensure_batch_scheduled();
	}

	/**
	 * Schedule the next background batch if one isn't already pending.
	 */
	private static function ensure_batch_scheduled() {
		$queue = get_option( 'hwbl_seed_queue', false );

		if ( is_array( $queue ) && ! empty( $queue ) && ! wp_next_scheduled( self::SEED_CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 15, self::SEED_CRON_HOOK );
		}
	}

	/**
	 * Finalize seeding once the queue is empty: record version/seeded state
	 * and surface a one-time admin notice with the total created.
	 */
	private static function finish_seeding() {
		delete_option( 'hwbl_seed_queue' );

		update_option( 'hwbl_seeded', true );
		update_option( 'hwbl_curriculum_version', HWBL_CURRICULUM_DB_VERSION );

		$created = (int) get_option( 'hwbl_seed_created_count', 0 );
		delete_option( 'hwbl_seed_created_count' );

		HWBL_Scheduler::rebuild_lookup_map();

		if ( $created > 0 ) {
			set_transient( 'hwbl_curriculum_upgraded', $created, MINUTE_IN_SECONDS * 5 );
		}
	}

	/**
	 * Progress info for the "seeding in progress" admin notice.
	 *
	 * @return array{remaining:int,created:int}|null Null when no seeding is in progress.
	 */
	public static function get_seed_progress() {
		$queue = get_option( 'hwbl_seed_queue', false );

		if ( ! is_array( $queue ) ) {
			return null;
		}

		return array(
			'remaining' => count( $queue ),
			'created'   => (int) get_option( 'hwbl_seed_created_count', 0 ),
		);
	}

	/**
	 * Copy legacy week numbers into lesson numbers for older installs.
	 */
	public static function migrate_legacy_lesson_numbers() {
		$lessons = get_posts(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		foreach ( $lessons as $lesson_id ) {
			$week        = (int) get_post_meta( $lesson_id, '_hwbl_week_number', true );
			$lesson_meta = (int) get_post_meta( $lesson_id, '_hwbl_lesson_number', true );

			if ( $week > 0 && $lesson_meta < 1 ) {
				update_post_meta( $lesson_id, '_hwbl_lesson_number', $week );
			}
		}
	}

	/**
	 * Map lesson numbers 1–366 to day-of-year slots for Verse of the Day mode.
	 */
	public static function migrate_day_numbers() {
		if ( get_option( 'hwbl_day_numbers_migrated', false ) ) {
			return;
		}

		$lessons = get_posts(
			array(
				'post_type'      => array( 'hwbl_lesson', 'thw_lesson' ),
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		$updated = 0;
		foreach ( $lessons as $lesson_id ) {
			$day_number    = (int) get_post_meta( $lesson_id, '_hwbl_day_number', true );
			$lesson_number = (int) get_post_meta( $lesson_id, '_hwbl_lesson_number', true );
			if ( ! $lesson_number ) {
				$lesson_number = (int) get_post_meta( $lesson_id, '_hwbl_week_number', true );
			}

			if ( $day_number > 0 || $lesson_number < 1 || $lesson_number > 366 ) {
				continue;
			}

			update_post_meta( $lesson_id, '_hwbl_day_number', $lesson_number );
			++$updated;
		}

		if ( $updated > 0 && class_exists( 'HWBL_Scheduler' ) ) {
			HWBL_Scheduler::rebuild_lookup_map();
		}

		update_option( 'hwbl_day_numbers_migrated', 1 );
	}

	/**
	 * Ensure bundled NIV verse count stays within Biblica fair-use limits.
	 */
	public static function validate_bundled_verse_count() {
		require_once HWBL_PLUGIN_DIR . 'includes/interface-translation-provider.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-curriculum.php';

		$niv_verses = HWBL_Curriculum::count_verses();
		if ( $niv_verses > HWBL_MAX_NIV_VERSES ) {
			wp_die(
				esc_html(
					sprintf(
						/* translators: 1: bundled NIV verse count, 2: maximum allowed */
						__( 'Hidden Word Bible Lessons: bundled NIV verse count (%1$d) exceeds the %2$d verse fair-use limit.', 'hidden-word-bible-lessons' ),
						$niv_verses,
						HWBL_MAX_NIV_VERSES
					)
				)
			);
		}

		$niv_count = HWBL_Curriculum::get_lesson_count();
		foreach ( HWBL_Bundled_Provider::get_parity_slugs() as $slug ) {
			$count = count( HWBL_Curriculum::load_translation( $slug ) );
			if ( $count > 0 && $count !== $niv_count ) {
				wp_die(
					esc_html(
						sprintf(
							/* translators: 1: translation slug, 2: actual lesson count, 3: NIV lesson count */
							__( 'Hidden Word Bible Lessons: %1$s curriculum (%2$d lessons) must match the NIV curriculum (%3$d lessons).', 'hidden-word-bible-lessons' ),
							strtoupper( $slug ),
							$count,
							$niv_count
						)
					)
				);
			}
		}
	}

	/**
	 * Seed specific lesson numbers from the bundled NIV curriculum. Skips any
	 * lesson number that already exists, so a batch can be safely re-run.
	 *
	 * @param int[] $lesson_numbers Lesson numbers to seed in this batch.
	 * @return int Number of lessons created.
	 */
	private static function seed_lesson_numbers( $lesson_numbers ) {
		require_once HWBL_PLUGIN_DIR . 'includes/class-curriculum.php';

		$by_lesson = array();
		foreach ( HWBL_Curriculum::load_niv() as $entry ) {
			$by_lesson[ HWBL_Curriculum::get_entry_lesson_number( $entry ) ] = $entry;
		}

		$books   = HWBL_Books::get_all();
		$created = 0;

		foreach ( $lesson_numbers as $lesson ) {
			if ( ! isset( $by_lesson[ $lesson ] ) ) {
				continue;
			}

			$entry = $by_lesson[ $lesson ];

			if ( self::lesson_number_exists( $lesson ) ) {
				continue;
			}

			$book_id = (int) $entry['book_id'];
			$book    = isset( $books[ $book_id ] ) ? $books[ $book_id ] : 'Scripture';

			$ref = $book . ' ' . $entry['chapter'] . ':' . $entry['verse_start'];
			if ( ! empty( $entry['verse_end'] ) && (int) $entry['verse_end'] !== (int) $entry['verse_start'] ) {
				$ref .= '-' . $entry['verse_end'];
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'hwbl_lesson',
					'post_title'   => sprintf(
						/* translators: 1: lesson number, 2: scripture reference */
						__( 'Lesson %1$d: %2$s', 'hidden-word-bible-lessons' ),
						$lesson,
						$ref
					),
					'post_status'  => 'publish',
					'post_content' => '',
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_hwbl_book_id', $book_id );
			update_post_meta( $post_id, '_hwbl_chapter', (int) $entry['chapter'] );
			update_post_meta( $post_id, '_hwbl_verse_start', (int) $entry['verse_start'] );
			update_post_meta( $post_id, '_hwbl_verse_end', isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : (int) $entry['verse_start'] );
			update_post_meta( $post_id, '_hwbl_lesson_number', $lesson );
			update_post_meta( $post_id, '_hwbl_week_number', $lesson );
			if ( $lesson >= 1 && $lesson <= 366 ) {
				update_post_meta( $post_id, '_hwbl_day_number', $lesson );
			}

			if ( ! empty( $entry['historical_context'] ) ) {
				update_post_meta( $post_id, '_hwbl_historical_context', wp_kses_post( $entry['historical_context'] ) );
			}
			if ( ! empty( $entry['preceding_narrative'] ) ) {
				update_post_meta( $post_id, '_hwbl_preceding_narrative', wp_kses_post( $entry['preceding_narrative'] ) );
			}
			if ( ! empty( $entry['discussion_questions'] ) ) {
				update_post_meta( $post_id, '_hwbl_discussion_questions', wp_json_encode( $entry['discussion_questions'] ) );
			}

			self::mark_lesson_number_seeded( $lesson );

			++$created;
		}

		return $created;
	}

	/**
	 * Whether a lesson number already exists in the database.
	 *
	 * @param int $lesson_number Lesson number.
	 * @return bool
	 */
	private static function lesson_number_exists( $lesson_number ) {
		$lesson_number = (int) $lesson_number;
		if ( $lesson_number < 1 ) {
			return false;
		}

		self::prime_seeded_lesson_numbers();

		return isset( self::$seeded_lesson_numbers[ $lesson_number ] );
	}

	/**
	 * Record a lesson number as seeded for the current request.
	 *
	 * @param int $lesson_number Lesson number.
	 */
	private static function mark_lesson_number_seeded( $lesson_number ) {
		$lesson_number = (int) $lesson_number;
		if ( $lesson_number < 1 ) {
			return;
		}

		self::prime_seeded_lesson_numbers();
		self::$seeded_lesson_numbers[ $lesson_number ] = true;
	}

	/**
	 * Load all existing lesson/week numbers once per seed request.
	 */
	private static function prime_seeded_lesson_numbers() {
		if ( null !== self::$seeded_lesson_numbers ) {
			return;
		}

		self::$seeded_lesson_numbers = array();

		$lesson_ids = get_posts(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		foreach ( $lesson_ids as $lesson_id ) {
			$lesson_number = (int) get_post_meta( $lesson_id, '_hwbl_lesson_number', true );
			if ( $lesson_number > 0 ) {
				self::$seeded_lesson_numbers[ $lesson_number ] = true;
				continue;
			}

			$week_number = (int) get_post_meta( $lesson_id, '_hwbl_week_number', true );
			if ( $week_number > 0 ) {
				self::$seeded_lesson_numbers[ $week_number ] = true;
			}
		}
	}

	/**
	 * Find the demo page by path or legacy/current titles.
	 *
	 * @return WP_Post|null
	 */
	public static function get_demo_page() {
		$by_path = get_page_by_path( 'todays-lesson' );
		if ( $by_path instanceof WP_Post ) {
			return $by_path;
		}

		$titles = array_unique(
			array_filter(
				array(
					__( "Today's Lesson", 'hidden-word-bible-lessons' ),
					class_exists( 'HWBL_Scheduler' ) ? HWBL_Scheduler::get_schedule_phrase( 'memorize' ) : '',
					__( "Today's Verse to Memorize", 'hidden-word-bible-lessons' ),
					__( "This Week's Verse to Memorize", 'hidden-word-bible-lessons' ),
				)
			)
		);

		foreach ( $titles as $title ) {
			$query = new WP_Query(
				array(
					'post_type'              => 'page',
					'title'                  => $title,
					'posts_per_page'         => 1,
					'post_status'            => 'publish',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( $query->have_posts() ) {
				return $query->posts[0];
			}
		}

		return null;
	}

	/**
	 * Create a starter front-end page with [hwbl_lesson] on first activation.
	 */
	private static function maybe_create_demo_page() {
		if ( get_option( 'hwbl_demo_page_created' ) ) {
			return;
		}

		$existing = self::get_demo_page();
		if ( $existing instanceof WP_Post ) {
			update_option( 'hwbl_demo_page_created', true );
			return;
		}

		$title = class_exists( 'HWBL_Scheduler' )
			? HWBL_Scheduler::get_schedule_phrase( 'memorize' )
			: __( "Today's Verse to Memorize", 'hidden-word-bible-lessons' );

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => $title,
				'post_name'    => 'todays-lesson',
				'post_status'  => 'publish',
				'post_content' => "<!-- wp:shortcode -->\n[hwbl_lesson]\n<!-- /wp:shortcode -->",
			),
			true
		);

		if ( ! is_wp_error( $page_id ) && $page_id > 0 ) {
			update_option( 'hwbl_demo_page_created', true );
		}
	}
}
