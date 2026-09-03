<?php
/**
 * Persist Bible reader AI explanations in a custom SQL table.
 *
 * Legacy CPT posts (thw_bible_explain) are still readable and can be migrated,
 * but new saves go to {$wpdb->prefix}hwbl_bible_explains.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_Bible_Reader_Explain_Store
 */
class THW_Premium_Bible_Reader_Explain_Store {

	const POST_TYPE = 'thw_bible_explain';

	const META_BOOK  = '_thw_bible_explain_book';
	const META_CHAP  = '_thw_bible_explain_chapter';
	const META_VERSE = '_thw_bible_explain_verse';
	const META_TRANS = '_thw_bible_explain_translation';
	const META_SCOPE = '_thw_bible_explain_scope';
	const META_TRAD  = '_thw_bible_explain_tradition';
	const META_REF   = '_thw_bible_explain_reference';
	const META_FLAG  = '_thw_bible_explain_flagged';

	const DB_VERSION_KEY = 'hwbl_bible_explain_db_version';
	const DB_VERSION     = '1.0.0';
	const MIGRATE_OPTION = 'hwbl_bible_explain_cpt_migrate_offset';

	/**
	 * Optional in-memory backend for unit tests.
	 *
	 * @var object|null
	 */
	public static $test_backend = null;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install_schema' ), 2 );
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 20 );
		add_action( 'init', array( __CLASS__, 'maybe_migrate_cpt_batch' ), 30 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_singular_styles' ) );
		add_filter( 'template_include', array( __CLASS__, 'maybe_use_plugin_template' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_singular_content' ), 12 );
	}

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'hwbl_bible_explains';
	}

	/**
	 * Install / upgrade schema.
	 */
	public static function maybe_install_schema() {
		$installed = (string) get_option( self::DB_VERSION_KEY, '' );
		if ( version_compare( $installed, self::DB_VERSION, '>=' ) ) {
			return;
		}
		self::create_table();
		update_option( self::DB_VERSION_KEY, self::DB_VERSION, false );
	}

	/**
	 * Create explains table.
	 */
	public static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			translation varchar(32) NOT NULL DEFAULT '',
			tradition varchar(64) NOT NULL DEFAULT '',
			scope varchar(16) NOT NULL DEFAULT 'verse',
			book_id smallint(5) unsigned NOT NULL DEFAULT 0,
			chapter smallint(5) unsigned NOT NULL DEFAULT 0,
			verse smallint(5) unsigned NOT NULL DEFAULT 0,
			reference varchar(191) NOT NULL DEFAULT '',
			html longtext NOT NULL,
			flagged tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY lookup_key (translation,tradition,scope,book_id,chapter,verse),
			KEY pack_key (translation,tradition,scope),
			KEY book_chapter (book_id,chapter)
		) {$charset};";

		dbDelta( $sql );
	}

	/**
	 * Drop table (uninstall).
	 */
	public static function drop_table() {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		delete_option( self::DB_VERSION_KEY );
		delete_option( self::MIGRATE_OPTION );
	}

	/**
	 * Repair markdown-fenced / plain-text AI bodies when rendering a legacy CPT.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_singular_content( $content ) {
		if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( class_exists( 'THW_Premium_AI_Client' ) ) {
			if ( ! preg_match( '/^(.*)<div class="thw-bible-explain__body[^"]*">(.*)<\/div>\s*$/is', (string) $content, $m ) ) {
				$content = THW_Premium_AI_Client::format_html_response( $content );
			} else {
				$prefix  = $m[1];
				$body    = THW_Premium_AI_Client::format_html_response( $m[2] );
				$content = $prefix . '<div class="thw-bible-explain__body thw-bible-explain-output">' . $body . '</div>';
			}
		}

		$map = self::render_places_map_for_current_post();
		if ( $map ) {
			$content .= $map;
		}

		return $content;
	}

	/**
	 * Append OpenBible place map for the explained passage (when maps are enabled).
	 *
	 * @return string
	 */
	private static function render_places_map_for_current_post() {
		if ( ! class_exists( 'HWBL_Bible_Places' ) || ! HWBL_Bible_Places::is_enabled() ) {
			return '';
		}

		if ( 'mapbox' === HWBL_Bible_Places::get_provider() && '' === HWBL_Bible_Places::get_mapbox_token() ) {
			return '';
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$book_id = absint( get_post_meta( $post_id, self::META_BOOK, true ) );
		$chapter = absint( get_post_meta( $post_id, self::META_CHAP, true ) );
		$verse   = absint( get_post_meta( $post_id, self::META_VERSE, true ) );
		$scope   = sanitize_key( (string) get_post_meta( $post_id, self::META_SCOPE, true ) );

		if ( $book_id < 1 || $chapter < 1 ) {
			return '';
		}

		if ( 'chapter' === $scope ) {
			$verse = 0;
		}

		$map = HWBL_Bible_Places::render_shortcode(
			array(
				'book'    => $book_id,
				'chapter' => $chapter,
				'verse'   => $verse,
				'scope'   => $verse > 0 ? 'verse' : 'chapter',
				'height'  => 320,
			)
		);

		if ( false === strpos( $map, 'hwbl-bible-map' ) ) {
			return '';
		}

		return '<section class="thw-bible-explain__map" aria-label="' . esc_attr__( 'Places on the map', 'hidden-word-bible-lessons' ) . '">'
			. '<h2 class="thw-bible-explain__map-title">' . esc_html__( 'Places in this passage', 'hidden-word-bible-lessons' ) . '</h2>'
			. $map
			. '</section>';
	}

	/**
	 * Enqueue styles on legacy CPT singular pages.
	 */
	public static function enqueue_singular_styles() {
		if ( ! is_singular( self::POST_TYPE ) ) {
			return;
		}
		if ( wp_style_is( 'thw-premium', 'registered' ) ) {
			wp_enqueue_style( 'thw-premium' );
		}
		if ( class_exists( 'HWBL_Bible_Places' ) && HWBL_Bible_Places::is_enabled() ) {
			if ( 'mapbox' !== HWBL_Bible_Places::get_provider() || '' !== HWBL_Bible_Places::get_mapbox_token() ) {
				HWBL_Bible_Places::enqueue_assets();
			}
		}
	}

	/**
	 * Plugin template for legacy CPT singles.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public static function maybe_use_plugin_template( $template ) {
		if ( ! is_singular( self::POST_TYPE ) ) {
			return $template;
		}

		$slug = 'single-' . self::POST_TYPE . '.php';
		if ( '' !== locate_template( array( $slug, 'single.php' ), false, false ) ) {
			return $template;
		}

		$plugin_template = THW_PREMIUM_DIR . 'templates/single-thw_bible_explain.php';
		if ( is_readable( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}

	/**
	 * Flush rewrites when needed (legacy CPT URLs).
	 */
	public static function maybe_flush_rewrites() {
		$version = defined( 'HWBL_VERSION' ) ? (string) HWBL_VERSION : ( defined( 'THW_PREMIUM_VERSION' ) ? (string) THW_PREMIUM_VERSION : '1' );
		$flag    = 'thw_bible_explain_rewrite_ver';
		$stored  = (string) get_option( $flag, '' );

		if ( $stored !== $version || ! self::rewrite_rules_registered() ) {
			flush_rewrite_rules( false );
			update_option( $flag, $version, false );
		}
	}

	/**
	 * Whether rewrite rules include the Bible explanation CPT slug.
	 *
	 * @return bool
	 */
	public static function rewrite_rules_registered() {
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) || array() === $rules ) {
			return false;
		}
		foreach ( array_keys( $rules ) as $rule ) {
			if ( false !== strpos( (string) $rule, 'bible-explanation' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Register legacy CPT (hidden from menus; kept for old public URLs).
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Bible Explanations', 'hidden-word-bible-lessons' ),
					'singular_name' => __( 'Bible Explanation', 'hidden-word-bible-lessons' ),
					'edit_item'     => __( 'Edit Bible Explanation', 'hidden-word-bible-lessons' ),
					'view_item'     => __( 'View Bible Explanation', 'hidden-word-bible-lessons' ),
					'search_items'  => __( 'Search Bible Explanations', 'hidden-word-bible-lessons' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=hwbl_lesson',
				'show_in_rest'        => true,
				'has_archive'         => false,
				'rewrite'             => array(
					'slug'       => 'bible-explanation',
					'with_front' => false,
				),
				'supports'            => array( 'title', 'editor', 'custom-fields' ),
				'exclude_from_search' => true,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Normalize a scripture reference.
	 *
	 * @param string $reference Human reference.
	 * @return string
	 */
	public static function normalize_reference( $reference ) {
		$reference = sanitize_text_field( (string) $reference );
		$reference = preg_replace( '/\s+/', ' ', $reference );
		return trim( (string) $reference );
	}

	/**
	 * Build stable lookup keys from a payload / request.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @return array{book_id:int,chapter:int,verse:int,translation:string,scope:string,tradition:string}
	 */
	public static function normalize_keys( $payload ) {
		$scope = sanitize_key( (string) ( $payload['scope'] ?? 'verse' ) );
		if ( 'chapter' !== $scope ) {
			$scope = 'verse';
		}

		$verse = max( 0, (int) ( $payload['verse'] ?? 0 ) );
		if ( 'chapter' === $scope ) {
			$verse = 0;
		}

		$translation = sanitize_key( (string) ( $payload['translation'] ?? '' ) );
		if ( '' === $translation ) {
			$translation = 'default';
		}

		$tradition = sanitize_key( (string) ( $payload['tradition'] ?? '' ) );
		if ( '' === $tradition ) {
			$tradition = 'site';
		}

		return array(
			'book_id'     => max( 1, (int) ( $payload['book_id'] ?? 0 ) ),
			'chapter'     => max( 1, (int) ( $payload['chapter'] ?? 0 ) ),
			'verse'       => $verse,
			'translation' => $translation,
			'scope'       => $scope,
			'tradition'   => $tradition,
		);
	}

	/**
	 * Whether value is a table row.
	 *
	 * @param mixed $row Value.
	 * @return bool
	 */
	public static function is_row( $row ) {
		return is_array( $row ) && isset( $row['html'] ) && ( isset( $row['book_id'] ) || isset( $row['id'] ) );
	}

	/**
	 * Find a saved explanation (table first, then legacy CPT).
	 *
	 * Exact tradition match only — use resolve_for_display() for base + override.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public static function find( array $payload ) {
		$keys = self::normalize_keys( $payload );
		if ( $keys['book_id'] < 1 || $keys['chapter'] < 1 ) {
			return null;
		}

		$row = self::get_row( $keys );
		if ( is_array( $row ) && self::has_usable_explanation( $row ) ) {
			return $row;
		}

		// CPT fallback is skipped under unit-test backends.
		if ( ! self::$test_backend ) {
			$post = self::find_cpt_post( $keys );
			if ( $post instanceof WP_Post && self::has_usable_explanation( $post ) ) {
				// Inventory / packs count SQL rows only — copy legacy CPT content into SQL.
				$migrated = self::ensure_sql_row_from_item( $keys, $post );
				return is_array( $migrated ) ? $migrated : self::row_from_post( $post );
			}
		}

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Find the shared base explanation for a passage (fallback chain).
	 *
	 * @param array<string, mixed> $payload Payload (tradition ignored).
	 * @return array<string, mixed>|null
	 */
	public static function find_base( array $payload ) {
		$keys = self::normalize_keys( $payload );
		$fallbacks = function_exists( 'thw_premium_explain_base_fallback_slugs' )
			? thw_premium_explain_base_fallback_slugs()
			: array( 'base', 'nondenom', 'general', 'site' );
		$base_slug = function_exists( 'thw_premium_explain_base_tradition_slug' )
			? thw_premium_explain_base_tradition_slug()
			: 'base';

		foreach ( $fallbacks as $slug ) {
			$keys['tradition'] = sanitize_key( (string) $slug );
			$found             = self::find( $keys );
			if ( is_array( $found ) && self::has_usable_explanation( $found ) ) {
				// Always ensure the canonical base slug has a SQL row for pack inventory.
				$base_keys              = $keys;
				$base_keys['tradition'] = $base_slug;
				$canonical              = self::ensure_sql_row_from_item( $base_keys, $found );
				return is_array( $canonical ) && self::has_usable_explanation( $canonical ) ? $canonical : $found;
			}
		}

		return null;
	}

	/**
	 * Persist a usable explanation into the SQL table for the given keys if missing.
	 *
	 * Used when legacy CPT (or a fallback tradition row) already has content that
	 * inventory/fill-gaps would otherwise treat as missing.
	 *
	 * @param array<string, mixed>             $keys Normalized keys.
	 * @param array<string, mixed>|WP_Post     $item Source row or CPT.
	 * @return array<string, mixed>|null
	 */
	public static function ensure_sql_row_from_item( array $keys, $item ) {
		$keys = self::normalize_keys( $keys );
		if ( $keys['book_id'] < 1 || $keys['chapter'] < 1 ) {
			return null;
		}
		if ( ! self::has_usable_explanation( $item ) ) {
			return null;
		}

		$existing = self::get_row( $keys );
		if ( is_array( $existing ) && self::has_usable_explanation( $existing ) ) {
			return $existing;
		}

		$html = self::get_explanation_html( $item );
		$ref  = '';
		if ( self::is_row( $item ) ) {
			$ref = (string) ( $item['reference'] ?? '' );
		} elseif ( $item instanceof WP_Post ) {
			$ref = (string) get_post_meta( $item->ID, self::META_REF, true );
			if ( '' === $ref ) {
				$ref = (string) $item->post_title;
			}
		}

		$payload = array(
			'book_id'     => $keys['book_id'],
			'chapter'     => $keys['chapter'],
			'verse'       => $keys['verse'],
			'translation' => $keys['translation'],
			'tradition'   => $keys['tradition'],
			'scope'       => $keys['scope'],
			'reference'   => $ref,
		);

		return self::save( $payload, $html, self::is_flagged( $item ) );
	}

	/**
	 * HTML stored when a tradition was checked and matches the shared base.
	 *
	 * @return string
	 */
	public static function no_diff_html_sentinel() {
		return '<!--HWBL_NO_TRADITION_DIFF-->';
	}

	/**
	 * Whether HTML (or a row) is the same-as-base marker.
	 *
	 * @param mixed $html_or_row HTML string or row/post.
	 * @return bool
	 */
	public static function is_same_as_base_marker( $html_or_row ) {
		if ( is_array( $html_or_row ) ) {
			$html = (string) ( $html_or_row['html'] ?? '' );
		} elseif ( $html_or_row instanceof WP_Post ) {
			$html = (string) $html_or_row->post_content;
		} else {
			$html = (string) $html_or_row;
		}
		return false !== strpos( $html, 'HWBL_NO_TRADITION_DIFF' );
	}

	/**
	 * Tradition override state for a passage: override|same_as_base|missing.
	 *
	 * @param array<string, mixed> $payload Payload with tradition.
	 * @return string
	 */
	public static function get_tradition_override_state( array $payload ) {
		$keys = self::normalize_keys( $payload );
		$row  = self::get_row( $keys );
		if ( ! is_array( $row ) ) {
			return 'missing';
		}
		if ( self::is_same_as_base_marker( $row ) ) {
			return 'same_as_base';
		}
		if ( self::has_usable_explanation( $row ) ) {
			return 'override';
		}
		return 'missing';
	}

	/**
	 * Persist a same-as-base marker for a tradition (skips future AI re-checks).
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public static function save_same_as_base( array $payload ) {
		$keys = self::normalize_keys( $payload );
		$html = self::no_diff_html_sentinel();

		$existing = self::get_row( $keys );
		if ( is_array( $existing ) && self::has_usable_explanation( $existing ) ) {
			return $existing;
		}
		if ( is_array( $existing ) && self::is_same_as_base_marker( $existing ) ) {
			return $existing;
		}

		$reference = isset( $payload['reference'] ) ? self::normalize_reference( (string) $payload['reference'] ) : '';
		$row       = array(
			'translation' => $keys['translation'],
			'tradition'   => $keys['tradition'],
			'scope'       => $keys['scope'],
			'book_id'     => $keys['book_id'],
			'chapter'     => $keys['chapter'],
			'verse'       => $keys['verse'],
			'reference'   => $reference,
			'html'        => $html,
			'flagged'     => 0,
		);

		if ( self::$test_backend && method_exists( self::$test_backend, 'save_row' ) ) {
			$saved = self::$test_backend->save_row( $row );
			return is_array( $saved ) ? $saved : null;
		}

		return self::save( $payload, $html, false );
	}

	/**
	 * Resolve what to show for a requested tradition (tradition-first when override exists).
	 *
	 * @param array<string, mixed> $payload Payload including tradition.
	 * @return array{
	 *   content_row:?array,
	 *   base:?array,
	 *   override:?array,
	 *   has_tradition_diff:bool,
	 *   legacy_full:bool,
	 *   same_as_base:bool,
	 *   needs_override_check:bool,
	 *   requested_tradition:string
	 * }
	 */
	public static function resolve_for_display( array $payload ) {
		$keys      = self::normalize_keys( $payload );
		$requested = $keys['tradition'];
		if ( function_exists( 'thw_premium_explain_tradition_uses_shared_base' ) ) {
			$uses_base = thw_premium_explain_tradition_uses_shared_base( $requested );
		} else {
			$uses_base = in_array( $requested, array( 'base', 'nondenom', 'general', 'site', '' ), true );
		}

		$base  = self::find_base( $keys );
		$state = 'missing';
		$row   = null;
		if ( ! $uses_base ) {
			$state = self::get_tradition_override_state( $keys );
			$row   = self::get_row( $keys );
		}

		$empty = array(
			'content_row'          => null,
			'base'                 => $base,
			'override'             => null,
			'has_tradition_diff'   => false,
			'legacy_full'          => false,
			'same_as_base'         => false,
			'needs_override_check' => false,
			'requested_tradition'  => $requested,
		);

		if ( $uses_base ) {
			if ( $base ) {
				$empty['content_row'] = $base;
			}
			return $empty;
		}

		if ( 'override' === $state && is_array( $row ) ) {
			// Tradition-specific answer is primary.
			return array(
				'content_row'          => $row,
				'base'                 => $base,
				'override'             => $row,
				'has_tradition_diff'   => true,
				'legacy_full'          => empty( $base ),
				'same_as_base'         => false,
				'needs_override_check' => false,
				'requested_tradition'  => $requested,
			);
		}

		if ( 'same_as_base' === $state && $base ) {
			return array(
				'content_row'          => $base,
				'base'                 => $base,
				'override'             => null,
				'has_tradition_diff'   => false,
				'legacy_full'          => false,
				'same_as_base'         => true,
				'needs_override_check' => false,
				'requested_tradition'  => $requested,
			);
		}

		if ( $base ) {
			return array(
				'content_row'          => $base,
				'base'                 => $base,
				'override'             => null,
				'has_tradition_diff'   => false,
				'legacy_full'          => false,
				'same_as_base'         => false,
				'needs_override_check' => true,
				'requested_tradition'  => $requested,
			);
		}

		return $empty;
	}

	/**
	 * @deprecated Use find().
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>|null
	 */
	public static function find_saved_post( $payload ) {
		return self::find( is_array( $payload ) ? $payload : array() );
	}

	/**
	 * Get table row by keys (no usability check).
	 *
	 * @param array<string, mixed> $keys Normalized keys.
	 * @return array<string, mixed>|null
	 */
	public static function get_row( array $keys ) {
		$keys = self::normalize_keys( $keys );

		if ( self::$test_backend && method_exists( self::$test_backend, 'get_row' ) ) {
			$row = self::$test_backend->get_row( $keys );
			return is_array( $row ) ? $row : null;
		}

		global $wpdb;
		self::maybe_install_schema();
		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, translation, tradition, scope, book_id, chapter, verse, reference, html, flagged, created_at, updated_at
				FROM {$table}
				WHERE translation = %s AND tradition = %s AND scope = %s AND book_id = %d AND chapter = %d AND verse = %d
				LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$keys['translation'],
				$keys['tradition'],
				$keys['scope'],
				$keys['book_id'],
				$keys['chapter'],
				$keys['verse']
			),
			ARRAY_A
		);

		return is_array( $row ) ? self::normalize_row( $row ) : null;
	}

	/**
	 * Save (upsert) an explanation into the SQL table.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @param string               $html    Explanation HTML body.
	 * @param bool                 $flagged Compliance flagged.
	 * @return array<string, mixed>|null
	 */
	public static function save( array $payload, $html, $flagged = false ) {
		$keys = self::normalize_keys( $payload );
		$html = (string) $html;
		$is_no_diff = self::is_same_as_base_marker( $html );
		if ( ! $is_no_diff && strlen( trim( wp_strip_all_tags( $html ) ) ) < 40 ) {
			return null;
		}

		$existing = self::get_row( $keys );
		if ( is_array( $existing ) && self::has_usable_explanation( $existing ) ) {
			return $existing;
		}
		if ( $is_no_diff && is_array( $existing ) && self::is_same_as_base_marker( $existing ) ) {
			return $existing;
		}

		$reference = isset( $payload['reference'] ) ? self::normalize_reference( (string) $payload['reference'] ) : '';
		if ( '' === $reference && is_array( $existing ) ) {
			$reference = (string) ( $existing['reference'] ?? '' );
		}

		$row = array(
			'translation' => $keys['translation'],
			'tradition'   => $keys['tradition'],
			'scope'       => $keys['scope'],
			'book_id'     => $keys['book_id'],
			'chapter'     => $keys['chapter'],
			'verse'       => $keys['verse'],
			'reference'   => $reference,
			'html'        => $html,
			'flagged'     => $flagged ? 1 : 0,
		);

		if ( self::$test_backend && method_exists( self::$test_backend, 'save_row' ) ) {
			$saved = self::$test_backend->save_row( $row );
			return is_array( $saved ) ? $saved : null;
		}

		global $wpdb;
		self::maybe_install_schema();
		$table = self::table();

		if ( is_array( $existing ) && ! empty( $existing['id'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array(
					'reference' => $row['reference'],
					'html'      => $row['html'],
					'flagged'   => $row['flagged'],
				),
				array( 'id' => (int) $existing['id'] ),
				array( '%s', '%s', '%d' ),
				array( '%d' )
			);
			return self::get_row( $keys );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ok = $wpdb->insert(
			$table,
			$row,
			array( '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d' )
		);
		if ( false === $ok ) {
			// Race: another request inserted — re-read.
			return self::get_row( $keys );
		}

		return self::get_row( $keys );
	}

	/**
	 * @deprecated Use save().
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @param string               $html    HTML.
	 * @param bool                 $flagged Flagged.
	 * @return array<string, mixed>|null
	 */
	public static function save_post( $payload, $html, $flagged ) {
		return self::save( is_array( $payload ) ? $payload : array(), $html, $flagged );
	}

	/**
	 * Explanation HTML body from a table row or legacy post.
	 *
	 * @param array<string, mixed>|WP_Post $item Row or post.
	 * @return string
	 */
	public static function get_explanation_html( $item ) {
		if ( self::is_row( $item ) ) {
			$body = (string) ( $item['html'] ?? '' );
		} elseif ( $item instanceof WP_Post ) {
			$content = (string) $item->post_content;
			if ( preg_match( '/<div class="thw-bible-explain__body[^"]*">(.*)<\/div>\s*$/is', $content, $m ) ) {
				$body = $m[1];
			} else {
				$body = $content;
			}
		} else {
			return '';
		}

		if ( class_exists( 'THW_Premium_AI_Client' ) ) {
			return THW_Premium_AI_Client::format_html_response( $body );
		}

		return wp_kses_post( $body );
	}

	/**
	 * Whether stored explanation is usable.
	 *
	 * @param array<string, mixed>|WP_Post $item Row or post.
	 * @return bool
	 */
	public static function has_usable_explanation( $item ) {
		if ( self::is_same_as_base_marker( $item ) ) {
			return false;
		}
		$text = trim( wp_strip_all_tags( (string) self::get_explanation_html( $item ) ) );
		return strlen( $text ) >= 40;
	}

	/**
	 * Flagged status from row or post.
	 *
	 * @param array<string, mixed>|WP_Post $item Item.
	 * @return bool
	 */
	public static function is_flagged( $item ) {
		if ( self::is_row( $item ) ) {
			return ! empty( $item['flagged'] );
		}
		if ( $item instanceof WP_Post ) {
			return (bool) get_post_meta( $item->ID, self::META_FLAG, true );
		}
		return false;
	}

	/**
	 * Storage id (table id or legacy post id).
	 *
	 * @param array<string, mixed>|WP_Post $item Item.
	 * @return int
	 */
	public static function item_id( $item ) {
		if ( self::is_row( $item ) ) {
			return (int) ( $item['id'] ?? 0 );
		}
		if ( $item instanceof WP_Post ) {
			return (int) $item->ID;
		}
		return 0;
	}

	/**
	 * Public URL — only for legacy CPT posts.
	 *
	 * @param array<string, mixed>|WP_Post $item Item.
	 * @return string
	 */
	public static function get_public_url( $item ) {
		if ( self::is_row( $item ) ) {
			return '';
		}
		if ( ! ( $item instanceof WP_Post ) || (int) $item->ID <= 0 ) {
			return '';
		}

		self::maybe_flush_rewrites();
		$url = get_permalink( $item );
		if ( is_string( $url ) && '' !== $url && ! is_wp_error( $url ) ) {
			return $url;
		}

		return add_query_arg(
			array(
				'p'         => (int) $item->ID,
				'post_type' => self::POST_TYPE,
			),
			home_url( '/' )
		);
	}

	/**
	 * Count rows for a pack key set.
	 *
	 * @param string             $translation Translation.
	 * @param string             $tradition   Tradition.
	 * @param array<int, string> $scopes      Scopes.
	 * @return int
	 */
	public static function count_for_keys( $translation, $tradition, array $scopes = array() ) {
		$translation = sanitize_key( (string) $translation );
		$tradition   = sanitize_key( (string) $tradition );
		$scopes      = array_values( array_filter( array_map( 'sanitize_key', $scopes ) ) );

		if ( self::$test_backend && method_exists( self::$test_backend, 'count_for_keys' ) ) {
			return (int) self::$test_backend->count_for_keys( $translation, $tradition, $scopes );
		}

		global $wpdb;
		self::maybe_install_schema();
		$table = self::table();

		$sql    = "SELECT COUNT(*) FROM {$table} WHERE translation = %s AND tradition = %s";
		$params = array( $translation ? $translation : 'default', $tradition ? $tradition : 'site' );
		if ( ! empty( $scopes ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $scopes ), '%s' ) );
			$sql         .= " AND scope IN ({$placeholders})";
			foreach ( $scopes as $scope ) {
				$params[] = $scope;
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		return (int) $count;
	}

	/**
	 * List rows for export (paginated by offset).
	 *
	 * @param string             $translation Translation.
	 * @param string             $tradition   Tradition.
	 * @param array<int, string> $scopes      Scopes.
	 * @param int                $offset      Offset.
	 * @param int                $limit       Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_for_keys( $translation, $tradition, array $scopes = array(), $offset = 0, $limit = 50 ) {
		$translation = sanitize_key( (string) $translation );
		$tradition   = sanitize_key( (string) $tradition );
		$scopes      = array_values( array_filter( array_map( 'sanitize_key', $scopes ) ) );
		$offset      = max( 0, (int) $offset );
		$limit       = max( 1, min( 200, (int) $limit ) );

		if ( self::$test_backend && method_exists( self::$test_backend, 'list_for_keys' ) ) {
			return (array) self::$test_backend->list_for_keys( $translation, $tradition, $scopes, $offset, $limit );
		}

		global $wpdb;
		self::maybe_install_schema();
		$table = self::table();

		$sql    = "SELECT id, translation, tradition, scope, book_id, chapter, verse, reference, html, flagged, created_at, updated_at
			FROM {$table}
			WHERE translation = %s AND tradition = %s";
		$params = array( $translation ? $translation : 'default', $tradition ? $tradition : 'site' );
		if ( ! empty( $scopes ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $scopes ), '%s' ) );
			$sql         .= " AND scope IN ({$placeholders})";
			foreach ( $scopes as $scope ) {
				$params[] = $scope;
			}
		}
		$sql     .= ' ORDER BY id ASC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return array_map( array( __CLASS__, 'normalize_row' ), $rows );
	}

	/**
	 * Delete rows for a pack key set (one batch).
	 *
	 * @param string             $translation Translation.
	 * @param string             $tradition   Tradition.
	 * @param array<int, string> $scopes      Scopes.
	 * @param int                $limit       Limit.
	 * @return array{deleted:int,remaining:int}
	 */
	public static function delete_for_keys( $translation, $tradition, array $scopes = array(), $limit = 50 ) {
		$limit = max( 1, min( 500, (int) $limit ) );
		$total = self::count_for_keys( $translation, $tradition, $scopes );
		if ( $total < 1 ) {
			return array(
				'deleted'   => 0,
				'remaining' => 0,
			);
		}

		if ( self::$test_backend && method_exists( self::$test_backend, 'delete_for_keys' ) ) {
			return self::$test_backend->delete_for_keys( $translation, $tradition, $scopes, $limit );
		}

		global $wpdb;
		self::maybe_install_schema();
		$table       = self::table();
		$translation = sanitize_key( (string) $translation );
		$tradition   = sanitize_key( (string) $tradition );
		$scopes      = array_values( array_filter( array_map( 'sanitize_key', $scopes ) ) );

		$sql    = "DELETE FROM {$table} WHERE translation = %s AND tradition = %s";
		$params = array( $translation ? $translation : 'default', $tradition ? $tradition : 'site' );
		if ( ! empty( $scopes ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $scopes ), '%s' ) );
			$sql         .= " AND scope IN ({$placeholders})";
			foreach ( $scopes as $scope ) {
				$params[] = $scope;
			}
		}
		$sql     .= ' LIMIT %d';
		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$deleted = (int) $wpdb->query( $wpdb->prepare( $sql, $params ) );
		$remaining = max( 0, $total - $deleted );

		return array(
			'deleted'   => $deleted,
			'remaining' => $remaining,
		);
	}

	/**
	 * Distinct translation slugs that have explains.
	 *
	 * @return array<int, string>
	 */
	public static function list_translation_slugs() {
		if ( self::$test_backend && method_exists( self::$test_backend, 'list_translation_slugs' ) ) {
			return array_map( 'sanitize_key', (array) self::$test_backend->list_translation_slugs() );
		}

		global $wpdb;
		self::maybe_install_schema();
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$col = $wpdb->get_col( "SELECT DISTINCT translation FROM {$table} ORDER BY translation ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $col ) ? array_values( array_filter( array_map( 'sanitize_key', $col ) ) ) : array();
	}

	/**
	 * Count passages in a ready Local Bible that lack an explain row.
	 *
	 * @param string $translation Translation slug.
	 * @param string $tradition   Tradition slug.
	 * @param string $scope       verse|chapter.
	 * @return int
	 */
	public static function count_missing_passages( $translation, $tradition, $scope = 'verse' ) {
		$translation = sanitize_key( (string) $translation );
		$tradition   = sanitize_key( (string) $tradition );
		$scope       = ( 'chapter' === sanitize_key( (string) $scope ) ) ? 'chapter' : 'verse';
		if ( '' === $translation || '' === $tradition ) {
			return 0;
		}

		if ( self::$test_backend && method_exists( self::$test_backend, 'count_missing_passages' ) ) {
			return (int) self::$test_backend->count_missing_passages( $translation, $tradition, $scope );
		}

		if ( ! class_exists( 'HWBL_Local_Bible_Store' ) || ! HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return 0;
		}

		global $wpdb;
		self::maybe_install_schema();
		HWBL_Local_Bible_Store::maybe_install_schema();
		$explains = self::table();
		$verses   = HWBL_Local_Bible_Store::verses_table();

		if ( 'chapter' === $scope ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM (
						SELECT v.book_id, v.chapter
						FROM {$verses} v
						LEFT JOIN {$explains} e
							ON e.translation = %s
							AND e.tradition = %s
							AND e.scope = 'chapter'
							AND e.book_id = v.book_id
							AND e.chapter = v.chapter
							AND e.verse = 0
						WHERE v.translation = %s AND e.id IS NULL
						GROUP BY v.book_id, v.chapter
					) missing_chapters", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$translation,
					$tradition,
					$translation
				)
			);
			return max( 0, (int) $count );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$verses} v
				LEFT JOIN {$explains} e
					ON e.translation = %s
					AND e.tradition = %s
					AND e.scope = 'verse'
					AND e.book_id = v.book_id
					AND e.chapter = v.chapter
					AND e.verse = v.verse
				WHERE v.translation = %s AND e.id IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$translation,
				$tradition,
				$translation
			)
		);
		return max( 0, (int) $count );
	}

	/**
	 * List Local Bible passages that lack an explain row.
	 *
	 * @param string $translation Translation slug.
	 * @param string $tradition   Tradition slug.
	 * @param string $scope       verse|chapter.
	 * @param int    $limit       Max rows.
	 * @param int    $offset      Offset.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,scope:string,translation:string,tradition:string}>
	 */
	public static function list_missing_passages( $translation, $tradition, $scope = 'verse', $limit = 50, $offset = 0 ) {
		$translation = sanitize_key( (string) $translation );
		$tradition   = sanitize_key( (string) $tradition );
		$scope       = ( 'chapter' === sanitize_key( (string) $scope ) ) ? 'chapter' : 'verse';
		$limit       = max( 1, min( 500, (int) $limit ) );
		$offset      = max( 0, (int) $offset );
		if ( '' === $translation || '' === $tradition ) {
			return array();
		}

		if ( self::$test_backend && method_exists( self::$test_backend, 'list_missing_passages' ) ) {
			$rows = (array) self::$test_backend->list_missing_passages( $translation, $tradition, $scope, $limit, $offset );
			return array_values(
				array_filter(
					array_map(
						static function ( $row ) use ( $translation, $tradition, $scope ) {
							if ( ! is_array( $row ) ) {
								return null;
							}
							return array(
								'book_id'     => max( 1, (int) ( $row['book_id'] ?? 0 ) ),
								'chapter'     => max( 1, (int) ( $row['chapter'] ?? 0 ) ),
								'verse'       => ( 'chapter' === $scope ) ? 0 : max( 1, (int) ( $row['verse'] ?? 0 ) ),
								'scope'       => $scope,
								'translation' => $translation,
								'tradition'   => $tradition,
							);
						},
						$rows
					)
				)
			);
		}

		if ( ! class_exists( 'HWBL_Local_Bible_Store' ) || ! HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return array();
		}

		global $wpdb;
		self::maybe_install_schema();
		HWBL_Local_Bible_Store::maybe_install_schema();
		$explains = self::table();
		$verses   = HWBL_Local_Bible_Store::verses_table();

		if ( 'chapter' === $scope ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT v.book_id, v.chapter, 0 AS verse
					FROM {$verses} v
					LEFT JOIN {$explains} e
						ON e.translation = %s
						AND e.tradition = %s
						AND e.scope = 'chapter'
						AND e.book_id = v.book_id
						AND e.chapter = v.chapter
						AND e.verse = 0
					WHERE v.translation = %s AND e.id IS NULL
					GROUP BY v.book_id, v.chapter
					ORDER BY v.book_id ASC, v.chapter ASC
					LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$translation,
					$tradition,
					$translation,
					$limit,
					$offset
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT v.book_id, v.chapter, v.verse
					FROM {$verses} v
					LEFT JOIN {$explains} e
						ON e.translation = %s
						AND e.tradition = %s
						AND e.scope = 'verse'
						AND e.book_id = v.book_id
						AND e.chapter = v.chapter
						AND e.verse = v.verse
					WHERE v.translation = %s AND e.id IS NULL
					ORDER BY v.book_id ASC, v.chapter ASC, v.verse ASC
					LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$translation,
					$tradition,
					$translation,
					$limit,
					$offset
				),
				ARRAY_A
			);
		}

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			$out[] = array(
				'book_id'     => max( 1, (int) ( $row['book_id'] ?? 0 ) ),
				'chapter'     => max( 1, (int) ( $row['chapter'] ?? 0 ) ),
				'verse'       => ( 'chapter' === $scope ) ? 0 : max( 1, (int) ( $row['verse'] ?? 0 ) ),
				'scope'       => $scope,
				'translation' => $translation,
				'tradition'   => $tradition,
			);
		}
		return $out;
	}

	/**
	 * Inventory of stored explains grouped by translation / tradition / scope.
	 *
	 * @return array<int, array{translation:string,tradition:string,scope:string,total:int,overrides:int,same_as_base:int}>
	 */
	public static function list_inventory_counts() {
		if ( self::$test_backend && method_exists( self::$test_backend, 'list_inventory_counts' ) ) {
			$rows = (array) self::$test_backend->list_inventory_counts();
			return array_values(
				array_filter(
					array_map(
						static function ( $row ) {
							if ( ! is_array( $row ) ) {
								return null;
							}
							$scope = sanitize_key( (string) ( $row['scope'] ?? 'verse' ) );
							if ( 'chapter' !== $scope ) {
								$scope = 'verse';
							}
							return array(
								'translation'  => sanitize_key( (string) ( $row['translation'] ?? '' ) ),
								'tradition'    => sanitize_key( (string) ( $row['tradition'] ?? '' ) ),
								'scope'        => $scope,
								'total'        => max( 0, (int) ( $row['total'] ?? 0 ) ),
								'overrides'    => max( 0, (int) ( $row['overrides'] ?? 0 ) ),
								'same_as_base' => max( 0, (int) ( $row['same_as_base'] ?? 0 ) ),
							);
						},
						$rows
					)
				)
			);
		}

		global $wpdb;
		self::maybe_install_schema();
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT translation, tradition, scope,
				COUNT(*) AS total,
				SUM(CASE WHEN html LIKE '%HWBL_NO_TRADITION_DIFF%' THEN 1 ELSE 0 END) AS same_as_base,
				SUM(CASE WHEN html LIKE '%HWBL_NO_TRADITION_DIFF%' THEN 0 ELSE 1 END) AS overrides
			FROM {$table}
			GROUP BY translation, tradition, scope
			ORDER BY translation ASC, tradition ASC, scope ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			$scope = sanitize_key( (string) ( $row['scope'] ?? 'verse' ) );
			if ( 'chapter' !== $scope ) {
				$scope = 'verse';
			}
			$out[] = array(
				'translation'  => sanitize_key( (string) ( $row['translation'] ?? '' ) ),
				'tradition'    => sanitize_key( (string) ( $row['tradition'] ?? '' ) ),
				'scope'        => $scope,
				'total'        => max( 0, (int) ( $row['total'] ?? 0 ) ),
				'overrides'    => max( 0, (int) ( $row['overrides'] ?? 0 ) ),
				'same_as_base' => max( 0, (int) ( $row['same_as_base'] ?? 0 ) ),
			);
		}
		return $out;
	}

	/**
	 * Import one pack row into the table.
	 *
	 * @param string               $translation Translation.
	 * @param string               $tradition   Tradition.
	 * @param array<string, mixed> $row         Pack row.
	 * @return array{ok:bool,skipped:bool,post_id:int,error:string}
	 */
	public static function import_pack_row( $translation, $tradition, array $row ) {
		$scope = sanitize_key( (string) ( $row['scope'] ?? 'verse' ) );
		if ( 'chapter' !== $scope ) {
			$scope = 'verse';
		}
		$book_id = max( 1, (int) ( $row['book_id'] ?? 0 ) );
		$chapter = max( 1, (int) ( $row['chapter'] ?? 0 ) );
		$verse   = max( 0, (int) ( $row['verse'] ?? 0 ) );
		if ( 'chapter' === $scope ) {
			$verse = 0;
		} elseif ( $verse < 1 ) {
			return array(
				'ok'      => false,
				'skipped' => false,
				'post_id' => 0,
				'error'   => 'verse_required',
			);
		}

		$html = isset( $row['html'] ) ? (string) $row['html'] : '';
		if ( strlen( trim( wp_strip_all_tags( $html ) ) ) < 40 ) {
			return array(
				'ok'      => false,
				'skipped' => false,
				'post_id' => 0,
				'error'   => 'empty_html',
			);
		}

		$payload = array(
			'book_id'     => $book_id,
			'chapter'     => $chapter,
			'verse'       => $verse,
			'translation' => sanitize_key( (string) $translation ),
			'tradition'   => sanitize_key( (string) $tradition ),
			'scope'       => $scope,
			'reference'   => isset( $row['reference'] ) ? (string) $row['reference'] : '',
		);

		$existing = self::find( $payload );
		if ( is_array( $existing ) && self::has_usable_explanation( $existing ) ) {
			return array(
				'ok'      => true,
				'skipped' => true,
				'post_id' => self::item_id( $existing ),
				'error'   => '',
			);
		}

		$saved = self::save( $payload, $html, ! empty( $row['flagged'] ) );
		if ( ! is_array( $saved ) ) {
			return array(
				'ok'      => false,
				'skipped' => false,
				'post_id' => 0,
				'error'   => 'save_failed',
			);
		}

		return array(
			'ok'      => true,
			'skipped' => false,
			'post_id' => self::item_id( $saved ),
			'error'   => '',
		);
	}

	/**
	 * Serialize a stored row (or legacy post) to a pack row.
	 *
	 * @param array<string, mixed>|WP_Post $item Item.
	 * @return array<string, mixed>|null
	 */
	public static function export_pack_row( $item ) {
		if ( $item instanceof WP_Post ) {
			$item = self::row_from_post( $item );
		}
		if ( ! self::is_row( $item ) ) {
			return null;
		}
		$html = self::get_explanation_html( $item );
		if ( strlen( trim( wp_strip_all_tags( $html ) ) ) < 40 ) {
			return null;
		}
		$scope = sanitize_key( (string) ( $item['scope'] ?? 'verse' ) );
		if ( 'chapter' !== $scope ) {
			$scope = 'verse';
		}
		return array(
			'book_id'   => (int) ( $item['book_id'] ?? 0 ),
			'chapter'   => (int) ( $item['chapter'] ?? 0 ),
			'verse'     => (int) ( $item['verse'] ?? 0 ),
			'scope'     => $scope,
			'reference' => (string) ( $item['reference'] ?? '' ),
			'html'      => $html,
			'flagged'   => ! empty( $item['flagged'] ),
		);
	}

	/**
	 * Migrate a batch of legacy CPT explains into the table.
	 */
	public static function maybe_migrate_cpt_batch() {
		if ( self::$test_backend ) {
			return;
		}

		$offset = get_option( self::MIGRATE_OPTION, null );
		if ( false === $offset || null === $offset ) {
			// First run: see if any CPT posts exist.
			$count = (int) wp_count_posts( self::POST_TYPE )->publish;
			if ( $count < 1 ) {
				update_option( self::MIGRATE_OPTION, -1, false );
				return;
			}
			$offset = 0;
		}
		$offset = (int) $offset;
		if ( $offset < 0 ) {
			return;
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 25,
				'offset'                 => $offset,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $query->posts ) ) {
			update_option( self::MIGRATE_OPTION, -1, false );
			return;
		}

		foreach ( $query->posts as $post ) {
			$row = self::row_from_post( $post );
			if ( ! is_array( $row ) || ! self::has_usable_explanation( $row ) ) {
				continue;
			}
			self::save(
				$row,
				(string) $row['html'],
				! empty( $row['flagged'] )
			);
		}

		update_option( self::MIGRATE_OPTION, $offset + count( $query->posts ), false );
	}

	/**
	 * Normalize DB row types.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	private static function normalize_row( array $row ) {
		return array(
			'id'          => (int) ( $row['id'] ?? 0 ),
			'translation' => sanitize_key( (string) ( $row['translation'] ?? '' ) ),
			'tradition'   => sanitize_key( (string) ( $row['tradition'] ?? '' ) ),
			'scope'       => ( 'chapter' === ( $row['scope'] ?? '' ) ) ? 'chapter' : 'verse',
			'book_id'     => (int) ( $row['book_id'] ?? 0 ),
			'chapter'     => (int) ( $row['chapter'] ?? 0 ),
			'verse'       => (int) ( $row['verse'] ?? 0 ),
			'reference'   => (string) ( $row['reference'] ?? '' ),
			'html'        => (string) ( $row['html'] ?? '' ),
			'flagged'     => ! empty( $row['flagged'] ) ? 1 : 0,
			'created_at'  => (string) ( $row['created_at'] ?? '' ),
			'updated_at'  => (string) ( $row['updated_at'] ?? '' ),
		);
	}

	/**
	 * Build a row array from a legacy CPT post.
	 *
	 * @param WP_Post $post Post.
	 * @return array<string, mixed>|null
	 */
	private static function row_from_post( $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			return null;
		}
		$html = self::get_explanation_html( $post );
		return self::normalize_row(
			array(
				'id'          => (int) $post->ID,
				'translation' => (string) get_post_meta( $post->ID, self::META_TRANS, true ),
				'tradition'   => (string) get_post_meta( $post->ID, self::META_TRAD, true ),
				'scope'       => (string) get_post_meta( $post->ID, self::META_SCOPE, true ),
				'book_id'     => (int) get_post_meta( $post->ID, self::META_BOOK, true ),
				'chapter'     => (int) get_post_meta( $post->ID, self::META_CHAP, true ),
				'verse'       => (int) get_post_meta( $post->ID, self::META_VERSE, true ),
				'reference'   => (string) get_post_meta( $post->ID, self::META_REF, true ),
				'html'        => $html,
				'flagged'     => (bool) get_post_meta( $post->ID, self::META_FLAG, true ),
			)
		);
	}

	/**
	 * Find legacy CPT by keys.
	 *
	 * @param array<string, mixed> $keys Keys.
	 * @return WP_Post|null
	 */
	private static function find_cpt_post( array $keys ) {
		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => self::META_BOOK,
						'value' => (string) $keys['book_id'],
					),
					array(
						'key'   => self::META_CHAP,
						'value' => (string) $keys['chapter'],
					),
					array(
						'key'   => self::META_VERSE,
						'value' => (string) $keys['verse'],
					),
					array(
						'key'   => self::META_TRANS,
						'value' => $keys['translation'],
					),
					array(
						'key'   => self::META_SCOPE,
						'value' => $keys['scope'],
					),
					array(
						'key'   => self::META_TRAD,
						'value' => $keys['tradition'],
					),
				),
			)
		);

		if ( empty( $query->posts[0] ) || ! ( $query->posts[0] instanceof WP_Post ) ) {
			return null;
		}
		return $query->posts[0];
	}

	/**
	 * Legacy WP_Query helper (unused by packs; kept for compatibility).
	 *
	 * @param string              $translation Translation.
	 * @param string              $tradition   Tradition.
	 * @param array<int, string>  $scopes      Scopes.
	 * @param array<string,mixed> $args        Args.
	 * @return WP_Query
	 */
	public static function query_for_keys( $translation, $tradition, array $scopes = array(), array $args = array() ) {
		$translation = sanitize_key( (string) $translation );
		$tradition   = sanitize_key( (string) $tradition );
		$scopes      = array_values( array_filter( array_map( 'sanitize_key', $scopes ) ) );

		$meta = array(
			'relation' => 'AND',
			array(
				'key'   => self::META_TRANS,
				'value' => $translation ? $translation : 'default',
			),
			array(
				'key'   => self::META_TRAD,
				'value' => $tradition ? $tradition : 'site',
			),
		);
		if ( ! empty( $scopes ) ) {
			$meta[] = array(
				'key'     => self::META_SCOPE,
				'value'   => $scopes,
				'compare' => 'IN',
			);
		}

		$defaults = array(
			'post_type'              => self::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => 50,
			'paged'                  => 1,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'meta_query'             => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		return new WP_Query( array_merge( $defaults, $args ) );
	}
}
