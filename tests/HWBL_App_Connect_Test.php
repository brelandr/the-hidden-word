<?php
/**
 * Companion app connect tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_App_Connect_Test
 */
class HWBL_App_Connect_Test extends TestCase {

	/**
	 * Reset stubs.
	 */
	protected function setUp(): void {
		HWBL_Test_Options::$options          = array();
		WP_Application_Passwords::$passwords = array();
		WP_Application_Passwords::$revoked   = array();
		$GLOBALS['hwbl_test_home']           = 'https://grace.example';
		$GLOBALS['hwbl_test_users']          = array();
		$GLOBALS['hwbl_test_usernames']      = array();
		$GLOBALS['hwbl_test_emails']         = array();
		$GLOBALS['hwbl_test_inserted_users'] = array();
		$GLOBALS['hwbl_test_next_user_id']   = 50;
		$_SERVER['REMOTE_ADDR']              = '203.0.113.10';
	}

	/**
	 * Connect URL is on this site.
	 */
	public function test_connect_url() {
		$this->assertSame( 'https://grace.example/app/connect/', HWBL_App_Connect::connect_url() );
	}

	/**
	 * Issue + claim returns credentials once.
	 */
	public function test_issue_and_claim() {
		$issued = HWBL_App_Connect::issue_auth_code_for_user( 7 );
		$this->assertIsArray( $issued );
		$this->assertArrayHasKey( 'code', $issued );
		$this->assertStringContainsString( 'hwbl://auth?', $issued['deepLink'] );

		$request = new WP_REST_Request( array( 'code' => $issued['code'] ) );
		$result  = HWBL_App_Connect::rest_claim_auth_code( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertSame( 'user7', $result->data['username'] );
		$this->assertNotEmpty( $result->data['appPassword'] );
		$this->assertSame( 'https://grace.example', $result->data['siteUrl'] );

		$again = HWBL_App_Connect::rest_claim_auth_code( $request );
		$this->assertInstanceOf( WP_Error::class, $again );
		$this->assertSame( 'hwbl_code_expired', $again->get_error_code() );
	}

	/**
	 * Self-signup disabled by default.
	 */
	public function test_register_disabled() {
		$result = HWBL_App_Connect::register_member( 'newmember', 'new@example.org', 'password123' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_signup_disabled', $result->get_error_code() );
	}

	/**
	 * Self-signup creates a subscriber when enabled.
	 */
	public function test_register_enabled() {
		update_option( 'hwbl_companion_self_signup', true );
		$id = HWBL_App_Connect::register_member( 'newmember', 'new@example.org', 'password123' );
		$this->assertSame( 50, $id );
		$this->assertSame( 'subscriber', $GLOBALS['hwbl_test_inserted_users'][0]['role'] );
	}

	/**
	 * App config exposes connectUrl + signup flag.
	 */
	public function test_app_config_connect_fields() {
		update_option( 'hwbl_companion_self_signup', true );
		$config = HWBL_App_Config::get_config();
		$this->assertSame( 'https://grace.example/app/connect/', $config['connectUrl'] );
		$this->assertTrue( $config['features']['companionSelfSignup'] );
	}

	/**
	 * Expo Go return bases are accepted; https return bases are not.
	 */
	public function test_expo_return_deep_link() {
		$deep = HWBL_App_Connect::build_auth_deep_link(
			'https://grace.example',
			'ABCDEFGHIJKL',
			'exp://192.168.1.20:8081/--/auth'
		);
		$this->assertStringStartsWith( 'exp://192.168.1.20:8081/--/auth?', $deep );
		$this->assertStringContainsString( 'code=ABCDEFGHIJKL', $deep );

		$this->assertSame( '', HWBL_App_Connect::sanitize_return_url( 'https://evil.example/phish' ) );
		$this->assertSame(
			'hwbl://auth?site=https%3A%2F%2Fgrace.example&code=ABCDEFGHIJKL',
			HWBL_App_Connect::build_auth_deep_link( 'https://grace.example', 'ABCDEFGHIJKL', '' )
		);
	}
}
