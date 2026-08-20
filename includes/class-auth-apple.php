<?php
/**
 * Sign in with Apple → companion Application Password claim.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Auth_Apple
 */
class HWBL_Auth_Apple {

	const META_SUB = '_hwbl_apple_sub';

	/**
	 * Register REST route.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * POST /hwbl/v1/auth/apple
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/auth/apple',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_apple_sign_in' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Exchange an Apple identity token for a one-time companion claim code.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_apple_sign_in( $request ) {
		if ( ! class_exists( 'HWBL_App_Connect' ) ) {
			return new WP_Error(
				'hwbl_apple_unavailable',
				__( 'App connect is not available.', 'hidden-word-bible-lessons' ),
				array( 'status' => 500 )
			);
		}

		$params         = $request->get_json_params();
		$params         = is_array( $params ) ? $params : $request->get_params();
		$identity_token = isset( $params['identityToken'] ) ? (string) $params['identityToken'] : '';
		$full_name      = isset( $params['fullName'] ) && is_array( $params['fullName'] ) ? $params['fullName'] : array();
		$email_hint     = isset( $params['email'] ) ? sanitize_email( (string) $params['email'] ) : '';

		if ( '' === $identity_token ) {
			return new WP_Error(
				'hwbl_apple_token_missing',
				__( 'Apple identity token is required.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$claims = self::verify_identity_token( $identity_token );
		if ( is_wp_error( $claims ) ) {
			return $claims;
		}

		$sub   = isset( $claims['sub'] ) ? sanitize_text_field( (string) $claims['sub'] ) : '';
		$email = isset( $claims['email'] ) ? sanitize_email( (string) $claims['email'] ) : $email_hint;
		if ( '' === $sub ) {
			return new WP_Error(
				'hwbl_apple_sub_missing',
				__( 'Apple account identifier missing.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$user = self::find_user_by_apple_sub( $sub );
		if ( ! $user && $email ) {
			$by_email = get_user_by( 'email', $email );
			if ( $by_email instanceof WP_User ) {
				$user = $by_email;
				update_user_meta( $user->ID, self::META_SUB, $sub );
			}
		}

		if ( ! $user ) {
			if ( ! $email ) {
				return new WP_Error(
					'hwbl_apple_email_required',
					__( 'Apple did not share an email for this sign-in. Use website connect, or try again and allow email sharing.', 'hidden-word-bible-lessons' ),
					array( 'status' => 400 )
				);
			}
			$user = self::create_user_from_apple( $email, $sub, $full_name );
			if ( is_wp_error( $user ) ) {
				return $user;
			}
		}

		$issued = HWBL_App_Connect::issue_auth_code_for_user( (int) $user->ID );
		if ( is_wp_error( $issued ) ) {
			return $issued;
		}

		return new WP_REST_Response(
			array(
				'code'     => (string) $issued['code'],
				'deepLink' => (string) $issued['deepLink'],
				'expires'  => (int) $issued['expires'],
				'siteUrl'  => untrailingslashit( home_url() ),
			)
		);
	}

	/**
	 * Verify Apple identity JWT via JWKS (RS256 today; ES256 retained for future keys).
	 *
	 * @param string $jwt Identity token.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function verify_identity_token( $jwt ) {
		$parts = explode( '.', $jwt );
		if ( 3 !== count( $parts ) ) {
			return new WP_Error( 'hwbl_apple_jwt', __( 'Invalid Apple token.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$header_json  = self::b64url_decode( $parts[0] );
		$payload_json = self::b64url_decode( $parts[1] );
		$header       = json_decode( $header_json, true );
		$payload      = json_decode( $payload_json, true );
		if ( ! is_array( $header ) || ! is_array( $payload ) ) {
			return new WP_Error( 'hwbl_apple_jwt', __( 'Invalid Apple token payload.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$kid = isset( $header['kid'] ) ? (string) $header['kid'] : '';
		$alg = isset( $header['alg'] ) ? strtoupper( (string) $header['alg'] ) : '';
		if ( ! in_array( $alg, array( 'RS256', 'ES256' ), true ) ) {
			return new WP_Error(
				'hwbl_apple_alg',
				/* translators: %s: JWT alg header */
				sprintf( __( 'Unsupported Apple token algorithm (%s). Expected RS256 or ES256.', 'hidden-word-bible-lessons' ), $alg ? $alg : 'none' ),
				array( 'status' => 401 )
			);
		}
		if ( '' === $kid ) {
			return new WP_Error( 'hwbl_apple_kid', __( 'Apple token is missing a key id.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$iss = isset( $payload['iss'] ) ? (string) $payload['iss'] : '';
		$aud = isset( $payload['aud'] ) ? (string) $payload['aud'] : '';
		$exp = isset( $payload['exp'] ) ? (int) $payload['exp'] : 0;
		if ( 'https://appleid.apple.com' !== $iss ) {
			return new WP_Error( 'hwbl_apple_jwt', __( 'Apple token issuer mismatch.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}
		if ( 'org.thehiddenword.companion' !== $aud ) {
			return new WP_Error( 'hwbl_apple_jwt', __( 'Apple token audience mismatch.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}
		if ( $exp < time() ) {
			return new WP_Error( 'hwbl_apple_jwt', __( 'Apple token expired.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$jwk = self::fetch_apple_jwk( $kid );
		if ( is_wp_error( $jwk ) ) {
			return $jwk;
		}

		$kty = isset( $jwk['kty'] ) ? (string) $jwk['kty'] : '';
		if ( 'RS256' === $alg && 'RSA' !== $kty ) {
			return new WP_Error( 'hwbl_apple_jwk', __( 'Apple RS256 token did not match an RSA signing key.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}
		if ( 'ES256' === $alg && 'EC' !== $kty ) {
			return new WP_Error( 'hwbl_apple_jwk', __( 'Apple ES256 token did not match an EC signing key.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$pem = self::jwk_to_pem( $jwk );
		if ( ! $pem ) {
			return new WP_Error( 'hwbl_apple_jwk', __( 'Could not build Apple public key.', 'hidden-word-bible-lessons' ), array( 'status' => 500 ) );
		}

		$signed = $parts[0] . '.' . $parts[1];
		$sig    = self::b64url_decode( $parts[2] );
		if ( 'ES256' === $alg ) {
			// Convert IEEE P1363 signature to DER for OpenSSL.
			$sig = self::p1363_to_der( $sig );
		}
		$ok = openssl_verify( $signed, $sig, $pem, OPENSSL_ALGO_SHA256 );
		if ( 1 !== $ok ) {
			return new WP_Error( 'hwbl_apple_jwt', __( 'Apple token signature invalid.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		return $payload;
	}

	/**
	 * @param string $sub Apple subject.
	 * @return WP_User|null
	 */
	private static function find_user_by_apple_sub( $sub ) {
		$users = get_users(
			array(
				'meta_key'   => self::META_SUB, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $sub, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
			)
		);
		return ! empty( $users[0] ) && $users[0] instanceof WP_User ? $users[0] : null;
	}

	/**
	 * @param string               $email Email.
	 * @param string               $sub   Apple sub.
	 * @param array<string, mixed> $full_name Apple fullName payload.
	 * @return WP_User|WP_Error
	 */
	private static function create_user_from_apple( $email, $sub, $full_name ) {
		$login = sanitize_user( strstr( $email, '@', true ) ?: ( 'apple_' . substr( md5( $sub ), 0, 8 ) ), true );
		if ( username_exists( $login ) ) {
			$login = 'apple_' . substr( md5( $sub . $email ), 0, 12 );
		}
		$user_id = wp_create_user( $login, wp_generate_password( 24, true, true ), $email );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
		$first = isset( $full_name['givenName'] ) ? sanitize_text_field( (string) $full_name['givenName'] ) : '';
		$last  = isset( $full_name['familyName'] ) ? sanitize_text_field( (string) $full_name['familyName'] ) : '';
		wp_update_user(
			array(
				'ID'         => $user_id,
				'first_name' => $first,
				'last_name'  => $last,
				'display_name' => trim( $first . ' ' . $last ) ?: $login,
			)
		);
		update_user_meta( $user_id, self::META_SUB, $sub );
		$user = get_userdata( $user_id );
		return $user instanceof WP_User ? $user : new WP_Error( 'hwbl_apple_user', __( 'Could not create user.', 'hidden-word-bible-lessons' ), array( 'status' => 500 ) );
	}

	/**
	 * @param string $kid Key id.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function fetch_apple_jwk( $kid ) {
		$cached = get_transient( 'hwbl_apple_jwks' );
		if ( ! is_array( $cached ) ) {
			$cached = self::download_apple_jwks();
			if ( is_wp_error( $cached ) ) {
				return $cached;
			}
		}
		foreach ( $cached as $key ) {
			if ( is_array( $key ) && isset( $key['kid'] ) && (string) $key['kid'] === $kid ) {
				return $key;
			}
		}
		// Key rotation: refresh once.
		$fresh = self::download_apple_jwks();
		if ( is_wp_error( $fresh ) ) {
			return $fresh;
		}
		foreach ( $fresh as $key ) {
			if ( is_array( $key ) && isset( $key['kid'] ) && (string) $key['kid'] === $kid ) {
				return $key;
			}
		}
		return new WP_Error( 'hwbl_apple_jwks', __( 'Apple signing key not found.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
	}

	/**
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	private static function download_apple_jwks() {
		$response = wp_remote_get( 'https://appleid.apple.com/auth/keys', array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['keys'] ) || ! is_array( $body['keys'] ) ) {
			return new WP_Error( 'hwbl_apple_jwks', __( 'Could not load Apple public keys.', 'hidden-word-bible-lessons' ), array( 'status' => 502 ) );
		}
		$keys = $body['keys'];
		set_transient( 'hwbl_apple_jwks', $keys, HOUR_IN_SECONDS );
		return $keys;
	}

	/**
	 * @param array<string, mixed> $jwk JWK.
	 * @return string|null PEM.
	 */
	private static function jwk_to_pem( array $jwk ) {
		$kty = isset( $jwk['kty'] ) ? (string) $jwk['kty'] : '';
		if ( 'RSA' === $kty ) {
			return self::rsa_jwk_to_pem( $jwk );
		}
		if ( 'EC' === $kty ) {
			return self::ec_jwk_to_pem( $jwk );
		}
		// Legacy: EC material without kty.
		if ( ! empty( $jwk['x'] ) && ! empty( $jwk['y'] ) ) {
			return self::ec_jwk_to_pem( $jwk );
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $jwk RSA JWK (n, e).
	 * @return string|null PEM.
	 */
	private static function rsa_jwk_to_pem( array $jwk ) {
		if ( empty( $jwk['n'] ) || empty( $jwk['e'] ) ) {
			return null;
		}
		$n = self::b64url_decode( (string) $jwk['n'] );
		$e = self::b64url_decode( (string) $jwk['e'] );
		if ( '' === $n || '' === $e ) {
			return null;
		}
		$modulus  = self::asn1_integer( $n );
		$exponent = self::asn1_integer( $e );
		$rsa_seq  = self::asn1_sequence( $modulus . $exponent );
		// rsaEncryption OID + NULL + BIT STRING wrapping RSAPublicKey.
		$alg_id   = hex2bin( '300d06092a864886f70d0101010500' );
		$bit_str  = "\x03" . self::asn1_length( strlen( $rsa_seq ) + 1 ) . "\x00" . $rsa_seq;
		$spki     = self::asn1_sequence( $alg_id . $bit_str );
		return "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $spki ), 64, "\n" ) . "-----END PUBLIC KEY-----\n";
	}

	/**
	 * @param array<string, mixed> $jwk EC JWK (x, y, crv).
	 * @return string|null PEM.
	 */
	private static function ec_jwk_to_pem( array $jwk ) {
		if ( empty( $jwk['x'] ) || empty( $jwk['y'] ) || empty( $jwk['crv'] ) || 'P-256' !== $jwk['crv'] ) {
			return null;
		}
		$x = self::b64url_decode( (string) $jwk['x'] );
		$y = self::b64url_decode( (string) $jwk['y'] );
		if ( 32 !== strlen( $x ) || 32 !== strlen( $y ) ) {
			return null;
		}
		// Uncompressed EC point + OID for prime256v1.
		$der  = hex2bin( '3059301306072a8648ce3d020106082a8648ce3d03010703420004' );
		$der .= $x . $y;
		return "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $der ), 64, "\n" ) . "-----END PUBLIC KEY-----\n";
	}

	/**
	 * @param string $bytes Unsigned big-endian integer bytes.
	 * @return string ASN.1 INTEGER.
	 */
	private static function asn1_integer( $bytes ) {
		$bytes = ltrim( $bytes, "\x00" );
		if ( '' === $bytes ) {
			$bytes = "\x00";
		}
		if ( ord( $bytes[0] ) > 0x7f ) {
			$bytes = "\x00" . $bytes;
		}
		return "\x02" . self::asn1_length( strlen( $bytes ) ) . $bytes;
	}

	/**
	 * @param string $contents Sequence contents.
	 * @return string ASN.1 SEQUENCE.
	 */
	private static function asn1_sequence( $contents ) {
		return "\x30" . self::asn1_length( strlen( $contents ) ) . $contents;
	}

	/**
	 * @param int $length Length.
	 * @return string ASN.1 length bytes.
	 */
	private static function asn1_length( $length ) {
		if ( $length < 0x80 ) {
			return chr( $length );
		}
		$bytes = ltrim( pack( 'N', $length ), "\x00" );
		return chr( 0x80 | strlen( $bytes ) ) . $bytes;
	}

	/**
	 * @param string $data Raw P1363 signature (r||s).
	 * @return string DER.
	 */
	private static function p1363_to_der( $data ) {
		if ( 64 !== strlen( $data ) ) {
			return $data;
		}
		$r = substr( $data, 0, 32 );
		$s = substr( $data, 32, 32 );
		$r = ltrim( $r, "\x00" );
		$s = ltrim( $s, "\x00" );
		if ( ord( $r[0] ) > 0x7f ) {
			$r = "\x00" . $r;
		}
		if ( ord( $s[0] ) > 0x7f ) {
			$s = "\x00" . $s;
		}
		return "\x30" . chr( 4 + strlen( $r ) + strlen( $s ) ) . "\x02" . chr( strlen( $r ) ) . $r . "\x02" . chr( strlen( $s ) ) . $s;
	}

	/**
	 * @param string $data Base64url.
	 * @return string
	 */
	private static function b64url_decode( $data ) {
		$remainder = strlen( $data ) % 4;
		if ( $remainder ) {
			$data .= str_repeat( '=', 4 - $remainder );
		}
		$decoded = base64_decode( strtr( $data, '-_', '+/' ), true );
		return false === $decoded ? '' : $decoded;
	}
}
