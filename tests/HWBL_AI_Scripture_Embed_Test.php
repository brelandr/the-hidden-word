<?php
/**
 * AI scripture-embedding policy tests (licensed vs free translations).
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_AI_Scripture_Embed_Test
 */
class HWBL_AI_Scripture_Embed_Test extends TestCase {

	/**
	 * Load premium helpers + explain prompt builders.
	 */
	public static function setUpBeforeClass(): void {
		require_once HWBL_PLUGIN_DIR . 'premium/includes/thw-premium-functions.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-ai-explain.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-bible-reader-explain.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-verse-of-the-day.php';
	}

	/**
	 * Restricted list includes NIV and other licensed BYOK editions.
	 */
	public function test_restricted_list_includes_licensed_translations() {
		$restricted = thw_premium_ai_restricted_scripture_translations();
		foreach ( array( 'niv', 'esv', 'nlt', 'nasb', 'csb', 'nkjv', 'amp', 'net', 'leb' ) as $slug ) {
			$this->assertContains( $slug, $restricted );
		}
		$this->assertFalse( thw_premium_ai_may_embed_scripture_text( 'niv' ) );
		$this->assertFalse( thw_premium_ai_may_embed_scripture_text( 'esv' ) );
	}

	/**
	 * Free / public-domain translations may still embed wording.
	 */
	public function test_free_translations_may_embed() {
		foreach ( array( 'kjv', 'web', 'asv', 'bsb', 'bbe', 'ylt', 'dby' ) as $slug ) {
			$this->assertTrue(
				thw_premium_ai_may_embed_scripture_text( $slug ),
				$slug . ' should allow scripture embedding in AI prompts'
			);
		}
	}

	/**
	 * Bible reader explain omits NIV verse body from the prompt.
	 */
	public function test_reader_explain_prompt_omits_licensed_verse_text() {
		$secret = 'UNIQUE_NIV_PHRASE_FORBID_IN_PROMPT_7f3a';
		$prompt = THW_Premium_Bible_Reader_Explain::build_prompt(
			array(
				'reference'   => 'John 3:16',
				'translation' => 'niv',
				'text'        => $secret,
				'follow_on'   => '17 ' . $secret . '_FOLLOW',
			),
			'verse'
		);

		$this->assertStringContainsString( 'John 3:16', $prompt );
		$this->assertStringContainsString( 'licensed translation', $prompt );
		$this->assertStringNotContainsString( $secret, $prompt );
		$this->assertStringNotContainsString( 'Verse text:', $prompt );
	}

	/**
	 * Bible reader explain still embeds free/PD verse text.
	 */
	public function test_reader_explain_prompt_includes_free_verse_text() {
		$phrase = 'UNIQUE_WEB_PHRASE_ALLOW_IN_PROMPT_9c2b';
		$prompt = THW_Premium_Bible_Reader_Explain::build_prompt(
			array(
				'reference'   => 'John 3:16',
				'translation' => 'web',
				'text'        => $phrase,
				'follow_on'   => '',
			),
			'verse'
		);

		$this->assertStringContainsString( 'Verse text:', $prompt );
		$this->assertStringContainsString( $phrase, $prompt );
	}

	/**
	 * VOTD explain omits licensed verse wording.
	 */
	public function test_votd_explain_prompt_omits_licensed_verse_text() {
		$secret = 'UNIQUE_VOTD_NIV_PHRASE_5e1d';
		$prompt = THW_Premium_Verse_Of_The_Day::build_explain_prompt(
			array(
				'reference'   => 'Philippians 4:6',
				'translation' => 'niv',
				'text'        => $secret,
				'book_id'     => 50,
				'chapter'     => 4,
				'verse_start' => 6,
				'verse_end'   => 6,
			)
		);

		$this->assertStringContainsString( 'Philippians 4:6', $prompt );
		$this->assertStringNotContainsString( $secret, $prompt );
		$this->assertStringNotContainsString( 'Verse text:', $prompt );
	}

	/**
	 * Lesson explain omits NIV verse text while keeping curriculum notes.
	 */
	public function test_lesson_explain_prompt_omits_niv_verse_but_keeps_context() {
		update_option( 'hwbl_active_translation', 'niv' );

		$prompt = THW_Premium_AI_Explain::build_prompt(
			array(
				'reference'            => 'Ephesians 6:13',
				'book_id'              => 49,
				'chapter'              => 6,
				'verse_start'          => 13,
				'historical_context'   => 'CURRICULUM_CONTEXT_MARKER_aa11',
				'preceding_narrative'  => '',
				'discussion_questions' => array(),
			),
			'all'
		);

		$this->assertStringContainsString( 'Ephesians 6:13', $prompt );
		$this->assertStringContainsString( 'CURRICULUM_CONTEXT_MARKER_aa11', $prompt );
		$this->assertStringContainsString( 'licensed translation', $prompt );
		$this->assertStringNotContainsString( "Verse:\n", $prompt );
	}
}
