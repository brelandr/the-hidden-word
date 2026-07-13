<?php
/**
 * Bible lesson custom post type.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_CPT_Lesson
 */
class THW_CPT_Lesson {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register thw_lesson post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Bible Lessons', 'the-hidden-word' ),
			'singular_name'      => __( 'Bible Lesson', 'the-hidden-word' ),
			'menu_name'          => __( 'The Hidden Word', 'the-hidden-word' ),
			'add_new'            => __( 'Add Lesson', 'the-hidden-word' ),
			'add_new_item'       => __( 'Add New Lesson', 'the-hidden-word' ),
			'edit_item'          => __( 'Edit Lesson', 'the-hidden-word' ),
			'new_item'           => __( 'New Lesson', 'the-hidden-word' ),
			'view_item'          => __( 'View Lesson', 'the-hidden-word' ),
			'search_items'       => __( 'Search Lessons', 'the-hidden-word' ),
			'not_found'          => __( 'No lessons found', 'the-hidden-word' ),
			'not_found_in_trash' => __( 'No lessons found in trash', 'the-hidden-word' ),
		);

		register_post_type(
			'thw_lesson',
			array(
				'labels'              => $labels,
				'public'              => true,
				'has_archive'         => true,
				'show_in_rest'        => true,
				'rest_base'           => 'thw-lessons',
				'menu_icon'           => 'dashicons-book-alt',
				'supports'            => array( 'title', 'editor', 'comments', 'thumbnail' ),
				'rewrite'             => array( 'slug' => 'bible-lesson' ),
				'capability_type'     => 'post',
				'show_in_menu'        => true,
			)
		);
	}

	/**
	 * Register post meta for REST API.
	 */
	public function register_meta() {
		$meta_fields = array(
			'_thw_book_id'              => 'integer',
			'_thw_chapter'              => 'integer',
			'_thw_verse_start'          => 'integer',
			'_thw_verse_end'            => 'integer',
			'_thw_lesson_number'        => 'integer',
			'_thw_week_number'          => 'integer',
			'_thw_day_number'           => 'integer',
			'_thw_historical_context'   => 'string',
			'_thw_preceding_narrative'  => 'string',
			'_thw_follow_on_verses'      => 'string',
			'_thw_discussion_questions' => 'string',
			'_thw_audio_url'            => 'string',
		);

		foreach ( $meta_fields as $key => $type ) {
			register_post_meta(
				'thw_lesson',
				$key,
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => $type,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Get lesson data as array.
	 *
	 * @param int $lesson_id Post ID.
	 * @return array<string, mixed>
	 */
	public static function get_lesson_data( $lesson_id ) {
		$lesson_id = (int) $lesson_id;
		$post      = get_post( $lesson_id );

		if ( ! $post || 'thw_lesson' !== $post->post_type ) {
			return array();
		}

		$book_id     = (int) get_post_meta( $lesson_id, '_thw_book_id', true );
		$chapter     = (int) get_post_meta( $lesson_id, '_thw_chapter', true );
		$verse_start = (int) get_post_meta( $lesson_id, '_thw_verse_start', true );
		$verse_end   = (int) get_post_meta( $lesson_id, '_thw_verse_end', true );

		if ( ! $verse_end ) {
			$verse_end = $verse_start;
		}

		$follow_on = get_post_meta( $lesson_id, '_thw_follow_on_verses', true );
		$questions = get_post_meta( $lesson_id, '_thw_discussion_questions', true );

		return array(
			'id'                   => $lesson_id,
			'title'                => get_the_title( $lesson_id ),
			'book_id'              => $book_id,
			'chapter'              => $chapter,
			'verse_start'          => $verse_start,
			'verse_end'            => $verse_end,
			'reference'            => THW_Books::format_reference( $book_id, $chapter, $verse_start, $verse_end ),
			'lesson_number'        => (int) get_post_meta( $lesson_id, '_thw_lesson_number', true ) ?: (int) get_post_meta( $lesson_id, '_thw_week_number', true ),
			'week_number'          => (int) get_post_meta( $lesson_id, '_thw_week_number', true ),
			'historical_context'   => get_post_meta( $lesson_id, '_thw_historical_context', true ),
			'preceding_narrative'  => get_post_meta( $lesson_id, '_thw_preceding_narrative', true ),
			'follow_on_verses'     => $follow_on ? json_decode( $follow_on, true ) : array(),
			'discussion_questions' => $questions ? json_decode( $questions, true ) : array(),
			'audio_url'            => get_post_meta( $lesson_id, '_thw_audio_url', true ),
		);
	}
}
