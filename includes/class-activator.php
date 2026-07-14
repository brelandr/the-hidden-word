<?php
/**
 * Plugin activation.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Activator
 *
 * Seeds the bundled curriculum in small background batches instead of
 * inserting all 500 lessons synchronously during activation, which risks a
 * PHP/webserver timeout on slower hosting. Activation runs one batch inline
 * (so the site has content immediately) and schedules the rest via wp-cron.
 */
class THW_Activator {

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
	const SEED_CRON_HOOK = 'thw_seed_curriculum_batch';

	/**
	 * Activate plugin.
	 */
	public static function activate() {
		self::validate_bundled_verse_count();

		$cpt = new THW_CPT_Lesson();
		$cpt->register_post_type();
		$cpt->register_meta();

		flush_rewrite_rules();

		if ( ! get_option( 'thw_schedule_mode' ) ) {
			update_option( 'thw_schedule_mode', 'week' );
		}

		if ( ! get_option( 'thw_active_translation' ) ) {
			update_option( 'thw_active_translation', 'niv' );
		}

		self::maybe_upgrade_curriculum();
		self::maybe_create_demo_page();
	}

	/**
	 * Check whether the curriculum needs (re)seeding and, if so, kick things
	 * off. Safe to call on every 'init' — it does almost nothing once seeding
	 * is either finished or already queued and scheduled.
	 */
	public static function maybe_upgrade_curriculum() {
		$installed = get_option( 'thw_curriculum_version', '' );
		$target    = THW_CURRICULUM_DB_VERSION;
		$queue     = get_option( 'thw_seed_queue', false );

		$fully_seeded = get_option( 'thw_seeded' ) && version_compare( $installed, $target, '>=' );

		if ( $fully_seeded && false === $queue ) {
			return;
		}

		if ( false === $queue ) {
			// First time we've seen this version bump: build the work queue and
			// run one batch immediately so the site has content right away.
			self::migrate_legacy_lesson_numbers();
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
	 * Build the queue of lesson numbers that still need to be created.
	 */
	private static function queue_pending_lessons() {
		require_once THW_PLUGIN_DIR . 'includes/class-curriculum.php';

		$queue = array();
		foreach ( THW_Curriculum::load_niv() as $entry ) {
			$lesson = THW_Curriculum::get_entry_lesson_number( $entry );
			if ( $lesson >= 1 ) {
				$queue[] = $lesson;
			}
		}

		update_option( 'thw_seed_queue', $queue, false );
		update_option( 'thw_seed_created_count', 0, false );
	}

	/**
	 * Process one batch off the seed queue, and either schedule the next
	 * batch (work remains) or finalize (queue empty).
	 */
	public static function process_seed_batch() {
		$queue = get_option( 'thw_seed_queue', false );

		if ( ! is_array( $queue ) ) {
			return; // Nothing queued — already finished, or never started.
		}

		if ( empty( $queue ) ) {
			self::finish_seeding();
			return;
		}

		$batch_size = (int) apply_filters( 'thw_seed_batch_size', self::SEED_BATCH_SIZE );
		$batch      = array_splice( $queue, 0, max( 1, $batch_size ) );

		update_option( 'thw_seed_queue', $queue, false );

		$created = self::seed_lesson_numbers( $batch );

		$total_created = (int) get_option( 'thw_seed_created_count', 0 ) + $created;
		update_option( 'thw_seed_created_count', $total_created, false );

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
		$queue = get_option( 'thw_seed_queue', false );

		if ( is_array( $queue ) && ! empty( $queue ) && ! wp_next_scheduled( self::SEED_CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 15, self::SEED_CRON_HOOK );
		}
	}

	/**
	 * Finalize seeding once the queue is empty: record version/seeded state
	 * and surface a one-time admin notice with the total created.
	 */
	private static function finish_seeding() {
		delete_option( 'thw_seed_queue' );

		update_option( 'thw_seeded', true );
		update_option( 'thw_curriculum_version', THW_CURRICULUM_DB_VERSION );

		$created = (int) get_option( 'thw_seed_created_count', 0 );
		delete_option( 'thw_seed_created_count' );

		THW_Scheduler::rebuild_lookup_map();

		if ( $created > 0 ) {
			set_transient( 'thw_curriculum_upgraded', $created, MINUTE_IN_SECONDS * 5 );
		}
	}

	/**
	 * Progress info for the "seeding in progress" admin notice.
	 *
	 * @return array{remaining:int,created:int}|null Null when no seeding is in progress.
	 */
	public static function get_seed_progress() {
		$queue = get_option( 'thw_seed_queue', false );

		if ( ! is_array( $queue ) ) {
			return null;
		}

		return array(
			'remaining' => count( $queue ),
			'created'   => (int) get_option( 'thw_seed_created_count', 0 ),
		);
	}

	/**
	 * Copy legacy week numbers into lesson numbers for older installs.
	 */
	public static function migrate_legacy_lesson_numbers() {
		$lessons = get_posts(
			array(
				'post_type'      => 'thw_lesson',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		foreach ( $lessons as $lesson_id ) {
			$week        = (int) get_post_meta( $lesson_id, '_thw_week_number', true );
			$lesson_meta = (int) get_post_meta( $lesson_id, '_thw_lesson_number', true );

			if ( $week > 0 && $lesson_meta < 1 ) {
				update_post_meta( $lesson_id, '_thw_lesson_number', $week );
			}
		}
	}

	/**
	 * Ensure bundled NIV verse count stays within Biblica fair-use limits.
	 */
	public static function validate_bundled_verse_count() {
		require_once THW_PLUGIN_DIR . 'includes/class-curriculum.php';

		$niv_verses = THW_Curriculum::count_verses();
		if ( $niv_verses > THW_MAX_NIV_VERSES ) {
			wp_die(
				esc_html(
					sprintf(
						/* translators: 1: bundled NIV verse count, 2: maximum allowed */
						__( 'The Hidden Word: bundled NIV verse count (%1$d) exceeds the %2$d verse fair-use limit.', 'the-hidden-word' ),
						$niv_verses,
						THW_MAX_NIV_VERSES
					)
				)
			);
		}

		$niv_count = THW_Curriculum::get_lesson_count();
		foreach ( THW_Bundled_Provider::get_parity_slugs() as $slug ) {
			$count = count( THW_Curriculum::load_translation( $slug ) );
			if ( $count > 0 && $count !== $niv_count ) {
				wp_die(
					esc_html(
						sprintf(
							/* translators: 1: translation slug, 2: actual lesson count, 3: NIV lesson count */
							__( 'The Hidden Word: %1$s curriculum (%2$d lessons) must match the NIV curriculum (%3$d lessons).', 'the-hidden-word' ),
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
		require_once THW_PLUGIN_DIR . 'includes/class-curriculum.php';

		$by_lesson = array();
		foreach ( THW_Curriculum::load_niv() as $entry ) {
			$by_lesson[ THW_Curriculum::get_entry_lesson_number( $entry ) ] = $entry;
		}

		$books   = THW_Books::get_all();
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
					'post_type'    => 'thw_lesson',
					'post_title'   => sprintf(
						/* translators: 1: lesson number, 2: scripture reference */
						__( 'Lesson %1$d: %2$s', 'the-hidden-word' ),
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

			update_post_meta( $post_id, '_thw_book_id', $book_id );
			update_post_meta( $post_id, '_thw_chapter', (int) $entry['chapter'] );
			update_post_meta( $post_id, '_thw_verse_start', (int) $entry['verse_start'] );
			update_post_meta( $post_id, '_thw_verse_end', isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : (int) $entry['verse_start'] );
			update_post_meta( $post_id, '_thw_lesson_number', $lesson );
			update_post_meta( $post_id, '_thw_week_number', $lesson );

			if ( ! empty( $entry['historical_context'] ) ) {
				update_post_meta( $post_id, '_thw_historical_context', wp_kses_post( $entry['historical_context'] ) );
			}
			if ( ! empty( $entry['preceding_narrative'] ) ) {
				update_post_meta( $post_id, '_thw_preceding_narrative', wp_kses_post( $entry['preceding_narrative'] ) );
			}
			if ( ! empty( $entry['discussion_questions'] ) ) {
				update_post_meta( $post_id, '_thw_discussion_questions', wp_json_encode( $entry['discussion_questions'] ) );
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
				'post_type'      => 'thw_lesson',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		foreach ( $lesson_ids as $lesson_id ) {
			$lesson_number = (int) get_post_meta( $lesson_id, '_thw_lesson_number', true );
			if ( $lesson_number > 0 ) {
				self::$seeded_lesson_numbers[ $lesson_number ] = true;
				continue;
			}

			$week_number = (int) get_post_meta( $lesson_id, '_thw_week_number', true );
			if ( $week_number > 0 ) {
				self::$seeded_lesson_numbers[ $week_number ] = true;
			}
		}
	}

	/**
	 * Find the demo page by title (WP 6.2+ compatible).
	 *
	 * @return WP_Post|null
	 */
	public static function get_demo_page() {
		$title = __( "Today's Lesson", 'the-hidden-word' );
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

		return null;
	}

	/**
	 * Create a starter front-end page with [thw_lesson] on first activation.
	 */
	private static function maybe_create_demo_page() {
		if ( get_option( 'thw_demo_page_created' ) ) {
			return;
		}

		$existing = self::get_demo_page();
		if ( $existing instanceof WP_Post ) {
			update_option( 'thw_demo_page_created', true );
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => __( "Today's Lesson", 'the-hidden-word' ),
				'post_status'  => 'publish',
				'post_content' => "<!-- wp:shortcode -->\n[thw_lesson]\n<!-- /wp:shortcode -->",
			),
			true
		);

		if ( ! is_wp_error( $page_id ) && $page_id > 0 ) {
			update_option( 'thw_demo_page_created', true );
		}
	}
}
