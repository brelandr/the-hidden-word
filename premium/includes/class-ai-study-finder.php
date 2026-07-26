<?php
/**
 * Keyword Bible study search shortcode and REST API.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_AI_Study_Finder
 */
class THW_Premium_AI_Study_Finder {

	const LOCAL_CANDIDATE_LIMIT = 15;

	/**
	 * Register the public shortcode (safe to call more than once).
	 */
	public static function register_shortcode() {
		thw_premium_register_shortcode( 'thw_study_finder', array( __CLASS__, 'render_shortcode' ) );
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
	 * Convert curly quotes in study-finder shortcodes to straight quotes.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public static function normalize_shortcode_quotes( $content ) {
		if ( false === strpos( (string) $content, '[thw_study_finder' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/\[thw_study_finder([^\]]*)\]/i',
			static function ( $matches ) {
				$atts = str_replace(
					array( "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x98", "\xE2\x80\x99", '“', '”', '‘', '’' ),
					array( '"', '"', "'", "'", '"', '"', "'", "'" ),
					$matches[1]
				);
				return '[thw_study_finder' . $atts . ']';
			},
			(string) $content
		);
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		THW_Premium_Study_REST::register_routes();
	}

	/**
	 * Render study finder shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		return THW_Premium_Study_Shortcode::render( $atts );
	}

	/**
	 * REST handler for study search.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_study_search( $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_body_params();
		}
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_params();
		}

		$keywords = isset( $params['keywords'] ) ? sanitize_text_field( (string) $params['keywords'] ) : '';
		$limit    = isset( $params['limit'] ) ? absint( $params['limit'] ) : 0;
		$tradition = isset( $params['tradition'] ) ? sanitize_key( (string) $params['tradition'] ) : '';

		if ( '' === trim( $keywords ) ) {
			return new WP_Error( 'thw_missing_keywords', __( 'Please enter a topic to search.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$audience = thw_premium_get_ai_study_audience();
		if ( 'disabled' === $audience ) {
			return new WP_Error(
				'thw_study_disabled',
				__( 'AI topic Scripture search is disabled on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		if ( ! thw_premium_current_user_can_use_ai_study_search() ) {
			return new WP_Error(
				'thw_study_login_required',
				__( 'Please log in to search Scripture by topic.', 'hidden-word-bible-lessons' ),
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

		$max_results = (int) get_option( 'thw_ai_study_result_count', 5 );
		if ( $limit < 1 ) {
			$limit = $max_results;
		}
		$limit = min( $limit, $max_results );

		$resolved = thw_premium_resolve_explain_rules_for_request( $tradition, $keywords );
		if ( is_user_logged_in() && thw_premium_user_tradition_enabled() && 'site' !== $resolved['preset'] ) {
			thw_premium_set_user_tradition_preset( get_current_user_id(), $resolved['preset'] );
		}

		$cache_key = 'hwbl_ai_study_' . md5(
			strtolower( $keywords ) . '|' . (string) $resolved['preset'] . '|' . (string) $limit . '|' . md5( (string) ( $resolved['rules'] ?? '' ) )
		);
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && isset( $cached['results'] ) ) {
			$guidance = $cached;
		} else {
			$guidance = self::generate_topic_guidance( $keywords, $limit, $resolved );
			if ( is_wp_error( $guidance ) ) {
				return $guidance;
			}
			if ( ! empty( $guidance['results'] ) ) {
				set_transient( $cache_key, $guidance, HOUR_IN_SECONDS );
			}
		}

		if ( empty( $guidance['results'] ) ) {
			return new WP_Error(
				'thw_study_empty',
				__( 'Could not generate Scripture guidance for that topic. Please try again.', 'hidden-word-bible-lessons' ),
				array( 'status' => 502 )
			);
		}

		return new WP_REST_Response(
			array(
				'keywords'          => $keywords,
				'source'            => 'ai',
				'tradition'         => $resolved['preset'],
				'results'           => $guidance['results'],
				'complianceFlagged' => ! empty( $guidance['flagged'] ),
				'lessons'           => thw_premium_ai_study_include_lessons()
					? self::suggest_related_lessons( $keywords, min( 5, $limit ) )
					: array(),
			)
		);
	}

	/**
	 * Generate AI Scripture guidance for a topic.
	 *
	 * @param string               $keywords Topic keywords.
	 * @param int                  $limit    Max passages.
	 * @param array<string,mixed>|string|null $resolved Optional resolve payload or explain-rules string.
	 * @return array{results:array<int, array<string, mixed>>, flagged:bool}|WP_Error
	 */
	public static function generate_topic_guidance( $keywords, $limit = 5, $resolved = null ) {
		if ( is_string( $resolved ) ) {
			$explain_rules = $resolved;
			$checklist     = $resolved;
			$resolved      = null;
		} elseif ( is_array( $resolved ) ) {
			$explain_rules = isset( $resolved['rules'] ) ? (string) $resolved['rules'] : '';
			$checklist     = ! empty( $resolved['checklist'] ) ? (string) $resolved['checklist'] : $explain_rules;
		} else {
			$resolved      = thw_premium_resolve_explain_rules_for_request( '', $keywords );
			$explain_rules = (string) $resolved['rules'];
			$checklist     = ! empty( $resolved['checklist'] ) ? (string) $resolved['checklist'] : $explain_rules;
		}

		if ( '' === trim( (string) $explain_rules ) ) {
			$explain_rules = thw_premium_default_ai_explain_rules();
			$checklist     = $explain_rules;
		}

		$study_rules = get_option( 'thw_ai_study_rules', thw_premium_default_ai_study_rules() );
		if ( '' === trim( (string) $study_rules ) ) {
			$study_rules = thw_premium_default_ai_study_rules();
		}

		// Tradition digest/rules + study-search rules go out as a mandatory
		// system instruction rather than being concatenated into the user
		// prompt below, so the model weighs them as priority constraints
		// instead of just more text to summarize alongside the topic.
		$combined_rules     = "Tradition / explain rules:\n{$explain_rules}\n\nStudy search rules:\n{$study_rules}";
		$compliance_rules   = "Tradition checklist:\n{$checklist}\n\nStudy search rules:\n{$study_rules}";
		$system_instruction = thw_premium_build_ai_system_instruction( $combined_rules );

		$citation_hint = '';
		if ( is_array( $resolved ) && ! empty( $resolved['digest'] ) && false !== strpos( (string) $resolved['digest'], 'Cited references' ) ) {
			$citation_hint = ' The Rules include a Cited references block for THIS tradition and topic. '
				. 'For EVERY passage, fill the citations field with ONLY those exact identifiers from that block. '
				. 'Never invent CIC, CCC, BF&M, or any other confession’s citations unless they appear in that Cited references block. '
				. 'Do not leave citations empty when Cited references are present.';
		} else {
			$citation_hint = ' If the Rules have no Cited references block for this topic, leave citations as an empty string. '
				. 'Never invent CIC, CCC, Canon Law, Catechism, BF&M, or other tradition documents not present in the Rules.';
		}

		$prompt = "User topic: {$keywords}\n\n"
			. 'Recommend up to ' . (int) $limit . ' Bible passages that best address this topic. '
			. 'Return a JSON object with key "passages" whose value is an array of objects with keys reference, summary, commentary, and citations. '
			. 'reference: string like "Matthew 19:3-9". '
			. 'summary: one short sentence. '
			. 'commentary: 2-4 pastoral sentences applying the passage using the tradition rules and doctrinal logic gates. '
			. 'When a doctrinal gate matches the topic, state the tradition stance clearly (yes/no/conditional/pastoral/silence) and any required conditions. '
			. 'citations: short string copied from the Rules Cited references block for this tradition only (or empty string if none). '
			. 'Do not invent verse quotations beyond naming the reference. Prefer clear, well-known passages.'
			. $citation_hint;

		$passage_item = array(
			'type'                 => 'object',
			'properties'           => array(
				'reference'  => array( 'type' => 'string' ),
				'summary'    => array( 'type' => 'string' ),
				'commentary' => array( 'type' => 'string' ),
				'citations'  => array( 'type' => 'string' ),
			),
			'required'             => array( 'reference', 'summary', 'commentary', 'citations' ),
			'additionalProperties' => false,
		);

		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'passages' => array(
					'type'  => 'array',
					'items' => $passage_item,
				),
			),
			'required'             => array( 'passages' ),
			'additionalProperties' => false,
		);

		$result = THW_Premium_AI_Client::generate_json( $prompt, $schema, $system_instruction );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$parsed = self::parse_ai_scripture_guidance( $result );
		if ( empty( $parsed ) ) {
			return new WP_Error(
				'thw_study_parse_failed',
				__( 'The AI response could not be read. Please try again.', 'hidden-word-bible-lessons' ),
				array( 'status' => 502 )
			);
		}

		$flagged = false;
		if ( thw_premium_ai_compliance_check_enabled() ) {
			$compliance = self::enforce_compliance( $prompt, $system_instruction, $compliance_rules, $parsed );
			if ( is_wp_error( $compliance ) ) {
				return $compliance;
			}
			$parsed  = $compliance['rows'];
			$flagged = $compliance['flagged'];

			if ( $flagged && 'block' === thw_premium_get_ai_compliance_failure_action() ) {
				return new WP_Error(
					'thw_ai_compliance_failed',
					__( 'We could not generate Scripture guidance that follows the selected tradition’s guidelines. Please try again.', 'hidden-word-bible-lessons' ),
					array( 'status' => 502 )
				);
			}
		}

		$fallback_citations = '';
		if ( is_array( $resolved ) && ! empty( $resolved['digest'] ) ) {
			$fallback_citations = self::extract_cited_references_summary( (string) $resolved['digest'] );
		}

		$results = array();
		foreach ( array_slice( $parsed, 0, max( 1, (int) $limit ) ) as $row ) {
			$reference  = sanitize_text_field( (string) $row['reference'] );
			$summary    = sanitize_text_field( (string) $row['summary'] );
			$commentary = (string) ( $row['commentary'] ?? '' );
			$citations  = isset( $row['citations'] ) ? sanitize_text_field( (string) $row['citations'] ) : '';
			$citations  = self::sanitize_citations_for_tradition(
				$citations,
				$fallback_citations,
				is_array( $resolved ) ? $resolved : array()
			);
			$commentary = self::strip_foreign_tradition_citations_from_text(
				$commentary,
				is_array( $resolved ) ? $resolved : array()
			);
			$commentary = wp_kses_post( wpautop( $commentary ) );
			$url        = self::find_lesson_url_for_reference( $reference );

			if ( '' === $reference ) {
				continue;
			}

			$results[] = array(
				'reference'     => $reference,
				'summary'       => $summary,
				'commentary'    => $commentary,
				'citations'     => $citations,
				'citationsHtml' => self::format_citations_html( $citations ),
				'url'           => $url,
			);
		}

		if ( empty( $results ) ) {
			return new WP_Error(
				'thw_study_empty',
				__( 'Could not generate Scripture guidance for that topic. Please try again.', 'hidden-word-bible-lessons' ),
				array( 'status' => 502 )
			);
		}

		return array(
			'results' => $results,
			'flagged' => $flagged,
		);
	}

	/**
	 * Verify parsed Scripture guidance rows against the resolved rules,
	 * retrying the whole generation once with a corrective nudge if flagged.
	 *
	 * If it's still flagged after the retry (or the retry itself fails), we
	 * don't withhold the guidance — we fall back to the best rows we have
	 * (the retry's, if we got usable ones; otherwise the first pass) marked
	 * as flagged, so the caller can show a stronger disclaimer instead of a
	 * generic error. This is a backstop against clear rule violations, not a
	 * guarantee.
	 *
	 * @param string                          $prompt             Original user-content prompt.
	 * @param string                          $system_instruction Original system instruction (mandatory rules).
	 * @param string                          $combined_rules     Resolved tradition + study rules text for compliance review.
	 * @param array<int, array<string, mixed>> $parsed             First-pass parsed rows.
	 * @return array{rows:array<int, array<string, mixed>>, flagged:bool}
	 */
	private static function enforce_compliance( $prompt, $system_instruction, $combined_rules, $parsed ) {
		$combined_text = self::combine_rows_for_compliance_review( $parsed );
		$check         = THW_Premium_AI_Client::check_compliance( $combined_rules, $combined_text );
		if ( $check['compliant'] ) {
			return array(
				'rows'    => $parsed,
				'flagged' => false,
			);
		}

		$retry_system = $system_instruction
			. "\n\nA previous set of recommendations was flagged for this specific issue: {$check['reason']} Revise your recommendations so they fully comply with the Rules above.";

		$schema = array(
			'type'                 => 'object',
			'properties'           => array(
				'passages' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'reference'  => array( 'type' => 'string' ),
							'summary'    => array( 'type' => 'string' ),
							'commentary' => array( 'type' => 'string' ),
							'citations'  => array( 'type' => 'string' ),
						),
						'required'             => array( 'reference', 'summary', 'commentary', 'citations' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'             => array( 'passages' ),
			'additionalProperties' => false,
		);

		$retry_result = THW_Premium_AI_Client::generate_json( $prompt, $schema, $retry_system );
		if ( is_wp_error( $retry_result ) ) {
			// The retry call itself failed (network/provider error) — fall
			// back to the first-pass rows, flagged, rather than losing the
			// user's request entirely.
			return array(
				'rows'    => $parsed,
				'flagged' => true,
			);
		}

		$retry_parsed = self::parse_ai_scripture_guidance( $retry_result );
		if ( empty( $retry_parsed ) ) {
			// Retry didn't parse — same fallback as above.
			return array(
				'rows'    => $parsed,
				'flagged' => true,
			);
		}

		$retry_check = THW_Premium_AI_Client::check_compliance( $combined_rules, self::combine_rows_for_compliance_review( $retry_parsed ) );

		return array(
			'rows'    => $retry_parsed,
			'flagged' => ! $retry_check['compliant'],
		);
	}

	/**
	 * Flatten parsed rows into plain text for a compliance review call.
	 *
	 * @param array<int, array<string, mixed>> $rows Parsed rows.
	 * @return string
	 */
	private static function combine_rows_for_compliance_review( $rows ) {
		$parts = array();
		foreach ( (array) $rows as $row ) {
			$reference  = isset( $row['reference'] ) ? (string) $row['reference'] : '';
			$summary    = isset( $row['summary'] ) ? (string) $row['summary'] : '';
			$commentary = isset( $row['commentary'] ) ? wp_strip_all_tags( (string) $row['commentary'] ) : '';
			$citations  = isset( $row['citations'] ) ? wp_strip_all_tags( (string) $row['citations'] ) : '';
			$parts[]    = trim( "{$reference}: {$summary} {$commentary} {$citations}" );
		}
		return implode( "\n", $parts );
	}

	/**
	 * Keep citations aligned with the resolved tradition digest (drop foreign confessions the model invents).
	 *
	 * @param string               $citations          AI citations field.
	 * @param string               $fallback_citations Pack-derived citation summary.
	 * @param array<string, mixed> $resolved           Resolve payload.
	 * @return string
	 */
	public static function sanitize_citations_for_tradition( $citations, $fallback_citations, $resolved ) {
		$citations          = trim( (string) $citations );
		$fallback_citations = trim( (string) $fallback_citations );
		$digest             = isset( $resolved['digest'] ) ? (string) $resolved['digest'] : '';
		$preset             = isset( $resolved['preset'] ) ? (string) $resolved['preset'] : '';

		if ( '' !== $citations && self::citations_match_tradition( $citations, $digest, $preset ) ) {
			return $citations;
		}

		if ( '' !== $fallback_citations ) {
			return $fallback_citations;
		}

		// Foreign or empty AI citations with no pack fallback → blank (do not show CIC for UPCI, etc.).
		return '';
	}

	/**
	 * Whether citation text is compatible with the active tradition digest/preset.
	 *
	 * @param string $citations Citation string.
	 * @param string $digest    Doctrine digest text.
	 * @param string $preset    Resolved preset slug.
	 * @return bool
	 */
	public static function citations_match_tradition( $citations, $digest, $preset ) {
		$citations = (string) $citations;
		$digest    = (string) $digest;
		$preset    = sanitize_key( $preset );

		$has_cic  = (bool) preg_match( '/\bCIC\b|\bCanon Law\b|\bCCC\b/i', $citations );
		$has_bfm  = (bool) preg_match( '/\bBF&M\b/i', $citations );
		$has_upci = (bool) preg_match( '/\bUPCI\b/i', $citations );
		$has_ag   = (bool) preg_match( '/\bAG\s*16\b|\bAG\s*Fundamental Truths\b|\b16\s*Fundamental Truths\b/i', $citations );
		$has_sda  = (bool) preg_match( '/\bSDA\s*28\s*Fundamental Beliefs\b|\b28\s*Fundamental Beliefs\b/i', $citations );
		$has_cog  = (bool) preg_match( '/\bCOG\s*Declaration\b|\bChurch of God Declaration\b/i', $citations );
		$has_iphc = (bool) preg_match( '/\bIPHC\b/i', $citations );
		$has_wcf  = (bool) preg_match( '/\bWestminster Confession\b|\bWCF\b/i', $citations );
		$has_ac   = (bool) preg_match( '/\bAugsburg Confession\b|\bLCMS Brief Statement\b/i', $citations );
		$has_39   = (bool) preg_match( '/\bThirty-Nine Articles\b/i', $citations );
		$has_eastern  = (bool) preg_match( '/\bNicene-Constantinopolitan Creed\b|\bSeven Ecumenical Councils\b|\bEastern Orthodox\b/i', $citations );
		$has_oriental = (bool) preg_match( '/\bOriental Orthodox\b|\bFirst Three Ecumenical Councils\b|\bMiaphysite\b/i', $citations );
		$has_umc      = (bool) preg_match( '/\bUMC Articles of Religion\b/i', $citations );
		$has_ame      = (bool) preg_match( '/\bAME Articles of Religion\b/i', $citations );
		$has_naz      = (bool) preg_match( '/\bNazarene Articles of Faith\b/i', $citations );
		$has_nh       = (bool) preg_match( '/\bNew Hampshire Baptist Confession\b|\b1689\s+London Baptist Confession\b|\bLBC\s*1689\b/i', $citations );
		$has_menn     = (bool) preg_match( '/\bMennonite Confession of Faith\b/i', $citations );
		$has_umjc     = (bool) preg_match( '/\bUMJC Statement of Faith\b/i', $citations );
		$has_lds      = (bool) preg_match( '/\bLDS Articles of Faith\b/i', $citations );
		$has_jw       = (bool) preg_match( '/\bJW Beliefs\b/i', $citations );
		$has_rd       = (bool) preg_match( '/\bRichmond Declaration(?:\s+of Faith)?\b/i', $citations );
		$has_coc      = (bool) preg_match( '/\bChurches of Christ Teaching\b/i', $citations );
		$has_sa       = (bool) preg_match( '/\bSalvation Army Doctrines\b/i', $citations );
		$has_cs       = (bool) preg_match( '/\bChristian Science Tenets\b/i', $citations );
		$has_nae      = (bool) preg_match( '/\bNAE Statement of Faith\b/i', $citations );
		$has_efca     = (bool) preg_match( '/\bEFCA Statement of Faith\b/i', $citations );
		$has_cc       = (bool) preg_match( '/\bCalvary Chapel Statement of Faith\b/i', $citations );
		$has_vineyard = (bool) preg_match( '/\bVineyard USA Core Values\b|\bVineyard USA\b/i', $citations );
		$has_doc      = (bool) preg_match( '/\bDisciples Preamble\b/i', $citations );
		$has_ucc      = (bool) preg_match( '/\bUCC Statement of Faith\b/i', $citations );
		$has_pwf      = (bool) preg_match( '/\bPWF Statement of Faith\b/i', $citations );
		$has_solas    = (bool) preg_match( '/\bFive Solas\b|\bApostles[’\'] Creed\b/i', $citations );

		// Bind known confession families to presets (do not trust digest prose that may mention other traditions as "do not cite").
		if ( $has_cic && 'catholic' !== $preset ) {
			return false;
		}
		if ( $has_bfm && ! in_array( $preset, array( 'sbc', 'baptist' ), true ) ) {
			return false;
		}
		if ( $has_upci && 'upci' !== $preset ) {
			return false;
		}
		if ( $has_ag && 'assemblies_god' !== $preset ) {
			return false;
		}
		if ( $has_sda && 'adventist' !== $preset ) {
			return false;
		}
		if ( $has_cog && 'church_of_god' !== $preset ) {
			return false;
		}
		if ( $has_iphc && 'iphc' !== $preset ) {
			return false;
		}
		if ( $has_wcf && 'reformed' !== $preset ) {
			return false;
		}
		if ( $has_ac && 'lutheran' !== $preset ) {
			return false;
		}
		if ( $has_39 && 'anglican' !== $preset ) {
			return false;
		}
		if ( $has_eastern && ! in_array( $preset, array( 'orthodox', 'greek_orthodox', 'russian_orthodox' ), true ) ) {
			return false;
		}
		if ( $has_oriental && ! in_array( $preset, array( 'oriental_orthodox', 'coptic', 'ethiopian_orthodox', 'armenian' ), true ) ) {
			return false;
		}
		if ( $has_umc && 'methodist' !== $preset ) {
			return false;
		}
		if ( $has_ame && 'ame' !== $preset ) {
			return false;
		}
		if ( $has_naz && 'holiness' !== $preset ) {
			return false;
		}
		if ( $has_nh && 'baptist' !== $preset ) {
			return false;
		}
		if ( $has_menn && 'mennonite' !== $preset ) {
			return false;
		}
		if ( $has_umjc && 'messianic' !== $preset ) {
			return false;
		}
		if ( $has_lds && 'lds' !== $preset ) {
			return false;
		}
		if ( $has_jw && 'jehovah_witnesses' !== $preset ) {
			return false;
		}
		if ( $has_rd && 'quaker' !== $preset ) {
			return false;
		}
		if ( $has_coc && 'church_of_christ' !== $preset ) {
			return false;
		}
		if ( $has_sa && 'salvation_army' !== $preset ) {
			return false;
		}
		if ( $has_cs && 'christian_science' !== $preset ) {
			return false;
		}
		if ( $has_nae && 'nondenom' !== $preset ) {
			return false;
		}
		if ( $has_efca && 'evangelical_free' !== $preset ) {
			return false;
		}
		if ( $has_cc && 'calvary_chapel' !== $preset ) {
			return false;
		}
		if ( $has_vineyard && 'vineyard' !== $preset ) {
			return false;
		}
		if ( $has_doc && 'disciples_christ' !== $preset ) {
			return false;
		}
		if ( $has_ucc && 'congregational' !== $preset ) {
			return false;
		}
		if ( $has_pwf && 'pentecostal' !== $preset ) {
			return false;
		}
		if ( $has_solas && 'general' !== $preset ) {
			return false;
		}

		unset( $digest );
		return true;
	}

	/**
	 * Remove invented foreign confession mentions from commentary when they conflict with the active tradition.
	 *
	 * @param string               $text     Commentary text.
	 * @param array<string, mixed> $resolved Resolve payload.
	 * @return string
	 */
	public static function strip_foreign_tradition_citations_from_text( $text, $resolved ) {
		$text   = (string) $text;
		$digest = isset( $resolved['digest'] ) ? (string) $resolved['digest'] : '';
		$preset = isset( $resolved['preset'] ) ? sanitize_key( (string) $resolved['preset'] ) : '';

		if ( 'catholic' !== $preset && false === stripos( $digest, 'CIC' ) && false === stripos( $digest, 'CCC' ) ) {
			$text = preg_replace( '/\bCIC\s*cc?\.?\s*[\d,\s\-–—]+/iu', '', $text );
			$text = preg_replace( '/\bCCC\s*[\d,\s\-–—]+/iu', '', $text );
			$text = preg_replace( '/\b(?:Code of\s+)?Canon Law\b/iu', '', $text );
			$text = preg_replace( '/\bCatechism(?:\s+of\s+the\s+Catholic\s+Church)?\b/iu', '', $text );
		}

		return trim( preg_replace( '/[ \t]{2,}/', ' ', (string) $text ) );
	}

	/**
	 * Pull a compact citations summary from a doctrine digest's Cited references block.
	 *
	 * @param string $digest Digest text from tradition pack builder.
	 * @return string
	 */
	public static function extract_cited_references_summary( $digest ) {
		$digest = (string) $digest;
		if ( '' === $digest || false === strpos( $digest, 'Cited references' ) ) {
			return '';
		}

		$parts = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $digest ) as $line ) {
			$line = trim( (string) $line );
			if ( ! preg_match( '/^-\s*Topic\s+/i', $line ) ) {
				continue;
			}
			if ( preg_match( '/—\s*((?:CIC|CCC|BF&M|UPCI|AG\s*16|COG|IPHC|Classical Pentecostal|Westminster|WCF|Augsburg|LCMS Brief Statement|Thirty-Nine|Nicene|Apostles[’\'] Creed|Five Solas|Eastern Orthodox|Oriental Orthodox|First Three Ecumenical Councils|Miaphysite|Holy Mysteries|UMC Articles|AME Articles|Nazarene Articles|SDA\s*28\s*Fundamental Beliefs|New Hampshire Baptist|1689\s+London Baptist|LBC\s*1689|Mennonite Confession|UMJC Statement|LDS Articles of Faith|JW Beliefs|Richmond Declaration|Churches of Christ Teaching|Salvation Army Doctrines|Christian Science Tenets|NAE Statement of Faith|EFCA Statement of Faith|Calvary Chapel Statement of Faith|Vineyard USA|Disciples Preamble|UCC Statement of Faith|PWF Statement of Faith)[^—]+)/iu', $line, $m ) ) {
				$parts[] = trim( $m[1], " \t;" );
			}
		}

		$parts = array_values( array_unique( array_filter( $parts ) ) );
		return implode( '; ', array_slice( $parts, 0, 4 ) );
	}

	/**
	 * Turn tradition citation text into HTML with external links (new tab) where known.
	 *
	 * BF&M articles deep-link to bfm.sbc.net anchors; CIC/CCC link to Vatican indexes.
	 *
	 * @param string $citations Plain citation summary.
	 * @return string Safe HTML (empty when no citations).
	 */
	public static function format_citations_html( $citations ) {
		$citations = trim( wp_strip_all_tags( (string) $citations ) );
		if ( '' === $citations ) {
			return '';
		}

		$segments   = preg_split( '/\s*;\s*/', $citations );
		$html_parts = array();

		foreach ( $segments as $segment ) {
			$segment = trim( (string) $segment );
			if ( '' === $segment ) {
				continue;
			}

			if ( preg_match( '/BF&M/i', $segment ) ) {
				$html_parts[] = self::linkify_bfm_citation_segment( $segment );
				continue;
			}

			$known = self::tradition_statement_url( $segment );
			if ( '' !== $known ) {
				$html_parts[] = '<a class="thw-study-finder__citation-link" href="' . esc_url( $known ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $segment ) . '</a>';
				continue;
			}

			if ( preg_match( '/^CIC\b/i', $segment ) ) {
				$url          = 'https://www.vatican.va/archive/cod-iuris-canonici/cic_index_en.html';
				$html_parts[] = '<a class="thw-study-finder__citation-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $segment ) . '</a>';
				continue;
			}

			if ( preg_match( '/^CCC\b/i', $segment ) ) {
				$url          = 'https://www.vatican.va/archive/ENG0015/_INDEX.HTM';
				$html_parts[] = '<a class="thw-study-finder__citation-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $segment ) . '</a>';
				continue;
			}

			$html_parts[] = esc_html( $segment );
		}

		return implode( '; ', $html_parts );
	}

	/**
	 * Official statement URL for known tradition confession labels in citation text.
	 *
	 * @param string $segment Citation segment.
	 * @return string URL or empty.
	 */
	private static function tradition_statement_url( $segment ) {
		$segment = (string) $segment;
		if ( preg_match( '/AG\s*16\s*Fundamental Truths|Assemblies of God/i', $segment ) ) {
			return 'https://ag.org/Beliefs/Statement-of-Fundamental-Truths';
		}
		if ( preg_match( '/UPCI/i', $segment ) ) {
			return 'https://www.upci.org/about/our-beliefs';
		}
		if ( preg_match( '/COG\s*Declaration|Church of God Declaration/i', $segment ) ) {
			return 'https://churchofgod.org/beliefs/declaration-of-faith/';
		}
		if ( preg_match( '/IPHC/i', $segment ) ) {
			return 'https://iphc.org/wp-content/uploads/2023/04/IPHC-Manual-April-1.pdf';
		}
		if ( preg_match( '/Westminster Confession|\bWCF\b/i', $segment ) ) {
			return 'https://www.pcaac.org/bco/westminster-confession/';
		}
		if ( preg_match( '/Augsburg Confession/i', $segment ) ) {
			return 'https://bookofconcord.org/augsburg-confession/';
		}
		if ( preg_match( '/LCMS Brief Statement/i', $segment ) ) {
			return 'https://www.lcms.org/about/beliefs/doctrine/brief-statement-of-lcms-doctrinal-position';
		}
		if ( preg_match( '/Thirty-Nine Articles/i', $segment ) ) {
			return 'https://www.churchofengland.org/prayer-and-worship/worship-texts-and-resources/book-common-prayer/articles-religion';
		}
		if ( preg_match( '/Oriental Orthodox|First Three Ecumenical Councils|Miaphysite/i', $segment ) ) {
			return 'https://copticorthodox.church/en/faith/';
		}
		if ( preg_match( '/Nicene-Constantinopolitan|Seven Ecumenical Councils|Eastern Orthodox|Holy Mysteries/i', $segment ) ) {
			return 'https://www.goarch.org/ourfaith';
		}
		if ( preg_match( '/Apostles[’\'] Creed|Nicene Creed|Five Solas/i', $segment ) ) {
			return 'https://www.oikoumene.org/resources/documents/nicene-creed';
		}
		if ( preg_match( '/NAE Statement of Faith/i', $segment ) ) {
			return 'https://www.nae.org/statement-of-faith/';
		}
		if ( preg_match( '/EFCA Statement of Faith/i', $segment ) ) {
			return 'https://www.efca.org/sof';
		}
		if ( preg_match( '/Calvary Chapel Statement of Faith/i', $segment ) ) {
			return 'https://calvarychapel.com/about/statement-of-faith/';
		}
		if ( preg_match( '/Vineyard USA/i', $segment ) ) {
			return 'https://vineyardusa.org/about/statement-of-faith/';
		}
		if ( preg_match( '/Disciples Preamble/i', $segment ) ) {
			return 'https://disciples.org/our-identity/the-design/';
		}
		if ( preg_match( '/UCC Statement of Faith/i', $segment ) ) {
			return 'https://www.ucc.org/beliefs_statement-of-faith/';
		}
		if ( preg_match( '/PWF Statement of Faith/i', $segment ) ) {
			return 'https://www.pwfellowship.org/about-us';
		}
		if ( preg_match( '/UMC Articles of Religion/i', $segment ) ) {
			return 'https://www.umc.org/en/content/articles-of-religion';
		}
		if ( preg_match( '/AME Articles of Religion/i', $segment ) ) {
			return 'https://www.ame-church.com/our-church/our-beliefs/';
		}
		if ( preg_match( '/Nazarene Articles of Faith/i', $segment ) ) {
			return 'https://nazarene.org/what-we-believe/';
		}
		if ( preg_match( '/SDA\s*28\s*Fundamental Beliefs|28\s*Fundamental Beliefs/i', $segment ) ) {
			return 'https://www.nadadventist.org/beliefs/';
		}
		if ( preg_match( '/New Hampshire Baptist Confession/i', $segment ) ) {
			return 'https://founders.org/library/new-hampshire-confession/';
		}
		if ( preg_match( '/1689\s+London Baptist Confession|LBC\s*1689/i', $segment ) ) {
			return 'https://founders.org/library/1689-confession/';
		}
		if ( preg_match( '/Mennonite Confession of Faith/i', $segment ) ) {
			return 'https://www.mennoniteusa.org/who-are-mennonites/what-we-believe/confession-of-faith/';
		}
		if ( preg_match( '/UMJC Statement of Faith/i', $segment ) ) {
			return 'https://www.umjc.org/statement-of-faith';
		}
		if ( preg_match( '/LDS Articles of Faith/i', $segment ) ) {
			return 'https://www.churchofjesuschrist.org/comeuntochrist/article/articles-of-faith';
		}
		if ( preg_match( '/JW Beliefs/i', $segment ) ) {
			return 'https://www.jw.org/en/jehovahs-witnesses/faq/jehovah-witness-beliefs/';
		}
		if ( preg_match( '/Richmond Declaration/i', $segment ) ) {
			return 'https://www.friendsunitedmeeting.org/';
		}
		if ( preg_match( '/Churches of Christ Teaching/i', $segment ) ) {
			return 'https://christiancourier.com/topics/church';
		}
		if ( preg_match( '/Salvation Army Doctrines/i', $segment ) ) {
			return 'https://www.salvationarmyusa.org/usn/what-we-believe/';
		}
		if ( preg_match( '/Christian Science Tenets/i', $segment ) ) {
			return 'https://www.christianscience.com/what-is-christian-science/tenets-of-christian-science';
		}
		return '';
	}

	/**
	 * Link BF&M article identifiers to official anchors on bfm.sbc.net.
	 *
	 * @param string $segment Citation segment containing BF&M articles.
	 * @return string
	 */
	private static function linkify_bfm_citation_segment( $segment ) {
		$base = 'https://bfm.sbc.net/bfm2000/';

		// Prefer linking each "Art. XVIII" (optionally preceded by "BF&M 2000 ").
		$linked = preg_replace_callback(
			'/(?:BF&M\s*2000\s+)?Art\.\s*([IVXLCDM]+)\b/iu',
			static function ( $m ) use ( $base ) {
				$roman = strtolower( (string) $m[1] );
				$url   = $base . '#' . rawurlencode( $roman );
				return '<a class="thw-study-finder__citation-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $m[0] ) . '</a>';
			},
			$segment
		);

		if ( null === $linked || $linked === $segment ) {
			// No Art. tokens — still link the BF&M phrase to the full document.
			return '<a class="thw-study-finder__citation-link" href="' . esc_url( $base ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $segment ) . '</a>';
		}

		// Escape any leftover plain text around the inserted anchors.
		$parts = preg_split( '/(<a\b[^>]*>.*?<\/a>)/su', $linked, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out   = '';
		foreach ( (array) $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			if ( 0 === strpos( $part, '<a ' ) ) {
				$out .= $part;
			} else {
				$out .= esc_html( $part );
			}
		}

		return $out;
	}

	/**
	 * Parse AI Scripture guidance JSON.
	 *
	 * @param string $raw Raw AI response.
	 * @return array<int, array<string, string>>
	 */
	public static function parse_ai_scripture_guidance( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return array();
		}

		// Strip markdown fences.
		$raw = preg_replace( '/^```(?:json)?\s*/i', '', $raw );
		$raw = preg_replace( '/\s*```$/', '', (string) $raw );
		$raw = trim( (string) $raw );

		// Normalize common smart quotes that break JSON.
		$raw = str_replace(
			array( "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x98", "\xE2\x80\x99" ),
			array( '"', '"', "'", "'" ),
			$raw
		);

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) && preg_match( '/\[[\s\S]*\]/', $raw, $matches ) ) {
			$data = json_decode( $matches[0], true );
		}
		if ( ! is_array( $data ) && preg_match( '/\{[\s\S]*\}/', $raw, $matches ) ) {
			$data = json_decode( $matches[0], true );
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		// Support { "results": [ ... ] } wrappers.
		if ( isset( $data['results'] ) && is_array( $data['results'] ) ) {
			$data = $data['results'];
		} elseif ( isset( $data['passages'] ) && is_array( $data['passages'] ) ) {
			$data = $data['passages'];
		} elseif ( self::is_assoc_scripture_row( $data ) ) {
			$data = array( $data );
		}

		$rows = array();
		foreach ( $data as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$reference = '';
			if ( ! empty( $item['reference'] ) ) {
				$reference = (string) $item['reference'];
			} elseif ( ! empty( $item['passage'] ) ) {
				$reference = (string) $item['passage'];
			} elseif ( ! empty( $item['verse'] ) ) {
				$reference = (string) $item['verse'];
			}

			if ( '' === trim( $reference ) ) {
				continue;
			}

			$rows[] = array(
				'reference'  => $reference,
				'summary'    => isset( $item['summary'] ) ? (string) $item['summary'] : '',
				'commentary' => isset( $item['commentary'] ) ? (string) $item['commentary'] : ( isset( $item['explanation'] ) ? (string) $item['explanation'] : '' ),
				'citations'  => isset( $item['citations'] ) ? (string) $item['citations'] : '',
			);
		}

		return $rows;
	}

	/**
	 * Whether an array looks like a single scripture guidance row.
	 *
	 * @param array<string, mixed> $data Decoded JSON object.
	 * @return bool
	 */
	private static function is_assoc_scripture_row( $data ) {
		if ( array_values( $data ) === $data ) {
			return false;
		}
		return ! empty( $data['reference'] ) || ! empty( $data['passage'] ) || ! empty( $data['verse'] );
	}

	/**
	 * Best-effort map a reference string to an existing lesson permalink.
	 *
	 * @param string $reference Reference like "Matthew 19:3-9".
	 * @return string
	 */
	public static function find_lesson_url_for_reference( $reference ) {
		$needle = strtolower( preg_replace( '/\s+/', ' ', trim( (string) $reference ) ) );
		if ( '' === $needle || ! class_exists( 'HWBL_Curriculum' ) || ! class_exists( 'HWBL_Books' ) ) {
			return '';
		}

		foreach ( HWBL_Curriculum::load_niv() as $entry ) {
			$lesson_number = HWBL_Curriculum::get_entry_lesson_number( $entry );
			if ( $lesson_number < 1 ) {
				continue;
			}

			$book_id     = isset( $entry['book_id'] ) ? (int) $entry['book_id'] : 0;
			$chapter     = isset( $entry['chapter'] ) ? (int) $entry['chapter'] : 0;
			$verse_start = isset( $entry['verse_start'] ) ? (int) $entry['verse_start'] : 0;
			$verse_end   = isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : $verse_start;
			if ( $book_id < 1 || $chapter < 1 || $verse_start < 1 ) {
				continue;
			}

			$formatted = strtolower(
				preg_replace(
					'/\s+/',
					' ',
					HWBL_Books::format_reference( $book_id, $chapter, $verse_start, $verse_end )
				)
			);

			$exact_start = strtolower(
				preg_replace(
					'/\s+/',
					' ',
					HWBL_Books::format_reference( $book_id, $chapter, $verse_start, $verse_start )
				)
			);

			if ( $needle !== $formatted && $needle !== $exact_start && false === strpos( $needle, $exact_start ) ) {
				continue;
			}

			if ( class_exists( 'HWBL_Scheduler' ) ) {
				$post_id = HWBL_Scheduler::get_lesson_id_by_number( $lesson_number );
				if ( $post_id ) {
					$url = get_permalink( $post_id );
					return $url ? (string) $url : '';
				}
			}
		}

		return '';
	}

	/**
	 * Suggest related curriculum lessons for a topic or question.
	 *
	 * @param string $keywords Topic or question text.
	 * @param int    $limit    Max lessons.
	 * @return array<int, array<string, mixed>>
	 */
	public static function suggest_related_lessons( $keywords, $limit = 5 ) {
		$candidates = self::search_curriculum( $keywords, max( 5, (int) $limit ) );
		$lessons    = array();

		foreach ( $candidates as $row ) {
			if ( count( $lessons ) >= max( 1, (int) $limit ) ) {
				break;
			}
			$entry         = isset( $row['entry'] ) && is_array( $row['entry'] ) ? $row['entry'] : array();
			$lesson_number = isset( $row['lesson_number'] ) ? (int) $row['lesson_number'] : 0;
			$formatted     = self::format_result_from_entry( $entry, $lesson_number );
			if ( ! $formatted ) {
				continue;
			}
			$lessons[] = array(
				'lesson_id'     => isset( $formatted['lesson_id'] ) ? (int) $formatted['lesson_id'] : 0,
				'lesson_number' => $formatted['lesson_number'],
				'reference'     => $formatted['reference'],
				'excerpt'       => $formatted['excerpt'],
				'url'           => $formatted['url'],
				'title'         => isset( $entry['title'] ) ? sanitize_text_field( (string) $entry['title'] ) : $formatted['reference'],
				'book_id'       => isset( $formatted['book_id'] ) ? (int) $formatted['book_id'] : 0,
				'chapter'       => isset( $formatted['chapter'] ) ? (int) $formatted['chapter'] : 0,
				'verse'         => isset( $formatted['verse'] ) ? (int) $formatted['verse'] : 0,
			);
		}

		return $lessons;
	}

	/**
	 * Search bundled curriculum by keywords (kept for tests / internal tools).
	 *
	 * @param string $keywords Keyword string.
	 * @param int    $limit    Max candidates.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_curriculum( $keywords, $limit = 15 ) {
		return THW_Premium_Study_Search::search_curriculum( $keywords, $limit );
	}

	/**
	 * Parse keyword string into terms.
	 *
	 * @param string $keywords Keywords.
	 * @return array<int, string>
	 */
	public static function parse_keywords( $keywords ) {
		return THW_Premium_Study_Search::parse_keywords( $keywords );
	}

	/**
	 * Score a curriculum entry against search terms.
	 *
	 * @param array<string, mixed> $entry Curriculum row.
	 * @param array<int, string>   $terms Search terms.
	 * @return int
	 */
	public static function score_entry( $entry, $terms ) {
		return THW_Premium_Study_Search::score_entry( $entry, $terms );
	}

	/**
	 * Build a result row from a curriculum entry.
	 *
	 * @param array<string, mixed> $entry         Curriculum row.
	 * @param int                  $lesson_number Lesson number.
	 * @param string               $rationale     Optional rationale.
	 * @return array<string, mixed>|null
	 */
	public static function format_result_from_entry( $entry, $lesson_number, $rationale = '' ) {
		$book_id     = isset( $entry['book_id'] ) ? (int) $entry['book_id'] : 0;
		$chapter     = isset( $entry['chapter'] ) ? (int) $entry['chapter'] : 0;
		$verse_start = isset( $entry['verse_start'] ) ? (int) $entry['verse_start'] : 0;
		$verse_end   = isset( $entry['verse_end'] ) ? (int) $entry['verse_end'] : $verse_start;

		if ( $book_id < 1 || $chapter < 1 || $verse_start < 1 ) {
			return null;
		}

		$reference = HWBL_Books::format_reference( $book_id, $chapter, $verse_start, $verse_end );
		$excerpt   = isset( $entry['text'] ) ? wp_trim_words( wp_strip_all_tags( (string) $entry['text'] ), 24, '…' ) : '';
		$url       = '';

		$post_id = 0;
		if ( class_exists( 'HWBL_Scheduler' ) ) {
			$post_id = (int) HWBL_Scheduler::get_lesson_id_by_number( $lesson_number );
			if ( $post_id ) {
				$url = get_permalink( $post_id );
			}
		}

		return array(
			'lesson_id'     => $post_id,
			'lesson_number' => $lesson_number,
			'reference'     => $reference,
			'excerpt'       => $excerpt,
			'url'           => $url ? (string) $url : '',
			'rationale'     => $rationale,
			'book_id'       => $book_id,
			'chapter'       => $chapter,
			'verse'         => $verse_start,
			'verse_end'     => $verse_end,
		);
	}

	/**
	 * Parse AI JSON ranking response (legacy helper for tests).
	 *
	 * @param string $raw Raw AI response.
	 * @return array<int, array<string, mixed>>
	 */
	public static function parse_ai_ranking( $raw ) {
		$raw = trim( (string) $raw );
		if ( preg_match( '/\[[\s\S]*\]/', $raw, $matches ) ) {
			$raw = $matches[0];
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		$rows = array();
		foreach ( $data as $item ) {
			if ( ! is_array( $item ) || empty( $item['lesson_number'] ) ) {
				continue;
			}
			$rows[] = array(
				'lesson_number' => (int) $item['lesson_number'],
				'rationale'     => isset( $item['rationale'] ) ? (string) $item['rationale'] : '',
			);
		}

		return $rows;
	}
}
