<?php
/**
 * API.Bible translation provider (BYOK).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_API_Bible
 */
class THW_Premium_API_Bible implements HWBL_Translation_Provider {

	const API_BASE = 'https://api.scripture.api.bible/v1/';

	/**
	 * Bible version IDs for API.Bible.
	 *
	 * @var array<string, string>
	 */
	private static $bible_ids = array(
		'esv'  => 'de4e12af7f28f599-02',
		'nlt'  => '06125adad2d5898a-01',
		'nasb' => 'a761ca71e0b3ddcf-01',
		'csb'  => 'a556c5305ee15c3f-01',
		'nkjv' => 'f421fe2617693084-01',
		'amp'  => 'c77d98e28e7a3ab3-02',
		'net'  => '7c464d81b8b5c8c2-01',
		'niv'  => '78a9f6124f344018-01',
	);

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'hwbl_translation_providers', array( __CLASS__, 'register_provider' ) );
		add_filter( 'hwbl_supported_translations', array( __CLASS__, 'add_translations' ) );
		add_filter( 'hwbl_get_verse_text', array( __CLASS__, 'filter_verse_text' ), 10, 5 );
		add_filter( 'hwbl_render_copyright', array( __CLASS__, 'render_copyright' ), 10, 2 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'update_option_thw_api_bible_key', array( __CLASS__, 'clear_accessibility_cache' ) );
		add_action( 'update_option_thw_api_bible_key', array( __CLASS__, 'clear_chapter_cache' ) );
		add_action( 'init', array( __CLASS__, 'maybe_clear_caches_on_upgrade' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_fums' ) );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_render_fums_attribution' ) );
	}

	/**
	 * API.Bible request headers including FUMS version.
	 *
	 * @return array<string, string>
	 */
	private static function api_request_headers() {
		$headers = array(
			'api-key' => (string) self::get_api_key(),
		);

		if ( self::get_api_key() ) {
			$headers['fums-version'] = '3';
		}

		return $headers;
	}

	/**
	 * Enqueue API.Bible FUMS script when licensed translations may display.
	 */
	public static function maybe_enqueue_fums() {
		if ( ! self::get_api_key() || is_admin() ) {
			return;
		}

		$init_ver = defined( 'HWBL_VERSION' ) ? HWBL_VERSION : ( defined( 'THW_PREMIUM_VERSION' ) ? THW_PREMIUM_VERSION : '1.0.0' );
		$fums_ver = defined( 'THW_PREMIUM_VERSION' ) ? THW_PREMIUM_VERSION : '1.0.0';

		wp_register_script(
			'hwbl-api-bible-fums-init',
			HWBL_PLUGIN_URL . 'assets/js/hwbl-api-bible-fums-init.js',
			array(),
			$init_ver,
			true
		);
		wp_register_script(
			'hwbl-api-bible-fums',
			'https://api.scripture.api.bible/fums/fumsv2.min.js',
			array( 'hwbl-api-bible-fums-init' ),
			$fums_ver,
			true
		);
		wp_enqueue_script( 'hwbl-api-bible-fums-init' );
		wp_enqueue_script( 'hwbl-api-bible-fums' );
		wp_script_add_data( 'hwbl-api-bible-fums', 'async', true );
	}

	/**
	 * Render API.Bible attribution when FUMS may run.
	 */
	public static function maybe_render_fums_attribution() {
		if ( ! self::get_api_key() || is_admin() ) {
			return;
		}

		echo '<p class="hwbl-api-bible-attribution" style="font-size:0.85em;text-align:center;margin:1rem 0;">';
		echo esc_html__( 'Bible translations provided by', 'hidden-word-bible-lessons' ) . ' ';
		echo '<a href="https://api.bible/" target="_blank" rel="noopener">API.Bible</a>';
		echo '</p>';
	}

	/**
	 * Register this provider.
	 *
	 * @param array $providers Providers.
	 * @return array
	 */
	public static function register_provider( $providers ) {
		$providers['api_bible'] = new self();
		return $providers;
	}

	/**
	 * Add API translations to supported list when key configured.
	 *
	 * @param array $translations Translations.
	 * @return array
	 */
	public static function add_translations( $translations ) {
		if ( ! self::get_api_key() ) {
			return $translations;
		}

		$api_translations = array(
			'esv'  => __( 'English Standard Version (ESV)', 'hidden-word-bible-lessons' ),
			'nlt'  => __( 'New Living Translation (NLT)', 'hidden-word-bible-lessons' ),
			'niv'  => __( 'New International Version (NIV)', 'hidden-word-bible-lessons' ),
			'nasb' => __( 'New American Standard Bible (NASB)', 'hidden-word-bible-lessons' ),
			'csb'  => __( 'Christian Standard Bible (CSB)', 'hidden-word-bible-lessons' ),
			'nkjv' => __( 'New King James Version (NKJV)', 'hidden-word-bible-lessons' ),
			'amp'  => __( 'Amplified Bible (AMP)', 'hidden-word-bible-lessons' ),
			'net'  => __( 'NET Bible (NET)', 'hidden-word-bible-lessons' ),
		);

		return array_merge( $translations, $api_translations );
	}

	/**
	 * Copyright notices for API.Bible translations.
	 *
	 * @param string $html        Existing copyright HTML.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public static function render_copyright( $html, $translation ) {
		if ( $html ) {
			return $html;
		}

		$notices = array(
			'esv'  => __( 'Scripture quotations marked ESV are from The Holy Bible, English Standard Version® (ESV®), copyright © 2001 by Crossway, a publishing ministry of Good News Publishers. Used by permission. All rights reserved.', 'hidden-word-bible-lessons' ),
			'nlt'  => __( 'Scripture quotations marked NLT are taken from the Holy Bible, New Living Translation, copyright © 1996, 2004, 2015 by Tyndale House Foundation. Used by permission of Tyndale House Publishers. All rights reserved.', 'hidden-word-bible-lessons' ),
			'niv'  => __( 'Scripture quotations marked NIV are taken from THE HOLY BIBLE, NEW INTERNATIONAL VERSION®, NIV® Copyright © 1973, 1978, 1984, 2011 by Biblica, Inc.® Used by permission. All rights reserved worldwide.', 'hidden-word-bible-lessons' ),
			'nasb' => __( 'Scripture quotations marked NASB are taken from the New American Standard Bible®, copyright © 1960, 1971, 1977, 1995, 2020 by The Lockman Foundation. Used by permission. All rights reserved.', 'hidden-word-bible-lessons' ),
			'csb'  => __( 'Scripture quotations marked CSB are taken from the Christian Standard Bible®, copyright © 2017 by Holman Bible Publishers. Used by permission. All rights reserved.', 'hidden-word-bible-lessons' ),
			'nkjv' => __( 'Scripture quotations marked NKJV are taken from the New King James Version®. Copyright © 1982 by Thomas Nelson. Used by permission. All rights reserved.', 'hidden-word-bible-lessons' ),
			'amp'  => __( 'Scripture quotations marked AMP are taken from the Amplified® Bible, copyright © 2015 by The Lockman Foundation, La Habra, CA 90631. All rights reserved.', 'hidden-word-bible-lessons' ),
			'net'  => __( 'Scripture quotations marked NET are taken from the NET Bible® copyright © 1996-2017 by Biblical Studies Press, L.L.C. Used by permission. All rights reserved.', 'hidden-word-bible-lessons' ),
		);

		if ( ! isset( $notices[ $translation ] ) ) {
			return $html;
		}

		return '<p class="thw-copyright">' . esc_html( $notices[ $translation ] ) . '</p>';
	}

	/**
	 * Filter verse text through API when bundled text unavailable.
	 *
	 * @param string|null $text        Current text.
	 * @param int         $book_id     Book ID.
	 * @param int         $chapter     Chapter.
	 * @param int         $verse       Verse.
	 * @param string      $translation Translation.
	 * @return string|null
	 */
	public static function filter_verse_text( $text, $book_id, $chapter, $verse, $translation ) {
		if ( $text ) {
			return $text;
		}

		$instance = new self();
		return $instance->get_verse( $book_id, $chapter, $verse, $translation );
	}

	/**
	 * Get stored API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return get_option( 'thw_api_bible_key', '' );
	}

	/**
	 * API.Bible version ID for a site translation slug.
	 *
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public static function get_bible_id_for_translation( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		return isset( self::$bible_ids[ $translation ] ) ? self::$bible_ids[ $translation ] : null;
	}

	/**
	 * Get verse from API.Bible with transient cache.
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

		$cache_key = 'thw_ab_' . md5( $translation . '_' . $book_id . '_' . $chapter . '_' . $verse );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$book_abbr = self::book_id_to_abbr( $book_id );
		if ( ! $book_abbr ) {
			return null;
		}

		$bible_id  = self::$bible_ids[ $translation ];
		$reference = $book_abbr . '.' . $chapter . '.' . $verse;
		$url       = self::API_BASE . 'bibles/' . $bible_id . '/verses/' . $reference;

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => self::api_request_headers(),
			)
		);

		if ( ! HWBL_Http_Utils::response_ok( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['data']['content'] ) ) {
			return null;
		}

		$text = HWBL_Http_Utils::sanitize_bible_text( wp_strip_all_tags( $body['data']['content'] ) );
		if ( '' === $text ) {
			return null;
		}
		set_transient( $cache_key, $text, WEEK_IN_SECONDS );

		return $text;
	}

	/**
	 * Get supported translations from API provider.
	 *
	 * @return array<string, string>
	 */
	public function get_supported_translations() {
		return self::add_translations( array() );
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

		$book_abbr = self::book_id_to_abbr( $book_id );
		if ( ! $book_abbr ) {
			return null;
		}

		$reference = $book_abbr . '.' . (int) $chapter;
		$cache_key = 'thw_ab_ch_' . md5( $translation . '_' . $reference );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && HWBL_Http_Utils::is_valid_chapter_payload( $cached ) ) {
			return $cached;
		}
		if ( false !== $cached ) {
			delete_transient( $cache_key );
		}

		$bible_id = self::$bible_ids[ $translation ];
		$verses   = self::fetch_chapter_verses( $api_key, $bible_id, $reference );
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
	 * Fetch and parse chapter verses from API.Bible.
	 *
	 * @param string $api_key   API key.
	 * @param string $bible_id  Bible version ID.
	 * @param string $reference Chapter reference (e.g. GEN.1).
	 * @return array<int, array{number:int,text:string}>
	 */
	public static function fetch_chapter_verses( $api_key, $bible_id, $reference ) {
		$requests = array(
			array(
				'content-type'          => 'text',
				'include-verse-numbers' => 'true',
				'include-titles'        => 'false',
				'include-notes'         => 'false',
			),
			array(
				'content-type'          => 'html',
				'include-verse-spans'   => 'true',
				'include-verse-numbers' => 'true',
				'include-titles'        => 'false',
				'include-notes'         => 'false',
			),
		);

		foreach ( $requests as $query_args ) {
			$url      = add_query_arg( $query_args, self::API_BASE . 'bibles/' . $bible_id . '/chapters/' . $reference );
			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 15,
					'headers' => self::api_request_headers(),
				)
			);

			if ( is_wp_error( $response ) || ! HWBL_Http_Utils::response_ok( $response ) ) {
				continue;
			}

			$raw = wp_remote_retrieve_body( $response );
			if ( HWBL_Http_Utils::looks_like_html_error( $raw ) ) {
				continue;
			}

			$body = json_decode( $raw, true );
			if ( empty( $body['data']['content'] ) ) {
				continue;
			}

			$content = (string) $body['data']['content'];
			$verses  = 'text' === $query_args['content-type']
				? self::parse_chapter_text( $content )
				: self::parse_chapter_html( $content );

			if ( ! empty( $verses ) ) {
				return $verses;
			}
		}

		return array();
	}

	/**
	 * Build chapter request query args for diagnostics and reader fetches.
	 *
	 * @param string $content_type html|text.
	 * @return array<string, string>
	 */
	public static function get_chapter_query_args( $content_type = 'text' ) {
		if ( 'html' === $content_type ) {
			return array(
				'content-type'          => 'html',
				'include-verse-spans'   => 'true',
				'include-verse-numbers' => 'true',
				'include-titles'        => 'false',
				'include-notes'         => 'false',
			);
		}

		return array(
			'content-type'          => 'text',
			'include-verse-numbers' => 'true',
			'include-titles'        => 'false',
			'include-notes'         => 'false',
		);
	}

	/**
	 * Parse API.Bible plain-text chapter content into numbered verses.
	 *
	 * @param string $raw Chapter text content.
	 * @return array<int, array{number:int,text:string}>
	 */
	public static function parse_chapter_text( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw || HWBL_Http_Utils::looks_like_html_error( $raw ) ) {
			return array();
		}

		if ( preg_match( '/\[\d+\]/', $raw ) ) {
			$verses = self::parse_bracketed_chapter_text( $raw );
			if ( ! empty( $verses ) ) {
				return $verses;
			}
		}

		$lines  = preg_split( '/\r\n|\r|\n/', $raw );
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
	 * Parse API.Bible text chapters that use inline [1] [2] verse markers (common for NLT).
	 *
	 * @param string $raw Chapter text content.
	 * @return array<int, array{number:int,text:string}>
	 */
	public static function parse_bracketed_chapter_text( $raw ) {
		$raw     = trim( (string) $raw );
		$verses  = array();
		$matches = array();

		if ( ! preg_match_all( '/\[(\d+)\]\s*(.*?)(?=\[\d+\]|$)/s', $raw, $matches, PREG_SET_ORDER ) ) {
			return $verses;
		}

		foreach ( $matches as $match ) {
			$text = trim( preg_replace( '/\s+/', ' ', (string) $match[2] ) );
			if ( '' === $text || self::looks_like_citation_line( $text ) ) {
				continue;
			}

			$verses[] = array(
				'number' => (int) $match[1],
				'text'   => $text,
			);
		}

		return $verses;
	}

	/**
	 * Parse API.Bible chapter HTML into numbered verses.
	 *
	 * @param string $html Chapter HTML content.
	 * @return array<int, array{number:int,text:string}>
	 */
	public static function parse_chapter_html( $html ) {
		$verses = array();
		$html   = (string) $html;

		if ( HWBL_Http_Utils::looks_like_html_error( $html ) ) {
			return $verses;
		}

		if ( preg_match_all( '/data-number="(\d+)"[^>]*>(.*?)<\/span>/si', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$text = trim( wp_strip_all_tags( $match[2] ) );
				if ( '' !== $text ) {
					$verses[] = array(
						'number' => (int) $match[1],
						'text'   => $text,
					);
				}
			}
			if ( ! empty( $verses ) ) {
				return $verses;
			}
		}

		if ( preg_match_all( '/<span[^>]*class="[^"]*\bv\b[^"]*"[^>]*>\s*(\d+)\s*<\/span>(.*?)(?=<span[^>]*class="[^"]*\bv\b|$)/si', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$text = trim( wp_strip_all_tags( $match[2] ) );
				if ( '' !== $text ) {
					$verses[] = array(
						'number' => (int) $match[1],
						'text'   => $text,
					);
				}
			}
		}

		return $verses;
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
	 * Map book ID to API.Bible abbreviation.
	 *
	 * @param int $book_id Book ID.
	 * @return string|null
	 */
	private static function book_id_to_abbr( $book_id ) {
		$abbrs = array(
			1 => 'GEN', 2 => 'EXO', 3 => 'LEV', 4 => 'NUM', 5 => 'DEU',
			6 => 'JOS', 7 => 'JDG', 8 => 'RUT', 9 => '1SA', 10 => '2SA',
			11 => '1KI', 12 => '2KI', 13 => '1CH', 14 => '2CH', 15 => 'EZR',
			16 => 'NEH', 17 => 'EST', 18 => 'JOB', 19 => 'PSA', 20 => 'PRO',
			21 => 'ECC', 22 => 'SNG', 23 => 'ISA', 24 => 'JER', 25 => 'LAM',
			26 => 'EZK', 27 => 'DAN', 28 => 'HOS', 29 => 'JOL', 30 => 'AMO',
			31 => 'OBA', 32 => 'JON', 33 => 'MIC', 34 => 'NAM', 35 => 'HAB',
			36 => 'ZEP', 37 => 'HAG', 38 => 'ZEC', 39 => 'MAL', 40 => 'MAT',
			41 => 'MRK', 42 => 'LUK', 43 => 'JHN', 44 => 'ACT', 45 => 'ROM',
			46 => '1CO', 47 => '2CO', 48 => 'GAL', 49 => 'EPH', 50 => 'PHP',
			51 => 'COL', 52 => '1TH', 53 => '2TH', 54 => '1TI', 55 => '2TI',
			56 => 'TIT', 57 => 'PHM', 58 => 'HEB', 59 => 'JAS', 60 => '1PE',
			61 => '2PE', 62 => '1JN', 63 => '2JN', 64 => '3JN', 65 => 'JUD',
			66 => 'REV',
		);

		return isset( $abbrs[ $book_id ] ) ? $abbrs[ $book_id ] : null;
	}

	/**
	 * Register REST routes for lesson verse translation switching.
	 *
	 * Public read-only GET that returns scripture text for a lesson ID using
	 * configured Bible providers (cached at the provider layer).
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'thw/v1',
			'/verse',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get_verse' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'lesson_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'translation' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * REST callback for verse fetch.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_get_verse( $request ) {
		$lesson_id   = (int) $request['lesson_id'];
		$translation = sanitize_text_field( $request['translation'] );
		$lesson      = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );

		if ( empty( $lesson ) ) {
			return new WP_REST_Response(
				array(
					'error' => __( 'Lesson not found.', 'hidden-word-bible-lessons' ),
					'code'  => 'hwbl_not_found',
				),
				404
			);
		}

		$trans_svc   = HWBL_Translation_Service::instance();
		$book_id     = isset( $lesson['book_id'] ) ? (int) $lesson['book_id'] : 0;
		$chapter     = isset( $lesson['chapter'] ) ? (int) $lesson['chapter'] : 0;
		$verse_start = isset( $lesson['verse_start'] ) ? (int) $lesson['verse_start'] : 0;
		$verse_end   = isset( $lesson['verse_end'] ) ? (int) $lesson['verse_end'] : 0;
		if ( $verse_end < 1 ) {
			$verse_end = $verse_start;
		}

		$text             = '';
		$resolved_slug    = $translation;
		// Prefer full verse range from coordinates so multi-verse lessons are complete.
		if ( $book_id && $chapter && $verse_start && class_exists( 'HWBL_Verse_Memorize' ) ) {
			$resolved      = HWBL_Verse_Memorize::resolve_verse_text(
				$book_id,
				$chapter,
				$verse_start,
				$verse_end,
				$translation
			);
			$text          = isset( $resolved['text'] ) ? (string) $resolved['text'] : '';
			$resolved_slug = ! empty( $resolved['translation'] ) ? (string) $resolved['translation'] : $translation;
		}

		if ( '' === trim( $text ) && ! empty( $lesson['week_number'] ) ) {
			$text = (string) $trans_svc->get_verse_by_week( (int) $lesson['week_number'], $translation );
			$resolved_slug = $translation;
		}

		if ( '' === trim( $text ) && $book_id && $chapter && $verse_start ) {
			$text = (string) $trans_svc->get_verse_text( $book_id, $chapter, $verse_start, $translation );
			$resolved_slug = $translation;
		}

		return new WP_REST_Response(
			array(
				'text'        => $text,
				'translation' => $resolved_slug,
				'copyright'   => $trans_svc->render_copyright( $resolved_slug ),
			)
		);
	}

	/**
	 * Clear cached reader accessibility probes.
	 */
	public static function clear_accessibility_cache() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk transient purge by prefix.
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_thw_ab_acc_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_thw_ab_acc_' ) . '%'
			)
		);
	}

	/**
	 * Clear cached API.Bible chapter payloads.
	 */
	public static function clear_chapter_cache() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk transient purge by prefix.
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_thw_ab_ch_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_thw_ab_ch_' ) . '%'
			)
		);
	}

	/**
	 * Clear API.Bible caches after plugin upgrade (e.g. bible ID or parser changes).
	 */
	public static function maybe_clear_caches_on_upgrade() {
		$current = defined( 'THW_PREMIUM_VERSION' ) ? THW_PREMIUM_VERSION : '1';
		$stored  = get_option( 'thw_ab_cache_version', '' );
		if ( $stored === $current ) {
			return;
		}

		self::clear_accessibility_cache();
		self::clear_chapter_cache();
		update_option( 'thw_ab_cache_version', $current, false );
	}

	/**
	 * Whether this translation slug returns content from API.Bible for the current key.
	 *
	 * @param string $translation Translation slug.
	 * @return bool
	 */
	public static function is_translation_accessible( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		$api_key     = self::get_api_key();
		if ( ! $api_key || ! isset( self::$bible_ids[ $translation ] ) ) {
			return false;
		}

		$bible_id  = self::$bible_ids[ $translation ];
		$cache_key = 'thw_ab_acc_' . md5( $api_key . '_' . $translation . '_' . $bible_id );
		$cached    = get_transient( $cache_key );
		if ( 'yes' === $cached ) {
			return true;
		}
		if ( 'no' === $cached ) {
			return false;
		}

		$url       = self::API_BASE . 'bibles/' . $bible_id . '/verses/JHN.3.16';
		$response  = wp_remote_get(
			$url,
			array(
				'timeout' => 12,
				'headers' => self::api_request_headers(),
			)
		);

		$ok = false;
		if ( ! is_wp_error( $response ) && HWBL_Http_Utils::response_ok( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$ok   = ! empty( $body['data']['content'] );
		}

		set_transient( $cache_key, $ok ? 'yes' : 'no', WEEK_IN_SECONDS );

		return $ok;
	}
}
