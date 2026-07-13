<?php
/**
 * Bible book ID mapping.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Books
 */
class THW_Books {

	/**
	 * Get all books keyed by ID.
	 *
	 * @return array<int, string>
	 */
	public static function get_all() {
		$path = THW_PLUGIN_DIR . 'data/books.json';
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
		return isset( $books[ (string) $book_id ] ) ? $books[ (string) $book_id ] : __( 'Unknown', 'the-hidden-word' );
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
}
