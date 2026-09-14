<?php
/**
 * Public-domain style cross-reference index for the Bible reader.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Bible_Cross_Refs
 */
class HWBL_Bible_Cross_Refs {

	/**
	 * In-request cache.
	 *
	 * @var array<string, array<int, array<string, mixed>>>|null
	 */
	private static $index = null;

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Data directory.
	 *
	 * @return string
	 */
	public static function data_dir() {
		return HWBL_PLUGIN_DIR . 'data/cross-refs/';
	}

	/**
	 * Whether pack is readable.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return is_readable( self::data_dir() . 'index.json.gz' )
			|| is_readable( self::data_dir() . 'index.json' );
	}

	/**
	 * Load index (gzip preferred).
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public static function load_index() {
		if ( null !== self::$index ) {
			return self::$index;
		}
		$dir  = self::data_dir();
		$gz   = $dir . 'index.json.gz';
		$json = $dir . 'index.json';
		$raw  = '';
		if ( is_readable( $gz ) ) {
			$raw = (string) file_get_contents( $gz );
			if ( function_exists( 'gzdecode' ) ) {
				$decoded = @gzdecode( $raw ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$raw     = false !== $decoded ? (string) $decoded : '';
			} else {
				$raw = '';
			}
		}
		if ( '' === $raw && is_readable( $json ) ) {
			$raw = (string) file_get_contents( $json );
		}
		$data        = $raw ? json_decode( $raw, true ) : null;
		self::$index = is_array( $data ) ? $data : array();
		return self::$index;
	}

	/**
	 * Key for a reference.
	 *
	 * @param int $book_id Book ID.
	 * @param int $chapter Chapter.
	 * @param int $verse   Verse.
	 * @return string
	 */
	public static function key( $book_id, $chapter, $verse ) {
		return (int) $book_id . '-' . (int) $chapter . '-' . (int) $verse;
	}

	/**
	 * Get cross-refs for a verse.
	 *
	 * @param int $book_id Book ID.
	 * @param int $chapter Chapter.
	 * @param int $verse   Verse.
	 * @return array<int, array{book_id:int,chapter:int,verse:int,reference:string}>
	 */
	public static function get_refs( $book_id, $chapter, $verse ) {
		$book_id = (int) $book_id;
		$chapter = (int) $chapter;
		$verse   = (int) $verse;
		if ( $book_id < 1 || $chapter < 1 || $verse < 1 ) {
			return array();
		}
		$index = self::load_index();
		$key   = self::key( $book_id, $chapter, $verse );
		$rows  = isset( $index[ $key ] ) && is_array( $index[ $key ] ) ? $index[ $key ] : array();
		$out   = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$b = isset( $row['book_id'] ) ? (int) $row['book_id'] : 0;
			$c = isset( $row['chapter'] ) ? (int) $row['chapter'] : 0;
			$v = isset( $row['verse'] ) ? (int) $row['verse'] : 0;
			if ( $b < 1 || $c < 1 || $v < 1 ) {
				continue;
			}
			$out[] = array(
				'book_id'   => $b,
				'chapter'   => $c,
				'verse'     => $v,
				'reference' => class_exists( 'HWBL_Books' )
					? HWBL_Books::format_reference( $b, $c, $v )
					: ( $b . ' ' . $c . ':' . $v ),
			);
		}
		return $out;
	}

	/**
	 * Register REST.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/bible/cross-refs',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'book_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'chapter' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'verse'   => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * REST handler.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_get( WP_REST_Request $request ) {
		$book_id = (int) $request->get_param( 'book_id' );
		$chapter = (int) $request->get_param( 'chapter' );
		$verse   = (int) $request->get_param( 'verse' );
		$refs    = self::get_refs( $book_id, $chapter, $verse );
		$meta    = array();
		$meta_path = self::data_dir() . 'meta.json';
		if ( is_readable( $meta_path ) ) {
			$decoded = json_decode( (string) file_get_contents( $meta_path ), true );
			if ( is_array( $decoded ) ) {
				$meta = $decoded;
			}
		}
		return rest_ensure_response(
			array(
				'book_id'     => $book_id,
				'chapter'     => $chapter,
				'verse'       => $verse,
				'reference'   => class_exists( 'HWBL_Books' )
					? HWBL_Books::format_reference( $book_id, $chapter, $verse )
					: '',
				'refs'        => $refs,
				'available'   => self::is_available(),
				'attribution' => isset( $meta['attribution'] ) ? (string) $meta['attribution'] : '',
			)
		);
	}
}
