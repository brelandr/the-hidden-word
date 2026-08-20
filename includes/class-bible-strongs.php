<?php
/**
 * Strong's concordance lexicon + occurrence index.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Bible_Strongs
 */
class HWBL_Bible_Strongs {

	/**
	 * In-request lexicon cache.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $lexicon = null;

	/**
	 * In-request English index cache.
	 *
	 * @var array<string, array<int, string>>|null
	 */
	private static $english = null;

	/**
	 * Occurrence caches keyed by H|G.
	 *
	 * @var array<string, array<string, array<int, string>>>
	 */
	private static $occurrences = array();

	/**
	 * Data directory.
	 *
	 * @return string
	 */
	public static function data_dir() {
		return HWBL_PLUGIN_DIR . 'data/strongs/';
	}

	/**
	 * Whether Strong's packs are present.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return is_readable( self::data_dir() . 'lexicon.json' )
			&& is_readable( self::data_dir() . 'occurrences-h.json.gz' )
			&& is_readable( self::data_dir() . 'occurrences-g.json.gz' );
	}

	/**
	 * Normalize a Strong's number query (H1 / G25 / 25 + testament hint).
	 *
	 * @param string $query     Raw query.
	 * @param string $testament ot|nt|'' hint when digits-only.
	 * @return string Normalized H#### / G#### or empty.
	 */
	public static function normalize_number( $query, $testament = '' ) {
		$query = strtoupper( trim( (string) $query ) );
		$query = preg_replace( '/\s+/', '', $query );
		if ( ! is_string( $query ) || '' === $query ) {
			return '';
		}

		if ( preg_match( '/^([HG])0*([1-9]\d*)$/', $query, $m ) ) {
			return $m[1] . (string) (int) $m[2];
		}

		if ( preg_match( '/^0*([1-9]\d*)$/', $query, $m ) ) {
			$n = (int) $m[1];
			if ( 'ot' === $testament ) {
				return 'H' . $n;
			}
			if ( 'nt' === $testament ) {
				return 'G' . $n;
			}
			// Prefer Greek for bare NT-range ambiguity when only one exists.
			if ( self::has_number( 'G' . $n ) ) {
				return 'G' . $n;
			}
			if ( self::has_number( 'H' . $n ) ) {
				return 'H' . $n;
			}
			return 'G' . $n;
		}

		return '';
	}

	/**
	 * Detect whether a query looks like a Strong's number.
	 *
	 * @param string $query Query.
	 * @return bool
	 */
	public static function looks_like_number( $query ) {
		$query = trim( (string) $query );
		return (bool) preg_match( '/^[HhGg]?\s*0*[1-9]\d*$/', $query );
	}

	/**
	 * Lexicon entry for a number.
	 *
	 * @param string $number H#### / G####.
	 * @return array<string, mixed>|null
	 */
	public static function get_entry( $number ) {
		$number = self::normalize_number( $number );
		if ( '' === $number ) {
			return null;
		}
		$lex = self::load_lexicon();
		if ( ! isset( $lex[ $number ] ) || ! is_array( $lex[ $number ] ) ) {
			return null;
		}
		$row = $lex[ $number ];
		return array(
			'number'          => $number,
			'lemma'           => isset( $row['w'] ) ? (string) $row['w'] : '',
			'transliteration' => isset( $row['t'] ) ? (string) $row['t'] : '',
			'gloss'           => isset( $row['g'] ) ? (string) $row['g'] : '',
			'glosses'         => isset( $row['gs'] ) && is_array( $row['gs'] ) ? array_values( $row['gs'] ) : array(),
			'testament'       => 0 === strpos( $number, 'H' ) ? 'ot' : 'nt',
		);
	}

	/**
	 * Whether a number exists in the lexicon or occurrence packs.
	 *
	 * @param string $number Number.
	 * @return bool
	 */
	public static function has_number( $number ) {
		$number = self::normalize_number( $number );
		if ( '' === $number ) {
			return false;
		}
		$lex = self::load_lexicon();
		if ( isset( $lex[ $number ] ) ) {
			return true;
		}
		$refs = self::get_occurrence_refs( $number );
		return ! empty( $refs );
	}

	/**
	 * Resolve English gloss → Strong's candidates.
	 *
	 * @param string $word English word.
	 * @param int    $limit Max candidates.
	 * @return array<int, array<string, mixed>>
	 */
	public static function suggest_from_english( $word, $limit = 12 ) {
		$word = strtolower( trim( (string) $word ) );
		if ( '' === $word || ! self::is_available() ) {
			return array();
		}
		$index = self::load_english_index();
		$nums  = isset( $index[ $word ] ) && is_array( $index[ $word ] ) ? $index[ $word ] : array();
		$out   = array();
		foreach ( $nums as $num ) {
			$entry = self::get_entry( (string) $num );
			if ( $entry ) {
				$out[] = $entry;
			}
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Occurrence verse references for a Strong's number.
	 *
	 * @param string $number H#### / G####.
	 * @return array<int, string>
	 */
	public static function get_occurrence_refs( $number ) {
		$number = self::normalize_number( $number );
		if ( '' === $number || ! self::is_available() ) {
			return array();
		}
		$pack = 0 === strpos( $number, 'H' ) ? 'H' : 'G';
		$map  = self::load_occurrences( $pack );
		if ( ! isset( $map[ $number ] ) || ! is_array( $map[ $number ] ) ) {
			return array();
		}
		return array_values( array_map( 'strval', $map[ $number ] ) );
	}

	/**
	 * Paginated concordance results for a Strong's number.
	 *
	 * @param string $number      Strong's number.
	 * @param int    $limit       Page size.
	 * @param int    $offset      Offset.
	 * @param string $testament   ot|nt|'' filter (usually redundant).
	 * @param string $translation Preferred local translation for verse text.
	 * @return array<string, mixed>
	 */
	public static function lookup( $number, $limit = 50, $offset = 0, $testament = '', $translation = '' ) {
		$limit  = max( 1, min( 100, (int) $limit ) );
		$offset = max( 0, (int) $offset );
		$number = self::normalize_number( $number, $testament );

		$payload = array(
			'query'       => $number,
			'mode'        => 'strongs',
			'strongs'     => null,
			'backend'     => 'strongs',
			'count'       => 0,
			'total'       => 0,
			'offset'      => $offset,
			'limit'       => $limit,
			'has_more'    => false,
			'truncated'   => false,
			'results'     => array(),
			'candidates'  => array(),
			'error'       => '',
			'message'     => '',
			'translation' => '',
		);

		if ( ! self::is_available() ) {
			$payload['error']   = 'unavailable';
			$payload['message'] = __( 'Strong\'s data is not installed.', 'hidden-word-bible-lessons' );
			return $payload;
		}

		if ( '' === $number ) {
			$payload['error']   = 'invalid_query';
			$payload['message'] = __( 'Enter a Strong\'s number such as G25 or H2617.', 'hidden-word-bible-lessons' );
			return $payload;
		}

		$entry = self::get_entry( $number );
		$payload['strongs'] = $entry;

		$refs = self::get_occurrence_refs( $number );
		$parsed_rows = array();
		foreach ( $refs as $ref ) {
			$parsed = HWBL_Books::parse_reference( $ref );
			if ( ! $parsed || empty( $parsed['book_id'] ) || empty( $parsed['chapter'] ) ) {
				continue;
			}
			$book_id = (int) $parsed['book_id'];
			$chapter = (int) $parsed['chapter'];
			$verse   = ! empty( $parsed['verse'] ) ? (int) $parsed['verse'] : 1;
			if ( $testament && in_array( $testament, array( 'ot', 'nt' ), true ) ) {
				if ( HWBL_Books::get_testament( $book_id ) !== $testament ) {
					continue;
				}
			}
			$parsed_rows[] = array(
				'book_id' => $book_id,
				'chapter' => $chapter,
				'verse'   => $verse,
			);
		}

		usort(
			$parsed_rows,
			static function ( $a, $b ) {
				if ( $a['book_id'] !== $b['book_id'] ) {
					return $a['book_id'] - $b['book_id'];
				}
				if ( $a['chapter'] !== $b['chapter'] ) {
					return $a['chapter'] - $b['chapter'];
				}
				return $a['verse'] - $b['verse'];
			}
		);

		$total       = count( $parsed_rows );
		$slice       = array_slice( $parsed_rows, $offset, $limit );
		$translation = self::resolve_text_translation( $translation );
		$results     = array();

		foreach ( $slice as $row ) {
			$book_id = (int) $row['book_id'];
			$chapter = (int) $row['chapter'];
			$verse   = (int) $row['verse'];
			$text    = '';
			if ( $translation && class_exists( 'HWBL_Local_Bible_Store' ) ) {
				$verse_text = HWBL_Local_Bible_Store::get_verse( $translation, $book_id, $chapter, $verse );
				if ( is_string( $verse_text ) && '' !== $verse_text ) {
					$text = $verse_text;
				}
			}
			$results[] = array(
				'reference' => HWBL_Books::format_reference( $book_id, $chapter, $verse ),
				'book_id'   => $book_id,
				'chapter'   => $chapter,
				'verse'     => $verse,
				'text'      => $text,
				'snippet'   => $text,
				'testament' => HWBL_Books::get_testament( $book_id ),
				'strongs'   => $number,
			);
		}

		$payload['results']     = $results;
		$payload['count']       = count( $results );
		$payload['total']       = $total;
		$payload['has_more']    = ( $offset + $payload['count'] ) < $total;
		$payload['truncated']   = $payload['has_more'];
		$payload['translation'] = $translation;

		if ( 0 === $total ) {
			$payload['message'] = __( 'No occurrences found for that Strong\'s number.', 'hidden-word-bible-lessons' );
		}

		return $payload;
	}

	/**
	 * Pick a local translation for verse text beside Strong's hits.
	 *
	 * @param string $preferred Preferred slug.
	 * @return string
	 */
	private static function resolve_text_translation( $preferred ) {
		if ( ! class_exists( 'HWBL_Local_Bible_Store' ) ) {
			return '';
		}
		$preferred = sanitize_key( (string) $preferred );
		if ( $preferred && HWBL_Local_Bible_Store::is_installed( $preferred ) ) {
			return $preferred;
		}
		foreach ( array( 'kjv', 'web', 'bsb', 'asv' ) as $slug ) {
			if ( HWBL_Local_Bible_Store::is_installed( $slug ) ) {
				return $slug;
			}
		}
		$labels = HWBL_Local_Bible_Store::get_installed_labels();
		if ( ! empty( $labels ) ) {
			$keys = array_keys( $labels );
			return (string) $keys[0];
		}
		return '';
	}

	/**
	 * Load lexicon JSON once.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function load_lexicon() {
		if ( null !== self::$lexicon ) {
			return self::$lexicon;
		}
		$path = self::data_dir() . 'lexicon.json';
		if ( ! is_readable( $path ) ) {
			self::$lexicon = array();
			return self::$lexicon;
		}
		$raw = file_get_contents( $path );
		$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
		self::$lexicon = is_array( $data ) ? $data : array();
		return self::$lexicon;
	}

	/**
	 * Load English reverse index once.
	 *
	 * @return array<string, array<int, string>>
	 */
	private static function load_english_index() {
		if ( null !== self::$english ) {
			return self::$english;
		}
		$path = self::data_dir() . 'english-index.json';
		if ( ! is_readable( $path ) ) {
			self::$english = array();
			return self::$english;
		}
		$raw  = file_get_contents( $path );
		$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
		self::$english = is_array( $data ) ? $data : array();
		return self::$english;
	}

	/**
	 * Load occurrence pack (H or G).
	 *
	 * @param string $pack H|G.
	 * @return array<string, array<int, string>>
	 */
	private static function load_occurrences( $pack ) {
		$pack = ( 'H' === $pack ) ? 'H' : 'G';
		if ( isset( self::$occurrences[ $pack ] ) ) {
			return self::$occurrences[ $pack ];
		}

		$file = self::data_dir() . ( 'H' === $pack ? 'occurrences-h.json.gz' : 'occurrences-g.json.gz' );
		if ( ! is_readable( $file ) ) {
			self::$occurrences[ $pack ] = array();
			return self::$occurrences[ $pack ];
		}

		$raw = function_exists( 'gzfile' ) ? implode( '', (array) gzfile( $file ) ) : '';
		if ( '' === $raw && function_exists( 'gzopen' ) ) {
			$zh = gzopen( $file, 'rb' );
			if ( $zh ) {
				while ( ! gzeof( $zh ) ) {
					$raw .= gzread( $zh, 8192 );
				}
				gzclose( $zh );
			}
		}

		$data = json_decode( $raw, true );
		self::$occurrences[ $pack ] = is_array( $data ) ? $data : array();
		return self::$occurrences[ $pack ];
	}
}
