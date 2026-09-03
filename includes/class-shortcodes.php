<?php
/**
 * Front-end shortcodes.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Shortcodes
 */
class HWBL_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'hwbl_lesson', array( $this, 'render_lesson' ) );
		add_shortcode( 'hwbl_verse_of_week', array( $this, 'render_verse_of_week' ) );
		add_shortcode( 'hwbl_lesson_list', array( $this, 'render_lesson_list' ) );
		add_shortcode( 'hwbl_bible_reader', array( $this, 'render_bible_reader' ) );
		add_shortcode( 'hwbl_memorize_verse', array( $this, 'render_memorize_verse' ) );
		add_shortcode( 'hwbl_plan_list', array( $this, 'render_plan_list' ) );
		add_shortcode( 'hwbl_plan', array( $this, 'render_plan' ) );
	}

	/**
	 * Render full lesson shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_lesson( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'                => 'auto',
				'show_memorization' => 'true',
				'show_discussion'   => 'true',
			),
			$atts,
			'hwbl_lesson'
		);

		$lesson_id = 'auto' === $atts['id'] ? 0 : absint( $atts['id'] );
		if ( ! $lesson_id ) {
			HWBL_Cache::mark_page_uncacheable( 'hwbl_lesson_shortcode' );
		}

		return HWBL_Lesson_Renderer::render(
			$lesson_id,
			array(
				'show_memorization' => filter_var( $atts['show_memorization'], FILTER_VALIDATE_BOOLEAN ),
				'show_discussion'   => filter_var( $atts['show_discussion'], FILTER_VALIDATE_BOOLEAN ),
			)
		);
	}

	/**
	 * Render compact verse-of-week shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_verse_of_week( $atts ) {
		$atts = shortcode_atts(
			array(
				'week' => 0,
			),
			$atts,
			'hwbl_verse_of_week'
		);

		$lesson_id = 0;
		if ( ! empty( $atts['week'] ) ) {
			$lesson_id = HWBL_Scheduler::get_lesson_id_by_week( absint( $atts['week'] ) );
		} else {
			$lesson_id = HWBL_Scheduler::get_current_lesson_id();
			HWBL_Cache::mark_page_uncacheable( 'hwbl_verse_of_week' );
		}

		if ( ! $lesson_id ) {
			return '';
		}

		$lesson = HWBL_CPT_Lesson::get_lesson_data( $lesson_id );
		if ( ! HWBL_CPT_Lesson::is_valid_lesson_data( $lesson ) ) {
			return '';
		}

		$translation = get_option( 'hwbl_active_translation', 'niv' );
		$trans_svc   = HWBL_Translation_Service::instance();
		$verse_text  = $trans_svc->get_verse_by_week( $lesson['lesson_number'], $translation );

		if ( ! $verse_text ) {
			$verse_text = $trans_svc->get_verse_text( $lesson['book_id'], $lesson['chapter'], $lesson['verse_start'], $translation );
		}

		$html  = '<div class="hwbl-verse-of-week">';
		$html .= '<p class="hwbl-verse-reference"><strong>' . esc_html( $lesson['reference'] ) . '</strong></p>';
		$html .= '<blockquote class="hwbl-verse-text">' . esc_html( $verse_text ) . '</blockquote>';
		$html .= wp_kses_post( $trans_svc->render_copyright( $translation ) );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render lesson catalog shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_lesson_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'group'     => 'book',
				'book'      => 0,
				'testament' => '',
				'per_page'  => 50,
				'show'      => 'both',
				'page'      => 0,
			),
			$atts,
			'hwbl_lesson_list'
		);

		return HWBL_Lesson_List::render(
			array(
				'group'     => sanitize_key( $atts['group'] ),
				'book'      => absint( $atts['book'] ),
				'testament' => sanitize_key( $atts['testament'] ),
				'per_page'  => absint( $atts['per_page'] ),
				'show'      => sanitize_key( $atts['show'] ),
				'page'      => absint( $atts['page'] ),
			)
		);
	}

	/**
	 * Render full Bible chapter reader shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_bible_reader( $atts ) {
		return HWBL_Bible_Reader::render_shortcode( $atts );
	}

	/**
	 * Render add-verse-to-memorize shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_memorize_verse( $atts ) {
		return HWBL_Verse_Memorize::render_shortcode( $atts );
	}

	/**
	 * Reading plan list shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render_plan_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'topic' => '',
			),
			$atts,
			'hwbl_plan_list'
		);
		$topic = sanitize_key( $atts['topic'] );
		if ( ! $topic && class_exists( 'HWBL_User_Preferences' ) && HWBL_User_Preferences::get_bool_pref( HWBL_User_Preferences::META_KIDS_MODE ) ) {
			$topic = 'kids';
		}
		return self::render_plans_markup( 0, $topic );
	}

	/**
	 * Single reading plan shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render_plan( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'topic' => '',
			),
			$atts,
			'hwbl_plan'
		);
		$plan_id = absint( $atts['id'] );
		// Prefer hwbl_plan_id — hwbl_plan is the CPT query var and 404s page routes.
		if ( ! $plan_id && isset( $_GET['hwbl_plan_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$plan_id = absint( wp_unslash( $_GET['hwbl_plan_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			HWBL_Cache::mark_page_uncacheable( 'hwbl_plan_query' );
		}
		return self::render_plans_markup( $plan_id, sanitize_key( $atts['topic'] ) );
	}

	/**
	 * Shared plan / plan-list markup + assets.
	 *
	 * @param int    $plan_id Plan ID (0 = list).
	 * @param string $topic   Topic filter.
	 * @return string
	 */
	public static function render_plans_markup( $plan_id, $topic = '' ) {
		wp_enqueue_style(
			'hwbl-plan',
			HWBL_PLUGIN_URL . 'public/css/plan.css',
			array(),
			HWBL_VERSION
		);
		wp_enqueue_script(
			'hwbl-plan',
			HWBL_PLUGIN_URL . 'public/js/plan.js',
			array( 'hwbl-journal-export-menu' ),
			HWBL_VERSION,
			true
		);

		if ( $plan_id > 0 ) {
			// Outline does not need verse text for every day — only enrich the active/preview day.
			$plan = HWBL_CPT_Plan::get_plan_data( $plan_id, false );
			if ( ! $plan || ( 'publish' !== $plan['status'] && ! current_user_can( 'read_post', $plan_id ) ) ) {
				return '<p class="hwbl-empty">' . esc_html__( 'Plan not found.', 'hidden-word-bible-lessons' ) . '</p>';
			}
			unset( $plan['status'] );
			$user_id  = get_current_user_id();
			$progress = $user_id ? HWBL_Plan_Progress::get( $user_id, $plan_id ) : null;
			$today    = null;
			$preview  = false;
			$req_day  = isset( $_GET['hwbl_plan_day'] ) ? absint( wp_unslash( $_GET['hwbl_plan_day'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $progress && (int) $progress['current_day'] > 0 ) {
				$view_day = $req_day > 0 ? $req_day : (int) $progress['current_day'];
				$today    = HWBL_CPT_Plan::get_day( $plan_id, $view_day );
				if ( ! $today ) {
					$today = HWBL_CPT_Plan::get_day( $plan_id, (int) $progress['current_day'] );
				}
			} else {
				// Preview day 1 so verse / explain / study are visible before starting.
				$today   = HWBL_CPT_Plan::get_day( $plan_id, $req_day > 0 ? $req_day : 1 );
				$preview = (bool) $today && ( ! $progress || (int) $progress['current_day'] < 1 );
			}
			$logged_in = (bool) $user_id;
			ob_start();
			include HWBL_PLUGIN_DIR . 'public/partials/plan-day.php';
			return (string) ob_get_clean();
		}

		$query = array(
			'post_type'      => HWBL_CPT_Plan::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		if ( $topic ) {
			$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => HWBL_CPT_Plan::META_TOPIC,
					'value' => $topic,
				),
			);
		}
		$posts = get_posts( $query );
		$plans = array();
		foreach ( $posts as $post ) {
			// Card lists never show verse text — skip remote/local enrich for every day.
			$data = HWBL_CPT_Plan::get_plan_data( $post->ID, false );
			if ( $data ) {
				unset( $data['content'], $data['days'], $data['status'] );
				$plans[] = $data;
			}
		}
		ob_start();
		include HWBL_PLUGIN_DIR . 'public/partials/plan-list.php';
		return (string) ob_get_clean();
	}
}
