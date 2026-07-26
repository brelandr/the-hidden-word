<?php
/**
 * Video embed support for lessons.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Video
 */
class THW_Premium_Video {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_video_meta_box' ) );
		add_action( 'save_post_hwbl_lesson', array( __CLASS__, 'save_video_url' ), 10, 2 );
		add_action( 'hwbl_lesson_render_before_tabs', array( __CLASS__, 'render_video_embed' ), 6 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_fields' ) );
	}

	/**
	 * Expose lesson video URL on the hwbl-lessons REST type for the companion app.
	 */
	public static function register_rest_fields() {
		register_rest_field(
			'hwbl_lesson',
			'video_url',
			array(
				'get_callback' => static function ( $object ) {
					$lesson_id = isset( $object['id'] ) ? (int) $object['id'] : 0;
					if ( $lesson_id < 1 ) {
						return '';
					}
					$url = get_post_meta( $lesson_id, '_hwbl_video_url', true );
					return is_string( $url ) ? esc_url_raw( $url ) : '';
				},
				'schema'       => array(
					'description' => __( 'YouTube or Vimeo URL for this lesson.', 'hidden-word-bible-lessons' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Add video URL meta box.
	 */
	public static function add_video_meta_box() {
		add_meta_box(
			'thw_video',
			__( 'Video Embed', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_meta_box' ),
			'hwbl_lesson',
			'side',
			'default'
		);
	}

	/**
	 * Render video meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'thw_video_url', 'thw_video_url_nonce' );
		$video_url = get_post_meta( $post->ID, '_hwbl_video_url', true );
		?>
		<p>
			<label for="thw_video_url"><?php esc_html_e( 'YouTube or Vimeo URL', 'hidden-word-bible-lessons' ); ?></label>
			<input type="url" id="thw_video_url" name="thw_video_url" value="<?php echo esc_url( $video_url ); ?>" class="widefat" placeholder="https://" />
		</p>
		<?php
	}

	/**
	 * Save video URL.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_video_url( $post_id, $post ) {
		unset( $post );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Missing nonce: autosave / quick edit did not submit this meta box.
		if ( ! isset( $_POST['thw_video_url_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['thw_video_url_nonce'] ) ), 'thw_video_url' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'hidden-word-bible-lessons' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		if ( isset( $_POST['thw_video_url'] ) ) {
			update_post_meta( $post_id, '_hwbl_video_url', esc_url_raw( wp_unslash( $_POST['thw_video_url'] ) ) );
		}
	}

	/**
	 * Render oEmbed video if URL is set.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public static function render_video_embed( $lesson_id ) {
		$video_url = get_post_meta( $lesson_id, '_hwbl_video_url', true );
		if ( ! $video_url ) {
			return;
		}
		$embed = wp_oembed_get( $video_url );
		if ( ! $embed ) {
			return;
		}
		echo '<div class="thw-video-embed">' . wp_kses( $embed, self::allowed_oembed_html() ) . '</div>';
	}

	/**
	 * Allowed tags for provider oEmbed HTML (YouTube/Vimeo iframes).
	 *
	 * @return array<string, array<string, bool>>
	 */
	private static function allowed_oembed_html() {
		return array(
			'iframe'     => array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'frameborder'     => true,
				'allow'           => true,
				'allowfullscreen' => true,
				'title'           => true,
				'loading'         => true,
				'referrerpolicy'  => true,
				'style'           => true,
				'class'           => true,
			),
			'blockquote' => array(
				'class' => true,
				'cite'  => true,
			),
			'div'        => array(
				'class' => true,
				'style' => true,
				'id'    => true,
			),
			'a'          => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
				'class'  => true,
			),
			'p'          => array(
				'class' => true,
			),
			'span'       => array(
				'class' => true,
			),
		);
	}
}
