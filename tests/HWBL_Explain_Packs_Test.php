<?php
/**
 * Explain pack helper tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Explain_Packs_Test
 */
class HWBL_Explain_Packs_Test extends TestCase {

	/**
	 * Load pack class + helpers.
	 */
	public static function setUpBeforeClass(): void {
		if ( ! defined( 'THW_PREMIUM_DIR' ) ) {
			define( 'THW_PREMIUM_DIR', HWBL_PLUGIN_DIR . 'premium/' );
		}
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}
		require_once HWBL_PLUGIN_DIR . 'premium/includes/thw-premium-functions.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-explain-packs.php';
	}

	/**
	 * Reset options.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options = array();
	}

	/**
	 * Pack ids are stable and scope-sorted.
	 */
	public function test_pack_id_stable() {
		$id_a = THW_Premium_Explain_Packs::pack_id( 'bsb', 'nondenom', array( 'chapter', 'verse' ) );
		$id_b = THW_Premium_Explain_Packs::pack_id( 'bsb', 'nondenom', array( 'verse', 'chapter' ) );
		$this->assertSame( 'bsb-nondenom-chapter-verse', $id_a );
		$this->assertSame( $id_a, $id_b );
	}

	/**
	 * Sanitize maps both and requires keys.
	 */
	public function test_sanitize_pack_keys() {
		$err = THW_Premium_Explain_Packs::sanitize_pack_keys(
			array(
				'translation' => '',
				'tradition'   => 'general',
			)
		);
		$this->assertInstanceOf( WP_Error::class, $err );

		$ok = THW_Premium_Explain_Packs::sanitize_pack_keys(
			array(
				'translation' => 'kjv',
				'tradition'   => 'nondenom',
				'scopes'      => array( 'both' ),
			)
		);
		$this->assertIsArray( $ok );
		$this->assertSame( array( 'verse', 'chapter' ), $ok['scopes'] );
	}

	/**
	 * Catalog entry builder fills release template fields.
	 */
	public function test_catalog_entry_from_export() {
		$entry = THW_Premium_Explain_Packs::catalog_entry_from_export(
			array(
				'pack_id'     => 'web-general-verse',
				'translation' => 'web',
				'tradition'   => 'general',
				'scopes'      => array( 'verse' ),
				'version'     => '1.2.0',
				'written'     => 42,
				'bytes'       => 1000,
			),
			'https://example.com/web-general-verse-1.2.0.zip'
		);

		$this->assertSame( 'web-general-verse', $entry['id'] );
		$this->assertSame( 'web', $entry['translation'] );
		$this->assertSame( 'https://example.com/web-general-verse-1.2.0.zip', $entry['url'] );
		$this->assertSame( 42, $entry['count'] );
	}

	/**
	 * Bundled catalog loads.
	 */
	public function test_bundled_catalog_format() {
		$catalog = THW_Premium_Explain_Packs::get_catalog();
		$this->assertSame( 'hwbl_explain_pack_catalog_v1', $catalog['format'] );
		$this->assertIsArray( $catalog['packs'] );
	}

	/**
	 * Default catalog points at brelandr/hwbl-explain-packs.
	 */
	public function test_default_catalog_url() {
		$url = THW_Premium_Explain_Packs::default_catalog_url();
		$this->assertStringContainsString( 'brelandr/hwbl-explain-packs', $url );
		$this->assertStringContainsString( 'explain-packs-catalog.json', $url );
		$this->assertSame( $url, THW_Premium_Explain_Packs::catalog_url() );
	}

	/**
	 * Installed registry mark/unmark.
	 */
	public function test_installed_registry() {
		THW_Premium_Explain_Packs::mark_installed(
			'kjv-general-verse',
			array(
				'translation' => 'kjv',
				'tradition'   => 'general',
				'scopes'      => array( 'verse' ),
				'version'     => '1.0.0',
			)
		);
		$rows = THW_Premium_Explain_Packs::get_installed();
		$this->assertArrayHasKey( 'kjv-general-verse', $rows );
		$this->assertSame( 'kjv', $rows['kjv-general-verse']['translation'] );

		THW_Premium_Explain_Packs::unmark_installed( 'kjv-general-verse' );
		$this->assertArrayNotHasKey( 'kjv-general-verse', THW_Premium_Explain_Packs::get_installed() );
	}
}
