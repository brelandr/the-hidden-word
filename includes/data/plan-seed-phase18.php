<?php
/**
 * Phase 18: remaining second plans (purity–kids, gospel, foundations, chronological, seasons).
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
 * Phase 18 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase18_definitions() {
	return array(
		array(
			'title'     => 'Guard Your Heart (7 days)',
			'topic'     => 'purity',
			'shareable' => false,
			'excerpt'   => 'A second purity plan—guarding the heart, fleeing temptation, and walking in the light.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Guard your heart',
					'verse_ref' => 'Proverbs 4:23',
					'body'      => hwbl_plan_day_body(
						'The heart is the spring of life—guard it.',
						'“Keep your heart with all vigilance, for from it flow the springs of life.”',
						'What is flowing into your heart unchecked?',
						'<strong>Practice:</strong> Remove one heart-polluting input. <em>Lord, help me guard my heart. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Blessed are the pure',
					'verse_ref' => 'Matthew 5:8',
					'body'      => hwbl_plan_day_body(
						'Purity of heart sees God.',
						'“Blessed are the pure in heart, for they shall see God.”',
						'Where do you need purity to see God more clearly?',
						'<strong>Practice:</strong> Confess impurity and ask for clean sight. <em>God, purify my heart. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Flee sexual immorality',
					'verse_ref' => '1 Corinthians 6:18',
					'body'      => hwbl_plan_day_body(
						'The body is for the Lord; flee what wars against purity.',
						'“Flee from sexual immorality. Every other sin a person commits is outside the body, but the sexually immoral person sins against his own body.”',
						'What do you need to flee, not manage?',
						'<strong>Practice:</strong> Flee one trigger decisively. <em>Lord, I flee to You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Set no evil thing',
					'verse_ref' => 'Psalm 101:3',
					'body'      => hwbl_plan_day_body(
						'Eyes and screens shape the heart.',
						'“I will not set before my eyes anything that is worthless.”',
						'What worthless thing is before your eyes?',
						'<strong>Practice:</strong> Set a boundary on one screen habit. <em>Lord, I will not set worthless things before my eyes. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Present your members',
					'verse_ref' => 'Romans 6:13',
					'body'      => hwbl_plan_day_body(
						'Present body parts as instruments of righteousness.',
						'“Do not present your members to sin as instruments for unrighteousness, but present yourselves to God…”',
						'What member needs presenting to God today?',
						'<strong>Practice:</strong> Present your body to God in prayer. <em>God, these members are Yours. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Walk as children of light',
					'verse_ref' => 'Ephesians 5:8-10',
					'body'      => hwbl_plan_day_body(
						'Light exposes and transforms.',
						'“…walk as children of light (for the fruit of light is found in all that is good and right and true)…”',
						'Where are you walking in shadows?',
						'<strong>Practice:</strong> Bring one shadow into light with a trusted believer. <em>Lord, I walk as a child of light. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Create in me a clean heart',
					'verse_ref' => 'Psalm 51:10',
					'body'      => hwbl_plan_day_body(
						'God creates clean hearts; we ask.',
						'“Create in me a clean heart, O God, and renew a right spirit within me.”',
						'Will you ask again for a clean heart?',
						'<strong>Practice:</strong> Pray Psalm 51:10 slowly twice. <em>O God, create in me a clean heart. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Content Without Comparing (7 days)',
			'topic'     => 'envy',
			'shareable' => false,
			'excerpt'   => 'A second envy plan—freedom from comparison, rejoicing with others, and contentment in God’s portion.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Rejoice with those who rejoice',
					'verse_ref' => 'Romans 12:15',
					'body'      => hwbl_plan_day_body(
						'Envy struggles to rejoice; love learns it.',
						'“Rejoice with those who rejoice, weep with those who weep.”',
						'Whose win can you rejoice in today?',
						'<strong>Practice:</strong> Congratulate someone sincerely. <em>Lord, teach me to rejoice with others. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Love does not envy',
					'verse_ref' => '1 Corinthians 13:4',
					'body'      => hwbl_plan_day_body(
						'Love and envy cannot share the throne.',
						'“Love is patient and kind; love does not envy or boast…”',
						'Where has envy replaced love?',
						'<strong>Practice:</strong> Choose a loving act toward someone you envy. <em>Lord, replace envy with love. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Do not covet',
					'verse_ref' => 'Exodus 20:17',
					'body'      => hwbl_plan_day_body(
						'Comparison often hides coveting.',
						'“You shall not covet… anything that is your neighbor’s.”',
						'What neighbor portion are you coveting?',
						'<strong>Practice:</strong> Bless that person by name. <em>Lord, free me from coveting. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Each will receive his own reward',
					'verse_ref' => 'Galatians 6:4-5',
					'body'      => hwbl_plan_day_body(
						'Test your own work; carry your own load.',
						'“But let each one test his own work… For each will have to bear his own load.”',
						'How does comparing distract from your load?',
						'<strong>Practice:</strong> Focus on your next faithful task only. <em>Lord, help me test my own work. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Godliness with contentment',
					'verse_ref' => '1 Timothy 6:6',
					'body'      => hwbl_plan_day_body(
						'Contentment undercuts envy’s fuel.',
						'“But godliness with contentment is great gain.”',
						'What is enough for today?',
						'<strong>Practice:</strong> List three enoughs and thank God. <em>Lord, I am content in You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'The last will be first',
					'verse_ref' => 'Matthew 20:16',
					'body'      => hwbl_plan_day_body(
						'Kingdom status overturns envy’s rankings.',
						'“So the last will be first, and the first last.”',
						'Whose ranking are you fighting for?',
						'<strong>Practice:</strong> Celebrate someone “ahead” without bitterness. <em>Lord, Your kingdom rankings are enough. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'I have learned',
					'verse_ref' => 'Philippians 4:11',
					'body'      => hwbl_plan_day_body(
						'Contentment is learned in Christ.',
						'“Not that I am speaking of being in need, for I have learned in whatever situation I am to be content.”',
						'What classroom of contentment are you in?',
						'<strong>Practice:</strong> Ask Christ to teach contentment there. <em>Christ, I learn contentment from You. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Be Still and Know (7 days)',
			'topic'     => 'hurry',
			'shareable' => false,
			'excerpt'   => 'A second unhurried plan—stillness, presence, and refusing the tyranny of urgency.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Be still and know',
					'verse_ref' => 'Psalm 46:10',
					'body'      => hwbl_plan_day_body(
						'Stillness is how we know God is God.',
						'“Be still, and know that I am God.”',
						'What hurry keeps you from knowing?',
						'<strong>Practice:</strong> Be still five minutes with no input. <em>God, I am still before You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Come away and rest',
					'verse_ref' => 'Mark 6:31',
					'body'      => hwbl_plan_day_body(
						'Jesus invites rest even when needs remain.',
						'“And he said to them, ‘Come away by yourselves to a desolate place and rest a while.’”',
						'Will you come away even briefly?',
						'<strong>Practice:</strong> Schedule a short come-away. <em>Jesus, I come away with You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Sufficient for the day',
					'verse_ref' => 'Matthew 6:34',
					'body'      => hwbl_plan_day_body(
						'Hurry often lives in tomorrow.',
						'“Therefore do not be anxious about tomorrow… Sufficient for the day is its own trouble.”',
						'What tomorrow haste is stealing today?',
						'<strong>Practice:</strong> Do only today’s next step. <em>Father, today is enough. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'In returning and rest',
					'verse_ref' => 'Isaiah 30:15',
					'body'      => hwbl_plan_day_body(
						'Strength is quiet trust, not frantic speed.',
						'“In returning and rest you shall be saved; in quietness and in trust shall be your strength.”',
						'Where is frantic self-rescue failing?',
						'<strong>Practice:</strong> Choose quiet trust for one decision. <em>Lord, my strength is quiet trust. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Wait for the Lord',
					'verse_ref' => 'Psalm 27:14',
					'body'      => hwbl_plan_day_body(
						'Waiting takes courage in a hurried age.',
						'“Wait for the Lord; be strong, and let your heart take courage; wait for the Lord!”',
						'Where do you need courage to wait?',
						'<strong>Practice:</strong> Delay one nonessential rush. <em>Lord, I wait with courage. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Martha, Martha',
					'verse_ref' => 'Luke 10:41-42',
					'body'      => hwbl_plan_day_body(
						'One thing is necessary amid many tasks.',
						'“Martha, Martha, you are anxious and troubled about many things, but one thing is necessary.”',
						'What is your one necessary thing today?',
						'<strong>Practice:</strong> Sit at Jesus’ feet before serving. <em>Jesus, one thing is necessary—You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Abide',
					'verse_ref' => 'John 15:4',
					'body'      => hwbl_plan_day_body(
						'Unhurried fruit comes from abiding.',
						'“Abide in me, and I in you… apart from me you can do nothing.”',
						'How will you abide instead of hurry-produce?',
						'<strong>Practice:</strong> Abide first; then act. <em>Jesus, I abide. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Watch and Be Ready (7 days)',
			'topic'     => 'return',
			'shareable' => false,
			'excerpt'   => 'A second return plan—watchfulness, readiness, and living for the appearing of Christ.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Watch therefore',
					'verse_ref' => 'Matthew 24:42',
					'body'      => hwbl_plan_day_body(
						'Watchfulness is the posture of hope.',
						'“Therefore, stay awake, for you do not know on what day your Lord is coming.”',
						'How awake is your hope?',
						'<strong>Practice:</strong> Begin the day asking, “Am I ready?” <em>Lord, keep me awake. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Be ready',
					'verse_ref' => 'Luke 12:40',
					'body'      => hwbl_plan_day_body(
						'Readiness shapes ordinary faithfulness.',
						'“You also must be ready, for the Son of Man is coming at an hour you do not expect.”',
						'What unfinished obedience needs attention?',
						'<strong>Practice:</strong> Obey one delayed command. <em>Son of Man, make me ready. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Our citizenship is in heaven',
					'verse_ref' => 'Philippians 3:20',
					'body'      => hwbl_plan_day_body(
						'Heavenly citizenship reorders earthly urgency.',
						'“But our citizenship is in heaven, and from it we await a Savior, the Lord Jesus Christ.”',
						'Where has earth crowded out awaiting?',
						'<strong>Practice:</strong> Pray as a citizen awaiting the Savior. <em>Jesus, I await You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Encourage one another',
					'verse_ref' => '1 Thessalonians 4:16-18',
					'body'      => hwbl_plan_day_body(
						'The Lord’s return is comfort for the grieving church.',
						'“And the dead in Christ will rise first… Therefore encourage one another with these words.”',
						'Whom can you encourage with this hope?',
						'<strong>Practice:</strong> Share resurrection hope with someone. <em>Lord, use me to encourage. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Purifies himself',
					'verse_ref' => '1 John 3:2-3',
					'body'      => hwbl_plan_day_body(
						'Hope purifies present living.',
						'“…when he appears we shall be like him… And everyone who thus hopes in him purifies himself as he is pure.”',
						'What impurity does hope ask you to put away?',
						'<strong>Practice:</strong> Purify one habit in hope of His appearing. <em>Lord, purify me as I hope. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Occupy until',
					'verse_ref' => 'Luke 19:13',
					'body'      => hwbl_plan_day_body(
						'Waiting is working faithfully with what He entrusted.',
						'“Calling ten of his servants, he gave them ten minas, and said to them, ‘Engage in business until I come.’”',
						'What mina are you neglecting?',
						'<strong>Practice:</strong> Invest one gift for the King today. <em>King Jesus, I work until You come. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Amen. Come, Lord Jesus',
					'verse_ref' => 'Revelation 22:20',
					'body'      => hwbl_plan_day_body(
						'The church’s last prayer is Come.',
						'“He who testifies to these things says, ‘Surely I am coming soon.’ Amen. Come, Lord Jesus!”',
						'Can you pray Come with desire?',
						'<strong>Practice:</strong> Pray Revelation 22:20 slowly. <em>Amen. Come, Lord Jesus. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'From Cross to Commission (7 days)',
			'topic'     => 'chronological',
			'shareable' => false,
			'excerpt'   => 'A second big-story plan—from the cross through resurrection to the church’s commission.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'It is finished',
					'verse_ref' => 'John 19:30',
					'body'      => hwbl_plan_day_body(
						'The cross completes redemption’s work.',
						'“When Jesus had received the sour wine, he said, ‘It is finished,’ and he bowed his head and gave up his spirit.”',
						'How does finished work free you from self-saving?',
						'<strong>Practice:</strong> Rest in finished work, not unfinished guilt. <em>Jesus, it is finished—thank You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'He is not here',
					'verse_ref' => 'Luke 24:5-6',
					'body'      => hwbl_plan_day_body(
						'Resurrection announces life where death seemed final.',
						'“Why do you seek the living among the dead? He is not here, but has risen.”',
						'Where do you need resurrection hope?',
						'<strong>Practice:</strong> Thank God that Jesus is risen. <em>Risen Lord, You live. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Peace be with you',
					'verse_ref' => 'John 20:19-21',
					'body'      => hwbl_plan_day_body(
						'Risen Jesus speaks peace and sends disciples.',
						'“Peace be with you. As the Father has sent me, even so I am sending you.”',
						'How does His peace empower your sending?',
						'<strong>Practice:</strong> Receive peace, then take one sent step. <em>Jesus, send me in Your peace. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Go therefore',
					'verse_ref' => 'Matthew 28:18-20',
					'body'      => hwbl_plan_day_body(
						'All authority commissions disciple-making.',
						'“All authority in heaven and on earth has been given to me. Go therefore and make disciples of all nations…”',
						'What is your next disciple-making step?',
						'<strong>Practice:</strong> Invite someone to follow Jesus with you. <em>Lord of all authority, I will go. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'You will be my witnesses',
					'verse_ref' => 'Acts 1:8',
					'body'      => hwbl_plan_day_body(
						'The Spirit powers witness from near to far.',
						'“But you will receive power when the Holy Spirit has come upon you, and you will be my witnesses…”',
						'Where is your Jerusalem—near witness field?',
						'<strong>Practice:</strong> Witness in your near place today. <em>Holy Spirit, empower my witness. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'They devoted themselves',
					'verse_ref' => 'Acts 2:42',
					'body'      => hwbl_plan_day_body(
						'The church takes shape in devoted common life.',
						'“And they devoted themselves to the apostles’ teaching and the fellowship, to the breaking of bread and the prayers.”',
						'Which devotion is thin for you?',
						'<strong>Practice:</strong> Strengthen one Acts 2 devotion. <em>Lord, make me a devoted disciple. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'To the end of the earth',
					'verse_ref' => 'Acts 1:8',
					'body'      => hwbl_plan_day_body(
						'The story moves outward until He returns.',
						'“…and you will be my witnesses in Jerusalem and in all Judea and Samaria, and to the end of the earth.”',
						'How can you join the outward story this week?',
						'<strong>Practice:</strong> Pray for and support one far witness. <em>Lord, to the end of the earth—use me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Come and See (7 days)',
			'topic'     => 'gospel',
			'shareable' => true,
			'excerpt'   => 'A shareable second gospel walk—come and see Jesus, the cross, and the call to believe.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Come and see',
					'verse_ref' => 'John 1:46',
					'body'      => hwbl_plan_day_body(
						'Faith often begins with an invitation to come and see.',
						'“Nathanael said to him, ‘Can anything good come out of Nazareth?’ Philip said to him, ‘Come and see.’”',
						'Whom can you invite to come and see Jesus?',
						'<strong>Practice:</strong> Invite someone to read a Gospel with you. <em>Jesus, help me say come and see. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'All have sinned',
					'verse_ref' => 'Romans 3:23',
					'body'      => hwbl_plan_day_body(
						'The gospel tells the truth about our need.',
						'“For all have sinned and fall short of the glory of God.”',
						'How does this level the ground at the cross?',
						'<strong>Practice:</strong> Confess your need without comparison. <em>God, I have sinned and need You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'The wages of sin',
					'verse_ref' => 'Romans 6:23',
					'body'      => hwbl_plan_day_body(
						'Sin’s wage is death; God’s gift is life.',
						'“For the wages of sin is death, but the free gift of God is eternal life in Christ Jesus our Lord.”',
						'Are you trying to earn what God gives freely?',
						'<strong>Practice:</strong> Thank God for the free gift. <em>God, thank You for eternal life in Christ. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Christ died for us',
					'verse_ref' => 'Romans 5:8',
					'body'      => hwbl_plan_day_body(
						'Love is demonstrated at the cross.',
						'“But God shows his love for us in that while we were still sinners, Christ died for us.”',
						'How does “while we were still sinners” land on you?',
						'<strong>Practice:</strong> Receive love you did not earn. <em>Jesus, thank You for dying for me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Confess and believe',
					'verse_ref' => 'Romans 10:9',
					'body'      => hwbl_plan_day_body(
						'Salvation is confessing Jesus as Lord and believing God raised Him.',
						'“…if you confess with your mouth that Jesus is Lord and believe in your heart that God raised him from the dead, you will be saved.”',
						'Have you confessed and believed?',
						'<strong>Practice:</strong> Confess Jesus as Lord aloud. <em>Jesus, You are Lord; I believe. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'By grace through faith',
					'verse_ref' => 'Ephesians 2:8-9',
					'body'      => hwbl_plan_day_body(
						'Salvation is gift, not boast.',
						'“For by grace you have been saved through faith. And this is not your own doing; it is the gift of God, not a result of works, so that no one may boast.”',
						'Where do you still boast in works?',
						'<strong>Practice:</strong> Rest in grace; refuse boasting. <em>God, I receive salvation as gift. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Go and make disciples',
					'verse_ref' => 'Matthew 28:19',
					'body'      => hwbl_plan_day_body(
						'Saved people join the sending.',
						'“Go therefore and make disciples of all nations…”',
						'What is your next gospel step toward someone?',
						'<strong>Practice:</strong> Share the gospel or this plan link with one person. <em>Lord, send me with Your gospel. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Built on the Rock (7 days)',
			'topic'     => 'foundations',
			'shareable' => false,
			'excerpt'   => 'A second foundations plan—built on the rock, core truths of faith, and a life that hears and does.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Built on the rock',
					'verse_ref' => 'Matthew 7:24-25',
					'body'      => hwbl_plan_day_body(
						'Hearing and doing builds on rock.',
						'“Everyone then who hears these words of mine and does them will be like a wise man who built his house on the rock.”',
						'Where are you hearing without doing?',
						'<strong>Practice:</strong> Do one word you already know. <em>Jesus, build me on the rock. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The gospel of first importance',
					'verse_ref' => '1 Corinthians 15:3-4',
					'body'      => hwbl_plan_day_body(
						'Foundations center on Christ’s death and resurrection.',
						'“For I delivered to you as of first importance what I also received: that Christ died for our sins… that he was buried, that he was raised on the third day…”',
						'Is the gospel first importance in your mind?',
						'<strong>Practice:</strong> Write the gospel in your own words. <em>Lord, keep the gospel first. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'There is one God',
					'verse_ref' => 'Deuteronomy 6:4-5',
					'body'      => hwbl_plan_day_body(
						'Love the Lord with all—foundation of covenant life.',
						'“Hear, O Israel: The Lord our God, the Lord is one. You shall love the Lord your God with all your heart…”',
						'What rival love fragments your all?',
						'<strong>Practice:</strong> Love God with one undivided act today. <em>Lord, You are one—I love You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'All Scripture',
					'verse_ref' => '2 Timothy 3:16',
					'body'      => hwbl_plan_day_body(
						'Scripture equips the foundation.',
						'“All Scripture is breathed out by God and profitable… that the man of God may be complete, equipped for every good work.”',
						'How is Scripture equipping you this week?',
						'<strong>Practice:</strong> Read for equipping, then obey. <em>God, equip me by Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Saved by grace',
					'verse_ref' => 'Ephesians 2:8-9',
					'body'      => hwbl_plan_day_body(
						'Grace foundation kills boasting.',
						'“For by grace you have been saved through faith… not a result of works, so that no one may boast.”',
						'Where does boasting still sneak in?',
						'<strong>Practice:</strong> Thank God for grace-only salvation. <em>God, I boast only in Your grace. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Baptized into Christ',
					'verse_ref' => 'Romans 6:3-4',
					'body'      => hwbl_plan_day_body(
						'Union with Christ is foundational identity.',
						'“Do you not know that all of us who have been baptized into Christ Jesus were baptized into his death?… we too might walk in newness of life.”',
						'How does newness of life show today?',
						'<strong>Practice:</strong> Walk in one newness habit. <em>Christ, I walk in newness with You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The church, His body',
					'verse_ref' => 'Ephesians 1:22-23',
					'body'      => hwbl_plan_day_body(
						'Foundations include belonging to Christ’s body.',
						'“And he put all things under his feet and gave him as head over all things to the church, which is his body…”',
						'Are you connected to the body?',
						'<strong>Practice:</strong> Engage your local church intentionally. <em>Head of the church, plant me in Your body. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Waiting for the Light (5 days)',
			'topic'     => 'advent',
			'shareable' => false,
			'excerpt'   => 'A second Advent walk—waiting, light in darkness, and hope for Christ’s coming.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'The people who walked in darkness',
					'verse_ref' => 'Isaiah 9:2',
					'body'      => hwbl_plan_day_body(
						'Advent hope dawns on people who walked in darkness.',
						'“The people who walked in darkness have seen a great light…”',
						'Where do you need great light?',
						'<strong>Practice:</strong> Light a candle and pray for Christ’s light. <em>Lord, shine on my darkness. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Behold, the virgin shall conceive',
					'verse_ref' => 'Isaiah 7:14',
					'body'      => hwbl_plan_day_body(
						'God with us is Advent’s promise.',
						'“Behold, the virgin shall conceive and bear a son, and shall call his name Immanuel.”',
						'How does Immanuel meet your waiting?',
						'<strong>Practice:</strong> Pray, “God with us,” over one fear. <em>Immanuel, be with me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'My soul waits',
					'verse_ref' => 'Psalm 130:5-6',
					'body'      => hwbl_plan_day_body(
						'Advent waiting watches for the Lord.',
						'“I wait for the Lord, my soul waits, and in his word I hope…”',
						'What are you waiting for—and is your hope in His word?',
						'<strong>Practice:</strong> Read one promise and wait quietly. <em>Lord, my soul waits for You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'The Word became flesh',
					'verse_ref' => 'John 1:14',
					'body'      => hwbl_plan_day_body(
						'The Light took on flesh and dwelt among us.',
						'“And the Word became flesh and dwelt among us, and we have seen his glory…”',
						'Will you make room for the Word made flesh?',
						'<strong>Practice:</strong> Worship Jesus’ incarnation today. <em>Word made flesh, I worship You. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Come, Lord Jesus',
					'verse_ref' => 'Revelation 22:20',
					'body'      => hwbl_plan_day_body(
						'Advent looks back to Bethlehem and forward to His return.',
						'“He who testifies to these things says, ‘Surely I am coming soon.’ Amen. Come, Lord Jesus!”',
						'Can you pray Come with desire?',
						'<strong>Practice:</strong> Pray Come, Lord Jesus. <em>Amen. Come, Lord Jesus. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Journey to the Cross (5 days)',
			'topic'     => 'lent',
			'shareable' => false,
			'excerpt'   => 'A second Lent walk—repentance, cross-bearing, and journeying with Jesus to the cross.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Return to Me',
					'verse_ref' => 'Joel 2:12-13',
					'body'      => hwbl_plan_day_body(
						'Lent begins with returning to God with all the heart.',
						'“‘Yet even now,’ declares the Lord, ‘return to me with all your heart…’ Rend your hearts and not your garments.”',
						'What return is God asking of you?',
						'<strong>Practice:</strong> Rend your heart in honest confession. <em>Lord, I return to You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Take up his cross',
					'verse_ref' => 'Luke 9:23',
					'body'      => hwbl_plan_day_body(
						'Lent practices daily cross-bearing.',
						'“If anyone would come after me, let him deny himself and take up his cross daily and follow me.”',
						'What self-denial marks this Lent day?',
						'<strong>Practice:</strong> Deny one preference to follow Jesus. <em>Jesus, I take up my cross. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Create in me a clean heart',
					'verse_ref' => 'Psalm 51:10',
					'body'      => hwbl_plan_day_body(
						'Repentance asks for a created clean heart.',
						'“Create in me a clean heart, O God, and renew a right spirit within me.”',
						'Where do you need creating, not just trying harder?',
						'<strong>Practice:</strong> Pray Psalm 51:10 twice. <em>O God, create in me a clean heart. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'He humbled himself',
					'verse_ref' => 'Philippians 2:8',
					'body'      => hwbl_plan_day_body(
						'The cross is the Son’s humble obedience.',
						'“And being found in human form, he humbled himself by becoming obedient to the point of death, even death on a cross.”',
						'How does His humility reshape yours?',
						'<strong>Practice:</strong> Choose one humble obedience. <em>Jesus, I follow Your humility. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'It is finished',
					'verse_ref' => 'John 19:30',
					'body'      => hwbl_plan_day_body(
						'Lent leads to finished redemption.',
						'“When Jesus had received the sour wine, he said, ‘It is finished’…”',
						'Are you still trying to finish what He finished?',
						'<strong>Practice:</strong> Rest in finished work at the cross. <em>Jesus, it is finished—thank You. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'More Bible Stories for Kids (7 days)',
			'topic'     => 'kids',
			'shareable' => false,
			'excerpt'   => 'More short Bible stories for kids—creation, Noah, David, Jesus, and following Him with joy.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God made everything',
					'verse_ref' => 'Genesis 1:1',
					'body'      => hwbl_plan_day_body(
						'God made the world—and He made you on purpose.',
						'“In the beginning, God created the heavens and the earth.”',
						'What is one thing God made that you love?',
						'<strong>Practice:</strong> Thank God for three created things. <em>God, thank You for making the world. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Noah trusted God',
					'verse_ref' => 'Genesis 7:1',
					'body'      => hwbl_plan_day_body(
						'Noah listened when God told him what to do.',
						'“Then the Lord said to Noah, ‘Go into the ark, you and all your household…’”',
						'When is it hard to obey right away?',
						'<strong>Practice:</strong> Practice quick obedience once today. <em>God, help me trust and obey like Noah. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'David and Goliath',
					'verse_ref' => '1 Samuel 17:45',
					'body'      => hwbl_plan_day_body(
						'David was brave because God was with him.',
						'“Then David said to the Philistine, ‘You come to me with a sword… but I come to you in the name of the Lord of hosts…’”',
						'What “giant” feels big to you?',
						'<strong>Practice:</strong> Pray about that giant with a grown-up. <em>Lord, You are bigger than my giant. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Jesus loves children',
					'verse_ref' => 'Mark 10:14',
					'body'      => hwbl_plan_day_body(
						'Jesus wants children to come to Him.',
						'“Let the children come to me; do not hinder them, for to such belongs the kingdom of God.”',
						'How does it feel that Jesus wants you near?',
						'<strong>Practice:</strong> Tell Jesus you come to Him. <em>Jesus, I come to You. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Jesus calms the storm',
					'verse_ref' => 'Mark 4:39',
					'body'      => hwbl_plan_day_body(
						'Jesus is stronger than scary storms.',
						'“And he awoke and rebuked the wind and said to the sea, ‘Peace! Be still!’”',
						'What makes you feel stormy inside?',
						'<strong>Practice:</strong> Ask Jesus for peace when you feel scared. <em>Jesus, peace—be still. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'The lost sheep',
					'verse_ref' => 'Luke 15:4-6',
					'body'      => hwbl_plan_day_body(
						'Jesus looks for people who feel lost.',
						'“What man of you, having a hundred sheep, if he has lost one of them, does not leave the ninety-nine… and go after the one that is lost…?”',
						'How does it feel that Jesus looks for people?',
						'<strong>Practice:</strong> Thank Jesus for finding you. <em>Jesus, thank You for looking for me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Go and tell',
					'verse_ref' => 'Matthew 28:19-20',
					'body'      => hwbl_plan_day_body(
						'Jesus’ friends tell others about Him.',
						'“Go therefore and make disciples of all nations… And behold, I am with you always…”',
						'Whom can you tell that Jesus loves them?',
						'<strong>Practice:</strong> Tell one person Jesus loves them. <em>Jesus, help me tell others about You. Amen.</em>'
					),
				),
			),
		),
	);
}
