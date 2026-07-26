<?php
/**
 * Audio playback support for lessons.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Audio
 */
class THW_Premium_Audio {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_audio_meta_box' ) );
		add_action( 'save_post_hwbl_lesson', array( __CLASS__, 'save_audio_url' ), 10, 2 );
		add_filter( 'hwbl_lesson_tabs', array( __CLASS__, 'ensure_blueprint_has_audio' ), 5, 2 );
		add_action( 'hwbl_lesson_render_before_tabs', array( __CLASS__, 'render_audio_player' ), 5 );
	}

	/**
	 * Add audio URL meta box.
	 */
	public static function add_audio_meta_box() {
		add_meta_box(
			'thw_audio',
			__( 'Audio Playback', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_meta_box' ),
			'hwbl_lesson',
			'side',
			'default'
		);
	}

	/**
	 * Render audio meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'thw_audio_url', 'thw_audio_url_nonce' );
		$audio_url = get_post_meta( $post->ID, '_hwbl_audio_url', true );
		?>
		<p>
			<label for="thw_audio_url"><?php esc_html_e( 'Audio File URL', 'hidden-word-bible-lessons' ); ?></label>
			<input type="url" id="thw_audio_url" name="thw_audio_url" value="<?php echo esc_url( $audio_url ); ?>" class="widefat" placeholder="https://" />
		</p>
		<p class="description"><?php esc_html_e( 'Optional audio for memorization playback. Upload to Media Library and paste the URL.', 'hidden-word-bible-lessons' ); ?></p>
		<?php
	}

	/**
	 * Save audio URL.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_audio_url( $post_id, $post ) {
		unset( $post );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Missing nonce: autosave / quick edit did not submit this meta box.
		if ( ! isset( $_POST['thw_audio_url_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['thw_audio_url_nonce'] ) ), 'thw_audio_url' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'hidden-word-bible-lessons' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		if ( isset( $_POST['thw_audio_url'] ) ) {
			update_post_meta( $post_id, '_hwbl_audio_url', esc_url_raw( wp_unslash( $_POST['thw_audio_url'] ) ) );
		}
	}

	/**
	 * Pass-through filter for tabs.
	 *
	 * @param array $tabs      Tabs.
	 * @param int   $lesson_id Lesson ID.
	 * @return array
	 */
	public static function ensure_blueprint_has_audio( $tabs, $lesson_id ) {
		unset( $lesson_id );
		return $tabs;
	}

	/**
	 * Render audio player if URL is set.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public static function render_audio_player( $lesson_id ) {
		$audio_url = get_post_meta( $lesson_id, '_hwbl_audio_url', true );
		if ( ! $audio_url ) {
			return;
		}
		?>
		<div class="thw-audio-player">
			<audio controls preload="none" src="<?php echo esc_url( $audio_url ); ?>">
				<?php esc_html_e( 'Your browser does not support audio playback.', 'hidden-word-bible-lessons' ); ?>
			</audio>
		</div>
		<?php
	}
}
