<?php
/**
 * Premium settings page.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
require_once dirname( __FILE__ ) . '/class-premium-settings-app.php';

/**
 * Class THW_Premium_Settings
 */
class THW_Premium_Settings {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		THW_Premium_Bible_Api_Diagnostics::init();
	}

	/**
	 * Add premium settings submenu.
	 */
	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=hwbl_lesson',
			( defined( 'HWBL_INTEGRATED_PREMIUM' ) && HWBL_INTEGRATED_PREMIUM )
				? __( 'Advanced Settings', 'hidden-word-bible-lessons' )
				: __( 'Premium Settings', 'hidden-word-bible-lessons' ),
			( defined( 'HWBL_INTEGRATED_PREMIUM' ) && HWBL_INTEGRATED_PREMIUM )
				? __( 'Advanced', 'hidden-word-bible-lessons' )
				: __( 'Premium', 'hidden-word-bible-lessons' ),
			'manage_options',
			'thw-premium-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Main Premium settings form option group.
	 */
	const GROUP_MAIN = 'thw_premium_settings';

	/**
	 * Lesson email digest form option group.
	 */
	const GROUP_DIGEST = 'thw_premium_digest_settings';

	/**
	 * Verse of the Day email form option group.
	 */
	const GROUP_VOTD_DIGEST = 'thw_premium_votd_digest_settings';

	/**
	 * Map option keys to their Settings API group (one group per admin form).
	 *
	 * Separate groups prevent saving one form from blanking options registered
	 * on another form that share a single options.php option_page.
	 *
	 * @return array<string, string> option_key => group
	 */
	public static function get_option_group_map() {
		$main = array(
			'thw_api_bible_key',
			'thw_biblia_api_key',
			'thw_youversion_app_key',
			'thw_openai_api_key',
			'thw_claude_api_key',
			'thw_manual_lesson_id',
			'thw_ai_explain_rules',
			'thw_ai_explain_rules_preset',
			'thw_ai_study_rules',
			'thw_ai_study_audience',
			'thw_ai_study_result_count',
			'thw_ai_study_include_lessons',
			'thw_ai_ask_audience',
			'thw_ai_ask_rules',
			'thw_ai_ask_include_lessons',
			'thw_ai_church_subject_rules',
			'thw_ai_allow_user_tradition',
			'thw_ai_enabled_traditions',
			'thw_ai_compliance_check_enabled',
			'thw_ai_compliance_failure_action',
			'thw_votd_ai_explain',
			'thw_votd_show_image',
			'thw_votd_explain_featured_image',
			'thw_votd_source',
			'thw_votd_translation',
			'thw_votd_explain_delivery',
			'thw_votd_auto_explain_enabled',
			'thw_votd_auto_explain_translations',
			'thw_votd_auto_study_enabled',
			'thw_votd_auto_study_translations',
		);

		$digest = array(
			'thw_digest_enabled',
			'thw_digest_subject',
			'thw_digest_from_name',
		);

		$votd_digest = array(
			'thw_votd_digest_enabled',
			'thw_votd_digest_subject',
			'thw_votd_digest_from_name',
			'thw_votd_digest_include_explain',
			'thw_votd_digest_generate_explain',
			'thw_votd_digest_include_image',
			'thw_votd_digest_allow_tradition',
			'thw_votd_digest_tradition',
		);

		$map = array();
		foreach ( $main as $key ) {
			$map[ $key ] = self::GROUP_MAIN;
		}
		foreach ( $digest as $key ) {
			$map[ $key ] = self::GROUP_DIGEST;
		}
		foreach ( $votd_digest as $key ) {
			$map[ $key ] = self::GROUP_VOTD_DIGEST;
		}

		return $map;
	}

	/**
	 * Register premium settings.
	 */
	public static function register_settings() {
		$sanitizers = array(
			'thw_api_bible_key'                   => array( __CLASS__, 'sanitize_api_key' ),
			'thw_biblia_api_key'                  => array( __CLASS__, 'sanitize_api_key' ),
			'thw_youversion_app_key'              => array( __CLASS__, 'sanitize_api_key' ),
			'thw_openai_api_key'                  => array( __CLASS__, 'sanitize_api_key' ),
			'thw_claude_api_key'                  => array( __CLASS__, 'sanitize_api_key' ),
			'thw_manual_lesson_id'                => 'absint',
			'thw_digest_enabled'                  => 'rest_sanitize_boolean',
			'thw_digest_subject'                  => 'sanitize_text_field',
			'thw_digest_from_name'                => 'sanitize_text_field',
			'thw_votd_digest_enabled'             => 'rest_sanitize_boolean',
			'thw_votd_digest_subject'             => 'sanitize_text_field',
			'thw_votd_digest_from_name'           => 'sanitize_text_field',
			'thw_votd_digest_include_explain'     => 'rest_sanitize_boolean',
			'thw_votd_digest_generate_explain'    => 'rest_sanitize_boolean',
			'thw_votd_digest_include_image'       => 'rest_sanitize_boolean',
			'thw_votd_digest_allow_tradition'     => 'rest_sanitize_boolean',
			'thw_votd_digest_tradition'           => 'sanitize_key',
			'thw_ai_explain_rules'                => array( __CLASS__, 'sanitize_ai_rules' ),
			'thw_ai_explain_rules_preset'         => array( __CLASS__, 'sanitize_explain_rules_preset' ),
			'thw_ai_study_rules'                  => array( __CLASS__, 'sanitize_ai_rules' ),
			'thw_ai_study_audience'               => array( __CLASS__, 'sanitize_study_audience' ),
			'thw_ai_study_result_count'           => array( __CLASS__, 'sanitize_study_result_count' ),
			'thw_ai_study_include_lessons'        => 'rest_sanitize_boolean',
			'thw_ai_ask_audience'                 => array( __CLASS__, 'sanitize_ask_audience' ),
			'thw_ai_ask_rules'                    => array( __CLASS__, 'sanitize_ai_rules' ),
			'thw_ai_ask_include_lessons'          => 'rest_sanitize_boolean',
			'thw_ai_church_subject_rules'         => array( 'THW_Premium_Church_Subject_Rules', 'sanitize_rules' ),
			'thw_ai_allow_user_tradition'         => 'rest_sanitize_boolean',
			'thw_ai_enabled_traditions'           => array( __CLASS__, 'sanitize_enabled_traditions' ),
			'thw_ai_compliance_check_enabled'     => 'rest_sanitize_boolean',
			'thw_ai_compliance_failure_action'    => array( __CLASS__, 'sanitize_compliance_failure_action' ),
			'thw_votd_ai_explain'                 => 'rest_sanitize_boolean',
			'thw_votd_show_image'                 => 'rest_sanitize_boolean',
			'thw_votd_explain_featured_image'     => 'rest_sanitize_boolean',
			'thw_votd_source'                     => array( __CLASS__, 'sanitize_votd_source' ),
			'thw_votd_translation'                => 'sanitize_key',
			'thw_votd_explain_delivery'           => array( __CLASS__, 'sanitize_votd_explain_delivery' ),
			'thw_votd_auto_explain_enabled'       => 'rest_sanitize_boolean',
			'thw_votd_auto_explain_translations'  => array( __CLASS__, 'sanitize_votd_auto_explain_translations' ),
			'thw_votd_auto_study_enabled'         => 'rest_sanitize_boolean',
			'thw_votd_auto_study_translations'    => array( __CLASS__, 'sanitize_votd_auto_study_translations' ),
		);

		$groups = self::get_option_group_map();
		$bools  = array(
			'thw_digest_enabled',
			'thw_ai_allow_user_tradition',
			'thw_ai_compliance_check_enabled',
			'thw_ai_study_include_lessons',
			'thw_ai_ask_include_lessons',
			'thw_votd_ai_explain',
			'thw_votd_show_image',
			'thw_votd_explain_featured_image',
			'thw_votd_auto_explain_enabled',
			'thw_votd_auto_study_enabled',
			'thw_votd_digest_enabled',
			'thw_votd_digest_include_explain',
			'thw_votd_digest_generate_explain',
			'thw_votd_digest_include_image',
			'thw_votd_digest_allow_tradition',
		);

		foreach ( $sanitizers as $key => $sanitize ) {
			$type = 'thw_ai_study_result_count' === $key ? 'integer' : 'string';
			if ( in_array( $key, $bools, true ) ) {
				$type = 'boolean';
			}
			if ( in_array( $key, array( 'thw_ai_church_subject_rules', 'thw_ai_enabled_traditions', 'thw_votd_auto_explain_translations', 'thw_votd_auto_study_translations' ), true ) ) {
				$type = 'array';
			}
			$group = isset( $groups[ $key ] ) ? $groups[ $key ] : self::GROUP_MAIN;
			register_setting(
				$group,
				$key,
				array(
					'type'              => $type,
					'sanitize_callback' => $sanitize,
				)
			);
		}
	}

	/**
	 * Enqueue admin assets for Premium settings.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'hwbl_lesson_page_thw-premium-settings' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'thw-explain-rules-presets',
			THW_PREMIUM_URL . 'admin/js/explain-rules-presets.js',
			array(),
			THW_PREMIUM_VERSION,
			true
		);
		wp_enqueue_script(
			'thw-church-subject-rules',
			THW_PREMIUM_URL . 'admin/js/church-subject-rules.js',
			array(),
			THW_PREMIUM_VERSION,
			true
		);
		wp_enqueue_style(
			'hwbl-admin-bible-api-test',
			HWBL_PLUGIN_URL . 'assets/css/hwbl-admin-bible-api-test.css',
			array(),
			defined( 'HWBL_VERSION' ) ? HWBL_VERSION : THW_PREMIUM_VERSION
		);
		wp_enqueue_script(
			'thw-bible-api-test',
			THW_PREMIUM_URL . 'admin/js/bible-api-test.js',
			array(),
			THW_PREMIUM_VERSION,
			true
		);
		wp_localize_script(
			'thw-bible-api-test',
			'thwBibleApiTest',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'thw_test_bible_api' ),
				'i18n'    => array(
					'testing'          => __( 'Testing API key…', 'hidden-word-bible-lessons' ),
					'diagnosing'       => __( 'Tracing Bible reader providers…', 'hidden-word-bible-lessons' ),
					'testFailed'       => __( 'Test failed.', 'hidden-word-bible-lessons' ),
					'requestFailed'    => __( 'Request failed.', 'hidden-word-bible-lessons' ),
					'diagnosisFailed'  => __( 'Diagnosis failed.', 'hidden-word-bible-lessons' ),
				),
			)
		);

		wp_enqueue_script(
			'hwbl-qrcode-generator',
			HWBL_PLUGIN_URL . 'assets/js/qrcode-generator.min.js',
			array(),
			defined( 'HWBL_VERSION' ) ? HWBL_VERSION : THW_PREMIUM_VERSION,
			true
		);
		wp_enqueue_script(
			'hwbl-admin-qr',
			HWBL_PLUGIN_URL . 'assets/js/hwbl-admin-qr.js',
			array( 'hwbl-qrcode-generator' ),
			defined( 'HWBL_VERSION' ) ? HWBL_VERSION : THW_PREMIUM_VERSION,
			true
		);
	}

	/**
	 * Sanitize AI rules textarea.
	 *
	 * @param string $value Input value.
	 * @return string
	 */
	public static function sanitize_ai_rules( $value ) {
		return sanitize_textarea_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Sanitize BYOK API keys; preserve existing value when the password field is left blank.
	 *
	 * @param string $value       Submitted key.
	 * @param string $option_name Option name being saved.
	 * @return string
	 */
	public static function sanitize_api_key( $value, $option_name = '' ) {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );
		if ( '' !== $value ) {
			return $value;
		}

		if ( is_string( $option_name ) && '' !== $option_name ) {
			return sanitize_text_field( (string) get_option( $option_name, '' ) );
		}

		return '';
	}

	/**
	 * Sanitize explain-rules preset slug.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function sanitize_explain_rules_preset( $value ) {
		$slug    = sanitize_key( (string) $value );
		$presets = thw_premium_ai_explain_rule_presets();

		if ( 'custom' === $slug || isset( $presets[ $slug ] ) ) {
			return $slug;
		}

		return 'custom';
	}

	/**
	 * Sanitize enabled traditions; missing checkbox group means allow all.
	 *
	 * @param mixed $value Input value.
	 * @return array
	 */
	public static function sanitize_enabled_traditions( $value ) {
		// When every box is unchecked, the field is absent from POST — treat as “all”.
		if ( ! is_array( $value ) && isset( $_POST['option_page'] ) && ! isset( $_POST['thw_ai_enabled_traditions'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = array();
		}
		return thw_premium_sanitize_enabled_traditions( $value );
	}

	/**
	 * Sanitize study-search audience.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function sanitize_study_audience( $value ) {
		$slug    = sanitize_key( (string) $value );
		$choices = thw_premium_ai_study_audience_choices();
		return isset( $choices[ $slug ] ) ? $slug : 'logged_in';
	}

	/**
	 * Sanitize Ask a Question audience.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function sanitize_ask_audience( $value ) {
		$slug    = sanitize_key( (string) $value );
		$choices = thw_premium_ai_ask_audience_choices();
		return isset( $choices[ $slug ] ) ? $slug : 'logged_in';
	}

	/**
	 * Sanitize VOTD explanation delivery mode.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function sanitize_votd_explain_delivery( $value ) {
		$slug = sanitize_key( (string) $value );
		return in_array( $slug, array( 'inline', 'redirect' ), true ) ? $slug : 'redirect';
	}

	/**
	 * Sanitize translations selected for daily auto-explain.
	 *
	 * @param mixed $value Input value.
	 * @return string[]
	 */
	public static function sanitize_votd_auto_explain_translations( $value ) {
		// When every box is unchecked, the field is absent from POST.
		if ( ! is_array( $value ) && isset( $_POST['option_page'] ) && ! isset( $_POST['thw_votd_auto_explain_translations'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = array();
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		$choices = class_exists( 'THW_Premium_Verse_Of_The_Day' )
			? THW_Premium_Verse_Of_The_Day::get_translation_choices()
			: array();
		$out     = array();
		foreach ( $value as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug ) {
				continue;
			}
			if ( ! empty( $choices ) && ! isset( $choices[ $slug ] ) ) {
				continue;
			}
			$out[] = $slug;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Sanitize translations selected for daily auto-study.
	 *
	 * @param mixed $value Input value.
	 * @return string[]
	 */
	public static function sanitize_votd_auto_study_translations( $value ) {
		if ( ! is_array( $value ) && isset( $_POST['option_page'] ) && ! isset( $_POST['thw_votd_auto_study_translations'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = array();
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		$choices = class_exists( 'THW_Premium_Verse_Of_The_Day' )
			? THW_Premium_Verse_Of_The_Day::get_translation_choices()
			: array();
		$out     = array();
		foreach ( $value as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug ) {
				continue;
			}
			if ( ! empty( $choices ) && ! isset( $choices[ $slug ] ) ) {
				continue;
			}
			$out[] = $slug;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Sanitize VOTD reference source.
	 *
	 * @param mixed $value Input value.
	 * @return string bible_com|curriculum
	 */
	public static function sanitize_votd_source( $value ) {
		$slug = sanitize_key( (string) $value );
		return in_array( $slug, array( 'bible_com', 'curriculum' ), true ) ? $slug : 'bible_com';
	}

	/**
	 * Sanitize the compliance-failure handling choice.
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public static function sanitize_compliance_failure_action( $value ) {
		$slug    = sanitize_key( (string) $value );
		$choices = thw_premium_ai_compliance_failure_action_choices();
		return isset( $choices[ $slug ] ) ? $slug : 'flag';
	}

	/**
	 * Sanitize study result count (3–12).
	 *
	 * @param mixed $value Input value.
	 * @return int
	 */
	public static function sanitize_study_result_count( $value ) {
		$count = absint( $value );
		if ( $count < 3 ) {
			return 3;
		}
		if ( $count > 12 ) {
			return 12;
		}
		return $count;
	}

	/**
	 * Render front-end AI status summary.
	 */
	private static function render_frontend_ai_status() {
		$toggle      = function_exists( 'hwbl_is_ai_enabled' ) && hwbl_is_ai_enabled();
		// call_user_func: WP 7.0 helper must not be a direct call when Requires at least is 6.2.
		$wp_ai_ok    = ! function_exists( 'wp_supports_ai' ) || (bool) call_user_func( 'wp_supports_ai' );
		$core_text   = THW_Premium_AI_Client::core_supports_text_generation();
		$connected   = THW_Premium_AI_Client::get_connected_ai_provider_ids();
		$configured  = THW_Premium_AI_Client::is_configured();
		$unavailable = thw_premium_ai_frontend_unavailable_reason();

		echo '<div class="notice notice-info inline" style="margin: 0 0 1em; padding: 12px;">';
		echo '<p><strong>' . esc_html__( 'Front-end AI status', 'hidden-word-bible-lessons' ) . '</strong></p>';
		echo '<ul style="list-style: disc; margin-left: 1.5em;">';
		echo '<li>' . esc_html__( 'AI enabled (Bible Lessons → Settings): ', 'hidden-word-bible-lessons' ) . ( $toggle ? esc_html__( 'Yes', 'hidden-word-bible-lessons' ) : esc_html__( 'No', 'hidden-word-bible-lessons' ) ) . '</li>';
		if ( THW_Premium_AI_Client::uses_core_ai() ) {
			echo '<li>' . esc_html__( 'WordPress AI support: ', 'hidden-word-bible-lessons' ) . ( $wp_ai_ok ? esc_html__( 'Yes', 'hidden-word-bible-lessons' ) : esc_html__( 'No — check WP_AI_SUPPORT in wp-config', 'hidden-word-bible-lessons' ) ) . '</li>';
			echo '<li>' . esc_html__( 'Settings → Connectors providers: ', 'hidden-word-bible-lessons' ) . ( empty( $connected ) ? esc_html__( 'None detected', 'hidden-word-bible-lessons' ) : esc_html( implode( ', ', $connected ) ) ) . '</li>';
			echo '<li>' . esc_html__( 'Connectors can generate text: ', 'hidden-word-bible-lessons' ) . ( $core_text ? esc_html__( 'Yes', 'hidden-word-bible-lessons' ) : esc_html__( 'No — reconnect under Settings → Connectors or use the temporary API key fields below', 'hidden-word-bible-lessons' ) ) . '</li>';
		}
		if ( THW_Premium_AI_Client::uses_byok_fallback() ) {
			$byok_label = __( 'None', 'hidden-word-bible-lessons' );
			if ( THW_Premium_AI_Client::has_byok_keys() ) {
				$byok_label = get_option( 'thw_openai_api_key', '' ) || get_option( 'thw_claude_api_key', '' )
					? __( 'Present (Advanced settings)', 'hidden-word-bible-lessons' )
					: __( 'Present (from Settings → Connectors)', 'hidden-word-bible-lessons' );
			}
			echo '<li>' . esc_html__( 'Direct OpenAI/Claude key: ', 'hidden-word-bible-lessons' ) . esc_html( $byok_label ) . '</li>';
		}
		echo '<li>' . esc_html__( 'AI provider overall: ', 'hidden-word-bible-lessons' ) . ( $configured ? esc_html__( 'Configured', 'hidden-word-bible-lessons' ) : esc_html__( 'Not configured', 'hidden-word-bible-lessons' ) ) . '</li>';
		echo '</ul>';
		if ( $unavailable ) {
			echo '<p class="description">' . esc_html( $unavailable ) . '</p>';
		} else {
			echo '<p class="description">' . esc_html__( 'Front-end AI is ready via WordPress Connectors (Explain + Find a Lesson, subject to audience settings).', 'hidden-word-bible-lessons' ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		settings_errors( 'thw_premium' );
		if ( ! empty( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Premium settings saved.', 'hidden-word-bible-lessons' ); ?></p></div>
			<?php
		endif;
		?>
		<div class="wrap">
			<h1>
				<?php
				echo esc_html(
					( defined( 'HWBL_INTEGRATED_PREMIUM' ) && HWBL_INTEGRATED_PREMIUM )
						? __( 'Advanced Settings', 'hidden-word-bible-lessons' )
						: __( 'The Hidden Word Premium', 'hidden-word-bible-lessons' )
				);
				?>
			</h1>

			<?php if ( ! defined( 'HWBL_INTEGRATED_PREMIUM' ) || ! HWBL_INTEGRATED_PREMIUM ) : ?>
				<?php THW_Premium_License::render_license_form(); ?>
				<hr />
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP_MAIN ); ?>

				<h2><?php esc_html_e( 'Bible API Providers', 'hidden-word-bible-lessons' ); ?></h2>
				<div class="thw-bible-api-test" data-thw-bible-api-test>
				<?php self::render_translation_status(); ?>
				<p class="description">
					<?php esc_html_e( 'Verse text is resolved in order: bundled offline curriculum, Hello AO (free, no key), Biblia.com (Premium BYOK), YouVersion Platform (Premium BYOK), then API.Bible (Premium BYOK fallback). NLT is not on Hello AO — use Biblia, YouVersion, or API.Bible.', 'hidden-word-bible-lessons' ); ?>
				</p>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Hello AO', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<?php
							$helloao_on = class_exists( 'HWBL_HelloAO_Provider' ) && HWBL_HelloAO_Provider::is_enabled();
							echo esc_html( $helloao_on ? __( 'Enabled in Bible Lessons → Settings (no API key).', 'hidden-word-bible-lessons' ) : __( 'Disabled — enable under Bible Lessons → Settings.', 'hidden-word-bible-lessons' ) );
							?>
						</td>
					</tr>
					<tr>
						<th><label for="thw_biblia_api_key"><?php esc_html_e( 'Biblia.com API Key', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<input type="password" id="thw_biblia_api_key" name="thw_biblia_api_key" value="<?php echo esc_attr( get_option( 'thw_biblia_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: Biblia API docs URL */
									wp_kses_post( __( 'Bring your own key from <a href="%s" target="_blank" rel="noopener">bibliaapi.com</a>. Unlocks ESV, NLT, NASB, NIV, LEB, and more when bundled or Hello AO text is unavailable.', 'hidden-word-bible-lessons' ) ),
									esc_url( 'https://bibliaapi.com/docs/' )
								);
								?>
							</p>
							<p>
								<button type="button" class="button button-secondary" data-thw-test-biblia>
									<?php esc_html_e( 'Test Biblia key', 'hidden-word-bible-lessons' ); ?>
								</button>
							</p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_youversion_app_key"><?php esc_html_e( 'YouVersion App Key', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<?php $youversion_key_saved = '' !== trim( (string) get_option( 'thw_youversion_app_key', '' ) ); ?>
							<input type="password" id="thw_youversion_app_key" name="thw_youversion_app_key" value="" class="regular-text" autocomplete="off" placeholder="<?php echo $youversion_key_saved ? esc_attr__( 'Saved — paste only to replace', 'hidden-word-bible-lessons' ) : esc_attr__( 'Paste App Key', 'hidden-word-bible-lessons' ); ?>" />
							<?php if ( $youversion_key_saved ) : ?>
								<p class="description"><?php esc_html_e( 'A key is already saved. Paste a new key to replace it, or leave blank to keep the current key.', 'hidden-word-bible-lessons' ); ?></p>
							<?php endif; ?>
							<p class="description">
								<?php
								printf(
									/* translators: %s: YouVersion Platform URL */
									wp_kses_post( __( 'Optional, separate from API.Bible. Register at <a href="%s" target="_blank" rel="noopener">YouVersion Platform</a>, accept Bible licenses in that portal, then paste your App Key here. Not required if you use API.Bible for NLT/NIV/NASB.', 'hidden-word-bible-lessons' ) ),
									esc_url( 'https://platform.youversion.com/platform/apps' )
								);
								?>
							</p>
							<p>
								<button type="button" class="button button-secondary" data-thw-test-youversion>
									<?php esc_html_e( 'Test YouVersion key', 'hidden-word-bible-lessons' ); ?>
								</button>
							</p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_api_bible_key"><?php esc_html_e( 'API.Bible Key', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<?php $api_bible_key_saved = '' !== trim( (string) get_option( 'thw_api_bible_key', '' ) ); ?>
							<input type="password" id="thw_api_bible_key" name="thw_api_bible_key" value="" class="regular-text" autocomplete="off" placeholder="<?php echo $api_bible_key_saved ? esc_attr__( 'Saved — paste only to replace', 'hidden-word-bible-lessons' ) : esc_attr__( 'Paste API.Bible key', 'hidden-word-bible-lessons' ); ?>" />
							<?php if ( $api_bible_key_saved ) : ?>
								<p class="description"><?php esc_html_e( 'A key is already saved. Paste a new key to replace it, or leave blank to keep the current key.', 'hidden-word-bible-lessons' ); ?></p>
							<?php endif; ?>
							<p class="description">
								<?php
								printf(
									/* translators: %s: API.Bible URL */
									wp_kses_post( __( 'From <a href="%s" target="_blank" rel="noopener">API.Bible</a> (scripture.api.bible). Use this for Open Book licenses such as NLT, NIV, and NASB — they appear in your API.Bible dashboard, not YouVersion. Save the key, then click Test API.Bible key.', 'hidden-word-bible-lessons' ) ),
									esc_url( 'https://scripture.api.bible/signup' )
								);
								?>
							</p>
							<p>
								<button type="button" class="button button-secondary" data-thw-test-api-bible>
									<?php esc_html_e( 'Test API.Bible key', 'hidden-word-bible-lessons' ); ?>
								</button>
							</p>
						</td>
					</tr>
				</table>

				<?php self::render_bible_api_test_panel(); ?>
				</div>

				<h2><?php esc_html_e( 'AI Lesson Assistant', 'hidden-word-bible-lessons' ); ?></h2>
				<?php if ( THW_Premium_AI_Client::uses_core_ai() ) : ?>
					<p>
						<?php esc_html_e( 'WordPress 7.0+ prefers Settings → Connectors. Enter your API key once there when a provider shows Connected.', 'hidden-word-bible-lessons' ); ?>
					</p>
					<ol style="list-style: decimal; margin-left: 1.5em;">
						<li><?php esc_html_e( 'Install/activate AI Provider for OpenAI (or Anthropic/Google) from Plugins → Add New, or via the Install button on Connectors.', 'hidden-word-bible-lessons' ); ?></li>
						<li><?php esc_html_e( 'Open Settings → Connectors, Edit the provider, save a valid API key until it shows Connected.', 'hidden-word-bible-lessons' ); ?></li>
						<li><?php esc_html_e( 'Enable AI Features under Bible Lessons → Settings.', 'hidden-word-bible-lessons' ); ?></li>
					</ol>
					<p>
						<a class="button button-secondary" href="<?php echo esc_url( THW_Premium_AI_Client::get_connectors_settings_url() ); ?>">
							<?php esc_html_e( 'Open AI Connectors', 'hidden-word-bible-lessons' ); ?>
						</a>
					</p>
					<?php if ( THW_Premium_AI_Client::core_supports_text_generation() ) : ?>
						<p class="description"><?php esc_html_e( 'A Connected provider is ready. Premium will use it for lesson drafting and front-end AI.', 'hidden-word-bible-lessons' ); ?></p>
					<?php elseif ( ! THW_Premium_AI_Client::core_ai_environment_enabled() ) : ?>
						<p class="description"><?php esc_html_e( 'WordPress AI is disabled (WP_AI_SUPPORT). Enable it in wp-config.php, or use the OpenAI/Claude keys below.', 'hidden-word-bible-lessons' ); ?></p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'No Connected AI provider is generating text yet. Anthropic/Google can stay on Install — you only need one Connected text provider (OpenAI is enough). Until Connectors works, you can paste a key below.', 'hidden-word-bible-lessons' ); ?></p>
					<?php endif; ?>
					<p class="description">
						<?php esc_html_e( 'Optional: if you installed the official “AI” plugin and turned on Connector Approvals, approve The Hidden Word Premium under Tools → Connector Approvals after the first blocked request. That screen is not part of core Connectors.', 'hidden-word-bible-lessons' ); ?>
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'This site does not have the WordPress 7.0 AI Client. Add an OpenAI or Claude API key below.', 'hidden-word-bible-lessons' ); ?></p>
				<?php endif; ?>

				<?php if ( THW_Premium_AI_Client::uses_byok_fallback() ) : ?>
					<?php if ( THW_Premium_AI_Client::uses_core_ai() ) : ?>
						<h3><?php esc_html_e( 'Temporary API keys (fallback)', 'hidden-word-bible-lessons' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Used only while Settings → Connectors cannot generate text. Prefer Connectors once a provider shows Connected and the status above turns green.', 'hidden-word-bible-lessons' ); ?></p>
					<?php endif; ?>
					<table class="form-table">
						<tr>
							<th><label for="thw_openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'hidden-word-bible-lessons' ); ?></label></th>
							<td>
								<input type="password" id="thw_openai_api_key" name="thw_openai_api_key" value="<?php echo esc_attr( get_option( 'thw_openai_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
							</td>
						</tr>
						<tr>
							<th><label for="thw_claude_api_key"><?php esc_html_e( 'Claude API Key', 'hidden-word-bible-lessons' ); ?></label></th>
							<td>
								<input type="password" id="thw_claude_api_key" name="thw_claude_api_key" value="<?php echo esc_attr( get_option( 'thw_claude_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
								<p class="description"><?php esc_html_e( 'Used if no OpenAI key is set.', 'hidden-word-bible-lessons' ); ?></p>
							</td>
						</tr>
					</table>
				<?php endif; ?>

				<h2><?php esc_html_e( 'Front-End AI', 'hidden-word-bible-lessons' ); ?></h2>
				<?php self::render_frontend_ai_status(); ?>
				<p><?php esc_html_e( 'Configure guardrails for AI lesson explanations, keyword Bible study search, and Ask a Question. Enable AI under Bible Lessons → Settings.', 'hidden-word-bible-lessons' ); ?></p>
				<table class="form-table">
					<tr>
						<th><label for="thw_ai_explain_rules_preset"><?php esc_html_e( 'Faith tradition', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<?php
							$explain_rules = get_option( 'thw_ai_explain_rules', thw_premium_default_ai_explain_rules() );
							$saved_preset  = get_option( 'thw_ai_explain_rules_preset', 'nondenom' );
							$active_preset = thw_premium_resolve_ai_explain_rules_preset( $explain_rules, $saved_preset );
							$presets       = thw_premium_ai_explain_rule_presets();
							uasort(
								$presets,
								static function ( $a, $b ) {
									return strcasecmp( (string) $a['label'], (string) $b['label'] );
								}
							);
							?>
							<select id="thw_ai_explain_rules_preset" name="thw_ai_explain_rules_preset">
								<option value="custom" <?php selected( $active_preset, 'custom' ); ?>>
									<?php esc_html_e( 'Custom', 'hidden-word-bible-lessons' ); ?>
								</option>
								<?php foreach ( $presets as $slug => $preset ) : ?>
									<option
										value="<?php echo esc_attr( $slug ); ?>"
										data-rules="<?php echo esc_attr( $preset['rules'] ); ?>"
										<?php selected( $active_preset, $slug ); ?>
									>
										<?php echo esc_html( $preset['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Your church’s or denomination’s belief framework. Selecting one replaces the Explain rules below and loads curated doctrine digests and stance gates for AI routing. You can still edit the free-text rules afterward.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_ai_explain_rules"><?php esc_html_e( 'Explain rules', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<textarea id="thw_ai_explain_rules" name="thw_ai_explain_rules" rows="5" class="large-text"><?php echo esc_textarea( $explain_rules ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Instructions sent with every “Explain this lesson” request.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_ai_study_rules"><?php esc_html_e( 'Study search rules', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<textarea id="thw_ai_study_rules" name="thw_ai_study_rules" rows="5" class="large-text"><?php echo esc_textarea( get_option( 'thw_ai_study_rules', thw_premium_default_ai_study_rules() ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Extra guardrails for AI topic Scripture search (hope, divorce, anxiety, etc.). Belief framing also comes from Explain rules above.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Front-end faith tradition picker', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_ai_allow_user_tradition" value="0" />
							<label>
								<input type="checkbox" name="thw_ai_allow_user_tradition" value="1" <?php checked( thw_premium_user_tradition_enabled() ); ?> />
								<?php esc_html_e( 'Let visitors choose their faith tradition on Explain, Find a Bible Study, and Ask a Question', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'When enabled, users pick from the traditions you enable below. If only one tradition is enabled, the picker is hidden automatically. Their choice routes Explain, Find a Bible Study, and Ask a Question through that tradition’s curated doctrine digests and Yes/No/Conditional logic gates. The site Faith tradition above remains the default when it is enabled.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Enabled traditions', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<?php
							$all_choices     = thw_premium_get_tradition_preset_choices( false );
							$enabled_slugs   = thw_premium_get_enabled_tradition_slugs();
							$stored_enabled  = get_option( 'thw_ai_enabled_traditions', array() );
							$limit_active    = is_array( $stored_enabled ) && ! empty( $stored_enabled );
							?>
							<p class="description" style="margin-top:0;">
								<?php esc_html_e( 'Choose which faith traditions appear on this site. Leave all unchecked (or check all) to offer every tradition. Checking only some limits the visitor picker to those options.', 'hidden-word-bible-lessons' ); ?>
							</p>
							<fieldset class="thw-enabled-traditions" style="max-width:520px;max-height:220px;overflow:auto;padding:8px 10px;border:1px solid #c3c4c7;background:#fff;">
								<?php foreach ( $all_choices as $slug => $label ) : ?>
									<label style="display:block;margin:4px 0;">
										<input
											type="checkbox"
											name="thw_ai_enabled_traditions[]"
											value="<?php echo esc_attr( $slug ); ?>"
											<?php checked( ! $limit_active || in_array( $slug, $enabled_slugs, true ) ); ?>
										/>
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Tip: enable a single tradition (for example Non-denominational) and turn the picker on — visitors will use that tradition with no dropdown shown.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'AI compliance check', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_ai_compliance_check_enabled" value="0" />
							<label>
								<input type="checkbox" name="thw_ai_compliance_check_enabled" value="1" <?php checked( thw_premium_ai_compliance_check_enabled() ); ?> />
								<?php esc_html_e( 'Before showing an AI response, verify it does not conflict with the selected tradition’s rules, and retry once if it does', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Adds a second (occasionally a third) AI call per request, so it costs a little more and responds a little slower. It’s a backstop, not a guarantee — always pair with the on-screen “consult trusted teachers” disclaimer.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'If it still fails the check', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<?php $failure_action = thw_premium_get_ai_compliance_failure_action(); ?>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'If a response is still flagged after the retry', 'hidden-word-bible-lessons' ); ?></legend>
								<?php foreach ( thw_premium_ai_compliance_failure_action_choices() as $slug => $label ) : ?>
									<label style="display:block; margin-bottom: 0.35em;">
										<input type="radio" name="thw_ai_compliance_failure_action" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $failure_action, $slug ); ?> />
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description"><?php esc_html_e( 'Only matters when the AI compliance check above is on and a response is still flagged after the one retry. Showing it flagged keeps an answer available to visitors; blocking it never lets an unverified answer through, at the cost of sometimes showing nothing.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_ai_study_audience"><?php esc_html_e( 'Who can see AI study results', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<?php $study_audience = thw_premium_get_ai_study_audience(); ?>
							<select id="thw_ai_study_audience" name="thw_ai_study_audience">
								<?php foreach ( thw_premium_ai_study_audience_choices() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $study_audience, $slug ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Controls who can run Find a Bible Study by Topic and see AI Scripture guidance.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_ai_study_result_count"><?php esc_html_e( 'Study results', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<input type="number" id="thw_ai_study_result_count" name="thw_ai_study_result_count" value="<?php echo esc_attr( get_option( 'thw_ai_study_result_count', 5 ) ); ?>" min="3" max="12" />
							<p class="description"><?php esc_html_e( 'Maximum Scripture passages returned (3–12).', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Study Finder related lessons', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_ai_study_include_lessons" value="0" />
							<label>
								<input type="checkbox" name="thw_ai_study_include_lessons" value="1" <?php checked( thw_premium_ai_study_include_lessons() ); ?> />
								<?php esc_html_e( 'Also suggest related curriculum lessons alongside Scripture results', 'hidden-word-bible-lessons' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th colspan="2"><h3 style="margin:1.5em 0 0.5em"><?php esc_html_e( 'Ask a Question', 'hidden-word-bible-lessons' ); ?></h3></th>
					</tr>
					<tr>
						<th><label for="thw_ai_ask_audience"><?php esc_html_e( 'Who can use Ask a Question', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<?php $ask_audience = thw_premium_get_ai_ask_audience(); ?>
							<select id="thw_ai_ask_audience" name="thw_ai_ask_audience">
								<?php foreach ( thw_premium_ai_ask_audience_choices() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $ask_audience, $slug ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Controls who can use the [thw_ask_question] shortcode / Ask a Question page.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_ai_ask_rules"><?php esc_html_e( 'Ask a Question rules', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<textarea id="thw_ai_ask_rules" name="thw_ai_ask_rules" rows="5" class="large-text"><?php echo esc_textarea( get_option( 'thw_ai_ask_rules', thw_premium_default_ai_ask_rules() ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Extra guardrails for free-form Q&A. Belief framing still comes from the faith tradition / visitor picker above.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Ask related lessons', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_ai_ask_include_lessons" value="0" />
							<label>
								<input type="checkbox" name="thw_ai_ask_include_lessons" value="1" <?php checked( thw_premium_ai_ask_include_lessons() ); ?> />
								<?php esc_html_e( 'Include related curriculum lesson suggestions with each answer (default on)', 'hidden-word-bible-lessons' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th colspan="2"><h3 style="margin:1.5em 0 0.5em"><?php esc_html_e( 'Church subject rules', 'hidden-word-bible-lessons' ); ?></h3></th>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Subject policies', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<p class="description" style="margin-top:0">
								<?php esc_html_e( 'These override the selected tradition for matching subjects on Ask, Explain, and Find a Lesson. Choose a preset topic or Custom…, then write your church’s policy.', 'hidden-word-bible-lessons' ); ?>
							</p>
							<?php self::render_church_subject_rules_ui(); ?>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Verse of the Day', 'hidden-word-bible-lessons' ); ?></h2>
				<?php if ( ! empty( $_GET['thw_votd_cache_cleared'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Verse of the Day cache cleared.', 'hidden-word-bible-lessons' ); ?></p></div>
				<?php endif; ?>
				<p>
					<?php
					printf(
						/* translators: %s: shortcode */
						esc_html__( 'Display the daily verse with [%s]. Verse text uses your configured Bible API providers. When bible.com is unreachable, the plugin uses the YouVersion Platform API if an App Key is configured; otherwise it falls back to today’s curriculum verse. With WP_DEBUG and WP_DEBUG_LOG enabled, diagnostic lines prefixed [THW VOTD] are written to debug.log.', 'hidden-word-bible-lessons' ),
						'thw_verse_of_the_day'
					);
					?>
				</p>
				<table class="form-table">
					<tr>
						<th><label for="thw_votd_source"><?php esc_html_e( 'VOTD source', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<?php $votd_source = sanitize_key( (string) get_option( 'thw_votd_source', 'bible_com' ) ); ?>
							<select id="thw_votd_source" name="thw_votd_source">
								<option value="bible_com" <?php selected( $votd_source, 'bible_com' ); ?>>
									<?php esc_html_e( 'Bible.com Verse of the Day (reference + image from YouVersion)', 'hidden-word-bible-lessons' ); ?>
								</option>
								<option value="curriculum" <?php selected( $votd_source, 'curriculum' ); ?>>
									<?php esc_html_e( 'Site curriculum schedule (today’s lesson; Bible API providers for text)', 'hidden-word-bible-lessons' ); ?>
								</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Show featured image', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_show_image" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_show_image" value="1" <?php checked( get_option( 'thw_votd_show_image', true ) ); ?> />
								<?php esc_html_e( 'Include the Bible.com / YouVersion Verse of the Day image when using the Bible.com source (default on)', 'hidden-word-bible-lessons' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'AI explain verse', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_ai_explain" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_ai_explain" value="1" <?php checked( get_option( 'thw_votd_ai_explain', false ) ); ?> />
								<?php esc_html_e( 'Show “Explain this verse” with Bible translation picker', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Visitors pick a Bible version (NIV, KJV, etc.). The first logged-in request per version per day generates a neutral AI explanation and publishes it as a post. Later visitors for that version go straight to that post—no extra AI call. Requires Enable AI Features + a configured provider.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Explain featured image', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_explain_featured_image" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_explain_featured_image" value="1" <?php checked( get_option( 'thw_votd_explain_featured_image', true ) ); ?> />
								<?php esc_html_e( 'Attach and show the Verse of the Day image on saved explanation posts (default on)', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When a new VOTD explanation is published, the Bible.com / YouVersion image is saved as the post featured image. You can also set or replace it manually under Bible Lessons → VOTD Explanations. Turn off to hide featured images on explanation pages.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_votd_explain_delivery"><?php esc_html_e( 'Saved explanation delivery', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<?php $votd_delivery = sanitize_key( (string) get_option( 'thw_votd_explain_delivery', 'redirect' ) ); ?>
							<select id="thw_votd_explain_delivery" name="thw_votd_explain_delivery">
								<option value="redirect" <?php selected( $votd_delivery, 'redirect' ); ?>>
									<?php esc_html_e( 'Redirect to the saved explanation post (recommended)', 'hidden-word-bible-lessons' ); ?>
								</option>
								<option value="inline" <?php selected( $votd_delivery, 'inline' ); ?>>
									<?php esc_html_e( 'Show on the page (with link to full post)', 'hidden-word-bible-lessons' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'The first “Explain this verse” for each Bible version per day publishes a post under Bible Lessons → VOTD Explanations. Later visitors are redirected to that post—no repeat AI call. VOTD explanations use neutral (non-denominational) AI rules.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Daily auto-explain', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_auto_explain_enabled" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_auto_explain_enabled" value="1" <?php checked( get_option( 'thw_votd_auto_explain_enabled', false ) ); ?> />
								<?php esc_html_e( 'Automatically generate today’s Verse of the Day explanations for selected Bible versions (default off)', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Enable on hub sites like thehiddenword.org so NIV, NLT, KJV (or other selected versions) are ready each morning. Leave off on church sites that prefer on-demand explains only. Uses AI credits once per version per day; skips versions that already have a saved post.', 'hidden-word-bible-lessons' ); ?>
							</p>
							<?php if ( class_exists( 'HWBL_Church_Network' ) && HWBL_Church_Network::is_hub() ) : ?>
								<p class="description">
									<strong><?php esc_html_e( 'Hub site:', 'hidden-word-bible-lessons' ); ?></strong>
									<?php esc_html_e( 'This network hub can safely enable daily auto-explain for popular translations. Connected church sites keep their own settings and stay off unless they opt in.', 'hidden-word-bible-lessons' ); ?>
								</p>
							<?php endif; ?>
							<?php
							$auto_selected = class_exists( 'THW_Premium_Verse_Of_The_Day' )
								? THW_Premium_Verse_Of_The_Day::get_auto_explain_translations()
								: array();
							$auto_choices  = class_exists( 'THW_Premium_Verse_Of_The_Day' )
								? THW_Premium_Verse_Of_The_Day::get_translation_choices()
								: array();
							?>
							<fieldset style="margin-top:0.75em">
								<legend class="screen-reader-text"><?php esc_html_e( 'Auto-explain translations', 'hidden-word-bible-lessons' ); ?></legend>
								<p><strong><?php esc_html_e( 'Generate for these translations:', 'hidden-word-bible-lessons' ); ?></strong></p>
								<?php if ( empty( $auto_choices ) ) : ?>
									<p class="description"><?php esc_html_e( 'No Bible translations are available yet. Configure Hello AO / local Bibles / API keys first.', 'hidden-word-bible-lessons' ); ?></p>
								<?php else : ?>
									<ul style="columns:2;max-width:36em;margin:0.5em 0 0;padding:0;list-style:none">
										<?php foreach ( $auto_choices as $slug => $label ) : ?>
											<li style="margin:0 0 0.35em;break-inside:avoid">
												<label>
													<input
														type="checkbox"
														name="thw_votd_auto_explain_translations[]"
														value="<?php echo esc_attr( $slug ); ?>"
														<?php checked( in_array( $slug, $auto_selected, true ) ); ?>
													/>
													<?php echo esc_html( $label ); ?>
												</label>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</fieldset>
							<?php
							$auto_status = get_option( 'thw_votd_auto_explain_status', array() );
							$auto_last   = get_option( 'thw_votd_auto_explain_last', array() );
							if ( ! empty( $_GET['thw_votd_auto_explain_ran'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								?>
								<div class="notice notice-success inline" style="margin:0.75em 0 0"><p><?php esc_html_e( 'Daily auto-explain ran for today.', 'hidden-word-bible-lessons' ); ?></p></div>
							<?php endif; ?>
							<?php if ( is_array( $auto_status ) && ( ! empty( $auto_status['generated'] ) || ! empty( $auto_status['skipped'] ) || ! empty( $auto_status['errors'] ) ) ) : ?>
								<p class="description" style="margin-top:0.75em">
									<?php
									printf(
										/* translators: 1: day, 2: generated list, 3: skipped list */
										esc_html__( 'Last run (%1$s): generated %2$s; already saved %3$s.', 'hidden-word-bible-lessons' ),
										esc_html( (string) ( $auto_status['day'] ?? '' ) ),
										esc_html( ! empty( $auto_status['generated'] ) ? implode( ', ', array_map( 'strtoupper', (array) $auto_status['generated'] ) ) : '—' ),
										esc_html( ! empty( $auto_status['skipped'] ) ? implode( ', ', array_map( 'strtoupper', (array) $auto_status['skipped'] ) ) : '—' )
									);
									?>
									<?php if ( ! empty( $auto_status['errors'] ) && is_array( $auto_status['errors'] ) ) : ?>
										<br />
										<?php
										foreach ( $auto_status['errors'] as $err_slug => $err_msg ) {
											echo esc_html( ( '_' === (string) $err_slug ? '' : strtoupper( (string) $err_slug ) . ': ' ) . (string) $err_msg ) . '<br />';
										}
										?>
									<?php endif; ?>
									<?php if ( is_array( $auto_last ) && ! empty( $auto_last['at'] ) ) : ?>
										<br /><?php echo esc_html( sprintf( /* translators: %s: mysql datetime */ __( 'Completed at %s', 'hidden-word-bible-lessons' ), (string) $auto_last['at'] ) ); ?>
									<?php endif; ?>
								</p>
							<?php endif; ?>
							<p style="margin-top:0.75em">
								<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=thw_votd_auto_explain_now' ), 'thw_votd_auto_explain_now' ) ); ?>">
									<?php esc_html_e( 'Generate selected explains now', 'hidden-word-bible-lessons' ); ?>
								</a>
							</p>
						</td>
					</tr>
					<?php if ( class_exists( 'THW_Premium_Bible_Study_Card' ) ) : ?>
					<tr>
						<th><?php esc_html_e( 'Daily auto-study', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_auto_study_enabled" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_auto_study_enabled" value="1" <?php checked( get_option( 'thw_votd_auto_study_enabled', false ) ); ?> />
								<?php esc_html_e( 'Automatically generate today’s Verse of the Day “Study this verse” cards for selected Bible versions (default off)', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Enable on hub sites like thehiddenword.org so NIV, NLT, KJV (or other selected versions) have Study this verse ready each morning—guests can open the card without signing in. Leave off on church sites that prefer on-demand study only. Uses AI credits once per version per day; skips versions that already have a cached study card.', 'hidden-word-bible-lessons' ); ?>
							</p>
							<?php if ( class_exists( 'HWBL_Church_Network' ) && HWBL_Church_Network::is_hub() ) : ?>
								<p class="description">
									<strong><?php esc_html_e( 'Hub site:', 'hidden-word-bible-lessons' ); ?></strong>
									<?php esc_html_e( 'This network hub can safely enable daily auto-study for popular translations. Connected church sites keep their own settings and stay off unless they opt in.', 'hidden-word-bible-lessons' ); ?>
								</p>
							<?php endif; ?>
							<?php
							$study_selected = class_exists( 'THW_Premium_Verse_Of_The_Day' )
								? THW_Premium_Verse_Of_The_Day::get_auto_study_translations()
								: array();
							$study_choices  = class_exists( 'THW_Premium_Verse_Of_The_Day' )
								? THW_Premium_Verse_Of_The_Day::get_translation_choices()
								: array();
							?>
							<fieldset style="margin-top:0.75em">
								<legend class="screen-reader-text"><?php esc_html_e( 'Auto-study translations', 'hidden-word-bible-lessons' ); ?></legend>
								<p><strong><?php esc_html_e( 'Generate for these translations:', 'hidden-word-bible-lessons' ); ?></strong></p>
								<?php if ( empty( $study_choices ) ) : ?>
									<p class="description"><?php esc_html_e( 'No Bible translations are available yet. Configure Hello AO / local Bibles / API keys first.', 'hidden-word-bible-lessons' ); ?></p>
								<?php else : ?>
									<ul style="columns:2;max-width:36em;margin:0.5em 0 0;padding:0;list-style:none">
										<?php foreach ( $study_choices as $slug => $label ) : ?>
											<li style="margin:0 0 0.35em;break-inside:avoid">
												<label>
													<input
														type="checkbox"
														name="thw_votd_auto_study_translations[]"
														value="<?php echo esc_attr( $slug ); ?>"
														<?php checked( in_array( $slug, $study_selected, true ) ); ?>
													/>
													<?php echo esc_html( $label ); ?>
												</label>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</fieldset>
							<?php
							$study_status = get_option( 'thw_votd_auto_study_status', array() );
							$study_last   = get_option( 'thw_votd_auto_study_last', array() );
							if ( ! empty( $_GET['thw_votd_auto_study_ran'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								?>
								<div class="notice notice-success inline" style="margin:0.75em 0 0"><p><?php esc_html_e( 'Daily auto-study ran for today.', 'hidden-word-bible-lessons' ); ?></p></div>
							<?php endif; ?>
							<?php if ( is_array( $study_status ) && ( ! empty( $study_status['generated'] ) || ! empty( $study_status['skipped'] ) || ! empty( $study_status['errors'] ) ) ) : ?>
								<p class="description" style="margin-top:0.75em">
									<?php
									printf(
										/* translators: 1: day, 2: generated list, 3: skipped list */
										esc_html__( 'Last run (%1$s): generated %2$s; already cached %3$s.', 'hidden-word-bible-lessons' ),
										esc_html( (string) ( $study_status['day'] ?? '' ) ),
										esc_html( ! empty( $study_status['generated'] ) ? implode( ', ', array_map( 'strtoupper', (array) $study_status['generated'] ) ) : '—' ),
										esc_html( ! empty( $study_status['skipped'] ) ? implode( ', ', array_map( 'strtoupper', (array) $study_status['skipped'] ) ) : '—' )
									);
									?>
									<?php if ( ! empty( $study_status['errors'] ) && is_array( $study_status['errors'] ) ) : ?>
										<br />
										<?php
										foreach ( $study_status['errors'] as $err_slug => $err_msg ) {
											echo esc_html( ( '_' === (string) $err_slug ? '' : strtoupper( (string) $err_slug ) . ': ' ) . (string) $err_msg ) . '<br />';
										}
										?>
									<?php endif; ?>
									<?php if ( is_array( $study_last ) && ! empty( $study_last['at'] ) ) : ?>
										<br /><?php echo esc_html( sprintf( /* translators: %s: mysql datetime */ __( 'Completed at %s', 'hidden-word-bible-lessons' ), (string) $study_last['at'] ) ); ?>
									<?php endif; ?>
								</p>
							<?php endif; ?>
							<p style="margin-top:0.75em">
								<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=thw_votd_auto_study_now' ), 'thw_votd_auto_study_now' ) ); ?>">
									<?php esc_html_e( 'Generate selected study cards now', 'hidden-word-bible-lessons' ); ?>
								</a>
							</p>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<th><label for="thw_votd_translation"><?php esc_html_e( 'Translation override', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<?php
							$votd_trans = sanitize_key( (string) get_option( 'thw_votd_translation', '' ) );
							$choices    = array( '' => __( 'Site default', 'hidden-word-bible-lessons' ) );
							if ( class_exists( 'HWBL_Translation_Service' ) ) {
								$supported = HWBL_Translation_Service::instance()->get_supported_translations();
								if ( is_array( $supported ) ) {
									foreach ( $supported as $slug => $label ) {
										$choices[ sanitize_key( (string) $slug ) ] = is_string( $label ) ? $label : strtoupper( (string) $slug );
									}
								}
							}
							?>
							<select id="thw_votd_translation" name="thw_votd_translation">
								<?php foreach ( $choices as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $votd_trans, $slug ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Leave as Site default to use Bible Lessons → Settings translation. Verses outside the free NIV bundle may fall back to a public-domain text.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Clear cache', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=thw_clear_votd_cache' ), 'thw_clear_votd_cache' ) ); ?>">
								<?php esc_html_e( 'Clear Verse of the Day cache', 'hidden-word-bible-lessons' ); ?>
							</a>
							<p class="description"><?php esc_html_e( 'Use after changing API keys or if the homepage shows an empty Verse of the Day.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Manual Scheduling', 'hidden-word-bible-lessons' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="thw_manual_lesson_id"><?php esc_html_e( 'Manual Lesson ID', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<input type="number" id="thw_manual_lesson_id" name="thw_manual_lesson_id" value="<?php echo esc_attr( get_option( 'thw_manual_lesson_id', '' ) ); ?>" min="0" />
							<p class="description"><?php esc_html_e( 'Used when schedule mode is set to Manual Selection.', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />

			<?php THW_Premium_Settings_App::render_section(); ?>

			<h2><?php esc_html_e( 'Email Digest', 'hidden-word-bible-lessons' ); ?></h2>
			<p><?php esc_html_e( 'Send scheduled lesson emails to subscribers. Use the [thw_subscribe] shortcode on a public page.', 'hidden-word-bible-lessons' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP_DIGEST ); ?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Enable digest', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_digest_enabled" value="0" />
							<label>
								<input type="checkbox" name="thw_digest_enabled" value="1" <?php checked( get_option( 'thw_digest_enabled', false ) ); ?> />
								<?php esc_html_e( 'Send daily (or weekly when schedule mode is week)', 'hidden-word-bible-lessons' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="thw_digest_subject"><?php esc_html_e( 'Email subject', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><input type="text" id="thw_digest_subject" name="thw_digest_subject" value="<?php echo esc_attr( get_option( 'thw_digest_subject', class_exists( 'HWBL_Scheduler' ) ? HWBL_Scheduler::get_schedule_phrase( 'memorize' ) : __( "Today's Verse to Memorize", 'hidden-word-bible-lessons' ) ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="thw_digest_from_name"><?php esc_html_e( 'From name', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><input type="text" id="thw_digest_from_name" name="thw_digest_from_name" value="<?php echo esc_attr( get_option( 'thw_digest_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Digest Settings', 'hidden-word-bible-lessons' ) ); ?>
			</form>
			<p><?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of confirmed digest subscribers */
					__( '%d confirmed subscribers.', 'hidden-word-bible-lessons' ),
					count(
						array_filter(
							THW_Premium_Digest::get_subscribers(),
							static function ( $row ) {
								return isset( $row['status'] ) && 'confirmed' === $row['status'];
							}
						)
					)
				)
			);
			?></p>
			<?php
			$subscribers = THW_Premium_Digest::get_subscribers();
			if ( ! empty( $subscribers ) ) :
				?>
				<table class="widefat striped" style="max-width: 40rem; margin-top: 1em;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Email', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php esc_html_e( 'Status', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php esc_html_e( 'Subscribed', 'hidden-word-bible-lessons' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $subscribers as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['email'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['status'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['subscribed_at'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<hr />

			<h2><?php esc_html_e( 'Verse of the Day Email', 'hidden-word-bible-lessons' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: shortcode */
					esc_html__( 'Email Bible.com’s Verse of the Day plus the full explanation in each subscriber’s chosen Bible translation. Place [%s] on a public page. Separate from the lesson digest above.', 'hidden-word-bible-lessons' ),
					'thw_votd_subscribe'
				);
				?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP_VOTD_DIGEST ); ?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Enable daily VOTD email', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_digest_enabled" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_digest_enabled" value="1" <?php checked( get_option( 'thw_votd_digest_enabled', false ) ); ?> />
								<?php esc_html_e( 'Send one email per day to confirmed subscribers', 'hidden-word-bible-lessons' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="thw_votd_digest_subject"><?php esc_html_e( 'Email subject', 'hidden-word-bible-lessons' ); ?></label></th>
						<td>
							<input type="text" id="thw_votd_digest_subject" name="thw_votd_digest_subject" value="<?php echo esc_attr( get_option( 'thw_votd_digest_subject', __( 'Verse of the Day — {reference}', 'hidden-word-bible-lessons' ) ) ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Placeholders: {reference}, {translation}', 'hidden-word-bible-lessons' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="thw_votd_digest_from_name"><?php esc_html_e( 'From name', 'hidden-word-bible-lessons' ); ?></label></th>
						<td><input type="text" id="thw_votd_digest_from_name" name="thw_votd_digest_from_name" value="<?php echo esc_attr( get_option( 'thw_votd_digest_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Include explanation', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_digest_include_explain" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_digest_include_explain" value="1" <?php checked( get_option( 'thw_votd_digest_include_explain', true ) ); ?> />
								<?php esc_html_e( 'Include the full saved explanation in the email (recommended)', 'hidden-word-bible-lessons' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Generate missing explanation', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_digest_generate_explain" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_digest_generate_explain" value="1" <?php checked( get_option( 'thw_votd_digest_generate_explain', false ) ); ?> />
								<?php esc_html_e( 'If no saved explanation exists yet, generate one via AI when the daily email runs (uses AI credits; then saves the post for reuse)', 'hidden-word-bible-lessons' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Requires “AI explain verse” enabled above and a configured AI provider. Leave off if you prefer explanations to be created on the website first.', 'hidden-word-bible-lessons' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Include featured image', 'hidden-word-bible-lessons' ); ?></th>
						<td>
							<input type="hidden" name="thw_votd_digest_include_image" value="0" />
							<label>
								<input type="checkbox" name="thw_votd_digest_include_image" value="1" <?php checked( get_option( 'thw_votd_digest_include_image', false ) ); ?> />
								<?php esc_html_e( 'Embed the Bible.com Verse of the Day image in the email', 'hidden-word-bible-lessons' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Verse of the Day Email Settings', 'hidden-word-bible-lessons' ) ); ?>
			</form>
			<?php
			$votd_subs = class_exists( 'THW_Premium_Votd_Digest' ) ? THW_Premium_Votd_Digest::get_subscribers() : array();
			$votd_confirmed = count(
				array_filter(
					$votd_subs,
					static function ( $row ) {
						return isset( $row['status'] ) && 'confirmed' === $row['status'];
					}
				)
			);
			?>
			<p><?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of confirmed Verse of the Day email subscribers */
					__( '%d confirmed Verse of the Day email subscribers.', 'hidden-word-bible-lessons' ),
					$votd_confirmed
				)
			);
			?></p>
			<?php if ( ! empty( $votd_subs ) ) : ?>
				<table class="widefat striped" style="max-width: 48rem; margin-top: 1em;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Email', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php esc_html_e( 'Translation', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php esc_html_e( 'Faith tradition', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php esc_html_e( 'Status', 'hidden-word-bible-lessons' ); ?></th>
							<th><?php esc_html_e( 'Subscribed', 'hidden-word-bible-lessons' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $votd_subs as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['email'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['translation'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['tradition'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['status'] ?? '' ); ?></td>
								<td><?php echo esc_html( $row['subscribed_at'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<hr />

			<h2><?php esc_html_e( 'Custom Track CSV Import', 'hidden-word-bible-lessons' ); ?></h2>
			<p><?php esc_html_e( 'Upload a CSV with columns: lesson_number or reference, track_order.', 'hidden-word-bible-lessons' ); ?></p>
			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Flash status after admin-post redirect (nonce verified in handle_import).
			if ( isset( $_GET['thw_track_import'] ) ) {
				$status = sanitize_text_field( wp_unslash( $_GET['thw_track_import'] ) );
				if ( 0 === strpos( $status, 'ok-' ) ) {
					echo '<div class="notice notice-success inline"><p>' . esc_html(
						sprintf(
							/* translators: %s: number of lessons updated by CSV import */
							__( 'Updated %s lessons.', 'hidden-word-bible-lessons' ),
							substr( $status, 3 )
						)
					) . '</p></div>';
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'thw_import_track_csv' ); ?>
				<input type="hidden" name="action" value="thw_import_track_csv" />
				<input type="file" name="track_csv" accept=".csv,text/csv" required />
				<?php submit_button( __( 'Import Track CSV', 'hidden-word-bible-lessons' ), 'secondary' ); ?>
			</form>
			<p><a href="data:text/csv;charset=utf-8,lesson_number%2Ctrack_order%0A1%2C1%0A2%2C2" download="thw-track-sample.csv"><?php esc_html_e( 'Download sample CSV (by lesson number)', 'hidden-word-bible-lessons' ); ?></a>
			| <a href="data:text/csv;charset=utf-8,reference%2Ctrack_order%0AJohn%203%3A16%2C1%0ARomans%208%3A28%2C2" download="thw-track-reference-sample.csv"><?php esc_html_e( 'Sample by reference', 'hidden-word-bible-lessons' ); ?></a></p>

			<hr />

			<h2><?php esc_html_e( 'Schedule Export', 'hidden-word-bible-lessons' ); ?></h2>
			<p><?php esc_html_e( 'Download an iCal file for the upcoming lesson schedule.', 'hidden-word-bible-lessons' ); ?></p>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'thw_download_ical' ); ?>
				<input type="hidden" name="action" value="thw_download_ical" />
				<label for="thw_ical_weeks"><?php esc_html_e( 'Weeks/slots to include', 'hidden-word-bible-lessons' ); ?></label>
				<input type="number" id="thw_ical_weeks" name="weeks" value="52" min="1" max="365" />
				<?php submit_button( __( 'Download iCal', 'hidden-word-bible-lessons' ), 'secondary' ); ?>
			</form>

			<hr />

			<h2><?php esc_html_e( 'Leader Booklet PDF', 'hidden-word-bible-lessons' ); ?></h2>
			<p><?php esc_html_e( 'Download multi-lesson leader guides as a single PDF.', 'hidden-word-bible-lessons' ); ?></p>
			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=thw_download_booklet&scope=custom' ), 'thw_download_booklet' ) ); ?>">
					<?php esc_html_e( 'Download custom track booklet', 'hidden-word-bible-lessons' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=thw_download_booklet&scope=all' ), 'thw_download_booklet' ) ); ?>">
					<?php esc_html_e( 'Download full 500-lesson booklet', 'hidden-word-bible-lessons' ); ?>
				</a>
			</p>

			<hr />

			<h2><?php esc_html_e( 'Premium Shortcodes', 'hidden-word-bible-lessons' ); ?></h2>
			<p><code>[thw_subscribe]</code> — <?php esc_html_e( 'Email signup form for the lesson digest.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[thw_votd_subscribe]</code> — <?php esc_html_e( 'Email signup for daily Bible.com Verse of the Day + explanation (chosen translation).', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[thw_cohort_roster cohort="slug"]</code> — <?php esc_html_e( 'Leader roster table for a cohort (requires Group Leader role or editor).', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[thw_my_progress]</code> — <?php esc_html_e( 'Logged-in user memorization dashboard with streaks and badges.', 'hidden-word-bible-lessons' ); ?></p>
			<p><code>[thw_study_finder]</code> — <?php esc_html_e( 'AI Scripture search by topic (e.g. hope, divorce, peace), shaped by your tradition settings.', 'hidden-word-bible-lessons' ); ?></p>
			<p class="description"><?php esc_html_e( 'In the block editor, add a Shortcode block and paste with straight quotes, for example: [thw_study_finder title="Find a Bible Study by Topic"]', 'hidden-word-bible-lessons' ); ?></p>

			<hr />

			<h2><?php esc_html_e( '365-Day Curriculum', 'hidden-word-bible-lessons' ); ?></h2>
			<p><?php esc_html_e( 'Map the first 365 lessons from the 500-verse bundled curriculum to daily slots. Stays within the 500-verse NIV fair-use limit.', 'hidden-word-bible-lessons' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'thw_expand_curriculum' ); ?>
				<input type="hidden" name="action" value="thw_expand_curriculum" />
				<?php submit_button( __( 'Generate 365-Day Curriculum', 'hidden-word-bible-lessons' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render church subject rules repeater.
	 */
	private static function render_church_subject_rules_ui() {
		$rows         = THW_Premium_Church_Subject_Rules::get_rules();
		$topic_choices = THW_Premium_Church_Subject_Rules::topic_choices();
		$stances      = THW_Premium_Church_Subject_Rules::stance_choices();
		if ( empty( $rows ) ) {
			$rows = array(
				array(
					'enabled'      => true,
					'topic'        => '',
					'custom_label' => '',
					'keywords'     => '',
					'stance'       => 'pastoral',
					'rules'        => '',
				),
			);
		}
		?>
		<div id="thw-church-subject-rules" data-thw-church-subject-rules>
			<table class="widefat striped thw-church-subject-rules__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'On', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Topic', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Custom label', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Keywords', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Stance', 'hidden-word-bible-lessons' ); ?></th>
						<th><?php esc_html_e( 'Church policy', 'hidden-word-bible-lessons' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody class="thw-church-subject-rules__body">
					<?php foreach ( $rows as $i => $row ) : ?>
						<?php self::render_church_subject_rule_row( (int) $i, $row, $topic_choices, $stances ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<button type="button" class="button thw-church-subject-rules__add"><?php esc_html_e( 'Add subject rule', 'hidden-word-bible-lessons' ); ?></button>
			</p>
			<template id="thw-church-subject-rule-template">
				<?php
				self::render_church_subject_rule_row(
					'__INDEX__',
					array(
						'enabled'      => true,
						'topic'        => '',
						'custom_label' => '',
						'keywords'     => '',
						'stance'       => 'pastoral',
						'rules'        => '',
					),
					$topic_choices,
					$stances
				);
				?>
			</template>
		</div>
		<?php
	}

	/**
	 * One church subject rule row.
	 *
	 * @param int|string               $index         Row index.
	 * @param array<string, mixed>     $row           Row data.
	 * @param array<string, string>    $topic_choices Topic choices.
	 * @param array<int, string>       $stances       Stance choices.
	 */
	private static function render_church_subject_rule_row( $index, $row, $topic_choices, $stances ) {
		$topic        = isset( $row['topic'] ) ? (string) $row['topic'] : '';
		$custom_label = isset( $row['custom_label'] ) ? (string) $row['custom_label'] : '';
		$keywords     = isset( $row['keywords'] ) ? (string) $row['keywords'] : '';
		$stance       = isset( $row['stance'] ) ? (string) $row['stance'] : 'pastoral';
		$rules        = isset( $row['rules'] ) ? (string) $row['rules'] : '';
		$enabled      = ! isset( $row['enabled'] ) || ! empty( $row['enabled'] );
		$name_base    = 'thw_ai_church_subject_rules[' . $index . ']';
		?>
		<tr class="thw-church-subject-rules__row">
			<td>
				<input type="hidden" name="<?php echo esc_attr( $name_base ); ?>[enabled]" value="0" />
				<input type="checkbox" name="<?php echo esc_attr( $name_base ); ?>[enabled]" value="1" <?php checked( $enabled ); ?> />
			</td>
			<td>
				<select name="<?php echo esc_attr( $name_base ); ?>[topic]" class="thw-church-subject-rules__topic">
					<option value="" <?php selected( $topic, '' ); ?>><?php esc_html_e( 'Custom…', 'hidden-word-bible-lessons' ); ?></option>
					<?php foreach ( $topic_choices as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $topic, $slug ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<input type="text" class="regular-text thw-church-subject-rules__custom-label" name="<?php echo esc_attr( $name_base ); ?>[custom_label]" value="<?php echo esc_attr( $custom_label ); ?>" placeholder="<?php esc_attr_e( 'Label (required for Custom)', 'hidden-word-bible-lessons' ); ?>" />
			</td>
			<td>
				<input type="text" class="regular-text thw-church-subject-rules__keywords" name="<?php echo esc_attr( $name_base ); ?>[keywords]" value="<?php echo esc_attr( $keywords ); ?>" placeholder="<?php esc_attr_e( 'comma, separated', 'hidden-word-bible-lessons' ); ?>" />
				<p class="description"><?php esc_html_e( 'Required for Custom; optional extras for presets.', 'hidden-word-bible-lessons' ); ?></p>
			</td>
			<td>
				<select name="<?php echo esc_attr( $name_base ); ?>[stance]">
					<?php foreach ( $stances as $choice ) : ?>
						<option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $stance, $choice ); ?>><?php echo esc_html( $choice ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<textarea name="<?php echo esc_attr( $name_base ); ?>[rules]" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Our church’s policy…', 'hidden-word-bible-lessons' ); ?>"><?php echo esc_textarea( $rules ); ?></textarea>
			</td>
			<td>
				<button type="button" class="button-link-delete thw-church-subject-rules__remove"><?php esc_html_e( 'Remove', 'hidden-word-bible-lessons' ); ?></button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Bible API test / translation diagnosis panel.
	 */
	private static function render_bible_api_test_panel() {
		$choices = array(
			'nlt'  => __( 'New Living Translation (NLT)', 'hidden-word-bible-lessons' ),
			'esv'  => __( 'English Standard Version (ESV)', 'hidden-word-bible-lessons' ),
			'nasb' => __( 'New American Standard Bible (NASB)', 'hidden-word-bible-lessons' ),
			'niv'  => __( 'New International Version (NIV)', 'hidden-word-bible-lessons' ),
			'csb'  => __( 'Christian Standard Bible (CSB)', 'hidden-word-bible-lessons' ),
			'kjv'  => __( 'King James Version (KJV)', 'hidden-word-bible-lessons' ),
			'bsb'  => __( 'Berean Standard Bible (BSB)', 'hidden-word-bible-lessons' ),
		);
		?>
			<h3><?php esc_html_e( 'Test keys & debug translations', 'hidden-word-bible-lessons' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Runs John 3:16 and Genesis 3 against the selected translation. For Open Book licenses (NLT, NIV, NASB) from api.bible, use Test API.Bible key — not Test YouVersion key. YouVersion and API.Bible are different services with different keys.', 'hidden-word-bible-lessons' ); ?>
			</p>
			<p>
				<label for="thw_bible_api_test_translation"><strong><?php esc_html_e( 'Translation to test', 'hidden-word-bible-lessons' ); ?></strong></label>
				<select id="thw_bible_api_test_translation">
					<?php foreach ( $choices as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, 'nlt' ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button button-primary" data-thw-diagnose-translation style="margin-left: 8px;">
					<?php esc_html_e( 'Diagnose Bible reader chain', 'hidden-word-bible-lessons' ); ?>
				</button>
			</p>
			<div class="thw-bible-api-test__output" aria-live="polite"></div>
		<?php
	}

	/**
	 * Show which translations are active and what is still required.
	 */
	private static function render_translation_status() {
		$rows = self::get_translation_source_rows();

		echo '<div class="notice notice-info inline" style="margin: 0 0 1em; padding: 12px;">';
		echo '<p><strong>' . esc_html__( 'Translation status', 'hidden-word-bible-lessons' ) . '</strong></p>';
		if ( empty( $rows ) ) {
			echo '<p>' . esc_html__( 'No translations available.', 'hidden-word-bible-lessons' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat striped" style="max-width: 720px;">';
		echo '<thead><tr>';
		echo '<th scope="col">' . esc_html__( 'Translation', 'hidden-word-bible-lessons' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Source', 'hidden-word-bible-lessons' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr>';
			echo '<td>' . esc_html( $row['label'] ) . '</td>';
			echo '<td>' . esc_html( $row['source'] ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Bundled NIV/KJV/WEB always load offline first. Premium BYOK providers fill gaps and add extra translations.', 'hidden-word-bible-lessons' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Build translation labels with their primary verse-text source.
	 *
	 * @return array<int, array{slug:string,label:string,source:string}>
	 */
	public static function get_translation_source_rows() {
		if ( ! class_exists( 'HWBL_Translation_Service' ) ) {
			return array();
		}

		$labels = HWBL_Translation_Service::instance()->get_supported_translations();
		if ( ! is_array( $labels ) || empty( $labels ) ) {
			return array();
		}

		$rows = array();
		foreach ( $labels as $slug => $label ) {
			$rows[] = array(
				'slug'   => (string) $slug,
				'label'  => is_string( $label ) ? $label : strtoupper( (string) $slug ),
				'source' => self::resolve_translation_source_label( (string) $slug ),
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				return strcasecmp( $a['label'], $b['label'] );
			}
		);

		return $rows;
	}

	/**
	 * Primary provider label for a translation slug.
	 *
	 * @param string $slug Translation slug.
	 * @return string
	 */
	public static function resolve_translation_source_label( $slug ) {
		$slug = strtolower( sanitize_key( $slug ) );

		$bundled = array( 'niv', 'kjv', 'web' );
		if ( in_array( $slug, $bundled, true ) ) {
			return __( 'Bundled', 'hidden-word-bible-lessons' );
		}

		if ( class_exists( 'HWBL_HelloAO_Provider' ) && HWBL_HelloAO_Provider::is_enabled() && HWBL_HelloAO_Provider::get_helloao_id( $slug ) ) {
			return __( 'Hello AO', 'hidden-word-bible-lessons' );
		}

		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() ) {
			$biblia = THW_Premium_Biblia::add_translations( array() );
			if ( isset( $biblia[ $slug ] ) ) {
				return __( 'Biblia', 'hidden-word-bible-lessons' );
			}
		}

		if ( class_exists( 'THW_Premium_YouVersion' ) && THW_Premium_YouVersion::is_available() ) {
			$youversion = THW_Premium_YouVersion::add_translations( array() );
			if ( isset( $youversion[ $slug ] ) ) {
				return __( 'YouVersion', 'hidden-word-bible-lessons' );
			}
		}

		if ( class_exists( 'THW_Premium_API_Bible' ) && THW_Premium_License::is_licensed() && THW_Premium_API_Bible::get_api_key() ) {
			$api = THW_Premium_API_Bible::add_translations( array() );
			if ( isset( $api[ $slug ] ) ) {
				return __( 'API.Bible', 'hidden-word-bible-lessons' );
			}
		}

		return __( 'Unavailable', 'hidden-word-bible-lessons' );
	}
}
