<?php
/**
 * Android Restore Credentials (Credential Manager) relying party.
 *
 * Mints WebAuthn create/get options and verifies registrations/assertions so the
 * companion can zero-tap sign-in after an Android device migration. On success,
 * issues the same one-time claim code as Apple/Google Sign-In.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Auth_Restore
 */
class HWBL_Auth_Restore {

	const META_CREDS       = '_hwbl_restore_credentials';
	const CHALLENGE_PREFIX = 'hwbl_restore_chal_';
	const CHALLENGE_TTL    = 300;

	/**
	 * Register REST routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Routes under hwbl/v1.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/auth/restore/register/options',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_register_options' ),
				'permission_callback' => array( __CLASS__, 'require_logged_in' ),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/auth/restore/register/verify',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_register_verify' ),
				'permission_callback' => array( __CLASS__, 'require_logged_in' ),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/auth/restore/login/options',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_login_options' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/auth/restore/login/verify',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_login_verify' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function require_logged_in( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( is_user_logged_in() ) {
			return true;
		}
		return new WP_Error(
			'hwbl_restore_auth',
			__( 'You must be signed in to register a restore credential.', 'hidden-word-bible-lessons' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * POST register/options — PublicKeyCredentialCreationOptionsJSON.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_register_options( $request ) {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return new WP_Error( 'hwbl_restore_user', __( 'Not signed in.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		try {
			$webauthn = self::make_webauthn();
			$user_id  = self::encode_user_handle( $user->ID );
			$exclude  = array();
			foreach ( self::get_stored_credentials( $user->ID ) as $cred ) {
				if ( ! empty( $cred['id_bin'] ) ) {
					$exclude[] = $cred['id_bin'];
				}
			}

			// Resident key required so Credential Manager can restore silently.
			$args = $webauthn->getCreateArgs(
				$user_id,
				$user->user_login,
				$user->display_name ? $user->display_name : $user->user_login,
				60,
				true,
				false,
				false,
				$exclude
			);

			$challenge_b64 = self::buffer_to_b64( $webauthn->getChallenge() );
			$challenge_id  = self::store_challenge(
				array(
					'type'    => 'register',
					'user_id' => (int) $user->ID,
					'chal'    => $challenge_b64,
				)
			);

			return rest_ensure_response(
				array(
					'challengeId' => $challenge_id,
					'publicKey'   => $args->publicKey,
					'siteUrl'     => home_url(),
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'hwbl_restore_options', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * POST register/verify — store credential public key.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_register_verify( $request ) {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return new WP_Error( 'hwbl_restore_user', __( 'Not signed in.', 'hidden-word-bible-lessons' ), array( 'status' => 401 ) );
		}

		$challenge_id = sanitize_text_field( (string) $request->get_param( 'challengeId' ) );
		$credential   = $request->get_param( 'credential' );
		if ( ! is_array( $credential ) ) {
			$raw = $request->get_param( 'credentialJson' );
			if ( is_string( $raw ) ) {
				$decoded = json_decode( $raw, true );
				$credential = is_array( $decoded ) ? $decoded : null;
			}
		}
		if ( '' === $challenge_id || ! is_array( $credential ) ) {
			return new WP_Error( 'hwbl_restore_bad_request', __( 'Missing challengeId or credential.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$stored = self::consume_challenge( $challenge_id );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}
		if ( 'register' !== ( $stored['type'] ?? '' ) || (int) ( $stored['user_id'] ?? 0 ) !== (int) $user->ID ) {
			return new WP_Error( 'hwbl_restore_challenge', __( 'Invalid registration challenge.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		try {
			$webauthn = self::make_webauthn();
			$response = isset( $credential['response'] ) && is_array( $credential['response'] ) ? $credential['response'] : array();
			$client   = self::b64_to_bin( (string) ( $response['clientDataJSON'] ?? '' ) );
			$attest   = self::b64_to_bin( (string) ( $response['attestationObject'] ?? '' ) );
			$chal     = self::b64_to_bin( (string) $stored['chal'] );

			$data = $webauthn->processCreate( $client, $attest, $chal, false, true, false, false );

			$cred_id_bin = $data->credentialId;
			$public_key  = $data->credentialPublicKey;
			if ( ! $cred_id_bin || ! $public_key ) {
				return new WP_Error( 'hwbl_restore_create', __( 'Could not process restore credential.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
			}

			self::upsert_credential(
				$user->ID,
				array(
					'id'      => self::bin_to_b64( $cred_id_bin ),
					'id_bin'  => $cred_id_bin,
					'public'  => $public_key,
					'counter' => isset( $data->signatureCounter ) ? (int) $data->signatureCounter : 0,
					'created' => time(),
					'type'    => 'restore',
				)
			);

			return rest_ensure_response(
				array(
					'success' => true,
					'siteUrl' => home_url(),
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'hwbl_restore_verify', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	/**
	 * POST login/options — PublicKeyCredentialRequestOptionsJSON.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_login_options( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		try {
			$webauthn = self::make_webauthn();
			$args     = $webauthn->getGetArgs( array(), 60, true, true, true, true, true, false );
			$chal_b64 = self::buffer_to_b64( $webauthn->getChallenge() );
			$cid      = self::store_challenge(
				array(
					'type' => 'login',
					'chal' => $chal_b64,
				)
			);

			return rest_ensure_response(
				array(
					'challengeId' => $cid,
					'publicKey'   => $args->publicKey,
					'siteUrl'     => home_url(),
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'hwbl_restore_login_options', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * POST login/verify — assertion → claim code.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_login_verify( $request ) {
		$challenge_id = sanitize_text_field( (string) $request->get_param( 'challengeId' ) );
		$credential   = $request->get_param( 'credential' );
		if ( ! is_array( $credential ) ) {
			$raw = $request->get_param( 'credentialJson' );
			if ( is_string( $raw ) ) {
				$decoded = json_decode( $raw, true );
				$credential = is_array( $decoded ) ? $decoded : null;
			}
		}
		if ( '' === $challenge_id || ! is_array( $credential ) ) {
			return new WP_Error( 'hwbl_restore_bad_request', __( 'Missing challengeId or credential.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$stored = self::consume_challenge( $challenge_id );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}
		if ( 'login' !== ( $stored['type'] ?? '' ) ) {
			return new WP_Error( 'hwbl_restore_challenge', __( 'Invalid login challenge.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$cred_id_b64 = (string) ( $credential['id'] ?? $credential['rawId'] ?? '' );
		$cred_id_bin = self::b64_to_bin( $cred_id_b64 );
		if ( '' === $cred_id_bin ) {
			return new WP_Error( 'hwbl_restore_cred', __( 'Missing credential id.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$match = self::find_credential_by_id( $cred_id_bin );
		if ( ! $match ) {
			return new WP_Error( 'hwbl_restore_unknown', __( 'Unknown restore credential.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		$user = get_user_by( 'id', (int) $match['user_id'] );
		if ( ! $user ) {
			return new WP_Error( 'hwbl_restore_user', __( 'Account no longer exists.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}

		try {
			$webauthn = self::make_webauthn();
			$response = isset( $credential['response'] ) && is_array( $credential['response'] ) ? $credential['response'] : array();
			$client   = self::b64_to_bin( (string) ( $response['clientDataJSON'] ?? '' ) );
			$authdata = self::b64_to_bin( (string) ( $response['authenticatorData'] ?? '' ) );
			$sig      = self::b64_to_bin( (string) ( $response['signature'] ?? '' ) );
			$chal     = self::b64_to_bin( (string) $stored['chal'] );

			$webauthn->processGet(
				$client,
				$authdata,
				$sig,
				$match['cred']['public'],
				$chal,
				isset( $match['cred']['counter'] ) ? (int) $match['cred']['counter'] : null,
				false,
				true
			);

			// Bump counter when available.
			$creds = self::get_stored_credentials( $user->ID );
			foreach ( $creds as &$c ) {
				if ( ( $c['id'] ?? '' ) === ( $match['cred']['id'] ?? '' ) ) {
					$c['counter'] = isset( $c['counter'] ) ? ( (int) $c['counter'] + 1 ) : 1;
					$c['used']    = time();
				}
			}
			unset( $c );
			update_user_meta( $user->ID, self::META_CREDS, array_values( $creds ) );

			if ( ! class_exists( 'HWBL_App_Connect' ) ) {
				return new WP_Error(
					'hwbl_restore_connect',
					__( 'App connect is unavailable.', 'hidden-word-bible-lessons' ),
					array( 'status' => 500 )
				);
			}

			$issued = HWBL_App_Connect::issue_auth_code_for_user( (int) $user->ID );
			if ( is_wp_error( $issued ) ) {
				return $issued;
			}

			return rest_ensure_response(
				array(
					'code'    => $issued['code'],
					'siteUrl' => home_url(),
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'hwbl_restore_assert', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	/**
	 * Delete all restore credentials for a user (account deletion).
	 *
	 * @param int $user_id User ID.
	 */
	public static function delete_for_user( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id > 0 ) {
			delete_user_meta( $user_id, self::META_CREDS );
		}
	}

	/**
	 * @return \lbuchs\WebAuthn\WebAuthn
	 */
	private static function make_webauthn() {
		self::load_library();
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host = is_string( $host ) ? $host : 'localhost';
		$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		if ( '' === $name ) {
			$name = 'The Hidden Word';
		}
		return new \lbuchs\WebAuthn\WebAuthn( $name, $host, true, array( 'none', 'packed', 'android-key', 'apple' ) );
	}

	/**
	 * Load vendored lbuchs WebAuthn.
	 */
	private static function load_library() {
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$base = HWBL_PLUGIN_DIR . 'includes/lib/webauthn/';
		require_once $base . 'WebAuthn.php';
		$loaded = true;
	}

	/**
	 * Binary user handle encoding site + WP user id.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function encode_user_handle( $user_id ) {
		$payload = wp_json_encode(
			array(
				'site' => home_url(),
				'uid'  => (int) $user_id,
			)
		);
		return (string) $payload;
	}

	/**
	 * @param array<string,mixed> $data Challenge payload.
	 * @return string Challenge id.
	 */
	private static function store_challenge( array $data ) {
		$id = wp_generate_password( 32, false, false );
		set_transient( self::CHALLENGE_PREFIX . $id, $data, self::CHALLENGE_TTL );
		return $id;
	}

	/**
	 * @param string $id Challenge id.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function consume_challenge( $id ) {
		$key = self::CHALLENGE_PREFIX . $id;
		$val = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $val ) ) {
			return new WP_Error( 'hwbl_restore_challenge', __( 'Challenge expired or missing.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		return $val;
	}

	/**
	 * @param int $user_id User ID.
	 * @return array<int,array<string,mixed>>
	 */
	private static function get_stored_credentials( $user_id ) {
		$raw = get_user_meta( (int) $user_id, self::META_CREDS, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) || empty( $row['id'] ) || empty( $row['public'] ) ) {
				continue;
			}
			if ( empty( $row['id_bin'] ) ) {
				$row['id_bin'] = self::b64_to_bin( (string) $row['id'] );
			}
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * Keep a single restore credential per user (Play: one account per app).
	 *
	 * @param int                 $user_id User ID.
	 * @param array<string,mixed> $cred    Credential row.
	 */
	private static function upsert_credential( $user_id, array $cred ) {
		// Binary blobs cannot live in usermeta as-is when serialized poorly — store id as b64 only.
		$store = array(
			'id'      => (string) $cred['id'],
			'public'  => (string) $cred['public'],
			'counter' => (int) ( $cred['counter'] ?? 0 ),
			'created' => (int) ( $cred['created'] ?? time() ),
			'type'    => 'restore',
		);
		update_user_meta( (int) $user_id, self::META_CREDS, array( $store ) );
	}

	/**
	 * @param string $cred_id_bin Binary credential id.
	 * @return array{user_id:int,cred:array<string,mixed>}|null
	 */
	private static function find_credential_by_id( $cred_id_bin ) {
		$needle = self::bin_to_b64( $cred_id_bin );
		// Prefer current blog users with the meta; fall back to a direct query.
		$q = new WP_User_Query(
			array(
				'meta_key'     => self::META_CREDS,
				'meta_compare' => 'EXISTS',
				'number'       => 500,
				'fields'       => 'ID',
				'count_total'  => false,
			)
		);
		foreach ( (array) $q->get_results() as $uid ) {
			foreach ( self::get_stored_credentials( (int) $uid ) as $cred ) {
				if ( ( $cred['id'] ?? '' ) === $needle ) {
					return array(
						'user_id' => (int) $uid,
						'cred'    => $cred,
					);
				}
			}
		}
		return null;
	}

	/**
	 * @param mixed $buf ByteBuffer|string.
	 * @return string base64url
	 */
	private static function buffer_to_b64( $buf ) {
		if ( is_object( $buf ) && method_exists( $buf, 'getBinaryString' ) ) {
			return self::bin_to_b64( $buf->getBinaryString() );
		}
		if ( is_string( $buf ) ) {
			return self::bin_to_b64( $buf );
		}
		return '';
	}

	/**
	 * @param string $bin Binary.
	 * @return string base64url
	 */
	private static function bin_to_b64( $bin ) {
		return rtrim( strtr( base64_encode( $bin ), '+/', '-_' ), '=' );
	}

	/**
	 * @param string $b64 base64 or base64url.
	 * @return string
	 */
	private static function b64_to_bin( $b64 ) {
		$b64 = strtr( (string) $b64, '-_', '+/' );
		$pad = strlen( $b64 ) % 4;
		if ( $pad ) {
			$b64 .= str_repeat( '=', 4 - $pad );
		}
		$out = base64_decode( $b64, true );
		return false === $out ? '' : $out;
	}
}
