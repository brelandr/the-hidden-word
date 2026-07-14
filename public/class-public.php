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
		add_filter( 'template_include', array( $this, 'lesson_archive_template' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register classic widgets.
	 */
	public function register_widgets() {
		register_widget( 'THW_Widget_Verse_Of_Week' );
	}

	/**
	 * Use plugin archive template for lesson catalog.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function lesson_archive_template( $template ) {
		if ( is_post_type_archive( 'thw_lesson' ) ) {
			$plugin_template = THW_PLUGIN_DIR . 'public/templates/archive-thw_lesson.php';
			if ( is_readable( $plugin_template ) ) {
				return $plugin_template;
			}
		}
		return $template;
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

		if ( $this->needs_full_lesson_assets() ) {
			wp_enqueue_style( 'thw-lesson' );
			wp_enqueue_script( 'thw-lesson-tabs' );
			wp_enqueue_script( 'thw-memorization-basic' );
			wp_localize_script(
				'thw-memorization-basic',
				'thwMemorization',
				array(
					'today'        => wp_date( 'Y-m-d' ),
					'streakUpsell' => class_exists( 'THW_Premium' ) ? __( 'Save your streak across devices with Premium progress tracking.', 'the-hidden-word' ) : '',
				)
			);
		} elseif ( $this->needs_verse_widget_assets() ) {
			wp_enqueue_style( 'thw-lesson' );
		} elseif ( is_post_type_archive( 'thw_lesson' ) || $this->has_lesson_list_shortcode_or_block() ) {
			wp_enqueue_style( 'thw-lesson' );
		}
	}

	/**
	 * Whether full lesson JS/CSS should load.
	 *
	 * @return bool
	 */
	private function needs_full_lesson_assets() {
		return is_singular( 'thw_lesson' ) || $this->has_lesson_shortcode_or_block();
	}

	/**
	 * Whether verse-of-week widget/shortcode assets should load.
	 *
	 * @return bool
	 */
	private function needs_verse_widget_assets() {
		if ( $this->has_verse_shortcode_or_block() ) {
			return true;
		}
		return $this->is_verse_widget_active();
	}

	/**
	 * Check if verse widget is active in a sidebar.
	 *
	 * @return bool
	 */
	private function is_verse_widget_active() {
		if ( ! is_active_widget( false, false, 'thw_verse_of_week', true ) ) {
			return false;
		}
		return true;
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
			|| ( function_exists( 'has_block' ) && has_block( 'thw/lesson', $post ) );
	}

	/**
	 * Check if current post contains verse shortcode or block.
	 *
	 * @return bool
	 */
	private function has_verse_shortcode_or_block() {
		global $post;
		if ( ! $post ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'thw_verse_of_week' );
	}

	/**
	 * Check if current post contains lesson list shortcode or block.
	 *
	 * @return bool
	 */
	private function has_lesson_list_shortcode_or_block() {
		global $post;
		if ( ! $post ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'thw_lesson_list' )
			|| ( function_exists( 'has_block' ) && has_block( 'thw/lesson-list', $post ) );
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
