<?php
/**
 * Local SQL Bible translation provider.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HWBL_PLUGIN_DIR . 'includes/interface-translation-provider.php';

/**
 * Class HWBL_Local_Bible_Provider
 */
class HWBL_Local_Bible_Provider implements HWBL_Translation_Provider {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'hwbl_translation_providers', array( __CLASS__, 'register_provider' ), 5 );
		add_filter( 'hwbl_render_copyright', array( __CLASS__, 'render_copyright' ), 5, 2 );
	}

	/**
	 * Prefer local provider before Hello AO / remote BYOK providers.
	 *
	 * @param array<string, HWBL_Translation_Provider> $providers Providers.
	 * @return array<string, HWBL_Translation_Provider>
	 */
	public static function register_provider( $providers ) {
		if ( ! is_array( $providers ) ) {
			$providers = array();
		}

		$local = array( 'local' => new self() );
		return $local + $providers;
	}

	/**
	 * Copyright notice for installed local translations.
	 *
	 * @param string $html        Existing HTML.
	 * @param string $translation Translation slug.
	 * @return string
	 */
	public static function render_copyright( $html, $translation ) {
		if ( $html ) {
			return $html;
		}

		$translation = sanitize_key( (string) $translation );
		if ( ! HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return $html;
		}

		$row     = HWBL_Local_Bible_Store::get_translation( $translation );
		$catalog = HWBL_Local_Bible_Store::get_catalog();
		$label   = $row['label'] ?? ( $catalog[ $translation ]['label'] ?? strtoupper( $translation ) );
		$license = $row['license'] ?? ( $catalog[ $translation ]['license'] ?? 'Public domain' );

		return esc_html(
			sprintf(
				/* translators: 1: translation label, 2: license */
				__( '%1$s — %2$s (served from this site’s local Bible database).', 'hidden-word-bible-lessons' ),
				$label,
				$license
			)
		);
	}

	/**
	 * Get verse text from local SQL when installed.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param int    $verse       Verse.
	 * @param string $translation Translation slug.
	 * @return string|null
	 */
	public function get_verse( $book_id, $chapter, $verse, $translation ) {
		$translation = sanitize_key( (string) $translation );
		if ( ! HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return null;
		}

		return HWBL_Local_Bible_Store::get_verse( $translation, (int) $book_id, (int) $chapter, (int) $verse );
	}

	/**
	 * Supported local translations that are currently installed.
	 *
	 * @return array<string, string>
	 */
	public function get_supported_translations() {
		$out     = array();
		$catalog = HWBL_Local_Bible_Store::get_catalog();
		foreach ( array_keys( $catalog ) as $slug ) {
			if ( HWBL_Local_Bible_Store::is_installed( $slug ) ) {
				$out[ $slug ] = $catalog[ $slug ]['label'];
			}
		}
		return $out;
	}

	/**
	 * Chapter payload from local SQL.
	 *
	 * @param int    $book_id     Book ID.
	 * @param int    $chapter     Chapter.
	 * @param string $translation Translation slug.
	 * @return array{verses:array,headings:array}|null
	 */
	public static function get_chapter_payload( $book_id, $chapter, $translation ) {
		$translation = sanitize_key( (string) $translation );
		if ( ! HWBL_Local_Bible_Store::is_installed( $translation ) ) {
			return null;
		}
		return HWBL_Local_Bible_Store::get_chapter( $translation, (int) $book_id, (int) $chapter );
	}
}
