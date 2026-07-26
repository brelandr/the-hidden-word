<?php
/**
 * Shared front-end i18n strings for JavaScript.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Frontend_I18n
 */
class HWBL_Frontend_I18n {

	/**
	 * Memorization widget strings.
	 *
	 * @return array<string, string>
	 */
	public static function memorization_strings() {
		return array(
			'streakDayOne'   => __( 'Day 1 streak — great start!', 'hidden-word-bible-lessons' ),
			/* translators: %d: streak day count */
			'streakDays'     => __( 'Day %d streak — keep going!', 'hidden-word-bible-lessons' ),
			/* translators: %s: word being hidden */
			'hideWord'       => __( 'Hide word: %s', 'hidden-word-bible-lessons' ),
			'revealWord'     => __( 'Reveal hidden word', 'hidden-word-bible-lessons' ),
			'practiceRegion' => __( 'Memorization practice', 'hidden-word-bible-lessons' ),
			'modeHide'       => __( 'Hide words', 'hidden-word-bible-lessons' ),
			'modeRecall'     => __( 'Type from memory', 'hidden-word-bible-lessons' ),
			'modeFirstLetter'=> __( 'First-letter hints', 'hidden-word-bible-lessons' ),
			'recallPrompt'   => __( 'Type the verse from memory, then check your answer.', 'hidden-word-bible-lessons' ),
			'recallCheck'    => __( 'Check answer', 'hidden-word-bible-lessons' ),
			'recallGood'     => __( 'Great recall!', 'hidden-word-bible-lessons' ),
			'recallPartial'  => __( 'Keep practicing — some words differ.', 'hidden-word-bible-lessons' ),
			'modeScramble'   => __( 'Click shuffled words in verse order.', 'hidden-word-bible-lessons' ),
			'scrambleGood'   => __( 'Correct order — well done!', 'hidden-word-bible-lessons' ),
			'scramblePartial'=> __( 'Not quite — try reshuffling and practice again.', 'hidden-word-bible-lessons' ),
			/* translators: %s: word to add in scramble mode */
			'scrambleWord'   => __( 'Add word: %s', 'hidden-word-bible-lessons' ),
			'reviewPrompt'   => __( 'Daily review: type the verse from memory, then rate your recall.', 'hidden-word-bible-lessons' ),
			/* translators: %s: next review due date */
			'reviewSaved'    => __( 'Review saved — next due %s.', 'hidden-word-bible-lessons' ),
			'dueBannerOne'   => __( '1 review due today — start with recall practice below.', 'hidden-word-bible-lessons' ),
			/* translators: %d: number of reviews due today */
			'dueBannerMany'  => __( '%d reviews due today — start with recall practice below.', 'hidden-word-bible-lessons' ),
			'queueEmpty'     => __( 'No reviews due — great work! Open a lesson to add verses to your deck.', 'hidden-word-bible-lessons' ),
			'dueHeading'     => __( 'Due today', 'hidden-word-bible-lessons' ),
			'newHeading'     => __( 'New cards', 'hidden-word-bible-lessons' ),
			'modeReference'  => __( 'Enter the book, chapter, and verse (for example Romans chapter 1, verse 1).', 'hidden-word-bible-lessons' ),
			'referenceGood'  => __( 'Correct — you know the reference!', 'hidden-word-bible-lessons' ),
			'referencePartial'=> __( 'Close — check the book, chapter, and verse again.', 'hidden-word-bible-lessons' ),
			'referenceEmpty' => __( 'Enter a book, chapter, and verse to check.', 'hidden-word-bible-lessons' ),
			'audioLoading'   => __( 'Loading chapter audio…', 'hidden-word-bible-lessons' ),
			'audioPlaying'   => __( 'Playing chapter audio for this verse.', 'hidden-word-bible-lessons' ),
			'audioUnavailable'=> __( 'Audio is not available right now.', 'hidden-word-bible-lessons' ),
			'audioBlocked'   => __( 'Tap play on the audio player to listen.', 'hidden-word-bible-lessons' ),
		);
	}

	/**
	 * Review dashboard strings.
	 *
	 * @return array<string, string>
	 */
	public static function review_strings() {
		return array(
			'queueEmpty' => __( 'No reviews due — great work! Open a lesson to add verses to your deck.', 'hidden-word-bible-lessons' ),
			'dueHeading' => __( 'Due today', 'hidden-word-bible-lessons' ),
			'newHeading' => __( 'New cards', 'hidden-word-bible-lessons' ),
		);
	}

	/**
	 * Lesson tab UI strings.
	 *
	 * @return array<string, string>
	 */
	public static function lesson_tab_strings() {
		return array(
			'verseCopied'     => __( 'Verse copied.', 'hidden-word-bible-lessons' ),
			'copyFailed'      => __( 'Could not copy verse.', 'hidden-word-bible-lessons' ),
			'copyUnsupported' => __( 'Copy not supported in this browser.', 'hidden-word-bible-lessons' ),
		);
	}

	/**
	 * Localize memorization script config.
	 *
	 * @return array<string, mixed>
	 */
	public static function memorization_config() {
		return array(
			'today'        => wp_date( 'Y-m-d' ),
			'streakUpsell' => class_exists( 'THW_Premium' ) ? __( 'Save your streak across devices with Premium progress tracking.', 'hidden-word-bible-lessons' ) : '',
			'i18n'         => self::memorization_strings(),
			'restUrl'      => rest_url( 'hwbl/v1/' ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'loggedIn'     => is_user_logged_in(),
			'narrator'     => class_exists( 'HWBL_Bible_Reader' ) ? HWBL_Bible_Reader::get_default_narrator() : 'david',
		);
	}
}
