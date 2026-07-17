<?php
/**
 * Front-end assets and single template.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Public
 */
class HWBL_Public {

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
		register_widget( 'HWBL_Widget_Verse_Of_Week' );
	}

	/**
	 * Use plugin archive template for lesson catalog.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function lesson_archive_template( $template ) {
		if ( is_post_type_archive( 'hwbl_lesson' ) ) {
			$plugin_template = HWBL_PLUGIN_DIR . 'public/templates/archive-hwbl_lesson.php';
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
			'hwbl-lesson',
			HWBL_PLUGIN_URL . 'public/css/lesson.css',
			array(),
			HWBL_VERSION
		);

		wp_register_script(
			'hwbl-lesson-tabs',
			HWBL_PLUGIN_URL . 'public/js/lesson-tabs.js',
			array(),
			HWBL_VERSION,
			true
		);

		wp_register_script(
			'hwbl-memorization-basic',
			HWBL_PLUGIN_URL . 'public/js/memorization-basic.js',
			array(),
			HWBL_VERSION,
			true
		);

		wp_register_script(
			'hwbl-memorization-quality',
			HWBL_PLUGIN_URL . 'public/js/memorization-quality.js',
			array(),
			HWBL_VERSION,
			true
		);

		wp_register_script(
			'hwbl-memorization-review',
			HWBL_PLUGIN_URL . 'public/js/memorization-review.js',
			array( 'hwbl-memorization-basic', 'hwbl-memorization-quality' ),
			HWBL_VERSION,
			true
		);

		wp_register_script(
			'hwbl-memorization-audio',
			HWBL_PLUGIN_URL . 'public/js/memorization-audio.js',
			array( 'hwbl-memorization-basic' ),
			HWBL_VERSION,
			true
		);

		if ( $this->needs_full_lesson_assets() ) {
			wp_enqueue_style( 'hwbl-lesson' );
			wp_enqueue_script( 'hwbl-lesson-tabs' );
			wp_enqueue_script( 'hwbl-memorization-basic' );
			wp_enqueue_script( 'hwbl-memorization-quality' );
			wp_enqueue_script( 'hwbl-memorization-review' );
			wp_enqueue_script( 'hwbl-memorization-audio' );
			wp_localize_script(
				'hwbl-lesson-tabs',
				'hwblLessonTabs',
				array(
					'i18n' => HWBL_Frontend_I18n::lesson_tab_strings(),
				)
			);
			wp_localize_script(
				'hwbl-memorization-basic',
				'hwblMemorization',
				HWBL_Frontend_I18n::memorization_config()
			);
		} elseif ( $this->needs_verse_widget_assets() ) {
			wp_enqueue_style( 'hwbl-lesson' );
		} elseif ( is_post_type_archive( 'hwbl_lesson' ) || $this->has_lesson_list_shortcode_or_block() ) {
			wp_enqueue_style( 'hwbl-lesson' );
		}
	}

	/**
	 * Whether full lesson JS/CSS should load.
	 *
	 * @return bool
	 */
	private function needs_full_lesson_assets() {
		return is_singular( 'hwbl_lesson' ) || $this->has_lesson_shortcode_or_block() || $this->has_memorize_reviews_shortcode();
	}

	/**
	 * Whether the review dashboard shortcode is present.
	 *
	 * @return bool
	 */
	private function has_memorize_reviews_shortcode() {
		global $post;
		if ( ! $post instanceof WP_Post ) {
			return false;
		}
		return has_shortcode( $post->post_content, 'hwbl_memorize_reviews' )
			|| has_shortcode( $post->post_content, 'thw_memorize_reviews' );
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
		if ( ! is_active_widget( false, false, 'hwbl_verse_of_week', true ) ) {
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
		return has_shortcode( $post->post_content, 'hwbl_lesson' )
			|| has_shortcode( $post->post_content, 'thw_lesson' )
			|| has_shortcode( $post->post_content, 'hwbl_memorize_verse' )
			|| has_shortcode( $post->post_content, 'thw_memorize_verse' )
			|| ( function_exists( 'has_block' ) && ( has_block( 'hwbl/lesson', $post ) || has_block( 'thw/lesson', $post ) ) );
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
		return has_shortcode( $post->post_content, 'hwbl_verse_of_week' )
			|| has_shortcode( $post->post_content, 'thw_verse_of_week' );
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
		return has_shortcode( $post->post_content, 'hwbl_lesson_list' )
			|| has_shortcode( $post->post_content, 'thw_lesson_list' )
			|| ( function_exists( 'has_block' ) && ( has_block( 'hwbl/lesson-list', $post ) || has_block( 'thw/lesson-list', $post ) ) );
	}

	/**
	 * Append lesson renderer to single lesson posts.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append_lesson_to_single( $content ) {
		if ( ! is_singular( array( 'hwbl_lesson', 'thw_lesson' ) ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$lesson_html = HWBL_Lesson_Renderer::render(
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
