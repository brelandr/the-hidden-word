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

$topic_slugs = array();
foreach ( (array) $plans as $plan_row ) {
	$slug = sanitize_key( (string) ( $plan_row['topic'] ?? '' ) );
	if ( $slug ) {
		$topic_slugs[ $slug ] = true;
	}
}
$topic_slugs = array_keys( $topic_slugs );
// Alphabetical by display label (e.g. "Holy Spirit", "New Believer").
usort(
	$topic_slugs,
	static function ( $a, $b ) {
		$la = ucwords( str_replace( '-', ' ', (string) $a ) );
		$lb = ucwords( str_replace( '-', ' ', (string) $b ) );
		return strcasecmp( $la, $lb );
	}
);
$ordered = $topic_slugs;

$show_filter = ( '' === (string) $topic ) && count( $ordered ) > 1;
$filter_id   = function_exists( 'wp_unique_id' )
	? wp_unique_id( 'hwbl-plan-topic-filter-' )
	: 'hwbl-plan-topic-filter-' . uniqid();

$active_plans = array();
if ( is_user_logged_in() && class_exists( 'HWBL_Plan_Progress' ) ) {
	$active_plans = array_slice( HWBL_Plan_Progress::get_active_for_user( get_current_user_id() ), 0, 3 );
}
?>
<div
	class="hwbl-plan-list"
	data-topic="<?php echo esc_attr( $topic ); ?>"
	data-filterable="<?php echo $show_filter ? '1' : '0'; ?>"
>
	<?php if ( ! empty( $active_plans ) ) : ?>
		<section class="hwbl-plan-list__continue" aria-label="<?php esc_attr_e( 'Continue reading plans', 'hidden-word-bible-lessons' ); ?>">
			<h3 class="hwbl-plan-list__continue-title"><?php esc_html_e( 'Continue where you left off', 'hidden-word-bible-lessons' ); ?></h3>
			<ul class="hwbl-plan-list__continue-items">
				<?php foreach ( $active_plans as $active ) : ?>
					<?php
					$day_num  = (int) ( $active['progress']['current_day'] ?? 0 );
					$plan_url = ! empty( $active['url'] ) ? (string) $active['url'] : get_permalink( (int) $active['plan_id'] );
					$day_url  = $day_num > 0 ? add_query_arg( 'hwbl_plan_day', $day_num, $plan_url ) : $plan_url;
					$day_title = is_array( $active['today'] ?? null ) && ! empty( $active['today']['title'] )
						? (string) $active['today']['title']
						: '';
					?>
					<li class="hwbl-plan-list__continue-item">
						<a class="hwbl-plan-list__continue-link" href="<?php echo esc_url( $day_url ); ?>">
							<strong><?php echo esc_html( (string) ( $active['title'] ?? '' ) ); ?></strong>
							<span>
								<?php
								echo esc_html(
									$day_title
										? sprintf(
											/* translators: 1: day number, 2: day title */
											__( 'Continue day %1$d — %2$s', 'hidden-word-bible-lessons' ),
											$day_num,
											$day_title
										)
										: sprintf(
											/* translators: %d: day number */
											__( 'Continue day %d', 'hidden-word-bible-lessons' ),
											$day_num
										)
								);
								?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
	<?php if ( empty( $plans ) ) : ?>
		<p class="hwbl-empty"><?php esc_html_e( 'No reading plans found.', 'hidden-word-bible-lessons' ); ?></p>
	<?php else : ?>
		<?php if ( $show_filter ) : ?>
			<div class="hwbl-plan-list__filter">
				<label class="hwbl-plan-list__filter-label" for="<?php echo esc_attr( $filter_id ); ?>">
					<?php esc_html_e( 'Filter by topic', 'hidden-word-bible-lessons' ); ?>
				</label>
				<select
					id="<?php echo esc_attr( $filter_id ); ?>"
					class="hwbl-plan-list__filter-select"
					autocomplete="off"
				>
					<option value=""><?php esc_html_e( 'All topics', 'hidden-word-bible-lessons' ); ?></option>
					<?php foreach ( $ordered as $slug ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>">
							<?php echo esc_html( ucwords( str_replace( '-', ' ', $slug ) ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>
		<ul class="hwbl-plan-list__items">
			<?php foreach ( $plans as $plan ) : ?>
				<?php
				$plan_link = ! empty( $plan['link'] ) ? (string) $plan['link'] : get_permalink( (int) $plan['id'] );
				if ( ! $plan_link ) {
					continue;
				}
				$plan_topic = sanitize_key( (string) ( $plan['topic'] ?? '' ) );
				?>
				<li class="hwbl-plan-list__item" data-topic="<?php echo esc_attr( $plan_topic ); ?>">
					<a class="hwbl-plan-list__link" href="<?php echo esc_url( $plan_link ); ?>">
						<strong class="hwbl-plan-list__title"><?php echo esc_html( $plan['title'] ); ?></strong>
						<span class="hwbl-plan-list__meta">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: topic, 2: day count */
									__( '%1$s · %2$d days', 'hidden-word-bible-lessons' ),
									ucwords( str_replace( '-', ' ', $plan_topic ) ),
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
		<p class="hwbl-plan-list__empty-filter" hidden>
			<?php esc_html_e( 'No plans match that topic.', 'hidden-word-bible-lessons' ); ?>
		</p>
	<?php endif; ?>
</div>
