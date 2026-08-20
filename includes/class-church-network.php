<?php
/**
 * Church directory hub + registration for the companion app.
 *
 * Hub mode (thehiddenword.org): hosts searchable church listings + join page.
 * Church sites: Connect to Network admin action + QR join link.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Church_Network
 */
class HWBL_Church_Network {

	const CPT            = 'hwbl_church';
	const META_SITE_URL  = '_hwbl_church_site_url';
	const META_CITY      = '_hwbl_church_city';
	const META_STATE     = '_hwbl_church_state';
	const META_ZIP       = '_hwbl_church_zip';
	const META_LAT       = '_hwbl_church_lat';
	const META_LNG       = '_hwbl_church_lng';
	const META_LOGO      = '_hwbl_church_logo';
	const META_STATUS    = '_hwbl_church_status';
	const OPT_STATUS       = 'hwbl_network_status';
	const OPT_HUB_TOKEN    = 'hwbl_network_hub_token';
	const OPT_IOS_STORE    = 'hwbl_app_store_ios_url';
	const OPT_ANDROID_STORE = 'hwbl_app_store_android_url';
	const DEFAULT_HUB      = 'https://thehiddenword.org';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'init', array( __CLASS__, 'register_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_join_page' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve_app_links' ), 0 );
		add_action( 'admin_post_hwbl_network_connect', array( __CLASS__, 'handle_connect' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_hub_settings' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ), 10, 2 );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
	}

	/**
	 * Hub home URL (no trailing slash).
	 *
	 * @return string
	 */
	public static function hub_url() {
		$url = (string) apply_filters( 'hwbl_network_hub_url', self::DEFAULT_HUB );
		$url = untrailingslashit( esc_url_raw( $url ) );
		return $url ? $url : self::DEFAULT_HUB;
	}

	/**
	 * Whether this install is the network hub.
	 *
	 * @return bool
	 */
	public static function is_hub() {
		$home = untrailingslashit( home_url() );
		$hub  = self::hub_url();
		$match = strtolower( $home ) === strtolower( $hub );
		return (bool) apply_filters( 'hwbl_network_is_hub', $match, $home, $hub );
	}

	/**
	 * Public join landing URL for a church site.
	 *
	 * @param string $site_url Church site URL.
	 * @return string
	 */
	public static function join_url_for_site( $site_url ) {
		$site_url = untrailingslashit( esc_url_raw( (string) $site_url ) );
		return self::hub_url() . '/app/join?url=' . rawurlencode( $site_url );
	}

	/**
	 * Deep link for companion church selection.
	 *
	 * @param string $site_url Church site URL.
	 * @return string
	 */
	public static function church_deep_link( $site_url ) {
		$site_url = untrailingslashit( esc_url_raw( (string) $site_url ) );
		return 'hwbl://church?url=' . rawurlencode( $site_url );
	}

	/**
	 * Register CPT on hub only.
	 */
	public static function register_cpt() {
		if ( ! self::is_hub() ) {
			return;
		}

		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'          => __( 'Network Churches', 'hidden-word-bible-lessons' ),
					'singular_name' => __( 'Network Church', 'hidden-word-bible-lessons' ),
					'add_new_item'  => __( 'Add Church', 'hidden-word-bible-lessons' ),
					'edit_item'     => __( 'Edit Church', 'hidden-word-bible-lessons' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=hwbl_lesson',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/network/churches',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_list_churches' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'zip'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'lat'    => array(
						'type' => 'number',
					),
					'lng'    => array(
						'type' => 'number',
					),
					'radius' => array(
						'type'    => 'number',
						'default' => 50,
					),
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/network/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_register_church' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/network/join-code',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_create_join_code' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/network/claim',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_claim_join_code' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'code' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Create a short deferred-install join code for a church site URL.
	 *
	 * @param string $site_url Church site URL.
	 * @return string Six-character code.
	 */
	public static function create_join_code( $site_url ) {
		$site_url = untrailingslashit( esc_url_raw( (string) $site_url ) );
		$code     = strtoupper( substr( preg_replace( '/[^A-Z0-9]/i', '', wp_generate_password( 12, false, false ) ), 0, 6 ) );
		if ( strlen( $code ) < 6 ) {
			$code = strtoupper( substr( md5( $site_url . microtime( true ) ), 0, 6 ) );
		}
		set_transient( 'hwbl_join_' . $code, $site_url, WEEK_IN_SECONDS );
		return $code;
	}

	/**
	 * POST create join code (hub only). Body: { siteUrl }.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_create_join_code( $request ) {
		if ( ! self::is_hub() ) {
			return new WP_Error( 'hwbl_not_hub', __( 'Not the network hub.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$rate = self::check_join_code_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$params   = $request->get_json_params();
		$params   = is_array( $params ) ? $params : $request->get_params();
		$site_url = isset( $params['siteUrl'] ) ? untrailingslashit( esc_url_raw( (string) $params['siteUrl'] ) ) : '';
		if ( ! $site_url || ! preg_match( '#^https://#i', $site_url ) ) {
			return new WP_Error( 'hwbl_invalid_site', __( 'A valid HTTPS siteUrl is required.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		$code = self::create_join_code( $site_url );
		return new WP_REST_Response(
			array(
				'code'     => $code,
				'siteUrl'  => $site_url,
				'deepLink' => self::church_deep_link( $site_url ),
				'expires'  => WEEK_IN_SECONDS,
			),
			201
		);
	}

	/**
	 * Limit public join-code creation (per IP, per hour).
	 *
	 * @return true|WP_Error
	 */
	public static function check_join_code_rate_limit() {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}
		if ( ! $ip ) {
			$ip = 'unknown';
		}

		$max   = (int) apply_filters( 'hwbl_join_code_rate_limit', 30 );
		$max   = $max > 0 ? $max : 30;
		$key   = 'hwbl_join_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return new WP_Error(
				'hwbl_rate_limited',
				__( 'Too many join codes requested. Try again later.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * GET claim a deferred join code.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_claim_join_code( $request ) {
		if ( ! self::is_hub() ) {
			return new WP_Error( 'hwbl_not_hub', __( 'Not the network hub.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		$code = strtoupper( preg_replace( '/[^A-Z0-9]/i', '', (string) $request->get_param( 'code' ) ) );
		if ( strlen( $code ) < 4 ) {
			return new WP_Error( 'hwbl_invalid_code', __( 'Enter a valid church code.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}
		$site_url = get_transient( 'hwbl_join_' . $code );
		if ( ! is_string( $site_url ) || ! $site_url ) {
			return new WP_Error( 'hwbl_code_expired', __( 'That code is invalid or expired.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		return new WP_REST_Response(
			array(
				'code'     => $code,
				'siteUrl'  => untrailingslashit( $site_url ),
				'deepLink' => self::church_deep_link( $site_url ),
			)
		);
	}

	/**
	 * GET searchable active churches (hub only; empty elsewhere).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_list_churches( $request ) {
		if ( ! self::is_hub() ) {
			return new WP_REST_Response(
				array(
					'churches' => array(),
					'hub'      => false,
				)
			);
		}

		$q      = trim( (string) $request->get_param( 'q' ) );
		$zip    = trim( (string) $request->get_param( 'zip' ) );
		$lat    = $request->get_param( 'lat' );
		$lng    = $request->get_param( 'lng' );
		$radius = (float) $request->get_param( 'radius' );
		if ( $radius <= 0 ) {
			$radius = 50;
		}
		$use_geo = null !== $lat && null !== $lng && '' !== (string) $lat && '' !== (string) $lng;
		$lat_f   = $use_geo ? (float) $lat : 0.0;
		$lng_f   = $use_geo ? (float) $lng : 0.0;

		$query = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => self::META_STATUS,
						'value' => 'active',
					),
				),
			)
		);

		$churches = array();
		$needle   = $q ? strtolower( $q ) : '';
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$site = untrailingslashit( (string) get_post_meta( $post->ID, self::META_SITE_URL, true ) );
			if ( ! $site ) {
				continue;
			}
			$name       = get_the_title( $post );
			$city       = (string) get_post_meta( $post->ID, self::META_CITY, true );
			$state      = (string) get_post_meta( $post->ID, self::META_STATE, true );
			$church_zip = (string) get_post_meta( $post->ID, self::META_ZIP, true );
			$church_lat = (float) get_post_meta( $post->ID, self::META_LAT, true );
			$church_lng = (float) get_post_meta( $post->ID, self::META_LNG, true );
			if ( $zip && 0 !== strcasecmp( $zip, $church_zip ) && false === stripos( $church_zip, $zip ) ) {
				continue;
			}
			if ( $needle ) {
				$haystack = strtolower(
					implode(
						' ',
						array(
							$name,
							$city,
							$state,
							$church_zip,
							$site,
						)
					)
				);
				if ( false === strpos( $haystack, $needle ) ) {
					continue;
				}
			}
			$distance = null;
			if ( $use_geo ) {
				if ( ! $church_lat && ! $church_lng ) {
					continue;
				}
				$distance = self::haversine_miles( $lat_f, $lng_f, $church_lat, $church_lng );
				if ( $distance > $radius ) {
					continue;
				}
			}
			$churches[] = array(
				'id'       => (int) $post->ID,
				'name'     => $name,
				'city'     => $city,
				'state'    => $state,
				'zip'      => $church_zip,
				'lat'      => $church_lat ? $church_lat : null,
				'lng'      => $church_lng ? $church_lng : null,
				'distance' => null !== $distance ? round( $distance, 1 ) : null,
				'siteUrl'  => $site,
				'logoUrl'  => (string) get_post_meta( $post->ID, self::META_LOGO, true ),
			);
		}

		if ( $use_geo ) {
			usort(
				$churches,
				static function ( $a, $b ) {
					$da = isset( $a['distance'] ) ? (float) $a['distance'] : PHP_FLOAT_MAX;
					$db = isset( $b['distance'] ) ? (float) $b['distance'] : PHP_FLOAT_MAX;
					return $da <=> $db;
				}
			);
		}

		return new WP_REST_Response(
			array(
				'churches' => $churches,
				'hub'      => true,
			)
		);
	}

	/**
	 * Great-circle distance in miles.
	 *
	 * @param float $lat1 Lat A.
	 * @param float $lng1 Lng A.
	 * @param float $lat2 Lat B.
	 * @param float $lng2 Lng B.
	 * @return float
	 */
	public static function haversine_miles( $lat1, $lng1, $lat2, $lng2 ) {
		$earth = 3958.8;
		$d_lat = deg2rad( (float) $lat2 - (float) $lat1 );
		$d_lng = deg2rad( (float) $lng2 - (float) $lng1 );
		$a     = sin( $d_lat / 2 ) * sin( $d_lat / 2 )
			+ cos( deg2rad( (float) $lat1 ) ) * cos( deg2rad( (float) $lat2 ) )
			* sin( $d_lng / 2 ) * sin( $d_lng / 2 );
		$c     = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
		return $earth * $c;
	}

	/**
	 * POST register a church into the hub directory.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_register_church( $request ) {
		if ( ! self::is_hub() ) {
			return new WP_Error(
				'hwbl_not_hub',
				__( 'This site is not the Hidden Word network hub.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$rate = self::check_register_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$params   = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_params();
		}

		$hub_token = (string) get_option( self::OPT_HUB_TOKEN, '' );
		if ( $hub_token ) {
			$provided = isset( $params['token'] ) ? (string) $params['token'] : '';
			if ( ! hash_equals( $hub_token, $provided ) ) {
				return new WP_Error(
					'hwbl_forbidden',
					__( 'Invalid network registration token.', 'hidden-word-bible-lessons' ),
					array( 'status' => 403 )
				);
			}
		}

		$site_url = isset( $params['siteUrl'] ) ? untrailingslashit( esc_url_raw( (string) $params['siteUrl'] ) ) : '';
		$name     = isset( $params['name'] ) ? sanitize_text_field( (string) $params['name'] ) : '';
		$city     = isset( $params['city'] ) ? sanitize_text_field( (string) $params['city'] ) : '';
		$state    = isset( $params['state'] ) ? sanitize_text_field( (string) $params['state'] ) : '';
		$zip      = isset( $params['zip'] ) ? sanitize_text_field( (string) $params['zip'] ) : '';
		$logo     = isset( $params['logoUrl'] ) ? esc_url_raw( (string) $params['logoUrl'] ) : '';

		if ( ! $site_url || ! preg_match( '#^https://#i', $site_url ) ) {
			return new WP_Error(
				'hwbl_invalid_site',
				__( 'A valid HTTPS siteUrl is required.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$host_check = self::assert_public_https_host( $site_url );
		if ( is_wp_error( $host_check ) ) {
			return $host_check;
		}

		$verified = self::verify_remote_app_config( $site_url );
		if ( is_wp_error( $verified ) ) {
			return $verified;
		}

		if ( ! $name && ! empty( $verified['name'] ) ) {
			$name = sanitize_text_field( (string) $verified['name'] );
		}
		if ( ! $name ) {
			$name = wp_parse_url( $site_url, PHP_URL_HOST ) ?: $site_url;
		}
		if ( ! $logo && ! empty( $verified['logoUrl'] ) ) {
			$logo = esc_url_raw( (string) $verified['logoUrl'] );
		}

		$existing_id = self::find_church_by_site_url( $site_url );
		$postarr     = array(
			'post_type'   => self::CPT,
			'post_title'  => $name,
			'post_status' => 'publish',
		);

		if ( $existing_id ) {
			$postarr['ID'] = $existing_id;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, self::META_SITE_URL, $site_url );
		update_post_meta( $post_id, self::META_CITY, $city );
		update_post_meta( $post_id, self::META_STATE, $state );
		update_post_meta( $post_id, self::META_ZIP, $zip );
		update_post_meta( $post_id, self::META_LOGO, $logo );

		$current_status = (string) get_post_meta( $post_id, self::META_STATUS, true );
		if ( ! in_array( $current_status, array( 'active', 'pending' ), true ) ) {
			// First registration lands pending for hub review; re-register keeps status.
			update_post_meta( $post_id, self::META_STATUS, 'pending' );
			$current_status = 'pending';
		}

		/**
		 * Filter whether a newly verified registration should auto-activate.
		 *
		 * @param bool $auto_active Default false.
		 * @param int  $post_id     Church post ID.
		 */
		if ( apply_filters( 'hwbl_network_auto_activate', false, (int) $post_id ) ) {
			update_post_meta( $post_id, self::META_STATUS, 'active' );
			$current_status = 'active';
		}

		return new WP_REST_Response(
			array(
				'ok'       => true,
				'id'       => (int) $post_id,
				'status'   => $current_status,
				'siteUrl'  => $site_url,
				'name'     => $name,
			),
			$existing_id ? 200 : 201
		);
	}

	/**
	 * Limit public church registration attempts (per IP, per hour).
	 *
	 * @return true|WP_Error
	 */
	public static function check_register_rate_limit() {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}
		if ( ! $ip ) {
			$ip = 'unknown';
		}

		$max   = (int) apply_filters( 'hwbl_register_rate_limit', 10 );
		$max   = $max > 0 ? $max : 10;
		$key   = 'hwbl_reg_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return new WP_Error(
				'hwbl_rate_limited',
				__( 'Too many registration attempts. Try again later.', 'hidden-word-bible-lessons' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Reject localhost / private hosts for remote verification.
	 *
	 * @param string $site_url Candidate church URL.
	 * @return true|WP_Error
	 */
	public static function assert_public_https_host( $site_url ) {
		$host = (string) wp_parse_url( $site_url, PHP_URL_HOST );
		$host = strtolower( $host );
		if ( ! $host ) {
			return new WP_Error(
				'hwbl_invalid_site',
				__( 'A valid HTTPS siteUrl is required.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		if (
			'localhost' === $host
			|| preg_match( '/\.local$/', $host )
			|| preg_match( '/^(127\.|10\.|192\.168\.|169\.254\.)/', $host )
			|| preg_match( '/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host )
		) {
			return new WP_Error(
				'hwbl_private_host',
				__( 'Church site URL must be a public HTTPS hostname.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Fetch and validate remote app-config.
	 *
	 * @param string $site_url Site URL.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function verify_remote_app_config( $site_url ) {
		$url      = untrailingslashit( $site_url ) . '/wp-json/hwbl/v1/app-config';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'             => 12,
				'redirection'         => 2,
				'limit_response_size' => 65536,
				'headers'             => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'hwbl_verify_failed',
				__( 'Could not reach the church site app-config endpoint.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || ! is_array( $body ) || empty( $body['siteUrl'] ) ) {
			return new WP_Error(
				'hwbl_verify_failed',
				__( 'Church site did not return a valid Hidden Word app-config.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		$remote = untrailingslashit( esc_url_raw( (string) $body['siteUrl'] ) );
		if ( strtolower( $remote ) !== strtolower( untrailingslashit( $site_url ) ) ) {
			return new WP_Error(
				'hwbl_verify_mismatch',
				__( 'Remote app-config siteUrl does not match the registration URL.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		return $body;
	}

	/**
	 * @param string $site_url Site URL.
	 * @return int
	 */
	private static function find_church_by_site_url( $site_url ) {
		$query = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_SITE_URL,
				'meta_value'     => untrailingslashit( $site_url ),
				'no_found_rows'  => true,
			)
		);
		return ! empty( $query->posts[0] ) ? (int) $query->posts[0] : 0;
	}

	/**
	 * Join page rewrite.
	 */
	public static function register_rewrite() {
		if ( ! self::is_hub() ) {
			return;
		}
		add_rewrite_rule( '^app/join/?$', 'index.php?hwbl_app_join=1', 'top' );
		add_rewrite_rule( '^\.well-known/apple-app-site-association$', 'index.php?hwbl_aasa=1', 'top' );
		add_rewrite_rule( '^\.well-known/assetlinks\.json$', 'index.php?hwbl_assetlinks=1', 'top' );
		if ( (string) get_option( 'hwbl_network_rewrite_flushed' ) !== '2' ) {
			flush_rewrite_rules( false );
			update_option( 'hwbl_network_rewrite_flushed', '2', false );
		}
	}

	/**
	 * @param array<int, string> $vars Query vars.
	 * @return array<int, string>
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'hwbl_app_join';
		$vars[] = 'hwbl_aasa';
		$vars[] = 'hwbl_assetlinks';
		return $vars;
	}

	/**
	 * Serve Apple / Android app-link association files on the hub.
	 *
	 * Team ID can be supplied via filter or option once the Apple developer
	 * team is known: update_option( 'hwbl_apple_team_id', 'XXXXXXXXXX' ).
	 */
	public static function maybe_serve_app_links() {
		if ( ! self::is_hub() ) {
			return;
		}

		if ( get_query_var( 'hwbl_aasa' ) ) {
			$team_id = (string) apply_filters(
				'hwbl_apple_team_id',
				(string) get_option( 'hwbl_apple_team_id', '' )
			);
			$app_id  = $team_id ? $team_id . '.org.thehiddenword.companion' : 'org.thehiddenword.companion';
			$payload = array(
				'applinks' => array(
					'apps'    => array(),
					'details' => array(
						array(
							'appID' => $app_id,
							'paths' => array(
								'/app/join',
								'/app/join/*',
								'/bible-plan',
								'/bible-plan/*',
								'/reading-plans',
								'/reading-plans/*',
								'/faith-formation',
								'/faith-formation/*',
							),
						),
					),
				),
			);
			nocache_headers();
			status_header( 200 );
			header( 'Content-Type: application/json' );
			echo wp_json_encode( $payload );
			exit;
		}

		if ( get_query_var( 'hwbl_assetlinks' ) ) {
			$sha = (string) apply_filters(
				'hwbl_android_sha256_cert_fingerprints',
				(string) get_option( 'hwbl_android_sha256_cert_fingerprints', '' )
			);
			$payload = array(
				array(
					'relation' => array( 'delegate_permission/common.handle_all_urls' ),
					'target'   => array(
						'namespace'                => 'android_app',
						'package_name'             => 'org.thehiddenword.companion',
						'sha256_cert_fingerprints' => $sha ? array_values( array_filter( array_map( 'trim', explode( ',', $sha ) ) ) ) : array(),
					),
				),
			);
			nocache_headers();
			status_header( 200 );
			header( 'Content-Type: application/json' );
			echo wp_json_encode( $payload );
			exit;
		}
	}

	/**
	 * Render hub join landing (QR / store fallback).
	 */
	public static function maybe_render_join_page() {
		if ( ! self::is_hub() || ! get_query_var( 'hwbl_app_join' ) ) {
			return;
		}

		$site_url = isset( $_GET['url'] ) ? untrailingslashit( esc_url_raw( wp_unslash( (string) $_GET['url'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$deep     = $site_url ? self::church_deep_link( $site_url ) : 'hwbl://church';
		$name     = get_bloginfo( 'name' );
		$join_code = $site_url ? self::create_join_code( $site_url ) : '';

		if ( $site_url ) {
			setcookie(
				'hwbl_pending_church',
				$site_url,
				time() + WEEK_IN_SECONDS,
				COOKIEPATH,
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);
			if ( $join_code ) {
				setcookie(
					'hwbl_pending_code',
					$join_code,
					time() + WEEK_IN_SECONDS,
					COOKIEPATH,
					COOKIE_DOMAIN,
					is_ssl(),
					false
				);
			}
		}

		nocache_headers();
		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );

		$ios = (string) apply_filters(
			'hwbl_app_store_ios_url',
			(string) get_option( 'hwbl_app_store_ios_url', '' )
		);
		$android = (string) apply_filters(
			'hwbl_app_store_android_url',
			(string) get_option( 'hwbl_app_store_android_url', '' )
		);
		$escaped_deep = esc_url( $deep );

		echo '<!DOCTYPE html><html><head><meta charset="utf-8" />';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
		echo '<title>' . esc_html__( 'Open Hidden Word', 'hidden-word-bible-lessons' ) . '</title>';
		$qr_js   = esc_url( HWBL_PLUGIN_URL . 'assets/js/qrcode-generator.min.js' );
		$qr_boot = esc_url( HWBL_PLUGIN_URL . 'assets/js/hwbl-admin-qr.js' );

		echo '<style>
			body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;margin:0;background:#f6f1e8;color:#1f2a2e}
			.wrap{max-width:420px;margin:0 auto;padding:2.5rem 1.25rem}
			h1{font-size:1.6rem;margin:0 0 .5rem}
			p{line-height:1.5;color:#4a585e}
			.btn{display:block;text-align:center;padding:.9rem 1rem;border-radius:12px;margin:.6rem 0;text-decoration:none;font-weight:700;border:0;width:100%;cursor:pointer;font-size:1rem}
			.primary{background:#1f2a2e;color:#fff}
			.secondary{background:#fff;color:#1f2a2e;border:1px solid #c9d0d3}
			.meta{font-size:.85rem;word-break:break-all}
			.code{font-size:2rem;letter-spacing:.2em;font-weight:800;margin:1rem 0;color:#1f2a2e}
			.qr{display:flex;justify-content:center;margin:1.25rem 0}
			.toast{min-height:1.25rem;font-size:.9rem;color:#2f6f4e;font-weight:600}
		</style>';
		echo '<script src="' . $qr_js . '"></script>';
		echo '<script src="' . $qr_boot . '"></script>';
		if ( $site_url ) {
			// Try native app once; keep page visible so installers can copy code / scan QR.
			echo '<script>
				(function(){
					var deep=' . wp_json_encode( $deep ) . ';
					var code=' . wp_json_encode( $join_code ) . ';
					try{
						if(navigator.clipboard){
							if(code){ navigator.clipboard.writeText(code); }
							else if(deep){ navigator.clipboard.writeText(deep); }
						}
					}catch(e){}
					setTimeout(function(){ try{ window.location.href=deep; }catch(e){} }, 700);
					window.hwblCopy = function(text, label){
						var el=document.getElementById("hwbl-toast");
						function done(ok){
							if(el){ el.textContent = ok ? (label||"Copied") : "Copy failed — select the text manually."; }
						}
						if(navigator.clipboard&&navigator.clipboard.writeText){
							navigator.clipboard.writeText(text).then(function(){ done(true); }).catch(function(){ done(false); });
							return;
						}
						done(false);
					};
				})();
			</script>';
		}
		echo '</head><body><div class="wrap">';
		echo '<h1>' . esc_html__( 'Open in Hidden Word', 'hidden-word-bible-lessons' ) . '</h1>';
		if ( $site_url ) {
			echo '<p>' . esc_html__( 'Opening the companion app for your church. If nothing happens, install the app, then use the code or Open app button.', 'hidden-word-bible-lessons' ) . '</p>';
			echo '<p class="meta">' . esc_html( $site_url ) . '</p>';
			echo '<div class="qr" data-hwbl-qr="' . esc_attr( $deep ) . '" data-hwbl-qr-size="5" data-hwbl-qr-alt="' . esc_attr__( 'QR code to open this church in Hidden Word', 'hidden-word-bible-lessons' ) . '"></div>';
			echo '<a class="btn primary" href="' . $escaped_deep . '">' . esc_html__( 'Open app', 'hidden-word-bible-lessons' ) . '</a>';
			if ( $join_code ) {
				echo '<p>' . esc_html__( 'New install? After downloading, enter this church code in the app:', 'hidden-word-bible-lessons' ) . '</p>';
				echo '<p class="code" id="hwbl-join-code">' . esc_html( $join_code ) . '</p>';
				echo '<button type="button" class="btn secondary" onclick="hwblCopy(' . wp_json_encode( $join_code ) . ', ' . wp_json_encode( __( 'Church code copied', 'hidden-word-bible-lessons' ) ) . ')">' . esc_html__( 'Copy church code', 'hidden-word-bible-lessons' ) . '</button>';
				echo '<button type="button" class="btn secondary" onclick="hwblCopy(' . wp_json_encode( $deep ) . ', ' . wp_json_encode( __( 'App link copied', 'hidden-word-bible-lessons' ) ) . ')">' . esc_html__( 'Copy app link', 'hidden-word-bible-lessons' ) . '</button>';
				echo '<p class="toast" id="hwbl-toast"></p>';
				echo '<p class="meta">' . esc_html__( 'Codes expire in 7 days. On first open, the app can also read a copied code from your clipboard.', 'hidden-word-bible-lessons' ) . '</p>';
			}
		} else {
			echo '<p>' . esc_html__( 'Choose your church in the Hidden Word app, or ask your church for their join QR code.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		if ( $ios ) {
			echo '<a class="btn secondary" href="' . esc_url( $ios ) . '">' . esc_html__( 'Get on the App Store', 'hidden-word-bible-lessons' ) . '</a>';
		}
		if ( $android ) {
			echo '<a class="btn secondary" href="' . esc_url( $android ) . '">' . esc_html__( 'Get on Google Play', 'hidden-word-bible-lessons' ) . '</a>';
		}
		if ( ! $ios && ! $android ) {
			echo '<p>' . esc_html__( 'App Store links will appear here once Hidden Word is published.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		echo '<p class="meta">' . esc_html( sprintf( /* translators: %s: site name */ __( 'Powered by %s', 'hidden-word-bible-lessons' ), $name ) ) . '</p>';
		echo '</div></body></html>';
		exit;
	}

	/**
	 * Register hub-only options.
	 */
	public static function register_hub_settings() {
		if ( ! self::is_hub() ) {
			return;
		}
		register_setting(
			'hwbl_network_hub',
			self::OPT_HUB_TOKEN,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'hwbl_network_hub',
			self::OPT_IOS_STORE,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
		register_setting(
			'hwbl_network_hub',
			self::OPT_ANDROID_STORE,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
		register_setting(
			'hwbl_network_hub',
			'hwbl_apple_team_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'hwbl_network_hub',
			'hwbl_android_sha256_cert_fingerprints',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}

	/**
	 * Admin Connect to Network handler (church sites).
	 */
	public static function handle_connect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'hidden-word-bible-lessons' ) );
		}
		check_admin_referer( 'hwbl_network_connect' );

		$city  = isset( $_POST['hwbl_church_city'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['hwbl_church_city'] ) ) : '';
		$state = isset( $_POST['hwbl_church_state'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['hwbl_church_state'] ) ) : '';
		$zip   = isset( $_POST['hwbl_church_zip'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['hwbl_church_zip'] ) ) : '';
		$token = isset( $_POST['hwbl_hub_token'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['hwbl_hub_token'] ) ) : '';

		$config = class_exists( 'HWBL_App_Config' ) ? HWBL_App_Config::get_config() : array();
		$body   = array(
			'name'    => isset( $config['name'] ) ? $config['name'] : get_bloginfo( 'name' ),
			'siteUrl' => isset( $config['siteUrl'] ) ? $config['siteUrl'] : untrailingslashit( home_url() ),
			'logoUrl' => isset( $config['logoUrl'] ) ? $config['logoUrl'] : '',
			'city'    => $city,
			'state'   => $state,
			'zip'     => $zip,
		);
		if ( $token ) {
			$body['token'] = $token;
		}

		$response = wp_remote_post(
			self::hub_url() . '/wp-json/hwbl/v1/network/register',
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			update_option(
				self::OPT_STATUS,
				array(
					'state'   => 'error',
					'message' => $response->get_error_message(),
					'at'      => time(),
				),
				false
			);
		} else {
			$code = (int) wp_remote_retrieve_response_code( $response );
			$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			if ( $code >= 200 && $code < 300 && is_array( $data ) ) {
				update_option(
					self::OPT_STATUS,
					array(
						'state'   => isset( $data['status'] ) ? sanitize_key( (string) $data['status'] ) : 'pending',
						'message' => '',
						'at'      => time(),
						'hubId'   => isset( $data['id'] ) ? (int) $data['id'] : 0,
					),
					false
				);
			} else {
				$msg = is_array( $data ) && ! empty( $data['message'] ) ? (string) $data['message'] : __( 'Registration failed.', 'hidden-word-bible-lessons' );
				update_option(
					self::OPT_STATUS,
					array(
						'state'   => 'error',
						'message' => $msg,
						'at'      => time(),
					),
					false
				);
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=thw-premium-settings&hwbl_network=1' ) );
		exit;
	}

	/**
	 * Current local connect status.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_local_status() {
		$status = get_option( self::OPT_STATUS, array() );
		return is_array( $status ) ? $status : array();
	}

	/**
	 * Admin meta boxes for hub listings.
	 */
	public static function add_meta_boxes() {
		if ( ! self::is_hub() ) {
			return;
		}
		add_meta_box(
			'hwbl_church_details',
			__( 'Church details', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_meta_box' ),
			self::CPT,
			'normal',
			'high'
		);
	}

	/**
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'hwbl_save_church', 'hwbl_church_nonce' );
		$site   = (string) get_post_meta( $post->ID, self::META_SITE_URL, true );
		$city   = (string) get_post_meta( $post->ID, self::META_CITY, true );
		$state  = (string) get_post_meta( $post->ID, self::META_STATE, true );
		$zip    = (string) get_post_meta( $post->ID, self::META_ZIP, true );
		$lat    = (string) get_post_meta( $post->ID, self::META_LAT, true );
		$lng    = (string) get_post_meta( $post->ID, self::META_LNG, true );
		$logo   = (string) get_post_meta( $post->ID, self::META_LOGO, true );
		$status = (string) get_post_meta( $post->ID, self::META_STATUS, true );
		if ( ! $status ) {
			$status = 'pending';
		}
		?>
		<p><label><?php esc_html_e( 'Site URL', 'hidden-word-bible-lessons' ); ?><br />
			<input type="url" class="widefat" name="hwbl_church_site_url" value="<?php echo esc_attr( $site ); ?>" /></label></p>
		<p><label><?php esc_html_e( 'City', 'hidden-word-bible-lessons' ); ?><br />
			<input type="text" class="widefat" name="hwbl_church_city" value="<?php echo esc_attr( $city ); ?>" /></label></p>
		<p><label><?php esc_html_e( 'State', 'hidden-word-bible-lessons' ); ?><br />
			<input type="text" class="widefat" name="hwbl_church_state" value="<?php echo esc_attr( $state ); ?>" /></label></p>
		<p><label><?php esc_html_e( 'ZIP', 'hidden-word-bible-lessons' ); ?><br />
			<input type="text" class="widefat" name="hwbl_church_zip" value="<?php echo esc_attr( $zip ); ?>" /></label></p>
		<p><label><?php esc_html_e( 'Latitude (optional, for Near me)', 'hidden-word-bible-lessons' ); ?><br />
			<input type="text" class="widefat" name="hwbl_church_lat" value="<?php echo esc_attr( $lat ); ?>" placeholder="32.3526" /></label></p>
		<p><label><?php esc_html_e( 'Longitude (optional, for Near me)', 'hidden-word-bible-lessons' ); ?><br />
			<input type="text" class="widefat" name="hwbl_church_lng" value="<?php echo esc_attr( $lng ); ?>" placeholder="-90.8779" /></label></p>
		<p><label><?php esc_html_e( 'Logo URL', 'hidden-word-bible-lessons' ); ?><br />
			<input type="url" class="widefat" name="hwbl_church_logo" value="<?php echo esc_attr( $logo ); ?>" /></label></p>
		<p><label><?php esc_html_e( 'Status', 'hidden-word-bible-lessons' ); ?><br />
			<select name="hwbl_church_status">
				<option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'hidden-word-bible-lessons' ); ?></option>
				<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'hidden-word-bible-lessons' ); ?></option>
			</select></label></p>
		<?php
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['hwbl_church_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hwbl_church_nonce'] ) ), 'hwbl_save_church' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::META_SITE_URL, untrailingslashit( esc_url_raw( wp_unslash( (string) ( $_POST['hwbl_church_site_url'] ?? '' ) ) ) ) );
		update_post_meta( $post_id, self::META_CITY, sanitize_text_field( wp_unslash( (string) ( $_POST['hwbl_church_city'] ?? '' ) ) ) );
		update_post_meta( $post_id, self::META_STATE, sanitize_text_field( wp_unslash( (string) ( $_POST['hwbl_church_state'] ?? '' ) ) ) );
		update_post_meta( $post_id, self::META_ZIP, sanitize_text_field( wp_unslash( (string) ( $_POST['hwbl_church_zip'] ?? '' ) ) ) );
		$lat = trim( (string) wp_unslash( $_POST['hwbl_church_lat'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$lng = trim( (string) wp_unslash( $_POST['hwbl_church_lng'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' !== $lat && is_numeric( $lat ) ) {
			update_post_meta( $post_id, self::META_LAT, (float) $lat );
		} else {
			delete_post_meta( $post_id, self::META_LAT );
		}
		if ( '' !== $lng && is_numeric( $lng ) ) {
			update_post_meta( $post_id, self::META_LNG, (float) $lng );
		} else {
			delete_post_meta( $post_id, self::META_LNG );
		}
		update_post_meta( $post_id, self::META_LOGO, esc_url_raw( wp_unslash( (string) ( $_POST['hwbl_church_logo'] ?? '' ) ) ) );
		$status = sanitize_key( (string) ( $_POST['hwbl_church_status'] ?? 'pending' ) );
		if ( ! in_array( $status, array( 'pending', 'active' ), true ) ) {
			$status = 'pending';
		}
		update_post_meta( $post_id, self::META_STATUS, $status );
	}

	/**
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function admin_columns( $columns ) {
		$columns['hwbl_site']   = __( 'Site', 'hidden-word-bible-lessons' );
		$columns['hwbl_place']  = __( 'Location', 'hidden-word-bible-lessons' );
		$columns['hwbl_status'] = __( 'Status', 'hidden-word-bible-lessons' );
		return $columns;
	}

	/**
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public static function admin_column_content( $column, $post_id ) {
		if ( 'hwbl_site' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, self::META_SITE_URL, true ) );
		}
		if ( 'hwbl_place' === $column ) {
			$parts = array_filter(
				array(
					(string) get_post_meta( $post_id, self::META_CITY, true ),
					(string) get_post_meta( $post_id, self::META_STATE, true ),
					(string) get_post_meta( $post_id, self::META_ZIP, true ),
				)
			);
			echo esc_html( implode( ', ', $parts ) );
		}
		if ( 'hwbl_status' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, self::META_STATUS, true ) );
		}
	}
}
