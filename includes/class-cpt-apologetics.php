<?php
/**
 * Apologetics library CPT + shortcode archive/search.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_CPT_Apologetics
 */
class HWBL_CPT_Apologetics {

	const POST_TYPE   = 'hwbl_apologetics';
	const META_Q      = '_hwbl_apologetics_question';
	const META_A      = '_hwbl_apologetics_answer';
	const META_REFS   = '_hwbl_apologetics_references';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_shortcode( 'hwbl_apologetics', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * CPT.
	 */
	public static function register_post_type() {
		$can_translate = (bool) did_action( 'init' );
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => $can_translate ? __( 'Apologetics', 'hidden-word-bible-lessons' ) : 'Apologetics',
					'singular_name' => $can_translate ? __( 'Apologetics Entry', 'hidden-word-bible-lessons' ) : 'Apologetics Entry',
				),
				'public'          => true,
				'has_archive'     => true,
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-shield',
				'supports'        => array( 'title', 'editor' ),
				'rewrite'         => array( 'slug' => 'apologetics' ),
				'capability_type' => 'post',
				'show_in_menu'    => true,
			)
		);
	}

	/**
	 * Meta boxes.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'hwbl_apologetics_fields',
			__( 'Apologetics content', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'hwbl_save_apologetics', 'hwbl_apologetics_nonce' );
		$q    = (string) get_post_meta( $post->ID, self::META_Q, true );
		$a    = (string) get_post_meta( $post->ID, self::META_A, true );
		$refs = (string) get_post_meta( $post->ID, self::META_REFS, true );
		?>
		<p class="description"><?php esc_html_e( 'Curated, reviewed answers — distinct from live AI Ask.', 'hidden-word-bible-lessons' ); ?></p>
		<p>
			<label><strong><?php esc_html_e( 'Question', 'hidden-word-bible-lessons' ); ?></strong></label>
			<textarea class="widefat" rows="2" name="hwbl_apologetics_question"><?php echo esc_textarea( $q ); ?></textarea>
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Reviewed answer', 'hidden-word-bible-lessons' ); ?></strong></label>
			<textarea class="widefat" rows="8" name="hwbl_apologetics_answer"><?php echo esc_textarea( $a ); ?></textarea>
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Supporting references', 'hidden-word-bible-lessons' ); ?></strong></label>
			<textarea class="widefat" rows="3" name="hwbl_apologetics_references"><?php echo esc_textarea( $refs ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_meta( $post_id, $post ) {
		unset( $post );
		if ( ! isset( $_POST['hwbl_apologetics_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hwbl_apologetics_nonce'] ) ), 'hwbl_save_apologetics' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, self::META_Q, isset( $_POST['hwbl_apologetics_question'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hwbl_apologetics_question'] ) ) : '' );
		update_post_meta( $post_id, self::META_A, isset( $_POST['hwbl_apologetics_answer'] ) ? wp_kses_post( wp_unslash( $_POST['hwbl_apologetics_answer'] ) ) : '' );
		update_post_meta( $post_id, self::META_REFS, isset( $_POST['hwbl_apologetics_references'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hwbl_apologetics_references'] ) ) : '' );
	}

	/**
	 * REST list/search.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/apologetics',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_list' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					),
				),
			)
		);
	}

	/**
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_payload( $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}
		$q = (string) get_post_meta( $post_id, self::META_Q, true );
		return array(
			'id'          => (int) $post_id,
			'title'       => get_the_title( $post_id ),
			'question'    => $q ?: get_the_title( $post_id ),
			'answer'      => (string) get_post_meta( $post_id, self::META_A, true ),
			'references'  => (string) get_post_meta( $post_id, self::META_REFS, true ),
			'excerpt'     => wp_trim_words( wp_strip_all_tags( (string) get_post_meta( $post_id, self::META_A, true ) ), 40 ),
			'link'        => get_permalink( $post_id ),
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_list( $request ) {
		$q     = trim( (string) $request->get_param( 'q' ) );
		$args  = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			's'              => $q,
		);
		$posts = get_posts( $args );
		$out   = array();
		foreach ( $posts as $post ) {
			$payload = self::get_payload( $post->ID );
			if ( $payload ) {
				$out[] = $payload;
			}
		}
		return rest_ensure_response( array( 'entries' => $out ) );
	}

	/**
	 * Archive/search shortcode.
	 *
	 * @param array $atts Atts.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'q' => '' ), $atts, 'hwbl_apologetics' );
		$q    = isset( $_GET['hwbl_apologetics_q'] ) ? sanitize_text_field( wp_unslash( $_GET['hwbl_apologetics_q'] ) ) : sanitize_text_field( $atts['q'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			's'              => $q,
		);
		$posts = get_posts( $args );
		ob_start();
		?>
		<div class="hwbl-apologetics">
			<p class="hwbl-apologetics__note">
				<?php esc_html_e( 'Curated apologetics answers (reviewed). This is not the same as live AI Ask.', 'hidden-word-bible-lessons' ); ?>
			</p>
			<form method="get" class="hwbl-apologetics__search" action="">
				<label>
					<span class="screen-reader-text"><?php esc_html_e( 'Search apologetics', 'hidden-word-bible-lessons' ); ?></span>
					<input type="search" name="hwbl_apologetics_q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php esc_attr_e( 'Search questions…', 'hidden-word-bible-lessons' ); ?>" />
				</label>
				<button type="submit" class="hwbl-btn"><?php esc_html_e( 'Search', 'hidden-word-bible-lessons' ); ?></button>
			</form>
			<?php if ( ! $posts ) : ?>
				<p class="hwbl-empty"><?php esc_html_e( 'No apologetics entries found.', 'hidden-word-bible-lessons' ); ?></p>
			<?php else : ?>
				<ul class="hwbl-apologetics__list">
					<?php foreach ( $posts as $post ) : ?>
						<?php $payload = self::get_payload( $post->ID ); ?>
						<?php if ( ! $payload ) { continue; } ?>
						<li>
							<details>
								<summary><strong><?php echo esc_html( $payload['question'] ); ?></strong></summary>
								<div class="hwbl-apologetics__answer"><?php echo wp_kses_post( wpautop( $payload['answer'] ) ); ?></div>
								<?php if ( $payload['references'] ) : ?>
									<p class="hwbl-apologetics__refs"><em><?php echo esc_html( $payload['references'] ); ?></em></p>
								<?php endif; ?>
							</details>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
