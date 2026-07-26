<?php
/**
 * Enabled site traditions filter tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Enabled_Traditions_Test
 */
class HWBL_Enabled_Traditions_Test extends TestCase {

	/**
	 * Load helpers.
	 */
	public static function setUpBeforeClass(): void {
		if ( ! defined( 'THW_PREMIUM_DIR' ) ) {
			define( 'THW_PREMIUM_DIR', HWBL_PLUGIN_DIR . 'premium/' );
		}
		require_once HWBL_PLUGIN_DIR . 'premium/includes/thw-premium-functions.php';
	}

	/**
	 * Reset options.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options = array();
	}

	/**
	 * Empty option means all traditions enabled.
	 */
	public function test_empty_option_enables_all() {
		$all     = array_keys( thw_premium_ai_explain_rule_presets() );
		$enabled = thw_premium_get_enabled_tradition_slugs();
		sort( $all );
		$sorted = $enabled;
		sort( $sorted );
		$this->assertSame( $all, $sorted );
	}

	/**
	 * Stored subset filters front-end choices.
	 */
	public function test_subset_filters_choices_and_hides_single() {
		HWBL_Test_Options::$options['thw_ai_allow_user_tradition'] = 1;
		HWBL_Test_Options::$options['thw_ai_enabled_traditions']   = array( 'nondenom', 'baptist' );
		HWBL_Test_Options::$options['thw_ai_explain_rules_preset'] = 'nondenom';
		HWBL_Test_Options::$options['thw_ai_explain_rules']        = thw_premium_default_ai_explain_rules();

		$choices = thw_premium_get_tradition_preset_choices( true );
		$this->assertArrayHasKey( 'nondenom', $choices );
		$this->assertArrayHasKey( 'baptist', $choices );
		$this->assertArrayNotHasKey( 'catholic', $choices );
		$this->assertTrue( thw_premium_show_tradition_select() );

		HWBL_Test_Options::$options['thw_ai_enabled_traditions'] = array( 'nondenom' );
		$one = thw_premium_get_tradition_preset_choices( true );
		$this->assertCount( 1, $one );
		$this->assertFalse( thw_premium_show_tradition_select() );
		$this->assertSame( '', thw_premium_render_tradition_select() );
	}

	/**
	 * Sanitize collapses full selection to empty (= all).
	 */
	public function test_sanitize_collapses_all_selected() {
		$all = array_keys( thw_premium_ai_explain_rule_presets() );
		$this->assertSame( array(), thw_premium_sanitize_enabled_traditions( $all ) );
		$this->assertSame( array( 'baptist', 'nondenom' ), thw_premium_sanitize_enabled_traditions( array( 'baptist', 'nondenom', 'nope' ) ) );
	}

	/**
	 * Disabled tradition cannot be set as user preference.
	 */
	public function test_sanitize_requires_enabled() {
		HWBL_Test_Options::$options['thw_ai_enabled_traditions'] = array( 'nondenom' );
		$this->assertSame( 'nondenom', thw_premium_sanitize_tradition_preset( 'nondenom', true ) );
		$this->assertSame( '', thw_premium_sanitize_tradition_preset( 'baptist', true ) );
		$this->assertSame( 'baptist', thw_premium_sanitize_tradition_preset( 'baptist', false ) );
	}
}
