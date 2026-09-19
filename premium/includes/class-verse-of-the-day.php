<?php
/**
 * Bible.com / YouVersion Verse of the Day shortcode + optional AI explain.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Verse_Of_The_Day
 */
class THW_Premium_Verse_Of_The_Day {

	const SOURCE_URL   = 'https://www.bible.com/verse-of-the-day';
	const TRANSIENT    = 'thw_votd_payload_';
	const META_TRANSIENT = 'thw_votd_bible_com_meta_';
	const EXPLAIN_OPT  = 'thw_votd_ai_explain';
	const IMAGE_OPT    = 'thw_votd_show_image';
	const TRANS_OPT    = 'thw_votd_translation';
	const SOURCE_OPT   = 'thw_votd_source';
	const DELIVERY_OPT = 'thw_votd_explain_delivery';
	const CRON_HOOK    = 'thw_premium_votd_daily_refresh';
	/** Opt-in: pre-generate today's VOTD explains for selected translations. */
	const AUTO_EXPLAIN_OPT           = 'thw_votd_auto_explain_enabled';
	const AUTO_EXPLAIN_TRANS_OPT     = 'thw_votd_auto_explain_translations';
	const AUTO_EXPLAIN_LAST_OPT      = 'thw_votd_auto_explain_last';
	const AUTO_EXPLAIN_STATUS_OPT    = 'thw_votd_auto_explain_status';
	/** Opt-in: pre-generate today's VOTD Study This Verse cards for selected translations. */
	const AUTO_STUDY_OPT             = 'thw_votd_auto_study_enabled';
	const AUTO_STUDY_TRANS_OPT       = 'thw_votd_auto_study_translations';
	const AUTO_STUDY_LAST_OPT        = 'thw_votd_auto_study_last';
	const AUTO_STUDY_STATUS_OPT      = 'thw_votd_auto_study_status';
	const RATE_LIMIT   = 10;
	const RATE_WINDOW  = 3600;

	/**
	 * Whether VOTD diagnostic lines should be written to debug.log.
	 *
	 * Requires WP_DEBUG and WP_DEBUG_LOG in wp-config.php.
	 *
	 * @return bool
	 */
	public static function is_debug_logging_enabled() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG
			&& defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
	}

	/**
	 * Write a prefixed diagnostic line when debug logging is enabled.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional structured context.
	 */
	public static function debug_log( $message, $context = array() ) {
		if ( ! self::is_debug_logging_enabled() ) {
			return;
		}

		$line = '[THW VOTD] ' . (string) $message;
		if ( ! empty( $context ) ) {
			$encoded = wp_json_encode( $context );
			if ( is_string( $encoded ) && '' !== $encoded ) {
				$line .= ' ' . $encoded;
			}
		}

		error_log( $line ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * Whether front-end assets should load on this request.
	 *
	 * @var bool
	 */
	private static $assets_needed = false;

	/**
	 * Initialize (licensed): REST + assets. Shortcode registered separately.
	 */
	public static function init() {
		self::register_shortcode();
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_footer', array( __CLASS__, 'enqueue_late_assets' ), 6 );
		add_action( 'init', array( __CLASS__, 'schedule_daily_refresh' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_cache_on_upgrade' ), 5 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_daily_refresh' ) );
		add_action( 'admin_post_thw_clear_votd_cache', array( __CLASS__, 'handle_clear_cache_admin' ) );
		add_action( 'admin_post_thw_votd_auto_explain_now', array( __CLASS__, 'handle_auto_explain_now_admin' ) );
		add_action( 'admin_post_thw_votd_auto_study_now', array( __CLASS__, 'handle_auto_study_now_admin' ) );
		// admin-ajax uses normal cookie auth (no REST nonce required) so page-cache
		// HTML with a logged-out wp_rest nonce can still refresh a valid session.
		add_action( 'wp_ajax_thw_votd_auth', array( __CLASS__, 'ajax_auth_bootstrap' ) );
		add_action( 'wp_ajax_nopriv_thw_votd_auth', array( __CLASS__, 'ajax_auth_bootstrap' ) );
	}

	/**
	 * Return a fresh REST nonce and generate capability for the current cookie session.
	 *
	 * Used by votd.js so cached page HTML cannot leave a logged-in visitor with a
	 * user-0 nonce (which makes REST treat them as logged out).
	 */
	public static function ajax_auth_bootstrap() {
		nocache_headers();
		$ai_ready = self::is_ai_explain_enabled()
			&& function_exists( 'thw_premium_ai_frontend_available' )
			&& thw_premium_ai_frontend_available();
		wp_send_json_success(
			array(
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'loggedIn'    => is_user_logged_in(),
				'canGenerate' => self::is_ai_available(),
				'aiReady'     => $ai_ready,
				'aiReason'    => $ai_ready ? '' : self::get_ai_unavailable_reason(),
			)
		);
	}

	/**
	 * Drop today's VOTD cache after a plugin upgrade so stale verses are not reused.
	 */
	public static function maybe_flush_cache_on_upgrade() {
		if ( ! THW_Premium_License::is_licensed() ) {
			return;
		}

		$version = defined( 'THW_PREMIUM_VERSION' ) ? THW_PREMIUM_VERSION : '';
		if ( '' === $version ) {
			return;
		}

		$flushed = (string) get_option( 'thw_votd_cache_flush_version', '' );
		if ( $flushed === $version ) {
			return;
		}

		self::clear_cache_for_day( wp_date( 'Y-m-d' ) );
		update_option( 'thw_votd_cache_flush_version', $version, false );
	}

	/**
	 * Register shortcode even when unlicensed (shows notice instead of raw tag).
	 */
	public static function register_shortcode() {
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;
		thw_premium_register_shortcode( 'thw_verse_of_the_day', array( __CLASS__, 'render_shortcode' ) );
		thw_premium_register_shortcode( 'thw_bible_com_votd', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * How saved explanations are delivered: inline or redirect to the post.
	 *
	 * @return string inline|redirect
	 */
	public static function get_explain_delivery() {
		$mode = sanitize_key( (string) get_option( self::DELIVERY_OPT, 'redirect' ) );
		return in_array( $mode, array( 'inline', 'redirect' ), true ) ? $mode : 'redirect';
	}

	/**
	 * Whether REST/JS should redirect to the saved explanation post.
	 *
	 * Companion app clients always receive inline content (they send
	 * X-HWBL-Client: companion). Website delivery still follows the admin
	 * “Saved explanation delivery” setting.
	 *
	 * @param string $post_url Post permalink.
	 * @return bool
	 */
	public static function should_redirect_to_post( $post_url ) {
		if ( ! $post_url ) {
			return false;
		}

		$client = '';
		if ( isset( $_SERVER['HTTP_X_HWBL_CLIENT'] ) ) {
			$client = sanitize_key( wp_unslash( (string) $_SERVER['HTTP_X_HWBL_CLIENT'] ) );
		}
		if ( 'companion' === $client ) {
			return false;
		}

		return 'redirect' === self::get_explain_delivery();
	}

	/**
	 * Whether AI explain for VOTD is enabled in admin.
	 *
	 * When the option has never been saved, follow the site-wide AI toggle so
	 * enabling AI Features also turns on Verse of the Day explain.
	 *
	 * @return bool
	 */
	public static function is_ai_explain_enabled() {
		$value = get_option( self::EXPLAIN_OPT, null );
		if ( null === $value ) {
			return function_exists( 'hwbl_is_ai_enabled' ) && hwbl_is_ai_enabled();
		}

		return (bool) $value;
	}

	/**
	 * Why VOTD explain cannot generate right now (empty when ready).
	 *
	 * @return string
	 */
	public static function get_ai_unavailable_reason() {
		if ( ! self::is_ai_explain_enabled() ) {
			return __( 'AI explanation for Verse of the Day is turned off. Enable it under Bible Lessons → Advanced → Verse of the Day → AI explanation.', 'hidden-word-bible-lessons' );
		}

		if ( function_exists( 'thw_premium_ai_frontend_unavailable_reason' ) ) {
			return thw_premium_ai_frontend_unavailable_reason();
		}

		return __( 'AI explanation is not available on this site right now.', 'hidden-word-bible-lessons' );
	}

	/**
	 * Whether VOTD AI explain UI/API can run.
	 *
	 * @return bool
	 */
	public static function is_ai_available() {
		return self::is_ai_explain_enabled()
			&& function_exists( 'thw_premium_ai_frontend_available' )
			&& thw_premium_ai_frontend_available()
			&& is_user_logged_in();
	}

	/**
	 * Register REST routes.
	 */
	public static function register_routes() {
		THW_Premium_Votd_Rest::register_routes();
	}

	/**
	 * Supported Bible translations for the VOTD picker.
	 *
	 * @return array<string, string> slug => label
	 */
	public static function get_translation_choices() {
		if ( ! class_exists( 'HWBL_Translation_Service' ) ) {
			return array();
		}

		$supported = HWBL_Translation_Service::instance()->get_supported_translations();
		if ( ! is_array( $supported ) ) {
			return array();
		}

		$choices = array();
		foreach ( $supported as $slug => $label ) {
			$key = sanitize_key( (string) $slug );
			if ( '' !== $key ) {
				$choices[ $key ] = is_string( $label ) ? $label : strtoupper( $key );
			}
		}

		return $choices;
	}

	/**
	 * Site default translation for VOTD (admin override or active translation).
	 *
	 * @return string
	 */
	public static function get_default_translation() {
		$translation = sanitize_key( (string) get_option( self::TRANS_OPT, '' ) );
		if ( '' !== $translation ) {
			return $translation;
		}
		return sanitize_key( (string) get_option( 'hwbl_active_translation', 'niv' ) );
	}

	/**
	 * Translation to show for the current visitor (user preference when available).
	 *
	 * @return string
	 */
	public static function get_display_translation() {
		$choices = self::get_translation_choices();
		if ( class_exists( 'HWBL_User_Preferences' ) && is_user_logged_in() ) {
			$user = HWBL_User_Preferences::get_preferred_translation();
			if ( '' !== $user && ( empty( $choices ) || isset( $choices[ $user ] ) ) ) {
				return $user;
			}
		}

		$site = self::get_default_translation();
		if ( ! empty( $choices ) && ! isset( $choices[ $site ] ) ) {
			$keys = array_keys( $choices );
			return sanitize_key( (string) $keys[0] );
		}
		return $site;
	}

	/**
	 * Admin VOTD reference source: bible.com scrape or site curriculum.
	 *
	 * @return string bible_com|curriculum
	 */
	public static function get_votd_source() {
		$source = sanitize_key( (string) get_option( self::SOURCE_OPT, 'bible_com' ) );
		return in_array( $source, array( 'bible_com', 'curriculum' ), true ) ? $source : 'bible_com';
	}

	/**
	 * Shortcode renderer.
	 *
	 * @param array<string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts = array() ) {
		if ( ! THW_Premium_License::is_licensed() ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return '';
			}
			return '<p class="thw-votd-notice">' . esc_html__( 'Bible.com Verse of the Day requires an active Premium license.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'show_image' => '',
				'explain'    => '',
			),
			is_array( $atts ) ? $atts : array(),
			'thw_verse_of_the_day'
		);

		$payload = self::get_today_payload();
		if ( empty( $payload['reference'] ) ) {
			return '<p class="thw-votd-notice">' . esc_html__( 'Verse of the Day is temporarily unavailable. Please try again later.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		if ( class_exists( 'HWBL_Cache' ) ) {
			HWBL_Cache::mark_page_uncacheable( 'thw_votd' );
		}

		$show_image = '' !== (string) $atts['show_image']
			? (bool) absint( $atts['show_image'] )
			: (bool) get_option( self::IMAGE_OPT, true );

		$allow_explain = '' !== (string) $atts['explain']
			? (bool) absint( $atts['explain'] )
			: self::is_ai_explain_enabled();

		self::$assets_needed = true;

		ob_start();
		?>
		<div class="thw-votd" data-thw-votd="1" data-votd-day="<?php echo esc_attr( (string) $payload['day'] ); ?>" data-reference="<?php echo esc_attr( $payload['reference'] ); ?>" data-site-name="<?php echo esc_attr( (string) get_bloginfo( 'name' ) ); ?>">
			<p class="thw-votd__eyebrow"><?php esc_html_e( 'Bible.com Verse of the Day', 'hidden-word-bible-lessons' ); ?></p>
			<?php if ( $show_image && ! empty( $payload['image'] ) ) : ?>
				<figure class="thw-votd__figure">
					<a href="<?php echo esc_url( self::SOURCE_URL ); ?>" target="_blank" rel="noopener noreferrer">
						<img class="thw-votd__image" src="<?php echo esc_url( $payload['image'] ); ?>" alt="<?php echo esc_attr( sprintf( /* translators: %s: scripture reference */ __( 'Verse of the Day — %s', 'hidden-word-bible-lessons' ), $payload['reference'] ) ); ?>" loading="lazy" width="640" height="640" />
					</a>
				</figure>
			<?php endif; ?>
			<blockquote class="thw-votd__quote">
				<p class="thw-votd__text"><?php echo esc_html( $payload['text'] ); ?></p>
				<footer class="thw-votd__footer">
					<cite class="thw-votd__ref"><?php echo esc_html( $payload['reference'] ); ?></cite>
					<?php if ( ! empty( $payload['translation_label'] ) ) : ?>
						<span class="thw-votd__translation"><?php echo esc_html( $payload['translation_label'] ); ?></span>
					<?php endif; ?>
				</footer>
			</blockquote>
			<p class="thw-votd__share">
				<?php
				echo HWBL_Verse_Share_Card::button_html( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					(string) ( $payload['text'] ?? '' ),
					(string) ( $payload['reference'] ?? '' )
				);
				?>
			</p>
			<p class="thw-votd__credit">
				<a href="<?php echo esc_url( self::SOURCE_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Source: Bible.com / YouVersion', 'hidden-word-bible-lessons' ); ?>
				</a>
			</p>
			<?php if ( $allow_explain ) : ?>
				<?php
				$delivery         = self::get_explain_delivery();
				$existing_map     = array();
				$translation_opts = self::get_translation_choices();
				$default_trans    = self::get_display_translation();
				if ( class_exists( 'THW_Premium_Votd_Explain_Store' ) && ! empty( $payload['day'] ) ) {
					$existing_map = THW_Premium_Votd_Explain_Store::get_urls_for_day( (string) $payload['day'] );
				}
				$ai_ok = self::is_ai_available();
				?>
				<div
					class="thw-votd__explain"
					id="thw-votd-explain"
					data-delivery="<?php echo esc_attr( $delivery ); ?>"
					data-existing-posts="<?php echo esc_attr( wp_json_encode( $existing_map ) ); ?>"
					data-default-translation="<?php echo esc_attr( $default_trans ); ?>"
					data-votd-day="<?php echo esc_attr( (string) $payload['day'] ); ?>"
				>
					<?php if ( ! empty( $translation_opts ) ) : ?>
						<label class="screen-reader-text" for="thw-votd-translation"><?php esc_html_e( 'Bible translation', 'hidden-word-bible-lessons' ); ?></label>
						<select id="thw-votd-translation" class="thw-votd__translation-select" data-thw-translation-select>
							<?php foreach ( $translation_opts as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $default_trans, $slug ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
					<a href="#thw-votd-explain" class="thw-votd__explain-trigger thw-votd__explain-btn thw-btn thw-btn--primary"<?php echo $ai_ok ? '' : ' ' . esc_attr( 'hidden' ); ?>>
						<?php esc_html_e( 'Explain this Bible Verse', 'hidden-word-bible-lessons' ); ?>
					</a>
					<a class="thw-votd__existing-link thw-votd__explain-link" href="#" hidden>
						<?php esc_html_e( 'Read saved explanation', 'hidden-word-bible-lessons' ); ?>
					</a>
					<p class="thw-votd__explain-hint thw-votd__explain-login-hint"<?php echo ( $ai_ok || is_user_logged_in() ) ? ' hidden' : ''; ?>>
						<a class="thw-votd__explain-link" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
							<?php esc_html_e( 'Log in to generate the first AI explanation for this Bible version', 'hidden-word-bible-lessons' ); ?>
						</a>
					</p>
					<?php
					$ai_reason = ( is_user_logged_in() && ! $ai_ok )
						? self::get_ai_unavailable_reason()
						: '';
					?>
					<p class="thw-votd__explain-hint thw-votd__explain-ai-hint"<?php echo $ai_reason ? '' : ' hidden'; ?>>
						<?php echo esc_html( $ai_reason ? $ai_reason : '' ); ?>
					</p>
					<div class="thw-votd__explain-panel" hidden>
						<div class="thw-votd__explain-output" aria-live="polite"></div>
						<div class="thw-votd__explain-actions hwbl-journal-export-actions" hidden>
							<span class="hwbl-journal-export-mount"></span>
							<p class="thw-votd__journal-status description" role="status" aria-live="polite"></p>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Today's VOTD payload (cached) for the site default / admin override translation.
	 *
	 * @return array{reference:string,text:string,image:string,translation:string,translation_label:string,book_id:int,chapter:int,verse_start:int,verse_end:int,day:string}
	 */
	public static function get_today_payload() {
		return self::get_payload_for_translation( '' );
	}

	/**
	 * Today's VOTD payload for a specific Bible translation (cached per day + translation).
	 *
	 * @param string $translation Translation slug; empty uses site VOTD override / active translation.
	 * @return array{reference:string,text:string,image:string,translation:string,translation_label:string,book_id:int,chapter:int,verse_start:int,verse_end:int,day:string}
	 */
	public static function get_payload_for_translation( $translation = '' ) {
		$day         = wp_date( 'Y-m-d' );
		$translation = sanitize_key( (string) $translation );
		$key         = self::TRANSIENT . $day . ( '' !== $translation ? '_' . $translation : '' );
		$cached      = get_transient( $key );
		if ( is_array( $cached ) && self::is_valid_cached_payload( $cached, $day ) ) {
			// Cache hits are the common path; omit debug_log to avoid flooding debug.log.
			return $cached;
		}

		if ( false !== $cached ) {
			delete_transient( $key );
			self::debug_log(
				'Discarded invalid cached payload',
				array(
					'day'         => $day,
					'translation' => $translation,
				)
			);
		}

		self::debug_log(
			'Building payload',
			array(
				'day'         => $day,
				'translation' => $translation,
				'source'      => self::get_votd_source(),
			)
		);

		$payload = self::build_payload_for_today( $day, $translation );
		if ( ! empty( $payload['reference'] ) && ! empty( $payload['text'] ) ) {
			$payload['text'] = class_exists( 'HWBL_Http_Utils' ) ? HWBL_Http_Utils::sanitize_bible_text( $payload['text'] ) : trim( (string) $payload['text'] );
		}
		/**
		 * Filter VOTD payload after build (before cache).
		 *
		 * @param array  $payload     Payload.
		 * @param string $day         Y-m-d.
		 * @param string $translation Translation slug.
		 */
		$payload = apply_filters( 'hwbl_votd_payload', $payload, $day, $translation );
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}
		if ( ! empty( $payload['reference'] ) && ! empty( $payload['text'] ) ) {
			set_transient( $key, $payload, self::seconds_until_midnight() );
			self::debug_log(
				'Payload cached',
				array(
					'day'         => $day,
					'translation' => $translation,
					'reference'   => (string) $payload['reference'],
					'source'      => isset( $payload['votd_source'] ) ? (string) $payload['votd_source'] : '',
				)
			);
		} else {
			self::debug_log(
				'Payload empty; not cached',
				array(
					'day'         => $day,
					'translation' => $translation,
				)
			);
		}

		return $payload;
	}

	/**
	 * Whether a cached VOTD payload is still valid for the requested calendar day.
	 *
	 * @param array<string, mixed> $cached Cached payload.
	 * @param string               $day    Expected Y-m-d.
	 * @return bool
	 */
	public static function is_valid_cached_payload( $cached, $day ) {
		return THW_Premium_VOTD_Cache::is_valid_cached_payload( $cached, $day );
	}

	/**
	 * Clear cached VOTD payloads for a calendar day (and translation variants).
	 *
	 * @param string $day Y-m-d.
	 */
	public static function clear_cache_for_day( $day ) {
		THW_Premium_VOTD_Cache::clear_cache_for_day( $day );
	}

	/**
	 * Admin: clear today's and yesterday's VOTD transients.
	 */
	public static function handle_clear_cache_admin() {
		check_admin_referer( 'thw_clear_votd_cache' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear the Verse of the Day cache.', 'hidden-word-bible-lessons' ) );
		}

		$today = wp_date( 'Y-m-d' );
		self::clear_cache_for_day( $today );
		self::clear_cache_for_day( wp_date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) ) );

		self::debug_log( 'Admin cleared VOTD cache', array( 'day' => $today ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                  => 'thw-premium-settings',
					'thw_votd_cache_cleared' => '1',
				),
				admin_url( 'edit.php?post_type=hwbl_lesson' )
			)
		);
		exit;
	}

	/**
	 * Seconds until midnight in the site timezone (minimum 60).
	 *
	 * @return int
	 */
	public static function seconds_until_midnight() {
		return THW_Premium_VOTD_Cache::seconds_until_midnight();
	}

	/**
	 * Schedule a daily cron job to refresh bible.com VOTD data after midnight.
	 */
	public static function schedule_daily_refresh() {
		if ( ! THW_Premium_License::is_licensed() ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Real UTC timestamp for site-local midnight (not current_time('timestamp') + strtotime).
			try {
				$timestamp = ( new DateTimeImmutable( 'tomorrow midnight', wp_timezone() ) )->getTimestamp();
			} catch ( Exception $e ) {
				$timestamp = time() + DAY_IN_SECONDS;
			}
			wp_schedule_event( $timestamp, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Cron: drop yesterday's caches and warm today's default payload.
	 */
	public static function run_daily_refresh() {
		$today     = wp_date( 'Y-m-d' );
		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );

		self::clear_cache_for_day( $today );
		self::clear_cache_for_day( $yesterday );

		self::debug_log( 'Daily cron refresh', array( 'day' => $today ) );
		self::get_today_payload();
		self::run_daily_auto_explains();
		self::run_daily_auto_studies();
	}

	/**
	 * Whether daily auto-explain for selected translations is enabled.
	 *
	 * Default off so individual churches do not incur AI cost unless they opt in.
	 *
	 * @return bool
	 */
	public static function is_auto_explain_enabled() {
		return (bool) get_option( self::AUTO_EXPLAIN_OPT, false )
			&& self::is_ai_explain_enabled();
	}

	/**
	 * Translations selected for daily auto-explain (sanitized slugs).
	 *
	 * @return string[]
	 */
	public static function get_auto_explain_translations() {
		$raw = get_option( self::AUTO_EXPLAIN_TRANS_OPT, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$choices = self::get_translation_choices();
		$out     = array();
		foreach ( $raw as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug || ! isset( $choices[ $slug ] ) ) {
				continue;
			}
			$out[] = $slug;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Pre-generate today's VOTD explanations for configured translations.
	 *
	 * Skips versions that already have a saved post. Safe to call from cron or admin.
	 *
	 * @param bool $force When true, ignore the once-per-day guard (admin “Run now”).
	 * @return array{day:string,generated:string[],skipped:string[],errors:array<string,string>}
	 */
	public static function run_daily_auto_explains( $force = false ) {
		$day = wp_date( 'Y-m-d' );
		$out = array(
			'day'       => $day,
			'generated' => array(),
			'skipped'   => array(),
			'errors'    => array(),
		);

		if ( ! self::is_auto_explain_enabled() ) {
			$out['errors']['_'] = __( 'Daily auto-explain is disabled, or AI explain verse is off.', 'hidden-word-bible-lessons' );
			update_option( self::AUTO_EXPLAIN_STATUS_OPT, $out, false );
			return $out;
		}

		$translations = self::get_auto_explain_translations();
		if ( empty( $translations ) ) {
			$out['errors']['_'] = __( 'No Bible translations selected for daily auto-explain.', 'hidden-word-bible-lessons' );
			update_option( self::AUTO_EXPLAIN_STATUS_OPT, $out, false );
			return $out;
		}

		$last = get_option( self::AUTO_EXPLAIN_LAST_OPT, array() );
		if ( ! is_array( $last ) ) {
			$last = array();
		}
		if ( ! $force && isset( $last['day'] ) && (string) $last['day'] === $day && ! empty( $last['done'] ) ) {
			self::debug_log( 'Daily auto-explain already completed for day', array( 'day' => $day ) );
			$cached = get_option( self::AUTO_EXPLAIN_STATUS_OPT, array() );
			return is_array( $cached ) ? $cached : $out;
		}

		if ( ! function_exists( 'thw_premium_ai_frontend_available' ) || ! thw_premium_ai_frontend_available() ) {
			$out['errors']['_'] = self::get_ai_unavailable_reason()
				? self::get_ai_unavailable_reason()
				: __( 'AI provider is not available.', 'hidden-word-bible-lessons' );
			update_option( self::AUTO_EXPLAIN_STATUS_OPT, $out, false );
			return $out;
		}

		foreach ( $translations as $slug ) {
			$payload = self::get_payload_for_translation( $slug );
			if ( empty( $payload['reference'] ) || empty( $payload['text'] ) ) {
				$out['errors'][ $slug ] = __( 'Could not load verse text for this translation.', 'hidden-word-bible-lessons' );
				continue;
			}

			if ( class_exists( 'THW_Premium_Votd_Explain_Store' ) ) {
				$existing = THW_Premium_Votd_Explain_Store::find_saved_post( $payload );
				if ( $existing instanceof WP_Post ) {
					$out['skipped'][] = $slug;
					continue;
				}
			}

			$result = self::ensure_explanation( $payload, true );
			if ( ! empty( $result['generated'] ) || ( ! empty( $result['post_url'] ) && ! empty( $result['html'] ) ) ) {
				if ( ! empty( $result['generated'] ) ) {
					$out['generated'][] = $slug;
				} else {
					$out['skipped'][] = $slug;
				}
			} else {
				$out['errors'][ $slug ] = __( 'Explain generation failed or was blocked.', 'hidden-word-bible-lessons' );
			}
		}

		$all_ok = empty( $out['errors'] ) && ( ! empty( $out['generated'] ) || ! empty( $out['skipped'] ) );
		update_option(
			self::AUTO_EXPLAIN_LAST_OPT,
			array(
				'day'  => $day,
				'done' => $all_ok || ( ! empty( $out['generated'] ) || ! empty( $out['skipped'] ) ),
				'at'   => current_time( 'mysql' ),
			),
			false
		);
		update_option( self::AUTO_EXPLAIN_STATUS_OPT, $out, false );
		self::debug_log( 'Daily auto-explain finished', $out );
		return $out;
	}

	/**
	 * Admin: run auto-explain for today immediately.
	 */
	public static function handle_auto_explain_now_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'hidden-word-bible-lessons' ) );
		}
		check_admin_referer( 'thw_votd_auto_explain_now' );

		self::run_daily_auto_explains( true );

		$redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=hwbl_lesson&page=thw-premium-settings' );
		wp_safe_redirect( add_query_arg( 'thw_votd_auto_explain_ran', '1', $redirect ) );
		exit;
	}

	/**
	 * Whether daily auto-study (Study This Verse) for selected translations is enabled.
	 *
	 * Default off so individual churches do not incur AI cost unless they opt in.
	 *
	 * @return bool
	 */
	public static function is_auto_study_enabled() {
		return (bool) get_option( self::AUTO_STUDY_OPT, false )
			&& class_exists( 'THW_Premium_Bible_Study_Card' )
			&& class_exists( 'THW_Premium_AI_Client' )
			&& THW_Premium_AI_Client::is_configured();
	}

	/**
	 * Translations selected for daily auto-study (sanitized slugs).
	 *
	 * @return string[]
	 */
	public static function get_auto_study_translations() {
		$raw = get_option( self::AUTO_STUDY_TRANS_OPT, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$choices = self::get_translation_choices();
		$out     = array();
		foreach ( $raw as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug || ! isset( $choices[ $slug ] ) ) {
				continue;
			}
			$out[] = $slug;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Pre-generate today's VOTD Study This Verse cards for configured translations.
	 *
	 * Skips versions that already have a valid cached study card. Safe for cron or admin.
	 *
	 * @param bool $force When true, ignore the once-per-day guard (admin “Run now”).
	 * @return array{day:string,generated:string[],skipped:string[],errors:array<string,string>}
	 */
	public static function run_daily_auto_studies( $force = false ) {
		$day = wp_date( 'Y-m-d' );
		$out = array(
			'day'       => $day,
			'generated' => array(),
			'skipped'   => array(),
			'errors'    => array(),
		);

		if ( ! (bool) get_option( self::AUTO_STUDY_OPT, false ) ) {
			$out['errors']['_'] = __( 'Daily auto-study is disabled.', 'hidden-word-bible-lessons' );
			update_option( self::AUTO_STUDY_STATUS_OPT, $out, false );
			return $out;
		}

		if ( ! class_exists( 'THW_Premium_Bible_Study_Card' ) ) {
			$out['errors']['_'] = __( 'Verse Study Card is not available on this site.', 'hidden-word-bible-lessons' );
			update_option( self::AUTO_STUDY_STATUS_OPT, $out, false );
			return $out;
		}

		if ( ! self::is_auto_study_enabled() ) {
			$out['errors']['_'] = __( 'Daily auto-study needs an AI provider configured.', 'hidden-word-bible-lessons' );
			update_option( self::AUTO_STUDY_STATUS_OPT, $out, false );
			return $out;
		}

		$translations = self::get_auto_study_translations();
		if ( empty( $translations ) ) {
			$out['errors']['_'] = __( 'No Bible translations selected for daily auto-study.', 'hidden-word-bible-lessons' );
			update_option( self::AUTO_STUDY_STATUS_OPT, $out, false );
			return $out;
		}

		$last = get_option( self::AUTO_STUDY_LAST_OPT, array() );
		if ( ! is_array( $last ) ) {
			$last = array();
		}
		if ( ! $force && isset( $last['day'] ) && (string) $last['day'] === $day && ! empty( $last['done'] ) ) {
			self::debug_log( 'Daily auto-study already completed for day', array( 'day' => $day ) );
			$cached = get_option( self::AUTO_STUDY_STATUS_OPT, array() );
			return is_array( $cached ) ? $cached : $out;
		}

		foreach ( $translations as $slug ) {
			$payload = self::get_payload_for_translation( $slug );
			if ( empty( $payload['reference'] ) || empty( $payload['text'] ) ) {
				$out['errors'][ $slug ] = __( 'Could not load verse text for this translation.', 'hidden-word-bible-lessons' );
				continue;
			}

			$book_id = (int) ( $payload['book_id'] ?? 0 );
			$chapter = (int) ( $payload['chapter'] ?? 0 );
			$verse   = (int) ( $payload['verse_start'] ?? 0 );
			if ( $book_id < 1 || $chapter < 1 || $verse < 1 ) {
				if ( class_exists( 'HWBL_Books' ) ) {
					$parsed = HWBL_Books::parse_reference( (string) $payload['reference'] );
					if ( is_array( $parsed ) ) {
						$book_id = (int) ( $parsed['book_id'] ?? 0 );
						$chapter = (int) ( $parsed['chapter'] ?? 0 );
						$verse   = (int) ( $parsed['verse'] ?? 0 );
					}
				}
			}
			if ( $book_id < 1 || $chapter < 1 || $verse < 1 ) {
				$out['errors'][ $slug ] = __( 'Could not resolve book/chapter/verse for this translation.', 'hidden-word-bible-lessons' );
				continue;
			}

			$result = THW_Premium_Bible_Study_Card::ensure_cached_card(
				$book_id,
				$chapter,
				$verse,
				$slug,
				''
			);
			if ( is_wp_error( $result ) ) {
				$out['errors'][ $slug ] = $result->get_error_message();
				continue;
			}

			$status = isset( $result['status'] ) ? (string) $result['status'] : '';
			if ( 'generated' === $status ) {
				$out['generated'][] = $slug;
			} elseif ( 'skipped' === $status ) {
				$out['skipped'][] = $slug;
			} else {
				$out['errors'][ $slug ] = __( 'Study card generation failed.', 'hidden-word-bible-lessons' );
			}
		}

		update_option(
			self::AUTO_STUDY_LAST_OPT,
			array(
				'day'  => $day,
				'done' => ( ! empty( $out['generated'] ) || ! empty( $out['skipped'] ) ),
				'at'   => current_time( 'mysql' ),
			),
			false
		);
		update_option( self::AUTO_STUDY_STATUS_OPT, $out, false );
		self::debug_log( 'Daily auto-study finished', $out );
		return $out;
	}

	/**
	 * Admin: run auto-study for today immediately.
	 */
	public static function handle_auto_study_now_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'hidden-word-bible-lessons' ) );
		}
		check_admin_referer( 'thw_votd_auto_study_now' );

		self::run_daily_auto_studies( true );

		$redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=hwbl_lesson&page=thw-premium-settings' );
		wp_safe_redirect( add_query_arg( 'thw_votd_auto_study_ran', '1', $redirect ) );
		exit;
	}

	/**
	 * Fetch and cache bible.com meta for a calendar day.
	 *
	 * @param string $day Y-m-d.
	 * @return array{reference:string,description_text:string,image:string}
	 */
	public static function get_bible_com_meta_for_day( $day ) {
		$day = (string) $day;
		$key = self::META_TRANSIENT . $day;
		$cached = get_transient( $key );
		if ( is_array( $cached ) && ! empty( $cached['reference'] ) ) {
			$page_date = isset( $cached['page_date'] ) ? (string) $cached['page_date'] : '';
			if ( '' === $page_date || $page_date === $day ) {
				self::debug_log(
					'Using cached bible.com/YouVersion meta',
					array(
						'day'       => $day,
						'reference' => (string) $cached['reference'],
					)
				);
				return $cached;
			}
			delete_transient( $key );
		}

		$remote = self::fetch_bible_com_meta();
		if ( empty( $remote['reference'] ) ) {
			self::debug_log( 'bible.com returned no reference; trying public YouVersion VOTD list', array( 'day' => $day ) );
			$remote = self::fetch_public_votd_list_meta( $day );
			if ( ! empty( $remote['reference'] ) ) {
				self::debug_log(
					'Public YouVersion VOTD list succeeded',
					array(
						'day'        => $day,
						'reference'  => (string) $remote['reference'],
						'passage_id' => isset( $remote['passage_id'] ) ? (string) $remote['passage_id'] : '',
					)
				);
			}
		}
		if ( empty( $remote['reference'] ) ) {
			self::debug_log( 'public VOTD list returned no reference; trying YouVersion API', array( 'day' => $day ) );
			$remote = self::fetch_youversion_votd_meta( $day );
			if ( ! empty( $remote['reference'] ) ) {
				self::debug_log(
					'YouVersion API meta fallback succeeded',
					array(
						'day'        => $day,
						'reference'  => (string) $remote['reference'],
						'passage_id' => isset( $remote['passage_id'] ) ? (string) $remote['passage_id'] : '',
					)
				);
			}
		}

		if ( empty( $remote['reference'] ) ) {
			self::debug_log( 'No VOTD meta from bible.com, public list, or YouVersion API', array( 'day' => $day ) );
			return $remote;
		}

		$page_date = isset( $remote['page_date'] ) ? (string) $remote['page_date'] : '';
		if ( '' !== $page_date && $page_date !== $day ) {
			self::debug_log(
				'bible.com page date does not match site calendar; meta not cached',
				array(
					'day'       => $day,
					'page_date' => $page_date,
					'reference' => (string) $remote['reference'],
				)
			);
			return $remote;
		}

		set_transient( $key, $remote, self::seconds_until_midnight() );
		self::debug_log(
			'Cached bible.com/YouVersion meta',
			array(
				'day'       => $day,
				'reference' => (string) $remote['reference'],
			)
		);
		return $remote;
	}

	/**
	 * Build today's payload from bible.com + local translation text.
	 *
	 * @param string $day         Y-m-d.
	 * @param string $translation Optional translation slug override.
	 * @return array<string, mixed>
	 */
	public static function build_payload_for_today( $day, $translation = '' ) {
		if ( 'curriculum' === self::get_votd_source() ) {
			self::debug_log( 'Building payload from curriculum source', array( 'day' => $day ) );
			return self::build_payload_from_curriculum( $day, $translation );
		}

		$payload = self::build_payload_from_bible_com( $day, $translation );
		if ( empty( $payload['reference'] ) || empty( $payload['text'] ) ) {
			self::debug_log(
				'bible.com payload incomplete; trying curriculum fallback',
				array(
					'day'       => $day,
					'reference' => isset( $payload['reference'] ) ? (string) $payload['reference'] : '',
					'has_text'  => ! empty( $payload['text'] ),
				)
			);
			$fallback = self::build_payload_from_curriculum( $day, $translation );
			if ( ! empty( $fallback['reference'] ) && ! empty( $fallback['text'] ) ) {
				self::debug_log(
					'Curriculum fallback succeeded',
					array(
						'day'       => $day,
						'reference' => (string) $fallback['reference'],
					)
				);
				return $fallback;
			}
			self::debug_log( 'Curriculum fallback also empty', array( 'day' => $day ) );
		}

		return $payload;
	}

	/**
	 * Build today's payload from the site curriculum schedule.
	 *
	 * @param string $day         Y-m-d.
	 * @param string $translation Optional translation slug override.
	 * @return array<string, mixed>
	 */
	public static function build_payload_from_curriculum( $day, $translation = '' ) {
		$empty = array(
			'reference'          => '',
			'text'               => '',
			'image'              => '',
			'translation'        => '',
			'translation_label'  => '',
			'book_id'            => 0,
			'chapter'            => 0,
			'verse_start'        => 0,
			'verse_end'          => 0,
			'day'                => (string) $day,
			'source_date'        => (string) $day,
			'votd_source'        => 'curriculum',
		);

		if ( ! class_exists( 'HWBL_Scheduler' ) || ! class_exists( 'HWBL_CPT_Lesson' ) ) {
			return $empty;
		}

		$lesson_id = (int) HWBL_Scheduler::get_current_lesson_id();
		if ( $lesson_id < 1 ) {
			return $empty;
		}

		$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		if ( empty( $lesson['book_id'] ) || empty( $lesson['chapter'] ) || empty( $lesson['verse_start'] ) ) {
			return $empty;
		}

		$parsed = array(
			'book_id'     => (int) $lesson['book_id'],
			'chapter'     => (int) $lesson['chapter'],
			'verse_start' => (int) $lesson['verse_start'],
			'verse_end'   => (int) ( ! empty( $lesson['verse_end'] ) ? $lesson['verse_end'] : $lesson['verse_start'] ),
		);

		$translation = sanitize_key( (string) $translation );
		if ( '' === $translation ) {
			$translation = sanitize_key( (string) get_option( self::TRANS_OPT, '' ) );
		}
		if ( '' === $translation ) {
			$translation = sanitize_key( (string) get_option( 'hwbl_active_translation', 'niv' ) );
		}

		$text  = self::resolve_verse_text( $parsed, $translation );
		$label = '';
		if ( class_exists( 'HWBL_Translation_Service' ) ) {
			$svc   = HWBL_Translation_Service::instance();
			$label = method_exists( $svc, 'get_translation_label' ) ? (string) $svc->get_translation_label( $translation ) : strtoupper( $translation );
		}

		return array(
			'reference'         => HWBL_Books::format_reference(
				$parsed['book_id'],
				$parsed['chapter'],
				$parsed['verse_start'],
				$parsed['verse_end']
			),
			'text'              => $text,
			'image'             => '',
			'translation'       => $translation,
			'translation_label' => $label,
			'book_id'           => $parsed['book_id'],
			'chapter'           => $parsed['chapter'],
			'verse_start'       => $parsed['verse_start'],
			'verse_end'         => $parsed['verse_end'],
			'day'               => (string) $day,
			'source_date'       => (string) $day,
			'votd_source'       => 'curriculum',
		);
	}

	/**
	 * Build today's payload from bible.com + local translation text.
	 *
	 * @param string $day         Y-m-d.
	 * @param string $translation Optional translation slug override.
	 * @return array<string, mixed>
	 */
	public static function build_payload_from_bible_com( $day, $translation = '' ) {
		$empty = array(
			'reference'          => '',
			'text'               => '',
			'image'              => '',
			'translation'        => '',
			'translation_label'  => '',
			'book_id'            => 0,
			'chapter'            => 0,
			'verse_start'        => 0,
			'verse_end'          => 0,
			'day'                => (string) $day,
			'source_date'        => '',
			'votd_source'        => 'bible_com',
		);

		$remote = self::get_bible_com_meta_for_day( (string) $day );
		if ( empty( $remote['reference'] ) ) {
			self::debug_log( 'build_payload_from_bible_com: no remote reference', array( 'day' => $day ) );
			return $empty;
		}

		if ( empty( $remote['image'] ) && ! empty( $remote['passage_id'] ) ) {
			$remote['image'] = self::resolve_votd_image_url( (string) $remote['passage_id'] );
		}

		$source_date = isset( $remote['page_date'] ) ? (string) $remote['page_date'] : '';
		if ( '' !== $source_date && $source_date !== (string) $day ) {
			self::debug_log(
				'build_payload_from_bible_com: source date mismatch',
				array(
					'day'         => $day,
					'source_date' => $source_date,
					'reference'   => (string) $remote['reference'],
				)
			);
			return $empty;
		}

		$parsed = self::parse_reference( $remote['reference'] );
		if ( empty( $parsed['book_id'] ) ) {
			// Fall back to og:description text when book parse fails.
			$text = ! empty( $remote['description_text'] ) ? $remote['description_text'] : '';
			if ( class_exists( 'HWBL_Http_Utils' ) ) {
				$text = HWBL_Http_Utils::sanitize_bible_text( $text );
			}
			if ( '' === $text ) {
				return $empty;
			}
			return array_merge(
				$empty,
				array(
					'reference'   => $remote['reference'],
					'text'        => $text,
					'image'       => $remote['image'],
					'source_date' => $source_date ? $source_date : (string) $day,
				)
			);
		}

		$translation = sanitize_key( (string) $translation );
		if ( '' === $translation ) {
			$translation = sanitize_key( (string) get_option( self::TRANS_OPT, '' ) );
		}
		if ( '' === $translation ) {
			$translation = sanitize_key( (string) get_option( 'hwbl_active_translation', 'niv' ) );
		}

		$text  = self::resolve_verse_text( $parsed, $translation );
		$label = '';
		if ( class_exists( 'HWBL_Translation_Service' ) ) {
			$svc   = HWBL_Translation_Service::instance();
			$label = method_exists( $svc, 'get_translation_label' ) ? (string) $svc->get_translation_label( $translation ) : strtoupper( $translation );
		}

		if ( '' === $text && ! empty( $remote['description_text'] ) ) {
			$text = $remote['description_text'];
			// Keep the requested translation’s label when known; Bible.com is only a last-resort source tag.
			if ( '' === $label ) {
				$label = __( 'Bible.com', 'hidden-word-bible-lessons' );
			}
		}

		if ( '' === $text && ! empty( $remote['passage_id'] ) ) {
			$text = self::fetch_youversion_passage_text( (string) $remote['passage_id'], $translation );
			if ( '' !== $text ) {
				$label = class_exists( 'HWBL_Translation_Service' )
					? HWBL_Translation_Service::instance()->get_translation_label( $translation )
					: strtoupper( $translation );
			}
		}

		if ( class_exists( 'HWBL_Http_Utils' ) ) {
			$text = HWBL_Http_Utils::sanitize_bible_text( $text );
		}

		if ( '' === $text ) {
			self::debug_log(
				'build_payload_from_bible_com: no verse text resolved',
				array(
					'day'         => $day,
					'reference'   => (string) $remote['reference'],
					'translation' => $translation,
					'passage_id'  => isset( $remote['passage_id'] ) ? (string) $remote['passage_id'] : '',
				)
			);
			return $empty;
		}

		self::debug_log(
			'build_payload_from_bible_com: success',
			array(
				'day'         => $day,
				'reference'   => (string) $remote['reference'],
				'translation' => $translation,
				'via'         => ! empty( $remote['passage_id'] ) ? 'youversion_api' : 'bible_com',
			)
		);

		return array(
			'reference'         => HWBL_Books::format_reference(
				(int) $parsed['book_id'],
				(int) $parsed['chapter'],
				(int) $parsed['verse_start'],
				(int) $parsed['verse_end']
			),
			'text'              => $text,
			'image'             => $remote['image'],
			'translation'       => $translation,
			'translation_label' => $label,
			'book_id'           => (int) $parsed['book_id'],
			'chapter'           => (int) $parsed['chapter'],
			'verse_start'       => (int) $parsed['verse_start'],
			'verse_end'         => (int) $parsed['verse_end'],
			'day'               => (string) $day,
			'source_date'       => $source_date ? $source_date : (string) $day,
			'votd_source'       => 'bible_com',
		);
	}

	/**
	 * Find or optionally generate a saved VOTD explanation for email / cron.
	 *
	 * @param array<string, mixed> $payload        VOTD payload.
	 * @param bool                 $allow_generate Whether to call AI when no saved post exists.
	 * @return array{html:string,post_url:string,generated:bool}
	 */
	public static function ensure_explanation( $payload, $allow_generate = false ) {
		$out = array(
			'html'      => '',
			'post_url'  => '',
			'generated' => false,
		);

		if ( empty( $payload['reference'] ) || empty( $payload['text'] ) || ! class_exists( 'THW_Premium_Votd_Explain_Store' ) ) {
			return $out;
		}

		$saved = THW_Premium_Votd_Explain_Store::find_saved_post( $payload );
		if ( $saved instanceof WP_Post ) {
			$url             = THW_Premium_Votd_Explain_Store::get_public_url( $saved );
			$out['html']     = THW_Premium_Votd_Explain_Store::get_explanation_html( $saved );
			$out['post_url'] = $url ? $url : '';
			return $out;
		}

		if ( ! $allow_generate || ! self::is_ai_explain_enabled() ) {
			return $out;
		}
		if ( ! function_exists( 'thw_premium_ai_frontend_available' ) || ! thw_premium_ai_frontend_available() ) {
			return $out;
		}

		$rules              = thw_premium_votd_neutral_explain_rules();
		$checklist          = $rules;
		$prompt             = self::build_explain_prompt( $payload );
		$system_instruction = thw_premium_build_ai_system_instruction( $rules );
		$result             = THW_Premium_AI_Client::generate_text( $prompt, $system_instruction );
		if ( is_wp_error( $result ) ) {
			return $out;
		}

		$flagged = false;
		if ( function_exists( 'thw_premium_ai_compliance_check_enabled' ) && thw_premium_ai_compliance_check_enabled() ) {
			$check = THW_Premium_AI_Client::check_compliance( $checklist, wp_strip_all_tags( $result ) );
			if ( ! $check['compliant'] ) {
				$flagged      = true;
				$retry_system = $system_instruction
					. "\n\nYour previous answer was flagged for this specific issue: {$check['reason']} Revise your answer so it fully complies with the Rules above.";
				$retry        = THW_Premium_AI_Client::generate_text( $prompt, $retry_system );
				if ( ! is_wp_error( $retry ) ) {
					$result  = $retry;
					$check2  = THW_Premium_AI_Client::check_compliance( $checklist, wp_strip_all_tags( $result ) );
					$flagged = ! $check2['compliant'];
				}
				if ( $flagged && 'block' === thw_premium_get_ai_compliance_failure_action() ) {
					return $out;
				}
			}
		}

		$html = THW_Premium_AI_Client::format_html_response( $result );
		$post = THW_Premium_Votd_Explain_Store::save_post( $payload, $html, $flagged );
		$url  = ( $post instanceof WP_Post ) ? THW_Premium_Votd_Explain_Store::get_public_url( $post ) : '';

		$out['html']      = $html;
		$out['post_url']  = $url ? $url : '';
		$out['generated'] = true;
		return $out;
	}

	/**
	 * Fetch bible.com Open Graph meta for today's VOTD.
	 *
	 * @return array{reference:string,description_text:string,image:string}
	 */
	public static function fetch_bible_com_meta() {
		return THW_Premium_Votd_YouVersion::fetch_bible_com_meta();
	}

	/**
	 * Day of year (1–366) for a calendar date in the site timezone.
	 *
	 * @param string $day Y-m-d.
	 * @return int
	 */
	public static function get_day_of_year( $day ) {
		return THW_Premium_Votd_YouVersion::get_day_of_year( $day );
	}

	/**
	 * Fetch Verse of the Day metadata from YouVersion Platform when bible.com is unreachable.
	 *
	 * @param string $day Y-m-d calendar day.
	 * @return array{reference:string,description_text:string,image:string,page_date:string,passage_id:string}
	 */
	public static function fetch_youversion_votd_meta( $day ) {
		return THW_Premium_Votd_YouVersion::fetch_youversion_votd_meta( $day );
	}

	/**
	 * Fetch VOTD metadata from the public YouVersion day list.
	 *
	 * @param string $day Y-m-d calendar day.
	 * @return array{reference:string,description_text:string,image:string,page_date:string,passage_id:string}
	 */
	public static function fetch_public_votd_list_meta( $day ) {
		return THW_Premium_Votd_YouVersion::fetch_public_votd_list_meta( $day );
	}

	/**
	 * Resolve a shareable VOTD image URL for a passage ID.
	 *
	 * @param string $passage_id YouVersion USFM passage ID.
	 * @return string
	 */
	public static function resolve_votd_image_url( $passage_id ) {
		return THW_Premium_Votd_YouVersion::resolve_votd_image_url( $passage_id );
	}

	/**
	 * Fetch bible.com VOTD HTML for image/reference parsing.
	 *
	 * @return string
	 */
	public static function fetch_bible_com_page_html() {
		return THW_Premium_Votd_YouVersion::fetch_bible_com_page_html();
	}

	/**
	 * Normalize a YouVersion/Bible.com image URL for front-end use.
	 *
	 * @param string $url Raw image URL.
	 * @return string
	 */
	public static function normalize_votd_image_url( $url ) {
		return THW_Premium_Votd_YouVersion::normalize_votd_image_url( $url );
	}

	/**
	 * Parse bible.com __NEXT_DATA__ page props JSON.
	 *
	 * @param string $html bible.com HTML.
	 * @return array<string, mixed>|null
	 */
	public static function parse_bible_com_next_data( $html ) {
		return THW_Premium_Votd_YouVersion::parse_bible_com_next_data( $html );
	}

	/**
	 * Extract a VOTD image URL from bible.com page props.
	 *
	 * @param array<string, mixed>|null $page_props bible.com pageProps.
	 * @param string                    $passage_id Expected USFM passage ID.
	 * @return string
	 */
	public static function extract_votd_image_from_page_props( $page_props, $passage_id = '' ) {
		return THW_Premium_Votd_YouVersion::extract_votd_image_from_page_props( $page_props, $passage_id );
	}

	/**
	 * Pick the closest image rendition from a bible.com image object.
	 *
	 * @param array<string, mixed> $image Image object.
	 * @param int                  $width Preferred width.
	 * @return string
	 */
	public static function pick_votd_image_rendition( $image, $width = 640 ) {
		return THW_Premium_Votd_YouVersion::pick_votd_image_rendition( $image, $width );
	}

	/**
	 * Load passage text from YouVersion for the configured translation.
	 *
	 * @param string $passage_id  YouVersion passage ID.
	 * @param string $translation Site translation slug.
	 * @return string
	 */
	public static function fetch_youversion_passage_text( $passage_id, $translation = '' ) {
		return THW_Premium_Votd_YouVersion::fetch_youversion_passage_text( $passage_id, $translation );
	}

	/**
	 * Parse bible.com HTML for reference, description text, and image.
	 *
	 * @param string $html HTML body.
	 * @return array{reference:string,description_text:string,image:string}
	 */
	public static function parse_bible_com_html( $html ) {
		return THW_Premium_Votd_YouVersion::parse_bible_com_html( $html );
	}

	/**
	 * Parse a human scripture reference into book/chapter/verses.
	 *
	 * @param string $reference e.g. "Colossians 3:12" or "1 John 4:7-8".
	 * @return array{book_id:int,chapter:int,verse_start:int,verse_end:int}
	 */
	public static function parse_reference( $reference ) {
		return THW_Premium_Votd_YouVersion::parse_reference( $reference );
	}

	/**
	 * Resolve verse text via free/Premium translation stack.
	 *
	 * @param array<string, int> $parsed      Parsed reference.
	 * @param string             $translation Translation slug.
	 * @return string
	 */
	private static function resolve_verse_text( $parsed, $translation ) {
		if ( ! class_exists( 'HWBL_Translation_Service' ) ) {
			return '';
		}

		$svc   = HWBL_Translation_Service::instance();
		$parts = array();
		$start = (int) $parsed['verse_start'];
		$end   = max( $start, (int) $parsed['verse_end'] );

		for ( $v = $start; $v <= $end; $v++ ) {
			$text = '';
			if ( method_exists( $svc, 'get_echo_verse_text' ) ) {
				$row  = $svc->get_echo_verse_text( (int) $parsed['book_id'], (int) $parsed['chapter'], $v, $translation );
				$text = is_array( $row ) && ! empty( $row['text'] ) ? (string) $row['text'] : '';
			}
			if ( '' === $text ) {
				$text = (string) $svc->get_verse_text( (int) $parsed['book_id'], (int) $parsed['chapter'], $v, $translation );
			}
			if ( '' !== $text ) {
				$parts[] = $text;
			}
		}

		return HWBL_Http_Utils::sanitize_bible_text( trim( implode( ' ', $parts ) ) );
	}

	/**
	 * REST: today's VOTD payload for a Bible translation.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_payload( $request ) {
		return THW_Premium_Votd_Rest::rest_payload( $request );
	}

	/**
	 * REST: AI explanation for today's VOTD (reuses a saved post when available).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_explain( $request ) {
		return THW_Premium_Votd_Rest::rest_explain( $request );
	}

	/**
	 * Build the AI explain prompt for today's VOTD.
	 *
	 * @param array<string, mixed> $payload VOTD payload.
	 * @return string
	 */
	public static function build_explain_prompt( $payload ) {
		$reference   = isset( $payload['reference'] ) ? (string) $payload['reference'] : '';
		$text        = isset( $payload['text'] ) ? (string) $payload['text'] : '';
		$translation = isset( $payload['translation'] ) ? sanitize_key( (string) $payload['translation'] ) : '';
		$may_embed   = function_exists( 'thw_premium_ai_may_embed_scripture_text' )
			? thw_premium_ai_may_embed_scripture_text( $translation )
			: ( 'niv' !== $translation );
		$follow_on   = $may_embed ? self::get_follow_on_verse_text( $payload, 3 ) : '';

		$prompt  = "You are explaining today's Bible.com Verse of the Day for a small-group Bible study participant.\n";
		$prompt .= "Write a clear, pastoral explanation in HTML using <h3> section headings and <p> paragraphs only.\n";
		$prompt .= "Return raw HTML only — do not wrap it in markdown code fences (no ``` or ```html).\n";
		if ( $may_embed ) {
			$prompt .= "Do not invent exact Bible quotations beyond the verse text supplied below. You may summarize surrounding context in your own words.\n";
		} else {
			$prompt .= "Scripture wording is not included (licensed translation). Do not invent or reproduce copyrighted quotations; explain from the reference only. The reader sees the official text in the UI. You may summarize surrounding context in your own words.\n";
		}
		$prompt .= "Keep the tone warm, humble, and practical—not academic jargon.\n\n";
		$prompt .= "Cover these sections in order:\n";
		$prompt .= "1) <h3>Historical lead-up</h3> — What was happening in the biblical story or setting that leads into this verse?\n";
		$prompt .= "2) <h3>Why this verse was written</h3> — What occasion, audience, or purpose led the human author (under God’s inspiration) to write these words?\n";
		$prompt .= "3) <h3>True meaning</h3> — Explain the verse’s meaning in context: key words, who is speaking/being addressed, and what the text is actually saying.\n";
		$prompt .= "4) <h3>Living it today</h3> — Concrete ways this verse can shape daily life, relationships, prayer, and obedience.\n";
		$prompt .= "5) <h3>What follows</h3> — ";
		if ( $may_embed ) {
			$prompt .= 'If follow-on verses are provided below, explain what they add and how that continuing thought applies today. ';
			$prompt .= "If no follow-on text is provided, briefly note the natural next thought in the passage without inventing quotations, or omit this section if it does not help.\n\n";
		} else {
			$prompt .= "Briefly note the natural next thought in the passage without inventing copyrighted quotations, or omit this section if it does not help.\n\n";
		}
		$prompt .= 'Reference: ' . $reference . "\n";
		if ( '' !== $translation ) {
			$prompt .= 'Translation: ' . strtoupper( $translation ) . "\n";
		}
		$prompt .= "\n";

		if ( $may_embed ) {
			$prompt .= "Verse text:\n" . $text . "\n";
			if ( '' !== $follow_on ) {
				$prompt .= "\nFollow-on verses (for section 5; quote or paraphrase carefully from this text only):\n" . $follow_on . "\n";
			}
		}

		return $prompt;
	}

	/**
	 * Fetch a few verses after the VOTD for follow-on context.
	 *
	 * @param array<string, mixed> $payload VOTD payload.
	 * @param int                  $count   How many following verses to include.
	 * @return string
	 */
	public static function get_follow_on_verse_text( $payload, $count = 3 ) {
		$count    = max( 1, min( 5, (int) $count ) );
		$book_id  = isset( $payload['book_id'] ) ? (int) $payload['book_id'] : 0;
		$chapter  = isset( $payload['chapter'] ) ? (int) $payload['chapter'] : 0;
		$verse_end = isset( $payload['verse_end'] ) ? (int) $payload['verse_end'] : 0;
		if ( $verse_end < 1 && isset( $payload['verse_start'] ) ) {
			$verse_end = (int) $payload['verse_start'];
		}

		if ( $book_id < 1 || $chapter < 1 || $verse_end < 1 || ! class_exists( 'HWBL_Translation_Service' ) ) {
			return '';
		}

		$translation = isset( $payload['translation'] ) ? sanitize_key( (string) $payload['translation'] ) : '';
		if ( '' === $translation ) {
			$translation = sanitize_key( (string) get_option( self::TRANS_OPT, '' ) );
		}
		if ( '' === $translation ) {
			$translation = sanitize_key( (string) get_option( 'hwbl_active_translation', 'niv' ) );
		}

		$svc   = HWBL_Translation_Service::instance();
		$lines = array();
		for ( $v = $verse_end + 1; $v <= $verse_end + $count; $v++ ) {
			$text = '';
			if ( method_exists( $svc, 'get_echo_verse_text' ) ) {
				$row  = $svc->get_echo_verse_text( $book_id, $chapter, $v, $translation );
				$text = is_array( $row ) && ! empty( $row['text'] ) ? (string) $row['text'] : '';
			}
			if ( '' === $text ) {
				$text = (string) $svc->get_verse_text( $book_id, $chapter, $v, $translation );
			}
			if ( '' === $text ) {
				break;
			}
			$ref = class_exists( 'HWBL_Books' )
				? HWBL_Books::format_reference( $book_id, $chapter, $v, $v )
				: ( $chapter . ':' . $v );
			$lines[] = $ref . ' — ' . $text;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Enqueue assets when shortcode rendered.
	 */
	public static function enqueue_late_assets() {
		if ( ! self::$assets_needed ) {
			return;
		}

		wp_enqueue_style( 'thw-premium' );

		$votd_deps = array( 'hwbl-journal-export-menu' );
		if ( wp_script_is( 'hwbl-user-preferences', 'registered' ) ) {
			$votd_deps[] = 'hwbl-user-preferences';
		}
		wp_enqueue_script(
			'thw-votd',
			THW_PREMIUM_URL . 'public/js/votd.js',
			$votd_deps,
			THW_PREMIUM_VERSION,
			true
		);

		wp_localize_script(
			'thw-votd',
			'thwVotd',
			array(
				'restUrl'               => esc_url_raw( rest_url( 'thw/v1/votd-explain' ) ),
				'payloadUrl'            => esc_url_raw( rest_url( 'thw/v1/votd' ) ),
				'authUrl'               => esc_url_raw( admin_url( 'admin-ajax.php?action=thw_votd_auth' ) ),
				'nonce'                 => wp_create_nonce( 'wp_rest' ),
				'loading'               => __( 'Preparing explanation…', 'hidden-word-bible-lessons' ),
				'error'                 => __( 'Could not generate an explanation. Please try again.', 'hidden-word-bible-lessons' ),
				'loginRequired'         => __( 'Log in to generate the first AI explanation for this Bible version. Once saved, everyone can read it.', 'hidden-word-bible-lessons' ),
				'sessionError'          => __( 'Your login session could not be verified. Refresh the page and try again.', 'hidden-word-bible-lessons' ),
				'rateLimit'             => __( 'Hourly AI explanation limit reached.', 'hidden-word-bible-lessons' ),
				'complianceFlagged'     => __( 'This explanation may need careful review. Compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
				'openFullPage'          => __( 'Open full explanation page', 'hidden-word-bible-lessons' ),
				'translationStorageKey' => class_exists( 'HWBL_User_Preferences' )
					? HWBL_User_Preferences::STORAGE_TRANSLATION
					: 'hwbl_preferred_translation',
				'defaultTranslation'    => self::get_display_translation(),
				'siteToday'             => wp_date( 'Y-m-d' ),
				'loggedIn'              => is_user_logged_in(),
				'canGenerate'           => self::is_ai_available(),
				'aiReason'              => ( is_user_logged_in() && ! self::is_ai_available() )
					? self::get_ai_unavailable_reason()
					: '',
				'delivery'              => self::get_explain_delivery(),
			)
		);
	}

}
