<?php
/**
 * Testimony custom post type + REST + public share page.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_CPT_Testimony
 */
class HWBL_CPT_Testimony {

	const POST_TYPE      = 'hwbl_testimony';
	const META_BEFORE    = '_hwbl_testimony_before';
	const META_TURNING   = '_hwbl_testimony_turning_point';
	const META_AFTER     = '_hwbl_testimony_after';
	const META_PUBLIC    = '_hwbl_testimony_public';
	const OPT_REWRITE    = 'hwbl_testimony_rewrite_flushed';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_share_page' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register CPT (not publicly queryable; share via rewrite).
	 */
	public static function register_post_type() {
		$can_translate = (bool) did_action( 'init' );
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => $can_translate ? __( 'Testimonies', 'hidden-word-bible-lessons' ) : 'Testimonies',
					'singular_name' => $can_translate ? __( 'Testimony', 'hidden-word-bible-lessons' ) : 'Testimony',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=hwbl_lesson',
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'author' ),
				'has_archive'         => false,
			)
		);
	}

	/**
	 * Share rewrite /testimony/{id}.
	 */
	public static function register_rewrite() {
		add_rewrite_rule( '^testimony/([0-9]+)/?$', 'index.php?hwbl_testimony_share=$matches[1]', 'top' );
		if ( (string) get_option( self::OPT_REWRITE ) !== '1' ) {
			flush_rewrite_rules( false );
			update_option( self::OPT_REWRITE, '1', false );
		}
	}

	/**
	 * @param array<int, string> $vars Vars.
	 * @return array<int, string>
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'hwbl_testimony_share';
		return $vars;
	}

	/**
	 * Public share URL.
	 *
	 * @param int $id Testimony ID.
	 * @return string
	 */
	public static function share_url( $id ) {
		return trailingslashit( home_url( '/testimony/' . (int) $id ) );
	}

	/**
	 * REST routes.
	 */
	public static function register_routes() {
		$auth = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};

		register_rest_route(
			'hwbl/v1',
			'/testimony',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_get_own' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_save' ),
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/testimony/(?P<id>\d+)/share',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_share' ),
				'permission_callback' => $auth,
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Payload for a testimony post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_payload( $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}
		$is_public = (bool) get_post_meta( $post_id, self::META_PUBLIC, true );
		return array(
			'id'            => (int) $post_id,
			'before'        => (string) get_post_meta( $post_id, self::META_BEFORE, true ),
			'turning_point' => (string) get_post_meta( $post_id, self::META_TURNING, true ),
			'after'         => (string) get_post_meta( $post_id, self::META_AFTER, true ),
			'is_public'     => $is_public,
			'share_url'     => $is_public ? self::share_url( $post_id ) : '',
		);
	}

	/**
	 * GET own testimony (latest for user).
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_get_own() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'author'         => get_current_user_id(),
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'private', 'draft' ),
			)
		);
		if ( ! $posts ) {
			return rest_ensure_response(
				array(
					'id'            => 0,
					'before'        => '',
					'turning_point' => '',
					'after'         => '',
					'is_public'     => false,
					'share_url'     => '',
				)
			);
		}
		return rest_ensure_response( self::get_payload( $posts[0]->ID ) );
	}

	/**
	 * POST create/update own testimony.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_save( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : $request->get_params();
		$before = isset( $params['before'] ) ? sanitize_textarea_field( (string) $params['before'] ) : '';
		$turn   = isset( $params['turning_point'] ) ? sanitize_textarea_field( (string) $params['turning_point'] ) : '';
		$after  = isset( $params['after'] ) ? sanitize_textarea_field( (string) $params['after'] ) : '';
		$public = ! empty( $params['is_public'] ) || ! empty( $params['public'] );

		$user_id = get_current_user_id();
		$existing = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'author'         => $user_id,
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'private', 'draft' ),
				'fields'         => 'ids',
			)
		);
		$post_id = $existing ? (int) $existing[0] : 0;
		$user    = get_userdata( $user_id );
		$title   = sprintf(
			/* translators: %s: display name */
			__( 'Testimony — %s', 'hidden-word-bible-lessons' ),
			$user ? $user->display_name : (string) $user_id
		);

		if ( $post_id ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_title'  => $title,
					'post_status' => $public ? 'publish' : 'private',
				)
			);
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'   => self::POST_TYPE,
					'post_status' => $public ? 'publish' : 'private',
					'post_title'  => $title,
					'post_author' => $user_id,
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
		}

		update_post_meta( $post_id, self::META_BEFORE, $before );
		update_post_meta( $post_id, self::META_TURNING, $turn );
		update_post_meta( $post_id, self::META_AFTER, $after );
		update_post_meta( $post_id, self::META_PUBLIC, $public ? 1 : 0 );

		return rest_ensure_response( self::get_payload( $post_id ) );
	}

	/**
	 * POST make public and return share URL.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_share( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'hwbl_testimony_missing', __( 'Testimony not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'hwbl_forbidden', __( 'You cannot share this testimony.', 'hidden-word-bible-lessons' ), array( 'status' => 403 ) );
		}
		update_post_meta( $post_id, self::META_PUBLIC, 1 );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		return rest_ensure_response(
			array(
				'ok'        => true,
				'share_url' => self::share_url( $post_id ),
				'testimony' => self::get_payload( $post_id ),
			)
		);
	}

	/**
	 * Render public share HTML when public.
	 */
	public static function maybe_render_share_page() {
		$id = (int) get_query_var( 'hwbl_testimony_share' );
		if ( $id < 1 ) {
			return;
		}
		$payload = self::get_payload( $id );
		if ( ! $payload || empty( $payload['is_public'] ) ) {
			status_header( 404 );
			nocache_headers();
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . esc_html__( 'Not found', 'hidden-word-bible-lessons' ) . '</title></head><body><p>' . esc_html__( 'This testimony is not available.', 'hidden-word-bible-lessons' ) . '</p></body></html>';
			exit;
		}

		$site      = get_bloginfo( 'name' );
		$primary   = (string) get_option( 'hwbl_app_primary_color', '#1a365d' );
		$share_url = self::share_url( $id );
		$wa        = class_exists( 'HWBL_Share_Links' ) ? HWBL_Share_Links::whatsapp_url( $share_url, __( 'A story of grace', 'hidden-word-bible-lessons' ) ) : '';
		$sms       = class_exists( 'HWBL_Share_Links' ) ? HWBL_Share_Links::sms_url( $share_url, __( 'A story of grace', 'hidden-word-bible-lessons' ) ) : '';
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8" />';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
		echo '<title>' . esc_html( sprintf( /* translators: %s: site name */ __( 'Testimony — %s', 'hidden-word-bible-lessons' ), $site ) ) . '</title>';
		echo '<style>
			body{font-family:Georgia,serif;margin:0;background:#f7f5f0;color:#1a1a1a;line-height:1.55}
			.wrap{max-width:40rem;margin:0 auto;padding:2rem 1.25rem 3rem}
			h1{font-size:1.75rem;color:' . esc_attr( $primary ?: '#1a365d' ) . ';margin:0 0 1.5rem}
			h2{font-size:1.05rem;text-transform:uppercase;letter-spacing:.04em;margin:1.5rem 0 .4rem;color:#555}
			p{white-space:pre-wrap;margin:0}
			.cta,.btn{display:inline-block;margin:.35rem .35rem 0 0;padding:.75rem 1.1rem;background:' . esc_attr( $primary ?: '#1a365d' ) . ';color:#fff;text-decoration:none;border-radius:6px}
			.btn-secondary{background:transparent;color:' . esc_attr( $primary ?: '#1a365d' ) . ';border:1px solid currentColor}
			.share-row{margin:1.5rem 0;display:flex;flex-wrap:wrap;gap:.5rem}
			.site{margin-top:2rem;font-size:.9rem;opacity:.7}
		</style></head><body><div class="wrap">';
		echo '<h1>' . esc_html__( 'A story of grace', 'hidden-word-bible-lessons' ) . '</h1>';
		echo '<h2>' . esc_html__( 'Before', 'hidden-word-bible-lessons' ) . '</h2><p>' . esc_html( $payload['before'] ) . '</p>';
		echo '<h2>' . esc_html__( 'Turning point', 'hidden-word-bible-lessons' ) . '</h2><p>' . esc_html( $payload['turning_point'] ) . '</p>';
		echo '<h2>' . esc_html__( 'After', 'hidden-word-bible-lessons' ) . '</h2><p>' . esc_html( $payload['after'] ) . '</p>';
		echo '<div class="share-row">';
		if ( $wa ) {
			echo '<a class="btn btn-secondary" href="' . esc_attr( $wa ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Share on WhatsApp', 'hidden-word-bible-lessons' ) . '</a>';
		}
		if ( $sms ) {
			echo '<a class="btn btn-secondary" href="' . esc_attr( $sms ) . '">' . esc_html__( 'Share by SMS', 'hidden-word-bible-lessons' ) . '</a>';
		}
		echo '</div>';
		echo '<canvas id="hwbl-qr" width="160" height="160" aria-label="' . esc_attr__( 'QR code for this page', 'hidden-word-bible-lessons' ) . '"></canvas>';
		$connect = class_exists( 'HWBL_App_Connect' ) ? HWBL_App_Connect::connect_url() : home_url( '/' );
		echo '<p style="margin-top:1.5rem"><a class="cta" href="' . esc_url( $connect ) . '">' . esc_html__( 'Explore The Hidden Word', 'hidden-word-bible-lessons' ) . '</a></p>';
		echo '<p class="site">' . esc_html( $site ) . '</p>';
		echo '</div>';
		echo '<script>window.hwblSharePage=' . wp_json_encode( array( 'shareUrl' => $share_url ) ) . ';</script>';
		echo '<script src="' . esc_url( HWBL_PLUGIN_URL . 'public/js/qrcode-mini.js' ) . '?v=' . esc_attr( HWBL_VERSION ) . '"></script>';
		echo '<script src="' . esc_url( HWBL_PLUGIN_URL . 'public/js/share-tools.js' ) . '?v=' . esc_attr( HWBL_VERSION ) . '"></script>';
		echo '<script>document.addEventListener("DOMContentLoaded",function(){var c=document.getElementById("hwbl-qr");if(c&&window.hwblDrawQr){window.hwblDrawQr(c,(window.hwblSharePage&&window.hwblSharePage.shareUrl)||location.href);}});</script>';
		if ( class_exists( 'HWBL_PWA' ) ) {
			echo HWBL_PWA::standalone_register_script(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</body></html>';
		exit;
	}
}
