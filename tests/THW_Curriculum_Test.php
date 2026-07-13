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
		$this->assertLessThanOrEqual( 500, count( $data ) );
	}

	/**
	 * Combined bundled verses must not exceed limit.
	 */
	public function test_combined_verse_count_within_limit() {
		$total = 0;
		foreach ( array( 'niv-curriculum.json', 'kjv-curriculum.json' ) as $file ) {
			$path = THW_PLUGIN_DIR . 'data/' . $file;
			$data = json_decode( file_get_contents( $path ), true );
			$total += count( $data );
		}
		$this->assertLessThanOrEqual( THW_MAX_BUNDLED_VERSES, $total );
	}

	/**
	 * Curriculum must have 52 weeks.
	 */
	public function test_curriculum_has_52_weeks() {
		$path = THW_PLUGIN_DIR . 'data/niv-curriculum.json';
		$data = json_decode( file_get_contents( $path ), true );
		$this->assertCount( 52, $data );
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
	 * Each NIV week includes enriched lesson fields.
	 */
	public function test_niv_curriculum_weeks_are_enriched() {
		$path = THW_PLUGIN_DIR . 'data/niv-curriculum.json';
		$data = json_decode( file_get_contents( $path ), true );

		foreach ( $data as $week ) {
			$this->assertNotEmpty( $week['historical_context'], 'Week ' . $week['week'] . ' missing historical_context' );
			$this->assertNotEmpty( $week['preceding_narrative'], 'Week ' . $week['week'] . ' missing preceding_narrative' );
			$this->assertIsArray( $week['discussion_questions'] );
			$this->assertGreaterThanOrEqual( 3, count( $week['discussion_questions'] ), 'Week ' . $week['week'] . ' needs discussion questions' );
		}
	}
}
