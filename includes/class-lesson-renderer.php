<?php
/**
 * Lesson front-end renderer.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Lesson_Renderer
 */
class HWBL_Lesson_Renderer {

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
			'show_context'      => true,
			'show_narrative'    => true,
			'show_echo'         => true,
			'translation'       => '',
			'compact'           => false,
		);
		$args = wp_parse_args( $args, $defaults );

		if ( ! $lesson_id || 'auto' === $lesson_id ) {
			$lesson_id = HWBL_Scheduler::get_current_lesson_id();
			HWBL_Cache::mark_page_uncacheable( 'hwbl_lesson_auto' );
		}

		$lesson_id = (int) $lesson_id;
		if ( ! $lesson_id ) {
			return '<p class="hwbl-notice">' . esc_html__( 'No verse found for the current schedule.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		if ( ! HWBL_CPT_Lesson::is_valid_lesson_data( $lesson ) ) {
			return '<p class="hwbl-notice">' . esc_html__( 'This lesson could not be loaded.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$translation = $args['translation'] ? sanitize_key( (string) $args['translation'] ) : get_option( 'hwbl_active_translation', 'niv' );
		$trans_svc    = HWBL_Translation_Service::instance();
		$verse_text   = '';

		if ( class_exists( 'HWBL_Verse_Memorize' ) ) {
			$verse_text = HWBL_Verse_Memorize::get_stored_verse_text( $lesson_id, $translation );
		}

		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_by_week( $lesson['lesson_number'], $translation );
		}

		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_text(
				$lesson['book_id'],
				$lesson['chapter'],
				$lesson['verse_start'],
				$translation
			);
		}

		$tabs = apply_filters(
			'hwbl_lesson_tabs',
			array(
				'blueprint' => __( 'The Blueprint', 'hidden-word-bible-lessons' ),
				'context'   => __( 'The Context', 'hidden-word-bible-lessons' ),
				'narrative' => __( 'The Narrative', 'hidden-word-bible-lessons' ),
				'echo'      => __( 'The Echo', 'hidden-word-bible-lessons' ),
				'discussion' => __( 'Discussion', 'hidden-word-bible-lessons' ),
			),
			$lesson_id
		);

		if ( ! empty( $args['compact'] ) ) {
			$tabs = array(
				'blueprint' => __( 'The Blueprint', 'hidden-word-bible-lessons' ),
			);
		} else {
			if ( empty( $args['show_context'] ) ) {
				unset( $tabs['context'] );
			}
			if ( empty( $args['show_narrative'] ) ) {
				unset( $tabs['narrative'] );
			}
			if ( empty( $args['show_echo'] ) ) {
				unset( $tabs['echo'] );
			}
			if ( empty( $args['show_discussion'] ) ) {
				unset( $tabs['discussion'] );
			}
		}

		ob_start();
		include HWBL_PLUGIN_DIR . 'public/partials/lesson-tabs.php';
		$html = ob_get_clean();

		return $html;
	}
}
