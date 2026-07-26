<?php
/**
 * Front-end registration email verification.
 *
 * New self-registered members stay pending until they click the email link.
 * Existing / admin-created users (no pending meta) remain usable.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Email_Verification
 */
class HWBL_Email_Verification {

	const META_STATUS  = '_hwbl_email_verified';
	const META_KEY     = '_hwbl_email_verify_key';
	const META_EXPIRES = '_hwbl_email_verify_expires';

	const STATUS_PENDING  = '0';
	const STATUS_VERIFIED = '1';

	const QUERY_VERIFY = 'hwbl_verify_email';
	const KEY_TTL      = 172800; // 48 hours.

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'wp_authenticate_user', array( __CLASS__, 'block_unverified_login' ), 15, 2 );
		add_action( 'init', array( __CLASS__, 'maybe_handle_verification_link' ), 5 );
	}

	/**
	 * Whether the user may sign in (verified or never marked pending).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_verified( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return false;
		}

		$status = (string) get_user_meta( $user_id, self::META_STATUS, true );
		if ( '' === $status || self::STATUS_VERIFIED === $status ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the user is explicitly waiting on email confirmation.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_pending( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return false;
		}
		return self::STATUS_PENDING === (string) get_user_meta( $user_id, self::META_STATUS, true );
	}

	/**
	 * Mark a newly registered user pending and email a confirmation link.
	 *
	 * @param int $user_id User ID.
	 * @return true|WP_Error
	 */
	public static function start_for_user( $user_id ) {
		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return new WP_Error( 'hwbl_verify_no_user', __( 'Could not find that account.', 'hidden-word-bible-lessons' ) );
		}

		$key = self::generate_key();
		update_user_meta( $user_id, self::META_STATUS, self::STATUS_PENDING );
		update_user_meta( $user_id, self::META_KEY, $key );
		update_user_meta( $user_id, self::META_EXPIRES, time() + self::KEY_TTL );

		$sent = self::send_verification_email( $user, $key );
		if ( ! $sent ) {
			return new WP_Error(
				'hwbl_verify_mail_failed',
				__( 'Your account was created, but we could not send the confirmation email. Please use “Resend confirmation” on the login page.', 'hidden-word-bible-lessons' )
			);
		}

		return true;
	}

	/**
	 * Resend confirmation for a pending account (by login or email).
	 *
	 * @param string $login_or_email Username or email.
	 * @return true|WP_Error
	 */
	public static function resend( $login_or_email ) {
		$login_or_email = trim( (string) $login_or_email );
		if ( '' === $login_or_email ) {
			return new WP_Error( 'hwbl_verify_missing', __( 'Enter your username or email.', 'hidden-word-bible-lessons' ) );
		}

		$user = is_email( $login_or_email )
			? get_user_by( 'email', $login_or_email )
			: get_user_by( 'login', $login_or_email );

		if ( ! $user instanceof WP_User && ! is_email( $login_or_email ) ) {
			$user = get_user_by( 'email', $login_or_email );
		}

		// Same generic response either way (avoid account enumeration).
		if ( ! $user instanceof WP_User ) {
			return true;
		}

		if ( self::is_verified( (int) $user->ID ) ) {
			return true;
		}

		return self::start_for_user( (int) $user->ID );
	}

	/**
	 * Confirm a verification key and activate the account.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Key from email.
	 * @return true|WP_Error
	 */
	public static function verify( $user_id, $key ) {
		$user_id = (int) $user_id;
		$key     = sanitize_text_field( (string) $key );
		$user    = get_userdata( $user_id );

		if ( ! $user instanceof WP_User || '' === $key ) {
			return new WP_Error( 'hwbl_verify_invalid', __( 'That confirmation link is invalid.', 'hidden-word-bible-lessons' ) );
		}

		if ( self::is_verified( $user_id ) ) {
			return true;
		}

		$stored  = (string) get_user_meta( $user_id, self::META_KEY, true );
		$expires = (int) get_user_meta( $user_id, self::META_EXPIRES, true );

		if ( '' === $stored || ! hash_equals( $stored, $key ) ) {
			return new WP_Error( 'hwbl_verify_invalid', __( 'That confirmation link is invalid.', 'hidden-word-bible-lessons' ) );
		}

		if ( $expires > 0 && time() > $expires ) {
			return new WP_Error(
				'hwbl_verify_expired',
				__( 'That confirmation link has expired. Please request a new one from the login page.', 'hidden-word-bible-lessons' )
			);
		}

		self::mark_verified( $user_id );
		return true;
	}

	/**
	 * Mark verified and clear the one-time key.
	 *
	 * @param int $user_id User ID.
	 */
	public static function mark_verified( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return;
		}
		update_user_meta( $user_id, self::META_STATUS, self::STATUS_VERIFIED );
		delete_user_meta( $user_id, self::META_KEY );
		delete_user_meta( $user_id, self::META_EXPIRES );
	}

	/**
	 * Block password login until the email is confirmed.
	 *
	 * @param WP_User|WP_Error $user     User or error.
	 * @param string           $password Password (unused).
	 * @return WP_User|WP_Error
	 */
	public static function block_unverified_login( $user, $password = '' ) {
		unset( $password );
		if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
			return $user;
		}

		if ( ! self::is_pending( (int) $user->ID ) ) {
			return $user;
		}

		return new WP_Error(
			'hwbl_email_unverified',
			__( 'Please confirm your email address before signing in. Check your inbox for the confirmation link, or request a new one below.', 'hidden-word-bible-lessons' )
		);
	}

	/**
	 * Handle ?hwbl_verify_email=1&uid=&key= links.
	 */
	public static function maybe_handle_verification_link() {
		if ( empty( $_GET[ self::QUERY_VERIFY ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$user_id = isset( $_GET['uid'] ) ? absint( wp_unslash( $_GET['uid'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key     = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result  = self::verify( $user_id, $key );
		$login   = self::login_url();
		$code    = is_wp_error( $result ) ? $result->get_error_code() : 'hwbl_verify_ok';

		if ( 'hwbl_verify_ok' === $code ) {
			$redirect = add_query_arg( 'login', 'verified', $login );
		} elseif ( 'hwbl_verify_expired' === $code ) {
			$redirect = add_query_arg( 'login', 'verify_expired', $login );
		} else {
			$redirect = add_query_arg( 'login', 'verify_invalid', $login );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Build the confirmation URL for a user + key.
	 *
	 * @param int    $user_id User ID.
	 * @param string $key     Key.
	 * @return string
	 */
	public static function get_verify_url( $user_id, $key ) {
		return add_query_arg(
			array(
				self::QUERY_VERIFY => '1',
				'uid'              => (int) $user_id,
				'key'              => (string) $key,
			),
			home_url( '/' )
		);
	}

	/**
	 * Preferred front-end login URL (theme page when present).
	 *
	 * @return string
	 */
	public static function login_url() {
		if ( function_exists( 'thw_theme_login_url' ) ) {
			return thw_theme_login_url();
		}
		return wp_login_url();
	}

	/**
	 * @return string
	 */
	private static function generate_key() {
		return wp_generate_password( 32, false, false );
	}

	/**
	 * @param WP_User $user User.
	 * @param string  $key  Key.
	 * @return bool
	 */
	private static function send_verification_email( $user, $key ) {
		$email = is_email( $user->user_email ) ? $user->user_email : '';
		if ( ! $email ) {
			return false;
		}

		$url     = self::get_verify_url( (int) $user->ID, $key );
		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Confirm your email address', 'hidden-word-bible-lessons' ),
			$site
		);

		$message  = sprintf(
			/* translators: %s: username */
			__( 'Hi %s,', 'hidden-word-bible-lessons' ),
			$user->user_login
		) . "\n\n";
		$message .= __( 'Thanks for creating an account. Please confirm your email address by opening this link:', 'hidden-word-bible-lessons' ) . "\n\n";
		$message .= $url . "\n\n";
		$message .= __( 'This link expires in 48 hours. If you did not create this account, you can ignore this email.', 'hidden-word-bible-lessons' ) . "\n";

		return (bool) wp_mail( $email, $subject, $message );
	}
}
