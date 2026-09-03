<?php
/**
 * Persist Bible.com VOTD AI explanations as posts (one per day + Bible translation).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Votd_Explain_Store
 */
class THW_Premium_Votd_Explain_Store {

	const POST_TYPE = 'thw_votd_explain';
	const META_DAY  = '_thw_votd_day';
	const META_TRAD = '_thw_votd_tradition';
	const META_TRANS = '_thw_votd_translation';
	const META_REF  = '_thw_votd_reference';
	const META_FLAG = '_thw_votd_flagged';
	const EXPLAIN_FEATURED_IMAGE_OPT = 'thw_votd_explain_featured_image';

	/**
	 * Register CPT.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_singular_styles' ) );
		add_filter( 'template_include', array( __CLASS__, 'maybe_use_plugin_template' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_singular_content' ), 12 );
	}

	/**
	 * Whether explain posts should show / auto-attach a featured image.
	 *
	 * @return bool
	 */
	public static function is_featured_image_enabled() {
		return (bool) get_option( self::EXPLAIN_FEATURED_IMAGE_OPT, true );
	}

	/**
	 * Repair markdown-fenced / plain-text AI bodies when rendering a saved explanation.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_singular_content( $content ) {
		if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$repaired = (string) $content;
		if ( class_exists( 'THW_Premium_AI_Client' ) ) {
			if ( ! preg_match( '/^(.*)<div class="thw-votd-explain__body[^"]*">(.*)<\/div>\s*$/is', $repaired, $m ) ) {
				$repaired = THW_Premium_AI_Client::format_html_response( $repaired );
			} else {
				$prefix   = $m[1];
				$body     = THW_Premium_AI_Client::format_html_response( $m[2] );
				$repaired = $prefix . '<div class="thw-votd-explain__body thw-votd-explain-output">' . $body . '</div>';
			}
		}

		// Themes that use single.php / the_content without a featured-image block.
		$already = ! empty( $GLOBALS['thw_votd_explain_featured_rendered'] )
			|| preg_match( '/thw-votd-explain__featured/', $repaired );
		if ( self::is_featured_image_enabled() && has_post_thumbnail() && ! $already ) {
			$repaired = self::render_featured_image_html( get_the_ID() ) . $repaired;
		}

		if ( false === strpos( $repaired, 'thw-votd-explain__study' ) ) {
			$study = self::render_study_card_for_current_post();
			if ( $study ) {
				$repaired .= $study;
			}
		}

		return $repaired;
	}

	/**
	 * Verse Study Card CTA for the explained VOTD reference.
	 *
	 * @return string
	 */
	public static function render_study_card_for_current_post() {
		if ( ! class_exists( 'THW_Premium_Bible_Study_Card' ) || ! class_exists( 'HWBL_Books' ) ) {
			return '';
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$reference = self::normalize_reference( (string) get_post_meta( $post_id, self::META_REF, true ) );
		if ( '' === $reference ) {
			return '';
		}

		$parsed = HWBL_Books::parse_reference( $reference );
		if ( ! is_array( $parsed ) || (int) $parsed['book_id'] < 1 || (int) $parsed['chapter'] < 1 || (int) $parsed['verse'] < 1 ) {
			return '';
		}

		$translation = sanitize_key( (string) get_post_meta( $post_id, self::META_TRANS, true ) );
		if ( 'default' === $translation ) {
			$translation = '';
		}

		$card = THW_Premium_Bible_Study_Card::render_shortcode(
			array(
				'book_id'     => (int) $parsed['book_id'],
				'chapter'     => (int) $parsed['chapter'],
				'verse'       => (int) $parsed['verse'],
				'translation' => $translation,
				'title'       => __( 'Study this verse', 'hidden-word-bible-lessons' ),
				'picker'      => '0',
			)
		);

		if ( ! is_string( $card ) || '' === trim( $card ) || false === strpos( $card, 'hwbl-verse-study' ) ) {
			return '';
		}

		return '<section class="thw-votd-explain__study" aria-label="' . esc_attr__( 'Study this verse', 'hidden-word-bible-lessons' ) . '">'
			. '<p class="thw-votd-explain__study-intro">'
			. esc_html__( 'Go deeper with a Verse Study Card — plain words, context, key words, cross-references, and a reflection.', 'hidden-word-bible-lessons' )
			. '</p>'
			. $card
			. '</section>';
	}

	/**
	 * Featured image markup for a VOTD explanation post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function render_featured_image_html( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id < 1 || ! has_post_thumbnail( $post_id ) ) {
			return '';
		}

		$img = get_the_post_thumbnail(
			$post_id,
			'large',
			array(
				'class'   => 'thw-votd-explain__featured-img',
				'loading' => 'lazy',
			)
		);
		if ( ! $img ) {
			return '';
		}

		$caption = get_the_post_thumbnail_caption( $post_id );
		$html    = '<figure class="thw-votd-explain__featured">' . $img;
		if ( is_string( $caption ) && '' !== trim( $caption ) ) {
			$html .= '<figcaption class="thw-votd-explain__featured-caption">' . esc_html( $caption ) . '</figcaption>';
		}
		$html .= '</figure>';

		return $html;
	}

	/**
	 * Enqueue styles on saved explanation single pages (theme or plugin fallback).
	 */
	public static function enqueue_singular_styles() {
		if ( ! is_singular( self::POST_TYPE ) ) {
			return;
		}

		if ( wp_style_is( 'thw-premium', 'registered' ) ) {
			wp_enqueue_style( 'thw-premium' );
		}

		if ( class_exists( 'THW_Premium_Bible_Study_Card' ) ) {
			THW_Premium_Bible_Study_Card::enqueue_assets();
		}
	}

	/**
	 * Use a plugin single template when the theme has no dedicated VOTD explain template.
	 *
	 * Prevents themes that fall back to archive/index (excerpt-only) from hiding the full explanation.
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

		$plugin_template = THW_PREMIUM_DIR . 'templates/single-thw_votd_explain.php';
		if ( is_readable( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}

	/**
	 * Flush rewrites when the CPT rewrite version changes or rules are missing.
	 *
	 * Pretty permalinks for this CPT 404 when rules were never flushed (common after
	 * first enabling advanced modules). get_permalink() still returns pretty URLs.
	 */
	public static function maybe_flush_rewrites() {
		$version = defined( 'HWBL_VERSION' ) ? (string) HWBL_VERSION : ( defined( 'THW_PREMIUM_VERSION' ) ? (string) THW_PREMIUM_VERSION : '1' );
		$flag    = 'thw_votd_explain_rewrite_ver';
		$stored  = (string) get_option( $flag, '' );

		if ( $stored !== $version || ! self::rewrite_rules_registered() ) {
			flush_rewrite_rules( false );
			update_option( $flag, $version, false );
		}
	}

	/**
	 * Whether rewrite rules include the VOTD explanation CPT slug.
	 *
	 * @return bool
	 */
	public static function rewrite_rules_registered() {
		$rules = get_option( 'rewrite_rules' );
		if ( ! is_array( $rules ) || array() === $rules ) {
			return false;
		}

		foreach ( array_keys( $rules ) as $rule ) {
			if ( false !== strpos( (string) $rule, 'verse-of-the-day-explanation' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Public URL for a saved explanation (ensures CPT rewrites, with ?p= fallback).
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	public static function get_public_url( $post ) {
		if ( ! ( $post instanceof WP_Post ) || (int) $post->ID <= 0 ) {
			return '';
		}

		self::maybe_flush_rewrites();

		$url = get_permalink( $post );
		if ( is_string( $url ) && '' !== $url && ! is_wp_error( $url ) ) {
			return $url;
		}

		return add_query_arg(
			array(
				'p'         => (int) $post->ID,
				'post_type' => self::POST_TYPE,
			),
			home_url( '/' )
		);
	}

	/**
	 * Whether a stored post has enough explanation body to show (not an empty shell).
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	public static function has_usable_explanation( $post ) {
		$html = self::get_explanation_html( $post );
		$text = trim( wp_strip_all_tags( (string) $html ) );

		return strlen( $text ) >= 40;
	}

	/**
	 * Register explanation post type.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'VOTD Explanations', 'hidden-word-bible-lessons' ),
					'singular_name' => __( 'VOTD Explanation', 'hidden-word-bible-lessons' ),
					'edit_item'     => __( 'Edit VOTD Explanation', 'hidden-word-bible-lessons' ),
					'view_item'     => __( 'View VOTD Explanation', 'hidden-word-bible-lessons' ),
					'search_items'  => __( 'Search VOTD Explanations', 'hidden-word-bible-lessons' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=hwbl_lesson',
				'show_in_rest'        => true,
				'has_archive'         => false,
				'rewrite'             => array(
					'slug'       => 'verse-of-the-day-explanation',
					'with_front' => false,
				),
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields' ),
				'exclude_from_search' => true,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Normalize a scripture reference for stable meta lookups.
	 *
	 * @param string $reference Human reference (e.g. "John 3:16").
	 * @return string
	 */
	public static function normalize_reference( $reference ) {
		$reference = sanitize_text_field( (string) $reference );
		$reference = preg_replace( '/\s+/', ' ', $reference );
		return trim( (string) $reference );
	}

	/**
	 * Find a saved explanation by calendar day + Bible translation (primary lookup).
	 *
	 * @param string $day         Y-m-d.
	 * @param string $translation Translation slug.
	 * @return WP_Post|null
	 */
	public static function find_post_by_day_translation( $day, $translation ) {
		$day         = sanitize_text_field( (string) $day );
		$translation = sanitize_key( (string) $translation );
		if ( '' === $day || '' === $translation ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => self::META_DAY,
						'value' => $day,
					),
					array(
						'key'   => self::META_TRANS,
						'value' => $translation,
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
	 * Find a saved explanation by verse reference + tradition.
	 *
	 * @param string $reference Scripture reference.
	 * @param string $tradition Tradition preset.
	 * @return WP_Post|null
	 */
	public static function find_post_by_reference( $reference, $tradition ) {
		$reference = self::normalize_reference( $reference );
		$tradition = sanitize_key( (string) $tradition );
		if ( '' === $reference || '' === $tradition ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => self::META_REF,
						'value' => $reference,
					),
					array(
						'key'   => self::META_TRAD,
						'value' => $tradition,
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
	 * Find a saved explanation post (legacy day + tradition + translation).
	 *
	 * Prefer find_post_by_reference() for new lookups.
	 *
	 * @param string $day         Y-m-d.
	 * @param string $tradition   Tradition preset.
	 * @param string $translation Translation slug.
	 * @return WP_Post|null
	 */
	public static function find_post( $day, $tradition, $translation ) {
		$day         = sanitize_text_field( (string) $day );
		$tradition   = sanitize_key( (string) $tradition );
		$translation = sanitize_key( (string) $translation );
		if ( '' === $day || '' === $tradition ) {
			return null;
		}
		if ( '' === $translation ) {
			$translation = 'default';
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => self::META_DAY,
						'value' => $day,
					),
					array(
						'key'   => self::META_TRAD,
						'value' => $tradition,
					),
					array(
						'key'   => self::META_TRANS,
						'value' => $translation,
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
	 * Map of translation slug => permalink for saved explanations on a given day.
	 *
	 * @param string $day Y-m-d.
	 * @return array<string, string>
	 */
	public static function get_urls_for_day( $day ) {
		$day = sanitize_text_field( (string) $day );
		if ( '' === $day ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_DAY,
						'value' => $day,
					),
				),
			)
		);

		$map = array();
		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof WP_Post ) || ! self::has_usable_explanation( $post ) ) {
				continue;
			}
			$translation = sanitize_key( (string) get_post_meta( $post->ID, self::META_TRANS, true ) );
			$url         = self::get_public_url( $post );
			if ( '' !== $translation && $url ) {
				$map[ $translation ] = $url;
			}
		}

		return $map;
	}

	/**
	 * Map of tradition => permalink for explanations of a scripture reference.
	 *
	 * @param string $reference Scripture reference.
	 * @return array<string, string>
	 */
	public static function get_urls_for_reference( $reference ) {
		$reference = self::normalize_reference( $reference );
		if ( '' === $reference ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::META_REF,
						'value' => $reference,
					),
				),
			)
		);

		$map = array();
		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof WP_Post ) ) {
				continue;
			}
			$trad = sanitize_key( (string) get_post_meta( $post->ID, self::META_TRAD, true ) );
			$url  = get_permalink( $post );
			if ( '' !== $trad && $url ) {
				$map[ $trad ] = $url;
			}
		}

		return $map;
	}

	/**
	 * Map of tradition => permalink for today's saved explanations (legacy).
	 *
	 * @param string $day         Y-m-d.
	 * @param string $translation Translation slug.
	 * @return array<string, string>
	 */
	public static function get_today_urls( $day, $translation ) {
		$day         = sanitize_text_field( (string) $day );
		$translation = sanitize_key( (string) $translation );
		if ( '' === $translation ) {
			$translation = 'default';
		}

		$query = new WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => self::META_DAY,
						'value' => $day,
					),
					array(
						'key'   => self::META_TRANS,
						'value' => $translation,
					),
				),
			)
		);

		$map = array();
		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof WP_Post ) ) {
				continue;
			}
			$trad = sanitize_key( (string) get_post_meta( $post->ID, self::META_TRAD, true ) );
			$url  = get_permalink( $post );
			if ( '' !== $trad && $url ) {
				$map[ $trad ] = $url;
			}
		}

		return $map;
	}

	/**
	 * Find the best saved post for a VOTD payload (day + translation, with legacy fallbacks).
	 *
	 * @param array<string, mixed> $payload VOTD payload.
	 * @return WP_Post|null
	 */
	public static function find_saved_post( $payload ) {
		$day         = isset( $payload['day'] ) ? sanitize_text_field( (string) $payload['day'] ) : wp_date( 'Y-m-d' );
		$translation = isset( $payload['translation'] ) ? sanitize_key( (string) $payload['translation'] ) : '';
		$reference   = isset( $payload['reference'] ) ? self::normalize_reference( (string) $payload['reference'] ) : '';

		if ( '' === $translation ) {
			$translation = 'default';
		}

		$saved = self::find_post_by_day_translation( $day, $translation );
		if ( $saved instanceof WP_Post && self::has_usable_explanation( $saved ) ) {
			return $saved;
		}

		// Legacy tradition-keyed posts (pre translation-based VOTD).
		if ( '' !== $reference ) {
			$legacy_traditions = array( 'neutral', 'general' );
			foreach ( $legacy_traditions as $tradition ) {
				$saved = self::find_post_by_reference( $reference, $tradition );
				if ( $saved instanceof WP_Post && self::has_usable_explanation( $saved ) ) {
					$legacy_trans = sanitize_key( (string) get_post_meta( $saved->ID, self::META_TRANS, true ) );
					if ( '' === $legacy_trans || $legacy_trans === $translation ) {
						return $saved;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Create (or reuse) an explanation post after AI generation.
	 *
	 * @param array<string, mixed> $payload VOTD payload.
	 * @param string               $html    Explanation HTML.
	 * @param bool                 $flagged Compliance flagged.
	 * @return WP_Post|null
	 */
	public static function save_post( $payload, $html, $flagged ) {
		$day         = isset( $payload['day'] ) ? sanitize_text_field( (string) $payload['day'] ) : wp_date( 'Y-m-d' );
		$translation = isset( $payload['translation'] ) ? sanitize_key( (string) $payload['translation'] ) : '';
		if ( '' === $translation ) {
			$translation = 'default';
		}
		$reference = isset( $payload['reference'] ) ? self::normalize_reference( (string) $payload['reference'] ) : '';
		$verse     = isset( $payload['text'] ) ? (string) $payload['text'] : '';

		$existing = self::find_post_by_day_translation( $day, $translation );
		if ( $existing instanceof WP_Post && self::has_usable_explanation( $existing ) ) {
			return $existing;
		}

		$trans_label = '';
		if ( ! empty( $payload['translation_label'] ) ) {
			$trans_label = (string) $payload['translation_label'];
		}
		// Never persist the Bible.com source tag as the translation name when we have a real slug.
		if (
			( '' === $trans_label || 0 === strcasecmp( $trans_label, 'Bible.com' ) )
			&& 'default' !== $translation
			&& class_exists( 'HWBL_Translation_Service' )
		) {
			$svc = HWBL_Translation_Service::instance();
			if ( method_exists( $svc, 'get_translation_label' ) ) {
				$resolved = (string) $svc->get_translation_label( $translation );
				if ( '' !== $resolved ) {
					$trans_label = $resolved;
				}
			}
		}
		if ( '' === $trans_label ) {
			$trans_label = strtoupper( $translation );
		}

		$title = sprintf(
			/* translators: 1: scripture reference, 2: translation label, 3: date */
			__( '%1$s — %2$s (%3$s)', 'hidden-word-bible-lessons' ),
			$reference ? $reference : __( 'Verse of the Day', 'hidden-word-bible-lessons' ),
			$trans_label,
			$day
		);

		$content  = '';
		if ( $reference || $verse ) {
			$content .= '<blockquote class="thw-votd-explain__verse">';
			if ( $verse ) {
				$content .= '<p>' . esc_html( $verse ) . '</p>';
			}
			if ( $reference ) {
				$content .= '<cite>' . esc_html( $reference ) . '</cite>';
			}
			$content .= '</blockquote>';
		}
		$content .= '<div class="thw-votd-explain__body thw-votd-explain-output">' . wp_kses_post( $html ) . '</div>';

		$slug_base = 'votd-' . $day . '-' . $translation;
		$post_data = array(
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
			'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
			'post_name'    => sanitize_title( $slug_base ),
		);

		// Reuse an empty/broken shell for the same day+translation instead of colliding on slug.
		if ( $existing instanceof WP_Post ) {
			$post_data['ID'] = (int) $existing->ID;
			$post_id         = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return null;
		}

		update_post_meta( $post_id, self::META_DAY, $day );
		update_post_meta( $post_id, self::META_TRAD, 'neutral' );
		update_post_meta( $post_id, self::META_TRANS, $translation );
		update_post_meta( $post_id, self::META_REF, $reference );
		update_post_meta( $post_id, self::META_FLAG, $flagged ? 1 : 0 );

		if ( self::is_featured_image_enabled() && empty( get_post_thumbnail_id( $post_id ) ) ) {
			$image_url = isset( $payload['image'] ) ? esc_url_raw( (string) $payload['image'] ) : '';
			if ( $image_url ) {
				self::maybe_set_featured_image_from_url(
					(int) $post_id,
					$image_url,
					$reference ? $reference : $title
				);
			}
		}

		// Pretty permalinks 404 until CPT rewrite rules exist — flush now if needed.
		self::maybe_flush_rewrites();

		$post = get_post( $post_id );
		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * Sideload a remote image and set it as the post featured image.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $image_url Remote image URL.
	 * @param string $title     Attachment title / alt.
	 * @return int Attachment ID or 0.
	 */
	public static function maybe_set_featured_image_from_url( $post_id, $image_url, $title = '' ) {
		$post_id   = absint( $post_id );
		$image_url = esc_url_raw( (string) $image_url );
		if ( $post_id < 1 || '' === $image_url ) {
			return 0;
		}
		if ( ! empty( get_post_thumbnail_id( $post_id ) ) ) {
			return (int) get_post_thumbnail_id( $post_id );
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$tmp = download_url( $image_url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return 0;
		}

		$path = wp_parse_url( $image_url, PHP_URL_PATH );
		$name = is_string( $path ) ? basename( $path ) : '';
		if ( '' === $name || false === strpos( $name, '.' ) ) {
			$name = 'votd-' . $post_id . '.jpg';
		}
		$name = sanitize_file_name( $name );

		$file_array = array(
			'name'     => $name,
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload(
			$file_array,
			$post_id,
			$title ? sanitize_text_field( $title ) : ''
		);

		if ( is_wp_error( $attachment_id ) ) {
			if ( is_string( $tmp ) && file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return 0;
		}

		set_post_thumbnail( $post_id, (int) $attachment_id );

		if ( $title ) {
			update_post_meta(
				(int) $attachment_id,
				'_wp_attachment_image_alt',
				sanitize_text_field( $title )
			);
		}

		return (int) $attachment_id;
	}

	/**
	 * Explanation HTML body from a stored post (strips verse block wrapper when possible).
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	public static function get_explanation_html( $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			return '';
		}

		$content = (string) $post->post_content;
		if ( preg_match( '/<div class="thw-votd-explain__body[^"]*">(.*)<\/div>\s*$/is', $content, $m ) ) {
			$body = $m[1];
		} else {
			$body = $content;
		}

		// Repair older saves that stored markdown fences / plain-text headings.
		if ( class_exists( 'THW_Premium_AI_Client' ) ) {
			return THW_Premium_AI_Client::format_html_response( $body );
		}

		return wp_kses_post( $body );
	}
}
