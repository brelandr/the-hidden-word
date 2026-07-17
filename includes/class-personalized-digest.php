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
		add_filter( 'thw_digest_email_sections', array( __CLASS__, 'append_srs_section' ), 10, 2 );
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
}
