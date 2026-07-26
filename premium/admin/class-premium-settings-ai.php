<?php
/**
 * AI settings tab (extracted from premium settings).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_Settings_AI
 */
class THW_Premium_Settings_AI {

	/**
	 * Option keys managed by the AI settings group.
	 *
	 * @return string[]
	 */
	public static function option_keys() {
		return array(
			'thw_ai_enabled',
			'thw_ai_provider',
			'thw_ai_model',
			'thw_ai_openai_key',
			'thw_ai_anthropic_key',
			'thw_ai_explain_rules',
			'thw_ai_study_rules',
			'thw_ai_ask_rules',
		);
	}
}
