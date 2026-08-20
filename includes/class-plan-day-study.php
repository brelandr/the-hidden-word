<?php
/**
 * Tradition-aware AI Bible studies for reading-plan days (DB-cached).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Plan_Day_Study
 */
class HWBL_Plan_Day_Study {

	const DB_VERSION = '1.0.0';
	const OPT_DB     = 'hwbl_plan_day_studies_db_version';
	const RATE_LIMIT = 8;
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
		return $wpdb->prefix . 'hwbl_plan_day_studies';
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
			plan_id bigint(20) unsigned NOT NULL,
			day_num int(11) NOT NULL,
			tradition varchar(64) NOT NULL DEFAULT '',
			verse_ref varchar(191) NOT NULL DEFAULT '',
			translation varchar(32) NOT NULL DEFAULT '',
			content longtext NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY plan_day_tradition (plan_id, day_num, tradition),
			KEY tradition (tradition)
		) {$charset};";
		dbDelta( $sql );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$args = array(
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
			'/plans/(?P<id>\d+)/days/(?P<day>\d+)/study',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_get' ),
					'permission_callback' => '__return_true',
					'args'                => $args + array(
						'tradition' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
							'default'           => '',
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_generate' ),
					'permission_callback' => static function () {
						return is_user_logged_in() && current_user_can( 'read' );
					},
					'args'                => $args + array(
						'tradition' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
							'default'           => '',
						),
					),
				),
			)
		);
	}

	/**
	 * Resolve tradition preset for request.
	 *
	 * @param string               $requested Requested slug.
	 * @param array<string, mixed> $day       Day payload.
	 * @param string               $topic     Plan topic.
	 * @return string
	 */
	public static function resolve_tradition( $requested, array $day, $topic = '' ) {
		$context = trim(
			(string) ( $day['verse_ref'] ?? '' ) . ' ' .
			(string) ( $day['title'] ?? '' ) . ' ' .
			wp_strip_all_tags( (string) ( $day['body'] ?? '' ) ) . ' ' .
			(string) $topic
		);
		if ( function_exists( 'thw_premium_resolve_explain_rules_for_request' ) ) {
			$resolved = thw_premium_resolve_explain_rules_for_request( sanitize_key( (string) $requested ), $context );
			return sanitize_key( (string) ( $resolved['preset'] ?? 'general' ) );
		}
		$requested = sanitize_key( (string) $requested );
		return $requested ? $requested : 'general';
	}

	/**
	 * Find cached study.
	 *
	 * @param int    $plan_id   Plan ID.
	 * @param int    $day_num   Day number.
	 * @param string $tradition Tradition slug.
	 * @return array<string, mixed>|null
	 */
	public static function find( $plan_id, $day_num, $tradition ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table_name() . ' WHERE plan_id = %d AND day_num = %d AND tradition = %s LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				(int) $plan_id,
				(int) $day_num,
				sanitize_key( (string) $tradition )
			),
			ARRAY_A
		);
		if ( ! is_array( $row ) ) {
			return null;
		}
		$content = trim( wp_strip_all_tags( (string) ( $row['content'] ?? '' ) ) );
		if ( strlen( $content ) < 40 ) {
			return null;
		}
		return $row;
	}

	/**
	 * Persist study (does not overwrite usable rows).
	 *
	 * @param int    $plan_id     Plan ID.
	 * @param int    $day_num     Day number.
	 * @param string $tradition   Tradition.
	 * @param string $verse_ref   Verse reference.
	 * @param string $translation Translation.
	 * @param string $content     HTML content.
	 * @return array<string, mixed>|null
	 */
	public static function save( $plan_id, $day_num, $tradition, $verse_ref, $translation, $content ) {
		$existing = self::find( $plan_id, $day_num, $tradition );
		if ( $existing ) {
			return $existing;
		}

		global $wpdb;
		$now = current_time( 'mysql' );
		$ok  = $wpdb->insert(
			self::table_name(),
			array(
				'plan_id'     => (int) $plan_id,
				'day_num'     => (int) $day_num,
				'tradition'   => sanitize_key( (string) $tradition ),
				'verse_ref'   => sanitize_text_field( (string) $verse_ref ),
				'translation' => sanitize_key( (string) $translation ),
				'content'     => wp_kses_post( (string) $content ),
				'created_at'  => $now,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( ! $ok ) {
			// Race: another request may have inserted.
			return self::find( $plan_id, $day_num, $tradition );
		}
		return self::find( $plan_id, $day_num, $tradition );
	}

	/**
	 * Format row for REST.
	 *
	 * @param array<string, mixed>|null $row    Row.
	 * @param bool                      $cached Cached flag.
	 * @return array<string, mixed>
	 */
	public static function format_row( $row, $cached = true ) {
		if ( ! is_array( $row ) ) {
			return array(
				'content'     => '',
				'cached'      => false,
				'tradition'   => '',
				'verse_ref'   => '',
				'translation' => '',
			);
		}
		return array(
			'content'     => (string) $row['content'],
			'cached'      => (bool) $cached,
			'tradition'   => (string) $row['tradition'],
			'verse_ref'   => (string) $row['verse_ref'],
			'translation' => (string) $row['translation'],
			'created_at'  => (string) $row['created_at'],
		);
	}

	/**
	 * GET cached study.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_get( $request ) {
		$plan_id = (int) $request['id'];
		$day_num = (int) $request['day'];
		$plan    = HWBL_CPT_Plan::get_plan_data( $plan_id );
		$day     = HWBL_CPT_Plan::get_day( $plan_id, $day_num );
		if ( ! $plan || ! $day ) {
			return new WP_Error( 'hwbl_plan_day_not_found', __( 'Plan day not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		$tradition = self::resolve_tradition( (string) $request->get_param( 'tradition' ), $day, (string) ( $plan['topic'] ?? '' ) );
		$row       = self::find( $plan_id, $day_num, $tradition );
		return rest_ensure_response( self::format_row( $row, true ) );
	}

	/**
	 * POST generate or return cached study.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_generate( $request ) {
		$plan_id = (int) $request['id'];
		$day_num = (int) $request['day'];
		$plan    = HWBL_CPT_Plan::get_plan_data( $plan_id );
		$day     = HWBL_CPT_Plan::get_day( $plan_id, $day_num );
		if ( ! $plan || ! $day ) {
			return new WP_Error( 'hwbl_plan_day_not_found', __( 'Plan day not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		$topic     = (string) ( $plan['topic'] ?? '' );
		$tradition = self::resolve_tradition( (string) $request->get_param( 'tradition' ), $day, $topic );
		if ( is_user_logged_in() && function_exists( 'thw_premium_user_tradition_enabled' ) && thw_premium_user_tradition_enabled() && 'site' !== $tradition && function_exists( 'thw_premium_set_user_tradition_preset' ) ) {
			thw_premium_set_user_tradition_preset( get_current_user_id(), $tradition );
		}

		$cached = self::find( $plan_id, $day_num, $tradition );
		if ( $cached ) {
			return rest_ensure_response( self::format_row( $cached, true ) );
		}

		if ( ! function_exists( 'thw_premium_ai_frontend_available' ) || ! thw_premium_ai_frontend_available() ) {
			return new WP_Error( 'thw_ai_disabled', __( 'AI study is not enabled on this site.', 'hidden-word-bible-lessons' ), array( 'status' => 403 ) );
		}
		if ( ! class_exists( 'THW_Premium_AI_Client' ) ) {
			return new WP_Error( 'thw_ai_disabled', __( 'AI client is not available.', 'hidden-word-bible-lessons' ), array( 'status' => 403 ) );
		}
		if ( ! self::check_rate_limit( get_current_user_id() ) ) {
			return new WP_Error( 'thw_ai_rate_limit', __( 'Hourly AI study limit reached.', 'hidden-word-bible-lessons' ), array( 'status' => 429 ) );
		}

		$context = trim(
			(string) ( $day['verse_ref'] ?? '' ) . "\n" .
			(string) ( $day['verse_text'] ?? '' ) . "\n" .
			(string) ( $day['title'] ?? '' ) . "\n" .
			wp_strip_all_tags( (string) ( $day['body'] ?? '' ) ) . "\n" .
			$topic
		);
		$rules = '';
		if ( function_exists( 'thw_premium_resolve_explain_rules_for_request' ) ) {
			$resolved = thw_premium_resolve_explain_rules_for_request( $tradition, $context );
			$rules    = (string) ( $resolved['rules'] ?? '' );
		}
		$system = function_exists( 'thw_premium_build_ai_system_instruction' )
			? thw_premium_build_ai_system_instruction( $rules )
			: $rules;

		$prompt  = "You are a biblical teacher writing a short Bible study for a multi-day reading plan.\n";
		$prompt .= "Write exactly 4 or 5 short paragraphs in HTML using only <p> tags (no headings, lists, or markdown).\n";
		$prompt .= "Return raw HTML only — do not wrap it in markdown code fences.\n";
		$prompt .= "Ground the study in the verse and the plan subject. Give concrete examples of how to apply this truth in daily life today.\n";
		$prompt .= "Stay pastoral, warm, and Scripture-first. Do not invent Bible quotations beyond the text supplied.\n\n";
		$prompt .= 'Plan title: ' . (string) ( $plan['title'] ?? '' ) . "\n";
		$prompt .= 'Plan topic: ' . $topic . "\n";
		$prompt .= 'Day ' . (int) $day_num . ': ' . (string) ( $day['title'] ?? '' ) . "\n";
		$prompt .= 'Verse reference: ' . (string) ( $day['verse_ref'] ?? '' ) . "\n";
		if ( ! empty( $day['translation'] ) ) {
			$prompt .= 'Translation: ' . strtoupper( (string) $day['translation'] ) . "\n";
		}
		if ( ! empty( $day['verse_text'] ) ) {
			$prompt .= "\nVerse text:\n" . (string) $day['verse_text'] . "\n";
		}
		if ( ! empty( $day['body'] ) ) {
			$prompt .= "\nDay focus:\n" . wp_strip_all_tags( (string) $day['body'] ) . "\n";
		}

		$result = THW_Premium_AI_Client::generate_text( $prompt, $system );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'thw_ai_generate_failed', $result->get_error_message(), array( 'status' => 502 ) );
		}

		$html = self::normalize_study_html( (string) $result );
		if ( strlen( wp_strip_all_tags( $html ) ) < 40 ) {
			return new WP_Error( 'thw_ai_generate_failed', __( 'Could not generate a usable study.', 'hidden-word-bible-lessons' ), array( 'status' => 502 ) );
		}

		self::increment_rate_limit( get_current_user_id() );
		$saved = self::save(
			$plan_id,
			$day_num,
			$tradition,
			(string) ( $day['verse_ref'] ?? '' ),
			(string) ( $day['translation'] ?? '' ),
			$html
		);
		return rest_ensure_response( self::format_row( $saved, false ) );
	}

	/**
	 * Normalize AI HTML to paragraphs.
	 *
	 * @param string $raw Raw AI output.
	 * @return string
	 */
	public static function normalize_study_html( $raw ) {
		$raw = trim( (string) $raw );
		$raw = preg_replace( '/^```(?:html)?\s*/i', '', $raw );
		$raw = preg_replace( '/\s*```$/', '', (string) $raw );
		if ( false === stripos( $raw, '<p' ) ) {
			$parts = preg_split( '/\n\s*\n/', $raw );
			$html  = '';
			foreach ( (array) $parts as $part ) {
				$part = trim( (string) $part );
				if ( '' !== $part ) {
					$html .= '<p>' . esc_html( $part ) . '</p>';
				}
			}
			return $html;
		}
		return wp_kses_post( $raw );
	}

	/**
	 * Rate limit check.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function check_rate_limit( $user_id ) {
		$key  = 'hwbl_plan_study_rl_' . (int) $user_id;
		$hits = (int) get_transient( $key );
		return $hits < self::RATE_LIMIT;
	}

	/**
	 * Increment rate limit.
	 *
	 * @param int $user_id User ID.
	 */
	private static function increment_rate_limit( $user_id ) {
		$key  = 'hwbl_plan_study_rl_' . (int) $user_id;
		$hits = (int) get_transient( $key );
		set_transient( $key, $hits + 1, self::RATE_WINDOW );
	}
}
