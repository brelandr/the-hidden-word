<?php
/**
 * Biblia.com translation provider (BYOK).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Biblia
 */
class THW_Premium_Biblia implements HWBL_Translation_Provider {

	const API_BASE = 'https://api.biblia.com/v1/';

	/**
	 * Site slug => Biblia Bible ID.
	 *
	 * @var array<string, string>
	 */
	private static $bible_ids = array(
		'esv'  => 'esv',
		'nlt'  => 'nlt',
		'nasb' => 'nasb',
		'niv'  => 'niv',
		'nkjv' => 'nkjv',
		'csb'  => 'csb',
		'leb'  => 'LEB',
		'kjv'  => 'KJV1900',
		'asv'  => 'ASV',
		'darby' => 'DARBY',
		'ylt'  => 'YLT',
	);

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'hwbl_translation_providers', array( __CLASS__, 'register_provider' ) );
		add_filter( 'hwbl_supported_translations', array( __CLASS__, 'add_translations' ) );
		add_filter( 'hwbl_get_verse_text', array( __CLASS__, 'filter_verse_text' ), 10, 5 );
		add_filter( 'hwbl_render_copyright', array( __CLASS__, 'render_copyright' ), 10, 2 );
		add_action( 'update_option_thw_biblia_api_key', array( __CLASS__, 'clear_accessibility_cache' ) );
	}

	/**
	 * Fill empty verse text via Biblia when earlier providers miss (e.g. NIV outside curriculum).
	 *
	 * @param string|null $text        Existing text.
	 * @param int         $book_id     Book ID.
	 * @param int         $chapter     Chapter.
	 * @param int         $verse       Verse.
	 * @param string      $translation Translation slug.
	 * @return string|null
	 */
	public static function filter_verse_text( $text, $book_id, $chapter, $verse, $translation ) {
		if ( $text ) {
			return $text;
		}

		if ( ! self::is_available() ) {
			return $text;
		}

		$instance = new self();
		$fetched  = $instance->get_verse( $book_id, $chapter, $verse, $translation );
		return $fetched ? $fetched : $text;
	}

	/**
	 * Register this provider when licensed and configured.
	 *
	 * @param array $providers Providers.
	 * @return array
	 */
	public static function register_provider( $providers ) {
		if ( ! self::is_available() ) {
			return $providers;
		}

		$providers['biblia'] = new self();
		return $providers;
	}

	/**
	 * Whether Biblia is licensed and has an API key.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return THW_Premium_License::is_licensed() && self::get_api_key();
	}

	/**
	 * Add Biblia translations when key configured.
	 *
	 * @param array $translations Translations.
	 * @return array
	 */
	public static function add_translations( $translations ) {
		if ( ! self::is_available() ) {
			return $translations;
		}

		$biblia = array(
			'esv'   => __( 'English Standard Version (ESV)', 'hidden-word-bible-lessons' ),
			'nlt'   => __( 'New Living Translation (NLT)', 'hidden-word-bible-lessons' ),
			'nasb'  => __( 'New American Standard Bible (NASB)', 'hidden-word-bible-lessons' ),
			'niv'   => __( 'New International Version (NIV)', 'hidden-word-bible-lessons' ),
			'nkjv'  => __( 'New King James Version (NKJV)', 'hidden-word-bible-lessons' ),
			'csb'   => __( 'Christian Standard Bible (CSB)', 'hidden-word-bible-lessons' ),
			'leb'   => __( 'Lexham English Bible (LEB)', 'hidden-word-bible-lessons' ),
			'kjv'   => __( 'King James Version (KJV)', 'hidden-word-bible-lessons' ),
			'asv'   => __( 'American Standard Version (ASV)', 'hidden-word-bible-lessons' ),
			'darby' => __( 'Darby Translation (DARBY)', 'hidden-word-bible-lessons' ),
			'ylt'   => __( "Young's Literal Translation (YLT)", 'hidden-word-bible-lessons' ),
		);

		return array_merge( $translations, $biblia );
	}

	/**
	 * Copyright from Biblia citation text when available.
	 *
	 * @param string $html        Existing copyright HTML.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public static function render_copyright( $html, $translation ) {
		if ( $html || ! self::is_available() || ! isset( self::$bible_ids[ $translation ] ) ) {
			return $html;
		}

		$citation = self::get_sample_citation( self::$bible_ids[ $translation ] );
		if ( '' === $citation ) {
			return $html;
		}

		return '<p class="thw-copyright thw-biblia-copyright">' . esc_html( $citation ) . '</p>';
	}

	/**
	 * Get stored Biblia API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return get_option( 'thw_biblia_api_key', '' );
	}

	/**
	 * Biblia Bible ID for a site translation slug.
	 *
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public static function get_bible_id_for_translation( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		return isset( self::$bible_ids[ $translation ] ) ? self::$bible_ids[ $translation ] : null;
	}

	/**
	 * Parse a free-form passage string via Biblia.
	 *
	 * @param string $text Passage text (e.g. "John 3:16").
	 * @return array<int, array<string, mixed>> Parsed passage entries.
	 */
	public static function parse_passage( $text ) {
		$api_key = self::get_api_key();
		$text    = trim( (string) $text );
		if ( ! $api_key || '' === $text ) {
			return array();
		}

		$cache_key = 'hwbl_biblia_parse_' . md5( strtolower( $text ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$url = add_query_arg(
			array(
				'passage' => $text,
				'key'     => $api_key,
			),
			self::API_BASE . 'bible/parse'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( ! HWBL_Http_Utils::response_ok( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return array();
		}

		$passages = ( isset( $body['passages'] ) && is_array( $body['passages'] ) ) ? $body['passages'] : array();
		set_transient( $cache_key, $passages, DAY_IN_SECONDS );

		return $passages;
	}

	/**
	 * Get verse from Biblia content API with transient cache.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public function get_verse( $book_id, $chapter, $verse, $translation ) {
		$api_key = self::get_api_key();
		if ( ! $api_key || ! isset( self::$bible_ids[ $translation ] ) ) {
			return null;
		}

		$passage   = HWBL_Books::passage_for_biblia( $book_id, $chapter, $verse );
		$cache_key = 'thw_biblia_' . md5( $translation . '_' . $passage );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return is_string( $cached ) ? $cached : null;
		}

		$bible_id = self::$bible_ids[ $translation ];
		$url      = add_query_arg(
			array(
				'passage'    => $passage,
				'key'        => $api_key,
				'citation'   => 'true',
				'style'      => 'bibleTextOnly',
				'paragraphs' => 'false',
			),
			self::API_BASE . 'bible/content/' . rawurlencode( $bible_id ) . '.txt'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( ! HWBL_Http_Utils::response_ok( $response ) ) {
			return null;
		}

		$raw = wp_remote_retrieve_body( $response );
		if ( '' === trim( $raw ) ) {
			return null;
		}

		$text = self::parse_content_response( $raw, $passage );
		$text = HWBL_Http_Utils::sanitize_bible_text( $text );
		if ( '' === $text ) {
			return null;
		}

		set_transient( $cache_key, $text, WEEK_IN_SECONDS );

		return $text;
	}

	/**
	 * Strip reference/citation lines from Biblia .txt content.
	 *
	 * @param string $raw     Raw API response.
	 * @param string $passage Expected passage reference.
	 * @return string
	 */
	public static function parse_content_response( $raw, $passage = '' ) {
		if ( HWBL_Http_Utils::looks_like_html_error( $raw ) ) {
			return '';
		}

		$lines = preg_split( '/\r\n|\r|\n/', trim( (string) $raw ) );
		if ( ! is_array( $lines ) ) {
			return trim( (string) $raw );
		}

		$text_lines = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			if ( self::looks_like_citation_line( $line ) ) {
				continue;
			}
			if ( '' !== $passage && 0 === stripos( $line, $passage ) ) {
				$line = trim( substr( $line, strlen( $passage ) ) );
				$line = ltrim( $line, " \t.:-" );
			}
			if ( '' !== $line ) {
				$text_lines[] = $line;
			}
		}

		return trim( implode( ' ', $text_lines ) );
	}

	/**
	 * Fetch a full chapter for the Bible reader.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter number.
	 * @param string $translation Translation slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_chapter( $book_id, $chapter, $translation ) {
		$api_key = self::get_api_key();
		$translation = strtolower( sanitize_key( (string) $translation ) );
		if ( ! $api_key || ! isset( self::$bible_ids[ $translation ] ) ) {
			return null;
		}

		$passage   = HWBL_Books::get_name( $book_id ) . ' ' . (int) $chapter;
		$cache_key = 'thw_biblia_ch_' . md5( $translation . '_' . $passage );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && HWBL_Http_Utils::is_valid_chapter_payload( $cached ) ) {
			return $cached;
		}
		if ( false !== $cached ) {
			delete_transient( $cache_key );
		}

		$bible_id = self::$bible_ids[ $translation ];
		$url      = add_query_arg(
			array(
				'passage'    => $passage,
				'key'        => $api_key,
				'citation'   => 'false',
				'style'      => 'oneVersePerLine',
				'paragraphs' => 'false',
			),
			self::API_BASE . 'bible/content/' . rawurlencode( $bible_id ) . '.txt'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) || ! HWBL_Http_Utils::response_ok( $response ) ) {
			return null;
		}

		$raw = wp_remote_retrieve_body( $response );
		if ( '' === trim( $raw ) || HWBL_Http_Utils::looks_like_html_error( $raw ) ) {
			return null;
		}

		$verses = self::parse_chapter_content( $raw );
		if ( empty( $verses ) ) {
			return null;
		}

		$payload = array(
			'verses'   => $verses,
			'headings' => array(),
			'audio'    => array(),
		);

		set_transient( $cache_key, $payload, WEEK_IN_SECONDS );

		return $payload;
	}

	/**
	 * Split Biblia one-verse-per-line chapter text into verse rows.
	 *
	 * @param string $raw Raw API response.
	 * @return array<int, array{number:int,text:string}>
	 */
	public static function parse_chapter_content( $raw ) {
		$raw = (string) $raw;
		if ( HWBL_Http_Utils::looks_like_html_error( $raw ) ) {
			return array();
		}

		$lines  = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
		$verses = array();

		if ( ! is_array( $lines ) ) {
			return $verses;
		}

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || self::looks_like_citation_line( $line ) ) {
				continue;
			}

			if ( preg_match( '/^(\d+)\s+(.+)$/', $line, $matches ) ) {
				$verses[] = array(
					'number' => (int) $matches[1],
					'text'   => trim( $matches[2] ),
				);
				continue;
			}

			if ( ! empty( $verses ) ) {
				$last = count( $verses ) - 1;
				$verses[ $last ]['text'] .= ' ' . $line;
			}
		}

		return $verses;
	}

	/**
	 * Supported translations from this provider.
	 *
	 * @return array<string, string>
	 */
	public function get_supported_translations() {
		return self::add_translations( array() );
	}

	/**
	 * Whether a line looks like a publisher citation footer.
	 *
	 * @param string $line Line.
	 * @return bool
	 */
	private static function looks_like_citation_line( $line ) {
		if ( preg_match( '/^(Scripture quotations|All rights reserved|Used by permission|copyright)/i', $line ) ) {
			return true;
		}
		if ( preg_match( '/^[(\[]?[A-Z]{2,5}[)\]]?\s*$/', $line ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Fetch a one-verse citation string for copyright display.
	 *
	 * @param string $bible_id Biblia Bible ID.
	 * @return string
	 */
	private static function get_sample_citation( $bible_id ) {
		$cache_key = 'thw_biblia_copy_' . md5( strtolower( $bible_id ) );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) ) {
			return $cached;
		}

		$api_key = self::get_api_key();
		if ( ! $api_key ) {
			return '';
		}

		$url = add_query_arg(
			array(
				'passage'  => 'John 3:16',
				'key'      => $api_key,
				'citation' => 'true',
				'style'    => 'bibleTextOnly',
			),
			self::API_BASE . 'bible/content/' . rawurlencode( $bible_id ) . '.txt'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( ! HWBL_Http_Utils::response_ok( $response ) ) {
			return '';
		}

		$raw   = wp_remote_retrieve_body( $response );
		$lines = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
		$citation = '';
		if ( is_array( $lines ) ) {
			foreach ( array_reverse( $lines ) as $line ) {
				$line = trim( $line );
				if ( self::looks_like_citation_line( $line ) ) {
					$citation = $line;
					break;
				}
			}
		}

		set_transient( $cache_key, $citation, WEEK_IN_SECONDS );

		return $citation;
	}

	/**
	 * Clear cached reader accessibility probes (after key change).
	 */
	public static function clear_accessibility_cache() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk transient purge by prefix.
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_thw_biblia_acc_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_thw_biblia_acc_' ) . '%'
			)
		);
	}

	/**
	 * Whether this translation slug returns content from Biblia for the current key.
	 *
	 * @param string $translation Translation slug.
	 * @return bool
	 */
	public static function is_translation_accessible( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		if ( ! self::is_available() || ! isset( self::$bible_ids[ $translation ] ) ) {
			return false;
		}

		$cache_key = 'thw_biblia_acc_' . md5( self::get_api_key() . '_' . $translation );
		$cached    = get_transient( $cache_key );
		if ( 'yes' === $cached ) {
			return true;
		}
		if ( 'no' === $cached ) {
			return false;
		}

		$bible_id = self::$bible_ids[ $translation ];
		$url      = add_query_arg(
			array(
				'passage'    => 'John 3:16',
				'key'        => self::get_api_key(),
				'citation'   => 'false',
				'style'      => 'bibleTextOnly',
				'paragraphs' => 'false',
			),
			self::API_BASE . 'bible/content/' . rawurlencode( $bible_id ) . '.txt'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 12 ) );
		$ok       = false;
		if ( ! is_wp_error( $response ) && HWBL_Http_Utils::response_ok( $response ) ) {
			$raw = wp_remote_retrieve_body( $response );
			$ok  = '' !== self::parse_content_response( $raw, 'John 3:16' );
		}

		set_transient( $cache_key, $ok ? 'yes' : 'no', WEEK_IN_SECONDS );

		return $ok;
	}

	/**
	 * Parse a reference for the Bible reader (Biblia parse with local fallback).
	 *
	 * @param string $text Reference text.
	 * @return array<string, mixed>|null
	 */
	public static function parse_reader_reference( $text ) {
		$local = HWBL_Books::parse_reference( $text );
		if ( ! self::is_available() ) {
			return $local;
		}

		$passages = self::parse_passage( $text );
		if ( empty( $passages[0]['parts'] ) || ! is_array( $passages[0]['parts'] ) ) {
			return $local;
		}

		$parts   = $passages[0]['parts'];
		$book_id = HWBL_Books::get_id_by_name( isset( $parts['book'] ) ? (string) $parts['book'] : '' );
		if ( $book_id < 1 && ! empty( $parts['book'] ) ) {
			$book_id = HWBL_Books::resolve_book_query( (string) $parts['book'] );
		}
		if ( $book_id < 1 || empty( $parts['chapter'] ) ) {
			return $local;
		}

		$chapter   = max( 1, (int) $parts['chapter'] );
		$verse     = ! empty( $parts['verse'] ) ? max( 1, (int) $parts['verse'] ) : 0;
		$verse_end = ! empty( $parts['endVerse'] ) ? max( 1, (int) $parts['endVerse'] ) : 0;

		return array(
			'book_id'   => $book_id,
			'chapter'   => $chapter,
			'verse'     => $verse,
			'verse_end' => $verse_end,
			'reference' => ! empty( $passages[0]['passage'] ) ? (string) $passages[0]['passage'] : HWBL_Books::format_reference( $book_id, $chapter, max( 1, $verse ), $verse_end ),
		);
	}

	/**
	 * Ordered Biblia translation slugs to try for text search.
	 *
	 * Hello AO–only prefs (e.g. BSB) are not searchable via Biblia, so we fall
	 * back to overlapping or public-domain Biblia texts.
	 *
	 * @param string $translation Requested translation slug.
	 * @return array<int, string>
	 */
	public static function get_search_translation_candidates( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		$aliases     = array(
			'dby' => 'darby',
		);
		if ( isset( $aliases[ $translation ] ) ) {
			$translation = $aliases[ $translation ];
		}

		$fallbacks = array( 'kjv', 'asv', 'ylt', 'leb', 'darby', 'esv', 'nlt', 'niv', 'nasb', 'nkjv', 'csb' );
		$ordered   = array();
		if ( $translation && isset( self::$bible_ids[ $translation ] ) ) {
			$ordered[] = $translation;
		}
		foreach ( $fallbacks as $slug ) {
			if ( ! in_array( $slug, $ordered, true ) && isset( self::$bible_ids[ $slug ] ) ) {
				$ordered[] = $slug;
			}
		}

		return $ordered;
	}

	/**
	 * Search Bible text via Biblia.com (results only).
	 *
	 * @param string $translation Translation slug.
	 * @param string $query       Search query.
	 * @param int    $limit       Max results.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_bible( $translation, $query, $limit = 12 ) {
		$payload = self::search_bible_detailed( $translation, $query, $limit );
		return isset( $payload['results'] ) && is_array( $payload['results'] ) ? $payload['results'] : array();
	}

	/**
	 * Search Bible text via Biblia.com with fallback metadata.
	 *
	 * @param string $translation Translation slug.
	 * @param string $query       Search query.
	 * @param int    $limit       Max results.
	 * @return array{results:array<int,array<string,mixed>>,translation:string,error:string}
	 */
	public static function search_bible_detailed( $translation, $query, $limit = 12 ) {
		$api_key = self::get_api_key();
		if ( class_exists( 'HWBL_Bible_Text_Search' ) ) {
			$query = HWBL_Bible_Text_Search::normalize_query( $query );
		} else {
			$query = trim( (string) $query );
		}
		$limit = max( 1, min( 25, (int) $limit ) );
		$empty = array(
			'results'     => array(),
			'translation' => '',
			'error'       => '',
		);

		if ( ! $api_key || '' === $query ) {
			$empty['error'] = 'search_unavailable';
			return $empty;
		}

		$candidates = self::get_search_translation_candidates( $translation );
		if ( empty( $candidates ) ) {
			$empty['error'] = 'search_unavailable';
			return $empty;
		}

		$cache_key = 'hwbl_biblia_search_v3_' . md5( implode( ',', $candidates ) . '|' . strtolower( $query ) . '|' . $limit );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['results'] ) && ! empty( $cached['results'] ) ) {
			return $cached;
		}

		$last_error     = 'search_failed';
		$empty_success  = null;
		foreach ( $candidates as $slug ) {
			foreach ( array( 'verse', 'fuzzy' ) as $mode ) {
				$attempt = self::request_bible_search( $slug, $query, $limit, $mode );
				if ( ! empty( $attempt['error'] ) ) {
					$last_error = (string) $attempt['error'];
					continue;
				}

				$results = isset( $attempt['results'] ) ? $attempt['results'] : array();
				$payload = array(
					'results'     => $results,
					'translation' => $slug,
					'error'       => '',
				);

				if ( ! empty( $results ) ) {
					set_transient( $cache_key, $payload, HOUR_IN_SECONDS );
					return $payload;
				}

				if ( null === $empty_success ) {
					$empty_success = $payload;
				}
			}
		}

		if ( is_array( $empty_success ) ) {
			return $empty_success;
		}

		$empty['error'] = $last_error;
		return $empty;
	}

	/**
	 * Biblia bible IDs to try for a site translation slug.
	 *
	 * @param string $translation Translation slug.
	 * @return array<int, string>
	 */
	private static function get_bible_ids_for_search( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		if ( ! isset( self::$bible_ids[ $translation ] ) ) {
			return array();
		}

		$ids = array( self::$bible_ids[ $translation ] );
		// Docs list both KJV and KJV1900; try both when searching KJV.
		if ( 'kjv' === $translation ) {
			$ids[] = 'KJV';
			$ids[] = 'KJV1900';
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Run one Biblia search request for a translation slug.
	 *
	 * @param string $translation Translation slug in $bible_ids.
	 * @param string $query       Search query.
	 * @param int    $limit       Max results.
	 * @param string $mode        verse|fuzzy.
	 * @return array{results?:array<int,array<string,mixed>>,error?:string}
	 */
	private static function request_bible_search( $translation, $query, $limit, $mode = 'verse' ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		$bible_ids   = self::get_bible_ids_for_search( $translation );
		if ( empty( $bible_ids ) ) {
			return array( 'error' => 'invalid_translation' );
		}

		$mode        = in_array( $mode, array( 'verse', 'fuzzy' ), true ) ? $mode : 'verse';
		$last_error  = 'search_failed';
		$empty_ok    = null;

		foreach ( $bible_ids as $bible_id ) {
			$url = add_query_arg(
				array(
					'query'   => $query,
					'mode'    => $mode,
					'limit'   => $limit,
					'preview' => 'text',
					'key'     => self::get_api_key(),
				),
				self::API_BASE . 'bible/search/' . rawurlencode( $bible_id )
			);

			$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
			if ( is_wp_error( $response ) || ! HWBL_Http_Utils::response_ok( $response ) ) {
				$last_error = 'search_failed';
				continue;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $body ) ) {
				$last_error = 'search_failed';
				continue;
			}

			$rows    = isset( $body['results'] ) && is_array( $body['results'] ) ? $body['results'] : array();
			$results = array();
			foreach ( $rows as $row ) {
				if ( empty( $row['passage'] ) ) {
					continue;
				}
				$passage = (string) $row['passage'];
				$parsed  = class_exists( 'HWBL_Books' ) ? HWBL_Books::parse_reference( $passage ) : null;
				if ( ( ! $parsed || empty( $parsed['book_id'] ) ) && method_exists( __CLASS__, 'parse_reader_reference' ) ) {
					$parsed = self::parse_reader_reference( $passage );
				}
				if ( ! $parsed || empty( $parsed['book_id'] ) ) {
					continue;
				}
				$results[] = array(
					'passage'   => $passage,
					'preview'   => isset( $row['preview'] ) ? (string) $row['preview'] : '',
					'book_id'   => (int) $parsed['book_id'],
					'chapter'   => (int) $parsed['chapter'],
					'verse'     => isset( $parsed['verse'] ) ? (int) $parsed['verse'] : 0,
					'verse_end' => isset( $parsed['verse_end'] ) ? (int) $parsed['verse_end'] : 0,
				);
			}

			if ( ! empty( $results ) ) {
				return array( 'results' => $results );
			}

			if ( null === $empty_ok ) {
				$empty_ok = array( 'results' => array() );
			}
		}

		if ( is_array( $empty_ok ) ) {
			return $empty_ok;
		}

		return array( 'error' => $last_error );
	}
}
