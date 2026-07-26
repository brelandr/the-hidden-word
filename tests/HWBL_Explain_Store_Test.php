<?php
/**
 * Bible explain SQL store tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * In-memory backend for explain store tests.
 */
class HWBL_Explain_Store_Test_Backend {

	/**
	 * @var array<string, array<string, mixed>>
	 */
	public $rows = array();

	/**
	 * @var int
	 */
	private $next_id = 1;

	/**
	 * @param array<string, mixed> $keys Keys.
	 * @return string
	 */
	private function key( array $keys ) {
		return implode(
			'|',
			array(
				$keys['translation'],
				$keys['tradition'],
				$keys['scope'],
				(string) $keys['book_id'],
				(string) $keys['chapter'],
				(string) $keys['verse'],
			)
		);
	}

	/**
	 * @param array<string, mixed> $keys Keys.
	 * @return array<string, mixed>|null
	 */
	public function get_row( array $keys ) {
		$k = $this->key( $keys );
		return $this->rows[ $k ] ?? null;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public function save_row( array $row ) {
		$keys = array(
			'translation' => $row['translation'],
			'tradition'   => $row['tradition'],
			'scope'       => $row['scope'],
			'book_id'     => $row['book_id'],
			'chapter'     => $row['chapter'],
			'verse'       => $row['verse'],
		);
		$k = $this->key( $keys );
		if ( ! isset( $this->rows[ $k ] ) ) {
			$row['id'] = $this->next_id++;
		} else {
			$row['id'] = $this->rows[ $k ]['id'];
		}
		$this->rows[ $k ] = $row;
		return $row;
	}

	/**
	 * @param string             $translation Translation.
	 * @param string             $tradition   Tradition.
	 * @param array<int, string> $scopes      Scopes.
	 * @return int
	 */
	public function count_for_keys( $translation, $tradition, array $scopes = array() ) {
		$n = 0;
		foreach ( $this->rows as $row ) {
			if ( $row['translation'] !== $translation || $row['tradition'] !== $tradition ) {
				continue;
			}
			if ( $scopes && ! in_array( $row['scope'], $scopes, true ) ) {
				continue;
			}
			++$n;
		}
		return $n;
	}

	/**
	 * @param string             $translation Translation.
	 * @param string             $tradition   Tradition.
	 * @param array<int, string> $scopes      Scopes.
	 * @param int                $offset      Offset.
	 * @param int                $limit       Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_for_keys( $translation, $tradition, array $scopes, $offset, $limit ) {
		$out = array();
		foreach ( $this->rows as $row ) {
			if ( $row['translation'] !== $translation || $row['tradition'] !== $tradition ) {
				continue;
			}
			if ( $scopes && ! in_array( $row['scope'], $scopes, true ) ) {
				continue;
			}
			$out[] = $row;
		}
		return array_slice( $out, $offset, $limit );
	}

	/**
	 * @param string             $translation Translation.
	 * @param string             $tradition   Tradition.
	 * @param array<int, string> $scopes      Scopes.
	 * @param int                $limit       Limit.
	 * @return array{deleted:int,remaining:int}
	 */
	public function delete_for_keys( $translation, $tradition, array $scopes, $limit ) {
		$deleted = 0;
		foreach ( array_keys( $this->rows ) as $k ) {
			if ( $deleted >= $limit ) {
				break;
			}
			$row = $this->rows[ $k ];
			if ( $row['translation'] !== $translation || $row['tradition'] !== $tradition ) {
				continue;
			}
			if ( $scopes && ! in_array( $row['scope'], $scopes, true ) ) {
				continue;
			}
			unset( $this->rows[ $k ] );
			++$deleted;
		}
		return array(
			'deleted'   => $deleted,
			'remaining' => $this->count_for_keys( $translation, $tradition, $scopes ),
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function list_translation_slugs() {
		$slugs = array();
		foreach ( $this->rows as $row ) {
			$slugs[ $row['translation'] ] = true;
		}
		$out = array_keys( $slugs );
		sort( $out );
		return $out;
	}
}

/**
 * Class HWBL_Explain_Store_Test
 */
class HWBL_Explain_Store_Test extends TestCase {

	/**
	 * Load store.
	 */
	public static function setUpBeforeClass(): void {
		if ( ! defined( 'THW_PREMIUM_DIR' ) ) {
			define( 'THW_PREMIUM_DIR', HWBL_PLUGIN_DIR . 'premium/' );
		}
		require_once HWBL_PLUGIN_DIR . 'premium/includes/thw-premium-functions.php';
		require_once HWBL_PLUGIN_DIR . 'premium/includes/class-bible-reader-explain-store.php';
	}

	/**
	 * Reset backend.
	 */
	protected function tearDown(): void {
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = null;
		HWBL_Test_Options::$options                           = array();
	}

	/**
	 * Save + find round trip via table backend.
	 */
	public function test_save_and_find_from_table() {
		$backend = new HWBL_Explain_Store_Test_Backend();
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = $backend;

		$html = str_repeat( 'This is a pastoral explanation of the passage. ', 3 );
		$saved = THW_Premium_Bible_Reader_Explain_Store::save(
			array(
				'book_id'     => 43,
				'chapter'     => 3,
				'verse'       => 16,
				'translation' => 'bsb',
				'tradition'   => 'nondenom',
				'scope'       => 'verse',
				'reference'   => 'John 3:16',
			),
			$html,
			false
		);

		$this->assertIsArray( $saved );
		$this->assertSame( 43, $saved['book_id'] );
		$this->assertTrue( THW_Premium_Bible_Reader_Explain_Store::has_usable_explanation( $saved ) );

		$found = THW_Premium_Bible_Reader_Explain_Store::find(
			array(
				'book_id'     => 43,
				'chapter'     => 3,
				'verse'       => 16,
				'translation' => 'bsb',
				'tradition'   => 'nondenom',
				'scope'       => 'verse',
			)
		);
		$this->assertIsArray( $found );
		$this->assertSame( 'John 3:16', $found['reference'] );
		$this->assertStringContainsString( 'pastoral explanation', $found['html'] );
	}

	/**
	 * Import pack row skips duplicates.
	 */
	public function test_import_pack_row_skips_existing() {
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = new HWBL_Explain_Store_Test_Backend();
		$html = str_repeat( 'Imported explanation body with enough length. ', 3 );

		$first = THW_Premium_Bible_Reader_Explain_Store::import_pack_row(
			'kjv',
			'general',
			array(
				'book_id'   => 1,
				'chapter'   => 1,
				'verse'     => 1,
				'scope'     => 'verse',
				'reference' => 'Genesis 1:1',
				'html'      => $html,
			)
		);
		$this->assertTrue( $first['ok'] );
		$this->assertFalse( $first['skipped'] );

		$second = THW_Premium_Bible_Reader_Explain_Store::import_pack_row(
			'kjv',
			'general',
			array(
				'book_id'   => 1,
				'chapter'   => 1,
				'verse'     => 1,
				'scope'     => 'verse',
				'reference' => 'Genesis 1:1',
				'html'      => $html,
			)
		);
		$this->assertTrue( $second['ok'] );
		$this->assertTrue( $second['skipped'] );
	}

	/**
	 * Base explain + rare tradition override resolution.
	 */
	public function test_resolve_for_display_base_and_override() {
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = new HWBL_Explain_Store_Test_Backend();
		$html_base = str_repeat( 'Shared base explanation content for testing. ', 3 );
		$html_over = str_repeat( 'Baptist override explanation content for testing. ', 3 );

		THW_Premium_Bible_Reader_Explain_Store::save(
			array(
				'book_id'     => 40,
				'chapter'     => 28,
				'verse'       => 19,
				'translation' => 'kjv',
				'tradition'   => 'base',
				'scope'       => 'verse',
				'reference'   => 'Matthew 28:19',
			),
			$html_base
		);
		THW_Premium_Bible_Reader_Explain_Store::save(
			array(
				'book_id'     => 40,
				'chapter'     => 28,
				'verse'       => 19,
				'translation' => 'kjv',
				'tradition'   => 'baptist',
				'scope'       => 'verse',
				'reference'   => 'Matthew 28:19',
			),
			$html_over
		);

		$keys = array(
			'book_id'     => 40,
			'chapter'     => 28,
			'verse'       => 19,
			'translation' => 'kjv',
			'scope'       => 'verse',
			'tradition'   => 'baptist',
		);

		$resolved = THW_Premium_Bible_Reader_Explain_Store::resolve_for_display( $keys );
		$this->assertTrue( $resolved['has_tradition_diff'] );
		$this->assertFalse( $resolved['needs_override_check'] );
		$this->assertSame( 'base', $resolved['base']['tradition'] );
		$this->assertSame( 'baptist', $resolved['override']['tradition'] );
		// Tradition-first: primary content is the override.
		$this->assertSame( 'baptist', $resolved['content_row']['tradition'] );

		$nondenom = THW_Premium_Bible_Reader_Explain_Store::resolve_for_display(
			array_merge( $keys, array( 'tradition' => 'nondenom' ) )
		);
		$this->assertFalse( $nondenom['has_tradition_diff'] );
		$this->assertSame( 'base', $nondenom['content_row']['tradition'] );
	}

	/**
	 * Same-as-base marker skips future override checks and shows shared base.
	 */
	public function test_same_as_base_marker_skips_override_check() {
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = new HWBL_Explain_Store_Test_Backend();
		$html_base = str_repeat( 'Shared base explanation content for testing. ', 3 );

		THW_Premium_Bible_Reader_Explain_Store::save(
			array(
				'book_id'     => 1,
				'chapter'     => 1,
				'verse'       => 1,
				'translation' => 'kjv',
				'tradition'   => 'base',
				'scope'       => 'verse',
				'reference'   => 'Genesis 1:1',
			),
			$html_base
		);

		$keys = array(
			'book_id'     => 1,
			'chapter'     => 1,
			'verse'       => 1,
			'translation' => 'kjv',
			'scope'       => 'verse',
			'tradition'   => 'baptist',
			'reference'   => 'Genesis 1:1',
		);

		$pending = THW_Premium_Bible_Reader_Explain_Store::resolve_for_display( $keys );
		$this->assertTrue( $pending['needs_override_check'] );
		$this->assertSame( 'base', $pending['content_row']['tradition'] );

		THW_Premium_Bible_Reader_Explain_Store::save_same_as_base( $keys );
		$this->assertSame( 'same_as_base', THW_Premium_Bible_Reader_Explain_Store::get_tradition_override_state( $keys ) );

		$resolved = THW_Premium_Bible_Reader_Explain_Store::resolve_for_display( $keys );
		$this->assertFalse( $resolved['needs_override_check'] );
		$this->assertTrue( $resolved['same_as_base'] );
		$this->assertFalse( $resolved['has_tradition_diff'] );
		$this->assertSame( 'base', $resolved['content_row']['tradition'] );
	}

	/**
	 * Export pack row shape.
	 */
	public function test_export_pack_row() {
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = new HWBL_Explain_Store_Test_Backend();
		$html = str_repeat( 'Exported explanation content for testing packs. ', 3 );
		$saved = THW_Premium_Bible_Reader_Explain_Store::save(
			array(
				'book_id'     => 19,
				'chapter'     => 23,
				'verse'       => 1,
				'translation' => 'web',
				'tradition'   => 'general',
				'scope'       => 'verse',
				'reference'   => 'Psalm 23:1',
			),
			$html,
			true
		);

		$pack = THW_Premium_Bible_Reader_Explain_Store::export_pack_row( $saved );
		$this->assertIsArray( $pack );
		$this->assertSame( 19, $pack['book_id'] );
		$this->assertTrue( $pack['flagged'] );
		$this->assertArrayHasKey( 'html', $pack );
	}

	/**
	 * Delete and count helpers.
	 */
	public function test_count_and_delete() {
		$backend = new HWBL_Explain_Store_Test_Backend();
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = $backend;
		$html = str_repeat( 'Count and delete explanation body text here. ', 3 );

		foreach ( array( 1, 2, 3 ) as $verse ) {
			THW_Premium_Bible_Reader_Explain_Store::save(
				array(
					'book_id'     => 1,
					'chapter'     => 1,
					'verse'       => $verse,
					'translation' => 'bsb',
					'tradition'   => 'nondenom',
					'scope'       => 'verse',
					'reference'   => 'Genesis 1:' . $verse,
				),
				$html,
				false
			);
		}

		$this->assertSame( 3, THW_Premium_Bible_Reader_Explain_Store::count_for_keys( 'bsb', 'nondenom', array( 'verse' ) ) );
		$result = THW_Premium_Bible_Reader_Explain_Store::delete_for_keys( 'bsb', 'nondenom', array( 'verse' ), 2 );
		$this->assertSame( 2, $result['deleted'] );
		$this->assertSame( 1, $result['remaining'] );
	}
}
