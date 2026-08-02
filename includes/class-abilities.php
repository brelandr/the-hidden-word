<?php
/**
 * WordPress Abilities API registrations for agentic discovery.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Abilities
 */
class HWBL_Abilities {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		// Legacy / alternate hook names while the API stabilizes.
		add_action( 'abilities_api_categories_init', array( __CLASS__, 'register_category' ) );

		add_action( 'abilities_api_init', array( __CLASS__, 'register' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the Hidden Word ability category (required before abilities).
	 */
	public static function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'hidden-word',
			array(
				'label'       => __( 'Hidden Word Bible Lessons', 'hidden-word-bible-lessons' ),
				'description' => __( 'Bible lesson, Verse of the Day, and memorization abilities.', 'hidden-word-bible-lessons' ),
			)
		);
	}

	/**
	 * Register abilities when the API is available.
	 */
	public static function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::register_one(
			'hwbl/get-votd',
			array(
				'label'               => __( 'Get Verse of the Day', 'hidden-word-bible-lessons' ),
				'description'         => __( 'Returns today’s Verse of the Day reference and text.', 'hidden-word-bible-lessons' ),
				'category'            => 'hidden-word',
				'output_schema'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'reference' => array( 'type' => 'string' ),
						'text'      => array( 'type' => 'string' ),
						'error'     => array( 'type' => 'string' ),
					),
					'additionalProperties' => true,
				),
				'permission_callback' => static function () {
					return true;
				},
				'execute_callback'    => static function () {
					if ( ! class_exists( 'THW_Premium_Verse_Of_The_Day' ) ) {
						return array( 'error' => 'votd_unavailable' );
					}
					if ( ! method_exists( 'THW_Premium_Verse_Of_The_Day', 'get_today_payload' ) ) {
						return array( 'error' => 'votd_unavailable' );
					}
					$payload = THW_Premium_Verse_Of_The_Day::get_today_payload();
					return is_array( $payload ) ? $payload : array( 'error' => 'votd_unavailable' );
				},
			)
		);

		self::register_one(
			'hwbl/lookup-passage',
			array(
				'label'               => __( 'Look up a Bible passage', 'hidden-word-bible-lessons' ),
				'description'         => __( 'Fetches chapter text for a book id and chapter.', 'hidden-word-bible-lessons' ),
				'category'            => 'hidden-word',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'book_id' => array( 'type' => 'integer' ),
						'chapter' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'book_id', 'chapter' ),
				),
				'output_schema'       => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
				'permission_callback' => static function () {
					return true;
				},
				'execute_callback'    => static function ( $input ) {
					$book    = (int) ( is_array( $input ) ? ( $input['book_id'] ?? 0 ) : 0 );
					$chapter = (int) ( is_array( $input ) ? ( $input['chapter'] ?? 0 ) : 0 );
					if ( $book < 1 || $chapter < 1 || ! class_exists( 'HWBL_Bible_Reader' ) ) {
						return array( 'error' => 'invalid_passage' );
					}
					if ( method_exists( 'HWBL_Bible_Reader', 'get_chapter_payload' ) ) {
						return HWBL_Bible_Reader::get_chapter_payload( $book, $chapter, '' );
					}
					return array(
						'book_id' => $book,
						'chapter' => $chapter,
					);
				},
			)
		);

		self::register_one(
			'hwbl/memorization-streak',
			array(
				'label'               => __( 'Check memorization streak', 'hidden-word-bible-lessons' ),
				'description'         => __( 'Returns the current user’s memorization streak and due count.', 'hidden-word-bible-lessons' ),
				'category'            => 'hidden-word',
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'streak' => array( 'type' => 'integer' ),
						'due'    => array( 'type' => 'integer' ),
						'error'  => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => static function () {
					return is_user_logged_in();
				},
				'execute_callback'    => static function () {
					$user_id = get_current_user_id();
					if ( $user_id < 1 || ! class_exists( 'HWBL_Memorization_SRS' ) ) {
						return array( 'error' => 'login_required' );
					}
					$stats = method_exists( 'HWBL_Memorization_SRS', 'get_progress_stats' )
						? HWBL_Memorization_SRS::get_progress_stats( $user_id )
						: array();
					$streak = method_exists( 'HWBL_Memorization_SRS', 'get_streak' )
						? HWBL_Memorization_SRS::get_streak( $user_id )
						: 0;
					return array(
						'streak' => (int) $streak,
						'due'    => is_array( $stats ) ? (int) ( $stats['due'] ?? 0 ) : 0,
					);
				},
			)
		);

		self::register_one(
			'hwbl/find-lessons',
			array(
				'label'               => __( 'Find lessons about a topic', 'hidden-word-bible-lessons' ),
				'description'         => __( 'Searches published Bible lessons by keyword.', 'hidden-word-bible-lessons' ),
				'category'            => 'hidden-word',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'q' => array( 'type' => 'string' ),
					),
					'required'   => array( 'q' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'lessons' => array( 'type' => 'array' ),
					),
				),
				'permission_callback' => static function () {
					return true;
				},
				'execute_callback'    => static function ( $input ) {
					$q = sanitize_text_field( (string) ( is_array( $input ) ? ( $input['q'] ?? '' ) : '' ) );
					if ( ! $q ) {
						return array( 'lessons' => array() );
					}
					$query = new WP_Query(
						array(
							'post_type'      => 'hwbl_lesson',
							'post_status'    => 'publish',
							's'              => $q,
							'posts_per_page' => 10,
						)
					);
					$out = array();
					foreach ( $query->posts as $post ) {
						$out[] = array(
							'id'    => (int) $post->ID,
							'title' => get_the_title( $post ),
							'url'   => get_permalink( $post ),
						);
					}
					return array( 'lessons' => $out );
				},
			)
		);
	}

	/**
	 * Register one ability with soft compatibility for evolving WP args.
	 *
	 * Some builds require permission_callback / output_schema; older docs used
	 * `callback` as an alias for execute_callback.
	 *
	 * @param string               $name Ability name (namespace/slug).
	 * @param array<string, mixed> $args Ability args.
	 */
	private static function register_one( $name, array $args ) {
		// Mirror execute_callback as callback for builds that expect that key.
		if ( isset( $args['execute_callback'] ) && ! isset( $args['callback'] ) ) {
			$args['callback'] = $args['execute_callback'];
		}

		$result = wp_register_ability( $name, $args );
		if ( null !== $result ) {
			return;
		}

		// Retry without optional schema keys if a strict build rejected them.
		$fallback = $args;
		unset( $fallback['output_schema'], $fallback['callback'] );
		wp_register_ability( $name, $fallback );
	}
}
