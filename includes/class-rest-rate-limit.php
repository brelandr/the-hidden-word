<?php
/**
 * Simple transient-based REST rate limiting by client IP.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Rest_Rate_Limit
 */
class HWBL_Rest_Rate_Limit {

	/**
	 * Whether a client has exceeded the rate limit.
	 *
	 * @param string $bucket   Rate limit bucket name.
	 * @param int    $limit    Max requests per window.
	 * @param int    $window   Window length in seconds.
	 * @return bool True when limited (over quota).
	 */
	public static function is_limited( $bucket, $limit = 10, $window = 60 ) {
		$bucket = sanitize_key( (string) $bucket );
		if ( '' === $bucket || $limit < 1 || $window < 1 ) {
			return false;
		}

		$key   = 'hwbl_rl_' . md5( $bucket . '|' . self::get_client_ip() );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return true;
		}

		set_transient( $key, $count + 1, $window );

		return false;
	}

	/**
	 * REST permission callback factory with rate limiting.
	 *
	 * @param string $bucket Rate limit bucket.
	 * @param int    $limit  Max requests per window.
	 * @param int    $window Window length in seconds.
	 * @return callable
	 */
	public static function public_permission( $bucket, $limit = 10, $window = 60 ) {
		return static function () use ( $bucket, $limit, $window ) {
			if ( self::is_limited( $bucket, $limit, $window ) ) {
				return new WP_Error(
					'hwbl_rate_limited',
					__( 'Too many requests. Please wait a moment and try again.', 'hidden-word-bible-lessons' ),
					array( 'status' => 429 )
				);
			}

			return true;
		};
	}

	/**
	 * Best-effort client IP for rate limiting.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$candidates = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $candidates as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$raw = sanitize_text_field( wp_unslash( (string) $_SERVER[ $header ] ) );
			if ( 'HTTP_X_FORWARDED_FOR' === $header && false !== strpos( $raw, ',' ) ) {
				$parts = explode( ',', $raw );
				$raw   = trim( (string) $parts[0] );
			}

			if ( filter_var( $raw, FILTER_VALIDATE_IP ) ) {
				return $raw;
			}
		}

		return '0.0.0.0';
	}
}
