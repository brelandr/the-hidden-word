<?php
/**
 * Tests for HWBL_Memorization_SRS SM-2 logic.
 *
 * @package Hidden_Word_Bible_Lessons
 */

require_once dirname( __DIR__ ) . '/tests/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-memorization-srs.php';

/**
 * Class HWBL_Memorization_SRS_Test
 */
class HWBL_Memorization_SRS_Test {

	/**
	 * @return void
	 */
	public function test_apply_sm2_success_increments_interval() {
		$card    = HWBL_Memorization_SRS::default_card( 1 );
		$updated = HWBL_Memorization_SRS::apply_sm2( $card, 5, 'recall' );

		assert( 1 === (int) $updated['repetitions'] );
		assert( 1 === (int) $updated['interval_days'] );
		assert( 5 === (int) $updated['last_quality'] );
		assert( 'recall' === $updated['last_mode'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_sm2_failure_resets_repetitions() {
		$card = array(
			'ease_factor'   => 2.5,
			'interval_days' => 10,
			'repetitions'   => 3,
			'due_date'      => '2026-07-01',
		);

		$updated = HWBL_Memorization_SRS::apply_sm2( $card, 2, 'hide' );

		assert( 0 === (int) $updated['repetitions'] );
		assert( 1 === (int) $updated['interval_days'] );
	}

	/**
	 * @return void
	 */
	public function test_can_claim_local_streak() {
		assert( true === HWBL_Memorization_SRS::can_claim_local_streak( 3, wp_date( 'Y-m-d' ) ) );
		assert( false === HWBL_Memorization_SRS::can_claim_local_streak( 0, wp_date( 'Y-m-d' ) ) );
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * @param string $format Format.
	 * @param int    $ts     Timestamp.
	 * @return string
	 */
	function wp_date( $format, $ts = null ) {
		return gmdate( $format, $ts ? $ts : time() );
	}
}

$test = new HWBL_Memorization_SRS_Test();
$test->test_apply_sm2_success_increments_interval();
$test->test_apply_sm2_failure_resets_repetitions();
$test->test_can_claim_local_streak();

echo "HWBL_Memorization_SRS_Test OK\n";
