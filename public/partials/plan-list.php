<?php
/**
 * Reading plan list partial.
 *
 * @package Hidden_Word_Bible_Lessons
 *
 * @var array  $plans
 * @var string $topic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="hwbl-plan-list" data-topic="<?php echo esc_attr( $topic ); ?>">
	<?php if ( empty( $plans ) ) : ?>
		<p class="hwbl-empty"><?php esc_html_e( 'No reading plans found.', 'hidden-word-bible-lessons' ); ?></p>
	<?php else : ?>
		<ul class="hwbl-plan-list__items">
			<?php foreach ( $plans as $plan ) : ?>
				<?php
				$plan_link = ! empty( $plan['link'] ) ? (string) $plan['link'] : get_permalink( (int) $plan['id'] );
				if ( ! $plan_link ) {
					continue;
				}
				?>
				<li class="hwbl-plan-list__item">
					<a class="hwbl-plan-list__link" href="<?php echo esc_url( $plan_link ); ?>">
						<strong class="hwbl-plan-list__title"><?php echo esc_html( $plan['title'] ); ?></strong>
						<span class="hwbl-plan-list__meta">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: topic, 2: day count */
									__( '%1$s · %2$d days', 'hidden-word-bible-lessons' ),
									ucwords( str_replace( '-', ' ', (string) $plan['topic'] ) ),
									(int) $plan['length']
								)
							);
							?>
						</span>
						<?php if ( ! empty( $plan['excerpt'] ) ) : ?>
							<span class="hwbl-plan-list__excerpt"><?php echo esc_html( $plan['excerpt'] ); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
