<?php
/**
 * Bible book ID mapping.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Books
 */
class HWBL_Books {

	/**
	 * Get all books keyed by ID.
	 *
	 * @return array<int, string>
	 */
	public static function get_all() {
		$path = HWBL_PLUGIN_DIR . 'data/books.json';
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$data = json_decode( file_get_contents( $path ), true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Get book name by ID.
	 *
	 * @param int $book_id Book ID.
	 * @return string
	 */
	public static function get_name( $book_id ) {
		$books = self::get_all();
		return isset( $books[ (string) $book_id ] ) ? $books[ (string) $book_id ] : __( 'Unknown', 'hidden-word-bible-lessons' );
	}

	/**
	 * Get book ID by name (case-insensitive).
	 *
	 * @param string $name Book name.
	 * @return int
	 */
	public static function get_id_by_name( $name ) {
		$name = strtolower( trim( $name ) );
		foreach ( self::get_all() as $id => $book_name ) {
			if ( strtolower( $book_name ) === $name ) {
				return (int) $id;
			}
		}
		return 0;
	}

	/**
	 * Format a scripture reference.
	 *
	 * @param int $book_id     Book ID.
	 * @param int $chapter     Chapter.
	 * @param int $verse_start Start verse.
	 * @param int $verse_end   End verse.
	 * @return string
	 */
	public static function format_reference( $book_id, $chapter, $verse_start, $verse_end = 0 ) {
		$ref = self::get_name( $book_id ) . ' ' . $chapter . ':' . $verse_start;
		if ( $verse_end && (int) $verse_end !== (int) $verse_start ) {
			$ref .= '-' . $verse_end;
		}
		return $ref;
	}

	/**
	 * Testament slug for a book ID (ot = 1–39, nt = 40–66).
	 *
	 * @param int $book_id Book ID.
	 * @return string ot|nt|''
	 */
	public static function get_testament( $book_id ) {
		$book_id = (int) $book_id;
		if ( $book_id >= 1 && $book_id <= 39 ) {
			return 'ot';
		}
		if ( $book_id >= 40 && $book_id <= 66 ) {
			return 'nt';
		}
		return '';
	}

	/**
	 * USFM book ID for Hello AO and other API providers (GEN, 1JN, etc.).
	 *
	 * @param int $book_id Book ID (1–66).
	 * @return string Empty when unknown.
	 */
	public static function get_usfm( $book_id ) {
		$map = array(
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

		$book_id = (int) $book_id;
		return isset( $map[ $book_id ] ) ? $map[ $book_id ] : '';
	}

	/**
	 * Book ID for a USFM code (GEN, JHN, 1CO, etc.).
	 *
	 * @param string $usfm USFM book code.
	 * @return int
	 */
	public static function get_id_by_usfm( $usfm ) {
		$usfm = strtoupper( trim( (string) $usfm ) );
		if ( '' === $usfm ) {
			return 0;
		}

		foreach ( self::get_all() as $id => $name ) {
			unset( $name );
			if ( self::get_usfm( (int) $id ) === $usfm ) {
				return (int) $id;
			}
		}

		$aliases = array(
			'JN' => 43,
			'MK' => 41,
			'MT' => 40,
			'LK' => 42,
			'PS' => 19,
			'SOS' => 22,
		);

		return isset( $aliases[ $usfm ] ) ? (int) $aliases[ $usfm ] : 0;
	}

	/**
	 * Parse a YouVersion passage ID (e.g. JHN.3.16 or 1CO.13.4-7).
	 *
	 * @param string $passage_id Passage ID from YouVersion API.
	 * @return array{book_id:int,chapter:int,verse:int,verse_end:int,reference:string}|null
	 */
	public static function parse_youversion_passage_id( $passage_id ) {
		$passage_id = strtoupper( trim( (string) $passage_id ) );
		if ( ! preg_match( '/^([1-3]?[A-Z]{2,3})\.(\d+)\.(\d+(?:-\d+)?)$/', $passage_id, $matches ) ) {
			return null;
		}

		$book_id = self::get_id_by_usfm( $matches[1] );
		if ( $book_id < 1 ) {
			return null;
		}

		$chapter = max( 1, (int) $matches[2] );
		$verse   = (string) $matches[3];
		$start   = $verse;
		$end     = 0;
		if ( false !== strpos( $verse, '-' ) ) {
			$parts = array_map( 'intval', explode( '-', $verse, 2 ) );
			$start = max( 1, (int) $parts[0] );
			$end   = max( $start, (int) ( $parts[1] ?? $start ) );
		} else {
			$start = max( 1, (int) $verse );
		}

		return array(
			'book_id'   => $book_id,
			'chapter'   => $chapter,
			'verse'     => $start,
			'verse_end' => $end > 0 ? $end : $start,
			'reference' => self::format_reference( $book_id, $chapter, $start, $end > 0 ? $end : 0 ),
		);
	}

	/**
	 * Human-readable passage string for Biblia.com content/parse endpoints.
	 *
	 * @param int $book_id     Book ID.
	 * @param int $chapter     Chapter.
	 * @param int $verse       Verse.
	 * @param int $verse_end   End verse (optional).
	 * @return string e.g. "Ephesians 6:13" or "1 John 4:7-8".
	 */
	public static function passage_for_biblia( $book_id, $chapter, $verse, $verse_end = 0 ) {
		return self::format_reference( $book_id, $chapter, $verse, $verse_end );
	}

	/**
	 * Parse a human reference like "John 3:16" or "Genesis 3".
	 *
	 * @param string $text Reference text.
	 * @return array{book_id:int,chapter:int,verse:int,verse_end:int,reference:string}|null
	 */
	public static function parse_reference( $text ) {
		$text = trim( preg_replace( '/\s+/', ' ', (string) $text ) );
		if ( '' === $text ) {
			return null;
		}

		if ( ! preg_match( '/^(.+?)\s+(\d+)(?:\s*:\s*(\d+))?(?:\s*-\s*(\d+))?$/u', $text, $matches ) ) {
			return null;
		}

		$book_id = self::resolve_book_query( $matches[1] );
		if ( $book_id < 1 ) {
			return null;
		}

		$chapter   = max( 1, (int) $matches[2] );
		$verse     = ! empty( $matches[3] ) ? max( 1, (int) $matches[3] ) : 0;
		$verse_end = ! empty( $matches[4] ) ? max( 1, (int) $matches[4] ) : 0;

		return array(
			'book_id'   => $book_id,
			'chapter'   => $chapter,
			'verse'     => $verse,
			'verse_end' => $verse_end,
			'reference' => $verse > 0
				? self::format_reference( $book_id, $chapter, $verse, $verse_end )
				: self::get_name( $book_id ) . ' ' . $chapter,
		);
	}

	/**
	 * Resolve a book name or abbreviation to a book ID.
	 *
	 * @param string $query Book query.
	 * @return int
	 */
	public static function resolve_book_query( $query ) {
		$query = trim( (string) $query );
		if ( '' === $query ) {
			return 0;
		}

		$book_id = self::get_id_by_name( $query );
		if ( $book_id > 0 ) {
			return $book_id;
		}

		$key = strtolower( preg_replace( '/\s+/', ' ', $query ) );
		$key = str_replace( '.', '', $key );

		$aliases = self::get_book_aliases();
		if ( isset( $aliases[ $key ] ) ) {
			return (int) $aliases[ $key ];
		}

		foreach ( self::get_all() as $id => $name ) {
			if ( 0 === strcasecmp( (string) $name, $query ) ) {
				return (int) $id;
			}
		}

		return 0;
	}

	/**
	 * Common book abbreviations mapped to book IDs.
	 *
	 * @return array<string, int>
	 */
	private static function get_book_aliases() {
		return array(
			'gen' => 1, 'gn' => 1, 'exod' => 2, 'ex' => 2, 'exo' => 2,
			'lev' => 3, 'num' => 4, 'nu' => 4, 'deut' => 5, 'deu' => 5, 'dt' => 5,
			'josh' => 6, 'jos' => 6, 'judg' => 7, 'jdg' => 7, 'ruth' => 8, 'ru' => 8, 'rt' => 8,
			'1 sam' => 9, '1sam' => 9, '1 sa' => 9, '2 sam' => 10, '2sam' => 10, '2 sa' => 10,
			'1 kgs' => 11, '1 ki' => 11, '1kgs' => 11, '1ki' => 11,
			'2 kgs' => 12, '2 ki' => 12, '2kgs' => 12, '2ki' => 12,
			'1 chr' => 13, '1ch' => 13, '2 chr' => 14, '2ch' => 14,
			'ezra' => 15, 'ezr' => 15, 'neh' => 16, 'est' => 17, 'esth' => 17,
			'job' => 18, 'ps' => 19, 'psa' => 19, 'psalm' => 19, 'prov' => 20, 'pro' => 20,
			'eccl' => 21, 'ecc' => 21, 'song' => 22, 'sos' => 22, 'isa' => 23,
			'jer' => 24, 'lam' => 25, 'ezek' => 26, 'ezk' => 26, 'dan' => 27,
			'hos' => 28, 'joel' => 29, 'jol' => 29, 'amos' => 30, 'amo' => 30,
			'obad' => 31, 'oba' => 31, 'jonah' => 32, 'jon' => 32, 'mic' => 33,
			'nah' => 34, 'nam' => 34, 'hab' => 35, 'zeph' => 36, 'zep' => 36,
			'hag' => 37, 'zech' => 38, 'zec' => 38, 'mal' => 39,
			'matt' => 40, 'mat' => 40, 'mt' => 40, 'mk' => 41, 'mrk' => 41,
			'lk' => 42, 'luk' => 42, 'jn' => 43, 'jhn' => 43, 'john' => 43,
			'acts' => 44, 'act' => 44, 'rom' => 45, '1 cor' => 46, '1cor' => 46,
			'2 cor' => 47, '2cor' => 47, 'gal' => 48, 'eph' => 49, 'phil' => 50, 'php' => 50,
			'col' => 51, '1 thess' => 52, '1th' => 52, '2 thess' => 53, '2th' => 53,
			'1 tim' => 54, '1ti' => 54, '2 tim' => 55, '2ti' => 55, 'tit' => 56,
			'phlm' => 57, 'phm' => 57, 'heb' => 58, 'jas' => 59, 'james' => 59,
			'1 pet' => 60, '1pe' => 60, '2 pet' => 61, '2pe' => 61,
			'1 jn' => 62, '1 john' => 62, '1jn' => 62, '2 jn' => 63, '2jn' => 63,
			'3 jn' => 64, '3jn' => 64, 'jud' => 65, 'rev' => 66, 'revelation' => 66,
		);
	}
}
