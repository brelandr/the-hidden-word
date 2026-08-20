<?php
/**
 * Local Bible SQL store (schema + CRUD).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Local_Bible_Store
 */
class HWBL_Local_Bible_Store {

	const DB_VERSION     = '1.0.0';
	const DB_VERSION_KEY = 'hwbl_local_bible_db_version';

	/**
	 * Optional test backend (unit tests only).
	 *
	 * @var object|null
	 */
	public static $test_backend = null;

	/**
	 * Catalog of downloadable public-domain translations.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_catalog() {
		$en = array( 'language' => 'en', 'language_label' => 'English' );
		$es = array( 'language' => 'es', 'language_label' => 'Spanish' );
		$fr = array( 'language' => 'fr', 'language_label' => 'French' );
		$de = array( 'language' => 'de', 'language_label' => 'German' );
		$zh = array( 'language' => 'zh', 'language_label' => 'Chinese' );
		$pt = array( 'language' => 'pt', 'language_label' => 'Portuguese' );
		$ru = array( 'language' => 'ru', 'language_label' => 'Russian' );
		$it = array( 'language' => 'it', 'language_label' => 'Italian' );
		$la = array( 'language' => 'la', 'language_label' => 'Latin' );
		$he = array( 'language' => 'he', 'language_label' => 'Hebrew' );
		$gr = array( 'language' => 'grc', 'language_label' => 'Ancient Greek' );

		return array(
			// English.
			'kjv'       => $en + array(
				'slug'    => 'kjv',
				'label'   => 'King James Version (KJV)',
				'source'  => 'biblesupersearch',
				'license' => 'Public domain',
				'note'    => 'Authorized King James Version (1769). Freely redistributable.',
			),
			'asv'       => $en + array(
				'slug'    => 'asv',
				'label'   => 'American Standard Version (ASV)',
				'source'  => 'biblesupersearch',
				'license' => 'Public domain',
				'note'    => 'American Standard Version (1901). Freely redistributable.',
			),
			'web'       => $en + array(
				'slug'    => 'web',
				'label'   => 'World English Bible (WEB)',
				'source'  => 'biblesupersearch',
				'license' => 'Public domain',
				'note'    => 'World English Bible. Freely redistributable.',
			),
			'bsb'       => $en + array(
				'slug'    => 'bsb',
				'label'   => 'Berean Standard Bible (BSB)',
				'source'  => 'berean',
				'license' => 'Public domain',
				'note'    => 'Berean Standard Bible. Dedicated to the public domain by Berean Bible.',
			),
			'bbe'       => $en + array(
				'slug'    => 'bbe',
				'label'   => 'Bible in Basic English (BBE)',
				'source'  => 'scrollmapper',
				'license' => 'Public domain',
				'note'    => 'Bible in Basic English (1949/1964). Simpler vocabulary; good for ESL readers.',
			),
			'ylt'       => $en + array(
				'slug'    => 'ylt',
				'label'   => "Young's Literal Translation (YLT)",
				'source'  => 'scrollmapper',
				'license' => 'Public domain',
				'note'    => "Young's Literal Translation (1898). Useful for close study and comparison.",
			),
			'dby'       => $en + array(
				'slug'    => 'dby',
				'label'   => 'Darby Translation (DARBY)',
				'source'  => 'scrollmapper',
				'license' => 'Public domain',
				'note'    => 'John Nelson Darby Translation. Traditional public-domain alternate to KJV/ASV.',
			),
			'gnv'       => $en + array(
				'slug'    => 'gnv',
				'label'   => 'Geneva Bible 1599 (GNV)',
				'source'  => 'biblesupersearch',
				'license' => 'Public domain',
				'note'    => 'Geneva Bible (1599 / 1587 text). Historic Reformation-era English Bible.',
			),
			'lsv'       => $en + array(
				'slug'    => 'lsv',
				'label'   => 'Literal Standard Version (LSV)',
				'source'  => 'ebible_usfx',
				'license' => 'CC BY-SA 4.0',
				'note'    => 'Literal Standard Version (Covenant Press). Freely redistributable under Creative Commons Attribution-ShareAlike 4.0 with attribution.',
				'ebible'  => 'englsv',
			),
			'dra'       => $en + array(
				'slug'    => 'dra',
				'label'   => 'Douay–Rheims (Challoner / DRA)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Douay–Rheims Bible, Challoner revision (1899 American Edition). Traditional Catholic English Bible including deuterocanonical books. Source: eBible.org engDRA.',
				'ebible'  => 'engDRA',
			),
			'cpdv'      => $en + array(
				'slug'    => 'cpdv',
				'label'   => 'Catholic Public Domain Version (CPDV)',
				'source'  => 'scrollmapper',
				'license' => 'Public domain',
				'note'    => 'Catholic Public Domain Version (2009, Ronald L. Conte Jr.). Modern English from the Clementine Vulgate, explicitly released to the public domain. Includes deuterocanonical books.',
			),
			'jps'       => $en + array(
				'slug'    => 'jps',
				'label'   => 'JPS Tanakh 1917',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Jewish Publication Society Tanakh (1917). Hebrew Bible only (39 books); Jewish translation perspective. Source: eBible.org engjps.',
				'ebible'  => 'engjps',
			),
			'brenton'   => $en + array(
				'slug'    => 'brenton',
				'label'   => "Brenton's English Septuagint",
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => "Sir Lancelot C. L. Brenton’s English translation of the Greek Septuagint (1851). OT / deuterocanon focus used in Eastern Orthodox and early-church study. Source: eBible.org eng-Brenton.",
				'ebible'  => 'eng-Brenton',
			),
			'webo'      => $en + array(
				'slug'    => 'webo',
				'label'   => 'World English Bible + Deuterocanon (WEB‑DC)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'World English Bible Updated with deuterocanon/apocrypha (80+ books covering Orthodox-scope texts) in modern English. Public-domain WEB — not the copyrighted Orthodox Study Bible. Source: eBible.org engwebu.',
				'ebible'  => 'engwebu',
			),
			'erv'       => $en + array(
				'slug'    => 'erv',
				'label'   => 'English Revised Version (ERV / RV 1885)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'English Revised Version (NT 1881, OT 1885; Apocrypha 1895). British companion to the American Standard Version (1901). Source: eBible.org eng-rv.',
				'ebible'  => 'eng-rv',
			),

			// Spanish.
			'rv1909'    => $es + array(
				'slug'    => 'rv1909',
				'label'   => 'Reina-Valera 1909 (RV1909)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Classic Spanish Protestant Bible (Reina 1569 / Valera 1602 lineage), 1909 revision. Source: eBible.org spaRV1909.',
				'ebible'  => 'spaRV1909',
			),
			'se1865'    => $es + array(
				'slug'    => 'se1865',
				'label'   => 'Sagradas Escrituras / RV 1865',
				'source'  => 'scrollmapper',
				'license' => 'Public domain',
				'note'    => 'Historic Spanish Protestant text (often published as Sagradas Escrituras / Reina-Valera 1865). Public-domain module.',
			),
			'torresamat' => $es + array(
				'slug'         => 'torresamat',
				'label'        => 'Torres Amat (historic Spanish Catholic)',
				'source'       => 'theword_ont',
				'license'      => 'Public domain',
				'note'         => 'Félix Torres Amat Spanish Vulgate-based Catholic Bible (theWord .ont module; 66-book verse map). Deuterocanonical books are not separately keyed in this module. Source: theword-modules.com.',
				'download_url' => 'https://theword-modules.com/download/3912/',
			),
			'spablm'    => $es + array(
				'slug'    => 'spablm',
				'label'   => 'Santa Biblia libre para el mundo (SBLM)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Modern public-domain Spanish Bible including deuterocanonical books (Catholic-scope canon). Source: eBible.org spablm.',
				'ebible'  => 'spablm',
			),

			// Latin / French Catholic.
			'vulgate'   => $la + array(
				'slug'    => 'vulgate',
				'label'   => 'Clementine Latin Vulgate (1598)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Clementine Vulgate (1598), long-standard Latin Catholic text underlying Douay-Rheims and CPDV. Source: eBible.org latVUC.',
				'ebible'  => 'latVUC',
			),
			'crampon'   => $fr + array(
				'slug'    => 'crampon',
				'label'   => 'Bible de Crampon (1904)',
				'source'  => 'scrollmapper',
				'license' => 'Public domain',
				'note'    => 'Classic French Catholic translation by Augustin Crampon (1904). Includes deuterocanonical books.',
			),
			'segond'    => $fr + array(
				'slug'    => 'segond',
				'label'   => 'Louis Segond 1910',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Standard French Protestant Bible (Louis Segond 1910). Source: eBible.org fraLSG.',
				'ebible'  => 'fraLSG',
			),

			// Historic Protestant by language.
			'luther1912' => $de + array(
				'slug'    => 'luther1912',
				'label'   => 'Lutherbibel 1912',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'German Luther Bible, 1912 revision (public domain). Source: eBible.org deu1912.',
				'ebible'  => 'deu1912',
			),
			'luther1545' => $de + array(
				'slug'    => 'luther1545',
				'label'   => 'Lutherbibel 1545 (GerBoLut)',
				'source'  => 'scrollmapper',
				'license' => 'Public domain',
				'note'    => 'Martin Luther’s 1545 Last-Hand Bible (Bolsinger / GerBoLut module; modernized spelling). CrossWire SWORD lineage via scrollmapper bible_databases.',
			),
			'cuvs'      => $zh + array(
				'slug'    => 'cuvs',
				'label'   => 'Chinese Union Version (Simplified)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Chinese Union Version in simplified characters (CUVs). Source: eBible.org cmn-cu89s.',
				'ebible'  => 'cmn-cu89s',
			),
			'cuvt'      => $zh + array(
				'slug'    => 'cuvt',
				'label'   => 'Chinese Union Version (Traditional)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Chinese Union Version in traditional characters (CUVt). Source: eBible.org cmn-cu89t.',
				'ebible'  => 'cmn-cu89t',
			),
			'almeida'   => $pt + array(
				'slug'         => 'almeida',
				'label'        => 'Almeida (public-domain text)',
				'source'       => 'usfx',
				'license'      => 'Public domain',
				'note'         => 'João Ferreira de Almeida Portuguese Bible (historic public-domain text). Source: open-bibles por-almeida.usfx.xml.',
				'download_url' => 'https://raw.githubusercontent.com/dbonates/open-bibles/master/por-almeida.usfx.xml',
			),
			'synodal'   => $ru + array(
				'slug'    => 'synodal',
				'label'   => 'Russian Synodal Translation (1876)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Russian Synodal Bible. Source: eBible.org russyn.',
				'ebible'  => 'russyn',
			),
			'diodati'   => $it + array(
				'slug'    => 'diodati',
				'label'   => 'Diodati Bible (1885)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Italian Diodati Bible (1885 revision of Giovanni Diodati). Source: eBible.org ita1885.',
				'ebible'  => 'ita1885',
			),

			// Ancient source texts.
			'wlc'       => $he + array(
				'slug'    => 'wlc',
				'label'   => 'Westminster Leningrad Codex (WLC)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Hebrew Masoretic Old Testament (Westminster Leningrad Codex). Source: eBible.org hboWLC.',
				'ebible'  => 'hboWLC',
			),
			'lxx'       => $gr + array(
				'slug'    => 'lxx',
				'label'   => 'Greek Septuagint (LXX)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Ancient Greek Old Testament (Septuagint). Source: eBible.org grclxx.',
				'ebible'  => 'grclxx',
			),
			'tr'        => $gr + array(
				'slug'    => 'tr',
				'label'   => 'Greek NT — Textus Receptus',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Greek New Testament, Textus Receptus. Source: eBible.org grctr.',
				'ebible'  => 'grctr',
			),
			'byz'       => $gr + array(
				'slug'    => 'byz',
				'label'   => 'Greek NT — Byzantine / Patriarchal (1904)',
				'source'  => 'ebible_usfx',
				'license' => 'Public domain',
				'note'    => 'Byzantine Greek New Testament (1904 Patriarchal text). Source: eBible.org grcbyz.',
				'ebible'  => 'grcbyz',
			),
		);
	}

	/**
	 * Languages present in the import catalog (code => label).
	 *
	 * @return array<string, string>
	 */
	public static function get_languages() {
		$order = array( 'en', 'es', 'fr', 'de', 'zh', 'pt', 'ru', 'it', 'la', 'he', 'grc' );
		$found = array();
		foreach ( self::get_catalog() as $meta ) {
			$code  = sanitize_key( (string) ( $meta['language'] ?? 'en' ) );
			$label = (string) ( $meta['language_label'] ?? strtoupper( $code ) );
			if ( '' === $code ) {
				continue;
			}
			$found[ $code ] = $label;
		}

		$sorted = array();
		foreach ( $order as $code ) {
			if ( isset( $found[ $code ] ) ) {
				$sorted[ $code ] = $found[ $code ];
				unset( $found[ $code ] );
			}
		}
		foreach ( $found as $code => $label ) {
			$sorted[ $code ] = $label;
		}
		return $sorted;
	}

	/**
	 * Translations table name.
	 *
	 * @return string
	 */
	public static function translations_table() {
		global $wpdb;
		return $wpdb->prefix . 'hwbl_bible_translations';
	}

	/**
	 * Verses table name.
	 *
	 * @return string
	 */
	public static function verses_table() {
		global $wpdb;
		return $wpdb->prefix . 'hwbl_bible_verses';
	}

	/**
	 * Create or upgrade tables.
	 */
	public static function maybe_install_schema() {
		$installed = (string) get_option( self::DB_VERSION_KEY, '' );
		if ( version_compare( $installed, self::DB_VERSION, '>=' ) ) {
			return;
		}

		self::create_tables();
		update_option( self::DB_VERSION_KEY, self::DB_VERSION, false );
	}

	/**
	 * Run dbDelta for local Bible tables.
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset   = $wpdb->get_charset_collate();
		$trans     = self::translations_table();
		$verses    = self::verses_table();

		$sql_trans = "CREATE TABLE {$trans} (
			slug varchar(32) NOT NULL,
			label varchar(191) NOT NULL DEFAULT '',
			source varchar(64) NOT NULL DEFAULT '',
			license varchar(191) NOT NULL DEFAULT '',
			verse_count bigint(20) unsigned NOT NULL DEFAULT 0,
			installed_at datetime DEFAULT NULL,
			status varchar(32) NOT NULL DEFAULT 'not_installed',
			error_message text NULL,
			PRIMARY KEY  (slug),
			KEY status (status)
		) {$charset};";

		$sql_verses = "CREATE TABLE {$verses} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			translation varchar(32) NOT NULL DEFAULT '',
			book_id smallint(5) unsigned NOT NULL DEFAULT 0,
			chapter smallint(5) unsigned NOT NULL DEFAULT 0,
			verse smallint(5) unsigned NOT NULL DEFAULT 0,
			text text NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY translation_ref (translation,book_id,chapter,verse),
			KEY translation_chapter (translation,book_id,chapter)
		) {$charset};";

		dbDelta( $sql_trans );
		dbDelta( $sql_verses );

		self::maybe_add_fulltext_index();
	}

	/**
	 * Add FULLTEXT index when InnoDB supports it (best-effort).
	 */
	private static function maybe_add_fulltext_index() {
		global $wpdb;

		$table = self::verses_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}` WHERE Key_name = 'text_ft'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! empty( $indexes ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "ALTER TABLE `{$table}` ADD FULLTEXT KEY text_ft (text)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Whether a translation is installed and ready.
	 *
	 * @param string $slug Translation slug.
	 * @return bool
	 */
	public static function is_installed( $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug ) {
			return false;
		}

		if ( self::$test_backend && method_exists( self::$test_backend, 'is_installed' ) ) {
			return (bool) self::$test_backend->is_installed( $slug );
		}

		$row = self::get_translation( $slug );
		return is_array( $row ) && 'ready' === ( $row['status'] ?? '' ) && (int) ( $row['verse_count'] ?? 0 ) > 0;
	}

	/**
	 * Get translation row.
	 *
	 * @param string $slug Translation slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_translation( $slug ) {
		global $wpdb;

		$slug = sanitize_key( (string) $slug );
		if ( self::$test_backend && method_exists( self::$test_backend, 'get_translation' ) ) {
			$row = self::$test_backend->get_translation( $slug );
			return is_array( $row ) ? $row : null;
		}

		// Unit tests / early bootstrap may not have $wpdb.
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return null;
		}

		$table = self::translations_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT slug, label, source, license, verse_count, installed_at, status, error_message FROM {$table} WHERE slug = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * List all translation rows.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_translations() {
		global $wpdb;

		$table = self::translations_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT slug, label, source, license, verse_count, installed_at, status, error_message FROM {$table} ORDER BY slug ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Upsert translation metadata.
	 *
	 * @param string               $slug Translation slug.
	 * @param array<string, mixed> $data Fields to set.
	 * @return bool
	 */
	public static function upsert_translation( $slug, array $data ) {
		global $wpdb;

		$slug  = sanitize_key( (string) $slug );
		$table = self::translations_table();
		if ( '' === $slug ) {
			return false;
		}

		$catalog = self::get_catalog();
		$defaults = isset( $catalog[ $slug ] ) ? $catalog[ $slug ] : array(
			'slug'    => $slug,
			'label'   => strtoupper( $slug ),
			'source'  => '',
			'license' => '',
		);

		$existing = self::get_translation( $slug );
		$row      = array(
			'slug'          => $slug,
			'label'         => isset( $data['label'] ) ? (string) $data['label'] : ( $existing['label'] ?? $defaults['label'] ),
			'source'        => isset( $data['source'] ) ? (string) $data['source'] : ( $existing['source'] ?? $defaults['source'] ),
			'license'       => isset( $data['license'] ) ? (string) $data['license'] : ( $existing['license'] ?? $defaults['license'] ),
			'verse_count'   => isset( $data['verse_count'] ) ? (int) $data['verse_count'] : (int) ( $existing['verse_count'] ?? 0 ),
			'installed_at'  => array_key_exists( 'installed_at', $data ) ? $data['installed_at'] : ( $existing['installed_at'] ?? null ),
			'status'        => isset( $data['status'] ) ? sanitize_key( (string) $data['status'] ) : ( $existing['status'] ?? 'not_installed' ),
			'error_message' => array_key_exists( 'error_message', $data ) ? $data['error_message'] : ( $existing['error_message'] ?? null ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->replace( $table, $row );
	}

	/**
	 * Insert a batch of verses (replace on conflict).
	 *
	 * @param string                            $slug   Translation slug.
	 * @param array<int, array<string, mixed>> $verses Normalized verses.
	 * @return int Rows affected (approx).
	 */
	public static function insert_verses_batch( $slug, array $verses ) {
		global $wpdb;

		$slug  = sanitize_key( (string) $slug );
		$table = self::verses_table();
		if ( '' === $slug || empty( $verses ) ) {
			return 0;
		}

		$placeholders = array();
		$values       = array();
		foreach ( $verses as $verse ) {
			$book_id = (int) ( $verse['book_id'] ?? 0 );
			$chapter = (int) ( $verse['chapter'] ?? 0 );
			$number  = (int) ( $verse['verse'] ?? 0 );
			$text    = isset( $verse['text'] ) ? (string) $verse['text'] : '';
			if ( $book_id < 1 || $chapter < 1 || $number < 1 || '' === $text ) {
				continue;
			}
			$placeholders[] = '(%s,%d,%d,%d,%s)';
			$values[]       = $slug;
			$values[]       = $book_id;
			$values[]       = $chapter;
			$values[]       = $number;
			$values[]       = $text;
		}

		if ( empty( $placeholders ) ) {
			return 0;
		}

		$sql = "INSERT INTO {$table} (translation, book_id, chapter, verse, text) VALUES "
			. implode( ',', $placeholders )
			. ' ON DUPLICATE KEY UPDATE text = VALUES(text)';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $wpdb->prepare( $sql, $values ) );
		return false === $result ? 0 : (int) $result;
	}

	/**
	 * Get chapter verses for a translation.
	 *
	 * @param string $slug    Translation slug.
	 * @param int    $book_id Book ID.
	 * @param int    $chapter Chapter.
	 * @return array{verses:array<int,array{number:int,text:string}>,headings:array}|null
	 */
	public static function get_chapter( $slug, $book_id, $chapter ) {
		global $wpdb;

		$slug    = sanitize_key( (string) $slug );
		$book_id = (int) $book_id;
		$chapter = (int) $chapter;
		if ( '' === $slug || $book_id < 1 || $chapter < 1 || ! self::is_installed( $slug ) ) {
			return null;
		}

		if ( self::$test_backend && method_exists( self::$test_backend, 'get_chapter' ) ) {
			return self::$test_backend->get_chapter( $slug, $book_id, $chapter );
		}

		$table = self::verses_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT verse, text FROM {$table} WHERE translation = %s AND book_id = %d AND chapter = %d ORDER BY verse ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug,
				$book_id,
				$chapter
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return null;
		}

		$verses = array();
		foreach ( $rows as $row ) {
			$text = trim( (string) ( $row['text'] ?? '' ) );
			if ( '' === $text ) {
				continue;
			}
			$verses[] = array(
				'number' => (int) $row['verse'],
				'text'   => $text,
			);
		}

		if ( empty( $verses ) ) {
			return null;
		}

		return array(
			'verses'   => $verses,
			'headings' => array(),
		);
	}

	/**
	 * Get a single verse text.
	 *
	 * @param string $slug    Translation slug.
	 * @param int    $book_id Book ID.
	 * @param int    $chapter Chapter.
	 * @param int    $verse   Verse.
	 * @return string|null
	 */
	public static function get_verse( $slug, $book_id, $chapter, $verse ) {
		global $wpdb;

		$slug    = sanitize_key( (string) $slug );
		$book_id = (int) $book_id;
		$chapter = (int) $chapter;
		$verse   = (int) $verse;
		if ( '' === $slug || $book_id < 1 || $chapter < 1 || $verse < 1 || ! self::is_installed( $slug ) ) {
			return null;
		}

		$table = self::verses_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$text = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT text FROM {$table} WHERE translation = %s AND book_id = %d AND chapter = %d AND verse = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug,
				$book_id,
				$chapter,
				$verse
			)
		);

		$text = is_string( $text ) ? trim( $text ) : '';
		return '' !== $text ? $text : null;
	}

	/**
	 * Search local verses (FULLTEXT when available, otherwise LIKE).
	 *
	 * @param string               $slug  Translation slug.
	 * @param string               $query Search query.
	 * @param int                  $limit Max results (up to 100).
	 * @param array<string, mixed> $args  Optional: testament (ot|nt|'').
	 * @return array<int, array<string, mixed>>
	 */
	public static function search( $slug, $query, $limit = 12, $args = array() ) {
		global $wpdb;

		$slug   = sanitize_key( (string) $slug );
		$query  = trim( (string) $query );
		$limit  = max( 1, min( 100, (int) $limit ) );
		$offset = max( 0, (int) ( $args['offset'] ?? 0 ) );
		if ( '' === $slug || '' === $query || ! self::is_installed( $slug ) ) {
			return array();
		}

		$testament = sanitize_key( (string) ( $args['testament'] ?? '' ) );
		if ( ! in_array( $testament, array( 'ot', 'nt' ), true ) ) {
			$testament = '';
		}

		$table    = self::verses_table();
		$results  = array();
		$book_sql = '';
		if ( 'ot' === $testament ) {
			$book_sql = ' AND (book_id BETWEEN 1 AND 39 OR book_id >= 67)';
		} elseif ( 'nt' === $testament ) {
			$book_sql = ' AND book_id BETWEEN 40 AND 66';
		}

		// Prefer FULLTEXT when the index exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$has_ft = $wpdb->get_results( "SHOW INDEX FROM `{$table}` WHERE Key_name = 'text_ft'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! empty( $has_ft ) ) {
			$boolean = self::to_boolean_fulltext( $query );
			if ( $boolean ) {
				$sql = "SELECT book_id, chapter, verse, text FROM {$table}
					WHERE translation = %s AND MATCH(text) AGAINST (%s IN BOOLEAN MODE){$book_sql}
					ORDER BY book_id ASC, chapter ASC, verse ASC
					LIMIT %d OFFSET %d";
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name + optional testament clause.
						$slug,
						$boolean,
						$limit,
						$offset
					),
					ARRAY_A
				);
				if ( is_array( $rows ) ) {
					$results = $rows;
				}
			}
		}

		if ( empty( $results ) ) {
			$like = '%' . $wpdb->esc_like( $query ) . '%';
			$sql  = "SELECT book_id, chapter, verse, text FROM {$table}
				WHERE translation = %s AND text LIKE %s{$book_sql}
				ORDER BY book_id ASC, chapter ASC, verse ASC
				LIMIT %d OFFSET %d";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name + optional testament clause.
					$slug,
					$like,
					$limit,
					$offset
				),
				ARRAY_A
			);
			$results = is_array( $rows ) ? $rows : array();
		}

		$out = array();
		foreach ( $results as $row ) {
			$book_id = (int) ( $row['book_id'] ?? 0 );
			$chapter = (int) ( $row['chapter'] ?? 0 );
			$verse   = (int) ( $row['verse'] ?? 0 );
			$text    = trim( (string) ( $row['text'] ?? '' ) );
			if ( $book_id < 1 || $chapter < 1 || $verse < 1 || '' === $text ) {
				continue;
			}
			$out[] = array(
				'reference' => HWBL_Books::format_reference( $book_id, $chapter, $verse ),
				'book_id'   => $book_id,
				'chapter'   => $chapter,
				'verse'     => $verse,
				'text'      => $text,
			);
		}

		return $out;
	}

	/**
	 * List installed translation slugs with labels.
	 *
	 * @return array<string, string>
	 */
	public static function get_installed_labels() {
		$catalog = self::get_catalog();
		$out     = array();
		foreach ( array_keys( $catalog ) as $slug ) {
			if ( ! self::is_installed( $slug ) ) {
				continue;
			}
			$out[ $slug ] = isset( $catalog[ $slug ]['label'] ) ? (string) $catalog[ $slug ]['label'] : $slug;
		}
		return $out;
	}

	/**
	 * Convert a query into a BOOLEAN MODE FULLTEXT expression.
	 *
	 * @param string $query Raw query.
	 * @return string
	 */
	private static function to_boolean_fulltext( $query ) {
		$parts = preg_split( '/\s+/u', $query );
		if ( ! is_array( $parts ) ) {
			return '';
		}
		$terms = array();
		foreach ( $parts as $part ) {
			$part = preg_replace( '/[^\p{L}\p{N}\'-]+/u', '', (string) $part );
			if ( null === $part || strlen( $part ) < 3 ) {
				continue;
			}
			$terms[] = '+' . $part . '*';
		}
		return implode( ' ', $terms );
	}

	/**
	 * Delete all verses and translation row for a slug.
	 *
	 * @param string $slug Translation slug.
	 * @return bool
	 */
	public static function remove_translation( $slug ) {
		global $wpdb;

		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug ) {
			return false;
		}

		$verses = self::verses_table();
		$trans  = self::translations_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $verses, array( 'translation' => $slug ), array( '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $trans, array( 'slug' => $slug ), array( '%s' ) );

		return true;
	}

	/**
	 * Book IDs present in an installed translation.
	 *
	 * @param string $slug Translation slug.
	 * @return int[]
	 */
	public static function list_book_ids( $slug ) {
		global $wpdb;

		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug || ! self::is_installed( $slug ) ) {
			return array();
		}
		if ( self::$test_backend && method_exists( self::$test_backend, 'list_book_ids' ) ) {
			return array_map( 'intval', (array) self::$test_backend->list_book_ids( $slug ) );
		}
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return array();
		}

		$table = self::verses_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT book_id FROM {$table} WHERE translation = %s ORDER BY book_id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug
			)
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( 'intval', $rows );
	}

	/**
	 * Chapter numbers present for a book in an installed translation.
	 *
	 * @param string $slug    Translation slug.
	 * @param int    $book_id Book ID.
	 * @return int[]
	 */
	public static function list_chapter_numbers( $slug, $book_id ) {
		global $wpdb;

		$slug    = sanitize_key( (string) $slug );
		$book_id = (int) $book_id;
		if ( '' === $slug || $book_id < 1 || ! self::is_installed( $slug ) ) {
			return array();
		}
		if ( self::$test_backend && method_exists( self::$test_backend, 'list_chapter_numbers' ) ) {
			return array_map( 'intval', (array) self::$test_backend->list_chapter_numbers( $slug, $book_id ) );
		}
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return array();
		}

		$table = self::verses_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT chapter FROM {$table} WHERE translation = %s AND book_id = %d ORDER BY chapter ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug,
				$book_id
			)
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( 'intval', $rows );
	}

	/**
	 * Count distinct chapters for a translation.
	 *
	 * @param string $slug Translation slug.
	 * @return int
	 */
	public static function count_chapters( $slug ) {
		global $wpdb;

		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug || ! self::is_installed( $slug ) ) {
			return 0;
		}
		if ( self::$test_backend && method_exists( self::$test_backend, 'count_chapters' ) ) {
			return (int) self::$test_backend->count_chapters( $slug );
		}

		$table = self::verses_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM ( SELECT 1 FROM {$table} WHERE translation = %s GROUP BY book_id, chapter ) AS hwbl_ch", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug
			)
		);

		return (int) $count;
	}

	/**
	 * Verse numbers present for a chapter in an installed translation.
	 *
	 * @param string $slug    Translation slug.
	 * @param int    $book_id Book ID.
	 * @param int    $chapter Chapter.
	 * @return int[]
	 */
	public static function list_verse_numbers( $slug, $book_id, $chapter ) {
		global $wpdb;

		$slug    = sanitize_key( (string) $slug );
		$book_id = (int) $book_id;
		$chapter = (int) $chapter;
		if ( '' === $slug || $book_id < 1 || $chapter < 1 || ! self::is_installed( $slug ) ) {
			return array();
		}
		if ( self::$test_backend && method_exists( self::$test_backend, 'list_verse_numbers' ) ) {
			return array_map( 'intval', (array) self::$test_backend->list_verse_numbers( $slug, $book_id, $chapter ) );
		}

		$table = self::verses_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT verse FROM {$table} WHERE translation = %s AND book_id = %d AND chapter = %d ORDER BY verse ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug,
				$book_id,
				$chapter
			)
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( 'intval', $rows );
	}

	/**
	 * Count verses for a translation.
	 *
	 * @param string $slug Translation slug.
	 * @return int
	 */
	public static function count_verses( $slug ) {
		global $wpdb;

		$slug = sanitize_key( (string) $slug );
		if ( self::$test_backend && method_exists( self::$test_backend, 'count_verses' ) ) {
			return (int) self::$test_backend->count_verses( $slug );
		}

		$table = self::verses_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE translation = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug
			)
		);

		return (int) $count;
	}

	/**
	 * Drop local Bible tables (uninstall).
	 */
	public static function drop_tables() {
		global $wpdb;

		$verses = self::verses_table();
		$trans  = self::translations_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$verses}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$trans}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		delete_option( self::DB_VERSION_KEY );
	}
}
