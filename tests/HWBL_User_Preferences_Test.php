<?php
/**
 * User preferences tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_User_Preferences_Test
 */
class HWBL_User_Preferences_Test extends TestCase {

	/**
	 * Reset options between tests.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options = array();
	}

	/**
	 * Resolve uses the site default when it is in the available map.
	 */
	public function test_resolve_translation_uses_available_map() {
		update_option( 'hwbl_active_translation', 'kjv' );

		$available = array(
			'kjv' => 'KJV',
			'niv' => 'NIV',
		);

		$resolved = HWBL_User_Preferences::resolve_translation( $available, 0 );
		$this->assertSame( 'kjv', $resolved );
	}

	/**
	 * Meta key is stable.
	 */
	public function test_translation_meta_key() {
		$this->assertSame( '_hwbl_preferred_translation', HWBL_User_Preferences::translation_meta_key() );
	}
}
