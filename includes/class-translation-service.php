<?php
/**
 * Translation service facade.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HWBL_PLUGIN_DIR . 'includes/interface-translation-provider.php';

/**
 * Class HWBL_Translation_Service
 */
class HWBL_Translation_Service {

	/**
	 * Registered providers.
	 *
	 * @var array<string, HWBL_Translation_Provider>
	 */
	private $providers = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( class_exists( 'HWBL_HelloAO_Provider' ) ) {
			HWBL_HelloAO_Provider::init();
		}
		$this->providers['bundled'] = new HWBL_Bundled_Provider();
		$this->providers['helloao']   = new HWBL_HelloAO_Provider();
		$this->providers              = apply_filters( 'hwbl_translation_providers', $this->providers );
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
			$translation = get_option( 'hwbl_active_translation', 'niv' );
		}

		$text = null;

		foreach ( $this->providers as $provider ) {
			$text = $provider->get_verse( $book_id, $chapter, $verse, $translation );
			if ( $text ) {
				break;
			}
		}

		$text = apply_filters( 'hwbl_get_verse_text', $text, $book_id, $chapter, $verse, $translation );

		return HWBL_Http_Utils::sanitize_bible_text( $text );
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
			$translation = get_option( 'hwbl_active_translation', 'niv' );
		}

		$translation = strtolower( $translation );
		$text        = $this->get_verse_text( $book_id, $chapter, $verse, $translation );

		if ( $text ) {
			return array(
				'text'         => $text,
				'translation'  => $translation,
			);
		}

		$cached = HWBL_Curriculum::get_echo_verse_text( $book_id, $chapter, $verse, $translation );
		if ( $cached ) {
			return $cached;
		}

		if ( 'niv' === $translation ) {
			$web = HWBL_Curriculum::get_echo_verse_text( $book_id, $chapter, $verse, 'web' );
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
			$translation = get_option( 'hwbl_active_translation', 'niv' );
		}

		if ( isset( $this->providers['bundled'] ) && $this->providers['bundled'] instanceof HWBL_Bundled_Provider ) {
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

		return apply_filters( 'hwbl_supported_translations', $translations );
	}

	/**
	 * Render copyright notice for current translation.
	 *
	 * @param string $translation Translation slug.
	 * @return string HTML.
	 */
	public function render_copyright( $translation = '' ) {
		if ( ! $translation ) {
			$translation = get_option( 'hwbl_active_translation', 'niv' );
		}

		if ( 'niv' === $translation ) {
			return '<p class="hwbl-copyright">' . esc_html( HWBL_Bundled_Provider::get_niv_copyright() ) . '</p>';
		}

		if ( 'kjv' === $translation ) {
			return '<p class="hwbl-copyright">' . esc_html__( 'King James Version (KJV) — Public Domain.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		if ( 'web' === $translation ) {
			return '<p class="hwbl-copyright">' . esc_html__( 'World English Bible (WEB) — Public Domain.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		return apply_filters( 'hwbl_render_copyright', '', $translation );
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
