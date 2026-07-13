<?php
/**
 * Gutenberg block registration.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Blocks
 */
class THW_Blocks {

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
			'thw-lesson-block',
			THW_PLUGIN_URL . 'blocks/lesson-block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			THW_VERSION,
			true
		);

		register_block_type(
			'thw/lesson',
			array(
				'editor_script'   => 'thw-lesson-block',
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
		}

		return THW_Lesson_Renderer::render(
			$lesson_id,
			array(
				'show_memorization' => ! empty( $attributes['showMemorization'] ),
				'show_discussion'   => ! empty( $attributes['showDiscussion'] ),
			)
		);
	}
}
