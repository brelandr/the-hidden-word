<?php
/**
 * Add any accessible Bible verse to the memorization practice flow.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Verse_Memorize
 */
class HWBL_Verse_Memorize {

	const USER_META_KEY = 'hwbl_user_memorize_verses';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register front-end assets (enqueued when shortcode renders).
	 */
	public static function register_assets() {
		wp_register_style(
			'hwbl-verse-memorize',
			HWBL_PLUGIN_URL . 'public/css/verse-memorize.css',
			array( 'hwbl-lesson' ),
			HWBL_VERSION
		);

		wp_register_script(
			'hwbl-verse-memorize',
			HWBL_PLUGIN_URL . 'public/js/verse-memorize.js',
			array( 'hwbl-memorization-basic' ),
			HWBL_VERSION,
			true
		);
	}

	/**
	 * Translations available for picking a verse to memorize.
	 *
	 * @return array<string, string>
	 */
	public static function get_memorize_translations() {
		$translations = HWBL_Translation_Service::instance()->get_supported_translations();

		if ( class_exists( 'HWBL_Bible_Reader' ) && HWBL_Bible_Reader::is_enabled() ) {
			$translations = array_merge( $translations, HWBL_Bible_Reader::get_reader_translations() );
		}

		$accessible = array();
		foreach ( $translations as $slug => $label ) {
			if ( self::is_translation_available( $slug ) ) {
				$accessible[ $slug ] = $label;
			}
		}

		if ( empty( $accessible ) ) {
			$accessible = array(
				'kjv' => __( 'King James Version', 'hidden-word-bible-lessons' ),
				'web' => __( 'World English Bible', 'hidden-word-bible-lessons' ),
			);
		}

		return apply_filters( 'hwbl_memorize_translations', $accessible );
	}

	/**
	 * Whether verse text can be fetched for a translation (cached probe).
	 *
	 * @param string $translation Translation slug.
	 * @return bool
	 */
	public static function is_translation_available( $translation ) {
		$translation = sanitize_key( (string) $translation );
		if ( '' === $translation ) {
			return false;
		}

		if ( class_exists( 'HWBL_Bible_Reader' ) && HWBL_Bible_Reader::is_enabled() ) {
			$reader = HWBL_Bible_Reader::get_reader_translations();
			if ( isset( $reader[ $translation ] ) ) {
				return true;
			}
		}

		$cache_key = 'hwbl_mem_trans_' . md5( $translation );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (bool) $cached;
		}

		$text    = HWBL_Translation_Service::instance()->get_verse_text( 43, 3, 16, $translation );
		$allowed = '' !== trim( (string) $text );
		set_transient( $cache_key, $allowed ? 1 : 0, WEEK_IN_SECONDS );

		return $allowed;
	}

	/**
	 * Parse a reference string into coordinates.
	 *
	 * @param string $reference Reference text.
	 * @return array<string, mixed>|null
	 */
	public static function parse_reference( $reference ) {
		$parsed = HWBL_Books::parse_reference( $reference );
		if ( ! $parsed || empty( $parsed['book_id'] ) || empty( $parsed['chapter'] ) ) {
			return null;
		}

		if ( empty( $parsed['verse'] ) ) {
			return null;
		}

		$verse_end = ! empty( $parsed['verse_end'] ) ? (int) $parsed['verse_end'] : (int) $parsed['verse'];

		return array(
			'book_id'     => (int) $parsed['book_id'],
			'chapter'     => (int) $parsed['chapter'],
			'verse_start' => (int) $parsed['verse'],
			'verse_end'   => $verse_end,
			'reference'   => HWBL_Books::format_reference(
				(int) $parsed['book_id'],
				(int) $parsed['chapter'],
				(int) $parsed['verse'],
				$verse_end
			),
		);
	}

	/**
	 * Build a stable lookup key for a scripture reference.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse_start Verse start.
	 * @param int    $verse_end   Verse end.
	 * @param string $translation Optional translation slug for custom verse posts.
	 * @return string
	 */
	public static function verse_key( $book_id, $chapter, $verse_start, $verse_end = 0, $translation = '' ) {
		if ( ! $verse_end ) {
			$verse_end = $verse_start;
		}

		$key = sprintf( '%d-%d-%d-%d', (int) $book_id, (int) $chapter, (int) $verse_start, (int) $verse_end );
		$translation = sanitize_key( (string) $translation );
		if ( '' !== $translation ) {
			$key .= '-' . $translation;
		}

		return $key;
	}

	/**
	 * Find a curriculum lesson (500-lesson catalog) by reference.
	 *
	 * @param int $book_id     Book ID.
	 * @param int $chapter     Chapter.
	 * @param int $verse_start Verse start.
	 * @param int $verse_end   Verse end.
	 * @return int Post ID or 0.
	 */
	public static function find_curriculum_lesson_by_reference( $book_id, $chapter, $verse_start, $verse_end = 0 ) {
		$lesson_id = self::find_lesson_by_reference( $book_id, $chapter, $verse_start, $verse_end );
		if ( ! $lesson_id ) {
			return 0;
		}

		$lesson_number = (int) HWBL_CPT_Lesson::get_meta_value( $lesson_id, 'lesson_number' );
		return $lesson_number > 0 ? $lesson_id : 0;
	}

	/**
	 * Find a user-added custom lesson for a reference and translation.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse_start Verse start.
	 * @param int    $verse_end   Verse end.
	 * @param string $translation Translation slug.
	 * @return int Post ID or 0.
	 */
	public static function find_custom_lesson_by_reference( $book_id, $chapter, $verse_start, $verse_end, $translation ) {
		$book_id     = (int) $book_id;
		$chapter     = (int) $chapter;
		$verse_start = (int) $verse_start;
		$verse_end   = $verse_end ? (int) $verse_end : $verse_start;
		$translation = sanitize_key( (string) $translation );

		if ( $book_id < 1 || $chapter < 1 || $verse_start < 1 || '' === $translation ) {
			return 0;
		}

		$key = self::verse_key( $book_id, $chapter, $verse_start, $verse_end, $translation );
		$query = new WP_Query(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_hwbl_verse_key',
						'value' => $key,
					),
					array(
						'key'   => '_hwbl_is_custom_verse',
						'value' => '1',
					),
				),
			)
		);

		if ( ! empty( $query->posts[0] ) ) {
			return (int) $query->posts[0];
		}

		return self::adopt_legacy_custom_lesson( $book_id, $chapter, $verse_start, $verse_end, $translation );
	}

	/**
	 * Upgrade a legacy custom post (reference-only key) to a translation-specific post.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse_start Verse start.
	 * @param int    $verse_end   Verse end.
	 * @param string $translation Translation slug.
	 * @return int Post ID or 0.
	 */
	private static function adopt_legacy_custom_lesson( $book_id, $chapter, $verse_start, $verse_end, $translation ) {
		$legacy_key = self::verse_key( $book_id, $chapter, $verse_start, $verse_end );
		$query      = new WP_Query(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_hwbl_verse_key',
						'value' => $legacy_key,
					),
					array(
						'key'   => '_hwbl_is_custom_verse',
						'value' => '1',
					),
				),
			)
		);

		if ( empty( $query->posts[0] ) ) {
			return 0;
		}

		$lesson_id = (int) $query->posts[0];
		$stored    = sanitize_key( (string) HWBL_CPT_Lesson::get_meta_value( $lesson_id, 'translation' ) );
		if ( '' !== $stored && $stored !== $translation ) {
			return 0;
		}

		update_post_meta( $lesson_id, '_hwbl_translation', $translation );
		update_post_meta( $lesson_id, '_hwbl_verse_key', self::verse_key( $book_id, $chapter, $verse_start, $verse_end, $translation ) );

		return $lesson_id;
	}

	/**
	 * Find an existing lesson post for a scripture reference.
	 *
	 * @param int $book_id     Book ID.
	 * @param int $chapter     Chapter.
	 * @param int $verse_start Verse start.
	 * @param int $verse_end   Verse end.
	 * @return int Post ID or 0.
	 */
	public static function find_lesson_by_reference( $book_id, $chapter, $verse_start, $verse_end = 0 ) {
		$book_id     = (int) $book_id;
		$chapter     = (int) $chapter;
		$verse_start = (int) $verse_start;
		$verse_end   = $verse_end ? (int) $verse_end : $verse_start;

		if ( $book_id < 1 || $chapter < 1 || $verse_start < 1 ) {
			return 0;
		}

		$key = self::verse_key( $book_id, $chapter, $verse_start, $verse_end );
		$query = new WP_Query(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_hwbl_verse_key',
						'value' => $key,
					),
				),
			)
		);

		if ( ! empty( $query->posts[0] ) ) {
			return (int) $query->posts[0];
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_hwbl_book_id',
						'value' => $book_id,
					),
					array(
						'key'   => '_hwbl_chapter',
						'value' => $chapter,
					),
					array(
						'key'   => '_hwbl_verse_start',
						'value' => $verse_start,
					),
				),
			)
		);

		if ( empty( $query->posts[0] ) ) {
			return 0;
		}

		$lesson_id = (int) $query->posts[0];
		$stored_end = (int) HWBL_CPT_Lesson::get_meta_value( $lesson_id, 'verse_end' );
		if ( ! $stored_end ) {
			$stored_end = (int) HWBL_CPT_Lesson::get_meta_value( $lesson_id, 'verse_start' );
		}

		if ( $stored_end && $stored_end !== $verse_end ) {
			return 0;
		}

		update_post_meta( $lesson_id, '_hwbl_verse_key', $key );

		return $lesson_id;
	}

	/**
	 * Fetch verse text for coordinates, or empty string when unavailable.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse_start Verse start.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public static function get_verse_text( $book_id, $chapter, $verse_start, $translation ) {
		return HWBL_Translation_Service::instance()->get_verse_text(
			(int) $book_id,
			(int) $chapter,
			(int) $verse_start,
			sanitize_key( (string) $translation )
		);
	}

	/**
	 * Find or create a lesson post for memorization practice.
	 *
	 * @param array<string, mixed> $parsed      Parsed reference.
	 * @param string               $translation Translation slug.
	 * @return array{lesson_id: int, in_curriculum: bool, verse_text: string}|WP_Error
	 */
	public static function ensure_lesson( $parsed, $translation ) {
		$book_id     = (int) $parsed['book_id'];
		$chapter     = (int) $parsed['chapter'];
		$verse_start = (int) $parsed['verse_start'];
		$verse_end   = (int) $parsed['verse_end'];
		$reference   = (string) $parsed['reference'];

		$translation = sanitize_key( (string) $translation );
		$verse_text  = self::get_verse_text( $book_id, $chapter, $verse_start, $translation );
		if ( '' === trim( $verse_text ) ) {
			return new WP_Error(
				'verse_unavailable',
				__( 'That verse is not available in the selected translation. Try another translation you have access to.', 'hidden-word-bible-lessons' )
			);
		}

		$lesson_id     = self::find_curriculum_lesson_by_reference( $book_id, $chapter, $verse_start, $verse_end );
		$in_curriculum = $lesson_id > 0;

		if ( ! $lesson_id ) {
			$lesson_id = self::find_custom_lesson_by_reference( $book_id, $chapter, $verse_start, $verse_end, $translation );
		}

		if ( ! $lesson_id ) {
			if ( ! is_user_logged_in() ) {
				return new WP_Error(
					'login_required',
					__( 'Sign in to memorize verses that are not already in the lesson catalog.', 'hidden-word-bible-lessons' )
				);
			}

			$lesson_id = self::create_custom_lesson( $parsed, $translation, $verse_text );
			if ( ! $lesson_id ) {
				return new WP_Error(
					'lesson_create_failed',
					__( 'Could not prepare this verse for memorization.', 'hidden-word-bible-lessons' )
				);
			}
		} elseif ( ! $in_curriculum ) {
			self::maybe_store_verse_snapshot( $lesson_id, $translation, $verse_text );
		}

		return array(
			'lesson_id'     => $lesson_id,
			'in_curriculum' => $in_curriculum,
			'verse_text'    => $verse_text,
			'reference'     => $reference,
			'translation'   => $translation,
		);
	}

	/**
	 * Persist verse text on a custom lesson post for reuse.
	 *
	 * @param int    $lesson_id   Lesson post ID.
	 * @param string $translation Translation slug.
	 * @param string $verse_text  Verse text.
	 * @return void
	 */
	public static function maybe_store_verse_snapshot( $lesson_id, $translation, $verse_text ) {
		$lesson_id = (int) $lesson_id;
		if ( $lesson_id < 1 || '' === trim( (string) $verse_text ) ) {
			return;
		}

		if ( ! (int) HWBL_CPT_Lesson::get_meta_value( $lesson_id, 'is_custom_verse' ) ) {
			return;
		}

		update_post_meta( $lesson_id, '_hwbl_translation', sanitize_key( (string) $translation ) );
		update_post_meta( $lesson_id, '_hwbl_verse_text_snapshot', (string) $verse_text );
	}

	/**
	 * Read a stored custom-verse snapshot when translation matches.
	 *
	 * @param int    $lesson_id   Lesson post ID.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public static function get_stored_verse_text( $lesson_id, $translation ) {
		$lesson_id = (int) $lesson_id;
		if ( $lesson_id < 1 ) {
			return '';
		}

		if ( ! (int) HWBL_CPT_Lesson::get_meta_value( $lesson_id, 'is_custom_verse' ) ) {
			return '';
		}

		$stored_translation = sanitize_key( (string) HWBL_CPT_Lesson::get_meta_value( $lesson_id, 'translation' ) );
		if ( '' === $stored_translation || $stored_translation !== sanitize_key( (string) $translation ) ) {
			return '';
		}

		return trim( (string) HWBL_CPT_Lesson::get_meta_value( $lesson_id, 'verse_text_snapshot' ) );
	}

	/**
	 * Create a lightweight lesson post for an out-of-curriculum verse and translation.
	 *
	 * @param array<string, mixed> $parsed      Parsed reference.
	 * @param string               $translation Translation slug.
	 * @param string               $verse_text  Verse text snapshot.
	 * @return int Post ID or 0.
	 */
	private static function create_custom_lesson( $parsed, $translation, $verse_text ) {
		if ( ! is_user_logged_in() ) {
			return 0;
		}

		$book_id     = (int) $parsed['book_id'];
		$chapter     = (int) $parsed['chapter'];
		$verse_start = (int) $parsed['verse_start'];
		$verse_end   = (int) $parsed['verse_end'];
		$reference   = (string) $parsed['reference'];
		$translation = sanitize_key( (string) $translation );
		$key         = self::verse_key( $book_id, $chapter, $verse_start, $verse_end, $translation );

		$existing = self::find_custom_lesson_by_reference( $book_id, $chapter, $verse_start, $verse_end, $translation );
		if ( $existing ) {
			self::maybe_store_verse_snapshot( $existing, $translation, $verse_text );
			return $existing;
		}

		$label = $reference;
		if ( class_exists( 'HWBL_Translation_Service' ) ) {
			$labels = HWBL_Translation_Service::instance()->get_supported_translations();
			if ( isset( $labels[ $translation ] ) ) {
				$label = $reference . ' (' . $labels[ $translation ] . ')';
			}
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'hwbl_lesson',
				'post_title'   => $label,
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, '_hwbl_book_id', $book_id );
		update_post_meta( $post_id, '_hwbl_chapter', $chapter );
		update_post_meta( $post_id, '_hwbl_verse_start', $verse_start );
		update_post_meta( $post_id, '_hwbl_verse_end', $verse_end );
		update_post_meta( $post_id, '_hwbl_reference', $reference );
		update_post_meta( $post_id, '_hwbl_verse_key', $key );
		update_post_meta( $post_id, '_hwbl_is_custom_verse', 1 );
		self::maybe_store_verse_snapshot( (int) $post_id, $translation, $verse_text );

		return (int) $post_id;
	}

	/**
	 * Track a verse in the current user's memorization list.
	 *
	 * @param int    $user_id     User ID.
	 * @param int    $lesson_id   Lesson post ID.
	 * @param string $translation Translation slug.
	 * @return void
	 */
	public static function add_to_user_list( $user_id, $lesson_id, $translation ) {
		$user_id   = (int) $user_id;
		$lesson_id = (int) $lesson_id;
		if ( $user_id < 1 || $lesson_id < 1 ) {
			return;
		}

		$list = get_user_meta( $user_id, self::USER_META_KEY, true );
		if ( ! is_array( $list ) ) {
			$list = array();
		}

		$list[ (string) $lesson_id ] = array(
			'added'       => time(),
			'translation' => sanitize_key( (string) $translation ),
		);

		update_user_meta( $user_id, self::USER_META_KEY, $list );
	}

	/**
	 * Get the current user's saved memorization verses.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_user_list( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id < 1 ) {
			return array();
		}

		$list = get_user_meta( (int) $user_id, self::USER_META_KEY, true );
		if ( ! is_array( $list ) ) {
			return array();
		}

		$items = array();
		foreach ( $list as $lesson_id => $row ) {
			$lesson_id = (int) $lesson_id;
			$lesson    = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
			if ( ! HWBL_CPT_Lesson::is_valid_lesson_data( $lesson ) ) {
				continue;
			}

			$items[] = array(
				'lesson_id'   => $lesson_id,
				'reference'   => $lesson['reference'],
				'added'       => isset( $row['added'] ) ? (int) $row['added'] : 0,
				'translation' => isset( $row['translation'] ) ? sanitize_key( (string) $row['translation'] ) : '',
				'permalink'   => get_permalink( $lesson_id ),
			);
		}

		usort(
			$items,
			static function ( $a, $b ) {
				return (int) $b['added'] <=> (int) $a['added'];
			}
		);

		return $items;
	}

	/**
	 * Register REST routes.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'hwbl/v1',
			'/memorize-verse',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_add_verse' ),
				'permission_callback' => HWBL_Rest_Rate_Limit::public_permission( 'memorize_verse', 10, 60 ),
				'args'                => array(
					'reference'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
					'book_id'     => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
					'chapter'     => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
					'verse'       => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
					'translation' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/memorize-verse/translations',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_translations' ),
				'permission_callback' => HWBL_Rest_Rate_Limit::public_permission( 'memorize_translations', 30, 60 ),
			)
		);
	}

	/**
	 * REST: list memorize translations.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_translations() {
		return new WP_REST_Response(
			array(
				'translations' => self::get_memorize_translations(),
			)
		);
	}

	/**
	 * REST: add a verse to memorization practice.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_add_verse( $request ) {
		$parsed = self::resolve_request_reference( $request );
		if ( is_wp_error( $parsed ) ) {
			return new WP_REST_Response(
				array(
					'error'   => $parsed->get_error_code(),
					'message' => $parsed->get_error_message(),
				),
				400
			);
		}

		$translations = self::get_memorize_translations();
		$translation  = sanitize_key( (string) $request['translation'] );
		if ( ! $translation || ! isset( $translations[ $translation ] ) ) {
			$translation = sanitize_key( (string) get_option( 'hwbl_active_translation', 'kjv' ) );
		}
		if ( ! isset( $translations[ $translation ] ) ) {
			$keys        = array_keys( $translations );
			$translation = $keys[0];
		}

		$result = self::ensure_lesson( $parsed, $translation );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'error'   => $result->get_error_code(),
					'message' => $result->get_error_message(),
				),
				404
			);
		}

		$user_id = get_current_user_id();
		if ( $user_id ) {
			self::add_to_user_list( $user_id, (int) $result['lesson_id'], $translation );
		}

		HWBL_Cache::mark_page_uncacheable( 'hwbl_memorize_verse' );

		$html = HWBL_Lesson_Renderer::render(
			(int) $result['lesson_id'],
			array(
				'show_memorization' => true,
				'show_discussion'   => false,
				'show_context'      => false,
				'show_narrative'    => false,
				'show_echo'         => false,
				'translation'       => $translation,
				'compact'           => true,
			)
		);

		return new WP_REST_Response(
			array(
				'lesson_id'     => (int) $result['lesson_id'],
				'reference'     => (string) $result['reference'],
				'verse_text'    => (string) $result['verse_text'],
				'in_curriculum' => (bool) $result['in_curriculum'],
				'translation'   => $translation,
				'permalink'     => get_permalink( (int) $result['lesson_id'] ),
				'html'          => $html,
			)
		);
	}

	/**
	 * Resolve coordinates from REST request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function resolve_request_reference( $request ) {
		$reference = trim( (string) $request['reference'] );
		if ( $reference ) {
			$parsed = self::parse_reference( $reference );
			if ( ! $parsed ) {
				return new WP_Error(
					'invalid_reference',
					__( 'Enter a verse reference like John 3:16.', 'hidden-word-bible-lessons' )
				);
			}
			return $parsed;
		}

		$book_id = (int) $request['book_id'];
		$chapter = (int) $request['chapter'];
		$verse   = (int) $request['verse'];
		if ( $book_id < 1 || $chapter < 1 || $verse < 1 ) {
			return new WP_Error(
				'invalid_reference',
				__( 'Enter a verse reference like John 3:16.', 'hidden-word-bible-lessons' )
			);
		}

		return array(
			'book_id'     => $book_id,
			'chapter'     => $chapter,
			'verse_start' => $verse,
			'verse_end'   => $verse,
			'reference'   => HWBL_Books::format_reference( $book_id, $chapter, $verse, $verse ),
		);
	}

	/**
	 * Render the add-verse shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'reference'   => '',
				'translation' => '',
				'show_list'   => 'true',
			),
			$atts,
			'hwbl_memorize_verse'
		);

		$translations = self::get_memorize_translations();
		if ( empty( $translations ) ) {
			return '<p class="hwbl-notice">' . esc_html__( 'No Bible translations are available. Enable Hello AO or configure Premium Bible API keys.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$translation = sanitize_key( (string) $atts['translation'] );
		if ( ! $translation || ! isset( $translations[ $translation ] ) ) {
			$translation = sanitize_key( (string) get_option( 'hwbl_active_translation', 'kjv' ) );
		}
		if ( ! isset( $translations[ $translation ] ) ) {
			$keys        = array_keys( $translations );
			$translation = $keys[0];
		}

		wp_enqueue_style( 'hwbl-verse-memorize' );
		wp_enqueue_style( 'hwbl-lesson' );
		wp_enqueue_script( 'hwbl-lesson-tabs' );
		wp_enqueue_script( 'hwbl-memorization-basic' );
		wp_enqueue_script( 'hwbl-verse-memorize' );
		wp_localize_script(
			'hwbl-memorization-basic',
			'hwblMemorization',
			HWBL_Frontend_I18n::memorization_config()
		);
		wp_localize_script(
			'hwbl-verse-memorize',
			'hwblVerseMemorize',
			array(
				'restUrl'      => rest_url( 'hwbl/v1/' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'translation'  => $translation,
				'reference'    => sanitize_text_field( (string) $atts['reference'] ),
				'translations' => $translations,
				'i18n'         => array(
					'reference'       => __( 'Verse reference', 'hidden-word-bible-lessons' ),
					'referencePh'     => __( 'e.g. John 3:16', 'hidden-word-bible-lessons' ),
					'translation'     => __( 'Translation', 'hidden-word-bible-lessons' ),
					'submit'          => __( 'Start memorizing', 'hidden-word-bible-lessons' ),
					'loading'         => __( 'Loading verse…', 'hidden-word-bible-lessons' ),
					'invalid'         => __( 'Enter a verse reference like John 3:16.', 'hidden-word-bible-lessons' ),
					'unavailable'     => __( 'That verse is not available in the selected translation.', 'hidden-word-bible-lessons' ),
					'error'           => __( 'Could not load that verse.', 'hidden-word-bible-lessons' ),
					'yourVerses'      => __( 'Your verses to memorize', 'hidden-word-bible-lessons' ),
					'openLesson'      => __( 'Open full lesson', 'hidden-word-bible-lessons' ),
					'inCurriculum'    => __( 'This verse is already in the lesson catalog.', 'hidden-word-bible-lessons' ),
				),
			)
		);

		HWBL_Cache::mark_page_uncacheable( 'hwbl_memorize_verse_shortcode' );

		$show_list = filter_var( $atts['show_list'], FILTER_VALIDATE_BOOLEAN );
		$user_list = $show_list ? self::get_user_list() : array();

		ob_start();
		?>
		<div class="hwbl-verse-memorize" data-translation="<?php echo esc_attr( $translation ); ?>">
			<form class="hwbl-verse-memorize__form" action="#" method="post">
				<label class="hwbl-verse-memorize__field">
					<span class="hwbl-verse-memorize__label"><?php esc_html_e( 'Verse reference', 'hidden-word-bible-lessons' ); ?></span>
					<input
						type="text"
						class="hwbl-verse-memorize__reference"
						value="<?php echo esc_attr( sanitize_text_field( (string) $atts['reference'] ) ); ?>"
						placeholder="<?php esc_attr_e( 'e.g. John 3:16', 'hidden-word-bible-lessons' ); ?>"
						autocomplete="off"
						required
					/>
				</label>
				<label class="hwbl-verse-memorize__field">
					<span class="hwbl-verse-memorize__label"><?php esc_html_e( 'Translation', 'hidden-word-bible-lessons' ); ?></span>
					<select class="hwbl-verse-memorize__translation">
						<?php foreach ( $translations as $slug => $label ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $translation ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button type="submit" class="hwbl-btn hwbl-verse-memorize__submit"><?php esc_html_e( 'Start memorizing', 'hidden-word-bible-lessons' ); ?></button>
			</form>
			<p class="hwbl-verse-memorize__status" role="status" aria-live="polite"></p>
			<div class="hwbl-verse-memorize__result" hidden></div>
			<?php if ( $show_list && $user_list ) : ?>
				<section class="hwbl-verse-memorize__list">
					<h3><?php esc_html_e( 'Your verses to memorize', 'hidden-word-bible-lessons' ); ?></h3>
					<ul>
						<?php foreach ( $user_list as $item ) : ?>
							<li>
								<button type="button" class="hwbl-verse-memorize__list-item" data-reference="<?php echo esc_attr( $item['reference'] ); ?>" data-translation="<?php echo esc_attr( $item['translation'] ); ?>">
									<?php echo esc_html( $item['reference'] ); ?>
								</button>
								<?php if ( ! empty( $item['permalink'] ) ) : ?>
									<a class="hwbl-verse-memorize__list-link" href="<?php echo esc_url( $item['permalink'] ); ?>"><?php esc_html_e( 'Open full lesson', 'hidden-word-bible-lessons' ); ?></a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
