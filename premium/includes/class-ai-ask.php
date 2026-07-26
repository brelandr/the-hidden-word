<?php
/**
 * Front-end Ask a Question shortcode and REST API.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_AI_Ask
 */
class THW_Premium_AI_Ask {

	const RATE_LIMIT  = 10;
	const RATE_WINDOW = 3600;

	/**
	 * Register the public shortcode (safe to call more than once).
	 */
	public static function register_shortcode() {
		thw_premium_register_shortcode( 'thw_ask_question', array( __CLASS__, 'render_shortcode' ) );
		add_filter( 'the_content', array( __CLASS__, 'normalize_shortcode_quotes' ), 9 );
		add_filter( 'widget_text', array( __CLASS__, 'normalize_shortcode_quotes' ), 9 );
	}

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		self::register_shortcode();
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Convert curly quotes in ask shortcodes to straight quotes.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public static function normalize_shortcode_quotes( $content ) {
		if ( false === strpos( (string) $content, '[thw_ask_question' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/\[thw_ask_question([^\]]*)\]/i',
			static function ( $matches ) {
				$atts = str_replace(
					array( "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x98", "\xE2\x80\x99", '“', '”', '‘', '’' ),
					array( '"', '"', "'", "'", '"', '"', "'", "'" ),
					$matches[1]
				);
				return '[thw_ask_question' . $atts . ']';
			},
			(string) $content
		);
	}

	/**
	 * Register REST routes for Ask a Question.
	 *
	 * Creates POST /thw/v1/ask for AI pastoral Q&A. Permission follows the site
	 * audience setting (logged-in / everyone / disabled).
	 */
	public static function register_routes() {
		register_rest_route(
			'thw/v1',
			'/ask',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_ask' ),
				'permission_callback' => static function () {
					return function_exists( 'thw_premium_current_user_can_use_ai_ask' )
						&& thw_premium_current_user_can_use_ai_ask();
				},
				'args'                => array(
					'question'  => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'tradition' => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			'thw/v1',
			'/ai-config',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_ai_config' ),
				/*
				 * Public on purpose: the companion app needs to know whether the
				 * tradition picker is enabled before (and without) mutating prefs.
				 */
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST: front-end AI config for the companion app and shortcodes.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_ai_config() {
		$user_tradition = thw_premium_user_tradition_enabled();
		$tradition      = '';
		$traditions     = array();

		if ( $user_tradition ) {
			$traditions = thw_premium_get_tradition_preset_choices( true );
			if ( is_user_logged_in() ) {
				$tradition = thw_premium_get_user_tradition_preset();
			} else {
				$tradition = thw_premium_get_site_default_tradition_preset();
			}
		}

		$show_tradition = thw_premium_show_tradition_select();

		return new WP_REST_Response(
			array(
				'userTradition'       => $show_tradition,
				'tradition'           => $tradition,
				'traditions'          => $traditions,
				'showTraditionPicker' => $show_tradition,
				'askAudience'         => thw_premium_get_ai_ask_audience(),
				'studyAudience'       => function_exists( 'thw_premium_get_ai_study_audience' )
					? thw_premium_get_ai_study_audience()
					: 'logged_in',
			)
		);
	}

	/**
	 * Render ask shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		if ( ! class_exists( 'THW_Premium_License' ) || ! THW_Premium_License::is_licensed() ) {
			return '<p class="thw-notice thw-notice-info">' . esc_html__( 'Ask a Question requires an active The Hidden Word Premium license.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'title'        => __( 'Ask a Bible Question', 'hidden-word-bible-lessons' ),
				'placeholder'  => __( 'Ask a question about Scripture, faith, or Christian living…', 'hidden-word-bible-lessons' ),
				'button_label' => __( 'Ask', 'hidden-word-bible-lessons' ),
			),
			$atts,
			'thw_ask_question'
		);

		ob_start();
		?>
		<div class="thw-ask-question" data-thw-ask-question>
			<?php if ( $atts['title'] ) : ?>
				<h2 class="thw-ask-question__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>
			<form class="thw-ask-question__form" action="#" method="post">
				<?php
				thw_premium_the_tradition_select(
					array(
						'id'    => 'thw-ai-tradition-ask',
						'class' => 'thw-ai-tradition-select thw-ask-question__tradition',
					)
				);
				?>
				<label class="screen-reader-text" for="thw-ask-question-input"><?php esc_html_e( 'Question', 'hidden-word-bible-lessons' ); ?></label>
				<textarea
					id="thw-ask-question-input"
					class="thw-ask-question__input"
					name="question"
					rows="4"
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
					required
				></textarea>
				<button type="submit" class="thw-btn thw-ask-question__submit"><?php echo esc_html( $atts['button_label'] ); ?></button>
			</form>
			<div class="thw-ask-question__status" role="status" aria-live="polite"></div>
			<div class="thw-ask-question__answer"></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * REST handler for Ask a Question.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_ask( $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_body_params();
		}
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_params();
		}

		$question  = isset( $params['question'] ) ? sanitize_textarea_field( (string) $params['question'] ) : '';
		$tradition = isset( $params['tradition'] ) ? sanitize_key( (string) $params['tradition'] ) : '';
		$history   = self::sanitize_ask_history( isset( $params['history'] ) ? $params['history'] : array() );

		if ( '' === trim( $question ) ) {
			return new WP_Error( 'thw_missing_question', __( 'Please enter a question.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		if ( strlen( $question ) > 2000 ) {
			return new WP_Error( 'thw_question_too_long', __( 'Please shorten your question (2000 characters max).', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$audience = thw_premium_get_ai_ask_audience();
		if ( 'disabled' === $audience ) {
			return new WP_Error(
				'thw_ask_disabled',
				__( 'Ask a Question is disabled on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		if ( ! thw_premium_current_user_can_use_ai_ask() ) {
			return new WP_Error(
				'thw_ask_login_required',
				__( 'Please log in to ask a Bible question.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		if ( ! thw_premium_ai_frontend_available() ) {
			$reason = thw_premium_ai_frontend_unavailable_reason();
			return new WP_Error(
				'thw_ai_disabled',
				$reason ? $reason : __( 'AI features are not enabled or configured on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		$rate_key = self::rate_limit_key();
		if ( ! self::check_rate_limit( $rate_key ) ) {
			return new WP_Error( 'thw_ai_rate_limit', __( 'Hourly Ask a Question limit reached. Please try again later.', 'hidden-word-bible-lessons' ), array( 'status' => 429 ) );
		}

		$resolved  = thw_premium_resolve_explain_rules_for_request( $tradition, $question );
		$preset    = $resolved['preset'];
		$checklist = ! empty( $resolved['checklist'] ) ? $resolved['checklist'] : $resolved['rules'];

		if ( is_user_logged_in() && thw_premium_user_tradition_enabled() && 'site' !== $preset ) {
			thw_premium_set_user_tradition_preset( get_current_user_id(), $preset );
		}

		$ask_rules = get_option( 'thw_ai_ask_rules', thw_premium_default_ai_ask_rules() );
		if ( '' === trim( (string) $ask_rules ) ) {
			$ask_rules = thw_premium_default_ai_ask_rules();
		}

		$tradition_label    = self::tradition_label_from_resolved( $resolved );
		$combined_rules     = "Tradition / explain rules:\n{$resolved['rules']}\n\nAsk a Question rules:\n{$ask_rules}";
		$compliance_rules   = "Tradition checklist:\n{$checklist}\n\nAsk a Question rules:\n{$ask_rules}";
		$system_instruction = thw_premium_build_ai_system_instruction( $combined_rules );

		$history_digest = $history ? md5( wp_json_encode( $history ) ) : '';
		$cache_key      = 'thw_ai_ask_' . md5( 'v3|' . $question . '|' . $preset . '|' . $resolved['digest_hash'] . '|' . $ask_rules . '|' . $history_digest );
		$cached         = get_transient( $cache_key );
		if ( is_array( $cached ) && ! empty( $cached['content'] ) ) {
			$response = array(
				'content'           => (string) $cached['content'],
				'citations'         => isset( $cached['citations'] ) ? (string) $cached['citations'] : '',
				'citationsHtml'     => isset( $cached['citationsHtml'] ) ? (string) $cached['citationsHtml'] : '',
				'tradition'         => $preset,
				'cached'            => true,
				'complianceFlagged' => ! empty( $cached['flagged'] ),
				'lessons'           => isset( $cached['lessons'] ) && is_array( $cached['lessons'] ) ? $cached['lessons'] : array(),
			);
			return new WP_REST_Response( $response );
		}

		$citation_hint = '';
		if ( ! empty( $resolved['digest'] ) && false !== strpos( (string) $resolved['digest'], 'Cited references' ) ) {
			$citation_hint = ' The Rules include a Cited references block for THIS tradition and topic. '
				. 'When you mention tradition sources, use ONLY identifiers from that block. '
				. 'Never invent CIC, CCC, BF&M, or other confession citations unless they appear there.';
		} else {
			$citation_hint = ' If the Rules have no Cited references block, do not invent confession citations.';
		}

		$prompt = self::build_prompt( $question, $resolved, $citation_hint, $history );

		$result = THW_Premium_AI_Client::generate_text( $prompt, $system_instruction );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Model sometimes ignores selected tradition and asks which church they belong to.
		if ( self::answer_asks_which_tradition( $result ) && self::has_selected_tradition( $preset ) ) {
			$retry_system = $system_instruction
				. "\n\nCRITICAL CORRECTION: The visitor already selected \"{$tradition_label}\". "
				. 'Answer that tradition’s teaching on the question. Do NOT ask which tradition, denomination, or church they belong to. '
				. 'Do NOT list Catholic, Orthodox, Baptist, Anglican, Methodist, Presbyterian, or similar as options.';
			$retry_result = THW_Premium_AI_Client::generate_text( $prompt, $retry_system );
			if ( ! is_wp_error( $retry_result ) && ! self::answer_asks_which_tradition( $retry_result ) ) {
				$result = $retry_result;
			} elseif ( ! is_wp_error( $retry_result ) ) {
				$result = $retry_result;
			}
		}

		$flagged = false;
		if ( thw_premium_ai_compliance_check_enabled() ) {
			$compliance = self::enforce_compliance( $prompt, $system_instruction, $compliance_rules, $result );
			$result     = $compliance['content'];
			$flagged    = $compliance['flagged'];

			if ( $flagged && 'block' === thw_premium_get_ai_compliance_failure_action() ) {
				return new WP_Error(
					'thw_ai_compliance_failed',
					__( 'We could not generate an answer that follows the selected tradition’s guidelines. Please try again.', 'hidden-word-bible-lessons' ),
					array( 'status' => 502 )
				);
			}
		}

		$html = THW_Premium_AI_Client::format_html_response( $result );
		$html = THW_Premium_AI_Study_Finder::strip_foreign_tradition_citations_from_text( $html, $resolved );

		$fallback_citations = THW_Premium_AI_Study_Finder::extract_cited_references_summary( (string) $resolved['digest'] );
		$citations          = THW_Premium_AI_Study_Finder::sanitize_citations_for_tradition(
			$fallback_citations,
			$fallback_citations,
			$resolved
		);
		$citations_html     = THW_Premium_AI_Study_Finder::format_citations_html( $citations );

		$lessons = array();
		if ( thw_premium_ai_ask_include_lessons() ) {
			$lessons = THW_Premium_AI_Study_Finder::suggest_related_lessons( $question, 5 );
		}

		set_transient(
			$cache_key,
			array(
				'content'       => $html,
				'citations'     => $citations,
				'citationsHtml' => $citations_html,
				'flagged'       => $flagged,
				'lessons'       => $lessons,
			),
			HOUR_IN_SECONDS
		);

		self::increment_rate_limit( $rate_key );

		return new WP_REST_Response(
			array(
				'content'           => $html,
				'citations'         => $citations,
				'citationsHtml'     => $citations_html,
				'tradition'         => $preset,
				'cached'            => false,
				'complianceFlagged' => $flagged,
				'lessons'           => $lessons,
			)
		);
	}

	/**
	 * Sanitize optional prior conversation turns for follow-up context.
	 *
	 * @param mixed $raw Raw history from the request.
	 * @return array<int, array{question:string,answer:string}>
	 */
	public static function sanitize_ask_history( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$clean = array();
		foreach ( array_slice( $raw, -4 ) as $turn ) {
			if ( ! is_array( $turn ) ) {
				continue;
			}
			$q = isset( $turn['question'] ) ? sanitize_textarea_field( (string) $turn['question'] ) : '';
			$a = isset( $turn['answer'] ) ? sanitize_textarea_field( (string) $turn['answer'] ) : '';
			if ( '' === trim( $q ) || '' === trim( $a ) ) {
				continue;
			}
			if ( strlen( $q ) > 2000 ) {
				$q = substr( $q, 0, 2000 );
			}
			if ( strlen( $a ) > 2500 ) {
				$a = substr( $a, 0, 2500 );
			}
			$clean[] = array(
				'question' => $q,
				'answer'   => $a,
			);
		}

		return $clean;
	}

	/**
	 * Build the Ask user prompt (tradition already selected is named explicitly).
	 *
	 * @param string               $question      Visitor question.
	 * @param array<string, mixed> $resolved      Resolve payload from thw_premium_resolve_explain_rules_for_request().
	 * @param string               $citation_hint Citation guardrail sentence(s).
	 * @param array<int, array{question:string,answer:string}> $history Prior turns (optional).
	 * @return string
	 */
	public static function build_prompt( $question, $resolved, $citation_hint = '', $history = array() ) {
		$preset = isset( $resolved['preset'] ) ? sanitize_key( (string) $resolved['preset'] ) : '';
		$label  = self::tradition_label_from_resolved( $resolved );

		$tradition_block = '';
		if ( self::has_selected_tradition( $preset ) ) {
			$tradition_block = "Selected tradition (already chosen by the visitor): {$label}.\n"
				. "Answer from THIS tradition using the Rules digests and stance gates.\n"
				. "If Church policy rules are present for a subject, follow those church rules over tradition digests for that subject.\n"
				. "Do NOT ask which tradition, denomination, or church the visitor belongs to.\n"
				. "Do NOT give a multi-tradition survey or ask them to choose Catholic, Orthodox, Baptist, Anglican, Methodist, Presbyterian, or similar.\n\n";
		} else {
			$tradition_block = "If Church policy rules are present for a subject, follow those church rules over any conflicting general guidance for that subject.\n\n";
		}

		$history_block = '';
		if ( is_array( $history ) && ! empty( $history ) ) {
			$history_block = "Prior conversation (for context only; answer the latest user question):\n";
			foreach ( $history as $turn ) {
				$history_block .= 'User: ' . (string) $turn['question'] . "\n";
				$history_block .= 'Assistant: ' . (string) $turn['answer'] . "\n\n";
			}
		}

		return $tradition_block
			. $history_block
			. "User question:\n{$question}\n\n"
			. 'Answer this Bible / faith question for a church website visitor. '
			. 'Use HTML paragraphs (<p> tags) only. Be pastoral, clear, and concise (typically 2–5 short paragraphs). '
			. 'Stay within Scripture and the tradition rules. When a doctrinal gate matches, state the tradition stance clearly. '
			. 'Do not invent verse quotations; you may name references. '
			. 'If prior conversation is present, treat this as a follow-up and stay consistent with it. '
			. 'Encourage consulting trusted pastors/teachers for sensitive personal situations. '
			. 'AI is not a substitute for pastoral counseling.'
			. $citation_hint;
	}

	/**
	 * Human-readable tradition label from a resolve payload.
	 *
	 * @param array<string, mixed> $resolved Resolve payload.
	 * @return string
	 */
	public static function tradition_label_from_resolved( $resolved ) {
		$preset = isset( $resolved['preset'] ) ? sanitize_key( (string) $resolved['preset'] ) : '';
		if ( ! empty( $resolved['pack'] ) && is_array( $resolved['pack'] ) && ! empty( $resolved['pack']['label'] ) ) {
			return (string) $resolved['pack']['label'];
		}
		$presets = thw_premium_ai_explain_rule_presets();
		if ( '' !== $preset && isset( $presets[ $preset ]['label'] ) ) {
			return (string) $presets[ $preset ]['label'];
		}
		return '' !== $preset ? $preset : __( 'Site default', 'hidden-word-bible-lessons' );
	}

	/**
	 * Whether a real tradition preset (not site default) is active.
	 *
	 * @param string $preset Preset slug.
	 * @return bool
	 */
	public static function has_selected_tradition( $preset ) {
		$preset = sanitize_key( (string) $preset );
		return '' !== $preset && 'site' !== $preset;
	}

	/**
	 * Detect answers that ask the visitor which tradition they belong to.
	 *
	 * @param string $html Answer HTML or text.
	 * @return bool
	 */
	public static function answer_asks_which_tradition( $html ) {
		$text = wp_strip_all_tags( (string) $html );
		if ( '' === trim( $text ) ) {
			return false;
		}

		return (bool) preg_match(
			'/\bwhich\s+(?:christian\s+)?(?:tradition|denomination|church)\b|\bwhat\s+(?:tradition|denomination|church)\s+(?:do\s+you|are\s+you)\b|\bbelong\s+to(?:—|-|,|:)?\s*(?:for\s+example)?\b.*\b(?:catholic|orthodox|baptist|anglican|methodist|presbyterian)\b/iu',
			$text
		);
	}

	/**
	 * Compliance check with one retry (same pattern as Explain).
	 *
	 * @param string $prompt             User prompt.
	 * @param string $system_instruction System instruction.
	 * @param string $rules              Checklist rules.
	 * @param string $result             First-pass HTML.
	 * @return array{content:string, flagged:bool, reason:string}
	 */
	public static function enforce_compliance( $prompt, $system_instruction, $rules, $result ) {
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
	 * Rate-limit key for current user or guest IP.
	 *
	 * @return string
	 */
	private static function rate_limit_key() {
		if ( is_user_logged_in() ) {
			return 'user_' . (int) get_current_user_id();
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : 'guest';
		return 'ip_' . md5( $ip );
	}

	/**
	 * @param string $key Rate key.
	 * @return bool
	 */
	private static function check_rate_limit( $key ) {
		$transient = 'thw_ai_ask_' . sanitize_key( $key );
		$count     = (int) get_transient( $transient );
		return $count < self::RATE_LIMIT;
	}

	/**
	 * @param string $key Rate key.
	 */
	private static function increment_rate_limit( $key ) {
		$transient = 'thw_ai_ask_' . sanitize_key( $key );
		$count     = (int) get_transient( $transient );
		set_transient( $transient, $count + 1, self::RATE_WINDOW );
	}
}
