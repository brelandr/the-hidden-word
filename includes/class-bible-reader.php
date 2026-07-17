<?php
/**
 * Full Bible chapter reader (text + Hello AO audio).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Bible_Reader
 */
class HWBL_Bible_Reader {

	/**
	 * Allowed default audio narrators.
	 *
	 * @var string[]
	 */
	private static $narrators = array( 'david', 'hays', 'souer', 'gilbert' );

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
			'hwbl-bible-reader',
			HWBL_PLUGIN_URL . 'public/css/bible-reader.css',
			array(),
			HWBL_VERSION
		);

		wp_register_script(
			'hwbl-bible-reader',
			HWBL_PLUGIN_URL . 'public/js/bible-reader.js',
			array(),
			HWBL_VERSION,
			true
		);

		wp_register_script(
			'hwbl-bible-reader-research',
			HWBL_PLUGIN_URL . 'public/js/bible-reader-research.js',
			array( 'hwbl-bible-reader' ),
			HWBL_VERSION,
			true
		);
	}

	/**
	 * Whether the Bible reader is enabled and has at least one translation.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( ! (bool) get_option( 'hwbl_bible_reader_enabled', true ) ) {
			return false;
		}

		return ! empty( self::get_reader_translations() );
	}

	/**
	 * Default audio narrator slug.
	 *
	 * @return string
	 */
	public static function get_default_narrator() {
		$narrator = sanitize_key( (string) get_option( 'hwbl_bible_reader_narrator', 'david' ) );
		return in_array( $narrator, self::$narrators, true ) ? $narrator : 'david';
	}

	/**
	 * All translations registered for the reader (before access filtering).
	 *
	 * @return array<string, string>
	 */
	private static function get_registered_reader_translations() {
		$translations = array();

		if ( class_exists( 'HWBL_HelloAO_Provider' ) ) {
			$translations = array_merge( $translations, HWBL_HelloAO_Provider::get_reader_translations() );
		}

		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() ) {
			foreach ( THW_Premium_Biblia::add_translations( array() ) as $slug => $label ) {
				if ( ! isset( $translations[ $slug ] ) ) {
					$translations[ $slug ] = $label;
				}
			}
		}

		if ( class_exists( 'THW_Premium_YouVersion' ) && THW_Premium_YouVersion::is_available() ) {
			foreach ( THW_Premium_YouVersion::add_translations( array() ) as $slug => $label ) {
				if ( ! isset( $translations[ $slug ] ) ) {
					$translations[ $slug ] = $label;
				}
			}
		}

		if ( class_exists( 'THW_Premium_API_Bible' ) && self::premium_api_bible_available() ) {
			foreach ( THW_Premium_API_Bible::add_translations( array() ) as $slug => $label ) {
				if ( ! isset( $translations[ $slug ] ) ) {
					$translations[ $slug ] = $label;
				}
			}
		}

		return $translations;
	}

	/**
	 * Translations that support full-chapter fetch for the reader (access verified).
	 *
	 * @return array<string, string>
	 */
	public static function get_reader_translations() {
		$accessible = array();
		foreach ( self::get_registered_reader_translations() as $slug => $label ) {
			if ( self::is_translation_accessible( $slug ) ) {
				$accessible[ $slug ] = $label;
			}
		}

		return apply_filters( 'hwbl_bible_reader_translations', $accessible );
	}

	/**
	 * Whether a translation can load chapter content with configured providers.
	 *
	 * @param string $translation Translation slug.
	 * @return bool
	 */
	public static function is_translation_accessible( $translation ) {
		$translation = self::sanitize_translation( $translation );
		if ( ! $translation ) {
			return false;
		}

		if ( class_exists( 'HWBL_HelloAO_Provider' ) && HWBL_HelloAO_Provider::is_enabled() && HWBL_HelloAO_Provider::get_helloao_id( $translation ) ) {
			return true;
		}

		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_translation_accessible( $translation ) ) {
			return true;
		}

		if ( class_exists( 'THW_Premium_YouVersion' ) && THW_Premium_YouVersion::is_translation_accessible( $translation ) ) {
			return true;
		}

		if ( class_exists( 'THW_Premium_API_Bible' ) && self::premium_api_bible_available() && THW_Premium_API_Bible::is_translation_accessible( $translation ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Reader feature flags for the current site.
	 *
	 * @return array<string, bool>
	 */
	public static function get_reader_features() {
		$features = array(
			'parse'    => true,
			'search'   => class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available(),
			'research' => true,
		);

		return apply_filters( 'hwbl_bible_reader_features', $features );
	}

	/**
	 * Resolve an accessible reader translation slug.
	 *
	 * @param string $translation Requested translation.
	 * @return string Empty when none available.
	 */
	public static function resolve_translation_for_request( $translation = '' ) {
		return self::resolve_translation( $translation );
	}

	/**
	 * Find a curriculum lesson URL for a verse, if one exists.
	 *
	 * @param int $book_id Book ID.
	 * @param int $chapter Chapter.
	 * @param int $verse   Verse number.
	 * @return array{lesson_id:int,url:string,in_curriculum:bool}
	 */
	public static function find_curriculum_lesson( $book_id, $chapter, $verse ) {
		$result = array(
			'lesson_id'     => 0,
			'url'           => '',
			'in_curriculum' => false,
		);

		if ( $book_id < 1 || $chapter < 1 || $verse < 1 || ! class_exists( 'HWBL_Verse_Memorize' ) ) {
			return $result;
		}

		$lesson_id = HWBL_Verse_Memorize::find_curriculum_lesson_by_reference( $book_id, $chapter, $verse, $verse );
		if ( ! $lesson_id ) {
			return $result;
		}

		$result['lesson_id']     = $lesson_id;
		$result['in_curriculum'] = true;
		$url                     = get_permalink( $lesson_id );
		$result['url']           = $url ? (string) $url : '';

		return $result;
	}

	/**
	 * Parse a reference string into book/chapter/verse coordinates.
	 *
	 * @param string $reference Reference text.
	 * @return array<string, mixed>|null
	 */
	public static function parse_reference( $reference ) {
		$reference = trim( (string) $reference );
		if ( '' === $reference ) {
			return null;
		}

		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() ) {
			$parsed = THW_Premium_Biblia::parse_reader_reference( $reference );
			if ( $parsed ) {
				return $parsed;
			}
		}

		return HWBL_Books::parse_reference( $reference );
	}

	/**
	 * Search Scripture (Biblia.com when configured).
	 *
	 * @param string $translation Translation slug.
	 * @param string $query       Search query.
	 * @param int    $limit       Max results.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search( $translation, $query, $limit = 12 ) {
		$translation = self::sanitize_translation( $translation );
		if ( ! $translation || ! self::is_translation_accessible( $translation ) ) {
			return array();
		}

		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() && THW_Premium_Biblia::is_translation_accessible( $translation ) ) {
			return THW_Premium_Biblia::search_bible( $translation, $query, $limit );
		}

		return array();
	}

	/**
	 * Book list with chapter counts for a translation.
	 *
	 * @param string $translation Translation slug.
	 * @return array<int, array{id:int,name:string,chapters:int,usfm:string}>
	 */
	public static function get_books( $translation = '' ) {
		$translation = self::sanitize_translation( $translation );
		if ( ! $translation ) {
			return array();
		}

		$chapter_map = self::get_chapter_count_map( $translation );
		$books       = array();

		foreach ( HWBL_Books::get_all() as $id => $name ) {
			$book_id = (int) $id;
			$usfm    = HWBL_Books::get_usfm( $book_id );
			$chapters = isset( $chapter_map[ $usfm ] ) ? (int) $chapter_map[ $usfm ] : 0;
			if ( $chapters < 1 ) {
				$chapters = self::fallback_chapter_count( $book_id );
			}

			$books[] = array(
				'id'       => $book_id,
				'name'     => (string) $name,
				'chapters' => $chapters,
				'usfm'     => $usfm,
			);
		}

		return $books;
	}

	/**
	 * Fetch a normalized chapter payload.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter number.
	 * @param string $translation Translation slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_chapter( $book_id, $chapter, $translation = '' ) {
		$book_id     = (int) $book_id;
		$chapter     = (int) $chapter;
		$translation = self::sanitize_translation( $translation );

		if ( $book_id < 1 || $chapter < 1 || ! $translation ) {
			return null;
		}

		$books = self::get_books( $translation );
		$meta  = null;
		foreach ( $books as $book ) {
			if ( (int) $book['id'] === $book_id ) {
				$meta = $book;
				break;
			}
		}

		if ( ! $meta || $chapter > (int) $meta['chapters'] ) {
			return null;
		}

		$payload = null;

		if ( class_exists( 'HWBL_HelloAO_Provider' ) && HWBL_HelloAO_Provider::get_helloao_id( $translation ) ) {
			$payload = HWBL_HelloAO_Provider::get_chapter_payload( $book_id, $chapter, $translation );
		}

		if ( ! HWBL_Http_Utils::is_valid_chapter_payload( $payload ) && class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() ) {
			$payload = THW_Premium_Biblia::get_chapter( $book_id, $chapter, $translation );
		}

		if ( ! HWBL_Http_Utils::is_valid_chapter_payload( $payload ) && class_exists( 'THW_Premium_YouVersion' ) && THW_Premium_YouVersion::is_available() ) {
			$payload = THW_Premium_YouVersion::get_chapter( $book_id, $chapter, $translation );
		}

		if ( ! HWBL_Http_Utils::is_valid_chapter_payload( $payload ) && class_exists( 'THW_Premium_API_Bible' ) && self::premium_api_bible_available() ) {
			$payload = THW_Premium_API_Bible::get_chapter( $book_id, $chapter, $translation );
		}

		if ( ! HWBL_Http_Utils::is_valid_chapter_payload( $payload ) ) {
			return null;
		}

		$reference = HWBL_Books::get_name( $book_id ) . ' ' . $chapter;
		$copyright = '';
		if ( class_exists( 'HWBL_Translation_Service' ) ) {
			$copyright = wp_strip_all_tags( HWBL_Translation_Service::instance()->render_copyright( $translation ) );
		}

		$normalized = array(
			'reference'   => $reference,
			'book_id'     => $book_id,
			'chapter'     => $chapter,
			'translation' => $translation,
			'verses'      => $payload['verses'],
			'headings'    => isset( $payload['headings'] ) ? $payload['headings'] : array(),
			'audio'       => isset( $payload['audio'] ) ? $payload['audio'] : array(),
			'navigation'  => self::build_navigation( $book_id, $chapter, (int) $meta['chapters'], $books ),
			'copyright'   => $copyright,
		);

		return apply_filters( 'hwbl_bible_chapter', $normalized, $book_id, $chapter, $translation );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'hwbl/v1',
			'/bible/books',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_books' ),
				'permission_callback' => '__return_true',
				'args'                => array(
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
			'/bible/chapter',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_chapter' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'book_id'     => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'chapter'     => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
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
			'/bible/parse',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_parse' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'reference' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/research',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_research' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'book_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'chapter' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'verse'   => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'           => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'translation' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
					'limit'       => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 12,
					),
				),
			)
		);
	}

	/**
	 * REST: book catalog.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_books( $request ) {
		if ( ! self::is_enabled() ) {
			return new WP_REST_Response( array( 'error' => 'disabled' ), 403 );
		}

		$translation = self::resolve_translation( $request['translation'] );
		if ( ! $translation ) {
			return new WP_REST_Response( array( 'error' => 'invalid_translation' ), 400 );
		}

		return new WP_REST_Response(
			array(
				'translation' => $translation,
				'books'       => self::get_books( $translation ),
				'narrator'    => self::get_default_narrator(),
			)
		);
	}

	/**
	 * REST: chapter payload.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_chapter( $request ) {
		if ( ! self::is_enabled() ) {
			return new WP_REST_Response( array( 'error' => 'disabled' ), 403 );
		}

		$translation = self::resolve_translation( $request['translation'] );
		if ( ! $translation ) {
			return new WP_REST_Response( array( 'error' => 'invalid_translation' ), 400 );
		}

		$payload = self::get_chapter( (int) $request['book_id'], (int) $request['chapter'], $translation );
		if ( ! $payload ) {
			return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
		}

		$verse = absint( $request['verse'] );
		if ( $verse > 0 ) {
			$payload['highlight_verse'] = $verse;
		}

		return new WP_REST_Response( $payload );
	}

	/**
	 * REST: parse a Bible reference.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_parse( $request ) {
		if ( ! self::is_enabled() ) {
			return new WP_REST_Response( array( 'error' => 'disabled' ), 403 );
		}

		$parsed = self::parse_reference( $request['reference'] );
		if ( ! $parsed ) {
			return new WP_REST_Response( array( 'error' => 'invalid_reference' ), 400 );
		}

		return new WP_REST_Response( $parsed );
	}

	/**
	 * REST: research metadata for a verse (curriculum lesson link, explain availability).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_research( $request ) {
		if ( ! self::is_enabled() ) {
			return new WP_REST_Response( array( 'error' => 'disabled' ), 403 );
		}

		$book_id = max( 1, (int) $request['book_id'] );
		$chapter = max( 1, (int) $request['chapter'] );
		$verse   = max( 0, (int) $request['verse'] );
		$lesson  = $verse > 0 ? self::find_curriculum_lesson( $book_id, $chapter, $verse ) : array(
			'lesson_id'     => 0,
			'url'           => '',
			'in_curriculum' => false,
		);

		$features = self::get_reader_features();

		return new WP_REST_Response(
			array(
				'lesson_id'     => (int) $lesson['lesson_id'],
				'lesson_url'    => (string) $lesson['url'],
				'in_curriculum' => (bool) $lesson['in_curriculum'],
				'explain'       => ! empty( $features['explain'] ),
				'logged_in'     => is_user_logged_in(),
			)
		);
	}

	/**
	 * REST: search Bible text.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_search( $request ) {
		if ( ! self::is_enabled() ) {
			return new WP_REST_Response( array( 'error' => 'disabled' ), 403 );
		}

		$features = self::get_reader_features();
		if ( empty( $features['search'] ) ) {
			return new WP_REST_Response( array( 'error' => 'search_unavailable' ), 403 );
		}

		$translation = self::resolve_translation( $request['translation'] );
		if ( ! $translation ) {
			return new WP_REST_Response( array( 'error' => 'invalid_translation' ), 400 );
		}

		$results = self::search( $translation, $request['q'], (int) $request['limit'] );

		return new WP_REST_Response(
			array(
				'translation' => $translation,
				'query'         => (string) $request['q'],
				'results'       => $results,
			)
		);
	}

	/**
	 * Enqueue reader assets and return shortcode HTML shell.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		if ( ! self::is_enabled() ) {
			return '<p class="hwbl-bible-reader-notice">' . esc_html__( 'The Bible reader is disabled in settings.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'translation' => '',
				'book'        => 0,
				'chapter'     => 1,
				'verse'       => 0,
			),
			$atts,
			'hwbl_bible_reader'
		);

		$translations = self::get_reader_translations();
		if ( empty( $translations ) ) {
			return '<p class="hwbl-bible-reader-notice">' . esc_html__( 'No Bible translations are available for the reader. Enable Hello AO or configure Premium Bible API keys.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$translation = self::sanitize_translation( $atts['translation'] );
		if ( ! $translation || ! isset( $translations[ $translation ] ) ) {
			$translation = self::sanitize_translation( get_option( 'hwbl_active_translation', 'bsb' ) );
		}
		if ( ! $translation || ! isset( $translations[ $translation ] ) ) {
			$keys        = array_keys( $translations );
			$translation = $keys[0];
		}

		$book_id = absint( $atts['book'] );
		if ( $book_id < 1 ) {
			$book_id = 1;
		}
		$chapter = max( 1, absint( $atts['chapter'] ) );
		$verse   = max( 0, absint( $atts['verse'] ) );
		$features = self::get_reader_features();

		if ( ! empty( $features['explain'] ) && wp_style_is( 'thw-premium', 'registered' ) ) {
			wp_enqueue_style( 'thw-premium' );
		}

		wp_enqueue_style( 'hwbl-bible-reader' );
		wp_enqueue_script( 'hwbl-bible-reader' );
		wp_enqueue_script( 'hwbl-bible-reader-research' );
		wp_enqueue_style( 'hwbl-verse-memorize' );
		wp_enqueue_style( 'hwbl-lesson' );
		wp_enqueue_script( 'hwbl-lesson-tabs' );
		wp_enqueue_script( 'hwbl-memorization-basic' );
		wp_enqueue_script( 'hwbl-verse-memorize' );
		wp_localize_script(
			'hwbl-memorization-basic',
			'hwblMemorization',
			array(
				'today'        => wp_date( 'Y-m-d' ),
				'streakUpsell' => class_exists( 'THW_Premium' ) ? __( 'Save your streak across devices with Premium progress tracking.', 'hidden-word-bible-lessons' ) : '',
			)
		);
		wp_localize_script(
			'hwbl-bible-reader',
			'hwblBibleReader',
			array(
				'restUrl'      => rest_url( 'hwbl/v1/' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'translation'  => $translation,
				'bookId'       => $book_id,
				'chapter'      => $chapter,
				'verse'        => $verse,
				'narrator'     => self::get_default_narrator(),
				'translations' => $translations,
				'features'     => $features,
				'i18n'         => array(
					'loading'       => __( 'Loading chapter…', 'hidden-word-bible-lessons' ),
					'error'         => __( 'Could not load this chapter.', 'hidden-word-bible-lessons' ),
					'prev'          => __( 'Previous chapter', 'hidden-word-bible-lessons' ),
					'next'          => __( 'Next chapter', 'hidden-word-bible-lessons' ),
					'narrator'      => __( 'Audio narrator', 'hidden-word-bible-lessons' ),
					'translation'   => __( 'Translation', 'hidden-word-bible-lessons' ),
					'book'          => __( 'Book', 'hidden-word-bible-lessons' ),
					'chapterLbl'    => __( 'Chapter', 'hidden-word-bible-lessons' ),
					'goto'          => __( 'Go to reference', 'hidden-word-bible-lessons' ),
					'gotoPlaceholder'=> __( 'e.g. John 3:16 or Genesis 3', 'hidden-word-bible-lessons' ),
					'gotoBtn'       => __( 'Go', 'hidden-word-bible-lessons' ),
					'gotoInvalid'   => __( 'Could not understand that reference.', 'hidden-word-bible-lessons' ),
					'search'        => __( 'Search the Bible', 'hidden-word-bible-lessons' ),
					'searchPlaceholder'=> __( 'Search words or phrases…', 'hidden-word-bible-lessons' ),
					'searchBtn'     => __( 'Search', 'hidden-word-bible-lessons' ),
					'searchEmpty'   => __( 'No results found.', 'hidden-word-bible-lessons' ),
					'searchUnavailable'=> __( 'Search requires a Biblia.com API key in Premium settings.', 'hidden-word-bible-lessons' ),
					'memorize'        => __( 'Memorize this verse', 'hidden-word-bible-lessons' ),
					'memorizeHint'    => __( 'Click a verse, then start memorization practice.', 'hidden-word-bible-lessons' ),
					'memorizeLoading' => __( 'Loading memorization practice…', 'hidden-word-bible-lessons' ),
					'memorizeError'   => __( 'Could not load memorization practice.', 'hidden-word-bible-lessons' ),
					'research'        => __( 'Research & explain', 'hidden-word-bible-lessons' ),
					'researchScope'   => __( 'Explain', 'hidden-word-bible-lessons' ),
					'researchVerse'   => __( 'This verse', 'hidden-word-bible-lessons' ),
					'researchChapter' => __( 'This chapter', 'hidden-word-bible-lessons' ),
					'researchBtn'     => __( 'Explain passage', 'hidden-word-bible-lessons' ),
					'researchLoading' => __( 'Generating explanation…', 'hidden-word-bible-lessons' ),
					'researchError'   => __( 'Could not generate an explanation.', 'hidden-word-bible-lessons' ),
					'researchLogin'   => __( 'Log in to generate an AI explanation.', 'hidden-word-bible-lessons' ),
					'researchLesson'  => __( 'Open full lesson study', 'hidden-word-bible-lessons' ),
					'researchHint'    => __( 'Click a verse to explain it, or explain the whole chapter.', 'hidden-word-bible-lessons' ),
					'researchDisclaimer' => __( 'AI-generated explanation. Compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
				),
				'explainRestUrl' => class_exists( 'THW_Premium_Bible_Reader_Explain' ) && THW_Premium_Bible_Reader_Explain::is_available()
					? esc_url_raw( rest_url( 'hwbl/v1/bible-explain' ) )
					: '',
				'loggedIn'       => is_user_logged_in(),
			)
		);

		ob_start();
		?>
		<div class="hwbl-bible-reader" data-translation="<?php echo esc_attr( $translation ); ?>" data-book="<?php echo esc_attr( (string) $book_id ); ?>" data-chapter="<?php echo esc_attr( (string) $chapter ); ?>" data-verse="<?php echo esc_attr( (string) $verse ); ?>">
			<div class="hwbl-bible-reader__tools">
				<form class="hwbl-bible-reader__goto" action="#" method="get">
					<label class="hwbl-bible-reader__field hwbl-bible-reader__field--goto">
						<span class="hwbl-bible-reader__label"><?php esc_html_e( 'Go to reference', 'hidden-word-bible-lessons' ); ?></span>
						<div class="hwbl-bible-reader__goto-row">
							<input type="text" class="hwbl-bible-reader__goto-input" placeholder="<?php esc_attr_e( 'e.g. John 3:16 or Genesis 3', 'hidden-word-bible-lessons' ); ?>" autocomplete="off" />
							<button type="submit" class="hwbl-btn hwbl-btn-secondary hwbl-bible-reader__goto-btn"><?php esc_html_e( 'Go', 'hidden-word-bible-lessons' ); ?></button>
						</div>
					</label>
				</form>
				<?php if ( ! empty( $features['search'] ) ) : ?>
					<details class="hwbl-bible-reader__search-panel">
						<summary><?php esc_html_e( 'Search the Bible', 'hidden-word-bible-lessons' ); ?></summary>
						<form class="hwbl-bible-reader__search" action="#" method="get">
							<div class="hwbl-bible-reader__goto-row">
								<input type="search" class="hwbl-bible-reader__search-input" placeholder="<?php esc_attr_e( 'Search words or phrases…', 'hidden-word-bible-lessons' ); ?>" autocomplete="off" />
								<button type="submit" class="hwbl-btn hwbl-btn-secondary hwbl-bible-reader__search-btn"><?php esc_html_e( 'Search', 'hidden-word-bible-lessons' ); ?></button>
							</div>
						</form>
						<ul class="hwbl-bible-reader__search-results" hidden></ul>
					</details>
				<?php endif; ?>
			</div>
			<div class="hwbl-bible-reader__controls">
				<label class="hwbl-bible-reader__field">
					<span class="hwbl-bible-reader__label"><?php esc_html_e( 'Translation', 'hidden-word-bible-lessons' ); ?></span>
					<select class="hwbl-bible-reader__translation"></select>
				</label>
				<label class="hwbl-bible-reader__field">
					<span class="hwbl-bible-reader__label"><?php esc_html_e( 'Book', 'hidden-word-bible-lessons' ); ?></span>
					<select class="hwbl-bible-reader__book"></select>
				</label>
				<label class="hwbl-bible-reader__field">
					<span class="hwbl-bible-reader__label"><?php esc_html_e( 'Chapter', 'hidden-word-bible-lessons' ); ?></span>
					<select class="hwbl-bible-reader__chapter"></select>
				</label>
				<label class="hwbl-bible-reader__field hwbl-bible-reader__field--audio">
					<span class="hwbl-bible-reader__label"><?php esc_html_e( 'Audio narrator', 'hidden-word-bible-lessons' ); ?></span>
					<select class="hwbl-bible-reader__narrator"></select>
				</label>
			</div>
			<div class="hwbl-bible-reader__audio-wrap">
				<audio class="hwbl-bible-reader__audio" controls preload="none"></audio>
			</div>
			<nav class="hwbl-bible-reader__nav" aria-label="<?php esc_attr_e( 'Chapter navigation', 'hidden-word-bible-lessons' ); ?>">
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-bible-reader__prev" disabled><?php esc_html_e( 'Previous chapter', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-bible-reader__next" disabled><?php esc_html_e( 'Next chapter', 'hidden-word-bible-lessons' ); ?></button>
			</nav>
			<div class="hwbl-bible-reader__status" role="status" aria-live="polite"></div>
			<div class="hwbl-bible-reader__memorize-bar" hidden>
				<p class="hwbl-bible-reader__memorize-hint"><?php esc_html_e( 'Click a verse, then start memorization practice.', 'hidden-word-bible-lessons' ); ?></p>
				<button type="button" class="hwbl-btn hwbl-bible-reader__memorize-btn"><?php esc_html_e( 'Memorize this verse', 'hidden-word-bible-lessons' ); ?></button>
			</div>
			<div class="hwbl-bible-reader__memorize-panel" hidden></div>
			<?php if ( ! empty( $features['research'] ) ) : ?>
				<div class="hwbl-bible-reader__research">
					<div class="hwbl-bible-reader__research-bar">
						<p class="hwbl-bible-reader__research-hint"><?php esc_html_e( 'Click a verse to explain it, or explain the whole chapter.', 'hidden-word-bible-lessons' ); ?></p>
						<div class="hwbl-bible-reader__research-controls">
							<label class="hwbl-bible-reader__field hwbl-bible-reader__field--research-scope">
								<span class="hwbl-bible-reader__label"><?php esc_html_e( 'Explain', 'hidden-word-bible-lessons' ); ?></span>
								<select class="hwbl-bible-reader__research-scope">
									<option value="verse"><?php esc_html_e( 'This verse', 'hidden-word-bible-lessons' ); ?></option>
									<option value="chapter"><?php esc_html_e( 'This chapter', 'hidden-word-bible-lessons' ); ?></option>
								</select>
							</label>
							<?php if ( ! empty( $features['explain'] ) && function_exists( 'thw_premium_render_tradition_select' ) ) : ?>
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes markup.
								echo thw_premium_render_tradition_select(
									array(
										'id'    => 'hwbl-bible-reader-tradition',
										'class' => 'thw-ai-tradition-select hwbl-bible-reader__research-tradition',
									)
								);
								?>
							<?php endif; ?>
							<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-bible-reader__research-btn"><?php esc_html_e( 'Explain passage', 'hidden-word-bible-lessons' ); ?></button>
							<a class="hwbl-bible-reader__research-lesson hwbl-btn hwbl-btn-secondary" href="#" hidden><?php esc_html_e( 'Open full lesson study', 'hidden-word-bible-lessons' ); ?></a>
						</div>
					</div>
					<div class="hwbl-bible-reader__research-panel" hidden>
						<h3 class="hwbl-bible-reader__research-title"></h3>
						<div class="hwbl-bible-reader__research-output" aria-live="polite"></div>
						<p class="hwbl-bible-reader__research-disclaimer description"><?php esc_html_e( 'AI-generated explanation. Compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ); ?></p>
					</div>
				</div>
			<?php endif; ?>
			<article class="hwbl-bible-reader__body">
				<h2 class="hwbl-bible-reader__reference"></h2>
				<div class="hwbl-bible-reader__content"></div>
			</article>
			<footer class="hwbl-bible-reader__copyright"></footer>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Build prev/next navigation metadata.
	 *
	 * @param int   $book_id       Current book ID.
	 * @param int   $chapter       Current chapter.
	 * @param int   $chapter_count Chapters in current book.
	 * @param array $books         Full book list.
	 * @return array{prev:?array,next:?array}
	 */
	private static function build_navigation( $book_id, $chapter, $chapter_count, $books ) {
		$prev = null;
		$next = null;

		if ( $chapter > 1 ) {
			$prev = array(
				'book_id' => $book_id,
				'chapter' => $chapter - 1,
			);
		} else {
			for ( $i = count( $books ) - 1; $i >= 0; $i-- ) {
				if ( (int) $books[ $i ]['id'] < $book_id && (int) $books[ $i ]['chapters'] > 0 ) {
					$prev = array(
						'book_id' => (int) $books[ $i ]['id'],
						'chapter' => (int) $books[ $i ]['chapters'],
					);
					break;
				}
			}
		}

		if ( $chapter < $chapter_count ) {
			$next = array(
				'book_id' => $book_id,
				'chapter' => $chapter + 1,
			);
		} else {
			foreach ( $books as $book ) {
				if ( (int) $book['id'] > $book_id && (int) $book['chapters'] > 0 ) {
					$next = array(
						'book_id' => (int) $book['id'],
						'chapter' => 1,
					);
					break;
				}
			}
		}

		return array(
			'prev' => $prev,
			'next' => $next,
		);
	}

	/**
	 * USFM => chapter count for a translation.
	 *
	 * @param string $translation Translation slug.
	 * @return array<string, int>
	 */
	private static function get_chapter_count_map( $translation ) {
		$ao_id = class_exists( 'HWBL_HelloAO_Provider' ) ? HWBL_HelloAO_Provider::get_helloao_id( $translation ) : null;
		if ( ! $ao_id ) {
			return array();
		}

		$catalog = HWBL_HelloAO_Provider::fetch_books_catalog( $ao_id );
		$map     = array();

		foreach ( $catalog as $entry ) {
			if ( empty( $entry['id'] ) ) {
				continue;
			}
			$chapters = 0;
			if ( ! empty( $entry['numberOfChapters'] ) ) {
				$chapters = (int) $entry['numberOfChapters'];
			} elseif ( ! empty( $entry['lastChapterNumber'] ) ) {
				$chapters = (int) $entry['lastChapterNumber'];
			}
			if ( $chapters > 0 ) {
				$map[ strtoupper( (string) $entry['id'] ) ] = $chapters;
			}
		}

		return $map;
	}

	/**
	 * Static fallback chapter counts when Hello AO catalog is unavailable.
	 *
	 * @param int $book_id Book ID.
	 * @return int
	 */
	private static function fallback_chapter_count( $book_id ) {
		$counts = array(
			1 => 50, 2 => 40, 3 => 27, 4 => 36, 5 => 34, 6 => 24, 7 => 21, 8 => 4,
			9 => 31, 10 => 24, 11 => 22, 12 => 25, 13 => 29, 14 => 36, 15 => 10,
			16 => 13, 17 => 10, 18 => 42, 19 => 150, 20 => 31, 21 => 12, 22 => 8,
			23 => 66, 24 => 52, 25 => 5, 26 => 48, 27 => 12, 28 => 14, 29 => 3,
			30 => 9, 31 => 1, 32 => 4, 33 => 7, 34 => 3, 35 => 3, 36 => 3,
			37 => 2, 38 => 14, 39 => 4, 40 => 28, 41 => 16, 42 => 24, 43 => 21,
			44 => 28, 45 => 16, 46 => 16, 47 => 13, 48 => 6, 49 => 6, 50 => 4,
			51 => 4, 52 => 5, 53 => 3, 54 => 6, 55 => 4, 56 => 3, 57 => 1,
			58 => 13, 59 => 5, 60 => 5, 61 => 3, 62 => 5, 63 => 1, 64 => 1,
			65 => 1, 66 => 22,
		);

		return isset( $counts[ $book_id ] ) ? (int) $counts[ $book_id ] : 0;
	}

	/**
	 * Resolve translation slug from request/default.
	 *
	 * @param string $translation Requested translation.
	 * @return string Empty when invalid.
	 */
	private static function resolve_translation( $translation ) {
		$translation = self::sanitize_translation( $translation );
		$available   = self::get_reader_translations();

		if ( $translation && isset( $available[ $translation ] ) ) {
			return $translation;
		}

		$default = self::sanitize_translation( get_option( 'hwbl_active_translation', 'bsb' ) );
		if ( $default && isset( $available[ $default ] ) ) {
			return $default;
		}

		$keys = array_keys( $available );
		return $keys ? (string) $keys[0] : '';
	}

	/**
	 * @param string $translation Translation slug.
	 * @return string
	 */
	private static function sanitize_translation( $translation ) {
		return sanitize_key( (string) $translation );
	}

	/**
	 * Whether API.Bible is licensed and configured.
	 *
	 * @return bool
	 */
	private static function premium_api_bible_available() {
		if ( ! class_exists( 'THW_Premium_API_Bible' ) || ! THW_Premium_API_Bible::get_api_key() ) {
			return false;
		}

		if ( class_exists( 'THW_Premium_License' ) && ! THW_Premium_License::is_licensed() ) {
			return false;
		}

		return true;
	}
}
