<?php
/**
 * SM-2 spaced repetition and server-side memorization progress.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Memorization_SRS
 */
class HWBL_Memorization_SRS {

	const PROGRESS_META_KEY = 'hwbl_srs_progress';
	const STREAK_META_KEY   = 'hwbl_streak';
	const STREAK_CLAIMED    = 'hwbl_streak_claimed';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_shortcode( 'hwbl_memorize_reviews', array( __CLASS__, 'render_reviews_shortcode' ) );
		add_shortcode( 'thw_memorize_reviews', array( __CLASS__, 'render_reviews_shortcode' ) );
	}

	/**
	 * Register SRS REST routes under hwbl/v1.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'hwbl/v1',
			'/memorize/review-queue',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_review_queue' ),
				'permission_callback' => array( __CLASS__, 'logged_in_permission' ),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/memorize/review',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_submit_review' ),
				'permission_callback' => array( __CLASS__, 'logged_in_permission' ),
				'args'                => array(
					'lesson_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'quality'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'mode'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => 'hide',
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/memorize/progress',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_progress' ),
				'permission_callback' => array( __CLASS__, 'logged_in_permission' ),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/memorize/practice',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_record_practice' ),
				'permission_callback' => array( __CLASS__, 'logged_in_permission' ),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/memorize/enroll',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_enroll_lesson' ),
				'permission_callback' => array( __CLASS__, 'logged_in_permission' ),
				'args'                => array(
					'lesson_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/memorize/claim-streak',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_claim_streak' ),
				'permission_callback' => array( __CLASS__, 'logged_in_permission' ),
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
			)
		);
	}

	/**
	 * REST permission: logged-in users only.
	 *
	 * @return bool|WP_Error
	 */
	public static function logged_in_permission() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'hwbl_login_required',
				__( 'Sign in to save memorization progress.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * GET review queue for current user.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_review_queue() {
		$user_id = get_current_user_id();
		self::maybe_migrate_legacy_progress( $user_id );

		return new WP_REST_Response( self::build_queue_payload( $user_id ) );
	}

	/**
	 * Build review queue payload for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<string, mixed>
	 */
	public static function build_queue_payload( $user_id ) {
		$all   = self::get_progress_map( $user_id );
		$today = wp_date( 'Y-m-d' );
		$due   = array();
		$new   = array();

		foreach ( self::get_review_lesson_ids( $user_id ) as $lesson_id ) {
			if ( isset( $all[ $lesson_id ] ) ) {
				$row = $all[ $lesson_id ];
				if ( ! empty( $row['due_date'] ) && $row['due_date'] <= $today ) {
					$due[] = self::format_queue_item( $lesson_id, $row );
				}
			} else {
				$new[] = self::format_queue_item( $lesson_id, self::default_card( $lesson_id ) );
			}
		}

		return array(
			'due'    => $due,
			'new'    => array_slice( $new, 0, 5 ),
			'streak' => self::get_streak( $user_id ),
			'stats'  => self::get_progress_stats( $user_id ),
		);
	}

	/**
	 * Progress summary counts.
	 *
	 * @param int $user_id User ID.
	 * @return array{total:int,due:int,learning:int}
	 */
	public static function get_progress_stats( $user_id ) {
		$map   = self::get_progress_map( $user_id );
		$today = wp_date( 'Y-m-d' );
		$due   = 0;
		$learning = 0;

		foreach ( $map as $row ) {
			if ( ! empty( $row['repetitions'] ) ) {
				++$learning;
			}
			if ( ! empty( $row['due_date'] ) && $row['due_date'] <= $today ) {
				++$due;
			}
		}

		return array(
			'total'    => count( $map ),
			'due'      => $due,
			'learning' => $learning,
		);
	}

	/**
	 * POST enroll a lesson in the SRS queue.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_enroll_lesson( $request ) {
		$user_id   = get_current_user_id();
		$lesson_id = (int) $request['lesson_id'];

		if ( $lesson_id < 1 || 'hwbl_lesson' !== get_post_type( $lesson_id ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_lesson' ), 400 );
		}

		$map = self::get_progress_map( $user_id );
		if ( ! isset( $map[ $lesson_id ] ) ) {
			$map[ $lesson_id ] = self::default_card( $lesson_id );
			update_user_meta( $user_id, self::PROGRESS_META_KEY, $map );
		}

		return new WP_REST_Response(
			array(
				'lesson_id' => $lesson_id,
				'card'      => $map[ $lesson_id ],
				'queue'     => self::build_queue_payload( $user_id ),
			)
		);
	}

	/**
	 * Render spaced-repetition review dashboard shortcode.
	 *
	 * @return string
	 */
	public static function render_reviews_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p class="hwbl-notice">' . esc_html__( 'Sign in to view your memorization review queue.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		ob_start();
		include HWBL_PLUGIN_DIR . 'public/partials/memorization-reviews.php';
		return (string) ob_get_clean();
	}

	/**
	 * POST review result and update SM-2 state.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_submit_review( $request ) {
		$user_id   = get_current_user_id();
		$lesson_id = (int) $request['lesson_id'];
		$quality   = max( 0, min( 5, (int) $request['quality'] ) );
		$mode      = sanitize_key( (string) $request['mode'] );

		if ( $lesson_id < 1 || 'hwbl_lesson' !== get_post_type( $lesson_id ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_lesson' ), 400 );
		}

		$map  = self::get_progress_map( $user_id );
		$card = isset( $map[ $lesson_id ] ) ? $map[ $lesson_id ] : self::default_card( $lesson_id );
		$card = self::apply_sm2( $card, $quality, $mode );
		$map[ $lesson_id ] = $card;
		update_user_meta( $user_id, self::PROGRESS_META_KEY, $map );
		self::update_streak( $user_id );
		self::maybe_migrate_premium_memorized( $user_id, $lesson_id );

		return new WP_REST_Response(
			array(
				'lesson_id' => $lesson_id,
				'card'      => $card,
				'streak'    => self::get_streak( $user_id ),
			)
		);
	}

	/**
	 * GET progress summary.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_progress() {
		$user_id = get_current_user_id();
		self::maybe_migrate_legacy_progress( $user_id );
		$map     = self::get_progress_map( $user_id );
		$stats   = self::get_progress_stats( $user_id );

		return new WP_REST_Response(
			array(
				'total'   => count( $map ),
				'due'     => $stats['due'],
				'learning'=> $stats['learning'],
				'streak'  => self::get_streak( $user_id ),
				'cards'   => $map,
				'stats'   => $stats,
			)
		);
	}

	/**
	 * POST daily practice ping (streak only, no SM-2 card change).
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_record_practice() {
		$user_id = get_current_user_id();
		self::update_streak( $user_id );

		return new WP_REST_Response(
			array(
				'streak' => self::get_streak( $user_id ),
			)
		);
	}

	/**
	 * Claim browser-local streak after login.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_claim_streak( $request ) {
		$user_id   = get_current_user_id();
		$count     = max( 0, (int) $request['count'] );
		$last_date = sanitize_text_field( (string) $request['last_date'] );

		if ( get_user_meta( $user_id, self::STREAK_CLAIMED, true ) ) {
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

		update_user_meta( $user_id, self::STREAK_CLAIMED, 1 );

		return new WP_REST_Response( array( 'success' => true, 'streak' => self::get_streak( $user_id ) ) );
	}

	/**
	 * SM-2 interval update.
	 *
	 * @param array<string, mixed> $card    Card state.
	 * @param int                  $quality Quality 0-5.
	 * @param string               $mode    Practice mode slug.
	 * @return array<string, mixed>
	 */
	public static function apply_sm2( $card, $quality, $mode = 'hide' ) {
		$quality = max( 0, min( 5, (int) $quality ) );
		$ef      = isset( $card['ease_factor'] ) ? (float) $card['ease_factor'] : 2.5;
		$rep     = isset( $card['repetitions'] ) ? (int) $card['repetitions'] : 0;
		$interval = isset( $card['interval_days'] ) ? (int) $card['interval_days'] : 0;

		$ef = $ef + ( 0.1 - ( 5 - $quality ) * ( 0.08 + ( 5 - $quality ) * 0.02 ) );
		if ( $ef < 1.3 ) {
			$ef = 1.3;
		}

		if ( $quality < 3 ) {
			$rep      = 0;
			$interval = 1;
		} else {
			++$rep;
			if ( 1 === $rep ) {
				$interval = 1;
			} elseif ( 2 === $rep ) {
				$interval = 6;
			} else {
				$interval = (int) round( $interval * $ef );
				if ( $interval < 1 ) {
					$interval = 1;
				}
			}
		}

		$due = wp_date( 'Y-m-d', strtotime( '+' . $interval . ' days', strtotime( wp_date( 'Y-m-d' ) ) ) );

		$card['ease_factor']   = round( $ef, 2 );
		$card['repetitions']   = $rep;
		$card['interval_days'] = $interval;
		$card['due_date']      = $due;
		$card['last_review']   = wp_date( 'Y-m-d' );
		$card['last_mode']     = sanitize_key( (string) $mode );
		$card['last_quality']  = $quality;

		return $card;
	}

	/**
	 * Default card for a lesson.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return array<string, mixed>
	 */
	public static function default_card( $lesson_id ) {
		return array(
			'lesson_id'     => (int) $lesson_id,
			'ease_factor'   => 2.5,
			'interval_days' => 0,
			'repetitions'   => 0,
			'due_date'      => wp_date( 'Y-m-d' ),
			'last_review'   => '',
			'last_mode'     => '',
			'last_quality'  => 0,
		);
	}

	/**
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_progress_map( $user_id ) {
		$data = get_user_meta( (int) $user_id, self::PROGRESS_META_KEY, true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * @param int $user_id User ID.
	 * @return array{current:int,longest:int,last_date:string}
	 */
	public static function get_streak( $user_id ) {
		$data = get_user_meta( (int) $user_id, self::STREAK_META_KEY, true );
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
	 * @param int $user_id User ID.
	 */
	public static function update_streak( $user_id ) {
		$today  = wp_date( 'Y-m-d' );
		$streak = self::get_streak( $user_id );

		if ( isset( $streak['last_date'] ) && $streak['last_date'] === $today ) {
			return;
		}

		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', strtotime( $today ) ) );
		if ( isset( $streak['last_date'] ) && $streak['last_date'] === $yesterday ) {
			$streak['current'] = (int) $streak['current'] + 1;
		} else {
			$streak['current'] = 1;
		}

		$streak['last_date'] = $today;
		$streak['longest']   = max( (int) $streak['longest'], (int) $streak['current'] );
		update_user_meta( (int) $user_id, self::STREAK_META_KEY, $streak );
	}

	/**
	 * @param int    $count     Streak count.
	 * @param string $last_date Last practice date.
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
	 * Candidate lessons: user bookmark list + curriculum due from memorized verses meta.
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	private static function get_candidate_lesson_ids( $user_id ) {
		$ids = array();

		if ( class_exists( 'HWBL_Verse_Memorize' ) ) {
			$list = get_user_meta( $user_id, HWBL_Verse_Memorize::USER_META_KEY, true );
			if ( is_array( $list ) ) {
				$ids = array_merge( $ids, array_map( 'intval', array_keys( $list ) ) );
			}
		}

		if ( class_exists( 'THW_Premium_Progress' ) ) {
			$legacy = THW_Premium_Progress::get_user_progress( $user_id );
			if ( is_array( $legacy ) ) {
				$ids = array_merge( $ids, array_map( 'intval', array_keys( $legacy ) ) );
			}
		}

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		return $ids;
	}

	/**
	 * @param int                  $lesson_id Lesson ID.
	 * @param array<string, mixed> $row       Card row.
	 * @return array<string, mixed>
	 */
	private static function format_queue_item( $lesson_id, $row ) {
		$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		$url    = get_permalink( $lesson_id );
		return array(
			'lesson_id' => (int) $lesson_id,
			'reference' => is_array( $lesson ) && ! empty( $lesson['reference'] ) ? (string) $lesson['reference'] : get_the_title( $lesson_id ),
			'url'       => $url ? (string) $url : '',
			'due_date'  => isset( $row['due_date'] ) ? (string) $row['due_date'] : '',
			'card'      => $row,
		);
	}

	/**
	 * Lesson IDs eligible for review (bookmarks, legacy, SRS map, scheduled verse).
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	private static function get_review_lesson_ids( $user_id ) {
		$ids = self::get_candidate_lesson_ids( $user_id );

		$map = self::get_progress_map( $user_id );
		foreach ( array_keys( $map ) as $lesson_id ) {
			$ids[] = (int) $lesson_id;
		}

		if ( class_exists( 'HWBL_Scheduler' ) ) {
			$current = (int) HWBL_Scheduler::get_current_lesson_id();
			if ( $current > 0 ) {
				$ids[] = $current;
			}
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	/**
	 * One-time import of Premium binary memorized list into SRS cards.
	 *
	 * @param int $user_id User ID.
	 */
	private static function maybe_migrate_legacy_progress( $user_id ) {
		if ( get_user_meta( (int) $user_id, 'hwbl_srs_migrated', true ) ) {
			return;
		}

		if ( ! class_exists( 'THW_Premium_Progress' ) ) {
			return;
		}

		$legacy = THW_Premium_Progress::get_user_progress( (int) $user_id );
		if ( ! is_array( $legacy ) || empty( $legacy ) ) {
			update_user_meta( (int) $user_id, 'hwbl_srs_migrated', 1 );
			return;
		}

		$map = self::get_progress_map( (int) $user_id );
		foreach ( $legacy as $lesson_id => $done ) {
			$lesson_id = (int) $lesson_id;
			if ( $lesson_id < 1 || ! $done || isset( $map[ $lesson_id ] ) ) {
				continue;
			}

			$card = self::default_card( $lesson_id );
			$card['repetitions']   = 1;
			$card['interval_days'] = 21;
			$card['due_date']      = wp_date( 'Y-m-d', strtotime( '+21 days' ) );
			$card['last_review']   = wp_date( 'Y-m-d' );
			$card['last_mode']     = 'legacy';
			$card['last_quality']  = 4;
			$map[ $lesson_id ]     = $card;
		}

		update_user_meta( (int) $user_id, self::PROGRESS_META_KEY, $map );
		update_user_meta( (int) $user_id, 'hwbl_srs_migrated', 1 );
	}

	/**
	 * Mirror premium binary memorized flag when SRS review succeeds.
	 *
	 * @param int $user_id   User ID.
	 * @param int $lesson_id Lesson ID.
	 */
	private static function maybe_migrate_premium_memorized( $user_id, $lesson_id ) {
		if ( ! class_exists( 'THW_Premium_Progress' ) ) {
			return;
		}

		if ( ! THW_Premium_Progress::is_memorized( $user_id, $lesson_id ) ) {
			THW_Premium_Progress::mark_memorized( $user_id, $lesson_id );
		}
	}
}
