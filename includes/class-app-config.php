<?php
/**
 * Public companion app-config REST endpoint.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_App_Config
 */
class HWBL_App_Config {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_brand_settings' ) );
	}

	/**
	 * Companion brand color options (any site).
	 */
	public static function register_brand_settings() {
		register_setting(
			'hwbl_app_brand',
			'hwbl_app_primary_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '',
			)
		);
		register_setting(
			'hwbl_app_brand',
			'hwbl_app_secondary_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '',
			)
		);
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$args = array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'rest_get_config' ),
			'permission_callback' => '__return_true',
		);

		register_rest_route( 'hwbl/v1', '/app-config', $args );

		if ( class_exists( 'HWBL_REST_Namespace_Bridge' ) ) {
			HWBL_REST_Namespace_Bridge::register_alias( 'thw/v1', 'hwbl/v1', '/app-config', $args );
		} else {
			register_rest_route( 'thw/v1', '/app-config', $args );
		}
	}

	/**
	 * Build public app-config payload for the companion.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_config() {
		$site_url = untrailingslashit( home_url() );
		$logo     = self::get_logo_url();
		$ask      = function_exists( 'thw_premium_get_ai_ask_audience' )
			? (string) thw_premium_get_ai_ask_audience()
			: 'logged_in';

		$bible_reader = class_exists( 'HWBL_Bible_Reader' ) && HWBL_Bible_Reader::is_enabled();
		$votd         = class_exists( 'THW_Premium_Verse_Of_The_Day' );
		$memorize     = class_exists( 'HWBL_Memorization_SRS' );
		$cohort       = class_exists( 'THW_Premium_Cohort' ) && class_exists( 'HWBL_Cohort_Leaderboard' );

		$primary   = (string) get_option( 'hwbl_app_primary_color', '' );
		$secondary = (string) get_option( 'hwbl_app_secondary_color', '' );

		return array(
			'name'           => (string) get_bloginfo( 'name' ),
			'siteUrl'        => $site_url,
			'logoUrl'        => $logo,
			'primaryColor'   => $primary,
			'secondaryColor' => $secondary,
			'features'       => array(
				'bibleReader'           => (bool) $bible_reader,
				'votd'                  => (bool) $votd,
				'memorize'              => (bool) $memorize,
				'cohort'                => (bool) $cohort,
				'askAudience'           => $ask,
				'studyFinder'           => class_exists( 'THW_Premium_AI_Study_Finder' ),
				'companionSelfSignup'   => class_exists( 'HWBL_App_Connect' )
					? HWBL_App_Connect::self_signup_enabled()
					: false,
				// BuddyPress/BuddyBoss activity share (thw/v1/share-activity).
				'communityShare'        => (
					( function_exists( 'bp_is_active' ) || function_exists( 'buddypress' ) )
					&& function_exists( 'bp_activity_add' )
				),
			),
			'deepLink'     => 'hwbl://church?url=' . rawurlencode( $site_url ),
			'joinUrl'      => class_exists( 'HWBL_Church_Network' )
				? HWBL_Church_Network::join_url_for_site( $site_url )
				: '',
			'connectUrl'   => class_exists( 'HWBL_App_Connect' )
				? HWBL_App_Connect::connect_url()
				: untrailingslashit( $site_url ) . '/app/connect',
		);
	}

	/**
	 * REST callback.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_get_config() {
		return new WP_REST_Response( self::get_config() );
	}

	/**
	 * Site logo or icon URL.
	 *
	 * @return string
	 */
	private static function get_logo_url() {
		$custom_logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
			if ( $url ) {
				return (string) $url;
			}
		}

		$icon = get_site_icon_url( 192 );
		return $icon ? (string) $icon : '';
	}
}
