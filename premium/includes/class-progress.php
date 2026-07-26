<?php
/**
 * User memorization progress tracking.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Progress
 */
class THW_Premium_Progress {

	const USER_META_KEY = 'thw_memorized_lessons';
	const STREAK_META_KEY = 'thw_streak';
	const STREAK_CLAIMED_META_KEY = 'thw_streak_claimed';

	const BADGE_MILESTONES = array( 7, 30, 100 );

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		thw_premium_register_shortcode( 'thw_my_progress', array( __CLASS__, 'render_progress_shortcode' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST routes for streak claims.
	 *
	 * POST /thw/v1/claim-streak (and hwbl/v1/memorize/claim-streak when SRS is
	 * loaded) merges a browser-local streak into user meta after login.
	 * Cookie auth supplies the REST nonce; capability gate is read (logged-in).
	 */
	public static function register_routes() {
		$route = array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'rest_claim_streak' ),
			'permission_callback' => static function () {
				return current_user_can( 'read' );
			},
			'args'                => array(
				'count'     => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'last_date' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		);

		register_rest_route( 'thw/v1', '/claim-streak', $route );

		if ( class_exists( 'HWBL_Memorization_SRS' ) ) {
			register_rest_route(
				'hwbl/v1',
				'/memorize/claim-streak',
				array_merge(
					$route,
					array( 'callback' => array( 'HWBL_Memorization_SRS', 'rest_claim_streak' ) )
				)
			);
		}
	}

	/**
	 * Claim a browser-local streak after login.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_claim_streak( $request ) {
		if ( class_exists( 'HWBL_Memorization_SRS' ) ) {
			return HWBL_Memorization_SRS::rest_claim_streak( $request );
		}

		$params    = $request->get_json_params();
		$count     = isset( $params['count'] ) ? max( 0, (int) $params['count'] ) : (int) $request->get_param( 'count' );
		$last_date = isset( $params['last_date'] ) ? sanitize_text_field( $params['last_date'] ) : sanitize_text_field( (string) $request->get_param( 'last_date' ) );
		$user_id   = get_current_user_id();

		if ( get_user_meta( $user_id, self::STREAK_CLAIMED_META_KEY, true ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => 'already_claimed' ), 400 );
		}

		if ( ! self::can_claim_local_streak( $count, $last_date ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => 'dates_mismatch' ), 400 );
		}

		$streak = self::get_streak( $user_id );
		if ( (int) $streak['current'] < $count ) {
			$streak['current']   = $count;
			$streak['longest']   = max( (int) $streak['longest'], $count );
			$streak['last_date'] = $last_date;
			update_user_meta( $user_id, self::STREAK_META_KEY, $streak );
		}

		update_user_meta( $user_id, self::STREAK_CLAIMED_META_KEY, 1 );
		return new WP_REST_Response( array( 'success' => true, 'streak' => self::get_streak( $user_id ) ) );
	}

	/**
	 * Whether local streak dates align enough to claim.
	 *
	 * @param int    $count     Local streak count.
	 * @param string $last_date Local last practice date (Y-m-d).
	 * @return bool
	 */
	public static function can_claim_local_streak( $count, $last_date ) {
		if ( $count < 1 || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $last_date ) ) {
			return false;
		}

		$today     = wp_date( 'Y-m-d' );
		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', strtotime( $today ) ) );
		return in_array( $last_date, array( $today, $yesterday ), true );
	}

	/**
	 * Get the most recent memorization activity timestamp for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function get_last_activity_date( $user_id ) {
		$progress = self::get_user_progress( $user_id );
		$latest   = '';
		foreach ( $progress as $entry ) {
			$ts = isset( $entry['timestamp'] ) ? $entry['timestamp'] : '';
			if ( $ts && ( ! $latest || strtotime( $ts ) > strtotime( $latest ) ) ) {
				$latest = $ts;
			}
		}
		return $latest;
	}

	/**
	 * Get user progress array.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array>
	 */
	public static function get_user_progress( $user_id ) {
		$data = get_user_meta( $user_id, self::USER_META_KEY, true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Check if lesson is memorized.
	 *
	 * @param int $user_id   User ID.
	 * @param int $lesson_id Lesson ID.
	 * @return bool
	 */
	public static function is_memorized( $user_id, $lesson_id ) {
		$progress = self::get_user_progress( $user_id );
		return isset( $progress[ $lesson_id ] );
	}

	/**
	 * Mark lesson as memorized.
	 *
	 * @param int $user_id   User ID.
	 * @param int $lesson_id Lesson ID.
	 */
	public static function mark_memorized( $user_id, $lesson_id ) {
		$progress = self::get_user_progress( $user_id );
		$progress[ $lesson_id ] = array(
			'timestamp' => current_time( 'mysql' ),
			'lesson_id' => $lesson_id,
		);
		update_user_meta( $user_id, self::USER_META_KEY, $progress );
		self::update_streak( $user_id );
	}

	/**
	 * Get user streak data.
	 *
	 * @param int $user_id User ID.
	 * @return array{current: int, longest: int, last_date: string}
	 */
	public static function get_streak( $user_id ) {
		$data = get_user_meta( $user_id, self::STREAK_META_KEY, true );
		if ( ! is_array( $data ) ) {
			return array(
				'current'   => 0,
				'longest'   => 0,
				'last_date' => '',
			);
		}
		return wp_parse_args(
			$data,
			array(
				'current'   => 0,
				'longest'   => 0,
				'last_date' => '',
			)
		);
	}

	/**
	 * Update streak after memorization activity.
	 *
	 * @param int $user_id User ID.
	 */
	public static function update_streak( $user_id ) {
		$today  = wp_date( 'Y-m-d' );
		$streak = self::get_streak( $user_id );

		if ( $streak['last_date'] === $today ) {
			return;
		}

		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', strtotime( $today ) ) );
		if ( $streak['last_date'] === $yesterday ) {
			$streak['current'] = (int) $streak['current'] + 1;
		} else {
			$streak['current'] = 1;
		}

		$streak['last_date'] = $today;
		$streak['longest']   = max( (int) $streak['longest'], (int) $streak['current'] );
		update_user_meta( $user_id, self::STREAK_META_KEY, $streak );
	}

	/**
	 * Earned badge labels for memorization milestones.
	 *
	 * @param int $user_id User ID.
	 * @return string[]
	 */
	public static function get_badges( $user_id ) {
		$count  = count( self::get_user_progress( $user_id ) );
		$badges = array();
		foreach ( self::BADGE_MILESTONES as $milestone ) {
			if ( $count >= $milestone ) {
				$badges[] = sprintf(
					/* translators: %d: lesson count milestone */
					__( '%d lessons memorized', 'hidden-word-bible-lessons' ),
					$milestone
				);
			}
		}
		return $badges;
	}

	/**
	 * Render progress dashboard shortcode.
	 *
	 * @return string
	 */
	public static function render_progress_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your memorization progress.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$user_id  = get_current_user_id();
		$progress = self::get_user_progress( $user_id );
		$streak   = self::get_streak( $user_id );
		$badges   = self::get_badges( $user_id );

		$html  = '<div class="thw-progress-dashboard">';
		$html .= '<h3>' . esc_html__( 'My Memorization Progress', 'hidden-word-bible-lessons' ) . '</h3>';
		$html .= '<p>' . esc_html( sprintf(
			/* translators: %d: count of memorized lessons */
			_n( '%d lesson memorized', '%d lessons memorized', count( $progress ), 'hidden-word-bible-lessons' ),
			count( $progress )
		) ) . '</p>';

		if ( (int) $streak['current'] > 0 ) {
			$html .= '<p class="thw-streak-badge">' . esc_html( sprintf(
				/* translators: %d: streak day count */
				_n( 'Day %d streak', 'Day %d streak', (int) $streak['current'], 'hidden-word-bible-lessons' ),
				(int) $streak['current']
			) ) . '</p>';
		}

		if ( ! empty( $badges ) ) {
			$html .= '<ul class="thw-badges">';
			foreach ( $badges as $badge ) {
				$html .= '<li>' . esc_html( $badge ) . '</li>';
			}
			$html .= '</ul>';
		}

		if ( ! empty( $progress ) ) {
			$html .= '<ul class="thw-progress-list">';
			foreach ( $progress as $lesson_id => $entry ) {
				$title = get_the_title( $lesson_id );
				$date  = isset( $entry['timestamp'] ) ? $entry['timestamp'] : '';
				$html .= '<li>';
				$html .= '<strong>' . esc_html( $title ) . '</strong>';
				if ( $date ) {
					$html .= ' <span class="thw-progress-date">(' . esc_html( $date ) . ')</span>';
				}
				$html .= '</li>';
			}
			$html .= '</ul>';
		}

		$html .= '</div>';
		return $html;
	}
}
