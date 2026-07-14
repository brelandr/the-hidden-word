<?php
/**
 * Verse count and curriculum tests.
 *
 * @package The_Hidden_Word
 */

use PHPUnit\Framework\TestCase;

/**
 * Class THW_Curriculum_Test
 */
class THW_Curriculum_Test extends TestCase {

	/**
	 * NIV curriculum must not exceed fair-use limit.
	 */
	public function test_niv_verse_count_within_limit() {
		$path = THW_PLUGIN_DIR . 'data/niv-curriculum.json';
		$this->assertFileExists( $path );
		$data = json_decode( file_get_contents( $path ), true );
		$this->assertIsArray( $data );
		$this->assertLessThanOrEqual( THW_MAX_NIV_VERSES, THW_Curriculum::count_verses( $data ) );
	}

	/**
	 * Bundled curriculum should use the full 500-verse NIV allowance.
	 */
	public function test_niv_curriculum_uses_full_allowance() {
		$data = THW_Curriculum::load_niv();
		$this->assertCount( 500, $data );
		$this->assertSame( 500, THW_Curriculum::count_verses( $data ) );
	}

	/**
	 * KJV curriculum must mirror NIV lesson count.
	 */
	public function test_kjv_matches_niv_lesson_count() {
		$niv = THW_Curriculum::load_niv();
		$kjv = THW_Curriculum::load_kjv();
		$this->assertCount( count( $niv ), $kjv );
	}

	/**
	 * WEB curriculum must mirror NIV lesson count.
	 */
	public function test_web_matches_niv_lesson_count() {
		$niv = THW_Curriculum::load_niv();
		$web = THW_Curriculum::load_web();
		$this->assertCount( count( $niv ), $web );
	}

	/**
	 * WEB provider returns John 3:16 text.
	 */
	public function test_bundled_provider_web_john_3_16() {
		$provider = new THW_Bundled_Provider();
		$text     = $provider->get_verse( 43, 3, 16, 'web' );
		$this->assertNotEmpty( $text );
		$this->assertStringContainsString( 'God so loved', $text );
	}

	/**
	 * Bundled provider returns NIV text for John 3:16.
	 */
	public function test_bundled_provider_john_3_16() {
		$provider = new THW_Bundled_Provider();
		$text     = $provider->get_verse( 43, 3, 16, 'niv' );
		$this->assertNotEmpty( $text );
		$this->assertStringContainsString( 'God so loved', $text );
	}

	/**
	 * Books JSON has 66 entries.
	 */
	public function test_books_count() {
		$books = THW_Books::get_all();
		$this->assertCount( 66, $books );
	}

	/**
	 * Each NIV lesson includes enriched lesson fields.
	 */
	public function test_niv_curriculum_lessons_are_enriched() {
		$data = THW_Curriculum::load_niv();

		foreach ( $data as $lesson ) {
			$num = THW_Curriculum::get_entry_lesson_number( $lesson );
			$this->assertNotEmpty( $lesson['historical_context'], 'Lesson ' . $num . ' missing historical_context' );
			$this->assertNotEmpty( $lesson['preceding_narrative'], 'Lesson ' . $num . ' missing preceding_narrative' );
			$this->assertIsArray( $lesson['discussion_questions'] );
			$this->assertGreaterThanOrEqual( 3, count( $lesson['discussion_questions'] ), 'Lesson ' . $num . ' needs discussion questions' );
		}
	}
}
