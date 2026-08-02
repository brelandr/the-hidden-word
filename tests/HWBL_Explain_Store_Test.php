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

	/**
	 * In-memory verse catalog for missing-passage tests: translation => list of [book, chapter, verse].
	 *
	 * @var array<string, array<int, array{0:int,1:int,2:int}>>
	 */
	public $bible_verses = array();

	/**
	 * @param string $translation Translation.
	 * @param string $tradition   Tradition.
	 * @param string $scope       Scope.
	 * @return int
	 */
	public function count_missing_passages( $translation, $tradition, $scope = 'verse' ) {
		return count( $this->list_missing_passages( $translation, $tradition, $scope, 10000, 0 ) );
	}

	/**
	 * @param string $translation Translation.
	 * @param string $tradition   Tradition.
	 * @param string $scope       Scope.
	 * @param int    $limit       Limit.
	 * @param int    $offset      Offset.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_missing_passages( $translation, $tradition, $scope = 'verse', $limit = 50, $offset = 0 ) {
		$verses = $this->bible_verses[ $translation ] ?? array();
		$out    = array();
		if ( 'chapter' === $scope ) {
			$seen = array();
			foreach ( $verses as $ref ) {
				$key = $ref[0] . ':' . $ref[1];
				if ( isset( $seen[ $key ] ) ) {
					continue;
				}
				$seen[ $key ] = true;
				$found        = false;
				foreach ( $this->rows as $row ) {
					if (
						$row['translation'] === $translation
						&& $row['tradition'] === $tradition
						&& $row['scope'] === 'chapter'
						&& (int) $row['book_id'] === (int) $ref[0]
						&& (int) $row['chapter'] === (int) $ref[1]
					) {
						$found = true;
						break;
					}
				}
				if ( ! $found ) {
					$out[] = array(
						'book_id' => (int) $ref[0],
						'chapter' => (int) $ref[1],
						'verse'   => 0,
					);
				}
			}
		} else {
			foreach ( $verses as $ref ) {
				$found = false;
				foreach ( $this->rows as $row ) {
					if (
						$row['translation'] === $translation
						&& $row['tradition'] === $tradition
						&& $row['scope'] === 'verse'
						&& (int) $row['book_id'] === (int) $ref[0]
						&& (int) $row['chapter'] === (int) $ref[1]
						&& (int) $row['verse'] === (int) $ref[2]
					) {
						$found = true;
						break;
					}
				}
				if ( ! $found ) {
					$out[] = array(
						'book_id' => (int) $ref[0],
						'chapter' => (int) $ref[1],
						'verse'   => (int) $ref[2],
					);
				}
			}
		}
		return array_slice( $out, $offset, $limit );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function list_inventory_counts() {
		$groups = array();
		foreach ( $this->rows as $row ) {
			$key = $row['translation'] . '|' . $row['tradition'] . '|' . $row['scope'];
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array(
					'translation'  => $row['translation'],
					'tradition'    => $row['tradition'],
					'scope'        => $row['scope'],
					'total'        => 0,
					'overrides'    => 0,
					'same_as_base' => 0,
				);
			}
			++$groups[ $key ]['total'];
			$html = (string) ( $row['html'] ?? '' );
			if ( false !== strpos( $html, 'HWBL_NO_TRADITION_DIFF' ) ) {
				++$groups[ $key ]['same_as_base'];
			} else {
				++$groups[ $key ]['overrides'];
			}
		}
		$out = array_values( $groups );
		usort(
			$out,
			static function ( $a, $b ) {
				return strcmp(
					$a['translation'] . '|' . $a['tradition'] . '|' . $a['scope'],
					$b['translation'] . '|' . $b['tradition'] . '|' . $b['scope']
				);
			}
		);
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

	/**
	 * Inventory groups by translation/tradition/scope with override splits.
	 */
	public function test_list_inventory_counts() {
		$backend = new HWBL_Explain_Store_Test_Backend();
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = $backend;
		$html = str_repeat( 'Inventory explanation body text for counting rows. ', 3 );

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
			$html,
			false
		);
		THW_Premium_Bible_Reader_Explain_Store::save(
			array(
				'book_id'     => 1,
				'chapter'     => 1,
				'verse'       => 2,
				'translation' => 'kjv',
				'tradition'   => 'catholic',
				'scope'       => 'verse',
				'reference'   => 'Genesis 1:2',
			),
			$html,
			false
		);
		THW_Premium_Bible_Reader_Explain_Store::save_same_as_base(
			array(
				'book_id'     => 1,
				'chapter'     => 1,
				'verse'       => 3,
				'translation' => 'kjv',
				'tradition'   => 'catholic',
				'scope'       => 'verse',
				'reference'   => 'Genesis 1:3',
			)
		);

		$inv = THW_Premium_Bible_Reader_Explain_Store::list_inventory_counts();
		$this->assertCount( 2, $inv );
		$this->assertSame( 'kjv', $inv[0]['translation'] );
		$this->assertSame( 'base', $inv[0]['tradition'] );
		$this->assertSame( 1, $inv[0]['total'] );
		$this->assertSame( 1, $inv[0]['overrides'] );
		$this->assertSame( 0, $inv[0]['same_as_base'] );
		$this->assertSame( 'catholic', $inv[1]['tradition'] );
		$this->assertSame( 2, $inv[1]['total'] );
		$this->assertSame( 1, $inv[1]['overrides'] );
		$this->assertSame( 1, $inv[1]['same_as_base'] );
	}

	/**
	 * Missing passages are Local Bible refs without an explain row.
	 */
	public function test_list_missing_passages() {
		$backend = new HWBL_Explain_Store_Test_Backend();
		$backend->bible_verses['bsb'] = array(
			array( 1, 1, 1 ),
			array( 1, 1, 2 ),
			array( 1, 1, 3 ),
		);
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = $backend;
		$html = str_repeat( 'Missing passage test explanation body text. ', 3 );

		THW_Premium_Bible_Reader_Explain_Store::save(
			array(
				'book_id'     => 1,
				'chapter'     => 1,
				'verse'       => 1,
				'translation' => 'bsb',
				'tradition'   => 'base',
				'scope'       => 'verse',
				'reference'   => 'Genesis 1:1',
			),
			$html,
			false
		);

		$this->assertSame( 2, THW_Premium_Bible_Reader_Explain_Store::count_missing_passages( 'bsb', 'base', 'verse' ) );
		$missing = THW_Premium_Bible_Reader_Explain_Store::list_missing_passages( 'bsb', 'base', 'verse', 10, 0 );
		$this->assertCount( 2, $missing );
		$this->assertSame( 2, (int) $missing[0]['verse'] );
		$this->assertSame( 3, (int) $missing[1]['verse'] );
	}

	/**
	 * ensure_sql_row_from_item copies usable HTML under the requested keys.
	 */
	public function test_ensure_sql_row_from_item_copies_to_base() {
		$backend = new HWBL_Explain_Store_Test_Backend();
		THW_Premium_Bible_Reader_Explain_Store::$test_backend = $backend;
		$html = str_repeat( 'Legacy CPT style explanation body for migrate. ', 3 );

		$source = array(
			'id'          => 99,
			'translation' => 'bsb',
			'tradition'   => 'nondenom',
			'scope'       => 'verse',
			'book_id'     => 20,
			'chapter'     => 17,
			'verse'       => 19,
			'reference'   => 'Proverbs 17:19',
			'html'        => $html,
			'flagged'     => 0,
		);

		$keys = array(
			'translation' => 'bsb',
			'tradition'   => 'base',
			'scope'       => 'verse',
			'book_id'     => 20,
			'chapter'     => 17,
			'verse'       => 19,
		);

		$this->assertNull( THW_Premium_Bible_Reader_Explain_Store::get_row( $keys ) );
		$saved = THW_Premium_Bible_Reader_Explain_Store::ensure_sql_row_from_item( $keys, $source );
		$this->assertIsArray( $saved );
		$this->assertSame( 'base', $saved['tradition'] );
		$this->assertTrue( THW_Premium_Bible_Reader_Explain_Store::has_usable_explanation( $saved ) );
	}
}
