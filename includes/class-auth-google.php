<?php
/**
 * Sign in with Google → create/link WordPress user → companion Application Password claim.
 *
 * Verifies Google ID tokens (RS256) against Google's JWKS.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Auth_Google
 */
class HWBL_Auth_Google {

	const META_SUB         = '_hwbl_google_sub';
	const JWKS_URL         = 'https://www.googleapis.com/oauth2/v3/certs';
	const TRANSIENT        = 'hwbl_google_jwks';
	const AUDIENCES_OPTION = 'hwbl_google_oauth_audiences';

	/**
	 * Register REST.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Allowed OAuth client IDs (JWT aud). Comma/whitespace-separated option + filter.
	 *
	 * @return string[]
	 */
	public static function allowed_audiences() {
		$raw = (string) get_option( self::AUDIENCES_OPTION, '' );
		$ids = preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		$ids = is_array( $ids ) ? array_values( array_unique( array_map( 'strval', $ids ) ) ) : array();
		/**
		 * Filter Google Sign-In OAuth client IDs accepted as JWT audience.
		 *
		 * @param string[] $ids Client IDs (web / iOS / Android).
		 */
		return apply_filters( 'hwbl_google_audiences', $ids );
	}

	/**
	 * POST /hwbl/v1/auth/google
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/auth/google',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_google_sign_in' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'identityToken' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => array( __CLASS__, 'sanitize_jwt' ),
					),
					'email'         => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
					),
					'fullName'      => array(
						'required' => false,
						'type'     => 'object',
					),
				),
			)
		);
	}

	/**
	 * Keep JWT charset (sanitize_text_field can corrupt the signature).
	 *
	 * @param mixed $value Raw token.
	 * @return string
	 */
	public static function sanitize_jwt( $value ) {
		$token = preg_replace( '/[^A-Za-z0-9_\-\.]/', '', (string) $value );
		return is_string( $token ) ? $token : '';
	}

	/**
	 * Exchange a Google ID token for a one-time companion claim code.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_google_sign_in( WP_REST_Request $request ) {
		if ( ! class_exists( 'HWBL_App_Connect' ) ) {
			return new WP_Error(
				'hwbl_google_unavailable',
				__( 'App connect is not available.', 'hidden-word-bible-lessons' ),
				array( 'status' => 500 )
			);
		}

		$token = self::sanitize_jwt( $request->get_param( 'identityToken' ) );
		if ( '' === $token ) {
			return new WP_Error(
				'hwbl_google_token_missing',
				__( 'Google identity token is required.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$allowed = self::allowed_audiences();
		if ( array() === $allowed ) {
			return new WP_Error(
				'hwbl_google_not_configured',
				__( 'Sign in with Google is not configured on this site.', 'hidden-word-bible-lessons' ),
				array( 'status' => 503 )
			);
		}

		$claims = self::verify_identity_token( $token, $allowed );
		if ( is_wp_error( $claims ) ) {
			return $claims;
		}

		$sub = isset( $claims['sub'] ) ? sanitize_text_field( (string) $claims['sub'] ) : '';
		if ( '' === $sub ) {
			return new WP_Error(
				'hwbl_google_sub_missing',
				__( 'Google account identifier missing.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$email_hint = sanitize_email( (string) $request->get_param( 'email' ) );
		$email      = isset( $claims['email'] ) ? sanitize_email( (string) $claims['email'] ) : $email_hint;
		$full_name  = $request->get_param( 'fullName' );
		$full_name  = is_array( $full_name ) ? $full_name : array();
		$given      = isset( $full_name['givenName'] ) ? sanitize_text_field( (string) $full_name['givenName'] ) : '';
		$family     = isset( $full_name['familyName'] ) ? sanitize_text_field( (string) $full_name['familyName'] ) : '';
		if ( '' === $given && isset( $claims['given_name'] ) ) {
			$given = sanitize_text_field( (string) $claims['given_name'] );
		}
		if ( '' === $family && isset( $claims['family_name'] ) ) {
			$family = sanitize_text_field( (string) $claims['family_name'] );
		}
		if ( '' === $given && '' === $family && isset( $claims['name'] ) ) {
			$parts  = preg_split( '/\s+/', sanitize_text_field( (string) $claims['name'] ), 2 );
			$given  = isset( $parts[0] ) ? (string) $parts[0] : '';
			$family = isset( $parts[1] ) ? (string) $parts[1] : '';
		}

		$user = self::find_or_create_user( $sub, $email, $given, $family );
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$issued = HWBL_App_Connect::issue_auth_code_for_user( (int) $user->ID );
		if ( is_wp_error( $issued ) ) {
			return $issued;
		}

		return new WP_REST_Response(
			array(
				'code'     => (string) $issued['code'],
				'deepLink' => isset( $issued['deepLink'] ) ? (string) $issued['deepLink'] : '',
				'expires'  => isset( $issued['expires'] ) ? (int) $issued['expires'] : 0,
				'siteUrl'  => untrailingslashit( home_url() ),
			)
		);
	}

	/**
	 * Verify Google ID JWT via JWKS (RS256).
	 *
	 * @param string   $jwt     Identity token.
	 * @param string[] $allowed Allowed aud values.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function verify_identity_token( $jwt, $allowed ) {
		$parts = explode( '.', $jwt );
		if ( 3 !== count( $parts ) ) {
			return new WP_Error( 'hwbl_google_jwt', __( 'Invalid Google token.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$header = json_decode( self::b64url_decode( $parts[0] ), true );
		if ( ! is_array( $header ) ) {
			return new WP_Error( 'hwbl_google_jwt', __( 'Invalid Google token header.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$alg = isset( $header['alg'] ) ? strtoupper( (string) $header['alg'] ) : '';
		$kid = isset( $header['kid'] ) ? (string) $header['kid'] : '';
		if ( 'RS256' !== $alg ) {
			return new WP_Error(
				'hwbl_google_alg',
				sprintf(
					/* translators: %s: JWT alg header */
					__( 'Unsupported Google token algorithm (%s). Expected RS256.', 'hidden-word-bible-lessons' ),
					$alg ? $alg : 'none'
				),
				array( 'status' => 401 )
			);
		}
		if ( '' === $kid ) {
			return new WP_Error( 'hwbl_google_kid', __( 'Google token is missing a key id.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$jwk = self::jwk_for_kid( $kid );
		if ( is_wp_error( $jwk ) ) {
			return $jwk;
		}

		$pem = self::jwk_to_pem( $jwk );
		if ( is_wp_error( $pem ) ) {
			return $pem;
		}

		$signed = $parts[0] . '.' . $parts[1];
		$sig    = self::b64url_decode( $parts[2] );
		if ( '' === $sig ) {
			return new WP_Error( 'hwbl_google_sig', __( 'Google token signature is missing.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$ok = openssl_verify( $signed, $sig, $pem, OPENSSL_ALGO_SHA256 );
		if ( 1 !== $ok ) {
			return new WP_Error( 'hwbl_google_sig', __( 'Google token signature is invalid.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$claims = json_decode( self::b64url_decode( $parts[1] ), true );
		if ( ! is_array( $claims ) ) {
			return new WP_Error( 'hwbl_google_jwt', __( 'Invalid Google token payload.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$iss = isset( $claims['iss'] ) ? (string) $claims['iss'] : '';
		if ( ! in_array( $iss, array( 'https://accounts.google.com', 'accounts.google.com' ), true ) ) {
			return new WP_Error( 'hwbl_google_iss', __( 'Google token issuer mismatch.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$aud    = isset( $claims['aud'] ) ? $claims['aud'] : '';
		$aud_ok = false;
		foreach ( (array) $allowed as $expect ) {
			if ( '' === (string) $expect ) {
				continue;
			}
			if ( is_array( $aud ) && in_array( (string) $expect, $aud, true ) ) {
				$aud_ok = true;
			} elseif ( (string) $expect === (string) $aud ) {
				$aud_ok = true;
			}
		}
		if ( ! $aud_ok ) {
			return new WP_Error( 'hwbl_google_aud', __( 'Google token audience mismatch.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$exp = isset( $claims['exp'] ) ? (int) $claims['exp'] : 0;
		if ( $exp < time() - 60 ) {
			return new WP_Error( 'hwbl_google_exp', __( 'Google token expired.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		return $claims;
	}

	/**
	 * Find by Google subject, else email, else create a subscriber.
	 *
	 * @param string $sub    Google user id.
	 * @param string $email  Email from token or app hint.
	 * @param string $given  Given name.
	 * @param string $family Family name.
	 * @return WP_User|WP_Error
	 */
	public static function find_or_create_user( $sub, $email, $given, $family ) {
		$users = get_users(
			array(
				'meta_key'   => self::META_SUB, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $sub, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
			)
		);
		if ( ! empty( $users[0] ) && $users[0] instanceof WP_User ) {
			return $users[0];
		}

		if ( $email && is_email( $email ) ) {
			$by_email = get_user_by( 'email', $email );
			if ( $by_email instanceof WP_User ) {
				update_user_meta( (int) $by_email->ID, self::META_SUB, $sub );
				return $by_email;
			}
		}

		$login = 'google_' . substr( hash( 'sha256', $sub ), 0, 12 );
		if ( username_exists( $login ) ) {
			$login = 'google_' . substr( hash( 'sha256', $sub . wp_generate_password( 8, false, false ) ), 0, 12 );
		}
		if ( ! $email || ! is_email( $email ) ) {
			$email = $login . '@users.noreply.google.com';
		}

		$display = trim( $given . ' ' . $family );
		if ( '' === $display ) {
			$display = $login;
		}

		$role = apply_filters( 'hwbl_google_new_user_role', 'subscriber' );
		$uid  = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => $display,
				'first_name'   => $given,
				'last_name'    => $family,
				'role'         => is_string( $role ) && '' !== $role ? $role : 'subscriber',
			)
		);
		if ( is_wp_error( $uid ) ) {
			return $uid;
		}

		update_user_meta( (int) $uid, self::META_SUB, $sub );
		$user = get_userdata( (int) $uid );
		if ( ! $user instanceof WP_User ) {
			return new WP_Error( 'hwbl_google_user', __( 'Could not create account.', 'hidden-word-bible-lessons' ), array( 'status' => 500 ) );
		}

		self::email_admin_new_google_user( $user );
		do_action( 'hwbl_google_user_created', (int) $uid, $sub );

		return $user;
	}

	/**
	 * @param WP_User $user New user.
	 */
	private static function email_admin_new_google_user( $user ) {
		$admin = get_option( 'admin_email' );
		if ( ! $admin || ! is_email( (string) $admin ) ) {
			return;
		}
		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] New member account (Sign in with Google)', 'hidden-word-bible-lessons' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$body = sprintf(
			/* translators: 1: username, 2: email, 3: user ID */
			__( "A reader signed in with Google and a WordPress account was created on this site.\n\nUsername: %1\$s\nEmail: %2\$s\nUser ID: %3\$d\n", 'hidden-word-bible-lessons' ),
			$user->user_login,
			$user->user_email,
			(int) $user->ID
		);
		wp_mail( (string) $admin, $subject, $body );
	}

	/**
	 * @param string $kid Key id.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function jwk_for_kid( $kid ) {
		$keys = self::fetch_jwks();
		if ( is_wp_error( $keys ) ) {
			return $keys;
		}
		foreach ( $keys as $jwk ) {
			if ( is_array( $jwk ) && isset( $jwk['kid'] ) && (string) $jwk['kid'] === $kid ) {
				return $jwk;
			}
		}
		delete_transient( self::TRANSIENT );
		$keys = self::fetch_jwks( true );
		if ( is_wp_error( $keys ) ) {
			return $keys;
		}
		foreach ( $keys as $jwk ) {
			if ( is_array( $jwk ) && isset( $jwk['kid'] ) && (string) $jwk['kid'] === $kid ) {
				return $jwk;
			}
		}
		return new WP_Error( 'hwbl_google_kid', __( 'Google signing key was not found.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
	}

	/**
	 * @param bool $force Bypass cache.
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	private static function fetch_jwks( $force = false ) {
		if ( ! $force ) {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) && ! empty( $cached ) ) {
				return $cached;
			}
		}
		$response = wp_safe_remote_get(
			self::JWKS_URL,
			array(
				'timeout' => 10,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'hwbl_google_jwks', __( 'Could not reach Google to verify Sign in with Google.', 'hidden-word-bible-lessons' ), array( 'status' => 503 ) );
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'hwbl_google_jwks', __( 'Google key list was unavailable.', 'hidden-word-bible-lessons' ), array( 'status' => 503 ) );
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$keys = isset( $body['keys'] ) && is_array( $body['keys'] ) ? $body['keys'] : array();
		if ( array() === $keys ) {
			return new WP_Error( 'hwbl_google_jwks', __( 'Google key list was empty.', 'hidden-word-bible-lessons' ), array( 'status' => 503 ) );
		}
		set_transient( self::TRANSIENT, $keys, 12 * HOUR_IN_SECONDS );
		return $keys;
	}

	/**
	 * RSA JWK to PEM.
	 *
	 * @param array<string, mixed> $jwk JWK.
	 * @return string|WP_Error
	 */
	private static function jwk_to_pem( $jwk ) {
		$kty = isset( $jwk['kty'] ) ? (string) $jwk['kty'] : '';
		if ( 'RSA' !== $kty ) {
			return new WP_Error( 'hwbl_google_jwk', __( 'Google signing key type is unsupported.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}
		$n = self::b64url_decode( isset( $jwk['n'] ) ? (string) $jwk['n'] : '' );
		$e = self::b64url_decode( isset( $jwk['e'] ) ? (string) $jwk['e'] : '' );
		if ( '' === $n || '' === $e ) {
			return new WP_Error( 'hwbl_google_jwk', __( 'Google RSA key is incomplete.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}
		$rsa  = self::asn1_seq( self::asn1_int( $n ) . self::asn1_int( $e ) );
		$bit  = "\x00" . $rsa;
		$oid  = hex2bin( '300d06092a864886f70d0101010500' );
		$spki = self::asn1_seq( $oid . "\x03" . self::asn1_length( strlen( $bit ) ) . $bit );
		return "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $spki ), 64, "\n" ) . "-----END PUBLIC KEY-----\n";
	}

	/**
	 * @param string $bin Unsigned big-endian.
	 * @return string
	 */
	private static function asn1_int( $bin ) {
		$bin = ltrim( $bin, "\x00" );
		if ( '' === $bin ) {
			$bin = "\x00";
		}
		if ( ord( $bin[0] ) & 0x80 ) {
			$bin = "\x00" . $bin;
		}
		return "\x02" . self::asn1_length( strlen( $bin ) ) . $bin;
	}

	/**
	 * @param string $bin Contents.
	 * @return string
	 */
	private static function asn1_seq( $bin ) {
		return "\x30" . self::asn1_length( strlen( $bin ) ) . $bin;
	}

	/**
	 * @param int $len Length.
	 * @return string
	 */
	private static function asn1_length( $len ) {
		$len = (int) $len;
		if ( $len < 128 ) {
			return chr( $len );
		}
		$out = '';
		while ( $len > 0 ) {
			$out = chr( $len & 0xff ) . $out;
			$len >>= 8;
		}
		return chr( 0x80 | strlen( $out ) ) . $out;
	}

	/**
	 * @param string $data Encoded.
	 * @return string
	 */
	private static function b64url_decode( $data ) {
		$b64 = strtr( (string) $data, '-_', '+/' );
		$pad = strlen( $b64 ) % 4;
		if ( $pad ) {
			$b64 .= str_repeat( '=', 4 - $pad );
		}
		$raw = base64_decode( $b64, true );
		return false === $raw ? '' : $raw;
	}
}
