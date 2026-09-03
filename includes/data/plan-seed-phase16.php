<?php
/**
 * Phase 16: second plans for character and calling topics.
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
 * Phase 16 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase16_definitions() {
	return array(
		array(
			'title'     => 'Anchored Hope (7 days)',
			'topic'     => 'hope',
			'shareable' => false,
			'excerpt'   => 'A second hope plan—anchored in God’s promises when feelings run low.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Hope as an anchor',
					'verse_ref' => 'Hebrews 6:19',
					'body'      => hwbl_plan_day_body(
						'Hope is not wishful thinking; it is an anchor for the soul.',
						'“We have this as a sure and steadfast anchor of the soul, a hope that enters into the inner place behind the curtain.”',
						'Where do you need an anchor more than a mood boost?',
						'<strong>Practice:</strong> Name one promise and cling to it today. <em>Lord, anchor my hope in You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Living hope',
					'verse_ref' => '1 Peter 1:3',
					'body'      => hwbl_plan_day_body(
						'New birth gives living hope through the resurrection.',
						'“According to his great mercy, he has caused us to be born again to a living hope through the resurrection of Jesus Christ from the dead.”',
						'How does resurrection make your hope living?',
						'<strong>Practice:</strong> Thank Jesus for empty-tomb hope. <em>Risen Lord, keep my hope alive. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Hope against hope',
					'verse_ref' => 'Romans 4:18',
					'body'      => hwbl_plan_day_body(
						'Abraham hoped against hope—faith looking past empty circumstances.',
						'“In hope he believed against hope, that he should become the father of many nations…”',
						'What circumstance feels hope-empty?',
						'<strong>Practice:</strong> Pray hope against that hope-empty place. <em>God, I hope against hope in You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Rejoice in hope',
					'verse_ref' => 'Romans 12:12',
					'body'      => hwbl_plan_day_body(
						'Hope fuels patience and prayer in pressure.',
						'“Rejoice in hope, be patient in tribulation, be constant in prayer.”',
						'Which of those three habits is weakest for you?',
						'<strong>Practice:</strong> Practice that habit for ten minutes. <em>Lord, teach me hopeful patience. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Those who hope renew strength',
					'verse_ref' => 'Isaiah 40:31',
					'body'      => hwbl_plan_day_body(
						'Waiting hope renews strength for the long road.',
						'“But they who wait for the Lord shall renew their strength; they shall mount up with wings like eagles…”',
						'Where are you waiting—and needing renewed strength?',
						'<strong>Practice:</strong> Wait quietly on God for five minutes. <em>Lord, renew my strength as I wait. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Christ in you, the hope',
					'verse_ref' => 'Colossians 1:27',
					'body'      => hwbl_plan_day_body(
						'Hope’s treasure is a Person within.',
						'“…Christ in you, the hope of glory.”',
						'How does “Christ in you” change today’s discouragement?',
						'<strong>Practice:</strong> Pray, “Christ in me, my hope.” <em>Jesus, be my hope of glory. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'May the God of hope',
					'verse_ref' => 'Romans 15:13',
					'body'      => hwbl_plan_day_body(
						'God Himself fills believers with hope by the Spirit.',
						'“May the God of hope fill you with all joy and peace in believing, so that by the power of the Holy Spirit you may abound in hope.”',
						'Where do you need to abound, not just scrape by?',
						'<strong>Practice:</strong> Ask the Spirit to fill you with hope. <em>God of hope, make me abound. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Give Thanks Always (7 days)',
			'topic'     => 'gratitude',
			'shareable' => false,
			'excerpt'   => 'A second gratitude plan—thanksgiving in all circumstances as worship, not denial.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Give thanks in all circumstances',
					'verse_ref' => '1 Thessalonians 5:18',
					'body'      => hwbl_plan_day_body(
						'Thanksgiving is God’s will even when life is hard—not pretending pain is pleasant.',
						'“Give thanks in all circumstances; for this is the will of God in Christ Jesus for you.”',
						'What hard circumstance can still hold a thanks?',
						'<strong>Practice:</strong> Write three thanks, including one costly one. <em>Lord, I give thanks in this. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Enter with thanksgiving',
					'verse_ref' => 'Psalm 100:4',
					'body'      => hwbl_plan_day_body(
						'Gratitude is how we approach God.',
						'“Enter his gates with thanksgiving, and his courts with praise!”',
						'How will you enter God’s presence today?',
						'<strong>Practice:</strong> Begin prayer with one minute of thanks only. <em>God, I enter with thanksgiving. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Every good gift',
					'verse_ref' => 'James 1:17',
					'body'      => hwbl_plan_day_body(
						'Gifts remind us of the Giver.',
						'“Every good gift and every perfect gift is from above, coming down from the Father of lights…”',
						'Which good gift have you treated as entitlement?',
						'<strong>Practice:</strong> Thank the Father for one overlooked gift. <em>Father of lights, thank You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Overflow with thanksgiving',
					'verse_ref' => 'Colossians 2:7',
					'body'      => hwbl_plan_day_body(
						'Rooted lives overflow with thanks.',
						'“…rooted and built up in him and established in the faith… abounding in thanksgiving.”',
						'Is your thanksgiving abounding or rationed?',
						'<strong>Practice:</strong> Tell someone one thanks about God today. <em>Jesus, make me abound in thanks. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Sing to the Lord',
					'verse_ref' => 'Psalm 95:1-2',
					'body'      => hwbl_plan_day_body(
						'Gratitude loves to sing.',
						'“Oh come, let us sing to the Lord… Let us come into his presence with thanksgiving!”',
						'What song can become your thanks today?',
						'<strong>Practice:</strong> Sing or hum one praise song to God. <em>Lord, I sing my thanks. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not forget his benefits',
					'verse_ref' => 'Psalm 103:2',
					'body'      => hwbl_plan_day_body(
						'Forgetfulness starves gratitude; remembering feeds it.',
						'“Bless the Lord, O my soul, and forget not all his benefits.”',
						'Which benefit have you forgotten lately?',
						'<strong>Practice:</strong> List five benefits and bless the Lord. <em>Soul, bless the Lord. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Whatever you do',
					'verse_ref' => 'Colossians 3:17',
					'body'      => hwbl_plan_day_body(
						'Ordinary work can become thanksgiving.',
						'“And whatever you do, in word or deed, do everything in the name of the Lord Jesus, giving thanks to God the Father through him.”',
						'How can today’s work become thanks?',
						'<strong>Practice:</strong> Do one task as explicit thanksgiving. <em>Father, I do this in Jesus’ name with thanks. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Strength While You Wait (7 days)',
			'topic'     => 'waiting',
			'shareable' => false,
			'excerpt'   => 'A second waiting plan—courage, renewed strength, and trust while God’s timing unfolds.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Wait for the Lord',
					'verse_ref' => 'Psalm 27:14',
					'body'      => hwbl_plan_day_body(
						'Waiting takes courage, not passivity alone.',
						'“Wait for the Lord; be strong, and let your heart take courage; wait for the Lord!”',
						'Where do you need courage to keep waiting?',
						'<strong>Practice:</strong> Pray for courage instead of shortcuts. <em>Lord, I wait with courage. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'They who wait renew strength',
					'verse_ref' => 'Isaiah 40:31',
					'body'      => hwbl_plan_day_body(
						'God renews strength for those who wait on Him.',
						'“But they who wait for the Lord shall renew their strength…”',
						'What depleted place needs renewed strength?',
						'<strong>Practice:</strong> Wait quietly five minutes before rushing. <em>Lord, renew my strength. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'My times are in Your hand',
					'verse_ref' => 'Psalm 31:15',
					'body'      => hwbl_plan_day_body(
						'Timing belongs to God, not anxiety.',
						'“My times are in your hand…”',
						'What timeline are you gripping too tightly?',
						'<strong>Practice:</strong> Open your hands and release the timeline. <em>God, my times are Yours. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Though it linger, wait',
					'verse_ref' => 'Habakkuk 2:3',
					'body'      => hwbl_plan_day_body(
						'Vision may linger; waiting remains faithful.',
						'“For still the vision awaits its appointed time… If it seems slow, wait for it; it will surely come; it will not delay.”',
						'What promise feels slow?',
						'<strong>Practice:</strong> Journal the promise and date your trust. <em>Lord, I wait for Your timing. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Be still before the Lord',
					'verse_ref' => 'Psalm 37:7',
					'body'      => hwbl_plan_day_body(
						'Stillness fights fretting over the wicked and the wait.',
						'“Be still before the Lord and wait patiently for him; fret not yourself…”',
						'What fretting can stillness replace?',
						'<strong>Practice:</strong> Sit still and refuse to fret for three minutes. <em>Lord, I am still before You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'I wait for the Lord',
					'verse_ref' => 'Psalm 130:5-6',
					'body'      => hwbl_plan_day_body(
						'Soul-waiting watches for God more than watchmen for morning.',
						'“I wait for the Lord, my soul waits, and in his word I hope…”',
						'Is your hope in His word or in outcomes?',
						'<strong>Practice:</strong> Read one promise slowly twice. <em>Lord, my soul waits for You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'At the right time',
					'verse_ref' => 'Galatians 4:4',
					'body'      => hwbl_plan_day_body(
						'God’s right time sent Christ—and still rules your story.',
						'“But when the fullness of time had come, God sent forth his Son…”',
						'How does God’s fullness-of-time encourage your wait?',
						'<strong>Practice:</strong> Thank God that He is never late. <em>Father, I trust Your fullness of time. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Sabbath Heart (7 days)',
			'topic'     => 'rest',
			'shareable' => false,
			'excerpt'   => 'A second rest plan—receiving Christ’s rest, practicing stillness, and resisting hurry in the soul.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'I will give you rest',
					'verse_ref' => 'Matthew 11:28',
					'body'      => hwbl_plan_day_body(
						'Rest is Jesus’ gift to the heavy-laden.',
						'“Come to me, all who labor and are heavy laden, and I will give you rest.”',
						'What load have you not brought to Him?',
						'<strong>Practice:</strong> Come to Jesus with the load for two quiet minutes. <em>Jesus, I come for rest. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Be still and know',
					'verse_ref' => 'Psalm 46:10',
					'body'      => hwbl_plan_day_body(
						'Knowing God is the goal of stillness.',
						'“Be still, and know that I am God.”',
						'What noise keeps you from knowing?',
						'<strong>Practice:</strong> Silence devices for ten minutes. <em>God, I am still. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Sabbath was made for man',
					'verse_ref' => 'Mark 2:27',
					'body'      => hwbl_plan_day_body(
						'Sabbath serves humans; humans are not slaves of productivity.',
						'“The Sabbath was made for man, not man for the Sabbath.”',
						'Where has productivity become a harsh master?',
						'<strong>Practice:</strong> Stop one nonessential task as Sabbath practice. <em>Lord, I receive Sabbath mercy. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'He makes me lie down',
					'verse_ref' => 'Psalm 23:2',
					'body'      => hwbl_plan_day_body(
						'Sometimes the Shepherd makes us lie down because we will not choose it.',
						'“He makes me lie down in green pastures. He leads me beside still waters.”',
						'Where do you need still waters?',
						'<strong>Practice:</strong> Take a short walk without multitasking. <em>Shepherd, lead me beside still waters. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'In returning and rest',
					'verse_ref' => 'Isaiah 30:15',
					'body'      => hwbl_plan_day_body(
						'Salvation is found in returning and rest, not frantic self-rescue.',
						'“In returning and rest you shall be saved; in quietness and in trust shall be your strength.”',
						'Where are you frantic instead of quiet?',
						'<strong>Practice:</strong> Choose quiet trust for one decision today. <em>Lord, my strength is quiet trust. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not be anxious',
					'verse_ref' => 'Philippians 4:6-7',
					'body'      => hwbl_plan_day_body(
						'Prayer escorts the heart into guarded peace.',
						'“Do not be anxious about anything, but in everything by prayer… And the peace of God… will guard your hearts.”',
						'What anxiety needs prayer and thanks?',
						'<strong>Practice:</strong> Pray one request and one thanks. <em>God of peace, guard me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Abide in Me',
					'verse_ref' => 'John 15:4',
					'body'      => hwbl_plan_day_body(
						'Rest is abiding, not achieving.',
						'“Abide in me, and I in you… apart from me you can do nothing.”',
						'How will you abide today?',
						'<strong>Practice:</strong> Set three abide reminders on your phone. <em>Jesus, I abide in You. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Low Enough to Lift Others (7 days)',
			'topic'     => 'humility',
			'shareable' => false,
			'excerpt'   => 'A second humility plan—Christ’s mind, lowliness that lifts others, and freedom from status games.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Have this mind',
					'verse_ref' => 'Philippians 2:5-7',
					'body'      => hwbl_plan_day_body(
						'Humility looks like Jesus emptying Himself.',
						'“Have this mind among yourselves, which is yours in Christ Jesus… he emptied himself.”',
						'Where do you need the mind of Christ today?',
						'<strong>Practice:</strong> Choose one unnoticed act of service. <em>Jesus, give me Your mind. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'God opposes the proud',
					'verse_ref' => 'James 4:6',
					'body'      => hwbl_plan_day_body(
						'Pride invites opposition; grace meets the humble.',
						'“God opposes the proud but gives grace to the humble.”',
						'Where might pride be blocking grace?',
						'<strong>Practice:</strong> Confess one prideful thought. <em>God, give grace to my humility. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Clothe yourselves with humility',
					'verse_ref' => '1 Peter 5:5',
					'body'      => hwbl_plan_day_body(
						'Humility is clothing we put on toward one another.',
						'“Clothe yourselves, all of you, with humility toward one another…”',
						'Whom do you need to treat as more significant today?',
						'<strong>Practice:</strong> Honor someone else’s preference. <em>Lord, clothe me with humility. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Not to be served',
					'verse_ref' => 'Mark 10:45',
					'body'      => hwbl_plan_day_body(
						'Greatness in Jesus’ kingdom serves.',
						'“For even the Son of Man came not to be served but to serve, and to give his life as a ransom for many.”',
						'Where do you expect to be served?',
						'<strong>Practice:</strong> Serve someone without announcing it. <em>Son of Man, teach me to serve. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Blessed are the meek',
					'verse_ref' => 'Matthew 5:5',
					'body'      => hwbl_plan_day_body(
						'Meekness is strength under God’s control.',
						'“Blessed are the meek, for they shall inherit the earth.”',
						'Where can meekness replace force?',
						'<strong>Practice:</strong> Yield one argument that feeds ego. <em>Lord, make me meek. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'He must increase',
					'verse_ref' => 'John 3:30',
					'body'      => hwbl_plan_day_body(
						'Humility celebrates Jesus increasing.',
						'“He must increase, but I must decrease.”',
						'Where do you need to decrease so Christ increases?',
						'<strong>Practice:</strong> Give credit away once today. <em>Jesus, increase in me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Humble yourselves',
					'verse_ref' => '1 Peter 5:6',
					'body'      => hwbl_plan_day_body(
						'Humbling now leads to lifting in due time.',
						'“Humble yourselves, therefore, under the mighty hand of God so that at the proper time he may exalt you.”',
						'What exalting can you leave to God’s timing?',
						'<strong>Practice:</strong> Refuse self-promotion once today. <em>Mighty God, I humble myself under Your hand. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Way of Escape (7 days)',
			'topic'     => 'temptation',
			'shareable' => false,
			'excerpt'   => 'A second temptation plan—watchfulness, escape routes, and Spirit-led refusal of the flesh.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'A way of escape',
					'verse_ref' => '1 Corinthians 10:13',
					'body'      => hwbl_plan_day_body(
						'Temptation is common; escape is promised.',
						'“God is faithful… he will also provide the way of escape, that you may be able to endure it.”',
						'What escape route can you prepare now?',
						'<strong>Practice:</strong> Write your escape plan. <em>Faithful God, show me the way out. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Watch and pray',
					'verse_ref' => 'Matthew 26:41',
					'body'      => hwbl_plan_day_body(
						'Watchfulness and prayer guard weak flesh.',
						'“Watch and pray that you may not enter into temptation. The spirit indeed is willing, but the flesh is weak.”',
						'Where is your flesh weak right now?',
						'<strong>Practice:</strong> Pray before entering a tempting context. <em>Jesus, help me watch and pray. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Flee youthful passions',
					'verse_ref' => '2 Timothy 2:22',
					'body'      => hwbl_plan_day_body(
						'Sometimes wisdom is fleeing, not negotiating.',
						'“So flee youthful passions and pursue righteousness, faith, love, and peace…”',
						'What should you flee—and what should you pursue?',
						'<strong>Practice:</strong> Flee one trigger; pursue one righteous habit. <em>Lord, I flee and I pursue. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Walk by the Spirit',
					'verse_ref' => 'Galatians 5:16',
					'body'      => hwbl_plan_day_body(
						'Spirit-walking starves fleshly gratification.',
						'“But I say, walk by the Spirit, and you will not gratify the desires of the flesh.”',
						'What would Spirit-led next step look like?',
						'<strong>Practice:</strong> Ask the Spirit before acting on desire. <em>Holy Spirit, lead my steps. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Take every thought captive',
					'verse_ref' => '2 Corinthians 10:5',
					'body'      => hwbl_plan_day_body(
						'Temptation often begins as a rehearsed thought.',
						'“We… take every thought captive to obey Christ.”',
						'Which thought needs captivity today?',
						'<strong>Practice:</strong> Interrupt the loop with Scripture. <em>Christ, capture this thought. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Submit and resist',
					'verse_ref' => 'James 4:7',
					'body'      => hwbl_plan_day_body(
						'Submission to God empowers resistance to the devil.',
						'“Submit yourselves therefore to God. Resist the devil, and he will flee from you.”',
						'Have you submitted before you tried resisting?',
						'<strong>Practice:</strong> Submit first in prayer, then resist. <em>God, I submit; help me resist. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Blessed is the one who endures',
					'verse_ref' => 'James 1:12',
					'body'      => hwbl_plan_day_body(
						'Endurance under trial is blessed and crowned.',
						'“Blessed is the man who remains steadfast under trial, for when he has stood the test he will receive the crown of life…”',
						'Where do you need steadfastness today?',
						'<strong>Practice:</strong> Endure one temptation without self-pity. <em>Lord, keep me steadfast. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Rejoice Always (7 days)',
			'topic'     => 'joy',
			'shareable' => false,
			'excerpt'   => 'A second joy plan—rejoicing in the Lord, joy as strength, and gladness that circumstances cannot cancel.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Rejoice in the Lord always',
					'verse_ref' => 'Philippians 4:4',
					'body'      => hwbl_plan_day_body(
						'Joy is commanded in the Lord—not in perfect circumstances.',
						'“Rejoice in the Lord always; again I will say, rejoice.”',
						'What can you rejoice in the Lord about today?',
						'<strong>Practice:</strong> Say aloud two rejoicings in the Lord. <em>Lord, I rejoice in You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The joy of the Lord is strength',
					'verse_ref' => 'Nehemiah 8:10',
					'body'      => hwbl_plan_day_body(
						'Joy strengthens weary obedience.',
						'“Do not be grieved, for the joy of the Lord is your strength.”',
						'Where do you need joy-strength?',
						'<strong>Practice:</strong> Ask God for joy as strength, not escape. <em>Lord, be my joy and strength. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Fullness of joy',
					'verse_ref' => 'Psalm 16:11',
					'body'      => hwbl_plan_day_body(
						'Joy’s fullness is in God’s presence.',
						'“You make known to me the path of life; in your presence there is fullness of joy…”',
						'Are you seeking fullness elsewhere?',
						'<strong>Practice:</strong> Spend five minutes seeking His presence. <em>God, fill me with joy in Your presence. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Ask that your joy may be full',
					'verse_ref' => 'John 16:24',
					'body'      => hwbl_plan_day_body(
						'Prayer is a path to fullness of joy.',
						'“Until now you have asked nothing in my name. Ask, and you will receive, that your joy may be full.”',
						'What will you ask in Jesus’ name today?',
						'<strong>Practice:</strong> Ask boldly for good gifts. <em>Jesus, I ask in Your name. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Count it all joy',
					'verse_ref' => 'James 1:2',
					'body'      => hwbl_plan_day_body(
						'Trials can be counted as joy because of what they produce.',
						'“Count it all joy, my brothers, when you meet trials of various kinds…”',
						'What trial can you count differently today?',
						'<strong>Practice:</strong> Thank God for one refining trial. <em>Lord, I count this with joy. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'These things I have spoken',
					'verse_ref' => 'John 15:11',
					'body'      => hwbl_plan_day_body(
						'Jesus wants His joy in us.',
						'“These things I have spoken to you, that my joy may be in you, and that your joy may be full.”',
						'Are you abiding enough for His joy to remain?',
						'<strong>Practice:</strong> Abide in a verse for five minutes. <em>Jesus, put Your joy in me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Restore to me the joy',
					'verse_ref' => 'Psalm 51:12',
					'body'      => hwbl_plan_day_body(
						'Joy can be restored after failure.',
						'“Restore to me the joy of your salvation, and uphold me with a willing spirit.”',
						'Where do you need restored joy?',
						'<strong>Practice:</strong> Confess and ask for restored joy. <em>God, restore my joy. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Called for Good Works (7 days)',
			'topic'     => 'purpose',
			'shareable' => false,
			'excerpt'   => 'A second purpose plan—created for good works, working as worship, and calling lived in ordinary places.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Created for good works',
					'verse_ref' => 'Ephesians 2:10',
					'body'      => hwbl_plan_day_body(
						'You are God’s workmanship with prepared works.',
						'“For we are his workmanship, created in Christ Jesus for good works, which God prepared beforehand, that we should walk in them.”',
						'Which prepared work is in front of you today?',
						'<strong>Practice:</strong> Walk in one good work intentionally. <em>Lord, I walk in what You prepared. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Whatever you do',
					'verse_ref' => 'Colossians 3:23',
					'body'      => hwbl_plan_day_body(
						'Ordinary work becomes worship.',
						'“Whatever you do, work heartily, as for the Lord and not for men.”',
						'How can today’s task become for the Lord?',
						'<strong>Practice:</strong> Offer your next task to Jesus aloud. <em>Lord, I work for You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Let your light shine',
					'verse_ref' => 'Matthew 5:16',
					'body'      => hwbl_plan_day_body(
						'Purpose includes visible good that points to the Father.',
						'“In the same way, let your light shine before others, so that they may see your good works and give glory to your Father who is in heaven.”',
						'Where can light shine without self-glory?',
						'<strong>Practice:</strong> Do one good work quietly for God’s glory. <em>Father, get the glory. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Seek first the kingdom',
					'verse_ref' => 'Matthew 6:33',
					'body'      => hwbl_plan_day_body(
						'Purpose is reordered by kingdom first.',
						'“But seek first the kingdom of God and his righteousness, and all these things will be added to you.”',
						'What rival “first” needs demoting?',
						'<strong>Practice:</strong> Put kingdom first in one calendar choice. <em>God, I seek You first. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Run with endurance',
					'verse_ref' => 'Hebrews 12:1',
					'body'      => hwbl_plan_day_body(
						'Calling is a race with endurance, eyes on Jesus.',
						'“…let us run with endurance the race that is set before us, looking to Jesus…”',
						'What weight slows your race?',
						'<strong>Practice:</strong> Lay aside one distraction. <em>Jesus, I run looking to You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Your labor is not in vain',
					'verse_ref' => '1 Corinthians 15:58',
					'body'      => hwbl_plan_day_body(
						'Resurrection makes steadfast work meaningful.',
						'“Therefore, my beloved brothers, be steadfast… knowing that in the Lord your labor is not in vain.”',
						'What labor feels vain that God calls meaningful?',
						'<strong>Practice:</strong> Thank God that unseen work matters. <em>Lord, my labor is not in vain. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Well done',
					'verse_ref' => 'Matthew 25:21',
					'body'      => hwbl_plan_day_body(
						'Purpose aims at the Master’s joy.',
						'“Well done, good and faithful servant… Enter into the joy of your master.”',
						'What faithfulness can you offer today?',
						'<strong>Practice:</strong> Do one faithful act for the Master’s joy. <em>Master, I want to hear well done. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Take Up Your Cross (7 days)',
			'topic'     => 'discipleship',
			'shareable' => false,
			'excerpt'   => 'A second discipleship plan—denying self, taking up the cross, and following Jesus daily.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Take up his cross',
					'verse_ref' => 'Luke 9:23',
					'body'      => hwbl_plan_day_body(
						'Discipleship is daily cross-bearing, not weekend religion.',
						'“If anyone would come after me, let him deny himself and take up his cross daily and follow me.”',
						'What self-denial is Jesus asking today?',
						'<strong>Practice:</strong> Deny one preference to follow Jesus. <em>Jesus, I take up my cross. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Abide in Me',
					'verse_ref' => 'John 15:5',
					'body'      => hwbl_plan_day_body(
						'Fruitfulness comes from abiding, not striving alone.',
						'“I am the vine; you are the branches… apart from me you can do nothing.”',
						'Where are you striving apart from Him?',
						'<strong>Practice:</strong> Abide five minutes before acting. <em>Jesus, I abide. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Teach them to observe',
					'verse_ref' => 'Matthew 28:20',
					'body'      => hwbl_plan_day_body(
						'Disciples make disciples who obey, not only hear.',
						'“…teaching them to observe all that I have commanded you.”',
						'Whom can you help observe one command?',
						'<strong>Practice:</strong> Share one command and practice it together. <em>Lord, make me a disciple who teaches. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'By this all people know',
					'verse_ref' => 'John 13:35',
					'body'      => hwbl_plan_day_body(
						'Love is the disciple’s badge.',
						'“By this all people will know that you are my disciples, if you have love for one another.”',
						'Where does love need to become visible?',
						'<strong>Practice:</strong> Love one hard-to-love person practically. <em>Jesus, mark me by love. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Leave nets behind',
					'verse_ref' => 'Mark 1:17-18',
					'body'      => hwbl_plan_day_body(
						'Following sometimes means leaving.',
						'“And Jesus said to them, ‘Follow me…’ And immediately they left their nets and followed him.”',
						'What net is hard to leave?',
						'<strong>Practice:</strong> Release one competing loyalty a step further. <em>Jesus, I leave to follow. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Count the cost',
					'verse_ref' => 'Luke 14:27-28',
					'body'      => hwbl_plan_day_body(
						'Discipleship is costly and worth counting.',
						'“Whoever does not bear his own cross and come after me cannot be my disciple… count the cost…”',
						'What cost are you avoiding naming?',
						'<strong>Practice:</strong> Name the cost and still say yes. <em>Lord, I count the cost and follow. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'I have fought the good fight',
					'verse_ref' => '2 Timothy 4:7',
					'body'      => hwbl_plan_day_body(
						'Finishing well is discipleship’s long aim.',
						'“I have fought the good fight, I have finished the race, I have kept the faith.”',
						'What would finishing well require this month?',
						'<strong>Practice:</strong> Choose one endurance habit. <em>Lord, help me keep the faith. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Ask for Wisdom (7 days)',
			'topic'     => 'wisdom',
			'shareable' => false,
			'excerpt'   => 'A second wisdom plan—asking God, fearing the Lord, and walking wisely in daily decisions.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Ask for wisdom',
					'verse_ref' => 'James 1:5',
					'body'      => hwbl_plan_day_body(
						'God gives wisdom generously to those who ask.',
						'“If any of you lacks wisdom, let him ask God, who gives generously to all without reproach…”',
						'What decision needs asked-for wisdom?',
						'<strong>Practice:</strong> Ask specifically and wait before deciding. <em>Generous God, give me wisdom. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The fear of the Lord',
					'verse_ref' => 'Proverbs 9:10',
					'body'      => hwbl_plan_day_body(
						'Wisdom begins with reverent fear of God.',
						'“The fear of the Lord is the beginning of wisdom, and the knowledge of the Holy One is insight.”',
						'Where is reverence missing from your choices?',
						'<strong>Practice:</strong> Begin a decision by acknowledging God. <em>Holy One, I fear You first. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Trust and do not lean',
					'verse_ref' => 'Proverbs 3:5-6',
					'body'      => hwbl_plan_day_body(
						'Wisdom trusts God more than personal insight.',
						'“Trust in the Lord with all your heart, and do not lean on your own understanding…”',
						'Where are you leaning on your own understanding?',
						'<strong>Practice:</strong> Acknowledge Him in one path today. <em>Lord, I trust You; straighten my path. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Wisdom from above',
					'verse_ref' => 'James 3:17',
					'body'      => hwbl_plan_day_body(
						'Heavenly wisdom looks peaceful and sincere.',
						'“But the wisdom from above is first pure, then peaceable, gentle, open to reason…”',
						'Does your “wisdom” look like this list?',
						'<strong>Practice:</strong> Choose the peaceable option once. <em>Lord, give wisdom from above. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Get wisdom',
					'verse_ref' => 'Proverbs 4:7',
					'body'      => hwbl_plan_day_body(
						'Wisdom is worth pursuing as a first thing.',
						'“The beginning of wisdom is this: Get wisdom, and whatever you get, get insight.”',
						'What competing pursuit outranks wisdom?',
						'<strong>Practice:</strong> Read one Proverbs chapter slowly. <em>Lord, I get wisdom today. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Walk wisely',
					'verse_ref' => 'Ephesians 5:15-16',
					'body'      => hwbl_plan_day_body(
						'Wisdom redeems time in evil days.',
						'“Look carefully then how you walk, not as unwise but as wise, making the best use of the time…”',
						'Where is time being wasted unwisely?',
						'<strong>Practice:</strong> Redeem one hour for what matters. <em>Lord, help me walk wisely. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Christ our wisdom',
					'verse_ref' => '1 Corinthians 1:30',
					'body'      => hwbl_plan_day_body(
						'Jesus Himself is our wisdom from God.',
						'“And because of him you are in Christ Jesus, who became to us wisdom from God…”',
						'How does Christ-as-wisdom reframe your next choice?',
						'<strong>Practice:</strong> Ask, “What looks like Jesus?” before choosing. <em>Christ, be my wisdom. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Salt and Light (7 days)',
			'topic'     => 'witness',
			'shareable' => false,
			'excerpt'   => 'A second witness plan—salt, light, ready answers, and gospel courage in ordinary places.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Salt and light',
					'verse_ref' => 'Matthew 5:13-16',
					'body'      => hwbl_plan_day_body(
						'Disciples preserve and illuminate for the Father’s glory.',
						'“You are the salt of the earth… You are the light of the world… let your light shine before others…”',
						'Where can you be salt without harshness?',
						'<strong>Practice:</strong> Do one good work that points to the Father. <em>Father, get glory through my light. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Ready to give a reason',
					'verse_ref' => '1 Peter 3:15',
					'body'      => hwbl_plan_day_body(
						'Witness includes gentle, prepared reasons for hope.',
						'“…always being prepared to make a defense to anyone who asks you for a reason for the hope that is in you; yet do it with gentleness and respect.”',
						'What is your hope-reason in one sentence?',
						'<strong>Practice:</strong> Write and rehearse your hope sentence. <em>Lord, make me ready and gentle. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Ambassadors for Christ',
					'verse_ref' => '2 Corinthians 5:20',
					'body'      => hwbl_plan_day_body(
						'We speak as ambassadors of reconciliation.',
						'“Therefore, we are ambassadors for Christ, God making his appeal through us.”',
						'Whom is God appealing to through you?',
						'<strong>Practice:</strong> Pray for one person by name, then reach out. <em>Christ, make Your appeal through me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Go and make disciples',
					'verse_ref' => 'Matthew 28:19',
					'body'      => hwbl_plan_day_body(
						'Witness aims at disciples, not only decisions.',
						'“Go therefore and make disciples of all nations…”',
						'What is your next “go” step?',
						'<strong>Practice:</strong> Invite someone to read Scripture with you. <em>Lord, help me make disciples. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Not ashamed of the gospel',
					'verse_ref' => 'Romans 1:16',
					'body'      => hwbl_plan_day_body(
						'Courage comes from knowing the gospel’s power.',
						'“For I am not ashamed of the gospel, for it is the power of God for salvation to everyone who believes…”',
						'Where does shame mute your witness?',
						'<strong>Practice:</strong> Share one gospel sentence without apology. <em>God, I am not ashamed. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Let your speech be gracious',
					'verse_ref' => 'Colossians 4:5-6',
					'body'      => hwbl_plan_day_body(
						'Wise witness walks with gracious speech.',
						'“Walk in wisdom toward outsiders… Let your speech always be gracious, seasoned with salt…”',
						'Is your speech gracious toward outsiders?',
						'<strong>Practice:</strong> Season one conversation with grace. <em>Lord, season my speech. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The harvest is plentiful',
					'verse_ref' => 'Matthew 9:37-38',
					'body'      => hwbl_plan_day_body(
						'Pray for laborers—and be willing to be one.',
						'“The harvest is plentiful, but the laborers are few; therefore pray earnestly to the Lord of the harvest…”',
						'Will you pray and go?',
						'<strong>Practice:</strong> Pray for laborers, then take one laborer step. <em>Lord of the harvest, send me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Peace with God and Neighbor (7 days)',
			'topic'     => 'peace',
			'shareable' => false,
			'excerpt'   => 'A second peace plan—peace with God through Christ, peacemaking with others, and guarded hearts.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Peace with God',
					'verse_ref' => 'Romans 5:1',
					'body'      => hwbl_plan_day_body(
						'True peace begins justified by faith.',
						'“Therefore, since we have been justified by faith, we have peace with God through our Lord Jesus Christ.”',
						'Are you seeking peace of mind without peace with God?',
						'<strong>Practice:</strong> Thank Christ for justifying peace. <em>Jesus, thank You for peace with God. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Peace of God will guard',
					'verse_ref' => 'Philippians 4:6-7',
					'body'      => hwbl_plan_day_body(
						'Prayer ushers in guarded peace.',
						'“Do not be anxious about anything, but in everything by prayer… And the peace of God… will guard your hearts and your minds in Christ Jesus.”',
						'What anxiety needs prayerful surrender?',
						'<strong>Practice:</strong> Pray with thanksgiving over that anxiety. <em>God of peace, guard me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Blessed are the peacemakers',
					'verse_ref' => 'Matthew 5:9',
					'body'      => hwbl_plan_day_body(
						'Peacemaking is a family resemblance to God.',
						'“Blessed are the peacemakers, for they shall be called sons of God.”',
						'Where can you make peace this week?',
						'<strong>Practice:</strong> Take one reconciling step. <em>Father, make me a peacemaker. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'As far as it depends on you',
					'verse_ref' => 'Romans 12:18',
					'body'      => hwbl_plan_day_body(
						'Peace requires what depends on you—not controlling others.',
						'“If possible, so far as it depends on you, live peaceably with all.”',
						'What depends on you that you have avoided?',
						'<strong>Practice:</strong> Do the part that depends on you. <em>Lord, help me live peaceably. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Let the peace of Christ rule',
					'verse_ref' => 'Colossians 3:15',
					'body'      => hwbl_plan_day_body(
						'Christ’s peace is an umpire for the heart.',
						'“And let the peace of Christ rule in your hearts, to which indeed you were called in one body.”',
						'What decision needs peace as umpire?',
						'<strong>Practice:</strong> Pause until Christ’s peace rules. <em>Christ, rule my heart with peace. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'A soft answer',
					'verse_ref' => 'Proverbs 15:1',
					'body'      => hwbl_plan_day_body(
						'Peace often begins with tone.',
						'“A soft answer turns away wrath, but a harsh word stirs up anger.”',
						'Where can a soft answer change the outcome?',
						'<strong>Practice:</strong> Soften your first sentence once today. <em>Lord, give me a soft answer. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Peace I leave with you',
					'verse_ref' => 'John 14:27',
					'body'      => hwbl_plan_day_body(
						'Jesus gives a peace the world cannot manufacture.',
						'“Peace I leave with you; my peace I give to you. Not as the world gives do I give to you.”',
						'What worldly peace-substitute can you release?',
						'<strong>Practice:</strong> Receive His peace by name in prayer. <em>Jesus, give me Your peace. Amen.</em>'
					),
				),
			),
		),
	);
}
