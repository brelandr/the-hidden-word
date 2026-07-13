<?php
/**
 * Lesson front-end renderer.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Lesson_Renderer
 */
class THW_Lesson_Renderer {

	/**
	 * Render a complete lesson.
	 *
	 * @param int    $lesson_id   Lesson post ID (0 = current schedule).
	 * @param array  $args        Display arguments.
	 * @return string HTML output.
	 */
	public static function render( $lesson_id = 0, $args = array() ) {
		$defaults = array(
			'show_memorization' => true,
			'show_discussion'   => true,
			'show_comments'     => false,
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! $lesson_id || 'auto' === $lesson_id ) {
			$lesson_id = THW_Scheduler::get_current_lesson_id();
		}

		$lesson_id = (int) $lesson_id;
		if ( ! $lesson_id ) {
			return '<p class="thw-notice">' . esc_html__( 'No lesson found for the current schedule.', 'the-hidden-word' ) . '</p>';
		}

		$lesson       = THW_CPT_Lesson::get_lesson_data( $lesson_id );
		$translation  = get_option( 'thw_active_translation', 'niv' );
		$trans_svc    = THW_Translation_Service::instance();
		$verse_text   = $trans_svc->get_verse_by_week( $lesson['week_number'], $translation );

		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_text(
				$lesson['book_id'],
				$lesson['chapter'],
				$lesson['verse_start'],
				$translation
			);
		}

		$tabs = apply_filters(
			'thw_lesson_tabs',
			array(
				'blueprint' => __( 'The Blueprint', 'the-hidden-word' ),
				'context'   => __( 'The Context', 'the-hidden-word' ),
				'narrative' => __( 'The Narrative', 'the-hidden-word' ),
				'echo'      => __( 'The Echo', 'the-hidden-word' ),
				'discussion' => __( 'Discussion', 'the-hidden-word' ),
			),
			$lesson_id
		);

		ob_start();
		include THW_PLUGIN_DIR . 'public/partials/lesson-tabs.php';
		$html = ob_get_clean();

		return $html;
	}
}
