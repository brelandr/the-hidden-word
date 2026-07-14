<?php
/**
 * Bundled curriculum helpers.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Curriculum
 */
class THW_Curriculum {

	/**
	 * Load NIV curriculum entries.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function load_niv() {
		$path = THW_PLUGIN_DIR . 'data/niv-curriculum.json';
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$data = json_decode( file_get_contents( $path ), true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Load KJV curriculum entries.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function load_kjv() {
		return self::load_translation( 'kjv' );
	}

	/**
	 * Load WEB curriculum entries.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function load_web() {
		return self::load_translation( 'web' );
	}

	/**
	 * Load bundled curriculum entries for a translation slug.
	 *
	 * @param string $slug Translation slug.
	 * @return array<int, array<string, mixed>>
	 */
	public static function load_translation( $slug ) {
		$slug = strtolower( $slug );
		$files = array(
			'niv' => 'niv-curriculum.json',
			'kjv' => 'kjv-curriculum.json',
			'web' => 'web-curriculum.json',
		);

		if ( ! isset( $files[ $slug ] ) ) {
			return array();
		}

		$path = THW_PLUGIN_DIR . 'data/' . $files[ $slug ];
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$data = json_decode( file_get_contents( $path ), true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Lesson index from a curriculum entry (supports legacy week field).
	 *
	 * @param array<string, mixed> $entry Curriculum row.
	 * @return int
	 */
	public static function get_entry_lesson_number( $entry ) {
		if ( isset( $entry['lesson'] ) ) {
			return (int) $entry['lesson'];
		}
		if ( isset( $entry['week'] ) ) {
			return (int) $entry['week'];
		}
		return 0;
	}

	/**
	 * Count individual verses in curriculum rows (handles verse ranges).
	 *
	 * @param array<int, array<string, mixed>>|null $entries Curriculum rows.
	 * @return int
	 */
	public static function count_verses( $entries = null ) {
		if ( null === $entries ) {
			$entries = self::load_niv();
		}

		$count = 0;
		foreach ( $entries as $entry ) {
			$start = isset( $entry['verse_start'] ) ? (int) $entry['verse_start'] : 0;
			$end   = isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : $start;
			if ( $start < 1 || $end < $start ) {
				continue;
			}
			$count += ( $end - $start + 1 );
		}

		return $count;
	}

	/**
	 * Total bundled NIV lessons in curriculum JSON.
	 *
	 * @return int
	 */
	public static function get_lesson_count() {
		return count( self::load_niv() );
	}

	/**
	 * Map a schedule slot to a lesson number (1-based, wraps catalog).
	 *
	 * @param int $slot Slot number (day of year, ISO week, etc.).
	 * @return int
	 */
	public static function slot_to_lesson_number( $slot ) {
		$total = self::get_lesson_count();
		if ( $total < 1 ) {
			return 0;
		}

		$slot = max( 1, (int) $slot );
		return ( ( $slot - 1 ) % $total ) + 1;
	}
}
