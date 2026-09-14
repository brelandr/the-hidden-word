<?php
/**
 * Personal Bible reading notes (custom table + REST).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Bible_Notes
 */
class HWBL_Bible_Notes {

	const DB_VERSION = '1.1.0';
	const OPT_DB     = 'hwbl_bible_notes_db_version';
	const MAX_LENGTH = 10000;

	/**
	 * Allowed note frameworks.
	 *
	 * @return string[]
	 */
	public static function frameworks() {
		return array( 'free', 'soap', 'hear', 'inductive' );
	}

	/**
	 * Framework field labels for UI/API.
	 *
	 * @return array<string, array<int, array{key:string,label:string}>>
	 */
	public static function framework_fields() {
		return array(
			'free'      => array(
				array(
					'key'   => 'body',
					'label' => __( 'Note', 'hidden-word-bible-lessons' ),
				),
			),
			'soap'      => array(
				array(
					'key'   => 'scripture',
					'label' => __( 'Scripture', 'hidden-word-bible-lessons' ),
				),
				array(
					'key'   => 'observation',
					'label' => __( 'Observation', 'hidden-word-bible-lessons' ),
				),
				array(
					'key'   => 'application',
					'label' => __( 'Application', 'hidden-word-bible-lessons' ),
				),
				array(
					'key'   => 'prayer',
					'label' => __( 'Prayer', 'hidden-word-bible-lessons' ),
				),
			),
			'hear'      => array(
				array(
					'key'   => 'highlight',
					'label' => __( 'Highlight', 'hidden-word-bible-lessons' ),
				),
				array(
					'key'   => 'explain',
					'label' => __( 'Explain', 'hidden-word-bible-lessons' ),
				),
				array(
					'key'   => 'apply',
					'label' => __( 'Apply', 'hidden-word-bible-lessons' ),
				),
				array(
					'key'   => 'respond',
					'label' => __( 'Respond', 'hidden-word-bible-lessons' ),
				),
			),
			'inductive' => array(
				array(
					'key'   => 'says',
					'label' => __( 'What does it say?', 'hidden-word-bible-lessons' ),
				),
				array(
					'key'   => 'means',
					'label' => __( 'What does it mean?', 'hidden-word-bible-lessons' ),
				),
				array(
					'key'   => 'forme',
					'label' => __( 'What does it mean for me?', 'hidden-word-bible-lessons' ),
				),
			),
		);
	}

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'hwbl_bible_notes';
	}

	/**
	 * Create/upgrade table.
	 */
	public static function maybe_install() {
		if ( (string) get_option( self::OPT_DB, '' ) === self::DB_VERSION ) {
			return;
		}
		self::install();
		update_option( self::OPT_DB, self::DB_VERSION, false );
	}

	/**
	 * dbDelta install.
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
			verse int(11) NOT NULL DEFAULT 0,
			note longtext NOT NULL,
			framework varchar(32) NOT NULL DEFAULT 'free',
			sections longtext NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_ref (user_id, book_id, chapter, verse),
			KEY user_updated (user_id, updated_at),
			KEY book_chapter (book_id, chapter)
		) {$charset};";
		dbDelta( $sql );
	}

	/**
	 * Drop table (uninstall).
	 */
	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		delete_option( self::OPT_DB );
	}

	/**
	 * Sanitize framework key.
	 *
	 * @param mixed $value Raw.
	 * @return string
	 */
	public static function sanitize_framework( $value ) {
		$key = sanitize_key( (string) $value );
		return in_array( $key, self::frameworks(), true ) ? $key : 'free';
	}

	/**
	 * Sanitize sections map.
	 *
	 * @param mixed  $sections Raw sections.
	 * @param string $framework Framework key.
	 * @return array<string, string>
	 */
	public static function sanitize_sections( $sections, $framework ) {
		$out    = array();
		$fields = self::framework_fields();
		$defs   = isset( $fields[ $framework ] ) ? $fields[ $framework ] : $fields['free'];
		$raw    = is_array( $sections ) ? $sections : array();
		foreach ( $defs as $field ) {
			$key         = $field['key'];
			$out[ $key ] = isset( $raw[ $key ] ) ? sanitize_textarea_field( (string) $raw[ $key ] ) : '';
		}
		return $out;
	}

	/**
	 * Build display note text from sections.
	 *
	 * @param string               $framework Framework.
	 * @param array<string,string> $sections  Sections.
	 * @param string               $fallback  Free-text fallback.
	 * @return string
	 */
	public static function compose_note_text( $framework, $sections, $fallback = '' ) {
		if ( 'free' === $framework ) {
			if ( isset( $sections['body'] ) && '' !== trim( (string) $sections['body'] ) ) {
				return trim( (string) $sections['body'] );
			}
			return trim( (string) $fallback );
		}
		$fields = self::framework_fields();
		$defs   = isset( $fields[ $framework ] ) ? $fields[ $framework ] : array();
		$parts  = array();
		foreach ( $defs as $field ) {
			$key   = $field['key'];
			$value = isset( $sections[ $key ] ) ? trim( (string) $sections[ $key ] ) : '';
			if ( '' === $value ) {
				continue;
			}
			$parts[] = $field['label'] . ":\n" . $value;
		}
		return implode( "\n\n", $parts );
	}

	/**
	 * REST routes.
	 */
	public static function register_routes() {
		$auth = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};

		$ref_args = array(
			'book_id' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
			'chapter' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
			'verse'   => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			),
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/notes',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_get' ),
					'permission_callback' => $auth,
					'args'                => array(
						'book_id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'chapter' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'verse'   => array(
							'description'       => 'When set (including 0 for chapter notes), also returns matching note.',
							'type'              => 'integer',
							'sanitize_callback' => static function ( $value ) {
								if ( null === $value || '' === $value ) {
									return null;
								}
								return (int) $value;
							},
							'default'           => null,
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_save' ),
					'permission_callback' => $auth,
					'args'                => $ref_args + array(
						'note'       => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_textarea_field',
							'default'           => '',
						),
						'framework'  => array(
							'type'              => 'string',
							'sanitize_callback' => array( __CLASS__, 'sanitize_framework' ),
							'default'           => 'free',
						),
						'sections'   => array(
							'type'    => 'object',
							'default' => array(),
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'rest_delete' ),
					'permission_callback' => $auth,
					'args'                => $ref_args,
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/notes/timeline',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_timeline' ),
				'permission_callback' => $auth,
				'args'                => array(
					'page'     => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 1,
					),
					'per_page' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 40,
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/notes/frameworks',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_frameworks' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Framework catalog for clients.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_frameworks() {
		$labels = array(
			'free'      => __( 'Free note', 'hidden-word-bible-lessons' ),
			'soap'      => __( 'S.O.A.P.', 'hidden-word-bible-lessons' ),
			'hear'      => __( 'H.E.A.R.', 'hidden-word-bible-lessons' ),
			'inductive' => __( 'Inductive', 'hidden-word-bible-lessons' ),
		);
		$out    = array();
		foreach ( self::frameworks() as $key ) {
			$out[] = array(
				'id'     => $key,
				'label'  => isset( $labels[ $key ] ) ? $labels[ $key ] : $key,
				'fields' => self::framework_fields()[ $key ],
			);
		}
		return rest_ensure_response( array( 'frameworks' => $out ) );
	}

	/**
	 * Format a note row for API responses.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public static function format_note( $row ) {
		$book_id   = (int) $row['book_id'];
		$chapter   = (int) $row['chapter'];
		$verse     = (int) $row['verse'];
		$framework = self::sanitize_framework( isset( $row['framework'] ) ? $row['framework'] : 'free' );
		$sections  = array();
		if ( ! empty( $row['sections'] ) ) {
			$decoded = json_decode( (string) $row['sections'], true );
			if ( is_array( $decoded ) ) {
				$sections = self::sanitize_sections( $decoded, $framework );
			}
		}
		$ref = '';
		if ( class_exists( 'HWBL_Books' ) ) {
			if ( $verse > 0 ) {
				$ref = HWBL_Books::format_reference( $book_id, $chapter, $verse, 0 );
			} else {
				$name = HWBL_Books::get_name( $book_id );
				$ref  = $name ? ( $name . ' ' . $chapter ) : ( 'Book ' . $book_id . ' ' . $chapter );
			}
		}

		return array(
			'id'         => isset( $row['id'] ) ? (int) $row['id'] : 0,
			'book_id'    => $book_id,
			'chapter'    => $chapter,
			'verse'      => $verse,
			'reference'  => $ref,
			'note'       => (string) $row['note'],
			'framework'  => $framework,
			'sections'   => $sections,
			'updated_at' => (string) $row['updated_at'],
		);
	}

	/**
	 * Select columns including framework fields.
	 *
	 * @return string
	 */
	private static function select_cols() {
		return 'id, book_id, chapter, verse, note, framework, sections, updated_at';
	}

	/**
	 * Get one note.
	 *
	 * @param int $user_id User.
	 * @param int $book_id Book.
	 * @param int $chapter Chapter.
	 * @param int $verse   Verse (0 = chapter note).
	 * @return array<string, mixed>|null
	 */
	public static function get_note( $user_id, $book_id, $chapter, $verse = 0 ) {
		global $wpdb;
		$table = self::table_name();
		$cols  = self::select_cols();
		$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT {$cols} FROM {$table} WHERE user_id = %d AND book_id = %d AND chapter = %d AND verse = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $user_id,
				(int) $book_id,
				(int) $chapter,
				(int) $verse
			),
			ARRAY_A
		);
		return is_array( $row ) ? self::format_note( $row ) : null;
	}

	/**
	 * Notes for a chapter (including chapter-level verse=0).
	 *
	 * @param int $user_id User.
	 * @param int $book_id Book.
	 * @param int $chapter Chapter.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_for_chapter( $user_id, $book_id, $chapter ) {
		global $wpdb;
		$table = self::table_name();
		$cols  = self::select_cols();
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT {$cols} FROM {$table} WHERE user_id = %d AND book_id = %d AND chapter = %d ORDER BY verse ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $user_id,
				(int) $book_id,
				(int) $chapter
			),
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = self::format_note( $row );
		}
		return $out;
	}

	/**
	 * Upsert or delete when empty.
	 *
	 * @param int                  $user_id    User.
	 * @param int                  $book_id    Book.
	 * @param int                  $chapter    Chapter.
	 * @param int                  $verse      Verse.
	 * @param string               $note       Text.
	 * @param string               $framework  Framework.
	 * @param array<string,string> $sections   Sections.
	 * @return array{ok:bool,deleted?:bool,note?:array<string,mixed>}
	 */
	public static function save_note( $user_id, $book_id, $chapter, $verse, $note, $framework = 'free', $sections = array() ) {
		$framework = self::sanitize_framework( $framework );
		$sections  = self::sanitize_sections( $sections, $framework );
		$composed  = self::compose_note_text( $framework, $sections, $note );
		$composed  = trim( $composed );

		if ( '' === $composed ) {
			self::delete_note( $user_id, $book_id, $chapter, $verse );
			return array(
				'ok'      => true,
				'deleted' => true,
			);
		}

		if ( strlen( $composed ) > self::MAX_LENGTH ) {
			$composed = substr( $composed, 0, self::MAX_LENGTH );
		}

		global $wpdb;
		$table = self::table_name();
		$now   = current_time( 'mysql', true );
		$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array(
				'user_id'    => (int) $user_id,
				'book_id'    => (int) $book_id,
				'chapter'    => (int) $chapter,
				'verse'      => (int) $verse,
				'note'       => $composed,
				'framework'  => $framework,
				'sections'   => wp_json_encode( $sections ),
				'updated_at' => $now,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		$saved = self::get_note( $user_id, $book_id, $chapter, $verse );
		return array(
			'ok'   => true,
			'note' => $saved,
		);
	}

	/**
	 * Delete a note.
	 *
	 * @param int $user_id User.
	 * @param int $book_id Book.
	 * @param int $chapter Chapter.
	 * @param int $verse   Verse.
	 * @return bool
	 */
	public static function delete_note( $user_id, $book_id, $chapter, $verse ) {
		global $wpdb;
		$table  = self::table_name();
		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			array(
				'user_id' => (int) $user_id,
				'book_id' => (int) $book_id,
				'chapter' => (int) $chapter,
				'verse'   => (int) $verse,
			),
			array( '%d', '%d', '%d', '%d' )
		);
		return false !== $result;
	}

	/**
	 * GET note(s).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_get( $request ) {
		$user_id = get_current_user_id();
		$book_id = (int) $request->get_param( 'book_id' );
		$chapter = (int) $request->get_param( 'chapter' );
		$verse   = $request->get_param( 'verse' );

		if ( $book_id < 1 || $chapter < 1 ) {
			return new WP_Error(
				'hwbl_bible_notes_ref',
				__( 'book_id and chapter are required.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$notes = self::get_for_chapter( $user_id, $book_id, $chapter );
		$out   = array( 'notes' => $notes );

		if ( null !== $verse && '' !== $verse ) {
			$verse_num = (int) $verse;
			$match     = null;
			foreach ( $notes as $row ) {
				if ( (int) $row['verse'] === $verse_num ) {
					$match = $row;
					break;
				}
			}
			$out['note'] = $match;
		}

		return rest_ensure_response( $out );
	}

	/**
	 * POST save note.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_save( $request ) {
		$params    = $request->get_json_params();
		$params    = is_array( $params ) ? $params : array();
		$book_id   = isset( $params['book_id'] ) ? absint( $params['book_id'] ) : absint( $request->get_param( 'book_id' ) );
		$chapter   = isset( $params['chapter'] ) ? absint( $params['chapter'] ) : absint( $request->get_param( 'chapter' ) );
		$verse     = isset( $params['verse'] ) ? absint( $params['verse'] ) : absint( $request->get_param( 'verse' ) );
		$note      = isset( $params['note'] )
			? sanitize_textarea_field( (string) $params['note'] )
			: sanitize_textarea_field( (string) $request->get_param( 'note' ) );
		$framework = isset( $params['framework'] )
			? self::sanitize_framework( $params['framework'] )
			: self::sanitize_framework( $request->get_param( 'framework' ) );
		$sections  = isset( $params['sections'] ) && is_array( $params['sections'] ) ? $params['sections'] : array();

		if ( $book_id < 1 || $chapter < 1 ) {
			return new WP_Error(
				'hwbl_bible_notes_ref',
				__( 'book_id and chapter are required.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$result = self::save_note( get_current_user_id(), $book_id, $chapter, $verse, $note, $framework, $sections );
		return rest_ensure_response( $result );
	}

	/**
	 * DELETE note.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_delete( $request ) {
		$book_id = (int) $request->get_param( 'book_id' );
		$chapter = (int) $request->get_param( 'chapter' );
		$verse   = (int) $request->get_param( 'verse' );

		if ( $book_id < 1 || $chapter < 1 ) {
			return new WP_Error(
				'hwbl_bible_notes_ref',
				__( 'book_id and chapter are required.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		self::delete_note( get_current_user_id(), $book_id, $chapter, $verse );
		return rest_ensure_response(
			array(
				'ok'      => true,
				'deleted' => true,
			)
		);
	}

	/**
	 * GET chronological timeline.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_timeline( $request ) {
		global $wpdb;
		$table    = self::table_name();
		$cols     = self::select_cols();
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT {$cols} FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				get_current_user_id(),
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = self::format_note( $row );
		}

		return rest_ensure_response(
			array(
				'notes' => $out,
				'page'  => $page,
			)
		);
	}
}
