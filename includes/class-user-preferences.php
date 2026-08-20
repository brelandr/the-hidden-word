<?php
/**
 * Logged-in user preferences for Bible translation (and tradition when allowed).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_User_Preferences
 */
class HWBL_User_Preferences {

	const META_TRANSLATION    = '_hwbl_preferred_translation';
	const META_EASY_READ      = '_hwbl_easy_read';
	const META_KIDS_MODE      = '_hwbl_kids_mode';
	const STORAGE_TRANSLATION = 'hwbl_preferred_translation';
	const STORAGE_TRADITION   = 'thw_ai_tradition_preset';
	const STORAGE_EASY_READ   = 'hwbl_easy_read';
	const STORAGE_KIDS_MODE   = 'hwbl_kids_mode';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_assets' ), 20 );
	}

	/**
	 * User meta key for preferred translation.
	 *
	 * @return string
	 */
	public static function translation_meta_key() {
		return self::META_TRANSLATION;
	}

	/**
	 * Whether a translation slug is available on this site.
	 *
	 * @param string $slug Translation slug.
	 * @return bool
	 */
	public static function is_valid_translation( $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug ) {
			return false;
		}

		if ( class_exists( 'HWBL_Bible_Reader' ) ) {
			$reader = HWBL_Bible_Reader::get_reader_translations();
			if ( is_array( $reader ) && isset( $reader[ $slug ] ) ) {
				return true;
			}
		}

		if ( class_exists( 'HWBL_Translation_Service' ) ) {
			$supported = HWBL_Translation_Service::instance()->get_supported_translations();
			if ( is_array( $supported ) && isset( $supported[ $slug ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Saved preferred translation for a user (empty when none/invalid).
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return string
	 */
	public static function get_preferred_translation( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id < 1 ) {
			return '';
		}

		$slug = sanitize_key( (string) get_user_meta( $user_id, self::META_TRANSLATION, true ) );
		return self::is_valid_translation( $slug ) ? $slug : '';
	}

	/**
	 * Persist preferred translation for a logged-in user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $slug    Translation slug.
	 * @return bool
	 */
	public static function set_preferred_translation( $user_id, $slug ) {
		$user_id = (int) $user_id;
		$slug    = sanitize_key( (string) $slug );
		if ( $user_id < 1 || ! self::is_valid_translation( $slug ) ) {
			return false;
		}

		update_user_meta( $user_id, self::META_TRANSLATION, $slug );
		return true;
	}

	/**
	 * Resolve translation: user preference → site default → first available.
	 *
	 * @param array<string, string>|null $available Optional slug => label map to constrain choice.
	 * @param int                        $user_id   User ID (0 = current).
	 * @return string
	 */
	public static function resolve_translation( $available = null, $user_id = 0 ) {
		$user = self::get_preferred_translation( $user_id );
		if ( '' !== $user && ( null === $available || isset( $available[ $user ] ) ) ) {
			return $user;
		}

		$site = sanitize_key( (string) get_option( 'hwbl_active_translation', 'niv' ) );
		if ( '' !== $site && self::is_valid_translation( $site ) && ( null === $available || isset( $available[ $site ] ) ) ) {
			return $site;
		}

		if ( is_array( $available ) && ! empty( $available ) ) {
			$keys = array_keys( $available );
			return sanitize_key( (string) $keys[0] );
		}

		return $site ? $site : 'niv';
	}

	/**
	 * Config array for front-end script localization.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_script_config() {
		$user_tradition = function_exists( 'thw_premium_user_tradition_enabled' )
			? (bool) thw_premium_user_tradition_enabled()
			: false;

		return array(
			'restUrl'                     => esc_url_raw( rest_url( 'hwbl/v1/user-preferences' ) ),
			'nonce'                       => wp_create_nonce( 'wp_rest' ),
			'loggedIn'                    => is_user_logged_in(),
			'userTradition'               => $user_tradition,
			'preferredTranslation'        => self::get_preferred_translation(),
			'easyRead'                    => self::get_bool_pref( self::META_EASY_READ ),
			'kidsMode'                    => self::get_bool_pref( self::META_KIDS_MODE ),
			'translationStorageKey'       => self::STORAGE_TRANSLATION,
			'traditionStorageKey'         => self::STORAGE_TRADITION,
			'easyReadStorageKey'          => self::STORAGE_EASY_READ,
			'kidsModeStorageKey'          => self::STORAGE_KIDS_MODE,
			'legacyTranslationStorageKey' => 'thw_votd_translation',
		);
	}

	/**
	 * Read a boolean user-meta preference (current user).
	 *
	 * @param string $meta_key Meta key.
	 * @param int    $user_id  User ID (0 = current).
	 * @return bool
	 */
	public static function get_bool_pref( $meta_key, $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}
		return (bool) get_user_meta( $user_id, $meta_key, true );
	}

	/**
	 * Persist a boolean preference.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $value    Value.
	 */
	public static function set_bool_pref( $user_id, $meta_key, $value ) {
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return;
		}
		update_user_meta( $user_id, $meta_key, $value ? 1 : 0 );
	}

	/**
	 * Register front-end script.
	 */
	public static function register_assets() {
		wp_register_script(
			'hwbl-user-preferences',
			HWBL_PLUGIN_URL . 'public/js/user-preferences.js',
			array(),
			HWBL_VERSION,
			true
		);
	}

	/**
	 * Enqueue preferences helper on the front end.
	 */
	public static function maybe_enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script( 'hwbl-user-preferences' );
		wp_localize_script( 'hwbl-user-preferences', 'hwblUserPrefs', self::get_script_config() );
	}

	/**
	 * Available translations for preference pickers (slug => label).
	 *
	 * @return array<string, string>
	 */
	public static function get_available_translations() {
		$translations = array();

		if ( class_exists( 'HWBL_Bible_Reader' ) && HWBL_Bible_Reader::is_enabled() ) {
			$reader = HWBL_Bible_Reader::get_reader_translations();
			if ( is_array( $reader ) ) {
				$translations = array_merge( $translations, $reader );
			}
		}

		if ( class_exists( 'HWBL_Translation_Service' ) ) {
			$supported = HWBL_Translation_Service::instance()->get_supported_translations();
			if ( is_array( $supported ) ) {
				$translations = array_merge( $translations, $supported );
			}
		}

		if ( empty( $translations ) ) {
			$translations = array(
				'niv' => 'NIV',
				'kjv' => 'KJV',
				'web' => 'WEB',
			);
		}

		natcasesort( $translations );
		return $translations;
	}

	/**
	 * Build the GET/POST preferences payload for the current user.
	 *
	 * @param int $user_id User ID.
	 * @return array<string, mixed>
	 */
	public static function build_preferences_payload( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$translations = self::get_available_translations();
		$translation  = self::resolve_translation( $translations, $user_id );

		// Read the option directly so this works even if Premium helpers load late.
		if ( function_exists( 'thw_premium_user_tradition_enabled' ) ) {
			$user_tradition = (bool) thw_premium_user_tradition_enabled();
		} else {
			$user_tradition = (bool) rest_sanitize_boolean(
				get_option( 'thw_ai_allow_user_tradition', false )
			);
		}

		$tradition  = '';
		$traditions = array();
		if ( $user_tradition && function_exists( 'thw_premium_get_tradition_preset_choices' ) ) {
			$traditions = thw_premium_get_tradition_preset_choices( true );
			if ( function_exists( 'thw_premium_get_user_tradition_preset' ) ) {
				$tradition = thw_premium_get_user_tradition_preset( $user_id );
			}
		}

		$show_tradition = $user_tradition && count( $traditions ) >= 2;
		if ( function_exists( 'thw_premium_show_tradition_select' ) ) {
			$show_tradition = thw_premium_show_tradition_select();
		}

		return array(
			'translation'         => $translation,
			'tradition'           => $tradition,
			'userTradition'       => $show_tradition,
			'translations'        => $translations,
			'traditions'          => $traditions,
			'showTraditionPicker' => $show_tradition,
			'easyRead'            => self::get_bool_pref( self::META_EASY_READ, $user_id ),
			'kidsMode'            => self::get_bool_pref( self::META_KIDS_MODE, $user_id ),
		);
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		$permission = static function () {
			return is_user_logged_in();
		};

		$get_route = array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'rest_get' ),
			'permission_callback' => $permission,
		);

		$post_route = array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'rest_save' ),
			'permission_callback' => $permission,
			'args'                => array(
				'translation' => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				),
				'tradition'   => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				),
				'easyRead'    => array(
					'type'     => 'boolean',
					'required' => false,
				),
				'kidsMode'    => array(
					'type'     => 'boolean',
					'required' => false,
				),
			),
		);

		register_rest_route( 'hwbl/v1', '/user-preferences', $get_route );
		register_rest_route( 'thw/v1', '/user-preferences', $get_route );
		register_rest_route( 'hwbl/v1', '/user-preferences', $post_route );
		register_rest_route( 'thw/v1', '/user-preferences', $post_route );
	}

	/**
	 * REST: read current preferences and available choices.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_get() {
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return new WP_Error(
				'hwbl_prefs_login',
				__( 'You must be logged in to load preferences.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		return new WP_REST_Response( self::build_preferences_payload( $user_id ) );
	}

	/**
	 * REST: save translation and/or tradition preferences.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_save( $request ) {
		$user_id = get_current_user_id();
		if ( $user_id < 1 ) {
			return new WP_Error(
				'hwbl_prefs_login',
				__( 'You must be logged in to save preferences.', 'hidden-word-bible-lessons' ),
				array( 'status' => 401 )
			);
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_params();
		}
		$params = is_array( $params ) ? $params : array();

		$translation_in = isset( $params['translation'] ) ? sanitize_key( (string) $params['translation'] ) : '';
		$tradition_in   = isset( $params['tradition'] ) ? sanitize_key( (string) $params['tradition'] ) : '';

		$saved_translation = self::get_preferred_translation( $user_id );
		$saved_tradition   = '';

		if ( array_key_exists( 'easyRead', $params ) ) {
			self::set_bool_pref( $user_id, self::META_EASY_READ, rest_sanitize_boolean( $params['easyRead'] ) );
		}
		if ( array_key_exists( 'kidsMode', $params ) ) {
			self::set_bool_pref( $user_id, self::META_KIDS_MODE, rest_sanitize_boolean( $params['kidsMode'] ) );
		}

		if ( '' !== $translation_in ) {
			if ( ! self::is_valid_translation( $translation_in ) ) {
				return new WP_Error(
					'hwbl_prefs_invalid_translation',
					__( 'That Bible translation is not available.', 'hidden-word-bible-lessons' ),
					array( 'status' => 400 )
				);
			}
			self::set_preferred_translation( $user_id, $translation_in );
			$saved_translation = $translation_in;
		}

		if (
			'' !== $tradition_in
			&& function_exists( 'thw_premium_user_tradition_enabled' )
			&& thw_premium_user_tradition_enabled()
			&& function_exists( 'thw_premium_set_user_tradition_preset' )
		) {
			if ( thw_premium_set_user_tradition_preset( $user_id, $tradition_in ) ) {
				$saved_tradition = function_exists( 'thw_premium_sanitize_tradition_preset' )
					? thw_premium_sanitize_tradition_preset( $tradition_in )
					: $tradition_in;
			}
		} elseif (
			function_exists( 'thw_premium_get_user_tradition_preset' )
			&& function_exists( 'thw_premium_user_tradition_enabled' )
			&& thw_premium_user_tradition_enabled()
		) {
			$saved_tradition = thw_premium_get_user_tradition_preset( $user_id );
		}

		$payload = self::build_preferences_payload( $user_id );
		// Prefer just-saved values when present (resolve may differ if lists lag).
		if ( '' !== $saved_translation ) {
			$payload['translation'] = $saved_translation;
		}
		if ( '' !== $saved_tradition ) {
			$payload['tradition'] = $saved_tradition;
		}

		return new WP_REST_Response( $payload );
	}
}

/**
 * Saved preferred translation for a user (empty when none/invalid).
 *
 * @param int $user_id User ID (0 = current).
 * @return string
 */
function hwbl_get_user_preferred_translation( $user_id = 0 ) {
	return HWBL_User_Preferences::get_preferred_translation( $user_id );
}

/**
 * Persist preferred translation for a logged-in user.
 *
 * @param int    $user_id User ID.
 * @param string $slug    Translation slug.
 * @return bool
 */
function hwbl_set_user_preferred_translation( $user_id, $slug ) {
	return HWBL_User_Preferences::set_preferred_translation( $user_id, $slug );
}

/**
 * Resolve translation: user preference → site default → first available.
 *
 * @param array<string, string>|null $available Optional slug => label map.
 * @param int                        $user_id   User ID (0 = current).
 * @return string
 */
function hwbl_resolve_user_translation( $available = null, $user_id = 0 ) {
	return HWBL_User_Preferences::resolve_translation( $available, $user_id );
}
