<?php
/**
 * Curriculum search and scoring helpers for AI Study Finder.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_AI_Study_Finder_Search
 */
class THW_Premium_AI_Study_Finder_Search {

	/**
	 * Best-effort map a reference string to an existing lesson permalink.
	 *
	 * @param string $reference Reference like "Matthew 19:3-9".
	 * @return string
	 */
	public static function find_lesson_url_for_reference( $reference ) {
		$needle = strtolower( preg_replace( '/\s+/', ' ', trim( (string) $reference ) ) );
		if ( '' === $needle || ! class_exists( 'HWBL_Curriculum' ) || ! class_exists( 'HWBL_Books' ) ) {
			return '';
		}

		foreach ( HWBL_Curriculum::load_niv() as $entry ) {
			$lesson_number = HWBL_Curriculum::get_entry_lesson_number( $entry );
			if ( $lesson_number < 1 ) {
				continue;
			}

			$book_id     = isset( $entry['book_id'] ) ? (int) $entry['book_id'] : 0;
			$chapter     = isset( $entry['chapter'] ) ? (int) $entry['chapter'] : 0;
			$verse_start = isset( $entry['verse_start'] ) ? (int) $entry['verse_start'] : 0;
			$verse_end   = isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : $verse_start;
			if ( $book_id < 1 || $chapter < 1 || $verse_start < 1 ) {
				continue;
			}

			$formatted = strtolower(
				preg_replace(
					'/\s+/',
					' ',
					HWBL_Books::format_reference( $book_id, $chapter, $verse_start, $verse_end )
				)
			);

			$exact_start = strtolower(
				preg_replace(
					'/\s+/',
					' ',
					HWBL_Books::format_reference( $book_id, $chapter, $verse_start, $verse_start )
				)
			);

			if ( $needle !== $formatted && $needle !== $exact_start && false === strpos( $needle, $exact_start ) ) {
				continue;
			}

			if ( class_exists( 'HWBL_Scheduler' ) ) {
				$post_id = HWBL_Scheduler::get_lesson_id_by_number( $lesson_number );
				if ( $post_id ) {
					$url = get_permalink( $post_id );
					return $url ? (string) $url : '';
				}
			}
		}

		return '';
	}

	/**
	 * Suggest related curriculum lessons for a topic or question.
	 *
	 * @param string $keywords Topic or question text.
	 * @param int    $limit    Max lessons.
	 * @return array<int, array<string, mixed>>
	 */
	public static function suggest_related_lessons( $keywords, $limit = 5 ) {
		$candidates = self::search_curriculum_grounded( $keywords, max( 5, (int) $limit ) );
		if ( empty( $candidates ) ) {
			$candidates = self::search_curriculum( $keywords, max( 5, (int) $limit ) );
		}
		$lessons    = array();

		foreach ( $candidates as $row ) {
			if ( count( $lessons ) >= max( 1, (int) $limit ) ) {
				break;
			}
			$entry         = isset( $row['entry'] ) && is_array( $row['entry'] ) ? $row['entry'] : array();
			$lesson_number = isset( $row['lesson_number'] ) ? (int) $row['lesson_number'] : 0;
			$formatted     = self::format_result_from_entry( $entry, $lesson_number );
			if ( ! $formatted ) {
				continue;
			}
			$lessons[] = array(
				'lesson_id'     => isset( $formatted['lesson_id'] ) ? (int) $formatted['lesson_id'] : 0,
				'lesson_number' => $formatted['lesson_number'],
				'reference'     => $formatted['reference'],
				'excerpt'       => $formatted['excerpt'],
				'url'           => $formatted['url'],
				'title'         => isset( $entry['title'] ) ? sanitize_text_field( (string) $entry['title'] ) : $formatted['reference'],
				'book_id'       => isset( $formatted['book_id'] ) ? (int) $formatted['book_id'] : 0,
				'chapter'       => isset( $formatted['chapter'] ) ? (int) $formatted['chapter'] : 0,
				'verse'         => isset( $formatted['verse'] ) ? (int) $formatted['verse'] : 0,
			);
		}

		return $lessons;
	}

	/**
	 * Search bundled curriculum by keywords (kept for tests / internal tools).
	 *
	 * @param string $keywords Keyword string.
	 * @param int    $limit    Max candidates.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_curriculum( $keywords, $limit = 15 ) {
		return THW_Premium_Study_Search::search_curriculum( $keywords, $limit );
	}

	/**
	 * Embeddings-aware curriculum search (falls back to keywords).
	 *
	 * @param string $keywords Keyword string.
	 * @param int    $limit    Max candidates.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_curriculum_grounded( $keywords, $limit = 15 ) {
		return THW_Premium_Study_Search::search_curriculum_grounded( $keywords, $limit );
	}

	/**
	 * Parse keyword string into terms.
	 *
	 * @param string $keywords Keywords.
	 * @return array<int, string>
	 */
	public static function parse_keywords( $keywords ) {
		return THW_Premium_Study_Search::parse_keywords( $keywords );
	}

	/**
	 * Score a curriculum entry against search terms.
	 *
	 * @param array<string, mixed> $entry Curriculum row.
	 * @param array<int, string>   $terms Search terms.
	 * @return int
	 */
	public static function score_entry( $entry, $terms ) {
		return THW_Premium_Study_Search::score_entry( $entry, $terms );
	}

	/**
	 * Build a result row from a curriculum entry.
	 *
	 * @param array<string, mixed> $entry         Curriculum row.
	 * @param int                  $lesson_number Lesson number.
	 * @param string               $rationale     Optional rationale.
	 * @return array<string, mixed>|null
	 */
	public static function format_result_from_entry( $entry, $lesson_number, $rationale = '' ) {
		$book_id     = isset( $entry['book_id'] ) ? (int) $entry['book_id'] : 0;
		$chapter     = isset( $entry['chapter'] ) ? (int) $entry['chapter'] : 0;
		$verse_start = isset( $entry['verse_start'] ) ? (int) $entry['verse_start'] : 0;
		$verse_end   = isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : $verse_start;

		if ( $book_id < 1 || $chapter < 1 || $verse_start < 1 ) {
			return null;
		}

		$reference = HWBL_Books::format_reference( $book_id, $chapter, $verse_start, $verse_end );
		$excerpt   = isset( $entry['text'] ) ? wp_trim_words( wp_strip_all_tags( (string) $entry['text'] ), 24, '…' ) : '';
		$url       = '';

		$post_id = 0;
		if ( class_exists( 'HWBL_Scheduler' ) ) {
			$post_id = (int) HWBL_Scheduler::get_lesson_id_by_number( $lesson_number );
			if ( $post_id ) {
				$url = get_permalink( $post_id );
			}
		}

		return array(
			'lesson_id'     => $post_id,
			'lesson_number' => $lesson_number,
			'reference'     => $reference,
			'excerpt'       => $excerpt,
			'url'           => $url ? (string) $url : '',
			'rationale'     => $rationale,
			'book_id'       => $book_id,
			'chapter'       => $chapter,
			'verse'         => $verse_start,
			'verse_end'     => $verse_end,
		);
	}

	/**
	 * Parse AI JSON ranking response (legacy helper for tests).
	 *
	 * @param string $raw Raw AI response.
	 * @return array<int, array<string, mixed>>
	 */
	public static function parse_ai_ranking( $raw ) {
		$raw = trim( (string) $raw );
		if ( preg_match( '/\[[\s\S]*\]/', $raw, $matches ) ) {
			$raw = $matches[0];
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		$rows = array();
		foreach ( $data as $item ) {
			if ( ! is_array( $item ) || empty( $item['lesson_number'] ) ) {
				continue;
			}
			$rows[] = array(
				'lesson_number' => (int) $item['lesson_number'],
				'rationale'     => isset( $item['rationale'] ) ? (string) $item['rationale'] : '',
			);
		}

		return $rows;
	}
}
