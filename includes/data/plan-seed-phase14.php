<?php
/**
 * Phase 14: divorce topic + priority second plans for high-need topics.
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
 * Phase 14 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase14_definitions() {
	$safety = 'This reading plan is pastoral Scripture study, not crisis counseling, legal advice, or medical care. If you are in immediate danger, call your local emergency number. In the US and Canada, call or text 988 (Suicide &amp; Crisis Lifeline). Reach a trusted pastor, counselor, or doctor alongside these daily readings.';

	$divorce_note = $safety . ' If you are being harmed at home, seek safety first—this plan is not a call to stay in danger. See also the domestic-violence reading plan <em>Safe in the Shadow of the Almighty</em>.';

	return array(
		array(
			'title'     => 'Walking Through Divorce (7 days)',
			'topic'     => 'divorce',
			'shareable' => false,
			'excerpt'   => 'Seven days of pastoral Scripture for the grief, anger, shame, and hope that often accompany divorce—not legal advice, and never a mandate to stay in danger.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'The Lord is near the brokenhearted',
					'verse_ref' => 'Psalm 34:18',
					'body'      => hwbl_plan_day_body(
						$divorce_note . ' Divorce can feel like a death with paperwork. God draws near to shattered hearts.',
						'“The Lord is near to the brokenhearted and saves the crushed in spirit.” Nearness is not a slogan; it is God’s posture toward crushed people.',
						'Where does your spirit feel crushed today—and have you told God honestly?',
						'<strong>Practice:</strong> Write one honest sentence of grief or anger to God. <em>Lord, come near to my broken heart. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Pour out your complaint',
					'verse_ref' => 'Psalm 142:1-2',
					'body'      => hwbl_plan_day_body(
						'Faith does not require polishing your pain. Lament is permitted speech.',
						'“With my voice I cry out to the Lord… I pour out my complaint before him; I tell my trouble before him.” God can hold what friends cannot.',
						'What trouble have you been rehearsing alone instead of pouring out?',
						'<strong>Practice:</strong> Pray Psalm 142 aloud as your own words. <em>Lord, I pour out my complaint before You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Cast your cares',
					'verse_ref' => '1 Peter 5:7',
					'body'      => hwbl_plan_day_body(
						'Logistics, children, money, and reputation can stack into anxiety. Scripture invites casting.',
						'“Casting all your anxieties on him, because he cares for you.” Care is personal—God is not bored by your details.',
						'Which anxiety are you still carrying as if He does not care?',
						'<strong>Practice:</strong> List three cares and physically cross them out as cast. <em>Caring God, I cast these on You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Forgive as the Lord forgave you',
					'verse_ref' => 'Colossians 3:13',
					'body'      => hwbl_plan_day_body(
						'Forgiveness is not pretending harm never happened, nor rushing reconciliation that is unsafe. It is releasing vengeance to God.',
						'“Bearing with one another and, if one has a complaint against another, forgiving each other; as the Lord has forgiven you, so you also must forgive.” The pattern is the cross.',
						'What complaint are you nursing that keeps you bound?',
						'<strong>Practice:</strong> Tell God you release revenge; seek wise counsel for boundaries. <em>Lord, as You forgave me, teach me to forgive. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'A future and a hope',
					'verse_ref' => 'Jeremiah 29:11',
					'body'      => hwbl_plan_day_body(
						'Exile felt like the end of the story for Judah. God still spoke future and hope.',
						'“For I know the plans I have for you, declares the Lord, plans for welfare and not for evil, to give you a future and a hope.” Hope is God’s character, not denial of loss.',
						'Where have you decided there is no future left?',
						'<strong>Practice:</strong> Ask God for one next faithful step—not the whole map. <em>Lord, give me a future and a hope in You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'My grace is sufficient',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'You will not feel strong enough for every day of this season. Grace meets weakness.',
						'“My grace is sufficient for you, for my power is made perfect in weakness.” Weakness can become the place God’s power shows.',
						'What weakness are you ashamed of that God may want to fill with grace?',
						'<strong>Practice:</strong> Admit one weakness to God and a trusted friend. <em>Lord, Your grace is enough for today. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He will wipe every tear',
					'verse_ref' => 'Revelation 21:4',
					'body'      => hwbl_plan_day_body(
						'Divorce is not the last word. The story ends with wiped tears and renewed dwelling with God.',
						'“He will wipe away every tear from their eyes, and death shall be no more, neither shall there be mourning, nor crying, nor pain anymore…”',
						'How does this ending steady you for one more faithful day?',
						'<strong>Practice:</strong> Encourage someone else who grieves with this hope. <em>Coming King, wipe tears—and keep me until You do. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Peace in the Storm (7 days)',
			'topic'     => 'anxiety',
			'shareable' => false,
			'excerpt'   => 'A second walk through anxiety—receiving Christ’s peace when storms do not immediately stop.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Peace, be still',
					'verse_ref' => 'Mark 4:39',
					'body'      => hwbl_plan_day_body(
						'Anxiety can feel like a storm that will never quiet. Jesus speaks to wind and waves.',
						'“And he awoke and rebuked the wind and said to the sea, ‘Peace! Be still!’ And the wind ceased, and there was a great calm.” His authority is greater than the storm.',
						'What storm are you staring at more than Jesus?',
						'<strong>Practice:</strong> Name the storm and pray, “Peace, be still,” over it. <em>Jesus, speak peace into my storm. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Do not be anxious about tomorrow',
					'verse_ref' => 'Matthew 6:34',
					'body'      => hwbl_plan_day_body(
						'Worry borrows tomorrow’s trouble and empties today’s strength.',
						'“Therefore do not be anxious about tomorrow, for tomorrow will be anxious for itself. Sufficient for the day is its own trouble.”',
						'What tomorrow-fear is stealing today?',
						'<strong>Practice:</strong> Write tomorrow’s list, then close it and do only today’s next step. <em>Father, give me today—and keep tomorrow. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'When I am afraid',
					'verse_ref' => 'Psalm 56:3-4',
					'body'      => hwbl_plan_day_body(
						'Fear is not failure when it becomes trust.',
						'“When I am afraid, I put my trust in you. In God, whose word I praise… what can flesh do to me?”',
						'Where can fear become a doorway to trust today?',
						'<strong>Practice:</strong> When fear rises, say Psalm 56:3 aloud. <em>God, when I am afraid, I trust You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Perfect love casts out fear',
					'verse_ref' => '1 John 4:18',
					'body'      => hwbl_plan_day_body(
						'Fear often thrives on punishment-stories about God. Love tells a better story.',
						'“There is no fear in love, but perfect love casts out fear. For fear has to do with punishment…”',
						'What punishing picture of God feeds your anxiety?',
						'<strong>Practice:</strong> Meditate on the cross as love that casts out fear. <em>Father, perfect Your love in me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'You keep him in perfect peace',
					'verse_ref' => 'Isaiah 26:3',
					'body'      => hwbl_plan_day_body(
						'Peace is linked to a mind stayed on God.',
						'“You keep him in perfect peace whose mind is stayed on you, because he trusts in you.”',
						'Where is your mind wandering from trust?',
						'<strong>Practice:</strong> Set a phone reminder: “Stay your mind on God.” <em>Lord, keep my mind stayed on You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Let not your hearts be troubled',
					'verse_ref' => 'John 14:1',
					'body'      => hwbl_plan_day_body(
						'Jesus speaks to troubled hearts with a call to trust.',
						'“Let not your hearts be troubled. Believe in God; believe also in me.” Belief here is relational resting, not gritted teeth.',
						'What would believing Jesus look like in your next anxious hour?',
						'<strong>Practice:</strong> Place a hand on your chest and pray John 14:1 slowly. <em>Jesus, I believe—steady my heart. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The God of peace will be with you',
					'verse_ref' => 'Philippians 4:8-9',
					'body'      => hwbl_plan_day_body(
						'Peace grows as we practice what is true and honorable.',
						'“Whatever is true… think about these things… practice these things, and the God of peace will be with you.”',
						'Which true thought can replace a looping worry?',
						'<strong>Practice:</strong> List three true things and rehearse them when worry returns. <em>God of peace, be with me as I practice. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Comfort for the Brokenhearted (7 days)',
			'topic'     => 'grief',
			'shareable' => false,
			'excerpt'   => 'A second grief walk—receiving comfort, weeping with hope, and letting God hold what feels unfinished.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Blessed are those who mourn',
					'verse_ref' => 'Matthew 5:4',
					'body'      => hwbl_plan_day_body(
						'Mourning is not a lack of faith. Jesus calls mourners blessed.',
						'“Blessed are those who mourn, for they shall be comforted.” Comfort is promised, not demanded on a schedule.',
						'Have you been rushing past mourning to look strong?',
						'<strong>Practice:</strong> Give yourself permission to mourn for ten quiet minutes. <em>Jesus, comfort me as I mourn. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Jesus wept',
					'verse_ref' => 'John 11:35',
					'body'      => hwbl_plan_day_body(
						'The shortest verse is one of the deepest: God-with-us weeps.',
						'“Jesus wept.” Tears are not the opposite of resurrection hope; they can travel with it.',
						'What loss still needs tears you have been holding back?',
						'<strong>Practice:</strong> Tell Jesus the name of your loss without editing. <em>Weeping Savior, meet me in my tears. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'God of all comfort',
					'verse_ref' => '2 Corinthians 1:3-4',
					'body'      => hwbl_plan_day_body(
						'Comfort received becomes comfort shared—in due time.',
						'“Blessed be… the God of all comfort, who comforts us in all our affliction, so that we may be able to comfort those who are in any affliction…”',
						'Whom might your present grief one day help you comfort?',
						'<strong>Practice:</strong> Receive comfort today; note one person who suffers similarly. <em>God of all comfort, hold me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'We do not grieve as others do',
					'verse_ref' => '1 Thessalonians 4:13-14',
					'body'      => hwbl_plan_day_body(
						'Christian grief is real—and it is not hopeless.',
						'“We do not want you to be uninformed… that you may not grieve as others do who have no hope. For since we believe that Jesus died and rose again…”',
						'Where has hope felt thin in your grief?',
						'<strong>Practice:</strong> Speak one resurrection hope aloud over your loss. <em>Risen Lord, hold my grief with hope. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'My tears in Your bottle',
					'verse_ref' => 'Psalm 56:8',
					'body'      => hwbl_plan_day_body(
						'No tear is wasted or unnoticed.',
						'“You have kept count of my tossings; put my tears in your bottle. Are they not in your book?”',
						'What tear do you fear was pointless?',
						'<strong>Practice:</strong> Thank God that He records what others overlook. <em>Lord, keep my tears—You see me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'A time to weep',
					'verse_ref' => 'Ecclesiastes 3:1-4',
					'body'      => hwbl_plan_day_body(
						'Grief has seasons. Wisdom refuses to force a premature smile.',
						'“For everything there is a season… a time to weep, and a time to laugh; a time to mourn, and a time to dance.”',
						'What season are you in—and who is pressuring you to skip it?',
						'<strong>Practice:</strong> Honor this season with one gentle boundary. <em>Lord of seasons, meet me in weeping time. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He will wipe every tear',
					'verse_ref' => 'Revelation 21:4',
					'body'      => hwbl_plan_day_body(
						'Comfort now is a down payment. Full wiping of tears is coming.',
						'“He will wipe away every tear from their eyes…”',
						'How does future comfort change how you carry today?',
						'<strong>Practice:</strong> Share this promise with another mourner. <em>Coming King, wipe every tear. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'When the Darkness Lingers (7 days)',
			'topic'     => 'depression',
			'shareable' => false,
			'excerpt'   => 'A second depression plan for lingering heaviness—honest lament, small faithfulness, and hope that does not shame slow healing.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Why are you cast down?',
					'verse_ref' => 'Psalm 42:5',
					'body'      => hwbl_plan_day_body(
						$safety . ' Depression can linger past easy answers. The psalmist talks to his own soul.',
						'“Why are you cast down, O my soul, and why are you in turmoil within me? Hope in God…” Honest questions and hope can share a verse.',
						'What would you say if you spoke kindly to your own soul today?',
						'<strong>Practice:</strong> Pray Psalm 42:5 slowly twice. <em>God, my soul hopes in You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Even the darkness is not dark',
					'verse_ref' => 'Psalm 139:11-12',
					'body'      => hwbl_plan_day_body(
						'Darkness can feel like God’s absence. Scripture says otherwise.',
						'“If I say, ‘Surely the darkness shall cover me’… even the darkness is not dark to you…”',
						'Where have you assumed God cannot find you in the dark?',
						'<strong>Practice:</strong> Sit in a dim room and thank God He sees you there. <em>Lord, even darkness is light to You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'A bruised reed',
					'verse_ref' => 'Isaiah 42:3',
					'body'      => hwbl_plan_day_body(
						'Jesus does not crush the barely holding-on.',
						'“A bruised reed he will not break, and a faintly burning wick he will not quench…”',
						'Have you been harsh with your own faintly burning wick?',
						'<strong>Practice:</strong> Do one small kind thing for your body (water, walk, rest). <em>Gentle Savior, do not quench this wick. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Sufficient for the day',
					'verse_ref' => 'Matthew 6:34',
					'body'      => hwbl_plan_day_body(
						'Depression often floods the future. Jesus limits the load to today.',
						'“Sufficient for the day is its own trouble.” One day at a time is holy wisdom.',
						'What future scenario is crushing today’s strength?',
						'<strong>Practice:</strong> Shrink your goal list to one faithful next step. <em>Father, enough for today. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'The Lord is my shepherd',
					'verse_ref' => 'Psalm 23:1-3',
					'body'      => hwbl_plan_day_body(
						'When energy is gone, being shepherded matters more than striving.',
						'“The Lord is my shepherd; I shall not want. He makes me lie down in green pastures. He leads me beside still waters. He restores my soul.”',
						'Will you let Him make you lie down instead of forcing productivity?',
						'<strong>Practice:</strong> Take a short rest without apology. <em>Shepherd, restore my soul. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Bear one another’s burdens',
					'verse_ref' => 'Galatians 6:2',
					'body'      => hwbl_plan_day_body(
						'Isolation feeds darkness. Burden-bearing is discipleship.',
						'“Bear one another’s burdens, and so fulfill the law of Christ.”',
						'Whom can you let help carry this week?',
						'<strong>Practice:</strong> Tell one safe person how you really are. <em>Lord, send burden-bearers—and help me receive. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Light shines in the darkness',
					'verse_ref' => 'John 1:5',
					'body'      => hwbl_plan_day_body(
						'Darkness does not get the last word in Christ.',
						'“The light shines in the darkness, and the darkness has not overcome it.”',
						'Where is even a small light already present?',
						'<strong>Practice:</strong> Name one mercy from this week, however small. <em>True Light, shine—and keep shining. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Belonging Again (7 days)',
			'topic'     => 'loneliness',
			'shareable' => false,
			'excerpt'   => 'A second loneliness plan—moving from isolation toward belonging in God and among His people.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'I will never leave you',
					'verse_ref' => 'Hebrews 13:5',
					'body'      => hwbl_plan_day_body(
						'Loneliness can lie that you are abandoned. God speaks presence.',
						'“I will never leave you nor forsake you.” Presence is promise, not mood.',
						'Where does forsakenness feel most true—and most challenged by this verse?',
						'<strong>Practice:</strong> Walk and repeat: “You will not forsake me.” <em>Lord, You are with me. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'God sets the lonely in a home',
					'verse_ref' => 'Psalm 68:6',
					'body'      => hwbl_plan_day_body(
						'God’s heart is toward the solitary.',
						'“God settles the solitary in a home…” Belonging is His idea.',
						'What “home” of belonging do you long for?',
						'<strong>Practice:</strong> Ask God to plant you among His people in a concrete way. <em>God, settle me in belonging. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Not neglecting to meet',
					'verse_ref' => 'Hebrews 10:24-25',
					'body'      => hwbl_plan_day_body(
						'Isolation can become a habit. Gathering stirs courage.',
						'“…not neglecting to meet together… but encouraging one another.”',
						'What meeting have you been avoiding?',
						'<strong>Practice:</strong> Commit to one gathering this week. <em>Lord, draw me into encouraging fellowship. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Welcome one another',
					'verse_ref' => 'Romans 15:7',
					'body'      => hwbl_plan_day_body(
						'Sometimes belonging begins when we welcome others first.',
						'“Therefore welcome one another as Christ has welcomed you…”',
						'Whom could you welcome this week?',
						'<strong>Practice:</strong> Initiate one welcome (message, coffee, invite). <em>Christ, let Your welcome flow through me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Two are better than one',
					'verse_ref' => 'Ecclesiastes 4:9-10',
					'body'      => hwbl_plan_day_body(
						'Friendship multiplies strength when we fall.',
						'“Two are better than one… if they fall, one will lift up his fellow.”',
						'Whom could lift—or whom could you ask to lift you?',
						'<strong>Practice:</strong> Ask or offer one concrete lift. <em>Lord, give me faithful companions. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Members of one another',
					'verse_ref' => 'Romans 12:4-5',
					'body'      => hwbl_plan_day_body(
						'You are not a spare part in Christ.',
						'“So we, though many, are one body in Christ, and individually members one of another.”',
						'Where have you been acting like a disconnected member?',
						'<strong>Practice:</strong> Serve or receive in one body ministry this week. <em>Christ, knit me into Your body. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Abide in My love',
					'verse_ref' => 'John 15:9',
					'body'      => hwbl_plan_day_body(
						'Belonging starts in Christ’s love before it shows up in a crowded room.',
						'“As the Father has loved me, so have I loved you. Abide in my love.”',
						'Are you seeking people more than abiding in His love?',
						'<strong>Practice:</strong> Abide ten minutes in John 15 before seeking other company. <em>Jesus, I abide in Your love. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Anchored When Questions Rise (7 days)',
			'topic'     => 'doubt',
			'shareable' => false,
			'excerpt'   => 'A second doubt plan—bringing questions to Jesus without pretending certainty you do not feel.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'I believe; help my unbelief',
					'verse_ref' => 'Mark 9:24',
					'body'      => hwbl_plan_day_body(
						'Doubt and faith can share a sentence. Jesus hears both.',
						'“Immediately the father of the child cried out and said, ‘I believe; help my unbelief!’”',
						'What unbelief needs honest naming today?',
						'<strong>Practice:</strong> Pray that sentence as your own. <em>Jesus, I believe—help my unbelief. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Come and see',
					'verse_ref' => 'John 1:46',
					'body'      => hwbl_plan_day_body(
						'Skepticism is invited toward Jesus, not silenced.',
						'“Nathanael said to him, ‘Can anything good come out of Nazareth?’ Philip said to him, ‘Come and see.’”',
						'What would “come and see” look like for your questions?',
						'<strong>Practice:</strong> Read one Gospel chapter slowly as investigation. <em>Lord, I come to see. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Thomas answered Him',
					'verse_ref' => 'John 20:27-28',
					'body'      => hwbl_plan_day_body(
						'Jesus meets Thomas’s need for evidence—and calls for faith.',
						'“Do not disbelieve, but believe… Thomas answered him, ‘My Lord and my God!’”',
						'What would move your heart from distance to confession?',
						'<strong>Practice:</strong> Speak “My Lord and my God” over what you do know of Jesus. <em>Jesus, meet my doubt with Yourself. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Your word is a lamp',
					'verse_ref' => 'Psalm 119:105',
					'body'      => hwbl_plan_day_body(
						'Doubt often wants a floodlight. God often gives a lamp for the next step.',
						'“Your word is a lamp to my feet and a light to my path.”',
						'What next obedient step is already clear?',
						'<strong>Practice:</strong> Obey one clear Word while questions remain. <em>Lord, lamp my next step. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Ask for wisdom',
					'verse_ref' => 'James 1:5-6',
					'body'      => hwbl_plan_day_body(
						'God is not stingy with wisdom for those who ask.',
						'“If any of you lacks wisdom, let him ask God, who gives generously to all without reproach…”',
						'Have you researched everyone except God?',
						'<strong>Practice:</strong> Ask specifically for wisdom on one live question. <em>Generous Father, give wisdom. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'We walk by faith',
					'verse_ref' => '2 Corinthians 5:7',
					'body'      => hwbl_plan_day_body(
						'Faith is not sight—but it is not blindness either. It trusts a Person.',
						'“For we walk by faith, not by sight.”',
						'Where are you demanding sight before trust?',
						'<strong>Practice:</strong> Take one trust-step without full clarity. <em>Lord, I walk by faith. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Now we see in a mirror dimly',
					'verse_ref' => '1 Corinthians 13:12',
					'body'      => hwbl_plan_day_body(
						'Partial knowledge is normal this side of glory.',
						'“For now we see in a mirror dimly, but then face to face…”',
						'Can you live with dim seeing while clinging to Christ?',
						'<strong>Practice:</strong> Thank God for what you do see of Him. <em>Lord, I wait to see face to face. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Trusting God with Money (7 days)',
			'topic'     => 'finances',
			'shareable' => false,
			'excerpt'   => 'A second finances plan—trust, generosity, and freedom from money-anxiety under God’s care.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'You cannot serve God and money',
					'verse_ref' => 'Matthew 6:24',
					'body'      => hwbl_plan_day_body(
						'Money makes a poor master. Jesus forces a loyalty choice.',
						'“No one can serve two masters… You cannot serve God and money.”',
						'Where is money quietly mastering your decisions?',
						'<strong>Practice:</strong> Name one money-driven fear and surrender it. <em>Lord, You are my Master—not money. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Do not be anxious',
					'verse_ref' => 'Matthew 6:31-33',
					'body'      => hwbl_plan_day_body(
						'Provision anxiety is ancient. Seek first the kingdom.',
						'“Therefore do not be anxious, saying, ‘What shall we eat?’… But seek first the kingdom of God and his righteousness, and all these things will be added to you.”',
						'What “these things” are crowding kingdom seeking?',
						'<strong>Practice:</strong> Give or serve first before fretting the budget for ten minutes. <em>Father, I seek Your kingdom first. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'The Lord is my shepherd',
					'verse_ref' => 'Psalm 23:1',
					'body'      => hwbl_plan_day_body(
						'Contentment starts with who shepherds you.',
						'“The Lord is my shepherd; I shall not want.”',
						'Where are you living as if you have no Shepherd?',
						'<strong>Practice:</strong> Pray Psalm 23 before opening bank or bills. <em>Shepherd, I shall not want. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Honor the Lord with your wealth',
					'verse_ref' => 'Proverbs 3:9-10',
					'body'      => hwbl_plan_day_body(
						'Firstfruits trust God with the beginning, not only leftovers.',
						'“Honor the Lord with your wealth and with the firstfruits of all your produce…”',
						'What would firstfruits look like in your season?',
						'<strong>Practice:</strong> Set aside a first gift—however small—with thanksgiving. <em>Lord, I honor You with what You provide. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'God loves a cheerful giver',
					'verse_ref' => '2 Corinthians 9:7',
					'body'      => hwbl_plan_day_body(
						'Giving forms trust muscles.',
						'“Each one must give as he has decided in his heart, not reluctantly or under compulsion, for God loves a cheerful giver.”',
						'Is your giving reluctant, absent, or cheerful?',
						'<strong>Practice:</strong> Give one cheerful gift today. <em>Lord, form a cheerful giver’s heart. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Keep your life free',
					'verse_ref' => 'Hebrews 13:5',
					'body'      => hwbl_plan_day_body(
						'Presence defeats money-love.',
						'“Keep your life free from love of money, and be content with what you have, for he has said, ‘I will never leave you nor forsake you.’”',
						'Does money-anxiety reveal fear that God might forsake you?',
						'<strong>Practice:</strong> Thank God for presence before reviewing finances. <em>Lord, You will not forsake me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'My God will supply',
					'verse_ref' => 'Philippians 4:19',
					'body'      => hwbl_plan_day_body(
						'Supply is promised according to God’s riches in glory—not according to our panic.',
						'“And my God will supply every need of yours according to his riches in glory in Christ Jesus.”',
						'What need are you confusing with a want?',
						'<strong>Practice:</strong> List needs vs wants; thank God for one supplied need. <em>God, supply what I need in Christ. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Strength in Weakness (7 days)',
			'topic'     => 'health',
			'shareable' => false,
			'excerpt'   => 'A second health plan—meeting God in bodily weakness, waiting, and hope that does not depend on perfect healing timelines.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'My grace is sufficient',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'Not every thorn is removed. Sometimes grace is the answer.',
						'“My grace is sufficient for you, for my power is made perfect in weakness.”',
						'What weakness are you demanding God erase rather than inhabit?',
						'<strong>Practice:</strong> Ask for sufficient grace for today’s symptoms. <em>Lord, Your grace is enough. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'This slight momentary affliction',
					'verse_ref' => '2 Corinthians 4:16-17',
					'body'      => hwbl_plan_day_body(
						'Paul does not minimize pain; he relocates it beside eternal glory.',
						'“So we do not lose heart… For this light momentary affliction is preparing for us an eternal weight of glory…”',
						'Where is affliction filling your whole field of vision?',
						'<strong>Practice:</strong> Read 2 Corinthians 4:16–18 twice slowly. <em>Lord, renew my inner self today. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'The prayer of faith',
					'verse_ref' => 'James 5:14-15',
					'body'      => hwbl_plan_day_body(
						'Scripture invites prayer for the sick without shame.',
						'“Is anyone among you sick? Let him call for the elders of the church, and let them pray over him…”',
						'Have you isolated instead of asking for prayer?',
						'<strong>Practice:</strong> Ask one believer or elder to pray for you. <em>Lord, receive prayer for my body. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'He gives power to the faint',
					'verse_ref' => 'Isaiah 40:29-31',
					'body'      => hwbl_plan_day_body(
						'Waiting on the Lord renews strength we do not manufacture.',
						'“He gives power to the faint… they who wait for the Lord shall renew their strength…”',
						'Are you forcing strength or waiting for renewal?',
						'<strong>Practice:</strong> Rest ten minutes as waiting, not wasting. <em>Lord, renew my strength. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Your body is a temple',
					'verse_ref' => '1 Corinthians 6:19-20',
					'body'      => hwbl_plan_day_body(
						'Care for the body is stewardship, not vanity—especially in illness.',
						'“Or do you not know that your body is a temple of the Holy Spirit… So glorify God in your body.”',
						'What stewarding step (rest, medicine, food, appointment) have you delayed?',
						'<strong>Practice:</strong> Take one wise care step today. <em>Spirit, this body is Yours—help me steward it. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Weep with those who weep',
					'verse_ref' => 'Romans 12:15',
					'body'      => hwbl_plan_day_body(
						'Chronic illness can isolate. The body of Christ is meant to feel together.',
						'“Rejoice with those who rejoice, weep with those who weep.”',
						'Whom can you let weep with you—or weep with?',
						'<strong>Practice:</strong> Share one honest update with a safe person. <em>Lord, knit me into shared tears and joys. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He will wipe every tear',
					'verse_ref' => 'Revelation 21:4',
					'body'      => hwbl_plan_day_body(
						'Bodies will be renewed. Pain is not forever.',
						'“He will wipe away every tear from their eyes… nor pain anymore…”',
						'How does future healing steady present faithfulness?',
						'<strong>Practice:</strong> Thank God for the coming day without pain. <em>Coming King, renew all things—including these bodies. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Raising Kids in Everyday Faith (7 days)',
			'topic'     => 'parenting',
			'shareable' => false,
			'excerpt'   => 'A second parenting plan—discipleship in ordinary moments, not only big talks.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Teach them diligently',
					'verse_ref' => 'Deuteronomy 6:6-7',
					'body'      => hwbl_plan_day_body(
						'Faith is taught along the road, not only in formal lessons.',
						'“And these words that I command you today shall be on your heart. You shall teach them diligently to your children… when you sit… walk… lie down… rise.”',
						'Which ordinary moment could become a faith conversation?',
						'<strong>Practice:</strong> Speak one Scripture at a meal or bedtime. <em>Lord, put Your words on our hearts. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Do not provoke',
					'verse_ref' => 'Ephesians 6:4',
					'body'      => hwbl_plan_day_body(
						'Authority is for nurture under the Lord.',
						'“Fathers, do not provoke your children to anger, but bring them up in the discipline and instruction of the Lord.”',
						'Where might your tone be provoking more than instructing?',
						'<strong>Practice:</strong> Apologize for one harsh moment if needed; restart gently. <em>Lord, form nurture in my leadership. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Train up a child',
					'verse_ref' => 'Proverbs 22:6',
					'body'      => hwbl_plan_day_body(
						'Training is long obedience in the same direction.',
						'“Train up a child in the way he should go; even when he is old he will not depart from it.”',
						'What consistent habit are you building—or neglecting?',
						'<strong>Practice:</strong> Choose one small training habit for this week. <em>Lord, help us train with hope. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Let the children come',
					'verse_ref' => 'Mark 10:14',
					'body'      => hwbl_plan_day_body(
						'Jesus welcomes children; disciples must not hinder.',
						'“Let the children come to me; do not hinder them, for to such belongs the kingdom of God.”',
						'How might you be hindering a child’s approach to Jesus?',
						'<strong>Practice:</strong> Create one unhurried moment of welcome today. <em>Jesus, let our children come to You. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Pray without ceasing',
					'verse_ref' => '1 Thessalonians 5:17',
					'body'      => hwbl_plan_day_body(
						'Parenting is intercession as much as instruction.',
						'“Pray without ceasing.” Short prayers through the day count.',
						'Whose name (child’s) needs more prayer than advice?',
						'<strong>Practice:</strong> Set three prayer alarms with your child’s name. <em>Father, I pray without ceasing for them. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Be an example',
					'verse_ref' => '1 Timothy 4:12',
					'body'      => hwbl_plan_day_body(
						'Children learn what we live more than what we lecture.',
						'“…set the believers an example in speech, in conduct, in love, in faith, in purity.”',
						'Which example area needs the most attention at home?',
						'<strong>Practice:</strong> Model one virtue you want them to catch. <em>Lord, make my life a living lesson. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Unless the Lord builds',
					'verse_ref' => 'Psalm 127:1',
					'body'      => hwbl_plan_day_body(
						'Anxious parenting can deny God’s building work.',
						'“Unless the Lord builds the house, those who build it labor in vain.”',
						'Where are you laboring as if God were absent?',
						'<strong>Practice:</strong> Hand one parenting fear to God and rest. <em>Lord, build this house—we trust You. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Persistent Prayer (7 days)',
			'topic'     => 'prayer',
			'shareable' => false,
			'excerpt'   => 'A second prayer plan—asking, seeking, knocking when answers feel slow.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Ask, seek, knock',
					'verse_ref' => 'Matthew 7:7-8',
					'body'      => hwbl_plan_day_body(
						'Prayer is invited persistence, not one polite request.',
						'“Ask, and it will be given to you; seek, and you will find; knock, and it will be opened to you.”',
						'Where have you stopped knocking too soon?',
						'<strong>Practice:</strong> Ask again for one long-held request. <em>Father, I ask, seek, and knock. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Always pray and not lose heart',
					'verse_ref' => 'Luke 18:1',
					'body'      => hwbl_plan_day_body(
						'Jesus told a parable so we would not quit.',
						'“And he told them a parable to the effect that they ought always to pray and not lose heart.”',
						'What request has made you lose heart?',
						'<strong>Practice:</strong> Set a daily reminder for that request this week. <em>Lord, I will not lose heart. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Your Father knows',
					'verse_ref' => 'Matthew 6:8',
					'body'      => hwbl_plan_day_body(
						'Persistence is not informing an ignorant God.',
						'“Do not be like them, for your Father knows what you need before you ask him.”',
						'How does His knowing change the tone of your asking?',
						'<strong>Practice:</strong> Begin prayer: “You already know—and I still come.” <em>Father, You know—and I ask. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'In everything by prayer',
					'verse_ref' => 'Philippians 4:6',
					'body'      => hwbl_plan_day_body(
						'Anxiety shrinks when requests are made known with thanksgiving.',
						'“Do not be anxious about anything, but in everything by prayer and supplication with thanksgiving let your requests be made known to God.”',
						'What unprayed anxiety still sits in your chest?',
						'<strong>Practice:</strong> Turn three anxieties into thank-filled requests. <em>God, I make my requests known. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Pray in the Spirit',
					'verse_ref' => 'Ephesians 6:18',
					'body'      => hwbl_plan_day_body(
						'Prayer is warfare and dependence—at all times.',
						'“…praying at all times in the Spirit, with all prayer and supplication…”',
						'When do you most forget to pray?',
						'<strong>Practice:</strong> Pair prayer with a daily trigger (coffee, commute, lunch). <em>Spirit, help me pray at all times. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'If two of you agree',
					'verse_ref' => 'Matthew 18:19-20',
					'body'      => hwbl_plan_day_body(
						'Shared prayer strengthens persistence.',
						'“If two of you agree on earth about anything they ask, it will be done for them by my Father in heaven. For where two or three are gathered in my name, there am I among them.”',
						'Whom could you invite to agree with you in prayer?',
						'<strong>Practice:</strong> Pray with someone for one shared request. <em>Jesus, be among us as we ask. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Your will be done',
					'verse_ref' => 'Matthew 6:10',
					'body'      => hwbl_plan_day_body(
						'Persistent prayer ends in trust: His will, His kingdom.',
						'“Your kingdom come, your will be done, on earth as it is in heaven.”',
						'Can you keep asking while surrendering outcomes?',
						'<strong>Practice:</strong> End every request today with “Your will be done.” <em>Father, Your kingdom come. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Healing Through Forgiveness (7 days)',
			'topic'     => 'forgiveness',
			'shareable' => false,
			'excerpt'   => 'A second forgiveness plan—releasing bitterness and receiving the freedom of the gospel.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Forgive us… as we forgive',
					'verse_ref' => 'Matthew 6:12',
					'body'      => hwbl_plan_day_body(
						'Jesus links received mercy and extended mercy.',
						'“And forgive us our debts, as we also have forgiven our debtors.”',
						'Whose debt are you still collecting with interest?',
						'<strong>Practice:</strong> Pray the Lord’s Prayer slowly, pausing on forgiveness. <em>Father, forgive me—and help me forgive. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Seventy-seven times',
					'verse_ref' => 'Matthew 18:21-22',
					'body'      => hwbl_plan_day_body(
						'Forgiveness is not a one-time math problem.',
						'“I do not say to you seven times, but seventy-seven times.”',
						'Where do you want to stop counting mercy?',
						'<strong>Practice:</strong> Release the same offense again in prayer if it returns. <em>Lord, teach me seventy-seven-times mercy. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Be kind… forgiving one another',
					'verse_ref' => 'Ephesians 4:31-32',
					'body'      => hwbl_plan_day_body(
						'Bitterness has a wardrobe: wrath, clamor, slander. Put it off.',
						'“Let all bitterness and wrath… be put away from you… Be kind to one another, tenderhearted, forgiving one another, as God in Christ forgave you.”',
						'Which bitterness piece are you still wearing?',
						'<strong>Practice:</strong> Put away one bitter rehearsal; do one kind act instead. <em>Christ, as You forgave me, I forgive. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'If possible, live peaceably',
					'verse_ref' => 'Romans 12:18',
					'body'      => hwbl_plan_day_body(
						'Forgiveness does not always restore the same closeness. Peace has limits of dependence.',
						'“If possible, so far as it depends on you, live peaceably with all.”',
						'Are you owning more—or less—than depends on you?',
						'<strong>Practice:</strong> Do your peaceable part; release what you cannot control. <em>Lord, help me live peaceably as far as it depends on me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Leave it to the wrath of God',
					'verse_ref' => 'Romans 12:19',
					'body'      => hwbl_plan_day_body(
						'Forgiveness hands the gavel back to God.',
						'“Beloved, never avenge yourselves, but leave it to the wrath of God…”',
						'What revenge fantasy needs surrendering?',
						'<strong>Practice:</strong> Say aloud: “Vengeance is Yours.” <em>Lord, I leave justice to You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Confess your sins',
					'verse_ref' => 'James 5:16',
					'body'      => hwbl_plan_day_body(
						'Sometimes healing requires owning our part.',
						'“Therefore, confess your sins to one another and pray for one another, that you may be healed.”',
						'What confession might open healing?',
						'<strong>Practice:</strong> Confess specifically to God (and a safe person if wise). <em>Lord, heal through honest confession. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He is faithful and just',
					'verse_ref' => '1 John 1:9',
					'body'      => hwbl_plan_day_body(
						'Forgiveness received fuels forgiveness given.',
						'“If we confess our sins, he is faithful and just to forgive us our sins and to cleanse us from all unrighteousness.”',
						'Have you received cleansing—or only tried harder?',
						'<strong>Practice:</strong> Receive 1 John 1:9 for yourself, then release another. <em>Faithful God, forgive and cleanse me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Secure in Christ (7 days)',
			'topic'     => 'identity',
			'shareable' => false,
			'excerpt'   => 'A second identity plan—resting in who you are in Christ when labels and performance shout louder.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'In Christ',
					'verse_ref' => '2 Corinthians 5:17',
					'body'      => hwbl_plan_day_body(
						'Identity begins with union, not self-reinvention.',
						'“Therefore, if anyone is in Christ, he is a new creation. The old has passed away; behold, the new has come.”',
						'What old label still tries to name you?',
						'<strong>Practice:</strong> Write “in Christ” over that label. <em>Lord, I am a new creation in You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Chosen and beloved',
					'verse_ref' => 'Colossians 3:12',
					'body'      => hwbl_plan_day_body(
						'Before commands come belovedness.',
						'“Put on then, as God’s chosen ones, holy and beloved, compassionate hearts…”',
						'Do you obey to become beloved—or because you are?',
						'<strong>Practice:</strong> Start the day: “I am chosen and beloved.” <em>Father, thank You that I am Yours. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'No condemnation',
					'verse_ref' => 'Romans 8:1',
					'body'      => hwbl_plan_day_body(
						'Shame lies about standing. The gospel settles it.',
						'“There is therefore now no condemnation for those who are in Christ Jesus.”',
						'What condemnation loop needs interrupting?',
						'<strong>Practice:</strong> When shame rises, speak Romans 8:1 aloud. <em>Jesus, there is no condemnation in You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Children of God',
					'verse_ref' => '1 John 3:1',
					'body'      => hwbl_plan_day_body(
						'Adoption is astonishing love.',
						'“See what kind of love the Father has given to us, that we should be called children of God; and so we are.”',
						'Are you living like a hired worker or a child?',
						'<strong>Practice:</strong> Pray “Abba, Father” slowly three times. <em>Father, I am Your child. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Hidden with Christ',
					'verse_ref' => 'Colossians 3:3',
					'body'      => hwbl_plan_day_body(
						'Your life is safer than public opinion.',
						'“For you have died, and your life is hidden with Christ in God.”',
						'Whose opinion feels like it can unhide or undo you?',
						'<strong>Practice:</strong> Release one person’s verdict to God. <em>Christ, my life is hidden with You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'His workmanship',
					'verse_ref' => 'Ephesians 2:10',
					'body'      => hwbl_plan_day_body(
						'You are not self-made; you are crafted for good works.',
						'“For we are his workmanship, created in Christ Jesus for good works, which God prepared beforehand…”',
						'What good work has God prepared that fear keeps you from?',
						'<strong>Practice:</strong> Take one small step into a prepared good work. <em>Lord, I am Your workmanship. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'To the praise of His glory',
					'verse_ref' => 'Ephesians 1:5-6',
					'body'      => hwbl_plan_day_body(
						'Identity ends in worship, not self-focus.',
						'“He predestined us for adoption to himself as sons through Jesus Christ… to the praise of his glorious grace…”',
						'How can your secured identity become praise today?',
						'<strong>Practice:</strong> Thank God for grace that adopted you. <em>Father, to the praise of Your glorious grace. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'First Steps Deeper (7 days)',
			'topic'     => 'new-believer',
			'shareable' => false,
			'excerpt'   => 'A second new-believer plan—going deeper in Word, prayer, church, and obedience after the first foundations.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Like newborn infants',
					'verse_ref' => '1 Peter 2:2-3',
					'body'      => hwbl_plan_day_body(
						'Growth starts with hunger for the Word.',
						'“Like newborn infants, long for the pure spiritual milk, that by it you may grow up into salvation—if indeed you have tasted that the Lord is good.”',
						'Have you tasted His goodness—and are you still hungry?',
						'<strong>Practice:</strong> Read one chapter and note one taste of His goodness. <em>Lord, grow me by Your Word. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Abide in Me',
					'verse_ref' => 'John 15:4-5',
					'body'      => hwbl_plan_day_body(
						'Fruit comes from abiding, not frantic effort.',
						'“Abide in me, and I in you… apart from me you can do nothing.”',
						'Where are you trying to produce fruit alone?',
						'<strong>Practice:</strong> Sit with Jesus ten minutes before tasks. <em>Jesus, I abide in You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Do not neglect meeting',
					'verse_ref' => 'Hebrews 10:24-25',
					'body'      => hwbl_plan_day_body(
						'New life thrives in community.',
						'“…not neglecting to meet together… but encouraging one another.”',
						'Are you connected—or only consuming content alone?',
						'<strong>Practice:</strong> Attend or reconnect with a local gathering. <em>Lord, plant me among Your people. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Be doers of the word',
					'verse_ref' => 'James 1:22',
					'body'      => hwbl_plan_day_body(
						'Hearing without doing deceives.',
						'“But be doers of the word, and not hearers only, deceiving yourselves.”',
						'What Word have you heard without doing?',
						'<strong>Practice:</strong> Obey one clear command today. <em>Lord, make me a doer. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Put on the new self',
					'verse_ref' => 'Ephesians 4:22-24',
					'body'      => hwbl_plan_day_body(
						'Conversion includes a wardrobe change—old patterns off, new self on.',
						'“…put off your old self… and put on the new self, created after the likeness of God in true righteousness and holiness.”',
						'What old pattern needs putting off this week?',
						'<strong>Practice:</strong> Replace one old habit with a new-self practice. <em>Lord, clothe me in the new self. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Always be prepared',
					'verse_ref' => '1 Peter 3:15',
					'body'      => hwbl_plan_day_body(
						'New believers become witnesses—gently.',
						'“Always being prepared to make a defense to anyone who asks you for a reason for the hope that is in you; yet do it with gentleness and respect.”',
						'Could you give a gentle reason for your hope in two minutes?',
						'<strong>Practice:</strong> Write a 3-sentence hope story and practice it. <em>Lord, prepare me with gentleness. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He who began a good work',
					'verse_ref' => 'Philippians 1:6',
					'body'      => hwbl_plan_day_body(
						'You are not finished—and God is not done.',
						'“And I am sure of this, that he who began a good work in you will bring it to completion at the day of Jesus Christ.”',
						'Where do you fear God has stopped mid-work?',
						'<strong>Practice:</strong> Thank God He finishes what He starts. <em>Faithful God, complete Your work in me. Amen.</em>'
					),
				),
			),
		),
	);
}
