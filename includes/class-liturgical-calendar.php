<?php
/**
 * Western liturgical seasons for digest / VOTD notes and seasonal plans.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Liturgical_Calendar
 */
class HWBL_Liturgical_Calendar {

	const OPT_LAST_SEASON = 'hwbl_liturgical_last_season';
	const CRON_HOOK       = 'hwbl_liturgical_daily_check';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_schedule' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_daily_check' ) );
		add_filter( 'hwbl_digest_email_sections', array( __CLASS__, 'append_season_section' ), 20, 2 );
		add_filter( 'hwbl_votd_payload', array( __CLASS__, 'filter_votd_payload' ), 10, 3 );
		add_shortcode( 'hwbl_liturgical_season', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Schedule daily season check.
	 */
	public static function maybe_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Western Easter Sunday for a given year (Anonymous Gregorian algorithm).
	 *
	 * @param int $year Year.
	 * @return DateTimeImmutable
	 */
	public static function easter_sunday( $year ) {
		$year = (int) $year;
		$a    = $year % 19;
		$b    = intdiv( $year, 100 );
		$c    = $year % 100;
		$d    = intdiv( $b, 4 );
		$e    = $b % 4;
		$f    = intdiv( $b + 8, 25 );
		$g    = intdiv( $b - $f + 1, 3 );
		$h    = ( 19 * $a + $b - $d - $g + 15 ) % 30;
		$i    = intdiv( $c, 4 );
		$k    = $c % 4;
		$l    = ( 32 + 2 * $e + 2 * $i - $h - $k ) % 7;
		$m    = intdiv( $a + 11 * $h + 22 * $l, 451 );
		$month = intdiv( $h + $l - 7 * $m + 114, 31 );
		$day   = ( ( $h + $l - 7 * $m + 114 ) % 31 ) + 1;
		return new DateTimeImmutable( sprintf( '%04d-%02d-%02d', $year, $month, $day ), wp_timezone() );
	}

	/**
	 * Resolve season key for a date (site timezone).
	 *
	 * @param DateTimeInterface|null $when Optional date.
	 * @return string advent|christmas|lent|holy-week|eastertide|ordinary
	 */
	public static function get_season( $when = null ) {
		$tz  = wp_timezone();
		$now = $when instanceof DateTimeInterface
			? DateTimeImmutable::createFromInterface( $when )->setTimezone( $tz )
			: new DateTimeImmutable( 'now', $tz );
		$y   = (int) $now->format( 'Y' );
		$d   = $now->format( 'Y-m-d' );

		$easter    = self::easter_sunday( $y );
		$ash       = $easter->modify( '-46 days' );
		$palm      = $easter->modify( '-7 days' );
		$pentecost = $easter->modify( '+49 days' );
		$christmas = new DateTimeImmutable( $y . '-12-25', $tz );
		// Fourth Sunday before Christmas.
		$advent_start = $christmas->modify( 'previous sunday' );
		for ( $i = 0; $i < 3; $i++ ) {
			$advent_start = $advent_start->modify( '-7 days' );
		}
		$epiphany = new DateTimeImmutable( $y . '-01-06', $tz );
		$prev_christmas = new DateTimeImmutable( ( $y - 1 ) . '-12-25', $tz );

		if ( $d >= $advent_start->format( 'Y-m-d' ) && $d < $christmas->format( 'Y-m-d' ) ) {
			return 'advent';
		}
		if ( $d >= $christmas->format( 'Y-m-d' ) ) {
			return 'christmas';
		}
		if ( $d >= $prev_christmas->format( 'Y-m-d' ) && $d < $epiphany->format( 'Y-m-d' ) ) {
			return 'christmas';
		}
		if ( $d >= $ash->format( 'Y-m-d' ) && $d < $palm->format( 'Y-m-d' ) ) {
			return 'lent';
		}
		if ( $d >= $palm->format( 'Y-m-d' ) && $d < $easter->format( 'Y-m-d' ) ) {
			return 'holy-week';
		}
		if ( $d >= $easter->format( 'Y-m-d' ) && $d <= $pentecost->format( 'Y-m-d' ) ) {
			return 'eastertide';
		}
		return 'ordinary';
	}

	/**
	 * Human label for season.
	 *
	 * @param string $season Season key.
	 * @return string
	 */
	public static function season_label( $season ) {
		$labels = array(
			'advent'     => __( 'Advent', 'hidden-word-bible-lessons' ),
			'christmas'  => __( 'Christmas', 'hidden-word-bible-lessons' ),
			'lent'       => __( 'Lent', 'hidden-word-bible-lessons' ),
			'holy-week'  => __( 'Holy Week', 'hidden-word-bible-lessons' ),
			'eastertide' => __( 'Eastertide', 'hidden-word-bible-lessons' ),
			'ordinary'   => __( 'Ordinary Time', 'hidden-word-bible-lessons' ),
		);
		$season = sanitize_key( $season );
		return isset( $labels[ $season ] ) ? $labels[ $season ] : $labels['ordinary'];
	}

	/**
	 * Find a published plan for a season topic.
	 *
	 * @param string $topic Topic slug.
	 * @return array{id:int,title:string,url:string}|null
	 */
	public static function find_season_plan( $topic ) {
		$topic = sanitize_key( $topic );
		if ( ! $topic || ! class_exists( 'HWBL_CPT_Plan' ) ) {
			return null;
		}
		$q = new WP_Query(
			array(
				'post_type'      => HWBL_CPT_Plan::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => HWBL_CPT_Plan::META_TOPIC,
						'value' => $topic,
					),
				),
			)
		);
		if ( empty( $q->posts[0] ) ) {
			return null;
		}
		$id = (int) $q->posts[0];
		return array(
			'id'    => $id,
			'title' => get_the_title( $id ),
			'url'   => get_permalink( $id ) ? (string) get_permalink( $id ) : home_url( '/' ),
		);
	}

	/**
	 * Daily: detect season change and clear VOTD cache.
	 */
	public static function run_daily_check() {
		$season = self::get_season();
		$last   = (string) get_option( self::OPT_LAST_SEASON, '' );
		if ( $season === $last ) {
			return;
		}
		update_option( self::OPT_LAST_SEASON, $season, false );
		if ( class_exists( 'THW_Premium_Verse_Of_The_Day' ) && method_exists( 'THW_Premium_Verse_Of_The_Day', 'clear_cache_for_day' ) ) {
			THW_Premium_Verse_Of_The_Day::clear_cache_for_day( wp_date( 'Y-m-d' ) );
		} else {
			$day = wp_date( 'Y-m-d' );
			delete_transient( 'thw_votd_payload_' . $day );
		}
	}

	/**
	 * Digest blurb for the current season.
	 *
	 * @param array<int, string> $sections Sections.
	 * @param int                $user_id  User ID.
	 * @return array<int, string>
	 */
	public static function append_season_section( $sections, $user_id ) {
		unset( $user_id );
		$season = self::get_season();
		if ( 'ordinary' === $season ) {
			return $sections;
		}
		$label = self::season_label( $season );
		$html  = '<p><strong>' . esc_html( sprintf( /* translators: %s: season name */ __( 'This season: %s', 'hidden-word-bible-lessons' ), $label ) ) . '</strong></p>';
		$topic = ( 'advent' === $season || 'christmas' === $season ) ? 'advent' : ( ( 'lent' === $season || 'holy-week' === $season ) ? 'lent' : '' );
		if ( $topic ) {
			$plan = self::find_season_plan( $topic );
			if ( $plan ) {
				$html .= '<p>' . esc_html__( 'Suggested reading plan:', 'hidden-word-bible-lessons' ) . ' <a href="' . esc_url( $plan['url'] ) . '">' . esc_html( $plan['title'] ) . '</a></p>';
			}
		}
		$sections[] = $html;
		return $sections;
	}

	/**
	 * Attach seasonal note to VOTD payload.
	 *
	 * @param array<string,mixed> $payload     Payload.
	 * @param string              $day         Y-m-d.
	 * @param string              $translation Translation.
	 * @return array<string,mixed>
	 */
	public static function filter_votd_payload( $payload, $day, $translation ) {
		unset( $day, $translation );
		if ( ! is_array( $payload ) ) {
			return $payload;
		}
		$season = self::get_season();
		$payload['liturgical_season'] = $season;
		$payload['liturgical_label']  = self::season_label( $season );
		if ( 'ordinary' !== $season ) {
			$payload['seasonal_note'] = sprintf(
				/* translators: %s: season name */
				__( 'We are in %s. Consider a seasonal reading plan.', 'hidden-word-bible-lessons' ),
				self::season_label( $season )
			);
		}
		return $payload;
	}

	/**
	 * Shortcode [hwbl_liturgical_season].
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$season = self::get_season();
		$label  = self::season_label( $season );
		$html   = '<div class="hwbl-liturgical-season"><p>' . esc_html( sprintf( /* translators: %s: season */ __( 'Church season: %s', 'hidden-word-bible-lessons' ), $label ) ) . '</p>';
		$topic  = ( 'advent' === $season || 'christmas' === $season ) ? 'advent' : ( ( 'lent' === $season || 'holy-week' === $season ) ? 'lent' : '' );
		if ( $topic ) {
			$plan = self::find_season_plan( $topic );
			if ( $plan ) {
				$html .= '<p><a href="' . esc_url( $plan['url'] ) . '">' . esc_html( $plan['title'] ) . '</a></p>';
			}
		}
		$html .= '</div>';
		return $html;
	}
}
