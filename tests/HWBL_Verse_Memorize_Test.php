<?php
/**
 * Verse memorization picker tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Verse_Memorize_Test
 */
class HWBL_Verse_Memorize_Test extends TestCase {

	/**
	 * Reference parser requires a verse number.
	 */
	public function test_parse_reference_requires_verse() {
		$this->assertNull( HWBL_Verse_Memorize::parse_reference( 'Genesis 3' ) );
	}

	/**
	 * Reference parser handles common references.
	 */
	public function test_parse_reference_success() {
		$parsed = HWBL_Verse_Memorize::parse_reference( 'John 3:16' );
		$this->assertSame( 43, $parsed['book_id'] );
		$this->assertSame( 3, $parsed['chapter'] );
		$this->assertSame( 16, $parsed['verse_start'] );
		$this->assertSame( 'John 3:16', $parsed['reference'] );
	}

	/**
	 * Verse keys are stable for lookups.
	 */
	public function test_verse_key() {
		$this->assertSame( '43-3-16-16', HWBL_Verse_Memorize::verse_key( 43, 3, 16 ) );
		$this->assertSame( '43-3-16-18', HWBL_Verse_Memorize::verse_key( 43, 3, 16, 18 ) );
		$this->assertSame( '43-3-16-16-kjv', HWBL_Verse_Memorize::verse_key( 43, 3, 16, 16, 'kjv' ) );
	}

	/**
	 * Companion detection uses the X-HWBL-Client header.
	 */
	public function test_is_companion_request() {
		unset( $_SERVER['HTTP_X_HWBL_CLIENT'] );
		$this->assertFalse( HWBL_Verse_Memorize::is_companion_request() );

		$_SERVER['HTTP_X_HWBL_CLIENT'] = 'companion';
		$this->assertTrue( HWBL_Verse_Memorize::is_companion_request() );

		$_SERVER['HTTP_X_HWBL_CLIENT'] = 'website';
		$this->assertFalse( HWBL_Verse_Memorize::is_companion_request() );

		unset( $_SERVER['HTTP_X_HWBL_CLIENT'] );
	}
}
