<?php
/**
 * Hub-only GitHub Release publish for explain packs.
 *
 * Visible / usable only when HWBL_Church_Network::is_hub() (thehiddenword.org).
 * Churches never receive or need a token — they only download public Release assets.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Explain_Packs_GitHub
 */
class THW_Premium_Explain_Packs_GitHub {

	const TOKEN_OPTION = 'thw_premium_github_token_encrypted';
	const OWNER        = 'brelandr';
	const REPO         = 'hwbl-explain-packs';
	const CATALOG_PATH = 'explain-packs-catalog.json';
	const BRANCH       = 'main';

	/**
	 * Whether hub publish controls may be shown/used.
	 *
	 * @return bool
	 */
	public static function is_hub_publisher() {
		$is_hub = class_exists( 'HWBL_Church_Network' ) && HWBL_Church_Network::is_hub();
		/**
		 * Filter whether this site may publish explain packs to GitHub.
		 *
		 * @param bool $is_hub Default hub detection.
		 */
		return (bool) apply_filters( 'thw_explain_pack_is_hub_publisher', $is_hub );
	}

	/**
	 * Public repo slug owner/name.
	 *
	 * @return string
	 */
	public static function repo_slug() {
		/**
		 * Filter GitHub repo for explain packs (owner/name).
		 *
		 * @param string $slug owner/repo.
		 */
		return (string) apply_filters( 'thw_explain_pack_github_repo', self::OWNER . '/' . self::REPO );
	}

	/**
	 * Whether a token is stored.
	 *
	 * @return bool
	 */
	public static function has_token() {
		return '' !== self::get_token();
	}

	/**
	 * Decrypt and return the hub GitHub token (empty when unset / not hub).
	 *
	 * @return string
	 */
	public static function get_token() {
		if ( ! self::is_hub_publisher() ) {
			return '';
		}
		$stored = (string) get_option( self::TOKEN_OPTION, '' );
		if ( '' === $stored ) {
			return '';
		}
		$plain = self::decrypt( $stored );
		return is_string( $plain ) ? $plain : '';
	}

	/**
	 * Store (or clear) the hub GitHub personal access token.
	 *
	 * @param string $token Raw token; empty clears.
	 * @return true|WP_Error
	 */
	public static function set_token( $token ) {
		if ( ! self::is_hub_publisher() ) {
			return new WP_Error( 'thw_pack_not_hub', __( 'GitHub publish is only available on the Hidden Word hub.', 'hidden-word-bible-lessons' ) );
		}

		$token = trim( (string) $token );
		if ( '' === $token ) {
			delete_option( self::TOKEN_OPTION );
			return true;
		}

		// Reject obvious non-tokens pasted by mistake.
		if ( strlen( $token ) < 20 ) {
			return new WP_Error( 'thw_pack_bad_token', __( 'That does not look like a valid GitHub token.', 'hidden-word-bible-lessons' ) );
		}

		$encrypted = self::encrypt( $token );
		if ( '' === $encrypted ) {
			return new WP_Error( 'thw_pack_encrypt', __( 'Could not encrypt the GitHub token on this server.', 'hidden-word-bible-lessons' ) );
		}

		update_option( self::TOKEN_OPTION, $encrypted, false );
		return true;
	}

	/**
	 * Publish the current ready export ZIP to a GitHub Release and update the catalog.
	 *
	 * @param array<string, mixed> $args Optional: tag, name.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function publish_ready_export( array $args = array() ) {
		if ( ! self::is_hub_publisher() ) {
			return new WP_Error( 'thw_pack_not_hub', __( 'GitHub publish is only available on the Hidden Word hub.', 'hidden-word-bible-lessons' ) );
		}

		$token = self::get_token();
		if ( '' === $token ) {
			return new WP_Error( 'thw_pack_no_token', __( 'Save a GitHub personal access token first (repo scope).', 'hidden-word-bible-lessons' ) );
		}

		$export = THW_Premium_Explain_Packs::export_status();
		if ( 'ready' !== ( $export['status'] ?? '' ) || empty( $export['download_url'] ) ) {
			return new WP_Error( 'thw_pack_not_ready', __( 'Export a pack ZIP first and wait until it is ready.', 'hidden-word-bible-lessons' ) );
		}

		$job = get_option( THW_Premium_Explain_Packs::EXPORT_OPTION, null );
		if ( ! is_array( $job ) || empty( $job['zip'] ) || ! is_readable( (string) $job['zip'] ) ) {
			return new WP_Error( 'thw_pack_zip_missing', __( 'Export ZIP file is missing on disk. Re-run export.', 'hidden-word-bible-lessons' ) );
		}

		$pack_id = sanitize_key( (string) ( $export['pack_id'] ?? '' ) );
		$version = sanitize_text_field( (string) ( $export['version'] ?? '1.0.0' ) );
		if ( '' === $version ) {
			$version = '1.0.0';
		}
		$tag = sanitize_text_field( (string) ( $args['tag'] ?? ( $pack_id . '-v' . $version ) ) );
		$tag = preg_replace( '/[^A-Za-z0-9._\-]/', '', $tag );
		if ( '' === $tag ) {
			return new WP_Error( 'thw_pack_bad_tag', __( 'Invalid release tag.', 'hidden-word-bible-lessons' ) );
		}

		$asset_name = $pack_id . '-' . sanitize_file_name( $version ) . '.zip';
		$zip_path   = (string) $job['zip'];

		$release = self::ensure_release( $token, $tag, $pack_id, $version );
		if ( is_wp_error( $release ) ) {
			return $release;
		}

		$upload = self::upload_release_asset( $token, (int) $release['id'], $zip_path, $asset_name );
		if ( is_wp_error( $upload ) ) {
			return $upload;
		}

		$browser_url = (string) ( $upload['browser_download_url'] ?? '' );
		if ( '' === $browser_url ) {
			$browser_url = sprintf(
				'https://github.com/%s/releases/download/%s/%s',
				self::repo_slug(),
				rawurlencode( $tag ),
				rawurlencode( $asset_name )
			);
		}

		$entry = THW_Premium_Explain_Packs::catalog_entry_from_export( $export, $browser_url );
		$entry['label'] = sprintf(
			'%s · %s · %s',
			strtoupper( (string) ( $entry['translation'] ?? '' ) ),
			(string) ( $entry['tradition'] ?? '' ),
			implode( '+', (array) ( $entry['scopes'] ?? array() ) )
		);

		$catalog_result = self::upsert_catalog_entry( $token, $entry );
		if ( is_wp_error( $catalog_result ) ) {
			return $catalog_result;
		}

		return array(
			'ok'            => true,
			'tag'           => $tag,
			'release_url'   => (string) ( $release['html_url'] ?? '' ),
			'download_url'  => $browser_url,
			'catalog_url'   => THW_Premium_Explain_Packs::default_catalog_url(),
			'pack'          => $entry,
			'commit'        => $catalog_result,
		);
	}

	/**
	 * Create release or return existing by tag.
	 *
	 * @param string $token   Token.
	 * @param string $tag     Tag.
	 * @param string $pack_id Pack id.
	 * @param string $version Version.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function ensure_release( $token, $tag, $pack_id, $version ) {
		$existing = self::api_request( 'GET', '/repos/' . self::repo_slug() . '/releases/tags/' . rawurlencode( $tag ), $token );
		if ( ! is_wp_error( $existing ) && ! empty( $existing['id'] ) ) {
			return $existing;
		}

		$body = array(
			'tag_name'         => $tag,
			'target_commitish' => self::BRANCH,
			'name'             => $pack_id . ' ' . $version,
			'body'             => 'Explain pack `' . $pack_id . '` version ' . $version . ' published from the Hidden Word hub.',
			'draft'            => false,
			'prerelease'       => false,
		);

		$created = self::api_request( 'POST', '/repos/' . self::repo_slug() . '/releases', $token, $body );
		if ( is_wp_error( $created ) ) {
			return $created;
		}
		if ( empty( $created['id'] ) ) {
			return new WP_Error( 'thw_pack_release', __( 'GitHub did not return a release id.', 'hidden-word-bible-lessons' ) );
		}
		return $created;
	}

	/**
	 * Upload (or replace) a release asset.
	 *
	 * @param string $token      Token.
	 * @param int    $release_id Release id.
	 * @param string $zip_path   Local ZIP.
	 * @param string $asset_name Asset filename.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function upload_release_asset( $token, $release_id, $zip_path, $asset_name ) {
		$release = self::api_request( 'GET', '/repos/' . self::repo_slug() . '/releases/' . (int) $release_id, $token );
		if ( ! is_wp_error( $release ) && ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( isset( $asset['name'] ) && (string) $asset['name'] === $asset_name && ! empty( $asset['id'] ) ) {
					self::api_request( 'DELETE', '/repos/' . self::repo_slug() . '/releases/assets/' . (int) $asset['id'], $token );
				}
			}
		}

		$bytes = file_get_contents( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $bytes || '' === $bytes ) {
			return new WP_Error( 'thw_pack_zip_read', __( 'Could not read the export ZIP.', 'hidden-word-bible-lessons' ) );
		}

		$url = sprintf(
			'https://uploads.github.com/repos/%s/releases/%d/assets?name=%s',
			self::repo_slug(),
			(int) $release_id,
			rawurlencode( $asset_name )
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 120,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/vnd.github+json',
					'Content-Type'  => 'application/zip',
					'X-GitHub-Api-Version' => '2022-11-28',
				),
				'body'    => $bytes,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code >= 300 || ! is_array( $data ) ) {
			$msg = is_array( $data ) && ! empty( $data['message'] ) ? (string) $data['message'] : 'upload_failed_' . $code;
			return new WP_Error( 'thw_pack_upload', $msg );
		}
		return $data;
	}

	/**
	 * Merge pack entry into catalog.json on GitHub.
	 *
	 * @param string               $token Token.
	 * @param array<string, mixed> $entry Pack entry.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function upsert_catalog_entry( $token, array $entry ) {
		$path = self::CATALOG_PATH;
		$info = self::api_request( 'GET', '/repos/' . self::repo_slug() . '/contents/' . rawurlencode( $path ) . '?ref=' . rawurlencode( self::BRANCH ), $token );

		$sha     = '';
		$catalog = array(
			'format' => THW_Premium_Explain_Packs::CATALOG_FORMAT,
			'packs'  => array(),
		);

		if ( ! is_wp_error( $info ) && ! empty( $info['content'] ) ) {
			$sha  = (string) ( $info['sha'] ?? '' );
			$raw  = base64_decode( str_replace( "\n", '', (string) $info['content'] ), true );
			$decoded = json_decode( (string) $raw, true );
			if ( is_array( $decoded ) ) {
				$catalog = $decoded;
			}
		}

		if ( empty( $catalog['packs'] ) || ! is_array( $catalog['packs'] ) ) {
			$catalog['packs'] = array();
		}
		$catalog['format'] = THW_Premium_Explain_Packs::CATALOG_FORMAT;

		$by_id = array();
		foreach ( $catalog['packs'] as $pack ) {
			$id = sanitize_key( (string) ( $pack['id'] ?? '' ) );
			if ( $id ) {
				$by_id[ $id ] = $pack;
			}
		}
		$id = sanitize_key( (string) ( $entry['id'] ?? '' ) );
		if ( ! $id ) {
			return new WP_Error( 'thw_pack_entry', __( 'Pack entry is missing an id.', 'hidden-word-bible-lessons' ) );
		}
		$by_id[ $id ]     = $entry;
		$catalog['packs'] = array_values( $by_id );

		$json = wp_json_encode( $catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) ) {
			return new WP_Error( 'thw_pack_json', __( 'Could not encode catalog JSON.', 'hidden-word-bible-lessons' ) );
		}

		$body = array(
			'message' => 'Update catalog: ' . $id,
			'content' => base64_encode( $json . "\n" ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'branch'  => self::BRANCH,
		);
		if ( $sha ) {
			$body['sha'] = $sha;
		}

		$result = self::api_request( 'PUT', '/repos/' . self::repo_slug() . '/contents/' . $path, $token, $body );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'sha' => (string) ( $result['content']['sha'] ?? '' ),
			'url' => (string) ( $result['content']['html_url'] ?? '' ),
		);
	}

	/**
	 * GitHub REST helper.
	 *
	 * @param string               $method Method.
	 * @param string               $path   Path starting with /repos/...
	 * @param string               $token  Token.
	 * @param array<string, mixed>|null $body JSON body.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function api_request( $method, $path, $token, $body = null ) {
		$url = 'https://api.github.com' . $path;
		$args = array(
			'method'  => strtoupper( (string) $method ),
			'timeout' => 60,
			'headers' => array(
				'Authorization'        => 'Bearer ' . $token,
				'Accept'               => 'application/vnd.github+json',
				'X-GitHub-Api-Version' => '2022-11-28',
				'User-Agent'           => 'Hidden-Word-Bible-Lessons-Hub',
			),
		);
		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 'DELETE' === strtoupper( (string) $method ) && $code >= 200 && $code < 300 ) {
			return array( 'ok' => true );
		}

		if ( $code >= 300 || ! is_array( $data ) ) {
			$msg = is_array( $data ) && ! empty( $data['message'] ) ? (string) $data['message'] : 'github_http_' . $code;
			return new WP_Error( 'thw_pack_github', $msg, array( 'status' => $code ) );
		}

		return $data;
	}

	/**
	 * Encrypt a secret for wp_options storage.
	 *
	 * @param string $plain Plaintext.
	 * @return string
	 */
	public static function encrypt( $plain ) {
		$plain = (string) $plain;
		if ( '' === $plain || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}
		$key = self::key_bytes();
		$iv  = random_bytes( 16 );
		$raw = openssl_encrypt( $plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $raw ) {
			return '';
		}
		return base64_encode( $iv . $raw ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a secret from wp_options.
	 *
	 * @param string $encoded Encoded blob.
	 * @return string
	 */
	public static function decrypt( $encoded ) {
		$encoded = (string) $encoded;
		if ( '' === $encoded || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$bin = base64_decode( $encoded, true );
		if ( false === $bin || strlen( $bin ) < 17 ) {
			return '';
		}
		$iv  = substr( $bin, 0, 16 );
		$raw = substr( $bin, 16 );
		$out = openssl_decrypt( $raw, 'AES-256-CBC', self::key_bytes(), OPENSSL_RAW_DATA, $iv );
		return is_string( $out ) ? $out : '';
	}

	/**
	 * Derivation key from WordPress salts.
	 *
	 * @return string
	 */
	private static function key_bytes() {
		$material = ( function_exists( 'wp_salt' ) ? wp_salt( 'auth' ) : 'hwbl' ) . '|thw_explain_pack_github';
		return hash( 'sha256', $material, true );
	}
}
