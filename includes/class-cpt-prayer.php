<?php
/**
 * Prayer requests CPT + REST + shortcode.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_CPT_Prayer
 */
class HWBL_CPT_Prayer {

	const POST_TYPE      = 'hwbl_prayer_request';
	const META_COHORT    = '_hwbl_prayer_cohort_id';
	const META_TEXT      = '_hwbl_prayer_text';
	const META_STATUS    = '_hwbl_prayer_status';
	const META_ANSWERED  = '_hwbl_prayer_answered_note';
	const META_PRAY_COUNT = '_hwbl_prayer_pray_count';
	const META_PRAYED_BY = '_hwbl_prayer_prayed_by';
	const META_ATTRIBUTION = '_hwbl_prayer_attribution';

	/**
	 * Allowed public name attribution modes.
	 *
	 * @return string[]
	 */
	public static function allowed_attributions() {
		return array( 'full', 'first', 'anonymous' );
	}

	/**
	 * Sanitize attribution mode.
	 *
	 * @param string $raw Raw value.
	 * @return string
	 */
	public static function sanitize_attribution( $raw ) {
		$key = sanitize_key( (string) $raw );
		return in_array( $key, self::allowed_attributions(), true ) ? $key : 'full';
	}

	/**
	 * Public display label for a prayer author.
	 *
	 * @param int    $author_id    Author user ID.
	 * @param string $attribution  full|first|anonymous.
	 * @return string
	 */
	public static function author_display_label( $author_id, $attribution ) {
		$attribution = self::sanitize_attribution( $attribution );
		if ( 'anonymous' === $attribution ) {
			return __( 'Anonymous', 'hidden-word-bible-lessons' );
		}
		$user = get_userdata( (int) $author_id );
		if ( ! $user ) {
			return __( 'Friend', 'hidden-word-bible-lessons' );
		}
		$name = trim( (string) $user->display_name );
		if ( '' === $name ) {
			return __( 'Friend', 'hidden-word-bible-lessons' );
		}
		if ( 'first' === $attribution ) {
			$parts = preg_split( '/\s+/', $name );
			$first = is_array( $parts ) && ! empty( $parts[0] ) ? $parts[0] : $name;
			return $first ? $first : __( 'Friend', 'hidden-word-bible-lessons' );
		}
		return $name;
	}

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'admin_post_hwbl_prayer_create', array( __CLASS__, 'handle_web_create' ) );
		add_shortcode( 'hwbl_prayer', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Front-end form create (logged-in).
	 */
	public static function handle_web_create() {
		if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
			wp_die( esc_html__( 'You must be signed in.', 'hidden-word-bible-lessons' ) );
		}
		check_admin_referer( 'hwbl_prayer_create', 'hwbl_prayer_nonce' );
		$text         = isset( $_POST['hwbl_prayer_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hwbl_prayer_text'] ) ) : '';
		$attribution  = isset( $_POST['hwbl_prayer_attribution'] )
			? self::sanitize_attribution( wp_unslash( $_POST['hwbl_prayer_attribution'] ) )
			: 'full';
		if ( '' === trim( $text ) ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
			exit;
		}
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => wp_trim_words( $text, 8, '…' ),
				'post_author' => get_current_user_id(),
			),
			true
		);
		if ( ! is_wp_error( $post_id ) && $post_id ) {
			update_post_meta( $post_id, self::META_TEXT, $text );
			update_post_meta( $post_id, self::META_COHORT, 0 );
			update_post_meta( $post_id, self::META_STATUS, 'open' );
			update_post_meta( $post_id, self::META_PRAY_COUNT, 0 );
			update_post_meta( $post_id, self::META_ATTRIBUTION, $attribution );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
		exit;
	}

	/**
	 * Register CPT.
	 */
	public static function register_post_type() {
		$can_translate = (bool) did_action( 'init' );
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => $can_translate ? __( 'Prayer Requests', 'hidden-word-bible-lessons' ) : 'Prayer Requests',
					'singular_name' => $can_translate ? __( 'Prayer Request', 'hidden-word-bible-lessons' ) : 'Prayer Request',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=hwbl_lesson',
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'supports'        => array( 'title', 'author' ),
			)
		);
	}

	/**
	 * REST.
	 */
	public static function register_routes() {
		$auth = static function () {
			return is_user_logged_in() && current_user_can( 'read' );
		};

		register_rest_route(
			'hwbl/v1',
			'/prayer',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_list' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_create' ),
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/prayer/(?P<id>\d+)/pray',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_pray' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			'hwbl/v1',
			'/prayer/(?P<id>\d+)/answered',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_answered' ),
				'permission_callback' => $auth,
			)
		);
	}

	/**
	 * Serialize prayer.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_payload( $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}
		$attribution = self::sanitize_attribution(
			(string) ( get_post_meta( $post_id, self::META_ATTRIBUTION, true ) ?: 'full' )
		);
		$author_id   = (int) $post->post_author;
		$can_see_id  = (
			get_current_user_id() === $author_id
			|| current_user_can( 'edit_post', (int) $post_id )
		);
		$payload     = array(
			'id'             => (int) $post_id,
			'text'           => (string) get_post_meta( $post_id, self::META_TEXT, true ),
			'status'         => (string) ( get_post_meta( $post_id, self::META_STATUS, true ) ?: 'open' ),
			'cohort_id'      => (int) get_post_meta( $post_id, self::META_COHORT, true ),
			'pray_count'     => (int) get_post_meta( $post_id, self::META_PRAY_COUNT, true ),
			'answered_note'  => (string) get_post_meta( $post_id, self::META_ANSWERED, true ),
			'attribution'    => $attribution,
			'author_display' => self::author_display_label( $author_id, $attribution ),
			'author'         => self::author_display_label( $author_id, $attribution ),
		);
		if ( $can_see_id ) {
			$payload['author_id'] = $author_id;
		} else {
			$payload['author_id'] = null;
		}
		return $payload;
	}

	/**
	 * GET list (personal + cohort-scoped).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_list( $request ) {
		$user_id   = get_current_user_id();
		$cohort_id = absint( $request->get_param( 'cohort_id' ) );
		$scope     = sanitize_key( (string) $request->get_param( 'scope' ) );

		$meta_query = array();
		if ( 'personal' === $scope || ( ! $cohort_id && 'cohort' !== $scope ) ) {
			$args = array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 50,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_COHORT,
						'value' => 0,
					),
				),
			);
			if ( $cohort_id > 0 ) {
				unset( $args['author'], $args['meta_query'] );
				$args['meta_query'] = array(
					array(
						'key'   => self::META_COHORT,
						'value' => $cohort_id,
					),
				);
			}
		} else {
			$args = array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_COHORT,
						'value' => $cohort_id > 0 ? $cohort_id : 0,
					),
				),
			);
		}

		$posts = get_posts( $args );
		$out   = array();
		foreach ( $posts as $post ) {
			$payload = self::get_payload( $post->ID );
			if ( $payload ) {
				$out[] = $payload;
			}
		}
		return rest_ensure_response( array( 'prayers' => $out ) );
	}

	/**
	 * POST create.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_create( $request ) {
		$params    = $request->get_json_params();
		$params    = is_array( $params ) ? $params : $request->get_params();
		$text         = isset( $params['text'] ) ? sanitize_textarea_field( (string) $params['text'] ) : '';
		$cohort_id    = isset( $params['cohort_id'] ) ? absint( $params['cohort_id'] ) : 0;
		$attribution  = isset( $params['attribution'] )
			? self::sanitize_attribution( (string) $params['attribution'] )
			: 'full';
		if ( '' === trim( $text ) ) {
			return new WP_Error( 'hwbl_prayer_empty', __( 'Prayer text is required.', 'hidden-word-bible-lessons' ), array( 'status' => 400 ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => wp_trim_words( $text, 8, '…' ),
				'post_author' => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		update_post_meta( $post_id, self::META_TEXT, $text );
		update_post_meta( $post_id, self::META_COHORT, $cohort_id );
		update_post_meta( $post_id, self::META_STATUS, 'open' );
		update_post_meta( $post_id, self::META_PRAY_COUNT, 0 );
		update_post_meta( $post_id, self::META_ATTRIBUTION, $attribution );

		return rest_ensure_response( self::get_payload( $post_id ) );
	}

	/**
	 * POST increment pray count.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_pray( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'hwbl_prayer_missing', __( 'Prayer not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		$user_id = get_current_user_id();
		$prayed  = get_post_meta( $post_id, self::META_PRAYED_BY, true );
		$prayed  = is_array( $prayed ) ? $prayed : array();
		if ( ! in_array( $user_id, array_map( 'intval', $prayed ), true ) ) {
			$prayed[] = $user_id;
			update_post_meta( $post_id, self::META_PRAYED_BY, $prayed );
			$count = (int) get_post_meta( $post_id, self::META_PRAY_COUNT, true );
			update_post_meta( $post_id, self::META_PRAY_COUNT, $count + 1 );

			if ( class_exists( 'HWBL_Push_Notifications' ) && (int) $post->post_author !== $user_id ) {
				$tokens = HWBL_Push_Notifications::get_tokens( (int) $post->post_author );
				if ( $tokens ) {
					HWBL_Push_Notifications::send_to_tokens(
						$tokens,
						array(
							'title' => __( 'Someone is praying', 'hidden-word-bible-lessons' ),
							'body'  => __( 'A friend is praying for your request.', 'hidden-word-bible-lessons' ),
							'data'  => array( 'url' => 'hwbl://prayer' ),
						)
					);
				}
			}
		}
		return rest_ensure_response( self::get_payload( $post_id ) );
	}

	/**
	 * POST mark answered (author only).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_answered( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'hwbl_prayer_missing', __( 'Prayer not found.', 'hidden-word-bible-lessons' ), array( 'status' => 404 ) );
		}
		if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'hwbl_forbidden', __( 'Only the author can mark this answered.', 'hidden-word-bible-lessons' ), array( 'status' => 403 ) );
		}
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : $request->get_params();
		$note   = isset( $params['note'] ) ? sanitize_textarea_field( (string) $params['note'] ) : '';
		update_post_meta( $post_id, self::META_STATUS, 'answered' );
		update_post_meta( $post_id, self::META_ANSWERED, $note );

		if ( class_exists( 'HWBL_Push_Notifications' ) ) {
			$prayed = get_post_meta( $post_id, self::META_PRAYED_BY, true );
			$prayed = is_array( $prayed ) ? $prayed : array();
			foreach ( $prayed as $uid ) {
				$uid = (int) $uid;
				if ( $uid < 1 || $uid === get_current_user_id() ) {
					continue;
				}
				$tokens = HWBL_Push_Notifications::get_tokens( $uid );
				if ( $tokens ) {
					HWBL_Push_Notifications::send_to_tokens(
						$tokens,
						array(
							'title' => __( 'Prayer answered', 'hidden-word-bible-lessons' ),
							'body'  => __( 'A prayer you supported was marked answered.', 'hidden-word-bible-lessons' ),
							'data'  => array( 'url' => 'hwbl://prayer' ),
						)
					);
				}
			}
		}

		return rest_ensure_response( self::get_payload( $post_id ) );
	}

	/**
	 * Personal prayer list shortcode.
	 *
	 * @param array $atts Atts.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="hwbl-empty">' . esc_html__( 'Sign in to view your prayer list.', 'hidden-word-bible-lessons' ) . '</p>';
		}
		$user_id = get_current_user_id();
		$posts   = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 50,
			)
		);
		ob_start();
		echo '<div class="hwbl-prayer-list">';
		echo '<h3>' . esc_html__( 'My prayer requests', 'hidden-word-bible-lessons' ) . '</h3>';
		echo '<form class="hwbl-prayer-create" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="hwbl_prayer_create" />';
		wp_nonce_field( 'hwbl_prayer_create', 'hwbl_prayer_nonce' );
		echo '<p><label for="hwbl-prayer-text">' . esc_html__( 'New request', 'hidden-word-bible-lessons' ) . '</label><br />';
		echo '<textarea id="hwbl-prayer-text" name="hwbl_prayer_text" rows="3" class="widefat" required></textarea></p>';
		echo '<p><label for="hwbl-prayer-attribution">' . esc_html__( 'Show my name as', 'hidden-word-bible-lessons' ) . '</label><br />';
		echo '<select id="hwbl-prayer-attribution" name="hwbl_prayer_attribution">';
		echo '<option value="full">' . esc_html__( 'Full name', 'hidden-word-bible-lessons' ) . '</option>';
		echo '<option value="first">' . esc_html__( 'First name only', 'hidden-word-bible-lessons' ) . '</option>';
		echo '<option value="anonymous">' . esc_html__( 'Anonymous', 'hidden-word-bible-lessons' ) . '</option>';
		echo '</select></p>';
		echo '<p><button type="submit" class="hwbl-btn">' . esc_html__( 'Submit prayer request', 'hidden-word-bible-lessons' ) . '</button></p>';
		echo '</form>';
		if ( ! $posts ) {
			echo '<p class="hwbl-empty">' . esc_html__( 'No prayer requests yet.', 'hidden-word-bible-lessons' ) . '</p>';
		} else {
			echo '<ul>';
			foreach ( $posts as $post ) {
				$payload = self::get_payload( $post->ID );
				if ( ! $payload ) {
					continue;
				}
				echo '<li><strong>[' . esc_html( $payload['status'] ) . ']</strong> ';
				echo esc_html( (string) $payload['author_display'] ) . ': ' . esc_html( $payload['text'] );
				if ( $payload['pray_count'] > 0 ) {
					echo ' <em>(' . esc_html( (string) $payload['pray_count'] ) . ' praying)</em>';
				}
				echo '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * Cohort prayer wall HTML fragment.
	 *
	 * @param int $cohort_id Cohort ID.
	 * @return string
	 */
	public static function render_cohort_wall( $cohort_id ) {
		$cohort_id = (int) $cohort_id;
		if ( $cohort_id < 1 || ! is_user_logged_in() ) {
			return '';
		}
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 30,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_COHORT,
						'value' => $cohort_id,
					),
				),
			)
		);
		ob_start();
		echo '<div class="hwbl-prayer-wall" data-cohort-id="' . esc_attr( (string) $cohort_id ) . '">';
		echo '<h3>' . esc_html__( 'Prayer wall', 'hidden-word-bible-lessons' ) . '</h3>';
		if ( ! $posts ) {
			echo '<p class="hwbl-empty">' . esc_html__( 'No cohort prayer requests yet.', 'hidden-word-bible-lessons' ) . '</p>';
		} else {
			echo '<ul>';
			foreach ( $posts as $post ) {
				$payload = self::get_payload( $post->ID );
				if ( ! $payload ) {
					continue;
				}
				echo '<li><strong>' . esc_html( (string) $payload['author_display'] ) . '</strong>: ' . esc_html( $payload['text'] );
				echo ' <span>(' . esc_html( (string) $payload['pray_count'] ) . ')</span></li>';
			}
			echo '</ul>';
		}
		echo '</div>';
		return (string) ob_get_clean();
	}
}
