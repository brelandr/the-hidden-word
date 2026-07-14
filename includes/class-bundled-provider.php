<?php
/**
 * Bundled NIV, KJV, and WEB translation provider.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Bundled_Provider
 */
class THW_Bundled_Provider implements THW_Translation_Provider {

	/**
	 * Bundled curriculum files keyed by translation slug.
	 *
	 * @var array<string, string>
	 */
	private static $curriculum_files = array(
		'niv' => 'niv-curriculum.json',
		'kjv' => 'kjv-curriculum.json',
		'web' => 'web-curriculum.json',
	);

	/**
	 * Cached curriculum data.
	 *
	 * @var array<string, array>
	 */
	private $cache = array();

	/**
	 * Get verse text from bundled JSON.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public function get_verse( $book_id, $chapter, $verse, $translation ) {
		$translation = strtolower( $translation );
		$curriculum  = $this->load_curriculum( $translation );

		foreach ( $curriculum as $entry ) {
			if (
				(int) $entry['book_id'] === (int) $book_id
				&& (int) $entry['chapter'] === (int) $chapter
				&& (int) $entry['verse_start'] <= (int) $verse
				&& (int) ( isset( $entry['verse_end'] ) ? $entry['verse_end'] : $entry['verse_start'] ) >= (int) $verse
			) {
				return isset( $entry['text'] ) ? $entry['text'] : null;
			}
		}

		return null;
	}

	/**
	 * Get verse text by lesson number.
	 *
	 * @param int    $lesson      Lesson number.
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public function get_verse_by_lesson( $lesson, $translation ) {
		$curriculum = $this->load_curriculum( strtolower( $translation ) );

		foreach ( $curriculum as $entry ) {
			if ( THW_Curriculum::get_entry_lesson_number( $entry ) === (int) $lesson ) {
				return isset( $entry['text'] ) ? $entry['text'] : null;
			}
		}

		return null;
	}

	/**
	 * Get verse text by week number (legacy alias).
	 *
	 * @param int    $week        Week number.
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public function get_verse_by_week( $week, $translation ) {
		return $this->get_verse_by_lesson( $week, $translation );
	}

	/**
	 * Get supported translations.
	 *
	 * @return array<string, string>
	 */
	public function get_supported_translations() {
		return array(
			'niv' => __( 'New International Version (NIV)', 'the-hidden-word' ),
			'kjv' => __( 'King James Version (KJV)', 'the-hidden-word' ),
			'web' => __( 'World English Bible (WEB)', 'the-hidden-word' ),
		);
	}

	/**
	 * Translation slugs that must mirror NIV lesson count (excludes NIV).
	 *
	 * @return string[]
	 */
	public static function get_parity_slugs() {
		$slugs = array_keys( self::$curriculum_files );
		return array_values( array_diff( $slugs, array( 'niv' ) ) );
	}

	/**
	 * Load curriculum JSON for a translation.
	 *
	 * @param string $translation Translation slug.
	 * @return array<int, array>
	 */
	private function load_curriculum( $translation ) {
		if ( isset( $this->cache[ $translation ] ) ) {
			return $this->cache[ $translation ];
		}

		$file = isset( self::$curriculum_files[ $translation ] ) ? self::$curriculum_files[ $translation ] : '';
		if ( ! $file ) {
			$this->cache[ $translation ] = array();
			return array();
		}
		$path = THW_PLUGIN_DIR . 'data/' . $file;

		if ( ! is_readable( $path ) ) {
			$this->cache[ $translation ] = array();
			return array();
		}

		$data = json_decode( file_get_contents( $path ), true );
		$this->cache[ $translation ] = is_array( $data ) ? $data : array();

		return $this->cache[ $translation ];
	}

	/**
	 * Get NIV copyright notice.
	 *
	 * @return string
	 */
	public static function get_niv_copyright() {
		return __( 'Scripture quotations marked NIV are from THE HOLY BIBLE, NEW INTERNATIONAL VERSION®, NIV® Copyright © 1973, 1978, 1984, 2011 by Biblica, Inc.® Used by permission. All rights reserved worldwide.', 'the-hidden-word' );
	}
}
