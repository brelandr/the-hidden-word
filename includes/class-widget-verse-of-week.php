<?php
/**
 * Classic widget: Verse of the Week.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Widget_Verse_Of_Week
 */
class HWBL_Widget_Verse_Of_Week extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$parent_args = array(
			'description' => __( 'Displays the current scheduled Bible verse to memorize.', 'hidden-word-bible-lessons' ),
		);
		parent::__construct(
			'hwbl_verse_of_week',
			HWBL_Scheduler::get_schedule_phrase( 'compact' ),
			$parent_args
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Widget args.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		$week = ! empty( $instance['week'] ) ? absint( $instance['week'] ) : 0;
		if ( ! $week ) {
			HWBL_Cache::mark_page_uncacheable( 'hwbl_verse_widget' );
		}
		$shortcode = $week ? '[hwbl_verse_of_week week="' . $week . '"]' : '[hwbl_verse_of_week]';
		$html = do_shortcode( $shortcode );

		if ( ! $html ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Widget settings form.
	 *
	 * @param array $instance Widget instance.
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : HWBL_Scheduler::get_schedule_phrase( 'compact' );
		$week  = isset( $instance['week'] ) ? absint( $instance['week'] ) : 0;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'hidden-word-bible-lessons' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'week' ) ); ?>"><?php esc_html_e( 'Curriculum number (optional):', 'hidden-word-bible-lessons' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'week' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'week' ) ); ?>" type="number" min="0" value="<?php echo esc_attr( $week ); ?>" />
		</p>
		<?php
	}

	/**
	 * Save widget settings.
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		unset( $old_instance );
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ),
			'week'  => absint( $new_instance['week'] ),
		);
	}
}
