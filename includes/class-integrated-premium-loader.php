<?php
/**
 * Loads bundled advanced (formerly Premium) feature modules.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Integrated_Premium_Loader
 */
class HWBL_Integrated_Premium_Loader {

	/**
	 * Whether integrated merge mode is active.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( defined( 'HWBL_INTEGRATED_PREMIUM' ) && HWBL_INTEGRATED_PREMIUM ) {
			return true;
		}

		return (bool) apply_filters( 'hwbl_integrated_premium', true );
	}

	/**
	 * Bootstrap bundled modules when merge mode is on.
	 */
	public static function maybe_load() {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! defined( 'HWBL_INTEGRATED_PREMIUM' ) ) {
			define( 'HWBL_INTEGRATED_PREMIUM', true );
		}

		if ( defined( 'THW_PREMIUM_VERSION' ) ) {
			self::after_premium_loaded();
			return;
		}

		$paths = array(
			HWBL_PLUGIN_DIR . 'premium/the-hidden-word-premium.php',
			dirname( HWBL_PLUGIN_DIR ) . '/The-Hidden-Word-Premium/the-hidden-word-premium.php',
		);

		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				require_once $path;
				break;
			}
		}

		self::after_premium_loaded();
	}

	/**
	 * Post-load wiring: license bypass and engagement modules.
	 */
	private static function after_premium_loaded() {
		add_filter( 'thw_premium_is_licensed', '__return_true', 100 );
		add_action( 'init', array( __CLASS__, 'init_engagement_modules' ), 30 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_deactivate_legacy_premium' ), 1 );
	}

	/**
	 * Deactivate the legacy standalone Premium plugin when merge mode is on.
	 */
	public static function maybe_deactivate_legacy_premium() {
		if ( ! self::is_enabled() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$legacy = 'the-hidden-word-premium/the-hidden-word-premium.php';
		if ( is_plugin_active( $legacy ) ) {
			deactivate_plugins( $legacy, true );
			set_transient( 'hwbl_legacy_premium_deactivated', 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Engagement modules that live in the free plugin.
	 */
	public static function init_engagement_modules() {
		if ( ! self::is_enabled() ) {
			return;
		}

		$modules = array(
			'HWBL_Cohort_Leaderboard',
			'HWBL_AI_Assistant_Unified',
			'HWBL_Personalized_Digest',
			'HWBL_Translation_Comparison',
			'HWBL_Memorization_Audio',
			'HWBL_PWA',
		);

		foreach ( $modules as $class ) {
			if ( class_exists( $class ) && method_exists( $class, 'init' ) ) {
				call_user_func( array( $class, 'init' ) );
			}
		}
	}
}
