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
	 * Translations known to often include Hello AO chapter audio.
	 *
	 * @var string[]
	 */
	private static $audio_fallback_order = array( 'kjv', 'web', 'bsb', 'asv' );

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
	 * Resolve chapter audio, falling back across Hello AO-capable translations.
	 *
	 * Site default is often NIV (bundled, no Hello AO audio). Fall back to KJV/WEB/BSB.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param string $translation Preferred translation slug.
	 * @return array{audio:array<string,string>,translation:string,message:string}
	 */
	public static function resolve_chapter_audio( $book_id, $chapter, $translation ) {
		$book_id     = max( 1, (int) $book_id );
		$chapter     = max( 1, (int) $chapter );
		$translation = sanitize_key( (string) $translation );

		if ( ! class_exists( 'HWBL_HelloAO_Provider' ) || ! HWBL_HelloAO_Provider::is_enabled() ) {
			return array(
				'audio'       => array(),
				'translation' => $translation,
				'message'     => __( 'Bible audio is unavailable. Enable Hello AO under Bible Lessons → Settings.', 'hidden-word-bible-lessons' ),
			);
		}

		$candidates = array_values(
			array_unique(
				array_filter(
					array_merge(
						array( $translation ),
						self::$audio_fallback_order
					)
				)
			)
		);

		foreach ( $candidates as $slug ) {
			if ( ! HWBL_HelloAO_Provider::get_helloao_id( $slug ) ) {
				continue;
			}

			$payload = HWBL_HelloAO_Provider::get_chapter_payload( $book_id, $chapter, $slug );
			$audio   = is_array( $payload ) && ! empty( $payload['audio'] ) && is_array( $payload['audio'] )
				? $payload['audio']
				: array();

			if ( empty( $audio ) ) {
				continue;
			}

			$message = '';
			if ( $slug !== $translation && $translation ) {
				$message = sprintf(
					/* translators: 1: requested translation slug, 2: audio translation slug */
					__( 'No audio for %1$s — playing the chapter in %2$s.', 'hidden-word-bible-lessons' ),
					strtoupper( $translation ),
					strtoupper( $slug )
				);
			}

			return array(
				'audio'       => $audio,
				'translation' => $slug,
				'message'     => $message,
			);
		}

		return array(
			'audio'       => array(),
			'translation' => $translation,
			'message'     => __( 'No chapter audio is available for this passage right now.', 'hidden-word-bible-lessons' ),
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

		$resolved = self::resolve_chapter_audio( $book_id, $chapter, $translation );
		$narrator = class_exists( 'HWBL_Bible_Reader' )
			? HWBL_Bible_Reader::get_default_narrator()
			: 'david';

		return new WP_REST_Response(
			array(
				'audio'       => $resolved['audio'],
				'translation' => $resolved['translation'],
				'narrator'    => $narrator,
				'message'     => $resolved['message'],
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
		unset( $lesson_id );

		if ( ! is_array( $lesson ) || empty( $lesson['book_id'] ) || empty( $lesson['chapter'] ) ) {
			return '';
		}

		$translation = sanitize_key( (string) get_option( 'hwbl_active_translation', 'kjv' ) );
		// Prefer an audio-capable Hello AO translation for the button default.
		if ( class_exists( 'HWBL_HelloAO_Provider' ) && ! HWBL_HelloAO_Provider::get_helloao_id( $translation ) ) {
			$translation = 'kjv';
		}

		return sprintf(
			'<div class="hwbl-memorization-audio-wrap">'
			. '<button type="button" class="hwbl-btn hwbl-memorization-audio" data-book-id="%1$d" data-chapter="%2$d" data-translation="%3$s" aria-label="%4$s">%5$s</button>'
			. '<audio class="hwbl-memorization-audio-player" controls preload="none" hidden></audio>'
			. '<p class="hwbl-memorization-audio-status" role="status" aria-live="polite" hidden></p>'
			. '</div>',
			(int) $lesson['book_id'],
			(int) $lesson['chapter'],
			esc_attr( $translation ),
			esc_attr__( 'Listen to chapter audio for this verse', 'hidden-word-bible-lessons' ),
			esc_html__( 'Listen to verse', 'hidden-word-bible-lessons' )
		);
	}
}
