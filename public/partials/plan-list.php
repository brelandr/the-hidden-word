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
sort( $topic_slugs );

$preferred = array(
	'prayer',
	'identity',
	'forgiveness',
	'purpose',
	'discipleship',
	'anxiety',
	'life-problems',
	'health',
	'marriage',
	'alcoholism',
	'addiction',
	'finances',
	'domestic-violence',
	'doubt',
	'loneliness',
	'depression',
	'gospel',
	'foundations',
	'grief',
	'new-believer',
	'advent',
	'lent',
	'kids',
	'parenting',
	'chronological',
	'other',
);
$ordered = array();
foreach ( $preferred as $pref ) {
	if ( in_array( $pref, $topic_slugs, true ) ) {
		$ordered[] = $pref;
	}
}
foreach ( $topic_slugs as $slug ) {
	if ( ! in_array( $slug, $ordered, true ) ) {
		$ordered[] = $slug;
	}
}

$show_filter = ( '' === (string) $topic ) && count( $ordered ) > 1;
$filter_id   = function_exists( 'wp_unique_id' )
	? wp_unique_id( 'hwbl-plan-topic-filter-' )
	: 'hwbl-plan-topic-filter-' . uniqid();
?>
<div
	class="hwbl-plan-list"
	data-topic="<?php echo esc_attr( $topic ); ?>"
	data-filterable="<?php echo $show_filter ? '1' : '0'; ?>"
>
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
