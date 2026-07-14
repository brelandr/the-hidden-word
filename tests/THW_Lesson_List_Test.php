<?php
/**
 * Lesson list helper tests.
 *
 * @package The_Hidden_Word
 */

use PHPUnit\Framework\TestCase;

/**
 * Class THW_Lesson_List_Test
 */
class THW_Lesson_List_Test extends TestCase {

	/**
	 * Pagination is omitted for a single page.
	 */
	public function test_render_pagination_empty_for_single_page() {
		require_once THW_PLUGIN_DIR . 'includes/class-lesson-list.php';
		$this->assertSame( '', THW_Lesson_List::render_pagination( 1, 1 ) );
	}

	/**
	 * Pagination renders page links.
	 */
	public function test_render_pagination_renders_links() {
		require_once THW_PLUGIN_DIR . 'includes/class-lesson-list.php';
		$html = THW_Lesson_List::render_pagination( 2, 3 );
		$this->assertStringContainsString( 'thw-lesson-list-pagination', $html );
		$this->assertStringContainsString( 'is-current', $html );
	}
}
