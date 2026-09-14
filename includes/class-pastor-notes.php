<?php
/**
 * Church / pastor shared study notes for Bible passages.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Pastor_Notes
 */
class HWBL_Pastor_Notes {

	const DB_VERSION = '1.1.0';
	const OPT_DB     = 'hwbl_pastor_notes_db_version';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		}
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'hwbl_pastor_notes';
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
			book_id int(11) NOT NULL,
			chapter int(11) NOT NULL,
			verse int(11) NOT NULL DEFAULT 0,
			title varchar(255) NOT NULL DEFAULT '',
			body longtext NOT NULL,
			author_id bigint(20) unsigned NOT NULL DEFAULT 0,
			published tinyint(1) NOT NULL DEFAULT 0,
			note_type varchar(32) NOT NULL DEFAULT 'study',
			series varchar(191) NOT NULL DEFAULT '',
			publish_on date NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY book_chapter (book_id, chapter),
			KEY published_ref (published, book_id, chapter, verse),
			KEY series_key (series),
			KEY publish_on (publish_on)
		) {$charset};";
		dbDelta( $sql );
	}

	/**
	 * Drop table.
	 */
	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		delete_option( self::OPT_DB );
	}

	/**
	 * Can edit pastor notes.
	 *
	 * @return bool
	 */
	public static function can_edit() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Format row for API.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public static function format_row( array $row ) {
		$book_id = (int) ( $row['book_id'] ?? 0 );
		$chapter = (int) ( $row['chapter'] ?? 0 );
		$verse   = (int) ( $row['verse'] ?? 0 );
		$ref     = '';
		if ( $book_id && $chapter ) {
			$ref = class_exists( 'HWBL_Books' )
				? ( $verse > 0
					? HWBL_Books::format_reference( $book_id, $chapter, $verse )
					: ( HWBL_Books::get_name( $book_id ) . ' ' . $chapter ) )
				: '';
		}
		return array(
			'id'         => (int) ( $row['id'] ?? 0 ),
			'book_id'    => $book_id,
			'chapter'    => $chapter,
			'verse'      => $verse,
			'title'      => (string) ( $row['title'] ?? '' ),
			'body'       => (string) ( $row['body'] ?? '' ),
			'author_id'  => (int) ( $row['author_id'] ?? 0 ),
			'published'  => ! empty( $row['published'] ),
			'note_type'  => sanitize_key( (string) ( $row['note_type'] ?? 'study' ) ),
			'series'     => (string) ( $row['series'] ?? '' ),
			'publish_on' => ! empty( $row['publish_on'] ) ? (string) $row['publish_on'] : null,
			'reference'  => $ref,
			'updated_at' => (string) ( $row['updated_at'] ?? '' ),
		);
	}

	/**
	 * Whether a published note is visible now.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return bool
	 */
	public static function is_visible_now( array $row ) {
		if ( empty( $row['published'] ) ) {
			return false;
		}
		$on = isset( $row['publish_on'] ) ? (string) $row['publish_on'] : '';
		if ( '' === $on || '0000-00-00' === $on ) {
			return true;
		}
		$today = gmdate( 'Y-m-d' );
		return $on <= $today;
	}

	/**
	 * List published notes for a chapter.
	 *
	 * @param int $book_id Book.
	 * @param int $chapter Chapter.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_published( $book_id, $chapter ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE book_id = %d AND chapter = %d AND published = 1 ORDER BY verse ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $book_id,
				(int) $chapter
			),
			ARRAY_A
		);
		$out = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( self::is_visible_now( $row ) ) {
				$out[] = self::format_row( $row );
			}
		}
		return $out;
	}

	/**
	 * Register REST.
	 */
	public static function register_routes() {
		$can_read = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};
		$can_edit = static function () {
			return self::can_edit();
		};

		register_rest_route(
			'hwbl/v1',
			'/bible/pastor-notes',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_list' ),
					'permission_callback' => $can_read,
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
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_save' ),
					'permission_callback' => $can_edit,
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/pastor-notes/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'rest_delete' ),
					'permission_callback' => $can_edit,
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/bible/pastor-notes/inbox',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_admin_list' ),
				'permission_callback' => $can_edit,
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
	}

	/**
	 * REST list published.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_list( WP_REST_Request $request ) {
		$book_id = (int) $request->get_param( 'book_id' );
		$chapter = (int) $request->get_param( 'chapter' );
		$notes   = self::list_published( $book_id, $chapter );
		if ( ! empty( $notes ) ) {
			self::record_chapter_view( $book_id, $chapter, $notes );
		}
		return rest_ensure_response(
			array(
				'notes' => $notes,
			)
		);
	}

	/**
	 * Privacy-safe engagement: chapter open counts + per-note views.
	 *
	 * @param int                              $book_id Book.
	 * @param int                              $chapter Chapter.
	 * @param array<int, array<string, mixed>> $notes   Notes shown.
	 */
	public static function record_chapter_view( $book_id, $chapter, array $notes ) {
		$key  = (int) $book_id . ':' . (int) $chapter;
		$chap = get_option( 'hwbl_pastor_note_chapter_views', array() );
		if ( ! is_array( $chap ) ) {
			$chap = array();
		}
		$chap[ $key ] = isset( $chap[ $key ] ) ? (int) $chap[ $key ] + 1 : 1;
		update_option( 'hwbl_pastor_note_chapter_views', $chap, false );

		$views = get_option( 'hwbl_pastor_note_views', array() );
		if ( ! is_array( $views ) ) {
			$views = array();
		}
		foreach ( $notes as $note ) {
			$id = (int) ( $note['id'] ?? 0 );
			if ( $id < 1 ) {
				continue;
			}
			$views[ (string) $id ] = isset( $views[ (string) $id ] ) ? (int) $views[ (string) $id ] + 1 : 1;
		}
		update_option( 'hwbl_pastor_note_views', $views, false );
	}

	/**
	 * Get engagement counts for admin.
	 *
	 * @return array{chapter_views:array<string,int>,note_views:array<string,int>}
	 */
	public static function get_engagement() {
		$chap = get_option( 'hwbl_pastor_note_chapter_views', array() );
		$note = get_option( 'hwbl_pastor_note_views', array() );
		return array(
			'chapter_views' => is_array( $chap ) ? $chap : array(),
			'note_views'    => is_array( $note ) ? $note : array(),
		);
	}

	/**
	 * Parse bulk markdown into note drafts.
	 *
	 * Blocks separated by ---. Optional front-matter lines:
	 * @ref John 3:16 | @type intro|study|sermon | @title … | @series … | @publish_on YYYY-MM-DD | @published 1
	 *
	 * @param string $markdown Raw markdown.
	 * @return array{notes:array<int,array<string,mixed>>,errors:string[]}
	 */
	public static function parse_bulk_markdown( $markdown ) {
		$markdown = trim( str_replace( "\r\n", "\n", (string) $markdown ) );
		$errors   = array();
		$notes    = array();
		if ( '' === $markdown ) {
			return array(
				'notes'  => array(),
				'errors' => array( __( 'Paste is empty.', 'hidden-word-bible-lessons' ) ),
			);
		}
		$blocks = preg_split( '/\n---\n/', $markdown );
		foreach ( (array) $blocks as $i => $block ) {
			$block = trim( (string) $block );
			if ( '' === $block ) {
				continue;
			}
			$meta = array(
				'ref'        => '',
				'type'       => 'study',
				'title'      => '',
				'series'     => '',
				'publish_on' => '',
				'published'  => true,
			);
			$lines = explode( "\n", $block );
			$body_lines = array();
			$in_body    = false;
			foreach ( $lines as $line ) {
				if ( ! $in_body && preg_match( '/^@(\w+)\s+(.+)$/', trim( $line ), $m ) ) {
					$key = strtolower( $m[1] );
					$val = trim( $m[2] );
					if ( 'ref' === $key ) {
						$meta['ref'] = $val;
					} elseif ( 'type' === $key ) {
						$meta['type'] = sanitize_key( $val );
					} elseif ( 'title' === $key ) {
						$meta['title'] = $val;
					} elseif ( 'series' === $key ) {
						$meta['series'] = $val;
					} elseif ( 'publish_on' === $key || 'publish' === $key ) {
						$meta['publish_on'] = $val;
					} elseif ( 'published' === $key ) {
						$meta['published'] = in_array( strtolower( $val ), array( '1', 'yes', 'true', 'published' ), true );
					}
					continue;
				}
				// Heading fallback: ## John 3:16
				if ( ! $in_body && '' === $meta['ref'] && preg_match( '/^#{1,3}\s+(.+)$/', trim( $line ), $m ) ) {
					$meta['ref'] = trim( $m[1] );
					continue;
				}
				$in_body      = true;
				$body_lines[] = $line;
			}
			$body = trim( implode( "\n", $body_lines ) );
			if ( '' === $meta['ref'] || '' === $body ) {
				$errors[] = sprintf(
					/* translators: %d: block number */
					__( 'Block %d needs @ref (or ## Reference) and a body.', 'hidden-word-bible-lessons' ),
					$i + 1
				);
				continue;
			}
			$parsed = class_exists( 'HWBL_Bible_Reader' )
				? HWBL_Bible_Reader::parse_reference( $meta['ref'] )
				: null;
			if ( ! is_array( $parsed ) || empty( $parsed['book_id'] ) || empty( $parsed['chapter'] ) ) {
				$errors[] = sprintf(
					/* translators: %s: reference string */
					__( 'Could not parse reference: %s', 'hidden-word-bible-lessons' ),
					$meta['ref']
				);
				continue;
			}
			$notes[] = array(
				'book_id'    => (int) $parsed['book_id'],
				'chapter'    => (int) $parsed['chapter'],
				'verse'      => isset( $parsed['verse'] ) ? (int) $parsed['verse'] : 0,
				'title'      => $meta['title'],
				'body'       => $body,
				'note_type'  => in_array( $meta['type'], array( 'study', 'intro', 'sermon' ), true ) ? $meta['type'] : 'study',
				'series'     => $meta['series'],
				'publish_on' => $meta['publish_on'],
				'published'  => ! empty( $meta['published'] ),
			);
		}
		return array(
			'notes'  => $notes,
			'errors' => $errors,
		);
	}

	/**
	 * Import parsed notes.
	 *
	 * @param array<int, array<string, mixed>> $notes Notes.
	 * @return array{imported:int,errors:string[]}
	 */
	public static function import_notes( array $notes ) {
		$imported = 0;
		$errors   = array();
		foreach ( $notes as $note ) {
			$request = new WP_REST_Request( 'POST' );
			$request->set_body_params( $note );
			$result = self::rest_save( $request );
			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				continue;
			}
			++$imported;
		}
		return array(
			'imported' => $imported,
			'errors'   => $errors,
		);
	}

	/**
	 * REST admin list.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_admin_list( WP_REST_Request $request ) {
		global $wpdb;
		$table    = self::table_name();
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset   = ( $page - 1 ) * $per_page;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY updated_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$per_page,
				$offset
			),
			ARRAY_A
		);
		$out = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$out[] = self::format_row( $row );
		}
		return rest_ensure_response( array( 'notes' => $out ) );
	}

	/**
	 * REST save.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_save( WP_REST_Request $request ) {
		global $wpdb;
		$params    = $request->get_json_params();
		$params    = is_array( $params ) ? $params : array();
		$id        = isset( $params['id'] ) ? absint( $params['id'] ) : absint( $request->get_param( 'id' ) );
		$book_id   = isset( $params['book_id'] ) ? absint( $params['book_id'] ) : absint( $request->get_param( 'book_id' ) );
		$chapter   = isset( $params['chapter'] ) ? absint( $params['chapter'] ) : absint( $request->get_param( 'chapter' ) );
		$verse     = isset( $params['verse'] ) ? absint( $params['verse'] ) : absint( $request->get_param( 'verse' ) );
		$title     = isset( $params['title'] ) ? sanitize_text_field( (string) $params['title'] ) : sanitize_text_field( (string) $request->get_param( 'title' ) );
		$body      = isset( $params['body'] ) ? sanitize_textarea_field( (string) $params['body'] ) : sanitize_textarea_field( (string) $request->get_param( 'body' ) );
		$published = ! empty( $params['published'] ) || ! empty( $request->get_param( 'published' ) );
		$note_type = sanitize_key( isset( $params['note_type'] ) ? (string) $params['note_type'] : (string) $request->get_param( 'note_type' ) );
		$series    = sanitize_text_field( isset( $params['series'] ) ? (string) $params['series'] : (string) $request->get_param( 'series' ) );
		$publish_on = isset( $params['publish_on'] ) ? sanitize_text_field( (string) $params['publish_on'] ) : sanitize_text_field( (string) $request->get_param( 'publish_on' ) );
		if ( ! in_array( $note_type, array( 'study', 'intro', 'sermon' ), true ) ) {
			$note_type = 'study';
		}
		if ( $book_id < 1 || $chapter < 1 || '' === trim( $body ) ) {
			return new WP_Error( 'hwbl_pastor_note_invalid', __( 'Book, chapter, and body are required.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		if ( $publish_on && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $publish_on ) ) {
			$publish_on = '';
		}
		$table = self::table_name();
		$now   = current_time( 'mysql', true );
		$data  = array(
			'book_id'    => $book_id,
			'chapter'    => $chapter,
			'verse'      => $verse,
			'title'      => $title,
			'body'       => $body,
			'author_id'  => get_current_user_id(),
			'published'  => $published ? 1 : 0,
			'note_type'  => $note_type,
			'series'     => $series,
			'publish_on' => $publish_on ? $publish_on : null,
			'updated_at' => $now,
		);
		if ( $id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, $data, array( 'id' => $id ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $table, $data );
			$id = (int) $wpdb->insert_id;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return rest_ensure_response( array( 'note' => self::format_row( is_array( $row ) ? $row : array() ) ) );
	}

	/**
	 * REST delete.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_delete( WP_REST_Request $request ) {
		global $wpdb;
		$id = (int) $request['id'];
		if ( $id < 1 ) {
			return new WP_Error( 'hwbl_pastor_note_invalid', __( 'Invalid note.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
		return rest_ensure_response( array( 'deleted' => true, 'id' => $id ) );
	}

	/**
	 * Admin menu.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=hwbl_lesson',
			__( 'Shared study notes', 'hidden-word-bible-lessons' ),
			__( 'Shared study notes', 'hidden-word-bible-lessons' ),
			'edit_posts',
			'hwbl-pastor-notes',
			array( __CLASS__, 'render_admin' )
		);
	}

	/**
	 * Admin UI.
	 */
	public static function render_admin() {
		if ( ! self::can_edit() ) {
			wp_die( esc_html__( 'You do not have permission to edit shared notes.', 'hidden-word-bible-lessons' ) );
		}
		$notice = '';
		if ( isset( $_POST['hwbl_pastor_notes_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hwbl_pastor_notes_nonce'] ) ), 'hwbl_pastor_notes_save' ) ) {
			$action = isset( $_POST['hwbl_action'] ) ? sanitize_key( wp_unslash( $_POST['hwbl_action'] ) ) : 'save';
			if ( 'import' === $action ) {
				$raw    = isset( $_POST['bulk_markdown'] ) ? wp_unslash( $_POST['bulk_markdown'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$parsed = self::parse_bulk_markdown( is_string( $raw ) ? $raw : '' );
				$result = self::import_notes( $parsed['notes'] );
				$parts  = array();
				if ( $result['imported'] > 0 ) {
					$parts[] = sprintf(
						/* translators: %d: count */
						_n( '%d note imported.', '%d notes imported.', $result['imported'], 'hidden-word-bible-lessons' ),
						$result['imported']
					);
				}
				$errs = array_merge( $parsed['errors'], $result['errors'] );
				if ( ! empty( $errs ) ) {
					$parts[] = implode( ' ', array_slice( $errs, 0, 5 ) );
				}
				$notice = implode( ' ', $parts );
			} elseif ( 'delete' === $action && ! empty( $_POST['note_id'] ) ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( self::table_name(), array( 'id' => absint( $_POST['note_id'] ) ), array( '%d' ) );
				$notice = __( 'Note deleted.', 'hidden-word-bible-lessons' );
			} else {
				$request = new WP_REST_Request( 'POST' );
				$request->set_body_params(
					array(
						'id'         => isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0,
						'book_id'    => isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0,
						'chapter'    => isset( $_POST['chapter'] ) ? absint( $_POST['chapter'] ) : 0,
						'verse'      => isset( $_POST['verse'] ) ? absint( $_POST['verse'] ) : 0,
						'title'      => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
						'body'       => isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '',
						'published'  => ! empty( $_POST['published'] ),
						'note_type'  => isset( $_POST['note_type'] ) ? sanitize_key( wp_unslash( $_POST['note_type'] ) ) : 'study',
						'series'     => isset( $_POST['series'] ) ? sanitize_text_field( wp_unslash( $_POST['series'] ) ) : '',
						'publish_on' => isset( $_POST['publish_on'] ) ? sanitize_text_field( wp_unslash( $_POST['publish_on'] ) ) : '',
					)
				);
				$result = self::rest_save( $request );
				$notice = is_wp_error( $result )
					? $result->get_error_message()
					: __( 'Note saved.', 'hidden-word-bible-lessons' );
			}
		}

		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY updated_at DESC LIMIT 50", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$books = class_exists( 'HWBL_Books' ) ? HWBL_Books::get_all() : array();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Shared study notes', 'hidden-word-bible-lessons' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Publish church commentary for readers. Optional publish date schedules Sunday teaching; series groups sermon notes.', 'hidden-word-bible-lessons' ); ?></p>
			<?php if ( $notice ) : ?>
				<div class="notice notice-info"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>
			<form method="post" style="max-width:720px;margin-bottom:2rem;">
				<?php wp_nonce_field( 'hwbl_pastor_notes_save', 'hwbl_pastor_notes_nonce' ); ?>
				<input type="hidden" name="hwbl_action" value="save" />
				<input type="hidden" name="note_id" value="0" />
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="hwbl_pn_book"><?php esc_html_e( 'Book ID', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<select id="hwbl_pn_book" name="book_id" required>
								<?php foreach ( $books as $id => $name ) : ?>
									<option value="<?php echo esc_attr( (string) $id ); ?>"><?php echo esc_html( $name . ' (' . $id . ')' ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="hwbl_pn_chapter"><?php esc_html_e( 'Chapter', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><input type="number" id="hwbl_pn_chapter" name="chapter" min="1" value="1" required /></td>
					</tr>
					<tr>
						<th><label for="hwbl_pn_verse"><?php esc_html_e( 'Verse (0 = chapter)', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><input type="number" id="hwbl_pn_verse" name="verse" min="0" value="0" /></td>
					</tr>
					<tr>
						<th><label for="hwbl_pn_title"><?php esc_html_e( 'Title', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><input type="text" class="regular-text" id="hwbl_pn_title" name="title" /></td>
					</tr>
					<tr>
						<th><label for="hwbl_pn_body"><?php esc_html_e( 'Body', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><textarea class="large-text" rows="6" id="hwbl_pn_body" name="body" required></textarea></td>
					</tr>
					<tr>
						<th><label for="hwbl_pn_type"><?php esc_html_e( 'Type', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<select id="hwbl_pn_type" name="note_type">
								<option value="study"><?php esc_html_e( 'Study', 'hidden-word-bible-lessons' ); ?></option>
								<option value="intro"><?php esc_html_e( 'Intro', 'hidden-word-bible-lessons' ); ?></option>
								<option value="sermon"><?php esc_html_e( 'Sermon', 'hidden-word-bible-lessons' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="hwbl_pn_series"><?php esc_html_e( 'Series', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><input type="text" class="regular-text" id="hwbl_pn_series" name="series" placeholder="<?php esc_attr_e( 'e.g. John — Believe', 'hidden-word-bible-lessons' ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="hwbl_pn_publish_on"><?php esc_html_e( 'Publish on (optional)', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><input type="date" id="hwbl_pn_publish_on" name="publish_on" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Published', 'hidden-word-bible-lessons' ); ?></th>
						<td><label><input type="checkbox" name="published" value="1" checked /> <?php esc_html_e( 'Visible to readers (when date allows)', 'hidden-word-bible-lessons' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save shared note', 'hidden-word-bible-lessons' ) ); ?>
			</form>

			<form method="post" style="max-width:720px;margin-bottom:2rem;">
				<?php wp_nonce_field( 'hwbl_pastor_notes_save', 'hwbl_pastor_notes_nonce' ); ?>
				<input type="hidden" name="hwbl_action" value="import" />
				<h2><?php esc_html_e( 'Bulk import (markdown)', 'hidden-word-bible-lessons' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Separate notes with a line containing only ---. Each block may start with @ref, @type, @title, @series, @publish_on, @published.', 'hidden-word-bible-lessons' ); ?>
				</p>
				<textarea class="large-text code" rows="10" name="bulk_markdown" placeholder="<?php esc_attr_e( "@ref John 3\n@type intro\n@title Nicodemus night\n@series John — Believe\n@published yes\n\nGod so loved the world…\n\n---\n\n@ref John 3:16\n@type study\n@title Amazing love\n\nBody here…", 'hidden-word-bible-lessons' ); ?>"></textarea>
				<?php submit_button( __( 'Import notes', 'hidden-word-bible-lessons' ), 'secondary' ); ?>
			</form>

			<?php
			$engagement = self::get_engagement();
			$note_views = $engagement['note_views'];
			$chap_views = $engagement['chapter_views'];
			?>
			<h2><?php esc_html_e( 'Engagement (privacy-safe counts)', 'hidden-word-bible-lessons' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Counts how often signed-in readers open church notes for a chapter. No reader identities are stored.', 'hidden-word-bible-lessons' ); ?></p>
			<?php if ( empty( $chap_views ) ) : ?>
				<p><?php esc_html_e( 'No chapter opens recorded yet.', 'hidden-word-bible-lessons' ); ?></p>
			<?php else : ?>
				<ul>
					<?php
					arsort( $chap_views );
					$shown = 0;
					foreach ( $chap_views as $ref_key => $count ) :
						if ( $shown >= 12 ) {
							break;
						}
						++$shown;
						$bits = explode( ':', (string) $ref_key );
						$label = $ref_key;
						if ( 2 === count( $bits ) && class_exists( 'HWBL_Books' ) ) {
							$label = HWBL_Books::get_name( (int) $bits[0] ) . ' ' . (int) $bits[1];
						}
						?>
						<li><?php echo esc_html( $label . ' — ' . (int) $count ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Recent notes', 'hidden-word-bible-lessons' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Reference', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Title', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Series', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Views', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Status', 'hidden-word-bible-lessons' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No shared notes yet.', 'hidden-word-bible-lessons' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $row ) : ?>
							<?php
							$fmt   = self::format_row( $row );
							$views = isset( $note_views[ (string) $fmt['id'] ] ) ? (int) $note_views[ (string) $fmt['id'] ] : 0;
							?>
							<tr>
								<td><?php echo esc_html( $fmt['reference'] ); ?></td>
								<td><?php echo esc_html( $fmt['title'] ?: wp_trim_words( $fmt['body'], 8 ) ); ?></td>
								<td><?php echo esc_html( $fmt['series'] ); ?></td>
								<td><?php echo esc_html( (string) $views ); ?></td>
								<td><?php echo $fmt['published'] ? esc_html__( 'Published', 'hidden-word-bible-lessons' ) : esc_html__( 'Draft', 'hidden-word-bible-lessons' ); ?><?php echo $fmt['publish_on'] ? esc_html( ' · ' . $fmt['publish_on'] ) : ''; ?></td>
								<td>
									<form method="post" style="display:inline;">
										<?php wp_nonce_field( 'hwbl_pastor_notes_save', 'hwbl_pastor_notes_nonce' ); ?>
										<input type="hidden" name="hwbl_action" value="delete" />
										<input type="hidden" name="note_id" value="<?php echo esc_attr( (string) $fmt['id'] ); ?>" />
										<button type="submit" class="button-link-delete"><?php esc_html_e( 'Delete', 'hidden-word-bible-lessons' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hwbl_lesson&page=hwbl-church-study-docs' ) ); ?>"><?php esc_html_e( 'Church study docs', 'hidden-word-bible-lessons' ); ?></a></p>
		</div>
		<?php
	}
}
