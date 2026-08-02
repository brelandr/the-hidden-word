<?php
/**
 * AI research and explanation for the Bible reader.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Bible_Reader_Explain
 */
class THW_Premium_Bible_Reader_Explain {

	const RATE_LIMIT  = 10;
	const RATE_WINDOW = 3600;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'hwbl_bible_reader_features', array( __CLASS__, 'filter_reader_features' ) );
	}

	/**
	 * Whether AI explain is available in the Bible reader.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return thw_premium_ai_frontend_available();
	}

	/**
	 * Add explain flag to reader features.
	 *
	 * @param array<string, bool> $features Feature flags.
	 * @return array<string, bool>
	 */
	public static function filter_reader_features( $features ) {
		$features['explain'] = self::is_available();
		return $features;
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$route = array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'rest_explain' ),
			/*
			 * Public on purpose: anonymous visitors may fetch an already-saved
			 * explanation. Creating a new AI explanation is gated inside
			 * rest_explain() with is_user_logged_in() + AI availability.
			 */
			'permission_callback' => '__return_true',
			'args'                => array(
				'book_id'     => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'chapter'     => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'verse'       => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => 0,
				),
				'translation' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'default'           => '',
				),
				'scope'       => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'default'           => 'verse',
				),
				'tradition'   => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'default'           => '',
				),
			),
		);

		register_rest_route( 'hwbl/v1', '/bible-explain', $route );
		register_rest_route( 'thw/v1', '/bible-explain', $route );
	}

	/**
	 * REST: explain the current verse or chapter (reuses a saved post when available).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_explain( $request ) {
		if ( ! class_exists( 'HWBL_Bible_Reader' ) || ! HWBL_Bible_Reader::is_enabled() ) {
			return new WP_Error(
				'hwbl_reader_disabled',
				__( 'The Bible reader is not available.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		$book_id     = max( 1, (int) $request['book_id'] );
		$chapter     = max( 1, (int) $request['chapter'] );
		$verse       = max( 0, (int) $request['verse'] );
		$scope       = self::normalize_scope( (string) $request['scope'], $verse );
		$translation = HWBL_Bible_Reader::resolve_translation_for_request( (string) $request['translation'] );
		if ( ! $translation ) {
			return new WP_Error(
				'invalid_translation',
				__( 'That translation is not available.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		if ( 'verse' === $scope && $verse < 1 ) {
			return new WP_Error(
				'verse_required',
				__( 'Select a verse or choose “Explain chapter”.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$payload = self::build_payload( $book_id, $chapter, $verse, $translation, $scope );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$context   = self::build_topic_context( $payload, $scope );
		$tradition = sanitize_key( (string) $request['tradition'] );
		$resolved  = thw_premium_resolve_explain_rules_for_request( $tradition, $context );
		$preset    = $resolved['preset'];

		if ( is_user_logged_in() && thw_premium_user_tradition_enabled() && 'site' !== $preset ) {
			thw_premium_set_user_tradition_preset( get_current_user_id(), $preset );
		}

		$store_payload              = $payload;
		$store_payload['tradition'] = $preset;
		$store_payload['scope']     = $scope;

		// Prefer stored answers — no AI call when already resolved.
		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			$resolved_display = THW_Premium_Bible_Reader_Explain_Store::resolve_for_display( $store_payload );
			$needs_check      = ! empty( $resolved_display['needs_override_check'] );
			if ( ! empty( $resolved_display['content_row'] ) && ! $needs_check ) {
				return new WP_REST_Response(
					self::format_explain_rest_payload(
						$resolved_display,
						$payload,
						$scope,
						$preset,
						true
					)
				);
			}
			// Guests can read the shared base while tradition overrides are not preloaded yet.
			if ( $needs_check && ! empty( $resolved_display['base'] ) && ! is_user_logged_in() ) {
				return new WP_REST_Response(
					self::format_explain_rest_payload(
						$resolved_display,
						$payload,
						$scope,
						$preset,
						true
					)
				);
			}
		}

		// Generating a new explanation / tradition check requires login + AI availability.
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'thw_ai_login',
				__( 'Log in to generate the first AI explanation for this passage and Bible version. Once saved, everyone can read it.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		if ( ! self::is_available() ) {
			return new WP_Error(
				'thw_ai_disabled',
				__( 'AI explanation is not enabled on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		if ( ! self::check_rate_limit( get_current_user_id() ) ) {
			return new WP_Error(
				'thw_ai_rate_limit',
				__( 'Hourly AI explanation limit reached.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}

		$gen = self::generate_for_passage(
			$book_id,
			$chapter,
			$verse,
			$translation,
			$scope,
			$preset,
			array(
				'bypass_rate_limit' => true,
				'user_id'           => get_current_user_id(),
			)
		);
		if ( empty( $gen['ok'] ) ) {
			$code = (string) ( $gen['error'] ?? 'generate_failed' );
			if ( 'compliance_failed' === $code ) {
				return new WP_Error(
					'thw_ai_compliance_failed',
					__( 'We could not generate an explanation that follows the selected tradition’s guidelines. Please try again.', 'hidden-word-bible-lessons' ),
					array( 'status' => 502 )
				);
			}
			return new WP_Error(
				'thw_ai_generate_failed',
				$code ? $code : __( 'Could not generate an explanation.', 'hidden-word-bible-lessons' ),
				array( 'status' => 502 )
			);
		}

		self::increment_rate_limit( get_current_user_id() );

		$resolved_display = class_exists( 'THW_Premium_Bible_Reader_Explain_Store' )
			? THW_Premium_Bible_Reader_Explain_Store::resolve_for_display( $store_payload )
			: array();

		return new WP_REST_Response(
			self::format_explain_rest_payload(
				$resolved_display,
				$payload,
				$scope,
				$preset,
				! empty( $gen['cached'] ) || ! empty( $gen['skipped'] )
			)
		);
	}

	/**
	 * Build REST payload for base (+ optional tradition override) explains.
	 *
	 * @param array<string, mixed> $resolved resolve_for_display() result.
	 * @param array<string, mixed> $payload  Passage payload.
	 * @param string               $scope    Scope.
	 * @param string               $preset   Requested tradition.
	 * @param bool                 $cached   Whether served from store.
	 * @return array<string, mixed>
	 */
	private static function format_explain_rest_payload( array $resolved, array $payload, $scope, $preset, $cached ) {
		$row  = is_array( $resolved['content_row'] ?? null ) ? $resolved['content_row'] : null;
		$html = ( $row && class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) )
			? THW_Premium_Bible_Reader_Explain_Store::get_explanation_html( $row )
			: '';
		$url  = ( $row && class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) )
			? THW_Premium_Bible_Reader_Explain_Store::get_public_url( $row )
			: '';
		$flagged = ( $row && class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) )
			? THW_Premium_Bible_Reader_Explain_Store::is_flagged( $row )
			: false;

		$has_diff        = ! empty( $resolved['has_tradition_diff'] );
		$tradition_label = '';
		if ( $has_diff ) {
			$choices = function_exists( 'thw_premium_get_tradition_preset_choices' )
				? thw_premium_get_tradition_preset_choices( false )
				: array();
			$tradition_label = isset( $choices[ $preset ] ) ? (string) $choices[ $preset ] : $preset;
		}

		$source = 'base';
		if ( $has_diff ) {
			$source = 'override';
		} elseif ( ! empty( $resolved['legacy_full'] ) ) {
			$source = 'legacy';
		} elseif ( ! empty( $resolved['same_as_base'] ) ) {
			$source = 'same_as_base';
		}

		return array(
			'content'           => $html,
			'scope'             => $scope,
			'reference'         => $payload['reference'] ?? '',
			'cached'            => (bool) $cached,
			'tradition'         => $preset,
			'traditionSource'   => $source,
			'hasTraditionDiff'  => (bool) $has_diff,
			'traditionLabel'    => $tradition_label,
			'complianceFlagged' => $flagged,
			'lessonUrl'         => $payload['lesson_url'] ?? '',
			'postUrl'           => $url ? $url : '',
			'postId'            => ( $row && class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) )
				? THW_Premium_Bible_Reader_Explain_Store::item_id( $row )
				: 0,
		);
	}

	/**
	 * Normalize explain scope.
	 *
	 * @param string $scope Scope slug.
	 * @param int    $verse Verse number.
	 * @return string
	 */
	public static function normalize_scope( $scope, $verse ) {
		$scope = sanitize_key( $scope );
		if ( 'chapter' === $scope ) {
			return 'chapter';
		}
		if ( $verse < 1 ) {
			return 'chapter';
		}
		return 'verse';
	}

	/**
	 * Build explain payload from reader chapter data.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse number.
	 * @param string $translation Translation slug.
	 * @param string $scope       Scope slug.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function build_payload( $book_id, $chapter, $verse, $translation, $scope ) {
		$chapter_payload = HWBL_Bible_Reader::get_chapter( $book_id, $chapter, $translation );
		if ( ! is_array( $chapter_payload ) || empty( $chapter_payload['verses'] ) ) {
			return new WP_Error(
				'chapter_unavailable',
				__( 'Could not load this passage for explanation.', 'hidden-word-bible-lessons' ),
				array( 'status' => 404 )
			);
		}

		$reference = HWBL_Books::format_reference( $book_id, $chapter, $verse > 0 ? $verse : 1, $verse > 0 ? $verse : 0 );
		if ( 'chapter' === $scope ) {
			$reference = HWBL_Books::get_name( $book_id ) . ' ' . $chapter;
		}

		$payload = array(
			'book_id'     => $book_id,
			'chapter'     => $chapter,
			'verse'       => $verse,
			'translation' => $translation,
			'reference'   => $reference,
			'scope'       => $scope,
			'verses'      => $chapter_payload['verses'],
			'headings'    => isset( $chapter_payload['headings'] ) ? $chapter_payload['headings'] : array(),
		);

		if ( 'verse' === $scope && $verse > 0 ) {
			$payload['text'] = self::extract_verse_text( $chapter_payload['verses'], $verse );
			if ( '' === $payload['text'] ) {
				return new WP_Error(
					'verse_unavailable',
					__( 'That verse could not be loaded.', 'hidden-word-bible-lessons' ),
					array( 'status' => 404 )
				);
			}
			$payload['follow_on'] = self::extract_follow_on_text( $chapter_payload['verses'], $verse, 3 );
		} else {
			$payload['text'] = self::format_chapter_text( $chapter_payload['verses'] );
		}

		self::attach_curriculum_context( $payload, $book_id, $chapter, $verse, $scope );

		return $payload;
	}

	/**
	 * Pull verse text from chapter rows.
	 *
	 * @param array<int, array<string, mixed>> $verses Verse rows.
	 * @param int                              $verse  Verse number.
	 * @return string
	 */
	private static function extract_verse_text( $verses, $verse ) {
		foreach ( $verses as $row ) {
			if ( (int) ( $row['number'] ?? 0 ) === (int) $verse ) {
				return trim( (string) ( $row['text'] ?? '' ) );
			}
		}
		return '';
	}

	/**
	 * Build follow-on verse text for a single-verse explain.
	 *
	 * @param array<int, array<string, mixed>> $verses Verse rows.
	 * @param int                              $verse  Starting verse.
	 * @param int                              $count  How many following verses.
	 * @return string
	 */
	private static function extract_follow_on_text( $verses, $verse, $count ) {
		$lines = array();
		foreach ( $verses as $row ) {
			$num = (int) ( $row['number'] ?? 0 );
			if ( $num <= $verse || $num > $verse + $count ) {
				continue;
			}
			$text = trim( (string) ( $row['text'] ?? '' ) );
			if ( '' !== $text ) {
				$lines[] = $num . ' ' . $text;
			}
		}
		return implode( "\n", $lines );
	}

	/**
	 * Format chapter verses for the prompt.
	 *
	 * @param array<int, array<string, mixed>> $verses Verse rows.
	 * @return string
	 */
	private static function format_chapter_text( $verses ) {
		$lines = array();
		foreach ( $verses as $row ) {
			$num  = (int) ( $row['number'] ?? 0 );
			$text = trim( (string) ( $row['text'] ?? '' ) );
			if ( $num > 0 && '' !== $text ) {
				$lines[] = $num . ' ' . $text;
			}
		}
		$text = implode( "\n", $lines );
		if ( strlen( $text ) > 12000 ) {
			$text = substr( $text, 0, 12000 ) . "\n…";
		}
		return $text;
	}

	/**
	 * Attach curriculum lesson context when available.
	 *
	 * @param array<string, mixed> $payload Payload (by reference).
	 * @param int                  $book_id Book ID.
	 * @param int                  $chapter Chapter.
	 * @param int                  $verse   Verse.
	 * @param string               $scope   Scope.
	 * @return void
	 */
	private static function attach_curriculum_context( &$payload, $book_id, $chapter, $verse, $scope ) {
		if ( ! class_exists( 'HWBL_Verse_Memorize' ) || ! class_exists( 'HWBL_CPT_Lesson' ) ) {
			return;
		}

		$lookup_verse = 'verse' === $scope && $verse > 0 ? $verse : 0;
		if ( $lookup_verse < 1 ) {
			return;
		}

		$lesson_id = HWBL_Verse_Memorize::find_lesson_by_reference( $book_id, $chapter, $lookup_verse, $lookup_verse );
		if ( ! $lesson_id ) {
			return;
		}

		$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		if ( empty( $lesson['lesson_number'] ) ) {
			return;
		}

		$payload['lesson_url']      = get_permalink( $lesson_id );
		$payload['lesson_context']  = wp_strip_all_tags( (string) ( $lesson['historical_context'] ?? '' ) );
		$payload['lesson_narrative'] = wp_strip_all_tags( (string) ( $lesson['preceding_narrative'] ?? '' ) );
	}

	/**
	 * Build topic context for doctrine routing.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @param string               $scope   Scope.
	 * @return string
	 */
	private static function build_topic_context( $payload, $scope ) {
		$parts = array(
			isset( $payload['reference'] ) ? (string) $payload['reference'] : '',
			isset( $payload['text'] ) ? (string) $payload['text'] : '',
			isset( $payload['lesson_context'] ) ? (string) $payload['lesson_context'] : '',
			isset( $payload['lesson_narrative'] ) ? (string) $payload['lesson_narrative'] : '',
			$scope,
		);
		return implode( "\n", array_filter( array_map( 'trim', $parts ) ) );
	}

	/**
	 * Build the AI user prompt.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @param string               $scope   Scope.
	 * @return string
	 */
	public static function build_prompt( $payload, $scope ) {
		$reference   = isset( $payload['reference'] ) ? (string) $payload['reference'] : '';
		$text        = isset( $payload['text'] ) ? (string) $payload['text'] : '';
		$translation = isset( $payload['translation'] ) ? sanitize_key( (string) $payload['translation'] ) : '';
		$may_embed   = function_exists( 'thw_premium_ai_may_embed_scripture_text' )
			? thw_premium_ai_may_embed_scripture_text( $translation )
			: ( 'niv' !== $translation );

		if ( 'chapter' === $scope ) {
			$prompt  = "You are helping someone study a full Bible chapter during personal reading.\n";
			$prompt .= "Write a clear, pastoral overview in HTML using <h3> section headings and <p> paragraphs only.\n";
			if ( $may_embed ) {
				$prompt .= "Do not invent exact Bible quotations beyond the chapter text supplied below.\n\n";
			} else {
				$prompt .= "Scripture wording is not included (licensed translation). Do not invent or reproduce copyrighted quotations; refer by verse number/reference only. The reader sees the official text in the UI.\n\n";
			}
			$prompt .= "Cover these sections in order:\n";
			$prompt .= "1) <h3>Setting and flow</h3> — What is happening in this chapter and how does it fit the book?\n";
			$prompt .= "2) <h3>Key themes</h3> — Main ideas, repeated words, and turning points.\n";
			$prompt .= "3) <h3>Important verses</h3> — Highlight 2–4 pivotal verses"
				. ( $may_embed ? ' from the supplied text' : ' by reference' )
				. " and explain why they matter.\n";
			$prompt .= "4) <h3>Living it today</h3> — Practical application for faith and daily life.\n\n";
			$prompt .= 'Reference: ' . $reference . "\n";
			if ( '' !== $translation ) {
				$prompt .= 'Translation: ' . strtoupper( $translation ) . "\n";
			}
			$prompt .= "\n";
			if ( $may_embed ) {
				$prompt .= "Chapter text:\n" . $text . "\n";
			}
		} else {
			$prompt  = "You are explaining a Bible verse someone is reading in a chapter view.\n";
			$prompt .= "Write a clear, pastoral explanation in HTML using <h3> section headings and <p> paragraphs only.\n";
			if ( $may_embed ) {
				$prompt .= "Do not invent exact Bible quotations beyond the verse text supplied below.\n\n";
			} else {
				$prompt .= "Scripture wording is not included (licensed translation). Do not invent or reproduce copyrighted quotations; explain from the reference only. The reader sees the official text in the UI.\n\n";
			}
			$prompt .= "Cover these sections in order:\n";
			$prompt .= "1) <h3>Historical lead-up</h3> — What leads into this verse in the biblical narrative?\n";
			$prompt .= "2) <h3>Why it was written</h3> — Occasion, audience, or purpose behind these words.\n";
			$prompt .= "3) <h3>True meaning</h3> — Meaning in context: key words, speaker, and what the text is saying.\n";
			$prompt .= "4) <h3>Living it today</h3> — Practical ways this verse can shape daily life.\n";
			$prompt .= "5) <h3>What follows</h3> — "
				. ( $may_embed
					? 'If follow-on verses are provided, explain how the thought continues.'
					: 'Briefly note how the immediate following verses continue the thought without quoting copyrighted wording.' )
				. "\n\n";
			$prompt .= 'Reference: ' . $reference . "\n";
			if ( '' !== $translation ) {
				$prompt .= 'Translation: ' . strtoupper( $translation ) . "\n";
			}
			$prompt .= "\n";
			if ( $may_embed ) {
				$prompt .= "Verse text:\n" . $text . "\n";
				if ( ! empty( $payload['follow_on'] ) ) {
					$prompt .= "\nFollow-on verses (for section 5):\n" . (string) $payload['follow_on'] . "\n";
				}
			}
		}

		if ( ! empty( $payload['lesson_context'] ) ) {
			$prompt .= "\nCurriculum historical context (use as background; do not quote verbatim unless helpful):\n" . (string) $payload['lesson_context'] . "\n";
		}
		if ( ! empty( $payload['lesson_narrative'] ) ) {
			$prompt .= "\nCurriculum narrative lead-up:\n" . (string) $payload['lesson_narrative'] . "\n";
		}

		return $prompt;
	}

	/**
	 * Marker the model returns when a tradition needs no separate override.
	 *
	 * @return string
	 */
	public static function no_tradition_diff_marker() {
		return 'NO_TRADITION_DIFF';
	}

	/**
	 * Whether raw model output is the no-diff marker.
	 *
	 * @param string $raw Raw text.
	 * @return bool
	 */
	public static function is_no_tradition_diff_marker( $raw ) {
		$plain = strtoupper( trim( wp_strip_all_tags( (string) $raw ) ) );
		$plain = preg_replace( '/[^A-Z_]/', '', (string) $plain );
		return self::no_tradition_diff_marker() === $plain;
	}

	/**
	 * Build prompt + store keys for one passage (used by OpenAI Batch preload).
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse (0 for chapter scope).
	 * @param string $translation Translation slug.
	 * @param string $scope       verse|chapter.
	 * @param string $tradition   Tradition preset slug.
	 * @return array{skipped:bool,reference:string,prompt?:string,system?:string,store_payload?:array,kind?:string}|WP_Error
	 */
	public static function prepare_passage_for_batch( $book_id, $chapter, $verse, $translation, $scope, $tradition = '' ) {
		$book_id     = max( 1, (int) $book_id );
		$chapter     = max( 1, (int) $chapter );
		$verse       = max( 0, (int) $verse );
		$translation = sanitize_key( (string) $translation );
		$scope       = self::normalize_scope( (string) $scope, $verse );
		$tradition   = sanitize_key( (string) $tradition );

		if ( ! $translation ) {
			return new WP_Error( 'invalid_translation', 'invalid_translation' );
		}
		if ( 'verse' === $scope && $verse < 1 ) {
			return new WP_Error( 'verse_required', 'verse_required' );
		}

		$payload = self::build_payload( $book_id, $chapter, $verse, $translation, $scope );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$reference = (string) ( $payload['reference'] ?? '' );

		// Shared base path.
		if ( thw_premium_explain_tradition_uses_shared_base( $tradition ) || 'base' === $tradition ) {
			$base_slug                  = thw_premium_explain_base_tradition_slug();
			$store_payload              = $payload;
			$store_payload['tradition'] = $base_slug;
			$store_payload['scope']     = $scope;

			if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
				$saved = THW_Premium_Bible_Reader_Explain_Store::find_base( $store_payload );
				if ( is_array( $saved ) && THW_Premium_Bible_Reader_Explain_Store::has_usable_explanation( $saved ) ) {
					return array(
						'skipped'   => true,
						'reference' => $reference,
						'kind'      => 'base',
					);
				}
			}

			return array(
				'skipped'       => false,
				'reference'     => $reference,
				'kind'          => 'base',
				'prompt'        => self::build_prompt( $payload, $scope ),
				'system'        => thw_premium_build_ai_system_instruction( thw_premium_explain_base_rules() ),
				'store_payload' => $store_payload,
			);
		}

		// Tradition override path — requires a saved base explain.
		$context  = self::build_topic_context( $payload, $scope );
		$resolved = thw_premium_resolve_explain_rules_for_request( $tradition, $context, true );
		$preset   = $resolved['preset'];
		if ( thw_premium_explain_tradition_uses_shared_base( $preset ) ) {
			return self::prepare_passage_for_batch( $book_id, $chapter, $verse, $translation, $scope, 'base' );
		}

		$store_payload              = $payload;
		$store_payload['tradition'] = $preset;
		$store_payload['scope']     = $scope;

		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			$state = THW_Premium_Bible_Reader_Explain_Store::get_tradition_override_state( $store_payload );
			if ( 'override' === $state || 'same_as_base' === $state ) {
				return array(
					'skipped'   => true,
					'reference' => $reference,
					'kind'      => 'override',
					'no_diff'   => ( 'same_as_base' === $state ),
				);
			}
			$base = THW_Premium_Bible_Reader_Explain_Store::find_base( $store_payload );
			if ( ! is_array( $base ) || ! THW_Premium_Bible_Reader_Explain_Store::has_usable_explanation( $base ) ) {
				return new WP_Error( 'base_required', 'base_required' );
			}
			$base_html = THW_Premium_Bible_Reader_Explain_Store::get_explanation_html( $base );
		} else {
			return new WP_Error( 'base_required', 'base_required' );
		}

		return array(
			'skipped'       => false,
			'reference'     => $reference,
			'kind'          => 'override',
			'prompt'        => self::build_tradition_override_prompt( $payload, $scope, $base_html, $preset ),
			'system'        => thw_premium_build_ai_system_instruction( $resolved['rules'] ),
			'store_payload' => $store_payload,
		);
	}

	/**
	 * Prompt that asks for a tradition override only when needed.
	 *
	 * @param array  $payload   Passage payload.
	 * @param string $scope     Scope.
	 * @param string $base_html Shared base HTML.
	 * @param string $preset    Tradition slug.
	 * @return string
	 */
	public static function build_tradition_override_prompt( array $payload, $scope, $base_html, $preset ) {
		$reference = (string) ( $payload['reference'] ?? '' );
		$marker    = self::no_tradition_diff_marker();
		$base_text = trim( wp_strip_all_tags( (string) $base_html ) );
		$label     = $preset;
		$choices   = function_exists( 'thw_premium_get_tradition_preset_choices' )
			? thw_premium_get_tradition_preset_choices( false )
			: array();
		if ( isset( $choices[ $preset ] ) ) {
			$label = (string) $choices[ $preset ];
		}

		$prompt  = "You are reviewing a shared Bible explanation for a specific faith tradition.\n";
		$prompt .= "Shared explanation (tradition-neutral) for {$reference}:\n{$base_text}\n\n";
		$prompt .= "Target tradition: {$label} ({$preset}).\n";
		$prompt .= "If this tradition would explain the passage in a meaningfully different way on doctrine, practice, sacraments, or emphasis that the shared explanation misses or contradicts, write a complete alternate pastoral explanation in HTML using <h3> section headings and <p> paragraphs only";
		$prompt .= ( 'chapter' === $scope )
			? " with sections: Setting and flow, Key themes, Important verses, Living it today.\n"
			: " with sections: Historical lead-up, Why it was written, True meaning, Living it today, What follows.\n";
		$prompt .= "If the shared explanation is already appropriate for this tradition, respond with exactly {$marker} and nothing else.\n";
		$prompt .= "Do not invent scripture quotations beyond the passage context. Prefer {$marker} unless the difference is substantive.\n";

		return $prompt;
	}

	/**
	 * Persist raw model HTML for a passage (Batch API import path).
	 *
	 * @param array  $store_payload Payload with translation/tradition/scope/book keys.
	 * @param string $raw_html      Raw model output.
	 * @param bool   $flagged       Compliance flag (batch preload skips live compliance).
	 * @return array{ok:bool,post_id:int,reference:string,error:string,no_diff?:bool}
	 */
	public static function persist_passage_explanation( array $store_payload, $raw_html, $flagged = false ) {
		if ( self::is_no_tradition_diff_marker( $raw_html ) ) {
			$saved = class_exists( 'THW_Premium_Bible_Reader_Explain_Store' )
				? THW_Premium_Bible_Reader_Explain_Store::save_same_as_base( $store_payload )
				: null;
			return array(
				'ok'        => true,
				'post_id'   => is_array( $saved ) ? THW_Premium_Bible_Reader_Explain_Store::item_id( $saved ) : 0,
				'reference' => (string) ( $store_payload['reference'] ?? '' ),
				'error'     => '',
				'no_diff'   => true,
			);
		}

		$html = THW_Premium_AI_Client::format_html_response( $raw_html );
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return array(
				'ok'        => false,
				'post_id'   => 0,
				'reference' => (string) ( $store_payload['reference'] ?? '' ),
				'error'     => 'empty_response',
			);
		}

		$saved = null;
		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			$saved = THW_Premium_Bible_Reader_Explain_Store::save( $store_payload, $html, (bool) $flagged );
		}

		return array(
			'ok'        => is_array( $saved ),
			'post_id'   => is_array( $saved ) ? THW_Premium_Bible_Reader_Explain_Store::item_id( $saved ) : 0,
			'reference' => (string) ( $store_payload['reference'] ?? '' ),
			'error'     => is_array( $saved ) ? '' : 'save_failed',
			'no_diff'   => false,
		);
	}

	/**
	 * Generate and persist an explanation for one passage (used by REST + preload).
	 *
	 * Shared traditions write the base row; other traditions write an override only when needed.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse (0 for chapter scope).
	 * @param string $translation Translation slug.
	 * @param string $scope       verse|chapter.
	 * @param string $tradition   Tradition preset slug.
	 * @param array  $args        Optional: bypass_rate_limit (bool), user_id (int).
	 * @return array{ok:bool,skipped:bool,cached:bool,reference:string,post_id:int,error:string,flagged:bool,no_diff?:bool}
	 */
	public static function generate_for_passage( $book_id, $chapter, $verse, $translation, $scope, $tradition = '', $args = array() ) {
		$tradition = sanitize_key( (string) $tradition );
		if ( thw_premium_explain_tradition_uses_shared_base( $tradition ) || 'base' === $tradition ) {
			return self::generate_base_for_passage( $book_id, $chapter, $verse, $translation, $scope, $args );
		}
		return self::generate_tradition_override_for_passage( $book_id, $chapter, $verse, $translation, $scope, $tradition, $args );
	}

	/**
	 * Generate/persist the shared base explain for a passage.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation.
	 * @param string $scope       Scope.
	 * @param array  $args        Args.
	 * @return array<string, mixed>
	 */
	public static function generate_base_for_passage( $book_id, $chapter, $verse, $translation, $scope, $args = array() ) {
		$empty = array(
			'ok'        => false,
			'skipped'   => false,
			'cached'    => false,
			'reference' => '',
			'post_id'   => 0,
			'error'     => '',
			'flagged'   => false,
			'no_diff'   => false,
		);

		$book_id     = max( 1, (int) $book_id );
		$chapter     = max( 1, (int) $chapter );
		$verse       = max( 0, (int) $verse );
		$translation = sanitize_key( (string) $translation );
		$scope       = self::normalize_scope( (string) $scope, $verse );
		$bypass      = ! empty( $args['bypass_rate_limit'] );
		$user_id     = isset( $args['user_id'] ) ? (int) $args['user_id'] : get_current_user_id();
		$base_slug   = thw_premium_explain_base_tradition_slug();

		if ( ! $translation ) {
			$empty['error'] = 'invalid_translation';
			return $empty;
		}
		if ( 'verse' === $scope && $verse < 1 ) {
			$empty['error'] = 'verse_required';
			return $empty;
		}

		$payload = self::build_payload( $book_id, $chapter, $verse, $translation, $scope );
		if ( is_wp_error( $payload ) ) {
			$empty['error'] = $payload->get_error_message();
			return $empty;
		}

		$store_payload              = $payload;
		$store_payload['tradition'] = $base_slug;
		$store_payload['scope']     = $scope;

		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			$saved = THW_Premium_Bible_Reader_Explain_Store::find_base( $store_payload );
			if ( is_array( $saved ) && THW_Premium_Bible_Reader_Explain_Store::has_usable_explanation( $saved ) ) {
				// find_base() also copies legacy CPT / fallback-tradition rows into SQL under "base".
				$canonical = THW_Premium_Bible_Reader_Explain_Store::get_row( $store_payload );
				if ( ! is_array( $canonical ) || ! THW_Premium_Bible_Reader_Explain_Store::has_usable_explanation( $canonical ) ) {
					$canonical = THW_Premium_Bible_Reader_Explain_Store::ensure_sql_row_from_item( $store_payload, $saved );
				}
				if ( is_array( $canonical ) ) {
					$saved = $canonical;
				}
				return array(
					'ok'        => true,
					'skipped'   => true,
					'cached'    => true,
					'reference' => (string) ( $payload['reference'] ?? '' ),
					'post_id'   => THW_Premium_Bible_Reader_Explain_Store::item_id( $saved ),
					'error'     => '',
					'flagged'   => THW_Premium_Bible_Reader_Explain_Store::is_flagged( $saved ),
					'no_diff'   => false,
				);
			}
		}

		if ( ! self::is_available() ) {
			$empty['error'] = 'ai_unavailable';
			return $empty;
		}

		if ( ! $bypass ) {
			if ( $user_id < 1 ) {
				$empty['error'] = 'login_required';
				return $empty;
			}
			if ( ! self::check_rate_limit( $user_id ) ) {
				$empty['error'] = 'rate_limit';
				return $empty;
			}
		}

		$rules              = thw_premium_explain_base_rules();
		$prompt             = self::build_prompt( $payload, $scope );
		$system_instruction = thw_premium_build_ai_system_instruction( $rules );
		$result             = THW_Premium_AI_Client::generate_text( $prompt, $system_instruction );
		if ( is_wp_error( $result ) ) {
			$empty['error'] = $result->get_error_message();
			return $empty;
		}

		$html  = THW_Premium_AI_Client::format_html_response( $result );
		$saved = null;
		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			$saved = THW_Premium_Bible_Reader_Explain_Store::save( $store_payload, $html, false );
		}

		if ( ! $bypass && $user_id > 0 ) {
			self::increment_rate_limit( $user_id );
		}

		return array(
			'ok'        => true,
			'skipped'   => false,
			'cached'    => false,
			'reference' => (string) ( $payload['reference'] ?? '' ),
			'post_id'   => is_array( $saved ) ? THW_Premium_Bible_Reader_Explain_Store::item_id( $saved ) : 0,
			'error'     => '',
			'flagged'   => false,
			'no_diff'   => false,
		);
	}

	/**
	 * Generate a tradition override only when it meaningfully differs from base.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation.
	 * @param string $scope       Scope.
	 * @param string $tradition   Tradition slug.
	 * @param array  $args        Args.
	 * @return array<string, mixed>
	 */
	public static function generate_tradition_override_for_passage( $book_id, $chapter, $verse, $translation, $scope, $tradition, $args = array() ) {
		$empty = array(
			'ok'        => false,
			'skipped'   => false,
			'cached'    => false,
			'reference' => '',
			'post_id'   => 0,
			'error'     => '',
			'flagged'   => false,
			'no_diff'   => false,
		);

		$book_id     = max( 1, (int) $book_id );
		$chapter     = max( 1, (int) $chapter );
		$verse       = max( 0, (int) $verse );
		$translation = sanitize_key( (string) $translation );
		$scope       = self::normalize_scope( (string) $scope, $verse );
		$tradition   = sanitize_key( (string) $tradition );
		$bypass      = ! empty( $args['bypass_rate_limit'] );
		$user_id     = isset( $args['user_id'] ) ? (int) $args['user_id'] : get_current_user_id();

		if ( ! $translation || ! $tradition ) {
			$empty['error'] = 'invalid_translation';
			return $empty;
		}
		if ( 'verse' === $scope && $verse < 1 ) {
			$empty['error'] = 'verse_required';
			return $empty;
		}

		$payload = self::build_payload( $book_id, $chapter, $verse, $translation, $scope );
		if ( is_wp_error( $payload ) ) {
			$empty['error'] = $payload->get_error_message();
			return $empty;
		}

		$context  = self::build_topic_context( $payload, $scope );
		$resolved = thw_premium_resolve_explain_rules_for_request( $tradition, $context, true );
		$preset   = $resolved['preset'];
		$rules    = $resolved['rules'];

		if ( thw_premium_explain_tradition_uses_shared_base( $preset ) ) {
			return self::generate_base_for_passage( $book_id, $chapter, $verse, $translation, $scope, $args );
		}

		$store_payload              = $payload;
		$store_payload['tradition'] = $preset;
		$store_payload['scope']     = $scope;

		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			$state = THW_Premium_Bible_Reader_Explain_Store::get_tradition_override_state( $store_payload );
			if ( 'override' === $state ) {
				$existing = THW_Premium_Bible_Reader_Explain_Store::get_row( $store_payload );
				return array(
					'ok'        => true,
					'skipped'   => true,
					'cached'    => true,
					'reference' => (string) ( $payload['reference'] ?? '' ),
					'post_id'   => is_array( $existing ) ? THW_Premium_Bible_Reader_Explain_Store::item_id( $existing ) : 0,
					'error'     => '',
					'flagged'   => is_array( $existing ) ? THW_Premium_Bible_Reader_Explain_Store::is_flagged( $existing ) : false,
					'no_diff'   => false,
				);
			}
			if ( 'same_as_base' === $state ) {
				return array(
					'ok'        => true,
					'skipped'   => true,
					'cached'    => true,
					'reference' => (string) ( $payload['reference'] ?? '' ),
					'post_id'   => 0,
					'error'     => '',
					'flagged'   => false,
					'no_diff'   => true,
				);
			}
		}

		// Ensure shared base exists first.
		$base_gen = self::generate_base_for_passage( $book_id, $chapter, $verse, $translation, $scope, $args );
		if ( empty( $base_gen['ok'] ) ) {
			$empty['error'] = (string) ( $base_gen['error'] ?? 'base_failed' );
			return $empty;
		}

		$base = class_exists( 'THW_Premium_Bible_Reader_Explain_Store' )
			? THW_Premium_Bible_Reader_Explain_Store::find_base( $store_payload )
			: null;
		if ( ! is_array( $base ) ) {
			$empty['error'] = 'base_required';
			return $empty;
		}
		$base_html = THW_Premium_Bible_Reader_Explain_Store::get_explanation_html( $base );

		if ( ! self::is_available() ) {
			$empty['error'] = 'ai_unavailable';
			return $empty;
		}

		if ( ! $bypass ) {
			if ( $user_id < 1 ) {
				$empty['error'] = 'login_required';
				return $empty;
			}
			if ( ! self::check_rate_limit( $user_id ) ) {
				$empty['error'] = 'rate_limit';
				return $empty;
			}
		}

		$prompt             = self::build_tradition_override_prompt( $payload, $scope, $base_html, $preset );
		$system_instruction = thw_premium_build_ai_system_instruction( $rules );
		$result             = THW_Premium_AI_Client::generate_text( $prompt, $system_instruction );
		if ( is_wp_error( $result ) ) {
			$empty['error'] = $result->get_error_message();
			return $empty;
		}

		if ( self::is_no_tradition_diff_marker( $result ) ) {
			$saved = THW_Premium_Bible_Reader_Explain_Store::save_same_as_base( $store_payload );
			if ( ! $bypass && $user_id > 0 ) {
				self::increment_rate_limit( $user_id );
			}
			return array(
				'ok'        => true,
				'skipped'   => true,
				'cached'    => false,
				'reference' => (string) ( $payload['reference'] ?? '' ),
				'post_id'   => is_array( $saved ) ? THW_Premium_Bible_Reader_Explain_Store::item_id( $saved ) : 0,
				'error'     => '',
				'flagged'   => false,
				'no_diff'   => true,
			);
		}

		$html  = THW_Premium_AI_Client::format_html_response( $result );
		$saved = class_exists( 'THW_Premium_Bible_Reader_Explain_Store' )
			? THW_Premium_Bible_Reader_Explain_Store::save( $store_payload, $html, false )
			: null;

		if ( ! $bypass && $user_id > 0 ) {
			self::increment_rate_limit( $user_id );
		}

		return array(
			'ok'        => true,
			'skipped'   => false,
			'cached'    => false,
			'reference' => (string) ( $payload['reference'] ?? '' ),
			'post_id'   => is_array( $saved ) ? THW_Premium_Bible_Reader_Explain_Store::item_id( $saved ) : 0,
			'error'     => '',
			'flagged'   => false,
			'no_diff'   => false,
		);
	}

	/**
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function check_rate_limit( $user_id ) {
		$key   = 'thw_ai_explain_' . (int) $user_id;
		$count = (int) get_transient( $key );
		return $count < self::RATE_LIMIT;
	}

	/**
	 * @param int $user_id User ID.
	 * @return void
	 */
	private static function increment_rate_limit( $user_id ) {
		$key   = 'thw_ai_explain_' . (int) $user_id;
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::RATE_WINDOW );
	}
}
