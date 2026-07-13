<?php
/**
 * Lesson meta boxes and save handlers.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class THW_Lesson_Meta
 */
class THW_Lesson_Meta {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_thw_lesson', array( $this, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'thw_verse_reference',
			__( 'Verse Reference', 'the-hidden-word' ),
			array( $this, 'render_verse_reference' ),
			'thw_lesson',
			'normal',
			'high'
		);

		add_meta_box(
			'thw_lesson_content',
			__( 'Lesson Content', 'the-hidden-word' ),
			array( $this, 'render_lesson_content' ),
			'thw_lesson',
			'normal',
			'default'
		);

		add_meta_box(
			'thw_echo_verses',
			__( 'The Echo — Follow-on Verses', 'the-hidden-word' ),
			array( $this, 'render_echo_verses' ),
			'thw_lesson',
			'normal',
			'default'
		);

		add_meta_box(
			'thw_discussion',
			__( 'Discussion Questions', 'the-hidden-word' ),
			array( $this, 'render_discussion' ),
			'thw_lesson',
			'normal',
			'default'
		);
	}

	/**
	 * Enqueue admin scripts on lesson edit screen.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'thw_lesson' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'thw-lesson-meta',
			THW_PLUGIN_URL . 'admin/js/lesson-meta.js',
			array( 'jquery' ),
			THW_VERSION,
			true
		);

		wp_enqueue_style(
			'thw-admin',
			THW_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			THW_VERSION
		);
	}

	/**
	 * Render verse reference meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_verse_reference( $post ) {
		wp_nonce_field( 'thw_save_lesson_meta', 'thw_lesson_meta_nonce' );

		$books       = THW_Books::get_all();
		$book_id     = (int) get_post_meta( $post->ID, '_thw_book_id', true );
		$chapter     = (int) get_post_meta( $post->ID, '_thw_chapter', true );
		$verse_start = (int) get_post_meta( $post->ID, '_thw_verse_start', true );
		$verse_end   = (int) get_post_meta( $post->ID, '_thw_verse_end', true );
		$week_number = (int) get_post_meta( $post->ID, '_thw_week_number', true );
		?>
		<table class="form-table thw-meta-table">
			<tr>
				<th><label for="thw_week_number"><?php esc_html_e( 'Week Number', 'the-hidden-word' ); ?></label></th>
				<td>
					<input type="number" id="thw_week_number" name="thw_week_number" value="<?php echo esc_attr( $week_number ); ?>" min="1" max="52" />
					<p class="description"><?php esc_html_e( 'Slot in the 52-week curriculum (1–52).', 'the-hidden-word' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="thw_book_id"><?php esc_html_e( 'Book', 'the-hidden-word' ); ?></label></th>
				<td>
					<select id="thw_book_id" name="thw_book_id">
						<option value=""><?php esc_html_e( 'Select book…', 'the-hidden-word' ); ?></option>
						<?php foreach ( $books as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $book_id, (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="thw_chapter"><?php esc_html_e( 'Chapter', 'the-hidden-word' ); ?></label></th>
				<td><input type="number" id="thw_chapter" name="thw_chapter" value="<?php echo esc_attr( $chapter ); ?>" min="1" /></td>
			</tr>
			<tr>
				<th><label for="thw_verse_start"><?php esc_html_e( 'Verse Start', 'the-hidden-word' ); ?></label></th>
				<td><input type="number" id="thw_verse_start" name="thw_verse_start" value="<?php echo esc_attr( $verse_start ); ?>" min="1" /></td>
			</tr>
			<tr>
				<th><label for="thw_verse_end"><?php esc_html_e( 'Verse End', 'the-hidden-word' ); ?></label></th>
				<td>
					<input type="number" id="thw_verse_end" name="thw_verse_end" value="<?php echo esc_attr( $verse_end ); ?>" min="1" />
					<p class="description"><?php esc_html_e( 'Leave same as start for a single verse.', 'the-hidden-word' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render lesson content meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_lesson_content( $post ) {
		$context  = get_post_meta( $post->ID, '_thw_historical_context', true );
		$narrative = get_post_meta( $post->ID, '_thw_preceding_narrative', true );
		?>
		<p>
			<label for="thw_historical_context"><strong><?php esc_html_e( 'The Context (Historical Background)', 'the-hidden-word' ); ?></strong></label>
		</p>
		<?php
		wp_editor(
			$context,
			'thw_historical_context',
			array(
				'textarea_name' => 'thw_historical_context',
				'textarea_rows' => 6,
				'media_buttons' => false,
			)
		);
		?>
		<p>
			<label for="thw_preceding_narrative"><strong><?php esc_html_e( 'The Narrative (Lead-Up)', 'the-hidden-word' ); ?></strong></label>
		</p>
		<?php
		wp_editor(
			$narrative,
			'thw_preceding_narrative',
			array(
				'textarea_name' => 'thw_preceding_narrative',
				'textarea_rows' => 6,
				'media_buttons' => false,
			)
		);
	}

	/**
	 * Render echo verses meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_echo_verses( $post ) {
		$follow_on = get_post_meta( $post->ID, '_thw_follow_on_verses', true );
		$entries   = $follow_on ? json_decode( $follow_on, true ) : array();
		if ( empty( $entries ) ) {
			$entries = array( array( 'book_id' => '', 'chapter' => '', 'verse' => '', 'note' => '' ) );
		}
		$books = THW_Books::get_all();
		?>
		<div id="thw-echo-repeater">
			<?php foreach ( $entries as $index => $entry ) : ?>
				<div class="thw-echo-row">
					<select name="thw_echo_book_id[]">
						<option value=""><?php esc_html_e( 'Book', 'the-hidden-word' ); ?></option>
						<?php foreach ( $books as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( isset( $entry['book_id'] ) ? (int) $entry['book_id'] : 0, (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="number" name="thw_echo_chapter[]" placeholder="<? esc_attr_e( 'Ch', 'the-hidden-word' ); ?>" value="<?php echo esc_attr( isset( $entry['chapter'] ) ? $entry['chapter'] : '' ); ?>" min="1" />
					<input type="number" name="thw_echo_verse[]" placeholder="<? esc_attr_e( 'Vs', 'the-hidden-word' ); ?>" value="<?php echo esc_attr( isset( $entry['verse'] ) ? $entry['verse'] : '' ); ?>" min="1" />
					<input type="text" name="thw_echo_note[]" class="widefat" placeholder="<? esc_attr_e( 'Connection note', 'the-hidden-word' ); ?>" value="<?php echo esc_attr( isset( $entry['note'] ) ? $entry['note'] : '' ); ?>" />
					<button type="button" class="button thw-remove-echo"><?php esc_html_e( 'Remove', 'the-hidden-word' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<p><button type="button" class="button" id="thw-add-echo"><?php esc_html_e( 'Add Follow-on Verse', 'the-hidden-word' ); ?></button></p>
		<?php
	}

	/**
	 * Render discussion questions meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_discussion( $post ) {
		$questions = get_post_meta( $post->ID, '_thw_discussion_questions', true );
		$entries   = $questions ? json_decode( $questions, true ) : array();
		if ( empty( $entries ) ) {
			$entries = array( '' );
		}
		?>
		<div id="thw-questions-repeater">
			<?php foreach ( $entries as $question ) : ?>
				<div class="thw-question-row">
					<input type="text" name="thw_discussion_question[]" class="widefat" value="<?php echo esc_attr( $question ); ?>" placeholder="<? esc_attr_e( 'Reflection question', 'the-hidden-word' ); ?>" />
					<button type="button" class="button thw-remove-question"><?php esc_html_e( 'Remove', 'the-hidden-word' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<p><button type="button" class="button" id="thw-add-question"><?php esc_html_e( 'Add Question', 'the-hidden-word' ); ?></button></p>
		<?php
	}

	/**
	 * Save lesson meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, $post ) {
		unset( $post );

		if ( ! isset( $_POST['thw_lesson_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['thw_lesson_meta_nonce'] ) ), 'thw_save_lesson_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$int_fields = array(
			'thw_book_id'     => '_thw_book_id',
			'thw_chapter'     => '_thw_chapter',
			'thw_verse_start' => '_thw_verse_start',
			'thw_verse_end'   => '_thw_verse_end',
			'thw_week_number' => '_thw_week_number',
		);

		foreach ( $int_fields as $field => $meta_key ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta_key, absint( $_POST[ $field ] ) );
			}
		}

		if ( isset( $_POST['thw_historical_context'] ) ) {
			update_post_meta( $post_id, '_thw_historical_context', wp_kses_post( wp_unslash( $_POST['thw_historical_context'] ) ) );
		}

		if ( isset( $_POST['thw_preceding_narrative'] ) ) {
			update_post_meta( $post_id, '_thw_preceding_narrative', wp_kses_post( wp_unslash( $_POST['thw_preceding_narrative'] ) ) );
		}

		$echo_entries = array();
		if ( isset( $_POST['thw_echo_book_id'] ) && is_array( $_POST['thw_echo_book_id'] ) ) {
			$book_ids = array_map( 'absint', wp_unslash( $_POST['thw_echo_book_id'] ) );
			$chapters = isset( $_POST['thw_echo_chapter'] ) ? array_map( 'absint', wp_unslash( $_POST['thw_echo_chapter'] ) ) : array();
			$verses   = isset( $_POST['thw_echo_verse'] ) ? array_map( 'absint', wp_unslash( $_POST['thw_echo_verse'] ) ) : array();
			$notes    = isset( $_POST['thw_echo_note'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['thw_echo_note'] ) ) : array();

			foreach ( $book_ids as $i => $book ) {
				if ( ! $book ) {
					continue;
				}
				$echo_entries[] = array(
					'book_id' => $book,
					'chapter' => isset( $chapters[ $i ] ) ? $chapters[ $i ] : 0,
					'verse'   => isset( $verses[ $i ] ) ? $verses[ $i ] : 0,
					'note'    => isset( $notes[ $i ] ) ? $notes[ $i ] : '',
				);
			}
		}
		update_post_meta( $post_id, '_thw_follow_on_verses', wp_json_encode( $echo_entries ) );

		$questions = array();
		if ( isset( $_POST['thw_discussion_question'] ) && is_array( $_POST['thw_discussion_question'] ) ) {
			foreach ( wp_unslash( $_POST['thw_discussion_question'] ) as $q ) {
				$q = sanitize_text_field( $q );
				if ( $q ) {
					$questions[] = $q;
				}
			}
		}
		update_post_meta( $post_id, '_thw_discussion_questions', wp_json_encode( $questions ) );
	}
}
