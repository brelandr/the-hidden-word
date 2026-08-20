<?php
/**
 * Companion REST: list saved VOTD / Bible explanations.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Explains_Rest
 */
class THW_Premium_Explains_Rest {

	/**
	 * Register routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register GET hwbl/v1/explains (+ thw alias).
	 */
	public static function register_routes() {
		$args = array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'rest_list' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'type'         => array(
					'type'              => 'string',
					'default'           => 'all',
					'sanitize_callback' => 'sanitize_key',
				),
				'page'         => array(
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => 'absint',
				),
				'per_page'     => array(
					'type'              => 'integer',
					'default'           => 20,
					'sanitize_callback' => 'absint',
				),
				'translation'  => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				),
				'day'          => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		);

		register_rest_route( 'hwbl/v1', '/explains', $args );
		register_rest_route( 'thw/v1', '/explains', $args );
	}

	/**
	 * List published explain posts for the companion.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_list( $request ) {
		$type        = sanitize_key( (string) $request->get_param( 'type' ) );
		$page        = max( 1, (int) $request->get_param( 'page' ) );
		$per_page    = min( 50, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$translation = sanitize_key( (string) $request->get_param( 'translation' ) );
		$day         = sanitize_text_field( (string) $request->get_param( 'day' ) );

		$post_types = array();
		if ( 'votd' === $type || 'all' === $type ) {
			$post_types[] = 'thw_votd_explain';
		}
		if ( 'bible' === $type || 'all' === $type ) {
			$post_types[] = 'thw_bible_explain';
		}
		if ( empty( $post_types ) ) {
			$post_types = array( 'thw_votd_explain', 'thw_bible_explain' );
		}

		$meta_query = array();
		if ( '' !== $translation ) {
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'   => '_thw_votd_translation',
					'value' => $translation,
				),
				array(
					'key'   => '_thw_bible_explain_translation',
					'value' => $translation,
				),
			);
		}
		if ( '' !== $day && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) ) {
			$meta_query[] = array(
				'key'   => '_thw_votd_day',
				'value' => $day,
			);
			// Day filter only applies to VOTD explains.
			$post_types = array( 'thw_votd_explain' );
		}

		$query_args = array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		);
		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$query = new WP_Query( $query_args );
		$items = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$items[] = self::serialize_post( $post );
		}

		return new WP_REST_Response(
			array(
				'items'       => $items,
				'page'        => $page,
				'per_page'    => $per_page,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * Serialize one explain post for the companion.
	 *
	 * @param WP_Post $post Post.
	 * @return array<string, mixed>
	 */
	private static function serialize_post( WP_Post $post ) {
		$is_votd = 'thw_votd_explain' === $post->post_type;
		$day     = $is_votd ? (string) get_post_meta( $post->ID, '_thw_votd_day', true ) : '';
		$trans   = $is_votd
			? (string) get_post_meta( $post->ID, '_thw_votd_translation', true )
			: (string) get_post_meta( $post->ID, '_thw_bible_explain_translation', true );
		$ref     = $is_votd
			? (string) get_post_meta( $post->ID, '_thw_votd_reference', true )
			: (string) get_post_meta( $post->ID, '_thw_bible_explain_reference', true );

		$label = '';
		if ( $trans && class_exists( 'HWBL_Translation_Service' ) ) {
			$svc = HWBL_Translation_Service::instance();
			if ( method_exists( $svc, 'get_translation_label' ) ) {
				$label = (string) $svc->get_translation_label( $trans );
			}
		}
		if ( '' === $label && $trans ) {
			$label = strtoupper( $trans );
		}

		$excerpt = wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 40 );

		return array(
			'id'                => (int) $post->ID,
			'type'              => $is_votd ? 'votd' : 'bible',
			'title'             => (string) get_the_title( $post ),
			'reference'         => $ref,
			'day'               => $day,
			'translation'       => $trans,
			'translation_label' => $label,
			'excerpt'           => $excerpt,
			'postUrl'           => (string) get_permalink( $post ),
			'date'              => get_the_date( 'c', $post ),
		);
	}
}
