<?php
/**
 * Expanded option cleanup for unified plugin uninstall (Phase 4).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Uninstall_Keys
 */
class HWBL_Uninstall_Keys {

	/**
	 * Premium option keys to remove when integrated plugin is uninstalled.
	 *
	 * @return string[]
	 */
	public static function premium_option_keys() {
		return array(
			'thw_premium_github_token_encrypted',
			'thw_api_bible_key',
			'thw_biblia_api_key',
			'thw_youversion_app_key',
			'thw_manual_lesson_id',
			'thw_custom_track',
			'thw_digest_subscribers',
			'thw_digest_enabled',
			'thw_digest_subject',
			'thw_digest_from_name',
			'thw_digest_last_slot',
			'thw_votd_digest_subscribers',
			'thw_votd_digest_enabled',
			'thw_votd_digest_subject',
			'thw_votd_digest_from_name',
			'thw_votd_digest_include_explain',
			'thw_votd_digest_generate_explain',
			'thw_votd_digest_include_image',
			'thw_votd_digest_allow_tradition',
			'thw_votd_digest_tradition',
			'thw_votd_digest_last_day',
			'thw_votd_ai_explain',
			'thw_votd_show_image',
			'thw_votd_source',
			'thw_votd_translation',
			'thw_votd_explain_delivery',
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
			'thw_ai_enabled',
			'thw_ai_provider',
			'thw_ai_model',
			'thw_ai_openai_key',
			'thw_ai_anthropic_key',
			'thw_active_translation',
			'thw_schedule_mode',
		);
	}

	/**
	 * Delete all registered premium options.
	 */
	public static function delete_premium_options() {
		foreach ( self::premium_option_keys() as $key ) {
			delete_option( $key );
		}

		delete_transient( 'thw_premium_remote_version' );
		wp_clear_scheduled_hook( 'thw_premium_daily_license_check' );
		wp_clear_scheduled_hook( 'thw_premium_lesson_digest' );
		wp_clear_scheduled_hook( 'thw_premium_votd_digest' );
		wp_clear_scheduled_hook( 'thw_premium_votd_daily_refresh' );
	}
}
