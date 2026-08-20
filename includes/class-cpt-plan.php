<?php
/**
 * Reading plan custom post type and admin day editor.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_CPT_Plan
 */
class HWBL_CPT_Plan {

	const POST_TYPE = 'hwbl_plan';

	const META_DAYS      = '_hwbl_plan_days';
	const META_TOPIC     = '_hwbl_plan_topic';
	const META_LENGTH    = '_hwbl_plan_length';
	const META_SHAREABLE = '_hwbl_plan_shareable';

	/**
	 * Known plan topics.
	 *
	 * @return string[]
	 */
	public static function allowed_topics() {
		return array(
			'anxiety',
			'grief',
			'marriage',
			'parenting',
			'new-believer',
			'chronological',
			'gospel',
			'foundations',
			'advent',
			'lent',
			'kids',
			'prayer',
			'identity',
			'forgiveness',
			'purpose',
			'discipleship',
			'life-problems',
			'health',
			'alcoholism',
			'addiction',
			'finances',
			'domestic-violence',
			'doubt',
			'loneliness',
			'depression',
			'other',
		);
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register CPT.
	 */
	public function register_post_type() {
		$can_translate = (bool) did_action( 'init' );
		$labels        = array(
			'name'               => $can_translate ? __( 'Reading Plans', 'hidden-word-bible-lessons' ) : 'Reading Plans',
			'singular_name'      => $can_translate ? __( 'Reading Plan', 'hidden-word-bible-lessons' ) : 'Reading Plan',
			'menu_name'          => $can_translate ? __( 'Reading Plans', 'hidden-word-bible-lessons' ) : 'Reading Plans',
			'add_new'            => $can_translate ? __( 'Add Plan', 'hidden-word-bible-lessons' ) : 'Add Plan',
			'add_new_item'       => $can_translate ? __( 'Add New Plan', 'hidden-word-bible-lessons' ) : 'Add New Plan',
			'edit_item'          => $can_translate ? __( 'Edit Plan', 'hidden-word-bible-lessons' ) : 'Edit Plan',
			'new_item'           => $can_translate ? __( 'New Plan', 'hidden-word-bible-lessons' ) : 'New Plan',
			'view_item'          => $can_translate ? __( 'View Plan', 'hidden-word-bible-lessons' ) : 'View Plan',
			'search_items'       => $can_translate ? __( 'Search Plans', 'hidden-word-bible-lessons' ) : 'Search Plans',
			'not_found'          => $can_translate ? __( 'No plans found', 'hidden-word-bible-lessons' ) : 'No plans found',
			'not_found_in_trash' => $can_translate ? __( 'No plans found in trash', 'hidden-word-bible-lessons' ) : 'No plans found in trash',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => $labels,
				'public'          => true,
				'has_archive'     => true,
				'show_in_rest'    => true,
				'rest_base'       => 'hwbl-plans',
				'menu_icon'       => 'dashicons-calendar-alt',
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'rewrite'         => array( 'slug' => 'bible-plan' ),
				// Avoid colliding with ?hwbl_plan= on marketing pages (that query var 404s pages).
				'query_var'       => 'bible_plan',
				'capability_type' => 'post',
				'show_in_menu'    => true,
			)
		);
	}

	/**
	 * Register post meta for REST.
	 */
	public function register_meta() {
		register_post_meta(
			self::POST_TYPE,
			self::META_TOPIC,
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => array( $this, 'meta_auth' ),
			)
		);
		register_post_meta(
			self::POST_TYPE,
			self::META_LENGTH,
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'integer',
				'auth_callback' => array( $this, 'meta_auth' ),
			)
		);
		register_post_meta(
			self::POST_TYPE,
			self::META_SHAREABLE,
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'boolean',
				'auth_callback' => array( $this, 'meta_auth' ),
			)
		);
		register_post_meta(
			self::POST_TYPE,
			self::META_DAYS,
			array(
				'show_in_rest'  => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'day'       => array( 'type' => 'integer' ),
								'lesson_id' => array( 'type' => array( 'integer', 'null' ) ),
								'title'     => array( 'type' => 'string' ),
								'body'      => array( 'type' => 'string' ),
								'verse_ref' => array( 'type' => 'string' ),
							),
						),
					),
				),
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => array( $this, 'meta_auth' ),
			)
		);
	}

	/**
	 * Meta edit auth.
	 *
	 * @param bool   $allowed  Allowed.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @return bool
	 */
	public function meta_auth( $allowed, $meta_key, $post_id ) {
		unset( $allowed, $meta_key );
		return current_user_can( 'edit_post', (int) $post_id );
	}

	/**
	 * Admin meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'hwbl_plan_settings',
			__( 'Plan settings', 'hidden-word-bible-lessons' ),
			array( $this, 'render_settings_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
		add_meta_box(
			'hwbl_plan_days',
			__( 'Plan days', 'hidden-word-bible-lessons' ),
			array( $this, 'render_days_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin day-editor assets.
	 *
	 * @param string $hook Hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}
		wp_enqueue_script(
			'hwbl-plan-meta',
			HWBL_PLUGIN_URL . 'admin/js/plan-meta.js',
			array( 'jquery' ),
			HWBL_VERSION,
			true
		);
		wp_enqueue_style(
			'hwbl-admin',
			HWBL_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			HWBL_VERSION
		);
	}

	/**
	 * Settings meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render_settings_box( $post ) {
		wp_nonce_field( 'hwbl_save_plan_meta', 'hwbl_plan_meta_nonce' );
		$topic     = (string) get_post_meta( $post->ID, self::META_TOPIC, true );
		$shareable = (bool) get_post_meta( $post->ID, self::META_SHAREABLE, true );
		?>
		<p>
			<label for="hwbl_plan_topic"><strong><?php esc_html_e( 'Topic', 'hidden-word-bible-lessons' ); ?></strong></label><br />
			<select name="hwbl_plan_topic" id="hwbl_plan_topic" class="widefat">
				<?php foreach ( self::allowed_topics() as $slug ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $topic, $slug ); ?>>
						<?php echo esc_html( ucwords( str_replace( '-', ' ', $slug ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label>
				<input type="checkbox" name="hwbl_plan_shareable" value="1" <?php checked( $shareable ); ?> />
				<?php esc_html_e( 'Shareable (public gospel / outreach page)', 'hidden-word-bible-lessons' ); ?>
			</label>
		</p>
		<?php if ( $shareable ) : ?>
			<p class="description">
				<?php esc_html_e( 'When shareable is on, this plan can be viewed at /gospel/{id} without an account.', 'hidden-word-bible-lessons' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Days editor meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render_days_box( $post ) {
		$days = self::get_days( $post->ID );
		if ( ! $days ) {
			$days = array(
				array(
					'day'       => 1,
					'lesson_id' => 0,
					'title'     => '',
					'body'      => '',
					'verse_ref' => '',
				),
			);
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Ordered days. Link an existing Bible lesson and/or write freeform title, body, and verse reference.', 'hidden-word-bible-lessons' ); ?>
		</p>
		<div id="hwbl-plan-days" class="hwbl-plan-days">
			<?php foreach ( $days as $index => $day ) : ?>
				<?php $this->render_day_row( $index, $day ); ?>
			<?php endforeach; ?>
		</div>
		<p>
			<button type="button" class="button" id="hwbl-plan-add-day"><?php esc_html_e( 'Add day', 'hidden-word-bible-lessons' ); ?></button>
		</p>
		<script type="text/html" id="hwbl-plan-day-template">
			<?php
			$this->render_day_row(
				'__INDEX__',
				array(
					'day'       => 0,
					'lesson_id' => 0,
					'title'     => '',
					'body'      => '',
					'verse_ref' => '',
				)
			);
			?>
		</script>
		<?php
	}

	/**
	 * One day editor row.
	 *
	 * @param int|string           $index Index.
	 * @param array<string, mixed> $day   Day data.
	 */
	private function render_day_row( $index, $day ) {
		$lesson_id = isset( $day['lesson_id'] ) ? (int) $day['lesson_id'] : 0;
		$title     = isset( $day['title'] ) ? (string) $day['title'] : '';
		$body      = isset( $day['body'] ) ? (string) $day['body'] : '';
		$verse_ref = isset( $day['verse_ref'] ) ? (string) $day['verse_ref'] : '';
		$day_num   = isset( $day['day'] ) ? (int) $day['day'] : 0;
		?>
		<div class="hwbl-plan-day-row" style="border:1px solid #ccd0d4;padding:12px;margin-bottom:10px;background:#fff;">
			<p>
				<label>
					<?php esc_html_e( 'Day #', 'hidden-word-bible-lessons' ); ?>
					<input type="number" min="1" class="small-text hwbl-plan-day-num" name="hwbl_plan_days[<?php echo esc_attr( (string) $index ); ?>][day]" value="<?php echo esc_attr( (string) ( $day_num ?: ( (int) $index + 1 ) ) ); ?>" />
				</label>
				<button type="button" class="button-link-delete hwbl-plan-remove-day" style="float:right;"><?php esc_html_e( 'Remove', 'hidden-word-bible-lessons' ); ?></button>
			</p>
			<p>
				<label>
					<?php esc_html_e( 'Lesson ID (optional)', 'hidden-word-bible-lessons' ); ?>
					<input type="number" min="0" class="small-text" name="hwbl_plan_days[<?php echo esc_attr( (string) $index ); ?>][lesson_id]" value="<?php echo esc_attr( (string) $lesson_id ); ?>" />
				</label>
			</p>
			<p>
				<label>
					<?php esc_html_e( 'Title', 'hidden-word-bible-lessons' ); ?>
					<input type="text" class="widefat" name="hwbl_plan_days[<?php echo esc_attr( (string) $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" />
				</label>
			</p>
			<p>
				<label>
					<?php esc_html_e( 'Verse reference', 'hidden-word-bible-lessons' ); ?>
					<input type="text" class="widefat" name="hwbl_plan_days[<?php echo esc_attr( (string) $index ); ?>][verse_ref]" value="<?php echo esc_attr( $verse_ref ); ?>" placeholder="John 3:16" />
				</label>
			</p>
			<p>
				<label>
					<?php esc_html_e( 'Body', 'hidden-word-bible-lessons' ); ?>
					<textarea class="widefat" rows="4" name="hwbl_plan_days[<?php echo esc_attr( (string) $index ); ?>][body]"><?php echo esc_textarea( $body ); ?></textarea>
				</label>
			</p>
		</div>
		<?php
	}

	/**
	 * Save plan meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public function save_meta( $post_id, $post ) {
		unset( $post );
		if ( ! isset( $_POST['hwbl_plan_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hwbl_plan_meta_nonce'] ) ), 'hwbl_save_plan_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$topic = isset( $_POST['hwbl_plan_topic'] ) ? sanitize_key( wp_unslash( $_POST['hwbl_plan_topic'] ) ) : 'other';
		if ( ! in_array( $topic, self::allowed_topics(), true ) ) {
			$topic = 'other';
		}
		update_post_meta( $post_id, self::META_TOPIC, $topic );
		update_post_meta( $post_id, self::META_SHAREABLE, ! empty( $_POST['hwbl_plan_shareable'] ) ? 1 : 0 );

		$raw_days = isset( $_POST['hwbl_plan_days'] ) && is_array( $_POST['hwbl_plan_days'] ) ? wp_unslash( $_POST['hwbl_plan_days'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$days     = self::sanitize_days( $raw_days );
		update_post_meta( $post_id, self::META_DAYS, $days );
		update_post_meta( $post_id, self::META_LENGTH, count( $days ) );
	}

	/**
	 * Sanitize days array.
	 *
	 * @param array<int, mixed> $raw Raw days.
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_days( $raw ) {
		$out = array();
		if ( ! is_array( $raw ) ) {
			return $out;
		}
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title = isset( $row['title'] ) ? sanitize_text_field( (string) $row['title'] ) : '';
			$body  = isset( $row['body'] ) ? wp_kses_post( (string) $row['body'] ) : '';
			$ref   = isset( $row['verse_ref'] ) ? sanitize_text_field( (string) $row['verse_ref'] ) : '';
			$lid   = isset( $row['lesson_id'] ) ? absint( $row['lesson_id'] ) : 0;
			if ( ! $title && ! $body && ! $ref && ! $lid ) {
				continue;
			}
			$out[] = array(
				'day'       => isset( $row['day'] ) ? max( 1, absint( $row['day'] ) ) : ( count( $out ) + 1 ),
				'lesson_id' => $lid > 0 ? $lid : null,
				'title'     => $title,
				'body'      => $body,
				'verse_ref' => $ref,
			);
		}
		usort(
			$out,
			static function ( $a, $b ) {
				return (int) $a['day'] <=> (int) $b['day'];
			}
		);
		// Re-number sequentially.
		foreach ( $out as $i => &$day ) {
			$day['day'] = $i + 1;
		}
		unset( $day );
		return array_values( $out );
	}

	/**
	 * Get sanitized days for a plan.
	 *
	 * @param int $plan_id Plan ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_days( $plan_id ) {
		$raw = get_post_meta( (int) $plan_id, self::META_DAYS, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		return self::sanitize_days( $raw );
	}

	/**
	 * Public plan summary payload.
	 *
	 * @param int $plan_id Plan ID.
	 * @return array<string, mixed>|null
	 */
	/**
	 * @param int  $plan_id     Plan post ID.
	 * @param bool $enrich_days When true, resolve Scripture text for every day (slow; often remote).
	 *                          Lists and outlines should pass false and use get_day() for the active day.
	 */
	public static function get_plan_data( $plan_id, $enrich_days = true ) {
		$post = get_post( (int) $plan_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}
		$days = self::get_days( $plan_id );
		if ( $enrich_days ) {
			$days = array_map(
				static function ( $day ) {
					return self::enrich_day_verse( is_array( $day ) ? $day : array() );
				},
				$days
			);
		}
		return array(
			'id'          => (int) $plan_id,
			'title'       => get_the_title( $plan_id ),
			'slug'        => (string) $post->post_name,
			'excerpt'     => has_excerpt( $plan_id )
				? get_the_excerpt( $plan_id )
				: wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 40 ),
			'content'     => wp_kses_post( wpautop( (string) $post->post_content ) ),
			'topic'       => (string) get_post_meta( $plan_id, self::META_TOPIC, true ),
			'length'      => (int) ( get_post_meta( $plan_id, self::META_LENGTH, true ) ?: count( $days ) ),
			'shareable'   => (bool) get_post_meta( $plan_id, self::META_SHAREABLE, true ),
			'days'        => $days,
			'link'        => get_permalink( $plan_id ),
			'status'      => $post->post_status,
		);
	}

	/**
	 * One day payload (with optional lesson title fill).
	 *
	 * @param int $plan_id Plan ID.
	 * @param int $day_num Day number (1-based).
	 * @return array<string, mixed>|null
	 */
	public static function get_day( $plan_id, $day_num ) {
		$days = self::get_days( $plan_id );
		foreach ( $days as $day ) {
			if ( (int) $day['day'] === (int) $day_num ) {
				if ( empty( $day['title'] ) && ! empty( $day['lesson_id'] ) ) {
					$day['title'] = get_the_title( (int) $day['lesson_id'] );
				}
				$day['lesson_link'] = ! empty( $day['lesson_id'] ) ? get_permalink( (int) $day['lesson_id'] ) : '';
				return self::enrich_day_verse( $day );
			}
		}
		return null;
	}

	/**
	 * Attach parsed reference + Scripture text to a plan day.
	 *
	 * @param array<string, mixed> $day Day payload.
	 * @return array<string, mixed>
	 */
	public static function enrich_day_verse( array $day ) {
		$day['verse_text']  = '';
		$day['book_id']     = 0;
		$day['chapter']     = 0;
		$day['verse']       = 0;
		$day['verse_end']   = 0;
		$day['translation'] = '';

		$ref = isset( $day['verse_ref'] ) ? trim( (string) $day['verse_ref'] ) : '';
		if ( '' === $ref || ! class_exists( 'HWBL_Bible_Reader' ) ) {
			return $day;
		}

		$parsed = HWBL_Bible_Reader::parse_reference( $ref );
		if ( ! is_array( $parsed ) || empty( $parsed['book_id'] ) ) {
			return $day;
		}

		$translation = '';
		if ( class_exists( 'THW_Premium_Verse_Of_The_Day' ) ) {
			$translation = (string) THW_Premium_Verse_Of_The_Day::get_display_translation();
		}
		if ( '' === $translation && class_exists( 'HWBL_User_Preferences' ) ) {
			$translation = (string) HWBL_User_Preferences::get_preferred_translation();
		}
		if ( '' === $translation && class_exists( 'HWBL_Bible_Reader' ) ) {
			$translation = (string) HWBL_Bible_Reader::resolve_translation_for_request( '' );
		}
		$translation = sanitize_key( (string) $translation );

		$book_id   = (int) $parsed['book_id'];
		$chapter   = (int) $parsed['chapter'];
		$verse     = 0;
		$verse_end = 0;
		if ( isset( $parsed['verse_start'] ) ) {
			$verse = (int) $parsed['verse_start'];
			$verse_end = isset( $parsed['verse_end'] ) ? (int) $parsed['verse_end'] : $verse;
		} elseif ( isset( $parsed['verse'] ) ) {
			$verse = (int) $parsed['verse'];
			$verse_end = isset( $parsed['verse_end'] ) ? (int) $parsed['verse_end'] : $verse;
		}
		if ( $verse_end < $verse ) {
			$verse_end = $verse;
		}

		$day['book_id']     = $book_id;
		$day['chapter']     = $chapter;
		$day['verse']       = $verse;
		$day['verse_end']   = $verse_end;
		$day['translation'] = $translation;
		$day['verse_text']  = self::resolve_day_verse_text( $book_id, $chapter, $verse, $verse_end, $translation );

		return $day;
	}

	/**
	 * Resolve Scripture text for a verse range.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse_start Start verse.
	 * @param int    $verse_end   End verse.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public static function resolve_day_verse_text( $book_id, $chapter, $verse_start, $verse_end, $translation ) {
		$book_id     = (int) $book_id;
		$chapter     = (int) $chapter;
		$verse_start = (int) $verse_start;
		$verse_end   = max( $verse_start, (int) $verse_end );
		$translation = sanitize_key( (string) $translation );

		if ( $book_id < 1 || $chapter < 1 || $verse_start < 1 || '' === $translation ) {
			return '';
		}
		if ( ! class_exists( 'HWBL_Translation_Service' ) ) {
			return '';
		}

		$svc   = HWBL_Translation_Service::instance();
		$parts = array();
		for ( $v = $verse_start; $v <= $verse_end; $v++ ) {
			$text = '';
			if ( method_exists( $svc, 'get_echo_verse_text' ) ) {
				$row  = $svc->get_echo_verse_text( $book_id, $chapter, $v, $translation );
				$text = is_array( $row ) && ! empty( $row['text'] ) ? (string) $row['text'] : '';
			}
			if ( '' === $text ) {
				$text = (string) $svc->get_verse_text( $book_id, $chapter, $v, $translation );
			}
			if ( '' !== $text ) {
				$parts[] = $text;
			}
		}

		$text = trim( implode( ' ', $parts ) );
		if ( '' !== $text && class_exists( 'HWBL_Http_Utils' ) ) {
			$text = HWBL_Http_Utils::sanitize_bible_text( $text );
		}
		return $text;
	}
}
