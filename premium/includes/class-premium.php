<?php
/**
 * Premium plugin bootstrap.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium
 */
class THW_Premium {

	/**
	 * Initialize premium features.
	 */
	public static function init() {
		THW_Premium_Settings::init();

		// Always register so the page never shows a raw shortcode tag.
		THW_Premium_AI_Study_Finder::register_shortcode();
		THW_Premium_AI_Ask::register_shortcode();
		THW_Premium_Verse_Of_The_Day::register_shortcode();
		if ( class_exists( 'THW_Premium_Bible_Study_Card' ) ) {
			THW_Premium_Bible_Study_Card::register_shortcode();
		}

		$features_enabled = function_exists( 'hwbl_premium_features_enabled' )
			? hwbl_premium_features_enabled()
			: ( class_exists( 'THW_Premium_License' ) && THW_Premium_License::is_licensed() );

		if ( ! $features_enabled ) {
			add_action( 'admin_notices', array( __CLASS__, 'license_notice' ) );
			return;
		}

		THW_Premium_API_Bible::init();
		THW_Premium_Biblia::init();
		THW_Premium_YouVersion::init();
		THW_Premium_Scheduler::init();
		THW_Premium_Memorization::init();
		THW_Premium_Progress::init();
		THW_Premium_PDF_Export::init();
		THW_Premium_AI_Assist::init();
		THW_Premium_AI_Explain::init();
		THW_Premium_Bible_Reader_Explain::init();
		THW_Premium_Bible_Reader_Explain_Store::init();
		THW_Premium_Bible_Study_Card::init();
		THW_Premium_Explain_Preload::init();
		THW_Premium_Explain_Preload_Admin::init();
		THW_Premium_Explain_Packs::init();
		THW_Premium_Explain_Packs_Admin::init();
		THW_Premium_AI_Study_Finder::init();
		THW_Premium_AI_Ask::init();
		THW_Premium_Verse_Of_The_Day::init();
		THW_Premium_Votd_Explain_Store::init();
		THW_Premium_BuddyPress::init();
		THW_Premium_Audio::init();
		THW_Premium_Curriculum::init();
		THW_Premium_Digest::init();
		THW_Premium_Votd_Digest::init();
		THW_Premium_Cohort::init();
		THW_Premium_Video::init();
		THW_Premium_ICal_Export::init();
		THW_Premium_Track_Import::init();
		THW_Premium_Analytics::init();

		if ( THW_Premium_AI_Client::uses_core_ai() && ! THW_Premium_AI_Client::is_configured() ) {
			add_action( 'admin_notices', array( __CLASS__, 'ai_connector_notice' ) );
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'hwbl_lesson_render_before_tabs', array( __CLASS__, 'render_translation_switcher' ), 10, 1 );
		add_filter( 'hwbl_current_lesson_id', array( 'THW_Premium_Scheduler', 'filter_current_lesson_id' ), 10, 3 );
	}

	/**
	 * Whether premium public styles/scripts should load.
	 *
	 * @return bool
	 */
	private static function should_enqueue_premium_assets() {
		if ( self::should_enqueue_lesson_assets() ) {
			return true;
		}

		global $post;
		if ( ! $post ) {
			return is_active_widget( false, false, 'hwbl_verse_of_week', true );
		}

		return thw_premium_content_has_shortcode( $post->post_content, 'thw_subscribe' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_votd_subscribe' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_cohort_roster' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_my_progress' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_study_finder' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_ask_question' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_verse_study' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_verse_of_the_day' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_bible_com_votd' )
			|| thw_premium_content_has_shortcode( $post->post_content, 'thw_bible_reader' )
			|| has_shortcode( $post->post_content, 'hwbl_bible_reader' )
			|| ( function_exists( 'has_block' ) && has_block( 'hwbl/verse-study', $post ) )
			|| is_active_widget( false, false, 'hwbl_verse_of_week', true );
	}

	/**
	 * License notice — unused in the free WordPress.org build (always licensed).
	 */
	public static function license_notice() {
	}

	/**
	 * Prompt admins to configure WordPress AI Connectors when drafting is unavailable.
	 */
	public static function ai_connector_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'thw' ) ) {
			return;
		}

		echo '<div class="notice notice-info"><p>';
		printf(
			/* translators: 1: opening anchor, 2: closing anchor */
			esc_html__( 'Enable AI lesson drafting by configuring an AI provider under %1$sSettings → Connectors%2$s.', 'hidden-word-bible-lessons' ),
			'<a href="' . esc_url( THW_Premium_AI_Client::get_connectors_settings_url() ) . '">',
			'</a>'
		);
		echo '</p></div>';
	}

	/**
	 * Script handles that should load after the free-plugin preferences helper when available.
	 *
	 * @return array<int, string>
	 */
	private static function preference_script_deps() {
		return wp_script_is( 'hwbl-user-preferences', 'registered' )
			? array( 'hwbl-user-preferences' )
			: array();
	}

	/**
	 * Enqueue premium front-end assets.
	 */
	public static function enqueue_assets() {
		$pref_deps = self::preference_script_deps();

		wp_register_style(
			'thw-premium',
			THW_PREMIUM_URL . 'public/css/premium.css',
			array(),
			THW_PREMIUM_VERSION
		);

		wp_register_script(
			'thw-translation-switcher',
			THW_PREMIUM_URL . 'public/js/translation-switcher.js',
			$pref_deps,
			THW_PREMIUM_VERSION,
			true
		);

		wp_register_script(
			'thw-memorization-pro',
			THW_PREMIUM_URL . 'public/js/memorization-pro.js',
			array(),
			THW_PREMIUM_VERSION,
			true
		);

		wp_register_script(
			'thw-ai-explain',
			THW_PREMIUM_URL . 'public/js/ai-explain.js',
			$pref_deps,
			THW_PREMIUM_VERSION,
			true
		);

		wp_register_script(
			'thw-ai-study-finder',
			THW_PREMIUM_URL . 'public/js/ai-study-finder.js',
			$pref_deps,
			THW_PREMIUM_VERSION,
			true
		);

		wp_register_script(
			'thw-ai-ask',
			THW_PREMIUM_URL . 'public/js/ai-ask.js',
			$pref_deps,
			THW_PREMIUM_VERSION,
			true
		);

		if ( class_exists( 'THW_Premium_Bible_Study_Card' ) ) {
			wp_register_style(
				'hwbl-verse-study',
				THW_PREMIUM_URL . 'public/css/verse-study-card.css',
				array(),
				THW_PREMIUM_VERSION
			);
			wp_register_script(
				'hwbl-verse-study',
				THW_PREMIUM_URL . 'public/js/verse-study-card.js',
				$pref_deps,
				THW_PREMIUM_VERSION,
				true
			);
		}

		wp_localize_script(
			'thw-translation-switcher',
			'thwPremium',
			array(
				'restUrl' => esc_url_raw( rest_url( 'thw/v1/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_localize_script(
			'thw-memorization-pro',
			'thwPremium',
			array(
				'restUrl'        => esc_url_raw( rest_url( 'thw/v1/' ) ),
				'streakClaimUrl' => esc_url_raw(
					class_exists( 'HWBL_Memorization_SRS' )
						? rest_url( 'hwbl/v1/memorize/claim-streak' )
						: rest_url( 'thw/v1/claim-streak' )
				),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'loggedIn'       => is_user_logged_in(),
				'streakClaimed'  => is_user_logged_in()
					? (bool) get_user_meta(
						get_current_user_id(),
						class_exists( 'HWBL_Memorization_SRS' ) ? HWBL_Memorization_SRS::STREAK_CLAIMED : THW_Premium_Progress::STREAK_CLAIMED_META_KEY,
						true
					)
					: false,
				/* translators: %d: number of streak days */
				'claimPrompt'    => __( 'Save your %d-day browser streak to your account?', 'hidden-word-bible-lessons' ),
				'claimSuccess'   => __( 'Streak saved to your account!', 'hidden-word-bible-lessons' ),
				'memorizedLabel' => __( 'Memorized ✓', 'hidden-word-bible-lessons' ),
				'sharedLabel'    => __( 'Shared ✓', 'hidden-word-bible-lessons' ),
				'hideFiveWords'  => __( 'Hide 5 Words', 'hidden-word-bible-lessons' ),
			)
		);

		if ( self::should_enqueue_premium_assets() ) {
			wp_enqueue_style( 'thw-premium' );
			wp_enqueue_script( 'thw-translation-switcher' );
			wp_enqueue_script( 'thw-memorization-pro' );
		}

		if (
			is_user_logged_in()
			&& thw_premium_ai_frontend_available()
			&& (
				self::should_enqueue_lesson_assets()
				|| THW_Premium_AI_Explain::needs_assets()
				|| self::has_bible_reader_shortcode()
			)
		) {
			wp_enqueue_style( 'thw-premium' );
			wp_enqueue_script( 'thw-ai-explain' );
			wp_localize_script(
				'thw-ai-explain',
				'thwAiExplain',
				array(
					'restUrl'             => esc_url_raw( rest_url( 'thw/v1/explain' ) ),
					'nonce'               => wp_create_nonce( 'wp_rest' ),
					'loading'             => __( 'Generating explanation…', 'hidden-word-bible-lessons' ),
					'error'               => __( 'Could not generate an explanation. Please try again.', 'hidden-word-bible-lessons' ),
					'rateLimit'           => __( 'You have reached the hourly limit for AI explanations. Please try again later.', 'hidden-word-bible-lessons' ),
					'noPanel'             => __( 'Could not open the explanation panel. Refresh the page and try again.', 'hidden-word-bible-lessons' ),
					'userTradition'       => thw_premium_show_tradition_select(),
					'traditionStorageKey' => 'thw_ai_tradition_preset',
					'complianceFlagged'   => __( 'We could not generate an explanation that follows your selected faith tradition’s guidelines. Please review carefully and compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
				)
			);
		}

		global $post;
		if ( $post && thw_premium_content_has_shortcode( $post->post_content, 'thw_study_finder' ) ) {
			$ai_unavailable = thw_premium_ai_frontend_unavailable_reason();
			wp_enqueue_style( 'thw-premium' );
			wp_enqueue_script( 'thw-ai-study-finder' );
			$ai_note = thw_premium_show_tradition_select()
				? __( 'AI Scripture guidance for your topic, shaped by the faith tradition you selected.', 'hidden-word-bible-lessons' )
				: __( 'AI Scripture guidance for your topic, shaped by your site’s faith tradition settings.', 'hidden-word-bible-lessons' );
			wp_localize_script(
				'thw-ai-study-finder',
				'thwStudyFinder',
				array(
					'restUrl'             => esc_url_raw( rest_url( 'thw/v1/study-search' ) ),
					'nonce'               => wp_create_nonce( 'wp_rest' ),
					'loggedIn'            => is_user_logged_in(),
					'aiEnabled'           => '' === $ai_unavailable,
					'audience'            => thw_premium_get_ai_study_audience(),
					'loading'             => __( 'Finding Scripture for your topic…', 'hidden-word-bible-lessons' ),
					'empty'               => __( 'No Scripture guidance found. Try a different topic.', 'hidden-word-bible-lessons' ),
					'error'               => __( 'Search failed. Please try again.', 'hidden-word-bible-lessons' ),
					'aiNote'              => $ai_note,
					'loginRequired'       => __( 'Please log in to search Scripture by topic.', 'hidden-word-bible-lessons' ),
					'disabled'            => __( 'AI topic Scripture search is disabled on this site.', 'hidden-word-bible-lessons' ),
					'aiUnavailable'       => $ai_unavailable
						? $ai_unavailable
						: __( 'AI is not enabled or configured on this site.', 'hidden-word-bible-lessons' ),
					'parseFailed'         => __( 'The AI response could not be read. Please try again.', 'hidden-word-bible-lessons' ),
					'userTradition'       => thw_premium_show_tradition_select(),
					'traditionStorageKey' => 'thw_ai_tradition_preset',
					'disclaimer'          => __( 'AI-generated guidance based on your site’s faith tradition rules. Always compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
					'complianceFlagged'   => __( 'We could not generate Scripture guidance that follows your selected faith tradition’s guidelines. Please review carefully and compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
					'citationsLabel'      => __( 'Faith tradition sources:', 'hidden-word-bible-lessons' ),
					'lessonsHeading'      => __( 'Related lessons', 'hidden-word-bible-lessons' ),
				)
			);
		}

		if (
			class_exists( 'THW_Premium_Bible_Study_Card' )
			&& (
				THW_Premium_Bible_Study_Card::needs_assets()
				|| ( $post && thw_premium_content_has_shortcode( $post->post_content, 'thw_verse_study' ) )
			)
		) {
			THW_Premium_Bible_Study_Card::enqueue_assets();
		}

		if ( $post && thw_premium_content_has_shortcode( $post->post_content, 'thw_ask_question' ) ) {
			$ai_unavailable = thw_premium_ai_frontend_unavailable_reason();
			wp_enqueue_style( 'thw-premium' );
			wp_enqueue_script( 'thw-ai-ask' );
			$ai_note = thw_premium_show_tradition_select()
				? __( 'AI answer shaped by the faith tradition you selected.', 'hidden-word-bible-lessons' )
				: __( 'AI answer shaped by your site’s faith tradition settings.', 'hidden-word-bible-lessons' );
			wp_localize_script(
				'thw-ai-ask',
				'thwAiAsk',
				array(
					'restUrl'             => esc_url_raw( rest_url( 'thw/v1/ask' ) ),
					'nonce'               => wp_create_nonce( 'wp_rest' ),
					'loggedIn'            => is_user_logged_in(),
					'aiEnabled'           => '' === $ai_unavailable,
					'audience'            => thw_premium_get_ai_ask_audience(),
					'loading'             => __( 'Thinking through your question…', 'hidden-word-bible-lessons' ),
					'empty'               => __( 'No answer was generated. Please try again.', 'hidden-word-bible-lessons' ),
					'error'               => __( 'Could not answer that question. Please try again.', 'hidden-word-bible-lessons' ),
					'aiNote'              => $ai_note,
					'loginRequired'       => __( 'Please log in to ask a Bible question.', 'hidden-word-bible-lessons' ),
					'disabled'            => __( 'Ask a Question is disabled on this site.', 'hidden-word-bible-lessons' ),
					'aiUnavailable'       => $ai_unavailable
						? $ai_unavailable
						: __( 'AI is not enabled or configured on this site.', 'hidden-word-bible-lessons' ),
					'rateLimit'           => __( 'Hourly Ask a Question limit reached. Please try again later.', 'hidden-word-bible-lessons' ),
					'userTradition'       => thw_premium_show_tradition_select(),
					'traditionStorageKey' => 'thw_ai_tradition_preset',
					'disclaimer'          => __( 'AI-generated guidance based on your site’s faith tradition rules. Always compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
					'complianceFlagged'   => __( 'We could not generate an answer that follows your selected faith tradition’s guidelines. Please review carefully and compare with Scripture and trusted teachers.', 'hidden-word-bible-lessons' ),
					'citationsLabel'      => __( 'Faith tradition sources:', 'hidden-word-bible-lessons' ),
					'lessonsHeading'      => __( 'Related lessons', 'hidden-word-bible-lessons' ),
				)
			);
		}
	}

	/**
	 * Whether the current page includes the Bible reader shortcode.
	 *
	 * @return bool
	 */
	public static function has_bible_reader_shortcode() {
		global $post;
		if ( ! $post ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'hwbl_bible_reader' )
			|| has_shortcode( $post->post_content, 'thw_bible_reader' );
	}

	/**
	 * Whether the current page loads a full lesson (shortcode, block, or CPT).
	 *
	 * @return bool
	 */
	public static function should_enqueue_lesson_assets() {
		if ( is_singular( 'hwbl_lesson' ) ) {
			return true;
		}

		global $post;
		if ( ! $post ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'hwbl_lesson' )
			|| has_shortcode( $post->post_content, 'thw_lesson' )
			|| has_shortcode( $post->post_content, 'hwbl_verse_of_week' )
			|| has_shortcode( $post->post_content, 'thw_verse_of_week' )
			|| ( function_exists( 'has_block' ) && ( has_block( 'hwbl/lesson', $post ) || has_block( 'thw/lesson', $post ) ) );
	}

	/**
	 * Render translation switcher before lesson tabs.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public static function render_translation_switcher( $lesson_id ) {
		unset( $lesson_id );
		$translations = HWBL_Translation_Service::instance()->get_supported_translations();
		$active       = class_exists( 'HWBL_User_Preferences' )
			? HWBL_User_Preferences::resolve_translation( $translations )
			: sanitize_key( (string) get_option( 'hwbl_active_translation', 'niv' ) );

		if ( count( $translations ) <= 1 ) {
			return;
		}
		?>
		<div class="thw-translation-switcher">
			<label for="thw-translation-select"><?php esc_html_e( 'Translation:', 'hidden-word-bible-lessons' ); ?></label>
			<select id="thw-translation-select" class="thw-translation-select" data-thw-translation-select>
				<?php foreach ( $translations as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $active, $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}
}
