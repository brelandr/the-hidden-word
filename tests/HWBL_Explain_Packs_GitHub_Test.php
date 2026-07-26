<?php
/**
 * Hub-only GitHub publish gate + token encryption tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Explain_Packs_GitHub_Test
 */
class HWBL_Explain_Packs_GitHub_Test extends TestCase {

	/**
	 * Load classes.
	 */
	public static function setUpBeforeClass(): void {
		if ( ! defined( 'THW_PREMIUM_DIR' ) ) {
			define( 'THW_PREMIUM_DIR', HWBL_PLUGIN_DIR . 'premium/' );
		}
		require_once HWBL_PLUGIN_DIR . 'includes/class-church-network.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-explain-packs.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-explain-packs-github.php';
	}

	/**
	 * Reset.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options = array();
		$GLOBALS['hwbl_test_home']  = 'https://example.org';
	}

	/**
	 * Hub publisher only on thehiddenword.org.
	 */
	public function test_is_hub_publisher_gate() {
		$GLOBALS['hwbl_test_home'] = 'https://thehiddenword.org';
		$this->assertTrue( THW_Premium_Explain_Packs_GitHub::is_hub_publisher() );

		$GLOBALS['hwbl_test_home'] = 'https://grace.example';
		$this->assertFalse( THW_Premium_Explain_Packs_GitHub::is_hub_publisher() );
	}

	/**
	 * Token rejected off-hub; encrypt/decrypt round-trip on hub.
	 */
	public function test_token_hub_only_and_encrypt_roundtrip() {
		$GLOBALS['hwbl_test_home'] = 'https://grace.example';
		$err = THW_Premium_Explain_Packs_GitHub::set_token( 'ghp_test_token_value_1234567890' );
		$this->assertInstanceOf( WP_Error::class, $err );

		$GLOBALS['hwbl_test_home'] = 'https://thehiddenword.org';
		$ok = THW_Premium_Explain_Packs_GitHub::set_token( 'ghp_test_token_value_1234567890' );
		$this->assertTrue( $ok );
		$this->assertTrue( THW_Premium_Explain_Packs_GitHub::has_token() );
		$this->assertSame( 'ghp_test_token_value_1234567890', THW_Premium_Explain_Packs_GitHub::get_token() );

		$plain = 'secret-token-abc-1234567890';
		$enc   = THW_Premium_Explain_Packs_GitHub::encrypt( $plain );
		$this->assertNotSame( '', $enc );
		$this->assertNotSame( $plain, $enc );
		$this->assertSame( $plain, THW_Premium_Explain_Packs_GitHub::decrypt( $enc ) );

		THW_Premium_Explain_Packs_GitHub::set_token( '' );
		$this->assertFalse( THW_Premium_Explain_Packs_GitHub::has_token() );
	}

	/**
	 * Repo slug defaults to brelandr/hwbl-explain-packs.
	 */
	public function test_repo_slug() {
		$this->assertSame( 'brelandr/hwbl-explain-packs', THW_Premium_Explain_Packs_GitHub::repo_slug() );
	}
}
