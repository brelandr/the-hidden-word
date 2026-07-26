<?php
/**
 * Helper functions for The Hidden Word Premium.
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'THW_PREMIUM_FUNCTIONS_LOADED' ) ) {
	return;
}
define( 'THW_PREMIUM_FUNCTIONS_LOADED', true );

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.
/**
 * Register a public shortcode with hwbl_ prefix (and thw_ only when not integrated).
 *
 * @param string   $legacy_tag Legacy thw_* shortcode tag.
 * @param callable $callback   Shortcode callback.
 * @return void
 */
function thw_premium_register_shortcode( $legacy_tag, $callback ) {
	$hwbl_tag = preg_replace( '/^thw_/', 'hwbl_', (string) $legacy_tag );
	add_shortcode( $hwbl_tag, $callback );

	// WordPress.org requires 4+ character prefixes; skip thw_* when bundled into free.
	if ( ! defined( 'HWBL_INTEGRATED_PREMIUM' ) || ! HWBL_INTEGRATED_PREMIUM ) {
		add_shortcode( $legacy_tag, $callback );
	}
}

/**
 * Whether content contains a shortcode under hwbl_ and/or legacy thw_ tag.
 *
 * @param string $content    Post content.
 * @param string $legacy_tag Legacy thw_* shortcode tag.
 * @return bool
 */
function thw_premium_content_has_shortcode( $content, $legacy_tag ) {
	$legacy_tag = (string) $legacy_tag;
	$hwbl_tag   = preg_replace( '/^thw_/', 'hwbl_', $legacy_tag );

	return has_shortcode( $content, $hwbl_tag ) || has_shortcode( $content, $legacy_tag );
}

/**
 * Whether either the hwbl_ or legacy thw_ shortcode is registered.
 *
 * @param string $legacy_tag Legacy thw_* shortcode tag.
 * @return bool
 */
function thw_premium_shortcode_exists( $legacy_tag ) {
	$legacy_tag = (string) $legacy_tag;
	$hwbl_tag   = preg_replace( '/^thw_/', 'hwbl_', $legacy_tag );

	return shortcode_exists( $hwbl_tag ) || shortcode_exists( $legacy_tag );
}

/**
 * Whether front-end AI is available (toggle, license, and provider).
 *
 * @return bool
 */
function thw_premium_ai_frontend_available() {
	return '' === thw_premium_ai_frontend_unavailable_reason();
}

/**
 * Licensed / restricted translation slugs that must not have verse text
 * embedded into AI prompts (display via API/UI is fine; prompting is not).
 *
 * @return string[]
 */
function thw_premium_ai_restricted_scripture_translations() {
	$slugs = array(
		'niv',
		'esv',
		'nlt',
		'nasb',
		'csb',
		'nkjv',
		'amp',
		'net',
		'leb',
	);

	/**
	 * Filter translation slugs whose scripture text must not be sent to AI models.
	 *
	 * @param string[] $slugs Translation slugs.
	 */
	$slugs = apply_filters( 'hwbl_ai_restricted_scripture_translations', $slugs );
	if ( ! is_array( $slugs ) ) {
		return array();
	}

	return array_values(
		array_unique(
			array_filter(
				array_map(
					static function ( $slug ) {
						return sanitize_key( (string) $slug );
					},
					$slugs
				)
			)
		)
	);
}

/**
 * Whether AI prompts may include the actual scripture wording for a translation.
 *
 * Free / public-domain translations may embed text. Licensed editions (NIV, ESV, …)
 * should be explained by reference only; the UI still renders official text separately.
 *
 * @param string $translation Translation slug.
 * @return bool
 */
function thw_premium_ai_may_embed_scripture_text( $translation ) {
	$translation = sanitize_key( (string) $translation );
	if ( '' === $translation ) {
		// Unknown translation: fail closed (do not embed).
		return false;
	}

	$allowed = ! in_array( $translation, thw_premium_ai_restricted_scripture_translations(), true );

	/**
	 * Filter whether scripture wording for a translation may be embedded in AI prompts.
	 *
	 * @param bool   $allowed     Whether embedding is allowed.
	 * @param string $translation Translation slug.
	 */
	return (bool) apply_filters( 'hwbl_ai_may_embed_scripture_text', $allowed, $translation );
}

/**
 * Human-readable reason front-end AI is unavailable, or empty when ready.
 *
 * @return string
 */
function thw_premium_ai_frontend_unavailable_reason() {
	if ( ! function_exists( 'hwbl_is_ai_enabled' ) || ! hwbl_is_ai_enabled() ) {
		return __( 'AI features are turned off. Enable them under Bible Lessons → Settings → Enable AI Features.', 'hidden-word-bible-lessons' );
	}

	if ( ! class_exists( 'THW_Premium_License' ) || ! THW_Premium_License::is_licensed() ) {
		return __( 'Advanced features are not available.', 'hidden-word-bible-lessons' );
	}

	if ( ! class_exists( 'THW_Premium_AI_Client' ) || ! THW_Premium_AI_Client::is_configured() ) {
		if ( class_exists( 'THW_Premium_AI_Client' ) && THW_Premium_AI_Client::uses_core_ai() ) {
			if ( ! THW_Premium_AI_Client::core_ai_environment_enabled() ) {
				return __( 'WordPress AI is disabled in this environment. Remove or set WP_AI_SUPPORT to true in wp-config.php, then reconnect a provider under Settings → Connectors.', 'hidden-word-bible-lessons' );
			}

			$connected = THW_Premium_AI_Client::get_connected_ai_provider_ids();
			if ( empty( $connected ) ) {
				return __( 'No WordPress AI connector is ready for text generation. Go to Settings → Connectors, Connect OpenAI (or another provider) with a valid API key, and keep Enable AI Features turned on under Bible Lessons → Settings. Or add a temporary OpenAI/Claude key under Bible Lessons → Advanced.', 'hidden-word-bible-lessons' );
			}

			return __( 'A connector shows Connected, but WordPress blocked text generation for this plugin (often Tools → Connector Approvals). Approve Hidden Word Bible Lessons there, or re-save your OpenAI/Claude key under Bible Lessons → Advanced so generation can use it directly.', 'hidden-word-bible-lessons' );
		}

		return __( 'No AI provider configured. Add an OpenAI or Claude API key under Bible Lessons → Advanced, or upgrade to WordPress 7.0+ and use Settings → Connectors.', 'hidden-word-bible-lessons' );
	}

	return '';
}

/**
 * Default guardrails for front-end lesson explanations.
 *
 * @return string
 */
function thw_premium_default_ai_explain_rules() {
	return __(
		'Use a Scripture-first, warm, pastoral tone for a Christian non-denominational church. Speak in plain language for everyday adults and small groups. Keep Jesus Christ central: His life, death, resurrection, and lordship. Affirm the authority and reliability of the Bible without inventing verses or details not in the provided text. Prefer a shared evangelical baseline (for example the NAE Statement of Faith): one God in three persons, salvation by grace through faith in Christ alone, the Bible as God’s Word, and the call to love God and neighbor. Avoid denominational labels, partisan politics, and arguing against other Christian traditions. When secondary issues arise (baptism mode, spiritual gifts, end-times details, church structure), present Scripture carefully, note that faithful Christians differ, and point people back to Christ and the local church. Encourage prayer, obedience, and conversation with trusted church leaders. For grief, abuse, addiction, divorce, mental health, or crisis topics, be gentle and clear that this tool does not replace pastoral care, counseling, or professional help. Keep explanations concise, hopeful, and practical for discipleship.',
		'hidden-word-bible-lessons'
	);
}

/**
 * Short shared core used when building tradition presets (not the empty-box default).
 *
 * @return string
 */
function thw_premium_shared_ai_explain_rules_core() {
	return __(
		'Use a Scripture-first, concise, pastoral tone. Explain in plain language for a small-group Bible study participant. Do not invent or quote scripture beyond what is provided. Encourage prayer and discussion with others. Do not replace professional counseling for sensitive life topics.',
		'hidden-word-bible-lessons'
	);
}

/**
 * Neutral AI rules for Verse of the Day explanations (no tradition framing).
 *
 * @return string
 */
function thw_premium_votd_neutral_explain_rules() {
	return __(
		'Use a Scripture-first, warm, pastoral tone for a general Bible study audience. Explain in plain language without denominational framing or tradition-specific doctrine. Do not invent or quote scripture beyond what is provided. Encourage prayer and discussion with others. Do not replace professional counseling for sensitive life topics.',
		'hidden-word-bible-lessons'
	);
}

/**
 * Curated tradition presets for AI explain rules (Christian / Bible-using traditions).
 *
 * Limited to audiences that use the Christian Bible translations available in the plugin.
 *
 * @return array<string, array{label:string, rules:string}>
 */
function thw_premium_ai_explain_rule_presets() {
	$shared = thw_premium_shared_ai_explain_rules_core();

	$framings = array(
		'general'               => array(
			'label'   => __( 'General Protestant', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a general Protestant audience: Apostles’ Creed, Nicene Creed, and the Five Solas. Prefer those identifiers—paraphrase only; do not invent a denominational handbook.', 'hidden-word-bible-lessons' ),
		),
		'catholic'              => array(
			'label'   => __( 'Catholic', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Catholic audience: emphasize Christ, Scripture, prayer, and life in the Church. Note continuity with historic Christian teaching without inventing magisterial claims beyond the provided text.', 'hidden-word-bible-lessons' ),
		),
		'orthodox'              => array(
			'label'   => __( 'Eastern Orthodox', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an Eastern Orthodox audience: Nicene Creed and Seven Ecumenical Councils; Holy Mysteries. Prefer Eastern Orthodox identifiers—not Oriental Miaphysite or First Three Councils only.', 'hidden-word-bible-lessons' ),
		),
		'greek_orthodox'        => array(
			'label'   => __( 'Greek Orthodox', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Greek Orthodox audience: same Eastern Orthodox dogma (Nicene Creed; Seven Councils) with Greek liturgical life (e.g. GOARCH). Jurisdiction differs in administration, not dogma.', 'hidden-word-bible-lessons' ),
		),
		'russian_orthodox'      => array(
			'label'   => __( 'Russian Orthodox', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Russian Orthodox audience: same Eastern Orthodox dogma (Nicene Creed; Seven Councils) with Russian liturgical life (e.g. OCA/ROC). Jurisdiction differs in administration, not dogma.', 'hidden-word-bible-lessons' ),
		),
		'oriental_orthodox'     => array(
			'label'   => __( 'Oriental Orthodox', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an Oriental Orthodox audience: Nicene Creed, First Three Ecumenical Councils, and Miaphysite Christology. Do NOT cite Councils 4–7 or Chalcedon as Oriental dogma.', 'hidden-word-bible-lessons' ),
		),
		'coptic'                => array(
			'label'   => __( 'Coptic Orthodox', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Coptic Orthodox audience: Oriental Orthodox faith (Nicene Creed, First Three Councils, Miaphysite Christology), martyrdom-minded faithfulness, and life in the Church.', 'hidden-word-bible-lessons' ),
		),
		'ethiopian_orthodox'    => array(
			'label'   => __( 'Ethiopian Orthodox', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an Ethiopian Orthodox Tewahedo audience: Oriental Orthodox faith (Nicene Creed, First Three Councils, Miaphysite/Tewahedo Christology), fasting-aware discipleship, and life in the Church.', 'hidden-word-bible-lessons' ),
		),
		'armenian'              => array(
			'label'   => __( 'Armenian Apostolic (Oriental Orthodox)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an Armenian Apostolic audience: Oriental Orthodox faith (Nicene Creed, First Three Councils, Miaphysite Christology), ancient Armenian Christian heritage, and life in the Church.', 'hidden-word-bible-lessons' ),
		),
		'anglican'              => array(
			'label'   => __( 'Anglican / Episcopal', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an Anglican or Episcopal audience: emphasize Scripture, tradition, and reasoned discipleship with a reverent, liturgical-aware pastoral tone.', 'hidden-word-bible-lessons' ),
		),
		'sbc'                   => array(
			'label'   => __( 'Southern Baptist Convention', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Southern Baptist audience: emphasize the authority of Scripture, personal faith in Jesus Christ, believer’s baptism, and the local church. Keep the tone warm and evangelistic without attacking other traditions.', 'hidden-word-bible-lessons' ),
		),
		'baptist'               => array(
			'label'   => __( 'Baptist (general)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Baptist audience: Scripture, believer’s baptism, congregational life. Prefer New Hampshire (1833) or 1689 London Baptist Confession identifiers—not BF&M (use SBC for BF&M).', 'hidden-word-bible-lessons' ),
		),
		'lutheran'              => array(
			'label'   => __( 'Lutheran', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Lutheran audience: highlight Law and Gospel, grace alone through faith, and Christ-centered reading of Scripture.', 'hidden-word-bible-lessons' ),
		),
		'methodist'             => array(
			'label'   => __( 'United Methodist Church (UMC)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a United Methodist audience: prevenient/justifying/sanctifying grace; Scripture primary in the Wesleyan quadrilateral; holiness of heart and life. Prefer UMC Articles of Religion from the Book of Discipline.', 'hidden-word-bible-lessons' ),
		),
		'ame'                   => array(
			'label'   => __( 'African Methodist Episcopal (AME) Church', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an AME audience: Scripture, liberation in Christ, dignity, justice, and Methodist Articles of Religion from the Doctrine and Discipline—without inventing Discipline dumps.', 'hidden-word-bible-lessons' ),
		),
		'reformed'              => array(
			'label'   => __( 'Presbyterian / Reformed', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Presbyterian or Reformed audience: emphasize God’s sovereignty, covenant faithfulness, and careful Scripture exposition with pastoral humility.', 'hidden-word-bible-lessons' ),
		),
		'pentecostal'           => array(
			'label'   => __( 'Pentecostal / Charismatic', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Pentecostal or Charismatic audience: prefer Pentecostal World Fellowship (PWF) Statement of Faith on a classical Pentecostal base—Spirit baptism, gifts, holy living. Avoid sensational claims.', 'hidden-word-bible-lessons' ),
		),
		'assemblies_god'        => array(
			'label'   => __( 'Assemblies of God', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an Assemblies of God audience: emphasize the 16 Fundamental Truths and the four cardinal doctrines (Salvation, Spirit Baptism, Healing, Second Coming). Keep Trinitarian language and avoid inventing spiritual experiences.', 'hidden-word-bible-lessons' ),
		),
		'church_of_god'         => array(
			'label'   => __( 'Church of God (Cleveland, TN)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Church of God (Cleveland, TN) audience: Trinitarian Holiness-Pentecostal teaching with sanctification subsequent to the new birth, Spirit baptism subsequent to a clean heart, and tongues as initial evidence.', 'hidden-word-bible-lessons' ),
		),
		'iphc'                  => array(
			'label'   => __( 'International Pentecostal Holiness Church (IPHC)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an IPHC audience: Wesleyan-Holiness and Pentecostal heritage—justification, entire sanctification, and Spirit baptism with tongues as initial evidence. Keep Trinitarian language.', 'hidden-word-bible-lessons' ),
		),
		'upci'                  => array(
			'label'   => __( 'United Pentecostal Church International (UPCI / Oneness)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a UPCI Oneness/Apostolic audience: one God; Jesus is God manifested in flesh; repentance, baptism in Jesus’ name, and Spirit baptism with tongues. Encourage holy living without inventing local dress codes.', 'hidden-word-bible-lessons' ),
		),
		'nondenom'              => array(
			'label'   => __( 'Non-denominational church', 'hidden-word-bible-lessons' ),
			'framing' => '',
		),
		'destiny_leaders'       => array(
			'label'   => __( 'Non-denominational — Destiny Leaders', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Destiny Leaders / Destiny Ministries affiliated non-denominational audience. Prefer Destiny Leaders What We Believe (destinyleaders.com/about) with a shared evangelical baseline—paraphrase only. Emphasize Scripture authority, Spirit-empowered gifts today, local-church ordinances, and biblical teaching on marriage, gender, and the sanctity of life.', 'hidden-word-bible-lessons' ),
		),
		'arc_churches'          => array(
			'label'   => __( 'Non-denominational — ARC (Association of Related Churches)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an ARC-related independent / non-denominational church. Prefer the ARC Statement of Faith (arcchurches.com/about/statement-of-faith)—paraphrase only. Emphasize Jesus’ gospel, Spirit empowerment, local-church life, and church autonomy (ARC is not a denomination with binding polity).', 'hidden-word-bible-lessons' ),
		),
		'churches_in_covenant'  => array(
			'label'   => __( 'Non-denominational — Churches In Covenant', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Churches In Covenant related audience. CIC emphasizes covenant relationships and mentoring among pastors (churchesincovenant.org) without publishing a full creedal handbook—use the Non-denominational church baseline for doctrine and do not invent CIC confession articles.', 'hidden-word-bible-lessons' ),
		),
		'evangelical_free'      => array(
			'label'   => __( 'Evangelical Free', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for an Evangelical Free audience: prefer EFCA Statement of Faith (10 articles). Scripture, personal faith in Christ, and practical discipleship—paraphrase only.', 'hidden-word-bible-lessons' ),
		),
		'calvary_chapel'        => array(
			'label'   => __( 'Calvary Chapel', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Calvary Chapel audience: prefer Calvary Chapel Statement of Faith identifiers—verse-by-verse teaching, pre-trib hope, middle-ground gifts. Paraphrase only.', 'hidden-word-bible-lessons' ),
		),
		'vineyard'              => array(
			'label'   => __( 'Vineyard', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Vineyard audience: prefer Vineyard USA Core Values & Beliefs—Kingdom already/not-yet, prayer, healing. Scripture-grounded without sensational claims.', 'hidden-word-bible-lessons' ),
		),
		'adventist'             => array(
			'label'   => __( 'Seventh-day Adventist', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Seventh-day Adventist audience: Bible alone as creed; the 28 Fundamental Beliefs as the church’s official statement of faith. Prefer SDA Art. numbers (Sabbath, remnant, sanctuary, gift of prophecy). Never invent Ellen White quotations.', 'hidden-word-bible-lessons' ),
		),
		'lds'                   => array(
			'label'   => __( 'Latter-day Saints (Mormon)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Latter-day Saint audience: prefer LDS Articles of Faith Art. 1–13 identifiers. Bible text only in this tool—never invent Book of Mormon, D&C, or handbook quotations.', 'hidden-word-bible-lessons' ),
		),
		'jehovah_witnesses'     => array(
			'label'   => __( 'Jehovah’s Witnesses', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Jehovah’s Witness audience: prefer JW Beliefs page identifiers (jw.org). Scripture-focused; never invent Watchtower citations or Governing Body directives.', 'hidden-word-bible-lessons' ),
		),
		'church_of_christ'      => array(
			'label'   => __( 'Churches of Christ', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Churches of Christ audience: no creed but the Bible; NT pattern—believer’s immersion, weekly Lord’s Supper, congregational autonomy. Prefer Churches of Christ Teaching identifiers.', 'hidden-word-bible-lessons' ),
		),
		'disciples_christ'      => array(
			'label'   => __( 'Christian Church (Disciples of Christ)', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Disciples of Christ audience: prefer the Preamble to the Design—unity in Christ, open table, non-creedal confession paragraph. Paraphrase only.', 'hidden-word-bible-lessons' ),
		),
		'holiness'              => array(
			'label'   => __( 'Church of the Nazarene', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Church of the Nazarene audience: Scripture, prevenient grace, justification, and entire sanctification as a second work of grace. Prefer Manual Articles of Faith identifiers.', 'hidden-word-bible-lessons' ),
		),
		'mennonite'             => array(
			'label'   => __( 'Mennonite / Anabaptist', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Mennonite/Anabaptist audience: Jesus-shaped discipleship, peace, community. Prefer Confession of Faith in a Mennonite Perspective (1995) Art. numbers.', 'hidden-word-bible-lessons' ),
		),
		'quaker'                => array(
			'label'   => __( 'Quaker / Friends', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Quaker/Friends audience: Scripture under the Spirit’s leading; peace and integrity testimonies. Prefer Richmond Declaration identifiers; acknowledge meeting diversity.', 'hidden-word-bible-lessons' ),
		),
		'congregational'        => array(
			'label'   => __( 'Congregational / United Church of Christ', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Congregational or UCC audience: prefer UCC Statement of Faith identifiers—thoughtful faith, justice-minded compassion. Paraphrase only.', 'hidden-word-bible-lessons' ),
		),
		'salvation_army'        => array(
			'label'   => __( 'Salvation Army', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Salvation Army audience: prefer the 11 Doctrines. Practical holiness and mercy; explain non-practice of water baptism/Communion gently.', 'hidden-word-bible-lessons' ),
		),
		'christian_science'     => array(
			'label'   => __( 'Christian Science', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Christian Science audience: prefer the Six Tenets identifiers. Bible lesson first—never invent Science and Health dumps or guarantee healings.', 'hidden-word-bible-lessons' ),
		),
		'messianic'             => array(
			'label'   => __( 'Messianic Jewish', 'hidden-word-bible-lessons' ),
			'framing' => __( 'Frame for a Messianic Jewish audience: Yeshua as Messiah; prefer UMJC Statement of Faith identifiers. Torah-shaped life under the New Covenant—do not invent binding halakha.', 'hidden-word-bible-lessons' ),
		),
	);

	$presets = array();
	foreach ( $framings as $slug => $item ) {
		$rules = $shared;
		if ( '' !== trim( (string) $item['framing'] ) ) {
			$rules .= ' ' . $item['framing'];
		}
		$presets[ $slug ] = array(
			'label' => $item['label'],
			'rules' => $rules,
		);
	}

	// Empty-box default and Non-denominational preset share the same sample rules.
	$presets['nondenom']['rules'] = thw_premium_default_ai_explain_rules();

	return $presets;
}

/**
 * Resolve which explain-rules preset slug should display in settings.
 *
 * @param string $rules_text Saved rules textarea value.
 * @param string $saved_slug Saved preset slug.
 * @return string
 */
function thw_premium_resolve_ai_explain_rules_preset( $rules_text, $saved_slug = '' ) {
	$presets = thw_premium_ai_explain_rule_presets();
	$slug    = sanitize_key( (string) $saved_slug );

	if ( 'custom' === $slug || '' === $slug ) {
		return 'custom';
	}

	if ( isset( $presets[ $slug ] ) && trim( (string) $rules_text ) === trim( $presets[ $slug ]['rules'] ) ) {
		return $slug;
	}

	return 'custom';
}

/**
 * Whether front-end users may pick their own tradition/religion for AI framing.
 *
 * @return bool
 */
function thw_premium_user_tradition_enabled() {
	// rest_sanitize_boolean treats "0"/0/"false" as false; bare (bool) "0" is true in PHP.
	return (bool) rest_sanitize_boolean( get_option( 'thw_ai_allow_user_tradition', false ) );
}

/**
 * Whether the second-pass "does this violate the tradition rules?" AI
 * compliance check runs before Explain/Study Finder responses are returned.
 * Costs one extra AI call per request (two if a violation triggers a
 * retry), so site owners can turn it off. Defaults on.
 *
 * @return bool
 */
function thw_premium_ai_compliance_check_enabled() {
	return (bool) get_option( 'thw_ai_compliance_check_enabled', true );
}

/**
 * Allowed values for what happens when a response is still flagged after
 * the one compliance-check retry.
 *
 * @return array<string, string>
 */
function thw_premium_ai_compliance_failure_action_choices() {
	return array(
		'flag'  => __( 'Show it anyway, with a stronger on-screen warning', 'hidden-word-bible-lessons' ),
		'block' => __( 'Block it and ask the visitor to try again', 'hidden-word-bible-lessons' ),
	);
}

/**
 * How a site owner wants a still-non-compliant response (after one retry)
 * handled: 'flag' shows it with a stronger warning, 'block' withholds it and
 * returns an error instead. Defaults to 'flag' — availability over silence,
 * with an obvious disclaimer as the backstop.
 *
 * @return string 'flag'|'block'
 */
function thw_premium_get_ai_compliance_failure_action() {
	$value   = get_option( 'thw_ai_compliance_failure_action', 'flag' );
	$choices = thw_premium_ai_compliance_failure_action_choices();
	return isset( $choices[ $value ] ) ? $value : 'flag';
}

/**
 * User meta key for saved tradition preset.
 *
 * @return string
 */
function thw_premium_user_tradition_meta_key() {
	return '_thw_ai_tradition_preset';
}

/**
 * Sanitize a tradition preset slug against the curated registry.
 *
 * @param string $slug Raw slug.
 * @return string Empty string when invalid.
 */
function thw_premium_sanitize_tradition_preset( $slug, $require_enabled = false ) {
	$slug    = sanitize_key( (string) $slug );
	$presets = thw_premium_ai_explain_rule_presets();
	if ( ! isset( $presets[ $slug ] ) ) {
		return '';
	}
	if ( $require_enabled && ! thw_premium_is_tradition_enabled( $slug ) ) {
		return '';
	}
	return $slug;
}

/**
 * Sanitize the site's enabled-traditions option.
 *
 * Empty array means "all traditions" (backward compatible).
 *
 * @param mixed $value Raw option value.
 * @return array<int, string>
 */
function thw_premium_sanitize_enabled_traditions( $value ) {
	$all = array_keys( thw_premium_ai_explain_rule_presets() );
	if ( ! is_array( $value ) ) {
		return array();
	}
	$slugs = array();
	foreach ( $value as $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( in_array( $slug, $all, true ) ) {
			$slugs[] = $slug;
		}
	}
	$slugs = array_values( array_unique( $slugs ) );

	// Selecting every known tradition collapses to "all" so new packs appear automatically.
	if ( count( $slugs ) === count( $all ) ) {
		return array();
	}

	return $slugs;
}

/**
 * Enabled tradition slugs for this site.
 *
 * Empty stored option = all presets enabled.
 *
 * @return array<int, string>
 */
function thw_premium_get_enabled_tradition_slugs() {
	$all    = array_keys( thw_premium_ai_explain_rule_presets() );
	$stored = get_option( 'thw_ai_enabled_traditions', array() );
	if ( ! is_array( $stored ) || array() === $stored ) {
		/**
		 * Filter enabled tradition slugs (empty stored option means all).
		 *
		 * @param array<int, string> $slugs Enabled slugs.
		 */
		return (array) apply_filters( 'thw_premium_enabled_tradition_slugs', $all );
	}

	$slugs = array();
	foreach ( $stored as $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( in_array( $slug, $all, true ) ) {
			$slugs[] = $slug;
		}
	}
	$slugs = array_values( array_unique( $slugs ) );
	if ( empty( $slugs ) ) {
		$slugs = $all;
	}

	return (array) apply_filters( 'thw_premium_enabled_tradition_slugs', $slugs );
}

/**
 * Whether a tradition slug is enabled for front-end use on this site.
 *
 * @param string $slug Tradition slug.
 * @return bool
 */
function thw_premium_is_tradition_enabled( $slug ) {
	$slug = sanitize_key( (string) $slug );
	return in_array( $slug, thw_premium_get_enabled_tradition_slugs(), true );
}

/**
 * Storage slug for the shared (tradition-neutral) Bible explanation.
 *
 * @return string
 */
function thw_premium_explain_base_tradition_slug() {
	/**
	 * Filter the shared explain tradition slug.
	 *
	 * @param string $slug Base slug.
	 */
	return sanitize_key( (string) apply_filters( 'thw_premium_explain_base_tradition_slug', 'base' ) );
}

/**
 * Legacy / fallback slugs that may hold shared base explains.
 *
 * @return array<int, string>
 */
function thw_premium_explain_base_fallback_slugs() {
	$slugs = array_values(
		array_unique(
			array_filter(
				array(
					thw_premium_explain_base_tradition_slug(),
					'nondenom',
					'general',
					'site',
				)
			)
		)
	);

	/**
	 * Filter fallback slugs consulted when loading the shared explain.
	 *
	 * @param array<int, string> $slugs Slugs.
	 */
	return (array) apply_filters( 'thw_premium_explain_base_fallback_slugs', $slugs );
}

/**
 * Whether this tradition uses the shared base explain (no per-tradition override row).
 *
 * @param string $slug Tradition slug.
 * @return bool
 */
function thw_premium_explain_tradition_uses_shared_base( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug ) {
		return true;
	}
	return in_array( $slug, thw_premium_explain_base_fallback_slugs(), true );
}

/**
 * Neutral rules text used when generating the shared base explain.
 *
 * @return string
 */
function thw_premium_explain_base_rules() {
	return thw_premium_votd_neutral_explain_rules();
}

/**
 * Sorted label map of tradition presets.
 *
 * @param bool $enabled_only When true, only site-enabled traditions (front-end).
 * @return array<string, string>
 */
function thw_premium_get_tradition_preset_choices( $enabled_only = false ) {
	$choices = array();
	$allowed = $enabled_only ? thw_premium_get_enabled_tradition_slugs() : null;
	foreach ( thw_premium_ai_explain_rule_presets() as $slug => $preset ) {
		if ( is_array( $allowed ) && ! in_array( $slug, $allowed, true ) ) {
			continue;
		}
		$choices[ $slug ] = (string) $preset['label'];
	}
	natcasesort( $choices );
	return $choices;
}

/**
 * Whether the front-end tradition <select> should be shown.
 *
 * Requires the site picker toggle and at least two enabled traditions.
 *
 * @return bool
 */
function thw_premium_show_tradition_select() {
	if ( ! thw_premium_user_tradition_enabled() ) {
		return false;
	}
	return count( thw_premium_get_tradition_preset_choices( true ) ) >= 2;
}

/**
 * Site default tradition slug for the front-end picker.
 *
 * @return string
 */
function thw_premium_get_site_default_tradition_preset() {
	$rules = get_option( 'thw_ai_explain_rules', thw_premium_default_ai_explain_rules() );
	$saved = get_option( 'thw_ai_explain_rules_preset', 'general' );
	$slug  = thw_premium_resolve_ai_explain_rules_preset( $rules, $saved );
	if ( 'custom' === $slug || '' === $slug ) {
		$slug = 'general';
	}
	if ( thw_premium_is_tradition_enabled( $slug ) ) {
		return $slug;
	}
	$enabled = thw_premium_get_enabled_tradition_slugs();
	return ! empty( $enabled[0] ) ? (string) $enabled[0] : 'general';
}

/**
 * Current user's saved tradition preset, or site default.
 *
 * @param int $user_id User ID (0 = current).
 * @return string
 */
function thw_premium_get_user_tradition_preset( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$default = thw_premium_get_site_default_tradition_preset();

	if ( $user_id < 1 ) {
		return $default;
	}

	$saved = thw_premium_sanitize_tradition_preset(
		(string) get_user_meta( $user_id, thw_premium_user_tradition_meta_key(), true ),
		true
	);

	return $saved ? $saved : $default;
}

/**
 * Persist a tradition preset for a logged-in user.
 *
 * @param int    $user_id User ID.
 * @param string $slug    Preset slug.
 * @return bool
 */
function thw_premium_set_user_tradition_preset( $user_id, $slug ) {
	$user_id = (int) $user_id;
	$slug    = thw_premium_sanitize_tradition_preset( $slug, true );
	if ( $user_id < 1 || '' === $slug ) {
		return false;
	}
	update_user_meta( $user_id, thw_premium_user_tradition_meta_key(), $slug );
	return true;
}

/**
 * Directory containing tradition doctrine pack JSON files.
 *
 * @return string
 */
function thw_premium_tradition_pack_dir() {
	if ( class_exists( 'THW_Premium_Tradition_Packs' ) ) {
		return THW_Premium_Tradition_Packs::pack_dir();
	}
	return trailingslashit( THW_PREMIUM_DIR ) . 'data/traditions/';
}

/**
 * Load a tradition doctrine pack by slug (with extends merge + cache).
 *
 * @param string $slug Tradition slug.
 * @return array<string, mixed>|null
 */
function thw_premium_load_tradition_pack( $slug ) {
	if ( ! class_exists( 'THW_Premium_Tradition_Packs' ) ) {
		return null;
	}
	return THW_Premium_Tradition_Packs::load_pack( $slug );
}

/**
 * Detect doctrine topics in free text.
 *
 * @param string $text Context text.
 * @return array<int, string>
 */
function thw_premium_detect_doctrine_topics( $text ) {
	if ( ! class_exists( 'THW_Premium_Tradition_Packs' ) ) {
		return array();
	}
	return THW_Premium_Tradition_Packs::detect_topics( $text );
}

/**
 * Build a compact tradition digest for AI system instructions.
 *
 * @param array<string, mixed>|null $pack         Loaded pack (or null for gates-only).
 * @param string                    $context_text Text for topic detection.
 * @return array{text:string, hash:string, topics:array<int,string>, checklist:string}
 */
function thw_premium_build_tradition_digest( $pack, $context_text = '' ) {
	if ( ! class_exists( 'THW_Premium_Tradition_Packs' ) ) {
		return array(
			'text'      => '',
			'hash'      => '',
			'topics'    => array(),
			'checklist' => '',
		);
	}
	return THW_Premium_Tradition_Packs::build_digest( $pack, $context_text );
}

/**
 * Resolve explain rules / tradition pack for an AI request.
 *
 * Always-on doctrine routing: when a tradition slug resolves, load its pack and
 * build a digest (framing + primary digests + stance gates) for the context.
 *
 * @param string $requested_preset Optional preset from the front end.
 * @param string $context_text     Lesson or topic text for topic detection.
 * @param bool   $force_tradition  Honor requested preset even when user tradition picker is off (admin preload).
 * @return array{rules:string, preset:string, pack:?array, digest:string, digest_hash:string, checklist:string, church_rules:array}
 */
function thw_premium_resolve_explain_rules_for_request( $requested_preset = '', $context_text = '', $force_tradition = false ) {
	$site_rules = get_option( 'thw_ai_explain_rules', thw_premium_default_ai_explain_rules() );
	if ( '' === trim( (string) $site_rules ) ) {
		$site_rules = thw_premium_default_ai_explain_rules();
	}

	$presets = thw_premium_ai_explain_rule_presets();
	$slug    = '';

	if ( $force_tradition || thw_premium_user_tradition_enabled() ) {
		$slug = thw_premium_sanitize_tradition_preset( $requested_preset, ! $force_tradition );
		if ( '' === $slug && is_user_logged_in() ) {
			$slug = thw_premium_get_user_tradition_preset();
		}
		if ( '' === $slug ) {
			$slug = thw_premium_get_site_default_tradition_preset();
		}
	} else {
		$saved = get_option( 'thw_ai_explain_rules_preset', 'general' );
		$resolved_slug = thw_premium_resolve_ai_explain_rules_preset( $site_rules, $saved );
		if ( 'custom' !== $resolved_slug && '' !== $resolved_slug ) {
			$slug = $resolved_slug;
		}
	}

	$pack   = null;
	$preset = 'site';
	$rules  = $site_rules;

	if ( '' !== $slug && isset( $presets[ $slug ] ) ) {
		$preset = $slug;
		$rules  = (string) $presets[ $slug ]['rules'];
		if ( class_exists( 'THW_Premium_Tradition_Packs' ) ) {
			$pack = THW_Premium_Tradition_Packs::load_pack( $slug );
		}
	}

	$digest_payload = array(
		'text'      => '',
		'hash'      => '',
		'topics'    => array(),
		'checklist' => '',
	);

	$church_matched = array();
	$exclude_topics = array();
	if ( class_exists( 'THW_Premium_Church_Subject_Rules' ) ) {
		$church_matched = THW_Premium_Church_Subject_Rules::match( $context_text );
		$exclude_topics = THW_Premium_Church_Subject_Rules::overridden_topics( $church_matched );
	}

	if ( class_exists( 'THW_Premium_Tradition_Packs' ) ) {
		$digest_payload = THW_Premium_Tradition_Packs::build_digest( $pack, $context_text, $exclude_topics );
	}

	$combined_rules = trim( $rules );
	if ( '' !== trim( (string) $digest_payload['text'] ) ) {
		$combined_rules .= "\n\n" . $digest_payload['text'];
	}

	$checklist = (string) $digest_payload['checklist'];
	$digest_hash = (string) $digest_payload['hash'];

	if ( ! empty( $church_matched ) ) {
		$church_block = THW_Premium_Church_Subject_Rules::format_block( $church_matched );
		if ( '' !== $church_block['rules'] ) {
			$combined_rules = $church_block['rules'] . "\n\n" . $combined_rules;
		}
		if ( '' !== $church_block['checklist'] ) {
			$checklist = $church_block['checklist'] . "\n" . $checklist;
		}
		$digest_hash = md5( $digest_hash . '|' . $church_block['hash'] );
	} elseif ( class_exists( 'THW_Premium_Church_Subject_Rules' ) ) {
		// Invalidate caches when church rules change even if none matched this query.
		$digest_hash = md5( $digest_hash . '|' . THW_Premium_Church_Subject_Rules::rules_hash() );
	}

	return array(
		'rules'       => $combined_rules,
		'preset'      => $preset,
		'pack'        => $pack,
		'digest'      => (string) $digest_payload['text'],
		'digest_hash' => $digest_hash,
		'checklist'   => $checklist,
		'church_rules'=> $church_matched,
	);
}

/**
 * Wrap resolved rules text (site default or a tradition preset) into a system
 * instruction that tells the model the rules are mandatory and take priority
 * over its own general knowledge. Passed as a real system-role message /
 * using_system_instruction() rather than concatenated into the user prompt,
 * so it carries more weight with the model and can't be diluted or
 * "talked over" by the lesson content that follows it.
 *
 * @param string $rules Resolved rules text (site default, tradition, and/or doctrine digest).
 * @return string
 */
function thw_premium_build_ai_system_instruction( $rules ) {
	$rules = trim( (string) $rules );
	if ( '' === $rules ) {
		$rules = thw_premium_default_ai_explain_rules();
	}

	return sprintf(
		/* translators: %s: resolved site or tradition-specific AI rules text */
		__(
			"You are a Bible study assistant embedded in a church or ministry website. The Rules below describe the audience's tradition, tone, doctrinal digests, and logic gates. They are mandatory: follow them exactly and give them priority over any conflicting general knowledge you may have. If Church policy rules are present for a subject, follow those church rules over tradition digests and stance gates for that subject only. If a doctrinal logic gate matches the question or lesson theme, state the tradition, give a clear Yes/No/Conditional/Pastoral/Silence stance, and list required conditions or next steps. If the Rules include a Cited references block, you MUST include those exact identifiers in your answer—do not omit them and never invent identifiers from other traditions (for example do not cite Catholic Canon Law/CIC/CCC unless those appear in the Rules). Never invent primary-source citations beyond the digests provided. When the Rules already identify a specific tradition, answer for that tradition only—do not ask the visitor which denomination or church they belong to, and do not replace the answer with a multi-tradition survey. If you are not sure whether a statement fits these Rules, leave it out rather than guess.\n\nRules:\n%s",
			'hidden-word-bible-lessons'
		),
		$rules
	);
}

/**
 * Allowed HTML for tradition select markup at late-escape call sites.
 *
 * @return array<string, array<string, bool>>
 */
function thw_premium_kses_tradition_select() {
	return array(
		'label'  => array(
			'class' => true,
			'for'   => true,
		),
		'span'   => array(
			'class' => true,
		),
		'select' => array(
			'id'                        => true,
			'name'                      => true,
			'class'                     => true,
			'data-thw-tradition-select' => true,
		),
		'option' => array(
			'value'    => true,
			'selected' => true,
		),
	);
}

/**
 * Echo tradition select markup with late escaping.
 *
 * @param array<string, mixed> $args Select args.
 */
function thw_premium_the_tradition_select( $args = array() ) {
	echo wp_kses( thw_premium_render_tradition_select( $args ), thw_premium_kses_tradition_select() );
}

/**
 * Render a front-end tradition/religion select (when enabled).
 *
 * @param array<string, mixed> $args {
 *     @type string $id       Select element id.
 *     @type string $name     Select name/class hint.
 *     @type string $selected Selected slug.
 *     @type string $class    Extra CSS classes.
 * }
 * @return string Empty when feature disabled.
 */
function thw_premium_render_tradition_select( $args = array() ) {
	if ( ! thw_premium_show_tradition_select() ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'id'       => 'thw-ai-tradition',
			'name'     => 'tradition',
			'selected' => thw_premium_get_user_tradition_preset(),
			'class'    => 'thw-ai-tradition-select',
		)
	);

	$selected = thw_premium_sanitize_tradition_preset( (string) $args['selected'], true );
	if ( '' === $selected ) {
		$selected = thw_premium_get_site_default_tradition_preset();
	}

	$choices = thw_premium_get_tradition_preset_choices( true );
	ob_start();
	?>
	<label class="thw-ai-tradition-label" for="<?php echo esc_attr( (string) $args['id'] ); ?>">
		<span class="thw-ai-tradition-label-text"><?php esc_html_e( 'My faith tradition', 'hidden-word-bible-lessons' ); ?></span>
		<select
			id="<?php echo esc_attr( (string) $args['id'] ); ?>"
			name="<?php echo esc_attr( (string) $args['name'] ); ?>"
			class="<?php echo esc_attr( (string) $args['class'] ); ?>"
			data-thw-tradition-select
		>
			<?php foreach ( $choices as $slug => $label ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $selected, $slug ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
	<?php
	return (string) ob_get_clean();
}

/**
 * Default guardrails for keyword Bible study matching.
 *
 * @return string
 */
function thw_premium_default_ai_study_rules() {
	return __(
		'Recommend Scripture that clearly and honestly relates to the user’s topic for a Christian non-denominational audience. Prefer well-known, contextually solid passages over obscure or stretched connections. Lead with Christ-centered hope and gospel clarity when the topic involves sin, suffering, identity, relationships, or purpose. For sensitive topics (divorce, remarriage, abuse, grief, anxiety, addiction, sexuality, parenting conflict), respond pastorally: acknowledge pain, avoid shame language, do not invent verses, and note that pastoral care or professional counseling may be needed. Prefer passages that teach trust in God, repentance and forgiveness, love of neighbor, wisdom, and practical discipleship. Do not invent or misquote Scripture. Keep commentary brief and useful for personal study or a small group. When several passages fit, choose a short balanced set (Old and New Testament when natural) rather than a long list.',
		'hidden-word-bible-lessons'
	);
}

/**
 * Allowed study-search audience values.
 *
 * @return array<string, string>
 */
function thw_premium_ai_study_audience_choices() {
	return array(
		'logged_in' => __( 'Logged-in users only', 'hidden-word-bible-lessons' ),
		'everyone'  => __( 'Everyone', 'hidden-word-bible-lessons' ),
		'disabled'  => __( 'Disabled', 'hidden-word-bible-lessons' ),
	);
}

/**
 * Current study-search audience setting.
 *
 * @return string logged_in|everyone|disabled
 */
function thw_premium_get_ai_study_audience() {
	$audience = sanitize_key( (string) get_option( 'thw_ai_study_audience', 'logged_in' ) );
	$choices  = thw_premium_ai_study_audience_choices();
	return isset( $choices[ $audience ] ) ? $audience : 'logged_in';
}

/**
 * Whether the current viewer may use AI study search.
 *
 * @return bool
 */
function thw_premium_current_user_can_use_ai_study_search() {
	$audience = thw_premium_get_ai_study_audience();
	if ( 'disabled' === $audience ) {
		return false;
	}
	if ( 'everyone' === $audience ) {
		return true;
	}
	return is_user_logged_in();
}

/**
 * Default Ask a Question guardrails.
 *
 * @return string
 */
function thw_premium_default_ai_ask_rules() {
	return __(
		'Answer pastoral Bible questions clearly and briefly for a Christian non-denominational church audience.

Stay Scripture-first and Christ-centered. Prefer a shared evangelical baseline (for example the NAE Statement of Faith): one God in three persons, salvation by grace through faith in Christ alone, the Bible as God’s Word, and the call to love God and neighbor.

When a tradition is already selected, answer for that tradition only—never ask which denomination or church the visitor belongs to, and never give a multi-tradition survey instead of answering.

Do not invent Bible verses, confession citations, or denominational handbook quotes. If you cite a reference, keep it accurate and limited to what is supported by the provided text or Cited references.

Use plain, warm language suitable for everyday adults and small groups. Avoid denominational labels, partisan politics, and arguing against other Christian traditions. On secondary issues (baptism mode, spiritual gifts, end-times details, church structure), present Scripture carefully, note that faithful Christians may differ, and point people back to Christ and the local church.

For sensitive personal situations—grief, abuse, addiction, divorce, mental health, sexuality, crisis, or relationship conflict—be gentle, avoid shame language, encourage prayer, and recommend trusted pastoral care or professional help when needed. AI is not a substitute for counseling or pastoral care.

Keep answers concise, hopeful, and practical for discipleship. End with one clear next step when helpful (pray, read a passage, talk with a church leader, or join a small group).',
		'hidden-word-bible-lessons'
	);
}

/**
 * Ask a Question audience choices (same shape as study search).
 *
 * @return array<string, string>
 */
function thw_premium_ai_ask_audience_choices() {
	return thw_premium_ai_study_audience_choices();
}

/**
 * Current Ask a Question audience setting.
 *
 * @return string logged_in|everyone|disabled
 */
function thw_premium_get_ai_ask_audience() {
	$audience = sanitize_key( (string) get_option( 'thw_ai_ask_audience', 'logged_in' ) );
	$choices  = thw_premium_ai_ask_audience_choices();
	return isset( $choices[ $audience ] ) ? $audience : 'logged_in';
}

/**
 * Whether the current viewer may use Ask a Question.
 *
 * @return bool
 */
function thw_premium_current_user_can_use_ai_ask() {
	$audience = thw_premium_get_ai_ask_audience();
	if ( 'disabled' === $audience ) {
		return false;
	}
	if ( 'everyone' === $audience ) {
		return true;
	}
	return is_user_logged_in();
}

/**
 * Whether Ask a Question should include related curriculum lessons.
 *
 * @return bool
 */
function thw_premium_ai_ask_include_lessons() {
	return (bool) get_option( 'thw_ai_ask_include_lessons', true );
}

/**
 * Whether Study Finder should include related curriculum lessons.
 *
 * @return bool
 */
function thw_premium_ai_study_include_lessons() {
	return (bool) get_option( 'thw_ai_study_include_lessons', false );
}
