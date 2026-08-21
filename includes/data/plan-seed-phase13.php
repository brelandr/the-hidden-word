<?php
/**
 * Phase 13 plan definitions: friendship, caregiving, hospitality, mercy, etc.
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
 * Phase 13 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase13_definitions() {
	return array(
		array(
			'title'     => 'Faithful Friends (7 days)',
			'topic'     => 'friendship',
			'shareable' => false,
			'excerpt'   => 'Seven days on friendship in Christ—loyalty, honesty, shared burdens, and friends who sharpen one another.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'A friend loves at all times',
					'verse_ref' => 'Proverbs 17:17',
					'body'      => hwbl_plan_day_body(
						'Friendship thins under convenience. Scripture names love that stays.',
						'“A friend loves at all times, and a brother is born for adversity.” True friendship shows up when schedules hurt.',
						'Who has loved you at all times—and whom have you left in adversity?',
						'<strong>Practice:</strong> Reach out to one friend in a hard season today. <em>Lord, make me a friend who stays. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Iron sharpens iron',
					'verse_ref' => 'Proverbs 27:17',
					'body'      => hwbl_plan_day_body(
						'Flattery feels kind; sharpening forms character.',
						'“Iron sharpens iron, and one man sharpens another.” Friendship that never challenges is incomplete.',
						'Do your closest friendships sharpen—or only soothe?',
						'<strong>Practice:</strong> Invite one honest sharpening conversation this week. <em>Lord, give friends who sharpen me in love. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Greater love',
					'verse_ref' => 'John 15:13-15',
					'body'      => hwbl_plan_day_body(
						'Jesus calls disciples friends—and defines friendship by laid-down life.',
						'“Greater love has no one than this, that someone lay down his life for his friends… I have called you friends.” Friendship is costly knowing and loving.',
						'Where can you lay down preference for a friend’s good?',
						'<strong>Practice:</strong> Sacrifice time or comfort for one friend today. <em>Jesus, teach me friendship like Yours. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Two are better than one',
					'verse_ref' => 'Ecclesiastes 4:9-10',
					'body'      => hwbl_plan_day_body(
						'Isolation multiplies falls. Friendship multiplies strength.',
						'“Two are better than one… For if they fall, one will lift up his fellow.” God designed mutual help.',
						'Where are you pretending you do not need a lift?',
						'<strong>Practice:</strong> Ask a friend for help—or offer to lift someone who fell. <em>Lord, weave me into faithful companionship. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Wounds of a friend',
					'verse_ref' => 'Proverbs 27:6',
					'body'      => hwbl_plan_day_body(
						'Enemy kisses flatter; friend wounds can heal.',
						'“Faithful are the wounds of a friend; profuse are the kisses of an enemy.” Truth in love is a gift.',
						'Have you rejected faithful wounds—or delivered cruel ones?',
						'<strong>Practice:</strong> Receive or give one gentle, truthful word for growth. <em>Lord, make my wounds faithful and my ears humble. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Jonathan and David',
					'verse_ref' => '1 Samuel 18:3-4',
					'body'      => hwbl_plan_day_body(
						'Covenant friendship chooses loyalty over self-advantage.',
						'“Then Jonathan made a covenant with David, because he loved him as his own soul. And Jonathan stripped himself of the robe that was on him and gave it to David…” Love yields status.',
						'What status or advantage are you clinging to instead of a friend?',
						'<strong>Practice:</strong> Give credit, access, or help without keeping score. <em>Lord, form covenant loyalty in me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Encourage one another',
					'verse_ref' => '1 Thessalonians 5:11',
					'body'      => hwbl_plan_day_body(
						'Friendship in Christ builds; it does not drain or compete.',
						'“Therefore encourage one another and build one another up, just as you are doing.” Encouragement is daily construction.',
						'Whose faith could your words strengthen today?',
						'<strong>Practice:</strong> Send one specific encouragement (not vague flattery). <em>Lord, make me a builder of friends. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Strength for Caregivers (7 days)',
			'topic'     => 'caregiving',
			'shareable' => false,
			'excerpt'   => 'Seven days for those who care for others—receiving God’s strength, asking for help, and remembering you are not the Savior.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Cast your burden',
					'verse_ref' => 'Psalm 55:22',
					'body'      => hwbl_plan_day_body(
						'Caregiving loads the soul. God invites casting, not endless carrying alone.',
						'“Cast your burden on the Lord, and he will sustain you; he will never permit the righteous to be moved.” Sustain is His verb.',
						'What burden are you still clutching as if God will not sustain?',
						'<strong>Practice:</strong> Name three burdens and cast them in prayer. <em>Lord, I cast this care on You—sustain me. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Come away and rest',
					'verse_ref' => 'Mark 6:31',
					'body'      => hwbl_plan_day_body(
						'Even ministry can crush without rest. Jesus sees the need.',
						'“And he said to them, ‘Come away by yourselves to a desolate place and rest a while.’” Rest is obedience, not luxury.',
						'Where have you treated rest as betrayal of the one you care for?',
						'<strong>Practice:</strong> Take one real rest break today without guilt. <em>Jesus, call me away to rest a while. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Bear one another’s burdens',
					'verse_ref' => 'Galatians 6:2',
					'body'      => hwbl_plan_day_body(
						'Caregivers often refuse the help they give others.',
						'“Bear one another’s burdens, and so fulfill the law of Christ.” Receiving help fulfills the law too.',
						'Whom could you allow to bear part of your load?',
						'<strong>Practice:</strong> Ask one specific person for one specific help. <em>Lord, humble me to receive burden-bearing. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'My grace is sufficient',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'You will not feel strong enough every day. Grace is enough for weakness.',
						'“My grace is sufficient for you, for my power is made perfect in weakness.” Caregiving weakness can showcase His power.',
						'Where are you demanding strength God is replacing with grace?',
						'<strong>Practice:</strong> Admit one weakness to God and ask only for sufficient grace. <em>Lord, Your grace is enough for this day. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Do not grow weary',
					'verse_ref' => 'Galatians 6:9',
					'body'      => hwbl_plan_day_body(
						'Long care seasons tempt quitting the good. Harvest has a due season.',
						'“And let us not grow weary of doing good, for in due season we will reap, if we do not give up.”',
						'What “good” caregiving step feels wearisome today?',
						'<strong>Practice:</strong> Do the next faithful care act; leave harvest timing to God. <em>Lord, keep me from quitting the good. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'As you did it to one of the least',
					'verse_ref' => 'Matthew 25:40',
					'body'      => hwbl_plan_day_body(
						'Hidden care is seen by Christ. Serving the vulnerable is serving Him.',
						'“Truly, I say to you, as you did it to one of the least of these my brothers, you did it to me.”',
						'How does seeing Jesus in the one you serve change today’s tone?',
						'<strong>Practice:</strong> Serve one task today as unto Christ. <em>Jesus, I serve You in this care. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He cares for you',
					'verse_ref' => '1 Peter 5:7',
					'body'      => hwbl_plan_day_body(
						'You are not only a caregiver; you are cared for.',
						'“Casting all your anxieties on him, because he cares for you.” His care for you is personal.',
						'Have you forgotten that God cares for the caregiver?',
						'<strong>Practice:</strong> Receive care—prayer, rest, or a friend’s help—as God’s attention to you. <em>Father, You care for me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Open Door Hospitality (7 days)',
			'topic'     => 'hospitality',
			'shareable' => false,
			'excerpt'   => 'Seven days on biblical hospitality—welcoming strangers and saints without performance anxiety.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Do not neglect to show hospitality',
					'verse_ref' => 'Hebrews 13:2',
					'body'      => hwbl_plan_day_body(
						'Hospitality is not Pinterest; it is gospel welcome.',
						'“Do not neglect to show hospitality to strangers, for thereby some have entertained angels unawares.” Neglect is the warning.',
						'Where has hospitality been neglected in your life?',
						'<strong>Practice:</strong> Invite or include one person this week—simple is fine. <em>Lord, reopen my door in love. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Seek to show hospitality',
					'verse_ref' => 'Romans 12:13',
					'body'      => hwbl_plan_day_body(
						'Hospitality is pursued, not accidental.',
						'“Contribute to the needs of the saints and seek to show hospitality.” Seeking means initiative.',
						'Are you waiting to feel ready before you seek to welcome?',
						'<strong>Practice:</strong> Text one invitation today. <em>Lord, help me seek hospitality. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Welcome one another',
					'verse_ref' => 'Romans 15:7',
					'body'      => hwbl_plan_day_body(
						'Christ’s welcome is the pattern and power.',
						'“Therefore welcome one another as Christ has welcomed you, for the glory of God.”',
						'Whom do you welcome easily—and whom do you quietly exclude?',
						'<strong>Practice:</strong> Welcome someone outside your usual circle. <em>Christ, let Your welcome shape mine. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Without grumbling',
					'verse_ref' => '1 Peter 4:9',
					'body'      => hwbl_plan_day_body(
						'Hospitality dies under resentment. Peter names the danger.',
						'“Show hospitality to one another without grumbling.” Cheerful welcome glorifies God; grudging welcome drains everyone.',
						'What grumble rises when you think of hosting?',
						'<strong>Practice:</strong> Host or help with thanksgiving instead of complaint. <em>Lord, remove grumbling from my welcome. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'A cup of cold water',
					'verse_ref' => 'Matthew 10:42',
					'body'      => hwbl_plan_day_body(
						'Small welcomes matter. Jesus honors ordinary kindness.',
						'“And whoever gives one of these little ones even a cup of cold water because he is a disciple, truly, I say to you, he will by no means lose his reward.”',
						'What “cup of cold water” can you give this week?',
						'<strong>Practice:</strong> Offer one small concrete kindness without fanfare. <em>Jesus, receive this small welcome. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Share your bread',
					'verse_ref' => 'Isaiah 58:7',
					'body'      => hwbl_plan_day_body(
						'True fasting and faith include shared tables and shelter.',
						'“Is it not to share your bread with the hungry and bring the homeless poor into your house…?” Hospitality meets real need.',
						'How can your table or resources meet one need?',
						'<strong>Practice:</strong> Share a meal or grocery help with someone. <em>Lord, open my hands and my table. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Martha and Mary',
					'verse_ref' => 'Luke 10:41-42',
					'body'      => hwbl_plan_day_body(
						'Hospitality can become anxious performance. Jesus redirects Martha to the better portion.',
						'“Martha, Martha, you are anxious and troubled about many things, but one thing is necessary.” Presence with Jesus fuels presence with guests.',
						'Has hosting replaced sitting with the Lord?',
						'<strong>Practice:</strong> Simplify one plan and put presence over perfection. <em>Jesus, free me from anxious hospitality. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Mercy Like the Father (7 days)',
			'topic'     => 'mercy',
			'shareable' => false,
			'excerpt'   => 'Seven days on mercy—receiving God’s compassion and extending it to the needy, the weak, and the undeserving.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Be merciful',
					'verse_ref' => 'Luke 6:36',
					'body'      => hwbl_plan_day_body(
						'Mercy is not optional personality; it is family resemblance.',
						'“Be merciful, even as your Father is merciful.” We mirror the Father’s heart.',
						'Where has hardness replaced mercy in you?',
						'<strong>Practice:</strong> Choose one merciful act toward someone who cannot repay. <em>Father, form Your mercy in me. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Blessed are the merciful',
					'verse_ref' => 'Matthew 5:7',
					'body'      => hwbl_plan_day_body(
						'Mercy given and mercy received are linked.',
						'“Blessed are the merciful, for they shall receive mercy.” Hard hearts block the flow they need.',
						'Are you withholding the mercy you hope to receive?',
						'<strong>Practice:</strong> Release one harsh judgment in prayer. <em>Lord, make me merciful and open to mercy. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'I desire mercy',
					'verse_ref' => 'Matthew 9:13',
					'body'      => hwbl_plan_day_body(
						'Religion without mercy misses God’s desire.',
						'“Go and learn what this means: ‘I desire mercy, and not sacrifice.’ For I came not to call the righteous, but sinners.”',
						'Where do you prefer religious appearance over merciful action?',
						'<strong>Practice:</strong> Move toward one “sinner” with welcome, not superiority. <em>Jesus, teach me that You desire mercy. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'The Good Samaritan',
					'verse_ref' => 'Luke 10:33-34',
					'body'      => hwbl_plan_day_body(
						'Mercy crosses the road and spends itself.',
						'“But a Samaritan… had compassion… and bound up his wounds… and took care of him.” Compassion acts.',
						'Whom have you passed by?',
						'<strong>Practice:</strong> Cross toward one inconvenient need today. <em>Lord, give Samaritan compassion. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Rich in mercy',
					'verse_ref' => 'Ephesians 2:4-5',
					'body'      => hwbl_plan_day_body(
						'Our mercy overflows from God’s rich mercy to us in Christ.',
						'“But God, being rich in mercy, because of the great love with which he loved us, even when we were dead in our trespasses, made us alive…”',
						'Have you forgotten how much mercy you received?',
						'<strong>Practice:</strong> Thank God for specific mercies, then pass one on. <em>God rich in mercy, thank You—make me merciful. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Mercy triumphs',
					'verse_ref' => 'James 2:13',
					'body'      => hwbl_plan_day_body(
						'Judgment without mercy boomerangs. Mercy triumphs.',
						'“For judgment is without mercy to one who has shown no mercy. Mercy triumphs over judgment.”',
						'Where is judgment winning in your speech?',
						'<strong>Practice:</strong> Replace one judgmental comment with merciful truth. <em>Lord, let mercy triumph in me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Love kindness',
					'verse_ref' => 'Micah 6:8',
					'body'      => hwbl_plan_day_body(
						'God’s requirement includes loving kindness—not merely doing duty.',
						'“He has told you, O man, what is good… to do justice, and to love kindness, and to walk humbly with your God.”',
						'Do you love kindness—or only tolerate it?',
						'<strong>Practice:</strong> Do one kind act with gladness, not resentment. <em>Lord, teach me to love kindness. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Servant Leadership (7 days)',
			'topic'     => 'leadership',
			'shareable' => false,
			'excerpt'   => 'Seven days for leaders at home, church, or work—leading like Jesus with towels, truth, and humble courage.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Not to be served',
					'verse_ref' => 'Mark 10:43-45',
					'body'      => hwbl_plan_day_body(
						'Worldly leadership climbs; Jesus washes.',
						'“Whoever would be great among you must be your servant… For even the Son of Man came not to be served but to serve, and to give his life as a ransom for many.”',
						'Where are you leading to be served?',
						'<strong>Practice:</strong> Do one unnoticed servant task for those you lead. <em>Jesus, make me a servant leader. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Shepherd the flock',
					'verse_ref' => '1 Peter 5:2-3',
					'body'      => hwbl_plan_day_body(
						'Shepherding is willing care, not domineering.',
						'“Shepherd the flock of God that is among you… not domineering over those in your charge, but being examples to the flock.”',
						'Do people experience your leadership as example—or control?',
						'<strong>Practice:</strong> Lead by example in one area you usually only instruct. <em>Chief Shepherd, form gentle courage in me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Wisdom from above',
					'verse_ref' => 'James 3:17',
					'body'      => hwbl_plan_day_body(
						'Leaders need wisdom that is pure, peaceable, and open to reason.',
						'“But the wisdom from above is first pure, then peaceable, gentle, open to reason, full of mercy and good fruits…”',
						'Is your leadership open to reason—or only to agreement?',
						'<strong>Practice:</strong> Invite one corrective perspective before a decision. <em>Lord, give wisdom from above. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Speak the truth in love',
					'verse_ref' => 'Ephesians 4:15',
					'body'      => hwbl_plan_day_body(
						'Leaders who only soothe leave people immature; leaders who only scold wound.',
						'“Speaking the truth in love, we are to grow up in every way into him who is the head, into Christ.”',
						'Which side do you overuse—truth without love, or love without truth?',
						'<strong>Practice:</strong> Have one honest, loving conversation for growth. <em>Lord, help me speak truth in love. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Take heed to yourselves',
					'verse_ref' => 'Acts 20:28',
					'body'      => hwbl_plan_day_body(
						'Leaders can neglect their own souls while watching others.',
						'“Pay careful attention to yourselves and to all the flock, in which the Holy Spirit has made you overseers…” Self first is not selfish—it is stewardship.',
						'How is your own walk with God while you lead others?',
						'<strong>Practice:</strong> Guard one personal devotion time this week as non-negotiable. <em>Spirit, help me attend to myself and the flock. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Not lording it over',
					'verse_ref' => 'Matthew 20:25-26',
					'body'      => hwbl_plan_day_body(
						'Gentile rulers lord power. Jesus forbids that pattern among His people.',
						'“You know that the rulers of the Gentiles lord it over them… It shall not be so among you.”',
						'Where does lording show up in your tone or decisions?',
						'<strong>Practice:</strong> Yield one preference and explain the why with humility. <em>Lord, it shall not be so among us. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Commit your work',
					'verse_ref' => 'Proverbs 16:3',
					'body'      => hwbl_plan_day_body(
						'Anxious leaders clutch outcomes. Wisdom commits work to the Lord.',
						'“Commit your work to the Lord, and your plans will be established.” Establishment is His gift.',
						'What leadership outcome are you clutching?',
						'<strong>Practice:</strong> Commit today’s leadership work to God before acting. <em>Lord, I commit this work to You. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Purity of Heart (7 days)',
			'topic'     => 'purity',
			'shareable' => false,
			'excerpt'   => 'Seven days on purity—heart, eyes, body, and speech set apart for God in a world of easy compromise.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Blessed are the pure in heart',
					'verse_ref' => 'Matthew 5:8',
					'body'      => hwbl_plan_day_body(
						'Purity begins in the heart, not only in public reputation.',
						'“Blessed are the pure in heart, for they shall see God.” Divided hearts blur vision of God.',
						'Where is your heart mixed—wanting God and wanting sin?',
						'<strong>Practice:</strong> Confess one divided desire and ask for a clean heart. <em>Lord, purify my heart to see You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Flee youthful passions',
					'verse_ref' => '2 Timothy 2:22',
					'body'      => hwbl_plan_day_body(
						'Some battles are won by fleeing and pursuing together.',
						'“So flee youthful passions and pursue righteousness, faith, love, and peace, along with those who call on the Lord from a pure heart.”',
						'What should you flee—and with whom will you pursue holiness?',
						'<strong>Practice:</strong> Cut one access point to temptation; tell a trusted believer. <em>Lord, I flee and I pursue with Your people. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'I have made a covenant with my eyes',
					'verse_ref' => 'Job 31:1',
					'body'      => hwbl_plan_day_body(
						'Eyes lead hearts. Job models intentional covenant.',
						'“I have made a covenant with my eyes; how then could I gaze at a virgin?” Purity plans ahead.',
						'What covenant do your eyes need?',
						'<strong>Practice:</strong> Set one concrete eye-guard (filters, habits, accountability). <em>Lord, covenant with my eyes for purity. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Your body is a temple',
					'verse_ref' => '1 Corinthians 6:19-20',
					'body'      => hwbl_plan_day_body(
						'The body is not private property for sin; it is bought with a price.',
						'“Or do you not know that your body is a temple of the Holy Spirit within you… You are not your own, for you were bought with a price. So glorify God in your body.”',
						'How does “not your own” change today’s choices?',
						'<strong>Practice:</strong> Honor God with one bodily choice (rest, food, sexuality, speech). <em>Holy Spirit, this body is Yours. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Think on these things',
					'verse_ref' => 'Philippians 4:8',
					'body'      => hwbl_plan_day_body(
						'Purity of mind is cultivated by what we dwell on.',
						'“Whatever is true, whatever is honorable, whatever is just, whatever is pure… think about these things.”',
						'What impure loop needs replacing with Philippians 4:8?',
						'<strong>Practice:</strong> When a dark thought returns, speak one true/pure alternative aloud. <em>Lord, train my mind on what is pure. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Let no corrupting talk',
					'verse_ref' => 'Ephesians 4:29',
					'body'      => hwbl_plan_day_body(
						'Purity includes speech that builds rather than stains.',
						'“Let no corrupting talk come out of your mouths, but only such as is good for building up…”',
						'What talk pattern corrupts your witness?',
						'<strong>Practice:</strong> Replace one corrupting habit of speech with grace-giving words. <em>Lord, purify my tongue. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Create in me a clean heart',
					'verse_ref' => 'Psalm 51:10',
					'body'      => hwbl_plan_day_body(
						'Purity is gift and renewal, not self-salvation. David asks God to create.',
						'“Create in me a clean heart, O God, and renew a right spirit within me.”',
						'Are you scrubbing appearance—or asking God to create cleanness?',
						'<strong>Practice:</strong> Pray Psalm 51:10 slowly; receive cleansing in Christ. <em>Create in me a clean heart, O God. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Faith When Pressed (7 days)',
			'topic'     => 'persecution',
			'shareable' => false,
			'excerpt'   => 'Seven days for believers under pressure—insult, exclusion, or hostility—holding fast with courage and love.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Blessed when others revile you',
					'verse_ref' => 'Matthew 5:10-12',
					'body'      => hwbl_plan_day_body(
						'Pressure for righteousness is not failure; Jesus calls it blessing.',
						'“Blessed are those who are persecuted for righteousness’ sake… Rejoice and be glad, for your reward is great in heaven.”',
						'Have you treated gospel pushback as a sign to quit?',
						'<strong>Practice:</strong> Thank God for one costly stand you will keep. <em>Lord, help me rejoice under pressure for Your name. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Do not be surprised',
					'verse_ref' => '1 Peter 4:12-14',
					'body'      => hwbl_plan_day_body(
						'Fiery trials for Christ are not strange.',
						'“Beloved, do not be surprised at the fiery trial… But rejoice insofar as you share Christ’s sufferings…”',
						'Where has surprise turned into bitterness?',
						'<strong>Practice:</strong> Name the pressure to God without surprise-shame. <em>Lord, I share in Christ’s sufferings with hope. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'All who desire to live godly',
					'verse_ref' => '2 Timothy 3:12',
					'body'      => hwbl_plan_day_body(
						'Godliness attracts resistance in a broken world.',
						'“Indeed, all who desire to live a godly life in Christ Jesus will be persecuted.” Desire for godliness has a cost.',
						'Are you diluting godliness to avoid friction?',
						'<strong>Practice:</strong> Keep one godly practice others misunderstand. <em>Jesus, I desire a godly life in You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Love your enemies',
					'verse_ref' => 'Matthew 5:44',
					'body'      => hwbl_plan_day_body(
						'Pressure tempts revenge. Jesus commands enemy-love and prayer.',
						'“But I say to you, Love your enemies and pray for those who persecute you.”',
						'Whom do you need to pray for instead of rehearse against?',
						'<strong>Practice:</strong> Pray blessing over one hostile name. <em>Father, teach me to love my enemies. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'We cannot but speak',
					'verse_ref' => 'Acts 4:19-20',
					'body'      => hwbl_plan_day_body(
						'When ordered silent, the apostles chose obedience to God.',
						'“Whether it is right in the sight of God to listen to you rather than to God, you must judge, for we cannot but speak of what we have seen and heard.”',
						'Where is fear silencing what you have seen of Christ?',
						'<strong>Practice:</strong> Speak one true word about Jesus gently today. <em>Lord, I cannot but speak of You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not fear those who kill the body',
					'verse_ref' => 'Matthew 10:28',
					'body'      => hwbl_plan_day_body(
						'Fear of people shrinks witness. Jesus relocates fear to holy reverence.',
						'“And do not fear those who kill the body but cannot kill the soul. Rather fear him who can destroy both soul and body in hell.”',
						'Whose opinion has become bigger than God?',
						'<strong>Practice:</strong> Confess people-fear; ask for holy courage. <em>Lord, I fear You more than people. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The Lord is my helper',
					'verse_ref' => 'Hebrews 13:6',
					'body'      => hwbl_plan_day_body(
						'Courage rests on God’s help, not on thick skin.',
						'“So we can confidently say, ‘The Lord is my helper; I will not fear; what can man do to me?’”',
						'What human threat still owns your confidence?',
						'<strong>Practice:</strong> Speak Hebrews 13:6 aloud over today’s pressure. <em>Lord, You are my helper—I will not fear. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Free from Comparison (7 days)',
			'topic'     => 'envy',
			'shareable' => false,
			'excerpt'   => 'Seven days for freedom from envy and comparison—rejoicing with others and receiving your own calling with peace.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Envy makes the bones rot',
					'verse_ref' => 'Proverbs 14:30',
					'body'      => hwbl_plan_day_body(
						'Comparison feels motivating; Scripture says envy rots.',
						'“A tranquil heart gives life to the flesh, but envy makes the bones rot.”',
						'Whose life is rotting your joy by comparison?',
						'<strong>Practice:</strong> Bless that person by name without adding “why not me.” <em>Lord, replace envy with a tranquil heart. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Rejoice with those who rejoice',
					'verse_ref' => 'Romans 12:15',
					'body'      => hwbl_plan_day_body(
						'Envy withholds celebration. Love rejoices.',
						'“Rejoice with those who rejoice, weep with those who weep.” Shared joy kills comparison.',
						'Whose win can you celebrate today?',
						'<strong>Practice:</strong> Send a sincere congratulations. <em>Lord, teach me to rejoice with others. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'What is that to you?',
					'verse_ref' => 'John 21:21-22',
					'body'      => hwbl_plan_day_body(
						'Peter compares his path to John’s. Jesus redirects.',
						'“If it is my will that he remain until I come, what is that to you? You follow me!” Your call is to follow, not to audit another’s assignment.',
						'Whose path are you watching more than Jesus?',
						'<strong>Practice:</strong> Say aloud: “What is that to me? I will follow You.” <em>Jesus, I follow You—not their story. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Do not covet',
					'verse_ref' => 'Exodus 20:17',
					'body'      => hwbl_plan_day_body(
						'Coveting turns neighbors into inventories of what you lack.',
						'“You shall not covet… anything that is your neighbor’s.”',
						'What “anything” has your heart been cataloging?',
						'<strong>Practice:</strong> Fast from one comparison trigger (scroll, status check) for 24 hours. <em>Lord, free me from coveting. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Godliness with contentment',
					'verse_ref' => '1 Timothy 6:6',
					'body'      => hwbl_plan_day_body(
						'Gain is not getting ahead of others; it is godliness with contentment.',
						'“But godliness with contentment is great gain.”',
						'Where has “gain” meant beating someone else’s scoreboard?',
						'<strong>Practice:</strong> Thank God for three gifts unique to your life. <em>Lord, godliness with contentment is enough. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Love does not envy',
					'verse_ref' => '1 Corinthians 13:4',
					'body'      => hwbl_plan_day_body(
						'Love and envy cannot share the throne.',
						'“Love is patient and kind; love does not envy or boast…”',
						'Is envy posing as ambition in a relationship?',
						'<strong>Practice:</strong> Choose one kind act toward someone you envy. <em>Lord, let love displace envy. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The Lord is my chosen portion',
					'verse_ref' => 'Psalm 16:5-6',
					'body'      => hwbl_plan_day_body(
						'Peace comes when God Himself is your portion—not a better lot than your neighbor’s.',
						'“The Lord is my chosen portion and my cup… The lines have fallen for me in pleasant places…”',
						'Can you call your lines pleasant because the Lord is your portion?',
						'<strong>Practice:</strong> Pray Psalm 16:5–6 over your actual life today. <em>Lord, You are my portion—I rest. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Unhurried with God (7 days)',
			'topic'     => 'hurry',
			'shareable' => false,
			'excerpt'   => 'Seven days against hurry—recovering presence with God and people when speed has become a false savior.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Be still',
					'verse_ref' => 'Psalm 46:10',
					'body'      => hwbl_plan_day_body(
						'Hurry pretends we are God. Stillness remembers we are not.',
						'“Be still, and know that I am God.” Knowing requires ceasing.',
						'What hurry habit blocks knowing God?',
						'<strong>Practice:</strong> Five still minutes—no phone—before the day runs. <em>God, I am still—You are God. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Martha, Martha',
					'verse_ref' => 'Luke 10:41-42',
					'body'      => hwbl_plan_day_body(
						'Anxiety about many things crowds the one necessary thing.',
						'“You are anxious and troubled about many things, but one thing is necessary.” Presence with Jesus outranks frantic service.',
						'What “many things” are bullying the one thing?',
						'<strong>Practice:</strong> Drop one nonessential and sit with a short Gospel reading. <em>Jesus, one thing is necessary—You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Wait for the Lord',
					'verse_ref' => 'Psalm 27:14',
					'body'      => hwbl_plan_day_body(
						'Hurry hates waiting. Faith learns courage in delay.',
						'“Wait for the Lord; be strong, and let your heart take courage; wait for the Lord!”',
						'Where is hurry a refusal to wait on God?',
						'<strong>Practice:</strong> Delay one impulsive decision 24 hours in prayer. <em>Lord, I wait for You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Make the best use of the time',
					'verse_ref' => 'Ephesians 5:15-16',
					'body'      => hwbl_plan_day_body(
						'Redeeming time is wisdom, not frantic packing of more.',
						'“Look carefully then how you walk, not as unwise but as wise, making the best use of the time, because the days are evil.”',
						'Is your calendar wise—or merely full?',
						'<strong>Practice:</strong> Remove one hurry-creating commitment this week. <em>Lord, help me walk carefully, not hurriedly. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Come to Me',
					'verse_ref' => 'Matthew 11:28-29',
					'body'      => hwbl_plan_day_body(
						'Jesus offers rest for souls worn by loads and pace.',
						'“Come to me, all who labor and are heavy laden, and I will give you rest… learn from me, for I am gentle and lowly in heart.”',
						'Have you been coming to productivity instead of to Jesus?',
						'<strong>Practice:</strong> Bring your hurried soul to Him for ten quiet minutes. <em>Jesus, I come—give me rest. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'A gentle and quiet spirit',
					'verse_ref' => '1 Peter 3:4',
					'body'      => hwbl_plan_day_body(
						'Hurry agitates the spirit. God values quiet strength.',
						'“…the imperishable beauty of a gentle and quiet spirit, which in God’s sight is very precious.” Quiet here is settled trust, not silence forced by fear.',
						'What would a quieter spirit look like in your pace today?',
						'<strong>Practice:</strong> Walk slower through one transition; pray while you move. <em>Lord, form a gentle, quiet spirit in me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'This is the day',
					'verse_ref' => 'Psalm 118:24',
					'body'      => hwbl_plan_day_body(
						'Hurry skips today chasing tomorrow. Worship receives the day as gift.',
						'“This is the day that the Lord has made; let us rejoice and be glad in it.”',
						'Are you missing today’s gift while racing to the next?',
						'<strong>Practice:</strong> Name three glad things in this day before bed. <em>Lord, I rejoice in the day You made. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Living for His Return (7 days)',
			'topic'     => 'return',
			'shareable' => true,
			'excerpt'   => 'Seven days on the hope of Christ’s return—watchfulness, holiness, courage, and comfort that steadies everyday faithfulness.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'This Jesus will come',
					'verse_ref' => 'Acts 1:11',
					'body'      => hwbl_plan_day_body(
						'Christian hope is not vague optimism. Jesus will return as surely as He ascended.',
						'“This Jesus, who was taken up from you into heaven, will come in the same way as you saw him go into heaven.”',
						'Does His return shape your week—or only your end-times curiosity?',
						'<strong>Practice:</strong> Begin the day: “Come, Lord Jesus—help me live ready.” <em>Lord Jesus, You will come—keep me ready. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Stay awake',
					'verse_ref' => 'Matthew 24:42',
					'body'      => hwbl_plan_day_body(
						'Watchfulness is faithful living, not frantic date-setting.',
						'“Therefore, stay awake, for you do not know on what day your Lord is coming.”',
						'Where have you gone spiritually drowsy?',
						'<strong>Practice:</strong> End the day with confession, thanks, and watchful prayer. <em>Lord, keep me awake in love. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Everyone who thus hopes',
					'verse_ref' => '1 John 3:2-3',
					'body'      => hwbl_plan_day_body(
						'Hope of seeing Christ purifies present life.',
						'“We know that when he appears we shall be like him… And everyone who thus hopes in him purifies himself as he is pure.”',
						'What impurity looks ridiculous in light of His appearing?',
						'<strong>Practice:</strong> Put away one practice that does not fit hope. <em>Lord, purify me as I hope in You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Encourage one another',
					'verse_ref' => '1 Thessalonians 4:16-18',
					'body'      => hwbl_plan_day_body(
						'The return of Christ comforts grieving believers.',
						'“For the Lord himself will descend from heaven… Therefore encourage one another with these words.”',
						'Whom can you encourage with this hope?',
						'<strong>Practice:</strong> Share this comfort with one person gently. <em>Coming Lord, use Your hope to encourage through me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Occupy until I come',
					'verse_ref' => 'Luke 19:13',
					'body'      => hwbl_plan_day_body(
						'Waiting for Christ is not idleness. Stewards work until He comes.',
						'“Calling ten of his servants, he gave them ten minas, and said to them, ‘Engage in business until I come.’”',
						'What trust are you neglecting while you “wait”?',
						'<strong>Practice:</strong> Faithfully do one entrusted task today. <em>Lord, I will occupy until You come. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Set your hope fully',
					'verse_ref' => '1 Peter 1:13',
					'body'      => hwbl_plan_day_body(
						'Hope needs a fixed mind, not a divided one.',
						'“Therefore, preparing your minds for action, and being sober-minded, set your hope fully on the grace that will be brought to you at the revelation of Jesus Christ.”',
						'Where is your hope only partly set on His appearing?',
						'<strong>Practice:</strong> Write one lesser hope you will demote under Christ’s return. <em>Lord, I set my hope fully on Your grace. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Amen. Come, Lord Jesus',
					'verse_ref' => 'Revelation 22:20',
					'body'      => hwbl_plan_day_body(
						'Scripture ends with longing, not fear, for those in Christ.',
						'“He who testifies to these things says, ‘Surely I am coming soon.’ Amen. Come, Lord Jesus!”',
						'Can you say Amen to His coming with joy?',
						'<strong>Practice:</strong> Pray Revelation 22:20 morning and night today. <em>Amen. Come, Lord Jesus! Amen.</em>'
					),
				),
			),
		),
	);
}
