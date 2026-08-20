<?php
/**
 * PDF leader guide export.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_PDF_Export
 */
class THW_Premium_PDF_Export {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_download_meta_box' ) );
		add_action( 'admin_post_thw_download_leader_guide', array( __CLASS__, 'handle_download' ) );
		add_action( 'admin_post_thw_download_booklet', array( __CLASS__, 'handle_booklet_download' ) );
		add_action( 'hwbl_lesson_render_after_echo', array( __CLASS__, 'render_front_end_button' ), 20 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * REST: leader guide download for lesson editors / leaders.
	 */
	public static function register_routes() {
		$args = array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'rest_leader_guide' ),
			'permission_callback' => static function ( $request ) {
				$lesson_id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
				return $lesson_id && current_user_can( 'edit_post', $lesson_id );
			},
			'args'                => array(
				'id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		);

		register_rest_route( 'hwbl/v1', '/lessons/(?P<id>\d+)/leader-guide', $args );
		register_rest_route( 'thw/v1', '/lessons/(?P<id>\d+)/leader-guide', $args );
	}

	/**
	 * Whether the current user can download a leader guide for this lesson.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return bool
	 */
	public static function can_download_leader_guide( $lesson_id ) {
		$lesson_id = absint( $lesson_id );
		return $lesson_id > 0 && current_user_can( 'edit_post', $lesson_id );
	}

	/**
	 * Signed download URL for companion / front-end (empty when unauthorized).
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return string
	 */
	public static function get_leader_guide_url( $lesson_id ) {
		$lesson_id = absint( $lesson_id );
		if ( ! self::can_download_leader_guide( $lesson_id ) ) {
			return '';
		}
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=thw_download_leader_guide&lesson_id=' . $lesson_id ),
			'thw_download_leader_guide_' . $lesson_id
		);
	}

	/**
	 * REST callback — returns a one-time download URL (HTML/PDF via admin-post).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_leader_guide( $request ) {
		$lesson_id = absint( $request['id'] );
		$post      = get_post( $lesson_id );
		if ( ! $post || ! in_array( $post->post_type, array( 'hwbl_lesson', 'thw_lesson' ), true ) ) {
			return new WP_Error(
				'thw_leader_guide_not_found',
				__( 'Lesson not found.', 'hidden-word-bible-lessons' ),
				array( 'status' => 404 )
			);
		}

		$url = self::get_leader_guide_url( $lesson_id );
		if ( '' === $url ) {
			return new WP_Error(
				'thw_leader_guide_forbidden',
				__( 'You cannot download this leader guide.', 'hidden-word-bible-lessons' ),
				array( 'status' => 403 )
			);
		}

		return new WP_REST_Response(
			array(
				'lessonId' => $lesson_id,
				'url'      => $url,
				'available' => true,
			)
		);
	}

	/**
	 * Add download meta box in admin.
	 */
	public static function add_download_meta_box() {
		add_meta_box(
			'thw_leader_guide',
			__( 'Leader Guide', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_meta_box' ),
			'hwbl_lesson',
			'side',
			'default'
		);
	}

	/**
	 * Render admin meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=thw_download_leader_guide&lesson_id=' . $post->ID ),
			'thw_download_leader_guide_' . $post->ID
		);
		?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary" target="_blank">
				<?php esc_html_e( 'Download Leader Guide (PDF)', 'hidden-word-bible-lessons' ); ?>
			</a>
		</p>
		<p class="description"><?php esc_html_e( 'Generates a printable leader guide with verse, context, and discussion questions.', 'hidden-word-bible-lessons' ); ?></p>
		<?php
		$booklet_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=thw_download_booklet&scope=custom' ),
			'thw_download_booklet'
		);
		?>
		<p>
			<a href="<?php echo esc_url( $booklet_url ); ?>" class="button" target="_blank">
				<?php esc_html_e( 'Download Booklet (custom track)', 'hidden-word-bible-lessons' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render front-end download button.
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public static function render_front_end_button( $lesson_id ) {
		$lesson_id = absint( $lesson_id );
		if ( ! $lesson_id || ! current_user_can( 'edit_post', $lesson_id ) ) {
			return;
		}
		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=thw_download_leader_guide&lesson_id=' . $lesson_id ),
			'thw_download_leader_guide_' . $lesson_id
		);
		?>
		<p class="thw-pdf-download">
			<a href="<?php echo esc_url( $url ); ?>" class="thw-btn" target="_blank">
				<?php esc_html_e( 'Download Leader Guide', 'hidden-word-bible-lessons' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Handle PDF download request.
	 */
	public static function handle_download() {
		$lesson_id = isset( $_GET['lesson_id'] ) ? absint( wp_unslash( $_GET['lesson_id'] ) ) : 0;
		if ( ! $lesson_id ) {
			wp_die( esc_html__( 'Invalid lesson.', 'hidden-word-bible-lessons' ) );
		}

		check_admin_referer( 'thw_download_leader_guide_' . $lesson_id );

		if ( ! current_user_can( 'edit_post', $lesson_id ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		$html = self::build_html( $lesson_id );

		if ( class_exists( 'Dompdf\Dompdf' ) ) {
			self::output_dompdf( $html, $lesson_id );
		} else {
			self::output_printable_html( $html, $lesson_id );
		}
	}

	/**
	 * Handle booklet PDF download.
	 */
	public static function handle_booklet_download() {
		check_admin_referer( 'thw_download_booklet' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		$scope = isset( $_GET['scope'] ) ? sanitize_text_field( wp_unslash( $_GET['scope'] ) ) : 'custom';
		$scope = in_array( $scope, array( 'custom', 'all' ), true ) ? $scope : 'custom';
		$html  = self::build_booklet_html( $scope );

		if ( class_exists( 'Dompdf\Dompdf' ) ) {
			self::output_dompdf( $html, 0, 'hidden-word-booklet.pdf' );
		} else {
			self::output_printable_html( $html, 0 );
		}
	}

	/**
	 * Build multi-lesson booklet HTML.
	 *
	 * @param string $scope `custom` or `all`.
	 * @return string
	 */
	private static function build_booklet_html( $scope ) {
		$lesson_ids = array();
		if ( 'all' === $scope ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'hwbl_lesson',
					'posts_per_page' => 500,
					'post_status'    => 'publish',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Ordered lesson booklet export; meta_key is intentional.
					'meta_key'       => '_hwbl_lesson_number',
					'orderby'        => 'meta_value_num',
					'order'          => 'ASC',
					'fields'         => 'ids',
				)
			);
			$lesson_ids = $query->posts;
		} else {
			$lesson_ids = THW_Premium_Scheduler::get_custom_track();
		}

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php esc_html_e( 'Hidden Word Leader Booklet', 'hidden-word-bible-lessons' ); ?></title>
			<style>
				body { font-family: Georgia, serif; line-height: 1.6; margin: 40px; color: #222; }
				h1 { color: #1a5276; }
				h2 { color: #1a5276; margin-top: 24px; page-break-before: always; }
				h2:first-of-type { page-break-before: auto; }
				blockquote { font-style: italic; border-left: 4px solid #1a5276; padding-left: 16px; }
				.toc li { margin-bottom: 4px; }
			</style>
		</head>
		<body>
			<h1><?php esc_html_e( 'Table of Contents', 'hidden-word-bible-lessons' ); ?></h1>
			<ol class="toc">
				<?php foreach ( $lesson_ids as $lesson_id ) : ?>
					<li><?php echo esc_html( get_the_title( $lesson_id ) ); ?></li>
				<?php endforeach; ?>
			</ol>
			<?php foreach ( $lesson_ids as $lesson_id ) : ?>
				<?php echo wp_kses_post( self::build_lesson_section_html( (int) $lesson_id ) ); ?>
			<?php endforeach; ?>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build a single lesson section for booklet export.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return string
	 */
	private static function build_lesson_section_html( $lesson_id ) {
		$lesson      = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		$translation = get_option( 'hwbl_active_translation', 'niv' );
		$trans_svc   = HWBL_Translation_Service::instance();
		$verse_text  = $trans_svc->get_verse_by_week( $lesson['lesson_number'], $translation );
		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_text( $lesson['book_id'], $lesson['chapter'], $lesson['verse_start'], $translation );
		}

		ob_start();
		?>
		<h2><?php echo esc_html( $lesson['title'] ); ?></h2>
		<p><strong><?php echo esc_html( $lesson['reference'] ); ?></strong></p>
		<blockquote><?php echo esc_html( $verse_text ); ?></blockquote>
		<?php if ( ! empty( $lesson['historical_context'] ) ) : ?>
			<h3><?php esc_html_e( 'The Context', 'hidden-word-bible-lessons' ); ?></h3>
			<?php echo wp_kses_post( $lesson['historical_context'] ); ?>
		<?php endif; ?>
		<?php if ( ! empty( $lesson['discussion_questions'] ) ) : ?>
			<h3><?php esc_html_e( 'Discussion Questions', 'hidden-word-bible-lessons' ); ?></h3>
			<ol>
				<?php foreach ( $lesson['discussion_questions'] as $q ) : ?>
					<li><?php echo esc_html( $q ); ?></li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build leader guide HTML.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return string
	 */
	private static function build_html( $lesson_id ) {
		$lesson     = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		$translation = get_option( 'hwbl_active_translation', 'niv' );
		$trans_svc  = HWBL_Translation_Service::instance();
		$verse_text = $trans_svc->get_verse_by_week( $lesson['week_number'], $translation );

		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_text( $lesson['book_id'], $lesson['chapter'], $lesson['verse_start'], $translation );
		}

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $lesson['title'] ); ?> — Leader Guide</title>
			<style>
				body { font-family: Georgia, serif; line-height: 1.6; margin: 40px; color: #222; }
				h1 { color: #1a5276; border-bottom: 2px solid #1a5276; padding-bottom: 8px; }
				h2 { color: #1a5276; margin-top: 24px; }
				blockquote { font-style: italic; border-left: 4px solid #1a5276; padding-left: 16px; margin: 16px 0; }
				.copyright { font-size: 10px; color: #666; margin-top: 32px; }
				ol li { margin-bottom: 8px; }
			</style>
		</head>
		<body>
			<h1><?php echo esc_html( $lesson['title'] ); ?></h1>
			<p><strong><?php echo esc_html( $lesson['reference'] ); ?></strong></p>

			<h2><?php esc_html_e( 'The Blueprint', 'hidden-word-bible-lessons' ); ?></h2>
			<blockquote><?php echo esc_html( $verse_text ); ?></blockquote>

			<?php if ( ! empty( $lesson['historical_context'] ) ) : ?>
				<h2><?php esc_html_e( 'The Context', 'hidden-word-bible-lessons' ); ?></h2>
				<?php echo wp_kses_post( $lesson['historical_context'] ); ?>
			<?php endif; ?>

			<?php if ( ! empty( $lesson['preceding_narrative'] ) ) : ?>
				<h2><?php esc_html_e( 'The Narrative', 'hidden-word-bible-lessons' ); ?></h2>
				<?php echo wp_kses_post( $lesson['preceding_narrative'] ); ?>
			<?php endif; ?>

			<?php if ( ! empty( $lesson['discussion_questions'] ) ) : ?>
				<h2><?php esc_html_e( 'Discussion Questions', 'hidden-word-bible-lessons' ); ?></h2>
				<ol>
					<?php foreach ( $lesson['discussion_questions'] as $q ) : ?>
						<li><?php echo esc_html( $q ); ?></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<div class="copyright"><?php echo wp_kses_post( $trans_svc->render_copyright( $translation ) ); ?></div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Output PDF via Dompdf.
	 *
	 * @param string $html      HTML content.
	 * @param int    $lesson_id Lesson ID.
	 */
	private static function output_dompdf( $html, $lesson_id, $filename = '' ) {
		$dompdf = new Dompdf\Dompdf();
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( 'letter', 'portrait' );
		$dompdf->render();
		$name = $filename ? $filename : 'leader-guide-' . $lesson_id . '.pdf';
		$dompdf->stream( $name, array( 'Attachment' => true ) );
		exit;
	}

	/**
	 * Fallback: output printable HTML when Dompdf unavailable.
	 *
	 * Emits a standalone document (not a normal WP page), so scripts are not
	 * enqueued via wp_enqueue_script(). The print trigger is inlined to avoid
	 * a NonEnqueuedScript hard dependency on an external file.
	 *
	 * @param string $html      HTML content.
	 * @param int    $lesson_id Lesson ID.
	 */
	private static function output_printable_html( $html, $lesson_id ) {
		unset( $lesson_id );

		header( 'Content-Type: text/html; charset=utf-8' );
		// Full document assembled from escaped fragments in build_*_html().
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone printable HTML document.
		echo '<script>window.print();</script>';
		exit;
	}
}
