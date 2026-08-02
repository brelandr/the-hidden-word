<?php
/**
 * Verse of the Day REST routes and handlers.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Votd_Rest
 */
class THW_Premium_Votd_Rest {

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'thw/v1',
			'/votd',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_payload' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'translation' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
					'refresh'     => array(
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
						'default'           => false,
					),
				),
			)
		);

		register_rest_route(
			'thw/v1',
			'/votd-explain',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_explain' ),
				/*
				 * Public on purpose: anonymous visitors may fetch an already-saved
				 * explanation. Creating a new AI explanation is gated inside
				 * rest_explain() with is_user_logged_in() + AI availability.
				 */
				'permission_callback' => '__return_true',
				'args'                => array(
					'translation' => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * REST: today's VOTD payload for a Bible translation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_payload( $request ) {
		$translation = sanitize_key( (string) $request->get_param( 'translation' ) );
		$refresh     = rest_sanitize_boolean( $request->get_param( 'refresh' ) );

		if ( $refresh ) {
			THW_Premium_Verse_Of_The_Day::clear_cache_for_day( wp_date( 'Y-m-d' ) );
		}

		$payload = THW_Premium_Verse_Of_The_Day::get_payload_for_translation( $translation );

		if ( empty( $payload['reference'] ) || empty( $payload['text'] ) ) {
			return new WP_Error(
				'thw_votd_missing',
				__( 'Verse of the Day is unavailable right now.', 'hidden-word-bible-lessons' ),
				array( 'status' => 503 )
			);
		}

		$post_url = '';
		if ( class_exists( 'THW_Premium_Votd_Explain_Store' ) ) {
			$saved = THW_Premium_Votd_Explain_Store::find_saved_post( $payload );
			if ( $saved instanceof WP_Post ) {
				$url = THW_Premium_Votd_Explain_Store::get_public_url( $saved );
				if ( $url ) {
					$post_url = $url;
				}
			}
		}

		return new WP_REST_Response(
			array(
				'reference'         => (string) $payload['reference'],
				'text'              => (string) $payload['text'],
				'translation'       => (string) $payload['translation'],
				'translation_label' => (string) $payload['translation_label'],
				'day'               => (string) $payload['day'],
				'postUrl'           => $post_url,
			),
			200,
			array(
				'Cache-Control' => 'no-cache, no-store, must-revalidate',
			)
		);
	}

	/**
	 * REST: AI explanation for today's VOTD (reuses a saved post when available).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_explain( $request ) {
		if ( ! THW_Premium_Verse_Of_The_Day::is_ai_explain_enabled() ) {
			return new WP_Error(
				'thw_votd_ai_disabled',
				__( 'AI explanation for Verse of the Day is not enabled.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_body_params();
		}
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_params();
		}
		$params      = is_array( $params ) ? $params : array();
		$translation = isset( $params['translation'] ) ? sanitize_key( (string) $params['translation'] ) : '';

		$payload = THW_Premium_Verse_Of_The_Day::get_payload_for_translation( $translation );
		if ( empty( $payload['reference'] ) || empty( $payload['text'] ) ) {
			return new WP_Error(
				'thw_votd_missing',
				__( 'Verse of the Day is unavailable right now.', 'hidden-word-bible-lessons' ),
				array( 'status' => 503 )
			);
		}

		$trans_key = ! empty( $payload['translation'] ) ? sanitize_key( (string) $payload['translation'] ) : 'default';
		$rules     = thw_premium_votd_neutral_explain_rules();
		$checklist = $rules;

		// Prefer a saved post — no AI call; redirect to the published explanation.
		if ( class_exists( 'THW_Premium_Votd_Explain_Store' ) ) {
			$saved = THW_Premium_Votd_Explain_Store::find_saved_post( $payload );
			if ( $saved instanceof WP_Post ) {
				$html = THW_Premium_Votd_Explain_Store::get_explanation_html( $saved );
				$url  = THW_Premium_Votd_Explain_Store::get_public_url( $saved );
				return new WP_REST_Response(
					array(
						'content'           => $html,
						'cached'            => true,
						'translation'       => $trans_key,
						'complianceFlagged' => (bool) get_post_meta( $saved->ID, THW_Premium_Votd_Explain_Store::META_FLAG, true ),
						'postUrl'           => $url ? $url : '',
						'postId'            => (int) $saved->ID,
						'redirect'          => THW_Premium_Verse_Of_The_Day::should_redirect_to_post( $url ),
					)
				);
			}
		}

		// Generating a new explanation requires login + AI availability.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'thw_votd_ai_login',
				__( 'Log in to generate the first AI explanation for this Bible version. Once saved, everyone can read it.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		if ( ! THW_Premium_Verse_Of_The_Day::is_ai_explain_enabled()
			|| ! function_exists( 'thw_premium_ai_frontend_available' )
			|| ! thw_premium_ai_frontend_available() ) {
			$reason = THW_Premium_Verse_Of_The_Day::get_ai_unavailable_reason();
			return new WP_Error(
				'thw_votd_ai_unavailable',
				$reason,
				array( 'status' => 503 )
			);
		}

		if ( ! self::check_rate_limit( get_current_user_id() ) ) {
			return new WP_Error(
				'thw_ai_rate_limit',
				__( 'Hourly AI explanation limit reached.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}

		$prompt             = THW_Premium_Verse_Of_The_Day::build_explain_prompt( $payload );
		$system_instruction = thw_premium_build_ai_system_instruction( $rules );
		$result             = THW_Premium_AI_Client::generate_text( $prompt, $system_instruction );
		if ( is_wp_error( $result ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional ops log for AI failures.
			error_log(
				'[THW VOTD] AI generate failed: '
				. $result->get_error_code()
				. ' — '
				. $result->get_error_message()
			);
			return $result;
		}

		$flagged = false;
		if ( thw_premium_ai_compliance_check_enabled() ) {
			$check = THW_Premium_AI_Client::check_compliance( $checklist, wp_strip_all_tags( $result ) );
			if ( ! $check['compliant'] ) {
				$flagged      = true;
				$retry_system = $system_instruction
					. "\n\nYour previous answer was flagged for this specific issue: {$check['reason']} Revise your answer so it fully complies with the Rules above.";
				$retry        = THW_Premium_AI_Client::generate_text( $prompt, $retry_system );
				if ( ! is_wp_error( $retry ) ) {
					$result  = $retry;
					$check2  = THW_Premium_AI_Client::check_compliance( $checklist, wp_strip_all_tags( $result ) );
					$flagged = ! $check2['compliant'];
				}
				if ( $flagged && 'block' === thw_premium_get_ai_compliance_failure_action() ) {
					return new WP_Error(
						'thw_ai_compliance_failed',
						__( 'We could not generate an explanation that meets the quality guidelines. Please try again.', 'hidden-word-bible-lessons' ),
						array( 'status' => 502 )
					);
				}
			}
		}

		$html = THW_Premium_AI_Client::format_html_response( $result );

		// Another request may have saved first — reuse that post.
		$post = null;
		if ( class_exists( 'THW_Premium_Votd_Explain_Store' ) ) {
			$post = THW_Premium_Votd_Explain_Store::find_saved_post( $payload );
			if ( ! ( $post instanceof WP_Post ) ) {
				$post = THW_Premium_Votd_Explain_Store::save_post( $payload, $html, $flagged );
			} else {
				$html    = THW_Premium_Votd_Explain_Store::get_explanation_html( $post );
				$flagged = (bool) get_post_meta( $post->ID, THW_Premium_Votd_Explain_Store::META_FLAG, true );
			}
		}

		self::increment_rate_limit( get_current_user_id() );

		$url = ( $post instanceof WP_Post ) ? THW_Premium_Votd_Explain_Store::get_public_url( $post ) : '';

		return new WP_REST_Response(
			array(
				'content'           => $html,
				'cached'            => false,
				'translation'       => $trans_key,
				'complianceFlagged' => $flagged,
				'postUrl'           => $url ? $url : '',
				'postId'            => ( $post instanceof WP_Post ) ? (int) $post->ID : 0,
				'redirect'          => THW_Premium_Verse_Of_The_Day::should_redirect_to_post( $url ),
			)
		);
	}

	/**
	 * Rate limit check.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function check_rate_limit( $user_id ) {
		$key   = 'thw_votd_ai_' . (int) $user_id;
		$count = (int) get_transient( $key );
		return $count < THW_Premium_Verse_Of_The_Day::RATE_LIMIT;
	}

	/**
	 * Increment rate limit counter.
	 *
	 * @param int $user_id User ID.
	 */
	private static function increment_rate_limit( $user_id ) {
		$key   = 'thw_votd_ai_' . (int) $user_id;
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, THW_Premium_Verse_Of_The_Day::RATE_WINDOW );
	}
}
