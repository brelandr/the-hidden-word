<?php
/**
 * Side-by-side translation comparison view (Phase 5).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Translation_Comparison
 */
class HWBL_Translation_Comparison {

	/**
	 * Initialize shortcode and assets.
	 */
	public static function init() {
		add_shortcode( 'hwbl_translation_compare', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue comparison script when shortcode is present.
	 */
	public static function enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! $post instanceof WP_Post || ! has_shortcode( $post->post_content, 'hwbl_translation_compare' ) ) {
			return;
		}

		wp_enqueue_script(
			'hwbl-translation-compare',
			HWBL_PLUGIN_URL . 'public/js/translation-compare.js',
			array(),
			HWBL_VERSION,
			true
		);

		wp_localize_script(
			'hwbl-translation-compare',
			'hwblTranslationCompare',
			array(
				'restUrl' => rest_url( 'hwbl/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Render parallel translation columns shell.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'left'  => 'kjv',
				'right' => 'web',
				'ref'   => '',
			),
			$atts,
			'hwbl_translation_compare'
		);

		return sprintf(
			'<div class="hwbl-translation-compare" data-left="%1$s" data-right="%2$s" data-ref="%3$s"><div class="hwbl-translation-compare__col" data-col="left"></div><div class="hwbl-translation-compare__col" data-col="right"></div></div>',
			esc_attr( sanitize_key( $atts['left'] ) ),
			esc_attr( sanitize_key( $atts['right'] ) ),
			esc_attr( sanitize_text_field( $atts['ref'] ) )
		);
	}
}
