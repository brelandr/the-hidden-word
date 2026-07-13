<?php
/**
 * Plugin settings page.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Settings
 */
class THW_Settings {

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
		register_setting( 'thw_settings', 'thw_schedule_mode', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_schedule_mode' ),
			'default'           => 'week',
		) );

		register_setting( 'thw_settings', 'thw_active_translation', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_translation' ),
			'default'           => 'niv',
		) );
	}

	/**
	 * Sanitize schedule mode.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_schedule_mode( $value ) {
		$modes = THW_Scheduler::get_schedule_modes();
		return isset( $modes[ $value ] ) ? $value : 'week';
	}

	/**
	 * Sanitize translation.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public function sanitize_translation( $value ) {
		$translations = THW_Translation_Service::instance()->get_supported_translations();
		return isset( $translations[ $value ] ) ? $value : 'niv';
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$schedule_mode = get_option( 'thw_schedule_mode', 'week' );
		$translation   = get_option( 'thw_active_translation', 'niv' );
		$modes         = THW_Scheduler::get_schedule_modes();
		$translations  = THW_Translation_Service::instance()->get_supported_translations();
		$trans_svc     = THW_Translation_Service::instance();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'The Hidden Word Settings', 'the-hidden-word' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'thw_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Schedule Mode', 'the-hidden-word' ); ?></th>
						<td>
							<select name="thw_schedule_mode">
								<?php foreach ( $modes as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $schedule_mode, $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'How often the site rotates to a new lesson.', 'the-hidden-word' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Active Translation', 'the-hidden-word' ); ?></th>
						<td>
							<select name="thw_active_translation">
								<?php foreach ( $translations as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $translation, $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Copyright Notice', 'the-hidden-word' ); ?></h2>
				<div class="thw-copyright-preview">
					<?php echo $trans_svc->render_copyright( $translation ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php submit_button(); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Shortcodes', 'the-hidden-word' ); ?></h2>
			<p><code>[thw_lesson]</code> — <?php esc_html_e( 'Full lesson for current schedule.', 'the-hidden-word' ); ?></p>
			<p><code>[thw_lesson id="123"]</code> — <?php esc_html_e( 'Specific lesson by post ID.', 'the-hidden-word' ); ?></p>
			<p><code>[thw_verse_of_week]</code> — <?php esc_html_e( 'Compact verse display.', 'the-hidden-word' ); ?></p>

			<hr />

			<p class="thw-premium-upsell">
				<?php
				printf(
					/* translators: %s: premium plugin URL */
					wp_kses_post( __( 'Want custom scheduling, PDF leader guides, multi-translation switching, and progress tracking? <a href="%s" target="_blank" rel="noopener">Learn about The Hidden Word Premium</a>.', 'the-hidden-word' ) ),
					esc_url( 'https://landtechwebdesigns.com/product/the-hidden-word-premium/' )
				);
				?>
			</p>
		</div>
		<?php
	}
}
