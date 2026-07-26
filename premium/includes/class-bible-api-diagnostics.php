<?php
/**
 * Bible API key testing and translation diagnostics.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Bible_Api_Diagnostics
 */
class THW_Premium_Bible_Api_Diagnostics {

	/**
	 * Initialize admin AJAX hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_thw_test_bible_api', array( __CLASS__, 'ajax_test' ) );
	}

	/**
	 * AJAX: test a provider key or diagnose a translation.
	 */
	public static function ajax_test() {
		if ( ! check_ajax_referer( 'thw_test_bible_api', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid security token.', 'hidden-word-bible-lessons' ),
				),
				403
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Permission denied.', 'hidden-word-bible-lessons' ),
				),
				403
			);
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'provider';
		$key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['api_key'] ) ) : '';

		if ( 'translation' === $mode ) {
			$translation = isset( $_POST['translation'] ) ? sanitize_key( wp_unslash( $_POST['translation'] ) ) : 'nlt';
			wp_send_json_success( self::diagnose_translation( $translation ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
		if ( ! in_array( $provider, array( 'biblia', 'api_bible', 'youversion' ), true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unknown provider.', 'hidden-word-bible-lessons' ),
				),
				400
			);
		}

		$translation = isset( $_POST['translation'] ) ? sanitize_key( wp_unslash( $_POST['translation'] ) ) : 'nlt';

		if ( 'biblia' === $provider ) {
			if ( '' === $key ) {
				$key = THW_Premium_Biblia::get_api_key();
			}
			wp_send_json_success( self::test_biblia_key( $key, $translation ) );
		}

		if ( 'youversion' === $provider ) {
			if ( '' === $key ) {
				$key = THW_Premium_YouVersion::get_app_key();
			}
			wp_send_json_success( self::test_youversion_key( $key, $translation ) );
		}

		if ( '' === $key ) {
			$key = THW_Premium_API_Bible::get_api_key();
		}
		wp_send_json_success( self::test_api_bible_key( $key, $translation ) );
	}

	/**
	 * Run Biblia verse + chapter checks for a translation.
	 *
	 * @param string $api_key     Biblia API key.
	 * @param string $translation Translation slug.
	 * @return array<string, mixed>
	 */
	public static function test_biblia_key( $api_key, $translation = 'nlt' ) {
		$translation = strtolower( sanitize_key( $translation ) );
		$result      = array(
			'ok'          => false,
			'provider'    => 'biblia',
			'translation' => $translation,
			'tests'       => array(),
			'summary'     => '',
		);

		if ( '' === trim( (string) $api_key ) ) {
			$result['summary'] = __( 'No Biblia.com API key is saved.', 'hidden-word-bible-lessons' );
			return $result;
		}

		if ( ! class_exists( 'THW_Premium_Biblia' ) ) {
			$result['summary'] = __( 'Biblia provider is not loaded.', 'hidden-word-bible-lessons' );
			return $result;
		}

		$bible_id = THW_Premium_Biblia::get_bible_id_for_translation( $translation );
		if ( ! $bible_id ) {
			$result['summary'] = sprintf(
				/* translators: %s: translation slug */
				__( 'Biblia does not support the %s translation.', 'hidden-word-bible-lessons' ),
				strtoupper( $translation )
			);
			return $result;
		}

		$key_probe = self::run_biblia_request(
			__( 'API key check (KJV — included on most Biblia keys)', 'hidden-word-bible-lessons' ),
			$api_key,
			'KJV1900',
			'John 3:16',
			'verse'
		);
		array_unshift( $result['tests'], $key_probe );

		$result['tests'][] = self::run_biblia_request(
			__( 'Single verse (John 3:16)', 'hidden-word-bible-lessons' ),
			$api_key,
			$bible_id,
			'John 3:16',
			'verse'
		);

		$result['tests'][] = self::run_biblia_request(
			__( 'Full chapter (Genesis 3)', 'hidden-word-bible-lessons' ),
			$api_key,
			$bible_id,
			'Genesis 3',
			'chapter'
		);

		$result['ok']      = self::biblia_translation_tests_passed( $result['tests'] );
		$result['summary'] = self::build_biblia_summary( $translation, $result['tests'], $result['ok'] );

		return $result;
	}

	/**
	 * Run API.Bible verse + chapter checks for a translation.
	 *
	 * @param string $api_key     API.Bible key.
	 * @param string $translation Translation slug.
	 * @return array<string, mixed>
	 */
	public static function test_api_bible_key( $api_key, $translation = 'nlt' ) {
		$translation = strtolower( sanitize_key( $translation ) );
		$result      = array(
			'ok'          => false,
			'provider'    => 'api_bible',
			'translation' => $translation,
			'tests'       => array(),
			'summary'     => '',
		);

		if ( '' === trim( (string) $api_key ) ) {
			$result['summary'] = __( 'No API.Bible key is saved.', 'hidden-word-bible-lessons' );
			return $result;
		}

		$bible_id = THW_Premium_API_Bible::get_bible_id_for_translation( $translation );
		if ( ! $bible_id ) {
			$result['summary'] = sprintf(
				/* translators: %s: translation slug */
				__( 'API.Bible does not support the %s translation in this plugin.', 'hidden-word-bible-lessons' ),
				strtoupper( $translation )
			);
			return $result;
		}

		$result['tests'][] = self::run_api_bible_request(
			__( 'Single verse (John 3:16)', 'hidden-word-bible-lessons' ),
			$api_key,
			$bible_id,
			'JHN.3.16',
			'verse'
		);

		$result['tests'][] = self::run_api_bible_request(
			__( 'Full chapter (Genesis 3)', 'hidden-word-bible-lessons' ),
			$api_key,
			$bible_id,
			'GEN.3',
			'chapter'
		);

		$result['ok']      = self::all_tests_passed( $result['tests'] );
		$result['summary'] = self::build_summary( 'api_bible', $translation, $result['tests'], $result['ok'] );

		return $result;
	}

	/**
	 * Run YouVersion verse + chapter checks for a translation.
	 *
	 * @param string $app_key     YouVersion App Key.
	 * @param string $translation Translation slug.
	 * @return array<string, mixed>
	 */
	public static function test_youversion_key( $app_key, $translation = 'nlt' ) {
		$translation = strtolower( sanitize_key( $translation ) );
		$result      = array(
			'ok'          => false,
			'provider'    => 'youversion',
			'translation' => $translation,
			'tests'       => array(),
			'summary'     => '',
		);

		if ( '' === trim( (string) $app_key ) ) {
			$result['summary'] = __( 'No YouVersion App Key is saved.', 'hidden-word-bible-lessons' );
			return $result;
		}

		if ( ! class_exists( 'THW_Premium_YouVersion' ) ) {
			$result['summary'] = __( 'YouVersion provider is not loaded.', 'hidden-word-bible-lessons' );
			return $result;
		}

		$key_probe = self::run_youversion_request(
			__( 'App Key check (BSB — Berean Standard Bible)', 'hidden-word-bible-lessons' ),
			$app_key,
			3034,
			'JHN.3.16',
			'verse'
		);
		array_unshift( $result['tests'], $key_probe );

		if ( empty( $key_probe['ok'] ) ) {
			$result['summary'] = __( 'Your YouVersion App Key is missing or invalid (HTTP 401). This is separate from API.Bible. Register an app at platform.youversion.com, copy the App Key, paste it above, save settings, then test again — or leave YouVersion blank and use your API.Bible key for NLT/NIV/NASB.', 'hidden-word-bible-lessons' );
			return $result;
		}

		$version_id = THW_Premium_YouVersion::get_version_id_for_translation( $translation, $app_key );
		if ( ! $version_id ) {
			$result['summary'] = sprintf(
				/* translators: %s: translation slug */
				__( 'YouVersion catalog does not include %s for this App Key. Accept the license in platform.youversion.com, then test again.', 'hidden-word-bible-lessons' ),
				strtoupper( $translation )
			);
			return $result;
		}

		$result['tests'][] = self::run_youversion_request(
			__( 'Single verse (John 3:16)', 'hidden-word-bible-lessons' ),
			$app_key,
			$version_id,
			'JHN.3.16',
			'verse'
		);

		$result['tests'][] = self::run_youversion_request(
			__( 'Full chapter (Genesis 3)', 'hidden-word-bible-lessons' ),
			$app_key,
			$version_id,
			'GEN.3',
			'chapter'
		);

		$result['ok']      = self::youversion_translation_tests_passed( $result['tests'] );
		$result['summary'] = self::build_youversion_summary( $translation, $result['tests'], $result['ok'] );

		return $result;
	}

	/**
	 * Trace the Bible reader provider chain for one translation.
	 *
	 * @param string $translation Translation slug.
	 * @return array<string, mixed>
	 */
	public static function diagnose_translation( $translation = 'nlt' ) {
		$translation = strtolower( sanitize_key( $translation ) );
		$book_id     = 1;
		$chapter     = 3;

		$out = array(
			'ok'          => false,
			'translation' => $translation,
			'steps'       => array(),
			'summary'     => '',
		);

		if ( class_exists( 'HWBL_HelloAO_Provider' ) && HWBL_HelloAO_Provider::get_helloao_id( $translation ) ) {
			$step = array(
				'provider' => 'helloao',
				'label'    => __( 'Hello AO (free)', 'hidden-word-bible-lessons' ),
				'ok'       => false,
				'detail'   => '',
			);
			if ( ! HWBL_HelloAO_Provider::is_enabled() ) {
				$step['detail'] = __( 'Disabled under Bible Lessons → Settings.', 'hidden-word-bible-lessons' );
			} else {
				$payload = HWBL_HelloAO_Provider::get_chapter_payload( $book_id, $chapter, $translation );
				if ( HWBL_Http_Utils::is_valid_chapter_payload( $payload ) ) {
					$step['ok']     = true;
					$step['detail'] = sprintf(
						/* translators: %d: verse count */
						__( 'Chapter loaded (%d verses).', 'hidden-word-bible-lessons' ),
						count( $payload['verses'] )
					);
				} else {
					$step['detail'] = __( 'Hello AO did not return a usable chapter.', 'hidden-word-bible-lessons' );
				}
			}
			$out['steps'][] = $step;
			if ( $step['ok'] ) {
				$out['ok']       = true;
				$out['summary']  = __( 'Hello AO serves this translation for the Bible reader.', 'hidden-word-bible-lessons' );
				return $out;
			}
		} else {
			$out['steps'][] = array(
				'provider' => 'helloao',
				'label'    => __( 'Hello AO (free)', 'hidden-word-bible-lessons' ),
				'ok'       => false,
				'detail'   => sprintf(
					/* translators: %s: translation slug */
					__( '%s is not available on Hello AO.', 'hidden-word-bible-lessons' ),
					strtoupper( $translation )
				),
			);
		}

		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() ) {
			$biblia = self::test_biblia_key( THW_Premium_Biblia::get_api_key(), $translation );
			$out['steps'][] = array(
				'provider' => 'biblia',
				'label'    => __( 'Biblia.com (Premium BYOK)', 'hidden-word-bible-lessons' ),
				'ok'       => ! empty( $biblia['ok'] ),
				'detail'   => isset( $biblia['summary'] ) ? (string) $biblia['summary'] : '',
				'tests'    => isset( $biblia['tests'] ) ? $biblia['tests'] : array(),
			);
			if ( ! empty( $biblia['ok'] ) ) {
				$out['ok']      = true;
				$out['summary'] = __( 'Biblia.com serves this translation for the Bible reader.', 'hidden-word-bible-lessons' );
				return $out;
			}
		} else {
			$out['steps'][] = array(
				'provider' => 'biblia',
				'label'    => __( 'Biblia.com (Premium BYOK)', 'hidden-word-bible-lessons' ),
				'ok'       => false,
				'detail'   => THW_Premium_License::is_licensed()
					? __( 'No Biblia.com API key saved.', 'hidden-word-bible-lessons' )
					: __( 'Premium license inactive.', 'hidden-word-bible-lessons' ),
			);
		}

		if ( class_exists( 'THW_Premium_YouVersion' ) && THW_Premium_YouVersion::is_available() ) {
			$youversion = self::test_youversion_key( THW_Premium_YouVersion::get_app_key(), $translation );
			$out['steps'][] = array(
				'provider' => 'youversion',
				'label'    => __( 'YouVersion Platform (Premium BYOK)', 'hidden-word-bible-lessons' ),
				'ok'       => ! empty( $youversion['ok'] ),
				'detail'   => isset( $youversion['summary'] ) ? (string) $youversion['summary'] : '',
				'tests'    => isset( $youversion['tests'] ) ? $youversion['tests'] : array(),
			);
			if ( ! empty( $youversion['ok'] ) ) {
				$out['ok']      = true;
				$out['summary'] = __( 'YouVersion Platform serves this translation for the Bible reader.', 'hidden-word-bible-lessons' );
				return $out;
			}
		} else {
			$out['steps'][] = array(
				'provider' => 'youversion',
				'label'    => __( 'YouVersion Platform (Premium BYOK)', 'hidden-word-bible-lessons' ),
				'ok'       => false,
				'detail'   => THW_Premium_License::is_licensed()
					? __( 'No YouVersion App Key saved.', 'hidden-word-bible-lessons' )
					: __( 'Premium license inactive.', 'hidden-word-bible-lessons' ),
			);
		}

		if ( class_exists( 'THW_Premium_API_Bible' ) && THW_Premium_License::is_licensed() && THW_Premium_API_Bible::get_api_key() ) {
			$api_bible = self::test_api_bible_key( THW_Premium_API_Bible::get_api_key(), $translation );
			$out['steps'][] = array(
				'provider' => 'api_bible',
				'label'    => __( 'API.Bible (Premium BYOK fallback)', 'hidden-word-bible-lessons' ),
				'ok'       => ! empty( $api_bible['ok'] ),
				'detail'   => isset( $api_bible['summary'] ) ? (string) $api_bible['summary'] : '',
				'tests'    => isset( $api_bible['tests'] ) ? $api_bible['tests'] : array(),
			);
			if ( ! empty( $api_bible['ok'] ) ) {
				$out['ok']      = true;
				$out['summary'] = __( 'API.Bible serves this translation for the Bible reader.', 'hidden-word-bible-lessons' );
				return $out;
			}
		} else {
			$out['steps'][] = array(
				'provider' => 'api_bible',
				'label'    => __( 'API.Bible (Premium BYOK fallback)', 'hidden-word-bible-lessons' ),
				'ok'       => false,
				'detail'   => THW_Premium_API_Bible::get_api_key()
					? __( 'Premium license inactive.', 'hidden-word-bible-lessons' )
					: __( 'No API.Bible key saved. NLT requires API.Bible when Biblia does not license it — get a key at scripture.api.bible, paste it above, click Save Changes, then Test API.Bible key.', 'hidden-word-bible-lessons' ),
			);
		}

		$out['summary'] = sprintf(
			/* translators: %s: translation slug */
			__( '%s cannot be loaded by any configured Bible reader provider. See step details below.', 'hidden-word-bible-lessons' ),
			strtoupper( $translation )
		);

		return $out;
	}

	/**
	 * Execute one Biblia content request and interpret the response.
	 *
	 * @param string $label     Test label.
	 * @param string $api_key   API key.
	 * @param string $bible_id  Biblia Bible ID.
	 * @param string $passage   Passage reference.
	 * @param string $type      verse|chapter.
	 * @return array<string, mixed>
	 */
	public static function run_biblia_request( $label, $api_key, $bible_id, $passage, $type ) {
		$url = add_query_arg(
			array(
				'passage'    => $passage,
				'key'        => $api_key,
				'citation'   => 'false',
				'style'      => 'chapter' === $type ? 'oneVersePerLine' : 'bibleTextOnly',
				'paragraphs' => 'false',
			),
			THW_Premium_Biblia::API_BASE . 'bible/content/' . rawurlencode( $bible_id ) . '.txt'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		return self::interpret_http_response(
			$label,
			$response,
			self::redact_url( $url, $api_key ),
			$type,
			'biblia'
		);
	}

	/**
	 * Execute one YouVersion passage request and interpret the response.
	 *
	 * @param string $label      Test label.
	 * @param string $app_key    App Key.
	 * @param int    $version_id YouVersion version ID.
	 * @param string $passage_id Passage ID.
	 * @param string $type       verse|chapter.
	 * @return array<string, mixed>
	 */
	public static function run_youversion_request( $label, $app_key, $version_id, $passage_id, $type ) {
		$url = add_query_arg(
			array( 'format' => 'text' ),
			THW_Premium_YouVersion::API_BASE . 'bibles/' . (int) $version_id . '/passages/' . rawurlencode( $passage_id )
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'X-YVP-App-Key' => $app_key,
					'Accept'        => 'application/json',
				),
			)
		);

		return self::interpret_http_response(
			$label,
			$response,
			$url,
			$type,
			'youversion'
		);
	}

	/**
	 * Execute one API.Bible request and interpret the response.
	 *
	 * @param string $label     Test label.
	 * @param string $api_key   API key.
	 * @param string $bible_id  Bible version ID.
	 * @param string $reference API.Bible reference.
	 * @param string $type      verse|chapter.
	 * @return array<string, mixed>
	 */
	public static function run_api_bible_request( $label, $api_key, $bible_id, $reference, $type ) {
		$endpoint = 'chapter' === $type ? 'chapters' : 'verses';
		$url      = THW_Premium_API_Bible::API_BASE . 'bibles/' . $bible_id . '/' . $endpoint . '/' . $reference;
		if ( 'chapter' === $type ) {
			$url = add_query_arg( THW_Premium_API_Bible::get_chapter_query_args( 'text' ), $url );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'api-key' => $api_key,
				),
			)
		);

		return self::interpret_http_response(
			$label,
			$response,
			$url,
			$type,
			'api_bible'
		);
	}

	/**
	 * Normalize an HTTP response into a diagnostic test row.
	 *
	 * @param string               $label    Test label.
	 * @param array|WP_Error       $response HTTP response.
	 * @param string               $url      Request URL (key redacted).
	 * @param string               $type     verse|chapter.
	 * @param string               $provider biblia|api_bible|youversion.
	 * @return array<string, mixed>
	 */
	public static function interpret_http_response( $label, $response, $url, $type, $provider ) {
		$row = array(
			'label'        => $label,
			'ok'           => false,
			'http_code'    => 0,
			'url'          => $url,
			'error'        => '',
			'hint'         => '',
			'body_preview' => '',
			'verse_count'  => 0,
			'sample_text'  => '',
		);

		if ( is_wp_error( $response ) ) {
			$row['error'] = $response->get_error_message();
			$row['hint']  = __( 'WordPress could not reach the API. Your host may block outbound HTTPS or DNS may be failing.', 'hidden-word-bible-lessons' );
			return $row;
		}

		$row['http_code']    = (int) wp_remote_retrieve_response_code( $response );
		$body                = (string) wp_remote_retrieve_body( $response );
		$row['body_preview'] = self::preview_body( $body );

		if ( ! HWBL_Http_Utils::response_ok( $response ) ) {
			$row['error'] = sprintf(
				/* translators: %d: HTTP status code */
				__( 'HTTP %d response from the API server.', 'hidden-word-bible-lessons' ),
				$row['http_code']
			);
			$row['hint'] = self::hint_for_http_failure( $provider, $row['http_code'], $body );
			return $row;
		}

		if ( HWBL_Http_Utils::looks_like_html_error( $body ) ) {
			$row['error'] = __( 'The API returned an HTML error page instead of Bible text.', 'hidden-word-bible-lessons' );
			$row['hint']  = self::hint_for_http_failure( $provider, $row['http_code'], $body );
			return $row;
		}

		if ( 'api_bible' === $provider ) {
			$decoded = json_decode( $body, true );
			if ( empty( $decoded['data']['content'] ) ) {
				$message = '';
				if ( is_array( $decoded ) && ! empty( $decoded['error']['message'] ) ) {
					$message = (string) $decoded['error']['message'];
				}
				$row['error'] = $message ? $message : __( 'API.Bible JSON did not include verse/chapter content.', 'hidden-word-bible-lessons' );
				$row['hint']  = __( 'Confirm your API.Bible plan includes this translation (NLT requires a licensed plan).', 'hidden-word-bible-lessons' );
				return $row;
			}

			if ( 'chapter' === $type ) {
				$verses = THW_Premium_API_Bible::parse_chapter_text( $decoded['data']['content'] );
				if ( empty( $verses ) ) {
					$verses = THW_Premium_API_Bible::parse_chapter_html( $decoded['data']['content'] );
				}
			} else {
				$text   = trim( wp_strip_all_tags( $decoded['data']['content'] ) );
				$verses = '' !== $text ? array( array( 'number' => 16, 'text' => $text ) ) : array();
			}
		} elseif ( 'youversion' === $provider ) {
			$decoded = json_decode( $body, true );
			$payload = is_array( $decoded ) ? THW_Premium_YouVersion::unwrap_payload( $decoded ) : array();
			$text    = THW_Premium_YouVersion::extract_passage_text( $payload );
			if ( '' === $text ) {
				$message = '';
				if ( is_array( $decoded ) && ! empty( $decoded['message'] ) ) {
					$message = (string) $decoded['message'];
				}
				$row['error'] = $message ? $message : __( 'YouVersion JSON did not include passage content.', 'hidden-word-bible-lessons' );
				$row['hint']  = __( 'Confirm your App Key is valid and the translation license is accepted at platform.youversion.com.', 'hidden-word-bible-lessons' );
				return $row;
			}

			if ( 'chapter' === $type ) {
				$verses = THW_Premium_YouVersion::parse_chapter_content( $text );
			} else {
				$verses = array( array( 'number' => 16, 'text' => $text ) );
			}
		} else {
			if ( 'chapter' === $type ) {
				$verses = THW_Premium_Biblia::parse_chapter_content( $body );
			} else {
				$text   = THW_Premium_Biblia::parse_content_response( $body, 'John 3:16' );
				$verses = '' !== $text ? array( array( 'number' => 16, 'text' => $text ) ) : array();
			}
		}

		if ( empty( $verses ) ) {
			$row['error'] = __( 'The response did not contain parseable Bible text.', 'hidden-word-bible-lessons' );
			$row['hint']  = __( 'The translation may not be enabled on your API account, or the response format changed.', 'hidden-word-bible-lessons' );
			return $row;
		}

		$row['ok']          = true;
		$row['verse_count'] = count( $verses );
		$row['sample_text'] = self::preview_text( $verses[0]['text'] );

		return $row;
	}

	/**
	 * Whether every test row succeeded.
	 *
	 * @param array<int, array<string, mixed>> $tests Test rows.
	 * @return bool
	 */
	private static function all_tests_passed( $tests ) {
		foreach ( $tests as $test ) {
			if ( empty( $test['ok'] ) ) {
				return false;
			}
		}
		return ! empty( $tests );
	}

	/**
	 * Biblia passes when the requested translation tests succeed (key probe is informational).
	 *
	 * @param array<int, array<string, mixed>> $tests Test rows.
	 * @return bool
	 */
	private static function biblia_translation_tests_passed( $tests ) {
		if ( count( $tests ) < 2 ) {
			return self::all_tests_passed( $tests );
		}

		$translation_tests = array_slice( $tests, 1 );
		return self::all_tests_passed( $translation_tests );
	}

	/**
	 * YouVersion passes when the requested translation tests succeed (key probe is informational).
	 *
	 * @param array<int, array<string, mixed>> $tests Test rows.
	 * @return bool
	 */
	private static function youversion_translation_tests_passed( $tests ) {
		if ( count( $tests ) < 2 ) {
			return self::all_tests_passed( $tests );
		}

		$translation_tests = array_slice( $tests, 1 );
		return self::all_tests_passed( $translation_tests );
	}

	/**
	 * Human-readable YouVersion summary with key-vs-translation failure detection.
	 *
	 * @param string                           $translation Translation slug.
	 * @param array<int, array<string, mixed>> $tests       Test rows.
	 * @param bool                             $ok          Translation tests passed.
	 * @return string
	 */
	private static function build_youversion_summary( $translation, $tests, $ok ) {
		if ( $ok ) {
			return sprintf(
				/* translators: %s: translation slug */
				__( 'YouVersion App Key works for %s (verse + chapter).', 'hidden-word-bible-lessons' ),
				strtoupper( $translation )
			);
		}

		$key_ok = ! empty( $tests[0]['ok'] );
		if ( $key_ok && count( $tests ) > 1 ) {
			return sprintf(
				/* translators: %s: translation slug */
				__( 'Your YouVersion App Key is valid, but %s is not licensed for this app. Accept the translation license at platform.youversion.com and test again.', 'hidden-word-bible-lessons' ),
				strtoupper( $translation )
			);
		}

		return self::build_summary( 'youversion', $translation, $tests, false );
	}

	/**
	 * Human-readable Biblia summary with key-vs-translation failure detection.
	 *
	 * @param string                           $translation Translation slug.
	 * @param array<int, array<string, mixed>> $tests       Test rows.
	 * @param bool                             $ok          Translation tests passed.
	 * @return string
	 */
	private static function build_biblia_summary( $translation, $tests, $ok ) {
		if ( $ok ) {
			return sprintf(
				/* translators: %s: translation slug */
				__( 'Biblia.com key works for %s (verse + chapter).', 'hidden-word-bible-lessons' ),
				strtoupper( $translation )
			);
		}

		$key_ok = ! empty( $tests[0]['ok'] );
		if ( $key_ok && count( $tests ) > 1 ) {
			return sprintf(
				/* translators: %s: translation slug */
				__( 'Your Biblia key is valid, but %s is not licensed on this Biblia account (ESV/NLT/NIV often require a paid Logos/Biblia license). Use API.Bible for NLT, or enable the translation in your Biblia account.', 'hidden-word-bible-lessons' ),
				strtoupper( $translation )
			);
		}

		return self::build_summary( 'biblia', $translation, $tests, false );
	}

	/**
	 * Build a human-readable summary for provider tests.
	 *
	 * @param string                           $provider    Provider slug.
	 * @param string                           $translation Translation slug.
	 * @param array<int, array<string, mixed>> $tests       Test rows.
	 * @param bool                             $ok          Overall success.
	 * @return string
	 */
	private static function build_summary( $provider, $translation, $tests, $ok ) {
		if ( $ok ) {
			$provider_label = 'Biblia.com';
			if ( 'api_bible' === $provider ) {
				$provider_label = 'API.Bible';
			} elseif ( 'youversion' === $provider ) {
				$provider_label = 'YouVersion';
			}
			return sprintf(
				/* translators: 1: provider name, 2: translation slug */
				__( '%1$s key works for %2$s (verse + chapter).', 'hidden-word-bible-lessons' ),
				$provider_label,
				strtoupper( $translation )
			);
		}

		foreach ( $tests as $test ) {
			if ( empty( $test['ok'] ) ) {
				$parts = array();
				if ( ! empty( $test['error'] ) ) {
					$parts[] = $test['error'];
				}
				if ( ! empty( $test['hint'] ) ) {
					$parts[] = $test['hint'];
				}
				if ( ! empty( $parts ) ) {
					return implode( ' ', $parts );
				}
			}
		}

		return __( 'API test failed.', 'hidden-word-bible-lessons' );
	}

	/**
	 * Suggest remediation based on HTTP status/body.
	 *
	 * @param string $provider Provider slug.
	 * @param int    $code     HTTP status.
	 * @param string $body     Response body.
	 * @return string
	 */
	private static function hint_for_http_failure( $provider, $code, $body ) {
		if ( 403 === $code || HWBL_Http_Utils::looks_like_html_error( $body ) ) {
			if ( 'biblia' === $provider ) {
				return __( 'Biblia returned Forbidden. The key may be invalid, or this translation may not be licensed on your Biblia account. Test ESV in the dropdown — if ESV works but NLT does not, add an API.Bible key for NLT.', 'hidden-word-bible-lessons' );
			}
			if ( 'youversion' === $provider ) {
				return __( 'YouVersion returned Forbidden. Verify the App Key at platform.youversion.com and accept the Bible license for this translation in your developer app.', 'hidden-word-bible-lessons' );
			}
			return __( 'API.Bible returned Forbidden. Verify the key at scripture.api.bible and confirm your plan licenses this translation (NLT often requires an upgraded plan).', 'hidden-word-bible-lessons' );
		}

		if ( 401 === $code ) {
			return __( 'Unauthorized — the API key is missing or invalid.', 'hidden-word-bible-lessons' );
		}

		if ( 429 === $code ) {
			return __( 'Rate limited — wait a few minutes and try again.', 'hidden-word-bible-lessons' );
		}

		return __( 'Unexpected API response — share the debug details below with your host or API provider support.', 'hidden-word-bible-lessons' );
	}

	/**
	 * Redact API key in a URL for safe display.
	 *
	 * @param string $url     URL.
	 * @param string $api_key API key.
	 * @return string
	 */
	public static function redact_url( $url, $api_key ) {
		if ( '' === $api_key ) {
			return $url;
		}
		return str_replace( rawurlencode( $api_key ), '***', str_replace( $api_key, '***', $url ) );
	}

	/**
	 * Short preview of a response body.
	 *
	 * @param string $body Raw body.
	 * @return string
	 */
	public static function preview_body( $body ) {
		$body = preg_replace( '/\s+/', ' ', trim( (string) $body ) );
		if ( strlen( $body ) > 220 ) {
			return substr( $body, 0, 220 ) . '…';
		}
		return $body;
	}

	/**
	 * Short preview of verse text.
	 *
	 * @param string $text Verse text.
	 * @return string
	 */
	public static function preview_text( $text ) {
		$text = trim( preg_replace( '/\s+/', ' ', (string) $text ) );
		if ( strlen( $text ) > 120 ) {
			return substr( $text, 0, 120 ) . '…';
		}
		return $text;
	}
}
