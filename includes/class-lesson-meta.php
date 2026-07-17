<?php
/**
 * Lesson meta boxes and save handlers.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Lesson_Meta
 */
class HWBL_Lesson_Meta {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_hwbl_lesson', array( $this, 'save_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'hwbl_verse_reference',
			__( 'Verse Reference', 'hidden-word-bible-lessons' ),
			array( $this, 'render_verse_reference' ),
			'hwbl_lesson',
			'normal',
			'high'
		);

		add_meta_box(
			'hwbl_lesson_content',
			__( 'Lesson Content', 'hidden-word-bible-lessons' ),
			array( $this, 'render_lesson_content' ),
			'hwbl_lesson',
			'normal',
			'default'
		);

		add_meta_box(
			'hwbl_echo_verses',
			__( 'The Echo — Follow-on Verses', 'hidden-word-bible-lessons' ),
			array( $this, 'render_echo_verses' ),
			'hwbl_lesson',
			'normal',
			'default'
		);

		add_meta_box(
			'hwbl_discussion',
			__( 'Discussion Questions', 'hidden-word-bible-lessons' ),
			array( $this, 'render_discussion' ),
			'hwbl_lesson',
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
		if ( ! $screen || 'hwbl_lesson' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'hwbl-lesson-meta',
			HWBL_PLUGIN_URL . 'admin/js/lesson-meta.js',
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
	 * Render verse reference meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_verse_reference( $post ) {
		wp_nonce_field( 'hwbl_save_lesson_meta', 'hwbl_lesson_meta_nonce' );

		$books       = HWBL_Books::get_all();
		$book_id     = (int) get_post_meta( $post->ID, '_hwbl_book_id', true );
		$chapter     = (int) get_post_meta( $post->ID, '_hwbl_chapter', true );
		$verse_start = (int) get_post_meta( $post->ID, '_hwbl_verse_start', true );
		$verse_end   = (int) get_post_meta( $post->ID, '_hwbl_verse_end', true );
		$lesson_number = (int) get_post_meta( $post->ID, '_hwbl_lesson_number', true );
		if ( ! $lesson_number ) {
			$lesson_number = (int) get_post_meta( $post->ID, '_hwbl_week_number', true );
		}
		$max_lesson = HWBL_Curriculum::get_lesson_count() ?: 500;
		?>
		<table class="form-table hwbl-meta-table">
			<tr>
				<th><label for="hwbl_lesson_number"><?php esc_html_e( 'Lesson Number', 'hidden-word-bible-lessons' ); ?></label></th>
				<td>
					<input type="number" id="hwbl_lesson_number" name="hwbl_lesson_number" value="<?php echo esc_attr( $lesson_number ); ?>" min="1" max="<?php echo esc_attr( $max_lesson ); ?>" />
					<p class="description"><?php esc_html_e( 'Slot in the bundled curriculum (1–500).', 'hidden-word-bible-lessons' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="hwbl_book_id"><?php esc_html_e( 'Book', 'hidden-word-bible-lessons' ); ?></label></th>
				<td>
					<select id="hwbl_book_id" name="hwbl_book_id">
						<option value=""><?php esc_html_e( 'Select book…', 'hidden-word-bible-lessons' ); ?></option>
						<?php foreach ( $books as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $book_id, (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="hwbl_chapter"><?php esc_html_e( 'Chapter', 'hidden-word-bible-lessons' ); ?></label></th>
				<td><input type="number" id="hwbl_chapter" name="hwbl_chapter" value="<?php echo esc_attr( $chapter ); ?>" min="1" /></td>
			</tr>
			<tr>
				<th><label for="hwbl_verse_start"><?php esc_html_e( 'Verse Start', 'hidden-word-bible-lessons' ); ?></label></th>
				<td><input type="number" id="hwbl_verse_start" name="hwbl_verse_start" value="<?php echo esc_attr( $verse_start ); ?>" min="1" /></td>
			</tr>
			<tr>
				<th><label for="hwbl_verse_end"><?php esc_html_e( 'Verse End', 'hidden-word-bible-lessons' ); ?></label></th>
				<td>
					<input type="number" id="hwbl_verse_end" name="hwbl_verse_end" value="<?php echo esc_attr( $verse_end ); ?>" min="1" />
					<p class="description"><?php esc_html_e( 'Leave same as start for a single verse.', 'hidden-word-bible-lessons' ); ?></p>
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
		$context  = get_post_meta( $post->ID, '_hwbl_historical_context', true );
		$narrative = get_post_meta( $post->ID, '_hwbl_preceding_narrative', true );
		?>
		<p>
			<label for="hwbl_historical_context"><strong><?php esc_html_e( 'The Context (Historical Background)', 'hidden-word-bible-lessons' ); ?></strong></label>
		</p>
		<?php
		wp_editor(
			$context,
			'hwbl_historical_context',
			array(
				'textarea_name' => 'hwbl_historical_context',
				'textarea_rows' => 6,
				'media_buttons' => false,
			)
		);
		?>
		<p>
			<label for="hwbl_preceding_narrative"><strong><?php esc_html_e( 'The Narrative (Lead-Up)', 'hidden-word-bible-lessons' ); ?></strong></label>
		</p>
		<?php
		wp_editor(
			$narrative,
			'hwbl_preceding_narrative',
			array(
				'textarea_name' => 'hwbl_preceding_narrative',
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
		$follow_on = get_post_meta( $post->ID, '_hwbl_follow_on_verses', true );
		$entries   = $follow_on ? json_decode( $follow_on, true ) : array();
		if ( empty( $entries ) ) {
			$entries = array( array( 'book_id' => '', 'chapter' => '', 'verse' => '', 'note' => '' ) );
		}
		$books = HWBL_Books::get_all();
		?>
		<div id="hwbl-echo-repeater">
			<?php foreach ( $entries as $index => $entry ) : ?>
				<div class="hwbl-echo-row">
					<select name="hwbl_echo_book_id[]">
						<option value=""><?php esc_html_e( 'Book', 'hidden-word-bible-lessons' ); ?></option>
						<?php foreach ( $books as $id => $name ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( isset( $entry['book_id'] ) ? (int) $entry['book_id'] : 0, (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="number" name="hwbl_echo_chapter[]" placeholder="<?php esc_attr_e( 'Ch', 'hidden-word-bible-lessons' ); ?>" value="<?php echo esc_attr( isset( $entry['chapter'] ) ? $entry['chapter'] : '' ); ?>" min="1" />
					<input type="number" name="hwbl_echo_verse[]" placeholder="<?php esc_attr_e( 'Vs', 'hidden-word-bible-lessons' ); ?>" value="<?php echo esc_attr( isset( $entry['verse'] ) ? $entry['verse'] : '' ); ?>" min="1" />
					<input type="text" name="hwbl_echo_note[]" class="widefat" placeholder="<?php esc_attr_e( 'Connection note', 'hidden-word-bible-lessons' ); ?>" value="<?php echo esc_attr( isset( $entry['note'] ) ? $entry['note'] : '' ); ?>" />
					<button type="button" class="button hwbl-remove-echo"><?php esc_html_e( 'Remove', 'hidden-word-bible-lessons' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<p><button type="button" class="button" id="hwbl-add-echo"><?php esc_html_e( 'Add Follow-on Verse', 'hidden-word-bible-lessons' ); ?></button></p>
		<?php
	}

	/**
	 * Render discussion questions meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_discussion( $post ) {
		$questions = get_post_meta( $post->ID, '_hwbl_discussion_questions', true );
		$entries   = $questions ? json_decode( $questions, true ) : array();
		if ( empty( $entries ) ) {
			$entries = array( '' );
		}
		?>
		<div id="hwbl-questions-repeater">
			<?php foreach ( $entries as $question ) : ?>
				<div class="hwbl-question-row">
					<input type="text" name="hwbl_discussion_question[]" class="widefat" value="<?php echo esc_attr( $question ); ?>" placeholder="<?php esc_attr_e( 'Reflection question', 'hidden-word-bible-lessons' ); ?>" />
					<button type="button" class="button hwbl-remove-question"><?php esc_html_e( 'Remove', 'hidden-word-bible-lessons' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<p><button type="button" class="button" id="hwbl-add-question"><?php esc_html_e( 'Add Question', 'hidden-word-bible-lessons' ); ?></button></p>
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

		if ( ! isset( $_POST['hwbl_lesson_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hwbl_lesson_meta_nonce'] ) ), 'hwbl_save_lesson_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$int_fields = array(
			'hwbl_book_id'       => '_hwbl_book_id',
			'hwbl_chapter'       => '_hwbl_chapter',
			'hwbl_verse_start'   => '_hwbl_verse_start',
			'hwbl_verse_end'     => '_hwbl_verse_end',
			'hwbl_lesson_number' => '_hwbl_lesson_number',
			'hwbl_week_number'   => '_hwbl_week_number',
		);

		foreach ( $int_fields as $field => $meta_key ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta_key, absint( $_POST[ $field ] ) );
			}
		}

		if ( isset( $_POST['hwbl_lesson_number'] ) ) {
			update_post_meta( $post_id, '_hwbl_week_number', absint( $_POST['hwbl_lesson_number'] ) );
		}

		if ( isset( $_POST['hwbl_historical_context'] ) ) {
			update_post_meta( $post_id, '_hwbl_historical_context', wp_kses_post( wp_unslash( $_POST['hwbl_historical_context'] ) ) );
		}

		if ( isset( $_POST['hwbl_preceding_narrative'] ) ) {
			update_post_meta( $post_id, '_hwbl_preceding_narrative', wp_kses_post( wp_unslash( $_POST['hwbl_preceding_narrative'] ) ) );
		}

		$echo_entries = array();
		if ( isset( $_POST['hwbl_echo_book_id'] ) && is_array( $_POST['hwbl_echo_book_id'] ) ) {
			$book_ids = array_map( 'absint', wp_unslash( $_POST['hwbl_echo_book_id'] ) );
			$chapters = isset( $_POST['hwbl_echo_chapter'] ) ? array_map( 'absint', wp_unslash( $_POST['hwbl_echo_chapter'] ) ) : array();
			$verses   = isset( $_POST['hwbl_echo_verse'] ) ? array_map( 'absint', wp_unslash( $_POST['hwbl_echo_verse'] ) ) : array();
			$notes    = isset( $_POST['hwbl_echo_note'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['hwbl_echo_note'] ) ) : array();

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
		update_post_meta( $post_id, '_hwbl_follow_on_verses', wp_json_encode( $echo_entries ) );

		$questions = array();
		if ( isset( $_POST['hwbl_discussion_question'] ) && is_array( $_POST['hwbl_discussion_question'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via array_map( 'sanitize_text_field', ... ) below; the sniff can't see through array_map.
			$raw_questions = array_map( 'sanitize_text_field', wp_unslash( $_POST['hwbl_discussion_question'] ) );
			foreach ( $raw_questions as $q ) {
				if ( $q ) {
					$questions[] = $q;
				}
			}
		}
		update_post_meta( $post_id, '_hwbl_discussion_questions', wp_json_encode( $questions ) );
	}
}
