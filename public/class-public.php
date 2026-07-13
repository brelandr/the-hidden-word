<?php
/**
 * Front-end assets and single template.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Public
 */
class THW_Public {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'the_content', array( $this, 'append_lesson_to_single' ) );
	}

	/**
	 * Enqueue front-end assets.
	 */
	public function enqueue_assets() {
		wp_register_style(
			'thw-lesson',
			THW_PLUGIN_URL . 'public/css/lesson.css',
			array(),
			THW_VERSION
		);

		wp_register_script(
			'thw-lesson-tabs',
			THW_PLUGIN_URL . 'public/js/lesson-tabs.js',
			array(),
			THW_VERSION,
			true
		);

		wp_register_script(
			'thw-memorization-basic',
			THW_PLUGIN_URL . 'public/js/memorization-basic.js',
			array(),
			THW_VERSION,
			true
		);

		if ( is_singular( 'thw_lesson' ) || $this->has_lesson_shortcode_or_block() ) {
			wp_enqueue_style( 'thw-lesson' );
			wp_enqueue_script( 'thw-lesson-tabs' );
			wp_enqueue_script( 'thw-memorization-basic' );
		}
	}

	/**
	 * Check if current post contains lesson shortcode or block.
	 *
	 * @return bool
	 */
	private function has_lesson_shortcode_or_block() {
		global $post;
		if ( ! $post ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'thw_lesson' )
			|| has_shortcode( $post->post_content, 'thw_verse_of_week' )
			|| function_exists( 'has_block' ) && has_block( 'thw/lesson', $post );
	}

	/**
	 * Append lesson renderer to single lesson posts.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append_lesson_to_single( $content ) {
		if ( ! is_singular( 'thw_lesson' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$lesson_html = THW_Lesson_Renderer::render(
			get_the_ID(),
			array(
				'show_memorization' => true,
				'show_discussion'   => true,
				'show_comments'     => true,
			)
		);

		return $content . $lesson_html;
	}
}
