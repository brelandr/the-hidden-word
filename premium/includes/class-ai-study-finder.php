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
			$source   = isset( $cached['source'] ) ? (string) $cached['source'] : 'ai';
		} else {
			// Prefer embeddings-grounded curriculum retrieval when available.
			$guidance = self::retrieve_grounded_guidance( $keywords, $limit, $resolved );
			$source   = 'embeddings';
			if ( is_wp_error( $guidance ) || empty( $guidance['results'] ) ) {
				$guidance = self::generate_topic_guidance( $keywords, $limit, $resolved );
				$source   = 'ai';
			}
			if ( is_wp_error( $guidance ) ) {
				return $guidance;
			}
			if ( ! empty( $guidance['results'] ) ) {
				$guidance['source'] = $source;
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
				'source'            => $source,
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
	 * Build Study Finder results from embeddings-ranked curriculum (grounded).
	 *
	 * Falls back with empty results / WP_Error when embeddings are unavailable
	 * so callers can use the AI JSON path.
	 *
	 * @param string               $keywords Topic keywords.
	 * @param int                  $limit    Max passages.
	 * @param array<string, mixed> $resolved Resolve payload.
	 * @return array{results:array<int, array<string, mixed>>, flagged:bool}|WP_Error
	 */
	public static function retrieve_grounded_guidance( $keywords, $limit = 5, $resolved = array() ) {
		if ( ! class_exists( 'THW_Premium_AI_Client' ) || ! THW_Premium_AI_Client::supports_embeddings() ) {
			return new WP_Error( 'hwbl_no_embeddings', 'embeddings_unavailable' );
		}

		$candidates = THW_Premium_Study_Search::search_curriculum_grounded( $keywords, max( 5, (int) $limit ) );
		// Only keep rows that were scored via embeddings (not keyword-only fallback).
		$candidates = array_values(
			array_filter(
				(array) $candidates,
				static function ( $row ) {
					return is_array( $row ) && ( isset( $row['similarity'] ) || ( isset( $row['source'] ) && 'embeddings' === $row['source'] ) );
				}
			)
		);
		if ( empty( $candidates ) ) {
			return new WP_Error( 'hwbl_embed_empty', 'no_matches' );
		}

		$fallback_citations = '';
		if ( is_array( $resolved ) && ! empty( $resolved['digest'] ) ) {
			$fallback_citations = self::extract_cited_references_summary( (string) $resolved['digest'] );
		}

		$results = array();
		foreach ( array_slice( $candidates, 0, max( 1, (int) $limit ) ) as $row ) {
			$entry         = isset( $row['entry'] ) && is_array( $row['entry'] ) ? $row['entry'] : array();
			$lesson_number = isset( $row['lesson_number'] ) ? (int) $row['lesson_number'] : 0;
			$formatted     = self::format_result_from_entry( $entry, $lesson_number );
			if ( ! $formatted ) {
				continue;
			}

			$summary = isset( $formatted['excerpt'] ) ? (string) $formatted['excerpt'] : '';
			$context = '';
			if ( ! empty( $entry['historical_context'] ) ) {
				$context = wp_strip_all_tags( (string) $entry['historical_context'] );
			} elseif ( ! empty( $entry['preceding_narrative'] ) ) {
				$context = wp_strip_all_tags( (string) $entry['preceding_narrative'] );
			}
			$commentary = $context ? wp_kses_post( wpautop( wp_trim_words( $context, 80, '…' ) ) ) : '';
			$citations  = self::sanitize_citations_for_tradition(
				'',
				$fallback_citations,
				is_array( $resolved ) ? $resolved : array()
			);

			$results[] = array(
				'reference'     => (string) $formatted['reference'],
				'summary'       => sanitize_text_field( $summary ),
				'commentary'    => $commentary,
				'citations'     => $citations,
				'citationsHtml' => self::format_citations_html( $citations ),
				'url'           => (string) ( $formatted['url'] ?? '' ),
			);
		}

		if ( empty( $results ) ) {
			return new WP_Error( 'hwbl_embed_empty', 'no_results' );
		}

		return array(
			'results' => $results,
			'flagged' => false,
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
		return THW_Premium_AI_Study_Finder_Citations::sanitize_citations_for_tradition( $citations, $fallback_citations, $resolved );
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
		return THW_Premium_AI_Study_Finder_Citations::citations_match_tradition( $citations, $digest, $preset );
	}

	/**
	 * Remove invented foreign confession mentions from commentary when they conflict with the active tradition.
	 *
	 * @param string               $text     Commentary text.
	 * @param array<string, mixed> $resolved Resolve payload.
	 * @return string
	 */
	public static function strip_foreign_tradition_citations_from_text( $text, $resolved ) {
		return THW_Premium_AI_Study_Finder_Citations::strip_foreign_tradition_citations_from_text( $text, $resolved );
	}

	/**
	 * Pull a compact citations summary from a doctrine digest's Cited references block.
	 *
	 * @param string $digest Digest text from tradition pack builder.
	 * @return string
	 */
	public static function extract_cited_references_summary( $digest ) {
		return THW_Premium_AI_Study_Finder_Citations::extract_cited_references_summary( $digest );
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
		return THW_Premium_AI_Study_Finder_Citations::format_citations_html( $citations );
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
		return THW_Premium_AI_Study_Finder_Search::find_lesson_url_for_reference( $reference );
	}

	/**
	 * Suggest related curriculum lessons for a topic or question.
	 *
	 * @param string $keywords Topic or question text.
	 * @param int    $limit    Max lessons.
	 * @return array<int, array<string, mixed>>
	 */
	public static function suggest_related_lessons( $keywords, $limit = 5 ) {
		return THW_Premium_AI_Study_Finder_Search::suggest_related_lessons( $keywords, $limit );
	}

	/**
	 * Search bundled curriculum by keywords (kept for tests / internal tools).
	 *
	 * @param string $keywords Keyword string.
	 * @param int    $limit    Max candidates.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_curriculum( $keywords, $limit = 15 ) {
		return THW_Premium_AI_Study_Finder_Search::search_curriculum( $keywords, $limit );
	}

	/**
	 * Embeddings-aware curriculum search (falls back to keywords).
	 *
	 * @param string $keywords Keyword string.
	 * @param int    $limit    Max candidates.
	 * @return array<int, array<string, mixed>>
	 */
	public static function search_curriculum_grounded( $keywords, $limit = 15 ) {
		return THW_Premium_AI_Study_Finder_Search::search_curriculum_grounded( $keywords, $limit );
	}

	/**
	 * Parse keyword string into terms.
	 *
	 * @param string $keywords Keywords.
	 * @return array<int, string>
	 */
	public static function parse_keywords( $keywords ) {
		return THW_Premium_AI_Study_Finder_Search::parse_keywords( $keywords );
	}

	/**
	 * Score a curriculum entry against search terms.
	 *
	 * @param array<string, mixed> $entry Curriculum row.
	 * @param array<int, string>   $terms Search terms.
	 * @return int
	 */
	public static function score_entry( $entry, $terms ) {
		return THW_Premium_AI_Study_Finder_Search::score_entry( $entry, $terms );
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
		return THW_Premium_AI_Study_Finder_Search::format_result_from_entry( $entry, $lesson_number, $rationale );
	}

	/**
	 * Parse AI JSON ranking response (legacy helper for tests).
	 *
	 * @param string $raw Raw AI response.
	 * @return array<int, array<string, mixed>>
	 */
	public static function parse_ai_ranking( $raw ) {
		return THW_Premium_AI_Study_Finder_Search::parse_ai_ranking( $raw );
	}

}
