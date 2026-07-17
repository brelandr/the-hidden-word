<?php
/**
 * HTTP response helpers for Bible API providers.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Http_Utils
 */
class HWBL_Http_Utils {

	/**
	 * Whether a wp_remote_* response succeeded with a 2xx status.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @return bool
	 */
	public static function response_ok( $response ) {
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		return $code >= 200 && $code < 300;
	}

	/**
	 * Detect HTML error pages returned instead of Bible text/JSON.
	 *
	 * @param string $body Response body.
	 * @return bool
	 */
	public static function looks_like_html_error( $body ) {
		$body = ltrim( (string) $body );
		if ( '' === $body ) {
			return false;
		}

		if ( preg_match( '/^\s*<!DOCTYPE/i', $body ) || preg_match( '/^\s*<html/i', $body ) ) {
			return true;
		}

		if ( preg_match( '/403\s*-\s*Forbidden|Access is denied|Server Error/i', $body ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Detect IIS/WAF block pages without rejecting intentional HTML (e.g. bible.com VOTD).
	 *
	 * @param string $body Response body.
	 * @return bool
	 */
	public static function looks_like_blocked_html_page( $body ) {
		$body = (string) $body;
		if ( '' === $body ) {
			return false;
		}

		if ( preg_match( '/403\s*-\s*Forbidden|Access is denied|<title>\s*403|IIS\s+\d|Server Error in/i', $body ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether a bible.com Verse of the Day HTML response looks usable.
	 *
	 * @param string $body Response body.
	 * @return bool
	 */
	public static function is_usable_bible_com_votd_html( $body ) {
		$body = (string) $body;
		if ( '' === $body || self::looks_like_blocked_html_page( $body ) ) {
			return false;
		}

		return (bool) preg_match( '/Verse of the Day|__NEXT_DATA__|verse-of-the-day/i', $body );
	}

	/**
	 * Drop HTML error pages and other non-scripture noise from API text.
	 *
	 * @param string|null $text Candidate verse or chapter text.
	 * @return string
	 */
	public static function sanitize_bible_text( $text ) {
		$text = trim( (string) $text );
		if ( '' === $text || self::looks_like_html_error( $text ) ) {
			return '';
		}

		return $text;
	}

	/**
	 * Whether a normalized chapter payload contains usable verse text.
	 *
	 * @param array<string, mixed>|null $payload Chapter payload.
	 * @return bool
	 */
	public static function is_valid_chapter_payload( $payload ) {
		if ( ! is_array( $payload ) || empty( $payload['verses'] ) || ! is_array( $payload['verses'] ) ) {
			return false;
		}

		$numbered = 0;
		foreach ( $payload['verses'] as $verse ) {
			if ( ! is_array( $verse ) || empty( $verse['text'] ) ) {
				continue;
			}

			$text = (string) $verse['text'];
			if ( self::looks_like_html_error( $text ) ) {
				return false;
			}

			if ( ! empty( $verse['number'] ) ) {
				++$numbered;
			}
		}

		return $numbered > 0;
	}
}
