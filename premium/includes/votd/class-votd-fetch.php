<?php
/**
 * VOTD remote fetch helpers (extracted from verse-of-the-day).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_VOTD_Fetch
 */
class THW_Premium_VOTD_Fetch {

	/**
	 * Fetch bible.com Open Graph meta for today's VOTD.
	 *
	 * @return array{reference:string,description_text:string,image:string}
	 */
	public static function fetch_bible_com_meta() {
		return THW_Premium_Verse_Of_The_Day::fetch_bible_com_meta();
	}

	/**
	 * Fetch YouVersion VOTD metadata for a calendar day.
	 *
	 * @param string $day Y-m-d.
	 * @return array<string, string>
	 */
	public static function fetch_youversion_votd_meta( $day ) {
		return THW_Premium_Verse_Of_The_Day::fetch_youversion_votd_meta( $day );
	}

	/**
	 * Fetch bible.com page HTML.
	 *
	 * @return string
	 */
	public static function fetch_bible_com_page_html() {
		return THW_Premium_Verse_Of_The_Day::fetch_bible_com_page_html();
	}

	/**
	 * Resolve shareable image URL for a passage.
	 *
	 * @param string $passage_id Passage ID.
	 * @return string
	 */
	public static function resolve_votd_image_url( $passage_id ) {
		return THW_Premium_Verse_Of_The_Day::resolve_votd_image_url( $passage_id );
	}
}
