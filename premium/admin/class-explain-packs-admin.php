<?php
/**
 * Admin UI for explain pack export / install / remove.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Explain_Packs_Admin
 */
class THW_Premium_Explain_Packs_Admin {

	const PAGE_SLUG = 'thw-explain-packs';

	/**
	 * Wire hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu' ), 26 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		add_action( 'wp_ajax_thw_explain_pack_export_start', array( __CLASS__, 'ajax_export_start' ) );
		add_action( 'wp_ajax_thw_explain_pack_export_process', array( __CLASS__, 'ajax_export_process' ) );
		add_action( 'wp_ajax_thw_explain_pack_export_clear', array( __CLASS__, 'ajax_export_clear' ) );
		add_action( 'wp_ajax_thw_explain_pack_import_start', array( __CLASS__, 'ajax_import_start' ) );
		add_action( 'wp_ajax_thw_explain_pack_import_process', array( __CLASS__, 'ajax_import_process' ) );
		add_action( 'wp_ajax_thw_explain_pack_import_clear', array( __CLASS__, 'ajax_import_clear' ) );
		add_action( 'wp_ajax_thw_explain_pack_remove', array( __CLASS__, 'ajax_remove' ) );
		add_action( 'wp_ajax_thw_explain_pack_refresh_catalog', array( __CLASS__, 'ajax_refresh_catalog' ) );
		add_action( 'wp_ajax_thw_explain_pack_save_token', array( __CLASS__, 'ajax_save_token' ) );
		add_action( 'wp_ajax_thw_explain_pack_publish', array( __CLASS__, 'ajax_publish' ) );
	}

	/**
	 * Settings for catalog URL.
	 */
	public static function register_settings() {
		register_setting(
			'thw_explain_packs',
			THW_Premium_Explain_Packs::CATALOG_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
	}

	/**
	 * Submenu.
	 */
	public static function add_submenu() {
		add_submenu_page(
			'edit.php?post_type=hwbl_lesson',
			__( 'Explain Packs', 'hidden-word-bible-lessons' ),
			__( 'Explain Packs', 'hidden-word-bible-lessons' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Assets.
	 *
	 * @param string $hook Hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'hwbl_lesson_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'thw-explain-packs',
			THW_PREMIUM_URL . 'admin/css/explain-packs.css',
			array(),
			THW_PREMIUM_VERSION
		);
		wp_enqueue_script(
			'thw-explain-packs',
			THW_PREMIUM_URL . 'admin/js/explain-packs.js',
			array( 'jquery' ),
			THW_PREMIUM_VERSION,
			true
		);

		$catalog  = THW_Premium_Explain_Packs::get_catalog();
		$is_hub   = class_exists( 'THW_Premium_Explain_Packs_GitHub' ) && THW_Premium_Explain_Packs_GitHub::is_hub_publisher();
		wp_localize_script(
			'thw-explain-packs',
			'thwExplainPacks',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'thw_explain_packs' ),
				'exportJob'   => THW_Premium_Explain_Packs::export_status(),
				'importJob'   => THW_Premium_Explain_Packs::import_status(),
				'installed'   => THW_Premium_Explain_Packs::get_installed(),
				'catalog'     => $catalog['packs'],
				'isHub'       => $is_hub,
				'hasToken'    => $is_hub && THW_Premium_Explain_Packs_GitHub::has_token(),
				'defaultCatalogUrl' => THW_Premium_Explain_Packs::default_catalog_url(),
				'i18n'        => array(
					'confirmRemove' => __( 'Delete local explanations for this Bible + tradition? This cannot be undone.', 'hidden-word-bible-lessons' ),
					'needKeys'      => __( 'Choose a translation and tradition.', 'hidden-word-bible-lessons' ),
					'needUrl'       => __( 'Enter a pack ZIP URL or choose a catalog pack.', 'hidden-word-bible-lessons' ),
					'tokenSaved'    => __( 'GitHub token saved on this hub only.', 'hidden-word-bible-lessons' ),
					'tokenCleared'  => __( 'GitHub token cleared.', 'hidden-word-bible-lessons' ),
					'publishOk'     => __( 'Published to GitHub Release and catalog updated.', 'hidden-word-bible-lessons' ),
					'publishNeed'   => __( 'Export a pack until status is ready, then publish.', 'hidden-word-bible-lessons' ),
				),
			)
		);
	}

	/**
	 * Translation slugs that already have explains (for export picker).
	 *
	 * @return array<int, string>
	 */
	private static function translations_with_explains() {
		if ( class_exists( 'THW_Premium_Bible_Reader_Explain_Store' ) ) {
			return THW_Premium_Bible_Reader_Explain_Store::list_translation_slugs();
		}
		return array();
	}

	/**
	 * Render page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$traditions  = function_exists( 'thw_premium_get_tradition_preset_choices' ) ? thw_premium_get_tradition_preset_choices() : array();
		$catalog     = THW_Premium_Explain_Packs::get_catalog();
		$installed   = THW_Premium_Explain_Packs::get_installed();
		$export      = THW_Premium_Explain_Packs::export_status();
		$import      = THW_Premium_Explain_Packs::import_status();
		$trans_slugs = self::translations_with_explains();
		$local       = array();
		if ( class_exists( 'HWBL_Local_Bible_Store' ) ) {
			foreach ( HWBL_Local_Bible_Store::list_translations() as $row ) {
				if ( 'ready' === ( $row['status'] ?? '' ) ) {
					$local[] = sanitize_key( (string) $row['slug'] );
				}
			}
		}
		$export_translations = array_values( array_unique( array_merge( $trans_slugs, $local ) ) );
		sort( $export_translations );
		$catalog_url = (string) get_option( THW_Premium_Explain_Packs::CATALOG_OPTION, '' );
		$default_url = THW_Premium_Explain_Packs::default_catalog_url();
		$is_hub      = class_exists( 'THW_Premium_Explain_Packs_GitHub' ) && THW_Premium_Explain_Packs_GitHub::is_hub_publisher();
		$has_token   = $is_hub && THW_Premium_Explain_Packs_GitHub::has_token();
		?>
		<div class="wrap thw-explain-packs">
			<h1><?php echo esc_html__( 'Explain Packs', 'hidden-word-bible-lessons' ); ?></h1>
			<p class="description">
				<?php echo esc_html__( 'Install shared explanation packs (no AI cost), or — on the hub — export and publish packs to GitHub for every church site.', 'hidden-word-bible-lessons' ); ?>
			</p>

			<div class="notice notice-info inline">
				<p>
					<?php
					echo esc_html__(
						'Default catalog: brelandr/hwbl-explain-packs (public downloads). Only thehiddenword.org can publish; churches never need a GitHub token.',
						'hidden-word-bible-lessons'
					);
					?>
				</p>
			</div>

			<?php if ( $is_hub ) : ?>
				<div class="thw-explain-packs-hub-panel" id="thw-explain-packs-hub-panel">
					<h2><?php echo esc_html__( 'Hub publish (thehiddenword.org only)', 'hidden-word-bible-lessons' ); ?></h2>
					<p class="description">
						<?php echo esc_html__( 'This panel is hidden on church sites. Store a fine-grained or classic GitHub PAT with access to brelandr/hwbl-explain-packs (Contents: Read/Write, Releases: Write). The token is encrypted in this site’s database and never sent to other installs.', 'hidden-word-bible-lessons' ); ?>
					</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="thw-explain-pack-github-token"><?php echo esc_html__( 'GitHub token', 'hidden-word-bible-lessons' ); ?></label></th>
							<td>
								<input type="password" class="regular-text" id="thw-explain-pack-github-token" autocomplete="new-password" placeholder="<?php echo $has_token ? esc_attr__( '•••• token saved — paste to replace', 'hidden-word-bible-lessons' ) : 'ghp_…'; ?>" />
								<button type="button" class="button button-primary" id="thw-explain-pack-save-token"><?php echo esc_html__( 'Save token', 'hidden-word-bible-lessons' ); ?></button>
								<button type="button" class="button" id="thw-explain-pack-clear-token"><?php echo esc_html__( 'Clear token', 'hidden-word-bible-lessons' ); ?></button>
								<p class="description">
									<span id="thw-explain-pack-token-status"><?php echo $has_token ? esc_html__( 'Token on file.', 'hidden-word-bible-lessons' ) : esc_html__( 'No token saved yet.', 'hidden-word-bible-lessons' ); ?></span>
									· <a href="https://github.com/brelandr/hwbl-explain-packs" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Open repo', 'hidden-word-bible-lessons' ); ?></a>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Publish', 'hidden-word-bible-lessons' ); ?></th>
							<td>
								<button type="button" class="button button-primary" id="thw-explain-pack-publish" <?php disabled( ! $has_token ); ?>>
									<?php echo esc_html__( 'Publish ready export to GitHub Release', 'hidden-word-bible-lessons' ); ?>
								</button>
								<p class="description"><?php echo esc_html__( 'Uses the current Export job when status is “ready”: uploads the ZIP, creates/updates the Release, and merges the pack into explain-packs-catalog.json.', 'hidden-word-bible-lessons' ); ?></p>
								<p class="thw-pack-publish-result" id="thw-pack-publish-result" hidden></p>
							</td>
						</tr>
					</table>
				</div>
				<hr />
			<?php endif; ?>

			<form method="post" action="options.php" class="thw-explain-packs-catalog-form">
				<?php settings_fields( 'thw_explain_packs' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="thw_explain_pack_catalog_url"><?php echo esc_html__( 'Remote catalog URL', 'hidden-word-bible-lessons' ); ?></label>
						</th>
						<td>
							<input type="url" class="regular-text" id="thw_explain_pack_catalog_url" name="<?php echo esc_attr( THW_Premium_Explain_Packs::CATALOG_OPTION ); ?>" value="<?php echo esc_attr( $catalog_url ); ?>" placeholder="<?php echo esc_attr( $default_url ); ?>" />
							<p class="description">
								<?php echo esc_html__( 'Leave blank to use the default:', 'hidden-word-bible-lessons' ); ?>
								<code><?php echo esc_html( $default_url ); ?></code>
							</p>
							<?php submit_button( __( 'Save catalog URL', 'hidden-word-bible-lessons' ), 'secondary', 'submit', false ); ?>
							<button type="button" class="button" id="thw-explain-pack-refresh-catalog"><?php echo esc_html__( 'Refresh catalog', 'hidden-word-bible-lessons' ); ?></button>
						</td>
					</tr>
				</table>
			</form>

			<hr />

			<h2><?php echo esc_html__( 'Install a pack', 'hidden-word-bible-lessons' ); ?></h2>
			<div id="thw-explain-pack-catalog" class="thw-explain-pack-catalog">
				<?php if ( empty( $catalog['packs'] ) ) : ?>
					<p class="description"><?php echo esc_html__( 'No catalog packs yet. Set a remote catalog URL, or install from a ZIP URL / upload below.', 'hidden-word-bible-lessons' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Pack', 'hidden-word-bible-lessons' ); ?></th>
								<th><?php echo esc_html__( 'Bible', 'hidden-word-bible-lessons' ); ?></th>
								<th><?php echo esc_html__( 'Tradition', 'hidden-word-bible-lessons' ); ?></th>
								<th><?php echo esc_html__( 'Version', 'hidden-word-bible-lessons' ); ?></th>
								<th><?php echo esc_html__( 'Actions', 'hidden-word-bible-lessons' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $catalog['packs'] as $pack ) : ?>
								<?php
								$pid     = sanitize_key( (string) ( $pack['id'] ?? '' ) );
								$is_inst = $pid && isset( $installed[ $pid ] );
								?>
								<tr data-pack-id="<?php echo esc_attr( $pid ); ?>">
									<td><strong><?php echo esc_html( (string) ( $pack['label'] ?? $pid ) ); ?></strong></td>
									<td><?php echo esc_html( (string) ( $pack['translation'] ?? '' ) ); ?></td>
									<td><?php echo esc_html( (string) ( $pack['tradition'] ?? '' ) ); ?></td>
									<td><?php echo esc_html( (string) ( $pack['version'] ?? '' ) ); ?></td>
									<td>
										<button type="button" class="button button-primary thw-explain-pack-install" data-url="<?php echo esc_url( (string) ( $pack['url'] ?? '' ) ); ?>" data-pack-id="<?php echo esc_attr( $pid ); ?>" <?php disabled( empty( $pack['url'] ) ); ?>>
											<?php echo $is_inst ? esc_html__( 'Re-install', 'hidden-word-bible-lessons' ) : esc_html__( 'Install', 'hidden-word-bible-lessons' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="thw-explain-pack-url"><?php echo esc_html__( 'Or ZIP URL', 'hidden-word-bible-lessons' ); ?></label></th>
					<td>
						<input type="url" class="large-text" id="thw-explain-pack-url" placeholder="https://github.com/org/repo/releases/download/v1.0.0/bsb-nondenom-verse-1.0.0.zip" />
						<button type="button" class="button button-primary" id="thw-explain-pack-import-url"><?php echo esc_html__( 'Install from URL', 'hidden-word-bible-lessons' ); ?></button>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thw-explain-pack-file"><?php echo esc_html__( 'Or upload ZIP', 'hidden-word-bible-lessons' ); ?></label></th>
					<td>
						<input type="file" id="thw-explain-pack-file" accept=".zip,application/zip" />
						<button type="button" class="button" id="thw-explain-pack-import-file"><?php echo esc_html__( 'Upload & install', 'hidden-word-bible-lessons' ); ?></button>
					</td>
				</tr>
			</table>

			<div class="thw-explain-pack-job" id="thw-explain-pack-import-status" data-status="<?php echo esc_attr( (string) ( $import['status'] ?? 'idle' ) ); ?>">
				<p>
					<strong><?php echo esc_html__( 'Import:', 'hidden-word-bible-lessons' ); ?></strong>
					<span class="thw-pack-import-label"><?php echo esc_html( (string) ( $import['status'] ?? 'idle' ) ); ?></span>
					— <span class="thw-pack-import-counts"></span>
				</p>
				<div class="thw-pack-progress">
					<progress max="100" value="<?php echo esc_attr( (string) (int) ( $import['percent'] ?? 0 ) ); ?>"></progress>
					<span class="thw-pack-import-percent"><?php echo esc_html( (string) (int) ( $import['percent'] ?? 0 ) ); ?>%</span>
				</div>
				<p class="thw-pack-import-error" hidden></p>
			</div>

			<hr />

			<h2><?php echo esc_html__( 'Export from this site (hub)', 'hidden-word-bible-lessons' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo esc_html__( 'Bible', 'hidden-word-bible-lessons' ); ?></th>
					<td>
						<select id="thw-explain-pack-export-translation">
							<option value=""><?php echo esc_html__( '— Select —', 'hidden-word-bible-lessons' ); ?></option>
							<?php foreach ( $export_translations as $slug ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( strtoupper( $slug ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Tradition', 'hidden-word-bible-lessons' ); ?></th>
					<td>
						<select id="thw-explain-pack-export-tradition">
							<option value=""><?php echo esc_html__( '— Select —', 'hidden-word-bible-lessons' ); ?></option>
							<?php
							$base_slug = function_exists( 'thw_premium_explain_base_tradition_slug' )
								? thw_premium_explain_base_tradition_slug()
								: 'base';
							?>
							<option value="<?php echo esc_attr( $base_slug ); ?>">
								<?php echo esc_html__( 'Shared base explanation', 'hidden-word-bible-lessons' ); ?>
							</option>
							<?php foreach ( $traditions as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html__( 'Export “Shared base” for the common explains. Tradition packs should contain only override rows (rare differences).', 'hidden-word-bible-lessons' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Scope', 'hidden-word-bible-lessons' ); ?></th>
					<td class="thw-explain-pack-scopes">
						<label><input type="checkbox" name="export_scopes[]" value="verse" checked /> <?php echo esc_html__( 'Verses', 'hidden-word-bible-lessons' ); ?></label>
						<label><input type="checkbox" name="export_scopes[]" value="chapter" /> <?php echo esc_html__( 'Chapters', 'hidden-word-bible-lessons' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Version label', 'hidden-word-bible-lessons' ); ?></th>
					<td><input type="text" id="thw-explain-pack-export-version" value="1.0.0" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'After export', 'hidden-word-bible-lessons' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="thw-explain-pack-remove-after" />
							<?php echo esc_html__( 'Remove these explanations from this site after the ZIP is ready', 'hidden-word-bible-lessons' ); ?>
						</label>
					</td>
				</tr>
			</table>
			<p>
				<button type="button" class="button button-primary" id="thw-explain-pack-export-start"><?php echo esc_html__( 'Export ZIP', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="button" id="thw-explain-pack-export-clear"><?php echo esc_html__( 'Clear export job', 'hidden-word-bible-lessons' ); ?></button>
			</p>

			<div class="thw-explain-pack-job" id="thw-explain-pack-export-status" data-status="<?php echo esc_attr( (string) ( $export['status'] ?? 'idle' ) ); ?>">
				<p>
					<strong><?php echo esc_html__( 'Export:', 'hidden-word-bible-lessons' ); ?></strong>
					<span class="thw-pack-export-label"><?php echo esc_html( (string) ( $export['status'] ?? 'idle' ) ); ?></span>
					— <span class="thw-pack-export-counts"></span>
				</p>
				<div class="thw-pack-progress">
					<progress max="100" value="<?php echo esc_attr( (string) (int) ( $export['percent'] ?? 0 ) ); ?>"></progress>
					<span class="thw-pack-export-percent"><?php echo esc_html( (string) (int) ( $export['percent'] ?? 0 ) ); ?>%</span>
				</div>
				<p class="thw-pack-export-download" <?php echo empty( $export['download_url'] ) ? 'hidden' : ''; ?>>
					<a class="button button-primary" id="thw-pack-download-link" href="<?php echo esc_url( (string) ( $export['download_url'] ?? '' ) ); ?>">
						<?php echo esc_html__( 'Download ZIP', 'hidden-word-bible-lessons' ); ?>
					</a>
					<span class="description"><?php echo esc_html__( 'Upload this file to a GitHub Release, then add its public URL to your catalog JSON.', 'hidden-word-bible-lessons' ); ?></span>
				</p>
				<label class="thw-pack-catalog-snippet-wrap" <?php echo empty( $export['download_url'] ) ? 'hidden' : ''; ?>>
					<?php echo esc_html__( 'Catalog entry template (paste your GitHub Release URL into "url"):', 'hidden-word-bible-lessons' ); ?>
					<textarea id="thw-pack-catalog-snippet" class="large-text code" rows="8" readonly></textarea>
				</label>
				<p class="thw-pack-export-error" hidden></p>
			</div>

			<hr />

			<h2><?php echo esc_html__( 'Installed / remove locally', 'hidden-word-bible-lessons' ); ?></h2>
			<?php if ( empty( $installed ) ) : ?>
				<p class="description"><?php echo esc_html__( 'No packs marked installed yet.', 'hidden-word-bible-lessons' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Pack', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php echo esc_html__( 'Bible', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php echo esc_html__( 'Tradition', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php echo esc_html__( 'Actions', 'hidden-word-bible-lessons' ); ?></th>
						</tr>
					</thead>
					<tbody id="thw-explain-pack-installed-body">
						<?php foreach ( $installed as $pid => $meta ) : ?>
							<tr data-pack-id="<?php echo esc_attr( $pid ); ?>">
								<td><?php echo esc_html( $pid ); ?></td>
								<td><?php echo esc_html( (string) ( $meta['translation'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $meta['tradition'] ?? '' ) ); ?></td>
								<td>
									<button type="button" class="button thw-explain-pack-remove" data-pack-id="<?php echo esc_attr( $pid ); ?>">
										<?php echo esc_html__( 'Remove from this site', 'hidden-word-bible-lessons' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo esc_html__( 'Remove by Bible + tradition', 'hidden-word-bible-lessons' ); ?></th>
					<td>
						<select id="thw-explain-pack-remove-translation">
							<option value=""><?php echo esc_html__( 'Bible', 'hidden-word-bible-lessons' ); ?></option>
							<?php foreach ( $export_translations as $slug ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( strtoupper( $slug ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<select id="thw-explain-pack-remove-tradition">
							<option value=""><?php echo esc_html__( 'Tradition', 'hidden-word-bible-lessons' ); ?></option>
							<?php
							$base_slug_remove = function_exists( 'thw_premium_explain_base_tradition_slug' )
								? thw_premium_explain_base_tradition_slug()
								: 'base';
							?>
							<option value="<?php echo esc_attr( $base_slug_remove ); ?>">
								<?php echo esc_html__( 'Shared base', 'hidden-word-bible-lessons' ); ?>
							</option>
							<?php foreach ( $traditions as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="button" id="thw-explain-pack-remove-keys"><?php echo esc_html__( 'Remove local explains', 'hidden-word-bible-lessons' ); ?></button>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * @return bool
	 */
	private static function verify_ajax() {
		return current_user_can( 'manage_options' )
			&& check_ajax_referer( 'thw_explain_packs', 'nonce', false );
	}

	/**
	 * AJAX export start.
	 */
	public static function ajax_export_start() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$scopes = isset( $_POST['scopes'] ) ? (array) wp_unslash( $_POST['scopes'] ) : array( 'verse' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$result = THW_Premium_Explain_Packs::start_export(
			array(
				'translation'  => isset( $_POST['translation'] ) ? sanitize_key( wp_unslash( $_POST['translation'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'tradition'    => isset( $_POST['tradition'] ) ? sanitize_key( wp_unslash( $_POST['tradition'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'scopes'       => $scopes,
				'version'      => isset( $_POST['version'] ) ? sanitize_text_field( wp_unslash( $_POST['version'] ) ) : '1.0.0', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'remove_after' => ! empty( $_POST['remove_after'] ),
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		$batch = THW_Premium_Explain_Packs::process_export_batch();
		$job   = THW_Premium_Explain_Packs::export_status();
		wp_send_json_success(
			array(
				'job'     => $job,
				'batch'   => $batch,
				'snippet' => THW_Premium_Explain_Packs::catalog_entry_from_export( $job, '' ),
			)
		);
	}

	/**
	 * AJAX export process.
	 */
	public static function ajax_export_process() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$job    = get_option( THW_Premium_Explain_Packs::EXPORT_OPTION, null );
		$status = is_array( $job ) ? (string) ( $job['status'] ?? '' ) : '';
		if ( 'removing' === $status ) {
			$batch = THW_Premium_Explain_Packs::process_export_remove_batch();
		} else {
			$batch = THW_Premium_Explain_Packs::process_export_batch();
		}
		$export = THW_Premium_Explain_Packs::export_status();
		wp_send_json_success(
			array(
				'job'     => $export,
				'batch'   => $batch,
				'snippet' => THW_Premium_Explain_Packs::catalog_entry_from_export( $export, '' ),
			)
		);
	}

	/**
	 * AJAX export clear.
	 */
	public static function ajax_export_clear() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		THW_Premium_Explain_Packs::clear_export( true );
		wp_send_json_success( array( 'job' => THW_Premium_Explain_Packs::export_status() ) );
	}

	/**
	 * AJAX import start (URL or uploaded file).
	 */
	public static function ajax_import_start() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$url     = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$pack_id = isset( $_POST['pack_id'] ) ? sanitize_key( wp_unslash( $_POST['pack_id'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path    = '';

		if ( ! empty( $_FILES['pack']['tmp_name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$upload = wp_handle_upload(
				$_FILES['pack'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				array(
					'test_form' => false,
					'mimes'     => array( 'zip' => 'application/zip' ),
				)
			);
			if ( isset( $upload['error'] ) ) {
				wp_send_json_error( array( 'message' => (string) $upload['error'] ), 400 );
			}
			$path = isset( $upload['file'] ) ? (string) $upload['file'] : '';
		}

		if ( ! $url && ! $path ) {
			wp_send_json_error( array( 'message' => __( 'Provide a ZIP URL or upload a pack file.', 'hidden-word-bible-lessons' ) ), 400 );
		}

		$result = THW_Premium_Explain_Packs::start_import(
			array(
				'url'       => $url,
				'file_path' => $path,
				'pack_id'   => $pack_id,
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		$batch = THW_Premium_Explain_Packs::process_import_batch();
		wp_send_json_success(
			array(
				'job'   => THW_Premium_Explain_Packs::import_status(),
				'batch' => $batch,
			)
		);
	}

	/**
	 * AJAX import process.
	 */
	public static function ajax_import_process() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$batch = THW_Premium_Explain_Packs::process_import_batch();
		wp_send_json_success(
			array(
				'job'   => THW_Premium_Explain_Packs::import_status(),
				'batch' => $batch,
			)
		);
	}

	/**
	 * AJAX import clear.
	 */
	public static function ajax_import_clear() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		THW_Premium_Explain_Packs::clear_import( true );
		wp_send_json_success( array( 'job' => THW_Premium_Explain_Packs::import_status() ) );
	}

	/**
	 * AJAX remove local pack / keys.
	 */
	public static function ajax_remove() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$pack_id = isset( $_POST['pack_id'] ) ? sanitize_key( wp_unslash( $_POST['pack_id'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$scopes  = isset( $_POST['scopes'] ) ? (array) wp_unslash( $_POST['scopes'] ) : array( 'verse', 'chapter' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$result = THW_Premium_Explain_Packs::remove_local_pack(
			$pack_id,
			array(
				'translation' => isset( $_POST['translation'] ) ? sanitize_key( wp_unslash( $_POST['translation'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'tradition'   => isset( $_POST['tradition'] ) ? sanitize_key( wp_unslash( $_POST['tradition'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'scopes'      => $scopes,
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'result'    => $result,
				'installed' => THW_Premium_Explain_Packs::get_installed(),
			)
		);
	}

	/**
	 * AJAX refresh catalog.
	 */
	public static function ajax_refresh_catalog() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$catalog = THW_Premium_Explain_Packs::get_catalog( true );
		wp_send_json_success( array( 'packs' => $catalog['packs'] ) );
	}

	/**
	 * AJAX: save or clear hub GitHub token.
	 */
	public static function ajax_save_token() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		if ( ! class_exists( 'THW_Premium_Explain_Packs_GitHub' ) || ! THW_Premium_Explain_Packs_GitHub::is_hub_publisher() ) {
			wp_send_json_error( array( 'message' => __( 'GitHub publish is only available on the Hidden Word hub.', 'hidden-word-bible-lessons' ) ), 403 );
		}

		$clear = ! empty( $_POST['clear'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$token = $clear ? '' : ( isset( $_POST['token'] ) ? (string) wp_unslash( $_POST['token'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$result = THW_Premium_Explain_Packs_GitHub::set_token( $token );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'hasToken' => THW_Premium_Explain_Packs_GitHub::has_token(),
				'cleared'  => $clear || '' === trim( $token ),
			)
		);
	}

	/**
	 * AJAX: publish ready export to GitHub.
	 */
	public static function ajax_publish() {
		if ( ! self::verify_ajax() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		if ( ! class_exists( 'THW_Premium_Explain_Packs_GitHub' ) || ! THW_Premium_Explain_Packs_GitHub::is_hub_publisher() ) {
			wp_send_json_error( array( 'message' => __( 'GitHub publish is only available on the Hidden Word hub.', 'hidden-word-bible-lessons' ) ), 403 );
		}

		$result = THW_Premium_Explain_Packs_GitHub::publish_ready_export();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( $result );
	}
}
