<?php
/**
 * Bible lesson custom post type.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_CPT_Lesson
 */
class HWBL_CPT_Lesson {

	/**
	 * Post types that store Bible lesson content (current + legacy).
	 *
	 * @var string[]
	 */
	private const LESSON_POST_TYPES = array( 'hwbl_lesson', 'thw_lesson' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register hwbl_lesson post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Bible Lessons', 'hidden-word-bible-lessons' ),
			'singular_name'      => __( 'Bible Lesson', 'hidden-word-bible-lessons' ),
			'menu_name'          => __( 'Hidden Word Bible Lessons', 'hidden-word-bible-lessons' ),
			'add_new'            => __( 'Add Lesson', 'hidden-word-bible-lessons' ),
			'add_new_item'       => __( 'Add New Lesson', 'hidden-word-bible-lessons' ),
			'edit_item'          => __( 'Edit Lesson', 'hidden-word-bible-lessons' ),
			'new_item'           => __( 'New Lesson', 'hidden-word-bible-lessons' ),
			'view_item'          => __( 'View Lesson', 'hidden-word-bible-lessons' ),
			'search_items'       => __( 'Search Lessons', 'hidden-word-bible-lessons' ),
			'not_found'          => __( 'No lessons found', 'hidden-word-bible-lessons' ),
			'not_found_in_trash' => __( 'No lessons found in trash', 'hidden-word-bible-lessons' ),
		);

		register_post_type(
			'hwbl_lesson',
			array(
				'labels'              => $labels,
				'public'              => true,
				'has_archive'         => true,
				'show_in_rest'        => true,
				'rest_base'           => 'hwbl-lessons',
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
			'_hwbl_book_id'              => 'integer',
			'_hwbl_chapter'              => 'integer',
			'_hwbl_verse_start'          => 'integer',
			'_hwbl_verse_end'            => 'integer',
			'_hwbl_lesson_number'        => 'integer',
			'_hwbl_week_number'          => 'integer',
			'_hwbl_day_number'           => 'integer',
			'_hwbl_historical_context'   => 'string',
			'_hwbl_preceding_narrative'  => 'string',
			'_hwbl_follow_on_verses'      => 'string',
			'_hwbl_discussion_questions' => 'string',
			'_hwbl_audio_url'            => 'string',
		);

		foreach ( $meta_fields as $key => $type ) {
			register_post_meta(
				'hwbl_lesson',
				$key,
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => $type,
					'auth_callback' => static function ( $allowed, $meta_key, $post_id ) {
						unset( $allowed, $meta_key );
						return current_user_can( 'edit_post', (int) $post_id );
					},
				)
			);
		}
	}

	/**
	 * Whether a post type stores Bible lesson content.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public static function is_lesson_post_type( $post_type ) {
		return in_array( (string) $post_type, self::LESSON_POST_TYPES, true );
	}

	/**
	 * Whether lesson data returned from get_lesson_data() is usable.
	 *
	 * @param array<string, mixed> $lesson Lesson data array.
	 * @return bool
	 */
	public static function is_valid_lesson_data( $lesson ) {
		return is_array( $lesson ) && ! empty( $lesson['id'] );
	}

	/**
	 * Read lesson meta, falling back to legacy _thw_* keys when needed.
	 *
	 * @param int    $lesson_id Post ID.
	 * @param string $key       Meta suffix without prefix (e.g. book_id).
	 * @return mixed
	 */
	public static function get_meta_value( $lesson_id, $key ) {
		$value = get_post_meta( $lesson_id, '_hwbl_' . $key, true );
		if ( '' === $value || null === $value || false === $value ) {
			$value = get_post_meta( $lesson_id, '_thw_' . $key, true );
		}

		return $value;
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

		if ( ! $post || ! self::is_lesson_post_type( $post->post_type ) ) {
			return array();
		}

		$book_id     = (int) self::get_meta_value( $lesson_id, 'book_id' );
		$chapter     = (int) self::get_meta_value( $lesson_id, 'chapter' );
		$verse_start = (int) self::get_meta_value( $lesson_id, 'verse_start' );
		$verse_end   = (int) self::get_meta_value( $lesson_id, 'verse_end' );

		if ( ! $verse_end ) {
			$verse_end = $verse_start;
		}

		$follow_on = self::get_meta_value( $lesson_id, 'follow_on_verses' );
		$questions = self::get_meta_value( $lesson_id, 'discussion_questions' );

		$follow_on_decoded = $follow_on ? json_decode( $follow_on, true ) : array();
		$questions_decoded = $questions ? json_decode( $questions, true ) : array();

		$lesson_number = (int) self::get_meta_value( $lesson_id, 'lesson_number' );
		if ( ! $lesson_number ) {
			$lesson_number = (int) self::get_meta_value( $lesson_id, 'week_number' );
		}

		$lesson = array(
			'id'                   => $lesson_id,
			'title'                => get_the_title( $lesson_id ),
			'book_id'              => $book_id,
			'chapter'              => $chapter,
			'verse_start'          => $verse_start,
			'verse_end'            => $verse_end,
			'reference'            => HWBL_Books::format_reference( $book_id, $chapter, $verse_start, $verse_end ),
			'lesson_number'        => $lesson_number,
			'week_number'          => (int) self::get_meta_value( $lesson_id, 'week_number' ),
			'historical_context'   => self::get_meta_value( $lesson_id, 'historical_context' ),
			'preceding_narrative'  => self::get_meta_value( $lesson_id, 'preceding_narrative' ),
			'follow_on_verses'     => is_array( $follow_on_decoded ) ? $follow_on_decoded : array(),
			'discussion_questions' => is_array( $questions_decoded ) ? $questions_decoded : array(),
			'audio_url'            => self::get_meta_value( $lesson_id, 'audio_url' ),
		);

		if ( $lesson['lesson_number'] > 0 ) {
			$entry = HWBL_Curriculum::get_entry_by_lesson_number( $lesson['lesson_number'] );
			$lesson = HWBL_Curriculum::fill_lesson_content_from_entry( $lesson, $entry );
		} elseif ( empty( $lesson['follow_on_verses'] ) ) {
			$lesson['follow_on_verses'] = HWBL_Curriculum::default_follow_on_verses(
				$lesson['book_id'],
				$lesson['chapter'],
				$lesson['verse_start'],
				$lesson['verse_end']
			);
		}

		return apply_filters( 'hwbl_lesson_data', $lesson, $lesson_id );
	}
}
