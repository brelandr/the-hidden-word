<?php
/**
 * Local Bibles admin page (download public-domain Bibles into SQL).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Local_Bibles_Admin
 */
class HWBL_Local_Bibles_Admin {

	const PAGE_SLUG = 'hwbl-local-bibles';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_hwbl_local_bible_install', array( $this, 'ajax_install' ) );
		add_action( 'wp_ajax_hwbl_local_bible_process', array( $this, 'ajax_process' ) );
		add_action( 'wp_ajax_hwbl_local_bible_status', array( $this, 'ajax_status' ) );
		add_action( 'wp_ajax_hwbl_local_bible_remove', array( $this, 'ajax_remove' ) );
	}

	/**
	 * Add Local Bibles under Bible Lessons.
	 */
	public function add_submenu() {
		add_submenu_page(
			'edit.php?post_type=hwbl_lesson',
			__( 'Local Bibles', 'hidden-word-bible-lessons' ),
			__( 'Local Bibles', 'hidden-word-bible-lessons' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin JS/CSS on this page.
	 *
	 * @param string $hook Current admin hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'hwbl_lesson_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		HWBL_Local_Bible_Store::maybe_install_schema();

		wp_enqueue_style(
			'hwbl-admin',
			HWBL_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			HWBL_VERSION
		);

		wp_enqueue_style(
			'hwbl-local-bibles',
			HWBL_PLUGIN_URL . 'admin/css/local-bibles.css',
			array( 'hwbl-admin' ),
			HWBL_VERSION
		);

		wp_enqueue_script(
			'hwbl-local-bibles',
			HWBL_PLUGIN_URL . 'admin/js/local-bibles.js',
			array( 'jquery' ),
			HWBL_VERSION,
			true
		);

		wp_localize_script(
			'hwbl-local-bibles',
			'hwblLocalBibles',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'hwbl_local_bibles' ),
				'languages' => HWBL_Local_Bible_Store::get_languages(),
				'i18n'      => array(
					'confirmRemove' => __( 'Remove this local Bible and all of its verses from the database?', 'hidden-word-bible-lessons' ),
					'installing'    => __( 'Downloading / importing…', 'hidden-word-bible-lessons' ),
					'ready'         => __( 'Ready', 'hidden-word-bible-lessons' ),
					'error'         => __( 'Error', 'hidden-word-bible-lessons' ),
					'notInstalled'  => __( 'Not installed', 'hidden-word-bible-lessons' ),
					'selectOne'     => __( 'Select at least one translation to download.', 'hidden-word-bible-lessons' ),
					'allLanguages'  => __( 'All languages', 'hidden-word-bible-lessons' ),
					'noMatches'     => __( 'No Bibles match this language filter.', 'hidden-word-bible-lessons' ),
				),
				'rows'      => $this->status_rows(),
			)
		);
	}

	/**
	 * Status rows for each catalog translation.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function status_rows() {
		$rows = array();
		foreach ( HWBL_Local_Bible_Store::get_catalog() as $slug => $meta ) {
			$progress = HWBL_Local_Bible_Importer::progress_payload( $slug );
			$row      = HWBL_Local_Bible_Store::get_translation( $slug );
			$rows[]   = array_merge(
				$meta,
				$progress,
				array(
					'installed_at' => is_array( $row ) ? (string) ( $row['installed_at'] ?? '' ) : '',
				)
			);
		}
		return $rows;
	}

	/**
	 * Render admin page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		HWBL_Local_Bible_Store::maybe_install_schema();
		$rows      = $this->status_rows();
		$languages = HWBL_Local_Bible_Store::get_languages();
		?>
		<div class="wrap hwbl-local-bibles">
			<h1><?php echo esc_html__( 'Local Bibles', 'hidden-word-bible-lessons' ); ?></h1>
			<p class="description">
				<?php echo esc_html__( 'Download free public-domain Bibles (many languages) into this site’s database. Installed translations are served locally by the Bible reader and search — no per-chapter outbound API calls.', 'hidden-word-bible-lessons' ); ?>
			</p>

			<div class="notice notice-warning inline hwbl-local-bibles-warning">
				<p>
					<?php echo esc_html__( 'Each Bible uses roughly 4–20 MB of database space (text + indexes). Download only what you need, and avoid starting large imports on low-resource hosts during peak traffic.', 'hidden-word-bible-lessons' ); ?>
				</p>
			</div>

			<p class="description">
				<?php echo esc_html__( 'Licensed translations (NIV, ESV, NLT, etc.) are not available here — use your API keys under Settings / Premium Bible APIs.', 'hidden-word-bible-lessons' ); ?>
			</p>

			<form id="hwbl-local-bibles-form" onsubmit="return false;">
				<p class="hwbl-local-language-filter">
					<label for="hwbl-local-language">
						<?php echo esc_html__( 'Language', 'hidden-word-bible-lessons' ); ?>
					</label>
					<select id="hwbl-local-language" name="language">
						<option value=""><?php echo esc_html__( 'All languages', 'hidden-word-bible-lessons' ); ?></option>
						<?php foreach ( $languages as $code => $label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, 'en' ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="hwbl-local-filter-empty description" hidden>
					<?php echo esc_html__( 'No Bibles match this language filter.', 'hidden-word-bible-lessons' ); ?>
				</p>
				<table class="widefat striped hwbl-local-bibles-table">
					<thead>
						<tr>
							<td class="check-column"><span class="screen-reader-text"><?php echo esc_html__( 'Select', 'hidden-word-bible-lessons' ); ?></span></td>
							<th><?php echo esc_html__( 'Translation', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php echo esc_html__( 'Language', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php echo esc_html__( 'License', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php echo esc_html__( 'Status', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php echo esc_html__( 'Verses', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php echo esc_html__( 'Actions', 'hidden-word-bible-lessons' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<?php
							$slug       = $row['slug'];
							$status     = (string) ( $row['status'] ?? 'not_installed' );
							$busy       = in_array( $status, array( 'downloading', 'importing' ), true );
							$installed  = ! empty( $row['installed'] );
							$status_lbl = $this->status_label( $status );
							$lang_code  = sanitize_key( (string) ( $row['language'] ?? 'en' ) );
							$lang_label = (string) ( $row['language_label'] ?? $lang_code );
							?>
							<tr data-slug="<?php echo esc_attr( $slug ); ?>" data-language="<?php echo esc_attr( $lang_code ); ?>">
								<th scope="row" class="check-column">
									<input type="checkbox" name="slugs[]" value="<?php echo esc_attr( $slug ); ?>" <?php disabled( $busy || $installed ); ?> />
								</th>
								<td>
									<strong><?php echo esc_html( $row['label'] ); ?></strong>
									<p class="description"><?php echo esc_html( $row['note'] ); ?></p>
								</td>
								<td class="hwbl-local-language"><?php echo esc_html( $lang_label ); ?></td>
								<td><?php echo esc_html( $row['license'] ); ?></td>
								<td class="hwbl-local-status">
									<span class="hwbl-local-status-label"><?php echo esc_html( $status_lbl ); ?></span>
									<div class="hwbl-local-progress" <?php echo $busy ? '' : 'hidden'; ?>>
										<progress max="100" value="<?php echo esc_attr( (string) (int) ( $row['percent'] ?? 0 ) ); ?>"></progress>
										<span class="hwbl-local-percent"><?php echo esc_html( (string) (int) ( $row['percent'] ?? 0 ) ); ?>%</span>
									</div>
									<?php if ( ! empty( $row['error'] ) ) : ?>
										<p class="hwbl-local-error"><?php echo esc_html( (string) $row['error'] ); ?></p>
									<?php endif; ?>
								</td>
								<td class="hwbl-local-verse-count"><?php echo esc_html( (string) (int) ( $row['verse_count'] ?? 0 ) ); ?></td>
								<td class="hwbl-local-actions">
									<button type="button" class="button button-primary hwbl-local-install-one" data-slug="<?php echo esc_attr( $slug ); ?>" <?php disabled( $busy || $installed ); ?>>
										<?php echo esc_html__( 'Download & install', 'hidden-word-bible-lessons' ); ?>
									</button>
									<button type="button" class="button hwbl-local-remove" data-slug="<?php echo esc_attr( $slug ); ?>" <?php disabled( ! $installed && ! $busy && 'error' !== $status ); ?>>
										<?php echo esc_html__( 'Remove', 'hidden-word-bible-lessons' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit">
					<button type="button" class="button button-primary" id="hwbl-local-install-selected">
						<?php echo esc_html__( 'Download & install selected', 'hidden-word-bible-lessons' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Human status label.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	private function status_label( $status ) {
		$map = array(
			'not_installed' => __( 'Not installed', 'hidden-word-bible-lessons' ),
			'downloading'   => __( 'Downloading', 'hidden-word-bible-lessons' ),
			'importing'     => __( 'Importing', 'hidden-word-bible-lessons' ),
			'ready'         => __( 'Ready', 'hidden-word-bible-lessons' ),
			'error'         => __( 'Error', 'hidden-word-bible-lessons' ),
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : $status;
	}

	/**
	 * Capability + nonce guard.
	 *
	 * @return bool
	 */
	private function verify_ajax() {
		return current_user_can( 'manage_options' )
			&& check_ajax_referer( 'hwbl_local_bibles', 'nonce', false );
	}

	/**
	 * AJAX: queue install.
	 */
	public function ajax_install() {
		if ( ! $this->verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$slugs = isset( $_POST['slugs'] ) ? (array) wp_unslash( $_POST['slugs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$slugs = array_values( array_filter( array_map( 'sanitize_key', $slugs ) ) );
		if ( empty( $slugs ) ) {
			wp_send_json_error( array( 'message' => 'empty' ), 400 );
		}

		$result = HWBL_Local_Bible_Importer::queue_install( $slugs );
		// Kick the first batch immediately for responsive UI.
		$batch = HWBL_Local_Bible_Importer::process_next_batch( $slugs[0] );

		wp_send_json_success(
			array(
				'queued' => $result['queued'],
				'errors' => $result['errors'],
				'batch'  => $batch,
				'rows'   => $this->status_rows(),
			)
		);
	}

	/**
	 * AJAX: process next batch.
	 */
	public function ajax_process() {
		if ( ! $this->verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$batch = HWBL_Local_Bible_Importer::process_next_batch( $slug );

		wp_send_json_success(
			array(
				'batch' => $batch,
				'rows'  => $this->status_rows(),
			)
		);
	}

	/**
	 * AJAX: refresh status rows.
	 */
	public function ajax_status() {
		if ( ! $this->verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		wp_send_json_success( array( 'rows' => $this->status_rows() ) );
	}

	/**
	 * AJAX: remove translation.
	 */
	public function ajax_remove() {
		if ( ! $this->verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $slug ) {
			wp_send_json_error( array( 'message' => 'empty' ), 400 );
		}

		HWBL_Local_Bible_Importer::remove( $slug );

		wp_send_json_success( array( 'rows' => $this->status_rows() ) );
	}
}
