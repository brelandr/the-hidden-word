<?php
/**
 * BuddyPress / BuddyBoss community bridge.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_BuddyPress
 */
class THW_Premium_BuddyPress {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		if ( ! self::is_community_active() ) {
			return;
		}

		add_action( 'hwbl_lesson_render_after_echo', array( __CLASS__, 'render_activity_button' ), 15 );
		add_action( 'bp_include', array( __CLASS__, 'register_activity_actions' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Check if BuddyPress or BuddyBoss is active.
	 *
	 * @return bool
	 */
	public static function is_community_active() {
		return function_exists( 'bp_is_active' ) || function_exists( 'buddypress' );
	}

	/**
	 * Register BuddyPress activity actions.
	 */
	public static function register_activity_actions() {
		if ( ! function_exists( 'bp_activity_set_action' ) ) {
			return;
		}

		bp_activity_set_action(
			buddypress()->activity->id,
			'thw_lesson_completed',
			__( 'Completed a Bible lesson', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'format_activity' ),
			__( 'Bible Lessons', 'hidden-word-bible-lessons' )
		);
	}

	/**
	 * Format activity entry.
	 *
	 * @param string $action   Action string.
	 * @param object $activity Activity object.
	 * @return string
	 */
	public static function format_activity( $action, $activity ) {
		unset( $action );
		$lesson_id = bp_activity_get_meta( $activity->id, 'thw_lesson_id', true );
		$title     = $lesson_id ? get_the_title( $lesson_id ) : __( 'a Bible lesson', 'hidden-word-bible-lessons' );

		return sprintf(
			/* translators: 1: user link, 2: lesson title */
			__( '%1$s completed the lesson %2$s', 'hidden-word-bible-lessons' ),
			bp_core_get_userlink( $activity->user_id ),
			'<strong>' . esc_html( $title ) . '</strong>'
		);
	}

	/**
	 * Register REST routes for community actions.
	 */
	public static function register_routes() {
		register_rest_route(
			'thw/v1',
			'/share-activity',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_share_activity' ),
				'permission_callback' => static function () {
					return current_user_can( 'read' ) && function_exists( 'bp_activity_add' );
				},
				'args'                => array(
					'lesson_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Share lesson completion to BuddyPress activity stream.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_share_activity( $request ) {
		$params    = $request->get_json_params();
		$lesson_id = isset( $params['lesson_id'] ) ? (int) $params['lesson_id'] : (int) $request->get_param( 'lesson_id' );

		if ( ! $lesson_id || 'hwbl_lesson' !== get_post_type( $lesson_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid lesson.', 'hidden-word-bible-lessons' ),
				),
				400
			);
		}

		self::record_activity( get_current_user_id(), $lesson_id );

		return new WP_REST_Response(
			array(
				'success'   => true,
				'lesson_id' => $lesson_id,
			)
		);
	}

	/**
	 * Render share to activity button.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public static function render_activity_button( $lesson_id ) {
		if ( ! is_user_logged_in() || ! function_exists( 'bp_activity_add' ) ) {
			return;
		}
		?>
		<p class="thw-share-activity">
			<button type="button" class="thw-btn thw-share-to-activity" data-lesson-id="<?php echo esc_attr( $lesson_id ); ?>">
				<?php esc_html_e( 'Share to Community', 'hidden-word-bible-lessons' ); ?>
			</button>
		</p>
		<?php
	}

	/**
	 * Record lesson completion activity.
	 *
	 * @param int $user_id   User ID.
	 * @param int $lesson_id Lesson ID.
	 */
	public static function record_activity( $user_id, $lesson_id ) {
		if ( ! function_exists( 'bp_activity_add' ) ) {
			return;
		}

		bp_activity_add(
			array(
				'user_id'   => $user_id,
				'action'    => sprintf(
					/* translators: %s: lesson title */
					__( 'Completed the lesson %s', 'hidden-word-bible-lessons' ),
					get_the_title( $lesson_id )
				),
				'component' => buddypress()->activity->id,
				'type'      => 'thw_lesson_completed',
				'item_id'   => $lesson_id,
			)
		);

		if ( function_exists( 'bp_activity_update_meta' ) ) {
			$activities = bp_activity_get(
				array(
					'filter' => array(
						'user_id' => $user_id,
						'action'  => 'thw_lesson_completed',
					),
					'max'    => 1,
				)
			);
			if ( ! empty( $activities['activities'][0] ) ) {
				bp_activity_update_meta( $activities['activities'][0]->id, 'thw_lesson_id', $lesson_id );
			}
		}
	}
}
