<?php
/**
 * Premium memorization features.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Memorization
 */
class THW_Premium_Memorization {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'hwbl_lesson_tabs', array( __CLASS__, 'add_progress_tab' ), 10, 2 );
		add_action( 'hwbl_lesson_render_after_echo', array( __CLASS__, 'render_mark_memorized_button' ) );
		add_action( 'hwbl_lesson_render_panels', array( __CLASS__, 'render_progress_panel' ), 10, 3 );
	}

	/**
	 * Render progress tab panel.
	 *
	 * @param int   $lesson_id Lesson ID.
	 * @param array $lesson    Lesson data.
	 * @param array $args      Render args.
	 */
	public static function render_progress_panel( $lesson_id, $lesson, $args ) {
		unset( $lesson, $args );
		if ( ! is_user_logged_in() ) {
			return;
		}
		$user_id   = get_current_user_id();
		$progress  = THW_Premium_Progress::get_user_progress( $user_id );
		$memorized = THW_Premium_Progress::is_memorized( $user_id, $lesson_id );
		$streak    = THW_Premium_Progress::get_streak( $user_id );
		$badges    = THW_Premium_Progress::get_badges( $user_id );
		?>
		<section
			id="thw-panel-progress-<?php echo esc_attr( $lesson_id ); ?>"
			class="thw-tab-panel"
			role="tabpanel"
			data-panel="progress"
			hidden
		>
			<h3><?php esc_html_e( 'My Progress', 'hidden-word-bible-lessons' ); ?></h3>
			<p><?php echo esc_html( sprintf(
				/* translators: %d: total memorized count */
				_n( 'You have memorized %d lesson.', 'You have memorized %d lessons.', count( $progress ), 'hidden-word-bible-lessons' ),
				count( $progress )
			) ); ?></p>
			<?php if ( (int) $streak['current'] > 0 ) : ?>
				<p class="thw-streak-badge"><?php echo esc_html( sprintf(
					/* translators: %d: streak day count */
					_n( 'Day %d streak — keep going!', 'Day %d streak — keep going!', (int) $streak['current'], 'hidden-word-bible-lessons' ),
					(int) $streak['current']
				) ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $badges ) ) : ?>
				<ul class="thw-badges">
					<?php foreach ( $badges as $badge ) : ?>
						<li><?php echo esc_html( $badge ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $memorized ) : ?>
				<p class="thw-progress-status"><?php esc_html_e( 'You have memorized this lesson.', 'hidden-word-bible-lessons' ); ?></p>
			<?php else : ?>
				<p class="thw-progress-status"><?php esc_html_e( 'You have not yet marked this lesson as memorized.', 'hidden-word-bible-lessons' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'thw/v1',
			'/memorize',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_mark_memorized' ),
				'permission_callback' => static function () {
					return current_user_can( 'read' );
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
	 * Mark lesson as memorized via REST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_mark_memorized( $request ) {
		$params    = $request->get_json_params();
		$lesson_id = isset( $params['lesson_id'] ) ? (int) $params['lesson_id'] : (int) $request->get_param( 'lesson_id' );
		$user_id   = get_current_user_id();

		THW_Premium_Progress::mark_memorized( $user_id, $lesson_id );
		THW_Premium_BuddyPress::record_activity( $user_id, $lesson_id );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'lesson_id' => $lesson_id,
				'progress' => THW_Premium_Progress::get_user_progress( $user_id ),
			)
		);
	}

	/**
	 * Add progress tab for logged-in users.
	 *
	 * @param array $tabs      Tabs.
	 * @param int   $lesson_id Lesson ID.
	 * @return array
	 */
	public static function add_progress_tab( $tabs, $lesson_id ) {
		unset( $lesson_id );
		if ( is_user_logged_in() ) {
			$tabs['progress'] = __( 'My Progress', 'hidden-word-bible-lessons' );
		}
		return $tabs;
	}

	/**
	 * Render mark as memorized button.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public static function render_mark_memorized_button( $lesson_id ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id     = get_current_user_id();
		$memorized   = THW_Premium_Progress::is_memorized( $user_id, $lesson_id );
		$button_text = $memorized
			? __( 'Memorized ✓', 'hidden-word-bible-lessons' )
			: __( 'Mark as Memorized', 'hidden-word-bible-lessons' );
		?>
		<div class="thw-memorized-action">
			<button
				type="button"
				class="<?php echo esc_attr( 'thw-btn thw-mark-memorized' . ( $memorized ? ' is-memorized' : '' ) ); ?>"
				data-lesson-id="<?php echo esc_attr( $lesson_id ); ?>"
			><?php echo esc_html( $button_text ); ?></button>
		</div>
		<?php
	}
}
