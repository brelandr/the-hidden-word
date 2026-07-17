<?php
/**
 * Verse count and curriculum tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Curriculum_Test
 */
class HWBL_Curriculum_Test extends TestCase {

	/**
	 * NIV curriculum must not exceed fair-use limit.
	 */
	public function test_niv_verse_count_within_limit() {
		$path = HWBL_PLUGIN_DIR . 'data/niv-curriculum.json';
		$this->assertFileExists( $path );
		$data = json_decode( file_get_contents( $path ), true );
		$this->assertIsArray( $data );
		$this->assertLessThanOrEqual( HWBL_MAX_NIV_VERSES, HWBL_Curriculum::count_verses( $data ) );
	}

	/**
	 * Bundled curriculum should use the full 500-verse NIV allowance.
	 */
	public function test_niv_curriculum_uses_full_allowance() {
		$data = HWBL_Curriculum::load_niv();
		$this->assertCount( 500, $data );
		$this->assertSame( 500, HWBL_Curriculum::count_verses( $data ) );
	}

	/**
	 * KJV curriculum must mirror NIV lesson count.
	 */
	public function test_kjv_matches_niv_lesson_count() {
		$niv = HWBL_Curriculum::load_niv();
		$kjv = HWBL_Curriculum::load_kjv();
		$this->assertCount( count( $niv ), $kjv );
	}

	/**
	 * WEB curriculum must mirror NIV lesson count.
	 */
	public function test_web_matches_niv_lesson_count() {
		$niv = HWBL_Curriculum::load_niv();
		$web = HWBL_Curriculum::load_web();
		$this->assertCount( count( $niv ), $web );
	}

	/**
	 * WEB provider returns John 3:16 text.
	 */
	public function test_bundled_provider_web_john_3_16() {
		$provider = new HWBL_Bundled_Provider();
		$text     = $provider->get_verse( 43, 3, 16, 'web' );
		$this->assertNotEmpty( $text );
		$this->assertStringContainsString( 'God so loved', $text );
	}

	/**
	 * Bundled provider returns NIV text for John 3:16.
	 */
	public function test_bundled_provider_john_3_16() {
		$provider = new HWBL_Bundled_Provider();
		$text     = $provider->get_verse( 43, 3, 16, 'niv' );
		$this->assertNotEmpty( $text );
		$this->assertStringContainsString( 'God so loved', $text );
	}

	/**
	 * Books JSON has 66 entries.
	 */
	public function test_books_count() {
		$books = HWBL_Books::get_all();
		$this->assertCount( 66, $books );
	}

	/**
	 * Default follow-on verses use the lines before and after the lesson.
	 */
	public function test_default_follow_on_verses_for_john_3_16() {
		$echo = HWBL_Curriculum::default_follow_on_verses( 43, 3, 16, 16 );
		$this->assertCount( 2, $echo );
		$this->assertSame( 15, $echo[0]['verse'] );
		$this->assertSame( 17, $echo[1]['verse'] );
	}

	/**
	 * Echo verse cache includes public-domain text for John 3:15.
	 */
	public function test_echo_verse_cache_john_3_15() {
		$path = HWBL_PLUGIN_DIR . 'data/echo-verses.json';
		if ( ! is_readable( $path ) ) {
			$this->markTestSkipped( 'echo-verses.json not built yet' );
		}

		$result = HWBL_Curriculum::get_echo_verse_text( 43, 3, 15, 'web' );
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result['text'] );
		$this->assertSame( 'web', $result['translation'] );
	}

	/**
	 * Each NIV lesson includes enriched lesson fields.
	 */
	public function test_niv_curriculum_lessons_are_enriched() {
		$data = HWBL_Curriculum::load_niv();

		foreach ( $data as $lesson ) {
			$num = HWBL_Curriculum::get_entry_lesson_number( $lesson );
			$this->assertNotEmpty( $lesson['historical_context'], 'Lesson ' . $num . ' missing historical_context' );
			$this->assertNotEmpty( $lesson['preceding_narrative'], 'Lesson ' . $num . ' missing preceding_narrative' );
			$this->assertIsArray( $lesson['discussion_questions'] );
			$this->assertGreaterThanOrEqual( 3, count( $lesson['discussion_questions'] ), 'Lesson ' . $num . ' needs discussion questions' );
		}
	}
}
