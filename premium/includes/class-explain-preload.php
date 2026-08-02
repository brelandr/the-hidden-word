<?php
/**
 * Chunked background preload of Bible reader AI explanations.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Explain_Preload
 */
class THW_Premium_Explain_Preload {

	const JOB_OPTION = 'thw_explain_preload_job';
	const CRON_HOOK  = 'thw_explain_preload_batch';

	/** Max AI generations per process tick (skips can exceed this). */
	const GENERATE_BATCH = 1;

	/** Max already-saved skips to walk per tick before yielding. */
	const SKIP_BATCH = 40;

	/** Pause the job after this many consecutive hard failures. */
	const MAX_CONSECUTIVE_ERRORS = 5;

	/** OpenAI Batch: requests to append to the JSONL file per tick. */
	const OPENAI_COLLECT_PER_TICK = 25;

	/** OpenAI Batch: submit when the local file reaches this many requests. */
	const OPENAI_SUBMIT_SIZE = 2000;

	/** OpenAI Batch: import this many output lines per tick. */
	const OPENAI_IMPORT_PER_TICK = 40;

	/** Poll delay (seconds) while waiting on an OpenAI batch. */
	const OPENAI_POLL_DELAY = 60;

	/** Max auto-resubmits for one OpenAI file after expired/failed before pausing. */
	const OPENAI_MAX_BATCH_RETRIES = 8;

	/** Max missing passages to snapshot into the job option at start. */
	const GAP_QUEUE_MAX = 2000;

	/**
	 * Wire cron / Action Scheduler.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'process_cron_batch' ) );
	}

	/**
	 * Default / empty job payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_job() {
		return array(
			'status'             => 'idle',
			'mode'               => 'realtime',
			'fill_gaps'          => false,
			'strategy'           => '',
			'translations'       => array(),
			'traditions'         => array(),
			'scopes'             => array(),
			'cursor'             => self::default_cursor(),
			'openai'             => self::default_openai_state(),
			'stats'              => array(
				'generated' => 0,
				'skipped'   => 0,
				'errors'    => 0,
				'processed' => 0,
				'total'     => 0,
				'queued'    => 0,
			),
			'gap_queue'          => array(),
			'gap_index'          => 0,
			'gap_samples'        => array(),
			'last_reference'     => '',
			'last_error'         => '',
			'consecutive_errors' => 0,
			'started_at'         => 0,
			'updated_at'         => 0,
			'finished_at'        => 0,
		);
	}

	/**
	 * Whether this job should only process missing passages.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return bool
	 */
	public static function job_is_fill_gaps( array $job ) {
		return ! empty( $job['fill_gaps'] ) || 'fill_gaps' === (string) ( $job['strategy'] ?? '' );
	}

	/**
	 * Default OpenAI Batch sub-state.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_openai_state() {
		return array(
			'phase'           => '',
			'batch_id'        => '',
			'input_file_id'   => '',
			'output_file_id'  => '',
			'batch_status'    => '',
			'local_input'     => '',
			'local_map'       => '',
			'local_output'    => '',
			'queued_in_file'  => 0,
			'import_offset'   => 0,
			'request_counts'  => array(),
			'retry_count'     => 0,
			'after_import'    => '',
		);
	}

	/**
	 * Default cursor.
	 *
	 * @return array<string, int|bool>
	 */
	public static function default_cursor() {
		return array(
			't'           => 0,
			'd'           => 0,
			's'           => 0,
			'book_id'     => 0,
			'chapter'     => 0,
			'verse'       => 0,
			'initialized' => false,
		);
	}

	/**
	 * Get job state.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_job() {
		$job = get_option( self::JOB_OPTION, null );
		if ( ! is_array( $job ) ) {
			return self::default_job();
		}
		return array_merge( self::default_job(), $job );
	}

	/**
	 * Persist job state.
	 *
	 * @param array<string, mixed> $job Job.
	 */
	public static function save_job( array $job ) {
		$job['updated_at'] = time();
		update_option( self::JOB_OPTION, $job, false );
	}

	/**
	 * Clear job state.
	 */
	public static function clear_job() {
		$job = self::get_job();
		self::cleanup_openai_artifacts( $job, true );
		delete_option( self::JOB_OPTION );
		self::clear_scheduled();
	}

	/**
	 * Normalize mode slug.
	 *
	 * @param mixed $mode Mode.
	 * @return string realtime|openai_batch
	 */
	public static function normalize_mode( $mode ) {
		$mode = sanitize_key( (string) $mode );
		return ( 'openai_batch' === $mode ) ? 'openai_batch' : 'realtime';
	}

	/**
	 * Sanitize start arguments.
	 *
	 * @param array<string, mixed> $args Raw args.
	 * @return array{translations:string[],traditions:string[],scopes:string[]}|WP_Error
	 */
	public static function sanitize_start_args( array $args ) {
		$translations = isset( $args['translations'] ) ? (array) $args['translations'] : array();
		$traditions   = isset( $args['traditions'] ) ? (array) $args['traditions'] : array();
		$scopes_in    = isset( $args['scopes'] ) ? (array) $args['scopes'] : array( 'verse' );

		$translations = array_values( array_unique( array_filter( array_map( 'sanitize_key', $translations ) ) ) );
		$traditions   = array_values( array_unique( array_filter( array_map( 'sanitize_key', $traditions ) ) ) );

		$scopes = array();
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

		if ( empty( $translations ) ) {
			return new WP_Error( 'thw_preload_no_bible', __( 'Select at least one installed local Bible.', 'hidden-word-bible-lessons' ) );
		}
		if ( empty( $traditions ) ) {
			return new WP_Error( 'thw_preload_no_tradition', __( 'Select at least one tradition.', 'hidden-word-bible-lessons' ) );
		}
		if ( empty( $scopes ) ) {
			return new WP_Error( 'thw_preload_no_scope', __( 'Select verse, chapter, or both.', 'hidden-word-bible-lessons' ) );
		}

		$ready = array();
		foreach ( $translations as $slug ) {
			if ( class_exists( 'HWBL_Local_Bible_Store' ) && HWBL_Local_Bible_Store::is_installed( $slug ) ) {
				$ready[] = $slug;
			}
		}
		if ( empty( $ready ) ) {
			return new WP_Error( 'thw_preload_bible_not_ready', __( 'None of the selected Bibles are installed locally. Install them under Local Bibles first.', 'hidden-word-bible-lessons' ) );
		}

		$valid_traditions = array();
		$choices          = function_exists( 'thw_premium_get_tradition_preset_choices' )
			? thw_premium_get_tradition_preset_choices()
			: array();
		$base_slug        = function_exists( 'thw_premium_explain_base_tradition_slug' )
			? thw_premium_explain_base_tradition_slug()
			: 'base';
		foreach ( $traditions as $slug ) {
			if ( $base_slug === $slug ) {
				$valid_traditions[] = $slug;
				continue;
			}
			if ( isset( $choices[ $slug ] ) || ( function_exists( 'thw_premium_sanitize_tradition_preset' ) && thw_premium_sanitize_tradition_preset( $slug ) ) ) {
				$valid_traditions[] = $slug;
			}
		}
		$valid_traditions = array_values( array_unique( $valid_traditions ) );
		if ( empty( $valid_traditions ) ) {
			return new WP_Error( 'thw_preload_bad_tradition', __( 'Select a valid tradition preset.', 'hidden-word-bible-lessons' ) );
		}

		// Shared base must run before tradition overrides.
		$needs_base = false;
		foreach ( $valid_traditions as $slug ) {
			if ( function_exists( 'thw_premium_explain_tradition_uses_shared_base' )
				? ! thw_premium_explain_tradition_uses_shared_base( $slug )
				: ( $base_slug !== $slug ) ) {
				$needs_base = true;
				break;
			}
		}
		if ( $needs_base && ! in_array( $base_slug, $valid_traditions, true ) ) {
			array_unshift( $valid_traditions, $base_slug );
		} elseif ( in_array( $base_slug, $valid_traditions, true ) ) {
			$valid_traditions = array_values(
				array_merge(
					array( $base_slug ),
					array_diff( $valid_traditions, array( $base_slug ) )
				)
			);
		}

		$mode = self::normalize_mode( $args['mode'] ?? 'realtime' );
		if ( 'openai_batch' === $mode ) {
			if ( ! class_exists( 'THW_Premium_AI_Client' ) || '' === THW_Premium_AI_Client::resolve_openai_api_key() ) {
				return new WP_Error(
					'thw_preload_need_openai',
					__( 'OpenAI Batch mode requires an OpenAI API key (Premium settings or Settings → Connectors).', 'hidden-word-bible-lessons' )
				);
			}
		}

		return array(
			'translations' => $ready,
			'traditions'   => $valid_traditions,
			'scopes'       => $scopes,
			'mode'         => $mode,
		);
	}

	/**
	 * Estimate total explains for a job configuration.
	 *
	 * @param string[] $translations Translation slugs.
	 * @param string[] $traditions   Tradition slugs.
	 * @param string[] $scopes       verse|chapter.
	 * @return int
	 */
	public static function estimate_total( array $translations, array $traditions, array $scopes ) {
		$trad_n = max( 1, count( $traditions ) );
		$total  = 0;

		foreach ( $translations as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( ! class_exists( 'HWBL_Local_Bible_Store' ) || ! HWBL_Local_Bible_Store::is_installed( $slug ) ) {
				continue;
			}
			$row         = HWBL_Local_Bible_Store::get_translation( $slug );
			$verse_count = is_array( $row ) ? (int) ( $row['verse_count'] ?? 0 ) : 0;
			if ( $verse_count < 1 ) {
				$verse_count = (int) HWBL_Local_Bible_Store::count_verses( $slug );
			}
			$chapter_count = (int) HWBL_Local_Bible_Store::count_chapters( $slug );

			foreach ( $scopes as $scope ) {
				if ( 'verse' === $scope ) {
					$total += $verse_count * $trad_n;
				} elseif ( 'chapter' === $scope ) {
					$total += $chapter_count * $trad_n;
				}
			}
		}

		return max( 0, (int) $total );
	}

	/**
	 * Start a new preload job (or replace a finished/idle one).
	 *
	 * @param array<string, mixed> $args Start args.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function start( array $args ) {
		$sanitized = self::sanitize_start_args( $args );
		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		$current = self::get_job();
		if ( 'running' === ( $current['status'] ?? '' ) ) {
			return new WP_Error( 'thw_preload_busy', __( 'A preload job is already running. Pause it first.', 'hidden-word-bible-lessons' ) );
		}

		$continue = ! empty( $args['continue'] );
		$same     = $continue
			&& in_array( (string) ( $current['status'] ?? '' ), array( 'paused', 'error' ), true )
			&& (array) ( $current['translations'] ?? array() ) === $sanitized['translations']
			&& (array) ( $current['traditions'] ?? array() ) === $sanitized['traditions']
			&& (array) ( $current['scopes'] ?? array() ) === $sanitized['scopes'];

		if ( $same ) {
			$job                       = array_merge( self::default_job(), $current );
			$job['status']             = 'running';
			$job['mode']               = $sanitized['mode'];
			$job['finished_at']        = 0;
			$job['consecutive_errors'] = 0;
			$job['last_error']         = '';
			if ( empty( $job['openai'] ) || ! is_array( $job['openai'] ) ) {
				$job['openai'] = self::default_openai_state();
			}
			// Switching into batch mid-job: keep cursor; drop only unfinished local collect files if mode changed.
			if ( 'openai_batch' !== self::normalize_mode( $current['mode'] ?? 'realtime' )
				&& 'openai_batch' === $sanitized['mode'] ) {
				self::cleanup_openai_artifacts( $job, false );
				$job['openai'] = self::default_openai_state();
			}
			self::save_job( $job );
			self::schedule_next_batch();
			return $job;
		}

		self::cleanup_openai_artifacts( $current, true );

		$job                       = self::default_job();
		$job['status']             = 'running';
		$job['mode']               = $sanitized['mode'];
		$job['translations']       = $sanitized['translations'];
		$job['traditions']         = $sanitized['traditions'];
		$job['scopes']             = $sanitized['scopes'];
		$job['stats']['total']     = self::estimate_total( $sanitized['translations'], $sanitized['traditions'], $sanitized['scopes'] );
		$job['started_at']         = time();
		$job['finished_at']        = 0;
		$job['consecutive_errors'] = 0;
		$job['cursor']             = self::default_cursor();

		self::save_job( $job );
		self::schedule_next_batch();

		return $job;
	}

	/**
	 * Start a job that only generates missing passages for one Bible × tradition × scope.
	 *
	 * @param array<string, mixed> $args translation, tradition, scope, mode.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function start_fill_gaps( array $args ) {
		$translation = sanitize_key( (string) ( $args['translation'] ?? '' ) );
		$tradition   = sanitize_key( (string) ( $args['tradition'] ?? '' ) );
		$scope       = sanitize_key( (string) ( $args['scope'] ?? 'verse' ) );
		if ( 'chapter' !== $scope ) {
			$scope = 'verse';
		}
		$mode = self::normalize_mode( $args['mode'] ?? 'realtime' );

		if ( '' === $translation || '' === $tradition ) {
			return new WP_Error( 'thw_preload_fill_keys', __( 'Choose a Bible and tradition to fill gaps.', 'hidden-word-bible-lessons' ) );
		}
		if ( ! class_exists( 'HWBL_Local_Bible_Store' ) || ! HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return new WP_Error( 'thw_preload_fill_bible', __( 'That Bible must be installed as a ready Local Bible before gaps can be filled.', 'hidden-word-bible-lessons' ) );
		}
		if ( ! class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			return new WP_Error( 'thw_preload_fill_store', __( 'Explain store is unavailable.', 'hidden-word-bible-lessons' ) );
		}

		$current = self::get_job();
		if ( 'running' === ( $current['status'] ?? '' ) ) {
			return new WP_Error( 'thw_preload_busy', __( 'A preload job is already running. Pause it first.', 'hidden-word-bible-lessons' ) );
		}

		if ( 'openai_batch' === $mode ) {
			if ( ! class_exists( 'THW_Premium_AI_Client' ) || '' === THW_Premium_AI_Client::resolve_openai_api_key() ) {
				return new WP_Error(
					'thw_preload_need_openai',
					__( 'OpenAI Batch mode requires an OpenAI API key (Premium settings or Settings → Connectors).', 'hidden-word-bible-lessons' )
				);
			}
		}

		$missing = THW_Premium_Bible_Reader_Explain_Store::count_missing_passages( $translation, $tradition, $scope );
		if ( $missing < 1 ) {
			return new WP_Error( 'thw_preload_no_gaps', __( 'No missing passages found for that Bible, tradition, and scope.', 'hidden-word-bible-lessons' ) );
		}

		$queue_limit = min( $missing, self::GAP_QUEUE_MAX );
		$gap_queue   = THW_Premium_Bible_Reader_Explain_Store::list_missing_passages( $translation, $tradition, $scope, $queue_limit, 0 );
		if ( empty( $gap_queue ) ) {
			return new WP_Error( 'thw_preload_no_gaps', __( 'No missing passages found for that Bible, tradition, and scope.', 'hidden-word-bible-lessons' ) );
		}

		$labels = array();
		foreach ( array_slice( $gap_queue, 0, 12 ) as $sample ) {
			$labels[] = self::format_gap_sample_label( $sample );
		}

		self::cleanup_openai_artifacts( $current, true );

		$job                       = self::default_job();
		$job['status']             = 'running';
		$job['mode']               = $mode;
		$job['fill_gaps']          = 1;
		$job['strategy']           = 'fill_gaps';
		$job['translations']       = array( $translation );
		$job['traditions']         = array( $tradition );
		$job['scopes']             = array( $scope );
		$job['stats']['total']     = $missing;
		$job['gap_queue']          = array_values( $gap_queue );
		$job['gap_index']          = 0;
		$job['gap_samples']        = array_values( array_filter( array_map( 'strval', $labels ) ) );
		$job['started_at']         = time();
		$job['finished_at']        = 0;
		$job['consecutive_errors'] = 0;
		$job['cursor']             = self::default_cursor();

		self::save_job( $job );
		self::schedule_next_batch();

		return $job;
	}

	/**
	 * Pause a running job.
	 *
	 * @return array<string, mixed>
	 */
	public static function pause() {
		$job = self::get_job();
		if ( 'running' === ( $job['status'] ?? '' ) ) {
			$job['status'] = 'paused';
			self::save_job( $job );
		}
		self::clear_scheduled();
		return self::get_job();
	}

	/**
	 * Resume a paused job.
	 *
	 * @param array<string, mixed> $args Optional: mode (realtime|openai_batch).
	 * @return array<string, mixed>|WP_Error
	 */
	public static function resume( array $args = array() ) {
		$job = self::get_job();
		if ( 'paused' !== ( $job['status'] ?? '' ) ) {
			return new WP_Error( 'thw_preload_not_paused', __( 'No paused preload job to resume.', 'hidden-word-bible-lessons' ) );
		}
		if ( empty( $job['translations'] ) || empty( $job['traditions'] ) || empty( $job['scopes'] ) ) {
			return new WP_Error( 'thw_preload_empty', __( 'Preload job is incomplete. Start a new one.', 'hidden-word-bible-lessons' ) );
		}

		if ( isset( $args['mode'] ) ) {
			$mode = self::normalize_mode( $args['mode'] );
			if ( 'openai_batch' === $mode ) {
				if ( ! class_exists( 'THW_Premium_AI_Client' ) || '' === THW_Premium_AI_Client::resolve_openai_api_key() ) {
					return new WP_Error(
						'thw_preload_need_openai',
						__( 'OpenAI Batch mode requires an OpenAI API key (Premium settings or Settings → Connectors).', 'hidden-word-bible-lessons' )
					);
				}
			}
			$prev = self::normalize_mode( $job['mode'] ?? 'realtime' );
			$job['mode'] = $mode;
			if ( $prev !== $mode && 'openai_batch' === $mode ) {
				// Keep cursor/stats; start a fresh Batch collect from here.
				$phase = is_array( $job['openai'] ?? null ) ? (string) ( $job['openai']['phase'] ?? '' ) : '';
				if ( 'submitted' !== $phase && 'importing' !== $phase ) {
					self::cleanup_openai_artifacts( $job, false );
					$job['openai'] = self::default_openai_state();
				}
			}
		}

		$job['status']             = 'running';
		$job['consecutive_errors'] = 0;
		$job['last_error']         = '';
		self::save_job( $job );
		self::schedule_next_batch();
		return self::get_job();
	}

	/**
	 * Public status payload for admin UI.
	 *
	 * @return array<string, mixed>
	 */
	public static function status_payload() {
		$job   = self::get_job();
		$stats = is_array( $job['stats'] ?? null ) ? $job['stats'] : array();
		$total = max( 0, (int) ( $stats['total'] ?? 0 ) );
		$done  = max( 0, (int) ( $stats['processed'] ?? 0 ) );
		$pct   = ( $total > 0 ) ? min( 100, (int) floor( ( $done / $total ) * 100 ) ) : ( 'done' === ( $job['status'] ?? '' ) ? 100 : 0 );

		$openai = is_array( $job['openai'] ?? null ) ? array_merge( self::default_openai_state(), $job['openai'] ) : self::default_openai_state();

		$fill_gaps = self::job_is_fill_gaps( $job );
		// Fill-gaps progress is generation-oriented (do not count Bible-walk skips).
		if ( $fill_gaps ) {
			$done = (int) ( $stats['generated'] ?? 0 ) + (int) ( $stats['errors'] ?? 0 ) + (int) ( $stats['skipped'] ?? 0 );
			$pct  = ( $total > 0 ) ? min( 100, (int) floor( ( $done / $total ) * 100 ) ) : ( 'done' === ( $job['status'] ?? '' ) ? 100 : 0 );
		}

		return array(
			'status'             => (string) ( $job['status'] ?? 'idle' ),
			'mode'               => self::normalize_mode( $job['mode'] ?? 'realtime' ),
			'fill_gaps'          => $fill_gaps,
			'strategy'           => (string) ( $job['strategy'] ?? '' ),
			'translations'       => array_values( (array) ( $job['translations'] ?? array() ) ),
			'traditions'         => array_values( (array) ( $job['traditions'] ?? array() ) ),
			'scopes'             => array_values( (array) ( $job['scopes'] ?? array() ) ),
			'stats'              => array(
				'generated' => (int) ( $stats['generated'] ?? 0 ),
				'skipped'   => (int) ( $stats['skipped'] ?? 0 ),
				'errors'    => (int) ( $stats['errors'] ?? 0 ),
				'processed' => $done,
				'total'     => $total,
				'queued'    => (int) ( $stats['queued'] ?? 0 ),
			),
			'percent'            => $pct,
			'gap_remaining'      => $fill_gaps ? max( 0, $total - $done ) : 0,
			'gap_samples'        => array_values( array_map( 'strval', (array) ( $job['gap_samples'] ?? array() ) ) ),
			'last_reference'     => (string) ( $job['last_reference'] ?? '' ),
			'last_error'         => (string) ( $job['last_error'] ?? '' ),
			'consecutive_errors' => (int) ( $job['consecutive_errors'] ?? 0 ),
			'started_at'         => (int) ( $job['started_at'] ?? 0 ),
			'updated_at'         => (int) ( $job['updated_at'] ?? 0 ),
			'finished_at'        => (int) ( $job['finished_at'] ?? 0 ),
			'cursor'             => is_array( $job['cursor'] ?? null ) ? $job['cursor'] : self::default_cursor(),
			'openai'             => array(
				'phase'          => (string) ( $openai['phase'] ?? '' ),
				'batch_id'       => (string) ( $openai['batch_id'] ?? '' ),
				'batch_status'   => (string) ( $openai['batch_status'] ?? '' ),
				'queued_in_file' => (int) ( $openai['queued_in_file'] ?? 0 ),
				'request_counts' => is_array( $openai['request_counts'] ?? null ) ? $openai['request_counts'] : array(),
			),
		);
	}

	/**
	 * Schedule next batch.
	 *
	 * @param int $delay Seconds until run (WP-Cron path).
	 */
	public static function schedule_next_batch( $delay = 2 ) {
		$delay = max( 1, (int) $delay );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::CRON_HOOK ) ) {
				return;
			}
			if ( $delay > 5 && function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action( time() + $delay, self::CRON_HOOK, array(), 'thw-explain-preload' );
				return;
			}
			as_enqueue_async_action( self::CRON_HOOK, array(), 'thw-explain-preload' );
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + $delay, self::CRON_HOOK );
		}
	}

	/**
	 * Clear scheduled batches.
	 */
	public static function clear_scheduled() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::CRON_HOOK, array(), 'thw-explain-preload' );
		}
	}

	/**
	 * Cron entry point.
	 */
	public static function process_cron_batch() {
		// process_batch() schedules the next tick itself when work remains.
		self::process_batch();
	}

	/**
	 * Process one chunk of work.
	 *
	 * @return array<string, mixed>
	 */
	public static function process_batch() {
		$job = self::get_job();
		if ( 'running' !== ( $job['status'] ?? '' ) ) {
			return array(
				'ok'       => true,
				'continue' => false,
				'status'   => (string) ( $job['status'] ?? 'idle' ),
				'job'      => self::status_payload(),
			);
		}

		if ( ! class_exists( 'THW_Premium_Bible_Reader_Explain' ) ) {
			$job['status']     = 'error';
			$job['last_error'] = 'explain_unavailable';
			$job['finished_at'] = time();
			self::save_job( $job );
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'error',
				'job'      => self::status_payload(),
			);
		}

		if ( 'openai_batch' === self::normalize_mode( $job['mode'] ?? 'realtime' ) ) {
			return self::process_openai_batch_tick( $job );
		}

		if ( self::job_is_fill_gaps( $job ) ) {
			return self::process_fill_gaps_realtime( $job );
		}

		$generated = 0;
		$skipped   = 0;
		$errors    = 0;
		$steps     = 0;
		$max_steps = self::SKIP_BATCH + self::GENERATE_BATCH;

		while ( $steps < $max_steps && $generated < self::GENERATE_BATCH ) {
			++$steps;
			$target = self::resolve_target( $job );
			if ( null === $target ) {
				$job['status']      = 'done';
				$job['finished_at'] = time();
				self::save_job( $job );
				self::clear_scheduled();
				return array(
					'ok'       => true,
					'continue' => false,
					'status'   => 'done',
					'job'      => self::status_payload(),
				);
			}

			$result = THW_Premium_Bible_Reader_Explain::generate_for_passage(
				(int) $target['book_id'],
				(int) $target['chapter'],
				(int) $target['verse'],
				(string) $target['translation'],
				(string) $target['scope'],
				(string) $target['tradition'],
				array(
					'bypass_rate_limit' => true,
					'user_id'           => get_current_user_id(),
				)
			);

			$job['last_reference'] = (string) ( $result['reference'] ?? $target['label'] ?? '' );
			$job['stats']['processed'] = (int) ( $job['stats']['processed'] ?? 0 ) + 1;

			if ( ! empty( $result['ok'] ) && ! empty( $result['skipped'] ) ) {
				++$skipped;
				$job['stats']['skipped']   = (int) ( $job['stats']['skipped'] ?? 0 ) + 1;
				$job['consecutive_errors'] = 0;
				$job['last_error']         = '';
			} elseif ( ! empty( $result['ok'] ) ) {
				++$generated;
				$job['stats']['generated'] = (int) ( $job['stats']['generated'] ?? 0 ) + 1;
				$job['consecutive_errors'] = 0;
				$job['last_error']         = '';
			} else {
				++$errors;
				$job['stats']['errors']    = (int) ( $job['stats']['errors'] ?? 0 ) + 1;
				$job['last_error']         = (string) ( $result['error'] ?? 'generate_failed' );
				$job['consecutive_errors'] = (int) ( $job['consecutive_errors'] ?? 0 ) + 1;

				if ( in_array( $job['last_error'], array( 'ai_unavailable', 'compliance_failed' ), true )
					|| $job['consecutive_errors'] >= self::MAX_CONSECUTIVE_ERRORS ) {
					$job['status'] = 'paused';
					self::save_job( $job );
					self::clear_scheduled();
					return array(
						'ok'       => false,
						'continue' => false,
						'status'   => 'paused',
						'message'  => $job['last_error'],
						'job'      => self::status_payload(),
					);
				}
			}

			$job = self::advance_job_cursor( $job, $target );
			self::save_job( $job );
		}

		$still = ( 'running' === ( $job['status'] ?? '' ) );
		if ( $still ) {
			self::schedule_next_batch();
		}

		return array(
			'ok'        => true,
			'continue'  => $still,
			'generated' => $generated,
			'skipped'   => $skipped,
			'errors'    => $errors,
			'status'    => (string) ( $job['status'] ?? 'idle' ),
			'job'       => self::status_payload(),
		);
	}

	/**
	 * Human label for a missing passage sample.
	 *
	 * @param array<string, mixed> $sample Sample keys.
	 * @return string
	 */
	private static function format_gap_sample_label( array $sample ) {
		$book_id = (int) ( $sample['book_id'] ?? 0 );
		$chapter = (int) ( $sample['chapter'] ?? 0 );
		$verse   = (int) ( $sample['verse'] ?? 0 );
		$scope   = sanitize_key( (string) ( $sample['scope'] ?? 'verse' ) );
		if ( class_exists( 'HWBL_Books' ) ) {
			return ( 'chapter' === $scope )
				? HWBL_Books::get_name( $book_id ) . ' ' . $chapter
				: HWBL_Books::format_reference( $book_id, $chapter, $verse );
		}
		return $book_id . ' ' . $chapter . ( ( 'verse' === $scope && $verse > 0 ) ? ':' . $verse : '' );
	}

	/**
	 * Next missing passage for a fill-gaps job (from the snapshot queue, not a Bible walk).
	 *
	 * @param array<string, mixed> $job   Job (by ref).
	 * @param int                  $depth Recursion guard for bad queue slots.
	 * @return array<string, mixed>|null
	 */
	private static function resolve_fill_gaps_target( array &$job, $depth = 0 ) {
		if ( $depth > 50 ) {
			return null;
		}
		$translation = sanitize_key( (string) ( ( array_values( (array) ( $job['translations'] ?? array() ) )[0] ?? '' ) ) );
		$tradition   = sanitize_key( (string) ( ( array_values( (array) ( $job['traditions'] ?? array() ) )[0] ?? '' ) ) );
		$scope       = sanitize_key( (string) ( ( array_values( (array) ( $job['scopes'] ?? array() ) )[0] ?? 'verse' ) ) );
		if ( 'chapter' !== $scope ) {
			$scope = 'verse';
		}
		if ( '' === $translation || '' === $tradition ) {
			return null;
		}

		$queue = array_values( (array) ( $job['gap_queue'] ?? array() ) );
		$index = max( 0, (int) ( $job['gap_index'] ?? 0 ) );

		if ( $index >= count( $queue ) ) {
			if ( ! class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
				return null;
			}
			// Refill from DB (offset 0): already-filled gaps drop out of the missing set.
			$more = THW_Premium_Bible_Reader_Explain_Store::list_missing_passages(
				$translation,
				$tradition,
				$scope,
				min( 100, self::GAP_QUEUE_MAX ),
				0
			);
			if ( empty( $more ) ) {
				return null;
			}
			$job['gap_queue'] = array_values( $more );
			$job['gap_index'] = 0;
			$queue            = $job['gap_queue'];
			$index            = 0;
		}

		$row = is_array( $queue[ $index ] ?? null ) ? $queue[ $index ] : null;
		$job['gap_index'] = $index + 1;
		if ( ! is_array( $row ) ) {
			// Bad queue slot — try the next one instead of ending the job.
			return self::resolve_fill_gaps_target( $job, $depth + 1 );
		}

		$book_id = (int) ( $row['book_id'] ?? 0 );
		$chapter = (int) ( $row['chapter'] ?? 0 );
		$verse   = ( 'chapter' === $scope ) ? 0 : (int) ( $row['verse'] ?? 0 );
		if ( $book_id < 1 || $chapter < 1 || ( 'verse' === $scope && $verse < 1 ) ) {
			return self::resolve_fill_gaps_target( $job, $depth + 1 );
		}

		$job['cursor'] = array(
			't'           => 0,
			'd'           => 0,
			's'           => 0,
			'book_id'     => $book_id,
			'chapter'     => $chapter,
			'verse'       => $verse,
			'initialized' => true,
		);

		$label = self::format_gap_sample_label(
			array(
				'book_id' => $book_id,
				'chapter' => $chapter,
				'verse'   => $verse,
				'scope'   => $scope,
			)
		);

		return array(
			'translation' => $translation,
			'tradition'   => $tradition,
			'scope'       => $scope,
			'book_id'     => $book_id,
			'chapter'     => $chapter,
			'verse'       => $verse,
			'label'       => $label,
		);
	}

	/**
	 * Mark a fill-gaps job complete and align totals with remaining DB gaps.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function finalize_fill_gaps_job( array $job ) {
		$translation = sanitize_key( (string) ( ( array_values( (array) ( $job['translations'] ?? array() ) )[0] ?? '' ) ) );
		$tradition   = sanitize_key( (string) ( ( array_values( (array) ( $job['traditions'] ?? array() ) )[0] ?? '' ) ) );
		$scope       = sanitize_key( (string) ( ( array_values( (array) ( $job['scopes'] ?? array() ) )[0] ?? 'verse' ) ) );
		if ( 'chapter' !== $scope ) {
			$scope = 'verse';
		}

		$remaining = 0;
		if ( $translation && $tradition && class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			$remaining = THW_Premium_Bible_Reader_Explain_Store::count_missing_passages( $translation, $tradition, $scope );
		}

		// If gaps remain, keep running with a fresh queue instead of stopping early.
		if ( $remaining > 0 ) {
			$job['gap_queue'] = THW_Premium_Bible_Reader_Explain_Store::list_missing_passages(
				$translation,
				$tradition,
				$scope,
				min( $remaining, self::GAP_QUEUE_MAX ),
				0
			);
			$job['gap_index']      = 0;
			$job['stats']['total'] = max( (int) ( $job['stats']['total'] ?? 0 ), $remaining + (int) ( $job['stats']['processed'] ?? 0 ) );
			$job['status']         = 'running';
			$job['last_error']     = '';
			self::save_job( $job );
			self::schedule_next_batch( 2 );
			return array(
				'ok'       => true,
				'continue' => true,
				'status'   => 'running',
				'job'      => self::status_payload(),
			);
		}

		$processed                 = (int) ( $job['stats']['processed'] ?? 0 );
		$job['stats']['total']     = max( $processed, (int) ( $job['stats']['total'] ?? 0 ) );
		$job['status']             = 'done';
		$job['finished_at']        = time();
		$job['gap_queue']          = array();
		$job['gap_index']          = 0;
		self::save_job( $job );
		self::clear_scheduled();
		return array(
			'ok'       => true,
			'continue' => false,
			'status'   => 'done',
			'job'      => self::status_payload(),
		);
	}

	/**
	 * Realtime processor that only generates queued missing passages.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function process_fill_gaps_realtime( array $job ) {
		$generated = 0;
		$skipped   = 0;
		$errors    = 0;
		$steps     = 0;
		// One AI call per tick; allow a few already-present skips without walking the Bible.
		$max_steps = self::GENERATE_BATCH + 5;

		while ( $steps < $max_steps && $generated < self::GENERATE_BATCH ) {
			++$steps;
			$target = self::resolve_fill_gaps_target( $job );
			if ( null === $target ) {
				return self::finalize_fill_gaps_job( $job );
			}

			$result = THW_Premium_Bible_Reader_Explain::generate_for_passage(
				(int) $target['book_id'],
				(int) $target['chapter'],
				(int) $target['verse'],
				(string) $target['translation'],
				(string) $target['scope'],
				(string) $target['tradition'],
				array(
					'bypass_rate_limit' => true,
					'user_id'           => get_current_user_id(),
				)
			);

			$job['last_reference']         = (string) ( $result['reference'] ?? $target['label'] ?? '' );
			$job['stats']['processed']     = (int) ( $job['stats']['processed'] ?? 0 ) + 1;

			if ( ! empty( $result['ok'] ) && ! empty( $result['skipped'] ) ) {
				++$skipped;
				$job['stats']['skipped']   = (int) ( $job['stats']['skipped'] ?? 0 ) + 1;
				$job['consecutive_errors'] = 0;
				$job['last_error']         = '';
			} elseif ( ! empty( $result['ok'] ) ) {
				++$generated;
				$job['stats']['generated'] = (int) ( $job['stats']['generated'] ?? 0 ) + 1;
				$job['consecutive_errors'] = 0;
				$job['last_error']         = '';
			} else {
				++$errors;
				$job['stats']['errors']    = (int) ( $job['stats']['errors'] ?? 0 ) + 1;
				$job['last_error']         = (string) ( $result['error'] ?? 'generate_failed' );
				$job['consecutive_errors'] = (int) ( $job['consecutive_errors'] ?? 0 ) + 1;

				if ( in_array( $job['last_error'], array( 'ai_unavailable', 'compliance_failed' ), true )
					|| $job['consecutive_errors'] >= self::MAX_CONSECUTIVE_ERRORS ) {
					$job['status'] = 'paused';
					self::save_job( $job );
					self::clear_scheduled();
					return array(
						'ok'       => false,
						'continue' => false,
						'status'   => 'paused',
						'message'  => $job['last_error'],
						'job'      => self::status_payload(),
					);
				}
			}

			self::save_job( $job );
		}

		$still = ( 'running' === ( $job['status'] ?? '' ) );
		if ( $still ) {
			self::schedule_next_batch();
		}

		return array(
			'ok'        => true,
			'continue'  => $still,
			'generated' => $generated,
			'skipped'   => $skipped,
			'errors'    => $errors,
			'status'    => (string) ( $job['status'] ?? 'idle' ),
			'job'       => self::status_payload(),
		);
	}

	/**
	 * Resolve the current cursor into a concrete passage target.
	 *
	 * @param array<string, mixed> $job Job (by value; may initialize cursor via advance).
	 * @return array<string, mixed>|null
	 */
	public static function resolve_target( array &$job ) {
		if ( self::job_is_fill_gaps( $job ) ) {
			return self::resolve_fill_gaps_target( $job );
		}

		$guard = 0;
		while ( $guard < 5000 ) {
			++$guard;
			$cursor = is_array( $job['cursor'] ?? null ) ? $job['cursor'] : self::default_cursor();
			$t_list = array_values( (array) ( $job['translations'] ?? array() ) );
			$d_list = array_values( (array) ( $job['traditions'] ?? array() ) );
			$s_list = array_values( (array) ( $job['scopes'] ?? array() ) );

			if ( empty( $t_list ) || empty( $d_list ) || empty( $s_list ) ) {
				return null;
			}

			$ti = (int) ( $cursor['t'] ?? 0 );
			$di = (int) ( $cursor['d'] ?? 0 );
			$si = (int) ( $cursor['s'] ?? 0 );

			if ( $ti >= count( $t_list ) ) {
				return null;
			}

			$translation = (string) $t_list[ $ti ];
			$tradition   = (string) $d_list[ $di ];
			$scope       = (string) $s_list[ $si ];

			if ( empty( $cursor['initialized'] ) ) {
				$books = class_exists( 'HWBL_Local_Bible_Store' )
					? HWBL_Local_Bible_Store::list_book_ids( $translation )
					: array();
				if ( empty( $books ) ) {
					$job = self::advance_to_next_scope_combo( $job );
					continue;
				}
				$book_id  = (int) $books[0];
				$chapters = HWBL_Local_Bible_Store::list_chapter_numbers( $translation, $book_id );
				if ( empty( $chapters ) ) {
					$job = self::advance_to_next_scope_combo( $job );
					continue;
				}
				$chapter = (int) $chapters[0];
				$verse   = 0;
				if ( 'verse' === $scope ) {
					$verses = HWBL_Local_Bible_Store::list_verse_numbers( $translation, $book_id, $chapter );
					if ( empty( $verses ) ) {
						$job['cursor'] = array(
							't'           => $ti,
							'd'           => $di,
							's'           => $si,
							'book_id'     => $book_id,
							'chapter'     => $chapter,
							'verse'       => 0,
							'initialized' => true,
						);
						$job = self::advance_job_cursor(
							$job,
							array(
								'translation' => $translation,
								'tradition'   => $tradition,
								'scope'       => $scope,
								'book_id'     => $book_id,
								'chapter'     => $chapter,
								'verse'       => 0,
							)
						);
						continue;
					}
					$verse = (int) $verses[0];
				}

				$job['cursor'] = array(
					't'           => $ti,
					'd'           => $di,
					's'           => $si,
					'book_id'     => $book_id,
					'chapter'     => $chapter,
					'verse'       => $verse,
					'initialized' => true,
				);
				self::save_job( $job );
			}

			$cursor  = $job['cursor'];
			$book_id = (int) ( $cursor['book_id'] ?? 0 );
			$chapter = (int) ( $cursor['chapter'] ?? 0 );
			$verse   = (int) ( $cursor['verse'] ?? 0 );

			if ( $book_id < 1 || $chapter < 1 ) {
				$job = self::advance_to_next_scope_combo( $job );
				continue;
			}

			if ( 'verse' === $scope && $verse < 1 ) {
				$job = self::advance_job_cursor(
					$job,
					array(
						'translation' => $translation,
						'tradition'   => $tradition,
						'scope'       => $scope,
						'book_id'     => $book_id,
						'chapter'     => $chapter,
						'verse'       => 0,
					)
				);
				continue;
			}

			$label = class_exists( 'HWBL_Books' )
				? ( 'chapter' === $scope
					? HWBL_Books::get_name( $book_id ) . ' ' . $chapter
					: HWBL_Books::format_reference( $book_id, $chapter, $verse ) )
				: ( $book_id . ' ' . $chapter . ( $verse ? ':' . $verse : '' ) );

			return array(
				'translation' => $translation,
				'tradition'   => $tradition,
				'scope'       => $scope,
				'book_id'     => $book_id,
				'chapter'     => $chapter,
				'verse'       => 'chapter' === $scope ? 0 : $verse,
				'label'       => $label,
			);
		}

		return null;
	}

	/**
	 * Advance cursor past the passage that was just processed.
	 *
	 * @param array<string, mixed> $job    Job.
	 * @param array<string, mixed> $target Last target.
	 * @return array<string, mixed>
	 */
	public static function advance_job_cursor( array $job, array $target ) {
		// Fill-gaps jobs advance via gap_index inside resolve_fill_gaps_target.
		if ( self::job_is_fill_gaps( $job ) ) {
			return $job;
		}

		$cursor = is_array( $job['cursor'] ?? null ) ? $job['cursor'] : self::default_cursor();
		$ti     = (int) ( $cursor['t'] ?? 0 );
		$di     = (int) ( $cursor['d'] ?? 0 );
		$si     = (int) ( $cursor['s'] ?? 0 );

		$translation = (string) ( $target['translation'] ?? '' );
		$scope       = (string) ( $target['scope'] ?? 'verse' );
		$book_id     = (int) ( $target['book_id'] ?? 0 );
		$chapter     = (int) ( $target['chapter'] ?? 0 );
		$verse       = (int) ( $target['verse'] ?? 0 );

		if ( 'verse' === $scope ) {
			$verses = class_exists( 'HWBL_Local_Bible_Store' )
				? HWBL_Local_Bible_Store::list_verse_numbers( $translation, $book_id, $chapter )
				: array();
			$idx = array_search( $verse, $verses, true );
			if ( false !== $idx && isset( $verses[ $idx + 1 ] ) ) {
				$cursor['verse'] = (int) $verses[ $idx + 1 ];
				$job['cursor']   = $cursor;
				return $job;
			}
		}

		$chapters = class_exists( 'HWBL_Local_Bible_Store' )
			? HWBL_Local_Bible_Store::list_chapter_numbers( $translation, $book_id )
			: array();
		$cidx = array_search( $chapter, $chapters, true );
		if ( false !== $cidx && isset( $chapters[ $cidx + 1 ] ) ) {
			$next_chapter = (int) $chapters[ $cidx + 1 ];
			$next_verse   = 0;
			if ( 'verse' === $scope ) {
				$verses = HWBL_Local_Bible_Store::list_verse_numbers( $translation, $book_id, $next_chapter );
				if ( empty( $verses ) ) {
					$cursor['chapter'] = $next_chapter;
					$cursor['verse']   = 0;
					$job['cursor']     = $cursor;
					return self::advance_job_cursor(
						$job,
						array(
							'translation' => $translation,
							'scope'       => $scope,
							'book_id'     => $book_id,
							'chapter'     => $next_chapter,
							'verse'       => 0,
						)
					);
				}
				$next_verse = (int) $verses[0];
			}
			$cursor['chapter'] = $next_chapter;
			$cursor['verse']   = $next_verse;
			$job['cursor']     = $cursor;
			return $job;
		}

		$books = class_exists( 'HWBL_Local_Bible_Store' )
			? HWBL_Local_Bible_Store::list_book_ids( $translation )
			: array();
		$bidx = array_search( $book_id, $books, true );
		if ( false !== $bidx && isset( $books[ $bidx + 1 ] ) ) {
			$next_book = (int) $books[ $bidx + 1 ];
			$chapters  = HWBL_Local_Bible_Store::list_chapter_numbers( $translation, $next_book );
			if ( empty( $chapters ) ) {
				$cursor['book_id'] = $next_book;
				$cursor['chapter'] = 0;
				$cursor['verse']   = 0;
				$job['cursor']     = $cursor;
				return self::advance_job_cursor(
					$job,
					array(
						'translation' => $translation,
						'scope'       => $scope,
						'book_id'     => $next_book,
						'chapter'     => 0,
						'verse'       => 0,
					)
				);
			}
			$next_chapter = (int) $chapters[0];
			$next_verse  = 0;
			if ( 'verse' === $scope ) {
				$verses = HWBL_Local_Bible_Store::list_verse_numbers( $translation, $next_book, $next_chapter );
				$next_verse = ! empty( $verses ) ? (int) $verses[0] : 0;
			}
			$cursor['book_id'] = $next_book;
			$cursor['chapter'] = $next_chapter;
			$cursor['verse']   = $next_verse;
			$job['cursor']     = $cursor;
			return $job;
		}

		return self::advance_to_next_scope_combo( $job );
	}

	/**
	 * Move to the next scope / tradition / translation combo.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	public static function advance_to_next_scope_combo( array $job ) {
		$cursor = is_array( $job['cursor'] ?? null ) ? $job['cursor'] : self::default_cursor();
		$t_list = array_values( (array) ( $job['translations'] ?? array() ) );
		$d_list = array_values( (array) ( $job['traditions'] ?? array() ) );
		$s_list = array_values( (array) ( $job['scopes'] ?? array() ) );

		$ti = (int) ( $cursor['t'] ?? 0 );
		$di = (int) ( $cursor['d'] ?? 0 );
		$si = (int) ( $cursor['s'] ?? 0 );

		++$si;
		if ( $si >= count( $s_list ) ) {
			$si = 0;
			++$di;
		}
		if ( $di >= count( $d_list ) ) {
			$di = 0;
			++$ti;
		}

		$job['cursor'] = array(
			't'           => $ti,
			'd'           => $di,
			's'           => $si,
			'book_id'     => 0,
			'chapter'     => 0,
			'verse'       => 0,
			'initialized' => false,
		);

		return $job;
	}

	/**
	 * Process one OpenAI Batch API tick (collect / poll / import).
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	public static function process_openai_batch_tick( array $job ) {
		$openai = array_merge( self::default_openai_state(), is_array( $job['openai'] ?? null ) ? $job['openai'] : array() );
		$phase  = (string) ( $openai['phase'] ?? '' );

		if ( 'submitted' === $phase ) {
			return self::openai_poll_submitted( $job );
		}
		if ( 'importing' === $phase ) {
			return self::openai_import_results( $job );
		}

		return self::openai_collect_requests( $job );
	}

	/**
	 * Working directory for Batch JSONL files.
	 *
	 * @return string
	 */
	private static function openai_workdir() {
		$upload = wp_upload_dir();
		$base   = trailingslashit( (string) ( $upload['basedir'] ?? '' ) ) . 'hwbl-explain-preload';
		if ( ! is_dir( $base ) ) {
			wp_mkdir_p( $base );
		}
		return $base;
	}

	/**
	 * Ensure local collect files exist on the job.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function openai_ensure_collect_files( array $job ) {
		$openai = array_merge( self::default_openai_state(), is_array( $job['openai'] ?? null ) ? $job['openai'] : array() );
		if ( empty( $openai['local_input'] ) || empty( $openai['local_map'] ) ) {
			$stamp = (int) ( $job['started_at'] ?? time() );
			$dir   = self::openai_workdir();
			$openai['local_input']    = $dir . '/batch-' . $stamp . '.jsonl';
			$openai['local_map']      = $dir . '/batch-' . $stamp . '.map.jsonl';
			$openai['queued_in_file'] = 0;
			$openai['phase']          = 'collecting';
		}
		$job['openai'] = $openai;
		return $job;
	}

	/**
	 * Encode a short custom_id from cursor indices + passage.
	 *
	 * @param array<string, mixed> $job    Job.
	 * @param array<string, mixed> $target Target.
	 * @return string
	 */
	private static function openai_custom_id( array $job, array $target ) {
		$c = is_array( $job['cursor'] ?? null ) ? $job['cursor'] : self::default_cursor();
		return sprintf(
			'%d.%d.%d.%d.%d.%d',
			(int) ( $c['t'] ?? 0 ),
			(int) ( $c['d'] ?? 0 ),
			(int) ( $c['s'] ?? 0 ),
			(int) ( $target['book_id'] ?? 0 ),
			(int) ( $target['chapter'] ?? 0 ),
			(int) ( $target['verse'] ?? 0 )
		);
	}

	/**
	 * Collect prompts into a local JSONL file, then submit when ready.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function openai_collect_requests( array $job ) {
		$job    = self::openai_ensure_collect_files( $job );
		$openai = $job['openai'];
		$input  = (string) $openai['local_input'];
		$map    = (string) $openai['local_map'];

		$collected = 0;
		$skipped   = 0;
		$steps     = 0;
		$max_steps = self::OPENAI_COLLECT_PER_TICK + self::SKIP_BATCH;
		$exhausted = false;

		while ( $steps < $max_steps && $collected < self::OPENAI_COLLECT_PER_TICK ) {
			++$steps;
			$target = self::resolve_target( $job );
			if ( null === $target ) {
				$exhausted = true;
				break;
			}

			$prepared = THW_Premium_Bible_Reader_Explain::prepare_passage_for_batch(
				(int) $target['book_id'],
				(int) $target['chapter'],
				(int) $target['verse'],
				(string) $target['translation'],
				(string) $target['scope'],
				(string) $target['tradition']
			);

			if ( is_wp_error( $prepared ) && 'base_required' === $prepared->get_error_code() ) {
				// Ensure shared base exists, then retry override prep.
				THW_Premium_Bible_Reader_Explain::generate_base_for_passage(
					(int) $target['book_id'],
					(int) $target['chapter'],
					(int) $target['verse'],
					(string) $target['translation'],
					(string) $target['scope'],
					array(
						'bypass_rate_limit' => true,
						'user_id'           => get_current_user_id(),
					)
				);
				$prepared = THW_Premium_Bible_Reader_Explain::prepare_passage_for_batch(
					(int) $target['book_id'],
					(int) $target['chapter'],
					(int) $target['verse'],
					(string) $target['translation'],
					(string) $target['scope'],
					(string) $target['tradition']
				);
			}

			if ( is_wp_error( $prepared ) ) {
				$job['stats']['errors']    = (int) ( $job['stats']['errors'] ?? 0 ) + 1;
				$job['stats']['processed'] = (int) ( $job['stats']['processed'] ?? 0 ) + 1;
				$job['last_error']         = $prepared->get_error_message();
				$job['consecutive_errors'] = (int) ( $job['consecutive_errors'] ?? 0 ) + 1;
				$job                       = self::advance_job_cursor( $job, $target );
				if ( $job['consecutive_errors'] >= self::MAX_CONSECUTIVE_ERRORS ) {
					$job['status'] = 'paused';
					$job['openai'] = $openai;
					self::save_job( $job );
					self::clear_scheduled();
					return array(
						'ok'       => false,
						'continue' => false,
						'status'   => 'paused',
						'job'      => self::status_payload(),
					);
				}
				continue;
			}

			$job['last_reference'] = (string) ( $prepared['reference'] ?? $target['label'] ?? '' );

			if ( ! empty( $prepared['skipped'] ) ) {
				++$skipped;
				$job['stats']['skipped']   = (int) ( $job['stats']['skipped'] ?? 0 ) + 1;
				$job['stats']['processed'] = (int) ( $job['stats']['processed'] ?? 0 ) + 1;
				$job['consecutive_errors'] = 0;
				$job['last_error']         = '';
				$job                       = self::advance_job_cursor( $job, $target );
				continue;
			}

			$custom_id = self::openai_custom_id( $job, $target );
			$request   = THW_Premium_AI_Client::build_openai_batch_request(
				$custom_id,
				(string) $prepared['prompt'],
				(string) $prepared['system']
			);
			$line = wp_json_encode( $request );
			$map_line = wp_json_encode(
				array(
					'id'          => $custom_id,
					'translation' => (string) ( $prepared['store_payload']['translation'] ?? $target['translation'] ),
					'tradition'   => (string) ( $prepared['store_payload']['tradition'] ?? $target['tradition'] ),
					'scope'       => (string) ( $prepared['store_payload']['scope'] ?? $target['scope'] ),
					'book_id'     => (int) ( $prepared['store_payload']['book_id'] ?? $target['book_id'] ),
					'chapter'     => (int) ( $prepared['store_payload']['chapter'] ?? $target['chapter'] ),
					'verse'       => (int) ( $prepared['store_payload']['verse'] ?? $target['verse'] ),
					'reference'   => (string) ( $prepared['reference'] ?? '' ),
				)
			);

			if ( ! $line || ! $map_line ) {
				$job['last_error'] = 'json_encode_failed';
				$job               = self::advance_job_cursor( $job, $target );
				continue;
			}

			$ok1 = ( false !== file_put_contents( $input, $line . "\n", FILE_APPEND | LOCK_EX ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$ok2 = ( false !== file_put_contents( $map, $map_line . "\n", FILE_APPEND | LOCK_EX ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( ! $ok1 || ! $ok2 ) {
				$job['status']     = 'paused';
				$job['last_error'] = 'batch_file_write_failed';
				$job['openai']     = $openai;
				self::save_job( $job );
				self::clear_scheduled();
				return array(
					'ok'       => false,
					'continue' => false,
					'status'   => 'paused',
					'job'      => self::status_payload(),
				);
			}

			++$collected;
			$openai['queued_in_file']      = (int) ( $openai['queued_in_file'] ?? 0 ) + 1;
			$job['stats']['queued']        = (int) ( $job['stats']['queued'] ?? 0 ) + 1;
			$job['consecutive_errors']     = 0;
			$job['last_error']             = '';
			$openai['phase']               = 'collecting';
			$job                           = self::advance_job_cursor( $job, $target );
		}

		$job['openai'] = $openai;
		self::save_job( $job );

		$queued = (int) ( $openai['queued_in_file'] ?? 0 );
		$should_submit = ( $queued > 0 ) && ( $exhausted || $queued >= self::OPENAI_SUBMIT_SIZE );
		if ( $should_submit ) {
			return self::openai_submit_file( $job );
		}

		if ( $exhausted && 0 === $queued ) {
			if ( self::job_is_fill_gaps( $job ) ) {
				$job['openai'] = self::default_openai_state();
				return self::finalize_fill_gaps_job( $job );
			}
			$job['status']      = 'done';
			$job['finished_at'] = time();
			$job['openai']      = self::default_openai_state();
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => true,
				'continue' => false,
				'status'   => 'done',
				'job'      => self::status_payload(),
			);
		}

		self::schedule_next_batch( 2 );
		return array(
			'ok'        => true,
			'continue'  => true,
			'collected' => $collected,
			'skipped'   => $skipped,
			'status'    => 'running',
			'job'       => self::status_payload(),
		);
	}

	/**
	 * Upload local JSONL and create an OpenAI batch.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function openai_submit_file( array $job ) {
		$openai = array_merge( self::default_openai_state(), is_array( $job['openai'] ?? null ) ? $job['openai'] : array() );
		$input  = (string) ( $openai['local_input'] ?? '' );

		$upload = THW_Premium_AI_Client::openai_upload_batch_file( $input );
		if ( is_wp_error( $upload ) ) {
			$job['status']     = 'paused';
			$job['last_error'] = $upload->get_error_message();
			$job['openai']     = $openai;
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'paused',
				'job'      => self::status_payload(),
			);
		}

		$file_id = (string) ( $upload['id'] ?? '' );
		$batch   = THW_Premium_AI_Client::openai_create_batch( $file_id );
		if ( is_wp_error( $batch ) ) {
			$job['status']     = 'paused';
			$job['last_error'] = $batch->get_error_message();
			$job['openai']     = $openai;
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'paused',
				'job'      => self::status_payload(),
			);
		}

		$openai['phase']          = 'submitted';
		$openai['input_file_id']  = $file_id;
		$openai['batch_id']       = (string) ( $batch['id'] ?? '' );
		$openai['batch_status']   = (string) ( $batch['status'] ?? 'validating' );
		$openai['request_counts'] = is_array( $batch['request_counts'] ?? null ) ? $batch['request_counts'] : array();
		$job['openai']            = $openai;
		$job['last_reference']    = sprintf(
			/* translators: 1: OpenAI batch id, 2: status */
			__( 'OpenAI batch %1$s (%2$s)', 'hidden-word-bible-lessons' ),
			$openai['batch_id'],
			$openai['batch_status']
		);
		$job['last_error'] = '';
		self::save_job( $job );
		self::schedule_next_batch( self::OPENAI_POLL_DELAY );

		return array(
			'ok'       => true,
			'continue' => true,
			'status'   => 'running',
			'job'      => self::status_payload(),
		);
	}

	/**
	 * Poll a submitted OpenAI batch until complete, then start import.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function openai_poll_submitted( array $job ) {
		$openai   = array_merge( self::default_openai_state(), is_array( $job['openai'] ?? null ) ? $job['openai'] : array() );
		$batch_id = (string) ( $openai['batch_id'] ?? '' );
		if ( '' === $batch_id ) {
			$openai['phase'] = '';
			$job['openai']   = $openai;
			self::save_job( $job );
			return self::openai_collect_requests( $job );
		}

		$batch = THW_Premium_AI_Client::openai_get_batch( $batch_id );
		if ( is_wp_error( $batch ) ) {
			$job['last_error'] = $batch->get_error_message();
			$job['openai']     = $openai;
			self::save_job( $job );
			self::schedule_next_batch( self::OPENAI_POLL_DELAY );
			return array(
				'ok'       => false,
				'continue' => true,
				'status'   => 'running',
				'job'      => self::status_payload(),
			);
		}

		$status                     = (string) ( $batch['status'] ?? '' );
		$openai['batch_status']     = $status;
		$openai['request_counts']   = is_array( $batch['request_counts'] ?? null ) ? $batch['request_counts'] : array();
		$job['last_reference']      = sprintf(
			/* translators: 1: OpenAI batch id, 2: status */
			__( 'OpenAI batch %1$s (%2$s)', 'hidden-word-bible-lessons' ),
			$batch_id,
			$status ? $status : 'unknown'
		);

		if ( 'cancelled' === $status ) {
			$job['status']     = 'paused';
			$job['last_error'] = 'openai_batch_cancelled';
			$job['openai']     = $openai;
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'paused',
				'job'      => self::status_payload(),
			);
		}

		if ( in_array( $status, array( 'failed', 'expired' ), true ) ) {
			$job['openai'] = $openai;
			return self::openai_recover_terminal_batch( $job, $batch, $status );
		}

		if ( 'completed' === $status ) {
			$output_id = (string) ( $batch['output_file_id'] ?? '' );
			if ( '' === $output_id ) {
				$job['openai'] = $openai;
				return self::openai_recover_terminal_batch( $job, $batch, 'no_output' );
			}

			$job['openai'] = $openai;
			return self::openai_begin_import( $job, $output_id, $batch_id, '' );
		}

		$job['openai'] = $openai;
		self::save_job( $job );
		self::schedule_next_batch( self::OPENAI_POLL_DELAY );
		return array(
			'ok'       => true,
			'continue' => true,
			'status'   => 'running',
			'job'      => self::status_payload(),
		);
	}

	/**
	 * Download batch output and enter the importing phase.
	 *
	 * @param array<string, mixed> $job       Job.
	 * @param string               $output_id OpenAI output file id.
	 * @param string               $batch_id  Batch id (for local filename).
	 * @param string               $after     Optional after_import action (e.g. resubmit_missing).
	 * @return array<string, mixed>
	 */
	private static function openai_begin_import( array $job, $output_id, $batch_id, $after = '' ) {
		$openai   = array_merge( self::default_openai_state(), is_array( $job['openai'] ?? null ) ? $job['openai'] : array() );
		$out_path = self::openai_workdir() . '/batch-out-' . sanitize_file_name( (string) $batch_id ) . '.jsonl';
		$dl       = THW_Premium_AI_Client::openai_download_file( $output_id, $out_path );
		if ( is_wp_error( $dl ) ) {
			// Still try to resubmit unfinished work if this was a recovery path.
			if ( 'resubmit_missing' === $after ) {
				$job['last_error'] = $dl->get_error_message();
				$job['openai']     = $openai;
				return self::openai_resubmit_missing( $job );
			}
			$job['status']     = 'paused';
			$job['last_error'] = $dl->get_error_message();
			$job['openai']     = $openai;
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'paused',
				'job'      => self::status_payload(),
			);
		}

		$openai['phase']          = 'importing';
		$openai['output_file_id'] = (string) $output_id;
		$openai['local_output']   = $out_path;
		$openai['import_offset']  = 0;
		$openai['after_import']   = sanitize_key( (string) $after );
		$job['openai']            = $openai;
		$job['last_error']        = '';
		self::save_job( $job );
		self::schedule_next_batch( 2 );
		return array(
			'ok'       => true,
			'continue' => true,
			'status'   => 'running',
			'job'      => self::status_payload(),
		);
	}

	/**
	 * After expired/failed: import any partial results, then auto-resubmit leftovers.
	 *
	 * @param array<string, mixed> $job    Job.
	 * @param array<string, mixed> $batch  OpenAI batch object.
	 * @param string               $status Terminal status label.
	 * @return array<string, mixed>
	 */
	private static function openai_recover_terminal_batch( array $job, array $batch, $status ) {
		$openai  = array_merge( self::default_openai_state(), is_array( $job['openai'] ?? null ) ? $job['openai'] : array() );
		$retries = (int) ( $openai['retry_count'] ?? 0 );
		if ( $retries >= self::OPENAI_MAX_BATCH_RETRIES ) {
			$job['status']     = 'paused';
			$job['last_error'] = 'openai_batch_' . sanitize_key( (string) $status ) . '_max_retries';
			$job['openai']     = $openai;
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'paused',
				'job'      => self::status_payload(),
			);
		}

		$job['last_error'] = sprintf(
			/* translators: 1: batch status, 2: retry number, 3: max retries */
			__( 'OpenAI batch %1$s — auto-retry %2$d/%3$d', 'hidden-word-bible-lessons' ),
			sanitize_key( (string) $status ),
			$retries + 1,
			self::OPENAI_MAX_BATCH_RETRIES
		);
		$job['last_reference'] = $job['last_error'];

		$output_id = (string) ( $batch['output_file_id'] ?? '' );
		$batch_id  = (string) ( $openai['batch_id'] ?? ( $batch['id'] ?? 'recover' ) );
		if ( '' !== $output_id ) {
			$openai['after_import'] = 'resubmit_missing';
			$job['openai']          = $openai;
			return self::openai_begin_import( $job, $output_id, $batch_id, 'resubmit_missing' );
		}

		$job['openai'] = $openai;
		return self::openai_resubmit_missing( $job );
	}

	/**
	 * Rebuild JSONL for passages still missing, then submit a new OpenAI batch.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function openai_resubmit_missing( array $job ) {
		$openai = array_merge( self::default_openai_state(), is_array( $job['openai'] ?? null ) ? $job['openai'] : array() );
		$input  = (string) ( $openai['local_input'] ?? '' );
		$map    = (string) ( $openai['local_map'] ?? '' );

		if ( '' === $input || '' === $map || ! is_readable( $input ) || ! is_readable( $map ) ) {
			// Nothing left to retry — continue scanning the Bible from the cursor.
			if ( ! empty( $openai['local_output'] ) && file_exists( (string) $openai['local_output'] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( (string) $openai['local_output'] );
			}
			$openai['phase']          = '';
			$openai['batch_id']       = '';
			$openai['input_file_id']  = '';
			$openai['output_file_id'] = '';
			$openai['batch_status']   = '';
			$openai['local_output']   = '';
			$openai['import_offset']  = 0;
			$openai['after_import']   = '';
			$openai['retry_count']    = 0;
			$openai['queued_in_file'] = 0;
			$openai['local_input']    = '';
			$openai['local_map']      = '';
			$job['openai']            = $openai;
			$job['last_error']        = '';
			self::save_job( $job );
			return self::openai_collect_requests( $job );
		}

		$requests = self::openai_load_requests_by_id( $input );
		$map_rows = self::openai_load_map( $map );
		$kept_req = array();
		$kept_map = array();

		foreach ( $map_rows as $id => $meta ) {
			if ( self::openai_map_entry_is_saved( $meta ) ) {
				continue;
			}
			if ( empty( $requests[ $id ] ) ) {
				continue;
			}
			$kept_req[] = $requests[ $id ];
			$kept_map[] = $meta;
		}

		// Drop old output; rewrite input/map to unfinished only.
		if ( ! empty( $openai['local_output'] ) && file_exists( (string) $openai['local_output'] ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( (string) $openai['local_output'] );
		}

		if ( empty( $kept_req ) ) {
			self::cleanup_openai_artifacts( $job, false );
			$job['openai']     = self::default_openai_state();
			$job['last_error'] = '';
			self::save_job( $job );
			return self::openai_collect_requests( $job );
		}

		$in_body  = '';
		$map_body = '';
		foreach ( $kept_req as $req ) {
			$line = wp_json_encode( $req );
			if ( $line ) {
				$in_body .= $line . "\n";
			}
		}
		foreach ( $kept_map as $meta ) {
			$line = wp_json_encode( $meta );
			if ( $line ) {
				$map_body .= $line . "\n";
			}
		}

		$ok1 = ( false !== file_put_contents( $input, $in_body, LOCK_EX ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$ok2 = ( false !== file_put_contents( $map, $map_body, LOCK_EX ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( ! $ok1 || ! $ok2 ) {
			$job['status']     = 'paused';
			$job['last_error'] = 'batch_resubmit_write_failed';
			$job['openai']     = $openai;
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'paused',
				'job'      => self::status_payload(),
			);
		}

		$openai['phase']          = 'collecting';
		$openai['batch_id']       = '';
		$openai['input_file_id']  = '';
		$openai['output_file_id'] = '';
		$openai['batch_status']   = '';
		$openai['local_output']   = '';
		$openai['import_offset']  = 0;
		$openai['after_import']   = '';
		$openai['queued_in_file'] = count( $kept_req );
		$openai['retry_count']    = (int) ( $openai['retry_count'] ?? 0 ) + 1;
		$job['openai']            = $openai;
		$job['last_reference']    = sprintf(
			/* translators: 1: remaining request count, 2: retry number */
			__( 'Resubmitting %1$s unfinished explains (retry %2$d)', 'hidden-word-bible-lessons' ),
			number_format_i18n( count( $kept_req ) ),
			(int) $openai['retry_count']
		);
		self::save_job( $job );

		return self::openai_submit_file( $job );
	}

	/**
	 * Whether a map row already has a usable saved explanation.
	 *
	 * @param array<string, mixed> $meta Map row.
	 * @return bool
	 */
	private static function openai_map_entry_is_saved( array $meta ) {
		if ( ! class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			return false;
		}
		$payload = array(
			'translation' => (string) ( $meta['translation'] ?? '' ),
			'tradition'   => (string) ( $meta['tradition'] ?? '' ),
			'scope'       => (string) ( $meta['scope'] ?? 'verse' ),
			'book_id'     => (int) ( $meta['book_id'] ?? 0 ),
			'chapter'     => (int) ( $meta['chapter'] ?? 0 ),
			'verse'       => (int) ( $meta['verse'] ?? 0 ),
			'reference'   => (string) ( $meta['reference'] ?? '' ),
		);
		$saved = THW_Premium_Bible_Reader_Explain_Store::find( $payload );
		return is_array( $saved ) && THW_Premium_Bible_Reader_Explain_Store::has_usable_explanation( $saved );
	}

	/**
	 * Load Batch JSONL requests keyed by custom_id.
	 *
	 * @param string $input_path Path.
	 * @return array<string, array<string, mixed>>
	 */
	private static function openai_load_requests_by_id( $input_path ) {
		$out = array();
		if ( '' === $input_path || ! is_readable( $input_path ) ) {
			return $out;
		}
		$handle = fopen( $input_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return $out;
		}
		while ( ( $line = fgets( $handle ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
			$row = json_decode( trim( (string) $line ), true );
			if ( is_array( $row ) && ! empty( $row['custom_id'] ) ) {
				$out[ (string) $row['custom_id'] ] = $row;
			}
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $out;
	}

	/**
	 * Import completed OpenAI batch output lines into the explains store.
	 *
	 * @param array<string, mixed> $job Job.
	 * @return array<string, mixed>
	 */
	private static function openai_import_results( array $job ) {
		$openai  = array_merge( self::default_openai_state(), is_array( $job['openai'] ?? null ) ? $job['openai'] : array() );
		$output  = (string) ( $openai['local_output'] ?? '' );
		$map     = (string) ( $openai['local_map'] ?? '' );
		$offset  = max( 0, (int) ( $openai['import_offset'] ?? 0 ) );

		if ( '' === $output || ! is_readable( $output ) ) {
			$job['status']     = 'paused';
			$job['last_error'] = 'batch_output_missing';
			$job['openai']     = $openai;
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'paused',
				'job'      => self::status_payload(),
			);
		}

		$map_by_id = self::openai_load_map( $map );
		$handle    = fopen( $output, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			$job['status']     = 'paused';
			$job['last_error'] = 'batch_output_open_failed';
			$job['openai']     = $openai;
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => false,
				'continue' => false,
				'status'   => 'paused',
				'job'      => self::status_payload(),
			);
		}

		$line_no   = 0;
		$imported  = 0;
		$errors    = 0;
		$done_file = true;

		while ( ( $line = fgets( $handle ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
			if ( $line_no < $offset ) {
				++$line_no;
				continue;
			}
			if ( $imported + $errors >= self::OPENAI_IMPORT_PER_TICK ) {
				$done_file = false;
				break;
			}

			++$line_no;
			$row = json_decode( trim( (string) $line ), true );
			if ( ! is_array( $row ) ) {
				++$errors;
				$job['stats']['errors']    = (int) ( $job['stats']['errors'] ?? 0 ) + 1;
				$job['stats']['processed'] = (int) ( $job['stats']['processed'] ?? 0 ) + 1;
				continue;
			}

			$custom_id = (string) ( $row['custom_id'] ?? '' );
			$meta      = isset( $map_by_id[ $custom_id ] ) && is_array( $map_by_id[ $custom_id ] ) ? $map_by_id[ $custom_id ] : null;
			$content   = '';
			if ( empty( $row['error'] ) && isset( $row['response']['body']['choices'][0]['message']['content'] ) ) {
				$content = (string) $row['response']['body']['choices'][0]['message']['content'];
			} elseif ( empty( $row['error'] ) && isset( $row['response']['body'] ) && is_string( $row['response']['body'] ) ) {
				$body = json_decode( $row['response']['body'], true );
				if ( is_array( $body ) && isset( $body['choices'][0]['message']['content'] ) ) {
					$content = (string) $body['choices'][0]['message']['content'];
				}
			}

			if ( ! $meta || '' === trim( $content ) ) {
				++$errors;
				$job['stats']['errors']    = (int) ( $job['stats']['errors'] ?? 0 ) + 1;
				$job['stats']['processed'] = (int) ( $job['stats']['processed'] ?? 0 ) + 1;
				$job['last_error']         = $custom_id ? ( 'batch_item_failed:' . $custom_id ) : 'batch_item_failed';
				continue;
			}

			$store_payload = array(
				'translation' => (string) ( $meta['translation'] ?? '' ),
				'tradition'   => (string) ( $meta['tradition'] ?? '' ),
				'scope'       => (string) ( $meta['scope'] ?? 'verse' ),
				'book_id'     => (int) ( $meta['book_id'] ?? 0 ),
				'chapter'     => (int) ( $meta['chapter'] ?? 0 ),
				'verse'       => (int) ( $meta['verse'] ?? 0 ),
				'reference'   => (string) ( $meta['reference'] ?? '' ),
			);

			$saved = THW_Premium_Bible_Reader_Explain::persist_passage_explanation( $store_payload, $content, false );
			$job['last_reference'] = (string) ( $saved['reference'] ?? $store_payload['reference'] );
			$job['stats']['processed'] = (int) ( $job['stats']['processed'] ?? 0 ) + 1;

			if ( ! empty( $saved['ok'] ) && ! empty( $saved['no_diff'] ) ) {
				$job['stats']['skipped'] = (int) ( $job['stats']['skipped'] ?? 0 ) + 1;
				$job['last_error']       = '';
			} elseif ( ! empty( $saved['ok'] ) ) {
				++$imported;
				$job['stats']['generated'] = (int) ( $job['stats']['generated'] ?? 0 ) + 1;
				$job['last_error']         = '';
			} else {
				++$errors;
				$job['stats']['errors'] = (int) ( $job['stats']['errors'] ?? 0 ) + 1;
				$job['last_error']      = (string) ( $saved['error'] ?? 'save_failed' );
			}
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$openai['import_offset'] = $line_no;
		$job['openai']           = $openai;

		if ( ! $done_file ) {
			self::save_job( $job );
			self::schedule_next_batch( 2 );
			return array(
				'ok'       => true,
				'continue' => true,
				'imported' => $imported,
				'errors'   => $errors,
				'status'   => 'running',
				'job'      => self::status_payload(),
			);
		}

		$after = (string) ( $openai['after_import'] ?? '' );
		if ( 'resubmit_missing' === $after ) {
			$openai['after_import']  = '';
			$openai['import_offset'] = 0;
			$job['openai']           = $openai;
			self::save_job( $job );
			return self::openai_resubmit_missing( $job );
		}

		// Finished this OpenAI file — clean local artifacts and continue collecting.
		self::cleanup_openai_artifacts( $job, false );
		$job['openai'] = self::default_openai_state();
		self::save_job( $job );

		$probe = $job;
		$next  = self::resolve_target( $probe );
		if ( null === $next ) {
			$job['status']      = 'done';
			$job['finished_at'] = time();
			self::save_job( $job );
			self::clear_scheduled();
			return array(
				'ok'       => true,
				'continue' => false,
				'status'   => 'done',
				'job'      => self::status_payload(),
			);
		}

		self::schedule_next_batch( 2 );
		return array(
			'ok'       => true,
			'continue' => true,
			'imported' => $imported,
			'status'   => 'running',
			'job'      => self::status_payload(),
		);
	}

	/**
	 * Load custom_id → passage map from map.jsonl.
	 *
	 * @param string $map_path Path.
	 * @return array<string, array<string, mixed>>
	 */
	private static function openai_load_map( $map_path ) {
		$out = array();
		if ( '' === $map_path || ! is_readable( $map_path ) ) {
			return $out;
		}
		$handle = fopen( $map_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return $out;
		}
		while ( ( $line = fgets( $handle ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
			$row = json_decode( trim( (string) $line ), true );
			if ( is_array( $row ) && ! empty( $row['id'] ) ) {
				$out[ (string) $row['id'] ] = $row;
			}
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $out;
	}

	/**
	 * Remove local batch files; optionally cancel an in-flight OpenAI batch.
	 *
	 * @param array<string, mixed> $job    Job.
	 * @param bool                 $cancel Whether to cancel OpenAI batch.
	 */
	private static function cleanup_openai_artifacts( array $job, $cancel = false ) {
		$openai = is_array( $job['openai'] ?? null ) ? $job['openai'] : array();
		if ( $cancel && ! empty( $openai['batch_id'] ) && in_array( (string) ( $openai['phase'] ?? '' ), array( 'submitted', 'collecting' ), true ) ) {
			if ( class_exists( 'THW_Premium_AI_Client' ) ) {
				THW_Premium_AI_Client::openai_cancel_batch( (string) $openai['batch_id'] );
			}
		}
		foreach ( array( 'local_input', 'local_map', 'local_output' ) as $key ) {
			$path = (string) ( $openai[ $key ] ?? '' );
			if ( $path && file_exists( $path ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $path );
			}
		}
	}
}
