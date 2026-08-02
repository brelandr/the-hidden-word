<?php
/**
 * Shareable Bible explain packs (export / import / catalog).
 *
 * Pack ZIP layout:
 *   manifest.json
 *   explains.jsonl   (one JSON object per line)
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Explain_Packs
 */
class THW_Premium_Explain_Packs {

	const FORMAT           = 'hwbl_explain_pack_v1';
	const CATALOG_FORMAT   = 'hwbl_explain_pack_catalog_v1';
	const EXPORT_OPTION    = 'thw_explain_pack_export_job';
	const IMPORT_OPTION    = 'thw_explain_pack_import_job';
	const INSTALLED_OPTION = 'thw_explain_packs_installed';
	const CATALOG_OPTION   = 'thw_explain_pack_catalog_url';
	const DEFAULT_CATALOG_URL = 'https://raw.githubusercontent.com/brelandr/hwbl-explain-packs/main/explain-packs-catalog.json';
	const EXPORT_CRON      = 'thw_explain_pack_export_batch';
	const IMPORT_CRON      = 'thw_explain_pack_import_batch';

	const EXPORT_BATCH = 40;
	const IMPORT_BATCH = 25;
	const DELETE_BATCH = 50;

	/**
	 * Wire cron hooks.
	 */
	public static function init() {
		add_action( self::EXPORT_CRON, array( __CLASS__, 'process_export_cron' ) );
		add_action( self::IMPORT_CRON, array( __CLASS__, 'process_import_cron' ) );
	}

	/**
	 * Pack id slug.
	 *
	 * @param string             $translation Translation.
	 * @param string             $tradition   Tradition.
	 * @param array<int, string> $scopes      Scopes.
	 * @return string
	 */
	public static function pack_id( $translation, $tradition, array $scopes ) {
		$scopes = array_values( array_unique( array_filter( array_map( 'sanitize_key', $scopes ) ) ) );
		sort( $scopes );
		$scope_part = $scopes ? implode( '-', $scopes ) : 'verse';
		return sanitize_key( $translation . '-' . $tradition . '-' . $scope_part );
	}

	/**
	 * Default catalog URL (filterable). Empty means bundled file only.
	 *
	 * @return string
	 */
	/**
	 * Built-in public catalog URL (brelandr/hwbl-explain-packs).
	 *
	 * @return string
	 */
	public static function default_catalog_url() {
		/**
		 * Default remote explain-pack catalog JSON URL.
		 *
		 * @param string $url Catalog URL.
		 */
		return (string) apply_filters( 'thw_explain_pack_default_catalog_url', self::DEFAULT_CATALOG_URL );
	}

	/**
	 * Active catalog URL (saved override, else default).
	 *
	 * @return string
	 */
	public static function catalog_url() {
		$saved = (string) get_option( self::CATALOG_OPTION, '' );
		if ( $saved ) {
			return esc_url_raw( $saved );
		}
		/**
		 * Remote explain-pack catalog JSON URL (GitHub raw or Releases).
		 *
		 * @param string $url Catalog URL.
		 */
		return (string) apply_filters( 'thw_explain_pack_catalog_url', self::default_catalog_url() );
	}

	/**
	 * Bundled catalog path.
	 *
	 * @return string
	 */
	public static function bundled_catalog_path() {
		return THW_PREMIUM_DIR . 'data/explain-packs-catalog.json';
	}

	/**
	 * Load catalog (remote override + bundled merge).
	 *
	 * @param bool $force_refresh Bypass transient.
	 * @return array{format:string,packs:array<int,array<string,mixed>>}
	 */
	public static function get_catalog( $force_refresh = false ) {
		$packs = array();
		$bundled = self::read_catalog_file( self::bundled_catalog_path() );
		if ( ! empty( $bundled['packs'] ) ) {
			$packs = array_merge( $packs, $bundled['packs'] );
		}

		$url = self::catalog_url();
		if ( $url ) {
			$remote = self::fetch_remote_catalog( $url, $force_refresh );
			if ( ! empty( $remote['packs'] ) ) {
				// Remote wins on duplicate ids.
				$by_id = array();
				foreach ( $packs as $pack ) {
					$id = sanitize_key( (string) ( $pack['id'] ?? '' ) );
					if ( $id ) {
						$by_id[ $id ] = $pack;
					}
				}
				foreach ( $remote['packs'] as $pack ) {
					$id = sanitize_key( (string) ( $pack['id'] ?? '' ) );
					if ( $id ) {
						$by_id[ $id ] = $pack;
					}
				}
				$packs = array_values( $by_id );
			}
		}

		/**
		 * Filter explain pack catalog entries.
		 *
		 * @param array<int, array<string, mixed>> $packs Packs.
		 */
		$packs = apply_filters( 'thw_explain_pack_catalog', $packs );

		return array(
			'format' => self::CATALOG_FORMAT,
			'packs'  => is_array( $packs ) ? array_values( $packs ) : array(),
		);
	}

	/**
	 * @param string $path Path.
	 * @return array{packs:array<int,array<string,mixed>>}
	 */
	private static function read_catalog_file( $path ) {
		if ( ! is_readable( $path ) ) {
			return array( 'packs' => array() );
		}
		$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $data ) || empty( $data['packs'] ) || ! is_array( $data['packs'] ) ) {
			return array( 'packs' => array() );
		}
		return array( 'packs' => $data['packs'] );
	}

	/**
	 * @param string $url           URL.
	 * @param bool   $force_refresh Force.
	 * @return array{packs:array<int,array<string,mixed>>}
	 */
	private static function fetch_remote_catalog( $url, $force_refresh ) {
		$key = 'thw_explain_pack_catalog_' . md5( $url );
		if ( ! $force_refresh ) {
			$cached = get_transient( $key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) >= 300 ) {
			return array( 'packs' => array() );
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['packs'] ) || ! is_array( $data['packs'] ) ) {
			return array( 'packs' => array() );
		}
		$out = array( 'packs' => $data['packs'] );
		set_transient( $key, $out, HOUR_IN_SECONDS );
		return $out;
	}

	/**
	 * Installed pack registry.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_installed() {
		$rows = get_option( self::INSTALLED_OPTION, array() );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Local explain inventory with expected coverage vs ready Local Bibles.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_local_inventory() {
		if ( ! class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			return array();
		}

		$base_slug = function_exists( 'thw_premium_explain_base_tradition_slug' )
			? thw_premium_explain_base_tradition_slug()
			: 'base';
		$labels    = function_exists( 'thw_premium_get_tradition_preset_choices' )
			? thw_premium_get_tradition_preset_choices()
			: array();

		$expected_cache = array();
		$out            = array();

		foreach ( THW_Premium_Bible_Reader_Explain_Store::list_inventory_counts() as $row ) {
			$translation = sanitize_key( (string) ( $row['translation'] ?? '' ) );
			$tradition   = sanitize_key( (string) ( $row['tradition'] ?? '' ) );
			$scope       = sanitize_key( (string) ( $row['scope'] ?? 'verse' ) );
			if ( 'chapter' !== $scope ) {
				$scope = 'verse';
			}
			if ( '' === $translation || '' === $tradition ) {
				continue;
			}

			$cache_key = $translation . '|' . $scope;
			if ( ! isset( $expected_cache[ $cache_key ] ) ) {
				$expected_cache[ $cache_key ] = self::expected_explain_count( $translation, $scope );
			}
			$expected = (int) $expected_cache[ $cache_key ];
			$total    = max( 0, (int) ( $row['total'] ?? 0 ) );
			$complete = ( $expected > 0 && $total >= $expected );
			if ( $expected > 0 ) {
				$raw_pct = (int) round( ( $total / $expected ) * 100 );
				// Keep "100%" reserved for truly complete rows (avoid 31084/31086 → 100%).
				$percent = $complete ? 100 : (int) min( 99, max( 0, $raw_pct ) );
			} else {
				$percent = 0;
			}
			$is_base = ( $tradition === $base_slug );
			$missing = 0;
			if ( $expected > 0 && ! $complete ) {
				$missing = THW_Premium_Bible_Reader_Explain_Store::count_missing_passages( $translation, $tradition, $scope );
				if ( $missing < 1 && $total < $expected ) {
					$missing = max( 0, $expected - $total );
				}
			}

			if ( $is_base ) {
				$tradition_label = __( 'Shared base', 'hidden-word-bible-lessons' );
			} elseif ( isset( $labels[ $tradition ] ) ) {
				$tradition_label = (string) $labels[ $tradition ];
			} else {
				$tradition_label = $tradition;
			}

			$out[] = array(
				'translation'     => $translation,
				'tradition'       => $tradition,
				'tradition_label' => $tradition_label,
				'scope'           => $scope,
				'total'           => $total,
				'overrides'       => max( 0, (int) ( $row['overrides'] ?? 0 ) ),
				'same_as_base'    => max( 0, (int) ( $row['same_as_base'] ?? 0 ) ),
				'expected'        => $expected,
				'percent'         => $percent,
				'complete'        => $complete,
				'missing'         => $missing,
				'is_base'         => $is_base,
				'bible_ready'     => $expected > 0,
			);
		}

		return $out;
	}

	/**
	 * Expected explain rows for a ready local Bible + scope.
	 *
	 * @param string $translation Translation slug.
	 * @param string $scope       verse|chapter.
	 * @return int
	 */
	private static function expected_explain_count( $translation, $scope ) {
		$translation = sanitize_key( (string) $translation );
		$scope       = sanitize_key( (string) $scope );
		if ( '' === $translation || ! class_exists( 'HWBL_Local_Bible_Store' ) || ! HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return 0;
		}

		if ( 'chapter' === $scope ) {
			return max( 0, (int) HWBL_Local_Bible_Store::count_chapters( $translation ) );
		}

		// Prefer live verse rows over cached verse_count (meta can be ahead of the import).
		$n = (int) HWBL_Local_Bible_Store::count_verses( $translation );
		if ( $n < 1 ) {
			$row = HWBL_Local_Bible_Store::get_translation( $translation );
			$n   = is_array( $row ) ? (int) ( $row['verse_count'] ?? 0 ) : 0;
		}
		return max( 0, $n );
	}

	/**
	 * Mark a pack installed.
	 *
	 * @param string               $pack_id Pack id.
	 * @param array<string, mixed> $meta    Meta.
	 */
	public static function mark_installed( $pack_id, array $meta ) {
		$pack_id = sanitize_key( $pack_id );
		if ( ! $pack_id ) {
			return;
		}
		$rows             = self::get_installed();
		$rows[ $pack_id ] = array_merge(
			array(
				'id'           => $pack_id,
				'installed_at' => time(),
			),
			$meta
		);
		update_option( self::INSTALLED_OPTION, $rows, false );
	}

	/**
	 * Unmark installed.
	 *
	 * @param string $pack_id Pack id.
	 */
	public static function unmark_installed( $pack_id ) {
		$pack_id = sanitize_key( $pack_id );
		$rows    = self::get_installed();
		unset( $rows[ $pack_id ] );
		update_option( self::INSTALLED_OPTION, $rows, false );
	}

	/**
	 * Sanitize export/remove args.
	 *
	 * @param array<string, mixed> $args Args.
	 * @return array{translation:string,tradition:string,scopes:array<int,string>}|WP_Error
	 */
	public static function sanitize_pack_keys( array $args ) {
		$translation = sanitize_key( (string) ( $args['translation'] ?? '' ) );
		$tradition   = sanitize_key( (string) ( $args['tradition'] ?? '' ) );
		$scopes_in   = isset( $args['scopes'] ) ? (array) $args['scopes'] : array( 'verse' );
		$scopes      = array();
		foreach ( $scopes_in as $scope ) {
			$scope = sanitize_key( (string) $scope );
			if ( 'both' === $scope ) {
				$scopes[] = 'verse';
				$scopes[] = 'chapter';
				continue;
			}
			if ( in_array( $scope, array( 'verse', 'chapter' ), true ) ) {
				$scopes[] = $scope;
			}
		}
		$scopes = array_values( array_unique( $scopes ) );
		if ( ! $translation ) {
			return new WP_Error( 'thw_pack_translation', __( 'Choose a Bible translation slug.', 'hidden-word-bible-lessons' ) );
		}
		if ( ! $tradition ) {
			return new WP_Error( 'thw_pack_tradition', __( 'Choose a tradition.', 'hidden-word-bible-lessons' ) );
		}
		if ( empty( $scopes ) ) {
			$scopes = array( 'verse' );
		}
		return array(
			'translation' => $translation,
			'tradition'   => $tradition,
			'scopes'      => $scopes,
		);
	}

	/**
	 * Start export job (builds a ZIP under uploads).
	 *
	 * @param array<string, mixed> $args translation, tradition, scopes, remove_after (bool), version (string).
	 * @return array<string, mixed>|WP_Error
	 */
	public static function start_export( array $args ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'thw_pack_zip', __( 'PHP ZipArchive is required to export explain packs.', 'hidden-word-bible-lessons' ) );
		}

		$keys = self::sanitize_pack_keys( $args );
		if ( is_wp_error( $keys ) ) {
			return $keys;
		}

		$count = THW_Premium_Bible_Reader_Explain_Store::count_for_keys( $keys['translation'], $keys['tradition'], $keys['scopes'] );
		if ( $count < 1 ) {
			return new WP_Error( 'thw_pack_empty', __( 'No saved explanations match that Bible and tradition.', 'hidden-word-bible-lessons' ) );
		}

		$dir = self::work_dir( 'export' );
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}

		$pack_id  = self::pack_id( $keys['translation'], $keys['tradition'], $keys['scopes'] );
		$version  = sanitize_text_field( (string) ( $args['version'] ?? '1.0.0' ) );
		if ( '' === $version ) {
			$version = '1.0.0';
		}
		$jsonl    = trailingslashit( $dir ) . $pack_id . '.jsonl';
		$manifest = trailingslashit( $dir ) . $pack_id . '-manifest.json';
		$zip_path = trailingslashit( $dir ) . $pack_id . '-' . sanitize_file_name( $version ) . '.zip';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $jsonl, '' );

		$job = array(
			'status'       => 'building',
			'pack_id'      => $pack_id,
			'translation'  => $keys['translation'],
			'tradition'    => $keys['tradition'],
			'scopes'       => $keys['scopes'],
			'version'      => $version,
			'remove_after' => ! empty( $args['remove_after'] ),
			'page'         => 1,
			'offset'       => 0,
			'written'      => 0,
			'total'        => $count,
			'jsonl'        => $jsonl,
			'manifest'     => $manifest,
			'zip'          => $zip_path,
			'download_url' => '',
			'error'        => '',
			'started_at'   => time(),
			'updated_at'   => time(),
		);
		update_option( self::EXPORT_OPTION, $job, false );
		self::schedule_export();

		return $job;
	}

	/**
	 * Process export batch.
	 *
	 * @return array<string, mixed>
	 */
	public static function process_export_batch() {
		$job = get_option( self::EXPORT_OPTION, null );
		if ( ! is_array( $job ) || 'building' !== ( $job['status'] ?? '' ) ) {
			return array(
				'ok'       => true,
				'continue' => false,
				'job'      => self::export_status(),
			);
		}

		$offset = max( 0, (int) ( $job['offset'] ?? 0 ) );
		$rows   = THW_Premium_Bible_Reader_Explain_Store::list_for_keys(
			(string) $job['translation'],
			(string) $job['tradition'],
			(array) $job['scopes'],
			$offset,
			self::EXPORT_BATCH
		);

		$fh = fopen( (string) $job['jsonl'], 'ab' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fh ) {
			$job['status'] = 'error';
			$job['error']  = 'jsonl_open_failed';
			update_option( self::EXPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::export_status(),
			);
		}

		$written = 0;
		foreach ( $rows as $db_row ) {
			$row = THW_Premium_Bible_Reader_Explain_Store::export_pack_row( $db_row );
			if ( ! is_array( $row ) ) {
				continue;
			}
			fwrite( $fh, wp_json_encode( $row ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			++$written;
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$job['written']    = (int) ( $job['written'] ?? 0 ) + $written;
		$job['offset']     = $offset + count( $rows );
		$job['updated_at'] = time();

		if ( count( $rows ) < self::EXPORT_BATCH ) {
			$finalize = self::finalize_export_zip( $job );
			if ( is_wp_error( $finalize ) ) {
				$job['status'] = 'error';
				$job['error']  = $finalize->get_error_message();
				update_option( self::EXPORT_OPTION, $job, false );
				return array(
					'ok'       => false,
					'continue' => false,
					'job'      => self::export_status(),
				);
			}
			$job = $finalize;

			if ( ! empty( $job['remove_after'] ) ) {
				$job['status'] = 'removing';
				update_option( self::EXPORT_OPTION, $job, false );
				return array(
					'ok'       => true,
					'continue' => true,
					'job'      => self::export_status(),
				);
			}

			$job['status'] = 'ready';
			update_option( self::EXPORT_OPTION, $job, false );
			return array(
				'ok'       => true,
				'continue' => false,
				'job'      => self::export_status(),
			);
		}

		update_option( self::EXPORT_OPTION, $job, false );
		self::schedule_export();
		return array(
			'ok'       => true,
			'continue' => true,
			'job'      => self::export_status(),
		);
	}

	/**
	 * After export, optionally delete local explains in batches.
	 *
	 * @return array<string, mixed>
	 */
	public static function process_export_remove_batch() {
		$job = get_option( self::EXPORT_OPTION, null );
		if ( ! is_array( $job ) || 'removing' !== ( $job['status'] ?? '' ) ) {
			return array(
				'ok'       => true,
				'continue' => false,
				'job'      => self::export_status(),
			);
		}

		$result = THW_Premium_Bible_Reader_Explain_Store::delete_for_keys(
			(string) $job['translation'],
			(string) $job['tradition'],
			(array) $job['scopes'],
			self::DELETE_BATCH
		);
		$job['deleted']    = (int) ( $job['deleted'] ?? 0 ) + (int) $result['deleted'];
		$job['updated_at'] = time();

		if ( (int) $result['remaining'] > 0 ) {
			update_option( self::EXPORT_OPTION, $job, false );
			self::schedule_export();
			return array(
				'ok'       => true,
				'continue' => true,
				'job'      => self::export_status(),
			);
		}

		self::unmark_installed( (string) ( $job['pack_id'] ?? '' ) );
		$job['status'] = 'ready';
		update_option( self::EXPORT_OPTION, $job, false );
		return array(
			'ok'       => true,
			'continue' => false,
			'job'      => self::export_status(),
		);
	}

	/**
	 * Cron: export or remove phase.
	 */
	public static function process_export_cron() {
		$job = get_option( self::EXPORT_OPTION, null );
		if ( ! is_array( $job ) ) {
			return;
		}
		$status = (string) ( $job['status'] ?? '' );
		if ( 'building' === $status ) {
			$result = self::process_export_batch();
		} elseif ( 'removing' === $status ) {
			$result = self::process_export_remove_batch();
		} else {
			return;
		}
		if ( ! empty( $result['continue'] ) ) {
			self::schedule_export();
		}
	}

	/**
	 * Finalize ZIP from jsonl + manifest.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function finalize_export_zip( array $job ) {
		$manifest = array(
			'format'      => self::FORMAT,
			'id'          => (string) $job['pack_id'],
			'translation' => (string) $job['translation'],
			'tradition'   => (string) $job['tradition'],
			'scopes'      => array_values( (array) $job['scopes'] ),
			'version'     => (string) $job['version'],
			'count'       => (int) ( $job['written'] ?? 0 ),
			'created_at'  => gmdate( 'c' ),
			'generator'   => 'hidden-word-bible-lessons',
		);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( (string) $job['manifest'], wp_json_encode( $manifest, JSON_PRETTY_PRINT ) );

		$zip = new ZipArchive();
		if ( true !== $zip->open( (string) $job['zip'], ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return new WP_Error( 'thw_pack_zip_open', __( 'Could not create pack ZIP.', 'hidden-word-bible-lessons' ) );
		}
		$zip->addFile( (string) $job['manifest'], 'manifest.json' );
		$zip->addFile( (string) $job['jsonl'], 'explains.jsonl' );
		$zip->close();

		$job['download_url'] = self::url_for_work_file( (string) $job['zip'] );
		$job['bytes']        = file_exists( (string) $job['zip'] ) ? (int) filesize( (string) $job['zip'] ) : 0;
		return $job;
	}

	/**
	 * Export status payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function export_status() {
		$job = get_option( self::EXPORT_OPTION, null );
		if ( ! is_array( $job ) ) {
			return array( 'status' => 'idle' );
		}
		$total   = max( 0, (int) ( $job['total'] ?? 0 ) );
		$written = max( 0, (int) ( $job['written'] ?? 0 ) );
		$pct     = $total > 0 ? min( 100, (int) floor( ( $written / $total ) * 100 ) ) : 0;
		if ( in_array( (string) ( $job['status'] ?? '' ), array( 'ready', 'removing' ), true ) && $total > 0 ) {
			$pct = max( $pct, 'ready' === $job['status'] ? 100 : 99 );
		}
		return array(
			'status'       => (string) ( $job['status'] ?? 'idle' ),
			'pack_id'      => (string) ( $job['pack_id'] ?? '' ),
			'translation'  => (string) ( $job['translation'] ?? '' ),
			'tradition'    => (string) ( $job['tradition'] ?? '' ),
			'scopes'       => array_values( (array) ( $job['scopes'] ?? array() ) ),
			'version'      => (string) ( $job['version'] ?? '' ),
			'written'      => $written,
			'total'        => $total,
			'deleted'      => (int) ( $job['deleted'] ?? 0 ),
			'percent'      => $pct,
			'download_url' => (string) ( $job['download_url'] ?? '' ),
			'bytes'        => (int) ( $job['bytes'] ?? 0 ),
			'remove_after' => ! empty( $job['remove_after'] ),
			'error'        => (string) ( $job['error'] ?? '' ),
		);
	}

	/**
	 * Clear export job (keeps ZIP file unless $delete_files).
	 *
	 * @param bool $delete_files Delete temp files.
	 */
	public static function clear_export( $delete_files = false ) {
		$job = get_option( self::EXPORT_OPTION, null );
		if ( $delete_files && is_array( $job ) ) {
			foreach ( array( 'jsonl', 'manifest', 'zip' ) as $key ) {
				if ( ! empty( $job[ $key ] ) && is_string( $job[ $key ] ) && file_exists( $job[ $key ] ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
					@unlink( $job[ $key ] );
				}
			}
		}
		delete_option( self::EXPORT_OPTION );
		wp_clear_scheduled_hook( self::EXPORT_CRON );
	}

	/**
	 * Start import from URL or local zip path.
	 *
	 * @param array<string, mixed> $args url|file_path, pack_id optional.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function start_import( array $args ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'thw_pack_zip', __( 'PHP ZipArchive is required to install explain packs.', 'hidden-word-bible-lessons' ) );
		}

		$dir = self::work_dir( 'import' );
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}

		$url  = isset( $args['url'] ) ? esc_url_raw( (string) $args['url'] ) : '';
		$path = isset( $args['file_path'] ) ? (string) $args['file_path'] : '';

		$job = array(
			'status'      => $url ? 'downloading' : 'extracting',
			'url'         => $url,
			'zip'         => $path,
			'work_dir'    => $dir,
			'jsonl'       => '',
			'manifest'    => array(),
			'offset'      => 0,
			'imported'    => 0,
			'skipped'     => 0,
			'errors'      => 0,
			'total'       => 0,
			'pack_id'     => sanitize_key( (string) ( $args['pack_id'] ?? '' ) ),
			'error'       => '',
			'started_at'  => time(),
			'updated_at'  => time(),
		);
		update_option( self::IMPORT_OPTION, $job, false );
		self::schedule_import();
		return $job;
	}

	/**
	 * Process import batch.
	 *
	 * @return array<string, mixed>
	 */
	public static function process_import_batch() {
		$job = get_option( self::IMPORT_OPTION, null );
		if ( ! is_array( $job ) ) {
			return array(
				'ok'       => true,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$status = (string) ( $job['status'] ?? '' );
		if ( 'downloading' === $status ) {
			return self::download_import_zip( $job );
		}
		if ( 'extracting' === $status ) {
			return self::extract_import_zip( $job );
		}
		if ( 'importing' !== $status ) {
			return array(
				'ok'       => true,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$jsonl = (string) ( $job['jsonl'] ?? '' );
		if ( ! is_readable( $jsonl ) ) {
			$job['status'] = 'error';
			$job['error']  = 'jsonl_missing';
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$manifest    = is_array( $job['manifest'] ?? null ) ? $job['manifest'] : array();
		$translation = sanitize_key( (string) ( $manifest['translation'] ?? '' ) );
		$tradition   = sanitize_key( (string) ( $manifest['tradition'] ?? '' ) );
		if ( ! $translation || ! $tradition ) {
			$job['status'] = 'error';
			$job['error']  = 'bad_manifest';
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$offset = max( 0, (int) ( $job['offset'] ?? 0 ) );
		$fh     = fopen( $jsonl, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fh ) {
			$job['status'] = 'error';
			$job['error']  = 'jsonl_open_failed';
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}
		if ( $offset > 0 ) {
			fseek( $fh, $offset ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek
		}

		$processed = 0;
		while ( $processed < self::IMPORT_BATCH && ! feof( $fh ) ) {
			$line = fgets( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgets
			if ( false === $line ) {
				break;
			}
			$job['offset'] = ftell( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_ftell
			$line          = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$row = json_decode( $line, true );
			if ( ! is_array( $row ) ) {
				$job['errors'] = (int) ( $job['errors'] ?? 0 ) + 1;
				++$processed;
				continue;
			}
			$result = THW_Premium_Bible_Reader_Explain_Store::import_pack_row( $translation, $tradition, $row );
			if ( ! empty( $result['ok'] ) && ! empty( $result['skipped'] ) ) {
				$job['skipped'] = (int) ( $job['skipped'] ?? 0 ) + 1;
			} elseif ( ! empty( $result['ok'] ) ) {
				$job['imported'] = (int) ( $job['imported'] ?? 0 ) + 1;
			} else {
				$job['errors'] = (int) ( $job['errors'] ?? 0 ) + 1;
			}
			++$processed;
		}
		$eof = feof( $fh );
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$job['updated_at'] = time();
		if ( $eof ) {
			$pack_id = (string) ( $job['pack_id'] ?: ( $manifest['id'] ?? '' ) );
			if ( ! $pack_id ) {
				$pack_id = self::pack_id( $translation, $tradition, (array) ( $manifest['scopes'] ?? array( 'verse' ) ) );
			}
			self::mark_installed(
				$pack_id,
				array(
					'translation' => $translation,
					'tradition'   => $tradition,
					'scopes'      => array_values( (array) ( $manifest['scopes'] ?? array() ) ),
					'version'     => (string) ( $manifest['version'] ?? '' ),
					'url'         => (string) ( $job['url'] ?? '' ),
					'count'       => (int) ( $job['imported'] ?? 0 ) + (int) ( $job['skipped'] ?? 0 ),
				)
			);
			$job['status']  = 'done';
			$job['pack_id'] = $pack_id;
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => true,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		update_option( self::IMPORT_OPTION, $job, false );
		self::schedule_import();
		return array(
			'ok'       => true,
			'continue' => true,
			'job'      => self::import_status(),
		);
	}

	/**
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function download_import_zip( array $job ) {
		$url = (string) ( $job['url'] ?? '' );
		if ( ! $url ) {
			$job['status'] = 'error';
			$job['error']  = 'missing_url';
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$target = trailingslashit( (string) $job['work_dir'] ) . 'pack.zip';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'  => 120,
				'stream'   => true,
				'filename' => $target,
			)
		);
		if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) >= 300 || ! file_exists( $target ) ) {
			// Fallback without stream for hosts that block it.
			$response = wp_remote_get( $url, array( 'timeout' => 120 ) );
			if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) >= 300 ) {
				$job['status'] = 'error';
				$job['error']  = 'download_failed';
				update_option( self::IMPORT_OPTION, $job, false );
				return array(
					'ok'       => false,
					'continue' => false,
					'job'      => self::import_status(),
				);
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $target, (string) wp_remote_retrieve_body( $response ) );
		}

		$job['zip']        = $target;
		$job['status']     = 'extracting';
		$job['updated_at'] = time();
		update_option( self::IMPORT_OPTION, $job, false );
		return self::extract_import_zip( $job );
	}

	/**
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function extract_import_zip( array $job ) {
		$zip_path = (string) ( $job['zip'] ?? '' );
		if ( ! is_readable( $zip_path ) ) {
			$job['status'] = 'error';
			$job['error']  = 'zip_missing';
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			$job['status'] = 'error';
			$job['error']  = 'zip_open_failed';
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$extract_dir = trailingslashit( (string) $job['work_dir'] ) . 'extracted';
		wp_mkdir_p( $extract_dir );
		$zip->extractTo( $extract_dir );
		$zip->close();

		$manifest_path = $extract_dir . '/manifest.json';
		$jsonl_path    = $extract_dir . '/explains.jsonl';
		if ( ! is_readable( $manifest_path ) || ! is_readable( $jsonl_path ) ) {
			$job['status'] = 'error';
			$job['error']  = 'pack_files_missing';
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$manifest = json_decode( (string) file_get_contents( $manifest_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $manifest ) || self::FORMAT !== ( $manifest['format'] ?? '' ) ) {
			$job['status'] = 'error';
			$job['error']  = 'bad_pack_format';
			update_option( self::IMPORT_OPTION, $job, false );
			return array(
				'ok'       => false,
				'continue' => false,
				'job'      => self::import_status(),
			);
		}

		$job['manifest']   = $manifest;
		$job['jsonl']      = $jsonl_path;
		$job['total']      = (int) ( $manifest['count'] ?? 0 );
		$job['pack_id']    = sanitize_key( (string) ( $job['pack_id'] ?: ( $manifest['id'] ?? '' ) ) );
		$job['status']     = 'importing';
		$job['offset']     = 0;
		$job['updated_at'] = time();
		update_option( self::IMPORT_OPTION, $job, false );
		self::schedule_import();

		return array(
			'ok'       => true,
			'continue' => true,
			'job'      => self::import_status(),
		);
	}

	/**
	 * Cron import.
	 */
	public static function process_import_cron() {
		$result = self::process_import_batch();
		if ( ! empty( $result['continue'] ) ) {
			self::schedule_import();
		}
	}

	/**
	 * Import status payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function import_status() {
		$job = get_option( self::IMPORT_OPTION, null );
		if ( ! is_array( $job ) ) {
			return array( 'status' => 'idle' );
		}
		$imported = (int) ( $job['imported'] ?? 0 );
		$skipped  = (int) ( $job['skipped'] ?? 0 );
		$total    = max( 0, (int) ( $job['total'] ?? 0 ) );
		$done     = $imported + $skipped + (int) ( $job['errors'] ?? 0 );
		$pct      = $total > 0 ? min( 100, (int) floor( ( $done / $total ) * 100 ) ) : ( 'done' === ( $job['status'] ?? '' ) ? 100 : 0 );
		$manifest = is_array( $job['manifest'] ?? null ) ? $job['manifest'] : array();
		return array(
			'status'      => (string) ( $job['status'] ?? 'idle' ),
			'pack_id'     => (string) ( $job['pack_id'] ?? '' ),
			'translation' => (string) ( $manifest['translation'] ?? '' ),
			'tradition'   => (string) ( $manifest['tradition'] ?? '' ),
			'scopes'      => array_values( (array) ( $manifest['scopes'] ?? array() ) ),
			'version'     => (string) ( $manifest['version'] ?? '' ),
			'imported'    => $imported,
			'skipped'     => $skipped,
			'errors'      => (int) ( $job['errors'] ?? 0 ),
			'total'       => $total,
			'percent'     => $pct,
			'error'       => (string) ( $job['error'] ?? '' ),
			'url'         => (string) ( $job['url'] ?? '' ),
		);
	}

	/**
	 * Clear import job.
	 *
	 * @param bool $delete_files Delete work files.
	 */
	public static function clear_import( $delete_files = true ) {
		$job = get_option( self::IMPORT_OPTION, null );
		if ( $delete_files && is_array( $job ) && ! empty( $job['work_dir'] ) && is_string( $job['work_dir'] ) ) {
			self::rrmdir( $job['work_dir'] );
		}
		delete_option( self::IMPORT_OPTION );
		wp_clear_scheduled_hook( self::IMPORT_CRON );
	}

	/**
	 * Remove an installed pack's local explains (chunked).
	 *
	 * @param string $pack_id Pack id from registry, or translation|tradition|scopes via args.
	 * @param array<string, mixed> $args Optional keys if not installed.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function remove_local_pack( $pack_id = '', array $args = array() ) {
		$pack_id = sanitize_key( (string) $pack_id );
		$installed = self::get_installed();
		if ( $pack_id && isset( $installed[ $pack_id ] ) ) {
			$meta = $installed[ $pack_id ];
			$keys = self::sanitize_pack_keys(
				array(
					'translation' => $meta['translation'] ?? '',
					'tradition'   => $meta['tradition'] ?? '',
					'scopes'      => $meta['scopes'] ?? array( 'verse' ),
				)
			);
		} else {
			$keys = self::sanitize_pack_keys( $args );
		}
		if ( is_wp_error( $keys ) ) {
			return $keys;
		}

		$result = THW_Premium_Bible_Reader_Explain_Store::delete_for_keys(
			$keys['translation'],
			$keys['tradition'],
			$keys['scopes'],
			self::DELETE_BATCH
		);

		if ( (int) $result['remaining'] < 1 && $pack_id ) {
			self::unmark_installed( $pack_id );
		}

		return array(
			'deleted'     => (int) $result['deleted'],
			'remaining'   => (int) $result['remaining'],
			'translation' => $keys['translation'],
			'tradition'   => $keys['tradition'],
			'scopes'      => $keys['scopes'],
			'pack_id'     => $pack_id ? $pack_id : self::pack_id( $keys['translation'], $keys['tradition'], $keys['scopes'] ),
			'continue'    => (int) $result['remaining'] > 0,
		);
	}

	/**
	 * Build a catalog entry JSON snippet for an exported pack (for pasting into GitHub catalog).
	 *
	 * @param array<string, mixed> $export_status Export status.
	 * @param string               $public_url    Public ZIP URL after you upload to GitHub Releases.
	 * @return array<string, mixed>
	 */
	public static function catalog_entry_from_export( array $export_status, $public_url = '' ) {
		$scopes = array_values( (array) ( $export_status['scopes'] ?? array() ) );
		$id     = (string) ( $export_status['pack_id'] ?? self::pack_id( (string) ( $export_status['translation'] ?? '' ), (string) ( $export_status['tradition'] ?? '' ), $scopes ) );
		return array(
			'id'          => $id,
			'translation' => (string) ( $export_status['translation'] ?? '' ),
			'tradition'   => (string) ( $export_status['tradition'] ?? '' ),
			'scopes'      => $scopes,
			'version'     => (string) ( $export_status['version'] ?? '1.0.0' ),
			'label'       => strtoupper( (string) ( $export_status['translation'] ?? '' ) ) . ' · ' . (string) ( $export_status['tradition'] ?? '' ),
			'url'         => esc_url_raw( (string) $public_url ),
			'count'       => (int) ( $export_status['written'] ?? $export_status['total'] ?? 0 ),
			'bytes'       => (int) ( $export_status['bytes'] ?? 0 ),
		);
	}

	/**
	 * Work directory under uploads.
	 *
	 * @param string $kind export|import.
	 * @return string|WP_Error
	 */
	private static function work_dir( $kind ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'thw_pack_uploads', (string) $uploads['error'] );
		}
		$dir = trailingslashit( $uploads['basedir'] ) . 'hwbl-explain-packs/' . sanitize_key( $kind ) . '-' . wp_generate_password( 8, false, false );
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'thw_pack_mkdir', __( 'Could not create working directory.', 'hidden-word-bible-lessons' ) );
		}
		return $dir;
	}

	/**
	 * Public URL for a file under uploads.
	 *
	 * @param string $path Absolute path.
	 * @return string
	 */
	private static function url_for_work_file( $path ) {
		$uploads = wp_upload_dir();
		$base    = (string) ( $uploads['basedir'] ?? '' );
		$url     = (string) ( $uploads['baseurl'] ?? '' );
		if ( $base && 0 === strpos( $path, $base ) ) {
			return $url . str_replace( '\\', '/', substr( $path, strlen( $base ) ) );
		}
		return '';
	}

	/**
	 * Schedule export cron.
	 */
	private static function schedule_export() {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( ! function_exists( 'as_has_scheduled_action' ) || ! as_has_scheduled_action( self::EXPORT_CRON ) ) {
				as_enqueue_async_action( self::EXPORT_CRON, array(), 'thw-explain-packs' );
			}
			return;
		}
		if ( ! wp_next_scheduled( self::EXPORT_CRON ) ) {
			wp_schedule_single_event( time() + 1, self::EXPORT_CRON );
		}
	}

	/**
	 * Schedule import cron.
	 */
	private static function schedule_import() {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( ! function_exists( 'as_has_scheduled_action' ) || ! as_has_scheduled_action( self::IMPORT_CRON ) ) {
				as_enqueue_async_action( self::IMPORT_CRON, array(), 'thw-explain-packs' );
			}
			return;
		}
		if ( ! wp_next_scheduled( self::IMPORT_CRON ) ) {
			wp_schedule_single_event( time() + 1, self::IMPORT_CRON );
		}
	}

	/**
	 * Recursively remove a directory.
	 *
	 * @param string $dir Directory.
	 */
	private static function rrmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		if ( ! is_array( $items ) ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . '/' . $item;
			if ( is_dir( $path ) ) {
				self::rrmdir( $path );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $path );
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		@rmdir( $dir );
	}
}
