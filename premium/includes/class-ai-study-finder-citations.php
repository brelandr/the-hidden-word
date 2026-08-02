<?php
/**
 * Tradition citation helpers for AI Study Finder.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.

/**
 * Class THW_Premium_AI_Study_Finder_Citations
 */
class THW_Premium_AI_Study_Finder_Citations {

	/**
	 * Keep citations aligned with the resolved tradition digest (drop foreign confessions the model invents).
	 *
	 * @param string               $citations          AI citations field.
	 * @param string               $fallback_citations Pack-derived citation summary.
	 * @param array<string, mixed> $resolved           Resolve payload.
	 * @return string
	 */
	public static function sanitize_citations_for_tradition( $citations, $fallback_citations, $resolved ) {
		$citations          = trim( (string) $citations );
		$fallback_citations = trim( (string) $fallback_citations );
		$digest             = isset( $resolved['digest'] ) ? (string) $resolved['digest'] : '';
		$preset             = isset( $resolved['preset'] ) ? (string) $resolved['preset'] : '';

		if ( '' !== $citations && self::citations_match_tradition( $citations, $digest, $preset ) ) {
			return $citations;
		}

		if ( '' !== $fallback_citations ) {
			return $fallback_citations;
		}

		// Foreign or empty AI citations with no pack fallback → blank (do not show CIC for UPCI, etc.).
		return '';
	}

	/**
	 * Whether citation text is compatible with the active tradition digest/preset.
	 *
	 * @param string $citations Citation string.
	 * @param string $digest    Doctrine digest text.
	 * @param string $preset    Resolved preset slug.
	 * @return bool
	 */
	public static function citations_match_tradition( $citations, $digest, $preset ) {
		$citations = (string) $citations;
		$digest    = (string) $digest;
		$preset    = sanitize_key( $preset );

		$has_cic  = (bool) preg_match( '/\bCIC\b|\bCanon Law\b|\bCCC\b/i', $citations );
		$has_bfm  = (bool) preg_match( '/\bBF&M\b/i', $citations );
		$has_upci = (bool) preg_match( '/\bUPCI\b/i', $citations );
		$has_ag   = (bool) preg_match( '/\bAG\s*16\b|\bAG\s*Fundamental Truths\b|\b16\s*Fundamental Truths\b/i', $citations );
		$has_sda  = (bool) preg_match( '/\bSDA\s*28\s*Fundamental Beliefs\b|\b28\s*Fundamental Beliefs\b/i', $citations );
		$has_cog  = (bool) preg_match( '/\bCOG\s*Declaration\b|\bChurch of God Declaration\b/i', $citations );
		$has_iphc = (bool) preg_match( '/\bIPHC\b/i', $citations );
		$has_wcf  = (bool) preg_match( '/\bWestminster Confession\b|\bWCF\b/i', $citations );
		$has_ac   = (bool) preg_match( '/\bAugsburg Confession\b|\bLCMS Brief Statement\b/i', $citations );
		$has_39   = (bool) preg_match( '/\bThirty-Nine Articles\b/i', $citations );
		$has_eastern  = (bool) preg_match( '/\bNicene-Constantinopolitan Creed\b|\bSeven Ecumenical Councils\b|\bEastern Orthodox\b/i', $citations );
		$has_oriental = (bool) preg_match( '/\bOriental Orthodox\b|\bFirst Three Ecumenical Councils\b|\bMiaphysite\b/i', $citations );
		$has_umc      = (bool) preg_match( '/\bUMC Articles of Religion\b/i', $citations );
		$has_ame      = (bool) preg_match( '/\bAME Articles of Religion\b/i', $citations );
		$has_naz      = (bool) preg_match( '/\bNazarene Articles of Faith\b/i', $citations );
		$has_nh       = (bool) preg_match( '/\bNew Hampshire Baptist Confession\b|\b1689\s+London Baptist Confession\b|\bLBC\s*1689\b/i', $citations );
		$has_menn     = (bool) preg_match( '/\bMennonite Confession of Faith\b/i', $citations );
		$has_umjc     = (bool) preg_match( '/\bUMJC Statement of Faith\b/i', $citations );
		$has_lds      = (bool) preg_match( '/\bLDS Articles of Faith\b/i', $citations );
		$has_jw       = (bool) preg_match( '/\bJW Beliefs\b/i', $citations );
		$has_rd       = (bool) preg_match( '/\bRichmond Declaration(?:\s+of Faith)?\b/i', $citations );
		$has_coc      = (bool) preg_match( '/\bChurches of Christ Teaching\b/i', $citations );
		$has_sa       = (bool) preg_match( '/\bSalvation Army Doctrines\b/i', $citations );
		$has_cs       = (bool) preg_match( '/\bChristian Science Tenets\b/i', $citations );
		$has_nae      = (bool) preg_match( '/\bNAE Statement of Faith\b/i', $citations );
		$has_efca     = (bool) preg_match( '/\bEFCA Statement of Faith\b/i', $citations );
		$has_cc       = (bool) preg_match( '/\bCalvary Chapel Statement of Faith\b/i', $citations );
		$has_vineyard = (bool) preg_match( '/\bVineyard USA Core Values\b|\bVineyard USA\b/i', $citations );
		$has_doc      = (bool) preg_match( '/\bDisciples Preamble\b/i', $citations );
		$has_ucc      = (bool) preg_match( '/\bUCC Statement of Faith\b/i', $citations );
		$has_pwf      = (bool) preg_match( '/\bPWF Statement of Faith\b/i', $citations );
		$has_solas    = (bool) preg_match( '/\bFive Solas\b|\bApostles[’\'] Creed\b/i', $citations );

		// Bind known confession families to presets (do not trust digest prose that may mention other traditions as "do not cite").
		if ( $has_cic && 'catholic' !== $preset ) {
			return false;
		}
		if ( $has_bfm && ! in_array( $preset, array( 'sbc', 'baptist' ), true ) ) {
			return false;
		}
		if ( $has_upci && 'upci' !== $preset ) {
			return false;
		}
		if ( $has_ag && 'assemblies_god' !== $preset ) {
			return false;
		}
		if ( $has_sda && 'adventist' !== $preset ) {
			return false;
		}
		if ( $has_cog && 'church_of_god' !== $preset ) {
			return false;
		}
		if ( $has_iphc && 'iphc' !== $preset ) {
			return false;
		}
		if ( $has_wcf && 'reformed' !== $preset ) {
			return false;
		}
		if ( $has_ac && 'lutheran' !== $preset ) {
			return false;
		}
		if ( $has_39 && 'anglican' !== $preset ) {
			return false;
		}
		if ( $has_eastern && ! in_array( $preset, array( 'orthodox', 'greek_orthodox', 'russian_orthodox' ), true ) ) {
			return false;
		}
		if ( $has_oriental && ! in_array( $preset, array( 'oriental_orthodox', 'coptic', 'ethiopian_orthodox', 'armenian' ), true ) ) {
			return false;
		}
		if ( $has_umc && 'methodist' !== $preset ) {
			return false;
		}
		if ( $has_ame && 'ame' !== $preset ) {
			return false;
		}
		if ( $has_naz && 'holiness' !== $preset ) {
			return false;
		}
		if ( $has_nh && 'baptist' !== $preset ) {
			return false;
		}
		if ( $has_menn && 'mennonite' !== $preset ) {
			return false;
		}
		if ( $has_umjc && 'messianic' !== $preset ) {
			return false;
		}
		if ( $has_lds && 'lds' !== $preset ) {
			return false;
		}
		if ( $has_jw && 'jehovah_witnesses' !== $preset ) {
			return false;
		}
		if ( $has_rd && 'quaker' !== $preset ) {
			return false;
		}
		if ( $has_coc && 'church_of_christ' !== $preset ) {
			return false;
		}
		if ( $has_sa && 'salvation_army' !== $preset ) {
			return false;
		}
		if ( $has_cs && 'christian_science' !== $preset ) {
			return false;
		}
		if ( $has_nae && 'nondenom' !== $preset ) {
			return false;
		}
		if ( $has_efca && 'evangelical_free' !== $preset ) {
			return false;
		}
		if ( $has_cc && 'calvary_chapel' !== $preset ) {
			return false;
		}
		if ( $has_vineyard && 'vineyard' !== $preset ) {
			return false;
		}
		if ( $has_doc && 'disciples_christ' !== $preset ) {
			return false;
		}
		if ( $has_ucc && 'congregational' !== $preset ) {
			return false;
		}
		if ( $has_pwf && 'pentecostal' !== $preset ) {
			return false;
		}
		if ( $has_solas && 'general' !== $preset ) {
			return false;
		}

		unset( $digest );
		return true;
	}

	/**
	 * Remove invented foreign confession mentions from commentary when they conflict with the active tradition.
	 *
	 * @param string               $text     Commentary text.
	 * @param array<string, mixed> $resolved Resolve payload.
	 * @return string
	 */
	public static function strip_foreign_tradition_citations_from_text( $text, $resolved ) {
		$text   = (string) $text;
		$digest = isset( $resolved['digest'] ) ? (string) $resolved['digest'] : '';
		$preset = isset( $resolved['preset'] ) ? sanitize_key( (string) $resolved['preset'] ) : '';

		if ( 'catholic' !== $preset && false === stripos( $digest, 'CIC' ) && false === stripos( $digest, 'CCC' ) ) {
			$text = preg_replace( '/\bCIC\s*cc?\.?\s*[\d,\s\-–—]+/iu', '', $text );
			$text = preg_replace( '/\bCCC\s*[\d,\s\-–—]+/iu', '', $text );
			$text = preg_replace( '/\b(?:Code of\s+)?Canon Law\b/iu', '', $text );
			$text = preg_replace( '/\bCatechism(?:\s+of\s+the\s+Catholic\s+Church)?\b/iu', '', $text );
		}

		return trim( preg_replace( '/[ \t]{2,}/', ' ', (string) $text ) );
	}

	/**
	 * Pull a compact citations summary from a doctrine digest's Cited references block.
	 *
	 * @param string $digest Digest text from tradition pack builder.
	 * @return string
	 */
	public static function extract_cited_references_summary( $digest ) {
		$digest = (string) $digest;
		if ( '' === $digest || false === strpos( $digest, 'Cited references' ) ) {
			return '';
		}

		$parts = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $digest ) as $line ) {
			$line = trim( (string) $line );
			if ( ! preg_match( '/^-\s*Topic\s+/i', $line ) ) {
				continue;
			}
			if ( preg_match( '/—\s*((?:CIC|CCC|BF&M|UPCI|AG\s*16|COG|IPHC|Classical Pentecostal|Westminster|WCF|Augsburg|LCMS Brief Statement|Thirty-Nine|Nicene|Apostles[’\'] Creed|Five Solas|Eastern Orthodox|Oriental Orthodox|First Three Ecumenical Councils|Miaphysite|Holy Mysteries|UMC Articles|AME Articles|Nazarene Articles|SDA\s*28\s*Fundamental Beliefs|New Hampshire Baptist|1689\s+London Baptist|LBC\s*1689|Mennonite Confession|UMJC Statement|LDS Articles of Faith|JW Beliefs|Richmond Declaration|Churches of Christ Teaching|Salvation Army Doctrines|Christian Science Tenets|NAE Statement of Faith|EFCA Statement of Faith|Calvary Chapel Statement of Faith|Vineyard USA|Disciples Preamble|UCC Statement of Faith|PWF Statement of Faith)[^—]+)/iu', $line, $m ) ) {
				$parts[] = trim( $m[1], " \t;" );
			}
		}

		$parts = array_values( array_unique( array_filter( $parts ) ) );
		return implode( '; ', array_slice( $parts, 0, 4 ) );
	}

	/**
	 * Turn tradition citation text into HTML with external links (new tab) where known.
	 *
	 * BF&M articles deep-link to bfm.sbc.net anchors; CIC/CCC link to Vatican indexes.
	 *
	 * @param string $citations Plain citation summary.
	 * @return string Safe HTML (empty when no citations).
	 */
	public static function format_citations_html( $citations ) {
		$citations = trim( wp_strip_all_tags( (string) $citations ) );
		if ( '' === $citations ) {
			return '';
		}

		$segments   = preg_split( '/\s*;\s*/', $citations );
		$html_parts = array();

		foreach ( $segments as $segment ) {
			$segment = trim( (string) $segment );
			if ( '' === $segment ) {
				continue;
			}

			if ( preg_match( '/BF&M/i', $segment ) ) {
				$html_parts[] = self::linkify_bfm_citation_segment( $segment );
				continue;
			}

			$known = self::tradition_statement_url( $segment );
			if ( '' !== $known ) {
				$html_parts[] = '<a class="thw-study-finder__citation-link" href="' . esc_url( $known ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $segment ) . '</a>';
				continue;
			}

			if ( preg_match( '/^CIC\b/i', $segment ) ) {
				$url          = 'https://www.vatican.va/archive/cod-iuris-canonici/cic_index_en.html';
				$html_parts[] = '<a class="thw-study-finder__citation-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $segment ) . '</a>';
				continue;
			}

			if ( preg_match( '/^CCC\b/i', $segment ) ) {
				$url          = 'https://www.vatican.va/archive/ENG0015/_INDEX.HTM';
				$html_parts[] = '<a class="thw-study-finder__citation-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $segment ) . '</a>';
				continue;
			}

			$html_parts[] = esc_html( $segment );
		}

		return implode( '; ', $html_parts );
	}

	/**
	 * Official statement URL for known tradition confession labels in citation text.
	 *
	 * @param string $segment Citation segment.
	 * @return string URL or empty.
	 */
	private static function tradition_statement_url( $segment ) {
		$segment = (string) $segment;
		if ( preg_match( '/AG\s*16\s*Fundamental Truths|Assemblies of God/i', $segment ) ) {
			return 'https://ag.org/Beliefs/Statement-of-Fundamental-Truths';
		}
		if ( preg_match( '/UPCI/i', $segment ) ) {
			return 'https://www.upci.org/about/our-beliefs';
		}
		if ( preg_match( '/COG\s*Declaration|Church of God Declaration/i', $segment ) ) {
			return 'https://churchofgod.org/beliefs/declaration-of-faith/';
		}
		if ( preg_match( '/IPHC/i', $segment ) ) {
			return 'https://iphc.org/wp-content/uploads/2023/04/IPHC-Manual-April-1.pdf';
		}
		if ( preg_match( '/Westminster Confession|\bWCF\b/i', $segment ) ) {
			return 'https://www.pcaac.org/bco/westminster-confession/';
		}
		if ( preg_match( '/Augsburg Confession/i', $segment ) ) {
			return 'https://bookofconcord.org/augsburg-confession/';
		}
		if ( preg_match( '/LCMS Brief Statement/i', $segment ) ) {
			return 'https://www.lcms.org/about/beliefs/doctrine/brief-statement-of-lcms-doctrinal-position';
		}
		if ( preg_match( '/Thirty-Nine Articles/i', $segment ) ) {
			return 'https://www.churchofengland.org/prayer-and-worship/worship-texts-and-resources/book-common-prayer/articles-religion';
		}
		if ( preg_match( '/Oriental Orthodox|First Three Ecumenical Councils|Miaphysite/i', $segment ) ) {
			return 'https://copticorthodox.church/en/faith/';
		}
		if ( preg_match( '/Nicene-Constantinopolitan|Seven Ecumenical Councils|Eastern Orthodox|Holy Mysteries/i', $segment ) ) {
			return 'https://www.goarch.org/ourfaith';
		}
		if ( preg_match( '/Apostles[’\'] Creed|Nicene Creed|Five Solas/i', $segment ) ) {
			return 'https://www.oikoumene.org/resources/documents/nicene-creed';
		}
		if ( preg_match( '/NAE Statement of Faith/i', $segment ) ) {
			return 'https://www.nae.org/statement-of-faith/';
		}
		if ( preg_match( '/EFCA Statement of Faith/i', $segment ) ) {
			return 'https://www.efca.org/sof';
		}
		if ( preg_match( '/Calvary Chapel Statement of Faith/i', $segment ) ) {
			return 'https://calvarychapel.com/about/statement-of-faith/';
		}
		if ( preg_match( '/Vineyard USA/i', $segment ) ) {
			return 'https://vineyardusa.org/about/statement-of-faith/';
		}
		if ( preg_match( '/Disciples Preamble/i', $segment ) ) {
			return 'https://disciples.org/our-identity/the-design/';
		}
		if ( preg_match( '/UCC Statement of Faith/i', $segment ) ) {
			return 'https://www.ucc.org/beliefs_statement-of-faith/';
		}
		if ( preg_match( '/PWF Statement of Faith/i', $segment ) ) {
			return 'https://www.pwfellowship.org/about-us';
		}
		if ( preg_match( '/UMC Articles of Religion/i', $segment ) ) {
			return 'https://www.umc.org/en/content/articles-of-religion';
		}
		if ( preg_match( '/AME Articles of Religion/i', $segment ) ) {
			return 'https://www.ame-church.com/our-church/our-beliefs/';
		}
		if ( preg_match( '/Nazarene Articles of Faith/i', $segment ) ) {
			return 'https://nazarene.org/what-we-believe/';
		}
		if ( preg_match( '/SDA\s*28\s*Fundamental Beliefs|28\s*Fundamental Beliefs/i', $segment ) ) {
			return 'https://www.nadadventist.org/beliefs/';
		}
		if ( preg_match( '/New Hampshire Baptist Confession/i', $segment ) ) {
			return 'https://founders.org/library/new-hampshire-confession/';
		}
		if ( preg_match( '/1689\s+London Baptist Confession|LBC\s*1689/i', $segment ) ) {
			return 'https://founders.org/library/1689-confession/';
		}
		if ( preg_match( '/Mennonite Confession of Faith/i', $segment ) ) {
			return 'https://www.mennoniteusa.org/who-are-mennonites/what-we-believe/confession-of-faith/';
		}
		if ( preg_match( '/UMJC Statement of Faith/i', $segment ) ) {
			return 'https://www.umjc.org/statement-of-faith';
		}
		if ( preg_match( '/LDS Articles of Faith/i', $segment ) ) {
			return 'https://www.churchofjesuschrist.org/comeuntochrist/article/articles-of-faith';
		}
		if ( preg_match( '/JW Beliefs/i', $segment ) ) {
			return 'https://www.jw.org/en/jehovahs-witnesses/faq/jehovah-witness-beliefs/';
		}
		if ( preg_match( '/Richmond Declaration/i', $segment ) ) {
			return 'https://www.friendsunitedmeeting.org/';
		}
		if ( preg_match( '/Churches of Christ Teaching/i', $segment ) ) {
			return 'https://christiancourier.com/topics/church';
		}
		if ( preg_match( '/Salvation Army Doctrines/i', $segment ) ) {
			return 'https://www.salvationarmyusa.org/usn/what-we-believe/';
		}
		if ( preg_match( '/Christian Science Tenets/i', $segment ) ) {
			return 'https://www.christianscience.com/what-is-christian-science/tenets-of-christian-science';
		}
		return '';
	}

	/**
	 * Link BF&M article identifiers to official anchors on bfm.sbc.net.
	 *
	 * @param string $segment Citation segment containing BF&M articles.
	 * @return string
	 */
	private static function linkify_bfm_citation_segment( $segment ) {
		$base = 'https://bfm.sbc.net/bfm2000/';

		// Prefer linking each "Art. XVIII" (optionally preceded by "BF&M 2000 ").
		$linked = preg_replace_callback(
			'/(?:BF&M\s*2000\s+)?Art\.\s*([IVXLCDM]+)\b/iu',
			static function ( $m ) use ( $base ) {
				$roman = strtolower( (string) $m[1] );
				$url   = $base . '#' . rawurlencode( $roman );
				return '<a class="thw-study-finder__citation-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $m[0] ) . '</a>';
			},
			$segment
		);

		if ( null === $linked || $linked === $segment ) {
			// No Art. tokens — still link the BF&M phrase to the full document.
			return '<a class="thw-study-finder__citation-link" href="' . esc_url( $base ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $segment ) . '</a>';
		}

		// Escape any leftover plain text around the inserted anchors.
		$parts = preg_split( '/(<a\b[^>]*>.*?<\/a>)/su', $linked, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out   = '';
		foreach ( (array) $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			if ( 0 === strpos( $part, '<a ' ) ) {
				$out .= $part;
			} else {
				$out .= esc_html( $part );
			}
		}

		return $out;
	}
}
