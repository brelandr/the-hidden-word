<?php
/**
 * Companion safety reports (Ask + Cohort) for App Store Guideline 1.2.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Community_Safety
 */
class HWBL_Community_Safety {

	const CPT           = 'hwbl_safety_report';
	const META_STATUS   = '_hwbl_report_status';
	const META_TYPE     = '_hwbl_report_type';
	const META_REASON   = '_hwbl_report_reason';
	const META_DETAILS  = '_hwbl_report_details';
	const META_REPORTER = '_hwbl_reporter_id';
	const META_TARGET   = '_hwbl_reported_user_id';
	const META_QUESTION = '_hwbl_report_question';
	const META_ANSWER   = '_hwbl_report_answer';

	/**
	 * Allowed report reasons.
	 *
	 * @return string[]
	 */
	public static function allowed_reasons() {
		return array( 'spam', 'harassment', 'hate', 'misinformation', 'other', 'account_deletion' );
	}

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
	}

	/**
	 * Register moderation CPT.
	 */
	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'          => __( 'Safety Reports', 'hidden-word-bible-lessons' ),
					'singular_name' => __( 'Safety Report', 'hidden-word-bible-lessons' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=hwbl_lesson',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
			)
		);
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$ask_args = array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'rest_report_ask' ),
			'permission_callback' => static function () {
				return is_user_logged_in() && current_user_can( 'read' );
			},
		);

		register_rest_route( 'hwbl/v1', '/ask/report', $ask_args );
		register_rest_route( 'thw/v1', '/ask/report', $ask_args );

		register_rest_route(
			'hwbl/v1',
			'/cohort/report',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_report_cohort' ),
				'permission_callback' => static function () {
					return is_user_logged_in() && current_user_can( 'read' );
				},
			)
		);
	}

	/**
	 * Rate-limit report creation.
	 *
	 * @return true|WP_Error
	 */
	public static function check_report_rate_limit() {
		$user_id = get_current_user_id();
		$ip      = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}
		$key   = 'hwbl_rpt_rl_' . md5( (string) $user_id . '|' . $ip );
		$max   = (int) apply_filters( 'hwbl_report_rate_limit', 20 );
		$max   = $max > 0 ? $max : 20;
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return new WP_Error(
				'hwbl_rate_limited',
				__( 'Too many reports submitted. Try again later.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Normalize and validate a reason key.
	 *
	 * @param string $reason Reason.
	 * @return string|WP_Error
	 */
	public static function sanitize_reason( $reason ) {
		$reason = sanitize_key( (string) $reason );
		if ( ! in_array( $reason, self::allowed_reasons(), true ) ) {
			return new WP_Error(
				'hwbl_invalid_reason',
				__( 'Choose a valid report reason.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}
		return $reason;
	}

	/**
	 * Create a safety report post and notify admin.
	 *
	 * @param array<string, mixed> $args Report args.
	 * @return int Report post ID (0 on failure).
	 */
	public static function create_report( $args ) {
		$type     = sanitize_key( (string) ( $args['type'] ?? 'other' ) );
		$reason   = sanitize_key( (string) ( $args['reason'] ?? 'other' ) );
		$details  = sanitize_textarea_field( (string) ( $args['details'] ?? '' ) );
		$reporter = (int) ( $args['reporter_id'] ?? 0 );
		$target   = (int) ( $args['reported_user_id'] ?? 0 );
		$question = sanitize_textarea_field( (string) ( $args['question'] ?? '' ) );
		$answer   = sanitize_textarea_field( (string) ( $args['answer'] ?? '' ) );

		if ( strlen( $question ) > 4000 ) {
			$question = substr( $question, 0, 4000 );
		}
		if ( strlen( $answer ) > 8000 ) {
			$answer = substr( $answer, 0, 8000 );
		}

		$title = sprintf(
			/* translators: 1: report type, 2: reason */
			__( 'Report: %1$s (%2$s)', 'hidden-word-bible-lessons' ),
			$type,
			$reason
		);

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'private',
				'post_title'  => $title,
				'post_author' => $reporter > 0 ? $reporter : get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, self::META_STATUS, 'pending' );
		update_post_meta( $post_id, self::META_TYPE, $type );
		update_post_meta( $post_id, self::META_REASON, $reason );
		update_post_meta( $post_id, self::META_DETAILS, $details );
		update_post_meta( $post_id, self::META_REPORTER, $reporter );
		update_post_meta( $post_id, self::META_TARGET, $target );
		update_post_meta( $post_id, self::META_QUESTION, $question );
		update_post_meta( $post_id, self::META_ANSWER, $answer );

		self::email_admin_new_report( $post_id, $type, $reason );

		return (int) $post_id;
	}

	/**
	 * POST Ask content report.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_report_ask( $request ) {
		$rate = self::check_report_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$params   = $request->get_json_params();
		$params   = is_array( $params ) ? $params : $request->get_params();
		$reason   = self::sanitize_reason( isset( $params['reason'] ) ? (string) $params['reason'] : '' );
		if ( is_wp_error( $reason ) ) {
			return $reason;
		}
		if ( 'account_deletion' === $reason ) {
			return new WP_Error(
				'hwbl_invalid_reason',
				__( 'Choose a valid report reason.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$question = isset( $params['question'] ) ? (string) $params['question'] : '';
		$answer   = isset( $params['answer'] ) ? (string) $params['answer'] : '';
		$details  = isset( $params['details'] ) ? (string) $params['details'] : '';
		if ( '' === trim( $question ) && '' === trim( $answer ) ) {
			return new WP_Error(
				'hwbl_missing_content',
				__( 'Include the question or answer you are reporting.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$report_id = self::create_report(
			array(
				'type'        => 'ask',
				'reporter_id' => get_current_user_id(),
				'reason'      => $reason,
				'details'     => $details,
				'question'    => $question,
				'answer'      => $answer,
			)
		);

		if ( ! $report_id ) {
			return new WP_Error(
				'hwbl_report_failed',
				__( 'Could not save that report. Try again.', 'hidden-word-bible-lessons' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'ok'       => true,
				'reportId' => $report_id,
				'message'  => __( 'Thanks — your report was submitted for review.', 'hidden-word-bible-lessons' ),
			),
			201
		);
	}

	/**
	 * POST Cohort member report.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_report_cohort( $request ) {
		$rate = self::check_report_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : $request->get_params();
		$reason = self::sanitize_reason( isset( $params['reason'] ) ? (string) $params['reason'] : '' );
		if ( is_wp_error( $reason ) ) {
			return $reason;
		}
		if ( 'account_deletion' === $reason ) {
			return new WP_Error(
				'hwbl_invalid_reason',
				__( 'Choose a valid report reason.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$target = isset( $params['user_id'] ) ? (int) $params['user_id'] : 0;
		if ( $target < 1 || ! get_userdata( $target ) ) {
			return new WP_Error(
				'hwbl_invalid_user',
				__( 'That member could not be found.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}
		if ( $target === get_current_user_id() ) {
			return new WP_Error(
				'hwbl_cannot_report_self',
				__( 'You cannot report yourself.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$details   = isset( $params['details'] ) ? (string) $params['details'] : '';
		$report_id = self::create_report(
			array(
				'type'             => 'cohort',
				'reporter_id'      => get_current_user_id(),
				'reported_user_id' => $target,
				'reason'           => $reason,
				'details'          => $details,
			)
		);

		if ( ! $report_id ) {
			return new WP_Error(
				'hwbl_report_failed',
				__( 'Could not save that report. Try again.', 'hidden-word-bible-lessons' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'ok'       => true,
				'reportId' => $report_id,
				'message'  => __( 'Thanks — your report was submitted for review.', 'hidden-word-bible-lessons' ),
			),
			201
		);
	}

	/**
	 * Email admin about a new report.
	 *
	 * @param int    $report_id Report ID.
	 * @param string $type      Type.
	 * @param string $reason    Reason.
	 */
	private static function email_admin_new_report( $report_id, $type, $reason ) {
		$admin = get_option( 'admin_email' );
		if ( ! $admin || ! is_email( $admin ) ) {
			return;
		}
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] New safety report', 'hidden-word-bible-lessons' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$edit = admin_url( 'post.php?post=' . (int) $report_id . '&action=edit' );
		$body = sprintf(
			/* translators: 1: type, 2: reason, 3: edit URL */
			__( "A new Hidden Word safety report was filed.\n\nType: %1\$s\nReason: %2\$s\n\nReview: %3\$s", 'hidden-word-bible-lessons' ),
			$type,
			$reason,
			$edit
		);
		wp_mail( $admin, $subject, $body );
	}

	/**
	 * Admin columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function admin_columns( $columns ) {
		$columns['hwbl_type']   = __( 'Type', 'hidden-word-bible-lessons' );
		$columns['hwbl_reason'] = __( 'Reason', 'hidden-word-bible-lessons' );
		$columns['hwbl_status'] = __( 'Status', 'hidden-word-bible-lessons' );
		return $columns;
	}

	/**
	 * Admin column content.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public static function admin_column_content( $column, $post_id ) {
		if ( 'hwbl_type' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, self::META_TYPE, true ) );
		} elseif ( 'hwbl_reason' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, self::META_REASON, true ) );
		} elseif ( 'hwbl_status' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, self::META_STATUS, true ) );
		}
	}

	/**
	 * Meta box for report details.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'hwbl_safety_report_details',
			__( 'Report details', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_meta_box' ),
			self::CPT,
			'normal',
			'high'
		);
	}

	/**
	 * Render report meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		$type     = (string) get_post_meta( $post->ID, self::META_TYPE, true );
		$reason   = (string) get_post_meta( $post->ID, self::META_REASON, true );
		$status   = (string) get_post_meta( $post->ID, self::META_STATUS, true );
		$details  = (string) get_post_meta( $post->ID, self::META_DETAILS, true );
		$reporter = (int) get_post_meta( $post->ID, self::META_REPORTER, true );
		$target   = (int) get_post_meta( $post->ID, self::META_TARGET, true );
		$question = (string) get_post_meta( $post->ID, self::META_QUESTION, true );
		$answer   = (string) get_post_meta( $post->ID, self::META_ANSWER, true );

		echo '<p><strong>' . esc_html__( 'Status:', 'hidden-word-bible-lessons' ) . '</strong> ' . esc_html( $status ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Type:', 'hidden-word-bible-lessons' ) . '</strong> ' . esc_html( $type ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Reason:', 'hidden-word-bible-lessons' ) . '</strong> ' . esc_html( $reason ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Reporter user ID:', 'hidden-word-bible-lessons' ) . '</strong> ' . esc_html( (string) $reporter ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Reported user ID:', 'hidden-word-bible-lessons' ) . '</strong> ' . esc_html( (string) $target ) . '</p>';
		if ( $details ) {
			echo '<p><strong>' . esc_html__( 'Details:', 'hidden-word-bible-lessons' ) . '</strong><br />' . nl2br( esc_html( $details ) ) . '</p>';
		}
		if ( $question ) {
			echo '<p><strong>' . esc_html__( 'Question:', 'hidden-word-bible-lessons' ) . '</strong><br />' . nl2br( esc_html( $question ) ) . '</p>';
		}
		if ( $answer ) {
			echo '<p><strong>' . esc_html__( 'Answer:', 'hidden-word-bible-lessons' ) . '</strong><br />' . nl2br( esc_html( $answer ) ) . '</p>';
		}
		echo '<p class="description">' . esc_html__( 'Update the post title or use Quick Edit notes as needed. Mark resolved by changing status meta in custom workflows, or trash the report when done.', 'hidden-word-bible-lessons' ) . '</p>';
	}
}
