<?php
/**
 * Bundled advanced feature modules for Hidden Word Bible Lessons.
 *
 * This file is NOT a standalone WordPress plugin. It is loaded by the free
 * plugin when HWBL_INTEGRATED_PREMIUM is enabled.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
if ( defined( 'THW_PREMIUM_VERSION' ) ) {
	return;
}

define( 'THW_PREMIUM_VERSION', '1.6.8' );
define( 'THW_FREE_MIN_VERSION', '1.4.0' );
define( 'THW_PREMIUM_FILE', __FILE__ );
define( 'THW_PREMIUM_DIR', plugin_dir_path( __FILE__ ) );
define( 'THW_PREMIUM_URL', plugin_dir_url( __FILE__ ) );
define( 'THW_PREMIUM_BASENAME', defined( 'HWBL_PLUGIN_BASENAME' ) ? HWBL_PLUGIN_BASENAME : plugin_basename( __FILE__ ) );
define( 'THW_FREE_PLUGIN_BASENAME', 'hidden-word-bible-lessons/hidden-word-bible-lessons.php' );

require_once THW_PREMIUM_DIR . 'includes/thw-premium-functions.php';

$thw_premium_composer = THW_PREMIUM_DIR . 'vendor/autoload.php';
if ( is_readable( $thw_premium_composer ) ) {
	require_once $thw_premium_composer;
}

require_once THW_PREMIUM_DIR . 'includes/votd/class-votd-cache.php';
require_once THW_PREMIUM_DIR . 'includes/votd/class-votd-fetch.php';
require_once THW_PREMIUM_DIR . 'includes/votd/class-votd-cron.php';
require_once THW_PREMIUM_DIR . 'includes/class-study-search.php';
require_once THW_PREMIUM_DIR . 'includes/class-study-rest.php';
require_once THW_PREMIUM_DIR . 'includes/class-study-shortcode.php';

require_once THW_PREMIUM_DIR . 'includes/class-license.php';
require_once THW_PREMIUM_DIR . 'includes/class-premium.php';

/**
 * Autoload premium classes.
 *
 * @param string $class Class name.
 */
if ( ! function_exists( 'thw_premium_autoload' ) ) {
	/**
	 * Autoload premium classes.
	 *
	 * @param string $class Class name.
	 */
	function thw_premium_autoload( $class ) {
		if ( 0 !== strpos( $class, 'THW_Premium_' ) && 'THW_Premium' !== $class ) {
			return;
		}

		$map = array(
			'THW_Premium'                       => 'class-premium.php',
			'THW_Premium_License'               => 'class-license.php',
			'THW_Premium_API_Bible'             => 'class-api-bible-provider.php',
			'THW_Premium_Biblia'                => 'class-biblia-provider.php',
			'THW_Premium_YouVersion'            => 'class-youversion-provider.php',
			'THW_Premium_Bible_Api_Diagnostics' => 'class-bible-api-diagnostics.php',
			'THW_Premium_Memorization'          => 'class-memorization-pro.php',
			'THW_Premium_Progress'              => 'class-progress.php',
			'THW_Premium_PDF_Export'            => 'class-pdf-export.php',
			'THW_Premium_AI_Assist'             => 'class-ai-assist.php',
			'THW_Premium_AI_Client'             => 'class-ai-client.php',
			'THW_Premium_AI_Explain'            => 'class-ai-explain.php',
			'THW_Premium_AI_Study_Finder'       => 'class-ai-study-finder.php',
			'THW_Premium_AI_Ask'                => 'class-ai-ask.php',
			'THW_Premium_Scheduler'             => 'class-scheduler-pro.php',
			'THW_Premium_BuddyPress'            => 'class-buddypress-bridge.php',
			'THW_Premium_Settings'              => 'class-premium-settings.php',
			'THW_Premium_Audio'                 => 'class-audio.php',
			'THW_Premium_Curriculum'            => 'class-curriculum-365.php',
			'THW_Premium_Digest'                => 'class-digest.php',
			'THW_Premium_Cohort'                => 'class-cohort.php',
			'THW_Premium_Video'                 => 'class-video.php',
			'THW_Premium_ICal_Export'           => 'class-ical-export.php',
			'THW_Premium_Track_Import'          => 'class-track-import.php',
			'THW_Premium_Analytics'             => 'class-analytics.php',
			'THW_Premium_Tradition_Packs'       => 'class-tradition-packs.php',
			'THW_Premium_Church_Subject_Rules'  => 'class-church-subject-rules.php',
			'THW_Premium_Verse_Of_The_Day'      => 'class-verse-of-the-day.php',
			'THW_Premium_VOTD_Cache'            => 'votd/class-votd-cache.php',
			'THW_Premium_VOTD_Fetch'            => 'votd/class-votd-fetch.php',
			'THW_Premium_Study_Search'          => 'class-study-search.php',
			'THW_Premium_Study_REST'            => 'class-study-rest.php',
			'THW_Premium_Study_Shortcode'       => 'class-study-shortcode.php',
			'THW_Premium_Votd_Explain_Store'           => 'class-votd-explain-store.php',
			'THW_Premium_Votd_Digest'                  => 'class-votd-digest.php',
			'THW_Premium_Bible_Reader_Explain'         => 'class-bible-reader-explain.php',
			'THW_Premium_Bible_Reader_Explain_Store'   => 'class-bible-reader-explain-store.php',
			'THW_Premium_Explain_Preload'               => 'class-explain-preload.php',
			'THW_Premium_Explain_Preload_Admin'         => 'class-explain-preload-admin.php',
			'THW_Premium_Explain_Packs'                 => 'class-explain-packs.php',
			'THW_Premium_Explain_Packs_Admin'           => 'class-explain-packs-admin.php',
			'THW_Premium_Explain_Packs_GitHub'          => 'class-explain-packs-github.php',
		);

		$name = str_replace( 'THW_Premium_', '', $class );
		if ( 'THW_Premium' === $class ) {
			$file = 'class-premium.php';
		} elseif ( isset( $map[ $class ] ) ) {
			$file = $map[ $class ];
		} else {
			$file = 'class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';
		}

		$paths = array(
			THW_PREMIUM_DIR . 'includes/' . $file,
			THW_PREMIUM_DIR . 'admin/' . $file,
		);

		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}

if ( function_exists( 'thw_premium_autoload' ) ) {
	spl_autoload_register( 'thw_premium_autoload' );
}

/**
 * Free plugin is always present when this file is loaded from integrated mode.
 *
 * @return bool
 */
if ( ! function_exists( 'thw_premium_is_free_active' ) ) {
	/**
	 * Free plugin is always present when this file is loaded from integrated mode.
	 *
	 * @return bool
	 */
	function thw_premium_is_free_active() {
		return defined( 'HWBL_VERSION' );
	}
}

/**
 * Check free plugin meets the minimum version for premium features.
 *
 * @return bool
 */
if ( ! function_exists( 'thw_premium_meets_free_requirement' ) ) {
	/**
	 * Check free plugin meets the minimum version for premium features.
	 *
	 * @return bool
	 */
	function thw_premium_meets_free_requirement() {
		if ( ! thw_premium_is_free_active() ) {
			return false;
		}

		if ( ! defined( 'HWBL_VERSION' ) || version_compare( HWBL_VERSION, THW_FREE_MIN_VERSION, '<' ) ) {
			return false;
		}

		return class_exists( 'HWBL_Curriculum' );
	}
}

add_action(
	'plugins_loaded',
	static function () {
		if ( ! thw_premium_meets_free_requirement() ) {
			return;
		}

		// Free WordPress.org build: features always on; no license phone-home.
		add_filter( 'thw_premium_is_licensed', '__return_true', 100 );

		THW_Premium::init();
	},
	20
);
