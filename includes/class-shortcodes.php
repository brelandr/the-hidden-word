<?php
/**
 * Front-end shortcodes.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Shortcodes
 */
class HWBL_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'hwbl_lesson', array( $this, 'render_lesson' ) );
		add_shortcode( 'hwbl_verse_of_week', array( $this, 'render_verse_of_week' ) );
		add_shortcode( 'hwbl_lesson_list', array( $this, 'render_lesson_list' ) );
		add_shortcode( 'hwbl_bible_reader', array( $this, 'render_bible_reader' ) );
		add_shortcode( 'hwbl_memorize_verse', array( $this, 'render_memorize_verse' ) );
		// Legacy shortcodes from pre-1.3.0 installs.
		add_shortcode( 'thw_lesson', array( $this, 'render_lesson' ) );
		add_shortcode( 'thw_verse_of_week', array( $this, 'render_verse_of_week' ) );
		add_shortcode( 'thw_lesson_list', array( $this, 'render_lesson_list' ) );
		add_shortcode( 'thw_bible_reader', array( $this, 'render_bible_reader' ) );
		add_shortcode( 'thw_memorize_verse', array( $this, 'render_memorize_verse' ) );
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
			'hwbl_lesson'
		);

		$lesson_id = 'auto' === $atts['id'] ? 0 : absint( $atts['id'] );
		if ( ! $lesson_id ) {
			HWBL_Cache::mark_page_uncacheable( 'hwbl_lesson_shortcode' );
		}

		return HWBL_Lesson_Renderer::render(
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
			'hwbl_verse_of_week'
		);

		$lesson_id = 0;
		if ( ! empty( $atts['week'] ) ) {
			$lesson_id = HWBL_Scheduler::get_lesson_id_by_week( absint( $atts['week'] ) );
		} else {
			$lesson_id = HWBL_Scheduler::get_current_lesson_id();
			HWBL_Cache::mark_page_uncacheable( 'hwbl_verse_of_week' );
		}

		if ( ! $lesson_id ) {
			return '';
		}

		$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		if ( ! HWBL_CPT_Lesson::is_valid_lesson_data( $lesson ) ) {
			return '';
		}

		$translation = get_option( 'hwbl_active_translation', 'niv' );
		$trans_svc   = HWBL_Translation_Service::instance();
		$verse_text  = $trans_svc->get_verse_by_week( $lesson['lesson_number'], $translation );

		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_text( $lesson['book_id'], $lesson['chapter'], $lesson['verse_start'], $translation );
		}

		$html  = '<div class="hwbl-verse-of-week">';
		$html .= '<p class="hwbl-verse-reference"><strong>' . esc_html( $lesson['reference'] ) . '</strong></p>';
		$html .= '<blockquote class="hwbl-verse-text">' . esc_html( $verse_text ) . '</blockquote>';
		$html .= $trans_svc->render_copyright( $translation );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render lesson catalog shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_lesson_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'group'     => 'book',
				'book'      => 0,
				'testament' => '',
				'per_page'  => 50,
				'show'      => 'both',
				'page'      => 0,
			),
			$atts,
			'hwbl_lesson_list'
		);

		return HWBL_Lesson_List::render(
			array(
				'group'     => sanitize_key( $atts['group'] ),
				'book'      => absint( $atts['book'] ),
				'testament' => sanitize_key( $atts['testament'] ),
				'per_page'  => absint( $atts['per_page'] ),
				'show'      => sanitize_key( $atts['show'] ),
				'page'      => absint( $atts['page'] ),
			)
		);
	}

	/**
	 * Render full Bible chapter reader shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_bible_reader( $atts ) {
		return HWBL_Bible_Reader::render_shortcode( $atts );
	}

	/**
	 * Render add-verse-to-memorize shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_memorize_verse( $atts ) {
		return HWBL_Verse_Memorize::render_shortcode( $atts );
	}
}
