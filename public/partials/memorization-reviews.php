<?php
/**
 * Spaced-repetition review dashboard partial.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$stats   = HWBL_Memorization_SRS::get_progress_stats( $user_id );
$streak  = HWBL_Memorization_SRS::get_streak( $user_id );
?>
<div class="hwbl-memorize-reviews" data-hwbl-memorize-reviews>
	<header class="hwbl-memorize-reviews__header">
		<h2><?php esc_html_e( 'Memorization Reviews', 'hidden-word-bible-lessons' ); ?></h2>
		<p class="hwbl-memorize-reviews__summary" aria-live="polite">
			<?php
			printf(
				/* translators: 1: due count, 2: total cards, 3: streak days */
				esc_html__( '%1$d due today · %2$d in your deck · %3$d-day streak', 'hidden-word-bible-lessons' ),
				(int) $stats['due'],
				(int) $stats['total'],
				(int) $streak['current']
			);
			?>
		</p>
	</header>
	<div class="hwbl-memorize-reviews__queue" role="region" aria-label="<?php esc_attr_e( 'Review queue', 'hidden-word-bible-lessons' ); ?>">
		<p class="hwbl-memorize-reviews__loading"><?php esc_html_e( 'Loading your review queue…', 'hidden-word-bible-lessons' ); ?></p>
	</div>
</div>
