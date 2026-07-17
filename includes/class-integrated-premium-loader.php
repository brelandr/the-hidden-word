<?php
/**
 * Loads Premium features from the bundled add-on when merge mode is enabled.
 *
 * Set HWBL_INTEGRATED_PREMIUM to true (or filter hwbl_integrated_premium) after
 * WordPress.org approval to fold Premium into the free plugin without license gates.
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

		return (bool) apply_filters( 'hwbl_integrated_premium', false );
	}

	/**
	 * Bootstrap Premium from sibling directory when merge mode is on.
	 */
	public static function maybe_load() {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( defined( 'THW_PREMIUM_VERSION' ) ) {
			return;
		}

		$paths = array(
			dirname( HWBL_PLUGIN_DIR ) . '/The-Hidden-Word-Premium/the-hidden-word-premium.php',
			HWBL_PLUGIN_DIR . 'premium/the-hidden-word-premium.php',
		);

		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				if ( ! defined( 'HWBL_INTEGRATED_PREMIUM' ) ) {
					define( 'HWBL_INTEGRATED_PREMIUM', true );
				}
				require_once $path;
				break;
			}
		}

		if ( class_exists( 'THW_Premium_Updater' ) ) {
			remove_action( 'init', array( 'THW_Premium_Updater', 'init' ) );
			remove_filter( 'pre_set_site_transient_update_plugins', array( 'THW_Premium_Updater', 'check_update' ) );
		}

		if ( class_exists( 'THW_Premium_License' ) ) {
			add_filter( 'thw_premium_is_licensed', '__return_true', 100 );
		}

		add_action( 'init', array( __CLASS__, 'init_engagement_modules' ), 30 );
	}

	/**
	 * Phase 5 engagement modules (leaderboards, digests, PWA, etc.).
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
