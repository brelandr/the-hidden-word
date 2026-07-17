<?php
/**
 * Hello AO provider tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_HelloAO_Provider_Test
 */
class HWBL_HelloAO_Provider_Test extends TestCase {

	/**
	 * Reset options between tests.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options = array();
	}

	/**
	 * Chapter JSON verse nodes yield expected plain text.
	 */
	public function test_extract_verse_text_from_chapter_json() {
		$content = array(
			array(
				'type'    => 'verse',
				'number'  => 13,
				'content' => array(
					'Therefore take up the full armor of God, so that when the day of evil comes, you will be able to stand your ground, and having done everything, to stand.',
				),
			),
		);

		$text = HWBL_HelloAO_Provider::extract_verse_text( $content, 13 );
		$this->assertStringContainsString( 'full armor of God', $text );
	}

	/**
	 * Numbered New Testament books map to Hello AO USFM IDs.
	 */
	public function test_usfm_mapping_for_numbered_books() {
		$this->assertSame( '1JN', HWBL_Books::get_usfm( 62 ) );
		$this->assertSame( 'EPH', HWBL_Books::get_usfm( 49 ) );
	}

	/**
	 * Provider returns null when Hello AO is disabled.
	 */
	public function test_get_verse_returns_null_when_disabled() {
		update_option( 'hwbl_helloao_enabled', false );
		$provider = new HWBL_HelloAO_Provider();
		$this->assertNull( $provider->get_verse( 49, 6, 13, 'bsb' ) );
	}

	/**
	 * Unknown translation slugs are ignored.
	 */
	public function test_get_verse_returns_null_for_unknown_slug() {
		$provider = new HWBL_HelloAO_Provider();
		$this->assertNull( $provider->get_verse( 49, 6, 13, 'niv' ) );
	}
}
