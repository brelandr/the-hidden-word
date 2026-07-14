<?php
/**
 * Translation service facade.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once THW_PLUGIN_DIR . 'includes/interface-translation-provider.php';

/**
 * Class THW_Translation_Service
 */
class THW_Translation_Service {

	/**
	 * Registered providers.
	 *
	 * @var array<string, THW_Translation_Provider>
	 */
	private $providers = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->providers['bundled'] = new THW_Bundled_Provider();
		$this->providers            = apply_filters( 'thw_translation_providers', $this->providers );
	}

	/**
	 * Get verse text for active translation.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Optional translation override.
	 * @return string
	 */
	public function get_verse_text( $book_id, $chapter, $verse, $translation = '' ) {
		if ( ! $translation ) {
			$translation = get_option( 'thw_active_translation', 'niv' );
		}

		$text = null;

		foreach ( $this->providers as $provider ) {
			$text = $provider->get_verse( $book_id, $chapter, $verse, $translation );
			if ( $text ) {
				break;
			}
		}

		$text = apply_filters( 'thw_get_verse_text', $text, $book_id, $chapter, $verse, $translation );

		return $text ? $text : '';
	}

	/**
	 * Get follow-on verse text, including public-domain cache for verses outside the 500-lesson bundle.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Optional translation override.
	 * @return array{text:string,translation:string}
	 */
	public function get_echo_verse_text( $book_id, $chapter, $verse, $translation = '' ) {
		if ( ! $translation ) {
			$translation = get_option( 'thw_active_translation', 'niv' );
		}

		$translation = strtolower( $translation );
		$text        = $this->get_verse_text( $book_id, $chapter, $verse, $translation );

		if ( $text ) {
			return array(
				'text'         => $text,
				'translation'  => $translation,
			);
		}

		$cached = THW_Curriculum::get_echo_verse_text( $book_id, $chapter, $verse, $translation );
		if ( $cached ) {
			return $cached;
		}

		if ( 'niv' === $translation ) {
			$web = THW_Curriculum::get_echo_verse_text( $book_id, $chapter, $verse, 'web' );
			if ( $web ) {
				return $web;
			}
		}

		return array(
			'text'        => '',
			'translation' => '',
		);
	}

	/**
	 * Human-readable label for a translation slug.
	 *
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public function get_translation_label( $translation ) {
		$labels = $this->get_supported_translations();
		return isset( $labels[ $translation ] ) ? $labels[ $translation ] : strtoupper( $translation );
	}

	/**
	 * Get verse text by week number.
	 *
	 * @param int    $week        Week number.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public function get_verse_by_week( $week, $translation = '' ) {
		if ( ! $translation ) {
			$translation = get_option( 'thw_active_translation', 'niv' );
		}

		if ( isset( $this->providers['bundled'] ) && $this->providers['bundled'] instanceof THW_Bundled_Provider ) {
			$text = $this->providers['bundled']->get_verse_by_week( $week, $translation );
			if ( $text ) {
				return $text;
			}
		}

		return '';
	}

	/**
	 * Get all supported translations.
	 *
	 * @return array<string, string>
	 */
	public function get_supported_translations() {
		$translations = array();

		foreach ( $this->providers as $provider ) {
			$translations = array_merge( $translations, $provider->get_supported_translations() );
		}

		return apply_filters( 'thw_supported_translations', $translations );
	}

	/**
	 * Render copyright notice for current translation.
	 *
	 * @param string $translation Translation slug.
	 * @return string HTML.
	 */
	public function render_copyright( $translation = '' ) {
		if ( ! $translation ) {
			$translation = get_option( 'thw_active_translation', 'niv' );
		}

		if ( 'niv' === $translation ) {
			return '<p class="thw-copyright">' . esc_html( THW_Bundled_Provider::get_niv_copyright() ) . '</p>';
		}

		if ( 'kjv' === $translation ) {
			return '<p class="thw-copyright">' . esc_html__( 'King James Version (KJV) — Public Domain.', 'the-hidden-word' ) . '</p>';
		}

		if ( 'web' === $translation ) {
			return '<p class="thw-copyright">' . esc_html__( 'World English Bible (WEB) — Public Domain.', 'the-hidden-word' ) . '</p>';
		}

		return apply_filters( 'thw_render_copyright', '', $translation );
	}

	/**
	 * Get singleton-style instance via static helper.
	 *
	 * @return self
	 */
	public static function instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new self();
		}
		return $instance;
	}
}
