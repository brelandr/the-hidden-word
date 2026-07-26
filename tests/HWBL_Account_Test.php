<?php
/**
 * Account deletion tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Account_Test
 */
class HWBL_Account_Test extends TestCase {

	/**
	 * Reset state.
	 */
	protected function setUp(): void {
		HWBL_Test_Options::$options     = array();
		HWBL_Test_User_Meta::$meta      = array();
		WP_Application_Passwords::$revoked = array();
		$GLOBALS['hwbl_test_user_id']   = 7;
		$GLOBALS['hwbl_test_mail']      = array();
		$GLOBALS['hwbl_test_posts']     = array();
		$GLOBALS['hwbl_test_post_meta'] = array();
		$GLOBALS['hwbl_test_next_post_id'] = 200;
		update_option( 'admin_email', 'admin@example.org' );
	}

	/**
	 * Confirm string is required.
	 */
	public function test_delete_requires_confirm() {
		$request = new WP_REST_Request( array(), array( 'confirm' => 'nope' ) );
		$result  = HWBL_Account::rest_delete_account( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_confirm_required', $result->get_error_code() );
	}

	/**
	 * Successful deletion marks pending and revokes app passwords.
	 */
	public function test_request_deletion_marks_pending() {
		$result = HWBL_Account::request_deletion( 7 );
		$this->assertIsArray( $result );
		$this->assertSame( 'pending_deletion', $result['status'] );
		$this->assertTrue( $result['revokedAppPasswords'] );
		$this->assertTrue( HWBL_Account::is_pending_deletion( 7 ) );
		$this->assertTrue( ! empty( WP_Application_Passwords::$revoked[7] ) );
		$this->assertNotEmpty( $GLOBALS['hwbl_test_mail'] );
	}

	/**
	 * Login filter rejects pending deletion users.
	 */
	public function test_block_pending_deletion_login() {
		update_user_meta( 7, HWBL_Account::META_STATUS, HWBL_Account::STATUS_PENDING );
		$user   = new WP_User( 7 );
		$result = HWBL_Account::block_pending_deletion_login( $user, 'x' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
