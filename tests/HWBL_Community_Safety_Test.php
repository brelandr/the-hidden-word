<?php
/**
 * Community safety report tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Community_Safety_Test
 */
class HWBL_Community_Safety_Test extends TestCase {

	/**
	 * Reset state.
	 */
	protected function setUp(): void {
		HWBL_Test_Options::$options        = array();
		$GLOBALS['hwbl_test_user_id']      = 7;
		$GLOBALS['hwbl_test_mail']         = array();
		$GLOBALS['hwbl_test_posts']        = array();
		$GLOBALS['hwbl_test_post_meta']    = array();
		$GLOBALS['hwbl_test_next_post_id'] = 300;
		update_option( 'admin_email', 'admin@example.org' );
	}

	/**
	 * Ask report creates a pending report.
	 */
	public function test_rest_report_ask() {
		$request = new WP_REST_Request(
			array(),
			array(
				'reason'   => 'misinformation',
				'question' => 'What is grace?',
				'answer'   => 'An AI answer',
			)
		);
		$result = HWBL_Community_Safety::rest_report_ask( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertSame( 201, $result->status );
		$this->assertTrue( $result->data['ok'] );
		$this->assertGreaterThan( 0, $result->data['reportId'] );
	}

	/**
	 * Cohort report rejects self.
	 */
	public function test_rest_report_cohort_rejects_self() {
		$request = new WP_REST_Request(
			array(),
			array(
				'user_id' => 7,
				'reason'  => 'harassment',
			)
		);
		$result = HWBL_Community_Safety::rest_report_cohort( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_cannot_report_self', $result->get_error_code() );
	}

	/**
	 * Cohort report succeeds for another user.
	 */
	public function test_rest_report_cohort_ok() {
		$request = new WP_REST_Request(
			array(),
			array(
				'user_id' => 9,
				'reason'  => 'spam',
				'details' => 'Offensive display name',
			)
		);
		$result = HWBL_Community_Safety::rest_report_cohort( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertSame( 201, $result->status );
	}

	/**
	 * Invalid reason rejected.
	 */
	public function test_sanitize_reason_invalid() {
		$result = HWBL_Community_Safety::sanitize_reason( 'not-a-reason' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
