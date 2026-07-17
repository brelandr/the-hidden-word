<?php
/**
 * Hello AO (bible.helloao.org) translation provider — free, no API key.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HWBL_PLUGIN_DIR . 'includes/interface-translation-provider.php';

/**
 * Class HWBL_HelloAO_Provider
 */
class HWBL_HelloAO_Provider implements HWBL_Translation_Provider {

	const API_BASE = 'https://bible.helloao.org/api/';

	/**
	 * Site slug => Hello AO translation ID.
	 *
	 * @var array<string, string>
	 */
	private static $translation_ids = array(
		'bsb' => 'BSB',
		'web' => 'ENGWEBP',
		'kjv' => 'eng_kjv',
		'asv' => 'eng_asv',
		'bbe' => 'eng_bbe',
		'dby' => 'eng_dby',
		'ylt' => 'YLT',
		'gnv' => 'eng_gnv',
		'lsv' => 'eng_lsv',
	);

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'hwbl_render_copyright', array( __CLASS__, 'render_copyright' ), 10, 2 );
	}

	/**
	 * Whether Hello AO is enabled in settings.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) get_option( 'hwbl_helloao_enabled', true );
	}

	/**
	 * Hello AO translation ID for a site slug.
	 *
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public static function get_helloao_id( $translation ) {
		$translation = strtolower( (string) $translation );
		return isset( self::$translation_ids[ $translation ] ) ? self::$translation_ids[ $translation ] : null;
	}

	/**
	 * Copyright notice from cached translation metadata.
	 *
	 * @param string $html        Existing copyright HTML.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public static function render_copyright( $html, $translation ) {
		if ( $html || ! self::is_enabled() ) {
			return $html;
		}

		$ao_id = self::get_helloao_id( $translation );
		if ( ! $ao_id ) {
			return $html;
		}

		$meta = self::get_translation_metadata( $ao_id );
		if ( empty( $meta['name'] ) ) {
			return $html;
		}

		$parts = array( $meta['name'] );
		if ( ! empty( $meta['licenseUrl'] ) ) {
			$parts[] = sprintf(
				/* translators: %s: license URL */
				__( 'License: %s', 'hidden-word-bible-lessons' ),
				$meta['licenseUrl']
			);
		}

		return '<p class="hwbl-copyright">' . esc_html( implode( ' — ', $parts ) ) . '</p>';
	}

	/**
	 * Get verse text from Hello AO chapter JSON.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public function get_verse( $book_id, $chapter, $verse, $translation ) {
		if ( ! self::is_enabled() ) {
			return null;
		}

		$ao_id = self::get_helloao_id( $translation );
		if ( ! $ao_id ) {
			return null;
		}

		$usfm = HWBL_Books::get_usfm( $book_id );
		if ( ! $usfm || $chapter < 1 || $verse < 1 ) {
			return null;
		}

		$body = self::fetch_chapter_body( $ao_id, $usfm, (int) $chapter );
		if ( ! $body || empty( $body['chapter']['content'] ) ) {
			return null;
		}

		return self::extract_verse_text( $body['chapter']['content'], (int) $verse );
	}

	/**
	 * Translation slugs available for the full chapter reader.
	 *
	 * @return array<string, string>
	 */
	public static function get_reader_translations() {
		if ( ! self::is_enabled() ) {
			return array();
		}

		$provider = new self();
		return $provider->get_supported_translations();
	}

	/**
	 * Full chapter payload for the Bible reader (text + audio links).
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter number.
	 * @param string $translation Translation slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_chapter_payload( $book_id, $chapter, $translation ) {
		if ( ! self::is_enabled() ) {
			return null;
		}

		$ao_id = self::get_helloao_id( $translation );
		if ( ! $ao_id ) {
			return null;
		}

		$usfm = HWBL_Books::get_usfm( $book_id );
		if ( ! $usfm || $chapter < 1 ) {
			return null;
		}

		$body = self::fetch_chapter_body( $ao_id, $usfm, (int) $chapter );
		if ( ! $body || empty( $body['chapter']['content'] ) ) {
			return null;
		}

		$parsed = self::parse_chapter_nodes( $body['chapter']['content'] );
		if ( empty( $parsed['verses'] ) ) {
			return null;
		}

		return array(
			'verses'   => $parsed['verses'],
			'headings' => $parsed['headings'],
			'audio'    => self::get_chapter_audio_links( $body ),
		);
	}

	/**
	 * Parse Hello AO chapter nodes into headings and numbered verses.
	 *
	 * @param array<int, mixed> $content Chapter content nodes.
	 * @return array{headings:array<int,string>,verses:array<int,array{number:int,text:string}>}
	 */
	public static function parse_chapter_nodes( $content ) {
		$headings = array();
		$verses   = array();

		if ( ! is_array( $content ) ) {
			return array(
				'headings' => $headings,
				'verses'   => $verses,
			);
		}

		foreach ( $content as $node ) {
			if ( ! is_array( $node ) || empty( $node['type'] ) ) {
				continue;
			}

			if ( 'heading' === $node['type'] ) {
				$text = trim( self::flatten_content( isset( $node['content'] ) ? $node['content'] : array() ) );
				if ( '' !== $text ) {
					$headings[] = $text;
				}
				continue;
			}

			if ( 'verse' === $node['type'] && ! empty( $node['number'] ) ) {
				$text = trim( self::flatten_content( isset( $node['content'] ) ? $node['content'] : array() ) );
				if ( '' !== $text ) {
					$verses[] = array(
						'number' => (int) $node['number'],
						'text'   => $text,
					);
				}
			}
		}

		return array(
			'headings' => $headings,
			'verses'   => $verses,
		);
	}

	/**
	 * Extract narrator => MP3 URL map from a Hello AO chapter response.
	 *
	 * @param array<string, mixed> $body Decoded chapter JSON.
	 * @return array<string, string>
	 */
	public static function get_chapter_audio_links( $body ) {
		if ( empty( $body['thisChapterAudioLinks'] ) || ! is_array( $body['thisChapterAudioLinks'] ) ) {
			return array();
		}

		$audio = array();
		foreach ( $body['thisChapterAudioLinks'] as $narrator => $url ) {
			$narrator = sanitize_key( (string) $narrator );
			$url      = esc_url_raw( (string) $url );
			if ( '' !== $narrator && '' !== $url ) {
				$audio[ $narrator ] = $url;
			}
		}

		return $audio;
	}

	/**
	 * Fetch Hello AO books.json for a translation (cached 24h).
	 *
	 * @param string $ao_id Hello AO translation ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function fetch_books_catalog( $ao_id ) {
		$cache_key = 'hwbl_ao_books_' . md5( strtolower( (string) $ao_id ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$url      = self::API_BASE . rawurlencode( $ao_id ) . '/books.json';
		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$books = ( is_array( $body ) && isset( $body['books'] ) && is_array( $body['books'] ) ) ? $body['books'] : array();
		set_transient( $cache_key, $books, DAY_IN_SECONDS );

		return $books;
	}

	/**
	 * Supported translation slugs.
	 *
	 * @return array<string, string>
	 */
	public function get_supported_translations() {
		if ( ! self::is_enabled() ) {
			return array();
		}

		return array(
			'bsb' => __( 'Berean Standard Bible (BSB)', 'hidden-word-bible-lessons' ),
			'web' => __( 'World English Bible (WEB)', 'hidden-word-bible-lessons' ),
			'kjv' => __( 'King James Version (KJV)', 'hidden-word-bible-lessons' ),
			'asv' => __( 'American Standard Version (ASV)', 'hidden-word-bible-lessons' ),
			'bbe' => __( 'Bible in Basic English (BBE)', 'hidden-word-bible-lessons' ),
			'dby' => __( 'Darby Translation (DARBY)', 'hidden-word-bible-lessons' ),
			'ylt' => __( "Young's Literal Translation (YLT)", 'hidden-word-bible-lessons' ),
			'gnv' => __( 'Geneva Bible 1599 (GNV)', 'hidden-word-bible-lessons' ),
			'lsv' => __( 'Literal Standard Version (LSV)', 'hidden-word-bible-lessons' ),
		);
	}

	/**
	 * Extract verse text from a Hello AO chapter content array.
	 *
	 * @param array<int, mixed> $content Chapter content nodes.
	 * @param int               $verse   Verse number.
	 * @return string|null
	 */
	public static function extract_verse_text( $content, $verse ) {
		if ( ! is_array( $content ) ) {
			return null;
		}

		foreach ( $content as $node ) {
			if ( ! is_array( $node ) || ( isset( $node['type'] ) && 'verse' !== $node['type'] ) ) {
				continue;
			}
			if ( empty( $node['number'] ) || (int) $node['number'] !== (int) $verse ) {
				continue;
			}
			$text = self::flatten_content( isset( $node['content'] ) ? $node['content'] : array() );
			return '' !== trim( $text ) ? trim( $text ) : null;
		}

		return null;
	}

	/**
	 * Flatten nested Hello AO content nodes into plain text.
	 *
	 * @param array<int, mixed> $nodes Content nodes.
	 * @return string
	 */
	public static function flatten_content( $nodes ) {
		if ( ! is_array( $nodes ) ) {
			return is_string( $nodes ) ? $nodes : '';
		}

		$parts = array();
		foreach ( $nodes as $node ) {
			if ( is_string( $node ) ) {
				$parts[] = $node;
				continue;
			}
			if ( is_array( $node ) ) {
				if ( isset( $node['text'] ) && is_string( $node['text'] ) ) {
					$parts[] = $node['text'];
				} elseif ( isset( $node['content'] ) ) {
					$parts[] = self::flatten_content( $node['content'] );
				}
			}
		}

		return implode( '', $parts );
	}

	/**
	 * Fetch and cache a full Hello AO chapter JSON response.
	 *
	 * @param string $ao_id   Hello AO translation ID.
	 * @param string $usfm    USFM book ID.
	 * @param int    $chapter Chapter number.
	 * @return array<string, mixed>|null
	 */
	public static function fetch_chapter_body( $ao_id, $usfm, $chapter ) {
		$cache_key = 'hwbl_ao_ch_' . md5( strtolower( $ao_id . '_' . $usfm . '_' . $chapter ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && ! empty( $cached['chapter']['content'] ) ) {
			return $cached;
		}

		$url = self::API_BASE . rawurlencode( $ao_id ) . '/' . rawurlencode( $usfm ) . '/' . (int) $chapter . '.json';
		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) || ! HWBL_Http_Utils::response_ok( $response ) ) {
			return null;
		}

		$raw = wp_remote_retrieve_body( $response );
		if ( HWBL_Http_Utils::looks_like_html_error( $raw ) ) {
			return null;
		}

		$body = json_decode( $raw, true );
		if ( empty( $body['chapter']['content'] ) || ! is_array( $body['chapter']['content'] ) ) {
			return null;
		}

		set_transient( $cache_key, $body, WEEK_IN_SECONDS );

		return $body;
	}

	/**
	 * Load translation metadata from Hello AO catalog (cached 24h).
	 *
	 * @param string $ao_id Hello AO translation ID.
	 * @return array{name:string,licenseUrl:string}
	 */
	private static function get_translation_metadata( $ao_id ) {
		$cache_key = 'hwbl_ao_meta_' . md5( strtolower( $ao_id ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$out = array(
			'name'       => '',
			'licenseUrl' => '',
		);

		$catalog_key = 'hwbl_ao_catalog';
		$catalog     = get_transient( $catalog_key );
		if ( false === $catalog ) {
			$response = wp_remote_get( self::API_BASE . 'available_translations.json', array( 'timeout' => 15 ) );
			if ( ! is_wp_error( $response ) ) {
				$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
				$catalog = is_array( $decoded ) && isset( $decoded['translations'] ) ? $decoded['translations'] : array();
				set_transient( $catalog_key, $catalog, DAY_IN_SECONDS );
			} else {
				$catalog = array();
			}
		}

		if ( is_array( $catalog ) ) {
			foreach ( $catalog as $entry ) {
				if ( ! is_array( $entry ) || empty( $entry['id'] ) ) {
					continue;
				}
				if ( strcasecmp( (string) $entry['id'], (string) $ao_id ) === 0 ) {
					$out['name']       = isset( $entry['name'] ) ? (string) $entry['name'] : '';
					$out['licenseUrl'] = isset( $entry['licenseUrl'] ) ? (string) $entry['licenseUrl'] : '';
					break;
				}
			}
		}

		set_transient( $cache_key, $out, DAY_IN_SECONDS );

		return $out;
	}
}
