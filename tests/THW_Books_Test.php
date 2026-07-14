<?php
/**
 * Books helper tests.
 *
 * @package The_Hidden_Word
 */

use PHPUnit\Framework\TestCase;

/**
 * Class THW_Books_Test
 */
class THW_Books_Test extends TestCase {

	/**
	 * Testament boundaries follow canonical book order.
	 */
	public function test_get_testament_boundaries() {
		$this->assertSame( 'ot', THW_Books::get_testament( 1 ) );
		$this->assertSame( 'ot', THW_Books::get_testament( 39 ) );
		$this->assertSame( 'nt', THW_Books::get_testament( 40 ) );
		$this->assertSame( 'nt', THW_Books::get_testament( 66 ) );
		$this->assertSame( '', THW_Books::get_testament( 0 ) );
	}

	/**
	 * Book name resolves to canonical ID.
	 */
	public function test_get_id_by_name() {
		$this->assertSame( 43, THW_Books::get_id_by_name( 'John' ) );
		$this->assertSame( 0, THW_Books::get_id_by_name( 'Not A Book' ) );
	}
}
