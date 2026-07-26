<?php
/**
 * Download and import public-domain Bibles into local SQL tables.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Local_Bible_Importer
 */
class HWBL_Local_Bible_Importer {

	const CRON_HOOK       = 'hwbl_local_bible_import_batch';
	const QUEUE_OPTION    = 'hwbl_local_bible_import_queue';
	const JOB_OPTION_PREFIX = 'hwbl_local_bible_job_';
	const BATCH_SIZE      = 400;

	const SUPERSEARCH_DOWNLOAD = 'https://api.biblesupersearch.com/api/download';
	const BSB_TXT_URL          = 'https://bereanbible.com/bsb.txt';
	const SCROLLMAPPER_JSON    = 'https://raw.githubusercontent.com/scrollmapper/bible_databases/master/formats/json/';
	const EBIBLE_USFX_BASE     = 'https://ebible.org/Scriptures/';
	/** @deprecated Use catalog `ebible` key via download_url_for(). */
	const EBIBLE_LSV_USFX      = 'https://ebible.org/Scriptures/englsv_usfx.zip';

	/**
	 * Wire cron / Action Scheduler hooks.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'process_cron_batch' ) );
		add_action( 'init', array( 'HWBL_Local_Bible_Store', 'maybe_install_schema' ), 2 );
	}

	/**
	 * Job option key for a slug.
	 *
	 * @param string $slug Translation slug.
	 * @return string
	 */
	public static function job_option_key( $slug ) {
		return self::JOB_OPTION_PREFIX . sanitize_key( $slug );
	}

	/**
	 * Get import job state.
	 *
	 * @param string $slug Translation slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_job( $slug ) {
		$job = get_option( self::job_option_key( $slug ), null );
		return is_array( $job ) ? $job : null;
	}

	/**
	 * Save import job state.
	 *
	 * @param string               $slug Translation slug.
	 * @param array<string, mixed> $job  Job data.
	 */
	public static function save_job( $slug, array $job ) {
		update_option( self::job_option_key( $slug ), $job, false );
	}

	/**
	 * Delete import job state and temp files.
	 *
	 * @param string $slug Translation slug.
	 */
	public static function clear_job( $slug ) {
		$job = self::get_job( $slug );
		if ( is_array( $job ) ) {
			foreach ( array( 'source_file', 'normalized_file' ) as $key ) {
				if ( ! empty( $job[ $key ] ) && is_string( $job[ $key ] ) && file_exists( $job[ $key ] ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
					@unlink( $job[ $key ] );
				}
			}
		}
		delete_option( self::job_option_key( $slug ) );
	}

	/**
	 * Queue one or more translations for install.
	 *
	 * @param array<int, string> $slugs Translation slugs.
	 * @return array{queued:array<int,string>,errors:array<string,string>}
	 */
	public static function queue_install( array $slugs ) {
		HWBL_Local_Bible_Store::maybe_install_schema();

		$catalog = HWBL_Local_Bible_Store::get_catalog();
		$queued  = array();
		$errors  = array();

		foreach ( $slugs as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( ! isset( $catalog[ $slug ] ) ) {
				$errors[ $slug ] = 'unknown_translation';
				continue;
			}

			$job = self::get_job( $slug );
			if ( is_array( $job ) && in_array( (string) ( $job['status'] ?? '' ), array( 'downloading', 'importing' ), true ) ) {
				$queued[] = $slug;
				continue;
			}

			self::clear_job( $slug );
			HWBL_Local_Bible_Store::upsert_translation(
				$slug,
				array(
					'label'         => $catalog[ $slug ]['label'],
					'source'        => $catalog[ $slug ]['source'],
					'license'       => $catalog[ $slug ]['license'],
					'status'        => 'downloading',
					'verse_count'   => 0,
					'installed_at'  => null,
					'error_message' => null,
				)
			);

			self::save_job(
				$slug,
				array(
					'status'          => 'downloading',
					'source_file'     => '',
					'normalized_file' => '',
					'offset'          => 0,
					'total'           => 0,
					'imported'        => 0,
					'error'           => '',
					'updated_at'      => time(),
				)
			);

			$queued[] = $slug;
		}

		$queue = get_option( self::QUEUE_OPTION, array() );
		if ( ! is_array( $queue ) ) {
			$queue = array();
		}
		$queue = array_values( array_unique( array_merge( $queue, $queued ) ) );
		update_option( self::QUEUE_OPTION, $queue, false );

		self::schedule_next_batch();

		return array(
			'queued' => $queued,
			'errors' => $errors,
		);
	}

	/**
	 * Schedule the next import batch via Action Scheduler or WP-Cron.
	 */
	public static function schedule_next_batch() {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( ! as_has_scheduled_action( self::CRON_HOOK ) ) {
				as_enqueue_async_action( self::CRON_HOOK, array(), 'hwbl-local-bible' );
			}
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 1, self::CRON_HOOK );
		}
	}

	/**
	 * Cron / Action Scheduler entry point.
	 */
	public static function process_cron_batch() {
		$result = self::process_next_batch();
		if ( ! empty( $result['continue'] ) ) {
			self::schedule_next_batch();
		}
	}

	/**
	 * Process the next pending batch (download or import).
	 *
	 * @param string $prefer_slug Optional slug to prefer.
	 * @return array<string, mixed>
	 */
	public static function process_next_batch( $prefer_slug = '' ) {
		HWBL_Local_Bible_Store::maybe_install_schema();

		$slug = sanitize_key( (string) $prefer_slug );
		if ( '' === $slug ) {
			$queue = get_option( self::QUEUE_OPTION, array() );
			if ( ! is_array( $queue ) || empty( $queue ) ) {
				return array(
					'ok'       => true,
					'continue' => false,
					'message'  => 'idle',
				);
			}
			$slug = sanitize_key( (string) $queue[0] );
		}

		$job = self::get_job( $slug );
		if ( ! is_array( $job ) ) {
			self::dequeue( $slug );
			return array(
				'ok'       => true,
				'continue' => self::has_pending(),
				'slug'     => $slug,
				'message'  => 'no_job',
			);
		}

		$status = (string) ( $job['status'] ?? '' );
		if ( 'downloading' === $status || empty( $job['normalized_file'] ) ) {
			return self::download_and_normalize( $slug, $job );
		}

		if ( 'importing' === $status ) {
			return self::import_batch( $slug, $job );
		}

		self::dequeue( $slug );
		return array(
			'ok'       => true,
			'continue' => self::has_pending(),
			'slug'     => $slug,
			'status'   => $status,
			'job'      => $job,
		);
	}

	/**
	 * Whether the import queue still has work.
	 *
	 * @return bool
	 */
	public static function has_pending() {
		$queue = get_option( self::QUEUE_OPTION, array() );
		return is_array( $queue ) && ! empty( $queue );
	}

	/**
	 * Remove a slug from the import queue.
	 *
	 * @param string $slug Translation slug.
	 */
	private static function dequeue( $slug ) {
		$queue = get_option( self::QUEUE_OPTION, array() );
		if ( ! is_array( $queue ) ) {
			$queue = array();
		}
		$queue = array_values(
			array_filter(
				$queue,
				static function ( $item ) use ( $slug ) {
					return sanitize_key( (string) $item ) !== $slug;
				}
			)
		);
		update_option( self::QUEUE_OPTION, $queue, false );
	}

	/**
	 * Download remote Bible and write a normalized JSON verse file.
	 *
	 * @param string               $slug Translation slug.
	 * @param array<string, mixed> $job  Job state.
	 * @return array<string, mixed>
	 */
	private static function download_and_normalize( $slug, array $job ) {
		$catalog = HWBL_Local_Bible_Store::get_catalog();
		if ( ! isset( $catalog[ $slug ] ) ) {
			return self::fail_job( $slug, $job, 'unknown_translation' );
		}

		HWBL_Local_Bible_Store::upsert_translation( $slug, array( 'status' => 'downloading' ) );

		$dir = self::temp_dir();
		if ( ! $dir ) {
			return self::fail_job( $slug, $job, 'temp_dir_failed' );
		}

		$source = (string) ( $catalog[ $slug ]['source'] ?? '' );
		$url    = self::download_url_for( $slug );
		if ( 'berean' === $source ) {
			$ext = 'txt';
		} elseif ( 'ebible_usfx' === $source ) {
			$ext = 'zip';
		} elseif ( 'usfx' === $source ) {
			$ext = preg_match( '/\.zip(\?|$)/i', $url ) ? 'zip' : 'xml';
		} elseif ( 'theword_ont' === $source ) {
			$ext = 'ont';
		} else {
			$ext = 'json';
		}
		$source_file = trailingslashit( $dir ) . $slug . '-source.' . $ext;

		$downloaded = self::download_file( $url, $source_file );
		if ( is_wp_error( $downloaded ) ) {
			return self::fail_job( $slug, $job, $downloaded->get_error_message() );
		}

		$verses = self::parse_source_file( $source_file, $slug, $source );

		if ( is_wp_error( $verses ) ) {
			return self::fail_job( $slug, $job, $verses->get_error_message() );
		}

		if ( empty( $verses ) ) {
			return self::fail_job( $slug, $job, 'no_verses_parsed' );
		}

		$normalized = trailingslashit( $dir ) . $slug . '-normalized.json';
		$written    = file_put_contents( $normalized, wp_json_encode( array_values( $verses ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === $written ) {
			return self::fail_job( $slug, $job, 'normalize_write_failed' );
		}

		// Clear prior verses before import.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( HWBL_Local_Bible_Store::verses_table(), array( 'translation' => $slug ), array( '%s' ) );

		$job['status']          = 'importing';
		$job['source_file']     = $source_file;
		$job['normalized_file'] = $normalized;
		$job['offset']          = 0;
		$job['total']           = count( $verses );
		$job['imported']        = 0;
		$job['error']           = '';
		$job['updated_at']      = time();
		self::save_job( $slug, $job );

		HWBL_Local_Bible_Store::upsert_translation( $slug, array( 'status' => 'importing' ) );

		// Immediate first import batch.
		return self::import_batch( $slug, $job );
	}

	/**
	 * Import one chunk from the normalized verse file.
	 *
	 * @param string               $slug Translation slug.
	 * @param array<string, mixed> $job  Job state.
	 * @return array<string, mixed>
	 */
	private static function import_batch( $slug, array $job ) {
		$file = (string) ( $job['normalized_file'] ?? '' );
		if ( '' === $file || ! is_readable( $file ) ) {
			return self::fail_job( $slug, $job, 'normalized_missing' );
		}

		$raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$all = json_decode( (string) $raw, true );
		if ( ! is_array( $all ) ) {
			return self::fail_job( $slug, $job, 'normalized_invalid' );
		}

		$offset = max( 0, (int) ( $job['offset'] ?? 0 ) );
		$batch  = (int) apply_filters( 'hwbl_local_bible_import_batch_size', self::BATCH_SIZE );
		$chunk  = array_slice( $all, $offset, max( 1, $batch ) );

		if ( empty( $chunk ) ) {
			return self::complete_job( $slug, $job );
		}

		HWBL_Local_Bible_Store::insert_verses_batch( $slug, $chunk );

		$job['offset']     = $offset + count( $chunk );
		$job['imported']   = (int) ( $job['imported'] ?? 0 ) + count( $chunk );
		$job['total']      = count( $all );
		$job['status']     = 'importing';
		$job['updated_at'] = time();
		self::save_job( $slug, $job );

		HWBL_Local_Bible_Store::upsert_translation(
			$slug,
			array(
				'status'      => 'importing',
				'verse_count' => (int) $job['imported'],
			)
		);

		if ( $job['offset'] >= count( $all ) ) {
			return self::complete_job( $slug, $job );
		}

		self::schedule_next_batch();

		return array(
			'ok'       => true,
			'continue' => true,
			'slug'     => $slug,
			'status'   => 'importing',
			'job'      => $job,
			'progress' => self::progress_payload( $slug, $job ),
		);
	}

	/**
	 * Mark a translation ready and clean temp files.
	 *
	 * @param string               $slug Translation slug.
	 * @param array<string, mixed> $job  Job state.
	 * @return array<string, mixed>
	 */
	private static function complete_job( $slug, array $job ) {
		$count = HWBL_Local_Bible_Store::count_verses( $slug );
		HWBL_Local_Bible_Store::upsert_translation(
			$slug,
			array(
				'status'        => 'ready',
				'verse_count'   => $count,
				'installed_at'  => current_time( 'mysql' ),
				'error_message' => null,
			)
		);

		$job['status']     = 'ready';
		$job['imported']   = $count;
		$job['updated_at'] = time();
		self::save_job( $slug, $job );

		foreach ( array( 'source_file', 'normalized_file' ) as $key ) {
			if ( ! empty( $job[ $key ] ) && is_string( $job[ $key ] ) && file_exists( $job[ $key ] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $job[ $key ] );
			}
		}
		$job['source_file']     = '';
		$job['normalized_file'] = '';
		self::save_job( $slug, $job );

		self::dequeue( $slug );
		$continue = self::has_pending();
		if ( $continue ) {
			self::schedule_next_batch();
		}

		return array(
			'ok'       => true,
			'continue' => $continue,
			'slug'     => $slug,
			'status'   => 'ready',
			'job'      => $job,
			'progress' => self::progress_payload( $slug, $job ),
		);
	}

	/**
	 * Fail a job with an error message.
	 *
	 * @param string               $slug    Translation slug.
	 * @param array<string, mixed> $job     Job state.
	 * @param string               $message Error.
	 * @return array<string, mixed>
	 */
	private static function fail_job( $slug, array $job, $message ) {
		$job['status']     = 'error';
		$job['error']      = (string) $message;
		$job['updated_at'] = time();
		self::save_job( $slug, $job );

		HWBL_Local_Bible_Store::upsert_translation(
			$slug,
			array(
				'status'        => 'error',
				'error_message' => (string) $message,
			)
		);

		self::dequeue( $slug );

		return array(
			'ok'       => false,
			'continue' => self::has_pending(),
			'slug'     => $slug,
			'status'   => 'error',
			'error'    => (string) $message,
			'job'      => $job,
			'progress' => self::progress_payload( $slug, $job ),
		);
	}

	/**
	 * Progress payload for admin UI.
	 *
	 * @param string               $slug Translation slug.
	 * @param array<string, mixed> $job  Job state.
	 * @return array<string, mixed>
	 */
	public static function progress_payload( $slug, ?array $job = null ) {
		if ( null === $job ) {
			$job = self::get_job( $slug );
		}
		$row = HWBL_Local_Bible_Store::get_translation( $slug );
		if ( ! is_array( $job ) ) {
			$job = array();
		}

		$total    = (int) ( $job['total'] ?? 0 );
		$imported = (int) ( $job['imported'] ?? 0 );
		$percent  = $total > 0 ? (int) min( 100, round( ( $imported / $total ) * 100 ) ) : 0;
		$status   = (string) ( $job['status'] ?? ( $row['status'] ?? 'not_installed' ) );

		return array(
			'slug'        => sanitize_key( $slug ),
			'status'      => $status,
			'total'       => $total,
			'imported'    => $imported,
			'percent'     => $percent,
			'verse_count' => (int) ( $row['verse_count'] ?? $imported ),
			'error'       => (string) ( $job['error'] ?? ( $row['error_message'] ?? '' ) ),
			'installed'   => HWBL_Local_Bible_Store::is_installed( $slug ),
		);
	}

	/**
	 * Download URL for a catalog slug.
	 *
	 * @param string $slug Translation slug.
	 * @return string
	 */
	public static function download_url_for( $slug ) {
		$slug    = sanitize_key( $slug );
		$catalog = HWBL_Local_Bible_Store::get_catalog();
		$source  = isset( $catalog[ $slug ]['source'] ) ? (string) $catalog[ $slug ]['source'] : '';

		if ( ! empty( $catalog[ $slug ]['download_url'] ) ) {
			return (string) $catalog[ $slug ]['download_url'];
		}

		if ( 'berean' === $source || 'bsb' === $slug ) {
			return self::BSB_TXT_URL;
		}

		if ( 'ebible_usfx' === $source ) {
			$ebible = isset( $catalog[ $slug ]['ebible'] ) ? (string) $catalog[ $slug ]['ebible'] : '';
			if ( '' === $ebible ) {
				$ebible = ( 'lsv' === $slug ) ? 'englsv' : '';
			}
			if ( '' !== $ebible ) {
				return self::EBIBLE_USFX_BASE . rawurlencode( $ebible ) . '_usfx.zip';
			}
		}

		if ( 'scrollmapper' === $source ) {
			$file_map = array(
				'bbe'     => 'BBE.json',
				'ylt'     => 'YLT.json',
				'dby'     => 'Darby.json',
				'gnv'     => 'Geneva1599.json',
				'cpdv'    => 'CPDV.json',
				'se1865'     => 'SpaRV1865.json',
				'crampon'    => 'FreCrampon.json',
				'luther1545' => 'GerBoLut.json',
			);
			$file = isset( $file_map[ $slug ] ) ? $file_map[ $slug ] : strtoupper( $slug ) . '.json';
			return self::SCROLLMAPPER_JSON . $file;
		}

		$modules = array(
			'kjv' => 'kjv',
			'asv' => 'asv',
			'web' => 'web',
			'gnv' => 'geneva',
		);
		$module = isset( $modules[ $slug ] ) ? $modules[ $slug ] : $slug;

		return self::SUPERSEARCH_DOWNLOAD . '?bible=' . rawurlencode( $module ) . '&format=json';
	}

	/**
	 * Parse a downloaded source file based on catalog source.
	 *
	 * @param string $path   Local file path.
	 * @param string $slug   Translation slug.
	 * @param string $source Catalog source key.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,text:string}>|WP_Error
	 */
	public static function parse_source_file( $path, $slug, $source ) {
		$source = sanitize_key( (string) $source );
		if ( 'berean' === $source || 'bsb' === $slug ) {
			return self::parse_bsb_txt( $path );
		}
		if ( 'scrollmapper' === $source ) {
			return self::parse_scrollmapper_json( $path );
		}
		if ( 'ebible_usfx' === $source || 'lsv' === $slug ) {
			return self::parse_ebible_usfx_zip( $path );
		}
		if ( 'usfx' === $source ) {
			if ( preg_match( '/\.zip$/i', (string) $path ) ) {
				return self::parse_ebible_usfx_zip( $path );
			}
			$xml = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $xml || '' === $xml ) {
				return new WP_Error( 'hwbl_parse', 'empty_file' );
			}
			return self::parse_usfx_xml( $xml );
		}
		if ( 'theword_ont' === $source ) {
			return self::parse_theword_ont( $path );
		}
		return self::parse_supersearch_json( $path, $slug );
	}

	/**
	 * Parse a theWord uncompressed Bible (.ont): one verse per line in Protestant order.
	 *
	 * @param string $path Local .ont path.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,text:string}>|WP_Error
	 */
	public static function parse_theword_ont( $path ) {
		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw || '' === $raw ) {
			return new WP_Error( 'hwbl_parse', 'empty_file' );
		}

		// Strip UTF-8 BOM.
		if ( "\xEF\xBB\xBF" === substr( $raw, 0, 3 ) ) {
			$raw = substr( $raw, 3 );
		}

		$lines = preg_split( '/\R/u', $raw );
		if ( ! is_array( $lines ) ) {
			return new WP_Error( 'hwbl_parse', 'invalid_theword_ont' );
		}

		$map_path = HWBL_PLUGIN_DIR . 'data/protestant-verse-counts.json';
		if ( ! is_readable( $map_path ) ) {
			return new WP_Error( 'hwbl_parse', 'verse_map_missing' );
		}
		$map = json_decode( (string) file_get_contents( $map_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $map ) || empty( $map ) ) {
			return new WP_Error( 'hwbl_parse', 'invalid_verse_map' );
		}

		$out = array();
		$i   = 0;
		$n   = count( $lines );
		foreach ( $map as $book ) {
			if ( ! is_array( $book ) ) {
				continue;
			}
			$book_id = (int) ( $book['book_id'] ?? 0 );
			$verses  = isset( $book['verses'] ) && is_array( $book['verses'] ) ? $book['verses'] : array();
			if ( $book_id < 1 || empty( $verses ) ) {
				continue;
			}
			foreach ( $verses as $chapter_index => $verse_count ) {
				$chapter     = (int) $chapter_index + 1;
				$verse_count = (int) $verse_count;
				for ( $verse = 1; $verse <= $verse_count; $verse++ ) {
					if ( $i >= $n ) {
						break 3;
					}
					$text = self::clean_verse_text( (string) $lines[ $i ] );
					$i++;
					if ( '' === $text ) {
						continue;
					}
					$out[] = array(
						'book_id' => $book_id,
						'chapter' => $chapter,
						'verse'   => $verse,
						'text'    => $text,
					);
				}
			}
		}

		// Append any leftover lines to the last mapped verse slot's chapter.
		if ( $i < $n && ! empty( $out ) ) {
			$last    = $out[ count( $out ) - 1 ];
			$book_id = (int) $last['book_id'];
			$chapter = (int) $last['chapter'];
			$verse   = (int) $last['verse'];
			for ( ; $i < $n; $i++ ) {
				$text = self::clean_verse_text( (string) $lines[ $i ] );
				if ( '' === $text ) {
					continue;
				}
				$verse++;
				$out[] = array(
					'book_id' => $book_id,
					'chapter' => $chapter,
					'verse'   => $verse,
					'text'    => $text,
				);
			}
		}

		return $out;
	}

	/**
	 * Temp directory under uploads.
	 *
	 * @return string|null
	 */
	private static function temp_dir() {
		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return null;
		}
		$dir = trailingslashit( $upload['basedir'] ) . 'hwbl-local-bibles';
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return null;
		}
		return $dir;
	}

	/**
	 * Download a remote file to a local path.
	 *
	 * @param string $url  Remote URL.
	 * @param string $path Local path.
	 * @return true|WP_Error
	 */
	private static function download_file( $url, $path ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$ua = 'Mozilla/5.0 (compatible; HiddenWordBibleLessons/' . ( defined( 'HWBL_VERSION' ) ? HWBL_VERSION : '1.0' ) . '; +https://thehiddenword.org)';
		$ua_filter = static function ( $args ) use ( $ua ) {
			if ( ! is_array( $args ) ) {
				$args = array();
			}
			$args['user-agent'] = $ua;
			if ( empty( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
				$args['headers'] = array();
			}
			$args['headers']['Accept'] = '*/*';
			return $args;
		};
		add_filter( 'http_request_args', $ua_filter, 10, 1 );

		$tmp = download_url( $url, 300 );
		if ( is_wp_error( $tmp ) ) {
			// Fallback to wp_remote_get for hosts that block download_url streams.
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 300,
					'sslverify'   => true,
					'redirection' => 5,
					'user-agent'  => $ua,
					'headers'     => array(
						'Accept' => '*/*',
					),
				)
			);
			remove_filter( 'http_request_args', $ua_filter, 10 );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			if ( $code < 200 || $code >= 300 || '' === $body ) {
				return new WP_Error( 'hwbl_download_failed', 'HTTP ' . $code );
			}
			// Reject API error JSON for SuperSearch.
			if ( '{' === substr( ltrim( $body ), 0, 1 ) ) {
				$probe = json_decode( $body, true );
				if ( is_array( $probe ) && ! empty( $probe['errors'] ) && empty( $probe['verses'] ) && empty( $probe['metadata'] ) ) {
					return new WP_Error( 'hwbl_download_failed', 'Remote API error' );
				}
			}
			$written = file_put_contents( $path, $body ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return false === $written ? new WP_Error( 'hwbl_download_failed', 'write_failed' ) : true;
		}

		remove_filter( 'http_request_args', $ua_filter, 10 );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		if ( ! @rename( $tmp, $path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
			if ( ! @copy( $tmp, $path ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $tmp );
				return new WP_Error( 'hwbl_download_failed', 'move_failed' );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $tmp );
		}

		return true;
	}

	/**
	 * Parse scrollmapper nested JSON (books → chapters → verses).
	 *
	 * @param string $path Local JSON path.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,text:string}>|WP_Error
	 */
	public static function parse_scrollmapper_json( $path ) {
		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw || '' === $raw ) {
			return new WP_Error( 'hwbl_parse', 'empty_file' );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || empty( $data['books'] ) || ! is_array( $data['books'] ) ) {
			return new WP_Error( 'hwbl_parse', 'invalid_scrollmapper_json' );
		}

		$out = array();
		foreach ( $data['books'] as $book ) {
			if ( ! is_array( $book ) ) {
				continue;
			}
			$book_id = HWBL_Books::resolve_book_query( (string) ( $book['name'] ?? '' ) );
			if ( $book_id < 1 ) {
				continue;
			}
			$chapters = isset( $book['chapters'] ) && is_array( $book['chapters'] ) ? $book['chapters'] : array();
			foreach ( $chapters as $chapter_row ) {
				if ( ! is_array( $chapter_row ) ) {
					continue;
				}
				$chapter = (int) ( $chapter_row['chapter'] ?? 0 );
				$verses  = isset( $chapter_row['verses'] ) && is_array( $chapter_row['verses'] ) ? $chapter_row['verses'] : array();
				foreach ( $verses as $verse_row ) {
					if ( ! is_array( $verse_row ) ) {
						continue;
					}
					$verse = (int) ( $verse_row['verse'] ?? 0 );
					$text  = self::clean_verse_text( (string) ( $verse_row['text'] ?? '' ) );
					if ( $chapter < 1 || $verse < 1 || '' === $text ) {
						continue;
					}
					$out[] = array(
						'book_id' => $book_id,
						'chapter' => $chapter,
						'verse'   => $verse,
						'text'    => $text,
					);
				}
			}
		}

		return $out;
	}

	/**
	 * Parse an eBible USFX zip (e.g. englsv_usfx.zip) into normalized verses.
	 *
	 * @param string $path Local zip path.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,text:string}>|WP_Error
	 */
	public static function parse_ebible_usfx_zip( $path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'hwbl_parse', 'ziparchive_unavailable' );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			return new WP_Error( 'hwbl_parse', 'zip_open_failed' );
		}

		$xml = '';
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = $zip->getNameIndex( $i );
			if ( ! is_string( $name ) || ! preg_match( '/usfx\.xml$/i', $name ) ) {
				continue;
			}
			$chunk = $zip->getFromIndex( $i );
			if ( is_string( $chunk ) && '' !== $chunk ) {
				$xml = $chunk;
				break;
			}
		}
		$zip->close();

		if ( '' === $xml ) {
			return new WP_Error( 'hwbl_parse', 'usfx_xml_missing' );
		}

		return self::parse_usfx_xml( $xml );
	}

	/**
	 * Parse USFX XML into normalized verses.
	 *
	 * @param string $xml USFX document.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,text:string}>|WP_Error
	 */
	public static function parse_usfx_xml( $xml ) {
		$xml = (string) $xml;
		if ( '' === $xml ) {
			return new WP_Error( 'hwbl_parse', 'empty_usfx' );
		}

		if ( ! preg_match_all( '/<book\s+id="([A-Z0-9]+)"[^>]*>(.*?)<\/book>/is', $xml, $books, PREG_SET_ORDER ) ) {
			return new WP_Error( 'hwbl_parse', 'invalid_usfx' );
		}

		$out = array();
		foreach ( $books as $book_match ) {
			$book_id = HWBL_Books::get_id_by_usfm( $book_match[1] );
			if ( $book_id < 1 ) {
				continue;
			}

			$body    = $book_match[2];
			$chapter = 0;
			if ( ! preg_match_all( '/<c\s+id="(\d+)"[^>]*\/?>|<v\s+id="(\d+)"[^>]*\/?>(.*?)<ve\s*\/>/is', $body, $parts, PREG_SET_ORDER ) ) {
				continue;
			}

			foreach ( $parts as $part ) {
				if ( '' !== (string) ( $part[1] ?? '' ) ) {
					$chapter = (int) $part[1];
					continue;
				}
				$verse = (int) ( $part[2] ?? 0 );
				$text  = self::clean_verse_text( wp_strip_all_tags( (string) ( $part[3] ?? '' ) ) );
				if ( $chapter < 1 || $verse < 1 || '' === $text ) {
					continue;
				}
				$out[] = array(
					'book_id' => $book_id,
					'chapter' => $chapter,
					'verse'   => $verse,
					'text'    => $text,
				);
			}
		}

		return $out;
	}

	/**
	 * Parse Bible SuperSearch JSON into normalized verses.
	 *
	 * @param string $path Local JSON path.
	 * @param string $slug Expected module slug.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,text:string}>|WP_Error
	 */
	public static function parse_supersearch_json( $path, $slug = '' ) {
		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw || '' === $raw ) {
			return new WP_Error( 'hwbl_parse', 'empty_file' );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || empty( $data['verses'] ) || ! is_array( $data['verses'] ) ) {
			return new WP_Error( 'hwbl_parse', 'invalid_supersearch_json' );
		}

		$out = array();
		foreach ( $data['verses'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$mapped = self::map_supersearch_row( $row );
			if ( $mapped ) {
				$out[] = $mapped;
			}
		}

		unset( $slug );
		return $out;
	}

	/**
	 * Map one SuperSearch verse row.
	 *
	 * @param array<string, mixed> $row Raw row.
	 * @return array{book_id:int,chapter:int,verse:int,text:string}|null
	 */
	public static function map_supersearch_row( array $row ) {
		$book_id = (int) ( $row['book'] ?? 0 );
		if ( $book_id < 1 || $book_id > HWBL_Books::MAX_BOOK_ID ) {
			$name    = (string) ( $row['book_name'] ?? '' );
			$book_id = HWBL_Books::resolve_book_query( $name );
		}
		$chapter = (int) ( $row['chapter'] ?? 0 );
		$verse   = (int) ( $row['verse'] ?? 0 );
		$text    = self::clean_verse_text( (string) ( $row['text'] ?? '' ) );

		if ( $book_id < 1 || $chapter < 1 || $verse < 1 || '' === $text ) {
			return null;
		}

		return array(
			'book_id' => $book_id,
			'chapter' => $chapter,
			'verse'   => $verse,
			'text'    => $text,
		);
	}

	/**
	 * Parse Berean BSB plain-text dump.
	 *
	 * @param string $path Local TXT path.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,text:string}>|WP_Error
	 */
	public static function parse_bsb_txt( $path ) {
		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw || '' === $raw ) {
			return new WP_Error( 'hwbl_parse', 'empty_file' );
		}

		// Strip UTF-8 BOM.
		if ( "\xEF\xBB\xBF" === substr( $raw, 0, 3 ) ) {
			$raw = substr( $raw, 3 );
		}

		$lines = preg_split( '/\R/u', $raw );
		if ( ! is_array( $lines ) ) {
			return new WP_Error( 'hwbl_parse', 'invalid_bsb_txt' );
		}

		$out = array();
		foreach ( $lines as $line ) {
			$mapped = self::map_bsb_line( (string) $line );
			if ( $mapped ) {
				$out[] = $mapped;
			}
		}

		return $out;
	}

	/**
	 * Map one BSB text line ("Genesis 1:1 In the beginning...").
	 *
	 * @param string $line Raw line.
	 * @return array{book_id:int,chapter:int,verse:int,text:string}|null
	 */
	public static function map_bsb_line( $line ) {
		$line = trim( (string) $line );
		if ( '' === $line ) {
			return null;
		}

		if ( ! preg_match( '/^(.+?)\s+(\d+):(\d+)\s+(.+)$/u', $line, $matches ) ) {
			return null;
		}

		$book_id = HWBL_Books::resolve_book_query( $matches[1] );
		$chapter = (int) $matches[2];
		$verse   = (int) $matches[3];
		$text    = self::clean_verse_text( $matches[4] );

		if ( $book_id < 1 || $chapter < 1 || $verse < 1 || '' === $text ) {
			return null;
		}

		return array(
			'book_id' => $book_id,
			'chapter' => $chapter,
			'verse'   => $verse,
			'text'    => $text,
		);
	}

	/**
	 * Normalize imported verse text.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function clean_verse_text( $text ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = wp_strip_all_tags( $text );
		// Drop common paragraph markers from KJV dumps.
		$text = preg_replace( '/^¶\s*/u', '', $text );
		$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
		return (string) $text;
	}

	/**
	 * Cancel/remove an installed or in-progress translation.
	 *
	 * @param string $slug Translation slug.
	 * @return bool
	 */
	public static function remove( $slug ) {
		$slug = sanitize_key( (string) $slug );
		self::clear_job( $slug );
		self::dequeue( $slug );
		return HWBL_Local_Bible_Store::remove_translation( $slug );
	}
}
