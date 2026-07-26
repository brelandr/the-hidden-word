<?php
/**
 * Admin UI for preloading Bible reader AI explanations.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Explain_Preload_Admin
 */
class THW_Premium_Explain_Preload_Admin {

	const PAGE_SLUG = 'thw-explain-preload';

	/**
	 * Wire admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu' ), 25 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_thw_explain_preload_start', array( __CLASS__, 'ajax_start' ) );
		add_action( 'wp_ajax_thw_explain_preload_pause', array( __CLASS__, 'ajax_pause' ) );
		add_action( 'wp_ajax_thw_explain_preload_resume', array( __CLASS__, 'ajax_resume' ) );
		add_action( 'wp_ajax_thw_explain_preload_process', array( __CLASS__, 'ajax_process' ) );
		add_action( 'wp_ajax_thw_explain_preload_status', array( __CLASS__, 'ajax_status' ) );
		add_action( 'wp_ajax_thw_explain_preload_clear', array( __CLASS__, 'ajax_clear' ) );
	}

	/**
	 * Add submenu under Bible Lessons.
	 */
	public static function add_submenu() {
		add_submenu_page(
			'edit.php?post_type=hwbl_lesson',
			__( 'Preload Explains', 'hidden-word-bible-lessons' ),
			__( 'Preload Explains', 'hidden-word-bible-lessons' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue assets on this page.
	 *
	 * @param string $hook Admin hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'hwbl_lesson_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'thw-explain-preload',
			THW_PREMIUM_URL . 'admin/css/explain-preload.css',
			array(),
			THW_PREMIUM_VERSION
		);

		wp_enqueue_script(
			'thw-explain-preload',
			THW_PREMIUM_URL . 'admin/js/explain-preload.js',
			array( 'jquery' ),
			THW_PREMIUM_VERSION,
			true
		);

		wp_localize_script(
			'thw-explain-preload',
			'thwExplainPreload',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'thw_explain_preload' ),
				'job'     => THW_Premium_Explain_Preload::status_payload(),
				'i18n'    => array(
					'selectBible'     => __( 'Select at least one installed local Bible.', 'hidden-word-bible-lessons' ),
					'selectTradition' => __( 'Select at least one tradition.', 'hidden-word-bible-lessons' ),
					'confirmClear'    => __( 'Clear the current preload job status? Already-saved explanations are kept. An in-flight OpenAI batch will be cancelled.', 'hidden-word-bible-lessons' ),
					'idle'            => __( 'Idle', 'hidden-word-bible-lessons' ),
					'running'         => __( 'Running', 'hidden-word-bible-lessons' ),
					'paused'          => __( 'Paused', 'hidden-word-bible-lessons' ),
					'done'            => __( 'Done', 'hidden-word-bible-lessons' ),
					'error'           => __( 'Error', 'hidden-word-bible-lessons' ),
					'realtime'        => __( 'Realtime API', 'hidden-word-bible-lessons' ),
					'openai_batch'    => __( 'OpenAI Batch API', 'hidden-word-bible-lessons' ),
				),
			)
		);
	}

	/**
	 * Installed local Bible options for the picker.
	 *
	 * @return array<int, array{slug:string,label:string,verse_count:int}>
	 */
	private static function installed_bibles() {
		$out = array();
		if ( ! class_exists( 'HWBL_Local_Bible_Store' ) ) {
			return $out;
		}

		HWBL_Local_Bible_Store::maybe_install_schema();
		$catalog = HWBL_Local_Bible_Store::get_catalog();
		foreach ( HWBL_Local_Bible_Store::list_translations() as $row ) {
			$slug = sanitize_key( (string) ( $row['slug'] ?? '' ) );
			if ( '' === $slug || 'ready' !== ( $row['status'] ?? '' ) || (int) ( $row['verse_count'] ?? 0 ) < 1 ) {
				continue;
			}
			$label = (string) ( $row['label'] ?? '' );
			if ( '' === $label && isset( $catalog[ $slug ]['label'] ) ) {
				$label = (string) $catalog[ $slug ]['label'];
			}
			$out[] = array(
				'slug'        => $slug,
				'label'       => $label ? $label : strtoupper( $slug ),
				'verse_count' => (int) ( $row['verse_count'] ?? 0 ),
			);
		}

		return $out;
	}

	/**
	 * Render admin page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$bibles     = self::installed_bibles();
		$traditions = function_exists( 'thw_premium_get_tradition_preset_choices' )
			? thw_premium_get_tradition_preset_choices()
			: array();
		$job        = THW_Premium_Explain_Preload::status_payload();
		$ai_ok      = class_exists( 'THW_Premium_Bible_Reader_Explain' ) && THW_Premium_Bible_Reader_Explain::is_available();
		$has_openai = class_exists( 'THW_Premium_AI_Client' ) && '' !== THW_Premium_AI_Client::resolve_openai_api_key();
		$mode       = (string) ( $job['mode'] ?? 'realtime' );
		if ( $has_openai && ( empty( $job['translations'] ) || 'openai_batch' === $mode ) ) {
			$selected_mode = 'openai_batch';
		} else {
			$selected_mode = 'realtime';
		}
		?>
		<div class="wrap thw-explain-preload">
			<h1><?php echo esc_html__( 'Preload Explains', 'hidden-word-bible-lessons' ); ?></h1>
			<p class="description">
				<?php echo esc_html__( 'Generate and save Bible reader AI explanations in the background for selected local Bibles and tradition presets. Visitors then get instant cached explains.', 'hidden-word-bible-lessons' ); ?>
			</p>

			<div class="notice notice-warning inline thw-explain-preload-warning">
				<p>
					<?php echo esc_html__( 'This uses your AI provider and can take a long time and cost money at full-Bible scale. Prefer OpenAI Batch API for whole-Bible jobs (about 50% off, results within 24 hours). Already-saved explains are skipped, so you can pause a realtime job and resume in Batch mode from the same cursor.', 'hidden-word-bible-lessons' ); ?>
				</p>
			</div>

			<?php if ( ! $ai_ok ) : ?>
				<div class="notice notice-error inline">
					<p><?php echo esc_html__( 'AI explanation is not configured or enabled. Configure AI under Premium settings before starting a preload.', 'hidden-word-bible-lessons' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( empty( $bibles ) ) : ?>
				<div class="notice notice-info inline">
					<p>
						<?php
						echo esc_html__( 'No local Bibles are installed yet.', 'hidden-word-bible-lessons' );
						echo ' ';
						echo esc_html__( 'Install free Bibles under Bible Lessons → Local Bibles first.', 'hidden-word-bible-lessons' );
						?>
					</p>
				</div>
			<?php endif; ?>

			<form id="thw-explain-preload-form" onsubmit="return false;">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Local Bibles', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<?php if ( empty( $bibles ) ) : ?>
								<p class="description"><?php echo esc_html__( 'None installed.', 'hidden-word-bible-lessons' ); ?></p>
							<?php else : ?>
								<fieldset class="thw-explain-preload-checks">
									<?php foreach ( $bibles as $bible ) : ?>
										<label>
											<input type="checkbox" name="translations[]" value="<?php echo esc_attr( $bible['slug'] ); ?>" />
											<?php echo esc_html( $bible['label'] ); ?>
											<span class="description">(<?php echo esc_html( number_format_i18n( $bible['verse_count'] ) ); ?> <?php echo esc_html__( 'verses', 'hidden-word-bible-lessons' ); ?>)</span>
										</label>
									<?php endforeach; ?>
								</fieldset>
								<p class="description"><?php echo esc_html__( 'Recommended starter: BSB or KJV.', 'hidden-word-bible-lessons' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Traditions', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<fieldset class="thw-explain-preload-checks">
								<?php
								$base_slug = function_exists( 'thw_premium_explain_base_tradition_slug' )
									? thw_premium_explain_base_tradition_slug()
									: 'base';
								?>
								<label>
									<input type="checkbox" name="traditions[]" value="<?php echo esc_attr( $base_slug ); ?>" checked />
									<?php echo esc_html__( 'Shared base explanation (all readers)', 'hidden-word-bible-lessons' ); ?>
								</label>
								<?php foreach ( $traditions as $slug => $label ) : ?>
									<?php
									if ( function_exists( 'thw_premium_explain_tradition_uses_shared_base' ) && thw_premium_explain_tradition_uses_shared_base( $slug ) ) {
										continue;
									}
									?>
									<label>
										<input type="checkbox" name="traditions[]" value="<?php echo esc_attr( $slug ); ?>" />
										<?php echo esc_html( $label ); ?>
										<span class="description"><?php echo esc_html__( '(overrides only when different)', 'hidden-word-bible-lessons' ); ?></span>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description">
								<?php echo esc_html__( 'Readers always see the shared base explain. Selected traditions only store a separate answer when the AI finds a substantive doctrinal difference for that verse/chapter.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Scope', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<fieldset class="thw-explain-preload-checks thw-explain-preload-scopes">
								<label><input type="checkbox" name="scopes[]" value="verse" checked /> <?php echo esc_html__( 'Verses', 'hidden-word-bible-lessons' ); ?></label>
								<label><input type="checkbox" name="scopes[]" value="chapter" /> <?php echo esc_html__( 'Chapters', 'hidden-word-bible-lessons' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'API mode', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<fieldset class="thw-explain-preload-checks">
								<label>
									<input type="radio" name="mode" value="openai_batch" <?php checked( $selected_mode, 'openai_batch' ); ?> <?php disabled( ! $has_openai ); ?> />
									<?php echo esc_html__( 'OpenAI Batch API (≈50% off, up to 24h)', 'hidden-word-bible-lessons' ); ?>
								</label>
								<label>
									<input type="radio" name="mode" value="realtime" <?php checked( $selected_mode, 'realtime' ); ?> />
									<?php echo esc_html__( 'Realtime API (immediate, full price)', 'hidden-word-bible-lessons' ); ?>
								</label>
							</fieldset>
							<?php if ( ! $has_openai ) : ?>
								<p class="description"><?php echo esc_html__( 'Add an OpenAI API key to enable Batch mode.', 'hidden-word-bible-lessons' ); ?></p>
							<?php else : ?>
								<p class="description"><?php echo esc_html__( 'Batch mode builds request files, submits them to OpenAI, then imports results. If a batch expires or fails, unfinished explains are resubmitted automatically (up to 8 retries per file) until the job finishes. Compliance re-check is skipped on import to keep cost down. Pause a running realtime job, choose Batch, then Resume — progress (cursor + saved explains) is kept.', 'hidden-word-bible-lessons' ); ?></p>
							<?php endif; ?>
							<?php if ( in_array( (string) ( $job['status'] ?? '' ), array( 'paused', 'error' ), true ) ) : ?>
								<p>
									<label>
										<input type="checkbox" name="continue_job" value="1" checked />
										<?php echo esc_html__( 'Continue paused job from current cursor (same Bible / tradition / scope)', 'hidden-word-bible-lessons' ); ?>
									</label>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<p class="submit thw-explain-preload-actions">
					<button type="button" class="button button-primary" id="thw-explain-preload-start" <?php disabled( empty( $bibles ) || ! $ai_ok ); ?>>
						<?php echo esc_html__( 'Start preload', 'hidden-word-bible-lessons' ); ?>
					</button>
					<button type="button" class="button" id="thw-explain-preload-pause">
						<?php echo esc_html__( 'Pause', 'hidden-word-bible-lessons' ); ?>
					</button>
					<button type="button" class="button" id="thw-explain-preload-resume">
						<?php echo esc_html__( 'Resume', 'hidden-word-bible-lessons' ); ?>
					</button>
					<button type="button" class="button" id="thw-explain-preload-clear">
						<?php echo esc_html__( 'Clear job', 'hidden-word-bible-lessons' ); ?>
					</button>
				</p>
			</form>

			<div class="thw-explain-preload-status" id="thw-explain-preload-status" data-status="<?php echo esc_attr( (string) $job['status'] ); ?>">
				<h2><?php echo esc_html__( 'Progress', 'hidden-word-bible-lessons' ); ?></h2>
				<p>
					<strong class="thw-preload-status-label"><?php echo esc_html( self::status_label( (string) $job['status'] ) ); ?></strong>
					— <span class="thw-preload-counts"><?php echo esc_html( self::counts_label( $job ) ); ?></span>
				</p>
				<div class="thw-preload-progress">
					<progress max="100" value="<?php echo esc_attr( (string) (int) ( $job['percent'] ?? 0 ) ); ?>"></progress>
					<span class="thw-preload-percent"><?php echo esc_html( (string) (int) ( $job['percent'] ?? 0 ) ); ?>%</span>
				</div>
				<p class="description thw-preload-last">
					<?php echo esc_html__( 'Last:', 'hidden-word-bible-lessons' ); ?>
					<span class="thw-preload-last-ref"><?php echo esc_html( (string) ( $job['last_reference'] ?? '—' ) ); ?></span>
				</p>
				<p class="thw-preload-error" <?php echo empty( $job['last_error'] ) ? 'hidden' : ''; ?>>
					<?php echo esc_html( (string) ( $job['last_error'] ?? '' ) ); ?>
				</p>
				<p class="description thw-preload-config">
					<span class="thw-preload-config-text"><?php echo esc_html( self::config_label( $job ) ); ?></span>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Human status label.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private static function status_label( $status ) {
		$map = array(
			'idle'    => __( 'Idle', 'hidden-word-bible-lessons' ),
			'running' => __( 'Running', 'hidden-word-bible-lessons' ),
			'paused'  => __( 'Paused', 'hidden-word-bible-lessons' ),
			'done'    => __( 'Done', 'hidden-word-bible-lessons' ),
			'error'   => __( 'Error', 'hidden-word-bible-lessons' ),
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : $status;
	}

	/**
	 * Counts summary.
	 *
	 * @param array<string, mixed> $job Job payload.
	 * @return string
	 */
	private static function counts_label( array $job ) {
		$stats = is_array( $job['stats'] ?? null ) ? $job['stats'] : array();
		return sprintf(
			/* translators: 1: processed, 2: total estimate, 3: generated, 4: skipped, 5: errors */
			__( '%1$s / %2$s processed · %3$s generated · %4$s skipped · %5$s errors', 'hidden-word-bible-lessons' ),
			number_format_i18n( (int) ( $stats['processed'] ?? 0 ) ),
			number_format_i18n( (int) ( $stats['total'] ?? 0 ) ),
			number_format_i18n( (int) ( $stats['generated'] ?? 0 ) ),
			number_format_i18n( (int) ( $stats['skipped'] ?? 0 ) ),
			number_format_i18n( (int) ( $stats['errors'] ?? 0 ) )
		);
	}

	/**
	 * Config summary.
	 *
	 * @param array<string, mixed> $job Job payload.
	 * @return string
	 */
	private static function config_label( array $job ) {
		$t = array_values( (array) ( $job['translations'] ?? array() ) );
		$d = array_values( (array) ( $job['traditions'] ?? array() ) );
		$s = array_values( (array) ( $job['scopes'] ?? array() ) );
		if ( empty( $t ) ) {
			return __( 'No active job configuration.', 'hidden-word-bible-lessons' );
		}
		$mode_label = ( 'openai_batch' === ( $job['mode'] ?? '' ) )
			? __( 'OpenAI Batch API', 'hidden-word-bible-lessons' )
			: __( 'Realtime API', 'hidden-word-bible-lessons' );
		$extra      = '';
		$phase      = (string) ( $job['openai']['phase'] ?? '' );
		if ( $phase ) {
			$extra = ' · ' . sprintf(
				/* translators: %s: batch phase */
				__( 'Batch phase: %s', 'hidden-word-bible-lessons' ),
				$phase
			);
			if ( ! empty( $job['openai']['batch_status'] ) ) {
				$extra .= ' (' . (string) $job['openai']['batch_status'] . ')';
			}
		}
		return sprintf(
			/* translators: 1: bible slugs, 2: tradition slugs, 3: scopes, 4: api mode, 5: optional batch phase */
			__( 'Bibles: %1$s · Traditions: %2$s · Scope: %3$s · %4$s%5$s', 'hidden-word-bible-lessons' ),
			implode( ', ', $t ),
			implode( ', ', $d ),
			implode( ', ', $s ),
			$mode_label,
			$extra
		);
	}

	/**
	 * Capability + nonce.
	 *
	 * @return bool
	 */
	private static function verify_ajax() {
		return current_user_can( 'manage_options' )
			&& check_ajax_referer( 'thw_explain_preload', 'nonce', false );
	}

	/**
	 * AJAX: start.
	 */
	public static function ajax_start() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$translations = isset( $_POST['translations'] ) ? (array) wp_unslash( $_POST['translations'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$traditions   = isset( $_POST['traditions'] ) ? (array) wp_unslash( $_POST['traditions'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$scopes       = isset( $_POST['scopes'] ) ? (array) wp_unslash( $_POST['scopes'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$mode         = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'realtime'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$continue     = ! empty( $_POST['continue_job'] );

		$result = THW_Premium_Explain_Preload::start(
			array(
				'translations' => $translations,
				'traditions'   => $traditions,
				'scopes'       => $scopes,
				'mode'         => $mode,
				'continue'     => $continue,
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		$batch = THW_Premium_Explain_Preload::process_batch();

		wp_send_json_success(
			array(
				'job'   => THW_Premium_Explain_Preload::status_payload(),
				'batch' => $batch,
			)
		);
	}

	/**
	 * AJAX: pause.
	 */
	public static function ajax_pause() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		THW_Premium_Explain_Preload::pause();
		wp_send_json_success( array( 'job' => THW_Premium_Explain_Preload::status_payload() ) );
	}

	/**
	 * AJAX: resume.
	 */
	public static function ajax_resume() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$result = THW_Premium_Explain_Preload::resume(
			$mode ? array( 'mode' => $mode ) : array()
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		$batch = THW_Premium_Explain_Preload::process_batch();
		wp_send_json_success(
			array(
				'job'   => THW_Premium_Explain_Preload::status_payload(),
				'batch' => $batch,
			)
		);
	}

	/**
	 * AJAX: process batch.
	 */
	public static function ajax_process() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$batch = THW_Premium_Explain_Preload::process_batch();
		wp_send_json_success(
			array(
				'job'   => THW_Premium_Explain_Preload::status_payload(),
				'batch' => $batch,
			)
		);
	}

	/**
	 * AJAX: status.
	 */
	public static function ajax_status() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		wp_send_json_success( array( 'job' => THW_Premium_Explain_Preload::status_payload() ) );
	}

	/**
	 * AJAX: clear.
	 */
	public static function ajax_clear() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		THW_Premium_Explain_Preload::clear_job();
		wp_send_json_success( array( 'job' => THW_Premium_Explain_Preload::status_payload() ) );
	}
}
