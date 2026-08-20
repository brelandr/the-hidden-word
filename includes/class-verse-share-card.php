<?php
/**
 * Client-side verse share card assets + button markup.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Verse_Share_Card
 */
class HWBL_Verse_Share_Card {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register assets.
	 */
	public static function register_assets() {
		wp_register_style(
			'hwbl-verse-share-card',
			HWBL_PLUGIN_URL . 'public/css/verse-share-card.css',
			array(),
			HWBL_VERSION
		);
		wp_register_script(
			'hwbl-verse-share-card',
			HWBL_PLUGIN_URL . 'public/js/verse-share-card.js',
			array(),
			HWBL_VERSION,
			true
		);
	}

	/**
	 * Enqueue + localize brand colors.
	 */
	public static function enqueue() {
		self::register_assets();
		wp_enqueue_style( 'hwbl-verse-share-card' );
		wp_enqueue_script( 'hwbl-verse-share-card' );
		wp_localize_script(
			'hwbl-verse-share-card',
			'hwblVerseShare',
			array(
				'primaryColor'   => (string) get_option( 'hwbl_app_primary_color', '#1a365d' ),
				'secondaryColor' => (string) get_option( 'hwbl_app_secondary_color', '#c9a227' ),
				'siteName'       => (string) get_bloginfo( 'name' ),
			)
		);
	}

	/**
	 * Share button HTML.
	 *
	 * @param string $verse_text Verse text.
	 * @param string $reference  Reference.
	 * @return string
	 */
	public static function button_html( $verse_text, $reference = '' ) {
		if ( ! $verse_text ) {
			return '';
		}
		self::enqueue();
		$text = trim( $reference ? $reference . "\n" . $verse_text : $verse_text );
		$text = $text . "\n" . home_url( '/' );
		$wa   = class_exists( 'HWBL_Share_Links' ) ? HWBL_Share_Links::whatsapp_url( '', $text ) : '';
		$sms  = class_exists( 'HWBL_Share_Links' ) ? HWBL_Share_Links::sms_url( '', $text ) : '';
		$html = sprintf(
			'<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-share-verse-btn" data-hwbl-share-verse="1" data-verse="%1$s" data-ref="%2$s">%3$s</button>',
			esc_attr( $verse_text ),
			esc_attr( $reference ),
			esc_html__( 'Share as image', 'hidden-word-bible-lessons' )
		);
		if ( $wa ) {
			$html .= sprintf(
				' <a class="hwbl-btn hwbl-btn-secondary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_attr( $wa ),
				esc_html__( 'WhatsApp', 'hidden-word-bible-lessons' )
			);
		}
		if ( $sms ) {
			$html .= sprintf(
				' <a class="hwbl-btn hwbl-btn-secondary" href="%1$s">%2$s</a>',
				esc_attr( $sms ),
				esc_html__( 'SMS', 'hidden-word-bible-lessons' )
			);
		}
		return $html;
	}
}
