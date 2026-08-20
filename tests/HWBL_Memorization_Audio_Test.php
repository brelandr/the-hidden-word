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

	/**
	 * Non-English Hello AO editions do not fall back to English chapter audio.
	 */
	public function test_non_english_does_not_fallback_to_english_audio() {
		update_option( 'hwbl_helloao_enabled', true );
		$resolved = HWBL_Memorization_Audio::resolve_chapter_audio( 43, 3, 'spa_r09' );
		$this->assertIsArray( $resolved );
		$this->assertSame( array(), $resolved['audio'] );
		$this->assertSame( 'spa_r09', $resolved['translation'] );
		$this->assertStringContainsString( 'text only', strtolower( $resolved['message'] ) );
		$this->assertStringNotContainsString( 'playing the chapter in', strtolower( $resolved['message'] ) );
	}
}
