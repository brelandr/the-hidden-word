<?php
/**
 * Companion app brand, version-gate, and Expo push settings UI.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Settings_App
 */
class THW_Premium_Settings_App {

	/**
	 * Option keys for the app brand / version-gate settings group.
	 *
	 * @return string[]
	 */
	public static function option_keys() {
		return array(
			'hwbl_app_primary_color',
			'hwbl_app_secondary_color',
			'hwbl_app_min_version',
			'hwbl_app_min_ios_build',
			'hwbl_app_min_android_version_code',
			'hwbl_expo_access_token',
		);
	}

	/**
	 * Render companion app branding, version gates, Expo push, and church-network UI.
	 */
	public static function render_section() {
		?>
			<?php if ( class_exists( 'HWBL_Church_Network' ) ) : ?>
				<?php
				$site_url   = untrailingslashit( home_url() );
				$join_url   = HWBL_Church_Network::join_url_for_site( $site_url );
				$deep_link  = HWBL_Church_Network::church_deep_link( $site_url );
				$net_status = HWBL_Church_Network::get_local_status();
				$net_state  = isset( $net_status['state'] ) ? (string) $net_status['state'] : '';
				$is_hub     = HWBL_Church_Network::is_hub();
				?>
				<h2><?php esc_html_e( 'Companion App — Church Network', 'hidden-word-bible-lessons' ); ?></h2>
				<p><?php esc_html_e( 'Members can find your church in the Hidden Word app directory, or scan this QR code from a bulletin to open your site in the app.', 'hidden-word-bible-lessons' ); ?></p>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'App theme colors', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<form method="post" action="options.php" style="margin-bottom:1em;">
								<?php settings_fields( 'hwbl_app_brand' ); ?>
								<p>
									<label><?php esc_html_e( 'Primary color', 'hidden-word-bible-lessons' ); ?>
										<input type="text" class="regular-text" name="hwbl_app_primary_color" value="<?php echo esc_attr( (string) get_option( 'hwbl_app_primary_color', '' ) ); ?>" placeholder="#1B3A4B" />
									</label>
								</p>
								<p>
									<label><?php esc_html_e( 'Secondary / accent color', 'hidden-word-bible-lessons' ); ?>
										<input type="text" class="regular-text" name="hwbl_app_secondary_color" value="<?php echo esc_attr( (string) get_option( 'hwbl_app_secondary_color', '' ) ); ?>" placeholder="#C9A227" />
									</label>
								</p>
								<p>
									<label><?php esc_html_e( 'Minimum app version (semver)', 'hidden-word-bible-lessons' ); ?>
										<input type="text" class="regular-text" name="hwbl_app_min_version" value="<?php echo esc_attr( (string) get_option( 'hwbl_app_min_version', '' ) ); ?>" placeholder="1.0.0" />
									</label>
								</p>
								<p>
									<label><?php esc_html_e( 'Minimum iOS build number', 'hidden-word-bible-lessons' ); ?>
										<input type="text" class="regular-text" name="hwbl_app_min_ios_build" value="<?php echo esc_attr( (string) get_option( 'hwbl_app_min_ios_build', '' ) ); ?>" placeholder="1" />
									</label>
								</p>
								<p>
									<label><?php esc_html_e( 'Minimum Android version code', 'hidden-word-bible-lessons' ); ?>
										<input type="number" class="small-text" min="0" step="1" name="hwbl_app_min_android_version_code" value="<?php echo esc_attr( (string) (int) get_option( 'hwbl_app_min_android_version_code', 0 ) ); ?>" />
									</label>
								</p>
								<p>
									<label><?php esc_html_e( 'Expo access token (optional, for remote push)', 'hidden-word-bible-lessons' ); ?>
										<input type="password" class="regular-text" autocomplete="off" name="hwbl_expo_access_token" value="<?php echo esc_attr( (string) get_option( 'hwbl_expo_access_token', '' ) ); ?>" />
									</label>
								</p>
								<?php submit_button( __( 'Save app branding & version gates', 'hidden-word-bible-lessons' ), 'secondary', 'submit', false ); ?>
								<p class="description"><?php esc_html_e( 'Colors and logo branding appear after members select your church. Version floors tell older companion installs to update from the App Store / Play Store. Expo access token enables higher-volume push delivery from this site.', 'hidden-word-bible-lessons' ); ?></p>
							</form>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Join QR code', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<div
								class="hwbl-admin-qr"
								data-hwbl-qr="<?php echo esc_attr( $join_url ); ?>"
								data-hwbl-qr-size="5"
								data-hwbl-qr-alt="<?php esc_attr_e( 'QR code to open Hidden Word for this church', 'hidden-word-bible-lessons' ); ?>"
							></div>
							<p class="description">
								<strong><?php esc_html_e( 'Join link:', 'hidden-word-bible-lessons' ); ?></strong>
								<code><?php echo esc_html( $join_url ); ?></code><br />
								<strong><?php esc_html_e( 'App deep link:', 'hidden-word-bible-lessons' ); ?></strong>
								<code><?php echo esc_html( $deep_link ); ?></code><br />
								<?php esc_html_e( 'QR is generated in your browser — the join URL is not sent to a third-party image service.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<?php if ( ! $is_hub ) : ?>
						<tr>
							<th><?php esc_html_e( 'Connect to Network', 'hidden-word-bible-lessons' ); ?></th>
							<td>
								<?php if ( $net_state ) : ?>
									<p>
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: connection status */
												__( 'Status: %s', 'hidden-word-bible-lessons' ),
												$net_state
											)
										);
										?>
										<?php if ( ! empty( $net_status['message'] ) ) : ?>
											— <?php echo esc_html( (string) $net_status['message'] ); ?>
										<?php endif; ?>
									</p>
								<?php endif; ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="hwbl_network_connect" />
									<?php wp_nonce_field( 'hwbl_network_connect' ); ?>
									<p>
										<label><?php esc_html_e( 'City', 'hidden-word-bible-lessons' ); ?>
											<input type="text" name="hwbl_church_city" class="regular-text" />
										</label>
										<label><?php esc_html_e( 'State', 'hidden-word-bible-lessons' ); ?>
											<input type="text" name="hwbl_church_state" class="regular-text" style="width:6em;" />
										</label>
										<label><?php esc_html_e( 'ZIP', 'hidden-word-bible-lessons' ); ?>
											<input type="text" name="hwbl_church_zip" class="regular-text" style="width:8em;" />
										</label>
									</p>
									<p>
										<label><?php esc_html_e( 'Hub token (if required)', 'hidden-word-bible-lessons' ); ?>
											<input type="text" name="hwbl_hub_token" class="regular-text" autocomplete="off" />
										</label>
									</p>
									<?php submit_button( __( 'Connect to Network', 'hidden-word-bible-lessons' ), 'secondary', 'submit', false ); ?>
									<p class="description"><?php esc_html_e( 'Sends your church name, site URL, and location to thehiddenword.org for directory listing. Listings may require hub approval before they appear in the app.', 'hidden-word-bible-lessons' ); ?></p>
								</form>
							</td>
						</tr>
					<?php else : ?>
						<tr>
							<th><?php esc_html_e( 'Hub mode', 'hidden-word-bible-lessons' ); ?></th>
							<td>
								<p><?php esc_html_e( 'This site is the network hub. Approve church listings under Bible Lessons → Network Churches.', 'hidden-word-bible-lessons' ); ?></p>
								<form method="post" action="options.php">
									<?php settings_fields( 'hwbl_network_hub' ); ?>
									<p>
										<label><?php esc_html_e( 'Optional registration token', 'hidden-word-bible-lessons' ); ?><br />
											<input type="text" class="regular-text" name="hwbl_network_hub_token" value="<?php echo esc_attr( (string) get_option( 'hwbl_network_hub_token', '' ) ); ?>" autocomplete="off" />
										</label>
									</p>
									<p>
										<label><?php esc_html_e( 'App Store URL (shown on /app/join)', 'hidden-word-bible-lessons' ); ?><br />
											<input type="url" class="regular-text" name="hwbl_app_store_ios_url" value="<?php echo esc_attr( (string) get_option( 'hwbl_app_store_ios_url', '' ) ); ?>" placeholder="https://apps.apple.com/app/id…" />
										</label>
									</p>
									<p>
										<label><?php esc_html_e( 'Google Play URL (shown on /app/join)', 'hidden-word-bible-lessons' ); ?><br />
											<input type="url" class="regular-text" name="hwbl_app_store_android_url" value="<?php echo esc_attr( (string) get_option( 'hwbl_app_store_android_url', '' ) ); ?>" placeholder="https://play.google.com/store/apps/details?id=…" />
										</label>
									</p>
									<p>
										<label><?php esc_html_e( 'Apple Team ID (for Universal Links AASA)', 'hidden-word-bible-lessons' ); ?><br />
											<input type="text" class="regular-text" name="hwbl_apple_team_id" value="<?php echo esc_attr( (string) get_option( 'hwbl_apple_team_id', '' ) ); ?>" placeholder="XXXXXXXXXX" autocomplete="off" />
										</label>
									</p>
									<p>
										<label><?php esc_html_e( 'Android SHA-256 cert fingerprints (comma-separated)', 'hidden-word-bible-lessons' ); ?><br />
											<input type="text" class="large-text" name="hwbl_android_sha256_cert_fingerprints" value="<?php echo esc_attr( (string) get_option( 'hwbl_android_sha256_cert_fingerprints', '' ) ); ?>" placeholder="AA:BB:…" autocomplete="off" />
										</label>
									</p>
									<?php submit_button( __( 'Save hub settings', 'hidden-word-bible-lessons' ), 'secondary', 'submit', false ); ?>
								</form>
							</td>
						</tr>
					<?php endif; ?>
				</table>
				<hr />
			<?php endif; ?>
		<?php
	}

}
