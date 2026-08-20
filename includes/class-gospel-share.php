<?php
/**
 * Public share page for shareable gospel plans (/gospel/{id}).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Gospel_Share
 */
class HWBL_Gospel_Share {

	const OPT_REWRITE = 'hwbl_gospel_rewrite_flushed';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ) );
	}

	/**
	 * Rewrite.
	 */
	public static function register_rewrite() {
		add_rewrite_rule( '^gospel/([0-9]+)/?$', 'index.php?hwbl_gospel_share=$matches[1]', 'top' );
		if ( (string) get_option( self::OPT_REWRITE ) !== '1' ) {
			flush_rewrite_rules( false );
			update_option( self::OPT_REWRITE, '1', false );
		}
	}

	/**
	 * @param array<int, string> $vars Vars.
	 * @return array<int, string>
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'hwbl_gospel_share';
		return $vars;
	}

	/**
	 * Share URL for a plan.
	 *
	 * @param int $plan_id Plan ID.
	 * @return string
	 */
	public static function share_url( $plan_id ) {
		return trailingslashit( home_url( '/gospel/' . (int) $plan_id ) );
	}

	/**
	 * Render public gospel HTML.
	 */
	public static function maybe_render() {
		$id = (int) get_query_var( 'hwbl_gospel_share' );
		if ( $id < 1 ) {
			return;
		}
		$plan = HWBL_CPT_Plan::get_plan_data( $id );
		if ( ! $plan || empty( $plan['shareable'] ) || 'publish' !== ( $plan['status'] ?? '' ) ) {
			status_header( 404 );
			nocache_headers();
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Not found</title></head><body><p>This gospel presentation is not available.</p></body></html>';
			exit;
		}

		if ( class_exists( 'HWBL_Gospel_Response' ) ) {
			HWBL_Gospel_Response::record_view( $id );
		}

		$site      = get_bloginfo( 'name' );
		$primary   = (string) get_option( 'hwbl_app_primary_color', '#1a365d' );
		$rest_url  = esc_url_raw( rest_url( 'hwbl/v1/gospel/' . $id . '/respond' ) );
		$share_url = self::share_url( $id );
		$wa        = class_exists( 'HWBL_Share_Links' ) ? HWBL_Share_Links::whatsapp_url( $share_url, $plan['title'] ) : '';
		$sms       = class_exists( 'HWBL_Share_Links' ) ? HWBL_Share_Links::sms_url( $share_url, $plan['title'] ) : '';

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8" />';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
		echo '<meta name="theme-color" content="' . esc_attr( $primary ?: '#1a365d' ) . '" />';
		echo '<title>' . esc_html( $plan['title'] ) . ' — ' . esc_html( $site ) . '</title>';
		echo '<style>
			body{font-family:Georgia,serif;margin:0;background:#f7f5f0;color:#1a1a1a;line-height:1.55}
			.wrap{max-width:40rem;margin:0 auto;padding:2rem 1.25rem 3rem}
			h1{font-size:1.85rem;color:' . esc_attr( $primary ?: '#1a365d' ) . ';margin:0 0 .5rem}
			.intro{opacity:.8;margin-bottom:1.75rem}
			.step{border-top:1px solid rgba(0,0,0,.1);padding:1.25rem 0}
			.step h2{font-size:1.15rem;margin:0 0 .35rem}
			.ref{font-weight:700;color:' . esc_attr( $primary ?: '#1a365d' ) . ';margin:0 0 .5rem}
			.cta,.btn{display:inline-block;margin:.35rem .35rem 0 0;padding:.75rem 1.1rem;background:' . esc_attr( $primary ?: '#1a365d' ) . ';color:#fff;text-decoration:none;border-radius:6px;border:0;cursor:pointer;font:inherit}
			.btn-secondary{background:transparent;color:' . esc_attr( $primary ?: '#1a365d' ) . ';border:1px solid currentColor}
			.response{margin-top:2rem;padding-top:1.5rem;border-top:2px solid ' . esc_attr( $primary ?: '#1a365d' ) . '}
			.response label{display:block;margin:.75rem 0 .25rem}
			.response input[type=email],.response input[type=text]{width:100%;padding:.55rem;font:inherit;box-sizing:border-box}
			.response .notice{font-size:.92rem;opacity:.9;margin:1rem 0 .5rem;line-height:1.45}
			.response .consent{display:flex;gap:.5rem;align-items:flex-start;margin:1rem 0;font-size:.92rem;line-height:1.4}
			.response .consent input{margin-top:.2rem}
			.response .choices{display:grid;gap:.5rem;margin:1rem 0}
			.response .choice{display:block;width:100%;text-align:left}
			#hwbl-gospel-thanks{display:none;margin-top:1rem}
			.share-row{margin:1.25rem 0;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center}
			#hwbl-qr{margin-top:1rem}
			.church{margin:.75rem 0;padding:.75rem;border:1px solid rgba(0,0,0,.12)}
		</style></head><body><div class="wrap">';
		echo '<h1>' . esc_html( $plan['title'] ) . '</h1>';
		if ( ! empty( $plan['excerpt'] ) ) {
			echo '<p class="intro">' . esc_html( $plan['excerpt'] ) . '</p>';
		}
		foreach ( (array) $plan['days'] as $day ) {
			echo '<section class="step">';
			echo '<h2>' . esc_html( sprintf( /* translators: %d day */ __( 'Step %d', 'hidden-word-bible-lessons' ), (int) $day['day'] ) );
			if ( ! empty( $day['title'] ) ) {
				echo ': ' . esc_html( (string) $day['title'] );
			}
			echo '</h2>';
			if ( ! empty( $day['verse_ref'] ) ) {
				echo '<p class="ref">' . esc_html( (string) $day['verse_ref'] ) . '</p>';
			}
			if ( ! empty( $day['body'] ) ) {
				echo '<div>' . wp_kses_post( $day['body'] ) . '</div>';
			}
			echo '</section>';
		}

		$privacy_url = esc_url( 'https://thehiddenword.org/privacy-policy' );
		echo '<section class="response" id="hwbl-gospel-response">';
		echo '<h2>' . esc_html__( 'How will you respond?', 'hidden-word-bible-lessons' ) . '</h2>';
		echo '<p>' . esc_html__( 'No account required. Choose one option below.', 'hidden-word-bible-lessons' ) . '</p>';
		echo '<label for="hwbl-gospel-email">' . esc_html__( 'Email for next steps (optional)', 'hidden-word-bible-lessons' ) . '</label>';
		echo '<input type="email" id="hwbl-gospel-email" autocomplete="email" />';
		echo '<label for="hwbl-gospel-location">' . esc_html__( 'City or ZIP to find a local church (optional)', 'hidden-word-bible-lessons' ) . '</label>';
		echo '<input type="text" id="hwbl-gospel-location" autocomplete="postal-code" />';
		echo '<p class="notice">' . wp_kses(
			sprintf(
				/* translators: %s: privacy policy URL */
				__( 'Email and location are optional. If provided, they are used so site administrators can follow up and suggest nearby churches. Your response (and any contact details) goes to this site’s administrators, and may also go to a matched church contact if one is found. See our <a href="%s" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.', 'hidden-word-bible-lessons' ),
				$privacy_url
			),
			array(
				'a' => array(
					'href'   => true,
					'target' => true,
					'rel'    => true,
				),
			)
		) . '</p>';
		echo '<label class="consent" for="hwbl-gospel-consent">';
		echo '<input type="checkbox" id="hwbl-gospel-consent" value="1" />';
		echo '<span>' . esc_html__( 'I agree to submit this response and understand how my information may be used as described above.', 'hidden-word-bible-lessons' ) . '</span>';
		echo '</label>';
		echo '<div class="choices">';
		echo '<button type="button" class="btn choice" data-choice="follow">' . esc_html__( 'I want to follow Jesus', 'hidden-word-bible-lessons' ) . '</button>';
		echo '<button type="button" class="btn btn-secondary choice" data-choice="questions">' . esc_html__( 'I have questions', 'hidden-word-bible-lessons' ) . '</button>';
		echo '<button type="button" class="btn btn-secondary choice" data-choice="already">' . esc_html__( 'I already follow Jesus', 'hidden-word-bible-lessons' ) . '</button>';
		echo '</div>';
		echo '<p id="hwbl-gospel-status" role="status" aria-live="polite"></p>';
		echo '<div id="hwbl-gospel-thanks"></div>';
		echo '</section>';

		echo '<div class="share-row">';
		if ( $wa ) {
			// esc_attr: esc_url() strips %0A from wa.me / sms bodies.
			echo '<a class="btn btn-secondary" href="' . esc_attr( $wa ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Share on WhatsApp', 'hidden-word-bible-lessons' ) . '</a>';
		}
		if ( $sms ) {
			echo '<a class="btn btn-secondary" href="' . esc_attr( $sms ) . '">' . esc_html__( 'Share by SMS', 'hidden-word-bible-lessons' ) . '</a>';
		}
		echo '</div>';
		echo '<canvas id="hwbl-qr" width="160" height="160" aria-label="' . esc_attr__( 'QR code for this page', 'hidden-word-bible-lessons' ) . '"></canvas>';

		$connect = class_exists( 'HWBL_App_Connect' ) ? HWBL_App_Connect::connect_url() : home_url( '/' );
		echo '<p style="margin-top:1.5rem"><a class="cta" href="' . esc_url( $connect ) . '">' . esc_html__( 'Continue with The Hidden Word', 'hidden-word-bible-lessons' ) . '</a></p>';
		echo '</div>';
		echo '<script>window.hwblGospelRespond=' . wp_json_encode(
			array(
				'restUrl'     => $rest_url,
				'shareUrl'    => $share_url,
				'title'       => $plan['title'],
				'privacyUrl'  => 'https://thehiddenword.org/privacy-policy',
				'consentRequired' => __( 'Please check the consent box before submitting.', 'hidden-word-bible-lessons' ),
			)
		) . ';</script>';
		echo '<script src="' . esc_url( HWBL_PLUGIN_URL . 'public/js/qrcode-mini.js' ) . '?v=' . esc_attr( HWBL_VERSION ) . '"></script>';
		echo '<script src="' . esc_url( HWBL_PLUGIN_URL . 'public/js/gospel-respond.js' ) . '?v=' . esc_attr( HWBL_VERSION ) . '"></script>';
		if ( class_exists( 'HWBL_PWA' ) ) {
			echo HWBL_PWA::standalone_register_script(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</body></html>';
		exit;
	}
}
