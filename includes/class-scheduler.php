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
	 * Constructor.
	 */
	public function __construct() {
		// Scheduler is used statically and via shortcodes.
	}

	/**
	 * Get current schedule slot number.
	 *
	 * @return int Week (1-52) or day (1-365) number.
	 */
	public static function get_current_slot() {
		$mode = get_option( 'thw_schedule_mode', 'week' );

		if ( 'day' === $mode ) {
			return self::get_day_of_year();
		}

		return self::get_week_of_year();
	}

	/**
	 * Get ISO week number (1-52).
	 *
	 * @return int
	 */
	public static function get_week_of_year() {
		return (int) gmdate( 'W' );
	}

	/**
	 * Get day of year (1-365).
	 *
	 * @return int
	 */
	public static function get_day_of_year() {
		return (int) gmdate( 'z' ) + 1;
	}

	/**
	 * Get the lesson post ID for the current schedule slot.
	 *
	 * @return int Lesson post ID or 0.
	 */
	public static function get_current_lesson_id() {
		$slot = self::get_current_slot();
		$mode = get_option( 'thw_schedule_mode', 'week' );

		if ( 'day' === $mode ) {
			$lessons = get_posts(
				array(
					'post_type'      => 'thw_lesson',
					'posts_per_page' => 1,
					'post_status'    => 'publish',
					'meta_key'       => '_thw_day_number',
					'meta_value'     => $slot,
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $lessons ) ) {
				return (int) apply_filters( 'thw_current_lesson_id', (int) $lessons[0], $slot, $mode );
			}

			$slot = ( ( $slot - 1 ) % 52 ) + 1;
		}

		$lessons = get_posts(
			array(
				'post_type'      => 'thw_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'meta_key'       => '_thw_week_number',
				'meta_value'     => $slot,
				'fields'         => 'ids',
			)
		);

		$lesson_id = ! empty( $lessons ) ? (int) $lessons[0] : 0;

		return (int) apply_filters( 'thw_current_lesson_id', $lesson_id, $slot, $mode );
	}

	/**
	 * Get lesson ID by week number.
	 *
	 * @param int $week Week number.
	 * @return int
	 */
	public static function get_lesson_id_by_week( $week ) {
		$lessons = get_posts(
			array(
				'post_type'      => 'thw_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'meta_key'       => '_thw_week_number',
				'meta_value'     => (int) $week,
				'fields'         => 'ids',
			)
		);

		return ! empty( $lessons ) ? (int) $lessons[0] : 0;
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
}
