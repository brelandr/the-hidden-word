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
$current_day = ( $progress && ! empty( $progress['current_day'] ) ) ? (int) $progress['current_day'] : 0;
$viewing_day = ! empty( $today['day'] ) ? (int) $today['day'] : 0;
$plan_length = (int) ( $plan['length'] ?? count( (array) ( $plan['days'] ?? array() ) ) );
$completed   = ( $progress && ! empty( $progress['completed_days'] ) && is_array( $progress['completed_days'] ) )
	? array_map( 'intval', $progress['completed_days'] )
	: array();
$explain_url = rest_url( 'hwbl/v1/bible-explain' );
$tradition   = '';
$study_styles = HWBL_Plan_Study_Styles::all();
$study_style  = class_exists( 'HWBL_User_Preferences' ) ? HWBL_User_Preferences::get_study_style() : 'devotional';
$plan_shape   = HWBL_CPT_Plan::resolve_shape( $plan['shape'] ?? '' );
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
	data-current-day="<?php echo esc_attr( (string) $current_day ); ?>"
	data-plan-length="<?php echo esc_attr( (string) $plan_length ); ?>"
	data-viewing-day="<?php echo esc_attr( (string) $viewing_day ); ?>"
	data-preview="<?php echo $preview ? '1' : '0'; ?>"
	data-site-name="<?php echo esc_attr( (string) get_bloginfo( 'name' ) ); ?>"
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

			<div class="hwbl-plan-day__nav" hidden>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-day-prev"><?php esc_html_e( 'Previous day', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-day-today"><?php esc_html_e( 'Back to today', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-day-next"><?php esc_html_e( 'Next day', 'hidden-word-bible-lessons' ); ?></button>
			</div>
			<p class="hwbl-plan-day__review-note" hidden></p>

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
			<p class="hwbl-plan-day__framing" <?php echo ( 'daily' === $plan_shape || empty( $today['framing'] ) ) ? 'hidden' : ''; ?>>
				<?php echo ! empty( $today['framing'] ) ? esc_html( (string) $today['framing'] ) : ''; ?>
			</p>

			<div class="hwbl-plan-day__verse-wrap">
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
			</div>
			<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-compare" aria-expanded="false" <?php echo empty( $today['verse_ref'] ) ? 'hidden' : ''; ?>><?php esc_html_e( 'Compare translations', 'hidden-word-bible-lessons' ); ?></button>
			<div class="hwbl-plan-day__compare hwbl-translation-compare" hidden></div>

			<div class="hwbl-plan-day__share-bar" role="group" aria-label="<?php esc_attr_e( 'Copy or share this day', 'hidden-word-bible-lessons' ); ?>">
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-copy-verse"><?php esc_html_e( 'Copy verse', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-copy-study"><?php esc_html_e( 'Copy study', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-copy-ask"><?php esc_html_e( 'Copy Ask reply', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-copy-day"><?php esc_html_e( 'Copy day', 'hidden-word-bible-lessons' ); ?></button>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-share-day"><?php esc_html_e( 'Share day', 'hidden-word-bible-lessons' ); ?></button>
				<span class="hwbl-plan-add-journal-wrap">
					<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-add-journal" aria-expanded="false" aria-haspopup="true"><?php esc_html_e( 'Add to journal', 'hidden-word-bible-lessons' ); ?></button>
				</span>
				<span class="hwbl-plan-day__share-status" role="status" aria-live="polite"></span>
			</div>

			<div class="hwbl-plan-day__body"><?php echo ! empty( $today['body'] ) ? wp_kses_post( $today['body'] ) : ''; ?></div>

			<div class="hwbl-plan-day__study">
				<label class="hwbl-plan-day__style-label">
					<span><?php esc_html_e( 'Study style', 'hidden-word-bible-lessons' ); ?></span>
					<select class="hwbl-plan-study-style">
						<?php foreach ( $study_styles as $style_slug => $style_data ) : ?>
							<option value="<?php echo esc_attr( $style_slug ); ?>" title="<?php echo esc_attr( $style_data['description'] ); ?>" <?php selected( $study_style, $style_slug ); ?>><?php echo esc_html( $style_data['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<h4 class="hwbl-plan-day__section-title"><?php esc_html_e( 'Today’s Bible study', 'hidden-word-bible-lessons' ); ?></h4>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-listen" hidden><?php esc_html_e( 'Listen', 'hidden-word-bible-lessons' ); ?></button>
				<div class="hwbl-plan-day__study-status" role="status" aria-live="polite"><?php esc_html_e( 'Loading study…', 'hidden-word-bible-lessons' ); ?></div>
				<div class="hwbl-plan-day__study-body" hidden></div>
			</div>

			<details class="hwbl-plan-day__word-study" <?php echo empty( $today['strongs_words'] ) ? 'hidden' : ''; ?>>
				<summary><?php esc_html_e( 'Word study', 'hidden-word-bible-lessons' ); ?></summary>
				<div class="hwbl-plan-day__word-study-body"></div>
			</details>

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

			<div class="hwbl-plan-day__explain" <?php echo ( empty( $today['book_id'] ) || empty( $today['verse'] ) ) ? 'hidden' : ''; ?>>
				<h4 class="hwbl-plan-day__section-title"><?php esc_html_e( 'Verse explanation', 'hidden-word-bible-lessons' ); ?></h4>
				<div class="hwbl-plan-day__explain-status" role="status" aria-live="polite"><?php esc_html_e( 'Loading explanation…', 'hidden-word-bible-lessons' ); ?></div>
				<div class="hwbl-plan-day__explain-body" hidden></div>
				<div class="hwbl-plan-day__explain-actions" hidden>
					<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-copy-explain"><?php esc_html_e( 'Copy explanation', 'hidden-word-bible-lessons' ); ?></button>
					<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-plan-share-explain"><?php esc_html_e( 'Share explanation', 'hidden-word-bible-lessons' ); ?></button>
					<span class="hwbl-journal-export-mount"></span>
				</div>
			</div>

			<p class="hwbl-plan-day__lesson-link" <?php echo empty( $today['lesson_id'] ) ? 'hidden' : ''; ?>>
				<a class="hwbl-btn hwbl-btn-secondary" href="<?php echo ! empty( $today['lesson_id'] ) ? esc_url( get_permalink( (int) $today['lesson_id'] ) ) : '#'; ?>">
					<?php esc_html_e( 'Open linked lesson', 'hidden-word-bible-lessons' ); ?>
				</a>
			</p>

			<?php if ( $logged_in && $current_day > 0 ) : ?>
				<div class="hwbl-plan-day__complete-wrap hwbl-plan-day__complete-wrap--footer">
					<button type="button" class="hwbl-btn hwbl-plan-advance" <?php echo ( $viewing_day !== $current_day || $preview ) ? 'hidden' : ''; ?>><?php esc_html_e( 'Mark day complete', 'hidden-word-bible-lessons' ); ?></button>
					<p class="hwbl-plan-day__complete-hint" <?php echo ( $viewing_day === $current_day || $preview ) ? 'hidden' : ''; ?>>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: current progress day number */
								__( 'Your progress is on day %d. Go back to today to mark that day complete.', 'hidden-word-bible-lessons' ),
								$current_day
							)
						);
						?>
					</p>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $plan['days'] ) ) : ?>
		<details class="hwbl-plan__outline" open>
			<summary><?php esc_html_e( 'Full plan outline — tap a day to review', 'hidden-word-bible-lessons' ); ?></summary>
			<ol class="hwbl-plan__days">
				<?php foreach ( $plan['days'] as $day ) : ?>
					<?php
					$day_num = (int) $day['day'];
					$label   = ! empty( $day['title'] ) ? (string) $day['title'] : __( 'Day', 'hidden-word-bible-lessons' );
					$is_done = in_array( $day_num, $completed, true );
					$is_cur  = ( $day_num === $current_day );
					$classes = 'hwbl-plan__day-btn';
					if ( $is_done ) {
						$classes .= ' is-completed';
					}
					if ( $is_cur ) {
						$classes .= ' is-current';
					}
					if ( $day_num === $viewing_day ) {
						$classes .= ' is-viewing';
					}
					$text = sprintf( '%d. %s', $day_num, $label );
					if ( ! empty( $day['verse_ref'] ) ) {
						$text .= ' — ' . (string) $day['verse_ref'];
					}
					?>
					<li>
						<button
							type="button"
							class="<?php echo esc_attr( $classes ); ?>"
							data-day="<?php echo esc_attr( (string) $day_num ); ?>"
							<?php disabled( $preview ); ?>
						>
							<?php echo esc_html( $text ); ?>
							<?php if ( $is_done ) : ?>
								<span class="hwbl-plan__day-badge"><?php esc_html_e( 'Done', 'hidden-word-bible-lessons' ); ?></span>
							<?php elseif ( $is_cur ) : ?>
								<span class="hwbl-plan__day-badge"><?php esc_html_e( 'Today', 'hidden-word-bible-lessons' ); ?></span>
							<?php endif; ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ol>
		</details>
	<?php endif; ?>
</div>
