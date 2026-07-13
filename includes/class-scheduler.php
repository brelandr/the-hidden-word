<?php
/**
 * Lesson scheduling — verse of the week/day.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Scheduler
 */
class THW_Scheduler {

	/**
	 * Option key for the lesson-number / day-number → post ID lookup map.
	 *
	 * @var string
	 */
	const LOOKUP_MAP_OPTION = 'thw_lesson_lookup_map';

	/**
	 * In-request cache of the lookup map.
	 *
	 * @var array<string, array<int, int>>|null
	 */
	private static $lookup_map_cache = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
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
		$mode = get_option( 'thw_schedule_mode', 'week' );

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
		return (int) gmdate( 'W' );
	}

	/**
	 * Get day of year (1-366).
	 *
	 * @return int
	 */
	public static function get_day_of_year() {
		return (int) gmdate( 'z' ) + 1;
	}

	/**
	 * Resolve a schedule slot to a lesson number in the bundled catalog.
	 *
	 * @param int $slot Schedule slot.
	 * @return int Lesson number (1-based).
	 */
	public static function slot_to_lesson_number( $slot ) {
		return THW_Curriculum::slot_to_lesson_number( $slot );
	}

	/**
	 * Get the lesson post ID for the current schedule slot.
	 *
	 * @return int Lesson post ID or 0.
	 */
	public static function get_current_lesson_id() {
		$slot       = self::get_current_slot();
		$mode       = get_option( 'thw_schedule_mode', 'week' );
		$lesson_num = self::slot_to_lesson_number( $slot );

		if ( 'day' === $mode ) {
			$lesson_id = self::get_lesson_id_by_day( $slot );
			if ( $lesson_id > 0 ) {
				return (int) apply_filters( 'thw_current_lesson_id', $lesson_id, $slot, $mode );
			}
		}

		$lesson_id = self::get_lesson_id_by_number( $lesson_num );

		return (int) apply_filters( 'thw_current_lesson_id', $lesson_id, $slot, $mode );
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
			return (int) $map['by_lesson'][ $lesson_number ];
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
			return (int) $map['by_day'][ $day_number ];
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
			'week' => __( 'Verse of the Week', 'the-hidden-word' ),
			'day'  => __( 'Verse of the Day', 'the-hidden-word' ),
		);

		return apply_filters( 'thw_schedule_modes', $modes );
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

		if ( empty( $map['by_lesson'] ) && empty( $map['by_day'] ) && get_option( 'thw_seeded' ) ) {
			self::rebuild_lookup_map();
			return self::$lookup_map_cache;
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
				'post_type'      => 'thw_lesson',
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
		if ( $post_id < 1 || 'thw_lesson' !== get_post_type( $post_id ) ) {
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
		if ( $post_id < 1 || 'thw_lesson' !== get_post_type( $post_id ) ) {
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
		$lesson_number = (int) get_post_meta( $post_id, '_thw_lesson_number', true );
		if ( $lesson_number > 0 ) {
			$map['by_lesson'][ $lesson_number ] = $post_id;
		} else {
			$week_number = (int) get_post_meta( $post_id, '_thw_week_number', true );
			if ( $week_number > 0 ) {
				$map['by_lesson'][ $week_number ] = $post_id;
			}
		}

		$day_number = (int) get_post_meta( $post_id, '_thw_day_number', true );
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
