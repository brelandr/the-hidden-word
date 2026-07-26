<?php
/**
 * Tests for memorization audio resolution.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Memorization_Audio_Test
 */
class HWBL_Memorization_Audio_Test extends TestCase {

	/**
	 * Reset options between tests.
	 */
	protected function setUp(): void {
		HWBL_Test_Options::$options = array();
	}

	/**
	 * When Hello AO is disabled, audio resolve returns a helpful message.
	 */
	public function test_resolve_audio_when_helloao_disabled() {
		update_option( 'hwbl_helloao_enabled', false );
		$resolved = HWBL_Memorization_Audio::resolve_chapter_audio( 45, 1, 'niv' );
		$this->assertIsArray( $resolved );
		$this->assertSame( array(), $resolved['audio'] );
		$this->assertNotEmpty( $resolved['message'] );
		$this->assertStringContainsString( 'Hello AO', $resolved['message'] );
	}
}
