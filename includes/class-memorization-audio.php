<?php
/**
 * Memorization widget audio via Hello AO / Bible Reader.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Memorization_Audio
 */
class HWBL_Memorization_Audio {

	/**
	 * Initialize memorization audio hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register audio REST route.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/memorize/audio',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_audio' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'book_id'     => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'chapter'     => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'translation' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => 'kjv',
					),
				),
			)
		);
	}

	/**
	 * GET audio URLs for a chapter.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_audio( $request ) {
		$book_id     = max( 1, (int) $request['book_id'] );
		$chapter     = max( 1, (int) $request['chapter'] );
		$translation = sanitize_key( (string) $request['translation'] );

		if ( ! class_exists( 'HWBL_HelloAO_Provider' ) ) {
			return new WP_REST_Response( array( 'audio' => array() ) );
		}

		$payload = HWBL_HelloAO_Provider::get_chapter_payload( $book_id, $chapter, $translation );
		$audio   = is_array( $payload ) && ! empty( $payload['audio'] ) ? $payload['audio'] : array();

		return new WP_REST_Response(
			array(
				'audio' => $audio,
			)
		);
	}

	/**
	 * Render listen button markup for a lesson.
	 *
	 * @param int                  $lesson_id Lesson post ID.
	 * @param array<string, mixed> $lesson    Lesson data.
	 * @return string
	 */
	public static function render_audio_button( $lesson_id, $lesson ) {
		if ( ! is_array( $lesson ) || empty( $lesson['book_id'] ) || empty( $lesson['chapter'] ) ) {
			return '';
		}

		$translation = get_option( 'hwbl_active_translation', 'kjv' );

		return sprintf(
			'<button type="button" class="hwbl-btn hwbl-memorization-audio" data-book-id="%1$d" data-chapter="%2$d" data-translation="%3$s" aria-label="%4$s">%5$s</button><audio class="hwbl-memorization-audio-player" controls hidden></audio>',
			(int) $lesson['book_id'],
			(int) $lesson['chapter'],
			esc_attr( sanitize_key( (string) $translation ) ),
			esc_attr__( 'Listen to verse audio', 'hidden-word-bible-lessons' ),
			esc_html__( 'Listen to verse', 'hidden-word-bible-lessons' )
		);
	}
}
