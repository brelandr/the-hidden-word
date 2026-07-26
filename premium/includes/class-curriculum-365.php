<?php
/**
 * 365-day curriculum expansion option.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Curriculum
 */
class THW_Premium_Curriculum {

	/**
	 * Day numbers already mapped, cached for the current expand request.
	 *
	 * @var array<int, true>|null
	 */
	private static $existing_day_numbers = null;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'admin_post_thw_expand_curriculum', array( __CLASS__, 'handle_expand' ) );
		add_action( 'admin_notices', array( __CLASS__, 'curriculum_notice' ) );
	}

	/**
	 * Show curriculum expansion notice on settings page.
	 */
	public static function curriculum_notice() {
		$screen = get_current_screen();
		if ( ! $screen || 'hwbl_lesson_page_thw-premium-settings' !== $screen->id ) {
			return;
		}

		if ( HWBL_Scheduler::get_lesson_id_by_day( 365 ) > 0 ) {
			echo '<div class="notice notice-success"><p>';
			esc_html_e( '365-day curriculum is active.', 'hidden-word-bible-lessons' );
			echo '</p></div>';
		}
	}

	/**
	 * Map the first 365 bundled lessons to daily schedule slots.
	 */
	public static function handle_expand() {
		check_admin_referer( 'thw_expand_curriculum' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		$niv_path = HWBL_PLUGIN_DIR . 'data/niv-curriculum.json';
		if ( ! is_readable( $niv_path ) ) {
			wp_die( esc_html__( 'Curriculum data not found.', 'hidden-word-bible-lessons' ) );
		}

		$curriculum = json_decode( file_get_contents( $niv_path ), true );
		if ( ! is_array( $curriculum ) || empty( $curriculum ) ) {
			wp_die( esc_html__( 'Invalid curriculum data.', 'hidden-word-bible-lessons' ) );
		}

		$created = 0;

		for ( $day = 1; $day <= 365; $day++ ) {
			if ( self::day_number_exists( $day ) ) {
				continue;
			}

			$entry = $curriculum[ $day - 1 ];
			$lesson_num = HWBL_Curriculum::get_entry_lesson_number( $entry );
			$book  = HWBL_Books::get_name( $entry['book_id'] );
			$ref   = $book . ' ' . $entry['chapter'] . ':' . $entry['verse_start'];

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'hwbl_lesson',
					'post_title'  => sprintf(
						/* translators: 1: day number, 2: reference */
						__( 'Day %1$d: %2$s', 'hidden-word-bible-lessons' ),
						$day,
						$ref
					),
					'post_status' => 'publish',
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_hwbl_book_id', (int) $entry['book_id'] );
			update_post_meta( $post_id, '_hwbl_chapter', (int) $entry['chapter'] );
			update_post_meta( $post_id, '_hwbl_verse_start', (int) $entry['verse_start'] );
			update_post_meta( $post_id, '_hwbl_verse_end', isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : (int) $entry['verse_start'] );
			update_post_meta( $post_id, '_hwbl_day_number', $day );
			update_post_meta( $post_id, '_hwbl_lesson_number', $lesson_num );
			update_post_meta( $post_id, '_hwbl_week_number', $lesson_num );

			self::mark_day_number_mapped( $day );
			$created++;
		}

		HWBL_Scheduler::rebuild_lookup_map();
		update_option( 'hwbl_schedule_mode', 'day' );

		add_settings_error(
			'thw_premium',
			'curriculum_expanded',
			sprintf(
				/* translators: %d: number of lessons created */
				__( 'Created %d daily lessons. Schedule mode set to Verse of the Day.', 'hidden-word-bible-lessons' ),
				$created
			),
			'success'
		);
		set_transient( 'settings_errors', get_settings_errors(), 30 );

		wp_safe_redirect( admin_url( 'edit.php?post_type=hwbl_lesson&page=thw-premium-settings' ) );
		exit;
	}

	/**
	 * Whether a day-of-year slot already has a lesson.
	 *
	 * @param int $day_number Day number (1-366).
	 * @return bool
	 */
	private static function day_number_exists( $day_number ) {
		$day_number = (int) $day_number;
		if ( $day_number < 1 ) {
			return false;
		}

		if ( HWBL_Scheduler::get_lesson_id_by_day( $day_number ) > 0 ) {
			return true;
		}

		self::prime_existing_day_numbers();

		return isset( self::$existing_day_numbers[ $day_number ] );
	}

	/**
	 * Record a day number as mapped for the current request.
	 *
	 * @param int $day_number Day number.
	 */
	private static function mark_day_number_mapped( $day_number ) {
		$day_number = (int) $day_number;
		if ( $day_number < 1 ) {
			return;
		}

		self::prime_existing_day_numbers();
		self::$existing_day_numbers[ $day_number ] = true;
	}

	/**
	 * Load existing day numbers from lesson meta once per expand request.
	 */
	private static function prime_existing_day_numbers() {
		if ( null !== self::$existing_day_numbers ) {
			return;
		}

		self::$existing_day_numbers = array();

		$lesson_ids = get_posts(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		foreach ( $lesson_ids as $lesson_id ) {
			$day_number = (int) get_post_meta( $lesson_id, '_hwbl_day_number', true );
			if ( $day_number > 0 ) {
				self::$existing_day_numbers[ $day_number ] = true;
			}
		}
	}
}
