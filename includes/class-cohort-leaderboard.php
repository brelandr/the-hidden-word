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
		add_shortcode( 'thw_cohort_leaderboard', array( __CLASS__, 'render_shortcode' ) );
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
		if ( ! has_shortcode( $post->post_content, 'hwbl_cohort_leaderboard' ) && ! has_shortcode( $post->post_content, 'thw_cohort_leaderboard' ) ) {
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
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/cohort/weekly-challenge',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_weekly_challenge' ),
				'permission_callback' => function () {
					return is_user_logged_in();
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
		return new WP_REST_Response( array( 'leaderboard' => self::get_leaderboard_rows() ) );
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
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_leaderboard_rows() {
		$user_id   = get_current_user_id();
		$rows      = array();
		$cohort_id = (int) get_user_meta( $user_id, THW_Premium_Cohort::MEMBER_META, true );

		if ( $cohort_id < 1 ) {
			return $rows;
		}

		$members = THW_Premium_Cohort::get_member_user_ids( $cohort_id );
		if ( ! in_array( $user_id, $members, true ) ) {
			$members[] = $user_id;
		}

		foreach ( $members as $member_id ) {
			$streak = class_exists( 'HWBL_Memorization_SRS' )
				? HWBL_Memorization_SRS::get_streak( (int) $member_id )
				: array( 'current' => 0 );
			$user   = get_userdata( (int) $member_id );
			$rows[] = array(
				'user_id' => (int) $member_id,
				'name'    => $user ? $user->display_name : '',
				'streak'  => (int) ( $streak['current'] ?? 0 ),
				'is_you'  => (int) $member_id === (int) $user_id,
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				return $b['streak'] <=> $a['streak'];
			}
		);

		return $rows;
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
			<ol class="hwbl-cohort-leaderboard__list"></ol>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
