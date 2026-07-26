<?php
/**
 * iCal schedule export.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_ICal_Export
 */
class THW_Premium_ICal_Export {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'admin_post_thw_download_ical', array( __CLASS__, 'handle_download' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_export_link' ) );
	}

	/**
	 * Add export under lesson menu via settings notice link only — register handler.
	 */
	public static function add_export_link() {
		// Export triggered from Premium settings page form.
	}

	/**
	 * Handle iCal download.
	 */
	public static function handle_download() {
		check_admin_referer( 'thw_download_ical' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		$weeks = isset( $_GET['weeks'] ) ? min( 365, max( 1, absint( wp_unslash( $_GET['weeks'] ) ) ) ) : 52;
		$ics   = self::build_ics( $weeks );

		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="hidden-word-schedule.ics"' );
		echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Build ICS content for upcoming schedule slots.
	 *
	 * @param int $weeks Number of weeks/slots.
	 * @return string
	 */
	public static function build_ics( $weeks ) {
		$mode = get_option( 'hwbl_schedule_mode', 'week' );
		$lines   = array( 'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//The Hidden Word//Schedule//EN' );

		for ( $slot = 1; $slot <= $weeks; $slot++ ) {
			$lesson_num = HWBL_Curriculum::slot_to_lesson_number( $slot );
			$lesson_id  = HWBL_Scheduler::get_lesson_id_by_number( $lesson_num );
			if ( ! $lesson_id ) {
				continue;
			}
			$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
			$start  = self::slot_to_datetime( $slot, $mode );
			$end    = clone $start;
			$end->modify( '+1 hour' );

			$uid = 'thw-lesson-' . $lesson_id . '-' . $slot . '@' . wp_parse_url( home_url(), PHP_URL_HOST );
			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:' . $uid;
			$lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
			$lines[] = 'DTSTART:' . $start->format( 'Ymd\THis' );
			$lines[] = 'DTEND:' . $end->format( 'Ymd\THis' );
			$lines[] = 'SUMMARY:' . self::escape_ics( $lesson['reference'] );
			$lines[] = 'URL:' . get_permalink( $lesson_id );
			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';
		return implode( "\r\n", $lines );
	}

	/**
	 * Map schedule slot to datetime.
	 *
	 * @param int    $slot Slot.
	 * @param string $mode Schedule mode.
	 * @return DateTime
	 */
	private static function slot_to_datetime( $slot, $mode ) {
		$tz_string = wp_timezone_string();
		$dt        = new DateTime( 'now', new DateTimeZone( $tz_string ) );

		if ( 'day' === $mode ) {
			$dt->modify( '+' . ( $slot - 1 ) . ' days' );
		} else {
			$dt->modify( '+' . ( $slot - 1 ) . ' weeks' );
		}
		$dt->setTime( 8, 0, 0 );
		return $dt;
	}

	/**
	 * Escape ICS text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function escape_ics( $text ) {
		return str_replace( array( ',', ';', "\n" ), array( '\,', '\;', ' ' ), $text );
	}
}
