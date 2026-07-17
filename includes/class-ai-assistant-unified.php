<?php
/**
 * Unified AI assistant (Explain, Study, Ask).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_AI_Assistant_Unified
 */
class HWBL_AI_Assistant_Unified {

	/**
	 * Initialize shortcode and REST shell for threaded assistant UI.
	 */
	public static function init() {
		if ( ! hwbl_is_ai_enabled() && ! hwbl_premium_features_enabled() ) {
			return;
		}

		add_shortcode( 'hwbl_ai_assistant', array( __CLASS__, 'render_shortcode' ) );
		add_shortcode( 'thw_ai_assistant', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assistant assets when shortcode is on the page.
	 */
	public static function enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}
		global $post;
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		if ( ! has_shortcode( $post->post_content, 'hwbl_ai_assistant' ) && ! has_shortcode( $post->post_content, 'thw_ai_assistant' ) ) {
			return;
		}

		wp_enqueue_style( 'hwbl-lesson' );
		wp_enqueue_script(
			'hwbl-ai-assistant',
			HWBL_PLUGIN_URL . 'public/js/ai-assistant.js',
			array(),
			HWBL_VERSION,
			true
		);
		wp_localize_script(
			'hwbl-ai-assistant',
			'hwblAiAssistant',
			array(
				'restUrl' => rest_url( 'hwbl/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'placeholder' => __( 'Ask about a passage, topic, or lesson…', 'hidden-word-bible-lessons' ),
					'send'        => __( 'Send', 'hidden-word-bible-lessons' ),
					'thinking'    => __( 'Thinking…', 'hidden-word-bible-lessons' ),
				),
			)
		);
	}

	/**
	 * Register assistant session route.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/ai/assistant',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_assistant' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}

	/**
	 * Route assistant prompts to existing Premium AI modules.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_assistant( $request ) {
		$params  = $request->get_json_params();
		$message = is_array( $params ) && ! empty( $params['message'] ) ? sanitize_text_field( (string) $params['message'] ) : '';
		$mode    = is_array( $params ) && ! empty( $params['mode'] ) ? sanitize_key( (string) $params['mode'] ) : 'study';

		if ( '' === $message ) {
			return new WP_REST_Response( array( 'error' => 'empty_message' ), 400 );
		}

		if ( 'study' === $mode && class_exists( 'THW_Premium_AI_Study_Finder' ) ) {
			$fake = new WP_REST_Request( 'POST', '/hwbl/v1/ai/assistant' );
			$fake->set_body_params(
				array(
					'keywords' => $message,
					'limit'    => 5,
				)
			);
			$result = THW_Premium_AI_Study_Finder::rest_study_search( $fake );
			if ( $result instanceof WP_REST_Response ) {
				$data = $result->get_data();
				return new WP_REST_Response(
					array(
						'mode'     => 'study',
						'message'  => $message,
						'response' => isset( $data['html'] ) ? $data['html'] : '',
						'raw'      => $data,
					)
				);
			}
		}

		return new WP_REST_Response(
			array(
				'mode'    => $mode,
				'message' => $message,
				'response'=> __( 'Use Study mode for topic search, or open a lesson for verse Explain.', 'hidden-word-bible-lessons' ),
			)
		);
	}

	/**
	 * Render assistant UI.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p class="hwbl-notice">' . esc_html__( 'Sign in to use the Bible study assistant.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		ob_start();
		?>
		<div class="hwbl-ai-assistant" data-hwbl-ai-assistant>
			<div class="hwbl-ai-assistant__modes" role="tablist">
				<button type="button" class="hwbl-btn hwbl-btn-secondary is-active" data-mode="study" role="tab" aria-selected="true"><?php esc_html_e( 'Study', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="hwbl-btn hwbl-btn-secondary" data-mode="explain" role="tab" aria-selected="false"><?php esc_html_e( 'Explain', 'hidden-word-bible-lessons' ); ?></button>
			</div>
			<div class="hwbl-ai-assistant__thread" role="log" aria-live="polite"></div>
			<form class="hwbl-ai-assistant__form">
				<label class="screen-reader-text" for="hwbl-ai-assistant-input"><?php esc_html_e( 'Assistant message', 'hidden-word-bible-lessons' ); ?></label>
				<input type="text" id="hwbl-ai-assistant-input" class="hwbl-ai-assistant__input" placeholder="<?php esc_attr_e( 'Ask about a passage, topic, or lesson…', 'hidden-word-bible-lessons' ); ?>" required />
				<button type="submit" class="hwbl-btn"><?php esc_html_e( 'Send', 'hidden-word-bible-lessons' ); ?></button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
