<?php
/**
 * Translation provider interface.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface HWBL_Translation_Provider
 */
interface HWBL_Translation_Provider {

	/**
	 * Get verse text.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public function get_verse( $book_id, $chapter, $verse, $translation );

	/**
	 * Get supported translation slugs.
	 *
	 * @return array<string, string> slug => label
	 */
	public function get_supported_translations();
}
