<?php
/**
 * Bible concordance study (local FULLTEXT + Biblia for licensed translations).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Bible_Concordance
 */
class HWBL_Bible_Concordance {

	const OPTION_ENABLED = 'hwbl_bible_concordance_enabled';

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_shortcode( 'hwbl_bible_concordance', array( __CLASS__, 'render_shortcode' ) );
		add_filter( 'hwbl_bible_reader_features', array( __CLASS__, 'filter_reader_features' ) );
	}

	/**
	 * Expose concordance on the Bible reader when enabled.
	 *
	 * @param array<string, bool> $features Features.
	 * @return array<string, bool>
	 */
	public static function filter_reader_features( $features ) {
		$features['concordance'] = self::is_enabled();
		return $features;
	}

	/**
	 * Whether concordance is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) get_option( self::OPTION_ENABLED, true );
	}

	/**
	 * Translations available for concordance UI.
	 *
	 * @return array<string, array{label:string,backend:string}>
	 */
	public static function get_available_translations() {
		$out = array();

		if ( class_exists( 'HWBL_Local_Bible_Store' ) ) {
			foreach ( HWBL_Local_Bible_Store::get_installed_labels() as $slug => $label ) {
				$out[ $slug ] = array(
					'label'   => $label,
					'backend' => 'local',
				);
			}
		}

		// Licensed / remote search via Biblia when configured.
		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() ) {
			$reader = class_exists( 'HWBL_Bible_Reader' ) ? HWBL_Bible_Reader::get_reader_translations() : array();
			foreach ( $reader as $slug => $label ) {
				if ( isset( $out[ $slug ] ) ) {
					continue;
				}
				if ( ! THW_Premium_Biblia::is_translation_accessible( $slug ) ) {
					continue;
				}
				$out[ $slug ] = array(
					'label'   => (string) $label,
					'backend' => 'biblia',
				);
			}
			// Ensure common licensed slugs appear even if not in reader list yet.
			foreach ( array( 'niv' => 'NIV', 'nlt' => 'NLT', 'esv' => 'ESV', 'nasb' => 'NASB' ) as $slug => $label ) {
				if ( isset( $out[ $slug ] ) ) {
					continue;
				}
				if ( THW_Premium_Biblia::is_translation_accessible( $slug ) ) {
					$out[ $slug ] = array(
						'label'   => $label,
						'backend' => 'biblia',
					);
				}
			}
		}

		return $out;
	}

	/**
	 * Resolve search backend for a translation.
	 *
	 * @param string $translation Translation slug.
	 * @return string local|biblia|unavailable
	 */
	public static function resolve_backend( $translation ) {
		$translation = sanitize_key( (string) $translation );
		if ( '' === $translation ) {
			return 'unavailable';
		}
		if ( class_exists( 'HWBL_Local_Bible_Store' ) && HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return 'local';
		}
		if ( class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available() && THW_Premium_Biblia::is_translation_accessible( $translation ) ) {
			return 'biblia';
		}
		return 'unavailable';
	}

	/**
	 * Run a concordance lookup.
	 *
	 * @param string $query       Search query.
	 * @param string $translation Translation slug.
	 * @param int    $limit       Max hits.
	 * @param string $testament   ot|nt|''.
	 * @return array<string, mixed>
	 */
	public static function lookup( $query, $translation = '', $limit = 50, $testament = '' ) {
		if ( class_exists( 'HWBL_Bible_Text_Search' ) ) {
			$query = HWBL_Bible_Text_Search::normalize_query( $query );
		} else {
			$query = trim( (string) $query );
		}

		$limit = max( 1, min( 100, (int) $limit ) );
		$testament = sanitize_key( (string) $testament );
		if ( ! in_array( $testament, array( 'ot', 'nt' ), true ) ) {
			$testament = '';
		}

		$available = self::get_available_translations();
		$translation = sanitize_key( (string) $translation );
		if ( '' === $translation || ! isset( $available[ $translation ] ) ) {
			$keys = array_keys( $available );
			$translation = ! empty( $keys ) ? (string) $keys[0] : '';
		}

		$payload = array(
			'query'                 => $query,
			'translation'           => $translation,
			'requested_translation' => $translation,
			'testament'             => $testament,
			'backend'               => 'unavailable',
			'count'                 => 0,
			'truncated'               => false,
			'results'               => array(),
			'error'                 => '',
			'message'               => '',
		);

		if ( '' === $query ) {
			$payload['error']   = 'invalid_query';
			$payload['message'] = __( 'Enter a word or phrase to search.', 'hidden-word-bible-lessons' );
			return $payload;
		}

		if ( '' === $translation ) {
			$payload['error']   = 'no_translation';
			$payload['message'] = __( 'Install a free Local Bible under Bible Lessons → Local Bibles, or configure a Biblia.com API key for NIV/NLT search.', 'hidden-word-bible-lessons' );
			return $payload;
		}

		$backend = self::resolve_backend( $translation );
		$payload['backend'] = $backend;

		if ( 'local' === $backend ) {
			$results = HWBL_Local_Bible_Store::search(
				$translation,
				$query,
				$limit,
				array( 'testament' => $testament )
			);
			$payload['results']   = self::normalize_results( $results, $query );
			$payload['count']     = count( $payload['results'] );
			$payload['truncated'] = $payload['count'] >= $limit;
			return $payload;
		}

		if ( 'biblia' === $backend ) {
			$detailed = THW_Premium_Biblia::search_bible_detailed( $translation, $query, $limit );
			$results  = isset( $detailed['results'] ) && is_array( $detailed['results'] ) ? $detailed['results'] : array();
			if ( $testament ) {
				$results = array_values(
					array_filter(
						$results,
						static function ( $row ) use ( $testament ) {
							$book_id = (int) ( $row['book_id'] ?? 0 );
							return HWBL_Books::get_testament( $book_id ) === $testament;
						}
					)
				);
			}
			$payload['results']     = self::normalize_results( $results, $query );
			$payload['count']       = count( $payload['results'] );
			$payload['truncated']   = $payload['count'] >= $limit;
			$payload['translation'] = ! empty( $detailed['translation'] ) ? (string) $detailed['translation'] : $translation;
			if ( empty( $payload['results'] ) && ! empty( $detailed['error'] ) ) {
				$payload['error']   = (string) $detailed['error'];
				$payload['message'] = __( 'Could not search that translation. Check your Biblia.com API key, or try an installed Local Bible.', 'hidden-word-bible-lessons' );
			}
			return $payload;
		}

		$payload['error']   = 'licensed_needs_api';
		$payload['message'] = __( 'This translation is not available for offline concordance. Install a free Local Bible, or add a Biblia.com API key under Advanced settings for NIV/NLT search.', 'hidden-word-bible-lessons' );
		return $payload;
	}

	/**
	 * Normalize hit rows and add match snippets.
	 *
	 * @param array<int, array<string, mixed>> $results Raw results.
	 * @param string                           $query   Query.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_results( $results, $query ) {
		$out = array();
		foreach ( $results as $row ) {
			$book_id = (int) ( $row['book_id'] ?? 0 );
			$chapter = (int) ( $row['chapter'] ?? 0 );
			$verse   = (int) ( $row['verse'] ?? 0 );
			$text    = trim( (string) ( $row['text'] ?? $row['preview'] ?? '' ) );
			if ( $book_id < 1 || $chapter < 1 || '' === $text ) {
				continue;
			}
			if ( $verse < 1 ) {
				$verse = 1;
			}
			$reference = '';
			if ( ! empty( $row['reference'] ) ) {
				$reference = (string) $row['reference'];
			} elseif ( ! empty( $row['passage'] ) ) {
				$reference = (string) $row['passage'];
			} else {
				$reference = HWBL_Books::format_reference( $book_id, $chapter, $verse );
			}
			$out[] = array(
				'reference' => $reference,
				'book_id'   => $book_id,
				'chapter'   => $chapter,
				'verse'     => $verse,
				'text'      => $text,
				'snippet'   => self::build_snippet( $text, $query ),
				'testament' => HWBL_Books::get_testament( $book_id ),
			);
		}
		return $out;
	}

	/**
	 * Short snippet centered on the first match.
	 *
	 * @param string $text  Verse text.
	 * @param string $query Query.
	 * @return string
	 */
	public static function build_snippet( $text, $query ) {
		$text  = preg_replace( '/\s+/u', ' ', (string) $text );
		$text  = trim( (string) $text );
		$query = trim( (string) $query );
		if ( '' === $text ) {
			return '';
		}
		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
		if ( '' === $query || $len <= 140 ) {
			return $text;
		}

		if ( function_exists( 'mb_stripos' ) ) {
			$pos = mb_stripos( $text, $query );
			if ( false === $pos ) {
				$first = preg_split( '/\s+/u', $query );
				$word  = is_array( $first ) && ! empty( $first[0] ) ? $first[0] : '';
				$pos   = $word ? mb_stripos( $text, $word ) : false;
			}
		} else {
			$pos = stripos( $text, $query );
			if ( false === $pos ) {
				$first = preg_split( '/\s+/u', $query );
				$word  = is_array( $first ) && ! empty( $first[0] ) ? $first[0] : '';
				$pos   = $word ? stripos( $text, $word ) : false;
			}
		}

		$substr = function_exists( 'mb_substr' ) ? 'mb_substr' : 'substr';
		if ( false === $pos ) {
			$out = $substr( $text, 0, 140 );
			return $len > 140 ? $out . '…' : $out;
		}

		$start = max( 0, (int) $pos - 40 );
		$snip  = $substr( $text, $start, 140 );
		if ( $start > 0 ) {
			$snip = '…' . $snip;
		}
		if ( $start + 140 < $len ) {
			$snip .= '…';
		}
		return $snip;
	}

	/**
	 * Front-end config.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_front_config() {
		$translations = self::get_available_translations();
		$default      = '';
		if ( isset( $translations['kjv'] ) ) {
			$default = 'kjv';
		} elseif ( isset( $translations['web'] ) ) {
			$default = 'web';
		} elseif ( isset( $translations['bsb'] ) ) {
			$default = 'bsb';
		} elseif ( ! empty( $translations ) ) {
			$keys    = array_keys( $translations );
			$default = (string) $keys[0];
		}

		$reader_url = '';
		if ( class_exists( 'HWBL_Bible_Reader' ) && HWBL_Bible_Reader::is_enabled() ) {
			// Best-effort: sites often put the reader on a known page; leave blank and use hash links in-widget.
			$reader_url = (string) apply_filters( 'hwbl_bible_reader_page_url', '' );
		}

		return array(
			'enabled'       => self::is_enabled(),
			'restUrl'       => rest_url( 'hwbl/v1/bible/concordance' ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'translations'  => $translations,
			'defaultTranslation' => $default,
			'bibliaAvailable'    => class_exists( 'THW_Premium_Biblia' ) && THW_Premium_Biblia::is_available(),
			'readerUrl'          => $reader_url,
			'i18n'               => array(
				'placeholder'   => __( 'e.g. love, faith, Jerusalem', 'hidden-word-bible-lessons' ),
				'search'        => __( 'Search concordance', 'hidden-word-bible-lessons' ),
				'loading'       => __( 'Searching…', 'hidden-word-bible-lessons' ),
				'empty'         => __( 'No matches found.', 'hidden-word-bible-lessons' ),
				'truncated'     => __( 'Showing the first %d matches. Refine your search for more specific results.', 'hidden-word-bible-lessons' ),
				'localHint'     => __( 'Searching your installed Local Bible (offline).', 'hidden-word-bible-lessons' ),
				'bibliaHint'    => __( 'Searching via Biblia.com (licensed translation).', 'hidden-word-bible-lessons' ),
				'openReader'    => __( 'Open in Bible reader', 'hidden-word-bible-lessons' ),
				'resultsLabel'  => __( '%d matches', 'hidden-word-bible-lessons' ),
				'allTestament'  => __( 'Whole Bible', 'hidden-word-bible-lessons' ),
				'ot'            => __( 'Old Testament', 'hidden-word-bible-lessons' ),
				'nt'            => __( 'New Testament', 'hidden-word-bible-lessons' ),
			),
		);
	}

	/**
	 * Register REST.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'hwbl/v1',
			'/bible/concordance',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_lookup' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'           => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'translation' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
					'limit'       => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 50,
					),
					'testament'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'default'           => '',
					),
				),
			)
		);
	}

	/**
	 * REST callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_lookup( $request ) {
		if ( ! self::is_enabled() ) {
			return rest_ensure_response(
				array(
					'error'   => 'disabled',
					'message' => __( 'Concordance is disabled in settings.', 'hidden-word-bible-lessons' ),
					'results' => array(),
					'count'   => 0,
				)
			);
		}

		$payload = self::lookup(
			(string) $request->get_param( 'q' ),
			(string) $request->get_param( 'translation' ),
			(int) $request->get_param( 'limit' ),
			(string) $request->get_param( 'testament' )
		);

		return rest_ensure_response( $payload );
	}

	/**
	 * Register assets.
	 */
	public static function register_assets() {
		wp_register_style(
			'hwbl-bible-concordance',
			HWBL_PLUGIN_URL . 'public/css/bible-concordance.css',
			array(),
			HWBL_VERSION
		);
		wp_register_script(
			'hwbl-bible-concordance',
			HWBL_PLUGIN_URL . 'public/js/bible-concordance.js',
			array(),
			HWBL_VERSION,
			true
		);
	}

	/**
	 * Enqueue + localize.
	 */
	public static function enqueue_assets() {
		self::register_assets();
		wp_enqueue_style( 'hwbl-bible-concordance' );
		wp_enqueue_script( 'hwbl-bible-concordance' );
		wp_localize_script( 'hwbl-bible-concordance', 'hwblBibleConcordance', self::get_front_config() );
	}

	/**
	 * Shortcode [hwbl_bible_concordance].
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts = array() ) {
		if ( ! self::is_enabled() ) {
			return '<p class="hwbl-bible-concordance-notice">' . esc_html__( 'Bible concordance is disabled in settings.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'translation' => '',
				'q'           => '',
				'title'       => __( 'Bible Concordance', 'hidden-word-bible-lessons' ),
			),
			$atts,
			'hwbl_bible_concordance'
		);

		self::enqueue_assets();
		$config = self::get_front_config();
		$default = sanitize_key( (string) $atts['translation'] );
		if ( '' === $default || ! isset( $config['translations'][ $default ] ) ) {
			$default = (string) $config['defaultTranslation'];
		}
		$query = sanitize_text_field( (string) $atts['q'] );
		$title = sanitize_text_field( (string) $atts['title'] );
		$uid   = 'hwbl-concordance-' . wp_unique_id();

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $uid ); ?>"
			class="hwbl-bible-concordance"
			data-translation="<?php echo esc_attr( $default ); ?>"
			data-q="<?php echo esc_attr( $query ); ?>"
		>
			<?php if ( $title ) : ?>
				<h3 class="hwbl-bible-concordance__heading"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<p class="hwbl-bible-concordance__intro description">
				<?php esc_html_e( 'Look up a word or phrase across Scripture. Free Local Bibles search offline; NIV/NLT use Biblia.com when configured.', 'hidden-word-bible-lessons' ); ?>
			</p>
			<form class="hwbl-bible-concordance__form" action="#" method="get">
				<label class="hwbl-bible-concordance__field hwbl-bible-concordance__field--query">
					<span class="screen-reader-text"><?php esc_html_e( 'Word or phrase', 'hidden-word-bible-lessons' ); ?></span>
					<input type="search" class="hwbl-bible-concordance__query" name="q" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php esc_attr_e( 'e.g. love, faith, Jerusalem', 'hidden-word-bible-lessons' ); ?>" autocomplete="off" />
				</label>
				<label class="hwbl-bible-concordance__field">
					<span><?php esc_html_e( 'Translation', 'hidden-word-bible-lessons' ); ?></span>
					<select class="hwbl-bible-concordance__translation">
						<?php foreach ( $config['translations'] as $slug => $meta ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $default, $slug ); ?>>
								<?php echo esc_html( $meta['label'] ); ?>
								<?php if ( 'biblia' === ( $meta['backend'] ?? '' ) ) : ?>
									<?php echo esc_html( ' (API)' ); ?>
								<?php endif; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="hwbl-bible-concordance__field">
					<span><?php esc_html_e( 'Scope', 'hidden-word-bible-lessons' ); ?></span>
					<select class="hwbl-bible-concordance__testament">
						<option value=""><?php esc_html_e( 'Whole Bible', 'hidden-word-bible-lessons' ); ?></option>
						<option value="ot"><?php esc_html_e( 'Old Testament', 'hidden-word-bible-lessons' ); ?></option>
						<option value="nt"><?php esc_html_e( 'New Testament', 'hidden-word-bible-lessons' ); ?></option>
					</select>
				</label>
				<button type="submit" class="hwbl-btn hwbl-bible-concordance__submit"><?php esc_html_e( 'Search concordance', 'hidden-word-bible-lessons' ); ?></button>
			</form>
			<?php if ( empty( $config['translations'] ) ) : ?>
				<p class="hwbl-bible-concordance__notice">
					<?php esc_html_e( 'No searchable Bibles yet. Install a free Local Bible under Bible Lessons → Local Bibles, or add a Biblia.com API key for NIV/NLT.', 'hidden-word-bible-lessons' ); ?>
				</p>
			<?php endif; ?>
			<p class="hwbl-bible-concordance__status" role="status" aria-live="polite"></p>
			<ol class="hwbl-bible-concordance__results" hidden></ol>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
