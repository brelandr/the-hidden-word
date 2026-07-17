<?php
/**
 * PWA service worker registration and offline cache hints (Phase 5).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_PWA
 */
class HWBL_PWA {

	/**
	 * Initialize PWA hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_service_worker' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register lightweight service worker for lesson pages.
	 */
	public static function register_service_worker() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script(
			'hwbl-pwa',
			HWBL_PLUGIN_URL . 'public/js/pwa-register.js',
			array(),
			HWBL_VERSION,
			true
		);

		wp_localize_script(
			'hwbl-pwa',
			'hwblPwa',
			array(
				'swUrl'   => HWBL_PLUGIN_URL . 'public/sw.js',
				'version' => HWBL_VERSION,
			)
		);
	}

	/**
	 * Offline due-queue snapshot for logged-in users.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/memorize/offline-pack',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_offline_pack' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}

	/**
	 * GET minimal offline memorization pack.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_offline_pack() {
		if ( ! class_exists( 'HWBL_Memorization_SRS' ) ) {
			return new WP_REST_Response( array( 'due' => array() ) );
		}

		$response = HWBL_Memorization_SRS::rest_review_queue();
		$data     = $response instanceof WP_REST_Response ? $response->get_data() : array();

		return new WP_REST_Response(
			array(
				'generated' => wp_date( 'c' ),
				'due'       => isset( $data['due'] ) ? $data['due'] : array(),
				'streak'    => isset( $data['streak'] ) ? $data['streak'] : array(),
			)
		);
	}
}
