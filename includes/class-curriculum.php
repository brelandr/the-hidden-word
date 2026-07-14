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
	 * Cached NIV curriculum rows keyed by lesson number.
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private static $entries_by_lesson = null;

	/**
	 * Get a bundled curriculum row by lesson number.
	 *
	 * @param int $lesson_number Lesson number (1-based).
	 * @return array<string, mixed>|null
	 */
	public static function get_entry_by_lesson_number( $lesson_number ) {
		$lesson_number = (int) $lesson_number;
		if ( $lesson_number < 1 ) {
			return null;
		}

		if ( null === self::$entries_by_lesson ) {
			self::$entries_by_lesson = array();
			foreach ( self::load_niv() as $entry ) {
				$num = self::get_entry_lesson_number( $entry );
				if ( $num >= 1 ) {
					self::$entries_by_lesson[ $num ] = $entry;
				}
			}
		}

		return isset( self::$entries_by_lesson[ $lesson_number ] )
			? self::$entries_by_lesson[ $lesson_number ]
			: null;
	}

	/**
	 * Fill empty lesson content fields from a bundled curriculum row.
	 *
	 * @param array<string, mixed>      $lesson Lesson data.
	 * @param array<string, mixed>|null $entry  Curriculum row.
	 * @return array<string, mixed>
	 */
	public static function fill_lesson_content_from_entry( $lesson, $entry ) {
		if ( is_array( $entry ) ) {
			if ( empty( $lesson['historical_context'] ) && ! empty( $entry['historical_context'] ) ) {
				$lesson['historical_context'] = $entry['historical_context'];
			}

			if ( empty( $lesson['preceding_narrative'] ) && ! empty( $entry['preceding_narrative'] ) ) {
				$lesson['preceding_narrative'] = $entry['preceding_narrative'];
			}

			if ( empty( $lesson['discussion_questions'] ) && ! empty( $entry['discussion_questions'] ) && is_array( $entry['discussion_questions'] ) ) {
				$lesson['discussion_questions'] = $entry['discussion_questions'];
			}

			if ( empty( $lesson['follow_on_verses'] ) && ! empty( $entry['follow_on_verses'] ) && is_array( $entry['follow_on_verses'] ) ) {
				$lesson['follow_on_verses'] = $entry['follow_on_verses'];
			}
		}

		if ( empty( $lesson['follow_on_verses'] ) ) {
			$lesson['follow_on_verses'] = self::default_follow_on_verses(
				isset( $lesson['book_id'] ) ? (int) $lesson['book_id'] : 0,
				isset( $lesson['chapter'] ) ? (int) $lesson['chapter'] : 0,
				isset( $lesson['verse_start'] ) ? (int) $lesson['verse_start'] : 0,
				isset( $lesson['verse_end'] ) ? (int) $lesson['verse_end'] : 0
			);
		}

		return $lesson;
	}

	/**
	 * Default follow-on verses: the lines immediately before and after the lesson.
	 *
	 * @param int $book_id     Book ID.
	 * @param int $chapter     Chapter.
	 * @param int $verse_start First verse of the lesson.
	 * @param int $verse_end   Last verse of the lesson.
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_follow_on_verses( $book_id, $chapter, $verse_start, $verse_end ) {
		$book_id     = (int) $book_id;
		$chapter     = (int) $chapter;
		$verse_start = (int) $verse_start;
		$verse_end   = (int) $verse_end;

		if ( $book_id < 1 || $chapter < 1 || $verse_start < 1 ) {
			return array();
		}

		if ( $verse_end < $verse_start ) {
			$verse_end = $verse_start;
		}

		$book_name = THW_Books::get_name( $book_id );
		$verses    = array();

		if ( $verse_start > 1 ) {
			$before = $verse_start - 1;
			$verses[] = array(
				'book_id' => $book_id,
				'chapter' => $chapter,
				'verse'   => $before,
				'note'    => sprintf(
					/* translators: 1: book name, 2: chapter, 3: verse */
					__( 'Read the verse immediately before this lesson (%1$s %2$d:%3$d) to hear the lead-in to the passage.', 'the-hidden-word' ),
					$book_name,
					$chapter,
					$before
				),
			);
		}

		$after = $verse_end + 1;
		$verses[] = array(
			'book_id' => $book_id,
			'chapter' => $chapter,
			'verse'   => $after,
			'note'    => sprintf(
				/* translators: 1: book name, 2: chapter, 3: verse */
				__( 'Read the verse immediately after this lesson (%1$s %2$d:%3$d) to continue the passage.', 'the-hidden-word' ),
				$book_name,
				$chapter,
				$after
			),
		);

		return $verses;
	}

	/**
	 * Cached public-domain text for echo verses (WEB/KJV).
	 *
	 * @var array<string, array<string, string>>|null
	 */
	private static $echo_verses = null;

	/**
	 * Load echo verse text cache.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function load_echo_verses() {
		if ( null !== self::$echo_verses ) {
			return self::$echo_verses;
		}

		$path = THW_PLUGIN_DIR . 'data/echo-verses.json';
		if ( ! is_readable( $path ) ) {
			self::$echo_verses = array();
			return self::$echo_verses;
		}

		$data = json_decode( file_get_contents( $path ), true );
		self::$echo_verses = is_array( $data ) ? $data : array();

		return self::$echo_verses;
	}

	/**
	 * Get cached echo verse text for public-domain translations.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation slug.
	 * @return array{text:string,translation:string}|null
	 */
	public static function get_echo_verse_text( $book_id, $chapter, $verse, $translation ) {
		$book_id     = (int) $book_id;
		$chapter     = (int) $chapter;
		$verse       = (int) $verse;
		$translation = strtolower( $translation );

		if ( $book_id < 1 || $chapter < 1 || $verse < 1 ) {
			return null;
		}

		if ( 'niv' === $translation ) {
			return null;
		}

		if ( ! in_array( $translation, array( 'web', 'kjv' ), true ) ) {
			$translation = 'web';
		}

		$key  = $book_id . '-' . $chapter . '-' . $verse;
		$data = self::load_echo_verses();

		if ( empty( $data[ $key ][ $translation ] ) ) {
			if ( 'web' !== $translation && ! empty( $data[ $key ]['web'] ) ) {
				$translation = 'web';
			} else {
				return null;
			}
		}

		return array(
			'text'        => $data[ $key ][ $translation ],
			'translation' => $translation,
		);
	}

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
