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

	/** Transient prefix for cached embedding vectors (keyed by content hash). */
	const EMBED_TRANSIENT_PREFIX = 'hwbl_emb_';

	/** Minimum cosine similarity to keep an embeddings match. */
	const EMBED_MIN_SCORE = 0.28;

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
	 * Search curriculum using embeddings similarity when available.
	 *
	 * Prefers embedding re-rank of keyword candidates; falls back to keyword-only
	 * when embeddings APIs / keys are missing.
	 *
	 * @param string $keywords Keyword / topic string.
	 * @param int    $limit    Max candidates.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_curriculum_grounded( $keywords, $limit = 15 ) {
		$limit = max( 1, (int) $limit );

		if ( ! class_exists( 'THW_Premium_AI_Client' ) || ! THW_Premium_AI_Client::supports_embeddings() ) {
			return self::search_curriculum( $keywords, $limit );
		}

		$query_vector = self::get_or_create_embedding( (string) $keywords );
		if ( is_wp_error( $query_vector ) || empty( $query_vector ) ) {
			return self::search_curriculum( $keywords, $limit );
		}

		// Seed with keyword hits so we only embed a practical subset.
		$candidates = self::search_curriculum( $keywords, max( 25, $limit * 3 ) );
		if ( empty( $candidates ) ) {
			// No keyword overlap — sample a broader curriculum slice for semantic match.
			$candidates = self::curriculum_sample_for_embeddings( 40 );
		}

		$scored = array();
		foreach ( $candidates as $row ) {
			$entry = isset( $row['entry'] ) && is_array( $row['entry'] ) ? $row['entry'] : array();
			if ( empty( $entry ) ) {
				continue;
			}
			$text = self::entry_embedding_text( $entry );
			if ( '' === $text ) {
				continue;
			}
			$vector = self::get_or_create_embedding( $text );
			if ( is_wp_error( $vector ) || empty( $vector ) ) {
				continue;
			}
			$sim = THW_Premium_AI_Client::cosine_similarity( $query_vector, $vector );
			if ( $sim < self::EMBED_MIN_SCORE ) {
				continue;
			}
			$scored[] = array(
				'lesson_number' => isset( $row['lesson_number'] ) ? (int) $row['lesson_number'] : HWBL_Curriculum::get_entry_lesson_number( $entry ),
				'score'         => (int) round( $sim * 1000 ),
				'similarity'    => $sim,
				'entry'         => $entry,
				'source'        => 'embeddings',
			);
		}

		if ( empty( $scored ) ) {
			return self::search_curriculum( $keywords, $limit );
		}

		usort(
			$scored,
			static function ( $a, $b ) {
				return ( $b['similarity'] ?? 0 ) <=> ( $a['similarity'] ?? 0 );
			}
		);

		return array_slice( $scored, 0, $limit );
	}

	/**
	 * Build plain text used for curriculum embeddings.
	 *
	 * @param array<string, mixed> $entry Curriculum row.
	 * @return string
	 */
	public static function entry_embedding_text( $entry ) {
		$parts = array();
		foreach ( array( 'text', 'historical_context', 'preceding_narrative' ) as $field ) {
			if ( ! empty( $entry[ $field ] ) ) {
				$parts[] = wp_strip_all_tags( (string) $entry[ $field ] );
			}
		}
		if ( ! empty( $entry['discussion_questions'] ) && is_array( $entry['discussion_questions'] ) ) {
			$parts[] = implode( ' ', array_map( 'strval', $entry['discussion_questions'] ) );
		}
		$book_id     = isset( $entry['book_id'] ) ? (int) $entry['book_id'] : 0;
		$chapter     = isset( $entry['chapter'] ) ? (int) $entry['chapter'] : 0;
		$verse_start = isset( $entry['verse_start'] ) ? (int) $entry['verse_start'] : 0;
		$verse_end   = isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : $verse_start;
		if ( $book_id > 0 && $chapter > 0 && $verse_start > 0 && class_exists( 'HWBL_Books' ) ) {
			array_unshift( $parts, HWBL_Books::format_reference( $book_id, $chapter, $verse_start, $verse_end ) );
		}
		return trim( preg_replace( '/\s+/', ' ', implode( ' ', $parts ) ) );
	}

	/**
	 * Content-hash key for an embedding cache entry.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	public static function embedding_cache_key( $text ) {
		return self::EMBED_TRANSIENT_PREFIX . md5( (string) $text );
	}

	/**
	 * Get a cached embedding or generate + store one.
	 *
	 * @param string $text Text to embed.
	 * @return array<int, float>|WP_Error
	 */
	public static function get_or_create_embedding( $text ) {
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return new WP_Error( 'hwbl_empty_embed', 'empty' );
		}
		if ( ! class_exists( 'THW_Premium_AI_Client' ) ) {
			return new WP_Error( 'hwbl_no_ai_client', 'missing' );
		}

		$key    = self::embedding_cache_key( $text );
		$cached = get_transient( $key );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return array_map( 'floatval', array_values( $cached ) );
		}

		// Longer-lived option map (autoload no) keyed by hash — practical, not a vector DB.
		$store = get_option( 'hwbl_study_embeddings', array() );
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		$hash = md5( $text );
		if ( ! empty( $store[ $hash ]['vector'] ) && is_array( $store[ $hash ]['vector'] ) ) {
			$vector = array_map( 'floatval', array_values( $store[ $hash ]['vector'] ) );
			set_transient( $key, $vector, MONTH_IN_SECONDS );
			return $vector;
		}

		$vector = THW_Premium_AI_Client::generate_embedding( $text );
		if ( is_wp_error( $vector ) || ! is_array( $vector ) ) {
			return $vector;
		}

		$vector = array_map( 'floatval', array_values( $vector ) );
		set_transient( $key, $vector, MONTH_IN_SECONDS );

		// Cap option growth — keep most recent ~200 vectors.
		$store[ $hash ] = array(
			'vector' => $vector,
			'ts'     => time(),
		);
		if ( count( $store ) > 200 ) {
			uasort(
				$store,
				static function ( $a, $b ) {
					return (int) ( $b['ts'] ?? 0 ) <=> (int) ( $a['ts'] ?? 0 );
				}
			);
			$store = array_slice( $store, 0, 200, true );
		}
		// Avoid autoload when the installed update_option() accepts $autoload.
		$update_params = ( new ReflectionFunction( 'update_option' ) )->getNumberOfParameters();
		if ( $update_params >= 3 ) {
			update_option( 'hwbl_study_embeddings', $store, false );
		} else {
			update_option( 'hwbl_study_embeddings', $store );
		}

		return $vector;
	}

	/**
	 * Sample curriculum rows when keyword search is empty (embeddings-only path).
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function curriculum_sample_for_embeddings( $limit = 40 ) {
		$out   = array();
		$limit = max( 1, (int) $limit );
		$step  = max( 1, (int) floor( 500 / $limit ) );
		$i     = 0;
		foreach ( HWBL_Curriculum::load_niv() as $entry ) {
			$lesson_number = HWBL_Curriculum::get_entry_lesson_number( $entry );
			if ( $lesson_number < 1 ) {
				continue;
			}
			++$i;
			if ( 0 !== ( $i % $step ) ) {
				continue;
			}
			$out[] = array(
				'lesson_number' => $lesson_number,
				'score'         => 0,
				'entry'         => $entry,
			);
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
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
