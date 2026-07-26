<?php
/**
 * Daily email for Bible.com Verse of the Day + full explanation.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_Votd_Digest
 */
class THW_Premium_Votd_Digest {

	const SUBSCRIBERS_OPTION = 'thw_votd_digest_subscribers';
	const CRON_HOOK          = 'thw_premium_votd_digest';
	const LAST_DAY_OPTION    = 'thw_votd_digest_last_day';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		thw_premium_register_shortcode( 'thw_votd_subscribe', array( __CLASS__, 'render_subscribe_form' ) );
		add_action( 'admin_post_thw_votd_digest_subscribe', array( __CLASS__, 'handle_subscribe' ) );
		add_action( 'admin_post_nopriv_thw_votd_digest_subscribe', array( __CLASS__, 'handle_subscribe' ) );
		add_action( 'admin_post_thw_votd_digest_confirm', array( __CLASS__, 'handle_confirm' ) );
		add_action( 'admin_post_nopriv_thw_votd_digest_confirm', array( __CLASS__, 'handle_confirm' ) );
		add_action( 'admin_post_thw_votd_digest_unsubscribe', array( __CLASS__, 'handle_unsubscribe' ) );
		add_action( 'admin_post_nopriv_thw_votd_digest_unsubscribe', array( __CLASS__, 'handle_unsubscribe' ) );
		add_action( 'init', array( __CLASS__, 'schedule_cron' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'send_digest' ) );
		add_filter( 'the_content', array( __CLASS__, 'prepend_subscribe_notices' ) );
	}

	/**
	 * Whether the daily VOTD email feature is enabled in admin.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) get_option( 'thw_votd_digest_enabled', false );
	}

	/**
	 * Show subscribe / confirm / unsubscribe notices above content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function prepend_subscribe_notices( $content ) {
		global $post;
		if ( ! $post || ! has_shortcode( $post->post_content, 'thw_votd_subscribe' ) ) {
			return $content;
		}

		$notice = self::get_status_notice();
		return $notice ? $notice . $content : $content;
	}

	/**
	 * Build front-end status notice from query args.
	 *
	 * @return string
	 */
	public static function get_status_notice() {
		if ( isset( $_GET['thw_votd_subscribed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<p class="thw-notice thw-notice-success">' . esc_html__( 'Check your email to confirm your Verse of the Day subscription.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		if ( isset( $_GET['thw_votd_confirmed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<p class="thw-notice thw-notice-success">' . esc_html__( 'Your Verse of the Day email subscription is confirmed.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		if ( isset( $_GET['thw_votd_unsubscribed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<p class="thw-notice thw-notice-info">' . esc_html__( 'You have been unsubscribed from Verse of the Day emails.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		return '';
	}

	/**
	 * Schedule daily VOTD digest cron.
	 */
	public static function schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Get subscribers.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_subscribers() {
		$data = get_option( self::SUBSCRIBERS_OPTION, array() );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Save subscribers.
	 *
	 * @param array<int, array<string, string>> $subscribers Subscribers.
	 */
	public static function save_subscribers( $subscribers ) {
		update_option( self::SUBSCRIBERS_OPTION, array_values( $subscribers ), false );
	}

	/**
	 * Translation choices for the signup form.
	 *
	 * @return array<string, string> slug => label
	 */
	public static function get_translation_choices() {
		$choices = array();
		if ( class_exists( 'HWBL_Translation_Service' ) ) {
			$supported = HWBL_Translation_Service::instance()->get_supported_translations();
			if ( is_array( $supported ) ) {
				foreach ( $supported as $slug => $label ) {
					$slug = sanitize_key( (string) $slug );
					if ( '' === $slug ) {
						continue;
					}
					$choices[ $slug ] = is_string( $label ) ? $label : strtoupper( $slug );
				}
			}
		}
		if ( empty( $choices ) ) {
			$choices['niv'] = 'NIV';
			$choices['kjv'] = 'KJV';
			$choices['web'] = 'WEB';
		}
		return $choices;
	}

	/**
	 * Default translation for new subscribers.
	 *
	 * @return string
	 */
	public static function get_default_translation() {
		$override = sanitize_key( (string) get_option( 'thw_votd_translation', '' ) );
		if ( '' !== $override ) {
			return $override;
		}
		return sanitize_key( (string) get_option( 'hwbl_active_translation', 'niv' ) );
	}

	/**
	 * Render subscribe shortcode.
	 *
	 * @return string
	 */
	public static function render_subscribe_form() {
		if ( ! self::is_enabled() ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return '';
			}
			return '<p class="thw-notice">' . esc_html__( 'Verse of the Day email signup is disabled in Premium settings.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$choices   = self::get_translation_choices();
		$default   = self::get_default_translation();

		$html  = '<form class="thw-subscribe-form thw-votd-subscribe-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		$html .= '<input type="hidden" name="action" value="thw_votd_digest_subscribe" />';
		$html .= wp_nonce_field( 'thw_votd_digest_subscribe', '_wpnonce', true, false );
		$html .= '<p class="thw-votd-subscribe-intro">' . esc_html__( 'Get the Bible.com Verse of the Day and a full explanation emailed to you each day.', 'hidden-word-bible-lessons' ) . '</p>';
		$html .= '<label for="thw_votd_digest_email">' . esc_html__( 'Email address', 'hidden-word-bible-lessons' ) . '</label>';
		$html .= '<input type="email" id="thw_votd_digest_email" name="email" required class="widefat" />';
		$html .= '<label for="thw_votd_digest_translation">' . esc_html__( 'Bible translation', 'hidden-word-bible-lessons' ) . '</label>';
		$html .= '<select id="thw_votd_digest_translation" name="translation" class="widefat">';
		foreach ( $choices as $slug => $label ) {
			$html .= '<option value="' . esc_attr( $slug ) . '" ' . selected( $default, $slug, false ) . '>' . esc_html( $label ) . '</option>';
		}
		$html .= '</select>';
		$html .= '<button type="submit" class="thw-btn">' . esc_html__( 'Subscribe to Verse of the Day emails', 'hidden-word-bible-lessons' ) . '</button>';
		$html .= '</form>';
		return $html;
	}

	/**
	 * Handle subscription request.
	 */
	public static function handle_subscribe() {
		check_admin_referer( 'thw_votd_digest_subscribe' );

		if ( ! self::is_enabled() ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
			exit;
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
			exit;
		}

		$choices     = self::get_translation_choices();
		$translation = isset( $_POST['translation'] ) ? sanitize_key( wp_unslash( $_POST['translation'] ) ) : '';
		if ( '' === $translation || ! isset( $choices[ $translation ] ) ) {
			$translation = self::get_default_translation();
		}

		$token = wp_generate_password( 32, false );
		$subs  = self::get_subscribers();

		// Replace an existing pending/confirmed row for the same email.
		$subs = array_values(
			array_filter(
				$subs,
				static function ( $row ) use ( $email ) {
					return empty( $row['email'] ) || $row['email'] !== $email;
				}
			)
		);

		$subs[] = array(
			'email'         => $email,
			'status'        => 'pending',
			'token'         => $token,
			'translation'   => $translation,
			'subscribed_at' => current_time( 'mysql' ),
		);
		self::save_subscribers( $subs );

		$confirm_url = add_query_arg(
			array(
				'action' => 'thw_votd_digest_confirm',
				'email'  => $email,
				'token'  => $token,
			),
			admin_url( 'admin-post.php' )
		);

		wp_mail(
			$email,
			/* translators: %s: site name */
			sprintf( __( 'Confirm your Verse of the Day subscription to %s', 'hidden-word-bible-lessons' ), get_bloginfo( 'name' ) ),
			sprintf(
				/* translators: %s: confirmation URL */
				__( "Please confirm your daily Bible.com Verse of the Day email subscription:\n\n%s\n", 'hidden-word-bible-lessons' ),
				$confirm_url
			)
		);

		wp_safe_redirect( add_query_arg( 'thw_votd_subscribed', '1', wp_get_referer() ? wp_get_referer() : home_url( '/' ) ) );
		exit;
	}

	/**
	 * Confirm subscription.
	 */
	public static function handle_confirm() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Email confirmation links authenticate via one-time token + hash_equals.
		$email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$subs = self::get_subscribers();
		foreach ( $subs as &$row ) {
			if ( $row['email'] === $email && isset( $row['token'] ) && hash_equals( $row['token'], $token ) ) {
				$row['status'] = 'confirmed';
				$row['token']  = wp_generate_password( 16, false );
			}
		}
		unset( $row );
		self::save_subscribers( $subs );

		wp_safe_redirect( add_query_arg( 'thw_votd_confirmed', '1', home_url( '/' ) ) );
		exit;
	}

	/**
	 * Unsubscribe link handler.
	 */
	public static function handle_unsubscribe() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Unsubscribe links authenticate via one-time token + hash_equals.
		$email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$subs = array_values(
			array_filter(
				self::get_subscribers(),
				static function ( $row ) use ( $email, $token ) {
					if ( empty( $row['email'] ) || $row['email'] !== $email ) {
						return true;
					}
					return ! ( isset( $row['token'] ) && hash_equals( $row['token'], $token ) );
				}
			)
		);
		self::save_subscribers( $subs );

		wp_safe_redirect( add_query_arg( 'thw_votd_unsubscribed', '1', home_url( '/' ) ) );
		exit;
	}

	/**
	 * Whether digest should send today (once per calendar day when enabled).
	 *
	 * @return bool
	 */
	public static function should_send_today() {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$day  = wp_date( 'Y-m-d' );
		$last = (string) get_option( self::LAST_DAY_OPTION, '' );
		return $last !== $day;
	}

	/**
	 * Cron callback: send VOTD emails.
	 */
	public static function send_digest() {
		if ( ! self::should_send_today() ) {
			return;
		}

		$confirmed = array_values(
			array_filter(
				self::get_subscribers(),
				static function ( $row ) {
					return ! empty( $row['status'] ) && 'confirmed' === $row['status'] && ! empty( $row['email'] ) && ! empty( $row['token'] );
				}
			)
		);
		if ( empty( $confirmed ) ) {
			return;
		}

		// Mark the day before sending so overlapping cron runs do not double-mail.
		update_option( self::LAST_DAY_OPTION, wp_date( 'Y-m-d' ), false );

		$include_explain = (bool) get_option( 'thw_votd_digest_include_explain', true );
		$allow_generate  = (bool) get_option( 'thw_votd_digest_generate_explain', false );
		$subject_tpl     = (string) get_option( 'thw_votd_digest_subject', __( 'Verse of the Day — {reference}', 'hidden-word-bible-lessons' ) );
		if ( '' === trim( $subject_tpl ) ) {
			$subject_tpl = __( 'Verse of the Day — {reference}', 'hidden-word-bible-lessons' );
		}
		$from = get_option( 'thw_votd_digest_from_name', get_bloginfo( 'name' ) );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( $from ) {
			$headers[] = 'From: ' . $from . ' <' . get_option( 'admin_email' ) . '>';
		}

		// Cache payload + explanation per translation.
		$cache = array();

		foreach ( $confirmed as $row ) {
			$translation = ! empty( $row['translation'] ) ? sanitize_key( (string) $row['translation'] ) : self::get_default_translation();
			$cache_key   = $translation;

			if ( ! isset( $cache[ $cache_key ] ) ) {
				$payload = THW_Premium_Verse_Of_The_Day::get_payload_for_translation( $translation );
				$explain = array(
					'html'     => '',
					'post_url' => '',
				);
				if ( $include_explain && ! empty( $payload['reference'] ) ) {
					$explain = THW_Premium_Verse_Of_The_Day::ensure_explanation( $payload, $allow_generate );
				}
				$cache[ $cache_key ] = array(
					'payload' => $payload,
					'explain' => $explain,
				);
			}

			$payload = $cache[ $cache_key ]['payload'];
			$explain = $cache[ $cache_key ]['explain'];
			if ( empty( $payload['reference'] ) || empty( $payload['text'] ) ) {
				continue;
			}

			$subject = str_replace(
				array( '{reference}', '{translation}' ),
				array( $payload['reference'], ! empty( $payload['translation_label'] ) ? $payload['translation_label'] : $translation ),
				$subject_tpl
			);

			$body = self::build_email_body( $payload, $explain, $row );

			wp_mail( $row['email'], $subject, $body, $headers );
		}
	}

	/**
	 * Build HTML email body for one subscriber.
	 *
	 * @param array<string, mixed> $payload VOTD payload.
	 * @param array<string, mixed> $explain Explanation data.
	 * @param array<string, string> $row    Subscriber row.
	 * @return string
	 */
	public static function build_email_body( $payload, $explain, $row ) {
		$unsub_url = add_query_arg(
			array(
				'action' => 'thw_votd_digest_unsubscribe',
				'email'  => $row['email'],
				'token'  => $row['token'],
			),
			admin_url( 'admin-post.php' )
		);

		$body  = '<h2>' . esc_html__( 'Bible.com Verse of the Day', 'hidden-word-bible-lessons' ) . '</h2>';
		$body .= '<p><strong>' . esc_html( $payload['reference'] ) . '</strong>';
		if ( ! empty( $payload['translation_label'] ) ) {
			$body .= ' <span>(' . esc_html( $payload['translation_label'] ) . ')</span>';
		}
		$body .= '</p>';
		$body .= '<blockquote>' . esc_html( $payload['text'] ) . '</blockquote>';

		if ( ! empty( $payload['image'] ) && get_option( 'thw_votd_digest_include_image', false ) ) {
			$body .= '<p><img src="' . esc_url( $payload['image'] ) . '" alt="" style="max-width:100%;height:auto;" /></p>';
		}

		if ( ! empty( $explain['html'] ) ) {
			$body .= '<h3>' . esc_html__( 'Explanation', 'hidden-word-bible-lessons' ) . '</h3>';
			$body .= '<div>' . wp_kses_post( $explain['html'] ) . '</div>';
			if ( ! empty( $explain['post_url'] ) ) {
				$body .= '<p><a href="' . esc_url( $explain['post_url'] ) . '">' . esc_html__( 'Read on the website', 'hidden-word-bible-lessons' ) . '</a></p>';
			}
		} elseif ( ! empty( $explain['post_url'] ) ) {
			$body .= '<p><a href="' . esc_url( $explain['post_url'] ) . '">' . esc_html__( 'Read the full explanation', 'hidden-word-bible-lessons' ) . '</a></p>';
		}

		$body .= '<p><a href="' . esc_url( THW_Premium_Verse_Of_The_Day::SOURCE_URL ) . '">' . esc_html__( 'Source: Bible.com / YouVersion', 'hidden-word-bible-lessons' ) . '</a></p>';

		$user    = get_user_by( 'email', $row['email'] );
		$user_id = $user ? (int) $user->ID : 0;
		$sections = apply_filters( 'hwbl_digest_email_sections', array(), $user_id );
		if ( ! empty( $sections ) ) {
			$body .= implode( '', $sections );
		}

		$body .= '<p><small><a href="' . esc_url( $unsub_url ) . '">' . esc_html__( 'Unsubscribe', 'hidden-word-bible-lessons' ) . '</a></small></p>';

		return $body;
	}
}
