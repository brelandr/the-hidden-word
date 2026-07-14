<?php
/**
 * Lesson tabs partial.
 *
 * @package The_Hidden_Word
 *
 * @var array  $lesson
 * @var string $verse_text
 * @var array  $tabs
 * @var array  $args
 * @var THW_Translation_Service $trans_svc
 * @var string $translation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lesson_id = $lesson['id'];
?>
<div class="thw-lesson" id="thw-lesson-<?php echo esc_attr( $lesson_id ); ?>" data-lesson-id="<?php echo esc_attr( $lesson_id ); ?>">
	<header class="thw-lesson-header">
		<h2 class="thw-lesson-title"><?php echo esc_html( $lesson['title'] ); ?></h2>
		<?php if ( ! empty( $lesson['reference'] ) ) : ?>
			<p class="thw-lesson-reference"><?php echo esc_html( $lesson['reference'] ); ?></p>
		<?php endif; ?>
		<div class="thw-lesson-toolbar">
			<button type="button" class="thw-btn thw-print-lesson"><?php esc_html_e( 'Print lesson', 'the-hidden-word' ); ?></button>
			<?php if ( $verse_text ) : ?>
				<button type="button" class="thw-btn thw-btn-secondary thw-copy-verse" data-verse="<?php echo esc_attr( $verse_text ); ?>"><?php esc_html_e( 'Copy verse', 'the-hidden-word' ); ?></button>
			<?php endif; ?>
			<span class="thw-copy-status" role="status" aria-live="polite"></span>
		</div>
		<?php do_action( 'thw_lesson_render_before_tabs', $lesson_id ); ?>
	</header>

	<nav class="thw-lesson-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Lesson sections', 'the-hidden-word' ); ?>">
		<?php $first = true; ?>
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<button
				type="button"
				class="thw-tab-button<?php echo $first ? ' is-active' : ''; ?>"
				role="tab"
				aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
				aria-controls="thw-panel-<?php echo esc_attr( $slug ); ?>-<?php echo esc_attr( $lesson_id ); ?>"
				data-tab="<?php echo esc_attr( $slug ); ?>"
			><?php echo esc_html( $label ); ?></button>
			<?php $first = false; ?>
		<?php endforeach; ?>
	</nav>

	<div class="thw-lesson-panels">
		<section
			id="thw-panel-blueprint-<?php echo esc_attr( $lesson_id ); ?>"
			class="thw-tab-panel is-active"
			role="tabpanel"
			data-panel="blueprint"
		>
			<h3><?php esc_html_e( 'The Blueprint — The Verse', 'the-hidden-word' ); ?></h3>
			<?php if ( $verse_text ) : ?>
				<blockquote class="thw-verse-text" data-verse="<?php echo esc_attr( $verse_text ); ?>">
					<?php echo esc_html( $verse_text ); ?>
				</blockquote>
			<?php else : ?>
				<p class="thw-empty"><?php esc_html_e( 'Verse text not available for this reference.', 'the-hidden-word' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $args['show_memorization'] ) && $verse_text ) : ?>
				<div class="thw-memorization" data-verse="<?php echo esc_attr( $verse_text ); ?>">
					<h4><?php esc_html_e( 'Memorization Practice', 'the-hidden-word' ); ?></h4>
					<p class="thw-memorization-streak" hidden></p>
					<p class="thw-memorization-hint"><?php esc_html_e( 'Click words to hide them and test your memory.', 'the-hidden-word' ); ?></p>
					<div class="thw-memorization-text"></div>
					<div class="thw-memorization-controls">
						<button type="button" class="thw-btn thw-hide-random"><?php esc_html_e( 'Hide Random Words', 'the-hidden-word' ); ?></button>
						<button type="button" class="thw-btn thw-reveal-all"><?php esc_html_e( 'Reveal All', 'the-hidden-word' ); ?></button>
						<button type="button" class="thw-btn thw-reset-memorization"><?php esc_html_e( 'Reset', 'the-hidden-word' ); ?></button>
					</div>
				</div>
			<?php endif; ?>

			<?php echo $trans_svc->render_copyright( $translation ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>

		<section
			id="thw-panel-context-<?php echo esc_attr( $lesson_id ); ?>"
			class="thw-tab-panel"
			role="tabpanel"
			data-panel="context"
			hidden
		>
			<h3><?php esc_html_e( 'The Context — The History', 'the-hidden-word' ); ?></h3>
			<?php if ( ! empty( $lesson['historical_context'] ) ) : ?>
				<div class="thw-rich-content"><?php echo wp_kses_post( $lesson['historical_context'] ); ?></div>
			<?php else : ?>
				<p class="thw-empty"><?php esc_html_e( 'Historical context has not been added yet.', 'the-hidden-word' ); ?></p>
			<?php endif; ?>
		</section>

		<section
			id="thw-panel-narrative-<?php echo esc_attr( $lesson_id ); ?>"
			class="thw-tab-panel"
			role="tabpanel"
			data-panel="narrative"
			hidden
		>
			<h3><?php esc_html_e( 'The Narrative — The Lead-Up', 'the-hidden-word' ); ?></h3>
			<?php if ( ! empty( $lesson['preceding_narrative'] ) ) : ?>
				<div class="thw-rich-content"><?php echo wp_kses_post( $lesson['preceding_narrative'] ); ?></div>
			<?php else : ?>
				<p class="thw-empty"><?php esc_html_e( 'Narrative background has not been added yet.', 'the-hidden-word' ); ?></p>
			<?php endif; ?>
		</section>

		<section
			id="thw-panel-echo-<?php echo esc_attr( $lesson_id ); ?>"
			class="thw-tab-panel"
			role="tabpanel"
			data-panel="echo"
			hidden
		>
			<h3><?php esc_html_e( 'The Echo — Follow-on Verses', 'the-hidden-word' ); ?></h3>
			<?php if ( ! empty( $lesson['follow_on_verses'] ) ) : ?>
				<ul class="thw-echo-list">
					<?php foreach ( $lesson['follow_on_verses'] as $echo ) : ?>
						<?php
						$echo_ref  = THW_Books::format_reference(
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
						<li class="thw-echo-item">
							<strong><?php echo esc_html( $echo_ref ); ?></strong>
							<?php if ( $echo_text ) : ?>
								<blockquote><?php echo esc_html( $echo_text ); ?></blockquote>
								<?php if ( $echo_translation && $echo_translation !== $translation ) : ?>
									<p class="thw-echo-translation">
										<?php
										printf(
											/* translators: %s: translation name */
											esc_html__( 'Shown in %s.', 'the-hidden-word' ),
											esc_html( $trans_svc->get_translation_label( $echo_translation ) )
										);
										?>
									</p>
								<?php endif; ?>
							<?php endif; ?>
							<?php if ( ! empty( $echo['note'] ) ) : ?>
								<p class="thw-echo-note"><?php echo esc_html( $echo['note'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="thw-empty"><?php esc_html_e( 'No follow-on verses have been added yet.', 'the-hidden-word' ); ?></p>
			<?php endif; ?>
			<?php do_action( 'thw_lesson_render_after_echo', $lesson_id ); ?>
		</section>

		<?php if ( ! empty( $args['show_discussion'] ) ) : ?>
		<section
			id="thw-panel-discussion-<?php echo esc_attr( $lesson_id ); ?>"
			class="thw-tab-panel"
			role="tabpanel"
			data-panel="discussion"
			hidden
		>
			<h3><?php esc_html_e( 'Discussion', 'the-hidden-word' ); ?></h3>
			<?php if ( ! empty( $lesson['discussion_questions'] ) ) : ?>
				<ol class="thw-discussion-questions">
					<?php foreach ( $lesson['discussion_questions'] as $question ) : ?>
						<li><?php echo esc_html( $question ); ?></li>
					<?php endforeach; ?>
				</ol>
			<?php else : ?>
				<p class="thw-empty"><?php esc_html_e( 'Discussion questions have not been added yet.', 'the-hidden-word' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $args['show_comments'] ) && comments_open( $lesson_id ) ) : ?>
				<div class="thw-comments">
					<?php comments_template(); ?>
				</div>
			<?php endif; ?>
		</section>
		<?php endif; ?>

		<?php do_action( 'thw_lesson_render_panels', $lesson_id, $lesson, $args ); ?>
	</div>
</div>
