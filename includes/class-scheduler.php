<?php
/**
 * Lesson scheduling — verse of the week/day.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Scheduler
 */
class HWBL_Scheduler {

	/**
	 * Option key for the lesson-number / day-number → post ID lookup map.
	 *
	 * @var string
	 */
	const LOOKUP_MAP_OPTION = 'hwbl_lesson_lookup_map';

	/**
	 * In-request cache of the lookup map.
	 *
	 * @var array<string, array<int, int>>|null
	 */
	private static $lookup_map_cache = null;

	/**
	 * Whether the lookup map was rebuilt once this request after a stale entry.
	 *
	 * @var bool
	 */
	private static $lookup_rebuilt_this_request = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'save_post_hwbl_lesson', array( __CLASS__, 'refresh_lookup_map_for_post' ), 20, 1 );
		add_action( 'save_post_thw_lesson', array( __CLASS__, 'refresh_lookup_map_for_post' ), 20, 1 );
		add_action( 'delete_post', array( __CLASS__, 'handle_post_removed' ), 10, 1 );
		add_action( 'trashed_post', array( __CLASS__, 'handle_post_removed' ), 10, 1 );
	}

	/**
	 * Get current schedule slot number.
	 *
	 * @return int Week (1-53) or day (1-366) number.
	 */
	public static function get_current_slot() {
		$mode = get_option( 'hwbl_schedule_mode', 'week' );

		if ( 'day' === $mode ) {
			return self::get_day_of_year();
		}

		return self::get_week_of_year();
	}

	/**
	 * Get ISO week number (1-53).
	 *
	 * @return int
	 */
	public static function get_week_of_year() {
		return (int) wp_date( 'W' );
	}

	/**
	 * Get day of year (1-366) in the site timezone.
	 *
	 * @return int
	 */
	public static function get_day_of_year() {
		return (int) wp_date( 'z' ) + 1;
	}

	/**
	 * Today's calendar date in the site timezone (Y-m-d).
	 *
	 * @return string
	 */
	public static function get_site_date() {
		return wp_date( 'Y-m-d' );
	}

	/**
	 * Resolve a schedule slot to a lesson number in the bundled catalog.
	 *
	 * @param int $slot Schedule slot.
	 * @return int Lesson number (1-based).
	 */
	public static function slot_to_lesson_number( $slot ) {
		return HWBL_Curriculum::slot_to_lesson_number( $slot );
	}

	/**
	 * Get the lesson post ID for the current schedule slot.
	 *
	 * @return int Lesson post ID or 0.
	 */
	public static function get_current_lesson_id() {
		$slot       = self::get_current_slot();
		$mode       = get_option( 'hwbl_schedule_mode', 'week' );
		$lesson_num = self::slot_to_lesson_number( $slot );

		if ( 'day' === $mode ) {
			$lesson_id = self::get_lesson_id_by_day( $slot );
			if ( $lesson_id > 0 ) {
				return (int) apply_filters( 'hwbl_current_lesson_id', $lesson_id, $slot, $mode );
			}
		}

		$lesson_id = self::get_lesson_id_by_number( $lesson_num );
		if ( $lesson_id > 0 ) {
			return (int) apply_filters( 'hwbl_current_lesson_id', $lesson_id, $slot, $mode );
		}

		return 0;
	}

	/**
	 * Return a lesson post ID only when the post exists and is loadable.
	 *
	 * @param int $lesson_id Candidate lesson post ID.
	 * @return int
	 */
	public static function resolve_lesson_id( $lesson_id ) {
		$lesson_id = (int) $lesson_id;
		if ( $lesson_id < 1 ) {
			return 0;
		}

		$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );

		return HWBL_CPT_Lesson::is_valid_lesson_data( $lesson ) ? $lesson_id : 0;
	}

	/**
	 * Post types queried when building the lesson lookup map.
	 *
	 * @return string[]
	 */
	private static function lesson_post_types() {
		return array( 'hwbl_lesson', 'thw_lesson' );
	}

	/**
	 * Get lesson ID by lesson number.
	 *
	 * @param int $lesson_number Lesson number.
	 * @return int
	 */
	public static function get_lesson_id_by_number( $lesson_number ) {
		$lesson_number = (int) $lesson_number;
		if ( $lesson_number < 1 ) {
			return 0;
		}

		$map = self::get_lookup_map();

		if ( isset( $map['by_lesson'][ $lesson_number ] ) ) {
			$lesson_id = self::resolve_lesson_id( (int) $map['by_lesson'][ $lesson_number ] );
			if ( $lesson_id > 0 ) {
				return $lesson_id;
			}

			if ( ! self::$lookup_rebuilt_this_request ) {
				self::$lookup_rebuilt_this_request = true;
				self::rebuild_lookup_map();
				return self::get_lesson_id_by_number( $lesson_number );
			}
		}

		return 0;
	}

	/**
	 * Get lesson ID by day-of-year number (365-day track).
	 *
	 * @param int $day_number Day number (1-366).
	 * @return int
	 */
	public static function get_lesson_id_by_day( $day_number ) {
		$day_number = (int) $day_number;
		if ( $day_number < 1 ) {
			return 0;
		}

		$map = self::get_lookup_map();

		if ( isset( $map['by_day'][ $day_number ] ) ) {
			$lesson_id = self::resolve_lesson_id( (int) $map['by_day'][ $day_number ] );
			if ( $lesson_id > 0 ) {
				return $lesson_id;
			}

			if ( ! self::$lookup_rebuilt_this_request ) {
				self::$lookup_rebuilt_this_request = true;
				self::rebuild_lookup_map();
				return self::get_lesson_id_by_day( $day_number );
			}
		}

		return 0;
	}

	/**
	 * Get lesson ID by week number (legacy alias).
	 *
	 * @param int $week Week number.
	 * @return int
	 */
	public static function get_lesson_id_by_week( $week ) {
		return self::get_lesson_id_by_number( $week );
	}

	/**
	 * Get available schedule modes.
	 *
	 * @return array<string, string>
	 */
	public static function get_schedule_modes() {
		$modes = array(
			'week' => __( 'Verse of the Week', 'hidden-word-bible-lessons' ),
			'day'  => __( 'Verse of the Day', 'hidden-word-bible-lessons' ),
		);

		return apply_filters( 'hwbl_schedule_modes', $modes );
	}

	/**
	 * Current schedule mode slug.
	 *
	 * @return string
	 */
	public static function get_current_mode() {
		$mode  = sanitize_key( (string) get_option( 'hwbl_schedule_mode', 'week' ) );
		$modes = self::get_schedule_modes();
		if ( '' === $mode || ! isset( $modes[ $mode ] ) ) {
			return 'week';
		}
		return $mode;
	}

	/**
	 * User-facing phrase for the current schedule (CTAs, nav, headings).
	 *
	 * Kinds:
	 * - memorize: primary CTA ("Today's Verse to Memorize")
	 * - heading:  section heading ("Today's Verse")
	 * - compact:  short label / widget ("Verse of the Day")
	 * - blurb:    one-line supporting copy
	 * - catalog:  catalog browsing label
	 * - find:     topic-search label
	 *
	 * @param string $kind Phrase kind.
	 * @param string $mode Optional mode override.
	 * @return string
	 */
	public static function get_schedule_phrase( $kind = 'memorize', $mode = '' ) {
		$kind = sanitize_key( (string) $kind );
		$mode = sanitize_key( (string) $mode );
		if ( '' === $mode ) {
			$mode = self::get_current_mode();
		}

		$phrases = array(
			'week'   => array(
				'memorize' => __( "This Week's Verse to Memorize", 'hidden-word-bible-lessons' ),
				'heading'  => __( "This Week's Verse", 'hidden-word-bible-lessons' ),
				'compact'  => __( 'Verse of the Week', 'hidden-word-bible-lessons' ),
				'blurb'    => __( "Read and memorize this week's scripture.", 'hidden-word-bible-lessons' ),
				'catalog'  => __( 'Verse Catalog', 'hidden-word-bible-lessons' ),
				'find'     => __( 'Find a Verse', 'hidden-word-bible-lessons' ),
			),
			'day'    => array(
				'memorize' => __( "Today's Verse to Memorize", 'hidden-word-bible-lessons' ),
				'heading'  => __( "Today's Verse", 'hidden-word-bible-lessons' ),
				'compact'  => __( 'Verse of the Day', 'hidden-word-bible-lessons' ),
				'blurb'    => __( "Read and memorize today's scripture.", 'hidden-word-bible-lessons' ),
				'catalog'  => __( 'Verse Catalog', 'hidden-word-bible-lessons' ),
				'find'     => __( 'Find a Verse', 'hidden-word-bible-lessons' ),
			),
			'month'  => array(
				'memorize' => __( "This Month's Verse to Memorize", 'hidden-word-bible-lessons' ),
				'heading'  => __( "This Month's Verse", 'hidden-word-bible-lessons' ),
				'compact'  => __( 'Verse of the Month', 'hidden-word-bible-lessons' ),
				'blurb'    => __( "Read and memorize this month's scripture.", 'hidden-word-bible-lessons' ),
				'catalog'  => __( 'Verse Catalog', 'hidden-word-bible-lessons' ),
				'find'     => __( 'Find a Verse', 'hidden-word-bible-lessons' ),
			),
			'manual' => array(
				'memorize' => __( 'Verse to Memorize', 'hidden-word-bible-lessons' ),
				'heading'  => __( 'Current Verse', 'hidden-word-bible-lessons' ),
				'compact'  => __( 'Current Verse', 'hidden-word-bible-lessons' ),
				'blurb'    => __( 'Read and memorize the selected scripture.', 'hidden-word-bible-lessons' ),
				'catalog'  => __( 'Verse Catalog', 'hidden-word-bible-lessons' ),
				'find'     => __( 'Find a Verse', 'hidden-word-bible-lessons' ),
			),
			'custom' => array(
				'memorize' => __( 'Verse to Memorize', 'hidden-word-bible-lessons' ),
				'heading'  => __( 'Current Verse', 'hidden-word-bible-lessons' ),
				'compact'  => __( 'Track Verse', 'hidden-word-bible-lessons' ),
				'blurb'    => __( 'Read and memorize the next verse on your reading track.', 'hidden-word-bible-lessons' ),
				'catalog'  => __( 'Verse Catalog', 'hidden-word-bible-lessons' ),
				'find'     => __( 'Find a Verse', 'hidden-word-bible-lessons' ),
			),
		);

		$phrases = apply_filters( 'hwbl_schedule_phrases', $phrases, $mode, $kind );

		if ( ! isset( $phrases[ $mode ] ) ) {
			$mode = 'week';
		}
		if ( ! isset( $phrases[ $mode ][ $kind ] ) ) {
			$kind = 'memorize';
		}

		return (string) $phrases[ $mode ][ $kind ];
	}

	/**
	 * Return the cached lesson lookup map.
	 *
	 * @return array{by_lesson: array<int, int>, by_day: array<int, int>}
	 */
	public static function get_lookup_map() {
		if ( null !== self::$lookup_map_cache ) {
			return self::$lookup_map_cache;
		}

		$map = get_option( self::LOOKUP_MAP_OPTION, array() );
		if ( ! is_array( $map ) ) {
			$map = array();
		}

		if ( ! isset( $map['by_lesson'] ) || ! is_array( $map['by_lesson'] ) ) {
			$map['by_lesson'] = array();
		}
		if ( ! isset( $map['by_day'] ) || ! is_array( $map['by_day'] ) ) {
			$map['by_day'] = array();
		}

		if ( empty( $map['by_lesson'] ) && empty( $map['by_day'] ) ) {
			$should_rebuild = (bool) get_option( 'hwbl_seeded' );

			if ( ! $should_rebuild ) {
				$existing = get_posts(
					array(
						'post_type'      => self::lesson_post_types(),
						'post_status'    => 'any',
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				);
				$should_rebuild = ! empty( $existing );
			}

			if ( $should_rebuild ) {
				self::rebuild_lookup_map();
				return self::$lookup_map_cache;
			}
		}

		self::$lookup_map_cache = $map;

		return $map;
	}

	/**
	 * Rebuild the lookup map from all lesson posts (activation / seed completion).
	 */
	public static function rebuild_lookup_map() {
		$map = array(
			'by_lesson' => array(),
			'by_day'    => array(),
		);

		$lesson_ids = get_posts(
			array(
				'post_type'      => self::lesson_post_types(),
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
				'fields'         => 'ids',
			)
		);

		foreach ( $lesson_ids as $lesson_id ) {
			self::add_post_to_lookup_map( $map, (int) $lesson_id );
		}

		self::$lookup_map_cache = $map;
		update_option( self::LOOKUP_MAP_OPTION, $map, false );
	}

	/**
	 * Update the lookup map after a lesson is saved in the admin.
	 *
	 * @param int $post_id Lesson post ID.
	 */
	public static function refresh_lookup_map_for_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 || ! HWBL_CPT_Lesson::is_lesson_post_type( get_post_type( $post_id ) ) ) {
			return;
		}

		if ( 'trash' === get_post_status( $post_id ) ) {
			self::handle_post_removed( $post_id );
			return;
		}

		$map = self::get_lookup_map();
		self::purge_post_id_from_map( $map, $post_id );
		self::add_post_to_lookup_map( $map, $post_id );
		self::$lookup_map_cache = $map;
		update_option( self::LOOKUP_MAP_OPTION, $map, false );
	}

	/**
	 * Remove a lesson from the lookup map when it is deleted or trashed.
	 *
	 * @param int $post_id Lesson post ID.
	 */
	public static function handle_post_removed( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id < 1 || ! HWBL_CPT_Lesson::is_lesson_post_type( get_post_type( $post_id ) ) ) {
			return;
		}

		$map = self::get_lookup_map();
		self::purge_post_id_from_map( $map, $post_id );
		self::$lookup_map_cache = $map;
		update_option( self::LOOKUP_MAP_OPTION, $map, false );
	}

	/**
	 * Add one lesson post's meta keys to a lookup map array.
	 *
	 * @param array{by_lesson: array<int, int>, by_day: array<int, int>} $map     Map to mutate.
	 * @param int                                                         $post_id Lesson post ID.
	 */
	private static function add_post_to_lookup_map( array &$map, $post_id ) {
		$lesson_number = (int) HWBL_CPT_Lesson::get_meta_value( $post_id, 'lesson_number' );
		if ( $lesson_number > 0 ) {
			$map['by_lesson'][ $lesson_number ] = $post_id;
		} else {
			$week_number = (int) HWBL_CPT_Lesson::get_meta_value( $post_id, 'week_number' );
			if ( $week_number > 0 ) {
				$map['by_lesson'][ $week_number ] = $post_id;
			}
		}

		$day_number = (int) HWBL_CPT_Lesson::get_meta_value( $post_id, 'day_number' );
		if ( $day_number > 0 ) {
			$map['by_day'][ $day_number ] = $post_id;
		}
	}

	/**
	 * Remove every map entry that points at a given post ID.
	 *
	 * @param array{by_lesson: array<int, int>, by_day: array<int, int>} $map     Map to mutate.
	 * @param int                                                         $post_id Lesson post ID.
	 */
	private static function purge_post_id_from_map( array &$map, $post_id ) {
		foreach ( $map['by_lesson'] as $key => $mapped_id ) {
			if ( (int) $mapped_id === $post_id ) {
				unset( $map['by_lesson'][ $key ] );
			}
		}

		foreach ( $map['by_day'] as $key => $mapped_id ) {
			if ( (int) $mapped_id === $post_id ) {
				unset( $map['by_day'][ $key ] );
			}
		}
	}
}
