<?php
/**
 * Plugin activation.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Activator
 */
class THW_Activator {

	/**
	 * Activate plugin.
	 */
	public static function activate() {
		self::validate_bundled_verse_count();

		$cpt = new THW_CPT_Lesson();
		$cpt->register_post_type();
		$cpt->register_meta();

		flush_rewrite_rules();

		if ( ! get_option( 'thw_seeded' ) ) {
			self::seed_lessons();
			update_option( 'thw_seeded', true );
		}

		if ( ! get_option( 'thw_schedule_mode' ) ) {
			update_option( 'thw_schedule_mode', 'week' );
		}

		if ( ! get_option( 'thw_active_translation' ) ) {
			update_option( 'thw_active_translation', 'niv' );
		}
	}

	/**
	 * Ensure bundled verse count stays within fair-use limits.
	 */
	public static function validate_bundled_verse_count() {
		$niv_path = THW_PLUGIN_DIR . 'data/niv-curriculum.json';
		$kjv_path = THW_PLUGIN_DIR . 'data/kjv-curriculum.json';

		$total = 0;

		foreach ( array( $niv_path, $kjv_path ) as $path ) {
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$data = json_decode( file_get_contents( $path ), true );
			if ( is_array( $data ) ) {
				$total += count( $data );
			}
		}

		if ( $total > THW_MAX_BUNDLED_VERSES ) {
			wp_die(
				esc_html(
					sprintf(
						/* translators: %d: maximum allowed verse count */
						__( 'The Hidden Word: bundled verse count (%1$d) exceeds the %2$d verse fair-use limit.', 'the-hidden-word' ),
						$total,
						THW_MAX_BUNDLED_VERSES
					)
				)
			);
		}
	}

	/**
	 * Seed 52 draft lessons from curriculum JSON.
	 */
	public static function seed_lessons() {
		$niv_path = THW_PLUGIN_DIR . 'data/niv-curriculum.json';
		if ( ! is_readable( $niv_path ) ) {
			return;
		}

		$curriculum = json_decode( file_get_contents( $niv_path ), true );
		if ( ! is_array( $curriculum ) ) {
			return;
		}

		$books = THW_Books::get_all();

		foreach ( $curriculum as $entry ) {
			$week = isset( $entry['week'] ) ? (int) $entry['week'] : 0;
			if ( $week < 1 ) {
				continue;
			}

			$existing = get_posts(
				array(
					'post_type'      => 'thw_lesson',
					'posts_per_page' => 1,
					'meta_key'       => '_thw_week_number',
					'meta_value'     => $week,
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $existing ) ) {
				continue;
			}

			$book_id = (int) $entry['book_id'];
			$book    = isset( $books[ $book_id ] ) ? $books[ $book_id ] : 'Scripture';

			$ref = $book . ' ' . $entry['chapter'] . ':' . $entry['verse_start'];
			if ( ! empty( $entry['verse_end'] ) && (int) $entry['verse_end'] !== (int) $entry['verse_start'] ) {
				$ref .= '-' . $entry['verse_end'];
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'thw_lesson',
					'post_title'   => sprintf(
						/* translators: 1: week number, 2: scripture reference */
						__( 'Week %1$d: %2$s', 'the-hidden-word' ),
						$week,
						$ref
					),
					'post_status'  => 'publish',
					'post_content' => '',
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_thw_book_id', $book_id );
			update_post_meta( $post_id, '_thw_chapter', (int) $entry['chapter'] );
			update_post_meta( $post_id, '_thw_verse_start', (int) $entry['verse_start'] );
			update_post_meta( $post_id, '_thw_verse_end', isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : (int) $entry['verse_start'] );
			update_post_meta( $post_id, '_thw_week_number', $week );

			if ( ! empty( $entry['historical_context'] ) ) {
				update_post_meta( $post_id, '_thw_historical_context', wp_kses_post( $entry['historical_context'] ) );
			}
			if ( ! empty( $entry['preceding_narrative'] ) ) {
				update_post_meta( $post_id, '_thw_preceding_narrative', wp_kses_post( $entry['preceding_narrative'] ) );
			}
			if ( ! empty( $entry['discussion_questions'] ) ) {
				update_post_meta( $post_id, '_thw_discussion_questions', wp_json_encode( $entry['discussion_questions'] ) );
			}
		}
	}
}
