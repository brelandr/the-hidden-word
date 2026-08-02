<?php
/**
 * YouVersion / Bible.com VOTD fetch and parse helpers.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Votd_YouVersion
 */
class THW_Premium_Votd_YouVersion {

	/**
	 * Fetch bible.com Open Graph meta for today's VOTD.
	 *
	 * @return array{reference:string,description_text:string,image:string}
	 */
	public static function fetch_bible_com_meta() {
		$body = self::fetch_bible_com_page_html();
		if ( '' === $body ) {
			THW_Premium_Verse_Of_The_Day::debug_log( 'bible.com HTTP request failed or returned blocked HTML' );
			return array(
				'reference'         => '',
				'description_text'  => '',
				'image'             => '',
			);
		}

		$parsed = self::parse_bible_com_html( $body );
		if ( empty( $parsed['reference'] ) ) {
			THW_Premium_Verse_Of_The_Day::debug_log( 'bible.com HTML parsed but no reference found' );
		} else {
			THW_Premium_Verse_Of_The_Day::debug_log(
				'bible.com scrape succeeded',
				array(
					'reference' => (string) $parsed['reference'],
					'page_date' => isset( $parsed['page_date'] ) ? (string) $parsed['page_date'] : '',
					'has_image' => ! empty( $parsed['image'] ),
				)
			);
		}

		return $parsed;
	}

	/**
	 * Day of year (1–366) for a calendar date in the site timezone.
	 *
	 * @param string $day Y-m-d.
	 * @return int
	 */
	public static function get_day_of_year( $day ) {
		try {
			$tz   = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
			$date = new DateTime( (string) $day, $tz );
			return max( 1, min( 366, (int) $date->format( 'z' ) + 1 ) );
		} catch ( Exception $e ) {
			unset( $e );
			return max( 1, (int) gmdate( 'z' ) + 1 );
		}
	}

	/**
	 * Fetch Verse of the Day metadata from YouVersion Platform when bible.com is unreachable.
	 *
	 * @param string $day Y-m-d calendar day.
	 * @return array{reference:string,description_text:string,image:string,page_date:string,passage_id:string}
	 */
	public static function fetch_youversion_votd_meta( $day ) {
		$empty = array(
			'reference'        => '',
			'description_text' => '',
			'image'            => '',
			'page_date'        => '',
			'passage_id'       => '',
		);

		if ( ! class_exists( 'THW_Premium_YouVersion' ) ) {
			THW_Premium_Verse_Of_The_Day::debug_log( 'YouVersion API unavailable (provider not loaded)', array( 'day' => $day ) );
			return $empty;
		}

		if ( ! THW_Premium_License::is_licensed() ) {
			THW_Premium_Verse_Of_The_Day::debug_log( 'YouVersion API unavailable (Premium license inactive)', array( 'day' => $day ) );
			return $empty;
		}

		if ( ! THW_Premium_YouVersion::get_app_key() ) {
			THW_Premium_Verse_Of_The_Day::debug_log( 'YouVersion API unavailable (no App Key saved in Premium settings)', array( 'day' => $day ) );
			return $empty;
		}

		$day_of_year = self::get_day_of_year( $day );
		$payload     = THW_Premium_YouVersion::api_get( 'verse_of_the_days/' . $day_of_year );
		if ( ! is_array( $payload ) || empty( $payload['passage_id'] ) ) {
			THW_Premium_Verse_Of_The_Day::debug_log(
				'YouVersion verse_of_the_days API returned no passage_id',
				array(
					'day'         => $day,
					'day_of_year' => $day_of_year,
				)
			);
			return $empty;
		}

		$passage_id = (string) $payload['passage_id'];
		if ( ! class_exists( 'HWBL_Books' ) ) {
			return $empty;
		}

		$parsed = HWBL_Books::parse_youversion_passage_id( $passage_id );
		if ( ! $parsed || empty( $parsed['reference'] ) ) {
			THW_Premium_Verse_Of_The_Day::debug_log(
				'YouVersion passage_id could not be parsed',
				array(
					'day'        => $day,
					'passage_id' => $passage_id,
				)
			);
			return $empty;
		}

		$translation = THW_Premium_Verse_Of_The_Day::get_default_translation();
		$text        = self::fetch_youversion_passage_text( $passage_id, $translation );
		if ( '' === $text ) {
			THW_Premium_Verse_Of_The_Day::debug_log(
				'YouVersion passage text empty',
				array(
					'day'         => $day,
					'passage_id'  => $passage_id,
					'translation' => $translation,
				)
			);
		}

		$image = self::resolve_votd_image_url( $passage_id );

		return array(
			'reference'        => (string) $parsed['reference'],
			'description_text' => $text,
			'image'            => $image,
			'page_date'        => (string) $day,
			'passage_id'       => $passage_id,
		);
	}

	/**
	 * Resolve a shareable VOTD image URL for a passage ID.
	 *
	 * @param string $passage_id YouVersion USFM passage ID.
	 * @return string
	 */
	public static function resolve_votd_image_url( $passage_id ) {
		$passage_id = strtoupper( trim( (string) $passage_id ) );
		if ( '' === $passage_id ) {
			return '';
		}

		if ( class_exists( 'THW_Premium_YouVersion' ) && THW_Premium_YouVersion::is_available() ) {
			$image = THW_Premium_YouVersion::fetch_image_for_passage( $passage_id );
			if ( '' !== $image ) {
				THW_Premium_Verse_Of_The_Day::debug_log(
					'VOTD image resolved via YouVersion images API',
					array(
						'passage_id' => $passage_id,
					)
				);
				return $image;
			}
		}

		$html = self::fetch_bible_com_page_html();
		if ( '' !== $html ) {
			$parsed = self::parse_bible_com_html( $html );
			if ( ! empty( $parsed['image'] ) ) {
				return self::normalize_votd_image_url( (string) $parsed['image'] );
			}

			$page_props = self::parse_bible_com_next_data( $html );
			$image      = self::extract_votd_image_from_page_props( $page_props, $passage_id );
			if ( '' !== $image ) {
				THW_Premium_Verse_Of_The_Day::debug_log(
					'VOTD image resolved via bible.com page JSON',
					array(
						'passage_id' => $passage_id,
					)
				);
				return $image;
			}
		}

		THW_Premium_Verse_Of_The_Day::debug_log( 'VOTD image unavailable', array( 'passage_id' => $passage_id ) );
		return '';
	}

	/**
	 * Fetch bible.com VOTD HTML for image/reference parsing.
	 *
	 * @return string
	 */
	public static function fetch_bible_com_page_html() {
		$response = wp_safe_remote_get(
			THW_Premium_Verse_Of_The_Day::SOURCE_URL,
			array(
				'timeout'     => 12,
				'redirection' => 3,
				'headers'     => array(
					'Accept'          => 'text/html,application/xhtml+xml',
					'Accept-Language' => 'en-US,en;q=0.9',
					'Cache-Control'   => 'no-cache',
					'Pragma'          => 'no-cache',
				),
				'user-agent'  => 'Mozilla/5.0 (compatible; TheHiddenWordPremium/' . ( defined( 'THW_PREMIUM_VERSION' ) ? THW_PREMIUM_VERSION : '1.0' ) . '; ' . home_url( '/' ) . ')',
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		if ( $code < 200 || $code >= 300 || '' === $body ) {
			THW_Premium_Verse_Of_The_Day::debug_log(
				'bible.com HTTP response rejected',
				array(
					'status' => $code,
				)
			);
			return '';
		}

		if ( ! class_exists( 'HWBL_Http_Utils' ) || ! HWBL_Http_Utils::is_usable_bible_com_votd_html( $body ) ) {
			THW_Premium_Verse_Of_The_Day::debug_log(
				'bible.com HTML not usable for VOTD',
				array(
					'status'     => $code,
					'body_bytes' => strlen( $body ),
					'blocked'    => class_exists( 'HWBL_Http_Utils' ) && HWBL_Http_Utils::looks_like_blocked_html_page( $body ),
				)
			);
			return '';
		}

		return $body;
	}

	/**
	 * Normalize a YouVersion/Bible.com image URL for front-end use.
	 *
	 * @param string $url Raw image URL.
	 * @return string
	 */
	public static function normalize_votd_image_url( $url ) {
		$url = trim( html_entity_decode( (string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' === $url ) {
			return '';
		}

		if ( 0 === strpos( $url, '//' ) ) {
			$url = 'https:' . $url;
		}

		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return '';
		}

		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( '' === $host ) {
			return '';
		}

		$allowed_hosts = array(
			'imageproxy.youversionapi.com',
			'imageproxy-cdn.youversionapi.com',
			's3.amazonaws.com',
		);

		$allowed = false;
		foreach ( $allowed_hosts as $allowed_host ) {
			if ( $host === $allowed_host ) {
				$allowed = true;
				break;
			}
			$suffix = '.' . $allowed_host;
			if ( strlen( $host ) > strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix ) {
				$allowed = true;
				break;
			}
		}

		return $allowed ? $url : '';
	}

	/**
	 * Parse bible.com __NEXT_DATA__ page props JSON.
	 *
	 * @param string $html bible.com HTML.
	 * @return array<string, mixed>|null
	 */
	public static function parse_bible_com_next_data( $html ) {
		if ( ! preg_match( '/<script id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/is', (string) $html, $matches ) ) {
			return null;
		}

		$data = json_decode( html_entity_decode( trim( $matches[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
		if ( ! is_array( $data ) || empty( $data['props']['pageProps'] ) || ! is_array( $data['props']['pageProps'] ) ) {
			return null;
		}

		return $data['props']['pageProps'];
	}

	/**
	 * Extract a VOTD image URL from bible.com page props.
	 *
	 * @param array<string, mixed>|null $page_props bible.com pageProps.
	 * @param string                    $passage_id Expected USFM passage ID.
	 * @return string
	 */
	public static function extract_votd_image_from_page_props( $page_props, $passage_id = '' ) {
		if ( ! is_array( $page_props ) || empty( $page_props['images'] ) || ! is_array( $page_props['images'] ) ) {
			return '';
		}

		$passage_id = strtoupper( trim( (string) $passage_id ) );
		foreach ( $page_props['images'] as $image ) {
			if ( ! is_array( $image ) ) {
				continue;
			}

			if ( $passage_id && ! empty( $image['usfm'] ) && is_array( $image['usfm'] ) ) {
				$matches = false;
				foreach ( $image['usfm'] as $usfm ) {
					if ( strtoupper( trim( (string) $usfm ) ) === $passage_id ) {
						$matches = true;
						break;
					}
				}
				if ( ! $matches ) {
					continue;
				}
			}

			$url = self::pick_votd_image_rendition( $image, 640 );
			if ( '' !== $url ) {
				return self::normalize_votd_image_url( $url );
			}
		}

		$first = $page_props['images'][0];
		if ( is_array( $first ) ) {
			return self::normalize_votd_image_url( self::pick_votd_image_rendition( $first, 640 ) );
		}

		return '';
	}

	/**
	 * Pick the closest image rendition from a bible.com image object.
	 *
	 * @param array<string, mixed> $image Image object.
	 * @param int                  $width Preferred width.
	 * @return string
	 */
	public static function pick_votd_image_rendition( $image, $width = 640 ) {
		if ( empty( $image['renditions'] ) || ! is_array( $image['renditions'] ) ) {
			return '';
		}

		$width      = max( 152, min( 1280, (int) $width ) );
		$best_url   = '';
		$best_delta = PHP_INT_MAX;

		foreach ( $image['renditions'] as $rendition ) {
			if ( ! is_array( $rendition ) || empty( $rendition['url'] ) ) {
				continue;
			}

			$rendition_width = isset( $rendition['width'] ) ? (int) $rendition['width'] : 0;
			$delta           = $rendition_width > 0 ? abs( $rendition_width - $width ) : 9999;
			if ( $delta < $best_delta ) {
				$best_delta = $delta;
				$best_url   = (string) $rendition['url'];
			}
		}

		return $best_url;
	}

	/**
	 * Load passage text from YouVersion for the configured translation.
	 *
	 * @param string $passage_id  YouVersion passage ID.
	 * @param string $translation Site translation slug.
	 * @return string
	 */
	public static function fetch_youversion_passage_text( $passage_id, $translation = '' ) {
		if ( ! class_exists( 'THW_Premium_YouVersion' ) || ! THW_Premium_YouVersion::is_available() ) {
			return '';
		}

		$translation = sanitize_key( (string) $translation );
		if ( '' === $translation ) {
			$translation = THW_Premium_Verse_Of_The_Day::get_default_translation();
		}

		$version_id = THW_Premium_YouVersion::get_version_id_for_translation( $translation );
		if ( ! $version_id ) {
			$version_id = THW_Premium_YouVersion::get_version_id_for_translation( 'niv' );
		}
		if ( ! $version_id ) {
			$version_id = THW_Premium_YouVersion::get_version_id_for_translation( 'bsb' );
		}
		if ( ! $version_id ) {
			return '';
		}

		$payload = THW_Premium_YouVersion::fetch_passage( (int) $version_id, (string) $passage_id );
		return THW_Premium_YouVersion::extract_passage_text( $payload );
	}

	/**
	 * Parse bible.com HTML for reference, description text, and image.
	 *
	 * @param string $html HTML body.
	 * @return array{reference:string,description_text:string,image:string}
	 */
	public static function parse_bible_com_html( $html ) {
		if ( class_exists( 'HWBL_Http_Utils' ) && ! HWBL_Http_Utils::is_usable_bible_com_votd_html( $html ) ) {
			return array(
				'reference'         => '',
				'description_text'  => '',
				'image'             => '',
			);
		}

		$reference = '';
		$image     = '';
		$desc      = '';
		$page_date = '';

		if ( preg_match( '/"date":"(\d{4}-\d{2}-\d{2})T/i', $html, $m ) ) {
			$page_date = $m[1];
		}

		if ( preg_match( '/<title[^>]*>\s*Verse of the Day\s*-\s*(.+?)\s*-\s*Bible App\s*<\/title>/is', $html, $m ) ) {
			$reference = html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		if ( preg_match( '/property=["\']og:description["\']\s+content=["\']([^"\']+)["\']/i', $html, $m )
			|| preg_match( '/content=["\']([^"\']+)["\']\s+property=["\']og:description["\']/i', $html, $m )
		) {
			$desc = html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		if ( '' === $reference && '' !== $desc && preg_match( '/^((?:\d+\s+)?[A-Za-z][A-Za-z\s\.]+?\s+\d+:\d+(?:-\d+)?)\s+(.+)$/u', $desc, $m ) ) {
			$reference = trim( $m[1] );
			$desc      = trim( $m[2] );
		} elseif ( '' !== $reference && '' !== $desc && 0 === stripos( $desc, $reference ) ) {
			$desc = trim( substr( $desc, strlen( $reference ) ) );
		}

		if ( preg_match( '/property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $m )
			|| preg_match( '/content=["\']([^"\']+)["\']\s+property=["\']og:image["\']/i', $html, $m )
		) {
			$image = self::normalize_votd_image_url( html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		}

		if ( '' === $image ) {
			$page_props = self::parse_bible_com_next_data( $html );
			if ( is_array( $page_props ) ) {
				$passage_id = '';
				if ( ! empty( $page_props['verses'][0]['reference']['usfm'][0] ) ) {
					$passage_id = (string) $page_props['verses'][0]['reference']['usfm'][0];
				}
				$image = self::extract_votd_image_from_page_props( $page_props, $passage_id );
			}
		}

		return array(
			'reference'        => $reference,
			'description_text' => $desc,
			'image'            => $image,
			'page_date'        => $page_date,
		);
	}

	/**
	 * Parse a human scripture reference into book/chapter/verses.
	 *
	 * @param string $reference e.g. "Colossians 3:12" or "1 John 4:7-8".
	 * @return array{book_id:int,chapter:int,verse_start:int,verse_end:int}
	 */
	public static function parse_reference( $reference ) {
		$reference = trim( preg_replace( '/\s+/', ' ', (string) $reference ) );
		$out       = array(
			'book_id'     => 0,
			'chapter'     => 0,
			'verse_start' => 0,
			'verse_end'   => 0,
		);

		if ( ! preg_match( '/^(.+?)\s+(\d+):(\d+)(?:-(\d+))?$/u', $reference, $m ) ) {
			return $out;
		}

		$book_name = trim( $m[1] );
		$book_id   = 0;
		if ( class_exists( 'HWBL_Books' ) ) {
			$book_id = (int) HWBL_Books::get_id_by_name( $book_name );
			if ( $book_id < 1 ) {
				// Try without trailing period / common aliases.
				$aliases = array(
					'Psalm'          => 'Psalms',
					'Ps'             => 'Psalms',
					'Song of Songs'  => 'Song of Solomon',
					'Song of Solomon'=> 'Song of Solomon',
					'Revelation'     => 'Revelation',
				);
				if ( isset( $aliases[ $book_name ] ) ) {
					$book_id = (int) HWBL_Books::get_id_by_name( $aliases[ $book_name ] );
				}
			}
		}

		$out['book_id']     = $book_id;
		$out['chapter']     = (int) $m[2];
		$out['verse_start'] = (int) $m[3];
		$out['verse_end']   = ! empty( $m[4] ) ? (int) $m[4] : (int) $m[3];
		return $out;
	}
}
