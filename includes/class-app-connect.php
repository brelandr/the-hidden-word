<?php
/**
 * Companion account connect: web login/register → one-time auth code → app.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_App_Connect
 */
class HWBL_App_Connect {

	const OPT_SELF_SIGNUP = 'hwbl_companion_self_signup';
	const OPT_REWRITE     = 'hwbl_app_connect_rewrite_flushed';
	const CODE_TTL        = 300; // 5 minutes.
	const APP_PASSWORD_NAME = 'Hidden Word Companion';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_connect_page' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Whether companion self-signup is enabled on this site.
	 *
	 * @return bool
	 */
	public static function self_signup_enabled() {
		return (bool) get_option( self::OPT_SELF_SIGNUP, false );
	}

	/**
	 * Public connect URL for this site.
	 *
	 * @return string
	 */
	public static function connect_url() {
		// Trailing slash matches WordPress canonical redirect.
		return trailingslashit( home_url( '/app/connect' ) );
	}

	/**
	 * Register settings option.
	 */
	public static function register_settings() {
		register_setting(
			'hwbl_settings',
			self::OPT_SELF_SIGNUP,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);
	}

	/**
	 * Rewrite for /app/connect on every site.
	 */
	public static function register_rewrite() {
		add_rewrite_rule( '^app/connect/?$', 'index.php?hwbl_app_connect=1', 'top' );
		if ( (string) get_option( self::OPT_REWRITE ) !== '2' ) {
			flush_rewrite_rules( false );
			update_option( self::OPT_REWRITE, '2', false );
		}
	}

	/**
	 * @param array<int, string> $vars Query vars.
	 * @return array<int, string>
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'hwbl_app_connect';
		return $vars;
	}

	/**
	 * REST: claim one-time auth code (public).
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/auth/claim',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_claim_auth_code' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Allow only companion / Expo deep-link return bases (never https websites).
	 *
	 * @param string $url Candidate return URL from the app.
	 * @return string
	 */
	public static function sanitize_return_url( $url ) {
		$url = trim( (string) $url );
		if ( ! $url ) {
			return '';
		}
		// App may send already-encoded values.
		$url = rawurldecode( $url );
		if ( preg_match( '#^hwbl://#i', $url ) ) {
			return $url;
		}
		// Expo Go / dev client: exp://192.168.x.x:8081/--/auth
		if ( preg_match( '#^exps?://#i', $url ) ) {
			return $url;
		}
		return '';
	}

	/**
	 * Build the deep link that returns to the companion (or Expo Go).
	 *
	 * @param string $site        Site URL.
	 * @param string $code        Claim code.
	 * @param string $return_base Optional return base from the app (Expo Go).
	 * @return string
	 */
	public static function build_auth_deep_link( $site, $code, $return_base = '' ) {
		$query = 'site=' . rawurlencode( (string) $site ) . '&code=' . rawurlencode( (string) $code );
		$base  = self::sanitize_return_url( $return_base );
		if ( $base ) {
			$sep = false !== strpos( $base, '?' ) ? '&' : '?';
			return $base . $sep . $query;
		}
		return 'hwbl://auth?' . $query;
	}

	/**
	 * Return URL supplied by the companion (GET return= or POST hwbl_return).
	 *
	 * @return string
	 */
	public static function request_return_url() {
		$from_post = isset( $_POST['hwbl_return'] ) ? (string) wp_unslash( $_POST['hwbl_return'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$from_get  = isset( $_GET['return'] ) ? (string) wp_unslash( $_GET['return'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return self::sanitize_return_url( $from_post ? $from_post : $from_get );
	}

	/**
	 * Create an Application Password + one-time claim code for a user.
	 *
	 * @param int    $user_id     User ID.
	 * @param string $return_base Optional Expo/hwbl return base from the app.
	 * @return array{code:string,deepLink:string,expires:int}|WP_Error
	 */
	public static function issue_auth_code_for_user( $user_id, $return_base = '' ) {
		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'hwbl_user_missing',
				__( 'User not found.', 'hidden-word-bible-lessons' ),
				array( 'status' => 404 )
			);
		}

		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			return new WP_Error(
				'hwbl_app_passwords_unavailable',
				__( 'Application Passwords are not available on this site. Enable HTTPS and update WordPress.', 'hidden-word-bible-lessons' ),
				array( 'status' => 500 )
			);
		}

		// Remove prior companion passwords so members don't accumulate many.
		if (
			method_exists( 'WP_Application_Passwords', 'get_user_application_passwords' )
			&& method_exists( 'WP_Application_Passwords', 'delete_application_password' )
		) {
			$existing = WP_Application_Passwords::get_user_application_passwords( $user_id );
			if ( is_array( $existing ) ) {
				foreach ( $existing as $item ) {
					$name = isset( $item['name'] ) ? (string) $item['name'] : '';
					$uuid = isset( $item['uuid'] ) ? (string) $item['uuid'] : '';
					if ( $uuid && 0 === strcasecmp( $name, self::APP_PASSWORD_NAME ) ) {
						WP_Application_Passwords::delete_application_password( $user_id, $uuid );
					}
				}
			}
		}

		$created = WP_Application_Passwords::create_new_application_password(
			$user_id,
			array(
				'name' => self::APP_PASSWORD_NAME,
			)
		);

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		$password = is_array( $created ) && isset( $created[0] ) ? (string) $created[0] : '';
		if ( ! $password ) {
			return new WP_Error(
				'hwbl_app_password_failed',
				__( 'Could not create an Application Password.', 'hidden-word-bible-lessons' ),
				array( 'status' => 500 )
			);
		}

		$code = self::generate_code();
		$site = untrailingslashit( home_url() );
		set_transient(
			'hwbl_auth_' . $code,
			array(
				'siteUrl'     => $site,
				'username'    => (string) $user->user_login,
				'appPassword' => $password,
				'userId'      => $user_id,
			),
			self::CODE_TTL
		);

		$deep = self::build_auth_deep_link( $site, $code, $return_base );

		return array(
			'code'     => $code,
			'deepLink' => $deep,
			'expires'  => self::CODE_TTL,
		);
	}

	/**
	 * Generate a claim code.
	 *
	 * @return string
	 */
	public static function generate_code() {
		$code = strtoupper( substr( preg_replace( '/[^A-Z0-9]/i', '', wp_generate_password( 16, false, false ) ), 0, 12 ) );
		if ( strlen( $code ) < 8 ) {
			$code = strtoupper( substr( md5( (string) microtime( true ) ), 0, 12 ) );
		}
		return $code;
	}

	/**
	 * POST /hwbl/v1/auth/claim — body { code }.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_claim_auth_code( $request ) {
		$rate = self::check_claim_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : $request->get_params();
		$code   = isset( $params['code'] ) ? strtoupper( preg_replace( '/[^A-Z0-9]/i', '', (string) $params['code'] ) ) : '';
		if ( strlen( $code ) < 8 ) {
			return new WP_Error(
				'hwbl_invalid_code',
				__( 'Enter a valid connect code.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$key  = 'hwbl_auth_' . $code;
		$data = get_transient( $key );
		delete_transient( $key );

		if ( ! is_array( $data ) || empty( $data['username'] ) || empty( $data['appPassword'] ) ) {
			return new WP_Error(
				'hwbl_code_expired',
				__( 'That connect code is invalid or expired. Sign in on the website again.', 'hidden-word-bible-lessons' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response(
			array(
				'siteUrl'     => isset( $data['siteUrl'] ) ? (string) $data['siteUrl'] : untrailingslashit( home_url() ),
				'username'    => (string) $data['username'],
				'appPassword' => (string) $data['appPassword'],
			),
			200
		);
	}

	/**
	 * Rate-limit auth claims per IP.
	 *
	 * @return true|WP_Error
	 */
	public static function check_claim_rate_limit() {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}
		if ( ! $ip ) {
			$ip = 'unknown';
		}

		$max   = (int) apply_filters( 'hwbl_auth_claim_rate_limit', 40 );
		$max   = $max > 0 ? $max : 40;
		$key   = 'hwbl_auth_claim_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return new WP_Error(
				'hwbl_rate_limited',
				__( 'Too many connect attempts. Try again later.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Rate-limit self-signup per IP.
	 *
	 * @return true|WP_Error
	 */
	public static function check_signup_rate_limit() {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}
		if ( ! $ip ) {
			$ip = 'unknown';
		}

		$max   = (int) apply_filters( 'hwbl_companion_signup_rate_limit', 8 );
		$max   = $max > 0 ? $max : 8;
		$key   = 'hwbl_signup_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return new WP_Error(
				'hwbl_rate_limited',
				__( 'Too many sign-up attempts. Try again later.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Register a subscriber for companion self-signup.
	 *
	 * @param string $username Username.
	 * @param string $email    Email.
	 * @param string $password Password.
	 * @return int|WP_Error User ID.
	 */
	public static function register_member( $username, $email, $password ) {
		if ( ! self::self_signup_enabled() ) {
			return new WP_Error(
				'hwbl_signup_disabled',
				__( 'Self sign-up is disabled on this site. Ask your church administrator for an account.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		$rate = self::check_signup_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$username = sanitize_user( (string) $username, true );
		$email    = sanitize_email( (string) $email );
		$password = (string) $password;

		if ( strlen( $username ) < 3 ) {
			return new WP_Error( 'hwbl_invalid_username', __( 'Choose a username with at least 3 characters.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'hwbl_invalid_email', __( 'Enter a valid email address.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		if ( strlen( $password ) < 8 ) {
			return new WP_Error( 'hwbl_weak_password', __( 'Use a password with at least 8 characters.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		if ( username_exists( $username ) ) {
			return new WP_Error( 'hwbl_username_exists', __( 'That username is already taken.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		if ( email_exists( $email ) ) {
			return new WP_Error( 'hwbl_email_exists', __( 'That email is already registered.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$user_id = wp_insert_user(
			array(
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => $password,
				'role'       => 'subscriber',
			)
		);

		return $user_id;
	}

	/**
	 * Render /app/connect landing and handle login/register/connect POSTs.
	 */
	public static function maybe_render_connect_page() {
		$is_connect = (bool) get_query_var( 'hwbl_app_connect' );
		if ( ! $is_connect ) {
			// Fallback when rewrite rules were not flushed yet.
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$is_connect = (bool) preg_match( '#/app/connect/?(\?|$)#', $uri );
		}
		if ( ! $is_connect ) {
			return;
		}

		$message = '';
		$error   = '';
		$issued  = null;

		if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : '' ) ) {
			$result = self::handle_connect_post();
			if ( is_wp_error( $result ) ) {
				$error = $result->get_error_message();
			} elseif ( is_array( $result ) && isset( $result['deepLink'] ) ) {
				$issued = $result;
			} elseif ( is_string( $result ) ) {
				$message = $result;
			}
		} elseif ( is_user_logged_in() && isset( $_GET['auto'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$issued_or_err = self::issue_auth_code_for_user( get_current_user_id(), self::request_return_url() );
			if ( is_wp_error( $issued_or_err ) ) {
				$error = $issued_or_err->get_error_message();
			} else {
				$issued = $issued_or_err;
			}
		}

		self::render_connect_html( $message, $error, $issued, self::request_return_url() );
		exit;
	}

	/**
	 * Process connect page forms.
	 *
	 * @return array<string,mixed>|string|WP_Error
	 */
	private static function handle_connect_post() {
		$action = isset( $_POST['hwbl_connect_action'] ) ? sanitize_key( (string) wp_unslash( $_POST['hwbl_connect_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce  = isset( $_POST['hwbl_connect_nonce'] ) ? (string) wp_unslash( $_POST['hwbl_connect_nonce'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $action || ! wp_verify_nonce( $nonce, 'hwbl_app_connect' ) ) {
			return new WP_Error( 'hwbl_bad_nonce', __( 'Security check failed. Refresh and try again.', 'hidden-word-bible-lessons' ) );
		}

		$return_base = self::request_return_url();

		if ( 'login' === $action ) {
			$login = isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['log'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$pass  = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$user  = wp_signon(
				array(
					'user_login'    => $login,
					'user_password' => $pass,
					'remember'      => true,
				),
				is_ssl()
			);
			if ( is_wp_error( $user ) ) {
				return $user;
			}
			wp_set_current_user( $user->ID );
			return self::issue_auth_code_for_user( $user->ID, $return_base );
		}

		if ( 'register' === $action ) {
			$username = isset( $_POST['user_login'] ) ? (string) wp_unslash( $_POST['user_login'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$email    = isset( $_POST['user_email'] ) ? (string) wp_unslash( $_POST['user_email'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$password = isset( $_POST['user_pass'] ) ? (string) wp_unslash( $_POST['user_pass'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$user_id  = self::register_member( $username, $email, $password );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, true, is_ssl() );
			return self::issue_auth_code_for_user( (int) $user_id, $return_base );
		}

		if ( 'issue' === $action ) {
			if ( ! is_user_logged_in() ) {
				return new WP_Error( 'hwbl_login_required', __( 'Sign in first.', 'hidden-word-bible-lessons' ) );
			}
			return self::issue_auth_code_for_user( get_current_user_id(), $return_base );
		}

		return new WP_Error( 'hwbl_unknown_action', __( 'Unknown action.', 'hidden-word-bible-lessons' ) );
	}

	/**
	 * Output standalone HTML for /app/connect.
	 *
	 * @param string                   $message     Info message.
	 * @param string                   $error       Error message.
	 * @param array<string,mixed>|null $issued      Issued code payload.
	 * @param string                   $return_base Optional Expo/hwbl return base.
	 */
	private static function render_connect_html( $message, $error, $issued, $return_base = '' ) {
		$site_name   = get_bloginfo( 'name' );
		$logged_in   = is_user_logged_in();
		$signup      = self::self_signup_enabled();
		$user        = $logged_in ? wp_get_current_user() : null;
		$return_base = self::sanitize_return_url( $return_base );
		$return_field = $return_base
			? '<input type="hidden" name="hwbl_return" value="' . esc_attr( $return_base ) . '" />'
			: '';

		nocache_headers();
		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );

		echo '<!DOCTYPE html><html><head><meta charset="utf-8" />';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
		echo '<title>' . esc_html__( 'Connect Hidden Word', 'hidden-word-bible-lessons' ) . '</title>';
		echo '<style>
			body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;margin:0;background:#f6f1e8;color:#1f2a2e}
			.wrap{max-width:420px;margin:0 auto;padding:2.5rem 1.25rem}
			h1{font-size:1.55rem;margin:0 0 .35rem}
			h2{font-size:1.1rem;margin:1.5rem 0 .6rem}
			p{line-height:1.5;color:#4a585e}
			label{display:block;font-weight:700;margin:.75rem 0 .35rem}
			input[type=text],input[type=email],input[type=password]{width:100%;box-sizing:border-box;padding:.75rem .85rem;border-radius:12px;border:1px solid #c9d0d3;font-size:1rem}
			.btn{display:block;text-align:center;padding:.9rem 1rem;border-radius:12px;margin:.75rem 0;text-decoration:none;font-weight:700;border:0;width:100%;cursor:pointer;font-size:1rem}
			.primary{background:#1f2a2e;color:#fff}
			.secondary{background:#fff;color:#1f2a2e;border:1px solid #c9d0d3}
			.error{background:#fde8e6;color:#8a1f11;padding:.75rem 1rem;border-radius:12px;margin:1rem 0}
			.ok{background:#e6f4ec;color:#1f6b4a;padding:.75rem 1rem;border-radius:12px;margin:1rem 0}
			.meta{font-size:.85rem;word-break:break-all}
			.divider{margin:1.5rem 0;border:0;border-top:1px solid #d9d2c5}
		</style>';

		if ( is_array( $issued ) && ! empty( $issued['deepLink'] ) ) {
			$deep = (string) $issued['deepLink'];
			echo '<script>
				(function(){
					var deep=' . wp_json_encode( $deep ) . ';
					setTimeout(function(){ try{ window.location.href=deep; }catch(e){} }, 400);
				})();
			</script>';
		}

		echo '</head><body><div class="wrap">';
		echo '<h1>' . esc_html__( 'Connect to Hidden Word', 'hidden-word-bible-lessons' ) . '</h1>';
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %s: site name */
				__( 'Sign in to %s, then return to the app. Your normal website password stays in the browser; the app receives a secure Application Password.', 'hidden-word-bible-lessons' ),
				$site_name
			)
		) . '</p>';

		if ( $error ) {
			echo '<div class="error">' . esc_html( $error ) . '</div>';
		}
		if ( $message ) {
			echo '<div class="ok">' . esc_html( $message ) . '</div>';
		}

		if ( is_array( $issued ) && ! empty( $issued['deepLink'] ) ) {
			// Do not use esc_url() — it strips hwbl:// and exp:// schemes.
			$deep = esc_attr( (string) $issued['deepLink'] );
			echo '<div class="ok">' . esc_html__( 'Connected. Opening the Hidden Word app…', 'hidden-word-bible-lessons' ) . '</div>';
			echo '<a class="btn primary" href="' . $deep . '">' . esc_html__( 'Open Hidden Word app', 'hidden-word-bible-lessons' ) . '</a>';
			echo '<p class="meta">' . esc_html__( 'If the app does not open, install Hidden Word, return here, and tap Open again. Codes expire in a few minutes.', 'hidden-word-bible-lessons' ) . '</p>';
			echo '</div></body></html>';
			return;
		}

		$logout_redirect = self::connect_url();
		if ( $return_base ) {
			$logout_redirect = add_query_arg( 'return', $return_base, $logout_redirect );
		}

		if ( $logged_in && $user ) {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: %s: username */
					__( 'Signed in as %s.', 'hidden-word-bible-lessons' ),
					$user->user_login
				)
			) . '</p>';
			echo '<form method="post">';
			wp_nonce_field( 'hwbl_app_connect', 'hwbl_connect_nonce' );
			echo $return_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_attr above.
			echo '<input type="hidden" name="hwbl_connect_action" value="issue" />';
			echo '<button class="btn primary" type="submit">' . esc_html__( 'Open Hidden Word app', 'hidden-word-bible-lessons' ) . '</button>';
			echo '</form>';
			echo '<p class="meta"><a href="' . esc_url( wp_logout_url( $logout_redirect ) ) . '">' . esc_html__( 'Use a different account', 'hidden-word-bible-lessons' ) . '</a></p>';
			echo '</div></body></html>';
			return;
		}

		echo '<h2>' . esc_html__( 'Sign in', 'hidden-word-bible-lessons' ) . '</h2>';
		echo '<form method="post">';
		wp_nonce_field( 'hwbl_app_connect', 'hwbl_connect_nonce' );
		echo $return_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_attr above.
		echo '<input type="hidden" name="hwbl_connect_action" value="login" />';
		echo '<label for="log">' . esc_html__( 'Username or email', 'hidden-word-bible-lessons' ) . '</label>';
		echo '<input id="log" name="log" type="text" autocomplete="username" required />';
		echo '<label for="pwd">' . esc_html__( 'Password', 'hidden-word-bible-lessons' ) . '</label>';
		echo '<input id="pwd" name="pwd" type="password" autocomplete="current-password" required />';
		echo '<button class="btn primary" type="submit">' . esc_html__( 'Sign in &amp; open app', 'hidden-word-bible-lessons' ) . '</button>';
		echo '</form>';

		if ( $signup ) {
			echo '<hr class="divider" />';
			echo '<h2>' . esc_html__( 'Create an account', 'hidden-word-bible-lessons' ) . '</h2>';
			echo '<p>' . esc_html__( 'New here? Create a member account for this church site, then open the app.', 'hidden-word-bible-lessons' ) . '</p>';
			echo '<form method="post">';
			wp_nonce_field( 'hwbl_app_connect', 'hwbl_connect_nonce' );
			echo $return_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_attr above.
			echo '<input type="hidden" name="hwbl_connect_action" value="register" />';
			echo '<label for="user_login">' . esc_html__( 'Username', 'hidden-word-bible-lessons' ) . '</label>';
			echo '<input id="user_login" name="user_login" type="text" autocomplete="username" required />';
			echo '<label for="user_email">' . esc_html__( 'Email', 'hidden-word-bible-lessons' ) . '</label>';
			echo '<input id="user_email" name="user_email" type="email" autocomplete="email" required />';
			echo '<label for="user_pass">' . esc_html__( 'Password', 'hidden-word-bible-lessons' ) . '</label>';
			echo '<input id="user_pass" name="user_pass" type="password" autocomplete="new-password" minlength="8" required />';
			echo '<button class="btn secondary" type="submit">' . esc_html__( 'Create account &amp; open app', 'hidden-word-bible-lessons' ) . '</button>';
			echo '</form>';
		} else {
			echo '<p class="meta">' . esc_html__( 'Need an account? Ask your church administrator to create a WordPress user for you, then return here to connect the app.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		echo '</div></body></html>';
	}
}
