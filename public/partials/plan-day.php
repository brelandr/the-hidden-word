<?php
/**
 * Single reading plan / day partial.
 *
 * @package Hidden_Word_Bible_Lessons
 *
 * @var array      $plan
 * @var array|null $progress
 * @var array|null $today
 * @var bool       $logged_in
 * @var bool       $preview Optional: showing day 1 before the plan is started.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plan_id     = (int) $plan['id'];
$preview     = ! empty( $preview );
$explain_url = rest_url( 'hwbl/v1/bible-explain' );
$tradition   = '';
if ( function_exists( 'thw_premium_get_user_tradition_preset' ) ) {
	$tradition = (string) thw_premium_get_user_tradition_preset( get_current_user_id() );
}
?>
<div
	class="hwbl-plan"
	id="hwbl-plan-<?php echo esc_attr( (string) $plan_id ); ?>"
	data-plan-id="<?php echo esc_attr( (string) $plan_id ); ?>"
	data-rest-url="<?php echo esc_url( rest_url( 'hwbl/v1/plans/' . $plan_id ) ); ?>"
	data-explain-url="<?php echo esc_url( $explain_url ); ?>"
	data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
	data-logged-in="<?php echo $logged_in ? '1' : '0'; ?>"
	data-tradition="<?php echo esc_attr( $tradition ); ?>"
>
	<header class="hwbl-plan__header">
		<h2 class="hwbl-plan__title"><?php echo esc_html( $plan['title'] ); ?></h2>
		<p class="hwbl-plan__meta">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: topic, 2: length */
					__( '%1$s · %2$d days', 'hidden-word-bible-lessons' ),
					ucwords( str_replace( '-', ' ', (string) $plan['topic'] ) ),
					(int) $plan['length']
				)
			);
			?>
		</p>
		<?php if ( ! empty( $plan['excerpt'] ) ) : ?>
			<p class="hwbl-plan__excerpt"><?php echo esc_html( $plan['excerpt'] ); ?></p>
		<?php endif; ?>
	</header>

	<div class="hwbl-plan__actions">
		<?php if ( ! $logged_in ) : ?>
			<p class="hwbl-plan__login-hint"><?php esc_html_e( 'Sign in to start this plan and track progress.', 'hidden-word-bible-lessons' ); ?></p>
		<?php elseif ( empty( $progress['current_day'] ) ) : ?>
			<button type="button" class="hwbl-btn hwbl-plan-start"><?php esc_html_e( 'Start plan', 'hidden-word-bible-lessons' ); ?></button>
		<?php else : ?>
			<p class="hwbl-plan__progress" role="status">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: current day, 2: total days, 3: completed count */
						__( 'Day %1$d of %2$d · %3$d completed', 'hidden-word-bible-lessons' ),
						(int) $progress['current_day'],
						(int) $plan['length'],
						count( (array) $progress['completed_days'] )
					)
				);
				?>
			</p>
		<?php endif; ?>
		<span class="hwbl-plan__status" role="status" aria-live="polite"></span>
	</div>

	<?php if ( ! empty( $today ) ) : ?>
		<section
			class="hwbl-plan-day"
			data-day="<?php echo esc_attr( (string) $today['day'] ); ?>"
			data-book-id="<?php echo esc_attr( (string) (int) ( $today['book_id'] ?? 0 ) ); ?>"
			data-chapter="<?php echo esc_attr( (string) (int) ( $today['chapter'] ?? 0 ) ); ?>"
			data-verse="<?php echo esc_attr( (string) (int) ( $today['verse'] ?? 0 ) ); ?>"
			data-translation="<?php echo esc_attr( (string) ( $today['translation'] ?? '' ) ); ?>"
			data-verse-ref="<?php echo esc_attr( (string) ( $today['verse_ref'] ?? '' ) ); ?>"
		>
			<?php if ( $preview ) : ?>
				<p class="hwbl-plan-day__preview-note"><?php esc_html_e( 'Preview of day 1. Start the plan to track progress through each day.', 'hidden-word-bible-lessons' ); ?></p>
			<?php endif; ?>

			<h3 class="hwbl-plan-day__title">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: day number, 2: day title */
						__( 'Day %1$d: %2$s', 'hidden-word-bible-lessons' ),
						(int) $today['day'],
						$today['title'] ? (string) $today['title'] : __( 'Reading', 'hidden-word-bible-lessons' )
					)
				);
				?>
			</h3>

			<?php if ( ! empty( $today['verse_ref'] ) || ! empty( $today['verse_text'] ) ) : ?>
				<figure class="hwbl-plan-day__verse">
					<?php if ( ! empty( $today['verse_ref'] ) ) : ?>
						<figcaption class="hwbl-plan-day__ref"><strong><?php echo esc_html( (string) $today['verse_ref'] ); ?></strong>
						<?php if ( ! empty( $today['translation'] ) ) : ?>
							<span class="hwbl-plan-day__translation"><?php echo esc_html( strtoupper( (string) $today['translation'] ) ); ?></span>
						<?php endif; ?>
						</figcaption>
					<?php endif; ?>
					<?php if ( ! empty( $today['verse_text'] ) ) : ?>
						<blockquote class="hwbl-plan-day__verse-text"><?php echo esc_html( (string) $today['verse_text'] ); ?></blockquote>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<?php if ( ! empty( $today['body'] ) ) : ?>
				<div class="hwbl-plan-day__body"><?php echo wp_kses_post( $today['body'] ); ?></div>
			<?php endif; ?>

			<div class="hwbl-plan-day__study">
				<h4 class="hwbl-plan-day__section-title"><?php esc_html_e( 'Today’s Bible study', 'hidden-word-bible-lessons' ); ?></h4>
				<div class="hwbl-plan-day__study-status" role="status" aria-live="polite"><?php esc_html_e( 'Loading study…', 'hidden-word-bible-lessons' ); ?></div>
				<div class="hwbl-plan-day__study-body" hidden></div>
			</div>

			<?php if ( $logged_in && ! $preview ) : ?>
				<div class="hwbl-plan-day__journal">
					<h4 class="hwbl-plan-day__section-title"><?php esc_html_e( 'Journal', 'hidden-word-bible-lessons' ); ?></h4>
					<p class="hwbl-plan-day__section-hint"><?php esc_html_e( 'Write honestly about what you are facing. You can ask for biblical guidance on your entry.', 'hidden-word-bible-lessons' ); ?></p>
					<textarea class="hwbl-plan-day__journal-input" rows="5" placeholder="<?php esc_attr_e( 'What is on your heart today?', 'hidden-word-bible-lessons' ); ?>"></textarea>
					<div class="hwbl-plan-day__journal-actions">
						<button type="button" class="hwbl-btn hwbl-plan-journal-save"><?php esc_html_e( 'Save entry', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-journal-ask"><?php esc_html_e( 'Save & ask for biblical guidance', 'hidden-word-bible-lessons' ); ?></button>
					</div>
					<div class="hwbl-plan-day__journal-status" role="status" aria-live="polite"></div>
					<div class="hwbl-plan-day__journal-list"></div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $today['book_id'] ) && ! empty( $today['verse'] ) ) : ?>
				<div class="hwbl-plan-day__explain">
					<h4 class="hwbl-plan-day__section-title"><?php esc_html_e( 'Verse explanation', 'hidden-word-bible-lessons' ); ?></h4>
					<div class="hwbl-plan-day__explain-status" role="status" aria-live="polite"><?php esc_html_e( 'Loading explanation…', 'hidden-word-bible-lessons' ); ?></div>
					<div class="hwbl-plan-day__explain-body" hidden></div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $today['lesson_id'] ) ) : ?>
				<p>
					<a class="hwbl-btn hwbl-btn-secondary" href="<?php echo esc_url( get_permalink( (int) $today['lesson_id'] ) ); ?>">
						<?php esc_html_e( 'Open linked lesson', 'hidden-word-bible-lessons' ); ?>
					</a>
				</p>
			<?php endif; ?>
			<?php if ( $logged_in && ! empty( $progress['current_day'] ) ) : ?>
				<button type="button" class="hwbl-btn hwbl-plan-advance"><?php esc_html_e( 'Mark day complete', 'hidden-word-bible-lessons' ); ?></button>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $plan['days'] ) ) : ?>
		<details class="hwbl-plan__outline">
			<summary><?php esc_html_e( 'Full plan outline', 'hidden-word-bible-lessons' ); ?></summary>
			<ol class="hwbl-plan__days">
				<?php foreach ( $plan['days'] as $day ) : ?>
					<li>
						<?php
						$label = ! empty( $day['title'] ) ? (string) $day['title'] : __( 'Day', 'hidden-word-bible-lessons' );
						echo esc_html( sprintf( '%d. %s', (int) $day['day'], $label ) );
						if ( ! empty( $day['verse_ref'] ) ) {
							echo ' — ' . esc_html( (string) $day['verse_ref'] );
						}
						?>
					</li>
				<?php endforeach; ?>
			</ol>
		</details>
	<?php endif; ?>
</div>
