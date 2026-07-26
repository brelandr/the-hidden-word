<?php
/**
 * Bulk CSV import for custom reading tracks.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Track_Import
 */
class THW_Premium_Track_Import {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'admin_post_thw_import_track_csv', array( __CLASS__, 'handle_import' ) );
	}

	/**
	 * Handle CSV upload and import.
	 */
	public static function handle_import() {
		check_admin_referer( 'thw_import_track_csv' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		if ( empty( $_FILES['track_csv']['tmp_name'] ) || empty( $_FILES['track_csv']['name'] ) ) {
			wp_safe_redirect( add_query_arg( 'thw_track_import', 'missing', wp_get_referer() ) );
			exit;
		}

		$tmp_name = (string) $_FILES['track_csv']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated via is_uploaded_file().
		$name     = sanitize_file_name( wp_unslash( (string) $_FILES['track_csv']['name'] ) );
		$ext      = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

		if ( ! is_uploaded_file( $tmp_name ) || 'csv' !== $ext ) {
			wp_safe_redirect( add_query_arg( 'thw_track_import', 'error', wp_get_referer() ) );
			exit;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp_filesystem;
		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			wp_safe_redirect( add_query_arg( 'thw_track_import', 'error', wp_get_referer() ) );
			exit;
		}

		$contents = $wp_filesystem->get_contents( $tmp_name );
		if ( false === $contents || '' === $contents ) {
			wp_safe_redirect( add_query_arg( 'thw_track_import', 'error', wp_get_referer() ) );
			exit;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $contents );
		if ( ! is_array( $lines ) || empty( $lines ) ) {
			wp_safe_redirect( add_query_arg( 'thw_track_import', 'error', wp_get_referer() ) );
			exit;
		}

		$header_line = array_shift( $lines );
		$header      = str_getcsv( (string) $header_line );
		$columns     = self::map_columns( is_array( $header ) ? $header : array() );
		$updated     = 0;

		foreach ( $lines as $line ) {
			if ( '' === trim( (string) $line ) ) {
				continue;
			}
			$row = str_getcsv( (string) $line );
			if ( ! is_array( $row ) ) {
				continue;
			}

			$lesson_id = self::resolve_lesson_id_from_row( $row, $columns );
			if ( ! $lesson_id ) {
				continue;
			}

			$track_order = isset( $row[ $columns['track_order'] ] ) ? absint( $row[ $columns['track_order'] ] ) : 0;
			if ( $track_order < 1 ) {
				continue;
			}

			update_post_meta( $lesson_id, '_hwbl_track_order', $track_order );
			++$updated;
		}

		THW_Premium_Scheduler::rebuild_custom_track_option();

		wp_safe_redirect( add_query_arg( 'thw_track_import', 'ok-' . $updated, wp_get_referer() ) );
		exit;
	}

	/**
	 * Map CSV header columns to indexes.
	 *
	 * @param array<int, string> $header Header row.
	 * @return array{lesson_number: int, reference: int, track_order: int}
	 */
	public static function map_columns( $header ) {
		$columns = array(
			'lesson_number' => 0,
			'reference'     => -1,
			'track_order'   => 1,
		);

		foreach ( $header as $index => $label ) {
			$key = strtolower( trim( (string) $label ) );
			if ( in_array( $key, array( 'lesson_number', 'lesson', 'number' ), true ) ) {
				$columns['lesson_number'] = (int) $index;
			} elseif ( in_array( $key, array( 'reference', 'ref' ), true ) ) {
				$columns['reference'] = (int) $index;
			} elseif ( in_array( $key, array( 'track_order', 'order', 'position' ), true ) ) {
				$columns['track_order'] = (int) $index;
			}
		}

		return $columns;
	}

	/**
	 * Resolve lesson post ID from a CSV row.
	 *
	 * @param array<int, string> $row     Data row.
	 * @param array<string, int> $columns Column map.
	 * @return int
	 */
	public static function resolve_lesson_id_from_row( $row, $columns ) {
		if ( $columns['reference'] >= 0 && ! empty( $row[ $columns['reference'] ] ) ) {
			$lesson_id = self::get_lesson_id_by_reference( trim( (string) $row[ $columns['reference'] ] ) );
			if ( $lesson_id ) {
				return $lesson_id;
			}
		}

		$lesson_number = isset( $row[ $columns['lesson_number'] ] ) ? absint( $row[ $columns['lesson_number'] ] ) : 0;
		if ( $lesson_number < 1 ) {
			return 0;
		}

		return (int) HWBL_Scheduler::get_lesson_id_by_number( $lesson_number );
	}

	/**
	 * Find lesson ID by scripture reference string.
	 *
	 * @param string $reference Reference like "John 3:16".
	 * @return int
	 */
	public static function get_lesson_id_by_reference( $reference ) {
		$reference = trim( $reference );
		if ( '' === $reference ) {
			return 0;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'     => '_hwbl_reference',
						'value'   => $reference,
						'compare' => '=',
					),
				),
			)
		);

		if ( ! empty( $query->posts[0] ) ) {
			return (int) $query->posts[0];
		}

		$parts = self::parse_reference( $reference );
		if ( ! $parts ) {
			return 0;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'hwbl_lesson',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_hwbl_book_id',
						'value' => $parts['book_id'],
					),
					array(
						'key'   => '_hwbl_chapter',
						'value' => $parts['chapter'],
					),
					array(
						'key'   => '_hwbl_verse_start',
						'value' => $parts['verse_start'],
					),
				),
			)
		);

		return ! empty( $query->posts[0] ) ? (int) $query->posts[0] : 0;
	}

	/**
	 * Parse a reference string into book/chapter/verse.
	 *
	 * @param string $reference Reference.
	 * @return array{book_id: int, chapter: int, verse_start: int}|null
	 */
	public static function parse_reference( $reference ) {
		if ( ! preg_match( '/^(.+?)\s+(\d+):(\d+)/', $reference, $matches ) ) {
			return null;
		}

		$book_name = trim( $matches[1] );
		$book_id   = HWBL_Books::get_id_by_name( $book_name );
		if ( ! $book_id ) {
			return null;
		}

		return array(
			'book_id'     => (int) $book_id,
			'chapter'     => (int) $matches[2],
			'verse_start' => (int) $matches[3],
		);
	}
}
