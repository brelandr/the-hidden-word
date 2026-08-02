<?php
/**
 * OpenBible place lookup + REST for Bible maps.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Bible_Places
 */
class HWBL_Bible_Places {

	const OPTION_ENABLED          = 'hwbl_bible_maps_enabled';
	const OPTION_PROVIDER         = 'hwbl_bible_maps_provider';
	const OPTION_MAPBOX_TOKEN     = 'hwbl_bible_maps_mapbox_token';
	const OPTION_MAPBOX_STYLE     = 'hwbl_bible_maps_mapbox_style';
	const OPTION_MAPBOX_STYLE_KEY = 'hwbl_bible_maps_mapbox_style_key';
	const DEFAULT_MAPBOX_STYLE    = 'mapbox://styles/mapbox/outdoors-v12';
	const DEFAULT_MAPBOX_STYLE_KEY = 'outdoors';
	const ATTRIBUTION_FALLBACK    = 'Geographic data © OpenBible.info (CC BY 4.0)';

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_shortcode( 'hwbl_bible_map', array( __CLASS__, 'render_shortcode' ) );
		add_filter( 'hwbl_bible_reader_features', array( __CLASS__, 'filter_reader_features' ) );
	}

	/**
	 * Expose maps feature on the Bible reader when enabled.
	 *
	 * @param array<string, bool> $features Features.
	 * @return array<string, bool>
	 */
	public static function filter_reader_features( $features ) {
		$features['maps'] = self::is_enabled();
		return $features;
	}

	/**
	 * Whether maps are enabled and index data exists.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( ! (bool) get_option( self::OPTION_ENABLED, true ) ) {
			return false;
		}
		return is_readable( self::meta_path() );
	}

	/**
	 * Active map provider slug.
	 *
	 * @return string leaflet|mapbox
	 */
	public static function get_provider() {
		$provider = sanitize_key( (string) get_option( self::OPTION_PROVIDER, 'leaflet' ) );
		return in_array( $provider, array( 'leaflet', 'mapbox' ), true ) ? $provider : 'leaflet';
	}

	/**
	 * Mapbox access token (empty when unused).
	 *
	 * @return string
	 */
	public static function get_mapbox_token() {
		return (string) get_option( self::OPTION_MAPBOX_TOKEN, '' );
	}

	/**
	 * Curated Mapbox style presets for Bible study (not driving-centric streets).
	 *
	 * @return array<string, array{url:string,label:string}>
	 */
	public static function get_mapbox_style_presets() {
		return array(
			'outdoors'          => array(
				'url'   => 'mapbox://styles/mapbox/outdoors-v12',
				'label' => __( 'Outdoors — terrain & journeys', 'hidden-word-bible-lessons' ),
			),
			'light'             => array(
				'url'   => 'mapbox://styles/mapbox/light-v11',
				'label' => __( 'Light — clear markers', 'hidden-word-bible-lessons' ),
			),
			'satellite_streets' => array(
				'url'   => 'mapbox://styles/mapbox/satellite-streets-v12',
				'label' => __( 'Satellite + labels', 'hidden-word-bible-lessons' ),
			),
			'satellite'         => array(
				'url'   => 'mapbox://styles/mapbox/satellite-v9',
				'label' => __( 'Satellite', 'hidden-word-bible-lessons' ),
			),
			'streets'           => array(
				'url'   => 'mapbox://styles/mapbox/streets-v12',
				'label' => __( 'Streets', 'hidden-word-bible-lessons' ),
			),
			'custom'            => array(
				'url'   => '',
				'label' => __( 'Custom (Mapbox Studio)', 'hidden-word-bible-lessons' ),
			),
		);
	}

	/**
	 * Active Mapbox style preset key.
	 *
	 * @return string
	 */
	public static function get_mapbox_style_key() {
		$key     = sanitize_key( (string) get_option( self::OPTION_MAPBOX_STYLE_KEY, self::DEFAULT_MAPBOX_STYLE_KEY ) );
		$presets = self::get_mapbox_style_presets();
		if ( isset( $presets[ $key ] ) ) {
			return $key;
		}

		// Migrate legacy free-text style URL into a known preset when possible.
		$legacy = trim( (string) get_option( self::OPTION_MAPBOX_STYLE, '' ) );
		foreach ( $presets as $preset_key => $preset ) {
			if ( 'custom' === $preset_key ) {
				continue;
			}
			if ( $legacy && $legacy === $preset['url'] ) {
				return $preset_key;
			}
		}
		if ( $legacy && self::sanitize_mapbox_style_url( $legacy ) ) {
			return 'custom';
		}

		return self::DEFAULT_MAPBOX_STYLE_KEY;
	}

	/**
	 * Sanitize a Mapbox style URL (Studio or stock).
	 *
	 * @param string $url Raw URL.
	 * @return string Empty when invalid.
	 */
	public static function sanitize_mapbox_style_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		if ( preg_match( '#^mapbox://styles/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+$#', $url ) ) {
			return $url;
		}
		if ( preg_match( '#^https://api\.mapbox\.com/styles/v1/[A-Za-z0-9_-]+/[A-Za-z0-9_-]+#', $url ) ) {
			return esc_url_raw( $url );
		}
		return '';
	}

	/**
	 * Mapbox style URL resolved from preset key (+ custom URL when selected).
	 *
	 * @return string
	 */
	public static function get_mapbox_style() {
		$key     = self::get_mapbox_style_key();
		$presets = self::get_mapbox_style_presets();

		if ( 'custom' === $key ) {
			$custom = self::sanitize_mapbox_style_url( (string) get_option( self::OPTION_MAPBOX_STYLE, '' ) );
			return $custom ? $custom : self::DEFAULT_MAPBOX_STYLE;
		}

		if ( isset( $presets[ $key ]['url'] ) && $presets[ $key ]['url'] ) {
			return $presets[ $key ]['url'];
		}

		return self::DEFAULT_MAPBOX_STYLE;
	}

	/**
	 * Styles available to the front-end switcher (presets + custom when configured).
	 *
	 * @return array<int, array{key:string,url:string,label:string}>
	 */
	public static function get_front_mapbox_styles() {
		$out     = array();
		$presets = self::get_mapbox_style_presets();
		$custom  = self::sanitize_mapbox_style_url( (string) get_option( self::OPTION_MAPBOX_STYLE, '' ) );

		foreach ( $presets as $key => $preset ) {
			if ( 'custom' === $key ) {
				if ( ! $custom ) {
					continue;
				}
				$out[] = array(
					'key'   => 'custom',
					'url'   => $custom,
					'label' => $preset['label'],
				);
				continue;
			}
			$out[] = array(
				'key'   => $key,
				'url'   => $preset['url'],
				'label' => $preset['label'],
			);
		}

		return $out;
	}

	/**
	 * Public config for front-end scripts.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_front_config() {
		$provider = self::get_provider();
		$config   = array(
			'enabled'     => self::is_enabled(),
			'provider'    => $provider,
			'attribution' => self::get_attribution(),
			'restUrl'     => rest_url( 'hwbl/v1/bible/places' ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
		);

		if ( 'mapbox' === $provider ) {
			$config['mapboxToken']    = self::get_mapbox_token();
			$config['mapboxStyle']    = self::get_mapbox_style();
			$config['mapboxStyleKey'] = self::get_mapbox_style_key();
			$config['mapboxStyles']   = self::get_front_mapbox_styles();
		}

		return $config;
	}

	/**
	 * Attribution string from index meta.
	 *
	 * @return string
	 */
	public static function get_attribution() {
		$meta = self::get_meta();
		if ( ! empty( $meta['attribution'] ) ) {
			return (string) $meta['attribution'];
		}
		return self::ATTRIBUTION_FALLBACK;
	}

	/**
	 * Path to data directory.
	 *
	 * @return string
	 */
	public static function data_dir() {
		return HWBL_PLUGIN_DIR . 'data/bible-geo/';
	}

	/**
	 * Path to index-meta.json.
	 *
	 * @return string
	 */
	public static function meta_path() {
		return self::data_dir() . 'index-meta.json';
	}

	/**
	 * Load index meta (cached per request).
	 *
	 * @return array<string, mixed>
	 */
	public static function get_meta() {
		static $meta = null;
		if ( null !== $meta ) {
			return $meta;
		}
		$path = self::meta_path();
		if ( ! is_readable( $path ) ) {
			$meta = array();
			return $meta;
		}
		$decoded = json_decode( (string) file_get_contents( $path ), true );
		$meta    = is_array( $decoded ) ? $decoded : array();
		return $meta;
	}

	/**
	 * Build OpenBible BBCCCVVV sort key.
	 *
	 * @param int $book_id Book ID 1–66.
	 * @param int $chapter Chapter.
	 * @param int $verse   Verse.
	 * @return string
	 */
	public static function sort_key( $book_id, $chapter, $verse ) {
		return sprintf( '%02d%03d%03d', absint( $book_id ), absint( $chapter ), absint( $verse ) );
	}

	/**
	 * Load one book shard.
	 *
	 * @param int $book_id Book ID.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public static function load_book_shard( $book_id ) {
		$book_id = absint( $book_id );
		if ( $book_id < 1 || $book_id > 66 ) {
			return array();
		}
		$path = self::data_dir() . 'by-book/' . sprintf( '%02d', $book_id ) . '.json';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$decoded = json_decode( (string) file_get_contents( $path ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Places for a verse or whole chapter.
	 *
	 * @param int $book_id Book ID.
	 * @param int $chapter Chapter.
	 * @param int $verse   Verse (0 = entire chapter).
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_places( $book_id, $chapter, $verse = 0 ) {
		$book_id = absint( $book_id );
		$chapter = absint( $chapter );
		$verse   = absint( $verse );
		if ( $book_id < 1 || $chapter < 1 ) {
			return array();
		}

		$shard = self::load_book_shard( $book_id );
		if ( empty( $shard ) ) {
			return array();
		}

		$collected = array();

		if ( $verse > 0 ) {
			$key  = self::sort_key( $book_id, $chapter, $verse );
			$rows = isset( $shard[ $key ] ) && is_array( $shard[ $key ] ) ? $shard[ $key ] : array();
			self::collect_places_from_rows( $collected, $rows, $key );
			return self::finalize_collected_places( $collected );
		}

		$prefix = sprintf( '%02d%03d', $book_id, $chapter );
		foreach ( $shard as $key => $rows ) {
			if ( 0 !== strpos( (string) $key, $prefix ) || ! is_array( $rows ) ) {
				continue;
			}
			self::collect_places_from_rows( $collected, $rows, (string) $key );
		}

		return self::finalize_collected_places( $collected );
	}

	/**
	 * Unique places for an entire book (deduped by place id).
	 *
	 * @param int $book_id Book ID 1–66.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_places_for_book( $book_id ) {
		$book_id = absint( $book_id );
		if ( $book_id < 1 || $book_id > 66 ) {
			return array();
		}

		$shard = self::load_book_shard( $book_id );
		if ( empty( $shard ) ) {
			return array();
		}

		$collected = array();
		foreach ( $shard as $key => $rows ) {
			if ( ! is_array( $rows ) ) {
				continue;
			}
			self::collect_places_from_rows( $collected, $rows, (string) $key );
		}

		return self::finalize_collected_places( $collected );
	}

	/**
	 * Accumulate place rows keyed by id, tracking OpenBible sort keys.
	 *
	 * @param array<string, array{row:array<string,mixed>,keys:array<int,string>}> $collected Collector.
	 * @param array<int, array<string, mixed>>                                      $rows      Place rows.
	 * @param string                                                                $sort_key  BBCCCVVV key.
	 */
	private static function collect_places_from_rows( &$collected, $rows, $sort_key ) {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id = isset( $row['id'] ) ? (string) $row['id'] : '';
			if ( '' === $id ) {
				continue;
			}
			if ( ! isset( $collected[ $id ] ) ) {
				$collected[ $id ] = array(
					'row'  => $row,
					'keys' => array(),
				);
			}
			if ( preg_match( '/^\d{8}$/', $sort_key ) ) {
				$collected[ $id ]['keys'][] = $sort_key;
			}
		}
	}

	/**
	 * Normalize + sort collected places.
	 *
	 * @param array<string, array{row:array<string,mixed>,keys:array<int,string>}> $collected Collector.
	 * @return array<int, array<string, mixed>>
	 */
	private static function finalize_collected_places( $collected ) {
		$places = array();
		foreach ( $collected as $item ) {
			$places[] = self::normalize_place( $item['row'], $item['keys'] );
		}

		usort(
			$places,
			static function ( $a, $b ) {
				return (int) ( $b['confidence_score'] ?? 0 ) - (int) ( $a['confidence_score'] ?? 0 );
			}
		);

		return $places;
	}

	/**
	 * Collapse OpenBible sort keys into readable refs (e.g. John 4:3-5).
	 *
	 * @param array<int, string> $sort_keys BBCCCVVV keys.
	 * @return array<int, string>
	 */
	public static function format_verse_refs_from_sort_keys( $sort_keys ) {
		$parsed = array();
		foreach ( (array) $sort_keys as $key ) {
			$key = (string) $key;
			if ( ! preg_match( '/^\d{8}$/', $key ) ) {
				continue;
			}
			$parsed[] = array(
				'book'    => (int) substr( $key, 0, 2 ),
				'chapter' => (int) substr( $key, 2, 3 ),
				'verse'   => (int) substr( $key, 5, 3 ),
				'key'     => $key,
			);
		}

		if ( empty( $parsed ) ) {
			return array();
		}

		usort(
			$parsed,
			static function ( $a, $b ) {
				return strcmp( $a['key'], $b['key'] );
			}
		);

		// Deduplicate identical verses.
		$unique = array();
		$seen   = array();
		foreach ( $parsed as $item ) {
			if ( isset( $seen[ $item['key'] ] ) ) {
				continue;
			}
			$seen[ $item['key'] ] = true;
			$unique[]             = $item;
		}

		$refs  = array();
		$count = count( $unique );
		$i     = 0;
		while ( $i < $count ) {
			$start = $unique[ $i ];
			$end   = $start;
			$j     = $i + 1;
			while ( $j < $count ) {
				$next = $unique[ $j ];
				if (
					$next['book'] === $end['book']
					&& $next['chapter'] === $end['chapter']
					&& $next['verse'] === $end['verse'] + 1
				) {
					$end = $next;
					++$j;
					continue;
				}
				break;
			}

			if ( class_exists( 'HWBL_Books' ) ) {
				$refs[] = HWBL_Books::format_reference(
					$start['book'],
					$start['chapter'],
					$start['verse'],
					$end['verse'] !== $start['verse'] ? $end['verse'] : 0
				);
			} else {
				$label = $start['chapter'] . ':' . $start['verse'];
				if ( $end['verse'] !== $start['verse'] ) {
					$label .= '-' . $end['verse'];
				}
				$refs[] = $label;
			}
			$i = $j;
		}

		return $refs;
	}

	/**
	 * Normalize a place row for API output.
	 *
	 * @param array<string, mixed> $row       Raw row.
	 * @param array<int, string>   $sort_keys Optional OpenBible sort keys for this place in scope.
	 * @return array<string, mixed>
	 */
	private static function normalize_place( $row, $sort_keys = array() ) {
		$verses = self::format_verse_refs_from_sort_keys( $sort_keys );

		return array(
			'id'               => isset( $row['id'] ) ? (string) $row['id'] : '',
			'name'             => isset( $row['name'] ) ? (string) $row['name'] : '',
			'lat'              => isset( $row['lat'] ) ? (float) $row['lat'] : 0.0,
			'lng'              => isset( $row['lng'] ) ? (float) $row['lng'] : 0.0,
			'confidence'       => isset( $row['confidence'] ) ? (string) $row['confidence'] : 'unknown',
			'confidence_score' => isset( $row['confidence_score'] ) ? (int) $row['confidence_score'] : 0,
			'modern_name'      => isset( $row['modern_name'] ) ? (string) $row['modern_name'] : '',
			'country'          => isset( $row['country'] ) ? (string) $row['country'] : '',
			'types'            => isset( $row['types'] ) && is_array( $row['types'] ) ? array_values( $row['types'] ) : array(),
			'verses'           => $verses,
		);
	}

	/**
	 * Register REST route.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'hwbl/v1',
			'/bible/places',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_places' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'book_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'chapter' => array(
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
					'verse'   => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
					'scope'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => 'passage',
						'enum'              => array( 'passage', 'book' ),
					),
				),
			)
		);
	}

	/**
	 * REST callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_places( $request ) {
		$book_id = absint( $request->get_param( 'book_id' ) );
		$chapter = absint( $request->get_param( 'chapter' ) );
		$verse   = absint( $request->get_param( 'verse' ) );
		$scope   = sanitize_key( (string) $request->get_param( 'scope' ) );
		if ( 'book' !== $scope ) {
			$scope = 'passage';
		}

		if ( $book_id < 1 || $book_id > 66 ) {
			return new WP_Error(
				'hwbl_bible_places_invalid',
				__( 'Invalid book or chapter.', 'hidden-word-bible-lessons' ),
				array( 'status' => 400 )
			);
		}

		if ( 'book' === $scope ) {
			$places = self::get_places_for_book( $book_id );
		} else {
			if ( $chapter < 1 ) {
				return new WP_Error(
					'hwbl_bible_places_invalid',
					__( 'Invalid book or chapter.', 'hidden-word-bible-lessons' ),
					array( 'status' => 400 )
				);
			}
			$places = self::get_places( $book_id, $chapter, $verse );
		}

		return rest_ensure_response(
			array(
				'book_id'     => $book_id,
				'chapter'     => $chapter,
				'verse'       => $verse,
				'scope'       => $scope,
				'places'      => $places,
				'count'       => count( $places ),
				'attribution' => self::get_attribution(),
				'provider'    => self::get_provider(),
				'source_url'  => 'https://github.com/openbibleinfo/Bible-Geocoding-Data',
			)
		);
	}

	/**
	 * Register map vendor + widget scripts.
	 */
	public static function register_assets() {
		$leaflet_js = HWBL_PLUGIN_DIR . 'public/vendor/leaflet/leaflet.js';
		$mapbox_js  = HWBL_PLUGIN_DIR . 'public/vendor/mapbox-gl/mapbox-gl.js';

		if ( is_readable( $leaflet_js ) ) {
			wp_register_style(
				'hwbl-leaflet',
				HWBL_PLUGIN_URL . 'public/vendor/leaflet/leaflet.css',
				array(),
				'1.9.4'
			);
			wp_register_script(
				'hwbl-leaflet',
				HWBL_PLUGIN_URL . 'public/vendor/leaflet/leaflet.js',
				array(),
				'1.9.4',
				true
			);
		}

		if ( is_readable( $mapbox_js ) ) {
			wp_register_style(
				'hwbl-mapbox-gl',
				HWBL_PLUGIN_URL . 'public/vendor/mapbox-gl/mapbox-gl.css',
				array(),
				'3.9.4'
			);
			wp_register_script(
				'hwbl-mapbox-gl',
				HWBL_PLUGIN_URL . 'public/vendor/mapbox-gl/mapbox-gl.js',
				array(),
				'3.9.4',
				true
			);
		}

		$style_deps = array();
		$script_deps = array();
		if ( 'mapbox' === self::get_provider() && wp_script_is( 'hwbl-mapbox-gl', 'registered' ) ) {
			$style_deps[]  = 'hwbl-mapbox-gl';
			$script_deps[] = 'hwbl-mapbox-gl';
		} elseif ( wp_script_is( 'hwbl-leaflet', 'registered' ) ) {
			$style_deps[]  = 'hwbl-leaflet';
			$script_deps[] = 'hwbl-leaflet';
		}

		wp_register_style(
			'hwbl-bible-map',
			HWBL_PLUGIN_URL . 'public/css/bible-map.css',
			$style_deps,
			HWBL_VERSION
		);

		wp_register_script(
			'hwbl-bible-map',
			HWBL_PLUGIN_URL . 'public/js/bible-map.js',
			$script_deps,
			HWBL_VERSION,
			true
		);
	}

	/**
	 * Enqueue assets for the active provider.
	 */
	public static function enqueue_assets() {
		self::register_assets();

		$provider = self::get_provider();
		if ( 'mapbox' === $provider && wp_script_is( 'hwbl-mapbox-gl', 'registered' ) ) {
			wp_enqueue_style( 'hwbl-mapbox-gl' );
			wp_enqueue_script( 'hwbl-mapbox-gl' );
		} elseif ( wp_script_is( 'hwbl-leaflet', 'registered' ) ) {
			wp_enqueue_style( 'hwbl-leaflet' );
			wp_enqueue_script( 'hwbl-leaflet' );
		}

		wp_enqueue_style( 'hwbl-bible-map' );
		wp_enqueue_script( 'hwbl-bible-map' );
		wp_localize_script( 'hwbl-bible-map', 'hwblBibleMap', self::get_front_config() );
	}

	/**
	 * Shortcode [hwbl_bible_map].
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts = array() ) {
		if ( ! self::is_enabled() ) {
			return '<p class="hwbl-bible-map-notice">' . esc_html__( 'Bible maps are disabled or data is missing.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		if ( 'mapbox' === self::get_provider() && '' === self::get_mapbox_token() ) {
			return '<p class="hwbl-bible-map-notice">' . esc_html__( 'Mapbox is selected but no access token is configured in Settings.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'book'    => 43,
				'chapter' => 3,
				'verse'   => 16,
				'height'  => 320,
				'scope'   => 'verse',
			),
			$atts,
			'hwbl_bible_map'
		);

		$book_id = max( 1, absint( $atts['book'] ) );
		$chapter = max( 1, absint( $atts['chapter'] ) );
		$verse   = max( 0, absint( $atts['verse'] ) );
		$height  = max( 180, absint( $atts['height'] ) );
		$scope = sanitize_key( (string) $atts['scope'] );
		if ( 'book' === $scope ) {
			$data_scope = 'book';
		} else {
			if ( 'chapter' === $scope ) {
				$verse = 0;
			}
			$data_scope = 'passage';
		}

		self::enqueue_assets();

		ob_start();
		?>
		<div
			class="hwbl-bible-map"
			data-book="<?php echo esc_attr( (string) $book_id ); ?>"
			data-chapter="<?php echo esc_attr( (string) $chapter ); ?>"
			data-verse="<?php echo esc_attr( (string) $verse ); ?>"
			data-scope="<?php echo esc_attr( $data_scope ); ?>"
			style="--hwbl-bible-map-height: <?php echo esc_attr( (string) $height ); ?>px;"
		>
			<div class="hwbl-bible-map__canvas" role="img" aria-label="<?php esc_attr_e( 'Map of biblical places', 'hidden-word-bible-lessons' ); ?>"></div>
			<details class="hwbl-bible-map__places">
				<summary class="hwbl-bible-map__places-summary"><?php esc_html_e( 'Places list', 'hidden-word-bible-lessons' ); ?></summary>
				<ul class="hwbl-bible-map__list" aria-live="polite"></ul>
			</details>
			<p class="hwbl-bible-map__status" role="status"></p>
			<p class="hwbl-bible-map__attribution"><?php echo esc_html( self::get_attribution() ); ?></p>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
