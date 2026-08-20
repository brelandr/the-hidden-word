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
	 * Inline SW registration snippet for standalone share pages.
	 *
	 * @return string
	 */
	public static function standalone_register_script() {
		$sw = esc_url( HWBL_PLUGIN_URL . 'public/sw.js' );
		$ver = esc_attr( HWBL_VERSION );
		return '<script>(function(){if(!("serviceWorker" in navigator))return;navigator.serviceWorker.register("' . $sw . '?v=' . $ver . '",{scope:"/"}).catch(function(){});})();</script>';
	}

	/**
	 * Register lightweight service worker for lesson pages and evangelism shells.
	 */
	public static function register_service_worker() {
		$on_evangelism = is_singular() && (
			(bool) get_query_var( 'hwbl_gospel_share' ) ||
			(bool) get_query_var( 'hwbl_testimony_share' )
		);
		if ( ! is_user_logged_in() && ! $on_evangelism ) {
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
				'precache'=> array(
					'/gospel/',
					'/testimony/',
				),
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
				'permission_callback' => static function () {
					return current_user_can( 'read' );
				},
			)
		);
	}

	/**
	 * GET offline memorization pack with verse text for due cards.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_offline_pack() {
		if ( ! class_exists( 'HWBL_Memorization_SRS' ) ) {
			return new WP_REST_Response(
				array(
					'generated'   => wp_date( 'c' ),
					'due'         => array(),
					'new'         => array(),
					'streak'      => array(),
					'translation' => '',
				)
			);
		}

		$response = HWBL_Memorization_SRS::rest_review_queue();
		$data     = $response instanceof WP_REST_Response ? $response->get_data() : array();
		$due      = isset( $data['due'] ) && is_array( $data['due'] ) ? $data['due'] : array();
		$new      = isset( $data['new'] ) && is_array( $data['new'] ) ? $data['new'] : array();

		$translation = '';
		if ( class_exists( 'HWBL_User_Preferences' ) ) {
			$translation = sanitize_key( (string) HWBL_User_Preferences::resolve_translation() );
		}
		if ( ! $translation ) {
			$translation = sanitize_key( (string) get_option( 'hwbl_active_translation', 'kjv' ) );
		}

		$pack_due = self::enrich_queue_items( array_slice( $due, 0, 25 ), $translation );
		$pack_new = self::enrich_queue_items( array_slice( $new, 0, 10 ), $translation );

		return new WP_REST_Response(
			array(
				'generated'   => wp_date( 'c' ),
				'due'         => $pack_due,
				'new'         => $pack_new,
				'streak'      => isset( $data['streak'] ) ? $data['streak'] : array(),
				'translation' => $translation,
			)
		);
	}

	/**
	 * Attach verse text to queue items for offline practice.
	 *
	 * @param array<int, array<string, mixed>> $items       Queue items.
	 * @param string                           $translation Preferred translation.
	 * @return array<int, array<string, mixed>>
	 */
	private static function enrich_queue_items( $items, $translation ) {
		$enriched = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$lesson_id = isset( $item['lesson_id'] ) ? (int) $item['lesson_id'] : 0;
			$row       = $item;
			$row['verse_text'] = '';
			$row['translation'] = sanitize_key( (string) $translation );

			if ( $lesson_id > 0 && class_exists( 'HWBL_CPT_Lesson' ) ) {
				$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
				if ( is_array( $lesson ) && ! empty( $lesson['book_id'] ) && ! empty( $lesson['chapter'] ) && ! empty( $lesson['verse_start'] ) ) {
					$verse_end = ! empty( $lesson['verse_end'] ) ? (int) $lesson['verse_end'] : (int) $lesson['verse_start'];
					if ( class_exists( 'HWBL_Verse_Memorize' ) ) {
						$resolved = HWBL_Verse_Memorize::resolve_verse_text(
							(int) $lesson['book_id'],
							(int) $lesson['chapter'],
							(int) $lesson['verse_start'],
							$verse_end,
							$translation
						);
						$row['verse_text']  = isset( $resolved['text'] ) ? (string) $resolved['text'] : '';
						$row['translation'] = ! empty( $resolved['translation'] ) ? (string) $resolved['translation'] : $translation;
					} elseif ( class_exists( 'HWBL_Translation_Service' ) ) {
						$row['verse_text'] = (string) HWBL_Translation_Service::instance()->get_verse_text(
							(int) $lesson['book_id'],
							(int) $lesson['chapter'],
							(int) $lesson['verse_start'],
							$translation
						);
					}
				}

				// Custom-verse snapshot fallback.
				if ( '' === trim( (string) $row['verse_text'] ) && class_exists( 'HWBL_Verse_Memorize' ) ) {
					$stored = HWBL_Verse_Memorize::get_stored_verse_text( $lesson_id, $translation );
					if ( $stored ) {
						$row['verse_text'] = $stored;
					}
				}
			}

			$enriched[] = $row;
		}

		return $enriched;
	}
}
