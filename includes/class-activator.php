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

		self::maybe_upgrade_curriculum();

		if ( ! get_option( 'thw_schedule_mode' ) ) {
			update_option( 'thw_schedule_mode', 'week' );
		}

		if ( ! get_option( 'thw_active_translation' ) ) {
			update_option( 'thw_active_translation', 'niv' );
		}
	}

	/**
	 * Seed new bundled lessons when the curriculum expands on upgrade.
	 */
	public static function maybe_upgrade_curriculum() {
		$installed = get_option( 'thw_curriculum_version', '' );
		$target    = THW_CURRICULUM_DB_VERSION;

		if ( ! get_option( 'thw_seeded' ) || version_compare( $installed, $target, '<' ) ) {
			self::migrate_legacy_lesson_numbers();
			$created = self::seed_lessons();
			update_option( 'thw_seeded', true );
			update_option( 'thw_curriculum_version', $target );

			if ( $created > 0 ) {
				set_transient( 'thw_curriculum_upgraded', $created, MINUTE_IN_SECONDS * 5 );
			}
		}
	}

	/**
	 * Copy legacy week numbers into lesson numbers for older installs.
	 */
	public static function migrate_legacy_lesson_numbers() {
		$lessons = get_posts(
			array(
				'post_type'      => 'thw_lesson',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_thw_week_number',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_thw_lesson_number',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_thw_lesson_number',
							'value'   => 0,
							'compare' => '=',
							'type'    => 'NUMERIC',
						),
					),
				),
			)
		);

		foreach ( $lessons as $lesson_id ) {
			$week = (int) get_post_meta( $lesson_id, '_thw_week_number', true );
			if ( $week > 0 ) {
				update_post_meta( $lesson_id, '_thw_lesson_number', $week );
			}
		}
	}

	/**
	 * Ensure bundled NIV verse count stays within Biblica fair-use limits.
	 */
	public static function validate_bundled_verse_count() {
		require_once THW_PLUGIN_DIR . 'includes/class-curriculum.php';

		$niv_verses = THW_Curriculum::count_verses();
		if ( $niv_verses > THW_MAX_NIV_VERSES ) {
			wp_die(
				esc_html(
					sprintf(
						/* translators: 1: bundled NIV verse count, 2: maximum allowed */
						__( 'The Hidden Word: bundled NIV verse count (%1$d) exceeds the %2$d verse fair-use limit.', 'the-hidden-word' ),
						$niv_verses,
						THW_MAX_NIV_VERSES
					)
				)
			);
		}

		$niv_count = THW_Curriculum::get_lesson_count();
		$kjv_count = count( THW_Curriculum::load_kjv() );
		if ( $kjv_count > 0 && $kjv_count !== $niv_count ) {
			wp_die(
				esc_html(
					sprintf(
						/* translators: 1: NIV lesson count, 2: KJV lesson count */
						__( 'The Hidden Word: KJV curriculum (%2$d lessons) must match the NIV curriculum (%1$d lessons).', 'the-hidden-word' ),
						$niv_count,
						$kjv_count
					)
				)
			);
		}
	}

	/**
	 * Seed bundled lessons from curriculum JSON.
	 *
	 * @return int Number of lessons created.
	 */
	public static function seed_lessons() {
		$niv_path = THW_PLUGIN_DIR . 'data/niv-curriculum.json';
		if ( ! is_readable( $niv_path ) ) {
			return 0;
		}

		$curriculum = json_decode( file_get_contents( $niv_path ), true );
		if ( ! is_array( $curriculum ) ) {
			return 0;
		}

		$books   = THW_Books::get_all();
		$created = 0;

		foreach ( $curriculum as $entry ) {
			$lesson = THW_Curriculum::get_entry_lesson_number( $entry );
			if ( $lesson < 1 ) {
				continue;
			}

			$existing = get_posts(
				array(
					'post_type'      => 'thw_lesson',
					'posts_per_page' => 1,
					'meta_key'       => '_thw_lesson_number',
					'meta_value'     => $lesson,
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
						/* translators: 1: lesson number, 2: scripture reference */
						__( 'Lesson %1$d: %2$s', 'the-hidden-word' ),
						$lesson,
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
			update_post_meta( $post_id, '_thw_lesson_number', $lesson );
			update_post_meta( $post_id, '_thw_week_number', $lesson );

			if ( ! empty( $entry['historical_context'] ) ) {
				update_post_meta( $post_id, '_thw_historical_context', wp_kses_post( $entry['historical_context'] ) );
			}
			if ( ! empty( $entry['preceding_narrative'] ) ) {
				update_post_meta( $post_id, '_thw_preceding_narrative', wp_kses_post( $entry['preceding_narrative'] ) );
			}
			if ( ! empty( $entry['discussion_questions'] ) ) {
				update_post_meta( $post_id, '_thw_discussion_questions', wp_json_encode( $entry['discussion_questions'] ) );
			}

			$created++;
		}

		return $created;
	}
}
