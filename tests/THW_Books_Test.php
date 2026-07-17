<?php
/**
 * Books helper tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Books_Test
 */
class HWBL_Books_Test extends TestCase {

	/**
	 * Testament boundaries follow canonical book order.
	 */
	public function test_get_testament_boundaries() {
		$this->assertSame( 'ot', HWBL_Books::get_testament( 1 ) );
		$this->assertSame( 'ot', HWBL_Books::get_testament( 39 ) );
		$this->assertSame( 'nt', HWBL_Books::get_testament( 40 ) );
		$this->assertSame( 'nt', HWBL_Books::get_testament( 66 ) );
		$this->assertSame( '', HWBL_Books::get_testament( 0 ) );
	}

	/**
	 * Book name resolves to canonical ID.
	 */
	public function test_get_id_by_name() {
		$this->assertSame( 43, HWBL_Books::get_id_by_name( 'John' ) );
		$this->assertSame( 0, HWBL_Books::get_id_by_name( 'Not A Book' ) );
	}

	/**
	 * USFM IDs cover numbered books.
	 */
	public function test_get_usfm_numbered_books() {
		$this->assertSame( '1SA', HWBL_Books::get_usfm( 9 ) );
		$this->assertSame( '2CO', HWBL_Books::get_usfm( 47 ) );
		$this->assertSame( '', HWBL_Books::get_usfm( 0 ) );
	}

	/**
	 * Biblia passage helper matches format_reference output.
	 */
	public function test_passage_for_biblia() {
		$this->assertSame(
			HWBL_Books::format_reference( 43, 3, 16 ),
			HWBL_Books::passage_for_biblia( 43, 3, 16 )
		);
	}

	/**
	 * Reference parser handles verse and chapter-only references.
	 */
	public function test_parse_reference() {
		$verse = HWBL_Books::parse_reference( 'John 3:16' );
		$this->assertSame( 43, $verse['book_id'] );
		$this->assertSame( 3, $verse['chapter'] );
		$this->assertSame( 16, $verse['verse'] );

		$chapter = HWBL_Books::parse_reference( 'Genesis 3' );
		$this->assertSame( 1, $chapter['book_id'] );
		$this->assertSame( 3, $chapter['chapter'] );
		$this->assertSame( 0, $chapter['verse'] );

		$abbr = HWBL_Books::parse_reference( 'Jn 3:16' );
		$this->assertSame( 43, $abbr['book_id'] );
	}

	/**
	 * USFM codes resolve back to book IDs.
	 */
	public function test_get_id_by_usfm() {
		$this->assertSame( 43, HWBL_Books::get_id_by_usfm( 'JHN' ) );
		$this->assertSame( 47, HWBL_Books::get_id_by_usfm( '2CO' ) );
		$this->assertSame( 0, HWBL_Books::get_id_by_usfm( 'ZZZ' ) );
	}

	/**
	 * YouVersion passage IDs parse to references.
	 */
	public function test_parse_youversion_passage_id() {
		$single = HWBL_Books::parse_youversion_passage_id( 'JHN.3.16' );
		$this->assertSame( 43, $single['book_id'] );
		$this->assertSame( 3, $single['chapter'] );
		$this->assertSame( 16, $single['verse'] );
		$this->assertSame( 'John 3:16', $single['reference'] );

		$range = HWBL_Books::parse_youversion_passage_id( '1CO.13.4-7' );
		$this->assertSame( 46, $range['book_id'] );
		$this->assertSame( 4, $range['verse'] );
		$this->assertSame( 7, $range['verse_end'] );
	}
}
