<?php
/**
 * Public-domain Bible text search fallback.
 *
 * Used when Biblia.com search is unavailable or returns no hits.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Bible_Text_Search
 */
class HWBL_Bible_Text_Search {

	const API_BASE = 'https://dailybible.ca/api/search';

	/**
	 * Site slug => dailybible.ca translation id.
	 *
	 * @var array<string, string>
	 */
	private static $translation_map = array(
		'kjv'   => 'kjv',
		'asv'   => 'asv',
		'darby' => 'darby',
		'dby'   => 'darby',
		'web'   => 'web',
		'bbe'   => 'bbe',
		'ylt'   => 'ylt',
	);

	/**
	 * Whether this fallback can run.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return true;
	}

	/**
	 * Normalize a user search query for remote APIs.
	 *
	 * @param string $query Raw query.
	 * @return string
	 */
	public static function normalize_query( $query ) {
		$query = trim( (string) $query );
		$query = preg_replace( '/^[\s\'"“”‘’]+|[\s\'"“”‘’]+$/u', '', $query );
		$query = preg_replace( '/[\.!?…]+$/u', '', $query );
		$query = trim( (string) $query );
		return $query;
	}

	/**
	 * Resolve a public-domain translation id for search.
	 *
	 * @param string $translation Requested site slug.
	 * @return string
	 */
	public static function resolve_translation( $translation ) {
		$translation = strtolower( sanitize_key( (string) $translation ) );
		if ( isset( self::$translation_map[ $translation ] ) ) {
			return self::$translation_map[ $translation ];
		}
		return 'kjv';
	}

	/**
	 * Search public-domain Bible text.
	 *
	 * @param string $translation Preferred translation slug.
	 * @param string $query       Search query.
	 * @param int    $limit       Max results.
	 * @return array{results:array<int,array<string,mixed>>,translation:string,error:string}
	 */
	public static function search( $translation, $query, $limit = 12 ) {
		$query = self::normalize_query( $query );
		$limit = max( 1, min( 25, (int) $limit ) );
		$empty = array(
			'results'     => array(),
			'translation' => '',
			'error'       => '',
		);

		if ( '' === $query ) {
			$empty['error'] = 'invalid_query';
			return $empty;
		}

		$search_translation = self::resolve_translation( $translation );
		$cache_key          = 'hwbl_pd_search_' . md5( $search_translation . '|' . strtolower( $query ) . '|' . $limit );
		$cached             = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['results'] ) ) {
			return $cached;
		}

		$url = add_query_arg(
			array(
				'q'           => $query,
				'translation' => $search_translation,
				'limit'       => $limit,
			),
			self::API_BASE
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'HiddenWordBibleLessons/' . ( defined( 'HWBL_VERSION' ) ? HWBL_VERSION : '1.0' ),
				),
			)
		);

		if ( is_wp_error( $response ) || ! HWBL_Http_Utils::response_ok( $response ) ) {
			$empty['error'] = 'search_failed';
			return $empty;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['results'] ) || ! is_array( $body['results'] ) ) {
			$payload = array(
				'results'     => array(),
				'translation' => $search_translation,
				'error'       => '',
			);
			set_transient( $cache_key, $payload, HOUR_IN_SECONDS );
			return $payload;
		}

		$results = array();
		foreach ( $body['results'] as $row ) {
			$book_id = 0;
			if ( ! empty( $row['book_id'] ) && class_exists( 'HWBL_Books' ) ) {
				$book_id = HWBL_Books::get_id_by_usfm( (string) $row['book_id'] );
			}
			if ( $book_id < 1 && ! empty( $row['book_name'] ) && class_exists( 'HWBL_Books' ) ) {
				$book_id = HWBL_Books::get_id_by_name( (string) $row['book_name'] );
			}
			if ( $book_id < 1 && ! empty( $row['reference'] ) && class_exists( 'HWBL_Books' ) ) {
				$parsed = HWBL_Books::parse_reference( (string) $row['reference'] );
				if ( $parsed && ! empty( $parsed['book_id'] ) ) {
					$book_id = (int) $parsed['book_id'];
				}
			}
			if ( $book_id < 1 || empty( $row['chapter'] ) ) {
				continue;
			}

			$chapter = max( 1, (int) $row['chapter'] );
			$verse   = ! empty( $row['verse'] ) ? max( 1, (int) $row['verse'] ) : 0;
			$preview = isset( $row['text'] ) ? wp_strip_all_tags( (string) $row['text'] ) : '';
			$passage = ! empty( $row['reference'] )
				? (string) $row['reference']
				: HWBL_Books::format_reference( $book_id, $chapter, max( 1, $verse ) );

			$results[] = array(
				'passage'   => $passage,
				'preview'   => $preview,
				'book_id'   => $book_id,
				'chapter'   => $chapter,
				'verse'     => $verse,
				'verse_end' => 0,
			);
		}

		$payload = array(
			'results'     => $results,
			'translation' => $search_translation,
			'error'       => '',
		);
		set_transient( $cache_key, $payload, HOUR_IN_SECONDS );

		return $payload;
	}
}
