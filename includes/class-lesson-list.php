<?php
/**
 * Lesson catalog / browser.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Lesson_List
 */
class HWBL_Lesson_List {

	const INDEX_TRANSIENT = 'hwbl_lesson_index';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'save_post_hwbl_lesson', array( __CLASS__, 'flush_index' ), 20 );
		add_action( 'deleted_post', array( __CLASS__, 'flush_index_on_delete' ) );
	}

	/**
	 * Clear cached lesson index.
	 */
	public static function flush_index() {
		delete_transient( self::INDEX_TRANSIENT );
	}

	/**
	 * Clear index when a lesson post is deleted.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function flush_index_on_delete( $post_id ) {
		if ( 'hwbl_lesson' === get_post_type( $post_id ) ) {
			self::flush_index();
		}
	}

	/**
	 * Build or load cached lesson index rows.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_index() {
		$cached = get_transient( self::INDEX_TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$lesson_ids = get_posts(
			array(
				'post_type'      => array( 'hwbl_lesson', 'thw_lesson' ),
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'orderby'        => 'meta_value_num',
				// Small, transient-cached list (see set_transient() below); meta_key sort is fine here.
				'meta_key'       => '_hwbl_lesson_number', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'order'          => 'ASC',
			)
		);

		$rows = array();
		foreach ( $lesson_ids as $lesson_id ) {
			$data = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
			if ( empty( $data['lesson_number'] ) ) {
				continue;
			}

			$rows[] = array(
				'id'            => (int) $lesson_id,
				'lesson_number' => (int) $data['lesson_number'],
				'title'         => $data['title'],
				'reference'     => $data['reference'],
				'book_id'       => (int) $data['book_id'],
				'permalink'     => get_permalink( $lesson_id ),
				'testament'     => HWBL_Books::get_testament( (int) $data['book_id'] ),
			);
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return $a['lesson_number'] <=> $b['lesson_number'];
			}
		);

		set_transient( self::INDEX_TRANSIENT, $rows, DAY_IN_SECONDS );

		return $rows;
	}

	/**
	 * Filter index rows by shortcode/archive args.
	 *
	 * @param array<string, mixed> $args Display arguments.
	 * @return array<int, array<string, mixed>>
	 */
	public static function filter_rows( $args ) {
		$rows = self::get_index();

		if ( ! empty( $args['book'] ) ) {
			$book = (int) $args['book'];
			$rows = array_values(
				array_filter(
					$rows,
					function ( $row ) use ( $book ) {
						return (int) $row['book_id'] === $book;
					}
				)
			);
		}

		if ( ! empty( $args['testament'] ) && in_array( $args['testament'], array( 'ot', 'nt' ), true ) ) {
			$rows = array_values(
				array_filter(
					$rows,
					function ( $row ) use ( $args ) {
						return $row['testament'] === $args['testament'];
					}
				)
			);
		}

		return $rows;
	}

	/**
	 * Render lesson catalog HTML.
	 *
	 * @param array<string, mixed> $args Display arguments.
	 * @return string
	 */
	public static function render( $args = array() ) {
		$defaults = array(
			'group'    => 'book',
			'book'     => 0,
			'testament'=> '',
			'per_page' => 50,
			'show'     => 'both',
			'page'     => 1,
		);
		$args = wp_parse_args( $args, $defaults );

		$group = in_array( $args['group'], array( 'all', 'book', 'testament' ), true ) ? $args['group'] : 'book';
		$show  = in_array( $args['show'], array( 'reference', 'title', 'both' ), true ) ? $args['show'] : 'both';
		$per_page = max( 1, (int) $args['per_page'] );
		$page     = max( 1, (int) $args['page'] );
		// Read-only pagination pointer, not a form submission — no nonce to verify.
		if ( $page < 2 && isset( $_GET['hwbl_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = max( 1, absint( $_GET['hwbl_page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$rows = self::filter_rows( $args );
		$total = count( $rows );

		if ( 'all' === $group ) {
			$offset     = ( $page - 1 ) * $per_page;
			$page_rows  = array_slice( $rows, $offset, $per_page );
			$grouped    = array( __( 'All Lessons', 'hidden-word-bible-lessons' ) => $page_rows );
			$total_pages = (int) ceil( $total / $per_page );
		} else {
			$grouped = self::group_rows( $rows, $group );
			$total_pages = 1;
			$page = 1;
		}

		ob_start();
		include HWBL_PLUGIN_DIR . 'public/partials/lesson-list.php';
		return ob_get_clean();
	}

	/**
	 * Group rows by book or testament.
	 *
	 * @param array<int, array<string, mixed>> $rows  Lesson rows.
	 * @param string                           $group Grouping mode.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private static function group_rows( $rows, $group ) {
		$grouped = array();

		foreach ( $rows as $row ) {
			if ( 'testament' === $group ) {
				$key = 'ot' === $row['testament']
					? __( 'Old Testament', 'hidden-word-bible-lessons' )
					: __( 'New Testament', 'hidden-word-bible-lessons' );
			} else {
				$key = HWBL_Books::get_name( (int) $row['book_id'] );
			}

			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}
			$grouped[ $key ][] = $row;
		}

		return $grouped;
	}

	/**
	 * Render pagination links for flat list mode.
	 *
	 * @param int   $page        Current page.
	 * @param int   $total_pages Total pages.
	 * @param array $args        Query args to preserve.
	 * @return string
	 */
	public static function render_pagination( $page, $total_pages, $args = array() ) {
		if ( $total_pages <= 1 ) {
			return '';
		}

		$html = '<nav class="hwbl-lesson-list-pagination" aria-label="' . esc_attr__( 'Lesson list pages', 'hidden-word-bible-lessons' ) . '"><ul>';

		for ( $i = 1; $i <= $total_pages; $i++ ) {
			$url = add_query_arg( 'hwbl_page', $i );
			$class     = $i === $page ? ' class="is-current"' : '';
			$html     .= '<li' . $class . '><a href="' . esc_url( $url ) . '">' . esc_html( (string) $i ) . '</a></li>';
		}

		$html .= '</ul></nav>';

		return $html;
	}
}
