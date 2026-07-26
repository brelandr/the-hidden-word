<?php
/**
 * Email verification tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Email_Verification_Test
 */
class HWBL_Email_Verification_Test extends TestCase {

	/**
	 * Reset state.
	 */
	protected function setUp(): void {
		HWBL_Test_User_Meta::$meta     = array();
		$GLOBALS['hwbl_test_mail']     = array();
		$GLOBALS['hwbl_test_users']    = array(
			9 => new WP_User( 9, 'newbie', 'newbie@example.org' ),
		);
		$GLOBALS['hwbl_test_redirect'] = null;
	}

	/**
	 * Legacy users without meta remain sign-in capable.
	 */
	public function test_missing_meta_counts_as_verified() {
		$this->assertTrue( HWBL_Email_Verification::is_verified( 9 ) );
		$this->assertFalse( HWBL_Email_Verification::is_pending( 9 ) );
		$user = new WP_User( 9 );
		$this->assertSame( $user, HWBL_Email_Verification::block_unverified_login( $user, 'x' ) );
	}

	/**
	 * start_for_user marks pending and sends mail.
	 */
	public function test_start_for_user_marks_pending_and_mails() {
		$result = HWBL_Email_Verification::start_for_user( 9 );
		$this->assertTrue( $result );
		$this->assertTrue( HWBL_Email_Verification::is_pending( 9 ) );
		$this->assertFalse( HWBL_Email_Verification::is_verified( 9 ) );
		$this->assertNotEmpty( $GLOBALS['hwbl_test_mail'] );
		$this->assertNotEmpty( get_user_meta( 9, HWBL_Email_Verification::META_KEY, true ) );
	}

	/**
	 * Login filter rejects pending users.
	 */
	public function test_block_unverified_login() {
		HWBL_Email_Verification::start_for_user( 9 );
		$user   = new WP_User( 9 );
		$result = HWBL_Email_Verification::block_unverified_login( $user, 'x' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_email_unverified', $result->get_error_code() );
	}

	/**
	 * Valid key activates the account.
	 */
	public function test_verify_success() {
		HWBL_Email_Verification::start_for_user( 9 );
		$key = (string) get_user_meta( 9, HWBL_Email_Verification::META_KEY, true );
		$this->assertNotSame( '', $key );

		$result = HWBL_Email_Verification::verify( 9, $key );
		$this->assertTrue( $result );
		$this->assertTrue( HWBL_Email_Verification::is_verified( 9 ) );
		$this->assertSame( '', (string) get_user_meta( 9, HWBL_Email_Verification::META_KEY, true ) );
	}

	/**
	 * Bad key is rejected.
	 */
	public function test_verify_rejects_bad_key() {
		HWBL_Email_Verification::start_for_user( 9 );
		$result = HWBL_Email_Verification::verify( 9, 'not-the-key' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertTrue( HWBL_Email_Verification::is_pending( 9 ) );
	}

	/**
	 * Expired key is rejected.
	 */
	public function test_verify_rejects_expired_key() {
		HWBL_Email_Verification::start_for_user( 9 );
		$key = (string) get_user_meta( 9, HWBL_Email_Verification::META_KEY, true );
		update_user_meta( 9, HWBL_Email_Verification::META_EXPIRES, time() - 10 );
		$result = HWBL_Email_Verification::verify( 9, $key );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_verify_expired', $result->get_error_code() );
	}

	/**
	 * Verify URL includes uid and key.
	 */
	public function test_verify_url_contains_params() {
		$url = HWBL_Email_Verification::get_verify_url( 9, 'abc123' );
		$this->assertStringContainsString( 'hwbl_verify_email=1', $url );
		$this->assertStringContainsString( 'uid=9', $url );
		$this->assertStringContainsString( 'key=abc123', $url );
	}
}
