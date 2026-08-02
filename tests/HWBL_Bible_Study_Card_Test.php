<?php
/**
 * Verse Study Card parser tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Bible_Study_Card_Test
 */
class HWBL_Bible_Study_Card_Test extends TestCase {

	/**
	 * Load class under test.
	 */
	public static function setUpBeforeClass(): void {
		if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
			define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
		}
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-bible-study-card.php';
	}

	/**
	 * Parses fenced JSON cards.
	 */
	public function test_parse_ai_card_from_fenced_json() {
		$raw = "```json\n" . wp_json_encode(
			array(
				'plainWords'      => 'God loved the world and gave his Son.',
				'context'         => 'Jesus speaks with Nicodemus about new birth.',
				'keywords'        => array(
					array(
						'term' => 'believes',
						'note' => 'Trust, not mere agreement.',
					),
				),
				'crossReferences' => array(
					array(
						'reference' => 'Romans 5:8',
						'why'       => 'God shows love while we were sinners.',
					),
				),
				'liveIt'          => 'Where do you need to trust God’s love today?',
			)
		) . "\n```";

		$parsed = THW_Premium_Bible_Study_Card::parse_ai_card( $raw );
		$this->assertIsArray( $parsed );
		$this->assertStringContainsString( 'God loved', $parsed['plainWords'] );
		$this->assertCount( 1, $parsed['keywords'] );
		$this->assertSame( 'Romans 5:8', $parsed['crossReferences'][0]['reference'] );
	}

	/**
	 * Rejects incomplete cards.
	 */
	public function test_parse_ai_card_requires_core_fields() {
		$this->assertNull(
			THW_Premium_Bible_Study_Card::parse_ai_card(
				wp_json_encode( array( 'plainWords' => 'Only one field' ) )
			)
		);
	}

	/**
	 * Cache key is stable.
	 */
	public function test_cache_key_stable() {
		$a = THW_Premium_Bible_Study_Card::cache_key( 43, 3, 16, 'niv', 'nondenom' );
		$b = THW_Premium_Bible_Study_Card::cache_key( 43, 3, 16, 'niv', 'nondenom' );
		$c = THW_Premium_Bible_Study_Card::cache_key( 43, 3, 16, 'esv', 'nondenom' );
		$this->assertSame( $a, $b );
		$this->assertNotSame( $a, $c );
	}
}
