<?php
/**
 * Front-end AI lesson explanations for logged-in users.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_AI_Explain
 */
class THW_Premium_AI_Explain {

	const RATE_LIMIT  = 10;
	const RATE_WINDOW = 3600;

	/**
	 * Whether explain UI was rendered on this request (late asset enqueue).
	 *
	 * @var bool
	 */
	private static $assets_needed = false;

	/**
	 * Pending SSE emitter for rest_pre_serve_request (callable|null).
	 *
	 * @var callable|null
	 */
	private static $pending_sse = null;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'hwbl_lesson_render_before_tabs', array( __CLASS__, 'render_controls' ), 8 );
		add_action( 'wp_footer', array( __CLASS__, 'enqueue_late_assets' ), 5 );
	}

	/**
	 * Whether explain assets should load on this request.
	 *
	 * @return bool
	 */
	public static function needs_assets() {
		return self::$assets_needed;
	}

	/**
	 * Register REST route for AI lesson explanations.
	 *
	 * POST /thw/v1/explain generates an explanation for a lesson (or section).
	 * Requires a logged-in user; cookie authentication supplies the REST nonce.
	 */
	public static function register_routes() {
		register_rest_route(
			'thw/v1',
			'/explain',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_explain' ),
				'permission_callback' => static function () {
					return current_user_can( 'read' );
				},
				'args'                => array(
					'lesson_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'scope'     => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => 'all',
						'sanitize_callback' => 'sanitize_key',
					),
					'tradition' => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
					'stream'    => array(
						'type'              => 'boolean',
						'required'          => false,
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);
	}

	/**
	 * Parse lesson ID and scope from a REST request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array{lesson_id:int, scope:string}
	 */
	public static function parse_request_params( $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_body_params();
		}
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_params();
		}

		$lesson_id = 0;
		if ( isset( $params['lesson_id'] ) ) {
			$lesson_id = absint( $params['lesson_id'] );
		}

		$scope = isset( $params['scope'] ) ? sanitize_key( (string) $params['scope'] ) : 'all';
		if ( ! self::is_valid_scope( $scope ) ) {
			$scope = 'all';
		}

		$tradition = isset( $params['tradition'] ) ? sanitize_key( (string) $params['tradition'] ) : '';
		$stream    = self::request_wants_stream( $request, $params );

		return array(
			'lesson_id' => $lesson_id,
			'scope'     => $scope,
			'tradition' => $tradition,
			'stream'    => $stream,
		);
	}

	/**
	 * Whether the request asked for Server-Sent Events streaming.
	 *
	 * Accepts ?stream=1, body stream=true, or Accept: text/event-stream.
	 *
	 * @param WP_REST_Request      $request Request.
	 * @param array<string, mixed> $params  Parsed params.
	 * @return bool
	 */
	public static function request_wants_stream( $request, $params = array() ) {
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		if ( isset( $params['stream'] ) ) {
			return (bool) rest_sanitize_boolean( $params['stream'] );
		}

		$query_stream = $request->get_param( 'stream' );
		if ( null !== $query_stream && '' !== $query_stream ) {
			return (bool) rest_sanitize_boolean( $query_stream );
		}

		$accept = (string) $request->get_header( 'accept' );
		if ( '' !== $accept && false !== stripos( $accept, 'text/event-stream' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether explain UI and API are available.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return thw_premium_ai_frontend_available();
	}

	/**
	 * Render explain controls in the lesson header area.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public static function render_controls( $lesson_id ) {
		if ( ! is_user_logged_in() || ! self::is_available() ) {
			return;
		}

		self::$assets_needed = true;
		?>
		<div class="thw-ai-explain-controls" data-lesson-id="<?php echo esc_attr( (string) $lesson_id ); ?>">
			<?php
			thw_premium_the_tradition_select(
				array(
					'id'    => 'thw-ai-tradition-explain-' . (int) $lesson_id,
					'class' => 'thw-ai-tradition-select thw-ai-explain-tradition',
				)
			);
			?>
			<label class="screen-reader-text" for="thw-ai-explain-scope-<?php echo esc_attr( (string) $lesson_id ); ?>">
				<?php esc_html_e( 'Explanation scope', 'hidden-word-bible-lessons' ); ?>
			</label>
			<select id="thw-ai-explain-scope-<?php echo esc_attr( (string) $lesson_id ); ?>" class="thw-ai-explain-scope">
				<option value="all"><?php esc_html_e( 'Whole lesson', 'hidden-word-bible-lessons' ); ?></option>
				<option value="verse"><?php esc_html_e( 'Verse', 'hidden-word-bible-lessons' ); ?></option>
				<option value="context"><?php esc_html_e( 'Context', 'hidden-word-bible-lessons' ); ?></option>
				<option value="narrative"><?php esc_html_e( 'Narrative', 'hidden-word-bible-lessons' ); ?></option>
				<option value="discussion"><?php esc_html_e( 'Discussion', 'hidden-word-bible-lessons' ); ?></option>
			</select>
			<button type="button" class="thw-btn thw-btn-secondary thw-ai-explain-trigger" data-lesson-id="<?php echo esc_attr( (string) $lesson_id ); ?>">
				<?php esc_html_e( 'Explain this lesson', 'hidden-word-bible-lessons' ); ?>
			</button>
		</div>
		<?php
		self::render_panel_markup( $lesson_id );
	}

	/**
	 * Enqueue explain assets after lesson HTML renders (wp_enqueue_scripts runs too early).
	 */
	public static function enqueue_late_assets() {
		if ( ! self::$assets_needed || ! is_user_logged_in() || ! self::is_available() ) {
			return;
		}

		wp_enqueue_style( 'thw-premium' );

		if ( ! wp_script_is( 'thw-ai-explain', 'enqueued' ) ) {
			wp_enqueue_script( 'thw-ai-explain' );
		}

		wp_localize_script(
			'thw-ai-explain',
			'thwAiExplain',
			array(
				'restUrl'            => esc_url_raw( rest_url( 'thw/v1/explain' ) ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'loading'            => __( 'Generating explanation…', 'hidden-word-bible-lessons' ),
				'error'              => __( 'Could not generate an explanation. Please try again.', 'hidden-word-bible-lessons' ),
				'rateLimit'          => __( 'You have reached the hourly limit for AI explanations. Please try again later.', 'hidden-word-bible-lessons' ),
				'noPanel'            => __( 'Could not open the explanation panel. Refresh the page and try again.', 'hidden-word-bible-lessons' ),
				'userTradition'      => thw_premium_show_tradition_select(),
				'traditionStorageKey'=> 'thw_ai_tradition_preset',
				'stream'             => class_exists( 'THW_Premium_AI_Client' ) && THW_Premium_AI_Client::supports_streaming(),
				'complianceFlagged'  => __( 'This explanation may not fully match the selected tradition’s guidelines. Please review carefully against Scripture and your church’s teaching.', 'hidden-word-bible-lessons' ),
			)
		);
	}

	/**
	 * Render explain output panel beside controls.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	private static function render_panel_markup( $lesson_id ) {
		?>
		<div
			id="thw-panel-ai-explain-<?php echo esc_attr( (string) $lesson_id ); ?>"
			class="thw-ai-explain-panel"
			role="region"
			aria-labelledby="thw-ai-explain-heading-<?php echo esc_attr( (string) $lesson_id ); ?>"
			hidden
		>
			<h3 id="thw-ai-explain-heading-<?php echo esc_attr( (string) $lesson_id ); ?>" class="thw-panel-title">
				<?php esc_html_e( 'AI Explanation', 'hidden-word-bible-lessons' ); ?>
			</h3>
			<div class="thw-ai-explain-output" aria-live="polite"></div>
			<p class="description thw-ai-explain-disclaimer">
				<?php esc_html_e( 'AI-generated explanation based on your site rules. Always compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * @deprecated Panel renders with controls via render_panel_markup().
	 *
	 * @param int                  $lesson_id Lesson ID.
	 * @param array<string, mixed> $lesson    Lesson data.
	 * @param array<string, mixed> $args      Render args.
	 */
	public static function render_panel( $lesson_id, $lesson, $args ) {
		unset( $lesson_id, $lesson, $args );
	}

	/**
	 * REST handler for lesson explanations.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_explain( $request ) {
		if ( ! self::is_available() ) {
			return new WP_Error( 'thw_ai_disabled', __( 'AI features are not enabled on this site.', 'hidden-word-bible-lessons' ), array( 'status' => 403 ) );
		}

		$parsed    = self::parse_request_params( $request );
		$lesson_id = (int) $parsed['lesson_id'];
		$scope     = $parsed['scope'];
		$want_stream = ! empty( $parsed['stream'] ) && THW_Premium_AI_Client::supports_streaming();

		if ( $lesson_id < 1 ) {
			return new WP_Error(
				'thw_invalid_lesson_id',
				__( 'A valid lesson ID is required.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		if ( ! self::check_rate_limit( get_current_user_id() ) ) {
			return new WP_Error( 'thw_ai_rate_limit', __( 'Hourly AI explanation limit reached.', 'hidden-word-bible-lessons' ), array( 'status' => 429 ) );
		}

		$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		if ( empty( $lesson['id'] ) ) {
			return new WP_Error( 'thw_invalid_lesson', __( 'Lesson not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		$context  = self::build_topic_context( $lesson, $scope );
		$resolved = thw_premium_resolve_explain_rules_for_request( $parsed['tradition'], $context );
		$rules    = $resolved['rules'];
		$preset   = $resolved['preset'];
		$checklist = ! empty( $resolved['checklist'] ) ? $resolved['checklist'] : $rules;

		if ( is_user_logged_in() && thw_premium_user_tradition_enabled() && 'site' !== $preset ) {
			thw_premium_set_user_tradition_preset( get_current_user_id(), $preset );
		}

		$content_hash = self::build_content_hash( $lesson, $scope, $rules, $resolved['digest_hash'] );
		$cached       = self::get_cached_explanation( $lesson_id, $scope, $content_hash, $preset );
		if ( null !== $cached ) {
			$payload = array(
				'content'           => $cached['content'],
				'scope'             => $scope,
				'cached'            => true,
				'tradition'         => $preset,
				'complianceFlagged' => $cached['flagged'],
			);
			if ( $want_stream ) {
				return self::stream_cached_sse_response( $payload );
			}
			return new WP_REST_Response( $payload );
		}

		$prompt             = self::build_prompt( $lesson, $scope );
		$system_instruction = thw_premium_build_ai_system_instruction( $rules );

		if ( $want_stream ) {
			return self::stream_explain_sse(
				$prompt,
				$system_instruction,
				$checklist,
				$lesson_id,
				$scope,
				$content_hash,
				$preset
			);
		}

		$result = THW_Premium_AI_Client::generate_text( $prompt, $system_instruction );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$flagged = false;
		if ( thw_premium_ai_compliance_check_enabled() ) {
			$compliance = self::enforce_compliance( $prompt, $system_instruction, $checklist, $result );
			$result     = $compliance['content'];
			$flagged    = $compliance['flagged'];

			if ( $flagged && 'block' === thw_premium_get_ai_compliance_failure_action() ) {
				return new WP_Error(
					'thw_ai_compliance_failed',
					__( 'We could not generate an explanation that follows the selected tradition’s guidelines. Please try again, or choose a different scope.', 'hidden-word-bible-lessons' ),
					array( 'status' => 502 )
				);
			}
		}

		$html = THW_Premium_AI_Client::format_html_response( $result );
		self::store_cached_explanation( $lesson_id, $scope, $content_hash, $html, $preset, $flagged );
		self::increment_rate_limit( get_current_user_id() );

		return new WP_REST_Response(
			array(
				'content'           => $html,
				'scope'             => $scope,
				'cached'            => false,
				'tradition'         => $preset,
				'complianceFlagged' => $flagged,
			)
		);
	}

	/**
	 * Emit a cached explanation over SSE (single done event).
	 *
	 * @param array<string, mixed> $payload REST payload.
	 * @return WP_REST_Response
	 */
	private static function stream_cached_sse_response( array $payload ) {
		self::queue_sse_emitter(
			static function () use ( $payload ) {
				self::emit_sse_event(
					'done',
					array(
						'content'           => (string) ( $payload['content'] ?? '' ),
						'scope'             => (string) ( $payload['scope'] ?? 'all' ),
						'cached'            => true,
						'tradition'         => (string) ( $payload['tradition'] ?? 'site' ),
						'complianceFlagged' => ! empty( $payload['complianceFlagged'] ),
					)
				);
				self::end_sse_response();
			}
		);
		return new WP_REST_Response( null, 200 );
	}

	/**
	 * Stream a new explanation via Server-Sent Events.
	 *
	 * Events: token {text}, done {content, …}, error {message}.
	 *
	 * @param string $prompt             Prompt.
	 * @param string $system_instruction System instruction.
	 * @param string $checklist          Compliance checklist.
	 * @param int    $lesson_id          Lesson ID.
	 * @param string $scope              Scope.
	 * @param string $content_hash       Cache hash.
	 * @param string $preset             Tradition preset.
	 * @return WP_REST_Response
	 */
	private static function stream_explain_sse( $prompt, $system_instruction, $checklist, $lesson_id, $scope, $content_hash, $preset ) {
		self::queue_sse_emitter(
			static function () use ( $prompt, $system_instruction, $checklist, $lesson_id, $scope, $content_hash, $preset ) {
				$result = THW_Premium_AI_Client::stream_completion(
					array(
						'prompt'             => $prompt,
						'system_instruction' => $system_instruction,
						'on_chunk'           => static function ( $delta ) {
							self::emit_sse_event( 'token', array( 'text' => (string) $delta ) );
						},
					)
				);

				if ( is_wp_error( $result ) ) {
					self::emit_sse_event(
						'error',
						array(
							'message' => $result->get_error_message(),
							'code'    => $result->get_error_code(),
						)
					);
					self::end_sse_response();
					return;
				}

				$flagged = false;
				if ( thw_premium_ai_compliance_check_enabled() ) {
					$compliance = self::enforce_compliance( $prompt, $system_instruction, $checklist, $result );
					$result     = $compliance['content'];
					$flagged    = $compliance['flagged'];

					if ( $flagged && 'block' === thw_premium_get_ai_compliance_failure_action() ) {
						self::emit_sse_event(
							'error',
							array(
								'message' => __( 'We could not generate an explanation that follows the selected tradition’s guidelines. Please try again, or choose a different scope.', 'hidden-word-bible-lessons' ),
								'code'    => 'thw_ai_compliance_failed',
							)
						);
						self::end_sse_response();
						return;
					}
				}

				$html = THW_Premium_AI_Client::format_html_response( $result );
				self::store_cached_explanation( $lesson_id, $scope, $content_hash, $html, $preset, $flagged );
				self::increment_rate_limit( get_current_user_id() );

				self::emit_sse_event(
					'done',
					array(
						'content'           => $html,
						'scope'             => $scope,
						'cached'            => false,
						'tradition'         => $preset,
						'complianceFlagged' => $flagged,
					)
				);
				self::end_sse_response();
			}
		);

		return new WP_REST_Response( null, 200 );
	}

	/**
	 * Queue an SSE body emitter served via rest_pre_serve_request.
	 *
	 * @param callable $emitter Emitter that writes SSE frames.
	 */
	private static function queue_sse_emitter( $emitter ) {
		self::$pending_sse = $emitter;
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'serve_pending_sse' ), 10, 4 );
	}

	/**
	 * Serve a queued SSE response instead of JSON.
	 *
	 * @param bool             $served  Whether the request has already been served.
	 * @param WP_REST_Response $result  Result to send.
	 * @param WP_REST_Request  $request Request.
	 * @param WP_REST_Server   $server  Server.
	 * @return bool
	 */
	public static function serve_pending_sse( $served, $result, $request, $server ) {
		unset( $result, $request, $server );
		if ( $served || ! is_callable( self::$pending_sse ) ) {
			return $served;
		}

		$emitter           = self::$pending_sse;
		self::$pending_sse = null;
		remove_filter( 'rest_pre_serve_request', array( __CLASS__, 'serve_pending_sse' ), 10 );

		self::begin_sse_response();
		call_user_func( $emitter );
		return true;
	}

	/**
	 * Send SSE response headers and disable buffering.
	 */
	private static function begin_sse_response() {
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: text/event-stream; charset=UTF-8' );
			header( 'Cache-Control: no-cache, no-transform' );
			header( 'X-Accel-Buffering: no' );
		}
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
		flush();
	}

	/**
	 * Write one SSE event.
	 *
	 * @param string               $event Event name.
	 * @param array<string, mixed> $data  Payload.
	 */
	private static function emit_sse_event( $event, array $data ) {
		$json = wp_json_encode( $data );
		if ( ! is_string( $json ) ) {
			$json = '{}';
		}
		echo 'event: ' . sanitize_key( (string) $event ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SSE protocol frame, not HTML.
		echo 'data: ' . $json . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON payload for EventSource clients.
		flush();
	}

	/**
	 * Finish an SSE response.
	 */
	private static function end_sse_response() {
		echo "event: close\ndata: {}\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SSE protocol frame.
		flush();
	}

	/**
	 * Build free-text context used for doctrine topic detection.
	 *
	 * @param array<string, mixed> $lesson Lesson data.
	 * @param string               $scope  Scope slug.
	 * @return string
	 */
	private static function build_topic_context( $lesson, $scope ) {
		$parts = array(
			isset( $lesson['reference'] ) ? (string) $lesson['reference'] : '',
			isset( $lesson['title'] ) ? (string) $lesson['title'] : '',
			wp_strip_all_tags( (string) ( $lesson['historical_context'] ?? '' ) ),
			wp_strip_all_tags( (string) ( $lesson['preceding_narrative'] ?? '' ) ),
			is_array( $lesson['discussion_questions'] ?? null ) ? implode( ' ', $lesson['discussion_questions'] ) : '',
			(string) $scope,
		);
		return implode( "\n", array_filter( array_map( 'trim', $parts ) ) );
	}

	/**
	 * Verify a generated explanation against the resolved rules, retrying
	 * once with a corrective nudge if the first pass is flagged.
	 *
	 * If it's still flagged after the retry, we don't withhold the answer —
	 * we return the best attempt (the retry, since it already tried to
	 * correct the specific issue) marked as flagged, so the caller can show
	 * a stronger disclaimer instead of a generic error. This is a backstop
	 * against clear rule violations, not a guarantee.
	 *
	 * @param string $prompt             Original user-content prompt.
	 * @param string $system_instruction Original system instruction (mandatory rules).
	 * @param string $rules              Resolved rules text for compliance review.
	 * @param string $result             First-pass AI response (HTML).
	 * @return array{content:string, flagged:bool, reason:string}
	 */
	private static function enforce_compliance( $prompt, $system_instruction, $rules, $result ) {
		$check = THW_Premium_AI_Client::check_compliance( $rules, wp_strip_all_tags( $result ) );
		if ( $check['compliant'] ) {
			return array(
				'content' => $result,
				'flagged' => false,
				'reason'  => '',
			);
		}

		$retry_system = $system_instruction
			. "\n\nYour previous answer was flagged for this specific issue: {$check['reason']} Revise your answer so it fully complies with the Rules above.";
		$retry_result = THW_Premium_AI_Client::generate_text( $prompt, $retry_system );

		if ( is_wp_error( $retry_result ) ) {
			// The retry call itself failed (network/provider error) — fall
			// back to the first-pass answer, flagged, rather than losing the
			// user's request entirely.
			return array(
				'content' => $result,
				'flagged' => true,
				'reason'  => $check['reason'],
			);
		}

		$retry_check = THW_Premium_AI_Client::check_compliance( $rules, wp_strip_all_tags( $retry_result ) );
		if ( $retry_check['compliant'] ) {
			return array(
				'content' => $retry_result,
				'flagged' => false,
				'reason'  => '',
			);
		}

		return array(
			'content' => $retry_result,
			'flagged' => true,
			'reason'  => $retry_check['reason'],
		);
	}

	/**
	 * Valid explanation scopes.
	 *
	 * @param string $scope Scope slug.
	 * @return bool
	 */
	public static function is_valid_scope( $scope ) {
		return in_array( $scope, array( 'all', 'verse', 'context', 'narrative', 'discussion' ), true );
	}

	/**
	 * Build the user-content prompt for an AI explanation (lesson content
	 * only). Tradition/site rules are no longer concatenated in here — they
	 * go out as a separate system instruction via
	 * thw_premium_build_ai_system_instruction(), see rest_explain().
	 *
	 * @param array<string, mixed> $lesson Lesson data.
	 * @param string               $scope  Scope slug.
	 * @return string
	 */
	public static function build_prompt( $lesson, $scope ) {
		$verse_text  = '';
		$translation = get_option( 'hwbl_active_translation', 'niv' );

		if ( ! empty( $lesson['id'] ) && class_exists( 'HWBL_Verse_Memorize' ) && class_exists( 'HWBL_CPT_Lesson' ) ) {
			$stored_translation = sanitize_key( (string) HWBL_CPT_Lesson::get_meta_value( (int) $lesson['id'], 'translation' ) );
			if ( '' !== $stored_translation ) {
				$translation = $stored_translation;
			}
		}

		$may_embed = function_exists( 'thw_premium_ai_may_embed_scripture_text' )
			? thw_premium_ai_may_embed_scripture_text( $translation )
			: ( 'niv' !== sanitize_key( (string) $translation ) );

		if ( $may_embed ) {
			if ( ! empty( $lesson['id'] ) && class_exists( 'HWBL_Verse_Memorize' ) ) {
				$verse_text = HWBL_Verse_Memorize::get_stored_verse_text( (int) $lesson['id'], $translation );
			}
			if ( '' === $verse_text && class_exists( 'HWBL_Translation_Service' ) ) {
				$trans_svc  = HWBL_Translation_Service::instance();
				$verse_text = $trans_svc->get_verse_text(
					(int) $lesson['book_id'],
					(int) $lesson['chapter'],
					(int) $lesson['verse_start'],
					$translation
				);
			}
		}

		$sections = array(
			'verse'      => $verse_text,
			'context'    => wp_strip_all_tags( (string) ( $lesson['historical_context'] ?? '' ) ),
			'narrative'  => wp_strip_all_tags( (string) ( $lesson['preceding_narrative'] ?? '' ) ),
			'discussion' => self::format_questions_for_prompt( $lesson['discussion_questions'] ?? array() ),
		);

		$body = "Reference: {$lesson['reference']}\n";
		if ( ! $may_embed ) {
			$body .= 'Translation: ' . strtoupper( (string) $translation ) . "\n";
			$body .= "Scripture wording is not included (licensed translation). Explain from the reference and curriculum notes only; do not invent copyrighted quotations. The reader sees the official text in the UI.\n";
		}
		$body .= "\n";

		if ( 'all' === $scope ) {
			foreach ( $sections as $label => $text ) {
				if ( '' !== trim( $text ) ) {
					$body .= ucfirst( $label ) . ":\n{$text}\n\n";
				}
			}
		} elseif ( isset( $sections[ $scope ] ) && '' !== trim( $sections[ $scope ] ) ) {
			$body .= ucfirst( $scope ) . ":\n{$sections[ $scope ]}\n\n";
		} elseif ( ! $may_embed && in_array( $scope, array( 'all', 'verse' ), true ) ) {
			$body .= "Explain this scripture reference for a small-group participant.\n\n";
		}

		$quote_rule = $may_embed
			? 'Do not quote scripture beyond what is provided.'
			: 'Do not invent or reproduce copyrighted scripture wording; refer to the passage by reference only.';

		return 'Explain the following Bible lesson content for a small-group participant. '
			. "Use HTML paragraphs (<p> tags) only. {$quote_rule}\n\n"
			. $body;
	}

	/**
	 * Format discussion questions for the prompt.
	 *
	 * @param array<int, string> $questions Questions.
	 * @return string
	 */
	private static function format_questions_for_prompt( $questions ) {
		if ( ! is_array( $questions ) || empty( $questions ) ) {
			return '';
		}

		return implode( "\n", array_map( 'strval', $questions ) );
	}

	/**
	 * Build cache hash from lesson content, effective rules, and doctrine digest.
	 *
	 * @param array<string, mixed> $lesson      Lesson data.
	 * @param string               $scope       Scope slug.
	 * @param string|null          $rules       Optional rules override.
	 * @param string               $digest_hash Optional doctrine digest hash.
	 * @return string
	 */
	public static function build_content_hash( $lesson, $scope, $rules = null, $digest_hash = '' ) {
		if ( null === $rules ) {
			$resolved    = thw_premium_resolve_explain_rules_for_request( '' );
			$rules       = $resolved['rules'];
			$digest_hash = $digest_hash ? $digest_hash : (string) $resolved['digest_hash'];
		}
		$payload = wp_json_encode(
			array(
				'scope'        => $scope,
				'rules'        => $rules,
				'digest_hash'  => $digest_hash,
				'lesson'       => array(
					'reference'            => $lesson['reference'] ?? '',
					'translation'          => self::get_lesson_translation_for_cache( $lesson ),
					'verse_text_snapshot'  => self::get_lesson_verse_snapshot_for_cache( $lesson ),
					'historical_context'   => $lesson['historical_context'] ?? '',
					'preceding_narrative'  => $lesson['preceding_narrative'] ?? '',
					'discussion_questions' => $lesson['discussion_questions'] ?? array(),
				),
			)
		);

		return md5( (string) $payload );
	}

	/**
	 * Translation slug stored on custom memorize lesson posts.
	 *
	 * @param array<string, mixed> $lesson Lesson data.
	 * @return string
	 */
	private static function get_lesson_translation_for_cache( $lesson ) {
		if ( empty( $lesson['id'] ) || ! class_exists( 'HWBL_CPT_Lesson' ) ) {
			return '';
		}

		return sanitize_key( (string) HWBL_CPT_Lesson::get_meta_value( (int) $lesson['id'], 'translation' ) );
	}

	/**
	 * Stored verse text for custom memorize lesson posts.
	 *
	 * @param array<string, mixed> $lesson Lesson data.
	 * @return string
	 */
	private static function get_lesson_verse_snapshot_for_cache( $lesson ) {
		if ( empty( $lesson['id'] ) || ! class_exists( 'HWBL_Verse_Memorize' ) ) {
			return '';
		}

		$translation = self::get_lesson_translation_for_cache( $lesson );
		if ( '' === $translation ) {
			return '';
		}

		return HWBL_Verse_Memorize::get_stored_verse_text( (int) $lesson['id'], $translation );
	}

	/**
	 * Get cached explanation if hash matches.
	 *
	 * @param int    $lesson_id    Lesson ID.
	 * @param string $scope        Scope slug.
	 * @param string $content_hash Content hash.
	 * @param string $preset       Tradition preset slug.
	 * @return array{content:string, flagged:bool}|null
	 */
	private static function get_cached_explanation( $lesson_id, $scope, $content_hash, $preset = 'site' ) {
		$meta_key = self::get_cache_meta_key( $scope, $preset );
		$stored   = get_post_meta( $lesson_id, $meta_key, true );
		if ( ! is_array( $stored ) || empty( $stored['hash'] ) || empty( $stored['content'] ) ) {
			return null;
		}

		if ( $stored['hash'] !== $content_hash ) {
			return null;
		}

		return array(
			'content' => (string) $stored['content'],
			'flagged' => ! empty( $stored['flagged'] ),
		);
	}

	/**
	 * Store cached explanation.
	 *
	 * @param int    $lesson_id    Lesson ID.
	 * @param string $scope        Scope slug.
	 * @param string $content_hash Content hash.
	 * @param string $content      HTML content.
	 * @param string $preset       Tradition preset slug.
	 * @param bool   $flagged      Whether the compliance check flagged this response.
	 */
	private static function store_cached_explanation( $lesson_id, $scope, $content_hash, $content, $preset = 'site', $flagged = false ) {
		update_post_meta(
			$lesson_id,
			self::get_cache_meta_key( $scope, $preset ),
			array(
				'hash'    => $content_hash,
				'content' => $content,
				'flagged' => (bool) $flagged,
			)
		);
	}

	/**
	 * Cache meta key for a scope and tradition preset.
	 *
	 * @param string $scope  Scope slug.
	 * @param string $preset Tradition preset slug.
	 * @return string
	 */
	private static function get_cache_meta_key( $scope, $preset = 'site' ) {
		$preset = sanitize_key( (string) $preset );
		if ( '' === $preset ) {
			$preset = 'site';
		}
		return '_hwbl_ai_explain_' . sanitize_key( $scope ) . '_' . $preset;
	}

	/**
	 * Check user rate limit.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function check_rate_limit( $user_id ) {
		$key   = 'thw_ai_explain_' . (int) $user_id;
		$count = (int) get_transient( $key );
		return $count < self::RATE_LIMIT;
	}

	/**
	 * Increment user rate limit counter.
	 *
	 * @param int $user_id User ID.
	 */
	private static function increment_rate_limit( $user_id ) {
		$key   = 'thw_ai_explain_' . (int) $user_id;
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::RATE_WINDOW );
	}
}
