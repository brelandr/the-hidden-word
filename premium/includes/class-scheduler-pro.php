<?php
/**
 * Advanced scheduling for premium.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Scheduler
 */
class THW_Premium_Scheduler {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter( 'hwbl_schedule_modes', array( __CLASS__, 'add_schedule_modes' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_schedule_meta_box' ) );
		add_action( 'save_post_hwbl_lesson', array( __CLASS__, 'save_schedule_override' ), 10, 2 );
	}

	/**
	 * Add premium schedule modes.
	 *
	 * @param array $modes Schedule modes.
	 * @return array
	 */
	public static function add_schedule_modes( $modes ) {
		$modes['month']   = __( 'Verse of the Month', 'hidden-word-bible-lessons' );
		$modes['manual']  = __( 'Manual Selection', 'hidden-word-bible-lessons' );
		$modes['custom']  = __( 'Custom Reading Track', 'hidden-word-bible-lessons' );
		return $modes;
	}

	/**
	 * Build custom track lesson IDs from per-lesson track order meta.
	 *
	 * @return int[]
	 */
	public static function get_custom_track() {
		$lesson_ids = get_posts(
			array(
				'post_type'      => array( 'hwbl_lesson', 'thw_lesson' ),
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
			)
		);

		$track = array();

		foreach ( $lesson_ids as $lesson_id ) {
			$order = (int) get_post_meta( $lesson_id, '_hwbl_track_order', true );
			if ( $order > 0 ) {
				$track[ $order ] = (int) $lesson_id;
			}
		}

		if ( empty( $track ) ) {
			return array();
		}

		ksort( $track, SORT_NUMERIC );

		return array_values( $track );
	}

	/**
	 * Persist custom track IDs for legacy reads.
	 */
	public static function rebuild_custom_track_option() {
		update_option( 'thw_custom_track', self::get_custom_track(), false );
	}

	/**
	 * Get lesson for manual schedule mode.
	 *
	 * @return int
	 */
	public static function get_manual_lesson_id() {
		return (int) get_option( 'thw_manual_lesson_id', 0 );
	}

	/**
	 * Filter current lesson ID for premium schedule modes.
	 *
	 * @param int    $lesson_id Current lesson ID.
	 * @param int    $slot      Schedule slot.
	 * @param string $mode      Schedule mode.
	 * @return int
	 */
	public static function filter_current_lesson_id( $lesson_id, $slot, $mode ) {
		unset( $slot );
		if ( 'manual' === $mode ) {
			$manual = self::get_manual_lesson_id();
			return $manual ? HWBL_Scheduler::resolve_lesson_id( $manual ) : HWBL_Scheduler::resolve_lesson_id( $lesson_id );
		}
		if ( 'month' === $mode ) {
			$month_slot = (int) gmdate( 'n' );
			$lesson_num = HWBL_Curriculum::slot_to_lesson_number( $month_slot );
			$monthly    = HWBL_Scheduler::get_lesson_id_by_number( $lesson_num );
			return $monthly ? $monthly : HWBL_Scheduler::resolve_lesson_id( $lesson_id );
		}
		if ( 'custom' === $mode ) {
			$track = self::get_custom_track();
			if ( ! empty( $track ) ) {
				$index = ( HWBL_Scheduler::get_current_slot() - 1 ) % count( $track );
				return HWBL_Scheduler::resolve_lesson_id( (int) $track[ $index ] );
			}
		}
		return HWBL_Scheduler::resolve_lesson_id( $lesson_id );
	}

	/**
	 * Get current lesson respecting premium modes.
	 *
	 * @return int
	 */
	public static function get_current_lesson_id() {
		$mode = get_option( 'hwbl_schedule_mode', 'week' );

		if ( 'manual' === $mode ) {
			$manual = self::get_manual_lesson_id();
			if ( $manual ) {
				return $manual;
			}
		}

		if ( 'month' === $mode ) {
			$month_slot = (int) gmdate( 'n' );
			$lesson_num = HWBL_Curriculum::slot_to_lesson_number( $month_slot );
			return HWBL_Scheduler::get_lesson_id_by_number( $lesson_num );
		}

		if ( 'custom' === $mode ) {
			$track = self::get_custom_track();
			if ( ! empty( $track ) ) {
				$index = ( HWBL_Scheduler::get_current_slot() - 1 ) % count( $track );
				return (int) $track[ $index ];
			}
		}

		return HWBL_Scheduler::get_current_lesson_id();
	}

	/**
	 * Add schedule override meta box.
	 */
	public static function add_schedule_meta_box() {
		add_meta_box(
			'thw_schedule_override',
			__( 'Schedule Override', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_meta_box' ),
			'hwbl_lesson',
			'side',
			'default'
		);
	}

	/**
	 * Render schedule meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'thw_schedule_override', 'thw_schedule_override_nonce' );
		$track_order = (int) get_post_meta( $post->ID, '_hwbl_track_order', true );
		?>
		<p>
			<label for="thw_track_order"><?php esc_html_e( 'Custom Track Order', 'hidden-word-bible-lessons' ); ?></label>
			<input type="number" id="thw_track_order" name="thw_track_order" value="<?php echo esc_attr( $track_order ); ?>" min="0" class="widefat" />
		</p>
		<p class="description"><?php esc_html_e( 'Used when schedule mode is Custom Reading Track.', 'hidden-word-bible-lessons' ); ?></p>
		<?php
	}

	/**
	 * Save schedule override meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_schedule_override( $post_id, $post ) {
		unset( $post );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Missing nonce: autosave / quick edit did not submit this meta box.
		if ( ! isset( $_POST['thw_schedule_override_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['thw_schedule_override_nonce'] ) ), 'thw_schedule_override' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'hidden-word-bible-lessons' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		if ( isset( $_POST['thw_track_order'] ) ) {
			update_post_meta( $post_id, '_hwbl_track_order', absint( wp_unslash( $_POST['thw_track_order'] ) ) );
			self::rebuild_custom_track_option();
		}
	}
}
