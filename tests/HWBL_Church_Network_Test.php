<?php
/**
 * Church network join-code / claim / deep-link tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Church_Network_Test
 */
class HWBL_Church_Network_Test extends TestCase {

	/**
	 * Reset shared test state.
	 */
	protected function setUp(): void {
		HWBL_Test_Options::$options = array();
		$GLOBALS['hwbl_test_home']  = 'https://thehiddenword.org';
		$_SERVER['REMOTE_ADDR']     = '203.0.113.10';
	}

	/**
	 * Clean up.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options = array();
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Hub join URL encodes the church site.
	 */
	public function test_join_url_for_site() {
		$url = HWBL_Church_Network::join_url_for_site( 'https://grace.example/' );
		$this->assertSame(
			'https://thehiddenword.org/app/join?url=' . rawurlencode( 'https://grace.example' ),
			$url
		);
	}

	/**
	 * Deep link uses hwbl scheme.
	 */
	public function test_church_deep_link() {
		$link = HWBL_Church_Network::church_deep_link( 'https://grace.example/' );
		$this->assertSame( 'hwbl://church?url=' . rawurlencode( 'https://grace.example' ), $link );
	}

	/**
	 * Hub detection matches home_url to hub.
	 */
	public function test_is_hub_true_on_hub_home() {
		$GLOBALS['hwbl_test_home'] = 'https://thehiddenword.org';
		$this->assertTrue( HWBL_Church_Network::is_hub() );
	}

	/**
	 * Non-hub sites are not the directory host.
	 */
	public function test_is_hub_false_elsewhere() {
		$GLOBALS['hwbl_test_home'] = 'https://grace.example';
		$this->assertFalse( HWBL_Church_Network::is_hub() );
	}

	/**
	 * Create + claim round-trip.
	 */
	public function test_create_and_claim_join_code() {
		$code = HWBL_Church_Network::create_join_code( 'https://grace.example/' );
		$this->assertSame( 6, strlen( $code ) );

		$request = new WP_REST_Request( array( 'code' => strtolower( $code ) ) );
		$result  = HWBL_Church_Network::rest_claim_join_code( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertSame( 'https://grace.example', $result->data['siteUrl'] );
		$this->assertSame( strtoupper( $code ), $result->data['code'] );
		$this->assertStringContainsString( 'hwbl://church?url=', $result->data['deepLink'] );
	}

	/**
	 * Missing codes return 404.
	 */
	public function test_claim_missing_code_404() {
		$request = new WP_REST_Request( array( 'code' => 'ZZZZZZ' ) );
		$result  = HWBL_Church_Network::rest_claim_join_code( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	/**
	 * Short codes are rejected.
	 */
	public function test_claim_short_code_400() {
		$request = new WP_REST_Request( array( 'code' => 'AB' ) );
		$result  = HWBL_Church_Network::rest_claim_join_code( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * Join-code REST requires hub + HTTPS siteUrl.
	 */
	public function test_rest_create_join_code_validates_https() {
		$GLOBALS['hwbl_test_home'] = 'https://thehiddenword.org';
		$request                   = new WP_REST_Request( array(), array( 'siteUrl' => 'http://insecure.example' ) );
		$result                    = HWBL_Church_Network::rest_create_join_code( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_invalid_site', $result->get_error_code() );
	}

	/**
	 * Non-hub cannot mint codes.
	 */
	public function test_rest_create_join_code_requires_hub() {
		$GLOBALS['hwbl_test_home'] = 'https://grace.example';
		$request                   = new WP_REST_Request( array(), array( 'siteUrl' => 'https://grace.example' ) );
		$result                    = HWBL_Church_Network::rest_create_join_code( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_not_hub', $result->get_error_code() );
	}

	/**
	 * Successful create returns 201 payload.
	 */
	public function test_rest_create_join_code_success() {
		$GLOBALS['hwbl_test_home'] = 'https://thehiddenword.org';
		$request                   = new WP_REST_Request( array(), array( 'siteUrl' => 'https://grace.example/' ) );
		$result                    = HWBL_Church_Network::rest_create_join_code( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $result );
		$this->assertSame( 201, $result->status );
		$this->assertSame( 6, strlen( $result->data['code'] ) );
		$this->assertSame( 'https://grace.example', $result->data['siteUrl'] );
	}

	/**
	 * Public host check rejects private/local URLs.
	 */
	public function test_assert_public_https_host_rejects_private() {
		$result = HWBL_Church_Network::assert_public_https_host( 'https://127.0.0.1' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_private_host', $result->get_error_code() );

		$ok = HWBL_Church_Network::assert_public_https_host( 'https://grace.example' );
		$this->assertTrue( $ok );
	}

	/**
	 * Rate limit blocks after max creates.
	 */
	public function test_join_code_rate_limit() {
		$GLOBALS['hwbl_test_home'] = 'https://thehiddenword.org';
		$_SERVER['REMOTE_ADDR']    = '198.51.100.20';

		for ( $i = 0; $i < 30; $i++ ) {
			$request = new WP_REST_Request( array(), array( 'siteUrl' => 'https://grace.example' ) );
			$result  = HWBL_Church_Network::rest_create_join_code( $request );
			$this->assertInstanceOf( WP_REST_Response::class, $result, 'request ' . $i );
		}

		$request = new WP_REST_Request( array(), array( 'siteUrl' => 'https://grace.example' ) );
		$result  = HWBL_Church_Network::rest_create_join_code( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'hwbl_rate_limited', $result->get_error_code() );
		$this->assertSame( 429, $result->get_error_data()['status'] );
	}
}
