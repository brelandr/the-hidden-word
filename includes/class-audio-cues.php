<?php
/**
 * Chapter audio verse-cue metadata for read-along highlight.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Audio_Cues
 */
class HWBL_Audio_Cues {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST.
	 */
	public static function register_routes() {
		register_rest_route(
			'hwbl/v1',
			'/bible/audio-cues',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_get' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'book_id'     => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'chapter'     => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'translation' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
					'narrator'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
					'duration'    => array(
						'type'              => 'number',
						'sanitize_callback' => static function ( $v ) {
							return (float) $v;
						},
						'default'           => 0,
					),
				),
			)
		);
	}

	/**
	 * Convert Hello AO start-time list into cue ranges.
	 *
	 * @param array<int, float> $starts Start seconds per verse (1-indexed order).
	 * @param float             $duration Optional total duration.
	 * @return array{cues:array<int,array{verse:int,start:float,end:float}>,duration:float}
	 */
	public static function cues_from_starts( array $starts, $duration = 0.0 ) {
		$cues     = array();
		$duration = (float) $duration;
		$count    = count( $starts );
		if ( $count < 1 ) {
			return array(
				'cues'     => array(),
				'duration' => $duration,
			);
		}
		if ( $duration <= 0 ) {
			$duration = (float) $starts[ $count - 1 ] + 8.0;
		}
		for ( $i = 0; $i < $count; $i++ ) {
			$start = (float) $starts[ $i ];
			$end   = ( $i + 1 < $count ) ? (float) $starts[ $i + 1 ] : $duration;
			if ( $end <= $start ) {
				$end = $start + 2.0;
			}
			$cues[] = array(
				'verse' => $i + 1,
				'start' => round( $start, 3 ),
				'end'   => round( $end, 3 ),
			);
		}
		return array(
			'cues'     => $cues,
			'duration' => $duration,
		);
	}

	/**
	 * Build cues: local pack → Hello AO timings → character-length approx.
	 *
	 * @param int    $book_id     Book.
	 * @param int    $chapter     Chapter.
	 * @param string $translation Translation slug.
	 * @param float  $duration    Optional total seconds.
	 * @param string $narrator    Optional narrator.
	 * @return array<string, mixed>
	 */
	public static function build_cues( $book_id, $chapter, $translation = '', $duration = 0.0, $narrator = '' ) {
		$book_id     = (int) $book_id;
		$chapter     = (int) $chapter;
		$translation = sanitize_key( $translation );
		$duration    = (float) $duration;
		$narrator    = sanitize_key( $narrator );
		$verses      = array();

		if ( class_exists( 'HWBL_Bible_Reader' ) ) {
			$payload = HWBL_Bible_Reader::get_chapter( $book_id, $chapter, $translation );
			if ( is_array( $payload ) && ! empty( $payload['verses'] ) && is_array( $payload['verses'] ) ) {
				foreach ( $payload['verses'] as $v ) {
					if ( ! is_array( $v ) ) {
						continue;
					}
					$num  = isset( $v['number'] ) ? (int) $v['number'] : 0;
					$text = isset( $v['text'] ) ? (string) $v['text'] : '';
					if ( $num > 0 ) {
						$verses[] = array(
							'number' => $num,
							'chars'  => max( 1, strlen( $text ) ),
						);
					}
				}
			}
		}

		// Timing pack override (optional JSON).
		$pack_path = HWBL_PLUGIN_DIR . 'data/audio-cues/' . $book_id . '-' . $chapter . '.json';
		if ( is_readable( $pack_path ) ) {
			$pack = json_decode( (string) file_get_contents( $pack_path ), true );
			if ( is_array( $pack ) && ! empty( $pack['cues'] ) && is_array( $pack['cues'] ) ) {
				return array(
					'book_id'     => $book_id,
					'chapter'     => $chapter,
					'translation' => $translation,
					'narrator'    => $narrator,
					'source'      => 'pack',
					'duration'    => isset( $pack['duration'] ) ? (float) $pack['duration'] : $duration,
					'cues'        => array_values( $pack['cues'] ),
				);
			}
		}

		// Hello AO real timings when available.
		if ( class_exists( 'HWBL_HelloAO_Provider' ) && HWBL_HelloAO_Provider::is_enabled() ) {
			$timing_translation = $translation;
			if ( ! HWBL_HelloAO_Provider::get_helloao_id( $timing_translation ) ) {
				$timing_translation = 'bsb';
			}
			$remote = HWBL_HelloAO_Provider::fetch_verse_timings( $book_id, $chapter, $timing_translation, $narrator ? $narrator : 'david' );
			if ( is_array( $remote ) && ! empty( $remote['starts'] ) ) {
				$built = self::cues_from_starts( $remote['starts'], $duration > 0 ? $duration : (float) $remote['duration'] );
				// Align cue verse numbers to actual chapter verse numbers when counts match.
				if ( count( $built['cues'] ) === count( $verses ) ) {
					foreach ( $built['cues'] as $i => $cue ) {
						$built['cues'][ $i ]['verse'] = $verses[ $i ]['number'];
					}
				}
				return array(
					'book_id'     => $book_id,
					'chapter'     => $chapter,
					'translation' => $timing_translation,
					'narrator'    => isset( $remote['narrator'] ) ? (string) $remote['narrator'] : $narrator,
					'source'      => 'helloao',
					'duration'    => $built['duration'],
					'cues'        => $built['cues'],
				);
			}
		}

		$total_chars = 0;
		foreach ( $verses as $v ) {
			$total_chars += $v['chars'];
		}
		if ( $total_chars < 1 ) {
			return array(
				'book_id'     => $book_id,
				'chapter'     => $chapter,
				'translation' => $translation,
				'narrator'    => $narrator,
				'source'      => 'none',
				'duration'    => $duration,
				'cues'        => array(),
			);
		}

		if ( $duration <= 0 ) {
			$duration = max( 30.0, $total_chars / 14.0 );
		}

		$cues   = array();
		$cursor = 0.0;
		foreach ( $verses as $v ) {
			$frac   = $v['chars'] / $total_chars;
			$span   = $duration * $frac;
			$cues[] = array(
				'verse' => $v['number'],
				'start' => round( $cursor, 3 ),
				'end'   => round( $cursor + $span, 3 ),
			);
			$cursor += $span;
		}

		return array(
			'book_id'     => $book_id,
			'chapter'     => $chapter,
			'translation' => $translation,
			'narrator'    => $narrator,
			'source'      => 'approx',
			'duration'    => $duration,
			'cues'        => $cues,
		);
	}

	/**
	 * REST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_get( WP_REST_Request $request ) {
		return rest_ensure_response(
			self::build_cues(
				(int) $request->get_param( 'book_id' ),
				(int) $request->get_param( 'chapter' ),
				(string) $request->get_param( 'translation' ),
				(float) $request->get_param( 'duration' ),
				(string) $request->get_param( 'narrator' )
			)
		);
	}
}
