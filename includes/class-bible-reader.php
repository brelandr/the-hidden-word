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
			array( 'hwbl-user-preferences' ),
			HWBL_VERSION,
			true
		);

		wp_register_script(
			'hwbl-bible-reader-research',
			HWBL_PLUGIN_URL . 'public/js/bible-reader-research.js',
			array( 'hwbl-bible-reader', 'hwbl-user-preferences' ),
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

		// Installed local Bibles (including Catholic DRA/CPDV not on Hello AO).
		if ( class_exists( 'HWBL_Local_Bible_Store' ) ) {
			$catalog = HWBL_Local_Bible_Store::get_catalog();
			foreach ( $catalog as $slug => $meta ) {
				if ( ! HWBL_Local_Bible_Store::is_installed( $slug ) ) {
					continue;
				}
				if ( ! isset( $translations[ $slug ] ) ) {
					$translations[ $slug ] = (string) ( $meta['label'] ?? strtoupper( $slug ) );
				}
			}
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
		$registered = self::get_registered_reader_translations();
		$local_keys = array();
		if ( class_exists( 'HWBL_Local_Bible_Store' ) ) {
			foreach ( array_keys( HWBL_Local_Bible_Store::get_catalog() ) as $slug ) {
				if ( HWBL_Local_Bible_Store::is_installed( $slug ) ) {
					$local_keys[] = $slug;
				}
			}
		}
		$fingerprint = md5(
			wp_json_encode(
				array(
					array_keys( $registered ),
					$local_keys,
					(bool) get_option( 'hwbl_helloao_enabled', true ),
					(bool) get_option( 'thw_biblia_api_key', '' ),
					(bool) get_option( 'thw_youversion_app_key', '' ),
					(bool) get_option( 'thw_api_bible_key', '' ),
				)
			)
		);
		$cache_key = 'hwbl_reader_translations_' . $fingerprint;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return apply_filters( 'hwbl_bible_reader_translations', $cached );
		}

		$accessible = array();
		foreach ( $registered as $slug => $label ) {
			if ( self::is_translation_accessible( $slug ) ) {
				$accessible[ $slug ] = $label;
			}
		}

		set_transient( $cache_key, $accessible, HOUR_IN_SECONDS );

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

		if ( class_exists( 'HWBL_Local_Bible_Store' ) && HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return true;
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
			// Public-domain fallback always works; Biblia is used first when configured.
			'search'   => class_exists( 'HWBL_Bible_Text_Search' ) || ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() ),
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
		$payload = self::search_detailed( $translation, $query, $limit );
		return isset( $payload['results'] ) && is_array( $payload['results'] ) ? $payload['results'] : array();
	}

	/**
	 * Search Bible text with metadata (fallback translation, errors).
	 *
	 * @param string $translation Translation slug.
	 * @param string $query       Search query.
	 * @param int    $limit       Max results.
	 * @return array{results:array<int,array<string,mixed>>,translation:string,requested_translation:string,error:string}
	 */
	public static function search_detailed( $translation, $query, $limit = 12 ) {
		$requested = self::sanitize_translation( $translation );
		$empty     = array(
			'results'               => array(),
			'translation'           => '',
			'requested_translation' => $requested,
			'error'                 => '',
		);

		if ( ! $requested ) {
			$requested = self::resolve_translation( '' );
		}
		$empty['requested_translation'] = $requested;

		if ( class_exists( 'HWBL_Bible_Text_Search' ) ) {
			$query = HWBL_Bible_Text_Search::normalize_query( $query );
		} else {
			$query = trim( (string) $query );
		}
		if ( '' === $query ) {
			$empty['error'] = 'invalid_query';
			return $empty;
		}

		// Prefer locally installed public-domain Bibles before remote search.
		if ( class_exists( 'HWBL_Local_Bible_Store' ) && HWBL_Local_Bible_Store::is_installed( $requested ) ) {
			$local_results = HWBL_Local_Bible_Store::search( $requested, $query, $limit );
			if ( ! empty( $local_results ) ) {
				return array(
					'results'               => $local_results,
					'translation'           => $requested,
					'requested_translation' => $requested,
					'error'                 => '',
				);
			}
		}

		// Prefer Biblia when configured; many site prefs (NIV/BSB) still need a public-domain fallback.
		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() ) {
			$payload = THW_Premium_Biblia::search_bible_detailed( $requested, $query, $limit );
			if ( is_array( $payload ) && ! empty( $payload['results'] ) ) {
				$payload['requested_translation'] = $requested;
				if ( empty( $payload['translation'] ) ) {
					$payload['translation'] = $requested;
				}
				$payload['error'] = '';
				return $payload;
			}
		}

		if ( class_exists( 'HWBL_Bible_Text_Search' ) ) {
			$fallback = HWBL_Bible_Text_Search::search( $requested, $query, $limit );
			if ( is_array( $fallback ) ) {
				$fallback['requested_translation'] = $requested;
				if ( empty( $fallback['translation'] ) ) {
					$fallback['translation'] = HWBL_Bible_Text_Search::resolve_translation( $requested );
				}
				if ( ! isset( $fallback['results'] ) || ! is_array( $fallback['results'] ) ) {
					$fallback['results'] = array();
				}
				if ( ! isset( $fallback['error'] ) ) {
					$fallback['error'] = '';
				}
				return $fallback;
			}
		}

		$empty['error'] = 'search_failed';
		return $empty;
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

		// Local installs: only books/chapters actually present (supports Catholic DC books
		// and longer Esther/Daniel chapter counts without polluting Protestant lists).
		if ( class_exists( 'HWBL_Local_Bible_Store' ) && HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			$books = array();
			foreach ( HWBL_Local_Bible_Store::list_book_ids( $translation ) as $book_id ) {
				$book_id  = (int) $book_id;
				$chapters = HWBL_Local_Bible_Store::list_chapter_numbers( $translation, $book_id );
				$count    = $chapters ? (int) max( $chapters ) : 0;
				if ( $count < 1 ) {
					continue;
				}
				$books[] = array(
					'id'       => $book_id,
					'name'     => HWBL_Books::get_name( $book_id ),
					'chapters' => $count,
					'usfm'     => HWBL_Books::get_usfm( $book_id ),
				);
			}
			usort(
				$books,
				static function ( $a, $b ) {
					return HWBL_Books::get_display_sort( (int) $a['id'] ) <=> HWBL_Books::get_display_sort( (int) $b['id'] );
				}
			);
			return $books;
		}

		$chapter_map = self::get_chapter_count_map( $translation );
		$books       = array();
		$has_dc      = false;

		foreach ( HWBL_Books::get_all() as $id => $name ) {
			$book_id = (int) $id;
			$usfm    = HWBL_Books::get_usfm( $book_id );
			$chapters = isset( $chapter_map[ $usfm ] ) ? (int) $chapter_map[ $usfm ] : 0;

			// Deuterocanonical books only appear when the translation catalog includes them.
			if ( HWBL_Books::is_deuterocanonical( $book_id ) ) {
				if ( $chapters < 1 ) {
					continue;
				}
				$has_dc = true;
			} elseif ( $chapters < 1 ) {
				$chapters = self::fallback_chapter_count( $book_id );
			}

			if ( $chapters < 1 ) {
				continue;
			}

			$books[] = array(
				'id'       => $book_id,
				'name'     => (string) $name,
				'chapters' => $chapters,
				'usfm'     => $usfm,
			);
		}

		if ( $has_dc ) {
			usort(
				$books,
				static function ( $a, $b ) {
					return HWBL_Books::get_display_sort( (int) $a['id'] ) <=> HWBL_Books::get_display_sort( (int) $b['id'] );
				}
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

		if ( class_exists( 'HWBL_Local_Bible_Store' ) && HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			$payload = HWBL_Local_Bible_Provider::get_chapter_payload( $book_id, $chapter, $translation );
		}

		if ( ! HWBL_Http_Utils::is_valid_chapter_payload( $payload ) && class_exists( 'HWBL_HelloAO_Provider' ) && HWBL_HelloAO_Provider::get_helloao_id( $translation ) ) {
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
					'scope'       => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => 'verse',
					),
					'tradition'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
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
		$scope    = sanitize_key( (string) $request['scope'] );
		if ( 'chapter' !== $scope ) {
			$scope = 'verse';
		}

		$explain_url = '';
		$translation = sanitize_key( (string) $request['translation'] );
		$tradition   = sanitize_key( (string) $request['tradition'] );
		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) && '' !== $translation ) {
			if ( 'chapter' === $scope ) {
				$lookup_verse = 0;
			} else {
				$lookup_verse = $verse;
			}
			if ( 'chapter' === $scope || $lookup_verse > 0 ) {
				if ( '' === $tradition && function_exists( 'thw_premium_resolve_explain_rules_for_request' ) ) {
					$resolved  = thw_premium_resolve_explain_rules_for_request( '', '' );
					$tradition = sanitize_key( (string) ( $resolved['preset'] ?? 'site' ) );
				}
				if ( '' === $tradition ) {
					$tradition = 'site';
				}
				$resolved_display = THW_Premium_Bible_Reader_Explain_Store::resolve_for_display(
					array(
						'book_id'     => $book_id,
						'chapter'     => $chapter,
						'verse'       => $lookup_verse,
						'translation' => $translation,
						'scope'       => $scope,
						'tradition'   => $tradition,
					)
				);
				$row = is_array( $resolved_display['content_row'] ?? null ) ? $resolved_display['content_row'] : null;
				if ( is_array( $row ) && THW_Premium_Bible_Reader_Explain_Store::has_usable_explanation( $row ) ) {
					$explain_url = THW_Premium_Bible_Reader_Explain_Store::get_public_url( $row );
				}
			}
		}

		return new WP_REST_Response(
			array(
				'lesson_id'     => (int) $lesson['lesson_id'],
				'lesson_url'    => (string) $lesson['url'],
				'in_curriculum' => (bool) $lesson['in_curriculum'],
				'explain'       => ! empty( $features['explain'] ) || class_exists( 'THW_Premium_Bible_Reader_Explain' ),
				'explain_url'   => $explain_url,
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

		$requested = self::sanitize_translation( $request['translation'] );
		if ( ! $requested ) {
			$requested = self::resolve_translation( '' );
		}
		if ( ! $requested ) {
			return new WP_REST_Response( array( 'error' => 'invalid_translation' ), 400 );
		}

		$payload = self::search_detailed( $requested, $request['q'], (int) $request['limit'] );
		if ( ! empty( $payload['error'] ) && 'search_unavailable' === $payload['error'] ) {
			return new WP_REST_Response(
				array(
					'error'                 => 'search_unavailable',
					'translation'           => $requested,
					'requested_translation' => $requested,
					'query'                 => (string) $request['q'],
					'results'               => array(),
				),
				403
			);
		}

		return new WP_REST_Response(
			array(
				'translation'           => ! empty( $payload['translation'] ) ? $payload['translation'] : $requested,
				'requested_translation' => $requested,
				'query'                 => (string) $request['q'],
				'results'               => isset( $payload['results'] ) ? $payload['results'] : array(),
				'error'                 => isset( $payload['error'] ) ? $payload['error'] : '',
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
			if ( class_exists( 'HWBL_User_Preferences' ) ) {
				$translation = self::sanitize_translation(
					HWBL_User_Preferences::resolve_translation( $translations )
				);
			} else {
				$translation = self::sanitize_translation( get_option( 'hwbl_active_translation', 'bsb' ) );
			}
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
		if ( ! empty( $features['study_card'] ) && class_exists( 'THW_Premium_Bible_Study_Card' ) ) {
			THW_Premium_Bible_Study_Card::enqueue_assets();
		}
		if ( ! empty( $features['maps'] ) && class_exists( 'HWBL_Bible_Places' ) ) {
			HWBL_Bible_Places::enqueue_assets();
		}
		if ( ! empty( $features['concordance'] ) && class_exists( 'HWBL_Bible_Concordance' ) ) {
			HWBL_Bible_Concordance::enqueue_assets();
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
				'preferredTranslation' => class_exists( 'HWBL_User_Preferences' )
					? HWBL_User_Preferences::get_preferred_translation()
					: '',
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
					'researchLogin'   => __( 'Log in to generate the first AI explanation for this passage and Bible version. Once saved, everyone can read it.', 'hidden-word-bible-lessons' ),
					'researchLesson'  => __( 'Open full lesson study', 'hidden-word-bible-lessons' ),
					'researchSaved'   => __( 'Read saved explanation', 'hidden-word-bible-lessons' ),
					'researchHint'    => __( 'Click a verse to explain it, or explain the whole chapter.', 'hidden-word-bible-lessons' ),
					'researchDisclaimer' => __( 'AI-generated explanation. Compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
					'researchLoadingCached' => __( 'Loading saved explanation…', 'hidden-word-bible-lessons' ),
					'researchTraditionDiff' => __( 'Tradition variation', 'hidden-word-bible-lessons' ),
					'studyBtn'              => __( 'Study this verse', 'hidden-word-bible-lessons' ),
					'studyLoading'          => __( 'Building your Verse Study Card…', 'hidden-word-bible-lessons' ),
					'studyError'            => __( 'Could not build a study card.', 'hidden-word-bible-lessons' ),
					'studyLogin'            => __( 'Sign in to generate a Verse Study Card. Once saved, everyone can read it.', 'hidden-word-bible-lessons' ),
					'studyHint'             => __( 'Click a verse, then open a guided study card.', 'hidden-word-bible-lessons' ),
					'mapBtn'                => __( 'Map places', 'hidden-word-bible-lessons' ),
					'mapLoading'            => __( 'Loading places…', 'hidden-word-bible-lessons' ),
					'mapError'              => __( 'Could not load places for this passage.', 'hidden-word-bible-lessons' ),
					'mapHint'               => __( 'Click a verse, then map its places — or map the whole chapter.', 'hidden-word-bible-lessons' ),
					'mapEmpty'              => __( 'No catalogued places for this passage.', 'hidden-word-bible-lessons' ),
					'concordanceBtn'        => __( 'Concordance', 'hidden-word-bible-lessons' ),
				),
				'explainRestUrl' => class_exists( 'THW_Premium_Bible_Reader_Explain' )
					? esc_url_raw( rest_url( 'hwbl/v1/bible-explain' ) )
					: '',
				'studyCardRestUrl' => class_exists( 'THW_Premium_Bible_Study_Card' )
					? esc_url_raw( rest_url( 'hwbl/v1/bible/study-card' ) )
					: '',
				'loggedIn'       => is_user_logged_in(),
				'maps'           => class_exists( 'HWBL_Bible_Places' ) ? HWBL_Bible_Places::get_front_config() : array( 'enabled' => false ),
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
							<?php if ( ! empty( $features['explain'] ) && function_exists( 'thw_premium_the_tradition_select' ) ) : ?>
								<?php
								thw_premium_the_tradition_select(
									array(
										'id'    => 'hwbl-bible-reader-tradition',
										'class' => 'thw-ai-tradition-select hwbl-bible-reader__research-tradition',
									)
								);
								?>
							<?php endif; ?>
							<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-bible-reader__research-btn"><?php esc_html_e( 'Explain passage', 'hidden-word-bible-lessons' ); ?></button>
							<?php if ( ! empty( $features['study_card'] ) ) : ?>
								<button type="button" class="hwbl-btn hwbl-bible-reader__study-btn"><?php esc_html_e( 'Study this verse', 'hidden-word-bible-lessons' ); ?></button>
							<?php endif; ?>
							<?php if ( ! empty( $features['maps'] ) ) : ?>
								<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-bible-reader__map-btn"><?php esc_html_e( 'Map places', 'hidden-word-bible-lessons' ); ?></button>
							<?php endif; ?>
							<?php if ( ! empty( $features['concordance'] ) ) : ?>
								<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-bible-reader__concordance-btn"><?php esc_html_e( 'Concordance', 'hidden-word-bible-lessons' ); ?></button>
							<?php endif; ?>
							<a class="hwbl-bible-reader__research-saved hwbl-btn hwbl-btn-secondary" href="#" hidden><?php esc_html_e( 'Read saved explanation', 'hidden-word-bible-lessons' ); ?></a>
							<a class="hwbl-bible-reader__research-lesson hwbl-btn hwbl-btn-secondary" href="#" hidden><?php esc_html_e( 'Open full lesson study', 'hidden-word-bible-lessons' ); ?></a>
						</div>
					</div>
					<div class="hwbl-bible-reader__research-panel" hidden>
						<h3 class="hwbl-bible-reader__research-title"></h3>
						<div class="hwbl-bible-reader__research-output" aria-live="polite"></div>
						<p class="hwbl-bible-reader__research-post-wrap" hidden>
							<a class="hwbl-bible-reader__research-post" href="#" target="_blank" rel="noopener noreferrer"></a>
						</p>
						<p class="hwbl-bible-reader__research-disclaimer description"><?php esc_html_e( 'AI-generated explanation. Compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ); ?></p>
					</div>
					<?php if ( ! empty( $features['study_card'] ) ) : ?>
						<div class="hwbl-bible-reader__study-panel" hidden>
							<div class="hwbl-bible-reader__study-status" role="status" aria-live="polite"></div>
							<div class="hwbl-bible-reader__study-output"></div>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $features['maps'] ) ) : ?>
						<div class="hwbl-bible-reader__map-panel hwbl-bible-map" hidden>
							<div class="hwbl-bible-map__canvas" role="img" aria-label="<?php esc_attr_e( 'Map of biblical places', 'hidden-word-bible-lessons' ); ?>"></div>
							<details class="hwbl-bible-map__places">
								<summary class="hwbl-bible-map__places-summary"><?php esc_html_e( 'Places list', 'hidden-word-bible-lessons' ); ?></summary>
								<ul class="hwbl-bible-map__list" aria-live="polite"></ul>
							</details>
							<p class="hwbl-bible-map__status" role="status"></p>
							<p class="hwbl-bible-map__attribution"><?php echo esc_html( HWBL_Bible_Places::get_attribution() ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $features['concordance'] ) ) : ?>
						<div class="hwbl-bible-reader__concordance-panel" hidden>
							<?php
							echo HWBL_Bible_Concordance::render_shortcode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode returns escaped HTML.
								array(
									'title'       => __( 'Bible Concordance', 'hidden-word-bible-lessons' ),
									'translation' => $translation,
								)
							);
							?>
						</div>
					<?php endif; ?>
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
		// Local installs use built-in chapter counts — skip remote Hello AO catalog.
		if ( class_exists( 'HWBL_Local_Bible_Store' ) && HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return array();
		}

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
			// Deuterocanonical / Apocrypha chapter counts (approx. fallbacks).
			67 => 14, 68 => 16, 69 => 19, 70 => 51, 71 => 6, 72 => 16, 73 => 15,
			74 => 9, 75 => 16, 76 => 1, 77 => 1, 78 => 7, 79 => 18,
			80 => 16, 81 => 14, 82 => 1, 83 => 1, 84 => 1, 85 => 1,
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
