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

		$plan_entries = array();
		if ( class_exists( 'HWBL_Plan_Journal' ) ) {
			$plan_resp = HWBL_Plan_Journal::timeline_response( get_current_user_id(), 0, 1, 50 );
			$plan_data = $plan_resp->get_data();
			$plan_entries = isset( $plan_data['entries'] ) && is_array( $plan_data['entries'] ) ? $plan_data['entries'] : array();
		}

		ob_start();
		echo '<div class="hwbl-my-journal">';
		echo '<h3>' . esc_html__( 'My Journal', 'hidden-word-bible-lessons' ) . '</h3>';

		echo '<section class="hwbl-my-journal__section">';
		echo '<h4>' . esc_html__( 'Lesson discussion', 'hidden-word-bible-lessons' ) . '</h4>';
		if ( ! $entries ) {
			echo '<p class="hwbl-empty">' . esc_html__( 'No lesson journal entries yet.', 'hidden-word-bible-lessons' ) . '</p>';
		} else {
			echo '<ol class="hwbl-my-journal__list">';
			foreach ( $entries as $entry ) {
				echo '<li><strong>' . esc_html( (string) ( $entry['lesson_title'] ?: ( 'Lesson #' . $entry['lesson_id'] ) ) ) . '</strong>';
				echo ' <span class="hwbl-my-journal__meta">Q' . esc_html( (string) ( (int) $entry['question_index'] + 1 ) ) . '</span>';
				echo '<p>' . esc_html( (string) $entry['response'] ) . '</p></li>';
			}
			echo '</ol>';
		}
		echo '</section>';

		echo '<section class="hwbl-my-journal__section hwbl-my-journal__plans">';
		echo '<h4>' . esc_html__( 'Reading plan Q&amp;A', 'hidden-word-bible-lessons' ) . '</h4>';
		if ( ! $plan_entries ) {
			echo '<p class="hwbl-empty">' . esc_html__( 'No plan journal or Ask replies yet. Open a reading plan day to save reflections.', 'hidden-word-bible-lessons' ) . '</p>';
		} else {
			echo '<ol class="hwbl-my-journal__list">';
			foreach ( $plan_entries as $entry ) {
				$title = (string) ( $entry['plan_title'] ?? '' );
				$day   = (int) ( $entry['day_num'] ?? 0 );
				$url   = (string) ( $entry['day_url'] ?? $entry['plan_url'] ?? '' );
				$crisis = ! empty( $entry['flagged_crisis'] );
				$export_parts = array();
				if ( $title ) {
					$export_parts[] = $title;
				}
				if ( $day > 0 ) {
					$export_parts[] = sprintf(
						/* translators: %d: day number */
						__( 'Day %d', 'hidden-word-bible-lessons' ),
						$day
					);
				}
				$export_parts[] = __( 'My reflection:', 'hidden-word-bible-lessons' ) . "\n" . (string) ( $entry['entry'] ?? '' );
				if ( ! $crisis && ! empty( $entry['ai_reply'] ) ) {
					$export_parts[] = __( 'Guidance:', 'hidden-word-bible-lessons' ) . "\n" . (string) $entry['ai_reply'];
				}
				$export_text = class_exists( 'HWBL_Share_Links' )
					? HWBL_Share_Links::with_site_attribution( implode( "\n\n", $export_parts ) )
					: implode( "\n\n", $export_parts );
				echo '<li>';
				if ( $url ) {
					echo '<a href="' . esc_url( $url ) . '"><strong>' . esc_html( $title ) . '</strong></a>';
				} else {
					echo '<strong>' . esc_html( $title ) . '</strong>';
				}
				echo ' <span class="hwbl-my-journal__meta">' . esc_html(
					sprintf(
						/* translators: %d: day number */
						__( 'Day %d', 'hidden-word-bible-lessons' ),
						$day
					)
				) . '</span>';
				echo '<p>' . esc_html( (string) ( $entry['entry'] ?? '' ) ) . '</p>';
				if ( ! empty( $entry['ai_reply'] ) && ! $crisis ) {
					echo '<p class="hwbl-my-journal__reply"><em>' . esc_html( (string) $entry['ai_reply'] ) . '</em></p>';
				} elseif ( $crisis && ! empty( $entry['ai_reply_html'] ) ) {
					echo '<div class="hwbl-my-journal__reply">' . wp_kses_post( (string) $entry['ai_reply_html'] ) . '</div>';
				}
				if ( class_exists( 'HWBL_Share_Links' ) && '' !== trim( (string) ( $entry['entry'] ?? '' ) ) ) {
					$dayone   = HWBL_Share_Links::dayone_url( $export_text, array( 'HiddenWord' ) );
					$quillday = HWBL_Share_Links::quillday_url( $export_text, $title );
					echo '<p class="hwbl-my-journal__export">';
					echo '<a class="button" href="' . esc_attr( $dayone ) . '">' . esc_html__( 'Day One', 'hidden-word-bible-lessons' ) . '</a> ';
					echo '<a class="button" href="' . esc_attr( $quillday ) . '">' . esc_html__( 'QuillDay', 'hidden-word-bible-lessons' ) . '</a> ';
					echo '<button type="button" class="button hwbl-journal-export-other" data-export-text="' . esc_attr( $export_text ) . '">' . esc_html__( 'Other journal app', 'hidden-word-bible-lessons' ) . '</button>';
					echo '</p>';
				}
				echo '</li>';
			}
			echo '</ol>';
		}
		echo '</section>';

		echo '</div>';
		echo '<script>(function(){function pref(kind){try{return String(localStorage.getItem(kind==="dayone"?"hwbl_dayone_journal":"hwbl_quillday_journal")||"").trim();}catch(e){return"";}}function withJournal(url,kind){var j=pref(kind);if(!j||!url)return url;return url+(url.indexOf("?")>=0?"&":"?")+"journal="+encodeURIComponent(j);}document.addEventListener("click",function(e){var other=e.target.closest(".hwbl-journal-export-other");if(other){var t=other.getAttribute("data-export-text")||"";if(!t)return;if(navigator.share){navigator.share({text:t}).catch(function(){if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t);}});}else if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t);}return;}var a=e.target.closest("a[href^=\\"dayone:\\"],a[href^=\\"quillday:\\"]");if(!a)return;e.preventDefault();var href=a.getAttribute("href")||"";var kind=href.indexOf("dayone:")===0?"dayone":"quillday";window.location.href=withJournal(href,kind);});})();</script>';
		return (string) ob_get_clean();
	}
}
