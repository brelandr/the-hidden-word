<?php
/**
 * Lesson discussion journal (custom table + REST + UI).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Journal_Store
 */
class HWBL_Journal_Store {

	const DB_VERSION = '1.0.0';
	const OPT_DB     = 'hwbl_journal_db_version';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_shortcode( 'hwbl_my_journal', array( __CLASS__, 'render_my_journal' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'hwbl_journal';
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
			lesson_id bigint(20) unsigned NOT NULL,
			question_index int(11) NOT NULL DEFAULT 0,
			response longtext NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_lesson_q (user_id, lesson_id, question_index),
			KEY lesson_id (lesson_id),
			KEY updated_at (updated_at)
		) {$charset};";
		dbDelta( $sql );
	}

	/**
	 * Assets.
	 */
	public static function register_assets() {
		wp_register_script(
			'hwbl-journal',
			HWBL_PLUGIN_URL . 'public/js/journal.js',
			array(),
			HWBL_VERSION,
			true
		);
	}

	/**
	 * Enqueue journal autosave.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public static function enqueue_for_lesson( $lesson_id ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		self::register_assets();
		wp_enqueue_script( 'hwbl-journal' );
		wp_localize_script(
			'hwbl-journal',
			'hwblJournal',
			array(
				'restUrl'  => esc_url_raw( rest_url( 'hwbl/v1/journal/' . (int) $lesson_id ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'lessonId' => (int) $lesson_id,
			)
		);
	}

	/**
	 * REST.
	 */
	public static function register_routes() {
		$auth = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};

		register_rest_route(
			'hwbl/v1',
			'/journal/(?P<lesson_id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_get' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_save' ),
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/journal',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_timeline' ),
				'permission_callback' => $auth,
			)
		);
	}

	/**
	 * Entries for lesson.
	 *
	 * @param int $user_id   User.
	 * @param int $lesson_id Lesson.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_for_lesson( $user_id, $lesson_id ) {
		global $wpdb;
		$table = self::table_name();
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT lesson_id, question_index, response, updated_at FROM {$table} WHERE user_id = %d AND lesson_id = %d ORDER BY question_index ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $user_id,
				(int) $lesson_id
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Upsert entry.
	 *
	 * @param int    $user_id   User.
	 * @param int    $lesson_id Lesson.
	 * @param int    $index     Question index.
	 * @param string $response  Text.
	 * @return bool
	 */
	public static function save_entry( $user_id, $lesson_id, $index, $response ) {
		global $wpdb;
		$table = self::table_name();
		$now   = current_time( 'mysql', true );
		$result = $wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array(
				'user_id'        => (int) $user_id,
				'lesson_id'      => (int) $lesson_id,
				'question_index' => (int) $index,
				'response'       => $response,
				'updated_at'     => $now,
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);
		return false !== $result;
	}

	/**
	 * GET lesson journal.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_get( $request ) {
		$lesson_id = (int) $request['lesson_id'];
		$entries   = self::get_for_lesson( get_current_user_id(), $lesson_id );
		return rest_ensure_response( array( 'entries' => $entries ) );
	}

	/**
	 * POST save entry.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_save( $request ) {
		$lesson_id = (int) $request['lesson_id'];
		$params    = $request->get_json_params();
		$params    = is_array( $params ) ? $params : $request->get_params();
		$index     = isset( $params['question_index'] ) ? absint( $params['question_index'] ) : 0;
		$response  = isset( $params['response'] ) ? sanitize_textarea_field( (string) $params['response'] ) : '';
		self::save_entry( get_current_user_id(), $lesson_id, $index, $response );
		return rest_ensure_response(
			array(
				'ok'             => true,
				'lesson_id'      => $lesson_id,
				'question_index' => $index,
				'response'       => $response,
			)
		);
	}

	/**
	 * GET chronological timeline.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_timeline() {
		global $wpdb;
		$table = self::table_name();
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT lesson_id, question_index, response, updated_at FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 200", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				get_current_user_id()
			),
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $row ) {
			$lesson_id = (int) $row['lesson_id'];
			$out[]     = array(
				'lesson_id'      => $lesson_id,
				'question_index' => (int) $row['question_index'],
				'response'       => (string) $row['response'],
				'updated_at'     => (string) $row['updated_at'],
				'lesson_title'   => get_the_title( $lesson_id ),
			);
		}
		return rest_ensure_response( array( 'entries' => $out ) );
	}

	/**
	 * My Journal shortcode.
	 *
	 * @return string
	 */
	public static function render_my_journal() {
		if ( ! is_user_logged_in() ) {
			return '<p class="hwbl-empty">' . esc_html__( 'Sign in to view your journal.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		$request  = new WP_REST_Request( 'GET' );
		$response = self::rest_timeline();
		$data     = $response->get_data();
		$entries  = isset( $data['entries'] ) ? $data['entries'] : array();
		ob_start();
		echo '<div class="hwbl-my-journal"><h3>' . esc_html__( 'My Journal', 'hidden-word-bible-lessons' ) . '</h3>';
		if ( ! $entries ) {
			echo '<p class="hwbl-empty">' . esc_html__( 'No journal entries yet.', 'hidden-word-bible-lessons' ) . '</p>';
		} else {
			echo '<ol class="hwbl-my-journal__list">';
			foreach ( $entries as $entry ) {
				echo '<li><strong>' . esc_html( (string) ( $entry['lesson_title'] ?: ( 'Lesson #' . $entry['lesson_id'] ) ) ) . '</strong>';
				echo ' <span class="hwbl-my-journal__meta">Q' . esc_html( (string) ( (int) $entry['question_index'] + 1 ) ) . '</span>';
				echo '<p>' . esc_html( (string) $entry['response'] ) . '</p></li>';
			}
			echo '</ol>';
		}
		echo '</div>';
		return (string) ob_get_clean();
	}
}
