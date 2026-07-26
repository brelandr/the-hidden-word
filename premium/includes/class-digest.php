<?php
/**
 * Email digest for scheduled lessons.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_Digest
 */
class THW_Premium_Digest {

	const SUBSCRIBERS_OPTION = 'thw_digest_subscribers';
	const CRON_HOOK          = 'thw_premium_lesson_digest';
	const LAST_SLOT_OPTION   = 'thw_digest_last_slot';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		thw_premium_register_shortcode( 'thw_subscribe', array( __CLASS__, 'render_subscribe_form' ) );
		add_action( 'admin_post_thw_digest_subscribe', array( __CLASS__, 'handle_subscribe' ) );
		add_action( 'admin_post_nopriv_thw_digest_subscribe', array( __CLASS__, 'handle_subscribe' ) );
		add_action( 'admin_post_thw_digest_confirm', array( __CLASS__, 'handle_confirm' ) );
		add_action( 'admin_post_nopriv_thw_digest_confirm', array( __CLASS__, 'handle_confirm' ) );
		add_action( 'admin_post_thw_digest_unsubscribe', array( __CLASS__, 'handle_unsubscribe' ) );
		add_action( 'admin_post_nopriv_thw_digest_unsubscribe', array( __CLASS__, 'handle_unsubscribe' ) );
		add_action( 'init', array( __CLASS__, 'schedule_cron' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'send_digest' ) );
		add_filter( 'the_content', array( __CLASS__, 'prepend_subscribe_notices' ) );
	}

	/**
	 * Show subscribe / confirm / unsubscribe notices above content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function prepend_subscribe_notices( $content ) {
		global $post;
		if ( ! $post || ! has_shortcode( $post->post_content, 'thw_subscribe' ) ) {
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
		if ( isset( $_GET['thw_subscribed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<p class="thw-notice thw-notice-success">' . esc_html__( 'Check your email to confirm your subscription.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		if ( isset( $_GET['thw_confirmed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<p class="thw-notice thw-notice-success">' . esc_html__( 'Your lesson email subscription is confirmed.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		if ( isset( $_GET['thw_unsubscribed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<p class="thw-notice thw-notice-info">' . esc_html__( 'You have been unsubscribed from lesson emails.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		return '';
	}

	/**
	 * Schedule daily digest cron.
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
	 * Render subscribe shortcode.
	 *
	 * @return string
	 */
	public static function render_subscribe_form() {
		$html  = '<form class="thw-subscribe-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		$html .= '<input type="hidden" name="action" value="thw_digest_subscribe" />';
		$html .= wp_nonce_field( 'thw_digest_subscribe', '_wpnonce', true, false );
		$html .= '<label for="thw_digest_email">' . esc_html__( 'Email address', 'hidden-word-bible-lessons' ) . '</label>';
		$html .= '<input type="email" id="thw_digest_email" name="email" required class="widefat" />';
		$html .= '<button type="submit" class="thw-btn">' . esc_html__( 'Subscribe to lesson emails', 'hidden-word-bible-lessons' ) . '</button>';
		$html .= '</form>';
		return $html;
	}

	/**
	 * Handle subscription request.
	 */
	public static function handle_subscribe() {
		check_admin_referer( 'thw_digest_subscribe' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
			exit;
		}

		$token = wp_generate_password( 32, false );
		$subs  = self::get_subscribers();
		$subs[] = array(
			'email'   => $email,
			'status'  => 'pending',
			'token'   => $token,
			'subscribed_at' => current_time( 'mysql' ),
		);
		self::save_subscribers( $subs );

		$confirm_url = add_query_arg(
			array(
				'action' => 'thw_digest_confirm',
				'email'  => rawurlencode( $email ),
				'token'  => $token,
			),
			admin_url( 'admin-post.php' )
		);

		wp_mail(
			$email,
			/* translators: %s: site name */
			sprintf( __( 'Confirm your subscription to %s', 'hidden-word-bible-lessons' ), get_bloginfo( 'name' ) ),
			sprintf(
				/* translators: %s: confirmation URL */
				__( "Please confirm your Bible lesson email subscription:\n\n%s\n", 'hidden-word-bible-lessons' ),
				$confirm_url
			)
		);

		wp_safe_redirect( add_query_arg( 'thw_subscribed', '1', wp_get_referer() ? wp_get_referer() : home_url( '/' ) ) );
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

		wp_safe_redirect( add_query_arg( 'thw_confirmed', '1', home_url( '/' ) ) );
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
				function ( $row ) use ( $email, $token ) {
					if ( $row['email'] !== $email ) {
						return true;
					}
					return ! ( isset( $row['token'] ) && hash_equals( $row['token'], $token ) );
				}
			)
		);
		self::save_subscribers( $subs );

		wp_safe_redirect( add_query_arg( 'thw_unsubscribed', '1', home_url( '/' ) ) );
		exit;
	}

	/**
	 * Whether digest should send today.
	 *
	 * @return bool
	 */
	public static function should_send_today() {
		if ( ! get_option( 'thw_digest_enabled', false ) ) {
			return false;
		}

		$mode = get_option( 'hwbl_schedule_mode', 'week' );
		$slot = HWBL_Scheduler::get_current_slot();

		if ( 'day' === $mode ) {
			return true;
		}

		$last = (int) get_option( self::LAST_SLOT_OPTION, 0 );
		if ( $last !== $slot ) {
			update_option( self::LAST_SLOT_OPTION, $slot );
			return true;
		}

		return false;
	}

	/**
	 * Cron callback: send digest emails.
	 */
	public static function send_digest() {
		if ( ! self::should_send_today() ) {
			return;
		}

		$lesson_id = HWBL_Scheduler::get_current_lesson_id();
		if ( ! $lesson_id ) {
			return;
		}

		$lesson      = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		$translation = get_option( 'hwbl_active_translation', 'niv' );
		$trans_svc   = HWBL_Translation_Service::instance();
		$verse_text  = $trans_svc->get_verse_by_week( $lesson['lesson_number'], $translation );
		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_text( $lesson['book_id'], $lesson['chapter'], $lesson['verse_start'], $translation );
		}

		$default_subject = class_exists( 'HWBL_Scheduler' )
			? HWBL_Scheduler::get_schedule_phrase( 'memorize' )
			: __( "Today's Verse to Memorize", 'hidden-word-bible-lessons' );
		$subject         = get_option( 'thw_digest_subject', $default_subject );
		if ( '' === trim( (string) $subject ) ) {
			$subject = $default_subject;
		}
		$from = get_option( 'thw_digest_from_name', get_bloginfo( 'name' ) );

		$cta = class_exists( 'HWBL_Scheduler' )
			? sprintf(
				/* translators: %s: schedule-aware memorize label */
				__( 'Open %s', 'hidden-word-bible-lessons' ),
				HWBL_Scheduler::get_schedule_phrase( 'memorize' )
			)
			: __( 'Open verse to memorize', 'hidden-word-bible-lessons' );

		$body  = '<h2>' . esc_html( $lesson['title'] ) . '</h2>';
		$body .= '<p><strong>' . esc_html( $lesson['reference'] ) . '</strong></p>';
		$body .= '<blockquote>' . esc_html( $verse_text ) . '</blockquote>';
		$body .= '<p><a href="' . esc_url( get_permalink( $lesson_id ) ) . '">' . esc_html( $cta ) . '</a></p>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( $from ) {
			$headers[] = 'From: ' . $from . ' <' . get_option( 'admin_email' ) . '>';
		}

		foreach ( self::get_subscribers() as $row ) {
			if ( empty( $row['status'] ) || 'confirmed' !== $row['status'] ) {
				continue;
			}
			$unsub_token = isset( $row['token'] ) ? $row['token'] : '';
			if ( ! $unsub_token ) {
				continue;
			}

			$user      = get_user_by( 'email', $row['email'] );
			$user_id   = $user ? (int) $user->ID : 0;
			$mail_body = $body;
			$sections  = apply_filters( 'hwbl_digest_email_sections', array(), $user_id );
			if ( ! empty( $sections ) ) {
				$mail_body .= implode( '', $sections );
			}

			$unsub_url   = add_query_arg(
				array(
					'action' => 'thw_digest_unsubscribe',
					'email'  => rawurlencode( $row['email'] ),
					'token'  => $unsub_token,
				),
				admin_url( 'admin-post.php' )
			);
			wp_mail( $row['email'], $subject, $mail_body . '<p><small><a href="' . esc_url( $unsub_url ) . '">' . esc_html__( 'Unsubscribe', 'hidden-word-bible-lessons' ) . '</a></small></p>', $headers );
		}
	}
}
