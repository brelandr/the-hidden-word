<?php
/**
 * User verse tags for decks / filtering.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Verse_Tags
 */
class HWBL_Verse_Tags {

	const DB_VERSION = '1.0.0';
	const OPT_DB     = 'hwbl_verse_tags_db_version';

	/**
	 * Suggested starter tags.
	 *
	 * @return string[]
	 */
	public static function suggested_tags() {
		return array( 'anxiety', 'grace', 'leadership', 'comfort' );
	}

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Table.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'hwbl_verse_tags';
	}

	/**
	 * Install.
	 */
	public static function maybe_install() {
		if ( (string) get_option( self::OPT_DB, '' ) === self::DB_VERSION ) {
			return;
		}
		self::install();
		update_option( self::OPT_DB, self::DB_VERSION, false );
	}

	/**
	 * dbDelta.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			book_id int(11) NOT NULL,
			chapter int(11) NOT NULL,
			verse int(11) NOT NULL,
			tag varchar(64) NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_ref_tag (user_id, book_id, chapter, verse, tag),
			KEY user_tag (user_id, tag),
			KEY book_chapter (book_id, chapter)
		) {$charset};";
		dbDelta( $sql );
	}

	/**
	 * Drop.
	 */
	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		delete_option( self::OPT_DB );
	}

	/**
	 * Sanitize tag.
	 *
	 * @param mixed $tag Raw.
	 * @return string
	 */
	public static function sanitize_tag( $tag ) {
		$tag = strtolower( sanitize_text_field( (string) $tag ) );
		$tag = preg_replace( '/[^a-z0-9\-_\s]/', '', $tag );
		$tag = trim( preg_replace( '/\s+/', '-', (string) $tag ) );
		return substr( (string) $tag, 0, 64 );
	}

	/**
	 * Format row.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public static function format_row( array $row ) {
		$book_id = (int) ( $row['book_id'] ?? 0 );
		$chapter = (int) ( $row['chapter'] ?? 0 );
		$verse   = (int) ( $row['verse'] ?? 0 );
		return array(
			'id'         => (int) ( $row['id'] ?? 0 ),
			'book_id'    => $book_id,
			'chapter'    => $chapter,
			'verse'      => $verse,
			'tag'        => (string) ( $row['tag'] ?? '' ),
			'reference'  => class_exists( 'HWBL_Books' ) && $book_id && $chapter && $verse
				? HWBL_Books::format_reference( $book_id, $chapter, $verse )
				: '',
			'updated_at' => (string) ( $row['updated_at'] ?? '' ),
		);
	}

	/**
	 * Register REST.
	 */
	public static function register_routes() {
		$logged_in = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};

		register_rest_route(
			'hwbl/v1',
			'/bible/verse-tags',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_list' ),
					'permission_callback' => $logged_in,
					'args'                => array(
						'tag'     => array(
							'type'              => 'string',
							'sanitize_callback' => array( __CLASS__, 'sanitize_tag' ),
							'default'           => '',
						),
						'book_id' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'default'           => 0,
						),
						'chapter' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'default'           => 0,
						),
						'verse'   => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'default'           => 0,
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_add' ),
					'permission_callback' => $logged_in,
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/verse-tags/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'rest_delete' ),
				'permission_callback' => $logged_in,
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/verse-tags/suggestions',
			array(
				'methods'             => 'GET',
				'callback'            => static function () {
					return rest_ensure_response( array( 'tags' => self::suggested_tags() ) );
				},
				'permission_callback' => $logged_in,
			)
		);
	}

	/**
	 * List tags.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_list( WP_REST_Request $request ) {
		global $wpdb;
		$user_id = get_current_user_id();
		$table   = self::table_name();
		$tag     = self::sanitize_tag( $request->get_param( 'tag' ) );
		$book_id = (int) $request->get_param( 'book_id' );
		$chapter = (int) $request->get_param( 'chapter' );
		$verse   = (int) $request->get_param( 'verse' );

		$sql    = "SELECT * FROM {$table} WHERE user_id = %d";
		$params = array( $user_id );
		if ( $tag ) {
			$sql     .= ' AND tag = %s';
			$params[] = $tag;
		}
		if ( $book_id > 0 ) {
			$sql     .= ' AND book_id = %d';
			$params[] = $book_id;
		}
		if ( $chapter > 0 ) {
			$sql     .= ' AND chapter = %d';
			$params[] = $chapter;
		}
		if ( $verse > 0 ) {
			$sql     .= ' AND verse = %d';
			$params[] = $verse;
		}
		$sql .= ' ORDER BY tag ASC, book_id ASC, chapter ASC, verse ASC LIMIT 500';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		$labels = array();
		$items  = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$fmt = self::format_row( $row );
			$items[] = $fmt;
			if ( $fmt['tag'] && ! in_array( $fmt['tag'], $labels, true ) ) {
				$labels[] = $fmt['tag'];
			}
		}
		return rest_ensure_response(
			array(
				'tags'        => $items,
				'labels'      => $labels,
				'items'       => $items,
				'suggestions' => self::suggested_tags(),
			)
		);
	}

	/**
	 * Add tag(s). Accepts single `tag` or `tags` array (replace set for a verse).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_add( WP_REST_Request $request ) {
		global $wpdb;
		$params  = $request->get_json_params();
		$params  = is_array( $params ) ? $params : array();
		$book_id = absint( $params['book_id'] ?? $request->get_param( 'book_id' ) );
		$chapter = absint( $params['chapter'] ?? $request->get_param( 'chapter' ) );
		$verse   = absint( $params['verse'] ?? $request->get_param( 'verse' ) );
		$tags_in = array();
		if ( isset( $params['tags'] ) && is_array( $params['tags'] ) ) {
			foreach ( $params['tags'] as $raw ) {
				$t = self::sanitize_tag( $raw );
				if ( $t ) {
					$tags_in[] = $t;
				}
			}
			$tags_in = array_values( array_unique( $tags_in ) );
		} else {
			$one = self::sanitize_tag( $params['tag'] ?? $request->get_param( 'tag' ) );
			if ( $one ) {
				$tags_in[] = $one;
			}
		}
		if ( $book_id < 1 || $chapter < 1 || $verse < 1 ) {
			return new WP_Error( 'hwbl_tag_invalid', __( 'Book, chapter, and verse are required.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		$table   = self::table_name();
		$user_id = get_current_user_id();
		$now     = current_time( 'mysql', true );

		// Replace mode when `tags` array provided.
		if ( isset( $params['tags'] ) && is_array( $params['tags'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$table,
				array(
					'user_id' => $user_id,
					'book_id' => $book_id,
					'chapter' => $chapter,
					'verse'   => $verse,
				),
				array( '%d', '%d', '%d', '%d' )
			);
		}

		if ( empty( $tags_in ) && ! isset( $params['tags'] ) ) {
			return new WP_Error( 'hwbl_tag_invalid', __( 'Tag is required.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$last = null;
		foreach ( $tags_in as $tag ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$table,
				array(
					'user_id'    => $user_id,
					'book_id'    => $book_id,
					'chapter'    => $chapter,
					'verse'      => $verse,
					'tag'        => $tag,
					'updated_at' => $now,
				)
			);
			$last = $tag;
		}

		if ( isset( $params['tags'] ) && is_array( $params['tags'] ) ) {
			return rest_ensure_response(
				array(
					'tags'        => $tags_in,
					'suggestions' => self::suggested_tags(),
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND book_id = %d AND chapter = %d AND verse = %d AND tag = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$book_id,
				$chapter,
				$verse,
				$last
			),
			ARRAY_A
		);
		return rest_ensure_response( array( 'tag' => self::format_row( is_array( $row ) ? $row : array() ) ) );
	}

	/**
	 * Delete tag row.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_delete( WP_REST_Request $request ) {
		global $wpdb;
		$id = (int) $request['id'];
		if ( $id < 1 ) {
			return new WP_Error( 'hwbl_tag_invalid', __( 'Invalid tag.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table,
			array(
				'id'      => $id,
				'user_id' => get_current_user_id(),
			),
			array( '%d', '%d' )
		);
		return rest_ensure_response( array( 'deleted' => true, 'id' => $id ) );
	}
}
