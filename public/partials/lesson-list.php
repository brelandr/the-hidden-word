<?php
/**
 * Lesson catalog partial.
 *
 * @package The_Hidden_Word
 *
 * @var array<string, array<int, array<string, mixed>>> $grouped
 * @var string $show
 * @var int    $total
 * @var int    $page
 * @var int    $total_pages
 * @var array  $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="thw-lesson-list">
	<p class="thw-lesson-list-count">
		<?php
		printf(
			/* translators: %d: number of lessons */
			esc_html( _n( '%d lesson', '%d lessons', $total, 'the-hidden-word' ) ),
			$total
		);
		?>
	</p>

	<?php if ( empty( $grouped ) || 0 === $total ) : ?>
		<p class="thw-empty"><?php esc_html_e( 'No lessons found.', 'the-hidden-word' ); ?></p>
	<?php else : ?>
		<?php foreach ( $grouped as $group_label => $group_rows ) : ?>
			<section class="thw-lesson-list-group">
				<?php if ( count( $grouped ) > 1 || __( 'All Lessons', 'the-hidden-word' ) !== $group_label ) : ?>
					<h3 class="thw-lesson-list-heading"><?php echo esc_html( $group_label ); ?></h3>
				<?php endif; ?>
				<ul class="thw-lesson-list-items">
					<?php foreach ( $group_rows as $row ) : ?>
						<li class="thw-lesson-list-item">
							<a href="<?php echo esc_url( $row['permalink'] ); ?>">
								<?php if ( 'both' === $show || 'title' === $show ) : ?>
									<span class="thw-lesson-list-title">
										<?php
										printf(
											/* translators: 1: lesson number, 2: lesson title */
											esc_html__( 'Lesson %1$d: %2$s', 'the-hidden-word' ),
											(int) $row['lesson_number'],
											esc_html( preg_replace( '/^Lesson \d+:\s*/', '', $row['title'] ) )
										);
										?>
									</span>
								<?php endif; ?>
								<?php if ( 'both' === $show || 'reference' === $show ) : ?>
									<span class="thw-lesson-list-reference"><?php echo esc_html( $row['reference'] ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>

	<?php
	if ( isset( $total_pages ) && $total_pages > 1 ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo THW_Lesson_List::render_pagination( $page, $total_pages );
	}
	?>
</div>
