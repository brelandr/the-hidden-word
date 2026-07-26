<?php
/**
 * VOTD transient cache helpers (extracted from verse-of-the-day).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_VOTD_Cache
 */
class THW_Premium_VOTD_Cache {

	/**
	 * Whether a cached VOTD payload is still valid for the requested calendar day.
	 *
	 * @param array<string, mixed> $cached Cached payload.
	 * @param string               $day    Expected Y-m-d.
	 * @return bool
	 */
	public static function is_valid_cached_payload( $cached, $day ) {
		if ( ! is_array( $cached ) || empty( $cached['reference'] ) || empty( $cached['text'] ) ) {
			return false;
		}

		if ( class_exists( 'HWBL_Http_Utils' ) && HWBL_Http_Utils::looks_like_html_error( (string) $cached['text'] ) ) {
			return false;
		}

		if ( empty( $cached['day'] ) || $cached['day'] !== $day ) {
			return false;
		}

		if ( ! empty( $cached['source_date'] ) && $cached['source_date'] !== $day ) {
			return false;
		}

		if ( empty( $cached['source_date'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Clear cached VOTD payloads for a calendar day (and translation variants).
	 *
	 * @param string $day Y-m-d.
	 */
	public static function clear_cache_for_day( $day ) {
		$day = (string) $day;
		delete_transient( THW_Premium_Verse_Of_The_Day::META_TRANSIENT . $day );
		delete_transient( THW_Premium_Verse_Of_The_Day::TRANSIENT . $day );

		if ( class_exists( 'HWBL_Translation_Service' ) ) {
			$supported = HWBL_Translation_Service::instance()->get_supported_translations();
			if ( is_array( $supported ) ) {
				foreach ( array_keys( $supported ) as $slug ) {
					$slug = sanitize_key( (string) $slug );
					if ( '' !== $slug ) {
						delete_transient( THW_Premium_Verse_Of_The_Day::TRANSIENT . $day . '_' . $slug );
					}
				}
			}
		}
	}

	/**
	 * Seconds until local midnight for transient TTL.
	 *
	 * @return int
	 */
	public static function seconds_until_midnight() {
		$now      = current_time( 'timestamp' );
		$tomorrow = strtotime( 'tomorrow', $now );
		return max( 60, (int) $tomorrow - (int) $now );
	}
}
