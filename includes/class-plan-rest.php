<?php
/**
 * Reading plan REST API (hwbl/v1/plans).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Plan_Rest
 */
class HWBL_Plan_Rest {

	/**
	 * Initialize routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$logged_in = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};

		register_rest_route(
			'hwbl/v1',
			'/plans',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_list' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'topic' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/plans/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get' ),
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

		register_rest_route(
			'hwbl/v1',
			'/plans/(?P<id>\d+)/start',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_start' ),
				'permission_callback' => $logged_in,
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/plans/(?P<id>\d+)/advance',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_advance' ),
				'permission_callback' => $logged_in,
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/plans/(?P<id>\d+)/days/(?P<day>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_day' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'  => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'day' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * List plans.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_list( $request ) {
		$topic = (string) $request->get_param( 'topic' );
		$args  = array(
			'post_type'      => HWBL_CPT_Plan::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		if ( $topic ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => HWBL_CPT_Plan::META_TOPIC,
					'value' => $topic,
				),
			);
		}
		$posts = get_posts( $args );
		$out   = array();
		foreach ( $posts as $post ) {
			$data = HWBL_CPT_Plan::get_plan_data( $post->ID );
			if ( ! $data ) {
				continue;
			}
			unset( $data['content'], $data['days'] );
			$data['day_count'] = (int) $data['length'];
			$out[]             = $data;
		}
		return rest_ensure_response( array( 'plans' => $out ) );
	}

	/**
	 * Plan detail + today's day for current user.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_get( $request ) {
		$plan_id = (int) $request['id'];
		$data    = HWBL_CPT_Plan::get_plan_data( $plan_id );
		if ( ! $data || ( 'publish' !== $data['status'] && ! current_user_can( 'read_post', $plan_id ) ) ) {
			return new WP_Error( 'hwbl_plan_not_found', __( 'Plan not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		unset( $data['status'] );

		$user_id = get_current_user_id();
		if ( $user_id ) {
			$progress         = HWBL_Plan_Progress::get( $user_id, $plan_id );
			$data['progress'] = $progress;
			$data['today']    = $progress['current_day'] > 0
				? HWBL_CPT_Plan::get_day( $plan_id, (int) $progress['current_day'] )
				: null;
		} else {
			$data['progress'] = null;
			$data['today']    = null;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Start a plan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_start( $request ) {
		$result = HWBL_Plan_Progress::start( get_current_user_id(), (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$plan_id = (int) $request['id'];
		return rest_ensure_response(
			array(
				'progress' => $result,
				'today'    => HWBL_CPT_Plan::get_day( $plan_id, (int) $result['current_day'] ),
			)
		);
	}

	/**
	 * Advance a plan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_advance( $request ) {
		$result = HWBL_Plan_Progress::advance( get_current_user_id(), (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$plan_id = (int) $request['id'];
		$today   = null;
		if ( empty( $result['completed'] ) ) {
			$today = HWBL_CPT_Plan::get_day( $plan_id, (int) $result['current_day'] );
		}
		return rest_ensure_response(
			array(
				'progress'  => $result,
				'today'     => $today,
				'completed' => ! empty( $result['completed'] ),
			)
		);
	}

	/**
	 * Enriched single plan day.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_get_day( $request ) {
		$plan_id = (int) $request['id'];
		$day_num = (int) $request['day'];
		$plan    = HWBL_CPT_Plan::get_plan_data( $plan_id );
		if ( ! $plan || ( 'publish' !== $plan['status'] && ! current_user_can( 'read_post', $plan_id ) ) ) {
			return new WP_Error( 'hwbl_plan_not_found', __( 'Plan not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		$day = HWBL_CPT_Plan::get_day( $plan_id, $day_num );
		if ( ! $day ) {
			return new WP_Error( 'hwbl_plan_day_not_found', __( 'Plan day not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		$day['plan_id']     = $plan_id;
		$day['plan_title']  = (string) ( $plan['title'] ?? '' );
		$day['topic']       = (string) ( $plan['topic'] ?? '' );
		$day['plan_length'] = (int) ( $plan['length'] ?? count( (array) ( $plan['days'] ?? array() ) ) );
		$lesson_id          = ! empty( $day['lesson_id'] ) ? (int) $day['lesson_id'] : 0;
		$day['lesson_url']  = $lesson_id > 0 ? (string) get_permalink( $lesson_id ) : '';
		return rest_ensure_response( $day );
	}
}
