<?php
/**
 * Scheduler phrase tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Scheduler_Phrase_Test
 */
class HWBL_Scheduler_Phrase_Test extends TestCase {

	/**
	 * Load scheduler.
	 */
	public static function setUpBeforeClass(): void {
		require_once HWBL_PLUGIN_DIR . 'includes/class-scheduler.php';
	}

	/**
	 * Day mode memorize CTA.
	 */
	public function test_day_mode_memorize_phrase() {
		$phrase = HWBL_Scheduler::get_schedule_phrase( 'memorize', 'day' );
		$this->assertSame( "Today's Verse to Memorize", $phrase );
	}

	/**
	 * Week / month compact labels.
	 */
	public function test_week_and_month_compact_phrases() {
		$this->assertSame( 'Verse of the Week', HWBL_Scheduler::get_schedule_phrase( 'compact', 'week' ) );
		$this->assertSame( 'Verse of the Month', HWBL_Scheduler::get_schedule_phrase( 'compact', 'month' ) );
		$this->assertSame( "This Month's Verse to Memorize", HWBL_Scheduler::get_schedule_phrase( 'memorize', 'month' ) );
	}

	/**
	 * Catalog / find labels are schedule-agnostic verse language.
	 */
	public function test_catalog_and_find_phrases() {
		$this->assertSame( 'Verse Catalog', HWBL_Scheduler::get_schedule_phrase( 'catalog', 'week' ) );
		$this->assertSame( 'Find a Verse', HWBL_Scheduler::get_schedule_phrase( 'find', 'day' ) );
	}
}
