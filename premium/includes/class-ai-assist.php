<?php
/**
 * AI-assisted lesson drafting.
 *
 * Uses the WordPress 7.0 AI Client when available (Settings → Connectors).
 * Falls back to BYOK OpenAI / Claude keys on older WordPress versions.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_AI_Assist
 */
class THW_Premium_AI_Assist {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_ai_meta_box' ) );
		add_action( 'wp_ajax_thw_ai_draft_context', array( __CLASS__, 'ajax_draft_context' ) );
		add_action( 'wp_ajax_thw_ai_draft_questions', array( __CLASS__, 'ajax_draft_questions' ) );
		add_action( 'wp_ajax_thw_ai_translate_content', array( __CLASS__, 'ajax_translate_content' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_filter( 'hwbl_lesson_data', array( __CLASS__, 'apply_stored_translation' ), 10, 2 );
	}

	/**
	 * Enqueue AI assist scripts.
	 *
	 * @param string $hook Admin hook.
	 */
	public static function enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'hwbl_lesson' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'thw-ai-assist',
			THW_PREMIUM_URL . 'admin/js/ai-assist.js',
			array( 'jquery' ),
			THW_PREMIUM_VERSION,
			true
		);

		wp_localize_script(
			'thw-ai-assist',
			'thwAiAssist',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'thw_ai_assist' ),
				'i18n'    => array(
					'draftingContext'   => __( 'Drafting context…', 'hidden-word-bible-lessons' ),
					'contextDrafted'    => __( 'Historical context drafted. Review and edit before saving.', 'hidden-word-bible-lessons' ),
					'generatingQs'      => __( 'Generating questions…', 'hidden-word-bible-lessons' ),
					'questionsReady'    => __( 'Discussion questions generated. Review and edit before saving.', 'hidden-word-bible-lessons' ),
					'translating'       => __( 'Translating content…', 'hidden-word-bible-lessons' ),
					/* translators: %s: locale code */
					'translationSaved'  => __( 'Translation saved for locale: %s.', 'hidden-word-bible-lessons' ),
					'requestFailed'     => __( 'Request failed.', 'hidden-word-bible-lessons' ),
					'remove'            => __( 'Remove', 'hidden-word-bible-lessons' ),
				),
			)
		);
	}

	/**
	 * Add AI assist meta box.
	 */
	public static function add_ai_meta_box() {
		if ( ! THW_Premium_AI_Client::is_configured() ) {
			return;
		}

		add_meta_box(
			'thw_ai_assist',
			__( 'AI Lesson Assistant', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_meta_box' ),
			'hwbl_lesson',
			'side',
			'high'
		);
	}

	/**
	 * Render AI meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		?>
		<p><?php esc_html_e( 'Use AI to draft lesson content. Results are editable suggestions.', 'hidden-word-bible-lessons' ); ?></p>
		<?php if ( THW_Premium_AI_Client::uses_core_ai() ) : ?>
			<p class="description">
				<?php esc_html_e( 'Powered by your WordPress AI connector (Settings → Connectors).', 'hidden-word-bible-lessons' ); ?>
			</p>
		<?php endif; ?>
		<p>
			<button type="button" class="button" id="thw-ai-draft-context" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
				<?php esc_html_e( 'Draft Historical Context', 'hidden-word-bible-lessons' ); ?>
			</button>
		</p>
		<p>
			<button type="button" class="button" id="thw-ai-draft-questions" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
				<?php esc_html_e( 'Generate Discussion Questions', 'hidden-word-bible-lessons' ); ?>
			</button>
		</p>
		<p>
			<label for="thw-ai-translate-locale"><?php esc_html_e( 'Translate lesson content to:', 'hidden-word-bible-lessons' ); ?></label>
			<select id="thw-ai-translate-locale">
				<option value="es"><?php esc_html_e( 'Spanish', 'hidden-word-bible-lessons' ); ?></option>
				<option value="pt"><?php esc_html_e( 'Portuguese', 'hidden-word-bible-lessons' ); ?></option>
				<option value="fr"><?php esc_html_e( 'French', 'hidden-word-bible-lessons' ); ?></option>
			</select>
			<button type="button" class="button" id="thw-ai-translate-content" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
				<?php esc_html_e( 'Translate & Save', 'hidden-word-bible-lessons' ); ?>
			</button>
		</p>
		<div id="thw-ai-status"></div>
		<?php
	}

	/**
	 * Verify AJAX nonce and object-level edit capability for a lesson.
	 *
	 * @return int Authorized lesson post ID.
	 */
	private static function authorize_lesson_ajax() {
		if ( ! check_ajax_referer( 'thw_ai_assist', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'hidden-word-bible-lessons' ) ), 403 );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		if ( ! $post_id || 'hwbl_lesson' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'hidden-word-bible-lessons' ) ), 403 );
		}

		return $post_id;
	}

	/**
	 * AJAX: draft historical context.
	 */
	public static function ajax_draft_context() {
		$post_id = self::authorize_lesson_ajax();
		$lesson  = HWBL_CPT_Lesson::get_lesson_data( $post_id );

		$prompt = sprintf(
			'Write a concise historical and cultural context (2-3 paragraphs, HTML format with <p> tags) for %s. Focus on who wrote it, the original audience, and the political/cultural climate. Do not include the verse text itself.',
			$lesson['reference']
		);

		$result = THW_Premium_AI_Client::generate_text( $prompt );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'content' => $result ) );
	}

	/**
	 * AJAX: draft discussion questions.
	 */
	public static function ajax_draft_questions() {
		$post_id = self::authorize_lesson_ajax();
		$lesson  = HWBL_CPT_Lesson::get_lesson_data( $post_id );

		$prompt = sprintf(
			'Generate 5 thoughtful small-group discussion questions for the Bible verse %s. Return only a JSON array of strings, no other text.',
			$lesson['reference']
		);

		$result = THW_Premium_AI_Client::generate_json_string_array( $prompt );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$questions = json_decode( $result, true );
		if ( ! is_array( $questions ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not parse AI response.', 'hidden-word-bible-lessons' ) ) );
		}

		wp_send_json_success( array( 'questions' => $questions ) );
	}

	/**
	 * AJAX: translate lesson content and store in post meta.
	 */
	public static function ajax_translate_content() {
		$post_id = self::authorize_lesson_ajax();
		// Nonce already verified in authorize_lesson_ajax(); re-check here so PHPCS sees it in this function.
		check_ajax_referer( 'thw_ai_assist', 'nonce' );
		$locale = isset( $_POST['locale'] ) ? sanitize_text_field( wp_unslash( $_POST['locale'] ) ) : '';
		if ( ! $locale ) {
			wp_send_json_error( array( 'message' => __( 'Missing parameters.', 'hidden-word-bible-lessons' ) ) );
		}

		$lesson = HWBL_CPT_Lesson::get_lesson_data( $post_id );
		$prompt = sprintf(
			'Translate the following Bible lesson content to %1$s. Return JSON with keys historical_context, preceding_narrative, discussion_questions (array of strings). Reference: %2$s. Context: %3$s. Narrative: %4$s. Questions: %5$s',
			$locale,
			$lesson['reference'],
			wp_strip_all_tags( $lesson['historical_context'] ),
			wp_strip_all_tags( $lesson['preceding_narrative'] ),
			wp_json_encode( $lesson['discussion_questions'] )
		);

		$result = THW_Premium_AI_Client::generate_text( $prompt );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$decoded = json_decode( $result, true );
		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not parse AI response.', 'hidden-word-bible-lessons' ) ) );
		}

		$locale_key = sanitize_key( $locale );
		update_post_meta( $post_id, '_hwbl_translation_' . $locale_key, wp_json_encode( $decoded ) );
		wp_send_json_success( array( 'locale' => $locale_key ) );
	}

	/**
	 * Apply stored translation when site locale matches.
	 *
	 * @param array $lesson    Lesson data.
	 * @param int   $lesson_id Lesson ID.
	 * @return array
	 */
	public static function apply_stored_translation( $lesson, $lesson_id ) {
		unset( $lesson_id );
		if ( ! HWBL_CPT_Lesson::is_valid_lesson_data( $lesson ) ) {
			return is_array( $lesson ) ? $lesson : array();
		}

		$locale = substr( get_locale(), 0, 2 );
		$stored = get_post_meta( $lesson['id'], '_hwbl_translation_' . $locale, true );
		if ( ! $stored ) {
			return $lesson;
		}
		$data = json_decode( $stored, true );
		if ( ! is_array( $data ) ) {
			return $lesson;
		}
		if ( ! empty( $data['historical_context'] ) ) {
			$lesson['historical_context'] = $data['historical_context'];
		}
		if ( ! empty( $data['preceding_narrative'] ) ) {
			$lesson['preceding_narrative'] = $data['preceding_narrative'];
		}
		if ( ! empty( $data['discussion_questions'] ) && is_array( $data['discussion_questions'] ) ) {
			$lesson['discussion_questions'] = $data['discussion_questions'];
		}
		return $lesson;
	}
}
