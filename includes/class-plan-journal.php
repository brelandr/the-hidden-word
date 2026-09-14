<?php
/**
 * Reading-plan day journal + pastoral AI replies with crisis guards.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Plan_Journal
 */
class HWBL_Plan_Journal {

	const DB_VERSION  = '1.1.0';
	const OPT_DB      = 'hwbl_plan_journal_db_version';
	const RATE_LIMIT  = 12;
	const RATE_WINDOW = 3600;

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
		return $wpdb->prefix . 'hwbl_plan_journal';
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
			plan_id bigint(20) unsigned NOT NULL,
			day_num int(11) NOT NULL,
			entry longtext NOT NULL,
			ai_reply longtext NULL,
			flagged_crisis tinyint(1) NOT NULL DEFAULT 0,
			share_with_leader tinyint(1) NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_plan_day (user_id, plan_id, day_num),
			KEY updated_at (updated_at),
			KEY plan_shared (plan_id, share_with_leader, day_num)
		) {$charset};";
		dbDelta( $sql );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$logged_in = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};
		$id_day    = array(
			'id'  => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
			'day' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);

		register_rest_route(
			'hwbl/v1',
			'/plans/(?P<id>\d+)/days/(?P<day>\d+)/journal',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_list' ),
					'permission_callback' => $logged_in,
					'args'                => $id_day,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_create' ),
					'permission_callback' => $logged_in,
					'args'                => $id_day + array(
						'entry'             => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_textarea_field',
						),
						'share_with_leader' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/plans/(?P<id>\d+)/journal/shared',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_leader_inbox' ),
				'permission_callback' => array( __CLASS__, 'can_view_shared' ),
				'args'                => array(
					'id'       => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'day'      => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
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
			'/plans/(?P<id>\d+)/days/(?P<day>\d+)/journal/(?P<entry_id>\d+)/ask',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_ask' ),
				'permission_callback' => $logged_in,
				'args'                => $id_day + array(
					'entry_id'  => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'tradition' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/plans/journal',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_timeline' ),
				'permission_callback' => $logged_in,
				'args'                => array(
					'plan_id'  => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
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
			'/plans/(?P<id>\d+)/journal',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_timeline_for_plan' ),
				'permission_callback' => $logged_in,
				'args'                => array(
					'id'       => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
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
	 * Format entry row.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public static function format_entry( array $row ) {
		$crisis  = ! empty( $row['flagged_crisis'] );
		$reply   = (string) ( $row['ai_reply'] ?? '' );
		$plan_id = (int) ( $row['plan_id'] ?? 0 );
		$day_num = (int) ( $row['day_num'] ?? 0 );
		$plan_url = $plan_id > 0 ? (string) get_permalink( $plan_id ) : '';
		$user_id = (int) ( $row['user_id'] ?? 0 );
		$user    = $user_id ? get_userdata( $user_id ) : null;
		return array(
			'id'                => (int) ( $row['id'] ?? 0 ),
			'plan_id'           => $plan_id,
			'plan_title'        => $plan_id > 0 ? (string) get_the_title( $plan_id ) : '',
			'day_num'           => $day_num,
			'entry'             => (string) ( $row['entry'] ?? '' ),
			'ai_reply'          => $reply,
			'ai_reply_html'     => $crisis && class_exists( 'HWBL_Crisis_Guard' )
				? HWBL_Crisis_Guard::helpline_html()
				: ( $reply ? wpautop( esc_html( $reply ) ) : '' ),
			'flagged_crisis'    => $crisis,
			'share_with_leader' => ! empty( $row['share_with_leader'] ),
			'user_id'           => $user_id,
			'user_display'      => $user ? (string) $user->display_name : '',
			'updated_at'        => (string) ( $row['updated_at'] ?? '' ),
			'plan_url'          => $plan_url,
			'day_url'           => ( $plan_url && $day_num > 0 )
				? add_query_arg( 'hwbl_plan_day', $day_num, $plan_url )
				: $plan_url,
		);
	}

	/**
	 * Whether current user can view shared plan reflections.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function can_view_shared( $request ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
			return false;
		}
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}
		$plan_id = (int) $request['id'];
		if ( $plan_id < 1 ) {
			return false;
		}
		if ( (int) get_post_field( 'post_author', $plan_id ) === get_current_user_id() ) {
			return true;
		}
		if ( class_exists( 'THW_Premium_Cohort' ) ) {
			// Leaders with cohort edit capability can read shared reflections.
			return current_user_can( 'edit_thw_cohorts' );
		}
		return false;
	}

	/**
	 * Timeline of journal/Ask entries for the current user.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_timeline( $request ) {
		return self::timeline_response(
			get_current_user_id(),
			(int) $request->get_param( 'plan_id' ),
			(int) $request->get_param( 'page' ),
			(int) $request->get_param( 'per_page' )
		);
	}

	/**
	 * Timeline for a single plan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_timeline_for_plan( $request ) {
		$plan_id = (int) $request['id'];
		$post    = get_post( $plan_id );
		if ( ! $post || HWBL_CPT_Plan::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'hwbl_plan_not_found', __( 'Plan not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		return self::timeline_response(
			get_current_user_id(),
			$plan_id,
			(int) $request->get_param( 'page' ),
			(int) $request->get_param( 'per_page' )
		);
	}

	/**
	 * Build paginated timeline response.
	 *
	 * @param int $user_id  User ID.
	 * @param int $plan_id  Optional plan filter (0 = all).
	 * @param int $page     Page.
	 * @param int $per_page Per page.
	 * @return WP_REST_Response
	 */
	public static function timeline_response( $user_id, $plan_id = 0, $page = 1, $per_page = 40 ) {
		$user_id  = (int) $user_id;
		$plan_id  = (int) $plan_id;
		$page     = max( 1, (int) $page );
		$per_page = max( 1, min( 100, (int) $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		global $wpdb;
		$table = self::table_name();
		if ( $plan_id > 0 ) {
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND plan_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id,
					$plan_id
				)
			);
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE user_id = %d AND plan_id = %d ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id,
					$plan_id,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		} else {
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id
				)
			);
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		}

		$entries = array();
		foreach ( (array) $rows as $row ) {
			$entries[] = self::format_entry( $row );
		}

		return rest_ensure_response(
			array(
				'entries'  => $entries,
				'total'    => $total,
				'page'     => $page,
				'per_page' => $per_page,
			)
		);
	}

	/**
	 * List journal entries for a plan day (current user).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_list( $request ) {
		$plan_id = (int) $request['id'];
		$day_num = (int) $request['day'];
		if ( ! HWBL_CPT_Plan::get_day( $plan_id, $day_num ) ) {
			return new WP_Error( 'hwbl_plan_day_not_found', __( 'Plan day not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table_name() . ' WHERE user_id = %d AND plan_id = %d AND day_num = %d ORDER BY id ASC', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				get_current_user_id(),
				$plan_id,
				$day_num
			),
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = self::format_entry( $row );
		}
		return rest_ensure_response( array( 'entries' => $out ) );
	}

	/**
	 * Leader inbox: shared reflections for a plan.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_leader_inbox( $request ) {
		$plan_id  = (int) $request['id'];
		$day_num  = (int) $request->get_param( 'day' );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset   = ( $page - 1 ) * $per_page;
		if ( ! HWBL_CPT_Plan::get_plan_data( $plan_id ) ) {
			return new WP_Error( 'hwbl_plan_not_found', __( 'Plan not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		global $wpdb;
		$table = self::table_name();
		if ( $day_num > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE plan_id = %d AND share_with_leader = 1 AND day_num = %d ORDER BY updated_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$plan_id,
					$day_num,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE plan_id = %d AND share_with_leader = 1 ORDER BY day_num ASC, updated_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$plan_id,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		}
		$out = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$out[] = self::format_entry( $row );
		}
		return rest_ensure_response( array( 'entries' => $out ) );
	}

	/**
	 * Create a journal entry.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_create( $request ) {
		$plan_id = (int) $request['id'];
		$day_num = (int) $request['day'];
		$entry   = trim( (string) $request->get_param( 'entry' ) );
		if ( ! HWBL_CPT_Plan::get_day( $plan_id, $day_num ) ) {
			return new WP_Error( 'hwbl_plan_day_not_found', __( 'Plan day not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		if ( '' === $entry ) {
			return new WP_Error( 'hwbl_journal_empty', __( 'Please write something first.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$crisis = class_exists( 'HWBL_Crisis_Guard' ) && HWBL_Crisis_Guard::is_crisis( $entry );
		$reply  = $crisis && class_exists( 'HWBL_Crisis_Guard' ) ? HWBL_Crisis_Guard::helpline_plain() : null;
		$share  = ! empty( $request->get_param( 'share_with_leader' ) );

		global $wpdb;
		$now = current_time( 'mysql' );
		$ok  = $wpdb->insert(
			self::table_name(),
			array(
				'user_id'           => get_current_user_id(),
				'plan_id'           => $plan_id,
				'day_num'           => $day_num,
				'entry'             => $entry,
				'ai_reply'          => $reply,
				'flagged_crisis'    => $crisis ? 1 : 0,
				'share_with_leader' => $share ? 1 : 0,
				'updated_at'        => $now,
			),
			array( '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s' )
		);
		if ( ! $ok ) {
			return new WP_Error( 'hwbl_journal_save_failed', __( 'Could not save journal entry.', 'hidden-word-bible-lessons' ), array( 'status' => 500 ) );
		}

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE id = %d', (int) $wpdb->insert_id ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);
		return rest_ensure_response( self::format_entry( is_array( $row ) ? $row : array() ) );
	}

	/**
	 * Generate a pastoral AI reply for a journal entry.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_ask( $request ) {
		$plan_id  = (int) $request['id'];
		$day_num  = (int) $request['day'];
		$entry_id = (int) $request['entry_id'];
		$day      = HWBL_CPT_Plan::get_day( $plan_id, $day_num );
		$plan     = HWBL_CPT_Plan::get_plan_data( $plan_id );
		if ( ! $day || ! $plan ) {
			return new WP_Error( 'hwbl_plan_day_not_found', __( 'Plan day not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table_name() . ' WHERE id = %d AND user_id = %d AND plan_id = %d AND day_num = %d LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$entry_id,
				get_current_user_id(),
				$plan_id,
				$day_num
			),
			ARRAY_A
		);
		if ( ! is_array( $row ) ) {
			return new WP_Error( 'hwbl_journal_not_found', __( 'Journal entry not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		$entry_text = (string) $row['entry'];
		if ( class_exists( 'HWBL_Crisis_Guard' ) && HWBL_Crisis_Guard::is_crisis( $entry_text ) ) {
			$reply = HWBL_Crisis_Guard::helpline_plain();
			$wpdb->update(
				self::table_name(),
				array(
					'ai_reply'       => $reply,
					'flagged_crisis' => 1,
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'id' => $entry_id ),
				array( '%s', '%d', '%s' ),
				array( '%d' )
			);
			$row['ai_reply']       = $reply;
			$row['flagged_crisis'] = 1;
			return rest_ensure_response( self::format_entry( $row ) );
		}

		if ( ! empty( $row['ai_reply'] ) && empty( $row['flagged_crisis'] ) ) {
			return rest_ensure_response( self::format_entry( $row ) );
		}

		if ( ! function_exists( 'thw_premium_ai_frontend_available' ) || ! thw_premium_ai_frontend_available() ) {
			return new WP_Error( 'thw_ai_disabled', __( 'AI guidance is not enabled on this site.', 'hidden-word-bible-lessons' ), array( 'status' => 403 ) );
		}
		if ( ! class_exists( 'THW_Premium_AI_Client' ) ) {
			return new WP_Error( 'thw_ai_disabled', __( 'AI client is not available.', 'hidden-word-bible-lessons' ), array( 'status' => 403 ) );
		}
		if ( ! self::check_rate_limit( get_current_user_id() ) ) {
			return new WP_Error( 'thw_ai_rate_limit', __( 'Hourly AI guidance limit reached.', 'hidden-word-bible-lessons' ), array( 'status' => 429 ) );
		}

		$tradition = sanitize_key( (string) $request->get_param( 'tradition' ) );
		$context   = (string) ( $day['verse_ref'] ?? '' ) . ' ' . (string) ( $plan['topic'] ?? '' ) . ' ' . $entry_text;
		$rules     = '';
		if ( function_exists( 'thw_premium_resolve_explain_rules_for_request' ) ) {
			$resolved  = thw_premium_resolve_explain_rules_for_request( $tradition, $context );
			$tradition = sanitize_key( (string) ( $resolved['preset'] ?? $tradition ) );
			$rules     = (string) ( $resolved['rules'] ?? '' );
		}
		$system = function_exists( 'thw_premium_build_ai_system_instruction' )
			? thw_premium_build_ai_system_instruction( $rules )
			: $rules;
		if ( class_exists( 'HWBL_Crisis_Guard' ) ) {
			$system .= "\n\n" . HWBL_Crisis_Guard::ai_system_addendum();
		}
		$system .= "\n\nYou are responding privately to a believer's journal reflection during a Bible reading plan. Answer from Scripture with warmth and practical application. Keep the reply to 2–4 short paragraphs of plain text (no HTML or markdown). AI is not a substitute for pastoral care or professional counseling.";

		$prompt  = "Reading plan: " . (string) ( $plan['title'] ?? '' ) . ' (' . (string) ( $plan['topic'] ?? '' ) . ")\n";
		$prompt .= 'Day ' . $day_num . ': ' . (string) ( $day['title'] ?? '' ) . "\n";
		$prompt .= 'Verse: ' . (string) ( $day['verse_ref'] ?? '' ) . "\n";
		if ( ! empty( $day['verse_text'] ) ) {
			$prompt .= 'Verse text: ' . (string) $day['verse_text'] . "\n";
		}
		$prompt .= "\nJournal entry / question:\n" . $entry_text . "\n";
		$prompt .= "\nWrite a biblical, pastoral response that helps them apply God's Word to what they shared.";

		$result = THW_Premium_AI_Client::generate_text( $prompt, $system );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'thw_ai_generate_failed', $result->get_error_message(), array( 'status' => 502 ) );
		}

		$reply = trim( wp_strip_all_tags( (string) $result ) );
		if ( '' === $reply ) {
			return new WP_Error( 'thw_ai_generate_failed', __( 'Could not generate guidance.', 'hidden-word-bible-lessons' ), array( 'status' => 502 ) );
		}

		// Second-pass crisis check on model output is unnecessary; re-check user text only.
		self::increment_rate_limit( get_current_user_id() );
		$wpdb->update(
			self::table_name(),
			array(
				'ai_reply'       => $reply,
				'flagged_crisis' => 0,
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $entry_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);
		$row['ai_reply']       = $reply;
		$row['flagged_crisis'] = 0;
		return rest_ensure_response( self::format_entry( $row ) );
	}

	/**
	 * Rate limit check.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function check_rate_limit( $user_id ) {
		$key  = 'hwbl_plan_journal_rl_' . (int) $user_id;
		$hits = (int) get_transient( $key );
		return $hits < self::RATE_LIMIT;
	}

	/**
	 * Increment rate limit.
	 *
	 * @param int $user_id User ID.
	 */
	private static function increment_rate_limit( $user_id ) {
		$key  = 'hwbl_plan_journal_rl_' . (int) $user_id;
		$hits = (int) get_transient( $key );
		set_transient( $key, $hits + 1, self::RATE_WINDOW );
	}
}
