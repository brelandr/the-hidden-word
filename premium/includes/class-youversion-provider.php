<?php
/**
 * YouVersion Platform Bible provider (BYOK App Key).
 *
 * @package The_Hidden_Word_Premium
 * @see https://developers.youversion.com/api-usage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_YouVersion
 */
class THW_Premium_YouVersion implements HWBL_Translation_Provider {

	const API_BASE = 'https://api.youversion.com/v1/';

	/**
	 * Site slug => catalog abbreviation aliases (uppercase).
	 *
	 * @var array<string, string[]>
	 */
	private static $slug_abbreviations = array(
		'amp'   => array( 'AMP' ),
		'asv'   => array( 'ASV' ),
		'bsb'   => array( 'BSB' ),
		'csb'   => array( 'CSB' ),
		'darby' => array( 'DARBY' ),
		'esv'   => array( 'ESV' ),
		'kjv'   => array( 'KJV' ),
		'leb'   => array( 'LEB' ),
		'nasb'  => array( 'NASB', 'NASB1995', 'NASB95' ),
		'net'   => array( 'NET' ),
		'niv'   => array( 'NIV' ),
		'nkjv'  => array( 'NKJV' ),
		'nlt'   => array( 'NLT' ),
		'web'   => array( 'WEB' ),
		'ylt'   => array( 'YLT' ),
	);

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'hwbl_translation_providers', array( __CLASS__, 'register_provider' ) );
		add_filter( 'hwbl_supported_translations', array( __CLASS__, 'add_translations' ) );
		add_filter( 'hwbl_render_copyright', array( __CLASS__, 'render_copyright' ), 10, 2 );
		add_action( 'update_option_thw_youversion_app_key', array( __CLASS__, 'clear_caches' ) );
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

		$providers['youversion'] = new self();
		return $providers;
	}

	/**
	 * Whether YouVersion is licensed and has an App Key.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return THW_Premium_License::is_licensed() && self::get_app_key();
	}

	/**
	 * Get stored YouVersion App Key.
	 *
	 * @return string
	 */
	public static function get_app_key() {
		return trim( (string) get_option( 'thw_youversion_app_key', '' ) );
	}

	/**
	 * Add translations when an App Key is configured.
	 *
	 * @param array $translations Translations.
	 * @return array
	 */
	public static function add_translations( $translations ) {
		if ( ! self::is_available() ) {
			return $translations;
		}

		$labels = array(
			'amp'   => __( 'Amplified Bible (AMP)', 'hidden-word-bible-lessons' ),
			'asv'   => __( 'American Standard Version (ASV)', 'hidden-word-bible-lessons' ),
			'bsb'   => __( 'Berean Standard Bible (BSB)', 'hidden-word-bible-lessons' ),
			'csb'   => __( 'Christian Standard Bible (CSB)', 'hidden-word-bible-lessons' ),
			'darby' => __( 'Darby Translation (DARBY)', 'hidden-word-bible-lessons' ),
			'esv'   => __( 'English Standard Version (ESV)', 'hidden-word-bible-lessons' ),
			'kjv'   => __( 'King James Version (KJV)', 'hidden-word-bible-lessons' ),
			'leb'   => __( 'Lexham English Bible (LEB)', 'hidden-word-bible-lessons' ),
			'nasb'  => __( 'New American Standard Bible (NASB)', 'hidden-word-bible-lessons' ),
			'net'   => __( 'NET Bible (NET)', 'hidden-word-bible-lessons' ),
			'niv'   => __( 'New International Version (NIV)', 'hidden-word-bible-lessons' ),
			'nkjv'  => __( 'New King James Version (NKJV)', 'hidden-word-bible-lessons' ),
			'nlt'   => __( 'New Living Translation (NLT)', 'hidden-word-bible-lessons' ),
			'web'   => __( 'World English Bible (WEB)', 'hidden-word-bible-lessons' ),
			'ylt'   => __( "Young's Literal Translation (YLT)", 'hidden-word-bible-lessons' ),
		);

		return array_merge( $translations, $labels );
	}

	/**
	 * Copyright from YouVersion catalog metadata.
	 *
	 * @param string $html        Existing copyright HTML.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public static function render_copyright( $html, $translation ) {
		if ( $html || ! self::is_available() ) {
			return $html;
		}

		$meta = self::get_version_meta( $translation );
		if ( empty( $meta['copyright'] ) ) {
			return $html;
		}

		return '<p class="thw-copyright thw-youversion-copyright">' . esc_html( (string) $meta['copyright'] ) . '</p>';
	}

	/**
	 * YouVersion version ID for a site translation slug.
	 *
	 * @param string $translation Translation slug.
	 * @return int|null
	 */
	public static function get_version_id_for_translation( $translation, $app_key = null ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		if ( ! isset( self::$slug_abbreviations[ $translation ] ) ) {
			return null;
		}

		$map = self::get_slug_version_map( $app_key );
		return isset( $map[ $translation ] ) ? (int) $map[ $translation ] : null;
	}

	/**
	 * Cached metadata for a translation slug.
	 *
	 * @param string $translation Translation slug.
	 * @return array{version_id:int,copyright:string,title:string}
	 */
	public static function get_version_meta( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		$version_id  = self::get_version_id_for_translation( $translation );
		if ( ! $version_id ) {
			return array(
				'version_id' => 0,
				'copyright'  => '',
				'title'      => '',
			);
		}

		$cache_key = 'thw_yv_meta_' . md5( self::get_app_key() . '_' . $translation );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$payload = self::api_get( 'bibles/' . (int) $version_id );
		$meta    = array(
			'version_id' => $version_id,
			'copyright'  => isset( $payload['copyright'] ) ? trim( (string) $payload['copyright'] ) : '',
			'title'      => isset( $payload['title'] ) ? trim( (string) $payload['title'] ) : '',
		);

		set_transient( $cache_key, $meta, WEEK_IN_SECONDS );

		return $meta;
	}

	/**
	 * Get verse text.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public function get_verse( $book_id, $chapter, $verse, $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		$version_id  = self::get_version_id_for_translation( $translation );
		$usfm        = HWBL_Books::get_usfm( (int) $book_id );

		if ( ! $version_id || ! $usfm || $chapter < 1 || $verse < 1 ) {
			return null;
		}

		$passage_id = $usfm . '.' . (int) $chapter . '.' . (int) $verse;
		$cache_key  = 'thw_yv_v_' . md5( $translation . '_' . $passage_id );
		$cached     = get_transient( $cache_key );
		if ( false !== $cached ) {
			return is_string( $cached ) && '' !== $cached ? $cached : null;
		}

		$payload = self::fetch_passage( $version_id, $passage_id );
		$text    = self::extract_passage_text( $payload );
		if ( '' === $text ) {
			set_transient( $cache_key, '', HOUR_IN_SECONDS );
			return null;
		}

		set_transient( $cache_key, $text, WEEK_IN_SECONDS );

		return $text;
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
		$translation = strtolower( sanitize_key( (string) $translation ) );
		$version_id  = self::get_version_id_for_translation( $translation );
		$usfm        = HWBL_Books::get_usfm( (int) $book_id );

		if ( ! $version_id || ! $usfm || $chapter < 1 ) {
			return null;
		}

		$passage_id = $usfm . '.' . (int) $chapter;
		$cache_key  = 'thw_yv_ch_' . md5( $translation . '_' . $passage_id );
		$cached     = get_transient( $cache_key );
		if ( is_array( $cached ) && HWBL_Http_Utils::is_valid_chapter_payload( $cached ) ) {
			return $cached;
		}
		if ( false !== $cached ) {
			delete_transient( $cache_key );
		}

		$payload = self::fetch_passage( $version_id, $passage_id );
		$verses  = self::parse_chapter_content( self::extract_passage_text( $payload ) );
		if ( empty( $verses ) ) {
			$verses = self::fetch_chapter_verses( $version_id, $usfm, (int) $chapter );
		}

		if ( empty( $verses ) ) {
			return null;
		}

		$result = array(
			'verses'   => $verses,
			'headings' => array(),
			'audio'    => array(),
		);

		set_transient( $cache_key, $result, WEEK_IN_SECONDS );

		return $result;
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
	 * Whether this translation slug returns content for the current App Key.
	 *
	 * @param string $translation Translation slug.
	 * @return bool
	 */
	public static function is_translation_accessible( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		if ( ! self::is_available() || ! isset( self::$slug_abbreviations[ $translation ] ) ) {
			return false;
		}

		$version_id = self::get_version_id_for_translation( $translation );
		if ( ! $version_id ) {
			return false;
		}

		$cache_key = 'thw_yv_acc_' . md5( self::get_app_key() . '_' . $translation );
		$cached    = get_transient( $cache_key );
		if ( 'yes' === $cached ) {
			return true;
		}
		if ( 'no' === $cached ) {
			return false;
		}

		$payload = self::fetch_passage( $version_id, 'JHN.3.16' );
		$ok      = '' !== self::extract_passage_text( $payload );

		set_transient( $cache_key, $ok ? 'yes' : 'no', WEEK_IN_SECONDS );

		return $ok;
	}

	/**
	 * Split chapter passage text into verse rows.
	 *
	 * @param string $raw Raw passage text.
	 * @return array<int, array{number:int,text:string}>
	 */
	public static function parse_chapter_content( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw || HWBL_Http_Utils::looks_like_html_error( $raw ) ) {
			return array();
		}

		$verses = array();
		$lines  = preg_split( '/\r\n|\r|\n/', $raw );
		if ( is_array( $lines ) && count( $lines ) > 1 ) {
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				if ( preg_match( '/^(\d+)\s+(.+)$/', $line, $matches ) ) {
					$verses[] = array(
						'number' => (int) $matches[1],
						'text'   => trim( $matches[2] ),
					);
				} elseif ( ! empty( $verses ) ) {
					$last                     = count( $verses ) - 1;
					$verses[ $last ]['text'] .= ' ' . $line;
				}
			}
			if ( ! empty( $verses ) ) {
				return $verses;
			}
		}

		if ( preg_match_all( '/(?:^|\s)(\d+)\s+([^0-9]+?)(?=(?:\s\d+\s)|$)/s', $raw, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$text = trim( $match[2] );
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
	 * Extract plain text from a passage API payload.
	 *
	 * @param array<string, mixed>|null $payload API payload.
	 * @return string
	 */
	public static function extract_passage_text( $payload ) {
		if ( ! is_array( $payload ) || empty( $payload['content'] ) ) {
			return '';
		}

		$content = (string) $payload['content'];
		if ( preg_match( '/<[a-z][\s\S]*>/i', $content ) ) {
			$content = wp_strip_all_tags( $content );
		}

		return HWBL_Http_Utils::sanitize_bible_text( trim( preg_replace( '/\s+/', ' ', $content ) ) );
	}

	/**
	 * Fetch chapter verses one-by-one when passage text is not parseable.
	 *
	 * @param int    $version_id YouVersion version ID.
	 * @param string $usfm       USFM book code.
	 * @param int    $chapter    Chapter number.
	 * @return array<int, array{number:int,text:string}>
	 */
	private static function fetch_chapter_verses( $version_id, $usfm, $chapter ) {
		$url_path = 'bibles/' . (int) $version_id . '/books/' . rawurlencode( $usfm ) . '/chapters/' . (int) $chapter . '/verses';
		$payload  = self::api_get( $url_path, array( 'page_size' => 100 ) );
		$items    = self::extract_collection_items( $payload );

		if ( empty( $items ) ) {
			return array();
		}

		$verses = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$verse_num = 0;
			if ( isset( $item['verse_number'] ) ) {
				$verse_num = (int) $item['verse_number'];
			} elseif ( isset( $item['number'] ) ) {
				$verse_num = (int) $item['number'];
			} elseif ( isset( $item['id'] ) ) {
				$verse_num = (int) $item['id'];
			}

			if ( $verse_num < 1 ) {
				continue;
			}

			$text = '';
			if ( ! empty( $item['content'] ) ) {
				$text = self::extract_passage_text( $item );
			} else {
				$verse_payload = self::fetch_passage( (int) $version_id, $usfm . '.' . (int) $chapter . '.' . $verse_num );
				$text          = self::extract_passage_text( $verse_payload );
			}

			if ( '' !== $text ) {
				$verses[] = array(
					'number' => $verse_num,
					'text'   => $text,
				);
			}
		}

		return $verses;
	}

	/**
	 * Fetch a passage from the YouVersion API.
	 *
	 * @param int    $version_id YouVersion version ID.
	 * @param string $passage_id Passage ID (e.g. JHN.3.16).
	 * @return array<string, mixed>|null
	 */
	public static function fetch_passage( $version_id, $passage_id, $app_key = null ) {
		$passage_id = trim( (string) $passage_id );
		if ( $version_id < 1 || '' === $passage_id ) {
			return null;
		}

		return self::api_get(
			'bibles/' . (int) $version_id . '/passages/' . rawurlencode( $passage_id ),
			array(
				'format' => 'text',
			),
			$app_key
		);
	}

	/**
	 * Map site slugs to YouVersion version IDs from the catalog.
	 *
	 * @param string|null $app_key Optional App Key override (for admin tests).
	 * @return array<string, int>
	 */
	public static function get_slug_version_map( $app_key = null ) {
		$use_saved = null === $app_key;
		$app_key   = $use_saved ? self::get_app_key() : trim( (string) $app_key );
		if ( '' === $app_key ) {
			return array();
		}

		$cache_key = 'thw_yv_map_' . md5( $app_key );
		$cached    = $use_saved ? get_transient( $cache_key ) : false;
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$map    = array();
		$bibles = self::fetch_bible_catalog( $app_key );
		foreach ( self::$slug_abbreviations as $slug => $abbrevs ) {
			$version_id = self::match_catalog_version( $bibles, $abbrevs );
			if ( $version_id ) {
				$map[ $slug ] = $version_id;
			}
		}

		if ( $use_saved ) {
			set_transient( $cache_key, $map, DAY_IN_SECONDS );
		}

		return $map;
	}

	/**
	 * Fetch all licensed Bibles for the App Key.
	 *
	 * @param string|null $app_key Optional App Key override.
	 * @return array<int, array<string, mixed>>
	 */
	public static function fetch_bible_catalog( $app_key = null ) {
		$bibles     = array();
		$page_token = '';

		do {
			$query = array(
				'language_ranges' => 'en',
				'page_size'       => 100,
			);
			if ( '' !== $page_token ) {
				$query['page_token'] = $page_token;
			}

			$payload = self::api_get( 'bibles', $query, $app_key );
			$items   = self::extract_collection_items( $payload );
			if ( ! empty( $items ) ) {
				$bibles = array_merge( $bibles, $items );
			}

			$page_token = is_array( $payload ) && isset( $payload['next_page_token'] ) ? (string) $payload['next_page_token'] : '';
		} while ( '' !== $page_token && count( $bibles ) < 500 );

		return $bibles;
	}

	/**
	 * Find a catalog row matching abbreviation aliases.
	 *
	 * @param array<int, array<string, mixed>> $bibles  Catalog rows.
	 * @param string[]                         $abbrevs Abbreviation aliases.
	 * @return int
	 */
	public static function match_catalog_version( $bibles, $abbrevs ) {
		$targets = array_map( 'strtoupper', $abbrevs );

		foreach ( $bibles as $row ) {
			if ( ! is_array( $row ) || empty( $row['id'] ) ) {
				continue;
			}

			$candidates = array();
			foreach ( array( 'abbreviation', 'local_abbreviation', 'localAbbreviation', 'short_name', 'shortName' ) as $field ) {
				if ( ! empty( $row[ $field ] ) ) {
					$candidates[] = strtoupper( trim( (string) $row[ $field ] ) );
				}
			}

			foreach ( $targets as $target ) {
				if ( in_array( $target, $candidates, true ) ) {
					return (int) $row['id'];
				}
			}
		}

		return 0;
	}

	/**
	 * Perform a GET request against the YouVersion API.
	 *
	 * @param string               $path  Path relative to API base (no leading slash).
	 * @param array<string, mixed> $query Optional query args.
	 * @return array<string, mixed>|null
	 */
	public static function api_get( $path, $query = array(), $app_key = null ) {
		$app_key = null !== $app_key ? trim( (string) $app_key ) : self::get_app_key();
		if ( '' === $app_key ) {
			return null;
		}

		$url = self::API_BASE . ltrim( (string) $path, '/' );
		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'X-YVP-App-Key' => $app_key,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) || ! HWBL_Http_Utils::response_ok( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return null;
		}

		return self::unwrap_payload( $body );
	}

	/**
	 * Normalize API JSON (top-level or nested under data).
	 *
	 * @param array<string, mixed> $body Raw JSON.
	 * @return array<string, mixed>
	 */
	public static function unwrap_payload( $body ) {
		if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
			$data = $body['data'];
			if ( self::is_assoc( $data ) && ( isset( $data['content'] ) || isset( $data['id'] ) || isset( $data['title'] ) ) ) {
				return $data;
			}
			if ( isset( $body['next_page_token'] ) && ! isset( $data['next_page_token'] ) ) {
				$data['next_page_token'] = $body['next_page_token'];
			}
			return $data;
		}

		return $body;
	}

	/**
	 * Extract list items from a collection payload.
	 *
	 * @param array<string, mixed>|null $payload API payload.
	 * @return array<int, array<string, mixed>>
	 */
	public static function extract_collection_items( $payload ) {
		if ( ! is_array( $payload ) ) {
			return array();
		}

		if ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
			return array_values( array_filter( $payload['items'], 'is_array' ) );
		}

		if ( self::is_list_array( $payload ) ) {
			return array_values( array_filter( $payload, 'is_array' ) );
		}

		if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
			return self::extract_collection_items( $payload['data'] );
		}

		return array();
	}

	/**
	 * Clear cached catalog, accessibility, and metadata entries.
	 */
	public static function clear_caches() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk transient purge by prefix.
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_thw_yv_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_thw_yv_' ) . '%'
			)
		);
	}

	/**
	 * Fetch a shareable Verse of the Day image URL for a passage ID.
	 *
	 * @param string $passage_id USFM passage (e.g. EPH.6.13).
	 * @param int    $width      Preferred rendition width in pixels.
	 * @return string
	 */
	public static function fetch_image_for_passage( $passage_id, $width = 640 ) {
		$passage_id = strtoupper( trim( (string) $passage_id ) );
		$width      = max( 152, min( 1280, (int) $width ) );
		if ( '' === $passage_id || ! self::is_available() ) {
			return '';
		}

		$cache_key = 'thw_yv_votd_image_' . md5( $passage_id . '_' . $width );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$queries = array(
			array( 'usfm' => $passage_id ),
			array( 'passage_id' => $passage_id ),
		);

		foreach ( $queries as $query ) {
			$payload = self::api_get( 'images', $query );
			$url     = self::extract_votd_image_url_from_payload( $payload, $passage_id, $width );
			if ( '' !== $url ) {
				set_transient( $cache_key, $url, DAY_IN_SECONDS );
				return $url;
			}
		}

		return '';
	}

	/**
	 * Pick a VOTD image URL from a YouVersion images API payload.
	 *
	 * @param array<string, mixed>|null $payload    API payload.
	 * @param string                    $passage_id Expected USFM passage ID.
	 * @param int                       $width      Preferred width.
	 * @return string
	 */
	public static function extract_votd_image_url_from_payload( $payload, $passage_id, $width = 640 ) {
		$passage_id = strtoupper( trim( (string) $passage_id ) );
		$width      = max( 152, min( 1280, (int) $width ) );
		$items      = self::extract_collection_items( $payload );
		if ( empty( $items ) && is_array( $payload ) && isset( $payload['renditions'] ) ) {
			$items = array( $payload );
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( $passage_id && ! self::image_item_matches_passage( $item, $passage_id ) ) {
				continue;
			}

			$url = self::pick_image_rendition_url( $item, $width );
			if ( '' !== $url ) {
				return THW_Premium_Verse_Of_The_Day::normalize_votd_image_url( $url );
			}
		}

		if ( $passage_id && ! empty( $items[0] ) && is_array( $items[0] ) ) {
			$url = self::pick_image_rendition_url( $items[0], $width );
			if ( '' !== $url ) {
				return THW_Premium_Verse_Of_The_Day::normalize_votd_image_url( $url );
			}
		}

		return '';
	}

	/**
	 * Whether an image catalog item matches a passage ID.
	 *
	 * @param array<string, mixed> $item       Image item.
	 * @param string               $passage_id Passage ID.
	 * @return bool
	 */
	private static function image_item_matches_passage( $item, $passage_id ) {
		if ( empty( $item['usfm'] ) ) {
			return true;
		}

		$needles = array( strtoupper( trim( (string) $passage_id ) ) );
		foreach ( (array) $item['usfm'] as $usfm ) {
			$usfm = strtoupper( trim( (string) $usfm ) );
			if ( in_array( $usfm, $needles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Choose the best rendition URL for a requested width.
	 *
	 * @param array<string, mixed> $item  Image item.
	 * @param int                  $width Preferred width.
	 * @return string
	 */
	private static function pick_image_rendition_url( $item, $width ) {
		if ( empty( $item['renditions'] ) || ! is_array( $item['renditions'] ) ) {
			return '';
		}

		$best_url   = '';
		$best_delta = PHP_INT_MAX;
		foreach ( $item['renditions'] as $rendition ) {
			if ( ! is_array( $rendition ) || empty( $rendition['url'] ) ) {
				continue;
			}

			$rendition_width = isset( $rendition['width'] ) ? (int) $rendition['width'] : 0;
			$delta           = $rendition_width > 0 ? abs( $rendition_width - $width ) : 9999;
			if ( $delta < $best_delta ) {
				$best_delta = $delta;
				$best_url   = (string) $rendition['url'];
			}
		}

		return $best_url;
	}

	/**
	 * @param array<mixed> $array Array to inspect.
	 * @return bool
	 */
	private static function is_list_array( $array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return false;
		}
		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}

	/**
	 * @param array<mixed> $array Array to inspect.
	 * @return bool
	 */
	private static function is_assoc( $array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return false;
		}
		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}
}
