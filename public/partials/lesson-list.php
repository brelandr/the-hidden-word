<?php
/**
 * Lesson catalog partial.
 *
 * @package Hidden_Word_Bible_Lessons
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

// This partial is include()'d from HWBL_Lesson_List::render(), so every
// variable below lives in that method's local scope, not the real global
// scope — safe to leave unprefixed for template readability.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="hwbl-lesson-list">
	<p class="hwbl-lesson-list-count">
		<?php
		printf(
			/* translators: %d: number of lessons */
			esc_html( _n( '%d lesson', '%d lessons', $total, 'hidden-word-bible-lessons' ) ),
			absint( $total )
		);
		?>
	</p>

	<?php if ( empty( $grouped ) || 0 === $total ) : ?>
		<p class="hwbl-empty"><?php esc_html_e( 'No lessons found.', 'hidden-word-bible-lessons' ); ?></p>
	<?php else : ?>
		<?php foreach ( $grouped as $group_label => $group_rows ) : ?>
			<section class="hwbl-lesson-list-group">
				<?php if ( count( $grouped ) > 1 || __( 'All Lessons', 'hidden-word-bible-lessons' ) !== $group_label ) : ?>
					<h3 class="hwbl-lesson-list-heading"><?php echo esc_html( $group_label ); ?></h3>
				<?php endif; ?>
				<ul class="hwbl-lesson-list-items">
					<?php foreach ( $group_rows as $row ) : ?>
						<li class="hwbl-lesson-list-item">
							<a href="<?php echo esc_url( $row['permalink'] ); ?>">
								<?php if ( 'both' === $show || 'title' === $show ) : ?>
									<span class="hwbl-lesson-list-title">
										<?php
										printf(
											/* translators: 1: lesson number, 2: lesson title */
											esc_html__( 'Lesson %1$d: %2$s', 'hidden-word-bible-lessons' ),
											(int) $row['lesson_number'],
											esc_html( preg_replace( '/^Lesson \d+:\s*/', '', $row['title'] ) )
										);
										?>
									</span>
								<?php endif; ?>
								<?php if ( 'both' === $show || 'reference' === $show ) : ?>
									<span class="hwbl-lesson-list-reference"><?php echo esc_html( $row['reference'] ); ?></span>
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
		echo HWBL_Lesson_List::render_pagination( $page, $total_pages );
	}
	?>
</div>
