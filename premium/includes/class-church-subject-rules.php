<?php
/**
 * Site-level church subject rules (override tradition packs for matched subjects).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Class THW_Premium_Church_Subject_Rules
 */
class THW_Premium_Church_Subject_Rules {

	const OPTION_KEY   = 'thw_ai_church_subject_rules';
	const MAX_ROWS     = 25;
	const MAX_RULE_LEN = 2000;
	const MAX_KEYWORDS = 500;

	/**
	 * Allowed stance values (same enum as tradition packs).
	 *
	 * @return array<int, string>
	 */
	public static function stance_choices() {
		return array( 'yes', 'no', 'conditional', 'pastoral', 'silence' );
	}

	/**
	 * Preset topic slugs + human labels.
	 *
	 * @return array<string, string>
	 */
	public static function topic_choices() {
		$topics = array();
		if ( class_exists( 'THW_Premium_Tradition_Packs' ) ) {
			foreach ( array_keys( THW_Premium_Tradition_Packs::topic_keywords() ) as $slug ) {
				$topics[ $slug ] = self::humanize_topic( $slug );
			}
		}
		return $topics;
	}

	/**
	 * @param string $slug Topic slug.
	 * @return string
	 */
	public static function humanize_topic( $slug ) {
		$slug = (string) $slug;
		$map  = array(
			'lords_supper'        => __( "Lord's Supper", 'hidden-word-bible-lessons' ),
			'scripture_authority' => __( 'Scripture authority', 'hidden-word-bible-lessons' ),
			'church_authority'    => __( 'Church authority', 'hidden-word-bible-lessons' ),
			'spirit_baptism'      => __( 'Spirit baptism', 'hidden-word-bible-lessons' ),
			'additional_scripture'=> __( 'Additional scripture', 'hidden-word-bible-lessons' ),
		);
		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ];
		}
		return ucwords( str_replace( '_', ' ', $slug ) );
	}

	/**
	 * Load and normalize stored rules.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_rules() {
		$raw = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		return self::sanitize_rules( $raw );
	}

	/**
	 * Sanitize rules array from options.php or tests.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_rules( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$topic_slugs = array_keys( self::topic_choices() );
		$stances     = self::stance_choices();
		$out         = array();

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( count( $out ) >= self::MAX_ROWS ) {
				break;
			}

			$topic        = isset( $row['topic'] ) ? sanitize_key( (string) $row['topic'] ) : '';
			$custom_label = isset( $row['custom_label'] ) ? sanitize_text_field( (string) $row['custom_label'] ) : '';
			$keywords     = isset( $row['keywords'] ) ? sanitize_text_field( (string) $row['keywords'] ) : '';
			$stance       = isset( $row['stance'] ) ? sanitize_key( (string) $row['stance'] ) : 'pastoral';
			$rules        = isset( $row['rules'] ) ? sanitize_textarea_field( (string) $row['rules'] ) : '';
			$enabled      = ! empty( $row['enabled'] );

			if ( '' !== $topic && ! in_array( $topic, $topic_slugs, true ) ) {
				$topic = '';
			}
			if ( ! in_array( $stance, $stances, true ) ) {
				$stance = 'pastoral';
			}

			$keywords = self::normalize_keywords( $keywords );
			$rules    = self::truncate( $rules, self::MAX_RULE_LEN );

			// Custom rows need a label + keywords; preset rows need a topic and non-empty rules.
			if ( '' === $topic ) {
				if ( '' === $custom_label || '' === $keywords || '' === trim( $rules ) ) {
					continue;
				}
			} elseif ( '' === trim( $rules ) ) {
				continue;
			}

			$out[] = array(
				'enabled'      => $enabled,
				'topic'        => $topic,
				'custom_label' => $custom_label,
				'keywords'     => $keywords,
				'stance'       => $stance,
				'rules'        => $rules,
			);
		}

		return $out;
	}

	/**
	 * Match enabled church rules against question/lesson context.
	 *
	 * @param string $context_text Free text.
	 * @return array<int, array<string, mixed>>
	 */
	public static function match( $context_text ) {
		$rules = self::get_rules();
		if ( empty( $rules ) ) {
			return array();
		}

		$haystack = strtolower( wp_strip_all_tags( (string) $context_text ) );
		$detected = class_exists( 'THW_Premium_Tradition_Packs' )
			? THW_Premium_Tradition_Packs::detect_topics( $context_text )
			: array();

		$matched = array();
		foreach ( $rules as $row ) {
			if ( empty( $row['enabled'] ) ) {
				continue;
			}

			$topic    = (string) $row['topic'];
			$keywords = self::parse_keywords( (string) $row['keywords'] );
			$hit      = false;

			if ( '' !== $topic && in_array( $topic, $detected, true ) ) {
				$hit = true;
			}

			if ( ! $hit && ! empty( $keywords ) ) {
				foreach ( $keywords as $keyword ) {
					if ( '' !== $keyword && false !== strpos( $haystack, strtolower( $keyword ) ) ) {
						$hit = true;
						break;
					}
				}
			}

			// Custom (no topic): also try custom_label tokens when keywords somehow empty after sanitize skip.
			if ( ! $hit && '' === $topic && ! empty( $row['custom_label'] ) ) {
				$label = strtolower( (string) $row['custom_label'] );
				if ( '' !== $label && false !== strpos( $haystack, $label ) ) {
					$hit = true;
				}
			}

			if ( $hit ) {
				$matched[] = $row;
			}
		}

		return $matched;
	}

	/**
	 * Preset topic slugs overridden by matched church rules.
	 *
	 * @param array<int, array<string, mixed>> $matched Matched rows.
	 * @return array<int, string>
	 */
	public static function overridden_topics( $matched ) {
		$topics = array();
		foreach ( $matched as $row ) {
			$topic = isset( $row['topic'] ) ? sanitize_key( (string) $row['topic'] ) : '';
			if ( '' !== $topic ) {
				$topics[] = $topic;
			}
		}
		return array_values( array_unique( $topics ) );
	}

	/**
	 * Format matched church rules for system instruction / checklist.
	 *
	 * @param array<int, array<string, mixed>> $matched Matched rows.
	 * @return array{rules:string, checklist:string, hash:string}
	 */
	public static function format_block( $matched ) {
		if ( empty( $matched ) ) {
			return array(
				'rules'     => '',
				'checklist' => '',
				'hash'      => '',
			);
		}

		$lines = array(
			'Church policy rules (HIGHEST PRIORITY — override tradition digests and stance gates for these subjects only):',
			'When a Church policy rule matches the question, follow the church stance and rules text over any conflicting tradition pack teaching for that subject.',
		);
		$check = array(
			'Follow Church policy rules over tradition digests for matched subjects.',
		);

		foreach ( $matched as $row ) {
			$label = self::row_label( $row );
			$stance = isset( $row['stance'] ) ? (string) $row['stance'] : 'pastoral';
			$rules  = isset( $row['rules'] ) ? trim( (string) $row['rules'] ) : '';
			$topic  = isset( $row['topic'] ) ? (string) $row['topic'] : '';

			$line = '- Subject: ' . $label;
			if ( '' !== $topic ) {
				$line .= ' (topic=' . $topic . ')';
			}
			$line .= '. Stance=' . $stance . '. Policy: ' . $rules;
			$lines[] = $line;
			$check[] = 'church:' . $label . ':' . $stance . ':' . $rules;
		}

		$text = implode( "\n", $lines );
		return array(
			'rules'     => $text,
			'checklist' => implode( "\n", $check ),
			'hash'      => md5( $text ),
		);
	}

	/**
	 * Display label for a rule row.
	 *
	 * @param array<string, mixed> $row Rule row.
	 * @return string
	 */
	public static function row_label( $row ) {
		$custom = isset( $row['custom_label'] ) ? trim( (string) $row['custom_label'] ) : '';
		if ( '' !== $custom ) {
			return $custom;
		}
		$topic = isset( $row['topic'] ) ? (string) $row['topic'] : '';
		if ( '' !== $topic ) {
			return self::humanize_topic( $topic );
		}
		return __( 'Custom subject', 'hidden-word-bible-lessons' );
	}

	/**
	 * Stable hash of all stored rules (for cache invalidation even when none match).
	 *
	 * @return string
	 */
	public static function rules_hash() {
		return md5( wp_json_encode( self::get_rules() ) );
	}

	/**
	 * @param string $keywords Comma-separated keywords.
	 * @return string
	 */
	private static function normalize_keywords( $keywords ) {
		$parts = self::parse_keywords( $keywords );
		$joined = implode( ', ', $parts );
		return self::truncate( $joined, self::MAX_KEYWORDS );
	}

	/**
	 * @param string $keywords Raw keywords.
	 * @return array<int, string>
	 */
	private static function parse_keywords( $keywords ) {
		$parts = preg_split( '/\s*,\s*/', (string) $keywords );
		if ( ! is_array( $parts ) ) {
			return array();
		}
		$out = array();
		foreach ( $parts as $part ) {
			$part = trim( (string) $part );
			if ( '' !== $part ) {
				$out[] = $part;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param string $text Text.
	 * @param int    $max  Max length.
	 * @return string
	 */
	private static function truncate( $text, $max ) {
		$text = (string) $text;
		if ( strlen( $text ) <= $max ) {
			return $text;
		}
		return substr( $text, 0, $max );
	}
}
