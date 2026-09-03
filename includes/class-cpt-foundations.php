<?php
/**
 * Ordered Foundations learning track.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_CPT_Foundations
 */
class HWBL_CPT_Foundations {

	const POST_TYPE = 'hwbl_foundations';
	const META_ORDER = '_hwbl_foundations_order';
	const META_TOPIC = '_hwbl_foundations_topic';
	const META_BODY  = '_hwbl_foundations_body';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_shortcode( 'hwbl_foundations', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Register CPT.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Foundations', 'hidden-word-bible-lessons' ),
					'singular_name' => __( 'Foundation Lesson', 'hidden-word-bible-lessons' ),
				),
				'public'          => true,
				'has_archive'     => true,
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-book-alt',
				'supports'        => array( 'title', 'excerpt' ),
				'rewrite'         => array( 'slug' => 'foundations' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Add ordering metadata.
	 */
	public static function add_meta_boxes() {
		add_meta_box( 'hwbl_foundations_fields', __( 'Foundation lesson', 'hidden-word-bible-lessons' ), array( __CLASS__, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * Render metadata.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'hwbl_save_foundations', 'hwbl_foundations_nonce' );
		$body = (string) get_post_meta( $post->ID, self::META_BODY, true );
		?>
		<p><label><strong><?php esc_html_e( 'Sequence order', 'hidden-word-bible-lessons' ); ?></strong><input class="widefat" type="number" min="0" name="hwbl_foundations_order" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::META_ORDER, true ) ); ?>" /></label></p>
		<p><label><strong><?php esc_html_e( 'Topic', 'hidden-word-bible-lessons' ); ?></strong><input class="widefat" type="text" name="hwbl_foundations_topic" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::META_TOPIC, true ) ); ?>" /></label></p>
		<p><strong><?php esc_html_e( 'Lesson body', 'hidden-word-bible-lessons' ); ?></strong></p>
		<?php
		wp_editor(
			$body,
			'hwbl_foundations_body_editor',
			array(
				'textarea_name' => 'hwbl_foundations_body',
				'textarea_rows' => 14,
			)
		);
	}

	/**
	 * Save metadata.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['hwbl_foundations_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hwbl_foundations_nonce'] ) ), 'hwbl_save_foundations' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		update_post_meta( $post_id, self::META_ORDER, isset( $_POST['hwbl_foundations_order'] ) ? absint( $_POST['hwbl_foundations_order'] ) : 0 );
		update_post_meta( $post_id, self::META_TOPIC, isset( $_POST['hwbl_foundations_topic'] ) ? sanitize_text_field( wp_unslash( $_POST['hwbl_foundations_topic'] ) ) : '' );
		update_post_meta( $post_id, self::META_BODY, isset( $_POST['hwbl_foundations_body'] ) ? wp_kses_post( wp_unslash( $_POST['hwbl_foundations_body'] ) ) : '' );
	}

	/**
	 * Register public read routes.
	 */
	public static function register_routes() {
		register_rest_route( 'hwbl/v1', '/foundations', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_list' ), 'permission_callback' => '__return_true' ) );
		register_rest_route(
			'hwbl/v1',
			'/foundations/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'id' => array( 'type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint' ) ),
			)
		);
	}

	/**
	 * Entry payload.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_payload( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		$payload = array(
			'id'      => (int) $post_id,
			'title'   => get_the_title( $post_id ),
			'order'   => (int) get_post_meta( $post_id, self::META_ORDER, true ),
			'topic'   => (string) get_post_meta( $post_id, self::META_TOPIC, true ),
			'body'    => wp_kses_post( wpautop( (string) get_post_meta( $post_id, self::META_BODY, true ) ) ),
			'excerpt' => has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( wp_strip_all_tags( (string) get_post_meta( $post_id, self::META_BODY, true ) ), 40 ),
			'link'    => get_permalink( $post_id ),
		);
		if ( function_exists( 'thw_premium_resolve_explain_rules_for_request' ) ) {
			$resolved = thw_premium_resolve_explain_rules_for_request( '', $payload['topic'] . ' ' . $payload['title'] );
			if ( ! empty( $resolved['preset'] ) && 'general' !== $resolved['preset'] && ! empty( $resolved['rules'] ) ) {
				$payload['tradition_callout'] = (string) $resolved['rules'];
			}
		}
		return $payload;
	}

	/**
	 * Ordered posts.
	 *
	 * @return WP_Post[]
	 */
	private static function ordered_posts() {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'meta_key'       => self::META_ORDER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'        => array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ),
			)
		);
	}

	/**
	 * REST list.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_list() {
		$entries = array();
		foreach ( self::ordered_posts() as $post ) {
			$payload = self::get_payload( $post->ID );
			if ( $payload ) {
				$entries[] = $payload;
			}
		}
		return rest_ensure_response( array( 'entries' => $entries ) );
	}

	/**
	 * REST get.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_get( $request ) {
		$payload = self::get_payload( (int) $request['id'] );
		return $payload ? rest_ensure_response( $payload ) : new WP_Error( 'hwbl_foundation_not_found', __( 'Foundation lesson not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
	}

	/**
	 * Ordered track shortcode.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		$posts = self::ordered_posts();
		if ( ! $posts ) {
			return '<p class="hwbl-empty">' . esc_html__( 'No Foundation lessons are published yet.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		$requested = isset( $_GET['hwbl_foundation'] ) ? absint( $_GET['hwbl_foundation'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$index     = 0;
		foreach ( $posts as $i => $post ) {
			if ( $requested === (int) $post->ID ) {
				$index = $i;
				break;
			}
		}
		$current = self::get_payload( $posts[ $index ]->ID );
		if ( ! $current ) {
			return '';
		}
		ob_start();
		?>
		<article class="hwbl-foundations">
			<p class="hwbl-foundations__topic"><?php echo esc_html( $current['topic'] ); ?></p>
			<h3><?php echo esc_html( $current['title'] ); ?></h3>
			<div><?php echo wp_kses_post( $current['body'] ); ?></div>
			<?php if ( ! empty( $current['tradition_callout'] ) ) : ?>
				<aside class="hwbl-foundations__tradition"><?php echo esc_html( $current['tradition_callout'] ); ?></aside>
			<?php endif; ?>
			<nav aria-label="<?php esc_attr_e( 'Foundation lesson navigation', 'hidden-word-bible-lessons' ); ?>">
				<?php if ( $index > 0 ) : ?><a class="hwbl-btn hwbl-btn-secondary" href="<?php echo esc_url( add_query_arg( 'hwbl_foundation', $posts[ $index - 1 ]->ID ) ); ?>"><?php esc_html_e( 'Previous', 'hidden-word-bible-lessons' ); ?></a><?php endif; ?>
				<?php if ( $index + 1 < count( $posts ) ) : ?><a class="hwbl-btn" href="<?php echo esc_url( add_query_arg( 'hwbl_foundation', $posts[ $index + 1 ]->ID ) ); ?>"><?php esc_html_e( 'Next', 'hidden-word-bible-lessons' ); ?></a><?php endif; ?>
			</nav>
		</article>
		<?php
		return (string) ob_get_clean();
	}
}
