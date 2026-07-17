<?php
/**
 * Bible reader tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Bible_Reader_Test
 */
class HWBL_Bible_Reader_Test extends TestCase {

	/**
	 * Load reader dependencies.
	 */
	public static function setUpBeforeClass(): void {
		require_once HWBL_PLUGIN_DIR . 'includes/class-http-utils.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-bible-reader.php';
	}

	/**
	 * Reset options between tests.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options = array();
	}

	/**
	 * Chapter node parser returns headings and numbered verses.
	 */
	public function test_parse_chapter_nodes_extracts_headings_and_verses() {
		$content = array(
			array(
				'type'    => 'heading',
				'content' => array( 'Armor of God' ),
			),
			array(
				'type'    => 'verse',
				'number'  => 13,
				'content' => array( 'Therefore take up the full armor of God.' ),
			),
		);

		$parsed = HWBL_HelloAO_Provider::parse_chapter_nodes( $content );
		$this->assertSame( array( 'Armor of God' ), $parsed['headings'] );
		$this->assertCount( 1, $parsed['verses'] );
		$this->assertSame( 13, $parsed['verses'][0]['number'] );
		$this->assertStringContainsString( 'armor of God', $parsed['verses'][0]['text'] );
	}

	/**
	 * Audio links are read from Hello AO chapter JSON.
	 */
	public function test_get_chapter_audio_links() {
		$body = array(
			'thisChapterAudioLinks' => array(
				'david' => 'https://audio.bible.helloao.org/api/BSB/EPH/6/audio/david.mp3',
				'hays'  => 'https://audio.bible.helloao.org/api/BSB/EPH/6/audio/hays.mp3',
			),
		);

		$audio = HWBL_HelloAO_Provider::get_chapter_audio_links( $body );
		$this->assertArrayHasKey( 'david', $audio );
		$this->assertStringContainsString( 'helloao.org', $audio['david'] );
	}

	/**
	 * Reader is disabled when admin toggle is off.
	 */
	public function test_is_enabled_false_when_reader_disabled() {
		update_option( 'hwbl_bible_reader_enabled', false );
		update_option( 'hwbl_helloao_enabled', true );
		$this->assertFalse( HWBL_Bible_Reader::is_enabled() );
	}

	/**
	 * Reader requires Hello AO when it is the only provider.
	 */
	public function test_is_enabled_false_when_helloao_disabled() {
		update_option( 'hwbl_bible_reader_enabled', true );
		update_option( 'hwbl_helloao_enabled', false );
		$this->assertFalse( HWBL_Bible_Reader::is_enabled() );
	}

	/**
	 * Default narrator falls back to david.
	 */
	public function test_default_narrator() {
		delete_option( 'hwbl_bible_reader_narrator' );
		$this->assertSame( 'david', HWBL_Bible_Reader::get_default_narrator() );
		update_option( 'hwbl_bible_reader_narrator', 'hays' );
		$this->assertSame( 'hays', HWBL_Bible_Reader::get_default_narrator() );
	}

	/**
	 * HTML error pages are not treated as chapter text.
	 */
	public function test_looks_like_html_error_detects_forbidden_page() {
		$html = '<!DOCTYPE html><html><body><h2>403 - Forbidden: Access is denied.</h2></body></html>';
		$this->assertTrue( HWBL_Http_Utils::looks_like_html_error( $html ) );
	}

	public function test_sanitize_bible_text_strips_iis_error() {
		$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"><html><body><h2>403 - Forbidden: Access is denied.</h2></body></html>';
		$this->assertSame( '', HWBL_Http_Utils::sanitize_bible_text( $html ) );
		$this->assertSame( 'For God so loved the world.', HWBL_Http_Utils::sanitize_bible_text( 'For God so loved the world.' ) );
	}

	/**
	 * Valid bible.com VOTD HTML should not be treated as a block page.
	 */
	public function test_is_usable_bible_com_votd_html_accepts_real_page_shape() {
		$html = '<!DOCTYPE html><html><head><title>Verse of the Day - John 3:16 - Bible App</title>'
			. '<meta property="og:image" content="https://imageproxy.youversionapi.com/640x640/example.jpg" />'
			. '</head><body><script id="__NEXT_DATA__">{"props":{}}</script></body></html>';

		$this->assertFalse( HWBL_Http_Utils::looks_like_blocked_html_page( $html ) );
		$this->assertTrue( HWBL_Http_Utils::is_usable_bible_com_votd_html( $html ) );
		$this->assertTrue( HWBL_Http_Utils::looks_like_html_error( $html ) );
	}

	/**
	 * Invalid chapter payloads are rejected.
	 */
	public function test_is_valid_chapter_payload_rejects_error_html() {
		$payload = array(
			'verses' => array(
				array(
					'number' => 1,
					'text'   => '<!DOCTYPE html><title>403 - Forbidden</title>',
				),
			),
		);
		$this->assertFalse( HWBL_Http_Utils::is_valid_chapter_payload( $payload ) );
	}
}
