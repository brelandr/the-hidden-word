<?php
/**
 * Crisis language detection and helpline responses.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Crisis_Guard
 */
class HWBL_Crisis_Guard {

	/**
	 * Detect high-risk crisis language in user text.
	 *
	 * @param string $text User input.
	 * @return bool
	 */
	public static function is_crisis( $text ) {
		$text = strtolower( wp_strip_all_tags( (string) $text ) );
		$text = preg_replace( '/\s+/u', ' ', $text );
		if ( '' === trim( (string) $text ) ) {
			return false;
		}

		$phrases = apply_filters(
			'hwbl_crisis_phrases',
			array(
				'suicide',
				'suicidal',
				'kill myself',
				'killing myself',
				'end my life',
				'ending my life',
				'take my own life',
				'take my life',
				'want to die',
				'wanna die',
				'wish i were dead',
				'wish i was dead',
				'self-harm',
				'self harm',
				'hurt myself',
				'hurting myself',
				'cut myself',
				'cutting myself',
				'no reason to live',
				'better off dead',
				'don\'t want to live',
				'do not want to live',
				'dont want to live',
			)
		);

		foreach ( (array) $phrases as $phrase ) {
			$phrase = strtolower( trim( (string) $phrase ) );
			if ( '' === $phrase ) {
				continue;
			}
			if ( false !== strpos( $text, $phrase ) ) {
				return true;
			}
		}

		return (bool) apply_filters( 'hwbl_crisis_detected', false, $text );
	}

	/**
	 * Plain-text helpline response (for API + AI short-circuit).
	 *
	 * @return string
	 */
	public static function helpline_plain() {
		$lines = array(
			__( 'Thank you for sharing something so heavy. Your life matters, and you do not have to carry this alone.', 'hidden-word-bible-lessons' ),
			__( 'This is not a crisis counseling service. If you are in immediate danger, call your local emergency number now.', 'hidden-word-bible-lessons' ),
			__( 'In the US and Canada, call or text 988 (Suicide & Crisis Lifeline) for free, confidential support 24/7.', 'hidden-word-bible-lessons' ),
			__( 'International resources: https://www.iasp.info/suicidalthoughts/', 'hidden-word-bible-lessons' ),
			__( 'Please also reach out to a trusted pastor, friend, or local mental-health professional. God cares for you, and people are ready to help.', 'hidden-word-bible-lessons' ),
		);
		return implode( "\n\n", $lines );
	}

	/**
	 * HTML helpline response for UI.
	 *
	 * @return string
	 */
	public static function helpline_html() {
		ob_start();
		?>
		<div class="hwbl-crisis-banner" role="alert">
			<p><strong><?php esc_html_e( 'You are not alone.', 'hidden-word-bible-lessons' ); ?></strong>
			<?php esc_html_e( 'Thank you for sharing something so heavy. Your life matters.', 'hidden-word-bible-lessons' ); ?></p>
			<p><?php esc_html_e( 'This is not a crisis counseling service. If you are in immediate danger, call your local emergency number now.', 'hidden-word-bible-lessons' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'US &amp; Canada: call or text 988 (Suicide &amp; Crisis Lifeline) — free, confidential, 24/7.', 'hidden-word-bible-lessons' ); ?></li>
				<li>
					<a href="https://www.iasp.info/suicidalthoughts/" rel="noopener noreferrer" target="_blank">
						<?php esc_html_e( 'International resources (IASP)', 'hidden-word-bible-lessons' ); ?>
					</a>
				</li>
			</ul>
			<p><?php esc_html_e( 'Please also reach out to a trusted pastor, friend, or local mental-health professional.', 'hidden-word-bible-lessons' ); ?></p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Hard rules appended to pastoral AI system instructions.
	 *
	 * @return string
	 */
	public static function ai_system_addendum() {
		return "CRISIS SAFETY (hard rule): If the person expresses suicidal ideation, self-harm intent, or an immediate desire to die, do NOT give ordinary pastoral advice. Instead tell them compassionately that this is not a crisis service, urge them to contact emergency services if in immediate danger, give the US/Canada 988 Suicide & Crisis Lifeline (call or text), and point to https://www.iasp.info/suicidalthoughts/ for international help. Encourage reaching a trusted pastor or professional. Keep Jesus' care central without minimizing the crisis.";
	}
}
