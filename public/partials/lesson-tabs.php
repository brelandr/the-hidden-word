<?php
/**
 * Lesson tabs partial.
 *
 * @package Hidden_Word_Bible_Lessons
 *
 * @var array  $lesson
 * @var string $verse_text
 * @var array  $tabs
 * @var array  $args
 * @var HWBL_Translation_Service $trans_svc
 * @var string $translation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This partial is include()'d from HWBL_Lesson_Renderer::render(), so every
// variable below lives in that method's local scope, not the real global
// scope — safe to leave unprefixed for template readability.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$lesson_id    = isset( $lesson['id'] ) ? (int) $lesson['id'] : 0;
$lesson_title = isset( $lesson['title'] ) ? (string) $lesson['title'] : '';
?>
<div class="hwbl-lesson" id="hwbl-lesson-<?php echo esc_attr( $lesson_id ); ?>" data-lesson-id="<?php echo esc_attr( $lesson_id ); ?>">
	<header class="hwbl-lesson-header">
		<h2 class="hwbl-lesson-title"><?php echo esc_html( $lesson_title ); ?></h2>
		<?php if ( ! empty( $lesson['reference'] ) ) : ?>
			<p class="hwbl-lesson-reference"><?php echo esc_html( $lesson['reference'] ); ?></p>
		<?php endif; ?>
		<div class="hwbl-lesson-toolbar">
			<button type="button" class="hwbl-btn hwbl-print-lesson"><?php esc_html_e( 'Print verse', 'hidden-word-bible-lessons' ); ?></button>
			<?php if ( $verse_text ) : ?>
				<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-copy-verse" data-verse="<?php echo esc_attr( $verse_text ); ?>"><?php esc_html_e( 'Copy verse', 'hidden-word-bible-lessons' ); ?></button>
			<?php endif; ?>
			<span class="hwbl-copy-status" role="status" aria-live="polite"></span>
		</div>
		<?php do_action( 'hwbl_lesson_render_before_tabs', $lesson_id ); ?>
	</header>

	<nav class="hwbl-lesson-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Verse study sections', 'hidden-word-bible-lessons' ); ?>">
		<?php $first = true; ?>
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<button
				type="button"
				class="<?php echo esc_attr( 'hwbl-tab-button' . ( $first ? ' is-active' : '' ) ); ?>"
				role="tab"
				aria-selected="<?php echo esc_attr( $first ? 'true' : 'false' ); ?>"
				aria-controls="hwbl-panel-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $lesson_id ); ?>"
				data-tab="<?php echo esc_attr( $slug ); ?>"
			><?php echo esc_html( $label ); ?></button>
			<?php $first = false; ?>
		<?php endforeach; ?>
	</nav>

	<div class="hwbl-lesson-panels">
		<section
			id="hwbl-panel-blueprint-<?php echo esc_attr( $lesson_id ); ?>"
			class="hwbl-tab-panel is-active"
			role="tabpanel"
			data-panel="blueprint"
		>
			<h3><?php esc_html_e( 'The Blueprint — The Verse', 'hidden-word-bible-lessons' ); ?></h3>
			<?php if ( $verse_text ) : ?>
				<blockquote class="hwbl-verse-text" data-verse="<?php echo esc_attr( $verse_text ); ?>">
					<?php echo esc_html( $verse_text ); ?>
				</blockquote>
			<?php else : ?>
				<p class="hwbl-empty"><?php esc_html_e( 'Verse text not available for this reference.', 'hidden-word-bible-lessons' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $args['show_memorization'] ) && $verse_text ) : ?>
				<?php if ( is_user_logged_in() ) : ?>
					<p class="hwbl-memorization-review-banner" data-lesson-id="<?php echo esc_attr( $lesson_id ); ?>" aria-live="polite" hidden></p>
				<?php endif; ?>
				<?php
				$mem_book_id   = isset( $lesson['book_id'] ) ? (int) $lesson['book_id'] : 0;
				$mem_chapter   = isset( $lesson['chapter'] ) ? (int) $lesson['chapter'] : 0;
				$mem_verse     = isset( $lesson['verse_start'] ) ? (int) $lesson['verse_start'] : ( isset( $lesson['verse'] ) ? (int) $lesson['verse'] : 0 );
				$mem_book_name = ( $mem_book_id && class_exists( 'HWBL_Books' ) ) ? HWBL_Books::get_name( $mem_book_id ) : '';
				$mem_reference = ! empty( $lesson['reference'] ) ? (string) $lesson['reference'] : '';
				$srs_reps      = 0;
				$srs_interval  = 0;
				$srs_ease      = 2.5;
				if ( is_user_logged_in() && class_exists( 'HWBL_Memorization_SRS' ) ) {
					$srs_map  = HWBL_Memorization_SRS::get_progress_map( get_current_user_id() );
					$srs_card = isset( $srs_map[ (int) $lesson_id ] ) && is_array( $srs_map[ (int) $lesson_id ] )
						? $srs_map[ (int) $lesson_id ]
						: HWBL_Memorization_SRS::default_card( (int) $lesson_id );
					$srs_reps     = isset( $srs_card['repetitions'] ) ? (int) $srs_card['repetitions'] : 0;
					$srs_interval = isset( $srs_card['interval_days'] ) ? (int) $srs_card['interval_days'] : 0;
					$srs_ease     = isset( $srs_card['ease_factor'] ) ? (float) $srs_card['ease_factor'] : 2.5;
				}
				?>
				<div
					class="hwbl-memorization"
					data-verse="<?php echo esc_attr( $verse_text ); ?>"
					data-lesson-id="<?php echo esc_attr( $lesson_id ); ?>"
					data-book-id="<?php echo esc_attr( (string) $mem_book_id ); ?>"
					data-book-name="<?php echo esc_attr( $mem_book_name ); ?>"
					data-chapter="<?php echo esc_attr( (string) $mem_chapter ); ?>"
					data-verse-num="<?php echo esc_attr( (string) $mem_verse ); ?>"
					data-reference="<?php echo esc_attr( $mem_reference ); ?>"
					data-srs-reps="<?php echo esc_attr( (string) $srs_reps ); ?>"
					data-srs-interval="<?php echo esc_attr( (string) $srs_interval ); ?>"
					data-srs-ease="<?php echo esc_attr( (string) $srs_ease ); ?>"
				>
					<h4><?php esc_html_e( 'Memorization Practice', 'hidden-word-bible-lessons' ); ?></h4>
					<div class="hwbl-memorization-mode" role="tablist" aria-label="<?php esc_attr_e( 'Practice mode', 'hidden-word-bible-lessons' ); ?>">
						<?php if ( is_user_logged_in() ) : ?>
							<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-mode-btn" data-mode="review" role="tab" aria-selected="false"><?php esc_html_e( 'Daily review', 'hidden-word-bible-lessons' ); ?></button>
						<?php endif; ?>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-mode-btn is-active" data-mode="hide" role="tab" aria-selected="true"><?php esc_html_e( 'Hide words', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-mode-btn" data-mode="flip" role="tab" aria-selected="false"><?php esc_html_e( 'Flip cards', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-mode-btn" data-mode="recall" role="tab" aria-selected="false"><?php esc_html_e( 'Type from memory', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-mode-btn" data-mode="reference" role="tab" aria-selected="false"><?php esc_html_e( 'Know the reference', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-mode-btn" data-mode="first-letter" role="tab" aria-selected="false"><?php esc_html_e( 'First-letter hints', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-mode-btn" data-mode="scramble" role="tab" aria-selected="false"><?php esc_html_e( 'Word scramble', 'hidden-word-bible-lessons' ); ?></button>
					</div>
					<p class="hwbl-memorization-streak" aria-live="polite" hidden></p>
					<p class="hwbl-memorization-hint"><?php esc_html_e( 'Click words to hide them and test your memory.', 'hidden-word-bible-lessons' ); ?></p>
					<div class="hwbl-memorization-text"></div>
					<div class="hwbl-memorization-recall" hidden>
						<label class="hwbl-memorization-recall-label">
							<span><?php esc_html_e( 'Type the verse from memory', 'hidden-word-bible-lessons' ); ?></span>
							<textarea class="hwbl-memorization-recall-input" rows="4"></textarea>
						</label>
						<button type="button" class="hwbl-btn hwbl-memorization-recall-check"><?php esc_html_e( 'Check answer', 'hidden-word-bible-lessons' ); ?></button>
						<p class="hwbl-memorization-recall-result" role="status" aria-live="polite"></p>
					</div>
					<div class="hwbl-memorization-reference" hidden>
						<p class="hwbl-memorization-reference-prompt"><?php esc_html_e( 'Without looking, enter the book, chapter, and verse for this passage.', 'hidden-word-bible-lessons' ); ?></p>
						<div class="hwbl-memorization-reference-fields">
							<label class="hwbl-memorization-reference-field">
								<span><?php esc_html_e( 'Book', 'hidden-word-bible-lessons' ); ?></span>
								<input type="text" class="hwbl-memorization-reference-book" autocomplete="off" placeholder="<?php esc_attr_e( 'e.g. Romans', 'hidden-word-bible-lessons' ); ?>" />
							</label>
							<label class="hwbl-memorization-reference-field">
								<span><?php esc_html_e( 'Chapter', 'hidden-word-bible-lessons' ); ?></span>
								<input type="number" class="hwbl-memorization-reference-chapter" min="1" step="1" inputmode="numeric" placeholder="1" />
							</label>
							<label class="hwbl-memorization-reference-field">
								<span><?php esc_html_e( 'Verse', 'hidden-word-bible-lessons' ); ?></span>
								<input type="number" class="hwbl-memorization-reference-verse" min="1" step="1" inputmode="numeric" placeholder="1" />
							</label>
						</div>
						<label class="hwbl-memorization-reference-field hwbl-memorization-reference-field--full">
							<span><?php esc_html_e( 'Or type the full reference', 'hidden-word-bible-lessons' ); ?></span>
							<input type="text" class="hwbl-memorization-reference-full" autocomplete="off" placeholder="<?php esc_attr_e( 'e.g. Romans 1:1 or Romans chapter 1, verse 1', 'hidden-word-bible-lessons' ); ?>" />
						</label>
						<button type="button" class="hwbl-btn hwbl-memorization-reference-check"><?php esc_html_e( 'Check reference', 'hidden-word-bible-lessons' ); ?></button>
						<p class="hwbl-memorization-reference-result" role="status" aria-live="polite"></p>
					</div>
					<div class="hwbl-memorization-scramble" hidden>
						<p class="hwbl-memorization-scramble-pool" aria-label="<?php esc_attr_e( 'Shuffled words — click in verse order', 'hidden-word-bible-lessons' ); ?>"></p>
						<p class="hwbl-memorization-scramble-build" aria-live="polite"></p>
						<button type="button" class="hwbl-btn hwbl-memorization-scramble-check"><?php esc_html_e( 'Check order', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-memorization-scramble-reset"><?php esc_html_e( 'Reshuffle', 'hidden-word-bible-lessons' ); ?></button>
						<p class="hwbl-memorization-scramble-result" role="status" aria-live="polite"></p>
					</div>
					<div class="hwbl-memorization-flip-controls" hidden>
						<button type="button" class="hwbl-btn hwbl-flip-new"><?php esc_html_e( 'New blanks', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-flip-all"><?php esc_html_e( 'Flip all', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-flip-done"><?php esc_html_e( 'Done — rate recall', 'hidden-word-bible-lessons' ); ?></button>
					</div>
					<div class="hwbl-memorization-controls">
						<button type="button" class="hwbl-btn hwbl-hide-random"><?php esc_html_e( 'Hide Random Words', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-reveal-all"><?php esc_html_e( 'Reveal All', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-reset-memorization"><?php esc_html_e( 'Reset', 'hidden-word-bible-lessons' ); ?></button>
					</div>
					<div class="hwbl-memorization-quality" hidden role="group" aria-label="<?php esc_attr_e( 'How well did you recall this verse?', 'hidden-word-bible-lessons' ); ?>">
						<p class="hwbl-memorization-quality-label"><?php esc_html_e( 'Rate your recall:', 'hidden-word-bible-lessons' ); ?></p>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-quality-btn" data-quality="1"><?php esc_html_e( 'Again', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-quality-btn" data-quality="3"><?php esc_html_e( 'Good', 'hidden-word-bible-lessons' ); ?></button>
						<button type="button" class="hwbl-btn hwbl-btn-secondary hwbl-quality-btn" data-quality="5"><?php esc_html_e( 'Easy', 'hidden-word-bible-lessons' ); ?></button>
					</div>
					<p class="hwbl-memorization-review-feedback" role="status" aria-live="polite" hidden></p>
					<?php
					$memorization_footer = '';
					if ( class_exists( 'HWBL_Memorization_Audio' ) && is_array( $lesson ) && ! empty( $lesson['book_id'] ) ) {
						$memorization_footer = HWBL_Memorization_Audio::render_audio_button( $lesson_id, $lesson, $verse_text );
					}
					echo wp_kses_post( apply_filters( 'hwbl_memorization_widget_html', $memorization_footer, $lesson_id ) );
					?>
				</div>
			<?php endif; ?>

			<?php echo wp_kses_post( $trans_svc->render_copyright( $translation ) ); ?>
		</section>

		<section
			id="hwbl-panel-context-<?php echo esc_attr( $lesson_id ); ?>"
			class="hwbl-tab-panel"
			role="tabpanel"
			data-panel="context"
			hidden
		>
			<h3><?php esc_html_e( 'The Context — The History', 'hidden-word-bible-lessons' ); ?></h3>
			<?php if ( ! empty( $lesson['historical_context'] ) ) : ?>
				<div class="hwbl-rich-content"><?php echo wp_kses_post( $lesson['historical_context'] ); ?></div>
			<?php else : ?>
				<p class="hwbl-empty"><?php esc_html_e( 'Historical context has not been added yet.', 'hidden-word-bible-lessons' ); ?></p>
			<?php endif; ?>
		</section>

		<section
			id="hwbl-panel-narrative-<?php echo esc_attr( $lesson_id ); ?>"
			class="hwbl-tab-panel"
			role="tabpanel"
			data-panel="narrative"
			hidden
		>
			<h3><?php esc_html_e( 'The Narrative — The Lead-Up', 'hidden-word-bible-lessons' ); ?></h3>
			<?php if ( ! empty( $lesson['preceding_narrative'] ) ) : ?>
				<div class="hwbl-rich-content"><?php echo wp_kses_post( $lesson['preceding_narrative'] ); ?></div>
			<?php else : ?>
				<p class="hwbl-empty"><?php esc_html_e( 'Narrative background has not been added yet.', 'hidden-word-bible-lessons' ); ?></p>
			<?php endif; ?>
		</section>

		<section
			id="hwbl-panel-echo-<?php echo esc_attr( $lesson_id ); ?>"
			class="hwbl-tab-panel"
			role="tabpanel"
			data-panel="echo"
			hidden
		>
			<h3><?php esc_html_e( 'The Echo — Follow-on Verses', 'hidden-word-bible-lessons' ); ?></h3>
			<?php if ( ! empty( $lesson['follow_on_verses'] ) ) : ?>
				<ul class="hwbl-echo-list">
					<?php foreach ( $lesson['follow_on_verses'] as $echo ) : ?>
						<?php
						$echo_ref  = HWBL_Books::format_reference(
							isset( $echo['book_id'] ) ? (int) $echo['book_id'] : 0,
							isset( $echo['chapter'] ) ? (int) $echo['chapter'] : 0,
							isset( $echo['verse'] ) ? (int) $echo['verse'] : 0
						);
						$echo_result = $trans_svc->get_echo_verse_text(
							isset( $echo['book_id'] ) ? (int) $echo['book_id'] : 0,
							isset( $echo['chapter'] ) ? (int) $echo['chapter'] : 0,
							isset( $echo['verse'] ) ? (int) $echo['verse'] : 0,
							$translation
						);
						$echo_text         = $echo_result['text'];
						$echo_translation  = $echo_result['translation'];
						?>
						<li class="hwbl-echo-item">
							<strong><?php echo esc_html( $echo_ref ); ?></strong>
							<?php if ( $echo_text ) : ?>
								<blockquote><?php echo esc_html( $echo_text ); ?></blockquote>
								<?php if ( $echo_translation && $echo_translation !== $translation ) : ?>
									<p class="hwbl-echo-translation">
										<?php
										printf(
											/* translators: %s: translation name */
											esc_html__( 'Shown in %s.', 'hidden-word-bible-lessons' ),
											esc_html( $trans_svc->get_translation_label( $echo_translation ) )
										);
										?>
									</p>
								<?php endif; ?>
							<?php endif; ?>
							<?php if ( ! empty( $echo['note'] ) ) : ?>
								<p class="hwbl-echo-note"><?php echo esc_html( $echo['note'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="hwbl-empty"><?php esc_html_e( 'No follow-on verses have been added yet.', 'hidden-word-bible-lessons' ); ?></p>
			<?php endif; ?>
			<?php do_action( 'hwbl_lesson_render_after_echo', $lesson_id ); ?>
		</section>

		<?php if ( ! empty( $args['show_discussion'] ) ) : ?>
		<section
			id="hwbl-panel-discussion-<?php echo esc_attr( $lesson_id ); ?>"
			class="hwbl-tab-panel"
			role="tabpanel"
			data-panel="discussion"
			hidden
		>
			<h3><?php esc_html_e( 'Discussion', 'hidden-word-bible-lessons' ); ?></h3>
			<?php if ( ! empty( $lesson['discussion_questions'] ) ) : ?>
				<ol class="hwbl-discussion-questions">
					<?php foreach ( $lesson['discussion_questions'] as $question ) : ?>
						<li><?php echo esc_html( $question ); ?></li>
					<?php endforeach; ?>
				</ol>
			<?php else : ?>
				<p class="hwbl-empty"><?php esc_html_e( 'Discussion questions have not been added yet.', 'hidden-word-bible-lessons' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $args['show_comments'] ) && comments_open( $lesson_id ) ) : ?>
				<div class="hwbl-comments">
					<?php comments_template(); ?>
				</div>
			<?php endif; ?>
		</section>
		<?php endif; ?>

		<?php do_action( 'hwbl_lesson_render_panels', $lesson_id, $lesson, $args ); ?>
	</div>
</div>
