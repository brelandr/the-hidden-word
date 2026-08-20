<?php
/**
 * Cross-channel share URL helpers (WhatsApp, SMS) + share tools shortcode.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Share_Links
 */
class HWBL_Share_Links {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_shortcode( 'hwbl_share_tools', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Build WhatsApp share URL.
	 *
	 * @param string $url   Page URL.
	 * @param string $title Optional title/preface.
	 * @return string
	 */
	public static function whatsapp_url( $url, $title = '' ) {
		$msg = self::compose_message( $url, $title );
		return 'https://wa.me/?text=' . rawurlencode( $msg );
	}

	/**
	 * Build SMS share URL (iOS + Android friendly).
	 *
	 * @param string $url   Page URL.
	 * @param string $title Optional title/preface.
	 * @return string
	 */
	public static function sms_url( $url, $title = '' ) {
		$msg = self::compose_message( $url, $title );
		// `sms:?&body=` works on iOS; Android commonly accepts `sms:?body=`.
		return 'sms:?&body=' . rawurlencode( $msg );
	}

	/**
	 * Compose share body from optional title + URL.
	 *
	 * @param string $url   URL (may be empty when title already includes body).
	 * @param string $title Title or full message.
	 * @return string
	 */
	private static function compose_message( $url, $title = '' ) {
		$text = trim( (string) $title );
		$url  = trim( (string) $url );
		if ( $text && $url ) {
			return $text . "\n" . $url;
		}
		return $text ? $text : $url;
	}

	/**
	 * Enqueue QR helper on pages that include the shortcode or share templates.
	 */
	public static function maybe_enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		if ( false === strpos( (string) $post->post_content, '[hwbl_share_tools' ) ) {
			return;
		}
		wp_enqueue_script(
			'hwbl-qrcode',
			HWBL_PLUGIN_URL . 'public/js/qrcode-mini.js',
			array(),
			HWBL_VERSION,
			true
		);
		wp_enqueue_script(
			'hwbl-share-tools',
			HWBL_PLUGIN_URL . 'public/js/share-tools.js',
			array( 'hwbl-qrcode' ),
			HWBL_VERSION,
			true
		);
	}

	/**
	 * Shortcode: [hwbl_share_tools url="..." title="..."]
	 *
	 * @param array<string,string>|string $atts Attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'   => '',
				'title' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'hwbl_share_tools'
		);
		$url = esc_url_raw( (string) $atts['url'] );
		if ( ! $url ) {
			$url = home_url( '/' );
		}
		$title = sanitize_text_field( (string) $atts['title'] );
		$wa    = self::whatsapp_url( $url, $title );
		$sms   = self::sms_url( $url, $title );
		$uid   = 'hwbl-qr-' . wp_unique_id();

		ob_start();
		echo '<div class="hwbl-share-tools" data-share-url="' . esc_attr( $url ) . '">';
		echo '<p class="hwbl-share-tools__actions">';
		// esc_attr keeps %0A in encoded share bodies (esc_url strips it).
		echo '<a class="button" href="' . esc_attr( $wa ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'WhatsApp', 'hidden-word-bible-lessons' ) . '</a> ';
		echo '<a class="button" href="' . esc_attr( $sms ) . '">' . esc_html__( 'SMS', 'hidden-word-bible-lessons' ) . '</a>';
		echo '</p>';
		echo '<canvas id="' . esc_attr( $uid ) . '" class="hwbl-share-tools__qr" width="160" height="160" data-hwbl-qr="' . esc_attr( $url ) . '" aria-label="' . esc_attr__( 'QR code', 'hidden-word-bible-lessons' ) . '"></canvas>';
		echo '<p><code>' . esc_html( $url ) . '</code></p>';
		echo '</div>';
		return (string) ob_get_clean();
	}
}
