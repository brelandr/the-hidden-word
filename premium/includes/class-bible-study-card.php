<?php
/**
 * Structured Verse Study Card REST API.
 *
 * Returns plain-words paraphrase, context, keywords, cross-references,
 * and a live-it reflection for a single verse.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Bible_Study_Card
 */
class THW_Premium_Bible_Study_Card {

	const RATE_LIMIT  = 12;
	const RATE_WINDOW = 3600;
	const CACHE_TTL   = WEEK_IN_SECONDS;

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'hwbl_bible_reader_features', array( __CLASS__, 'filter_reader_features' ) );
		self::register_shortcode();
	}

	/**
	 * Register shortcodes (safe to call more than once).
	 */
	public static function register_shortcode() {
		if ( function_exists( 'thw_premium_register_shortcode' ) ) {
			thw_premium_register_shortcode( 'thw_verse_study', array( __CLASS__, 'render_shortcode' ) );
		} else {
			add_shortcode( 'hwbl_verse_study', array( __CLASS__, 'render_shortcode' ) );
		}
	}

	/**
	 * Expose study_card in Bible reader feature flags.
	 *
	 * @param array<string, bool> $features Features.
	 * @return array<string, bool>
	 */
	public static function filter_reader_features( $features ) {
		$features['study_card'] = true;
		return $features;
	}

	/**
	 * Whether the current request should load study-card assets.
	 *
	 * @return bool
	 */
	public static function needs_assets() {
		global $post;
		if ( ! $post ) {
			return false;
		}
		if ( function_exists( 'thw_premium_content_has_shortcode' )
			&& thw_premium_content_has_shortcode( $post->post_content, 'thw_verse_study' ) ) {
			return true;
		}
		if ( function_exists( 'has_block' ) && has_block( 'hwbl/verse-study', $post ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Register + enqueue front-end assets and localize config.
	 */
	public static function enqueue_assets() {
		$style_handle  = 'hwbl-verse-study';
		$script_handle = 'hwbl-verse-study';

		wp_register_style(
			$style_handle,
			THW_PREMIUM_URL . 'public/css/verse-study-card.css',
			array(),
			defined( 'THW_PREMIUM_VERSION' ) ? THW_PREMIUM_VERSION : HWBL_VERSION
		);
		wp_register_script(
			$script_handle,
			THW_PREMIUM_URL . 'public/js/verse-study-card.js',
			wp_script_is( 'hwbl-user-preferences', 'registered' )
				? array( 'hwbl-user-preferences' )
				: array(),
			defined( 'THW_PREMIUM_VERSION' ) ? THW_PREMIUM_VERSION : HWBL_VERSION,
			true
		);

		wp_enqueue_style( $style_handle );
		wp_enqueue_script( $script_handle );

		$ai_unavailable = function_exists( 'thw_premium_ai_frontend_unavailable_reason' )
			? thw_premium_ai_frontend_unavailable_reason()
			: '';

		wp_localize_script(
			$script_handle,
			'hwblVerseStudy',
			array(
				'restUrl'             => esc_url_raw( rest_url( 'hwbl/v1/bible/study-card' ) ),
				'booksUrl'            => esc_url_raw( rest_url( 'hwbl/v1/bible/books' ) ),
				'nonce'               => wp_create_nonce( 'wp_rest' ),
				'loggedIn'            => is_user_logged_in(),
				'aiEnabled'           => '' === $ai_unavailable,
				'userTradition'       => function_exists( 'thw_premium_show_tradition_select' )
					? thw_premium_show_tradition_select()
					: false,
				'traditionStorageKey' => 'thw_ai_tradition_preset',
				'i18n'                => array(
					'loading'       => __( 'Building your Verse Study Card…', 'hidden-word-bible-lessons' ),
					'error'         => __( 'Could not build a study card. Please try again.', 'hidden-word-bible-lessons' ),
					'loginRequired' => __( 'Sign in to generate a Verse Study Card. Once saved, everyone can read it.', 'hidden-word-bible-lessons' ),
					'verseRequired' => __( 'Choose a book, chapter, and verse first.', 'hidden-word-bible-lessons' ),
					'plainWords'    => __( 'In plain words', 'hidden-word-bible-lessons' ),
					'context'       => __( 'In context', 'hidden-word-bible-lessons' ),
					'keywords'      => __( 'Key words', 'hidden-word-bible-lessons' ),
					'xrefs'         => __( 'Cross-references', 'hidden-word-bible-lessons' ),
					'liveIt'        => __( 'Live it', 'hidden-word-bible-lessons' ),
					'lesson'        => __( 'Open related lesson', 'hidden-word-bible-lessons' ),
					'disclaimer'    => __( 'AI-generated study guidance. Always compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
					'cached'        => __( 'Saved study', 'hidden-word-bible-lessons' ),
					'disabled'      => $ai_unavailable
						? $ai_unavailable
						: __( 'AI study is not enabled on this site.', 'hidden-word-bible-lessons' ),
					'studyBtn'      => __( 'Study this verse', 'hidden-word-bible-lessons' ),
				),
			)
		);
	}

	/**
	 * Shortcode / block render callback.
	 *
	 * Attributes: book_id, chapter, verse, translation, title, picker (1|0).
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'book_id'     => '43',
				'chapter'     => '3',
				'verse'       => '16',
				'translation' => '',
				'title'       => __( 'Verse Study Card', 'hidden-word-bible-lessons' ),
				'picker'      => '1',
			),
			$atts,
			'hwbl_verse_study'
		);

		self::enqueue_assets();

		$book_id     = max( 1, (int) $atts['book_id'] );
		$chapter     = max( 1, (int) $atts['chapter'] );
		$verse       = max( 1, (int) $atts['verse'] );
		$translation = sanitize_key( (string) $atts['translation'] );
		$picker      = ! in_array( strtolower( (string) $atts['picker'] ), array( '0', 'false', 'no', 'off' ), true );
		$title       = sanitize_text_field( (string) $atts['title'] );
		$uid         = 'hwbl-verse-study-' . wp_unique_id();

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $uid ); ?>"
			class="hwbl-verse-study"
			data-book-id="<?php echo esc_attr( (string) $book_id ); ?>"
			data-chapter="<?php echo esc_attr( (string) $chapter ); ?>"
			data-verse="<?php echo esc_attr( (string) $verse ); ?>"
			data-translation="<?php echo esc_attr( $translation ); ?>"
			data-picker="<?php echo $picker ? '1' : '0'; ?>"
			data-autofetch="<?php echo $picker ? '0' : '1'; ?>"
		>
			<?php if ( $title ) : ?>
				<h3 class="hwbl-verse-study__heading"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<p class="hwbl-verse-study__intro description">
				<?php esc_html_e( 'Plain words, context, key words, cross-references, and one reflection question for a single verse.', 'hidden-word-bible-lessons' ); ?>
			</p>
			<?php if ( $picker ) : ?>
				<div class="hwbl-verse-study__picker">
					<label class="hwbl-verse-study__field">
						<span><?php esc_html_e( 'Book', 'hidden-word-bible-lessons' ); ?></span>
						<select class="hwbl-verse-study__book"></select>
					</label>
					<label class="hwbl-verse-study__field">
						<span><?php esc_html_e( 'Chapter', 'hidden-word-bible-lessons' ); ?></span>
						<input type="number" class="hwbl-verse-study__chapter" min="1" value="<?php echo esc_attr( (string) $chapter ); ?>" />
					</label>
					<label class="hwbl-verse-study__field">
						<span><?php esc_html_e( 'Verse', 'hidden-word-bible-lessons' ); ?></span>
						<input type="number" class="hwbl-verse-study__verse" min="1" value="<?php echo esc_attr( (string) $verse ); ?>" />
					</label>
				</div>
			<?php endif; ?>
			<?php if ( function_exists( 'thw_premium_the_tradition_select' ) && function_exists( 'thw_premium_show_tradition_select' ) && thw_premium_show_tradition_select() ) : ?>
				<?php
				thw_premium_the_tradition_select(
					array(
						'id'    => $uid . '-tradition',
						'class' => 'thw-ai-tradition-select hwbl-verse-study__tradition',
					)
				);
				?>
			<?php endif; ?>
			<p class="hwbl-verse-study__actions">
				<button type="button" class="hwbl-btn hwbl-verse-study__btn">
					<?php esc_html_e( 'Study this verse', 'hidden-word-bible-lessons' ); ?>
				</button>
			</p>
			<div class="hwbl-verse-study__status" role="status" aria-live="polite"></div>
			<div class="hwbl-verse-study__result" hidden></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$route = array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'rest_study_card' ),
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
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'translation' => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				),
				'tradition'   => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				),
			),
		);

		register_rest_route( 'hwbl/v1', '/bible/study-card', $route );
		register_rest_route( 'thw/v1', '/bible/study-card', $route );
	}

	/**
	 * REST: build or return a cached study card for one verse.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_study_card( $request ) {
		if ( ! class_exists( 'HWBL_Bible_Reader' ) || ! HWBL_Bible_Reader::is_enabled() ) {
			return new WP_Error(
				'hwbl_reader_disabled',
				__( 'The Bible reader is not available.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		if ( ! class_exists( 'THW_Premium_Bible_Reader_Explain' ) ) {
			return new WP_Error(
				'hwbl_study_unavailable',
				__( 'Verse study is not available on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		$book_id     = max( 1, (int) $request['book_id'] );
		$chapter     = max( 1, (int) $request['chapter'] );
		$verse       = max( 1, (int) $request['verse'] );
		$translation = HWBL_Bible_Reader::resolve_translation_for_request( (string) $request['translation'] );
		if ( ! $translation ) {
			return new WP_Error(
				'invalid_translation',
				__( 'That translation is not available.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$payload = THW_Premium_Bible_Reader_Explain::build_payload(
			$book_id,
			$chapter,
			$verse,
			$translation,
			'verse'
		);
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$context   = (string) ( $payload['reference'] ?? '' );
		$tradition = sanitize_key( (string) $request['tradition'] );
		$resolved  = function_exists( 'thw_premium_resolve_explain_rules_for_request' )
			? thw_premium_resolve_explain_rules_for_request( $tradition, $context )
			: array(
				'preset' => $tradition ? $tradition : 'site',
				'rules'  => '',
			);
		$preset    = sanitize_key( (string) ( $resolved['preset'] ?? 'site' ) );

		if ( is_user_logged_in() && function_exists( 'thw_premium_user_tradition_enabled' ) && thw_premium_user_tradition_enabled() && 'site' !== $preset ) {
			thw_premium_set_user_tradition_preset( get_current_user_id(), $preset );
		}

		$cache_key = self::cache_key( $book_id, $chapter, $verse, $translation, $preset );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && self::is_valid_card( $cached ) ) {
			$cached['cached'] = true;
			return new WP_REST_Response( self::enrich_with_research( $cached, $book_id, $chapter, $verse ), 200 );
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'thw_ai_login',
				__( 'Sign in to generate a Verse Study Card. Once saved, everyone can read it.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		if ( ! class_exists( 'THW_Premium_AI_Client' ) || ! THW_Premium_AI_Client::is_configured() ) {
			return new WP_Error(
				'thw_ai_disabled',
				__( 'AI study is not enabled on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		if ( ! self::check_rate_limit( get_current_user_id() ) ) {
			return new WP_Error(
				'thw_ai_rate_limit',
				__( 'Hourly study-card limit reached. Try again later.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}

		$card = self::generate_card( $payload, $preset, $resolved );
		if ( is_wp_error( $card ) ) {
			return $card;
		}

		self::increment_rate_limit( get_current_user_id() );
		set_transient( $cache_key, $card, self::CACHE_TTL );

		$card['cached'] = false;
		return new WP_REST_Response( self::enrich_with_research( $card, $book_id, $chapter, $verse ), 200 );
	}

	/**
	 * Ensure a Verse Study Card is cached for the site-default tradition (guest path).
	 *
	 * Used by daily auto-study cron so visitors can open “Study this verse” without signing in.
	 * Skips AI when a valid transient already exists.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation slug.
	 * @param string $tradition   Optional tradition override (empty = same as guest default).
	 * @return array{status:string,preset?:string,card?:array<string,mixed>}|WP_Error
	 */
	public static function ensure_cached_card( $book_id, $chapter, $verse, $translation, $tradition = '' ) {
		$book_id     = max( 1, (int) $book_id );
		$chapter     = max( 1, (int) $chapter );
		$verse       = max( 1, (int) $verse );
		$translation = sanitize_key( (string) $translation );

		if ( ! class_exists( 'HWBL_Bible_Reader' ) || ! HWBL_Bible_Reader::is_enabled() ) {
			return new WP_Error(
				'hwbl_reader_disabled',
				__( 'The Bible reader is not available.', 'hidden-word-bible-lessons' )
			);
		}

		if ( ! class_exists( 'THW_Premium_Bible_Reader_Explain' ) ) {
			return new WP_Error(
				'hwbl_study_unavailable',
				__( 'Verse study is not available on this site.', 'hidden-word-bible-lessons' )
			);
		}

		if ( class_exists( 'HWBL_Bible_Reader' ) ) {
			$resolved_translation = HWBL_Bible_Reader::resolve_translation_for_request( $translation );
			if ( $resolved_translation ) {
				$translation = $resolved_translation;
			}
		}

		$payload = THW_Premium_Bible_Reader_Explain::build_payload(
			$book_id,
			$chapter,
			$verse,
			$translation,
			'verse'
		);
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$context  = (string) ( $payload['reference'] ?? '' );
		$resolved = function_exists( 'thw_premium_resolve_explain_rules_for_request' )
			? thw_premium_resolve_explain_rules_for_request( sanitize_key( (string) $tradition ), $context )
			: array(
				'preset' => 'site',
				'rules'  => '',
			);
		$preset   = sanitize_key( (string) ( $resolved['preset'] ?? 'site' ) );
		if ( '' === $preset ) {
			$preset = 'site';
		}

		$cache_key = self::cache_key( $book_id, $chapter, $verse, $translation, $preset );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && self::is_valid_card( $cached ) ) {
			return array(
				'status' => 'skipped',
				'preset' => $preset,
				'card'   => $cached,
			);
		}

		if ( ! class_exists( 'THW_Premium_AI_Client' ) || ! THW_Premium_AI_Client::is_configured() ) {
			return new WP_Error(
				'thw_ai_disabled',
				__( 'AI study is not enabled on this site.', 'hidden-word-bible-lessons' )
			);
		}

		$card = self::generate_card( $payload, $preset, $resolved );
		if ( is_wp_error( $card ) ) {
			return $card;
		}

		set_transient( $cache_key, $card, self::CACHE_TTL );

		return array(
			'status' => 'generated',
			'preset' => $preset,
			'card'   => $card,
		);
	}

	/**
	 * Generate structured study sections via AI.
	 *
	 * @param array<string, mixed> $payload  Passage payload from build_payload.
	 * @param string               $preset   Tradition preset.
	 * @param array<string, mixed> $resolved Rules resolution.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function generate_card( array $payload, $preset, array $resolved ) {
		$reference   = (string) ( $payload['reference'] ?? '' );
		$text        = (string) ( $payload['text'] ?? '' );
		$translation = (string) ( $payload['translation'] ?? '' );

		/*
		 * Study cards are short educational paraphrases. Full tradition digests often
		 * require formal citations (BF&M, CIC/CCC, etc.) that these cards cannot carry.
		 * Use a light tradition-tone instruction instead of the full explain system prompt,
		 * and do not hard-fail on post-hoc citation compliance.
		 */
		$system = self::build_study_system_instruction( $preset, (string) ( $resolved['rules'] ?? '' ) );
		$prompt = self::build_prompt( $reference, $text, $translation, $preset );
		$raw    = THW_Premium_AI_Client::generate_text( $prompt, $system );
		if ( is_wp_error( $raw ) ) {
			return $raw;
		}

		$parsed = self::parse_ai_card( (string) $raw );
		if ( ! $parsed ) {
			// One retry with a stricter JSON reminder.
			$retry_prompt = $prompt . "\n\nIMPORTANT: Return ONLY the JSON object. No markdown fences.";
			$raw_retry    = THW_Premium_AI_Client::generate_text( $retry_prompt, $system );
			if ( ! is_wp_error( $raw_retry ) ) {
				$parsed = self::parse_ai_card( (string) $raw_retry );
			}
		}
		if ( ! $parsed ) {
			return new WP_Error(
				'thw_ai_parse_failed',
				__( 'Could not build a study card for this verse. Please try again.', 'hidden-word-bible-lessons' ),
				array( 'status' => 502 )
			);
		}

		return array(
			'reference'       => $reference,
			'text'            => $text,
			'translation'     => $translation,
			'tradition'       => $preset,
			'bookId'          => (int) ( $payload['book_id'] ?? 0 ),
			'chapter'         => (int) ( $payload['chapter'] ?? 0 ),
			'verse'           => (int) ( $payload['verse'] ?? 0 ),
			'plainWords'      => (string) ( $parsed['plainWords'] ?? '' ),
			'context'         => (string) ( $parsed['context'] ?? '' ),
			'keywords'        => is_array( $parsed['keywords'] ?? null ) ? $parsed['keywords'] : array(),
			'crossReferences' => is_array( $parsed['crossReferences'] ?? null ) ? $parsed['crossReferences'] : array(),
			'liveIt'          => (string) ( $parsed['liveIt'] ?? '' ),
		);
	}

	/**
	 * Light system instruction for study cards (tone/stance, not citation digests).
	 *
	 * @param string $preset Tradition preset slug.
	 * @param string $rules  Full resolved rules (used only for a short excerpt).
	 * @return string
	 */
	public static function build_study_system_instruction( $preset, $rules = '' ) {
		$preset = sanitize_key( (string) $preset );
		$label  = $preset && 'site' !== $preset ? $preset : 'the site’s faith tradition';

		$instruction = 'You are a Bible study helper for a church website. '
			. "Write a short Verse Study Card faithful to the verse text and sensitive to {$label}. "
			. 'Stay pastoral and clear. Do not invent doctrines not present in the verse. '
			. 'Do not invent or require formal tradition citations (catechism numbers, confession article IDs, canon law, etc.). '
			. 'Never contradict historic Christian teaching on the Trinity, the person of Christ, or salvation by grace.';

		$rules = trim( (string) $rules );
		if ( $rules ) {
			// Keep a brief excerpt so stance/tone can influence without citation mandates.
			$excerpt = wp_strip_all_tags( $rules );
			if ( strlen( $excerpt ) > 800 ) {
				$excerpt = substr( $excerpt, 0, 800 ) . '…';
			}
			$instruction .= "\n\nTradition tone notes (follow stance/tone only; ignore citation requirements):\n" . $excerpt;
		}

		return $instruction;
	}

	/**
	 * Prompt for JSON study card.
	 *
	 * @param string $reference   Reference.
	 * @param string $text        Verse text.
	 * @param string $translation Translation slug.
	 * @param string $preset      Tradition.
	 * @return string
	 */
	public static function build_prompt( $reference, $text, $translation, $preset ) {
		return "Create a short Verse Study Card for {$reference} ({$translation}). Tradition focus: {$preset}.\n\n"
			. "Verse text:\n\"{$text}\"\n\n"
			. "Return ONLY valid JSON with this exact shape:\n"
			. "{\n"
			. "  \"plainWords\": \"2-4 sentence paraphrase in everyday English\",\n"
			. "  \"context\": \"2-4 sentences: who wrote it, to whom, and what surrounds this verse\",\n"
			. "  \"keywords\": [{\"term\": \"word or phrase\", \"note\": \"why it matters (1 sentence)\"}],\n"
			. "  \"crossReferences\": [{\"reference\": \"Book C:V\", \"why\": \"how it connects (1 sentence)\"}],\n"
			. "  \"liveIt\": \"one reflection question the reader can answer today\"\n"
			. "}\n\n"
			. "Rules:\n"
			. "- Include 2 or 3 keywords and 2 to 4 crossReferences.\n"
			. "- Stay faithful to the text; do not invent doctrines not present in the verse.\n"
			. "- Do not include markdown fences or commentary outside JSON.";
	}

	/**
	 * Parse AI JSON into a card array.
	 *
	 * @param string $raw Raw model output.
	 * @return array<string, mixed>|null
	 */
	public static function parse_ai_card( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return null;
		}
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)```/i', $raw, $m ) ) {
			$raw = trim( $m[1] );
		}
		$start = strpos( $raw, '{' );
		$end   = strrpos( $raw, '}' );
		if ( false === $start || false === $end || $end <= $start ) {
			return null;
		}
		$decoded = json_decode( substr( $raw, $start, $end - $start + 1 ), true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		$plain = sanitize_textarea_field( (string) ( $decoded['plainWords'] ?? '' ) );
		$ctx   = sanitize_textarea_field( (string) ( $decoded['context'] ?? '' ) );
		$live  = sanitize_textarea_field( (string) ( $decoded['liveIt'] ?? '' ) );
		if ( '' === $plain || '' === $ctx ) {
			return null;
		}

		$keywords = array();
		foreach ( (array) ( $decoded['keywords'] ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$term = sanitize_text_field( (string) ( $row['term'] ?? '' ) );
			$note = sanitize_text_field( (string) ( $row['note'] ?? '' ) );
			if ( $term && $note ) {
				$keywords[] = array(
					'term' => $term,
					'note' => $note,
				);
			}
			if ( count( $keywords ) >= 3 ) {
				break;
			}
		}

		$xrefs = array();
		foreach ( (array) ( $decoded['crossReferences'] ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$ref = sanitize_text_field( (string) ( $row['reference'] ?? '' ) );
			$why = sanitize_text_field( (string) ( $row['why'] ?? '' ) );
			if ( $ref ) {
				$xrefs[] = array(
					'reference' => $ref,
					'why'       => $why,
				);
			}
			if ( count( $xrefs ) >= 4 ) {
				break;
			}
		}

		return array(
			'plainWords'      => $plain,
			'context'         => $ctx,
			'keywords'        => $keywords,
			'crossReferences' => $xrefs,
			'liveIt'          => $live,
		);
	}

	/**
	 * Attach curriculum research fields.
	 *
	 * @param array<string, mixed> $card    Card payload.
	 * @param int                  $book_id Book ID.
	 * @param int                  $chapter Chapter.
	 * @param int                  $verse   Verse.
	 * @return array<string, mixed>
	 */
	public static function enrich_with_research( array $card, $book_id, $chapter, $verse ) {
		$lesson = HWBL_Bible_Reader::find_curriculum_lesson( $book_id, $chapter, $verse );
		$card['lessonId']      = (int) ( $lesson['lesson_id'] ?? 0 );
		$card['lessonUrl']     = (string) ( $lesson['url'] ?? '' );
		$card['inCurriculum'] = ! empty( $lesson['in_curriculum'] );
		return $card;
	}

	/**
	 * Whether a cached card has required fields.
	 *
	 * @param array<string, mixed> $card Card.
	 * @return bool
	 */
	public static function is_valid_card( array $card ) {
		return ! empty( $card['plainWords'] ) && ! empty( $card['context'] ) && ! empty( $card['reference'] );
	}

	/**
	 * Transient cache key.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation.
	 * @param string $preset      Tradition.
	 * @return string
	 */
	public static function cache_key( $book_id, $chapter, $verse, $translation, $preset ) {
		return 'hwbl_study_card_' . md5( implode( '|', array( (int) $book_id, (int) $chapter, (int) $verse, $translation, $preset ) ) );
	}

	/**
	 * Rate limit check.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function check_rate_limit( $user_id ) {
		$key   = 'hwbl_study_card_rl_' . (int) $user_id;
		$count = (int) get_transient( $key );
		return $count < self::RATE_LIMIT;
	}

	/**
	 * Increment rate limit.
	 *
	 * @param int $user_id User ID.
	 */
	private static function increment_rate_limit( $user_id ) {
		$key   = 'hwbl_study_card_rl_' . (int) $user_id;
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::RATE_WINDOW );
	}
}
