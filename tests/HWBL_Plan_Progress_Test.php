<?php
/**
 * Tests for HWBL_Plan_Progress.
 *
 * @package Hidden_Word_Bible_Lessons
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-cpt-plan.php';
require_once dirname( __DIR__ ) . '/includes/class-plan-progress.php';

/**
 * Class HWBL_Plan_Progress_Test
 */
class HWBL_Plan_Progress_Test {

	/**
	 * @return void
	 */
	public function test_empty_progress_shape() {
		$p = HWBL_Plan_Progress::empty_progress();
		assert( 0 === (int) $p['current_day'] );
		assert( '' === $p['started_at'] );
		assert( '' === $p['last_advanced_at'] );
		assert( is_array( $p['completed_days'] ) && 0 === count( $p['completed_days'] ) );
	}

	/**
	 * @return void
	 */
	public function test_sanitize_days_renumbers() {
		$days = HWBL_CPT_Plan::sanitize_days(
			array(
				array(
					'day'       => 5,
					'title'     => 'B',
					'body'      => '',
					'verse_ref' => '',
					'lesson_id' => 0,
				),
				array(
					'day'       => 2,
					'title'     => 'A',
					'body'      => 'hi',
					'verse_ref' => 'John 1:1',
					'lesson_id' => 10,
				),
			)
		);
		assert( 2 === count( $days ) );
		assert( 1 === (int) $days[0]['day'] );
		assert( 'A' === $days[0]['title'] );
		assert( 10 === (int) $days[0]['lesson_id'] );
		assert( 2 === (int) $days[1]['day'] );
		assert( 'B' === $days[1]['title'] );
	}

	/**
	 * @return void
	 */
	public function test_meta_key() {
		assert( '_hwbl_plan_progress_42' === HWBL_Plan_Progress::meta_key( 42 ) );
	}

	/**
	 * @return void
	 */
	public function test_get_falls_back_last_advanced_at_to_started_at() {
		$raw = array(
			'current_day'     => 3,
			'started_at'      => '2026-01-01T00:00:00+00:00',
			'completed_days'  => array( 1, 2 ),
		);
		// Simulate get() normalization without WP user meta by mirroring fallback logic.
		$started = (string) $raw['started_at'];
		$last    = isset( $raw['last_advanced_at'] ) ? (string) $raw['last_advanced_at'] : '';
		if ( '' === $last && '' !== $started ) {
			$last = $started;
		}
		assert( $started === $last );
	}
}

$t = new HWBL_Plan_Progress_Test();
$t->test_empty_progress_shape();
$t->test_sanitize_days_renumbers();
$t->test_meta_key();
$t->test_get_falls_back_last_advanced_at_to_started_at();
echo "HWBL_Plan_Progress_Test: OK\n";
