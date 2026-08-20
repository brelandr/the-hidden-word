<?php
/**
 * Personalized email digests with SRS due queue (Phase 5).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Personalized_Digest
 */
class HWBL_Personalized_Digest {

	/**
	 * Initialize digest personalization hooks.
	 */
	public static function init() {
		add_filter( 'hwbl_digest_email_sections', array( __CLASS__, 'append_srs_section' ), 10, 2 );
		add_filter( 'hwbl_digest_email_sections', array( __CLASS__, 'append_plan_section' ), 15, 2 );
	}

	/**
	 * Append "reviews due today" block to Premium digest emails.
	 *
	 * @param array<int, string> $sections Existing HTML sections.
	 * @param int                $user_id  Recipient user ID.
	 * @return array<int, string>
	 */
	public static function append_srs_section( $sections, $user_id ) {
		if ( ! class_exists( 'HWBL_Memorization_SRS' ) ) {
			return $sections;
		}

		$map   = HWBL_Memorization_SRS::get_progress_map( (int) $user_id );
		$today = wp_date( 'Y-m-d' );
		$due   = 0;

		foreach ( $map as $row ) {
			if ( ! empty( $row['due_date'] ) && $row['due_date'] <= $today ) {
				++$due;
			}
		}

		if ( $due < 1 ) {
			return $sections;
		}

		$sections[] = '<p>' . esc_html(
			sprintf(
				/* translators: %d: number of memorization reviews due */
				_n( '%d memorization review is due today.', '%d memorization reviews are due today.', $due, 'hidden-word-bible-lessons' ),
				$due
			)
		) . '</p>';

		return $sections;
	}

	/**
	 * Append today's reading-plan day when the user has an active plan.
	 *
	 * @param array<int, string> $sections Existing HTML sections.
	 * @param int                $user_id  Recipient user ID.
	 * @return array<int, string>
	 */
	public static function append_plan_section( $sections, $user_id ) {
		if ( ! class_exists( 'HWBL_Plan_Progress' ) || ! class_exists( 'HWBL_CPT_Plan' ) ) {
			return $sections;
		}

		$active = HWBL_Plan_Progress::get_active_for_user( (int) $user_id );
		if ( ! $active ) {
			return $sections;
		}

		$first = $active[0];
		$title = isset( $first['title'] ) ? (string) $first['title'] : '';
		$today = isset( $first['today'] ) && is_array( $first['today'] ) ? $first['today'] : null;
		$day_n = $today ? (int) $today['day'] : (int) ( $first['progress']['current_day'] ?? 0 );
		$day_t = $today && ! empty( $today['title'] ) ? (string) $today['title'] : '';
		$ref   = $today && ! empty( $today['verse_ref'] ) ? (string) $today['verse_ref'] : '';

		$line = sprintf(
			/* translators: 1: plan title, 2: day number */
			__( "Today's plan reading: %1\$s — Day %2\$d", 'hidden-word-bible-lessons' ),
			$title,
			$day_n
		);
		if ( $day_t ) {
			$line .= ': ' . $day_t;
		}
		if ( $ref ) {
			$line .= ' (' . $ref . ')';
		}

		$sections[] = '<p>' . esc_html( $line ) . '</p>';
		return $sections;
	}
}
