<?php
/**
 * Gospel presentation responses (public invitation follow-up).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Gospel_Response
 */
class HWBL_Gospel_Response {

	const POST_TYPE     = 'hwbl_gospel_resp';
	const META_PLAN     = '_hwbl_gospel_plan_id';
	const META_CHOICE   = '_hwbl_gospel_choice';
	const META_EMAIL    = '_hwbl_gospel_email';
	const META_LOCATION = '_hwbl_gospel_location';
	const META_CHURCH   = '_hwbl_gospel_church_id';
	const META_VIEWS    = '_hwbl_gospel_views';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_review_notice' ) );
	}

	/**
	 * Remind admins to review new gospel responses promptly.
	 */
	public static function admin_review_notice() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . self::POST_TYPE !== $screen->id ) {
			return;
		}
		echo '<div class="notice notice-info"><p>';
		echo esc_html__(
			'Review new Gospel Responses promptly. Each submission is stored as a private post and emailed to the site admin (and a matched church contact when applicable). Prefer same-business-day follow-up when contact details are present.',
			'hidden-word-bible-lessons'
		);
		echo '</p></div>';
	}

	/**
	 * Allowed response choices.
	 *
	 * @return string[]
	 */
	public static function allowed_choices() {
		return array( 'follow', 'questions', 'already' );
	}

	/**
	 * CPT (private moderation store).
	 */
	public static function register_post_type() {
		$can_translate = (bool) did_action( 'init' );
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => $can_translate ? __( 'Gospel Responses', 'hidden-word-bible-lessons' ) : 'Gospel Responses',
					'singular_name' => $can_translate ? __( 'Gospel Response', 'hidden-word-bible-lessons' ) : 'Gospel Response',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=hwbl_lesson',
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'supports'        => array( 'title' ),
			)
		);
	}

	/**
	 * REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/gospel/(?P<id>\d+)/respond',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_respond' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Rate limit public responses.
	 *
	 * @return true|WP_Error
	 */
	public static function check_rate_limit() {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}
		$key   = 'hwbl_gsp_rl_' . md5( $ip );
		$max   = (int) apply_filters( 'hwbl_gospel_response_rate_limit', 15 );
		$max   = $max > 0 ? $max : 15;
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return new WP_Error(
				'hwbl_rate_limited',
				__( 'Too many responses submitted. Try again later.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Increment gospel page view counter on the plan.
	 *
	 * @param int $plan_id Plan ID.
	 */
	public static function record_view( $plan_id ) {
		$plan_id = (int) $plan_id;
		if ( $plan_id < 1 ) {
			return;
		}
		$count = (int) get_post_meta( $plan_id, self::META_VIEWS, true );
		update_post_meta( $plan_id, self::META_VIEWS, $count + 1 );
	}

	/**
	 * Find nearby/matching churches via network hub search.
	 *
	 * @param string $location Zip or city query.
	 * @return array<int, array<string, mixed>>
	 */
	public static function match_churches( $location ) {
		$location = trim( (string) $location );
		if ( '' === $location || ! class_exists( 'HWBL_Church_Network' ) ) {
			return array();
		}
		$request = new WP_REST_Request( 'GET' );
		if ( preg_match( '/^\d{5}(-\d{4})?$/', $location ) ) {
			$request->set_param( 'zip', $location );
		} else {
			$request->set_param( 'q', $location );
		}
		$request->set_param( 'radius', 50 );
		$response = HWBL_Church_Network::rest_list_churches( $request );
		$data     = $response instanceof WP_REST_Response ? $response->get_data() : array();
		$list     = isset( $data['churches'] ) && is_array( $data['churches'] ) ? $data['churches'] : array();
		return array_slice( $list, 0, 3 );
	}

	/**
	 * POST respond.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_respond( $request ) {
		$rate = self::check_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$plan_id = (int) $request['id'];
		$plan    = class_exists( 'HWBL_CPT_Plan' ) ? HWBL_CPT_Plan::get_plan_data( $plan_id ) : null;
		if ( ! $plan || empty( $plan['shareable'] ) || 'publish' !== ( $plan['status'] ?? '' ) ) {
			return new WP_Error( 'hwbl_gospel_missing', __( 'Gospel presentation not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		$params   = $request->get_json_params();
		$params   = is_array( $params ) ? $params : $request->get_params();
		$choice   = sanitize_key( (string) ( $params['choice'] ?? '' ) );
		$email    = isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '';
		$location = isset( $params['location'] ) ? sanitize_text_field( (string) $params['location'] ) : '';
		$consent  = ! empty( $params['consent'] ) && (
			true === $params['consent']
			|| 1 === $params['consent']
			|| '1' === $params['consent']
			|| 'true' === $params['consent']
		);

		if ( ! $consent ) {
			return new WP_Error(
				'hwbl_consent_required',
				__( 'Consent is required before submitting a gospel response.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		if ( ! in_array( $choice, self::allowed_choices(), true ) ) {
			return new WP_Error( 'hwbl_bad_choice', __( 'Choose a valid response.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$churches  = self::match_churches( $location );
		$church_id = ! empty( $churches[0]['id'] ) ? (int) $churches[0]['id'] : 0;

		$labels = array(
			'follow'    => __( 'I want to follow Jesus', 'hidden-word-bible-lessons' ),
			'questions' => __( 'I have questions', 'hidden-word-bible-lessons' ),
			'already'   => __( 'I already follow Jesus', 'hidden-word-bible-lessons' ),
		);

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf(
					/* translators: 1: choice label, 2: plan title */
					__( 'Gospel response: %1$s — %2$s', 'hidden-word-bible-lessons' ),
					$labels[ $choice ],
					$plan['title']
				),
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return new WP_Error( 'hwbl_save_failed', __( 'Could not save your response.', 'hidden-word-bible-lessons' ), array( 'status' => 500 ) );
		}

		update_post_meta( $post_id, self::META_PLAN, $plan_id );
		update_post_meta( $post_id, self::META_CHOICE, $choice );
		update_post_meta( $post_id, self::META_EMAIL, $email );
		update_post_meta( $post_id, self::META_LOCATION, $location );
		update_post_meta( $post_id, self::META_CHURCH, $church_id );

		self::notify_admins( $post_id, $plan, $choice, $email, $location, $churches );

		return rest_ensure_response(
			array(
				'ok'       => true,
				'choice'   => $choice,
				'message'  => __( 'Thank you — we are glad you responded.', 'hidden-word-bible-lessons' ),
				'email'    => (bool) $email,
				'churches' => $churches,
			)
		);
	}

	/**
	 * Email site admin (and matched church site if known).
	 *
	 * @param int                  $response_id Response post ID.
	 * @param array<string,mixed>  $plan        Plan data.
	 * @param string               $choice      Choice key.
	 * @param string               $email       Optional visitor email.
	 * @param string               $location    Location string.
	 * @param array<int,array>     $churches    Matched churches.
	 */
	private static function notify_admins( $response_id, $plan, $choice, $email, $location, $churches ) {
		$admin = get_option( 'admin_email' );
		if ( ! $admin ) {
			return;
		}
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] New gospel presentation response', 'hidden-word-bible-lessons' ),
			get_bloginfo( 'name' )
		);
		$body  = __( 'Someone responded on a shared gospel page.', 'hidden-word-bible-lessons' ) . "\n\n";
		$body .= 'Plan: ' . ( $plan['title'] ?? '' ) . ' (#' . (int) ( $plan['id'] ?? 0 ) . ")\n";
		$body .= 'Choice: ' . $choice . "\n";
		$body .= 'Email: ' . ( $email ? $email : '(none)' ) . "\n";
		$body .= 'Location: ' . ( $location ? $location : '(none)' ) . "\n";
		$body .= 'Response ID: ' . (int) $response_id . "\n";
		if ( $churches ) {
			$body .= "\nMatched churches:\n";
			foreach ( $churches as $c ) {
				$body .= '- ' . ( $c['name'] ?? '' ) . ' ' . ( $c['city'] ?? '' ) . ' ' . ( $c['siteUrl'] ?? $c['site_url'] ?? '' ) . "\n";
			}
		}
		wp_mail( $admin, $subject, $body );

		foreach ( $churches as $c ) {
			$cid = isset( $c['id'] ) ? (int) $c['id'] : 0;
			if ( $cid < 1 ) {
				continue;
			}
			$author_id = (int) get_post_field( 'post_author', $cid );
			$church_email = $author_id ? (string) get_the_author_meta( 'user_email', $author_id ) : '';
			if ( $church_email && is_email( $church_email ) && strcasecmp( $church_email, (string) $admin ) !== 0 ) {
				wp_mail( $church_email, $subject, $body );
			}
		}
	}

	/**
	 * Count responses this calendar month.
	 *
	 * @return int
	 */
	public static function count_responses_this_month() {
		$q = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'year'  => (int) wp_date( 'Y' ),
						'month' => (int) wp_date( 'n' ),
					),
				),
			)
		);
		return (int) $q->found_posts;
	}

	/**
	 * Sum gospel page views across shareable plans.
	 *
	 * @return int
	 */
	public static function total_gospel_views() {
		$plans = get_posts(
			array(
				'post_type'      => 'hwbl_plan',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_hwbl_plan_shareable',
						'value' => '1',
					),
				),
			)
		);
		$total = 0;
		foreach ( $plans as $pid ) {
			$total += (int) get_post_meta( (int) $pid, self::META_VIEWS, true );
		}
		return $total;
	}
}
