<?php
/**
 * Expo Push notifications for the companion app (VOTD + streak-at-risk).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Push_Notifications
 */
class HWBL_Push_Notifications {

	const META_TOKENS     = '_hwbl_expo_push_tokens';
	const OPT_ACCESS      = 'hwbl_expo_access_token';
	const CRON_HOOK       = 'hwbl_push_daily_cron';
	const EXPO_PUSH_URL   = 'https://exp.host/--/api/v2/push/send';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_daily_pushes' ) );
		add_action( 'init', array( __CLASS__, 'maybe_schedule_cron' ) );
	}

	/**
	 * Whether Expo push can send.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return (bool) apply_filters( 'hwbl_push_enabled', true );
	}

	/**
	 * Register options.
	 */
	public static function register_settings() {
		register_setting(
			'hwbl_app_brand',
			self::OPT_ACCESS,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}

	/**
	 * Schedule daily cron once.
	 */
	public static function maybe_schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * REST routes.
	 */
	public static function register_routes() {
		$auth = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};

		register_rest_route(
			'hwbl/v1',
			'/push/token',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_register_token' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/push/token',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'rest_delete_token' ),
				'permission_callback' => $auth,
			)
		);
	}

	/**
	 * Tokens for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, string>>
	 */
	public static function get_tokens( $user_id ) {
		$raw = get_user_meta( (int) $user_id, self::META_TOKENS, true );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Save tokens.
	 *
	 * @param int                              $user_id User ID.
	 * @param array<int, array<string, string>> $tokens  Tokens.
	 */
	public static function save_tokens( $user_id, $tokens ) {
		update_user_meta( (int) $user_id, self::META_TOKENS, array_values( $tokens ) );
	}

	/**
	 * POST register Expo push token.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_register_token( $request ) {
		$params   = $request->get_json_params();
		$params   = is_array( $params ) ? $params : $request->get_params();
		$token    = isset( $params['token'] ) ? sanitize_text_field( (string) $params['token'] ) : '';
		$platform = isset( $params['platform'] ) ? sanitize_key( (string) $params['platform'] ) : '';
		if ( ! $token || ! in_array( $platform, array( 'ios', 'android' ), true ) ) {
			return new WP_Error(
				'hwbl_bad_token',
				__( 'Provide a valid Expo push token and platform (ios|android).', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$user_id = get_current_user_id();
		$tokens  = self::get_tokens( $user_id );
		$found   = false;
		foreach ( $tokens as &$row ) {
			if ( isset( $row['token'] ) && $row['token'] === $token ) {
				$row['platform'] = $platform;
				$row['updated']  = (string) time();
				$found           = true;
				break;
			}
		}
		unset( $row );
		if ( ! $found ) {
			$tokens[] = array(
				'token'    => $token,
				'platform' => $platform,
				'updated'  => (string) time(),
			);
		}
		self::save_tokens( $user_id, $tokens );

		return new WP_REST_Response( array( 'registered' => true ), 200 );
	}

	/**
	 * DELETE unregister token.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_delete_token( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : $request->get_params();
		$token  = isset( $params['token'] ) ? sanitize_text_field( (string) $params['token'] ) : '';
		$user_id = get_current_user_id();
		$tokens  = array_values(
			array_filter(
				self::get_tokens( $user_id ),
				static function ( $row ) use ( $token ) {
					return ! isset( $row['token'] ) || $row['token'] !== $token;
				}
			)
		);
		self::save_tokens( $user_id, $tokens );
		return new WP_REST_Response( array( 'unregistered' => true ), 200 );
	}

	/**
	 * Daily VOTD + streak-at-risk pushes.
	 */
	public static function run_daily_pushes() {
		if ( ! self::is_configured() ) {
			return;
		}

		$votd_ref = '';
		if ( class_exists( 'THW_Premium_Verse_Of_The_Day' ) && method_exists( 'THW_Premium_Verse_Of_The_Day', 'get_today_payload' ) ) {
			$payload  = THW_Premium_Verse_Of_The_Day::get_today_payload();
			$votd_ref = is_array( $payload ) && ! empty( $payload['reference'] )
				? (string) $payload['reference']
				: '';
		}
		$votd_ref = (string) apply_filters( 'hwbl_push_votd_reference', $votd_ref );

		$user_query = new WP_User_Query(
			array(
				'meta_key'     => self::META_TOKENS,
				'meta_compare' => 'EXISTS',
				'fields'       => 'ID',
				'number'       => 500,
			)
		);

		foreach ( (array) $user_query->get_results() as $user_id ) {
			$user_id = (int) $user_id;
			$tokens  = self::get_tokens( $user_id );
			if ( ! $tokens ) {
				continue;
			}

			$messages = array();
			if ( $votd_ref ) {
				$messages[] = array(
					'title' => __( 'Verse of the Day', 'hidden-word-bible-lessons' ),
					'body'  => $votd_ref,
					'data'  => array( 'url' => 'hwbl://votd' ),
				);
			}

			$due = 0;
			if ( class_exists( 'HWBL_Memorization_SRS' ) && method_exists( 'HWBL_Memorization_SRS', 'get_progress_stats' ) ) {
				$stats = HWBL_Memorization_SRS::get_progress_stats( $user_id );
				$due   = is_array( $stats ) ? (int) ( $stats['due'] ?? 0 ) : 0;
			}
			if ( $due > 0 ) {
				$messages[] = array(
					'title' => __( 'Keep your streak', 'hidden-word-bible-lessons' ),
					'body'  => sprintf(
						/* translators: %d: reviews due */
						_n( 'You have %d verse due for practice.', 'You have %d verses due for practice.', $due, 'hidden-word-bible-lessons' ),
						$due
					),
					'data'  => array( 'url' => 'hwbl://practice' ),
				);
			}

			if ( class_exists( 'HWBL_Plan_Progress' ) ) {
				$active = HWBL_Plan_Progress::get_active_for_user( $user_id );
				if ( $active ) {
					$first = $active[0];
					$day_n = (int) ( $first['progress']['current_day'] ?? 0 );
					$ptitle = isset( $first['title'] ) ? (string) $first['title'] : __( 'your plan', 'hidden-word-bible-lessons' );
					$messages[] = array(
						'title' => __( 'Reading plan', 'hidden-word-bible-lessons' ),
						'body'  => sprintf(
							/* translators: 1: plan title, 2: day number */
							__( '%1$s — Day %2$d is ready', 'hidden-word-bible-lessons' ),
							$ptitle,
							$day_n
						),
						'data'  => array(
							'url' => 'hwbl://plans/' . (int) $first['plan_id'],
						),
					);
				}
			}

			foreach ( $messages as $message ) {
				self::send_to_tokens( $tokens, $message );
			}
		}
	}

	/**
	 * Send Expo push messages.
	 *
	 * @param array<int, array<string, string>> $tokens  Tokens.
	 * @param array<string, mixed>              $message Message.
	 */
	public static function send_to_tokens( $tokens, $message ) {
		$payloads = array();
		foreach ( $tokens as $row ) {
			if ( empty( $row['token'] ) ) {
				continue;
			}
			$payloads[] = array(
				'to'    => (string) $row['token'],
				'title' => (string) ( $message['title'] ?? '' ),
				'body'  => (string) ( $message['body'] ?? '' ),
				'data'  => isset( $message['data'] ) && is_array( $message['data'] ) ? $message['data'] : array(),
				'sound' => 'default',
			);
		}
		if ( ! $payloads ) {
			return;
		}

		$headers = array(
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);
		$access  = trim( (string) get_option( self::OPT_ACCESS, '' ) );
		if ( $access ) {
			$headers['Authorization'] = 'Bearer ' . $access;
		}

		wp_remote_post(
			self::EXPO_PUSH_URL,
			array(
				'timeout' => 20,
				'headers' => $headers,
				'body'    => wp_json_encode( $payloads ),
			)
		);
	}
}
