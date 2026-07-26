<?php
/**
 * Local Bible import mapping and reader preference tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

use PHPUnit\Framework\TestCase;

/**
 * Class HWBL_Local_Bible_Test
 */
class HWBL_Local_Bible_Test extends TestCase {

	/**
	 * Load local Bible classes.
	 */
	public static function setUpBeforeClass(): void {
		require_once HWBL_PLUGIN_DIR . 'includes/class-http-utils.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-local-bible-store.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-local-bible-importer.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-local-bible-provider.php';
		require_once HWBL_PLUGIN_DIR . 'includes/class-bible-reader.php';
	}

	/**
	 * Reset options / test backend between tests.
	 */
	protected function tearDown(): void {
		HWBL_Test_Options::$options           = array();
		HWBL_Local_Bible_Store::$test_backend = null;
	}

	/**
	 * SuperSearch JSON maps book numbers and strips paragraph markers.
	 */
	public function test_parse_supersearch_json_maps_verses() {
		$path   = HWBL_PLUGIN_DIR . 'tests/fixtures/local-bible-supersearch-sample.json';
		$verses = HWBL_Local_Bible_Importer::parse_supersearch_json( $path, 'kjv' );

		$this->assertIsArray( $verses );
		$this->assertCount( 3, $verses );
		$this->assertSame( 1, $verses[0]['book_id'] );
		$this->assertSame( 1, $verses[0]['chapter'] );
		$this->assertSame( 1, $verses[0]['verse'] );
		$this->assertStringStartsWith( 'In the beginning', $verses[0]['text'] );
		$this->assertStringNotContainsString( '¶', $verses[0]['text'] );
		$this->assertSame( 43, $verses[1]['book_id'] );
		$this->assertSame( 49, $verses[2]['book_id'] );
	}

	/**
	 * SuperSearch row can resolve book from name when number missing.
	 */
	public function test_map_supersearch_row_uses_book_name_fallback() {
		$mapped = HWBL_Local_Bible_Importer::map_supersearch_row(
			array(
				'book_name' => 'Romans',
				'book'      => 0,
				'chapter'   => 8,
				'verse'     => 28,
				'text'      => 'And we know that all things work together for good…',
			)
		);

		$this->assertNotNull( $mapped );
		$this->assertSame( 45, $mapped['book_id'] );
		$this->assertSame( 8, $mapped['chapter'] );
		$this->assertSame( 28, $mapped['verse'] );
	}

	/**
	 * BSB TXT lines map to book IDs (including Psalm singular).
	 */
	public function test_parse_bsb_txt_maps_verses() {
		$path   = HWBL_PLUGIN_DIR . 'tests/fixtures/local-bible-bsb-sample.txt';
		$verses = HWBL_Local_Bible_Importer::parse_bsb_txt( $path );

		$this->assertIsArray( $verses );
		$this->assertCount( 5, $verses );
		$this->assertSame( 1, $verses[0]['book_id'] );
		$this->assertStringContainsString( 'heavens and the earth', $verses[0]['text'] );
		$this->assertSame( 43, $verses[2]['book_id'] );
		$this->assertSame( 49, $verses[3]['book_id'] );
		$this->assertSame( 19, $verses[4]['book_id'] );
		$this->assertSame( 23, $verses[4]['chapter'] );
	}

	/**
	 * Catalog lists the public-domain starter + recommended pack.
	 */
	public function test_catalog_includes_starter_pack() {
		$catalog = HWBL_Local_Bible_Store::get_catalog();
		foreach ( array(
			'kjv', 'asv', 'web', 'bsb', 'bbe', 'ylt', 'dby', 'gnv', 'lsv', 'dra', 'cpdv',
			'jps', 'brenton', 'webo', 'erv',
			'rv1909', 'se1865', 'torresamat', 'spablm', 'vulgate', 'crampon', 'segond', 'luther1912', 'luther1545',
			'cuvs', 'cuvt', 'almeida', 'synodal', 'diodati',
			'wlc', 'lxx', 'tr', 'byz',
		) as $slug ) {
			$this->assertArrayHasKey( $slug, $catalog );
			$this->assertNotEmpty( $catalog[ $slug ]['language'] );
		}
		$this->assertSame( 'theword_ont', $catalog['torresamat']['source'] );
		$this->assertSame( 'es', $catalog['torresamat']['language'] );
		$this->assertSame( 'ebible_usfx', $catalog['spablm']['source'] );
		$this->assertSame( 'spablm', $catalog['spablm']['ebible'] );
		$this->assertSame( 'scrollmapper', $catalog['luther1545']['source'] );
		$this->assertSame( 'de', $catalog['luther1545']['language'] );
		$this->assertSame( 'scrollmapper', $catalog['bbe']['source'] );
		$this->assertSame( 'scrollmapper', $catalog['ylt']['source'] );
		$this->assertSame( 'scrollmapper', $catalog['dby']['source'] );
		$this->assertSame( 'scrollmapper', $catalog['cpdv']['source'] );
		$this->assertSame( 'biblesupersearch', $catalog['gnv']['source'] );
		$this->assertSame( 'ebible_usfx', $catalog['lsv']['source'] );
		$this->assertSame( 'ebible_usfx', $catalog['dra']['source'] );
		$this->assertSame( 'engDRA', $catalog['dra']['ebible'] );
		$this->assertSame( 'engjps', $catalog['jps']['ebible'] );
		$this->assertSame( 'eng-Brenton', $catalog['brenton']['ebible'] );
		$this->assertSame( 'engwebu', $catalog['webo']['ebible'] );
		$this->assertSame( 'eng-rv', $catalog['erv']['ebible'] );
		$this->assertSame( 'es', $catalog['rv1909']['language'] );
		$this->assertSame( 'fr', $catalog['crampon']['language'] );
		$this->assertSame( 'usfx', $catalog['almeida']['source'] );
		$this->assertSame( 'grc', $catalog['lxx']['language'] );

		$languages = HWBL_Local_Bible_Store::get_languages();
		$this->assertArrayHasKey( 'en', $languages );
		$this->assertArrayHasKey( 'es', $languages );
		$this->assertArrayHasKey( 'zh', $languages );
		$this->assertArrayHasKey( 'grc', $languages );
	}

	/**
	 * Scrollmapper nested JSON maps book names to IDs.
	 */
	public function test_parse_scrollmapper_json_maps_verses() {
		$path   = HWBL_PLUGIN_DIR . 'tests/fixtures/local-bible-scrollmapper-sample.json';
		$verses = HWBL_Local_Bible_Importer::parse_scrollmapper_json( $path );

		$this->assertIsArray( $verses );
		$this->assertCount( 4, $verses );
		$this->assertSame( 1, $verses[0]['book_id'] );
		$this->assertSame( 1, $verses[0]['chapter'] );
		$this->assertSame( 1, $verses[0]['verse'] );
		$this->assertStringContainsString( 'heaven and the earth', $verses[0]['text'] );
		$this->assertSame( 43, $verses[2]['book_id'] );
		$this->assertSame( 45, $verses[3]['book_id'] );
	}

	/**
	 * Download URLs resolve per catalog source.
	 */
	public function test_download_url_for_scrollmapper_and_supersearch() {
		$bbe  = HWBL_Local_Bible_Importer::download_url_for( 'bbe' );
		$kjv  = HWBL_Local_Bible_Importer::download_url_for( 'kjv' );
		$bsb  = HWBL_Local_Bible_Importer::download_url_for( 'bsb' );
		$gnv  = HWBL_Local_Bible_Importer::download_url_for( 'gnv' );
		$lsv  = HWBL_Local_Bible_Importer::download_url_for( 'lsv' );
		$dra     = HWBL_Local_Bible_Importer::download_url_for( 'dra' );
		$cpdv    = HWBL_Local_Bible_Importer::download_url_for( 'cpdv' );
		$jps     = HWBL_Local_Bible_Importer::download_url_for( 'jps' );
		$brenton = HWBL_Local_Bible_Importer::download_url_for( 'brenton' );
		$webo    = HWBL_Local_Bible_Importer::download_url_for( 'webo' );
		$erv     = HWBL_Local_Bible_Importer::download_url_for( 'erv' );
		$rv1909  = HWBL_Local_Bible_Importer::download_url_for( 'rv1909' );
		$almeida = HWBL_Local_Bible_Importer::download_url_for( 'almeida' );
		$crampon    = HWBL_Local_Bible_Importer::download_url_for( 'crampon' );
		$luther1545 = HWBL_Local_Bible_Importer::download_url_for( 'luther1545' );
		$torres     = HWBL_Local_Bible_Importer::download_url_for( 'torresamat' );
		$spablm     = HWBL_Local_Bible_Importer::download_url_for( 'spablm' );

		$this->assertStringContainsString( 'scrollmapper/bible_databases', $bbe );
		$this->assertStringContainsString( 'BBE.json', $bbe );
		$this->assertStringContainsString( 'biblesupersearch.com', $kjv );
		$this->assertStringContainsString( 'bible=kjv', $kjv );
		$this->assertStringContainsString( 'bereanbible.com', $bsb );
		$this->assertStringContainsString( 'bible=geneva', $gnv );
		$this->assertStringContainsString( 'englsv_usfx.zip', $lsv );
		$this->assertStringContainsString( 'engDRA_usfx.zip', $dra );
		$this->assertStringContainsString( 'CPDV.json', $cpdv );
		$this->assertStringContainsString( 'engjps_usfx.zip', $jps );
		$this->assertStringContainsString( 'eng-Brenton_usfx.zip', $brenton );
		$this->assertStringContainsString( 'engwebu_usfx.zip', $webo );
		$this->assertStringContainsString( 'eng-rv_usfx.zip', $erv );
		$this->assertStringContainsString( 'spaRV1909_usfx.zip', $rv1909 );
		$this->assertStringContainsString( 'por-almeida.usfx.xml', $almeida );
		$this->assertStringContainsString( 'FreCrampon.json', $crampon );
		$this->assertStringContainsString( 'GerBoLut.json', $luther1545 );
		$this->assertStringContainsString( 'theword-modules.com/download/3912', $torres );
		$this->assertStringContainsString( 'spablm_usfx.zip', $spablm );
	}

	/**
	 * theWord .ont maps sequential lines onto the Protestant verse map.
	 */
	public function test_parse_theword_ont_maps_verses() {
		$path   = HWBL_PLUGIN_DIR . 'tests/fixtures/local-bible-theword-sample.ont';
		$verses = HWBL_Local_Bible_Importer::parse_theword_ont( $path );

		$this->assertIsArray( $verses );
		$this->assertCount( 5, $verses );
		$this->assertSame( 1, $verses[0]['book_id'] );
		$this->assertSame( 1, $verses[0]['chapter'] );
		$this->assertSame( 1, $verses[0]['verse'] );
		$this->assertStringContainsString( 'principio', $verses[0]['text'] );
		$this->assertSame( 5, $verses[4]['verse'] );
		$this->assertStringContainsString( 'Día', $verses[4]['text'] );
	}

	/**
	 * eBible USFX sample maps books/chapters/verses.
	 */
	public function test_parse_usfx_xml_maps_verses() {
		$xml    = file_get_contents( HWBL_PLUGIN_DIR . 'tests/fixtures/local-bible-usfx-sample.xml' );
		$verses = HWBL_Local_Bible_Importer::parse_usfx_xml( $xml );

		$this->assertIsArray( $verses );
		$this->assertCount( 7, $verses );
		$this->assertSame( 1, $verses[0]['book_id'] );
		$this->assertSame( 1, $verses[0]['chapter'] );
		$this->assertSame( 1, $verses[0]['verse'] );
		$this->assertStringContainsString( 'beginning God created', $verses[0]['text'] );
		$this->assertSame( 2, $verses[2]['chapter'] );
		$this->assertSame( 43, $verses[3]['book_id'] );
		$this->assertSame( 16, $verses[3]['verse'] );
		$this->assertSame( 67, $verses[4]['book_id'] );
		$this->assertStringContainsString( 'Tobit', $verses[4]['text'] );
		$this->assertSame( 74, $verses[5]['book_id'] );
		$this->assertSame( 78, $verses[6]['book_id'] );
	}

	/**
	 * CPDV scrollmapper names (Roman numerals + deuterocanonical) map correctly.
	 */
	public function test_parse_cpdv_scrollmapper_maps_deuterocanonical() {
		$path   = HWBL_PLUGIN_DIR . 'tests/fixtures/local-bible-cpdv-sample.json';
		$verses = HWBL_Local_Bible_Importer::parse_scrollmapper_json( $path );

		$this->assertIsArray( $verses );
		$this->assertCount( 3, $verses );
		$this->assertSame( 9, $verses[0]['book_id'] );
		$this->assertSame( 67, $verses[1]['book_id'] );
		$this->assertSame( 66, $verses[2]['book_id'] );
	}

	/**
	 * Local provider is registered ahead of Hello AO.
	 */
	public function test_local_provider_registers_before_helloao() {
		$providers = array(
			'bundled' => new HWBL_Bundled_Provider(),
			'helloao' => new HWBL_HelloAO_Provider(),
		);
		$providers = HWBL_Local_Bible_Provider::register_provider( $providers );
		$keys      = array_keys( $providers );

		$this->assertSame( 'local', $keys[0] );
		$this->assertContains( 'helloao', $keys );
		$this->assertLessThan(
			array_search( 'helloao', $keys, true ),
			array_search( 'local', $keys, true )
		);
	}

	/**
	 * Bible reader prefers seeded local chapter payload over remote providers.
	 */
	public function test_get_chapter_prefers_local_when_installed() {
		HWBL_Local_Bible_Store::$test_backend = new HWBL_Local_Bible_Test_Backend(
			array( 'kjv' => true ),
			array(
				'kjv:49:6' => array(
					'verses'   => array(
						array(
							'number' => 13,
							'text'   => 'LOCAL Wherefore take unto you the whole armour of God.',
						),
					),
					'headings' => array(),
				),
			)
		);

		update_option( 'hwbl_helloao_enabled', true );
		update_option( 'hwbl_bible_reader_enabled', true );

		$chapter = HWBL_Bible_Reader::get_chapter( 49, 6, 'kjv' );
		$this->assertIsArray( $chapter );
		$this->assertNotEmpty( $chapter['verses'] );
		$this->assertStringContainsString( 'LOCAL', $chapter['verses'][0]['text'] );
	}
}

/**
 * In-memory backend for Local Bible store tests.
 */
class HWBL_Local_Bible_Test_Backend {

	/**
	 * @var array<string, bool>
	 */
	private $installed;

	/**
	 * @var array<string, array{verses:array,headings:array}>
	 */
	private $chapters;

	/**
	 * @param array<string, bool>                              $installed Installed slugs.
	 * @param array<string, array{verses:array,headings:array}> $chapters  Chapter payloads.
	 */
	public function __construct( array $installed, array $chapters ) {
		$this->installed = $installed;
		$this->chapters  = $chapters;
	}

	/**
	 * @param string $slug Translation slug.
	 * @return bool
	 */
	public function is_installed( $slug ) {
		return ! empty( $this->installed[ sanitize_key( (string) $slug ) ] );
	}

	/**
	 * @param string $slug Translation slug.
	 * @return int[]
	 */
	public function list_book_ids( $slug ) {
		$slug  = sanitize_key( (string) $slug );
		$books = array();
		foreach ( array_keys( $this->chapters ) as $key ) {
			$parts = explode( ':', (string) $key );
			if ( 3 !== count( $parts ) || $parts[0] !== $slug ) {
				continue;
			}
			$books[ (int) $parts[1] ] = true;
		}
		$ids = array_map( 'intval', array_keys( $books ) );
		sort( $ids );
		return $ids;
	}

	/**
	 * @param string $slug    Translation slug.
	 * @param int    $book_id Book ID.
	 * @return int[]
	 */
	public function list_chapter_numbers( $slug, $book_id ) {
		$slug    = sanitize_key( (string) $slug );
		$book_id = (int) $book_id;
		$out     = array();
		foreach ( array_keys( $this->chapters ) as $key ) {
			$parts = explode( ':', (string) $key );
			if ( 3 !== count( $parts ) || $parts[0] !== $slug || (int) $parts[1] !== $book_id ) {
				continue;
			}
			$out[] = (int) $parts[2];
		}
		sort( $out );
		return $out;
	}

	/**
	 * @param string $slug    Translation slug.
	 * @param int    $book_id Book ID.
	 * @param int    $chapter Chapter.
	 * @return array{verses:array,headings:array}|null
	 */
	public function get_chapter( $slug, $book_id, $chapter ) {
		$key = sanitize_key( (string) $slug ) . ':' . (int) $book_id . ':' . (int) $chapter;
		return isset( $this->chapters[ $key ] ) ? $this->chapters[ $key ] : null;
	}
}
