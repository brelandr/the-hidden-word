<?php
/**
 * Study finder REST registration (extracted from AI study finder).
 *
 * Registers POST routes that accept a topic/keywords payload and return
 * AI-assisted Scripture guidance via THW_Premium_AI_Study_Finder.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Study_REST
 */
class THW_Premium_Study_REST {

	/**
	 * Register study search REST routes (hwbl + legacy thw namespaces).
	 *
	 * Permission mirrors thw_premium_current_user_can_use_ai_study_search() so
	 * disabled / logged-in / everyone audience settings are enforced at the
	 * permission_callback layer (not only inside the handler).
	 */
	public static function register_routes() {
		$route = array(
			'methods'             => 'POST',
			'callback'            => array( 'THW_Premium_AI_Study_Finder', 'rest_study_search' ),
			'permission_callback' => static function () {
				return function_exists( 'thw_premium_current_user_can_use_ai_study_search' )
					&& thw_premium_current_user_can_use_ai_study_search();
			},
			'args'                => array(
				'keywords'  => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'limit'     => array(
					'type'              => 'integer',
					'required'          => false,
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'tradition' => array(
					'type'              => 'string',
					'required'          => false,
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				),
			),
		);

		register_rest_route( 'hwbl/v1', '/study-search', $route );
		register_rest_route( 'thw/v1', '/study-search', $route );
	}
}
