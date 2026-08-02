<?php
/**
 * Tests for OpenBible place index loader.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Bible_Places_Test
 */
class HWBL_Bible_Places_Test extends TestCase {

	/**
	 * Load places class.
	 */
	public static function setUpBeforeClass(): void {
		require_once HWBL_PLUGIN_DIR . 'includes/class-bible-places.php';
	}

	/**
	 * Sort keys use BBCCCVVV.
	 */
	public function test_sort_key_format() {
		$this->assertSame( '43003016', HWBL_Bible_Places::sort_key( 43, 3, 16 ) );
		$this->assertSame( '01001001', HWBL_Bible_Places::sort_key( 1, 1, 1 ) );
	}

	/**
	 * John 3 chapter shard includes mapped places.
	 */
	public function test_john_chapter_has_places() {
		if ( ! is_readable( HWBL_Bible_Places::meta_path() ) ) {
			$this->markTestSkipped( 'Bible geo index not present.' );
		}

		$places = HWBL_Bible_Places::get_places( 43, 3, 0 );
		$this->assertNotEmpty( $places );
		$first = $places[0];
		$this->assertArrayHasKey( 'name', $first );
		$this->assertArrayHasKey( 'lat', $first );
		$this->assertArrayHasKey( 'lng', $first );
		$this->assertTrue( abs( $first['lat'] ) <= 90 );
		$this->assertTrue( abs( $first['lng'] ) <= 180 );
	}

	/**
	 * Attribution credits OpenBible.
	 */
	public function test_attribution_present() {
		if ( ! is_readable( HWBL_Bible_Places::meta_path() ) ) {
			$this->markTestSkipped( 'Bible geo index not present.' );
		}
		$attr = HWBL_Bible_Places::get_attribution();
		$this->assertStringContainsString( 'OpenBible', $attr );
	}
}
