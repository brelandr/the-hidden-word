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

		register_setting( 'hwbl_settings', 'hwbl_companion_self_signup', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		) );
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
