<?php
/**
 * Cohort leaderboard shortcode + weekly challenge (Phase 5).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Cohort_Leaderboard
 */
class HWBL_Cohort_Leaderboard {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		if ( ! class_exists( 'THW_Premium_Cohort' ) ) {
			return;
		}

		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_shortcode( 'hwbl_cohort_leaderboard', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue leaderboard script when shortcode is present.
	 */
	public static function enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}
		global $post;
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		if ( ! has_shortcode( $post->post_content, 'hwbl_cohort_leaderboard' ) ) {
			return;
		}
		wp_enqueue_script(
			'hwbl-cohort-leaderboard',
			HWBL_PLUGIN_URL . 'public/js/cohort-leaderboard.js',
			array(),
			HWBL_VERSION,
			true
		);
		wp_localize_script(
			'hwbl-cohort-leaderboard',
			'hwblCohortLeaderboard',
			array(
				'restUrl' => rest_url( 'hwbl/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Register cohort leaderboard REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/cohort/leaderboard',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_leaderboard' ),
				'permission_callback' => static function () {
					return current_user_can( 'read' );
				},
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/cohort/weekly-challenge',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_weekly_challenge' ),
				'permission_callback' => static function () {
					return current_user_can( 'read' );
				},
			)
		);
	}

	/**
	 * GET weekly streak leaderboard for the user's cohort.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_leaderboard() {
		$payload = self::get_leaderboard_payload();
		return new WP_REST_Response( $payload );
	}

	/**
	 * GET current weekly verse challenge for the cohort.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_weekly_challenge() {
		$lesson_id = class_exists( 'HWBL_Scheduler' ) ? (int) HWBL_Scheduler::get_current_lesson_id() : 0;
		$lesson    = $lesson_id ? HWBL_CPT_Lesson::get_lesson_data( $lesson_id ) : null;

		return new WP_REST_Response(
			array(
				'lesson_id' => $lesson_id,
				'reference' => is_array( $lesson ) && ! empty( $lesson['reference'] ) ? (string) $lesson['reference'] : '',
				'url'       => $lesson_id ? get_permalink( $lesson_id ) : '',
				'week'      => wp_date( 'o-\\WW' ),
			)
		);
	}

	/**
	 * Build ranked leaderboard payload including the current member's rank.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_leaderboard_payload() {
		$user_id   = get_current_user_id();
		$cohort_id = (int) get_user_meta( $user_id, THW_Premium_Cohort::MEMBER_META, true );

		if ( $cohort_id < 1 ) {
			return array(
				'leaderboard'  => array(),
				'your_rank'    => null,
				'your_streak'  => 0,
				'member_count' => 0,
				'cohort_id'    => 0,
			);
		}

		$members = THW_Premium_Cohort::get_member_user_ids( $cohort_id );
		if ( ! in_array( $user_id, $members, true ) ) {
			$members[] = $user_id;
		}

		$blocked = class_exists( 'HWBL_Community_Safety' )
			? HWBL_Community_Safety::get_blocked_user_ids( $user_id )
			: array();

		$rows = array();
		foreach ( $members as $member_id ) {
			$member_id = (int) $member_id;
			if ( $member_id !== (int) $user_id && in_array( $member_id, $blocked, true ) ) {
				continue;
			}
			$streak = class_exists( 'HWBL_Memorization_SRS' )
				? HWBL_Memorization_SRS::get_streak( $member_id )
				: array( 'current' => 0 );
			$user   = get_userdata( $member_id );
			$name   = $user ? wp_strip_all_tags( (string) $user->display_name ) : '';
			$rows[] = array(
				'user_id' => $member_id,
				'name'    => $name,
				'streak'  => (int) ( $streak['current'] ?? 0 ),
				'is_you'  => $member_id === (int) $user_id,
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				if ( $a['streak'] === $b['streak'] ) {
					return $a['user_id'] <=> $b['user_id'];
				}
				return $b['streak'] <=> $a['streak'];
			}
		);

		$your_rank   = null;
		$your_streak = 0;
		$rank        = 0;
		foreach ( $rows as &$row ) {
			$rank++;
			$row['rank'] = $rank;
			if ( ! empty( $row['is_you'] ) ) {
				$your_rank   = $rank;
				$your_streak = (int) $row['streak'];
			}
		}
		unset( $row );

		/**
		 * Cap listed rows for clients/shortcodes while keeping your_rank accurate
		 * against the full cohort.
		 */
		$limit = (int) apply_filters( 'hwbl_cohort_leaderboard_limit', 50 );
		if ( $limit > 0 && count( $rows ) > $limit ) {
			$rows = array_slice( $rows, 0, $limit );
		}

		return array(
			'leaderboard'  => array_values( $rows ),
			'your_rank'    => $your_rank,
			'your_streak'  => $your_streak,
			'member_count' => count( $members ),
			'cohort_id'    => $cohort_id,
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_leaderboard_rows() {
		$payload = self::get_leaderboard_payload();
		return isset( $payload['leaderboard'] ) && is_array( $payload['leaderboard'] )
			? $payload['leaderboard']
			: array();
	}

	/**
	 * Render cohort leaderboard shortcode.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p class="hwbl-notice">' . esc_html__( 'Sign in to view your cohort leaderboard.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		ob_start();
		?>
		<div class="hwbl-cohort-leaderboard" data-hwbl-cohort-leaderboard>
			<h2><?php esc_html_e( 'Cohort memorization streaks', 'hidden-word-bible-lessons' ); ?></h2>
			<div class="hwbl-cohort-weekly-challenge" aria-live="polite"></div>
			<div class="hwbl-cohort-leaderboard__you" aria-live="polite"></div>
			<ol class="hwbl-cohort-leaderboard__list"></ol>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
