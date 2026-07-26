<?php
/**
 * Curriculum keyword search (extracted from AI study finder).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Study_Search
 */
class THW_Premium_Study_Search {

	/**
	 * Search bundled curriculum by keywords.
	 *
	 * @param string $keywords Keyword string.
	 * @param int    $limit    Max candidates.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_curriculum( $keywords, $limit = 15 ) {
		$terms = self::parse_keywords( $keywords );
		if ( empty( $terms ) ) {
			return array();
		}

		$scored = array();
		foreach ( HWBL_Curriculum::load_niv() as $entry ) {
			$lesson_number = HWBL_Curriculum::get_entry_lesson_number( $entry );
			if ( $lesson_number < 1 ) {
				continue;
			}

			$score = self::score_entry( $entry, $terms );
			if ( $score < 1 ) {
				continue;
			}

			$scored[] = array(
				'lesson_number' => $lesson_number,
				'score'         => $score,
				'entry'         => $entry,
			);
		}

		usort(
			$scored,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return array_slice( $scored, 0, max( 1, (int) $limit ) );
	}

	/**
	 * Parse keyword string into terms.
	 *
	 * @param string $keywords Keywords.
	 * @return array<int, string>
	 */
	public static function parse_keywords( $keywords ) {
		$parts = preg_split( '/[\s,;]+/', strtolower( trim( $keywords ) ), -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return array();
		}

		$terms = array();
		foreach ( $parts as $part ) {
			if ( strlen( $part ) >= 2 ) {
				$terms[] = $part;
			}
		}

		return array_values( array_unique( $terms ) );
	}

	/**
	 * Score a curriculum entry against search terms.
	 *
	 * @param array<string, mixed> $entry Curriculum row.
	 * @param array<int, string>   $terms Search terms.
	 * @return int
	 */
	public static function score_entry( $entry, $terms ) {
		$fields = array(
			'text'                 => 10,
			'historical_context'   => 5,
			'preceding_narrative'  => 5,
			'discussion_questions' => 8,
		);

		$score = 0;
		foreach ( $fields as $field => $weight ) {
			$haystack = '';
			if ( 'discussion_questions' === $field && ! empty( $entry[ $field ] ) && is_array( $entry[ $field ] ) ) {
				$haystack = strtolower( implode( ' ', $entry[ $field ] ) );
			} elseif ( ! empty( $entry[ $field ] ) ) {
				$haystack = strtolower( wp_strip_all_tags( (string) $entry[ $field ] ) );
			}

			if ( '' === $haystack ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( false !== strpos( $haystack, $term ) ) {
					$score += $weight;
				}
			}
		}

		return $score;
	}
}
