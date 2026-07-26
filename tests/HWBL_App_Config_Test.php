<?php
/**
 * Companion app-config tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_App_Config_Test
 */
class HWBL_App_Config_Test extends TestCase {

	/**
	 * Reset options.
	 */
	protected function setUp(): void {
		HWBL_Test_Options::$options = array();
		$GLOBALS['hwbl_test_home']  = 'https://grace.example';
		$GLOBALS['hwbl_test_blog']  = 'Grace Community';
	}

	/**
	 * Clean up.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options = array();
	}

	/**
	 * Defaults include identity, deep link, and feature flags.
	 */
	public function test_get_config_defaults() {
		$config = HWBL_App_Config::get_config();

		$this->assertSame( 'Grace Community', $config['name'] );
		$this->assertSame( 'https://grace.example', $config['siteUrl'] );
		$this->assertSame( '', $config['logoUrl'] );
		$this->assertSame( '', $config['primaryColor'] );
		$this->assertSame( '', $config['secondaryColor'] );
		$this->assertSame( 'hwbl://church?url=' . rawurlencode( 'https://grace.example' ), $config['deepLink'] );
		$this->assertArrayHasKey( 'bibleReader', $config['features'] );
		$this->assertArrayHasKey( 'votd', $config['features'] );
		$this->assertArrayHasKey( 'memorize', $config['features'] );
		$this->assertArrayHasKey( 'askAudience', $config['features'] );
		$this->assertSame( 'logged_in', $config['features']['askAudience'] );
		$this->assertStringContainsString( '/app/join?url=', $config['joinUrl'] );
		$this->assertSame( 'https://grace.example/app/connect/', $config['connectUrl'] );
		$this->assertArrayHasKey( 'companionSelfSignup', $config['features'] );
		$this->assertFalse( $config['features']['companionSelfSignup'] );
		$this->assertArrayHasKey( 'communityShare', $config['features'] );
		$this->assertFalse( $config['features']['communityShare'] );
	}

	/**
	 * Brand colors surface from options.
	 */
	public function test_get_config_brand_colors() {
		update_option( 'hwbl_app_primary_color', '#1B3A4B' );
		update_option( 'hwbl_app_secondary_color', '#C9A227' );

		$config = HWBL_App_Config::get_config();
		$this->assertSame( '#1B3A4B', $config['primaryColor'] );
		$this->assertSame( '#C9A227', $config['secondaryColor'] );
	}

	/**
	 * REST wrapper returns WP_REST_Response.
	 */
	public function test_rest_get_config() {
		$response = HWBL_App_Config::rest_get_config();
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'Grace Community', $response->data['name'] );
	}
}
