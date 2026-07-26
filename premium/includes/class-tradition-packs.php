<?php
/**
 * Tradition doctrine packs: load, topic detect, digest for AI routing.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_Tradition_Packs
 */
class THW_Premium_Tradition_Packs {

	const MAX_SOURCES       = 4;
	const MAX_STANCES       = 6;
	const MAX_CITATION_MAP  = 4;
	/** @deprecated Use MAX_CITATION_MAP */
	const MAX_CANON_MAP     = 4;

	/**
	 * In-request pack cache.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static $pack_cache = array();

	/**
	 * Directory containing tradition JSON packs.
	 *
	 * @return string
	 */
	public static function pack_dir() {
		return trailingslashit( THW_PREMIUM_DIR ) . 'data/traditions/';
	}

	/**
	 * Topic keyword map: topic slug => keywords.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function topic_keywords() {
		return array(
			'marriage'            => array( 'marriage', 'marry', 'married', 'wedding', 'spouse', 'husband', 'wife', 'family', 'husband and wife' ),
			'divorce'             => array( 'divorce', 'divorced', 'separated', 'separation' ),
			'annulment'           => array( 'annulment', 'annul', 'nullity', 'tribunal' ),
			'remarriage'          => array( 'remarriage', 'remarry', 'remarried', 'second marriage' ),
			'baptism'             => array( 'baptism', 'baptize', 'baptized', 'christening', 'immerse', 'immersion' ),
			'eucharist'           => array( 'eucharist', 'communion', 'mass', 'transubstantiation', 'real presence' ),
			'lords_supper'        => array( "lord's supper", 'lords supper', 'breaking of bread', 'cup and bread' ),
			'sacraments'          => array( 'sacrament', 'sacraments', 'ordinance', 'ordinances', 'holy mysteries', 'mysteries' ),
			'confession'          => array( 'confession', 'confess', 'penance', 'reconciliation', 'absolution', 'confessional' ),
			'confirmation'        => array( 'confirmation', 'confirmed', 'chrismation' ),
			'salvation'           => array( 'salvation', 'saved', 'justification', 'justify', 'born again', 'gospel', 'grace through faith' ),
			'scripture_authority' => array( 'scripture', 'bible authority', 'sola scriptura', 'inerrancy', 'inspiration', 'word of god' ),
			'church_authority'    => array( 'magisterium', 'pope', 'papal', 'bishop', 'elders', 'session', 'presbytery', 'church authority', 'local church', 'congregation' ),
			'sexuality'           => array( 'sexual', 'sexuality', 'sex', 'chastity', 'lust', 'pornography', 'lgbt', 'homosexual', 'same-sex', 'family' ),
			'prayer'              => array( 'prayer', 'pray', 'praying' ),
			'holiness'            => array( 'holiness', 'sanctification', 'holy living', 'consecration', 'entire sanctification', 'second work of grace' ),
			'sanctification'      => array( 'sanctification', 'entire sanctification', 'second work of grace', 'clean heart', 'entire consecration' ),
			'spirit_baptism'      => array( 'spirit baptism', 'baptism in the holy spirit', 'baptism with the holy ghost', 'baptism in the holy ghost', 'filled with the spirit', 'tongues', 'speaking in tongues', 'glossolalia', 'initial evidence' ),
			'healing'             => array( 'healing', 'divine healing', 'heal the sick', 'prayer for healing', 'gifts of healing' ),
			'mission'             => array( 'mission', 'evangelism', 'witness', 'disciple-making', 'justice' ),
			'sabbath'             => array( 'sabbath', 'saturday sabbath', 'seventh day', "lord's day", 'lords day' ),
			'prophecy'            => array( 'prophecy', 'prophetic', 'ellen white', 'end times prophet', 'last things', 'second coming', 'rapture', 'millennial' ),
			'additional_scripture'=> array( 'book of mormon', 'doctrine and covenants', 'pearl of great price', 'watchtower', 'science and health' ),
		);
	}

	/**
	 * Detect doctrine topics in free text.
	 *
	 * @param string $text Context text.
	 * @return array<int, string>
	 */
	public static function detect_topics( $text ) {
		$haystack = strtolower( wp_strip_all_tags( (string) $text ) );
		$found    = array();

		foreach ( self::topic_keywords() as $topic => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( '' !== $keyword && false !== strpos( $haystack, strtolower( $keyword ) ) ) {
					$found[] = $topic;
					break;
				}
			}
		}

		return array_values( array_unique( $found ) );
	}

	/**
	 * Load index.json.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function load_index() {
		$path = self::pack_dir() . 'index.json';
		if ( ! is_readable( $path ) ) {
			return array();
		}
		$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Load a raw pack file (no extends merge).
	 *
	 * @param string $slug Pack slug.
	 * @return array<string, mixed>|null
	 */
	private static function load_raw_pack_file( $slug ) {
		$slug = sanitize_key( $slug );
		if ( '' === $slug ) {
			return null;
		}

		$path = self::pack_dir() . $slug . '.json';
		if ( ! is_readable( $path ) ) {
			return null;
		}

		$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Merge parent pack into child (extends).
	 *
	 * @param array<string, mixed> $parent Parent pack.
	 * @param array<string, mixed> $child  Child pack.
	 * @return array<string, mixed>
	 */
	private static function merge_packs( $parent, $child ) {
		$merged                     = $parent;
		$merged['slug']             = isset( $child['slug'] ) ? $child['slug'] : $parent['slug'];
		$merged['label']            = ! empty( $child['label'] ) ? $child['label'] : $parent['label'];
		$merged['version']          = ! empty( $child['version'] ) ? $child['version'] : $parent['version'];
		$merged['framing']          = isset( $child['framing'] ) ? (string) $child['framing'] : (string) ( $parent['framing'] ?? '' );
		$merged['general_gates']    = ! empty( $child['general_gates'] ) && is_array( $child['general_gates'] )
			? $child['general_gates']
			: ( isset( $parent['general_gates'] ) && is_array( $parent['general_gates'] ) ? $parent['general_gates'] : array() );
		$merged['primary_sources']  = array_values(
			array_merge(
				isset( $parent['primary_sources'] ) && is_array( $parent['primary_sources'] ) ? $parent['primary_sources'] : array(),
				isset( $child['primary_sources'] ) && is_array( $child['primary_sources'] ) ? $child['primary_sources'] : array()
			)
		);

		$parent_stances = isset( $parent['stances'] ) && is_array( $parent['stances'] ) ? $parent['stances'] : array();
		$child_stances  = isset( $child['stances'] ) && is_array( $child['stances'] ) ? $child['stances'] : array();
		$by_topic       = array();
		foreach ( $parent_stances as $row ) {
			if ( ! empty( $row['topic'] ) ) {
				$by_topic[ (string) $row['topic'] ] = $row;
			}
		}
		foreach ( $child_stances as $row ) {
			if ( ! empty( $row['topic'] ) ) {
				$by_topic[ (string) $row['topic'] ] = $row;
			}
		}
		$merged['stances'] = array_values( $by_topic );

		// Child citation_map (or legacy canon_map) replaces parent when set.
		$child_map  = self::pack_citation_map( $child );
		$parent_map = self::pack_citation_map( $parent );
		if ( ! empty( $child_map ) ) {
			$merged['citation_map'] = $child_map;
			unset( $merged['canon_map'] );
		} elseif ( ! empty( $parent_map ) ) {
			$merged['citation_map'] = $parent_map;
			unset( $merged['canon_map'] );
		} else {
			unset( $merged['citation_map'], $merged['canon_map'] );
		}

		unset( $merged['extends'] );

		return $merged;
	}

	/**
	 * Resolve citation_map from a pack, accepting legacy canon_map alias.
	 *
	 * @param array<string, mixed> $pack Pack data.
	 * @return array<int, array<string, mixed>>
	 */
	private static function pack_citation_map( $pack ) {
		if ( ! empty( $pack['citation_map'] ) && is_array( $pack['citation_map'] ) ) {
			return $pack['citation_map'];
		}
		if ( ! empty( $pack['canon_map'] ) && is_array( $pack['canon_map'] ) ) {
			return $pack['canon_map'];
		}
		return array();
	}

	/**
	 * Load and merge a tradition pack by slug.
	 *
	 * @param string $slug Tradition slug.
	 * @return array<string, mixed>|null
	 */
	public static function load_pack( $slug ) {
		$slug = sanitize_key( $slug );
		if ( '' === $slug ) {
			return null;
		}

		if ( isset( self::$pack_cache[ $slug ] ) ) {
			return self::$pack_cache[ $slug ];
		}

		$index   = self::load_index();
		$version = isset( $index[ $slug ]['version'] ) ? (string) $index[ $slug ]['version'] : '1';
		$cache_key = 'thw_trad_pack_' . $slug . '_' . md5( $version );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && ! empty( $cached['slug'] ) ) {
			self::$pack_cache[ $slug ] = $cached;
			return $cached;
		}

		$child = self::load_raw_pack_file( $slug );
		if ( null === $child ) {
			return null;
		}

		$extends = isset( $child['extends'] ) ? sanitize_key( (string) $child['extends'] ) : '';
		if ( '' !== $extends && $extends !== $slug ) {
			$parent = self::load_raw_pack_file( $extends );
			if ( null !== $parent ) {
				// Support one-level chain: parent may also extend (e.g. baptist -> evangelical).
				$parent_extends = isset( $parent['extends'] ) ? sanitize_key( (string) $parent['extends'] ) : '';
				if ( '' !== $parent_extends && $parent_extends !== $extends ) {
					$grand = self::load_raw_pack_file( $parent_extends );
					if ( null !== $grand ) {
						$parent = self::merge_packs( $grand, $parent );
					}
				}
				$child = self::merge_packs( $parent, $child );
			}
		}

		self::$pack_cache[ $slug ] = $child;
		set_transient( $cache_key, $child, DAY_IN_SECONDS );

		return $child;
	}

	/**
	 * Build a compact doctrine digest for system instructions.
	 *
	 * Always includes framing + general gates. Adds topic-matched sources/stances
	 * when topics are detected; if none detected, includes a short general stance sample.
	 *
	 * @param array<string, mixed>|null $pack           Pack data.
	 * @param string                    $context_text   Lesson or query text.
	 * @param array<int, string>        $exclude_topics Topic slugs to omit from stance gates (church overrides).
	 * @return array{text:string, hash:string, topics:array<int,string>, checklist:string}
	 */
	public static function build_digest( $pack, $context_text = '', $exclude_topics = array() ) {
		$topics = self::detect_topics( $context_text );
		$lines  = array();
		$check  = array();
		$exclude_topics = array_values( array_filter( array_map( 'sanitize_key', (array) $exclude_topics ) ) );

		$label   = is_array( $pack ) && ! empty( $pack['label'] ) ? (string) $pack['label'] : 'General';
		$framing = is_array( $pack ) && ! empty( $pack['framing'] ) ? trim( (string) $pack['framing'] ) : '';
		$gates   = is_array( $pack ) && ! empty( $pack['general_gates'] ) && is_array( $pack['general_gates'] )
			? $pack['general_gates']
			: array(
				'Stay within the provided Bible text for verse claims; do not invent quotations.',
				'When a doctrinal stance gate matches, state the tradition, the stance, and any required conditions clearly.',
			);

		$lines[] = 'Tradition: ' . $label;
		if ( '' !== $framing ) {
			$lines[] = 'Framing: ' . $framing;
		}

		$lines[] = 'General doctrinal gates:';
		foreach ( $gates as $gate ) {
			$gate = trim( (string) $gate );
			if ( '' !== $gate ) {
				$lines[] = '- ' . $gate;
				$check[] = $gate;
			}
		}

		$citation_rows = self::match_citation_map_rows(
			is_array( $pack ) ? self::pack_citation_map( $pack ) : array(),
			$topics
		);
		if ( ! empty( $citation_rows ) ) {
			$lines[] = 'Cited references (cite only these identifiers; do not invent others):';
			foreach ( $citation_rows as $row ) {
				$topic_list = isset( $row['topics'] ) && is_array( $row['topics'] )
					? implode( '/', array_map( 'strval', $row['topics'] ) )
					: 'general';
				$note       = isset( $row['note'] ) ? trim( (string) $row['note'] ) : '';
				$parts      = self::format_citation_parts( $row );
				$cite_line  = '- Topic ' . $topic_list . ( $parts ? ' — ' . implode( '; ', $parts ) : '' );
				if ( '' !== $note ) {
					$cite_line .= ' — ' . $note;
				}
				$lines[] = $cite_line;
				$check[] = 'cite:' . $topic_list . ':' . implode( '|', $parts ) . ':' . $note;
			}
		}

		$sources = is_array( $pack ) && ! empty( $pack['primary_sources'] ) && is_array( $pack['primary_sources'] )
			? $pack['primary_sources']
			: array();
		$stances = is_array( $pack ) && ! empty( $pack['stances'] ) && is_array( $pack['stances'] )
			? $pack['stances']
			: array();

		$matched_sources = array();
		foreach ( $sources as $source ) {
			$source_topics = isset( $source['topics'] ) && is_array( $source['topics'] ) ? $source['topics'] : array();
			if ( empty( $topics ) || array_intersect( $topics, $source_topics ) ) {
				$matched_sources[] = $source;
			}
		}
		if ( empty( $matched_sources ) && ! empty( $sources ) && empty( $topics ) ) {
			$matched_sources = array_slice( $sources, 0, 2 );
		}
		$matched_sources = array_slice( $matched_sources, 0, self::MAX_SOURCES );

		if ( ! empty( $matched_sources ) ) {
			$lines[] = 'Primary source digests (copyright-safe paraphrases — do not invent citations beyond these):';
			foreach ( $matched_sources as $source ) {
				$title   = isset( $source['title'] ) ? (string) $source['title'] : 'Source';
				$cite    = isset( $source['cite'] ) ? (string) $source['cite'] : '';
				$excerpt = isset( $source['excerpt'] ) ? (string) $source['excerpt'] : '';
				$lines[] = '- ' . $title . ( $cite ? " [{$cite}]" : '' ) . ': ' . $excerpt;
			}
		}

		$matched_stances = array();
		foreach ( $stances as $row ) {
			$topic = isset( $row['topic'] ) ? (string) $row['topic'] : '';
			if ( '' === $topic ) {
				continue;
			}
			if ( ! empty( $exclude_topics ) && in_array( $topic, $exclude_topics, true ) ) {
				continue;
			}
			if ( empty( $topics ) || in_array( $topic, $topics, true ) ) {
				$matched_stances[] = $row;
			}
		}
		if ( empty( $matched_stances ) && ! empty( $stances ) && empty( $topics ) ) {
			// Always-on: include core stances even without keyword hits.
			foreach ( array( 'scripture_authority', 'salvation', 'baptism', 'marriage' ) as $core ) {
				if ( ! empty( $exclude_topics ) && in_array( $core, $exclude_topics, true ) ) {
					continue;
				}
				foreach ( $stances as $row ) {
					if ( isset( $row['topic'] ) && $core === $row['topic'] ) {
						$matched_stances[] = $row;
					}
				}
			}
			if ( empty( $matched_stances ) ) {
				foreach ( array_slice( $stances, 0, 3 ) as $row ) {
					$topic = isset( $row['topic'] ) ? (string) $row['topic'] : '';
					if ( '' !== $topic && ! empty( $exclude_topics ) && in_array( $topic, $exclude_topics, true ) ) {
						continue;
					}
					$matched_stances[] = $row;
				}
			}
		}
		$matched_stances = array_slice( $matched_stances, 0, self::MAX_STANCES );

		if ( ! empty( $matched_stances ) ) {
			$lines[] = 'Doctrinal logic gates (apply when relevant; output clear stance + conditions):';
			foreach ( $matched_stances as $row ) {
				$topic      = (string) $row['topic'];
				$stance     = isset( $row['stance'] ) ? (string) $row['stance'] : 'pastoral';
				$summary    = isset( $row['summary'] ) ? (string) $row['summary'] : '';
				$conditions = isset( $row['conditions'] ) && is_array( $row['conditions'] ) ? $row['conditions'] : array();
				$forbid     = isset( $row['forbid'] ) && is_array( $row['forbid'] ) ? $row['forbid'] : array();
				$line       = "- Topic {$topic}: stance={$stance}. {$summary}";
				if ( ! empty( $conditions ) ) {
					$line .= ' Conditions: ' . implode( '; ', array_map( 'strval', $conditions ) ) . '.';
				}
				if ( ! empty( $forbid ) ) {
					$line .= ' Forbidden: ' . implode( '; ', array_map( 'strval', $forbid ) ) . '.';
				}
				$lines[] = $line;
				$check[] = "{$topic}:{$stance}:{$summary}";
			}
		}

		$lines[] = 'Output contract: Name the tradition. If a gate matches the user question or lesson theme, give a clear Yes/No/Conditional/Pastoral/Silence stance and list required conditions or next steps. When Cited references are listed above, you MUST include those exact identifiers in the answer. Never invent primary sources or documents from a different tradition beyond the digests and cited identifiers above. Prefer the provided Bible lesson text for verse claims.';

		$text = implode( "\n", $lines );
		$hash = md5( $text );

		return array(
			'text'      => $text,
			'hash'      => $hash,
			'topics'    => $topics,
			'checklist' => implode( "\n", $check ),
		);
	}

	/**
	 * Format citation identifiers for a map row (CIC/CCC and/or confession articles).
	 *
	 * @param array<string, mixed> $row Citation map row.
	 * @return array<int, string>
	 */
	private static function format_citation_parts( $row ) {
		$parts  = array();
		$canons = isset( $row['canons'] ) && is_array( $row['canons'] ) ? array_map( 'strval', $row['canons'] ) : array();
		$ccc    = isset( $row['ccc'] ) && is_array( $row['ccc'] ) ? array_map( 'strval', $row['ccc'] ) : array();
		if ( ! empty( $canons ) ) {
			$parts[] = 'CIC cc. ' . implode( ', ', $canons );
		}
		if ( ! empty( $ccc ) ) {
			$parts[] = 'CCC ' . implode( ', ', $ccc );
		}

		$articles = isset( $row['articles'] ) && is_array( $row['articles'] ) ? array_map( 'strval', $row['articles'] ) : array();
		if ( ! empty( $articles ) ) {
			$confession = ! empty( $row['confession'] ) ? trim( (string) $row['confession'] ) : 'BF&M 2000';
			$art_list   = array();
			foreach ( $articles as $article ) {
				$article = trim( $article );
				if ( '' === $article ) {
					continue;
				}
				// Roman or numeric → "Art. VII" / "Art. 7"; titled sections stay as-is.
				if ( preg_match( '/^(?:[IVXLCDM]+|\d+)$/i', $article ) ) {
					$art_list[] = 'Art. ' . $article;
				} else {
					$art_list[] = $article;
				}
			}
			if ( ! empty( $art_list ) ) {
				$parts[] = $confession . ' ' . implode( ', ', $art_list );
			}
		}

		return $parts;
	}

	/**
	 * Match citation_map rows to detected topics (capped).
	 *
	 * @param array<int, array<string, mixed>> $citation_map Pack citation map rows.
	 * @param array<int, string>               $topics       Detected topics.
	 * @return array<int, array<string, mixed>>
	 */
	private static function match_citation_map_rows( $citation_map, $topics ) {
		if ( empty( $citation_map ) ) {
			return array();
		}

		$matched = array();
		foreach ( $citation_map as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$row_topics = isset( $row['topics'] ) && is_array( $row['topics'] ) ? $row['topics'] : array();
			if ( empty( $topics ) || array_intersect( $topics, $row_topics ) ) {
				$matched[] = $row;
			}
		}

		if ( empty( $matched ) && empty( $topics ) ) {
			foreach ( $citation_map as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$row_topics = isset( $row['topics'] ) && is_array( $row['topics'] ) ? $row['topics'] : array();
				if ( array_intersect( array( 'marriage', 'scripture_authority', 'church_authority', 'salvation', 'baptism' ), $row_topics ) ) {
					$matched[] = $row;
				}
			}
			if ( empty( $matched ) ) {
				$matched = array_slice( $citation_map, 0, 2 );
			}
		}

		return array_slice( $matched, 0, self::MAX_CITATION_MAP );
	}
}
