<?php
/**
 * Lesson list helper tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Lesson_List_Test
 */
class HWBL_Lesson_List_Test extends TestCase {

	/**
	 * Pagination is omitted for a single page.
	 */
	public function test_render_pagination_empty_for_single_page() {
		require_once HWBL_PLUGIN_DIR . 'includes/class-lesson-list.php';
		$this->assertSame( '', HWBL_Lesson_List::render_pagination( 1, 1 ) );
	}

	/**
	 * Pagination renders page links.
	 */
	public function test_render_pagination_renders_links() {
		require_once HWBL_PLUGIN_DIR . 'includes/class-lesson-list.php';
		$html = HWBL_Lesson_List::render_pagination( 2, 3 );
		$this->assertStringContainsString( 'hwbl-lesson-list-pagination', $html );
		$this->assertStringContainsString( 'is-current', $html );
	}
}
