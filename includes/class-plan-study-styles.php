<?php
/**
 * Reading-plan study style registry.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Plan_Study_Styles
 */
class HWBL_Plan_Study_Styles {

	/**
	 * Available styles.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function all() {
		return array(
			'devotional' => array(
				'label'               => __( 'Devotional', 'hidden-word-bible-lessons' ),
				'description'         => __( 'A warm, practical Scripture-first reflection.', 'hidden-word-bible-lessons' ),
				'prompt_instructions' => "Write exactly 4 or 5 short paragraphs in HTML using only <p> tags (no headings, lists, or markdown).\nReturn raw HTML only — do not wrap it in markdown code fences.\nGround the study in the verse and the plan subject. Give concrete examples of how to apply this truth in daily life today.\nStay pastoral, warm, and Scripture-first. Do not invent Bible quotations beyond the text supplied.",
			),
			'lectio'    => array(
				'label'               => __( 'Lectio Divina', 'hidden-word-bible-lessons' ),
				'description'         => __( 'Slow, meditative reading with room for prayerful reflection.', 'hidden-word-bible-lessons' ),
				'prompt_instructions' => "Write 2 or 3 short paragraphs maximum in sparse, unhurried language using only <p> tags. Slow down; do not explain everything, and leave room for the reader's own reflection. End with one silent-reflection or prayer prompt rather than an application paragraph. Return raw HTML only, with no markdown or invented Bible quotations.",
			),
			'soap'      => array(
				'label'               => __( 'SOAP', 'hidden-word-bible-lessons' ),
				'description'         => __( 'Scripture, observation, application, and prayer.', 'hidden-word-bible-lessons' ),
				'prompt_instructions' => "Return structured HTML, not flowing prose, using only <p> and <strong> tags. Write four short labeled sections: <strong>Scripture</strong> (restate the focus without re-quoting the whole verse), <strong>Observation</strong> (2-3 sentences on what the text says), <strong>Application</strong> (2-3 concrete, personal sentences), and <strong>Prayer</strong> (a 2-3 sentence first-person prayer). No headings, markdown, or invented Bible quotations.",
			),
			'expository' => array(
				'label'               => __( 'Expository', 'hidden-word-bible-lessons' ),
				'description'         => __( 'A precise phrase-by-phrase explanation of the verse.', 'hidden-word-bible-lessons' ),
				'prompt_instructions' => "Write 4-6 teacherly, precise paragraphs in HTML using only <p> tags. Walk through the verse phrase by phrase. You may name original-language nuance in plain English without requiring citations, but do not invent quotations or unsupported details. Return raw HTML only, with no headings, lists, or markdown.",
			),
			'narrative' => array(
				'label'               => __( 'Narrative', 'hidden-word-bible-lessons' ),
				'description'         => __( 'Experience the passage through its biblical story and setting.', 'hidden-word-bible-lessons' ),
				'prompt_instructions' => "Retell the passage's context as a short story or scene in 2-4 paragraphs, grounded strictly in the supplied biblical text and without invented dialogue beyond what Scripture attests. Add one closing paragraph connecting the scene to the reader's day. Use only <p> tags and return raw HTML without markdown.",
			),
			'socratic'  => array(
				'label'               => __( 'Question-driven', 'hidden-word-bible-lessons' ),
				'description'         => __( 'Reflect through three open-ended questions.', 'hidden-word-bible-lessons' ),
				'prompt_instructions' => "Write one short 1-2 sentence framing paragraph, then exactly 3 open-ended reflection questions in an HTML <ol> with <li> items. Skip other prose. The questions should naturally prepare the reader to journal and should not require answers inline. Return raw HTML only using <p>, <ol>, and <li>, with no markdown.",
			),
			'takeaway'  => array(
				'label'               => __( 'One-line takeaway', 'hidden-word-bible-lessons' ),
				'description'         => __( 'A concise truth, explanation, and prayer in under 80 words.', 'hidden-word-bible-lessons' ),
				'prompt_instructions' => "Keep the total output under 80 words. Begin with one bolded single-sentence takeaway using <p><strong>...</strong></p>, then one short 2-3 sentence explanatory paragraph, then a one-sentence prayer. Use only <p> and <strong> tags. Return raw HTML only with no markdown or invented Bible quotations.",
			),
		);
	}

	/**
	 * Resolve a requested style.
	 *
	 * @param string $requested Requested slug.
	 * @return string
	 */
	public static function resolve( $requested ) {
		$requested = sanitize_key( (string) $requested );
		$styles    = self::all();
		return isset( $styles[ $requested ] ) ? $requested : 'devotional';
	}
}
