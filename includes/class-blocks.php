<?php
/**
 * Gutenberg block registration.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Blocks
 */
class HWBL_Blocks {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register Gutenberg blocks.
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'hwbl-lesson-block',
			HWBL_PLUGIN_URL . 'blocks/lesson-block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			HWBL_VERSION,
			true
		);

		register_block_type(
			'hwbl/lesson',
			array(
				'editor_script'   => 'hwbl-lesson-block',
				'render_callback' => array( $this, 'render_lesson_block' ),
				'attributes'      => array(
					'lessonId'          => array(
						'type'    => 'string',
						'default' => 'auto',
					),
					'showMemorization'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showDiscussion'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);

		wp_register_script(
			'hwbl-lesson-list-block',
			HWBL_PLUGIN_URL . 'blocks/lesson-list-block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			HWBL_VERSION,
			true
		);

		register_block_type(
			'hwbl/lesson-list',
			array(
				'editor_script'   => 'hwbl-lesson-list-block',
				'render_callback' => array( $this, 'render_lesson_list_block' ),
				'attributes'      => array(
					'group'     => array( 'type' => 'string', 'default' => 'book' ),
					'book'      => array( 'type' => 'string', 'default' => '' ),
					'testament' => array( 'type' => 'string', 'default' => '' ),
					'perPage'   => array( 'type' => 'string', 'default' => '50' ),
					'show'      => array( 'type' => 'string', 'default' => 'both' ),
				),
			)
		);

		wp_register_script(
			'hwbl-verse-study-block',
			HWBL_PLUGIN_URL . 'blocks/verse-study-block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			HWBL_VERSION,
			true
		);

		register_block_type(
			'hwbl/verse-study',
			array(
				'editor_script'   => 'hwbl-verse-study-block',
				'render_callback' => array( $this, 'render_verse_study_block' ),
				'attributes'      => array(
					'bookId'      => array( 'type' => 'string', 'default' => '43' ),
					'chapter'     => array( 'type' => 'string', 'default' => '3' ),
					'verse'       => array( 'type' => 'string', 'default' => '16' ),
					'translation' => array( 'type' => 'string', 'default' => '' ),
					'title'       => array(
						'type'    => 'string',
						'default' => 'Verse Study Card',
					),
					'picker'      => array( 'type' => 'boolean', 'default' => true ),
				),
			)
		);

		wp_register_script(
			'hwbl-bible-map-block',
			HWBL_PLUGIN_URL . 'blocks/bible-map-block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			HWBL_VERSION,
			true
		);

		register_block_type(
			'hwbl/bible-map',
			array(
				'editor_script'   => 'hwbl-bible-map-block',
				'render_callback' => array( $this, 'render_bible_map_block' ),
				'attributes'      => array(
					'book'    => array( 'type' => 'string', 'default' => '43' ),
					'chapter' => array( 'type' => 'string', 'default' => '3' ),
					'verse'   => array( 'type' => 'string', 'default' => '16' ),
					'height'  => array( 'type' => 'string', 'default' => '320' ),
					'scope'   => array( 'type' => 'string', 'default' => 'verse' ),
				),
			)
		);

		wp_register_script(
			'hwbl-bible-concordance-block',
			HWBL_PLUGIN_URL . 'blocks/bible-concordance-block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			HWBL_VERSION,
			true
		);

		register_block_type(
			'hwbl/bible-concordance',
			array(
				'editor_script'   => 'hwbl-bible-concordance-block',
				'render_callback' => array( $this, 'render_bible_concordance_block' ),
				'attributes'      => array(
					'translation' => array( 'type' => 'string', 'default' => '' ),
					'q'           => array( 'type' => 'string', 'default' => '' ),
					'title'       => array(
						'type'    => 'string',
						'default' => 'Bible Concordance',
					),
				),
			)
		);

		wp_register_script(
			'hwbl-plan-block',
			HWBL_PLUGIN_URL . 'blocks/plan-block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			HWBL_VERSION,
			true
		);

		register_block_type(
			'hwbl/plan',
			array(
				'editor_script'   => 'hwbl-plan-block',
				'render_callback' => array( $this, 'render_plan_block' ),
				'attributes'      => array(
					'planId' => array( 'type' => 'string', 'default' => '' ),
					'topic'  => array( 'type' => 'string', 'default' => '' ),
				),
			)
		);
	}

	/**
	 * Server-side render callback for lesson block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_lesson_block( $attributes ) {
		$lesson_id = isset( $attributes['lessonId'] ) ? $attributes['lessonId'] : 'auto';
		if ( 'auto' !== $lesson_id ) {
			$lesson_id = absint( $lesson_id );
		} else {
			$lesson_id = 0;
			HWBL_Cache::mark_page_uncacheable( 'hwbl_lesson_block' );
		}

		return HWBL_Lesson_Renderer::render(
			$lesson_id,
			array(
				'show_memorization' => ! empty( $attributes['showMemorization'] ),
				'show_discussion'   => ! empty( $attributes['showDiscussion'] ),
			)
		);
	}

	/**
	 * Server-side render callback for lesson list block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_lesson_list_block( $attributes ) {
		return HWBL_Lesson_List::render(
			array(
				'group'     => isset( $attributes['group'] ) ? $attributes['group'] : 'book',
				'book'      => ! empty( $attributes['book'] ) ? absint( $attributes['book'] ) : 0,
				'testament' => isset( $attributes['testament'] ) ? $attributes['testament'] : '',
				'per_page'  => ! empty( $attributes['perPage'] ) ? absint( $attributes['perPage'] ) : 50,
				'show'      => isset( $attributes['show'] ) ? $attributes['show'] : 'both',
			)
		);
	}

	/**
	 * Server-side render for Verse Study Card block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_verse_study_block( $attributes ) {
		if ( ! class_exists( 'THW_Premium_Bible_Study_Card' ) ) {
			return '<p>' . esc_html__( 'Verse Study Card requires The Hidden Word premium features.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		return THW_Premium_Bible_Study_Card::render_shortcode(
			array(
				'book_id'     => isset( $attributes['bookId'] ) ? $attributes['bookId'] : '43',
				'chapter'     => isset( $attributes['chapter'] ) ? $attributes['chapter'] : '3',
				'verse'       => isset( $attributes['verse'] ) ? $attributes['verse'] : '16',
				'translation' => isset( $attributes['translation'] ) ? $attributes['translation'] : '',
				'title'       => isset( $attributes['title'] ) ? $attributes['title'] : __( 'Verse Study Card', 'hidden-word-bible-lessons' ),
				'picker'      => ! empty( $attributes['picker'] ) ? '1' : '0',
			)
		);
	}

	/**
	 * Server-side render for Bible Map block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_bible_map_block( $attributes ) {
		if ( ! class_exists( 'HWBL_Bible_Places' ) ) {
			return '';
		}

		return HWBL_Bible_Places::render_shortcode(
			array(
				'book'    => isset( $attributes['book'] ) ? $attributes['book'] : '43',
				'chapter' => isset( $attributes['chapter'] ) ? $attributes['chapter'] : '3',
				'verse'   => isset( $attributes['verse'] ) ? $attributes['verse'] : '16',
				'height'  => isset( $attributes['height'] ) ? $attributes['height'] : '320',
				'scope'   => isset( $attributes['scope'] ) ? $attributes['scope'] : 'verse',
			)
		);
	}

	/**
	 * Server-side render for Bible Concordance block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_bible_concordance_block( $attributes ) {
		if ( ! class_exists( 'HWBL_Bible_Concordance' ) ) {
			return '';
		}

		return HWBL_Bible_Concordance::render_shortcode(
			array(
				'translation' => isset( $attributes['translation'] ) ? $attributes['translation'] : '',
				'q'           => isset( $attributes['q'] ) ? $attributes['q'] : '',
				'title'       => isset( $attributes['title'] ) ? $attributes['title'] : __( 'Bible Concordance', 'hidden-word-bible-lessons' ),
			)
		);
	}

	/**
	 * Server-side render for Reading Plan block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_plan_block( $attributes ) {
		$plan_id = ! empty( $attributes['planId'] ) ? absint( $attributes['planId'] ) : 0;
		$topic   = isset( $attributes['topic'] ) ? sanitize_key( (string) $attributes['topic'] ) : '';
		return HWBL_Shortcodes::render_plans_markup( $plan_id, $topic );
	}
}
