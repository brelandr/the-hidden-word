<?php
/**
 * Phase 15: second plans for Hard Places + Life Season leftovers.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hwbl_plan_day_body' ) ) {
	/**
	 * Build a four-beat day body (open, text, heart, practice).
	 *
	 * @param string $open     Pastoral hook.
	 * @param string $text     Exposition.
	 * @param string $heart    Heart/application.
	 * @param string $practice Practice + prayer prompt.
	 * @return string
	 */
	function hwbl_plan_day_body( $open, $text, $heart, $practice ) {
		return '<p>' . $open . '</p><p>' . $text . '</p><p>' . $heart . '</p><p>' . $practice . '</p>';
	}
}

/**
 * Phase 15 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase15_definitions() {
	return array(
		array(
			'title'     => 'Cast Your Cares (7 days)',
			'topic'     => 'life-problems',
			'shareable' => false,
			'excerpt'   => 'A second walk through overwhelm—casting cares, receiving daily bread, and trusting God with what you cannot carry alone.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Cast all your anxiety',
					'verse_ref' => '1 Peter 5:7',
					'body'      => hwbl_plan_day_body(
						'Overwhelm stacks tasks, fears, and unfinished conversations. God invites casting, not performing.',
						'“Casting all your anxieties on him, because he cares for you.” Care is personal—God is not bored by your pile.',
						'What anxiety are you still carrying as if He does not care?',
						'<strong>Practice:</strong> Write three cares and physically cross them out as cast. <em>Caring God, I cast these on You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Sufficient for the day',
					'verse_ref' => 'Matthew 6:34',
					'body'      => hwbl_plan_day_body(
						'Overwhelm often lives in tomorrow. Jesus keeps you in today.',
						'“Therefore do not be anxious about tomorrow… Sufficient for the day is its own trouble.”',
						'Which tomorrow-fear is emptying today’s strength?',
						'<strong>Practice:</strong> Close tomorrow’s list and do only the next faithful step. <em>Father, give me today. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Come to Me',
					'verse_ref' => 'Matthew 11:28',
					'body'      => hwbl_plan_day_body(
						'Jesus does not scold the weary; He invites them.',
						'“Come to me, all who labor and are heavy laden, and I will give you rest.”',
						'Where are you laboring without coming?',
						'<strong>Practice:</strong> Sit quietly for two minutes and pray, “I come.” <em>Jesus, I come with my load. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'My grace is sufficient',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'Weakness is not disqualification; it is a place grace shows.',
						'“My grace is sufficient for you, for my power is made perfect in weakness.”',
						'Where do you need sufficient grace more than sufficient strength?',
						'<strong>Practice:</strong> Name one weakness and ask for grace there. <em>Lord, Your grace is enough here. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Be still',
					'verse_ref' => 'Psalm 46:10',
					'body'      => hwbl_plan_day_body(
						'Stillness is not laziness when life feels loud; it is trust.',
						'“Be still, and know that I am God.” Knowing God is the point of stillness.',
						'What noise keeps you from knowing He is God?',
						'<strong>Practice:</strong> Silence notifications for ten minutes and breathe Psalm 46:10. <em>God, I am still before You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'The Lord will fight',
					'verse_ref' => 'Exodus 14:14',
					'body'      => hwbl_plan_day_body(
						'Some battles require standing still while God fights.',
						'“The Lord will fight for you, and you have only to be silent.”',
						'Where are you fighting what only God can handle?',
						'<strong>Practice:</strong> Hand one conflict to God and refuse to rehearse it today. <em>Lord, fight for me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Peace of God',
					'verse_ref' => 'Philippians 4:6-7',
					'body'      => hwbl_plan_day_body(
						'Prayer turns overwhelm into petition and thanksgiving.',
						'“Do not be anxious about anything, but in everything by prayer… And the peace of God… will guard your hearts.”',
						'What request and what thanks can you bring today?',
						'<strong>Practice:</strong> Pray one request and one thanks aloud. <em>God of peace, guard my heart. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'One Day at a Time (7 days)',
			'topic'     => 'alcoholism',
			'shareable' => false,
			'excerpt'   => 'A second walk for those seeking freedom from alcohol—honest confession, daily dependence, and hope in Christ (not a substitute for recovery care).',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Walk in the light',
					'verse_ref' => '1 John 1:7',
					'body'      => hwbl_plan_day_body(
						'This reading plan is pastoral Scripture study, not crisis counseling, legal advice, or medical care. If you are in immediate danger, call your local emergency number. In the US and Canada, call or text 988 (Suicide &amp; Crisis Lifeline). Reach a trusted pastor, counselor, or doctor alongside these daily readings. Freedom begins in light, not secrecy.',
						'“But if we walk in the light, as he is in the light, we have fellowship with one another, and the blood of Jesus his Son cleanses us from all sin.”',
						'Where do you need light more than self-management?',
						'<strong>Practice:</strong> Tell a trusted person one honest sentence. <em>Lord, bring me into the light. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Confess and be healed',
					'verse_ref' => 'James 5:16',
					'body'      => hwbl_plan_day_body(
						'Confession is not humiliation; it is a path to healing.',
						'“Therefore, confess your sins to one another and pray for one another, that you may be healed.”',
						'Who can pray with you this week?',
						'<strong>Practice:</strong> Schedule one prayer conversation. <em>God, heal what I confess. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'A way of escape',
					'verse_ref' => '1 Corinthians 10:13',
					'body'      => hwbl_plan_day_body(
						'Temptation is common; escape is promised.',
						'“God is faithful… he will also provide the way of escape, that you may be able to endure it.”',
						'What escape route can you prepare before temptation hits?',
						'<strong>Practice:</strong> Write your escape plan (call, leave, pray, meeting). <em>Faithful God, show me the way out. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Be sober-minded',
					'verse_ref' => '1 Peter 5:8',
					'body'      => hwbl_plan_day_body(
						'Sobriety of mind guards the heart when the enemy prowls.',
						'“Be sober-minded; be watchful. Your adversary the devil prowls around like a roaring lion.”',
						'Where do you need watchfulness tonight?',
						'<strong>Practice:</strong> Remove one trigger from easy reach today. <em>Lord, keep me sober-minded. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'New creation',
					'verse_ref' => '2 Corinthians 5:17',
					'body'      => hwbl_plan_day_body(
						'Identity in Christ is deeper than the old pattern.',
						'“Therefore, if anyone is in Christ, he is a new creation. The old has passed away; behold, the new has come.”',
						'What old label do you need to stop wearing?',
						'<strong>Practice:</strong> Write “in Christ” beside your name. <em>Jesus, make me new today. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Renew your mind',
					'verse_ref' => 'Romans 12:2',
					'body'      => hwbl_plan_day_body(
						'Freedom grows as the mind is renewed, not only the will strained.',
						'“Do not be conformed to this world, but be transformed by the renewal of your mind.”',
						'What thought pattern needs renewing?',
						'<strong>Practice:</strong> Replace one lie with one verse on a card. <em>Spirit, renew my mind. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Sufficient grace',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'Weak days still belong under grace.',
						'“My grace is sufficient for you, for my power is made perfect in weakness.”',
						'Where do you need grace for one more day?',
						'<strong>Practice:</strong> Pray for today’s strength only. <em>Lord, Your grace is enough for this day. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'New Path, Renewed Mind (7 days)',
			'topic'     => 'addiction',
			'shareable' => false,
			'excerpt'   => 'A second addiction plan focused on renewed mind, honest community, and walking a new path in Christ—alongside real recovery help.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Put off the old self',
					'verse_ref' => 'Ephesians 4:22-24',
					'body'      => hwbl_plan_day_body(
						'This reading plan is pastoral Scripture study, not crisis counseling, legal advice, or medical care. If you are in immediate danger, call your local emergency number. In the US and Canada, call or text 988 (Suicide &amp; Crisis Lifeline). Reach a trusted pastor, counselor, or doctor alongside these daily readings. Freedom is both gift and path—put off, be renewed, put on.',
						'“Put off your old self… and be renewed in the spirit of your minds, and put on the new self.”',
						'What old pattern are you ready to name without shame?',
						'<strong>Practice:</strong> Name the pattern to God and one safe person. <em>Lord, renew my mind. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'No longer slaves',
					'verse_ref' => 'Romans 6:14',
					'body'      => hwbl_plan_day_body(
						'Grace does not wink at slavery; it frees slaves.',
						'“For sin will have no dominion over you, since you are not under law but under grace.”',
						'Where does sin still claim dominion language over you?',
						'<strong>Practice:</strong> Speak Romans 6:14 aloud as belonging. <em>Jesus, I am under grace. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Take every thought captive',
					'verse_ref' => '2 Corinthians 10:5',
					'body'      => hwbl_plan_day_body(
						'Addiction often starts in rehearsed thoughts.',
						'“We… take every thought captive to obey Christ.”',
						'Which thought loop needs captivity today?',
						'<strong>Practice:</strong> When the loop starts, interrupt with a verse. <em>Christ, capture this thought. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Walk by the Spirit',
					'verse_ref' => 'Galatians 5:16',
					'body'      => hwbl_plan_day_body(
						'Desire is not destiny when the Spirit leads.',
						'“But I say, walk by the Spirit, and you will not gratify the desires of the flesh.”',
						'What would Spirit-led next step look like in the next hour?',
						'<strong>Practice:</strong> Ask the Spirit before you act on craving. <em>Holy Spirit, lead my steps. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Confess to one another',
					'verse_ref' => 'James 5:16',
					'body'      => hwbl_plan_day_body(
						'Isolation feeds addiction; honest prayer heals.',
						'“Confess your sins to one another and pray for one another, that you may be healed.”',
						'Who is safe enough for honesty?',
						'<strong>Practice:</strong> Send one honest text asking for prayer. <em>God, heal through honest community. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Forget what lies behind',
					'verse_ref' => 'Philippians 3:13-14',
					'body'      => hwbl_plan_day_body(
						'Shame about yesterday can sabotage today. Press on.',
						'“Forgetting what lies behind and straining forward to what lies ahead, I press on toward the goal.”',
						'What past failure are you letting define tomorrow?',
						'<strong>Practice:</strong> Thank God for one forward step, however small. <em>Lord, I press on. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He who began a good work',
					'verse_ref' => 'Philippians 1:6',
					'body'      => hwbl_plan_day_body(
						'God finishes what He starts—including your freedom path.',
						'“He who began a good work in you will bring it to completion at the day of Jesus Christ.”',
						'Where do you need to trust His finishing work?',
						'<strong>Practice:</strong> Journal one evidence of God’s good work already begun. <em>Faithful God, complete what You began. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'God Who Sees You (7 days)',
			'topic'     => 'domestic-violence',
			'shareable' => false,
			'excerpt'   => 'A second pastoral plan for those harmed at home—God sees, shelters, and never requires staying in danger. Seek safety; this is not counseling.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'You are the God who sees',
					'verse_ref' => 'Genesis 16:13',
					'body'      => hwbl_plan_day_body(
						'This reading plan is pastoral Scripture study, not crisis counseling, legal advice, or medical care. If you are in immediate danger, call your local emergency number. In the US and Canada, call or text 988 (Suicide &amp; Crisis Lifeline). Reach a trusted pastor, counselor, or doctor alongside these daily readings. If you are being harmed, seek safety first. God sees you—this plan never asks you to stay in danger.',
						'“So she called the name of the Lord who spoke to her, ‘You are a God of seeing.’” Hagar names God as the One who sees the unseen.',
						'Where do you need to be seen without explaining everything?',
						'<strong>Practice:</strong> Pray, “You see me,” and tell one safe person if you can. <em>God who sees, see me today. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Refuge and strength',
					'verse_ref' => 'Psalm 46:1',
					'body'      => hwbl_plan_day_body(
						'God is refuge—not a demand to endure abuse.',
						'“God is our refuge and strength, a very present help in trouble.”',
						'What would refuge look like practically today?',
						'<strong>Practice:</strong> Identify one safe place or person. <em>Lord, be my refuge. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'The Lord is near',
					'verse_ref' => 'Psalm 34:18',
					'body'      => hwbl_plan_day_body(
						'Broken hearts are not overlooked.',
						'“The Lord is near to the brokenhearted and saves the crushed in spirit.”',
						'Where is your spirit crushed—and have you told God?',
						'<strong>Practice:</strong> Write one honest sentence of pain to God. <em>Near God, hold my crushed heart. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Justice and righteousness',
					'verse_ref' => 'Psalm 89:14',
					'body'      => hwbl_plan_day_body(
						'God’s throne is founded on justice—abuse is not His will.',
						'“Righteousness and justice are the foundation of your throne.”',
						'What injustice needs naming without minimizing?',
						'<strong>Practice:</strong> Name the harm truthfully before God. <em>Just God, You hate what harms me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Cast your burden',
					'verse_ref' => 'Psalm 55:22',
					'body'      => hwbl_plan_day_body(
						'You were not meant to carry this alone.',
						'“Cast your burden on the Lord, and he will sustain you.”',
						'What burden can you cast—and who can help carry?',
						'<strong>Practice:</strong> Ask for one practical help today. <em>Lord, sustain me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Peace I leave with you',
					'verse_ref' => 'John 14:27',
					'body'      => hwbl_plan_day_body(
						'Jesus gives peace the world cannot manufacture.',
						'“Peace I leave with you; my peace I give to you. Not as the world gives do I give to you.”',
						'Where do you need His peace more than control?',
						'<strong>Practice:</strong> Breathe slowly and pray John 14:27. <em>Jesus, give me Your peace. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He will wipe every tear',
					'verse_ref' => 'Revelation 21:4',
					'body'      => hwbl_plan_day_body(
						'The story ends with God wiping tears—not ignoring them.',
						'“He will wipe away every tear from their eyes, and death shall be no more… for the former things have passed away.”',
						'What tear do you need hope for beyond today?',
						'<strong>Practice:</strong> Thank God for a future without terror. <em>Lord, wipe my tears and hold my hope. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Hope After Breaking (7 days)',
			'topic'     => 'divorce',
			'shareable' => false,
			'excerpt'   => 'A second divorce plan—grief, identity, forgiveness, and hope after breaking. Not legal advice; never a mandate to stay in danger.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'A future and a hope',
					'verse_ref' => 'Jeremiah 29:11',
					'body'      => hwbl_plan_day_body(
						'This reading plan is pastoral Scripture study, not crisis counseling, legal advice, or medical care. If you are in immediate danger, call your local emergency number. In the US and Canada, call or text 988 (Suicide &amp; Crisis Lifeline). Reach a trusted pastor, counselor, or doctor alongside these daily readings. If you are in danger at home, seek safety first. Divorce can feel like an ending; God still speaks future and hope.',
						'“For I know the plans I have for you, declares the Lord… to give you a future and a hope.”',
						'Where do you struggle to believe any future remains?',
						'<strong>Practice:</strong> Write one hope you dare ask God for. <em>Lord, give me a future with You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'New every morning',
					'verse_ref' => 'Lamentations 3:22-23',
					'body'      => hwbl_plan_day_body(
						'Mercies arrive in daily portions when life feels ruined.',
						'“The steadfast love of the Lord never ceases… they are new every morning.”',
						'What mercy do you need for this morning only?',
						'<strong>Practice:</strong> Thank God for one new mercy today. <em>Faithful God, Your mercies are new. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Forget what lies behind',
					'verse_ref' => 'Philippians 3:13',
					'body'      => hwbl_plan_day_body(
						'Pressing on does not deny grief; it refuses captivity to the past.',
						'“Forgetting what lies behind and straining forward to what lies ahead…”',
						'What past loop keeps stealing today’s obedience?',
						'<strong>Practice:</strong> Name one forward step and take it. <em>Lord, I strain forward with You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Forgive as the Lord forgave',
					'verse_ref' => 'Colossians 3:13',
					'body'      => hwbl_plan_day_body(
						'Forgiveness is not pretending harm was small; it is releasing vengeance to God.',
						'“Bearing with one another and, if one has a complaint against another, forgiving each other; as the Lord has forgiven you.”',
						'What complaint still owns your inner life?',
						'<strong>Practice:</strong> Pray honest lament, then release one grievance to God. <em>Lord, help me forgive as You forgave. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'You are not alone',
					'verse_ref' => 'Hebrews 13:5',
					'body'      => hwbl_plan_day_body(
						'Loneliness after divorce is real; God’s presence is truer.',
						'“I will never leave you nor forsake you.”',
						'Where do you feel forsaken—and can you invite God there?',
						'<strong>Practice:</strong> Pray Hebrews 13:5 with your name in it. <em>God, You will not forsake me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Strength for the weak',
					'verse_ref' => 'Isaiah 40:29',
					'body'      => hwbl_plan_day_body(
						'Weak seasons still receive divine strength.',
						'“He gives power to the faint, and to him who has no might he increases strength.”',
						'Where are you faint today?',
						'<strong>Practice:</strong> Ask for strength for one task only. <em>Lord, power for the faint—help me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'All things new',
					'verse_ref' => 'Revelation 21:5',
					'body'      => hwbl_plan_day_body(
						'God’s end is not erasure of pain but making all things new.',
						'“And he who was seated on the throne said, ‘Behold, I am making all things new.’”',
						'What newness do you need courage to believe?',
						'<strong>Practice:</strong> Thank God for one small new beginning. <em>Lord, make things new in me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Comfort in Affliction (7 days)',
			'topic'     => 'suffering',
			'shareable' => false,
			'excerpt'   => 'A second suffering plan—receiving God’s comfort, lamenting honestly, and finding purpose without pretending pain is easy.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God of all comfort',
					'verse_ref' => '2 Corinthians 1:3-4',
					'body'      => hwbl_plan_day_body(
						'This reading plan is pastoral Scripture study, not crisis counseling, legal advice, or medical care. If you are in immediate danger, call your local emergency number. In the US and Canada, call or text 988 (Suicide &amp; Crisis Lifeline). Reach a trusted pastor, counselor, or doctor alongside these daily readings. Suffering is not proof God abandoned you. He comforts so we can comfort.',
						'“The Father of mercies and God of all comfort, who comforts us in all our affliction…”',
						'Where do you need comfort more than explanation?',
						'<strong>Practice:</strong> Receive comfort in prayer without forcing answers. <em>Father of mercies, comfort me. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Weeping may tarry',
					'verse_ref' => 'Psalm 30:5',
					'body'      => hwbl_plan_day_body(
						'Night is real; morning is promised.',
						'“Weeping may tarry for the night, but joy comes with the morning.”',
						'What night are you in—and can you wait for morning?',
						'<strong>Practice:</strong> Name the night without rushing past it. <em>Lord, meet me in the night. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Share Christ’s sufferings',
					'verse_ref' => '1 Peter 4:13',
					'body'      => hwbl_plan_day_body(
						'Suffering with Christ is not meaningless pain theater.',
						'“But rejoice insofar as you share Christ’s sufferings, that you may also rejoice and be glad when his glory is revealed.”',
						'How might this pain connect you to Jesus?',
						'<strong>Practice:</strong> Pray with Christ as companion, not only as fixer. <em>Jesus, suffer with me and hold me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'My grace is sufficient',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'Grace meets weakness without shame.',
						'“My grace is sufficient for you, for my power is made perfect in weakness.”',
						'Where is weakness asking for grace today?',
						'<strong>Practice:</strong> Stop apologizing for weakness; ask for grace. <em>Lord, sufficient grace here. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Do not lose heart',
					'verse_ref' => '2 Corinthians 4:16-18',
					'body'      => hwbl_plan_day_body(
						'What is unseen can outweigh what is seen.',
						'“So we do not lose heart… For this light momentary affliction is preparing for us an eternal weight of glory…”',
						'What seen thing is stealing unseen hope?',
						'<strong>Practice:</strong> Look to eternity for one minute in prayer. <em>Lord, I do not lose heart. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'The Lord is my shepherd',
					'verse_ref' => 'Psalm 23:4',
					'body'      => hwbl_plan_day_body(
						'Even the valley is walked with a Shepherd.',
						'“Even though I walk through the valley of the shadow of death, I will fear no evil, for you are with me.”',
						'What valley are you walking—and is He with you there?',
						'<strong>Practice:</strong> Pray Psalm 23 slowly. <em>Shepherd, walk this valley with me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'After you have suffered',
					'verse_ref' => '1 Peter 5:10',
					'body'      => hwbl_plan_day_body(
						'God Himself restores, confirms, strengthens, and establishes.',
						'“And after you have suffered a little while, the God of all grace… will himself restore, confirm, strengthen, and establish you.”',
						'Which of those four verbs do you need most?',
						'<strong>Practice:</strong> Ask God for that verb by name. <em>God of all grace, restore me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Steadfast Under Pressure (7 days)',
			'topic'     => 'persecution',
			'shareable' => false,
			'excerpt'   => 'A second plan for pressure and persecution—courage, blessing under insult, and steadfast hope in Christ.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Blessed when others revile',
					'verse_ref' => 'Matthew 5:11-12',
					'body'      => hwbl_plan_day_body(
						'Insult for Christ’s sake is not the end of the story.',
						'“Blessed are you when others revile you and persecute you… Rejoice and be glad, for your reward is great in heaven.”',
						'Where have you been reviled for faithfulness?',
						'<strong>Practice:</strong> Pray blessing over one person who opposed you. <em>Lord, keep me glad in You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Do not fear those who kill',
					'verse_ref' => 'Matthew 10:28',
					'body'      => hwbl_plan_day_body(
						'Fear is redirected toward God who holds the soul.',
						'“And do not fear those who kill the body but cannot kill the soul…”',
						'What fear of people is louder than fear of God?',
						'<strong>Practice:</strong> Name the fear and hand it to God. <em>Father, I fear You more than them. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'All who desire to live godly',
					'verse_ref' => '2 Timothy 3:12',
					'body'      => hwbl_plan_day_body(
						'Pressure is not always a sign you failed.',
						'“Indeed, all who desire to live a godly life in Christ Jesus will be persecuted.”',
						'How does this verse reframe your surprise at opposition?',
						'<strong>Practice:</strong> Thank God for one chance to remain faithful. <em>Jesus, help me live godly under pressure. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Love your enemies',
					'verse_ref' => 'Matthew 5:44',
					'body'      => hwbl_plan_day_body(
						'Enemy-love is supernatural courage, not denial.',
						'“But I say to you, Love your enemies and pray for those who persecute you.”',
						'Who is hard to pray for—and will you try?',
						'<strong>Practice:</strong> Pray one honest blessing for an opponent. <em>Lord, teach me to love. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Count it all joy',
					'verse_ref' => 'James 1:2-3',
					'body'      => hwbl_plan_day_body(
						'Trials test faith toward steadfastness.',
						'“Count it all joy, my brothers, when you meet trials of various kinds, for you know that the testing of your faith produces steadfastness.”',
						'Where is testing producing steadfastness in you?',
						'<strong>Practice:</strong> Journal one steadfast fruit already growing. <em>Lord, produce steadfastness in me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Be faithful unto death',
					'verse_ref' => 'Revelation 2:10',
					'body'      => hwbl_plan_day_body(
						'Faithfulness is measured in days of pressure, not ease.',
						'“Be faithful unto death, and I will give you the crown of life.”',
						'What does faithfulness look like in your next hard conversation?',
						'<strong>Practice:</strong> Choose one faithful act today. <em>Jesus, keep me faithful. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The Lord is my helper',
					'verse_ref' => 'Hebrews 13:6',
					'body'      => hwbl_plan_day_body(
						'Confidence comes from the Helper, not from control.',
						'“So we can confidently say, ‘The Lord is my helper; I will not fear; what can man do to me?’”',
						'Where do you need that confidence today?',
						'<strong>Practice:</strong> Say Hebrews 13:6 aloud. <em>Lord, You are my helper. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Undivided Devotion (7 days)',
			'topic'     => 'singleness',
			'shareable' => false,
			'excerpt'   => 'A second singleness plan—undivided devotion to the Lord, belonging in the body, and hope without hurry.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Undivided devotion',
					'verse_ref' => '1 Corinthians 7:35',
					'body'      => hwbl_plan_day_body(
						'Singleness can be a gift of undivided attention to the Lord.',
						'“…to promote good order and to secure your undivided devotion to the Lord.”',
						'What would undivided devotion look like this week?',
						'<strong>Practice:</strong> Give God one undistracted hour. <em>Lord, my devotion is Yours. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Not good to be alone—community',
					'verse_ref' => 'Genesis 2:18',
					'body'      => hwbl_plan_day_body(
						'“Alone” is answered first by God and His people, not only by marriage.',
						'“Then the Lord God said, ‘It is not good that the man should be alone…’”',
						'Where do you need embodied community, not just coping?',
						'<strong>Practice:</strong> Reach out to one believer for shared life. <em>God, meet my loneliness with Your people. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Content in every situation',
					'verse_ref' => 'Philippians 4:11-13',
					'body'      => hwbl_plan_day_body(
						'Contentment is learned in Christ, not in status.',
						'“I have learned in whatever situation I am to be content… I can do all things through him who strengthens me.”',
						'Where is discontent rewriting your story?',
						'<strong>Practice:</strong> Thank God for three present gifts. <em>Christ, strengthen contentment in me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Delight yourself in the Lord',
					'verse_ref' => 'Psalm 37:4',
					'body'      => hwbl_plan_day_body(
						'Desire finds its right home in delighting in God.',
						'“Delight yourself in the Lord, and he will give you the desires of your heart.”',
						'What desire needs to be brought into delight before God?',
						'<strong>Practice:</strong> Tell God your desire without demanding. <em>Lord, I delight in You first. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Family of God',
					'verse_ref' => 'Mark 3:35',
					'body'      => hwbl_plan_day_body(
						'Jesus forms family beyond bloodlines and romance.',
						'“For whoever does the will of God, he is my brother and sister and mother.”',
						'Who is family in Christ for you right now?',
						'<strong>Practice:</strong> Encourage one spiritual sibling today. <em>Jesus, thank You for Your family. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Wait for the Lord',
					'verse_ref' => 'Psalm 27:14',
					'body'      => hwbl_plan_day_body(
						'Waiting is active trust, not wasted life.',
						'“Wait for the Lord; be strong, and let your heart take courage; wait for the Lord!”',
						'Where do you need courage to keep waiting well?',
						'<strong>Practice:</strong> Pray for strength to wait without bitterness. <em>Lord, I wait with courage. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Your life is hidden',
					'verse_ref' => 'Colossians 3:3',
					'body'      => hwbl_plan_day_body(
						'Your worth is hidden with Christ—not displayed by a ring.',
						'“For you have died, and your life is hidden with Christ in God.”',
						'What false measure of worth can you release?',
						'<strong>Practice:</strong> Rest identity in Christ alone today. <em>Christ, my life is hidden with You. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Sustained for the Long Haul (7 days)',
			'topic'     => 'caregiving',
			'shareable' => false,
			'excerpt'   => 'A second caregiving plan—daily strength, honest lament, and sustained love for the long haul.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Those who wait renew strength',
					'verse_ref' => 'Isaiah 40:31',
					'body'      => hwbl_plan_day_body(
						'Caregiving is a marathon; waiting on God renews strength.',
						'“But they who wait for the Lord shall renew their strength…”',
						'Where are you running on empty?',
						'<strong>Practice:</strong> Pause ten minutes to wait on God before the next task. <em>Lord, renew my strength. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Bear one another’s burdens',
					'verse_ref' => 'Galatians 6:2',
					'body'      => hwbl_plan_day_body(
						'You need burden-bearers too—not only to be one.',
						'“Bear one another’s burdens, and so fulfill the law of Christ.”',
						'Who can share your load this week?',
						'<strong>Practice:</strong> Ask for one specific help. <em>Christ, teach me to receive help. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Compassion of Christ',
					'verse_ref' => 'Matthew 9:36',
					'body'      => hwbl_plan_day_body(
						'Jesus sees crowds with compassion; He sees you too.',
						'“When he saw the crowds, he had compassion for them, because they were harassed and helpless…”',
						'Where do you need His compassion for your own fatigue?',
						'<strong>Practice:</strong> Receive compassion before giving it. <em>Jesus, have compassion on me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Cast your burden',
					'verse_ref' => 'Psalm 55:22',
					'body'      => hwbl_plan_day_body(
						'Sustainment comes from casting, not clutching.',
						'“Cast your burden on the Lord, and he will sustain you.”',
						'What burden can you cast today?',
						'<strong>Practice:</strong> Name the burden and release it in prayer. <em>Lord, sustain me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Gentle and lowly',
					'verse_ref' => 'Matthew 11:29',
					'body'      => hwbl_plan_day_body(
						'Learn from Jesus’ heart when caregiving hardens yours.',
						'“Take my yoke upon you, and learn from me, for I am gentle and lowly in heart…”',
						'Where has caregiving made you harsh with yourself?',
						'<strong>Practice:</strong> Speak one gentle word to yourself as Jesus would. <em>Gentle Jesus, teach me Your heart. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not grow weary',
					'verse_ref' => 'Galatians 6:9',
					'body'      => hwbl_plan_day_body(
						'Long-haul love needs a promise of harvest.',
						'“And let us not grow weary of doing good, for in due season we will reap, if we do not give up.”',
						'Where are you weary of doing good?',
						'<strong>Practice:</strong> Do one small good and trust the season. <em>Lord, keep me from giving up. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Well done',
					'verse_ref' => 'Matthew 25:21',
					'body'      => hwbl_plan_day_body(
						'Hidden faithfulness is seen by the Master.',
						'“Well done, good and faithful servant… Enter into the joy of your master.”',
						'What unseen act of care can you offer as worship?',
						'<strong>Practice:</strong> Offer today’s care as service to Christ. <em>Master, I serve You in them. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Iron Sharpens Iron (7 days)',
			'topic'     => 'friendship',
			'shareable' => false,
			'excerpt'   => 'A second friendship plan—loyalty, honest sharpening, shared burdens, and friends who point each other to Christ.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Iron sharpens iron',
					'verse_ref' => 'Proverbs 27:17',
					'body'      => hwbl_plan_day_body(
						'True friendship sharpens; flattery dulls.',
						'“Iron sharpens iron, and one man sharpens another.”',
						'Who sharpens you—and whom do you sharpen?',
						'<strong>Practice:</strong> Offer one truthful encouragement today. <em>Lord, make me a sharpening friend. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'A friend loves at all times',
					'verse_ref' => 'Proverbs 17:17',
					'body'      => hwbl_plan_day_body(
						'Loyalty shows in hard seasons, not only easy ones.',
						'“A friend loves at all times, and a brother is born for adversity.”',
						'Who needs your presence in adversity?',
						'<strong>Practice:</strong> Check in on one friend in a hard place. <em>Lord, teach me loyal love. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Speak truth in love',
					'verse_ref' => 'Ephesians 4:15',
					'body'      => hwbl_plan_day_body(
						'Friendship without truth is not love; truth without love wounds.',
						'“Speaking the truth in love, we are to grow up in every way into him who is the head, into Christ.”',
						'What truth needs love’s tone?',
						'<strong>Practice:</strong> Pray before a hard conversation. <em>Christ, help me speak truth in love. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Confess and pray',
					'verse_ref' => 'James 5:16',
					'body'      => hwbl_plan_day_body(
						'Friends who pray become healers together.',
						'“Confess your sins to one another and pray for one another, that you may be healed.”',
						'What can you safely confess to a friend?',
						'<strong>Practice:</strong> Share one need and pray together. <em>God, heal us as we pray. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Two are better than one',
					'verse_ref' => 'Ecclesiastes 4:9-10',
					'body'      => hwbl_plan_day_body(
						'Friendship catches us when we fall.',
						'“Two are better than one… For if they fall, one will lift up his fellow.”',
						'Where do you need a lifter—or to be one?',
						'<strong>Practice:</strong> Lift a friend with a call or meal. <em>Lord, make me a lifter. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Greater love',
					'verse_ref' => 'John 15:13',
					'body'      => hwbl_plan_day_body(
						'Jesus defines friendship by sacrificial love.',
						'“Greater love has no one than this, that someone lay down his life for his friends.”',
						'What small laying-down can friendship ask of you today?',
						'<strong>Practice:</strong> Sacrifice one preference for a friend’s good. <em>Jesus, teach me greater love. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'I have called you friends',
					'verse_ref' => 'John 15:15',
					'body'      => hwbl_plan_day_body(
						'The best friendship begins with being Jesus’ friend.',
						'“No longer do I call you servants… but I have called you friends.”',
						'How does Jesus’ friendship reshape your other friendships?',
						'<strong>Practice:</strong> Sit with Jesus as Friend in prayer. <em>Jesus, thank You for calling me friend. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Slow to Anger (7 days)',
			'topic'     => 'anger',
			'shareable' => false,
			'excerpt'   => 'A second anger plan—slow speech, righteous zeal without sin, and peacemaking that begins in the heart.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Slow to anger',
					'verse_ref' => 'James 1:19-20',
					'body'      => hwbl_plan_day_body(
						'Speed is anger’s favorite fuel; slowness is wisdom.',
						'“Let every person be quick to hear, slow to speak, slow to anger; for the anger of man does not produce the righteousness of God.”',
						'Where do you need to slow down before speaking?',
						'<strong>Practice:</strong> Count to ten and pray before reacting once today. <em>Lord, make me slow to anger. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Be angry and do not sin',
					'verse_ref' => 'Ephesians 4:26-27',
					'body'      => hwbl_plan_day_body(
						'Anger can be honest without becoming sinful or overnight bitterness.',
						'“Be angry and do not sin; do not let the sun go down on your anger, and give no opportunity to the devil.”',
						'What anger needs daylight honesty without feeding the devil?',
						'<strong>Practice:</strong> Address one anger before sleep. <em>Lord, keep my anger from sin. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'A soft answer',
					'verse_ref' => 'Proverbs 15:1',
					'body'      => hwbl_plan_day_body(
						'Tone can turn away wrath or pour fuel.',
						'“A soft answer turns away wrath, but a harsh word stirs up anger.”',
						'Where can a soft answer change the room?',
						'<strong>Practice:</strong> Choose a softer first sentence in one hard talk. <em>Lord, give me a soft answer. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Put away bitterness',
					'verse_ref' => 'Ephesians 4:31-32',
					'body'      => hwbl_plan_day_body(
						'Anger often hardens into bitterness; kindness is the alternative path.',
						'“Let all bitterness and wrath and anger… be put away from you… Be kind to one another…”',
						'What bitterness are you rehearsing?',
						'<strong>Practice:</strong> Pray kindness toward one person you resent. <em>God, put away bitterness in me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Revenge belongs to God',
					'verse_ref' => 'Romans 12:19',
					'body'      => hwbl_plan_day_body(
						'Vengeance fantasies keep anger hot; God claims justice.',
						'“Beloved, never avenge yourselves, but leave it to the wrath of God…”',
						'What revenge story can you hand to God?',
						'<strong>Practice:</strong> Write the grievance and give it to God. <em>Lord, vengeance is Yours. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Peaceable and gentle',
					'verse_ref' => 'Titus 3:2',
					'body'      => hwbl_plan_day_body(
						'Gentleness is strength under control.',
						'“…to speak evil of no one, to avoid quarreling, to be gentle, and to show perfect courtesy toward all people.”',
						'Where do you need courtesy instead of combat?',
						'<strong>Practice:</strong> Show courtesy in one tense moment. <em>Spirit, make me gentle. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Fruit of the Spirit',
					'verse_ref' => 'Galatians 5:22-23',
					'body'      => hwbl_plan_day_body(
						'Peace and patience grow where the Spirit leads.',
						'“But the fruit of the Spirit is love, joy, peace, patience…”',
						'Which fruit do you need most against anger?',
						'<strong>Practice:</strong> Ask the Spirit for that fruit by name. <em>Holy Spirit, grow Your fruit in me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Perfect Love Casts Out Fear (7 days)',
			'topic'     => 'fear',
			'shareable' => false,
			'excerpt'   => 'A second fear plan—trust when afraid, perfect love that casts out fear, and courage rooted in God’s presence.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'When I am afraid',
					'verse_ref' => 'Psalm 56:3',
					'body'      => hwbl_plan_day_body(
						'Fear is a moment; trust can be the next move.',
						'“When I am afraid, I put my trust in you.”',
						'What fear can become a doorway to trust today?',
						'<strong>Practice:</strong> Say Psalm 56:3 when fear rises. <em>God, when I am afraid, I trust You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Perfect love casts out fear',
					'verse_ref' => '1 John 4:18',
					'body'      => hwbl_plan_day_body(
						'Fear often thrives on punishment-stories; love tells a better one.',
						'“There is no fear in love, but perfect love casts out fear.”',
						'What punishing picture of God feeds your fear?',
						'<strong>Practice:</strong> Meditate on the cross as love. <em>Father, perfect Your love in me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Do not fear, for I am with you',
					'verse_ref' => 'Isaiah 41:10',
					'body'      => hwbl_plan_day_body(
						'Presence is God’s answer to fear.',
						'“Fear not, for I am with you; be not dismayed, for I am your God…”',
						'Where do you need His with-ness more than outcomes?',
						'<strong>Practice:</strong> Place a hand on your heart and pray Isaiah 41:10. <em>God, You are with me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Peace, be still',
					'verse_ref' => 'Mark 4:39',
					'body'      => hwbl_plan_day_body(
						'Jesus still speaks to storms.',
						'“Peace! Be still!” And the wind ceased, and there was a great calm.',
						'What storm are you staring at more than Jesus?',
						'<strong>Practice:</strong> Name the storm and ask Jesus for calm. <em>Jesus, speak peace. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'God gave us a spirit',
					'verse_ref' => '2 Timothy 1:7',
					'body'      => hwbl_plan_day_body(
						'Fear is not the Spirit’s gift.',
						'“For God gave us a spirit not of fear but of power and love and self-control.”',
						'Which gift—power, love, or self-control—do you need?',
						'<strong>Practice:</strong> Ask for that gift specifically. <em>Spirit, replace fear with Your gift. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Be strong and courageous',
					'verse_ref' => 'Joshua 1:9',
					'body'      => hwbl_plan_day_body(
						'Courage is commanded because presence is promised.',
						'“Be strong and courageous. Do not be frightened… for the Lord your God is with you wherever you go.”',
						'Where is courage required today?',
						'<strong>Practice:</strong> Take one courageous step with God. <em>Lord, I will be strong with You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The Lord is my light',
					'verse_ref' => 'Psalm 27:1',
					'body'      => hwbl_plan_day_body(
						'Fear shrinks when the Lord is light and salvation.',
						'“The Lord is my light and my salvation; whom shall I fear?”',
						'What fear looks smaller in His light?',
						'<strong>Practice:</strong> Pray Psalm 27:1 at day’s end. <em>Lord, You are my light. Amen.</em>'
					),
				),
			),
		),
	);
}
