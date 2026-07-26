<?php
/**
 * Public-domain Bible text search tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Bible_Text_Search_Test
 */
class HWBL_Bible_Text_Search_Test extends TestCase {

	/**
	 * Load class under test.
	 */
	public static function setUpBeforeClass(): void {
		require_once HWBL_PLUGIN_DIR . 'includes/class-bible-text-search.php';
	}

	/**
	 * Trailing punctuation is stripped from phrase queries.
	 */
	public function test_normalize_query_strips_trailing_punctuation() {
		$this->assertSame(
			'In the beginning was the Word',
			HWBL_Bible_Text_Search::normalize_query( 'In the beginning was the Word.' )
		);
	}

	/**
	 * Unsupported prefs fall back to KJV for public search.
	 */
	public function test_resolve_translation_falls_back_to_kjv() {
		$this->assertSame( 'kjv', HWBL_Bible_Text_Search::resolve_translation( 'niv' ) );
		$this->assertSame( 'kjv', HWBL_Bible_Text_Search::resolve_translation( 'bsb' ) );
		$this->assertSame( 'asv', HWBL_Bible_Text_Search::resolve_translation( 'asv' ) );
	}
}
