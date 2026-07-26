<?php
/**
 * Small-group cohorts and leader rosters.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Cohort
 */
class THW_Premium_Cohort {

	const MEMBER_META = 'thw_cohort_id';
	const LEADER_ROLE   = 'thw_group_leader';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_leader_role' ) );
		add_action( 'init', array( __CLASS__, 'register_capabilities' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_thw_cohort', array( __CLASS__, 'save_meta' ), 10, 2 );
		thw_premium_register_shortcode( 'thw_cohort_roster', array( __CLASS__, 'render_roster_shortcode' ) );
		add_action( 'groups_join_group', array( __CLASS__, 'maybe_assign_cohort_from_bp_group' ), 10, 2 );
	}

	/**
	 * Register cohort CPT.
	 */
	public static function register_post_type() {
		register_post_type(
			'thw_cohort',
			array(
				'labels'       => array(
					'name'          => __( 'Cohorts', 'hidden-word-bible-lessons' ),
					'singular_name' => __( 'Cohort', 'hidden-word-bible-lessons' ),
					'add_new_item'  => __( 'Add Cohort', 'hidden-word-bible-lessons' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'edit.php?post_type=hwbl_lesson',
				'supports'     => array( 'title' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Register group leader role.
	 */
	public static function register_leader_role() {
		if ( get_role( self::LEADER_ROLE ) ) {
			return;
		}

		add_role(
			self::LEADER_ROLE,
			__( 'Group Leader', 'hidden-word-bible-lessons' ),
			array(
				'read'             => true,
				'edit_thw_cohorts' => true,
			)
		);
	}

	/**
	 * Register leader capability for cohort rosters.
	 */
	public static function register_capabilities() {
		$roles = array( 'administrator', 'editor', self::LEADER_ROLE );
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( 'edit_thw_cohorts' );
			}
		}
	}

	/**
	 * Add cohort meta boxes.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'thw_cohort_members',
			__( 'Cohort Members', 'hidden-word-bible-lessons' ),
			array( __CLASS__, 'render_members_box' ),
			'thw_cohort',
			'normal',
			'default'
		);
	}

	/**
	 * Render members meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_members_box( $post ) {
		wp_nonce_field( 'thw_save_cohort', 'thw_cohort_nonce' );
		$members = get_post_meta( $post->ID, '_thw_cohort_members', true );
		$members = is_array( $members ) ? $members : array();
		$track   = get_post_meta( $post->ID, '_thw_cohort_track', true );
		$track   = is_array( $track ) ? implode( ',', $track ) : '';
		$leader  = (int) get_post_meta( $post->ID, '_thw_leader_user_id', true );
		$bp_group = (int) get_post_meta( $post->ID, '_thw_bp_group_id', true );
		$use_global = (bool) get_post_meta( $post->ID, '_thw_cohort_use_global_track', true );
		?>
		<p>
			<label for="thw_cohort_members"><strong><?php esc_html_e( 'Member user IDs or emails (one per line)', 'hidden-word-bible-lessons' ); ?></strong></label>
			<textarea id="thw_cohort_members" name="thw_cohort_members" class="widefat" rows="6"><?php echo esc_textarea( implode( "\n", $members ) ); ?></textarea>
		</p>
		<p>
			<label for="thw_leader_user_id"><strong><?php esc_html_e( 'Leader user ID', 'hidden-word-bible-lessons' ); ?></strong></label>
			<input type="number" id="thw_leader_user_id" name="thw_leader_user_id" class="small-text" value="<?php echo esc_attr( (string) $leader ); ?>" min="0" />
		</p>
		<p>
			<label>
				<input type="checkbox" name="thw_cohort_use_global_track" value="1" <?php checked( $use_global ); ?> />
				<?php esc_html_e( 'Use site-wide custom reading track', 'hidden-word-bible-lessons' ); ?>
			</label>
		</p>
		<p>
			<label for="thw_cohort_track"><strong><?php esc_html_e( 'Lesson post IDs in track order (comma-separated)', 'hidden-word-bible-lessons' ); ?></strong></label>
			<input type="text" id="thw_cohort_track" name="thw_cohort_track" class="widefat" value="<?php echo esc_attr( $track ); ?>" />
		</p>
		<p>
			<label for="thw_bp_group_id"><strong><?php esc_html_e( 'BuddyPress group ID (optional auto-join)', 'hidden-word-bible-lessons' ); ?></strong></label>
			<input type="number" id="thw_bp_group_id" name="thw_bp_group_id" class="small-text" value="<?php echo esc_attr( (string) $bp_group ); ?>" min="0" />
		</p>
		<p class="description"><?php esc_html_e( 'Use the [thw_cohort_roster cohort="slug"] shortcode on a leader page.', 'hidden-word-bible-lessons' ); ?></p>
		<?php
	}

	/**
	 * Save cohort meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_meta( $post_id, $post ) {
		unset( $post );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Missing nonce: autosave / quick edit did not submit this meta box.
		if ( ! isset( $_POST['thw_cohort_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['thw_cohort_nonce'] ) ), 'thw_save_cohort' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'hidden-word-bible-lessons' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Unauthorized', 'hidden-word-bible-lessons' ) );
		}

		$raw_members = isset( $_POST['thw_cohort_members'] ) ? sanitize_textarea_field( wp_unslash( $_POST['thw_cohort_members'] ) ) : '';
		$lines       = array_filter(
			array_map(
				static function ( $line ) {
					return sanitize_text_field( trim( (string) $line ) );
				},
				preg_split( '/\r\n|\r|\n/', $raw_members )
			)
		);
		update_post_meta( $post_id, '_thw_cohort_members', $lines );

		$track_raw = isset( $_POST['thw_cohort_track'] ) ? sanitize_text_field( wp_unslash( $_POST['thw_cohort_track'] ) ) : '';
		$track_ids = array_filter( array_map( 'absint', explode( ',', $track_raw ) ) );
		update_post_meta( $post_id, '_thw_cohort_track', $track_ids );

		$leader_id = isset( $_POST['thw_leader_user_id'] ) ? absint( wp_unslash( $_POST['thw_leader_user_id'] ) ) : 0;
		update_post_meta( $post_id, '_thw_leader_user_id', $leader_id );
		// Role grant can escalate privileges; only users who can promote users may assign it.
		if ( $leader_id && current_user_can( 'promote_users' ) ) {
			self::assign_leader_role( $leader_id );
		}

		$bp_group_id = isset( $_POST['thw_bp_group_id'] ) ? absint( wp_unslash( $_POST['thw_bp_group_id'] ) ) : 0;
		update_post_meta( $post_id, '_thw_bp_group_id', $bp_group_id );

		$use_global = isset( $_POST['thw_cohort_use_global_track'] )
			? rest_sanitize_boolean( wp_unslash( $_POST['thw_cohort_use_global_track'] ) )
			: false;
		update_post_meta( $post_id, '_thw_cohort_use_global_track', $use_global ? 1 : 0 );

		foreach ( $lines as $line ) {
			$user = is_numeric( $line ) ? get_user_by( 'id', (int) $line ) : get_user_by( 'email', $line );
			if ( $user ) {
				update_user_meta( $user->ID, self::MEMBER_META, $post_id );
			}
		}
	}

	/**
	 * Assign the group leader role to a user.
	 *
	 * @param int $user_id User ID.
	 */
	public static function assign_leader_role( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		if ( user_can( $user, 'manage_options' ) || user_can( $user, 'edit_posts' ) ) {
			return;
		}

		$user->add_role( self::LEADER_ROLE );
	}

	/**
	 * Whether the current user may view a cohort roster.
	 *
	 * @param int $cohort_id Cohort post ID.
	 * @return bool
	 */
	public static function can_view_roster( $cohort_id ) {
		if ( current_user_can( 'edit_thw_cohorts' ) ) {
			return true;
		}

		$leader_id = (int) get_post_meta( $cohort_id, '_thw_leader_user_id', true );
		return $leader_id > 0 && get_current_user_id() === $leader_id;
	}

	/**
	 * Resolve cohort post by slug.
	 *
	 * @param string $slug Cohort slug.
	 * @return WP_Post|null
	 */
	public static function get_cohort_by_slug( $slug ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'thw_cohort',
				'name'           => sanitize_title( $slug ),
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			)
		);
		return $query->have_posts() ? $query->posts[0] : null;
	}

	/**
	 * Get member user IDs for a cohort.
	 *
	 * @param int $cohort_id Cohort post ID.
	 * @return int[]
	 */
	public static function get_member_user_ids( $cohort_id ) {
		$lines = get_post_meta( $cohort_id, '_thw_cohort_members', true );
		if ( ! is_array( $lines ) ) {
			return array();
		}

		$ids = array();
		foreach ( $lines as $line ) {
			$user = is_numeric( $line ) ? get_user_by( 'id', (int) $line ) : get_user_by( 'email', $line );
			if ( $user ) {
				$ids[] = (int) $user->ID;
			}
		}
		return $ids;
	}

	/**
	 * Resolve reading track for a cohort.
	 *
	 * @param int $cohort_id Cohort post ID.
	 * @return int[]
	 */
	public static function get_cohort_track( $cohort_id ) {
		if ( get_post_meta( $cohort_id, '_thw_cohort_use_global_track', true ) ) {
			$track = THW_Premium_Scheduler::get_custom_track();
			if ( ! empty( $track ) ) {
				return $track;
			}
		}

		$track = get_post_meta( $cohort_id, '_thw_cohort_track', true );
		if ( is_array( $track ) && ! empty( $track ) ) {
			return array_map( 'intval', $track );
		}

		$fallback = HWBL_Scheduler::get_current_lesson_id();
		return $fallback ? array( (int) $fallback ) : array();
	}

	/**
	 * Current lesson for a member on a cohort track.
	 *
	 * @param int   $user_id User ID.
	 * @param int[] $track   Lesson post IDs in order.
	 * @return int
	 */
	public static function get_member_current_lesson_id( $user_id, $track ) {
		if ( empty( $track ) ) {
			return (int) HWBL_Scheduler::get_current_lesson_id();
		}

		foreach ( $track as $lesson_id ) {
			if ( ! THW_Premium_Progress::is_memorized( $user_id, (int) $lesson_id ) ) {
				return (int) $lesson_id;
			}
		}

		return (int) end( $track );
	}

	/**
	 * Assign cohort when a user joins a linked BuddyPress group.
	 *
	 * @param int $group_id Group ID.
	 * @param int $user_id  User ID.
	 */
	public static function maybe_assign_cohort_from_bp_group( $group_id, $user_id ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'thw_cohort',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Cohort lookup by BuddyPress group ID.
				'meta_key'       => '_thw_bp_group_id',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Cohort lookup by BuddyPress group ID.
				'meta_value'     => (int) $group_id,
				'fields'         => 'ids',
			)
		);

		if ( empty( $query->posts[0] ) ) {
			return;
		}

		$cohort_id = (int) $query->posts[0];
		update_user_meta( $user_id, self::MEMBER_META, $cohort_id );

		$members = get_post_meta( $cohort_id, '_thw_cohort_members', true );
		$members = is_array( $members ) ? $members : array();
		$user    = get_userdata( $user_id );
		if ( $user && ! in_array( (string) $user->ID, $members, true ) && ! in_array( $user->user_email, $members, true ) ) {
			$members[] = (string) $user->ID;
			update_post_meta( $cohort_id, '_thw_cohort_members', $members );
		}
	}

	/**
	 * Render cohort roster shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render_roster_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'cohort' => '' ), $atts, 'thw_cohort_roster' );
		$post = self::get_cohort_by_slug( $atts['cohort'] );
		if ( ! $post ) {
			return '<p>' . esc_html__( 'Cohort not found.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		if ( ! self::can_view_roster( $post->ID ) ) {
			return '<p>' . esc_html__( 'You do not have permission to view this roster.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$member_ids = self::get_member_user_ids( $post->ID );
		$track      = self::get_cohort_track( $post->ID );

		$html  = '<table class="thw-cohort-roster"><thead><tr>';
		$html .= '<th>' . esc_html__( 'Member', 'hidden-word-bible-lessons' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Current lesson', 'hidden-word-bible-lessons' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Memorized', 'hidden-word-bible-lessons' ) . '</th>';
		$html .= '<th>' . esc_html__( 'Last activity', 'hidden-word-bible-lessons' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $member_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}
			$current_id = self::get_member_current_lesson_id( $user_id, $track );
			$memorized  = THW_Premium_Progress::is_memorized( $user_id, $current_id );
			$lesson     = HWBL_CPT_Lesson::get_lesson_data( $current_id );
			$last       = THW_Premium_Progress::get_last_activity_date( $user_id );
			$reference  = isset( $lesson['reference'] ) ? (string) $lesson['reference'] : __( '—', 'hidden-word-bible-lessons' );
			$html      .= '<tr>';
			$html      .= '<td>' . esc_html( $user->display_name ) . '</td>';
			$html      .= '<td>' . esc_html( $reference ) . '</td>';
			$html      .= '<td>' . ( $memorized ? esc_html__( 'Yes', 'hidden-word-bible-lessons' ) : esc_html__( 'No', 'hidden-word-bible-lessons' ) ) . '</td>';
			$html      .= '<td>' . ( $last ? esc_html( $last ) : esc_html__( '—', 'hidden-word-bible-lessons' ) ) . '</td>';
			$html      .= '</tr>';
		}

		$html .= '</tbody></table>';
		return '<div class="thw-cohort-roster-wrap">' . $html . '</div>';
	}
}
