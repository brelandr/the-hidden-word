<?php
/**
 * Front-end shortcodes.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Shortcodes
 */
class THW_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'thw_lesson', array( $this, 'render_lesson' ) );
		add_shortcode( 'thw_verse_of_week', array( $this, 'render_verse_of_week' ) );
	}

	/**
	 * Render full lesson shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_lesson( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'                => 'auto',
				'show_memorization' => 'true',
				'show_discussion'   => 'true',
			),
			$atts,
			'thw_lesson'
		);

		$lesson_id = 'auto' === $atts['id'] ? 0 : absint( $atts['id'] );

		return THW_Lesson_Renderer::render(
			$lesson_id,
			array(
				'show_memorization' => filter_var( $atts['show_memorization'], FILTER_VALIDATE_BOOLEAN ),
				'show_discussion'   => filter_var( $atts['show_discussion'], FILTER_VALIDATE_BOOLEAN ),
			)
		);
	}

	/**
	 * Render compact verse-of-week shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_verse_of_week( $atts ) {
		$atts = shortcode_atts(
			array(
				'week' => 0,
			),
			$atts,
			'thw_verse_of_week'
		);

		$lesson_id = 0;
		if ( ! empty( $atts['week'] ) ) {
			$lesson_id = THW_Scheduler::get_lesson_id_by_week( absint( $atts['week'] ) );
		} else {
			$lesson_id = THW_Scheduler::get_current_lesson_id();
		}

		if ( ! $lesson_id ) {
			return '';
		}

		$lesson      = THW_CPT_Lesson::get_lesson_data( $lesson_id );
		$translation = get_option( 'thw_active_translation', 'niv' );
		$trans_svc   = THW_Translation_Service::instance();
		$verse_text  = $trans_svc->get_verse_by_week( $lesson['lesson_number'], $translation );

		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_text( $lesson['book_id'], $lesson['chapter'], $lesson['verse_start'], $translation );
		}

		$html  = '<div class="thw-verse-of-week">';
		$html .= '<p class="thw-verse-reference"><strong>' . esc_html( $lesson['reference'] ) . '</strong></p>';
		$html .= '<blockquote class="thw-verse-text">' . esc_html( $verse_text ) . '</blockquote>';
		$html .= $trans_svc->render_copyright( $translation );
		$html .= '</div>';

		return $html;
	}
}
