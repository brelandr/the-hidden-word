<?php
/**
 * Plugin settings page.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Settings
 */
class HWBL_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'hwbl_settings', 'hwbl_schedule_mode', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_schedule_mode' ),
			'default'           => 'week',
		) );

		register_setting( 'hwbl_settings', 'hwbl_active_translation', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_translation' ),
			'default'           => 'niv',
		) );

		register_setting( 'hwbl_settings', 'hwbl_ai_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		) );

		register_setting( 'hwbl_settings', 'hwbl_helloao_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		) );

		register_setting( 'hwbl_settings', 'hwbl_bible_reader_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		) );

		register_setting( 'hwbl_settings', 'hwbl_bible_reader_narrator', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_bible_reader_narrator' ),
			'default'           => 'david',
		) );

		register_setting( 'hwbl_settings', 'hwbl_google_oauth_audiences', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );
		register_setting( 'hwbl_settings', 'hwbl_companion_self_signup', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		) );

		register_setting( 'hwbl_settings', 'hwbl_bible_maps_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		) );

		register_setting( 'hwbl_settings', 'hwbl_bible_maps_provider', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_bible_maps_provider' ),
			'default'           => 'leaflet',
		) );

		register_setting( 'hwbl_settings', 'hwbl_bible_maps_mapbox_token', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( 'hwbl_settings', 'hwbl_bible_maps_mapbox_style_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_bible_maps_mapbox_style_key' ),
			'default'           => 'outdoors',
		) );

		register_setting( 'hwbl_settings', 'hwbl_bible_maps_mapbox_style', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_bible_maps_mapbox_style' ),
			'default'           => '',
		) );

		register_setting( 'hwbl_settings', 'hwbl_bible_concordance_enabled', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		) );
	}

	/**
	 * Sanitize map provider slug.
	 *
	 * @param string $value Input.
	 * @return string
	 */
	public function sanitize_bible_maps_provider( $value ) {
		$value = sanitize_key( (string) $value );
		return in_array( $value, array( 'leaflet', 'mapbox' ), true ) ? $value : 'leaflet';
	}

	/**
	 * Sanitize Mapbox style preset key.
	 *
	 * @param string $value Input.
	 * @return string
	 */
	public function sanitize_bible_maps_mapbox_style_key( $value ) {
		$value   = sanitize_key( (string) $value );
		$presets = class_exists( 'HWBL_Bible_Places' ) ? array_keys( HWBL_Bible_Places::get_mapbox_style_presets() ) : array( 'outdoors' );
		return in_array( $value, $presets, true ) ? $value : 'outdoors';
	}

	/**
	 * Sanitize custom Mapbox Studio style URL.
	 *
	 * @param string $value Input.
	 * @return string
	 */
	public function sanitize_bible_maps_mapbox_style( $value ) {
		if ( class_exists( 'HWBL_Bible_Places' ) ) {
			return HWBL_Bible_Places::sanitize_mapbox_style_url( $value );
		}
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize default Bible reader audio narrator.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_bible_reader_narrator( $value ) {
		$value = sanitize_key( (string) $value );
		$allowed = array( 'david', 'hays', 'souer', 'gilbert' );
		return in_array( $value, $allowed, true ) ? $value : 'david';
	}

	/**
	 * Sanitize schedule mode.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_schedule_mode( $value ) {
		$modes = HWBL_Scheduler::get_schedule_modes();
		return isset( $modes[ $value ] ) ? $value : 'week';
	}

	/**
	 * Sanitize translation.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_translation( $value ) {
		$translations = HWBL_Translation_Service::instance()->get_supported_translations();
		return isset( $translations[ $value ] ) ? $value : 'niv';
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$schedule_mode = get_option( 'hwbl_schedule_mode', 'week' );
		$translation   = get_option( 'hwbl_active_translation', 'niv' );
		$ai_enabled       = (bool) get_option( 'hwbl_ai_enabled', false );
		$helloao_enabled  = (bool) get_option( 'hwbl_helloao_enabled', true );
		$reader_enabled   = (bool) get_option( 'hwbl_bible_reader_enabled', true );
		$reader_narrator  = sanitize_key( (string) get_option( 'hwbl_bible_reader_narrator', 'david' ) );
		$self_signup      = (bool) get_option( 'hwbl_companion_self_signup', false );
		$google_audiences = (string) get_option( 'hwbl_google_oauth_audiences', '' );
		$maps_enabled     = (bool) get_option( 'hwbl_bible_maps_enabled', true );
		$maps_provider    = sanitize_key( (string) get_option( 'hwbl_bible_maps_provider', 'leaflet' ) );
		if ( ! in_array( $maps_provider, array( 'leaflet', 'mapbox' ), true ) ) {
			$maps_provider = 'leaflet';
		}
		$mapbox_token        = (string) get_option( 'hwbl_bible_maps_mapbox_token', '' );
		$mapbox_style_key    = class_exists( 'HWBL_Bible_Places' ) ? HWBL_Bible_Places::get_mapbox_style_key() : 'outdoors';
		$mapbox_style        = (string) get_option( 'hwbl_bible_maps_mapbox_style', '' );
		$mapbox_presets      = class_exists( 'HWBL_Bible_Places' ) ? HWBL_Bible_Places::get_mapbox_style_presets() : array();
		$concordance_enabled = (bool) get_option( 'hwbl_bible_concordance_enabled', true );
		$modes         = HWBL_Scheduler::get_schedule_modes();
		$translations  = HWBL_Translation_Service::instance()->get_supported_translations();
		$trans_svc     = HWBL_Translation_Service::instance();
		$connect_url   = class_exists( 'HWBL_App_Connect' ) ? HWBL_App_Connect::connect_url() : home_url( '/app/connect' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Hidden Word Bible Lessons Settings', 'hidden-word-bible-lessons' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'hwbl_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Schedule Mode', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<select name="hwbl_schedule_mode">
								<?php foreach ( $modes as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $schedule_mode, $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'How often the site rotates to a new lesson.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Active Translation', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<select name="hwbl_active_translation">
								<?php foreach ( $translations as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $translation, $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php
								$count = count( $translations );
								printf(
									/* translators: %d: number of bundled translations */
									esc_html( _n( '%d bundled translation available offline.', '%d bundled translations available offline.', $count, 'hidden-word-bible-lessons' ) ),
									absint( $count )
								);
								?>
								<?php if ( class_exists( 'THW_Premium_API_Bible' ) && THW_Premium_API_Bible::get_api_key() ) : ?>
									<?php esc_html_e( 'API.Bible is configured — additional translations appear on the front-end switcher.', 'hidden-word-bible-lessons' ); ?>
								<?php else : ?>
									<?php esc_html_e( 'Optional: add your API.Bible key under Bible Lessons → Advanced to enable ESV, NLT, NASB, CSB, NKJV, AMP, and NET on the front-end switcher.', 'hidden-word-bible-lessons' ); ?>
								<?php endif; ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Hello AO Bible API', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="hwbl_helloao_enabled" value="0" />
							<label>
								<input type="checkbox" name="hwbl_helloao_enabled" value="1" <?php checked( $helloao_enabled ); ?> />
								<?php esc_html_e( 'Enable Hello AO for verse text (no API key required)', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php
								printf(
									/* translators: %s: Hello AO URL */
									wp_kses_post( __( 'Fetches public-domain and open-license translations from <a href="%s" target="_blank" rel="noopener">bible.helloao.org</a> for echo verses, out-of-curriculum references, and extra translations. Bundled NIV/KJV/WEB still load offline first.', 'hidden-word-bible-lessons' ) ),
									esc_url( 'https://bible.helloao.org/' )
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Bible Reader', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="hwbl_bible_reader_enabled" value="0" />
							<label>
								<input type="checkbox" name="hwbl_bible_reader_enabled" value="1" <?php checked( $reader_enabled ); ?> />
								<?php esc_html_e( 'Enable full chapter Bible reader shortcode', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Adds [hwbl_bible_reader] to read and listen to any book/chapter. Uses Hello AO text and audio; optional Biblia/API.Bible keys enable additional translations.', 'hidden-word-bible-lessons' ); ?>
							</p>
							<p>
								<label for="hwbl_bible_reader_narrator"><?php esc_html_e( 'Default audio narrator', 'hidden-word-bible-lessons' ); ?></label>
								<select id="hwbl_bible_reader_narrator" name="hwbl_bible_reader_narrator">
									<?php foreach ( array( 'david', 'hays', 'souer', 'gilbert' ) as $narrator ) : ?>
										<option value="<?php echo esc_attr( $narrator ); ?>" <?php selected( $reader_narrator, $narrator ); ?>><?php echo esc_html( ucfirst( $narrator ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable AI Features', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="hwbl_ai_enabled" value="0" />
							<label>
								<input type="checkbox" name="hwbl_ai_enabled" value="1" <?php checked( $ai_enabled ); ?> />
								<?php esc_html_e( 'Allow AI-powered lesson explanations and keyword study search on the front end', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Requires Hidden Word Bible Lessons Premium with a valid license and an AI provider (WordPress 7.0 Connectors or BYOK keys). Configure rules under Bible Lessons → Premium.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Bible Maps', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="hwbl_bible_maps_enabled" value="0" />
							<label>
								<input type="checkbox" name="hwbl_bible_maps_enabled" value="1" <?php checked( $maps_enabled ); ?> />
								<?php esc_html_e( 'Show place maps for Bible verses (OpenBible geocoding data)', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Adds a Map places control to the Bible reader and the [hwbl_bible_map] shortcode. Geographic data © OpenBible.info (CC BY 4.0).', 'hidden-word-bible-lessons' ); ?>
							</p>
							<p>
								<label for="hwbl_bible_maps_provider"><?php esc_html_e( 'Map provider', 'hidden-word-bible-lessons' ); ?></label>
								<select id="hwbl_bible_maps_provider" name="hwbl_bible_maps_provider">
									<option value="leaflet" <?php selected( $maps_provider, 'leaflet' ); ?>><?php esc_html_e( 'Leaflet + OpenStreetMap (no API key)', 'hidden-word-bible-lessons' ); ?></option>
									<option value="mapbox" <?php selected( $maps_provider, 'mapbox' ); ?>><?php esc_html_e( 'Mapbox GL', 'hidden-word-bible-lessons' ); ?></option>
								</select>
							</p>
							<p>
								<label for="hwbl_bible_maps_mapbox_token"><?php esc_html_e( 'Mapbox access token', 'hidden-word-bible-lessons' ); ?></label><br />
								<input type="password" class="regular-text" id="hwbl_bible_maps_mapbox_token" name="hwbl_bible_maps_mapbox_token" value="<?php echo esc_attr( $mapbox_token ); ?>" autocomplete="off" />
							</p>
							<p>
								<label for="hwbl_bible_maps_mapbox_style_key"><?php esc_html_e( 'Mapbox map style', 'hidden-word-bible-lessons' ); ?></label><br />
								<select id="hwbl_bible_maps_mapbox_style_key" name="hwbl_bible_maps_mapbox_style_key">
									<?php foreach ( $mapbox_presets as $preset_key => $preset ) : ?>
										<option value="<?php echo esc_attr( $preset_key ); ?>" <?php selected( $mapbox_style_key, $preset_key ); ?>>
											<?php echo esc_html( $preset['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
							<p class="hwbl-mapbox-custom-style" <?php echo 'custom' === $mapbox_style_key ? '' : 'hidden'; ?>>
								<label for="hwbl_bible_maps_mapbox_style"><?php esc_html_e( 'Custom Mapbox Studio style URL', 'hidden-word-bible-lessons' ); ?></label><br />
								<input type="text" class="regular-text" id="hwbl_bible_maps_mapbox_style" name="hwbl_bible_maps_mapbox_style" value="<?php echo esc_attr( $mapbox_style ); ?>" placeholder="mapbox://styles/your-username/your-style-id" />
							</p>
							<p class="description">
								<?php esc_html_e( 'Mapbox token is required when Mapbox is selected. Default style is Outdoors (terrain & journeys). Leaflet works without a key.', 'hidden-word-bible-lessons' ); ?>
							</p>
							<p class="description">
								<?php esc_html_e( 'Custom Studio tip: mute modern POIs and highway shields, use parchment land (#FDFBF7), warm water, and serif labels (Cinzel, Lora, or EB Garamond). Then paste the style URL here.', 'hidden-word-bible-lessons' ); ?>
							</p>
							<script>
							(function () {
								var sel = document.getElementById('hwbl_bible_maps_mapbox_style_key');
								var wrap = document.querySelector('.hwbl-mapbox-custom-style');
								if (!sel || !wrap) return;
								function sync() { wrap.hidden = sel.value !== 'custom'; }
								sel.addEventListener('change', sync);
								sync();
							})();
							</script>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Bible Concordance', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="hwbl_bible_concordance_enabled" value="0" />
							<label>
								<input type="checkbox" name="hwbl_bible_concordance_enabled" value="1" <?php checked( $concordance_enabled ); ?> />
								<?php esc_html_e( 'Enable concordance word/phrase study', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Adds [hwbl_bible_concordance] and a Concordance panel in the Bible reader. Installed Local Bibles search offline; NIV/NLT require a Biblia.com API key under Advanced settings.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Companion App Sign-up', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="hwbl_companion_self_signup" value="0" />
							<label>
								<input type="checkbox" name="hwbl_companion_self_signup" value="1" <?php checked( $self_signup ); ?> />
								<?php esc_html_e( 'Allow members to create a WordPress account from the companion connect page', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php
								printf(
									/* translators: %s: connect URL */
									esc_html__( 'Members open %s in a browser, sign in (or create an account when enabled), then return to the app with a one-time connect code. New accounts are Subscribers.', 'hidden-word-bible-lessons' ),
									esc_html( $connect_url )
								);
								?>
							</p>
							<p class="description">
								<code><?php echo esc_html( $connect_url ); ?></code>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="hwbl_google_oauth_audiences"><?php esc_html_e( 'Google Sign-In client IDs', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<input name="hwbl_google_oauth_audiences" id="hwbl_google_oauth_audiences" type="text" class="large-text" value="<?php echo esc_attr( $google_audiences ); ?>" placeholder="….apps.googleusercontent.com, ….apps.googleusercontent.com" />
							<p class="description">
								<?php esc_html_e( 'OAuth client IDs allowed as JWT audience for POST /hwbl/v1/auth/google (web + iOS + Android). Comma-separated. Required for Sign in with Google in the companion app.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Copyright Notice', 'hidden-word-bible-lessons' ); ?></h2>
				<div class="hwbl-copyright-preview">
					<?php echo wp_kses_post( $trans_svc->render_copyright( $translation ) ); ?>
				</div>

				<?php submit_button(); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Shortcodes', 'hidden-word-bible-lessons' ); ?></h2>
			<p><code>[hwbl_lesson]</code> — <?php esc_html_e( 'Full verse-to-memorize view for the current schedule.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_lesson id="123"]</code> — <?php esc_html_e( 'Specific verse study by post ID.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_lesson_list]</code> — <?php esc_html_e( 'Browse the verse catalog (group by book, testament, or flat list).', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_verse_of_week]</code> — <?php esc_html_e( 'Compact scheduled verse display.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_bible_reader]</code> — <?php esc_html_e( 'Read and listen to any Bible chapter (translation, book, and chapter pickers).', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_bible_map book="43" chapter="3" verse="16"]</code> — <?php esc_html_e( 'Map biblical places for a verse or chapter (OpenBible data). Use scope="book" for the whole book; the map also has a This passage / Whole book toggle.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_bible_concordance]</code> — <?php esc_html_e( 'Word/phrase concordance (Local Bibles offline; NIV/NLT via Biblia when configured).', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_memorize_verse]</code> — <?php esc_html_e( 'Pick any accessible verse reference and open memorization practice.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_study_finder]</code> — <?php esc_html_e( 'Keyword Bible study search (AI / curriculum; configure under Advanced Settings).', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_ask_question]</code> — <?php esc_html_e( 'Ask a Bible question (configure AI under Advanced Settings).', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_verse_of_the_day]</code> — <?php esc_html_e( 'Verse of the Day display.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_my_progress]</code> — <?php esc_html_e( 'Memorization progress and streaks.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[hwbl_memorize_reviews]</code> — <?php esc_html_e( 'Spaced-repetition review queue.', 'hidden-word-bible-lessons' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: %s: schedule-aware widget label */
					esc_html__( 'Widgets: add “%s” under Appearance → Widgets.', 'hidden-word-bible-lessons' ),
					esc_html( HWBL_Scheduler::get_schedule_phrase( 'compact' ) )
				);
				?>
			</p>
			<p><?php esc_html_e( 'Verse catalog archive:', 'hidden-word-bible-lessons' ); ?> <code>/bible-lesson/</code></p>
			<p><?php esc_html_e( 'Verse pages include Print and Copy verse buttons in the toolbar.', 'hidden-word-bible-lessons' ); ?></p>
			<p><?php esc_html_e( 'API keys, digests, AI, and scheduling extras: Bible Lessons → Advanced.', 'hidden-word-bible-lessons' ); ?></p>
		</div>
		<?php
	}
}
