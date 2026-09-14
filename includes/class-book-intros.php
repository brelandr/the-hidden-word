<?php
/**
 * Book / chapter context intro chips for the Bible reader.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Book_Intros
 */
class HWBL_Book_Intros {

	/**
	 * Cache.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $books = null;

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Data path.
	 *
	 * @return string
	 */
	public static function data_file() {
		return HWBL_PLUGIN_DIR . 'data/book-intros/books.json';
	}

	/**
	 * Load pack.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function load() {
		if ( null !== self::$books ) {
			return self::$books;
		}
		$path = self::data_file();
		if ( ! is_readable( $path ) ) {
			self::$books = array();
			return self::$books;
		}
		$data = json_decode( (string) file_get_contents( $path ), true );
		self::$books = is_array( $data ) ? $data : array();
		return self::$books;
	}

	/**
	 * Get intro for a book (optional chapter church override).
	 *
	 * @param int $book_id Book ID.
	 * @param int $chapter Chapter.
	 * @return array<string, mixed>|null
	 */
	public static function get( $book_id, $chapter = 0 ) {
		$book_id = (int) $book_id;
		$chapter = (int) $chapter;
		$pack    = self::load();
		$key     = (string) $book_id;
		if ( ! isset( $pack[ $key ] ) || ! is_array( $pack[ $key ] ) ) {
			return null;
		}
		$row = $pack[ $key ];
		$out = array(
			'book_id'       => $book_id,
			'chapter'       => $chapter,
			'name'          => class_exists( 'HWBL_Books' ) ? HWBL_Books::get_name( $book_id ) : '',
			'author'        => isset( $row['author'] ) ? (string) $row['author'] : '',
			'audience'      => isset( $row['audience'] ) ? (string) $row['audience'] : '',
			'purpose'       => isset( $row['purpose'] ) ? (string) $row['purpose'] : '',
			'date'          => isset( $row['date'] ) ? (string) $row['date'] : '',
			'blurb'         => isset( $row['blurb'] ) ? (string) $row['blurb'] : '',
			'pack_blurb'    => isset( $row['blurb'] ) ? (string) $row['blurb'] : '',
			'chips'         => array(),
			'source'        => 'pack',
			'church_intro'  => false,
			'church_title'  => '',
			'church_series' => '',
			'church_body'   => '',
		);
		foreach ( array(
			'author'   => __( 'Author', 'hidden-word-bible-lessons' ),
			'audience' => __( 'Audience', 'hidden-word-bible-lessons' ),
			'purpose'  => __( 'Purpose', 'hidden-word-bible-lessons' ),
			'date'     => __( 'Approx. date', 'hidden-word-bible-lessons' ),
		) as $k => $label ) {
			if ( ! empty( $out[ $k ] ) ) {
				$out['chips'][] = array(
					'key'   => $k,
					'label' => $label,
					'value' => $out[ $k ],
				);
			}
		}

		// Prefer published church intro notes for this chapter (verse 0 first, then any intro).
		if ( $chapter > 0 && class_exists( 'HWBL_Pastor_Notes' ) ) {
			$notes      = HWBL_Pastor_Notes::list_published( $book_id, $chapter );
			$church_note = null;
			foreach ( $notes as $note ) {
				if ( empty( $note['note_type'] ) || 'intro' !== $note['note_type'] || empty( $note['body'] ) ) {
					continue;
				}
				if ( 0 === (int) ( $note['verse'] ?? 0 ) ) {
					$church_note = $note;
					break;
				}
				if ( null === $church_note ) {
					$church_note = $note;
				}
			}
			if ( $church_note ) {
				$out['church_intro']  = true;
				$out['source']        = 'church';
				$out['church_title']  = (string) ( $church_note['title'] ?? '' );
				$out['church_series'] = (string) ( $church_note['series'] ?? '' );
				$out['church_body']   = (string) $church_note['body'];
				$out['blurb']         = (string) $church_note['body'];
				array_unshift(
					$out['chips'],
					array(
						'key'   => 'church',
						'label' => __( 'From your church', 'hidden-word-bible-lessons' ),
						'value' => $out['church_title']
							? $out['church_title']
							: __( 'Pastoral intro', 'hidden-word-bible-lessons' ),
					)
				);
			}
		}

		return $out;
	}

	/**
	 * Register REST.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/bible/book-intro',
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
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
				),
			)
		);
	}

	/**
	 * REST handler.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_get( WP_REST_Request $request ) {
		$intro = self::get( (int) $request->get_param( 'book_id' ), (int) $request->get_param( 'chapter' ) );
		if ( ! $intro ) {
			return rest_ensure_response( array( 'intro' => null ) );
		}
		return rest_ensure_response( array( 'intro' => $intro ) );
	}
}
