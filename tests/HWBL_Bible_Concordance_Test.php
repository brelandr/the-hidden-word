<?php
/**
 * Concordance helpers tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Bible_Concordance_Test
 */
class HWBL_Bible_Concordance_Test extends TestCase {

	/**
	 * Load class.
	 */
	public static function setUpBeforeClass(): void {
		require_once HWBL_PLUGIN_DIR . 'includes/class-bible-concordance.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-books.php';
	}

	/**
	 * Snippet centers around the query when present.
	 */
	public function test_build_snippet_includes_query() {
		$text = str_repeat( 'word ', 40 ) . 'Jerusalem is mentioned here ' . str_repeat( 'word ', 40 );
		$snip = HWBL_Bible_Concordance::build_snippet( $text, 'Jerusalem' );
		$this->assertStringContainsString( 'Jerusalem', $snip );
		$this->assertLessThanOrEqual( 160, strlen( $snip ) );
	}

	/**
	 * Empty query returns a clear error payload.
	 */
	public function test_lookup_rejects_empty_query() {
		$payload = HWBL_Bible_Concordance::lookup( '' );
		$this->assertSame( 'invalid_query', $payload['error'] );
		$this->assertSame( array(), $payload['results'] );
	}

	/**
	 * Sort / testament helpers still align with Protestant book IDs.
	 */
	public function test_testament_helpers() {
		$this->assertSame( 'ot', HWBL_Books::get_testament( 1 ) );
		$this->assertSame( 'nt', HWBL_Books::get_testament( 43 ) );
	}
}
