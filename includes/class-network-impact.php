<?php
/**
 * Hub network impact stats (aggregated, anonymized).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Network_Impact
 */
class HWBL_Network_Impact {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_shortcode( 'hwbl_network_impact', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * REST.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/network/impact',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_impact' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Aggregated impact payload.
	 *
	 * @return array<string,int|string|bool>
	 */
	public static function get_stats() {
		$hub = class_exists( 'HWBL_Church_Network' ) && HWBL_Church_Network::is_hub();

		$testimonies = 0;
		if ( post_type_exists( 'hwbl_testimony' ) ) {
			$q = new WP_Query(
				array(
					'post_type'      => 'hwbl_testimony',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'date_query'     => array(
						array(
							'year'  => (int) wp_date( 'Y' ),
							'month' => (int) wp_date( 'n' ),
						),
					),
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => '_hwbl_testimony_public',
							'value' => '1',
						),
					),
				)
			);
			$testimonies = (int) $q->found_posts;
		}

		$gospel_views = class_exists( 'HWBL_Gospel_Response' ) ? HWBL_Gospel_Response::total_gospel_views() : 0;
		$responses    = class_exists( 'HWBL_Gospel_Response' ) ? HWBL_Gospel_Response::count_responses_this_month() : 0;

		$churches = 0;
		if ( $hub && post_type_exists( 'hwbl_church' ) ) {
			$cq = new WP_Query(
				array(
					'post_type'      => 'hwbl_church',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => '_hwbl_church_status',
							'value' => 'active',
						),
					),
				)
			);
			$churches = (int) $cq->found_posts;
		}

		return array(
			'hub'                       => $hub,
			'month'                     => wp_date( 'F Y' ),
			'testimonies_public_month'  => $testimonies,
			'gospel_page_views'         => $gospel_views,
			'gospel_responses_month'    => $responses,
			'registered_churches'       => $churches,
		);
	}

	/**
	 * GET /network/impact.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_impact() {
		return rest_ensure_response( self::get_stats() );
	}

	/**
	 * Shortcode [hwbl_network_impact] — hub-oriented display.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$stats = self::get_stats();
		if ( empty( $stats['hub'] ) ) {
			return '<p class="hwbl-network-impact">' . esc_html__( 'Impact stats are available on the Hidden Word network hub.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		ob_start();
		echo '<div class="hwbl-network-impact">';
		echo '<p class="hwbl-network-impact__period">' . esc_html( sprintf( /* translators: %s: month year */ __( 'Network impact — %s', 'hidden-word-bible-lessons' ), (string) $stats['month'] ) ) . '</p>';
		echo '<ul class="hwbl-network-impact__list">';
		echo '<li>' . esc_html( sprintf( /* translators: %d: count */ _n( '%d testimony shared publicly this month', '%d testimonies shared publicly this month', (int) $stats['testimonies_public_month'], 'hidden-word-bible-lessons' ), (int) $stats['testimonies_public_month'] ) ) . '</li>';
		echo '<li>' . esc_html( sprintf( /* translators: %d: count */ _n( '%d gospel presentation view', '%d gospel presentation views', (int) $stats['gospel_page_views'], 'hidden-word-bible-lessons' ), (int) $stats['gospel_page_views'] ) ) . '</li>';
		echo '<li>' . esc_html( sprintf( /* translators: %d: count */ _n( '%d gospel response this month', '%d gospel responses this month', (int) $stats['gospel_responses_month'], 'hidden-word-bible-lessons' ), (int) $stats['gospel_responses_month'] ) ) . '</li>';
		echo '<li>' . esc_html( sprintf( /* translators: %d: count */ _n( '%d registered church', '%d registered churches', (int) $stats['registered_churches'], 'hidden-word-bible-lessons' ), (int) $stats['registered_churches'] ) ) . '</li>';
		echo '</ul></div>';
		return (string) ob_get_clean();
	}
}
